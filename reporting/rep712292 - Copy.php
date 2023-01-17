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

// function get_particulars($cv_id)
// {
	// $sql = "SELECT * FROM ".TB_PREF."cv_details
			// WHERE cv_id = '$cv_id'
			// AND trans_type = '53'";
	// $res = db_query($sql);
	// $row = db_fetch($res);
	// return html_entity_decode(get_comments_string($row['trans_type'], $row['trans_no']));
// }


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
    $com = get_company_prefs();
    $rep = new FrontReport(_('General Ledger'), "GeneralLedger ".$com['coy_name'], "LETTER");

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
	$rep->sheet->writeString($rep->y, 0, 'GENERAL LEDGER', $format_bold);
	$rep->y ++;


	$rep->sheet->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;
//===================================================================================

	// get the header
	$c_header = array('Account','Account Name');
	array_push($c_header, 'Debit','Credit');
	
	$c_header_last_index = count($c_header)-1;
		
		

//array_push($c_header,0,2);


//$in_header=array(0,2);
	$in_header= array();
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	// $sql = "SELECT account,SUM(amount) as amount FROM 0_gl_trans
	// WHERE ";
				
	// $sql .="  tran_date >= '".date2sql($from)."'
				// AND tran_date <= '".date2sql($to)."'
				// AND amount != 0
				// GROUP BY account";
	// $res = db_query($sql);
// //	display_error($sql); die;


$res = get_gl_accounts();

	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	$pdeb = $pcre = $cdeb = $ccre = $tdeb = $tcre = $pbal = $cbal = $tbal = 0;
	$begin = begin_fiscalyear();
	if (date1_greater_date2($begin, $from))
		$begin = $from;
	$begin = add_days($begin, -1);
	
	while ($row=db_fetch($res))
	{
		$prev = get_balance($row["account_code"], $dimension, $dimension2, $begin, $from, false, false);
	//	$curr = get_balance($account["account_code"], $dimension, $dimension2, $from, $to, true, true);
	//	$tot = get_balance($account["account_code"], $dimension, $dimension2, $begin, $to, false, true);
		
		
		// $count_purch=check_if_purch($row["type_no"]);
		// if($count_purch==0) 
		// {
			$count ++;

			$date_to_ = explode_date_to_dmy($to);

			//$c_details[$count][0] = $del_dates;
			//$c_details[$count][0] = $row['account_code'];
			$c_details[$count][1] = $row['account_name'];
			$last_index = 1;
			
			
			if ($prev['balance'] >= 0.0)
			{
				//$rep->AmountCol(2, 3, $prev['balance'], $dec);
				$c_details[$count][$last_index+1] =round(abs($prev['balance']),2);
				$c_details[$count][$last_index+2] =0;
				$pdeb += $prev['balance'];
			}
			else
			{

				//$rep->AmountCol(3, 4, abs($prev['balance']), $dec);
				$c_details[$count][$last_index+1] = 0;
				$c_details[$count][$last_index+2] = round(abs($prev['balance']),2);
				$pcre += $prev['balance'];
			}
			
			//$c_details[$count][$last_index+2] = get_comments_string($row["type"],$row["type_no"]);
		//	$c_details[$count][$last_index+2] = "Supplier Debit Memo# ".$row['reference']." ". get_comments_string($row["type"],$row["type_no"]);

									$sql2 = "SELECT type,SUM(amount) as amount
									FROM 0_gl_trans WHERE
									tran_date >= '".date2sql($from)."'
									AND tran_date <= '".date2sql($to)."'
									AND account = ".$row['account_code']."
									AND amount!=0
									GROUP by type
									";				
									//display_error($sql2);die();
									
									// $sql2 = "SELECT *
									// FROM 0_gl_trans WHERE type = 20
									// AND type_no IN (19511)
									// ";
									//display_error($sql2);
									$res2 = db_query($sql2);
									while($row2 = db_fetch($res2))
									{
										$gl_[$row2['type']]= array("type"=>$row2['type'],"amount"=>$row2['amount']);

									}
									
									print_r($gl_);
									
											foreach($gl_ as $ind => $amt)
											{
												
											
										//	print_r($ind);
													
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
																$c_header[] = $ind;
																//array_push($c_header,$ind);
																$c_details1[$count][$ind]=$amt['amount'];
																$c_totals1[$indw]+=$amt['amount'];
																array_push($in_header,$ind);
															}				
														}						
														$c_details[$count][$indx]+=$amt['amount'];
														$c_totals[$indx]+=$amt['amount'];	

											}

										$gl_='';
										//$last_check = $row2['type'];
										$c_details[$count][0] = $row['account_code'];
										
										
		//}

	}
		//var_dump($c_totals1);die;
			//var_dump($gl_);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){

		$rep->sheet->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : $systypes_array[$title]), $format_bold_title);
	}
		$c_header_last_index = count($c_header)-1;
		// array_push($c_header, 'Debit', 'Credit');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	$x = $ind++;
	$rep->sheet->writeString($rep->y, $ind, 'Debit', $format_bold_title);
	$ind++;
	$rep->sheet->writeString($rep->y, $ind, 'Credit', $format_bold_title);
	//var_dump($c_details);
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
					$rep->sheet->writeString($rep->y, $a, $systypes_array[$title], $rep->formatLeft);
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

    $rep->End();
}

?>