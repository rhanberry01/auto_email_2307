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
				
// if (isset($_GET['AddedID'])) {
	// $trans_no = $_GET['AddedID'];
	// $cvid = $_GET['CV_id'];
	// $trans_type = ST_CREDITDEBITDEPOSIT;
   	// display_notification_centered( _("Other Income has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	// display_footer_exit();
// }

//----------------------------------------------------------------------------------------
if (isset($_POST['Fix'])){
	
	set_time_limit(0);
	//===========GET OLD AND NEW BANK ID.
	$sql_get_old_bank_id= "SELECT id FROM ".TB_PREF."bank_accounts WHERE account_code='".$_POST['account_from']."'";
	//display_error($sql_get_old_bank_id);
	$result1 = db_query($sql_get_old_bank_id, "failed to get bank_accounts id.");
	$row1=db_fetch($result1);
	$old_bank_id=$row1['id'];
	//display_error($old_bank_id);
	
	$sql_get_new_bank_id= "SELECT id FROM ".TB_PREF."bank_accounts WHERE account_code='".$_POST['account_to']."'";
	//display_error($sql_get_new_bank_id);
	$result2 = db_query($sql_get_new_bank_id, "failed to get bank_accounts id.");
	$row2=db_fetch($result2);
	$new_bank_id=$row2['id'];
	//display_error($new_bank_id);
	
	if ($old_bank_id!='' AND $new_bank_id!='' ){	
	begin_transaction();	
	//===========UPDATE gl_trans
	$sql_update_gl_trans = "UPDATE ".TB_PREF."gl_trans 
	SET account='".$_POST['account_to']."' WHERE account = '".$_POST['account_from']."' and tran_date>='2016-01-01'";
	//display_error($sql_update_gl_trans);
	db_query($sql_update_gl_trans,"Failed to update gl_trans.");
	
	//===========UPDATE gl_trans_temp
	$sql_update_gl_trans_temp = "UPDATE ".TB_PREF."gl_trans_temp 
	SET account='".$_POST['account_to']."' WHERE account = '".$_POST['account_from']."' and tran_date>='2016-01-01'";
	//display_error($sql_update_gl_trans_temp);
	db_query($sql_update_gl_trans_temp,"Failed to update gl_trans.");
	
	//===========UPDATE bank_trans
	$sql_update_bank_trans = "UPDATE ".TB_PREF."bank_trans 
	SET bank_act='$new_bank_id' WHERE bank_act = '$old_bank_id' and trans_date>='2016-01-01'";
	//display_error($sql_update_bank_trans);
	db_query($sql_update_bank_trans,"Failed to update bank_trans.");
	
	//===========UPDATE other_income_payment_header
	$sql_update_oi_payment_header= "UPDATE ".TB_PREF."other_income_payment_header
	SET bd_payment_to_bank='$new_bank_id' WHERE bd_payment_to_bank = '$old_bank_id' and bd_trans_date>='2016-01-01'";
	//display_error($sql_update_oi_payment_header);
	db_query($sql_update_oi_payment_header,"Failed to update other_income_payment_header.");
	
	//===========UPDATE check_account
	$sql_update_check_account = "UPDATE ".TB_PREF."check_account 
	SET bank_ref='".$_POST['account_to']."' WHERE bank_ref = '".$_POST['account_from']."'";
	//display_error($sql_update_check_account);
	db_query($sql_update_check_account,"Failed to update check_account.");
	
	//===========UPDATE cheque_details
	$sql_update_cheque_details= "UPDATE ".TB_PREF."cheque_details 
	SET bank_id='$new_bank_id' WHERE bank_id= '$old_bank_id' and chk_date>='2016-01-01'";
	//display_error($sql_update_cheque_details);
	db_query($sql_update_cheque_details,"Failed to update cheque_details.");
	
	//===========UPDATE acquiring_banks
	$sql_update_acquiring_banks = "UPDATE ".TB_PREF."acquiring_banks 
	SET gl_bank_account='".$_POST['account_to']."' WHERE gl_bank_account= '".$_POST['account_from']."'";
	//display_error($sql_update_acquiring_banks);
	db_query($sql_update_acquiring_banks,"Failed to update acquiring_banks.");
	
	//===========UPDATE cash_dep_header
	$sql_update_cash_dep_header= "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
	SET cd_bank_deposited='$new_bank_id' WHERE cd_bank_deposited = '$old_bank_id' and cd_sales_date>='2016-01-01'";
	//display_error($sql_update_cash_dep_header);
	db_query($sql_update_cash_dep_header,"Failed to update cash_dep_header.");

	//===========UPDATE cash_dep_details
	// $sql_update_cash_dep_details= "UPDATE cash_deposit.".TB_PREF."cash_dep_details
	// SET cd_chk_bank_id='$new_bank_id' WHERE cd_chk_bank_id = '$old_bank_id'";
	// //display_error($sql_update_cash_dep_details);
	// db_query($sql_update_cash_dep_details,"Failed to update cash_dep_details.");
	
	// //===========UPDATE chart_master
	$sql_update_chart_master = "UPDATE ".TB_PREF."chart_master 
	SET inactive='1' WHERE account_code= '".$_POST['account_from']."'";
	//display_error($sql_update_chart_master);
	db_query($sql_update_chart_master,"Failed to update chart_master.");
	
	//===========UPDATE bank_accounts
	$sql_update_bank_accounts= "UPDATE ".TB_PREF."bank_accounts 
	SET inactive='1' WHERE account_code= '".$_POST['account_from']."'";
	//display_error($sql_update_bank_accounts);
	db_query($sql_update_bank_accounts,"Failed to update bank_accounts.");
	
	display_notification("Fixing Bank Accounts are Successful!");
	
	commit_transaction();
	}
	else {
	display_notification("Failed to update bank accounts");
	}
	
}

start_form();
start_table();
start_row();
 gl_all_accounts_list_cells(_("Change Account FROM:"), 'account_from', null, false, false, "All Accounts");
 gl_all_accounts_list_cells(_("TO:"), 'account_to', null, false, false, "All Accounts");
end_row();
end_table();
br();
br();
start_row();
submit_center('Fix',_("Fix Bank Accounts"), true, '', 'default');
end_table();
end_form();
end_page();
?>