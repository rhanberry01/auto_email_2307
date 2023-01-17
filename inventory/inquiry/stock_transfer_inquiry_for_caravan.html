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
include_once($path_to_root . "/inventory/includes/stock_transfer2.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Stocks Transfer Inquiry"), false, false, "", $js);

$delete = find_submit('delete_id',false);
if ($delete!='') 
{
	global $Ajax;
	$sql = "UPDATE transfers.0_transfer_header SET inactive = 1,cancelled = 1 WHERE id = ". $delete;
	db_query($sql , "Successfully deleted.");
	display_notification("Transfer #".$delete." is successfully deleted.");
	$Ajax->activate('_page_body');		
	//refresh();	
}
start_form();

start_table();
text_cells('Transfer #', 'trans_no');
get_branchcode_list_cells('From Location:','from_loc',null,'ALL Branch');
get_branchcode_list_cells('To Location:','to_loc',null,'ALL Branch');
end_table();

start_table();
$status = array('ALL', 'PENDING', 'DISPATCHED', 'RECEIVED');
echo "<td>Status</td>
		  <td>";
echo array_selector('status', NULL, $status, array());
echo "</td>";

date_cells('(Date Created) From : ', 'from_date',null,null, -60);
date_cells('(Date Created) To : ', 'to_date');


end_table();
br();
submit_center('search', 'Search');

br();br();

$th = array('#', 'Request Date','M#out','M#in', 'From','To','Requested By', 'Pick List',
'Dispatch Date',  'Delivered By', 'Checked By', 'Transfer Printout',
 'Transfer Received Date', 'Received By', '');
start_table($table_style2);
table_header($th);

$sql = "SELECT * FROM transfers.0_transfer_header
			WHERE m_code_out = 'STO' and inactive = 0";
			
if (trim($_POST['trans_no']) != '')
{
	$sql .= " AND id LIKE ". db_escape('%'.$_POST['trans_no'].'%');
}
else
{
	$sql .= " AND date_created >= '".date2sql($_POST['from_date'])."'";
	$sql .= " AND date_created <= '".date2sql($_POST['to_date'])."'";
	
	if ($_POST['from_loc'] != '')
		$sql .= ' AND br_code_out = ' .db_escape($_POST['from_loc']);
	
	if ($_POST['to_loc'] != '')
		$sql .= ' AND br_code_in = ' .db_escape($_POST['to_loc']);
}

if($_POST['status'] == 1){
	// PENDING
	$sql .= " AND m_id_out = 0 AND m_id_in = 0 ";
}else if($_POST['status'] == 2){
	//DISPATCHED
	$sql .= " AND m_id_out != 0 AND m_id_in = 0 ";
}else if($_POST['status'] == 3){
	//RECEIVED
	$sql .= " AND m_id_out != 0 AND m_id_in != 0 ";
}

// display_error($sql);
$res = db_query($sql);

$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	
	$actual = '';
	
	if ($row['transfer_out_date'])
		$actual = '&actual=1';
	
	$prev_str =  "<a target='_blank' href='$path_to_root/inventory/view/view_transfer_2.php?transfer_id=". $row['id'] ."$actual' 
				onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['id'] ."</a>";
				
	$pick_list_str =  "<a target='_blank' href='$path_to_root/reporting/transfer_pick_list.php?transfer_id=".  $row['id'] ."$actual' 
				onclick=\"javascript:openWindow(this.href,this.target); return false;\">".'Print Packing List' ."</a>";
	
	$printout_str = '';
	if ($row['transfer_out_date'])	
		$printout_str =  "<a target='_blank' href='$path_to_root/reporting/transfer_printout.php?transfer_id=".  $row['id'] ."$actual' 
				onclick=\"javascript:openWindow(this.href,this.target); return false;\">".'Print Transfer' ."</a>";
	
				
	label_cell($prev_str,'align=center');
		
	label_cell(sql2date($row['date_created']), 'align=center');
	label_cell($row['m_no_out']);
	label_cell($row['m_no_in']);
	label_cell(get_transfer_branch_name($row['br_code_out']));
	label_cell(get_transfer_branch_name($row['br_code_in']));
	label_cell($row['requested_by']);
	label_cell($pick_list_str,'align=center');
	label_cell($row['transfer_out_date'] ? sql2date($row['transfer_out_date']) : '<i>not yet dispatched</i>', 'align=center');
	label_cell($row['delivered_by']);
	label_cell($row['checked_by']);
	label_cell($printout_str,'align=center');
	label_cell($row['transfer_in_date'] ? sql2date($row['transfer_in_date']) : '<i>not yet received</i>', 'align=center');
	label_cell($row['name_in']);
	if($row['transfer_out_date'] == null AND $row['transfer_in_date'] == null){
		$delete_id='delete_id'.$row['id'];
		submit_cells($delete_id, _("Delete"), "colspan=0 align=center",_('Delete'), true, ICON_DELETE);
	}else{
		label_cell('');
	}
	end_row();
}

end_table();

end_form();
end_page();
?>