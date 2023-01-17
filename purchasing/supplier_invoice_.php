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
$page_security = 'SA_SUPPLIERINVOICE';
$path_to_root = "..";

set_time_limit(0);
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/session.inc");


include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Enter AP Voucher"), false, false, "", $js);

//----------------------------------------------------------------------------------------

check_db_has_suppliers(_("There are no suppliers defined in the system."));

//---------------------------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$invoice_no = $_GET['AddedID'];
	$trans_type = ST_SUPPINVOICE;


    echo "<center>";
    display_notification_centered(_("AP Voucher has been processed."));
    display_note(get_trans_view_str($trans_type, $invoice_no, _("View this APV")));

	display_note(get_gl_view_str($trans_type, $invoice_no, _("View the GL Journal Entries for this APV")), 1);

	// hyperlink_no_params("$path_to_root/purchasing/supplier_payment.php", _("Entry supplier &payment for this APV"));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another APV"), "New=1");

	hyperlink_params("$path_to_root/admin/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
	
/*	echo "
		<p>
		<center>
		<a href='../sales/customer_del_so.php?OrderNumber=$invoice_no&type=6&view=0&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Invoice</a>
		</center>
	";*/
	
	display_footer_exit();
}

if (isset($_GET['D_AddedID'])) 
{
	echo "<center>";
    
	display_notification_centered("Supplier Invoice # ".$_GET['D_AddedID'] .' has been added to discrepancy report');

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another APV"), "New=1");
		
	display_footer_exit();
}

//--------------------------------------------------------------------------------------------------

if (isset($_GET['New']))
{
	if (isset( $_SESSION['supp_trans']))
	{
		unset ($_SESSION['supp_trans']->grn_items);
		unset ($_SESSION['supp_trans']->gl_codes);
		unset ($_SESSION['supp_trans']);
	}

	//session_register("SuppInv");
	//session_register("supp_trans");
	$_SESSION['supp_trans'] = new supp_trans;
	$_SESSION['supp_trans']->is_invoice = true;
	
	if (isset($_GET['discrepancy_id']))
	{
		$_SESSION['supp_trans']->discrepancy_id= $_GET['discrepancy_id'];
	}
	
	if (isset($_GET['NT']))
		$_SESSION['supp_trans']->nt = true;
}

//--------------------------------------------------------------------------------------------------
function clear_fields()
{
	global $Ajax;
	
	unset($_POST['gl_code']);
	unset($_POST['dimension_id']);
	unset($_POST['dimension2_id']);
	unset($_POST['amount']);
	unset($_POST['memo_']);
	unset($_POST['AddGLCodeToTrans']);
	$Ajax->activate('gl_items');
	set_focus('gl_code');
}
//------------------------------------------------------------------------------------------------
//	GL postings are often entered in the same form to two accounts
//  so fileds are cleared only on user demand.
//
if (isset($_POST['ClearFields']))
{
	clear_fields();
}

if (isset($_POST['AddGLCodeToTrans'])){

	$Ajax->activate('gl_items');
	$input_error = false;

	$sql = "SELECT account_code, account_name FROM ".TB_PREF."chart_master WHERE account_code=".db_escape($_POST['gl_code']);
	$result = db_query($sql,"get account information");
	if (db_num_rows($result) == 0)
	{
		display_error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
		set_focus('gl_code');
		$input_error = true;
	}
	else
	{
		$myrow = db_fetch_row($result);
		$gl_act_name = $myrow[1];
		if (!check_num('amount'))
		{
			display_error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
			set_focus('amount');
			$input_error = true;
		}
		
		if (input_num('amount') == 0)
		{
			display_error(_("Amount should not be zero"));
			set_focus('amount');
			$input_error = true;
		}
	}

	if (!is_tax_gl_unique(get_post('gl_code'))) {
   		display_error(_("Cannot post to GL account used by more than one tax type."));
		set_focus('gl_code');
   		$input_error = true;
	}

	if ($input_error == false)
	{
		$_SESSION['supp_trans']->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name,
			$_POST['dimension_id'], $_POST['dimension2_id'], 
			input_num('amount'), $_POST['memo_']);
			
		$_POST['amount'] = $_POST['memo_'] = '';
		set_focus('gl_code');
	}
}

//------------------------------------------------------------------------------------------------

