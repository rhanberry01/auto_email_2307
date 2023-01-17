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

function add_ar_header($payment_type,$date_,$trans_type,$trans_no,$payment_to_bank,$payee_id,$gross_amount,$or_no,$memo,$payee_name) {

$date_=date2sql($date_);
	
if ($payment_type=='0'){
$payment_type_id='1';
$payment_type='cash';
}

if ($payment_type=='1' or $payment_type=='2'){
$payment_type_id='2';
$payment_type='check';
}

$payee=get_supplier_name($payee_id);


if ($payment_to_bank=='') {
$payment_to_bank=0;
}

$sql = "INSERT INTO ".TB_PREF."ar_header(bd_payment_type_id,bd_payment_type,bd_trans_date,bd_trans_type,bd_trans_no,bd_payment_to_bank,bd_payee_id,bd_payee,bd_gross_amount,bd_or,bd_memo,bd_date_deposited,bd_date_cleared,bd_cleared)				
VALUES ('$payment_type_id','$payment_type','$date_','$trans_type','$trans_no','$payment_to_bank','$payee_id',".db_escape($payee).",'$gross_amount','$or_no',".db_escape($memo).",'0000-00-00','0000-00-00','0')";		
db_query($sql,'unable to add bank deposit header');
//display_error($sql);

$id = db_insert_id();
return $id;
}

function add_ar_details($bd_det_id,$bd_det_date,$bd_det_type,$bd_det_trans_no,$bd_det_amount,$bd_supp_trans_no,$bd_ref,$bd_supp_ref,$tran_date,$bd_det_memo) 
{
$bd_det_date=date2sql($bd_det_date);

$sql = "INSERT INTO ".TB_PREF."ar_details(bd_det_id,bd_det_date,bd_det_type,bd_det_trans_no,bd_det_amount,bd_supp_trans_no,bd_ref,bd_supp_ref,bd_tran_date,bd_det_memo)				
VALUES ('$bd_det_id','$bd_det_date','$bd_det_type','$bd_det_trans_no','$bd_det_amount','$bd_supp_trans_no','$bd_ref','$bd_supp_ref','".$tran_date."',".db_escape($bd_det_memo).")";		
db_query($sql);
//display_error($sql);
}


function process_dm_to_ar($c_id_str,$trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,$ref, $memo_, $use_transaction=true, $checks)
{
	$payment_type=$_POST['payment_type'];
	$payment_from=$_POST['PayType'];
	$payment_to_bank=$_POST['bank_account'];
	$payee_branch=$_POST['PersonDetailID'];
	$or_no=$_POST['or_no'];
	$trans_type=ST_SUPPDMAR;
	$gross_amount=input_num('gross_amount');
	
	if ($use_transaction)
	begin_transaction();
	
	$sql="SELECT st.*,gl.* FROM ".TB_PREF."supp_trans as st 
	LEFT JOIN 0_gl_trans as gl on st.trans_no=gl.type_no 
	where gl.type='53' and st.type='53' and gl.account='1440'
	and st.supplier_id='".$person_id."'
	and cv_id='0'
	and st.ov_amount!=0
	and st.trans_no IN ($c_id_str)
	";
	//display_error($sql);
	
	$dm_res=db_query($sql);
	
	while($dm_row = db_fetch($dm_res))
	{
	$ov_amount+=abs($dm_row['amount']);
	$amount=abs($dm_row['amount']);
	$oi_amount=$ov_amount/1.12;
	$tax_amount=$oi_amount * (12/100);
	//$t_ov_amount+=abs($dm_row['amount']);
	}

	if(get_bank_trans_type($from_account) == '1')
	{
		foreach ($checks->checks as $id=>$check_item)
		{
			$total_amount_ += $check_item->check_amount;
		}		
		//display_error($total_amount_);
		
	}
	else
	{
		$total_amount_ = input_num('cash_amount');
		//display_error($total_amount_);
	}
	
	if ($total_amount_!=$ov_amount){
		//display_error($total_amount_);
		//display_error($ov_amount);
	display_error("Total cash payment amount is not enough or higher.");
	return false;
	}
			
	global $Refs;
	// we can only handle type 1 (payment)and type 2 (deposit)
	 if ($trans_type != ST_SUPPDMAR)
		 display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");
	

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);
	// the gl items are already inversed/negated for type 2 (deposit)
	
	
	
	$sqlid_details="select max(bd_id)+1 as ot_id from ".TB_PREF."ar_header";

	$result_id_details=db_query($sqlid_details);

	while ($ot_id_row = db_fetch($result_id_details))
	{
	$trans_no=$ot_id_row['ot_id'];
	}	

	if (($trans_no=='') or ($trans_no=='0')) {
	$trans_no=$trans_no+1;
	}
	
	//$trans_no = get_next_trans_no($trans_type);
	$ref=$trans_no;
	
	//do the source account postings
	// //-------------

// //BANK_TRANS INSERT
if(get_bank_trans_type($from_account) == '1')
	{
	//---------if check
	//display_error('a');
		foreach ($checks->checks as $id=>$check_item)
		{
			if ($check_item->deleted == true)
				continue;
				
			$id = add_bank_trans_2($trans_type, $trans_no, $from_account, $ref, $check_item->check_date, -$check_item->check_amount,
			$person_type_id, $person_id,$currency,"Cannot insert a source bank transaction");
				
			add_check_details($trans_no,$trans_type,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
			$check_item->check_date,$check_item->check_amount, $id);
			
			issue_check_number2($trans_no, $check_item->check_number, $trans_type, $id);
			$total_amount += $check_item->check_amount;
		}		
	}
	else
	{
	//	-------------cash
	//display_error('b');
		$total_amount = input_num('cash_amount');
		add_bank_trans_2($trans_type, $trans_no, $from_account, $ref,$date_, -$total_amount,$person_type_id, $person_id,$currency,"Cannot insert a source bank transaction");
	}
				
									$id=add_ar_header($payment_type,$date_,$trans_type,$trans_no,$payment_to_bank,$person_id,$ov_amount,$or_no,$memo_,$payee_name);					
									
									$sql="SELECT st.*,gl.* FROM ".TB_PREF."supp_trans as st 
									LEFT JOIN 0_gl_trans as gl on st.trans_no=gl.type_no 
									where gl.type='53' and st.type='53' and gl.account='1440'
									and st.supplier_id='".$person_id."'
									and cv_id='0'
									and st.ov_amount!=0
									and st.trans_no IN ($c_id_str)
									";
									//display_error($sql);

									$dm_res=db_query($sql);

									while($dm_row = db_fetch($dm_res))
									{
									$supp_trans_no=$dm_row['trans_no'];
									$reference=$dm_row['reference'];
									$supp_reference=$dm_row['supp_reference'];
									$supplier_id=$dm_row['supplier_id'];
									$amount=abs($dm_row['amount']);
									$account=$dm_row['account'];
									$counter=$dm_row['counter'];
									$tran_date1=$dm_row['tran_date'];
									add_ar_details($id,$date_,$trans_type,$trans_no,$amount,$supp_trans_no,$reference,$supp_reference,$tran_date1,$memo_);
									}
											
									// //other income
									// if ($rec2_row['ot_total_oi']!=0) {
									// add_gl_trans($trans_type, $trans_no, $date_, $oi_gl_code, 0, 0, $rec_memo, -($rec2_row['ot_total_oi']), null, $person_type_id, $person_id);
									// }
									
									// //output vat
									// if ($rec2_row['ot_total_ot']!=0) {
									// add_gl_trans($trans_type, $trans_no, $date_, $oi_ov_gl, 0, 0, $memo__,-$rec2_row['ot_total_ot'], null, $person_type_id, $person_id);
									// }
									
									// //withholding tax
									// if ($rec2_row['ot_total_wt']!=0) {
									// add_gl_trans($trans_type, $trans_no, $date_, $oi_wt_gl, 0, 0, $memo__,$rec2_row['ot_total_wt'], null, $person_type_id, $person_id);
									// }
											
									// $t_receivable+=$rec2_row['ot_total_receivable'];
									// }
										
									// //cash on hand
									// if ($total_amount!=0) {
									// add_gl_trans($trans_type, $trans_no, $date_, 1010, 0, 0, $memo_,$total_amount, null, $person_type_id, $person_id);
									// }

	$sql = "UPDATE ".TB_PREF."supp_trans SET cv_id=-1 WHERE trans_no IN ($c_id_str)";
	db_query($sql,'failed to set cv_id to -1 for supp_trans');
	
	if (payment_type==0) {
		$gl_account=1000; //petty cash
	}
	else {
		$gl_account=$bank_gl_account; //cash in bank
	}
	
	$sql ="UPDATE ".TB_PREF."gl_trans SET 
	account='".$gl_account."'
	WHERE type = '53'
	AND amount>0
	AND type_no = '".$supp_trans_no."'";
	//display_error($sql);
	db_query($sql, 'failed to update gl_trans');

	
	//add_comments($trans_type, $trans_no, $date_, $memo_);
	//$memo_='DM to AR';
	add_comments(53, $supp_trans_no, $date_, $memo_);
	//$Refs->save($trans_type, $trans_no, $ref);
	
	$desc='DM to AR';
	add_audit_trail($trans_type, $trans_no, $date_,$desc);
	if ($use_transaction)
	commit_transaction();

	return array(53, $supp_trans_no);
}


