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
include_once($path_to_root . "/purchasing/includes/db/invoice_items_db.inc");

//--------------------------------------------------------------------------------------------------

function read_supplier_details_to_trans(&$supp_trans, $supplier_id)
{
	$sql = "SELECT ".TB_PREF."suppliers.supp_name, ".TB_PREF."payment_terms.terms, "
		.TB_PREF."payment_terms.days_before_due,
		".TB_PREF."payment_terms.day_in_following_month,
		".TB_PREF."suppliers.tax_group_id, ".TB_PREF."tax_groups.name As tax_group_name
		From ".TB_PREF."suppliers, ".TB_PREF."payment_terms, ".TB_PREF."tax_groups
		WHERE ".TB_PREF."suppliers.tax_group_id = ".TB_PREF."tax_groups.id
		AND ".TB_PREF."suppliers.payment_terms=".TB_PREF."payment_terms.terms_indicator
		AND ".TB_PREF."suppliers.supplier_id = ".db_escape($supplier_id);

	$result = db_query($sql, "The supplier record selected: " . $supplier_id . " cannot be retrieved");

	$myrow = db_fetch($result);

    $supp_trans->supplier_id = $supplier_id;
    $supp_trans->supplier_name = $myrow['supp_name'];
	$supp_trans->terms_description = $myrow['terms'];

	if ($myrow['day_in_following_month'] == 0)
	{
		$supp_trans->terms = "0" . $myrow['days_before_due'];
	}
	else
	{
		$supp_trans->terms = "1" . $myrow['day_in_following_month'];
	}
	$supp_trans->tax_description = $myrow['tax_group_name'];
	$supp_trans->tax_group_id = $myrow['tax_group_id'];

    if ($supp_trans->tran_date == "")
    {
		$supp_trans->tran_date = Today();
		if (!is_date_in_fiscalyear($supp_trans->tran_date))
			$supp_trans->tran_date = end_fiscalyear();
	}
    //if ($supp_trans->due_date=="") {
    //	get_duedate_from_terms($supp_trans);
    //}
    get_duedate_from_terms($supp_trans);
}
//rhan 212018
function get_new_ref(){
	$sql = "select reference from 0_refs where type ='20' ORDER BY id desc LIMIT 1";
	$result = db_query($sql, "Cannot retrieve updated reference");
	$fetch = db_fetch($result);
	return  $fetch['reference'];
}
//rhan 212018
//--------------------------------------------------------------------------------------------------

function update_supp_received_items_for_invoice($id, $po_detail_item, $qty_invoiced, $chg_price=null)
{
	$po_detail = $grn_item = array();
	
	if ($chg_price != null)
	{
		$sql = "SELECT act_price, unit_price, multiplier FROM ".TB_PREF."purch_order_details WHERE
			po_detail_item = ".db_escape($po_detail_item);
		$result = db_query($sql, "The old actual price of the purchase order line could not be retrieved");
		$row = db_fetch($result);
		$po_detail = $row;
		$ret = $row[0];

		$unit_price = $row[1]; //Added by Rasmus

		$sql = "SELECT delivery_date , multiplier
			FROM ".TB_PREF."grn_batch,".TB_PREF."grn_items WHERE
			".TB_PREF."grn_batch.id = ".TB_PREF."grn_items.grn_batch_id AND "
			.TB_PREF."grn_items.id=".db_escape($id);
		$result = db_query($sql, "The old delivery date from the received record cout not be retrieved");
		$row = db_fetch($result);
		$grn_item = $row;
		$date = $row[0];
	}
	else
	{
		$ret = 0;
		$date = "";
		$unit_price = 0; // Added by Rasmus
	}
    // $sql = "UPDATE ".TB_PREF."purch_order_details
		// SET qty_invoiced = qty_invoiced_pcs + ".db_escape($qty_invoiced).",
		 // qty_invoiced = qty_invoiced_pcs + ".db_escape($qty_invoiced);
	
	$sql = "UPDATE ".TB_PREF."purch_order_details
        SET qty_invoiced = (qty_invoiced + (" .($qty_invoiced * ($grn_item['multiplier']+0))." / ".($po_detail['multiplier']+0).")),
         qty_invoiced_pcs = qty_invoiced_pcs + (".$qty_invoiced * ($grn_item['multiplier']+0).")";

	if ($chg_price != null)
		$sql .= " , act_price = ".((($chg_price / $grn_item['multiplier']) * ($po_detail['multiplier'])+0));

	$sql .= " WHERE po_detail_item = ".db_escape($po_detail_item);
	
    db_query($sql, "The quantity invoiced of the purchase order line could not be updated");

    $sql = "UPDATE ".TB_PREF."grn_items SET
			quantity_inv_pcs = round(quantity_inv_pcs + (".$qty_invoiced * $grn_item['multiplier']."),2),
			quantity_inv = round(quantity_inv + ".($qty_invoiced+0).",2)
        WHERE id = ".db_escape($id);
	db_query($sql, "The quantity invoiced off the items received record could not be updated");
	return array($ret, $date, $unit_price);
}

function get_diff_in_home_currency($supplier, $old_date, $date, $amount1, $amount2)
{
	$dec = user_price_dec();
	price_decimal_format($amount2, $dec);
	$currency = get_supplier_currency($supplier);
	$ex_rate = get_exchange_rate_to_home_currency($currency, $old_date);
	$amount1 = $amount1 / $ex_rate;
	$ex_rate = get_exchange_rate_to_home_currency($currency, $date);
	$amount2 = $amount2 / $ex_rate;
	$diff = $amount2 - $amount1;
	return round2($diff, $dec);
}
//----------------------------------------------------------------------------------------

