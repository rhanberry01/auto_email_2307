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
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/db/all_item_adjustments_db.inc");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

if(isset($_POST['btn_delete'])){
	echo $_POST['xheader_id'];
}
	
page(_($help_context = "Adjustment Inquiry"), false, false, "", $js);

$approve_add = find_submit('selected_id');
$posted_add = find_submit('posted_id');

function update_posted_adjustment_header($id, $branch_id) {
	$b = get_branch_by_id($branch_id);
	$sql = "UPDATE ".$b['aria_db'].".".TB_PREF."adjustment_header SET 
			a_temp_posted = '1',
			a_temp_posted_by = ".$_SESSION['wa_current_user']->user."
			WHERE a_trans_no ='$id'";
	db_query($sql,'failed to update movement (Adjustment Header)');
}

function update_purch_status($id, $branch_id) {
	$b = get_branch_by_id($branch_id);
	$sql = "UPDATE ".$b['aria_db'].".".TB_PREF."adjustment_header
			SET a_purchaser_approved = '1', 
			a_purchaser_approved_by = ".$_SESSION['wa_current_user']->user."
			WHERE a_trans_no = '$id'";
	db_query($sql, 'failed to update purchaser approve status (Adjustment Header)');
}

#extended total function
function get_stock_moves_total_by_branch($trans_no, $type, $branch_id) {
	$b = get_branch_by_id($branch_id); 
	$sql = "SELECT SUM(standard_cost * qty * multiplier) as total
			FROM ".$b['aria_db'].".".TB_PREF."stock_moves
			WHERE trans_no =".$trans_no." and type=".$type." ";
	// db_query($sql, 'failed to retrieve records from stock_moves');
	$result = db_query($sql, 'failed to retrieve records from stock_moves');
	$row = db_fetch($result);
	return number_format2($row['total'], 2);
}

function is_purchaser($user_id) {
	$po_host = '192.168.0.56'; 
	$po_user = 'root';
	$po_pass = '';
	$po_db = 'srs';

	$conn = mysql_connect($po_host, $po_user, $po_pass);
	mysql_select_db($po_db, $conn);

	$sql = "SELECT * FROM users WHERE aria_user_id = '".$user_id."'";
	$query = mysql_query($sql, $conn);
	if (mysql_num_rows($query) > 0)
		return true;
}

function is_operations($user_id) {
	$sql = "SELECT role FROM ".TB_PREF."security_roles WHERE id = '".$user_id."'";
	$query = db_query($sql);
	if (db_num_rows($query) == 1) {
		$result = db_fetch_assoc($query);
		$role = $result['role'];
		if ($role == 'Purchase Officer') {
			return true;
		}
	}
}

function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM transfers.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

function get_user_by_branch($id, $branch_id) {
	$b = get_branch_by_id($branch_id);
	
	$sql = "SELECT * FROM ".$b['aria_db'].".".TB_PREF."users WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get user $id");

	return db_fetch($result);
}

