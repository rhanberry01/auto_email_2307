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

function get_delivery_dates($cv_id)
{
	$sql = "SELECT trans_no FROM ".TB_PREF."cv_details
			  WHERE trans_type = '20'
			  AND cv_id = '$cv_id'";
	$res = db_query($sql);
	
	$in = array();
	while($row = db_fetch($res))
	{
		$in[] = $row[0];
	}
	
	$sql = "SELECT del_date FROM ".TB_PREF."supp_trans
		where type = '20'
		and trans_no IN (".implode(',', $in).");";
	$res = db_query($sql);
	
	$del_dates = array();
	$max_month = 0;
	$max_year = 0;
	while($row = db_fetch($res))
	{	
		if (!is_date(sql2date($row[0])))
			continue;
			
		$date_ = explode_date_to_dmy(sql2date($row[0]));
		
		if ($max_year == 0 OR $date_[2] > $max_year)
		{
			$max_month = $date_[1];
			$max_year = $date_[2];
		}
		
		if ($max_month == 0 OR $date_[1] > $max_month)
				$max_month = $date_[1];
		
		$del_dates[] = sql2date($row[0]);
	}
	return array(implode(', ', array_unique($del_dates)), $max_month, $max_year);
}

function is_pdc($date, $max_month, $max_year)
{
	$date_ = explode_date_to_dmy($date);
	
	if ($date_[2] > $max_year)
		return true;
	else if ($date_[2] < $max_year)
		return false;
	else if ($date_[1] > $max_month)
		return true;
	else if ($date_[1] <= $max_month)
		return false;
		
	return false;
}

function is_payable($del_date_month, $del_date_year, $max_month, $max_year)
{	
	if ($del_date_year > $max_year)
		return false;
	else if ($del_date_year < $max_year)
		return true;
	else if ($del_date_month >= $max_month)
		return false;
	else if ($del_date_month < $max_month)
		return true;
		
	return false;
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
			if ($tran_det['supp_reference'] != '')
				$invoices[] = $tran_det['supp_reference'];
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
	
	return array($ret,$invoices);
}

function find_check($bank_id , $check_number)
{
	$sql = "SELECT a.tran_date, a.supplier_id, a.cv_id, c.bank_id, c.chk_number,c.chk_date, c.chk_amount, c.remark 
	FROM 0_supp_trans a, 0_bank_trans b, 0_cheque_details c 
	WHERE a.type = '22' 
	AND a.type = b.type 
	AND a.trans_no = b.trans_no 
	AND b.id = c.bank_trans_id 
	AND c.bank_id = '$bank_id'
	AND chk_number = '$check_number'
	ORDER BY bank_id, chk_number";
	
	// if ($check_number == '3849410')
	// {
		// echo $sql;die;
	// }
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;
	return db_fetch($res);
}


function get_online_payments($from, $to, $t_nt)
{
	$sql = "SELECT c.tran_date, a.* , b.trans_date
			FROM 0_cv_header a, 0_bank_trans b, 0_supp_trans c
			WHERE online_payment='2' 
			AND a.bank_trans_id = b.id
			AND b.trans_date >= '".date2sql($from)."'
			AND b.trans_date <= '".date2sql($to)."'
			AND b.type = c.type
			AND b.trans_no = c.trans_no";
	
	if ($t_nt == 1) //trade
	{
		$ap_account = get_company_pref('creditors_act'); //2000
		$sql .= " AND (SELECT COUNT(*) FROM 0_gl_trans d WHERE d.type=c.type AND d.type_no = c.trans_no AND d.account = '$ap_account') > 0";
	}
	else if ($t_nt == 0) // non trade
	{
		$ap_account = get_company_pref('creditors_act_nt'); //2000010
		$sql .= " AND (SELECT COUNT(*) FROM 0_gl_trans d WHERE d.type=c.type AND d.type_no = c.trans_no AND d.account = '$ap_account') > 0";
	}
	
	$sql .= " ORDER BY trans_date";
	// echo $sql;die;
	$res = db_query($sql);
	
	return $res;
	
}

