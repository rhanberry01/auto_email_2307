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
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/ui/cust_credit_debit.inc");

$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Petty Cash Replenishment");
page($_SESSION['page_title'], false, false,'', $js);
//$exp_type=find_submit('exp_type');
$approve_add = find_submit('selected_id');
$selected_add = find_submit('selected_add');
$approve_delete = find_submit('selected_del');
$create_breakdown = find_submit('selected_breakdown');

//-------------------------------------------------------------------------------------------------
start_form();
start_table();
start_row();
	get_petty_cash_user_list_cells('Created By:','created_by');
	yesno_list_cells('Replenished:', 'yes_no', '', 'Yes', 'No');
	date_cells(_("From:"), 'TransAfterDate', '', null);
	date_cells(_("To:"), 'TransToDate', '', null);
	get_employee_list_cells('Employee:', 'a_emp_cashier_id');
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(2);
end_form();
start_form();

function insert_petty_cash_details($selected_add,$pc_id,$pcd_date,$pcd_payee,$pcd_purpose,$exp_type,$pcd_ref,$pcd_tin,$tax_type,$prepared_by_id,$prepared_by_name)
{
global $path_to_root;
$sql = "INSERT INTO ".TB_PREF."petty_cash_details (pc_id,pcd_date,pcd_payee,pcd_purpose,pcd_gl_type,
pcd_ref,pcd_tin,pcd_amount,pcd_tax,pcd_tax_type,pcd_prepared_by_id,pcd_prepared_by_name,pcd_approved_by_id,pcd_approved_by_name,pcd_replenished,pcd_date_replenished,pcd_wid_breakdown,pcd_breakdown,pcd_head_id)
VALUES ('$pc_id','".date2sql($pcd_date)."',".db_escape($pcd_payee).",".db_escape($pcd_purpose).",'$exp_type','$pcd_ref','$pcd_tin','".input_num('pcd_amount2'.$selected_add)."','".input_num('tax_amount2')."','$tax_type','$prepared_by_id',".db_escape($prepared_by_name).",'','','0','0000-00-00','0','1','$selected_add')";
db_query($sql,'failed to insert petty cash details');
$remittance_id = db_insert_id();

$sql = "UPDATE ".TB_PREF."petty_cash_details 
		SET pcd_wid_breakdown = '1'
		WHERE pcd_id = '$selected_add'";
db_query($sql);
return $remittance_id;
}

function clear_petty_cash_details($selected_id,$pcd_gl_type,$pcd_payee,$pcd_purpose,$pcd_ref,$pcd_tin,$tax_type,$approved_by_id,$approved_by_name)	
{	
$date_replenished=today();

	global $path_to_root;
	 $sql = "UPDATE ".TB_PREF."petty_cash_details 
	 SET pcd_payee = ".db_escape($pcd_payee).",
	 pcd_purpose =".db_escape($pcd_purpose).",
	 pcd_gl_type = '$pcd_gl_type',
	 pcd_ref = ".db_escape($pcd_ref).",
	 pcd_tin = ".db_escape($pcd_tin).",
	 pcd_amount='".input_num('pcd_amount'.$selected_id)."',
	 pcd_tax='".input_num('tax_amount'.$selected_id)."',
	 pcd_tax_type = '$tax_type',
	 pcd_approved_by_id = '$approved_by_id',
	 pcd_approved_by_name=".db_escape($approved_by_name).",
	 pcd_replenished='1',
	 pcd_date_replenished='".date2sql($date_replenished)."'
	WHERE pcd_id = '$selected_id'";
db_query($sql);

	 $sql2 = "UPDATE ".TB_PREF."petty_cash_details 
	 SET pcd_approved_by_id = '$approved_by_id',
	 pcd_approved_by_name=".db_escape($approved_by_name).",
	 pcd_replenished='1',
	 pcd_date_replenished='".date2sql($date_replenished)."'
	WHERE pcd_head_id = '$selected_id'";
db_query($sql2);
}

function get_expenditure_name($type)
{
$sql="select exp_type_name from ".TB_PREF."expenditure_type where exp_id='$type'";
$result=db_query($sql);
$row=db_fetch($result);
$name=$row['exp_type_name'];
return $name;
}

