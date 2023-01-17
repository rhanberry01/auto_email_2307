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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
simple_page_mode(false);


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Acquiring Banks"), false, false, "", $js);


//----------------------------------------------------------------------------------


function write_acquiring_bank($selected,$acquiring_bank, $gl_bank_account, $gl_bank_debit_account,  $gl_mfee_account, $gl_wtax_account,$dc_merchant_fee_percent, $cc_merchant_fee_percent,$cc_withholding_tax_percent)
{
    if($selected!='')
		$sql = "UPDATE ".TB_PREF."acquiring_banks SET
	 	acquiring_bank = ".db_escape($acquiring_bank).",
	 	gl_bank_account = ".db_escape($gl_bank_account).",
		gl_bank_debit_account = ".db_escape($gl_bank_debit_account).",
		gl_mfee_account = ".db_escape($gl_mfee_account).",
		gl_wtax_account = ".db_escape($gl_wtax_account).",
	 	dc_merchant_fee_percent = ".db_escape($dc_merchant_fee_percent).",
	 	cc_merchant_fee_percent = ".db_escape($cc_merchant_fee_percent).",
	 	cc_withholding_tax_percent = ".db_escape($cc_withholding_tax_percent)."
        	WHERE id = ".db_escape($selected);
    else
		$sql = "INSERT INTO ".TB_PREF."acquiring_banks
				(acquiring_bank, gl_bank_account, gl_bank_debit_account, gl_mfee_account, gl_wtax_account, dc_merchant_fee_percent, cc_merchant_fee_percent, cc_withholding_tax_percent) 
			VALUES(".db_escape($acquiring_bank).", ".$gl_bank_account.", ".$gl_bank_debit_account.", ".$gl_mfee_account.", ".$gl_wtax_account.", ".$dc_merchant_fee_percent.", ".$cc_merchant_fee_percent.", ".$cc_withholding_tax_percent.")";

	db_query($sql,"Acquiring Bank could not be updated");
	//display_error($sql);
}


