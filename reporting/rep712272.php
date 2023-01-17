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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Audit Trail
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");

//----------------------------------------------------------------------------------------------------

print_report();
//----------------------------------------------------------------------------------------------------
function get_particulars($cv_id)
{
	$sql = "SELECT * FROM ".TB_PREF."cv_details
			WHERE cv_id = '$cv_id'
			AND trans_type = '20'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return html_entity_decode(get_comments_string($row['trans_type'], $row['trans_no']));
}

function get_cv_gl_details($cv_id)
{
	$sql = "SELECT trans_type, trans_no
			FROM ".TB_PREF."cv_details
			WHERE cv_id = '$cv_id' ";
	$res = db_query($sql,'fail 1');
	
	$invoices = $where_ = array();
	
	if(db_num_rows($res) == 0)
		return false;
	
	while($row = db_fetch($res))
	{
		$where_[] = "(type = " . $row['trans_type'] ." AND type_no = " . $row['trans_no'] . ")";
		
		// get invoices
		if ($row['trans_type'] == 20)
		{
			$tran_det = get_tran_details($row['trans_type'], $row['trans_no']);
			if ($tran_det['supp_reference'] != ''){
				$apv[] = $tran_det['reference'];
				$invoices[] = $tran_det['supp_reference'];
				
			}
				
		}
	}
			// AND account NOT IN (".get_company_pref('creditors_act').",".get_company_pref('creditors_act_nt').",".
				// get_company_pref('purchase_vat').",".get_company_pref('purchase_non_vat').") 
	$where = implode(' OR ',$where_);
	$sql = "SELECT account , SUM(amount) FROM `0_gl_trans`
			WHERE amount != 0
			AND ($where)
			GROUP BY account
			HAVING SUM(amount) != 0";
	// display_error($sql);die;
	$res = db_query($sql,'fail 2');
	
	
	$ret = array();
	while ($row = db_fetch($res))
	{
		if (!is_bank_account($row[0]) OR (is_bank_account($row[0]) AND $row[1] > 0))
		$ret[$row[0]] = round2($row[1],2);
	}
	
	return array($ret,$invoices,$apv);
}


function print_report()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $t_nt = $_POST['PARAM_2']; //trade 1  nontrade 0 all 2
	$rba = $_POST['PARAM_3']; // 1-retail 0-belen 2-all
	
	include_once($path_to_root . "/reporting/includes/excel_report.inc");

    $dec = user_price_dec();

	//==================================================== header
		$ap_account = get_company_pref('creditors_act'); //2000
		$h = 'For AP Trade/Non-Trade';

    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

      $rep = new FrontReport(_('Cash Disbursement Book'), "CashDisbursementBook", "LETTER");

    $rep->Font();
	
	$rep->sheet->setColumn(0,0,15);
	$rep->sheet->setColumn(0,1,12);
	$rep->sheet->setColumn(0,2,50);
	$rep->sheet->setColumn(3,3,10);
	$rep->sheet->setColumn(4,4,50);
	$rep->sheet->setColumn(5,6,10);
	$rep->sheet->setColumn(7,7,12);
	$rep->sheet->setColumn(8,9,13);
	$rep->sheet->setColumn(9,9,13);
	$rep->sheet->setColumn(10,count($c_header),18);
	
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
	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'CASH DISBURSEMENT BOOK', $format_bold);
	$rep->y ++;

	$rep->sheet->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet->setMerge(0,0,0,3);
	// $rep->sheet->setMerge(1,0,1,3);
	// $rep->sheet->setMerge(2,0,2,3);
	// $rep->sheet->setMerge(4,0,4,3);
	// $rep->sheet->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('CV DATE','CV NO.', 'PAYEE', 'INVOICE','PARTICULARS', 'CHECK #', 'DATE CHECK');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	
	array_push($c_header,
	
'10102299','1020021','1030042','1010040','1030040',
	'2000',
	'2000010'
	);
		

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
	
	// select * from 0_other_income_payment_header  as oh
	// left join 0_other_income_payment_details as od
	// on oh.bd_trans_no = od.bd_det_trans_no 
	// where oh.bd_trans_date>='".date2sql($from)."'
	// and oh.bd_trans_date<='".date2sql($to)."'
	// and oh.bd_receipt_type='OR' ORDER BY oh.bd_or
	
	$sql = "SELECT c.trans_no, c.type, c.supplier_id, c.tran_date, c.reference, c.supp_reference,c.cv_id, a.id as a_id, a.cv_date,
	a.cv_no, b.bank_act,b.trans_date, b.id as b_id, b.amount, b.reconciled, cd.chk_number, 
	cd.chk_date, g.tran_date as g_tran_date, cd.deposit_date, cd.chk_amount,g.account
	FROM 0_supp_trans as c
	LEFT JOIN 0_cv_header as a 
	ON c.cv_id=a.id 
	LEFT JOIN 0_bank_trans as b 
	ON a.bank_trans_id=b.id 
	LEFT JOIN 0_cheque_details as cd 
	ON b.id=cd.bank_trans_id
	LEFT JOIN 0_gl_trans as g
	ON g.type_no=c.trans_no
	WHERE a.amount!=0 
	AND b.type='22'
	AND c.type='22' 
	AND c.ov_amount!=0 
	AND g.type='22'
	and g.amount<0
	AND g.account IN ('10102299','1020021', '1010041', '1020011', '1010031', '1010043', 
	'1030030', '1030040', '1030042', '1010050','1010040', '1010051','1020010','1020036','1010042','1030021','1010011','1010060')
	AND g.tran_date >='".date2sql($from)."' AND g.tran_date <='".date2sql($to)."'
	order by cd.chk_number";
