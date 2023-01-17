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

//=====================ITEM TRANSFORMATION OUT========================
function deduct_raw_materials($items, $location, $location2, $date_, $transfer_type, $reference, $memo_)
{
	global $Refs;
	//ms_db_query("BEGIN TRANSACTION");
	begin_transaction();
	
		$transfer_type=1;
		$stats=2;
		$movement_status='POSTED';
		$posted_by=$_SESSION['wa_current_user']->user;
		$created_by=$_SESSION['wa_current_user']->user;

		$movement_code='ITO';
		$movement_loc_code='1';
		$movement_loc=$location;
		$movement_loc2=$location2;
		$MovementCode = $movement_code;
		$area='SELLING AREA';
		
		$reference=get_next_trans_no(ST_ITEM_TRANSFORMATION);
		$adj_id=$reference;
	
	foreach ($items as $line_item)
	{
	
	$item_code = check_my_items_($line_item->stock_id);
	
	if ($item_code === false)
	{
		return true;
	}
	
	$uoms_qty_multiplier=get_adj_qty_multiplier4($line_item->units);
	
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
	
		$movement_no=str_pad(getCounter4($movement_code),10,'0',STR_PAD_LEFT);

	//---- MOVEMENTS insertion
		$ToDescription = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])." (".$area.")";
		$from_description = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])."(".$area.")";
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
		
		//---------------------
		$type=ST_ITEM_TRANSFORMATION;
		
		add_item_transformation_header($last_inserted_recID,$adj_id,$type, $date_, $movement_loc,$movement_loc2, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		//add_adjustment_head($last_inserted_recID+0,$adj_id,ST_ITEM_TRANSFORMATION, $date_, $movement_loc, $movement_loc2,$reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		
		foreach ($items as $line_item)
		{
		$uoms_qty_multiplier=get_adj_qty_multiplier4($line_item->units);	

		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT ProductCode,SellingArea,Damaged,StockRoom FROM Products WHERE ProductID = '".$line_item->stock_id."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareadmg = $sellingarearow['Damaged'];
		$sellingareabarcode = $sellingarearow['ProductCode'];
		
		//----UPDATING PRODUCTS [SellingArea]
		 $qty_per_piece=$uoms_qty_multiplier*$line_item->quantity;

		$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]-".$qty_per_piece."
		WHERE ProductID='".$line_item->stock_id."'";
		//display_error($sql_upadate_all_input);
		ms_db_query($sql_upadate_all_input);

		
		//----Inserting MovementLine
		add_adjustment_movement_line4_out($last_inserted_recID,$line_item->stock_id,$sellingareabarcode,$line_item->item_description,$line_item->units,$line_item->standard_cost+0,$line_item->quantity,$uoms_qty_multiplier,$sellingareabarcode);
		$movement_details_row = get_adjustment_movement_line_details4($last_inserted_recID, $line_item->stock_id);

		
		add_stock_all_adjustment_item4($adj_id, $line_item->stock_id, $movement_loc_code,$date_,$reference,$line_item->quantity, 
		$line_item->standard_cost+0, $line_item->standard_cost+0, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode,$type);
				
				
		add_transformation_out_detail($adj_id,$line_item->stock_id,$line_item->barcode,$line_item->units,$line_item->standard_cost+0, $line_item->quantity,$line_item->item_description,$uoms_qty_multiplier);
		
		//---HISTORY
				$sellingareastock_room = 'NULL';
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

	commit_transaction();
	return $adj_id;	
}
//=====================END OF ITEM TRANSFORMATION OUT========================


