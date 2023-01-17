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

set_time_limit(0);
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");


include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Mark Receiving for Belen Tan"), false, false, "", $js);
//---------------------------------------------------------------------------------------------------------------
function flag_rr_for_belen_tan($id)
{
	$sql = "UPDATE ".TB_PREF."grn_batch SET rcomments='belen_tan'
			WHERE id = $id";
	db_query($sql,'failed to update rcomments');
}

function get_rr_total_using_po_price($grn_id)
{
	$sql = "SELECT SUM(a.qty_recd *(SELECT extended/quantity_ordered 
									FROM 0_purch_order_details 
									WHERE po_detail_item = a.po_detail_item)) 
			FROM `0_grn_items` a 
			WHERE `grn_batch_id` = $grn_id ";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

$mark_id = find_submit('flag_me');
if ($mark_id != -1)
{
	global $Ajax;
	flag_rr_for_belen_tan($mark_id);
	$Ajax->activate('table_');
}

start_form();
div_start('table_');
display_heading('Received from September 1, 2013 onwards.');
br();

// // get tagged CVs
// $sql = "SELECT b.cv_id FROM 0_cv_header a, 0_cv_details b
		// WHERE cv_no like '%R%'
		// AND a.id = b.cv_id
		// AND trans_type = 20
		// AND voided = 0
		// ORDER BY trans_type";
// $res = db_query($sql);
// // display_error($sql);
// $cv_ids = array();

// while($row = db_fetch($res))
// {
	// $cv_ids[] = $row[0];
// }

// // get rr of cv_id
// $rr_ids = '';
// if (count($cv_ids) > 0)
// {
	// $rr_ids = array();
	// $sql = "SELECT DISTINCT b.id FROM 0_supp_invoice_items a, 0_grn_batch b, 0_grn_items c
		// WHERE supp_trans_no IN (".implode(',',$cv_ids).")
		// AND supp_trans_type = 20
		// AND a.grn_item_id = c.id 
		// AND b.id = c.grn_batch_id";
	// // display_error($sql);
	// $res = db_query($sql);
	// while($row = db_fetch($res))
	// {
		// $rr_ids[] = $row[0];
	// }
	
	// $rr_ids = implode(',',$rr_ids);
// }

// $sql = "SELECT a.id, a.delivery_date, c.supp_name, b.reference as po_number, a.purch_order_no, a.source_invoice_no as invoice_no
		// FROM 0_grn_batch a, 0_purch_orders b, 0_suppliers c
		// WHERE a.delivery_date >= '2013-09-01'
		// AND a.purch_order_no = b.order_no
		// AND a.supplier_id = c.supplier_id
		// AND a.rcomments = ''".
		// ($rr_ids != '' ? " AND a.id NOT IN ($rr_ids)": '');
// // display_error($sql);

$sql = "SELECT MIN(b.id), CONCAT(c.supp_name, '  --  ', b.source_invoice_no) as disp,
			b.id as id, b.delivery_date, c.supp_name, d.reference as po_number, b.purch_order_no, b.source_invoice_no as invoice_no
				FROM 0_grn_items a , 0_grn_batch b, 0_suppliers c, 0_purch_orders d
				WHERE quantity_inv_pcs < qty_recd_pcs
				AND a.grn_batch_id = b.id 
				AND b.purch_order_no = d.order_no
				AND b.source_invoice_no != 'NULL'
				AND b.source_invoice_no != ''
				AND b.delivery_date >= '2013-08-01'
				AND b.supplier_id = c.supplier_id
				GROUP BY CONCAT(c.supp_name, '  --  ', b.source_invoice_no)";
				
$res = db_query($sql);
start_table($table_style2);
$th = array('','Date','Supplier', 'P.O. #','Invoice #', 'Amount','');
table_header($th);

$c = $k = 0;

while($row = db_fetch($res))
{
	$c ++;
	alt_table_row_color($k);
	label_cell($c.'. ');
	label_cell(sql2date($row['delivery_date']));
	label_cell($row['supp_name']);
	label_cell(viewer_link($row['po_number'], "purchasing/view/srs_view_po.php?trans_no=".$row['purch_order_no']));
	label_cell(get_trans_view_str(ST_SUPPRECEIVE, $row['id'],$row['invoice_no']));
	amount_cell(get_rr_total_using_po_price($row['id']));
	submit_cells('flag_me'.$row['id'],'=> Bellen Tan','','',true);
	end_row();
}

end_table();
div_end();
end_form();
end_page();
?>