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
$page_security = 'SA_USERS';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "Users"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/admin/db/users_db.inc");

simple_page_mode(true);
//-------------------------------------------------------------------------------------------------

function can_process() 
{

	if (strlen($_POST['user_id']) < 4)
	{
		display_error( _("The user login entered must be at least 4 characters long."));
		set_focus('user_id');
		return false;
	}

	if ($_POST['password'] != "") 
	{
    	if (strlen($_POST['password']) < 4)
    	{
    		display_error( _("The password entered must be at least 4 characters long."));
			set_focus('password');
    		return false;
    	}

    	if (strstr($_POST['password'], $_POST['user_id']) != false)
    	{
    		display_error( _("The password cannot contain the user login."));
			set_focus('password');
    		return false;
    	}
	}
	// else{
		// display_error( _("The password entered must be at least 4 characters long."));
		// return false;
	// }

	return true;
}

//-------------------------------------------------------------------------------------------------

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{
	global $Ajax;
	$temp = array();
	foreach($_POST['limit'] as $key=>$items){
		$temp[] = $key;
	}
	
	$_POST['limit'] = $temp;
	// display_error($items);


	if (can_process())
	{
		// switch($_POST['can_']){
				// case 'Both' : $type = 3; break;
				// case 'Negative Inventory Only' : $type = 2; break;
				// case 'Credit Note Only' : $type = 1; break;
			// }
	
    	if ($selected_id != -1) 
    	{
			// if(!isset($_POST['allow_user']))
			// $_POST['allow_user']=0;
			
    		update_user($selected_id, $_POST['user_id'], $_POST['real_name'], $_POST['phone'],
    			$_POST['email'], $_POST['Access'], $_POST['language'], 
				$_POST['profile'], check_value('rep_popup'), $_POST['pos'], $_POST['is_supervisor'],
				$_POST['limit_'], check_value('can_approve_cv'), check_value('can_approve_sales_remittance'),
				check_value('can_approve_sdma_1'), check_value('can_approve_sdma_2'));

    		if ($_POST['password'] != "")
    			update_user_password($selected_id, $_POST['user_id'], md5($_POST['password']));

    		display_notification_centered(_("The selected user has been updated."));
    	} 
    	else 
    	{
			// display_error($_POST['can_']);
			// foreach($_POST['limit'] as $items=>$val)
			// display_error($items.' = '.$val);
    		add_user($_POST['user_id'], $_POST['real_name'], md5($_POST['password']),
				$_POST['phone'], $_POST['email'], $_POST['Access'], $_POST['language'],
				$_POST['profile'], check_value('rep_popup'), $_POST['pos'], $_POST['is_supervisor'],$_POST['limit'], 
				check_value('can_approve_cv'), check_value('can_approve_sales_remittance'),
				check_value('can_approve_sdma_1'), check_value('can_approve_sdma_2'));
			$id = db_insert_id();
			// use current user display preferences as start point for new user
			update_user_display_prefs($id, user_price_dec(), user_qty_dec(), user_exrate_dec(), 
				user_percent_dec(), user_show_gl_info(), user_show_codes(), 
				user_date_format(), user_date_sep(), user_tho_sep(), 
				user_dec_sep(), user_theme(), user_pagesize(), user_hints(), 
				$_POST['profile'], check_value('rep_popup'), user_query_size(), 
				user_graphic_links(), $_POST['language'], sticky_doc_date(), user_startup_tab());

			display_notification_centered(_("A new user has been added."));
    	}
		 $Mode = 'RESET';
	}
	
	$Ajax->activate('page_body');
}

//-------------------------------------------------------------------------------------------------

if ($Mode == 'Delete')
{
	delete_user($selected_id);
	display_notification_centered(_("User has been deleted."));
	$Mode = 'RESET';
}

//-------------------------------------------------------------------------------------------------
if ($Mode == 'RESET')
{
 	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);	// clean all input fields
	$_POST['show_inactive'] = $sav;
}

