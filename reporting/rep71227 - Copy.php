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

    $rep = new FrontReport(_('Cash Receipts Book'), "CashReceiptsBook", "LETTER");

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
	$rep->sheet->writeString($rep->y, 0, 'CASH RECEIPTS BOOK', $format_bold);
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
	$c_header = array('DATE','PAYEE');
	$c_header[] = 'PARTICULARS';
	array_push($c_header, 'OR #', 'CASH IN BANK', 'ACCOUNTS PAYABLE');
	array_push($c_header, 'CREDITABLE WITHHOLDING TAX','OUTPUT TAX');
	
	$c_header_last_index = count($c_header)-1;
	

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	$sql = "select * from 0_other_income_payment_header  as oh
	left join 0_other_income_payment_details as od
	on oh.bd_trans_no = od.bd_det_trans_no 
	where oh.bd_trans_date>='".date2sql($from)."'
	and oh.bd_trans_date<='".date2sql($to)."'
	and oh.bd_receipt_type='OR' ORDER BY oh.bd_or";
	//display_error($sql);
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	
	//================To count total header, then add the sundries part
	while($row2 = db_fetch($res2))
	{
		$gl_2[$row2['bd_det_gl_code']] = $row2['bd_oi'];

						foreach($gl_2 as $ind2 => $amt2)
						{
								$indx2 = array_search($ind2, $c_header);
								if ($indx2 == false)
								{
									$c_header[] = $ind2;
								}
								
						}
	}
	$header_total_count=count($c_header)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		$gl_[$row['bd_det_gl_code']] = $row['bd_oi'];
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = sql2date($row['bd_trans_date']);
		$c_details[$count][1] = $row['bd_payee'];
		$last_index = 1;

		$c_details[$count][$last_index+1] = $row['bd_memo'];
		$c_details[$count][$last_index+2] = $row['bd_or'];
		$c_details[$count][$last_index+3] = $row['bd_amount'];
		$c_details[$count][$last_index+4] = 0; //dm metrobank
		$c_details[$count][$last_index+5] = $row['bd_wt'];
		$c_details[$count][$last_index+6] = $row['bd_vat'];

		
		// $c_totals[$last_index+5] += $chk_amount;			
		// $c_totals[$last_index+6] += $pdc;		
					//print_r($gl_);
						foreach($gl_ as $ind => $amt)
						{
								$indx = array_search($ind, $c_header);
								if ($indx == false)
								{
									$c_header[] = $ind;
									end($c_header);         // move the internal pointer to the end of the array
									$indx = key($c_header); // fetches the key of the element pointed to by the internal pointer
								}
								
								$c_details[$count][$indx] += $amt;
								$c_totals[$indx] += $amt;	
						}
			
						// print_r($c_header);
						// // print_r(end($c_header));
						// print_r($indx-1);
						// print_r(count($c_header));
						// print_r($c_header_last_index);
						
						$r=(count($c_header));
						$g=($c_header_last_index);
						
						
		$c_details[$count][$header_total_count+1] = get_gl_account_name($row['bd_det_gl_code']);//formerly 9
		$c_details[$count][$header_total_count+2] = '';//formerly 9
		$c_details[$count][$header_total_count+3] = $row['bd_oi'];//formerly 9
	
		
		// $c_details[$count][$indx] = 4;//formerly 9
		// $c_details[$count][$indx] = 5;//formerly 9

		$gl_[$row['bd_det_gl_code']]='';
		
		$last_check = $row['bd_or'];
		// $c_totals[7] += $payables;			
		// $c_totals[8] += $purchases;		
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	// var_dump($c_header_last_index."lklk");
	// var_dump($c_header);die;

	foreach ($c_header as $ind => $title){
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
						$rep->sheet->writeString($rep->y, $index, '', $rep->formatRight);
					}
					else{
						$rep->sheet->writeNumber($rep->y, $index, $det, $rep->formatRight);
					}
				
					
				}
				else{
					$rep->sheet->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	//$rep->sheet->writeString($rep->y, 0, 'TOTALS', $rep->formatLeft);
	$qwert = $rep->y;
	foreach ($c_totals as $ind => $total)
	{
		$rep->sheet->writeNumber ($rep->y, $ind, $total, $format_bold_right);
		
	}

	$rep->y++;
    $rep->End();
}

?>