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
$page_security = 'SA_SALESPRICE';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Inventory Item Sales prices per Customer"));

include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

//---------------------------------------------------------------------------------------------------

check_db_has_stock_items(_("There are no items defined in the system."));

check_db_has_sales_types(_("There are no sales types in the system. Please set up sales types befor entering pricing."));

simple_page_mode(true);
//---------------------------------------------------------------------------------------------------
$input_error = 0;

if (isset($_GET['stock_id']))
{
	$_POST['stock_id'] = $_GET['stock_id'];
}
if (isset($_GET['Item']))
{
	$_POST['stock_id'] = $_GET['Item'];
}

if (!isset($_POST['curr_abrev']))
{
	$_POST['curr_abrev'] = get_company_currency();
}

//---------------------------------------------------------------------------------------------------

start_form();

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

echo "<center>" . _("Item:"). "&nbsp;";
echo sales_items_list('stock_id', $_POST['stock_id'], false, true);
echo "<hr></center>";

set_global_stock_item($_POST['stock_id']);

//----------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	if (!check_num('price', 0))
	{
		$input_error = 1;
		display_error( _("The price entered must be numeric."));
		set_focus('price');
	}

	if ($input_error != 1)
	{

    	if ($selected_id != -1) 
		{
			//editing an existing price
			update_item_price_per_customer($selected_id, $_POST['sales_type_id'],
			$_POST['curr_abrev'], input_num('price'), $_POST['debtor_no']);

			$msg = _("This price has been updated.");
		}
		else
		{

			add_item_price_per_customer($_POST['stock_id'], $_POST['sales_type_id'],
			    $_POST['curr_abrev'], input_num('price'), $_POST['debtor_no']);

			$msg = _("The new price has been added.");
		}
		display_notification($msg);
		$Mode = 'RESET';
	}

}

//------------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	//the link to delete a selected record was clicked
	delete_item_price_per_customer($selected_id);
	display_notification(_("The selected price has been deleted."));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
}

if (list_updated('stock_id')) {
	$Ajax->activate('price_table');
	$Ajax->activate('price_details');
}
if (list_updated('stock_id') || isset($_POST['_curr_abrev_update']) || isset($_POST['_sales_type_id_update'])) {
	// after change of stock, currency or salestype selector
	// display default calculated price for new settings. 
	// If we have this price already in db it is overwritten later.
	unset($_POST['price']);
	$Ajax->activate('price_details');
}

//---------------------------------------------------------------------------------------------------

$prices_list = get_prices_per_customer($_POST['stock_id']);

div_start('price_table');
start_table("$table_style width=30%");

$th = array(_("Customer"), _("Currency"), _("Sales Type"), _("Price"), "", "");
table_header($th);
$k = 0; //row colour counter
$calculated = false;
while ($myrow = db_fetch($prices_list))
{

	alt_table_row_color($k);

	label_cell(get_customer_name($myrow["debtor_no"]));
	label_cell($myrow["curr_abrev"]);
    label_cell($myrow["sales_type"]);
    amount_cell($myrow["price"]);
 	edit_button_cell("Edit".$myrow['id'], _("Edit"));
 	delete_button_cell("Delete".$myrow['id'], _("Delete"));
    end_row();

}
end_table();
if (db_num_rows($prices_list) == 0)
{
	if (get_company_pref('add_pct') != -1)
		$calculated = true;
	display_note(_("There are no prices set up for this part."), 1);
}
div_end();
//------------------------------------------------------------------------------------------------

echo "<br>";

if ($Mode == 'Edit')
{
	$myrow = get_stock_price_per_customer($selected_id);
	$_POST['curr_abrev'] = $myrow["curr_abrev"];
	$_POST['sales_type_id'] = $myrow["sales_type_id"];
	$_POST['price'] = price_format($myrow["price"]);
	$_POST['debtor_no'] = price_format($myrow["debtor_no"]);
}

hidden('selected_id', $selected_id);
div_start('price_details');
start_table($table_style2);

customer_list_row(_("Customer:"), 'debtor_no');

currencies_list_row(_("Currency:"), 'curr_abrev', null, true);

sales_types_list_row(_("Sales Type:"), 'sales_type_id', null, true);

if (!isset($_POST['price'])) {
	$_POST['price'] = price_format(get_kit_price(get_post('stock_id'), 
		get_post('curr_abrev'),	get_post('sales_type_id')));
}

$kit = get_item_code_dflts($_POST['stock_id']);
small_amount_row(_("Price:"), 'price', null, '', _('per') .' '.$kit["units"]);

end_table(1);
if ($calculated)
	display_note(_("The price is calculated."), 0, 1);

submit_add_or_update_center($selected_id == -1, '', 'both');
div_end();

end_form();
end_page();
?>
