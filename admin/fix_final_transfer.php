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

	
page('Fix Transfers based on audited values', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
function write_dr_out_jv($movement_no , $to_branch_name, $tran_date, $amount, $due_to)
{
	global $Refs;
	$trans_type = 0;
	
	
	$memo_ = "Transfer OUT (DR) to $to_branch_name (Movement # $movement_no)";
	
	$sql = "SELECT * FROM 0_gl_trans WHERE type= 0 AND memo_ = '$memo_' ";
	$res = db_query($sql);
	
	if (db_num_rows($res) > 0) // already done
		return false;
		
    $trans_id = get_next_trans_no($trans_type);
	$reference = get_next_reference($trans_type);
	// display_error($trans_id);
	//====================
	// CREATE NEW JOURNAL ENTRY

	$credit_account = '570002'; // Stock Transfer Out - FIXED
	$debit_account = $due_to;
	
	$debit = add_gl_trans($trans_type, $trans_id, $tran_date, $debit_account, 0, 0, $memo_, $amount);
	$credit = add_gl_trans($trans_type, $trans_id, $tran_date, $credit_account, 0, 0, $memo_, -$amount);
	//====================
	$Refs->save($trans_type, $trans_id, $reference);
	add_audit_trail($trans_type, $trans_id, Today());

	// return $trans_id;
	return $reference;
}

function write_dr_in_jv($movement_no , $from_branch_name, $tran_date, $amount, $due_from)
{
	global $Refs;
	$trans_type = 0;
	
	
	$memo_ = "Transfer IN (DR) from $from_branch_name (Movement # $movement_no )";
	
	$sql = "SELECT * FROM 0_gl_trans WHERE type= 0 AND memo_ = '$memo_' ";
	$res = db_query($sql);
	
	if (db_num_rows($res) > 0) // already done
		return false;
		
    $trans_id = get_next_trans_no($trans_type);
	$reference = get_next_reference($trans_type);
	// display_error($trans_id);
	//====================
	// CREATE NEW JOURNAL ENTRY

	$debit_account = '570001'; // Stock Transfer Out - FIXED
	$credit_account = $due_from;
	
	$debit = add_gl_trans($trans_type, $trans_id, $tran_date, $debit_account, 0, 0, $memo_, $amount);
	$credit = add_gl_trans($trans_type, $trans_id, $tran_date, $credit_account, 0, 0, $memo_, -$amount);
	//====================
	$Refs->save($trans_type, $trans_id, $reference);
	add_audit_trail($trans_type, $trans_id, Today());

	// return $trans_id;
	return $reference;
}

function void_journal_out()
{
	//jan to march only
	//void Transfer OUT created from DR.================================================
	$sql = "SELECT * FROM 0_gl_trans WHERE memo_ LIKE 'Transfer OUT (DR)%' AND tran_date < '2016-04-01' AND amount != 0";
	db_query($sql);
	$res = db_query($sql);
	$memo_ = ' will be replaced by audited amount';
	while($row = db_fetch($res))
	{
		void_bank_trans($row['type'], $row['type_no'],true);
		add_audit_trail($row['type'], $row['type_no'], Today(), _("Voided.")."\n".$memo_);
		add_voided_entry($row['type'], $row['type_no'], Today(), $memo_);
	}
	//==========================================================================
}

function void_journal_in()
{
	//jan to march only
	//void Transfer OUT created from DR.================================================
	// $sql = "SELECT * FROM 0_gl_trans WHERE memo_ LIKE 'Adjustment for Stock Transfer IN with PO#%' AND tran_date < '2015-04-01' AND amount != 0";
	// db_query($sql);
	// $res = db_query($sql);
	// $memo_ = ' will be replaced by audited amount';
	// while($row = db_fetch($res))
	// {
		// void_bank_trans($row['type'], $row['type_no'],true);
		// add_audit_trail($row['type'], $row['type_no'], Today(), _("Voided.")."\n".$memo_);
		// add_voided_entry($row['type'], $row['type_no'], Today(), $memo_);
	// }
	//==========================================================================
	
	$sql = "DELETE FROM 0_gl_trans 
				WHERE memo_ LIKE 'Adjustment for Stock Transfer IN with PO#%' 
				AND amount != 0
				AND ((account = 570001 and amount > 0)
				OR (account LIKE '2350%' and amount < 0))";
	db_query($sql,'failed to delete transfer in');
}

function create_journal_out($due_from, $start_date, $end_date)
{
	// for transfer OUT ===========================
	
	$sql = "SELECT date_, movement_no, due_to, to_desc, (a_total/1.12) as net_of_vat FROM transfers.consolidated 
			WHERE due_from =  '$due_from'
			AND date_ >= '".date2sql($start_date)."'
			AND date_ <= '".date2sql($end_date)."'";
	// display_error($sql);die;
	$res = db_query($sql);
	
	$sql = "SELECT SUM(amount) WHERE account = ";
	while($row = db_fetch($res))
	{
		// display_error('ok');die;
		write_dr_out_jv($row['movement_no'] , $row['to_desc'], sql2date($row['date_']), round($row['net_of_vat'],2), $row['due_to']);
	}
}

function create_journal_in($due_to, $start_date, $end_date)
{
	// for transfer OUT ===========================
	
	$sql = "SELECT date_, movement_no, due_from, from_desc, (a_total/1.12) as net_of_vat FROM transfers.consolidated 
			WHERE due_to =  '$due_to'
			AND date_ >= '".date2sql($start_date)."'
			AND date_ <= '".date2sql($end_date)."'";
	// display_error($sql);die;
	$res = db_query($sql);
	
	$sql = "SELECT SUM(amount) WHERE account = ";
	while($row = db_fetch($res))
	{
		// display_error('ok');die;
		write_dr_in_jv($row['movement_no'] , $row['from_desc'], sql2date($row['date_']), round($row['net_of_vat'],2), $row['due_from']);
	}
}


if (isset($_POST['fix_now']))
{
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];

	$sql = "SELECT gl_stock_to as due_from , gl_stock_from as due_to 
				FROM transfers.0_branches 
				WHERE code= '$this_branch'";
	// display_error($sql);die;
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$due_from = $row['due_from'];
	$due_to = $row['due_to'];
	
	
	begin_transaction();
	
	void_journal_out();
	void_journal_in();
	
	// loop january - march
	for($i=1;$i<=3;$i++)
	{
		
		$start_date = "0$i/01/2016";
		$end_date = end_month("0$i/01/2016");
		
		create_journal_out($due_from, $start_date, $end_date);
		create_journal_in($due_to, $start_date, $end_date);
		
	}
	
	commit_transaction();
	
	display_notification('TRANSFERS  - DONE!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