function add_supp_invoice_new($supp_trans, $invoice_no=0, $advances=0, $trans_type=null) // do not receive as ref because we change locally
{
	global $Refs;
	
	$company_pref = get_company_prefs();
	
	//$company_currency = get_company_currency();
	/*Start an sql transaction */
	if ($trans_type === null)
		$trans_type = ST_SUPPINVOICE;
	
	begin_transaction();
	
	$tax_total = $supp_trans->vat;

	$invoice_items_total = $supp_trans->purch_non_vat + $supp_trans->purch_vat;
	
    $gl_date = $date_ = $supp_trans->tran_date;
    
	if (is_date($supp_trans->del_date))
		$gl_date = $supp_trans->del_date;
	
	$ex_rate = get_exchange_rate_from_home_currency(get_supplier_currency($supp_trans->supplier_id), $date_);

	//rhan 212018
	if (!$supp_trans->nt)
	{
		$new_r = get_new_ref();
		$new_ref_ = preg_replace('/[^0-9]/','',$new_r);
		$new_ref_ref = 	$new_ref_+1;
		$new_ref = 	'SRS_'.$new_ref_ref;

	}else{

		$new_r = get_new_ref();
		$new_ref_ = preg_replace('/[^0-9]/','',$new_r);
		$new_nt_ref = $new_ref_+1;
		if(!$supp_trans->addback){
			$new_ref = 	'NTSRS_'.$new_nt_ref;
		}else{
			$new_ref = 	'SRS_'.$new_nt_ref;
		}
		
	}


    /*First insert the invoice into the supp_trans table*/
	$invoice_id = add_supp_trans($trans_type, $supp_trans->supplier_id, 
													$date_, 
													$supp_trans->due_date,
													$new_ref/* rhan 212018 $supp_trans->reference*/, 
													$supp_trans->supp_reference,
													$supp_trans->purch_non_vat + $supp_trans->purch_vat - ($trans_type == 24 ? 0 : $advances), 
													$tax_total, 
													$supp_trans->purch_discount,
													"",
													0,
													$supp_trans->ewt+0,
													$supp_trans->del_date,
													$supp_trans->nt,
													($supp_trans->ewt+0 > 0 ? get_company_pref('ewt_percent') : '0'),
													$supp_trans->special_reference
													);

		//display_error('-->'.$supp_trans->del_date);
	if ($trans_type == ST_SUPPINVOICE)
		update_purchase_book($date_, $supp_trans->supplier_id, $trans_type, $invoice_id, $supp_trans->supp_reference, 0, 0, 0,
			0,0);
	
    /* Now the control account */
    $supplier_accounts = get_supplier_accounts($supp_trans->supplier_id);
	
	$total = 0;
	
	
	if ($supp_trans->nt AND (strpos($supp_trans->reference,'NT') !== false))
		$company_pref["creditors_act"] = $company_pref["creditors_act_nt"];
	
	$advances_remaining = 0;
	$advances_account = '1440';
	if ($advances > 0)
	{
		$advances_remaining = $advances - ($supp_trans->acounts_payable + $supp_trans->ewt);
		
		if ($advances_remaining > 0) // over payment
		{
			$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $advances_account, 0, 0,-($supp_trans->acounts_payable + $supp_trans->ewt) ,
				$supp_trans->supplier_id,"The general ledger transaction for the control total could not be added");
		}
		else // exact payment
		  $total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $advances_account, 0, 0,-($advances) ,$supp_trans->supplier_id,
		"The general ledger transaction for the control total could not be added");
	}
	
	if($advances_remaining  <= 0 AND $trans_type == 20)
	{
    $total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $company_pref["creditors_act"], 0, 0,
		-($supp_trans->acounts_payable + $supp_trans->ewt - $advances) ,$supp_trans->supplier_id,
		"The general ledger transaction for the control total could not be added");
	// display_error($total);
	}

	if ($supp_trans->purch_discount > 0)
	{
		$disc_act = $company_pref["pyt_discount_act"];
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $disc_act, 0, 0,
			-(round2($supp_trans->purch_discount,2)),$supp_trans->supplier_id,
			"The general ledger transaction for discount could not be added");
		// display_error( 'minus '.$supp_trans->ov_discount.' equals '.$total);
	}
	if ($supp_trans->purch_ret > 0)
	{
		$purch_ret_act = 5500;
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $purch_ret_act, 0, 0,
			-(round2($supp_trans->purch_ret,2)),$supp_trans->supplier_id,
			"The general ledger transaction for discount could not be added");
		// display_error( 'minus '.$supp_trans->ov_discount.' equals '.$total);
	}
	if ($supp_trans->disp_allow > 0)
	{
		$disp_allow_act = 4020010;
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $disp_allow_act, 0, 0,
			-(round2($supp_trans->disp_allow,2)),$supp_trans->supplier_id,
			"The general ledger transaction for discount could not be added");
		// display_error( 'minus '.$supp_trans->ov_discount.' equals '.$total);
	}
	if ($supp_trans->trade_promo > 0)
	{
		$trade_promo_act = 4020030;
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $trade_promo_act, 0, 0,
			-(round2($supp_trans->trade_promo,2)),$supp_trans->supplier_id,
			"The general ledger transaction for discount could not be added");
		// display_error( 'minus '.$supp_trans->ov_discount.' equals '.$total);
	}
	if ($supp_trans->rebate > 0)
	{
		$rebate_act = 4020040;
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $rebate_act, 0, 0,
			-(round2($supp_trans->rebate,2)),$supp_trans->supplier_id,
			"The general ledger transaction for discount could not be added");
		// display_error( 'minus '.$supp_trans->ov_discount.' equals '.$total);
	}
	
	
	// if ($supp_trans->ewt > 0)
	// $total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $company_pref["default_purchase_ewt_act"], 0, 0,
		// -(round2($supp_trans->ewt,2)),
		// $supp_trans->supplier_id,
		// "The general ledger transaction for the control ewt could not be added");
	

    /*Loop through the GL Entries and create a debit posting for each of the accounts entered */

    /*the postings here are a little tricky, the logic goes like this:
    if its a general ledger amount it goes straight to the account specified

    if its a GRN amount invoiced then :

    The cost as originally credited to GRN suspense on arrival of items is debited to GRN suspense. Any difference
    between the std cost and the currency cost charged as converted at the ex rate of of the invoice is written off
    to the purchase price variance account applicable to the item being invoiced.
    */
    foreach ($supp_trans->gl_codes as $entered_gl_code)
    {

	    /*GL Items are straight forward - just do the debit postings to the GL accounts specified -
	    the credit is to creditors control act  done later for the total invoice value + tax*/

		if (!$supp_trans->is_invoice)
			$entered_gl_code->amount = -$entered_gl_code->amount;

		$memo_ = $entered_gl_code->memo_;
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $entered_gl_code->gl_code,
			$entered_gl_code->gl_dim, $entered_gl_code->gl_dim2, round2($entered_gl_code->amount,2), $supp_trans->supplier_id, "", 0, $memo_);

		add_supp_invoice_gl_item($trans_type, $invoice_id, $entered_gl_code->gl_code,
			$entered_gl_code->amount, $memo_);

		// store tax details if the gl account is a tax account
		if (!$supp_trans->is_invoice)
			$entered_gl_code->amount = -$entered_gl_code->amount;
		add_gl_tax_details($entered_gl_code->gl_code, 
			$trans_type, $invoice_id, $entered_gl_code->amunt,
			$ex_rate, $gl_date, $supp_trans->supp_reference);
    }
	
	$stock_gl_code = '';
	$inv_items_total = 0;
    foreach ($supp_trans->grn_items as $entered_grn)
    {

    	if (!$supp_trans->is_invoice)
    	{
			$entered_grn->this_quantity_inv = -$entered_grn->this_quantity_inv;
			set_grn_item_credited($entered_grn, $supp_trans->supplier_id, $invoice_id, $gl_date);
    	}
		$line_taxfree = $entered_grn->taxfree_charge_price_vat_inc($supp_trans->tax_group_id);
		// $line_tax = $entered_grn->full_charge_price($supp_trans->tax_group_id) - $line_taxfree;
		$line_tax = $entered_grn->chg_price-$line_taxfree;
		$stock_gl_code = get_stock_gl_code($entered_grn->item_code);
		
		// $iv_act = (is_inventory_item($entered_grn->item_code) ? $stock_gl_code["inventory_account"] : $stock_gl_code["cogs_account"]);
		// $total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $iv_act,
			// $stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],
			// $entered_grn->this_quantity_inv * $line_taxfree, $supp_trans->supplier_id);
		
		$inv_items_total += $entered_grn->this_quantity_inv * $line_taxfree;
		
    	// -------------- if price changed since po received. 16 Aug 2008 Joe Hunt
    	if($supp_trans->is_invoice)
    	{
    		$old = update_supp_received_items_for_invoice($entered_grn->id, $entered_grn->po_detail_item,
    			$entered_grn->this_quantity_inv, $entered_grn->chg_price, '');
			// Since the standard cost is always calculated on basis of the po unit_price,
			// this is also the price that should be the base of calculating the price diff.
			// In cases where there is two different po invoices on the same delivery with different unit prices this will not work either

			//$old_price = $old[0];
			 
			$old_price = $old[2];

			/*
 			If statement is removed. Should always check for deliveries nomatter if there has been a price change. 
			*/
			//if ($old_price != $entered_grn->chg_price) // price-change, so update
			//{
			//$diff = $entered_grn->chg_price - $old_price;
			$old_date = sql2date($old[1]);
			$diff = get_diff_in_home_currency($supp_trans->supplier_id, $old_date, $gl_date, $old_price, 
				$entered_grn->chg_price);
			// always return due to change in currency.
			$mat_cost = update_average_material_cost(null, $entered_grn->item_code,
				$diff, $entered_grn->this_quantity_inv, $old_date, true);
				
			// added 2008-12-08 Joe Hunt. Update the purchase data table
			// add_or_update_purchase_data($supp_trans->supplier_id, $entered_grn->item_code, $entered_grn->chg_price); 
			
			$deliveries = get_deliveries_between($entered_grn->item_code, $old_date, Today()); // extend the period, if invoice is before any deliveries.
			if ($deliveries[0] != 0) // have deliveries been done during the period?
			{
				$deliveries[1] /= $deliveries[0];
				$amt = ($mat_cost - $deliveries[1]) * $deliveries[0]; // $amt in home currency
	
				update_stock_move_pid(ST_CUSTDELIVERY, $entered_grn->item_code, $old_date, $date_, 0, $mat_cost);
			}
			update_stock_move_pid(ST_SUPPRECEIVE, $entered_grn->item_code, $old_date, $old_date, $supp_trans->supplier_id, $mat_cost);
		//}
		}
    	// ----------------------------------------------------------------------
		add_supp_invoice_item($trans_type, $invoice_id, $entered_grn->item_code,
			$entered_grn->item_description, 0, 	$line_taxfree, $line_tax,
			$entered_grn->this_quantity_inv, $entered_grn->id, $entered_grn->po_detail_item, "", '', $entered_grn->r_uom, $entered_grn->multiplier);
    } /* end of GRN postings */
	
	// $iv_act = (is_inventory_item($entered_grn->item_code) ? $company_pref["default_inventory_act"] : $company_pref["default_cogs_act"]);
	$iv_act = $company_pref['purchase_vat'];
	$iv_act_nv = $company_pref['purchase_non_vat'];
	
	if ($supp_trans->purch_vat > 0)
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $iv_act,$stock_gl_code['dimension_id'], 
			$stock_gl_code['dimension2_id'],$supp_trans->purch_vat, $supp_trans->supplier_id);
	
	// display_error( 'p VAT : '.$total);
	if ($supp_trans->purch_non_vat != 0)
	{
		//get vat and non vat account
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date, $iv_act_nv,$stock_gl_code['dimension_id'], 
			$stock_gl_code['dimension2_id'],round2($supp_trans->purch_non_vat,2), $supp_trans->supplier_id);
	}
			
    /* Now the TAX account */
    $taxes = $supp_trans->get_taxes($supp_trans->tax_group_id, 0, false); // 2009.08-18 Joe Hunt. We have already got the gl lines
    foreach ($taxes as $taxitem)
    {
    	if ($taxitem['Net'] != 0)
    	{

    		if (!$supp_trans->is_invoice)
    		{
    			$taxitem['Net'] = -$taxitem['Net'];
    			$taxitem['Value'] = -$taxitem['Value'];
    		}	
    		// here we suppose that tax is never included in price (we are company customer).
			add_trans_tax_details($trans_type, $invoice_id, 
				$taxitem['tax_type_id'], $taxitem['rate'], 0, $tax_total,
				$invoice_items_total, $ex_rate, $gl_date, $supp_trans->supp_reference);

    		if (!$supp_trans->is_invoice)
    			$taxitem['Value'] = -$taxitem['Value'];
			
			$total += add_gl_trans_supplier($trans_type, $invoice_id, $gl_date,
    			$taxitem['purchasing_gl_code'], 0, 0, round2($tax_total,2),
    			$supp_trans->supplier_id,
    			"A general ledger transaction for the tax amount could not be added");
			// display_error( 'taxes : '.round2($taxitem['Value'],2));
    	}
    }
	
	/*Post a balance post if $total != 0 */
	// add_gl_balance($trans_type, $invoice_id, $date_, -$total, PT_SUPPLIER, $supp_trans->supplier_id);	
	// display_error(round2($total,2));
	// if (abs(round2($total,2)) != 0)
	// {
		// $sql = "UPDATE ".TB_PREF."gl_trans SET amount = amount - $total WHERE account = ".db_escape($company_pref["creditors_act"])."
			// AND type = $trans_type AND type_no = $invoice_id";
		// db_query($sql, 'failed to correct payables account');
		
		// $sql = "UPDATE ".TB_PREF."supp_trans SET ov_amount = ov_amount+$total WHERE type = $trans_type AND trans_no = $invoice_id";
		// db_query($sql, 'failed to correct ov_amount');
	// }
	

	add_comments($trans_type, $invoice_id, $date_, $supp_trans->Comments);

	$Refs->save($trans_type, $invoice_id, $new_ref/* rhan 212018 $supp_trans->reference*/);

	if ($invoice_no != 0)
	{
		$invoice_alloc_balance = get_supp_trans_allocation_balance(ST_SUPPINVOICE, $invoice_no);
		$total = -$invoice_items_total;
		if ($invoice_alloc_balance > 0) 
		{ 	//the invoice is not already fully allocated 
		
			// $trans = get_supp_trans($invoice_no, ST_SUPPINVOICE);
			// $total = $trans['Total'];

			$allocate_amount = ($invoice_alloc_balance > $total) ? $total : $invoice_alloc_balance;
			/*Now insert the allocation record if > 0 */
			if ($allocate_amount != 0) 
			{
				update_supp_trans_allocation(ST_SUPPINVOICE, $invoice_no, $allocate_amount);
				update_supp_trans_allocation(ST_SUPPCREDIT, $invoice_id, $allocate_amount); // ***
				add_supp_allocation($allocate_amount, ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no,
					$date_);
				// Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////

				exchange_variation(ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no, $date_,
					$allocate_amount, PT_SUPPLIER);

				///////////////////////////////////////////////////////////////////////////
			}
		}
	}

    commit_transaction();

    return $invoice_id;
}

