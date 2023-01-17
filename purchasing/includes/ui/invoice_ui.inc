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
//--------------------------------------------------------------------------------------------------

function copy_from_trans(&$supp_trans)
{
	$_POST['Comments'] = $supp_trans->Comments;
	$_POST['tran_date'] = $supp_trans->tran_date;
	$_POST['due_date'] = $supp_trans->due_date;
	$_POST['supp_reference'] = $supp_trans->supp_reference;
	$_POST['reference'] = $supp_trans->reference;
	$_POST['supplier_id'] = $supp_trans->supplier_id;
	$_POST['del_date'] = $supp_trans->del_date;
}

//--------------------------------------------------------------------------------------------------

function copy_to_trans(&$supp_trans)
{
	global $Ajax;
	
	$supp_trans->Comments = $_POST['Comments'];
	$supp_trans->tran_date = $_POST['tran_date'];
	$supp_trans->due_date = $_POST['due_date'];
	$supp_trans->supp_reference = $_POST['supp_reference'];
	$supp_trans->reference = $_POST['reference'];
	$supp_trans->del_date = $_POST['del_date'];
	
	$supp_trans->ov_amount = $supp_trans->gl_amount = $supp_trans->ov_nv = $supp_trans->ov_discount = 0;/* for starters */
	
	if (count($supp_trans->grn_items) > 0)
	{
		foreach ( $supp_trans->grn_items as $grn)
		{
			$supp_trans->ov_amount += round2(($grn->this_quantity_inv * $grn->chg_price),4);
		}
	}
	if (count($supp_trans->gl_codes) > 0)
	{
		foreach ( $supp_trans->gl_codes as $gl_line)
		{
			////////// 2009-08-18 Joe Hunt
			// if (!is_tax_account($gl_line->gl_code))
				$supp_trans->gl_amount += $gl_line->amount;
		}
	}
	
	
	$supp_trans->ov_nv = $supp_trans->get_non_vat_item_total();
	
	if ($supp_trans->tax_group_id != 1) //transfer all to non vat
	{
		$supp_trans->ov_nv = $supp_trans->ov_amount;
	}
	
	// //============================== off invoice discount
	// $ret_array = get_off_invoice_discount($_POST['supplier_id'], $supp_trans->ov_amount);
	// $supp_trans->ov_discount = $ret_array[0];
	// $supp_trans->off_inv_disc = $ret_array[1];
	
	$Ajax->activate('inv_tot');
}

function get_off_invoice_discount($supplier_id, $ov_amount)
{
	$sql = "SELECT * FROM ".TB_PREF."supplier_off_invoice 
			WHERE supplier_id = ".$supplier_id."
			ORDER BY id";
	$res = db_query($sql);
	
	$off_inv_disc  = array();
	$ov_discount = 0;
	while($row = db_fetch($res))
	{
		$disc = $ov_amount * ($row['discount']/100);
		$ov_amount -= $disc;
		$ov_discount += $disc;
		$off_inv_disc[] = $row['discount'] . '%';
	}
	$ov_discount = round2($ov_discount,2);
	
	return array($ov_discount,$off_inv_disc);
}
//--------------------------------------------------------------------------------------------------
function get_discount_str($po_detail_item)
{
	$sql = "SELECT * FROM ".TB_PREF."purch_order_details WHERE po_detail_item=$po_detail_item";
	$res = db_query($sql);
	$row = db_fetch($res);
	$discounts = array();
	if ($row['disc_percent1'] != 0)
		$discounts[] = $row['disc_percent1'].'%';
	if ($row['disc_percent2'] != 0)
		$discounts[] = $row['disc_percent2'].'%';
	if ($row['disc_percent3'] != 0)
		$discounts[] = $row['disc_percent3'].'%';
	if ($row['disc_amount1'] != 0)
		$discounts[] = $row['disc_amount1'];
	if ($row['disc_amount2'] != 0)
		$discounts[] = $row['disc_amount2'];
	if ($row['disc_amount3'] != 0)
		$discounts[] = $row['disc_amount3'];
		
	return implode(', ',$discounts);
}

