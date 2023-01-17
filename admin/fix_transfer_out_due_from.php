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
$page_security = 'SA_GLSETUP';

$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Fix Stock Transfer IN Due To Account', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE Due TO ACCOUNT IN ARIA

function adjust_gl_entry_due_to($trans_no, $account)
{
	$sql = "UPDATE 0_gl_trans SET account = '$account' 
				WHERE type = 70 AND type_no = $trans_no 
				AND account=''
				AND account != 570002";
				// AND amount < 0
	// display_error($sql);
	db_query($sql, 'failed to update gl_trans - due to' );
	
	if (mysql_affected_rows())
		return 1;
	else
		return 0;
}


if (isset($_POST['fix_now']))
{
	global $db_connections;
	
	$sql = "SELECT a.id, b.gl_stock_to
				FROM transfers.0_transfer_header a, transfers.0_branches b
				WHERE a.br_code_in = b.code
				AND transfer_out_date>='2018-01-01'
				AND br_code_in = br_code_in
				AND br_code_out = ". db_escape($db_connections[$_SESSION["wa_current_user"]->company]['br_code']);
	 //display_error($sql);
	// AND a.id = 1491
	$res = db_query($sql);
	
	$cc = 0;
	while($row = db_fetch($res))
	{
		$cc += adjust_gl_entry_due_to($row['id'], $row['gl_stock_to']);
	}
	display_notification("SUCCESS!!! $cc updated");
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
