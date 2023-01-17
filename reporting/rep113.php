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
$page_security = 'SA_CUSTPAYMREP';

// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Customer Balances
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

// trial_inquiry_controls();
print_sales_report();

function get_transactions($from, $to, $fromcust, $area)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = "SELECT ".TB_PREF."debtor_trans.*,
		(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
			(".TB_PREF."debtor_trans.ov_amount  * (1 - discount1) * (1 - discount2) * (1 - discount3)
					* (1 - discount4) * (1 - discount5) 
			)
		)
		AS TotalAmount,
		(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
			 ".TB_PREF."debtor_trans.ov_amount  - (".TB_PREF."debtor_trans.ov_amount  * (1 - discount1) * (1 - discount2) * (1 - discount3)
					* (1 - discount4) * (1 - discount5) 
			)
		)
		AS DiscAmount,
		(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
			(".TB_PREF."debtor_trans.ov_amount )
		)
		AS Amount, ".TB_PREF."debtors_master.name, ".TB_PREF."cust_branch.salesman, ".TB_PREF."cust_branch.area
    	FROM ".TB_PREF."debtor_trans, ".TB_PREF."debtors_master, ".TB_PREF."cust_branch
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '$from'
		AND ".TB_PREF."debtor_trans.tran_date <= '$to'
		AND ".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE."
		AND ".TB_PREF."debtor_trans.debtor_no = ".TB_PREF."debtors_master.debtor_no
		AND ".TB_PREF."debtor_trans.branch_code = ".TB_PREF."cust_branch.branch_code
		AND ".TB_PREF."debtors_master.debtor_no = ".TB_PREF."cust_branch.debtor_no ";
	if ($fromcust != ALL_NUMERIC)
		$sql .= " AND ".TB_PREF."debtor_trans.debtor_no=".db_escape($fromcust);	
	if ($area != ALL_NUMERIC)
		$sql .= " AND ".TB_PREF."cust_branch.area=".db_escape($area);
		
    $sql .= " ORDER BY ".TB_PREF."debtor_trans.trans_no, ".TB_PREF."debtor_trans.tran_date, 
			".TB_PREF."debtor_trans.debtor_no, ".TB_PREF."cust_branch.area";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_sales_report()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $fromcust = $_POST['PARAM_2'];
	$area = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($fromcust == ALL_NUMERIC)
		$cust = _('All');
	else
		$cust = get_customer_name($fromcust);
    $dec = user_price_dec();

	$cols = array(0, 250, 320, 420, 480, 540, 620);

	$headers = array(_('Customer'), _('INV #'), _('INV Date'), _('Amount'), "Discount", "Total");

	$aligns = array('left',	'left',	'left',	'left',	'right', 'right','right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to));

    $rep = new FrontReport(_('Sales Report'), "SalesReport", user_pagesize(), 9, "L");

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$result = get_transactions($from, $to, $fromcust, $area);
	
	if(db_num_rows($result) <= 0)
		$rep->TextCol(0,6, "No report found.");
	else
		$total = 0;
		while ($myrow = db_fetch($result))
		{
			$rep->TextCol(0, 1, $myrow['name']);
			$rep->TextCol(1, 2, $myrow['reference']);
			$rep->TextCol(2, 3, date("m/d/Y", strtotime($myrow['tran_date'])));
			$rep->AmountCol(3, 4, $myrow['Amount'], $dec);
			$rep->AmountCol(4, 5, $myrow['DiscAmount'], $dec);
			$rep->AmountCol(5, 6, $myrow['TotalAmount'], $dec);
			$rep->NewLine();
			
			$total += $myrow['TotalAmount'];
		}
		
		$rep->Line($rep->row + 6);
		$rep->row -= 6;
		$rep->fontSize+=2;
		$rep->TextCol(0, 2,	_('Grand Total '));
		$rep->fontSize-=2;
		$rep->AmountCol(5, 6, $total, $dec);
		$rep->Line($rep->row  - 4);
			$rep->NewLine();
    $rep->End();
}

?>