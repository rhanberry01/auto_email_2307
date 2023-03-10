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
$page_security = 'SA_GLACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Chart of Accounts"));

include($path_to_root . "/includes/ui.inc");
include($path_to_root . "/gl/includes/gl_db.inc");
include($path_to_root . "/admin/db/tags_db.inc");
include_once($path_to_root . "/includes/data_checks.inc");

check_db_has_gl_account_groups(_("There are no account groups defined. Please define at least one account group before entering accounts."));

//-------------------------------------------------------------------------------------

if (isset($_POST['_AccountList_update'])) 
{
	$_POST['selected_account'] = $_POST['AccountList'];
	unset($_POST['account_code']);
}

if (isset($_POST['selected_account']))
{
	$selected_account = $_POST['selected_account'];
} 
elseif (isset($_GET['selected_account']))
{
	$selected_account = $_GET['selected_account'];
}
else
	$selected_account = "";
//-------------------------------------------------------------------------------------

if (isset($_POST['add']) || isset($_POST['update'])) 
{

	$input_error = 0;

	if (strlen($_POST['account_code']) == 0) 
	{
		$input_error = 1;
		display_error( _("The account code must be entered."));
		set_focus('account_code');
	} 
	elseif (strlen($_POST['account_name']) == 0) 
	{
		$input_error = 1;
		display_error( _("The account name cannot be empty."));
		set_focus('account_name');
	} 
	elseif (!$accounts_alpha && !is_numeric($_POST['account_code'])) 
	{
	    $input_error = 1;
	    display_error( _("The account code must be numeric."));
		set_focus('account_code');
	}

	if ($input_error != 1)
	{
		if ($accounts_alpha == 2)
			$_POST['account_code'] = strtoupper($_POST['account_code']);

		if (!isset($_POST['account_tags']))
			$_POST['account_tags'] = array();

    	if ($selected_account) 
		{
    		if (update_gl_account($_POST['account_code'], $_POST['account_name'], 
				$_POST['account_type'], $_POST['account_code2'])) {
				update_record_status($_POST['account_code'], $_POST['inactive'],
					'chart_master', 'account_code');
				update_tag_associations(TAG_ACCOUNT, $_POST['account_code'], 
					$_POST['account_tags']);
				$Ajax->activate('account_code'); // in case of status change
				display_notification(_("Account data has been updated."));
			}
		}
    	else 
		{
    		if (add_gl_account($_POST['account_code'], $_POST['account_name'], 
				$_POST['account_type'], $_POST['account_code2']))
				{
					add_tag_associations($_POST['account_code'], $_POST['account_tags']);
					display_notification(_("New account has been added."));
					$selected_account = $_POST['AccountList'] = $_POST['account_code'];
					
				}
		}
		$Ajax->activate('_page_body');
	}
} 

//-------------------------------------------------------------------------------------