function delete_line($id)
{
$sql="SELECT pc_id FROM ".TB_PREF."petty_cash_details WHERE pcd_id=".db_escape($id)."";
$result=db_query($sql);
$row=db_fetch($result);
$pc_id=$row['pc_id'];

$sql="DELETE FROM ".TB_PREF."petty_cash_details WHERE pcd_id=".db_escape($id)." or  pcd_head_id=".db_escape($id)."";
db_query($sql, "could not delete line item");


$sql="SELECT * FROM ".TB_PREF."petty_cash_details WHERE pc_id='$pc_id'";
$result=db_query($sql);
$count=db_num_rows($result);

if ($count<=0) {
$sql="DELETE FROM ".TB_PREF."petty_cash_header WHERE pc_id='$pc_id'";
db_query($sql);
}

}

if (isset($_GET['AddedDep']))
{
$trans_no = $_GET['AddedDep'];
//$trans_type = ST_BANKDEPOSIT;
display_notification_centered(_("Petty Cash $trans_no has been processed."));
display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL Postings for this Deposit")));
hyperlink_params("$path_to_root/gl/inquiry/petty_cash_inquiry.php", _("Process Another Petty Cash"));
display_footer_exit();
}

if ($approve_delete != -1) {
global $Ajax;
delete_line($approve_delete);
$Ajax->activate('items_line');
}

if ($create_breakdown != -1) {
global $Ajax;
$Ajax->activate('breakdown_line');
}

if ($selected_add != -1) 
{
global $Ajax;

if (($_POST['exp_type2'.$selected_add]=='') or ($_POST['exp_type2'.$selected_add]==0)) {
		display_error(_("Expenditure type cannot be empty. Please select one."));
		set_focus($_POST['exp_type2'.$selected_add]);
		return false;
}

if (($_POST['pcd_amount2'.$selected_add]=='') or ($_POST['pcd_amount2'.$selected_add]==0)) {
		display_error(_("The Amount cannot be empty or equal to zero (0)."));
		set_focus($_POST['pcd_amount2'.$selected_add]);
		return false;
}

if ($_POST['pcd_purpose2'.$selected_add]=='') {
		display_error(_("Purpose of expense cannot be empty."));
		set_focus($_POST['pcd_purpose2'.$selected_add]);
		return false;
}

	$approver_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver_real_name = $u['real_name'];
	
	$pc_id=$_POST['pc_id2'.$selected_add];
	$pcd_date=$_POST['pcd_date2'.$selected_add];
	$exp_type=$_POST['exp_type2'.$selected_add];
	$pcd_payee=$_POST['pcd_payee2'.$selected_add];
	$pcd_purpose=$_POST['pcd_purpose2'.$selected_add];
	$pcd_ref=$_POST['pcd_ref2'.$selected_add];
	$pcd_tin=$_POST['pcd_tin2'.$selected_add];
	$tax_type=$_POST['tax_type2'.$selected_add];
insert_petty_cash_details($selected_add,$pc_id,$pcd_date,$pcd_payee,$pcd_purpose,$exp_type,$pcd_ref,$pcd_tin,$tax_type+0,$approver_id,$approver_real_name);

$Ajax->activate('items_line');
//div_start('breakdown_line');
}

