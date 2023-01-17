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
$posted_add = find_submit('posted_id');


function update_posted_rms_header($rs_id,$approver_comments)
{
	global $db_connections;
	$sql = "UPDATE returned_merchandise.".TB_PREF."rms_header SET 
			date_temp_posted = '".date2sql(Today())."',
			temp_posted = '1',
			temp_posted_by_aria_user = ".$_SESSION['wa_current_user']->user.",
			temp_post_comment =".db_escape($approver_comments)."
			WHERE rs_id ='$rs_id'";
	$objquery = db_query_rs($sql,'failed to update movement (FDFB)');
	$cancel=rollback_mssql_transaction($sql, $objquery);
	if($cancel == 1){
		return 	false;
	} 
	return 1;
}
function check_damaged($rs_id){
	$count = 0 ;
	$count_ = 0 ;
	$barcode = array();
	
	$sql = "SELECT prod_id,barcode,item_name,uom,SUM(qty) as qty,orig_uom,orig_multiplier,
					custom_multiplier,price,supplier_code
			FROM returned_merchandise.".TB_PREF."rms_items WHERE rs_id IN ($rs_id)
			GROUP BY prod_id,barcode,item_name,uom,orig_uom,orig_multiplier,
					custom_multiplier,price,supplier_code";
	$res = db_query_rs($sql);
	while($row = db_fetch($res))
	{
		$count++;
		
		$pack = $row['custom_multiplier'] == 0 ? $row['orig_multiplier'] : $row['custom_multiplier'];
		$pcs_qty = ($pack * $row['qty']);
		
		$damaged_check = "SELECT Damaged FROM Products WHERE ProductID = ".$row['prod_id'];
		$damaged_ = ms_db_query($damaged_check);
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
function getFDFBCounter()
{
	return getCounter('FDFB');
}

function get_rs_items_total($rs_id)
{
	$sql = "SELECT * FROM ".TB_PREF."rms_items
			WHERE rs_id=$rs_id";
	$res = db_query_rs($sql);

	while($row = db_fetch($res)){
		$total += round2($row['qty']*$row['price'],3);
	}
	
	return $total;
}

function get_username_by_id_($id)
{
	$sql = "SELECT real_name FROM returned_merchandise.".TB_PREF."users WHERE id = $id";
	$res = db_query_rs($sql);
	$row = db_fetch($res);
	return $row[0];
}

function add_ms_movement_line_disposal($MovementID,$ProductID,$ProductCode,$Description,
				$UOM,$unitcost,$qty,$pack,$barcode)
{
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
				UOM,unitcost,qty,extended,pack,barcode)
			VALUES ($MovementID,$ProductID,'$ProductCode','$Description',
				'$UOM',$unitcost,$qty,".round($unitcost*$qty,4).",$pack,'$barcode')";
	$objquery = ms_db_query($sql,"error inserting movement line");
	$cancel=rollback_mssql_transaction($sql, $objquery);
	if($cancel == 1){
		return 1;
	} 
}

function get_pos_product_row($barcode)
{
	$sql = "SELECT * FROM POS_Products WHERE Barcode = '".$barcode."'";
	$res = ms_db_query($sql);
	$prod =  mssql_fetch_array($res);
	
	return $prod;
}

function get_product_row($prod_id,$column='')
{
	$sql = "SELECT ".($column == '' ? '*' : $column)." FROM Products WHERE ProductID = $prod_id";
	$res = ms_db_query($sql);
	$prod =  mssql_fetch_array($res);
	return $prod;
}

function update_approved_rms_header($rs_id,$movement_no,$approver_comments)
{
	global $db_connections;
	$sql = "UPDATE returned_merchandise.".TB_PREF."rms_header SET 
			date_approved = '".date2sql(Today())."',
			approved = '1',
			movement_no = $movement_no,
			approved_by_aria_user = ".$_SESSION['wa_current_user']->user.",
			approver_comment =".db_escape($approver_comments)."
			WHERE rs_id ='$rs_id'";
	$objquery = db_query_rs($sql,'failed to update movement (FDFB)');
	$cancel=rollback_mssql_transaction($sql, $objquery);
	if($cancel == 1){
		return false;
	}
	return 1;
}

function process_ms_movement($rs_id, $MovementCode, $remarks='')
{
	//display_error('ok');
	global $db_connections;
	$sql = "SELECT SUM(qty * (IF(custom_multiplier=0,orig_multiplier,custom_multiplier)))
			FROM returned_merchandise.".TB_PREF."rms_items 
			WHERE rs_id IN ($rs_id)";
	$res = db_query_rs($sql);
	$row = db_fetch($res);
	$total_qty = $row[0];
	//display_error($sql);
	
	$sql = "SELECT SUM(ROUND(qty*price,4)) FROM returned_merchandise.".TB_PREF."rms_items 
			WHERE rs_id IN ($rs_id)";
	$res = db_query_rs($sql);
	$row = db_fetch($res);
	$net_total = $row[0];
	
	$vendor_code = $ToDescription = $ToAddress = $ContactPerson = '';

	if($MovementCode == 'FDFB')
	{	
		$movement_no = str_pad(getFDFBCounter(), 10, "0", STR_PAD_LEFT);
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
	$sql = "INSERT INTO Movements (MovementNo,
				MovementCode,ReferenceNo,SourceInvoiceNo,SourceDRNo,ToDescription,ToAddress,ContactPerson,FromDescription,
				FromAddress,DateCreated,LastModifiedBy,LastDateModified,Status,PostedBy,PostedDate,Terms,TransactionDate,
				FieldStyleCode1,NetTotal,StatusDescription,TotalQty,CreatedBy,Remarks,CustomerCode,VendorCode,BranchCode,
				CashDiscount,FieldStyleCode,ToBranchCode,FrBranchCode,sourcemovementno,countered,Transmitted,WithPayable,
				WithReceivable,OtherExpenses,ForexRate,ForexCurrency,SalesmanID,RECEIVEDBY)
			VALUES ('$movement_no','$MovementCode','','','',$ToDescription,$ToAddress,$ContactPerson,
				'$from_description','','".Today()." 00:00:00','".$_SESSION['wa_current_user']->ms_user_id."','".Today()." 00:00:00',2,'".
				$_SESSION['wa_current_user']->ms_user_id."','".Today()." 00:00:00',0,'".Today()." 00:00:00','',"
				. ($net_total+0) .",'POSTED',".($total_qty+0).",'".$_SESSION['wa_current_user']->ms_user_id."','$remarks',
				'','$vendor_code','','','','','','',0,0,0,0,0,1,'PHP',0,'')";
	$objquery = ms_db_query($sql);
	$cancel=rollback_mssql_transaction($sql, $objquery);
	if($cancel == 1){
		return false;
	}
	
	// $last_inserted_line_res = ms_db_query("SELECT IDENT_CURRENT('Movements') AS LAST");
	// $last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	// $movement_id = $last_inserted_line_row['LAST'];
	
	$last_inserted_line_res = ms_db_query("SELECT SCOPE_IDENTITY() AS [SCOPE_IDENTITY]");
	$last_inserted_line_row = mssql_fetch_array($last_inserted_line_res);
	$movement_id = $last_inserted_line_row['SCOPE_IDENTITY'];
	
	if ($movement_id == 0)
	{
		display_error($sql);
		return false;
	}

	$sql = "SELECT prod_id,barcode,item_name,uom,SUM(qty) as qty,orig_uom,orig_multiplier,
			custom_multiplier,price,supplier_code
			FROM returned_merchandise.".TB_PREF."rms_items WHERE rs_id IN ($rs_id)
			GROUP BY prod_id,barcode,item_name,uom,orig_uom,orig_multiplier,
			custom_multiplier,price,supplier_code;";

	$res = db_query_rs($sql);
		
	$cos_total = 0;
	while($row = db_fetch($res)) //Products and Product History
	{
		// $pos_row = get_pos_product
		$prod_row = get_product_row($row['prod_id']);
		$pos_prod_row = get_pos_product_row($row['barcode']);
		
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
				  $dmg_out, NULL, NULL, NULL, NULL, '".$_SESSION['wa_current_user']->ms_user_id."', NULL, NULL, '$MovementCode', '',
				  0, NULL, 0, 0, '', $beg_damaged, $flow_dmg)";
		$objquery = ms_db_query($producthistory);
		$cancel=rollback_mssql_transaction($sql, $objquery);
		if($cancel == 1){
			return false;
		}
		$line = add_ms_movement_line_disposal($movement_id,$row['prod_id'],$pos_prod_row['ProductCode'],$row['item_name'],
				$row['uom'],$row['price'],$row['qty'],$pack,$row['barcode'], $MovementCode);
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
		
		$objquery = ms_db_query($prod_sql);
		$cancel=rollback_mssql_transaction($prod_sql, $objquery);
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
		$objquery = ms_db_query($sql);
		$cancel=rollback_mssql_transaction($sql, $objquery);
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


if ($posted_add!=-1) 
{
global $Ajax;
//display_error($posted_add);
$approver_comments=$_POST['approver_comments'.$posted_add];
update_posted_rms_header($posted_add,$approver_comments);

display_notification("Disposal #".$posted_add." is successfully posted for approval.");
$Ajax->activate('dm_list');
}

if ($approve_add!='') 
{
global $Ajax, $db_connections;
	// $myserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["host"], 2);
	// $msserver = ping($db_connections[$_SESSION["wa_current_user"]->company]["ms_host"], 2);
	// if(!$myserver){
		// display_error($db_connections[$_SESSION["wa_current_user"]->company]["host"].' FAILED TO CONNECT.');
		// return false;
	// }elseif(!$msserver){
		// display_error($db_connections[$_SESSION["wa_current_user"]->company]["ms_host"].' FAILED TO CONNECT.');
		// return false;
	// }
		$check = check_damaged($approve_add);
		ms_db_query("BEGIN TRANSACTION");
		begin_transaction();
		if($check == 0){
			$approver_comments=$_POST['approver_comments'.$approve_add];
			$movement_no = process_ms_movement($approve_add,'FDFB');
			
			if($movement_no[0] == 1)
				$a = update_approved_rms_header($approve_add,$movement_no[1],$approver_comments);
			if($movement_no[0] == 1 and $a == 1){
				commit_transaction();
				ms_db_query("COMMIT TRANSACTION");
				display_notification("Disposal #".$approve_add." is successfully approved.");
			}
		}else{
				display_error("Cannot processed barcode(s) ".implode(',',$check)." in Trans. # ".$approve_add.".");		
		}
			$Ajax->activate('dm_list');
}

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

	$sql = "SELECT * FROM returned_merchandise.".TB_PREF."rms_header ";

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
	//display_error($sql);
	$res = db_query_rs($sql);                            


start_table($table_style2.' width=90%');
$th =array('#', 'SA to BO Date', 'Supplier','Extended','Created by','Processed by','Remarks', 'Status','Comment','Audit');
		
//array_push($th, 'Date Created', 'TransNo','MovementID','From Location','To Location', 'Created By', 'Date Posted', 'Posted By', 'Status','','','');

if (db_num_rows($res) > 0)
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
//display_error($approver);
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(get_rs_view_str($row['rs_id'],'# '.$row['rs_id']));
	label_cell(sql2date($row['rs_date']));
	label_cell(get_ms_supp_name($row['supplier_code']));
	$total=get_rs_items_total($row['rs_id']);
	label_cell(number_format2($total,3),'align=right');
	label_cell(get_username_by_id_($row['created_by']));
	label_cell(get_username_by_id_($row['processed_by']));
	label_cell($row['comment']);
	
	if ($row['approved']==0) {
	label_cell('For Approval');
	}
	else {
	label_cell("Approved by ".get_username_by_id($row['approved_by_aria_user']));
	}
		
	if ($row['approved']==0 and ($approver=='juliet' or $approver=='admin' or $approver=='6666' OR $approver=='jenC' or $approver=='cezz' or $approver=='0238')) {
	text_cells(_(""), 'approver_comments'.$row['rs_id']);
	}
	else {
	label_cell($row['approver_comment']);
	}

	// if ($row['approved']==0 and ($approver=='juliet' or $approver=='admin' or $approver=='6666' OR $approver=='jenC' or $approver=='cezz' or $approver=='0238' or $approver=='0056' or $approver=='121608' or $approver=='benjie')) {
	if ($row['approved']==0 and ($approver=='d4128' || $approver=='0238'||  $_SESSION['wa_current_user']->user == 1 )) {
	$selected='selected_id'.$row['rs_id'];
	submit_cells($selected, _("Approve Disposal"), "colspan=1",_('Approve Disposal'), true, ICON_ADD);
	// $selected_1='posted_id'.$row['rs_id'];
	// submit_cells($selected_1, _("Post Disposal"), "colspan=1",_('Post Disposal'), true, ICON_ADD);
	}
	// else if ($row['approved']==1){
	// label_cell('Approved');
	// }
	else {
		label_cell('Approved');
	}

	
	// if ($row['temp_posted']==1 and  $row['approved']==0 and ($approver=='admin' or $approver=='6666' OR $approver=='jenC')) {
	// $selected='selected_id'.$row['rs_id'];
	// submit_cells($selected, _("Approve Disposal"), "colspan=1",_('Approve Disposal'), true, ICON_ADD);
	// }
	// else if ($row['temp_posted']==0 and  $row['approved']==0 and ($approver=='admin' or $approver=='6666' OR $approver=='jenC')) {
	// label_cell('For Approval of Audit');
	// }
	// else {
	// label_cell('Approved');
	// }
	


	end_row();
}
end_table();
br();
br();
div_end();

end_form();
end_page();
?>