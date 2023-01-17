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
// $page_security = 'SA_GLACCOUNTCLASS';
$page_security = 'SA_DEPOSIT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Other Income Types"));

include($path_to_root . "/gl/includes/gl_db.inc");

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);
//-----------------------------------------------------------------------------------

function can_process() 
{
	global $use_oldstyle_convert;

	if (strlen($_POST['name']) == 0) 
	{
		display_error( _("The account class name cannot be empty."));
		set_focus('name');
		return false;
	}
	
	if (($_POST['gl_vat_account']=='') or ($_POST['gl_vat_account']==0)) {
		display_error(_("Gl Account for VAT Cannot be empty. Please select one."));
		set_focus('gl_vat_account');
		return false;
	}
	
	if (($_POST['gl_wtax_account']=='') or ($_POST['gl_wtax_account']==0)) {
		display_error(_("Gl Account for Wtax Cannot be empty. Please select one."));
		set_focus('gl_wtax_account');
		return false;
	}
	
	
	return true;
}

//-----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	if (can_process()) 
	{

    	if ($selected_id != -1) 
    	{
    		if(update_oi_type($selected_id, $_POST['name'], $_POST['gl_debit_account'],	
			$_POST['gl_vat_account'],$_POST['gl_wtax_account'],$_POST['vat_percent'],$_POST['withholding_tax_percent']))
			display_notification(_('Selected other income type has been updated'));
    		$Mode = 'RESET';
		} 
    	else 
    	{
    		if(add_oi_type($_POST['name'], $_POST['gl_debit_account'],	
			$_POST['gl_vat_account'],$_POST['gl_wtax_account'],$_POST['vat_percent'],$_POST['withholding_tax_percent'])) {
			display_notification(_('New other income type has been added'));
			$Mode = 'RESET';
			}
    	}
	}
}
//-----------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
		delete_oi_type($selected_id);
		display_notification(_('Selected other income type has been deleted'));
		$Mode = 'RESET';
}

//-----------------------------------------------------------------------------------
if ($Mode == 'RESET')
{
 $selected_id = -1;
 $_POST['id']  = $_POST['name']  = $_POST['ctype'] = $_POST['gl_debit_account'] = 
 $_POST['gl_vat_account'] = $_POST['gl_wtax_account'] = $_POST['vat_percent'] = $_POST['withholding_tax_percent'] ='';
}
//-----------------------------------------------------------------------------------

$result = get_oi_types(check_value('show_inactive'));

start_form();
start_table($table_style);
$th = array(_("Type"),_("GL Acount"), _("VAT GL Acount"), _("EWT GL Acount"),  _("VAT (%)"), "EWT (%)", "","");
if (isset($use_oldstyle_convert) && $use_oldstyle_convert == 1)
	$th[2] = _("Balance Sheet");
inactive_control_column($th);
table_header($th);

$k = 0;
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);
	label_cell($myrow["oi_type_name"]);
	label_cell(get_gl_account_name($myrow["oi_gl_debit"]),'nowrap');
	label_cell(get_gl_account_name($myrow["oi_ov_gl"]),'nowrap');
	label_cell(get_gl_account_name($myrow["oi_wt_gl"]),'nowrap');
	label_cell($myrow["oi_ov"]);
	label_cell($myrow["oi_wt"]);
	inactive_control_cell($myrow["oi_id"], $myrow["inactive"], 'other_income_type', 'oi_id');
	edit_button_cell("Edit".$myrow["oi_id"], _("Edit"));
	delete_button_cell("Delete".$myrow["oi_id"], _("Delete"));
	end_row();
}
inactive_control_row($th);
end_table(1);
//-----------------------------------------------------------------------------------

start_table($table_style2);

if ($selected_id != -1) 
{
 if ($Mode == 'Edit') {
	//editing an existing status code
	$myrow = get_selected_oi_type($selected_id);

	$_POST['id']    = $myrow["oi_id"];
	$_POST['name']  = $myrow["oi_type_name"];
	$_POST['gl_debit_account']  = $myrow["oi_gl_debit"];
	$_POST['gl_vat_account']  = $myrow["oi_ov_gl"];
	$_POST['gl_wtax_account']  = $myrow["oi_wt_gl"];
	$_POST['vat_percent']  = $myrow["oi_ov"];
	$_POST['withholding_tax_percent']  = $myrow["oi_wt"];
	hidden('selected_id', $selected_id);
 }
} 

text_row_ex(_("Type Name:"), 'name', 50, 60);
gl_all_accounts_list_row("Debit GL Account:", 'gl_debit_account', null, false, false, 'Choose an account');
gl_all_accounts_list_row("VAT GL Account:", 'gl_vat_account', null, false, false, 'Choose an account');
gl_all_accounts_list_row("Withholding Tax Account:", 'gl_wtax_account', null, false, false, 'Choose an account');
small_amount_row(_("VAT:"), 'vat_percent','','','',2);
small_amount_row(_("Withholding Tax:"), 'withholding_tax_percent','','','',2);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();
end_page();
?>