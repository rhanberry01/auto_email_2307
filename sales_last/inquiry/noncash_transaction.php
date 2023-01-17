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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
simple_page_mode(false);
$js = "";
if ($use_popup_windows)
$js .= get_js_open_window(900, 600);
if ($use_date_picker)
$js .= get_js_date_picker();

page(_($help_context = "Non-Cash Remittance Details"), false, false, "", $js);

//----------------------------------------------------------------------------------
function write_acquiring_bank($selected,$tender_type,$approval_no,$trans_amount,$card_desc)
{
begin_transaction();
if($selected!=''){
$sql = "UPDATE ".CR_DB.TB_PREF."other_trans SET
tender_type = ".db_escape($tender_type).",
approval_no = ".db_escape($approval_no).",
trans_amount = ".db_escape($trans_amount).",
card_desc = ".db_escape($card_desc)."
WHERE id = ".db_escape($selected);
db_query_rs($sql,"selected could not be updated");
//display_error($sql);
}
commit_transaction();
}

function  update_remittance_amount($remittance_id,$tender_type,$new_trans_amount,$orig_amount,$orig_tender_type)
{
begin_transaction();
$sqlremittance="SELECT * FROM ".CR_DB.TB_PREF."remittance WHERE remittance_id = ".db_escape($remittance_id)."";
$result_remit=db_query_rs($sqlremittance);
// display_error($sqlremittance);
while ($row = db_fetch($result_remit))
{
$credit=$row['total_credit_card'];
$debit=$row['total_debit_card'];
$suki=$row['total_suki_card'];
$srsgc=$row['total_srs_gc'];
$gc=$row['total_gc'];
$terms=$row['total_terms'];
$evoucher=$row['total_e_voucher'];	
}
if ($orig_tender_type=='013'){
$from_old_trans_amount=$credit;
$fromtype="total_credit_card";
}
if ($orig_tender_type=='014'){
$from_old_trans_amount=$debit;
$fromtype="total_debit_card";
}	
if ($orig_tender_type=='004'){
$from_old_trans_amount=$suki;
$fromtype="total_suki_card";
}	
if ($orig_tender_type=='016'){
$from_old_trans_amount=$srsgc;
$fromtype="total_srs_gc";
}
if ($orig_tender_type=='001'){
$from_old_trans_amount=$gc;
$fromtype="total_gc";
}
if ($orig_tender_type=='017'){
$from_old_trans_amount=$terms;
$fromtype="total_terms";
}
if ($orig_tender_type=='018'){
$from_old_trans_amount=$evoucher;
$fromtype="total_e_voucher";
}
$sql = "UPDATE ".CR_DB.TB_PREF."remittance SET";

if ($tender_type=='013') {
$to_new_trans_amount=$credit;
$totype="total_credit_card";
if ($orig_tender_type!=$tender_type){
$amount_subtract=($from_old_trans_amount-$orig_amount);
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=($to_new_trans_amount+$new_trans_amount);
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($credit-$orig_amount)+$new_trans_amount;
$sql.=" total_credit_card=".db_escape($amount)."";
}
}

if ($tender_type=='014') {
$to_new_trans_amount=$debit;
$totype="total_debit_card";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($debit-$orig_amount)+$new_trans_amount;
$sql.=" total_debit_card=".db_escape($amount)."";
}
}
if ($tender_type=='004') {
$to_new_trans_amount=$suki;
$totype="total_suki_card";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($suki-$orig_amount)+$new_trans_amount;
$sql.=" total_suki_card=".db_escape($amount)."";
}
}

if ($tender_type=='016') {
$to_new_trans_amount=$srsgc;
$totype="total_srs_gc";
if ($orig_tender_type!=$tender_type )
{
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1)
{
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($srsgc-$orig_amount)+$new_trans_amount;
$sql.=" total_srs_gc=".db_escape($amount)."";
}
}

