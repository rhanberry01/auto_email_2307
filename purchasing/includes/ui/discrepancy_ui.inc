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
	$_POST['Comments'] = $supp_trans->note;
	$_POST['tran_date'] = $supp_trans->tran_date;
	$_POST['due_date'] = $supp_trans->due_date;
	$_POST['supp_reference'] = $supp_trans->supp_reference;
	$_POST['reference'] = $supp_trans->reference;
	$_POST['supplier_id'] = $supp_trans->supplier_id;
	$_POST['del_date'] = $supp_trans->del_date;
	$_POST['discrepancy_id'] = $supp_trans->discrepancy_id;
	$_POST['choose_inv'] = $supp_trans->choose_inv;
	$_POST['note'] = $supp_trans->note;
}

//--------------------------------------------------------------------------------------------------

function copy_to_trans(&$supp_trans)
{
	$supp_trans->note = $_POST['Comments'];
	$supp_trans->tran_date = $_POST['tran_date'];
	$supp_trans->due_date = $_POST['due_date'];
	$supp_trans->supp_reference = $_POST['supp_reference'];
	$supp_trans->reference = $_POST['reference'];
	$supp_trans->del_date = $_POST['del_date'];
	$supp_trans->discrepancy_id = $_POST['discrepancy_id'];
	$supp_trans->choose_inv = $_POST['choose_inv'];
	$supp_trans->note = $_POST['note'];
	

	$supp_trans->ewt_percent = input_num('apv_ewt_percent');
	
	$supp_trans->ov_amount = $supp_trans->gl_amount = $supp_trans->ov_nv = $supp_trans->ov_discount = 0;/* for starters */
	if (count($supp_trans->grn_items) > 0)
	{
		foreach ( $supp_trans->grn_items as $grn)
		{
			$supp_trans->ov_amount += round2(($grn->this_quantity_inv * $grn->chg_price),
			  4);
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


	
	label_row(_("Supplier:"), $supp_trans->supplier_name.hidden('supplier_id', $_POST['supplier_id'], false));
	label_row(_("Supplier's Invoice #:"), $supp_trans->supp_reference.hidden('supp_reference', $_POST['supp_reference'], false));
	label_row('Expected Total:',number_format2($supp_trans->expected_total,2));
	if ($supp_trans->note != '')
		label_row('<b>Notes:<b>',html_entity_decode($supp_trans->note));
	
	table_section(2, "33%");

	label_row(_("Delivery Date:"),  $supp_trans->del_date.hidden('del_date', $_POST['del_date'], false));
	label_row(_("Due Date:"),  $supp_trans->due_date.hidden('due_date', $_POST['due_date'], false));
   	// date_row(_("Delivery Date") . ":", 'del_date', '', true, 0, 0, 0, "", true);
	
	
	// if (isset($_POST['_del_date_changed'])) 
	// {
		// $Ajax->activate('_ex_rate');
		// $supp_trans->tran_date = $_POST['del_date'];
		// get_duedate_from_terms($supp_trans);
		// $_POST['due_date'] = $supp_trans->due_date;
		// $Ajax->activate('due_date');
	// }


    label_row(_("Terms:"), $supp_trans->terms_description);
	
	start_row();
	// percent_cells("<b>EWT % for this APV :</b>",'apv_ewt_percent');
	// submit_cells('refresh_totals', 'Update Totals', "", false, true);
	end_row();

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
	
   	start_table("$table_style width=95%");
	
	if (!$supp_trans->nt)
	{
		$supp_trans->ov_nv = $supp_trans->get_non_vat_item_total();
		
		label_row(_("Purchase NON-VAT:"), number_format2($supp_trans->ov_nv,2), "colspan=$colspan align=right", "align=right");
		label_row(_("Purchase VAT:"), number_format2(abs($supp_trans->ov_amount - $_tax_total - $supp_trans->ov_nv),2), "colspan=$colspan align=right", "align=right");
		// label_row(_("Sub-total:"), price_format( $supp_trans->ov_amount - $_tax_total), "colspan=$colspan align=right", "align=right");
			
		$taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
		$tax_total = display_edit_tax_items($taxes, $colspan, $vat_inc);

		$display_total = number_format2($supp_trans->ov_amount,2);
	}
	if ($supp_trans->is_invoice)
	{
		if (!$supp_trans->nt)
		{
			label_row(_("Invoice Total:"), $display_total, "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
			$supp_trans->ewt_percent = $ewt_p = 0;//get_company_pref('ewt_percent');
			// if ($ewt_p > 0)
			// {
				// $supp_trans->ewt = ($supp_trans->ov_amount - $_tax_total) * ($ewt_p/100);
				// label_row(_("less $ewt_p% EWT:"), '('.number_format2($supp_trans->ewt,2).')', "colspan=$colspan align=right", "align=right");
				
				// if ($supp_trans->gl_amount != 0)
				// {
					// label_row(_("Items Net:"), number_format2($supp_trans->ov_amount - $supp_trans->ewt,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
					// label_row(_("GL Total:"), number_format2($supp_trans->gl_amount,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
				// }
				
				// label_row(_("NET:"), number_format2($supp_trans->ov_amount - $supp_trans->ewt + $supp_trans->gl_amount,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
			// }
		}
		else
		{
			if($supp_trans->ewt_percent > 0)
				$supp_trans->ewt = ($supp_trans->gl_amount) * ($supp_trans->ewt_percent/100);
				
			label_row(_("Invoice Total:"), number_format2($supp_trans->gl_amount,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
			
			if($supp_trans->ewt_percent > 0)
			{
				label_row(_("less ".$supp_trans->ewt_percent."% EWT:"), '('.number_format2($supp_trans->ewt,2).')', "colspan=$colspan align=right", "align=right");
				label_row(_("NET:"), number_format2($supp_trans->gl_amount-$supp_trans->ewt,2), "colspan=$colspan align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
			}
		}
    }
	else
		label_row(_("Credit Note Total"),
			$display_total, "colspan=$colspan align=right style='font-weight:bold;color:red;'", "nowrap align=right style='font-weight:bold;color:red;'");
    
	end_table(1);
    start_table($table_style2);
    textarea_row(_("Additional Notes:"), "Comments", null, 50, 3);
    end_table(1);
}

//--------------------------------------------------------------------------------------------------
function display_gl_controls(&$supp_trans, $k)
{
	global $table_style, $table_style2;

	$accs = get_supplier_accounts($supp_trans->supplier_id);
	$_POST['gl_code'] = $accs['purchase_account'];

	alt_table_row_color($k);
	echo gl_all_accounts_list('gl_code', null, true, true);
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
			$dec = 4;
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
	label_row(_("Total"), price_format($total_gl_value),
		"colspan=".$colspan." align=right", "nowrap align=right", ($mode==1?3:1));

	end_table(1);
	div_end();

	return $total_gl_value;
}

//--------------//-----------------------------------------------------------------------------------------

function display_grn_items_for_selection(&$supp_trans, $k)
{
	global $table_style, $Ajax;;

	if ($supp_trans->is_invoice)
	{
		$result = get_grn_items(0, $supp_trans->supplier_id, true,false, 0, "", "", $supp_trans->choose_inv,$supp_trans->supp_reference,1);
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
	$supp_trans->qty_total = 0;
    while ($myrow = db_fetch($result))
    {
		$grn_already_on_invoice = false;
		$supp_trans->qty_total += $myrow['qty_recd'];
			
    	foreach ($supp_trans->grn_items as $entered_grn)
    	{
    		if ($entered_grn->id == $myrow["id"])
    		{
    			$grn_already_on_invoice = true;
    		}
    	}
    	if ($grn_already_on_invoice == false)
    	{
    		$item_det=get_item($myrow['item_code']);
			alt_table_row_color($k);

			$n = $myrow["id"];
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
        	label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["purch_order_no"]));
            // label_cell($myrow["item_code"]);
            label_cell($myrow["description"]);
            label_cell($myrow["r_uom"]);
            label_cell(sql2date($myrow["delivery_date"]));
            $dec = get_qty_dec($myrow["item_code"]);
            // qty_cell($myrow["qty_recd"], false, $dec);
            // qty_cell($myrow["quantity_inv"], false, $dec);
			label_cell(hidden('this_quantity_inv'.$n,$myrow["qty_recd"] - $myrow["quantity_inv"])
				.$myrow["qty_recd"] - $myrow["quantity_inv"],'align=right');
				
            $dec2 = 4; 	
			
			$price__ = round2($myrow["price_per_pc"]*$myrow["multiplier"], $dec2);
			
			$price__2 = get_discrepancy_saved_price($supp_trans->discrepancy_id, $n);
			
			if ($price__2 != 0)
				$price__ = $price__2;
			
            amount_cells(null, 'ChgPrice'.$n, number_format2($price__,4) , null, null, 4);
			
			
			label_cell(get_discount_str($myrow['po_detail_item']));
			
            // if ($supp_trans->is_invoice)
            	// amount_cell(round2($myrow["extended"], 4));
            // else	
            	amount_cell(round2($price__ * $myrow["qty_recd"], 4));
				
        	submit_cells('grn_item_id'.$n, _("Confirm"), '', _("Add to Invoice"), true);

    		if ($supp_trans->is_invoice && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE')) {	// Added 2008-10-18 by Joe Hunt. Special access rights needed.
        		// submit_cells('void_item_id'.$n, _("Remove"), '', _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!"), true);
				submit_js_confirm('void_item_id'.$n,
					sprintf(_('You are about to remove all yet non-invoiced items from delivery line #%d. This operation also irreversibly changes related order line. Do you want to continue ?'), $n));
			}
			
			end_row();
    	}
    }
    return true;
}

function line_confirmed($myrow)
{
	$sql = "SELECT * FROM ".TB_PREF."discrepancy_details 
			WHERE grn_item_id = ". $myrow['id'].'
			AND discrepancy_id = '.$_SESSION['supp_trans']->discrepancy_id.'
			AND confirmed=1';
	$res = db_query($sql);
	
	if(db_num_rows($res) > 0)
	{	
		$row = db_fetch($res);
		// if (input_num('this_quantity_inv'.$n) >= ($_POST['qty_recd'.$n] - $_POST['prev_quantity_inv'.$n]))
    	// {
    		$complete = true;
    	// }
    	// else
    	// {
    		// $complete = false;
    	// }

		$_SESSION['supp_trans']->add_grn_to_trans($myrow['id'], $myrow['po_detail_item'],
			$myrow["item_code"], $myrow["description"], $myrow["qty_recd"],
			$myrow['quantity_inv'], $myrow["qty_recd"] - $myrow["quantity_inv"],
			$myrow['real_price'], $row['new_price'], $complete,
			$myrow['std_cost_unit'], "",$myrow['r_uom'],$myrow['multiplier'], 
			array('source_invoice_no' => $myrow["source_invoice_no"],
					'purch_order_no' => $myrow["purch_order_no"],
					'delivery_date' => $myrow['delivery_date'],
					'manual_confirmation' => true)
		);
	}
}

//------------------------------------------------------------------------------------

// $mode = 0 none at the moment
//		 = 1 display on invoice/credit page
//		 = 2 display on view invoice
//		 = 3 display on view credit

function display_grn_items(&$supp_trans, $mode=0)
{
	global $table_style, $path_to_root;

	if (isset($_GET['discrepancy_id']))
	{
		$sql = "SELECT * FROM ".TB_PREF."discrepancy_details 
				WHERE discrepancy_id = ". $_GET['discrepancy_id'].'
				AND confirmed=1';
		$res = db_query($sql);
		
		if(db_num_rows($res) > 0)
		{
			if ($supp_trans->is_invoice)
			{
				$result = get_grn_items(0, $supp_trans->supplier_id, true,false, 0, "", "", $supp_trans->choose_inv,$supp_trans->supp_reference,1);
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
			if (db_num_rows($result) > 0)
			{
				while($myrow = db_fetch($result))
				line_confirmed($myrow);
			}
		}
	}
	
    $ret = true;
    // if displaying in form, and no items, exit
    if (($mode == 2  || $mode == 3) && count($supp_trans->grn_items) == 0)
    	return 0;
		
	start_outer_table("style='border:1px solid #cccccc;' width=95%");

	$heading2 = "";
	if ($mode == 1)
	{
		if ($supp_trans->is_invoice)
		{
			$heading = _("Items Received Yet to be Invoiced");
    		if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	// Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
				$heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
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
			display_note($heading2, 0, 0, "class='overduefg'");
		}	
		echo "</td><td width=10% align='right'>";
		// submit('InvGRNAll', _("Add All Items"), true, false,true);
	}	

	end_outer_table(0, false);

  	div_start('grn_items');
	start_table("$table_style width=95%");
	if ($mode == 1)
	{
    	$th = array('Line #',_("Invoice Number"),  _("P.O.") , _("Description"),"Unit",
    		_("Received On"), _("Quantity Received"),  _("Order Price"), _("Discount/s"), _("Total"));
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
	if (count($supp_trans->grn_items) > 0)
	{
    	foreach ($supp_trans->grn_items as $entered_grn)
    	{
    		$item_det=get_item($entered_grn->item_code);
    	//	var_dump($item_det);
    		alt_table_row_color($k);

			$grn_batch = get_grn_batch(get_grn_batch_from_item($entered_grn->id));
    		label_cell(get_trans_view_str(ST_SUPPRECEIVE,$grn_batch['id'],$grn_batch['reference']));
    		if ($mode == 1)
    		{
				label_cell($entered_grn->row['source_invoice_no']);
				label_cell(get_trans_view_str(ST_PURCHORDER, $entered_grn->row['purch_order_no']));
			}	
			// label_cell($entered_grn->item_code);
			label_cell($entered_grn->item_description);
			label_cell($entered_grn->r_uom);
            $dec = get_qty_dec($entered_grn->item_code);
            if ($mode == 1)
            {
				label_cell(sql2date($entered_grn->row['delivery_date']));
				// qty_cell($entered_grn->qty_recd, false, $dec);
				// qty_cell($entered_grn->prev_quantity_inv, false, $dec);
            }
			// qty_cell(abs($entered_grn->this_quantity_inv), true, $dec);
			label_cell('<b>'.abs($entered_grn->this_quantity_inv).'</b>', 'align=right');
		//	amount_decimal_cell($entered_grn->chg_price);
							
			label_cell('<b>'.number_format2($entered_grn->chg_price,4).'</b>', 'align=right');
			label_cell(get_discount_str($entered_grn->po_detail_item));
			amount_cell( round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv), 4), true);
			
			if ($mode == 1)
			{
				edit_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
				// if ($supp_trans->is_invoice && $_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))	  
					// label_cell("");
			}	
			
			// label_cell($entered_grn->row['manual_confirmation']);
			end_row();

    		$total_grn_value += round2($entered_grn->chg_price * abs($entered_grn->this_quantity_inv),
			   4);

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
    	$colspan = 10+1;
	}
	else
		$colspan = 6;
	// label_row(_("Total"), price_format($total_grn_value),"colspan=$colspan align=right", "nowrap align=right");
	
	label_cell("TOTAL QTY : ", 'colspan=6 align=right');
	label_cell($supp_trans->qty_total, 'align=right');
	label_cell('TOTAL : ', 'colspan=2 align=right');
	label_cell(price_format($total_grn_value), 'align=right');
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
		$supp_trans->due_date = add_days(end_month($supp_trans->del_date), (int) substr( $supp_trans->terms,1));
	}
	else 
	{ /*Use the Days Before Due to add to the invoice date */
		$supp_trans->due_date = add_days($supp_trans->del_date, (int) substr( $supp_trans->terms,1));
	}
	
}

//--------------------------------------------------------------------------------------------------

?>