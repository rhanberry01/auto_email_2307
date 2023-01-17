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
$page_security = 'SA_SUPPLIERINVOICE';
$path_to_root = "..";

set_time_limit(0);
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");


include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Change Reference Number"), false, false, "", $js);
//---------------------------------------------------------------------------------------------------------------

if (isset($_POST['search']))
{
	global $Ajax;
	$Ajax->activate('result');
}

$update_id = find_submit('update');
if($update_id != -1)
{
	global $Ajax;
	$sql = "UPDATE ".TB_PREF."supp_trans SET 
				supp_reference = ".db_escape($_POST['new_ref'.$update_id])."
			WHERE id = $update_id";
	db_query($sql);
	
	$_POST['new_ref'.$update_id] = '';
	
	display_notification('Transaction Updated.');
	$Ajax->activate('result');
}

start_form();
div_start('header');
start_table();
	get_supp_trans_type_list_cells('Transaction Type:','tran_type');
	ref_cells('Transaction #:','reference');
	submit_cells('search', 'Search', "", false, true);
end_table(2);
div_end();

div_start('result');
if ($_POST['reference'] == '')
{
	display_footer_exit();
}
	
	
global $systypes_array;
$sql = "SELECT * FROM ".TB_PREF."supp_trans
		WHERE type = ".$_POST['tran_type']."
		AND reference LIKE ('%".$_POST['reference']."%')";
$res = db_query($sql);

if (db_num_rows($res) == 0)
{
	display_error('No transaction found');
	display_footer_exit();
}

start_table($table_style2);
$th = array('Type','#', 'Reference #','New Reference #', '');
table_header($th);

$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell($systypes_array[$row['type']]);
	label_cell($row['reference']);
	label_cell($row['supp_reference']);
	text_cells('', 'new_ref'.$row['id']);
	submit_cells('update'.$row['id'], 'Update', "", false, true);
	end_row();
}

end_table();
div_end();
end_form();
end_page();
?>