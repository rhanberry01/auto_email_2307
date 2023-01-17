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

function get_delivery_dates($cv_id)
{
	$sql = "SELECT trans_no FROM ".TB_PREF."cv_details
			  WHERE trans_type = 20
			  AND cv_id = $cv_id";
	$res = db_query($sql);
	
	$in = array();
	while($row = db_fetch($res))
	{
		$in[] = $row[0];
	}
	
	$sql = "SELECT del_date FROM ".TB_PREF."supp_trans
		where type = 20
		and trans_no IN (".implode(',', $in).");";
	$res = db_query($sql);
	
	$del_dates = array();
	$max_month = 0;
	$max_year = 0;
	while($row = db_fetch($res))
	{	
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
			WHERE cv_id = $cv_id ";
	$res = db_query($sql,'fail 1');
	
	$invoices = $where_ = array();
	
	
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
		if (!is_bank_account($row[0]))
		$ret[$row[0]] = round2($row[1],2);
	}
	
	return array($ret,$invoices);
}

function print_report()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $t_nt = $_POST['PARAM_2']; //trade 1  nontrade 0
	
	
	// if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	// else
		// include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();



    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => ($t_nt == 1 ? 'Trade' : 'Non-Trade'), 'to' => '')
						);

    $rep = new FrontReport(_('Check Register'), "CheckRegister", "LETTER");

    $rep->Font();
	
	//==================================================== header
	if ($t_nt)
	{
		$ap_account = get_company_pref('creditors_act'); //2000
		$not = ' NOT ';
		$h = 'For AP Trade';
	}
	else
	{
		$ap_account = get_company_pref('creditors_act_nt'); //2000010
		$not = '';
		$h = 'For AP Non-Trade';
	}
	
	$purch_vat = get_company_pref('purchase_vat');
	$purch_non_vat = get_company_pref('purchase_non_vat');
	
	// get the header
	$c_header = array('DATE DEL','CV DATE', 'SUPPLIER NAME', 'INVOICE', 'CHECK #', 'DATE CHECK', 'DM METROBANK','CURRENT CHECK AMOUNT', 
					'PDC');
	$c_header[] = $ap_account;
	
	if ($t_nt)
	{
		$c_header[] = $purch_vat;
		$c_header[] = $purch_non_vat;
	}
	$c_header_last_index = count($c_header)-1;
					
	$sql = "SELECT b.trans_type, b.trans_no
			 FROM 0_cv_header a, 0_cv_details b
			 WHERE a.cv_date >= '".date2sql($from)."'
			 AND a.cv_date <= '".date2sql($to)."'
			AND a.bank_trans_id != 0
			AND a.id = b.cv_id
			AND cv_no $not LIKE ('%NT%')";
	$res = db_query($sql);
	
	$where_ = array();
	
	if (db_num_rows($res) == 0)
		return false;
	
	while($row = db_fetch($res))
		$where_[] = "(type = " . $row['trans_type'] ." AND type_no = " . $row['trans_no'] . ")";
	
	$where = implode(' OR ',$where_);

	//5400 = purch vat
	//5450 = purch non vat
	// $sql = "SELECT DISTINCT account FROM `0_gl_trans`
				// WHERE amount != 0
				// AND account NOT IN ($ap_account,".$purch_vat.",
						// ".$purch_non_vat.") 
				// AND ($where)
				// ORDER BY account";
	// $res = db_query($sql);
	

	// while($row = db_fetch($res))
	// {
		// $c_header[] = $row['account'];
	// }
		
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	$sql = "SELECT a.cv_date,a.id as cv_id, a.person_type, a.person_id, b.chk_number, chk_date, chk_amount
			FROM 0_cv_header a, 0_cheque_details b
			WHERE a.cv_date >= '".date2sql($from)."'
			AND a.cv_date <= '".date2sql($to)."'
			AND a.bank_trans_id = b.bank_trans_id
			AND cv_no $not LIKE ('%NT%')
			ORDER BY b.chk_number";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;

	$count = 0;
	while($row = db_fetch($res))
	{
		$count ++;
		
		$chk_amount = $pdc = '';
		
		$is_payable = true;
		$date_to_ = explode_date_to_dmy($to);
		
		if ($t_nt)
		{
			$x = get_delivery_dates($row['cv_id']); // dates, max month, max year
			$del_dates = $x[0];
			$is_payable = is_payable($x[1], $x[2], $date_to_[1], $date_to_[2]);
		}
		else
		{
			$del_dates = '';
		}
		
		
		$d_checker = is_pdc(sql2date($row['chk_date']), $date_to_[1], $date_to_[2]);
		
		if ($d_checker)
			$pdc = $row['chk_amount'];
		else
			$chk_amount = $row['chk_amount'];
		
		$gl_details_array = get_cv_gl_details($row['cv_id']);
		$gl_ = $gl_details_array[0];
		$invoices = $gl_details_array[1];
		
		$c_details[$count][0] = $del_dates;
		$c_details[$count][1] = sql2date($row['cv_date']);
		$c_details[$count][2] = html_entity_decode(payment_person_name($row["person_type"],$row["person_id"], false));
		$c_details[$count][3] = implode(', ',$invoices);
		$c_details[$count][4] = $row['chk_number'];
		$c_details[$count][5] = sql2date($row['chk_date']);
		$c_details[$count][6] = ''; //dm metrobank
		$c_details[$count][7] = $chk_amount;
		$c_details[$count][8] = $pdc;
		
		$c_totals[7] += $chk_amount;			
		$c_totals[8] += $pdc;			
	
		foreach($gl_ as $ind => $amt)
		{
			// $amt = abs($amt);
			
			if ($ind != get_company_pref('purchase_vat') AND $ind != get_company_pref('purchase_non_vat'))
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
			else
			{
				if ($is_payable)	// payable account
				{
					$index_ = 9;
					$c_details[$count][$index_] += $amt;
					$c_totals[$index_] += $amt;	
					$c_details[$count][$index_+1] = '';
					$c_details[$count][$index_+2] = '';
				}
				else				// purchase vat and non vat
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
			}
		}
		
		// $c_totals[7] += $payables;			
		// $c_totals[8] += $purchases;		
		// var_dump($c_details);die;
	}
	// var_dump($c_details);die;
	
	$rep->sheet->setColumn(0,1,10);
	$rep->sheet->setColumn(2,2,50);
	$rep->sheet->setColumn(3,3,13);
	$rep->sheet->setColumn(4,5,10);
	$rep->sheet->setColumn(6,6,12);
	$rep->sheet->setColumn(7,7,13);
	$rep->sheet->setColumn(8,8,13);
	$rep->sheet->setColumn(9,count($c_header),18);
	
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

	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'TIN : '.$com['gst_no'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, $com['postal_address'], $format_bold);
	$rep->y ++;
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Check Register', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'From :', $format_bold);
	$rep->sheet->writeString($rep->y, 1, $from, $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'To :', $format_bold);
	$rep->sheet->writeString($rep->y, 1, $to, $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0,$h, $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,3);
	$rep->sheet->setMerge(1,0,1,3);
	$rep->sheet->setMerge(2,0,2,3);
	$rep->sheet->setMerge(4,0,4,3);
	$rep->sheet->setMerge(7,0,7,3);
	
	if($t_nt)
		$c_header_last_index -= 3;
	else
		$c_header_last_index -= 1;
	foreach ($c_header as $ind => $title)
		// $rep->sheet->writeString($rep->y, $ind, ($ind < $subtractor ? $title : 
				// html_entity_decode(get_gl_account_name($title))), $rep->formatLeft);
		$rep->sheet->writeString($rep->y, $ind, 
			($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	
			
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		
		foreach($details as $index => $det)
		{
			if ($index <= 5)
				$rep->sheet->writeString($rep->y, $index, $det, $rep->formatLeft);
			else if($det != 0)
				$rep->sheet->writeNumber($rep->y, $index, $det, $rep->formatRight);
		}
	}
	
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'TOTALS', $rep->formatLeft);
	
	foreach ($c_totals as $ind => $total)
	{
		$rep->sheet->writeNumber ($rep->y, $ind, $total, $format_bold_right);
	}
    $rep->End();
}

?>