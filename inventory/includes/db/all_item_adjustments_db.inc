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
//-------------------------------------------------------------------------------------------------------------
// function ping($host, $timeout = 1) {
	// /* ICMP ping packet with a pre-calculated checksum */
	// $package = "\x08\x00\x7d\x4b\x00\x00\x00\x00PingHost";
	// $socket  = socket_create(AF_INET, SOCK_RAW, 1);
	// socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
	// socket_connect($socket, $host, null);

	// $ts = microtime(true);
	// socket_send($socket, $package, strLen($package), 0);
	// if (socket_read($socket, 255))
	// $result = microtime(true) - $ts;
	// else    $result = false;
	// socket_close($socket);

	// return $result;
// }

// function rollback_mssql_transaction($sql_query,$objquery){
	// if(!$objquery)
	// {
		// ms_db_query("ROLLBACK TRANSACTION");
		// cancel_transaction();
		// display_error("An Error occured while processing this query on database : ".$sql_query);
		// display_error("Try to logout and login your account then process the transaction again. If this attempt doesn't work, Contact IS-Department for further instructions.");
		// return 1;
	// }
// }


function approve_adjustment_details($get_trans_no,$reference, $m_code,$memo_)
{
	global $Refs, $db_connections;
	$myserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["host"], 2);
	$msserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["ms_host"], 2);
	if(!$myserver){
	display_error($db_connections[$_SESSION["wa_current_user"]->company]["host"].' CONNECTION FAILED  TO MYSQL DATABASE SERVER. Please inform IS-Department to fix it.');
	return false;
	}elseif(!$msserver){
	display_error($db_connections[$_SESSION["wa_current_user"]->company]["ms_host"].' CONNECTION FAILED  TO MSSQL DATABASE SERVER. Please inform IS-Department to fix it.');
	return false;
	}
	
	$date_=Today();
	$type = ST_INVADJUST;
	
	ms_db_query("BEGIN TRANSACTION");
	begin_transaction();
	
	$adj_id=$reference;
	$stats=2;
	$movement_status='POSTED';
	$posted_by=$_SESSION['wa_current_user']->user;
	$created_by=$_SESSION['wa_current_user']->user;
	
	
	$sql_select_all_input="select * from ".TB_PREF."movement_types where movement_code='$m_code'";
	//display_error($sql_select_all_input);
	$res=db_query($sql_select_all_input);
	$row = db_fetch($res);
	$movement_code=$row['movement_code'];
	$movement_loc_code=$row['location_code'];
	$movement_loc=$row['location'];
	$action_type=$row['action_type'];
	
	
	$MovementCode = $movement_code;
	$ToDescription=$movement_loc;
	$area = $movement_loc;
	
	
	$gl_type=ST_INVADJUST;
	// $sql = "SELECT a_date_created from ".TB_PREF."adjustment_header WHERE a_type='$gl_type' AND a_trans_no = '".$get_trans_no."'";
	// $res = db_query($sql);
	// $row = db_fetch($res);
	// $date_ = sql2date($row[0]);
	

	$sql = "SELECT * from ".TB_PREF."stock_moves WHERE type='$gl_type' AND trans_no = '".$get_trans_no."'";
	$res = db_query($sql);
	//display_error($sql);
	
	while($row=mysql_fetch_array($res)) {
		$item_code = check_my_items_($row['stock_id']);
		
		if ($item_code === false)
		{
			return true;
		}
		
		$uoms_qty_multiplier=get_adj_qty_multiplier($row['i_uom']);
		
		$totalqty += $uoms_qty_multiplier * $row['qty'];
		$nettotal +=($uoms_qty_multiplier*$row['qty'])*$row['standard_cost'];
	}
	
	
	$ToAddress = $ContactPerson = '';
	$mc_sql = "SELECT * FROM MovementTypes WHERE MovementCode= '$movement_code'";
	//display_error($mc_sql);
	$mc_sql_res = ms_db_query($mc_sql);			  
	$mc_row = mssql_fetch_array($mc_sql_res);
	$flow_stockroom =  $mc_row['FlowSellingArea'];
	$flow_sa = $mc_row[' FlowStockRoom'];
	$flow_dmg =  $mc_row['FlowDamaged'];
	$prod_history_desc = $mc_row['Description'];
	
	$movement_no=str_pad(getCounter($movement_code),10,'0',STR_PAD_LEFT);
	
	
	//---- MOVEMENTS insertion
	$from_description = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])."($area)";
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
	$objquery = ms_db_query($sql);
	$cancel=rollback_mssql_transaction($sql, $objquery);
	if($cancel == 1){
	return false;
	}
	
	//---- get last ms movements id
	// $last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
	// $last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	// $last_inserted_recID = $last_inserted_line_row['LAST'];
	
	$last_inserted_line_res = ms_db_query("SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY]");
	$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	$last_inserted_recID = $last_inserted_line_row['SCOPE_IDENTITY'];
	
	//---------------------
	
	update_adjustment_header($last_inserted_recID,$adj_id,$MovementCode, Today(), 
		$movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
	
	$gl_type=ST_INVADJUST;
	$sql = "SELECT * from ".TB_PREF."stock_moves WHERE type='$gl_type' AND trans_no = '".$get_trans_no."'";
	$res = db_query($sql);
	//display_error($sql);
	
	while($row=mysql_fetch_array($res)) {
		$uoms_qty_multiplier=get_adj_qty_multiplier($row['i_uom']);
		
		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT [Description],ProductCode,SellingArea,Damaged FROM Products WHERE ProductID = '".$row['stock_id']."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
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
					$cancel=rollback_mssql_transaction($sql_upadate_all_input, $objquery);
					if($cancel == 1){
					return false;
					}
									} 
				else {
					//BO
					$sql_upadate_all_input="UPDATE Products SET [Damaged]=[Damaged]-".$qty_per_piece."
					WHERE ProductID='".$row['stock_id']."'";					
					//display_error($sql_upadate_all_input);
					$objquery = ms_db_query($sql_upadate_all_input);
					$cancel=rollback_mssql_transaction($sql_upadate_all_input, $objquery);
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
					$objquery = ms_db_query($sql_upadate_all_input);
					$cancel=rollback_mssql_transaction($sql_upadate_all_input, $objquery);
					if($cancel == 1){
					return false;
					}
				}
				else {
					//BO
					$sql_upadate_all_input="UPDATE Products SET [Damaged]=[Damaged]+".$qty_per_piece."
					WHERE ProductID='".$row['stock_id']."'";
					//display_error($sql_upadate_all_input);
					$objquery = ms_db_query($sql_upadate_all_input);
					$cancel=rollback_mssql_transaction($sql_upadate_all_input, $objquery);
					if($cancel == 1){
					return false;
					}
				}
			}
		}
		
		//----Inserting MovementLine
		add_adjustment_movement_line($last_inserted_recID,$row['stock_id'],$sellingareabarcode,$sellingareadesc,$row['i_uom'],$row['standard_cost']+0,$row['qty'],$uoms_qty_multiplier,$sellingareabarcode);
		$movement_details_row = get_adjustment_movement_line_details($last_inserted_recID, $row['stock_id']);
		
		
		// add_stock_all_adjustment_item($adj_id, $row['stock_id'], $movement_loc_code,$date_,$reference,$row['qty'], 
		// $row['standard_cost']+0, $row['i_uom'], $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$row['barcode']);
		
		
		if ($stats=='2') {
			$flow_selling_area=0;
			
			if ($action_type=='0')//action_type is positive or negative
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
			//display_error($producthistory);
			//ms_db_query($producthistory, "error inserting");	
			$objquery = ms_db_query($producthistory);
			$cancel=rollback_mssql_transaction($producthistory, $objquery);
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
					add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, -$gl_debit_amount);
					add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, $gl_credit_amount);
				}
				else{
					add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, $gl_debit_amount);
					add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, -$gl_credit_amount);
				}
				
			}
			else {
				//POSITIVE
				if($movement_code!='PS') {
					if($movement_loc_code=='2' or $movement_loc_code=='1') {
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, $gl_debit_amount);
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, -$gl_credit_amount);
					}
					else{
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, -$gl_debit_amount);
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, $gl_credit_amount);
					}
				}
			}
			
		}
	}
	
	//add_comments(ST_INVADJUST, $adj_id, $date_, $memo_);
	
	add_audit_trail(ST_INVADJUST, $adj_id, $date_);
	
	// recompute_cost_of_sales_from_adjustment(ST_INVADJUST, $get_trans_no);
	
	if($cancel != 1)
	{
		commit_transaction();
		ms_db_query("COMMIT TRANSACTION");
	}
	return $adj_id;	
}