function print_report()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $t_nt = $_POST['PARAM_2']; //trade 1  nontrade 0 all 2
	$rba = $_POST['PARAM_3']; // 1-retail 0-belen 2-all
	
	// if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	// else
		// include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

	//==================================================== header

	$h = 'For AP Trade/Non-Trade';

    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Cash Disbursement Book'), "CashDisbursementBook", "LETTER");

    $rep->Font();
	

	// get the header
	$c_header = array('CV DATE','CV NO.', 'PAYEE', 'INVOICE','PARTICULARS');
	array_push($c_header, 'CHECK #', 'DATE CHECK', 'DM METROBANK','CURRENT CHECK AMOUNT', 'PDC');

	
	$c_header_last_index = count($c_header)-1;
	
	
	//print_r($c_header_last_index); die;
					
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	$sql = "SELECT c.trans_no, c.type, c.supplier_id, c.tran_date, c.cv_id, a.id as a_id,
	a.cv_no, b.bank_act,b.trans_date, b.id as b_id, b.amount, b.reconciled, cd.chk_number, 
	cd.chk_date, cd.deposit_date, cd.chk_amount,g.account
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
	AND
	g.account IN (
	'1020011',
	'1010031',
	'1010043',
	'1030030',
	'1030040',
	'1030042',
	'1010050',
	'10102299',
	'1010040',
	'1010051'
	) 
	AND cd.chk_date >='".date2sql($from)."' AND cd.chk_date <='".date2sql($to)."'
	AND !ISNULL(cd.chk_number) 
	order by cd.chk_number";
	display_error($sql); die;
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;
	
	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	while($row = db_fetch($res))
	{

			$count ++;
			$is_trade = true;
			
			$cv_header = get_cv_header($row['cv_id']);

			if (strpos($cv_header['cv_no'],'NT') !== false)
				$is_trade = false;
			
			$chk_amount = $pdc = '';
			
			if($row['cv_id'] == 0) //voided
			{
				//$c_details[$count][0] = '';
				$c_details[$count][0] = 'VOIDED';
				$c_details[$count][1] = '';
				$c_details[$count][2] = html_entity_decode(get_supplier_name($row['supplier_id']));
				$c_details[$count][3] = '';
				$last_index = 3;
				if ($t_nt != 1)
				{
					$c_details[$count][$last_index+1] = '';				
					$last_index ++;
				}	
				$c_details[$count][$last_index+1] = $row['chk_number'];
				$c_details[$count][$last_index+2] = sql2date($row['chk_date']);
				$c_details[$count][$last_index+3] = ''; //dm metrobank
				$c_details[$count][$last_index+4] = '';//$row['chk_amount'];
				$c_details[$count][$last_index+5] = '';
				$last_check = $row['chk_number'];
				continue;
			}
			
			$is_payable = true;
			$date_to_ = explode_date_to_dmy($to);
			
			if ($is_trade)
			{
				$x = get_delivery_dates($row['cv_id']); // dates, max month, max year
				$del_dates = $x[0];
				$is_payable = is_payable($x[1], $x[2], $date_to_[1], $date_to_[2]);
			}
			else
			{
				// $del_dates = '';
				$del_dates = get_supplier_tin($row['supplier_id']);
			}
			
			$d_checker = is_pdc(sql2date($row['chk_date']), $date_to_[1], $date_to_[2]);
			
			if ($d_checker)
				$pdc = $row['chk_amount'];
			else
				$chk_amount = $row['chk_amount'];
			
			$gl_details_array = get_cv_gl_details($row['cv_id']);
			$gl_ = $gl_details_array[0];
			$invoices = $gl_details_array[1];
			
			//$c_details[$count][0] = $del_dates;
			$c_details[$count][0] = sql2date($cv_header['cv_date']);
			$c_details[$count][1] = ($cv_header['cv_no']);
			$c_details[$count][2] = html_entity_decode(get_supplier_name($row['supplier_id']));
			$c_details[$count][3] = implode(', ',$invoices);
			$last_index = 3;
			if ($t_nt != 1)
			{
				if (strpos($cv_header['cv_no'],'NT') !== false) // nt get comment
					$c_details[$count][$last_index+1] = get_particulars($cv_header['id']);
				else
					$c_details[$count][$last_index+1] = '';
				
				$last_index ++;
			}
			$c_details[$count][$last_index+1] = $row['chk_number'];
			$c_details[$count][$last_index+2] = sql2date($row['chk_date']);
			$c_details[$count][$last_index+3] = ''; //dm metrobank
			$c_details[$count][$last_index+4] = $chk_amount; //formerly 8
			$c_details[$count][$last_index+5] = $pdc;//formerly 9
			
			$c_totals[$last_index+4] += $chk_amount;			
			$c_totals[$last_index+5] += $pdc;			
		
			$not_header = array('','6210012','2305','2501601','1494','6090010','6191','6160010','6251','2000016',
			'6090011','622010','6290010','6201','9050','9051','6040010','2400','2380','1430018','1430019','1430020','1430021','1430022','1430023',
			'1430024','1430025','1430026','1430027','1430028','1430029','1430030','1430031','1430032','1430033',
			'2470',
			'2471',
			'2472',
			'2473',
			'2474',
			'2475',
			'2476',
			'2477',
			'2478',
			'2479',
			'2480',
			'2481',
			'2482',
			'2483',
			'6010',
			'6010010',
			'6010011',
			'6011',
			'6020',
			'6030',
			'6040',
			'6040010',
			'6050',
			'605030',
			'605031',
			'605040010',
			'605040011',
			'605040012',
			'605040013',
			'605040020',
			'605040030',
			'605050010',
			'605050011',
			'605050020',
			'605050021',
			'605050022',
			'605050023',
			'605050024',
			'605050025',
			'605060010',
			'605070010',
			'605080010',
			'605090010',
			'6060',
			'606010010',
			'606010011',
			'6070',
			'6080',
			'6090',
			'6090010',
			'6090011',
			'6090012',
			'6100',
			'6110',
			'6111',
			'6112',
			'6113',
			'6120',
			'6121',
			'6130',
			'6131',
			'6140',
			'6150',
			'6151',
			'6160',
			'6160010',
			'6170',
			'6171',
			'6180',
			'6181',
			'6182',
			'6190',
			'6191',
			'6200',
			'6201',
			'6210',
			'6210010',
			'6210011',
			'6210013',
			'6210014',
			'6220',
			'622010',
			'6230',
			'6233',
			'6240',
			'6250',
			'6251',
			'6260',
			'6280',
			'6281',
			'6290',
			'6290010',
			'6290011',
			'6300',
			'6300010',
			'6300011',
			'6300012',
			'6310',
			'6310010',
			'6330',
			'8000',
			'80001',
			'80003',
			'9000',
			'9050',
			'9051',
			'9052',
			'9053',
			'9054',
			'9055',
			'9056'
			);

			foreach($gl_ as $ind => $amt)
			{
				// if ($ind != get_company_pref('purchase_vat') AND $ind != get_company_pref('purchase_non_vat'))
				// {	
			if($ind!=5450 AND $ind!=5400){
						$indx = array_search($ind, $c_header);

						if ($indx == false)
						{
							
							$indw = array_search($ind, $not_header);
							if ($indw == false)
							{
								
									
								
								$c_header[] = $ind;
								end($c_header);         // move the internal pointer to the end of the array
								$indx = key($c_header); // fetches the key of the element pointed to by the internal pointer
								
							}else{
								
								$c_details1[$count][$indw] += $amt;
								$c_totals1[$indw] += $amt;
							}				
						}
						
						$c_details[$count][$indx] += $amt;
						$c_totals[$indx] += $amt;	
			}
			}
			
			$last_check = $row['chk_number'];
		// $c_totals[7] += $payables;			
		// $c_totals[8] += $purchases;		
	}
	//var_dump($c_details);
	//var_dump($c_details1);die;
	
	//===================================================================================================
	//================================= GET ONLINE PAYMENTS =============================================
	//===================================================================================================
	
	if (true)
	{
		$ol_res = get_online_payments($from, $to,$t_nt);
		
		$count ++;
		$c_details[$count][0] = '';
		$count ++;
		$c_details[$count][0] = 'ONLINE PAYMENTS';
		while($ol_row = db_fetch($ol_res))
		{
		
			if ($rba == 1) //retail only
			{
				if (strpos($ol_row['cv_no'],'NTR') === false AND strpos($ol_row['cv_no'],'R') === false)
				{
					continue;
				}
			}
			else if ($rba == 0) //belen only
			{
				if (strpos($ol_row['cv_no'],'NTR') !== false OR strpos($ol_row['cv_no'],'R') !== false)
				{
					continue;
				}
			}
			
			$count ++;
			
			$gl_details_array = get_cv_gl_details($ol_row['id']);
			$gl_ = $gl_details_array[0];
			$invoices = $gl_details_array[1];
			
			$is_trade = true;
				
			$is_payable = true;
			$date_to_ = explode_date_to_dmy($to);
			
			if ($is_trade)
			{
				$x = get_delivery_dates($ol_row['id']); // dates, max month, max year
				$del_dates = $x[0];
				$is_payable = is_payable($x[1], $x[2], $date_to_[1], $date_to_[2]);
			}
			else
			{
				// $del_dates = '';
				$del_dates = get_supplier_tin($ol_row['person_id']);
			}
			
			
			//$c_details[$count][0] = $del_dates;
			$c_details[$count][0] = sql2date($ol_row['cv_date']);
			$c_details[$count][1] = ($ol_row['cv_no']);
			$c_details[$count][2] = html_entity_decode(get_supplier_name($ol_row['person_id']));
			$c_details[$count][3] = implode(', ',$invoices);
			$last_index = 3;
			if ($t_nt != 1)
			{
				if (strpos($ol_row['cv_no'],'NT') !== false) // nt get comment
					$c_details[$count][$last_index+1] = get_particulars($ol_row['id']);
				else
					$c_details[$count][$last_index+1] = '';
				
				$last_index ++;
			}
			
			$c_details[$count][$last_index+1] = '';
			$c_details[$count][$last_index+2] = sql2date($ol_row['trans_date']);
			$c_details[$count][$last_index+3] = $ol_row['amount']; //dm metrobank
			$c_details[$count][$last_index+4] = '';
			$c_details[$count][$last_index+5] = '';
			
			$c_totals[$last_index+3] += $ol_row['amount'];		
		

			foreach($gl_ as $ind => $amt)
			{
				// if ($ind != get_company_pref('purchase_vat') AND $ind != get_company_pref('purchase_non_vat'))
				// {	
			if($ind!=5450 AND $ind!=5400){
						$indx = array_search($ind, $c_header);
						
						if ($indx == false)
						{
							
							$indw = array_search($ind, $not_header);
							if ($indw == false)
							{
								
								$c_header[] = $ind;
								end($c_header);         // move the internal pointer to the end of the array
								$indx = key($c_header); // fetches the key of the element pointed to by the internal pointer
							
							}else{
								
								$c_details1[$count][$indw] += $amt;
								$c_totals1[$indw] += $amt;
							}				
						}
						
						$c_details[$count][$indx] += $amt;
						$c_totals[$indx] += $amt;	
			}
			}
			
			
			
		}
	}
	//=====================end of online payment================================
	$rep->sheet->setColumn(0,2,10);
	$rep->sheet->setColumn(3,3,50);
	$rep->sheet->setColumn(4,4,13);
	
	if ($t_nt != 1)
	{
		$rep->sheet->setColumn(5,5,50);
		$rep->sheet->setColumn(6,6,10);
	}
	else
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

	$rep->sheet->setMerge(0,0,0,3);
	$rep->sheet->setMerge(1,0,1,3);
	$rep->sheet->setMerge(2,0,2,3);
	$rep->sheet->setMerge(4,0,4,3);
	$rep->sheet->setMerge(7,0,7,3);
	
	
	//	$c_header_last_index+=4;
	// elseif($t_nt == 1)
		// $c_header_last_index -= 3;
	// else
		// $c_header_last_index -= 4;
	
	//====================================================HEADER-============================
	// var_dump($c_header);die;
	foreach ($c_header as $ind => $title){
		// $rep->sheet->writeString($rep->y, $ind, ($ind < $subtractor ? $title : 
				// html_entity_decode(get_gl_account_name($title))), $rep->formatLeft);
		$rep->sheet->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	$x = $ind++;
	$rep->sheet->writeString($rep->y, $ind, 'SUNDRIES', $format_bold_title);
	$ind++;
	$rep->sheet->writeString($rep->y, $ind, 'DR', $format_bold_title);
	$ind++;
	$rep->sheet->writeString($rep->y, $ind, 'CR', $format_bold_title);
	
//	 var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		
		$rep->y ++;
		foreach($details as $index => $det)
		{		
			if ($index <= ($t_nt != 1 ? 7 : 6))
				$rep->sheet->writeString($rep->y, $index, $det, $rep->formatLeft);
			else if($det != 0)
				$rep->sheet->writeNumber($rep->y, $index, $det, $rep->formatRight);

		}	
		
		foreach($c_details1 as $i1 => $details1)
		{	
			if($i1 == $i)
			{		
				foreach($details1 as $index1 => $det1)
				{
					$a = $x;
					$a++;
					$rep->sheet->writeString($rep->y, $a, get_gl_account_name($not_header[$index1]), $rep->formatRight);
					$a++;
					if($det1 >= 0){
						$rep->sheet->writeNumber($rep->y, $a, $det1, $rep->formatRight);
					}else{
						$a++;
						$rep->sheet->writeNumber($rep->y, $a, abs($det1), $rep->formatRight);
					}
					$rep->y++;
				
				}
			}
		//	echo $c_header[$index].'<br>';
		}	
	}
