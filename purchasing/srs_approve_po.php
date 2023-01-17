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
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/db/audit_trail_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Purchase Orders"), false, false, "", $js);

if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';

	$Ajax->addDisable(true, 'vendorcode', $disable);
	$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	$Ajax->addDisable(true, 'OrdersToDate', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'order_number');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}


//===================================================
function check_my_items($stock_id)
{
	$sql = "SELECT * FROM ".TB_PREF."stock_master WHERE stock_id =".db_escape($stock_id);
	$res = db_query($sql);
	
	$v_sql = "SELECT 	ProductID,
					ProductCode,
					Description,
					pVatable,
					reportuom,
					reportqty,
					inactive,
					CostOfSales
			FROM Products 
			WHERE  ProductID = ".db_escape($stock_id);
		$v_res = ms_db_query($v_sql);
		$v_row = mssql_fetch_array($v_res);
		
	if (db_num_rows($res) > 0) 
	{
		// $row = db_fetch($res);
		
		$update_sql = "UPDATE ".TB_PREF."stock_master SET
				product_code = ".db_escape($v_row['ProductCode']).", 
				description = ".db_escape($v_row['Description']).", 
				long_description  = ".db_escape($v_row['Description']).", 
				units  = ".db_escape($v_row['reportuom']).", 
				inactive = ".$v_row['inactive']."
			WHERE stock_id =".db_escape($stock_id);
				// last_cost = ".$v_row['CostOfSales'].", 
				// material_cost = ".$v_row['CostOfSales'].", 
			db_query($update_sql,'failed to update stock master');
		
	}
	else
	{
		$ins_sql = "INSERT INTO ".TB_PREF."stock_master (stock_id, product_code, description, long_description, tax_type_id, units, base_multiplier, last_cost ,material_cost)
			VALUES(".db_escape($stock_id).", 
				".db_escape($v_row['ProductCode']).", 
				".db_escape($v_row['Description']).", 
				".db_escape($v_row['Description']).", 
				".($v_row['pVatable'] == 1 ? 1 : 2 ).", 
				".db_escape($v_row['reportuom']).", 
				".db_escape($v_row['reportqty']).", 
				".db_escape($v_row['CostOfSales']).",  
				".db_escape($v_row['CostOfSales']).")";
		db_query($ins_sql, 'failed to insert stock in master');
	}
	
	return $stock_id;
}

function import_po_header($po)
{
	global $Refs;
	
	$sql = "INSERT INTO ".TB_PREF."purch_orders (order_no, supplier_id, comments, ord_date, reference, requisition_no, 
				into_stock_location, delivery_address, is_approve, date_approve, approving_officer)
				VALUES (".
				$po['PurchaseOrderID'].",". 
				check_my_suppliers($po['VendorCode']).",". 
				db_escape($po['Remarks']).",'".
				date2sql(mssql2date($po['DateCreated']))."',".
				db_escape($po['PurchaseOrderNo']).",".
				db_escape($po['ReferenceNo']).",".
				$po['DeliverTo'].",".
				db_escape($po['DeliveryAddress']).",
				1,'".
				date2sql(Today())."',".
				db_escape($_SESSION["wa_current_user"]->username).")";
	db_query($sql,'failed to import header');
	
	$Refs->save(ST_PURCHORDER, $po['PurchaseOrderID'], $po['PurchaseOrderNo']);

	add_audit_trail(ST_PURCHORDER, $po['PurchaseOrderID'], mssql2date($po['DateCreated']),'import PO');
}

function import_po_item($line)
{	
	$disc_percent1 = $disc_percent2 = $disc_percent3 = 
	$disc_amount1 = $disc_amount2 = $disc_amount3 = '0';
	
	if ($line['DiscAmount1'] != 0)
	{
		if($line['Percent1'] != 0)
			$disc_percent1 = $line['DiscAmount1'];
		else
			$disc_amount1 = $line['DiscAmount1'];
	}
	if ($line['DiscAmount2'] != 0)
	{
		if($line['Percent2'] != 0)
			$disc_percent2 = $line['DiscAmount2'];
		else
			$disc_amount2 = $line['DiscAmount2'];
	}
	if ($line['DiscAmount3'] != 0)
	{
		if($line['Percent3'] != 0)
			$disc_percent3 = $line['DiscAmount3'];
		else
			$disc_amount3 = $line['DiscAmount3'];
	}
	
	
	$sql = "INSERT INTO ".TB_PREF."purch_order_details (order_no, item_code, description, p_uom, multiplier,
					qty_invoiced, unit_price, act_price, quantity_ordered , quantity_ordered_pcs,
					disc_percent1, disc_percent2, disc_percent3, 
					disc_amount1, disc_amount2, disc_amount3,
					extended)
				VALUES (".
				$line['PurchaseOrderID'].",". 
				check_my_items($line['ProductID']).",". 
				db_escape($line['Description']).",". 
				db_escape($line['UOM']).",". $line['pack'].",".
				"0,". 
				$line['unitcost'].",". 
				"0,". 
				$line['qty'].",".($line['qty']*$line['pack']).",
				$disc_percent1, $disc_percent2, $disc_percent3, 
				$disc_amount1, $disc_amount2, $disc_amount3,".
				$line['extended'].")";
	db_query($sql,'failed to import detail');
}

