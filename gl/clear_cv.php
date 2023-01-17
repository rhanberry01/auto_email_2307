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
include_once($path_to_root . "/includes/db/audit_trail_db.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/date_functions.inc");



$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

// page(_($help_context = "ON-HOLD CV"));
page(_("CLEAR ON-HOLD CV"), false, false, "", $js);

if (isset($_POST['clear'])){
	$sql = "UPDATE ".TB_PREF."cv_on_hold SET
			new_invoice_no = ".db_escape($_POST['new_inv_no']).", 
			remarks2 = ".db_escape($_POST['remarks']).", 
			cleared = 1 WHERE cv_id =".$_POST['cv_id'];
		db_query($sql,'failed to clear cv');
		
		// display_error($sql);
		display_notification("CV # ".$_POST['cv_id']." successfully clear.");
		meta_forward($path_to_root . "/gl/cv_on_hold_clear.php");
}

	start_form();
		hidden('cv_id', $_GET['id']);
		hidden('cv_no', $_GET['cv_no']);
		
		$cv_link = "<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?
				cv_id=".$_GET["id"]."'onclick=\"javascript:openWindow(this.href,this.target); return false;\">" .
				$_GET["cv_no"] . "&nbsp;</a> ";	
		
		start_table('cellpadding=4 border=0 align=center');
			label_cells('<b>CV # :</b>' , '<b>'.$cv_link.'</b>');
			text_row_ex(_("<b>New Invoice #:"), 'new_inv_no', 44, 250);
			textarea_row(_("<b>Remarks :</b>"), 'remarks', null, 35, 5);	
		end_table();
			submit_center('clear', _("Clear"), true, '',  'default');
	end_form();
end_page();
?>
