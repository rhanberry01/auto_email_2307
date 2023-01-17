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

$seconds = 20;
// if (isset($_GET['nmhgsdrdntgdsad']))
	// header('Refresh: '.$seconds);

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

page(_($help_context = "Purchase Order and Receive Importing"), false, false, "", $js);

$import_errors = array();
//===================================================
function check_my_items($line_item)
{
	$stock_id = $line_item['ProductID'];
	
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
				tax_type_id  = ".($v_row['pVatable'] == 1 ? 1 : 2 ).", 
				inactive = ".$v_row['inactive']."
			WHERE stock_id =".db_escape($stock_id);
				// last_cost = ".$v_row['CostOfSales'].", 
				// material_cost = ".$v_row['CostOfSales'].", 
			db_query($update_sql,'failed to update stock master');
		
	}
	else if ($v_row['Description'] != '')
	{
		$ins_sql = "INSERT INTO ".TB_PREF."stock_master (stock_id, product_code, description, long_description, tax_type_id, units, base_multiplier, last_cost ,material_cost)
			VALUES(".db_escape($stock_id).", 
				".db_escape($v_row['ProductCode']).", 
				".db_escape($v_row['Description']).", 
				".db_escape($v_row['Description']).", 
				".($v_row['pVatable'] == 1 ? 1 : 2 ).", 
				".db_escape($v_row['reportuom']).", 
				".db_escape($v_row['reportqty']).", 
				".db_escape($v_row['CostOfSales']+0).",  
				".db_escape($v_row['CostOfSales']+0).")";
		db_query($ins_sql, 'failed to insert stock in master');
	}
	else
	{
		// $ins_sql = "INSERT INTO ".TB_PREF."stock_master (stock_id, product_code, description, long_description, tax_type_id, units, base_multiplier, last_cost ,material_cost)
			// VALUES(".db_escape($stock_id).", 
				// ".db_escape($v_row['ProductCode']).", 
				// ".db_escape($v_row['Description']).", 
				// ".db_escape($v_row['Description']).", 
				// ".($v_row['pVatable'] == 1 ? 1 : 2 ).", 
				// ".db_escape($v_row['reportuom']).", 
				// ".db_escape($v_row['reportqty']).", 
				// ".db_escape($v_row['CostOfSales']+0).",  
				// ".db_escape($v_row['CostOfSales']+0).")";
		// db_query($ins_sql, 'failed to insert stock in master');
		return false;
	}
	
	return $stock_id;
}

function import_po_header($po)
{
	global $Refs, $import_errors;
	
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
				WHERE order_no = ".$po['PurchaseOrderID'];
	$res = db_query($sql);
	
	if (db_num_rows($res) != 0)
		return false;
	
	$supp_id = check_my_suppliers($po['VendorCode']);
	
	if ($supp_id === false)
	{
		$import_errors[] = array($po['PurchaseOrderID'],$po['PurchaseOrderNo'],"vendor code: ". $po['VendorCode'] ." not in vendor (MSSQL) table");
		
		return false;
	}
	
	$sql = "INSERT INTO ".TB_PREF."purch_orders (order_no, supplier_id, comments, ord_date, reference, requisition_no, 
				into_stock_location, delivery_address, is_approve, date_approve, approving_officer)
				VALUES (".
				$po['PurchaseOrderID'].",". 
				$supp_id.",". 
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
	
	return true;
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
	
	$item_code = check_my_items($line);
	
	if ($item_code === false)
	{
		return false;
	}
	
	$sql = "INSERT INTO ".TB_PREF."purch_order_details (order_no, item_code, description, p_uom, multiplier,
					qty_invoiced, unit_price, act_price, quantity_ordered , quantity_ordered_pcs,
					disc_percent1, disc_percent2, disc_percent3, 
					disc_amount1, disc_amount2, disc_amount3,
					extended)
				VALUES (".
				$line['PurchaseOrderID'].",". 
				$item_code.",". 
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
	
	return true;
}

