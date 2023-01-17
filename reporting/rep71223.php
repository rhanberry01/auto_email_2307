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
// Title:	List of Journal Entries
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");

//----------------------------------------------------------------------------------------------------

print_list_of_journal_entries();


function get_journal_transactions($from_date, $to_date, $trans_no=0,
	$account=null, $dimension=0, $dimension2=0, $filter_type=null,
	$amount_min=null, $amount_max=null)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT ".TB_PREF."gl_trans.*, "
		.TB_PREF."chart_master.account_name FROM ".TB_PREF."gl_trans, "
		.TB_PREF."chart_master
		WHERE ".TB_PREF."chart_master.account_code=".TB_PREF."gl_trans.account
		AND tran_date >= '$from'
		AND tran_date <= '$to'";
	if ($trans_no > 0)
		$sql .= " AND ".TB_PREF."gl_trans.type_no LIKE ".db_escape('%'.$trans_no);

	if ($account != null)
		$sql .= " AND ".TB_PREF."gl_trans.account = ".db_escape($account);

	if ($dimension != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension_id = ".($dimension<0?0:db_escape($dimension));

	if ($dimension2 != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension2_id = ".($dimension2<0?0:db_escape($dimension2));

	if (is_numeric($filter_type))
		$sql .= " AND ".TB_PREF."gl_trans.type= ".db_escape($filter_type);
		
	if ($amount_min != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) >= ABS(".db_escape($amount_min).")";
	
	if ($amount_max != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) <= ABS(".db_escape($amount_max).")";

	//$sql .= " ORDER BY tran_date, counter";
	$sql .= " ORDER BY type_no";
	
	//display_error($sql); die;
	return db_query($sql, "The transactions for could not be retrieved");
}

function get_journal_transactions_summary($from_date, $to_date, $trans_no=0,
	$account=null, $dimension=0, $dimension2=0, $filter_type=null,
	$amount_min=null, $amount_max=null)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

$sql = "SELECT account,
		sum(case when amount > 0 then amount else 0 end) as debits,
		sum(case when amount < 0 then amount else 0 end) as credits FROM (
		SELECT account,SUM(amount) as amount FROM ( SELECT ".TB_PREF."gl_trans.*, "
		.TB_PREF."chart_master.account_name FROM ".TB_PREF."gl_trans, "
		.TB_PREF."chart_master
		WHERE ".TB_PREF."chart_master.account_code=".TB_PREF."gl_trans.account
		AND tran_date >= '$from'
		AND tran_date <= '$to'";
	if ($trans_no > 0)
		$sql .= " AND ".TB_PREF."gl_trans.type_no LIKE ".db_escape('%'.$trans_no);

	if ($account != null)
		$sql .= " AND ".TB_PREF."gl_trans.account = ".db_escape($account);

	if ($dimension != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension_id = ".($dimension<0?0:db_escape($dimension));

	if ($dimension2 != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension2_id = ".($dimension2<0?0:db_escape($dimension2));

	if (is_numeric($filter_type))
		$sql .= " AND ".TB_PREF."gl_trans.type= ".db_escape($filter_type);
		
	if ($amount_min != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) >= ABS(".db_escape($amount_min).")";
	
	if ($amount_max != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) <= ABS(".db_escape($amount_max).")";

	//$sql .= " ORDER BY tran_date, counter";
	$sql .= " ) as a GROUP BY account, SIGN(amount)) as b GROUP BY account";
	
	//display_error($sql); die;
	return db_query($sql, "The transactions for could not be retrieved");
}

function chkvoid($ref = null){
	$sql = "select 
			SUM(CASE WHEN amount < 0 THEN -amount ELSE amount END) as amount
			from 0_gl_trans as gll 
			LEFT JOIN 0_refs as rr
			on gll.type = rr.type and gll.type_no = rr.id
			where reference  = '".$ref."' and gll.type = 0 and rr.type = 0";
	$que =  db_query($sql, "The transactions for could not be retrieved");
	$res = db_fetch($que);
	return $res['amount'];

}
//----------------------------------------------------------------------------------------------------

function print_list_of_journal_entries()
{
    global $path_to_root, $systypes_array;
	

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	$systype = ST_JOURNAL;
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

    $cols = array(0, 100, 240, 300, 400, 460, 520, 580);

    $headers = array( _('Date'),_('Account'), _('Particulars'), _('JV NO.'),_('Debit'), _('Credit'));

    $aligns = array('left', 'left', 'left', 'left', 'right', 'right');

    // $params =   array( 	0 => $comments,
    				    // 1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	// 2 => array('text' => _('Jo'), 'from' => 
						// $systype == -1 ? _('All') : $systypes_array[$systype],
                            // 'to' => ''));

							

							
   $rep = new FrontReport('Journal Book', "Journal Book", "LETTER");
   
   	$format_bold_title =& $rep->addFormat();
	$format_bold_title->setTextWrap();
	$format_bold_title->setBold();
	$format_bold_title->setAlign('center');
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
    $rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
    //$rep->Header();
		$com = get_company_prefs();
	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'JOURNAL BOOK', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, date('F j, Y', strtotime($to)), $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	
foreach($headers as $header)
{
$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
$x++;
}
$rep->y++;

    if ($systype == -1)
        $systype = null;
	
    $trans = get_journal_transactions($from, $to, -1, null, 0, 0, $systype);
	$count = db_num_rows($trans);
	
	if($count==0)
	{
		display_error("No reports can be generated with the given parameters.");
		die();
	}
    $d_total = $c_total = $typeno = $type = 0;
    while ($myrow=db_fetch($trans))
    {
		//if ($myrow['amount']){
			if ($type != $myrow['type'] || $typeno != $myrow['type_no'])
			{
				if ($typeno != 0)
				{
					$rep->Line($rep->row  + 4);
					$rep->NewLine();
				}
				
				
				$typeno = $myrow['type_no'];
				$type = $myrow['type'];
				$TransName = $systypes_array[$myrow['type']];
				$rep->DateCol(0, 1, $myrow['tran_date'], true);
				
				//$rep->TextCol(1, 2, get_reference($myrow['type'], $myrow['type_no']));
			
				$coms =  payment_person_name($myrow["person_type_id"],$myrow["person_id"]);
				$memo = get_comments_string($myrow['type'], $myrow['type_no']);
				if ($memo != '')
				{
					if ($coms == "")
						$coms = $memo;
					else
						$coms .= " / ".$memo;
				}		
			
				//$rep->TextCol(2, 3, $coms);
				if ($myrow['amount']){
					$rep->TextCol(3, 4, get_reference($myrow['type'], $myrow['type_no']));
				}else{
					if(chkvoid(get_reference($myrow['type'], $myrow['type_no'])) != 0){
						$rep->TextCol(3, 4, get_reference($myrow['type'], $myrow['type_no']));
					}else{
						$rep->TextCol(3, 4, get_reference($myrow['type'], $myrow['type_no']).'-VOIDED');
					}
				}
				//$rep->NewLine(2);
				$dim_str = get_dimension_string($myrow['dimension_id']);
				$dim_str2 = get_dimension_string($myrow['dimension2_id']);
				if ($dim_str2 != "")
					$dim_str .= "/".$dim_str2;
				
				$rep->TextCol(2, 3, $myrow['memo_']);
				$rep->TextCol(3, 4, $dim_str);
			}
			//$rep->TextCol(0, 1, $myrow['account']);
			$rep->TextCol(1, 2, $myrow['account_name']);
		/* 	$dim_str = get_dimension_string($myrow['dimension_id']);
			$dim_str2 = get_dimension_string($myrow['dimension2_id']);
			if ($dim_str2 != "")
				$dim_str .= "/".$dim_str2;
			
			$rep->TextCol(2, 3, $myrow['memo_']);
			$rep->TextCol(3, 4, $dim_str); */
		
			if ($myrow['amount'] > 0.0)
			{
				$d_total += abs($myrow['amount']);
				$rep->AmountCol(4, 5, abs($myrow['amount']), $dec);
			}
			else
			{
				$c_total += abs($myrow['amount']);
				$rep->AmountCol(5, 6, abs($myrow['amount']), $dec);
			}
			$rep->NewLine(1, 2);
		//}
    }
	
    $rep->Line($rep->row  + 4);
	
	$rep->NewLine();
	$rep->font('b');
	$rep->AmountCol(4, 5, abs($d_total), $dec);
	$rep->AmountCol(5, 6, abs($c_total), $dec);
	$rep->font('');
	$rep->NewLine();
	
	
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
	$rep->sheet2->writeString($rep->y, 0, 'JOURNAL BOOK', $format_bold);
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
	

    $trans = get_journal_transactions_summary($from, $to, -1, null, 0, 0, $systype);
	$count = db_num_rows($trans);
	
	if($count==0)
	{
		display_error("No reports can be generated with the given parameters.");
		die();
	}
    $d_total = $c_total = $typeno = $type = 0;
				
				while($row2 = db_fetch($trans))
				{
					$count ++;

					$date_to_ = explode_date_to_dmy($to);

					$c_details[$count][0] = get_gl_account_name($row2['account']);
					
				//	if($row2['debits']>0){
						$c_details[$count][1] = ROUND($row2['debits'],2);
						//$c_details[$count][2] = 0;
						$total_debit+=ROUND($row2['debits'],2);
					//}
					
					// if($row2['credits']<0){
						// $c_details[$count][1] = 0;
						$c_details[$count][2] = ROUND(abs($row2['credits']),2);
						$total_credit+=ROUND(abs($row2['credits']),2);
					//}
			
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