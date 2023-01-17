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
$page_security = 'SA_BANKACCOUNT';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");

page('Update RS Movement Line', false, false,'', '');
//-----------------------------------------------------------------------------------
function add_ms_movement_line($MovementID,$ProductID,$ProductCode,$Description,
				$UOM,$unitcost,$qty,$pack,$barcode)
{
	$sql = "INSERT INTO MovementLine (MovementID,ProductID,ProductCode,Description,
				UOM,unitcost,qty,extended,pack,barcode)
			VALUES ($MovementID,$ProductID,'$ProductCode','$Description',
				'$UOM',$unitcost,$qty,".round($unitcost*$qty,4).",$pack,'$barcode')";
	ms_db_query($sql);
}

function delete_movement_lines($MovementID)
{
	$sql = "DELETE FROM MovementLine WHERE MovementID=$MovementID";
	// display_error($sql);
	ms_db_query($sql);
}

if (isset($_POST['update']))
{
	$sql = "SELECT a.movement_type, a.movement_no, a.rs_id
			FROM 0_rms_header a
			WHERE movement_no != 0
			AND rs_date >= '2013-11-14'
			ORDER BY movement_no";
	$res = db_query_rs($sql);
	display_notification(db_num_rows($res));
	
	while($h_row = db_fetch($res))
	{
		$sql = "SELECT MovementID FROM Movements 
				WHERE MovementNo = ".str_pad($h_row['movement_no'], 10, "0", STR_PAD_LEFT)."
				AND MovementCode = '".$h_row['movement_type']."'";
		$m_res = ms_db_query($sql);
		$m_row = mssql_fetch_array($m_res);
		$movement_id = $m_row['MovementID'];
		// display_error($movement_id);
		
		delete_movement_lines($movement_id);
		
		$sql = "SELECT prod_id,barcode,item_name,uom,SUM(qty) as qty,orig_uom,orig_multiplier,
					custom_multiplier,price,supplier_code
			FROM 0_rms_items WHERE rs_id IN (".$h_row['rs_id'].")
			GROUP BY prod_id,barcode,item_name,uom,orig_uom,orig_multiplier,
				custom_multiplier,price,supplier_code;";

		$res = db_query_rs($sql);
			
		while($row = db_fetch($res)) //Products and Product History
		{
			$pack = $row['custom_multiplier'] == 0 ? $row['orig_multiplier'] : $row['custom_multiplier'];
			add_ms_movement_line($movement_id,$row['prod_id'],$pos_prod_row['ProductCode'],$row['item_name'],
					$row['uom'],$row['price'],$row['qty'],$pack,$row['barcode']);
		}
	}
	
	display_notification('Success');
}

start_form();
submit_center('update', 'UPDATE MovementLine');
end_form();

end_page();
?>
