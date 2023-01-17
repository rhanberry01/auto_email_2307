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
$page_security = 'SA_SALESPAYMNT';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/cust_check_allocation_cart.inc");
include_once($path_to_root . "/includes/ui/check_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
//include_once($path_to_root . "/sales/includes/ui/cust_alloc_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}
if ($use_date_picker) {
	$js .= get_js_date_picker();
}

// add_js_file('allocate.js');

page(_($help_context = "Customer Payment Entry (CHECK)"), false, false, "", $js);

//----------------------------------------------------------------------------------------------

check_db_has_customers(_("There are no customers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------

if (list_updated('BranchID')) {
	// when branch is selected via external editor also customer can change
	$br = get_branch(get_post('BranchID'));
	$_POST['customer_id'] = $br['debtor_no'];
	$Ajax->activate('customer_id');
}

/*if (!isset($_POST['customer_id']))
	$_POST['customer_id'] = get_global_customer(false);*/
if (!isset($_POST['DateBanked'])) {
	$_POST['DateBanked'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['DateBanked'])) {
		$_POST['DateBanked'] = end_fiscalyear();
	}
}

if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The customer payment has been successfully entered."));

	//submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no."-".ST_CUSTPAYMENT, 'prtopt');

	display_note(get_gl_view_str(ST_CUSTPAYMENT, $payment_no, _("&View the GL Journal Entries for this Customer Payment")));

//	hyperlink_params($path_to_root . "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");

	hyperlink_no_params($path_to_root . "/sales/customer_payments_check.php", _("Enter Another &Customer Payment"));
	
	display_footer_exit();
}

//----------------------------------------------------------------------------------------------

function can_process()
{
	global $Refs;
	
	if (!get_post('customer_id')) 
	{
		display_error(_("There is no customer selected."));
		set_focus('customer_id');
		return false;
	} 
	
	if (get_post('salesman') == -1) 
	{
		display_error(_("There is no salesman selected."));
		set_focus('salesman');
		$atype = 0;
		return false;
	}
	
	if (!get_post('BranchID')) 
	{
		display_error(_("This customer has no branch defined."));
		set_focus('BranchID');
		return false;
	} 
	
	if (!get_post('bank_account')) 
	{
		display_error(_("There is no bank account selected."));
		set_focus('bank_account');
		$atype = 0;
		return false;
	}else{
		$atype = getBAccountType(get_post('bank_account'));
	}	
	if (!isset($_POST['DateBanked']) || !is_date($_POST['DateBanked'])) {
		display_error(_("The entered date is invalid. Please enter a valid date for the payment."));
		set_focus('DateBanked');
		return false;
	} elseif (!is_date_in_fiscalyear($_POST['DateBanked'])) {
		display_error(_("The entered date is not in fiscal year."));
		set_focus('DateBanked');
		return false;
	}

	if (!$Refs->is_valid($_POST['ref'])) {
		display_error(_("You must enter a reference."));
		set_focus('ref');
		return false;
	}

	if (!is_new_reference($_POST['ref'], ST_CUSTPAYMENT)) {
		display_error(_("The entered reference is already in use."));
		set_focus('ref');
		return false;
	}

	// if (!check_num('amount', 0)) {
		// display_error(_("The entered amount is invalid or negative and cannot be processed."));
		// set_focus('amount');
		// return false;
	// }

	if (isset($_POST['charge']) && !check_num('charge', 0)) {
		display_error(_("The entered amount is invalid or negative and cannot be processed."));
		set_focus('charge');
		return false;
	}
	if (isset($_POST['charge']) && input_num('charge') > 0) {
		$charge_acct = get_company_pref('bank_charge_act');
		if (get_gl_account($charge_acct) == false) {
			display_error(_("The Bank Charge Account has not been set in System and General GL Setup."));
			set_focus('charge');
			return false;
		}	
	}

	if (isset($_POST['_ex_rate']) && !check_num('_ex_rate', 0.000001))
	{
		display_error(_("The exchange rate must be numeric and greater than zero."));
		set_focus('_ex_rate');
		return false;
	}

	if ($_POST['discount'] == "") 
	{
		$_POST['discount'] = 0;
	}

	if (!check_num('discount')) {
		display_error(_("The entered discount is not a valid number."));
		set_focus('discount');
		return false;
	}
	
	if ($_POST['ewt'] == "") 
	{
		$_POST['ewt'] = 0;
	}

	if (!check_num('ewt')) {
		display_error(_("The entered ewt is not a valid number."));
		set_focus('ewt');
		return false;
	}
	
	if ($_POST['tracking'] == "") 
	{
		$_POST['tracking'] = 0;
	}

	if (!check_num('tracking')) {
		display_error(_("The entered tracking charge is not a valid number."));
		set_focus('tracking');
		return false;
	}

	//if ((input_num('amount') - input_num('discount') <= 0)) {
	// if (input_num('amount') <= 0) {
		// display_error(_("The balance of the amount and discout is zero or negative. Please enter valid amounts."));
		// set_focus('discount');
		// return false;
	// }
	
	$amount = $_SESSION['alloc']->amount;
	$ewt = $_SESSION['alloc']->ewt;
	$discount = $_SESSION['alloc']->discount;
	$total_allocated = $_SESSION['alloc']->get_total_allocations();
	
	// if ($amount != $_SESSION['checks']->amount AND $atype == 1) 
	// {
		// display_error(_("Check Total is not equal to total amount "));
		// set_focus('amount');
		// return false;
	// }
	
	if (round2($amount + $ewt + $discount - $total_allocated,2) < 0)
	{
		display_error('Amount is less than the Total Allocation.');
		return false;
	}
	else if (round2($amount + $ewt +$discount - $total_allocated,2) > 0 && !isset($_SESSION['over_pay_check']))
	{
		display_notification_centered('You are about to process a payment with an entered Amount more than the Total Allocation. Click the \'Add Payment\' button again if you want to proceed');
		$_SESSION['over_pay_check']=1;
		return false;
	}

	if($total_allocated<=0){
		display_error('Amount allocated must be greater than zero.');
		return false;
	}
	
	// if (isset($_POST["TotalNumberOfAllocs"]))
		// return check_allocations();
	// else
		return true;
}

//----------------------------------------------------------------------------------------------

// validate inputs
if (isset($_POST['AddPaymentItem'])) {

	if (!can_process()) {
		unset($_POST['AddPaymentItem']);
	}
}
if (isset($_POST['_customer_id_button'])) {
//	unset($_POST['branch_id']);
	$Ajax->activate('BranchID');
}
if (isset($_POST['_DateBanked_changed'])) {
  $Ajax->activate('_ex_rate');
}
if (list_updated('customer_id') || list_updated('bank_account')) {
	unset($_SESSION['over_pay_check']);
  $_SESSION['alloc']->read();
  $Ajax->activate('alloc_tbl');
}
//----------------------------------------------------------------------------------------------

if (isset($_POST['AddPaymentItem'])) {
	
	$cust_currency = get_customer_currency($_POST['customer_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);
	$comp_currency = get_company_currency();
	if ($comp_currency != $bank_currency && $bank_currency != $cust_currency)
		$rate = 0;
	else
		$rate = input_num('_ex_rate');

	new_doc_date($_POST['DateBanked']);

	// $payment_no = write_customer_payment(0, $_POST['customer_id'], $_POST['BranchID'],
		// $_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'],
		// input_num('amount'), input_num('discount'), $_POST['memo_'], $rate, 
		// input_num('charge'), $_POST['salesman'], input_num('ewt'), input_num('tracking'));
		
	//=====================================================================	
	$trans_no = 0;
	$customer_id = $_POST['customer_id'];
	$branch_id = $_POST['BranchID'];
	$bank_account = $_POST['bank_account'];
	$date_ = $_POST['DateBanked']; 
	$ref = $_POST['ref'];
	$amount = $_SESSION['alloc']->amount;
	$discount = input_num('discount');
	$memo_ =$_POST['memo_'];
	$charge=input_num('charge');
	$salesman = $_POST['salesman'];
	$ewt=input_num('ewt');
	$tracking=input_num('tracking');
	
	global $Refs;

	begin_transaction();

	$company_record = get_company_prefs();

	$payment_no = write_customer_trans(ST_CUSTPAYMENT, $trans_no, $customer_id, $branch_id, 
		$date_, $ref, $amount, $discount, 0, 0, 0, 0, 0, 0, 0, "", 0, $rate, 0, 0, $salesman, 0,
		$ewt, $tracking);

	$bank_gl_account = get_bank_gl_account($bank_account);

	if ($trans_no != 0) {
	  delete_comments(ST_CUSTPAYMENT, $trans_no);
	  void_bank_trans(ST_CUSTPAYMENT, $trans_no, true);
	  void_gl_trans(ST_CUSTPAYMENT, $trans_no, true);
	  void_cust_allocations(ST_CUSTPAYMENT, $trans_no, $date_);
	}
	$total = 0;
	/* Bank account entry first */
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
		$bank_gl_account, 0, 0, ($amount - $charge) + $tracking,  $customer_id,
		"Cannot insert a GL transaction for the bank account debit", $rate);

	if ($branch_id != ANY_NUMERIC) {

		$branch_data = get_branch_accounts($branch_id);

		$debtors_account = $branch_data["receivables_account"];
		$discount_account = $branch_data["payment_discount_account"];

	} else {
		$debtors_account = $company_record["debtors_act"];
		$discount_account = $company_record["default_prompt_payment_act"];
	}

	if (($ewt + $discount + $amount) != 0)	{
	/* Now Credit Debtors account with receipts + discounts */
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
		$debtors_account, 0, 0, -($ewt + $discount + $amount), $customer_id,
		"Cannot insert a GL transaction for the debtors account credit", $rate);
	}
	
	if ($discount != 0)	{
		/* Now Debit discount account with discounts allowed*/
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$discount_account, 0, 0, $discount, $customer_id,
			"Cannot insert a GL transaction for the payment discount debit", $rate);
	}
	
	if ($ewt != 0)	{
		/* Now Debit discount account with discounts allowed*/
		$ewt_act = get_company_pref('default_sales_ewt_act');
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$ewt_act, 0, 0, $ewt, $customer_id,
			"Cannot insert a GL transaction for the ewt debit", $rate);
	}
	
	if ($tracking != 0)	{
		/* Now Debit discount account with discounts allowed*/
		$tracking_act = get_company_pref('default_sales_tracking_charges_act');
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$tracking_act, 0, 0, -($tracking), $customer_id,
			"Cannot insert a GL transaction for the tracking credit", $rate);
	}

	if ($charge != 0)	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_company_pref('bank_charge_act');
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$charge_act, 0, 0, $charge, $customer_id,
			"Cannot insert a GL transaction for the payment bank charge debit", $rate);
	}
	/*Post a balance post if $total != 0 */
	add_gl_balance(ST_CUSTPAYMENT, $payment_no, $date_, -$total, PT_CUSTOMER, $customer_id);	

		//===== checks here
	foreach ($_SESSION['checks']->checks as $id=>$check_item)
	{
		if ($check_item->deleted == true)
			continue;
			
		/*now enter the bank_trans entry */
		$id = add_bank_trans_2(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref,
			$check_item->check_date, $check_item->check_amount, PT_CUSTOMER, $customer_id,
			get_customer_currency($customer_id), "", $rate);
		
		add_check2($payment_no,ST_CUSTPAYMENT,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
			$check_item->check_date,$check_item->check_amount, $id);
			// add_check2($payment_no, ST_CUSTPAYMENT, $_POST['Bank'], $_POST['Branch'], $_POST['ChkNo'], $_POST['Cheque_Date']);		
	}

	add_books_receipts($date_, $customer_id, $payment_no, ST_CUSTPAYMENT, $ref, $amount, $ewt);
	
	add_comments(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);

	$Refs->save(ST_CUSTPAYMENT, $payment_no, $ref);

	commit_transaction();

	//=================================================================================

	$_SESSION['alloc']->trans_no = $payment_no;
	$_SESSION['alloc']->write();

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_no");
}
//----------------------------------------------------------------------------------------------

