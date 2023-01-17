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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");

//--------------------------------------------------------------------------------

function add_to_order(&$order, $new_item, $new_item_qty, $new_item_uom, $standard_cost,$description,$barcode)
{
	// display_error($order);
	// display_error($new_item);
	// display_error($new_item_qty);
	// display_error($new_item_uom);
	//display_error($barcode);
	//display_error($description);
						if ($order->find_cart_item($new_item)) {
						display_error(_("For Item :") . $new_item . ", " . "This is already included on this batch of movement.  You can change the quantity of this existing line if necessary.");
						}
						else {
						$order->add_to_cart (count($order->line_items), $new_item, $new_item_qty, $new_item_uom, $standard_cost,$description,$barcode);
						}
}
//--------------------------------------------------------------------------------

function display_order_header(&$order)
{
	global $table_style, $Refs;

	start_outer_table("width=75% $table_style");

	table_section(1);
	// label_row("From Location:"," <b>SELLING AREA</b>");
	// hidden('FromStockLocation', 2);
	// label_row("To Location:"," <b>SRS KUSINA</b>");
	// hidden('ToStockLocation', 1);
	
	//locations_list_row(_("From Location:"), 'FromStockLocation', null);
	label_row("From Location:"," <b>SELLING AREA</b>");
	hidden('FromStockLocation', 1);
	
	//locations_list_row(_("To Location:"), 'ToStockLocation', null);
	label_row("To Location:"," <b>SELLING AREA</b>");
	hidden('ToStockLocation', 1);
	
	table_section(2, "33%");
	$x=$Refs->get_next(ST_ITEM_TRANSFORMATION);
	hidden('ref', $x);
	label_row("Transformation No:"," <b>$x</b>");

    date_row(_("Date:"), 'AdjDate', '', true);

	table_section(3, "33%");

    //movement_types_list_row(_("Transfer Type:"), 'type', null);
	label_row("Movement Type:"," <b>ITEM TRANSFORMATION</b>");
	label_row("Status Type:"," <b> POSTED</b>");
	//hidden('status_type', 2);
	hidden('type', 11);
	end_outer_table(1); // outer table
}
//---------------------------------------------------------------------------------

function display_transform_items($title, &$order)
{
	global $table_style, $path_to_root;

	display_heading($title);
    div_start('items_table');
	start_table("$table_style width=100%");
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),_("Unit"), _("Cost of Sales"), _("Total"), "");
	if ( count($order->line_items)) $th[] = '';

	table_header($th);
	$total = 0;
	$k = 0;  //row colour counter

	$id = find_submit('Edit');
	foreach ($order->line_items as $line_no=>$stock_item)
	{
	//display_error($stock_item->item_description);
	//display_error($stock_item->units);
	
		$multiplier=get_qty_multiplier($stock_item->units);
		$total += ($stock_item->standard_cost * $stock_item->quantity * $multiplier);

		if ($id != $line_no)
		{
    		alt_table_row_color($k);
			view_stock_status_cell($stock_item->barcode);
			label_cell($stock_item->item_description);
    		qty_cell($stock_item->quantity, false, 4);
			label_cell($stock_item->units);
		
			amount_decimal_cell($stock_item->standard_cost);
			amount_cell($stock_item->standard_cost * $stock_item->quantity * $multiplier);

			edit_button_cell("Edit$line_no", _("Edit"),
				_('Edit document line'));
			delete_button_cell("Delete$line_no", _("Delete"),
				_('Remove line from document'));
			end_row();
		}
		else
		{
			transform_edit_item_controls($order, $line_no);
		}
	}

	if ($id == -1)
		transform_edit_item_controls($order);

	label_row(_("Total"), number_format2($total,user_price_dec()), "align=right colspan=5", "align=right", 2);

    end_table();
	div_end();
	//return $total;
	return $total;
}
//---------------------------------------------------------------------------------

function transform_edit_item_controls(&$order, $line_no=-1)
{
global $Ajax;
	start_row();

	$dec2 = 4;
	$id = find_submit('Edit');
	if ($line_no != -1 && $line_no == $id)
	{
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$_POST['qty'] = qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec=4);
		$_POST['std_cost'] = price_decimal_format($order->line_items[$id]->standard_cost, $dec2);
		$_POST['units'] = $order->line_items[$id]->units;
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		$_POST['b_code'] = $order->line_items[$id]->barcode;

		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['b_code']);
		label_cell($order->line_items[$id]->item_description, 'nowrap');
	    $Ajax->activate('items_table');
	}
	else
	{
    	ms_stock_items_list_cells2(null, 'barcode', null, false, true);
		if (list_updated('barcode'))
		{
		global $Ajax;
		
		if ($_POST['barcode']) {
		
		$possql = "SELECT Barcode,ProductID, [Description] FROM POS_Products WHERE [Barcode]=".db_escape($_POST['barcode'])."";	
		//display_error($possql);	
		$result=ms_db_query($possql);
		while ($row2=mssql_fetch_array($result)) {
		$ms_pos_id=$row2['ProductID'];
		$ms_pos_item_name=$row2['Description'];
		$ms_pos_barcode=$row2['Barcode'];
		}
		
		$sql="SELECT [ProductID],[CostOfSales],[SellingArea] from Products WHERE [ProductID]=".db_escape($ms_pos_id)."";
		//display_error($sql);	
		$res=ms_db_query($sql);
		while ($row=mssql_fetch_array($res)) {
		$ms_totalcost=$row['CostOfSales'];
		$beginning_qty=$row['SellingArea'];
		 }
		
		//$_POST['qty'] = number_format2(0, $dec);
		$_POST['qty'] = number_format2(0, 4);
		$_POST['beginning_qty']=$beginning_qty;
		$_POST['std_cost']=$ms_totalcost;
		$_POST['stock_id']=$ms_pos_id;
		$_POST['item_description']=$ms_pos_item_name;
		$_POST['b_code']=$ms_pos_barcode;
		}
		
		$Ajax->activate('stock_id');
		$Ajax->activate('units');
		$Ajax->activate('qty');
		$Ajax->activate('std_cost2');
		$Ajax->activate('std_cost');
		$Ajax->activate('item_description');
		$Ajax->activate('b_code');
		$Ajax->activate('beginning_qty');
		}
	}
	
	$keyups="onkeyup=\"if(event.keyCode==13) {try{this.value=get_amount(this.name);document.getElementById('AddItem').click();}catch(h){document.forms[0].UpdateItem.click();}}\"";
	
	qty_cells(null, 'qty', $_POST['qty'], null, null, 4,$keyups);
	get_ms_uom_list_cells('','units',$_POST['units'],true);
	label_cell($_POST['std_cost'], '', 'std_cost2');
	label_cell("&nbsp;");
	hidden('std_cost', $_POST['std_cost']);
	hidden('stock_id', $_POST['stock_id']);
	hidden('item_description',$_POST['item_description']);
	hidden('b_code',$_POST['b_code']);
	hidden('beginning_qty',$_POST['beginning_qty']);

	if ($id != -1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
 		set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new item to document'), true);
	}

	end_row();
}
//---------------------------------------------------------------------------------

function transform_options_controls()
{
	  echo "<br>";
	  start_table();

	  textarea_row(_("Memo"), 'memo_', null, 50, 3);

	  end_table(1);
}
//---------------------------------------------------------------------------------
?>