function add_supp_invoice($supp_trans, $invoice_no=0) // do not receive as ref because we change locally
{
	global $Refs;
	
	$company_pref = get_company_prefs();

	$vat_inc = true;
	//$company_currency = get_company_currency();
	/*Start an sql transaction */
	begin_transaction();
	
	$tax_total = 0;
    // $taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);

    // foreach ($taxes as $taxitem)
    // {
		// $taxitem['Value'] =  round2($taxitem['Value'], user_price_dec());
    	// $tax_total += $taxitem['Value'];
    // }

    $invoice_items_total = $supp_trans->get_total_charged($supp_trans->tax_group_id);

	if($vat_inc) { // INCLUSIVE 
		$item_added_tax = $tax_total;
	} else {
		$item_added_tax = 0;
		
	}
	
	if ($supp_trans->is_invoice)
		$trans_type = ST_SUPPINVOICE;
	else
	{
		$trans_type = ST_SUPPCREDIT;
		// let's negate everything because it's a credit note
		$invoice_items_total = -$invoice_items_total;
		$tax_total = -$tax_total;
		$supp_trans->ov_discount = -$supp_trans->ov_discount; // this isn't used at all...
		$item_added_tax = -$item_added_tax;
	}
	
	if($vat_inc) // INCLUSIVE 
		$invoice_items_total -= $item_added_tax;

    $date_ = $supp_trans->tran_date;
	$ex_rate = get_exchange_rate_from_home_currency(get_supplier_currency($supp_trans->supplier_id), $date_);

	//rhan 212018
	if (!$supp_trans->nt)
	{
		$new_r = get_new_ref();
		$new_ref_ = preg_replace('/[^0-9]/','',$new_r);
		$new_ref_ref = 	$new_ref_+1;
		$new_ref = 	'SRS_'.$new_ref_ref;

	}else{
		$new_r = get_new_ref();
		$new_ref_ = preg_replace('/[^0-9]/','',$new_r);
		$new_nt_ref = $new_ref_+1;
		if(!$supp_trans->addback){
			$new_ref = 	'NTSRS_'.$new_nt_ref;
		}else{
			$new_ref = 	'SRS_'.$new_nt_ref;
		}
	}
	//rhan

    /*First insert the invoice into the supp_trans table*/
	$invoice_id = add_supp_trans($trans_type, $supp_trans->supplier_id, 
													$date_, 
													$supp_trans->due_date,
													$new_ref /* rhan 212018 $supp_trans->reference*/, 
													$supp_trans->supp_reference,
													$invoice_items_total-$supp_trans->ewt, 
													$tax_total, 
													$supp_trans->ov_discount,
													"",
													0,
													$supp_trans->ewt+0,
													$supp_trans->del_date,
													$supp_trans->nt,
													$supp_trans->ewt_percent,
													$supp_trans->special_reference
													);

		//display_error('-->'.$supp_trans->del_date);
	if ($trans_type == ST_SUPPINVOICE)
		update_purchase_book($date_, $supp_trans->supplier_id, $trans_type, $invoice_id, $supp_trans->supp_reference, 0, 0, 0,
			0,0);
	else if ($trans_type == ST_SUPPCREDIT)
	{
		$sql__ = "SELECT * FROM ".TB_PREF."books_purchase WHERE trans_type = ".ST_SUPPINVOICE." AND trans_no = $invoice_no";
		// display_error($sql__);
		$res__ = db_query($sql__);
		$row__ = db_fetch($res__);
		if($invoice_no!=""&&$invoice_no!=null)
		update_purchase_book(sql2date($row__['date']), $row__['supplier_id'], ST_SUPPINVOICE, $invoice_no, $row__['reference'], $row__['purchase_amount'], 
			$row__['ewt'], $row__['input_tax']+$tax_total,$row__['discount'], $row__['returns'] + abs($invoice_items_total));
	}
	
	
	if($vat_inc) // INCLUSIVE 
		$invoice_items_total += $item_added_tax;
		
	$total = 0;
    /* Now the control account */
    $supplier_accounts = get_supplier_accounts($supp_trans->supplier_id);
	
	if ($supp_trans->nt AND strpos($supp_trans->reference,'NT') !== false)
		$company_pref["creditors_act"] = $company_pref["creditors_act_nt"];
	
	// pending off invoice discount
	
    $total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $company_pref["creditors_act"], 0, 0,
		-(round2($invoice_items_total,2) - round2($supp_trans->ov_discount,2) - round2($supp_trans->ewt,2)),
		$supp_trans->supplier_id,
		"The general ledger transaction for the control total could not be added");
	// display_error($total);
	
	if ($supp_trans->ov_discount > 0)
	$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $company_pref["pyt_discount_act"], 0, 0,
		-(round2($supp_trans->ov_discount,2)),
		$supp_trans->supplier_id,
		"The general ledger transaction for discount could not be added");
	// display_error( 'minus '.$supp_trans->ov_discount.' equals '.$total);
	
	if ($supp_trans->ewt > 0)
	$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $company_pref["default_purchase_ewt_act"], 0, 0,
		-(round2($supp_trans->ewt,2)),
		$supp_trans->supplier_id,
		"The general ledger transaction for the control ewt could not be added");
	

    /*Loop through the GL Entries and create a debit posting for each of the accounts entered */

    /*the postings here are a little tricky, the logic goes like this:
    if its a general ledger amount it goes straight to the account specified

    if its a GRN amount invoiced then :

    The cost as originally credited to GRN suspense on arrival of items is debited to GRN suspense. Any difference
    between the std cost and the currency cost charged as converted at the ex rate of of the invoice is written off
    to the purchase price variance account applicable to the item being invoiced.
    */
    foreach ($supp_trans->gl_codes as $entered_gl_code)
    {

	    /*GL Items are straight forward - just do the debit postings to the GL accounts specified -
	    the credit is to creditors control act  done later for the total invoice value + tax*/

		if (!$supp_trans->is_invoice)
			$entered_gl_code->amount = -$entered_gl_code->amount;

		$memo_ = $entered_gl_code->memo_;
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $entered_gl_code->gl_code,
			$entered_gl_code->gl_dim, $entered_gl_code->gl_dim2, round2($entered_gl_code->amount,2), $supp_trans->supplier_id, "", 0, $memo_);

		add_supp_invoice_gl_item($trans_type, $invoice_id, $entered_gl_code->gl_code,
			$entered_gl_code->amount, $memo_);

		// store tax details if the gl account is a tax account
		if (!$supp_trans->is_invoice)
			$entered_gl_code->amount = -$entered_gl_code->amount;
		add_gl_tax_details($entered_gl_code->gl_code, 
			$trans_type, $invoice_id, $entered_gl_code->amunt,
			$ex_rate, $date_, $supp_trans->supp_reference);
    }
	
	$stock_gl_code = '';
	$inv_items_total = 0;
    foreach ($supp_trans->grn_items as $entered_grn)
    {

    	if (!$supp_trans->is_invoice)
    	{
			$entered_grn->this_quantity_inv = -$entered_grn->this_quantity_inv;
			set_grn_item_credited($entered_grn, $supp_trans->supplier_id, $invoice_id, $date_);
    	}
		$line_taxfree = $entered_grn->taxfree_charge_price_vat_inc($supp_trans->tax_group_id);
		// $line_tax = $entered_grn->full_charge_price($supp_trans->tax_group_id) - $line_taxfree;
		$line_tax = $entered_grn->chg_price-$line_taxfree;
		$stock_gl_code = get_stock_gl_code($entered_grn->item_code);
		
		// $iv_act = (is_inventory_item($entered_grn->item_code) ? $stock_gl_code["inventory_account"] : $stock_gl_code["cogs_account"]);
		// $total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $iv_act,
			// $stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],
			// $entered_grn->this_quantity_inv * $line_taxfree, $supp_trans->supplier_id);
		
		$inv_items_total += $entered_grn->this_quantity_inv * $line_taxfree;
		
    	// -------------- if price changed since po received. 16 Aug 2008 Joe Hunt
    	if($supp_trans->is_invoice)
    	{
    		$old = update_supp_received_items_for_invoice($entered_grn->id, $entered_grn->po_detail_item,
    			$entered_grn->this_quantity_inv, $entered_grn->chg_price, '');
			// Since the standard cost is always calculated on basis of the po unit_price,
			// this is also the price that should be the base of calculating the price diff.
			// In cases where there is two different po invoices on the same delivery with different unit prices this will not work either

			//$old_price = $old[0];
			 
			$old_price = $old[2];

			/*
 			If statement is removed. Should always check for deliveries nomatter if there has been a price change. 
			*/
			//if ($old_price != $entered_grn->chg_price) // price-change, so update
			//{
			//$diff = $entered_grn->chg_price - $old_price;
			$old_date = sql2date($old[1]);
			$diff = get_diff_in_home_currency($supp_trans->supplier_id, $old_date, $date_, $old_price, 
				$entered_grn->chg_price);
			// always return due to change in currency.
			$mat_cost = update_average_material_cost(null, $entered_grn->item_code,
				$diff, $entered_grn->this_quantity_inv, $old_date, true);
				
			// added 2008-12-08 Joe Hunt. Update the purchase data table
			// add_or_update_purchase_data($supp_trans->supplier_id, $entered_grn->item_code, $entered_grn->chg_price); 
			
			$deliveries = get_deliveries_between($entered_grn->item_code, $old_date, Today()); // extend the period, if invoice is before any deliveries.
			if ($deliveries[0] != 0) // have deliveries been done during the period?
			{
				$deliveries[1] /= $deliveries[0];
				$amt = ($mat_cost - $deliveries[1]) * $deliveries[0]; // $amt in home currency
	
				update_stock_move_pid(ST_CUSTDELIVERY, $entered_grn->item_code, $old_date, $date_, 0, $mat_cost);
			}
			update_stock_move_pid(ST_SUPPRECEIVE, $entered_grn->item_code, $old_date, $old_date, $supp_trans->supplier_id, $mat_cost);
		//}
		}
    	// ----------------------------------------------------------------------
		add_supp_invoice_item($trans_type, $invoice_id, $entered_grn->item_code,
			$entered_grn->item_description, 0, 	$line_taxfree, $line_tax,
			$entered_grn->this_quantity_inv, $entered_grn->id, $entered_grn->po_detail_item, "", '', $entered_grn->r_uom, $entered_grn->multiplier);
    } /* end of GRN postings */
	
	// $iv_act = (is_inventory_item($entered_grn->item_code) ? $company_pref["default_inventory_act"] : $company_pref["default_cogs_act"]);
	$iv_act = $company_pref['purchase_vat'];
	$iv_act_nv = $company_pref['purchase_non_vat'];
	
	if (($supp_trans->ov_amount - $tax_total - $supp_trans->ov_nv) != 0)
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $iv_act,$stock_gl_code['dimension_id'], 
			$stock_gl_code['dimension2_id'],abs($supp_trans->ov_amount - $tax_total - $supp_trans->ov_nv), $supp_trans->supplier_id);
	
	// display_error( 'p VAT : '.$total);
	if ($supp_trans->ov_nv != 0)
	{
		//get vat and non vat account
		$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_, $iv_act_nv,$stock_gl_code['dimension_id'], 
			$stock_gl_code['dimension2_id'],round2($supp_trans->ov_nv,2), $supp_trans->supplier_id);
	}
			
    /* Now the TAX account */
    $taxes = $supp_trans->get_taxes($supp_trans->tax_group_id, 0, false); // 2009.08-18 Joe Hunt. We have already got the gl lines
    foreach ($taxes as $taxitem)
    {
    	if ($taxitem['Net'] != 0)
    	{

    		if (!$supp_trans->is_invoice)
    		{
    			$taxitem['Net'] = -$taxitem['Net'];
    			$taxitem['Value'] = -$taxitem['Value'];
    		}	
    		// here we suppose that tax is never included in price (we are company customer).
			add_trans_tax_details($trans_type, $invoice_id, 
				$taxitem['tax_type_id'], $taxitem['rate'], 0, $taxitem['Value'],
				$taxitem['Net'], $ex_rate, $date_, $supp_trans->supp_reference);

    		if (!$supp_trans->is_invoice)
    			$taxitem['Value'] = -$taxitem['Value'];
			
			$total += add_gl_trans_supplier($trans_type, $invoice_id, $date_,
    			$taxitem['purchasing_gl_code'], 0, 0, round2($taxitem['Value'],2),
    			$supp_trans->supplier_id,
    			"A general ledger transaction for the tax amount could not be added");
			// display_error( 'taxes : '.round2($taxitem['Value'],2));
    	}
    }
	
	/*Post a balance post if $total != 0 */
	// add_gl_balance($trans_type, $invoice_id, $date_, -$total, PT_SUPPLIER, $supp_trans->supplier_id);	
	// display_error(round2($total,2));
	if (abs(round2($total,2)) != 0)
	{
		$sql = "UPDATE ".TB_PREF."gl_trans SET amount = amount - $total WHERE account = ".db_escape($company_pref["creditors_act"])."
			AND type = $trans_type AND type_no = $invoice_id";
		db_query($sql, 'failed to correct payables account');
		
		$sql = "UPDATE ".TB_PREF."supp_trans SET ov_amount = ov_amount+$total WHERE type = $trans_type AND trans_no = $invoice_id";
		db_query($sql, 'failed to correct ov_amount');
	}
	

	add_comments($trans_type, $invoice_id, $date_, $supp_trans->Comments);

	$Refs->save($trans_type, $invoice_id, $new_ref/* rhan 212018 $supp_trans->reference*/);

	if ($invoice_no != 0)
	{
		$invoice_alloc_balance = get_supp_trans_allocation_balance(ST_SUPPINVOICE, $invoice_no);
		$total = -$invoice_items_total;
		if ($invoice_alloc_balance > 0) 
		{ 	//the invoice is not already fully allocated 
		
			// $trans = get_supp_trans($invoice_no, ST_SUPPINVOICE);
			// $total = $trans['Total'];

			$allocate_amount = ($invoice_alloc_balance > $total) ? $total : $invoice_alloc_balance;
			/*Now insert the allocation record if > 0 */
			if ($allocate_amount != 0) 
			{
				update_supp_trans_allocation(ST_SUPPINVOICE, $invoice_no, $allocate_amount);
				update_supp_trans_allocation(ST_SUPPCREDIT, $invoice_id, $allocate_amount); // ***
				add_supp_allocation($allocate_amount, ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no,
					$date_);
				// Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////

				exchange_variation(ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no, $date_,
					$allocate_amount, PT_SUPPLIER);

				///////////////////////////////////////////////////////////////////////////
			}
		}
	}

    commit_transaction();

    return $invoice_id;
}

