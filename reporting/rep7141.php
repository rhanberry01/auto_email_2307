<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_GLREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	GL Accounts Transactions
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_GL_transactions();

//----------------------------------------------------------------------------------------------------
function get_non_apv_purchase($type, $type_no)
{
	$sql = "SELECT tran_date,
					SUM(if(account='5400',amount,0)) as p_nv,
					SUM(if(account='5450',amount,0)) as p_v,
					SUM(if(account='1410010' ,amount,0)) as vat,
					memo_
				FROM 0_gl_trans
				WHERE type = $type AND type_no = $type_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return array($row['memo_'],$row['p_nv'],$row['p_v'],$row['vat'],$row['tran_date']);
}

function get_oi_bd_payee($bd_id)
{
	$sql = "SELECT bd_payee, bd_memo FROM 0_other_income_payment_header WHERE bd_trans_no = $bd_id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function get_consigment_supplier()
{
	$sql = "SELECT supplier_id FROM `0_suppliers` WHERE consignment_email LIKE '%@%.%'";
	$res = db_query($sql);
	$array_ = array();
	while($row = db_fetch($res))
		$array_[] =$row[0];
	return $array_;
}

function get_consigment_supplier_ms()
{
	global $db_connections;
	
	$db_133 = $db_connections[$_SESSION["wa_current_user"]->company]["db_133"];
	$ms_db_133 = mssql_connect('192.168.0.133' , 'markuser', 'tseug');
    mssql_select_db($db_133,$ms_db_133);
	
	$sql = "SELECT vendorcode FROM vendor WHERE Consignor = 1";
	$res = mssql_query($sql, $ms_db_133);
	
	$supp_ref_array= array();
	while($row = mssql_fetch_array($res))
		$supp_ref_array[] = "'".$row[0]."'";
	
	$sql = "SELECT supplier_id FROM `0_suppliers` WHERE supp_ref IN (".implode(',',$supp_ref_array).")";
	// echo $sql;die;
	$res = db_query($sql);
	$array_ = array();
	while($row = db_fetch($res))
		$array_[] =$row[0];
	return $array_;
}

function print_GL_transactions()
{
	global $path_to_root, $systypes_array, $systypes_array_short;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$rep = new FrontReport(_('Purchase Report'), "PurchaseReport", 'LONG',8 ,'P');
	$dec = user_price_dec();


	$cols = array(0, 20, 130, 205, 275, 345, 415, 485, 555);
	$aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right');
	$headers = array('','Delivery Date', 'Inv. #', 'Amount','Purch. NON-VAT', 'Purch. VAT', '12% VAT', ' Others');
	//'Discount', 


	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to));
						
	if ($destination)
	{
		global $db_connections;
		$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"];
		$params[] = array('text' => _('Branch'), 'from' => $this_branch, 'to' => '');
	}

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	

	$last_supp_id = $supp_name = $last_gst = '';
	$counter = $supp_total_ = $ov_total = 0;
	
	
	$p_nv_total = $p_v_total = $v_total = $others_total = 0;
	
	$for_vat_total = $for_nv_total = $for_dr_total = $supp_total = array(0,0,0,0,0);
	$details = array();
	
	$consignment_purchases = $srs_as_supp_purchases = $no_purchase = array();
	// $consignment_supp = get_consigment_supplier();
	$consignment_supp = get_consigment_supplier_ms();
	
	$consignment_count = $srs_as_supp_count = $np_count = 0;
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
	for($xyz=0;$xyz<=2;$xyz++)
	{
		if($xyz == 0) // VAT
		{
			$rep->Font('bi');
			$rep->fontSize += 3;
			$rep->TextCol(0, 5, '     VATABLE Suppliers');
			$rep->Line($rep->row -5);
			$rep->NewLine(2);
			$rep->fontSize -= 3;
			
			$and = " AND tax_group_id = 1 AND TRIM(gst_no) != ''";
		}
		else if($xyz == 1) // NON VAT
		{
			
			// show VAT total
			$rep->Font('bold');
			$rep->Line($rep->row + 10,1);
			$rep->TextCol(0, 1,	'');
			$rep->TextCol(1, 2, '');
			$rep->TextCol(2, 3, 'VAT TOTAL : ');
			$rep->AmountCol(3, 4, $for_vat_total[0],2);
			$rep->AmountCol(4, 5, $for_vat_total[1],2);
			$rep->AmountCol(5, 6, $for_vat_total[2],2);
			$rep->AmountCol(6, 7, $for_vat_total[3],2);
			$rep->AmountCol(7, 8, $for_vat_total[4],2);
			$rep->Font('');
			$rep->NewLine();
			
			
			// $rep->NewPage();
			$rep->Header();
			$rep->Font('bi');
			$rep->fontSize += 3;
			$rep->TextCol(0, 5, '     NON-VATABLE Suppliers');
			$rep->Line($rep->row -5);
			$rep->NewLine(2);
			$rep->fontSize -= 3;
			
			$and = " AND tax_group_id != 1 AND TRIM(gst_no) != ''";
		}
		else if($xyz == 2) // DR
		{
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10,1);
			$rep->TextCol(0, 1,	'');
			$rep->TextCol(1, 2, '');
			$rep->TextCol(2, 3, 'NON VAT TOTAL : ');
			$rep->AmountCol(3, 4, $for_nv_total[0],2);
			$rep->AmountCol(4, 5, $for_nv_total[1],2);
			$rep->AmountCol(5, 6, $for_nv_total[2],2);
			$rep->AmountCol(6, 7, $for_nv_total[3],2);
			$rep->AmountCol(7, 8, $for_nv_total[4],2);
			$rep->Font('');
			$rep->NewLine();
			
			// $rep->NewPage();
			$rep->Header();
			$rep->Font('bi');
			$rep->fontSize += 3;
			$rep->TextCol(0, 5, '     DR Suppliers');
			$rep->Line($rep->row -5);
			$rep->NewLine(2);
			$rep->fontSize -= 3;
			
			$and = " AND TRIM(gst_no) = ''";
		}
		
		$sql = "SELECT DISTINCT c.type, c.trans_no, b.gst_no, b.supp_name, b.supplier_id,
							a.tran_date, c.del_date, c.supp_reference, c.ov_amount + c.ov_gst as total, b.tax_group_id
						FROM 0_gl_trans a, 0_suppliers b, 0_supp_trans c
						WHERE a.tran_date >= '".date2sql($from)."'
						AND a.tran_date <= '".date2sql($to)."'
						AND (account = '5400' OR account = '5450' OR account = '1410010')
						AND a.amount != 0
						AND ((a.type = 20 AND c.type = 20) OR (a.type = 24 AND c.type = 24))
						AND a.person_id = b.supplier_id
						AND a.type_no = c.trans_no
						AND a.type = c.type
						".$and."
						ORDER BY supp_name,gst_no, c.del_date";
		// echo $sql .'; <br><br> ';
		// continue;
		$res = db_query($sql,'error.');
		
		$company_pref = get_company_prefs();
		
		while ($row = db_fetch($res))
		{
			$counter ++;
			$last_gst = $row['gst_no'];

			$supp_name = $row['supp_name'];
			
			
			$vat = $p_nv = $p_v = 0;
			
			$p_nv = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_non_vat"]);
			$p_v = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_vat"]);
			// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410');
			$vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410010');
			// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410011');

			$others = round($row['total'],2) - round($p_nv+$p_v+$vat,2);
			
			// FOR NO PURCHASE BUT WITH VAT
			if ( abs(abs($p_v*(0.12)) - abs($vat)) > 1 )
			{
				$no_purchase[$np_count][0] = $supp_name . '   -   '.$last_gst;
				$no_purchase[$np_count][1] = sql2date($row['del_date']);
				$no_purchase[$np_count][2] = $row['supp_reference'];
				$no_purchase[$np_count][3] = round($p_nv,2);
				$no_purchase[$np_count][4] = round($p_v,2);
				$no_purchase[$np_count][5] = round($vat,2);
				$no_purchase[$np_count][6] = round($row['total'],2);
				$no_purchase[$np_count][7] = round($others,2);
				$np_count ++;
				continue;
			}
			
			//FOR SRS SUPPLIER
			if (strpos($supp_name, 'SAN ROQUE SUPERMARKET RETAIL SYSTEMS') !== false
				AND strpos($supp_name, '(') === false)
			{
				$srs_as_supp_purchases[$srs_as_supp_count][0] = $supp_name . '   -   '.$last_gst;
				$srs_as_supp_purchases[$srs_as_supp_count][1] = sql2date($row['del_date']);
				$srs_as_supp_purchases[$srs_as_supp_count][2] = $row['supp_reference'];
				$srs_as_supp_purchases[$srs_as_supp_count][3] = round($p_nv,2);
				$srs_as_supp_purchases[$srs_as_supp_count][4] = round($p_v,2);
				$srs_as_supp_purchases[$srs_as_supp_count][5] = round($vat,2);
				$srs_as_supp_purchases[$srs_as_supp_count][6] = round($row['total'],2);
				$srs_as_supp_purchases[$srs_as_supp_count][7] = round($others,2);
				$srs_as_supp_count ++;
				continue;
			}

			//FOR CONSIGNMENT
			if (in_array($row['supplier_id'], $consignment_supp))
			{
				$consignment_purchases[$consignment_count][0] = $supp_name . '   -   '.$last_gst;
				$consignment_purchases[$consignment_count][1] = sql2date($row['del_date']);
				$consignment_purchases[$consignment_count][2] = $row['supp_reference'];
				$consignment_purchases[$consignment_count][3] = round($p_nv,2);
				$consignment_purchases[$consignment_count][4] = round($p_v,2);
				$consignment_purchases[$consignment_count][5] = round($vat,2);
				$consignment_purchases[$consignment_count][6] = round($row['total'],2);
				$consignment_purchases[$consignment_count][7] = round($others,2);
				$consignment_count ++;
				continue;
			}

			// header
			if ($last_supp_id != $row['supplier_id'] )
			{
				if ($last_supp_id != '')
				{
					// total per supplier
					$rep->Line($rep->row+10);
					
					$rep->Font('bold');
					$rep->Line($rep->row + 10);
					
					if ($destination)
					{
						$rep->sheet->writeString($rep->y, 0, html_entity_decode(get_supplier_name($last_supp_id)). '   -   '.$last_gst1, $format_bold);
						$rep->sheet->writeString($rep->y, 1, '===>', $format_bold);
					}
					else
					{
						$rep->TextCol(0, 1,	'');
						$rep->TextCol(1, 2, '');
						$rep->TextCol(2, 3, '');
					}
					
					$rep->AmountCol(3, 4, $supp_total[0],2);
					$rep->AmountCol(4, 5, $supp_total[1],2);
					$rep->AmountCol(5, 6, $supp_total[2],2);
					$rep->AmountCol(6, 7, $supp_total[3],2);
					$rep->AmountCol(7, 8, $supp_total[4],2);
					$rep->Font('');
					$rep->NewLine(2);
					
					$supp_total = array(0,0,0,0,0);
				}
				
				$rep->font('bold');
				//$rep->TextCol(0, 5,	$supp_name . '   -   '.$last_gst);
				$rep->Line($rep->row -2);
				$rep->NewLine();
				$rep->font('');
			}
			//==================================
			$last_supp_id = $row['supplier_id'];
			$last_gst1 = $row['gst_no'];
			// totals -----------------------------------------------------
			
			$details[$counter][0] = '';
			$details[$counter][1] = sql2date($row['del_date']);
			$details[$counter][2] = $row['supp_reference'];
			$details[$counter][3] = round($p_nv,2);
			$details[$counter][4] = round($p_v,2);
			$details[$counter][5] = round($vat,2);
			$details[$counter][6] = round($row['total'],2);
			$details[$counter][7] = round($others,2);
			
			$p_nv_total += round($p_nv,2);
			$p_v_total += round($p_v,2); 
			$v_total += round($vat,2); 
			$ov_total += round($row['total'],2);
			$others_total += round($others,2);
			
			$supp_total[0] += $details[$counter][6];
			$supp_total[1] += $details[$counter][3];
			$supp_total[2] += $details[$counter][4];
			$supp_total[3] += $details[$counter][5];
			$supp_total[4] += $details[$counter][7];
			
			
			// if ($row['type'] == 24 OR $details[$counter][7] < 0)
			$rep->TextCol(0, 1,	html_entity_decode(get_supplier_name($last_supp_id)). '   -   '.$last_gst1);
			// $rep->TextCol(1, 2, $supp_name);
			$rep->TextCol(1, 2, $details[$counter][1]);
			$rep->TextCol(2, 3, $details[$counter][2] , -5);		
			$rep->AmountCol(3, 4, $details[$counter][6],2);
			$rep->AmountCol(4, 5, $details[$counter][3],2);
			$rep->AmountCol(5, 6, $details[$counter][4],2);
			$rep->AmountCol(6, 7, $details[$counter][5],2);
			
			if ($details[$counter][7] >= 0)
				$rep->AmountCol(7, 8, $details[$counter][7],2);
			else
				$rep->AmountCol(7, 8, $details[$counter][7],2,0, 0, 0, 0, NULL, 0, true);

			if ($xyz == 0) //VAT
			{
				$for_vat_total[0] += $details[$counter][6];
				$for_vat_total[1] += $details[$counter][3];
				$for_vat_total[2] += $details[$counter][4];
				$for_vat_total[3] += $details[$counter][5];
				$for_vat_total[4] += $details[$counter][7];
			}
			else if ($xyz == 1) //VAT
			{
				$for_nv_total[0] += $details[$counter][6];
				$for_nv_total[1] += $details[$counter][3];
				$for_nv_total[2] += $details[$counter][4];
				$for_nv_total[3] += $details[$counter][5];
				$for_nv_total[4] += $details[$counter][7];
			}
			else if ($xyz == 2) //DR
			{
				$for_dr_total[0] += $details[$counter][6];
				$for_dr_total[1] += $details[$counter][3];
				$for_dr_total[2] += $details[$counter][4];
				$for_dr_total[3] += $details[$counter][5];
				$for_dr_total[4] += $details[$counter][7];
			}
		
			$rep->NewLine();
		}
		
		if ($last_supp_id != '')
		{
			// total per supplier
			$rep->Line($rep->row+10);
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10);
			$rep->TextCol(0, 1,	'');
			$rep->TextCol(1, 2, '');
			$rep->TextCol(2, 3, '');
			$rep->AmountCol(3, 4, $supp_total[0],2);
			$rep->AmountCol(4, 5, $supp_total[1],2);
			$rep->AmountCol(5, 6, $supp_total[2],2);
			$rep->AmountCol(6, 7, $supp_total[3],2);
			$rep->AmountCol(7, 8, $supp_total[4],2);
			$rep->Font('');
			$rep->NewLine();
			
			$supp_total = array(0,0,0,0,0);
		}
			
	}
	// die;
	
	$rep->Font('bold');
	$rep->Line($rep->row + 10,1);
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 2, '');
	$rep->TextCol(2, 3, 'DR TOTAL : ');
	$rep->AmountCol(3, 4, $for_dr_total[0],2);
	$rep->AmountCol(4, 5, $for_dr_total[1],2);
	$rep->AmountCol(5, 6, $for_dr_total[2],2);
	$rep->AmountCol(6, 7, $for_dr_total[3],2);
	$rep->AmountCol(7, 8, $for_dr_total[4],2);
	$rep->Font('');
	$rep->NewLine();

	// ============================== APV TOTAL
	$rep->Font('bold');
	$rep->Line($rep->row + 10,1);
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 2, '');
	$rep->TextCol(2, 3, 'APV GRAND TOTAL : ');
	$rep->AmountCol(3, 4, $ov_total,2);
	$rep->AmountCol(4, 5, $p_nv_total,2);
	$rep->AmountCol(5, 6, $p_v_total,2);
	$rep->AmountCol(6, 7, $v_total,2);
	$rep->AmountCol(7, 8, $others_total,2);
	$rep->Font('');
	$rep->NewLine();
	
	
	//==================================================================
	// NON APV TRANSACTIONS WITH PURCHASE ACCOUNT
	$sql = "SELECT DISTINCT type,type_no, person_id 
				FROM 0_gl_trans WHERE (account = '5450' OR account = '5400' OR account = '1410010') 
				AND amount != 0
				AND tran_date >= '".date2sql($from)."' AND tran_date <= '".date2sql($to)."'
				AND type NOT IN (20,24)
				AND memo_ NOT LIKE 'Adjustment for Stock Transfer IN%'";
	// echo $sql;die;
	$res = db_query($sql);
	
	$display_grand_total = false;
	
	if (db_num_rows($res) > 0)
	{
		$rep->Header();
		$rep->Line($rep->row + 10,3);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 3,	'Non APV with Purchase / Purchase Non-Vat GL entry');
		$rep->NewLine();
		// $rep->Line($rep->row + 10,2);
		$rep->NewLine();
		$rep->Font('');
		
		$display_grand_total = true;
	}
	
	global $systypes_array;
	
	$non_apv_total = array();
	while ($row = db_fetch($res))
	{
		list($memo_,$p_nv,$p_v,$vat,$non_apv_tran_date) = get_non_apv_purchase($row['type'], $row['type_no']);
		$oi_payee = '';
		$non_apv_tran_date = sql2date($non_apv_tran_date);
		$amt = $others = 0;
		$amt = $p_nv+$p_v+$vat;
		
		$trans_ref = get_reference($row['type'], $row['type_no']);
		if ($row['type'] == 2) //other income
		{
			list($oi_payee,$memo_) = get_oi_bd_payee($row['type_no']);
			$trans_ref = $row['type_no'];
		}
		
		$over = $rep->TextCol(0, 3, $systypes_array[$row['type']].' - '.$non_apv_tran_date. ' - ' . $trans_ref . ' - '.  $oi_payee);
		
		if($memo_ != '')
		{
			$rep->NewLine();
			$over = $rep->TextCol(0, 4, '&nbsp;&nbsp;&nbsp;&nbsp;'.$memo_);
		}
		
		if ($p_nv)
			$rep->AmountCol(4, 5, $p_nv,2);
		else
			$rep->TextCol(4, 5, '');
		
		if ($p_v)
			$rep->AmountCol(5, 6, $p_v,2);
		else
			$rep->TextCol(5, 6, '');

		if ($vat)
			$rep->AmountCol(6, 7, $vat,2);
		else
			$rep->TextCol(6, 7, '');
		
		$p_nv_total += round($p_nv,2);
		$p_v_total += round($p_v,2); 
		$v_total += round($vat,2); 
		$ov_total += round($amt,2);
		$others_total += round($others,2);
		
		$non_apv_total[0] += round($amt,2);
		$non_apv_total[1] += round($p_nv,2);
		$non_apv_total[2] += round($p_v,2); 
		$non_apv_total[3] += round($vat,2); 
		$non_apv_total[4] += round($others,2);
		
		$rep->NewLine();	
	}
	
	if (count($non_apv_total) != 0)
	{
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 3, 'NON APV TOTAL : ');
		$rep->AmountCol(3, 4, $non_apv_total[0],2);
		$rep->AmountCol(4, 5, $non_apv_total[1],2);
		$rep->AmountCol(5, 6, $non_apv_total[2],2);
		$rep->AmountCol(6, 7, $non_apv_total[3],2);
		$rep->AmountCol(7, 8, $non_apv_total[4],2);
		$rep->Font('');
		$rep->NewLine();
	}
	//======================================================================
	
	// // NO PURCHASES =====================================
	$last_supp_id = '';
	$np_total = $supp_total = array(0,0,0,0,0);
	
	// echo count($no_purchase) . 'rextvumk;'; die;
	if (count($no_purchase) > 0)
	{
		$rep->Header();
		$rep->Line($rep->row + 10,3);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 3,	'APV with Input Tax not equal to 12% of Purchase VAT');
		$rep->NewLine();
		// $rep->Line($rep->row + 10,2);
		$rep->NewLine();
		$rep->Font('');
		
		$display_grand_total = true;
	
		foreach($no_purchase as $np)
		{
			// header
			if ($last_supp_id != $np[0])
			{
				if ($last_supp_id != '')
				{
					// total per supplier
					$rep->Line($rep->row+10);
					
					$rep->Font('bold');
					$rep->Line($rep->row + 10);
					
					if ($destination)
					{
						$rep->sheet->writeString($rep->y, 0, $last_supp_id, $format_bold);
						$rep->sheet->writeString($rep->y, 1, '===>', $format_bold);
					}
					else
					{
						$rep->TextCol(0, 1,	'');
						$rep->TextCol(1, 2, '');
						$rep->TextCol(2, 3, '');
					}
					
					$rep->AmountCol(3, 4, $supp_total[0],2);
					$rep->AmountCol(4, 5, $supp_total[1],2);
					$rep->AmountCol(5, 6, $supp_total[2],2);
					$rep->AmountCol(6, 7, $supp_total[3],2);
					$rep->AmountCol(7, 8, $supp_total[4],2);
					$rep->Font('');
					$rep->NewLine(2);
					
					$supp_total = array(0,0,0,0,0);
				}
				
				$rep->font('bold');
				$rep->TextCol(0, 5,	$np[0]);
				$rep->Line($rep->row -2);
				$rep->NewLine();
				$rep->font('');
			}
			
			// $rep->TextCol(1, 2, $supp_name);
			$rep->TextCol(1, 2, $np[1]);
			$rep->TextCol(2, 3, $np[2] , -5);		
			$rep->AmountCol(3, 4, $np[6],2);
			$rep->AmountCol(4, 5, $np[3],2);
			$rep->AmountCol(5, 6, $np[4],2);
			$rep->AmountCol(6, 7, $np[5],2);
			
			
			if ($np[7] >= 0)
				$rep->AmountCol(7, 8, $np[7],2);
			else
				$rep->AmountCol(7, 8, $np[7],2,0, 0, 0, 0, NULL, 0, true);
			
			$supp_total[0] += $np[6];
			$supp_total[1] += $np[3];
			$supp_total[2] += $np[4];
			$supp_total[3] += $np[5];
			$supp_total[4] += $np[7];
			
			$np_total[0] += $np[6];
			$np_total[1] += $np[3];
			$np_total[2] += $np[4];
			$np_total[3] += $np[5];
			$np_total[4] += $np[7];
			
			$p_nv_total += round($np[3],2);
			$p_v_total += round($np[4],2); 
			$v_total += round($np[5],2); 
			$ov_total += round($np[6],2);
			$others_total += round($np[7],2);
			
			$rep->NewLine();
			$last_supp_id = $np[0];
		}
		
		if ($last_supp_id != '')
		{
			// total per supplier
			$rep->Line($rep->row+10);
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10);
			
			if ($destination)
			{
				$rep->sheet->writeString($rep->y, 0, html_entity_decode(get_supplier_name($last_supp_id)), $format_bold);
				$rep->sheet->writeString($rep->y, 1, '===>', $format_bold);
			}
			else
			{
				$rep->TextCol(0, 1,	'');
				$rep->TextCol(1, 2, '');
				$rep->TextCol(2, 3, '');
			}
			
			$rep->AmountCol(3, 4, $supp_total[0],2);
			$rep->AmountCol(4, 5, $supp_total[1],2);
			$rep->AmountCol(5, 6, $supp_total[2],2);
			$rep->AmountCol(6, 7, $supp_total[3],2);
			$rep->AmountCol(7, 8, $supp_total[4],2);
			$rep->Font('');
			$rep->NewLine(2);
			
			$supp_total = array(0,0,0,0,0);
		}
	}
	
	if ($np_total[0] != 0 OR $np_total[1] != 0 OR $np_total[2] != 0 OR $np_total[3] != 0 OR $np_total[4] != 0)
	{
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 3, 'APV TOTAL : ');
		$rep->AmountCol(3, 4, $np_total[0],2);
		$rep->AmountCol(4, 5, $np_total[1],2);
		$rep->AmountCol(5, 6, $np_total[2],2);
		$rep->AmountCol(6, 7, $np_total[3],2);
		$rep->AmountCol(7, 8, $np_total[4],2);
		$rep->Font('');
		$rep->NewLine();
	}
	// ==================================================
	
	// // SRS SUPPLIER =====================================
	$last_supp_id = '';
	$srs_supp_total = $supp_total = array(0,0,0,0,0);
	
	// echo count($srs_as_supp_purchases) . 'rextvumk;'; die;
	if (count($srs_as_supp_purchases) > 0)
	{
		$rep->Header();
		$rep->Line($rep->row + 10,3);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 3,	'APV with San Roque Supplier');
		$rep->NewLine();
		// $rep->Line($rep->row + 10,2);
		$rep->NewLine();
		$rep->Font('');
		
		$display_grand_total = true;
	
		foreach($srs_as_supp_purchases as $srs_supp)
		{
			// header
			if ($last_supp_id != $srs_supp[0])
			{
				if ($last_supp_id != '')
				{
					// total per supplier
					$rep->Line($rep->row+10);
					
					$rep->Font('bold');
					$rep->Line($rep->row + 10);
					
					if ($destination)
					{
						$rep->sheet->writeString($rep->y, 0, $last_supp_id, $format_bold);
						$rep->sheet->writeString($rep->y, 1, '===>', $format_bold);
					}
					else
					{
						$rep->TextCol(0, 1,	'');
						$rep->TextCol(1, 2, '');
						$rep->TextCol(2, 3, '');
					}
					
					$rep->AmountCol(3, 4, $supp_total[0],2);
					$rep->AmountCol(4, 5, $supp_total[1],2);
					$rep->AmountCol(5, 6, $supp_total[2],2);
					$rep->AmountCol(6, 7, $supp_total[3],2);
					$rep->AmountCol(7, 8, $supp_total[4],2);
					$rep->Font('');
					$rep->NewLine(2);
					
					$supp_total = array(0,0,0,0,0);
				}
				
				$rep->font('bold');
				//$rep->TextCol(0, 5,	$srs_supp[0]);
				$rep->Line($rep->row -2);
				$rep->NewLine();
				$rep->font('');
			}
			
			// $rep->TextCol(1, 2, $supp_name);
			$rep->TextCol(1, 2, $srs_supp[1]);
			$rep->TextCol(2, 3, $srs_supp[2] , -5);		
			$rep->AmountCol(3, 4, $srs_supp[6],2);
			$rep->AmountCol(4, 5, $srs_supp[3],2);
			$rep->AmountCol(5, 6, $srs_supp[4],2);
			$rep->AmountCol(6, 7, $srs_supp[5],2);
			
			
			if ($srs_supp[7] >= 0)
				$rep->AmountCol(7, 8, $srs_supp[7],2);
			else
				$rep->AmountCol(7, 8, $srs_supp[7],2,0, 0, 0, 0, NULL, 0, true);
			
			$supp_total[0] += $srs_supp[6];
			$supp_total[1] += $srs_supp[3];
			$supp_total[2] += $srs_supp[4];
			$supp_total[3] += $srs_supp[5];
			$supp_total[4] += $srs_supp[7];
			
			$srs_supp_total[0] += $srs_supp[6];
			$srs_supp_total[1] += $srs_supp[3];
			$srs_supp_total[2] += $srs_supp[4];
			$srs_supp_total[3] += $srs_supp[5];
			$srs_supp_total[4] += $srs_supp[7];
			
			$p_nv_total += round($srs_supp[3],2);
			$p_v_total += round($srs_supp[4],2); 
			$v_total += round($srs_supp[5],2); 
			$ov_total += round($srs_supp[6],2);
			$others_total += round($srs_supp[7],2);
			
			$rep->NewLine();
			$last_supp_id = $srs_supp[0];
		}
		
		if ($last_supp_id != '')
		{
			// total per supplier
			$rep->Line($rep->row+10);
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10);
			
			if ($destination)
			{
				$rep->sheet->writeString($rep->y, 0, $last_supp_id, $format_bold);
				$rep->sheet->writeString($rep->y, 1, '===>', $format_bold);
			}
			else
			{
				$rep->TextCol(0, 1,	'');
				$rep->TextCol(1, 2, '');
				$rep->TextCol(2, 3, '');
			}
			
			$rep->AmountCol(3, 4, $supp_total[0],2);
			$rep->AmountCol(4, 5, $supp_total[1],2);
			$rep->AmountCol(5, 6, $supp_total[2],2);
			$rep->AmountCol(6, 7, $supp_total[3],2);
			$rep->AmountCol(7, 8, $supp_total[4],2);
			$rep->Font('');
			$rep->NewLine(2);
			
			$supp_total = array(0,0,0,0,0);
		}
	
		if (count($srs_supp_total) > 0)
		{
			$rep->Font('bold');
			$rep->Line($rep->row + 10,1);
			$rep->TextCol(0, 1,	'');
			$rep->TextCol(1, 2, '');
			$rep->TextCol(2, 3, 'APV TOTAL : ');
			$rep->AmountCol(3, 4, $srs_supp_total[0],2);
			$rep->AmountCol(4, 5, $srs_supp_total[1],2);
			$rep->AmountCol(5, 6, $srs_supp_total[2],2);
			$rep->AmountCol(6, 7, $srs_supp_total[3],2);
			$rep->AmountCol(7, 8, $srs_supp_total[4],2);
			$rep->Font('');
			$rep->NewLine();
		}
	}
	// ==================================================
	
	// // CONSIGNMENT =====================================
	$last_supp_id = '';
	$srs_consignment_total = $consignment_total = array(0,0,0,0,0);
	
	// echo count($consignment_purchases) . '<-- count;'; die;
	if (count($consignment_purchases) > 0)
	{
		$rep->Header();
		$rep->Line($rep->row + 10,3);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 3,	'Consignment');
		$rep->NewLine();
		// $rep->Line($rep->row + 10,2);
		$rep->NewLine();
		$rep->Font('');
		
		$display_grand_total = true;
	
		foreach($consignment_purchases as $cons_supp)
		{
			// header
			if ($last_supp_id != $cons_supp[0])
			{
				if ($last_supp_id != '')
				{
					// total per supplier
					$rep->Line($rep->row+10);

					$rep->Font('bold');
					$rep->Line($rep->row + 10);
					
					if ($destination)
					{
						$rep->sheet->writeString($rep->y, 0, $last_supp_id, $format_bold);
						$rep->sheet->writeString($rep->y, 1, '===>', $format_bold);
					}
					else
					{
						$rep->TextCol(0, 1,	'');
						$rep->TextCol(1, 2, '');
						$rep->TextCol(2, 3, '');
					}
					
					$rep->AmountCol(3, 4, $consignment_total[0],2);
					$rep->AmountCol(4, 5, $consignment_total[1],2);
					$rep->AmountCol(5, 6, $consignment_total[2],2);
					$rep->AmountCol(6, 7, $consignment_total[3],2);
					$rep->AmountCol(7, 8, $consignment_total[4],2);
					$rep->Font('');
					$rep->NewLine(2);
					
					$consignment_total = array(0,0,0,0,0);
				}

				$rep->font('bold');
				//$rep->TextCol(0, 5,	$cons_supp[0]);
				$rep->Line($rep->row -2);
				$rep->NewLine();
				$rep->font('');
			}
			
			$rep->TextCol(0, 1,	$cons_supp[0]);
			$rep->TextCol(1, 2, $cons_supp[1]);
			$rep->TextCol(2, 3, $cons_supp[2] , -5);		
			$rep->AmountCol(3, 4, $cons_supp[6],2);
			$rep->AmountCol(4, 5, $cons_supp[3],2);
			$rep->AmountCol(5, 6, $cons_supp[4],2);
			$rep->AmountCol(6, 7, $cons_supp[5],2);
			
			
			if ($cons_supp[7] >= 0)
				$rep->AmountCol(7, 8, $cons_supp[7],2);
			else
				$rep->AmountCol(7, 8, $cons_supp[7],2,0, 0, 0, 0, NULL, 0, true);
			
			$consignment_total[0] += $cons_supp[6];
			$consignment_total[1] += $cons_supp[3];
			$consignment_total[2] += $cons_supp[4];
			$consignment_total[3] += $cons_supp[5];
			$consignment_total[4] += $cons_supp[7];
			
			$srs_consignment_total[0] += $cons_supp[6];
			$srs_consignment_total[1] += $cons_supp[3];
			$srs_consignment_total[2] += $cons_supp[4];
			$srs_consignment_total[3] += $cons_supp[5];
			$srs_consignment_total[4] += $cons_supp[7];
			
			$p_nv_total += round($cons_supp[3],2);
			$p_v_total += round($cons_supp[4],2); 
			$v_total += round($cons_supp[5],2); 
			$ov_total += round($cons_supp[6],2);
			$others_total += round($cons_supp[7],2);
			
			$rep->NewLine();
			$last_supp_id = $cons_supp[0];
		}
		
		if ($last_supp_id != '')
		{
			// total per supplier
			$rep->Line($rep->row+10);
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10);
			
			if ($destination)
			{
				$rep->sheet->writeString($rep->y, 0, $last_supp_id, $format_bold);
				$rep->sheet->writeString($rep->y, 1, '===>', $format_bold);
			}
			else
			{
				$rep->TextCol(0, 1,	'');
				$rep->TextCol(1, 2, '');
				$rep->TextCol(2, 3, '');
			}
			
			$rep->AmountCol(3, 4, $consignment_total[0],2);
			$rep->AmountCol(4, 5, $consignment_total[1],2);
			$rep->AmountCol(5, 6, $consignment_total[2],2);
			$rep->AmountCol(6, 7, $consignment_total[3],2);
			$rep->AmountCol(7, 8, $consignment_total[4],2);
			$rep->Font('');
			$rep->NewLine(2);
			
			$consignment_total = array(0,0,0,0,0);
		}
	
		if (count($srs_consignment_total) > 0)
		{
			$rep->Font('bold');
			$rep->Line($rep->row + 10,1);
			$rep->TextCol(0, 1,	'');
			$rep->TextCol(1, 2, '');
			$rep->TextCol(2, 3, 'CONSIGNMENT TOTAL : ');
			$rep->AmountCol(3, 4, $srs_consignment_total[0],2);
			$rep->AmountCol(4, 5, $srs_consignment_total[1],2);
			$rep->AmountCol(5, 6, $srs_consignment_total[2],2);
			$rep->AmountCol(6, 7, $srs_consignment_total[3],2);
			$rep->AmountCol(7, 8, $srs_consignment_total[4],2);
			$rep->Font('');
			$rep->NewLine();
		}
	}
	// ==================================================
	
	
	// ADJUSTMENTS for TRANSFERS with APV
	// ==================================================
	$sql = "SELECT DISTINCT type,type_no, person_id 
				FROM 0_gl_trans WHERE (account = '5450' OR account = '5400' OR account = '1410010') 
				AND amount != 0
				AND tran_date >= '".date2sql($from)."' AND tran_date <= '".date2sql($to)."'
				AND type NOT IN (20,24)
				AND memo_ LIKE 'Adjustment for Stock Transfer IN%'";
	// echo $sql;die;
	$res = db_query($sql);
	
	$display_grand_total = true;
	
	if (db_num_rows($res) > 0)
	{
		$rep->Header();
		$rep->Line($rep->row + 10,3);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 3,	'Adjustment for Transfers with APV');
		$rep->NewLine();
		// $rep->Line($rep->row + 10,2);
		$rep->NewLine();
		$rep->Font('');
		
		$display_grand_total = true;
	}
	
	global $systypes_array;
	
	$adj_for_transfers_total = array();
	while ($row = db_fetch($res))
	{
		list($memo_,$p_nv,$p_v,$vat,$non_apv_tran_date) = get_non_apv_purchase($row['type'], $row['type_no']);
		$non_apv_tran_date = sql2date($non_apv_tran_date);
		$amt = $others = 0;
		
		$amt = $p_nv+$p_v+$vat;
		$rep->TextCol(0, 3, $systypes_array_short[$row['type']].' - '.$non_apv_tran_date. ' - ' . get_reference($row['type'], $row['type_no']) .' - ' .$memo_);
		if ($p_nv)
			$rep->AmountCol(4, 5, $p_nv,2);
		else
			$rep->TextCol(4, 5, '');
		
		if ($p_v)
			$rep->AmountCol(5, 6, $p_v,2);
		else
			$rep->TextCol(5, 6, '');

		if ($vat)
			$rep->AmountCol(6, 7, $vat,2);
		else
			$rep->TextCol(6, 7, '');
		
		$ov_total += round($amt,2);
		$p_nv_total += round($p_nv,2);
		$p_v_total += round($p_v,2); 
		$v_total += round($vat,2); 
		$others_total += round($others,2);
		
		$adj_for_transfers_total[0] += round($amt,2);
		$adj_for_transfers_total[1] += round($p_nv,2); 
		$adj_for_transfers_total[2] += round($p_v,2); 
		$adj_for_transfers_total[3] += round($vat,2);
		$adj_for_transfers_total[4] += round($others,2);
		
		
		$rep->NewLine();	
	}
	
	if (count($adj_for_transfers_total) != 0)
	{
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 3, 'ADJUSTMENTS TOTAL : ');
		$rep->AmountCol(3, 4, $adj_for_transfers_total[0],2);
		$rep->AmountCol(4, 5, $adj_for_transfers_total[1],2);
		$rep->AmountCol(5, 6, $adj_for_transfers_total[2],2);
		$rep->AmountCol(6, 7, $adj_for_transfers_total[3],2);
		$rep->AmountCol(7, 8, $adj_for_transfers_total[4],2);
		$rep->Font('');
		$rep->NewLine();
	}
	//======================================================================
	
	// if ($display_grand_total)
	// {
		$rep->FontSize +=2;
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 3, 'GRAND TOTAL : ');
		$rep->AmountCol(3, 4, $ov_total,2);
		$rep->AmountCol(4, 5, $p_nv_total,2);
		$rep->AmountCol(5, 6, $p_v_total,2);
		$rep->AmountCol(6, 7, $v_total,2);
		$rep->AmountCol(7, 8, $others_total,2);
		$rep->NewLine();
		$rep->FontSize -= 2;
	// }
	$rep->NewLine(2);
	// $rep->Line($rep->row + 10,1);
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 3, 'Purchase VAT : ');
	$rep->AmountCol(3, 4, $p_v_total,2);
	$rep->NewLine();	
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 3, 'Purchase NON VAT : ');
	$rep->AmountCol(3, 4, $p_nv_total,2);
	$rep->NewLine();	
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 3, 'Input VAT : ');
	$rep->AmountCol(3, 4, $v_total,2);
	$rep->Font('');
	$rep->NewLine();
	
	$rep->End();
}

?>