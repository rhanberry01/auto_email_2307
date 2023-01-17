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
	
page(_($help_context = "Approve Disposal"), false, false, "", $js);

$approve_add = find_submit('selected_id',false);
$posted_add = find_submit('posted_id',false);

function update_posted_rms_header($rs_id, $branch_id)
{
	$b = get_branch_by_id($branch_id);
	if (!empty($b)) {
		$conn = mysql_connect($b['rs_host'], $b['rs_user'], $b['rs_pass']);
		mysql_select_db($b['rs_db'], $conn);
	}
	else {
		display_error("Unable to get branch connection information. Please try again.");
		exit;
	}
	$sql = "UPDATE ".$b['rs_db'].".".TB_PREF."rms_header SET 
			date_temp_posted = '".date2sql(Today())."',
			temp_posted = '1',
			temp_posted_by_aria_user = ".$_SESSION['wa_current_user']->user.",
			temp_post_comment = ''
			WHERE rs_id = '$rs_id' ";
	$query = mysql_query($sql, $conn);
	if (mysql_affected_rows() > 0)
		return true;
}

function update_purch_approve_status($rs_id, $branch_id)
{
	$b = get_branch_by_id($branch_id);
	if (!empty($b)) {
		$conn = mysql_connect($b['rs_host'], $b['rs_user'], $b['rs_pass']);
		mysql_select_db($b['rs_db'], $conn);
	}
	else {
		display_error("Unable to get branch connection information. Please try again.");
		exit;
	}

	$sql = "UPDATE ".$b['rs_db'].".".TB_PREF."rms_header SET 
			purch_approved_date = '".date2sql(Today())."',
			purch_approved = '1',
			purch_approved_by = ".$_SESSION['wa_current_user']->user.",
			purch_approved_comment = ''
			WHERE rs_id = '$rs_id'";
	$query = mysql_query($sql, $conn);
	if (mysql_affected_rows() > 0)
		return true;
}

function check_damaged($rs_id, $branch){
	$b = $branch;
	$conn = mysql_connect($b['rs_host'], $b['rs_user'], $b['rs_pass']);
	if (!$conn) {
		display_error("Could not connect: " . mysql_error());
		exit;
	}
	mysql_select_db($b['rs_db'], $conn);

	$count = 0 ;
	$count_ = 0 ;
	$barcode = array();
	
	$sql = "SELECT prod_id,barcode,item_name,uom,SUM(qty) as qty,orig_uom,orig_multiplier,
					custom_multiplier,price,supplier_code
			FROM ".$b['rs_db'].".".TB_PREF."rms_items WHERE rs_id IN ($rs_id)
			GROUP BY prod_id,barcode,item_name,uom,orig_uom,orig_multiplier,
					custom_multiplier,price,supplier_code";
	$res = mysql_query($sql, $conn);
	while($row = mysql_fetch_array($res))
	{
		$msconn = mssql_connect($b['ms_mov_host'], $b['ms_mov_user'], $b['ms_mov_pass']);
		if (!$msconn) {
			display_error('Could not connect to '. $b['ms_mov_host']);
			exit;
		}
		mssql_select_db($b['ms_mov_db'], $msconn);

		$count++;
		
		$pack = $row['custom_multiplier'] == 0 ? $row['orig_multiplier'] : $row['custom_multiplier'];
		$pcs_qty = ($pack * $row['qty']);
		
		$damaged_check = "SELECT Damaged FROM Products WHERE ProductID = ".$row['prod_id'];
		$damaged_ = mssql_query($damaged_check);
		$damaged_row = mssql_fetch_array($damaged_);
		
		$qty_left = $damaged_row['Damaged'] - $pcs_qty;
		
		if($qty_left >= 0)
			$count_++;
		else
			$barcode[] = $row['barcode'];
		
	}
	if($count_ != $count)
		return $barcode;
	else
		return 0;
	
}

function getFDFBCounter($branch, $myconn, $msconn)
{
	return getCounter_by_branch('FDFB', $branch, $myconn, $msconn);
}

