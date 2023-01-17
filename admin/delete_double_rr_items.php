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
$page_security = 'SA_SUPPLIERINVOICE';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/admin/db/voiding_db.inc");
$js = "";
if ($use_date_picker)
	$js .= get_js_date_picker();
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
	
page(_($help_context = "Void a Transaction"), false, false, "", $js);

//----------------------------------------------------------------------------------------
function delete_copycat($grn_id,$grn_item_id, $po_detail_item, $stock_id, $qty_recd_pcs)
{
	$sql = "DELETE FROM ".TB_PREF."grn_items
			WHERE id = $grn_item_id";
	db_query($sql, 'faild to delete copycat');
	
	$sql = "UPDATE ".TB_PREF."purch_order_details
		SET quantity_received = ((quantity_received + " .$qty_recd_pcs.") / multiplier),
		 quantity_received_pcs = quantity_received_pcs + ".$qty_recd_pcs."
		WHERE po_detail_item = ".db_escape($po_detail_item);
	db_query($sql, "a purchase order details record could not be updated.");
	
	$sql = "DELETE FROM ".TB_PREF."stock_moves
			WHERE trans_no = $grn_id
			AND type = 25
			AND stock_id = $stock_id
			AND qty_pcs = $qty_recd_pcs
			LIMIT 1";
	db_query($sql,'failed to delete 1 stock move');
}

begin_transaction();

$sql = "SELECT * FROM ".TB_PREF."grn_items";
$res = db_query($sql);

$last_grn_id = $last_po_detail_id = $last_item_code = 0;
$grn_batch_ids = array();
while($row = db_fetch($res))
{
	if ($last_grn_id == $row['grn_batch_id'] AND 
		$last_po_detail_id == $row['po_detail_item'] AND 
		$last_item_code == $row['item_code']) 
	{
		// display_error($row['id']);
		delete_copycat($row['grn_batch_id'],$row['id'], $row['po_detail_item'], $row['item_code'], $row['qty_recd_pcs']);
		
		if (!in_array($row['grn_batch_id'],$grn_batch_ids))
			$grn_batch_ids[] = $row['grn_batch_id'];
	}

	$last_grn_id = $row['grn_batch_id']; 
	$last_po_detail_id = $row['po_detail_item'];
	$last_item_code = $row['item_code'];
}

commit_transaction();
// foreach($grn_batch_ids as $grn_batch_id)
// {
	// display_error($grn_batch_id);
	
	// //reenter stock_moves here
// }
display_error(count($grn_batch_ids));

display_notification('SUCCESS!!!!!!!!!!!!!!!');
end_page();

?>