function approve_adjustment_details_by_branch($get_trans_no, $reference, $m_code, $memo_, $branch_id) {
	global $Refs, $db_connections;
	$b = get_branch_by_id($branch_id);
	$myserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["host"], 2);
	$msserver = ping($b['ms_mov_host'], 2);
	if (!$myserver) {
		display_error($db_connections[$_SESSION["wa_current_user"]->company]["host"].' CONNECTION FAILED  TO MYSQL DATABASE SERVER. Please inform IS-Department to fix it.');
		return false;
	}
	elseif (!$msserver) {
		display_error($b['ms_mov_host'].' CONNECTION FAILED  TO MSSQL DATABASE SERVER. Please inform IS-Department to fix it.');
		return false;
	}
	
	$date_ = Today();
	$type = ST_INVADJUST;
	
	$msconn = mssql_connect($b['ms_mov_host'], $b['ms_mov_user'], $b['ms_mov_pass']);
	mssql_select_db($b['ms_mov_db'], $msconn);
	mssql_query("BEGIN TRANSACTION", $msconn);
	begin_transaction();
	
	$adj_id = $reference;
	$stats = 2;
	$movement_status = 'POSTED';
	$posted_by = $_SESSION['wa_current_user']->user;
	$created_by = $_SESSION['wa_current_user']->user;
	
	
	$sql_select_all_input = "SELECT * FROM ".$b['aria_db'].".".TB_PREF."movement_types WHERE movement_code='$m_code'";
	$res = db_query($sql_select_all_input);
	$row = db_fetch($res);
	$movement_code = $row['movement_code'];
	$movement_loc_code = $row['location_code'];
	$movement_loc = $row['location'];
	$action_type = $row['action_type'];
	
	
	$MovementCode = $movement_code;
	$ToDescription = $movement_loc;
	$area = $movement_loc;
	
	
	$gl_type = ST_INVADJUST;
	

	$sql = "SELECT * FROM ".$b['aria_db'].".".TB_PREF."stock_moves WHERE type='$gl_type' AND trans_no = '".$get_trans_no."'";
	$res = db_query($sql);
	
	while ($row = mysql_fetch_array($res)) {
		$item_code = check_my_items_by_branch($row['stock_id'], $b, $msconn);
		
		if ($item_code === false) {
			return true;
		}
		
		$uoms_qty_multiplier = get_adj_qty_multiplier_by_branch($row['i_uom'], $b, $msconn);
		
		$totalqty += $uoms_qty_multiplier * $row['qty'];
		$nettotal += ($uoms_qty_multiplier*$row['qty'])*$row['standard_cost'];
	}
	
	
	$ToAddress = $ContactPerson = '';
	$mc_sql = "SELECT * FROM MovementTypes WHERE MovementCode= '$movement_code'";

	$mc_sql_res = mssql_query($mc_sql, $msconn);			  
	$mc_row = mssql_fetch_array($mc_sql_res);
	$flow_stockroom =  $mc_row['FlowSellingArea'];
	$flow_sa = $mc_row[' FlowStockRoom'];
	$flow_dmg =  $mc_row['FlowDamaged'];
	$prod_history_desc = $mc_row['Description'];
	
	$movement_no=str_pad(getCounter_by_branch($movement_code, $b, $msconn), 10, '0', STR_PAD_LEFT);
	
	
	//---- MOVEMENTS insertion
	$from_description = "SAN ROQUE SUPERMARKET  ".strtoupper($b['name'])."($area)";
	$sql = "INSERT INTO Movements (MovementNo,MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,
	ContactPerson,FromDescription,FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,
	Terms,TransactionDate,FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,
	BranchCode,CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
	WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
	VALUES ('$movement_no','$MovementCode','','','','$ToDescription','$ToAddress','$ContactPerson','$from_description','',
	'".date2sql($date_)."','".$_SESSION['wa_current_user']->ms_user_id."','".date2sql($date_)."','$stats','".$_SESSION['wa_current_user']->ms_user_id."',
	'".date2sql($date_)."',0,'".date2sql($date_)."','NULL','".$nettotal."','$movement_status','".$totalqty."',
	'".$_SESSION['wa_current_user']->ms_user_id."','$remarks','NULL','NULL','NULL','','','','','','0','0','0','0','0','1','PHP','0','')";
	//display_error($sql);
	$objquery = mssql_query($sql, $msconn);
	$cancel=rollback_mssql_trans($sql, $objquery, $msconn);
	if($cancel == 1){
	return false;
	}
	
	//---- get last ms movements id
	// $last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
	// $last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	// $last_inserted_recID = $last_inserted_line_row['LAST'];
	
	$last_inserted_line_res = mssql_query("SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY]", $msconn);
	$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	$last_inserted_recID = $last_inserted_line_row['SCOPE_IDENTITY'];
	
	//---------------------
	
	update_adjustment_header_by_branch($last_inserted_recID, $adj_id, $MovementCode, Today(), 
		$movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no, $created_by, $posted_by, $b, $msconn);
	
	$gl_type=ST_INVADJUST;
	$sql = "SELECT * from ".$b['aria_db'].".".TB_PREF."stock_moves WHERE type='$gl_type' AND trans_no = '".$get_trans_no."'";
	$res = db_query($sql);
	//display_error($sql);
	
	while($row=mysql_fetch_array($res)) {
		$uoms_qty_multiplier=get_adj_qty_multiplier_by_branch($row['i_uom'], $b, $msconn);
		
		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT [Description],ProductCode,SellingArea,Damaged FROM Products WHERE ProductID = '".$row['stock_id']."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = mssql_query($sellingareabeg, $msconn);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareadmg = $sellingarearow['Damaged'];
		$sellingareabarcode = $sellingarearow['ProductCode'];
		$sellingareadesc = $sellingarearow['Description'];
		
		//----UPDATING PRODUCTS [SellingArea]
		$qty_per_piece=$uoms_qty_multiplier*$row['qty'];
		
		if ($stats=='2')
		{
			
			if ($action_type=='0') 
			{
				//NEGATIVE
				if($movement_loc_code=='2' or $movement_loc_code=='1') {
					//SA
					$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]-".$qty_per_piece."
					WHERE ProductID='".$row['stock_id']."'";
					//display_error($sql_upadate_all_input);
					$objquery = ms_db_query($sql_upadate_all_input);
					$cancel=rollback_mssql_trans($sql_upadate_all_input, $objquery, $msconn);
					if($cancel == 1){
					return false;
					}
									} 
				else {
					//BO
					$sql_upadate_all_input="UPDATE Products SET [Damaged]=[Damaged]-".$qty_per_piece."
					WHERE ProductID='".$row['stock_id']."'";					
					//display_error($sql_upadate_all_input);
					$objquery = mssql_query($sql_upadate_all_input, $msconn);
					$cancel=rollback_mssql_trans($sql_upadate_all_input, $objquery, $msconn);
					if($cancel == 1){
					return false;
					}
				}
			}
			else 
			{ 
				
				//POSITIVE
				if($movement_loc_code=='2' or $movement_loc_code=='1') {
					//SA
					$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]+".$qty_per_piece."
					WHERE ProductID='".$row['stock_id']."'";
					//display_error($sql_upadate_all_input);
					$objquery = mssql_query($sql_upadate_all_input, $msconn);
					$cancel=rollback_mssql_trans($sql_upadate_all_input, $objquery, $msconn);
					if($cancel == 1){
					return false;
					}
				}
				else {
					//BO
					$sql_upadate_all_input="UPDATE Products SET [Damaged]=[Damaged]+".$qty_per_piece."
					WHERE ProductID='".$row['stock_id']."'";
					//display_error($sql_upadate_all_input);
					$objquery = mssql_query($sql_upadate_all_input, $msconn);
					$cancel=rollback_mssql_trans($sql_upadate_all_input, $objquery, $msconn);
					if($cancel == 1){
					return false;
					}
				}
			}
		}
		
		//----Inserting MovementLine
		add_adjustment_movement_line_by_branch($last_inserted_recID, $row['stock_id'], $sellingareabarcode, $sellingareadesc, $row['i_uom'], $row['standard_cost']+0, $row['qty'], $uoms_qty_multiplier, $sellingareabarcode, $b, $msconn);
		$movement_details_row = get_adjustment_movement_line_details_by_branch($last_inserted_recID, $row['stock_id'], $b, $msconn);
		
		
		if ($stats == '2') {
			$flow_selling_area = 0;
			
			if ($action_type == '0')//action_type is positive or negative
			{
				//NEGATIVE
				if($movement_loc_code=='2' or $movement_loc_code=='1') {
					$flow_selling_area=1;
					$sellingareadmg='NULL';
					$selling_area_in='NULL';
					$selling_area_out=$movement_details_row['qty'] * $movement_details_row['pack'];
					$damaged_in='NULL';
					$damaged_out='NULL';
				}
				else {
					$flow_selling_area=1;
					$sellingareaqty='NULL';
					$selling_area_in='NULL';
					$selling_area_out='NULL';
					$damaged_in='NULL';
					$damaged_out=$movement_details_row['qty'] * $movement_details_row['pack'];
				}
			}
			else {
				//POSITIVE
				if($movement_loc_code=='2' or $movement_loc_code=='1') {
					$flow_selling_area=2;
					$sellingareadmg='NULL';
					$selling_area_in=$movement_details_row['qty'] * $movement_details_row['pack'];
					$selling_area_out='NULL';
					$damaged_in='NULL';
					$damaged_out='NULL';
				}
				else {
					$flow_selling_area=2;
					$sellingareaqty='NULL';
					$selling_area_in='NULL';
					$selling_area_out='NULL';
					$damaged_in=$movement_details_row['qty'] * $movement_details_row['pack'];
					$damaged_out='NULL';
				}
			}
			
			$movement_id_2_insert=$last_inserted_recID;
			
			$producthistory = "INSERT INTO ProductHistory ([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
			,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
			,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
			,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
			,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
			VALUES('".$row['stock_id']."','".$sellingareabarcode."','".$movement_id_2_insert."','".$movement_no."','".date2sql($date_)."', 
			'".date2sql($date_)."', '$prod_history_desc', $sellingareaqty, NULL, 2, $flow_selling_area,$selling_area_in, $selling_area_out, NULL, 
			NULL, '".$movement_details_row['unitcost']."', $damaged_in,$damaged_out, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->ms_user_id."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '',$sellingareadmg, NULL)";

			$objquery = mssql_query($producthistory, $msconn);
			$cancel=rollback_mssql_trans($producthistory, $objquery, $msconn);
			if($cancel == 1){
			return false;
			}			
		}
		
		$gl_debit_amount+=$row['standard_cost'] * $row['qty'] * $uoms_qty_multiplier;
		$gl_credit_amount+=$row['standard_cost'] * $row['qty'] * $uoms_qty_multiplier;
		
		$t_s_cost+=$row['standard_cost'];
	}
	
	
	if ($stats=='2') {
		if ($t_s_cost> 0)
		{
			
			//inserting 0_stock_master data selected.
			//5300 inventory adjustments
			
			if ($action_type=='0')//action_type is positive or negative
			{
				//NEGATIVE
				if($movement_loc_code=='2' or $movement_loc_code=='1') {
					add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, -$gl_debit_amount, '', '', '', $b, $msconn);
					add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, $gl_credit_amount, '', '', '', $b, $msconn);
				}
				else{
					add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, $gl_debit_amount, '', '', '', $b, $msconn);
					add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, -$gl_credit_amount, '', '', '', $b, $msconn);
				}
				
			}
			else {
				//POSITIVE
				if($movement_code!='PS') {
					if($movement_loc_code=='2' or $movement_loc_code=='1') {
						add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, $gl_debit_amount, '', '', '', $b, $msconn);
						add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, -$gl_credit_amount, '', '', '', $b, $msconn);
					}
					else{
						add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, -$gl_debit_amount, '', '', '', $b, $msconn);
						add_gl_trans_std_cost_by_branch(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, $gl_credit_amount, '', '', '', $b, $msconn);
					}
				}
			}
			
		}
	}
	
	//add_comments(ST_INVADJUST, $adj_id, $date_, $memo_);
	add_audit_trail_by_branch(ST_INVADJUST, $adj_id, $date_, '', '', $b);
	
	// recompute_cost_of_sales_from_adjustment(ST_INVADJUST, $get_trans_no);
	
	if($cancel != 1)
	{
		commit_transaction();
		mssql_query("COMMIT TRANSACTION", $msconn);
	}
	return $adj_id;	
}

