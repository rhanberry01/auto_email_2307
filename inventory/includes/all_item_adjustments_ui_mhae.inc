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
		// if ($order->check_cart_item($barcode)) {
			// display_error(_("For Item :") . $barcode . ", " . "This is already included on this batch of movement.  You can change the quantity of this existing line if necessary.");
		// }
		// else {
			$order->add_to_cart (count($order->line_items), $new_item, $new_item_qty, $new_item_uom, $standard_cost,$description,$barcode);
		// }
	}
	
	//--------------------------------------------------------------------------------
	function display_order_header(&$order,$get_trans_no)
	{
		global $Ajax, $table_style2, $Refs;
		
		if($get_trans_no) {
			$gl_type=ST_INVADJUST;
			$sql = "SELECT * from ".TB_PREF."adjustment_header WHERE a_type='$gl_type' AND a_trans_no = '".$get_trans_no."'";
			$res = db_query($sql);
			//display_error($sql);
			
			while($row=mysql_fetch_array($res)) {
				$_POST['ref']=$_POST['i_ref']=$row['a_ref'];
				$_POST['AdjDate'] = sql2date($row['a_date_created']);
				$_POST['status_type']=$row['a_status'];
				$_POST['type']=$row['a_movement_code'];
			}
			if ($_POST['status_type']=='1')
			{
				$_POST['status_type']='0';
			}
			else {
				$_POST['status_type']='1';
			}
			
			$Ajax->activate('ref');
			$Ajax->activate('i_ref');
			//$Ajax->activate('AdjDate');
			$Ajax->activate('status_type');
			$Ajax->activate('type');
		}
		
		//IF GET trans_no
		if($get_trans_no) {
			start_outer_table("width=75% $table_style2"); // outer table
			table_section(1);
			//ref_row(_("Reference:"), 'ref', '', $Refs->get_next(ST_INVADJUST));
			label_row("Transaction No:"," <b>".$_POST['ref']."</b>",'','','','i_ref');
			hidden('ref', $_POST['ref']);
			date_row(_("Adjustment Date :"), 'AdjDate', '', true);
			table_section(2);
			$sql = "SELECT id FROM ".TB_PREF."movement_types WHERE movement_code=".db_escape($_POST['type']);
			$result = db_query($sql);
			$row=db_fetch($result);
			
			adjustment_types_list_row(_("Movement Type:"), 'type', $row['id']);
			//label_row("Movement Type:"," <b> DELIVER TO BRANCH</b>");
			//movement_types_list_row(_("Movement Type:"), 'type', null);
			hidden('get_trans_no', $_GET['trans_no']);
			//yesno_list_row(_("Status Type:"), 'status_type', $_POST['status_type'],_("Posted"), _("Open"));
			
			label_row("Status Type:"," <b>Open/For Approval</b>");
			hidden('status_type', 0);
			
			end_outer_table(1); // outer table
		}
		
		else {
			//CREATE NEW
			start_outer_table("width=75% $table_style2"); // outer table
			table_section(1);
			$x=get_next_trans_no(ST_INVADJUST);
			hidden('ref', $x);
			//ref_row(_("Reference:"), 'ref', '', $Refs->get_next(ST_INVADJUST));
			label_row("Transaction No:"," <b>$x</b>");
			date_row(_("Adjustment Date :"), 'AdjDate', '', true);
			
			table_section(2);
			adjustment_types_list_row(_("Movement Type:"), 'type', null);
			//yesno_list_row(_("Status Type:"), 'status_type', '',_("Posted"), _("Open"));
			label_row("Status Type:"," <b>Open/For Approval</b>");
			hidden('status_type', 0);
			end_outer_table(1);
		}
	}
	
	//---------------------------------------------------------------------------------
	
	function display_adjustment_items($title, &$order)
	{
		global $table_style, $path_to_root;
		
		display_heading($title);
		div_start('items_table');
		start_table("$table_style width=100%");
		$th = array(_("Item Code"), _("Item Description"), _("Quantity"),_("Unit"), _("Unit Cost"), _("Total"), "");
		if ( count($order->line_items)) $th[] = '';
		
		table_header($th);
		$total = 0;
		$k = 0;  //row colour counter
		
		$id = find_submit('Edit');
		foreach ($order->line_items as $line_no=>$stock_item)
		{
			//display_error($stock_item->item_description);
			//display_error($stock_item->units);
			
			$total += ($stock_item->standard_cost * $stock_item->quantity);
			$t_qty+=$stock_item->quantity;
			
			if ($id != $line_no)
			{
				alt_table_row_color($k);
				label_cell($stock_item->barcode);
				label_cell($stock_item->item_description);
				qty_cell($stock_item->quantity, false, 4);
				label_cell($stock_item->units);
				
				amount_decimal_cell($stock_item->standard_cost);
				label_cell(number_format2($stock_item->standard_cost * $stock_item->quantity,4), "align=right");
				
				edit_button_cell("Edit$line_no", _("Edit"),
				_('Edit document line'));
				delete_button_cell("Delete$line_no", _("Delete"),
				_('Remove line from document'));
				end_row();
			}
			else
			{
				adjustment_edit_item_controls($order, $line_no);
			}
		}
		
		if ($id == -1)
		adjustment_edit_item_controls($order);
		
		start_row();
		label_cell('Total:',"align=right colspan=2");
		qty_cell($t_qty, false, 4);
		//label_cell($t_qty,"align=right");
		label_cell('');
		label_cell('');
		label_cell(number_format2($total,4), "align=right");
		label_cell('',"colspan=2");
		end_row();
		//label_row(_("Total"), number_format2($total,user_price_dec()), "align=right colspan=4", "align=right", 2);
		
		end_table();
		div_end();
	}
	
	//---------------------------------------------------------------------------------
	
	function adjustment_edit_item_controls(&$order, $line_no=-1)
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
			hidden('b_code', $_POST['b_code']);
			label_cell($_POST['b_code']);
			label_cell($order->line_items[$id]->item_description, 'nowrap');
			$Ajax->activate('items_table');
		}
		else
		{
			// ms_stock_items_list_cells2(null, 'barcode', null, false, true);
			ms_stock_items_list_transfer_cells(null, 'barcode', null, false, true);
		}
		
		// display_error($_POST['b_code']);
		// list_updated('units');
		
		if (list_updated('barcode') OR (($_POST['barcode'] != '' OR $_POST['b_code'] != '') AND list_updated('units')))
		{
			global $Ajax;
			
			// if ($_POST['barcode']) 
			// {
				
				$bar_code = $_POST['barcode'];
				
				if ($_POST['b_code'] != '')
					$bar_code = $_POST['b_code'];
				
				$possql = "SELECT Barcode,ProductID, [Description], uom, qty as pack 
				FROM POS_Products WHERE [Barcode]=".db_escape($bar_code)."";	
				// display_error($possql);	
				$result=ms_db_query($possql);
				while ($row2=mssql_fetch_array($result)) {
					$ms_pos_id=$row2['ProductID'];
					$ms_pos_item_name=$row2['Description'];
					$ms_pos_barcode=$row2['Barcode'];
					$ms_pos_uom=$row2['uom'];
					$ms_pos_pack=$row2['pack'];
				}
				
				$sql="SELECT [ProductID],[CostOfSales] from Products WHERE [ProductID]=".db_escape($ms_pos_id)."";
				//display_error($sql);	
				$res=ms_db_query($sql);
				while ($row=mssql_fetch_array($res)) {
					$ms_totalcost=$row['CostOfSales'];
				}
				
				$_POST['stock_id']=$ms_pos_id;
				$_POST['item_description']=$ms_pos_item_name;
				$_POST['b_code']=$ms_pos_barcode;
				// $_POST['pack']=$ms_pos_pack;
			// }
			
			if (list_updated('units'))
			{
					$ms_pos_pack = get_adj_qty_multiplier($_POST['units']);
					$_POST['std_cost']=$ms_totalcost * $ms_pos_pack;
					$ms_pos_uom = $_POST['units'];
			}
			
			$_POST['std_cost']=$ms_totalcost * $ms_pos_pack;
			$_POST['units']=$ms_pos_uom;
			$Ajax->activate('units');
			$Ajax->activate('stock_id');
			$Ajax->activate('std_cost2');
			$Ajax->activate('std_cost');
			$Ajax->activate('pack');
			$Ajax->activate('item_description');
			$Ajax->activate('b_code');
		}
		
		$keyups="onkeyup=\"if(event.keyCode==13) {try{this.value=get_amount(this.name);document.getElementById('AddItem').click();}catch(h){document.forms[0].UpdateItem.click();}}\"";
		
		qty_cells(null, 'qty', $_POST['qty'], null, null, 4,$keyups);
		
		// if ($line_no != -1 && $line_no == $id)
			// get_ms_uom_list_cells('','units',$_POST['units'],true,false);
		// else
			get_ms_uom_list_cells('','units',$_POST['units'],true,true);
		// label_cell($_POST['std_cost'], '', 'std_cost2');
		amount_cells('', 'std_cost', null,null,null, 4);
		label_cell("&nbsp;");
		// hidden('std_cost', $_POST['std_cost']);
		// hidden('pack', $_POST['pack']);
		hidden('stock_id', $_POST['stock_id']);
		hidden('item_description',$_POST['item_description']);
		// hidden('b_code',$_POST['b_code']);
		
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
	function adjustment_options_controls($get_trans_no)
	{
		//display_error($get_trans_no);
		echo "<br>";
		start_table();
		if ($get_trans_no) {
			$comment=get_comments_string(17, $get_trans_no);
			//display_error($comment);
			textarea_row(_("Memo"), 'memo_', $comment, 50, 3);
		}
		else {
			textarea_row(_("Memo"), 'memo_', null, 50, 3);
		}
		
		
		end_table(1);
	}
	//--------------------------------------------------------------------------------
?>