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
	
$sql= "SELECT * FROM 0_other_income_payment_header
where bd_trans_date>='2016-01-01'
and bd_trans_date<='2016-12-31'
and bd_receipt_type='RF'
and bd_official_receipt!='' and bd_official_receipt!=0";
$res=db_query($sql);
//display_error($sql);

while($row = db_fetch($res))
{	

	$bd_gross=$row['bd_gross_amount'];
	$bd_oi=round($bd_gross /1.12,2);
	$bd_vat=round($bd_gross-$bd_oi,2);
	
	$memo_="OR# ".$row['bd_official_receipt'];
	
	$sql2 = "UPDATE 0_other_income_payment_header SET bd_oi='$bd_oi', bd_vat='$bd_vat'
	WHERE bd_trans_no='".$row['bd_trans_no']."'";
	//display_error($sql2);
	db_query($sql2,"Failed to update other income header.");
	
	$sql3 = "UPDATE 0_other_income_payment_details SET bd_det_ov='$bd_vat'
	WHERE bd_det_trans_no='".$row['bd_trans_no']."'";
	//display_error($sql3);
	db_query($sql3,"Failed to update other income header.");
	
	$sql4 = "UPDATE 0_gl_trans SET amount = -$bd_oi WHERE type = 2 AND type_no = ".$row['bd_trans_no']." AND amount!=0 AND account!='1010' and amount<0";
	//display_error($sql4);
	db_query($sql4,"Failed to update other income header.");
	
	$sql5 = "DELETE FROM 0_gl_trans WHERE type = 2 AND type_no = ".$row['bd_trans_no']." AND account=2310";
	db_query($sql5,'failed to delete wrong transfer in GL');
	
	add_gl_trans(2, $row['bd_trans_no'], sql2date($row['bd_trans_date']), 2310, 0, 0, $memo_, -$bd_vat);
	
	

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