function process_cash_dep($myBranchCode,$c_id_str,$trans_type, $from_account, $items, $date_, $person_type_id, $person_detail_id,$ref, $memo_, $use_transaction=true, $checks)
{
	
	$bank_account_code=$_POST['bank_account2'];
	
	$payment_type=$_POST['payment_type'];
	$payment_from=$_POST['PayType'];
	$payment_to_bank=$_POST['bank_account'];
	$payee_branch=$_POST['PersonDetailID'];
	$bank_gl_account=$_POST['bank_gl_account'];
	$desc=$_POST['desc'];
	$date_deposit=$_POST['date_deposit'];
	// $trans_type=ST_CASHDEPOSIT;
	

	if ($use_transaction)
	begin_transaction();

	global $Refs;
	
	  $trans_no = get_next_trans_no($trans_type);
	
	//getting total amount
	if(get_bank_trans_type($from_account) == '1')
	{
		foreach ($checks->checks as $id=>$check_item)
		{
			$total_amount_ += $check_item->check_amount;
		}		
		//display_error($total_amount_);

	}
	else
	{
		$total_amount_ = input_num('cash_amount');
	//display_error($total_amount_);
	}
	
	
	// we can only handle type 1 (payment)and type 2 (deposit)
	
	$sqlid_details="select max(bd_id)+1 as ot_id from cash_deposit.".TB_PREF."cash_dep_header";

	$result_id_details=db_query($sqlid_details);

	while ($ot_id_row = db_fetch($result_id_details))
	{
	$trans_no=$ot_id_row['ot_id'];
	}	

	if (($trans_no=='') or ($trans_no=='0')) {
	$trans_no=$trans_no+1;
	}
	
	//$trans_no = get_next_trans_no($trans_type);
	$ref=$trans_no;
	
	$id=add_cash_dep_header($myBranchCode,$payment_type,$date_,$trans_type,$trans_no,$total_amount_,$desc,$memo_,$payee_name,$date_deposit);
	
			$sql_cib="select id,account_code FROM cash_deposit.".TB_PREF."all_bank_accounts where account_code='$bank_account_code'";
			//display_error($sql_cib);
			$result_cib=db_query($sql_cib);

			while ($accountrow = db_fetch($result_cib))
			{
			$bank_id=$accountrow['id'];
			$cash_in_bank=$accountrow['account_code'];
			}

				$sql = "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
				SET cd_bank_account_code='$bank_gl_account',
				cd_bank_deposited='$bank_id'
				WHERE cd_id = '$id'";
				db_query($sql);
	
	if(get_bank_trans_type($from_account) == '1')
	{
		foreach ($checks->checks as $check_id=>$check_item)
		{
			add_cash_dep_details($id,$date_,$trans_type,$trans_no,$check_item->check_amount,$memo_,$payment_to_bank,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,$check_item->check_date);
		}		
	}
	else
	{
		if ($payment_to_bank=='')
		{
			$payment_to_bank=0;
		}

		add_cash_dep_details($id,$date_,$trans_type,$trans_no,$total_amount_,$memo_,$payment_to_bank,$check_bank,$check_branch,$check_number,$check_date);
	}
						

									// //other income
									// if ($rec2_row['ot_total_oi']!=0) {
									// add_gl_trans($trans_type, $trans_no, $date_, $oi_gl_code, 0, 0, $rec_memo, -($rec2_row['ot_total_oi']), null, $person_type_id, $person_id);
									// }
									
									// //output vat
									// if ($rec2_row['ot_total_ot']!=0) {
									// add_gl_trans($trans_type, $trans_no, $date_, $oi_ov_gl, 0, 0, $memo__,-$rec2_row['ot_total_ot'], null, $person_type_id, $person_id);
									// }
									
									// //withholding tax
									// if ($rec2_row['ot_total_wt']!=0) {
									// add_gl_trans($trans_type, $trans_no, $date_, $oi_wt_gl, 0, 0, $memo__,$rec2_row['ot_total_wt'], null, $person_type_id, $person_id);
									// }
											
									// $t_receivable+=$rec2_row['ot_total_receivable'];
									// }
										
									if(date2sql($date_) >= '2018-01-01'){
										//deposit in transit
										if ($total_amount_!=0) {
										add_gl_trans($trans_type, $trans_no, $date_, $cash_in_bank, 0, 0, $memo_,$total_amount_, null, $person_type_id, $person_id);
										}
										
										//cash on hand
										if ($total_amount_!=0) {
										add_gl_trans($trans_type, $trans_no, $date_, 1010, 0, 0, $memo_,-$total_amount_, null, $person_type_id, $person_id);
										}
									}
									else{
										display_error('Not ALLOWED'); die();
									}
									
									

	// $sql = "UPDATE ".TB_PREF."supp_trans SET cv_id=-1 WHERE trans_no IN ($c_id_str)";
	// db_query($sql,'failed to set cv_id to -1 for supp_trans');
	
	// if (payment_type==0) {
		// $gl_account=1000; //petty cash
	// }
	// else {
		// $gl_account=$bank_gl_account; //cash in bank
	// }
	
	// $sql ="UPDATE ".TB_PREF."gl_trans SET 
	// account='".$gl_account."'
	// WHERE type = '53'
	// AND amount>0
	// AND type_no = '".$supp_trans_no."'";
	// //display_error($sql);
	// db_query($sql, 'failed to update gl_trans');

	
	// //add_comments($trans_type, $trans_no, $date_, $memo_);
	// //$memo_='DM to AR';
	// add_comments(53, $supp_trans_no, $date_, $memo_);
	$Refs->save($trans_type, $trans_no, $ref);
	
	// $desc='DM to AR';
	// add_audit_trail($trans_type, $trans_no, $date_,$desc);
	// if ($use_transaction)
	commit_transaction();

	return array(53, $trans_no);
}


function add_cash_dep_header($myBranchCode,$payment_type,$date_,$trans_type,$trans_no,$gross_amount,$desc,$memo,$payee_name,$date_deposit) {
$date_=date2sql($date_);
$date_deposit=date2sql($date_deposit);

if ($date_deposit==''){
	$date_deposit='0000-00-00';
}
	
if ($payment_type=='0'){
$payment_type_id='1';
$payment_type='cash';
}

if ($payment_type=='1' or $payment_type=='2'){
$payment_type_id='2';
$payment_type='check';
}

$today=date2sql(Today());

$sql = "INSERT INTO cash_deposit.".TB_PREF."cash_dep_header(cd_br_code,cd_payment_type_id,cd_payment_type,cd_sales_date,cd_trans_date,cd_trans_type,cd_aria_trans_no,cd_gross_amount,cd_description,cd_memo,cd_date_deposited,cd_date_cleared,cd_cleared,cd_date_deposit)				
VALUES ('$myBranchCode','$payment_type_id','$payment_type','$date_','".$today."','$trans_type',".db_escape($trans_no).",'$gross_amount',".db_escape($desc).",".db_escape($memo).",'0000-00-00','0000-00-00','0','$date_deposit')";		
db_query($sql,'unable to add bank deposit header');

$id = db_insert_id();
return $id;
}


function add_cash_dep_details($cd_det_id,$cd_det_date,$cd_det_type,$cd_det_trans_no,$cd_det_amount,$cd_det_memo,$payment_to_bank,$check_bank,$check_branch,$check_number,$check_date) 
{
$cd_det_date=date2sql($cd_det_date);

if ($check_date=='') {
	$check_date='0000-00-00';
}
else {
	$check_date=date2sql($check_date);
}


$sql = "INSERT INTO cash_deposit.".TB_PREF."cash_dep_details(cd_det_id,cd_sales_date,cd_det_type,cd_det_aria_trans_no,cd_det_amount,cd_chk_bank_id,cd_chk_bank,cd_chk_bank_branch,cd_chk_number,cd_chk_date,cd_tran_date,cd_det_memo)				
VALUES ('$cd_det_id','".$cd_det_date."','$cd_det_type','$cd_det_trans_no','$cd_det_amount','$payment_to_bank',".db_escape($check_bank).",".db_escape($check_branch).",".db_escape($check_number).",'$check_date','".date2sql(Today())."',".db_escape($cd_det_memo).")";		

//display_error($sql);
db_query($sql,'failed to insert details.');
}



function add_exchange_variation($trans_type, $trans_no, $date_, $acc_id, $account,
    $currency, $person_type_id=null, $person_id = "")
{
	if (is_company_currency($currency))
		return;
	if ($date_ == null)
		$date_ = Today();
	$for_amount = 0;

	// We have to calculate all the currency accounts belonging to the GL account
	// upto $date_ and calculate with the exchange rates. And then compare with the GL account balance.
	// 2010-02-23 Joe Hunt with help of Ary Wibowo
	$sql = "SELECT SUM(bt.amount) AS for_amount, ba.bank_curr_code 		
		FROM ".TB_PREF."bank_trans bt, ".TB_PREF."bank_accounts ba
		WHERE ba.id = bt.bank_act AND ba.account_code = ".db_escape($account)." AND bt.trans_date<='".date2sql($date_)."'
		GROUP BY ba.bank_curr_code";	
	$result = db_query($sql, "Transactions for bank account $acc_id could not be calculated");
	while ($row = db_fetch($result))
	{
		if ($row['for_amount'] == 0)
			continue;
		$rate = get_exchange_rate_from_home_currency($row['bank_curr_code'], $date_);
		$for_amount += round2($row['for_amount'] * $rate, user_price_dec());
	}	
	$amount = get_gl_trans_from_to("", $date_, $account);
	$diff = $amount - $for_amount;
	if ($diff != 0)
	{
		if ($trans_type == null)
			$trans_type = ST_JOURNAL;
		if ($trans_no == null)
			$trans_no = get_next_trans_no($trans_type);
		if ($person_type_id == null)
			$person_type_id = PT_MISC;
		add_gl_trans($trans_type, $trans_no, $date_, $account, 0, 0, _("Exchange Variance"),
    		-$diff, null, $person_type_id, $person_id);
    	add_gl_trans($trans_type, $trans_no, $date_, get_company_pref('exchange_diff_act'), 0, 0, 
    		_("Exchange Variance"),	$diff, null, $person_type_id, $person_id);
	}	
}