function po_imported_already($order_no)
{
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
				WHERE order_no=$order_no";
	$res = db_query($sql);
	
	return (db_num_rows($res) > 0);
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

	if (trim($rr['Remarks']) == 'NULL' OR $rr['Remarks'] == NULL)
		$rr['Remarks'] = '';
		
	if ($rr['Remarks'] == '' AND $rr['SourceInvoiceNo'] != '')
		$rr['Remarks'] = $rr['SourceInvoiceNo'];
		
	// $vendor_code = get_supplier_id_by_supp_ref($rr['VendorCode']); //some did not match dont use this!
	$vendor_code = get_supplier_id_by_po_id($rr['PurchaseOrderID']);
	
	$sql = "INSERT INTO ".TB_PREF."grn_batch (id, supplier_id, purch_order_no, reference, source_invoice_no, delivery_date, loc_code, rcomments)
				VALUES (".
				$rr['ReceivingID'] .",".
				$vendor_code .",".
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
		
		if ($po_detail_item == '')
		{
			return $rr['PurchaseOrderNo'];
		}
		
		$sql = "INSERT INTO ".TB_PREF."grn_items (grn_batch_id, po_detail_item, item_code, description, r_uom, multiplier, qty_recd, qty_recd_pcs)
		VALUES (".db_escape($rr['ReceivingID']).", ".$po_detail_item.", ".db_escape($row['ProductID']).", ".db_escape($row['Description']) .", '"
		.$row['UOM']."',"
		.$row['pack'].","
		.$row['qty'].","
		.$row['qty'] * $row['pack'] .")";
		
		db_query($sql, "A GRN detail item could not be inserted.".$rr['PurchaseOrderID']);
		
		$sql = "UPDATE ".TB_PREF."purch_order_details
		SET quantity_received = ((quantity_received + " .($row['qty'] * $row['pack']).") / multiplier),
		 quantity_received_pcs = quantity_received_pcs + ".$row['qty'] * $row['pack']."
		WHERE po_detail_item = ".db_escape($po_detail_item);
		db_query($sql, "a purchase order details record could not be updated.");
		
		$standard_cost = get_standard_cost($row['ProductID']);
		$vendor_code = get_supplier_id_by_po_id($rr['PurchaseOrderID']);
		
		add_stock_move(ST_SUPPRECEIVE, $row['ProductID'], $rr['ReceivingID'], $rr['DeliverTo'], $date_, "",
				$row['qty'], $standard_cost, $vendor_code, 1, 
				($row['extended'] / $row['qty']),$row['UOM'], $row['pack']);
	
		// update_average_material_cost(null, $row['ProductID'], $standard_cost, $row['qty'] * $row['pack'], $date_);
	}
	return 'ok_ok_ok';
}

function import_rr($rr)
{
	global $Refs;
		
	$r_id = $rr['ReceivingID'];
	
	import_rr_header($rr);
	
	$good = import_rr_items($rr);
	
	// $sql = "SELECT Status, StatusDescription FROM ".TB_PREF."PurchaseOrder WHERE PurchaseOrderID = ".$rr['PurchaseOrderID'];
	// $res = ms_db_query($sql);
	// $row = mssql_fetch_array($res);
	
	$Refs->save(ST_SUPPRECEIVE, $r_id, $rr['ReceivingNo']);

	add_audit_trail(ST_SUPPRECEIVE, $r_id, mssql2date($rr['DateCreated']),'import RR');
	
	return $good;
}
//===================================================
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
//===================================================
start_form();

if (isset($_POST['ImportAll']) OR isset($_GET['nmhgsdrdntgdsad']))
{
	$preloader_gif = $path_to_root.'/themes/modern/images/ajax-loader.gif';
	echo "<div id='ploader' style='display:none'>
								<img src='$preloader_gif'>
				</div>";
	
	if (!isset($_GET['nmhgsdrdntgdsad']))
		$date_ = explode_date_to_dmy($_POST['OrdersAfterDate']);
	else
		display_notification("Auto import PO and RR every $seconds seconds.");
	
	if (isset($_POST['ImportAll']))
		meta_forward($_SERVER['PHP_SELF'], "startdate=".$_POST['OrdersAfterDate']."&nmhgsdrdntgdsad=zxcnnp". (6*$date_[0])*(6*$date_[1]).'fsdlaa'.(6*$date_[2]).'dfsbyu');
	
	$_POST['OrdersAfterDate'] = $_GET['startdate'];
	// global $Ajax;
	set_time_limit(0);
	
	// get all po of receiving with trandate >= startdate
	$ms_po_nos = array();
	
	$sql = "SELECT PurchaseOrderID FROM Receiving 
				WHERE DateReceived >= '".date2sql($_GET['startdate'])."'
				AND PurchaseOrderID != 0
				AND Status = 2";
	$res = ms_db_query($sql);
	while($po_row = mssql_fetch_array($res))
	{
		$ms_po_nos[] = $po_row[0];
	}
	
	if (count($ms_po_nos) > 0)
	{
		$ms_po_nos = implode(',',$ms_po_nos);
		//==================================
		
		
		$po_nos = array();

		$sql = "SELECT order_no FROM ".TB_PREF."purch_orders";
					// WHERE ord_date >= '".date2sql($_GET['startdate'])."'";
		$res = db_query($sql);
		while($row = db_fetch($res))
		{
			$po_nos[] = $row[0];
		}
		// WHERE DateCreated = '2012-03-07'
		// AND PurchaseOrderID = 6091";
					// WHERE DateCreated>= '".date2sql($_GET['startdate'])."'
		$sql = "SELECT *
					  FROM PurchaseOrder 
					WHERE PurchaseOrderID IN ($ms_po_nos)
					AND Status IN (2,3,4)";
		if (count($po_nos) > 0)
			$sql .= "AND PurchaseOrderID NOT IN (".implode(',',$po_nos).")";
		
		$sql .= " ORDER BY PurchaseOrderID";
		
		$res = ms_db_query($sql);

		while($po_row = mssql_fetch_array($res))
		{
			begin_transaction();
			//====== Import header
			$good = import_po_header($po_row);
			
			if (!$good)
				continue;
				
			$i_sql = "SELECT * FROM PurchaseOrderLine
						WHERE PurchaseOrderID=".$po_row['PurchaseOrderID'];
			$i_res = ms_db_query($i_sql);
			
			$ret = true;
			while($item_row = mssql_fetch_array($i_res))
			{
				$ret = import_po_item($item_row);
				
				if ($ret === false)
				{
				
					$import_errors[] = array($po_row['PurchaseOrderID'],$po_row['PurchaseOrderNo'],
						"Product ID: ".$item_row['ProductID']." Product Description:".$item_row['Description']." is not in the products (MSSQL) table");
					// $er_msg ="Failed to import PO # ".$po_row['PurchaseOrderNo']." because item with product id: ".$item_row['ProductID'].
						// " Product Description:".$item_row['Description']."is not in the products table. Please add the product to import PO</br>"; 
					break;
				}
			}
			
			if ($ret === false)
			{
				cancel_transaction();
				continue;
			}
			else
				commit_transaction();
		}
	}
	
	// ========================================= END of import PO ====================================
	
	//========================================= import RR
	$sql = "SELECT DISTINCT order_no FROM 0_purch_order_details 
				WHERE quantity_received < quantity_ordered";
	$res = db_query($sql);
	
	$po_ids = '';
	while($row = db_fetch($res))
	{
		$po_ids .= $row[0] .",";
	}
	
	if ($po_ids != '')
	{
		$s_sql = "SELECT * FROM Receiving 
						WHERE status = 2
						AND PurchaseOrderID IN(".substr($po_ids,0,-1).")";
		$s_res = ms_db_query($s_sql);
		while($s_row = mssql_fetch_array($s_res))
		{
			$rr_good = '';
			
			if (!check_rr($s_row['ReceivingID']))
				continue;
				
			begin_transaction();
			$rr_good = import_rr($s_row);
			
			if ($rr_good == 'ok_ok_ok')
				commit_transaction();
			else
			{
				display_error('ERROR at RR # '. $rr_good);
				cancel_transaction();
			}
		}
		
	}
	//========================================= END of RR

	div_start('items_table');
	if (count($import_errors) > 0)
	{
		display_heading('PO with issues (Not Imported)');

		start_table("$table_style2 width=80%");
		$th = array("PO #", "Reason" );
		table_header($th);
		$k = 0;
		foreach($import_errors as $errors)
		{
			alt_table_row_color($k);
			label_cell(viewer_link($errors[1], "purchasing/view/srs_view_po.php?trans_no=".$errors[0]));
			label_cell($errors[2]);
			end_row();
		}
		
		end_table();
	}
	div_end();
}

//===================================================


start_table("class='tablestyle_noborder'");
start_row();

date_cells(_("Beginning From :"), 'OrdersAfterDate');


submit_cells('ImportAll', _("Import All"),'',_('Import'));

end_row();

end_table(1);

end_form();
end_page();

?>
