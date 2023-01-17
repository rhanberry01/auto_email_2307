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

page('Fix Bank and GL Entry of Online Payments', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------
function update_ol_payments($bank_account, $bank_gl_account, $row)
{
	$sql = "UPDATE ".TB_PREF."bank_trans SET bank_act = $bank_account
				WHERE id = ".$row['id'];
	db_query($sql, 'failed to update bank_trans');
	
	$sql = "UPDATE ".TB_PREF."gl_trans SET account = '$bank_gl_account'
			WHERE type = ".$row['type']."
			AND type_no = ".$row['trans_no']." 
			AND amount < 0";
			// display_notification($sql);
	db_query($sql, 'failed to update gl_trans');
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT account_code, b.type , b.trans_no, b.id
			FROM 0_cv_header a, 0_bank_trans b, 0_bank_accounts c
			where online_payment = 2
			AND a.bank_trans_id = b.id
			AND c.id = b.bank_act";
			
	$res = db_query($sql);
	
	$company_pref = get_company_prefs();
	$bank_account = $company_pref['online_payment_bank_id'];
	$bank_gl_account = get_bank_gl_account($bank_account);
	
	while ($row = db_fetch($res))
	{
		update_ol_payments($bank_account, $bank_gl_account, $row);
	}
	
	display_notification('Done!');
}

start_form();
submit_center('fix_now', 'Fix Bank and GL Entry of ONLINE PAYMENTS');
end_form();

end_page();
?>
