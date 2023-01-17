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
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/check_cart_2.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
$page_security = isset($_GET['NewPayment']) || 
	@($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT)
 ? 'SA_PAYMENT' : 'SA_DEPOSIT';

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/ui/gl_bank_ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");

include_once($path_to_root . "/modules/checkprint/includes/check_accounts_db.inc");
add_access_extensions();

if (isset($_GET['NewPayment']))
{
	$_SESSION['checks'] = new check_cart(ST_BANKPAYMENT,0);
} else if(isset($_GET['NewPayment'])) {
	$_SESSION['checks'] = new check_cart(ST_BANKDEPOSIT,0);
}

$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();


if(isset($_GET['NewPayment'])) {
	$_SESSION['page_title'] = _($help_context = "Other Income Payment Entry");
	handle_new_order(ST_BANKDEPOSIT);
}
page($_SESSION['page_title'], false, false, '', $js);

$selected_id = find_submit('selected_id');
//-----------------------------------------------------------------------------------------------
check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
if (list_updated('PersonDetailID')) {
	$br = get_branch(get_post('PersonDetailID'));
	$_POST['person_id'] = $br['debtor_no'];
	$Ajax->activate('person_id');
}

//--------------------------------------------------------------------------------------------------
function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_code_id_edit');
}

//-----------------------------------------------------------------------------------------------

function save_last_cheque($bank_ref, $check_ref)
{
	global $Refs;
	$next = $Refs->increment($check_ref);
	save_next_check_reference($bank_ref, $next);
}

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedDep']))
{
	$trans_no = $_GET['AddedDep'];
	$trans_type = ST_BANKDEPOSIT;

   	display_notification_centered(_("Other Income Payment $trans_no has been entered."));

	display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL Postings for this Payment")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Payment"), "NewPayment=yes");

	display_footer_exit();
}
if (isset($_POST['_date__changed'])) {
	$Ajax->activate('_ex_rate');
}
//--------------------------------------------------------------------------------------------------

function handle_new_order($type)
{
	if (isset($_SESSION['pay_items']))
	{
		unset ($_SESSION['pay_items']);
	}

	//session_register("pay_items");

	$_SESSION['pay_items'] = new items_cart($type);
			
	$_POST['date_'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['date_']))
	$_POST['date_'] = end_fiscalyear();
	$_SESSION['pay_items']->tran_date = $_POST['date_'];
}

// $_SESSION['checks'] = new check_cart($_SESSION['pay_items']->trans_type,0);

