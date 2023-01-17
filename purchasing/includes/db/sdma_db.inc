<?php

function insert_sdma($reference,$supplier_id,$sdma_type,$dm_date,$frequency,$period,$amount,$disc_percent,
		$effective_from,$effective_to,$once_only,$po_no,$comment, $receivable)
{
	$disc_vat_only = 0; // change later
	$frequency += 0;
	
	if (is_date($effective_from))
		$effective_from = "'".date2sql($effective_from)."'";
	else
		$effective_from = 'NULL';
	
	if (is_date($effective_to))
		$effective_to = "'".date2sql($effective_to)."'";
	else
		$effective_to = 'NULL';
	
	if (is_date($dm_date))
		$dm_date = "'".date2sql($dm_date)."'";
	else
		$dm_date = 'NULL';
	
		
	$sql = "INSERT INTO ".TB_PREF."sdma(reference,supplier_id,sdma_type,dm_date,frequency,period,amount,disc_percent,
					disc_vat_only,effective_from,effective_to,once_only,po_no,comment, receivable, prepared_by)
			VALUES(".db_escape($reference).",".$supplier_id.",".$sdma_type.",$dm_date,$frequency,$period,
				".$amount.",".$disc_percent.",$disc_vat_only, $effective_from, $effective_to,$once_only,
				".db_escape($po_no).",".db_escape($comment).",$receivable,".$_SESSION["wa_current_user"]->user.")";
	// display_error($sql);
	db_query($sql,'failed to add DM Agreement');
	return db_insert_id();
}

function update_sdma($sdma_id,$reference,$supplier_id,$sdma_type,$dm_date,$frequency,$period,$amount,$disc_percent,
		$effective_from,$effective_to,$once_only,$po_no,$comment, $receivable)
{
	$disc_vat_only = 0; // change later
	$frequency += 0;
	
	if (is_date($effective_from))
		$effective_from = "'".date2sql($effective_from)."'";
	else
		$effective_from = 'NULL';
	
	if (is_date($effective_to))
		$effective_to = "'".date2sql($effective_to)."'";
	else
		$effective_to = 'NULL';
	
	if (is_date($dm_date))
		$dm_date = "'".date2sql($dm_date)."'";
	else
		$dm_date = 'NULL';
				
	$sql = "UPDATE ".TB_PREF."sdma SET
				supplier_id = $supplier_id,
				sdma_type = $sdma_type,
				dm_date = $dm_date,
				frequency = $frequency,
				period = $period,
				amount = $amount,
				disc_percent = $disc_percent,
				disc_vat_only = $disc_vat_only,
				effective_from = $effective_from,
				effective_to = $effective_to,
				once_only = $once_only,
				po_no = ".db_escape($po_no).",
				comment = ".db_escape($comment).",
				receivable = $receivable,
				prepared_by = ".$_SESSION["wa_current_user"]->user."
			WHERE id = $sdma_id";
	// display_error($sql);
	db_query($sql,'failed to add DM Agreement');
}
//mhae start
function update_sdma_per_reference($reference,$supplier_id,$sdma_type,$dm_date,$frequency,$period,$amount,$disc_percent,
		$effective_from,$effective_to,$once_only,$po_no,$comment, $receivable)
{
	$disc_vat_only = 0; // change later
	$frequency += 0;
	
	if (is_date($effective_from))
		$effective_from = "'".date2sql($effective_from)."'";
	else
		$effective_from = 'NULL';
	
	if (is_date($effective_to))
		$effective_to = "'".date2sql($effective_to)."'";
	else
		$effective_to = 'NULL';
	
	if (is_date($dm_date))
		$dm_date = "'".date2sql($dm_date)."'";
	else
		$dm_date = 'NULL';
				
	$sql = "UPDATE ".TB_PREF."sdma SET
				supplier_id = $supplier_id,
				sdma_type = $sdma_type,
				dm_date = $dm_date,
				frequency = $frequency,
				period = $period,
				amount = $amount,
				disc_percent = $disc_percent,
				disc_vat_only = $disc_vat_only,
				effective_from = $effective_from,
				effective_to = $effective_to,
				once_only = $once_only,
				po_no = ".db_escape($po_no).",
				comment = ".db_escape($comment).",
				receivable = $receivable
			WHERE reference = '".$reference."'";
	//display_error($sql);
	db_query($sql,'failed to add DM Agreement');
}
//mhae end
function delete_sdma($sdma_id)
{
	$sql = "DELETE FROM ".TB_PREF."sdma WHERE id = $sdma_id";
	db_query($sql,'failed to delete DM agreement');
}

