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
function new_stock_transfer_out($items, $location, $location2, $date_, $status_type,$memo_,$get_trans_no)
{
	global $Refs;
	begin_transaction();
	
	$x=get_nextmov_no();
	//$reference=get_next_trans_no(ST_TRANSFEROUT);
	$adj_id=$x;
	$m_type=ST_TRANSFEROUT;
	
	
	foreach ($items as $line_item)
	{
	
	// $item_code = check_my_items_($line_item->stock_id);
	
	// if ($item_code === false)
	// {
		// return true;
	// }
	$uoms_qty_multiplier=out_get_qty_multiplier($line_item->units);
	$totalqty+=$uoms_qty_multiplier*$line_item->quantity;
	$nettotal+=($uoms_qty_multiplier*$line_item->quantity)*$line_item->standard_cost;
	}
	
	
	$MovementCode = 'STO';
		
	$treasurer_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$user_real_name = $u['real_name'];
	$my_header_id=add_transfers_out_head($date_,$location,$location2,$MovementCode,$user_real_name,$memo_);
	
	$db_133 = get_branch_db_56($location);
	$db_133_2 = get_branch_db_56($location2);
	
	foreach ($items as $line_item)
	{
	$uoms_qty_multiplier=out_get_qty_multiplier($line_item->units);

	add_transfer_out_det($my_header_id,$line_item->stock_id,$line_item->barcode,$line_item->units,
	$line_item->standard_cost+0, $line_item->quantity,$line_item->item_description,$db_133,$db_133_2);

	$gl_debit_amount+=$line_item->standard_cost * $line_item->quantity;
	$gl_credit_amount+=$line_item->standard_cost * -$line_item->quantity;
	}


	//add_comments(ST_TRANSFEROUT, $adj_id, $date_, $memo_);
	//$Refs->save(ST_TRANSFEROUT, $adj_id, $reference); //comment for testing.

	//add_audit_trail(ST_TRANSFEROUT, $adj_id, $date_);
	commit_transaction();

	return $adj_id;
}

function new_stock_transfer_out_caravan($items, $location, $location2, $date_, $status_type,$memo_,$get_trans_no)
{
	global $Refs;
	begin_transaction();
	
	$x=get_nextmov_no();
	//$reference=get_next_trans_no(ST_TRANSFEROUT);
	$adj_id=$x;
	$m_type=ST_TRANSFEROUT;
	
	
	foreach ($items as $line_item)
	{
	
	// $item_code = check_my_items_($line_item->stock_id);
	
	// if ($item_code === false)
	// {
		// return true;
	// }
	$uoms_qty_multiplier=out_get_qty_multiplier($line_item->units);
	$totalqty+=$uoms_qty_multiplier*$line_item->quantity;
	$nettotal+=($uoms_qty_multiplier*$line_item->quantity)*$line_item->standard_cost;
	}
	
	
	$MovementCode = 'STO';
		
	$treasurer_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$user_real_name = $u['real_name'];

	$my_header_id=add_transfers_out_head_caravan($date_,$location,$location2,$MovementCode,$user_real_name,$memo_);
	
	$db_133 = get_branch_db_56($location);
	$db_133_2 = get_branch_db_56($location2);
	
	foreach ($items as $line_item)
	{
	$uoms_qty_multiplier=out_get_qty_multiplier($line_item->units);

	add_transfer_out_det_caravan($my_header_id,$line_item->stock_id,$line_item->barcode,$line_item->units,
	$line_item->standard_cost+0, $line_item->quantity,$line_item->item_description,$db_133,$db_133_2);

	$gl_debit_amount+=$line_item->standard_cost * $line_item->quantity;
	$gl_credit_amount+=$line_item->standard_cost * -$line_item->quantity;
	}


	//add_comments(ST_TRANSFEROUT, $adj_id, $date_, $memo_);
	//$Refs->save(ST_TRANSFEROUT, $adj_id, $reference); //comment for testing.

	//add_audit_trail(ST_TRANSFEROUT, $adj_id, $date_);
	commit_transaction();

	return $my_header_id;
}



