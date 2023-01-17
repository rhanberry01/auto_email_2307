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

	
page('Stock Transfer IN - MS Movement to ARIA (Net of VAT per Item)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function adjust_gl_entry($m_id, $m_no, $nettotal,$branch)
{
	begin_transaction();
	$sql = "SELECT id FROM transfers.0_transfer_header WHERE m_id_in = $m_id AND br_code_in = '$branch' AND transfer_in_date>='2017-01-01' and transfer_in_date<='2017-12-31'";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	
	if ($trans_no == '')
	{
		display_notification('HEADER NOT UPDATED for transfer in movement id # '. $m_id .' movement # '. $m_no);
		return true;
	}
	
	$sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 71 AND type_no = $trans_no AND account = '570001'";
	db_query($sql, 'failed to update gl_trans (positive)'. 'for m_id : '.$m_id );
	$sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 71 AND type_no = $trans_no AND account != '570001'";
	db_query($sql, 'failed to update gl_trans (negative)'. 'for m_id : '.$m_id );
	
	
	//correction_for_negative_amount($trans_no, $nettotal);
	
	commit_transaction();
}

function correction_for_negative_amount($trans_no, $nettotal)
{
	
	$sql = "SELECT SUM(amount), tran_date
				FROM 0_gl_trans WHERe type = 71
				and tran_date>='2017-01-01' and tran_date<='2017-12-31'
				AND type_no = $trans_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if ($row[0] == 0)
		return true;
	
	$tran_date = sql2date($row['tran_date']);
	
	$sql = "SELECT a.id, b.gl_stock_from
				FROM transfers.0_transfer_header a, transfers.0_branches b
				WHERE a.br_code_out = b.code
				AND a.id = $trans_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$gl_from = $row['gl_stock_from'];
	
	// delete previous entry
	$sql = "DELETE FROM 0_gl_trans WHERE type = 71 AND type_no = $trans_no";
	db_query($sql,'failed to delete wrong transfer in GL');
	
	//create new one
	// add_gl_trans(71, $trans_no, $tran_date, '570001', 0, 0, '', $row['amt']); 
	// add_gl_trans(71, $trans_no, $tran_date, $gl_from, 0, 0, '', -$row['amt']); 
}

//=========================================================

function get_net_of_vat_total($m_id, $branch)
{
	$sql = "SELECT b.* FROM transfers.0_transfer_header a, transfers.0_transfer_details b 
				WHERE a.id = b.transfer_id
				AND br_code_in = '$branch'
				AND a.m_id_in = $m_id";
	$res = db_query($sql);
	
	//display_error($sql); die;
	$total = 0;
	while($row = db_fetch($res))
	{
		$per_item_total = (get_net_of_vat_cost($row['stock_id'], $row['cost']) * $row['qty_in']);
		//$per_item_total = round(round(get_net_of_vat_cost_in_transfers_copy($branch, $row['stock_id_2'], $row['cost']),2) * $row['qty_in'] , 2);
		
		// display_error($per_item_total);
		$total += $per_item_total;
		
	}
	
	return $total;
}

function get_net_of_vat_cost($product_id, $cost)
{
	$tax_rate = 12;
	
	$sql = "SELECT pVatable FROM Products WHERE ProductID = $product_id";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	if (!$row[0])
		$tax_rate = 0;
	
	return ($cost / (1+($tax_rate/100)));
}

function get_net_of_vat_cost_in_transfers_copy($branch, $product_id, $cost)
{
	$tax_rate = 12;
	
	// $sql = "SELECT pVatable FROM Products WHERE ProductID = $product_id";
	// $res = ms_db_query($sql);
	// $row = mssql_fetch_array($res);
	$sql = "SELECT * FROM transfers.0_non_vat_items 
				WHERE branch_code = '$branch'
				AND stock_id = $product_id";
	$res = db_query($sql);
	
	if (db_num_rows($res) != 0) //in the table = NON VAT
		$tax_rate = 0;
	
	return ($cost / (1+($tax_rate/100)));
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT MovementID,MovementNo FROM Movements WHERE MovementCode = 'STI' and TransactionDate>='2017-01-01 00:00:00' AND TransactionDate<='2017-12-31 00:00:00'";
// $sql .= " AND MovementNo = '0000000146'";
//	$sql .= " AND MovementID = 30690";
	$res = ms_db_query($sql);
	
	// display_error($sql);
	
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mssql_fetch_array($res))
	{
		$m_id = $row[0];
		$m_no = $row[1];
		
		if ($m_id == 0)
			continue;
		
		$net_of_vat_total = get_net_of_vat_total($m_id,$this_branch);
		adjust_gl_entry($m_id,$m_no, round($net_of_vat_total,2),$this_branch);
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'Process');
end_form();

end_page();
?>
