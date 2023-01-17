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
page(_($help_context = "Fix Other Income Date OR"), false, false, "", $js);

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
	
$sql= "select 'sri' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_trans_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from 0_other_income_payment_header as oh 
left join 0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_trans_date>='2017-01-01' and oh.bd_trans_date<='2017-03-31'
and od.bd_det_date>='2017-01-01' and od.bd_det_date<='2017-03-31'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_trans_no";
$res=db_query($sql);
//display_error($sql);

while($row = db_fetch($res))
{
	
$sql1= "SELECT * FROM cash_deposit.0_srs_or where or_no='".$row['bd_official_receipt']."'";
$res1=db_query($sql1);
//display_error($sql1);

while($row1 = db_fetch($res1))
{
	$sql2 = "UPDATE 0_other_income_payment_header SET bd_or_date='".date2sql($row1['or_date'])."'
	WHERE bd_trans_no='".$row['bd_trans_no']."'";
	//display_error($sql2);
	db_query($sql2,"Failed to update other income header.");
}
	
}
	
display_notification("Fixing Other Income OR is Successful!");
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
submit_center('Add',_("Fix Other Income OR"), true, '', 'default');
end_table();
end_form();
end_page();
?>