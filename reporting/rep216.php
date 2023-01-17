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
$page_security = 'SA_SALESANALYTIC';
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_pdcc_for_payments();

function print_pdcc_for_payments()
{
    global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	
    $dec = user_price_dec();

	$cols = array(0, 100, 400, 470, 540, 590, 680);

	// $headers = array(_('Customer'), _('Bank'), _('OR #'), _('Check #'), _('Check Date'), _('Amount'));
	$headers = array('Date',
								'Customer',
								'OR #',
								'Amount',
								'EWT',
								'Net Amount'
	);
	
	$aligns = array('left',	'left',	'left',	'right', 'right', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to));

// <<<<<<< .mine
    // // $rep = new FrontReport(_('Cash Receipts'), "PDCCollections", user_pagesize());
    // $rep = new FrontReport(_('Cash Receipts'), "PDCCollections", 'letter','','L');
// =======
    $rep = new FrontReport(_('Cash Receipts'), "PDCCollections", 'A4', 9, 'L');
// >>>>>>> .r471

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
	
	$sql = "SELECT * FROM ".TB_PREF."books_receipts receipt 
				WHERE receipt.date BETWEEN '".date2sql($from)."' 
				AND '".date2sql($to)."'";
	$sql.= " AND trans_type = 12 ";

	$res = db_query($sql, "could not retrieve data");
	
	if(db_num_rows($res) <= 0)
		$rep->TextCol(0,6, "No report found.");
	else{
		while ($myrow = db_fetch($res))
		{
			$rep->TextCol(0, 1, sql2date($myrow['date']));
			$rep->TextCol(1, 2, htmlspecialchars_decode(get_customer_name($myrow['debtor_no'])));
			$rep->TextCol(2, 3, $myrow['reference']);
			$rep->TextCol(3, 4, number_format2(($myrow['amount']+$myrow['ewt']),2));
			$rep->TextCol(4, 5, number_format2($myrow['ewt'],2));
			$rep->TextCol(5, 6, number_format2($myrow['amount'],2));
			$totals[1]+=$myrow['amount']+$myrow['ewt'];
			$totals[2]+=$myrow['ewt'];
			$totals[3]+=$myrow['amount'];
			$rep->NewLine();
		}
		$s=3;
		$rep->Line($rep->row  - 3);
		$rep->NewLine();
		$rep->TextCol(0,3,"Total");
		foreach ($totals as $key => $value) {
			$rep->TextCol($s,$s+1,number_format2($value,2));
			$s++;
		}
			$rep->Line($rep->row  - 4);
	}
	
    $rep->End();
}

?>