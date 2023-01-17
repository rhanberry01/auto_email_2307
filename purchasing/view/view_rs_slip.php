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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/gl/includes/db/rs_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
	
// page("View Returns or Disposals", true, false, "", $js);

	$com = get_company_prefs();
	
if (!isset($_GET['rs_id']))
{
	die ("<br>" . _("This page must be called with a Returned Merchandise Slip number to review."));
}

$row = get_rs_header($_GET['rs_id']);
$remarks=$row['comment'];
$approver_comment=$row['approver_comment'];
$rs_id = $_GET['rs_id'];

echo "<font face=verdana>";

start_table("style=font-size:12px align='left' width='280'");
start_row('align=center');
label_cell("<font size='2' face='arial'><b>".$com['coy_name']."</b></font>",'colspan=5');
end_row();

start_row();
label_cell("&nbsp;",'colspan=5');
end_row();

start_row('align=center');
label_cell("Return to Supplier Slip",'colspan=5');
end_row();

start_row('align=center');
label_cell("RS # :".$row['movement_no']."",'colspan=5');
end_row();

start_row();
label_cell("&nbsp;",'colspan=5');
end_row();

start_row('align=left');
label_cell("Supplier : ",'colspan=5');
end_row();

start_row('align=left');
label_cell(get_ms_supp_name($row["supplier_code"]),'colspan=5');
end_row();

start_row();
label_cell("&nbsp;",'colspan=5');
end_row();

start_row('align=left');
label_cell("Date : ".sql2date($row['rs_date']),'colspan=5');
end_row();

start_row('align=left','colspan=5');
label_cell("SA to BO #: ".$row['rs_id'],'colspan=5');
end_row();

if ($remarks!='') {
start_row('align=left','colspan=5');
label_cell("Remarks : ".$remarks,'colspan=5');
end_row();
}

start_row();
label_cell("-----------------------------------------------------",'colspan=5 align=center');
end_row();

//start_table("style=font-size:12px align='left' width='280'");
start_row('align=left');
label_cell("&nbsp;");
end_row();

start_row('align=center');
label_cell('PRODUCT',"align=left");
label_cell('UOM',"align=center");
label_cell('QTY',"align=center");
label_cell('UCOST',"align=center");
label_cell('EXTENDED',"align=center");
end_row();

$res = get_rs_items($rs_id);

$total = 0;
while($row = db_fetch($res))
{
	$total += round2($row['qty']*$row['price'],3);
	start_row();
	label_cell($row['item_name'],'align=left');
	label_cell($row['qty'],'align=center');
	label_cell($row['uom'],'align=center');
	label_cell(number_format2($row['price'],3),'align=center');
	label_cell(number_format2($row['qty']*$row['price'],3),'align=right');
	end_row();
}
start_row();
label_cell("-----------------------------------------------------",'colspan=5 align=center');
end_row();

start_row();
	label_cell('TOTAL: ',"align=right colspan=3");
	label_cell(number_format2(abs($total),3),"align=right");
	//label_cell(number_format2(abs($t_cost),3),"align=right");
end_row();

start_row();
label_cell("-----------------------------------------------------",'colspan=5 align=center');
end_row();
end_page(true);

?>
