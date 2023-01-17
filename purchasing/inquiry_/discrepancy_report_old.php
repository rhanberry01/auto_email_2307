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
	
page(_($help_context = "Receiving Discrepancy Report"), false, false, "", $js);

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
ref_cells(_("Invoice #:"), 'invoice_no', '',null, '', true);
supplier_list_cells('Supplier: ','supp_id',null,true);
br();

yesno_list_cells('Show Resolved:', 'show');

$items = array();
// $items['0'] = 'ALL';
$items['2'] = 'Submitted';
$items['3'] = 'Resolved';
$items['1'] = 'Delivery';

label_cells('Date by :',array_selector('p_type', null, $items, array() ));

date_cells(_("from:"), 'OrdersAfterDate', '', null, -30);
date_cells(_("to:"), 'OrdersToDate');

// echo '<br>';
// locations_list_cells(_("into location:"), 'StockLocation', null, true);

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

//---------------------------------------------------------------------------------------------
function po_trans_view($trans)
{
	return get_trans_view_str(ST_PURCHORDER, $trans["order_no"], $trans["reference"]);
}

function rr_trans_view($trans)
{
	return get_trans_view_str(ST_SUPPRECEIVE, $trans["grn_id"], $trans["source_invoice_no"]);
}

function supplier_name($row)
{
	return get_supplier_name($row['supplier_id']);
}

function get_username__($row)
{
	return get_username_by_id($row['submitted_by']);
}

function discrepancy_view($row)
{
	// return viewer_link('<b>view items</b>', "purchasing/view/discrepancy_po.php?trans_no=".$row['order_no']);;
	return viewer_link('<b>view items</b>', "purchasing/view/discrepancy_po.php?trans_no=".$row['order_no']);
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
	if ($row['resolved_by'] == 0)
		return pager_link( _("Edit"),"/purchasing/discrepancy_fix.php?". SID. "discrepancy_id=" . $row["discrepancy_id"], ICON_EDIT);
	// else
		// return 'resolved:<b>'.sql2date($row['date_resolved']).'</b> <br> by:' .get_username_by_id($row['resolved_by']);
}

function f_date_resolved($row) 
{	
	if ($row['resolved_by'] != 0)
		return sql2date($row['date_resolved']);
}

function f_resolved_by($row) 
{	
	if ($row['resolved_by'] != 0)
		return get_username_by_id($row['resolved_by']);
}

function prt_link($row)
{
	if (get_voided_entry(ST_PURCHORDER, $row['order_no']) === false)
	return print_document_link($row['order_no'], _("Print"), true, 18, ICON_PRINT);
}
//---------------------------------------------------------------------------------------------

display_heading('Transactions with Discrepancy');

$sql = "SELECT c.reference,
			  b.source_invoice_no,
			  c.supplier_id,
			  b.delivery_date,
			  a.date_submitted,
			  submitted_by,
			  invoice_total_po_price,
			  actual_invoice_total,
			  b.id AS grn_id,
			  c.order_no,
			  a.id AS discrepancy_id,
			  a.resolved_by,
			  a.date_resolved
			FROM 0_discrepancy_header a , 0_grn_batch b, 0_purch_orders c
			WHERE a.grn_batch_id = b.id
			AND (b.locked = 1 OR a.resolved_by != 0)
			AND b.purch_order_no = c.order_no";
			
if (!$_POST['show'])
{
	$sql .= " AND a.resolved_by = 0";
}
if ($_POST['order_number'] != '')
{
	$sql .= " AND c.reference LIKE (". db_escape('%'.$_POST['order_number'].'%').")";
}

if ($_POST['invoice_no'] != '')
{
	$sql .= " AND b.source_invoice_no LIKE (". db_escape('%'.$_POST['invoice_no'].'%').")";
}
		
if ($_POST['supp_id'] != '')
{
	$sql .= " AND b.supplier_id = ". db_escape($_POST['supp_id']);
}

switch($_POST['p_type'])
{	
	case 1: //delivery
		$sql .= " AND b.delivery_date >= '". date2sql($_POST['OrdersAfterDate'])."'
				AND b.delivery_date <= '". date2sql($_POST['OrdersToDate'])."'";
		break;
	case 2: // submitted
		$sql .= " AND date_submitted >= '". date2sql($_POST['OrdersAfterDate'])."'
				AND date_submitted <= '". date2sql($_POST['OrdersToDate'])."'";
		break;
	case 3: // resolved
		$sql .= " AND date_resolved >= '". date2sql($_POST['OrdersAfterDate'])."'
				AND date_resolved <= '". date2sql($_POST['OrdersToDate'])."'
				AND a.resolved_by != 0";
		break;
}

$cols = array(
	_("PO #") => array('fun'=>'po_trans_view', 'ord'=>''), 
	_("Invoice #") => array('ord'=>''),
	_("Supplier") => array('fun'=>'supplier_name','ord'=>''),
	_("Delivery Date") => array('type'=>'date','ord'=>''),
	_("Date Submitted") => array('type'=>'date','ord'=>''),
	_("Submitted by") => array('fun' => 'get_username__'),
	_("Amount (in PO price)") => 'amount',
	_("Actual Invoice Amount") => 'amount',
	"Update Prices" => array('fun'=>'edit_link', 'align'=>'center'),
	"Date Resolved" => array('fun'=>'f_date_resolved', 'align'=>'center'),
	"Resolved By" => array('fun'=>'f_resolved_by', 'align'=>'center'),
	);

$table =& new_db_pager('orders_tbl', $sql, $cols);

$table->width = "80%";

display_db_pager($table);
// ***************************************************************************************************************

end_form();
end_page();
?>
