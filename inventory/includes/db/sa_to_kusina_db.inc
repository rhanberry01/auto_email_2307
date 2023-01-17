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
//==========================OUT==================
function transfer_to_resto($items, $location, $location2, $date_, $transfer_type, $reference, $memo_,$br_code1,$br_code2,$req_by)
{
	$m_type=ST_SAKUSINAOUT;
	global $Refs;
	ms_db_query("BEGIN TRANSACTION");
	begin_transaction();
	
		$transfer_type=11;
		$stats=2;
		$movement_status='POSTED';
		$posted_by=$_SESSION['wa_current_user']->user;
		$created_by=$_SESSION['wa_current_user']->user;
		
		$adj_id=$reference;

		 $sql_select_all_input="select * from ".TB_PREF."movement_types where id='$transfer_type'";
		 $res=db_query($sql_select_all_input);
		 $row = db_fetch($res);
		 $movement_code=$row['movement_code'];
		 $movement_loc_code=$row['location_code'];
		 $movement_loc=$row['location'];
		 $action_type=$row['action_type'];
		 
		$MovementCode = $movement_code;
		
		
		$ToDescription = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])." (".$movement_loc.")";
		//$ToDescription=$movement_loc;
		//$area = $movement_loc;
	
	
	foreach ($items as $line_item)
	{
	
	$item_code = check_my_items_($line_item->stock_id);
	
	if ($item_code === false)
	{
		return true;
	}
	
	$uoms_qty_multiplier=get_adj_qty_multiplier3($line_item->units);
	
	$totalqty+=$uoms_qty_multiplier*$line_item->quantity;
	$nettotal+=($uoms_qty_multiplier*$line_item->quantity)*$line_item->standard_cost;
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
	
		$movement_no=str_pad(getCounter3($movement_code),10,'0',STR_PAD_LEFT);

	//---- MOVEMENTS insertion
		$from_description = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])."(SELLING AREA)";
		$sql = "INSERT INTO Movements (MovementNo,MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,
		ContactPerson,FromDescription,FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,
		Terms,TransactionDate,FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,
		BranchCode,CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
		WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
		VALUES ('$movement_no','$MovementCode','','','','$ToDescription','$ToAddress','$ContactPerson','$from_description','',
		'".Today()." 00:00:00','".$_SESSION['wa_current_user']->ms_user_id."','".Today()." 00:00:00','$stats','".$_SESSION['wa_current_user']->ms_user_id."',
		'".Today()." 00:00:00',0,'".Today()." 00:00:00','NULL','".$nettotal."','$movement_status','".$totalqty."',
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
		
		$treasurer_id=$_SESSION["wa_current_user"]->user;
		$u = get_user($_SESSION["wa_current_user"]->user);
		$user_real_name = $u['real_name'];

		$my_header_id=add_transfers_out_header($last_inserted_recID,$adj_id,$m_type,$date_,$br_code1,$MovementCode,$movement_no,$user_real_name,$req_by);
		//---------------------
		
		
		foreach ($items as $line_item)
		{
		$uoms_qty_multiplier=get_adj_qty_multiplier3($line_item->units);	

		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT ProductCode,SellingArea,Damaged,StockRoom FROM Products WHERE ProductID = '".$line_item->stock_id."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareadmg = $sellingarearow['Damaged'];
		$sellingareastock_room = $sellingarearow['StockRoom'];
		$sellingareabarcode = $sellingarearow['ProductCode'];
		
		//----UPDATING PRODUCTS [SellingArea]
		 $qty_per_piece=$uoms_qty_multiplier*$line_item->quantity;

		$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]-".$qty_per_piece." 
		WHERE ProductID='".$line_item->stock_id."'";
		//display_error($sql_upadate_all_input);
		ms_db_query($sql_upadate_all_input);

		
		//----Inserting MovementLine
		add_adjustment_movement_line3($last_inserted_recID,$line_item->stock_id,$sellingareabarcode,$line_item->item_description,$line_item->units,$line_item->standard_cost+0,$line_item->quantity,$uoms_qty_multiplier,$sellingareabarcode);
		$movement_details_row = get_adjustment_movement_line_details3($last_inserted_recID, $line_item->stock_id);

		
		add_stock_all_adjustment_item3($m_type,$adj_id, $line_item->stock_id, $movement_loc_code,$date_,$reference,$line_item->quantity, 
		$line_item->standard_cost+0, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode);
		
		add_transfer_out_detail($my_header_id,$line_item->stock_id,$line_item->barcode,$line_item->units,$line_item->standard_cost+0, $line_item->quantity,$line_item->item_description);
		

		
		//---HISTORY
				$sellingareadmg='NULL';
				$selling_area_in='NULL';
				$stock_room_in='NULL';
				$stock_room_out='NULL';
				$selling_area_out=$movement_details_row['qty'] * $movement_details_row['pack'];
				$damaged_in='NULL';
				$damaged_out='NULL';

		$movement_id_2_insert=$last_inserted_recID;
		
		$producthistory = "INSERT INTO ProductHistory ([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
		,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
		,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
		,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
		,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
		VALUES('".$line_item->stock_id."','".$sellingareabarcode."','".$movement_id_2_insert."','".$movement_no."',CURRENT_TIMESTAMP, 
		CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', $sellingareaqty, $sellingareastock_room, 2, 1,$selling_area_in, $selling_area_out, $stock_room_in, 
		$stock_room_out, '".$movement_details_row['unitcost']."', $damaged_in,$damaged_out, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->ms_user_id."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '',$sellingareadmg, NULL)";
		//display_error($producthistory);
		ms_db_query($producthistory, "error inserting");		
		
	$gl_debit_amount+=$line_item->standard_cost * $line_item->quantity;
	$gl_credit_amount+=$line_item->standard_cost * $line_item->quantity;
	}
		
		if ($line_item->standard_cost > 0)
		{
			//inserting 0_stock_master data selected.
			//5300 inventory adjustments
			//5700 stock transfer
			//9053 business development-restaurant
			//570002 stock transfer out
			//1450042 due from malabon-resto expenses
			add_gl_trans_std_cost(ST_SAKUSINAOUT, $adj_id, $date_, 1450042, 0, 0, $memo_, $gl_debit_amount);
			add_gl_trans_std_cost(ST_SAKUSINAOUT, $adj_id, $date_, 570002, 0, 0, $memo_, -$gl_credit_amount);
		}

			
	add_comments(ST_SAKUSINAOUT, $adj_id, $date_, $memo_);

	if ($get_trans_no=='') {
	$Refs->save(ST_SAKUSINAOUT, $adj_id, $reference);
	}
	add_audit_trail(ST_SAKUSINAOUT, $adj_id, $date_, 'SA to SRS KUSINA Processed');

	commit_transaction();
	ms_db_query("COMMIT TRANSACTION");
	return $adj_id;	
}
//==========================END OF OUT==================


