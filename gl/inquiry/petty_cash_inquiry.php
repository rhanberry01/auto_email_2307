<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without ev en the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/
$page_security = 'SA_DEPOSIT';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Petty Cash Inquiry", false, false, "", $js);
//------------------------------------------------------------------------------------------------
$approved_id = find_submit('approved_id');

function approve_cash_deposit($id,$date_deposit,$sales_date,$amount,$date_approved,$approver_id,$approver_name,$remarks) {
$sql = "INSERT INTO ".TB_PREF."cash_deposited
(cd_c_id,cd_date_deposited,cd_sales_date,cd_amount,cd_date_approved,cd_approver_id,cd_approver_name,cd_remarks) 
VALUES('".$id."','".$date_deposit."', '".$sales_date."', '".$amount."','".date2sql($date_approved)."','".$approver_id."','".$approver_name."','".$remarks."')";
//db_query($sql,"Cash Deposit Approval could not be saved.");
//display_error($sql);
}
										//ACTIONS UPON APPROVAL.
										if ($approved_id != -1)
										{
										global $Ajax;
										//display_error($_POST['remarks'.$approved_id]);
										$date_deposit=$_POST['date_deposit'.$approved_id];
										$sales_date=$_POST['sales_date'.$approved_id];
										$amount=$_POST['amount'.$approved_id];
										$date_approved=$_POST['date_approved'.$approved_id];
										$approver_id=$_SESSION["wa_current_user"]->user;
										$u = get_user($_SESSION["wa_current_user"]->user);
										$approver_real_name = $u['real_name'];
										$remarks=$_POST['remarks'.$approved_id];
										
										$inserttype=ST_CASHDEPOSIT;
										//TO CHECK IF TRANSNO IS VOIDED ALREADY
										$void_entry = get_voided_entry($inserttype, $$approved_id);
										db_fetch_row($void_entry);
										if ($void_entry>0)
										{
										
										$sqldelrefs = "Delete FROM ".TB_PREF."voided WHERE type = '$inserttype' and id='$$approved_id'";
											db_query($sqldelrefs);
											//display_error($sqldelrefs);			
										}
										approve_cash_deposit($approved_id,$date_deposit,$sales_date,$amount,$date_approved,$approver_id,$approver_real_name,$remarks);
										
										$sql = "UPDATE ".TB_PREF."cash_deposit_details
										SET c_approved=1 WHERE c_id='$approved_id'";
										$result=db_query($sql);

										$sqlcash_account="select cash_account, cash_in_bank from ".TB_PREF."sales_gl_accounts";
										$result_cash_account=db_query($sqlcash_account);
										while ($accountrow = db_fetch($result_cash_account))
										{
										$cash_in_transit=$accountrow["cash_account"];
										$cash_in_bank=$accountrow["cash_in_bank"];	
										}
										
										if (($amount!='') or ($amount!=0)) { 
										add_gl_trans(ST_CASHDEPOSIT, $approved_id, $date_approved, $cash_in_bank, 0, 0, $remarks, $amount, null, 0);
										add_gl_trans(ST_CASHDEPOSIT, $approved_id, $date_approved, $cash_in_transit, 0, 0, $remarks, -$amount, null, 0);
										}
										
										$Ajax->activate('table_');	
										}

start_form();
start_table();
start_row();
	date_cells(_("Date:"), 'date_', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null); 
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);

$date_from =date2sql($_POST['date_']);
$date_to = date2sql($_POST['TransToDate']);
				
display_heading("Petty Cash Summary from ".$_POST['date_']."  to  ".$_POST['TransToDate']);
br();
br();
div_start('table_');
start_table($table_style2.' width=60%');
$th = array('Trans#','Date Approved',_("Received By"), 'Approved By','Description','Amount',"");
table_header($th);

$sql="select * from ".TB_PREF."petty_cash_header where pc_processed='0'";
$result=db_query($sql);
//display_error($sql);

while($row = db_fetch($result))
{
start_row();
	//date_cells('','date_approved'.$row['pc_id']);
	label_cell($row['pc_id']);
	label_cell(sql2date($row['pc_date']));
	label_cell($row['pc_received_by']);
	label_cell($row['pc_approved_by_name']);
	label_cell($row['pc_remarks']);
	label_cell("<font color=#880000><b>".number_format2(abs($row['pc_amount']),2)."<b></font>",'align=right');
	label_cell(pager_link(_('Process'), "/gl/petty_cash_replenishment.php?trans_no=" .$row['pc_id']));
	//hidden('amount'.$row['c_id'],$row['c_amount']);
	//$submit='approved_id'.$row['c_id'];
	//submit_cells($submit, 'Process', "align=center", true, true,'ok.gif');
end_row();
//$t_amount+=$row['c_amount'];
}	
end_table(1);
div_end();
end_form();
end_page();
?>