//die;

	
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'TOTALS', $rep->formatLeft);
	$qwert = $rep->y;
	foreach ($c_totals as $ind => $total)
	{
		$rep->sheet->writeNumber ($rep->y, $ind, $total, $format_bold_right);
	}
	
	//	$rep->y ++;

	$rep->y++;
	//=========================================================
	$rep->sheet2 = $rep->addWorksheet('GL Accounts');
	
	$rep->sheet2->setColumn(0,0,38);
	$rep->sheet2->setColumn(1,2,12);
	
	$rep->y = 0;
	$rep->sheet2->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet2->writeString($rep->y, 0, 'CASH DISBURSEMENT BOOK', $format_bold);
	$rep->y ++;
	$rep->sheet2->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,3);
	$rep->sheet->setMerge(1,0,1,3);
	$rep->sheet->setMerge(2,0,2,3);
	$rep->sheet->setMerge(4,0,4,3);
	$rep->sheet->setMerge(7,0,7,3);
	
	$rep->sheet2->writeString($rep->y, 0, 'Account Name', $format_bold);
	$rep->sheet2->writeString($rep->y, 1, 'Debit', $format_bold);
	$rep->sheet2->writeString($rep->y, 2, 'Credit', $format_bold);
	
	$debit_total = $credit_total = 0;
	

	
	
	
	
	
	
	
	
	
	
		$minus = 7;
	// // ======================= SPECIAL CASE FOR SHEET 2 ==========================
	// Accounts Payable, Purchases VAT Non-VAT, Input Tax Goods for Resale
