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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager.inc");

//page(_($help_context = "Item Location Transfer Approval"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/manufacturing.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/purchasing/includes/ui/po_ui.inc");
include_once($path_to_root . "/includes/db/audit_trail_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "View Imported Receiving"), false, false, "", $js);

if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';

	$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	$Ajax->addDisable(true, 'OrdersToDate', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'order_number');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}

//===================================================
function check_rr($id)
{
	$sql = "SELECT * FROM ".TB_PREF."grn_batch	WHERE id=$id";
	$res = db_query($sql);
	
	return (db_num_rows($res) == 0);
}

function import_rr_header($rr)
{

	if (db_escape($rr['Remarks']) == 'NULL')
		$rr['Remarks'] = '';
		
	$sql = "INSERT INTO ".TB_PREF."grn_batch (id, supplier_id, purch_order_no, reference, source_invoice_no, delivery_date, loc_code, rcomments)
				VALUES (".
				$rr['ReceivingID'] .",".
				get_supplier_id_by_supp_ref($rr['VendorCode']) .",".
				$rr['PurchaseOrderID'] .",".
				db_escape($rr['ReceivingNo']) .",".
				db_escape($rr['Remarks']) .",'".
				date2sql(mssql2date($rr['DateCreated']))."',".
				db_escape($rr['DeliverTo']) .",'')";
	db_query($sql,'failed to import receiving header');
}

function get_po_item_id($order_no, $item_code)
{
	$sql = "SELECT po_detail_item FROM ".TB_PREF."purch_order_details
				WHERE order_no=$order_no
				AND item_code = ". db_escape($item_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function import_rr_items($rr)
{
	$sql = "SELECT * FROM ReceivingLine WHERE ReceivingID = ".$rr['ReceivingID'];
	$res = ms_db_query($sql);
	
	while($row = mssql_fetch_array($res))
	{
		if ($row['qty'] == 0)
			continue;
		
		$po_detail_item = get_po_item_id($rr['PurchaseOrderID'], $row['ProductID']);
		$date_ = mssql2date($rr['DateCreated']);
		
		$sql = "INSERT INTO ".TB_PREF."grn_items (grn_batch_id, po_detail_item, item_code, description, r_uom, multiplier, qty_recd, qty_recd_pcs)
		VALUES (".db_escape($rr['ReceivingID']).", ".$po_detail_item.", ".db_escape($row['ProductID']).", ".db_escape($row['Description']) .", '"
		.$row['UOM']."',"
		.$row['pack'].","
		.$row['qty'].","
		.$row['qty'] * $row['pack'] .")";
		
		db_query($sql, "A GRN detail item could not be inserted.");
		
		$sql = "UPDATE ".TB_PREF."purch_order_details
        SET quantity_received = ((quantity_received + " .($row['qty'] * $row['pack']).") / multiplier),
         quantity_received_pcs = quantity_received_pcs + ".$row['qty'] * $row['pack']."
        WHERE po_detail_item = ".db_escape($po_detail_item);
		db_query($sql, "a purchase order details record could not be updated.");
		
		$standard_cost = get_standard_cost($row['ProductID']);
		add_stock_move(ST_SUPPRECEIVE, $row['ProductID'], $rr['ReceivingID'], $rr['DeliverTo'], $date_, "",
            	$row['qty'], $standard_cost, get_supplier_id_by_supp_ref($rr['VendorCode']), 1, 
				($row['extended'] / $row['qty']),$row['UOM'], $row['pack']);
	
		// update_average_material_cost(null, $row['ProductID'], $standard_cost, $row['qty'] * $row['pack'], $date_);
	}
}

function import_rr($rr)
{
	global $Refs;
	
	begin_transaction();
	
	$r_id = $rr['ReceivingID'];
	
	import_rr_header($rr);
	
	import_rr_items($rr);
	
	$sql = "SELECT Status, StatusDescription FROM ".TB_PREF."PurchaseOrder WHERE PurchaseOrderID = ".$rr['PurchaseOrderID'];
	// $res = ms_db_query($sql);
	// $row = mssql_fetch_array($res);
	
	$Refs->save(ST_SUPPRECEIVE, $r_id, $rr['ReceivingNo']);

	add_audit_trail(ST_SUPPRECEIVE, $r_id, mssql2date($rr['DateCreated']),'import RR');
	
	commit_transaction();
}

//===================================================
if (isset($_POST['import_rr']))
{
	global $Ajax;
	
	//== get po's that are incomplete
	$sql = "SELECT DISTINCT order_no FROM 0_purch_order_details 
				WHERE quantity_received < quantity_ordered";
	$res = db_query($sql);
	
	$po_ids = array();
	while($row = db_fetch($res))
	{
		$po_ids[] = $row[0];
	}
	
	$counter = 0;
	
	if (count($po_ids) > 0)
	{
		$s_sql = "SELECT * FROM Receiving 
						WHERE status = 2
						AND PurchaseOrderID IN(".implode(',',$po_ids).")";
		$s_res = ms_db_query($s_sql);
		
		while($s_row = mssql_fetch_array($s_res))
		{
			if (!check_rr($s_row['ReceivingID']))
				continue;
			 import_rr($s_row);
			 $counter ++;
		}
		
	}
	display_notification("$counter Receives Imported");
	$Ajax->activate('orders_tbl');
}

//===================================================

start_form();

// submit_center('import_rr', '<b>Import ALL POSTED Receives</b>');
// br();

start_table("class='tablestyle_noborder'");
start_row();
supplier_list_cells('Supplier', 'supplier_id', null, true);
ref_cells(_("Receiving/PO/Invoice #:"), 'order_number', '',null, '', true);
date_cells(_("from:"), 'OrdersAfterDate');
date_cells(_("to:"), 'OrdersToDate');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), false);
end_row();
end_table();

div_start('orders_tbl');

	$sql = "SELECT a.*, b.reference as po_number FROM ".TB_PREF."grn_batch a, ".TB_PREF."purch_orders b
	WHERE a.purch_order_no = b.order_no";
	
	
	
	// if ($_SESSION['wa_current_user']->user == 1)
	// {
		// $sql .= " AND a.reference like '%_____84___'";
	// }
	
	// else
	// {
		if (trim($_POST['order_number']) == '')
		{
			$sql .= " AND delivery_date BETWEEN ".db_escape(date2sql($_POST['OrdersAfterDate']))." 
					  AND ".db_escape(date2sql($_POST['OrdersToDate']));
					  
			if ($_POST['supplier_id'] != '')
				$sql .= " AND a.supplier_id = ".$_POST['supplier_id'];
		}
		
		else
		{
			$sql .= " AND (TRIM(LEADING '0' FROM a.reference) = ".db_escape(ltrim($_POST['order_number'],'0'))."
						OR TRIM(LEADING '0' FROM b.reference) = ".db_escape(ltrim($_POST['order_number'],'0'))."
						OR source_invoice_no LIKE ".db_escape('%'.$_POST['order_number'].'%').")";
		}
	// }
	$sql .= " ORDER BY delivery_date DESC";
	
	
	// if ($_SESSION['wa_current_user']->user == 1)
	// display_error($sql);
	$res = db_query($sql);

	br();
	start_table("$table_style2 width=90%");
	$th = array(_("Receiving #"),_("PO #"),'CV #', _("Supplier"), 'Invoice #', _("Delivery Date"), 'Total' );
	table_header($th);
	
	$k = 0;
	$total_total = $count = 0;
	while($row = db_fetch($res))
	{
		$count ++;
		
		$cv_no_ = get_cv_of_rr($row['id']);
		
		$rr_po_total = get_po_total($row['id']);
		
		$total_total += $rr_po_total;
		
		alt_table_row_color($k);
			label_cell(get_trans_view_str(ST_SUPPRECEIVE, $row['id'],ltrim($row['reference'],0)));
			// label_cell(get_trans_view_str(ST_PURCHORDER, $row['purch_order_no'],get_reference(ST_PURCHORDER, $row['purch_order_no'])));
			if (strpos($row['po_number'], 'PO') === false)
				label_cell(viewer_link(ltrim($row['po_number'],0), "purchasing/view/srs_view_po.php?trans_no=".$row['purch_order_no']));
			else
				label_cell(get_trans_view_str(ST_PURCHORDER, $row['purch_order_no'],$row['po_number']));
				
			label_cell(
			"<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?
				cv_id=".($cv_no_['id'])."'onclick=\"javascript:openWindow(this.href,this.target); return false;\">" .
				$cv_no_[1] . "&nbsp;</a> "
			
			);
			
			label_cell(get_supplier_name($row['supplier_id']));
			label_cell($row['source_invoice_no']);
			label_cell(sql2date($row['delivery_date']));
			amount_cell($rr_po_total);
		end_row();
	}
	alt_table_row_color($k);
		label_cell('TOTAL: ','colspan=6 align=right');
		amount_cell($total_total,true);
	end_row();
	display_heading('Record count : '.$count);
	end_table();
	// //===================================
	