function check_my_items_by_branch($line_item, $branch, $msconn) {
	$stock_id = $line_item;

	$sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."stock_master WHERE stock_id =".db_escape($stock_id);
	
	$res = db_query($sql);

	mssql_select_db($branch['ms_mov_db'], $msconn);
	$v_sql = "SELECT ProductID,
					ProductCode,
					Description,
					pVatable,
					reportuom,
					reportqty,
					inactive,
					CostOfSales
			FROM Products 
			WHERE  ProductID = ".db_escape($stock_id);
	
	$v_res = mssql_query($v_sql, $msconn);
	$v_row = mssql_fetch_array($v_res);
		
	if (db_num_rows($res) > 0) {
		
		$update_sql = "UPDATE ".$branch['aria_db'].".".TB_PREF."stock_master SET
					product_code = ".db_escape($v_row['ProductCode']).", 
					description = ".db_escape($v_row['Description']).", 
					long_description  = ".db_escape($v_row['Description']).", 
					units  = ".db_escape($v_row['reportuom']).", 
					inactive = ".$v_row['inactive']."
					WHERE stock_id =".db_escape($stock_id);
		db_query($update_sql, 'failed to update stock master');
		
	}
	else if ($v_row['Description'] != '') {

		$ins_sql = "INSERT INTO ".$branch['aria_db'].".".TB_PREF."stock_master (stock_id, product_code, description, long_description, tax_type_id, units, base_multiplier, last_cost ,material_cost)
			VALUES(".db_escape($stock_id).", 
				".db_escape($v_row['ProductCode']).", 
				".db_escape($v_row['Description']).", 
				".db_escape($v_row['Description']).", 
				1,
				".db_escape($v_row['reportuom']).", 
				".db_escape($v_row['reportqty']).", 
				".db_escape($v_row['CostOfSales']+0).",  
				".db_escape($v_row['CostOfSales']+0).")";
		//display_error($ins_sql);	
		db_query($ins_sql, 'failed to insert stock in master');
	}
	else {
		$ins_sql = "INSERT INTO ".$branch['aria_db'].".".TB_PREF."stock_master (stock_id, product_code, description, long_description, tax_type_id, units, base_multiplier, last_cost ,material_cost)
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
		return false;
	}
	
	return $stock_id;
}