function add_exchange_variation_all()
{
	$trans_no = get_next_trans_no(ST_JOURNAL);
	$sql = "SELECT * FROM ".TB_PREF."bank_accounts";
	$result = db_query($sql, "could not retrieve bank accounts");
	while ($myrow = db_fetch($result))
		add_exchange_variation(ST_JOURNAL, $trans_no, null, $myrow['id'], $myrow['account_code'],
			$myrow['currency_code']);
}
//----------------------------------------------------------------------------------
//	Add bank tranfer to database.
//
//	$from_account - source bank account id
//	$to_account   -	target bank account id
//	

function add_bank_transfer($from_account, $to_account, $date_,
	$amount, $ref, $memo_, $charge=0)
{
	global $Refs;
	
	begin_transaction();

	$trans_type = ST_BANKTRANSFER;

	$currency = get_bank_account_currency($from_account);

	$trans_no = get_next_trans_no($trans_type);

	$from_gl_account = get_bank_gl_account($from_account);
	$to_gl_account = get_bank_gl_account($to_account);

	$total = 0;
	// do the source account postings
    $total += add_gl_trans($trans_type, $trans_no, $date_, $from_gl_account, 0, 0, "",
		-($amount + $charge), $currency);

    add_bank_trans($trans_type, $trans_no, $from_account, $ref,
		$date_, -($amount + $charge),
		PT_MISC, "", $currency,
		"Cannot insert a source bank transaction");

	add_exchange_variation($trans_type, $trans_no, $date_, $from_account, $from_gl_account, 
		$currency, PT_MISC, "");

	if ($charge != 0)
	{
		/* Now Debit bank charge account with charges */
		$charge_act = get_company_pref('bank_charge_act');
		$total += add_gl_trans($trans_type, $trans_no, $date_,
			$charge_act, 0, 0, "", $charge, $currency);
	}
	// do the destination account postings
	$total += add_gl_trans($trans_type, $trans_no, $date_, $to_gl_account, 0, 0, "",
		$amount, $currency);
		
	/*Post a balance post if $total != 0 */
	add_gl_balance($trans_type, $trans_no, $date_, -$total);	
	
	add_bank_trans($trans_type, $trans_no, $to_account, $ref,
		$date_, $amount, PT_MISC, "",
		$currency, "Cannot insert a destination bank transaction");

	$currency = get_bank_account_currency($to_account);
	
	add_exchange_variation($trans_type, $trans_no, $date_, $to_account, $to_gl_account, 
		$currency, PT_MISC, "");
	
	add_comments($trans_type, $trans_no, $date_, $memo_);

	$Refs->save($trans_type, $trans_no, $ref);
	add_audit_trail($trans_type, $trans_no, $date_);

	commit_transaction();

	return $trans_no;
}

//----------------------------------------------------------------------------------
//	Add bank payment or deposit to database.
//
//	$from_account - bank account id
//  $item - transaction cart (line item's amounts in bank account's currency)
//  $person_type_id - defines type of $person_id identifiers
//  $person_id	- supplier/customer/other id
// $person_detail_id - customer branch id or not used
//
// returns an array of (inserted trans type, trans no)

function add_bank_transaction($trans_type, $from_account, $items, $date_,
	$person_type_id, $person_id, $person_detail_id,	$ref, $memo_, $use_transaction=true)
{
	global $Refs;

	// we can only handle type 1 (payment)and type 2 (deposit)
	if ($trans_type != ST_BANKPAYMENT && $trans_type != ST_BANKDEPOSIT)
		display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");

	$do_exchange_variance = false;
	
	if ($use_transaction)
		begin_transaction();

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);

	// the gl items are already inversed/negated for type 2 (deposit)
	$total_amount = $items->gl_items_total();

    // if ($person_type_id == PT_CUSTOMER)
    // {
    	// // we need to add a customer transaction record

		// // convert to customer currency 
		// $cust_amount = exchange_from_to($total_amount, $currency, get_customer_currency($person_id), $date_);
		// // we need to negate it too
		// $cust_amount = -$cust_amount;

		// $trans_no = write_customer_trans($trans_type, 0, $person_id, $person_detail_id, $date_,
        	// $ref, $cust_amount);

    // }
    // elseif ($person_type_id == PT_SUPPLIER)
    if ($person_type_id == PT_SUPPLIER)
    {
    	// we need to add a supplier transaction record
		// convert to supp currency
		$supp_amount = exchange_from_to($total_amount, $currency, get_supplier_currency($person_id), $date_);

		// we need to negate it too
		$supp_amount = -$supp_amount;

		$trans_no = add_supp_trans($trans_type, $person_id, $date_, '',
    		$ref, "", $supp_amount, 0, 0);

    }
    else
    {
    	$trans_no = get_next_trans_no($trans_type);
    	$do_exchange_variance = true;
    }

	// do the source account postings

    $id = add_bank_trans_2($trans_type, $trans_no, $from_account, $ref,
    	$date_, -$total_amount,
    	$person_type_id, $person_id,
    	$currency,
    	"Cannot insert a source bank transaction");
	$total = 0;
	
	// ewt
	$ewt = 0;
	$ewt_act = get_company_pref('default_purchase_ewt_act');
	
	// disc
	$disc=0;
	$disc_act = get_company_pref('pyt_discount_act');
	
	// input tax
	$input_tax=0;
		
	foreach ($items->gl_items as $gl_item)
	{
		$is_bank_to = is_bank_account($gl_item->code_id);

		if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
		{
			// we don't allow payments to go to a bank account. use transfer for this !
			display_db_error("invalid payment entered. Cannot pay to another bank account", "");
		}

    	// do the destination account postings
    	$total += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,
    		$gl_item->dimension_id, $gl_item->dimension2_id, $gl_item->reference,
    		$gl_item->amount, $currency, $person_type_id, $person_id);
	
		// ewt
		if($gl_item->code_id == $ewt_act)
			$ewt += $gl_item->amount;
			
		// disc
		if($gl_item->code_id == $disc_act)
			$disc += $gl_item->amount;
			
		// input tax			
		$sql = "SELECT COUNT(*) FROM ".TB_PREF."tax_types WHERE purchasing_gl_code IN (".db_escape($gl_item->code_id).")";
		$result = db_query($sql,"could not retrieve purchasing_gl_code");
		$row = db_fetch_row($result);
		
		if($row[0] != 0)
			$input_tax += $gl_item->amount;

    	if ($is_bank_to)
    	{
    		add_bank_trans($trans_type, $trans_no, $is_bank_to, $ref,
    			$date_, $gl_item->amount,
    			$person_type_id, $person_id, $currency,
    			"Cannot insert a destination bank transaction");
    		if ($do_exchange_variance)
    			add_exchange_variation($trans_type, $trans_no, $date_, $is_bank_to, $gl_item->code_id, 
    				$currency, $person_type_id, $person_id);
    	}
		// store tax details if the gl account is a tax account

		$amount = $gl_item->amount;
		$ex_rate = get_exchange_rate_from_home_currency($currency, $date_);
			
		add_gl_tax_details($gl_item->code_id, $trans_type, $trans_no, -$amount,
			$ex_rate, $date_, $memo_);
			
	}

	// do the source account postings
    add_gl_trans($trans_type, $trans_no, $date_, $bank_gl_account, 0, 0, $memo_,
    	-$total, null, $person_type_id, $person_id);

    if ($do_exchange_variance)
    	add_exchange_variation($trans_type, $trans_no, $date_, $from_account, $bank_gl_account, 
    		$currency, $person_type_id, $person_id);

	add_comments($trans_type, $trans_no, $date_, $memo_);
	
	if ($person_type_id == PT_MISC)
		add_books_disbursements($date_, $person_id, $trans_type, $trans_no, $ref, $total_amount-$ewt-$input_tax-$disc, $ewt, $input_tax, $disc);

	$Refs->save($trans_type, $trans_no, $ref);
	add_audit_trail($trans_type, $trans_no, $date_);

	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no, $id);
}

//----------------------------------------------------------------------------------------

function add_books_disbursements($date, $supplier_id, $trans_type, $trans_no, $reference, $purchase_amount=0, $ewt=0, $input_tax=0, $discount=0, $returns=0)
{
	$sql = "INSERT  INTO ".TB_PREF."books_disbursements(date, supplier_id, trans_type, trans_no, reference, purchase_amount, ewt, input_tax, discount, returns)
			VALUES(".db_escape(date2sql($date)).", ".db_escape($supplier_id).", ".db_escape($trans_type).", ".db_escape($trans_no).", 
				".db_escape($reference).", $purchase_amount, $ewt, $input_tax, $discount, $returns)";
			
	db_query($sql,'unable to add disbursements');
}

//----------------------------------------------------------------------------------------
//without receivables
function add_bank_transaction_2($trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,	$ref, $memo_, $use_transaction=true, $checks)
{
	$payment_type=$_POST['payment_type'];
	$ref=$_POST['ref'];
	$payment_from=$_POST['PayType'];
	$payee=$_POST['person_id'];
	$payment_to_bank=$_POST['bank_account'];
	$payee_branch=$_POST['PersonDetailID'];
	$memo=$_POST['memo_'];
	$trans_type=ST_BANKDEPOSIT;
			
	global $Refs;
	// we can only handle type 1 (payment)and type 2 (deposit)
	if ($trans_type != ST_BANKPAYMENT && $trans_type != ST_BANKDEPOSIT)
		display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");
	
	if ($use_transaction)
		begin_transaction();

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);
	// the gl items are already inversed/negated for type 2 (deposit)
	$total_amount = $items->gl_items_total();
    $trans_no = get_next_trans_no($trans_type);
	// do the source account postings
	//-------------


