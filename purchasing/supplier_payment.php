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

page(_($help_context = "Supplier Payment Entry (CASH)"), false, false, "", $js);

//----------------------------------------------------------------------------------------

check_db_has_suppliers(_("There are no suppliers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------

/*if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier(false);
*/
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
if ($_POST['_discount_changed'])
{
	if (input_num('discount') < 0)
	{
		display_error('Discount must be greater than 0');
		$_POST['discount'] = 0;
	}
	
	$_SESSION['s_alloc']->discount = input_num('discount');
	
	$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_alloc']->ewt - $_SESSION['s_alloc']->discount ,2);
		
	$Ajax->activate('amount');
	$Ajax->activate('alloc_tbl');
}

if ($_POST['_ewt_changed'] AND ($_SESSION['s_alloc']->ewt != input_num('ewt')))
{
	if (input_num('ewt') < 0)
	{
		display_error('EWT must be greater than 0');
		$_POST['ewt'] = 0;
	}
	
	$_SESSION['s_alloc']->ewt = input_num('ewt');
	
	foreach ($_SESSION['s_alloc']->allocs as $key=>$alloc_item)
	{
		$alloc_item->ewt = 0;
		$_POST['ewt_percent'.$key] = '';

	}
	$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_alloc']->ewt - $_SESSION['s_alloc']->discount ,2);
	
	$Ajax->activate('amount');
	$Ajax->activate('alloc_tbl');
}

$all_id = find_submit('All');
if ($all_id != -1)
{
	
	// if (input_num('limiter') == 0)
	// {
		$_SESSION['s_alloc']->allocs[$all_id]->current_allocated = 0;
		$_SESSION['s_alloc']->allocs[$all_id]->current_allocated = $_SESSION['s_alloc']->allocs[$all_id]->amount - 
			$_SESSION['s_alloc']->allocs[$all_id]->amount_allocated;
	
		$_POST['amount'.$all_id] = number_format2($_SESSION['s_alloc']->allocs[$all_id]->current_allocated,2);
		$_POST['ewt'.$all_id] = number_format2($_SESSION['s_alloc']->allocs[$all_id]->ewt,2);
		
		
		$_SESSION['s_alloc']->update_ewt_using_item();
		$_POST['ewt'] = number_format2($_SESSION['s_alloc']->ewt,2);
		
		$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_alloc']->ewt - $_SESSION['s_alloc']->discount,2);
		
		if (count($_SESSION['s_checks']) > 0)
		{
			$_POST['c_amt'] = number_format2(input_num('amount') - $_SESSION['s_checks']->amount ,2);
		}
		else
			$_POST['c_amt'] = $_POST['amount'];
		
		$Ajax->activate('amount');
	// }
	$Ajax->activate('amount'.$all_id);
	$Ajax->activate('ewt');
	$Ajax->activate('alloc_tbl');
	

}

$none_id = find_submit('None');
if ($none_id != -1)
{
	if (input_num('limiter') == 0)
	{
		$_SESSION['s_alloc']->allocs[$none_id]->current_allocated = 0;
	
		$_POST['amount'.$none_id] = number_format2($_SESSION['s_alloc']->allocs[$none_id]->current_allocated,2);
		
		$_POST['ewt_percent'.$none_id2] = number_format2($_SESSION['s_alloc']->allocs[$none_id2]->ewt,2);
		
		$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations()-$_SESSION['s_alloc']->discount,2);
		$Ajax->activate('amount');
		
		$_SESSION['s_alloc']->update_ewt_using_item();
		$_POST['ewt'] = number_format2($_SESSION['s_alloc']->ewt,2);
		
		$Ajax->activate('amount');
		$Ajax->activate('ewt');
	}
	
	$Ajax->activate('alloc_tbl');
	
}

$key2 = find_submit('_ewt_percent');

if ($key2 != -1)
{
	if (($_POST['_ewt_percent'.$key2.'_changed']))
	{
		// if (input_num('limiter') == 0)
		// {
			if (input_num('ewt_percent'.$key2) != $_SESSION['s_alloc']->allocs[$key2]->ewt)
			{
			
				$_SESSION['s_alloc']->allocs[$key2]->ewt = 0;
				$_SESSION['s_alloc']->allocs[$key2]->ewt = input_num('ewt_percent'.$key2);
			
				$_POST['ewt_percent'.$key2] = number_format2($_SESSION['s_alloc']->allocs[$key2]->ewt,2);
				
				$_SESSION['s_alloc']->update_ewt_using_item();
				$_POST['ewt'] = number_format2($_SESSION['s_alloc']->ewt,2);
				
				$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_alloc']->ewt - $_SESSION['s_alloc']->discount,2);
				
				$Ajax->activate('amount');
				
				if (count($_SESSION['s_checks']) > 0)
				{
					if ($_SESSION['s_alloc']->get_total_allocations()-$_SESSION['s_alloc']->allocs[$key2]->current_allocated < $_SESSION['s_checks']->amount)
					{
						unset($_SESSION['s_checks']->checks);
						$_SESSION['s_checks']->amount = 0;
					}
					
					$_POST['c_amt'] = number_format2(input_num('amount') - $_SESSION['s_checks']->amount,2);
				}
				else
					$_POST['c_amt'] = $_POST['amount'];
				
				$Ajax->activate('ewt');
				$Ajax->activate('alloc_tbl');
				$Ajax->activate('amount'.$key2);
				
			}
		// }
	}
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
				
				$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_alloc']->ewt - $_SESSION['s_alloc']->discount,2);
				
				$_SESSION['s_alloc']->update_ewt_using_item();
				$_POST['ewt'] = number_format2($_SESSION['s_alloc']->ewt,2);
						
				$Ajax->activate('ewt');
				$Ajax->activate('alloc_tbl');
				$Ajax->activate('amount'.$key);
				
				$Ajax->activate('amount');
			}
		}
	}
}

