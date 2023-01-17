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
$page_security = 'SA_DEPOSIT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
simple_page_mode(false);
$js = "";
if ($use_popup_windows)
$js .= get_js_open_window(900, 600);
if ($use_date_picker)
$js .= get_js_date_picker();

page(_($help_context = "POS Transaction Details"), false, false, "", $js);

//------------------------START DISPLAYING DATA TO TABLE-------------------------------------------
start_form();
start_table();
start_row();
// get_cashier_list_cells('Cashier:', 'cashier_id2');
acquiring_bank_list_cells(' Acquiring Bank:', 'acquiring_bank_id',null);
ref_cells('ApprovalNo#:', 'approval_no');
ref_cells('Account#:', 'account_no');
ref_cells('Amount:', 'amount');
tender_list_cells("Transaction Type:", 'trans_type',  $myrow2["tender_type"]); 
date_cells(_("Date:"), 'TransAfterDate', '', null,1);
date_cells(_("To:"), 'TransToDate', '', null, 1);
submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);
$date_from = date2sql($_POST['TransAfterDate']);
$trans_type =$_POST['trans_type'];
$cashier_id2 =$_POST['cashier_id2'];
$date_to = date2sql($_POST['TransToDate']);
$approval_no = $_POST['approval_no'];
$account_no = $_POST['account_no'];
$amount = $_POST['amount'];
$acquiring_bank_id=$_POST['acquiring_bank_id'];
$selected_bank=get_selected_acquiring_banks($acquiring_bank_id);

if ($Mode == 'RESET')
{
$selected_id = '';
$sav = isset($_POST['show_inactive']);
unset($_POST);
$_POST['show_inactive'] = $sav;
}
$result = get_all_noncash(check_value('show_inactive'),$date_from,$trans_type,$cashier_id2);

$sql="SELECT fp.*,m.name from [FinishedPayments] as fp
left join [MarkUsers] as m
ON fp.UserID=m.userid

where fp.LogDate>='$date_from' and fp.LogDate<='$date_to' 
";

if($selected_bank!=''){
	$sql.=" and fp.Remarks like '%$selected_bank%'";
}
if($approval_no!=''){
	$sql.=" and fp.ApprovalNo like '%$approval_no%'";
}
if($account_no!=''){
	$sql.=" and fp.AccountNo like '%$account_no%'";
}
if($amount!=''){
	$sql.=" and fp.Amount like '%$amount%'";
}


if($trans_type!=''){
	$sql.=" and fp.TenderCode like '%$trans_type%'";
}

$res=ms_db_query($sql);
//display_error($sql);

start_table("$table_style width=80%");
$th = array(_('Transaction Date'),_('Cashier Name'),_('TerminalNo'),_('TransactionNo'), _('Account#'),_('Transaction Type'), _('Card Description'), 'ApprovalNo','Amount');
//verified_control_column($th);
table_header($th);
$k = 0; //row colour counter
$previous_tender = "";
while($myrow = mssql_fetch_array($res))
{
if($previous_tender!=$myrow['Description'])
{
alt_table_row_color($k);
label_cell($myrow['Description'].":",'colspan=9 class=tableheader2');
end_row();
alt_table_row_color($k);
$previous_tender=$myrow['Description'];
} 
$tendercode=$myrow["tender_type"];

if ((strpos($myrow["card_desc"]," ")!==false and ($tendercode=='014' or $tendercode=='013')) or $myrow["card_desc"]=='') {
start_row("class='inquirybg'");
}
else {
alt_table_row_color($k);
}


label_cell($myrow["LogDate"]);
label_cell($myrow["name"]);
label_cell($myrow["TerminalNo"]);
label_cell($myrow["TransactionNo"]);
label_cell($myrow["AccountNo"]);
label_cell($myrow["Description"]);
if ($myrow["Remarks"]=='') {
label_cell("<font color='RED'>".'NOT INSERTED'.$myrow["Remarks"]."</font>");
}
else if (strpos($myrow["Remarks"]," ")!==false and ($tendercode=='014' or $tendercode=='013')) {
label_cell("<font color='red'>".$myrow["Remarks"]."</font>");
}
else {
label_cell($myrow["Remarks"]);
}
label_cell($myrow["ApprovalNo"]);
label_cell($myrow["Amount"]);

// if ($myrow["verified"]=='0'){
// edit_button_cell("Edit".$myrow["id"], _("Edit"));
// }
// else {
// label_cell('-','align=center');
// }
end_row();

$t_amount+=$myrow["Amount"];
}

start_row();
label_cell('');
label_cell('');
label_cell('');
label_cell('');	
label_cell('');
label_cell('');
label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
label_cell('');
end_row();


//verified_control_row($th); //CHECKBOX
end_table(1);
//------------------------END OF DISPLAYING DATA TO TABLE-------------------------------------------
end_form();
end_page();
?>