function invoice_header(&$supp_trans)
{
	global $Ajax, $table_style2, $Refs;
	
	// if vars have been lost, recopy
	if (!isset($_POST['tran_date']))
		copy_from_trans($supp_trans);

	start_outer_table("width=95% $table_style2");

	table_section(1);

	if (!$supp_trans->nt)
	{
			// get_srs_invoice_row('From Data Collector', 'choose_inv', null, false, true, '', false);
		start_row();
			echo '<td>';
			echo 'From Data Collector:';
			echo '</td>';
			echo '<td>';
				start_table();
				get_srs_invoice_cells('', 'choose_inv', null, true, true, '', false);
				submit_cells('reload_', 'Reload');
				end_table();
			echo '</td>';
		end_row();
	}

	if (isset($_POST['invoice_no']))
	{
		$trans = get_supp_trans($_POST['invoice_no'], ST_SUPPINVOICE);
		$_POST['supplier_id'] = $trans['supplier_id'];
		$supp = $trans['supplier_name'] . " - " . $trans['SupplierCurrCode'];
		label_row(_("Supplier:"), $supp.hidden('supplier_id', $_POST['supplier_id'], false));
	}	
	else if ($_POST['choose_inv'] != '')
	{
		$detalye = get_grn_batch($_POST['choose_inv']);
		$_POST['supplier_id'] = $detalye['supplier_id'];
		$supp = get_supplier_name($detalye['supplier_id']);
		label_row(_("Supplier:"), $supp.hidden('supplier_id', $_POST['supplier_id'], false));
	}
	else
	{
    	if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
    		$_POST['supplier_id'] = get_global_supplier();

		supplier_list_row(_("Supplier:"), 'supplier_id', $_POST['supplier_id'], false, true);
	}
	
	if ($supp_trans->supplier_id != $_POST['supplier_id'] OR list_updated('choose_inv') OR
		($_POST['choose_inv'] != '' AND isset($_POST['reload_'])))
	{
		// supplier has changed
		// delete all the order items - drastic but necessary because of
		// change of currency, etc
		// if (!isset($supp_trans))
		// {

		//rhan 2172018 for addback ui
		if (isset($supp_trans->addback)){
			$addback = true;
		}
		//rhan 2172018

		if (isset($supp_trans->nt))
			$_GET['NT'] = $supp_trans->nt;
		$supp_trans = new supp_trans;
		$supp_trans->is_invoice = true;
		
		if (isset($_GET['discrepancy_id']))
		{
			$supp_trans->discrepancy_id= $_GET['discrepancy_id'];
		}
		//rhan init addback again
		if ($addback){
			$supp_trans->addback = true;	
		}
		//rhan end

		if (isset($_GET['NT'])){
			$supp_trans->nt = true;	
		}
		// }
		$supp_trans->clear_items();
		read_supplier_details_to_trans($supp_trans, $_POST['supplier_id']);
		copy_from_trans($supp_trans);
	}
	
	if (list_updated('choose_inv') OR
		($_POST['choose_inv'] != '' AND isset($_POST['reload_'])))
	{
		//get latest grn_batch
		$sql = "SELECT * FROM ".TB_PREF."grn_batch WHERE id = ". $_POST['choose_inv'];
		$res = db_query($sql);
		$row = db_fetch($res);
		
		$sql = "SELECT id FROM ".TB_PREF."grn_batch 
					WHERE source_invoice_no = ". db_escape($row['source_invoice_no'])."
					AND supplier_id = ". $row['supplier_id']."
					ORDER BY delivery_date DESC";
		$res = db_query($sql);
		$row = db_fetch($res);
		
		$latest_grn_id = $row[0];
		// ========================================
		
		$grn__ = get_grn_batch($latest_grn_id);
		$_POST['supp_reference'] = $grn__['source_invoice_no'];
		$Ajax->activate('supp_reference');
		
		$_POST['tran_date'] = $_POST['del_date'] = sql2date($grn__['delivery_date']);
		$Ajax->activate('_ex_rate');
		$supp_trans->tran_date = $_POST['del_date'];
		get_duedate_from_terms($supp_trans);
		$_POST['due_date'] = $supp_trans->due_date;
		
		$supp_trans->special_reference = ltrim(get_reference(ST_PURCHORDER , $grn__['purch_order_no']),'0');
		
		$Ajax->activate('del_date');
		$Ajax->activate('due_date');
		$Ajax->activate('tran_date');
		$Ajax->activate('proc_disc');
		
	}

	$refref = $Refs->get_next(ST_SUPPINVOICE);
	$refref = str_replace('NT','',$refref);
	$refref = 'SRS_'.$refref;
	
	//if ($supp_trans->addback)
		
	if ($supp_trans->nt){
		//rhan 2172018 for addback entry no NT 
		if(!$addback){
			$refref = 'NT'.$refref;
		}else{
			$refref = $refref;
			hidden('addback_',true);
		}
		//rhan end
	}
		
		//hidden('reference', $refref);
	if ($supp_trans->is_invoice)
		//label_row(_("APV No.:"),'reference',$refref);
		label_row(_("APV No.:"),"<b>$refref</b>",hidden('reference', $refref));
    	//ref_row(_("APV No.:"), 'reference', '', $refref);
    else
    	ref_row(_("Credit Note No.:"), 'reference', '', $Refs->get_next(ST_SUPPCREDIT));

	if (isset($_POST['invoice_no']))
	{
		label_row(_("Supplier's Invoice #:"), hidden('supp_reference', $_POST['invoice_no'], false));
	}	
	else
   		text_row(_("Supplier's Invoice #:"), 'supp_reference', null);
	
	table_section(2, "33%");

   	date_row(_("Delivery Date") . ":", 'del_date', '', true, 0, 0, 0, "", true);
	
	if (isset($_POST['_del_date_changed'])) 
	{
		$Ajax->activate('_ex_rate');
		$supp_trans->tran_date = $_POST['del_date'];
		$_POST['tran_date'] = $_POST['del_date'];
		get_duedate_from_terms($supp_trans);
		$_POST['due_date'] = $supp_trans->due_date;
		$Ajax->activate('due_date');
		$Ajax->activate('tran_date');
	}

    date_row(_("Due Date") . ":", 'due_date');

    label_row(_("Terms:"), $supp_trans->terms_description);
	
	if ($_SESSION['wa_current_user']->company == 4)
	{
		if (!isset($_POST['no_ewt']))
			$_POST['no_ewt'] = 1;
	}
	
	if(get_company_pref('ewt_percent') > 0 AND !$supp_trans->nt)
		yesno_list_row('<font color=red><b>Type:</b></font>', 'no_ewt', null, "DR -- no EWT", "INVOICE -- with EWT", true, true);
	else
	{
		$_POST['no_ewt'] = 1;
		hidden('no_ewt',$_POST['no_ewt']);
	}
	table_section(3, "33%");

	date_row(_("APV Date") . ":", 'tran_date');
	
	set_global_supplier($_POST['supplier_id']);

	$supplier_currency = get_supplier_currency($supp_trans->supplier_id);

	$company_currency = get_company_currency();

	if ($supplier_currency != $company_currency)
	{
        label_row(_("Supplier's Currency:"), "<b>" . $supplier_currency . "</b>");
		exchange_rate_display($supplier_currency, $company_currency, $_POST['tran_date']);
	}

  	label_row("<b>Tax Group:</b>", '<b>'.$supp_trans->tax_description.'</b>');
	
	$s_notes = get_supplier_note($supp_trans->supplier_id);
	if ($s_notes != '')
		label_row("<b>Notes :</b>", $s_notes);
	
	end_outer_table(1);
}

