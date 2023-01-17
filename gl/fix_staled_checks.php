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
	
	global $db_connections,$Refs;
	
	set_time_limit(0);
	
	begin_transaction();	
	
	$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		
	$sql="SELECT '$myBranchCode' as br_code, c.type, c.trans_no, c.supplier_id, 
	c.tran_date, b.trans_date, cd.chk_date, c.cv_id,c.non_trade,
	a.cv_date,a.cv_no,b.id as b_id, cd.chk_number, ROUND(cd.chk_amount,2) as  chk_amount,
	cd.deposit_date,'0' as reconciled
	FROM 0_supp_trans as c
	LEFT JOIN 0_cv_header as a 
	ON c.cv_id=a.id 
	LEFT JOIN 0_bank_trans as b 
	ON a.bank_trans_id=b.id 
	LEFT JOIN 0_cheque_details as cd 
	ON b.id=cd.bank_trans_id
	WHERE a.amount!=0 
	AND c.ov_amount!=0 
	AND c.type='22' 
	AND b.type='22'
	AND b.amount!=0 
	AND !ISNULL(cd.chk_number) 
	AND cd.chk_number IN (
6017602,
6130348

	)
	order by b.trans_date
	";
		
	//display_error($sql);
		
	$result= db_query($sql, "failed to get bank_accounts id.");
	
	//2305-pdc payable
	//2501601-pdc payable-aub
	while($row = db_fetch($result))
	{
		$date_ = sql2date($row['chk_date']);
		
		//$staled_check_month=add_months($date_,+6);
		//$staled_check_month_date=end_month($staled_check_month);
		$staled_check_month_date='12/31/2016';
		
		//display_error($staled_check_month_date);
		
		$ref   = $Refs->get_next(ST_STALEDCHECKS);
		$memo_ = "Adjustment for Payment, Staled Check, Check#: ".$row['chk_number'];
		
		$trans_type = ST_STALEDCHECKS;

		$trans_id = get_next_trans_no($trans_type);
		
			add_gl_trans($trans_type, $trans_id, $staled_check_month_date, 10102299, 0, 0, $memo_, $row['chk_amount'],null,3,$row['supplier_id']);
			add_gl_trans($trans_type, $trans_id, $staled_check_month_date, 2000014, 0, 0, $memo_, -$row['chk_amount'],null,3,$row['supplier_id']);

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $staled_check_month_date, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $staled_check_month_date);
		
		//display_error($trans_id);
	}

	display_notification("Fixing Staled Checks are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }	
}

start_form();
start_row();
submit_center('Fix',_("Fix Staled Checks"), true, '', false);
end_table();
end_form();
end_page();
?>