function read_customer_data()
{
	global $Refs;

	$sql = "SELECT ".TB_PREF."debtors_master.pymt_discount,
		".TB_PREF."credit_status.dissallow_invoices
		FROM ".TB_PREF."debtors_master, ".TB_PREF."credit_status
		WHERE ".TB_PREF."debtors_master.credit_status = ".TB_PREF."credit_status.id
			AND ".TB_PREF."debtors_master.debtor_no = ".db_escape($_POST['customer_id']);

	$result = db_query($sql, "could not query customers");

	$myrow = db_fetch($result);

	$_POST['HoldAccount'] = $myrow["dissallow_invoices"];
	$_POST['pymt_discount'] = $myrow["pymt_discount"];
	$_POST['ref'] = $Refs->get_next(12);
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
//----------------------------------------------------------------------------------------------
function check_line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('show_check_cart');
  set_focus('c_bank');
  
  unset($_POST['c_bank']);
  unset($_POST['c_branch']);
  unset($_POST['c_no']);
  unset($_POST['c_date']);
  unset($_POST['c_amt']);
}

function handle_new_check()
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
			
			$_SESSION['alloc']->amount = $_SESSION['checks']->amount;
			$Ajax->activate('amount');
			$Ajax->activate('alloc_tbl');
			
			check_line_start_focus();
		}
		else
		{
			display_error(_("Duplicate Check number for ".$_POST['c_bank']." - ".$_POST['c_branch']." on line item #".($chkchk+1)));
			set_focus('c_bank');
		}
	}
}

