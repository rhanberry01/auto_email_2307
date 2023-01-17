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
function get_supp_trans_new($trans_no, $trans_type=-1)
{
	$sql = "SELECT ".TB_PREF."supp_trans.*, ".TB_PREF."supp_trans.ov_amount+".TB_PREF."supp_trans.ov_gst+".TB_PREF."supp_trans.ov_discount+".TB_PREF."supp_trans.ewt AS Total,
		".TB_PREF."suppliers.supp_name AS supplier_name, ".TB_PREF."suppliers.supp_address AS supplier_address, 
		".TB_PREF."suppliers.gst_no, ".TB_PREF."suppliers.curr_code AS SupplierCurrCode, ".TB_PREF."supp_trans.ewt ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".TB_PREF."bank_accounts.bank_name, ".TB_PREF."bank_accounts.bank_account_name, ".TB_PREF."bank_accounts.bank_curr_code,
			".TB_PREF."bank_accounts.account_type AS BankTransType, SUM(".TB_PREF."bank_trans.amount) AS BankAmount,
			".TB_PREF."bank_trans.ref ";
	}

	$sql .= " FROM ".TB_PREF."supp_trans, ".TB_PREF."suppliers ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".TB_PREF."bank_trans, ".TB_PREF."bank_accounts";
	}

	$sql .= " WHERE ".TB_PREF."supp_trans.trans_no=".db_escape($trans_no)."
		AND ".TB_PREF."supp_trans.supplier_id=".TB_PREF."suppliers.supplier_id";

	if ($trans_type > 0)
		$sql .= " AND ".TB_PREF."supp_trans.type=".db_escape($trans_type);

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= " AND ".TB_PREF."bank_trans.trans_no =".db_escape($trans_no)."
			AND ".TB_PREF."bank_trans.type=".db_escape($trans_type)."
			AND ".TB_PREF."bank_accounts.id=".TB_PREF."bank_trans.bank_act ";
	}

	$sql .= " GROUP BY trans_no,type";
	$result = db_query($sql, "Cannot retrieve a supplier transaction");

    if (db_num_rows($result) == 0)
    {
       // can't return nothing
       display_db_error("no supplier trans found for given params d", $sql, true);
       exit;
    }

    if (db_num_rows($result) > 1)
    {
       // can't return multiple
       display_db_error("duplicate supplier transactions found for given params", $sql, true);
       exit;
    }

    return db_fetch($result);
}

function get_cv_and_check($type, $trans_no)
{
	$sql = "SELECT * FROM ".TB_PREF."cv_details 
				WHERE trans_type = $type AND trans_no = $trans_no AND voided = 0";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;
	
	$cv = db_fetch($res);

	$cv_id = $cv['cv_id'];
	$details =  get_check_of_cv($cv_id);
	
	if (!$details)
	{
		$cv_header_row = get_cv_header($cv_id);
		
		if ($cv_header_row['online_payment'] == 2)
			return array($cv_header_row['cv_no'],'paid Online');
		else if ($cv_header_row['bank_trans_id'] == 0)	
			return array($cv_header_row['cv_no'],'');
		else
			return array('','');
	}
	
	return $details;
}

function get_check_of_cv($cv_id)
{
	$sql = "SELECT a.cv_no, c.bank, c.chk_number
				FROM ".TB_PREF."cv_header a, ".TB_PREF."bank_trans b, ".TB_PREF."cheque_details c
				WHERE a.id = $cv_id
				AND a.bank_trans_id = b.id
				AND b.id = c.bank_trans_id
				AND b.amount != 0";
	$res = db_query($sql);

	if (db_num_rows($res) == 0)
		return false;
		
	$cvs = $chks = array();
	while ($row = db_fetch($res))
	{
		$cvs[] = $row['cv_no'];
		$chks[] = $row['bank'].' - '.$row['chk_number'];
	}
	
	return array(implode('; ', $cvs) , implode('; ', $chks));
}

