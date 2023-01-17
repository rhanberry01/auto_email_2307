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
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");

include_once($path_to_root . "/sales/includes/sales_db.inc");

include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
page(_($help_context = "View Sales Dispatch"), true, false, "", $js);


if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}
elseif (isset($_POST["trans_no"]))
{
	$trans_id = $_POST["trans_no"];
}

// 3 different queries to get the information - what a JOKE !!!!

$myrow = get_customer_trans($trans_id, ST_CUSTDELIVERY);

$branch = get_branch($myrow["branch_code"]);
//display_error($myrow['order_']);
$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);

//display_heading(sprintf(_("DISPATCH NOTE #%d"),getReferencebyType($trans_id, 'DR')));
//display_heading(sprintf(_("DELIVERY RECEIPT #%s"),getReferencebyType($trans_id, 'DR')));
display_heading(sprintf(_("DELIVERY RECEIPT"),''));

echo "<br>";
start_table("$table_style2 width=95%");
echo "<tr valign=top><td>"; // outer table

/*Now the customer charged to details in a sub table*/
start_table("$table_style width=100%");
$th = array(_("Charge To"));
table_header($th);

label_row(null, $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "nowrap");

end_table();

/*end of the small table showing charge to account details */

echo "</td><td>"; // outer table

/*end of the main table showing the company name and charge to details */

start_table("$table_style width=100%");
$th = array(_("Charge Branch"));
table_header($th);

label_row(null, $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "nowrap");
end_table();

echo "</td><td>"; // outer table

start_table("$table_style width=100%");
$th = array(_("Delivered To"));
table_header($th);

label_row(null, $sales_order["deliver_to"] . "<br>" . nl2br($sales_order["delivery_address"]),
	"nowrap");
end_table();

echo "</td><td>"; // outer table

start_table("$table_style width=100%");
start_row();
label_cells(_("DR No."), $myrow["reference"], "class='tableheader2'");
label_cells(_("Currency"), $sales_order["curr_code"], "class='tableheader2'");
/*label_cells(_("Our Order No"),
	get_customer_trans_view_str(ST_SALESORDER,$sales_order["order_no"]), "class='tableheader2'");*/
$so_ref = getSORef($sales_order["order_no"]);
$view_so_ref = '';
if ($so_ref != 'auto')
	$view_so_ref = get_customer_trans_view_str(ST_SALESORDER,$sales_order["order_no"], $so_ref);

label_cells(_("Our Order No"),	$view_so_ref, "class='tableheader2'");

end_row();
start_row();
label_cells(_("Customer Order Ref."), $sales_order["customer_ref"], "class='tableheader2'");
label_cells(_("Shipping Company"), $myrow["shipper_name"], "class='tableheader2'");
label_cells(_("Sales Type"), $myrow["sales_type"], "class='tableheader2'");
end_row();
start_row();
label_cells(_("Payment Terms"), get_term_name($sales_order["payment_terms"]), "class='tableheader2'");
label_cells(_("Dispatch Date"), sql2date($myrow["tran_date"]), "class='tableheader2'", "nowrap");
label_cells(_("Due Date"), sql2date($myrow["due_date"]), "class='tableheader2'", "nowrap");
end_row();
comments_display_row(ST_CUSTDELIVERY, $trans_id);
end_table();

echo "</td></tr>";
end_table(1); // outer table


$result = get_customer_trans_details(ST_CUSTDELIVERY, $trans_id);

start_table("$table_style width=95%");