function check_supp_invoice()
{
	$sql = "SELECT * FROM ".TB_PREF."supp_trans
			WHERE supplier_id = ".$_SESSION['supp_trans']->supplier_id."
			AND type = 20
			AND ov_amount > 0
			AND supp_reference = ".db_escape($_SESSION['supp_trans']->supp_reference);
	$res = db_query($sql);
	
	return (db_num_rows($res) == 0);
}

function check_data()
{
	global $Refs;

	if (!$_SESSION['supp_trans']->is_valid_trans_to_post())
	{
		display_error(_("The invoice cannot be processed because the there are no items or values on the invoice.  Invoices are expected to have a charge."));
		return false;
	}

	if (!$Refs->is_valid($_SESSION['supp_trans']->reference)) 
	{
		display_error(_("You must enter an invoice reference."));
		set_focus('reference');
		return false;
	}

	if (!is_new_reference($_SESSION['supp_trans']->reference, ST_SUPPINVOICE)) 
	{
		display_error(_("The entered reference is already in use."));
		set_focus('reference');
		return false;
	}

	if ($_SESSION['supp_trans']->supp_reference == '') 
	{
		display_error(_("You must enter a supplier's invoice reference."));
		set_focus('supp_reference');
		return false;
	}
	
	if(!check_supp_invoice())
	{
		display_error('An APV has been made with the same Supplier\'s Invoice #');
		set_focus('supp_reference');
		return false;
	}

	if (!is_date( $_SESSION['supp_trans']->tran_date))
	{
		display_error(_("The invoice as entered cannot be processed because the invoice date is in an incorrect format."));
		set_focus('trans_date');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_SESSION['supp_trans']->tran_date)) 
	{
		display_error(_("The entered date is not in fiscal year."));
		set_focus('trans_date');
		return false;
	}
	if (!is_date( $_SESSION['supp_trans']->due_date))
	{
		display_error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
		set_focus('due_date');
		return false;
	}

	// $sql = "SELECT Count(*) FROM ".TB_PREF."supp_trans WHERE supplier_id="
		// .db_escape($_SESSION['supp_trans']->supplier_id) . " AND supp_reference=" 
		// .db_escape( $_POST['supp_reference']) 
		// . " AND ov_amount!=0"; // ignore voided invoice references

	// $result=db_query($sql,"The sql to check for the previous entry of the same invoice failed");

	// $myrow = db_fetch_row($result);
	// if ($myrow[0] == 1)
	// { 	/*Transaction reference already entered */
		// display_error(_("This invoice number has already been entered. It cannot be entered again." . " (" . $_POST['supp_reference'] . ")"));
		// return false;
	// }

	if($_SESSION['supp_trans']->get_total_charged()<=0)
	{
			display_error("The total invoice amount must be greater than zero.");
			return false;

	}

	return true;
}

//--------------------------------------------------------------------------------------------------

function handle_commit_invoice()
{
	display_error(var_dump($_SESSION['supp_trans']).'**');
	die();
	copy_to_trans($_SESSION['supp_trans']);

	if (!check_data())
		return;
	
	if (!$_SESSION['supp_trans']->nt)
	{
		if(round($_SESSION['supp_trans']->purch_non_vat + $_SESSION['supp_trans']->purch_vat + $_SESSION['supp_trans']->vat,2)
			== round($_SESSION['supp_trans']->acounts_payable + $_SESSION['supp_trans']->ewt,2))
			// display_error('no discount GL');
		{
			$_SESSION['supp_trans']->purch_discount = 0;
			$_SESSION['supp_trans']->purch_ret = 0;
		}
		
		if ($_POST['ret_disc'] == 1)
		{
			$_SESSION['supp_trans']->purch_ret=$_SESSION['supp_trans']->purch_discount;
			$_SESSION['supp_trans']->purch_discount = 0;
		}
	}
	// display_error('p non vat: '.$_SESSION['supp_trans']->purch_non_vat);
	// display_error('p vat: '.$_SESSION['supp_trans']->purch_vat);
	// display_error('vat: '.$_SESSION['supp_trans']->vat);
	// display_error('-----------------------------');
	// display_error('discount: '.$_SESSION['supp_trans']->purch_discount);
	// display_error('purch_ret: '.$_SESSION['supp_trans']->purch_ret);
	// display_error('disp_allow: '.$_SESSION['supp_trans']->disp_allow);
	// display_error('trade_promo: '.$_SESSION['supp_trans']->trade_promo);
	// display_error('rebate: '.$_SESSION['supp_trans']->rebate);
	
	// display_error('ewt: '.$_SESSION['supp_trans']->ewt);
	// display_error('ap : '.$_SESSION['supp_trans']->acounts_payable);
	
	// display_error($_SESSION['supp_trans']->purch_non_vat + $_SESSION['supp_trans']->purch_vat + $_SESSION['supp_trans']->ewt);
	// display_error($_SESSION['supp_trans']->purch_non_vat + $_SESSION['supp_trans']->purch_vat 
		// + $_SESSION['supp_trans']->vat  - $_SESSION['supp_trans']->purch_discount - $_SESSION['supp_trans']->ewt);
	if (!$_SESSION['supp_trans']->nt)
		$invoice_no = add_supp_invoice_new($_SESSION['supp_trans']);
		//display_error($_SESSION['supp_trans'].'NT');
	else
		$invoice_no = add_supp_invoice($_SESSION['supp_trans']);
		//display_error($_SESSION['supp_trans'].'-');
    $_SESSION['supp_trans']->clear_items();
    unset($_SESSION['supp_trans']);

	meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
}

