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
$page_security = 'SA_GLACCOUNT';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Chart of Accounts"));

include($path_to_root . "/includes/ui.inc");
include($path_to_root . "/gl/includes/gl_db.inc");
include($path_to_root . "/admin/db/tags_db.inc");
include_once($path_to_root . "/includes/data_checks.inc");

check_db_has_gl_account_groups(_("There are no account groups defined. Please define at least one account group before entering accounts."));

//-------------------------------------------------------------------------------------
start_form();

if (isset($_POST['add']))
{
	$sql = "SELECT * FROM 0_chart_master_copy";
	$res = db_query($sql);
	while ($row = db_fetch($res))
		add_gl_account($row['account_code'], $row['account_name'], $row['account_type'], '');
	
	display_notification(_("New accounts added."));	
}


submit_center('add', _("Add Accounts"));
end_form();

end_page();

?>
