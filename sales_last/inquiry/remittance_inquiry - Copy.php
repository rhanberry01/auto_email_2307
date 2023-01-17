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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Cashier Remittances"), false, false, "", $js);

//------------------------------------------------------------------------------------------------

start_form();


start_table("class='tablestyle_noborder'");
start_row();

// customer_list_cells('', 'customer_id', null, true);
// cashier_remittance_list_cells('Cashier : ', 'cashier_id');

get_ms_cashier_list_cells('Cashier', 'cashier_id', null,'', false);

date_cells(_("From:"), 'TransAfterDate', '', null, -1);
date_cells(_("To:"), 'TransToDate', '', null);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');

end_row();
end_table(2);

if (isset($_POST['RefreshInquiry']))
{
	global $Ajax;
	$Ajax->activate('r_tbl');
}


div_start('r_tbl');

$date_after = date2sql($_POST['TransAfterDate']);
$date_before = date2sql($_POST['TransToDate']);
		
$sql = "SELECT a.* FROM 0_remittance a
			WHERE remittance_date >= '$date_after'
			AND remittance_date <= '$date_before'";
			
if ($_POST['cashier_id'] != '')
{
	$sql .= " AND a.cashier_id= " . $_POST['cashier_id'];
}

$sql .= " ORDER BY remittance_id";
start_table($table_style);
$th = array( 'Type', '#',"Date", _("Cashier"), _("Treasurer"), _("Amount"), 'View', "Print",'');

table_header($th);

$j = 1;
$k = 0; //row colour counter
$result = db_query($sql);
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	
	label_cell($myrow['final_remittance'] ? 'Final Remittance' : 'Partial Remittance');
	label_cell($myrow['remittance_id']);
	label_cell(sql2date($myrow['remittance_date']));
	label_cell(get_ms_username_by_id($myrow["cashier_id"]));
	label_cell(get_username_by_id($myrow['treasurer_id']));
	amount_cell($myrow["total_amount"]);
	
	if ($myrow['final_remittance'])
	{
		// hyperlink_params_td($path_to_root.'/sales/cashier_remittance.php', 'View', 'AddedRID='.$myrow['remittance_id'].'&final=1');
		label_cell(viewer_link('View Over/Short', "sales/cashier_remittance.php?AddedRID=".$myrow['remittance_id']."&final=1", '', '', ''));
		label_cell(print_document_link($myrow['remittance_id'], _("Print"), true, 888));
		hyperlink_params_td($path_to_root.'/sales/inquiry/sales_breakdown.php', 'Check Transactions', 'remittance_id='.$myrow['remittance_id']);
	}
	else
	{
		label_cell('');
		label_cell('');
		label_cell('');
	}
	end_row();
	
	$j++;
	if ($j == 11)
	{
		$j = 1;
		table_header($th);
	}
}

end_table(1);

div_end();

end_form();
end_page();

?>