//-----------------------------------------------------------------------------------------------
if (isset($_POST['Process']))
{
	$input_error = 0;
	/*
		display_error($_POST['payment_type']);
		display_error(abs($_SESSION['checks']->amount));
		display_error(abs($_SESSION['pay_items']->gl_items_total()));

		if(abs($_SESSION['pay_items']->gl_items_total())<0)
		{
		display_error('negative');
		}
		
		
		
		if(abs($_SESSION['pay_items']->gl_items_total())!=abs($_SESSION['checks']->amount))
		{
		display_error('not equal');
		}
		else {
		display_error('equal');
		}
		
		
	if($_POST['payment_type']==2) {
		if (abs($_SESSION['checks']->amount) != abs($_SESSION['pay_items']->gl_items_total())) 
		{
			display_error(_("Total Amount of Checks must be equal to check items amount"));
			$input_error = 1;
			display_error($input_error);
		}
	}
	*/


	if (!is_date($_POST['date_']))
	{
		display_error(_("The entered date for the payment is invalid."));
		set_focus('date_');
		$input_error = 1;
	}
	
	if (!is_date_in_fiscalyear($_POST['date_']))
	{
		display_error(_("The entered date is not in fiscal year."));
		set_focus('date_');
		$input_error = 1;
	}
	
	if ($input_error == 1)
		unset($_POST['Process']);
}														if (isset($_POST['Process']))
														{
														global $Ajax;
														$prefix = 'selected_id';
														$c_ids = array();
														foreach($_POST as $postkey=>$postval)
														{
														if (strpos($postkey, $prefix) === 0) {
														$id = substr($postkey, strlen($prefix));
														$c_ids[] = $id;
														}
														}

														if (count($c_ids) > 0) {
														$c_id_str = implode(',',$c_ids);
														//display_error($c_id_str);
														}
														
														
														if ($c_id_str!='') {  //with receivable
														$trans = add_other_income_process_rec_payment($c_id_str,$_SESSION['pay_items']->trans_type, $_POST['bank_account'],
														$_SESSION['pay_items'], $_POST['date_'],$_POST['PayType'], $_POST['person_id'], get_post('PersonDetailID'),
														$_POST['ref'], $_POST['memo_'], true, $_SESSION['checks']);
														}
														else { //without receivable
														$trans = add_other_income_process_payment($c_id_str,$_SESSION['pay_items']->trans_type, $_POST['bank_account'],
														$_SESSION['pay_items'], $_POST['date_'],$_POST['PayType'], $_POST['person_id'], get_post('PersonDetailID'),
														$_POST['ref'], $_POST['memo_'], true, $_SESSION['checks']);
														}
														
														  $trans_type = $trans[0];
														  $trans_no = $trans[1];
														  new_doc_date($_POST['date_']);
										
													$_SESSION['pay_items']->clear_items();
													unset($_SESSION['pay_items']);
													if ($trans_no!='') {
													meta_forward($_SERVER['PHP_SELF'], "AddedDep=$trans_no");
													}

													} /*end of process credit note */

//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	//if (!check_num('amount', 0))
	//{
	//	display_error( _("The amount entered is not a valid number or is less than zero."));
	//	set_focus('amount');
	//	return false;
	//}

	if ($_POST['code_id'] == $_POST['bank_account'])
	{
		display_error( _("The source and destination accounts cannot be the same."));
		set_focus('code_id');
		return false;
	}

   	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
	$amount = ($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? 1:-1) * input_num('amount');
    if($_POST['UpdateItem'] != "" && check_item_data())
    {
    	$_SESSION['pay_items']->update_gl_item($_POST['Index'], $_POST['code_id'], 
    	    $_POST['dimension_id'], $_POST['dimension2_id'], $amount , $_POST['LineMemo']);
    }
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['pay_items']->remove_gl_item($id);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_new_item()
{
	if (!check_item_data())
		return;
	$amount = ($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? 1:-1) * input_num('amount');

	$_SESSION['pay_items']->add_gl_item($_POST['code_id'], $_POST['dimension_id'],
		$_POST['dimension2_id'], $amount, $_POST['LineMemo']);
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['AddItem']))
	handle_new_item();

if (isset($_POST['UpdateItem']))
	handle_update_item();

if (isset($_POST['CancelItemChanges']))
	line_start_focus();

