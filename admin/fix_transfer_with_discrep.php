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

	
page('Fix Stock Transfer IN', false, false, "", $js);
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
						SellingAreaIn = ".($row['qty'] * $row['pack']).",
						UnitCost = ".($row['unitcost'] / $row['pack'])."
					WHERE TransactionID = $m_id 
					AND MovementCode = 'STI'
					AND ProductID = ".$row['ProductID'];
		ms_db_query($sql);
	}
	
}

function adjust_movement_header($m_id, $nettotal)
{
	$sql = "UPDATE Movements SET NetTotal = $nettotal WHERE MovementID =  $m_id";
	ms_db_query($sql);
}

function adjust_gl_entry($m_id, $m_no, $nettotal,$branch)
{
	begin_transaction();
	$sql = "SELECT id FROM transfers.0_transfer_header WHERE m_id_in = $m_id
				AND br_code_in = '$branch'";
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
	
	
	correction_for_negative_amount($trans_no, $nettotal);
	
	commit_transaction();
}

function correction_for_negative_amount($trans_no, $nettotal)
{
	
	$sql = "SELECT SUM(amount), tran_date
				FROM 0_gl_trans WHERe type = 71
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
	add_gl_trans(71, $trans_no, $tran_date, '570001', 0, 0, '', $row['amt']); 
	add_gl_trans(71, $trans_no, $tran_date, $gl_from, 0, 0, '', -$row['amt']); 
}

function adjust_movement_cost($m_id)
{
	$sql = "SELECT b.* FROM transfers.0_transfer_header a, transfers.0_transfer_details b 
				WHERE a.id = b.transfer_id
				AND a.m_id_in = $m_id";
	$res = db_query($sql);
	
	// display_error($sql);
	while($row = db_fetch($res))
	{
		adjust_movement_line($m_id, $row['stock_id_2'], $row['cost']);
	}
}

function get_net_of_vat_total_based_on_qty_out($m_id_out,$transfer_id)
{
	$sql = "SELECT b.* FROM transfers.0_transfer_header a, transfers.0_transfer_details b 
				WHERE a.id = b.transfer_id
				AND a.m_id_out = $m_id_out
				AND a.id=$transfer_id";
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

function get_net_of_vat_total_based_on_qty_in($m_id_out,$transfer_id)
{
	$sql = "SELECT b.* FROM transfers.0_transfer_header a, transfers.0_transfer_details b 
				WHERE a.id = b.transfer_id
				AND a.m_id_out = $m_id_out
				AND a.id=$transfer_id";
	$res = db_query($sql);
	
	// display_error($sql);
	$total = 0;
	while($row = db_fetch($res))
	{
		$per_item_total = (get_net_of_vat_cost($row['stock_id'], $row['cost']) * $row['qty_in']);
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
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$sql="SELECT id, m_id_out, m_id_in,transfer_out_date FROM transfers.0_transfer_header
	where w_discrep=1
	and date_created>='2016-01-01'
	AND br_code_out = '$this_branch'
	AND m_id_out!=0
	AND journal_trans_no=0
	and id='7106'
	";
	$res = db_query($sql);
	//display_error($sql);
	
	$nettotal = $net_of_vat_total = 0;
	while($row = db_fetch($res))
	{
		$transfer_id = $row['id'];
		$m_id_out = $row['m_id_out'];
		$m_id_in = $row['m_id_in'];
		$transfer_out_date = $row['transfer_out_date'];
		
		// //NET TOTAL OUT
		// $sql_out="SELECT sum(cost*actual_qty_out) as total_cost_out FROM transfers.0_transfer_details
		// where transfer_id='$transfer_id'";
		// $res_out = db_query($sql_out);
		// $row_out=db_fetch($res_out);

		// $nettotal_out=$row_out['total_cost_out'];
		
		
		$nettotal_out = get_net_of_vat_total_based_on_qty_out($m_id_out,$transfer_id);
		$nettotal_out = round($nettotal_out,2);
		
		//$tran_date=sql2date($tran_date);
	
		
		// $sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 70 AND type_no = $trans_no AND amount > 0";
		$sql = "UPDATE 0_gl_trans SET amount = -$nettotal_out WHERE type = 70 AND type_no = $transfer_id AND account = '570002'";
		// display_error($sql);
		db_query($sql, 'failed to update gl_trans (positive)'. 'for m_id : '.$m_id_out );
		// $sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 70 AND type_no = $trans_no AND amount < 0";
		$sql = "UPDATE 0_gl_trans SET amount = $nettotal_out WHERE type = 70 AND type_no = $transfer_id AND account != '570002'";
		// display_error($sql);
		db_query($sql, 'failed to update gl_trans (negative)'. 'for m_id : '.$m_id_out );
		
		
		$nettotal_in = get_net_of_vat_total_based_on_qty_in($m_id_out,$transfer_id);
		
		
		$discrep=$nettotal_out-$nettotal_in;
		
		display_error($nettotal_out);
		display_error($nettotal_in);
		display_error($discrep);

		// //NET TOTAL IN
		// $sql_out="SELECT sum(cost*qty_in) as total_cost_out FROM transfers.0_transfer_details
		// where transfer_id='$transfer_id'";
		// $res_out = db_query($sql_out);
		// $row_out=db_fetch($res_out);

		// $nettotal_out=$row_out['total_cost_out'];
		
		$sql = "SELECT a.id, b.gl_stock_to
		FROM transfers.0_transfer_header a, transfers.0_branches b
		WHERE a.br_code_in = b.code
		AND a.id = $transfer_id";
		$res = db_query($sql);
		$row = db_fetch($res);
	
		$gl_to = $row['gl_stock_to'];
		
		
		//$date_='12/31/2016';
		$date_=sql2date($transfer_out_date);
		$ref   = $Refs->get_next(0);
		$memo_ = "Adjustment for Correcting Entry of Stock Transfer#: ".$transfer_id." -Discrepancy";
		
		$trans_type = ST_JOURNAL;

		$trans_id = get_next_trans_no($trans_type);
	
			add_gl_trans($trans_type, $trans_id, $date_, 570002, 0, 0, $memo_, $discrep);
			add_gl_trans($trans_type, $trans_id, $date_, $gl_to, 0, 0, $memo_, -$discrep);
			

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $date_, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $date_);

		
		$sqli = "UPDATE transfers.0_transfer_header SET journal_trans_no = '$trans_id'  WHERE transfer_id = $transfer_id";
		display_error($sqli);
		db_query($sqli,'failed to update transfer header');
		
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
