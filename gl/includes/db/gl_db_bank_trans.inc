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
//----------------------------------------------------------------------------------

// add a bank transaction
// $amount is in $currency
// $date_ is display date (non-sql)

function add_bank_trans($type, $trans_no, $bank_act, $ref, $date_,
	$amount, $person_type_id, $person_id, $currency="", $err_msg="", $rate=0)
{

	if (is_date_in_event_locker($date_)==1)
	{
		display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
		exit();
	}


	$sqlDate = date2sql($date_);

	// convert $amount to the bank's currency
	if ($currency != "")
	{
    	$bank_account_currency = get_bank_account_currency($bank_act);
    	if ($rate == 0)
    		$to_bank_currency = get_exchange_rate_from_to($currency, $bank_account_currency, $date_);
    	else
    		$to_bank_currency = 1 / $rate;
    	$amount_bank = ($amount / $to_bank_currency);
	}
	else
		$amount_bank = $amount;


	// Also store the rate to the home
	//$BankToHomeCurrencyRate = get_exchange_rate_to_home_currency($bank_account_currency, $date_);

	$sql = "INSERT INTO ".TB_PREF."bank_trans (type, trans_no, bank_act, ref,
		trans_date, amount, person_type_id, person_id) ";

	$sql .= "VALUES ($type, $trans_no, '$bank_act', ".db_escape($ref).", '$sqlDate',
		".db_escape($amount_bank).", ".db_escape($person_type_id)
		.", ". db_escape($person_id).")";

	if ($err_msg == "")
		$err_msg = "The bank transaction could not be inserted";

	db_query($sql, $err_msg);
}

function add_bank_trans_2($type, $trans_no, $bank_act, $ref, $date_,
	$amount, $person_type_id, $person_id, $currency="", $err_msg="", $rate=0)
{
		if (is_date_in_event_locker($date_)==1)
		{
			display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
			exit();
		}
	
	$sqlDate = date2sql($date_);

	// convert $amount to the bank's currency
	if ($currency != "")
	{
    	$bank_account_currency = get_bank_account_currency($bank_act);
    	if ($rate == 0)
    		$to_bank_currency = get_exchange_rate_from_to($currency, $bank_account_currency, $date_);
    	else
    		$to_bank_currency = 1 / $rate;
    	$amount_bank = ($amount / $to_bank_currency);
	}
	else
		$amount_bank = $amount;


	// Also store the rate to the home
	//$BankToHomeCurrencyRate = get_exchange_rate_to_home_currency($bank_account_currency, $date_);

	$sql = "INSERT INTO ".TB_PREF."bank_trans (type, trans_no, bank_act, ref,
		trans_date, amount, person_type_id, person_id) ";

	$sql .= "VALUES ($type, $trans_no, '$bank_act', ".db_escape($ref).", '$sqlDate',
		".db_escape($amount_bank).", ".db_escape($person_type_id)
		.", ". db_escape($person_id).")";

	if ($err_msg == "")
		$err_msg = "The bank transaction could not be inserted";

	db_query($sql, $err_msg);
	
	return mysql_insert_id();
}

//----------------------------------------------------------------------------------------

