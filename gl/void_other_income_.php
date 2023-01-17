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
page(_($help_context = "Void Other Income"), false, false, "", $js);

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
	
	
		$res2=get_gl_trans(2, $_POST['trans_no']);
		$row2=db_fetch($res2);
		$tran_date=sql2date($row2['tran_date']);
		
		
		if (is_date_in_event_locker($tran_date)==1)
		{
			display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
			exit();
		}
	
	
$cash_dep_sql = "SELECT cd_id FROM cash_deposit.".TB_PREF."cash_dep_header
WHERE cd_trans_type = '2'
AND cd_trans_no = '".$_POST['trans_no']."'";
$res = db_query($cash_dep_sql);
$row=db_fetch($res);
$cash_dep_id=$row['cd_id'];

$sqlupdate_status="UPDATE ".TB_PREF."bank_statement_bpi
SET type='0', reference='0', cleared='0' where 
reference='$cash_dep_id' and type='2'";
//display_error($sqlupdate_status);
db_query($sqlupdate_status);

$sqlupdate_status="UPDATE ".TB_PREF."bank_statement_metro
SET type='0', reference='0', cleared='0' where 
reference='$cash_dep_id' and type='2'";
//display_error($sqlupdate_status);
db_query($sqlupdate_status);

$sqldeldep_header = "Delete FROM cash_deposit.".TB_PREF."cash_dep_header
WHERE cd_trans_type = '2'
AND cd_trans_no ='".$_POST['trans_no']."'";
db_query($sqldeldep_header);
	
	
$sql = "DELETE FROM ".TB_PREF."bank_trans WHERE type='2' AND trans_no='".$_POST['trans_no']."'";
db_query($sql, "Could not delete bank trans");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."other_income_payment_header WHERE bd_trans_type='2' AND bd_trans_no='".$_POST['trans_no']."'";
db_query($sql, "Could not delete Other income payment header.");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."other_income_payment_details WHERE bd_det_type='2' AND bd_det_trans_no='".$_POST['trans_no']."'";
db_query($sql, "Could not delete Other income payment details.");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."bank_deposit_cheque_details WHERE type='2' AND bank_trans_id='".$_POST['trans_no']."'";
db_query($sql, "Could not delete bank deposit cheque details.");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."gl_trans WHERE type='2' AND type_no='".$_POST['trans_no']."'";
db_query($sql, "Could not delete gl transaction.");

// $type_no=$_POST['trans_no'];
// $type=2;
// void_gl_trans($type, $type_no, true);	
	
display_notification("Voiding Other Income Transaction is Successful!");
}
start_form();
start_table();
start_row();
ref_cells('Transaction #:', 'trans_no');
end_row();
end_table();
br();
start_row();
submit_center('Add',_("Void Other Income"), true, '', 'default');
end_table();
end_form();
end_page();
?>