function get_adj_qty_multiplier_by_branch($uom, $branch, $msconn) {
	mssql_select_db($branch['ms_mov_db'], $msconn);

	$sql = "SELECT Qty FROM UOM WHERE UOM='$uom'";
	$res = mssql_query($sql, $msconn);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}

function getCounter_by_branch($type, $branch, $msconn) {
	mssql_select_db($branch['ms_mov_db'], $msconn);
	$sql_re = "SELECT Counter FROM Counters WHERE TransactionTypeCode = '$type'";
	//display_error($sql_re);
	$res = mssql_query($sql_re, $msconn);
	$trancode =  mssql_fetch_array($res);
	
	if ($trancode['Counter']!='' or $trancode['Counter']!=null) {
		$tran_no = $trancode['Counter'] + 1;
		$sql = "UPDATE [Counters] SET 
		Counter = $tran_no
		WHERE TransactionTypeCode = '$type'";
		//display_error($sql);
		//ms_db_query($sql);
		$objquery = mssql_query($sql, $msconn);
		$cancel=rollback_mssql_trans($sql, $objquery, $msconn);
		if($cancel == 1){
		return false;
		}
	}
	
	else {
		return false;
	}
	
	return $tran_no;
}

function rollback_mssql_trans($sql_query, $objquery, $msconn) {
	if(!$objquery)
	{
		mssql_query("ROLLBACK TRANSACTION", $msconn);
		cancel_transaction();
		display_error("An Error occured while processing this query on database : ".$sql_query);
		display_error("Try to logout and login your account then process the transaction again. If this attempt doesn't work, Contact IS-Department for further instructions.");
		return 1;
	}
}

