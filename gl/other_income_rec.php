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
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/check_cart_2.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
$page_security = isset($_GET['NewPayment']) || 
	@($_SESSION['pay_items']->trans_type==ST_BANKPAYMENT)
 ? 'SA_PAYMENT' : 'SA_DEPOSIT';

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/ui/gl_bank_ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");

include_once($path_to_root . "/modules/checkprint/includes/check_accounts_db.inc");
add_access_extensions();

if(isset($_GET['NewIncome'])) {
	$_SESSION['checks'] = new check_cart(ST_BANKDEPOSIT,0);
}
$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

if(isset($_GET['NewIncome'])) {
	$_SESSION['page_title'] = _($help_context = "Other Income Receivable Entry");
}
page($_SESSION['page_title'], false, false, '', $js);

//-----------------------------------------------------------------------------------------------
check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//-----------------------------------------------------------------------------------------------
if (list_updated('PersonDetailID')) {
	$br = get_branch(get_post('PersonDetailID'));
	$_POST['person_id'] = $br['debtor_no'];
	$Ajax->activate('person_id');
}

if (isset($_GET['AddedDep']))
{
	$trans_no = $_GET['AddedDep'];
	$trans_type = ST_BANKDEPOSIT;

   	display_notification_centered(_("Other Income Receivable $trans_no has been entered."));

	//display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL Postings for this Deposit")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Receivable"), "NewIncome=yes");

	display_footer_exit();
}

if (isset($_POST['_date__changed'])) {
	$Ajax->activate('_ex_rate');
}
//--------------------------------------------------------------------------------------------------
if (isset($_POST['Create']))
{

	$input_error = 0;

	if (!is_date($_POST['date_']))
	{
		display_error(_("The seleted date for receivable is invalid."));
		set_focus('date_');
		$input_error = 1;
	}
	
		if (($_POST['person_id'])=='')
	{
		display_error(_("Please select or provide where the receivable came from."));
		set_focus('date_');
		$input_error = 1;
	}
	
	
		if (($_POST['rec_type']==0) or ($_POST['rec_type']==''))
	{
		display_error(_("Please select receivable type."));
		set_focus('date_');
		$input_error = 1;
	}
	
		if (($_POST['amount']==0) or ($_POST['amount']==''))
	{
		display_error(_("Gross Amount cannot be empty or zero."));
		set_focus('date_');
		$input_error = 1;
	}
	

	if ($input_error == 1)
		unset($_POST['Create']);
}
														if (isset($_POST['Create']))
														{
														$trans = create_other_income($_SESSION['pay_items']->trans_type, $_POST['bank_account'],
														$_SESSION['pay_items'], $_POST['date_'],$_POST['PayType'], $_POST['person_id'], get_post('PersonDetailID'),
														$_POST['ref'], $_POST['memo_'], true, $_SESSION['checks']);

														$trans_type = $trans[0];
														$trans_no = $trans[1];
														new_doc_date($_POST['date_']);

														//$_SESSION['pay_items']->clear_items();
												unset($_SESSION['pay_items']);
												meta_forward($_SERVER['PHP_SELF'], "AddedDep=$trans_no");
														} /*end of process credit note */
												
//-----------------------------------------------------------------------------------------------
start_form();
display_heading("Create New Receivable");	
br();
other_income_rec_header($_SESSION['pay_items']);		
start_table("$table_style2 width=90%", 10);
start_row();

end_row();
end_table(1);
br();
submit_center_first('Update', _("Update"), '', null);
submit_center_last('Create', $_SESSION['pay_items']->trans_type==ST_BANKPAYMENT ?_("Approve Receivable"):_("Create"), '', 'default');
end_form();
//----------------------------------------------------------------------------------------------
end_page();
?>