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

page(_($help_context = "Create Payment"), false, false, "", $js);

//----------------------------------------------------------------------------------------------

check_db_has_customers(_("There are no customers defined in the system."));
check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
if ($_GET['trans_no'] != 0)	// first page call
{
  $_SESSION['checks'] = new check_cart(ST_CUSTPAYMENT,0);
  $_SESSION['alloc'] = new allocation(ST_CUSTPAYMENT,0);
}

if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The customer payment has been successfully entered."));

	//submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no."-".ST_CUSTPAYMENT, 'prtopt');

	display_note(get_gl_view_str(ST_CUSTPAYMENT, $payment_no, _("&View the GL Journal Entries for this Customer Payment")));

//	hyperlink_params($path_to_root . "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");

	hyperlink_no_params($path_to_root ."/sales/inquiry/other_income_inquiry.php", _("Process Another &Customer Payment"));
	
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


	// $amount = $_SESSION['alloc']->amount;
		return true;
}

//----------------------------------------------------------------------------------------------

// validate inputs
if (isset($_POST['AddPaymentItem'])) {

	if (!can_process()) {
		unset($_POST['AddPaymentItem']);
	}
}
//----------------------------------------------------------------------------------------------

if (isset($_POST['AddPaymentItem'])) {


	$trans_no = 0;
	$create_check_transno = $_POST['create_check_transno'];
	$customer_id = $_POST['customer_id'];
	$branch_id = $_POST['BranchID'];
	$bank_account = $_POST['bank_account'];
	$date_ = Today(); 
	$trans_date = $_POST['c_date'];
	$due_date = sql2date($_POST['due_date']);
	$ref = $_POST['ref'];
	$amount = $_SESSION['checks']->amount;
	$discount = input_num('discount');
	$memo_ =$_POST['memo_'];
	$charge=input_num('charge');
	$salesman = $_POST['salesman'];
	$cwt=input_num('ewt');
	$tracking=input_num('tracking');
	$cash_amount = input_num('cash_amount');
	$trans_amount = input_num('trans_amount');
	$total_amount=$amount+$cash_amount+$cwt;
	$alloc_amount=$trans_amount-$cwt;
	
	// display_error($cash_amount);
	// display_error($cwt);
	// display_error($amount);
	// display_error($_SESSION['checks']->amount);
	
	
	global $Refs;						
	
	if ((($amount != 0) or ($cash_amount != 0) or ($total_amount > $trans_amount )) and  ($total_amount == $trans_amount ))	{
	
	begin_transaction();

	$company_record = get_company_prefs();
		
		$updatetype=ST_OTHERINCOME;
		$inserttype=ST_CUSTPAYMENT;
			$sql = "UPDATE ".TB_PREF."debtor_trans SET tracking = 1, alloc='$trans_amount'
			WHERE trans_no = '$create_check_transno'
			and type='$updatetype'";
			db_query($sql,'failed to update tracking.');
			//display_error($sql);
		

	
	$payment_no = write_customer_trans(ST_CUSTPAYMENT, $trans_no, $customer_id, $branch_id, $date_, $ref, $trans_amount, $discount, $cwt, $Freight=0, $FreightTax=0,
	$sales_type=0, $order_no=0, $trans_link=0, $ship_via=0, $due_date, $alloc_amount, $rate=0, $dimension_id=0, $dimension2_id=0, $salesman=0, $skip_dr=0, 
	$ewt=0, $tracking=1, $discount1=0, $discount2=0, $discount3=0, $discount4=0, $discount5=0);

	add_cust_allocation($trans_amount, ST_CUSTPAYMENT, $payment_no, ST_OTHERINCOME, $create_check_transno);

	//$bank_gl_account = get_bank_gl_account($bank_account);

	// if ($trans_no != 0) {
	  // delete_comments(ST_CUSTPAYMENT, $trans_no);
	  // void_bank_trans(ST_CUSTPAYMENT, $trans_no, true);
	  // void_gl_trans(ST_CUSTPAYMENT, $trans_no, true);
	  // void_cust_allocations(ST_CUSTPAYMENT, $trans_no, $date_);
	// }
	$total = 0;
	
	
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
	
	
		$debtors_account = $company_record["debtors_act"];
		$cwt_account = $company_record["default_sales_ewt_act"];
		$discount_account = $company_record["default_prompt_payment_act"];
	


	// if ($branch_id != ANY_NUMERIC) {

		// $branch_data = get_branch_accounts($branch_id);

		// $debtors_account = $branch_data["receivables_account"];
		// $discount_account = $branch_data["payment_discount_account"];

	// } else {
		//$debtors_account = $company_record["debtors_act"];
	//	$discount_account = $company_record["default_prompt_payment_act"];
	// }

	
	//START OF INSERT GL
	
	/* Bank account entry first  TOTAL*/
	if (($amount != 0) or ($cash_amount != 0))	{
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_, $debtors_account, 0, 0, -$trans_amount,  $customer_id,
	"Cannot insert a GL transaction for the bank account debit", $rate);
	}
	
	
	if ($amount != 0)	{
	/* Now Credit Debtors account with receipts + discounts */
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,$check_in_transit, 0, 0, $amount, $customer_id,
		"Cannot insert a GL transaction for the debtors account credit", $rate);
	
	foreach ($_SESSION['checks']->checks as $id=>$check_item)
	{
	 add_cust_payment_details($inserttype,$payment_no,$ref,$customer_id,$paymenttype='Check',date2sql($check_item->check_date),$check_item->check_amount,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,$deposited=0);	
	}

	}

	if ($cash_amount != 0)	{
	/* Now Credit Debtors account with receipts + discounts */
	$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,$cash_in_transit, 0, 0, $cash_amount, $customer_id,
		"Cannot insert a GL transaction for the debtors account credit", $rate);
	add_cust_payment_details($inserttype,$payment_no, $ref, $customer_id ,$paymenttype='Cash',date2sql($date_),$cash_amount);			
	}
	
	if ($cwt != 0)	{
	/* Now Credit Debtors account with receipts + discounts */
	add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,$cwt_account, 0, 0, $cwt, $customer_id,
	"Cannot insert a GL transaction for the debtors account credit", $rate);
	}
	

	
	
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);
		

	

	//END OF INSERT GL
	
	
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
	
	// if ($tracking != 0)	{
		// /* Now Debit discount account with discounts allowed*/
		// $tracking_act = get_company_pref('default_sales_tracking_charges_act');
		// $total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			// $tracking_act, 0, 0, -($tracking), $customer_id,
			// "Cannot insert a GL transaction for the tracking credit", $rate);
	// }

	if ($charge != 0)	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_company_pref('bank_charge_act');
		$total += add_gl_trans_customer(ST_CUSTPAYMENT, $payment_no, $date_,
			$charge_act, 0, 0, $charge, $customer_id,
			"Cannot insert a GL transaction for the payment bank charge debit", $rate);
	}
	/*Post a balance post if $total != 0 */
	// add_gl_balance(ST_CUSTPAYMENT, $payment_no, $date_, -$total, PT_CUSTOMER, $customer_id);	

		//===== checks here (exra insert)
			// foreach ($_SESSION['checks']->checks as $id=>$check_item)
			// {
				// if ($check_item->deleted == true)
					// continue;
					
				// /*now enter the bank_trans entry */
				// $id = add_bank_trans_2(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref,
					// $check_item->check_date, $check_item->check_amount, PT_CUSTOMER, $customer_id,
					// get_customer_currency($customer_id), "", $rate);
				
				// add_check2($payment_no,ST_CUSTPAYMENT,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
					// $check_item->check_date,$check_item->check_amount, $id);
					// // add_check2($payment_no, ST_CUSTPAYMENT, $_POST['Bank'], $_POST['Branch'], $_POST['ChkNo'], $_POST['Cheque_Date']);		
			// }

			// add_books_receipts($date_, $customer_id, $payment_no, ST_CUSTPAYMENT, $ref, $amount, $ewt);
			
			
			add_comments(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);
			$Refs->save(ST_CUSTPAYMENT, $payment_no, $ref);
		
		//===== end of checks here (exra insert)
	
	
	commit_transaction();
	
	
	$_SESSION['alloc']->trans_no = $payment_no;
	meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_no");
	 $_SESSION['checks']->clear_items();
	}
	
	else if ($total_amount > $trans_amount ) {
		display_error(_("Failed to process payment, The amount paid is greater than transaction amount."));
		set_focus('c_amount');
		$error = true;
		}
	
	else if ($total_amount != $trans_amount ) {
		display_error(_("Failed to process payment, The amount paid is not equal to transaction amount."));
		set_focus('c_amount');
		$error = true;
		}
	
	else {

		if (($amount == 0) or ($cash_amount == 0)){
		display_error(_("Failed to process payment, Cash Amount Total or Check Payment Total cannot be empty."));
		set_focus('c_amount');
		$error = true;
		}	
	}
	//=================================================================================

	
	//$_SESSION['alloc']->write();



 
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
	$_POST['ref'] = $Refs->get_next(ST_CUSTPAYMENT);
}

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
//----------------------------------------------------------------------------------------------
function check_line_start_focus() {
  global $Ajax;

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
			// $_POST['amount'] = number_format2($_SESSION['checks']->amount,2);
			
			// $_SESSION['alloc']->amount = $_SESSION['checks']->amount;
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
	// $_SESSION['alloc']->amount = $_SESSION['checks']->amount;
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
													// $_SESSION['alloc']->amount = $_SESSION['checks']->amount;
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



//START OF FORM
start_form();
		  
if (isset($_GET['trans_no'])!='')
{
$transno=$_GET['trans_no'];
$date_transact=$_GET['tran_date'];
$tran_date=Today();
$sql = "SELECT a.*, b.debtor_ref FROM ".TB_PREF."debtor_trans a, ".TB_PREF."debtors_master b
		WHERE a.trans_no = '$transno'
		AND a.debtor_no = b.debtor_no
		AND ov_amount > 0";
		
$res = db_query($sql);
//display_error($sql);

while($row = db_fetch($res))
{
$f_trans_no=$row['trans_no'];
$debtor_no=$row['debtor_no'];
$name=$row['debtor_ref'];
$due_date=$row['due_date'];
$trans_amount=$row['ov_amount'];
$branch_code=$row['branch_code'];
}
}


	start_outer_table("$table_style2 width=65%", 5);
	table_section(1);
	label_row(_("Customer:"), $name);
	
	// if ($name!='')	// first page call
	// {
		  // $_SESSION['checks'] = new check_cart(ST_CUSTPAYMENT,0);
	// }

	label_row(_("Transaction #:"), $f_trans_no);
	
	label_cell('Memo: '.get_comments_string(ST_OTHERINCOME, $f_trans_no));
	hidden('customer_id', $debtor_no);
	hidden('BranchID', $branch_code);
	hidden('tran_date', $tran_date);
	hidden('due_date', $due_date);
	hidden('trans_amount', $trans_amount);
	hidden('create_check_transno', $f_trans_no);
	read_customer_data();

	set_global_customer($debtor_no);
		

		
		
		table_section(2);
		label_row(_("Transaction Amount:"), number_format2(abs($trans_amount),2));
		//check_accounts_list_row_2(_("Into Account:<font color=red>*</font>"), 'bank_account', null, true, '');
		text_row(_("Reference #:<font color=red>*</font>"), 'ref', null, 20, 40);

		table_section(3);
		label_row("Transaction Date: ", $tran_date);
		label_row("Due Date: ", sql2date($due_date));
		end_outer_table(1);
		
		
		br();
		div_start('show_cash_payment');
		display_heading('<u>Cash Details</u>');
		br();
		start_table();
		label_cell(_("<b>Cash Payment Total:</b><font color=red>*</font>"));
		amount_cells('', 'cash_amount');
		//submit_cells('add_cash', _("Add Cash"), "colspan=2", _('Add new check to OR'), true);
		end_table();
		div_end();
		
		br();
		br();
		br();
  		div_start('show_check_cart');
			show_check_cart($_SESSION['checks']);
		div_end();

		start_table("$table_style width=65%");


		amount_row(_("Creditable Withholding Tax:"), 'ewt');
		// start_row();
		// label_cell("EWT:", "class=label", $id=null);
		// amount_cell($_POST['ewt'], false, '', 'ewt');
		// end_row();
		
		// amount_row(_("Other Charges:"), 'tracking');

		// amount_row(_("Amount:<font color=red>*</font>"), 'amount');
		start_row();
			label_cell(_("<b>Check Payment Total:</b><font color=red>*</font>"));
			label_cell(number_format($_SESSION['checks']->amount,2),'','amount');
					//	label_cell('<b>'.number_format($_SESSION['checks']->amount,2).'</b>','','amount');
		end_row();
		text_row(_("Memo:"), 'memo_');
		end_table(1);

		if ($cust_currency != $bank_currency)
			display_note(_("Amount and discount are in customer's currency."));

		br();

		submit_center('AddPaymentItem', _("Process"), true, '', 'default');

	br();
end_form();
//END OF FORM

end_page();
?>
