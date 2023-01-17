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

$seconds = 500;
if (isset($_GET['nmhgsdrdntgdsad']))
	header('Refresh: '.$seconds);

$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/db/audit_trail_db.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_cv.inc");
include_once($path_to_root . "/gl/includes/db/rs_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Import RR to CV"), false, false, "", $js);
set_time_limit(0);

$import_errors = array();
//===================================================
function check_my_items($line_item, $po_id='')
{
	if (isset($line_item['ProductID']))
		$stock_id = $line_item['ProductID'];
	else
		$stock_id = $line_item['stock_id'];
	
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
	
	if (mssql_num_rows($v_res) == 0)
	{
		display_error('Product ID : '. $stock_id . ' not in MSSQL Products table' . ($po_id ? ' PO # : '.$po_id : ''));die;
		return false;
	}
		
	if (db_num_rows($res) > 0) 
	{
		// $row = db_fetch($res);
				// tax_type_id  = ".($v_row['pVatable'] == 1 ? 1 : 2 ).", 
		
		$update_sql = "UPDATE ".TB_PREF."stock_master SET
				product_code = ".db_escape($v_row['ProductCode']).", 
				description = ".db_escape($v_row['Description']).", 
				long_description  = ".db_escape($v_row['Description']).", 
				units  = ".db_escape($v_row['reportuom']).", 
				inactive = ".($v_row['inactive']+0)."
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
				1,
				".db_escape($v_row['reportuom']).", 
				".db_escape($v_row['reportqty']).", 
				".db_escape($v_row['CostOfSales']+0).",  
				".db_escape($v_row['CostOfSales']+0).")";
		db_query($ins_sql, 'failed to insert stock in master');
	}
	else
	{
		return false;
	}
	
	return $stock_id;
}

function import_po_header($po)
{
	global $Refs, $import_errors;
	
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
				WHERE reference = '".$po['PurchaseOrderNo']."'";
	$res = db_query($sql);
	
	if (db_num_rows($res) != 0)
	{
		$po_row = db_fetch($res);
		$sql = "SELECT * FROM ".TB_PREF."purch_order_details
				WHERE order_no = ".$po_row['order_no'];
		$res = db_query($sql);
		if (db_num_rows($res) > 0)
			return false;
		else
			return $po_row['order_no'];
	}
	$supp_id = check_my_suppliers($po['VendorCode']);
	
	if ($supp_id === false)
	{
		$import_errors[] = array($po['PurchaseOrderID'],$po['PurchaseOrderNo'],"vendor code: ". $po['VendorCode'] ." not in vendor (MSSQL) table");
		
		return false;
	}
	
	$sql = "INSERT INTO ".TB_PREF."purch_orders (supplier_id, comments, ord_date, reference, requisition_no, 
				into_stock_location, delivery_address, is_approve, date_approve, approving_officer)
				VALUES (".
				// $po['PurchaseOrderID'].",". 
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
	
	$po_no = db_insert_id();
	$Refs->save(ST_PURCHORDER, $po_no, $po['PurchaseOrderNo']);

	add_audit_trail(ST_PURCHORDER, $po_no, mssql2date($po['DateCreated']),'import PO');
	
	return $po_no;
}

