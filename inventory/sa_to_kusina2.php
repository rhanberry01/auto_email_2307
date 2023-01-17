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
$page_security = 'SA_LOCATIONTRANSFER';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/inventory/includes/sa_to_kusina_ui2.inc");
include_once($path_to_root . "/inventory/includes/inventory_db.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Stock Transfer (SA to SRS KUSINA)"), false, false, "", $js);
//-------------------------------------------------------------------------------------------------------------
 // function ping($host, $timeout = 1) {
	// /* ICMP ping packet with a pre-calculated checksum */
	// $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
	// $socket  = socket_create(AF_INET, SOCK_RAW, 1);
	// socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
	// socket_connect($socket, $host, null);

	// $ts = microtime(true);
	// socket_send($socket, $package, strLen($package), 0);
	// if (socket_read($socket, 255))
	// $result = microtime(true) - $ts;
	// else    $result = false;
	// socket_close($socket);

	// return $result;
// } 
//-----------------------------------------------------------------------------------------------

check_db_has_costable_items(_("There are no inventory items defined in the system (Purchased or manufactured items)."));

check_db_has_movement_types(_("There are no inventory movement types defined in the system. Please define at least one inventory adjustment type."));

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_SAKUSINAOUT;

	display_notification_centered(_("Selling area to SRS Kusina transfer has been processed."));
	display_note(get_trans_view_str($trans_type, $trans_no, _("&View this transfer")));

	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another Inventory Transfer"));

	display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global $Ajax;

  $Ajax->activate('items_table');

      	$_POST['qty'] = '';
		$_POST['std_cost']='';
		$_POST['stock_id']='';
		$_POST['item_description']='';
		$_POST['b_code']='';
		$_POST['barcode']='';
		$_POST['units']='';
  
  set_focus('_barcode_edit');
  
}
//-----------------------------------------------------------------------------------------------

function handle_new_order()
{
	if (isset($_SESSION['transfer_items']))
	{
		$_SESSION['transfer_items']->clear_items();
		unset ($_SESSION['transfer_items']);
	}

    //session_register("transfer_items");

	$_SESSION['transfer_items'] = new items_cart(ST_SAKUSINAOUT);
	$_POST['AdjDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
	$_SESSION['transfer_items']->tran_date = $_POST['AdjDate'];	
}

//-----------------------------------------------------------------------------------------------

function can_process()
{
	global $Refs;

	$adj = &$_SESSION['transfer_items'];

	if (count($adj->line_items) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		return false;
	}
	if (!$Refs->is_valid($_POST['ref'])) 
	{
		display_error( _("You must enter a reference."));
		set_focus('ref');
		return false;
	}
		if ($_POST['req_by']=='') 
	{
		display_error(_("You must enter Requested By field."));
		set_focus('req_by');
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

	global $db_connections;
	$myserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["host"], 2);
	$msserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["ms_host"], 2);
//	$mainserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["ms_main_host"], 2);
	if(!$myserver){
		display_error($db_connections[$_SESSION["wa_current_user"]->company]["host"].' FAILED TO CONNECT.');
		return false;
	}elseif(!$msserver){
		display_error($db_connections[$_SESSION["wa_current_user"]->company]["ms_host"].' FAILED TO CONNECT.');
		return false;
	 }
	 
	ms_db_query("BEGIN TRANSACTION");
	begin_transaction();

	$trans_no = transfer_to_resto_k2($_SESSION['transfer_items']->line_items,
	$_POST['FromStockLocation'],$_POST['ToStockLocation'], $_POST['AdjDate'],$_POST['type'],
	$_POST['ref'], $_POST['memo_'],$_POST['BrLocation1'],$_POST['BrLocation2'],$req_by);
	
	set_global_connection_branch($db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"]);

	$trans_no2 = receive_from_resto_k2($_SESSION['transfer_items']->line_items,
	$_POST['FromStockLocation'],$_POST['ToStockLocation'], $_POST['AdjDate'],$_POST['type'],
	$trans_no, $_POST['memo_'],$_POST['BrLocation1'],$_POST['BrLocation2']);

	commit_transaction();
	ms_db_query("COMMIT TRANSACTION");
	
	set_global_connection_branch();
	
	new_doc_date($_POST['AdjDate']);
	
	$_SESSION['transfer_items']->clear_items();
	
	unset($_SESSION['allownegativecost']);
	unset($_SESSION['transfer_items']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
} /*end of process credit note */

//-----------------------------------------------------------------------------------------------
function check_item_data()
{
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
	
	if (!check_num('std_cost', 0))
	{
		display_error(_("The entered standard cost is negative or invalid."));
		set_focus('std_cost');
		return false;
	}
	
	$db_133 = get_branch_db_133_k2($_POST['BrLocation1']);
	$db_133_2 = get_branch_db_133_k2($_POST['BrLocation2']);
	if (!get_branch_stock_id_k2($db_133, $db_133_2, $_POST['stock_id'],$_POST['item_description']))
	{
		return false;
	}
	
   	return true;
}

//-----------------------------------------------------------------------------------------------
function handle_update_item()
{
    if($_POST['UpdateItem'] != "" && check_item_data())
    {
		$id = $_POST['LineNo'];
    	$_SESSION['transfer_items']->update_cart_item($id, input_num('qty'),$_POST['units'], input_num('std_cost'),$_POST['b_code']);
    }
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['transfer_items']->remove_from_cart($id);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------
function handle_new_item()
{
	if (!check_item_data())
	return;

	add_to_order($_SESSION['transfer_items'], $_POST['stock_id'], input_num('qty'), $_POST['units'], input_num('std_cost'), $_POST['item_description'],$_POST['b_code']);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);
	
if (isset($_POST['AddItem']))
	handle_new_item();

if (isset($_POST['UpdateItem']))
	handle_update_item();

if (isset($_POST['CancelItemChanges'])) {
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------

if (isset($_GET['NewTransfer']) || !isset($_SESSION['transfer_items']))
{
	handle_new_order();
}

//-----------------------------------------------------------------------------------------------
start_form();
//display_error('CURRENTLY UPDATING... PLEASE DO NOT USE THIS PAGE FOR A WHILE..');
display_order_header($_SESSION['transfer_items']);

start_table("$table_style width=80%", 10);
start_row();
echo "<td>";
display_transfer_items(_("Items to Transfer"), $_SESSION['transfer_items']);
transfer_options_controls();
echo "</td>";
end_row();
end_table(1);

submit_center_first('Update', _("Update"), '', null);
submit_center_last('Process', _("Process Transfer"), '',  'default');

end_form();
end_page();
?>