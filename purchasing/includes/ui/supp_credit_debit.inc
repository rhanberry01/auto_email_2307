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
include_once($path_to_root . "/includes/ui.inc");
//--------------------------------------------------------------------------------

function display_order_header()
{
	global $table_style2, $Ajax;

	if (list_updated('is_recurrent'))
		$Ajax->activate('recur');
		
	div_start('recur');
	start_outer_table("$table_style2 width=90%");
	table_section(1,"35%");

	$display_ref = '# :';
	
	if (list_updated('memo_type'))
	{
		if ($_POST['memo_type'] == 53)
		{
			$display_ref = 'Debit Memo #: ';
		}
		else if ($_POST['memo_type'] == 52)
		{
			$display_ref = 'Credit Memo #: ';
		}
		create_cart($_POST['memo_type'],0);
	}
	
	start_row();
	supp_debit_credit_memo_list_cells('Memo Type: ','memo_type');
	end_row();
	
	// supplier_list_row2(_("Supplier:"), 'supplier_id', null, '');
	supplier_list_ms_cells('Database Supplier:', 'supplier_id', null, 'Use Supplier below');
	supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
	table_section(2, "30%");
	ref_row(_("$display_ref"), 'ref', '');
    date_row(_("Date:"), 'date_');
	
	table_section(2, "35%");
	check_row('Recurrent:', 'is_recurrent', null, true);
	// ref_row('Supplier Reference :', 'supp_ref');
	text_row('Supplier Reference :', 'supp_ref');
	if (check_value('is_recurrent'))
	{
		echo '<tr>';
		frequency_list_cells("Frequency", 'freq', null);
		qty_cells('Periods:', 'periods', null, null, null, 0);
		echo '</tr>';
	}
	
	end_outer_table(1);
	div_end();
}

//---------------------------------------------------------------------------------

function display_gl_items($title, &$order)
{
	global $table_style, $path_to_root;

	display_heading($title);

	$dim = get_company_pref('use_dimension');

    div_start('items_table');
	start_table("$table_style colspan=7 width=95%");
	if ($dim == 2)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1",
			_("Dimension")." 2", _("Debit"), _("Credit"), _("Particulars"), "");
	else if ($dim == 1)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension"),
			_("Debit"), _("Credit"), _("Particulars"), "");
	else
		$th = array(_("Account Code"), _("Account Description"),
			_("Debit"), _("Credit"), _("Particulars"), "");

	if (count($order->gl_items)) $th[] = '';

	table_header($th);	

	$k = 0;

	$id = find_submit('Edit');
	foreach ($order->gl_items as $line => $item) 
	{
		if ($id != $line)
		{
    		alt_table_row_color($k);

			label_cells($item->code_id, $item->description);
    		if ($dim >= 1)
   				label_cell(get_dimension_string($item->dimension_id, true));
    		if ($dim > 1)
   				label_cell(get_dimension_string($item->dimension2_id, true));
    		if ($item->amount > 0)
    		{
    			amount_cell(abs($item->amount));
    			label_cell("");
    		}	
    		else
    		{
    			label_cell("");
    			amount_cell(abs($item->amount));
    		}	
			label_cell($item->reference);

			edit_button_cell("Edit$line", _("Edit"),
				_('Edit journal line'));
			delete_button_cell("Delete$line", _("Delete"),
				_('Remove line from journal'));
    		end_row();
		} 
		else 
		{
			gl_edit_item_controls($order, $dim, $line);
		}
	}

	if ($id == -1)
		gl_edit_item_controls($order, $dim);

	if ($order->count_gl_items()) 
	{
		$colspan = ($dim == 2 ? "4" : ($dim == 1 ? "3" : "2"));
		start_row();
		label_cell(_("Total"), "align=right colspan=" . $colspan);
		amount_cell($order->gl_items_total_debit());
		amount_cell(abs($order->gl_items_total_credit()));
		label_cell('', "colspan=3");
		end_row();
	}

    end_table();
	div_end();
}

//---------------------------------------------------------------------------------

function gl_edit_item_controls(&$order, $dim, $Index=null)
{
	global $Ajax;
	start_row();

	$id = find_submit('Edit');
	if ($Index != -1 && $Index == $id)
	{
	    // Modifying an existing row
		$item = $order->gl_items[$Index];
		$_POST['code_id'] = $item->code_id;
		$_POST['dimension_id'] = $item->dimension_id;
		$_POST['dimension2_id'] = $item->dimension2_id;
		if ($item->amount > 0)
		{
			$_POST['Amount'] = price_format($item->amount);
			$_POST['AmountCredit'] = "";
		}
		else
		{
			$_POST['Amount'] = "";
			$_POST['AmountCredit'] = price_format(abs($item->amount));
		}	
		$_POST['description'] = $item->description;
		$_POST['LineMemo'] = $item->reference;

		hidden('Index', $id);
		$skip_bank = !$_SESSION["wa_current_user"]->can_access('SA_BANKJOURNAL');
		echo gl_all_accounts_list('code_id', null, $skip_bank, true);
		if ($dim >= 1) 
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1) 
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	    $Ajax->activate('items_table');
	}
	else
	{
	    // Adding a new row
		$_POST['Amount'] = '';  //price_format(0);
		$_POST['AmountCredit'] = ''; //price_format(0);
		$_POST['dimension_id'] = 0;
		$_POST['dimension2_id'] = 0;
		$_POST['LineMemo'] = "";
		$_POST['_code_id_edit'] = "";
		$_POST['code_id'] = "";
		if(isset($_POST['_code_id_update'])) {
			    $Ajax->activate('code_id');
		}
		
		$skip_bank = !$_SESSION["wa_current_user"]->can_access('SA_BANKJOURNAL');
		echo gl_all_accounts_list('code_id', null, $skip_bank, true);
		if ($dim >= 1)
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1)
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	}
	if ($dim < 1)
		hidden('dimension_id', 0);
	if ($dim < 2)
		hidden('dimension2_id', 0);

	amount_cells(null, 'Amount');
	amount_cells(null, 'AmountCredit');
	text_cells_ex(null, 'LineMemo', 35, 255);

	if ($id != -1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
 		set_focus('amount');
	} 
	else 
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new line to journal'), true);

	end_row();
}

//---------------------------------------------------------------------------------

function gl_options_controls()
{
	  echo "<br><table align='center'>";

	  textarea_row(_("Memo"), 'memo_', null, 50, 3);

	  echo "</table>";
}

//---------------------------------------------------------------------------------

function is_date_in_fiscalyears($date)
{
	$date = date2sql($date);
	$sql = "SELECT * FROM ".TB_PREF."fiscal_year WHERE '$date' >= begin AND '$date' <= end";

	$result = db_query($sql, "could not get all fiscal years");
	return (db_num_rows($result) > 0);
}


?>