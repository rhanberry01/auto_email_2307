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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
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
	//$cvid = $_GET['CV_id'];
	//$trans_type = ST_CREDITDEBITDEPOSIT;
   	display_notification_centered( _("Other Income has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	display_footer_exit();
}


//----------------------------------------------------------------------------------------
if (isset($_POST['Add'])){
set_time_limit(0);
	
	$sql= "SELECT cv_id FROM 0_cons_sales_header WHERE cv_id!='0'";
	$res=db_query($sql);

while($row = db_fetch($res))
{
	$sql1 = "UPDATE ".TB_PREF."supp_trans SET non_trade='0'
	WHERE cv_id='".$row['cv_id']."' and non_trade='1' ";
	db_query($sql1,"Failed to update supp_trans.");
	
	$sql2= "SELECT type, trans_no FROM 0_supp_trans WHERE cv_id='".$row['cv_id']."'";
	$res2=db_query($sql2);
			while($row2 = db_fetch($res2))
			{
				$sql2_1 = "UPDATE ".TB_PREF."gl_trans SET account='2000'
				WHERE type='".$row2['type']."' and type_no='".$row2['trans_no']."' and account='2000010'";
				db_query($sql2_1,"Failed to update gl_trans.");
			}
}
	
display_notification("Fixing Consignment non_trade tagging is Successful!");
}
start_form();
start_table();
start_row();
//ref_cells('Transaction #:', 'trans_no');
//date_cells('Change date deposited to :', 'date');
end_row();
end_table();
br();
start_row();
submit_center('Add',_("Fix Consignment non-trade."), true, '', 'default');
end_table();
end_form();
end_page();
?>