function getCounter_by_branch($type, $branch, $myconn, $msconn)
{
	mssql_select_db($branch['ms_mov_db'], $msconn);
	$sql_re = "SELECT Counter FROM Counters WHERE TransactionTypeCode = '$type'";

	$res = mssql_query($sql_re, $msconn);
	$trancode =  mssql_fetch_array($res);
	
	if ($trancode['Counter']!='' or $trancode['Counter']!=null) {
		$tran_no = $trancode['Counter'] + 1;
		$sql = "UPDATE [Counters] SET 
		Counter = $tran_no
		WHERE TransactionTypeCode = '$type'";

		$objquery = mssql_query($sql, $msconn);
		$cancel = rollback_mssql_trans($sql, $objquery, $myconn, $msconn);
		if($cancel == 1){
		return false;
		}
	}
	
	else {
		return false;
	}
	
	return $tran_no;
}

function rollback_mssql_trans($sql_query, $objquery, $myconn, $msconn){
	if(!$objquery)
	{
		mssql_query("ROLLBACK TRANSACTION", $msconn);
		mysql_query("ROLLBACK", $myconn);
		display_error("An Error occured while processing this query on database : ".$sql_query);
		display_error("Try to logout and login your account then process the transaction again. If this attempt doesn't work, Contact IS-Department for further instructions.");
		return 1;
	}
}

function get_rs_items_total($rs_id, $branch_id)
{	
	$b = get_branch_by_id($branch_id);
	$rsConn = mysql_connect($b['rs_host'], $b['rs_user'], $b['rs_pass']);
	mysql_select_db($b['rs_db'], $rsConn);
	$sql = "SELECT * FROM ".TB_PREF."rms_items
			WHERE rs_id=$rs_id";
	$res = mysql_query($sql, $rsConn);

	while($row = mysql_fetch_array($res)){
		$total += round2($row['qty']*$row['price'],3);
	}
	
	return $total;
}

function get_username_by_id_($id, $branch_id)
{	
	$b = get_branch_by_id($branch_id);
	$rsConn = mysql_connect($b['rs_host'], $b['rs_user'], $b['rs_pass']);
	mysql_select_db($b['rs_db'], $rsConn);
	$sql = "SELECT real_name FROM ".TB_PREF."users WHERE id = $id";
	$query = mysql_query($sql, $rsConn);
	$row = mysql_fetch_array($query);
	return $row[0];
}

function add_ms_movement_line_disposal($MovementID, $ProductID, $ProductCode, $Description, $UOM, $unitcost, $qty, $pack, $barcode, $branch, $myconn, $msconn)
{
	mssql_select_db($branch['ms_mov_db'], $msconn);
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
				UOM,unitcost,qty,extended,pack,barcode)
			VALUES ($MovementID,$ProductID,'$ProductCode','$Description',
				'$UOM',$unitcost,$qty,".round($unitcost*$qty,4).",$pack,'$barcode')";
	$objquery = mssql_query($sql, $msconn);
	if (!$objquery) {
		display_error("error inserting movement line");		
	}
	$cancel = rollback_mssql_trans($sql, $objquery, $myconn, $msconn);
	if($cancel == 1){
		return 1;
	} 
}

function get_pos_product_row($barcode, $branch, $msconn)
{
	mssql_select_db($branch['ms_mov_db'], $msconn);
	$sql = "SELECT * FROM POS_Products WHERE Barcode = '".$barcode."'";
	$res = mssql_query($sql, $msconn);
	$prod =  mssql_fetch_array($res);
	
	return $prod;
}

function get_product_row($prod_id, $branch, $msconn, $column='')
{
	mssql_select_db($branch['ms_mov_db'], $msconn);
	$sql = "SELECT ".($column == '' ? '*' : $column)." FROM Products WHERE ProductID = $prod_id";
	$res = mssql_query($sql, $msconn);
	$prod =  mssql_fetch_array($res);
	return $prod;
}

