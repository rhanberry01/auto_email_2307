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

	
page('Fix GL  discrep 2016', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
function update_gl_trans_less_05($type, $type_no, $diff)
{
	$sql = "UPDATE 0_gl_trans SET 
						amount = amount - $diff
					WHERE type = $type
					AND type_no = $type_no
					ORDER BY amount DESC LIMIT 1";
	// echo $sql;die;
	db_query($sql,'failed to update gl_trans');
}

function update_gl_trans_cwo($type_no, $diff)
{
	$sql = "UPDATE 0_gl_trans SET 
						amount = amount - $diff
					WHERE type = 24
					AND type_no = $type_no
					AND account = 1440";
	// echo $sql;die;
	db_query($sql,'failed to update gl_trans');
}

function update_gl_payroll($trans_no, $diff, $tran_date, $memo_)
{
	$missing_account = '2000011';
	$diff = -$diff;
	
	add_gl_trans(0, $trans_no, $tran_date, $missing_account, 0, 0, $memo_,$diff);
}

function update_gl_recon($trans_no, $diff, $tran_date, $memo_)
{
	$missing_account = '272727';
	$diff = -$diff;
	
	add_gl_trans(62, $trans_no, $tran_date, $missing_account, 0, 0, $memo_,$diff);
}


function update_gl_61_v2($trans_no, $diff, $tran_date, $memo_)
{
	$sql = "DELETE FROM 0_gl_trans WHERE type = 61 AND type_no=$trans_no
					AND amount = $diff AND memo_ = ''";
	db_query($sql);
}

function fix_gl_entries($type, $trans_no, $diff, $tran_date, $memo_)
{
	if (abs($diff) < 0.1) // caused by VAT computation
	{
		update_gl_trans_less_05($type, $trans_no, $diff);
		return true;
	}
	
	if ($type == 24) //CWO
	{
		update_gl_trans_cwo($trans_no, $diff);
		return true;
	}	
	
	if ($type == 0) //payroll
	{
		update_gl_payroll($trans_no, $diff,$tran_date,$memo_);
		return true;
	}	
	
	if ($type == 61) //61
	{
		update_gl_61_v2($trans_no, $diff,$tran_date,$memo_);
		return true;
	}		
	
	// if ($type == 62) //reconciliation
	// {
		// update_gl_recon($trans_no, $diff,$tran_date,$memo_);
		// return true;
	// }	
}

function update_61($row)
{
	$sql = "UPDATE 0_gl_trans SET
					tran_date = '".$row['date__']."'
					WHERE type = ". $row['type']."
					AND type_no = ".$row['type_no'];
	db_query($sql,"failed to update type 61");
}

if (isset($_POST['fix_now']))
{
	$sql = "UPDATE 0_gl_trans SET amount = round(amount,2)
				WHERE tran_date >= '".date2sql($_POST['from'])."'
				AND tran_date <= '".date2sql($_POST['to'])."'";
	db_query($sql,'failed to round amounts');
	
	$sql = "UPDATE 0_gl_trans SET account = '272727'
					WHERE type = 62 AND account = ''
					AND tran_date >= '".date2sql($_POST['from'])."'
					AND tran_date <= '".date2sql($_POST['to'])."'";
	db_query($sql,'failed to update type 62 in transit');
	
	$sql = 'UPDATE 0_gl_trans 
		SET account = 2350017
		WHERE memo_ LIKE "Transfer OUT (DR)%(NOVA)%"
		AND amount > 0 AND type = 0 
		AND tran_date >= '."'".date2sql($_POST['from'])."'
		AND tran_date <= '".date2sql($_POST['to'])."'";
	db_query($sql,'failed to update transfers to NOVA');
	
	$sql = 'UPDATE 0_gl_trans 
		SET account = 2350029
		WHERE memo_ LIKE "Transfer OUT (DR)%(PAVIA%"
		AND amount > 0 AND type = 0 
		AND tran_date >= '."'".date2sql($_POST['from'])."'
		AND tran_date <= '".date2sql($_POST['to'])."'";
	db_query($sql,'failed to update transfers to TONDO');
	
	$sql = 'UPDATE 0_gl_trans 
		SET account = 1450058
		WHERE memo_ LIKE "Transfer OUT (DR)%(MALA)%"
		AND amount > 0 AND type = 0 
		AND tran_date >= '."'".date2sql($_POST['from'])."'
		AND tran_date <= '".date2sql($_POST['to'])."'";
	db_query($sql,'failed to update transfers to MALABON');
	
	// update type 61 -------------------------------------------
	$sql = "SELECT type, type_no, MAX(tran_date) as date__
		FROM 0_gl_trans
		WHERE type = 61
		AND tran_date >= '".date2sql($_POST['from'])."'
		AND tran_date <= '".date2sql($_POST['to'])."'
		GROUP BY type,type_no";
	$res = db_query($sql);
	while($row = db_fetch($res))
		update_61($row);
	// ---------------------------------------------------------------
	
	
	// $sql = "SELECT type, type_no, ROUND(SUM(amount),2) as diff, tran_date
					// FROM 0_gl_trans
					// GROUP BY type, type_no
					// HAVING ROUND(SUM(amount),2) != 0
					// ORDER BY type, tran_date, ABS(ROUND(SUM(amount),2)) DESC";
					
	$sql = "SELECT type, type_no, SUM(round(amount,2)) as diff, tran_date, memo_
					FROM 0_gl_trans a
					WHERE a.account IN (SELECT account_code FROM 0_chart_master)
					AND tran_date >= '".date2sql($_POST['from'])."'
					AND tran_date <= '".date2sql($_POST['to'])."'
					GROUP BY type, type_no
					HAVING SUM(round(amount,2)) != 0";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
			fix_gl_entries($row['type'],$row['type_no'],$row['diff'],sql2date($row['tran_date']), $row['memo_']);
	}
	
	display_notification('...DONE...');
}

start_form();
start_table();

if (!isset($_POST['from']))
	$_POST['from'] = '01/01/'.date('Y');

date_row('Date From:', 'from');
date_row('Date To:', 'to');
end_table(1);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