function can_delete($selected_account)
{
	if ($selected_account == "")
		return false;
	$acc = db_escape($selected_account);

	$sql= "SELECT COUNT(*) FROM ".TB_PREF."gl_trans WHERE account=$acc";
	$result = db_query($sql,"Couldn't test for existing transactions");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because transactions have been created using this account."));
		return false;
	}

	$sql= "SELECT COUNT(*) FROM ".TB_PREF."company WHERE debtors_act=$acc 
		OR pyt_discount_act=$acc
		OR creditors_act=$acc 
		OR bank_charge_act=$acc 
		OR exchange_diff_act=$acc
		OR profit_loss_year_act=$acc
		OR retained_earnings_act=$acc
		OR freight_act=$acc
		OR default_sales_act=$acc 
		OR default_sales_discount_act=$acc
		OR default_prompt_payment_act=$acc
		OR default_inventory_act=$acc
		OR default_cogs_act=$acc
		OR default_adj_act=$acc
		OR default_inv_sales_act=$acc
		OR default_assembly_act=$acc";
	$result = db_query($sql,"Couldn't test for default company GL codes");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because it is used as one of the company default GL accounts."));
		return false;
	}
	
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."bank_accounts WHERE account_code=$acc";
	$result = db_query($sql,"Couldn't test for bank accounts");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because it is used by a bank account."));
		return false;
	}	

	$sql= "SELECT COUNT(*) FROM ".TB_PREF."stock_master WHERE 
		inventory_account=$acc 
		OR cogs_account=$acc
		OR adjustment_account=$acc 
		OR sales_account=$acc";
	$result = db_query($sql,"Couldn't test for existing stock GL codes");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because it is used by one or more Items."));
		return false;
	}	
	
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."tax_types WHERE sales_gl_code=$acc OR purchasing_gl_code=$acc";
	$result = db_query($sql,"Couldn't test for existing tax GL codes");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because it is used by one or more Taxes."));
		return false;
	}	
	
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."cust_branch WHERE 
		sales_account=$acc 
		OR sales_discount_account=$acc
		OR receivables_account=$acc
		OR payment_discount_account=$acc";
	$result = db_query($sql,"Couldn't test for existing cust branch GL codes");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because it is used by one or more Customer Branches."));
		return false;
	}		
	
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."suppliers WHERE 
		purchase_account=$acc
		OR payment_discount_account=$acc
		OR payable_account=$acc";
	$result = db_query($sql,"Couldn't test for existing suppliers GL codes");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because it is used by one or more suppliers."));
		return false;
	}									
	
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."quick_entry_lines WHERE 
		dest_id=$acc AND UPPER(LEFT(action, 1)) <> 'T'";
	$result = db_query($sql,"Couldn't test for existing suppliers GL codes");

	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this account because it is used by one or more Quick Entry Lines."));
		return false;
	}									

	return true;
}

//--------------------------------------------------------------------------------------

if (isset($_POST['delete'])) 
{

	if (can_delete($selected_account))
	{
		delete_gl_account($selected_account);
		$selected_account = $_POST['AccountList'] = '';
		delete_tag_associations(TAG_ACCOUNT,$selected_account, true);
		$selected_account = $_POST['AccountList'] = '';
		display_notification(_("Selected account has been deleted"));
		unset($_POST['account_code']);
		$Ajax->activate('_page_body');
	}
} 

//-------------------------------------------------------------------------------------

start_form();

if (db_has_gl_accounts()) 
{
	start_table("class = 'tablestyle_noborder'");
	start_row();
    gl_all_accounts_list_cells(null, 'AccountList', null, false, false,
		_('New account'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('AccountList');
		set_focus('AccountList');
	}
}
	
br(1);
start_table($table_style2);

if ($selected_account != "") 
{
	//editing an existing account
	$myrow = get_gl_account($selected_account);

	$_POST['account_code'] = $myrow["account_code"];
	$_POST['account_code2'] = $myrow["account_code2"];
	$_POST['account_name']	= $myrow["account_name"];
	$_POST['account_type'] = $myrow["account_type"];
 	$_POST['inactive'] = $myrow["inactive"];
 	
 	$tags_result = get_tags_associated_with_record(TAG_ACCOUNT, $selected_account);
 	$tagids = array();
 	while ($tag = db_fetch($tags_result)) 
 	 	$tagids[] = $tag['id'];
 	$_POST['account_tags'] = $tagids;

	hidden('account_code', $_POST['account_code']);
	hidden('selected_account', $selected_account);
		
	label_row(_("Account Code:"), $_POST['account_code']);
} 
else
{
	if (!isset($_POST['account_code'])) {
		$_POST['account_tags'] = array();
		$_POST['account_code'] = $_POST['account_code2'] = '';
		$_POST['account_name']	= $_POST['account_type'] = '';
 		$_POST['inactive'] = 0;
	}
	text_row_ex(_("Account Code:"), 'account_code', 11);
}

text_row_ex(_("Account Code 2:"), 'account_code2', 11);

text_row_ex(_("Account Name:"), 'account_name', 60);

gl_account_types_list_row(_("Account Group:"), 'account_type', null);

tag_list_row(_("Account Tags:"), 'account_tags', 5, TAG_ACCOUNT, true);

record_status_list_row(_("Account status:"), 'inactive');
end_table(1);

if ($selected_account == "") 
{
	submit_center('add', _("Add Account"), true, '', 'default');
} 
else 
{
    submit_center_first('update', _("Update Account"), '', 'default');
    submit_center_last('delete', _("Delete account"), '',true);
}
end_form();

end_page();

?>