//----------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$payment_id = $_GET['AddedID'];

   	display_notification_centered( _("Payment has been sucessfully entered"));

	submenu_print(_("&Print This Payment"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, 'prtopt');
	//submenu_print(_("&Email This Payment"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, null, 1);

    display_note(get_gl_view_str(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment")));

//    hyperlink_params($path_to_root . "/purchasing/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter another supplier &payment"), "supplier_id=" . $_POST['supplier_id']);

	display_footer_exit();
}

//----------------------------------------------------------------------------------------

function check_inputs()
{
	global $Refs;

	
	$_POST['amount'] = $_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_alloc']->ewt;
		
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

	$payment_id = add_supp_payment($_POST['supplier_id'], $_POST['DatePaid'],
		$_POST['bank_account'],	input_num('amount'), input_num('discount'), 
		$_POST['ref'], $_POST['memo_'], $rate, input_num('charge'), input_num('ewt'));
	new_doc_date($_POST['DatePaid']);

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
	
  
}

function handle_new_check()
{
	global $Ajax;
	// display_error((input_num('limiter') - ($_SESSION['s_checks']->amount + (input_num('c_amt') - 
// $_SESSION['s_checks']->checks[$_POST['LineNo']]->check_amount))));
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
	}if (round(($_SESSION['s_alloc']->get_total_allocations() - ($_SESSION['s_checks']->amount + input_num('c_amt'))),2) < 0 AND round(input_num('limiter'),2) == 0)
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
				$_POST['c_amt'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_checks']->amount,2);
			else
				$_POST['c_amt'] = number_format2(input_num('limiter') - $_SESSION['s_checks']->amount,2);
			
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
		$_POST['amount'] = number_format2($_SESSION['s_alloc']->get_total_allocations(),2);
		$_POST['c_amt'] = number_format2($_SESSION['s_alloc']->get_total_allocations() - $_SESSION['s_checks']->amount,2);
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
			$_POST['amount'] = number_format2($_SESSION['s_checks']->amount,2);
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

	if (!isset($_POST['bank_account']) OR list_updated('bank_account'))
	{	// first page call
		if (!isset($_POST['_limiter_changed']))
			$_POST['amount'] = $_POST['limiter'] = '';
		
		
		$_SESSION['s_alloc'] = new allocation(ST_SUPPAYMENT, 0);
		$_SESSION['s_checks'] = new check_cart(ST_SUPPAYMENT,0);
		 
		if (isset($_POST['bank_account']))
		{
			$bank_row = get_bank_account($_POST['bank_account']);			  
			$_POST['c_bank'] = $bank_row['bank_name'];
			$_POST['c_no'] = get_next_check_reference2($bank_row['account_code']);
		}
		
		$Ajax->activate('alloc_tbl');
	  
	}
	set_global_supplier($_POST['supplier_id']);
	
	cash_accounts_list_row(_("From Account:<font color=red>*</font>"), 'bank_account', null, true, '');

	// submit_cells('limit_me','Limit',true,false,false);
	// amount_cells('','limiter','',"class='tableheader2'");
	
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
	

	start_table("$table_style width=60%");
	// hidden('discount', $_POST['discount']);
	amount_row(_("Amount of Discount:"), 'discount',null, null, null, null, 'amount_submit');
	
	amount_row(_("EWT:"), 'ewt',null, null, null, null, 'amount_submit');
	
	// amount_row(_("Amount of Payment:<font color=red>*</font>"), 'amount');
	
	start_row();
		label_cell('Amount of Payment:');
		label_cell('<b>'.(isset($_POST['amount']) ? $_POST['amount'] : '').'</b>','','amount');
	end_row();
	
	text_row(_("Memo:"), 'memo_', null, 22);
	end_table(1);
	
	if ($bank_currency != $supplier_currency) 
	{
		display_note(_("The amount and discount are in the bank account's currency."), 0, 1);
	}

	submit_center('ProcessSuppPayment',_("Enter Payment"), true, '', 'default');

end_form();

end_page();
?>
