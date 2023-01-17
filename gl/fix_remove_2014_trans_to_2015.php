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
	global $Refs;
	
	set_time_limit(0);
	
	begin_transaction();	
	
	$sql="SELECT 'srs' as br_code, c.type, c.trans_no,  c.supplier_id, 
	c.tran_date,  b.trans_date, cd.chk_date,a.cv_date, c.cv_id,
	a.cv_no,b.id as b_id,  cd.chk_number, cd.chk_amount,
	cd.deposit_date,'0000-00-00' as reconciled
	FROM 0_supp_trans as c
	LEFT JOIN 0_cv_header as a 
	ON c.cv_id=a.id 
	LEFT JOIN 0_bank_trans as b 
	ON a.bank_trans_id=b.id 
	LEFT JOIN 0_cheque_details as cd 
	ON b.id=cd.bank_trans_id
	LEFT JOIN 0_gl_trans as g
	ON g.type_no=c.trans_no
	WHERE b.amount!=0 
	AND a.amount!=0 
	AND c.type='22' 
	AND g.type='22'
	AND b.type='22'
	AND c.ov_amount!=0 
	AND b.trans_date>='2015-01-01' AND b.trans_date <='2015-12-31'
	AND a.cv_date>='2014-01-01' AND a.cv_date <='2014-12-31'
	AND g.account='10102299'
AND !ISNULL(cd.chk_number) 
AND cd.chk_number IN (

5466204,

5466205,

5466209,

5466212,

5466213,

5466214,

5466215,

5466216



)

	order by b.trans_date";

	$result= db_query($sql, "failed to get bank_accounts id.");
	
	while($row = db_fetch($result))
	{
		
		$sql1 = "UPDATE ".TB_PREF."cheque_details SET chk_date='".$row['cv_date']."' 
		WHERE bank_trans_id='".$row['b_id']."'";
		db_query($sql1,"Failed to update cheque_details.");
		
		$sql2 = "UPDATE ".TB_PREF."bank_trans SET trans_date='".$row['cv_date']."' 
		WHERE id='".$row['b_id']."'";
		db_query($sql2,"Failed to update 0_bank_trans.");
		
		$sql3 = "UPDATE ".TB_PREF."supp_trans SET tran_date='".$row['cv_date']."' 
		WHERE type='22' AND trans_no='".$row['trans_no']."'";
		db_query($sql3,"Failed to update 0_supp_trans.");
		
		$sql4 = "UPDATE ".TB_PREF."gl_trans SET tran_date='".$row['cv_date']."' 
		WHERE type='22' AND type_no='".$row['trans_no']."'";
		db_query($sql4,"Failed to update 0_gl_trans.");
		
		
	}

	
	display_notification("Fixing/Removing of 2014 transaction to 2015 are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
}

start_form();
start_row();
submit_center('Fix',_("Remove 2014 to 2015"), true, '', false);
end_table();
end_form();
end_page();
?>