if ($approve_add != -1) 
{
	
global $Ajax;

	$sqlx = "SELECT * FROM ".TB_PREF."petty_cash_details 
	WHERE pcd_replenished='1' AND pcd_id = '$approve_add'";
	$resultx=db_query($sqlx);
	$count=db_num_rows($resultx);
	
	if($count>0){
		display_error('double clicked.');
		exit();
	}


if (($_POST['exp_type'.$approve_add]=='') or ($_POST['exp_type'.$approve_add]==0)) {
		display_error(_("Expenditure type cannot be empty. Please select one."));
		set_focus($_POST['exp_type'.$approve_add]);
		return false;
}

if (($_POST['pcd_amount'.$approve_add]=='') or ($_POST['pcd_amount'.$approve_add]==0)) {
		display_error(_("The Amount cannot be empty or equal to zero (0)."));
		set_focus($_POST['pcd_amount'.$approve_add]);
		return false;
}

if ($_POST['pcd_purpose'.$approve_add]=='') {
		display_error(_("Purpose of expense cannot be empty."));
		set_focus($_POST['pcd_purpose'.$approve_add]);
		return false;
}

	$approver_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver_real_name = $u['real_name'];
	
$exp_type=$_POST['exp_type'.$approve_add];
$tax_amount=$_POST['tax_amount'.$approve_add];
$pcd_amount=$_POST['pcd_amount'.$approve_add];
$pcd_payee=$_POST['pcd_payee'.$approve_add];
$pcd_purpose=$_POST['pcd_purpose'.$approve_add];
$pcd_ref=$_POST['pcd_ref'.$approve_add];
$pcd_tin=$_POST['pcd_tin'.$approve_add];
$tax_type=$_POST['tax_type'.$approve_add];
$pcd_date=$_POST['pcd_date'.$approve_add];
clear_petty_cash_details($approve_add,$exp_type,$pcd_payee,$pcd_purpose,$pcd_ref,$pcd_tin,$tax_type,$approver_id,$approver_real_name);

$date_approved=Today();

add_gl_trans_temp(ST_PETTYCASH, $approve_add, $pcd_date, 2000010, 0, 0, $remarks,-$pcd_amount, null, 0, $pcd_payee);
if ($tax_amount!=0) {
add_gl_trans_temp(ST_PETTYCASH, $approve_add, $pcd_date, $tax_type, 0, 0, $remarks,$tax_amount, null, 0, $person_id);
}
add_gl_trans_temp(ST_PETTYCASH, $approve_add, $pcd_date, $exp_type, 0, 0, $remarks,$pcd_amount-$tax_amount, null, 0, $person_id);
$desc='Cleared';
add_audit_trail(ST_PETTYCASH, $approve_add, $date_approved,$desc);


//$Ajax->activate('items_line');
}

//START OF FORM
start_form();
global $table_style2, $Ajax;

$date_after = date2sql($_POST['TransAfterDate']);
$date_before = date2sql($_POST['TransToDate']);

hidden('TransAfterDate2',$_POST['TransAfterDate']);
hidden('TransToDate2',$_POST['TransToDate']);