function update_approved_rms_header($rs_id, $movement_no, $approver_comments, $branch, $myconn, $msconn)
{
	// global $db_connections;
	mysql_select_db($branch['rs_db'], $myconn);
	$sql = "UPDATE ".$branch['rs_db'].".".TB_PREF."rms_header SET 
			date_approved = '".date2sql(Today())."',
			approved = '1',
			movement_no = $movement_no,
			approved_by_aria_user = ".$_SESSION['wa_current_user']->user.",
			approver_comment =".db_escape($approver_comments)."
			WHERE rs_id ='$rs_id'";
	$objquery = mysql_query($sql, $myconn);
	if (!$objquery)
		display_error('failed to update movement (FDFB)');
	$cancel = rollback_mssql_trans($sql, $objquery, $myconn, $msconn);
	if($cancel == 1){
		return false;
	}
	return 1;
}

function process_ms_movement($rs_id, $MovementCode, $branch, $myconn, $msconn, $remarks='')
{
	//display_error('ok');
	global $db_connections;
	mysql_select_db($branch['rs_db'], $myconn);
	$sql = "SELECT SUM(qty * (IF(custom_multiplier=0,orig_multiplier,custom_multiplier)))
			FROM ".$branch['rs_db'].".".TB_PREF."rms_items 
			WHERE rs_id IN ($rs_id)";
	$res = mysql_query($sql, $myconn);
	$row = mysql_fetch_array($res);
	$total_qty = $row[0];
	//display_error($sql);
	
	$sql = "SELECT SUM(ROUND(qty*price,4)) FROM ".$branch['rs_db'].".".TB_PREF."rms_items 
			WHERE rs_id IN ($rs_id)";
	$res = mysql_query($sql, $myconn);
	$row = mysql_fetch_array($res);
	$net_total = $row[0];
	
	$vendor_code = $ToDescription = $ToAddress = $ContactPerson = '';

	$ms_user_id = 0;
	if (!empty($_SESSION['wa_current_user']->ms_user_id)) {
		$ms_user_id = $_SESSION['wa_current_user']->ms_user_id;
	}

	if($MovementCode == 'FDFB')
	{	
		$movement_no = str_pad(getFDFBCounter($branch, $myconn, $msconn), 10, "0", STR_PAD_LEFT);
		$area = 'BO ROOM';
		
		$prod_history_desc = 'For Disposal From BO';
		$flow_stockroom = 0;
		$flow_sa = 0;
		$flow_dmg = 1;
		$beg_sa = $dmg_in = $sa_out = 'NULL';
	}
	
	$from_description = "SAN ROQUE SUPERMARKET   ".strtoupper($db_connections[$_SESSION["wa_current_user"]->company]
				["srs_branch"])." ($area)";
				
	$ToDescription = db_escape($ToDescription);
	$ToAddress = db_escape($ToAddress);
	$ContactPerson = db_escape($ContactPerson);

	mssql_select_db($branch['ms_mov_db'], $msconn);
	$sql = "INSERT INTO Movements (MovementNo,
				MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,ContactPerson,FromDescription,
				FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,Terms,TransactionDate,
				FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,BranchCode,
				CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
				WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
			VALUES ('$movement_no','$MovementCode','','','',$ToDescription,$ToAddress,$ContactPerson,
				'$from_description','','".Today()." 00:00:00',".
				$ms_user_id.",'".Today()." 00:00:00',2,".
				$ms_user_id.",'".Today()." 00:00:00',0,'".Today()." 00:00:00','',"
				. ($net_total+0) .",'POSTED',".($total_qty+0).",".$ms_user_id.",'$remarks',
				'','$vendor_code','','','','','','',0,0,0,0,0,1,'PHP',0,'')";
	$objquery = mssql_query($sql, $msconn);
	$cancel = rollback_mssql_trans($sql, $objquery, $myconn, $msconn);
	if($cancel == 1){
		return false;
	}
	
	// $last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
	// $last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	// $movement_id = $last_inserted_line_row['LAST'];
	
	$last_inserted_line_res = mssql_query("SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY]", $msconn);
	$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	$movement_id = $last_inserted_line_row['SCOPE_IDENTITY'];
	
	if ($movement_id == 0)
	{
		display_error($sql);
		return false;
	}

	$sql = "SELECT prod_id,barcode,item_name,uom,SUM(qty) as qty,orig_uom,orig_multiplier,
			custom_multiplier,price,supplier_code
			FROM ".$branch['rs_db'].".".TB_PREF."rms_items WHERE rs_id IN ($rs_id)
			GROUP BY prod_id,barcode,item_name,uom,orig_uom,orig_multiplier,
			custom_multiplier,price,supplier_code;";

	$res = mysql_query($sql, $myconn);
		
	$cos_total = 0;
	while($row = mysql_fetch_array($res)) //Products and Product History
	{
		// $pos_row = get_pos_product
		$prod_row = get_product_row($row['prod_id'], $branch, $msconn);
		$pos_prod_row = get_pos_product_row($row['barcode'], $branch, $msconn);
		
		$pack = ($row['custom_multiplier'] == 0 ? $row['orig_multiplier'] : $row['custom_multiplier']);
		$pcs_qty = ($pack * $row['qty'])+0;
		
		
		if ($MovementCode == 'SA2BO')
		{
			$beg_sa = $prod_row['SellingArea']+0;
			$beg_damaged = $prod_row['Damaged']+0;
			$dmg_in = $sa_out = $pcs_qty;
			$dmg_out = 'NULL';
			
			$row['price'] = $prod_row['CostOfSales'];
			
			$cos_total +=  $row['price']*$pcs_qty;
		}
		
		else if ($MovementCode == 'FDFB')
		{
			$dmg_out = $pcs_qty+0;
			$beg_damaged = $prod_row['Damaged']+0;
			$row['price'] = $prod_row['CostOfSales'];
			
			$cos_total +=  $row['price']*$pcs_qty;
		}
		else // return and disposal
		{
			$dmg_out = $pcs_qty+0;
			$beg_damaged = $prod_row['Damaged']+0;
		}
		
		$row['price'] += 0;
		
		$producthistory = "INSERT INTO [ProductHistory]([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
				  ,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
				  ,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
				  ,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
				  ,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
			  VALUES(".$row['prod_id'].",'".$row['barcode']."',$movement_id,'$movement_no',CURRENT_TIMESTAMP, 
				  CONVERT (date, CURRENT_TIMESTAMP), '$prod_history_desc', $beg_sa, 0, $flow_stockroom, 
				  $flow_sa, NULL,".
				  $sa_out.", NULL, 
				  NULL, ".$row['price'].",$dmg_in,
				  $dmg_out, NULL, NULL, NULL, NULL, '".$ms_user_id."', NULL, NULL, '$MovementCode', '',
				  0, NULL, 0, 0, '', $beg_damaged, $flow_dmg)";
		$objquery = mssql_query($producthistory, $msconn);
		$cancel = rollback_mssql_trans($sql, $objquery, $myconn, $msconn);
		if($cancel == 1){
			return false;
		}
		$line = add_ms_movement_line_disposal($movement_id, $row['prod_id'], $pos_prod_row['ProductCode'], $row['item_name'],
				$row['uom'], $row['price'], $row['qty'], $pack, $row['barcode'], $branch, $myconn, $msconn);
		if($line == 1){
			return false;
		}
		if ($MovementCode == 'SA2BO')
		{
			$prod_sql = "UPDATE Products SET 
						SellingArea = SellingArea - ".$pcs_qty.",
						Damaged = Damaged + ". $pcs_qty ."
					WHERE ProductID = ". $row['prod_id'];
		}
		else
		{
			$prod_sql = "UPDATE Products SET 
					Damaged = Damaged - ". $pcs_qty ."
					WHERE ProductID = ". $row['prod_id'];
		}
		
		$objquery = mssql_query($prod_sql, $msconn);
		$cancel = rollback_mssql_trans($prod_sql, $objquery, $myconn, $msconn);
		if($cancel == 1){
			return false;
		}
	}
	
	if ($MovementCode == 'SA2BO' OR $MovementCode == 'FDFB')
	{
		//update net total of movements
		$sql = "UPDATE Movements SET NetTotal = $cos_total
						WHERE MovementID = $movement_id
						AND MovementCode = '$MovementCode'";
		$objquery = mssql_query($sql, $msconn);
		$cancel = rollback_mssql_trans($sql, $objquery, $myconn, $msconn);
		if($cancel == 1){
			return false;
		}
	}
	$a = array(1,$movement_no);
	return $a;
}

