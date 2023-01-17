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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/acquiring_bank_deduction_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/admin/db/voiding_db.inc");
// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $start = $time;
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Credit Card/Debit Card Reconciliation", false, false, "", $js);
//------------------------------------------------------------------------------------------------
if(isset($_POST['cancel_insert'])) {
header("Location: ../inquiry/acquiring_bank_deductions.php");
}

if(isset($_POST['insert_new'])) {
if ($_POST['new_tran_date']=='') {
display_error(_("Transaction Date cannot be empty."));
set_focus('new_tran_date');
return false;
}
if ($_POST['new_account_num']=='') {
display_error(_("Account Number cannot be empty."));
set_focus('new_account_num');
return false;
}
if ($_POST['new_approval_no']=='') {
display_error(_("Approval Number cannot be empty."));
return false;
}
if ($_POST['new_acquiring_bank_id']=='') {
display_error(_("Acquiring Bank cannot be empty."));
return false;
}
if ($_POST['new_card_tender_type']=='') {
display_error(_("Tender Type cannot be empty."));
return false;
}

if ($_POST['insertion_type']=='') {
display_error(_("Tender Type cannot be empty."));
return false;
}

if (($_POST['new_amount']=='') or ($_POST['new_amount']<=0)) {
display_error(_("Amount cannot be empty and less than or equal to zero (0)."));
return false;
}

if ($_POST['new_card_tender_type']=='0') {
$new_card_tender_type='014';
$bank_header=$selected_bank."Debit";
}
else {
$new_card_tender_type='013';
$bank_header=$selected_bank."Credit";
}

if ($_POST['insertion_type']=='0') {
$over_payment='1';
$charge_back='0';
}
else {
$over_payment='0';
$charge_back='1';
}


$new_acquiring_bank=get_acquiring_bank_col($_POST['new_acquiring_bank_id'], 'acquiring_bank');
$sql = "INSERT INTO ".TB_PREF."sales_debit_credit (dc_remittance_id,dc_remittance_date,dc_transaction_date,
dc_trans_no,dc_account_no,dc_tender_type,dc_approval_no,dc_trans_amount,dc_card_desc,dc_date_paid,dc_over_payment, dc_charge_back, processed,paid)
VALUES ('0','0000-00-00','".date2sql($_POST['new_tran_date'])."','0',".db_escape($_POST['new_account_num']).",
".db_escape($new_card_tender_type).",".db_escape($_POST['new_approval_no']).",".db_escape($_POST['new_amount']).",
".db_escape($new_acquiring_bank).",'0000-00-00','$over_payment','$charge_back','0','0')";
//display_error($sql);		
db_query($sql);

$trans_date=date2sql($_POST['new_tran_date']);
meta_forward($path_to_root."/sales/inquiry/acquiring_bank_deductions.php?tran_date=$trans_date&selected_bank=$new_acquiring_bank");
display_notification('New amount has been added.');
return true;
//header("Location: ../inquiry/acquiring_bank_deductions.php");
}

start_table($table_style2);
start_form();
date_cells(_("Transaction Date:"), 'new_tran_date', '', null, -1);
text_row(_("Account#"), 'new_account_num', null);
text_row(_("Approval#"),'new_approval_no', null);
acquiring_bank_list_row(' Acquiring Bank:', 'new_acquiring_bank_id',null);
yesno_list_row(_("Card Type:"), 'new_card_tender_type', '',_("Credit"), _("Debit"));
yesno_list_row(_("Insertion Type:"), 'insertion_type', '',_("Charge Back"), _("Over Payment"));
text_row('Amount:', 'new_amount');
end_table();

start_table();
submit_cells('insert_new', 'Insert New', "align=center",true,true,'ok.gif');
submit_cells('cancel_insert', 'Cancel', "align=center",true,false,ICON_CANCEL);
end_form();
end_table();
end_form();
end_page();
?>