//display_error($sql); die;
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;
	//================To count total header, then add the sundries part
	// while($row2 = db_fetch($res2))
	// {
		// $gl_2[$row2['bd_det_gl_code']] = $row2['bd_oi'];
		// if (in_array($row2['bd_det_gl_code'], $c_header2)){
		
						// foreach($gl_2 as $ind2 => $amt2)
						// {
								// $indx2 = array_search($ind2, $c_header2);
								// if ($indx2 == false)
								// {
									// $c_header2[] = $ind2;
								// }
								
						// }
		// }
	// }
	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = sql2date($row['cv_date']);
		$c_details[$count][1] = $row['cv_no'];
		$c_details[$count][2] =html_entity_decode(get_supplier_name($row['supplier_id']));
		$gl_details_array=get_cv_gl_details($row['cv_id']);
		$invoices = $gl_details_array[1];
		$apv_no = $gl_details_array[2];
		$c_details[$count][3] = implode(', ',$invoices);
		
		$invoices = implode(', ',$invoices);
		$apv_no =implode(', ',$apv_no);
		
		//$c_details[$count][4] = get_particulars($row['cv_id']);
		
		$c_details[$count][4] = "AP Voucher # ".$apv_no." ".get_particulars($row['cv_id']);
		
		if(is_null($row['chk_number'])){
			$c_details[$count][5] = 'ONLINE';
		}else{
			$c_details[$count][5] = $row['chk_number'];
		}
		
		$c_details[$count][6] = sql2date($row['g_tran_date']);
		// $c_details[$count][$last_index+5] = $row['bd_wt'];
		// $c_details[$count][$last_index+6] = $row['bd_vat'];

		
		// $c_totals[$last_index+5] += $chk_amount;			
		// $c_totals[$last_index+6] += $pdc;		
					//print_r($gl_);
					//print_r($c_header);
				//	print_r(in_array($row['bd_det_gl_code'], $c_header));
				
					$sql2 = "SELECT *
					FROM 0_gl_trans WHERe type = 22
					AND type_no = ".$row['trans_no']."";
					//display_error($sql2);
					$res2 = db_query($sql2);
			
					
		while($row2 = db_fetch($res2))
		{
	
						$gl_[$row2['account']] = $row2['amount'];
						
						 if (in_array($row2['account'], $c_header)){
								foreach($gl_ as $ind => $amt)
								{
										$indx = array_search($ind, $c_header);
										if ($indx == false)
										{
											//$c_header[] = $ind;

											end($c_header);         // move the internal pointer to the end of the array
											$indx = key($c_header); // fetches the key of the element pointed to by the internal pointer
										}								
										$c_details[$count][$indx] += $amt;
										$c_totals[$indx] += $amt;	
								}
				
						}
							
										if (!in_array($row2['account'], $c_header)){
											$c_details[$count][$header_total_count+1] = get_gl_account_name($row2['account']);//formerly 9
											$c_details[$count][$header_total_count+2] = '';//formerly 9
											$c_details[$count][$header_total_count+3] = $row2['amount'];//formerly 9es the key of the element pointed to by the internal pointer
											}
							
							
						$gl_[$row2['account']]='';
						$last_check = $row['trans_no'];
		}					

		// $c_details[$count][$indx] = 4;//formerly 9
		// $c_details[$count][$indx] = 5;//formerly 9

		// $c_totals[7] += $payables;			
		// $c_totals[8] += $purchases;		
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	foreach ($c_header as $ind => $title){
		$rep->sheet->writeString($rep->y, $ind, $title , $format_bold_title);
	}
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'TOTAL', $rep->formatLeft);
	$qwert = $rep->y;
	foreach ($c_totals as $ind => $total)
	{
		$rep->sheet->writeNumber ($rep->y, $ind, $total, $format_bold_right);
		
	}

	
	
	
	
	
	
	
	
	
	
		$rep->y++;
	
	
		$rep->sheet2 = $rep->addWorksheet('Summary');
	
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
	$rep->sheet2->writeString($rep->y, 0, 'CASH DISBURSEMENT BOOK', $format_bold);
	$rep->y ++;

	$rep->sheet2->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet2->setMerge(0,0,0,3);
	// $rep->sheet2->setMerge(1,0,1,3);
	// $rep->sheet2->setMerge(2,0,2,3);
	// $rep->sheet2->setMerge(4,0,4,3);
	// $rep->sheet2->setMerge(7,0,7,3);