//=====================ITEM TRANSFORMATION IN========================
function add_finished_goods($items, $location, $location2, $date_, $transfer_type, $reference, $memo_)
{
		global $Refs;
		begin_transaction();
	
		$transfer_type=1;
		$stats=2;
		$movement_status='POSTED';
		$posted_by=$_SESSION['wa_current_user']->user;
		$created_by=$_SESSION['wa_current_user']->user;

		$movement_code='ITI';
		$movement_loc_code='1';
		$movement_loc=$location;
		$movement_loc2=$location2;
		$MovementCode = $movement_code;
		$area='SELLING AREA';
		
		//$reference=get_next_trans_no(ST_ITEM_TRANSFORMATION);
		$adj_id=$reference;
	
		foreach ($items as $line_item)
		{

		$item_code = check_my_items_($line_item->stock_id);

		if ($item_code === false)
		{
		return true;
		}
	
		$uoms_qty_multiplier=get_adj_qty_multiplier4($line_item->units);
	
		$sellingareabeg = "SELECT ProductCode,Damaged,StockRoom,CostOfSales,SellingArea
		FROM Products WHERE ProductID = '".$line_item->stock_id."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareadmg = $sellingarearow['Damaged'];
		$old_unit_cost=$sellingarearow['CostOfSales'];

		$qty_per_piece=$uoms_qty_multiplier*$line_item->quantity;
		$new_unit_cost=$line_item->standard_cost/$qty_per_piece;
		//----UPDATING PRODUCTS [SellingArea]
		
		 $new_cost=(($old_unit_cost * ($sellingareadmg+$sellingareaqty)) + ($new_unit_cost * $qty_per_piece)) / ($sellingareadmg + $sellingareaqty + $qty_per_piece);
		// $new_cost=(($old_unit_cost * $old_qty) + ($new_unit_cost * $qty_per_piece)) / ($old_qty + $qty_per_piece);
	
	
	$totalqty+=$uoms_qty_multiplier*$line_item->quantity;
	$nettotal+=$qty_per_piece*$new_unit_cost;
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
	
		$movement_no=str_pad(getCounter4($movement_code),10,'0',STR_PAD_LEFT);

	//---- MOVEMENTS insertion
		$ToDescription = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])." (".$area.")";
		$from_description = "SAN ROQUE SUPERMARKET  ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])."(".$area.")";
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
		
		//---------------------
		$type=ST_ITEM_TRANSFORMATION;
		update_item_transformation_header($last_inserted_recID,$adj_id,$MovementCode, $movement_no);
		//add_adjustment_head($last_inserted_recID+0,$adj_id,ST_ITEM_TRANSFORMATION, $date_, $movement_loc, $movement_loc2,$reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		
		foreach ($items as $line_item)
		{
		$uoms_qty_multiplier=get_adj_qty_multiplier4($line_item->units);	

		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT ProductCode,Damaged,StockRoom,CostOfSales,SellingArea
		FROM Products WHERE ProductID = '".$line_item->stock_id."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareadmg = $sellingarearow['Damaged'];
		$sellingareabarcode = $sellingarearow['ProductCode'];
		$old_unit_cost=$sellingarearow['CostOfSales'];
		
		$qty_per_piece=$uoms_qty_multiplier*$line_item->quantity;
		$new_unit_cost=$line_item->standard_cost/$qty_per_piece;
		//----UPDATING PRODUCTS [SellingArea]
		  
		 $new_cost=(($old_unit_cost * ($sellingareadmg+$sellingareaqty)) + ($new_unit_cost * $qty_per_piece)) / ($sellingareadmg + $sellingareaqty + $qty_per_piece);

		$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]+".$qty_per_piece.",
		[CostOfSales]='".$new_cost."'
		WHERE ProductID='".$line_item->stock_id."'";
	
		//display_error($sql_upadate_all_input);
		ms_db_query($sql_upadate_all_input);
		
		//----Inserting MovementLine	
		add_adjustment_movement_line4_in($last_inserted_recID,$line_item->stock_id,$sellingareabarcode,$line_item->item_description,$line_item->units,$new_unit_cost+0,$line_item->quantity,$uoms_qty_multiplier,$sellingareabarcode);
		$movement_details_row = get_adjustment_movement_line_details4($last_inserted_recID, $line_item->stock_id);

		
		add_stock_all_adjustment_item4($adj_id, $line_item->stock_id, $movement_loc_code,$date_,$reference,$line_item->quantity, 
		$new_unit_cost+0, $old_unit_cost, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode,$type);
			
		add_transformation_in_detail($adj_id,$line_item->stock_id,$line_item->barcode,$line_item->units,$new_unit_cost, $line_item->quantity,$line_item->item_description,$uoms_qty_multiplier);

		//---HISTORY
				$sellingareastock_room = 'NULL';
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
		CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', $sellingareaqty, $sellingareastock_room, 2, 2,$selling_area_in, $selling_area_out, $stock_room_in, 
		$stock_room_out, '".$movement_details_row['unitcost']."', $damaged_in,$damaged_out, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->ms_user_id."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '',$sellingareadmg, NULL)";
		//display_error($producthistory);
		ms_db_query($producthistory, "error inserting");		
		
	$gl_debit_amount+=$new_cost * $line_item->quantity;
	$gl_credit_amount+=$new_cost * $line_item->quantity;
	}
			
	add_comments(ST_ITEM_TRANSFORMATION, $adj_id, $date_, $memo_);

	$Refs->save(ST_ITEM_TRANSFORMATION, $adj_id, $reference);
	add_audit_trail(ST_ITEM_TRANSFORMATION, $adj_id, $date_);

	commit_transaction();
	return $adj_id;	
}
//=====================END OF ITEM TRANSFORMATION IN========================


function add_stock_all_adjustment_item4($adj_id, $stock_id, $location, $date_, $reference, $quantity, $standard_cost, $old_cost,$units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$barcode,$type)
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
		add_stock_move($type, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
		$show_or_hide=1,$old_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
		$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
}

function getCounter4($type)
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

function add_adjustment_movement_line4_out($last_inserted_recID,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece;
	if ($qty<0) {
	$qty=0;
	}
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
				UOM,unitcost,qty,extended,pack,barcode)
			VALUES ('$last_inserted_recID','$ProductID','$ProductCode','".ms_escape_string($Description)."','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
	ms_db_query($sql);
}


