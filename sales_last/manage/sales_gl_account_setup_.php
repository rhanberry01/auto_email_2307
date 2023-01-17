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
simple_page_mode(false);


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Cash-Deposit in Transit GL Debit Accounts"), false, false, "", $js);

//----------------------------------------------------------------------------------

if (isset($_POST['submit']))
{
update_sales_gl_account_setup(
$_POST['sales_account'],
$_POST['cash_account'],
$_POST['cash_tender'],
$_POST['gc_account'],
$_POST['gc_tender'],
$_POST['suki_account'],
$_POST['suki_tender'],
$_POST['debit_account'],
$_POST['debit_tender'],
$_POST['credit_account'],
$_POST['credit_tender'],
$_POST['check_account'],
$_POST['check_tender'],
$_POST['terms_account'],
$_POST['terms_tender'],
$_POST['evoucher_account'],
$_POST['evoucher_tender'],
$_POST['receivable_account'],
$_POST['shortage_account'],
$_POST['overage_account'],
$_POST['cash_in_bank'],
$_POST['check_in_transit']);
display_notification(_("The Sales GL Account Setup has been Updated."));

} /* end of if submit */


//-------------------------------------------------------------------------------------------


start_form();

//start_outer_table("class='tablestyle'");
start_outer_table($table_style2, 5);

table_section(1);

$myrow = get_sales_gl_account_prefs();
$_POST['sales_account'] = $myrow["sales_account"];
$_POST['cash_account'] = $myrow["cash_account"];
$_POST['cash_tender'] = $myrow["cash_tender"];
$_POST['gc_account'] = $myrow["gc_account"];
$_POST['gc_tender'] = $myrow["gc_tender"];
$_POST['suki_account'] = $myrow["suki_account"];
$_POST['suki_tender'] = $myrow["suki_tender"];
$_POST['debit_account'] = $myrow["debit_account"];
$_POST['debit_tender'] = $myrow["debit_tender"];
$_POST['credit_account'] = $myrow["credit_account"];
$_POST['credit_tender'] = $myrow["credit_tender"];
$_POST['check_account'] = $myrow["check_account"];
$_POST['check_tender'] = $myrow["check_tender"];
$_POST['terms_account'] = $myrow["terms_account"];
$_POST['terms_tender'] = $myrow["terms_tender"];
$_POST['evoucher_account'] = $myrow["evoucher_account"];
$_POST['evoucher_tender'] = $myrow["evoucher_tender"];
$_POST['receivable_account'] = $myrow["receivable_account"];
$_POST['shortage_account'] = $myrow["shortage"];
$_POST['overage_account'] = $myrow["overage"];
$_POST['cash_in_bank'] = $myrow["cash_in_bank"];
$_POST['check_in_transit'] = $myrow["check_in_transit"];

//-----------------------------------------------------------------




start_form();
start_outer_table($table_style2, 5);
//FORM
table_section(1);
table_section_title(_("Sales GL Account Maintenance"));
gl_all_accounts_list_row("Sales Account:", 'sales_account', $_POST['sales_account']);
gl_all_accounts_list_row(_("Cash in Transit Account:"), 'cash_account', $_POST['cash_account']);
tender_list_row("Cash Tender Type:", 'cash_tender', $_POST['cash_tender']); 
gl_all_accounts_list_row(_("GC Account:"), 'gc_account', $_POST['gc_account']);
tender_list_row("GC Tender Type:", 'gc_tender',  $_POST['gc_tender']); 
gl_all_accounts_list_row(_("Suki Card Account:"), 'suki_account', $_POST['suki_account']);
tender_list_row("Suki Card Tender Type:", 'suki_tender',   $_POST['suki_tender']); 
gl_all_accounts_list_row(_("Debit Card Account:"), 'debit_account', $_POST['debit_account']);
tender_list_row("Debit Card Tender Type:", 'debit_tender',  $_POST['debit_tender']); 
gl_all_accounts_list_row(_("Credit Card Account:"), 'credit_account', $_POST['credit_account']);
tender_list_row("Credit Card Tender Type:", 'credit_tender',  $_POST['credit_tender']); 
gl_all_accounts_list_row(_("Check Account:"), 'check_account', $_POST['check_account']);
tender_list_row("Check Tender Type:", 'check_tender',  $_POST['check_tender']); 
gl_all_accounts_list_row(_("Terms Account:"), 'terms_account', $_POST['terms_account']);
tender_list_row("Terms Tender Type:", 'terms_tender',  $_POST['terms_tender']); 
gl_all_accounts_list_row(_("EVoucher Account:"), 'evoucher_account', $_POST['evoucher_account']);
tender_list_row("EVoucher Tender Type:", 'evoucher_tender',  $_POST['evoucher_tender']); 
gl_all_accounts_list_row(_("Receivable Account:"), 'receivable_account', $_POST['receivable_account']);
gl_all_accounts_list_row(_("Shortage Account:"), 'shortage_account', $_POST['shortage_account']);
gl_all_accounts_list_row(_("Overage Account:"), 'overage_account', $_POST['overage_account']);

table_section_title(_("Cash Deposit"));
gl_all_accounts_list_row(_("Cash in Bank:"), 'cash_in_bank', $_POST['cash_in_bank']);

table_section_title(_("Check Deposit"));
gl_all_accounts_list_row(_("Check in Transit:"), 'check_in_transit', $_POST['check_in_transit']);
end_outer_table(1);
end_table(1);

submit_center('submit', _("Update"), true, '', 'default');
end_form();

end_page();


?>