function get_ms_supp_name($supp_code)
{
	$sql = "SELECT description FROM vendor
			WHERE vendorcode = '$supp_code'";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
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

function get_purchaser_vendors($user_id) {
	$po_host = '192.168.0.56'; 
	$po_user = 'root';
	$po_pass = '';
	$po_db = 'srs';

	$vArr = array();

	$conn = mysql_connect($po_host, $po_user, $po_pass);
	mysql_select_db($po_db, $conn);

	$sql = "SELECT id FROM users WHERE aria_user_id = '".$user_id."'";
	$query = mysql_query($sql, $conn);
	if (mysql_num_rows($query) == 1) {
		$res = mysql_fetch_assoc($query);
		$id = $res['id'];

		$vSql = "SELECT vendor FROM user_vendor WHERE user_id = '".$id."'";
		$vQuery = mysql_query($vSql, $conn);
		while ($vRow = mysql_fetch_assoc($vQuery)) {
			array_push($vArr, $vRow['vendor']);
		}
	}
	return $vArr;
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

function get_rs_view_str_per_branch($trans_no, $label, $branch_id) {
	global $path_to_root;

	$viewer = "purchasing/view/view_rs_live.php?rs_id=$trans_no&bid=$branch_id";

	return "<a target='_blank' href='$path_to_root/$viewer' onclick=\"javascript:openWindow(this.href,this.target); return false;\">$label</a>";
}

if ($db_connections[$_SESSION["wa_current_user"]->company]["name"] != 'San Roque Supermarket - NOVA') {
	display_error('<h2>'."YOU CAN ONLY ACCESS THIS PAGE WHEN YOU'RE LOGGED IN FROM NOVALICHES BRANCH. ".'</br>'."Please logout and login to novaliches branch in order to proceed. Thank you!".'</h2>');
	exit;
}


if (isset($_POST['purch_id']) && !empty($_POST['purch_id'])) {
	foreach ($_POST['purch_id'] as $key => $purch_approve_id) {
		$branch_id = $_POST['bid_'.$purch_approve_id];
		$update_status = update_purch_approve_status($purch_approve_id, $branch_id);
		if ($update_status) 
			display_notification("Disposal #".$purch_approve_id." is successfully approved but it is still pending for approval.");
		else 
			display_notification("Disposal #".$purch_approve_id." is not approved! Please try again.");
	}
} 

if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
	foreach ($_POST['post_id'] as $key => $posted_approve_id) {
		$branch_id = $_POST['bid_'.$posted_approve_id];
		$update_status = update_posted_rms_header($posted_approve_id, $branch_id);
		if ($update_status) 
			display_notification("Disposal #".$posted_approve_id." is successfully posted but it is still pending for approval.");
		else 
			display_notification("Disposal #".$posted_approve_id." is not posted! Please try again.");
	}
}



if (isset($_POST['approve_id']) && !empty($_POST['approve_id'])) {

	foreach ($_POST['approve_id'] as $key => $approve_id) {
		$b = get_branch_by_id($_POST['bid_'.$approve_id]);
		$myserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["host"], 2);
		$msserver = ping($b['ms_mov_host'], 2);
		if(!$myserver){
			display_error($db_connections[$_SESSION["wa_current_user"]->company]["host"].' FAILED TO CONNECT.');
			return false;
		}elseif(!$msserver){
			display_error($b['ms_mov_host'].' FAILED TO CONNECT.');
			return false;
		}

		$check = check_damaged($approve_id, $b);

		$msconn = mssql_connect($b['ms_mov_host'], $b['ms_mov_user'], $b['ms_mov_pass']);
		mssql_select_db($b['ms_mov_db'], $msconn);
		mssql_query("BEGIN TRANSACTION", $msconn);

		$myconn = mysql_connect($b['rs_host'], $b['rs_user'], $b['rs_pass']);
		mysql_select_db($b['rs_db'], $myconn);
		mysql_query("BEGIN", $myconn);
		if($check == 0){
			$approver_comments = $_POST['approver_comments'.$approve_id];
			$movement_no = process_ms_movement($approve_id, 'FDFB', $b, $myconn, $msconn);
			
			if($movement_no[0] == 1)
				$a = update_approved_rms_header($approve_id, $movement_no[1], $approver_comments, $b, $myconn, $msconn);
			if($movement_no[0] == 1 and $a == 1){
				mysql_query("COMMIT", $myconn);
				mssql_query("COMMIT TRANSACTION", $msconn);
				display_notification("Disposal #".$approve_id." is successfully approved.");
			}
		}else{
				display_error("Cannot processed barcode(s) ".implode(',',$check)." in Trans. # ".$approve_id.".");		
		}

	}
}

