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
	global $Refs, $db_connections;
	
	set_time_limit(0);
	
	begin_transaction();	
	
	$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		
	$sql="SELECT id,date_,amount,DATE_FORMAT(date_,'%M') as month_desc FROM cash_deposit.0_fix_sales WHERE br_code='$myBranchCode' and status=0";
	$result= db_query($sql, "failed to get cash_deposit.fix_sales.");
		
	//2305-pdc payable
	//2501601-pdc payable-aub
	while($row = db_fetch($result))
	{
		$id = $row['id'];
		$date_ = sql2date($row['date_']);
		$month_desc = $row['month_desc'];
		$ref   = $Refs->get_next(0);
		$memo_ = "BANK RECON, Sales Deposit for the month of ".$row['month_desc'];
		
		$trans_type = ST_JOURNAL;

		$trans_id = get_next_trans_no($trans_type);
		
			add_gl_trans($trans_type, $trans_id, $date_, 1020021, 0, 0, $memo_, $row['amount'],null,null,'');
			add_gl_trans($trans_type, $trans_id, $date_, 1010, 0, 0, $memo_, -$row['amount'],null,null,'');

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $date_, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $date_);
		
		$sql__="UPDATE cash_deposit.0_fix_sales SET status=1 WHERE br_code='$myBranchCode' and id='$id'";
		$result_= db_query($sql__, "failed to cash_deposit.fix_sales.");
	}

	
	display_notification("Journal for Manual Sales are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
}

start_form();
start_row();
submit_center('Fix',_("Journal Manual Sales"), true, '', false);
end_table();
end_form();
end_page();
?>