function po_imported_already($order_no)
{
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
				WHERE order_no=$order_no";
	$res = db_query($sql);
	
	return (db_num_rows($res) > 0);
}
//===================================================
$id = find_submit('app');
if ($id != -1)
{
	global $Ajax;
	
	$x_sql = "SELECT Status FROM PurchaseOrder WHERE PurchaseOrderID=$id";
	$x_res = ms_db_query($x_sql);
	$x_row = mssql_fetch_array($x_res);
	
	if ($x_row[0] != 2)
	{
		$sql = "UPDATE PurchaseOrder SET
						PostedBy = ".$_SESSION["wa_current_user"]->user.",
						Status = 2,
						StatusDescription = 'POSTED',
						PostedDate = CURRENT_TIMESTAMP 
					WHERE PurchaseOrderID=$id";
		// ms_db_query($sql,'Failed to update  MS Purchase Order');
	}
	
	begin_transaction();
	
	$sql = "SELECT *
				  FROM PurchaseOrder
				WHERE PurchaseOrderID=$id";
	$res = ms_db_query($sql);
	$po_row = mssql_fetch_array($res);
	
	//====== Import header
	import_po_header($po_row);
	
	$i_sql = "SELECT * FROM PurchaseOrderLine
				WHERE PurchaseOrderID=$id";
	$i_res = ms_db_query($i_sql);
	
	while($item_row = mssql_fetch_array($i_res))
	{
		//====== import item
		import_po_item($item_row);
	}
	
	commit_transaction();
	
	$Ajax->activate('orders_tbl');
}

//===================================================

start_form();
start_table("class='tablestyle_noborder'");
start_row();

// if ($_POST['OrdersAfterDate'] == '')
	// $_POST['OrdersAfterDate'] = '03/28/2012';

ref_cells(_("PO #:"), 'order_number', '',null, '', true);
supplier_list_ms_cells('Supplier:', 'vendorcode', null, true);
date_cells(_("from:"), 'OrdersAfterDate');
date_cells(_("to:"), 'OrdersToDate');

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

end_row();

end_table(1);


div_start('orders_tbl');
	$sql = "SELECT PurchaseOrderID, PurchaseOrderNo, Description, DateCreated, NetTotal, StatusDescription, Status
				  FROM PurchaseOrder";
	if ($_POST['order_number'] == '')
	{
		$sql .= "  WHERE Status != 1
					AND DateCreated BETWEEN '".date2sql($_POST['OrdersAfterDate'])."' 
				  AND '".date2sql($_POST['OrdersToDate'])."'";
	}
	else
	{
		$sql .= " WHERE PurchaseOrderNo LIKE ".db_escape('%'.$_POST['order_number']);
	}
	
	if ($_POST['vendorcode'] != '')
	{
		$sql .= " AND VendorCode = ".db_escape($_POST['vendorcode']);
	}
	$res = ms_db_query($sql);

	start_table("$table_style2 width=90%");
	$th = array(_("PO #"), _("Supplier"), _("Order Date"), _("Amount"), 'Status',"" );
	table_header($th);
	
	$k = 0;
	while($row = mssql_fetch_array($res))
	{
		alt_table_row_color($k);
			label_cell(viewer_link($row['PurchaseOrderNo'], "purchasing/view/srs_view_po.php?trans_no=".$row['PurchaseOrderID']));
			label_cell($row['Description']);
			label_cell(mssql2date($row['DateCreated']));
			amount_cell($row['NetTotal']);
			label_cell($row['StatusDescription']);
			
			if (po_imported_already($row['PurchaseOrderID']))
				label_cell('imported');
			else if ($row['Status'] == 1) // OPEN -> approve then import
				// edit_button_cell("app".$row['PurchaseOrderID'], _("Approve"),false,true);
				label_cell('for approval', 'align=center');
			else if ($row['Status'] == 2 OR $row['Status'] == 3 OR $row['Status'] == 4) // POSTED/PARTIAL/FULL -> import only
				edit_button_cell("app".$row['PurchaseOrderID'], _("Import"),false,true);
				
		end_row();
	}
	end_table();
	//===================================
	
div_end();

end_form();
end_page();

?>