$purchaser_vendors = '';
if (is_purchaser($_SESSION['wa_current_user']->user)) {
	$purchaser_vendors = get_purchaser_vendors($_SESSION['wa_current_user']->user);
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

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
ref_cells('#: ','rs_id', null, null, null, true);
supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
date_cells('Date From: ', 'rs_date_from');
date_cells('Date To: ', 'rs_date_to');
yesno_list_cells(_("Status Type:"), 'status_type', '',_("For Approval"), _("Approved"));
submit_cells('search', 'Search', "", false, false);
end_table(2);
div_end();
div_end();

div_start('dm_list');

$branches_sql = "SELECT * FROM transfers.0_branches";
$branches_query = db_query($branches_sql);

$all_branch = array();
$running_total = array();
while ($branches_res = db_fetch($branches_query)) {
	$temp_total = 0;
	$conn = mysql_connect($branches_res['rs_host'], $branches_res['rs_user'], $branches_res['rs_pass']);
	mysql_select_db($branches_res['rs_db'], $conn);

	$sql = "SELECT * FROM ".TB_PREF."rms_header";

	if ($_POST['rs_id'] == '')
	{
		$sql .= " WHERE rs_date >= '".date2sql($_POST['rs_date_from'])."'
				  AND rs_date <= '".date2sql($_POST['rs_date_to'])."'";
				  
		if ($_POST['supplier_code'] != '')
			$sql .= " AND supplier_code = ".db_escape($_POST['supplier_code']);
			
		if ($_POST['status_type']==1)
		{
		//Open
		$stats='0' ;
		$sql .= "  AND movement_no = 0";	
		}
		else {
		//Posted
		$stats='1';
		$sql .= "  AND movement_no!= ''";	
		}

		if ($_POST['status_type']!='')
		{
		$sql .= "  AND approved = '$stats'";	
		}
					
		$sql .= " AND movement_type='FDFB' AND processed = '1'";		
			
	}
	else		  
	{
		$sql .= " WHERE (rs_id = ".$_POST['rs_id']." 
				  OR movement_no = ".$_POST['rs_id'].")";
		
		if($prevent_duplicate) // pending only
		$sql .= " AND processed = 0";		
		$_POST['status'] == 2;
	}
	$sql .= " ORDER BY rs_id";

	$res = mysql_query($sql, $conn);
	if (mysql_num_rows($res) > 0) {
		while ($row = mysql_fetch_assoc($res)) {
			if ($_POST['status_type'] != 0) {
				$temp_total = get_rs_items_total($row['rs_id'], $branches_res['id']);
				$running_total[$branches_res['name']] += $temp_total;
			}
			$_row = $row;
			$_row['branch_name'] = $branches_res['name'];
			$_row['branch_id'] = $branches_res['id'];
			array_push($all_branch, $_row);
		}
	}
	else {
		$running_total[$branches_res['name']] = 0; 
	}
}