function get_branch_db_133($loc_code)
{
	$sql = "SELECT db_133 FROM transfers.0_branches
				WHERE `code` = ".db_escape($loc_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

function get_branch_db_56($loc_code)
{
	$sql = "SELECT db_56 FROM transfers.0_branches
				WHERE `code` = ".db_escape($loc_code);
				//display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

function add_stock_transfer_out($items, $location, $location2, $date_, $status_type, $reference, $memo_,$get_trans_no)
{
	global $Refs;
	begin_transaction();

		$stats=2;
		$movement_status='POSTED';
		$posted_by=$_SESSION['wa_current_user']->user;
		$created_by=$_SESSION['wa_current_user']->user;
	
	//$adj_id = get_next_trans_no(ST_TRANSFEROUT);
	$adj_id=$reference;
	
	foreach ($items as $line_item)
	{
	//display_error($line_item->stock_id);
	
	$item_code = check_my_items_($line_item->stock_id);
	
	if ($item_code === false)
	{
		return true;
	}
	
	$uoms_qty_multiplier=out_get_qty_multiplier($line_item->units);
	
	$totalqty+=$uoms_qty_multiplier*$line_item->quantity;
	$nettotal+=($uoms_qty_multiplier*$line_item->quantity)*$line_item->standard_cost;
	}
		// $sql_select_all_input="select * from ".TB_PREF."movement_types where id='$type'";
		// $res=db_query($sql_select_all_input);
		// $row = db_fetch($res);
		// $movement_code=$row['movement_code'];
		//display_error($sql_select_all_input);

		//$MovementCode = $movement_code;
		$MovementCode = 'STO';
		
	
		$Tobr_code='NULL';
		$ToDescription='';
		$ToAddress='';
		$ContactPerson='';
		
		$Frombr_code='NULL';
		$FromDescription = "SAN ROQUE SUPERMARKET ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])."";
		$FromAddress='';
		
		$area = 'SELLING AREA';
		$flow_stockroom = 1;
		$flow_sa = 0;
		$flow_dmg = 0;
		$prod_history_desc = 'STOCK TRANSFER OUT';
		

		$movement_no=str_pad(getSTOCounter(),10,'0',STR_PAD_LEFT);
	
	//---- MOVEMENTS insertion
		$sql = "INSERT INTO Movements (MovementNo,MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,
		ContactPerson,FromDescription,FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,
		Terms,TransactionDate,FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,
		BranchCode,CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
		WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
		VALUES ('$movement_no','$MovementCode','','','','$ToDescription','$ToAddress','$ContactPerson','$FromDescription ($area)','',
		'".Today()." 00:00:00','".$_SESSION['wa_current_user']->user."','".Today()." 00:00:00','$stats','$posted_by',
		'".Today()." 00:00:00',0,'".Today()." 00:00:00','NULL','".$nettotal."','$movement_status','".$totalqty."',
		'$created_by','$remarks','NULL','NULL','$Tobr_code','','','$Tobr_code','$Frombr_code','','0','0','0','0','0','1','PHP','0','')";
		//display_error($sql);
		ms_db_query($sql);

		//---- get last ms movements id
		$last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
		$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
		$last_inserted_recID = $last_inserted_line_row['LAST'];
		
		//---------------------
		
		$ms_user_id=$_SESSION['wa_current_user']->ms_user_id;
		//add_transfer_out_header($last_inserted_recID,$adj_id,ST_TRANSFEROUT, $date_, $reference, $location2,$location,$MovementCode,$movement_no,$ms_user_id);
		
		foreach ($items as $line_item)
		{
		$uoms_qty_multiplier=out_get_qty_multiplier($line_item->units);
		//INSERT TO 0_stock_moves
		
		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT ProductCode,SellingArea FROM Products WHERE ProductID = '".$line_item->stock_id."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareabarcode = $sellingarearow['ProductCode'];
		
		//----UPDATING PRODUCTS [SellingArea]
		 $qty_per_piece=$uoms_qty_multiplier*$line_item->quantity;
		 
	
		$sql_upadate_all_input="UPDATE Products SET [SellingArea]=CASE WHEN [SellingArea]<=0 then 0 else [SellingArea]-".$qty_per_piece." end
		WHERE ProductID='".$line_item->stock_id."'";
		
		//display_error($sql_upadate_all_input);
		ms_db_query($sql_upadate_all_input);
	

		out_add_ms_movement_line($last_inserted_recID,$line_item->stock_id,$sellingareabarcode,$line_item->item_description,$line_item->units,$line_item->standard_cost,$line_item->quantity,$uoms_qty_multiplier,$sellingareabarcode);
		$movement_details_row = out_get_movement_line_details($last_inserted_recID, $line_item->stock_id);
	
		add_stock_transfer_out_item($adj_id, $line_item->stock_id, $location,$date_,$reference,$line_item->quantity, 
		$line_item->standard_cost, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode);
		

		$selling_area_in='NULL';
		$selling_area_out=$movement_details_row['qty'] * $movement_details_row['pack'];
		
		$movement_id_2_insert=$last_inserted_recID;

		$producthistory = "INSERT INTO ProductHistory ([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
		,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
		,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
		,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
		,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
		VALUES('".$line_item->stock_id."','".$sellingareabarcode."','".$movement_id_2_insert."','".$movement_no."',CURRENT_TIMESTAMP, 
		CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', '".$sellingareaqty."', NULL, 2, 2,$selling_area_in,".$selling_area_out.", NULL, 
		NULL, '".$movement_details_row['unitcost']."', NULL,NULL, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->user."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '', NULL, NULL)";
		//display_error($producthistory);
		ms_db_query($producthistory);		

	
	$gl_debit_amount+=$line_item->standard_cost * $line_item->quantity;
	$gl_credit_amount+=$line_item->standard_cost * -$line_item->quantity;
	}
	
		if ($line_item->standard_cost > 0)
		{
			//selecting 0_stock_master data.
			$stock_gl_codes = get_stock_gl_code($stock_id);
			$inventory_gl_account = $stock_gl_code["inventory_account"];
			$adjustment_gl_account=$stock_gl_codes['adjustment_account'];
		
			//inserting 0_stock_master data selected.
			add_gl_trans_std_cost(ST_TRANSFEROUT, $adj_id, $date_, $adjustment_gl_account, 0, 0, $memo_, $gl_debit_amount);
			add_gl_trans_std_cost(ST_TRANSFEROUT, $adj_id, $date_, $inventory_gl_account, 0, 0, $memo_, $gl_credit_amount);
		}
	

	add_comments(ST_TRANSFEROUT, $adj_id, $date_, $memo_);
	$Refs->save(ST_TRANSFEROUT, $adj_id, $reference);

	add_audit_trail(ST_TRANSFEROUT, $adj_id, $date_);
	commit_transaction();

	return $adj_id;
}



function add_new_transfer_out_item($adj_id, $stock_id, $location, $date_, $reference, $quantity, $standard_cost, $units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$barcode)
{
		
	$mb_flag = get_mb_flag($stock_id);

    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do stock transfer out for Service item : $stock_id", "");
    }
		
		add_transfer_out_details($adj_id,$stock_id,$barcode,$units,$standard_cost, $quantity);		
}




function add_stock_transfer_out_item($adj_id, $stock_id, $location, $date_, $reference, $quantity, $standard_cost, $units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$barcode)
{
		
	$mb_flag = get_mb_flag($stock_id);

    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
    }
	

	//updating material cost.
	update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);
	
		//---- select all temporary mysql movements header and details
		$sql_stock_id="select stock_id from ".TB_PREF."stock_moves where trans_no='$reference' and stock_id='$stock_id'";
		//display_error($sql_stock_id);
		$res=db_query($sql_stock_id);
		$row = db_fetch($res);
		$check_stock_id=$row['stock_id'];
		
		add_stock_move(ST_TRANSFEROUT, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
		$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
		$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
		
		add_transfer_out_details($adj_id,$stock_id,$barcode,$units,$standard_cost, $quantity);		
}

function getSTOCounter()
{
	$sql_re = "SELECT Counter FROM Counters WHERE TransactionTypeCode = 'STO'";
	$res = ms_db_query($sql_re);
	$trancode =  mssql_fetch_array($res);
	$tran_no = $trancode['Counter'] + 1;
	
	$sql = "UPDATE [Counters] SET 
				Counter = $tran_no
			WHERE TransactionTypeCode = 'STO'";
	ms_db_query($sql);
	return $tran_no;
}


function out_add_ms_movement_line($last_inserted_recID,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece*$pack;
	if ($qty<0) {
	$qty=0;
	}
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
				UOM,unitcost,qty,extended,pack,barcode)
			VALUES ('$last_inserted_recID','$ProductID','$ProductCode','$Description','$UOM','$unit_cost','$qty',".round($unit_cost*$qty,4).",'$pack','$barcode')";
	ms_db_query($sql);
}


