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

	
page('Stock Transfer Out - MS Movement to ARIA (Net of VAT per Item)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function adjust_gl_entry($m_id, $m_no, $nettotal)
{
	begin_transaction();
	$sql = "SELECT id FROM transfers.0_transfer_header WHERE m_id_out = $m_id AND transfer_out_date>='2017-01-01' and transfer_out_date<='2017-12-31'";
	//display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	
	if ($trans_no == '')
	{
		display_notification('HEADER NOT UPDATED for transfer out movement id # '. $m_id .' movement # '. $m_no);
		return true;
	}
	
	//display_error($nettotal);
	// $sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 70 AND type_no = $trans_no AND amount > 0";
	$sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 70 AND type_no = $trans_no AND account = '570002'";
	//display_error($sql);
	db_query($sql, 'failed to update gl_trans (positive)'. 'for m_id : '.$m_id );
	$sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 70 AND type_no = $trans_no AND amount < 0";
	$sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 70 AND type_no = $trans_no AND account != '570002'";
	// display_error($sql);
	db_query($sql, 'failed to update gl_trans (negative)'. 'for m_id : '.$m_id );
	
	commit_transaction();
}

//=========================================================
function get_net_of_vat_total($m_id)
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
	and Movements.MovementCode='STO' 
	and Movements.MovementID='$m_id'
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

if (isset($_POST['fix_now']))
{
	$sql = "
	
	SELECT DISTINCT MovementID,MovementNo from (
	SELECT m.MovementID,m.MovementNo
	FROM [Movements] as m
	LEFT JOIN [Movementline] as ml
	ON m.MovementID=ml.MovementID
	where m.MovementCode='STO' 
	and CAST (m.PostedDate as Date)>='2017-01-01'
	and CAST (m.PostedDate as Date)<='2017-12-31'
	) x

	";
	//$sql .= " AND MovementID = 777694";
	$res = ms_db_query($sql);
	
	//display_error($sql);
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mssql_fetch_array($res))
	{
		$m_id = $row[0];
		$m_no = $row[1];
		
		if ($m_id == 0)
			continue;
		

		$net_of_vat_total = get_net_of_vat_total($m_id);
		adjust_gl_entry($m_id,$m_no, round($net_of_vat_total,2));
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'Process');
end_form();

end_page();
?>
