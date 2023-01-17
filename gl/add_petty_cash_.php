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

$_SESSION['page_title'] = _($help_context = "Petty Cash Entry");
page($_SESSION['page_title'], false, false,'', $js);

$approve_add = find_submit('selected_id');
$approve_delete = find_submit('selected_del');
//--------------------------------------------------------------------------------------------------
function insert_petty_cash_header($date_,$emp_id,$prepared_by_id,$prepared_by_name,$memo)
{
global $path_to_root;

		$sql = "SELECT e.employee_id as emp_no, CONCAT(e.emp_firstname, ' ', e.emp_lastname)as  emp_name, ej.agency_id as emp_agency_id, a.agency_name as emp_agency_name
		FROM ".TB_PREF."hr_employee as e
		left join 0_hr_emp_job_info as ej
		on e.emp_number=ej.emp_id
		left join ".TB_PREF."hr_agency as a
		on ej.agency_id=a.id
		WHERE e.employee_id REGEXP '^[0-9]+$' 
		AND ej.emp_status NOT IN (6,7)
		and e.employee_id=".db_escape($emp_id)."";
		//display_error($sql);
		//$sql = "select * from ".TB_PREF."employee_list WHERE emp_no =".db_escape($emp_id)."";	
		$res = db_query($sql);
		$row=db_fetch($res);
		$emp_agency_id=$row['emp_agency_id'];
		$emp_agency_name=$row['emp_agency_name'];
		$employee_name=$row['emp_name'];
		$employee_id=$row['emp_no'];

$sql = "INSERT INTO ".TB_PREF."petty_cash_header(pc_date,pc_amount,pc_employee_id,pc_employee_name,pc_approved_by_id,pc_approved_by_name,pc_memo)
VALUES ('".date2sql($date_)."','".input_num('pc_amount')."','$emp_id',".db_escape($employee_name).",'$prepared_by_id',".db_escape($prepared_by_name).",".db_escape($memo).")";
//display_error($sql);	
db_query($sql,'failed to insert petty cash');

$remittance_id = db_insert_id();
return $remittance_id;
}

function insert_petty_cash_details($pc_id,$date_,$payee,$purpose,$ref,$tin,$prepared_by_id,$prepared_by_name)
{
global $path_to_root;

$sql = "INSERT INTO ".TB_PREF."petty_cash_details (pc_id,pcd_date,pcd_payee,pcd_purpose,pcd_gl_type,
pcd_ref,pcd_tin,pcd_amount,pcd_tax,pcd_prepared_by_id,pcd_prepared_by_name,pcd_approved_by_id,pcd_approved_by_name,pcd_replenished,pcd_date_replenished,pcd_wid_breakdown,pcd_breakdown,pcd_head_id)
VALUES ('$pc_id','".date2sql($date_)."',".db_escape($payee).",".db_escape($purpose).",'0','$ref','$tin','".input_num('amount')."','0','$prepared_by_id',".db_escape($prepared_by_name).",'','','0','0000-00-00','0','0','0')";
//display_error($sql);	
db_query($sql,'failed to insert petty cash details');

$remittance_id = db_insert_id();
return $remittance_id;
}

function delete_line($id)
{
$sql="DELETE FROM ".TB_PREF."petty_cash_details WHERE pcd_id=".db_escape($id)."";
//display_error($sql);
db_query($sql, "could not delete line item");
}

//-----------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_BANKTRANSFER;

   	display_notification_centered( _("Petty Cash Sequence #$trans_no has been approved."));
	hyperlink_no_params($path_to_root."/gl/add_petty_cash.php?NewPettyCash=Yes",_("Enter &Another Petty Cash"));
	display_footer_exit();
}

if (isset($_POST['Cancel'])){
$sqldelrefs = "Delete pch.*, pcd.* FROM ".TB_PREF."petty_cash_header as pch
left join ".TB_PREF."petty_cash_details as pcd
on pch.pc_id=pcd.pc_id
WHERE pch.pc_id = '".$_POST['seq']."'";
db_query($sqldelrefs);
//display_error($sqldelrefs);	 
meta_forward($_SERVER['PHP_SELF'], "NewPettyCash=Yes");
}