//----------------------------------------------------------------------------------------

// get all the invoices/credits for a given PO - quite long route to get there !

function get_po_invoices_credits($po_number)
{
	$sql = "SELECT DISTINCT ".TB_PREF."supp_trans.trans_no, ".TB_PREF."supp_trans.type,
		ov_amount+ov_discount+ov_gst AS Total,
		".TB_PREF."supp_trans.tran_date
		FROM ".TB_PREF."supp_trans, ".TB_PREF."supp_invoice_items, "
		.TB_PREF."purch_order_details, ".TB_PREF."purch_orders
		WHERE ".TB_PREF."supp_invoice_items.supp_trans_no = ".TB_PREF."supp_trans.trans_no
		AND ".TB_PREF."supp_invoice_items.po_detail_item_id = ".TB_PREF."purch_order_details.po_detail_item
		AND ".TB_PREF."purch_orders.supplier_id = ".TB_PREF."supp_trans.supplier_id
		AND ".TB_PREF."purch_order_details.order_no = ".db_escape($po_number);

	return db_query($sql, "The invoices/credits for the po $po_number could not be retrieved");
}

//----------------------------------------------------------------------------------------

function read_supp_invoice($trans_no, $trans_type, &$supp_trans)
{
	$sql = "SELECT ".TB_PREF."supp_trans.*, supp_name FROM ".TB_PREF."supp_trans,".TB_PREF."suppliers
		WHERE trans_no = ".db_escape($trans_no)." AND type = ".db_escape($trans_type)."
		AND ".TB_PREF."suppliers.supplier_id=".TB_PREF."supp_trans.supplier_id";
	$result = db_query($sql, "Cannot retrieve a supplier transaction");

	if (db_num_rows($result) == 1)
	{
		$trans_row = db_fetch($result);

		$supp_trans->supplier_id = $trans_row["supplier_id"];
		$supp_trans->supplier_name = $trans_row["supp_name"];
		$supp_trans->tran_date = sql2date($trans_row["tran_date"]);
		$supp_trans->due_date = sql2date($trans_row["due_date"]);
		$supp_trans->del_date = sql2date($trans_row["del_date"]);
		//$supp_trans->Comments = $trans_row["TransText"];
		$supp_trans->Comments = "";
		$supp_trans->reference = $trans_row["reference"];
		$supp_trans->supp_reference = $trans_row["supp_reference"];
		$supp_trans->ov_amount = $trans_row["ov_amount"];
		$supp_trans->ov_discount = $trans_row["ov_discount"];
		$supp_trans->ov_gst = $trans_row["ov_gst"];
		$supp_trans->ewt = $trans_row["ewt"];
		
		$supp_trans->nt = $trans_row["non_trade"];
		$supp_trans->ewt_percent = $trans_row["ewt_percent"];

		$id = $trans_row["trans_no"];

		$result = get_supp_invoice_items($trans_type, $id);

		if (db_num_rows($result) > 0)
		{

            while ($details_row = db_fetch($result))
            {
             	if ($details_row["gl_code"] == 0)
             	{
             		$supp_trans->add_grn_to_trans($details_row["grn_item_id"], $details_row["po_detail_item_id"], $details_row["stock_id"],
           				$details_row["description"], 0, 0, $details_row["quantity"], 0, $details_row["FullUnitPrice"],
           				false, 0, 0,$details_row['i_uom'], $details_row["multiplier"]);
             	}
             	else
             	{
             		$supp_trans->add_gl_codes_to_trans($details_row["gl_code"], get_gl_account_name($details_row["gl_code"]), 0, 0,
           				$details_row["FullUnitPrice"], $details_row["memo_"]);
             	}
            }
        }
        else
        {
			return display_db_error("Invalid supptrans details for supptrans number : $trans_no and type : $trans_type", $sql, true);
		}

	}
	else
	{
		return display_db_error("Invalid supptrans number : $trans_no and type : $trans_type", $sql, true);
	}
}