function print_report()
{
    global $path_to_root, $systypes_array;

	set_time_limit(0);
	
    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $t_nt = $_POST['PARAM_2']; //trade 1  nontrade 0
	
	
	 if ($t_nt)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	 else
		 include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();



    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => ($t_nt == 1 ? 'Trade' : 'Non-Trade'), 'to' => '')
						);

    $rep = new FrontReport(_('Purchase Book'), "PurchaseBook", "LETTER");

    $rep->Font();
	
	//==================================================== header
	if ($t_nt)
	{
		$ap_account = get_company_pref('creditors_act');
		$h = 'For AP Trade';
	}
	else
	{
		$ap_account = get_company_pref('creditors_act_nt');
		$h = 'For AP Non-Trade';
	}
	
				
	$sql = "SELECT type,type_no FROM `0_gl_trans` 
				WHERE account IN('5400','5450','1410010')
				AND type!=0
				AND type!=2
				AND tran_date >= '".date2sql($from)."'
				AND tran_date <= '".date2sql($to)."'
				ORDER BY account";
	$res = db_query($sql);
	//echo $sql; die;
	$where_ = array();
	
	if (db_num_rows($res) == 0)
		return false;
	
	while($row = db_fetch($res))
		$where_[] = "(type = " . $row['type'] ." AND type_no = " . $row['type_no'] . ")";
		
	$where = implode(' OR ',$where_);
	
	// $sql = "SELECT DISTINCT b.account_name , a.account FROM `0_gl_trans` a, 0_chart_master b
				// WHERE (a.account = b.account_code)
				// AND amount != 0
				// AND ($where)";
	// $res = db_query($sql);
		// echo $sql; die;
	$c_header = array('DATE', 'NAME OF SUPPLIER', 'ADDRESS', 'INV. NO ', 'TIN', 'VAT PURCHASES (DR)', 'NON-VAT PURCHASES (DR)', 'INPUT TAX (DR)', 'TOTAL INVOICE AMOUNT', 'ADVANCES TO SUPPLIER','ACCOUNTS PAYABLE (CR)','ACCOUNTS PAYABLE - NT (CR)');
	$c_totals = array();
	$subtractor = count($c_header);
	
	$c_header[] = $ap_account;
	
	//while($row = db_fetch($res))
	//{
	//	if ($row['account'] != $ap_account)
			$c_header_ = array('', '', '', '', '', '5450', '5400', '','','1440','2000','2000010');
			$c_header_1 = array('', '', '', '', '', '', '', '1410010');
			$c_header_2 = array('', '', '', '', '', '', '', '1410');
			$c_header_3 = array('', '', '', '', '', '', '', '1410011');
			$c_header_4= array('', '', '', '', '', '', '', '1410012');
			$c_header_5= array('', '', '', '', '', '', '', '1410013');
			$c_header_6 = array('', '', '', '', '', '', '', '1410014');

			$c_header_7 = array('', '', '', '', '', '', '', '', '1440');
			$c_header_8 = array('', '', '', '', '', '', '', '', '2000');
			$c_header_9 = array('', '', '', '', '', '', '', '', '2000010');
	//}

	$rep->sheet->setColumn(0,0,12);
	$rep->sheet->setColumn(1,1,22);
	$rep->sheet->setColumn(2,2,12);
	$rep->sheet->setColumn(3,3,17);
	$rep->sheet->setColumn(4,4,12);
	$rep->sheet->setColumn(5,5,17);
	$rep->sheet->setColumn(6,6,12);
	$rep->sheet->setColumn(7,8,40);
	$rep->sheet->setColumn(9,count($c_header),23);
	
	$com = get_company_prefs();
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
	$format_bold_right =& $rep->addFormat();
	$format_bold_right->setBold();
	$format_bold_right->setAlign('right');

	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'PURCHASE BOOK', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, date('F j, Y', strtotime($to)), $format_bold);
	$rep->y ++;
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,1);
	$rep->sheet->setMerge(1,0,1,1);
	$rep->sheet->setMerge(2,0,2,1);
	$rep->sheet->setMerge(4,0,4,1);
	$rep->sheet->setMerge(7,0,7,1);
	
	foreach ($c_header as $ind => $title)
		$rep->sheet->writeString($rep->y, $ind, ($ind < $subtractor ? $title : ''), $rep->formatLeft);
	
	// $sql = "SELECT a.* FROM `0_gl_trans` a, 0_chart_master b
				// WHERE (a.account = b.account_code)
				// AND amount != 0
				// AND ($where)";
	// echo $sql;
				
	$sql = "SELECT type, type_no, tran_date, account, SUM(amount) as total 
			FROM ".TB_PREF."gl_trans
			WHERE ($where)
			GROUP BY type, type_no, tran_date, account
			HAVING SUM(amount) != 0";
