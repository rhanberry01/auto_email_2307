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
$page_security = 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Order Status List
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/inventory/includes/db/items_category_db.inc");

//----------------------------------------------------------------------------------------------------

print_order_status_list();

//----------------------------------------------------------------------------------------------------

function GetSalesOrders($from, $to, $category=0, $location=null, $backorder=0)
{
	$fromdate = date2sql($from);
	$todate = date2sql($to);

	$sql= "SELECT ".TB_PREF."sales_orders.order_no,
				".TB_PREF."sales_orders.debtor_no,
                ".TB_PREF."sales_orders.branch_code,
                ".TB_PREF."sales_orders.customer_ref,
                ".TB_PREF."sales_orders.ord_date,
                ".TB_PREF."sales_orders.from_stk_loc,
                ".TB_PREF."sales_orders.delivery_date,
                ".TB_PREF."sales_order_details.stk_code,
                ".TB_PREF."stock_master.description,
                ".TB_PREF."stock_master.units,
                ".TB_PREF."sales_order_details.quantity,
                ".TB_PREF."sales_order_details.qty_sent,
                ".TB_PREF."sales_orders.reference
            FROM ".TB_PREF."sales_orders
            	INNER JOIN ".TB_PREF."sales_order_details
            	    ON (".TB_PREF."sales_orders.order_no = ".TB_PREF."sales_order_details.order_no
            	    AND ".TB_PREF."sales_orders.trans_type = ".TB_PREF."sales_order_details.trans_type
            	    AND ".TB_PREF."sales_orders.trans_type = ".ST_SALESORDER.")
            	INNER JOIN ".TB_PREF."stock_master
            	    ON ".TB_PREF."sales_order_details.stk_code = ".TB_PREF."stock_master.stock_id
            WHERE ".TB_PREF."sales_orders.ord_date >='$fromdate'
                AND ".TB_PREF."sales_orders.ord_date <='$todate'";
	if ($category > 0)
		$sql .= " AND ".TB_PREF."stock_master.category_id=".db_escape($category);
	if ($location != null)
		$sql .= " AND ".TB_PREF."sales_orders.from_stk_loc=".db_escape($location);
	if ($backorder)
		$sql .= " AND ".TB_PREF."sales_order_details.quantity - ".TB_PREF."sales_order_details.qty_sent > 0";
		
	$sql .= " AND ".TB_PREF."sales_orders.reference !=".db_escape('auto');
	$sql .= " ORDER BY ".TB_PREF."sales_orders.order_no";

	return db_query($sql, "Error getting order details");
}

//----------------------------------------------------------------------------------------------------

