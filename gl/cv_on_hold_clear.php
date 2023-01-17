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
page(_("ON-HOLD CVs"), false, false, "", $js);


//---------------------------------------------------------------------------------------------------------------
$sql = "SELECT a.remarks, b.* FROM ".TB_PREF."cv_on_hold a JOIN ".TB_PREF."cv_header  b ON a.cv_id = b.id
		WHERE cleared = 0 ORDER BY id desc";
$res = db_query($sql);

$th = array('CV ID','Supplier', 'Amount', 'Remarks',  '');
start_table($table_style);
table_header($th);

$k = 0;
while($row = db_fetch($res))
{
	$supplier_row = get_supplier($row['person_id']);
	
	echo '<td nowrap>';
	echo "<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?
				cv_id=".$row["id"]."'onclick=\"javascript:openWindow(this.href,this.target); return false;\">" .
				$row["cv_no"] . "&nbsp;</a> ";	
	echo '</td>';
				
	label_cell($supplier_row['supp_name']);
	amount_cell($row['amount']);
	label_cell($row['remarks']);
	hyperlink_params_td($path_to_root.'/gl/clear_cv.php', 'Clear', 'id='.$row['id'].'&cv_no='.$row['cv_no']);

	end_row();
	
}
end_table();

end_page();
?>
