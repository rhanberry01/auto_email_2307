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

function approve_stock_withrawal_req($id,$user_id)
{
$sql = "UPDATE transfers.0_stocks_withdrawal_header SET manager_approved='1', approved_by=".db_escape($user_id).", released_date='".date2sql(Today())."' where id='$id'";
		db_query($sql, "could not update manager's approval.");
		//display_error($sql);
}

function nature_of_req_gl($id)
{
	$sql = "SELECT gl_debit FROM transfers.0_request_nature WHERE id = ".db_escape($id);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_gl_stock_to($br_code)
{
	$sql = "SELECT gl_stock_to FROM transfers.0_branches WHERE code = ".db_escape($br_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_gl_stock_from($br_code)
{
	$sql = "SELECT gl_stock_from FROM transfers.0_branches WHERE code = ".db_escape($br_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function add_gl_trans_stock_withdrawal($aria_db,$m_type, $transfer_id, $date_, $account, $dimension, $dimension2, $memo_, $net_total)
{
	$sql = "INSERT INTO $aria_db.".TB_PREF."gl_trans (type, type_no, tran_date,
		account, dimension_id, dimension2_id, memo_, amount)";

	$sql .= "VALUES (".db_escape($m_type).", ".db_escape($transfer_id).", '".date2sql($date_)."',
		".db_escape($account).", ".db_escape($dimension).", "
		.db_escape($dimension2).", ".db_escape($memo_).", "
		.db_escape($net_total);
	$sql .= ") ";

	if ($err_msg == "")
		$err_msg = "The GL transaction could not be inserted";

	db_query($sql, $err_msg);

	return mysql_insert_id();
}

function approve_stock_withdrawal($adj_id)
{
		ini_set('mssql.connect_timeout',0);
		ini_set('mssql.timeout',0);
		set_time_limit(0);
		
		global $Refs,$db_connections;
		
		ms_db_query("BEGIN TRANSACTION");
		begin_transaction();
	
	
		$t_sql = "SELECT * FROM transfers.0_stocks_withdrawal_details  WHERE transfer_id ='$adj_id'";
		$res_2=db_query($t_sql);

		while($row_2 = db_fetch($res_2))
		{
			$uoms_qty_multiplier=$row_2['qty_multiplier'];
			$stock_id=$row_2['stock_id'];
			$qty_out=$row_2['qty_out'];
			$cost=$row_2['cost'];
		
			$totalqty+=$uoms_qty_multiplier*$qty_out;
			$nettotal+=($uoms_qty_multiplier*$qty_out)*$cost;
		}
		

		$sql = "SELECT * FROM transfers.0_stocks_withdrawal_header WHERE id='$adj_id'";
		$tr_res = db_query($sql);
		//display_error($sql);
		$head_row=db_fetch($tr_res);
		
		$location=$head_row['br_code_out'];
		$location2=$head_row['br_code_in'];
		$nature_of_req=$head_row['nature_of_req'];
		
		$connect_to=get_connection_to_branch($location2);
		$location2_aria_db=$db_connections[$connect_to]['dbname'];
		
		$users_id=$_SESSION["wa_current_user"]->user;

		$date_=Today();
		$status_type=1;
		$memo_=$head_row['memo_'];
		$remarks=$memo_;
	
		$stats=2;
		$movement_status='POSTED';
		$posted_by=$_SESSION['wa_current_user']->user;
		$created_by=$_SESSION['wa_current_user']->user;

		$MovementCode = 'SW';
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
		$prod_history_desc = 'STOCKS WITHDRAWAL';
		
		$movement_no=str_pad(getSWCounter(),10,'0',STR_PAD_LEFT);
	
	//---- MOVEMENTS insertion
		$sql = "INSERT INTO Movements (MovementNo,MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,
		ContactPerson,FromDescription,FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,
		Terms,TransactionDate,FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,
		BranchCode,CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
		WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
		VALUES ('$movement_no','$MovementCode','','','','$ToDescription','$ToAddress','$ContactPerson','$FromDescription ($area)','',
		'".Today()." 00:00:00','".$_SESSION['wa_current_user']->user."','".Today()." 00:00:00','$stats','$posted_by',
		'".Today()." 00:00:00',0,'".Today()." 00:00:00','NULL','".$nettotal."','$movement_status','".$totalqty."',
		'$created_by','".ms_escape_string($remarks)."','NULL','NULL','$location','','','$location2','$location','','0','0','0','0','0','1','PHP','0','')";
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
		
		$ms_user_id=$_SESSION['wa_current_user']->ms_user_id;
			

		$sql_details = "SELECT * FROM transfers.0_stocks_withdrawal_details  WHERE transfer_id ='$adj_id'";
		$res_=db_query($sql_details);

	while($row_ = db_fetch($res_))
	{
		$uoms_qty_multiplier=$row_['qty_multiplier'];
		$stock_id=$row_['stock_id'];
		$qty_out=$row_['qty_out'];
		$description=$row_['description'];
		$uom=$row_['uom'];
		$cost=$row_['cost'];
		$barcode=$row_['barcode'];
		//INSERT TO 0_stock_moves
		
		//---- PRODUCTS selling area update, MOVEMENTLINE insertion, and PRODUCT HISTORY 
		
		//getting SellingArea Beginning before updating
		$sellingareabeg = "SELECT pVatable,ProductCode,SellingArea FROM Products WHERE ProductID = '".$stock_id."'";
		//display_error($sellingareabeg);
		$qsellingareabeg = ms_db_query($sellingareabeg);			  
		$sellingarearow = mssql_fetch_array($qsellingareabeg);
		$sellingareaqty = $sellingarearow['SellingArea'];
		$sellingareabarcode = $sellingarearow['ProductCode'];
		$pVatable = $sellingarearow['pVatable'];
		
		//----UPDATING PRODUCTS [SellingArea]
		 $qty_per_piece=$uoms_qty_multiplier*$qty_out;
	
		$sql_upadate_all_input="UPDATE Products SET [SellingArea]=[SellingArea]-".$qty_per_piece." WHERE ProductID='".$stock_id."'";
		
		//display_error($sql_upadate_all_input);
		ms_db_query($sql_upadate_all_input);

		out_add_ms_movement_line($last_inserted_recID,$stock_id,$sellingareabarcode,$description,$uom,$cost,$qty_out,$uoms_qty_multiplier,$sellingareabarcode);
		$movement_details_row = out_get_movement_line_details($last_inserted_recID,$stock_id);
	
		add_stock_transfer_out_item($adj_id,$stock_id, $location,$date_,$reference,$qty_out, 
		$cost, $uom, $uoms_qty_multiplier, $memo_,$stats,$get_trans_no,$barcode);
		
		$selling_area_in='NULL';
		$selling_area_out=$movement_details_row['qty'] * $movement_details_row['pack'];
		
		$movement_id_2_insert=$last_inserted_recID;

		$producthistory = "INSERT INTO ProductHistory ([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
		,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
		,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
		,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
		,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
		VALUES('".$stock_id."','".$sellingareabarcode."','".$movement_id_2_insert."','".$movement_no."',CURRENT_TIMESTAMP, 
		CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', '".$sellingareaqty."', NULL, 1, 1,$selling_area_in,".$selling_area_out.", NULL, 
		NULL, '".$movement_details_row['unitcost']."', NULL,NULL, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->user."', NULL, NULL, '".$MovementCode."', NULL, 0, NULL, 0, 0, '', NULL, NULL)";
		//display_error($producthistory);
		ms_db_query($producthistory);		
		
		$sql_m = "UPDATE transfers.0_stocks_withdrawal_header SET m_id_out='".$movement_id_2_insert."', m_no_out='".$movement_no."' where id='$adj_id'";
		db_query($sql_m, "could not 0_stocks_withdrawal_header.");
		
		//$pVatable=1;
		$taxrate = 12;
		$net_of_vat_total=($uoms_qty_multiplier*$qty_out)*$cost;

		if ($pVatable==1){
		$net_of_vat_total2+= ($net_of_vat_total / (1+($taxrate/100)));
		}
		else {
		$net_of_vat_total2+=$net_of_vat_total;
		}

		$net_of_vat_total=$net_of_vat_total2;
		$gl_debit_amount=$net_of_vat_total;
		$gl_credit_amount=$net_of_vat_total;
	}
		/*
		if ($net_of_vat_total>0)
		{
			//insert to ARIA GL of transfer out
			
			if ($location==$location2) {
			$debit_account_expense=nature_of_req_gl($nature_of_req);
			$credit_account_from='570002'; //Stock Transfer Out
			
			add_gl_trans_std_cost(ST_STOCKS_WITHDRAWAL, $adj_id, $date_, $debit_account_expense, 0, 0, $memo_, $gl_debit_amount);
			add_gl_trans_std_cost(ST_STOCKS_WITHDRAWAL, $adj_id, $date_, $credit_account_from, 0, 0, $memo_, -$gl_credit_amount);
			}
			
			else{		
			$debit_account_expense=nature_of_req_gl($nature_of_req);
			//FROM BRANCH
			$debit_account_from= get_gl_stock_to($location2);
			$credit_account_from='570002'; //Stock Transfer Out
			
			add_gl_trans_std_cost(ST_STOCKS_WITHDRAWAL, $adj_id, $date_, $debit_account_from, 0, 0, $memo_, $gl_debit_amount);
			add_gl_trans_std_cost(ST_STOCKS_WITHDRAWAL, $adj_id, $date_, $credit_account_from, 0, 0, $memo_, -$gl_credit_amount);
			
			//TO BRANCH
			$credit_account_to= get_gl_stock_from($location);
			add_gl_trans_stock_withdrawal($location2_aria_db,ST_STOCKS_WITHDRAWAL, $adj_id, Today(), $debit_account_expense, 0, 0, '', $gl_debit_amount);
			add_gl_trans_stock_withdrawal($location2_aria_db,ST_STOCKS_WITHDRAWAL, $adj_id, Today(), $credit_account_to, 0, 0, '', -$gl_credit_amount);	
				
			}
			
		}
		*/
		
		$sql_update = "UPDATE transfers.0_stocks_withdrawal_header SET oic_approved='1', witnessed_by=".db_escape($users_id).", date_posted='".date2sql(Today())."' where id='$adj_id'";
		db_query($sql_update, "could not update oic's approval.");
		

	//add_comments(ST_STOCKS_WITHDRAWAL, $adj_id, $date_, $memo_);
	//$Refs->save(ST_STOCKS_WITHDRAWAL, $adj_id, $reference);
	//add_audit_trail(ST_STOCKS_WITHDRAWAL, $adj_id, $date_);
	commit_transaction();
	ms_db_query("COMMIT TRANSACTION");

	return $adj_id;
}

function new_stock_transfer_out($items, $location, $location2, $date_, $status_type,$memo_,$get_trans_no)
{

	global $Refs,$db_connections;
	
	$location=$db_connections[$location]['br_code2'];
	$location2=$db_connections[$location2]['br_code2'];

	$request_date=$_POST['date_requested'];
	//$released_date=$_POST['date_released'];
	// $w_slip_no=$_POST['slip_no'];
	$req_department=$_POST['req_department'];
	$rel_department=$_POST['rel_department'];
	$nature_of_req=$_POST['nature_of_req'];
	$requested_by=$_POST['requested_by'];
	// $noted_by=$_POST['noted_by'];
	$approved_by=$_POST['approved_by'];
	$witnessed_by=$_POST['witnessed_by'];
	
	ms_db_query("BEGIN TRANSACTION");
	begin_transaction();
	
	$x=get_nextmov_no();
	$adj_id=$x;
	$m_type=ST_STOCKS_WITHDRAWAL;
	
	$MovementCode = 'SW';
	$users_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$user_real_name = $u['real_name'];
	$my_header_id=add_transfers_out_head($date_,$request_date,$req_department,$rel_department,$nature_of_req,
	$location,$location2,$m_type,$adj_id,$MovementCode,$users_id,$user_real_name,$requested_by,$approved_by,$witnessed_by,$memo_);
	
	// $db_133 = get_branch_db_133($location);
	// $db_133_2 = get_branch_db_133($location2);
	
	foreach ($items as $line_item)
	{
	$uoms_qty_multiplier=out_get_qty_multiplier($line_item->units);
	
	//getting SellingArea Beginning before updating
	$sqlpvatable= "SELECT pVatable FROM Products WHERE ProductID = '".$line_item->stock_id."'";
	$respvatable = ms_db_query($sqlpvatable);			  
	$pvatable_row = mssql_fetch_array($respvatable);
	$pVatable = $pvatable_row['pVatable'];

	add_transfer_out_det($my_header_id,$line_item->stock_id,$line_item->barcode,$line_item->units,$line_item->standard_cost+0, 
	$line_item->quantity,$line_item->item_description,$uoms_qty_multiplier,$pVatable,$db_133,$db_133_2);

	// $gl_debit_amount+=$line_item->standard_cost * $line_item->quantity;
	// $gl_credit_amount+=$line_item->standard_cost * -$line_item->quantity;
	}

	commit_transaction();
	ms_db_query("COMMIT TRANSACTION");

	return $adj_id;
}

function get_branch_db_133_($loc_code)
{
	$sql = "SELECT db_133 FROM transfers.0_branches
				WHERE `code` = ".db_escape($loc_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
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

	add_stock_move(ST_STOCKS_WITHDRAWAL, $stock_id, $adj_id, $location, $date_, $reference, $quantity, $standard_cost, $_SESSION['wa_current_user']->user, 
	$show_or_hide=1,$standard_cost, $units, $uoms_qty_multiplier, $discount_percent=0, $error_msg="", $discount_percent2=0, 
	$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0,$barcode);
}

function getSWCounter()
{
	$sql_re = "SELECT Counter FROM Counters WHERE TransactionTypeCode = 'SW'";
	$res = ms_db_query($sql_re);
	$trancode =  mssql_fetch_array($res);
	$tran_no = $trancode['Counter'] + 1;
	
	$sql = "UPDATE [Counters] SET 
				Counter = $tran_no
			WHERE TransactionTypeCode = 'SW'";
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

function add_transfers_out_head($date_,$request_date,$req_department,$rel_department,$nature_of_req,$br_location,$br_location2,
$type_out,$trans_no_out,$MovementCode,$name_out_id,$name_out,$requested_by,$approved_by,$witnessed_by,$memo_)
{
	$date_ = date2sql($date_);
	$request_date = date2sql($request_date);
	//$released_date = date2sql($released_date);
	
	$sql = "INSERT INTO transfers.".TB_PREF."stocks_withdrawal_header(date_created,request_date,requesting_dept,releasing_dept,nature_of_req,
	br_code_out,br_code_in,aria_type_out,aria_trans_no_out,m_code_out,name_out_id,name_out,requested_by,approved_by,witnessed_by,memo_) 
	VALUES ('$date_','$request_date',".db_escape($req_department).",".db_escape($rel_department).",".db_escape($nature_of_req).",
	".db_escape($br_location).",".db_escape($br_location2).",".db_escape($type_out).",".db_escape($trans_no_out).",".db_escape($MovementCode).",
	".db_escape($name_out_id).",".db_escape($name_out).",".db_escape($requested_by).",".db_escape($approved_by).",
	".db_escape($witnessed_by).",".db_escape($memo_).")";
	
	db_query($sql,"Failed to Insert.");
//	display_error($sql);
	$id = db_insert_id();
	return $id;
}

function add_transfer_out_det($movement_id,$stock_id,$barcode,$units,$standard_cost, $quantity_out,$description,$qty_multiplier,$pVatable)
{
	$date_created = date2sql($date_);
	//$prod_id2 = get_branch_stock_id($db_133,$db_133_2, $stock_id,$description);
	
	$sql = "INSERT INTO transfers.".TB_PREF."stocks_withdrawal_details (transfer_id,stock_id,description,barcode,uom,qty_multiplier,cost,pvatable,qty_out,qty_in) 
	VALUES (".db_escape($movement_id).",".db_escape($stock_id).","
		.db_escape($description).",".db_escape($barcode).",".db_escape($units).",'$qty_multiplier',
	'$standard_cost','$pVatable','$quantity_out', '0')";
	
	db_query($sql);
	//display_error($sql);
}

function get_branch_stock_id_($db_133, $db_133_2, $stock_id,$description)
{
	//connect to db 133
	$ms_db_133 = mssql_connect('192.168.0.133' , 'markuser', 'tseug');
    mssql_select_db($db_133,$ms_db_133);
					
	//get global id
	$sql = "SELECT GlobalID FROM Products WHERE ProductID =  $stock_id";
	$res = mssql_query($sql, $ms_db_133);
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
	$ms_db_133 = mssql_connect('192.168.0.133' , 'markuser', 'tseug');
    mssql_select_db($db_133_2,$ms_db_133);
					
	//get product id
	$sql = "SELECT ProductID FROM Products WHERE GlobalID =  $global_id";
	$res = mssql_query($sql, $ms_db_133);
	$row = mssql_fetch_array($res);
	$global_id = $row[0];
	
	mssql_close($ms_db_133);
	
	if ($global_id == '')
	{
		display_error('(To Location) No Global ID found for :  '.$description);
		// display_footer_exit();
		return false;
	}
	return $global_id;
}
?>