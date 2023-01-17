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

function add_to_order2(&$order, $new_item, $new_item_qty, $new_item_uom, $standard_cost,$description,$barcode)
{
	//display_error($order);
	//display_error($new_item);
	//display_error($new_item_qty);
	//display_error($new_item_uom);
	//display_error($barcode);
	//display_error($description);
	//display_error($standard_cost);
    if ($order->find_cart_item($new_item)) {
    display_error(_("For Item :") . $new_item . ", " . "This is already included on this batch of movement.  You can change the quantity of this existing line if necessary.");
	}
	
	else if (count($order->line_items) >= 1)	{
		display_error(_("You can only enter 1 finished item for every transformation."));
	}
	
	else {
    $order->add_to_cart (count($order->line_items), $new_item, $new_item_qty, $new_item_uom, $standard_cost,$description,$barcode);
	}
}
//---------------------------------------------------------------------------------

function display_transform_items2($title, &$order,$t)
{
	global $table_style, $path_to_root;

	display_heading($title);
    div_start('items_table2');
	start_table("$table_style width=100%");
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),_("Unit"), _("Unit Cost"), _("Total"), "");
	if ( count($order->line_items)) $th[] = '';

	table_header($th);
	$total = 0;
	$k = 0;  //row colour counter

	$id = find_submit('Edit2');
	foreach ($order->line_items as $line_no=>$stock_item)
	{
	//display_error($stock_item->item_description);
	//display_error($stock_item->units);
	
		$multiplier=get_qty_multiplier($stock_item->units);
		$new_unit_cost=$t / ($stock_item->quantity * $multiplier);
		
		
		if ($id != $line_no)
		{
    		alt_table_row_color($k);
			start_row("class='inquirybg'");
			view_stock_status_cell($stock_item->barcode);
			label_cell($stock_item->item_description);
    		qty_cell($stock_item->quantity, false, 4);
			label_cell($stock_item->units);
		
			amount_decimal_cell($new_unit_cost);
			amount_cell($total=$new_unit_cost * ($stock_item->quantity * $multiplier));

			edit_button_cell("Edit2$line_no", _("Edit"),
				_('Edit document line'));
			delete_button_cell("Delete2$line_no", _("Delete"),
				_('Remove line from document'));
			
			end_row();

			//$total += ($t / $stock_item->quantity * $stock_item->quantity * $multiplier);
	}
		else
		{
			transform_edit_item_controls2($order, $line_no,$t);
		}
	}
	

	if ($id == -1)
		transform_edit_item_controls2($order,'',$t);

	label_row(_("Total"), number_format2($total,user_price_dec()), "align=right colspan=5", "align=right", 2);

    end_table();
	div_end();
}
//---------------------------------------------------------------------------------

function transform_edit_item_controls2(&$order, $line_no=-1,$t)
{

global $Ajax;
	start_row();

	$dec2 = 4;
	$id = find_submit('Edit2');
	if ($line_no != -1 && $line_no == $id)
	{
		$_POST['stock_id2'] = $order->line_items[$id]->stock_id;
		$_POST['qty2'] = qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec=4);
		$_POST['std_cost2'] = price_decimal_format($order->line_items[$id]->standard_cost, $dec2);
		$_POST['units2'] = $order->line_items[$id]->units;
		$_POST['item_description2'] = $order->line_items[$id]->item_description;
		$_POST['b_code2'] = $order->line_items[$id]->barcode;

		hidden('std_cost2', $_POST['std_cost2']);
		hidden('stock_id2', $_POST['stock_id2']);
		label_cell($_POST['b_code2']);
		label_cell($order->line_items[$id]->item_description, 'nowrap');
	    $Ajax->activate('items_table2');
	}
	else
	{
		if (count($order->line_items)!=1) //to disable form if it has finished goods already.
		{
    	ms_stock_items_list_cells2(null, 'barcode2', null, false, true);
		}
		
		if (list_updated('barcode2'))
		{
		global $Ajax;
		
		if ($_POST['barcode2']) {
		
		$possql = "SELECT Barcode,ProductID, [Description] FROM POS_Products WHERE [Barcode]=".db_escape($_POST['barcode2'])."";	
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
		
		$_POST['std_cost2']=$t;
		$_POST['beginning_qty2']=$beginning_qty;
		$_POST['qty2'] = number_format2(0, 4);
		$_POST['stock_id2']=$ms_pos_id;
		$_POST['item_description2']=$ms_pos_item_name;
		$_POST['b_code2']=$ms_pos_barcode;

		}
		
		$Ajax->activate('std_cost2');
		$Ajax->activate('stock_id2');
		$Ajax->activate('units2');
		$Ajax->activate('qty2');
		$Ajax->activate('item_description2');
		$Ajax->activate('b_code2');
		$Ajax->activate('beginning_qty2');
		}
	}
	
	$keyups="onkeyup=\"if(event.keyCode==13) {try{this.value=get_amount(this.name);document.getElementById('AddItem2').click();}catch(h){document.forms[0].UpdateItem2.click();}}\"";
	
	if (count($order->line_items)!=1 or ($line_no != -1 && $line_no == $id)) //to disable form if it has finished goods already.
	{
	qty_cells(null, 'qty2', $_POST['qty2'], null, null, 4,$keyups);
	get_ms_uom_list_cells('','units2',$_POST['units2'],true);
	label_cell($_POST['std_cost2'], '', 'std_cost3');
	label_cell("&nbsp;");
	}
	
	hidden('std_cost2', $_POST['std_cost2']);
	hidden('stock_id2', $_POST['stock_id2']);
	hidden('item_description2',$_POST['item_description2']);
	hidden('b_code2',$_POST['b_code2']);
	hidden('beginning_qty2',$_POST['beginning_qty2']);

	
	if ($id != -1)
	{
		button_cell('UpdateItem2', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges2', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo2', $line_no);
 		set_focus('qty2');
	}
	else
	{
	if (count($order->line_items)!=1) //to disable form if it has finished goods already.
	{
		submit_cells('AddItem2', _("Confirm"), "colspan=2",
		    _('Add new item to document'), true);
	}
	}
	end_row();
}
//---------------------------------------------------------------------------------
?>