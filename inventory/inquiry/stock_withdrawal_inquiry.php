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
include_once($path_to_root . "/inventory/includes/db/stocks_withdrawal_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Stocks Withdrawal Inquiry"), false, false, "", $js);


$approve_add = find_submit('selected_id',false);
$approve_second = find_submit('approve_id',false);


if ($approve_second!='') 
{
global $Ajax,$db_connections;
//display_error($approve_second);
$users_id=$_SESSION["wa_current_user"]->user;

approve_stock_withdrawal($approve_second);
display_notification("Stock Withdrawal #".$approve_second." is successfully approved.");
$Ajax->activate('dm_list');
}

if ($approve_add!='') 
{
global $Ajax,$db_connections;
//display_error($approve_add);
$users_id=$_SESSION["wa_current_user"]->user;
approve_stock_withrawal_req($approve_add,$users_id);
display_notification("Stock Withdrawal #".$approve_add." is successfully approved by Dept./Division Manager.");
$Ajax->activate('dm_list');
}


function get_gl_view_str_per_branch($br_code,$type, $trans_no, $label="", $force=false, $class='', $id='',$icon=true)
{
	global $db_connections;
	//display_error($br_code);
	// switch($br_code){
						// case 'srsn':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
									// break;
						// case 'sri':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
									// break;
						// case 'srsnav':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
									// break;
						// case 'srst':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
									// break;
						// case 'srsc':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
									// break;
						// case 'srsant1':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
									// break;
						// case 'srsant2':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
									// break;
						// case 'srsm':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
									// break;
						// case 'srsmr':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
									// break;
						// case 'srsg':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
									// break;
						// case 'srscain':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
									// break;
						// case 'srsval':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
									// break;			
						// case 'srspun':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
									// break;								
						// case 'srsbsl':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
									// break;			
						// case 'srspat':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
									// break;		
		// }

		// //display_error($connect_to);
// //set_global_connection_branch($connect_to);
	
	$connect_to=get_connection_to_branch($br_code);
	
	if (!$force && !user_show_gl_info())
		return "";

	$icon = false;
	if ($label == "")
	{
		$label = _("GL");
		$icon = ICON_GL;
	}	
		//	set_global_connection_branch();
	
	return viewer_link($label, 
		"gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no&branch=$connect_to", 
		$class, $id, $icon);
		
		set_global_connection_branch();
		
}

start_form();
div_start('dm_list');
start_table();
text_cells('Transfer #', 'trans_no');
// get_branchcode_list_cells('From Location:','from_loc',null,'ALL Branch');
// get_branchcode_list_cells('To Location:','to_loc',null,'ALL Branch');

		echo "<td>"._("To Location:")."</td><td><select name='from_loc'>\n";
		echo "<option value='' selected:>Select Branch</option>";
		for ($i = 0; $i < count($db_connections); $i++)
			echo "<option value=".$db_connections[$i]["br_code2"].">" . $db_connections[$i]["name"] . "</option>";
		echo "</select>\n";
		
		echo "<td>"._("To Location:")."</td><td><select name='to_loc'>\n";
		echo "<option value='' selected:>Select Branch</option>";
		for ($i = 0; $i < count($db_connections); $i++)
			echo "<option value=".$db_connections[$i]["br_code2"].">" . $db_connections[$i]["name"] . "</option>";
		echo "</select>\n";


date_cells('(Date Created) From : ', 'from_date',null,null, -60);
date_cells('(Date Created) To : ', 'to_date');
end_table();
br();
submit_center('search', 'Search');

br();br();

$th = array('','OK','Withdrawal#','Date Created','Request Date', 'From', 'To','Requested By', 'Department','Nature of Request','Date Released', 'Approved By','Released By','');
start_table($table_style2);
table_header($th);

$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];

$sql = "SELECT * FROM transfers.0_stocks_withdrawal_header
			WHERE m_code_out = 'SW'";
			
if (trim($_POST['trans_no']) != '')
{
	$sql .= " AND id LIKE ". db_escape('%'.$_POST['trans_no'].'%');
}
else
{
	$sql .= " AND date_created >= '".date2sql($_POST['from_date'])."'";
	$sql .= " AND date_created <= '".date2sql($_POST['to_date'])."'";
	
		$sql .= " AND br_code_out = '".$myBranchCode."'";
	
	// if ($_POST['from_loc'] != '')
		// $sql .= ' AND br_code_out = ' .db_escape($_POST['from_loc']);
	
	// if ($_POST['to_loc'] != '')
		// $sql .= ' AND br_code_in = ' .db_escape($_POST['to_loc']);
}
//display_error($sql);

$res = db_query($sql);

$k = 0;
$c=0;

while($row = db_fetch($res))
{
	$c++;
	alt_table_row_color($k);
	
	$actual = '';
	
	if ($row['transfer_out_date'])
		$actual = '&actual=1';
	
	$prev_str =  "<a target='_blank' href='$path_to_root/inventory/view/view_stocks_withdrawal.php?transfer_id=". $row['id'] ."$actual' 
				onclick=\"javascript:openWindow(this.href,this.target); return false;\">View</a>";
				
	label_cell($c,'align=center');
	label_cell($row["is_ok"],'align=center');
	label_cell($row["id"],'align=center');
	//label_cell(get_gl_view_str(ST_STOCKS_WITHDRAWAL, $row["id"], $row["id"]),'align=center');
	//label_cell($row['withdrawal_slip_no']);	
	label_cell(sql2date($row['date_created']), 'align=center');
	label_cell(sql2date($row['request_date']), 'align=center');
	label_cell(get_gl_view_str_per_branch($row['br_code_out'],ST_STOCKS_WITHDRAWAL, $row["id"], get_transfer_branch_name($row['br_code_out'])),'align=center');
	label_cell(get_gl_view_str_per_branch($row['br_code_in'],ST_STOCKS_WITHDRAWAL, $row["id"], get_transfer_branch_name($row['br_code_in'])),'align=center');
	
	// $u = get_user($row['requested_by']);
	// $requested_by = $u['real_name'];
	label_cell($row['requested_by'],'align=center');
	
	label_cell(get_hr_dept_name($row['requesting_dept']));
	label_cell(get_nature_of_req_name($row["nature_of_req"]));
	label_cell(sql2date($row['released_date']) , 'align=center');
	
	if ($row['manager_approved']==0) {
	$selected='selected_id'.$row['id'];
	submit_cells($selected, _("Approve Request"), "colspan=0 align=center",_('Approve Request'), true, ICON_ADD);
	}
	else{
	$u1 = get_user($row['approved_by']);
	$approved_by = $u1['real_name'];
		
	label_cell($approved_by,'align=center');
	}
	
	if ($row['oic_approved']==0 and $row['manager_approved']==1) {
	$approve_id='approve_id'.$row['id'];
	submit_cells($approve_id, _("Approve Request"), "colspan=0 align=center",_('Approve Request'), true, ICON_ADD);
	}
	else{
		$u2 = get_user($row['witnessed_by']);
		$witnessed_by = $u2['real_name'];
		label_cell($witnessed_by,'align=center');
	}
	
	label_cell($prev_str,'align=center');
	end_row();
}

end_table();
div_end();

end_form();
end_page();
?>