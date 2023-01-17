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

	
page('Fix Stock OLD Transfer OUT using Transfer IN (VOID then create using TRANSFER IN) + set gl of type 72 to ZERO', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function new_trans_out($trans_type,$tran_date,$debit_account,$credit_account,$memo_,$amount)
{
	global $Refs;
	//====================
	// CREATE NEW JOURNAL ENTRY
    $trans_id = get_next_trans_no($trans_type);
	$reference = get_next_reference($trans_type);
	
	
	$debit = add_gl_trans($trans_type, $trans_id, $tran_date, $debit_account, 0, 0, $memo_, $amount);
	$credit = add_gl_trans($trans_type, $trans_id, $tran_date, $credit_account, 0, 0, $memo_, -$amount);
	//====================
	$Refs->save($trans_type, $trans_id, $reference);
	add_audit_trail($trans_type, $trans_id, $tran_date);

	return $trans_id;
	// return $reference;
}

if (isset($_POST['fix_now']))
{
	begin_transaction();
	
	
	// set GL of Stock Withdrawal to 0
	$sql = "UPDATE 0_gl_trans SET amount = 0 
				WHERE type = 72";
	db_query($sql);
	
	// void OUT
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
	
	// void OUT
	$sql = "SELECT DISTINCT type_no 
			FROM 0_gl_trans
			WHERE memo_ LIKE 'Transfer OUT (DR) to %(PO#%'
			AND amount != 0
			AND type = 0";
	$res = db_query($sql);
	
	// void newer OUT
	$sql = "SELECT DISTINCT type_no 
			FROM 0_gl_trans
			WHERE memo_ LIKE 'Transfer OUT : PO#%for branch :%'
			AND amount != 0
			AND type = 0";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{	
		$x = void_transaction('0', $row['type_no'], Today(), 'will generate a new one');
		// display_error($x . $row['type_no']);
	}
	

	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$sql = "SELECT gl_stock_to as due_from , gl_stock_from as due_to 
				FROM transfers.0_branches 
				WHERE code= '$this_branch'";
	// display_error($sql);die;
	$res = db_query($sql);
	$row = db_fetch($res);
	

	$due_to = $row['due_to'];
	
	global $db_connections;
	
	$ids = array();
	foreach($db_connections as $key=>$db_con)
	{
		// if ($key != 10)
			// continue;
		
		$sql = "SELECT gl_stock_to as due_from , gl_stock_from as due_to 
					FROM transfers.0_branches 
					WHERE code= '".$db_con['br_code']."'";
		// display_error($sql);die;
		$res = db_query($sql);
		$row = db_fetch($res);
		
		$due_from = $row['due_from'];
		
		if ($due_from == '')
			continue;
		// get IN from other branches
		$sql = "SELECT amount, tran_date, REPLACE(memo_,'Adjustment for Stock Transfer IN with ','') as for_po
					FROM ".$db_con['dbname'].".0_gl_trans
					WHERE account = $due_to
					AND memo_ LIKE 'Adjustment for Stock Transfer IN with PO#%'
					AND amount != 0
					AND type = 0";
		// display_error($sql);die;
		$res = db_query($sql);
		
		$credit_account = '570002'; // Stock Transfer Out - FIXED
		$debit_account = $due_from;
		
		while($row = db_fetch($res))
		{
			$memo_ = 'Transfer OUT : ' . $row['for_po'] .' for branch : '. strtoupper($db_con['srs_branch']);
			//  write GL of out to corresponding branch (ibang branch)
			$id = new_trans_out('0',sql2date($row['tran_date']),$debit_account, $credit_account, $memo_, -$row['amount']);
			$ids[] = $id;
		}
		
		display_notification('branch : ' . $db_con['srs_branch']);
		
	}
	
	display_notification('SUCCESS!!');
	
	// foreach($ids as $id)
		// display_notification($id);	
		
	commit_transaction();
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
