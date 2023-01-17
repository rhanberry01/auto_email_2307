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
$page_security = 'SA_PURCHASEPRICING';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();	
page(_($help_context = "Supplier Purchasing Data"),true, false, "", $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/manufacturing.inc");
include_once($path_to_root . "/includes/data_checks.inc");

check_db_has_purchasable_items(_("There are no purchasable inventory items defined in the system."));
check_db_has_suppliers(_("There are no suppliers defined in the system."));

//----------------------------------------------------------------------------------------
simple_page_mode(true);



//--------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM')
{

   	$input_error = 0;
   	if ($_POST['stock_id'] == "" || !isset($_POST['stock_id']))
   	{
      	$input_error = 1;
      	display_error( _("There is no item selected."));
	set_focus('stock_id');
   	}
   	elseif (!check_num('price', 0))
   	{
      	$input_error = 1;
      	display_error( _("The price entered was not numeric."));
	set_focus('price');
   	}
   	elseif (!check_num('conversion_factor'))
   	{
      	$input_error = 1;
      	display_error( _("The conversion factor entered was not numeric. The conversion factor is the number by which the price must be divided by to get the unit price in our unit of measure."));
		set_focus('conversion_factor');
   	}

	if ($input_error == 0)
	{
     	if ($Mode == 'ADD_ITEM') 
       	{

    		$sql = "INSERT INTO ".TB_PREF."purch_data (supplier_id, stock_id, price, suppliers_uom,
    			conversion_factor, supplier_description) VALUES (";
    		$sql .= db_escape($_POST['supplier_id']).", ".db_escape($_POST['stock_id']). ", "
		    	.input_num('price',0) . ", ".db_escape( $_POST['suppliers_uom'] ). ", "
    			.input_num('conversion_factor') . ", "
    			.db_escape($_POST['supplier_description']) . ")";

    		db_query($sql,"The supplier purchasing details could not be added");
    		display_notification(_("This supplier purchasing data has been added."));
       	} else
       	{
          	$sql = "UPDATE ".TB_PREF."purch_data SET price=" . input_num('price',0) . ",
				suppliers_uom=".db_escape($_POST['suppliers_uom']) . ",
				conversion_factor=" . input_num('conversion_factor') . ",
				supplier_description=" . db_escape($_POST['supplier_description']) . "
				WHERE stock_id=".db_escape($_POST['stock_id']) . " AND
				supplier_id=".db_escape($selected_id);
          	db_query($sql,"The supplier purchasing details could not be updated");

    	  	display_notification(_("Supplier purchasing data has been updated."));
       	}
		$Mode = 'RESET';
	}
}

//--------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{

	$sql = "DELETE FROM ".TB_PREF."purch_data WHERE supplier_id=".db_escape($selected_id)."
		AND stock_id=".db_escape($_POST['stock_id']);
	db_query($sql,"could not delete purchasing data");

	display_notification(_("The purchasing data item has been sucessfully deleted."));
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
}

if (isset($_POST['_selected_id_update']) )
{
	$selected_id = $_POST['selected_id'];
	$Ajax->activate('_page_body');
}

if (list_updated('stock_id')) 
{
	$Ajax->activate('price_table');
	$Ajax->activate('h_table');
}

if (isset($_POST['show_history']))
	$Ajax->activate('h_table');
	
//--------------------------------------------------------------------------------------------------

start_form();

if (!isset($_POST['stock_id']))
	$_POST['stock_id'] = get_global_stock_item();

echo "<center>" . _("Item:"). "&nbsp;";
echo stock_purchasable_items_list('stock_id', $_POST['stock_id'], false, true);

echo "<hr></center>";

set_global_stock_item($_POST['stock_id']);

$mb_flag = get_mb_flag($_POST['stock_id']);

$item_uom_multi = get_item_report_uom_and_multiplier($_POST['stock_id']);

if ($mb_flag == -1)
{
	display_error(_("Entered item is not defined. Please re-enter."));
	set_focus('stock_id');
}
else
{

    $sql = "SELECT ".TB_PREF."purch_data.*,".TB_PREF."suppliers.supp_name,"
    	.TB_PREF."suppliers.curr_code
		FROM ".TB_PREF."purch_data INNER JOIN ".TB_PREF."suppliers
		ON ".TB_PREF."purch_data.supplier_id=".TB_PREF."suppliers.supplier_id
		WHERE stock_id = ".db_escape($_POST['stock_id']);

    $result = db_query($sql, "The supplier purchasing details for the selected part could not be retrieved");
  div_start('price_table');
    if (db_num_rows($result) == 0)
    {
    	display_note(_("There is no purchasing data set up for the part selected"));
    }
    else
    {
        start_table("$table_style width=65%");

		$th = array(_("Supplier"), _("Price"), _("Currency"),
			_("Supplier's Unit"), _("# of Pieces"), "", "");

        table_header($th);

        $k = $j = 0; //row colour counter

        while ($myrow = db_fetch($result))
        {
			alt_table_row_color($k);

            label_cell($myrow["supp_name"]);
            amount_decimal_cell($myrow["price"]);
            label_cell($myrow["curr_code"]);
            label_cell($item_uom_multi[0]);
            label_cell($item_uom_multi[1]);
            // qty_cell($myrow['conversion_factor'], false, user_exrate_dec());
            // label_cell($myrow["supplier_description"]);
		 	edit_button_cell("Edit".$myrow['supplier_id'], _("Edit"));
		 	delete_button_cell("Delete".$myrow['supplier_id'], _("Delete"));
            end_row();

            $j++;
            If ($j == 12)
            {
            	$j = 1;
        		table_header($th);
            } //end of page full new headings
        } //end of while loop

        end_table();
    }
 div_end();
}

