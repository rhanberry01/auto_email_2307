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
//-------------------------------------------------------------------------------------------------------------

function add_supp_trans($type, $supplier_id, $date_, $due_date, $reference, $supp_reference,
	$amount, $amount_tax, $discount, $err_msg="", $rate=0, $ewt=0, $del_date='', $non_trade=false, $ewt_percent=0,
	$special_reference='')
{
	
	if (is_date_in_event_locker($date_)==1)
	{
		display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
		exit();
	}
	
	if (is_date_in_event_locker($del_date)==1)
	{
		display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
		exit();
	}
	
		if (is_date_in_event_locker($due_date)==1)
	{
		display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
		exit();
	}
	
	
	$date = date2sql($date_);
	if ($due_date == "")
		$due_date = "0000-00-00";
	else
		$due_date = date2sql($due_date);

	//display_error('before'.$del_date);
		
	if($del_date == "")
		$del_date = "0000-00-00";
	else
		$del_date = date2sql($del_date);
	
	
	//display_error('after'.$del_date);
	$trans_no = get_next_trans_no($type);

	$curr = get_supplier_currency($supplier_id);
	
	if ($rate == 0)
		$rate = get_exchange_rate_from_home_currency($curr, $date_);


	$sql = "INSERT INTO ".TB_PREF."supp_trans (trans_no, type, supplier_id, tran_date, due_date,
		reference, supp_reference, ov_amount, ov_gst, rate, ov_discount, ewt, del_date, non_trade, ewt_percent,
		special_reference) ";
		
	$sql .= "VALUES (".db_escape($trans_no).", ".db_escape($type)
	.", ".db_escape($supplier_id).", '$date', '$due_date',
		".db_escape($reference).", ".db_escape($supp_reference).", ".db_escape($amount)
		.", ".db_escape($amount_tax).", ".db_escape($rate).", ".db_escape($discount).", ".db_escape($ewt).", '$del_date',
		".($non_trade+0).",$ewt_percent+0,".db_escape($special_reference).")";

	if ($err_msg == "")
		$err_msg = "Cannot insert a supplier transaction record";

	db_query($sql, $err_msg);
	add_audit_trail($type, $trans_no, $date_);

	return $trans_no;
}

//-------------------------------------------------------------------------------------------------------------

function get_supp_trans($trans_no, $trans_type=-1)
{
	$sql = "SELECT ".TB_PREF."supp_trans.*, SUM(".TB_PREF."supp_trans.ov_amount+".TB_PREF."supp_trans.ov_gst+".TB_PREF."supp_trans.ov_discount+".TB_PREF."supp_trans.ewt) AS Total,
		".TB_PREF."suppliers.supp_name AS supplier_name, ".TB_PREF."suppliers.curr_code AS SupplierCurrCode, ".TB_PREF."supp_trans.ewt ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".TB_PREF."bank_accounts.bank_name, ".TB_PREF."bank_accounts.bank_account_name, ".TB_PREF."bank_accounts.bank_curr_code,
			".TB_PREF."bank_accounts.account_type AS BankTransType, ".TB_PREF."bank_trans.amount AS BankAmount,
			".TB_PREF."bank_trans.ref ";
	}

	$sql .= " FROM ".TB_PREF."supp_trans, ".TB_PREF."suppliers ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".TB_PREF."bank_trans, ".TB_PREF."bank_accounts";
	}

	$sql .= " WHERE ".TB_PREF."supp_trans.trans_no=".db_escape($trans_no)."
		AND ".TB_PREF."supp_trans.supplier_id=".TB_PREF."suppliers.supplier_id";

	if ($trans_type > 0)
		$sql .= " AND ".TB_PREF."supp_trans.type=".db_escape($trans_type);

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= " AND ".TB_PREF."bank_trans.trans_no =".db_escape($trans_no)."
			AND ".TB_PREF."bank_trans.type=".db_escape($trans_type)."
			AND ".TB_PREF."bank_accounts.id=".TB_PREF."bank_trans.bank_act ";
	}

	$sql .= " GROUP BY trans_no,type";
	$result = db_query($sql, "Cannot retrieve a supplier transaction");

    if (db_num_rows($result) == 0)
    {
       // can't return nothing
       display_db_error("no supplier trans found for given params c", $sql, true);
       exit;
    }

    if (db_num_rows($result) > 1)
    {
       // can't return multiple
       display_db_error("duplicate supplier transactions found for given params", $sql, true);
       exit;
    }

    return db_fetch($result);
}

