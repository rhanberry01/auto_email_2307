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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager.inc");

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/db/audit_trail_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");


$js = '';
#####################

if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

####################
	
// display_error($_SESSION['wa_current_user']->username);

page(_($help_context = "Price Survey Inquiry"), false, false, "", $js);
//page(_($help_context = "Price Survey Inquiry"));

function get_vendor_description($vendorcode){
	
	$sql = "SELECT * FROM vendor
			WHERE vendorcode = '".$vendorcode."'";
	$res = ms_db_query($sql);
	$vendor = mssql_fetch_array($res);
	
	return $vendor['description'];
}

//---------------------------------------------------------------------------------------------------------------
/* $sql = "SELECT * FROM ".TB_PREF."price_survey
		WHERE posted = 1";
 */
 
 $sql = "SELECT * FROM [dbo].[Movements] WHERE TransactionDate >= '2017-05-23' AND MovementCode = 'PSV'";
// if ($_SESSION['wa_current_user']->username != 'admin')
	//$sql .= " AND CreatedBy = ".db_escape($_SESSION['wa_current_user']->user);
//display_error($sql);
$res = ms_db_query($sql);

//$th = array('Supplier', 'PO #', 'Invoice #', 'Date', '');
$th = array('Transaction ID', 'Movement No', 'Date Posted', '','');
div_start('', null, true);
start_table($table_style);// . 'bgcolor=white');
table_header($th);

$k = 0;
while($row = mssql_fetch_array($res))
{
	alt_table_row_color($k);
	label_cell($row['MovementID']);
//	label_cell($row['po_no']);
	label_cell($row['MovementNo']);
	label_cell(date('Y-m-d', strtotime($row['PostedDate'])));
	// edit_button_cell("Edit".$row['id'], _("Edit"));
	label_cell(get_price_survey_details_view_str($row['MovementID'],'View'));
	//display_error(get_po_attachment('PS'.$row['MovementNo'],''));
	if(get_po_attachment('PS'.$row['MovementNo'],''))
		label_cell("<a target=blank href='".get_po_attachment('PS'.$row['MovementNo'],'')."'onclick=\"javascript:openWindow(href,target); return false;\">".
					_("View Attachement") . "&nbsp;</a> ", 'align=center');
	else
		label_cell('');
	end_row();
	
}
end_table();
div_end();
end_page();
?>