if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;
	

	if (strlen($_POST['acquiring_bank']) == 0)
	{
		$input_error = 1;
		display_error(_("The Acquiring Bank cannot be empty."));
		set_focus('acquiring_bank');
	}
	
	if ($_POST['gl_bank_account'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose bank account."));
		set_focus('gl_bank_account');
	}
	
	
		if ($_POST['gl_bank_debit_account'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose bank debit gl account."));
		set_focus('gl_bank_debit_account');
	}
	
		if ($_POST['gl_mfee_account'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose merchant fee account."));
		set_focus('gl_mfee_account');
	}
	
	
			if ($_POST['gl_wtax_account'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose withholding tax account."));
		set_focus('gl_wtax_account');
	}
	
	if (input_num('dc_merchant_fee_percent') > 100 OR input_num('dc_merchant_fee_percent') < 0)
	{
		$input_error = 1;
		display_error(_("Debit Card Merchant Fee must be between 0 - 100 (inclusive)"));
		set_focus('dc_merchant_fee_percent');
	}

	if (input_num('cc_merchant_fee_percent') > 100 OR input_num('cc_merchant_fee_percent') < 0)
	{
		$input_error = 1;
		display_error(_("Credit Card Merchant Fee must be between 0 - 100 (inclusive)"));
		set_focus('cc_merchant_fee_percent');
	}

	if (input_num('cc_withholding_tax_percent') > 100 OR input_num('cc_withholding_tax_percent') < 0)
	{
		$input_error = 1;
		display_error(_("Credit Card Withholding Tax must be between 0 - 100 (inclusive)"));
		set_focus('cc_withholding_tax_percent');
	}

	
	if ($input_error !=1) {
		write_acquiring_bank($selected_id,$_POST['acquiring_bank'],$_POST['gl_bank_account'],$_POST['gl_bank_debit_account'],$_POST['gl_mfee_account'],$_POST['gl_wtax_account'], input_num('dc_merchant_fee_percent'), 
			input_num('cc_merchant_fee_percent'),input_num('cc_withholding_tax_percent'));
		if($selected_id != '')
			display_notification(_('Selected Bank has been updated'));
		else
			display_notification(_('New Acquiring Bank has been added'));
		$Mode = 'RESET';
	}
	
}

//---------------------START OF DELETE--------------------------------

if ($Mode == 'Delete')
{
	delete_item_unit($selected_id);
	$sql_del="delete from ".TB_PREF."acquiring_banks where id=".db_escape($selected_id)."";
	db_query($sql_del);

	delete_item_unit($selected_id);
	display_notification(_('Selected Bank has been Deleted..'));
	$Mode = 'RESET';
}
	
if ($Mode == 'RESET')
{
	$selected_id = '';
	$sav = isset($_POST['show_inactive']);
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}

//------------------------END OF DELETE-------------------------------------------


//------------------------START DISPLAYING DATA TO TABLE-------------------------------------------
$result = get_all_acquiring_banks(check_value('show_inactive'));

start_form();
start_table("$table_style width=98%");
$th = array(_('Acquiring Bank'), _('Bank Account'), _('GL Debit Account'),_('Merchant Fee Account'),_('Withholding Tax Account'),_('Debit Merchant Rate'), _('Credit Merchant Rate'),'Withholding Tax',"","");
inactive_control_column($th);

table_header($th);
$k = 0; //row colour counter

while ($myrow = db_fetch($result))
{

	alt_table_row_color($k);

	label_cell($myrow["acquiring_bank"],'nowrap');
	label_cell(get_gl_account_name($myrow["gl_bank_account"]),'nowrap');
	label_cell(get_gl_account_name($myrow["gl_bank_debit_account"]),'nowrap');
	label_cell(get_gl_account_name($myrow["gl_mfee_account"]),'nowrap');
	label_cell(get_gl_account_name($myrow["gl_wtax_account"]),'nowrap');
	percent_cell($myrow["dc_merchant_fee_percent"]);
	percent_cell($myrow["cc_merchant_fee_percent"]);
	percent_cell($myrow["cc_withholding_tax_percent"]);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'acquiring_banks', 'id');
 	edit_button_cell("Edit".$myrow["id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["id"], _("Delete"));
	end_row();
}
inactive_control_row($th);

end_table(1);
//------------------------END OF DISPLAYING DATA TO TABLE-------------------------------------------



//-------------------------EDIT FORM QUERY---------------------------------------

start_table($table_style2);

if ($selected_id != '') 
{
 	if ($Mode == 'Edit') {
		//editing an existing item category

		$result2 = edit_display($selected_id); //FROM sales/includes/sales_db.inc
		
while ($myrow2 = db_fetch($result2)) //for displaying data to edit
{
		$_POST['acquiring_bank'] = $myrow2["acquiring_bank"];
		$_POST['gl_bank_account'] = $myrow2["gl_bank_account"];
		$_POST['gl_bank_debit_account'] = $myrow2["gl_bank_debit_account"];
		$_POST['gl_mfee_account'] = $myrow2["gl_mfee_account"];
		$_POST['gl_wtax_account'] = $myrow2["gl_wtax_account"];
		$_POST['dc_merchant_fee_percent']  = $myrow2["dc_merchant_fee_percent"];
		$_POST['cc_merchant_fee_percent']  = $myrow2["cc_merchant_fee_percent"];
		$_POST['cc_withholding_tax_percent']  = $myrow2["cc_withholding_tax_percent"];
		
		}
	}
	hidden('selected_id', $selected_id);
}


//FORM


text_row(_("Acquiring Bank:"), 'acquiring_bank');
gl_all_accounts_list_row("Bank Account:", 'gl_bank_account', null, false, false, 'Choose an account');
gl_all_accounts_list_row("Debit GL Account:", 'gl_bank_debit_account', null, false, false, 'Choose an account');
gl_all_accounts_list_row("Merchant Fee Account:", 'gl_mfee_account', null, false, false, 'Choose an account');
gl_all_accounts_list_row("Withholding Tax Account (Credit Only):", 'gl_wtax_account', null, false, false, 'Choose an account');
percent_row(_("Debit Card Merchant Rate:"), 'dc_merchant_fee_percent');
percent_row(_("Credit Card Merchant Rate:"), 'cc_merchant_fee_percent');
percent_row(_("Creditable Withholding Tax:"), 'cc_withholding_tax_percent');
end_table(1);


submit_add_or_update_center($selected_id == '', '', 'both');

end_form();


end_page();


?>