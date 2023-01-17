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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Fix Purchase Order and Receiving ID"), @$_REQUEST['popup']);

include($path_to_root . "/includes/ui.inc");

if ($_SESSION['wa_current_user']->username != 'admin')
{
	display_error('for ADMIN only');
	display_footer_exit();
}

function update_purch_orders($PurchaseOrderNo, $order_no)
{
	$sql = "SELECT * FROM PurchaseOrder WHERE PurchaseOrderNo = '$PurchaseOrderNo'";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	$sql = "UPDATE ".TB_PREF."purch_orders SET new_po_id =".$row['PurchaseOrderID']." 
		WHERE order_no = $order_no";
	db_query($sql,'failed to update purch orders');
	
	return $row['PurchaseOrderID'];
}

function update_purch_order_detail($old_id, $new_id)
{
	$sql = "UPDATE ".TB_PREF."purch_order_details SET 
				new_order_no = $new_id
			WHERE order_no = $old_id";
	db_query($sql,'failed to update purch order details');
}

function update_grn_batch($ReceivingNo, $rr_id, $po_no)
{
	$sql = "SELECT * FROM Receiving WHERE ReceivingNo = '$ReceivingNo'";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	$po_sql = "SELECT new_po_id FROM 0_purch_orders WHERE order_no = $po_no";
	$po_res = db_query($sql);
	$po_row = db_fetch($res);
	
	$purch_order_no = $po_row[0];
	
	$sql = "UPDATE ".TB_PREF."grn_batch SET 
			new_id =".$row['ReceivingID'].",
			purch_order_no = $purch_order_no
		WHERE id = $rr_id";
	db_query($sql,'failed to update grn batch');
	
	return $row['ReceivingID'];
}

function update_audit_trail($type, $old_id, $new_id)
{
	$sql = "UPDATE ".TB_PREF."audit_trail  SET 
				new_trans_no = $new_id
			WHERE trans_no = $old_id
			AND type = ".$type;
	db_query($sql,'failed to update audit trail');
}

function update_refs($type, $reference, $new_id)
{
	$sql = "UPDATE ".TB_PREF."refs SET 
				new_id = $new_id
			WHERE reference = '$reference'
			AND type = ".$type;
	db_query($sql,'failed to update audit trail');
}

if (isset($_POST['gogogo'])) // start fixing
{
	set_time_limit(0);
	begin_transaction();
	
	// fix PO first (0_purch_orders, 0_purch_order_details, 0_refs, 0_audit_trail)
	$sql = "UPDATE ".TB_PREF."purch_orders SET new_po_id = 0";
	db_query($sql);
	$sql = "UPDATE ".TB_PREF."purch_order_details SET new_order_no = 0";
	db_query($sql);
	$sql = "UPDATE ".TB_PREF."audit_trail  SET new_trans_no = 0";
	db_query($sql);
	$sql = "UPDATE ".TB_PREF."refs SET new_id = 0 ";
	db_query($sql);
	
	$sql = "SELECT * FROM `0_purch_orders`";// WHERE `ord_date` >= '2013-04-29'";
	$res = db_query($sql);
	while($row = db_fetch($res))
	{
		$new_po_id = update_purch_orders($row['reference'],$row['order_no']);
		update_purch_order_detail($row['order_no'], $new_po_id);
		update_audit_trail(ST_PURCHORDER, $row['order_no'], $new_po_id);
		update_refs(ST_PURCHORDER$row['reference'], $new_po_id);
		
	}
	//=========================================================================================
	
	$sql = "SELECT * FROM ".TB_PREF."grn_batch";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		$new_rr_id = update_grn_batch($row['reference'],$row['id'],$row['purch_order_no']);
		// update_grn_items($row['id'], $new_rr_id);
		// update_stock_moves(ST_SUPPRECEIVE, $row['id'], $new_rr_id);
		// update_audit_trail(ST_SUPPRECEIVE, $row['id'], $new_rr_id);
		// update_refs(ST_SUPPRECEIVE, $row['reference'], $new_rr_id);
		
	}
	//fix RR
	
	// cancelled. invoice will be affected //\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
	
	commit_transaction();
	display_notification('orayt');
}

//=================================================================================
start_form();
submit_center('gogogo','GO GO GO');
end_form();

end_page();

?>