function exists_bank_trans($type, $type_no)
{
	$sql = "SELECT trans_no FROM ".TB_PREF."bank_trans WHERE type=".db_escape($type)
		." AND trans_no=".db_escape($type_no);
	$result = db_query($sql, "Cannot retrieve a bank transaction");

    return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------

function get_bank_trans($type, $trans_no=null, $person_type_id=null, $person_id=null)
{
	$sql = "SELECT *, bank_account_name, account_code, bank_curr_code
		FROM ".TB_PREF."bank_trans, ".TB_PREF."bank_accounts
		WHERE ".TB_PREF."bank_accounts.id=".TB_PREF."bank_trans.bank_act ";
	if ($type != null)
		$sql .= " AND type=".db_escape($type);
	if ($trans_no != null)
		$sql .= " AND ".TB_PREF."bank_trans.trans_no = ".db_escape($trans_no);
	if ($person_type_id != null)
		$sql .= " AND ".TB_PREF."bank_trans.person_type_id = ".db_escape($person_type_id);
	if ($person_id != null)
		$sql .= " AND ".TB_PREF."bank_trans.person_id = ".db_escape($person_id);
	$sql .= " ORDER BY trans_date, ".TB_PREF."bank_trans.id";

	return db_query($sql, "query for bank transaction");
}

function get_bank_trans_2($type, $trans_no=null, $person_type_id=null, $person_id=null)
{
	$sql = "SELECT *, SUM(amount) as Total, bank_account_name, account_code, bank_curr_code
		FROM ".TB_PREF."bank_trans, ".TB_PREF."bank_accounts
		WHERE ".TB_PREF."bank_accounts.id=".TB_PREF."bank_trans.bank_act ";
	if ($type != null)
		$sql .= " AND type=".db_escape($type);
	if ($trans_no != null)
		$sql .= " AND ".TB_PREF."bank_trans.trans_no = ".db_escape($trans_no);
	if ($person_type_id != null)
		$sql .= " AND ".TB_PREF."bank_trans.person_type_id = ".db_escape($person_type_id);
	if ($person_id != null)
		$sql .= " AND ".TB_PREF."bank_trans.person_id = ".db_escape($person_id);
	$sql .= " GROUP BY trans_no ORDER BY trans_date, ".TB_PREF."bank_trans.id";

	return db_query($sql, "query for bank transaction");
}

function get_bank_trans_details($id)
{
	$sql = "SELECT * FROM ".TB_PREF."bank_trans
			WHERE id=$id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}
function get_bank_trans_date($id)
{
	$sql = "SELECT trans_date FROM ".TB_PREF."bank_trans
			WHERE id=$id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return sql2date($row[0]);
}

//----------------------------------------------------------------------------------------

function get_gl_trans_value($account, $type, $trans_no)
{
	$sql = "SELECT SUM(amount) FROM ".TB_PREF."gl_trans WHERE account="
	.db_escape($account)." AND type=".db_escape($type)
	." AND type_no=".db_escape($trans_no);

	$result = db_query($sql, "query for gl trans value");

	$row = db_fetch_row($result);
	return $row[0];
}

//----------------------------------------------------------------------------------------

function void_bank_trans($type, $type_no, $nested=false)
{
	if (!$nested)
		begin_transaction();
	
		$res2=get_gl_trans($type, $type_no);
		$row2=db_fetch($res2);
		$tran_date=sql2date($row2['tran_date']);
		
		
		if (is_date_in_event_locker($tran_date)==1)
		{
			display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
			exit();
		}
	

	$sql = "UPDATE ".TB_PREF."bank_trans SET amount=0
		WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	$result = db_query($sql, "could not void bank transactions for type=$type and trans_no=$type_no");

	void_gl_trans($type, $type_no, true);

	// in case it's a customer trans - probably better to check first
	void_cust_allocations($type, $type_no);
	void_customer_trans($type, $type_no);

	// in case it's a supplier trans - probably better to check first
	void_supp_allocations($type, $type_no);
	void_supp_trans($type, $type_no);

	void_trans_tax_details($type, $type_no);

	if (!$nested)
		commit_transaction();
}
function void_supp_trans_dis($type, $type_no)
{
	$sql = "UPDATE ".TB_PREF."supp_trans SET cv_id=0 WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
}

//----------------------------------------------------------------------------------
function void_bank_trans_dis($type, $type_no, $nested=false)
{
	if (!$nested)
		begin_transaction();
	
		$res2=get_gl_trans($type, $type_no);
		$row2=db_fetch($res2);
		$tran_date=sql2date($row2['tran_date']);
		
		
		if (is_date_in_event_locker($tran_date)==1)
		{
			display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
			exit();
		}
	

	$sql = "UPDATE ".TB_PREF."bank_trans SET amount=0
		WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	$result = db_query($sql, "could not void bank transactions for type=$type and trans_no=$type_no");

	//void_gl_trans($type, $type_no, true);

	// in case it's a customer trans - probably better to check first
	void_supp_trans_dis($type, $type_no);
	void_cust_allocations($type, $type_no);
	void_customer_trans($type, $type_no);

	// in case it's a supplier trans - probably better to check first
	//void_supp_allocations($type, $type_no);
	//void_supp_trans($type, $type_no);

	void_trans_tax_details($type, $type_no);

	if (!$nested)
		commit_transaction();
}

//----------------------------------------------------------------------------------


?>