//-----------------------------------------------------------------------------------------------

$dec2 = 6;
if ($Mode =='Edit')
{

	$sql = "SELECT ".TB_PREF."purch_data.*,".TB_PREF."suppliers.supp_name FROM ".TB_PREF."purch_data
		INNER JOIN ".TB_PREF."suppliers ON ".TB_PREF."purch_data.supplier_id=".TB_PREF."suppliers.supplier_id
		WHERE ".TB_PREF."purch_data.supplier_id=".db_escape($selected_id)."
		AND ".TB_PREF."purch_data.stock_id=".db_escape($_POST['stock_id']);

	$result = db_query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

	$myrow = db_fetch($result);

    $supp_name = $myrow["supp_name"];
    $_POST['price'] = price_decimal_format($myrow["price"], $dec2);
    $_POST['suppliers_uom'] = $myrow["suppliers_uom"];
    $_POST['supplier_description'] = $myrow["supplier_description"];
    $_POST['conversion_factor'] = exrate_format($myrow["conversion_factor"]);
}

br();
hidden('selected_id', $selected_id);
start_table($table_style2);

if ($Mode == 'Edit')
{
	hidden('supplier_id');
	label_row(_("Supplier:"), $supp_name);
}
else
{
	supplier_list_row(_("Supplier:"), 'supplier_id', null, false, true);
	$_POST['price'] = $_POST['suppliers_uom'] = $_POST['conversion_factor'] = $_POST['supplier_description'] = "";
}

amount_row(_("Price:"), 'price', null,'', get_supplier_currency($selected_id), $dec2);

// text_row(_("Suppliers Unit of Measure:"), 'suppliers_uom', null, 50, 51);

label_row('Report UOM:',$item_uom_multi[0]);
label_row('# of Pieces:',$item_uom_multi[1]);

hidden('conversion_factor',1);
// if (!isset($_POST['conversion_factor']) || $_POST['conversion_factor'] == "")
// {
   	// $_POST['conversion_factor'] = exrate_format(1);
// }
// amount_row(_("Conversion Factor (to our UOM):"), 'conversion_factor',
  // exrate_format($_POST['conversion_factor']), null, null, user_exrate_dec() );
  
// text_row(_("Supplier's Code or Description:"), 'supplier_description', null, 50, 51);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

div_start('h_table');
echo '<hr>';
display_heading('Price History for '.get_item_name($_POST['stock_id']));
br();
echo '<center>';
date_cells(_("From:"), 'AfterDate', '', null, -30);
date_cells(_("To:"), 'BeforeDate');
submit('show_history', _("Show History"), true, '', 'default');
echo '</center>';
	
$before_date = date2sql($_POST['BeforeDate']);
$after_date = date2sql($_POST['AfterDate']);

$sql = "SELECT b.supplier_id, b.trans_no, b.reference AS 'apv', b.tran_date AS 'apv_date',
					d.source_invoice_no, d.delivery_date,
					a.description AS 'item',a.i_uom, a.unit_price+a.unit_tax AS 'price'
			FROM 0_supp_invoice_items a, 0_supp_trans b,
				 0_grn_items c, 0_grn_batch d
			WHERE a.supp_trans_no = b.trans_no
			AND a.supp_trans_type = b.type
			AND a.supp_trans_type = 20
			AND a.grn_item_id = c.id
			AND c.grn_batch_id = d.id";

$sql .= " AND d.delivery_date <= '$before_date'
			AND d.delivery_date >= '$after_date'";
$sql .= " AND a.stock_id = ".db_escape($_POST['stock_id']);

$sql .= " ORDER BY supplier_id, apv";
// display_error($sql);
$result = db_query($sql, "failed to get purchase history");

$th = array(_("APV #"),_("APV Date"), _("Invoice #"), _("Delivery Date"), _("UOM"), _("Price"));

$k = 0; //row colour counter

$supplier = 0;

while($row = db_fetch($result))
{
	// for($i=0;$i<=3;$i++)
	// {
		if ($supplier != $row['supplier_id'])
		{
			// if ($supplier != 0)
				// end_table(2);
			br();
			start_table("$table_style width=65%");
			label_cell(get_supplier_name($row['supplier_id']), "colspan=6 class='tableheader2'");
			table_header($th);
			
			// $supplier = $row['supplier_id'];
		}
		alt_table_row_color($k);
		label_cell(get_trans_view_str(20, $row['trans_no']), $row['apv']);
		label_cell(sql2date($row['apv_date']));
		label_cell($row['source_invoice_no']);
		label_cell(sql2date($row['delivery_date']));
		label_cell($row['i_uom']);
		label_cell($row['price'], 'align=right');
		end_row();
	// }
}

br();

div_end();

end_form();
end_page();

?>
