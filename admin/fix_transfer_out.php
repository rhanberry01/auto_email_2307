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

	
page('Fix Stock Transfer OUT', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function adjust_movement_line($m_id, $product_id, $cost)
{
	$sql = "UPDATE MovementLine SET 
					unitcost = $cost, 
					extended = (qty * $cost)
				WHERE MovementID = $m_id
				AND ProductID = $product_id";
	ms_db_query($sql);
	// display_error($sql);
}

function adjust_product_history($m_id)
{
	$sql = "SELECT ProductID, unitcost, qty, pack FROM MovementLine WHERE MovementID = $m_id";
	$res = ms_db_query($sql);
	while($row = mssql_fetch_array($res))
	{
		$sql = "UPDATE ProductHistory SET
						SellingAreaOut = ".($row['qty'] * $row['pack']).",
						UnitCost = ".($row['unitcost'] / $row['pack'])."
					WHERE TransactionID = $m_id 
					AND MovementCode = 'STO'
					AND ProductID = ".$row['ProductID'];
		ms_db_query($sql);
	}
}

function adjust_movement_header($m_id, $nettotal)
{
	$sql = "UPDATE Movements SET NetTotal = $nettotal WHERE MovementID =  $m_id";
	ms_db_query($sql);
}

function adjust_gl_entry($m_id, $m_no, $nettotal)
{
	begin_transaction();
	$sql = "SELECT id FROM transfers.0_transfer_header WHERE m_id_out = $m_id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	
	if ($trans_no == '')
	{
		display_notification('HEADER NOT UPDATED for transfer out movement id # '. $m_id .' movement # '. $m_no);
		return true;
	}
	
	// display_error($nettotal);
	// $sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 70 AND type_no = $trans_no AND amount > 0";
	$sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 70 AND type_no = $trans_no AND account = '570002'";
	// display_error($sql);
	db_query($sql, 'failed to update gl_trans (positive)'. 'for m_id : '.$m_id );
	// $sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 70 AND type_no = $trans_no AND amount < 0";
	$sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 70 AND type_no = $trans_no AND account != '570002'";
	// display_error($sql);
	db_query($sql, 'failed to update gl_trans (negative)'. 'for m_id : '.$m_id );
	
	correction_for_negative_amount($trans_no, $nettotal);
	
	commit_transaction();
}

function correction_for_negative_amount($trans_no, $nettotal)
{
	
	$sql = "SELECT SUM(amount), tran_date
				FROM 0_gl_trans WHERe type = 70
				AND type_no = $trans_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if ($row[0] == 0)
		return true;
	
	$tran_date = sql2date($row['tran_date']);
	
	$sql = "SELECT a.id, b.gl_stock_to
				FROM transfers.0_transfer_header a, transfers.0_branches b
				WHERE a.br_code_in = b.code
				AND a.id = $trans_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$gl_to = $row['gl_stock_to'];
	
	// delete previous entry
	$sql = "DELETE FROM 0_gl_trans WHERE type = 70 AND type_no = $trans_no";
	db_query($sql,'failed to delete wrong transfer in GL');
	
	//create new one
	add_gl_trans(70, $trans_no, $tran_date, $gl_to, 0, 0, '', $row['amt']); 
	add_gl_trans(70, $trans_no, $tran_date, '570002', 0, 0, '', -$row['amt']); 
}

function adjust_movement_cost($m_id)
{
	$sql = "SELECT b.* FROM transfers.0_transfer_header a, transfers.0_transfer_details b 
				WHERE a.id = b.transfer_id
				AND a.m_id_out = $m_id";
	$res = db_query($sql);
	
	// display_error($sql);
	while($row = db_fetch($res))
	{
		adjust_movement_line($m_id, $row['stock_id'], $row['cost']);
	}
}

function get_net_of_vat_total($m_id)
{
	$sql = "SELECT b.* FROM transfers.0_transfer_header a, transfers.0_transfer_details b 
				WHERE a.id = b.transfer_id
				AND a.m_id_out = $m_id";
	$res = db_query($sql);
	
	// display_error($sql);
	$total = 0;
	while($row = db_fetch($res))
	{
		$per_item_total = (get_net_of_vat_cost($row['stock_id'], $row['cost']) * $row['actual_qty_out']);
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

if (isset($_POST['fix_now']))
{
	$sql = "SELECT MovementID,MovementNo FROM Movements WHERE MovementCode = 'STO' and TransactionDate>='2016-01-01 00:00:00' ";
	// $sql .= " AND MovementID = 42732";
	$res = ms_db_query($sql);
	
	// display_error($sql);
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mssql_fetch_array($res))
	{
		$m_id = $row[0];
		$m_no = $row[1];
		
		if ($m_id == 0)
			continue;
		
		// adjust_movement_cost($m_id);
		// adjust_product_history($m_id);
		
		// $sql = "SELECT SUM(extended) FROM [dbo].[MovementLine] WHERE [MovementID] = '$m_id'";
		// $ms_res = ms_db_query($sql);
		// $ms_row = mssql_fetch_array($ms_res);
		
		// $nettotal = $ms_row[0];
		// adjust_movement_header($m_id,$nettotal);
		
		$net_of_vat_total = get_net_of_vat_total($m_id);
		adjust_gl_entry($m_id,$m_no, round($net_of_vat_total,2));
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