function get_supp_trans_2($trans_no, $trans_type=-1)
{
	$sql = "SELECT ".TB_PREF."supp_trans.*, ".TB_PREF."supp_trans.ov_amount+".TB_PREF."supp_trans.ov_gst+".TB_PREF."supp_trans.ov_discount+".TB_PREF."supp_trans.ewt AS Total,
		".TB_PREF."suppliers.supp_name AS supplier_name, 
		".TB_PREF."suppliers.gst_no, ".TB_PREF."suppliers.curr_code AS SupplierCurrCode, ".TB_PREF."supp_trans.ewt ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".TB_PREF."bank_accounts.bank_name, ".TB_PREF."bank_accounts.bank_account_name, ".TB_PREF."bank_accounts.bank_curr_code,
			".TB_PREF."bank_accounts.account_type AS BankTransType, SUM(".TB_PREF."bank_trans.amount) AS BankAmount,
			".TB_PREF."bank_trans.ref ";
	}

	$sql .= " FROM ".TB_PREF."supp_trans, ".TB_PREF."suppliers ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".TB_PREF."bank_trans, ".TB_PREF."bank_accounts";
	}

	$sql .= " WHERE ".TB_PREF."supp_trans.trans_no=".db_escape($trans_no)."
		AND ".TB_PREF."supp_trans.supplier_id=".TB_PREF."suppliers.supplier_id";

	if ($trans_type > 0)
		$sql .= " AND ".TB_PREF."supp_trans.type=".db_escape($trans_type);

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= " AND ".TB_PREF."bank_trans.trans_no =".db_escape($trans_no)."
			AND ".TB_PREF."bank_trans.type=".db_escape($trans_type)."
			AND ".TB_PREF."bank_accounts.id=".TB_PREF."bank_trans.bank_act ";
	}

	$sql .= " GROUP BY trans_no,type";
	$result = db_query($sql, "Cannot retrieve a supplier transaction");

    if (db_num_rows($result) == 0)
    {
       // can't return nothing
       display_db_error("no supplier trans found for given params d", $sql, true);
       exit;
    }

    if (db_num_rows($result) > 1)
    {
       // can't return multiple
       display_db_error("duplicate supplier transactions found for given params", $sql, true);
       exit;
    }

    return db_fetch($result);
}

//----------------------------------------------------------------------------------------

function exists_supp_trans($type, $type_no)
{
	if ($type == ST_SUPPRECEIVE)
		return exists_grn($type_no);

	$sql = "SELECT trans_no FROM ".TB_PREF."supp_trans WHERE type=".db_escape($type)."
		AND trans_no=".db_escape($type_no);
	$result = db_query($sql, "Cannot retrieve a supplier transaction");

    return (db_num_rows($result) > 0);
}

//----------------------------------------------------------------------------------------

function void_supp_trans($type, $type_no)
{
	$sql = "UPDATE ".TB_PREF."supp_trans SET ov_amount=0, ov_discount=0, ov_gst=0, ewt=0,
		alloc=0, cv_id=0 WHERE type=".db_escape($type)." AND trans_no=".db_escape($type_no);

	db_query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
}

//----------------------------------------------------------------------------------------

function post_void_supp_trans($type, $type_no)
{
	if ($type == ST_SUPPAYMENT)
	{
		void_supp_payment($type, $type_no);
		return true;
	}

	if ($type == ST_SUPPINVOICE || $type == ST_SUPPCREDIT)
	{
		$sql = "UPDATE ".TB_PREF."cwo_header SET voided='1' WHERE c_supp_trans_no = ".db_escape($type_no);
		db_query($sql,'failed to update cwo header');	
		
	 	void_supp_invoice($type, $type_no);
		return true;
	}

	if ($type == SUPPRECEIVE)
	{
	 	return void_grn($type_no);
	}

	if ($type == ST_SUPPDEBITMEMO OR $type == ST_SUPPCREDITMEMO)
	{
		void_bank_trans($type, $type_no, true);
		void_gl_trans($type, $type_no, true);
		void_supp_allocations($type, $type_no);
		void_supp_trans($type, $type_no);
		return true;
	}
	return false;
}//----------------------------------------------------------------------------

function post_void_supp_trans_dis($type, $type_no)
{
	
	if ($type == ST_SUPPINVOICE)
	{
		//display_error('dasasasd');
		///exit;
		$sql = "UPDATE ".TB_PREF."cwo_header SET voided='1' WHERE c_supp_trans_no = ".db_escape($type_no);
		db_query($sql,'failed to update cwo header');	
		
	 	void_supp_invoice_dis($type, $type_no);
		return true;
	}

	return false;
}