function get_inventory_and_cost_backup($prod_id, $date_)
{	
	$sql = "SELECT SellingArea ,Damaged, CostOfSales
				FROM	ProductsBackUp
				WHERE CAST(BackUpDate AS DATE)= '".date2sql($date_)."'
				AND ProductID = $prod_id";
	// display_notification($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return array($row['SellingArea'],$row['Damaged'], $row['CostOfSales']);
}

function recompute_cost_of_sales_from_adjustment($type, $get_trans_no)
{
	$gl_type=ST_INVADJUST;
	$sql = "SELECT DISTINCT stock_id, tran_date from ".TB_PREF."stock_moves WHERE type='$gl_type' AND trans_no = '".$get_trans_no."'";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		// loop distinct items per date, from tran_date to current date
		$stock_id = $row['stock_id'];
		$start_date = sql2date($row['tran_date']);
		recompute_item_cost_of_sales($stock_id, $start_date);
	}
}

function check_adjustment_for_date_posted($movement_code, $ms_movement_id)
{
	$sql = "SELECT * FROM ".TB_PREF."adjustment_header 
					WHERE a_ms_movement_id = $ms_movement_id
					AND a_movement_code = ". db_escape($movement_code)."
					AND a_date_posted >= '2015-10-29'";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0) // not an adjustment in ARIA OR old adjustment
		return false;
	return true;
}

function get_adjustment_header($movement_code, $ms_movement_id)
{
	$sql = "SELECT * FROM ".TB_PREF."adjustment_header 
					WHERE a_ms_movement_id = $ms_movement_id
					AND a_movement_code = ". db_escape($movement_code);
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0) // not an adjustment in ARIA OR old adjustment
		return false;
		
	$row = db_fetch($res);
	return $row;
}

function update_finished_sales($stock_id, $date_, $avg_cost)
{
	$sql = "UPDATE FinishedSales SET
					AverageUnitCost = $avg_cost
					WHERE ProductID = $stock_id
					AND CAST(LogDate as DATE)= '".date2sql($date_)."'";
	ms_db_query($sql);
	}

