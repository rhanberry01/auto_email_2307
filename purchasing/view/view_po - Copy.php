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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/purchasing/includes/po_class.inc");

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Purchase Order"), true, false, "", $js);


if (!isset($_GET['trans_no']))
{
	die ("<br>" . _("This page must be called with a purchase order number to review."));
}

//display_heading(_("Purchase Order") . " #" . getPORef($_GET['trans_no']));
display_heading(_("Purchase Order"));

$purchase_order = new purch_order;

read_po($_GET['trans_no'], $purchase_order);
echo "<br>";
display_po_summary($purchase_order, true);

start_table("$table_style width=90%", 6);
echo "<tr><td valign=top>"; // outer table

display_heading2(_("Details"));

start_table("colspan=9 $table_style width=100%");
//_("Date Required"),
$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"),'Discounts',
	_("Amount"),  _("PO QTY (pcs)"), _("RR QTY (pcs)"), _("Invoiced QTY (pcs)"));

table_header($th);
$total = $k = 0;
$overdue_items = false;

foreach ($purchase_order->line_items as $stock_item)
{

	$line_total = $stock_item->extended;
	$taxable = checkIfTaxable($stock_item->stock_id);
	
	$discounts = array();
	if ($stock_item->disc_percent1 != 0)
		$discounts[] = $stock_item->disc_percent1.'%';
	if ($stock_item->disc_percent2 != 0)
		$discounts[] = $stock_item->disc_percent2.'%';
	if ($stock_item->disc_percent3 != 0)
		$discounts[] = $stock_item->disc_percent3.'%';
	if ($stock_item->disc_amount1 != 0)
		$discounts[] = $stock_item->disc_amount1;
	if ($stock_item->disc_amount2 != 0)
		$discounts[] = $stock_item->disc_amount2;
	if ($stock_item->disc_amount3 != 0)
		$discounts[] = $stock_item->disc_amount3;
	
	alt_table_row_color($k);

	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->quantity, false, $dec);
	label_cell($stock_item->units);
	amount_decimal_cell($stock_item->price);
	label_cell(implode(', ',$discounts));
	amount_cell($line_total);
	// label_cell($stock_item->req_del_date);
	qty_cell($stock_item->item_row['quantity_ordered_pcs'], false, $dec);
	qty_cell($stock_item->item_row['quantity_received_pcs'], false, $dec);
	qty_cell($stock_item->item_row['qty_invoiced_pcs'], false, $dec);
	end_row();

	$total += $line_total;
}

$display_total = number_format2($total,user_price_dec());

$vat_inc = true;

$sql = "SELECT tax_group_id FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($purchase_order->supplier_id);

$result = db_query($sql, "could not get supplier");

$row = db_fetch_row($result);

$_taxes = $purchase_order->get_taxes($row[0]);
$_tax_total = display_edit_tax_items($_taxes, 5, $vat_inc, 0, 0); // tax_included==0 (we are the company)

//display_error($_tax_total);

// if (!$vat_inc)
	// label_row(_("Total Excluding Tax/Shipping"), price_format($total - $_tax_total), "align=right colspan=6", "nowrap align=right", 3);
// else
	// label_row(_("Sub Total"), price_format($total - $_tax_total), "align=right colspan=6", "nowrap align=right", 3);

// $taxes = $purchase_order->get_taxes($row[0]);
// $tax_total = display_edit_tax_items($taxes, 5, $vat_inc); // tax_included==0 (we are the company)

label_row(_("<b>Total</b>"), '<b>'.price_format($total).'</b>', "colspan=6 align=right", "nowrap align=right", 2);

end_table();

// if ($overdue_items)
	// display_note(_("Highlighted items are overdue."), 0, 0, "class='overduefg'");

// if($has_marked)
	// display_note(_("Highlighted items are taxable."), 0, 0);
//----------------------------------------------------------------------------------------------------

$k = 0;

$grns_result = get_po_grns($_GET['trans_no']);

if (db_num_rows($grns_result) > 0)
{

    echo "</td><td valign=top>"; // outer table

    display_heading2(_("Deliveries"));
    start_table($table_style);
    $th = array(_("#"), _("Reference"), _("Delivered On"));
    table_header($th);
    while ($myrow = db_fetch($grns_result))
    {
		alt_table_row_color($k);

    	label_cell(get_trans_view_str(ST_SUPPRECEIVE,$myrow["id"]));
    	label_cell($myrow["reference"]);
    	label_cell(sql2date($myrow["delivery_date"]));
    	end_row();
    }
    end_table();;
}

$invoice_result = get_po_invoices_credits($_GET['trans_no']);

$k = 0;

if (db_num_rows($invoice_result) > 0)
{

    echo "</td><td valign=top>"; // outer table

    display_heading2(_("Invoices/Credits"));
    start_table($table_style);
    $th = array(_("#"), _("Date"), _("Total"));
    table_header($th);
    while ($myrow = db_fetch($invoice_result))
    {
    	alt_table_row_color($k);

    	label_cell(get_trans_view_str($myrow["type"],$myrow["trans_no"]));
    	label_cell(sql2date($myrow["tran_date"]));
    	amount_cell($myrow["Total"]);
    	end_row();
    }
    end_table();
}

echo "</td></tr>";

end_table(1); // outer table

//----------------------------------------------------------------------------------------------------

//end_page(true);
$link_no_amt = '<a target="_blank" href="../../reporting/prn_redirect.php?PARAM_0='.$_GET['trans_no'].'&amp;PARAM_1='.$_GET['trans_no'].'&amp;PARAM_2=&amp;PARAM_3=0&amp;PARAM_4=&amp;PARAM_5=1&amp;REP_ID=209"class="printlink" accesskey="P"><u>P</u>rint Warehouse Copy</a>';
display_note(print_document_link($_GET['trans_no'], _("&Print This Order"), true, ST_PURCHORDER). '   |   '. $link_no_amt, 0, 1);

echo "<br><center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

?>
