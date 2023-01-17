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

	
page('Fix Debit Memo entries to Promo Fund', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function fix_dm($type_no, $debit_credit)
{
	$sql = "UPDATE 0_gl_trans 
			SET account = ".$debit_credit[2]."
			WHERE type = 53
			AND type_no = $type_no
			AND account = ".$debit_credit[0];
	db_query($sql,'failed to update debit for type_no '.$type_no);
	
	// display_error($sql);
	$sql = "UPDATE 0_gl_trans 
			SET account = ".$debit_credit[3]."
			WHERE type = 53
			AND type_no = $type_no
			AND account = ".$debit_credit[1];
	db_query($sql,'failed to update credit for type_no '.$type_no);
	// display_error($sql);
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT DISTINCT old_debit, old_credit, new_debit, new_credit
				FROM sdma_old_new_accounts
				WHERE old_credit != new_credit";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
	{
		display_error('No VIEW (sdma_old_new_accounts) OR  no TABLE (0_sdma_type_copy) : <-- copy from srs_aria_nova');
		display_footer_exit();
	}
	
	$credits = $fixer = array();
	while($row = db_fetch($res))
	{
		$credits[] = $row['old_credit'];
		$fixer[$row['old_credit']] = array($row['old_debit'],$row['old_credit'],$row['new_debit'],$row['new_credit']);
		// $fixer['debit'][$row['old_credit']] = $row['new_credit'];
	}
		
	$sql = "SELECT * FROM 0_gl_trans WHERE type = 53
		AND amount <0
		AND account IN (".implode($credits,',').")
		AND tran_date >= '2016-01-01'";
	$res = db_query($sql);
	
	// display_error($sql);
	
	// fix per type_no
	begin_transaction();
	$count = 0;
	while($row = db_fetch($res))
	{
		$count ++;
		fix_dm($row['type_no'],$fixer[$row['account']]);
	}
	commit_transaction();
	
	display_notification('DONE - '.$count);
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