if (isset($_POST['Approve'])){
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

// if (($_POST['emp_id']=='') or ($_POST['emp_id']==0)) {
		// display_error(_("Employee cannot be empty. Please select one."));
		// set_focus('emp_id');
		// return false;
// }


// if (($_POST['rec_type']=='') or ($_POST['rec_type']==0)) {
		// display_error(_("Expenditure type cannot be empty. Please select one."));
		// set_focus('rec_type');
		// return false;
// }

	$approver_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver_real_name = $u['real_name'];
	
insert_petty_cash_details($_POST['pc_id'],$_POST['date'],$_POST['payee'],$_POST['purpose'],$_POST['ref'],$_POST['tin'],$approver_id,$approver_real_name);

$Ajax->activate('items_line');
$_POST['date']=$_POST['payee']=$_POST['purpose']=$_POST['ref']=$_POST['tin']=$_POST['amount']=$_POST['tax']=$approver_id=$approver_real_name='';
}


if (isset($_POST['save'])) 
{
if (($_POST['a_emp_id']=='') or ($_POST['a_emp_id']==0)) {
		display_error(_("Employee cannot be empty. Go back and select one."));
		set_focus('a_emp_id');
		hyperlink_no_params($path_to_root."/gl/add_petty_cash.php?NewPettyCash=Yes",_("Go Back"));
		return false;
}

if (($_POST['pc_amount']=='') or ($_POST['pc_amount']==0)) {
		display_error(_("The Amount cannot be empty or equal to zero (0)."));
		set_focus('pc_amount');
		hyperlink_no_params($path_to_root."/gl/add_petty_cash.php?NewPettyCash=Yes",_("Go Back"));
		return false;
}
	$approver_id=$_SESSION["wa_current_user"]->user;
	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver_real_name = $u['real_name'];
	
$trans_num=insert_petty_cash_header($_POST['date_'],$_POST['a_emp_id'],$approver_id,$approver_real_name,$_POST['memo_']);
//meta_forward($path_to_root."/gl/add_petty_cash.php?trans_no=$trans_num");
//hyperlink_no_params($path_to_root."/gl/add_petty_cash.php?trans_no=$trans_num");
header("location:../gl/add_petty_cash.php?trans_no=$trans_num");
}

if ($approve_delete != -1) {
global $Ajax;
delete_line($approve_delete);
$Ajax->activate('items_line');
}
//------------------------------------------------------------------------------

start_form();
if (isset($_GET['NewPettyCash'])) {

$sql="select max(pc_id) as pc_id from ".TB_PREF."petty_cash_header";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$pc_id=$row['pc_id'];
//display_error($pc_id);

$sql="select * from ".TB_PREF."petty_cash_details where pc_id='$pc_id'";
//display_error($sql);
$result_id_details=db_query($sql);
$count=db_num_rows($result_id_details);
//display_error($count);

if ($count<=0) {
$sql="delete from ".TB_PREF."petty_cash_header where pc_id='$pc_id'";
$result=db_query($sql);
//display_error($sql);
}

	div_start('pmt_header');
	start_outer_table("width=70% $table_style2"); // outer table
	table_section(1);
		if (isset($_GET['NewPettyCash']))
		$sqlid_details="select max(pc_id)+1 as pc_id from ".TB_PREF."petty_cash_header";
		
		$result_id_details=db_query($sqlid_details);
		
		while ($ot_id_row = db_fetch($result_id_details))
		{
		$trans_no=$ot_id_row['pc_id'];
		}	
		
	if (($trans_no=='') or ($trans_no=='0')) {
	$trans_no=$trans_no+1;
	}
  //  ref_row(_("Sequence #:"), 'seq', '', $trans_no);
	//label_cells("<b>Sequence #:</b>",$trans_no);
	date_row(_("<b>Date:</b> "), 'date_', '', true, 0, 0, 0, null, false);
	hidden('seq',$trans_no);
	table_section(2, "33%");
	get_employee_list_cells('<b>Employee:</b>', 'a_emp_id');
	amount_row('<b>Amount:</b> ', 'pc_amount',$t_receivable);
	table_section(3, "33%");
	//textarea_row(_("<b>Memo:</b>"), 'memo_', null, 28, 1);
	textarea_row(_("Memo:"), 'memo_', null, 28, 3);
	end_outer_table(1); // outer table
	div_end();
	
	br();	
	submit_center('save', 'Add New Petty Cash', "align=center", true, false,'ok.gif');
	}
	
