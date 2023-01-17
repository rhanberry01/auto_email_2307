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

	
page('Fix Debit Memo (POST all temp + FIX Supp payment (delete Other Expense account))', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function fix_gl_date($type, $trans_no, $tran_date)
{
	$sql = "UPDATE 0_gl_trans SET
				tran_date = '$tran_date'
				WHERE type = $type
				AND type_no=$trans_no";
	db_query($sql,'failed to update gl');
	
	post_gl_trans($type, $trans_no, sql2date($tran_date));
}

function fix_supp_payment($type, $type_no, $amount)
{
	$sql = "UPDATE 0_gl_trans SET amount = amount-$amount
				WHERE type=$type AND type_no=$type_no 
				AND amount > 0";
	db_query($sql,'failed to update entry amount for supp payment');
	
	$sql = "DELETE FROM 0_gl_trans WHERE type=$type AND type_no=$type_no AND account='8000'";
	db_query($sql,'failed to delete other expense account');
}

if (isset($_POST['fix_now']))
{
	// $sql = "SELECT * FROM  0_supp_trans 
			// WHERE type IN (52,53)
			// AND tran_date >= '2015-01-01'";
	$sql = "SELECT DISTINCT type, type_no, tran_date 
				FROM 0_gl_trans_temp
				WHERE (type = 53 OR type = 52)
				AND tran_date >= '2018-01-01'
				AND posted = 0
				AND type!=59
				";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		// fix_gl_date($row['type'],$row['trans_no'],$row['tran_date']);
		fix_gl_date($row['type'],$row['type_no'],$row['tran_date']);
	}
	display_notification('DM and CM fix DONE!');
	
	//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
	$sql = "SELECT type, type_no, amount 
				FROM 0_gl_trans
				WHERE type = 22
				AND account = 8000
				AND tran_date >= '2018-01-01'";
	$res = db_query($sql);
	while($row = db_fetch($res))
		fix_supp_payment($row['type'], $row['type_no'], -$row['amount']);

	display_notification('Supp Payment fix DONE!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
