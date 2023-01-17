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

	
page('Fix Debit Memo GL (Promo fund w/ tax)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

if (isset($_POST['fix_now']))
{
	begin_transaction();
	$sql = "SELECT * FROM 0_gl_trans_temp WHERE type = 53 AND account IN (2470,2471,2472,2473,2474,2475,2476,2477,2478,2479,2480,2481,2482,2483) and tran_date>='2017-01-01'";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		$type = 53;
		$type_no = $row['type_no'];
		$account_no = $row['account'];
		
		$sql = "DELETE FROM 0_gl_trans_temp
					WHERE type = 53 AND type_no = $type_no
					AND account NOT IN(2000,$account_no)";
		db_query($sql,'failed to delete tax of DM');
		
		$sql = "DELETE FROM 0_gl_trans
					WHERE type = 53 AND type_no = $type_no
					AND account NOT IN(2000,$account_no)";
		db_query($sql,'failed to delete tax of DM');
		
		$sql = "SELECT amount FROM 0_gl_trans_temp WHERE type=53 AND type_no=$type_no AND account=2000";
		$res2 = db_query($sql);
		$row2 = db_fetch($res2);
		
		$amount = $row2[0];
		$sql = "UPDATE 0_gl_trans_temp SET amount = -$amount WHERE type=53 AND type_no=$type_no AND account=$account_no";
		db_query($sql,'failed to update amount for promo fund');
		$sql = "UPDATE 0_gl_trans SET amount = -$amount WHERE type=53 AND type_no=$type_no AND account=$account_no";
		db_query($sql,'failed to update amount for promo fund');
	}
	commit_transaction();
	
	display_notification('FIX DONE');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
