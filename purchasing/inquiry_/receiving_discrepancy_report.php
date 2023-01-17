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
	return get_trans_view_str(ST_PURCHORDER, $trans["purch_order_id"], $trans["po_num"]);
}

function gl_view($row)
{
	return get_gl_view_str(20,$row["supp_trans_id"],$row["supp_trans_id"]);
}

function rr_trans_view($trans)
{
	return get_trans_view_str(ST_SUPPRECEIVE, $trans["grn_id"], $trans["rr_reference"]);
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

$sql = "SELECT r.date_,rd.*, round(sum(gb.extended),2) as grn_amount,r.inv_no,r.inv_amount
FROM 0_receiving_discrepancy as rd
LEFT JOIN 0_grn_items as gb
ON gb.grn_batch_id=rd.grn_id
LEFT JOIN receiving_new.0_receiving as r
ON rd.receiving_id=r.id
GROUP BY rd.grn_id";
$res = db_query($sql);
//display_error($sql);

div_start('header');

start_table($table_style2.' width=90%');
$th = array();
		array_push($th,'', 'PO #','Invoice#','Date','Supplier','Attachment','Receiving#','Trans#','PO Price','Invoice Amount','Discrepancy');
		
//array_push($th, 'Date Created', 'TransNo','MovementID','From Location','To Location', 'Created By', 'Date Posted', 'Posted By', 'Status','','','');
$x=db_num_rows($res) ;
//display_error($x);

if (db_num_rows($res) > 0){
	display_heading('Transactions with Discrepancy');
	br();
	table_header($th);
}
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$k = 0;
$c++;
$u = get_user($_SESSION["wa_current_user"]->user);
$approver= $u['user_id'];

$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
//display_error($approver);
while($row = db_fetch($res))
{
	$discrepancy=$row['grn_amount']-$row['inv_amount'];
	alt_table_row_color($k);
	if ($discrepancy>5){
	label_cell($c++);
	label_cell(po_trans_view($row),'align=left');
	label_cell($row['inv_no'],'align=left');
	label_cell(sql2date($row['date_']));
	label_cell(get_supplier_name($row['supp_id']));
	label_cell("<a target=blank href='".get_po_attachment($row['po_num'],str_pad($row['rr_reference'], 10, "0", STR_PAD_LEFT))
	."'onclick=\"javascript:openWindow(href,target); return false;\">"._("View Invoice") . "&nbsp;</a> ", 'align=center');
	label_cell(rr_trans_view($row),'align=right');
	label_cell(gl_view($row),'align=right');
	label_cell(number_format2($row['grn_amount'],2),'align=right');
	label_cell(number_format2($row['inv_amount'],2),'align=right');
	label_cell(number_format2($row['inv_amount']-$row['grn_amount'],2),'align=right');
	}

	end_row();
}
end_table();
br();
br();
div_end();

end_form();
end_page();
?>