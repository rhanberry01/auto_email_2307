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
$page_security = 'SA_SETUPCOMPANY';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");

page('Approve ALL CV', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------
if (isset($_POST['approve_cv']))
{
	$sql = "SELECT * FROM ".TB_PREF."cv_header WHERE approved = 0 AND amount > 0";
	$res = db_query($sql);
	
	$c = 0;
	while($row = db_fetch($res))
	{
		$sql = "UPDATE ".TB_PREF."cv_header SET approved = 1
			WHERE id = ".$row['id'];
		db_query($sql,'failed to approve CV');
		add_audit_trail(99, $row['id'], Today(), 'CV approved');
		$c ++;
	}
	
	display_notification("$c CVs approved");
}

start_form();
submit_center('approve_cv', 'Approve all pending CV');
end_form();

end_page();
?>