if (db_num_rows($result) > 0)
{
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
		_("Unit"), _("Price"), _("Discount %"), /*_("Discount2 %"), _("Discount3 %"), 
		_("Discount4 %"), _("Discount5 %"), _("Discount6 %"),*/ _("Notes"), _("Total"));
	table_header($th);

	$k = 0;	//row colour counter
	$sub_total = 0;
	$vatable = $nonvat = $zerorated = 0;
	while ($myrow2 = db_fetch($result))
	{
		if($myrow2['quantity']==0) continue;
		alt_table_row_color($k);

		$value = round2(((1 - $myrow2["discount_percent"]) *(1 - $myrow2["discount_percent2"]) *(1 - $myrow2["discount_percent3"]) *(1 - $myrow2["discount_percent4"]) *(1 - $myrow2["discount_percent5"]) *(1 - $myrow2["discount_percent6"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
		   user_price_dec());
		//$value = $myrow2["standard_cost"] * $myrow2["quantity"] * (1-$myrow2["discount_percent"]) * (1-$myrow2["discount_percent2"]) * (1-$myrow2["discount_percent3"]);
		// display_error((1-$myrow2["discount_percent"]));
		$sub_total += $value;
		
		/////////////////////////////////////////////////////////////////
			
		$sql2 = "SELECT tax_type_id FROM 0_stock_master WHERE stock_id IN ( ".db_escape($myrow2["stock_id"])." )";
		$result2 = db_query($sql2,"could not retrieve tax_type_id");
		$row2 = db_fetch_row($result2);		

		if($row2[0] == 1)
			$vatable += $value;
		else if($row2[0] == 2)
			$nonvat += $value;
		else
			$zerorated += $value;

		/////////////////////////////////////////////////////////////////

	    if ($myrow2["discount_percent"] == 0)
	    {
		  	$display_discount = "";
	    }
	    else
	    {
		  	$display_discount = percent_format($myrow2["discount_percent"]*100) . "%";
	    }
		
		// if ($myrow2["discount_percent2"] == 0)
	    // {
		  	// $display_discount2 = "";
	    // }
	    // else
	    // {
		  	// $display_discount2 = percent_format($myrow2["discount_percent2"]*100) . "%";
	    // }
		
		// if ($myrow2["discount_percent3"] == 0)
	    // {
		  	// $display_discount3 = "";
	    // }
	    // else
	    // {
		  	// $display_discount3 = percent_format($myrow2["discount_percent3"]*100) . "%";
	    // }
		
		// if ($myrow2["discount_percent4"] == 0)
	    // {
		  	// $display_discount4 = "";
	    // }
	    // else
	    // {
		  	// $display_discount4 = percent_format($myrow2["discount_percent4"]*100) . "%";
	    // }
		
		// if ($myrow2["discount_percent5"] == 0)
	    // {
		  	// $display_discount5 = "";
	    // }
	    // else
	    // {
		  	// $display_discount5 = percent_format($myrow2["discount_percent5"]*100) . "%";
	    // }
		
		// if ($myrow2["discount_percent6"] == 0)
	    // {
		  	// $display_discount6 = "";
	    // }
	    // else
	    // {
		  	// $display_discount6 = percent_format($myrow2["discount_percent6"]*100) . "%";
	    // }

		label_cell($myrow2["stock_id"]);
		label_cell($myrow2["StockDescription"]);
        qty_cell($myrow2["quantity"], false, get_qty_dec($myrow2["stock_id"]));
        label_cell($myrow2["units"], "align=right");
        amount_cell($myrow2["unit_price"]);
        label_cell($display_discount, "nowrap align=right");
        // label_cell($display_discount2, "nowrap align=right");
        // label_cell($display_discount3, "nowrap align=right");
        // label_cell($display_discount4, "nowrap align=right");
        // label_cell($display_discount5, "nowrap align=right");
        // label_cell($display_discount6, "nowrap align=right");
        label_cell($myrow2['comment']);
        amount_cell($value);
	end_row();
	} //end while there are line items to print out

}
else
	display_note(_("There are no line items on this dispatch."), 1, 2);

$display_sub_tot = price_format($sub_total);
$display_freight = price_format($myrow["ov_freight"]);

$_tax_items = get_trans_tax_details(ST_CUSTDELIVERY, $trans_id);
$_total_tax = display_customer_trans_tax_details_16($_tax_items, 10, 0);

/*Print out the delivery note text entered */
label_row(_("Total Sales"), price_format($sub_total), "colspan=7 align=right", "nowrap align=right width=15%");

// label_row(_("VATABLE Sales"), price_format($sub_total-$_total_tax), "colspan=12 align=right", "nowrap align=right width=15%");
// label_row(_("NON-VATABLE Sales"), price_format(0), "colspan=12 align=right", "nowrap align=right width=15%");
// label_row(_("ZERO RATED Sales"), price_format(0), "colspan=12 align=right", "nowrap align=right width=15%");


//////////////////////////////////////////////////////////////////////////////
$sql1 = "SELECT tax_group_id FROM 0_cust_branch WHERE branch_code = ".db_escape($myrow["branch_code"]);
$result1 = db_query($sql1,"could not retrieve tax_type_id");
$row1 = db_fetch_row($result1);

if($row1[0] == 1)
{
	label_row(_("VATABLE Sales"), price_format($vatable/1.12), "colspan=7 align=right", "nowrap align=right width=15%");
	label_row(_("NON-VATABLE Sales"), price_format($nonvat), "colspan=7 align=right", "nowrap align=right width=15%");
	label_row(_("ZERO RATED Sales"), price_format($zerorated), "colspan=7 align=right", "nowrap align=right width=15%");
}
else if($row1[0] == 2)
{
	label_row(_("VATABLE Sales"), price_format(0), "colspan=7 align=right", "nowrap align=right width=15%");
	label_row(_("NON-VATABLE Sales"), price_format($vatable+$nonvat+$zerorated), "colspan=7 align=right", "nowrap align=right width=15%");
	label_row(_("ZERO RATED Sales"), price_format(0), "colspan=7 align=right", "nowrap align=right width=15%");
}
else 
{
	label_row(_("VATABLE Sales"), price_format(0), "colspan=7 align=right", "nowrap align=right width=15%");
	label_row(_("NON-VATABLE Sales"), price_format($nonvat), "colspan=7 align=right", "nowrap align=right width=15%");
	label_row(_("ZERO RATED Sales"), price_format($vatable+$zerorated), "colspan=7 align=right", "nowrap align=right width=15%");
}
//////////////////////////////////////////////////////////////////////////////


$tax_items = get_trans_tax_details(ST_CUSTDELIVERY, $trans_id);
display_customer_trans_tax_details($tax_items, 7);

	
label_row(_("Shipping"), $display_freight, "colspan=7 align=right", "nowrap align=right");



$display_total = price_format($myrow["ov_amount"] +$myrow["ov_freight"]+$myrow["ov_freight_tax"]+$myrow["ov_gst"]);

label_row(_("Amount Total"), $display_total, "colspan=7 align=right",
	"nowrap align=right");
end_table(1);

is_voided_display(ST_CUSTDELIVERY, $trans_id, _("This dispatch has been voided."));

//end_page(true);
display_note(print_document_link($trans_id, _("&Print Delivery Receipt"), true, ST_CUSTDELIVERY), 0, 1);
echo "<center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

?>