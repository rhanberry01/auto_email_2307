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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$page_security = 'SA_LOCATIONTRANSFER';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/item_transformation_ui_special.inc");
include_once($path_to_root . "/inventory/includes/finished_goods_ui_special.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
//include_once($path_to_root . "/inventory/includes/item_transformation_db_special.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Movements (Special Transformation)"), false, false, "", $js);

//-----------------------------------------------------------------------------------------------

check_db_has_costable_items(_("There are no inventory items defined in the system (Purchased or manufactured items)."));

check_db_has_movement_types(_("There are no inventory movement types defined in the system. Please define at least one inventory adjustment type."));

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_ITEM_TRANSFORMATION;

	display_notification_centered(_("Item Transformation has been processed"));
	display_note(get_trans_view_str($trans_type, $trans_no, _("&View this Transformation")));

	hyperlink_no_params($_SERVER['PHP_SELF'], _("Process &Another Item Transformation"));

	display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global $Ajax;
  
  $Ajax->activate('items_table');
  $Ajax->activate('items_table2');
    	$_POST['qty'] = '';
		$_POST['std_cost']='';
		$_POST['stock_id']='';
		$_POST['item_description']='';
		$_POST['b_code']='';
		$_POST['barcode']='';
		$_POST['units']='';
		$_POST['std_cost2']='';
  
  set_focus('_barcode_edit');
}

function line_start_focus2() {
  global $Ajax;

  $Ajax->activate('items_table2');
  set_focus('_barcode_edit');
}
//-----------------------------------------------------------------------------------------------

function handle_new_order()
{
	if (isset($_SESSION['transform_items']))
	{
		$_SESSION['transform_items']->clear_items();
		unset ($_SESSION['transform_items']);
	}

    //session_register("transform_items");

	$_SESSION['transform_items'] = new items_cart(ST_ITEM_TRANSFORMATION);
	$_POST['AdjDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
	$_SESSION['transform_items']->tran_date = $_POST['AdjDate'];	
}

function handle_new_order2()
{
	if (isset($_SESSION['finished_goods']))
	{
		$_SESSION['finished_goods']->clear_items();
		unset ($_SESSION['finished_goods']);
	}

    //session_register("finished_goods");

	$_SESSION['finished_goods'] = new items_cart(ST_ITEM_TRANSFORMATION);
	$_POST['AdjDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
	$_SESSION['finished_goods']->tran_date = $_POST['AdjDate'];	
}

//-----------------------------------------------------------------------------------------------

function can_process()
{
	global $Refs;

	$adj = &$_SESSION['transform_items'];
	$adj2 = &$_SESSION['finished_goods'];

	if (count($adj->line_items) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		return false;
	}
	
	if (count($adj2->line_items) == 0)	{
		display_error(_("You must enter Finished Goods."));
		set_focus('stock_id');
		return false;
	}
	
	if (!$Refs->is_valid($_POST['ref'])) 
	{
		display_error( _("You must enter a reference."));
		set_focus('ref');
		return false;
	}
	
	if ($_POST['type']=='') 
	{
		display_error(_("You must select Movement type."));
		set_focus('type');
		return false;
	} 
	
		if ($_POST['memo_']=='') 
	{
		display_error(_("You must Enter a Memo."));
		set_focus('memo_');
		return false;
	} 
	
	if (!is_date($_POST['AdjDate'])) 
	{
		display_error(_("The entered date for the adjustment is invalid."));
		set_focus('AdjDate');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['AdjDate'])) 
	{
		display_error(_("The entered date is not in fiscal year."));
		set_focus('AdjDate');
		return false;
	} else {
		
		if($_SESSION['allownegativecost']==1)
			return true;
	}
	return true;
}

//-------------------------------------------------------------------------------

if (isset($_POST['Process']) && can_process()){

	$checking = checking_materials($_SESSION['transform_items']->line_items, $_SESSION['finished_goods']->line_items);
	if($checking != 1){
		display_error($checking);
	}else{
		ms_db_query("BEGIN TRANSACTION");
		$trans_no = deduct_raw_materials_special($_SESSION['transform_items']->line_items,
			$_POST['FromStockLocation'],$_POST['ToStockLocation'], $_POST['AdjDate'],$_POST['type'],
			$_POST['ref'], $_POST['memo_']);
		
		$trans_no = add_finished_goods_special($_SESSION['finished_goods']->line_items,
		$_POST['FromStockLocation'],$_POST['ToStockLocation'], $_POST['AdjDate'],$_POST['type'],
		$trans_no, $_POST['memo_']);
		ms_db_query("COMMIT TRANSACTION");
		
		new_doc_date($_POST['AdjDate']);
		
		$_SESSION['transform_items']->clear_items();
		$_SESSION['finished_goods']->clear_items();
		
		unset($_SESSION['allownegativecost']);
		unset($_SESSION['transform_items']);
		unset($_SESSION['finished_goods']);
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no"); 
	} 
	/*end of process credit note */
}
	

//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	$unit_qty_multiplier=get_adj_qty_multiplier4($_POST['units']);
	$inserted_qty=$_POST['qty']*$unit_qty_multiplier;
	//display_error($inserted_qty);

		$possql = "SELECT ProductID FROM POS_Products WHERE [Barcode]=".db_escape($_POST['b_code'])."";	
		$result=ms_db_query($possql);
		//display_error($possql);
		while ($row2=mssql_fetch_array($result)) {
		$ms_pos_id=$row2['ProductID'];
		}

		$sql="SELECT [SellingArea] from Products WHERE [ProductID]=".db_escape($ms_pos_id)."";
		//display_error($sql);	
		$res=ms_db_query($sql);
		while ($row=mssql_fetch_array($res)) {
		$beginning_qty=$row['SellingArea'];
		 }
		 
		//display_error($beginning_qty);
	
	if ($inserted_qty>$beginning_qty)
	{
		display_error($_POST['b_code']." -- ".$_POST['item_description']." is currently negative or will turn negative quantity on the system, Kindly inform Audit Department to proceed scanning of this item.");
		// br();
		// display_error($_POST['b_code']." -- ".$_POST['item_description']." ay kasalukuyang negative o magiging negative ang bilang sa system, Pasuyo po na ipaalam sa Audit or ISD Department para mapagpatuloy ang pag-scan sa item na ito.");
		set_focus('beginning_qty');
		return false;
	}

	if ($_POST['qty']==0)
	{
		display_error(_("The quantity entered is negative or invalid."));
		set_focus('qty');
		return false;
	}
	
	if (!check_value('units') or $_POST['units']=='')
	{
		display_error(_("The units must be entered."));
		set_focus('units');
		return false;
	}
	
	if (!check_num('beginning_qty', 0))
	{
		display_error($_POST['b_code']." -- ".$_POST['item_description']." is currently negative or will turn negative quantity on the system, Kindly inform Audit Department to proceed scanning of this item.");
		// br();
		// display_error($_POST['b_code']." -- ".$_POST['item_description']." ay kasalukuyang negative o magiging negative ang bilang sa system, Pasuyo po na ipaalam sa Audit or ISD Department para mapagpatuloy ang pag-scan sa item na ito.");
		set_focus('beginning_qty');
		return false;
	}
	
	if (!check_num('std_cost', 0))
	{
		display_error(_("The entered standard cost is negative or invalid."));
		set_focus('std_cost');
		return false;
	}

   	return true;
}