//----------------------------------------------------------------------------------------

function get_matching_invoice_item($stock_id, $po_item_id)
{
	$sql = "SELECT *, tran_date FROM ".TB_PREF."supp_invoice_items, ".TB_PREF."supp_trans
		WHERE supp_trans_type = ".ST_SUPPINVOICE." AND stock_id = "
		.db_escape($stock_id)." AND po_detail_item_id = ".db_escape($po_item_id)."
		AND supp_trans_no = trans_no";
	$result = db_query($sql, "Cannot retrieve supplier transaction detail records");
	return db_fetch($result);  
}

function void_supp_invoice($type, $type_no)
{
	begin_transaction();

	$trans = get_supp_trans($type_no, $type);

	if ($type == ST_SUPPCREDIT)
	{
		$sql___ = "SELECT * FROM ".TB_PREF."supp_allocations WHERE trans_no_from = $type_no AND trans_type_from = ".ST_SUPPCREDIT;
		$res____ = db_query($sql___);
		$row___ = db_fetch($res____);
	}
	
	void_bank_trans($type, $type_no, true);

	void_gl_trans($type, $type_no, true);

	void_supp_allocations($type, $type_no);

	void_supp_trans($type, $type_no);

	$result = get_supp_invoice_items($type, $type_no);

	// now remove this invoice/credit from any GRNs/POs that it's related to
	if (db_num_rows($result) > 0)
	{
		$date_ = Today();
        while ($details_row = db_fetch($result))
        {
         	if ((int)$details_row["grn_item_id"] > 0) // it can be empty for GL items
         	{
         		// Changed 2008-10-17 by Joe Hunt to get the avg. material cost updated
				$old = update_supp_received_items_for_invoice($details_row["grn_item_id"],
					$details_row["po_detail_item_id"], -$details_row["quantity"], $details_row["FullUnitPrice"]); 
				
				//$diff = $details_row["FullUnitPrice"] - $old[2];
				$old_date = sql2date($old[1]);
				
				$batch = get_grn_batch_from_item($details_row["grn_item_id"]);	
				$grn = get_grn_batch($batch);
         		if ($type == ST_SUPPCREDIT) // credit note 2009-06-14 Joe Hunt Must restore the po and grn
         		{	// We must get the corresponding invoice item to check for price chg.
					$match = get_matching_invoice_item($details_row["stock_id"], $details_row["po_detail_item_id"]);
					if ($match !== false)
						$mat_cost = update_average_material_cost($grn["supplier_id"], $details_row["stock_id"],  
							$match["unit_price"], -$details_row["quantity"], sql2date($match['tran_date']), $match['tran_date'] !== $trans['tran_date']);
					else		
						$mat_cost = update_average_material_cost($grn["supplier_id"], $details_row["stock_id"],  
							$details_row["FullUnitPrice"], -$details_row["quantity"], $old_date, $old[1] !== $trans['tran_date']);
					$sql = "UPDATE ".TB_PREF."purch_order_details
        				SET quantity_ordered = quantity_ordered + ".-$details_row["quantity"].", ";
        			if ($match !== false)
        				$sql .= "act_price=".$match['unit_price'].", ";
    				$sql .= "quantity_received = quantity_received + ".-$details_row["quantity"]."
    				    WHERE po_detail_item = ".$details_row["po_detail_item_id"];
					db_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
					$sql = "UPDATE ".TB_PREF."grn_items SET qty_recd=qty_recd+".-$details_row["quantity"]."
						WHERE id=".$details_row["grn_item_id"];
					db_query($sql);
    			}
    			else
    			{
					$diff = get_diff_in_home_currency($grn["supplier_id"], $old_date, sql2date($trans['tran_date']), $old[2], 
						$details_row["FullUnitPrice"]);
					// Only adjust the avg for the diff
					$mat_cost = update_average_material_cost(null, $details_row["stock_id"],
						$diff, -$details_row["quantity"], $old_date, true);
				}
				$deliveries = get_deliveries_between($details_row["stock_id"], $old_date, $date_);
				if ($deliveries[0] != 0) // have deliveries been done during the period?
				{
					update_stock_move_pid(ST_CUSTDELIVERY, $details_row["stock_id"], $old_date, $date_, 0, $mat_cost);
				}
				update_stock_move_pid(ST_SUPPRECEIVE, $details_row["stock_id"], $old_date, $old_date, $grn['supplier_id'], $mat_cost);
         	}
        }
	}

	if ($type == ST_SUPPCREDIT) // void the credits in stock moves
	{
		void_stock_move($type, $type_no); 
		
		$sql__ = "SELECT * FROM ".TB_PREF."books_purchase WHERE trans_type = ".ST_SUPPINVOICE." AND trans_no = ".$row___['trans_no_to'];
		$res__ = db_query($sql__);
		$row__ = db_fetch($res__);
		
		update_purchase_book(sql2date($row__['date']), $row__['supplier_id'], ST_SUPPINVOICE, $row__['trans_no'], $row__['reference'], 
			$row__['purchase_amount'], $row__['ewt'], $row__['input_tax']-$trans['ov_gst'],$row__['discount'],$row__['returns']+$trans['ov_amount']);
		
	}
	else
	{
		$sql__ = "SELECT * FROM ".TB_PREF."books_purchase WHERE trans_type = ".ST_SUPPINVOICE." AND trans_no = $type_no";
		// display_error($sql__);
		$res__ = db_query($sql__);
		$row__ = db_fetch($res__);
		
		update_purchase_book(sql2date($row__['date']), $row__['supplier_id'], $type, $type_no, $row__['reference'], 0, 
			0,0,0,0);
	}
		
		
	void_supp_invoice_items($type, $type_no);
	void_trans_tax_details($type, $type_no);

	commit_transaction();
}

