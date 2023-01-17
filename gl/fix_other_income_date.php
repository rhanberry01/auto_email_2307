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
page(_($help_context = "Fix Other Income Date Deposit"), false, false, "", $js);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	$cvid = $_GET['CV_id'];
	$trans_type = ST_CREDITDEBITDEPOSIT;
   	display_notification_centered( _("Other Income has been fixed"));
	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	br();
	display_note(get_cv_view_str($cvid, _("View Transaction")));
   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	display_footer_exit();
}


//----------------------------------------------------------------------------------------
if (isset($_POST['Add'])){
	
	$sql1 = "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
	 SET cd_date_deposited='".date2sql($_POST['date'])."'
	WHERE cd_aria_trans_no = '".$_POST['trans_no']."'
	AND cd_trans_type='2'";
	db_query($sql1,"Failed to update cash deposit header.");
	
	$sql = "UPDATE ".TB_PREF."other_income_payment_header 
	SET bd_date_deposited='".date2sql($_POST['date'])."'
	WHERE bd_trans_no = '".$_POST['trans_no']."'";
	db_query($sql,"Failed to update cash deposit header.");
	
	$sql2 = "UPDATE ".TB_PREF."bank_trans 
	SET trans_date='".date2sql($_POST['date'])."'
	WHERE ref = '".$_POST['trans_no']."'
	AND type='2'";
	db_query($sql2,"Failed to update cash deposit header.");
	
	$sql3 = "UPDATE ".TB_PREF."bank_deposit_cheque_details 
	SET deposit_date='".date2sql($_POST['date'])."'
	WHERE bank_trans_id = '".$_POST['trans_no']."'
	AND type='2'";
	db_query($sql3,"Failed to update cash deposit header.");
	
	$sql4 = "UPDATE ".TB_PREF."gl_trans 
	SET tran_date='".date2sql($_POST['date'])."'
	WHERE type_no = '".$_POST['trans_no']."'
	AND type='2'";
	db_query($sql4,"Failed to update cash deposit header.");

display_notification("Fixing Other Income Date Deposit is Successful!");
}
start_form();
start_table();
start_row();
ref_cells('Transaction #:', 'trans_no');
date_cells('Change date deposited to :', 'date');
end_row();
end_table();
br();
start_row();
submit_center('Add',_("Fix Other Income"), true, '', 'default');
end_table();
end_form();
end_page();
?>