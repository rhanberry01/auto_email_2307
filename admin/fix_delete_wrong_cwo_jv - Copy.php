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

	
page('Delete CWO wrong JV * reimport RR after this', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function delete_grn_and_po($grn_batch_id, $po)
{
	$sql = "DELETE FROM 0_grn_items WHERE grn_batch_id = $grn_batch_id";
	db_query($sql,'failed to delete grn_items');
	
	$sql = "DELETE FROM 0_grn_batch WHERE id = $grn_batch_id";
	db_query($sql,'failed to delete grn_batch');
	
	$sql = "DELETE FROM 0_purch_order_details WHERE order_no = $po";
	db_query($sql,'failed to delete po_details');
	
	$sql = "DELETE FROM 0_purch_orders WHERE order_no = $po";
	db_query($sql,'failed to delete grn_items');
}

if (isset($_POST['fix_now']))
{
	begin_transaction();
	$sql = "DELETE FROM 0_gl_trans WHERE type=0 AND person_type_id = 3";
	db_query($sql,'failed to delete wrong JV from CWO');
	commit_transaction();
	
	display_notification('CWO JV  - DONE!');
	
	begin_transaction();
	
	$sql = "SELECT c.* FROM 0_cwo_header a, 0_purch_orders b, 0_grn_batch c
				WHERE a.c_po_no = b.reference
				AND b.order_no = c.purch_order_no
				AND c.delivery_date <= '2015-05-18'";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		$grn_id = $row['id'];
		$po_no = $row['purch_order_no'];
		delete_grn_and_po($grn_id, $po_no);
	}
	
	commit_transaction();
	display_notification('DELETE GRN OF OLD CWO  - DONE!');
	
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