function update_adjustment_header_by_branch($movement_id, $trans_no, $adjustment_type, $date_, $location, $reference, $person_id, $stats, $MovementCode, $movement_no, $created_by, $posted_by, $branch, $msconn) {
	$date = date2sql($date_);
	
	if ($movement_no == 0)
			$date = '0000-00-00';
	
	$sql = "UPDATE ".$branch['aria_db'].".".TB_PREF."adjustment_header SET a_ms_movement_id=".db_escape($movement_id+0).",a_movement_code=".db_escape($adjustment_type).",a_movement_no=".db_escape($movement_no).",
	a_date_posted='$date',a_from_location= ".db_escape($location).",a_to_location=".db_escape($location).",a_posted_by=".db_escape($posted_by).",a_status=".db_escape($stats)." 
	WHERE a_trans_no=".db_escape($trans_no)."";
	$objquery=db_query($sql);
	$cancel=rollback_mssql_trans($sql, $objquery, $msconn);
	if($cancel == 1){
	return false;
	}
}

function add_adjustment_movement_line_by_branch($last_inserted_recID, $ProductID, $ProductCode, $Description, $UOM, $price_per_piece, $qty, $pack, $barcode, $branch, $msconn) {
	$unit_cost=$price_per_piece;
	if ($qty<0) {
		$qty=0;
	}
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
	UOM,unitcost,qty,extended,pack,barcode)
	VALUES ('$last_inserted_recID','$ProductID','$ProductCode','".ms_escape_string($Description)."','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
	// ms_db_query($sql);
	
	$objquery = mssql_query($sql, $msconn);
	$cancel=rollback_mssql_trans($sql, $objquery, $msconn);
	if($cancel == 1){
	return false;
	}
}

function get_adjustment_movement_line_details_by_branch($movement_id, $prod_id, $branch, $msconn) {	
	$sql = "SELECT * FROM MovementLine WHERE MovementID ='$movement_id' AND ProductID ='$prod_id'";	
	$res = mssql_query($sql, $msconn);
	$row = mssql_fetch_array($res);
	return $row;
}

function add_gl_trans_std_cost_by_branch($type, $trans_id, $date_, $account, $dimension, $dimension2,
	$memo_,	$amount, $person_type_id=null, $person_id=null, $err_msg="", $branch, $msconn) {
	if ($amount != 0)
		return add_gl_trans_by_branch($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_,
			$amount, null, $person_type_id, $person_id, $err_msg, 0, $branch, $msconn);
	else
		return 0;
}

