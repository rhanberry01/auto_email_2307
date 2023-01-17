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
$path_to_root="../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
$js .= "
	function openThickBox(id,typex,txt){
		url = '../../sales/customer_del_so.php?OrderNumber=' + id + '&view=2&type='+typex+'&KeepThis=true&TB_iframe=true&height=280&width=300';
		tb_show('Void '+txt, url);
	}
";
	
page('Approved Purchase Orders Inquiry', false, false, "", $js);

if (isset($_GET['order_number']))
{
	$order_number = $_GET['order_number'];
}

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';

	$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	$Ajax->addDisable(true, 'OrdersToDate', $disable);
	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	$Ajax->addDisable(true, 'SelectStockFromList', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'order_number');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}
//---------------------------------------------------------------------------------------------

start_form();

// start_table("class='tablestyle_noborder'");
echo '<center>';
// start_row();
ref_cells(_("PO #:"), 'order_number', '',null, '', true);
supplier_list_cells('Supplier: ','supp_id',null,true);

date_cells(_("from:"), 'OrdersAfterDate', '', null, -30);
date_cells(_("to:"), 'OrdersToDate');

echo '<br>';
locations_list_cells(_("into location:"), 'StockLocation', null, true);

stock_items_list_cells(_("for item:"), 'SelectStockFromList', null, true);

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
echo '</center>';
// end_row();
// end_table();
//---------------------------------------------------------------------------------------------
if (isset($_POST['order_number']))
{
	$order_number = $_POST['order_number'];
}

if (isset($_POST['SelectStockFromList']) &&	($_POST['SelectStockFromList'] != "") &&
	($_POST['SelectStockFromList'] != ALL_TEXT))
{
 	$selected_stock_item = $_POST['SelectStockFromList'];
}
else
{
	unset($selected_stock_item);
}

//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return get_trans_view_str(ST_PURCHORDER, $trans["order_no"], $trans["reference"]);
}

function check_voided(&$row)
{
	$sql = "SELECT memo_
				FROM 0_voided
				WHERE type = 18
				AND id = ".$row['order_no'];
				//echo $sql.'<p>';
	$query = db_query($sql);
	$count = db_num_rows($query);
	//echo $count.'<p>';
	return $count;
}

function edit_link($row) 
{	
	$count = check_voided($row);
	$received = po_received($row['order_no']);
	
	if($count > 0){
		return '';
	}else if($received > 0){
		return '';
	}else{
		return pager_link( _("Edit"),
		"/purchasing/po_entry_items.php?" . SID 
		. "ModifyOrderNumber=" . $row["order_no"], ICON_EDIT);
	}
}

function prt_link($row)
{
	if (get_voided_entry(ST_PURCHORDER, $row['order_no']) === false)
	return print_document_link($row['order_no'], _("Print"), true, 18, ICON_PRINT);
}

function po_void_link($row)
{
	global $systypes_array;
	if(po_received($row['order_no'])){
	}else{
		if (get_voided_entry(ST_PURCHORDER, $row['order_no']) === false)
		return "<img style='cursor:pointer' title='Void PO' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['order_no'].",".ST_PURCHORDER.",\"".$systypes_array[ST_PURCHORDER]."\")'>"; 
	}
}

//---------------------------------------------------------------------------------------------

$sql = "SELECT 
	porder.order_no, 
	supplier.supp_name, 
	location.location_name,
	porder.requisition_no, 
	porder.ord_date, 
	supplier.curr_code, 
	( Sum(line.extended)) AS OrderValue,
	porder.reference, 
	porder.into_stock_location
	FROM ".TB_PREF."purch_orders as porder, "
		.TB_PREF."purch_order_details as line, "
		.TB_PREF."suppliers as supplier, "
		.TB_PREF."locations as location
	WHERE porder.order_no = line.order_no
	AND porder.supplier_id = supplier.supplier_id
	AND porder.is_approve = 1
	AND location.loc_code = porder.into_stock_location ";

if (isset($order_number) && $order_number != "")
{
	$sql .= "AND porder.reference LIKE ".db_escape('%'. $order_number . '%');
}
else
{

	$data_after = date2sql($_POST['OrdersAfterDate']);
	$date_before = date2sql($_POST['OrdersToDate']);

	$sql .= " AND porder.ord_date >= '$data_after'";
	$sql .= " AND porder.ord_date <= '$date_before'";

	if ($_POST['supp_id'] != '')
	{
		$sql .= " AND porder.supplier_id = ". $_POST['supp_id'];
	}
	
	if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT)
	{
		$sql .= " AND porder.into_stock_location = ".db_escape($_POST['StockLocation']);
	}
	if (isset($selected_stock_item))
	{
		$sql .= " AND line.item_code=".db_escape($selected_stock_item);
	}

} //end not order number selected

$sql .= " GROUP BY porder.order_no";

$cols = array(
		_("PO #") => array('fun'=>'trans_view', 'ord'=>''), 
		_("Supplier") => array('ord'=>''),
		_("Location"),
		_("Supplier's Reference"), 
		_("Order Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		_("Currency") => array('align'=>'center'), 
		_("Order Total") => 'amount',
		// array('insert'=>true, 'fun'=>'edit_link'),
		array('insert'=>true, 'fun'=>'prt_link'),
		// array('insert'=>true, 'fun'=>'po_void_link')
);

if (get_post('StockLocation') != $all_items) {
	$cols[_("Location")] = 'skip';
}
//---------------------------------------------------------------------------------------------------

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

end_form();
end_page();
?>
