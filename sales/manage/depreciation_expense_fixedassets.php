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
	
page(_($help_context = "Depreciation Expense (Fixed Assets)"), false, false, "", $js);

//----------------------------------------------------------------------------------

function write_depreciation_expense($selected,$date_acquired,$apv_num,$asset_name,$supplier,$invoice_num,$branch,$life,$expected_life_date,$asset_num,$serial_num,$asset_gl,$dep_exp_gl,$accum_dep_gl,$acquisition_cost)
{
    if($selected!='')
		$sql = "UPDATE ".TB_PREF."dep_exp_fixed_assets SET
		date_acquired = ".db_escape($date_acquired).",
		apv_num = ".db_escape($apv_num).",
		asset_name = ".db_escape($asset_name).",
		supplier = ".db_escape($supplier).",
		invoice_num = ".db_escape($invoice_num).",
		branch = ".db_escape($branch).",
		life = ".db_escape($life).",
		expected_life_date = ".db_escape($expected_life_date).",
		asset_num = ".db_escape($asset_num).",
		serial_num = ".db_escape($serial_num).",
		asset_gl_type= ".db_escape($asset_gl).",
		dep_expense_gl_type = ".db_escape($dep_exp_gl).",
		accum_dep_gl_type=".db_escape($accum_dep_gl).",
		acquisition_cost = ".db_escape($acquisition_cost)."
        WHERE dep_id = ".db_escape($selected);
    else
		$sql = "INSERT INTO ".TB_PREF."dep_exp_fixed_assets
				(date_acquired,apv_num,asset_name,supplier,invoice_num,branch,life,expected_life_date,asset_num,serial_num,asset_gl_type,dep_expense_gl_type,accum_dep_gl_type,acquisition_cost) 
			VALUES(".db_escape($date_acquired).",".db_escape($apv_num).",".db_escape($asset_name).",".db_escape($supplier).",".db_escape($invoice_num).",
			".db_escape($branch).",".db_escape($life).",".db_escape($expected_life_date).",".db_escape($asset_num).",".db_escape($serial_num).",".db_escape($asset_gl).",
			".db_escape($dep_exp_gl).",".db_escape($accum_dep_gl).",".db_escape($acquisition_cost).")";

	//display_error($sql);
	db_query($sql,"Acquiring Bank could not be updated");
}

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

		if ($_POST['date_acquired'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose date aqcuired."));
		set_focus('date_acquired');
	}
	
			// if ($_POST['apv_num'] == '')
	// {
		// $input_error = 1;
		// display_error(_("APV # cannot be empty."));
		// set_focus('apv_num');
	// }
	
		if ($_POST['asset_name'] == '')
	{
		$input_error = 1;
		display_error(_("Asset Name cannot be empty."));
		set_focus('asset_name');
	}
	
			if ($_POST['supplier'] == '')
	{
		$input_error = 1;
		display_error(_("Supplier cannot be empty."));
		set_focus('supplier');
	}
	
			if ($_POST['invoice_num'] == '')
	{
		$input_error = 1;
		display_error(_("Invoice Number cannot be empty."));
		set_focus('invoice_num');
	}
	
			if ($_POST['life'] == '')
	{
		$input_error = 1;
		display_error(_("Life cannot be empty."));
		set_focus('life');
	}
	
	
		if (date1_greater_date2($_POST['date_acquired'],$_POST['expected_life_date']))
	{
		$input_error = 1;
		display_error(_("Expected Life End Date must be greater than Date Acquired."));
		set_focus('expected_life_date');
	}
	
			if ($_POST['expected_life_date'] == '')
	{
		$input_error = 1;
		display_error(_("Expected Life End Date cannot be empty."));
		set_focus('life');
	}
	
	
	
			if ($_POST['acquisition_cost'] == '')
	{
		$input_error = 1;
		display_error(_("Acquisition Cost cannot be empty."));
		set_focus('acquisition_cost');
	}
	
			if ($_POST['asset_num'] == '')
	{
		$input_error = 1;
		display_error(_("Asset Number cannot be empty."));
		set_focus('asset_num');
	}
	
			if ($_POST['serial_num'] == '')
	{
		$input_error = 1;
		display_error(_("Serial Number cannot be empty."));
		set_focus('serial_num');
	}
	
			if ($_POST['asset_gl'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose Asset GL account."));
		set_focus('asset_gl');
	}
	
			if ($_POST['dep_exp_gl'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose Depreciation Expense GL account."));
		set_focus('dep_exp_gl');
	}
	
		if ($_POST['accum_dep_gl'] == '')
	{
		$input_error = 1;
		display_error(_("Please choose Accumulated Depricaition GL account."));
		set_focus('accum_dep_gl');
	}
	
	
	if ($_POST['acquisition_cost'] <= 0)
	{
		$input_error = 1;
		display_error(_("Acquisition Cost amount cannot be zero. "));
		set_focus('acquisition_cost');
	}
	
		if (!is_numeric(input_num('acquisition_cost')))
	{
		$input_error = 1;
		display_error(_("Acquisition Cost amount you entered is not numeric. "));
		set_focus('acquisition_cost');
	}

	if ($input_error !=1) {
		write_depreciation_expense($selected_id,date2sql($_POST['date_acquired']),$_POST['apv_num'],$_POST['asset_name'],$_POST['supplier'],$_POST['invoice_num'],
		$_POST['branch'],$_POST['life'],date2sql($_POST['expected_life_date']),$_POST['asset_num'],$_POST['serial_num'],$_POST['asset_gl'],$_POST['dep_exp_gl'],$_POST['accum_dep_gl'],
		input_num('acquisition_cost'));
		if($selected_id != '')
			display_notification(_('Selected Asset has been updated'));
		else
			display_notification(_('New Asset has been added'));
		$Mode = 'RESET';
	}
	
}

//---------------------START OF DELETE--------------------------------

if ($Mode == 'Delete')
{
	delete_item_unit($selected_id);
	$sql_del="delete from ".TB_PREF."dep_exp_fixed_assets where dep_id=".db_escape($selected_id)."";
	//display_error($sql_del);
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



start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	fixed_assets_list_cells(_('Fixed Asset Account:'), 'account', null,true); 
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);

if ($_POST['account']!='')
{

$result = get_depreciation_expenses(check_value('show_inactive'),$_POST['account']);

start_table("$table_style width=95%");
$th = array(_('Date Acquired'),_('APV #'),_('Asset Description'), _('Supplier'),_('Invoice #'),_('Acquisition Cost'), _('Life'),_('Expected Life End Date'),_('Monthly Depreciation'),'Asset #',' Serial #','Fixed Asset Type',"","");
//inactive_control_column($th);

table_header($th);
$k = 0; //row colour counter

$counter=mysql_num_rows($result);

if ($counter=='0') {
display_error("No Asset Found on this Account.");
}

while ($myrow = db_fetch($result))
{

	alt_table_row_color($k);
	//$months_life=date_diff2(sql2date($myrow["expected_life_date"]),sql2date($myrow["date_acquired"]),"m");
	$months_life=$myrow["life"]*12;
	$monthly_depreciation=$myrow["acquisition_cost"]/$months_life;
	label_cell($myrow["date_acquired"],'nowrap');
	label_cell($myrow["apv_num"],'nowrap');
	label_cell($myrow["asset_name"],'nowrap');
	label_cell($myrow["supplier"],'nowrap');
	label_cell($myrow["invoice_num"],'nowrap');
	amount_cell($myrow["acquisition_cost"],'nowrap');
	//label_cell($myrow["branch"],'nowrap');
	label_cell($myrow["life"]." (Yrs.)",'nowrap');
	label_cell($myrow["expected_life_date"],'nowrap');
	amount_cell($monthly_depreciation,'nowrap');
	//label_cell($myrow["expected_life_date"]." (".$months_life." yrs.)",'nowrap');
	label_cell($myrow["asset_num"],'nowrap');
	label_cell($myrow["serial_num"],'nowrap');
	//label_cell(date_diff2(sql2date($myrow["expected_life_date"]),sql2date($myrow["date_acquired"]), "m"));
	label_cell(get_gl_account_name($myrow["asset_gl_type"]),'nowrap');
	//inactive_control_cell($myrow["dep_id"], $myrow["inactive"], 'dep_exp_fixed_assets', 'dep_id');
 	edit_button_cell("Edit".$myrow["dep_id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["dep_id"], _("Delete"));
	end_row();
}


inactive_control_row($th);
end_table(1);
}
//------------------------END OF DISPLAYING DATA TO TABLE-------------------------------------------

if ($_POST['apv_num']!=null)
{
global $Ajax;
$apv=$_POST['apv_num'];
// $sqldet = "SELECT * FROM ".TB_PREF."dep_exp_fixed_assets WHERE dep_id=".$selected_id."";
$sqldet="select st.reference as apv, st.supp_reference as invoice_number,st.tran_date as date_acq, s.supp_name as supp_name from ".TB_PREF."supp_trans as st
left join ".TB_PREF."suppliers as s
on st.supplier_id=s.supplier_id
where reference='".$apv."'";
$resultdet= db_query($sqldet);
$count_result=db_num_rows($resultdet);
while($detrow=db_fetch($resultdet)) //for displaying data to edit
{
$_POST['date_acquired'] = sql2date($detrow["date_acq"]);
$sup_reference = $detrow['invoice_number'];
$supp_name = $detrow['supp_name'];
}
if ($count_result==0 or $count_result=='')
{
display_error("Entered APV# has no result.");
$sup_reference='';
$supp_name='';
$_POST['date_acquired']='';
}
$Ajax->activate('detail_tbl');
}

//-------------------------EDIT FORM QUERY---------------------------------------
div_start('detail_tbl');
start_table($table_style2);
if ($selected_id != '') 
{
 	if ($Mode == 'Edit') {
		//editing an existing item category

		// $result2 = edit_display($selected_id); //FROM sales/includes/sales_db.inc
		
	$sql = "SELECT * FROM ".TB_PREF."dep_exp_fixed_assets WHERE dep_id=".$selected_id."";
	$result2= db_query($sql);
		
while ($myrow2 = db_fetch($result2)) //for displaying data to edit
{
		$_POST['date_acquired'] = sql2date($myrow2["date_acquired"]);
		$_POST['apv_num'] = $myrow2["apv_num"];
		$_POST['asset_name'] = $myrow2["asset_name"];
		$_POST['supplier'] = $myrow2["supplier"];
		$_POST['invoice_num'] = $myrow2["invoice_num"];
		$_POST['acquisition_cost']  = $myrow2["acquisition_cost"];
		//$_POST['branch']  = $myrow2["branch"];
		$_POST['life']  =  $myrow2["life"];
		$_POST['expected_life_date']  =  sql2date($myrow2["expected_life_date"]);
		$_POST['asset_num']  = $myrow2["asset_num"];
		$_POST['serial_num']  = $myrow2["serial_num"];
		$_POST['asset_gl']  = $myrow2["asset_gl_type"];
		$_POST['dep_exp_gl']  = $myrow2["dep_expense_gl_type"];
		$_POST['accum_dep_gl']  = $myrow2["accum_dep_gl_type"];
		}
	}
	hidden('selected_id', $selected_id);
}

//FORM

text_cells_ex(_("APV #:"), 'apv_num','','','','','','',true);
date_row(_("Date Acquired:"), 'date_acquired');
text_row(_("Asset Description:"), 'asset_name');
text_row(_("Supplier:"), 'supplier',$supp_name,52);
text_row(_("Invoice Number:"), 'invoice_num',$sup_reference);
amount_row(_("Acquisition Cost (Vat Exclusive):"), 'acquisition_cost');
hidden('branch',null);
text_row(_("Useful Life (Yrs.):"), 'life');
date_row(_("Expected Life End Date:"), 'expected_life_date');
text_row(_("Asset Number:"), 'asset_num');
text_row(_("Serial Number:"), 'serial_num');
//gl_all_accounts_list_row("Fixed Asset Type:", 'asset_gl', null, false, false, 'Choose an account');
fixed_assets_list_row(_('Fixed Asset Type:'), 'asset_gl', null,true); 
gl_all_accounts_list_row("Depreciation Expense GL Account:", 'dep_exp_gl', null, false, false, 'Choose an account');
//gl_all_accounts_list_row("Accumulated Depreciation GL Account:", 'accum_dep_gl', null, false, false, 'Choose an account');
fixed_assets_list_row(_('Accumulated Depreciation GL Account:'), 'accum_dep_gl', null,true); 
end_table(1);
submit_add_or_update_center($selected_id == '', '', 'both');
div_end();
end_form();
end_page();
?>