function handle_delete_item($id)
{
	global $Ajax;
	$_SESSION['checks']->delete_check_item($id);
	// $_POST['amount'] = number_format2($_SESSION['checks']->amount,2);
	$_SESSION['alloc']->amount = $_SESSION['checks']->amount;
	$Ajax->activate('amount');
	$Ajax->activate('alloc_tbl');
    check_line_start_focus();
}

function handle_update_item()
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
			// $_POST['amount'] = number_format2($_SESSION['checks']->amount,2);
			$_SESSION['alloc']->amount = $_SESSION['checks']->amount;
			$Ajax->activate('amount');
			$Ajax->activate('alloc_tbl');
			check_line_start_focus();
		}
		else
		{
			display_error(_("Duplicate Check!"));
			set_focus('c_bank');
		}
	}
}

//========================
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
global $Ajax;

$del_id = find_submit('Delete');
if ($del_id!=-1)
	handle_delete_item($del_id);

if (isset($_POST['add_c']))
	handle_new_check();

if (isset($_POST['CancelItemChanges'])) {
	check_line_start_focus();
}

if (isset($_POST['UpdateItem']))
	handle_update_item();

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\// ALL CONTROLS HERE
if ($_POST['_discount_changed'] AND ($_SESSION['alloc']->discount != input_num('discount')))
{
	if (input_num('discount') < 0)
	{
		display_error('Discount must be greater than 0');
		$_POST['discount'] = 0;
	}
	
	$_SESSION['alloc']->discount = input_num('discount');
	
	$Ajax->activate('amount');
	$Ajax->activate('alloc_tbl');

}