function add_gl_trans_by_branch($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_,
	$amount, $currency=null, $person_type_id=null, $person_id=null,	$err_msg="", $rate=0, $branch, $msconn)
{
	global $use_audit_trail;

	$date = date2sql($date_);
	if ($currency != null)
	{
		if ($rate == 0)
			$amount_in_home_currency = to_home_currency($amount, $currency, $date_);
		else
			$amount_in_home_currency = round2($amount * $rate,  user_price_dec());
	}		
	else
		$amount_in_home_currency = round2($amount, user_price_dec());
	if ($dimension == null || $dimension < 0)
		$dimension = 0;
	if ($dimension2 == null || $dimension2 < 0)
		$dimension2 = 0;
	if (isset($use_audit_trail) && $use_audit_trail)
	{
		if ($memo_ == "" || $memo_ == null)
			$memo_ = $_SESSION["wa_current_user"]->username;
		else
			$memo_ = $_SESSION["wa_current_user"]->username . " - " . $memo_;
	}
	$sql = "INSERT INTO ".$branch['aria_db'].".".TB_PREF."gl_trans ( type, type_no, tran_date,
		account, dimension_id, dimension2_id, memo_, amount";

	if ($person_type_id != null)
		$sql .= ", person_type_id, person_id";

	$sql .= ") ";

	$sql .= "VALUES (".db_escape($type).", ".db_escape($trans_id).", '$date',
		".db_escape($account).", ".db_escape($dimension).", "
		.db_escape($dimension2).", ".db_escape($memo_).", "
		.db_escape($amount_in_home_currency);

	if ($person_type_id != null)
		$sql .= ", ".db_escape($person_type_id).", ". db_escape($person_id);

	$sql .= ") ";

	if ($err_msg == "")
		$err_msg = "The GL transaction could not be inserted";

	db_query($sql, $err_msg);
	//display_error($sql);
	return $amount_in_home_currency;
}

function add_audit_trail_by_branch($trans_type, $trans_no, $trans_date, $descr='',$br_code='', $branch) {

	if ($br_code!=''){
		switch_connection_to_branch($br_code);
	}
	
	$ip = '';
	if (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}
	elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED'];
	}
	elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_FORWARDED_FOR'];
	}
	elseif (isset($_SERVER['HTTP_FORWARDED'])) {
		$ip = $_SERVER['HTTP_FORWARDED'];
	}
	else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
		
	$sql = "INSERT INTO ".$branch['aria_db'].".".TB_PREF."audit_trail"
		. " (type, trans_no, user, fiscal_year, gl_date, description, gl_seq, remote_address)
			VALUES(".db_escape($trans_type).", ".db_escape($trans_no).","
			. $_SESSION["wa_current_user"]->user. ","
			. get_company_pref('f_year') .","
			. "'". date2sql(Today()) ."',"
			. db_escape($descr). ", 0,"
			. db_escape($ip). ")";

	db_query($sql, "Cannot add audit info");
	
	// all audit records beside latest one should have gl_seq set to NULL
	// to avoid need for subqueries (not existing in MySQL 3) all over the code
	$sql = "UPDATE ".$branch['aria_db'].".".TB_PREF."audit_trail SET gl_seq = NULL"
		. " WHERE type=".db_escape($trans_type)." AND trans_no="
		.db_escape($trans_no)." AND id!=".db_insert_id();

	db_query($sql, "Cannot update audit gl_seq");
}

function get_gl_view_str_by_branch($type, $trans_no, $label, $branch_id) {
	global $path_to_root;

	$viewer = "gl/view/gl_trans_view_live.php?type_id=$type&trans_no=$trans_no&bid=$branch_id";

	return "<a target='_blank' href='$path_to_root/$viewer' onclick=\"javascript:openWindow(this.href,this.target); return false;\">$label</a>";
}

function get_adjustment_details_view_str_by_branch($trans_no, $label="", $branch_id) {
	global $path_to_root;

	$viewer = "inventory/inquiry/adjustment_details_view_live.php?trans_no=$trans_no&bid=$branch_id";

	return "<a target='_blank' href='$path_to_root/$viewer' onclick=\"javascript:openWindow(this.href,this.target); return false;\">$label</a>";
}
 
// if ($db_connections[$_SESSION["wa_current_user"]->company]["name"] != 'San Roque Supermarket - NOVA') {
// 	display_error('<h2>'."YOU CAN ONLY ACCESS THIS PAGE WHEN YOU'RE LOGGED IN FROM NOVALICHES BRANCH. ".'</br>'."Please logout and login to novaliches branch in order to proceed. Thank you!".'</h2>');
// 	exit;
// }

