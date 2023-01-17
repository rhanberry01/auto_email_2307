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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/sales_gl_db.inc");
simple_page_mode(false);


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Tender Types Setup"), false, false, "", $js);


//----------------------------------------------------------------------------------

//START OF ADD/UPDATE 

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$inpug_error = 0;

	if (strlen($_POST['TenderCode']) == 0) 
	{
		$inpug_error = 1;
		display_error( _("The Tender Code must be entered."));
		set_focus('TenderCode');
	} 

	else if (strlen($_POST['Description']) == 0) 
	{
		$inpug_error = 1;
		display_error( _("Tender Description must be entered."));
		set_focus('Description');
	} 
	
	if ($inpug_error != 1)
	{
    	if ($selected_id != '') 
    	{
			$sql = "UPDATE ".TB_PREF."tendertypes SET
							TenderCode=" . db_escape($_POST['TenderCode']) . ",
							Description=" . db_escape($_POST['Description']) . "
						WHERE id= " .db_escape( $selected_id );
		
 			$note = _('Selected tender type has been updated');
    	} 
    	else 
    	{
			$sql = "INSERT INTO ".TB_PREF."tendertypes (TenderCode, Description)
				VALUES (".db_escape($_POST['TenderCode']).','.db_escape($_POST['Description']).")";
			$note = _('New tender type has been added');
    	}
    	//run the sql from either of the above possibilites
    	db_query($sql,"The tender type could not be added or updated");
		
		$_POST['TenderCode']  = '';
		$_POST['Description']  = '';
			
		display_notification($note);
 		$Mode = 'RESET';
	}
}
//END OF ADD/UPDATE


//START OF DELETE 
if ($Mode == 'Delete')
{
	$sql="DELETE FROM ".TB_PREF."tendertypes WHERE id=".db_escape($selected_id);
	db_query($sql,"could not delete a tender type");
	display_notification(_('Selected tender type has been deleted'));

	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = '';
	$sav = check_value('show_inactive');
	unset($_POST['show_inactive']);
	// unset($_POST);
	// $_POST['show_inactive'] = $sav;
}
//START OF DELETE 



//-------------------------------------------------------------------------------------------------

//START OF DISPLAY DATA INTO TABLE


$sql = "SELECT * FROM ".TB_PREF."tendertypes";
if (!check_value('show_inactive')) $sql .= " WHERE !inactive";
$sql .= ' ORDER BY TenderCode';
$result = db_query($sql,"could not get tender types");

start_form();
start_table('width=30% ' .$table_style2);
$th = array(_("Tender Code"), _("Description"), "", "");
inactive_control_column($th);
table_header($th);

$k = 0; //row colour counter
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);

    label_cell($myrow["TenderCode"]);
    label_cell($myrow['Description']);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'tendertypes', "id");
 	edit_button_cell("Edit".$myrow["id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["id"], _("Delete"));
    end_row();
	
} //END WHILE LIST LOOP

inactive_control_row($th);
end_table(1);
//END OF DISPLAY DATA INTO TABLE


//-------------------------------------------------------------------------------------------------


//START OF INSERT 
start_table($table_style2);

$day_in_following_month = $days_before_due = 0;
if ($selected_id != '') 
{
	if ($Mode == 'Edit') {
		//editing an existing payment terms
		$sql = "SELECT * FROM ".TB_PREF."tendertypes
			WHERE id=".db_escape($selected_id);

		$result = db_query($sql,"could not get tender type");
		$myrow = db_fetch($result);

		$_POST['TenderCode']  = $myrow["TenderCode"];
		$_POST['Description']  = $myrow["Description"];

	}
	hidden('selected_id', $selected_id);
}

text_row(_("Tender Code"), 'TenderCode', null, 40, 40);
text_row(_("Tender Type:"), 'Description', null, 40, 40);


end_table(1);

submit_add_or_update_center($selected_id == '', '', 'both');

end_form();
//END OF INSERT 


end_page();


?>