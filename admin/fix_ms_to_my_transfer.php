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


//FIX IN CASE OF DELETED MYSQL RECEIVING TRANSFER IN DATABASE

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

function db_insert_id2()
{
	global $db;
	return mysql_insert_id($db);
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT MovementID,MovementNo,PostedBy,CAST(PostedDate as Date) as PostedDate
	FROM [srsval].[dbo].[Movements]
	where MovementCode='STI'
	and MovementNo>'0000001743'
	and PostedDate>='2017-12-06'";
	$res = ms_db_query($sql);
	
	// display_error($sql);
	
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mssql_fetch_array($res))
	{
		
			$MovementID = $row['MovementID'];
			$MovementNo = $row['MovementNo'];
			$PostedDate = $row['PostedDate'];
			$PostedBy = $row['PostedBy'];
			
			$sqlx = "SELECT user_id FROM receiving_new.0_users
						WHERE id = '$PostedBy'";
			$resx = db_query_rs($sqlx);
			$rowx = db_fetch($resx);
			$user_id=$rowx['user_id'];
			
			
			$sqlx1 = "SELECT id,br_code_out FROM transfers.0_transfer_header
			where m_no_in='$MovementNo'
			and m_id_in='$MovementID'
			and m_code_in='STI'
			and br_code_in='srsval'
			"
			;
			$resx1 = db_query($sqlx1,'failed to select details');
			$rowx1 = db_fetch($resx1);
			$transfer_id=$rowx1['id'];
			$br_code_out=$rowx1['br_code_out'];
			//display_error($sqlx1);
		
			
			$sql = "INSERT INTO receiving_new.0_receive_transfer(transfer_id,location_from,date_,user_id,posted)
			VALUES ($transfer_id,'$br_code_out','$PostedDate','$user_id',1)";
			//display_error($sql);
			db_query_rs($sql,'failed to insert header');
			$temp_receiving_id = mysql_insert_id();

			
			$sql1 = "SELECT [MovementID]
			  ,[ProductID]
			  ,[barcode]
			  ,[Description]
			  ,[UOM]
			  ,[qty]
			  FROM [MovementLine]
			  where MovementID=$MovementID";
			$ms_res = ms_db_query($sql1);
			
			while($ms_row = mssql_fetch_array($ms_res))
			{
				$ProductID = $ms_row['ProductID'];
				$Barcode = $ms_row['barcode'];
				$Description = $ms_row['Description'];
				$UOM = $ms_row['UOM'];
				$qty = $ms_row['qty'];
				
				$sql2 = "INSERT INTO receiving_new.0_receive_transfer_details(
				temp_receiving_id,
				prod_id,
				barcode,
				item_name,
				uom,
				qty)
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
