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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");

include_once($path_to_root . '/includes/pdf/fpdf/fpdf.php');
include_once($path_to_root . '/includes/pdf/fpdfi/fpdi.php');

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "BIR 2307"), false, false, "", $js);

//----------------------------------------------------------------------------------------

if (isset($_POST['Display']))
{
	
	$date_after = date2sql($_POST['TransAfterDate']);
    $date_to = date2sql($_POST['TransToDate']);
	$supplier_id=$_POST['supplier_id'];
	
		echo "<script type='text/javascript'>
				window.open('".$path_to_root . "/gl/2307_pdf.php?",'supplier_id='.$supplier_id. '&from='.$date_after. '&to='.$date_to."',
				'_blank','width=900px,height=600px,scrollbars=0,resizable=no')
				</script>";
}

//----------------------------------------------------------------------------------------


start_form();
start_table();
supplier_list_cells('Supplier:', 'supplier_id', null, 'All Suppliers');
date_cells(_("From:"), 'TransAfterDate', '', null);
date_cells(_("To:"), 'TransToDate', '',null);
end_table();
br(2);
start_table();
submit_center('Display',_("Display"));
end_table();
end_form();
end_page();

?>