if ($_POST['_ewt_changed'] AND ($_SESSION['alloc']->ewt != input_num('ewt')))
{
	if (input_num('ewt') < 0)
	{
		display_error('EWT must be greater than 0');
		$_POST['ewt'] = 0;
	}
	
	$_SESSION['alloc']->ewt = input_num('ewt');
	
	foreach ($_SESSION['alloc']->allocs as $key=>$alloc_item)
	{
		$alloc_item->ewt = 0;
		$_POST['ewt_percent'.$key] = '';

	}
		
	$Ajax->activate('ewt');
	$Ajax->activate('alloc_tbl');
}

$all_id = find_submit('All');
if ($all_id != - 1)
{
	$lacking = $_SESSION['alloc']->allocs[$all_id]->amount - $_SESSION['alloc']->allocs[$all_id]->amount_allocated;
	$_SESSION['alloc']->allocs[$all_id]->current_allocated = $lacking;
	$_POST['amount'.$all_id] = number_format2($lacking,2);
	
	$_SESSION['alloc']->update_ewt_using_item();
	
	$_POST['ewt'] = number_format2($_SESSION['alloc']->ewt,2);
	$Ajax->activate('ewt');
	$Ajax->activate('alloc_tbl');
	$Ajax->activate('amount'.$all_id);
}

$none_id = find_submit('None');
if ($none_id != - 1)
{
	$_POST['amount'.$none_id] = $_SESSION['alloc']->allocs[$none_id]->current_allocated = 0;
	
	$_SESSION['alloc']->update_ewt_using_item();
	
	$_POST['ewt'] = number_format2($_SESSION['alloc']->ewt,2);
	$Ajax->activate('ewt');
	$Ajax->activate('alloc_tbl');
	$Ajax->activate('amount'.$none_id);
}

$ewt_id = find_submit('_ewt_percent');
if ($ewt_id != -1)
{

	if ($_SESSION['alloc']->allocs[$ewt_id]->ewt != input_num('ewt_percent'.$ewt_id))
	{	
		$_SESSION['alloc']->allocs[$ewt_id]->ewt = input_num('ewt_percent'.$ewt_id);
		
		$_SESSION['alloc']->update_ewt_using_item();
		
		// $_POST['amount'.$ewt_id] =  input_num('amount'.$ewt_id) * (1 + input_num('ewt_percent'.$ewt_id)/100);
		// $_SESSION['alloc']->allocs[$ewt_id]->current_allocated = input_num('amount'.$amt_id);
		
		
		$_POST['ewt'] = number_format2($_SESSION['alloc']->ewt,2);
		$Ajax->activate('ewt');
		$Ajax->activate('alloc_tbl');
		$Ajax->activate('ewt_percent'.$ewt_id);
	}
}

