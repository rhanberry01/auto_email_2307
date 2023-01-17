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

print_sales_book();

function print_sales_book()
{
    global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    // $customer = $_POST['PARAM_2'];
    // $or_num = $_POST['PARAM_3'];
    // $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_2'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

	$cols = array(0, 60, 300, 375,450, 525, 600,670);

	// $headers = array(_('Customer'), _('Bank'), _('OR #'), _('Check #'), _('Check Date'), _('Amount'));
	$headers = array('Date',
								'Customer',
								'INV #',
								'Sales Amount',
								'Output Tax',
								'Discount',
								'Return',
								'Net Sales'
	);
	
	$aligns = array('left',	'left',	'left',	'right', 'right', 'right','right','right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''));

    $rep = new FrontReport(_('Sales Book'), "SalesBook", 'Letter','9','L');

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
	
	$sql="SELECT a.trans_type,a.trans_no FROM ".TB_PREF."books_sales a JOIN ".TB_PREF."voided b ON a.trans_type = b.type AND a.trans_no = b.id WHERE a.date BETWEEN ".db_escape(date2sql($from))." AND ".db_escape(date2sql($to));
	$sql=db_query($sql);
	$voided=array();
	while($data=db_fetch($sql)){
		$voided[$data['trans_type']][$data['trans_no']]=true;
	}

	$sql="SELECT * FROM ".TB_PREF."books_sales WHERE date BETWEEN ".db_escape(date2sql($from))." AND ".db_escape(date2sql($to));
		//die( $sql);
/*	
	$sql = "SELECT * FROM ".TB_PREF."books_receipts receipt 
				WHERE receipt.date BETWEEN '".date2sql($from)."' 
				AND '".date2sql($to)."'";
	$sql.= " AND trans_type = 12 ";
	if ($customer != ALL_NUMERIC)
	$sql.= " AND receipt.debtor_no = ".db_escape($customer);
	if($or_num!="")
	$sql.= " AND receipt.reference LIKE (".db_escape($or_num).")";
*/
	$res = db_query($sql, "could not retrieve date");
	
	if(db_num_rows($res) <= 0)
		$rep->TextCol(0,6, "No Record found.");
	else{
		while ($myrow = db_fetch($res))
		{
			$rep->TextCol(0, 1, sql2date($myrow['date']));
			$rep->TextCol(1, 2, htmlspecialchars_decode(get_customer_name($myrow['debtor_no'])));
			$rep->TextCol(2, 3, $myrow['reference'].((isset($voided[$myrow['trans_type']][$myrow['trans_no']]) && $voided[$myrow['trans_type']][$myrow['trans_no']]==true)?" (voided)":""));
			$rep->TextCol(3, 4, number_format2(($myrow['sales_amount']),2));
			$rep->TextCol(4, 5, number_format2($myrow['output_tax'],2));
			$rep->TextCol(5, 6, number_format2($myrow['discount'],2));
			$rep->TextCol(6, 7, number_format2($myrow['returns'],2));
			$rep->TextCol(7, 8, number_format2(($myrow['sales_amount']-$myrow['discount']-$myrow['returns']),2));
			$rep->NewLine();
			$totals[1]+=$myrow['sales_amount'];
			$totals[2]+=$myrow['output_tax'];
			$totals[3]+=$myrow['discount'];
			$totals[4]+=$myrow['returns'];
			$totals[5]+=$myrow['sales_amount']-$myrow['discount']-$myrow['returns'];
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