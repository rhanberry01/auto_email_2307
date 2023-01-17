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



function print_GL_transactions()
{
	set_time_limit(0);
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

	//$rep = new FrontReport(_('Purchase Report'), "PurchaseReport", 'LONG',8 ,'P');
	$rep = new FrontReport(_('Accounts Payable - Aging Reports'), "AgingReport", user_pagesize(),9 ,'L');
	$dec = user_price_dec();


	$cols = array(0, 20, 130, 205, 275, 345, 415, 485, 555, 630,700);
	$aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right','right','right');
	$headers = array('','Date', 'Reference', 'Amount','1-30 Days', '31-60 Days', '61-90 Days', '91-120 Days', 'Over 120 Days','No. of Days');
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

	$consignment_count = $srs_as_supp_count = $np_count = 0;
	

		if($xyz == 0) // VAT
		{
			$rep->Font('bi');
			$rep->fontSize += 3;
			//$rep->TextCol(0, 5, '     VATABLE Suppliers');
			$rep->Line($rep->row -5);
			$rep->NewLine(2);
			$rep->fontSize -= 3;
		}
		
// $sql = "SELECT st.*, gl.amount FROM 0_supp_trans as st
// LEFT JOIN 0_gl_trans as gl
// on st.trans_no=gl.type_no
// where st.type=20 and gl.type=20
// and gl.tran_date >= '".date2sql($from)."' and gl.tran_date <= '".date2sql($to)."'
// and gl.amount!=0
// and gl.amount<0
// and gl.account IN (2000,2000010)
// and cv_id=0";
		
		
		//ORIG---
		
	// $sql = "SELECT DISTINCT c.type, c.trans_no, b.gst_no, b.supp_name, b.supplier_id,
				// a.tran_date, c.del_date, c.due_date, c.supp_reference, c.ov_amount + c.ov_gst as total, b.tax_group_id, ABS(a.amount) as t
				// FROM 0_gl_trans a, 0_suppliers b, 0_supp_trans c
				// WHERE a.tran_date >= '".date2sql($from)."'
				// AND a.tran_date <= '".date2sql($to)."'
				// AND a.amount != 0
				// AND a.amount<0
				// AND (a.type = 20 AND c.type = 20)
				// AND a.person_id = b.supplier_id
				// AND a.type_no = c.trans_no
				// AND a.type = c.type
				// and a.account IN (2000,2000010)
				// AND c.cv_id=0
				// ORDER BY supp_name,gst_no, c.del_date";

		// $sql = "SELECT DISTINCT c.type, c.trans_no, b.gst_no, b.supp_name, b.supplier_id,
						// a.tran_date, c.del_date, c.due_date, c.supp_reference, c.ov_amount + c.ov_gst as total, b.tax_group_id, a.amount
						// FROM 0_gl_trans a, 0_suppliers b, 0_supp_trans c
						// WHERE a.tran_date >= '".date2sql($from)."'
						// AND a.tran_date <= '".date2sql($to)."'
						// AND a.amount != 0
						// AND (a.type = 20 AND c.type = 20)
						// AND a.person_id = b.supplier_id
						// AND a.type_no = c.trans_no
						// AND a.type = c.type
						// AND c.cv_id=0
						// ORDER BY supp_name,gst_no, c.del_date";
		// echo $sql .'; <br><br> ';
		// continue;
		
		
		
		
		$sql = "SELECT a.type,a.type_no,a.tran_date,a.account,a.person_id,a.amount, a.memo_,c.supp_name,st.supplier_id,st.del_date,
		st.supp_reference, st.cv_id, st.special_reference, st.reference FROM 0_gl_trans a
		JOIN 0_chart_master b ON a.account = b.account_code
		LEFT OUTER JOIN  0_suppliers c ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
		LEFT JOIN 0_supp_trans as st
		on (a.type_no=st.trans_no and a.type=st.type) 
		WHERE a.tran_date >= '".date2sql($from)."'
		AND a.tran_date <= '".date2sql($to)."'
		AND a.amount!='0'
		AND a.amount<0
		ORDER BY c.supp_name, a.type, a.tran_date, a.amount";

		
		$res = db_query($sql,'error.');
		
		$company_pref = get_company_prefs();
		
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
						//$rep->sheet->writeString($rep->y, 2, '===> TOTAL:', $rep->formatTitle);
						$rep->sheet->writeString($rep->y, 2, '===> TOTAL:');
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
					$rep->AmountCol(8, 9, $supp_total[5],2);
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
			
			$age_days=date_diff2($to,sql2date($row["del_date"]),"d");
			//$age_days=date_diff2(Today(),sql2date($row["del_date"]),"d");
			//$due_days=date_diff2(Today(),sql2date($row["due_date"]),"d");
			

			
			
			$details[$counter][0] = '';
			
			if ($row['del_date']!='' and $row['del_date']!='0000-00-00' ){
			$details[$counter][1] = sql2date($row['del_date']);
			}
			else{
			$details[$counter][1] = sql2date($row['tran_date']);
			}
			
			
			
			
			
			if ($row['supp_reference']!=''){
				$details[$counter][2] = $row['supp_reference'];
			}
			else if ($row['supp_reference']=='' and $row['memo_']!='' ){
				$details[$counter][2] = $row['memo_'];
			}
			else if ($row['supp_reference']=='' and $row['memo_']=='' and $row['special_reference']!=''){
				$details[$counter][2] = $row['special_reference'];
			}
			else{
				$details[$counter][2] = $row['reference'];
			}
			
			
			if(($age_days>=0 and $age_days<=30) and $row['type']==20 and $row['bank_trans_id']==0){
				$details[$counter][3] = round($row['amount'],2);
				$day_1_30+=$details[$counter][3];
			}
			else{
				$details[$counter][3] = round(0,2);
			}
			
			if(($age_days>=31 and $age_days<=60) and $row['type']==20 and $row['bank_trans_id']==0){
				$details[$counter][4] = round($row['amount'],2);
				$day_31_60+=$details[$counter][4];
			}
			else{
				$details[$counter][4] = round(0,2);
			}
			
			if(($age_days>=61 and $age_days<=90) and $row['type']==20 and $row['bank_trans_id']==0){
				$details[$counter][5] = round($row['amount'],2);
				$day_61_90+=$details[$counter][5];
			}
			else{
				$details[$counter][5] = round(0,2);
			}
			
			if(($age_days>=91 and $age_days<=120) and $row['type']==20 and $row['bank_trans_id']==0){
			$details[$counter][7] = round($row['amount'],2);
			$day_91_120+=$details[$counter][7] ;
			}
			else{
				$details[$counter][7] = round(0,2);
			}
			
			if(($age_days>=121)and $row['type']==20 and $row['bank_trans_id']==0){
			$details[$counter][8] = round($row['amount'],2);
			$day_over_120+=$details[$counter][8] ;
			}
			else{
				$details[$counter][8] = round(0,2);
			}
			
			// if($due_days<0){
				// $details[$counter][9] = 0;
			// }
			// else{
				// $details[$counter][9] = $due_days;
			// }
			
			if($row['type']==20 and $row['bank_trans_id']==0){
				$details[$counter][9] = $age_days;
			}
			
			
			
	
			$details[$counter][6] = round($row['amount'],2);
			//$details[$counter][7] = $age_days;
			
			//$p_nv_total += round($p_nv,2);
			//$p_v_total += round($p_v,2); 
			//	$v_total += round($vat,2); 
			$ov_total += round($row['amount'],2);
			//$others_total += round($others,2);
			
			$supp_total[0] += $details[$counter][6];
			$supp_total[1] += $details[$counter][3];
			$supp_total[2] += $details[$counter][4];
			$supp_total[3] += $details[$counter][5];
			$supp_total[4] += $details[$counter][7];
			$supp_total[5] += $details[$counter][8];
			
			
			$rep->TextCol(0, 1, $systypes_array_short[$row["type"]], -2);
			// $rep->TextCol(1, 2, $supp_name);
			$rep->TextCol(1, 2, $details[$counter][1]);
			$rep->TextCol(2, 3, $details[$counter][2] , -5);		
			$rep->AmountCol(3, 4, $details[$counter][6],2);
			$rep->AmountCol(4, 5, $details[$counter][3],2);
			$rep->AmountCol(5, 6, $details[$counter][4],2);
			$rep->AmountCol(6, 7, $details[$counter][5],2);
			$rep->AmountCol(7, 8, $details[$counter][7],2);
			$rep->AmountCol(8, 9, $details[$counter][8],2);
			$rep->AmountCol(9, 10, $details[$counter][9]);
			
			$rep->NewLine();
			
		//	$supp_total[0] += $details[$counter][6];
			
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
			$rep->AmountCol(8, 9, $supp_total[5],2);
			$rep->Font('');
			$rep->NewLine();
			
			$supp_total = array(0,0,0,0,0);
		}
			
	
	// die;
	
	$rep->NewLine();

	// ============================== APV TOTAL
	$rep->Font('bold');
	$rep->Line($rep->row + 10,1);
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 2, '');
	$rep->TextCol(2, 3, 'APV GRAND TOTAL : ');
	$rep->AmountCol(3, 4, $ov_total,2);
	$rep->AmountCol(4, 5, $day_1_30,2);
	$rep->AmountCol(5, 6, $day_31_60,2);
	$rep->AmountCol(6, 7, $day_61_90,2);
	$rep->AmountCol(7, 8, $day_91_120,2);
	$rep->AmountCol(8, 9, $day_over_120,2);
	$rep->Font('');
	$rep->NewLine();
	
	$rep->End();
}

?>