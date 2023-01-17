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

//FIX IN CASE OF DELETED MYSQL RECEIVING TRANSFER DATABASE

page('Fix Stock Transfer IN', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function db_insert_id2()
{
	global $db;
	return mysql_insert_id($db);
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT * FROM receiving_caravan.0_receiving";
	
	$res = db_query_rs($sql);
	
	// display_error($sql);
	
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mysql_fetch_array($res))
	{
		
			//$ReceivingID = $row['ReceivingID'];
			$id = $row['id'];
			$po_id = $row['po_id'];
			$PurchaseOrderNo = $row['po_no'];
			$Description = $row['supplier_name'];
			$Remarks = $row['inv_no'];
			$NetTotal = $row['inv_amount'];
			$DateReceived = $row['date_'];
			$ReceivedBy = $row['user_id'];
			//$ReceivingNo = $row['ReceivingNo'];
			
			
			// $sqlx = "SELECT user_id FROM receiving_new.0_users
						// WHERE id = '$ReceivedBy'";
			// $resx = db_query_rs($sqlx);
			// $rowx = db_fetch($resx);
			// $user_id=$rowx['user_id'];

			
			$sql = "INSERT INTO srs_new_receiving_cainta2.0_receiving (po_id,po_no,supplier_name,inv_no,inv_amount,date_,user_id,is_new)
			VALUES ('$po_id','$PurchaseOrderNo',".db_escape($Description).",".db_escape($Remarks).",'$NetTotal','$DateReceived','$ReceivedBy',1)";
			//display_error($sql);
			db_query_rs($sql,'failed to insert header');
			$temp_receiving_id = mysql_insert_id();
			
			$sql1 = "SELECT * FROM receiving_caravan.0_receiving_details
			where temp_receiving_id='$id'";
			$ms_res = db_query_rs($sql1);
			
			while($ms_row = mysql_fetch_array($ms_res))
			{
				$ProductID = $ms_row['prod_id'];
				$Barcode = $ms_row['barcode'];
				$Description = $ms_row['item_name'];
				$UOM = $ms_row['uom'];
				$qty = $ms_row['qty'];
				
				$sql2 = "INSERT INTO srs_new_receiving_cainta2.0_receiving_details(temp_receiving_id,prod_id,barcode,item_name,uom,qty)
				VALUES ($temp_receiving_id,'$ProductID','$Barcode',".db_escape($Description).",'$UOM','$qty')";
				db_query_rs($sql2,'failed to insert details');
				//display_error($sql2);
				
			}

	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