$sub_group = array ('2000', '2000010','1410010');
	$sub_group_total = array(0,0);
	
	$rep->y ++;
	foreach ($c_header as $ind => $title)
	{
		if (!in_array(trim($title),$sub_group))
			continue;
		if ($ind < $minus )
			continue;
			
		$rep->sheet2->writeString($rep->y, 0, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $rep->formatLeft);
		
		if ($c_totals[$ind] >= 0 AND $ind > $c_header_last_index)
		{
			$rep->sheet2->writeNumber ($rep->y, 1, abs($c_totals[$ind]), $rep->formatRight);
			$debit_total += $c_totals[$ind];
			$sub_group_total[0] += $c_totals[$ind];
		}
		else
		{
			$rep->sheet2->writeNumber ($rep->y, 2, abs($c_totals[$ind]), $rep->formatRight);
			$credit_total += abs($c_totals[$ind]);
			$sub_group_total[1] += abs($c_totals[$ind]);
		}
		
		// display_error($c_totals[$ind]);
		display_error($ind);
		//display_error($c_header_last_index);
	
	
		$rep->y ++;
	}
	
	$indx=$ind;
	$rep->y=4;
 	foreach ($c_totals1 as $ind1 => $amount)
	{
		$indx=$ind;
		$rep->y ++;
		//echo $amount.'<br>';
		if($amount >= 0)
			$dr = $dr + abs($amount);
		else
			$cr = $cr + abs($amount);
	
	}
	//die;
		$ind++;
		$rep->sheet->writeString($qwert, $ind, '', $format_bold_right);
		$ind++;
		$rep->sheet->writeString($qwert, $ind, $dr, $format_bold_right);
		$ind++;
		$rep->sheet->writeString($qwert, $ind, $cr, $format_bold_right);
	$rep->sheet2->writeString($rep->y, 0, 'TOTAL:', $format_bold_center);
	$rep->sheet2->writeNumber ($rep->y, 1, abs($sub_group_total[0]), $format_bold_right);
	$rep->sheet2->writeNumber ($rep->y, 2, abs($sub_group_total[1]), $format_bold_right);
	$rep->y ++;
	
	// // =================================================
	foreach ($c_header as $ind => $title)
	{
		if (in_array($title,$sub_group))
			continue;
		if ($ind < $minus )
			continue;
			
		$rep->sheet2->writeString($rep->y, 0, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $rep->formatLeft);
		
		if ($c_totals[$ind] >= 0 AND $ind > $c_header_last_index)
		{
			$rep->sheet2->writeNumber ($rep->y, 1, abs($c_totals[$ind]), $rep->formatRight);
			$debit_total += $c_totals[$ind];
		}
		else
		{
			$rep->sheet2->writeNumber ($rep->y, 2, abs($c_totals[$ind]), $rep->formatRight);
			$credit_total += abs($c_totals[$ind]);
		}
		$rep->y ++;
	}
	
	foreach ($c_totals1 as $ind1 => $amount)
	{
	$rep->sheet2->writeString($rep->y, 0, html_entity_decode(get_gl_account_name($not_header[$ind1])), $rep->formatLeft);
		if ($c_totals1[$ind1] >= 0)
		{
			$rep->sheet2->writeNumber ($rep->y, 1, abs($c_totals1[$ind1]), $rep->formatRight);
			$debit_total += $c_totals1[$ind1];
		}
		else
		{
			$rep->sheet2->writeNumber ($rep->y, 2, abs($c_totals1[$ind1]), $rep->formatRight);
			$credit_total += abs($c_totals1[$ind1]);
		}
		$rep->y ++;
	}
	// $rep->sheet2->writeString ($rep->y, 0, '');
	$rep->sheet2->writeNumber ($rep->y, 1, $debit_total, $format_bold_right);
	$rep->sheet2->writeNumber ($rep->y, 2, $credit_total, $format_bold_right);
	$rep->y++;
	
    $rep->End();
}

?>