//===================================================================================
	$rep->y ++;
	$rep->y ++;
	// get the header
	$c_header = array('ACCOUNTS','DEBIT','CREDIT');
	//$c_header = array('BRANCH','MEMO','TRANS DATE','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	

	$sql = "SELECT c.trans_no, c.type, c.supplier_id, c.tran_date, c.reference, c.supp_reference,c.cv_id, a.id as a_id, a.cv_date,
	a.cv_no, b.bank_act,b.trans_date, b.id as b_id, b.amount, b.reconciled, cd.chk_number, 
	cd.chk_date, g.tran_date as g_tran_date, cd.deposit_date, cd.chk_amount,g.account
	FROM 0_supp_trans as c
	LEFT JOIN 0_cv_header as a 
	ON c.cv_id=a.id 
	LEFT JOIN 0_bank_trans as b 
	ON a.bank_trans_id=b.id 
	LEFT JOIN 0_cheque_details as cd 
	ON b.id=cd.bank_trans_id
	LEFT JOIN 0_gl_trans as g
	ON g.type_no=c.trans_no
	WHERE a.amount!=0 
	AND b.type='22'
	AND c.type='22' 
	AND c.ov_amount!=0 
	AND g.type='22'
	and g.amount<0
	AND g.account IN ('10102299','1020021', '1010041', '1020011', '1010031', '1010043', 
	'1030030', '1030040', '1030042', '1010050','1010040', '1010051','1020010','1020036','1010042','1030021','1010011','1010060')
	AND g.tran_date >='".date2sql($from)."' AND g.tran_date <='".date2sql($to)."'
	order by cd.chk_number";
//display_error($sql); die;
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;
	//================To count total header, then add the sundries part
	// while($row2 = db_fetch($res2))
	// {
		// $gl_2[$row2['bd_det_gl_code']] = $row2['bd_oi'];
		// if (in_array($row2['bd_det_gl_code'], $c_header2)){
		
						// foreach($gl_2 as $ind2 => $amt2)
						// {
								// $indx2 = array_search($ind2, $c_header2);
								// if ($indx2 == false)
								// {
									// $c_header2[] = $ind2;
								// }
								
						// }
		// }
	// }
	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		// $count_purch=check_if_purch($row["type_no"]);
		// if($count_purch==0) 
		// {
			$type_no[]=$row['trans_no'];

		//}

	}
		//print_r($type_no); die;
		
		$trans_number = implode(',',$type_no);
		
		//display_error($trans_number); die;
	
	
				$sql2 = "SELECT account,SUM(amount) as amount FROM (SELECT *
									FROM 0_gl_trans WHERe type = 22
									AND type_no IN($trans_number)
									AND amount!=0) as a GROUP BY account
				";	
				//display_error($sql2); die;
				$res2 = db_query($sql2);
				
				while($row2 = db_fetch($res2))
				{
					$count ++;

					$date_to_ = explode_date_to_dmy($to);

					$c_details[$count][0] = get_gl_account_name($row2['account']);
					
					if($row2['amount']>0){
						$c_details[$count][1] = ROUND($row2['amount'],2);
						$c_details[$count][2] = 0;
						$total_debit+=ROUND($row2['amount'],2);
					}
					
					if($row2['amount']<0){
						$c_details[$count][1] = 0;
						$c_details[$count][2] = ROUND(abs($row2['amount']),2);
						$total_credit+=ROUND(abs($row2['amount']),2);
					}
			
				}


	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){

		$rep->sheet2->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	//print_r(c_details);die;
	
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet2->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet2->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				}
				else{
					$rep->sheet2->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
		}	
	}

	$rep->y ++;
	$rep->sheet2->writeString($rep->y, 0, 'GRAND TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet2->writeNumber ($rep->y, 1, $total_debit, $format_bold_right);
		$rep->sheet2->writeNumber ($rep->y, 2, abs($total_credit), $format_bold_right);
		
	//}

	$rep->y++;
	
	
	
    $rep->End();
}

?>