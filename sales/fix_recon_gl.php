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
page(_($help_context = "Fix Recon"), false, false, "", $js);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	$cvid = $_GET['CV_id'];
	$trans_type = ST_CREDITDEBITDEPOSIT;
   	display_notification_centered( _("CWO has been entered"));
	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this CWO")));
	br();
	display_note(get_cv_view_str($cvid, _("View CV for this CWO")));
   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another CWO"));
	display_footer_exit();
}

function delete_recon_account($trans_no){
	$sql = "DELETE FROM ".TB_PREF."gl_trans WHERE type='62' and type_no='$trans_no'";
	db_query($sql,'failed to delete account.');
	//display_error($sql);
}

function update_acquiring_dduction($trans_no,$bank_card,$card_tender_type){
											$mysql = "select * from  ".TB_PREF."acquiring_banks where acquiring_bank='$bank_card'";
											$res1 = db_query($mysql);
											//display_error($mysql);
											while($myrow = db_fetch($res1))
											{
											$gl_bank_account=$myrow['gl_bank_account'];
											$gl_bank_debit_account=$myrow['gl_bank_debit_account'];
											$gl_mfee_account=$myrow['gl_mfee_account'];
											$gl_wtax_account=$myrow['gl_wtax_account'];
											$cc_fee=$myrow['cc_merchant_fee_percent'];
											$dc_fee=$myrow['dc_merchant_fee_percent'];
											$cc_wt=$myrow['cc_withholding_tax_percent'];
											//$dc_wt=$myrow['dc_withholding_tax_percent'];
											$a_b=$myrow['acquiring_bank'];
											//$iv=$myrow['input_vat'];
											//$ov_fee=$myrow['output_vat'];
											$a_b=$selected_bank;
											}
																					
											if ($card_tender_type=='013') {
											$fee=$cc_fee; 
											$wt=$cc_wt;

											if ($charge_back=='1') {
											$wt=$cc_wt;
											}
											}
											
											if ($card_tender_type=='014') {
											$fee=$dc_fee; 
											$wt=0;

											if ($charge_back=='1') {
											$wt=0;
											}
											}
	
							$sqldc="select * from ".TB_PREF."acquiring_deductions where p_ref_id='$trans_no'";
							$result_dc=db_query($sqldc);

							while ($dc_row = db_fetch($result_dc)){

							$mdr=$dc_row['p_deposited_amount'] * $fee / 100;
							$cwt=$dc_row['p_deposited_amount']  * $wt/ 100;
							$net=$dc_row['p_deposited_amount']-($cwt+$mdr);

							$sql = "UPDATE ".TB_PREF."acquiring_deductions SET p_wtaxpercent='$wt',p_mfeepercent='$fee',p_mfeeamount='$mdr',p_wtaxamount='$cwt', p_net_total='$net' WHERE p_ref_id='$trans_no' and p_id=".$dc_row['p_id']."";
							db_query($sql,'failed to update acquiring_deductions.');
							//display_error($sql);

							}
}
//----------------------------------------------------------------------------------------
if (isset($_POST['Add'])){
	
$sqldc="select * from ".TB_PREF."acquiring_deductions where p_ref_id=".$_POST['trans_no']." LIMIT 1";
//display_error($sqldc);
$result_dc=db_query($sqldc);
$rows=db_fetch($result_dc);
$card_tender_type=$rows['p_tender_type'];
$bank_card=$rows['p_bank_card'];


update_acquiring_dduction($_POST['trans_no'],$bank_card,$card_tender_type);
	
delete_recon_account($_POST['trans_no']);
	

											$mysql = "select * from  ".TB_PREF."acquiring_banks where acquiring_bank='$bank_card'";
											$res1 = db_query($mysql);
											//display_error($mysql);
											while($myrow = db_fetch($res1))
											{
											$gl_bank_account=$myrow['gl_bank_account'];
											$gl_bank_debit_account=$myrow['gl_bank_debit_account'];
											$gl_mfee_account=$myrow['gl_mfee_account'];
											$gl_wtax_account=$myrow['gl_wtax_account'];
											$cc_fee=$myrow['cc_merchant_fee_percent'];
											$dc_fee=$myrow['dc_merchant_fee_percent'];
											$cc_wt=$myrow['cc_withholding_tax_percent'];
											//$dc_wt=$myrow['dc_withholding_tax_percent'];
											$a_b=$myrow['acquiring_bank'];
											//$iv=$myrow['input_vat'];
											//$ov_fee=$myrow['output_vat'];
											$a_b=$selected_bank;
											}
											
											$mysql = "SELECT date_paid,sum(p_deposited_amount) as p_deposited_amount,
											sum(p_mfeeamount) as p_mfeeamount,
											sum(p_wtaxamount) as p_wtaxamount,
											sum(p_net_total) as p_net_total
											FROM 0_acquiring_deductions
											WHERE p_ref_id=".$_POST['trans_no']."
											GROUP BY p_ref_id";
											//display_error($mysql);
											$rest = db_query($mysql);
											$row_amount=db_fetch($rest);
											
											//display_error($row_amount['p_mfeeamount']);
											if ($row_amount['p_net_total']>0){
											add_gl_trans(ST_CREDITDEBITDEPOSIT, $_POST['trans_no'],sql2date($row_amount['date_paid']), $gl_bank_account, 0, 0, $memo, $row_amount['p_net_total'], null, 0);
											}
											if ($row_amount['p_mfeeamount']>0){
											add_gl_trans(ST_CREDITDEBITDEPOSIT, $_POST['trans_no'], sql2date($row_amount['date_paid']), $gl_mfee_account, 0, 0, $memo, $row_amount['p_mfeeamount'], null, 0);
											}
											if ($row_amount['p_wtaxamount']>0){
											add_gl_trans(ST_CREDITDEBITDEPOSIT, $_POST['trans_no'],sql2date($row_amount['date_paid']), $gl_wtax_account, 0, 0, $memo, $row_amount['p_wtaxamount'], null, 0);
											}
											if ($row_amount['p_deposited_amount']>0){
											add_gl_trans(ST_CREDITDEBITDEPOSIT, $_POST['trans_no'],sql2date($row_amount['date_paid']), $gl_bank_debit_account, 0, 0, $memo, -$row_amount['p_deposited_amount'], null, 0);
											}

display_notification("Fixing Recon is Successful!");
}
start_form();
start_table();
start_row();
ref_cells('Transaction #:', 'trans_no');
end_row();
end_table();
br();
start_row();
submit_center('Add',_("Fix Recon"), true, '', 'default');
end_table();
end_form();
end_page();
?>