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

echo "<script language='JavaScript' type='text/javascript' src='../js/journal_non_trade_suggest_purpose.js'></script>";
echo "<script language='JavaScript' type='text/javascript' src='../js/journal_non_trade_suggest_payee.js'></script>";

$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Journal Entry Non-trade");
page($_SESSION['page_title'], false, false,'', $js);

$approve_add = find_submit('selected_id');
$approve_delete = find_submit('selected_del');
//--------------------------------------------------------------------------------------------------

function insert_journal_non_trade_header($date_,$emp_id,$prepared_by_id,$prepared_by_name,$memo)
{
global $path_to_root;

		// $sql = "SELECT e.employee_id as emp_no, CONCAT(e.emp_firstname, ' ', e.emp_lastname)as  emp_name, ej.agency_id as emp_agency_id, a.agency_name as emp_agency_name
		// FROM ".TB_PREF."hr_employee as e
		// left join 0_hr_emp_job_info as ej
		// on e.emp_number=ej.emp_id
		// left join ".TB_PREF."hr_agency as a
		// on ej.agency_id=a.id
		// WHERE e.employee_id REGEXP '^[0-9]+$' 
		// AND ej.emp_status NOT IN (6,7)
		// and e.employee_id=".db_escape($emp_id)."";	
		
		$sql = "SELECT e.employee_id as emp_no, CONCAT(e.emp_firstname, ' ', e.emp_lastname)as  emp_name, ej.agency_id as emp_agency_id, a.agency_name as emp_agency_name
		FROM orange.hs_hr_employee as e
		left join orange.hr_emp_job_info as ej
		on e.emp_number=ej.emp_id
		left join orange.hs_hr_agency as a
		on ej.agency_id=a.id
		WHERE e.employee_id
		AND ej.emp_status NOT IN (6,7)
		and e.employee_id=".db_escape($emp_id)."";
		
		//REGEXP '^[0-9]+$' 
		
		//display_error($sql);
		//$sql = "select * from ".TB_PREF."employee_list WHERE emp_no =".db_escape($emp_id)."";	
		$res = db_query($sql);
		$row=db_fetch($res);
		$emp_agency_id=$row['emp_agency_id'];
		$emp_agency_name=$row['emp_agency_name'];
		$employee_name=$row['emp_name'];
		$employee_id=$row['emp_no'];

$sql = "INSERT INTO ".TB_PREF."journal_non_trade_header(pc_date,pc_employee_id,pc_employee_name,pc_approved_by_id,pc_approved_by_name,pc_memo)
VALUES ('".date2sql($date_)."','$emp_id',".db_escape($employee_name).",'$prepared_by_id',".db_escape($prepared_by_name).",".db_escape($memo).")";
//display_error($sql);	
db_query($sql,'failed to insert journal');

$remittance_id = db_insert_id();
return $remittance_id;
}

function insert_journal_non_trade_details($pc_id,$date_,$payee,$purpose,$gl_type,$ref,$tin,$tax,$tax_type,$prepared_by_id,$prepared_by_name)
{
global $path_to_root;

if ($tax_type=='')
{
$tax_type=0;
}
$sql = "INSERT INTO ".TB_PREF."journal_non_trade_details (pc_id,pcd_date_created,pcd_date,pcd_payee,pcd_purpose,pcd_gl_type,
pcd_ref,pcd_tin,pcd_amount,pcd_tax,pcd_tax_type,pcd_prepared_by_id,pcd_prepared_by_name,pcd_approved_by_id,pcd_approved_by_name,pcd_replenished,pcd_date_replenished,pcd_wid_breakdown,pcd_breakdown,pcd_head_id)
VALUES ('$pc_id','".date2sql(Today())."','".date2sql($date_)."',".db_escape($payee).",".db_escape($purpose).",'$gl_type','$ref','$tin','".input_num('amount')."','".input_num('tax')."','$tax_type','$prepared_by_id',".db_escape($prepared_by_name).",'','','0','0000-00-00','0','0','0')";
//display_error($sql);	
db_query($sql,'failed to insert journal details');