//--------------------------------------------------------------------------------------------------

function invoice_totals(&$supp_trans)
{
	global $table_style, $table_style2;

	copy_to_trans($supp_trans);

	$vat_inc = true;
	$dim = get_company_pref('use_dimension');
 	$colspan = ($dim == 2 ? 7 : ($dim == 1 ? 6 : 5));
	
	echo "<span style='display:none;'>";
	$_taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
    $_tax_total = display_edit_tax_items($_taxes, $colspan, $vat_inc);
	echo "</span>";
	
	if (input_num('inv_total_no_hidden') > 0 AND input_num('inv_total_no_hidden') > $supp_trans->ov_amount)
		$msg_ = 'Invoice has hidden discount';
	else if (input_num('inv_total_no_hidden') > 0 AND input_num('inv_total_no_hidden') < $supp_trans->ov_amount)
		$msg_ = 'Invoice Amount is less than PO Amount';
	
	echo "<center><font color=red><b>$msg_</b></font></center>";
	
   	start_table("$table_style width=95%");
	
	
	if (!$supp_trans->nt)
	{
		// $supp_trans->ov_nv = $supp_trans->get_non_vat_item_total();
		$supp_trans->purch_discount = $p_nv_disc = $p_v_disc = $vat_disc = 0;
		$p_vat = abs($supp_trans->ov_amount - $_tax_total - $supp_trans->ov_nv);

		if (input_num('inv_total_no_hidden') > 0 AND input_num('inv_total_no_hidden') > $supp_trans->ov_amount)
		{
			$total_p_vat = $supp_trans->ov_amount - $supp_trans->ov_nv;
			
			$supp_trans->purch_discount = abs(round2($supp_trans->ov_amount - input_num('inv_total_no_hidden'),2));
			
			$p_nv_disc = abs(round2($supp_trans->ov_nv/$supp_trans->ov_amount * $supp_trans->purch_discount,2));
			$p_v_disc = $supp_trans->purch_discount - $p_nv_disc;
			
			$supp_trans->ov_nv += $p_nv_disc;
			// purch vat
			
			$p_vatable_disc = abs(round2($p_vat/$total_p_vat * $p_v_disc,2));
			$vat_disc = $p_v_disc - $p_vatable_disc;
			
			$p_vat += $p_vatable_disc;
			
			//=====================
			
			$display_total = number_format2($supp_trans->ov_amount+$supp_trans->purch_discount,2);
			$supp_trans->vat = $_tax_total + $vat_disc;
			$supp_trans->add_to_ov_amount = 0;
			
			$supp_trans->ov_amount = round($supp_trans->ov_amount,2);
			$supp_trans->ov_nv = round($supp_trans->ov_nv,2);
			$p_vat = round($p_vat,2);
			$supp_trans->vat = round($supp_trans->vat,2);
			$supp_trans->purch_discount = round($supp_trans->purch_discount,2);
			
			$variance = $supp_trans->ov_amount - ($supp_trans->ov_nv+$p_vat+$supp_trans->vat-$supp_trans->purch_discount);
			
		}
		// actual invoice amount lower than po amount
		else if (input_num('inv_total_no_hidden') > 0 AND input_num('inv_total_no_hidden') < $supp_trans->ov_amount)
		{
			$total_p_vat = $supp_trans->ov_amount - $supp_trans->ov_nv;

			// $vat_percentage = round2($_tax_total/($total_p_vat-$_tax_total),2);
			$vat_percentage = 0.12;
			
			$nv_percentage = $supp_trans->ov_nv/$supp_trans->ov_amount;
			$nv_percentage = round2($nv_percentage,5);
			$v_percentage = 1-$nv_percentage;
			
			$supp_trans->purch_discount = abs(round2($supp_trans->ov_amount - input_num('inv_total_no_hidden'),2));
			
			$supp_trans->ov_amount = input_num('inv_total_no_hidden');
			// display_error($nv_percentage);
			$supp_trans->ov_nv = $nv_percentage*$supp_trans->ov_amount;
			
			$p_vat = $supp_trans->ov_amount - $supp_trans->ov_nv;
			
			$p_vat = $p_vat/(1+$vat_percentage);
			
			$supp_trans->vat = $p_vat*$vat_percentage;
			
			$display_total = number_format2($supp_trans->ov_amount,2);
			
			$supp_trans->add_to_ov_amount = $supp_trans->purch_discount;
			
			$supp_trans->ov_amount = round($supp_trans->ov_amount,2);
			$supp_trans->ov_nv = round($supp_trans->ov_nv,2);
			$p_vat = round($p_vat,2);
			$supp_trans->vat = round($supp_trans->vat,2);
			$supp_trans->purch_discount = round($supp_trans->purch_discount,2);
			
			$variance = $supp_trans->ov_amount - ($supp_trans->ov_nv+$p_vat+$supp_trans->vat);
			
			//=====================
		}
		else
		{
			$supp_trans->add_to_ov_amount = 0;
			$display_total = number_format2($supp_trans->ov_amount+$supp_trans->purch_discount,2);
			$supp_trans->vat = $_tax_total + $vat_disc;
		}
		
		
		if ($variance != 0)
		{
			$p_vat += $variance;
		}
		
		$add_text = '';
		if (count($_taxes) == 0)
		{

			$supp_trans->ov_nv += $p_vat;
			$p_vat = $supp_trans->purch_vat = 0;
		}
		
		label_row(_("Purchase NON-VAT:"), number_format2($supp_trans->ov_nv,2), "colspan=$colspan align=right", "align=right");
			$supp_trans->purch_non_vat = round2($supp_trans->ov_nv,2);
		
		if (round2($p_vat,2) > 0)
		{
			label_row(_("Purchase VAT:"), number_format2($p_vat,2), "colspan=$colspan align=right", "align=right");
		}
			
		$supp_trans->purch_vat = round2($p_vat,2);
		
		// $taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
		// $tax_total = display_edit_tax_items($taxes, $colspan, $vat_inc);
		
		foreach ($_taxes as $taxitem)
		{
			start_row();
			label_cell($taxitem['tax_type_name'] . " (" . $taxitem['rate'] . "%) " .
				_("Amount:") , "colspan=$colspan align=right", "align=right",0);
			label_cell(number_format2($supp_trans->vat,2), 'align=right');
			end_row();
		}
		
		
		// if ($supp_trans->ov_discount > 0)
			// label_row("<font color=red>Less Off Invoice Discount (".implode(',',$supp_trans->off_inv_disc)."):</font>", 
				// number_format2(abs($supp_trans->ov_discount),2),"colspan=$colspan align=right", "align=right");
	}
	if ($supp_trans->is_invoice)
	{
		if (!$supp_trans->nt)
		{
			label_row(_("Invoice Total:"), $display_total, "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
			
			
			if (!$_POST['no_ewt'])
				$supp_trans->ewt_percent = $ewt_p = get_company_pref('ewt_percent');
			else
				$supp_trans->ewt_percent = $ewt_p = 0;
			
			if ($supp_trans->purch_discount > 0)
			{
				if(!$_POST['ret_disc'])
				label_row("Purchase Discount:",number_format2(abs($supp_trans->purch_discount),2),"colspan=$colspan 
					align=right", "align=right");
				else
				label_row("Purchase Returns:",number_format2(abs($supp_trans->purch_discount),2),"colspan=$colspan 
					align=right", "align=right");
			}
			
			if ($ewt_p > 0)
			{
				$supp_trans->ewt = round2(($supp_trans->purch_non_vat + $supp_trans->purch_vat) * ($ewt_p/100),2);
				label_row(_("less $ewt_p% EWT (<i>upon payment</i>):"), '('.number_format2($supp_trans->ewt,2).')', "colspan=$colspan align=right", "align=right");
				
				if ($supp_trans->gl_amount != 0)
				{
					label_row(_("Items Net:"), number_format2($supp_trans->ov_amount - $supp_trans->ewt,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
					label_row(_("GL Total:"), number_format2($supp_trans->gl_amount,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
				}
			}
			else
				$supp_trans->ewt = 0;
			if ($ewt_p > 0 OR $supp_trans->purch_discount > 0)
			label_row(_("NET:"), number_format2($supp_trans->ov_amount - $supp_trans->ewt + $supp_trans->gl_amount,2), "colspan=$colspan align=right style='font-weight:bold;background-color:#FFAEB9'", "align=right style='font-weight:bold;background-color:#FFAEB9'");
					$supp_trans->acounts_payable = round2($supp_trans->ov_amount - $supp_trans->ewt + $supp_trans->gl_amount,2);
		}
		else
		{
			if (!$_POST['no_ewt'])
				$supp_trans->ewt_percent = $ewt_p = get_company_pref('ewt_percent');
			else
				$supp_trans->ewt_percent = $ewt_p = 0;

			if($supp_trans->ewt_percent > 0)
				$supp_trans->ewt = round2(($supp_trans->gl_amount) * ($supp_trans->ewt_percent/100),2);
				
			label_row(_("Invoice Total:"), number_format2($supp_trans->gl_amount,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
			
			if($supp_trans->ewt_percent > 0)
			{
				label_row(_("less ".$supp_trans->ewt_percent."% EWT:"), '('.number_format2($supp_trans->ewt,2).')', "colspan=$colspan align=right", "align=right");
				label_row(_("NET:"), number_format2($supp_trans->gl_amount-$supp_trans->ewt,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
			}
		}
		
		// display_error($supp_trans->purch_non_vat);
		// display_error($supp_trans->purch_vat);
		// display_error($supp_trans->vat);
		// display_error($supp_trans->purch_discount);
		// display_error($supp_trans->ewt);
		// display_error($supp_trans->acounts_payable);
    }
	else
		label_row(_("Credit Note Total"),
			$display_total, "colspan=$colspan align=right style='font-weight:bold;color:red;'", "nowrap align=right style='font-weight:bold;color:red;'");
    
	end_table(1);
    start_table($table_style2);
    textarea_row(_("Memo:"), "Comments", null, 50, 3);
    end_table(1);
}

//--------------------------------------------------------------------------------------------------
function display_gl_controls(&$supp_trans, $k)
{
	global $table_style, $table_style2;

	$accs = get_supplier_accounts($supp_trans->supplier_id);
	$_POST['gl_code'] = $accs['purchase_account'];

	alt_table_row_color($k);
	echo gl_all_accounts_list('gl_code', null, false, true);
	$dim = get_company_pref('use_dimension');
	if ($dim >= 1)
		dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
	if ($dim > 1)
		dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	amount_cells(null, 'amount');
	if ($dim < 1)	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null, hidden('dimension_id', 0, false).hidden('dimension2_id', 0, false));
	else if ($dim < 2)	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null, hidden('dimension2_id', 0, false));
	else	
		text_cells_ex(null, 'memo_', 35, 50, null, null, null);
	submit_cells('AddGLCodeToTrans', _("Add"), "",
		    _('Add GL Line'), true);
	submit_cells('ClearFields', _("Reset"), "",
		    _("Clear all GL entry fields"), true);
	end_row();	
}

// $mode = 0 none at the moment
//		 = 1 display on invoice/credit page
//		 = 2 display on view invoice
//		 = 3 display on view credit

function display_gl_items(&$supp_trans, $mode=0)
{
	global $table_style, $table_style2, $path_to_root, $Ajax;

    // if displaying in form, and no items, exit
    if (($mode == 2 || $mode == 3) && count($supp_trans->gl_codes) == 0)
    	return 0;

	if ($supp_trans->is_invoice)
		$heading = _("GL Items for this Invoice");
	else
		$heading = _("GL Items for this Credit Note");

	start_outer_table("$table_style width=95%");

	if ($mode == 1)
	{
		$qes = has_quick_entries(QE_SUPPINV);
		if ($qes !== false)
		{
			echo "<div style='float:right;'>";
			echo _("Quick Entry:")."&nbsp;"; 
			echo quick_entries_list('qid', null, QE_SUPPINV, true);
			$qid = get_quick_entry(get_post('qid'));
			if (list_updated('qid')) {
				unset($_POST['totamount']); // enable default
				$Ajax->activate('totamount');
			}
			echo "&nbsp;".$qid['base_desc'].":"."&nbsp;";

			$amount = input_num('totamount', $qid['base_amount']);
			$dec = user_price_dec();
			echo "<input class='amount' type='text' name='totamount' size='7' maxlength='12' dec='$dec' value='$amount'>&nbsp;";
			submit('go', _("Go"), true, false, true);
			echo "</div>";

		}	
	}
	display_heading($heading);

	end_outer_table(0, false);

	div_start('gl_items');
	start_table("$table_style width=95%");

	$dim = get_company_pref('use_dimension');
	if ($dim == 2)
    	$th = array(_("Account"), _("Name"), _("Dimension")." 1", _("Dimension")." 2", _("Amount"), _("Memo"));
	else if ($dim == 1)
    	$th = array(_("Account"), _("Name"), _("Dimension"), _("Amount"), _("Memo"));
    else
    	$th = array(_("Account"), _("Name"), _("Amount"), _("Memo"));

	if ($mode == 1)
	{
		$th[] = "";
		$th[] = "";
	}	
	table_header($th);
	$total_gl_value=0;
	$i = $k = 0;

	if (count($supp_trans->gl_codes) > 0)
	{

		foreach ($supp_trans->gl_codes as $entered_gl_code)
		{

			alt_table_row_color($k);
			
			if ($mode == 3)
				$entered_gl_code->amount = -$entered_gl_code->amount;

			label_cell($entered_gl_code->gl_code);
			label_cell($entered_gl_code->gl_act_name);

			if ($dim >= 1)
   				label_cell(get_dimension_string($entered_gl_code->gl_dim, true));
			if ($dim > 1)
   				label_cell(get_dimension_string($entered_gl_code->gl_dim2, true));

			amount_cell($entered_gl_code->amount, true);
			label_cell($entered_gl_code->memo_);

			if ($mode == 1)
			{
				delete_button_cell("Delete2" . $entered_gl_code->Counter, _("Delete"),
					  _('Remove line from document'));
				label_cell("");
			}	
			end_row();
			/////////// 2009-08-18 Joe Hunt
			// if ($mode > 1 && !is_tax_account($entered_gl_code->gl_code))
				// $total_gl_value += $entered_gl_code->amount;
			// else	
				$total_gl_value += $entered_gl_code->amount;
			$i++;
			if ($i > 15)
			{
				$i = 0;
				table_header($th);
			}
		}

	}
	if ($mode == 1)
		display_gl_controls($supp_trans, $k);
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	label_row(_("Total"), price_format($total_gl_value),	"colspan=".$colspan." align=right", "nowrap align=right", ($mode==1?3:1));

	end_table(1);
	div_end();

	return $total_gl_value;
}

//--------------//-----------------------------------------------------------------------------------------

function display_grn_items_for_selection(&$supp_trans, $k, $auto_add=false)
{
	global $table_style, $Ajax;

	if ($supp_trans->is_invoice)
	{
		$result = get_grn_items(0, $supp_trans->supplier_id, true,false, 0, "", "", $_POST['choose_inv'],$_POST['supp_reference'],0, true);
	}
	else
	{
		if (isset($_POST['receive_begin']) && isset($_POST['receive_end']))
			$result = get_grn_items(0, $supp_trans->supplier_id, false, true, 0, $_POST['receive_begin'], $_POST['receive_end']);
		else if (isset($_POST['invoice_no']))
			$result = get_grn_items(0, $supp_trans->supplier_id, false, true, $_POST['invoice_no']);
		else	
			$result = get_grn_items(0, $supp_trans->supplier_id, false, true);
	}
    if (db_num_rows($result) == 0)
    {
    	return false;
    }
    
    /*Set up a table to show the outstanding GRN items for selection */
	$supp_trans->item_count = 0;
    while ($myrow = db_fetch($result))
    {
		$grn_already_on_invoice = false;

		$supp_trans->item_count ++;
    	foreach ($supp_trans->grn_items as $entered_grn)
    	{
    		if ($entered_grn->id == $myrow["id"])
    		{
    			$grn_already_on_invoice = true;
    		}
    	}		
		
    	if ($grn_already_on_invoice == false)
    	{
			$n = $myrow["id"];
            $dec = get_qty_dec($myrow["item_code"]);
		
			$dec2 = 4; 		
			$chgprce_ = $myrow["price_per_pc"]*$myrow["multiplier"];
			$ppp = get_resolved_item_price($n);
		   
		   if ($ppp !== false)
			$chgprce_ = $ppp;
			 
			 
			if ($auto_add)
			{
				// update_inv_item_details($myrow['item_code']);
				$_SESSION['supp_trans']->add_grn_to_trans($n, $myrow['po_detail_item'],
						$myrow['item_code'], $myrow['description'], $myrow['qty_recd'],
						$myrow['prev_quantity_inv'], round2($myrow["qty_recd"] - $myrow["quantity_inv"], $dec2),
						$myrow['real_price'], round2($chgprce_, $dec2), 1,
						$myrow['std_cost_unit'], "",$myrow['r_uom'],$myrow['multiplier'], 
						array('source_invoice_no' => $myrow['source_invoice_no'],
								'purch_order_no' => $myrow['purch_order_no'],
								'delivery_date' => $myrow['delivery_date'])
					);
				continue;
			}
			 
    		$item_det=get_item($myrow['item_code']);
			alt_table_row_color($k);

    		label_cell(get_trans_view_str(25, $myrow["grn_batch_id"],$myrow["reference"]));
        	label_cell($myrow['source_invoice_no'].
            	hidden('source_invoice_no'.$n, $myrow["source_invoice_no"], false).
            	hidden('qty_recd'.$n, $myrow["qty_recd"], false).
            	hidden('purch_order_no'.$n, $myrow["purch_order_no"], false).
            	hidden('item_code'.$n, $myrow["item_code"], false).
            	hidden('item_description'.$n, $myrow["description"], false).
            	hidden('prev_quantity_inv'.$n, $myrow['quantity_inv'], false).
            	hidden('order_price'.$n, $myrow['real_price'], false).
            	hidden('std_cost_unit'.$n, $myrow['std_cost_unit'], false).
            	hidden('r_uom'.$n, $myrow['r_uom'], false).
            	hidden('delivery_date'.$n, $myrow['delivery_date'], false).
            	hidden('multiplier'.$n, $myrow['multiplier'], false).
            	hidden('po_detail_item'.$n, $myrow['po_detail_item'], false));
        	// label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["purch_order_no"]));
			label_cell(viewer_link(get_reference(ST_PURCHORDER, $myrow['purch_order_no']), "purchasing/view/srs_view_po.php?trans_no=".$myrow['purch_order_no']));
            // label_cell($myrow["item_code"]);
            label_cell($myrow["description"]);
            label_cell($myrow["r_uom"]);
            label_cell(sql2date($myrow["delivery_date"]));
            qty_cell($myrow["qty_recd"], false, $dec);
            qty_cell($myrow["quantity_inv"], false, $dec);
            if ($supp_trans->is_invoice)
            	qty_cells(null, 'this_quantity_inv'.$n, number_format2($myrow["qty_recd"] - $myrow["quantity_inv"], $dec), 
            		null, null, $dec);
            else		
            	qty_cells(null, 'This_QuantityCredited'.$n, number_format2(max($myrow["quantity_inv"], 0), $dec), 
            		null, null, $dec);
		   
			amount_cells(null, 'ChgPrice'.$n, price_decimal_format($chgprce_, $dec2), null, null, 4);
			
			label_cell(get_discount_str($myrow['po_detail_item']));
			
            if ($supp_trans->is_invoice)
            	amount_cell(round2($myrow["extended"], 4));
            else	
            	amount_cell(round2($myrow["unit_price"] * max($myrow['quantity_inv'], 0), 4));
				
            if ($supp_trans->is_invoice)	
        		submit_cells('grn_item_id'.$n, _("Add"), '', _("Add to Invoice"), true);
        	else	
        		submit_cells('grn_item_id'.$n, _("Add"), '', _("Add to Credit Note"), true);
				
    		// if ($supp_trans->is_invoice && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE')) {	// Added 2008-10-18 by Joe Hunt. Special access rights needed.
        		// submit_cells('void_item_id'.$n, _("Remove"), '', _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!"), true);
				// submit_js_confirm('void_item_id'.$n,
					// sprintf(_('You are about to remove all yet non-invoiced items from delivery line #%d. This operation also irreversibly changes related order line. Do you want to continue ?'), $n));
			// }
			
			end_row();
			
    	}
    }
	
	
	return true;
}

//------------------------------------------------------------------------------------

// $mode = 0 none at the moment
//		 = 1 display on invoice/credit page
//		 = 2 display on view invoice
//		 = 3 display on view credit

function display_grn_items(&$supp_trans, $mode=0)
{
	global $table_style, $path_to_root;

    $ret = true;
    // if displaying in form, and no items, exit
    if (($mode == 2  || $mode == 3) && count($supp_trans->grn_items) == 0)
    	return 0;

		
	// ================ for quick processing and discrepancy report =============== //
	div_start('proc_disc');	
	
	
	if ($_POST['choose_inv'] != '')
	{
		start_table("$table_style width=95%");
		if (grn_is_locked($_POST['choose_inv']))
		{
			display_error("Waiting for Purchasing to fix the prices");
			
			$fth = array('Waiting for Purchasing to fix the prices');
			table_header($fth);
			// display_footer_exit();
		}
		else
		{
			$drow = get_grn_discrepancy_header($_POST['choose_inv']);
			
			if (!$drow)
			{
				hidden('drow',0);
				$fth = array('Invoice Total (using PO price)','Process APV','Add to Discrepancy Report');
				table_header($fth);
				$grn_total = get_grn_items_total(0, $supp_trans->supplier_id, true,false, 0, "", "", $_POST['choose_inv'],$_POST['supp_reference'], true);
				
				//============== off invoice
				$ret_array = get_off_invoice_discount($supp_trans->supplier_id, $grn_total);
				$ov_discount = $ret_array[0];
				$off_inv_disc = $ret_array[1];
				
				start_row();
					label_cell('<b>'.number_format2($grn_total,2).'</b>'.
						(count($off_inv_disc) > 0 ?  '<br><hr> Off Invoice discount :'.
							implode(', ',$off_inv_disc) . '<br>Payable : '.number_format2($grn_total-$ov_discount,2):''), 'align=center');
					hidden('total_po_price', round2($grn_total,2));

					// label_cell('<b>'.number_format2($supp_trans->ov_amount).'</b>', 'align=right', 'act_inv_tot');
					// amount_cells('','actual_inv_total');
					
					submit_cells('process_apv', 'Process APV','align=center');
					
					echo '<td>';
					start_table();
					start_row();
						amount_cells('Actual Invoice Amount: ','actual_inv_total');
						submit_cells('submit_to_discrepancy', 'Add to Discrepancy Report','align=center');
					end_row();
					start_row();
					label_cell('<font color=red>* use the memo field at the end of the page for notes </font>','colspan=3');
					end_row();
					end_table();
					echo '</td>';
				end_row();
			}
			else
			{
				hidden('drow',1);
				$fth = array('Invoice Total (using PO price)','Invoice Total after Discrepancy Report', 'Actual Invoice Total','Process APV','Add to Discrepancy Report');
				table_header($fth);
				$grn_total = get_grn_items_total(0, $supp_trans->supplier_id, true,false, 0, "", "", $_POST['choose_inv'],$_POST['supp_reference'], true);
				start_row();
					label_cell('<b>'.number_format2($grn_total,2).'</b>', 'align=center');
					// hidden('total_po_price', round2($grn_total,2));
					label_cell('<b>'.number_format2($drow['actual_invoice_total'],2).'</b>', 'align=center');
					hidden('total_po_price', round2($drow['actual_invoice_total'],2));
					amount_cells('','actual_inv_total');
					submit_cells('process_apv', 'Process APV','align=center');
					submit_cells('submit_to_discrepancy', 'Add to Discrepancy Report','align=center');
					start_row();
					label_cell('<font color=red>* use the memo field at the end of the page for notes </font>','colspan=5 align=right');
					end_row();
				end_row();
			}
		}
		end_table(2);
	}
	div_end();
	//============================================================
	
	start_outer_table("style='border:1px solid #cccccc;' width=95%");

	$heading2 = "";
	if ($mode == 1)
	{
		if ($supp_trans->is_invoice)
		{
			$heading = _("Items Received Yet to be Invoiced");
    		// if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
				// $heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
			
			$heading2 = _("Items highlighted in pink are set as NON-VATABLE");
		}
		else
			$heading = _("Delivery Item Selected For Adding To A Purchase Returns");
	}
	else
	{
		if ($supp_trans->is_invoice)
			$heading = _("Received Items Charged on this Invoice");
		else
			$heading = _("Received Items Credited on this Note");
	}		
			
	display_heading($heading);

	if ($mode == 1)
	{
		if (!$supp_trans->is_invoice && !isset($_POST['invoice_no']))
		{
			echo "</td>";
			date_cells(_("Received between"), 'receive_begin', "", null, -30, 0, 0, "valign=middle");
			date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "valign=middle");
			submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), true);
			echo "<td>";
		}	
			
		if ($heading2 != "")
		{
			display_note($heading2, 0, 0, "style=background-color:#FFAEB9");
		}	
		
		echo "</td><td width=10% align='right'>";
		submit('InvGRNAll', _("Add All Items"), true, false,true);
	}	

	end_outer_table(0, false);

  	div_start('grn_items');
	start_table("$table_style width=95%");
	if ($mode == 1)
	{
    	$th = array(_("Delivery"), _("Reference Invoice #"), _("P.O.") , _("Description"),"Unit",
    		_("Received On"), _("Quantity Received"), _("Quantity Invoiced"),
    		_("Qty Yet To Invoice"), _("Order Price"), _("Discount/s"), _("Total"), "");
    	if ($supp_trans->is_invoice && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
    		$th[] = "";
		if (!$supp_trans->is_invoice)
		{
			$th[9] = _("Qty Yet To Credit");
		}
    }
    else
		$th = array(_("Delivery"), _("Item"), "Unit",
			_("Quantity"), _("Price"), 'Discount/s', _("Amount"));

	table_header($th);
    $total_grn_value = 0;
    $i = $k = 0;

	if ((count($supp_trans->grn_items) == 0 AND list_updated('choose_inv') AND $_POST['choose_inv'] != '')
		OR ($_POST['choose_inv'] != '' AND isset($_POST['reload_'])))
	{
		display_grn_items_for_selection($supp_trans, $k, true);
		copy_to_trans($supp_trans);
	}
	
	if (count($supp_trans->grn_items) > 0)
	{

    	foreach ($supp_trans->grn_items as $entered_grn)
    	{
    		$item_det=get_item($entered_grn->item_code);
    	//	var_dump($item_det);
		
			
			if ($item_det['tax_type_id'] == 1) // VATABLE
				alt_table_row_color($k);
			else
				echo '<tr bgcolor="#FFAEB9">';

			$grn_batch = get_grn_batch(get_grn_batch_from_item($entered_grn->id));
    		label_cell(get_trans_view_str(ST_SUPPRECEIVE,$grn_batch['id'],$grn_batch['reference']));
    		if ($mode == 1)
    		{
				label_cell($entered_grn->row['source_invoice_no']);
				// label_cell(get_trans_view_str(ST_PURCHORDER, $entered_grn->row['purch_order_no']));
				label_cell(viewer_link(get_reference(ST_PURCHORDER, $entered_grn->row['purch_order_no']), "purchasing/view/srs_view_po.php?trans_no=".$entered_grn->row['purch_order_no']));
			}	
			// label_cell($entered_grn->item_code);
			label_cell($entered_grn->item_description);
			label_cell($entered_grn->r_uom);
            // $dec = get_qty_dec($entered_grn->item_code);
            $dec = 0;
            if ($mode == 1)
            {
				label_cell(sql2date($entered_grn->row['delivery_date']));
				qty_cell($entered_grn->qty_recd, false, $dec);
				$total_qty +=$entered_grn->qty_recd;
				qty_cell($entered_grn->prev_quantity_inv, false, $dec);
            }
			else
				$total_qty += $entered_grn->this_quantity_inv;
			qty_cell(abs($entered_grn->this_quantity_inv), true, $dec);
		//	amount_decimal_cell($entered_grn->chg_price);
			label_cell('<b>'.number_format2($entered_grn->chg_price,2).'</b>', 'align=right');
			label_cell(get_discount_str($entered_grn->po_detail_item));
			
			label_cell('<b>' . number_format2($entered_grn->chg_price * ($entered_grn->this_quantity_inv), 2) .'</b>', 'align=right');
			
			if ($mode == 1)
			{
				delete_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
				if ($supp_trans->is_invoice && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	  
					label_cell("");
			}	
			end_row();

    		$total_grn_value += round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv), 4);

    		$i++;
    		if ($i > 15)
    		{
    		 	$i = 0;
    		 	table_header($th);
    		}
    	}
    }
	if ($mode == 1)
	{
		$ret = display_grn_items_for_selection($supp_trans, $k);
    	$colspan_ = 6;
    	$colspan = 10+1 - $colspan_;
	}
	else
	{
		$colspan_ = 3 ;
		$colspan = 2+1;
	}
	start_row();	
	label_cells(_("Total QTY"), number_format2($total_qty,0),"colspan= $colspan_ align=right", "nowrap align=right");
	label_cells(_("Total"), number_format2($total_grn_value,2),"colspan=".($colspan-1)." align=right", "nowrap align=right");
	end_row();
	if (!$ret)
	{
		start_row();
		echo "<td colspan=".($colspan + 1).">";
		if ($supp_trans->is_invoice)
			display_note(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
		else
		{
			display_note(_("There are no received items for the selected supplier that have been invoiced."));
			display_note(_("Credits can only be applied to invoiced items."), 0, 0);
		}
		echo "</td>";
		end_row();
	}	
    end_table(1);
    div_end();

	return $total_grn_value;
}

//--------------------------------------------------------------------------------------------------
function get_duedate_from_terms(&$supp_trans)
{
	if (!is_date($supp_trans->tran_date))
	{
		$supp_trans->tran_date = Today();
	}
	
	if (substr( $supp_trans->terms, 0, 1) == "1")
	{ /*Its a day in the following month when due */
		$supp_trans->due_date = add_days(end_month($supp_trans->tran_date), (int) substr( $supp_trans->terms,1));
	}
	else 
	{ /*Use the Days Before Due to add to the invoice date */
		$supp_trans->due_date = add_days($supp_trans->tran_date, (int) substr( $supp_trans->terms,1));
	}
}

//--------------------------------------------------------------------------------------------------

?>