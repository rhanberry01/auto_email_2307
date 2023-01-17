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
				AND br_code_in = '$branch' and date_created>='2016-01-01'";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	
	if ($trans_no == '')
	{
		//display_notification('HEADER NOT UPDATED for transfer in movement id # '. $m_id .' movement # '. $m_no);
		
		$sqls = "SELECT       
		MovementID
		,MovementNo
		,MovementCode
		,cast(PostedDate as date) as PostedDate
		,NetTotal
		,TotalQty
		FROM Movements where MovementID=".$m_id." AND MovementCode='STI'";
		//display_error($sql);
		$po_res=ms_db_query($sqls);
		
		while($row = mssql_fetch_array($po_res))
		{
			$NetTotal=$row['NetTotal'];
			$TotalQty=$row['TotalQty'];
			$movement_id=$row['MovementID'];
			$movement_no=$row['MovementNo'];
			$tran_date=$row['PostedDate'];
			
			$tr_sql="SELECT * from (SELECT transfer_id,sum(td.actual_qty_out) as totalqty, sum(td.actual_qty_out*cost) as net FROM transfers.0_transfer_header as th
			LEFT JOIN transfers.0_transfer_details as td
			ON th.id=td.transfer_id
			where th.m_id_in=''
			and td.qty_in=0
			and th.br_code_in='srsal'
			and actual_qty_out!=0
			GROUP BY td.transfer_id) as a
			where totalqty='$TotalQty'
			and net='$NetTotal'";
			//display_error($tr_sql);
			$my_res=db_query($tr_sql,'error');
			
				while($mrow = db_fetch($my_res))		
				{
					$transfer_id=$mrow['transfer_id'];
					//display_error($transfer_id);
					
					if(!IS_NULL($mrow['transfer_id'])){
						
							$sqlu = "UPDATE transfers.0_transfer_header SET 
							aria_type_in = 71,
							aria_trans_no_in = $transfer_id,
							m_id_in = $movement_id,
							m_code_in = ".db_escape($branch).",
							m_no_in = '$movement_no',
							transfer_in_date = '".$tran_date."'
							WHERE id = $transfer_id";
							display_error($sqlu);
							db_query($sqlu,'failed to update transfer header');
						
						
										$sql_line = "SELECT  * FROM MovementLine where MovementID=".$m_id."";
										//display_error($sql);
										$sql_line_res=ms_db_query($sql_line);

										while($row_line = mssql_fetch_array($sql_line_res))
										{
												$qty_in=$row_line['qty'];
												$product_id_in=$row_line['ProductID'];
											
												$sqli = "UPDATE transfers.0_transfer_details SET qty_in = '$qty_in'  WHERE transfer_id = $transfer_id
												AND stock_id_2='$product_id_in'";
											display_error($sqli);
											db_query($sqli,'failed to post transfer in');
										}
					
					
					}
					
												$sqlb = "SELECT a.id, b.gl_stock_from
												FROM transfers.0_transfer_header a, transfers.0_branches b
												WHERE a.br_code_out = b.code
												AND a.id = $transfer_id";
												$resb = db_query($sqlb);
												$rowb = db_fetch($resb);

												$gl_from = $rowb['gl_stock_from'];
												
												$net_of_vat_total = get_net_of_vat_total($m_id,$branch);
					

												add_gl_trans(71, $transfer_id, sql2date($tran_date), '570001', 0, 0, '', $net_of_vat_total); 
												add_gl_trans(71, $transfer_id, sql2date($tran_date), $gl_from, 0, 0, '', -$net_of_vat_total); 
				}

			

		
		}
		
		
		
		
	}
	

	
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

function get_net_of_vat_total($m_id, $branch)
{
	$sql = "SELECT b.* FROM transfers.0_transfer_header a, transfers.0_transfer_details b 
				WHERE a.id = b.transfer_id
				AND br_code_in = '$branch'
				AND a.m_id_in = $m_id";
	$res = db_query($sql);
	
	// display_error($sql);
	$total = 0;
	while($row = db_fetch($res))
	{
		// $per_item_total = (round(get_net_of_vat_cost($row['stock_id_2'], $row['cost']),2) * $row['qty_in']);
		$per_item_total = round(round(get_net_of_vat_cost_in_transfers_copy($branch, $row['stock_id_2'], $row['cost']),2) * $row['qty_in'] , 2);
		
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
	$sql = "SELECT MovementID,MovementNo FROM Movements WHERE MovementCode = 'STI' and TransactionDate>='2016-01-01 00:00:00'";
	// $sql .= " AND MovementNo = '0000000146'";
	// $sql .= " AND MovementID = 39935";
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
		
		// adjust_movement_cost($m_id);
		// adjust_product_history($m_id);
		
		// $sql = "SELECT SUM(extended) FROM [dbo].[MovementLine] WHERE [MovementID] = '$m_id'";
		// $ms_res = ms_db_query($sql);
		// $ms_row = mssql_fetch_array($ms_res);
		
		// $nettotal = $ms_row[0];
		// adjust_movement_header($m_id,$nettotal);
		
		//$net_of_vat_total = get_net_of_vat_total($m_id,$this_branch);
		adjust_gl_entry($m_id,$m_no, round($net_of_vat_total,2),$this_branch);
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
