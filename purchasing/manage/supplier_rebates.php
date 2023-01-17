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
$page_security = 'SA_SUPPLIER';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Manage Rebates"), false, false, "", $js);

include_once($path_to_root . "/includes/ui.inc");

//================================================================

function check_input()
{
	$input_error = false;
	
	if (!is_date($_POST['start_date']))
	{
		display_error("Invalid Start Date");
		set_focus('start_date');
		$input_error = true;
	}
	
	if (!is_date($_POST['end_date']))
	{
		display_error("Invalid End Date");
		set_focus('end_date');
		$input_error = true;
	}
	
	if (date1_greater_date2($_POST['start_date'],$_POST['end_date']))
	{
		display_error("Start Date must be less than End Date.");
		set_focus('start_date');
		$input_error = true;
	}
	
	if (!check_num('percentage',0,100) OR input_num('percentage') == 0)
	{
		display_error("Percentage must be greater than 0 and less than or equal to 100");
		$input_error = true;
	}
	
	if ($input_error == true)
		return false;
	else
		return true;
}

global $Ajax;

if (isset($_POST['add_rebate']))  //============================================ ADD
{
	if (check_input())
	{
	
		//------------------------------------ import suppliers
		$supp_ref = $_POST['supplier_id'];
		$_POST['supplier_id'] = check_my_suppliers($supp_ref);
		//---------------------------------------------------------
	
		$sql = "INSERT INTO ".TB_PREF."rebates(supplier_id, percentage, start_date, end_date)
					VALUES(".$_POST['supplier_id'].", ". input_num('percentage').", '". date2sql($_POST['start_date'])."', '". date2sql($_POST['end_date']) . "')";
		db_query($sql,'failed to insert rebate');
		
		display_notification('Added Rebate for '. get_supplier_name($_POST['supplier_id']));
		
		unset($_POST['percentage']);
		unset($_POST['start_date']);
		unset($_POST['end_date']);
		
		$Ajax->activate('tabless');
		$Ajax->activate('forms_');
	}
}

if (isset($_POST['update_rebate']))
{
	if (check_input())
	{
		$sql = "UPDATE ".TB_PREF."rebates SET
						start_date = '". date2sql($_POST['start_date'])."',
						end_date = '". date2sql($_POST['end_date'])."',
						percentage = ".input_num('percentage')."
					WHERE id = ". $_POST['rebate_id'];
		
		db_query($sql,'failed to update rebate');
	
		display_notification('Updated Rebate for '. get_supplier_name($_POST['supplier_id']));
		
		unset($_POST['percentage']);
		unset($_POST['start_date']);
		unset($_POST['end_date']);
		
		$Ajax->activate('tabless');
		$Ajax->activate('forms_');
	}
}

if (isset($_POST['delete_rebate']))
{
	$sql = "DELETE FROM ".TB_PREF."rebates WHERE id = ". $_POST['rebate_id'];
	db_query($sql);
	
	display_notification('Deleted Rebate for '. get_supplier_name($_POST['supplier_id']));
		
	unset($_POST['percentage']);
	unset($_POST['start_date']);
	unset($_POST['end_date']);
	
	$Ajax->activate('tabless');
	$Ajax->activate('forms_');
}

if (!isset($_POST['start_date']))
{
	// $date_ = explode_date_to_dmy(Today());
	
	$_POST['start_date'] = __date(date("Y"),1,1);
}
//================================================================

$id = find_submit('Edit');

if ($id != -1)
	$Ajax->activate('forms_');

start_form();

div_start('tabless'); // for the table

start_table($table_style2);

$sql = "SELECT * FROM ".TB_PREF."rebates";
$res = db_query($sql);

$th = array('Supplier', 'Start Date', 'End Date', 'Percentage','');

table_header($th);
$k = 0;
while($row= db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(get_supplier_name($row['supplier_id']));
	label_cell(sql2date($row['start_date']));
	label_cell(sql2date($row['end_date']));
	label_cell('<b>'.number_format2($row['percentage'],user_percent_dec()). ' %</b>', 'align=right');
	edit_button_cell("Edit".$row['id'], _("Edit"));
	end_row();
}

end_table();

div_end();

echo '<br>';

div_start('forms_');

if ($id != -1)
{
	$sql = "SELECT * FROM ".TB_PREF."rebates WHERE id = $id";
	$res_ = db_query($sql);
	$row_ = db_fetch($res_);
	
	$_POST['supplier_id'] = $row_['supplier_id'];
	$_POST['start_date'] = sql2date($row_['start_date']);
	$_POST['end_date'] = sql2date($row_['end_date']);
	$_POST['percentage'] = $row_['percentage'];

}

start_table($table_style2);

hidden('rebate_id',$id);

if ($id == -1)
	supplier_list_ms_row('Supplier:', 'supplier_id');
else
{
	hidden('supplier_id',$_POST['supplier_id']);
	label_row('Supplier:', get_supplier_name($_POST['supplier_id']));
}

date_row('Start Date : ', 'start_date', null, null, 0, 0, 1001);
date_row('End Date : ', 'end_date', null, null, 0, 0, 1001);
percent_row('Percentage : ', 'percentage');

end_table(1);

if ($id == -1)
	submit_center('add_rebate', 'Add Rebate', true, false, true);
else
{
	// submit_center('add_rebate', 'Update Rebate', true, false, true);
	submit_center_first('update_rebate', 'Update Rebate', false, true);
	submit_center_last('delete_rebate', 'Delete Rebate', false, true);
}
div_end();
end_form();

end_page();

?>
