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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager2.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Debit Memo Inquiry"), false, false, "", $js);

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('#/Reference :', 'dm_no');
		supplier_list_cells('Supplier :', 'supp_id', null, true);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		allyesno_list_cells('Status:','status', null, 'ALL', 'with CV', 'no CV yet');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();
	
$sql = "SELECT a.*, b.supp_name FROM ".TB_PREF."supp_trans a, ".TB_PREF."suppliers b
		WHERE type = 53
		AND a.supplier_id = b.supplier_id
		AND ov_amount < 0";
		
if($_POST['status'] == 0) // no CV
	$sql .= " AND cv_id = 0";
else if($_POST['status'] == 1) // with CV
		$sql .= " AND cv_id != 0";
		
if (trim($_POST['dm_no']) == '')
{
	$sql .= " AND tran_date >= '".date2sql($_POST['start_date'])."'
			  AND tran_date <= '".date2sql($_POST['end_date'])."'";
			  
	if ($_POST['supp_id'])
	{
		$sql .= " AND a.supplier_id = ".$_POST['supp_id'];
	}
}
else
{
	$sql .= " AND (reference LIKE ".db_escape('%'.$_POST['dm_no'].'%')." 
			  OR supp_reference LIKE ".db_escape('%'.$_POST['dm_no'].'%')." )";
}
$sql .= " ORDER BY tran_date";
$res = db_query($sql);
// display_error($sql);

start_table($table_style2.' width=90%');
$th = array();

if ($_POST['status'] != 0)
	$th[] = 'CV #';
	
array_push($th,'#', 'Date', 'Supplier', 'Reference', 'Remarks', 'Amount');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$type = 53;
$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	if ($_POST['status'] != 0 AND $row['cv_id'] != 0)
		label_cell(get_cv_view_str($row['cv_id']));
	else if ($_POST['status'] != 0 AND $row['cv_id'] == 0)
		label_cell('');
		
	label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell(sql2date($row['tran_date']));
	label_cell($row['supp_name'] ,'nowrap');
	label_cell($row['supp_reference']);
	label_cell(get_comments_string($type, $row['trans_no']));
	amount_cell(-$row['ov_amount'],true);
	

	end_row();
}

end_table();
div_end();
end_form();



end_page();

?>
