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
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Approve Remittances", false, false, "", $js);

//------------------------------------------------------------------------------------------------
$approve_id = find_submit('approve_remittance');
if ($approve_id != -1)
{
	global $Ajax;
	approve_remittance($approve_id);
	display_notification('Remittance # '.$approve_id.' has been approved');
	$Ajax->activate('table_');
}

$disapprove_id = find_submit('disapprove_remittance');
if ($disapprove_id != -1)
{
	global $Ajax;
	
	if (trim($_POST['reason'.$disapprove_id]) != '')
	{
		disapprove_remittance($disapprove_id,$_POST['reason'.$disapprove_id]);
		display_notification('Disapproved Remittance # '.$disapprove_id.'. '.$_POST['reason'.$disapprove_id]);
	}
	else
		display_error('Please provide a reason for disapproval');
	$Ajax->activate('table_');
}

//------------------------------------------------------------------------------------------------
start_form();

start_table();
start_row();
	get_cashier_list_cells('Cashier:', 'cashier_id');
	date_cells(_("From:"), 'TransAfterDate', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null);

	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(2);

div_start('table_');
// if (isset($_POST['RefreshInquiry']))
// {
$date_after = date2sql($_POST['TransAfterDate']);
$date_before = date2sql($_POST['TransToDate']);
		
$sql = "SELECT a.* FROM 0_remittance a
			WHERE remittance_date >= '$date_after'
			AND remittance_date <= '$date_before'
			AND treasurer_id = 0";
			
if ($_POST['cashier_id'] != '')
{
	$sql .= " AND a.cashier_id= " . $_POST['cashier_id'];
}

$sql .= " ORDER BY remittance_date, remittance_id";
// display_error($sql);
$result = db_query($sql);
// }
start_table($table_style2.' width=80%');
$th = array( /*'Type',*/ "Date",'Remittance #', _("Cashier"), 
'Total Cash', 'Total Credit Card', 'Total Debit Card', 'Total Suki Card', 'Total SRS GC', 'Total Others',
_("Total Amount"), "View", 'Approve', 'Reason', 'Disapprove');

table_header($th);

$j = 1;
$k = 0; //row colour counter
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	
	$total_total = $myrow["total_cash"] + $myrow["total_credit_card"] + $myrow["total_debit_card"] + $myrow["total_suki_card"] + 
					$myrow["total_srs_gc"] + $myrow["total_others"];
	// label_cell($myrow['final_remittance'] ? 'Final Remittance' : 'Partial Remittance');
	label_cell(sql2date($myrow['remittance_date']));
	label_cell('<b>' . $myrow['remittance_id'].'</b>', 'align=center');
	label_cell($myrow["cashier_name"],'align=center nowrap');
	// label_cell($myrow['treasurer_name']);
	amount_cell($myrow["total_cash"]);
	amount_cell($myrow["total_credit_card"]);
	amount_cell($myrow["total_debit_card"]);
	amount_cell($myrow["total_suki_card"]);
	amount_cell($myrow["total_srs_gc"]);
	amount_cell($myrow["total_others"]);
	
	amount_cell($total_total,true);
	label_cell(viewer_link('View Details', "sales/view/view_cashier_remittance.php?
		Remittance_ID=".$myrow['remittance_id'], '', '', ''),'align=center nowrap');
	submit_cells('approve_remittance'.$myrow['remittance_id'], 'Approve', "align=center", false, true,'ok.gif');
	// label_cell(print_document_link($myrow['remittance_id'], _("Print"), true, 888));	
	// hyperlink_params_td($path_to_root.'/sales/inquiry/sales_breakdown.php', 'Check Transactions', 'remittance_id='.$myrow['remittance_id']);
	
	text_cells('','reason'.$myrow['remittance_id'], null, '', 255, false, "", "", '',"align=right");
	submit_cells('disapprove_remittance'.$myrow['remittance_id'], 'Disapprove', "align=center", false, true,'delete.gif');
	
	end_row();
	
	// $j++;
	// if ($j == 11)
	// {
		// $j = 1;
		// table_header($th);
	// }
}
end_table(1);
div_end();
end_form();
end_page();

?>
