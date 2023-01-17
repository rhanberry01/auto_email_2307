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
$page_security = 'SA_STANDARDCOST';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/db/all_item_adjustments_db.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Inventory Item Cost Update"), false, false, "", $js);

//--------------------------------------------------------------------------------------

function insert_to_zzz_stock_id($stock_id)
{
	$sql = "INSERT INTO z_z_z_stock_idsss(stock_id) VALUES($stock_id)";
	db_query($sql,'failed to insert z_z_z_stock_idsss');
}

function update_zzz_stock_id($stock_id)
{
	$sql = "UPDATE z_z_z_stock_idsss SET done = 1 WHERE stock_id=$stock_id";
	db_query($sql,'failed to update z_z_z_stock_idsss');
}

if(isset($_POST['fix_now']))
{
	if ($_POST['stock_id'] == '*')
	{
		// get all undone
		$sql = "SELECT COUNT(*) FROM z_z_z_stock_idsss";
		$res = db_query($sql);
		$row = db_fetch($res);
		$count = $row[0];
		
		if ($count == 0) // wala pang record
		{
			$sql = "SELECT DISTINCT ProductID FROM ProductHistory
						WHERE CAST(DatePosted as DATE) >= '".date2sql($_POST['from'])."'";
		// $sql = "SELECT * FROM Products WHERE ProductID IN (146868,146900,2004804297,146857)";
			
			$res = ms_db_query($sql);
			while($row = mssql_fetch_array($res))
				// display_notification($row[0]);
				insert_to_zzz_stock_id($row[0]);
		}
		
		$sql = "SELECT stock_id FROM z_z_z_stock_idsss WHERE done = 0";
		$res = db_query($sql);
		$count = 0;
		while($row = db_fetch($res))
		{
			// display_notification($row[0]);
			recompute_item_cost_of_sales($row[0], $_POST['from']);
			update_zzz_stock_id($row['stock_id']);
			// display_notification('DONE : '.$row['stock_id']);
			$count ++;
		}
	}
	else
		recompute_item_cost_of_sales($_POST['stock_id'], $_POST['from']);
	
	display_notification("DONE : $count Item/s Updated");
}

start_form();
start_table($table_style2);
date_row('DATE from:', 'from');
text_row_ex('"Product ID" : ', 'stock_id','15');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();

?>
