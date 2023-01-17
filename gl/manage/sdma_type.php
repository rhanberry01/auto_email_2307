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

page(_($help_context = "Supplier Debit Memo Agreement Types"));

include($path_to_root . "/gl/includes/gl_db.inc");

include($path_to_root . "/includes/ui.inc");

simple_page_mode(true);
//-------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$input_error = 0;

	if (strlen($_POST['type_name']) == 0) 
	{
		$input_error = 1;
		display_error( _("Type Name must be entered."));
		set_focus('type_name');
	} 

	if ($_POST['debit_account'] == '') 
	{
		$input_error = 1;
		display_error( _("Please Choose a debit account."));
		set_focus('debit_account');
	} 
	
	if ($_POST['credit_account'] == '') 
	{
		$input_error = 1;
		display_error( _("Please Choose a Promo Fund credit account."));
		set_focus('credit_account');
	}

	// if ($_POST['oi_credit_account'] == '') 
	// {
		// $input_error = 1;
		// display_error( _("Please Choose an Other Income credit account."));
		// set_focus('oi_credit_account');
	// }

	// if ($_POST['output_vat_account'] != '' AND input_num('output_vat_percent') <= 0)
	// {
		// $input_error = 1;
		// display_error( _("Output VAT Percent should be greater than 0."));
		// set_focus('output_vat_percent');
	// }

	if ($input_error != 1)
	{
		if ($_POST['output_vat_account'] == '')
			$_POST['output_vat_percent'] = 0;
			
		if ($_POST['credit_tax_account'] == '')
			$_POST['credit_tax_percent'] = 0;
			
    	if ($selected_id != -1) 
    	{
			global $db_connections;
			begin_transaction();
			foreach($db_connections as $key=>$db_con)
			{
				$sql = "UPDATE ".$db_con['dbname'].'.'.$db_con['tbpref']."sdma_type SET
								type_name=" . db_escape($_POST['type_name']) . ",
								debit_account=" . db_escape($_POST['debit_account']) . ",
								credit_account=" . db_escape($_POST['credit_account']) . ",
								oi_credit_account=" . db_escape($_POST['oi_credit_account']) . ",
								credit_tax_account=" . db_escape($_POST['credit_tax_account']) . ",
								credit_tax_percent=" . input_num('credit_tax_percent') . ",
								output_vat_account=" . db_escape($_POST['output_vat_account']) . ",
								output_vat_percent=" . input_num('output_vat_percent') . "
							WHERE id= " .db_escape( $selected_id );
			
				
				db_query($sql,"The sdma type could not be updated");
			}
			commit_transaction();
 			$note = _('Selected sdma type has been updated on all branches');
    	} 
    	else 
    	{
			global $db_connections;
			begin_transaction();
			foreach($db_connections as $key=>$db_con)
			{
				$sql = "INSERT INTO ".$db_con['dbname'].'.'.$db_con['tbpref']."sdma_type (type_name, debit_account, credit_account,credit_tax_account, credit_tax_percent,
					oi_credit_account,output_vat_account,output_vat_percent)
					VALUES (" . db_escape($_POST['type_name']) .',' . db_escape($_POST['debit_account']) . ','
						. db_escape($_POST['credit_account']) . ",". db_escape($_POST['credit_tax_account']) . ",". input_num('credit_tax_percent') . "," 
						. db_escape($_POST['oi_credit_account']) . "," . db_escape($_POST['output_vat_account']) . ",". input_num('output_vat_percent'). ")";
				db_query($sql,"The sdma type could not be updated");
			}
			commit_transaction();
			$note = _('New sdma type has been added on all branches');
    	}
		
		unset($_POST);
    	//run the sql from either of the above possibilites
		
		
		display_notification($note);
 		$Mode = 'RESET';
	}
}

if ($Mode == 'Delete')
{
	$sql = "SELECT * FROM ".TB_PREF."sdma WHERE sdma_type =".db_escape($selected_id) ." LIMIT 1";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
	{
		$sql="DELETE FROM ".TB_PREF."sdma_type WHERE id=".db_escape($selected_id);
		db_query($sql,"could not delete a sdma type");
		display_notification(_('Selected sdma type has been deleted'));

		$Mode = 'RESET';
	}
	else
		display_error("can't delete type because DM Agreement/s use this type.");
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = check_value('show_inactive');
	unset($_POST['show_inactive']);
	// unset($_POST);
	// $_POST['show_inactive'] = $sav;
}
//-------------------------------------------------------------------------------------------------

