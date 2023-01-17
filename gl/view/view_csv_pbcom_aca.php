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
include_once($path_to_root . "/modules/checkprint/includes/cv_mailer.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "View CSV Details"), true,true, "", $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$cv_id = find_submit('email');
if ($cv_id != -1)
{
	$cv_header = get_cv_header($cv_id);
	send_that_cv($cv_header['id'],$cv_header['bank_trans_id']);
	unset($_POST['email'.$cv_id]);
	$_GET['trans_no'] = $_POST['trans_no'];
}


if (!isset($_GET['trans_no']))
{
	die ("<br>" . _("This page must be called with a CSV to review."));
}
$trans_no = $_GET['trans_no'];

$csv_header = get_csv_header_pbcomaca($trans_no);

display_heading(_("PBCOM ACA CSV Batch # ".$trans_no));
br();

global $table_style;



start_form();
hidden('trans_no',$_GET['trans_no']);
start_table("$table_style width=90%");

    start_row();
    label_cells(_("PBCOM ACA CSV Batch."), $csv_header['id'], "class='tableheader2'");
    label_cells(_("PBCOM ACA CSV Date"), sql2date($csv_header['date']), "class='tableheader2'");
	end_row();

	start_row();
    // label_cells(_("Filename"),  $csv_header["csv_file"], "class='tableheader2'");
    label_cells(_("Amount"),'<b>'. number_format2(get_csv_amount_pbcomaca($csv_header['id']),2). '</b>', "class='tableheader2'");
    end_row();

end_table(1);
start_table("$table_style width=90%", 6);
echo "<tr><td valign=top>"; // outer table

//--------------------------------------------------------------------------------------------------------------------
display_heading2(_("<b>Details</b>"));

 
$res = get_csv_details_pbcomaca($trans_no);

start_table("colspan=9 $table_style width=100%");

$th = array('E-mail sent?', 'CV #', 'CV Date', 'CV Due Date' , 'Supplier', 'Amount');


table_header($th);

$total = $k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	// label_cell($row['email_sent'] ? 'Yes' : 'Not yet');
	$id = $row["id"];
	display_error($row['email_sent']);
	if (!$row['email_sent'])
			label_cell('Not yet '.submit('email'.$id, 'Send e-mail', false, false, false,ICON_EMAIL));
	else
			label_cell('Yes &nbsp;&nbsp;'.submit('email'.$id, 'Resend e-mail', false, false, false,ICON_EMAIL));
	label_cell(get_cv_view_str($row["id"], $row["cv_no"]));
	label_cell(sql2date($row['cv_date']));
	label_cell(sql2date($row['due_date']));
	label_cell(payment_person_name($row["person_type"],$row["person_id"], false));
	amount_cell($row['amount']);
	end_row();
	
	$total += $row['amount'];
}

label_cell('<b>Total : </b>', 'align=right colspan=4');
amount_cell($total, true);

end_table();

end_table();

end_form();
//--------------------------------------------------------------------------------------------------------------------

// echo "<br><center><a href='#' onclick='javascript:window.close()'>Close</a></center>";

end_page(true);
?>
