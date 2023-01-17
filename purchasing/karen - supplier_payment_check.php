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
$page_security = 'SA_SUPPLIERPAYMNT';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/allocation_cart2.inc");
include_once($path_to_root . "/includes/ui/check_cart2.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/modules/checkprint/includes/check_accounts_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Supplier Payment Entry"), false, false, "", $js);

//----------------------------------------------------------------------------------------

check_db_has_suppliers(_("There are no suppliers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------

if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier(false);

if (!isset($_POST['DatePaid']))
{
	$_POST['DatePaid'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['DatePaid']))
		$_POST['DatePaid'] = end_fiscalyear();
}

if (isset($_POST['_DatePaid_changed'])) {
  $Ajax->activate('_ex_rate');
}

if (list_updated('supplier_id') || list_updated('bank_account')) {
  $_SESSION['s_alloc']->read();
  $Ajax->activate('alloc_tbl');
}
//----------------------------------------------------------------------------------------
//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\ ALL OR NOTHING

$all_id = find_submit('All');
if ($all_id != -1)
{
	
	if (input_num('limiter') == 0)
	{
		$_SESSION['s_alloc']->allocs[$all_id]->current_allocated = 0;
		$_SESSION['s_alloc']->allocs[$all_id]->current_allocated = $_SESSION['s_alloc']->allocs[$all_id]->amount - 
			$_SESSION['s_alloc']->allocs[$all_id]->amount_allocated;
	
		$_POST['amount'.$all_id] = number_format2($_SESSION['s_alloc']->allocs[$all_id]->current_allocated,2);
		
		$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations()-input_num('ewt'),2);
		$Ajax->activate('amount');
		
		if (count($_SESSION['s_checks']) > 0)
		{
			$_POST['c_amt'] = number_format2(input_num('amount') - $_SESSION['s_checks']->amount ,2);
		}
	}
	else
	{
		$_SESSION['s_alloc']->allocs[$all_id]->current_allocated = 0;
		
		if ((-$_SESSION['s_alloc']->amount - $_SESSION['s_alloc']->get_total_allocations()) -
			$_SESSION['s_alloc']->allocs[$all_id]->amount - $_SESSION['s_alloc']->allocs[$all_id]->amount_allocated >= 0)
		{
			$_SESSION['s_alloc']->allocs[$all_id]->current_allocated = $_SESSION['s_alloc']->allocs[$all_id]->amount - 
				$_SESSION['s_alloc']->allocs[$all_id]->amount_allocated;
		}
		else
		{
			$less_this = (-$_SESSION['s_alloc']->amount - $_SESSION['s_alloc']->get_total_allocations()) - $_SESSION['s_alloc']->allocs[$all_id]->amount;
			
			$_SESSION['s_alloc']->allocs[$all_id]->current_allocated = $_SESSION['s_alloc']->allocs[$all_id]->amount + $less_this;
		}
		
		$_POST['amount'.$all_id] = number_format2($_SESSION['s_alloc']->allocs[$all_id]->current_allocated,2);

		// if (count($_SESSION['s_checks']) > 0)
		// {
			// $_POST['c_amt'] = number_format2(input_num('amount') - $_SESSION['s_checks']->amount,2);
		// }
	}

	$Ajax->activate('alloc_tbl');
	$Ajax->activate('show_check_cart');

}

$none_id = find_submit('None');
if ($none_id != -1)
{
	if (input_num('limiter') == 0)
	{
		$_SESSION['s_alloc']->allocs[$none_id]->current_allocated = 0;
	
		$_POST['amount'.$none_id] = number_format2($_SESSION['s_alloc']->allocs[$none_id]->current_allocated,2);
		
		$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations()-input_num('ewt'),2);
		$Ajax->activate('amount');
		
		if (count($_SESSION['s_checks']) > 0)
		{
			$_POST['c_amt'] = $_POST['amount'];
			
			if ($_SESSION['s_alloc']->get_total_allocations()-$_SESSION['s_alloc']->allocs[$none_id]->current_allocated < $_SESSION['s_checks']->amount)
			{
				unset($_SESSION['s_checks']->checks);
				$_SESSION['s_checks']->amount = 0;
			}
		}
		$Ajax->activate('amount');
	}
	else
	{
		$_SESSION['s_alloc']->allocs[$none_id]->current_allocated = 0;
	
		$_POST['amount'.$none_id] = number_format2($_SESSION['s_alloc']->allocs[$none_id]->current_allocated,2);
	}
	
	$Ajax->activate('alloc_tbl');
	$Ajax->activate('show_check_cart');
}

