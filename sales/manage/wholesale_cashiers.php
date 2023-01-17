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
	
page(_($help_context = "Wholesale Cashier Setup"), false, false, "", $js);


//----------------------------------------------------------------------------------

//START OF ADD/UPDATE 

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	$inpug_error = 0;

	if (strlen($_POST['cashier_id']) == 0) 
	{
		$inpug_error = 1;
		display_error( _("The Cashier ID must be entered."));
		set_focus('agency_code');
	} 

	else if (strlen($_POST['cashier_name']) == 0) 
	{
		$inpug_error = 1;
		display_error( _("Cashier Name must be entered."));
		set_focus('cashier_name');
	} 
	
	if ($inpug_error != 1)
	{
    	if ($selected_id != '') 
    	{
			$sql = "UPDATE ".TB_PREF."wholesale_cashiers SET
							cashier_id=" . db_escape($_POST['cashier_id']) . ",
							cashier_name=" . db_escape($_POST['cashier_name']) . "
						WHERE id= " .db_escape( $selected_id );
		
 			$note = _('Selected Cashier has been updated');
    	} 
    	else 
    	{
			$sql = "INSERT INTO ".TB_PREF."wholesale_cashiers (cashier_id, cashier_name)
				VALUES (" . db_escape($_POST['cashier_id']) .',' .db_escape($_POST['cashier_name']) . ")";
			$note = _('New Cashier has been added');
    	}
    	//run the sql from either of the above possibilites
    	db_query($sql,"Cashier could not be added or updated");
		
		$_POST['cashier_id']  = '';
		$_POST['cashier_name']  = '';
			
		display_notification($note);
 		$Mode = 'RESET';
	}
}
//END OF ADD/UPDATE



//START OF DELETE 
if ($Mode == 'Delete')
{
	$sql="DELETE FROM ".TB_PREF."wholesale_cashiers WHERE id=".db_escape($selected_id);
	db_query($sql,"could not delete the cashier");
	display_notification(_('Selected cashier has been deleted'));

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
$sql = "SELECT * FROM ".TB_PREF."wholesale_cashiers";
if (!check_value('show_inactive')) $sql .= " WHERE !inactive";
$sql .= ' ORDER BY cashier_id';
$result = db_query($sql,"could not get agency");

start_form();
start_table('width=30% ' .$table_style2);
$th = array(_("Cashier ID"), _("Cashier Name"), "", "");
inactive_control_column($th);
table_header($th);

$k = 0; //row colour counter
while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);

    label_cell($myrow["cashier_id"]);
    label_cell($myrow['cashier_name']);
	inactive_control_cell($myrow["id"], $myrow["inactive"], 'wholesale_cashiers', "id");
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
		$sql = "SELECT * FROM ".TB_PREF."wholesale_cashiers
			WHERE id=".db_escape($selected_id);

		$result = db_query($sql,"could not get cashier");
		$myrow = db_fetch($result);

		$_POST['cashier_id']  = $myrow["cashier_id"];
		$_POST['cashier_name']  = $myrow["cashier_name"];

	}
	hidden('selected_id', $selected_id);
}

text_row(_("Cashier ID"), 'cashier_id', null, 40, 40);
text_row(_("Cashier Name:"), 'cashier_name', null, 40, 40);


end_table(1);

submit_add_or_update_center($selected_id =='', '', 'both');

end_form();
//END OF INSERT 


end_page();
?>