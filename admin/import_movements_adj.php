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

page('Import MS Movement to ARIA (Net of VAT per Item)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function adjust_gl_entry($nettotal,$branch,$tran_date,$code)
{
	global $Refs;
	begin_transaction();
		
		//INSERT CASE HERE:
		$trans_type = ST_INVMOVEMENTS;
	
		$ref = $Refs->get_next($trans_type);
		
		$memo_ = "Inventory Movements Entry for MovementCode: ".$code;

		$trans_id = get_next_trans_no($trans_type);

		add_gl_trans($trans_type, $trans_id, $tran_date, $debit, 0, 0, $memo_, $nettotal);
		add_gl_trans($trans_type, $trans_id, $tran_date, $credit, 0, 0, $memo_, -$nettotal);
		
		$Refs->save($trans_type, $trans_id, $ref);

		display_notification('Sucessful import for movement code '. $code .' dated '. $tran_date);
	
	commit_transaction();
}

//=========================================================
function get_mcode_net_of_vat_total($movementcode,$date)
{
	$sql = "SELECT ml.ProductID, ml.unitcost, ml.qty, ml.pack FROM Movements as m
	LEFT JOIN MovementLine as ml
	on m.MovementID=ml.MovementID
	WHERE cast (m.TransactionDate as DATE)='$date' 
	AND m.MovementCode='$movementcode'";
	$res = ms_db_query($sql);
	//display_error($sql);die;
	
	$total = 0;
	while($row = mssql_fetch_array($res))
	{
		$per_item_total = (get_net_of_vat_cost($row['ProductID'], $row['unitcost']) * ($row['qty']*$row['pack']));
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

if (isset($_POST['fix_now']))
{
	set_time_limit(0);
	
	$sql = "SELECT m.MovementCode  as code,cast (m.TransactionDate as DATE) as date FROM Movements as m
	LEFT JOIN MovementLine as ml
	on m.MovementID=ml.MovementID
	WHERE cast (m.TransactionDate as DATE)>='2017-01-01' 
	AND cast (m.TransactionDate as DATE)<='2017-01-31'
	AND m.MovementCode NOT IN ('ITI','ITO','SA2BO','R2SSA','PS','STI','STO','PSV','SW')
	GROUP BY m.TransactionDate,m.MovementCode
	ORDER BY m.MovementCode,m.TransactionDate";
	$res = ms_db_query($sql);
	// display_error($sql);
	
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mssql_fetch_array($res))
	{
		$code = $row[0];
		$date = $row[1];
		//$extended = $row[2];
		//	$to_desc = $row[3];
		
		$net_of_vat_total = get_mcode_net_of_vat_total($code,$date);
		adjust_gl_entry(round($net_of_vat_total,2),$this_branch,$date,$code);
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('Import', 'Process');
end_form();
end_page();
?>