$sql = "SELECT * FROM ".TB_PREF."sdma_type";
if (!check_value('show_inactive')) $sql .= " WHERE !inactive";
$result = db_query($sql,"could not get sdma types");

start_form();
start_table('width=85% ' .$table_style2);
$th = array(_("Type"), "Promo Fund<br> Debit Account","Promo Fund <br> Credit Account" ,
	"Promo Fund <br> Credit Tax Account" ,"Credit Tax %" ,"Other Income <br> Credit Account",
	'Output VAT <br> Account', 'Output VAT %', "", "");
inactive_control_column($th);
table_header($th);

$k = 0; //row colour counter
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);

    label_cell('<b>'.$myrow["type_name"].'</b>');
    label_cell(get_gl_account_name($myrow['debit_account']),'');
    label_cell(get_gl_account_name($myrow['credit_account']));

	label_cell($myrow['credit_tax_account'] ? get_gl_account_name($myrow['credit_tax_account']) : '');
    label_cell($myrow['credit_tax_percent'] == 0 ? '' : $myrow['credit_tax_percent'].'%');
	
	label_cell($myrow['oi_credit_account'] ? get_gl_account_name($myrow['oi_credit_account']) : '');
    label_cell($myrow['output_vat_account'] == '' ? 'N/A' : get_gl_account_name($myrow['output_vat_account']));
    label_cell($myrow['output_vat_percent'] == 0 ? '' : $myrow['output_vat_percent'].'%');
		
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'sdma_type', "id");
	edit_button_cell("Edit".$myrow["id"], _("Edit"));

	// if ($myrow["id"] != 1) //rebate
		delete_button_cell("Delete".$myrow["id"], _("Delete"));
	// else
		// label_cell('');
    end_row();
	
} //END WHILE LIST LOOP

inactive_control_row($th);
end_table(1);

//-------------------------------------------------------------------------------------------------

start_table($table_style2);

$day_in_following_month = $days_before_due = 0;
if ($selected_id != -1) 
{
	if ($Mode == 'Edit') {
		//editing an existing payment terms
		$sql = "SELECT * FROM ".TB_PREF."sdma_type
			WHERE id=".db_escape($selected_id);

		$result = db_query($sql,"could not get sdma type");
		$myrow = db_fetch($result);

		$_POST['type_name']  = $myrow["type_name"];
		$_POST['debit_account']  = $myrow["debit_account"];
		$_POST['credit_account']  = $myrow["credit_account"];
		$_POST['oi_credit_account']  = $myrow["oi_credit_account"];
		$_POST['credit_tax_account']  = $myrow["credit_tax_account"];
		$_POST['credit_tax_percent']  = $myrow["credit_tax_percent"];
		$_POST['output_vat_account']  = $myrow["output_vat_account"];
		$_POST['output_vat_percent']  = $myrow["output_vat_percent"];

	}
	hidden('selected_id', $selected_id);
}

if (!isset($_POST['debit_account']))
{
	$_POST['debit_account'] = get_company_pref('creditors_act');
}

// if (!isset($_POST['output_vat_account']))
		$_POST['output_vat_account'] = 2310;
	
text_row_ex(_("Type Name").':', 'type_name', 50, 200);
gl_all_accounts_list_row('Promo Fund Debit Account:', 'debit_account', null, true, false, '');
gl_all_accounts_list_row('Promo Fund Credit Account:', 'credit_account', null, true, false, '');
gl_all_accounts_list_row('Promo Fund Credit Input Tax Account (automatically computed from whole amount) :', 'credit_tax_account', null, true, false, '');
percent_row('Credit Input Tax Percent (automatically computed from whole amount) :', 'credit_tax_percent');
start_row();echo '<td colspan=2><hr></td>';end_row();
gl_all_accounts_list_row('Other Income Credit Account:', 'oi_credit_account', null, true, false, '');
gl_all_accounts_list_row('Output VAT Account:', 'output_vat_account', null, true, false, '');
percent_row('Output VAT Percent:', 'output_vat_percent');

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
?>