if ($tender_type=='001') {
$to_new_trans_amount=$gc;
$totype="total_gc";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1)
{
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($gc-$orig_amount)+$new_trans_amount;
$sql.=" total_gc=".db_escape($amount)."";
}
}

if ($tender_type=='017') {
$to_new_trans_amount=$terms;
$totype="total_terms";

if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;	
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($terms-$orig_amount)+$new_trans_amount;
$sql.=" total_terms=".db_escape($amount)."";
}
}
if ($tender_type=='018') {
$to_new_trans_amount=$evoucher;
$totype="total_e_voucher";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($evoucher-$orig_amount)+$new_trans_amount;
$sql.=" total_e_voucher=".db_escape($amount)."";
}
}
$sql.=" WHERE remittance_id = ".db_escape($remittance_id)."";
db_query_rs($sql,"selected could not be updated");
commit_transaction();
}

function  update_remittance_summary_amount($remittance_id,$tender_type,$new_trans_amount,$orig_amount,$cashier_id,$remittance_date,$orig_tender_type)
{
begin_transaction();
$sqlremittancesummary="SELECT * from ".CR_DB.TB_PREF."remittance_summary WHERE remittance_ids like ".db_escape('%'.$remittance_id.'%')."
AND cashier_id='$cashier_id' AND r_summary_date='$remittance_date'";
$result_remit=db_query_rs($sqlremittancesummary);
//display_error($sqlremittancesummary);
while ($row = db_fetch($result_remit))
{
$cash=$row['total_cash'];
$credit=$row['total_credit_card'];
$debit=$row['total_debit_card'];
$suki=$row['total_suki_card'];
$srsgc=$row['total_srs_gc'];
$gc=$row['total_gc'];
$terms=$row['total_terms'];
$evoucher=$row['total_e_voucher'];
$atd=$row['total_atd'];
$st=$row['total_stock_transfer'];
$others=$row['total_others'];
$reading=$row['reading'];
$over_short=$row['over_short'];
}
if ($orig_tender_type=='013'){
$from_old_trans_amount=$credit;
$fromtype="total_credit_card";
}
if ($orig_tender_type=='014'){
$from_old_trans_amount=$debit;
$fromtype="total_debit_card";
}	
if ($orig_tender_type=='004'){
$from_old_trans_amount=$suki;
$fromtype="total_suki_card";
}	
if ($orig_tender_type=='016'){
$from_old_trans_amount=$srsgc;
$fromtype="total_srs_gc";
}
if ($orig_tender_type=='001'){
$from_old_trans_amount=$gc;
$fromtype="total_gc";
}
if ($orig_tender_type=='017'){
$from_old_trans_amount=$terms;
$fromtype="total_terms";
}
if ($orig_tender_type=='018'){
$from_old_trans_amount=$evoucher;
$fromtype="total_e_voucher";
}	
$sql = "UPDATE ".CR_DB.TB_PREF."remittance_summary SET";

if ($tender_type=='013') {
$to_new_trans_amount=$credit;
$totype="total_credit_card";
if ($orig_tender_type!=$tender_type )
{
$amount_subtract=($from_old_trans_amount-$orig_amount);
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=($to_new_trans_amount+$new_trans_amount);
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($credit-$orig_amount)+$new_trans_amount;
$sql.=" total_credit_card=".db_escape($amount)."";
}
}

if ($tender_type=='014') {
$to_new_trans_amount=$debit;
$totype="total_debit_card";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";

$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($debit-$orig_amount)+$new_trans_amount;
$sql.=" total_debit_card=".db_escape($amount)."";
}
}	 

if ($tender_type=='004') {
$to_new_trans_amount=$suki;
$totype="total_suki_card";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($suki-$orig_amount)+$new_trans_amount;
$sql.=" total_suki_card=".db_escape($amount)."";
}
}

