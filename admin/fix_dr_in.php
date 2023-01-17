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

	
page('Create Journal For DR Transfers (IN)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
function write_journal_entries_special($trans_no , $tran_date, $amount, $credit_account, $po_no)
{
	global $Refs;
	$apv_type = 20;
	$trans_type = 0;

	$memo_ = 'Adjustment for Stock Transfer IN with PO#'.$po_no;
	
	// $sql = "SELECT * FROM 0_gl_trans WHERE type= 0 AND memo_ = '$memo_' AND amount != 0";
	// $res = db_query($sql);
	
	// if (db_num_rows($res) > 0) // already done
		// return false;
	
    $trans_id = get_next_trans_no($trans_type);
	$reference = get_next_reference($trans_type);

	
	//REVERSE ENTRIES HERE
	$sql = "SELECT * FROM 0_gl_trans WHERE type = 20 AND type_no = $trans_no";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		add_gl_trans($trans_type, $trans_id, $tran_date, $row['account'], 0, 0, $memo_, -$row['amount']);
	}
	
	//====================
	// CREATE NEW CORRECT ENTRY
	$debit_account = '570001'; //STOCK IN FIXED
	
	add_gl_trans($trans_type, $trans_id, $tran_date, $debit_account, 0, 0, $memo_, $amount);
	add_gl_trans($trans_type, $trans_id, $tran_date, $credit_account, 0, 0, $memo_, -$amount);
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
			WHERE memo_ LIKE 'Adjustment for Stock Transfer IN with PO#%'
			AND amount != 0
			AND type = 0";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{	
		$x = void_transaction('0', $row['type_no'], Today(), 'will generate a new one');
		// display_error($x . $row['type_no']);
	}
	
	$sql = "SELECT b.supp_ref, a.type, a.trans_no, a.ov_amount, c.tran_date, e.gl_stock_from,  a.special_reference 
				FROM 0_supp_trans a, 0_suppliers b, 0_gl_trans c, transfers.supp_branch_link d, transfers.0_branches e
				WHERE del_date >= '2016-01-01'
				AND a.type = 20
				AND a.supplier_id = b.supplier_id
				AND (b.supp_name LIKE '%SRS%' OR supp_name LIKE '%san roque%')
				AND (b.supp_name NOT LIKE 'san roque%(%)%')
				AND a.type = c.type
				AND a.trans_no = c.type_no
				AND c.account = 2000
				AND b.supp_ref = d.supp_ref
				AND d.br_code = e.code
				ORDER BY a.trans_no
				";
	// $sql = "SELECT b.supp_ref, a.type, a.trans_no, a.ov_amount, c.tran_date, e.gl_stock_from,  a.special_reference 
				// FROM 0_supp_trans a, 0_suppliers b, 0_gl_trans c, transfers.supp_branch_link d, transfers.0_branches e
				// WHERE del_date >= '2015-04-01'
				// AND del_date <= '2015-04-30'
				// AND a.type = 20
				// AND a.supplier_id = b.supplier_id
				// AND (b.supp_name LIKE '%SRS%' OR supp_name LIKE '%san roque%')
				// AND (b.supp_name NOT LIKE 'san roque%(%)%')
				// AND a.type = c.type
				// AND a.trans_no = c.type_no
				// AND c.account = 2000
				// AND b.supp_ref = d.supp_ref
				// AND d.br_code = e.code
				// AND b.supp_ref = 'SRV'
				// ORDER BY b.supp_ref";
	// display_error($sql);die;
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		$j_id = write_journal_entries_special($row['trans_no'] , sql2date($row['tran_date']), $row['ov_amount'],
			$row['gl_stock_from'],$row['special_reference']);
		if ($j_id)
			display_notification($j_id);
	}
	commit_transaction();
	
	display_notification('DR IN  - DONE!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
