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

	
page('FIX COST OF SALES'  , false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
//-----------------------------------------------------------------------------------
function get_item_received_unit_cost($receiving_id, $stock_id)
{
	$sql = "SELECT extended/pack/qty FROM ReceivingLine
					WHERE ReceivingID = $receiving_id
					AND ProductID = $stock_id";
	//+++++++++++++++$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return round($row[0],4);
}

function get_item_movement_unit_cost($movement_id, $stock_id)
{
	$sql = "SELECT extended/pack/qty FROM MovementLine
					WHERE MovementID = $movement_id
					AND ProductID = $stock_id";
	//+++++++++++++++$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return round($row[0],4);
}


function update_movements($stock_id, $date_, $cost, $movements_to_be_updated)
{
	// check if adjustment has record on ARIA, then update its GL entry
	foreach($movements_to_be_updated as $mm)
	{
		$movement_code = $mm[0];
		$movement_id = $mm[1];
		
		if ($movement_id == 0)
				continue;
		// display_notification($movement_code);
		// display_notification($movement_id);
		// display_notification('~~');
		
		$adj_row = get_adjustment_header($movement_code,$movement_id);
		if ($adj_row) // update ARIA
		{
			// update GL in ARIA
			update_adjustment_gl($adj_row['a_type'], $adj_row['a_trans_no'], $stock_id, $cost);
		}
		
		$sql = "SELECT $cost-(extended/pack/qty) FROM MovementLine
						WHERE MovementID = $movement_id
						AND ProductID = $stock_id";
		$res = ms_db_query($sql);
		$row = mssql_fetch_array($res);
		$diff_ms = round($row[0],4);
		
		// update MSSQL movement line
		$sql = "UPDATE MovementLine SET unitcost = $cost, extended = ROUND($cost*pack*qty,4)
						WHERE MovementID = $movement_id AND ProductID = $stock_id";
		// display_notification($sql);
		//+++++++++++++++ms_db_query($sql);
		
		// update MSSQL movement header
		$sql = "UPDATE Movements SET NetTotal = NetTotal-$diff_ms WHERE MovementID = $movement_id AND MovementCode='$movement_code'";
		// display_notification($sql);
		//+++++++++++++++ms_db_query($sql);
	}
}


function update_finished_sales($stock_id, $date_, $avg_cost)
{
	$sql = "UPDATE FinishedSales SET
					AverageUnitCost = $avg_cost
					WHERE ProductID = $stock_id
					AND CAST(LogDate as DATE)= '".date2sql($date_)."'";
	//+++++++++++++++ms_db_query($sql);
	}
	
	function update_products_table($stock_id, $avg_cost, $sa, $bo)
{
	$sql = "UPDATE Products SET
					SellingArea = $sa,
					Damaged = $bo,
					CostOfSales = $avg_cost
					WHERE ProductID = $stock_id";
	//+++++++++++++++ms_db_query($sql);
}
	
function recompute_item_cost_of_sales($stock_id, $start_date)
{

	$to_date = Today();
	$times = date_diff2($to_date, $start_date, 'd');
		
		
		//get history by lne id
		$sql = "SELECT MovementCode, TransactionID, Description, DatePosted, BeginningSellingArea, BeginningDamaged,
							SellingAreaIn, SellingAreaOut, DamagedIn, DamagedOut , UnitCost,
							(CASE
								WHEN MovementCode IN('_DR', 'STI', 'R2SSA','ITI')THEN
									1
								ELSE
									0
								END
							)AS recomp
							FROM ProductHistory
						WHERE ProductID = $stock_id
						AND CAST(DatePosted as DATE) = CONVERT(DATE, '$start_date', 101)
						AND MovementCode != 'NOSLE'
						ORDER BY recomp DESC,LineID DESC";

		//+++++++++++++++$ms_res = ms_db_query($sql);
		$with_computation = array('_DR', 'STI', 'R2SSA','ITI');
	
		$movements_to_be_updated = array();
		while($ms_row = mssql_fetch_array($ms_res))
		{
			if (!in_array($ms_row['MovementCode'], $with_computation) ) // skip computation for movements not in array and old adjustments
			{
				 $sa_beg += $ms_row['SellingAreaIn'] - $ms_row['SellingAreaOut'] ;
				 $bo_beg += $ms_row['DamagedIn'] - $ms_row['DamagedOut'] ;
				 $movements_to_be_updated[] = array($ms_row['MovementCode'],$ms_row['TransactionID']);
				 // display_notification('SA : ' . $sa_beg .'');
				continue;
			}
			
			//recompute per transaction if transaction is receiving, free receiving, transfer in, returns, adjustments
			$totalqtymoved =  ($ms_row['SellingAreaIn'] - $ms_row['SellingAreaOut']) + ($ms_row['DamagedIn'] - $ms_row['DamagedOut']);
			
			$unit_cost = $ms_row['UnitCost'];
			if ($ms_row['MovementCode'] == '_DR' ) // for receiving or free
			{
				if ($ms_row['UnitCost'] != 0) // not free
				{
					$unit_cost = get_item_received_unit_cost($ms_row['TransactionID'], $stock_id);
				}
				else// if _DR but 0 unit cost  = FREE
				{
					$unit_cost = 0;
				}
			}
			else
			{
				$unit_cost = get_item_movement_unit_cost($ms_row['TransactionID'], $stock_id);
			}
			
			// computation of cost
			// $old_stock =  $sa_beg_c + $bo_beg_c;
			$old_stock =  $sa_beg + $bo_beg;
			$old_stock_cos = $old_stock * $cos_beg;

			$new_cos = $cos_beg;
			$extended_price = $totalqtymoved * $unit_cost;
			
			if($old_stock+$totalqtymoved != 0)
			{
				$old_stock_cos_ext = $old_stock_cos + $extended_price;
				$new_cos = round($old_stock_cos_ext/($old_stock+$totalqtymoved),4);
				// display_notification('Computation : ((' . $sa_beg .' + '. $bo_beg .') * '.$cos_beg .')+('. $totalqtymoved .' * '. $unit_cost .') / ('.$old_stock.'+'.$totalqtymoved.')');
			}
			
			$sa_beg += $ms_row['SellingAreaIn'] - $ms_row['SellingAreaOut'] ;
			$bo_beg += $ms_row['DamagedIn'] - $ms_row['DamagedOut'] ;
			
			$cos_beg = round($new_cos,4);
		}
		
		
		// negative checker
		$adj_sa = $adj_bo = 0;

		
		update_movements($stock_id, $beg_date, $cos_beg, $movements_to_be_updated);
		
		update_finished_sales($stock_id, $beg_date, $cos_beg);

		$start_date = add_days($start_date,1);
	
	
	if ($cos_beg != 0)
		update_products_table($stock_id, $cos_beg, $sa_beg, $bo_beg);
	
	return true;
}


if (isset($_POST['fix_now']))
{
	
	$start_date = $_POST['from'];
	$stock_id=143834;
	
	recompute_item_cost_of_sales($stock_id, $start_date);
	
	display_notification('DONE');
}

if(!isset($_POST['from']))
	$_POST['from'] = '01/01/2016';

start_form();
start_table($table_style2);
date_row('DATE from:', 'from');
// date_row('DATE to:', 'to');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
