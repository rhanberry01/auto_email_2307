<?php
/**
 * Title: Import Per Branch RS to Centralized RS
 * Description: Auto Import [RS] Returned Merchandise (RS Header, Items)
 * 				per branch to Centralized RS Database.
 * Author: AJSP 
 * Date: 08/29/2017 
 */
 
ini_set('memory_limit', '-1');

// MySQL Custom Settings
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 

// MSSQL Custom Settings
ini_set('mssql.connect_timeout', 0);
ini_set('mssql.timeout', 0);
ini_set('mssql.textlimit', 2147483647);
ini_set('mssql.textsize', 2147483647);

set_time_limit(0);   
  

$seconds = 15;
if (isset($_GET['sitrololo'])) 
	header('Refresh: '.$seconds);

$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/db/all_item_adjustments_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Fetch and Throw Updates Centralized RS to Branch RS"), false, false, "", $js);

function ping_conn($host, $port=1433, $timeout=2) {
	$fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
	if (!$fsock) 
		return false;
	else
		return true;
}

function notify($msg, $type=1) {
	if ($type == 2) {
		echo '<div class="msgbox">
				<div class="err_msg">
					' . $msg . '
				</div>
			</div>';
	}
	elseif ($type == 1) {
	 	echo '<div class="msgbox">
				<div class="note_msg">
					' . $msg . '
				</div>
			</div>';
	 }
}

function get_rms_ids($conn, $branch) {
	$sql = "SELECT rs_id FROM 0_rms_header WHERE branch_id = " . $branch['id'] ."";
	$query = mysql_query($sql, $conn);
	$rms_id = array();
	while ($rms_row = mysql_fetch_assoc($query)) {
		$rms_id[] = $rms_row['rs_id'];
	}
	return $rms_id;
}

function get_branch_by_id($branch_id) {
	$sql = "SELECT * 
			FROM centralized_returned_merchandise.0_branches 
			WHERE id = '".$branch_id."'";
	$query = db_query($sql);
	return db_fetch($query);
}

function update_posted_rms_header($rs, $conn) {
	$sql = "UPDATE ".TB_PREF."rms_header SET 
			date_temp_posted = '".$rs['date_temp_posted']."',
			temp_posted = '".$rs['temp_posted']."',
			temp_posted_by_aria_user = '".$rs['temp_posted_by_aria_user']."',
			temp_post_comment = '".$rs['temp_post_comment']."'
			WHERE rs_id = '".$rs['rs_id']."'";
	$query = mysql_query($sql, $conn);
	if (mysql_affected_rows() > 0)
		return true;
}

function update_central_posted_rms_header($rs_id, $branch_id) {
	$sql = "UPDATE centralized_returned_merchandise.".TB_PREF."rms_header SET 
			temp_posted = '2'
			WHERE rs_id = '$rs_id'
			AND branch_id = '$branch_id' ";
	$query = db_query($sql);
	if (mysql_affected_rows() > 0)
		return true;
}

function update_central_approved_rms_header($rs_id, $branch_id) {
	$sql = "UPDATE centralized_returned_merchandise.".TB_PREF."rms_header SET 
			approved = '2'
			WHERE rs_id = '$rs_id'
			AND branch_id = '$branch_id' ";
	$query = db_query($sql);
	if (mysql_affected_rows() > 0)
		return true;
}