//echo $sql;die;
	$res = db_query($sql,'failed');
	
	$last_type = $last_type_no = 0;
	
	while($row = db_fetch($res))
	{
		// if ($row['account'] != $ap_account)
		// {
		if ($last_type != $row['type'] OR $last_type_no != $row['type_no'])
		{
			$rep->y ++;
			
			$supp_trans_row = get_supp_trans_new($row['type_no'], $row['type']);
			
			// CV and check details
			$apv_cv_check = get_cv_and_check($supp_trans_row["type"],$supp_trans_row["trans_no"]);
			
			$rep->sheet->writeString($rep->y, 0, sql2date($row['tran_date']), $rep->formatLeft); // date
		
			$rep->sheet->writeString($rep->y, 1, html_entity_decode($supp_trans_row['supplier_name']), $rep->formatLeft); // SUPPLIER NAME#
			$rep->sheet->writeString($rep->y, 2,  $supp_trans_row['supplier_address'], $rep->formatLeft); // supp_address
			$rep->sheet->writeString($rep->y, 3, $supp_trans_row['supp_reference'], $rep->formatLeft); // supp_reference
			$rep->sheet->writeString($rep->y, 4, $supp_trans_row['gst_no'], $rep->formatLeft); // TIN
			
			// if($row['account']=='2000' or $row['account']=='2000010'){
				// $rep->sheet->writeNumber($rep->y, 8, $row['total'], $rep->formatLeft); // TIN
			// }
			
		}
		else{
			if($index1 == 9)
				$a = $row['total']; 
		}
		
			$rep->sheet->writeNumber($rep->y, $index =  array_search($row['account'], $c_header_),$row['total'], $rep->formatRight);
			
			//for input tax
			$rep->sheet->writeNumber($rep->y, $index1_1 =  array_search($row['account'], $c_header_1), $row['total'], $rep->formatRight);
			$rep->sheet->writeNumber($rep->y, $index1_2 =  array_search($row['account'], $c_header_2), $row['total'], $rep->formatRight);
			$rep->sheet->writeNumber($rep->y, $index1_3 =  array_search($row['account'], $c_header_3), $row['total'], $rep->formatRight);
			$rep->sheet->writeNumber($rep->y, $index1_4 =  array_search($row['account'], $c_header_4), $row['total'], $rep->formatRight);
			$rep->sheet->writeNumber($rep->y, $index1_5=   array_search($row['account'], $c_header_5), $row['total'], $rep->formatRight);
			$rep->sheet->writeNumber($rep->y, $index1_6 =  array_search($row['account'], $c_header_6), $row['total'], $rep->formatRight);
			
			
			//for total invoice amount
			$rep->sheet->writeNumber($rep->y, $index2_1 =  array_search($row['account'], $c_header_7), abs($row['total']), $rep->formatRight);
			$rep->sheet->writeNumber($rep->y, $index2_2 =  array_search($row['account'], $c_header_8), abs($row['total']), $rep->formatRight);
			$rep->sheet->writeNumber($rep->y, $index2_3 =  array_search($row['account'], $c_header_9), abs($row['total']), $rep->formatRight);

			 
			 if($index1 == 10 && $a != '') {
				$rep->sheet->writeNumber($rep->y, $index1 =  array_search($row['account'], $c_header_), '', $rep->formatRight);
			 }
			 else{
				 $rep->sheet->writeNumber($rep->y, $index1 =  array_search($row['account'], $c_header_), $row['total'], $rep->formatRight);
			 }
				
			$last_type = $row['type'];
			$last_type_no = $row['type_no'];
		
			$c_totals[$index] += $row['total'];
			$c_totals1[$index1] += $row['total'];
			
			$c_totals2[$index2_1] += $row['total'];
			$c_totals2[$index2_2] += $row['total'];
			$c_totals2[$index2_3] += $row['total'];
			
			
			$c_totals3[$index1_1] += $row['total'];
			$c_totals3[$index1_2] += $row['total'];
			$c_totals3[$index1_3] += $row['total'];
			$c_totals3[$index1_4] += $row['total'];
			$c_totals3[$index1_5] += $row['total'];
			$c_totals3[$index1_6] += $row['total'];

		// }
	}
	
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'TOTALS', $rep->formatLeft);

	foreach ($c_totals as $ind => $total)
	{
		if ($ind < 5)
			continue;
			
		$rep->sheet->writeNumber ($rep->y, $ind,$total, $format_bold_right);
	}
	foreach ($c_totals1 as $ind => $total)
	{
		if ($ind < 8)
			continue;
			
		$rep->sheet->writeNumber ($rep->y, $ind, abs($total), $format_bold_right);
	}
	
		foreach ($c_totals2 as $ind => $total)
	{
		if ($ind < 7)
			continue;
			
		$rep->sheet->writeNumber ($rep->y, $ind, abs($total), $format_bold_right);
	}
	
	foreach ($c_totals3 as $ind => $total)
	{
		if ($ind < 6)
			continue;
			
		$rep->sheet->writeNumber ($rep->y, $ind, abs($total), $format_bold_right);
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
	$rep->sheet2->writeString($rep->y, 0, 'PURCHASE BOOK', $format_bold);
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
	

	$sql = "SELECT account, SUM(total) as amount FROM (SELECT type, type_no, tran_date, account, SUM(amount) as total 
			FROM ".TB_PREF."gl_trans
			WHERE ($where)
			GROUP BY type, type_no, tran_date, account
			HAVING SUM(amount) != 0) as a GROUP BY account";
			
			//echo $sql;die;

	$res = db_query($sql,'failed');
				
				while($row2 = db_fetch($res))
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
	
	
	meta_forward($_SERVER['PHP_SELF'], "xls=1&filename=$this->filename&unique=$this->unique_name");
}

?>