$remittance_id = db_insert_id();
return $remittance_id;
}

function delete_line($id)
{
$sql="DELETE FROM ".TB_PREF."journal_non_trade_details WHERE pcd_id=".db_escape($id)."";
//display_error($sql);
db_query($sql, "could not delete line item");
}
//-----------------------------------------------------------------------------
if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_BANKTRANSFER;

   	display_notification_centered( _("Journal Sequence #$trans_no has been approved."));
	hyperlink_no_params($path_to_root."/gl/journal_non_trade.php?NewJournal=Yes",_("Enter &Another Journal"));
	display_footer_exit();
}

if (isset($_POST['Cancel'])){
$sqldelrefs = "Delete pch.*, pcd.* FROM ".TB_PREF."journal_non_trade_header as pch
left join ".TB_PREF."journal_non_trade_details as pcd
on pch.pc_id=pcd.pc_id
WHERE pch.pc_id = '".$_POST['seq']."'";
db_query($sqldelrefs);
//display_error($sqldelrefs);	 
meta_forward($_SERVER['PHP_SELF'], "NewJournal=Yes");
}

if (isset($_POST['Approve'])){
	$sql = "UPDATE ".TB_PREF."journal_non_trade_header
	SET pc_approved = '1'
	WHERE pc_id = '".$_POST['seq']."'";
	db_query($sql);
	//display_error($sql);
	
	meta_forward($_SERVER['PHP_SELF'], "AddedID=".$_POST['seq']."");
}

if ($approve_add != -1) 
{
global $Ajax;

if (($_POST['amount']=='') or ($_POST['amount']==0)) {
		display_error(_("The Amount cannot be empty or equal to zero (0)."));
		set_focus('amount');
		return false;
}

if (($_POST['rec_type']=='') or ($_POST['rec_type']==0)) {
		display_error(_("GL Account Type cannot be empty. Please select one."));
		set_focus('rec_type');
		return false;
}

if ($_POST['payee']=='') {
		display_error(_("Payee cannot be empty."));
		set_focus('payee');
		return false;
}
if ($_POST['purpose']=='') {
		display_error(_("Purpose of Expense cannot be empty."));
		set_focus('purpose');
		return false;
}

	$approver_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver_real_name = $u['real_name'];
	
insert_journal_non_trade_details($_POST['pc_id'],$_POST['date'],$_POST['payee'],$_POST['purpose'],$_POST['rec_type'],$_POST['ref'],$_POST['tin'],$_POST['tax'],$_POST['tax_type'],$approver_id,$approver_real_name);

$Ajax->activate('items_line');
$_POST['date']=$_POST['payee']=$_POST['purpose']=$_POST['ref']=$_POST['tin']=$_POST['amount']=$_POST['tax']=$_POST['tax_type']=$approver_id=$approver_real_name='';
}

if (isset($_POST['save'])) 
{
if (($_POST['a_emp_id']=='') or ($_POST['a_emp_id']==0)) {
		display_error(_("Employee cannot be empty. Go back and select one."));
		set_focus('a_emp_id');
		hyperlink_no_params($path_to_root."/gl/journal_non_trade.php?NewJournal=Yes",_("Go Back"));
		return false;
}
	$approver_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver_real_name = $u['real_name'];
	
$trans_num=insert_journal_non_trade_header($_POST['date_'],$_POST['a_emp_id'],$approver_id,$approver_real_name,$_POST['memo_']);
header("location:../gl/journal_non_trade.php?trans_no=$trans_num");
}

if ($approve_delete != -1) {
global $Ajax;
delete_line($approve_delete);
$Ajax->activate('items_line');
}

