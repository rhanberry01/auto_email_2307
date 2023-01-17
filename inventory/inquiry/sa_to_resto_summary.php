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
include($path_to_root . "/includes/db_pager2.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "SA to SRS Kusina Summary"), false, false, "", $js);

start_form();
div_start('header');

$type = ST_SAKUSINAOUT;

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('Transaction #:', 'trans_no');
		//yesno_list_cells(_("Status Type:"), 'movement_type', '',_("Deliver to Branch"), _("Received from Branch"));
		//adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
		//yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();
	
$sql = "select id,date_created,trans_out,sum(ext) as extended from(SELECT th.date_created as date_created,th.id as id, th.aria_trans_no_out as trans_out,(td.cost*td.qty_out) as ext
FROM transfers.".TB_PREF."transfer_details as td
left join transfers.".TB_PREF."transfer_header as th
on th.id=td.transfer_id
where th.aria_type_out='67' ";

if ($_POST['start_date'])
{
$sql .= " and th.date_created>='".date2sql($_POST['start_date'])."'
and th.date_created<='".date2sql($_POST['end_date'])."'";		  
}

if ($_POST['trans_no'])
{
$sql .= " AND th.aria_trans_no_out = '".$_POST['trans_no']."'";	
}

$sql .= ")as a GROUP BY id";

$res=db_query($sql);
//display_error($sql);

start_table($table_style2.' width=45%');

display_heading("Kusina Transfer Summary from ".$_POST['start_date']." to ".$_POST['end_date']."");
br();

$th = array();
array_push($th, 'Date Created', 'Trans#','Amount','');
if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}


$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['date_created']));
	//label_cell(get_gl_view_str(ST_SAKUSINAOUT, $row["id"], $row["id"]));
	label_cell($row["trans_out"]);

	amount_cell($row['extended']);

	
	label_cell(get_sa_to_resto_details_view_str($row['trans_out'],'View'));
	end_row();
	
	$t_amount+=$row['extended'];
}

start_row();
label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
label_cell('');
end_row();

end_table();
br();
br();
div_end();
end_form();
end_page();
?>