function out_get_movement_line_details($movement_id, $prod_id)
{
	$sql = "SELECT * FROM MovementLine WHERE MovementID ='$movement_id' AND ProductID ='$prod_id'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}


function out_get_qty_multiplier($uom)
{
	$sql = "SELECT Qty FROM UOM WHERE UOM='$uom'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}

function add_transfers_out_head_caravan($date_,$br_location,$br_location2,$MovementCode,$name_out,$memo_)
{
	$date_created = date2sql($date_);
	$sql = "INSERT INTO transfers_caravan.".TB_PREF."transfer_header (date_created,delivery_date,
	br_code_out,m_code_out,br_code_in,requested_by,memo_) 
	VALUES ('".date2sql(Today())."', '$date_created',".db_escape($br_location).",".db_escape($MovementCode)."
	,".db_escape($br_location2).",".db_escape($name_out).",".db_escape($memo_).")";
	
		db_query($sql,"Failed to Insert.");

		$id = db_insert_id();
		return $id;
}

function add_transfers_out_head($date_,$br_location,$br_location2,$MovementCode,$name_out,$memo_)
{
	$date_created = date2sql($date_);
	$sql = "INSERT INTO transfers.".TB_PREF."transfer_header (date_created,delivery_date,
	br_code_out,m_code_out,br_code_in,requested_by,memo_) 
	VALUES ('".date2sql(Today())."', '$date_created',".db_escape($br_location).",".db_escape($MovementCode)."
	,".db_escape($br_location2).",".db_escape($name_out).",".db_escape($memo_).")";
	
	db_query($sql,"Failed to Insert.");
	//display_error($sql);
$id = db_insert_id();
return $id;
}