if (list_updated('tax_type')){
global $Ajax;
$amount=input_num('amount');
$tax=($amount/1.12)-$amount;
$tx=number_format2(abs($tax),2);
$_POST['tax']=$tx;
$Ajax->activate('tax');
}

//------------------------------------------------------------------------------

start_form();
if (isset($_GET['NewJournal'])) {
$sql="select max(pc_id) as pc_id from ".TB_PREF."journal_non_trade_header";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$pc_id=$row['pc_id'];
//display_error($pc_id);

$sql="select * from ".TB_PREF."journal_non_trade_details where pc_id='$pc_id'";
//display_error($sql);
$result_id_details=db_query($sql);
$count=db_num_rows($result_id_details);
//display_error($count);

if ($count<=0) {
$sql="delete from ".TB_PREF."journal_non_trade_header where pc_id='$pc_id'";
$result=db_query($sql);
//display_error($sql);
}

	div_start('pmt_header');
	start_outer_table("width=65% $table_style2"); // outer table
	table_section(1);
		if (isset($_GET['NewJournal']))
		$sqlid_details="select max(pc_id)+1 as pc_id from ".TB_PREF."journal_non_trade_header";
		
		$result_id_details=db_query($sqlid_details);
		
		while ($ot_id_row = db_fetch($result_id_details))
		{
		$trans_no=$ot_id_row['pc_id'];
		}	
		
	if (($trans_no=='') or ($trans_no=='0')) {
	$trans_no=$trans_no+1;
	}
	
	date_row(_("<b>Date:</b> "), 'date_', '', true, 0, 0, 0, null, false);
	hidden('seq',$trans_no);
	table_section(2, "38%");
	get_employee_list_cells('<b>Employee:</b>', 'a_emp_id');
	table_section(3, "38%");
	textarea_row(_("Memo:"), 'memo_', null, 28, 1);
	end_outer_table(1); // outer table
	div_end();
	
	br();	
	submit_center('save', 'Add New Journal', "align=center", true, false,'ok.gif');
}
	
		else {
		div_start('pmt_header');
		start_outer_table("width=65% $table_style2"); // outer table
		table_section(1);
		if (isset($_GET['trans_no']))
		$sqlid_details="select * from ".TB_PREF."journal_non_trade_header where pc_id='".$_GET['trans_no']."'";

		$result_id_details=db_query($sqlid_details);

		while ($ot_id_row = db_fetch($result_id_details))
		{
		$trans_no=$ot_id_row['pc_id'];
		$pc_date=$ot_id_row['pc_date'];
		//$pc_amount=$ot_id_row['pc_amount'];
		$pc_employee_name=$ot_id_row['pc_employee_name'];
		$pc_approved_by_name=$ot_id_row['pc_approved_by_name'];
		$pc_memo=$ot_id_row['pc_memo'];
		}	

		//ref_row(_("Sequence #:"), 'seq', '', $trans_no);
		label_row("<b>Sequence #:</b> ",$trans_no);
		label_row("<b>Date:</b> ",sql2date($pc_date));
		hidden('seq',$trans_no);
		table_section(2, "40%");
		label_row("<b>Employee:</b> ",$pc_employee_name);
		//label_row("<b>Amount:</b> ",number_format2($pc_amount));
		table_section(3, "40%");
		label_row(_("<b>Memo:</b> "),$pc_memo);
		label_row("<b>Prepared By:</b> ",$pc_approved_by_name);
		end_outer_table(1); // outer table
		div_end();
		}
		
