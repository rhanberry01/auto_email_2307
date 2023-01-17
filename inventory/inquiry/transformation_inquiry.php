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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Item Transformation Inquiry"), false, false, "", $js);

start_form();
div_start('header');

$type = ST_ITEM_TRANSFORMATION;

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('ARIA Transaction #:', 'trans_no');
		ref_cells('Database Movement #:', 'm_no');
		//yesno_list_cells(_("Status Type:"), 'movement_type', '',_("Deliver to Branch"), _("Received from Branch"));
		//adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
		//yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();
	
$sql = "SELECT * from ".TB_PREF."transformation_header";
	
if ($_POST['start_date'])
{
	$sql .= " WHERE a_date_posted >= '".date2sql($_POST['start_date'])."'
			  AND a_date_posted<= '".date2sql($_POST['end_date'])."'";	
}

if ($_POST['trans_no'])
{
$sql .= " AND a_trans_no= '".$_POST['trans_no']."'";	
}

if ($_POST['m_no'])
{
$sql .= " AND a_ms_movement_no_out= '".$_POST['m_no']."' OR a_ms_movement_no_in= '".$_POST['m_no']."'";	
}

$sql .= " ORDER BY a_date_posted,a_trans_no";
$res = db_query($sql);
//display_error($sql);

start_table($table_style2.' width=95%');
$th = array();
	
array_push($th, 'Date Created','Date Posted', 'TransNo','MovementOutID','MovementInID','From Location','To Location', 'Created By','Posted By', 'Status','');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}


$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['a_date_created']));
	label_cell(sql2date($row['a_date_posted']));
	label_cell(get_gl_view_str(ST_ITEM_TRANSFORMATION, $row["a_trans_no"], $row["a_trans_no"]));
	//label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['a_ms_movement_id_out']);
	label_cell($row['a_ms_movement_id_in']);
	$loc=get_location_name($row["a_from_location"]);
	label_cell($loc);
	$loc2=get_location_name($row["a_to_location"]);
	label_cell($loc2);
	$user=get_user($row['a_created_by']);
	label_cell($user['real_name']);
	$user2=get_user($row['a_posted_by']);
	label_cell($user2['real_name']);
	//label_cell(get_comments_string($type, $row['trans_no']));
	
	if ($row['a_status']==1)
	{
	label_cell('Posted');
	}
	else {
	label_cell('Open');
	}
		label_cell(get_transformation_details_view_str($row['a_trans_no'],'View'));

	end_row();
}
end_table();
br();
br();
div_end();
end_form();
end_page();
?>