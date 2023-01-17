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

	$Ajax->activate('inv_tot');
}


//--------------------------------------------------------------------------------------------------

function invoice_header(&$supp_trans)
{
	global $Ajax, $table_style2, $Refs;
	
	// if vars have been lost, recopy
	if (!isset($_POST['tran_date']))
		copy_from_trans($supp_trans);

	start_outer_table("width=95% $table_style2");

	table_section(1);

    	if (!isset($_POST['supplier_id']) && (get_global_supplier() != ALL_TEXT))
    		$_POST['supplier_id'] = get_global_supplier();

	
			 customer_list_row(_("Customer/Tenant:"), 'supplier_id', $_POST['supplier_id'], false, true);
	
	
	if ($supp_trans->supplier_id != $_POST['supplier_id'] OR list_updated('choose_inv') OR
		($_POST['choose_inv'] != '' AND isset($_POST['reload_'])))
	{
		if (isset($_GET['NT']))
			$supp_trans->nt = true;
		// }
		$supp_trans->clear_items();
		read_supplier_details_to_trans($supp_trans, $_POST['supplier_id']);
		copy_from_trans($supp_trans);
	}
	
	if (list_updated('choose_inv') OR
		($_POST['choose_inv'] != '' AND isset($_POST['reload_'])))
	{
		
		// ========================================

		$Ajax->activate('supp_reference');
		$Ajax->activate('_ex_rate');
		$supp_trans->tran_date = $_POST['del_date'];
		get_duedate_from_terms($supp_trans);
		$_POST['due_date'] = $supp_trans->due_date;
		
		$Ajax->activate('del_date');
		$Ajax->activate('due_date');
		$Ajax->activate('tran_date');
		$Ajax->activate('proc_disc');
		
	}

	$refref = $Refs->get_next(ST_OTHERINCOME);
		
	if ($supp_trans->is_invoice)
    	ref_row(_("Reference No.:"), 'reference', '', $refref);

	table_section(2, "33%");

   	// date_row(_("Delivery Date") . ":", 'del_date', '', true, 0, 0, 0, "", true);
	
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
	
	
	date_row(_("Date") . ":", 'tran_date');
    date_row(_("Due Date") . ":", 'due_date');

    label_row(_("Terms:"), $supp_trans->terms_description);
	

	table_section(3, "33%");

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
	
	set_global_supplier($_POST['supplier_id']);


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

	end_table(1);
    start_table($table_style2);
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


	start_outer_table("$table_style width=95%");

	
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
	label_row(_("<b>Total:</b>"), price_format($total_gl_value),"colspan=".$colspan." align=right", "nowrap align=right", ($mode==1?3:1));
	hidden('total_gl_value', $total_gl_value);
	end_table(1);
	div_end();

	return $total_gl_value;
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