if ($tender_type=='016') {
$to_new_trans_amount=$srsgc;
$totype="total_srs_gc";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($srsgc-$orig_amount)+$new_trans_amount;
$sql.=" total_srs_gc=".db_escape($amount)."";
}
}
if ($tender_type=='001') {
$to_new_trans_amount=$gc;
$totype="total_gc";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($gc-$orig_amount)+$new_trans_amount;
$sql.=" total_gc=".db_escape($amount)."";
}
}

if ($tender_type=='017') {
$to_new_trans_amount=$terms;
$totype="total_terms";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($terms-$orig_amount)+$new_trans_amount;
$sql.=" total_terms=".db_escape($amount)."";
}
}

if ($tender_type=='018') {
$to_new_trans_amount=$evoucher;
$totype="total_e_voucher";
if ($orig_tender_type!=$tender_type ){
$amount_subtract=$from_old_trans_amount-$orig_amount;
if($amount_subtract<1){
$amount_subtract=0;
}
$sql.=" $fromtype=".db_escape($amount_subtract).",";
$amount_add=$to_new_trans_amount+$new_trans_amount;
$sql.=" $totype=".db_escape($amount_add)."";
}
else{
$amount=($evoucher-$orig_amount)+$new_trans_amount;
$sql.=" total_e_voucher=".db_escape($amount)."";
}
}		
$sql.=" WHERE remittance_ids like ".db_escape('%'.$remittance_id.'%')." AND cashier_id='$cashier_id' AND r_summary_date='$remittance_date'";
db_query_rs($sql,"selected could not be updated");
//display_error($sql);
commit_transaction();
}

function  update_new_over_short($remittance_id,$tender_type,$new_trans_amount,$orig_amount,$cashier_id,$remittance_date)
{
begin_transaction();
$sqlover_short="SELECT * from ".CR_DB.TB_PREF."remittance_summary WHERE remittance_ids like ".db_escape('%'.$remittance_id.'%')."
AND cashier_id='$cashier_id' AND r_summary_date='$remittance_date'";
$result_remit=db_query_rs($sqlover_short);
//display_error($sqlover_short);
while ($row = db_fetch($result_remit))
{
$cash=$row['total_cash'];
$credit=$row['total_credit_card'];
$debit=$row['total_debit_card'];
$suki=$row['total_suki_card'];
$srsgc=$row['total_srs_gc'];
$gc=$row['total_gc'];
$terms=$row['total_terms'];
$evoucher=$row['total_e_voucher'];
$atd=$row['total_atd'];
$st=$row['total_stock_transfer'];
$others=$row['total_others'];
$reading=$row['reading'];
$over_short=$row['over_short'];
$grand_total=$cash+$credit+$debit+$suki+$srsgc+$gc+$terms+$totalgc+$evoucher+$atd+$st+$others;
$difference = $grand_total - $reading;
$sql = "UPDATE ".CR_DB.TB_PREF."remittance_summary SET over_short=".db_escape($difference)."
WHERE remittance_ids like ".db_escape('%'.$remittance_id.'%')." AND cashier_id='$cashier_id' AND r_summary_date='$remittance_date'";
}
db_query_rs($sql,"selected could not be updated");
//display_error($sql);
commit_transaction();
}

