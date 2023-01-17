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
	
page(_($help_context = "Debit Memo Agreement Inquiry"), false, false, "", $js);

function yes_no($x,$yes='YES',$no='NO')
{
	if ($x)
		return $yes;
	return $no;
}

//======================================================================================

$approve1 = find_submit('approve_sdma1');
if ($approve1 != -1)
{
	global $Ajax;
	$sql = "UPDATE ".TB_PREF."sdma SET approval_1 = ". $_SESSION['wa_current_user']->user ."
			WHERE id = $approve1";
	db_query($sql);
	$_POST['search'] = 1;
	$Ajax->activate('dm_list');
	
	// create_dm_from_sdma($approve1);
}

$approve2 = find_submit('approve_sdma2');
if ($approve2 != -1)
{
	global $Ajax;
	$sql = "UPDATE ".TB_PREF."sdma SET approval_2 = ". $_SESSION['wa_current_user']->user ."
			WHERE id = $approve2";
	db_query($sql);
	$_POST['search'] = 1;
	$Ajax->activate('dm_list');
	
	create_dm_from_sdma($approve2);
}

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('PO #/Reference :', 'dm_po_ref');
		supplier_list_cells('Supplier :', 'supp_id', null, true);
		date_cells('Date Created From :', 'start_date');
		date_cells(' To :', 'end_date');
		allyesno_list_cells('Approval Status:','status', null, 'ALL', 'approved', 'not yet approved');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
// if (!isset($_POST['search']))
	// display_footer_exit();
	
$sql = "SELECT a.*, b.supp_name, c.type_name FROM ".TB_PREF."sdma a, ".TB_PREF."suppliers b,
			".TB_PREF."sdma_type c
		WHERE a.supplier_id = b.supplier_id 
		AND a.sdma_type = c.id";
		
if (trim($_POST['dm_po_ref']) == '')
{
	$sql .= " AND DATE(date_created) >= '".date2sql($_POST['start_date'])."'
			  AND DATE(date_created) <= '".date2sql($_POST['end_date'])."'";
			  
	if ($_POST['supp_id'])
	{
		$sql .= " AND a.supplier_id = ".$_POST['supp_id'];
	}
	
	if($_POST['status'] == 0) // for approval
		$sql .= " AND (approval_1 = 0 OR approval_2 = 0)";
	else if($_POST['status'] == 1) // with CV
			$sql .= " AND (approval_1 != 0 AND approval_2 != 0)";	
}
else
{
	$sql .= " AND DATE(date_created) >= '".date2sql($_POST['start_date'])."'
			  AND DATE(date_created) <= '".date2sql($_POST['end_date'])."'"; // added due to accunting request module trade payable ** rhan 11/10/17

	$sql .= " AND (reference LIKE ".db_escape('%'.$_POST['dm_po_ref'].'%')." 
			  OR po_no LIKE ".db_escape('%'.$_POST['dm_po_ref'].'%')." )";
}
$sql .= " ORDER BY date_created";
$res = db_query($sql);
// display_error($sql);

start_table($table_style2.' width=90%');
$th = array('#', 'Reference','PO #', 'Date Created', 'Supplier', 'Type', 'Amount', 'Effectivity','Comment',
		'Approval 1','Approval 2','');//,'Inactive');


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

	// label_cell(get_supplier_trans_view_str($type, $row['trans_no'], $row['reference']));
	
	label_cell($row['id']);
	label_cell($row['reference']);
	label_cell($row['po_no']);
	label_cell(sql2date($row['date_created']));
	label_cell($row['supp_name'] ,'nowrap');
	label_cell($row['type_name']);
	
	if ($row['amount'] > 0)
		amount_cell($row['amount']);
	
	else
		label_cell($row['disc_percent'].'%','align=right');
	
	if ($row['po_no'] != '')
	{
		$effectivity = 'for PO # '.$row['po_no'].' Only';
	}
	else if ($row['po_no'] == '' AND $row['once_only'] == 1)
	{
		global $frequency;
		if ($row['frequency'] == 0)
			$effectivity = 'for 1 CV dated '. sql2date($row['dm_date']);
		else
			$effectivity = 'for 1 CV '. $frequency[$row['frequency']] .' starting '. sql2date($row['dm_date']).
				' <br>(<i>for '. ($row['period']+1) .' deductions</i>)';
	}
	else
		$effectivity = sql2date($row['effective_from']) .' to '. sql2date($row['effective_to']);
	
	label_cell($effectivity);
	
	// label_cell(yes_no($row['once_only']),'align = center');
	
	label_cell($row['comment']);
	
	// display_error($_SESSION['wa_current_user']->can_approve_sdma_1);
	if ($row['approval_1'] == 0 AND !$_SESSION['wa_current_user']->can_approve_sdma_1)
		label_cell('<i>pending</i>','align=center');
	else if ($row['approval_1'] == 0 AND $_SESSION['wa_current_user']->can_approve_sdma_1)
		approve_button_cell('approve_sdma1'.$row['id'], 'Approve');
	else
		label_cell('by: <i><b>'.get_username_by_id($row['approval_1']).'</b></i>');
		
	if ($row['approval_2'] == 0 AND !$_SESSION['wa_current_user']->can_approve_sdma_2)
		label_cell('<i>pending</i>','align=center');
	else if ($row['approval_2'] == 0 AND $_SESSION['wa_current_user']->can_approve_sdma_2)
	{
		if ($row['approval_1'] == 0)
			label_cell('<i>waiting for 1st approval</i>','align=center');
		else
			approve_button_cell('approve_sdma2'.$row['id'], 'Approve');
	}
	else
		label_cell('by: <i><b>'.get_username_by_id($row['approval_2']).'</b></i>');

	// label_cell('<b>'.yes_no($row['inactive'],'INACTIVE','ACTIVE').'</b>','align = center');	
	
	if (($_SESSION['wa_current_user']->username == 'leah' OR $_SESSION['wa_current_user']->username == 'beth' OR $_SESSION['wa_current_user']->username == 'melay' OR $_SESSION['wa_current_user']->can_approve_sdma_1) 
	OR ($row['approval_2'] == 0 AND $row['prepared_by'] == $_SESSION['wa_current_user']->user))
	label_cell(pager_link(_("Edit"),"/purchasing/sdma_entry.php?sdma_id=" . $row["id"], ICON_EDIT));
	
	end_row();
}

end_table();
div_end();
end_form();



end_page();

?>
