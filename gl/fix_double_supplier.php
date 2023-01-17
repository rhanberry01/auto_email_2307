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
page(_($help_context = "Fix Double Supplier"), false, false, "", $js);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	$cvid = $_GET['CV_id'];
	$trans_type = ST_SUPPINVOICE;
   	display_notification_centered( _("CWO has been entered"));
	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this CWO")));
	br();
	display_note(get_cv_view_str($cvid, _("View CV for this CWO")));
   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another CWO"));
	display_footer_exit();
}

function get_orig_supplier_id($group_id){
	$sql = "SELECT * FROM ".TB_PREF."double_supplier WHERE temp_group_id='$group_id' and is_orig='1'";
	display_error($sql);	
	$res=db_query($sql);
	$row=db_fetch($res);
	return $row['supplier_id'];
}

function update_sup_id_from_supp_trans($fake_supp_id,$orig_supp_id){
	$sql = "UPDATE ".TB_PREF."supp_trans SET supplier_id='$orig_supp_id' WHERE supplier_id='$fake_supp_id'";
	db_query($sql,'failed to update supp_trans.');
	display_error($sql);
}

function update_sup_id_from_gl_trans($fake_supp_id,$orig_supp_id){
	$sql = "UPDATE ".TB_PREF."gl_trans SET person_id='$orig_supp_id' WHERE person_id='$fake_supp_id'";
	db_query($sql,'failed to update gl_trans.');
	display_error($sql);
}

function update_sup_id_from_gl_trans_temp($fake_supp_id,$orig_supp_id){
	$sql = "UPDATE ".TB_PREF."gl_trans_temp SET person_id='$orig_supp_id' WHERE person_id='$fake_supp_id'";
	db_query($sql,'failed to update gl_trans_temp.');
	display_error($sql);
}

function update_sup_id_from_cv_header($fake_supp_id,$orig_supp_id){
	$sql = "UPDATE ".TB_PREF."cv_header SET person_id='$orig_supp_id' WHERE person_id='$fake_supp_id'";
	db_query($sql,'failed to update cv_header.');
	display_error($sql);
}

function update_sup_id_from_purch_orders($fake_supp_id,$orig_supp_id){
	$sql = "UPDATE ".TB_PREF."purch_orders SET supplier_id='$orig_supp_id' WHERE supplier_id='$fake_supp_id'";
	db_query($sql,'failed to update purch_orders.');
	display_error($sql);
}

function update_sup_id_from_grn_batch($fake_supp_id,$orig_supp_id){
	$sql = "UPDATE ".TB_PREF."grn_batch SET supplier_id='$orig_supp_id' WHERE supplier_id='$fake_supp_id'";
	db_query($sql,'failed to update grn_batch.');
	display_error($sql);
}

function update_sup_id_from_cwo_header($fake_supp_id,$orig_supp_id){
	$sql = "UPDATE ".TB_PREF."cwo_header SET c_sup_id='$orig_supp_id' WHERE c_sup_id='$fake_supp_id'";
	db_query($sql,'failed to update cwo_header.');
	display_error($sql);
}

function delete_fake_supplier($fake_supp_id){
	$sql = "DELETE FROM ".TB_PREF."suppliers WHERE supplier_id='$fake_supp_id'";
	db_query($sql,'failed to delete supplier.');
	display_error($sql);
}

//----------------------------------------------------------------------------------------
if (isset($_POST['Add'])){
	$sql = "SELECT * FROM ".TB_PREF."double_supplier WHERE is_orig='0' ORDER BY temp_group_id";
	$result=db_query($sql);

	while($row=mysql_fetch_array($result)) 
	{
	$orig_supp_id=get_orig_supplier_id($row['temp_group_id']);

	if ($orig_supp_id!='' and $row['supplier_id']!='') {
		update_sup_id_from_supp_trans($row['supplier_id'],$orig_supp_id);
		update_sup_id_from_gl_trans($row['supplier_id'],$orig_supp_id);
		update_sup_id_from_gl_trans_temp($row['supplier_id'],$orig_supp_id);
		update_sup_id_from_cv_header($row['supplier_id'],$orig_supp_id);
		update_sup_id_from_purch_orders($row['supplier_id'],$orig_supp_id);
		update_sup_id_from_grn_batch($row['supplier_id'],$orig_supp_id);
		update_sup_id_from_cwo_header($row['supplier_id'],$orig_supp_id);
		//delete_fake_supplier($row['supplier_id']);
	}
}

display_notification("Fixing Double Supplier is Successfull!");
}
start_form();
start_table();
submit_center('Add',_("Fix Double Supplier"), true, '', 'default');
end_table();
end_form();
end_page();
?>