if (isset($_POST['go']))
{
	display_quick_entries($_SESSION['pay_items'], $_POST['person_id'], input_num('totamount'), 
		$_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ? QE_PAYMENT : QE_DEPOSIT);
	$_POST['totamount'] = price_format(0); $Ajax->activate('totamount');
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
//----------------------------------------------------------------------------------------------
function check_line_start_focus_2() {
  global $Ajax;

  $Ajax->activate('show_check_cart');
  set_focus('c_bank');
  
  unset($_POST['c_bank']);
  unset($_POST['c_branch']);
  unset($_POST['c_no']);
  unset($_POST['c_date']);
  unset($_POST['c_amt']);
}

function handle_new_check_2()
{
	global $Ajax;
	
	$error = false;
	
	if (trim($_POST['c_bank']) == '')
	{
		display_error(_("Check Bank is required."));
		set_focus('c_bank');
		$error = true;
	}if (trim($_POST['c_branch']) == '')
	{
		display_error(_("Check Bank Branch is required."));
		set_focus('c_branch');
		$error = true;
	}if (trim($_POST['c_no']) == '')
	{
		display_error(_("Check Number is required."));
		set_focus('c_no');
		$error = true;
	}if (!is_date($_POST['c_date']))
	{
		display_error(_("Invalid Check Date."));
		set_focus('c_date');
		$error = true;
	}if (input_num('c_amt') <= 0)
	{
		display_error(_("Check Amount is required."));
		set_focus('c_amount');
		$error = true;
	}
	
	if (!$error)
	{
		$chkchk = $_SESSION['checks']->check_check($_POST['c_bank'], $_POST['c_branch'], $_POST['c_no']);
		
		if ($chkchk === null)
		{
			$_SESSION['checks']->add_item($_POST['c_bank'], $_POST['c_branch'], $_POST['c_no'], $_POST['c_date'], input_num('c_amt'));
			$_POST['amount'] = number_format2($_SESSION['checks']->amount,2);
			//$Ajax->activate('amount');
			check_line_start_focus_2();
		}
		else
		{
			display_error(_("Duplicate Check number for ".$_POST['c_bank']." - ".$_POST['c_branch']." on line item #".($chkchk+1)));
			set_focus('c_bank');
		}
	}
}

function handle_delete_item_2($id)
{
	global $Ajax;
	$_SESSION['checks']->delete_check_item($id);
	$_POST['amount'] = number_format2($_SESSION['checks']->amount,2);
	//$Ajax->activate('amount');
    check_line_start_focus_2();
}

function handle_update_item_2()
{
	global $Ajax;
	$error = false;
	
	if (trim($_POST['c_bank']) == '')
	{
		display_error(_("Check Bank is required."));
		set_focus('c_bank');
		$error = true;
	}if (trim($_POST['c_branch']) == '')
	{
		display_error(_("Check Bank Branch is required."));
		set_focus('c_branch');
		$error = true;
	}if (trim($_POST['c_no']) == '')
	{
		display_error(_("Check Number is required."));
		set_focus('c_no');
		$error = true;
	}if (!is_date($_POST['c_date']))
	{
		display_error(_("Invalid Check Date."));
		set_focus('c_date');
		$error = true;
	}if (input_num('c_amt') <= 0)
	{
		display_error(_("Check Amount is required."));
		set_focus('c_amount');
		$error = true;
	}
	
	if (!$error)
	{
		$chkchk = $_SESSION['checks']->check_check($_POST['c_bank'], $_POST['c_branch'], $_POST['c_no'], $_POST['LineNo']);
		
		if ($chkchk === null)
		{
			$_SESSION['checks']->edit_item($_POST['LineNo'], $_POST['c_bank'], $_POST['c_branch'], $_POST['c_no'], $_POST['c_date'], input_num('c_amt'));
			$_POST['amount'] = number_format2($_SESSION['checks']->amount,2);
			//$Ajax->activate('amount');
			check_line_start_focus_2();
		}
		else
		{
			display_error(_("Duplicate Check!"));
			set_focus('c_bank');
		}
	}
}
//========================

$del_id = find_submit('Delete_2_');
if ($del_id!=-1)
	handle_delete_item_2($del_id);

if (isset($_POST['add_c']))
	handle_new_check_2();

if (isset($_POST['CancelItemChanges_2_'])) {
	check_line_start_focus_2();
}

if (isset($_POST['UpdateItem_2_']))
	handle_update_item_2();


//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

if ($selected_id != -1) {
global $Ajax;
	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'selected_id') === 0)
		{
		$id = substr($postkey, strlen('selected_id'));
		$id_ = explode(',', $id);
		//display_error("t_net_amount==>".$_POST['t_net_amount'.$id_[0]]);
		$_POST['sub_amount']+=$_POST['t_total_receivable'.$id_[0]];
		//display_error($_POST['sub_amount']);
		}
	}
$Ajax->activate('sub_amount'); 
}



start_form();
display_payment_header($_SESSION['pay_items']);		
start_table("$table_style2 width=80%", 10);
echo "<td>";

