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
include_once($path_to_root . "/inventory/includes/db/item_transformation_db.inc");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Item Transformation (Centralized)"), false, false, "", $js);

$approve_add = find_submit('selected_id');
$approve_delete = find_submit('selected_del');

function ping_conn($host, $port=1433, $timeout=2) {
	$fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
	if (!$fsock) 
		return false;
	else
		return true;
}

function notify($msg, $type=1) {
	if ($type == 2) {
		echo '<div class="msgbox">
				<div class="err_msg">
					' . $msg . '
				</div>
			</div>';
	}
	elseif ($type == 1) {
	 	echo '<div class="msgbox">
				<div class="note_msg">
					' . $msg . '
				</div>
			</div>';
	 }
}

function get_user_by_branch($id, $aria_db) {
	
	$sql = "SELECT * FROM ".$aria_db.".".TB_PREF."users WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get user $id");

	return db_fetch($result);
}

function get_gl_view_str_per_branch($type_id, $trans_no, $label, $branch_id) {
	global $path_to_root;

	$viewer = "gl/view/gl_trans_view_2.php?type_id=$type_id&trans_no=$trans_no&bid=$branch_id";

	return "<a target='_blank' href='$path_to_root/$viewer' onclick=\"javascript:openWindow(this.href,this.target); return false;\">$label</a>";
}

function get_transformation_details_view_str_by_branch($trans_no, $label, $branch_id) {
	global $path_to_root;

	$viewer = "inventory/inquiry/transformation_details_view_2.php?trans_no=$trans_no&bid=$branch_id";

	return "<a target='_blank' href='$path_to_root/$viewer' onclick=\"javascript:openWindow(this.href,this.target); return false;\">$label</a>";
}

function movement_name($code)
{
	global $db_connections;
	$sql = "SELECT name from ".TB_PREF."movement_types where movement_code = '".$code."'";
	$res = db_query($sql);
	$row = db_fetch_row($res);
	return $row[0];
}


function delete_transformation($id)
{
	if(!is_null($id)){
		$sql="DELETE FROM ".TB_PREF."transformation_header WHERE a_trans_no=".db_escape($id)."";
		//display_error($sql);
		db_query($sql, "could not delete line item");

		$sql="DELETE FROM ".TB_PREF."transformation_details WHERE transformation_no=".db_escape($id)."";
		//display_error($sql);
		db_query($sql, "could not delete line item");
	}

}


if ($approve_add != -1) 
{
global $Ajax;

$check = check_($approve_add);
if($check == 0){
	item_transformation_approval($approve_add);
	display_notification("Adjustment #".$approve_add." is successfully approved.");
	$Ajax->activate('dm_list');
}else{
	display_error("Cannot processed barcode(s) ".implode(',',$check)." in Trans. # ".$approve_add.".");
}
	
}

if ($approve_delete != -1) {
	global $Ajax;
	delete_transformation($approve_delete);
	display_notification("Transformation #".$approve_delete." is successfully removed.");
}


start_form();
div_start('header');

$type = ST_ITEM_TRANSFORMATION;

start_table();
	start_row();
		ref_cells('Transaction #:', 'trans_no');
		// yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']) or ($approve_add != -1 and $posted_add!= -1))
	display_footer_exit();

$b_sql = "SELECT * FROM transfers.0_branches";
$b_query = db_query($b_sql);
$all_branch = array();
while ($b_row = mysql_fetch_assoc($b_query)) {
	$sql = "SELECT a.* FROM ".$b_row['aria_db'].".".TB_PREF."transformation_header a WHERE";
		
	if ($_POST['start_date'])
	{
		$sql .= "  a.a_date_created >= '".date2sql($_POST['start_date'])."'
				  AND a.a_date_created<= '".date2sql($_POST['end_date'])."'";	
	}

	if ($_POST['trans_no'])
	{
	$sql .= " AND a.a_trans_no = '".$_POST['trans_no']."'";	
	}

	$sql .= " ORDER BY a.a_date_created";
	$res = db_query($sql);

	while ($row = mysql_fetch_assoc($res)) {
		$row['branch_id'] = $b_row['id'];
		$row['branch_name'] = $b_row['name'];
		$row['branch_aria_db'] = $b_row['aria_db'];
		$all_branch[] = $row;
	}
}

// echo "<pre>";
// print_r($all_branch);
// exit;

start_table($table_style2.' width=95%');
$th = array();
	
// array_push($th, 'Branch', 'Date Created', 'TransNo','Movement# OUT', 'Movement# IN', 'Created By', 'Date Posted', 'Posted By', 'Status','','','');
array_push($th, 'Branch', 'Date Created', 'TransNo','Movement# OUT', 'Movement# IN', 'Created By', 'Date Posted', 'Posted By', 'Status','');


if (!empty($all_branch))
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

foreach ($all_branch as $key => $row) {
	alt_table_row_color($k);
	label_cell($row['branch_name']);
	label_cell(sql2date($row['a_date_created']));
	label_cell(get_gl_view_str_per_branch(ST_ITEM_TRANSFORMATION, $row["a_trans_no"], $row["a_trans_no"], $row['branch_id']));
	label_cell($row['a_ms_movement_no_out']);
	label_cell($row['a_ms_movement_no_in']);
	$user_create=get_user_by_branch($row['a_created_by'], $row['branch_aria_db']);
	label_cell($user_create['real_name']);
	label_cell(sql2date($row['a_date_posted']));
	$user_post=get_user_by_branch($row['a_posted_by'], $row['branch_aria_db']);
	label_cell($user_post['real_name']);
	
	if ($row['a_status']==0)
	{
	label_cell('Open');
	}
	else {
	label_cell('Posted');
	}
		label_cell(get_transformation_details_view_str_by_branch($row['a_trans_no'],'View', $row['branch_id']));
		

	/*if ($row['a_status']==0 and ($approver=='juliet' or $approver=='admin' or $approver=='cezz' or $approver=='0238')) {
	$selected_1='selected_id'.$row['a_trans_no'];
	submit_cells($selected_1, _("Post Transformation"), "colspan=1",_('Post Transformation'), true, ICON_ADD);
	}
	else if ($row['a_status']==1 OR $row['a_status']==2){
	label_cell('Posted');
	}
	else {
	label_cell('Not yet approved');
	}
	
	$selected_del='selected_del'.$row["a_trans_no"];
	submit_cells($selected_del, _("Delete"), "colspan=2",_('Delete Transformation'), true,ICON_DELETE);	*/

	end_row();
}
end_table();
br();
br();
div_end();
end_form();
end_page();
?>