function update_products_table($stock_id, $avg_cost, $sa, $bo)
{
	$sql = "UPDATE Products SET
					SellingArea = $sa,
					Damaged = $bo,
					CostOfSales = $avg_cost
					WHERE ProductID = $stock_id";
	ms_db_query($sql);
}

function update_products_backup($stock_id, $date_, $avg_cost, $sa, $bo)
{
	// list($sa_beg, $bo_beg, $cos_beg) = get_inventory_and_cost_backup($stock_id, $date_);
	
	$sql = "SELECT COUNT(*)
				FROM ProductsBackUp
				WHERE CAST(BackUpDate AS DATE)= '".date2sql($date_)."'
				AND ProductID = $stock_id";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);

	// display_notification($sql);
	// display_notification($row[0] . '  dsfxb');die;
	$pass_here = false;
	if ($row[0] > 1) //delete if more than 1
	{
		$sql = "DELETE FROM ProductsBackUp
				WHERE CAST(BackUpDate AS DATE)= '".date2sql($date_)."'
				AND ProductID = $stock_id";
		$res = ms_db_query($sql);
		
		$pass_here = true;
	}
		
	if ($row[0] == 0 OR $pass_here)
	{
		// insert PoductsBackup using last date then update 
		$sql = "SELECT * FROM	ProductsBackUp
				WHERE CAST(BackUpDate AS DATE)= '".date2sql(add_days($date_,-1))."'
				AND ProductID = $stock_id";
		$res = ms_db_query($sql);
		$row = mssql_fetch_array($res,MSSQL_ASSOC);
		
		if ($row['ProductID'] == '') // wala pang backup kahit kailan
		{
				$sql = "SELECT ProductID,
									ProductCode,
									Description,
									reportuom,
									reportqty,
									inactive,
									0 as SellingArea,
									0 as StockRoom,
									0 as Damaged,
									0 as CostOfSales,
									GlobalID,
									'' as BackUpDate,
									'' as BeginningSA,
									'' as SellingAreaOut,
									'' as WithVat,
									'' as NonVat,
									PVatable,
									0 as BeginningCOS,
									VendorCode
									 FROM Products
									WHERE ProductID = $stock_id";
				$res = ms_db_query($sql);
				$row = mssql_fetch_array($res,MSSQL_ASSOC);
		}
		
		$row['BackUpDate'] = date2sql($date_);
		
		$zeroes = array('inactive', 'SellingArea', 'StockRoom', 'Damaged', 'CostOfSales','WithVat');
		foreach($row as $key => $values)
		{
			if (TRIM($values) == '')
			{
				$row[$key] = "NULL";
				if (in_array($key,$zeroes))
					$row[$key] = "0";
			}
			else if (!is_numeric($values))
				$row[$key] = db_escape($values);
		}
		
		if ($avg_cost != 0 OR $sa == 0 OR $bo== 0)
		{
			$sql = "INSERT INTO ProductsBackUp VALUES(" . implode(",",$row) . ")";
			// display_notification($sql);
			ms_db_query($sql);
		}
	}
	
	$sql = "UPDATE ProductsBackUp SET
					SellingArea = ".($sa+0).",
					Damaged = ".($bo+0).",
					CostOfSales = ".($avg_cost+0).",
					StockRoom = 0
					WHERE ProductID = $stock_id
					AND CAST(BackUpDate as DATE) = '".date2sql($date_)."'";
	ms_db_query($sql);
	// display_notification($sql);
}

function update_movements($stock_id, $date_, $cost, $movements_to_be_updated)
{
	// check if adjustment has record on ARIA, then update its GL entry
	foreach($movements_to_be_updated as $mm)
	{
		$movement_code = $mm[0];
		$movement_id = $mm[1];
		
		if ($movement_id == 0)
				continue;
		// display_notification($movement_code);
		// display_notification($movement_id);
		// display_notification('~~');
		
		$adj_row = get_adjustment_header($movement_code,$movement_id);
		if ($adj_row) // update ARIA
		{
			// update GL in ARIA
			update_adjustment_gl($adj_row['a_type'], $adj_row['a_trans_no'], $stock_id, $cost);
		}
		
		$sql = "SELECT $cost-(extended/pack/qty) FROM MovementLine
						WHERE MovementID = $movement_id
						AND ProductID = $stock_id";
		$res = ms_db_query($sql);
		$row = mssql_fetch_array($res);
		$diff_ms = round($row[0],4);
		
		// update MSSQL movement line
		$sql = "UPDATE MovementLine SET unitcost = $cost, extended = ROUND($cost*pack*qty,4)
						WHERE MovementID = $movement_id AND ProductID = $stock_id";
		// display_notification($sql);
		ms_db_query($sql);
		
		// update MSSQL movement header
		$sql = "UPDATE Movements SET NetTotal = NetTotal-$diff_ms WHERE MovementID = $movement_id AND MovementCode='$movement_code'";
		// display_notification($sql);
		ms_db_query($sql);
	}
}

function update_adjustment_gl($type, $trans_no, $stock_id, $cost)
{
	//get the difference from stock moves vs cost
	$sql = "SELECT ($cost)-price FROM 0_stock_moves WHERE type=$type AND trans_no=$trans_no AND stock_id=$stock_id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$diff = round($row[0],2);
	
	if ($diff == 0)
		return true;
	
	// +/- amount in both GL entries
	$sql = "UPDATE 0_gl_trans SET amount=amount-$diff WHERE type=$type AND  type_no=$trans_no AND amount > 0";
	db_query($sql,'failed to update GL of adjustment (debit)');
	$sql = "UPDATE 0_gl_trans SET amount=amount+$diff WHERE type=$type AND  type_no=$trans_no AND amount < 0";
	db_query($sql,'failed to update GL of adjustment (debit)');
}

