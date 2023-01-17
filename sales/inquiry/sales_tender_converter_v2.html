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

page(_($help_context = "Non-Cash Remittance Details"), false, false, "", $js);

//----------------------------------------------------------------------------------

$convert_id = find_submit('convert_selected');
if ($convert_id != -1)
{
global $Ajax;

$card_type=$_POST['card_type'.$convert_id];
$account_no=$_POST['account_no'.$convert_id];
$approval_no=$_POST['approval_no'.$convert_id];
$new_acquiring_bank_id=$_POST['new_acquiring_bank_id'.$convert_id];
$tran_date=$_POST['tran_date'.$convert_id];
$amount=$_POST['amount'.$convert_id];
$cashier_id=$_POST['cashier_id'.$convert_id];
$terminal_no=$_POST['terminal_no'.$convert_id];


if ($card_type=='0') {
$new_card_tender_type='014';
$bank_header=$selected_bank."Debit";
}
else {
$new_card_tender_type='013';
$bank_header=$selected_bank."Credit";
}

$new_acquiring_bank=get_acquiring_bank_col($new_acquiring_bank_id, 'acquiring_bank');


$sqlwholesale3="select * from ".TB_PREF."wholesale_counter";
$wholesaleresult3=db_query($sqlwholesale3);
$ws_cashier3 = array();
while($ws_row3 = db_fetch($wholesaleresult3))
{
	$ws_cashier3[]=$ws_row3['counter'];
}


$sqlwholesale33="select * from ".TB_PREF."special_counter";
$wholesaleresult33=db_query($sqlwholesale33);
$ws_cashier33 = array();
while($ws_row33 = db_fetch($wholesaleresult33))
{
	$ws_cashier33[]=$ws_row33['counter'];
}






$sqlremittance="SELECT * FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date='$tran_date'
and final_remittance=1
";


if (count($ws_cashier3) > 0){
	$sqlremittance.= " and terminal_nos IN(".implode(',',$ws_cashier3).")";
	//display_error($sqlremittance);
	$ws=1;
}

else if (count($ws_cashier33) > 0){
		$sqlremittance.= " and terminal_nos IN(".implode(',',$ws_cashier3).")";
		display_error($sqlremittance);
		$spc=1;
}
	else{
		$sqlremittance.=" and cashier_id = ".db_escape($cashier_id)."";
		
	}







$result_remit=db_query_rs($sqlremittance);
display_error($sqlremittance);
while ($row = db_fetch($result_remit))
{
$remittance_id=$row['remittance_id'];
$cash=$row['total_cash'];
$credit=$row['total_credit_card'];
$debit=$row['total_debit_card'];
$suki=$row['total_suki_card'];
$srsgc=$row['total_srs_gc'];
$gc=$row['total_gc'];
$terms=$row['total_terms'];
$total_ddkita=$row['total_ddkita'];
$total_rice_promo=$row['total_rice_promo'];
$evoucher=$row['total_e_voucher'];
$total_receivable=$row['total_receivable'];
$atd=$row['total_atd'];
$st=$row['total_stock_transfer'];

$others=$row['total_others'];
$reading=$row['readtotal_receivableng'];
$over_short=$row['over_short'];
}
//display_error($remittance_id);
										


												if ($remittance_id != "0" or $remittance_id != "") {
													
													//display_error($remittance_id);
													
													if ($new_card_tender_type=="")
													{
													display_error(_("Please Select new tender type."));
													return false;
													}													
													
													if ($new_acquiring_bank=="")
													{
													display_error(_("Please select bank."));
													return false;
													}
													
														begin_transaction();
													
														$sql = "INSERT INTO ".CR_DB.TB_PREF."other_trans (
														remittance_id,
														transaction_date,
														trans_no,
														account_no,
														tender_type,
														approval_no,
														trans_amount,
														card_desc,
														verified)
														VALUES (
														".db_escape($remittance_id).",
														'$tran_date',
														".db_escape($convert_id).",
														".db_escape($account_no).",
														".db_escape($new_card_tender_type).",
														".db_escape($approval_no).",
														".db_escape($amount).",
														".db_escape($new_acquiring_bank).",'0')";
														
														//display_error($sql);
														db_query_rs($sql,'failed to insert other remittance');
														
														
														$dc_date_paid='0000-00-00';

														$sql = "INSERT INTO ".TB_PREF."sales_debit_credit (dc_remittance_id,dc_remittance_date,dc_transaction_date,
														dc_trans_no,dc_account_no,dc_tender_type,dc_approval_no,dc_trans_amount,dc_card_desc,dc_date_paid,dc_over_payment,dc_charge_back,processed,paid)
														VALUES (".db_escape($remittance_id).", '$tran_date', '$tran_date', ".db_escape($convert_id).",".db_escape($account_no).",".db_escape($new_card_tender_type).",
														".db_escape($approval_no).",".db_escape($amount).",".db_escape($new_acquiring_bank).",'".$dc_date_paid."','0','0','0','0')";
														//display_error($sql);		
														db_query($sql,'failed to insert other remittance');
		
														
														
														$sql1 = "UPDATE ".CR_DB.TB_PREF."remittance SET";
														if ($new_card_tender_type=='013') {
														$to_new_trans_amount=$credit;
														$totype="total_credit_card";
														$amount_add=($to_new_trans_amount+$amount);
														$sql1.=" $totype=".db_escape($amount_add)."";
														}
														
														if ($new_card_tender_type=='014') {
														$to_new_trans_amount=$debit;
														$totype="total_debit_card";
														$amount_add=($to_new_trans_amount+$amount);
														$sql1.=" $totype=".db_escape($amount_add)."";
														}
														$sql1.=" WHERE remittance_id = ".db_escape($remittance_id)."";
														//display_error($sql1);
														
														db_query_rs($sql1,"selected could not be updated");
														
														$sqlremittancesummary2="SELECT * from ".CR_DB.TB_PREF."remittance_summary WHERE remittance_ids like ".db_escape('%'.$remittance_id.'%')."
														 AND r_summary_date='$tran_date'";
														
															if($ws==1){
																$sqlremittancesummary2.= " and terminal_nos IN(".implode(',',$ws_cashier3).")";
																
															}
															else if ($spc==1){
																
																$sqlremittancesummary2.= " and terminal_nos IN(".implode(',',$ws_cashier33).")";
																
															}
															else{
																$sqlremittancesummary2="AND cashier_id='$cashier_id'";
															}
														
														
														$result_remit2=db_query_rs($sqlremittancesummary2);
														//display_error($sqlremittancesummary2);
														while ($row2 = db_fetch($result_remit2))
														{
														$cash2=$row2['total_cash'];
														$credit2=$row2['total_credit_card'];
														$debit2=$row2['total_debit_card'];
														$suki2=$row2['total_suki_card'];
														$srsgc2=$row2['total_srs_gc'];
														$gc2=$row2['total_gc'];
														$terms2=$row2['total_terms'];
														$total_ddkita2=$row2['total_ddkita'];
														$total_rice_promo2=$row2['total_rice_promo'];
														$evoucher2=$row2['total_e_voucher'];
														$total_receivable2=$row2['total_receivable'];
														$atd2=$row2['total_atd'];
														$st2=$row2['total_stock_transfer'];
														$others2=$row2['total_others'];
														$reading2=$row2['reading'];
														$over_short2=$row2['over_short'];
														}
														
														
														
														$sql2 = "UPDATE ".CR_DB.TB_PREF."remittance_summary SET";

														if ($new_card_tender_type=='013') {
														$to_new_trans_amount2=$credit2;
														$totype="total_credit_card";
														$amount_add=($to_new_trans_amount2+$amount);
														$sql2.=" $totype=".db_escape($amount_add)."";
														}

														if ($new_card_tender_type=='014') {
														$to_new_trans_amount2=$debit2;
														$totype="total_debit_card";
														$amount_add=($to_new_trans_amount2+$amount);
														$sql2.=" $totype=".db_escape($amount_add)."";
														}
																												
														$sql2.=" WHERE remittance_ids like ".db_escape('%'.$remittance_id.'%')." AND r_summary_date='$tran_date'";
														
														
															if($ws==1){
																$sql2.= " and terminal_nos IN(".implode(',',$ws_cashier3).")";
																
															}
															else if ($spc==1){
																
																$sql2.= " and terminal_nos IN(".implode(',',$ws_cashier33).")";
																
															}
															else{
																$sql2="AND cashier_id='$cashier_id'";
															}
														
														//display_error($sql2);
														db_query_rs($sql2,"selected could not be updated");
														
														
														
													$reading2=$row['reading'];
													$over_short2=$row['over_short'];
													$grand_total=$cash2+$credit2+$debit2+$suki2+$srsgc2+$gc2+$terms2+$totalgc2+$evoucher2+$total_ddkita2+$total_rice_promo2+$atd2+$st2+$total_receivable2+$others2;
													$difference = $grand_total - $reading2;
													$sql3 = "UPDATE ".CR_DB.TB_PREF."remittance_summary SET over_short=".db_escape($difference)."
													WHERE remittance_ids like ".db_escape('%'.$remittance_id.'%')." AND r_summary_date='$tran_date'";
													

															if($ws==1){
																$sql3.= " and terminal_nos IN(".implode(',',$ws_cashier3).")";
																
															}
															else if ($spc==1){
																
																$sql3.= " and terminal_nos IN(".implode(',',$ws_cashier33).")";
																
															}
															else{
																$sql3="AND cashier_id='$cashier_id'";
															}
															display_error($sql3);
													db_query_rs($sql3,"selected could not be updated");
															
															
													
													commit_transaction();
														
												}
												else{
													display_error("No Remittance Found.");
												}
												
												

