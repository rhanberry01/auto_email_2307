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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Purchase Order Delivery"), true, false, "", $js);

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");

if (!isset($_GET['trans_no']))
{
	die ("<BR>" . _("This page must be called with a Purchase Order Delivery number to review."));
}

$purchase_order = new purch_order;
read_grn($_GET["trans_no"], $purchase_order);

//display_heading(_("Purchase Order Delivery") . " #" . getRecRef($_GET['trans_no']));
display_heading(_("Purchase Order Delivery"));
echo "<BR>";
display_grn_summary($purchase_order);

display_heading2(_("Details"));

start_table("colspan=9 $table_style width=90%");
$th = array(_("Item Code"), _("Item Description"), _("Delivery Date"), _("Quantity"),
	_("Unit"), 
	// _("Price"), _("Amount"), _("Quantity Invoiced"), 
	_("Notes"));

table_header($th);

$total = 0;
$k = 0;  //row colour counter

//print_r($purchase_order->line_items);

$_total_qty = 0;

foreach ($purchase_order->line_items as $stock_item)
{

	$line_total = $stock_item->qty_received * $stock_item->price;

	alt_table_row_color($k);

	label_cell($stock_item->stock_id);
	label_cell($stock_item->item_description);
	label_cell($stock_item->req_del_date, "nowrap align=right");
	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->qty_received, false, $dec);
	label_cell($stock_item->units);
	// amount_decimal_cell($stock_item->price);
	// amount_cell($line_total);
	// qty_cell($stock_item->qty_inv, false, $dec);
	label_cell($stock_item->rnotes);
	end_row();

	$total += $line_total;
	$_total_qty += $stock_item->qty_received;
}

$sql = "SELECT tax_group_id FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($purchase_order->supplier_id);

$result = db_query($sql, "could not get supplier");

$row = db_fetch_row($result);
//display_error($row[0]);

//$_taxes = $purchase_order->get_taxes($row[0]);
//$_tax_total = display_edit_tax_items($_taxes, 6, $vat_inc); 

//display_error($_tax_total);

//$display_total = number_format2($total,user_price_dec());
//label_row(_("Total Excluding Tax/Shipping"),  $display_total, "colspan=6", "nowrap align=right");

//$taxes = $purchase_order->get_taxes($row[0]);
//$tax_total = display_edit_tax_items($taxes, 6, $vat_inc); // tax_included==0 (we are the company)
	
//label_row(_("Total"),  $display_total, "colspan=6", "nowrap align=right");

end_table();

echo "<br>";

$sql = "SELECT reference FROM ".TB_PREF."purch_orders WHERE order_no=".$purchase_order->order_no;
$result = db_query($sql, "could not get supplier");
$row = db_fetch_row($result);

// $sql = "SELECT rr_no FROM receiving_new.0_receiving WHERE po_no = '".$row[0]."'";
$sql = "SELECT reference FROM 0_grn_batch WHERE id = ".$_GET["trans_no"];
$res2 = db_query($sql);
$row2 = db_fetch($res2);


start_table("$table_style width=40%");

if ($purchase_order->rcomments != '' AND $purchase_order->rcomments != 'NULL')
	label_cells(_("Comments"), $purchase_order->rcomments, "class='tableheader2'");

end_table(1);

hyperlink_params($path_to_root.'/includes/ui/view_pdf.php', 'View Invoice Attachment', "file=".get_po_attachment($row[0],str_pad($row2[0], 10, "0", STR_PAD_LEFT)) , $center=true);
is_voided_display(ST_SUPPRECEIVE, $_GET['trans_no'], _("This delivery has been voided."));

end_page(true);

?>
