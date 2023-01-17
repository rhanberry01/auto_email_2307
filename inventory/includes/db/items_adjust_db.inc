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

function add_stock_adjustment($items, $location, $date_, $type, $reference, $memo_)
{
	global $Refs;

	begin_transaction();

	$adj_id = get_next_trans_no(ST_INVADJUST);
	
	foreach ($items as $line_item)
	{
	
//	$item_code = check_my_items_($line_item);
	
	 // if ($item_code === false)
	// {
	  // return true;
	// }
	
	$uoms_qty_multiplier=get_qty_multiplier($line_item->units);
	
	$totalqty+=$uoms_qty_multiplier*$line_item->quantity;
	$nettotal+=($uoms_qty_multiplier*$line_item->quantity)*$line_item->standard_cost;
	}

	

		$sql_select_all_input="select * from ".TB_PREF."movement_types where id='$type'";
		$res=db_query($sql_select_all_input);
		$row = db_fetch($res);
		$movement_code=$row['movement_code'];
		//display_error($sql_select_all_input);

		
		$ToAddress = $ContactPerson = '';
		if ($movement_code == 'PASA')
		{
		$MovementCode = 'PASA';
		$movement_no=str_pad(getPASACounter(),10,'0',STR_PAD_LEFT);
		$ToDescription='SELLING AREA';
		$area = 'SELLING AREA';
		$flow_stockroom = 0;
		$flow_sa = 2;
		$flow_dmg = 0;
		$prod_history_desc = 'Positive Adjustment(SA)';
		}

		if ($movement_code == 'NASA')
		{
		$MovementCode = 'NASA';
		$movement_no=str_pad(getNASACounter(),10,'0',STR_PAD_LEFT);
		$area = 'SELLING AREA';
		$flow_stockroom = 0;
		$flow_sa = 1;
		$flow_dmg = 0;
		$prod_history_desc = 'Negative Adjustment(SA)';
		//display_error($movement_no);
		}
	
		//---- MOVEMENTS insertion
		$from_description = "SAN ROQUE SUPERMARKET   ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"])." ($area)";
		$sql = "INSERT INTO Movements (MovementNo,MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,
		ContactPerson,FromDescription,FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,
		Terms,TransactionDate,FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,
		BranchCode,CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
		WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
		VALUES ('$movement_no','$MovementCode','','','','$ToDescription','$ToAddress','$ContactPerson','$from_description','',
		'".Today()." 00:00:00','".$_SESSION['wa_current_user']->user."','".Today()." 00:00:00','2','".$_SESSION['wa_current_user']->user."',
		'".Today()." 00:00:00',0,'".Today()." 00:00:00','NULL','".$nettotal."','POSTED','".$totalqty."',
		'".$_SESSION['wa_current_user']->user."','$remarks','NULL','NULL','NULL','','','','','','0','0','0','0','0','1','PHP','0','')";
		//display_error($sql);
		ms_db_query($sql);
		//---------------------

		//---- get last ms movements id
		$last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
		$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
		$last_inserted_recID = $last_inserted_line_row['LAST'];
		
		
		foreach ($items as $line_item)
		{
		$uoms_qty_multiplier=get_qty_multiplier($line_item->units);
		
		// if ($MovementCode != 'PASA')
			// $line_item->quantity = -$line_item->quantity;

		//INSERT TO 0_stock_moves
		add_stock_adjustment_item($adj_id, $line_item->stock_id, $location, $date_, $type, $reference,
		$line_item->quantity, $line_item->standard_cost, $line_item->units, $uoms_qty_multiplier, $memo_);
	
	
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
		 if ($movement_code == 'PASA')
		 {
		 $sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea] + ".$qty_per_piece."
		 WHERE ProductID='".$line_item->stock_id."'";
		 }
		 if ($movement_code  == 'NASA')
		 {
		 // $sql_upadate_all_input="UPDATE Products SET [SellingArea]=([SellingArea] - ".$qty_per_piece.")
		 // WHERE ProductID='".$line_item->stock_id."'";
		 
		   $sql_upadate_all_input="UPDATE Products SET [SellingArea]=CASE WHEN [SellingArea]<=0 then 0 else [SellingArea]-".$qty_per_piece." end
		WHERE ProductID='".$line_item->stock_id."'";
		 
		 }
		//display_error($sql_upadate_all_input);
		ms_db_query($sql_upadate_all_input,'failed to process adjustments');
		
		//----Inserting MovementLine
		add_ms_movement_line($last_inserted_recID,$line_item->stock_id,$sellingareabarcode,$line_item->item_description,$line_item->units,$line_item->standard_cost,$line_item->quantity,$uoms_qty_multiplier,$sellingareabarcode);
	
	
		$movement_details_row = get_movement_line_details($last_inserted_recID, $line_item->stock_id);

		if ($movement_code == 'PASA')
		{
		$MovementCode = 'PASA';
		$selling_area_in=$movement_details_row['qty'] * $movement_details_row['pack'];
		$selling_area_out='NULL';
		}
		
		if ($movement_code == 'NASA')
		{
		$MovementCode = 'NASA';
		$selling_area_in='NULL';
		$selling_area_out=$movement_details_row['qty'] * $movement_details_row['pack'];
		}
		
		$producthistory = "INSERT INTO ProductHistory ([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
		,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
		,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
		,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
		,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
		VALUES('".$line_item->stock_id."','".$sellingareabarcode."','".$last_inserted_recID."','".$movement_no."',CURRENT_TIMESTAMP, 
		CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', '".$sellingareaqty."', NULL, 2, 2,".$selling_area_in.",".$selling_area_out.", NULL, 
		NULL, '".$movement_details_row['unitcost']."', NULL,NULL, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->user."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '', NULL, NULL)";
		//display_error($producthistory);
		ms_db_query($producthistory);		
	}
	
	add_comments(ST_INVADJUST, $adj_id, $date_, $memo_);

	$Refs->save(ST_INVADJUST, $adj_id, $reference);
	add_audit_trail(ST_INVADJUST, $adj_id, $date_);

	commit_transaction();

	return $adj_id;
}