function void_supp_invoice_dis($type, $type_no)
{
	begin_transaction();

	$trans = get_supp_trans($type_no, $type);

	/* if ($type == ST_SUPPCREDIT)
	{
		$sql___ = "SELECT * FROM ".TB_PREF."supp_allocations WHERE trans_no_from = $type_no AND trans_type_from = ".ST_SUPPCREDIT;
		$res____ = db_query($sql___);
		$row___ = db_fetch($res____);
	} */
	
	void_bank_trans_dis($type, $type_no, true);

	void_gl_trans($type, $type_no, true);
	
	//void_supp_allocations($type, $type_no);

	//void_supp_trans($type, $type_no);

	$result = get_supp_invoice_items($type, $type_no);

	// now remove this invoice/credit from any GRNs/POs that it's related to
	if (db_num_rows($result) > 0)
	{
		$date_ = Today();
        while ($details_row = db_fetch($result))
        {
         	if ((int)$details_row["grn_item_id"] > 0) // it can be empty for GL items
         	{
         		// Changed 2008-10-17 by Joe Hunt to get the avg. material cost updated
				$old = update_supp_received_items_for_invoice($details_row["grn_item_id"],
					$details_row["po_detail_item_id"], -$details_row["quantity"], $details_row["FullUnitPrice"]); 
				
				//$diff = $details_row["FullUnitPrice"] - $old[2];
				$old_date = sql2date($old[1]);
				
				$batch = get_grn_batch_from_item($details_row["grn_item_id"]);	
				$grn = get_grn_batch($batch);
          	/*	if ($type == ST_SUPPCREDIT) // credit note 2009-06-14 Joe Hunt Must restore the po and grn
         		{	// We must get the corresponding invoice item to check for price chg.
					$match = get_matching_invoice_item($details_row["stock_id"], $details_row["po_detail_item_id"]);
					if ($match !== false)
						$mat_cost = update_average_material_cost($grn["supplier_id"], $details_row["stock_id"],  
							$match["unit_price"], -$details_row["quantity"], sql2date($match['tran_date']), $match['tran_date'] !== $trans['tran_date']);
					else		
						$mat_cost = update_average_material_cost($grn["supplier_id"], $details_row["stock_id"],  
							$details_row["FullUnitPrice"], -$details_row["quantity"], $old_date, $old[1] !== $trans['tran_date']);
					$sql = "UPDATE ".TB_PREF."purch_order_details
        				SET quantity_ordered = quantity_ordered + ".-$details_row["quantity"].", ";
        			if ($match !== false)
        				$sql .= "act_price=".$match['unit_price'].", ";
    				$sql .= "quantity_received = quantity_received + ".-$details_row["quantity"]."
    				    WHERE po_detail_item = ".$details_row["po_detail_item_id"];
					db_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
					$sql = "UPDATE ".TB_PREF."grn_items SET qty_recd=qty_recd+".-$details_row["quantity"]."
						WHERE id=".$details_row["grn_item_id"];
					db_query($sql);
    			}
    			else
    			{ */
					$diff = get_diff_in_home_currency($grn["supplier_id"], $old_date, sql2date($trans['tran_date']), $old[2], 
						$details_row["FullUnitPrice"]);
					// Only adjust the avg for the diff
					$mat_cost = update_average_material_cost(null, $details_row["stock_id"],
						$diff, -$details_row["quantity"], $old_date, true);
			//	}
				
				$deliveries = get_deliveries_between($details_row["stock_id"], $old_date, $date_);
				if ($deliveries[0] != 0) // have deliveries been done during the period?
				{
					update_stock_move_pid(ST_CUSTDELIVERY, $details_row["stock_id"], $old_date, $date_, 0, $mat_cost);
				}
				update_stock_move_pid(ST_SUPPRECEIVE, $details_row["stock_id"], $old_date, $old_date, $grn['supplier_id'], $mat_cost);
         	}
        }
	}

/* 	if ($type == ST_SUPPCREDIT) // void the credits in stock moves
	{
		void_stock_move($type, $type_no); 
		
		$sql__ = "SELECT * FROM ".TB_PREF."books_purchase WHERE trans_type = ".ST_SUPPINVOICE." AND trans_no = ".$row___['trans_no_to'];
		$res__ = db_query($sql__);
		$row__ = db_fetch($res__);
		
		update_purchase_book(sql2date($row__['date']), $row__['supplier_id'], ST_SUPPINVOICE, $row__['trans_no'], $row__['reference'], 
			$row__['purchase_amount'], $row__['ewt'], $row__['input_tax']-$trans['ov_gst'],$row__['discount'],$row__['returns']+$trans['ov_amount']);
		
	}
	else
	{ */
		$sql__ = "SELECT * FROM ".TB_PREF."books_purchase WHERE trans_type = ".ST_SUPPINVOICE." AND trans_no = $type_no";
		// display_error($sql__);
		$res__ = db_query($sql__);
		$row__ = db_fetch($res__);
		
		update_purchase_book(sql2date($row__['date']), $row__['supplier_id'], $type, $type_no, $row__['reference'], 0, 
			0,0,0,0);
//	}
	
	
	//void_supp_invoice_items($type, $type_no);
	void_trans_tax_details($type, $type_no);

	commit_transaction();
}

