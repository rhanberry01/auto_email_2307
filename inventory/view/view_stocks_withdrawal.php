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
page(_($help_context = "View Stocks Withdrawal Request"), true, false, "", $js);


if (!isset($_GET['transfer_id']))
{
	die ("<br>" . _("This page must be called with a transfer number to review."));
}


$transfer_id =  $_GET['transfer_id'];

display_heading(_("Stocks Withdrawal"));
br();

global $db_connections,$table_style2;

$tr_header = get_stocks_withdrawal_header($_GET['transfer_id']);
// div_start('header');

start_outer_table("width=95% $table_style2");

table_section(1,'50%');

start_table("width=100% $table_style2");
label_row('<b>Stock Withdrawal  # :</b>',"<font style='font-size:14px'><b>".$_GET['transfer_id'],"class='tableheader2'");
label_row('<b>From Branch :</b>',"<font style='font-size:14px'><b>".get_transfer_branch_name($tr_header['br_code_out']).
	'</b></font>',"class='tableheader2' nowrap");
//label_row('<b>Date Created:</b>', '<b>'.sql2date($tr_header['date_created']).'</b>',"class='tableheader2' nowrap");
label_row('<b>Date Requested:</b>', '<b>'.sql2date($tr_header['request_date']).'</b>',"class='tableheader2' nowrap ");
label_row('<b>Date Released :</b>', '<b>'.sql2date($tr_header['released_date']).'</b>',"class='tableheader2' nowrap ");
label_row('<b>Date Posted :</b>', '<b>'.sql2date($tr_header['date_posted']).'</b>',"class='tableheader2' nowrap ");

// label_row('<b>Location :</b>', '<b><font size="5" color="red">'.
	// ($db_connections[$_SESSION['wa_current_user']->company]['is_warehouse'] ? 'WAREHOUSE' : 'SELLING AREA')
	// .'</font></b>',"class='tableheader2' nowrap");
	
table_section(2,'50%');

start_table("width=100% $table_style2");
//label_row('<b>Slip# :</b>',"<font style='font-size:14px'><b>".$tr_header['withdrawal_slip_no'],"class='tableheader2'");
		// $u2 = get_user($tr_header['requested_by']);
		// $requested_by = $u2['real_name'];
label_row('<b>Requested by :</b>',"<b>".$tr_header['requested_by'],"class='tableheader2' nowrap");
label_row('<b>To Branch :</b>',"<font style='font-size:14px'><b>".get_transfer_branch_name($tr_header['br_code_in']).
	'</b></font>',"class='tableheader2' nowrap");
label_row('<b>Department :</b>', '<b>'.get_hr_dept_name($tr_header['requesting_dept']).'</b>',"class='tableheader2' nowrap ");
label_row('<b>Nature of Request :</b>', '<b>'.get_nature_of_req_name($tr_header["nature_of_req"]).'</b>',"class='tableheader2' nowrap ");
label_row('<b>Transaction # :</b>',"<b>".$tr_header['m_no_out'],"class='tableheader2' nowrap");

end_outer_table(2); // outer table

if($tr_header['memo_']!=''){
start_table();
label_cell('<b>Memo:</b>','colspan=3 align=right');
label_cell($tr_header['memo_'],'align=center');
end_table();
br(2);
}

display_heading('Stocks Released');
$res = get_for_stocks_withdrawal_items($transfer_id);
$th = array('Barcode', 'Decription' , 'UOM', 'QTY','COST','EXTENDED');

start_table("$table_style width=95%");
table_header($th);
$qty_total = $qty_total_out =$qty_total_in = $k = 0;

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
	label_cell($row['qty_out'],'align=center');
	amount_decimal_cell($row['cost']);
	amount_decimal_cell($row['cost']*$row['qty_multiplier']);
	
	// label_cell($row['actual_qty_out'],'align=center');
	// $qty_total_out += $row['actual_qty_out'];
	
	// label_cell($row['qty_in'],'align=center');
	// $qty_total_in += $row['qty_in'];
	
	end_row();
	$qty_total += $row['qty_out'];
	$cost_total+=$row['cost'];
	$extended_total+=$row['qty_out']*$row['cost']*$row['qty_multiplier'];
}

label_cell('<b>TOTAL:</b>','colspan=3 align=right');
label_cell($qty_total,'align=center');
label_cell($cost_total,'align=right');
label_cell($extended_total,'align=right');
// label_cell($qty_total_in,'align=center');
end_table();


br(2);
start_outer_table("width=95%");

table_section(1,'50%');
start_table();
$u = get_user($tr_header['approved_by']);
$approved_by = $u['real_name'];
label_row('<b>Approved by :</b>',$approved_by,"class='' nowrap ");

table_section(2,'50%');
start_table();
$u1 = get_user($tr_header['witnessed_by']);
$witnessed_by = $u1['real_name'];
label_row('<b>Released by :</b>',$witnessed_by,"class='' nowrap ");

end_outer_table(2); // outer table

if ($has_marked)
display_note(_("Marked lines have discrepancy in receiving."), 0, 0, "class='overduefg'");

//echo "<br><center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

?>
