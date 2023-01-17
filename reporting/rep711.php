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
	
	
	// if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	// else
		// include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();



    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => ($t_nt == 1 ? 'Trade' : 'Non-Trade'), 'to' => '')
						);

    $rep = new FrontReport(_('Columnar Report'), "ColumnarReport", "LETTER");

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
	
	// SELECT DISTINCT b.account_name FROM 0_gl_trans a , 0_chart_master b
	// WHERE CONCAT(a.type,'-',a.type_no) IN (SELECT CONCAT(type,'-',type_no) FROM `0_gl_trans` where account = 5450)
	// AND a.account = b.account_code
	// AND a.account != 5450;
	
	$sql = "SELECT type,type_no FROM `0_gl_trans` 
				WHERE account = ".db_escape($ap_account) ."
				AND tran_date >= '".date2sql($from)."'
				AND tran_date <= '".date2sql($to)."'
				AND amount < 0
				ORDER BY account";
	$res = db_query($sql);
	
	$where_ = array();
	
	if (db_num_rows($res) == 0)
		return false;
	
	while($row = db_fetch($res))
		$where_[] = "(type = " . $row['type'] ." AND type_no = " . $row['type_no'] . ")";
		
	$where = implode(' OR ',$where_);
	
	$sql = "SELECT DISTINCT b.account_name , a.account FROM `0_gl_trans` a, 0_chart_master b
				WHERE (a.account = b.account_code)
				AND amount != 0
				AND ($where)";
	$res = db_query($sql);
	
	$c_header = array('Date', 'Trans Type', 'Trans No.', 'Invoice/Reference #', 'CV No.','Check No.', 'TIN', 
		'Supplier', 'Particulars');
	$c_totals = array();
	$subtractor = count($c_header);
	
	$c_header[] = $ap_account;
	
	while($row = db_fetch($res))
	{
		if ($row['account'] != $ap_account)
			$c_header[] = $row['account'];
	}

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
	$rep->sheet->writeString($rep->y, 0, 'TIN : '.$com['gst_no'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, $com['postal_address'], $format_bold);
	$rep->y ++;
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Columnar Report', $format_bold);
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
	
	$rep->sheet->setMerge(0,0,0,1);
	$rep->sheet->setMerge(1,0,1,1);
	$rep->sheet->setMerge(2,0,2,1);
	$rep->sheet->setMerge(4,0,4,1);
	$rep->sheet->setMerge(7,0,7,1);
	
	foreach ($c_header as $ind => $title)
		$rep->sheet->writeString($rep->y, $ind, ($ind < $subtractor ? $title : html_entity_decode(get_gl_account_name($title))), $rep->formatLeft);
	
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
	// echo $sql;die;
	$res = db_query($sql,'failed');
	
	$last_type = $last_type_no = 0;
	
	while($row = db_fetch($res))
	{
		// if ($row['account'] != $ap_account)
		// {
		if ($last_type != $row['type'] OR $last_type_no != $row['type_no'])
		{
			$rep->y ++;
			
			$supp_trans_row = get_supp_trans_2($row['type_no'], $row['type']);
			
			// CV and check details
			$apv_cv_check = get_cv_and_check($supp_trans_row["type"],$supp_trans_row["trans_no"]);
			
			$rep->sheet->writeString($rep->y, 0, sql2date($row['tran_date']), $rep->formatLeft); // date
			
			// $rep->sheet->writeString($rep->y, 1, get_cv_of_trans($row['type'], $row['type_no']), $rep->formatLeft); // CV #
			// $rep->sheet->writeString($rep->y, 2, get_cv_of_trans($row['type'], $row['type_no']), $rep->formatLeft); // for check #
			$rep->sheet->writeString($rep->y, 1, $systypes_array[$supp_trans_row["type"]]. 
					trade_non_trade_inv($supp_trans_row["type"],$supp_trans_row["trans_no"]), $rep->formatLeft); // CV #
			$rep->sheet->writeString($rep->y, 2, $supp_trans_row['reference'], $rep->formatLeft); // supp_reference
			$rep->sheet->writeString($rep->y, 3, $supp_trans_row['supp_reference'], $rep->formatLeft); // supp_reference
			$rep->sheet->writeString($rep->y, 4, $apv_cv_check[0], $rep->formatLeft); // CV NO
			$rep->sheet->writeString($rep->y, 5, $apv_cv_check[1], $rep->formatLeft); // check number
			$rep->sheet->writeString($rep->y, 6, $supp_trans_row['gst_no'], $rep->formatLeft); // TIN
			$rep->sheet->writeString($rep->y, 7, $supp_trans_row['supplier_name'], $rep->formatLeft); // supplier name
			$rep->sheet->writeString($rep->y, 8, get_comments_string($supp_trans_row["type"],$supp_trans_row["trans_no"]), $rep->formatLeft); // supplier name
			// $rep->sheet->writeString($rep->y, 6, $row['memo_'], $rep->formatLeft); // memo
		}	
			$last_type = $row['type'];
			$last_type_no = $row['type_no'];
			
			
			$rep->sheet->writeNumber ($rep->y, $index =  array_search($row['account'], $c_header), $row['total'], $rep->formatRight);
			$c_totals[$index] += $row['total'];
		// }
	}
	
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'TOTALS', $rep->formatLeft);
	foreach ($c_totals as $ind => $total)
	{
		if ($ind < $subtractor)
			continue;
			
		$rep->sheet->writeNumber ($rep->y, $ind, $total, $format_bold_right);
	}
	
    $rep->End();
	meta_forward($_SERVER['PHP_SELF'], "xls=1&filename=$this->filename&unique=$this->unique_name");
}

?>