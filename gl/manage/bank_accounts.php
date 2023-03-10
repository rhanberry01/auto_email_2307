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
$page_security = 'SA_BANKACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Bank Accounts"));

include($path_to_root . "/includes/ui.inc");

simple_page_mode();
//-----------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	//first off validate inputs sensible
	if (strlen($_POST['bank_account_name']) == 0) 
	{
		$input_error = 1;
		display_error(_("The bank account name cannot be empty."));
		set_focus('bank_account_name');
	} 
	
	if ($input_error != 1)
	{
    	if ($selected_id != -1) 
    	{
    		
    		update_bank_account($selected_id, $_POST['account_code'],
				$_POST['account_type'], $_POST['bank_account_name'], 
				$_POST['bank_name'], $_POST['bank_account_number'], 
    			$_POST['bank_address'], $_POST['BankAccountCurrency'],
    			$_POST['dflt_curr_act']);
			display_notification(_('Bank account has been updated'));
    	} 
    	else 
    	{
    
    		add_bank_account($_POST['account_code'], $_POST['account_type'], 
				$_POST['bank_account_name'], $_POST['bank_name'], 
    			$_POST['bank_account_number'], $_POST['bank_address'], 
				$_POST['BankAccountCurrency'], $_POST['dflt_curr_act']);
			display_notification(_('New bank account has been added'));
    	}
 		$Mode = 'RESET';
	}
} 
elseif( $Mode == 'Delete')
{
	//the link to delete a selected record was clicked instead of the submit button

	$cancel_delete = 0;
	$acc = db_escape($selected_id);
	// PREVENT DELETES IF DEPENDENT RECORDS IN 'bank_trans'

	$sql= "SELECT COUNT(*) FROM ".TB_PREF."bank_trans WHERE bank_act=$acc";
	$result = db_query($sql,"check failed");
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		$cancel_delete = 1;
		display_error(_("Cannot delete this bank account because transactions have been created using this account."));
	}
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."sales_pos WHERE pos_account=$acc";
	$result = db_query($sql,"check failed");
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		$cancel_delete = 1;
		display_error(_("Cannot delete this bank account because POS definitions have been created using this account."));
	}
	if (!$cancel_delete) 
	{
		delete_bank_account($selected_id);
		display_notification(_('Selected bank account has been deleted'));
	} //end if Delete bank account
	$Mode = 'RESET';
} 

if ($Mode == 'RESET')
{
 	$selected_id = -1;
	$_POST['bank_name']  = 	$_POST['bank_account_name']  = '';
	$_POST['bank_account_number'] = $_POST['bank_address'] = '';
}

/* Always show the list of accounts */

$sql = "SELECT account.*, gl_account.account_name 
	FROM ".TB_PREF."bank_accounts account, ".TB_PREF."chart_master gl_account 
	WHERE account.account_code = gl_account.account_code";
if (!check_value('show_inactive')) $sql .= " AND !account.inactive";
$sql .= " ORDER BY account_code, bank_curr_code";

$result = db_query($sql,"could not get bank accounts");

check_db_error("The bank accounts set up could not be retrieved", $sql);

start_form();
start_table("$table_style width='80%'");

$th = array(_("Account Name"), _("Type"), _("Currency"), _("GL Account"), 
	_("Bank"), _("Number"), _("Bank Address"), _("Dflt"), '','');
inactive_control_column($th);
table_header($th);	

$k = 0; 
while ($myrow = db_fetch($result)) 
{
	
	alt_table_row_color($k);

    label_cell($myrow["bank_account_name"], "nowrap");
	label_cell($bank_account_types[$myrow["account_type"]], "nowrap");
    label_cell($myrow["bank_curr_code"], "nowrap");
    label_cell($myrow["account_code"] . " " . $myrow["account_name"], "nowrap");
    label_cell($myrow["bank_name"], "nowrap");
    label_cell($myrow["bank_account_number"], "nowrap");
    label_cell($myrow["bank_address"]);
    if ($myrow["dflt_curr_act"])
		label_cell(_("Yes"));
	else
		label_cell(_("No"));

	inactive_control_cell($myrow["id"], $myrow["inactive"], 'bank_accounts', 'id');
 	edit_button_cell("Edit".$myrow["id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["id"], _("Delete"));
    end_row(); 
}

inactive_control_row($th);
end_table(1);

$is_editing = $selected_id != -1; 

start_table($table_style2);

if ($is_editing) 
{
  if ($Mode == 'Edit') {	
	$myrow = get_bank_account($selected_id);

	$_POST['account_code'] = $myrow["account_code"];
	$_POST['account_type'] = $myrow["account_type"];
	$_POST['bank_name']  = $myrow["bank_name"];
	$_POST['bank_account_name']  = $myrow["bank_account_name"];
	$_POST['bank_account_number'] = $myrow["bank_account_number"];
	$_POST['bank_address'] = $myrow["bank_address"];
	$_POST['BankAccountCurrency'] = $myrow["bank_curr_code"];
	$_POST['dflt_curr_act'] = $myrow["dflt_curr_act"];
  }
	hidden('selected_id', $selected_id);
	hidden('account_code');
	hidden('account_type');
	hidden('BankAccountCurrency', $_POST['BankAccountCurrency']);	
	set_focus('bank_account_name');
} 

text_row(_("Bank Account Name:"), 'bank_account_name', null, 50, 100);

if ($is_editing) 
{
	label_row(_("Account Type:"), $bank_account_types[$_POST['account_type']]);
} 
else 
{
	bank_account_types_list_row(_("Account Type:"), 'account_type', null); 
}
if ($is_editing) 
{
	label_row(_("Bank Account Currency:"), $_POST['BankAccountCurrency']);
} 
else 
{
	currencies_list_row(_("Bank Account Currency:"), 'BankAccountCurrency', null);
}	

yesno_list_row(_("Default currency account:"), 'dflt_curr_act');

if($is_editing)
	label_row(_("Bank Account GL Code:"), $_POST['account_code']);
else 
	gl_all_accounts_list_row(_("Bank Account GL Code:"), 'account_code', null, true);

text_row(_("Bank Name:"), 'bank_name', null, 50, 60);
text_row(_("Bank Account Number:"), 'bank_account_number', null, 30, 60);
textarea_row(_("Bank Address:"), 'bank_address', null, 40, 5);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
?>