//BANK_TRANS INSERT
if(get_bank_trans_type($from_account) == '1')
	{
		foreach ($checks->checks as $id=>$check_item)
		{
			if ($check_item->deleted == true)
				continue;
				
			$id = add_bank_trans_2($trans_type, $trans_no, $from_account, $ref, $check_item->check_date, -$check_item->check_amount,
			$person_type_id, $person_id,$currency,"Cannot insert a source bank transaction");
				
			add_check3($trans_no,$trans_type,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
			$check_item->check_date,$check_item->check_amount, $id);
			
			issue_check_number2($trans_no, $check_item->check_number, $trans_type, $id);
		}		
	}
	else
	{
		add_bank_trans($trans_type, $trans_no, $from_account, $ref,$date_, -$total_amount,$person_type_id, $person_id,$currency,
		"Cannot insert a source bank transaction");
	}
	
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
	
	$total = 0;
	// ewt
	$ewt = 0;
	$ewt_act = get_company_pref('default_purchase_ewt_act');
	
	// disc
	$disc=0;
	$disc_act = get_company_pref('pyt_discount_act');
	
	// input tax
	$input_tax=0;
								
						//---------START OF BANK DEPOSIT DETAILS-------------
											foreach ($items->gl_items as $gl_item)
											{
												$is_bank_to = is_bank_account($gl_item->code_id);

												if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
												{
													// we don't allow payments to go to a bank account. use transfer for this !
													display_db_error("invalid payment entered. Cannot pay to another bank account", "");
												}

												// do the destination account postings
												$total += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,$gl_item->dimension_id, $gl_item->dimension2_id,
												$gl_item->reference,$gl_item->amount, $currency, $person_type_id, $person_id);
												add_gl_trans($trans_type, $trans_no, $date_, 1100, 0, 0, $gl_item->reference,-$gl_item->amount, null, $person_type_id, $person_id);
												
												add_bank_deposit_details($date_,$trans_type,$trans_no,$ref,$gl_item->code_id,abs($gl_item->amount),$gl_item->reference);
												// ewt
												if($gl_item->code_id == $ewt_act)
													$ewt += $gl_item->amount;
													
												// disc
												if($gl_item->code_id == $disc_act)
													$disc += $gl_item->amount;
													
												// input tax			
												$sql = "SELECT COUNT(*) FROM ".TB_PREF."tax_types WHERE purchasing_gl_code IN (".db_escape($gl_item->code_id).")";
												$result = db_query($sql,"could not retrieve purchasing_gl_code");
												$row = db_fetch_row($result);
												
												if($row[0] != 0)
													$input_tax += $gl_item->amount;

												if ($is_bank_to)
												{
													add_bank_trans($trans_type, $trans_no, $is_bank_to, $ref,$date_, $gl_item->amount,
													$person_type_id, $person_id, $currency,
													"Cannot insert a destination bank transaction");
												}
												// store tax details if the gl account is a tax account

												$amount = $gl_item->amount;
												}
								//--------END OF BANK DEPOSIT DETAILS--------

									
								//add_gl_trans($trans_type, $trans_no, $date_, 1100, 0, 0, $memo_,$total_amount, null, $person_type_id, $person_id);
								//add_gl_trans($trans_type, $trans_no,$date_, $cash_in_transit, 0, 0, $memo_,-$total_amount, null, $person_type_id, $person_id);


	//add_comments($trans_type, $trans_no, $date_, $memo_);
	//$Refs->save($trans_type, $trans_no, $ref);
	add_bank_deposit_header($payment_type,$date_,$trans_type,$trans_no,$ref,$payment_from,$payment_to_bank,$payee,
	$payee_branch,$total_amount,$memo);
	$desc='Created';
	add_audit_trail($trans_type, $trans_no, $date_,$desc);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}

//with receivables
function add_receivable_payment($trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id, $memo_, $use_transaction=true,$checks,$cash_amount,$trans_no,$payment_type,$bd_vat,$bd_wt)
{
	$payment_type=$_POST['payment_type'];
	$ref=$_POST['ref'];
	$payment_from=$_POST['PayType'];
	$payee=$_POST['person_id'];
	$payment_to_bank=$_POST['bank_account'];
	$payee_branch=$_POST['PersonDetailID'];
	$memo=$_POST['memo_'];
	$trans_type=ST_BANKDEPOSIT;			
	
	if ($use_transaction)
		begin_transaction();

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);
	// the gl items are already inversed/negated for type 2 (deposit)
												//$total_amount = $items->gl_items_total(); ONE REASON
   // $trans_no = get_next_trans_no($trans_type);
	// do the source account postings
	//-------------

		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
								
						//---------START OF BANK DEPOSIT DETAILS-------------
											if ($payment_type=='0') {
											$cash_amount=str_replace(',','',$cash_amount);
											add_gl_trans($trans_type, $trans_no, $date_, $cash_in_transit, 0, 0, $memo_,$cash_amount, null, $person_type_id, $person_id);
											add_bank_deposit_header($payment_type,$date_,$trans_type,$trans_no,$ref,$payment_from,$payment_to_bank,$payee,$payee_branch,$cash_amount,$bd_vat,$bd_wt,$memo);
											}
											
											
											if ($payment_type=='1') {

											foreach ($checks->checks as $id=>$check_item)
											{
											if ($check_item->deleted == true)
											continue;

											add_check3($trans_no,$trans_type,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
											$check_item->check_date,$check_item->check_amount, $id);
											//issue_check_number2($trans_no, $check_item->check_number, $trans_type, $id);
											$total_check+=$check_item->check_amount;
											}		
											add_gl_trans($trans_type, $trans_no, $date_, $check_in_transit, 0, 0, $memo_,$total_check, null, $person_type_id, $person_id);
											add_bank_deposit_header($payment_type,$date_,$trans_type,$trans_no,$ref,$payment_from,$payment_to_bank,$payee,$payee_branch,$total_check,$bd_vat,$bd_wt,$memo);
											}
			
								//add_gl_trans($trans_type, $trans_no, $date_, 1100, 0, 0, $memo_,$total_amount, null, $person_type_id, $person_id);
								//add_gl_trans($trans_type, $trans_no, $date_, $cash_in_transit, 0, 0, $memo_,-$total_amount, null, $person_type_id, $person_id);


	$desc='Created';
	add_audit_trail($trans_type, $trans_no, $date_,$desc);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}

//----------------------------------------------------------------------------------------
function add_bank_deposit_header($payment_type,$date_,$bd_trans_type,$trans_no,$ref,$payment_from_id,$payment_to_bank,
$payee_id,$payee_branch_id,$total_amount,$bd_vat,$bd_wt,$memo) {

	$date_=date2sql($date_);
	
if ($payment_type=='0'){
$payment_type='cash';
}

if ($payment_type=='1'){
$payment_type='check';
}

if ($payment_from_id=='0'){
$payment_from='Miscellaneous';
}
if ($payment_from_id=='2'){
$payment_from='Customer';
$payee_branch=get_customer_branch($payee_id,$payee_branch_id);
$payee=get_customer_name($payee_id);
}
if ($payment_from=='3'){
$payment_from='Supplier';
$payee=get_supplier_name($payee_id);
}


$sql = "INSERT INTO ".TB_PREF."bank_deposit_header(bd_payment_type,bd_trans_date,bd_trans_type,bd_trans_no,
bd_reference,bd_payment_from_id,bd_payment_from,bd_payment_to_bank,bd_payee_id,bd_payee,bd_cust_branch_id,bd_cust_branch,bd_amount,bd_vat,bd_wt,bd_memo)				
VALUES ('$payment_type','$date_','$bd_trans_type','$trans_no','$ref','$payment_from_id','$payment_from','$payment_to_bank','$payee_id','$payee','$payee_branch_id','$payee_branch','$total_amount','$bd_vat','$bd_wt','$memo')";		
db_query($sql,'unable to add bank deposit header');
//display_error($sql);
}

//----------------------------------------------------------------------------------------

function add_bank_deposit_details($bd_det_date,$bd_det_type,$bd_det_trans_no,$bd_det_ref,$bd_det_gl_code,$bd_det_amount,$bd_det_memo) 
{
$bd_det_date=date2sql($bd_det_date);

$sql = "INSERT INTO ".TB_PREF."bank_deposit_details(bd_det_date,bd_det_type,bd_det_trans_no,bd_det_ref,bd_det_gl_code,bd_det_amount,bd_det_memo)				
VALUES ('$bd_det_date','$bd_det_type','$bd_det_trans_no','$bd_det_ref','$bd_det_gl_code','$bd_det_amount','$bd_det_memo')";		
db_query($sql,'unable to add bank deposit details');
//display_error($sql);
}

function add_other_income_payment_details($bd_det_date,$bd_det_type,$bd_det_trans_no,$bd_det_ref,$bd_det_gl_code,$bd_det_amount,$bd_det_wt,$bd_det_ov,$bd_det_memo) 
{
$bd_det_date=date2sql($bd_det_date);

$sql = "INSERT INTO ".TB_PREF."other_income_payment_details(bd_det_date,bd_det_type,bd_det_trans_no,bd_det_ref,bd_det_gl_code,bd_det_amount,bd_det_wt,bd_det_ov,bd_det_memo)				
VALUES ('$bd_det_date','$bd_det_type','$bd_det_trans_no','$bd_det_ref','$bd_det_gl_code','$bd_det_amount','$bd_det_wt','$bd_det_ov',".db_escape($bd_det_memo).")";		
db_query($sql,'unable to add other income payment details');
//display_error($sql);
}
//----------------------------------------------------------------------------------------

function add_check3($trans_id,$type,$bank,$bank_branch,$chk_number,$date,$amount=0, $id)
{
	if($type == ST_BANKPAYMENT || $type == ST_SUPPAYMENT)
		$amount = -$amount;

	$sql = "INSERT  INTO ".TB_PREF."bank_deposit_cheque_details(bank_trans_id, bank, branch, chk_number, chk_date, type, chk_amount, deposited, bank_id)
			VALUES($trans_id, ".db_escape($bank).", ".db_escape($bank_branch).", ".db_escape($chk_number).", '".date2sql($date)."', $type, $amount, 1, $id)";
			
	db_query($sql,'unable to add check details');
}