function supp_trans_in_cv($trans_type, $trans_no)
{
	$sql = "SELECT * FROM ".TB_PREF."cv_details WHERE trans_type=$trans_type AND trans_no=$trans_no AND voided=0";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;
	else
	{
		$r = db_fetch($res);
		$sql = "SELECT cv_no FROM ".TB_PREF."cv_header WHERE id=".$r['cv_id'];
		$res = db_query($sql);
		$row = db_fetch($res);
		
		return $row[0];
	}
}

//---------------------------------------- used in auto import to CV
function get_apv_supp_trans($trans_no)
{
	$sql = "SELECT * , round(ov_amount + ov_gst  - ov_discount - ewt,2) AS TotalAmount
			FROM ".TB_PREF."supp_trans
			WHERE type = 20
			AND trans_no = $trans_no";
			//display_error($sql);
	$res = db_query($sql);
	return db_fetch($res);
}

function get_unused_dm_fixed_price($supp_id, $exclude=array(), $po_no='') //$exclude trans_no of percent DM
{
	// $additionl_sql = " AND special_reference != 0 "; uncomment to include only new DM
	
	if (count($exclude) > 0)
		$additionl_sql = " AND trans_no NOT IN (".implode(',',$exclude).")";
	
	$po_sql = "";
	
	if ($po_no == '')
		$po_sql = " AND special_reference NOT LIKE '%~PO%'";
	else
		$po_sql = " AND special_reference LIKE '%~PO$po_no%'";
	
	
	$sql = "SELECT * , round(ov_amount + ov_gst  - ov_discount - ewt,2) AS TotalAmount
			FROM ".TB_PREF."supp_trans 
			WHERE supplier_id = $supp_id
			AND type = 53
			AND tran_date <= '".date2sql(end_month(Today()))."'
			AND cv_id = 0
			$po_sql 
			$additionl_sql
			AND round(ov_amount + ov_gst  - ov_discount - ewt,2) != 0
			ORDER BY TotalAmount DESC";
	//display_error($sql);die;
	return db_query($sql);
}

function get_percent_dm($supp_id,$del_date,$po_no='')
{
	//for no PO 
	$sql = "SELECT * FROM ".TB_PREF."sdma 
			WHERE supplier_id = $supp_id
			AND amount = 0
			AND disc_percent > 0
			AND effective_from <= '".date2sql($del_date)."'
			AND effective_to >= '".date2sql($del_date)."'
			AND approval_1 != 0
			AND approval_2 != 0
			AND po_no = '$po_no'
			ORDER BY dm_date";
	$res = db_query($sql);
	// display_error($sql);die;
	return $res;
	
}


function get_pending_apv_and_cm($supp_id,$trans_no)
{
	// $sql = "SELECT * , round(ov_amount + ov_gst  - ov_discount - ewt,2) AS TotalAmount
			// FROM ".TB_PREF."supp_trans
			// WHERE (type = 20 OR type = 52)
			// AND (type != 20 AND trans_no != $trans_no)
			// AND supplier_id = $supp_id
			// AND cv_id = 0
			// AND special_reference != 0
			// AND round(ov_amount + ov_gst  - ov_discount - ewt,2) != 0
			// ORDER BY type, TotalAmount";
			
	// ((type = 20  AND special_reference != 0 AND trans_no != $trans_no) OR 
	$sql = "SELECT * , round(ov_amount + ov_gst  - ov_discount - ewt,2) AS TotalAmount
			FROM 0_supp_trans
			WHERE (type = 52 AND tran_date >= '2016-01-01')
			AND supplier_id = $supp_id
			AND cv_id = 0
			AND round(ov_amount + ov_gst  - ov_discount - ewt,2) != 0
			ORDER BY type DESC, TotalAmount";
	return db_query($sql);
}


//================================== REBATES =========================
function get_rebate($id)
{
	$sql = "SELECT * FROM ".TB_PREF."rebates
				WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function insert_rebate($supplier_id, $percentage, $start_date, $end_date)
{
	$sql = "INSERT INTO ".TB_PREF."rebates (supplier_id, percentage, start_date, end_date)
				VALUES ($supplier_id, $percentage, '".date2sql($start_date)."', '".date2sql($end_date)."')";
	db_query($sql,'failed to insert rebate');
}
//----------------------------------------------------------------------------------------
?>