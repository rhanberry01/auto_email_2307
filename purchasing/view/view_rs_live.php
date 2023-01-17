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
	
function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM transfers.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

function get_rs_header_by_branch($rs_id, $branch) {
	$conn = mysql_connect($branch['rs_host'], $branch['rs_user'], $branch['rs_pass']);
	mysql_select_db($branch['rs_db'], $conn);
	$sql = "SELECT * FROM ".TB_PREF."rms_header
			WHERE rs_id=$rs_id";
	$res = mysql_query($sql, $conn);
	if (mysql_num_rows($res) > 0)
		return mysql_fetch_array($res);	
	else 
		display_error("No RMS Header Found!");
}

function get_rs_items_by_branch($rs_id, $branch) {
	$conn = mysql_connect($branch['rs_host'], $branch['rs_user'], $branch['rs_pass']);
	mysql_select_db($branch['rs_db'], $conn);
	$sql = "SELECT * FROM ".TB_PREF."rms_items
			WHERE rs_id=$rs_id
			ORDER BY id";
	$res = mysql_query($sql, $conn);
	return $res;
}

	
if (!isset($_GET['rs_id']) && !isset($_GET['bid']))
{
	die ("<br>" . _("This page must be called with a Returned Merchandise Slip number to review."));
}

page("View Returns or Disposals", true, false, "", $js);

	$com = get_company_prefs();
	
	$b = get_branch_by_id($_GET['bid']);
	display_heading("SAN ROQUE SUPERMARKET RETAIL SYSTEMS INC. - ". $b['name']);
	display_heading2($b['address']);
	br(2); 

$row = get_rs_header_by_branch($_GET['rs_id'], $b);
$remarks=$row['comment'];
$approver_comment=$row['approver_comment'];
if ($row['rs_action'] == 1)
	display_heading('Return to Supplier Slip #'.$row['movement_no']);
if ($row['rs_action'] == 2)
	display_heading('For Disposal From BO Slip #'.$row['movement_no']);

br();
$rs_id = $_GET['rs_id'];


start_table("$table_style2 width=90%");
    start_row();
    label_cells(_("Supplier"),  get_ms_supp_name($row["supplier_code"]), "class='tableheader2'");
    label_cells(_("Date:"), sql2date($row['rs_date']), "class='tableheader2'");
	end_row();
    start_row();
	if ($row['processed'] == 0)
	{
		label_cells("Status : ",'<b>PENDING</b>', "class='tableheader2'");
	}
	else
	{
		if ($row['rs_action'] == 1)
		{
			label_cells("Status : ",'<b>Returned to Supplier</b>', "class='tableheader2'");
			label_cells("SA to BO Slip # : ",'<b>'.$row['rs_id'].'</b>', "class='tableheader2'");	
		}
		else if ($row['rs_action'] == 2)
		{
			label_cells("Status : ",'<b>Disposed</b>', "class='tableheader2'");
			label_cells("SA to BO Slip # : ",'<b>'.$row['rs_id'].'</b>', "class='tableheader2'");			
		}
		
		// label_cells("Status : ",'<b>'.($row['processed'] == 0 ? 'Not yet processed' : 'Processed').'</b>', "class='tableheader2'");
		// label_cells("Action : ",'<b>'.($row['rs_action'] == 0 ? 'Return to Supplier' : 'For Disposal').'</b>', "class='tableheader2'");
	}
	
	
	end_row();
end_table(2);

if ($remarks!='') {
start_table();
label_row("<b>Remarks:</b> ".$remarks);
end_table();
}

 br(2);

$res = get_rs_items_by_branch($rs_id, $b);

display_heading('Items');

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

br();
 
if ($approver_comment!='') {
start_table("width=90%");
label_row("<b>Approver Remarks:</b> ".$approver_comment);
end_table();
}

end_page(true);

?>
