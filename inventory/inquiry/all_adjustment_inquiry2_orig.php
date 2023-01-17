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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Adjustment Inquiry"), false, false, "", $js);

$approve_add = find_submit('selected_id');
$posted_add = find_submit('posted_id');

function update_posted_adjustment_header($temp_posted_id)
{
	global $db_connections;
	$sql = "UPDATE ".TB_PREF."adjustment_header SET 
			a_temp_posted = '1',
			a_temp_posted_by = ".$_SESSION['wa_current_user']->user."
			WHERE a_trans_no ='$temp_posted_id'";
	db_query($sql,'failed to update movement (Adjustment Header)');
}



if ($posted_add != -1) 
{
global $Ajax;
//display_error($posted_add);
update_posted_adjustment_header($posted_add);

display_notification("Adjustment #".$posted_add." is temporary posted for approval.");
$Ajax->activate('dm_list');
}


if ($approve_add != -1) 
{
global $Ajax;
//display_error($approve_add);
$movement_code=$_POST['a_movement_code'.$approve_add];
//display_error($movement_code);
$trans_no = approve_adjustment_details($approve_add,$approve_add,$movement_code, $_POST['memo_']);

display_notification("Adjustment #".$approve_add." is successfully approved.");
$Ajax->activate('dm_list');
}


start_form();
div_start('header');

$type = ST_INVADJUST;

// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');
start_table();
	start_row();
		ref_cells('Transaction #:', 'trans_no');
		//yesno_list_cells(_("Status Type:"), 'movement_type', '',_("Deliver to Branch"), _("Received from Branch"));
		//adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
		yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']) or ($approve_add != -1 and $posted_add!= -1))
	display_footer_exit();
	
$sql = "SELECT b.name, a.* from ".TB_PREF."adjustment_header  a, ".TB_PREF."movement_types b
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


if ($_POST['status_type']==1)
{
//Open
$stats='1' ;
}
else {
//Posted
$stats='2';
}

if ($_POST['status_type']!='')
{
$sql .= " AND a.a_status = '$stats'";	
}


$sql .= " ORDER BY a.a_date_created";
$res = db_query($sql);
//display_error($sql);

start_table($table_style2.' width=95%');
$th = array();
	
array_push($th, 'Date Created', 'TransNo','MovementNo','Movement Type', 'Created By', 'Date Posted', 'Posted By', 'Status','','','');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}


$k = 0;

	$u = get_user($_SESSION["wa_current_user"]->user);
	$approver= $u['user_id'];
	//display_error($approver);

while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['a_date_created']));
	label_cell(get_gl_view_str(ST_INVADJUST, $row["a_trans_no"], $row["a_trans_no"]));
	//label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['a_movement_no']);
	// label_cell($row['a_from_location']);
	label_cell($row['name']);
	$user_create=get_user($row['a_created_by']);
	label_cell($user_create['real_name']);
	label_cell(sql2date($row['a_date_posted']));
	$user_post=get_user($row['a_posted_by']);
	label_cell($user_post['real_name']);
	//label_cell(get_comments_string($type, $row['trans_no']));
	
	if ($row['a_status']==1)
	{
	label_cell('Open');
	}
	else {
	label_cell('Posted');
	}
		label_cell(get_adjustment_details_view_str($row['a_trans_no'],'View'));
		

	if (($row['a_status']==1 and ($approver=='juliet' or $approver=='jelyn' or $approver=='belle' or $approver=='admin')) 
		OR ($row['a_status']==1 AND $approver=='salve' AND $row['a_movement_code'] == 'WH2BU')
		OR ($row['a_status']==1 AND  ($approver=='0238' or $approver=='cezz' or $approver=='0056' ) AND ($row['a_movement_code'] == 'IGNSA' or $row['a_movement_code'] == 'IGSA' ))
	)
	{
	label_cell(pager_link(_('Edit'), "/inventory/all_item_adjustments.php?trans_no=" .$row['a_trans_no'], false));
	hidden('a_movement_code'.$row['a_trans_no'],$row['a_movement_code']);
	$selected_1='posted_id'.$row['a_trans_no'];
	$selected='selected_id'.$row['a_trans_no'];
	
	if(($row['a_movement_code'] == 'IGSA' OR $row['a_movement_code'] == 'IGNSA')  AND $row['a_temp_posted']==0 AND  ($approver=='0238' or $approver=='cezz' or $approver=='admin' or $approver=='0056' ) )
	{
			submit_cells($selected_1, _("Post Adjustment"), "colspan=2",_('Post Adjustment'), true, ICON_ADD);
	}
	else if (($row['a_movement_code'] == 'IGSA' OR $row['a_movement_code'] == 'IGNSA')  AND $row['a_temp_posted']==1
	AND $row['a_status']==1 and ($approver=='juliet' or $approver=='jelyn' or $approver=='belle' or $approver=='admin')){
			submit_cells($selected, _("Approve Adjustment"), "colspan=2",_('Approve Adjustment'), true, ICON_ADD);
	}
	else if ($row['a_movement_code'] == 'IGSA' OR $row['a_movement_code'] == 'IGNSA' ) {
			label_cell('For Approval of Operations');
	}
	else if (($row['a_movement_code'] != 'IGSA' OR $row['a_movement_code'] != 'IGNSA')  AND $row['a_temp_posted']==0  AND 
	($approver=='juliet' or $approver=='jelyn' or $approver=='belle' or $approver=='admin')){
			submit_cells($selected, _("Approve Adjustment"), "colspan=2",_('Approve Adjustment'), true, ICON_ADD);
	}
	
	}
	else {
	label_cell('');
	label_cell('');
	}
	end_row();
}
end_table();
br();
br();
div_end();
end_form();
end_page();
?>