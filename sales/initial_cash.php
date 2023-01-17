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
//-----------------------------------------------------------------------------
//
//	Entry/Modify Sales Quotations
//	Entry/Modify Sales Order
//	Entry Direct Delivery
//	Entry Direct Invoice
//

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
$js = '';

if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}
page('Initial Cash', false, false, "", $js);

global $Ajax;

start_form();
if (isset($_POST['add_initial_cash']))
{
	//=========== cashier
	$user_id = $_POST['cashier_username'];
	$password = $_POST['cashier_pass'];
	
	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id = ".db_escape($user_id)." AND"
		." password=".db_escape(md5($password));
	$res = db_query($sql, "could not get validate cashier login for $user_id");
	
	if (db_num_rows($res) == 0)
	{
		display_error('Invalid user OR Wrong Password (Cashier)');
		set_focus($_POST['cashier_username']);
		$_POST['cashier_username'] = $_POST['cashier_password'] = $_POST['treasurer_username'] = $_POST['cashier_password'] = '';
		return false;
	}
	
	$row = db_fetch($res);
	$cashier_id = $row['id'];
	//==================
	
	//=========== treasurer
	$user_id = $_POST['treasurer_username'];
	$password = $_POST['treasurer_pass'];
	
	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id = ".db_escape($user_id)." AND"
		." password=".db_escape(md5($password));
	$res = db_query($sql, "could not get validate treasurer login for $user_id");
	
	if (db_num_rows($res) == 0)
	{
		display_error('Invalid user OR Wrong Password (Treasurer)');
		set_focus($_POST['treasurer_username']);
		$_POST['cashier_username'] = $_POST['cashier_password'] = $_POST['treasurer_username'] = $_POST['cashier_password'] = '';
		return false;
	}
	
	$row = db_fetch($res);
	$treasurer_id = $row['id'];
	//==================
	
	if (input_num('cash') < 0)
	{
		display_error('Initial Cash is less than 0');
		set_focus($_POST['cash']);
		$_POST['cashier_username'] = $_POST['cashier_password'] = $_POST['treasurer_username'] = $_POST['cashier_password'] = '';
		return false;
	}
	
	if ($_POST['edit_id'] == -1)
	{
		$sql = "INSERT INTO ".TB_PREF."initial_cash(cashier_id, treasurer_id, i_date, amount)
					 VALUES ($cashier_id, $treasurer_id, '".date2sql($_POST['date_'])."', ".input_num('cash').")";

		db_query($sql, 'failed to insert initial cash');
		display_notification('Initial Cash Added');
	}
	else
	{
		$sql = "SELECT cashier_id FROM ".TB_PREF."initial_cash WHERE id = ".$_POST['edit_id'];
		$res = db_query($sql);
		$row = db_fetch($res);
		$old_c_id = $row[0];
		
		if ($cashier_id != $old_c_id)
		{
			display_error('This is a different Cashier!');
			set_focus($_POST['cashier_username']);
			$_POST['cashier_username'] = $_POST['cashier_password'] = $_POST['treasurer_username'] = $_POST['cashier_password'] = '';
			return false;
		}
		
		$sql = "UPDATE ".TB_PREF."initial_cash SET amount = ".input_num('cash') ." WHERE id = ".$_POST['edit_id'];
		db_query($sql, 'failed to update initial cash');
		
		display_notification('Initial Cash Updated');
	}
	
	$_POST['cash'] = $_POST['cashier_username'] = $_POST['cashier_password'] = $_POST['treasurer_username'] = $_POST['cashier_password'] = '';
	
	$Ajax->activate('table_');
}


if (isset($_POST['date_']))
{
	global $Ajax;
	$Ajax->activate('table_');
}

$edit_id = find_submit('Edit');

if (isset($_POST['cancel_edit']))
{
	$edit_id = -1;
	$_POST['cash'] = $_POST['cashier_username'] = $_POST['cashier_password'] = $_POST['treasurer_username'] = $_POST['cashier_password'] = '';
}

echo '<center>';
date_row('Date:', 'date_', null, null, 0, 0, 0, null, true);
echo '</center><br>';

div_start('table_');
$sql = "SELECT * FROM ".TB_PREF."initial_cash 
			WHERE i_date = '".date2sql($_POST['date_'])."'";
$res = db_query($sql);

start_table("width=30% ".$table_style2);

$th = array('Cashier', 'Amount','');
table_header($th);

$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(get_username_by_id($row['cashier_id']));
	amount_cell($row['amount']);
	edit_button_cell("Edit".$row['id'], _("Edit"));
	end_row();
}
end_table(2);

if ($edit_id != -1)
{
	$sql = "SELECT * FROM ".TB_PREF."initial_cash 
			WHERE id = $edit_id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$_POST['cash'] = number_format2($row['amount']);
	$_POST['cashier_username'] = $_POST['cashier_password'] = $_POST['treasurer_username'] = $_POST['cashier_password'] = '';
	
}

hidden('edit_id',$edit_id);
start_outer_table($table_style2);
table_section(1);
text_row('<b>Cashier Username :</b>','cashier_username');
password_row('<b>Cashier Password :</b>', 'cashier_pass');
table_section(2);
amount_row('Initial Cash : ', 'cash');
start_row();
if ($edit_id == -1)
	submit_cells('add_initial_cash', 'Add Initial Cash', 'colspan=2 align=center',false,true);
else
{
	submit_cells('add_initial_cash', 'Edit Initial Cash', 'align=center',false,true);
	submit_cells('cancel_edit', 'Cancel Edit', 'align=center',false,true);
}
end_row();
table_section(3);
text_row('<b>Treasurer Username :</b>','treasurer_username');
password_row('<b>Treasurer Password :</b>', 'treasurer_pass');
end_outer_table(2);

div_end();

end_form();
end_page();

?>