function add_adjustment_movement_line4_in($last_inserted_recID,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece;
	if ($qty<0) {
	$qty=0;
	}
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
				UOM,unitcost,qty,extended,pack,barcode)
			VALUES ('$last_inserted_recID','$ProductID','$ProductCode','".ms_escape_string($Description)."','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
	ms_db_query($sql);
}

function get_adjustment_movement_line_details4($movement_id, $prod_id)
{
	$sql = "SELECT * FROM MovementLine WHERE MovementID ='$movement_id' AND ProductID ='$prod_id'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}

function get_adj_qty_multiplier4($uom)
{
	$sql = "SELECT Qty FROM UOM WHERE UOM='$uom'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}

function add_item_transformation_header($movement_id,$trans_no,$type,$date_,$locationfrom,$locationto,$reference,$person_id,$stats,$MovementCode,$movement_no,$created_by,$posted_by)
{
$stats='';
	$date_posted = date2sql($date_);
	$date_created = date2sql($date_);
	
	$sql="select * from ".TB_PREF."transformation_header where a_trans_no=".db_escape($trans_no)."";
	//display_error($sql);
	$result_id_details=db_query($sql);
	$count=db_num_rows($result_id_details);
	
	if ($count<=0 or $count=='') {
	$sql = "INSERT INTO ".TB_PREF."transformation_header (a_trans_no,a_ms_movement_id_out,a_ms_movement_code_out,a_ms_movement_no_out,a_type,a_date_created,a_date_posted,a_ref,a_from_location,a_to_location,a_created_by,a_posted_by,a_status) 
	VALUES (".db_escape($trans_no).",".db_escape($movement_id).",".db_escape($MovementCode).",".db_escape($movement_no).", ".db_escape($type).",'$date_created', '$date_posted', ".db_escape($reference).", ".db_escape($locationfrom).",
	".db_escape($locationto).",".db_escape($created_by).",".db_escape($posted_by).",".db_escape($stats).")";
	//display_error($sql);
	db_query($sql);
	}
}

function update_item_transformation_header($movement_id,$trans_no,$MovementCode,$movement_no)
{
	$sql = "UPDATE ".TB_PREF."transformation_header SET a_ms_movement_id_in=".db_escape($movement_id).",a_ms_movement_code_in=".db_escape($MovementCode).",a_ms_movement_no_in=".db_escape($movement_no)." where a_trans_no=".db_escape($trans_no).""; 
	//display_error($sql);
	db_query($sql);
}

function add_transformation_out_detail($movement_id,$stock_id,$barcode,$units,$standard_cost, $quantity_out,$description,$multiplier)
{
	$date_created = date2sql($date_);
	$sql = "INSERT INTO ".TB_PREF."transformation_details (transformation_no,stock_id,description,barcode,uom,multiplier,cost,qty_out,qty_in) 
	VALUES (".db_escape($movement_id).",".db_escape($stock_id).",".db_escape($description).",".db_escape($barcode).",".db_escape($units).",
	".db_escape($multiplier).",'$standard_cost','$quantity_out', '0')";
	
	db_query($sql);
	//display_error($sql);
}

function add_transformation_in_detail($movement_id,$stock_id,$barcode,$units,$standard_cost, $quantity_in,$description,$multiplier)
{
	$date_created = date2sql($date_);
	$sql = "INSERT INTO ".TB_PREF."transformation_details (transformation_no,stock_id,description,barcode,uom,multiplier,cost,qty_out,qty_in) 
	VALUES (".db_escape($movement_id).",".db_escape($stock_id).",".db_escape($description).",".db_escape($barcode).",".db_escape($units).",
	".db_escape($multiplier).",'$standard_cost','0', '$quantity_in')";	
	db_query($sql);
	//display_error($sql);
}

function add_adjustment_head($movement_id,$trans_no,$type,$date_,$location,$location2,$reference,$person_id,$stats,$MovementCode,$movement_no,$created_by,$posted_by)
{
	$date_posted = date2sql($date_);

	$date_created = date2sql($date_);
	$sql = "INSERT INTO ".TB_PREF."adjustment_header (a_trans_no,a_ms_movement_id,a_movement_code,a_movement_no,a_type,a_date_created,a_date_posted,a_ref,a_from_location,a_to_location,a_created_by,a_posted_by,a_status) 
	VALUES (".db_escape($trans_no).",".db_escape($movement_id).",".db_escape($MovementCode).",".db_escape($movement_no).", ".db_escape($type).",'$date_created', '$date_posted', ".db_escape($reference).", ".db_escape($location).",
	".db_escape($location2).",".db_escape($created_by).",".db_escape($posted_by).",".db_escape($stats).")";
	//display_error($sql);
	db_query($sql);
}
?>