function add_transfer_out_det($movement_id,$stock_id,$barcode,$units,$standard_cost, $quantity_out,$description,$db_133,$db_133_2)
{
	$date_created = date2sql($date_);
	
	$prod_id2 = get_branch_stock_id_from_56($db_133,$db_133_2, $stock_id,$description);
	
	// display_error($movement_id);
	// display_error($stock_id);
	// display_error($description);
	// display_error($barcode);
	// display_error($units);
	
	$sql = "INSERT INTO transfers.".TB_PREF."transfer_details (transfer_id,stock_id,stock_id_2,description,barcode,uom,cost,qty_out,qty_in) 
	VALUES ('$movement_id','$stock_id','$prod_id2','".mysql_escape_string($description)."','$barcode','$units','$standard_cost','$quantity_out', '0')";
	
	db_query($sql,"Failed to Insert. Please check connection or GlobalID of the items.");
	//display_error($sql);
}


function add_transfer_out_det_caravan($movement_id,$stock_id,$barcode,$units,$standard_cost, $quantity_out,$description,$db_133,$db_133_2)
{
	$date_created = date2sql($date_);
	
	$prod_id2 = get_branch_stock_id_from_56($db_133,$db_133_2, $stock_id,$description);
	
	$sql = "INSERT INTO transfers_caravan.".TB_PREF."transfer_details (transfer_id,stock_id,stock_id_2,description,barcode,uom,cost,qty_out,qty_in) 
	VALUES ('$movement_id','$stock_id','$prod_id2','".mysql_escape_string($description)."','$barcode','$units','$standard_cost','$quantity_out', '0')";
	
	db_query($sql,"Failed to Insert. Please check connection or GlobalID of the items.");
	//display_error($sql);
}