else {
	div_start('pmt_header');
	start_outer_table("width=70% $table_style2"); // outer table
	table_section(1);
		if (isset($_GET['trans_no']))
		$sqlid_details="select * from ".TB_PREF."petty_cash_header where pc_id='".$_GET['trans_no']."'";
		
		$result_id_details=db_query($sqlid_details);
		
		while ($ot_id_row = db_fetch($result_id_details))
		{
		$trans_no=$ot_id_row['pc_id'];
		$pc_date=$ot_id_row['pc_date'];
		$pc_amount=$ot_id_row['pc_amount'];
		$pc_employee_name=$ot_id_row['pc_employee_name'];
		$pc_approved_by_name=$ot_id_row['pc_approved_by_name'];
		$pc_memo=$ot_id_row['pc_memo'];
		}	

  //  ref_row(_("Sequence #:"), 'seq', '', $trans_no);
	label_row("<b>Sequence #:</b> ",$trans_no);
	label_row("<b>Date:</b> ",sql2date($pc_date));
	hidden('seq',$trans_no);
	table_section(2, "33%");
	label_row("<b>Employee:</b> ",$pc_employee_name);
	label_row("<b>Amount:</b> ",number_format2($pc_amount));
	table_section(3, "33%");
	label_row(_("<b>Memo:</b> "),$pc_memo);
	label_row("<b>Prepared By:</b> ",$pc_approved_by_name);
	end_outer_table(1); // outer table
	div_end();
}


start_form();	
if (!$_GET['NewPettyCash']) {
//display_error($_GET['trans_no']);
div_start('items_line');
br();	
br();
// start_table();
// start_row();
	// date_cells(_("From:"), 'TransAfterDate', '', null);
	// date_cells(_("To:"), 'TransToDate', '', null);
	// get_employee_list_cells('Employee:', 'a_emp_cashier_id');
	// submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
// end_row();
// end_table(2);

//START OF FORM

global $table_style2, $Ajax;
display_heading("Petty Cash List of Expenditures");	
br();	
//$result = db_query($sql);

start_table($table_style2.' width=85%');
$th = array("Date",'Payee','Purpose of Expense(s)','REF#','TIN#','Amount','');
table_header($th);
start_row();
date_cells('', 'date', '', null);
//get_employee_list_cells('', 'emp_id');
text_cells('','payee','',20);
text_cells('', 'purpose', '', 25);
//get_expenditure_type_list_cells('','rec_type','',true);
text_cells('','ref','',9);
text_cells('','tin','',9);
//amount_cells_ex(null, 'tax',8,'','','','',2);
amount_cells_ex(null, 'amount',8,'','','','',2);
hidden('pc_id',$_GET['trans_no']);
hidden('p_amount',$_POST['p_amount']);
$selected='selected_id'.'1';
submit_cells($selected, _("Add Trans"), "colspan=2",_('Add Petty Cash'), true, ICON_ADD);			
											
										if ($_GET['trans_no']) {
										$sql = "select * from ".TB_PREF."petty_cash_details where pc_id='".$_GET['trans_no']."'";
										}
										else {
										$sql = "select * from ".TB_PREF."petty_cash_details where pc_id='".$_POST['pc_id']."'";
										}
										
										//display_error($sql);
										$result = db_query($sql);
										$k = 0; 
										while($row=mysql_fetch_array($result)) 
										{
										start_row();
										alt_table_row_color($k);
										label_cell(sql2date($row['pcd_date']));
										//label_cell($row['pcd_employee_name']);
										label_cell($row['pcd_payee']);
										label_cell($row['pcd_purpose']);
										//label_cell($row['exp_type_name'],'nowrap');
										label_cell($row['pcd_ref']);
										label_cell($row['pcd_tin']);
										//label_cell(number_format2(abs($row['pcd_tax']),2),'align=right');
										label_cell(number_format2(abs($row['pcd_amount']),2),'align=right');
										$selected_del='selected_del'.$row["pcd_id"];
										if (($approver_id==$row['a_prepared_by_id']) and (sql2date($row['pcd_date'])==Today()))
										{
										submit_cells($selected_del, _("Delete"), "colspan=2",_('Delete Particular'), true,ICON_DELETE);	
										}
										else {
										label_cell('');
										}			
										end_row();
										//$t_tax+=$row['pcd_tax'];
										$t_amount+=$row['pcd_amount'];
										}

										start_row();
										// label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										label_cell('');
										// label_cell('');
										label_cell('<b><font color=#880000>TOTAL:</font></b>','align=right');
										//label_cell("<font color=#880000><b>".number_format2(abs($t_tax),2)."<b></font>",'align=right');
										label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
										label_cell('');
										end_row();
										end_table();
div_end();		
br(2);
submit_center_first('Approve', _("Approve"),_('Approve Petty Cash'), false, ICON_ADD);
submit_center_last('Cancel', _("Cancel"), _('Cancel Petty Cash'), false, ICON_CANCEL);
}													
end_form();
//END OF FORM
end_page();
?>