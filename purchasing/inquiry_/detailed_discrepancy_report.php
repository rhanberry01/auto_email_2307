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
include($path_to_root . "/includes/db_pager2.inc");
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
	
page(_($help_context = "Discrepancy Report"), false, false, "", $js);

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

	// $Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	// $Ajax->addDisable(true, 'OrdersToDate', $disable);
	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, 'supp_id', $disable);
	// $Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	// $Ajax->addDisable(true, 'SelectStockFromList', $disable);

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

// date_cells(_("from:"), 'OrdersAfterDate', '', null, -30);
// date_cells(_("to:"), 'OrdersToDate');

// echo '<br>';
locations_list_cells(_("into location:"), 'StockLocation', null, true);

// stock_items_list_cells(_("for item:"), 'SelectStockFromList', null, true);

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');
echo '</center><br>';
// end_row();
// end_table();
//---------------------------------------------------------------------------------------------
if (isset($_POST['order_number']))
{
	$order_number = $_POST['order_number'];
}

// if (isset($_POST['SelectStockFromList']) &&	($_POST['SelectStockFromList'] != "") &&
	// ($_POST['SelectStockFromList'] != ALL_TEXT))
// {
 	// $selected_stock_item = $_POST['SelectStockFromList'];
// }
// else
// {
	// unset($selected_stock_item);
// }

//---------------------------------------------------------------------------------------------
function trans_view($trans)
{
	return get_trans_view_str(ST_PURCHORDER, $trans["order_no"], $trans["reference"]);
}

function supplier_name($row)
{
	return get_supplier_name($row['supplier_id']);
}

function item_name($row)
{
	return get_item_name($row['item_code']);
}

function loc_name($row)
{
	return get_location_name($row['into_stock_location']);
}

function discrepancy_view($row)
{
	;
	return viewer_link('<b>view items</b>', "purchasing/view/discrepancy_po.php?trans_no=".$row['order_no']);
}

function get_total($row)
{
	$sql = "SELECT SUM(extended) FROM 0_purch_order_details 
			WHERE order_no = ". $row['order_no'];
	$res = db_query($sql);
	$row_ = db_fetch($res);
	return $row_[0];
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

// ***************************************************************************************************************
display_heading('Lacking Items');
$sql = "SELECT b.reference, b.supplier_id, b.ord_date, a.item_code, a.quantity_ordered, a.quantity_received, b.order_no
			FROM `0_purch_order_details` a, 0_purch_orders b
			WHERE (quantity_received_pcs < quantity_ordered_pcs AND quantity_received_pcs > 0)
			AND a.order_no = b.order_no";

if ($_POST['order_number'] != '')
{
	$sql .= " AND b.reference LIKE ". db_escape('%'.$_POST['order_number']);
}			

if ($_POST['supp_id'] != '')
{
	$sql .= " AND b.supplier_id = ". db_escape($_POST['supp_id']);
}			

if ($_POST['StockLocation'] != '')
{
	$sql .= " AND b.into_stock_location = ". db_escape($_POST['StockLocation']);
}			

// display_error($sql);
$cols = array(
		_("PO #") => array('fun'=>'trans_view', 'ord'=>''), 
		_("Supplier") => array('fun'=>'supplier_name','ord'=>''),
		_("PO Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		_("Item") => array('fun'=>'item_name','ord'=>''),
		_("PO QTY") => array('align'=>'right'),
		_("RR QTY") => array('align'=>'right')
);

if (get_post('StockLocation') != $all_items) {
	$cols[_("Location")] = 'skip';
}

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);

br();
// ***************************************************************************************************************

// ***************************************************************************************************************
display_heading('Price Difference');

$sql2 = "SELECT c.reference, c.supplier_id, c. ord_date, a.item_code, round(a.extended / a.quantity_ordered,2), round((b.unit_price + b.unit_tax) * (a.multiplier/b.multiplier),2)
			FROM 0_purch_order_details a, 0_supp_invoice_items b, 0_purch_orders c
			WHERE a.po_detail_item = b.po_detail_item_id
			AND b.quantity > 0
			AND round(a.extended / a.quantity_ordered,2) != round((b.unit_price + b.unit_tax) * (a.multiplier/b.multiplier),2)
			AND a.order_no = c.order_no";

if ($_POST['order_number'] != '')
{
	$sql2 .= " AND c.reference LIKE ". db_escape('%'.$_POST['order_number']);
}			

if ($_POST['supp_id'] != '')
{
	$sql2 .= " AND c.supplier_id = ". db_escape($_POST['supp_id']);
}			

if ($_POST['StockLocation'] != '')
{
	$sql2 .= " AND c.into_stock_location = ". db_escape($_POST['StockLocation']);
}		
	
// display_error($sql2);
$cols2 = array(
		_("PO #") => array('fun'=>'trans_view', 'ord'=>''), 
		_("Supplier") => array('fun'=>'supplier_name','ord'=>''),
		_("PO Date") => array('name'=>'ord_date', 'type'=>'date', 'ord'=>'desc'),
		_("Item") => array('fun'=>'item_name','ord'=>''),
		_("PO Price") => array('align'=>'right'),
		_("Invoice Price") => array('align'=>'right')
);

if (get_post('StockLocation') != $all_items) {
	$cols[_("Location")] = 'skip';
}

$table2 =& new_db_pager('orders_tbl2', $sql2, $cols2);

$table2->width = "80%";

display_db_pager($table2);
// ***************************************************************************************************************

end_form();
end_page();
?>
