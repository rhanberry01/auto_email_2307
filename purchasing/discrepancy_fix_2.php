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
$page_security = 'SA_PURCHASEPRICING';
$path_to_root = "..";

include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/session.inc");


include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/includes/date_functions.inc");

include_once($path_to_root . "/includes/ui.inc");


include_once($path_to_root . "/purchasing/includes/ui/discrepancy_ui.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Update Invoice Price"), false, false, "", $js);

//----------------------------------------------------------------------------------------

check_db_has_suppliers(_("There are no suppliers defined in the system."));

//---------------------------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$invoice_no = $_GET['AddedID'];
	$trans_type = ST_SUPPINVOICE;

    echo "<center>";
    display_notification_centered(_("Invoice #".$invoice_no .' has been resolved'));
	hyperlink_no_params($path_to_root."/purchasing/inquiry/discrepancy_report.php", _("Back to Discrepancy Report"));	

	display_footer_exit();
}

if (isset($_GET['SavedID'])) 
{
	$invoice_no = $_GET['SavedID'];
	$trans_type = ST_SUPPINVOICE;

    echo "<center>";
    display_notification_centered(_("Invoice #".$invoice_no .' has been saved'));
	hyperlink_no_params($path_to_root."/purchasing/inquiry/discrepancy_report.php", _("Back to Discrepancy Report"));	

	display_footer_exit();
}
//------------------------------------------------------------------------------------------------
start_form();

invoice_header($_SESSION['supp_trans']);


	div_start('tablesss');
		
		if (!$_SESSION['supp_trans']->nt)
			display_grn_items($_SESSION['supp_trans'], 1);
		
	div_end();

	div_start('inv_tot');
	invoice_totals($_SESSION['supp_trans']);
	div_end();



//-----------------------------------------------------------------------------------------
br();
submit_center_first('ConfirmAll', _("Confirm All Prices"), false, true);
submit('PostProcess', _("Confirm and Process"), true, '', false, 'ok.gif');
submit_center_last('Save', _("Save and continue later"), false, false, 'report.png');
br();
end_form();
//--------------------------------------------------------------------------------------------------
end_page();
?>