function get_item_received_unit_cost($receiving_id, $stock_id)
{
	$sql = "SELECT extended/pack/qty FROM ReceivingLine
					WHERE ReceivingID = $receiving_id
					AND ProductID = $stock_id";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return round($row[0],4);
}

function get_item_movement_unit_cost($movement_id, $stock_id)
{
	$sql = "SELECT extended/pack/qty FROM MovementLine
					WHERE MovementID = $movement_id
					AND ProductID = $stock_id";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return round($row[0],4);
}

function add_adjustment_details($items, $date_,$type, $status_type, $reference, $memo_,$get_trans_no,$bypass_products_table=false)
{
	global $Refs;
	ms_db_query("BEGIN TRANSACTION");
	begin_transaction();
	
	
	if ($get_trans_no=='') {
		$reference=get_next_trans_no(ST_INVADJUST);
		$adj_id=$reference;
	}
	else {
		$adj_id=$reference;
	}
	
	if ($status_type=='1')
	{
		$stats=2;
		$movement_status='POSTED';
		$posted_by=$_SESSION['wa_current_user']->user;
		$created_by=$_SESSION['wa_current_user']->user;
	}
	else {
		$stats=1;
		$movement_status='OPEN';
		$created_by=$_SESSION['wa_current_user']->user;
	}
	//$reference=get_next_trans_no(ST_INVADJUST);
	//$adj_id=$reference;
	
	//display_error($movement_status);
	//display_error($status_type);
	
	$sql_select_all_input="select * from ".TB_PREF."movement_types where id='$type'";
	//display_error($sql_select_all_input);
	$res=db_query($sql_select_all_input);
	$row = db_fetch($res);
	$movement_code=$row['movement_code'];
	$movement_loc_code=$row['location_code'];
	$movement_loc=$row['location'];
	$action_type=$row['action_type'];
	
	
	$MovementCode = $movement_code;
	$ToDescription=$movement_loc;
	$area = $movement_loc;
	
	$totalqty = $nettotal = 0;
	foreach ($items as $line_item)
	{
		// display_error('stock_id test : '. $line_item->stock_id);
		// display_error('date_ ' . $date_ );
		$item_code = check_my_items_($line_item->stock_id);
		
		if ($item_code === false)
		{
			return true;
		}
		
		$uoms_qty_multiplier = get_adj_qty_multiplier($line_item->units);
		// display_error('stock_id test uom : '. $line_item->units);
		
		$line_item->standard_cost = $line_item->standard_cost / $uoms_qty_multiplier;
		
		$totalqty += $uoms_qty_multiplier*$line_item->quantity;
		$nettotal += $uoms_qty_multiplier * $line_item->quantity*$line_item->standard_cost;
	}
	// die;
	$ToAddress = $ContactPerson = '';
	$mc_sql = "SELECT * FROM MovementTypes WHERE MovementCode= '$movement_code'";
	//display_error($mc_sql);
	$mc_sql_res = ms_db_query($mc_sql);			  
	$mc_row = mssql_fetch_array($mc_sql_res);
	$flow_stockroom =  $mc_row['FlowSellingArea'];
	$flow_sa = $mc_row[' FlowStockRoom'];
	$flow_dmg =  $mc_row['FlowDamaged'];
	$prod_history_desc = $mc_row['Description'];
	
	
	
	if ($stats==1) {	//=========================IF OPEN=========================
		
		if ($get_trans_no=='') {
			add_adjustment_header($last_inserted_recID+0,$adj_id,ST_INVADJUST, $date_, $movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		}
		else {
			update_adjustment_header($last_inserted_recID,$adj_id,$MovementCode, $date_, $movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		}
		
		$sql_stock_id="DELETE FROM ".TB_PREF."stock_moves where trans_no='$reference' AND type = 17";
		db_query($sql_stock_id,'failed to delete previous entries');
		
		foreach ($items as $line_item)
		{
			$uoms_qty_multiplier=get_adj_qty_multiplier($line_item->units);
			
			add_stock_all_adjustment_item($adj_id, $line_item->stock_id, $movement_loc_code,$date_,$reference,$line_item->quantity,
			$line_item->standard_cost+0, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode);
		}
		
		$comment=get_comments_string(17, $get_trans_no);
		
		if ($comment){
			update_comments(ST_INVADJUST, $adj_id, null, $memo_);
		}
		else{
			add_comments(ST_INVADJUST, $adj_id, $date_, $memo_);
		}
		
	}
	
	//===================ELSE=============================
	else {
		
		$movement_no=str_pad(getCounter($movement_code),10,'0',STR_PAD_LEFT);
		
		//---- MOVEMENTS insertion
		$from_description = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])."($area)";
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
		ms_db_query($sql);
		
		//---- get last ms movements id
		// $last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
		// $last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
		// $last_inserted_recID = $last_inserted_line_row['LAST'];
		
		$last_inserted_line_res = ms_db_query("SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY]");
		$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
		$last_inserted_recID = $last_inserted_line_row['SCOPE_IDENTITY'];
		
		//---------------------
		
		if ($get_trans_no=='') {
			add_adjustment_header($last_inserted_recID+0,$adj_id,ST_INVADJUST, $date_, $movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		}
		else {
			update_adjustment_header($last_inserted_recID,$adj_id,$MovementCode, $date_, $movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		}
		
		
		foreach ($items as $line_item)
		{
			$uoms_qty_multiplier=get_adj_qty_multiplier($line_item->units);
			
			
			//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
			
			//getting SellingArea Beginning before updating
			$sellingareabeg = "SELECT ProductCode,SellingArea,Damaged FROM Products WHERE ProductID = '".$line_item->stock_id."'";
			//display_error($sellingareabeg);
			$qsellingareabeg = ms_db_query($sellingareabeg);			  
			$sellingarearow = mssql_fetch_array($qsellingareabeg);
			$sellingareaqty = $sellingarearow['SellingArea'];
			$sellingareadmg = $sellingarearow['Damaged'];
			$sellingareabarcode = $sellingarearow['ProductCode'];
			
			//----UPDATING PRODUCTS [SellingArea]
			$qty_per_piece=$uoms_qty_multiplier*$line_item->quantity;
			
			if ($stats=='2' AND !$bypass_products_table)
			{
				
				if ($action_type=='0') 
				{
					//NEGATIVE
					if($movement_loc_code=='2' or $movement_loc_code=='1') {
						//SA
						$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]-".$qty_per_piece."
						WHERE ProductID='".$line_item->stock_id."'";
						//display_error($sql_upadate_all_input);
						ms_db_query($sql_upadate_all_input);
					} 
					else {
						//BO
						$sql_upadate_all_input="UPDATE Products SET [Damaged]=[Damaged]-".$qty_per_piece."
						WHERE ProductID='".$line_item->stock_id."'";					
						//display_error($sql_upadate_all_input);
						ms_db_query($sql_upadate_all_input);
					}
				}
				
				else { 
					
					//POSITIVE
					if($movement_loc_code=='2' or $movement_loc_code=='1') {
						//SA
						$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]+".$qty_per_piece."
						WHERE ProductID='".$line_item->stock_id."'";
						//display_error($sql_upadate_all_input);
						ms_db_query($sql_upadate_all_input);
					}
					else {
						//BO
						$sql_upadate_all_input="UPDATE Products SET [Damaged]=[Damaged]+".$qty_per_piece."
						WHERE ProductID='".$line_item->stock_id."'";
						//display_error($sql_upadate_all_input);
						ms_db_query($sql_upadate_all_input);
					}
				}
			}
			
			
			//----Inserting MovementLine
			add_adjustment_movement_line($last_inserted_recID,$line_item->stock_id,$sellingareabarcode,$line_item->item_description,$line_item->units,$line_item->standard_cost+0,$line_item->quantity,$uoms_qty_multiplier,$sellingareabarcode);
			$movement_details_row = get_adjustment_movement_line_details($last_inserted_recID, $line_item->stock_id);
			
			
			add_stock_all_adjustment_item($adj_id, $line_item->stock_id, $movement_loc_code,$date_,$reference,$line_item->quantity, 
			$line_item->standard_cost+0, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode);
			
			
			if ($stats=='2') {
				
				if ($action_type=='0')//action_type is positive or negative
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
				VALUES('".$line_item->stock_id."','".$sellingareabarcode."','".$movement_id_2_insert."','".$movement_no."','".date2sql($date_)."', 
				'".date2sql($date_)."', '$prod_history_desc', $sellingareaqty, NULL, 2, 2,$selling_area_in, $selling_area_out, NULL, 
				NULL, '".($movement_details_row['unitcost']+0)."', $damaged_in,$damaged_out, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->ms_user_id."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '',$sellingareadmg, NULL)";
				//display_error($producthistory);
				ms_db_query($producthistory, "error inserting");	
			}
			
			$gl_debit_amount+=$line_item->standard_cost * $line_item->quantity * $uoms_qty_multiplier;
			$gl_credit_amount+=$line_item->standard_cost * $line_item->quantity * $uoms_qty_multiplier;
		}
		
		
		if ($stats=='2') {
			if ($line_item->standard_cost > 0)
			{
				//inserting 0_stock_master data selected.
				//5300 inventory adjustments
				
				if ($action_type=='0')//action_type is positive or negative
				{
					//NEGATIVE
					if($movement_loc_code=='2' or $movement_loc_code=='1') {
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, -$gl_debit_amount);
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, $gl_credit_amount);
					}
					else{
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, $gl_debit_amount);
						add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, -$gl_credit_amount);
					}
					
				}
				else {
					//POSITIVE
					if($movement_code!='PS') {
						if($movement_loc_code=='2' or $movement_loc_code=='1') {
							add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, $gl_debit_amount);
							add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, -$gl_credit_amount);
						}
						else{
							add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5300, 0, 0, $memo_, -$gl_debit_amount);
							add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, 5000, 0, 0, $memo_, $gl_credit_amount);
						}
					}
				}
				
			}
		}
		
		
		if ($get_trans_no=='') {
			$Refs->save(ST_INVADJUST, $adj_id, $reference);
		}
		
		add_audit_trail(ST_INVADJUST, $adj_id, $date_);
		
		$comment=get_comments_string(17, $get_trans_no);
		
		if ($comment){
			update_comments(ST_INVADJUST, $adj_id, null, $memo_);
		}
		else{
			add_comments(ST_INVADJUST, $adj_id, $date_, $memo_);
		}
		
	}
	
	commit_transaction();
	ms_db_query("COMMIT TRANSACTION");
	return $adj_id;	
}