function import_po_item($po_id,$line)
{	
	$disc_percent1 = $disc_percent2 = $disc_percent3 = $disc_amount1 = $disc_amount2 = $disc_amount3 = 0;
	
	$disc_amount1=0;
	if ($line['DiscAmount1'] != 0)
	{
		if($line['Percent1'] != 0)
			$disc_percent1 = $line['DiscAmount1'];
		else
			$disc_amount1 = $line['DiscAmount1']+0;
	}
	if ($line['DiscAmount2'] != 0)
	{
		if($line['Percent2'] != 0)
			$disc_percent2 = $line['DiscAmount2'];
		else
			$disc_amount2 = $line['DiscAmount2']+0;
	}
	if ($line['DiscAmount3'] != 0)
	{
		if($line['Percent3'] != 0)
			$disc_percent3 = $line['DiscAmount3'];
		else
			$disc_amount3 = $line['DiscAmount3']+0;
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
				$po_id.",". 
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
	db_query($sql,'failed to import detail - PO');
	
	return true;
}

function po_imported_already($reference)
{
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
				WHERE reference='$reference'";
	$res = db_query($sql);
	return (db_num_rows($res) > 0);
}
//===================================================
function check_rr($rec_no)
{
	$sql = "SELECT * FROM ".TB_PREF."grn_batch	WHERE reference='$rec_no'";
	$res = db_query($sql);
	
	if (db_num_rows($res) != 0)
	{
		$row = db_fetch($res);
		$sql_ = "SELECT COUNT(*) FROM ".TB_PREF."grn_items
				WHERE grn_batch_id = ".$row['id'];
			// display_error($sql_);die;
		$res_ = db_query($sql_);
		$row_ = db_fetch($res_);
		if ($row_[0] == 0)
		{
			$sql = "DELETE FROM ".TB_PREF."grn_batch WHERE reference='$rec_no'";
			db_query($sql,'failed to delete grn_item');
			return true;
		}
		else 
			return false;
		
	}
	return true;
}

function get_po_header_by_reference($reference)
{
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
			WHERE reference = ".db_escape($reference);
	$res = db_query($sql,'failed to get po <br>'.$sql);
	$row = db_fetch($res);
	return $row;
}

function import_rr_header($rr)
{
	if (trim($rr['Remarks']) == 'NULL' OR $rr['Remarks'] == NULL OR trim($rr['Remarks']) == '')
		$rr['Remarks'] = 'PO#'. ltrim($rr['PurchaseOrderNo'],'0');
		
	// $vendor_code = get_supplier_id_by_supp_ref($rr['VendorCode']); //some did not match dont use this!
	$po_header = get_po_header_by_reference($rr['PurchaseOrderNo']);
	$vendor_code = $po_header['supplier_id'];
	// display_error($vendor_code);die;
	$sql = "INSERT INTO ".TB_PREF."grn_batch (supplier_id, purch_order_no, reference, source_invoice_no, delivery_date, loc_code, rcomments)
				VALUES (".
				// $rr['ReceivingID'] .",".
				$vendor_code .",".
				$po_header['order_no'] .",".
				db_escape($rr['ReceivingNo']) .",".
				db_escape($rr['Remarks']) .",'".
				date2sql(mssql2date($rr['DateReceived']))."',".
				db_escape($rr['DeliverTo']) .",'')";
	db_query($sql,'failed to import receiving header');
	
	return db_insert_id();
}

function get_aria_po_id($reference)
{
	$sql = "SELECT order_no FROM ".TB_PREF."purch_orders
			WHERE reference = '$reference'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_po_item_id($order_no, $item_code)
{
	$sql = "SELECT po_detail_item, unit_price  FROM ".TB_PREF."purch_order_details
				WHERE order_no=$order_no
				AND item_code = ". db_escape($item_code);
	// display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function import_rr_items($rr,$rr_id)
{
	$sql = "SELECT * FROM ReceivingLine WHERE ReceivingID = ".$rr['ReceivingID'];
	$res = ms_db_query($sql);
	
	while($row = mssql_fetch_array($res))
	{
		if ($row['qty'] == 0)
			continue;
		
		$po_id = get_aria_po_id($rr['PurchaseOrderNo']);
		$po_detail_item_row = get_po_item_id($po_id, $row['ProductID']);
		$date_ = mssql2date($rr['DateCreated']);
		
		$po_detail_item = $po_detail_item_row[0];
		
		if ($po_detail_item == '')
		{
			return array($rr['PurchaseOrderNo'],$rr['ReceivingID']) ;
		}
		// display_error('RR '. $row['extended']);
		$sql = "INSERT INTO ".TB_PREF."grn_items (grn_batch_id, po_detail_item, item_code, description, r_uom, multiplier, 
				qty_recd, qty_recd_pcs, extended)
		VALUES (".$rr_id.", ".$po_detail_item.", ".db_escape($row['ProductID']).", ".db_escape($row['Description']) .", '"
		.$row['UOM']."',"
		.$row['pack'].","
		.$row['qty'].","
		.$row['qty'] * $row['pack'] .","
		.round($row['extended'],2) .")";
		// display_error($sql);
		db_query($sql, "A GRN detail item could not be inserted for PO#.".$rr['PurchaseOrderNo']);
		
		$sql = "UPDATE ".TB_PREF."purch_order_details
		SET quantity_received = ((quantity_received + " .($row['qty'] * $row['pack']).") / multiplier),
		 quantity_received_pcs = quantity_received_pcs + ".$row['qty'] * $row['pack']."
		WHERE po_detail_item = ".db_escape($po_detail_item);
		db_query($sql, "a purchase order details record could not be updated. PO# " . $rr['PurchaseOrderNo']);
		
		// $standard_cost = get_standard_cost($row['ProductID']);
		$standard_cost = $po_detail_item_row[1];
		$vendor_code = get_supplier_id_by_po_no($rr['PurchaseOrderNo']);
		
		add_stock_move(ST_SUPPRECEIVE, $row['ProductID'], $rr_id, $rr['DeliverTo'], $date_, "",
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
	
	$rr_id = import_rr_header($rr);
	
	if ($rr_id)
	{
	
		$good = import_rr_items($rr,$rr_id);
		
		$Refs->save(ST_SUPPRECEIVE, $rr_id, $rr['ReceivingNo']);

		add_audit_trail(ST_SUPPRECEIVE, $rr_id, mssql2date($rr['DateReceived']),'import RR');
		
		// display_notification('imported Receiving No. : '.$rr['ReceivingNo'].' to APV');
		
		return $rr_id;
	}
}

function import_po($po_no)
{
	$po_no = str_pad($po_no, 10, "0", STR_PAD_LEFT);
	begin_transaction();
	$sql = "SELECT *
			FROM PurchaseOrder 
			WHERE PurchaseOrderNo = '$po_no'";

	$res = ms_db_query($sql);
	$po_row = mssql_fetch_array($res);
	
	if (trim($po_row['PurchaseOrderNo']) == '')
	{
		cancel_transaction();
		return false;
	}
	
	$po_id = import_po_header($po_row);
	
	if (!$po_id)
			return false;
			
	
	$i_sql = "SELECT * FROM PurchaseOrderLine
				WHERE PurchaseOrderID=".$po_row['PurchaseOrderID']."
				AND ProductID != 0";
	$i_res = ms_db_query($i_sql);
	
	$ret = true;
	$no_import_errors = array();
	while($item_row = mssql_fetch_array($i_res))
	{
		$ret = import_po_item($po_id, $item_row);
		
		if ($ret === false)
		{
		
			$no_import_errors[] = array($po_row['PurchaseOrderID'],$po_row['PurchaseOrderNo'],
				"Product ID: ".$item_row['ProductID']." Product Description:".$item_row['Description']." is not in the products (MSSQL) table");
			// $er_msg ="Failed to import PO # ".$po_row['PurchaseOrderNo']." because item with product id: ".$item_row['ProductID'].
				// " Product Description:".$item_row['Description']."is not in the products table. Please add the product to import PO</br>"; 
			break;
		}
	}
	if ($ret === false)
	{
		cancel_transaction();
		return $no_import_errors;
	}
	else
	{
		commit_transaction();
		return true;
	}
}

function import_new_po($po_no) // for point 1 PO
{

	begin_transaction();
	//===============================================================================
	$sql = "SELECT a.*
				FROM srs.purch_orders a, srs.refs b
				WHERE a.trans_type=16
				AND a.`status` = 0
				AND a.order_no = b.trans_id
				AND a.trans_type = b.trans_type
				AND b.reference = ".db_escape($po_no);
	// display_error($sql);
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
	{
		cancel_transaction();
		return false;
	}
	$po_row = db_fetch($res);
	
	$po_id = import_new_po_header($po_row, $po_no);
	
	if (!$po_id)
			return false;
			
	
	$sql = "SELECT * FROM srs.purch_order_details  WHERE order_no = ".$po_row['order_no']." AND trans_type=16";
	$i_res = db_query($sql);
	
	$ret = true;
	$no_import_errors = array();
	while($item_row = db_fetch($i_res))
	{
		$ret = import_new_po_item($po_id, $item_row,$po_no);
		
		if ($ret === false)
		{
		
			$no_import_errors[] = array('new PO',$po_no,
				"Product ID: ".$item_row['ProductID']." Product Description:".$item_row['description']." is not in the products (MSSQL) table");
			// $er_msg ="Failed to import PO # ".$po_row['PurchaseOrderNo']." because item with product id: ".$item_row['ProductID'].
				// " Product Description:".$item_row['Description']."is not in the products table. Please add the product to import PO</br>"; 
			break;
		}
	}
	if ($ret === false)
	{
		cancel_transaction();
		return $no_import_errors;
	}
	else
	{
		commit_transaction();
		return true;
	}
}

function import_open_po($rr_header) // for open PO
{

	begin_transaction();
	//===============================================================================
	
	$po_id = import_open_po_header($rr_header);
	
	if (!$po_id)
			return false;
			
	
	$sql = "SELECT * FROM ReceivingLine WHERE ReceivingID = ".$rr_header['ReceivingID'];
	$res = ms_db_query($sql);
	
	$ret = true;
	$no_import_errors = array();

	while($item_row = mssql_fetch_array($res))
	{
	
		$ret = import_open_po_item($po_id, $item_row);
		
		if ($ret === false)
		{
		
			$no_import_errors[] = array('new PO',$po_no,
				"Product ID: ".$item_row['ProductID']." Product Description:".$item_row['description']." is not in the products (MSSQL) table");
			// $er_msg ="Failed to import PO # ".$po_row['PurchaseOrderNo']." because item with product id: ".$item_row['ProductID'].
				// " Product Description:".$item_row['Description']."is not in the products table. Please add the product to import PO</br>"; 
			break;
		}
	}
	if ($ret === false)
	{
		cancel_transaction();
		return $no_import_errors;
	}
	else
	{
		commit_transaction();
		return true;
	}
}

function import_new_po_header($po,$po_no)
{
	global $Refs, $import_errors;
	
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
				WHERE reference = '$po_no'";
	$res = db_query($sql);
	
	if (db_num_rows($res) != 0)
	{
		$po_row = db_fetch($res);
		$sql = "SELECT * FROM ".TB_PREF."purch_order_details
				WHERE order_no = ".$po_row['order_no'];
		$res = db_query($sql);
		if (db_num_rows($res) > 0)
			return false;
		else
			return $po_row['order_no'];
	}
	// $supp_id = check_my_suppliers($po['VendorCode']);
	
	$sql = "SELECT memo_ FROM srs.comments WHERE trans_type=16 AND id = ".$po['order_no'];
	$res = db_query($sql);
	$row = db_fetch($res);
	$comment = $row[0];
	$supp_id = check_my_suppliers($po['supplier_id']);
	
	if ($supp_id === false)
	{
		$import_errors[] = array('new PO',$po_no,"vendor code: ". $po['supplier_id'] ." not in vendor (MSSQL) table");
		
		return false;
	}
		
	$po['DeliverTo'] = 1; // change to branch code in centralized aria
	
	
	$sql = "INSERT INTO ".TB_PREF."purch_orders (supplier_id, comments, ord_date, reference, requisition_no, 
				into_stock_location, delivery_address, is_approve, date_approve, approving_officer)
				VALUES (".
				// $po['PurchaseOrderID'].",". 
				$supp_id.",". 
				db_escape($comment).",'".
				$po['trans_date']."',".
				db_escape($po_no).",".
				db_escape($po['trans_ref']).",".
				$po['DeliverTo'].",".
				db_escape($po['delivery_address']).",
				1,'".
				date2sql(Today())."',".
				db_escape($_SESSION["wa_current_user"]->username).")";
	
	db_query($sql,'failed to import header');
	
	$po_id = db_insert_id();
	$Refs->save(ST_PURCHORDER, $po_id, $po_no);

	add_audit_trail(ST_PURCHORDER, $po_id, sql2date($po['trans_date']),'import PO');
	
	return $po_id;
}

function import_open_po_header($rr_row)
{
	global $Refs, $import_errors;
	
	$sql = "SELECT * FROM ".TB_PREF."purch_orders
				WHERE reference = '".$rr_row['PurchaseOrderNo']."'";
	$res = db_query($sql);
	
	if (db_num_rows($res) != 0)
	{
		$po_row = db_fetch($res);
		$sql = "SELECT * FROM ".TB_PREF."purch_order_details
				WHERE order_no = ".$po_row['order_no'];
		$res = db_query($sql);
		if (db_num_rows($res) > 0)
			return false;
		else
			return $po_row['order_no'];
	}
	
	$supp_id = check_my_suppliers($rr_row['VendorCode']);
	
	$comment = '';
	
	if ($supp_id === false)
	{
		$import_errors[] = array('new PO',$rr_row['PurchaseOrderNo'],"vendor code: ". $rr_row['VendorCode'] ." not in vendor (MSSQL) table");
		display_error("PO# " . $rr_row['PurchaseOrderNo'] . "vendor code: ". $rr_row['VendorCode'] ." not in vendor (MSSQL) table");die;
		return false;
	}
		
	$po['DeliverTo'] = 1; // change to branch code in centralized aria
	
	
	$rr_date = date2sql(mssql2date($rr_row['DateReceived']));
	
	$sql = "INSERT INTO ".TB_PREF."purch_orders (supplier_id, comments, ord_date, reference, requisition_no, 
				into_stock_location, delivery_address, is_approve, date_approve, approving_officer)
				VALUES (".
				// $po['PurchaseOrderID'].",". 
				$supp_id.",". 
				db_escape($comment).",'".
				$rr_date."',".
				db_escape($rr_row['PurchaseOrderNo']).",'',".
				$po['DeliverTo'].",".
				db_escape($rr_row['DeliveryDescription']).",
				1,'".
				$rr_date."',".
				db_escape($_SESSION["wa_current_user"]->username).")";
	
	db_query($sql,'failed to import header');
	
	$po_id = db_insert_id();
	$Refs->save(ST_PURCHORDER, $po_id, $rr_row['PurchaseOrderNo']);

	add_audit_trail(ST_PURCHORDER, $po_id, sql2date($rr_date),'import PO');
	
	return $po_id;
}

function get_discounts_details($disc_code)
{
	$sql = "SELECT [Amount], [Percent], [Plus] FROM Discounts WHERE DiscountCode = '$disc_code'";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row;
}

function import_new_po_item($po_id,$line, $po_no='')
{	
	$DiscountCode1 = 
	$DiscountCode2 = 
	$DiscountCode3 = '';
	
	$DiscAmount1 = 
	$DiscAmount2 = 
	$DiscAmount3 = '.0000';
	
	$Percent1 = 
	$Percent2 = 
	$Percent3 = '0';
	
	$DiscPlus1 = 
	$DiscPlus2 = 
	$DiscPlus3 = '0';
	
	$disc_percent1 = 
	$disc_percent2 = 
	$disc_percent3 = 
	$disc_amount1 =
	$disc_amount2 =
	$disc_amount3 = 0;
	
	$discs = explode(',',$line['discounts']);
	
	$cc = 0;

	$extended_price = ($line['unit_price']);
	foreach($discs as $disc_code_amt)
	{
		$cc++;
		
		list($disc_code,$amt) = explode('=>',$disc_code_amt);
		
		if ($amt == 0)
			continue;
		
		$disc_details = get_discounts_details($disc_code);
		$deduc = 0;
		
		if ($cc == 1)
		{
			$DiscountCode1 = $disc_code;
			$DiscAmount1 = $disc_details[0];
			$Percent1 = $disc_details[1];
			$DiscPlus1 = $disc_details[2];
			
			if($Percent1 == 1){
				$deduc = ($DiscAmount1/100) * $extended_price;
				$disc_percent1 = $DiscAmount1;
			}
			else{
				$deduc = $DiscAmount1;
				$disc_amount1 = $DiscAmount1;
			}

			if($DiscPlus1 == 1){
				$extended_price += $deduc;
				$disc_percent1 = $disc_percent1 * (-1);
				$disc_amount1 = $disc_amount1 * (-1);
			}
			else{
				$extended_price -= $deduc;
			}
		}
		else if ($cc == 2)
		{
			$DiscountCode2 = $disc_code;
			$DiscAmount2 = $disc_details[0];
			$Percent2 = $disc_details[1];
			$DiscPlus2 = $disc_details[2];
			
			if($Percent2 == 1){
				$deduc = ($DiscAmount2/100) * $extended_price;
				$disc_percent2 = $DiscAmount2;
			}
			else{
				$deduc = $DiscAmount2;
				$disc_amount2 = $DiscAmount2;
			}

			if($DiscPlus2 == 1){
				$extended_price += $deduc;
				$disc_percent2 = $disc_percent2 * (-1);
				$disc_amount2 = $disc_amount2 * (-1);
			}
			else{
				$extended_price -= $deduc;
			}
		}
		else if ($cc == 3)
		{
			$DiscountCode3 = $disc_code;
			$DiscAmount3 = $disc_details[0];
			$Percent3 = $disc_details[1];
			$DiscPlus3 = $disc_details[2];
			
			if($Percent3 == 1){
				$deduc = ($DiscAmount3/100) * $extended_price;
				$disc_percent3 = $DiscAmount3;
			}
			else{
				$deduc = $DiscAmount3;
				$disc_amount3 = $DiscAmount3;
			}

			if($DiscPlus3 == 1){
				$extended_price += $deduc;
				$disc_percent3 = $disc_percent3 * (-1);
				$disc_amount3 = $disc_amount3 * (-1);
			}
			else{
				$extended_price -= $deduc;
			}
		}
	}
	
	$extended_price = $extended_price * $line['ord_qty'];

	$item_code = check_my_items($line,$po_no);	
	
	
	if ($item_code === false)
	{
		return true;
	}
	
	$line['pack'] = get_uom_pack($line['unit_id']);
	$sql = "INSERT INTO ".TB_PREF."purch_order_details (order_no, item_code, description, p_uom, multiplier,
					qty_invoiced, unit_price, act_price, quantity_ordered , quantity_ordered_pcs,
					disc_percent1, disc_percent2, disc_percent3, 
					disc_amount1, disc_amount2, disc_amount3,
					extended)
				VALUES (".
				$po_id.",". 
				$item_code.",". 
				db_escape($line['description']).",". 
				db_escape($line['unit_id']).",". $line['pack'].",".
				"0,". 
				$line['unit_price'].",". 
				"0,". 
				$line['ord_qty'].",".($line['ord_qty']*$line['pack']).",
				$disc_percent1, $disc_percent2, $disc_percent3, 
				$disc_amount1, $disc_amount2, $disc_amount3,".
				$extended_price.")";
	// display_error($sql);die;
	db_query($sql,'failed to import detail - NEW PO: PO# ' . $po_no .' ~ '.$sql);
	
	return true;
}

function import_open_po_item($po_id,$line)
{	

	$item_code = check_my_items($line);	
	
	
	if ($item_code === false)
	{
		return true;
	}

	$sql = "INSERT INTO ".TB_PREF."purch_order_details (order_no, item_code, description, p_uom, multiplier,
					qty_invoiced, unit_price, act_price, quantity_ordered , quantity_ordered_pcs,
					disc_percent1, disc_percent2, disc_percent3, 
					disc_amount1, disc_amount2, disc_amount3,
					extended)
				VALUES (".
				$po_id.",". 
				$item_code.",". 
				db_escape($line['Description']).",". 
				db_escape($line['UOM']).",". $line['pack'].",".
				"0,". 
				$line['unitcost'].",". 
				"0,". 
				$line['qty'].",".($line['qty']*$line['pack']).",
				0, 0, 0, 
				0, 0, 0,".
				$line['extended'].")";
	db_query($sql,'failed to import detail - open PO');
	
	return true;
}

function get_uom_pack($uom_id)
{
	$sql = "SELECT Qty FROM UOM WHERE UOM = '$uom_id'";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	// display_error($row[0] .'<<< QTY');
	return $row[0];
}


function check_supp_trans_ref($ref,$type=20)
{
	$sql = "SELECT * FROM ".TB_PREF."supp_trans WHERE type=$type AND reference=".db_escape($ref);
	$res = db_query($sql);
	
	if(db_num_rows($res) == 0)
		return $ref;
	else
	{
		foreach(range('A', 'Z') as $letter)
		{
			$sql = "SELECT * FROM ".TB_PREF."supp_trans WHERE type=$type AND reference=".db_escape($ref.$letter);
			$res = db_query($sql);
			
			if (db_num_rows($res) == 0)
				return $ref;
		}
	}
		
	
}
//===========================START OF RECEIVING DISCREPANCY====================================
function getPonum_from_purch_orders($grn_purch_order_no){
	$sql = "SELECT reference
				FROM  0_purch_orders
				WHERE order_no = $grn_purch_order_no";
	$query = db_query($sql);
	$row = db_fetch($query);
	return $row['reference'];
}

function getReceiving_inv_amount($po_number,$grn_reference){
	$sql = "SELECT id,inv_amount,inv_no
			FROM  receiving_new.0_receiving
			WHERE po_no = '".$po_number."'
			AND rr_no=".db_escape($grn_reference+0)."";
			//display_error($sql);
	$query = db_query($sql);
	$row = db_fetch($query);
	$id=$row['id'];
	$invoice_amount=$row['inv_amount'];
	$invoice_no=$row['inv_no'];
	
	// display_error($id);
	// display_error($invoice_amount);
	return array($id, $invoice_amount,$invoice_no);
}

function insert_receiving_discrepancy($grn_supp_id,$invoice_no,$receiving_id,$rr_id,$grn_purch_order_no,$po_number,$grn_reference,$invoice_amount,$po_amount,$invoice_num)
{
$sql = "INSERT INTO ".TB_PREF."receiving_discrepancy(supp_id,supp_trans_id,receiving_id,grn_id,purch_order_id,po_num,rr_reference,invoice_amount,po_amount,invoice_no,is_resolved)
VALUES (".$grn_supp_id.",".$invoice_no.",".$receiving_id.",".$rr_id.",".$grn_purch_order_no.",'".$po_number."',".$grn_reference.",".$invoice_amount.",".$po_amount.",".db_escape($invoice_num).",'0')";
db_query($sql,'failed to insert receiving discrepancy.');
//$discrepancy_id = db_insert_id();
	// $sql = "SELECT * FROM ".TB_PREF."grn_items	WHERE grn_batch_id='$rr_id'";
	// $res = db_query($sql);
	
			// while ($row = db_fetch($res))
			// {
			// $sql = "INSERT INTO ".TB_PREF."receiving_discrepancy_items(discrepancy_id,grn_batch_id, grn_item_id, stock_id,old_price,new_price, temp, confirmed)
			// VALUES ($discrepancy_id,".$row['grn_batch_id'].",".$row['id'].",".$row['item_code'].",0,0, ". ($temp + 0) .", ". ($confirmed + 0) .")";
			// db_query($sql,'failed to insert discrepancy details');
			// }
}

//===========================END OF RECEIVING DISCREPANCY====================================

//THIS IS UPDATED LAST FRIDAY 11/24/2015
function create_apv_from_rr($rr_id, $advances=0, $type=null)
{
	$sql = "SELECT MIN(b.id), b.supplier_id
			FROM 0_grn_items a , 0_grn_batch b
			WHERE quantity_inv_pcs < qty_recd_pcs
			AND a.grn_batch_id = b.id 
			AND b.source_invoice_no != 'NULL'
			AND b.source_invoice_no != ''
			AND b.delivery_date >= '2016-01-01'
			AND b.id = $rr_id
			GROUP BY b.source_invoice_no";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;
		
	$grn_header = get_grn_batch($rr_id);
	// $_POST['supp_reference'] = $grn_header['source_invoice_no'];
		
	//$grn_reference=$grn_header['reference'];
	//$grn_purch_order_no=$grn_header['purch_order_no'];
	//$grn_supp_id=$grn_header['supplier_id'];
		
	$supp_trans = new supp_trans;
	$supp_trans->is_invoice = true;
	
	$supp_trans->reference = check_supp_trans_ref(ltrim($grn_header['reference'],'0'));
	$supp_trans->tran_date = $supp_trans->del_date = sql2date($grn_header['delivery_date']);
	$supp_trans->supp_reference = $grn_header['source_invoice_no'];	
	$supp_trans->clear_items();
	read_supplier_details_to_trans($supp_trans, $grn_header['supplier_id']);
	// $supp_trans->tran_date = Today();
	
	$supp_trans->special_reference = ltrim(get_reference(ST_PURCHORDER , $grn_header['purch_order_no']),'0');
	
	$result = get_grn_items($rr_id, $supp_trans->supplier_id, true,false, 0, "", "", $rr_id,$grn_header['source_invoice_no'],0, true);
	
	$ov_amt = $supp_trans->item_count = 0;
    while ($myrow = db_fetch($result))
    {
		$grn_already_on_invoice = false;

		$supp_trans->item_count ++;
	
		$n = $myrow["id"];
	
		$dec2 = 10; 		
		$chgprce_ = $myrow["rr_extended"]/$myrow['qty_recd'];
		$ov_amt += $myrow["rr_extended"];
		// display_error($myrow["rr_extended"]);
		 
		// update_inv_item_details($myrow['item_code']);
		// display_error($chgprce_ * ($myrow["qty_recd"] - $myrow["quantity_inv"]));
		$supp_trans->add_grn_to_trans($n, $myrow['po_detail_item'],
				$myrow['item_code'], $myrow['description'], $myrow['qty_recd'],
				$myrow['prev_quantity_inv'], round2($myrow["qty_recd"] - $myrow["quantity_inv"], $dec2),
				$myrow['real_price'], round($myrow['real_price'],$dec2), 1,
				$myrow['std_cost_unit'], "",$myrow['r_uom'],$myrow['multiplier'], 
				array('source_invoice_no' => $myrow['source_invoice_no'],
						'purch_order_no' => $myrow['purch_order_no'],
						'delivery_date' => $myrow['delivery_date']));
    }
	$supp_trans->ov_amount = $supp_trans->gl_amount = $supp_trans->ov_nv = $supp_trans->ov_discount = 0;/* for starters */
	
	if (count($supp_trans->grn_items) > 0)
	{
		// foreach ( $supp_trans->grn_items as $grn)
		// {
			// $supp_trans->ov_amount += round2(($grn->this_quantity_inv * $grn->chg_price),4);
		// }
		$supp_trans->ov_amount = $ov_amt;
	}
	if (count($supp_trans->gl_codes) > 0)
	{
		foreach ( $supp_trans->gl_codes as $gl_line)
		{
			////////// 2009-08-18 Joe Hunt
			// if (!is_tax_account($gl_line->gl_code))
				$supp_trans->gl_amount += $gl_line->amount;
		}
	}
	
	// $supp_trans->ov_nv = $supp_trans->get_non_vat_item_total();
	
	// if ($supp_trans->tax_group_id != 1) //transfer all to non vat
	// {
		// $supp_trans->ov_nv = $supp_trans->ov_amount;
	// }
	
	//---------------------------------------------------------
	$vat_inc = true;
	$dim = get_company_pref('use_dimension');
 	$colspan = ($dim == 2 ? 7 : ($dim == 1 ? 6 : 5));
	
	// echo "<span style='display:none;'>";
	// $_taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
    // $_tax_total = display_edit_tax_items($_taxes, $colspan, $vat_inc);
	// echo "</span>";
	
	
	$supp_trans->purch_discount = $p_nv_disc = $p_v_disc = $vat_disc = 0;
	// $p_vat = abs($supp_trans->ov_amount - $_tax_total - $supp_trans->ov_nv);
	
	// $supp_trans->add_to_ov_amount = 0;
	// $display_total = number_format2($supp_trans->ov_amount+$supp_trans->purch_discount,2);
	// $supp_trans->vat = $_tax_total + $vat_disc;
	
	// if (count($_taxes) == 0)
	// {

		// $supp_trans->ov_nv += $p_vat;
		// $p_vat = $supp_trans->purch_vat = 0;
	// }
	
	$supp_trans->purch_non_vat = $supp_trans->ov_amount;
	$p_vat = $supp_trans->vat = 0;
	
	if ($supp_trans->tax_group_id == 1)
	{
		$supp_trans->purch_non_vat = $supp_trans->ov_nv= 0;
		$p_vat = round(($supp_trans->ov_amount/1.12),2);
		$supp_trans->vat = $supp_trans->ov_amount - $p_vat;
	}
	
	$supp_trans->purch_vat = round2($p_vat,2);
	
	$supp_trans->ewt = $supp_trans->ewt_percent = $ewt_p = 0;
	
	if (apply_ewt_to_supplier($supp_trans->supplier_id))
		$supp_trans->ewt_percent = $ewt_p = get_company_pref('ewt_percent');
				
	if ($ewt_p > 0)
		$supp_trans->ewt = round2(($supp_trans->purch_non_vat + $supp_trans->purch_vat) * ($ewt_p/100),2);
	
	$supp_trans->acounts_payable = round2($supp_trans->ov_amount - $supp_trans->ewt + $supp_trans->gl_amount,2);
	
	if(round($supp_trans->purch_non_vat + $supp_trans->purch_vat + $supp_trans->vat,2)
		== round($supp_trans->acounts_payable + $supp_trans->ewt,2))
		// display_error('no discount GL');
	{
		$supp_trans->purch_discount = 0;
		$supp_trans->purch_ret = 0;
	}
	
	if ($_POST['ret_disc'] == 1)
	{
		$supp_trans->purch_ret=$supp_trans->purch_discount;
		$supp_trans->purch_discount = 0;
	}

	// display_error('p non vat: '.$supp_trans->purch_non_vat);
	// display_error('p vat: '.$supp_trans->purch_vat);
	// display_error('vat: '.$supp_trans->vat);
	// display_error('-----------------------------');
	// display_error('discount: '.$supp_trans->purch_discount);
	// display_error('purch_ret: '.$supp_trans->purch_ret);
	// display_error('disp_allow: '.$supp_trans->disp_allow);
	// display_error('trade_promo: '.$supp_trans->trade_promo);
	// display_error('rebate: '.$supp_trans->rebate);
	
	// display_error('ewt: '.$supp_trans->ewt);
	// display_error('ap : '.$supp_trans->acounts_payable);
	
	// // display_error($supp_trans->purch_non_vat + $supp_trans->purch_vat + $supp_trans->ewt);
	// display_error($supp_trans->purch_non_vat + $supp_trans->purch_vat + $supp_trans->vat);
	// var_dump($supp_trans);
	// die;
	
	// display_error($rr_id, $type);
	$invoice_no = add_supp_invoice_new($supp_trans,0,$advances, $type);

	//=================================CHECK RECEIVING DISCREPANCY
	/*$ov_amt =round($ov_amt,2);
	$tag_dis=0;
	$po_number=getPonum_from_purch_orders($grn_purch_order_no);
	
	if (substr($po_number,0,2) != 'OP'){
		$result=getReceiving_inv_amount($po_number,$grn_reference);
		$receiving_id=$result[0];
		$invoice_amount=$result[1];
		$invoice_num=$result[2];
		
		// display_error($receiving_id);
		// display_error($invoice_amount);
		// display_error($ov_amt);
		
		$tag_dis=0;
		if($invoice_amount<$ov_amt){
			$discrepancy=$ov_amt-$invoice_amount;
			
			if ($discrepancy>5){
			insert_receiving_discrepancy($grn_supp_id,$invoice_no,$receiving_id,$rr_id,$grn_purch_order_no,$po_number,$grn_reference,$invoice_amount,$ov_amt,$invoice_num);
			$tag_dis=1;
			}
		}
	}
	*/
	//=================================

	// add import APV to CV here
	if ($type != 24)
	{
		$cv_id = 0;
		$cv_id = auto_apv_to_cv($invoice_no);
		if ($cv_id != 0)
			display_notification('imported Invoice '.$grn_header['source_invoice_no'].' to CV');
		else
			display_notification('imported Invoice '.$grn_header['source_invoice_no'].' to APV (debit memo > total AP)');
		
		return $cv_id;
		display_error('dd');
	}
	
}

function auto_apv_to_cv($invoice_no)
{
	$trans_no = $invoice_no;
	$type = 20;
	
	//get apv 
	$apv_header = get_apv_supp_trans($trans_no);
	$real_cv_trans[] = array(20, $trans_no, $apv_header['TotalAmount']);
	
	$payable_amount = $apv_header['TotalAmount'];
	$total_ewt_ex = 0;
	// $total_ewt_inc = 0;
	
	if ($apv_header['ewt'] > 0)
	{
		$total_ewt_ex += $apv_header['ewt'];
	}
	
	$dm_used = 0;
	
	// get PO specific DM here
	//------------------------
	
	$exlude_dm = array();
	// check for percentage DM here
	$current_apv_amount = $apv_header['TotalAmount'];
	$percent_dm_res = get_percent_dm($apv_header['supplier_id'],sql2date($apv_header['del_date']));
	while($percent_dm_row = db_fetch($percent_dm_res))
	{
		$percent_dm_amount = ($current_apv_amount+$apv_header['ewt']) * ($percent_dm_row['disc_percent']/100);
		$p_dm_trans_no = create_dm_from_percentage_sdma($percent_dm_row['id'],$percent_dm_amount,$apv_header['reference']);
		$exclude_dm[] = $p_dm_trans_no;
		
		$percent_dm_amount = -$percent_dm_amount;
		$real_cv_trans[] = array(53, $p_dm_trans_no, $percent_dm_amount);
		$payable_amount += $percent_dm_amount; //dm amount is negative
		$dm_used ++;
		
		$current_apv_amount += $percent_dm_amount;
	}
	//-------------------- 
	
	// get pending apv
	$p_apv_cm_res = get_pending_apv_and_cm($apv_header['supplier_id'],$trans_no);
	while ($p_apv_cm_row = db_fetch($p_apv_cm_res))
	{
		// get percentage DM here -- use del date from apv
		$real_cv_trans[] = array($p_apv_cm_row['type'], $p_apv_cm_row['trans_no'], $p_apv_cm_row['TotalAmount']);
		$payable_amount += $p_apv_cm_row['TotalAmount'];
		
		if ($p_apv_cm_row['ewt'] > 0)
		{
			$total_ewt_ex += $p_apv_cm_row['ewt'];
		}
		
		// check for percentage DM here
		$current_apv_amount = $p_apv_cm_row['TotalAmount'];
		$percent_dm_res = get_percent_dm($apv_header['supplier_id'],sql2date($p_apv_cm_row['del_date']));
		while($percent_dm_row = db_fetch($percent_dm_res))
		{
			$percent_dm_amount = ($current_apv_amount+$p_apv_cm_row['ewt']) * ($percent_dm_row['disc_percent']/100);
			$p_dm_trans_no = create_dm_from_percentage_sdma($percent_dm_row['id'],$percent_dm_amount,$percent_dm_row['reference']);
			$exclude_dm[] = $p_dm_trans_no;
			
			$percent_dm_amount = -$percent_dm_amount;
			$real_cv_trans[] = array(53, $p_dm_trans_no, $percent_dm_amount);
			$payable_amount += $percent_dm_amount; //dm amount is negative
			$dm_used ++;
			
			$current_apv_amount += $percent_dm_amount;
		}
		//-------------------- 
	}
	
	
	// get fixed price DM
	$dm_res = get_unused_dm_fixed_price($apv_header['supplier_id'],$exclude_dm);
	
	//compare total APV to DM 
	$dm_count = db_num_rows($dm_res);
	
	while($dm_row = db_fetch($dm_res))
	{
		//display_error("APV".$payable_amount);
		//display_error("DM".abs($dm_row['TotalAmount']));
		if ($payable_amount > abs($dm_row['TotalAmount']))
		{
			$real_cv_trans[] = array($dm_row['type'], $dm_row['trans_no'], $dm_row['TotalAmount']);
			//display_error($dm_row['TotalAmount']);
			$payable_amount += $dm_row['TotalAmount']; //dm amount is negative
			$dm_used ++;
		}
	}
	
	if ($dm_count > 0 AND $dm_used == 0) {
	return false;
	}//there are pending DM but payable amount < any of DM amount/s
	
	// else create a CV. not yet approved this time
	$cv_no = get_next_cv_no();
	
	//display_error("APV2".$payable_amount);
	$cv_id = insert_cv($cv_no,Today(),$payable_amount,PT_SUPPLIER,$apv_header['supplier_id'], 
	$real_cv_trans, sql2date($apv_header['due_date']), $total_ewt_ex);
		
	// CV approval ------------------------------- Remove comment for auto approve
	$sql = "UPDATE ".TB_PREF."cv_header SET approved = 1
			WHERE id = $cv_id";
	db_query($sql,'failed to approve CV');
	
	add_audit_trail(99, $cv_id, Today(), 'CV approved');
	//---------------------------------------------
	return $cv_id;
}


// echo $_SESSION["wa_current_user"]->company;

//===================================================
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
//===================================================
								//+++++++++++++++++++++++++++++++++++++++++++++++++INSERTED for cwo
								function auto_create_cv($invoice_no)
								{
									$trans_no = $invoice_no;
									$type = 20;
									
									//==============GET APV TYPE 20
									$apv_header = get_apv_supp_trans($trans_no);
									$real_cv_trans[] = array(20, $trans_no, $apv_header['TotalAmount']);
									
									$payable_amount = $apv_header['TotalAmount'];
									$total_ewt_ex = 0;
									
									if ($apv_header['ewt'] > 0)
									{
											$total_ewt_ex += $apv_header['ewt'];
									}
									
									$dm_used = 0;
									//========================
									
										// get PO specific DM here
										//------------------------
										$exlude_dm = array();
										// check for percentage DM here
										$current_apv_amount = $apv_header['TotalAmount'];
										$percent_dm_res = get_percent_dm($apv_header['supplier_id'],sql2date($apv_header['del_date']));
										while($percent_dm_row = db_fetch($percent_dm_res))
										{
										$percent_dm_amount = ($current_apv_amount+$apv_header['ewt']) * ($percent_dm_row['disc_percent']/100);
										$p_dm_trans_no = create_dm_from_percentage_sdma($percent_dm_row['id'],$percent_dm_amount,$apv_header['reference']);
										$exclude_dm[] = $p_dm_trans_no;

										$percent_dm_amount = -$percent_dm_amount;
										$real_cv_trans[] = array(53, $p_dm_trans_no, $percent_dm_amount);
										$payable_amount += $percent_dm_amount; //dm amount is negative
										$dm_used ++;

										$current_apv_amount += $percent_dm_amount;
										}
										//-------------------- 

										//==============================GET CREDIT MEMOs
										$p_apv_cm_res = get_pending_apv_and_cm($apv_header['supplier_id'],$trans_no);
										while ($p_apv_cm_row = db_fetch($p_apv_cm_res))
										{
										// get percentage DM here -- use del date from apv
										$real_cv_trans[] = array($p_apv_cm_row['type'], $p_apv_cm_row['trans_no'], $p_apv_cm_row['TotalAmount']);
										$payable_amount += $p_apv_cm_row['TotalAmount'];

										if ($p_apv_cm_row['ewt'] > 0)
										{
											$total_ewt_ex += $p_apv_cm_row['ewt'];
										}

										// check for percentage DM here
										$current_apv_amount = $p_apv_cm_row['TotalAmount'];
										$percent_dm_res = get_percent_dm($apv_header['supplier_id'],sql2date($p_apv_cm_row['del_date']));
										while($percent_dm_row = db_fetch($percent_dm_res))
										{
										$percent_dm_amount = ($current_apv_amount+$p_apv_cm_row['ewt']) * ($percent_dm_row['disc_percent']/100);
										$p_dm_trans_no = create_dm_from_percentage_sdma($percent_dm_row['id'],$percent_dm_amount,$percent_dm_row['reference']);
										$exclude_dm[] = $p_dm_trans_no;

										$percent_dm_amount = -$percent_dm_amount;
										$real_cv_trans[] = array(53, $p_dm_trans_no, $percent_dm_amount);
										$payable_amount += $percent_dm_amount; //dm amount is negative
										$dm_used ++;

										$current_apv_amount += $percent_dm_amount;
										}
										//-------------------- 
										}
										//========================================================
								
										// ================GET DEBIT MEMOs
										$dm_res = get_unused_dm_fixed_price($apv_header['supplier_id'],$exclude_dm);

										//compare total APV to DM 
										$dm_count = db_num_rows($dm_res);

										while($dm_row = db_fetch($dm_res))
										{
										if ($payable_amount > abs($dm_row['TotalAmount']))
										{
										$real_cv_trans[] = array($dm_row['type'], $dm_row['trans_no'], $dm_row['TotalAmount']);
										$payable_amount += $dm_row['TotalAmount']; //dm amount is negative
										$dm_used ++;
										}
										}

										if ($dm_count > 0 AND $dm_used == 0) //{//there are pending DM but payable amount < any of DM amount/s
										return false;
										// else create a CV. not yet approved this time
										//===============================================
		
									
									//=======AUTO CREATE CV
									$cv_no = get_next_cv_no();
									
									$cv_id = insert_cv($cv_no,Today(),$payable_amount,PT_SUPPLIER,$apv_header['supplier_id'], 
										$real_cv_trans, sql2date($apv_header['due_date']), $total_ewt_ex);
										
									//=======CV approval auto approve
									$sql = "UPDATE ".TB_PREF."cv_header SET approved = 1
											WHERE id = $cv_id";
									db_query($sql,'failed to approve CV');
									
									add_audit_trail(99, $cv_id, Today(), 'CV approved');
									
									return $cv_id;
								//}
								}
								//++++++++++++++++++++++++++++++++++++++++++++++++++END
//**INSERT ON HOLD CV**//								
function insert_cv_on_hold($cv_id, $remarks){
	$sql = "INSERT INTO ".TB_PREF."cv_on_hold(cv_id, remarks) VALUES (".$cv_id.",".db_escape($remarks).")";
	db_query($sql,'failed to insert cv on hold.'. $sql);
	
}

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
	{
		meta_forward($_SERVER['PHP_SELF'], "startdate=".$_POST['OrdersAfterDate']."&nmhgsdrdntgdsad=zxcnnp". (6*$date_[0])*(6*$date_[1]).'fsdlaa'.(6*$date_[2]).'dfsbyu');
		exit;
	}
	
	if (isset($_GET['startdate']))
		//$_POST['OrdersAfterDate'] = add_days(Today(), -8);
$_POST['OrdersAfterDate'] = '01/01/2016';
	
	
	if ($_SESSION["wa_current_user"]->company == 15) // for resto
	{	
		// $_POST['OrdersAfterDate'] = add_days(Today(), -21);
		$_POST['OrdersAfterDate'] = '01/01/2016';
	}

	// $_POST['OrdersAfterDate'] = '07/31/2015';
	
	// confirm all RS to DM
	$sql= "SELECT a.*, SUM(b.qty*b.price) as Total 
			FROM 0_rms_header a, 0_rms_items b 
			WHERE a.bo_processed_date >= '2016-01-01' 
			AND a.processed = 1 AND a.movement_type = 'R2SSA' 
			AND a.trans_no = 0 
			AND a.rs_id = b.rs_id 
			GROUP BY b.rs_id 
			ORDER BY movement_type,movement_no, rs_id";
	$res = db_query_rs($sql);
	$rs_id_a = array();
	while($row = db_fetch($res))
	{
		$rs_id_a[0] = $row['rs_id'];
		begin_transaction();
			create_debit_memo_for_rs($rs_id_a);
		commit_transaction();
	}
	
	// die;
	
	
	$my_sql = "SELECT reference FROM 0_grn_batch WHERE delivery_date >= '".date2sql($_POST['OrdersAfterDate'])."'";
	$my_res = db_query($my_sql);
	
	$exclude_rrs = array();
	
	while($rec = db_fetch($my_res))
		$exclude_rrs[] = "'".$rec[0]."'";
	
	$sql = "SELECT * FROM Receiving 
				WHERE PostedDate >= '".date2sql($_POST['OrdersAfterDate'])."'
				AND Status = 2
				AND PurchaseOrderNo != ''";
	if (count($exclude_rrs) > 0)
		$sql .= " AND ReceivingNo NOT IN (".implode(",",$exclude_rrs).")";
	
	// $sql = "SELECT * FROM Receiving 
				// WHERE ReceivingID = 94970
				// AND Status = 2
				// AND PurchaseOrderNo != ''";
	// display_error($sql);die;
	$res = ms_db_query($sql);
	
	while($rr_row = mssql_fetch_array($res))
	{
		if ($rr_row['PurchaseOrderID'] == -1 AND $_SESSION["wa_current_user"]->company != 17)
			continue;
		
		if ($rr_row['PurchaseOrderID'] != 0 AND $rr_row['PurchaseOrderID'] != -1)	
			$no_import_errors = import_po($rr_row['PurchaseOrderNo']);
		else if ($rr_row['PurchaseOrderNo'] != '' AND substr($rr_row['PurchaseOrderNo'],0,2) == 'PO')
			$no_import_errors = import_new_po($rr_row['PurchaseOrderNo']);
		else if ($rr_row['PurchaseOrderNo'] != '' AND substr($rr_row['PurchaseOrderNo'],0,2) == 'OP')
			$no_import_errors = import_open_po($rr_row);
		// if ($no_import_errors === true) // no error
		// {
			$rr_good = '';
			
			if (!check_rr($rr_row['ReceivingNo']))
			{
				continue;
			}
			if (!po_imported_already($rr_row['PurchaseOrderNo']))
			{
				display_error("Failed to import Receiving No. ". $rr_row['ReceivingNo'].'. 
					Because PO # '.$rr_row['PurchaseOrderNo'].'could not be imported . (Check if cancelled in NEW PO)');
				continue;
			}
			
			begin_transaction();
			$rr_id = import_rr($rr_row);
			
			$orig_po_number = $rr_row['PurchaseOrderNo'];
			if ($rr_id > 0 AND !is_array($rr_id)) // rr id
			{
						//+++++++++++++++++++++++++++++++++++++++++++++++++INSERTED for cwo
						
				// if (is_numeric($rr_row['PurchaseOrderNo']))
				// {
				// $po_num=$rr_row['PurchaseOrderNo']+0;
				// }
				// else {
				// $po_num=$rr_row['PurchaseOrderNo'];
				// // $po_num = str_replace('PO','',$po_num);
				// }
				$po_num=$rr_row['PurchaseOrderNo'];
				
				//$rr_row['PurchaseOrderNo'] exist in cwo
				//get amount using $rr_id = grn_batch_id @grn_items then compare
				$sql = "SELECT * FROM ".TB_PREF."cwo_header WHERE TRIM(LEADING '0' FROM c_po_no) = TRIM(LEADING '0' FROM '$po_num') AND voided='0'";
				// display_error($sql);
				$result=db_query($sql);
				$row=db_fetch($result);
				$cwo_amount=round($row['amount'],2);
				$num_row=db_num_rows($result);
				$cv_id = 0; 		
				if ($num_row>0)
				{
								// display_error('PO# EXIST IN CWO');
								$sql = "select sum(extended) as grn_items_extended from ".TB_PREF."grn_items where grn_batch_id='".$rr_id."'";
								//display_error($sql);
								$result=db_query($sql);
								$row=db_fetch($result);
								$grn_amount=round($row['grn_items_extended'],2);
								
								//if over or equal payment
								if ($cwo_amount>=$grn_amount) {
									$cv_id = create_apv_from_rr($rr_id,$cwo_amount,24);
									
									//	if over create dm
									if ($cwo_amount>$grn_amount) 
									{
										$refref = $Refs->get_next(ST_SUPPDEBITMEMO);
										
										$sql = "select supplier_id,delivery_date from ".TB_PREF."grn_batch where id='".$rr_id."'";
										//display_error($sql);
										$result=db_query($sql);
										$row=db_fetch($result);
										$supplier_id=$row['supplier_id'];
										$delivery_date=$row['delivery_date'];
											
										$invoice_no = add_supp_trans(ST_SUPPDEBITMEMO, $supplier_id, Today(),add_days(Today(), 1),
										$refref,$supp_ref=$rr_row['PurchaseOrderNo'],-($cwo_amount-$grn_amount), 0, 0,"",0,0,sql2date($delivery_date),1,0,0);
										//$cv_id=auto_create_cv($invoice_no); //no creation of cv
										// $purchases_account = 5400; //purchases
										$accounts_payable = 2000; //accounts_payable
										$advances_to_supplier = 1440; // advances_to_supplier
			
										add_gl_trans_supplier(ST_SUPPDEBITMEMO, $invoice_no, sql2date($delivery_date), $accounts_payable, 0, 0,$cwo_amount-$grn_amount, $supplier_id, "", $rate);
										add_gl_trans_supplier(ST_SUPPDEBITMEMO, $invoice_no, sql2date($delivery_date), $advances_to_supplier, 0, 0,-($cwo_amount-$grn_amount), $supplier_id, "", $rate);
										$Refs->save(ST_SUPPDEBITMEMO, $invoice_no, $refref);
											
									}
								}
									
								else // if  short payment
								{
									create_apv_from_rr($rr_id,$cwo_amount,24);
									$cv_id = create_apv_from_rr($rr_id,$grn_amount-$cwo_amount);
									
								}
				}
				//+++++++++++++++++++++++++++++++++++++++++++++++++INSERTED for cwo	
				else 
				{ //original--
				// display_error('PO# NOT EXIST IN CWO');
					$cv_id = create_apv_from_rr($rr_id);// create apv from RR here\
				
				//----
				}
				commit_transaction();

			}
			else
			{
				display_error('ERROR at PO # '. $rr_id[0].' RECEIVING ID :'.$rr_id[1]);
				cancel_transaction();
			}
		// }
			if(trim($rr_row['DocumentMismatchRemarks']) != '' AND $cv_id != 0){
				insert_cv_on_hold($cv_id, $rr_row['DocumentMismatchRemarks']);
			}
				
	}
	
}
				
//===================================================


start_table("class='tablestyle_noborder'");
start_row();

date_cells(_("Beginning From :"), 'OrdersAfterDate');

submit_cells('ImportAll', "<b>Import All to APV/CV</b>",'',_('Import'));

end_row();

end_table(1);

end_form();
end_page();

?>
