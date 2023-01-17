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

function count_apv($from, $to, $supp_id,$t_nt)
{
	$sql="
	select count(*) as counter from (select a.*,b.id as cv_id2, b.bank_trans_id as cv_bt_id, c.id  as bank_trans_id from(SELECT a.type,a.type_no,a.tran_date,a.account,a.person_id,
	a.amount, a.memo_,c.supp_name,st.supplier_id,st.del_date, 
	st.supp_reference, st.special_reference, st.reference,st.cv_id
	FROM 0_gl_trans a JOIN 0_chart_master b ON a.account = b.account_code 
	LEFT  JOIN 0_suppliers c
	ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
	LEFT JOIN 0_supp_trans as st on (a.type_no=st.trans_no and a.type=st.type) 
			WHERE a.tran_date >= '".date2sql($from)."'
			AND a.tran_date <= '".date2sql($to)."'
	AND a.amount!='0' AND a.amount<0 AND a.type IN (20,53) 
	AND st.is_cwo=0 ";

		if ($t_nt == 1) {//trade{
			$sql .= " AND (st.reference NOT LIKE 'NT%')";
			$sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		}
		
		else if ($t_nt == 0) {//non trade
			$sql .= "  AND (st.reference LIKE 'NT%')";
			$sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		}
		else{
			$sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		}

		$sql.=") as a
		LEFT JOIN (
		SELECT * FROM 0_cv_header
		) b ON a.cv_id = b.id
		LEFT JOIN (
		SELECT * FROM 0_bank_trans as bt where trans_date <= '".date2sql($to)."'
		) c ON b.bank_trans_id = c.id
		WHERE ISNULL(c.id)
		) as final
		where supplier_id='$supp_id'
		and type=20";

		
		
		//display_error($sql);
	
		$res = db_query($sql,'error.');
		$row=db_fetch($res);
		return $row['counter'];
		
}


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

function get_bank_trans_of_cv($bank_trans_id,$trans_date)
 {
	$sql = "SELECT * FROM ".TB_PREF."bank_trans WHERE id IN (".$bank_trans_id.") and trans_date<='$trans_date' and amount!=0";
	$res = db_query($sql,'no bank trans found for this bank ref');
	//display_error($sql);
	return db_fetch($res);
 }

 
function check_ap_sched($type, $refx, $amount, $date)
{
	$sql = "SELECT * FROM ap_sched
				WHERE type = '$type'
				AND amount = '$amount'
				AND ref = '$refx'
				AND date='$date'
				";
	//display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function check_cv_negative($type, $type_no)
{
	$sql = "SELECT cv_id FROM 0_supp_trans
				WHERE type = $type
				AND type_no = $type_no";
	$res = db_query($sql);
	return db_num_rows($res) == 0;
}

function check_ov_amount($type, $refx, $amount, $date)
{
	$supp_id='';
	
	if($refx=='0'){
	$sql = "SELECT * FROM 0_supp_trans
				WHERE type = $type
				AND tran_date = '$date'
				AND ov_amount='$amount'";
	$res = db_query($sql);
	$row = db_fetch($res);
	display_error($sql);
	}
	
	if($refx!='0'){
	$sql = "SELECT * FROM 0_supp_trans
	WHERE type = $type
	AND supp_reference = '$refx'";
	$res = db_query($sql);
	$row = db_fetch($res);
	display_error($sql);
	}
	
	
	if($row['cv_id']==-1){
		$status='CV TAGGED';
	}
	
	$sql1 = "SELECT SUM(amount) as amount FROM 0_gl_trans
				WHERE type = ".$row['type']."
				AND type_no = ".$row['trans_no']."
				AND amount>0
				";
	$res1 = db_query($sql1);
	$row1 = db_fetch($res1);
	display_error($sql1);
	
	if($row1['amount']==0){
		$status='NO GL';
	}

	if($row['ov_amount']==0){
			$sql_v = "SELECT * FROM 0_voided
			WHERE type = ".$row['type']."
			AND id = ".$row['trans_no'];
			display_error($sql_v);
			$query = db_query($sql_v);
			$rowv = db_fetch($query);
			
			if($rowv['id']!=''){
				$status='VOIDED';
			}
			// else{
				// $status='ZERO AMOUNT';
			// }
			
	}
	
	$supp_id=$row['supplier_id'];
	
	
	return array($status,$supp_id);
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
	$t_nt = $_POST['PARAM_3'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	//$rep = new FrontReport(_('Purchase Report'), "PurchaseReport", 'LONG',8 ,'P');
	$rep = new FrontReport(_('Accounts Payable - Aging Reports'), "AgingReport", user_pagesize(),9 ,'L');
	$dec = user_price_dec();


	$cols = array(0, 20, 130, 205, 275, 345, 415, 485, 555, 630,700);
	$aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right','right','right');
	$headers = array('','Date', 'Reference', 'Amount','1-30 Days', '31-60 Days', '61-90 Days', '91-120 Days', 'Over 120 Days','No. of Days','STATUS');
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
		
		
	$sql="
	select * from (select a.*,b.id as cv_id2, b.bank_trans_id as cv_bt_id, c.id  as bank_trans_id from(SELECT a.type,a.type_no,a.tran_date,a.account,a.person_id,
	a.amount, a.memo_,c.supp_name,st.supplier_id,st.del_date, 
	st.supp_reference, st.special_reference, st.reference,st.cv_id
	FROM 0_gl_trans a JOIN 0_chart_master b ON a.account = b.account_code 
	LEFT  JOIN 0_suppliers c
	ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
	LEFT JOIN 0_supp_trans as st on (a.type_no=st.trans_no and a.type=st.type) 
			WHERE a.tran_date >= '".date2sql($from)."'
			AND a.tran_date <= '".date2sql($to)."'
	AND a.amount!='0' AND a.amount<0 AND a.type IN (20,53) 
	AND st.is_cwo=0 ";

		if ($t_nt == 1) {//trade{
			$sql .= " AND (st.reference NOT LIKE 'NT%')";
			$sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		}
		
		else if ($t_nt == 0) {//non trade
			$sql .= "  AND (st.reference LIKE 'NT%')";
			$sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		}
		else{
			$sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		}

		$sql.=") as a
		LEFT JOIN (
		SELECT * FROM 0_cv_header
		) b ON a.cv_id = b.id
		LEFT JOIN (
		SELECT * FROM 0_bank_trans as bt where trans_date <= '".date2sql($to)."'
		) c ON b.bank_trans_id = c.id
		WHERE ISNULL(c.id) and a.cv_id!=-1 
		) as final
		ORDER BY supp_name, type, tran_date, amount";
		
		
		// $sql = "SELECT a.type,a.type_no,a.tran_date,a.account,a.person_id,a.amount, a.memo_,c.supp_name,st.supplier_id,st.del_date,
		// st.supp_reference, st.cv_id, st.special_reference, st.reference FROM 0_gl_trans a
		// JOIN 0_chart_master b ON a.account = b.account_code
		// LEFT OUTER JOIN  0_suppliers c ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
		// LEFT JOIN 0_supp_trans as st
		// on (a.type_no=st.trans_no and a.type=st.type) 
		// WHERE a.tran_date >= '".date2sql($from)."'
		// AND a.tran_date <= '".date2sql($to)."'
		// AND a.amount!='0'
		// AND a.amount<0
		// AND a.type IN (20,53)
		// AND st.is_cwo!=1
		// ";
		
		// if ($t_nt == 1) {//trade{
			// $sql .= " AND (st.reference NOT LIKE 'NT%')";
			// $sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		// }
		
		// else if ($t_nt == 0) {//non trade
			// $sql .= "  AND (st.reference LIKE 'NT%')";
			// $sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		// }
		// else{
			// $sql .= " ORDER BY c.supp_name, a.type, a.tran_date, a.amount";
		// }
		
		
		//display_error($sql); die();
	
		$res = db_query($sql,'error.');
		
		$company_pref = get_company_prefs();
		
		while ($row = db_fetch($res))
		{

	$apv_counter=count_apv($from,$to,$row['supplier_id'],$t_nt);
	if($apv_counter>0){
		
	
				$counter ++;
			
				$supp_name = $row['supp_name'];
				$last_gst = $row['gst_no'];
				
				$vat = $p_nv = $p_v = 0;
				
				if ($row['type']==20){
					$row['amount']=abs($row['amount']);
				}
				if($row['supp_reference']==''){
					$row['supp_reference']=0;
					
				}
				
				$row_ap=check_ap_sched($row['type'], $row['supp_reference'], $row['amount'], $row['tran_date']);
				
				$status='NOT OK/ with DIFF';
				
				if($row_ap['id']!=''){
							$sqlx = "UPDATE ap_sched SET status = '1'
							WHERE type='".$row_ap['type']."'
							AND id = '".$row_ap['id']."'
						and status=0
							";
							//display_error($sqlx);
							db_query($sqlx,'failed to update status');
							
							
							$status='OK';
				}

				$p_nv = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_non_vat"]);
				$p_v = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_vat"]);
				// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410');
				$vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410010');
				// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410011');
				
				
				// if($row['type']==20 and $last_supp_id == $row['supplier_id'] and ){
					// $apv_total= abs(round($row['amount'],2));
				// }
						// $supp_apv_extended+=$apv_total;
				
				// header
				if ($last_supp_id != $row['supplier_id'])
				{

					if ($supp_apv_extended!=0){
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
				if($row['type']==20){
				$age_days=date_diff2($to,sql2date($row["del_date"]),"d");
				//$age_days=date_diff2(Today(),sql2date($row["del_date"]),"d");
				//$due_days=date_diff2(Today(),sql2date($row["due_date"]),"d");
				}
				else{
				$age_days=date_diff2($to,sql2date($row["tran_date"]),"d");
				}

				
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
				
				
				if(($age_days>=0 and $age_days<=30)){
					if($row['type']==20){
						$details[$counter][3] = abs(round($row['amount'],2));
					}
					else{
						$details[$counter][3] = round($row['amount'],2);
					}
					
					$day_1_30+=$details[$counter][3];
				}
				else{
					$details[$counter][3] = round(0,2);
				}
				
				if(($age_days>=31 and $age_days<=60)){
					if($row['type']==20){
						$details[$counter][4] = abs(round($row['amount'],2));
					}
					else{
						$details[$counter][4] = round($row['amount'],2);
					}
					$day_31_60+=$details[$counter][4];
				}
				else{
					$details[$counter][4] = round(0,2);
				}
				
				if(($age_days>=61 and $age_days<=90)){
					if($row['type']==20){
						$details[$counter][5] = abs(round($row['amount'],2));
					}
					else{
						$details[$counter][5] = round($row['amount'],2);
					}
					$day_61_90+=$details[$counter][5];
				}
				else{
					$details[$counter][5] = round(0,2);
				}
				
				if(($age_days>=91 and $age_days<=120)){
					if($row['type']==20){
						$details[$counter][7] = abs(round($row['amount'],2));
					}
					else{
						$details[$counter][7] = round($row['amount'],2);
					}
				$day_91_120+=$details[$counter][7] ;
				}
				else{
					$details[$counter][7] = round(0,2);
				}
				
				if(($age_days>=121)){
				if($row['type']==20){
						$details[$counter][8] = abs(round($row['amount'],2));
					}
					else{
						$details[$counter][8] = round($row['amount'],2);
					}
				$day_over_120+=$details[$counter][8] ;
				}
				else{
					$details[$counter][8] = round(0,2);
				}
			
				$details[$counter][9] = $age_days;

				
				if($row['type']==20){
					$details[$counter][6] = abs(round($row['amount'],2));
					$apv_total= abs(round($row['amount'],2));
				}
				else{
					$details[$counter][6] = round($row['amount'],2);
				}
				
				
				if($row['type']==20){
					$ov_total += abs(round($row['amount'],2));
					
				}
				else{
					$ov_total += round($row['amount'],2);
				}
				
				$details[$counter][10] = $status;
				
				$supp_apv_extended+=$apv_total;
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
				$rep->TextCol(10, 11, $details[$counter][10]);
				
				$rep->NewLine();
				
				
				//	$supp_total[0] += $details[$counter][6];
	}
			
		}
		
		if ($last_supp_id != '')
		{
			// total per supplier
			$rep->Line($rep->row+10);
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10);
			
			if ($destination)
			{
			$rep->sheet->writeString($rep->y, 2, '===> TOTAL:');
			}
			else{
			$rep->TextCol(0, 1,	'');
			}

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
	
	$rep->y++;
	
			$rep->sheet2 = $rep->addWorksheet('OTHER_DIFF');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet2->setColumn(0,0,15);
	$rep->sheet2->setColumn(0,1,12);
	$rep->sheet2->setColumn(0,2,12);
	$rep->sheet2->setColumn(3,3,10);
	$rep->sheet2->setColumn(4,4,50);
	$rep->sheet2->setColumn(5,6,10);
	$rep->sheet2->setColumn(7,7,12);
	$rep->sheet2->setColumn(8,9,13);
	$rep->sheet2->setColumn(9,9,13);
	//$rep->sheet->setColumn(10,count($c_header),18);
	
	$com = get_company_prefs();
	
	$format_bold_title =& $rep->addFormat();
	$format_bold_title->setTextWrap();
	$format_bold_title->setBold();
	$format_bold_title->setAlign('center');
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
	$format_bold_right =& $rep->addFormat();
	$format_bold_right->setBold();
	$format_bold_right->setAlign('right');

	$format_bold_center =& $rep->addFormat();
	$format_bold_center->setBold();
	$format_bold_center->setAlign('center');
	
	
	$rep->y = 0;
	$rep->sheet2->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet2->writeString($rep->y, 0, 'OTHER DIFF', $format_bold);
	$rep->y ++;

	$rep->sheet2->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet2->setMerge(0,0,0,3);
	// $rep->sheet2->setMerge(1,0,1,3);
	// $rep->sheet2->setMerge(2,0,2,3);
	// $rep->sheet2->setMerge(4,0,4,3);
	// $rep->sheet2->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('SUPPLIER','TYPE','REF','TRANS DATE','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	
	// array_push($c_header,
	
// '1010041'
	// );
		

// '2000011',
// '2000012',
// '2000099',
// '2000100',
// '2000101',
// '2000102',
// '2000103',
// '2000104',
// '2000105'

	
	//print_r($c_header);
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
$sql="SELECT * FROM ap_sched where status='0'";
	
	
//display_error($sql); die;
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	// if (db_num_rows($res) == 0)
		// return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		if($row['type']=='20'){
			$typex='APV';
		}
		else{
			$typex='Supp DM';
		}
		
	
		
		// if ($row['ref']=='' and $row['ref']==0){
			// $refx='no reference';
		// }
		// else{
			$refx=$row['ref'];
	//	}
	
		$statusy=check_ov_amount($row['type'], $refx, $row['amount'], $row['date']);
		
		$supp_namex=get_supplier_name($statusy[1]);
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = $supp_namex;
		$c_details[$count][1] = $typex;
		$c_details[$count][2] = $refx;
		$c_details[$count][3] = sql2date($row['date']);
		$c_details[$count][4] = $row['amount'];
		$c_details[$count][5] = $statusy[0];
		
		
		$total3+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet2->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet2->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		

					$rep->sheet2->writeString($rep->y, $index, $det, $rep->formatLeft);
		
				
		}	

	}

	$rep->y ++;
	$rep->sheet2->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet2->writeNumber ($rep->y, 3, $total3, $format_bold_right);
		
	//}
	
	
	$rep->End();
	
	
}

?>