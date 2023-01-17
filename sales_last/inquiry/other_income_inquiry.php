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

// include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
// include($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Other Income Inquiry"), false, false, "", $js);

start_form();
div_start('header');

$type = ST_OTHERINCOME;

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('Reference #:', 'dm_no');
		yesno_list_cells('Paid:', 'paid_status');
		customer_list_cells('Customer :', 'supp_id', null, true);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();
	
$sql = "SELECT a.*, b.debtor_ref FROM ".TB_PREF."debtor_trans a, ".TB_PREF."debtors_master b
		WHERE type = '$type'
		AND a.debtor_no = b.debtor_no
		AND ov_amount > 0";
		

if (trim($_POST['dm_no']) == '')
{
	$sql .= " AND tran_date >= '".date2sql($_POST['start_date'])."'
			  AND tran_date <= '".date2sql($_POST['end_date'])."'";
			  
	if ($_POST['supp_id'])
	{
		$sql .= " AND a.debtor_no = ".$_POST['supp_id'];
	}
	
		if ($_POST['paid_status'])
	{
		$sql .= " AND a.tracking = ".$_POST['paid_status'];
	}
	
			if (!$_POST['paid_status'])
	{
		$sql .= " AND a.tracking = ".$_POST['paid_status'];
	}
	
}
else
{
	$sql .= " AND (reference LIKE ".db_escape('%'.$_POST['dm_no'].'%')." )";
	
}
$sql .= " ORDER BY tran_date";
$res = db_query($sql);
//display_error($sql);

start_table($table_style2.' width=75%');
$th = array();
	
array_push($th, 'Trans Date', 'Trans #', 'Customer', 'Reference #','Amount', 'Due Date', 'Status','');


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
	label_cell(sql2date($row['tran_date']));
	label_cell(get_gl_view_str(ST_OTHERINCOME, $row["trans_no"], $row["trans_no"]));
	//label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['debtor_ref'] ,'nowrap');
	label_cell($row['reference']);
	amount_cell($row['ov_amount'],true);
	label_cell(sql2date($row['due_date']));
	//label_cell(get_comments_string($type, $row['trans_no']));
	if ($row['tracking']==0)
	{
	label_cell('Not Yet Paid');
	label_cell(pager_link(_('Create Payment'), "/sales/other_income_payment.php?trans_no=" .$row['trans_no'], ICON_MONEY));
	}
	else {
	label_cell('Paid');
	label_cell('---');
	}
	end_row();
}

end_table();
div_end();
end_form();



end_page();

?>