function add_stock_all_adjustment_item($adj_id, $stock_id, $location, $date_, $reference, $quantity, $standard_cost, $units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$barcode)
{
	
	$mb_flag = get_mb_flag($stock_id);
	
	if (is_service($mb_flag))
	{
		display_db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
	}
	
	
	//updating material cost.
	// update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);
	
	// if ($get_trans_no=='') {
		// //display_error("OPEN");
		
		// //add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $type);
		// //add to 0_stock_moves
		
		// $sql_stock_id="select stock_id from ".TB_PREF."stock_moves where trans_no='$reference' and stock_id='$stock_id'";
		// //display_error($sql_stock_id);
		// $res=db_query($sql_stock_id);
		// $row = db_fetch($res);
		// $check_stock_id=$row['stock_id'];
		
		// if ($check_stock_id=='') {
			add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
			$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
			$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
		// }
	// }
	// else {
		// //display_error("POSTED");
		// //---- select all temporary mysql movements header and details
		// $sql_stock_id="DELETE FROM ".TB_PREF."stock_moves where trans_no='$reference' and stock_id='$stock_id'";
		// //display_error($sql_stock_id);
		// $res=db_query($sql_stock_id);
		// $row = db_fetch($res);
		// $check_stock_id=$row['stock_id'];
		
		// // if ($check_stock_id!='') {
			// // update_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
			// // $show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
			// // $discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
		// // }
		// // else {
			// add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
			// $show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
			// $discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
		// // }
		
	// }
}

