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
$page_security = 'SA_SETUPCOMPANY';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

page('Fix Open Po Dates', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------

function fix_me($row)
{
	$sql = "SELECT DateCreated FROM Receiving WHERE ReceivingNo = '". $row['reference'] ."'";
	$res = ms_db_query($sql);
	$ms_row = mssql_fetch_array($res);
	
	$date_rec = mssql2date($ms_row[0]);
	
	$sql = "UPDATE 0_grn_batch SET delivery_date = '".date2sql($date_rec)."' WHERE id = ". $row['id'];
	db_query($sql,'failed to update 0_grn_batch');
	
	$sql = "UPDATE 0_purch_orders SET ord_date = '".date2sql($date_rec)."' WHERE order_no = ". $row['purch_order_no'];
	db_query($sql,'failed to update 0_purch_orders');
	
}

if (isset($_POST['fixer']))
{
	global $db_connections;
	
	// update ms part
		$sql = "UPDATE Receiving SET DateReceived = DateCreated
				WHERE DateReceived < '2000-01-01'
				AND PurchaseOrderNo LIKE 'OP%' ";
		ms_db_query($sql);
		
	// =======================================================

	$sql = "SELECT * FROM `0_grn_batch` WHERE delivery_date < '2000-01-01' AND reference != ''";
	$res = db_query($sql);
	
	begin_transaction();
	while($row = db_fetch($res))
	{
		fix_me($row);
	}

	display_notification('It is finished...');
	commit_transaction();
}

start_form();
submit_center('fixer', 'FIX FIX FIX FIX FIX FIX ');
end_form();

end_page();
?>
