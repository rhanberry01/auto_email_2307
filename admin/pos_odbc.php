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
$path_to_root="..";
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/admin/db/company_db.inc");
include_once($path_to_root . "/admin/db/maintenance_db.inc");
include_once($path_to_root . "/includes/ui.inc");

page(_($help_context = "POS ODBC"));

simple_page_mode(true);
//-------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$inpug_error = 0;

	if (strlen($_POST['terminal_no']) == 0) 
	{
		$inpug_error = 1;
		display_error( _("The Terminal No. must be entered."));
		set_focus('terminal_no');
	} 

	else if (strlen($_POST['odbc']) == 0) 
	{
		$inpug_error = 1;
		display_error( _("The ODBC Name must be entered."));
		set_focus('odbc');
	} 

	if ($inpug_error != 1)
	{
    	if ($selected_id != -1) 
    	{
			$sql = "UPDATE ".TB_PREF."terminal_odbc SET
							terminal_no=" . db_escape($_POST['terminal_no']) . ",
							odbc=" . db_escape($_POST['odbc']) . "
						WHERE id= " .db_escape( $selected_id );
		
 			$note = _('Selected payment terms have been updated');
    	} 
    	else 
    	{
			$sql = "INSERT INTO ".TB_PREF."terminal_odbc (terminal_no,odbc)
				VALUES (" . db_escape($_POST['terminal_no']) .',' .db_escape($_POST['odbc']) . ")";
			$note = _('New payment terms have been added');
    	}
    	//run the sql from either of the above possibilites
    	db_query($sql,"The payment term could not be added or updated");
		display_notification($note);
 		$Mode = 'RESET';
	}
}

if ($Mode == 'Delete')
{
	$sql="DELETE FROM ".TB_PREF."terminal_odbc WHERE id=".db_escape($selected_id);
	db_query($sql,"could not delete a terminal-ODBC");
	display_notification(_('Selected Terminal-ODBC name have been deleted'));

	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = check_value('show_inactive');
	unset($_POST['show_inactive']);
	// unset($_POST);
	// $_POST['show_inactive'] = $sav;
}
//-------------------------------------------------------------------------------------------------

$sql = "SELECT * FROM ".TB_PREF."terminal_odbc";
if (!check_value('show_inactive')) $sql .= " WHERE !inactive";
$result = db_query($sql,"could not get odbc names");

start_form();
start_table('width=30% ' .$table_style2);
$th = array(_("Terminal"), _("ODBC Name"), "", "");
inactive_control_column($th);
table_header($th);

$k = 0; //row colour counter
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);

    label_cell($myrow["terminal_no"]);
    label_cell($myrow['odbc']);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'terminal_odbc', "id");
 	edit_button_cell("Edit".$myrow["id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["id"], _("Delete"));
    end_row();
	
} //END WHILE LIST LOOP

inactive_control_row($th);
end_table(1);

//-------------------------------------------------------------------------------------------------

start_table($table_style2);

$day_in_following_month = $days_before_due = 0;
if ($selected_id != -1) 
{
	if ($Mode == 'Edit') {
		//editing an existing payment terms
		$sql = "SELECT * FROM ".TB_PREF."terminal_odbc
			WHERE id=".db_escape($selected_id);

		$result = db_query($sql,"could not get odbc terminal odbc");
		$myrow = db_fetch($result);

		$_POST['terminal_no']  = $myrow["terminal_no"];
		$_POST['odbc']  = $myrow["odbc"];

	}
	hidden('selected_id', $selected_id);
}
text_row(_("Terminal No. <i>(Ex. 001)</i>"), 'terminal_no', null, 40, 40);
text_row(_("ODBC Name:"), 'odbc', null, 40, 40);

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();

end_page();
?>