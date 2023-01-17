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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/purchasing/includes/po_class.inc");

include($path_to_root . "/includes/session.inc");
include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_cv.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
	
page(_($help_context = "View Check Voucher"), true, true, "", $js);


if (!isset($_GET['trans_no']))
{
	die ("<br>" . _("This page must be called with a CV number to review."));
}
$trans_no = $_GET['trans_no'];

$cv_header = get_cv_header($trans_no);

display_heading(_("Check Voucher # ".$cv_header['cv_no']));
br();

global $table_style;

start_table("$table_style width=90%");

    start_row();
    label_cells(_("CV No."), $cv_header['cv_no'], "class='tableheader2'");
    label_cells(_("CV Date"), mssql2date($cv_header['cv_date']), "class='tableheader2'");
	end_row();

	start_row();
    label_cells(_("Supplier"),  get_supplier_name($cv_header["person_id"]), "class='tableheader2'");
    label_cells(_("Amount"),'<b>'. number_format2($cv_header['amount'],2). '</b>', "class='tableheader2'");
    end_row();

comments_display_row(ST_CV, $trans_no);
end_table(1);
start_table("$table_style width=90%", 6);
echo "<tr><td valign=top>"; // outer table

//--------------------------------------------------------------------------------------------------------------------
display_heading2(_("<b>Details</b>"));

// $sql = "SELECT * FROM PurchaseOrderLine
			// WHERE PurchaseOrderID=$trans_no";
$res = get_cv_details($trans_no);

start_table("colspan=9 $table_style width=100%");

$th = array('Transaciton Type', 'Trxn #' , 'Date', 'Supplier Invoice','Amount');

table_header($th);

$items_total = $k = 0;
while($item_row = db_fetch($res))
{
	alt_table_row_color($k);
	$tran_det = get_tran_details($item_row['trans_type'], $item_row['trans_no']);
	label_cell($systypes_array[$item_row['trans_type']]);
	
	if($tran_det["type"] == ST_SUPPDEBITMEMO || $tran_det["type"] == ST_SUPPCREDITMEMO)
		label_cell(get_gl_view_str($tran_det["type"], $tran_det["trans_no"], $tran_det["reference"]));
	else if ($tran_det["type"] != ST_SUPPAYMENT)
		label_cell(get_trans_view_str($tran_det["type"], $tran_det["trans_no"], $tran_det["reference"]));
	else
		label_cell(get_trans_view_str($tran_det["type"], $tran_det["trans_no"], $tran_det["trans_no"]));

	label_cell(sql2date($tran_det['tran_date']));
	
	if($tran_det['supp_reference'] != '')
	{
		label_cell('Supp Inv. # '.$tran_det['supp_reference']);
	}	
	else
		label_cell('');
	amount_cell($item_row['amount']+$tran_det['ewt']);
	
	end_row();
}

if ($cv_header['ewt'] != 0)
{
	label_row(_("<b>Lines Total:</b>"), '<b>' . number_format2($cv_header['amount']+$cv_header['ewt'],2). '</b>', "colspan=4 align=right", "nowrap align=right");
	label_row(_("<b>less EWT:</b>"), '<b>' . number_format2($cv_header['ewt'],2). '</b>', "colspan=4 align=right", "nowrap align=right");
}

label_row(_("<b>CV TOTAL :</b>"), '<b>' . number_format2($cv_header['amount'],2). '</b>', "colspan=4 align=right", "nowrap align=right");

end_table();

end_table();
//--------------------------------------------------------------------------------------------------------------------

// echo "<br><center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

end_page(true);
?>
