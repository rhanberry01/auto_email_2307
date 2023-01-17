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
	
page(_($help_context = "Running Total (Approve Disposal)"), false, false, "", $js);


function get_rs_items_total($rs_id, $branch_id)
{	
	$b = get_branch_by_id($branch_id);
	$sql = "SELECT * FROM centralized_returned_merchandise.".TB_PREF."rms_items
			WHERE rs_id=$rs_id
			AND branch_id='".$branch_id."'";
	$res = db_query($sql);

	$total = 0;
	while($row = mysql_fetch_array($res)){
		$total += round2($row['qty']*$row['price'],3);
	}
	
	return $total;
}

function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM centralized_returned_merchandise.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

if ($db_connections[$_SESSION["wa_current_user"]->company]["name"] != 'San Roque Supermarket - NOVA') {
	display_error('<h2>'."YOU CAN ONLY ACCESS THIS PAGE WHEN YOU'RE LOGGED IN FROM NOVALICHES BRANCH. ".'</br>'."Please logout and login to novaliches branch in order to proceed. Thank you!".'</h2>');
	exit;
}

$_POST['search'] = 

start_form();
div_start('header');

start_table();
// ref_cells('#: ','rs_id', null, null, null, true);
supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
date_cells('Date From: ', 'rs_date_from', '', null, -30);
date_cells('Date To: ', 'rs_date_to');
yesno_list_cells(_("Status Type:"), 'status_type', '',_("For Approval"), _("Approved"));
submit_cells('search', 'Search', "", false, false);
end_table(2);
div_end();
div_end();

div_start('dm_list');

$branches_sql = "SELECT * FROM centralized_returned_merchandise.0_branches";
$branches_query = db_query($branches_sql);

$running_total = array();
while ($branches_res = db_fetch($branches_query)) {	
	
	$running_total[$branches_res['name']] = 0;

	$sql = "SELECT SUM(items_total) as items_total FROM centralized_returned_merchandise.".TB_PREF."rms_header";

	if ($_POST['rs_id'] == '')
	{
		$sql .= " WHERE rs_date >= '".date2sql($_POST['rs_date_from'])."'
				  AND rs_date <= '".date2sql($_POST['rs_date_to'])."'";
				  
		if ($_POST['supplier_code'] != '')
			$sql .= " AND supplier_code = ".db_escape($_POST['supplier_code']);
			
		if ($_POST['status_type']==1)
		{
		//Open
		$stats='0' ;
		$sql .= "  AND movement_no = 0";	
		}
		else {
		//Posted
		$stats='2';
		$sql .= "  AND movement_no!= ''";	
		}

		if ($_POST['status_type']!='')
		{
			if ($stats == 0) {
				$sql .= "  AND (approved = '$stats' OR approved = '1')";	
			}
			else {
				$sql .= "  AND approved = '$stats'";	
			}
		}
					
		$sql .= " AND movement_type='FDFB' AND processed = '1'";		
			
	}
	else		  
	{
		$sql .= " WHERE (rs_id = ".$_POST['rs_id']." 
				  OR movement_no = ".$_POST['rs_id'].")";
		
		if($prevent_duplicate) // pending only
		$sql .= " AND processed = 0";		
		$_POST['status'] == 2;
	}
	$sql .= " AND branch_id = '".$branches_res['id']."'";
	$sql .= " ORDER BY rs_id";

	// display_error($sql); exit;

	$res = db_query($sql);
	if (mysql_num_rows($res) > 0) {
		while ($row = mysql_fetch_assoc($res)) {
			$running_total[$branches_res['name']] = $row['items_total'];
		}
	}
	else {
		$running_total[$branches_res['name']] = 0; 
	}
}


// if (!empty($running_total) && $_POST['status_type'] != '') {
	start_table($table_style2.' width=90%');
	
	if ($_POST['status_type'] == 0)
		echo "<h1>Posted (Approved)</h1>";
	else 
		echo "<h1>Not Posted (For Approval)</h1>";
	
	$th_total = array('BRANCH', 'RUNNING TOTAL');
	table_header($th_total);
	$j = 0;
	$grand_total = 0;
	foreach ($running_total as $branch_name => $branch_total) {
		$grand_total += $branch_total;
		alt_table_row_color($j);
		label_cell($branch_name.':', 'align=right');
		label_cell(number_format2($branch_total, 3), 'align=right');
		end_row();
	}
	start_row();
	label_cell('<font color=#880000><b>'.'OVERALL TOTAL:'.'</b></font>', 'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($grand_total), 2)."<b></font>",'align=right');
	end_row();
	end_table();
	br();
	br();
// }

br();
br();
div_end();

end_form();
end_page();
?>