$key = find_submit('_amount');

if ($key != -1)
{
	 if (($_POST['_amount'.$key.'_changed']))
	{
		if (input_num('limiter') == 0)
		{
			
			if (input_num('amount'.$key) != $_SESSION['s_alloc']->allocs[$key]->current_allocated)
			{
			
				if (input_num('amount'.$key) > $_SESSION['s_alloc']->allocs[$key]->amount-$_SESSION['s_alloc']->allocs[$key]->amount_allocated)
				{
					$_POST['amount'.$key] = $_SESSION['s_alloc']->allocs[$key]->amount-$_SESSION['s_alloc']->allocs[$key]->amount_allocated;
				}

				$_SESSION['s_alloc']->allocs[$key]->current_allocated = 0;
				$_SESSION['s_alloc']->allocs[$key]->current_allocated = input_num('amount'.$key);
			
				$_POST['amount'.$key] = number_format2($_SESSION['s_alloc']->allocs[$key]->current_allocated,2);
				
				$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations()-input_num('ewt'),2);
				
				$Ajax->activate('amount');
				
				if (count($_SESSION['s_checks']) > 0)
				{
					if ($_SESSION['s_alloc']->get_total_allocations()-$_SESSION['s_alloc']->allocs[$none_id]->current_allocated < $_SESSION['s_checks']->amount)
					{
						unset($_SESSION['s_checks']->checks);
						$_SESSION['s_checks']->amount = 0;
					}
					
					$_POST['c_amt'] = number_format2(input_num('amount') - $_SESSION['s_checks']->amount,2);
				}
				
				$Ajax->activate('alloc_tbl');
				$Ajax->activate('amount'.$key);
			}
		}
		else
		{	
			if (input_num('amount'.$key) != $_SESSION['s_alloc']->allocs[$key]->current_allocated)
			{
				if (input_num('amount'.$key) > (-$_SESSION['s_alloc']->amount) - $_SESSION['s_alloc']->get_total_allocations())
				{
					$_POST['amount'.$key] = (-$_SESSION['s_alloc']->amount) - $_SESSION['s_alloc']->get_total_allocations();
				}
				
				$_SESSION['s_alloc']->allocs[$key]->current_allocated = 0;
				$_SESSION['s_alloc']->allocs[$key]->current_allocated = input_num('amount'.$key);
				
				$_POST['amount'.$key] = number_format2($_SESSION['s_alloc']->allocs[$key]->current_allocated,2);
			
				$Ajax->activate('alloc_tbl');
			}
		}
		
		
		$Ajax->activate('show_check_cart');
		// break;
	}
}
$ewt_key=find_submit('ewt_percent');
if($ewt_key!=-1){
	$c=count($_SESSION['s_alloc']->allocs);
	for($counterx=0;$counterx<$c;$counterx++){
	$amx=input_num('amount'.$counterx);
	$amtx+=$amx;
	$valx=input_num('ewt_percent'.$counterx);
	$netx+=($amx/1.12)*($valx/100);
}
	//display_error($netx);
	$_POST['ewt']=number_format($netx,user_price_dec());
	$_POST['amount']=number_format($amtx-$netx,user_price_dec());
	$_POST['c_amt']=number_format($amtx-$netx,user_price_dec());
	$Ajax->activate('ewt');
	$Ajax->activate('amount');
	$Ajax->activate('show_check_cart');
}
//----------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$payment_id = $_GET['AddedID'];

   	display_notification_centered( _("Payment has been sucessfully entered"));

	submenu_print(_("&Print This Remittance"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, 'prtopt');
	submenu_print(_("&Email This Remittance"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, null, 1);

    display_note(get_gl_view_str(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment")));

//    hyperlink_params($path_to_root . "/purchasing/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter another supplier &payment"), "supplier_id=" . $_POST['supplier_id']);

	display_footer_exit();
}

//----------------------------------------------------------------------------------------

function check_inputs()
{
	global $Refs;

	
	if (input_num('limiter') > 0)
		$_POST['amount'] = -$_SESSION['s_alloc']->amount;
	else
		$_POST['amount'] = $_SESSION['s_alloc']->get_total_allocations();
		
	if (!get_post('supplier_id')) 
	{
		display_error(_("There is no supplier selected."));
		set_focus('supplier_id');
		return false;
	}
		
	if ($_POST['amount'] == "") 
	{
		$_POST['amount'] = price_format(0);
	}

	if (!check_num('amount', 0))
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}

	if (isset($_POST['charge']) && !check_num('charge', 0)) {
		display_error(_("The entered amount is invalid or less than zero."));
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

	if (!check_num('discount', 0))
	{
		display_error(_("The entered discount is invalid or less than zero."));
		set_focus('amount');
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

	if (input_num('amount') - input_num('discount') <= 0) 
	// if (input_num('amount') <= 0) 
	{
		display_error(_("The total of the amount and the discount is zero or negative. Please enter positive values."));
		set_focus('amount');
		return false;
	}

   	if (!is_date($_POST['DatePaid']))
   	{
		display_error(_("The entered date is invalid."));
		set_focus('DatePaid');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['DatePaid'])) 
	{
		display_error(_("The entered date is not in fiscal year."));
		set_focus('DatePaid');
		return false;
	}
    if (!$Refs->is_valid($_POST['ref'])) 
    {
		display_error(_("You must enter a reference."));
		set_focus('ref');
		return false;
	}

	if (!is_new_reference($_POST['ref'], ST_SUPPAYMENT)) 
	{
		display_error(_("The entered reference is already in use."));
		set_focus('ref');
		return false;
	}
	
	if ($_SESSION['s_checks']->amount != input_num('amount'))
	{
		display_error(_("Amount of Payment is not equal to Check Total Amount"));
		return false;
	}

	// if (isset($_POST["TotalNumberOfAllocs"]))
		// return check_allocations();
	// else
	// display_error('tentenenen');
		return true;
}

//----------------------------------------------------------------------------------------
function handle_add_payment()
{
	$supp_currency = get_supplier_currency($_POST['supplier_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);
	$comp_currency = get_company_currency();
	if ($comp_currency != $bank_currency && $bank_currency != $supp_currency)
		$rate = 0;
	else
		$rate = input_num('_ex_rate');

	// $payment_id = add_supp_payment($_POST['supplier_id'], $_POST['DatePaid'],
		// $_POST['bank_account'],	input_num('amount'), input_num('discount'), 
		// $_POST['ref'], $_POST['memo_'], $rate, input_num('charge'), input_num('ewt'));
	new_doc_date($_POST['DatePaid']);
	
	//============================================================================================
	global $Refs;
	
	$supplier_id = $_POST['supplier_id'];
	$date_ = $_POST['DatePaid'];
	$bank_account = $_POST['bank_account'];
	$amount = input_num('amount')-input_num('ewt');
	$discount = input_num('discount');
	$ref = $_POST['ref'];
	$memo_ = $_POST['memo_'];
	$charge = input_num('charge');
	$ewt = input_num('ewt');

	begin_transaction();

   	$supplier_currency = get_supplier_currency($supplier_id);
    $bank_account_currency = get_bank_account_currency($bank_account);
	$bank_gl_account = get_bank_gl_account($bank_account);

	if ($rate == 0)
	{
		$supp_amount = exchange_from_to($amount, $bank_account_currency, $supplier_currency, $date_);
		$supp_discount = exchange_from_to($discount, $bank_account_currency, $supplier_currency, $date_);
		$supp_charge = exchange_from_to($charge, $bank_account_currency, $supplier_currency, $date_);
	}
	else
	{
		$supp_amount = round($amount / $rate, user_price_dec());
		$supp_discount = round($discount / $rate, user_price_dec());
		$supp_charge = round($charge / $rate, user_price_dec());
	}
	

	// it's a supplier payment
	$trans_type = ST_SUPPAYMENT;

	/* Create a supp_trans entry for the supplier payment */
	$payment_id = add_supp_trans($trans_type, $supplier_id, $date_, $date_,
		$ref, "", -$supp_amount, 0, -$supp_discount, "", $rate, -$ewt);
	
	$total = 0;
	$supplier_accounts = get_supplier_accounts($supplier_id);
	$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $supplier_accounts["payable_account"], 0, 0,
		$ewt + $supp_amount + $supp_discount, $supplier_id, "", $rate);

	if ($supp_charge != 0)
	{
		$charge_act = get_company_pref('bank_charge_act');
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $charge_act, 0, 0,
			$supp_charge, $supplier_id, "", $rate);
	}

	if ($supp_discount != 0)
	{
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $supplier_accounts["payment_discount_account"], 0, 0,
			-$supp_discount, $supplier_id, "", $rate);
	}

	if ($ewt != 0)
	{
		$ewt_act = get_company_pref('default_sales_ewt_act');
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $ewt_act, 0, 0,
			-$ewt, $supplier_id, "", $rate);
	}
	
	if ($supp_charge != 0)
	{
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $bank_gl_account, 0, 0,
				-($supp_charge), $supplier_id, "", $rate);
				
		add_bank_trans($trans_type, $payment_id, $bank_account, $ref,
		$date_, -($supp_charge), PT_SUPPLIER,
		$supplier_id, $bank_account_currency,
		"Could not add the supplier payment bank transaction");
	}
	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\ loop through the checks
	foreach($_SESSION['s_checks']->checks as $key=>$check_item)	// Now debit creditors account with payment + discount
	{
		// Now credit discount received account with discounts
		
		if ($supp_amount != 0)
		{
			$total += add_gl_trans_supplier($trans_type, $payment_id, $check_item->check_date, $bank_gl_account, 0, 0,
				-($check_item->check_amount), $supplier_id, "", $rate);
		}
		
		/*now enter the bank_trans entry */
		add_bank_trans($trans_type, $payment_id, $bank_account, $ref,
			$check_item->check_date, -($check_item->check_amount), PT_SUPPLIER,
			$supplier_id, $bank_account_currency,
			"Could not add the supplier payment bank transaction");
			
		if(get_bank_trans_type($bank_account) == 1)
		{
			add_check2($payment_id, ST_SUPPAYMENT, $check_item->check_bank, '', $check_item->check_number, $check_item->check_date,-($check_item->check_amount));		
		}
	}
	/*Post a balance post if $total != 0 */
	add_gl_balance($trans_type, $payment_id, $date_, -$total, PT_SUPPLIER, $supplier_id);	
	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\\//\\\//\\\//\\\//\\\//\\\//\\\//\\\//\\\//\\\//\\

	add_comments($trans_type, $payment_id, $date_, $memo_);

	$Refs->save($trans_type, $payment_id, $ref);

	commit_transaction();
	
	//============================================================================================

	$_SESSION['s_alloc']->trans_no = $payment_id;
	$_SESSION['s_alloc']->write();
	//unset($_POST['supplier_id']);
   	unset($_POST['bank_account']);
   	unset($_POST['DatePaid']);
   	unset($_POST['currency']);
   	unset($_POST['memo_']);
   	unset($_POST['amount']);
   	unset($_POST['discount']);
   	unset($_POST['ProcessSuppPayment']);
	
	unset($_SESSION['s_alloc']);
	unset($_SESSION['s_checks']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_id&supplier_id=".$_POST['supplier_id']);
}

function increment($reference) 
{
	if (preg_match('/^(\D*?)(\d+)(.*)/', $reference, $result) == 1) 
	{
		list($all, $prefix, $number, $postfix) = $result;
		$dig_count = strlen($number); // How many digits? eg. 0003 = 4
		$fmt = '%0' . $dig_count . 'd'; // Make a format string - leading zeroes
		$nextval =  sprintf($fmt, intval($number + 1)); // Add one on, and put prefix back on

		return $prefix.$nextval.$postfix;
	}
	else 
		return $reference;
}
	
//----------------------------------------------------------------------------------------

if (isset($_POST['ProcessSuppPayment']))
{
	 /*First off  check for valid inputs */
    if (check_inputs() == true) 
    {
    	handle_add_payment();
    	end_page();
     	exit;
    }
}

//----------------------------------------------------------------------------------------

//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
//----------------------------------------------------------------------------------------------
function check_line_start_focus() {
  global 	$Ajax;

  set_focus('c_date');
  // unset($_POST['c_date']);
	
  $Ajax->activate('show_check_cart');
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
	}
	// if (trim($_POST['c_branch']) == '')
	// {
		// display_error(_("Check Bank Branch is required."));
		// set_focus('c_branch');
		// $error = true;
	// }
	if (trim($_POST['c_no']) == '')
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
		set_focus('c_amt');
		$error = true;
	}if (($_SESSION['s_alloc']->get_total_allocations() - ($_SESSION['s_checks']->amount + input_num('c_amt'))) < 0 AND input_num('limiter') == 0)
	{
		display_error(_("Total Check Amount will exceed amount of payment."));
		set_focus('c_amt');
		$error = true;
	}
	if ((input_num('limiter') - ($_SESSION['s_checks']->amount + input_num('c_amt')) < 0) AND input_num('limiter') > 0)
	{
		display_error(_("Total Check Amount will exceed amount of payment."));
		set_focus('c_amt');
		$error = true;
	}
	
	
	if (!$error)
	{
		$chkchk = $_SESSION['s_checks']->check_check($_POST['c_bank'], '', $_POST['c_no']);
		
		if ($chkchk === null)
		{
			$input_amt = input_num('c_amt');
			$_SESSION['s_checks']->add_item($_POST['c_bank'], '', $_POST['c_no'], $_POST['c_date'], input_num('c_amt'));
			// $_POST['amount'] = number_format2($_SESSION['s_checks']->amount,2);
			if (input_num('limiter') == 0)
				$_POST['c_amt'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_checks']->amount-input_num('ewt'),2);
			else
				$_POST['c_amt'] = number_format2(input_num('limiter') - $_SESSION['s_checks']->amount-input_num('ewt'),2);
			
			$_POST['c_no'] = increment($_POST['c_no']);
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
	
	$_SESSION['s_checks']->delete_check_item($id);
	
	if (input_num('limiter') == 0)
	{
		$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations()-input_num('ewt'),2);
		$_POST['c_amt'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_checks']->amount-input_num('ewt'),2);
	}
	else
		$_POST['c_amt'] = number_format2(input_num('limiter') - $_SESSION['s_checks']->amount,2);
	
	//$Ajax->activate('amount');
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
	}
	// if (trim($_POST['c_branch']) == '')
	// {
		// display_error(_("Check Bank Branch is required."));
		// set_focus('c_branch');
		// $error = true;
	// }
	if (trim($_POST['c_no']) == '')
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
	if (($_SESSION['s_alloc']->get_total_allocations() - ($_SESSION['s_checks']->amount + input_num('c_amt') - 
		$_SESSION['s_checks']->checks[$_POST['LineNo']]->check_amount)) < 0 AND input_num('limiter') == 0)
	{
		// display_error('allocs : ' . $_SESSION['s_alloc']->get_total_allocations());
		// display_error('total check : ' . $_SESSION['s_checks']->amount);
		// display_error('old check amount: ' . $_SESSION['s_checks']->amount);
		display_error(_("Total Check Amount will exceed amount of payment."));
		set_focus('c_amount');
		$error = true;
	}
	if ((input_num('limiter') - ($_SESSION['s_checks']->amount + (input_num('c_amt') - 
		$_SESSION['s_checks']->checks[$_POST['LineNo']]->check_amount)) < 0) AND input_num('limiter') > 0)
	{
		display_error(_("Total Check Amount will exceed amount of payment."));
		set_focus('c_amount');
		$error = true;
	}
	
	if (!$error)
	{
		$chkchk = $_SESSION['s_checks']->check_check($_POST['c_bank'], '', $_POST['c_no'], $_POST['LineNo']);
		
		if ($chkchk === null)
		{
			$_SESSION['s_checks']->edit_item($_POST['LineNo'], $_POST['c_bank'], '', $_POST['c_no'], $_POST['c_date'], input_num('c_amt'));
			$_POST['amount'] = number_format2($_SESSION['s_checks']->amount-input_num('ewt'),2);
			//$Ajax->activate('amount');
			if (input_num('limiter') == 0)
				$_POST['c_amt'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_checks']->amount,2);
			else
				$_POST['c_amt'] = number_format2(input_num('limiter') - $_SESSION['s_checks']->amount,2);
				
			$_POST['c_no'] = increment($_SESSION['s_checks']->get_last_check());
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

$del_id = find_submit('Delete_2_');
if ($del_id!=-1)
	handle_delete_item($del_id);

if (isset($_POST['add_c']))
	handle_new_check();

if (isset($_POST['CancelItemChanges'])) {
	
	check_line_start_focus();
}

if (isset($_POST['UpdateItem']))
	handle_update_item();


//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
global $Ajax;

start_form();

	start_outer_table("$table_style2 width=60%", 5);

	table_section(1);

    supplier_list_row(_("Payment To:<font color=red>*</font>"), 'supplier_id', null, '', true);

	if (!isset($_POST['bank_account']) OR list_updated('bank_account') OR ($_POST['_limiter_changed']))
	{	// first page call
		if (!isset($_POST['_limiter_changed']))
			$_POST['amount'] = $_POST['limiter'] = '';
		else
			$_POST['amount'] = $_POST['limiter'];
		
		
		$_SESSION['s_alloc'] = new allocation(ST_SUPPAYMENT, 0);
		$_SESSION['s_checks'] = new check_cart(ST_SUPPAYMENT,0);
		 
		if (isset($_POST['bank_account']))
		{
			$bank_row = get_bank_account($_POST['bank_account']);			  
			$_POST['c_bank'] = $bank_row['bank_name'];
			$_POST['c_no'] = get_next_check_reference2($bank_row['account_code']);
		}
		
		if (isset($_POST['_limiter_changed']))
		{
			$_SESSION['s_alloc']->clear_allocations();
			$_SESSION['s_alloc']->amount = -input_num('limiter');
			$_POST['c_amt'] = $_POST['limiter'];
			$_POST['amount'] = $_POST['limiter'];
			$Ajax->activate('amount');
		}
		
		$Ajax->activate('alloc_tbl');
		$Ajax->activate('show_check_cart');
		  
	}
	set_global_supplier($_POST['supplier_id']);
	
	check_accounts_list_row(_("From Bank Account:<font color=red>*</font>"), 'bank_account', null, true, '');

	// submit_cells('limit_me','Limit',true,false,false);
	// amount_cells('','limiter','',"class='tableheader2'");
	amount_cells('Limit Amount: ', 'limiter', null, null, null, null, 'amount_submit');
	
	table_section(2);

    ref_row(_("OR/PR No.:<font color=red>*</font>"), 'ref', '', $Refs->get_next(ST_SUPPAYMENT));

    date_row(_("<!--//Date Paid//-->OR/PR Date") . ":<font color=red>*</font>", 'DatePaid', '', true, 0, 0, 0, null, true);

	table_section(3);

	$supplier_currency = get_supplier_currency($_POST['supplier_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);
	if ($bank_currency != $supplier_currency) 
	{
		exchange_rate_display($bank_currency, $supplier_currency, $_POST['DatePaid'], true);
	}

	amount_row(_("Bank Charge:"), 'charge');
	

	end_outer_table(1); // outer table

	
	if ($bank_currency == $supplier_currency) {
		show_allocatable(input_num('limiter') > 0);
	}
	
	if ($_POST['bank_account'] != 0)
	{
		div_start('show_check_cart');
			show_check_cart($_SESSION['s_checks']);
		div_end();
	}
		

	start_table("$table_style width=60%");
	hidden('discount', $_POST['discount']);
	// amount_row(_("Amount of Discount:"), 'discount');
	
	amount_row(_("EWT:"), 'ewt');
	
	// amount_row(_("Amount of Payment:<font color=red>*</font>"), 'amount');
	
	start_row();
		label_cell('Amount of Payment:');
		label_cell('<b>'.(isset($_POST['amount']) ? $_POST['amount'] : '').'</b>','','amount');
	end_row();
	
	textarea_row(_("Memo:"), 'memo_', null, 22, 4);
	end_table(1);
	
	if ($bank_currency != $supplier_currency) 
	{
		display_note(_("The amount and discount are in the bank account's currency."), 0, 1);
	}

	submit_center('ProcessSuppPayment',_("Enter Payment"), true, '', 'default');

end_form();

end_page();
?>