// echo "<pre>"; print_r($_POST); echo "</pre>";
if (isset($_POST['approve_all_selected'])) {
	
	if (isset($_POST['purch_id']) && !empty($_POST['purch_id'])) {
		foreach ($_POST['purch_id'] as $key => $a_id) {
			$branch_id = $_POST['bid_'.$a_id];
			update_purch_status($a_id, $branch_id);
			display_notification("Adjustment #".$a_id." is approved by purchaser only. Still need operations and GM approval.");
		}
	}

	if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
		foreach ($_POST['post_id'] as $key => $a_id) {
			$branch_id = $_POST['bid_'.$a_id];
			update_posted_adjustment_header($a_id, $branch_id);
			display_notification("Adjustment #".$a_id." is approved by purchaser and operations only. Still need GM approval.");
		}
	}

	if (isset($_POST['approve_id']) && !empty($_POST['approve_id'])) {
		foreach ($_POST['approve_id'] as $key => $a_id) {
			$branch_id = $_POST['bid_'.$a_id];
			$movement_code = $_POST['a_movement_code'.$a_id];
			$trans_no = approve_adjustment_details_by_branch($a_id, $a_id, $movement_code, '', $branch_id);
			display_notification("Adjustment #".$a_id." is successfully approved.");
		}
	}

}

// Custom JS here
echo '<script type="text/javascript">';
	echo '$(document).ready(function() {
			$("#purch_all").change(function() {
				$(".purch_one").attr("checked", this.checked);
			});

			$("#post_all").change(function() {
				$(".post_one").attr("checked", this.checked);
			});

			$("#approve_all").change(function() {
				$(".approve_one").attr("checked", this.checked);
			});

			$(".purch_one").change(function() {
				if ($(".purch_one").length == $(".purch_one:checked").length) {
					$("#purch_all").attr("checked", "checked");
				}
				else {
					$("#purch_all").removeAttr("checked");
				}
			});

			$(".post_one").change(function() {
				if ($(".post_one").length == $(".post_one:checked").length) {
					$("#post_all").attr("checked", "checked");
				}
				else {
					$("#post_all").removeAttr("checked");
				}
			});

			$(".approve_one").change(function() {
				if ($(".approve_one").length == $(".approve_one:checked").length) {
					$("#approve_all").attr("checked", "checked");
				}
				else {
					$("#approve_all").removeAttr("checked");
				}
			});

			$("input:checkbox").change(function() {
				if ($("input:checkbox:checked").length > 0) {
					$("#approve_all_selected").show();
				}
				else {
					$("#approve_all_selected").hide();
				}	
			});
			
		});';
echo '</script>';	


start_form();
div_start('header');

$type = ST_INVADJUST;

// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');
start_table();
	start_row();
		ref_cells('Transaction #:', 'trans_no');
		//yesno_list_cells(_("Status Type:"), 'movement_type', '',_("Deliver to Branch"), _("Received from Branch"));
		//adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
		yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();



div_start('dm_list');
// if (!isset($_POST['search']) or ($approve_add != -1 and $posted_add!= -1))
// 	display_footer_exit();
$code = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
$bSql = "SELECT * FROM transfers.0_branches WHERE code = '".$code."' ";

$bQuery = db_query($bSql);

$all_branch = array();

while ($bRow = db_fetch($bQuery)) {
	// jade
	$sql = "SELECT b.name, a.* from ". $bRow['aria_db'].".".TB_PREF."adjustment_header  a, ".TB_PREF."movement_types b
	WHERE a.a_movement_code = b.movement_code ";
	if ($_POST['start_date'])
	{
		$sql .= " AND a.a_date_created >= '".date2sql($_POST['start_date'])."'
				  AND a.a_date_created<= '".date2sql($_POST['end_date'])."'";	
	}

	if ($_POST['trans_no'])
	{
	$sql .= " AND a.a_movement_no LIKE '%". $_POST['trans_no'] . "%'";	

	}

	$sql .= " AND a.a_movement_code IN ('IGSA', 'IGNSA', 'IGBO', 'IGNBO')";


	if ($_POST['status_type']==1)
	{
	//Open
	$stats='1' ;
	}
	else {
	//Posted
	$stats='2';
	}

	if ($_POST['status_type']!='')
	{
	$sql .= " AND a.a_status = '$stats'";	
	}


	$sql .= " ORDER BY a.a_date_created";
	$res = db_query($sql);
	
	while ($row = db_fetch($res)) {
		$_row = $row;
		$_row['branch_name'] = $bRow['name'];
		$_row['branch_id'] = $bRow['id'];
		array_push($all_branch, $_row);
	}
}

start_table($table_style2.' width=95%');
$th = array();
// ============================================== 
array_push($th, 'Branch', 'Date Created', 'TransNo','MovementNo','Movement Type', 'Created By', 'Date Posted', 'Approved By','Posted By', 'Status','Extended','');


if (!empty($all_branch))
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}


$k = 0;

	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver= $u['user_id'];
	//display_error($approver);

