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
		if (!is_bank_account($row[0]))
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
	AND c.bank_id = $bank_id
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


function get_online_payments($from, $to)
{
	$sql = "SELECT c.tran_date, a.*  
			FROM 0_cv_header a, 0_bank_trans b, 0_supp_trans c
			WHERE online_payment=2 
			AND a.bank_trans_id = b.id
			AND c.tran_date >= '".date2sql($from)."'
			AND c.tran_date <= '".date2sql($to)."'
			AND b.type = c.type
			AND b.trans_no = c.trans_no
			ORDER BY trans_date";
	$res = db_query($sql);
	
	return $res;
	
}

function print_report()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $t_nt = $_POST['PARAM_2']; //trade 1  nontrade 0 all 2
	
	
	// if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	// else
		// include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

	//==================================================== header
	if ($t_nt == 2)
	{
		$ap_account = get_company_pref('creditors_act'); //2000
		$h = 'For AP Trade/Non-Trade';
	}
	else if ($t_nt == 1)
	{
		$ap_account = get_company_pref('creditors_act'); //2000
		$h = 'For AP Trade';
	}
	else
	{
		$ap_account = get_company_pref('creditors_act_nt'); //2000010
		$h = 'For AP Non-Trade';
	}

    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Check Register'), "CheckRegister", "LETTER");

    $rep->Font();
	
	
	$purch_vat = get_company_pref('purchase_vat');
	$purch_non_vat = get_company_pref('purchase_non_vat');
	
	// get the header
	$c_header = array('DATE DEL','CV DATE','CV NO.', 'SUPPLIER NAME', 'INVOICE', 'CHECK #', 'DATE CHECK', 'DM METROBANK','CURRENT CHECK AMOUNT', 
					'PDC');
	$c_header[] = $ap_account;
	if ($t_nt == 2)
		$c_header[] = get_company_pref('creditors_act_nt');
	
	if ($t_nt)
	{
		$c_header[] = $purch_vat;
		$c_header[] = $purch_non_vat;
	}
	$c_header_last_index = count($c_header)-1;
					
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	if ($t_nt)
		$sql = "SELECT a.tran_date, a.supplier_id, a.cv_id, c.bank_id, c.chk_number, c.chk_date, c.chk_amount, c.remark
			FROM 0_supp_trans a, 0_bank_trans b, 0_cheque_details c
			WHERE a.type = '22' 
			AND a.tran_date >= '".date2sql($from)."'
			AND a.tran_date <= '".date2sql($to)."'
			AND a.type = b.type
			AND a.trans_no = b.trans_no
			AND b.id = c.bank_trans_id
			ORDER BY bank_id, chk_number ";
	else
		$sql = "SELECT a.cv_date as tran_date, a.id as cv_id, a.person_type, a.person_id as supplier_id, b.bank_id,
				b.chk_number, chk_date, chk_amount, remark
			FROM 0_cv_header a, 0_cheque_details b
			WHERE a.cv_date >= '".date2sql($from)."'
			AND a.cv_date <= '".date2sql($to)."'
			AND a.bank_trans_id = b.bank_trans_id
			AND cv_no LIKE ('%NT%')
			ORDER BY b.bank_id,b.chk_number";
		
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
		
		if ($t_nt == 1) // trade
		{
			if (strpos($cv_header['cv_no'],'NT') !== false)
			{
				continue;
			}
		}
		else if ($t_nt == 0) // non trade
		{
			if (strpos($cv_header['cv_no'],'NT') === false)
			{
				continue;
			}
		}
		
		if ($t_nt == 2)
		{
			if ($last_bank_id == 0)
				$last_bank_id = $row['bank_id'];
				
			
			if (($last_check == 0 OR abs($last_check-$row['chk_number']) > 100) OR
				$last_bank_id != $row['bank_id'] OR !is_numeric($row['chk_number']))
			{			
				$last_bank_id = $row['bank_id'];
				$last_check = $row['chk_number'];
			}
			else
			{
				$first = true;
				
				// if ($last_check+1 != $row['chk_number'])
				// {
					// echo $last_check+1 . '<br>';
					// echo $row['chk_number'] . '<br>';
					// die;
				// }
				
				while($last_check+1 != $row['chk_number'])
				{
					// echo 'skipper<br>';
					// echo ($last_check+1).'<br>';
					// echo $row['chk_number'].'<br>';
					if(!$first)
						$count ++;
					else
						$first = false;
						
					$m_check_row = find_check($row['bank_id'] , $last_check+1);
					//skipped some checks
					if (is_array($m_check_row))
					{
						if($m_check_row['cv_id'] == 0) //voided
						{
							$c_details[$count][0] = '';
							$c_details[$count][1] = 'VOIDED';
							$c_details[$count][2] = '';
							$c_details[$count][3] = html_entity_decode(get_supplier_name($m_check_row['supplier_id']));
							$c_details[$count][4] = '';
							$c_details[$count][5] = $m_check_row['chk_number'];
							$c_details[$count][6] = sql2date($m_check_row['chk_date']);
							$c_details[$count][7] = ''; //dm metrobank
							$c_details[$count][8] = '';//$row['chk_amount'];
							$c_details[$count][9] = '';
							$last_check ++;
							continue;
						}
						$is_payable = true;
						$date_to_ = explode_date_to_dmy($to);
						
						if ($is_trade)
						{
							$x = get_delivery_dates($m_check_row['cv_id']); // dates, max month, max year
							$del_dates = $x[0];
							$is_payable = is_payable($x[1], $x[2], $date_to_[1], $date_to_[2]);
						}
						else
						{
							$del_dates = '';
						}
						
						$d_checker = is_pdc(sql2date($m_check_row['chk_date']), $date_to_[1], $date_to_[2]);
						
						if ($d_checker)
							$pdc = $m_check_row['chk_amount'];
						else
							$chk_amount = $m_check_row['chk_amount'];
						
						$cv_header_ = get_cv_header($m_check_row['cv_id']);
						$gl_details_array = get_cv_gl_details($m_check_row['cv_id']);
						$gl_ = $gl_details_array[0];
						$invoices = $gl_details_array[1];
						
						
						$c_details[$count][0] = $del_dates;
						$c_details[$count][1] = sql2date($cv_header_['cv_date']);
						$c_details[$count][2] = ($cv_header_['cv_no']);
						$c_details[$count][3] = html_entity_decode(get_supplier_name($m_check_row['supplier_id']));
						$c_details[$count][4] = implode(', ',$invoices);
						$c_details[$count][5] = $m_check_row['chk_number'];
						$c_details[$count][6] = sql2date($m_check_row['chk_date']);
						$c_details[$count][7] = ''; //dm metrobank
						$c_details[$count][8] = $chk_amount;
						$c_details[$count][9] = $pdc;
						
						$c_totals[8] += $chk_amount;			
						$c_totals[9] += $pdc;			
					
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
									$index_ = 10;;
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
					}
					else
					{
						$c_details[$count][0] = '';
						$c_details[$count][1] = 'MISSING';
						$c_details[$count][2] = '';
						$c_details[$count][3] = '';
						$c_details[$count][4] = '';
						$c_details[$count][5] = $last_check+1;
						$c_details[$count][6] = '';
						$c_details[$count][7] = ''; //dm metrobank
						$c_details[$count][8] = '';//$row['chk_amount'];
						$c_details[$count][9] = '';
					}
					$last_check ++;
				}
				
				// if ($row['chk_number'] == '3849480')
					// die;
			}
		}
		
		
		
		
		if (strpos($cv_header['cv_no'],'NT') !== false)
			$is_trade = false;
		
		$chk_amount = $pdc = '';
		
		if($row['cv_id'] == 0) //voided
		{
			$c_details[$count][0] = '';
			$c_details[$count][1] = 'VOIDED';
			$c_details[$count][2] = '';
			$c_details[$count][3] = html_entity_decode(get_supplier_name($row['supplier_id']));
			$c_details[$count][4] = '';
			$c_details[$count][5] = $row['chk_number'];
			$c_details[$count][6] = sql2date($row['chk_date']);
			$c_details[$count][7] = ''; //dm metrobank
			$c_details[$count][8] = '';//$row['chk_amount'];
			$c_details[$count][9] = '';
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
		$c_details[$count][1] = sql2date($cv_header['cv_date']);
		$c_details[$count][2] = ($cv_header['cv_no']);
		$c_details[$count][3] = html_entity_decode(get_supplier_name($row['supplier_id']));
		$c_details[$count][4] = implode(', ',$invoices);
		$c_details[$count][5] = $row['chk_number'];
		$c_details[$count][6] = sql2date($row['chk_date']);
		$c_details[$count][7] = ''; //dm metrobank
		$c_details[$count][8] = $chk_amount;
		$c_details[$count][9] = $pdc;
		
		$c_totals[8] += $chk_amount;			
		$c_totals[9] += $pdc;			
	
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
					$index_ = 10;
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
		$last_check = $row['chk_number'];
		
		// $c_totals[7] += $payables;			
		// $c_totals[8] += $purchases;		
		// var_dump($c_details);die;
	}
	
	//===================================================================================================
	//================================= GET ONLINE PAYMENTS =============================================
	//===================================================================================================
	
	if ($t_nt)
	{
		$ol_res = get_online_payments($from, $to);
		
		$count ++;
		$c_details[$count][0] = '';
		$count ++;
		$c_details[$count][0] = 'ONLINE PAYMENTS';
		while($ol_row = db_fetch($ol_res))
		{
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
				$del_dates = '';
			}
			
			
			$c_details[$count][0] = $del_dates;
			$c_details[$count][1] = sql2date($ol_row['cv_date']);
			$c_details[$count][2] = ($ol_row['cv_no']);
			$c_details[$count][3] = html_entity_decode(get_supplier_name($ol_row['person_id']));
			$c_details[$count][4] = implode(', ',$invoices);
			$c_details[$count][5] = '';
			$c_details[$count][6] = sql2date($ol_row['tran_date']);
			$c_details[$count][7] = $ol_row['amount']; //dm metrobank
			$c_details[$count][8] = '';
			$c_details[$count][9] = '';
			
			$c_totals[7] += $ol_row['amount'];		
		
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
						$index_ = 10;
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
		}
	}
	
	// var_dump($c_details);die;
	
	$rep->sheet->setColumn(0,2,10);
	$rep->sheet->setColumn(3,3,50);
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
	
	if($t_nt == 2)
		$c_header_last_index -= 4;
	elseif($t_nt == 1)
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
			if ($index <= 6)
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