div_end();

function get_po_total($grn_id)
{
	// $sql = "SELECT SUM(truncate((((a.qty_recd/(b.multiplier/a.multiplier) * b.unit_price)
				// *(1-b.disc_percent1/100)*(1-b.disc_percent2/100) *(1-b.disc_percent3/100))
				// - (b.disc_amount1) - (b.disc_amount3) - (b.disc_amount3)),2))
			// FROM 0_grn_items a, 0_purch_order_details b 
			// WHERE a.grn_batch_id = $grn_id AND a.po_detail_item = b.po_detail_item";
	$sql = "SELECT SUM(`extended`)
			FROM `0_grn_items` a 
			WHERE `grn_batch_id` = $grn_id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if ($row[0] > 0)
		return $row[0];
		
	$sql = "SELECT SUM(a.qty_recd *(SELECT extended/quantity_ordered 
									FROM 0_purch_order_details 
									WHERE po_detail_item = a.po_detail_item)) 
			FROM `0_grn_items` a 
			WHERE `grn_batch_id` = $grn_id ";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_cv_of_rr($grn_batch_id)
{
	$sql= "SELECT b.id FROM 0_grn_batch a, 0_grn_items b
			WHERE a.id = $grn_batch_id
			AND a.id = b.grn_batch_id
			LIMIT 1";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$grn_item_id = $row[0];
	
	
	$sql = "SELECT e.id,e.cv_no FROM 0_supp_invoice_items c , 0_supp_trans d, 0_cv_header e
			WHERE  c.grn_item_id = $grn_item_id
			AND c.supp_trans_no = d.trans_no
			AND c.supp_trans_type = 20
			AND d.type = 20
			AND d.cv_id = e.id
			LIMIT 1";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

end_form();
end_page();

?>
