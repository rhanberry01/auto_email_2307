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
$page_security = 'SA_GLTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_cv.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "View Check Issuance Batch Details"), true,true, "", $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

function get_check_pay_to($bank_trans_id)
{
	$sql = "SELECT pay_to FROM ".TB_PREF."cheque_details
			WHERE bank_trans_id = $bank_trans_id";
	
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

if (!isset($_GET['id']))
{
	die ("<br>" . _("This page must be called with a Check Issue Batch Number."));
}
$batch_id = $_GET['id'];

$header = get_check_batch($batch_id);

if ($header['check_writer'] == 1)
	$type = 'AUB Check ';
else if ($header['check_writer'] == 2)
	$type = 'Metrobank Check ';

display_heading(_("$type Batch # ".$header['batch_no']));
br();

global $table_style;

start_table("$table_style width=90%");

    start_row();
    // label_cells(_("$type Batch."), $header['batch_no'], "class='tableheader2'");
    label_cells(_("Date Created"), sql2date($header['stamp']), "class='tableheader2'");
    label_cells(_("Total Amount"),'<b>'. number_format2(get_check_batch_amount($header['id']),2). '</b>', "class='tableheader2'");end_table(1);
start_table("$table_style width=90%", 6);
echo "<tr><td valign=top>"; // outer table

//--------------------------------------------------------------------------------------------------------------------
display_heading2(_("<b>Details</b>"));


$res = get_check_batch_details($batch_id);

start_table("colspan=9 $table_style width=100%");

$th = array('Check #', 'Check Date','CV #', 'CV Date', 'CV Due Date' , 'Pay To', 'Amount');

table_header($th);

$total = $k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	$c_row = get_bank_trans_cheque_details($row['bank_trans_id']);
	label_cell($c_row['bank']);
	label_cell($c_row['chk_number']);
	label_cell(get_cv_view_str($row["id"], $row["cv_no"]));
	label_cell(sql2date($row['cv_date']));
	label_cell(sql2date($row['due_date']));
	$pay_to = get_check_pay_to($row['bank_trans_id']);
	if ($pay_to == '') // voided
	{
		$pay_to = get_check_pay_to($row['bank_trans_id2']) .' - <b>VOIDED</b>';
	}
	
	label_cell($pay_to);
	amount_cell($row['amount']);
	end_row();
	
	$total += $row['amount'];
}

label_cell('<b>Total : </b>', 'align=right colspan=6');
amount_cell($total, true);

end_table();

end_table();
//--------------------------------------------------------------------------------------------------------------------

// echo "<br><center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

end_page(true);
?>