$action = $_SERVER['PHP_SELF'];
echo "<form method='POST' id='suggestSearch' action='$action'>";
if (!$_GET['NewJournal']) {
//display_error($_GET['trans_no']);
div_start('items_line');
br();	
br();
//START OF FORM
global $table_style2, $Ajax;
display_heading("Journal Non-trade List");	
br();	
//$result = db_query($sql);
start_table($table_style2.' width=90%');
$th = array("Date",'REF#','Payee','Purpose of Expense(s)','GL Type','Amount','TIN#','Tax Type','Input Tax','');
table_header($th);
start_row();
date_cells('', 'date', '', null);
text_cells('','ref','',9);
//get_employee_list_cells('', 'emp_id');
//text_cells('','payee','',18);
text_cells('', 'payee', '', 18,'','','','',"id='payee' alt='Search Payee' onkeyup='SuggestPayee();' autocomplete='off'");
echo "<div id='layer1'></div><br>";
text_cells('', 'purpose', '', 20,'','','','',"id='purpose' alt='Search Purpose' onkeyup='SuggestPurpose();' autocomplete='off'");
//echo "<input type='text' id='purpose' alt='Search Criteria' onkeyup='SuggestPurpose();' autocomplete='off'/>";
echo "<div id='layer2'></div><br>";
//get_expenditure_type_list_cells('','rec_type','',true);
gl_all_accounts_list_cells('', 'rec_type', null, false, false, "All Accounts");
amount_cells_ex(null, 'amount',8,'','','','',2);
text_cells('','tin','',9);
tax_gl_list_cells('', 'tax_type', null, true, true, "All Accounts");
amount_cells_ex(null, 'tax',8,'','','','',2);
hidden('pc_id',$_GET['trans_no']);
hidden('p_amount',$_POST['p_amount']);
$selected='selected_id'.'1';
submit_cells($selected, _("Add"), "colspan=2",_('Add Journal'), true, ICON_ADD);			
											
										if ($_GET['trans_no']) {
										$sql = "select * from ".TB_PREF."journal_non_trade_details where pc_id='".$_GET['trans_no']."'";
										}
										else {
										$sql = "select * from ".TB_PREF."journal_non_trade_details where pc_id='".$_POST['pc_id']."'";
										}
										
										//display_error($sql);
										$result = db_query($sql);
										$k = 0; 
										while($row=mysql_fetch_array($result)) 
										{
										start_row();
										alt_table_row_color($k);
										label_cell(sql2date($row['pcd_date']));
										label_cell($row['pcd_ref']);
										//label_cell($row['pcd_employee_name']);
										label_cell($row['pcd_payee']);
										label_cell($row['pcd_purpose']);
										label_cell(get_gl_account_name($row['pcd_gl_type']),'nowrap');
										label_cell(number_format2(abs($row['pcd_amount']),2),'align=right');
										label_cell($row['pcd_tin']);
										if ($row['pcd_tax_type']!='0') {
										label_cell(get_gl_account_name($row['pcd_tax_type']),'nowrap');
										}
										else {
										label_cell('');
										}
										label_cell(number_format2(abs($row['pcd_tax']),2),'align=right');
										$selected_del='selected_del'.$row["pcd_id"];
										// if (($approver_id==$row['a_prepared_by_id']) and (sql2date($row['pcd_date'])==Today()))
										// {
										submit_cells($selected_del, _("Delete"), "colspan=2",_('Delete Particular'), true,ICON_DELETE);	
										// }
										// else {
										// label_cell('');
										// }			
										end_row();
										$t_tax+=$row['pcd_tax'];
										$t_amount+=$row['pcd_amount'];
										}

										start_row();
										// label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('<b><font color=#880000>TOTAL AMOUNT:</font></b>','align=right');
										label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
										label_cell('');
										label_cell('<b><font color=#880000>TOTAL TAX:</font></b>','align=right');
										label_cell("<font color=#880000><b>".number_format2(abs($t_tax),2)."<b></font>",'align=right');
										label_cell('');
										end_row();
										end_table();
										
div_end();		
br(2);
submit_center_first('Approve', _("Approve"),_('Approve Journal'), false, ICON_ADD);
submit_center_last('Cancel', _("Cancel"), _('Cancel Journal'), false, ICON_CANCEL);
}													
end_form();
//END OF FORM
end_page();
?>