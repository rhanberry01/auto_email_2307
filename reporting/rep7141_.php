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
					SUM(if((account='2310' OR account='1410' OR account='1410010' OR account='1410011') ,amount,0)) as vat,
					memo_
				FROM 0_gl_trans
				WHERE type = $type AND type_no = $type_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return array($row['memo_'],$row['p_nv'],$row['p_v'],$row['vat']);
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
						
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	

	$last_supp_id = $supp_name = $last_gst = '';
	$counter = $supp_total_ = $ov_total = 0;
	
	
	$p_nv_total = $p_v_total = $v_total = $others_total = 0;
	
	$for_vat_total = $for_nv_total = $for_dr_total = $supp_total = array(0,0,0,0,0);
	$details = array();
	
	$no_purchase = array();
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
						AND amount > 0
						AND ((a.type = 20 AND c.type = 20) OR (a.type = 24 AND c.type = 24))
						AND a.person_id = b.supplier_id
						AND a.type_no = c.trans_no
						AND a.type = c.type
						".$and."
						ORDER BY supp_name,gst_no, c.del_date";
							
		$res = db_query($sql,'error.');
		
		$company_pref = get_company_prefs();
		$np_count = 0;
		while ($row = db_fetch($res))
		{
			$counter ++;
			

			$supp_name = $row['supp_name'];
			$last_gst = $row['gst_no'];
			
			$vat = $p_nv = $p_v = 0;
			
			$p_nv = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_non_vat"]);
			$p_v = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_vat"]);
			// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410');
			$vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410010');
			// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410011');

			$others = round($row['total'],2) - round($p_nv+$p_v+$vat,2);
			
				
			if ( abs(abs($p_v*(0.12)) - abs($vat)) > 1 )
			{
				$np_count ++;
				$no_purchase[$np_count][0] = $supp_name . '   -   '.$last_gst;
				$no_purchase[$np_count][1] = sql2date($row['del_date']);
				$no_purchase[$np_count][2] = $row['supp_reference'];
				$no_purchase[$np_count][3] = round($p_nv,2);
				$no_purchase[$np_count][4] = round($p_v,2);
				$no_purchase[$np_count][5] = round($vat,2);
				$no_purchase[$np_count][6] = round($row['total'],2);
				$no_purchase[$np_count][7] = round($others,2);
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
						$rep->sheet->writeString($rep->y, 2, '===>', $rep->formatTitle);
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
				$rep->TextCol(0, 5,	$supp_name . '   -   '.$last_gst);
				$rep->Line($rep->row -2);
				$rep->NewLine();
				$rep->font('');
			}
			//==================================
			$last_supp_id = $row['supplier_id'];
			
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
			
			
			if ($row['type'] == 24 OR $details[$counter][7] < 0)
				$rep->TextCol(0, 1,	($details[$counter][7] < 0 ? '      CWO-APV' : '      CWO'));
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
	
	
	$sql = "SELECT DISTINCT type,type_no, person_id 
				FROM 0_gl_trans WHERE (account = '5450' OR account = '5400') 
				AND tran_date >= '".date2sql($from)."' AND tran_date <= '".date2sql($to)."'
				AND (type != 20 AND type != 24)";
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
	while ($row = db_fetch($res))
	{
		list($memo_,$p_nv,$p_v,$vat) = get_non_apv_purchase($row['type'], $row['type_no']);
		$amt = $others = 0;
		
		$rep->TextCol(0, 3, $systypes_array[$row['type']].' '.$row['type_no'].' - ' .$memo_);
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
		$rep->NewLine();
		
	}
	
	// // NO PURCHASES =====================================
	$last_supp_id = '';
	$supp_total = array(0,0,0,0,0);
	
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
	}
	
	foreach($no_purchase as $np)
	{
		// $no_purchase[$np_count][0] = $supp_name . '   -   '.$last_gst;
		// $no_purchase[$np_count][1] = sql2date($row['del_date']);
		// $no_purchase[$np_count][2] = $row['supp_reference'];
		// $no_purchase[$np_count][3] = round($p_nv,2);
		// $no_purchase[$np_count][4] = round($p_v,2);
		// $no_purchase[$np_count][5] = round($vat,2);
		// $no_purchase[$np_count][6] = round($row['total'],2);
		// $no_purchase[$np_count][7] = round($others,2);
		
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
					$rep->sheet->writeString($rep->y, 2, '===>', $rep->formatTitle);
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
			$rep->sheet->writeString($rep->y, 2, '===>', $rep->formatTitle);
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
	// ==================================================
	
	if ($display_grand_total)
	{
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
		$rep->Font('');
		$rep->NewLine();
		$rep->FontSize -= 2;
	}
	$rep->End();
}

?>