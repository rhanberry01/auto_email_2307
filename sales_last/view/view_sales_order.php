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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/sales/includes/cart_class.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows){
	$js .= get_js_open_window(900, 600);
}

if ($_GET['trans_type'] == ST_SALESQUOTE)
{
	page(_($help_context = "View Sales Quotation"), true, false, "", $js);
	//display_heading(sprintf(_("Sales Quotation #%s"),getSORef($_GET['trans_no'])));
	display_heading(sprintf(_("Sales Quotation"),''));

}	
else
{
	page(_($help_context = "View Sales Order"), true, false, "", $js);
	//display_heading(sprintf(_("Sales Order #%d"),$_GET['trans_no']));
	display_heading(sprintf(_("Sales Order"),''));
}

if (isset($_SESSION['View']))
{
	unset ($_SESSION['View']);
}

$_SESSION['View'] = new Cart($_GET['trans_type'], $_GET['trans_no'], true);

start_table("$table_style2 width=95%", 5);
echo "<tr valign=top><td>";
display_heading2(_("Order Information"));
if ($_GET['trans_type'] != ST_SALESQUOTE)
{
	echo "</td><td>";
	display_heading2(_("Deliveries"));
	echo "</td><td>";
	display_heading2(_("Invoices/Credits"));
}	
echo "</td></tr>";

echo "<tr valign=top><td>";				

start_table("$table_style width=95%");
label_row(_("Customer Name"), $_SESSION['View']->customer_name, "class='tableheader2'",
	"colspan=3");
start_row();
label_cells(_("Customer Order Ref."), $_SESSION['View']->cust_ref, "class='tableheader2'");
label_cells(_("Deliver To Branch"), $_SESSION['View']->deliver_to, "class='tableheader2'");
end_row();
start_row();
label_cells(_("Ordered On"), $_SESSION['View']->document_date, "class='tableheader2'");
if ($_GET['trans_type'] == ST_SALESQUOTE)
	label_cells(_("Valid until"), $_SESSION['View']->due_date, "class='tableheader2'");
else
	label_cells(_("Requested Delivery"), $_SESSION['View']->due_date, "class='tableheader2'");
end_row();
start_row();
label_cells(_("Order Currency"), $_SESSION['View']->customer_currency, "class='tableheader2'");
label_cells(_("Deliver From Location"), $_SESSION['View']->location_name, "class='tableheader2'");
end_row();

label_row(_("Delivery Address"), nl2br($_SESSION['View']->delivery_address),
	"class='tableheader2'", "colspan=3");