function add_other_income_header($trans_type,$date_,$payment_from_id,$payment_from,$payment_to_bank,$payee_id,$payee_branch_id,$t_receivable,$total_wt,$total_ot,$t_amount,$paid,$cleared,$memo) 
{
	$date_=date2sql($date_);
	$t_receivable=abs($t_receivable);
	$total_wt=abs($total_wt);
	$total_ot=abs($total_ot);
	$t_amount=abs($t_amount);
	$paid='0';
	$cleared='0';

if($payment_from_id==PT_MISC) {
$payment_from='Miscellaneous';
}

if($payment_from_id==PT_CUSTOMER) {
$payment_from='Customer';
$payee_branch=get_customer_branch($payee_id,$payee_branch_id);
$payee=get_customer_name($payee_id);
}

if($payment_from_id==PT_SUPPLIER) {
$payment_from='Supplier';
$payee=get_supplier_name($payee_id);
}

$sql = "INSERT INTO ".TB_PREF."other_income_header(ot_type,ot_trans_date,ot_trans_from_id,ot_trans_from_desc,ot_payee_id,ot_payee,ot_cust_branch_id,
ot_cust_branch,ot_total_receivable,ot_total_wt,ot_total_ot,ot_total_amount,ot_paid,ot_cleared,ot_memo)				
VALUES ('$trans_type','$date_','$payment_from_id','$payment_from','$payee_id','$payee','$payee_branch_id','$payee_branch','$t_receivable','$total_wt','$total_ot','$t_amount','$paid','$cleared','$memo')";		
db_query($sql,'unable to add bank deposit header');
//display_error($sql);
}



function add_create_other_income($trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,$ref, $memo_, $use_transaction=true, $checks)
{
	$payment_type=$_POST['payment_type'];
	$payment_from=$_POST['PayType'];
	$payee=$_POST['person_id'];
	$payment_to_bank=$_POST['bank_account'];
	$memo=$_POST['memo_'];
	$trans_type=ST_BANKDEPOSIT;
	$payee_branch=$_POST['PersonDetailID'];
				

		$sqlid_details="select max(ot_id)+1 as ot_id from ".TB_PREF."other_income_header";
		
		$result_id_details=db_query($sqlid_details);
		
		while ($ot_id_row = db_fetch($result_id_details))
		{
		$trans_no=$ot_id_row['ot_id'];
		}	
		
	if (($trans_no=='') or ($trans_no=='0')) {
	$trans_no=$trans_no+1;
	}
				
	global $Refs;

	if ($use_transaction)
		begin_transaction();

		$total_amount = $items->gl_items_total();
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
	
	$total = 0;

											//---------START OF CREATE OTHER INCOME DETAILS-------------
											foreach ($items->gl_items as $gl_item)
											{

											//other income items
											if (($gl_item->code_id!='2310') and ($gl_item->code_id!='1400')) {
											$total += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,$gl_item->dimension_id, $gl_item->dimension2_id,
											$gl_item->reference, -$gl_item->amount, $currency, $person_type_id, $person_id);
											}

											//output vat
											if ($gl_item->code_id=='2310') {
											$total_ot += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,$gl_item->dimension_id, $gl_item->dimension2_id,
											$gl_item->reference,-$gl_item->amount, $currency, $person_type_id, $person_id);
											}

											//withholding tax
											if ($gl_item->code_id=='1400') {
											$total_wt += add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,$gl_item->dimension_id, $gl_item->dimension2_id,
											$gl_item->reference,$gl_item->amount, $currency, $person_type_id, $person_id);
											}

											$amount = $gl_item->amount;
											}
											//--------END OF CREATE OTHER INCOME DETAILS-------------
											$t_amount=abs($total)+abs($total_ot);
											$t_receivable=(abs($total)+abs($total_ot))-abs($total_wt);
											add_gl_trans($trans_type, $trans_no, $date_, 1100, 0, 0, $memo_,-$t_receivable, null, $person_type_id, $person_id);
										
								
											
	add_other_income_header($trans_type,$date_,$payment_from,$payment_from_desc,$payment_to_bank,$payee,$payee_branch,$t_receivable,$total_wt,$total_ot,$t_amount,$paid,$cleared,$memo);
	$desc='Created';
	add_audit_trail($trans_type, $trans_no, $date_,$desc);
	if ($use_transaction)
		commit_transaction();	
	return array($trans_type, $trans_no);
}


//======================================================NEW=============================================
function add_other_income_receivable($trans_type,$date_,$payment_from_id,$payment_from,$payment_to_bank,$payee_id,$payee_branch_id,$total_receivable,$total_wt,$total_vat,$amount,$other_income,$other_income_type_id,$paid,$cleared,$memo) 
{

	$date_=date2sql($date_);
	$total_receivable=abs(round2($total_receivable,2));
	$total_wt=abs(round2($total_wt,2));
	$total_vat=abs(round2($total_vat,2));
	$amount=abs(round2($amount,2));
	$other_income=abs(round2($other_income,2));
	$paid='0';
	$cleared='0';
	$ot_payment_id='0';

if($payment_from_id==PT_MISC) {
$payment_from='Miscellaneous';
$payee=$payee_id;
$payee_id=0;
$payee_branch_id=0;
}

if($payment_from_id==PT_CUSTOMER) {
$payment_from='Customer';
$payee_branch=get_customer_branch($payee_id,$payee_branch_id);
$payee=get_customer_name($payee_id);
}

if($payment_from_id==PT_SUPPLIER) {
$payment_from='Supplier';
$payee=get_supplier_name($payee_id);
$payee_branch_id=0;
}

$other_income_type_name=get_other_income_type_name($other_income_type_id);

$sql = "INSERT INTO ".TB_PREF."other_income_receivable(ot_type,ot_trans_date,ot_trans_from_id,ot_trans_from_desc,ot_payee_id,ot_payee,ot_cust_branch_id,
ot_cust_branch,ot_rec_type_id,ot_rec_type_name,ot_total_receivable,ot_total_wt,ot_total_ot,ot_total_oi,ot_total_amount,ot_payment_id,ot_paid,ot_cleared,ot_memo)				
VALUES ('$trans_type','$date_','$payment_from_id','$payment_from','$payee_id','$payee','$payee_branch_id','$payee_branch','$other_income_type_id','$other_income_type_name','$total_receivable','$total_wt','$total_vat','$other_income','$amount','$ot_payment_id','$paid','$cleared','$memo')";		
db_query($sql,'unable to add bank deposit header');
//display_error($sql); 
}


function create_other_income($trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,$ref, $memo_, $use_transaction=true, $checks)
{
	$payment_type=$_POST['payment_type'];
	$payment_from=$_POST['PayType'];
	$payee=$_POST['person_id'];
	$payee_branch=$_POST['PersonDetailID'];
	$memo=$_POST['memo_'];
	$other_income_type_id=$_POST['rec_type'];
	$amount=input_num('amount');
	$trans_type=ST_BANKDEPOSIT;
	$payment_to_bank=$_POST['bank_account'];
	if ($payment_to_bank=='')
	{
	$payment_to_bank=0;
	}
						
	global $Refs;

	if ($use_transaction)
		begin_transaction();
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
		
		$sqlrec="select * from ".TB_PREF."other_income_type where oi_id='$other_income_type_id' and inactive='0'";
		//display_error($sqlrec);
		$result_=db_query($sqlrec);
		

		while ($rec_row = db_fetch($result_))
		{
		$ot_name=$rec_row["oi_type_name"];
		$ot_gl_debit_account=$rec_row["oi_gl_debit"];
		$ot_vat_gl=$rec_row["oi_ov_gl"];
		$ot_wt_gl=$rec_row["oi_wt_gl"];
		$ot_vat_percent=$rec_row["oi_ov"];
		$ot_wt_percent=$rec_row["oi_wt"];
		}
		
		if ($ot_vat_percent!=0) { 
		$vat_exclusive=$amount/$ot_vat_percent;
		$total_vat=$vat_exclusive-$amount;
		$other_income=$vat_exclusive;
		}
		else {
		$total_vat=0;
		$other_income=$amount;
		}
		
		if ($ot_wt_percent!=0) {
		$total_wt=$vat_exclusive * $ot_wt_percent;
		$total_receivable=$amount-$total_wt;
		}
		else {
		$total_wt=0;
		$total_receivable=$amount;
		}
		
											
	add_other_income_receivable($trans_type,$date_,$payment_from,$payment_from_desc,$payment_to_bank,$payee,$payee_branch,$total_receivable,$total_wt,$total_vat,$amount,$other_income,$other_income_type_id,$paid,$cleared,$memo);
	$desc='Created';
	
	
		$sqlid_details="select max(ot_id) as ot_id from ".TB_PREF."other_income_receivable";
		
		$result_id_details=db_query($sqlid_details);
		
		while ($ot_id_row = db_fetch($result_id_details))
		{
		$trans_no=$ot_id_row['ot_id'];
		}	
		
	if (($trans_no=='') or ($trans_no=='0')) {
	$trans_no=$trans_no+1;
	}
		
	add_audit_trail($trans_type, $trans_no, $date_,$desc);
	if ($use_transaction)
		commit_transaction();	
	return array($trans_type, $trans_no);
}




function add_check_details($trans_id,$type,$bank,$bank_branch,$chk_number,$date,$amount=0, $id)
{
	if($type == ST_BANKPAYMENT || $type == ST_SUPPAYMENT)
		$amount = -$amount;

	$sql = "INSERT  INTO ".TB_PREF."bank_deposit_cheque_details(bank_trans_id, bank, branch, chk_number, chk_date, receive, type, chk_amount, deposited, bank_id)
			VALUES($trans_id, ".db_escape($bank).", ".db_escape($bank_branch).", ".db_escape($chk_number).", '".date2sql($date)."', 1, $type, $amount, 0, $id)";
			
	db_query($sql,'unable to add check details');
}