foreach ($all_branch as $key => $row) {

	alt_table_row_color($k);
	label_cell($row['branch_name']);
	label_cell(sql2date($row['a_date_created']));
	label_cell(get_gl_view_str_by_branch(ST_INVADJUST, $row["a_trans_no"], $row["a_trans_no"], $row['branch_id']));
	//label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['a_movement_no']);
	// label_cell($row['a_from_location']);
	label_cell($row['name']);
	$user_create = get_user_by_branch($row['a_created_by'], $row['branch_id']);
	label_cell($user_create['real_name']);
	label_cell(sql2date($row['a_date_posted']));
	$user_approve = get_user_by_branch($row['a_temp_posted_by'], $row['branch_id']);
	label_cell($user_approve['real_name']);
	
	$user_post = get_user_by_branch($row['a_posted_by'], $row['branch_id']);
	label_cell($user_post['real_name']);
	//label_cell(get_comments_string($type, $row['trans_no']));
	
	if ($row['a_status']==1)
	{
	label_cell('Open');
	}
	else {
	label_cell('Posted');
	}
	
	#Extended Total
	label_cell(get_stock_moves_total_by_branch($row['a_trans_no'], $type, $row['branch_id']));

	label_cell(get_adjustment_details_view_str_by_branch($row['a_trans_no'], 'View', $row['branch_id']));


	if ($row['a_status'] == 1) {
		/*label_cell(pager_link(_('Edit'), "/inventory/all_item_adjustments.php?trans_no=" .$row['a_trans_no'], false));
		//hidden('a_movement_code'.$row['a_trans_no'],$row['a_movement_code']);
		$selected_1='posted_id'.$row['a_trans_no'];
		$selected='selected_id'.$row['a_trans_no'];*/

		// if ($row['a_purchaser_approved'] == 0 && is_purchaser($_SESSION['wa_current_user']->user)) {
		// 	echo '<td align="center">
		// 		<input type="hidden" name="bid_'.$row["a_trans_no"].'" value="'.$row['branch_id'].'" />
		// 		<input type="checkbox" class="purch_one" name="purch_id[]" value="'.$row["a_trans_no"].'" />
		// 	</td>';
		// }
		// else if ($row['a_purchaser_approved'] == 0) {
		// 	// label_cell('<i>Pending</i>','align=center');
		// }
		// else {
		// 	// label_cell('<i>Approved</i>','align=center');
		// }


		// if ($row['a_purchaser_approved'] == 0) {
		// 	label_cell('<i>For Approval of Purchaser</i>','align=center');
		// }
		// else if ($row['a_purchaser_approved'] == 1 && $row['a_temp_posted'] == 0) {
		// 	if (is_operations($_SESSION['wa_current_user']->access)) {
		// 		echo '<td align="center">
		// 			<input type="hidden" name="bid_'.$row["a_trans_no"].'" value="'.$row['branch_id'].'" />
		// 			<input type="checkbox" class="post_one" name="post_id[]" value="'.$row["a_trans_no"].'" />
		// 		</td>';
		// 	}
		// 	else {
		// 		label_cell('<i>Pending</i>','align=center');
		// 	}
		// }
		// else {
		// 	label_cell('<i>Approved</i>','align=center');
		// }


		// if ($row['a_temp_posted'] == 0) {
		// 	label_cell('<i>For Approval of Operations</i>','align=center'); 
		// }
		// else if ($row['a_temp_posted'] == 1) {
		// 	if ($approver == 'admin' || $approver == '6666') {
		// 		hidden('a_movement_code'.$row["a_trans_no"], $row['a_movement_code']);
		// 		echo '<td align="center">
		// 			<input type="hidden" name="bid_'.$row["a_trans_no"].'" value="'.$row['branch_id'].'" />
		// 			<input type="checkbox" class="approve_one" name="approve_id[]" value="'.$row["a_trans_no"].'" />
		// 		</td>';	
		// 	}
		// 	else {
		// 		label_cell('<i>Pending</i>','align=center');
		// 	}
		// }
		// else {
		// 	label_cell('<i>Approved</i>','align=center');
		// }
	}
	else {
		label_cell('');
		label_cell('');
		label_cell('');
		label_cell('');
	}

	end_row();
}
end_table();
br();
br();
div_end();

echo '<center style="margin: 10px 0;">
		<button class="inputsubmit" type="submit" id="approve_all_selected" name="approve_all_selected" style="display: none;">
			<span>Approve Selected</span>
		</button>
	</center>';

end_form();
end_page();
?>