// //==========================IN==================

function receive_from_resto($items, $location, $location2, $date_, $transfer_type, $reference1, $memo_,$br_code1,$br_code2)
{
	$m_type=ST_SAKUSINAIN;
	
//display_error('connected');
	global $Refs,$db_connections;
	ms_db_query("BEGIN TRANSACTION");
	begin_transaction();
	
		$transfer_type=12;
		$stats=2;
		$movement_status='POSTED';
		$posted_by=$_SESSION['wa_current_user']->user;
		$created_by=$_SESSION['wa_current_user']->user;
		
		$reference=get_next_trans_no(ST_SAKUSINAIN);
		$adj_id=$reference;
		
		//$adj_id = get_next_trans_no(ST_SAKUSINAIN);

		
		 $movement_code='SA2KI';
		 //display_error($movement_code);
		 $movement_loc_code='1';
		 $movement_loc='KUSINA';
		 
		$MovementCode = $movement_code;
		
		
		$ToDescription = "SRS  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])." (".$movement_loc.")";
		//$ToDescription=$movement_loc;
		//$area = $movement_loc;
	
	
	foreach ($items as $line_item)
	{
	
	$item_code = check_my_items_($line_item->stock_id);
	
	if ($item_code === false)
	{
		return true;
	}
	
	$uoms_qty_multiplier=get_adj_qty_multiplier3($line_item->units);
	
	$totalqty+=$uoms_qty_multiplier*$line_item->quantity;
	$nettotal+=($uoms_qty_multiplier*$line_item->quantity)*$line_item->standard_cost;
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
	
		$movement_no=str_pad(getCounter3($movement_code),10,'0',STR_PAD_LEFT);

	//---- MOVEMENTS insertion
		$from_description = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])."(SELLING AREA)";
		$sql = "INSERT INTO Movements (MovementNo,MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,
		ContactPerson,FromDescription,FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,
		Terms,TransactionDate,FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,
		BranchCode,CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
		WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
		VALUES ('$movement_no','$MovementCode','','','','$ToDescription','$ToAddress','$ContactPerson','$from_description','',
		'".Today()." 00:00:00','".$_SESSION['wa_current_user']->ms_user_id."','".Today()." 00:00:00','$stats','".$_SESSION['wa_current_user']->ms_user_id."',
		'".Today()." 00:00:00',0,'".Today()." 00:00:00','NULL','".$nettotal."','$movement_status','".$totalqty."',
		'".$_SESSION['wa_current_user']->ms_user_id."','$remarks','NULL','NULL','NULL','','','','','','0','0','0','0','0','1','PHP','0','')";
		ms_db_query($sql, "error inserting");
		
		//---- get last ms movements id
		// $last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
		// $last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
		// $last_inserted_recID = $last_inserted_line_row['LAST'];
		
		$last_inserted_line_res = ms_db_query("SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY]");
		$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
		$last_inserted_recID = $last_inserted_line_row['SCOPE_IDENTITY'];
		
		//display_error($last_inserted_recID);
		//--------------------
		$treasurer_id=$_SESSION["wa_current_user"]->user;
		$u = get_user($_SESSION["wa_current_user"]->user);
		$user_real_name = $u['real_name'];
		
		
		$my_header_id=add_transfers_in_header($reference1,$last_inserted_recID,$adj_id,$m_type,$date_,$br_code2,$MovementCode,$movement_no,$user_real_name);
		
		foreach ($items as $line_item)
		{
		$uoms_qty_multiplier=get_adj_qty_multiplier3($line_item->units);	

		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT ProductCode,SellingArea,Damaged,StockRoom FROM Products WHERE ProductID = '".$line_item->stock_id."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareadmg = $sellingarearow['Damaged'];
		$sellingareastock_room = $sellingarearow['StockRoom'];
		$sellingareabarcode = $sellingarearow['ProductCode'];
		
		//----UPDATING PRODUCTS [SellingArea]
		 $qty_per_piece=$uoms_qty_multiplier*$line_item->quantity;

		$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]+".$qty_per_piece."
		WHERE ProductID='".$line_item->stock_id."'";
		//display_error($sql_upadate_all_input);
		ms_db_query($sql_upadate_all_input);

		
		//----Inserting MovementLine
		add_adjustment_movement_line3($last_inserted_recID,$line_item->stock_id,$sellingareabarcode,$line_item->item_description,$line_item->units,$line_item->standard_cost+0,$line_item->quantity,$uoms_qty_multiplier,$sellingareabarcode);
		$movement_details_row = get_adjustment_movement_line_details3($last_inserted_recID, $line_item->stock_id);

		

		add_stock_all_adjustment_item3($m_type,$adj_id, $line_item->stock_id, $movement_loc_code,$date_,$reference,$line_item->quantity, 
		$line_item->standard_cost+0, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode);

	
		add_transfer_in_detail($my_header_id,$line_item->stock_id,$line_item->quantity);
		
		//---HISTORY
				$sellingareadmg='NULL';
				$selling_area_in=$movement_details_row['qty'] * $movement_details_row['pack'];
				$stock_room_in='NULL';
				$stock_room_out='NULL';
				$selling_area_out='NULL';
				$damaged_in='NULL';
				$damaged_out='NULL';

		$movement_id_2_insert=$last_inserted_recID;
		
		$producthistory = "INSERT INTO ProductHistory ([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
		,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
		,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
		,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
		,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
		VALUES('".$line_item->stock_id."','".$sellingareabarcode."','".$movement_id_2_insert."','".$movement_no."',CURRENT_TIMESTAMP, 
		CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', '$sellingareaqty', '$sellingareastock_room', 2, 2,$selling_area_in, $selling_area_out, $stock_room_in, 
		$stock_room_out, '".$movement_details_row['unitcost']."', $damaged_in,$damaged_out, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->ms_user_id."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '',$sellingareadmg, NULL)";
		//display_error($producthistory);
		ms_db_query($producthistory, "error inserting");		
		
	$gl_debit_amount+=$line_item->standard_cost * $line_item->quantity;
	$gl_credit_amount+=$line_item->standard_cost * $line_item->quantity;
	}
		
		if ($line_item->standard_cost > 0)
		{
			//inserting 0_stock_master data selected.
			//5300 inventory adjustments
			//5700 stock transfer
			//9053 business development-restaurant
			//570002 stock transfer out
			//1450042 due from malabon-resto expenses
			add_gl_trans_std_cost(ST_SAKUSINAIN, $adj_id, $date_, 2350019, 0, 0, $memo_, $gl_debit_amount);
			add_gl_trans_std_cost(ST_SAKUSINAIN, $adj_id, $date_, 570002, 0, 0, $memo_, -$gl_credit_amount);
		}

			
	add_comments(ST_SAKUSINAIN, $adj_id, $date_, $memo_);

	if ($get_trans_no=='') {
	$Refs->save(ST_SAKUSINAIN, $adj_id, $reference);
	}
	add_audit_trail(ST_SAKUSINAIN, $adj_id, $date_, 'SA to SRS KUSINA Processed');

	commit_transaction();
	ms_db_query("COMMIT TRANSACTION");
	return $adj_id;	
}
// //=============END OF IN======================================