function getCounter($type)
{
	$sql_re = "SELECT Counter FROM Counters WHERE TransactionTypeCode = '$type'";
	//display_error($sql_re);
	$res = ms_db_query($sql_re);
	$trancode =  mssql_fetch_array($res);
	
	if ($trancode['Counter']!='' or $trancode['Counter']!=null) {
		$tran_no = $trancode['Counter'] + 1;
		$sql = "UPDATE [Counters] SET 
		Counter = $tran_no
		WHERE TransactionTypeCode = '$type'";
		//display_error($sql);
		//ms_db_query($sql);
		$objquery = ms_db_query($sql);
		$cancel=rollback_mssql_transaction($sql, $objquery);
		if($cancel == 1){
		return false;
		}
	}
	
	else {
		return false;
	}
	
	return $tran_no;
}

function add_adjustment_movement_line($last_inserted_recID,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece;
	if ($qty<0) {
		$qty=0;
	}
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
	UOM,unitcost,qty,extended,pack,barcode)
	VALUES ('$last_inserted_recID','$ProductID','$ProductCode','".ms_escape_string($Description)."','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
	// ms_db_query($sql);
	
	$objquery = ms_db_query($sql);
	$cancel=rollback_mssql_transaction($sql, $objquery);
	if($cancel == 1){
	return false;
	}
	
	
}

function update_adjustment_movement_line($movement_id_2_update,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece;
	if ($qty<0) {
		$qty=0;
	}
	
	$movement_details_row = get_adjustment_movement_line_details($movement_id_2_update, $ProductID);
	
	if ($movement_details_row['ProductID']!='') {
		$sql = "UPDATE MovementLine SET ProductCode='$ProductCode',Description='".ms_escape_string($Description)."',UOM='$UOM',unitcost='$unit_cost',
		qty='$qty',extended=".round($unit_cost*$qty,4).",pack='$pack',barcode='$barcode'
		WHERE MovementID= '$movement_id_2_update' AND ProductID='$ProductID'";
		//display_error($sql);
		//ms_db_query($sql);
		$objquery = ms_db_query($sql);
		$cancel=rollback_mssql_transaction($sql, $objquery);
		if($cancel == 1){
		return false;
		}
	}
	else {
		$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
		UOM,unitcost,qty,extended,pack,barcode)
		VALUES ('$movement_id_2_update','$ProductID','$ProductCode','".ms_escape_string($Description)."','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
		//display_error($sql);
		//ms_db_query($sql);
		$objquery = ms_db_query($sql);
		$cancel=rollback_mssql_transaction($sql, $objquery);
		if($cancel == 1){
		return false;
		}
	}
}