function print_order_status_list()
{
	global $path_to_root;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$category = $_POST['PARAM_2'];
	$location = $_POST['PARAM_3'];
	$backorder = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($category == ALL_NUMERIC)
		$category = 0;
	if ($location == ALL_TEXT)
		$location = null;
	if ($category == 0)
		$cat = _('All');
	else
		$cat = get_category_name($category);
	if ($location == null)
		$loc = _('All');
	else
		$loc = get_location_name($location);
	if ($backorder == 0)
		$back = _('All Orders');
	else
		$back = _('Back Orders Only');

	//$cols = array(0, 60, 150, 260, 325,	385, 450, 515);
	$cols = array(0, 80, 200, 310, 400,	500, 600, 700);

	// $headers2 = array(_('Order'), _('Customer'), _('Branch'), _('Customer Ref'),
		// _('Ord Date'),	_('Del Date'),	_('Loc'));

	$aligns = array('left',	'left',	'right', 'right', 'right', 'right',	'right');

	// $headers = array(_('Code'),	_('Description'), _('Ordered'),	_('QTY Invoiced'),
		// _('Outstanding'), '');

    $params =   array( 	0 => $comments,
	    				1 => array(  'text' => _('Period'), 'from' => $from, 'to' => $to),
	    				2 => array(  'text' => _('Category'), 'from' => $cat,'to' => ''),
	    				3 => array(  'text' => _('Location'), 'from' => $loc, 'to' => ''),
	    				4 => array(  'text' => _('Selection'),'from' => $back,'to' => ''));

	$cols2 = $cols;
	$aligns2 = $aligns;

	$rep = new FrontReport(_('Order Status Listing'), "OrderStatusListing", user_pagesize(), 9, "L");
	$rep->Font();
	// $rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
	// $rep->Header();
	
	if ($destination)	//excel
	{
		$headers2 = array(_('Order'), _('Customer'), _('Branch'), _('Customer Ref'),
		_('Ord Date'),	_('Del Date'),	_('Loc'));
		$headers = array(_('Code'),	_('Description'), _(''),	_('Ordered'),
		_('QTY Invoiced'), _('Outstanding'), '');
		
		$rep->Info($params, $cols, $headers, $aligns, $cols2, $headers2, $aligns2);
		$rep->Header__();
	}
	else
	{
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header_();
	}
	
	$orderno = 0;

	$result = GetSalesOrders($from, $to, $category, $location, $backorder);
	
	$rep->row += 20;

	while ($myrow=db_fetch($result))
	{
		if ($rep->row < $rep->bottomMargin + (2 * $rep->lineHeight))
		{
			$orderno = 0;
			//$rep->Header();
			
			if ($destination)	//excel
				$rep->Header__();
			else
				$rep->Header_();
		}
		$rep->NewLine(0, 2, false, $orderno);
		if ($orderno != $myrow['reference'])
		{
			if ($orderno != 0)
			{
				$rep->Line($rep->row,1);
				$rep->NewLine(4);
			}
			
			// header	
			$rep->TextCol(0, 1,	"Order");
			$rep->TextCol(1, 2,	"Customer");
			$rep->TextCol(2, 3, " ");
			$rep->TextCol(3, 4,	"Customer Ref");
			$rep->TextCol(4, 5,	"Ord Date");
			$rep->TextCol(5, 6,	"Del Date");	
			$rep->TextCol(6, 7, "Loc");	
			$rep->Line($rep->row - 4);
			$rep->NewLine(2);
			// header	
			
			$rep->font('b');
			$rep->TextCol(0, 1,	$myrow['reference']);
			$rep->TextCol(1, 3,	get_customer_name($myrow['debtor_no']));
			//$rep->TextCol(2, 3,	get_branch_name($myrow['branch_code']));
			$rep->TextCol(3, 4,	$myrow['customer_ref']);
			$rep->DateCol(4, 5,	$myrow['ord_date'], true);
			$rep->DateCol(5, 6,	$myrow['delivery_date'], true);
			$rep->TextCol(6, 7,	$myrow['from_stk_loc']);
			$rep->NewLine(2);
			// $rep->Line($rep->row-3);
			// $rep->NewLine(1);
			$rep->font('');
			$orderno = $myrow['reference'];
			
			// header	
			$rep->TextCol(0, 1,	"Code");
			$rep->TextCol(1, 2,	"Description");
			$rep->TextCol(2, 3, "");
			$rep->TextCol(3, 4,	"Ordered");
			$rep->TextCol(4, 5,	"QTY Invoiced");
			$rep->TextCol(5, 6,	"Outstanding");
			$rep->Line($rep->row - 4);
			$rep->NewLine(2);
			// header	
		}
		$rep->TextCol(0, 1,	$myrow['stk_code']);
		$rep->TextCol(1, 3,	$myrow['description']);
		$dec = get_qty_dec($myrow['stk_code']);
		$rep->AmountCol(3, 4, $myrow['quantity'], $dec);
		$rep->AmountCol(4, 5, $myrow['qty_sent'], $dec);
		$rep->AmountCol(5, 6, $myrow['quantity'] - $myrow['qty_sent'], $dec);
		if ($myrow['quantity'] - $myrow['qty_sent'] > 0)
		{
			$rep->Font('italic');
			$rep->TextCol(6, 7,	_('Outstanding'));
			$rep->Font();
		}
		$rep->NewLine();
		if ($rep->row < $rep->bottomMargin + (2 * $rep->lineHeight))
		{
			$orderno = 0;
			//$rep->Header();
			
			if ($destination)	//excel
				$rep->Header__();
			else
				$rep->Header_();
		}
	}
	$rep->Line($rep->row);
	$rep->End();
}

?>