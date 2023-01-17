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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager2.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Debit Memo Agreement Inquiry"), false, false, "", $js);
//======================================================================================
if (isset($_POST['Fix'])){
	global $Refs;
	
	set_time_limit(0);
	
	begin_transaction();	
	
	global $Ajax;
	
	$sql="SELECT * FROM 0_sdma
	where approval_1!=0 and approval_2=0
	and date_created>='2016-01-01' and date_created<='2016-12-31'";
	$result= db_query($sql, "failed to get id.");
	
	while($row = db_fetch($result))
	{
		$sql1 = "UPDATE ".TB_PREF."sdma SET approval_2 = ". $_SESSION['wa_current_user']->user ."
		WHERE id = ".$row['id']." AND date_created>='2016-01-01' and date_created<='2016-12-31' ";
		//display_error($sql1);
		
		db_query($sql1);
		
		create_dm_from_sdma($row['id']);
	}

	display_notification("Updating is successful!");
	
	commit_transaction();
}

start_form();
start_row();
submit_center('Fix',_("Fix sdma"), true, '', false);
end_table();
end_form();
end_page();

?>