function add_purchase_book($date, $supplier_id='', $trans_type, $trans_no, $reference, $purchase_amount=0, $ewt=0, $input_tax=0, $discount=0, $returns=0){
	$sql = "INSERT INTO ".TB_PREF."books_purchase(date, supplier_id, trans_type, trans_no, reference, purchase_amount, ewt, input_tax, discount, returns)
				VALUES('".date2sql($date)."', ".db_escape($supplier_id).", $trans_type, $trans_no, '$reference', $purchase_amount, $ewt, $input_tax, $discount, $returns)";
	db_query($sql);
	//display_error($sql);
}

function update_purchase_book($date, $supplier_id, $trans_type, $trans_no, $reference, $purchase_amount, $ewt, $input_tax, $discount, $returns)
{
	delete_purchase_book($trans_type, $trans_no);	
	add_purchase_book($date, $supplier_id, $trans_type, $trans_no, $reference, $purchase_amount, $ewt, $input_tax, $discount, $returns);
	
	// $sql = "UPDATE ".TB_PREF."books_purchase SET
					// date = '".date2sql($date)."',
					// supplier_id = $supplier_id, 
					// trans_type = $trans_type, 
					// trans_no = $trans_no, 
					// reference = ".db_escape($reference).", 
					// purchase_amount = $purchase_amount, 
					// ewt = $ewt, 
					// input_tax = $input_tax, 
					// discount = $discount, 
					// returns = $returns
				// WHERE id = $id";
	// db_query($sql,'failed to update purchase book');
}

function delete_purchase_book($trans_type, $trans_no)
{
	$sql = "DELETE FROM ".TB_PREF."books_purchase
				WHERE trans_type = $trans_type
				AND trans_no =$trans_no";
	db_query($sql);
}
//----------------------------------------------------------------------------------------


?>