function get_adjustment_movement_line_details($movement_id, $prod_id)
{
	$sql = "SELECT * FROM MovementLine WHERE MovementID ='$movement_id' AND ProductID ='$prod_id'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}


function get_adj_qty_multiplier($uom)
{
	$sql = "SELECT Qty FROM UOM WHERE UOM='$uom'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}

function add_adjustment_header($movement_id,$trans_no,$type,$date_,$location,$reference,$person_id,$stats,$MovementCode,$movement_no,$created_by,$posted_by)
{
	if ($stats==2) {
		$date_posted = date2sql($date_);
	}
	else {
		$date_posted ='0000-00-00';
	}
	
	$sql="select * from ".TB_PREF."adjustment_header where a_trans_no=".db_escape($trans_no)."";
	//display_error($sql);
	$result_id_details=db_query($sql);
	$count=db_num_rows($result_id_details);
	
	if ($count<=0 or $count=='') {
		$date_created = date2sql($date_);
		$sql = "INSERT INTO ".TB_PREF."adjustment_header(a_trans_no,a_ms_movement_id,a_movement_code,a_movement_no,a_type,a_date_created,a_date_posted,a_ref,a_from_location,a_to_location,a_created_by,a_posted_by,a_status) 
		VALUES (".db_escape($trans_no).",".db_escape($movement_id).",".db_escape($MovementCode).",".db_escape($movement_no).", ".db_escape($type).",'$date_created', '$date_posted', ".db_escape($reference).", ".db_escape($location).",
		".db_escape($location).",".db_escape($created_by).",".db_escape($posted_by).",".db_escape($stats).")";
		//display_error($sql);
		//db_query($sql);
		$objquery=db_query($sql);
		$cancel=rollback_mssql_transaction($sql, $objquery);
		if($cancel == 1){
		return false;
		}
	}
}

function update_adjustment_header($movement_id,$trans_no,$adjustment_type,$date_,$location,$reference,$person_id,$stats,$MovementCode,$movement_no,$created_by,$posted_by)
{
	$date = date2sql($date_);
	
	if ($movement_no == 0)
			$date = '0000-00-00';
	
	$sql = "UPDATE ".TB_PREF."adjustment_header SET a_ms_movement_id=".db_escape($movement_id+0).",a_movement_code=".db_escape($adjustment_type).",a_movement_no=".db_escape($movement_no).",
	a_date_posted='$date',a_from_location= ".db_escape($location).",a_to_location=".db_escape($location).",a_posted_by=".db_escape($posted_by).",a_status=".db_escape($stats)." 
	WHERE a_trans_no=".db_escape($trans_no)."";
	$objquery=db_query($sql);
	$cancel=rollback_mssql_transaction($sql, $objquery);
	if($cancel == 1){
	return false;
	}
}

function get_total_history($stock_id, $date_)
{
	$sql = "SELECT SUM(SellingAreaIn),SUM(SellingAreaOut),SUM(DamagedIn),SUM(DamagedOut)
					FROM ProductHistory
					WHERE CAST(DatePosted as DATE) = CONVERT(DATE, '$date_', 101)
					AND ProductID = $stock_id ";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return array($row[0] - $row[1], $row[2] - $row[3]);
}

function check_and_create_adjustment_for_negative_ending($stock_id,$cos_end, $sa_end, $bo_end, $date_)
{
	$adj_sa = $adj_bo = 0; 
	if ($sa_end < 0)
	{
		$_sql = "SELECT TOP 1 qty, uom, Description, Barcode FROM [dbo].[POS_Products]
						WHERE ProductID = $stock_id
						ORDER BY qty ASC";
		$_res = ms_db_query($_sql);
		$_row = mssql_fetch_array($_res);
								
		$_cart = new items_cart(ST_INVADJUST);
		$_cart->tran_date = $date_;	
		$_cart->add_to_cart(count($_cart->line_items), $stock_id, abs($sa_end)/$_row['qty'], $_row['uom'], (($cos_end*$_row['qty']) + 0),$_row['Description'],$_row['Barcode']);
		$adj_id = add_adjustment_details($_cart->line_items, $date_,5, 1, '', 'to prevent negative qty','',true);
		$adj_sa = abs($sa_end);
		
		// display_notification("added SA adjustment # $adj_id");
	}
	if ($bo_end < 0)
	{
		$_sql = "SELECT TOP 1 qty, uom, Description, Barcode FROM [dbo].[POS_Products]
						WHERE ProductID = $stock_id
						ORDER BY qty ASC";
		$_res = ms_db_query($_sql);
		$_row = mssql_fetch_array($_res);
								
		$_cart = new items_cart(ST_INVADJUST);
		$_cart->tran_date = $date_;	
		$_cart->add_to_cart(count($_cart->line_items), $stock_id, abs($bo_end)/$_row['qty'], $_row['uom'], (($cos_end*$_row['qty']) + 0),$_row['Description'],$_row['Barcode']);
		$adj_id = add_adjustment_details($_cart->line_items, $date_,7, 1, '', 'to prevent negative qty','',true);
		$adj_bo = abs($bo_end);
		
		// display_notification("added SA adjustment # $adj_id");
	}
	return array($adj_sa, $adj_bo);
}

function recompute_item_cost_of_sales($stock_id, $start_date)
{
	// loop distinct items per date, from start_date to current date
	$to_date = Today();
	// $to_date = '10/31/2015';
	// $to_date = $start_date;
	$times = date_diff2($to_date, $start_date, 'd');
	// while($start_date != Today())
	// global $ms_db;
	// display_notification($times);
	$sa_beg_c = $bo_beg_c = $sa_beg = $bo_beg = $cos_beg = 0;
	for($i=0; $i<=$times; $i++)
	{
		// $ms_db = $_SESSION["wa_current_user"]->ms_get_db_connection();
		
		// if (!$ms_db)
			// return false;
		
		// get beginning cost of sales from back up
		$beg_date = $start_date;
		
		$sa_beg_c = $bo_beg_c = $sa_beg = $bo_beg = $cos_beg = 0;
		
		list($sa_beg, $bo_beg, $cos_beg) = get_inventory_and_cost_backup($stock_id, $beg_date);
		
		// $sa_beg_c = $sa_beg;
		// $bo_beg_c = $bo_beg;
		// display_notification('----------------------------');
		// display_notification('Date : '.$start_date);
		// display_notification('stock_id : '.$stock_id);
		// display_notification('beg SA : '.$sa_beg);
		// display_notification('beg BO : '.$bo_beg);
		// display_notification('beg COST : '.$cos_beg);
		
		$new_cos = $cos_beg;
		
		//get history by lne id
		$sql = "SELECT MovementCode, TransactionID, Description, DatePosted, BeginningSellingArea, BeginningDamaged,
							SellingAreaIn, SellingAreaOut, DamagedIn, DamagedOut , UnitCost,
							(CASE
								WHEN MovementCode IN('_DR', 'STI', 'R2SSA','ITI')THEN
									1
								ELSE
									0
								END
							)AS recomp
							FROM ProductHistory
						WHERE ProductID = $stock_id
						AND CAST(DatePosted as DATE) = CONVERT(DATE, '$start_date', 101)
						AND MovementCode != 'NOSLE'
						ORDER BY recomp DESC,LineID DESC";
		// $sql = "SELECT MovementCode, TransactionID, Description, DatePosted, BeginningSellingArea, BeginningDamaged,
							// SellingAreaIn, SellingAreaOut, DamagedIn, DamagedOut , UnitCost
							// FROM ProductHistory
						// WHERE ProductID = $stock_id
						// AND CAST(DatePosted as DATE) = '".date2sql($start_date)."'
						// ORDER BY LineID";
		// display_notification($sql);//die;
		$ms_res = ms_db_query($sql);
		$with_computation = array('_DR', 'STI', 'R2SSA','ITI');
	
		$movements_to_be_updated = array();
		while($ms_row = mssql_fetch_array($ms_res))
		{
			if (!in_array($ms_row['MovementCode'], $with_computation) 
					AND !check_adjustment_for_date_posted($ms_row['MovementCode'],$ms_row['TransactionID'])) // skip computation for movements not in array and old adjustments
			{
				 $sa_beg += $ms_row['SellingAreaIn'] - $ms_row['SellingAreaOut'] ;
				 $bo_beg += $ms_row['DamagedIn'] - $ms_row['DamagedOut'] ;
				 $movements_to_be_updated[] = array($ms_row['MovementCode'],$ms_row['TransactionID']);
				 // display_notification('SA : ' . $sa_beg .'');
				continue;
			}
			
			//recompute per transaction if transaction is receiving, free receiving, transfer in, returns, adjustments
			$totalqtymoved =  ($ms_row['SellingAreaIn'] - $ms_row['SellingAreaOut']) + ($ms_row['DamagedIn'] - $ms_row['DamagedOut']);
			
			$unit_cost = $ms_row['UnitCost'];
			if ($ms_row['MovementCode'] == '_DR' ) // for receiving or free
			{
				if ($ms_row['UnitCost'] != 0) // not free
				{
					$unit_cost = get_item_received_unit_cost($ms_row['TransactionID'], $stock_id);
				}
				else// if _DR but 0 unit cost  = FREE
				{
					$unit_cost = 0;
				}
			}
			else
			{
				$unit_cost = get_item_movement_unit_cost($ms_row['TransactionID'], $stock_id);
			}
			
			// computation of cost
			// $old_stock =  $sa_beg_c + $bo_beg_c;
			$old_stock =  $sa_beg + $bo_beg;
			$old_stock_cos = $old_stock * $cos_beg;

			$new_cos = $cos_beg;
			$extended_price = $totalqtymoved * $unit_cost;
			
			if($old_stock+$totalqtymoved != 0)
			{
				$old_stock_cos_ext = $old_stock_cos + $extended_price;
				$new_cos = round($old_stock_cos_ext/($old_stock+$totalqtymoved),4);
				// display_notification('Computation : ((' . $sa_beg .' + '. $bo_beg .') * '.$cos_beg .')+('. $totalqtymoved .' * '. $unit_cost .') / ('.$old_stock.'+'.$totalqtymoved.')');
			}
			
			$sa_beg += $ms_row['SellingAreaIn'] - $ms_row['SellingAreaOut'] ;
			$bo_beg += $ms_row['DamagedIn'] - $ms_row['DamagedOut'] ;
			
			// $sa_beg_c += $ms_row['SellingAreaIn'] - $ms_row['SellingAreaOut'] ;
			// $bo_beg_c += $ms_row['DamagedIn'] - $ms_row['DamagedOut'] ;
			// display_notification('SA : ' . $sa_beg);
			// display_notification('COST : ' . $new_cos);
			$cos_beg = round($new_cos,4);
		}
		
		// display_notification('ending SA before adj: '.$sa_beg);
		// display_notification('ending BO before adj : '.$bo_beg);
		// display_notification('ending COST before adj : '.$cos_beg);
		
		// negative checker
		$adj_sa = $adj_bo = 0;
		// list($adj_sa, $adj_bo) = check_and_create_adjustment_for_negative_ending($stock_id,$cos_beg, $sa_beg, $bo_beg,$beg_date);
		
		// $sa_beg += $adj_sa;
		// $bo_beg += $adj_bo;
		// update sales , movements that use standard cost, products back up
		
		update_movements($stock_id, $beg_date, $cos_beg, $movements_to_be_updated);
		
		// if ($i != $times) // update sales and back up until date is previous date
		// {
			update_finished_sales($stock_id, $beg_date, $cos_beg);
			update_products_backup($stock_id, add_days($beg_date,1), $cos_beg, $sa_beg, $bo_beg);
		// }
		// else // update products for current date
			// update_products_table($stock_id, $cos_beg, $sa_beg, $bo_beg);
			
		// display_notification('ending SA : '.$sa_beg);
		// display_notification('ending BO : '.$bo_beg);
		// display_notification('ending COST : '.$cos_beg);
		
		$start_date = add_days($start_date,1);
	}
	
	if ($cos_beg != 0)
		update_products_table($stock_id, $cos_beg, $sa_beg, $bo_beg);
	
	return true;
}
?>