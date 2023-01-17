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

$seconds = 30;
// if (isset($_GET['nmhgsdrdntgdsad']))
	// header('Refresh: '.$seconds);

$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/db/audit_trail_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Import Purchase Order and Receiving to APV"), false, false, "", $js);
set_time_limit(0);
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
				// tax_type_id  = ".($v_row['pVatable'] == 1 ? 1 : 2 ).", 
		
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
				$disc_percent1, $disc_percent2, $disc_percent3, 
				$disc_amount1, $disc_amount2, $disc_amount3,".
				$line['extended'].")";
	db_query($sql,'failed to import detail');
	
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
	if (trim($rr['Remarks']) == 'NULL' OR $rr['Remarks'] == NULL)
		$rr['Remarks'] = '';
		
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
		db_query($sql, "a purchase order details record could not be updated.");
		
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

function create_apv_from_rr($rr_id)
{
	$sql = "SELECT MIN(b.id), b.supplier_id
			FROM 0_grn_items a , 0_grn_batch b
			WHERE quantity_inv_pcs < qty_recd_pcs
			AND a.grn_batch_id = b.id 
			AND b.source_invoice_no != 'NULL'
			AND b.source_invoice_no != ''
			AND b.delivery_date >= '2013-01-01'
			AND b.id = $rr_id
			GROUP BY b.source_invoice_no";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;
		
	$grn_header = get_grn_batch($rr_id);
	// $_POST['supp_reference'] = $grn_header['source_invoice_no'];
		
	$supp_trans = new supp_trans;
	$supp_trans->is_invoice = true;
	
	$supp_trans->reference = check_supp_trans_ref(ltrim($grn_header['reference'],'0'));
	$supp_trans->tran_date = $supp_trans->del_date = sql2date($grn_header['delivery_date']);
	$supp_trans->supp_reference = $grn_header['source_invoice_no'];	
	$supp_trans->clear_items();
	read_supplier_details_to_trans($supp_trans, $grn_header['supplier_id']);
	$supp_trans->tran_date = Today();
	
	$supp_trans->special_reference = ltrim(get_reference(ST_PURCHORDER , $grn_header['purch_order_no']),'0');
	
	$result = get_grn_items(0, $supp_trans->supplier_id, true,false, 0, "", "", $rr_id,$grn_header['source_invoice_no'],0, true);
	
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
	
	$supp_trans->ov_nv = $supp_trans->get_non_vat_item_total();
	
	if ($supp_trans->tax_group_id != 1) //transfer all to non vat
	{
		$supp_trans->ov_nv = $supp_trans->ov_amount;
	}
	
	//---------------------------------------------------------
	$vat_inc = true;
	$dim = get_company_pref('use_dimension');
 	$colspan = ($dim == 2 ? 7 : ($dim == 1 ? 6 : 5));
	
	echo "<span style='display:none;'>";
	$_taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
    $_tax_total = display_edit_tax_items($_taxes, $colspan, $vat_inc);
	echo "</span>";
	
	
	$supp_trans->purch_discount = $p_nv_disc = $p_v_disc = $vat_disc = 0;
	$p_vat = abs($supp_trans->ov_amount - $_tax_total - $supp_trans->ov_nv);
	
	$supp_trans->add_to_ov_amount = 0;
	$display_total = number_format2($supp_trans->ov_amount+$supp_trans->purch_discount,2);
	$supp_trans->vat = $_tax_total + $vat_disc;
	
	if (count($_taxes) == 0)
	{

		$supp_trans->ov_nv += $p_vat;
		$p_vat = $supp_trans->purch_vat = 0;
	}
	
	$supp_trans->purch_vat = round2($p_vat,2);
	$supp_trans->purch_non_vat = $supp_trans->ov_nv;
	
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
	$invoice_no = add_supp_invoice_new($supp_trans);
	
	display_notification('imported Invoice '.$grn_header['source_invoice_no'].' to APV');
	
	// add import APV to CV here
	
	// commit_transaction();
	// die;
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
	{
		meta_forward($_SERVER['PHP_SELF'], "startdate=".$_POST['OrdersAfterDate']."&nmhgsdrdntgdsad=zxcnnp". (6*$date_[0])*(6*$date_[1]).'fsdlaa'.(6*$date_[2]).'dfsbyu');
		exit;
	}
	
	$_POST['OrdersAfterDate'] = $_GET['startdate'];
	// global $Ajax;
	
	// get all RR with trandate >= startdate START HERE ---------------------------------------------------------
	
	$sql = "SELECT * FROM Receiving 
				WHERE DateReceived >= '".date2sql($_POST['OrdersAfterDate'])."'
				AND Status = 2
				AND PurchaseOrderNo != ''";
				
	// $sql = "SELECT * FROM Receiving 
				// WHERE ReceivingID = 68000
				// AND Status = 2
				// AND PurchaseOrderNo != ''";
	// display_error($sql);die;
	$res = ms_db_query($sql);
	while($rr_row = mssql_fetch_array($res))
	{
		$no_import_errors = import_po($rr_row['PurchaseOrderNo']);
	
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
					Because PO # '.$rr_row['PurchaseOrderNo'].'could not be imported');
				continue;
			}
			
			begin_transaction();
			$rr_id = import_rr($rr_row);
			
			if ($rr_id > 0 AND !is_array($rr_id)) // rr id
			{
				create_apv_from_rr($rr_id);// create apv from RR here
				commit_transaction();
			}
			else
			{
				display_error('ERROR at PO # '. $rr_id[0].' RECEIVING ID :'.$rr_id[1]);
				cancel_transaction();
			}
		// }
	}
	
}

//===================================================


start_table("class='tablestyle_noborder'");
start_row();

date_cells(_("Beginning From :"), 'OrdersAfterDate');

submit_cells('ImportAll', "<b>Import All to APV</b>",'',_('Import'));

end_row();

end_table(1);

end_form();
end_page();

?>
