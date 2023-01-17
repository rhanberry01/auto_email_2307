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
//login to where in
	
page('Stock Transfer IN - MS Movement to ARIA (Net of VAT per Item)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function adjust_gl_entry($m_id, $m_no, $nettotal,$branch)
{
	begin_transaction();
	$sql = "SELECT id,br_code_out,DATE(transfer_in_date) as transfer_in_date FROM transfers.0_transfer_header 
	WHERE m_id_in = '$m_id' AND br_code_in = '$branch' 
	AND (transfer_in_date>='2017-01-01' and transfer_in_date<='2017-12-31')
	";
	//display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	$branch_out = $row[1];
	$tran_date = sql2date($row[2]);
	
	if ($trans_no == '')
	{
		display_notification('HEADER NOT UPDATED for transfer in movement id # '. $m_id .' movement # '. $m_no);
		return true;
	}
	
		$sql1 = "SELECT b.gl_stock_from
					FROM transfers.0_branches b
					WHERE b.code= '$branch_out'";
					//display_error($sql1);
		$res1= db_query($sql1);
		$row1 = db_fetch($res1);
		
		$gl_from = $row1['gl_stock_from'];
		$memo_ = "Transfer,  MovementID#: ".$m_id;
		
		$sql = "DELETE FROM 0_gl_trans WHERE type = 71 AND type_no = $trans_no";
		db_query($sql,'failed to delete wrong transfer in GL');
		
		add_gl_trans(71, $trans_no, $tran_date, $gl_from, 0, 0, $memo_, -$nettotal);
		add_gl_trans(71, $trans_no, $tran_date, '570001', 0, 0, $memo_, $nettotal);
	
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
	$sql = "SELECT 
	MovementLine.MovementID,
	ROUND(SUM(CASE WHEN Products.pVatable = 1
	THEN ROUND((ROUND((extended/1.12),2)),2)
	ELSE ROUND((ROUND((extended),2)),2) END),2) AS net_of_vat
	from MovementLine inner join Movements
	on MovementLine.MovementID = Movements.MovementID inner join
	Products on Products.ProductID = MovementLine.ProductID
	inner join MovementTypes on Movements.MovementCode = MovementTypes.MovementCode
	where (CAST (Movements.TransactionDate  as DATE) >=  '2017-01-01' 
	and  CAST (Movements.TransactionDate  as DATE)<='2017-12-31') 
	and  Movements.status = 2
	and Movements.MovementCode='STI' 
	and Movements.MovementID IN ($m_id)
	group by  MovementLine.MovementID";
	$res = ms_db_query($sql);
		
	 //display_error($sql);
	$total = 0;
	while($row = mssql_fetch_array($res))
	{
		$per_item_total = $row['net_of_vat'];
		//display_error($per_item_total);
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
	// $sql = "SELECT MovementID,MovementNo FROM Movements WHERE MovementCode = 'STI' and TransactionDate>='2017-01-01 00:00:00' AND TransactionDate<='2017-12-31 00:00:00'";
	// // $sql .= " AND MovementNo = '0000000146'";
	// // $sql .= " AND MovementID = 39935";
	// $res = ms_db_query($sql);
	
	// display_error($sql);
	
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
		$sql = "SELECT * FROM transfers.0_transfer_header
		where transfer_in_date>='2017-01-01'
		and transfer_in_date<='2017-12-31'
		AND (spc_transfer_in_date>='2017-01-01' and spc_transfer_in_date<='2017-12-01')
		and m_id_in like '%,%'
		and br_code_in = '$this_branch'";
		$res = db_query($sql);
//display_error($sql); die();

	$nettotal = $net_of_vat_total = 0;
	while($row = db_fetch($res))
	{
		$m_id = $row['m_id_in'];
		$m_no = $row['m_no_in'];
		
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
