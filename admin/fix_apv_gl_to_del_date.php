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

function fix_gl_date($trans_no, $del_date)
{
	$sql = "UPDATE 0_gl_trans SET
				tran_date = '$del_date'
			WHERE type = 20
			AND type_no=$trans_no";
	db_query($sql,'failed to update gl');
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT * FROM  0_supp_trans 
			WHERE type = 20
			AND del_date >= '2017-01-01'";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		fix_gl_date($row['trans_no'],$row['del_date']);
	}
	
	display_notification('DONE');
}

start_form();

submit_center('fix_now', 'GO');
end_form();

end_page();
?>
