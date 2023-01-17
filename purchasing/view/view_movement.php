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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/gl/includes/db/rs_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);

if (!isset($_GET['rs_id']))
{
	die ("<br>" . _("This page must be called with a Returned Merchandise Slip number to review."));
}

$row = get_rs_header($_GET['rs_id']);

$heading = '';
if ($row['movement_type'] == 'R2SSA')
{
	$heading = 'Return to Supplier Slip # ';
}

if ($row['movement_type'] == 'FDFB')
{
	$heading = 'For Disposal From BO Slip # ';
}

page($heading, true, false, "", $js);
display_heading($heading.$row['movement_no']);

$m_type = $row['movement_type'];
$m_no = $row['movement_no'];
$rs_id = get_rs_ids($m_type, $m_no);

br();

start_table("$table_style2 width=90%");
    start_row();
    label_cells(_("Supplier"),  get_ms_supp_name($row["supplier_code"]), "class='tableheader2'");
    label_cells(_("Date:"), sql2date($row['bo_processed_date']), "class='tableheader2'");
	end_row();
    start_row();
	
	if ($row['trans_no'] == 0)
	{
		label_cells("Status : ",'<b>to be processed by accounting</b>', "class='tableheader2'");
		
	}
	else
	{
		label_cells("Status : ",'<b>already processed by accounting</b>', "class='tableheader2'");
	}
	label_cells("SA to BO #: ",'<b>'.$rs_id.'</b>', "class='tableheader2'");
	
	end_row();
end_table(2);

// $res = get_rs_items($rs_id);
$res = get_movement_items($m_type, $m_no);

display_heading2('Items');
start_table("$table_style2 width=90%");
$th = array('Product','QTY','UOM', 'PRICE', 'AMOUNT');
table_header($th);
$total = 0;
while($row = db_fetch($res))
{
	$total += round2($row['qty']*$row['price'],3);
	alt_table_row_color($k);
	label_cell($row['item_name']);
	label_cell($row['qty'],'align=right');
	label_cell($row['uom'],'align=center');
	label_cell(number_format2($row['price'],3),'align=right');
	label_cell(number_format2($row['qty']*$row['price'],3),'align=right');
	end_row();
}
alt_table_row_color($k);
	label_cell('TOTAL: ','colspan=4 align=right');
	label_cell('<b>'.number_format2($total,3).'</b>','align=right');
	end_row();

end_table(2);
end_page();

?>
