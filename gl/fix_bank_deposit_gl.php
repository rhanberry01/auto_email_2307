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
	
$sql="SELECT * FROM `0_gl_trans`
where type='61'
and type_no IN (

)

and account='1020021'
and amount>0


";
		//10102299
		//1020021
		
		
$result= db_query($sql, "failed to get id.");
	
	//2305-pdc payable
	//2501601-pdc payable-aub
	while($row = db_fetch($result))
	{
		
		// $sql1 = "UPDATE ".TB_PREF."gl_trans SET account='10102299'
		// WHERE type_no='".$row['trans_no']."' and type='22' and account='2501601'";
		// db_query($sql1,"Failed to update gl_trans.");
		
		$date_ = sql2date($row['tran_date']);
		//$date_='01/31/2015';
		//$ref   = $Refs->get_next(0);
	//	$memo_ = "Adjustment for Payment, Outstanding Checks Beginning, Transaction#: ".$row['trans_no'];
		
		//$trans_type = ST_JOURNAL;
		$amount=abs($row['amount']);
			
			add_gl_trans(61,$row['type_no'], $date_, 272727, 0, 0, $row['memo_'],-$amount,null,3,$row['person_id']);
		
	}

	
	display_notification("Fixing cash deposit gl are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
}

start_form();
start_row();
submit_center('Fix',_("Fix Cash Deposit GL"), true, '', false);
end_table();
end_form();
end_page();
?>