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

	$sql .= " ORDER BY tran_date, counter";
	return db_query($sql, "The transactions for could not be retrieved");
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

							

							
   $rep = new FrontReport('', "JournalBook", "LETTER");
   
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
	$rep->sheet->writeString($rep->y, 0, $to, $format_bold);
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
		if ($myrow['amount']){
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
				
				$rep->TextCol(1, 2, get_reference($myrow['type'], $myrow['type_no']));
			
				$coms =  payment_person_name($myrow["person_type_id"],$myrow["person_id"]);
				$memo = get_comments_string($myrow['type'], $myrow['type_no']);
				if ($memo != '')
				{
					if ($coms == "")
						$coms = $memo;
					else
						$coms .= " / ".$memo;
				}		
			
				$rep->TextCol(2, 3, $coms);
				$rep->TextCol(3, 4, $TransName . " # " . $myrow['type_no']);
				$rep->NewLine(2);
			}
			$rep->TextCol(0, 1, $myrow['account']);
			$rep->TextCol(1, 2, $myrow['account_name']);
			$dim_str = get_dimension_string($myrow['dimension_id']);
			$dim_str2 = get_dimension_string($myrow['dimension2_id']);
			if ($dim_str2 != "")
				$dim_str .= "/".$dim_str2;
			
			$rep->TextCol(2, 3, $myrow['memo_']);
			$rep->TextCol(3, 4, $dim_str);
		
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
		}
    }
	
    $rep->Line($rep->row  + 4);
	
	$rep->NewLine();
	$rep->font('b');
	$rep->AmountCol(4, 5, abs($d_total), $dec);
	$rep->AmountCol(5, 6, abs($c_total), $dec);
	$rep->font('');
	$rep->NewLine();
	
    $rep->End();
}

?>