//-----	WITH RECEIVABLES
function add_other_income_process_rec_payment($c_id_str,$trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,$ref, $memo_, $use_transaction=true, $checks,$or_no,$saf,$paid_online){
//display_error("THIS IS WITH RECEIVABLES");
	$receipt_type=$_POST['receipt_type'];
	$payment_type=$_POST['payment_type'];
	$ref=$_POST['ref'];
	$payment_from=$_POST['PayType'];
	$payee=$_POST['person_id'];
	$payment_to_bank=$_POST['bank_account'];
	$payee_branch=$_POST['PersonDetailID'];
	$memo=$_POST['memo_'];
	$or_no=$_POST['or_no'];
	$trans_type=ST_BANKDEPOSIT;
	$other_income_type_id=$_POST['rec_type'];
	$gross_amount=input_num('gross_amount');

		if ($use_transaction)
		begin_transaction();
	
	$sqlrec="select ot_payee,sum(ot_total_amount) as t_gross_amount, sum(ot_total_ot) as t_ov, sum(ot_total_wt) as t_wt,sum(ot_total_oi) as t_oi,
	sum(ot_total_receivable) as t_receivable
	from ".TB_PREF."other_income_receivable where ot_id IN ($c_id_str)";
	//display_error($sqlrec);
	$result_=db_query($sqlrec);
	$rec_row=db_fetch($result_);

	$gross_amount=$rec_row["t_gross_amount"];
	$total_vat=$rec_row["t_ov"];
	$total_wt=$rec_row["t_wt"];
	$total_oi=$rec_row["t_oi"];
	$total_receivable=$rec_row["t_receivable"];
	$payee_name=$rec_row["ot_payee"];

	
	if(get_bank_trans_type($from_account) == '1')
	{
		foreach ($checks->checks as $id=>$check_item)
		{
			$total_amount_ += $check_item->check_amount;
		}		
	}
	else
	{
		$total_amount_ = input_num('cash_amount');
	}
	
	if ($total_amount_!=$total_receivable){
	display_error("Total cash payment amount is not enough or higher.");
	return false;
	}
			
	global $Refs;
	// we can only handle type 1 (payment)and type 2 (deposit)
	if ($trans_type != ST_BANKDEPOSIT)
		display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");
	

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);
	// the gl items are already inversed/negated for type 2 (deposit)
    $trans_no = get_next_trans_no($trans_type);
	// do the source account postings
	//-------------
	$ref=$trans_no;
//BANK_TRANS INSERT
if(get_bank_trans_type($from_account) == '1')
	{
	//---------if check
		foreach ($checks->checks as $id=>$check_item)
		{
			if ($check_item->deleted == true)
				continue;
				
			$id = add_bank_trans_2($trans_type, $trans_no, $from_account, $ref, $check_item->check_date, -$check_item->check_amount,
			$person_type_id, $person_id,$currency,"Cannot insert a source bank transaction");
				
			add_check_details($trans_no,$trans_type,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
			$check_item->check_date,$check_item->check_amount, $id);
			
			//issue_check_number2($trans_no, $check_item->check_number, $trans_type, $id);
			$total_amount += $check_item->check_amount;
		}		
	}
	else
	{
	//-------------cash
		$total_amount = input_num('cash_amount');
		add_bank_trans_2($trans_type, $trans_no, $from_account, $ref,$date_, -$total_amount,$person_type_id, $person_id,$currency,
		"Cannot insert a source bank transaction");
	}
	
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
					
							//--------START OF OI PAYMENT DETAILS--------
											$sqlrec2="select * from ".TB_PREF."other_income_receivable where ot_id IN ($c_id_str)";
											//display_error($sqlrec2);
											$result_2=db_query($sqlrec2);
											
											while ($rec2_row=db_fetch($result_2)) 
											{
											$rec_id=$rec2_row['ot_rec_type_id'];
											$rec_memo=$rec2_row['ot_memo'];
											//display_error($rec_id);
											
											//to select gl of receivable
											$sql="select * from ".TB_PREF."other_income_type where oi_id='$rec_id'";
											//display_error($sql);
											$res=db_query($sql);
											$row=db_fetch($res);
											$oi_gl_code=$row['oi_gl_debit'];
											$oi_ov_gl=$row['oi_ov_gl'];
											$oi_wt_gl=$row['oi_wt_gl'];
						
												
							$is_bank_to = is_bank_account($oi_gl_code);
							if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
							{
								// we don't allow payments to go to a bank account. use transfer for this !
								display_db_error("invalid payment entered. Cannot pay to another bank account", "");
							}	
										add_other_income_payment_details($date_,$trans_type,$trans_no,$ref,$oi_gl_code,
										$rec2_row['ot_total_receivable'],$rec2_row['ot_total_wt'],$rec2_row['ot_total_ot'],$rec_memo);
											
									//other income
									if ($rec2_row['ot_total_oi']!=0) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_gl_code, 0, 0, $rec_memo, -($rec2_row['ot_total_oi']), null, $person_type_id, $person_id);
									}
									
									//output vat
									if ($rec2_row['ot_total_ot']!=0) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_ov_gl, 0, 0, $memo__,-$rec2_row['ot_total_ot'], null, $person_type_id, $person_id);
									}
									
									//withholding tax
									if ($rec2_row['ot_total_wt']!=0) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_wt_gl, 0, 0, $memo__,$rec2_row['ot_total_wt'], null, $person_type_id, $person_id);
									}
											
									$t_receivable+=$rec2_row['ot_total_receivable'];
											}
										
								//cash on hand
								if ($total_amount!=0) {
								add_gl_trans($trans_type, $trans_no, $date_, 1010, 0, 0, $memo_,$total_amount, null, $person_type_id, $person_id);
								}
																						
						//--------END OF OI PAYMENT DETAILS--------
												


	//add_comments($trans_type, $trans_no, $date_, $memo_);
	//$Refs->save($trans_type, $trans_no, $ref);
	add_bank_deposit_header2($payment_type,$date_,$trans_type,$trans_no,$ref,$payment_from,$payment_to_bank,$payee,$payee_branch,$gross_amount,$total_receivable,$total_vat,$total_wt,$total_oi,$or_no,$memo,$other_income_type_id,$payee_name,$receipt_type,$saf,$paid_online);
	$desc='Paid';
	
	
	$sqlupdate_rec ="UPDATE ".TB_PREF."other_income_receivable SET ot_payment_id='$trans_no', ot_paid='1' WHERE ot_id IN ($c_id_str)";
	//display_error($sqlupdate_rec);
	db_query($sqlupdate_rec);	
	
	
	add_audit_trail($trans_type, $trans_no, $date_,$desc);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}



//-----	WITHOUT RECEIVABLES
function add_other_income_process_payment($c_id_str,$trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,$ref, $memo_, $use_transaction=true, $checks,$or_no,$saf,$paid_online)
{
		if ((($_POST['rec_type']==0) or ($_POST['rec_type']=='')) and ($_POST['payment_type']!=2))
	{
		display_error(_("Please select receivable type."));
		set_focus('date_');
		$input_error = 1;
	}
	
		if ((($_POST['gross_amount']==0) or ($_POST['gross_amount']=='')) and ($_POST['payment_type']!=2))
	{
		display_error(_("Gross Amount cannot be empty or zero."));
		set_focus('date_');
		$input_error = 1;
	}
	
			if (($_POST['person_id'])=='')
	{
		display_error(_("Please select or provide where the receivable came from."));
		set_focus('date_');
		$input_error = 1;
	}
	
		if ($input_error == 1)
		//unset($_POST['Process']);
		return false;


//display_error("THIS IS WITHOUT RECEIVABLES");
	$receipt_type=$_POST['receipt_type'];
	$payment_type=$_POST['payment_type'];
	$ref=$_POST['ref'];
	$payment_from=$_POST['PayType'];
	$payee=$_POST['person_id'];
	$payment_to_bank=$_POST['bank_account'];
	$payee_branch=$_POST['PersonDetailID'];
	$memo=$_POST['memo_'];
	$or_no=$_POST['or_no'];
	$trans_type=ST_BANKDEPOSIT;
	$other_income_type_id=$_POST['rec_type'];
	$gross_amount=input_num('gross_amount');
	$payee_name=$payee;
	
	if ($use_transaction)
	begin_transaction();	
	
		$sqlrec="select * from ".TB_PREF."other_income_type where oi_id='$other_income_type_id' and inactive='0'";
		//display_error($sqlrec);
		$result_=db_query($sqlrec);
		

		while ($rec_row = db_fetch($result_))
		{
		$ot_name=$rec_row["oi_type_name"];
		$ot_gl_debit_account=$rec_row["oi_gl_debit"];
		$ot_vat_gl=$rec_row["oi_ov_gl"];
		$ot_wt_gl=$rec_row["oi_wt_gl"];
		$ot_vat_percent=$rec_row["oi_ov"];
		$ot_wt_percent=$rec_row["oi_wt"];
		}
		
		$amount=$gross_amount;
		
		
		if ($ot_vat_percent!=0) { 
		$vat_exclusive=abs(round2($amount/$ot_vat_percent,2));
		$total_vat=abs(round2($vat_exclusive-$amount,2));
		$other_income=abs(round2($vat_exclusive,2));
		}
		else {
		$total_vat=0;
		$other_income=abs(round2($amount,2));
		}
		
		if ($ot_wt_percent!=0) {
		$total_wt=abs(round2($vat_exclusive * $ot_wt_percent,2));
		$total_receivable=abs(round2($amount-$total_wt,2));
		}
		else {
		$total_wt=0;
		$total_receivable=abs(round2($amount,2));
		}
	
	
	if($payment_type == '1' or $payment_type == '2')
	{
		foreach ($checks->checks as $id=>$check_item)
		{
		$total_amount_ += $check_item->check_amount;
		//display_error($check_item->check_amount);
		}		
	}
	else
	{
		$total_amount_ = input_num('cash_amount');
	}
	
	/*
	if ($total_amount_!=abs(round2($total_receivable,2))){
	display_error("Total cash payment amount is not enough or higher.");
	
	
	display_error("TOTAL GROSS AMOUNT:".$amount);
	display_error("TOTAL VAT:".$total_vat);
	display_error("TOTAL WTAX:".$total_wt);
	display_error("TOTAL RECEIVABLE:".$total_receivable);
	display_error("TOTAL AMOUNT PAID:".$total_amount_);
	return false;
	}
	*/		
	global $Refs;
	// we can only handle type 1 (payment)and type 2 (deposit)
	if ($trans_type != ST_BANKDEPOSIT)
		display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");
	

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);
	// the gl items are already inversed/negated for type 2 (deposit)
    $trans_no = get_next_trans_no($trans_type);
	// do the source account postings
	//-------------
	$ref=$trans_no;
	