if (!empty($running_total) && $_POST['status_type'] != 0) {
	start_table($table_style2.' width=90%');
	$th_total = array('BRANCH', 'RUNNING TOTAL');
	table_header($th_total);
	$j = 0;
	$grand_total = 0;
	foreach ($running_total as $branch_name => $branch_total) {
		$grand_total += $branch_total;
		alt_table_row_color($j);
		label_cell($branch_name.':', 'align=right');
		label_cell(number_format2($branch_total, 3), 'align=right');
		end_row();
	}
	start_row();
	label_cell('<font color=#880000><b>'.'OVERALL TOTAL:'.'</b></font>', 'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($grand_total), 2)."<b></font>",'align=right');
	end_row();
	end_table();
	br();
	br();
}


start_table($table_style2.' width=90%');
$th =array('#', 'Branch', 'SA to BO Date', 'Supplier', 'Extended', 'Created by', 'Processed by','Remarks', 'Status', 'Comment', 'Purchasing '.'<input type="checkbox" id="purch_all">', 'Operations '.'<input type="checkbox" id="post_all">', 'GM '.'<input type="checkbox" id="approve_all">');

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

$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];

foreach ($all_branch as $key => $row) 
{
	alt_table_row_color($k);
	label_cell(get_rs_view_str_per_branch($row['rs_id'],'# '.$row['rs_id'], $row['branch_id']));
	label_cell($row['branch_name']);
	label_cell(sql2date($row['rs_date']));
	label_cell(get_ms_supp_name($row['supplier_code']));
	$total=get_rs_items_total($row['rs_id'], $row['branch_id']);
	label_cell(number_format2($total,3),'align=right');
	label_cell(get_username_by_id_($row['created_by'], $row['branch_id']));
	label_cell(get_username_by_id_($row['processed_by'], $row['branch_id']));
	label_cell($row['comment']);
	
	if ($row['approved'] == 0) {
		label_cell('For Approval');
	}
	else {
		label_cell("Approved by ".get_username_by_id($row['approved_by_aria_user']));
	}
		
	if ($row['approved']==0 and ($approver=='juliet' or $approver=='admin' or $approver=='6666')) {
		text_cells(_(""), 'approver_comments'.$row['rs_id']);
	}
	else {
		label_cell($row['approver_comment']);
	}

	if ($row['purch_approved'] == 0 && is_purchaser($_SESSION['wa_current_user']->user)) {
		if (!empty($purchaser_vendors) && in_array($row['supplier_code'], $purchaser_vendors)) {
			echo '<td align="center">
				<input type="hidden" name="bid_'.$row['rs_id'].'" value="'.$row['branch_id'].'" />
				<input type="checkbox" class="purch_one" name="purch_id[]" value="'.$row['rs_id'].'" />
			</td>';
		}
		else {
			label_cell('<i>Not Your Supplier</i>','align=center');
		}
	}
	else if ($row['purch_approved'] == 0) {
		label_cell('<i>Pending</i>','align=center');
	}
	else {
		label_cell('<i>Approved</i>','align=center');
	}


	if ($row['purch_approved'] == 0) {
		label_cell('<i>For Approval of Purchaser</i>','align=center');
	}
	else if ($row['purch_approved'] == 1 && $row['temp_posted'] == 0) {
		if (is_operations($_SESSION['wa_current_user']->access)) {
			echo '<td align="center">
				<input type="hidden" name="bid_'.$row['rs_id'].'" value="'.$row['branch_id'].'" />
				<input type="checkbox" class="post_one" name="post_id[]" value="'.$row['rs_id'].'" />
			</td>';
		}
		else {
			label_cell('<i>Pending</i>','align=center');
		}
	}
	else {
		label_cell('<i>Approved</i>','align=center');
	}


	if ($row['temp_posted'] == 0) {
		label_cell('<i>For Approval of Operations</i>','align=center');
	}
	else if ($row['temp_posted'] == 1 && $row['approved'] == 0) {
		if ($approver == 'admin' || $approver == '6666') {
			echo '<td align="center">
				<input type="hidden" name="bid_'.$row['rs_id'].'" value="'.$row['branch_id'].'" />
				<input type="checkbox" class="approve_one" name="approve_id[]" value="'.$row['rs_id'].'" />
			</td>';	
		}
		else {
			label_cell('<i>Pending</i>','align=center');
		}
	}
	else {
		label_cell('<i>Approved</i>','align=center');
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