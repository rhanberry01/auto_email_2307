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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Consolidate GL accounts', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
function update_same_gl($row)
{
	global $db_connections;
	
	foreach($db_connections as $key=>$db_con)
	{
		$sql = "UPDATE ".$db_con['dbname'].'.'.$db_con['tbpref']."gl_trans SET account = ". $row['new_code'] ."
					WHERE account IN (".$row['accounts'].")";
		db_query($sql,'failed to update gl');
	}
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT new_code, GROUP_CONCAT(account) as accounts
				FROM `zzz_gl_conso`
				WHERE new_code != ''
				GROUP BY new_code;";
	$res = db_query($sql);
	while($row = db_fetch($res))
	{
		update_same_gl($row);
	}
	
	$sql = "SELECT GROUP_CONCAT(account) 
					FROM `zzz_gl_conso`
					WHERE account != new_code";
	$res = db_query($sql);
	$row__ = db_fetch($res);
	
	global $db_connections;
	
	foreach($db_connections as $key=>$db_con)
	{
		// //============================ UPDATE chart master
		// $sql = "UPDATE ".$db_con['dbname'].'.'.$db_con['tbpref']."chart_master SET inactive=1 WHERE account_code IN (".$row__[0].")";
		// // display_error($sql);die;
		// db_query($sql);
		
		// //============================ UPDATE bank accounts
		// $sql = "SELECT account_code FROM ".$db_con['dbname'].'.'.$db_con['tbpref']."bank_accounts
		// WHERE account_code
		// IN (SELECT account
							// FROM srs_aria_nova.`zzz_gl_conso`
							// WHERE account != new_code AND new_code != '')";
		// $res = db_query($sql);
		
		// display_error($db_con['dbname'] . "  ". $sql);
		while($row = db_fetch($res))
		{
			$sql = "SELECT account, new_code FROM srs_aria_nova.`zzz_gl_conso` WHERE account = ". $row['account_code'];
			$res2 = db_query($sql);
			$row2 = db_fetch($res2);
			$sql3 = "UPDATE ".$db_con['dbname'].'.'.$db_con['tbpref']."bank_accounts SET account_code=".$row2['new_code']." 
							WHERE account_code = ".$row['account_code'];
			// display_error($sql3);
			db_query($sql3);
		}
		
		// // //============================ UPDATE check accounts
		// // $sql = "SELECT bank_ref FROM ".$db_con['dbname'].'.'.$db_con['tbpref']."check_account 
					// // WHERE bank_ref
					// // IN (1001050302,1001050312,1010010,1010055,10102299,1020011,1030042,1030071,1030090,1430013,1483,1493,2320,2460)";
		// // $res = db_query($sql);
		
		// // // display_error($db_con['dbname'] . "  ". $sql);
		// // while($row = db_fetch($res))
		// // {
			// // $sql = "SELECT account, new_code FROM srs_aria_nova.`zzz_gl_conso` WHERE account = ". $row['bank_ref'];
			// // $res2 = db_query($sql);
			// // $row2 = db_fetch($res2);
			// // $sql3 = "UPDATE ".$db_con['dbname'].'.'.$db_con['tbpref']."check_account SET bank_ref=".$row2['new_code']." 
							// // WHERE bank_ref = ".$row['bank_ref'];
			// // // display_error($sql3);
			// // db_query($sql3);
		// // }
	}
	
	display_notification('DONE');
}

start_form();

submit_center('fix_now', 'GO');
end_form();

end_page();
?>