label_row(_("SO No."), $_SESSION['View']->reference, "class='tableheader2'", "colspan=3");
label_row(_("Telephone"), $_SESSION['View']->phone, "class='tableheader2'", "colspan=3");
label_row(_("Payment Terms"), get_term_name($_SESSION['View']->p_terms), "class='tableheader2'", "colspan=3");
label_row(_("E-mail"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>",
	"class='tableheader2'", "colspan=3");
label_row(_("Comments"), $_SESSION['View']->Comments, "class='tableheader2'", "colspan=3");
end_table();

if ($_GET['trans_type'] != ST_SALESQUOTE)
{
	echo "</td><td valign='top'>";

	start_table($table_style);
	display_heading2(_("Delivery Receipts"));


	$th = array( _("DR No."), _("Date"), _("Total"));
	table_header($th);

	$sql = "SELECT * FROM ".TB_PREF."debtor_trans WHERE type=".ST_CUSTDELIVERY." AND skip_dr = 0 AND order_=".db_escape($_GET['trans_no']);
	$result = db_query($sql,"The related delivery receipts could not be retrieved");

	$delivery_total = 0;
	$k = 0;

	while ($del_row = db_fetch($result))
	{

		alt_table_row_color($k);

		$this_total = (
						$del_row["ov_amount"] 
					)
					+ $del_row["ov_freight"] + $del_row["ov_freight_tax"] + $del_row["ov_gst"] ;
		$delivery_total += $this_total;

		label_cell(get_customer_trans_view_str($del_row["type"], $del_row["trans_no"], $del_row["reference"]), 'nowrap');
		//label_cell($del_row["reference"]);
		label_cell(sql2date($del_row["tran_date"]));
		amount_cell($this_total);
		end_row();

	}
	if($delivery_total!=0){
	label_row(null, price_format($delivery_total), " ", "colspan=4 align=right");
	end_table(1);
	}else
	end_table(2);
	echo "</td><td valign='top'>";

	start_table($table_style);
	display_heading2(_("Sales Invoices"));

	$th = array( _("INV No."), _("Date"), _("Total"));
	table_header($th);

	$sql = "SELECT * FROM ".TB_PREF."debtor_trans WHERE type=".ST_SALESINVOICE." AND order_=".db_escape($_GET['trans_no']);
	$result = db_query($sql,"The related invoices could not be retrieved");

	$invoices_total = 0;
	$k = 0;

	while ($inv_row = db_fetch($result))
	{

		alt_table_row_color($k);

		$this_total = $inv_row["ov_freight"] + $inv_row["ov_freight_tax"]  + $inv_row["ov_gst"] + ($inv_row["ov_amount"]
			  );
		$invoices_total += $this_total;

		label_cell(get_customer_trans_view_str($inv_row["type"], $inv_row["trans_no"], $inv_row["reference"]), 'nowrap');
		//label_cell($inv_row["reference"]);
		label_cell(sql2date($inv_row["tran_date"]));
		amount_cell($this_total);
		end_row();

	}

	if($invoices_total!=0){
	label_row(null, price_format($invoices_total), " ", "colspan=4 align=right");
	end_table(1);
	}
	else{

	end_table(2);
	}

	display_heading2(_("Credit Notes"));

	start_table($table_style);
	$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
	table_header($th);

	$sql = "SELECT * FROM ".TB_PREF."debtor_trans WHERE type=".ST_CUSTCREDIT." AND order_=".db_escape($_GET['trans_no']);
	$result = db_query($sql,"The related credit notes could not be retrieved");

	$credits_total = 0;
	$k = 0;

	while ($credits_row = db_fetch($result))
	{

		alt_table_row_color($k);

		$this_total = $credits_row["ov_freight"] + $credits_row["ov_freight_tax"]  + $credits_row["ov_gst"] + $credits_row["ov_amount"];
		$credits_total += $this_total;

		label_cell(get_customer_trans_view_str($credits_row["type"], $credits_row["trans_no"]), 'nowrap');
		label_cell($credits_row["reference"]);
		label_cell(sql2date($credits_row["tran_date"]));
		amount_cell(-$this_total);
		end_row();

	}
	if($credits_total!=0){
	label_row(null, "<font color=red>" . price_format(-$credits_total) . "</font>",
		" ", "colspan=4 align=right");
	end_table(1);
	}else
	end_table(2);

	echo "</td></tr>";

	end_table();
}
echo "<center>";
if ($_SESSION['View']->so_type == 1)
	display_note(_("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
display_heading2(_("Details"));

start_table("colspan=9 width=95% $table_style");
$th = array(_("Item Code"), _("Item Description"), _("Notes"), _("Quantity"), _("Unit"),
	_("Price"), _("Discount"), /*_("Discount2"), _("Discount3"), _("Discount4"), _("Discount5"), 
	_("Discount6"),*/ _("Total"), _("Quantity Delivered"));
table_header($th);

$k = 0;  //row colour counter

$vatable = $nonvat = $zerorated = 0;
foreach ($_SESSION['View']->line_items as $stock_item) {

	$line_total = round2($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent) * 
					(1 - $stock_item->discount_percent2) * (1 - $stock_item->discount_percent3) * 
					(1 - $stock_item->discount_percent4) * (1 - $stock_item->discount_percent5) * 
					(1 - $stock_item->discount_percent6),
				user_price_dec());
				
	/////////////////////////////////////////////////////////////////
			
	$sql = "SELECT tax_type_id FROM 0_stock_master WHERE stock_id = ".db_escape($stock_item->stock_id);
	$result = db_query($sql,"could not retrieve tax_type_id");
	$row = db_fetch_row($result);		
	
	if($row[0] == 1)
		$vatable += $line_total;
	else if($row[0] == 2)
		$nonvat += $line_total;
	else
		$zerorated += $line_total;
	
	/////////////////////////////////////////////////////////////////

	alt_table_row_color($k);

	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	label_cell($stock_item->comment);
	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->quantity, false, $dec);
	label_cell($stock_item->units);
	amount_cell($stock_item->price);
	amount_cell($stock_item->discount_percent * 100);
	// amount_cell($stock_item->discount_percent2 * 100);
	// amount_cell($stock_item->discount_percent3 * 100);
	// amount_cell($stock_item->discount_percent4 * 100);
	// amount_cell($stock_item->discount_percent5 * 100);
	// amount_cell($stock_item->discount_percent6 * 100);
	amount_cell($line_total);

	qty_cell($stock_item->qty_done, false, $dec);
	end_row();
}

$items_total = $_SESSION['View']->get_items_total();

//$display_total = price_format($items_total + $_SESSION['View']->freight_cost);
$display_subtotal = price_format($items_total);



// display_error(var_dump($_SESSION['View']));	

//$_subtamt = trim($display_subtotal, ',');
$_subtotal = $items_total/1.12;
$total = $_SESSION['View']->freight_cost + ( $items_total );

label_row(_("Total Sales"), price_format($total - $_SESSION['View']->freight_cost), "align=right colspan=7", "nowrap align=right", 1);

// label_row(_("VATABLE Sales"), price_format(round($_subtotal, user_price_dec())), "align=right colspan=12", "nowrap align=right", 1);
// label_row(_("NON-VATABLE Sales"), price_format(0), "align=right colspan=12", "nowrap align=right", 1);
// label_row(_("ZERO RATED Sales"), price_format(0), "align=right colspan=12", "nowrap align=right", 1);
// label_row(_("VAT (12%) Amount"), price_format(round($items_total-($items_total/1.12), user_price_dec())), "align=right colspan=12", "nowrap align=right", 1);


//////////////////////////////////////////////////////////////////////////////
$sql1 = "SELECT tax_group_id FROM 0_cust_branch WHERE branch_code = ".db_escape($_SESSION['View']->Branch);
$result1 = db_query($sql1,"could not retrieve tax_type_id");
$row1 = db_fetch_row($result1);

if($row1[0] == 1)
{
	label_row(_("VATABLE Sales"), price_format($vatable/1.12), "align=right colspan=7", "nowrap align=right", 1);
	label_row(_("NON-VATABLE Sales"), price_format($nonvat), "align=right colspan=7", "nowrap align=right", 1);
	label_row(_("ZERO RATED Sales"), price_format($zerorated), "align=right colspan=7", "nowrap align=right", 1);
}
else if($row1[0] == 2)
{
	label_row(_("VATABLE Sales"), price_format(0), "align=right colspan=7", "nowrap align=right", 1);
	label_row(_("NON-VATABLE Sales"), price_format($vatable+$nonvat+$zerorated), "align=right colspan=7", "nowrap align=right", 1);
	label_row(_("ZERO RATED Sales"), price_format(0), "align=right colspan=7", "nowrap align=right", 1);
}
else 
{
	label_row(_("VATABLE Sales"), price_format(0), "align=right colspan=7", "nowrap align=right", 1);
	label_row(_("NON-VATABLE Sales"), price_format($nonvat), "align=right colspan=7", "nowrap align=right", 1);
	label_row(_("ZERO RATED Sales"), price_format($vatable+$zerorated), "align=right colspan=7", "nowrap align=right", 1);
}

$taxes = $_SESSION['View']->get_taxes($_SESSION['View']->freight_cost);
$tax_total = display_edit_tax_items($taxes, 7, $_SESSION['View']->tax_included, 2);

//////////////////////////////////////////////////////////////////////////////

	
label_row(_("Shipping"), price_format($_SESSION['View']->freight_cost), "align=right colspan=7", "nowrap align=right", 1);

label_row(_("Amount Total"), price_format($total), "align=right colspan=7", "nowrap align=right", 1);
	


end_table(2);

//end_page(true);
//submenu_print(_("&Print This Order"), ST_SALESORDER, $_GET['trans_no'], 'printopt');
//display_note(print_document_link($trans_id, _("&Print This Invoice"), true, ST_SALESINVOICE), 0, 1);
display_note(print_document_link($_GET['trans_no'], _("&Print This Order"), true, ST_SALESORDER), 0, 1);
echo "<center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

?>
