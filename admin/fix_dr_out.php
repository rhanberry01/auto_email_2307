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

	
page('Create Journal For DR Transfers (OUT)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
function get_gl_stock_to($br_code)
{
	$sql = "SELECT gl_stock_to 
				FROM transfers.0_branches a, transfers.supp_branch_link b
				WHERE a.code=b.br_code
				AND b.ms_br_code_out = ".db_escape($br_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function write_dr_out_jv($po_no , $to_branch_name, $tran_date, $amount, $br_code)
{
	global $Refs;
	$trans_type = 0;
	
	
	$memo_ = "Transfer OUT (DR) to $to_branch_name (PO#$po_no)";
	
	// $sql = "SELECT * FROM 0_gl_trans WHERE type= 0 AND memo_ = '$memo_' amount != 0";
	// $res = db_query($sql);
	
	// if (db_num_rows($res) > 0) // already done
		// return false;
		
	$credit_account = '570002'; // Stock Transfer Out - FIXED
	$debit_account = get_gl_stock_to($br_code);
	
	if ($debit_account == '')
	{
		display_error('No debit account for br code - '.$br_code);
		die;
	}
	
	//====================
	// CREATE NEW JOURNAL ENTRY
    $trans_id = get_next_trans_no($trans_type);
	$reference = get_next_reference($trans_type);
	
	
	$debit = add_gl_trans($trans_type, $trans_id, $tran_date, $debit_account, 0, 0, $memo_, $amount);
	$credit = add_gl_trans($trans_type, $trans_id, $tran_date, $credit_account, 0, 0, $memo_, -$amount);
	//====================
	$Refs->save($trans_type, $trans_id, $reference);
	add_audit_trail($trans_type, $trans_id, $date_);

	// return $trans_id;
	return $reference;
}

if (isset($_POST['fix_now']))
{
	begin_transaction();
	
	$sql = "SELECT DISTINCT type_no 
			FROM 0_gl_trans
			WHERE memo_ LIKE 'Transfer OUT (DR) to %(PO#%'
			AND amount != 0
			AND type = 0";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{	
		$x = void_transaction('0', $row['type_no'], Today(), 'will generate a new one');
		// display_error($x . $row['type_no']);
	}

	$sql = "SELECT a.MovementID,ReferenceNo, ToDescription, PostedDate, NetTotal, BranchCode , 
				SUM(CASE 
						WHEN c.pVatable = 1
						   THEN (b.extended/1.12) 
						   ELSE b.extended 
				   END) as net_of_vat
			FROM Movements a, MovementLine b, Products c
			WHERE MovementCode = 'D2BSR'
			AND Status = 2
			AND PostedDate >= '2016-01-01'
			AND a.MovementID = b.MovementID
			AND b.ProductID = c.ProductID
			AND BranchCode != ''
			GROUP BY  a.MovementID, ReferenceNo, ToDescription, PostedDate, NetTotal, BranchCode";
	// display_error($sql);
	$res = ms_db_query($sql);
	
	while($row = mssql_fetch_array($res))
	{
		$j_id = write_dr_out_jv($row['ReferenceNo'], $row['ToDescription'], mssql2date($row['PostedDate']), $row['net_of_vat'], $row['BranchCode']);
		if ($j_id);
			display_notification($j_id);
	}
	commit_transaction();
	
	display_notification('DR OUT  - DONE!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