//BANK_TRANS INSERT
if($payment_type == '1' or $payment_type == '2')
	{
	//---------if check
		foreach ($checks->checks as $id=>$check_item)
		{
			if ($check_item->deleted == true)
				continue;
				
				
			$id = add_bank_trans_2($trans_type, $trans_no, $from_account, $ref, $check_item->check_date, -$check_item->check_amount,
			$person_type_id, $person_id,$currency,"Cannot insert a source bank transaction");
				
			add_check_details($trans_no,$trans_type,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
			$check_item->check_date,$check_item->check_amount, $id);
			
			//issue_check_number2($trans_no, $check_item->check_number, $trans_type, $id);
			$total_amount += $check_item->check_amount;
			//display_error($check_item->check_amount);
		}		
	}
	else
	{
	//-------------cash
		$total_amount = input_num('cash_amount');
		add_bank_trans_2($trans_type, $trans_no, $from_account, $ref,$date_, -$total_amount,$person_type_id, $person_id,$currency,
		"Cannot insert a source bank transaction");
	}
	
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
					
							//--------START OF OI PAYMENT DETAILS--------
							//---------START OF BANK DEPOSIT DETAILS-------------
							if ($payment_type==2){
											foreach ($items->gl_items as $gl_item)
												{
												// do the destination account postings
												add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,$gl_item->dimension_id, $gl_item->dimension2_id,
												$gl_item->reference,$gl_item->amount, $currency, $person_type_id, $person_id);
												
												add_other_income_payment_details($date_,$trans_type,$trans_no,$ref,$gl_item->code_id,abs($gl_item->amount),$total_wt=0,$total_vat=0,$gl_item->reference);
												}
												}
								//--------END OF BANK DEPOSIT DETAILS--------
											
											//to select gl of receivable
											$sql="select * from ".TB_PREF."other_income_type where oi_id='$other_income_type_id'";
											//display_error($sql);
											$res=db_query($sql);
											$row=db_fetch($res);
											$oi_gl_code=$row['oi_gl_debit'];
											$oi_ov_gl=$row['oi_ov_gl'];
											$oi_wt_gl=$row['oi_wt_gl'];
												
												
							$is_bank_to = is_bank_account($oi_gl_code);
							if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
							{
								// we don't allow payments to go to a bank account. use transfer for this !
								display_db_error("invalid payment entered. Cannot pay to another bank account", "");
							}	
							
							
							
							//if transaction is check with breakdown.
							if ($payment_type==2){
							$gross_amount=$total_amount_;
							$total_vat;
							$total_wt;
							$total_receivable=$total_amount_;
							$total_oi=$total_amount_;
							}
							else{
							$total_oi=$other_income;
								}	
								if ($payment_type!=2){
									add_other_income_payment_details($date_,$trans_type,$trans_no,$ref,$oi_gl_code,$total_receivable,$total_wt,$total_vat,$memo_);
								}
									//other income
									if ($gross_amount!=0 and $payment_type!=2 and $payment_type!=3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_gl_code, 0, 0, $rec_memo, -($total_oi), null, $person_type_id, $person_id);
									}
									
									//output vat
									if ($total_vat!=0 and $payment_type!=2 and $payment_type!=3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_ov_gl, 0, 0, $memo__,-$total_vat, null, $person_type_id, $person_id);
									}
									
									//withholding tax
									if ($total_wt!=0 and $payment_type!=2) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_wt_gl, 0, 0, $memo__,$total_wt, null, $person_type_id, $person_id);
									}								
									
									//cash on hand
									if ($total_amount!=0 and $payment_type!=3) {
									add_gl_trans($trans_type, $trans_no, $date_, 1010, 0, 0, $memo_,$total_amount, null, $person_type_id, $person_id);
									}
						//--------END OF OI PAYMENT DETAILS--------
						
						
															//other income
									if ($payment_type==3) {
									add_gl_trans($trans_type, $trans_no, $date_, 2470, 0, 0, $rec_memo, $total_amount, null, $person_type_id, $person_id);
									}
									
									
									if ($payment_type==3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_gl_code, 0, 0, $memo__,-$total_oi, null, $person_type_id, $person_id);
									}
									
									//output vat
									if ($payment_type==3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_ov_gl, 0, 0, $memo__,-$total_vat, null, $person_type_id, $person_id);
									}
						
	//add_comments($trans_type, $trans_no, $date_, $memo_);
	//$Refs->save($trans_type, $trans_no, $ref);
	add_bank_deposit_header2($payment_type,$date_,$trans_type,$trans_no,$ref,$payment_from,$payment_to_bank,$payee,$payee_branch,$gross_amount,$total_receivable,$total_vat,$total_wt,$total_oi,$or_no,$memo,$other_income_type_id,$payee_name,$receipt_type,$saf,$paid_online);
	$desc='Paid';
	add_audit_trail($trans_type, $trans_no, $date_,$desc);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}