//-------------------------------------------------------------------------------------------------------------

function void_stock_adjustment($type_no)
{
	void_gl_trans(ST_INVADJUST, $type_no);
	void_stock_move(ST_INVADJUST, $type_no);
}

//-------------------------------------------------------------------------------------------------------------

function get_stock_adjustment_items($trans_no)
{
	$result = get_stock_moves(ST_INVADJUST, $trans_no);

	if (db_num_rows($result) == 0)
	{
		return null;
	}

	return $result;
}

//--------------------------------------------------------------------------------------------------

function add_stock_adjustment_item($adj_id, $stock_id, $location, $date_, $type, $reference,
	$quantity, $standard_cost, $units, $uoms_qty_multiplier, $memo_)
{
	$mb_flag = get_mb_flag($stock_id);

    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
    }
	
	//updating material cost.
	update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);

	
	
		//add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $type);
	//add to 0_stock_moves
	add_stock_move(ST_INVADJUST, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $type, 
	$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
	$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0);
	
	if ($standard_cost > 0)
	{
		//selecting 0_stock_master data.
		$stock_gl_codes = get_stock_gl_code($stock_id);
		$inventory_gl_account = $stock_gl_code["inventory_account"];
		$adjustment_gl_account=$stock_gl_codes['adjustment_account'];
	
		//inserting 0_stock_master data selected.
		add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, $adjustment_gl_account, $stock_gl_codes['dimension_id'], $stock_gl_codes['dimension2_id'], $memo_, ($standard_cost * -($quantity)));
		add_gl_trans_std_cost(ST_INVADJUST, $adj_id, $date_, $inventory_gl_account, 0, 0, $memo_, ($standard_cost * $quantity));
	}
}

//-------------------------------------------------------------------------------------------------------------
function check_my_items_($line_item)
{
	$stock_id = $line_item;
	//display_error($stock_id);
	$sql = "SELECT * FROM ".TB_PREF."stock_master WHERE stock_id =".db_escape($stock_id);
	//display_error($sql);
	$res = db_query($sql);
	
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
	//display_error($v_sql);
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
			//display_error($update_sql);
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
		//display_error($ins_sql);	
		db_query($ins_sql, 'failed to insert stock in master');
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
			 ".db_escape($v_row['CostOfSales']+0).",  
			 ".db_escape($v_row['CostOfSales']+0).")";
		 db_query($ins_sql, 'failed to insert stock in master');
		return false;
	}
	
	return $stock_id;
}


function getPASACounter()
{
	$sql_re = "SELECT Counter FROM Counters WHERE TransactionTypeCode = 'PASA'";
	$res = ms_db_query($sql_re);
	$trancode =  mssql_fetch_array($res);
	$tran_no = $trancode['Counter'] + 1;
	
	$sql = "UPDATE [Counters] SET Counter = $tran_no WHERE TransactionTypeCode = 'PASA'";
	ms_db_query($sql);
	// display_error($sql);
	return $tran_no;
}

function getNASACounter()
{
	$sql_re = "SELECT Counter FROM Counters WHERE TransactionTypeCode = 'NASA'";
	$res = ms_db_query($sql_re);
	$trancode =  mssql_fetch_array($res);
	$tran_no = $trancode['Counter'] + 1;
	
	$sql = "UPDATE [Counters] SET 
				Counter = $tran_no
			WHERE TransactionTypeCode = 'NASA'";
	ms_db_query($sql);
	return $tran_no;
}



function add_ms_movement_line($last_inserted_recID,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
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

function update_ms_movement_line($movement_id_2_update,$ProductID,$ProductCode,$Description,$UOM,$price_per_piece,$qty,$pack,$barcode)
{
	$unit_cost=$price_per_piece*$pack;
	if ($qty<0) {
	$qty=0;
	}
	
		$movement_details_row = get_movement_line_details($movement_id_2_update, $ProductID);

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
		VALUES ('$movement_id_2_update','$ProductID','$ProductCode','$Description','$UOM','$unit_cost','$qty',".round($unit_cost*$qty,4).",'$pack','$barcode')";
		//display_error($sql);
		ms_db_query($sql);
		}
}

function get_movement_line_details($movement_id, $prod_id)
{
	$sql = "SELECT * FROM MovementLine WHERE MovementID ='$movement_id' AND ProductID ='$prod_id'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}

function get_qty_multiplier($uom)
{
	$sql = "SELECT Qty FROM UOM WHERE UOM='$uom'";
	//display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}
?>