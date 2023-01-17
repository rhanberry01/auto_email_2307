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

page('Fix EWT GL Entry of Online Payments', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------
function fix_ewt_gl($trans_no,$ewt_amount,$date_,$person_id)
{
	$sql = "UPDATE ".TB_PREF."gl_trans SET amount = amount+$ewt_amount
			WHERE type=22 AND type_no=$trans_no
			AND account = '2000'";
	db_query($sql,'failed to update GL Accounts Payable');
	if (db_num_affected_rows() == 0)
	{
		$sql = "UPDATE ".TB_PREF."gl_trans SET amount = amount+$ewt_amount
			WHERE type=22 AND type_no=$trans_no
			AND account = '2000010'";
		db_query($sql,'failed to update GL Accounts Payable');
	}
	
	add_gl_trans(22, $trans_no, $date_, '2330', 0, 0, '',-$ewt_amount, null, 3, $person_id);
}

if (isset($_POST['fix_now']))
{
	set_time_limit(0);
	$sql = "SELECT a.ewt,c.*
			FROM 0_cv_header a, 0_cv_details b, 0_gl_trans c
			WHERE a.id = b.cv_id
			AND a.ewt > 0
			AND a.amount > 0
			AND a.online_payment = 2
			AND b.trans_type = 22
			AND b.trans_no = c.type_no
			AND c.type = 22
			AND  0 = (SELECT COUNT(z.type_no) FROM 0_gl_trans z 
								WHERE z.type = 22 AND z.type_no = c.type_no
								AND z.account = 2330)
			AND account = 2000
			ORDER BY cv_no";
			
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		fix_ewt_gl($row['type_no'],$row['ewt'],sql2date($row['tran_date']),$row['person_id']);
	}
	
	display_notification('Success');
}

start_form();
submit_center('fix_now', 'Fix EWT GL Entry of ONLINE PAYMENTS');
end_form();

end_page();
?>
