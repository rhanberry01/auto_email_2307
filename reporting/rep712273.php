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

function check_if_purch($trans_no)
{
	$sql = "SELECT count(type_no) as count FROM 0_gl_trans WHERe type = 20 AND type_no = $trans_no 
	and account IN (5450,5400) AND amount!=0";
	$res = db_query($sql);
	$row = db_fetch($res);
	$count = $row['count'];
	//display_error($sql);
	
	if($count==1){
		$sql2 = "SELECT count(type_no) as count FROM 0_gl_trans WHERe type = 20 AND type_no = $trans_no AND amount!=0 and amount>0";
		$res2 = db_query($sql2);
		$row2 = db_fetch($res2);
		$count2 = $row2['count'];
		
			if($count2<=2){
			$count=1;
			}
			else{
			$count=0;
			}
	
	}

	
	
	
	return $count;
	
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

    $rep = new FrontReport(_('Accounts Payable Book'), "AccountsPayableBook", "LETTER");

    $rep->Font();
	
	$rep->sheet->setColumn(0,0,15);
	$rep->sheet->setColumn(0,2,25);
	$rep->sheet->setColumn(0,2,25);
	$rep->sheet->setColumn(3,3,10);
	$rep->sheet->setColumn(4,4,13);
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
	$rep->sheet->writeString($rep->y, 0, 'ACCOUNTS PAYABLE BOOK', $format_bold);
	$rep->y ++;


	$rep->sheet->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;
//===================================================================================

	// get the header
	$c_header = array('DATE','SUPPLIER');
	array_push($c_header, 'TRANS NO.','PARTICULARS');
	
	$c_header_last_index = count($c_header)-1;
		if ($t_nt==1) {
	array_push($c_header,'2000','2300','1410011',
'1410012',
'6280',
'6290',
'6010',
'6220',
'6230',
'6310',
'1440',
'6250'
);
	}
	else if ($t_nt==0){
		array_push($c_header,'2000010','2300','1410011',
'1410012',
'6280',
'6290',
'6010',
'6220',
'6230',
'6310',
'1440',
'6250'
);
	}
	else {
		array_push($c_header,'2000','2000010','2300','1410011',
'1410012',
'6280',
'6290',
'6010',
'6220',
'6230',
'6310',
'1440',
'6250'
);
	}


$in_header=array('2000','2000010','2300','1410011',
'1410012',
'6280',
'6290',
'6010',
'6220',
'6230',
'6310',
'1440',
'6250');

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	$sql = "SELECT DISTINCT st.cv_id,st.tran_date, st.supplier_id, st.reference, st.supp_reference, 
	gl.type,gl.type_no FROM 0_gl_trans as gl
	LEFT JOIN 0_supp_trans as st
	ON gl.type_no=st.trans_no
	WHERE ";
	if ($t_nt==1) {
	$sql .= " gl.account IN (2000)";
	}
	else if ($t_nt==0){
		$sql .= " gl.account IN (2000010)";
	}
	else {
		$sql .= " gl.account IN (2000,2000010)";
	}	
				
	$sql .=" AND gl.tran_date >= '".date2sql($from)."'
				AND gl.tran_date <= '".date2sql($to)."'
				AND gl.amount < 0
				AND gl.amount!=0
				AND gl.type = 20
				AND st.type =20

				ORDER BY gl.type_no";
	$res = db_query($sql);
	//display_error($sql); die;
	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count_purch=check_if_purch($row["type_no"]);
		if($count_purch==0) 
		{
			$count ++;

			$date_to_ = explode_date_to_dmy($to);

			//$c_details[$count][0] = $del_dates;
			
			$c_details[$count][1] = html_entity_decode(get_supplier_name($row['supplier_id']));
			$last_index = 1;

			$c_details[$count][$last_index+1] = $row['reference'];
			$c_details[$count][$last_index+2] = "AP Voucher # ".$row['reference']." ".get_particulars($row['cv_id']);
			//$c_details[$count][$last_index+2] = get_comments_string($row["type"],$row["type_no"]);

									$sql2 = "SELECT 
									counter,
									type,
									type_no,
									tran_date,
									account,
									memo_,
									sum(amount) as amount,
									person_id
									FROM 0_gl_trans WHERe type = 20
									AND type_no = ".$row['type_no']."
									AND amount!=0
									GROUP BY account
									";									
									
									// $sql2 = "SELECT *
									// FROM 0_gl_trans WHERE type = 20
									// AND type_no IN (21987)
									// ";
									//display_error($sql2);
									$res2 = db_query($sql2);
									while($row2 = db_fetch($res2))
									{
										$gl_[$row2['account']]= array("account"=>$row2['account'],"amount"=>$row2['amount']);
									}
											foreach($gl_ as $ind => $amt)
											{		
														$indx = array_search($ind, $c_header);
														if ($indx == false)
														{
															
															$indw = array_search($ind, $in_header);
															if ($indw == true)
															{
																//$c_header[] = $ind;
																end($c_header);         // move the internal pointer to the end of the array
																$indx = key($c_header); // fetches the key of the element pointed to by the internal pointer
															}
															else{
																$c_details1[$count][$ind]=$amt['amount'];
																$c_totals1[$indw]+=$amt['amount'];
															}				
														}						
														$c_details[$count][$indx]+=$amt['amount'];
														$c_totals[$indx]+=$amt['amount'];	
									
											}

										$gl_='';
										$last_check = $row['trans_no'];
										
				$c_details[$count][0] = sql2date($row['tran_date']);
		}

	}
		//var_dump($c_totals1);die;
			//var_dump($gl_);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		$rep->sheet->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
		$c_header_last_index = count($c_header)-1;
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	$x = $ind++;
	$rep->sheet->writeString($rep->y, $ind, 'SUNDRIES', $format_bold_title);
	$ind++;
	$rep->sheet->writeString($rep->y, $ind, 'DR', $format_bold_title);
	$ind++;
	$rep->sheet->writeString($rep->y, $ind, 'CR', $format_bold_title);
	//var_dump($c_details); die;
	//var_dump($c_details1); die;
	
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
		
		foreach($c_details1 as $i1 => $details1)
		{	
			if($i1 == $i)
			{		
				foreach($details1 as $index1 => $det1)
				{
					$a = $x;
					$a++;
					$rep->sheet->writeString($rep->y, $a, html_entity_decode(get_gl_account_name($index1)), $rep->formatLeft);
					$a++;
					if($det1 >= 0){
						$rep->sheet->writeNumber($rep->y, $a, $det1, $rep->formatLeft);
					}else{
						$a++;
						$rep->sheet->writeNumber($rep->y, $a, abs($det1), $rep->formatLeft);
					}
					$rep->y++;
				
				}
			}
		//	echo $c_header[$index].'<br>';
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
	$rep->sheet2->writeString($rep->y, 0, 'ACCOUNTS PAYABLE BOOK', $format_bold);
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
	

$bank_account_code='10102299';

	$sql = "SELECT DISTINCT st.cv_id,st.tran_date, st.supplier_id, st.reference, st.supp_reference, 
	gl.type,gl.type_no FROM 0_gl_trans as gl
	LEFT JOIN 0_supp_trans as st
	ON gl.type_no=st.trans_no
	WHERE ";
	if ($t_nt==1) {
	$sql .= " gl.account IN (2000)";
	}
	else if ($t_nt==0){
		$sql .= " gl.account IN (2000010)";
	}
	else {
		$sql .= " gl.account IN (2000,2000010)";
	}	
				
	$sql .=" AND gl.tran_date >= '".date2sql($from)."'
				AND gl.tran_date <= '".date2sql($to)."'
				AND gl.amount < 0
				AND gl.amount!=0
				AND gl.type = 20
				AND st.type =20

				ORDER BY gl.type_no";
	$res = db_query($sql);
	//display_error($sql); die;
	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count_purch=check_if_purch($row["type_no"]);
		if($count_purch==0) 
		{
			$type_no[]=$row['type_no'];

		}

	}
		//print_r($type_no); die;
		
		$trans_number = implode(',',$type_no);
		
		//display_error($trans_number); die;
	
	
				$sql2 = "SELECT account,SUM(amount) as amount FROM (SELECT 
				counter,
				type,
				type_no,
				tran_date,
				account,
				memo_,
				sum(amount) as amount,
				person_id
				FROM 0_gl_trans WHERE type = 20
				AND type_no IN ($trans_number)
				AND amount!=0
				GROUP BY account) as a GROUP BY account
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