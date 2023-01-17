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
	
page(_($help_context = "B.O Aging Disposal Approval"), false, false, "", $js);


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

function update_aging_status($aging_id) {
	$sql = "UPDATE centralized_returned_merchandise.0_bo_aging 
			SET status = 1, 
			disposed_by = '".$_SESSION['wa_current_user']->user."'
			WHERE aging_id = $aging_id";
	$query = db_query($sql);
	if (mysql_affected_rows() > 0) {
		return true;
	}
}

function get_username_by_id_($id, $branch_id)
{	
	$sql = "SELECT real_name FROM centralized_returned_merchandise.".TB_PREF."users 
			WHERE id = $id
			AND branch_id='".$branch_id."'";
	$query = db_query($sql);
	$row = mysql_fetch_array($query);
	return $row[0];
}

function get_ms_supp_name($supp_code)
{
	$sql = "SELECT description FROM vendor
			WHERE vendorcode = '$supp_code'";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}

function get_ms_supp_name_by_branch($supp_code, $branch_id) {
	$b = get_branch_by_id($branch_id);
	$conn = mssql_connect($b['ms_mov_host'], $b['ms_mov_user'], $b['ms_mov_pass']);
	mssql_select_db($b['ms_mov_db'], $conn);
	$sql = "SELECT description FROM vendor
			WHERE vendorcode = '$supp_code'";
	$res = mssql_query($sql, $conn);
	$row = mssql_fetch_array($res);
	return $row[0];
}


function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM centralized_returned_merchandise.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

function get_rs_view_str_per_branch($trans_no, $label, $branch_id) {
	global $path_to_root;

	$viewer = "purchasing/view/view_rs_live_2.php?rs_id=$trans_no&bid=$branch_id";

	return "<a target='_blank' href='$path_to_root/$viewer' onclick=\"javascript:openWindow(this.href,this.target); return false;\">$label</a>";
}


if ($db_connections[$_SESSION["wa_current_user"]->company]["name"] != 'San Roque Supermarket - NOVA') {
	display_error('<h2>'."YOU CAN ONLY ACCESS THIS PAGE WHEN YOU'RE LOGGED IN FROM NOVALICHES BRANCH. ".'</br>'."Please logout and login to novaliches branch in order to proceed. Thank you!".'</h2>');
	exit;
}

if (isset($_POST['approve_all_selected'])) {
	if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
		foreach ($_POST['post_id'] as $key => $aging_id) {
			$update_status = update_aging_status($aging_id);
			if ($update_status) 
				notify("Disposal #".$aging_id." is successfully disposed.");
			else 
				notify("Disposal #".$aging_id." is not disposed! Please try again.", 2);
		}
	}
}


// Custom JS here
echo '<script type="text/javascript">';
	echo '$(document).ready(function() {

			$("#post_all").change(function() {
				$(".post_one").attr("checked", this.checked);
			});

			$(".post_one").change(function() {
				if ($(".post_one").length == $(".post_one:checked").length) {
					$("#post_all").attr("checked", "checked");
				}
				else {
					$("#post_all").removeAttr("checked");
				}
			});

			$("input:checkbox").change(function() {
				if ($("input:checkbox:checked").length > 0) {
					$("#approve_all_selected").show();
				}
				else {
					$("#approve_all_selected").hide();
				}	
			});
			
		});';
echo '</script>';	

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
ref_cells('ID #: ','rs_id', null, null, null, true);
// supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
date_cells('Date From: ', 'bo_date_from');
date_cells('Date To: ', 'bo_date_to');
yesno_list_cells(_("Status Type:"), 'status_type', '',_("Disposed"), _("Not Disposed"));
submit_cells('search', 'Search', "", false, false);
end_table(2);
div_end();
div_end();

div_start('dm_list');


// display_error($sql);
// exit;
$sql = "SELECT * FROM centralized_returned_merchandise.0_bo_aging";

$sql .= " WHERE date_created >= '".date2sql($_POST['bo_date_from'])."'
		  AND date_created <= '".date2sql($_POST['bo_date_to'])."'";

if (!empty($_POST['rs_id'])) {
	$sql .= " AND rs_id = '".$_POST['rs_id']."'";
}

if ($_POST['status_type'] != '') {
	$sql .= " AND status = '".$_POST['status_type']."'";
}

$res = db_query($sql);

// notify($sql, 2);

start_table($table_style2.' width=90%');
$th =array('#', 'Branch', 'Date Approved', 'Age (by days)', 'Status', 'Date Disposed', 'Approved By', 'BO Custodian'.'<input type="checkbox" id="approve_all">');

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



while ($row = mysql_fetch_assoc($res)) {
	alt_table_row_color($k);
	label_cell(get_rs_view_str_per_branch($row['rs_id'],'# '.$row['rs_id'], $row['branch_id']));
	label_cell($row['branch_name']);
	label_cell(sql2date($row['date_created']));


	if ($row['status'] == 1) {	
		label_cell("0");
		label_cell("Disposed");
		label_cell(sql2date($row['date_disposed']));
	}
	else {
		$now = time(); // or your date as well
		$your_date = strtotime($row['date_created']);
		$datediff = $now - $your_date;

		label_cell(floor($datediff / (60 * 60 * 24)));
		label_cell("For Disposal");
		label_cell("");
	}


	label_cell(get_username_by_id_($row['disposed_by'], $row['branch_id']));

	if ($row['status'] == 0) {
		echo '<td align="center">
						<input type="checkbox" class="post_one" name="post_id[]" value="'.$row['aging_id'].'" />
					</td>';	
	}
	else {
		label_cell("");
	}
	

	end_row();
}
end_table();
br();
br();
div_end();

echo '<center style="margin: 10px 0;">
		<button class="inputsubmit" type="submit" id="approve_all_selected" name="approve_all_selected" style="display: none;">
			<span>Approve Selected</span>
		</button>
	</center>';

end_form();
end_page();
?>