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
$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/cust_credit_debit.inc");


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Receivable Adjustment"), false, false, "", $js);

$approve_id = find_submit('update_rec_entry');

start_form();
div_start('header');

$type = ST_CUSTPAYMENT;

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('Reference #:', 'dm_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();


if ($approve_id != -1)
{
global $Ajax;
$remarks=$_POST['remarks'.$approve_id];
$gl_type=$_POST['gl_type'.$approve_id];


display_error($gl_type);
display_error($approve_id);

		// begin_transaction();
			// $sql2 = "UPDATE ".TB_PREF."gl_trans 
			// SET account = '$gl_type'
			// WHERE type='60' AND account='1430017' AND type_no= '$approve_id'";
			// db_query($sql2);
		// commit_transaction();

$Ajax->activate('table_');
display_notification(_("The Transaction has been Cleared."));
}

$sql = "SELECT * FROM 0_salestotals as st
LEFT JOIN 0_salestotals_details as std
ON st.ts_id=std.ts_id
LEFT JOIN 0_gl_trans as gl
ON std.tsd_id=gl.type_no
where st.ts_receivable!=0
AND gl.type='60'
AND gl.account='1430017'";

if (trim($_POST['dm_no']) == '')
{
	$sql .= " AND ts_date_remit >= '".date2sql($_POST['start_date'])."'
				 AND ts_date_remit <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " AND (tsd_id LIKE ".db_escape('%'.$_POST['dm_no'].'%')." )";
}

$sql .= " ORDER BY ts_date_remit";
$res = db_query($sql);
//display_error($sql);
display_gl_items('Invoice Details', $_SESSION['cust_check_items']);

div_start('table_');
start_table($table_style2.' width=65%');
$th = array();
array_push($th, 'Date', 'Trans #','Account','Amount','Remarks','');


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
start_form();
	alt_table_row_color($k);
	label_cell(sql2date($row['ts_date_remit']));
	label_cell(get_gl_view_str(60, $row["tsd_id"], $row["tsd_id"]));
	gl_all_accounts_list_cells('', 'gl_type'.$row['tsd_id'], $row['account'], false, false, "All Accounts");
	amount_cell($row['ts_receivable'],true);
	text_cells(null, 'remarks'.$row['tsd_id'],$row['remarks']);
	$submit='update_rec_entry'.$row['tsd_id'];
	submit_cells($submit, 'Apply changes', "align=center", true, true,'ok.gif');
	end_row();
end_form();
}

end_table();
div_end();
end_form();
end_page();
?>