//--------------------------------------------------------------------------------------------------


if (isset($_POST['PostInvoice']))
{
	// if ($_POST['choose_inv'] != '' AND input_num('total_po_price') < $_SESSION['supp_trans']->ov_amount)
	if ($_POST['choose_inv'] != '')
	{
		// $comp = "Invoice Total (using PO price)";
		// if($_POST['drow'])
			// $comp = "Invoice Total after Discrepancy Report";
		// display_error("Actual Invoice Total is greater than $comp. Please add to Discrepancy Report");
		// $_POST['actual_inv_total'] = number_format2($_SESSION['supp_trans']->ov_amount,2);
		// set_focus('actual_inv_total');$_SESSION['supp_trans']
		unset($_POST['PostInvoice']);
		$_POST['process_apv'] = true;
	}
	else if ($_POST['choose_inv'] != '' AND count($_SESSION['supp_trans']->grn_items) < $_SESSION['supp_trans']->item_count)
	{
		display_error("Some items are not yet added to the invoice.");
		unset($_POST['PostInvoice']);
	}
	else
		handle_commit_invoice();
}

function check_item_data($n)
{
	global $check_price_charged_vs_order_price,
		$check_qty_charged_vs_del_qty, $SysPrefs;
	if (!check_num('this_quantity_inv'.$n, 0) || input_num('this_quantity_inv'.$n)==0)
	{
		display_error( _("The quantity to invoice must be numeric and greater than zero."));
		set_focus('this_quantity_inv'.$n);
		return false;
	}

	if (!check_num('ChgPrice'.$n))
	{
		display_error( _("The price is not numeric."));
		set_focus('ChgPrice'.$n);
		return false;
	}

	$margin = $SysPrefs->over_charge_allowance();
	if ($check_price_charged_vs_order_price == True)
	{
		if ($_POST['order_price'.$n]!=input_num('ChgPrice'.$n)) {
		     if ($_POST['order_price'.$n]==0 ||
				input_num('ChgPrice'.$n)/$_POST['order_price'.$n] >
			    (1 + ($margin/ 100)))
		    {
			display_error(_("The price being invoiced is more than the purchase order price by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") .
			_("The over-charge percentage allowance is :") . $margin . "%" . $_POST['order_price'.$n] . ' -> '.input_num('ChgPrice'.$n));
			set_focus('ChgPrice'.$n);
			return false;
		    }
		}
	}

	if ($check_qty_charged_vs_del_qty == True)
	{
		if (input_num('this_quantity_inv'.$n) / ($_POST['qty_recd'.$n] - $_POST['prev_quantity_inv'.$n]) >
			(1+ ($margin / 100)))
		{
			display_error( _("The quantity being invoiced is more than the outstanding quantity by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.")
			. _("The over-charge percentage allowance is :") . $margin . "%");
			set_focus('this_quantity_inv'.$n);
			return false;
		}
	}

	return true;
}

function commit_item_data($n)
{
	if (check_item_data($n))
	{
    	if (input_num('this_quantity_inv'.$n) >= ($_POST['qty_recd'.$n] - $_POST['prev_quantity_inv'.$n]))
    	{
    		$complete = true;
    	}
    	else
    	{
    		$complete = false;
    	}

		$_SESSION['supp_trans']->add_grn_to_trans($n, $_POST['po_detail_item'.$n],
			$_POST['item_code'.$n], $_POST['item_description'.$n], $_POST['qty_recd'.$n],
			$_POST['prev_quantity_inv'.$n], input_num('this_quantity_inv'.$n),
			$_POST['order_price'.$n], input_num('ChgPrice'.$n), $complete,
			$_POST['std_cost_unit'.$n], "",$_POST['r_uom'.$n],$_POST['multiplier'.$n], 
			array('source_invoice_no' => $_POST['source_invoice_no'.$n],
					'purch_order_no' => $_POST['purch_order_no'.$n],
					'delivery_date' => $_POST['delivery_date'.$n])
		);
	}
}