$result = get_users(check_value('show_inactive'));
start_form();
start_table($table_style);

$th = array(_("User login"), _("Full Name"), _("Phone"),
	_("E-mail"), _("Last Visit"), _("Access Level"), "", "");

inactive_control_column($th);
table_header($th);	

$k = 0; //row colour counter

while ($myrow = db_fetch($result)) 
{

	alt_table_row_color($k);

	$last_visit_date = sql2date($myrow["last_visit_date"]);

	/*The security_headings array is defined in config.php */
	$not_me = strcasecmp($myrow["user_id"], $_SESSION["wa_current_user"]->username);

	label_cell($myrow["user_id"]);
	label_cell($myrow["real_name"]);
	label_cell($myrow["phone"]);
	email_cell($myrow["email"]);
	label_cell($last_visit_date, "nowrap");
	label_cell($myrow["role"]);
	
    if ($not_me)
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'users', 'id');
	elseif (check_value('show_inactive'))
		label_cell('');

	edit_button_cell("Edit".$myrow["id"], _("Edit"));
    if ($not_me)
 		delete_button_cell("Delete".$myrow["id"], _("Delete"));
	else
		label_cell('');
	end_row();

} //END WHILE LIST LOOP

inactive_control_row($th);
end_table(1);
//-------------------------------------------------------------------------------------------------
start_table($table_style2);

$_POST['email'] = "";
if ($selected_id != -1) 
{
  	if ($Mode == 'Edit') {
		//editing an existing User
		$myrow = get_user($selected_id);

		$_POST['id'] = $myrow["id"];
		$_POST['user_id'] = $myrow["user_id"];
		$_POST['real_name'] = $myrow["real_name"];
		$_POST['phone'] = $myrow["phone"];
		$_POST['email'] = $myrow["email"];
		$_POST['Access'] = $myrow["role_id"];
		$_POST['language'] = $myrow["language"];
		$_POST['profile'] = $myrow["print_profile"];
		$_POST['rep_popup'] = $myrow["rep_popup"];
		$_POST['can_approve_cv'] = $myrow["can_approve_cv"];
		$_POST['can_approve_sales_remittance'] = $myrow["can_approve_sales_remittance"];
		$_POST['can_approve_sdma_1'] = $myrow["can_approve_sdma_1"];
		$_POST['can_approve_sdma_2'] = $myrow["can_approve_sdma_2"];
		$_POST['pos'] = $myrow["pos"];
		$_POST['is_supervisor'] = $myrow["is_supervisor"];
		$_POST['allow_user'] = $myrow["allow_user"];
		
		
		hidden('id', $_POST['id']);
		
	}
	hidden('selected_id', $selected_id);
	hidden('user_id');

	start_row();
	label_row(_("User login:"), $_POST['user_id']);
} 
else 
{ //end of if $selected_id only do the else when a new record is being entered
	text_row(_("User Login:"), "user_id",  null, 22, 20);
	$_POST['language'] = user_language();
	$_POST['profile'] = user_print_profile();
	$_POST['rep_popup'] = user_rep_popup();
	$_POST['pos'] = user_pos();
}
// $_POST['password'] = "";
password_row(_("Password:"), 'password', $_POST['password']);

if ($selected_id != -1) 
{
	table_section_title(_("Enter a new password to change, leave empty to keep current."));
}

text_row_ex(_("Full Name").":", 'real_name',  50);

text_row_ex(_("Telephone No.:"), 'phone', 30);

email_row_ex(_("Email Address:"), 'email', 50);

security_roles_list_row(_("Role :"), 'Access', null); 

languages_list_row(_("Language:"), 'language', null);

pos_list_row(_("User's POS"). ':', 'pos', null);

print_profiles_list_row(_("Printing profile"). ':', 'profile', null,
	_('Browser printing support'));