$amt_id = find_submit('_amount');
if ($amt_id != -1)
{	
	if ($_SESSION['alloc']->allocs[$amt_id]->current_allocated != input_num('amount'.$amt_id))
	{	
		$lacking = $_SESSION['alloc']->allocs[$amt_id]->amount - $_SESSION['alloc']->allocs[$amt_id]->amount_allocated;
		
		if (input_num('amount'.$amt_id) < 0)
		{
			$_POST['amount'.$amt_id] = 0;
		}
		else if (input_num('amount'.$amt_id) > $lacking)
		{
			$_POST['amount'.$amt_id] = $lacking;
		}
		
		$_SESSION['alloc']->allocs[$amt_id]->current_allocated = input_num('amount'.$amt_id);
		
		$_SESSION['alloc']->update_ewt_using_item();
		$_POST['ewt'] = number_format2($_SESSION['alloc']->ewt,2);

		$Ajax->activate('ewt');
		$Ajax->activate('alloc_tbl');
		$Ajax->activate('amount'.$amt_id);
	}
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

start_form();

	start_outer_table("$table_style2 width=60%", 5);
	table_section(1);

	customer_list_row(_("From Customer:<font color=red>*</font>"), 'customer_id', null, '', true);
	if (!isset($_POST['bank_account']))	// first page call
	{
		  $_SESSION['alloc'] = new allocation(ST_CUSTPAYMENT,0);
		  $_SESSION['checks'] = new check_cart(ST_CUSTPAYMENT,0);
	}

	if (db_customer_has_branches($_POST['customer_id'])) {
		customer_branches_list_row(_("Branch:"), $_POST['customer_id'], 'BranchID', null, false, true, true);
	} else {
		hidden('BranchID', ANY_NUMERIC);
	}

	read_customer_data();
	
	//sales_persons_list_row(_("Sales Person:"), 'salesman');
	sales_persons_list_row2( _("Sales Person:"), 'salesman', $salesman, '');	

	set_global_customer($_POST['customer_id']);
	if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0)	{
		end_outer_table();
		display_error(_("This customer account is on hold."));
	} else {
		$display_discount_percent = percent_format($_POST['pymt_discount']*100) . "%";

		table_section(2);

		check_accounts_list_row_2(_("Into Account:<font color=red>*</font>"), 'bank_account', null, true, '');

		// if(get_bank_trans_type($_POST['bank_account']) == 1)
		// {
			// text_row(_("Bank:<font color=red>*</font>"), 'Bank', null, 20, 40);
			// text_row(_("Branch:<font color=red>*</font>"), 'Branch', null, 20, 40);
			// text_row(_("Cheque Number:<font color=red>*</font>"), 'ChkNo', null, 20, 40);
			// date_row(_("Cheque Date:<font color=red>*</font>"), 'Cheque_Date','',null, 0, 0, 0, null, true);
		// }


		text_row(_("OR/PR No.:<font color=red>*</font>"), 'ref', null, 20, 40);

		table_section(3);

		date_row(_("<!--//Date of Deposit//-->OR/PR Date:<font color=red>*</font>"), 'DateBanked', '', true, 0, 0, 0, null, true);

		$comp_currency = get_company_currency();
		$cust_currency = get_customer_currency($_POST['customer_id']);
		$bank_currency = get_bank_account_currency($_POST['bank_account']);

		if ($cust_currency != $bank_currency) {
			exchange_rate_display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
		}

		amount_row(_("Bank Charge:"), 'charge');

		end_outer_table(1);
		
  		div_start('show_check_cart');
			show_check_cart($_SESSION['checks']);
		div_end();

		if ($cust_currency == $bank_currency) {
	  		div_start('alloc_tbl');
			show_allocatable(get_bank_trans_type($_POST['bank_account']) == 1);
			div_end();
		}

		start_table("$table_style width=60%");

		label_row(_("Customer prompt payment discount :"), $display_discount_percent);
		amount_row(_("Amount of Discount:"), 'discount',null,null,null,null,'amount_submit');
		
		
		amount_row(_("EWT:"), 'ewt',null,null,null,null,'amount_submit');
		// start_row();
		// label_cell("EWT:", "class=label", $id=null);
		// amount_cell($_POST['ewt'], false, '', 'ewt');
		// end_row();
		
		// amount_row(_("Other Charges:"), 'tracking');

		// amount_row(_("Amount:<font color=red>*</font>"), 'amount');
		start_row();
			label_cell(_("Amount:<font color=red>*</font>"));
			label_cell('<b>'.number_format($_SESSION['checks']->amount,2).'</b>','','amount');
		end_row();	

		text_row(_("Memo:"), 'memo_');
		end_table(1);

		if ($cust_currency != $bank_currency)
			display_note(_("Amount and discount are in customer's currency."));

		br();

		submit_center('AddPaymentItem', _("Add Payment"), true, '', 'default');
	}

	br();

end_form();
end_page();
?>
