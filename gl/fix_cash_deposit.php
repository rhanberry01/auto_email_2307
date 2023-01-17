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
page(_($help_context = "Fix Bank Accounts"), false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	

function add_cash_dep_headerx($myBranchCode,$payment_type,$date_,$trans_type,$trans_no,$gross_amount,$desc,$memo,$payee_name,$date_deposit,$account) {
$date_=date2sql($date_);
$date_deposit=date2sql($date_deposit);

if ($date_deposit==''){
	$date_deposit='0000-00-00';
}
	
if ($payment_type=='0'){
$payment_type_id='1';
$payment_type='cash';
}

if ($payment_type=='1' or $payment_type=='2'){
$payment_type_id='2';
$payment_type='check';
}

$today=date2sql(Today());

$sql = "INSERT INTO cash_deposit.".TB_PREF."cash_dep_header_new(cd_br_code,cd_payment_type_id,cd_payment_type,cd_sales_date,cd_trans_date,cd_trans_type,cd_aria_trans_no,cd_gross_amount,cd_description,cd_memo,cd_date_deposited,cd_date_cleared,cd_cleared,cd_date_deposit,cd_bank_account_code)				
VALUES ('$myBranchCode','$payment_type_id','$payment_type','$date_','".$date_."','$trans_type',".db_escape($trans_no).",'$gross_amount',".db_escape($desc).",".db_escape($memo).",'0000-00-00','0000-00-00','0','$date_deposit',$account)";		
db_query($sql,'unable to add bank deposit header');

$id = db_insert_id();
return $id;
}

function add_cash_dep_detailsx($cd_det_id,$cd_det_date,$cd_det_type,$cd_det_trans_no,$cd_det_amount,$cd_det_memo,$payment_to_bank,$check_bank,$check_branch,$check_number,$check_date) 
{
$cd_det_date=date2sql($cd_det_date);

if ($check_date=='') {
	$check_date='0000-00-00';
}
else {
	$check_date=date2sql($check_date);
}


$sql = "INSERT INTO cash_deposit.".TB_PREF."cash_dep_details_new(cd_det_id,cd_sales_date,cd_det_type,cd_det_aria_trans_no,cd_det_amount,cd_chk_bank_id,cd_chk_bank,cd_chk_bank_branch,cd_chk_number,cd_chk_date,cd_tran_date,cd_det_memo)				
VALUES ('$cd_det_id','".$cd_det_date."','$cd_det_type','$cd_det_trans_no','$cd_det_amount','$payment_to_bank',".db_escape($check_bank).",".db_escape($check_branch).",".db_escape($check_number).",'$check_date','".$cd_det_date."',".db_escape($cd_det_memo).")";		

//display_error($sql);
db_query($sql,'failed to insert details.');
}



if (isset($_POST['Fix'])){
	
	global $db_connections,$Refs;
	
	set_time_limit(0);
	
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	begin_transaction();	
	
	$trans_type = 101;
		
	$sql="SELECT * FROM cash_deposit.0_salesbook_manual_2018
	where br_code='$this_branch'
	and cash_in_bank!=0 
	and status=0
	and (sales_date >='2018-07-01' and sales_date <='2018-12-31')";

	//display_error($sql);

	//die();
		
	$result= db_query($sql, "failed to get bank_accounts id.");
	
	while($row = db_fetch($result))
	{
		$memo_=' ';
		$sales_manual_id=$row['id'];
		$sales_date=$row['sales_date'];
		$date_deposited=$row['date_deposited'];
		$cash_on_hand=$row['cash_on_hand'];
		$cash_in_bank=$row['cash_in_bank'];
		$account=$row['account'];
		$br_code=$row['br_code'];
		
		
		$sql2 = "SELECT cd_memo FROM cash_deposit.".TB_PREF."cash_dep_header WHERE cd_br_code='$this_branch' and cd_trans_type='61' and cd_gross_amount='$cash_in_bank' and cd_sales_date='$sales_date'";
		$res2 = db_query($sql2);
		$count=db_num_rows($res2);
		//display_error($sql);
		if ($count>0){
		$row2 = db_fetch($res2);
		$memo_=$row2['cd_memo'];
		}
		
		$id=add_cash_dep_headerx($br_code,$payment_type=0,sql2date($sales_date),$trans_type=101,$trans_no=0,$cash_in_bank,$desc,$memo_,$payee_name,sql2date($date_deposited),$account);
		add_cash_dep_detailsx($id,sql2date($sales_date),$trans_type=101,$trans_no=0,$cash_in_bank,$memo_,$payment_to_bank=0,$check_bank,$check_branch,$check_number,$check_date);
		
		add_gl_trans($trans_type, $id, sql2date($sales_date), $account, 0, 0, $memo_, $cash_in_bank);
		add_gl_trans($trans_type, $id, sql2date($sales_date), '1010', 0, 0, $memo_, -$cash_in_bank);
		
		$sqlx = "UPDATE cash_deposit.0_salesbook_manual_2018 SET status = '1'
		WHERE id = '$sales_manual_id'";
		db_query($sqlx,'failed to ');

	}

	display_notification("Successful!");
	
	commit_transaction();
}

start_form();
start_row();
submit_center('Fix',_("Fix"), true, '', false);
end_table();
end_form();
end_page();
?>