function check_item_data2()
{
	$unit_qty_multiplier=get_adj_qty_multiplier4($_POST['units2']);
	$inserted_qty=$_POST['qty2']*$unit_qty_multiplier;
	//display_error($inserted_qty);

		$possql = "SELECT ProductID FROM POS_Products WHERE [Barcode]=".db_escape($_POST['b_code2'])."";	
		$result=ms_db_query($possql);
		//display_error($possql);
		while ($row2=mssql_fetch_array($result)) {
		$ms_pos_id=$row2['ProductID'];
		}

		$sql="SELECT [SellingArea] from Products WHERE [ProductID]=".db_escape($ms_pos_id)."";
		//display_error($sql);	
		$res=ms_db_query($sql);
		while ($row=mssql_fetch_array($res)) {
		$beginning_qty=$row['SellingArea'];
		 }
		 
		//display_error($beginning_qty);
	
	if ($beginning_qty<0)
	{
		display_error($_POST['b_code2']." -- ".$_POST['item_description']." is currently negative or will turn negative quantity on the system, Kindly inform Audit Department to proceed scanning of this item.");
		// br();
		// display_error($_POST['b_code']." -- ".$_POST['item_description']." ay kasalukuyang negative o magiging negative ang bilang sa system, Pasuyo po na ipaalam sa Audit or ISD Department para mapagpatuloy ang pag-scan sa item na ito.");
		set_focus('beginning_qty2');
		return false;
	}

	if ($_POST['qty2']==0)
	{
		display_error(_("The quantity entered is negative or invalid."));
		set_focus('qty2');
		return false;
	}
	
	if (!check_value('units2') or $_POST['units2']=='')
	{
		display_error(_("The units must be entered."));
		set_focus('units2');
		return false;
	}
	
	if (!check_num('beginning_qty2', 0))
	{
		display_error($_POST['b_code2']." -- ".$_POST['item_description']." is currently negative or will turn negative quantity on the system, Kindly inform Audit Department to proceed scanning of this item.");
		// br();
		// display_error($_POST['b_code']." -- ".$_POST['item_description']." ay kasalukuyang negative o magiging negative ang bilang sa system, Pasuyo po na ipaalam sa Audit or ISD Department para mapagpatuloy ang pag-scan sa item na ito.");
		set_focus('beginning_qty2');
		return false;
	}
	
	if (!check_num('std_cost2', 0))
	{
		display_error(_("The entered standard cost is negative or invalid."));
		set_focus('std_cost2');
		return false;
	}

   	return true;
}


