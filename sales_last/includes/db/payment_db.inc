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
/*
  Write/update customer payment.
*/
function write_customer_payment($trans_no, $customer_id, $branch_id, $bank_account,
	$date_, $ref, $amount, $discount, $memo_, $rate=0, $charge=0, $salesman=0,
	$ewt=0, $tracking=0)
{
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

	/*now enter the bank_trans entry */
	$id = add_bank_trans_2(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref,
		$date_, $amount - $charge, PT_CUSTOMER, $customer_id,
		get_customer_currency($customer_id), "", $rate);
		
	if(get_bank_trans_type($bank_account) == 1)
	{
		add_checks($payment_no, ST_CUSTPAYMENT, $_SESSION['checks'], $id);
		// add_check2($payment_no, ST_CUSTPAYMENT, $_POST['Bank'], $_POST['Branch'], $_POST['ChkNo'], $_POST['Cheque_Date']);		
	}

	add_comments(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);
	
	add_books_receipts($date_, $customer_id, $payment_no, ST_CUSTPAYMENT, $ref, $amount, $ewt);

	$Refs->save(ST_CUSTPAYMENT, $payment_no, $ref);

	commit_transaction();

	return $payment_no;
}

//-------------------------------------------------------------------------------------------------

function add_books_receipts($date, $debtor_no, $trans_no, $trans_type, $reference, $amount, $ewt)
{
	$sql = "INSERT  INTO ".TB_PREF."books_receipts(date, debtor_no, trans_no, trans_type, reference, amount, ewt)
			VALUES(".db_escape(date2sql($date)).", ".db_escape($debtor_no).", ".db_escape($trans_no).", ".db_escape($trans_type).", 
				".db_escape($reference).", ".db_escape($amount).", ".db_escape($ewt).")";
			
	db_query($sql,'unable to add check cash receipts');
}

function void_books_receipts($trans_no)
{
	$sql = "UPDATE ".TB_PREF."books_receipts
			SET amount = 0, ewt = 0
			WHERE trans_no = ".db_escape($trans_no);
			
	db_query($sql,'unable to add check cash receipts');
}


//-------------------------------------------------------------------------------------------------



function add_check2($trans_id, $type, $bank, $bank_branch, $chk_number, $date, $amount=0, $id, $pay_to='')
{
	if($type == ST_CUSTPAYMENT)
	{
		if($date == Today())
			$deposited = 1;
		else
			$deposited = 0;
	}
	else
		$deposited = 1;
		
	if($type == ST_BANKPAYMENT || $type == ST_SUPPAYMENT)
		$amount = -$amount;
		
	$sql = "INSERT  INTO ".TB_PREF."cheque_details(bank_trans_id, bank, branch, chk_number, chk_date, 
					type, chk_amount, deposited, bank_id, pay_to)
			VALUES($trans_id, ".db_escape($bank).", ".db_escape($bank_branch).", ".db_escape($chk_number).", 
					'".date2sql($date)."', $type, $amount, $deposited, $id, ".db_escape($pay_to).")";
			
	db_query($sql,'unable to add check details');
}



function add_cust_payment_details($inserttype,$payment_no,$ref,$debtor_no,$paymenttype,$check_date,$amount,$bank,$branch,$check_num,$deposited)	
{

		
$sql = "INSERT INTO ".TB_PREF."cust_payment_details(type,trans_no,ref_no,debtor_no,payment_type,chk_date,amount,bank,branch,chk_number,deposited,deposit_date,bank_id,remarks,pay_to)				
VALUES ('$inserttype','$payment_no','$ref','$debtor_no','$paymenttype','$check_date','$amount','$bank','$branch','$check_num','0','0000-00-00','0','','')";		

db_query($sql,'unable to add check details');

//display_error($sql);
}


 function update_cust_payment_details($approve_id,$deposit_date,$bank_id,$remarks,$payto)	
 {		
	 $sql = "UPDATE ".TB_PREF."cust_payment_details 
	 SET deposited = 1,
	 deposit_date='$deposit_date',
	 bank_id='$bank_id',
	 remarks='$remarks',
	 pay_to='$payto'
	WHERE id = '$approve_id'";

 db_query($sql,'unable to add check details');

//display_error($sql);
}



function add_check_deposit($trans_id,$type,$bank,$bank_branch,$chk_number,$date,$amount=0,$status,$deposit_date, $id)
{
	if($type == ST_CUSTPAYMENT)
	{
		if($date == Today())
			$deposited = 1;
		else
			$deposited = 0;
	}
	else
		$deposited = 1;
		
	if($type == ST_BANKPAYMENT || $type == ST_SUPPAYMENT)
		$amount = -$amount;
		
	$sql = "INSERT  INTO ".TB_PREF."cheque_details(bank_trans_id, bank, branch, chk_number, chk_date, type, chk_amount, deposited, status, deposit_date, bank_id)
			VALUES($trans_id, ".db_escape($bank).", ".db_escape($bank_branch).", ".db_escape($chk_number).", '".date2sql($date)."', $type, $amount, $deposited, '$status', '".date2sql($deposit_date)."', $id)";
			
	db_query($sql,'unable to add check details');
}

