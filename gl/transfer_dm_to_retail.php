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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Transfer DM to other Supplier"), false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	

function update_sup_id_from_supp_trans($account_from,$account_to,$trans_no){
	$sql = "UPDATE ".TB_PREF."supp_trans SET supplier_id='$account_to' 
	WHERE type='53'
	AND trans_no='$trans_no'
	AND supplier_id='$account_from'";
	//db_query($sql,'failed to update supp_trans.');
	//display_error($sql);
}

function update_sup_id_from_gl_trans($account_from,$account_to,$trans_no,$supp_name){
	$supp_name="transferred DM from ".$supp_name;
	$sql = "UPDATE ".TB_PREF."gl_trans SET person_id='$account_to', memo_=".db_escape($supp_name)."
	WHERE type='53'
	AND type_no='$trans_no'
	AND person_id='$account_from'";
	//db_query($sql,'failed to update gl_trans.');
	//display_error($sql);
}

function update_sup_id_from_gl_trans_temp($account_from,$account_to,$trans_no){
	$sql = "UPDATE ".TB_PREF."gl_trans_temp SET person_id='$account_to' 
	WHERE type='53'
	AND type_no='$trans_no'
	AND person_id='$account_from'";
	//db_query($sql,'failed to update gl_trans temp.');
	//display_error($sql);
}

function update_rs_header($vendor_account_from,$vendor_account_to,$trans_no){
	$sql = "UPDATE ".TB_PREF."rms_header SET supplier_code='$vendor_account_to'
	where trans_type=53
	and movement_type='R2SSA'
	and trans_no='$trans_no'";
	//db_query_rs($sql,'failed to update gl_trans temp.');
	//display_error($sql);
}


function update_ms_rs_header($vendor_account_from,$vendor_account_to,$movement_no,$supp_name){
	$sql = "UPDATE Movements SET VendorCode=".db_escape($vendor_account_to).",ToDescription=".db_escape($supp_name)."
	where MovementNo=$movement_no
	and MovementCode='R2SSA'";
	//ms_db_query($sql,'failed to update gl_trans temp.');
	//display_error($sql);
}

//----------------------------------------------------------------------------------------
if (isset($_POST['Fix'])) {
	

	
	global $db_connections;
	$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	set_time_limit(0);
	
	$pending_dms_sql= "SELECT * FROM 0_dm_summary_from_2015_to_2017";
	$pending_dms_result=db_query($pending_dms_sql);

	while($pending_dms_result_row=mysql_fetch_array($pending_dms_result)) 
	{
		
		$supp_id=$pending_dms_result_row['supplier_id'];
		$supp_ref=$pending_dms_result_row['supp_reference'];
		$supp_name=$pending_dms_result_row['supp_name'];
		$br_code=$pending_dms_result_row['branch_code'];
		$trans_no=$pending_dms_result_row['trans_no'];
		$ref=$pending_dms_result_row['reference'];
		$date_=$pending_dms_result_row['tran_date'];
		$amount=$pending_dms_result_row['ov_amount'];

		$sql = "INSERT INTO transfers.0_transferred_dms (supp_id, supp_ref, supp_name, br_code, trans_no, ref, date_, amount) 
		VALUES ('$supp_id', ".db_escape($supp_ref).",".db_escape($supp_name).", '$br_code', '$trans_no', ".db_escape($ref).", '$date_', '$amount')";
		db_query($sql, "dm could not be inserted");	
		//display_error($sql);
		
	}
		display_notification("Create DM Successful!");
	
	
	// // display_error($_POST['account_from']);
	// // display_error($_POST['account_to']);
	
	// $account_from=$_POST['account_from'];
	// $account_to=$_POST['account_to'];
	
	// //===========GET NEW VENDOR CODE.
	// $sql_get_new_vendor_code= "SELECT supp_ref, supp_name FROM ".TB_PREF."suppliers WHERE supplier_id='".$_POST['account_to']."'";
	// //display_error($sql_get_old_bank_id);
	// $result1 = db_query($sql_get_new_vendor_code, "failed to get bank_accounts id.");
	// $row1=db_fetch($result1);
	// $vendor_account_to=$row1['supp_ref'];
	// $supp_name=$row1['supp_name'];
	// //display_error($vendor_account_to);
	 
	// $sql = "SELECT 'srs' as branch_code, sup.supp_name, spt.*, s.supp_ref
	// FROM 0_supp_trans as spt
	// LEFT JOIN 0_suppliers as sup
	// ON spt.supplier_id=sup.supplier_id
	// LEFT JOIN 0_suppliers as s
	// ON spt.supplier_id=s.supplier_id
	// where type=53
	// and cv_id=0
	// and ov_amount!=0
	// and tran_date>='2016-01-01' and tran_date<='2017-12-31'
	// and spt.supplier_id='$account_from'";

	// $result=db_query($sql);

	// while($row=mysql_fetch_array($result)) 
	// {
		// $vendor_account_from=$row['supp_ref'];
		// //display_error($row['trans_no']);
		
		// //===========GET MS MOVEMENT NO.
		// $sql_get_movementno= "SELECT movement_no FROM ".TB_PREF."rms_header WHERE trans_no='".$row['trans_no']."'";
		// //display_error($sql_get_old_bank_id);
		// $result2 = db_query_rs($sql_get_movementno, "failed to get bank_accounts id.");
		// $row2=db_fetch($result2);
		// $movement_no=$row2['movement_no'];
		// //display_error($movement_no);
	
		// update_sup_id_from_supp_trans($account_from,$account_to,$row['trans_no']);
		// update_sup_id_from_gl_trans($account_from,$account_to,$row['trans_no'], $row['supp_name']);
		// update_sup_id_from_gl_trans_temp($account_from,$account_to,$row['trans_no']);
		// update_rs_header($vendor_account_from,$vendor_account_to,$row['trans_no']);
		// update_ms_rs_header($vendor_account_from,$vendor_account_to,$movement_no,$supp_name);
		
	// }

}


start_form();
start_table();
start_row();
supplier_list_cells(_("Transfer DM From Supplier:"), 'account_from', null, true);
supplier_list_cells(_("to Supplier:"), 'account_to', null, true);
end_row();
end_table();
br();
br();
start_row();
submit_center('Fix', 'Transfer');
end_table();
end_form();
end_page();
?>