check_row(_("Use popup window for reports:"), 'rep_popup', $_POST['rep_popup'],
	false, _('Set this option to on if your browser directly supports pdf files'));

check_row(_("CV Approver:"), 'can_approve_cv', $_POST['can_approve_cv'],false);
check_row(_("Sales Remittance Approver:"), 'can_approve_sales_remittance', $_POST['can_approve_sales_remittance'],false);
	
check_row(_("Debit Memo Approver 1:"), 'can_approve_sdma_1', $_POST['can_approve_sdma_1'],false);
check_row(_("Debit Memo Approver 2:"), 'can_approve_sdma_2', $_POST['can_approve_sdma_2'],false);
// check_row(_("Allow user account in prompt"), 'allow_user', $_POST['allow_user'],
	// false, _('Set this option to allow this user to be entered in user validation.'));

yesno_list_row(_("Supervisor:"), 'is_supervisor',null,null,null,true);

if(list_updated('is_supervisor')||$Mode == 'Edit'){
	if($_POST['is_supervisor']=='1')
	{
		// $sql = "SELECT *
				  // FROM 0_supervisor_type";
		// $sql = db_query($sql);
		// $count = db_num_rows($sql);
				// echo "<td rowspan=".($count+1).">Can Process: </td>";
			// while($data = db_fetch($sql)){
				// start_row();
				// echo "<td><input type=checkbox value=".$data['id'].">".$data['type']."</option></td>";
				// end_row();
			// }
		$Ajax->activate('supr');
	}
	// supervisor_type(_("Can Process:"), 'can_', null);
}

if($_POST['is_supervisor']=='1'){
div_start('supr');

		$type = array(1=>'Credit Limit',
						   2=>'Negative Inventory',
						   3=>'SO Editing',
						   4=>'Voiding',
						   5=>'PO Editing',
						   6=>'Sales Order Approval',
						   7=>'Purchase Order Approval'
		);
		
		$count = count($type);
		// echo "<td rowspan=".($count+1).">Can Process</td>";
		foreach($type as $key=>$value){
			switch($key)
			{
				case 1:
					$sql = "SELECT can_credit_limit FROM ".TB_PREF."users WHERE user_id = ".db_escape($_POST['user_id']);
				break;
				case 2:
					$sql = "SELECT can_negative_inv FROM ".TB_PREF."users WHERE user_id = ".db_escape($_POST['user_id']);
				break;
				case 3:
					$sql = "SELECT can_details FROM ".TB_PREF."users WHERE user_id = ".db_escape($_POST['user_id']);
				break;
				case 4:
					$sql = "SELECT can_void FROM ".TB_PREF."users WHERE user_id = ".db_escape($_POST['user_id']);
				break;
				case 5:
					$sql = "SELECT can_edit FROM ".TB_PREF."users WHERE user_id = ".db_escape($_POST['user_id']);
				break;
				case 6:
					$sql = "SELECT can_approve_so FROM ".TB_PREF."users WHERE user_id = ".db_escape($_POST['user_id']);
				break;
				case 7:
					$sql = "SELECT can_approve_po FROM ".TB_PREF."users WHERE user_id = ".db_escape($_POST['user_id']);
				break;
				
			}
			
			$res_ = db_query($sql);
			$rowrow = db_fetch($res_);
			
			if ($rowrow[0] == 1)
				$_POST['limit['.$key.']'] = 1;
			
			start_row();
			// echo "<td><input type='checkbox' name='limit_[$key]' value=".$key.">".$value."</td>";
			check_cells("<font color=red>".$type[$key]."</font>",'limit['.$key.']',
			isset($_POST['limit['.$key.']']));
			// check_cells("<font color=red>".$type[$key]."</font>",'limit['.$key.']',1);
			// check_value('limit_[$key]');
		}
div_end();
}

end_table(1);

submit_add_or_update_center($selected_id == -1, '', 'both');

end_form();
end_page();
?>