// function check_item_data2()
// {
	// if ($_POST['qty2']==0)
	// {
		// display_error(_("The quantity entered is negative or invalid."));
		// set_focus('qty2');
		// return false;
	// }
	
	// if (!check_value('units2') or $_POST['units2']=='')
	// {
		// display_error(_("The units must be entered."));
		// set_focus('units2');
		// return false;
	// }
	
	// // if (!check_num('beginning_qty2', 0))
	// // {
		// // display_error(_("The quantity of this item is negative or invalid."));
		// // set_focus('beginning_qty2');
		// // return false;
	// // }
	
	// if (!check_num('std_cost2', 0))
	// {
		// display_error(_("The entered standard cost is negative or invalid."));
		// set_focus('std_cost2');
		// return false;
	// }
	
	// // if ($_POST['raw_mats_cost']=='')
	// // {
	// // display_error(_("Please insert first the items to transform."));
	// // display_error($_POST['raw_mats_cost']);
	// // set_focus('qty2');
	// // return false;
	// // }

   	// return true;
// }

//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
    if($_POST['UpdateItem'] != "" && check_item_data())
    {
		$id = $_POST['LineNo'];
    	$_SESSION['transform_items']->update_cart_item($id, input_num('qty'),$_POST['units'], input_num('std_cost'),$_POST['b_code']);
    }
	line_start_focus();
}

function handle_update_item2()
{
    if($_POST['UpdateItem2'] != "" && check_item_data2())
    {
		$id2 = $_POST['LineNo2'];
    	$_SESSION['finished_goods']->update_cart_item($id2, input_num('qty2'),$_POST['units2'], input_num('std_cost2'),$_POST['b_code2']);
    }
	line_start_focus2();
}
//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['transform_items']->remove_from_cart($id);
	line_start_focus();
}

function handle_delete_item2($id2)
{
	$_SESSION['finished_goods']->remove_from_cart($id2);
	line_start_focus2();
}
//-----------------------------------------------------------------------------------------------
function handle_new_item()
{
	if (!check_item_data())
		return;

	add_to_order($_SESSION['transform_items'], $_POST['stock_id'], input_num('qty'), $_POST['units'], input_num('std_cost'), $_POST['item_description'],$_POST['b_code']);
	line_start_focus();
}
//---------------------------------------------------------------------------------------------
function handle_new_item2()
{
	if (!check_item_data2())
		return;

	add_to_order2($_SESSION['finished_goods'], $_POST['stock_id2'], input_num('qty2'), $_POST['units2'], input_num('std_cost2'), $_POST['item_description2'],$_POST['b_code2']);
	line_start_focus2();
}
//----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);
	
$id2 = find_submit('Delete2');
if ($id2 != -1)
	handle_delete_item2($id2);
	
if (isset($_POST['AddItem']))
	handle_new_item();
	
if (isset($_POST['AddItem2']))
	handle_new_item2();

if (isset($_POST['UpdateItem']))
	handle_update_item();

if (isset($_POST['UpdateItem2']))
	handle_update_item2();

if (isset($_POST['CancelItemChanges'])) {
	line_start_focus();
}

if (isset($_POST['CancelItemChanges2'])) {
	line_start_focus2();
}
//-----------------------------------------------------------------------------------------------

if (isset($_GET['NewTransformations']) || !isset($_SESSION['transform_items']) || !isset($_SESSION['finished_goods']))
{
	handle_new_order();
	handle_new_order2();
}

//-----------------------------------------------------------------------------------------------
start_form();
display_order_header($_SESSION['transform_items']);

start_table("$table_style width=75%", 10);
start_row();
echo "<td>";
$t=display_transform_items(_("Items to Transform"), $_SESSION['transform_items']);
br(2);
//returning unit and total cost of item to transform
display_transform_items2(_("Finished Goods"), $_SESSION['finished_goods'], $t);

transform_options_controls();
echo "</td>";
end_row();
end_table(1);

submit_center_first('Update', _("Update"), '', null);
submit_center_last('Process', _("Process Transformation"), '',  'default');

end_form();
end_page();
?>