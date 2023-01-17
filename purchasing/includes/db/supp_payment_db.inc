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
function add_supp_payment($supplier_id, $date_, $bank_account,
	$amount, $discount, $ref, $memo_, $rate=0, $charge=0, $ewt=0)
{
	global $Refs;

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

	// Now debit creditors account with payment + discount

	$total = 0;
    $supplier_accounts = get_supplier_accounts($supplier_id);
	/*$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $supplier_accounts["payable_account"], 0, 0,
		$ewt + $supp_amount + $supp_discount, $supplier_id, "", $rate);*/
	$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $supplier_accounts["payable_account"], 0, 0,
		$ewt + $supp_amount + $supp_discount, $supplier_id, "", $rate);

	if ($supp_charge != 0)
	{
		$charge_act = get_company_pref('bank_charge_act');
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $charge_act, 0, 0,
			$supp_charge, $supplier_id, "", $rate);
	}
	
	// Now credit discount received account with discounts
	if ($supp_discount != 0)
	{
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $supplier_accounts["payment_discount_account"], 0, 0,
			-$supp_discount, $supplier_id, "", $rate);
	}
	
	if ($supp_amount != 0)
	{
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $bank_gl_account, 0, 0,
			-($supp_amount + $supp_charge), $supplier_id, "", $rate);
	}
	
	if ($ewt != 0)
	{
		$ewt_act = get_company_pref('default_purchase_ewt_act');
		$total += add_gl_trans_supplier($trans_type, $payment_id, $date_, $ewt_act, 0, 0,
			-$ewt, $supplier_id, "", $rate);
	}

	/*Post a balance post if $total != 0 */
	add_gl_balance($trans_type, $payment_id, $date_, -$total, PT_SUPPLIER, $supplier_id);	

   /*now enter the bank_trans entry */
	$id = add_bank_trans_2($trans_type, $payment_id, $bank_account, $ref,
		$date_, -($amount + $supp_charge), PT_SUPPLIER,
		$supplier_id, $bank_account_currency,
		"Could not add the supplier payment bank transaction");
		
	if(get_bank_trans_type($bank_account) == 1)
	{
		// add_check2($payment_id, ST_SUPPAYMENT, $_POST['Bank'], $_POST['Branch'], $_POST['ChkNo'], $_POST['Cheque_Date']);		
		add_checks($payment_id, ST_SUPPAYMENT, $_SESSION['s_checks'], $id);
	}

	add_comments($trans_type, $payment_id, $date_, $memo_);

	$Refs->save($trans_type, $payment_id, $ref);

	commit_transaction();

	return $payment_id;
}

//------------------------------------------------------------------------------------------------

function void_supp_payment($type, $type_no)
{
	begin_transaction();

	//================ for CV =======================
	$sql = "SELECT id FROM ".TB_PREF."bank_trans WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);
	$res = db_query($sql);
	
	$bti = array();
	while($row = db_fetch($res))
	{
		$bti[] = $row[0];
	}
	$bank_trans_id = implode(',',$bti);
	
	//---- supp_allocation of whole CV
	$cv_header_row = get_cv_header_by_bank_trans_id($bank_trans_id);
	$cv_det = get_cv_details($cv_header_row['id']);
	
	$sql = "UPDATE ".TB_PREF."cheque_details SET remark = ".db_escape('CV_ID_'.$cv_header_row['id'])."
			WHERE bank_trans_id = $bank_trans_id";
	db_query($sql,'failed to update cheque details');
	
	while($cv_d_row = db_fetch($cv_det))
	{
		clear_supp_alloctions($cv_d_row['trans_type'], $cv_d_row['trans_no']);
	}
	
	//---- cv_header
	$sql = "UPDATE ".TB_PREF."cv_header SET 
				bank_trans_id=0,
				check_printed=0 
			WHERE bank_trans_id= ". db_escape($bank_trans_id);
	$res = db_query($sql,'failed to update cv_header for supp payment');
	
	//---- cv_detail
	$sql = "DELETE FROM ".TB_PREF."cv_details 
				WHERE trans_type = $type
				AND trans_no = $type_no";
	db_query($sql,'failed to delete cv_detail for supp payment');
	
	//---- check_trans
	// $sql = "DELETE FROM ".TB_PREF."check_trans WHERE bank_trans_id IN (".$bank_trans_id.")";
	// db_query($sql,'failed to delete check_trans for supp payment');
	
	//============================================
	
	void_bank_trans($type, $type_no, true);
	void_gl_trans($type, $type_no, true);
	void_supp_allocations($type, $type_no);
	void_supp_trans($type, $type_no);

	$budget_cv_sql = "SELECT * FROM srs_aria_budgeting.tbl_cv WHERE cv_id = ".$cv_header_row['id']."";
	$budget_cv_query = db_query($budget_cv_sql, 'failed to select cv from tbl_cv budgeting');
	$budget_cv_result = db_fetch_assoc($budget_cv_query);

	if (!empty($budget_cv_result)) {
		$id = $budget_cv_result['id'];
		$cv_delete_sql = "DELETE FROM srs_aria_budgeting.tbl_cv WHERE id = $id";
		$cv_delete_query = db_query($cv_delete_sql, 'failed to delete cv from tbl_cv budgeting');
	}
	
	commit_transaction();
}


?>