//-----------------------------------------------------------------------------------------

$id = find_submit('grn_item_id');
if ($id != -1)
{
	commit_item_data($id);
}



if (isset($_POST['process_apv']))
{
	if (round2(input_num('total_po_price'),2) < round2($_SESSION['supp_trans']->ov_amount,2))
	{
		$comp = "Invoice Total (using PO price)";
		if($_POST['drow'])
			$comp = "Invoice Total after Discrepancy Report";
		display_error("Actual Invoice Total (<b>".number_format2($_SESSION['supp_trans']->ov_amount,2)."</b>)
			is greater than $comp (<b>".number_format2(input_num('total_po_price'),2)."</b>). Please add to Discrepancy Report");
		$_POST['actual_inv_total'] = number_format2($_SESSION['supp_trans']->ov_amount,2);
		set_focus('actual_inv_total');
		unset($_POST['process_apv']);
	}
	else if (count($_SESSION['supp_trans']->grn_items) < $_SESSION['supp_trans']->item_count)
	{
		display_error("Some items are not yet added to the invoice.");
		unset($_POST['process_apv']);
	}
	else
		handle_commit_invoice();	
}

if (isset($_POST['submit_to_discrepancy']))
{
	if (input_num('actual_inv_total') == 0)
	{
		display_error("Actual Invoice Total is required for discrepancy");
		set_focus('actual_inv_total');
	}
	else if (input_num('total_po_price') == input_num('actual_inv_total'))
	{
		$comp = "Invoice Total (using PO price)";
		if($_POST['drow'])
			$comp = "Invoice Total after Discrepancy Report";
		display_error("Actual Invoice Total is equal to $comp. No need to add to Discrepancy Report");
		set_focus('actual_inv_total');
	}
	else
	{
		add_to_discrepancy_report($_POST['choose_inv'], input_num('total_po_price'), input_num('actual_inv_total'), $_POST['Comments']);
		 $_SESSION['supp_trans']->clear_items();
		unset($_SESSION['supp_trans']);

		meta_forward($_SERVER['PHP_SELF'], "D_AddedID=".get_grn_batch_inv_no($_POST['choose_inv']));
	}
}

if (isset($_POST['InvGRNAll'])) // OR isset($_POST['process_apv'])
{
   	foreach($_POST as $postkey=>$postval )
    {
		if (strpos($postkey, "qty_recd") === 0)
		{
			$id = substr($postkey, strlen("qty_recd"));
			$id = (int)$id;
			commit_item_data($id);
		}
    }
}	

//--------------------------------------------------------------------------------------------------

if (isset($_POST['recompute_totals']) OR list_updated('no_ewt') OR list_updated('ret_disc'))
{
	global $Ajax;
	
	$Ajax->activate('inv_tot');
	set_focus('inv_total_no_hidden');
}

//--------------------------------------------------------------------------------------------------
$id3 = find_submit('Delete');
if ($id3 != -1)
{
	$_SESSION['supp_trans']->remove_grn_from_trans($id3);
	$Ajax->activate('grn_items');
	$Ajax->activate('inv_tot');
}

$id4 = find_submit('Delete2');
if ($id4 != -1)
{
	$_SESSION['supp_trans']->remove_gl_codes_from_trans($id4);
	clear_fields();
	$Ajax->activate('gl_items');
	$Ajax->activate('inv_tot');
}

$id2 = -1;
if ($_SESSION["wa_current_user"]->can_access('SA_GRNDELETE'))
{
	$id2 = find_submit('void_item_id');
	if ($id2 != -1) 
	{
		begin_transaction();
		
		$myrow = get_grn_item_detail($id2);

		$grn = get_grn_batch($myrow['grn_batch_id']);

	    $sql = "UPDATE ".TB_PREF."purch_order_details
			SET quantity_received = qty_invoiced, quantity_ordered = qty_invoiced WHERE po_detail_item = ".$myrow["po_detail_item"];
	    db_query($sql, "The quantity invoiced of the purchase order line could not be updated");

	    $sql = "UPDATE ".TB_PREF."grn_items
	    	SET qty_recd = quantity_inv WHERE id = ".$myrow["id"];
		db_query($sql, "The quantity invoiced off the items received record could not be updated");
	
		update_average_material_cost($grn["supplier_id"], $myrow["item_code"],
			$myrow["unit_price"], -$myrow["QtyOstdg"], Today());

	   	add_stock_move(ST_SUPPRECEIVE, $myrow["item_code"], $myrow['grn_batch_id'], $grn['loc_code'], sql2date($grn["delivery_date"]), "",
	   		-$myrow["QtyOstdg"], $myrow['std_cost_unit'], $grn["supplier_id"], 1, $myrow['unit_price']);
	   		
	   	commit_transaction();
		display_notification(sprintf(_('All yet non-invoiced items on delivery line # %d has been removed.'), $id2));

	}   		
}

if (isset($_POST['go']))
{
	$Ajax->activate('gl_items');
	display_quick_entries($_SESSION['supp_trans'], $_POST['qid'], input_num('totamount'), QE_SUPPINV);
	$_POST['totamount'] = price_format(0); $Ajax->activate('totamount');
	$Ajax->activate('inv_tot');
}

start_form();

invoice_header($_SESSION['supp_trans']);

if ($_POST['supplier_id']=='') 
		display_error(_("There is no supplier selected."));
else {
	div_start('tablesss');
		
		if (!$_SESSION['supp_trans']->nt)
		{
			display_grn_items($_SESSION['supp_trans'], 1);
		}
		else
			display_gl_items($_SESSION['supp_trans'], 1);
	div_end();
	
	
	div_start('hidden_disc');
	if (!$_SESSION['supp_trans']->nt)
	{
		$th = array('For Discounts / included Returns');
		start_table("$table_style width=95%");
		table_header($th);
		start_row();
		echo '<td>';
			start_table('align=right');
			start_row();
				yesno_list_cells('<b>Use Discount/Purchase Returns:</b>', 
					'ret_disc', null, 'Purchase Returns', 'Purchase Discount', true, true);
					
				label_cell('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
				amount_cells('Invoice Amount (without hidden discounts): ','inv_total_no_hidden');
				submit_cells('recompute_totals', 'Recompute Totals','align=center', false, true);
			end_row();
			end_table();
		echo '</td>';
		end_row();
		end_table(2);
	}
	// if (!$_SESSION['supp_trans']->nt)
	// {
		// $th = array('For Discounts / included Returns');
		// start_table("$table_style width=95%");
		// table_header($th);
		// start_row();
		// echo '<td>';
			// start_table('align=right');
			
			
			// start_row();
				// // amount_cells('Purchase Discount:', 'purch_disc';
				// label_cells('Purchase Discount:', '<i>auto compute</i>');
			// end_row();
			// start_row();
				// amount_cells('Purchase Returns:', 'purch_ret');
			// end_row();
			// start_row();
				// amount_cells('Display Allowance:', 'disp_allow');
			// end_row();
			// start_row();
				// amount_cells('Trade Promo:', 'trade_promo');
			// end_row();
			// start_row();
				// amount_cells('Rebate:', 'rebate');
			// end_row();
			// start_row();
				// hidden('ret_disc', false);
				// // yesno_list_cells('<b>Use Discount/Purchase Returns:</b>', 
					// // 'ret_disc', null, 'Purchase Returns', 'Purchase Discount', true, true);
					
				// // label_cell('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
				// amount_cells('Invoice Amount (without hidden discounts): ','inv_total_no_hidden');
				// submit_cells('recompute_totals', 'Recompute Total','align=center', false, true);
			// end_row();
			// end_table();
		// echo '</td>';
		// end_row();
		// end_table(2);
	// }
	
	div_end();
	
	div_start('inv_tot');
	invoice_totals($_SESSION['supp_trans']);
	div_end();

}

//-----------------------------------------------------------------------------------------

if ($id != -1 || $id2 != -1)
{
	$Ajax->activate('grn_items');
	$Ajax->activate('inv_tot');
}

if (get_post('AddGLCodeToTrans'))
	$Ajax->activate('inv_tot');

if (isset($_POST['refresh_totals']))
{
	
	copy_to_trans($_SESSION['supp_trans']);
	$Ajax->activate('inv_tot');
}

br();

submit_center('PostInvoice', _("Process APV"), true, '', false);
br();

end_form();

//--------------------------------------------------------------------------------------------------

end_page();
?>