function add_checks($trans_id,$type,$checks, $id)
{
	foreach ($checks->checks as $id=>$check_item)
	{
		if ($check_item->deleted == true)
			continue;
			
		add_check2($trans_id,$type,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
			$check_item->check_date,$check_item->check_amount, $id);
	}
}

//-------------------------------------------------------------------------------------------------

function void_customer_payment($type, $type_no)
{
	begin_transaction();

	void_bank_trans($type, $type_no, true);
	void_gl_trans($type, $type_no, true);
	void_cust_allocations($type, $type_no);
	void_customer_trans($type, $type_no);

	commit_transaction();
}

function get_checks($trans_id,$type)
{
	$sql = "SELECT * FROM ".TB_PREF."cheque_details
				WHERE bank_trans_id = $trans_id
				AND type = $type";
	$res = db_query($sql);
	
	return $res;
}


//============== REMITTANCE


function get_cash_remittance_short_over($remittance_id)
{
		$sql = "SELECT * FROM ".TB_PREF."remittance WHERE remittance_id = ".$remittance_id;
		$res = db_query($sql);
		$r_row = db_fetch($res);
		
		$remittance_id = $r_row['remittance_id'];
		$cashier_id = $r_row['cashier_id'];
		$remittance_date = sql2date($r_row['remittance_date']);
		
		//show SHORT / OVER in here.
		// use terminal-ODBC table to loop on all the terminals
		
		//====== get initial cash
		$sql = "SELECT amount FROM ".TB_PREF."initial_cash WHERE cashier_id = $cashier_id";
		$res = db_query($sql);
		$row = db_fetch($res);
		
		$initial_cash = $row[0];
		//==============
		
		//====== get total remittances and id
		$sql = "SELECT total_amount,remittance_id FROM ".TB_PREF."remittance 
					WHERE cashier_id = $cashier_id
					AND remittance_date = '".date2sql($remittance_date)."'";
		$res = db_query($sql);
		
		$total_remittances = 0;
		$r_ids = array();
		while($row = db_fetch($res))
		{
			$total_remittances += $row['total_amount'];
			$r_ids[] = $row['remittance_id'];
		}
		
		//====== get cash, credit card, etc.
		$cash_description = 'Cash';
		$total_payments = array(); // [payment type] = array(total_amount, count)
		
		$sql = "SELECT * FROM ".TB_PREF."finishedpayments 
					WHERE remittance_id = $remittance_id
					AND Voided = 0
					ORDER BY Description";
		$res = db_query($sql);
		
		while($p_row = db_fetch($res))
		{
			$total_payments[$p_row['Description']][0] += $p_row['Amount'];
			$total_payments[$p_row['Description']][1] ++;
		}
		//================================
		$over_short = $total_remittances - $total_payments[$cash_description][0];
		return $over_short;
}

function get_remittance_short_over_per_type($remittance_id)
{
		$sql = "SELECT * FROM ".TB_PREF."remittance WHERE remittance_id = ".$remittance_id;
		$res = db_query($sql);
		$r_row = db_fetch($res);
		
		$remittance_id = $r_row['remittance_id'];
		$cashier_id = $r_row['cashier_id'];
		$remittance_date = sql2date($r_row['remittance_date']);
		
		//show SHORT / OVER in here.
		// use terminal-ODBC table to loop on all the terminals
		
		//====== get initial cash
		$sql = "SELECT amount FROM ".TB_PREF."initial_cash WHERE cashier_id = $cashier_id";
		$res = db_query($sql);
		$row = db_fetch($res);
		
		$initial_cash = $row[0];
		//==============
		
		//====== get total remittances and id
		$sql = "SELECT total_amount,remittance_id FROM ".TB_PREF."remittance 
					WHERE cashier_id = $cashier_id
					AND remittance_date = '".date2sql($remittance_date)."'";
		$res = db_query($sql);
		
		$total_remittances = 0;
		$r_ids = array();
		while($row = db_fetch($res))
		{
			$total_remittances += $row['total_amount'];
			$r_ids[] = $row['remittance_id'];
		}
		
		//====== get cash, credit card, etc.
		$cash_description = 'Cash';
		$total_payments = array(); // [payment type] = array(total_amount OR total short, count)
		
		$sql = "SELECT * FROM ".TB_PREF."finishedpayments 
					WHERE remittance_id = $remittance_id
					AND Voided = 0
					ORDER BY Description";
		$res = db_query($sql);
		
		while($p_row = db_fetch($res))
		{
			if ($p_row['Description'] != $cash_description AND $p_row['checked_by'] != 0)
			{
				$total_payments[$p_row['Description']][0] += $p_row['actual_amount'] - $p_row['Amount'];	
			}
			else if ($p_row['Description'] != $cash_description AND $p_row['checked_by'] == 0)
			{
				$total_payments[$p_row['Description']][0] += 0;	
			}
			else
			{
				$total_payments[$p_row['Description']][0] += $p_row['Amount'];	
			}
			
			$total_payments[$p_row['Description']][1] ++;
		}
		//================================
		$total_payments[$cash_description][0] = $total_remittances - $initial_cash - $total_payments[$cash_description][0]; //cash
		
		
		return $total_payments;
}

?>