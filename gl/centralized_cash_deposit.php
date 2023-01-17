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
	$_SESSION['page_title'] = _($help_context = "Cash Deposit");
	handle_new_order(ST_BANKDEPOSIT);
}
page($_SESSION['page_title'], false, false, '', $js);

$selected_id = find_submit('selected_id');
$added_id = find_submit('Process');
//-----------------------------------------------------------------------------------------------
check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//--------------------------------------------------------------------------------------------------

function get_branchcode_name($br_code)
{
$sql = "SELECT name from transfers.0_branches where code='".$br_code."' ";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_name=$row['name'];
return $br_name;
}

function get_dep_type_name($dep_id)
{
$sql = "SELECT dep_type_name from cash_deposit.0_deposit_type where dep_id='".$dep_id."' ";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_name=$row['dep_type_name'];
return $br_name;
}


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
	$trans_type = ST_CASHDEPOSIT;

   	display_notification_centered(_("Cash Deposit $trans_no has been saved."));

	display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL Postings for this deposit")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Deposit"), "NewPayment=yes");

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
if (isset($_POST['Process1']))
{
	// if ($added_id != -1)
	// {
	$input_error = 0;
	
	
		if ($_POST['bank_account2']==0)
	{
		display_error(_("Please select bank."));
		set_focus('bank_account2');
		$input_error = 1;
	}
	
	if ($_POST['desc']=='')
	{
		display_error(_("Please select deposit type."));
		set_focus('desc');
		$input_error = 1;
	}
	
	if ($_POST['payment_type']==1) {
		if($_SESSION['checks']->amount=='' or $_SESSION['checks']->amount<=0)
		{
		display_error('amount cannot be empty or 0.');
		$input_error = 1;
		}
	}
	
	if ($_POST['payment_type']==0) {
		if ($_POST['cash_amount']=='' or $_POST['cash_amount']<=0)
		{
			display_error(_("Amount cannot be empty or 0."));
			set_focus('cash_amount');
			$input_error = 1;
		}
	}
	
	if (!is_date($_POST['date_']))
	{
		display_error(_("The entered date for the payment is invalid."));
		set_focus('date_');
		$input_error = 1;
	}
	elseif (!is_date_in_fiscalyear($_POST['date_']))
	{
		display_error(_("The entered date is not in fiscal year."));
		set_focus('date_');
		$input_error = 1;
	}
	
	if ($input_error == 1)
		unset($_POST['Process1']);
	
}															


																// if ($added_id != -1)
																// {
																if (isset($_POST['Process1']))
																{									
																global $Ajax;

																$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
																
																$trans = process_cash_dep($myBranchCode,$dm_trans_nos,ST_CASHDEPOSIT, $_POST['bank_account'],
																$_SESSION['pay_items'], $_POST['date_'],$_POST['PayType'],get_post('PersonDetailID'),
																$_POST['ref'], $_POST['memo_'], true, $_SESSION['checks']);

																$trans_type = $trans[0];
																$trans_no = $trans[1];
																new_doc_date($_POST['date_']);

																$_SESSION['pay_items']->clear_items();
																unset($_SESSION['pay_items']);
															
																display_notification('New Desposit has been saved.');
																meta_forward($_SERVER['PHP_SELF'], "NewPayment=Yes");
																
																$Ajax->activate('table_2');
																}
																// else{
																// display_error('Nothing to process!');
																// }
														//	} /*end of process credit note */

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

start_table();
		//date_row('Date Deposit:', 'date_paid');
		bank_accounts_list_row2('Bank Account:', 'bank_account2', null,'',true);
end_table();

br(2);

display_cash_deposit_header($_SESSION['pay_items']);		
start_table("$table_style2 width=80%", 10);
echo "<td>";

//-----START OF CASH------
if ($_POST['payment_type']=='0') {
display_heading('Cash Details');
br();
start_table();
start_row();
amount_row('Total Cash:', 'cash_amount',$t_receivable);
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
//----END OF CHECK-----
end_table(1);
submit_center('Process1', 'Add Deposit', "align=center", true, true,'ok.gif');
br(2);
end_form();
end_table(1);
div_end();


start_form();

if (isset($_POST['date_'])) {
global $Ajax, $db_connections;

$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
//display_error($myBranchCode);
$sql = "select * from cash_deposit.".TB_PREF."cash_dep_header where cd_trans_type='61' and cd_sales_date='".date2sql($_POST['date_'])."' AND cd_br_code='$myBranchCode' ";
$sql .= "  order by cd_id asc";
$res = db_query($sql);
//display_error($sql);
$Ajax->activate('table_2');
}


div_start('table_2');

display_heading("Cash Deposit Summary");	
br();

start_table($table_style2.' width=80%');
$th = array();
array_push($th,'', 'Aria Trans #','Date Created','Sales Date', 'Branch', 'Type','Description','Amount','Remarks');
	
if (db_num_rows($res) > 0){
table_header($th);
		
$c=0;
while($row = db_fetch($res))
{
$c++;
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell(get_gl_view_str(ST_CASHDEPOSIT, $row["cd_aria_trans_no"], $row["cd_aria_trans_no"]));
	label_cell(sql2date($row['cd_trans_date']));
	label_cell(sql2date($row['cd_sales_date']));
	//label_cell($row["cd_id"]);
//	label_cell(get_gl_view_str(ST_CASHDEPOSIT, $row["cd_aria_trans_no"], $row["cd_aria_trans_no"]));
	
	$branch_name=get_branchcode_name($row['cd_br_code']);
	label_cell($branch_name,'nowrap');
	label_cell($row['cd_payment_type']);
	$cd_description=get_dep_type_name($row['cd_description']);
	label_cell($cd_description,'nowrap');
	//label_cell($row['cd_description'] ,'nowrap');
	amount_cell($row['cd_gross_amount'],false);
	label_cell($row['cd_memo']);
	//text_cells('','remarks'.$row['cd_id'],null);
	
	// if ($row['cd_cleared']==0) {
	// $submit='clear_selected'.$row['cd_id'];
	// submit_cells($submit, 'Clear', "align=center", true, true,'ok.gif');
	// }
	// else {
		// label_cell('Deposited');
	// }
	end_row();
end_form();

$t_amount+=$row['cd_gross_amount'];
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
}
else
{
	display_heading('No result found');

}

end_table();
div_end();
end_page();
?>