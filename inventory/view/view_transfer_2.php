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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/inventory/includes/stock_transfer2.inc");
// include_once($path_to_root . "/reporting/includes/reporting.inc");
// include_once($path_to_root . "/includes/db/audit_trail_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Transfer Request"), true, false, "", $js);


if (!isset($_GET['transfer_id']))
{
	die ("<br>" . _("This page must be called with a transfer number to review."));
}

$transfer_id =  $_GET['transfer_id'];

display_heading(_("Transfer # $transfer_id"));

global $db_connections,$table_style2;

$tr_header = get_transfer_header($_GET['transfer_id']);
// div_start('header');

start_outer_table("width=95% $table_style2");

table_section(1,'50%');

start_table("width=100% $table_style2");
label_row('<b>Transfer  # :</b>',"<font style='font-size:20px'><b>".$_GET['transfer_id'],"class='tableheader2'");

label_row('<b>Requested Delivery Date :</b>', '<b>'.sql2date($tr_header['delivery_date']).'</b>',"class='tableheader2' nowrap ");
label_row('<b>From Branch :</b>',"<font style='font-size:20px'><b>".get_transfer_branch_name($tr_header['br_code_out']).
	'</b></font>',"class='tableheader2' nowrap");
	
label_row('<b>Transfer Dispatched Date :</b>', '<b>'.sql2date($tr_header['transfer_out_date']).'</b>',"class='tableheader2' nowrap ");
label_row('<b>Delivered by :</b>', '<b>'.$tr_header['delivered_by'].'</b>',"class='tableheader2' nowrap ");
label_row('<b>Checked by :</b>', '<b>'.$tr_header['checked_by'].'</b>',"class='tableheader2' nowrap ");
// label_row('<b>Location :</b>', '<b><font size="5" color="red">'.
	// ($db_connections[$_SESSION['wa_current_user']->company]['is_warehouse'] ? 'WAREHOUSE' : 'SELLING AREA')
	// .'</font></b>',"class='tableheader2' nowrap");
	
table_section(2,'50%');

start_table("width=100% $table_style2");
label_row('<b>Requested by :</b>',"<b>".strtoupper($tr_header['requested_by']),"class='tableheader2' nowrap");
label_row('<b>Date Created:</b>', '<b>'.sql2date($tr_header['date_created']).'</b>',"class='tableheader2' nowrap");
label_row('<b>To Branch :</b>',"<font style='font-size:20px'><b>".get_transfer_branch_name($tr_header['br_code_in']).
	'</b></font>',"class='tableheader2' nowrap");
label_row('<b>Transfer Received Date :</b>', '<b>'.sql2date($tr_header['transfer_in_date']).'</b>',"class='tableheader2' nowrap ");
label_row('<b>Received by :</b>', '<b>'.$tr_header['name_in'].'</b>',"class='tableheader2' nowrap ");
// label_row('<b>Location :</b>', '<b><font size="5" color="red">'.'SELLING AREA'.'</font></b>',"class='tableheader2' nowrap");

if ($tr_header['m_no_out'] != '')
{
	end_outer_table(2); // outer table
	$k = 0;
	display_heading('Movement Numbers');

	$th = array('Transfer OUT', 'Transfer IN');
	start_table("$table_style width=30%");
	table_header($th);
	alt_table_row_color($k);
		label_cell('<b>'.$tr_header['m_no_out'].'</b>', 'align=center');
		label_cell('<b>'.$tr_header['m_no_in'].'</b>', 'align=center');
	end_row();
}


end_outer_table(2); // outer table
display_heading('Items for Transfer');
$res = get_for_transfer_items($transfer_id);
$th = array('Barcode', 'Decription','UOM', 'COST','QTY', 'QTY Dispatched', 'QTY Received');

start_table("$table_style width=90%");
table_header($th);
$qty_total = $qty_total_out =$qty_total_in = $k = $qty_total_cost_out = $qty_total_cost_in= 0;

$has_marked = false;
while($row = db_fetch($res))
{
	
	if ($tr_header['m_id_in'] != '' AND $row['qty_in'] <$row['actual_qty_out'])
	{
		echo '<tr class=overduebg>';
		$has_marked = true;
	}
	else
		alt_table_row_color($k);
	label_cell($row['barcode']);
	label_cell($row['description']);
	label_cell($row['uom'],'align=center');
	label_cell(number_format($row['cost'],2),'align=center');

	$qty_total_cost_out += $row['cost'] * $row['actual_qty_out'] ;
	$qty_total_cost_in += $row['cost'] * $row['qty_in'];
	
	$qty_total += $row['qty_out'];
	label_cell($row['qty_out'],'align=center');
	
	label_cell($row['actual_qty_out'],'align=center');
	$qty_total_out += $row['actual_qty_out'];
	
	label_cell($row['qty_in'],'align=center');
	$qty_total_in += $row['qty_in'];
	
	end_row();
}

label_cell('<b>TOTAL QTY:</b>','colspan=4 align=right');
label_cell($qty_total,'align=center');
label_cell($qty_total_out,'align=center');
label_cell($qty_total_in,'align=center');
end_row();
label_cell('<b>TOTAL COST:</b>','colspan=5 align=right');
label_cell($qty_total_cost_out,'align=center');
label_cell($qty_total_cost_in,'align=center');
end_table();

// $sql = "SELECT memo_ FROM ".TB_PREF."comments where id='$tr_header' and type='$type'";
// //display_error($sql);
// $res = db_query($sql);
// $row = db_fetch($res);
// if ($row['memo_']!='') {
// start_table();
// label_row("<b>Remarks:</b> ".$row['memo_']);
// end_table();
// }
// br(2);




if ($has_marked)
display_note(_("Marked lines have discrepancy in receiving."), 0, 0, "class='overduefg'");

echo "<br><center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

?>