//-----START OF CASH------
if ($_POST['payment_type']=='0') {
display_heading('Cash Payment Details');
br();
start_table();
start_row();
amount_row('Total Cash Payment:', 'cash_amount',$t_receivable);
hidden('bd_vat', $t_ot);
hidden('bd_wt', $t_cwt);
hidden('trans_no', $transno);
end_row();
end_table(1);
}
//-----END OF CASH------

//----START OF CHECK----
if ($_POST['payment_type']=='1' or $_POST['payment_type']=='2') {
$bank_row = get_bank_account($_POST['bank_account']);			  
$_POST['c_bank'] = $bank_row['bank_name'];
$_POST['c_no'] = get_next_check_reference2($bank_row['account_code']);
div_start('show_check_cart');
show_check_cart($_SESSION['checks']);
if ($_POST['payment_type']=='2'){
display_gl_items($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ?_("GL Items"):_("GL Items"), $_SESSION['pay_items']);
}
div_end();
echo "</td>";
end_row();
end_table(1);
}
//----START OF CHECK-----
end_table(1);
//submit_center_first('Update', _("Update"), '', null);
//submit_center_last('Process',_("Process Payment"), '', 'default');
submit_center('Process', 'Process Payment', "align=center", true, true,'ok.gif');
br(3);


$sql = "select * from ".TB_PREF."other_income_receivable where (ot_trans_from_id='".$_POST['PayType']."' AND ot_payee_id='".$_POST['person_id']."') AND ot_paid = 0 ORDER BY ot_trans_date";
$res = db_query($sql);
//display_error($sql);
$count_row=db_num_rows($res);
//display_error($count_row);


if ((isset($_POST['PayType']) and isset($_POST['person_id'])) and ($count_row>0)) {
div_start('payable_list');
start_table("$table_style2 width=80%");
display_heading('List of Payables');
$th = array('Trans Date', 'Trans #', 'Payee Type','Payee','Total Sales','CWT','Output Vat','Total Payable', 'Memo',"");
//inactive_control_column($th);
table_header($th);
$k = 0;
while($row = db_fetch($res))
{
//display_error($row['ot_total_amount']);
$f_trans_no=$row['ot_id'];
$f_trans_no2[]=$row['ot_id'];
	alt_table_row_color($k);
	label_cell(sql2date($row['ot_trans_date']));
	label_cell(get_gl_view_str(ST_BANKDEPOSIT,$f_trans_no, $f_trans_no));
	label_cell($row['ot_trans_from_desc'] ,'nowrap');
	label_cell($row['ot_payee'] ,'nowrap');
	amount_cell($row['ot_total_amount'],false);
	amount_cell($row['ot_total_wt'],false);
	amount_cell($row['ot_total_ot'],false);
	amount_cell($row['ot_total_receivable'],false);
	hidden('t_total_receivable'.$row['ot_id'],$row['ot_total_receivable']);
	label_cell($row['ot_memo']);
	$selected='selected_id'.$row['ot_id'];
	check_cells('',$selected,'',true);
	end_row();
	$t_amount+=$row['ot_total_amount'];
	$t_cwt+=$row['ot_total_wt'];
	$t_ot+=$row['ot_total_ot'];
	$t_receivable+=$row['ot_total_receivable'];
}
	//$sel_trans_no=implode(',',$f_trans_no2);
	label_cell('');
	label_cell('');
	hidden('create_check_transno', $sel_trans_no);
	label_cell('');
	label_cell("<font color=#880000><b>"."TOTAL AMOUNT:"."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_cwt),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_ot),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_receivable),2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
end_table();

br(2);

start_table();
label_cell('<b>Total Net Amount Selected: </b>');
label_cell("<font color=#880000><b>".number_format2(abs($_POST['sub_amount']),2)."<b></font>",'align=right','sub_amount');
end_table();
div_end();
}

end_form();
end_table(1);
end_page();
?>