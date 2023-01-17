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
$page_security = 'SA_GLSETUP';

$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Fix APV GL to Del Date', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function fix_cv_due_date($cv_id,$supp_id,$del_date,$days)
{

	$sql = "UPDATE 0_cv_header 
					SET due_date = '". date2sql(add_days($del_date,$days))."'
					WHERE id=$cv_id
					AND person_id = $supp_id";
	db_query($sql,'failed to update gl');
	// display_notification($sql);
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT DISTINCT a.cv_id, a.supplier_id, a.del_date, c.days_before_due
					FROM 0_supp_trans a, 0_suppliers b, 0_payment_terms c
					WHERE a.type = 20
					AND a.del_date >= '2015-11-13'
					AND a.cv_id != 0
					AND a.supplier_id = b.supplier_id
					AND b.payment_terms = c.terms_indicator";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		$row['del_date'] = sql2date($row['del_date']);
		if(is_date($row['del_date']))
			fix_cv_due_date($row['cv_id'],$row['supplier_id'],$row['del_date'],$row['days_before_due']);
	}
	
	display_notification('DONE');
}

start_form();

submit_center('fix_now', 'GO');
end_form();

end_page();
?>
