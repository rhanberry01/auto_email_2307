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

function add_adjustment_details($items, $date_,$type, $status_type, $reference, $memo_,$get_trans_no)
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
	
	
	foreach ($items as $line_item)
	{
	
	$item_code = check_my_items_($line_item->stock_id);
	
	if ($item_code === false)
	{
		return true;
	}
	
	$uoms_qty_multiplier=get_adj_qty_multiplier($line_item->units);
	
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
		


if ($stats==1) {	//=========================IF OPEN=========================

		if ($get_trans_no=='') {
		add_adjustment_header($last_inserted_recID+0,$adj_id,ST_INVADJUST, $date_, $movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		}
		else {
		update_adjustment_header($last_inserted_recID,$adj_id,$MovementCode, $date_, $movement_loc, $reference, $_SESSION['wa_current_user']->user, $stats, $MovementCode, $movement_no,$created_by,$posted_by);
		}
	
	foreach ($items as $line_item)
	{
	$uoms_qty_multiplier=get_adj_qty_multiplier($line_item->units);
	
	add_stock_all_adjustment_item($adj_id, $line_item->stock_id, $movement_loc_code,$date_,$reference,$line_item->quantity,
	$line_item->standard_cost+0, $line_item->units, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$line_item->barcode);
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
		'".Today()." 00:00:00','".$_SESSION['wa_current_user']->ms_user_id."','".Today()." 00:00:00','$stats','".$_SESSION['wa_current_user']->ms_user_id."',
		'".Today()." 00:00:00',0,'".Today()." 00:00:00','NULL','".$nettotal."','$movement_status','".$totalqty."',
		'".$_SESSION['wa_current_user']->ms_user_id."','$remarks','NULL','NULL','NULL','','','','','','0','0','0','0','0','1','PHP','0','')";
		//display_error($sql);
		ms_db_query($sql);
		
		//---- get last ms movements id
		$last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
		$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
		$last_inserted_recID = $last_inserted_line_row['LAST'];
		
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
		 
		if ($stats=='2')
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
				$sellingareadmg='NULL';
				$selling_area_in='NULL';
				$selling_area_out=$movement_details_row['qty'] * $movement_details_row['pack'];
				$damaged_in='NULL';
				$damaged_out='NULL';
				}
				else {
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
				$sellingareadmg='NULL';
				$selling_area_in=$movement_details_row['qty'] * $movement_details_row['pack'];
				$selling_area_out='NULL';
				$damaged_in='NULL';
				$damaged_out='NULL';
				}
				else {
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
		VALUES('".$line_item->stock_id."','".$sellingareabarcode."','".$movement_id_2_insert."','".$movement_no."',CURRENT_TIMESTAMP, 
		CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', $sellingareaqty, NULL, 2, 2,$selling_area_in, $selling_area_out, NULL, 
		NULL, '".$movement_details_row['unitcost']."', $damaged_in,$damaged_out, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->ms_user_id."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '',$sellingareadmg, NULL)";
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
		
	add_comments(ST_INVADJUST, $adj_id, $date_, $memo_);

	if ($get_trans_no=='') {
	$Refs->save(ST_INVADJUST, $adj_id, $reference);
	}
	
	add_audit_trail(ST_INVADJUST, $adj_id, $date_);

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
	update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);
	
	if ($get_trans_no=='') {
	//display_error("OPEN");
	
	//add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $type);
	//add to 0_stock_moves
	add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
	$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
	$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
	}
	else {
	//display_error("POSTED");
		//---- select all temporary mysql movements header and details
		$sql_stock_id="select stock_id from ".TB_PREF."stock_moves where trans_no='$reference' and stock_id='$stock_id'";
		//display_error($sql_stock_id);
		$res=db_query($sql_stock_id);
		$row = db_fetch($res);
		$check_stock_id=$row['stock_id'];
		
		if ($check_stock_id!='') {
		update_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
		$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
		$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
		}
		else {
		add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
		$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
		$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
		}
	
	}
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
	ms_db_query($sql);
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
			VALUES ('$last_inserted_recID','$ProductID','$ProductCode','$Description','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
	ms_db_query($sql);
}

function update_adjustment_movement_line($movement_id_2_update,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece;
	if ($qty<0) {
	$qty=0;
	}
	
		$movement_details_row = get_adjustment_movement_line_details($movement_id_2_update, $ProductID);

		if ($movement_details_row['ProductID']!='') {
		$sql = "UPDATE MovementLine SET ProductCode='$ProductCode',Description='$Description',UOM='$UOM',unitcost='$unit_cost',
		qty='$qty',extended=".round($unit_cost*$qty,4).",pack='$pack',barcode='$barcode'
		WHERE MovementID= '$movement_id_2_update' AND ProductID='$ProductID'";
		//display_error($sql);
		ms_db_query($sql);
		}
		else {
		$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
		UOM,unitcost,qty,extended,pack,barcode)
		VALUES ('$movement_id_2_update','$ProductID','$ProductCode','$Description','$UOM','$unit_cost','$qty',".round($unit_cost*($qty*$pack),4).",'$pack','$barcode')";
		//display_error($sql);
		ms_db_query($sql);
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
	
	$date_created = date2sql($date_);
	$sql = "INSERT INTO ".TB_PREF."adjustment_header (a_trans_no,a_ms_movement_id,a_movement_code,a_movement_no,a_type,a_date_created,a_date_posted,a_ref,a_from_location,a_to_location,a_created_by,a_posted_by,a_status) 
	VALUES (".db_escape($trans_no).",".db_escape($movement_id).",".db_escape($MovementCode).",".db_escape($movement_no).", ".db_escape($type).",'$date_created', '$date_posted', ".db_escape($reference).", ".db_escape($location).",
	".db_escape($location).",".db_escape($created_by).",".db_escape($posted_by).",".db_escape($stats).")";
	//display_error($sql);
	db_query($sql);
}

function update_adjustment_header($movement_id,$trans_no,$adjustment_type,$date_,$location,$reference,$person_id,$stats,$MovementCode,$movement_no,$created_by,$posted_by)
{
$date = date2sql($date_);
$sql = "UPDATE ".TB_PREF."adjustment_header SET a_ms_movement_id=".db_escape($movement_id).",a_movement_code=".db_escape($adjustment_type).",a_movement_no=".db_escape($movement_no).",
a_date_posted='$date',a_from_location= ".db_escape($location).",a_to_location=".db_escape($location).",a_posted_by=".db_escape($posted_by).",a_status=".db_escape($stats)." 
WHERE a_trans_no=".db_escape($trans_no)."";
//display_error($sql);
db_query($sql);
}
?>