if ($Mode=='UPDATE_ITEM') {
begin_transaction();
//initialise no input errors assumed initially before we test
$input_error = 0;
if ($input_error !=1) {
write_acquiring_bank($selected_id,$_POST['tender_type'],$_POST['approval_no'],$_POST['trans_amount'],$_POST['card_desc']);
update_remittance_amount($_POST['remittance_id'],$_POST['tender_type'],$_POST['trans_amount'], $_POST['orig_amount'], $_POST['orig_tender_type']);
update_remittance_summary_amount($_POST['remittance_id'],$_POST['tender_type'],$_POST['trans_amount'], $_POST['orig_amount'],
$_POST['cashier_id'],$_POST['remittance_date'],$_POST['orig_tender_type']);
update_new_over_short($_POST['remittance_id'],$_POST['tender_type'],$_POST['trans_amount'], $_POST['orig_amount'],$_POST['cashier_id'],
$_POST['remittance_date']);
if($selected_id != '')
display_notification(_('Selected  has been updated'));
$Mode = 'RESET';
}
commit_transaction();
}
//------------------------START DISPLAYING DATA TO TABLE-------------------------------------------
start_form();
start_table();
start_row();
get_cashier_list_cells('Cashier:', 'cashier_id2');
tender_list_cells("Transaction Type:", 'trans_type',  $myrow2["tender_type"]); 
date_cells(_("Date:"), 'TransAfterDate', '', null,-1);
//date_cells(_("To:"), 'TransToDate', '', null, 1);
submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);
$date_from = date2sql($_POST['TransAfterDate']);
$trans_type =$_POST['trans_type'];
$cashier_id2 =$_POST['cashier_id2'];
//$date_to = date2sql($_POST['TransToDate']);
if ($Mode == 'RESET')
{
$selected_id = '';
$sav = isset($_POST['show_inactive']);
unset($_POST);
$_POST['show_inactive'] = $sav;
}
$result = get_all_noncash(check_value('show_inactive'),$date_from,$trans_type,$cashier_id2);
start_table("$table_style width=80%");
$th = array(_('Remittance Date'),_('Cashier Name'),_('TransactionNo'), _('Remittance ID'),_('Transaction Type'), _('Card Description'), 'ApprovalNo','Amount',"Edit");
//verified_control_column($th);
table_header($th);
$k = 0; //row colour counter
$previous_tender = "";
while ($myrow = db_fetch($result))
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
alt_table_row_color($k);
label_cell($myrow["remittance_date"]);
label_cell($myrow["cashier_name"]);
label_cell($myrow["trans_no"]);
label_cell($myrow["remittance_id"]);
label_cell($myrow["Description"]);
label_cell($myrow["card_desc"]);
label_cell($myrow["approval_no"]);
label_cell($myrow["trans_amount"]);

if ($myrow["verified"]=='0'){
edit_button_cell("Edit".$myrow["id"], _("Edit"));
}
else {
label_cell('-','align=center');
}
end_row();
}

//verified_control_row($th); //CHECKBOX
end_table(1);
//------------------------END OF DISPLAYING DATA TO TABLE-------------------------------------------

//-------------------------EDIT FORM QUERY---------------------------------------
start_table($table_style2);

if ($selected_id != '') {
begin_transaction();
if ($Mode == 'Edit') {
// //editing an existing item category
$result2 = edit_noncash($selected_id); //FROM sales/includes/sales_db.inc
while ($myrow2 = db_fetch($result2)) //for displaying data to edit
{
$_POST['tender_type'] = $myrow2["tender_type"];
$_POST['card_desc']  = $myrow2["card_desc"];
$_POST['approval_no']  = $myrow2["approval_no"];
$_POST['trans_amount']  = $myrow2["trans_amount"];
$remittance_id = $myrow2["remittance_id"];
$orig_amount = $myrow2["trans_amount"];
$cashier_id = $myrow2["cashier_id"];
$r_date=$myrow2["remittance_date"];
$orig_tender_type = $myrow2["tender_type"];
}
}
hidden('selected_id', $selected_id);
commit_transaction();
}
// FORM
tender_list_row("Tender Type:", 'tender_type',  $myrow2["tender_type"]); 
text_row(_("Card Description:"), 'card_desc');
text_row(_("ApprovalNo:"), 'approval_no');
text_row(_("Amount:"), 'trans_amount');
hidden('remittance_id', $remittance_id);
hidden('orig_amount', $orig_amount);
hidden('cashier_id', $cashier_id);
hidden('remittance_date', $r_date);
hidden('orig_tender_type', $orig_tender_type);
end_table(1);
submit_update_center($selected_id == '', '', 'both');
end_form();
end_page();
?>