display_notification(_("The Transaction has been Converted."));


}



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
$total_rice_promo=$row['total_rice_promo'];	
$total_ddkita=$row['total_ddkita'];	
$evoucher=$row['total_e_voucher'];	
$total_receivable=$row['total_receivable'];	
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
if ($orig_tender_type=='117'){
$from_old_trans_amount=$terms;
$fromtype="total_terms";
}
if ($orig_tender_type=='018'){
$from_old_trans_amount=$evoucher;
$fromtype="total_e_voucher";
}
if ($orig_tender_type=='017'){
$from_old_trans_amount=$total_ddkita;
$fromtype="total_ddkita";
}
if ($orig_tender_type=='019'){
$from_old_trans_amount=$total_rice_promo;
$fromtype="total_rice_promo";
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

if ($tender_type=='117') {
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

if ($tender_type=='017') {
$to_new_trans_amount=$total_ddkita;
$totype="total_ddkita";
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
$amount=($total_ddkita-$orig_amount)+$new_trans_amount;
$sql.=" total_ddkita=".db_escape($amount)."";
}
}

if ($tender_type=='019') {
$to_new_trans_amount=$total_rice_promo;
$totype="total_rice_promo";
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
$amount=($total_rice_promo-$orig_amount)+$new_trans_amount;
$sql.=" total_rice_promo=".db_escape($amount)."";
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
$total_ddkita=$row['total_ddkita'];
$total_rice_promo=$row['total_rice_promo'];
$total_receivable=$row['total_receivable'];	
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
if ($orig_tender_type=='117'){
$from_old_trans_amount=$terms;
$fromtype="total_terms";
}
if ($orig_tender_type=='018'){
$from_old_trans_amount=$evoucher;
$fromtype="total_e_voucher";
}	

if ($orig_tender_type=='017'){
$from_old_trans_amount=$total_ddkita;
$fromtype="total_ddkita";
}	

if ($orig_tender_type=='019'){
$from_old_trans_amount=$total_rice_promo;
$fromtype="total_rice_promo";
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

if ($tender_type=='117') {
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


if ($tender_type=='017') {
$to_new_trans_amount=$total_ddkita;
$totype="total_ddkita";
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
$amount=($total_ddkita-$orig_amount)+$new_trans_amount;
$sql.=" total_ddkita=".db_escape($amount)."";
}
}		


if ($tender_type=='019') {
$to_new_trans_amount=$total_rice_promo;
$totype="total_rice_promo";
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
$amount=($total_rice_promo-$orig_amount)+$new_trans_amount;
$sql.=" total_rice_promo=".db_escape($amount)."";
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
$total_ddkita=$row['total_ddkita'];
$total_rice_promo=$row['total_rice_promo'];
$total_receivable=$row['total_receivable'];	
$atd=$row['total_atd'];
$st=$row['total_stock_transfer'];
$others=$row['total_others'];
$reading=$row['reading'];
$over_short=$row['over_short'];
$grand_total=$cash+$credit+$debit+$suki+$srsgc+$gc+$terms+$totalgc+$evoucher+$total_ddkita+$total_rice_promo+$total_receivable+$atd+$st+$others;
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
//tender_list_cells("Transaction Type:", 'trans_type',  $myrow2["tender_type"]); 
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

$sql="SELECT fp.TransactionNo, fp.TenderCode,fp.[Description],fp.Amount,fp.UserID,fp.TerminalNo,
CAST(fp.LogDate as date) as Logdate ,m.name
FROM [FinishedPayments] as fp 
left join MarkUsers as m
on fp.UserID=m.userid
where fp.LogDate='$date_from'
and fp.TenderCode='000'
";

if ($cashier_id2!='') $sql .= " AND fp.UserID='$cashier_id2'";

// SELECT TransactionNo, TenderCode,[Description], Amount, UserID
// , TerminalNo,CAST(LogDate as date) as Logdate FROM [srimu].[dbo].[FinishedPayments]
  // where  LogDate='2016-09-01'
  // and TenderCode='000'

//display_error($sql);
$res=ms_db_query($sql);

start_table("$table_style width=80%");
$th = array(_('Transaction Date'),_('Cashier Name'),_('TransactionNo'), _('From Tender Type'), _('To  Tender Type'), 'AccountNo','ApprovalNo','Bank','Amount',"");
//verified_control_column($th);
table_header($th);
$k = 0; //row colour counter
$previous_tender = "";
while($myrow = mssql_fetch_array($res))
{
if($previous_tender!=$myrow['name'])
{
alt_table_row_color($k);
label_cell($myrow['name'].":",'colspan=10 class=tableheader2');
end_row();
alt_table_row_color($k);
$previous_tender=$myrow['name'];
} 
$tendercode=$myrow["TenderCode"];
hidden("tran_date".$myrow['TransactionNo'],$myrow['Logdate']);
//hidden("amount".$myrow['TransactionNo'],$myrow['Amount']);
hidden("cashier_id".$myrow['TransactionNo'],$myrow['UserID']);
hidden("terminal_no".$myrow['TransactionNo'],$myrow['TerminalNo']);
label_cell(sql2date($myrow["Logdate"]));
label_cell($myrow["name"]);
label_cell($myrow["TransactionNo"]);
label_cell($myrow["Description"]);
	
	
yesno_list_cells('',  'card_type'.$myrow['TransactionNo'], '',_("Credit"), _("Debit"));
text_cells(null, 'account_no'.$myrow['TransactionNo'],'',8,60);
text_cells(null, 'approval_no'.$myrow['TransactionNo'],'',8,60);
acquiring_bank_list_cells('', 'new_acquiring_bank_id'.$myrow['TransactionNo'],null);
// label_cell(number_format2($myrow["Amount"],2));
text_cells('',"amount".$myrow['TransactionNo'],$myrow["Amount"],'12');
	$submit='convert_selected'.$myrow['TransactionNo'];
	submit_cells($submit, 'Convert', "align=center", true, true,'ok.gif');

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