function add_stock_all_adjustment_item3($m_type,$adj_id, $stock_id, $location, $date_, $reference, $quantity, $standard_cost, $units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$barcode)
{	
	$mb_flag = get_mb_flag($stock_id);

    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
    }
	
	//updating material cost.
	update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);
	//display_error("POSTED");
		//---- select all temporary mysql movements header and details
		add_stock_move($m_type, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
		$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
		$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
}

function getCounter3($type)
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
	ms_db_query($sql);
	}
	else {
	return false;
	}
	return $tran_no;
}

function add_adjustment_movement_line3($last_inserted_recID,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece;
	if ($qty<0) {
	$qty=0;
	}
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
				UOM,unitcost,qty,extended,pack,barcode)
			VALUES ('$last_inserted_recID','$ProductID','$ProductCode','$Description','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
	ms_db_query($sql);
}

function get_adjustment_movement_line_details3($movement_id, $prod_id)
{
	$sql = "SELECT * FROM MovementLine WHERE MovementID ='$movement_id' AND ProductID ='$prod_id'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}

function get_adj_qty_multiplier3($uom)
{
	$sql = "SELECT Qty FROM UOM WHERE UOM='$uom'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}


function add_transfers_out_header($movement_id,$trans_no,$type_out,$date_,$br_location,$MovementCode,$movement_no,$name_out)
{
	$date_created = date2sql($date_);
	$sql = "INSERT INTO transfers.".TB_PREF."transfer_header (date_created,delivery_date,
	br_code_out,aria_type_out,aria_trans_no_out,name_out,m_code_out,m_id_out,m_no_out,delivered_by,requested_by) 
	VALUES ('$date_created', '$date_created',".db_escape($br_location).",
	".db_escape($type_out).",".db_escape($trans_no).",".db_escape($name_out).",".db_escape($MovementCode).",".db_escape($movement_id).",".db_escape($movement_no).",".db_escape($name_out).",".db_escape($req_by).")";
	
	db_query($sql,"Failed to Insert.");
	//display_error($sql);
$id = db_insert_id();
return $id;
}

function add_transfer_out_detail($movement_id,$stock_id,$barcode,$units,$standard_cost, $quantity_out,$description)
{
	$date_created = date2sql($date_);
	$sql = "INSERT INTO transfers.".TB_PREF."transfer_details (transfer_id,stock_id,description,barcode,uom,cost,qty_out,qty_in) 
	VALUES (".db_escape($movement_id).",".db_escape($stock_id).",".db_escape($description).",".db_escape($barcode).",".db_escape($units).",
	'$standard_cost','$quantity_out', '0')";
	
	db_query($sql);
	//display_error($sql);
}


function add_transfer_in_detail($head_id,$stock_id,$quantity_in)
{
	$sql = "UPDATE transfers.".TB_PREF."transfer_details SET 
	qty_in = '$quantity_in' where transfer_id='$head_id' and stock_id='$stock_id'";
	db_query($sql,"Failed to Insert.");
	//display_error($sql);
}

function add_transfers_in_header($reference1,$movement_id,$trans_no,$type_in,$date_,$br_location,$MovementCode,$movement_no,$name_in)
{
	$date_created = date2sql($date_);
	$sql = "UPDATE transfers.".TB_PREF."transfer_header SET 
	br_code_in=".db_escape($br_location).",aria_type_in=".db_escape($type_in).",aria_trans_no_in=".db_escape($trans_no).",name_in=".db_escape($name_in).",
	m_code_in=".db_escape($MovementCode).",m_id_in=".db_escape($movement_id).",m_no_in=".db_escape($movement_no)."
	where aria_trans_no_out='$reference1'";
	db_query($sql,"Failed to Insert.");

	$sql2="select id from transfers.".TB_PREF."transfer_header where aria_trans_no_out='$reference1'";
	$res=db_query($sql2,"Failed to select id to update.");
	$row=db_fetch($res);
	$id=$row['id'];
	//display_error($sql2);
return $id;
}
?>