//-----	WITHOUT RECEIVABLES
function add_other_income_process_payment_ho($c_id_str,$trans_type, $from_account, $items, $date_, $person_type_id, $person_id, $person_detail_id,$ref, $memo_, $use_transaction=true, $checks,$or_no,$saf,$paid_online)
{
	global $db_connections;
	
	//display_error($_POST['from_loc']);
	if ($_POST['from_loc']=='')
	{
		display_error(_("Please Select Branch."));
		set_focus('from_loc');
		$input_error = 1;
	}	
	
		if ((($_POST['rec_type']==0) or ($_POST['rec_type']=='')) and ($_POST['payment_type']!=2))
	{
		display_error(_("Please select receivable type."));
		set_focus('date_');
		$input_error = 1;
	}
	
		if ((($_POST['gross_amount']==0) or ($_POST['gross_amount']=='')) and ($_POST['payment_type']!=2))
	{
		display_error(_("Gross Amount cannot be empty or zero."));
		set_focus('date_');
		$input_error = 1;
	}
	
			if (($_POST['person_id'])=='')
	{
		display_error(_("Please select or provide where the receivable came from."));
		set_focus('date_');
		$input_error = 1;
	}
	
		if ($input_error == 1)
		//unset($_POST['Process']);
		return false;
		
		
	switch_connection_to_branch_mysql($_POST['from_loc']);

	//display_error("THIS IS WITHOUT RECEIVABLES");
	$receipt_type=$_POST['receipt_type'];
	$payment_type=$_POST['payment_type'];
	$ref=$_POST['ref'];
	$payment_from=$_POST['PayType'];
	$payee=$_POST['person_id'];
	$payment_to_bank=$_POST['bank_account'];
	$payee_branch=$_POST['PersonDetailID'];
	$memo=$_POST['memo_'];
	$or_no=$_POST['or_no'];
	$trans_type=ST_BANKDEPOSIT;
	$other_income_type_id=$_POST['rec_type'];
	$gross_amount=input_num('gross_amount');
	$payee_name=$payee;
	
	if ($use_transaction)
	begin_transaction();	
	
		$sqlrec="select * from ".TB_PREF."other_income_type where oi_id='$other_income_type_id' and inactive='0'";
		//display_error($sqlrec);
		$result_=db_query($sqlrec);
		

		while ($rec_row = db_fetch($result_))
		{
		$ot_name=$rec_row["oi_type_name"];
		$ot_gl_debit_account=$rec_row["oi_gl_debit"];
		$ot_vat_gl=$rec_row["oi_ov_gl"];
		$ot_wt_gl=$rec_row["oi_wt_gl"];
		$ot_vat_percent=$rec_row["oi_ov"];
		$ot_wt_percent=$rec_row["oi_wt"];
		}
		
		$amount=$gross_amount;
		
		
		if ($ot_vat_percent!=0) { 
		$vat_exclusive=abs(round2($amount/$ot_vat_percent,2));
		$total_vat=abs(round2($vat_exclusive-$amount,2));
		$other_income=abs(round2($vat_exclusive,2));
		}
		else {
		$total_vat=0;
		$other_income=abs(round2($amount,2));
		}
		
		if ($ot_wt_percent!=0) {
		$total_wt=abs(round2($vat_exclusive * $ot_wt_percent,2));
		$total_receivable=abs(round2($amount-$total_wt,2));
		}
		else {
		$total_wt=0;
		$total_receivable=abs(round2($amount,2));
		}
	
	
	if($payment_type == '1' or $payment_type == '2')
	{
		foreach ($checks->checks as $id=>$check_item)
		{
		$total_amount_ += $check_item->check_amount;
		//display_error($check_item->check_amount);
		}		
	}
	else
	{
		$total_amount_ = input_num('cash_amount');
	}
	
	/*
	if ($total_amount_!=abs(round2($total_receivable,2))){
	display_error("Total cash payment amount is not enough or higher.");
	
	
	display_error("TOTAL GROSS AMOUNT:".$amount);
	display_error("TOTAL VAT:".$total_vat);
	display_error("TOTAL WTAX:".$total_wt);
	display_error("TOTAL RECEIVABLE:".$total_receivable);
	display_error("TOTAL AMOUNT PAID:".$total_amount_);
	return false;
	}
	*/		
	global $Refs;
	// we can only handle type 1 (payment)and type 2 (deposit)
	if ($trans_type != ST_BANKDEPOSIT)
		display_db_error("Invalid type ($trans_type) sent to add_bank_transaction");
	

	$currency = get_bank_account_currency($from_account);
	$bank_gl_account = get_bank_gl_account($from_account);
	// the gl items are already inversed/negated for type 2 (deposit)
    $trans_no = get_next_trans_no($trans_type);
	// do the source account postings
	//-------------
	$ref=$trans_no;
	
//BANK_TRANS INSERT
if($payment_type == '1' or $payment_type == '2')
	{
	//---------if check
		foreach ($checks->checks as $id=>$check_item)
		{
			if ($check_item->deleted == true)
				continue;
				
				
			$id = add_bank_trans_2($trans_type, $trans_no, $from_account, $ref, $check_item->check_date, -$check_item->check_amount,
			$person_type_id, $person_id,$currency,"Cannot insert a source bank transaction");
				
			add_check_details($trans_no,$trans_type,$check_item->check_bank,$check_item->check_branch,$check_item->check_number,
			$check_item->check_date,$check_item->check_amount, $id);
			
			//issue_check_number2($trans_no, $check_item->check_number, $trans_type, $id);
			$total_amount += $check_item->check_amount;
			//display_error($check_item->check_amount);
		}		
	}
	else
	{
	//-------------cash
		$total_amount = input_num('cash_amount');
		add_bank_trans_2($trans_type, $trans_no, $from_account, $ref,$date_, -$total_amount,$person_type_id, $person_id,$currency,
		"Cannot insert a source bank transaction");
	}
	
		$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
		$result_check_in_transit=db_query($sqlcheck_in_transit);

		while ($accountrow = db_fetch($result_check_in_transit))
		{
		$check_in_transit=$accountrow["check_in_transit"];
		$cash_in_transit=$accountrow["cash_account"];
		}
					
							//--------START OF OI PAYMENT DETAILS--------
							//---------START OF BANK DEPOSIT DETAILS-------------
							if ($payment_type==2){
											foreach ($items->gl_items as $gl_item)
												{
												// do the destination account postings
												add_gl_trans($trans_type, $trans_no, $date_, $gl_item->code_id,$gl_item->dimension_id, $gl_item->dimension2_id,
												$gl_item->reference,$gl_item->amount, $currency, $person_type_id, $person_id);
												
												add_other_income_payment_details($date_,$trans_type,$trans_no,$ref,$gl_item->code_id,abs($gl_item->amount),$total_wt=0,$total_vat=0,$gl_item->reference);
												}
								}
								//--------END OF BANK DEPOSIT DETAILS--------
											
											//to select gl of receivable
											$sql="select * from ".TB_PREF."other_income_type where oi_id='$other_income_type_id'";
											//display_error($sql);
											$res=db_query($sql);
											$row=db_fetch($res);
											$oi_gl_code=$row['oi_gl_debit'];
											$oi_ov_gl=$row['oi_ov_gl'];
											$oi_wt_gl=$row['oi_wt_gl'];
												
												
							$is_bank_to = is_bank_account($oi_gl_code);
							if ($trans_type == ST_BANKPAYMENT AND $is_bank_to)
							{
								// we don't allow payments to go to a bank account. use transfer for this !
								display_db_error("invalid payment entered. Cannot pay to another bank account", "");
							}	
							
							
							
							//if transaction is check with breakdown.
							if ($payment_type==2){
							$gross_amount=$total_amount_;
							$total_vat;
							$total_wt;
							$total_receivable=$total_amount_;
							$total_oi=$total_amount_;
							}
							else{
							$total_oi=$other_income;
								}	
								if ($payment_type!=2){
									add_other_income_payment_details($date_,$trans_type,$trans_no,$ref,$oi_gl_code,$total_receivable,$total_wt,$total_vat,$memo_);
								}
									//other income
									if ($gross_amount!=0 and $payment_type!=2 and $payment_type!=3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_gl_code, 0, 0, $rec_memo, -($total_oi), null, $person_type_id, $person_id);
									}
									
									//output vat
									if ($total_vat!=0 and $payment_type!=2 and $payment_type!=3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_ov_gl, 0, 0, $memo__,-$total_vat, null, $person_type_id, $person_id);
									}
									
									//withholding tax
									if ($total_wt!=0 and $payment_type!=2) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_wt_gl, 0, 0, $memo__,$total_wt, null, $person_type_id, $person_id);
									}								
									
									//cash on hand
									if ($total_amount!=0 and $payment_type!=3) {
									add_gl_trans($trans_type, $trans_no, $date_, 1010, 0, 0, $memo_,$total_amount, null, $person_type_id, $person_id);
									}
									
									
									//other income
									if ($payment_type==3) {
									add_gl_trans($trans_type, $trans_no, $date_, 2470, 0, 0, $rec_memo, $total_amount, null, $person_type_id, $person_id);
									}
									
									
									if ($payment_type==3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_gl_code, 0, 0, $memo__,-$total_oi, null, $person_type_id, $person_id);
									}
									
									//output vat
									if ($payment_type==3) {
									add_gl_trans($trans_type, $trans_no, $date_, $oi_ov_gl, 0, 0, $memo__,-$total_vat, null, $person_type_id, $person_id);
									}

						//--------END OF OI PAYMENT DETAILS--------
						
	//add_comments($trans_type, $trans_no, $date_, $memo_);
	//$Refs->save($trans_type, $trans_no, $ref);
	add_bank_deposit_header2($payment_type,$date_,$trans_type,$trans_no,$ref,$payment_from,$payment_to_bank,$payee,$payee_branch,$gross_amount,$total_receivable,$total_vat,$total_wt,$total_oi,$or_no,$memo,$other_income_type_id,$payee_name,$receipt_type,$saf,$paid_online);
	
	if($payment_type == '1' or $payment_type == '2')
	{
	add_cash_dep_header($_POST['from_loc'],$payment_type,$date_,$trans_type,$trans_no,$gross_amount,$desc,$memo,$payee_name) ;
	
	$sql = "UPDATE ".TB_PREF."other_income_payment_header SET checked = '2'
	WHERE bd_trans_no = '$trans_no'";
	//display_error($sql);
	db_query($sql,'failed to update checking.');
	}
	
	$desc='Paid';
	add_audit_trail($trans_type, $trans_no, $date_,$desc,$_POST['from_loc']);
	if ($use_transaction)
		commit_transaction();

	return array($trans_type, $trans_no);
}


function add_bank_deposit_header2($payment_type,$date_,$trans_type,$trans_no,$ref,$payment_from_id,$payment_to_bank,
$payee_id,$payee_branch_id,$gross_amount,$total_receivable,$total_vat,$total_wt,$total_oi=0,$or_no,$memo,$other_income_type_id,$payee_name,$receipt_type,$saf,$paid_online) {

$date_=date2sql($date_);

$official_receipt='';

if($receipt_type=='OR'){
	$official_receipt=$or_no;
	$or_no='';
	$or_date=$date_;
}
else{
	$official_receipt='';
	//$or_no='';
	$or_date='0000-00-00';
	
}
	
if ($payment_type=='0'){
$payment_type_id='1';
$payment_type='cash';
}

if ($payment_type=='3'){
$payment_type_id='3';
$payment_type='invoice deduction';

}

if ($payment_type=='1' or $payment_type=='2'){
$payment_type_id='2';
$payment_type='check';
}

if ($payment_from_id=='0'){
$payment_from='Miscellaneous';
$payee=$payee_name;
$payee_id=0;
}
if ($payment_from_id=='2'){
$payment_from='Customer';
$payee_branch=get_customer_branch($payee_id,$payee_branch_id);
$payee=get_customer_name($payee_id);
}
if ($payment_from_id=='3'){
$payment_from='Supplier';
$payee=get_supplier_name($payee_id);
}

if ($payment_to_bank=='') {
$payment_to_bank=0;
}

/*if($gross_amount=='') {
$gross_amount=0;
}

if($total_receivable=='') {
$total_receivable=0;
}
*/

if($payee_branch_id=='') {
$payee_branch_id=0;
}

$sql = "INSERT INTO ".TB_PREF."other_income_payment_header(bd_payment_type_id,bd_payment_type,bd_trans_date,bd_trans_type,bd_trans_no,
bd_reference,bd_payment_from_id,bd_payment_from,bd_payment_to_bank,bd_payee_id,bd_payee,bd_cust_branch_id,bd_cust_branch,bd_gross_amount,bd_amount,bd_vat,bd_wt,bd_oi,bd_or,bd_memo,bd_date_deposited,bd_date_cleared,bd_cleared,bd_receipt_type,bd_official_receipt,bd_created_by,bd_saf,bd_paid_online,bd_or_date)				
VALUES ('$payment_type_id','$payment_type','$date_','$trans_type','$trans_no','$ref','$payment_from_id','$payment_from','$payment_to_bank','$payee_id',".db_escape($payee).",'$payee_branch_id','$payee_branch','$gross_amount','$total_receivable','$total_vat','$total_wt','$total_oi','$or_no','$memo','0000-00-00','0000-00-00','0','$receipt_type','$official_receipt','".$_SESSION["wa_current_user"]->user."','".$saf."',".$paid_online.",'$or_date')";		
db_query($sql,'unable to add bank deposit header');
//display_error($sql);
}
?>