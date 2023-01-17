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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/db/all_item_adjustments_db.inc");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Inventory Gain and Loss Running Total"), false, false, "", $js);


start_form();
div_start('header');

$type = ST_INVADJUST;

function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM transfers.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

#extended total function
function get_stock_moves_total_by_branch($trans_no, $type, $branch_id) {
	$b = get_branch_by_id($branch_id); 
	$sql = "SELECT SUM(standard_cost * qty * multiplier) as total
			FROM ".$b['aria_db'].".".TB_PREF."stock_moves
			WHERE trans_no =".$trans_no." and type=".$type." ";
	$result = db_query($sql, 'failed to retrieve records from stock_moves');
	$row = db_fetch($result);
	return $row['total'];
}

start_table();
	start_row();
		ref_cells('Transaction #:', 'trans_no');
		yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();


div_start('dm_list');
// if (!isset($_POST['search']) or ($approve_add != -1 and $posted_add!= -1))
// 	display_footer_exit();
	
$bSql = "SELECT * FROM transfers.0_branches";
$bQuery = db_query($bSql);

$running_total = array();

while ($bRow = db_fetch($bQuery)) {
	
	$temp_total = 0;

	$sql = "SELECT b.name, a.* FROM ".$bRow['aria_db'].".".TB_PREF."adjustment_header  a, ".$bRow['aria_db'].".".TB_PREF."movement_types b
	WHERE a.a_movement_code = b.movement_code";
		
	if ($_POST['start_date'])
	{
		$sql .= " AND a.a_date_created >= '".date2sql($_POST['start_date'])."'
				  AND a.a_date_created<= '".date2sql($_POST['end_date'])."'";	
	}

	if ($_POST['trans_no'])
	{
	$sql .= " AND a.a_movement_no LIKE '%". $_POST['trans_no'] . "%'";	

	}

	$sql .= " AND a.a_movement_code IN ('IGSA', 'IGNSA', 'IGBO', 'IGNBO')";

	if ($_POST['status_type']==1)
	{
	//Open
	$stats='1' ;
	}
	else {
	//Posted
	$stats='2';
	}

	$sql .= " AND a.a_status = '$stats'";	

	$sql .= " ORDER BY a.a_date_created";
	$res = db_query($sql);

	$running_total[$bRow['name']] = 0;
	if (mysql_num_rows($res) > 0) {
		while ($row = mysql_fetch_assoc($res)) {
			$temp_total = get_stock_moves_total_by_branch($row['a_trans_no'], $type, $bRow['id']);
			$running_total[$bRow['name']] += $temp_total;
		}
	}
	else {
		$running_total[$bRow['name']] = 0; 
	}
}
// echo "<pre>";
// print_r($running_total);

start_table($table_style2.' width=90%');

if ($_POST['status_type'] == 0)
	echo "<h1>Posted (Approved)</h1>";
else 
	echo "<h1>Open (For Approval)</h1>";

$th_total = array('BRANCH', 'RUNNING TOTAL');
table_header($th_total);
$j = 0;
$grand_total = 0;
foreach ($running_total as $branch_name => $branch_total) {
	$grand_total += $branch_total;
	alt_table_row_color($j);
	label_cell($branch_name.':', 'align=right');
	label_cell(number_format2($branch_total, 2), 'align=right');
	end_row();
}
start_row();
label_cell('<font color=#880000><b>'.'OVERALL TOTAL:'.'</b></font>', 'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($grand_total), 2)."<b></font>",'align=right');
end_row();
end_table();
br();
br();

div_end();

end_form();
end_page();
?>