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
print_sales_report_per_item();

function get_transactions($from, $to, $fromcust, $items)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = "SELECT ".TB_PREF."debtor_trans.*, ".TB_PREF."debtor_trans_details.quantity, ".TB_PREF."debtor_trans_details.unit_price,".TB_PREF."debtor_trans_details.unit_price - ((".TB_PREF."debtor_trans_details.unit_price) 
				*  (1 - ".TB_PREF."debtor_trans_details.discount_percent) * (1 - ".TB_PREF."debtor_trans_details.discount_percent2) * (1 - ".TB_PREF."debtor_trans_details.discount_percent3) * (1 - ".TB_PREF."debtor_trans_details.discount_percent4) * (1 - ".TB_PREF."debtor_trans_details.discount_percent5) * (1 - ".TB_PREF."debtor_trans_details.discount_percent6)
			) as DiscAmount,
			((".TB_PREF."debtor_trans_details.quantity * ".TB_PREF."debtor_trans_details.unit_price) 
				* (1 - ".TB_PREF."debtor_trans_details.discount_percent) * (1 - ".TB_PREF."debtor_trans_details.discount_percent2) * (1 - ".TB_PREF."debtor_trans_details.discount_percent3) * (1 - ".TB_PREF."debtor_trans_details.discount_percent4) * (1 - ".TB_PREF."debtor_trans_details.discount_percent5) * (1 - ".TB_PREF."debtor_trans_details.discount_percent6)
			)
			AS TotalAmount, 
			".TB_PREF."debtors_master.name, ".TB_PREF."cust_branch.salesman, ".TB_PREF."cust_branch.area
    	FROM ".TB_PREF."debtor_trans, ".TB_PREF."debtor_trans_details, ".TB_PREF."debtors_master, ".TB_PREF."cust_branch
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '$from'
		AND ".TB_PREF."debtor_trans.tran_date <= '$to'
		AND ".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE." 
		AND ".TB_PREF."debtor_trans.trans_no = ".TB_PREF."debtor_trans_details.debtor_trans_no
		AND ".TB_PREF."debtor_trans.debtor_no = ".TB_PREF."debtors_master.debtor_no
		AND ".TB_PREF."debtor_trans.branch_code = ".TB_PREF."cust_branch.branch_code
		AND ".TB_PREF."debtors_master.debtor_no = ".TB_PREF."cust_branch.debtor_no 
		AND ".TB_PREF."debtor_trans.type = ".TB_PREF."debtor_trans_details.debtor_trans_type 
		AND ".TB_PREF."debtor_trans_details.stock_id=".db_escape($items);
	if ($fromcust != ALL_NUMERIC)
		$sql .= " AND ".TB_PREF."debtor_trans.debtor_no=".db_escape($fromcust);
		
    $sql .= " ORDER BY ".TB_PREF."debtor_trans.trans_no, ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_sales_report_per_item()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $items = $_POST['PARAM_2'];
    $fromcust = $_POST['PARAM_3'];
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
    $iDec = user_qty_dec();

	//$cols = array(0, 70, 120, 360, 430, 500, 575,650,730);
	$cols = array(0, 70, 150, 380, 460, 555, 650, 730);

	// $headers = array(_('SI #'), _('Date'), _('Name'),  _('Salesman'), "Item Name", "Qty Unit", "Unit Price",
		// _('Discount Amt'),"Net Amount");

	$aligns = array('left',	'left',	'left',	'right','right',"right","right");

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 		'to' => $to));

    $rep = new FrontReport(_('Sales Report Per Item'), "SalesReportPerItem", user_pagesize(),9,"L");

    $rep->Font();
    // $rep->Info($params, $cols, $headers, $aligns);
    // $rep->Header();
	
	if ($destination)	//excel
	{
		$headers = array(_('INV #'), _('Date'), _('Name'), "Qty Unit", "Unit Price", _('Discount Amt'),"Net Amount");
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header__();
	}
	else
	{
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header_();
	}
	
	$sql = "SELECT DISTINCT ".TB_PREF."debtor_trans_details.stock_id, ".TB_PREF."debtor_trans_details.description
    	FROM ".TB_PREF."debtor_trans, ".TB_PREF."debtor_trans_details
    	WHERE ".TB_PREF."debtor_trans.tran_date >= '".date2sql($from)."'
		AND ".TB_PREF."debtor_trans.tran_date <= '".date2sql($to)."'
		AND ".TB_PREF."debtor_trans.type = ".ST_SALESINVOICE." 
		AND ".TB_PREF."debtor_trans.trans_no = ".TB_PREF."debtor_trans_details.debtor_trans_no
		AND ".TB_PREF."debtor_trans.type = ".TB_PREF."debtor_trans_details.debtor_trans_type ";
	if ($items != null)
		$sql .= " AND ".TB_PREF."debtor_trans_details.stock_id=".db_escape($items);
		
    $sql .= " ORDER BY ".TB_PREF."debtor_trans.trans_no, ".TB_PREF."debtor_trans.tran_date";
	$res = db_query($sql, "Items could not be retrieved");

	$rep->row += 20;
	
	$name = '';
	if(db_num_rows($res) <= 0)
		$rep->TextCol(0,6, "No report found.");
	else
		while ($row=db_fetch($res)) 
		{
			if ($name != $row['stock_id'])
			{
				$rep->fontSize += 1;
				$rep->Font('bold');	
				$rep->NewLine();
				$rep->TextCol(0, 3, strtoupper($row['stock_id'] ." - ". $row['description']), 100);
				$name = $row['stock_id'];
				$rep->fontSize -= 1;
				$rep->Font();
				$rep->NewLine();
				$stock_det=get_item($name);
			}
			
			// header	
			$rep->Font('bold');	
			$rep->TextCol(0, 1,	"INV #");
			$rep->TextCol(1, 2,	"Date");
			$rep->TextCol(2, 3,	"Name");
		//	$rep->TextCol(3, 4, "Salesman");
		//	$rep->TextCol(4, 5,	"Item Name");
			$rep->TextCol(3, 4,	"Qty Unit");	
			$rep->TextCol(4, 5, "Unit Price");	
			$rep->TextCol(5, 6,	"Discount Amt");	
			$rep->TextCol(6, 7,	"Net Amount");	
			$rep->NewLine(1, 2);
			$rep->Font('');
			
			$rep->Line($rep->row + 4);
			$rep->NewLine();
			// header	
			
			$result = get_transactions($from, $to, $fromcust, $row['stock_id']);
			$total = 0;
			while ($myrow = db_fetch($result))
			{
				$rep->TextCol(0, 1, $myrow['reference']);
				$rep->TextCol(1, 2, date("m/d/Y", strtotime($myrow['tran_date'])));
				$rep->TextCol(2, 3, $myrow['name']);
				//$rep->TextCol(3, 4, get_area_name($myrow['area']));
			//	$rep->TextCol(3, 4, get_salesman_name($myrow['salesman']));
			//	$rep->TextCol(4, 5, $stock_det['description']);
				$rep->TextCol(3, 4, number_format($myrow['quantity'],$iDec)." ".$stock_det['units']);
				$rep->AmountCol(4, 5, $myrow['unit_price'],$dec);
				$rep->AmountCol(5, 6, $myrow['DiscAmount'], $dec);
				$rep->AmountCol(6, 7, $myrow['TotalAmount'], $dec);
				$rep->NewLine();
				
				$total += $myrow['TotalAmount'];
			}
			
			$rep->Line($rep->row + 6);
			$rep->row -= 6;
			$rep->TextCol(0, 2,	_('Sub-Total '));
			$rep->AmountCol(6, 7, $total, $dec);
			$rep->NewLine();
			$gtotal += $total;
		}
			$rep->Line($rep->row + 6);
			$rep->row -=10;
			
			$rep->fontSize += 3;
			$rep->Font('bold');
				$rep->NewLine();
			$rep->TextCol(0, 2,	_('Grand Total '));
			$rep->AmountCol(6, 7, $gtotal, $dec);
			//$rep->Line($rep->row -4);
			$rep->NewLine();
	
	
    $rep->End();
}

?>