function insert_bo_aging($rs_id, $branch_id, $branch_name) {
	$sql = "INSERT INTO centralized_returned_merchandise.".TB_PREF."bo_aging
			(rs_id, branch_id, branch_name, date_created, status, disposed_by) VALUES 
			($rs_id, $branch_id, '".$branch_name."', '".date2sql(Today())."', '0', NULL)";
	$query = db_query($sql);
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
		notify("An Error occured while processing this query on database : ".$sql_query, 2);
		notify("Try to logout and login your account then process the transaction again. If this attempt doesn't work, Contact IS-Department for further instructions.", 2);
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
	
	$total = 0;
	
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

function update_approved_rms_header($rs_id, $rs, $movement_no, $approver_comments, $branch, $myconn, $msconn)
{
	mysql_select_db($branch['rs_db'], $myconn);
	$sql = "UPDATE ".$branch['rs_db'].".".TB_PREF."rms_header SET 
			date_approved = '".$rs['date_approved']."',
			approved = '1',
			movement_no = $movement_no,
			approved_by_aria_user = '".$rs['approved_by_aria_user']."',
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
	global $db_connections;
	mysql_select_db($branch['rs_db'], $myconn);
	$sql = "SELECT SUM(qty * (IF(custom_multiplier=0,orig_multiplier,custom_multiplier)))
			FROM ".$branch['rs_db'].".".TB_PREF."rms_items 
			WHERE rs_id IN ($rs_id)";
	$res = mysql_query($sql, $myconn);
	$row = mysql_fetch_array($res);
	$total_qty = $row[0];
	
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


if (isset($_POST['UpdateRS']) OR isset($_GET['sitrololo'])) {
	
	$preloader_gif = $path_to_root.'/themes/modern/images/ajax-loader.gif';

	echo "<div id='ploader' style='display:none'>
			<img src='$preloader_gif'>
		</div>";

	display_notification("Fetching and throwing updates now started and running...");

	if (isset($_POST['UpdateRS'])) {
		meta_forward($_SERVER['PHP_SELF'], "sitrololo=ayasawanitralala");
	}

	$branch_updates = array();

	$b_sql = "SELECT * FROM centralized_returned_merchandise.0_branches";
	$b_query = db_query($b_sql);
	while ($b_row = mysql_fetch_assoc($b_query)) {

		$sql = "SELECT * 
				FROM centralized_returned_merchandise.0_rms_header
				WHERE (temp_posted = 1
				OR approved = 1)
				-- AND (date_temp_posted > '2017-01-01'
				-- OR date_approved > '2017-01-01')
				AND branch_id = '". $b_row['id'] ."'
				LIMIT 1000";
		$query = db_query($sql);
		$updates = array();
		while ($row = mysql_fetch_assoc($query)) {
			$updates[] = $row;
		}

		$branch_updates[] = array(
			'branch_id' => $b_row['id'],
			'branch_name' => $b_row['name'],
			'updates' => $updates
		);

	}

	/*echo "<pre>";
	print_r($branch_updates);
	echo "</pre>";
	exit;*/

	if (!empty($branch_updates)) {
		foreach ($branch_updates as $k => $u) {
			$b = get_branch_by_id($u['branch_id']);
			if (!ping_conn($b['rs_host'], 80)) {
				notify($b['name'] . " is not reachable. Skipping ...", 2);
				continue;
			}
			else {
				notify($b['name'] . " is reacheable. Trying to connect ...");
				$conn = mysql_connect($b['rs_host'], $b['rs_user'], $b['rs_pass']);
				$ms_conn = mssql_connect($b['ms_mov_host'], $b['ms_mov_user'], $b['ms_mov_pass']);
				if (!$ms_conn) {
					notify("Cannot connect to MSSQL database " . $b['name'] . " - " . $b['ms_mov_host'] . ".", 2);
				}

				if (!$conn) {
					notify("Cannot connect to " . $b['name'] . " .Skipping ...", 2);
					continue;
				}
				else {
					notify("Connected to " . $b['name']);
					mssql_select_db($b['ms_mov_db'], $ms_conn);
					mysql_select_db($b['rs_db'], $conn);
					
					foreach ($u['updates'] as $key => $value) {
						if ($value['temp_posted'] == 2 && $value['approved'] == 1) {
							// notify($value['rs_id'], 2);
							mssql_query("BEGIN TRANSACTION", $ms_conn);
							mysql_query("BEGIN", $conn);
							$rs_id = $value['rs_id'];
							$movement_no = process_ms_movement($rs_id, 'FDFB', $b, $conn, $ms_conn);

							if ($movement_no[0] == 1) 
								$a = update_approved_rms_header($rs_id, $value, $movement_no[1], $value['approver_comment'], $b, $conn, $ms_conn);
							if ($movement_no[0] == 1 and $a == 1){
								// display_notification("Disposal #".$rs_id." is successfully approved.");
								if ($a) {
									$ac = update_central_approved_rms_header($value['rs_id'], $value['branch_id']);
									$ia = insert_bo_aging($value['rs_id'], $value['branch_id'], $value['branch_name']);
									if ($ac) {
										if ($ia) {
											mysql_query("COMMIT", $conn);
											mssql_query("COMMIT TRANSACTION", $ms_conn);
											notify($value['rs_id'] . " is successfully approved and updated.");
											notify($value['rs_id'] . " is successfully approved and updated in centralized database.");
										}
										else {
											mysql_query("ROLLBACK", $conn);
											mssql_query("ROLLBACK TRANSACTION", $ms_conn);
										}
									}
									else {
										mysql_query("ROLLBACK", $conn);
										mssql_query("ROLLBACK TRANSACTION", $ms_conn);
									}
								}
								else {
									mysql_query("ROLLBACK", $conn);
									mssql_query("ROLLBACK TRANSACTION", $ms_conn);
									notify($value['rs_id'] . " cannot be approved and updated. Try again later.", 2);
								}
							}
						}
						elseif ($value['temp_posted'] == 1) {
							mysql_query("BEGIN", $conn);
							$is_posted = update_posted_rms_header($value, $conn);
							if ($is_posted) {
								$ac = update_central_posted_rms_header($value['rs_id'], $value['branch_id']);
								if ($ac) {
									mysql_query("COMMIT", $conn);
									notify($value['rs_id'] . " is successfully posted and updated.");
									notify($value['rs_id'] . " is successfully posted and updated in centralized database.");
								}
								else {
									mysql_query("ROLLBACK", $conn);
								}
							}
							else {
								mysql_query("ROLLBACK", $conn);
								notify($value['rs_id'] . " cannot be posted and updated. Try again later.", 2);
							}
						}
						else {
							continue;
						}
					}
				}
			}

		}
	}

}


// Start Form
start_form();

if (!isset($_GET['sitrololo'])) 
	submit_center('UpdateRS', '<b>Run Updates</b>');

end_form();
end_page();