display_heading("Petty Cash List");	
br();	
//$result = db_query($sql);
div_start('items_line');
start_table($table_style2.' width=99%');
if ($_POST['yes_no']==0) {
$th = array("CreatedBy","TransNo","SEQ#","Date","Employee Name",'REF#','Payee','Purpose of Expense','Type','Amount','TIN#','Tax Type','Input Tax','','','');
}
else {
$th = array("CreatedBy","TransNo","SEQ#","Date","Employee Name",'REF#','Payee','Purpose of Expense','Amount','Type','TIN#','Tax Type','Input Tax');
}
table_header($th);			

										if (!isset($_POST['Finish'])) {	
										if ($create_breakdown != -1 or $selected_add != -1 or $_POST['hidden_id2']!='') {
										global $Ajax;
										//$_POST['tax_amount2']='';
										$hidden_pc_id=$_POST['hidden_id2'];
										$sql="select pcd.*,pch.pc_employee_id,pch.pc_employee_name from ".TB_PREF."petty_cash_details as pcd
										left join ".TB_PREF."petty_cash_header as pch
										on pcd.pc_id=pch.pc_id and pch.pc_approved='1'";
										
										if($create_breakdown != -1){
										//display_error('x1');
										$sql.=" where pcd.pcd_id='$create_breakdown'";
										}
										else if ($approve_delete != -1){
										//display_error('x2');
										$sql.=" where pcd.pcd_id='$hidden_pc_id'";
										}
										else {
										//display_error('x3');
										$sql.=" where pcd.pcd_id='$selected_add'";
										}
										//display_error($sql);
										$result = db_query($sql);
										$row=db_fetch($result);
									
										//ORANGE for insert breakdown
										div_start('breakdown_line');
										start_row("class='overduebg'");
										label_cell($row['pcd_prepared_by_name']);
										label_cell("<font color=blue>".$row['pcd_id']."</font>");
										//hidden('pcd_id'.$row['pcd_id'],$row['pcd_id']);
										label_cell("<font color=blue>".$row['pc_id']."</font>");
										hidden('pc_id2'.$row['pcd_id'],$row['pc_id']);
										label_cell("<font color=blue>".sql2date($row['pcd_date'])."</font>");
										hidden('pcd_date2'.$row['pcd_id'],sql2date($row['pcd_date']));
										label_cell("<font color=blue>".$row['pc_employee_name']."</font>");
										text_cells(null, 'pcd_ref2'.$row['pcd_id'],$row['pcd_ref'],8,60);
										//hidden('pc_employee_id'.$row['pcd_id'],$row['pc_employee_id']);
										text_cells(null, 'pcd_payee2'.$row['pcd_id'],$row['pcd_payee'],18,60);
										text_cells(null, 'pcd_purpose2'.$row['pcd_id'],'',18,60);
										gl_all_accounts_list_cells('', 'exp_type2'.$row['pcd_id'], $row['pcd_gl_type'], false, false, "All Accounts");
										//display_error($tx);
										text_cells(null, 'pcd_amount2'.$row['pcd_id'],'',6,60);
										text_cells(null, 'pcd_tin2'.$row['pcd_id'],$row['pcd_tin'],8,60);
										$tx=number_format2(abs($_POST['tax_amount2']),2);
										tax_gl_list_cells('', 'tax_type2'.$row['pcd_id'], $row['pcd_tax_type'], true, false, "All Accounts");
										text_cells(null, 'tax_amount2',$tx,6,60);
										hidden('hidden_id2',$row['pcd_id']);
										$selected_add_new='selected_add'.$row["pcd_id"];
										submit_cells($selected_add_new, 'Insert Breakdown', "colspan=3 align=center", _('Insert Breakdown'), true);
										$selected_del='selected_del'.$row["pcd_id"];
										end_row();
										div_end();
										}
										}
											
										if ($create_breakdown != -1) {
										//display_error('m1');
										$sql="select pcd.*,pch.pc_employee_id,pch.pc_employee_name from ".TB_PREF."petty_cash_details as pcd
										left join ".TB_PREF."petty_cash_header as pch
										on pcd.pc_id=pch.pc_id";
										$sql.=" where (pcd.pcd_id='$create_breakdown' or pcd.pcd_head_id='$create_breakdown') and pch.pc_approved='1'";
										$sql.=" order by pcd.pcd_id asc";
										//display_error($sql);
										}

										else if ($selected_add != -1) {
										//display_error('m2');
										$sql="select pcd.*,pch.pc_employee_id,pch.pc_employee_name from ".TB_PREF."petty_cash_details as pcd
										left join ".TB_PREF."petty_cash_header as pch
										on pcd.pc_id=pch.pc_id";
										$sql.=" where (pcd.pcd_id='$selected_add' or pcd.pcd_head_id='$selected_add') and pch.pc_approved='1'";
										$sql.=" order by pcd.pcd_id asc";
										//display_error($sql);
										}
										
										else if ($approve_delete != -1) {
										//display_error('m3');
										$hidden_pc_id=$_POST['hidden_id2'];
										$sql="select pcd.*,pch.pc_employee_id,pch.pc_employee_name from ".TB_PREF."petty_cash_details as pcd
										left join ".TB_PREF."petty_cash_header as pch
										on pcd.pc_id=pch.pc_id";
										$sql.=" where (pcd.pcd_id='$hidden_pc_id' or pcd.pcd_head_id='$hidden_pc_id') and pch.pc_approved='1'";
										$sql.=" AND (pcd.pcd_date>='".date2sql($_POST['TransAfterDate2'])."' AND pcd.pcd_date<='".date2sql($_POST['TransToDate2'])."') AND pcd.pcd_replenished='0' and pcd.pcd_breakdown='0' ORDER BY pcd.pcd_date,pc_id,pcd.pcd_head_id ASC";
										//display_error($sql);
										}
										
										else if ($approve_add != -1) {
										$sql="select pcd.*,pch.pc_employee_id,pch.pc_employee_name from ".TB_PREF."petty_cash_details as pcd
										left join ".TB_PREF."petty_cash_header as pch
										on pcd.pc_id=pch.pc_id WHERE pcd.pcd_replenished='0'";
										$sql .= " AND (pcd.pcd_date>='".date2sql($_POST['TransAfterDate2'])."' AND pcd.pcd_date<='".date2sql($_POST['TransToDate2'])."')
										and pch.pc_approved='1' AND pcd.pcd_replenished='0' and pcd.pcd_breakdown='0' ORDER BY pcd.pcd_date,pc_id,pcd.pcd_head_id ASC";
										//display_error($sql);
										$Ajax->activate('items_line');
										}
											
										else {
										$user_id=$_SESSION["wa_current_user"]->user;
										//display_error('m4');
										//$sql = "SELECT * FROM ".TB_PREF."petty_cash_details";
										$sql="select pcd.*,pch.pc_employee_id,pch.pc_employee_name from ".TB_PREF."petty_cash_details as pcd
										left join ".TB_PREF."petty_cash_header as pch
										on pcd.pc_id=pch.pc_id";
											
										$sql .= " WHERE (pcd.pcd_date>='$date_after' AND pcd.pcd_date<='$date_before') and pch.pc_approved='1'";
										
										if ($_POST['a_emp_cashier_id']!='') {
										$sql .= " AND pch.pc_employee_id='".$_POST['a_emp_cashier_id']."'";
										}
										
										if ($_POST['a_type']!='') {
										$sql .= " AND pcd.pcd_gl_type='".$_POST['a_type']."'";
										}
										
										if ($_POST['yes_no']==1) {
										$sql .= " AND pcd.pcd_replenished='1'";
										}
										else {
										$sql .= " AND pcd.pcd_replenished='0' and pcd.pcd_breakdown='0'";
										}
										
										if ($_POST['created_by']!='') {
										$sql.=" and pcd.pcd_prepared_by_id='".$_POST['created_by']."' ";
										}										
										//if ($user_id==1) {
											$sql .= " ORDER BY  pcd.pcd_date,pc_id,pcd.pcd_head_id ASC";	
										// }
										// else{
											// $sql .= " AND pch.pc_approved_by_id=".$user_id." ORDER BY  pcd.pcd_date,pc_id,pcd.pcd_head_id ASC";	
										// }
										
										}
										
										$result = db_query($sql);
										$k = 0; 
										
										//YELLOW for editing
										if ($_POST['yes_no']==0) {
										
													while($row=mysql_fetch_array($result)) 
													{
													start_row();
													if ($row["pcd_wid_breakdown"]!='0')
													{
													start_row("class='inquirybg'");
													}
													else {
													alt_table_row_color($k);
													}
													label_cell($row['pcd_prepared_by_name']);
													label_cell($row['pcd_id']);
													label_cell($row['pc_id']);
													label_cell(sql2date($row['pcd_date']));
													label_cell($row['pc_employee_name']);
													
															if ($selected_add==$row['pcd_id'] or $create_breakdown==$row['pcd_id'] or $hidden_pc_id==$row['pcd_id'])
															{
															//display_error('b1');
															label_cell($row['pcd_ref']);
															label_cell($row['pcd_payee']);
															label_cell($row['pcd_purpose']);
															label_cell('');
															label_cell($row['pcd_amount']);
															label_cell($row['pcd_tin']);
															label_cell($row['pcd_tax']);
															label_cell('',"colspan=4");
															end_row();
															}
															
															else {
															//display_error('b2');
															
															if ($row["pcd_head_id"]!='0')
															{
															label_cell($row['pcd_ref']);
															label_cell($row['pcd_payee']);
															label_cell($row['pcd_purpose']);
															label_cell(get_gl_account_name($row['pcd_gl_type']));
															label_cell($row['pcd_amount']);
															label_cell($row['pcd_tin']);
															if ($row['pcd_tax_type']!='0') {
															label_cell(get_gl_account_name($row['pcd_tax_type']),'nowrap');
															}
															else {
															label_cell('');
															}
															
															label_cell($row['pcd_tax']);
															//label_cell(get_gl_account_name($row['pcd_tax_type']));
															}
															else {
															text_cells(null, 'pcd_ref'.$row['pcd_id'],$row['pcd_ref'],8,60);
															text_cells(null, 'pcd_payee'.$row['pcd_id'],$row['pcd_payee'],18,60);
															text_cells(null, 'pcd_purpose'.$row['pcd_id'],$row['pcd_purpose'],18,60);
															gl_all_accounts_list_cells('', 'exp_type'.$row['pcd_id'], $row['pcd_gl_type'], false, false, "All Accounts");
															text_cells(null, 'pcd_amount'.$row['pcd_id'],$row['pcd_amount'],6,20);
															text_cells(null, 'pcd_tin'.$row['pcd_id'],$row['pcd_tin'],8,60);
															tax_gl_list_cells('', 'tax_type'.$row['pcd_id'], $row['pcd_tax_type'], true, false, "All Accounts");
															text_cells(null, 'tax_amount'.$row['pcd_id'],$row['pcd_tax'],6,60);
															hidden('pcd_date'.$row['pcd_id'],sql2date($row['pcd_date']));
															}
															
															if ($row["pcd_head_id"]!='0')
															{
															$selected_del='selected_del'.$row["pcd_id"];
															submit_cells($selected_del, _("Remove"),"colspan=3",_('Delete'), true,ICON_DELETE);
															}
															else {
														
															$selected='selected_id'.$row["pcd_id"];
															submit_cells($selected, '', "align=center", _('Replenish'), true,'ok.gif');
															$selected_del='selected_del'.$row["pcd_id"];
															submit_cells($selected_del, _(""), "",_('Delete'), true,ICON_DELETE);
															$breakdown='selected_breakdown'.$row["pcd_id"];
															submit_cells($breakdown, _(""), "",_('Create Breakdown'), false,ICON_DOWN);
															}
															end_row();
															$t_tax+=$row['pcd_tax'];
															$t_amount+=$row['pcd_amount'];
															}
													}
										}
										
										else {
													while($row=mysql_fetch_array($result)) 
													{
													start_row();
													if ($row["pcd_wid_breakdown"]!='0')
													{
													start_row("class='inquirybg'");
													}
													else {
													alt_table_row_color($k);
													}
													label_cell($row['pcd_prepared_by_name']);
													label_cell($row['pcd_id']);
													label_cell($row['pc_id']);
													label_cell(sql2date($row['pcd_date']));
													label_cell($row['pc_employee_name']);
													label_cell($row['pcd_ref']);
													label_cell($row['pcd_payee']);
													label_cell($row['pcd_purpose']);
													label_cell(get_gl_account_name($row['pcd_gl_type']));
													label_cell($row['pcd_amount']);
													label_cell($row['pcd_tin']);												
													if ($row['pcd_tax_type']!='0' and $row['pcd_tax_type']!='') {
													label_cell(get_gl_account_name($row['pcd_tax_type']),'nowrap');
													}
													else {
													label_cell('');
													}
													label_cell($row['pcd_tax']);
													end_row();
													$t_tax+=$row['pcd_tax'];
													$t_amount+=$row['pcd_amount'];
													}
										}

										start_row();
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('<b><font color=#880000>TOTAL AMOUNT:</font></b>','align=right');
										label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
										label_cell('');
										label_cell('<b><font color=#880000>TOTAL TAX:</font></b>','align=right');
										label_cell("<font color=#880000><b>".number_format2(abs($t_tax),2)."<b></font>",'align=right');
										if ($_POST['yes_no']==0) {
										label_cell('');
										label_cell('');
										label_cell('');
										}
										end_row();
										end_table();

br();										
if ($create_breakdown != -1 or $selected_add != -1 or $hidden_pc_id!='') {
br(2);
submit_center_first('Finish', _("Finish"),_('Finish'), false, ICON_ADD);
}
															
div_end();				
end_form();
//END OF FORM
end_page();
?>