function get_branch_stock_id($db_133, $db_133_2, $stock_id,$description)
{
	//connect to db 133
	$ms_db_132 = mssql_connect('192.168.0.133' , 'sa', 'tseug');
	//display_error($db_133);
	
	if($ms_db_132){
		display_error('Connected in 133 database 1');
	}
	else{
		display_error('Failed to connect in 133 database 1');
		return false;
	}

    mssql_select_db($db_133,$ms_db_132);
					
	//get global id
	$sql = "SELECT GlobalID FROM Products WHERE ProductID =  $stock_id";
	//display_error($sql);
	$res = mssql_query($sql, $ms_db_132);
	$row = mssql_fetch_array($res);
	
	$global_id = $row[0];
	
	mssql_close($ms_db_133);
	
	if ($global_id == '')
	{
		display_error('(from Location) No Global ID found for :  '.$description);
		// display_footer_exit();
		return false;
	}
	
	
	//connect to db 133 2
	$ms_db_133 = mssql_connect('192.168.0.133' , 'sa', 'tseug');
	
	if(!$ms_db_133){
		display_error('Failed to connect in 133 database 2');
		return false;
	}
	
	
    mssql_select_db($db_133_2,$ms_db_133);
					
	//get product id
	$sql = "SELECT ProductID FROM Products WHERE GlobalID =  $global_id";
	//display_error($sql);
	$res = mssql_query($sql, $ms_db_133);
	$row = mssql_fetch_array($res);
	$global_id = $row[0];
	
	mssql_close($ms_db_133);
	
	if ($global_id == '')
	{
		display_error('(To Location) No Global ID found for this item:  '.$description);
		// display_footer_exit();
		return false;
	}
	return $global_id;
}


function get_branch_stock_id_from_56($db_133, $db_133_2, $stock_id,$description)
{
	
		// display_error($db_133);
		// display_error($db_133_2);
		
		$link = mysql_connect('192.168.0.56', 'root', '');
		if (!$link) {
			die('Could not connect: ' . mysql_error());
		}

		$db_selected = mysql_select_db($db_133, $link);
		if (!$db_selected) {
			die ('Cant use db : ' . mysql_error());
		}

					
		//get global id
		$sql = "SELECT GlobalID FROM products WHERE ProductID =  $stock_id ORDER BY LastDateModified DESC
		
		";
		//display_error($sql);
		$res = mysql_query($sql, $link);
		$row = mysql_fetch_array($res);
		
		$global_id = $row[0];
		
		//display_error($global_id);
		mysql_close($link);
		
		if ($global_id == '')
		{
			display_error('(from Location) No Global ID found for :  '.$description);
			// display_footer_exit();
			return false;
		}
		
	
	
		$link = mysql_connect('192.168.0.56', 'root', '');
		if (!$link) {
			die('Could not connect: ' . mysql_error());
		}
		
		// make foo the current db
		$db_selected = mysql_select_db($db_133_2, $link);
		if (!$db_selected) {
		die ('Cant use db : ' . mysql_error());
		}

		//get product id
		$sql = "SELECT ProductID FROM products WHERE GlobalID =  $global_id ORDER BY LastDateModified DESC";
		//display_error($sql);
		$res = mysql_query($sql, $link);
		$row = mysql_fetch_array($res);
		$global_id = $row[0];
		
		//display_error($global_id);
		mysql_close($link);
		
		if ($global_id == '')
		{
			display_error('(To Location) No Global ID found for this item:  '.$description);
			// display_footer_exit();
			return false;
		}
		
	return $global_id;
}

?>