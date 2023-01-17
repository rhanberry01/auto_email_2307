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
	
page(_($help_context = "Free Items Received"), false, false, "", $js);

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = begin_month(Today());

start_table();
	start_row();
		supplier_list_ms_cells('Supplier :', 'supp_id', null, true);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();
	
$sql = "SELECT  a.ReceivingID, a. ReceivingNo, a.DateReceived, a.Description as supplier_name, b.Description as item, b.UOM, b.qty
			FROM ROF a, ROFLine b
			WHERE a.ReceivingID = b.ReceivingID";
		
if($_POST['supp_id'] != '') 
	$sql .= " AND a.VendorCode = ". db_escape($_POST['supp_id']);


$sql .= " AND a.DateReceived >= '".date2sql($_POST['start_date'])."'
			  AND a.DateReceived <= '".date2sql($_POST['end_date'])."'";
			  
$sql .= " ORDER BY DateReceived, a.Description, b.Description";
$res = ms_db_query($sql);
// display_error($sql);

start_table($table_style2.' width=90%');

$th = array ('#', 'Date', 'Supplier', 'Item', 'UOM', 'QTY');


if (mssql_num_rows($res) > 0)
	table_header($th);
else 
{
	display_heading('No transactions found');
	display_footer_exit();
}

$k = 0;
while($row = mssql_fetch_array($res))
{
	alt_table_row_color($k);

	label_cell($row['ReceivingID']);
	label_cell(date('m/d/Y',strtotime($row['DateReceived'])));
	label_cell($row['supplier_name'] ,'nowrap');
	label_cell($row['item']);
	label_cell($row['UOM']);
	label_cell($row['qty']);
	
	end_row();
}

end_table();
div_end();
end_form();

end_page();

?>