function get_sdma($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sdma WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function get_sdma_by_ref($ref)
{
	$sql = "SELECT * FROM ".TB_PREF."sdma WHERE reference = ".db_escape($ref);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function get_sdma_ref($id)
{
	if (is_numeric($id))
	{
		$sql = "SELECT reference FROM ".TB_PREF."sdma WHERE id = $id";
		$res = db_query($sql);
		$row = db_fetch($res);
		return $row[0];
	}
	else // dm has po
	{
		list($sdma_id,$po_no) = explode('~PO',$id);
		// $sql = "SELECT reference FROM ".TB_PREF."sdma WHERE id = $sdma_id";
		// $res = db_query($sql);
		// $row = db_fetch($res);
		return 'for PO# '.$po_no.' only';
	}
}

function get_sdma_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sdma_type WHERE id=$id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function set_sdma_to_done($id)
{
	$sql = "UPDATE ".TB_PREF."sdma SET is_done=1 WHERE id = $id";
	db_query($sql,'failed to set sdma to is_done');
}

function create_dm_from_sdma($id)
{
	$sql = "SELECT * FROM ".TB_PREF."sdma WHERE id=$id AND approval_1 != 0 AND approval_2 != 0 AND is_done = 0";
	// display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if (db_num_rows($res) == 1 AND $row['amount'] != 0)
		create_dm_from_fixed_amount_sdma($id);
}

function create_dm_from_fixed_amount_sdma($id)
{
	global $Refs;
	
	$sdma = get_sdma($id);
	
	begin_transaction();
	
	$dm_date = sql2date($sdma['dm_date']);
	$freq = $sdma['frequency'];
	$repeat = $sdma['period']+1;
	$spec_ref = $sdma['id'];
	
	if ($sdma['po_no'] != '')
		$spec_ref .= '~PO'.$sdma['po_no'];

	$sdma_type = get_sdma_type($sdma['sdma_type']);
	
	if ($repeat == 0)
		$repeat = 1;
	
	$count = 0;
	while($repeat != 0)
	{
		$count ++;
		
		$supp_trans_reference = $Refs->get_next(53);
		
		if ($freq > 0 AND $count > 1)
		{
			switch($freq)
			{
				case 1:
					$dm_date = add_days($dm_date, 7);
					break;
				case 2:	
					$dm_date = add_days($dm_date, 14);
					break;
				case 3:	
					$dm_date= add_months($dm_date, 1);
					break;
				case 4:	
					$dm_date = add_months($dm_date, 3);
					break;
			}
		}
		
		$company_record = get_company_prefs();
		
		$dc_amount = $sdma['amount']; //positive for debit , negative for credit. reverse in supp trans
		$tax_amount = 0;
		
		if ($sdma_type['credit_tax_account'] != '')
		{
			$dc_amount = round($dc_amount/(1+($sdma_type['credit_tax_percent'])/100),2);
			$tax_amount  = $sdma['amount']-$dc_amount;
		}
		
		$trans_no = add_supp_trans(53, $sdma['supplier_id'], $dm_date, '',
			$supp_trans_reference, $sdma['reference'], -$dc_amount,  -$tax_amount, 0, "", 0, 0,'', false, 0, $spec_ref);
		
		$debit_amt = $sdma['amount'];
		$credit_amt = $debit_amt;
		$output_vat_amt = 0;
		
		// if ($sdma_type['output_vat_account'] != '' AND $sdma_type['output_vat_percent'] > 0)
		// {
			// $credit_amt = round2($credit_amt * (1+($sdma_type['output_vat_percent']/100)));
			// $output_vat_amt = $debit_amt - $credit_amt;
		// }
		
		add_gl_trans_supplier_temp(53, $trans_no, $dm_date, $sdma_type['debit_account'], 0, 0,$debit_amt, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment']);
		
		add_gl_trans_supplier_temp(53, $trans_no, $dm_date, $sdma_type['credit_account'], 0, 0,-$credit_amt, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment']);
		
		if ($tax_amount != 0)
		add_gl_trans_supplier_temp(53, $trans_no, $dm_date, $sdma_type['credit_tax_account'], 0, 0,-$tax_amount, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment']);
				
		if ($output_vat_amt != 0 AND $sdma_type['output_vat_account'] != '')
			add_gl_trans_supplier_temp(53, $trans_no, $dm_date, $sdma_type['output_vat_account'], 0, 0,-$output_vat_amt, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment']);
		
		add_comments(53, $trans_no, $dm_date, $sdma['comment']);

		$Refs->save(53, $trans_no, $supp_trans_reference);
		
		$repeat	--;
	}
	
	set_sdma_to_done($id);
	commit_transaction();
}

function create_dm_from_percentage_sdma($id,$amount,$for_apv)
{
	global $Refs;
	
	$sdma = get_sdma($id);
	
	// begin_transaction();
	
	$dm_date = Today(); //sql2date($sdma['dm_date']);
	// $freq = $sdma['frequency'];
	// $repeat = 1;
	$spec_ref = $sdma['id'];
	
	if ($sdma['po_no'] != '')
		$spec_ref .= '~PO'.$sdma['po_no'];

	$sdma_type = get_sdma_type($sdma['sdma_type']);

	$supp_trans_reference = $Refs->get_next(53);
	
	$company_record = get_company_prefs();
	
	$dc_amount = $amount; //positive for debit , negative for credit. reverse in supp trans
	
	$trans_no = add_supp_trans(53, $sdma['supplier_id'], $dm_date, '',
		$supp_trans_reference, $sdma['reference'], -$dc_amount,  0, 0, "", 0, 0,'', false, 0, $spec_ref);
	
	$debit_amt = $dc_amount;
	$credit_amt = $debit_amt;
	$output_vat_amt = 0;
	
	if ($sdma_type['output_vat_account'] != '' AND $sdma_type['output_vat_percent'] > 0)
	{
		$credit_amt = round2($credit_amt * (1+($sdma_type['output_vat_percent']/100)));
		$output_vat_amt = $debit_amt - $credit_amt;
	}
	
	add_gl_trans_supplier_temp(53, $trans_no, $dm_date, $sdma_type['debit_account'], 0, 0,$debit_amt, 
			$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment']);
	
	add_gl_trans_supplier_temp(53, $trans_no, $dm_date, $sdma_type['credit_account'], 0, 0,-$credit_amt, 
			$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment']);
			
	if ($output_vat_amt != 0 AND $sdma_type['output_vat_account'] != '')
		add_gl_trans_supplier_temp(53, $trans_no, $dm_date, $sdma_type['output_vat_account'], 0, 0,-$output_vat_amt, 
			$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment']);
	
	add_comments(53, $trans_no, $dm_date, $sdma['comment'] .' (for APV #'.$for_apv.')');

	$Refs->save(53, $trans_no, $supp_trans_reference);
	
	return $trans_no;
	
	// set_sdma_to_done($id);
	// commit_transaction();
}
?>