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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "GET TB DISCREP"), false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
// if (isset($_GET['AddedID'])) {
	// $trans_no = $_GET['AddedID'];
	// $cvid = $_GET['CV_id'];
	// $trans_type = ST_CREDITDEBITDEPOSIT;
   	// display_notification_centered( _("Other Income has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	// display_footer_exit();
// }

//----------------------------------------------------------------------------------------
if (isset($_POST['Fix'])){
	global $Refs;
	
	set_time_limit(0);
	
	begin_transaction();	
	
	$account='2472';

		$sql_ = "SELECT * from transfers.0_branches";
		//display_error($sql);
		$result_=db_query($sql_);
		while($row_ = db_fetch($result_))
		{
			$myBranchCode=$row_['code'];
				
					$username = "root";
					$password = "";
					$hostname = "192.168.0.124"; 
					$myBranchCode=$row_['code'];
					$backup_aria_db=$row_['backup_aria_db'];
					//connection to the database
					$dbhandle = mysql_connect($hostname, $username, $password) 
					or die("Unable to connect to $hostname");
					//echo "Connected to 124<br>";
					//echo $backup_aria_db;

					//select a database to work with
					$selected = mysql_select_db($backup_aria_db,$dbhandle) 
					or die("Could not select db1 database.");	
					
					
					//=========================================================
					
					$my_po_from_ref = "SELECT *
					FROM $backup_aria_db.0_gl_trans
					WHERE tran_date >= '2013-01-01'
					AND  tran_date <= '2017-12-30'
					AND account ='$account'
					and amount!=0
					ORDER BY type_no
					";
					//display_error($my_po_from_ref);
					$ms_po_ref_res = mysql_query($my_po_from_ref);	
					//print_r($ms_po_ref_res);

					while($po_ref_row = mysql_fetch_array($ms_po_ref_res))
					{
						$type=$po_ref_row['type'];
						$type_no=$po_ref_row['type_no'];
						$amount=$po_ref_row['amount'];
						
						
						//display_error($type_no);
						
							$username_2 = "root";
							$password_2 = "srsnova";
							$hostname_2= "192.168.0.91"; 
							$myBranchCode_2=$row_['code'];
							$aria_db=$row_['aria_db'];
							//connection to the database
							$dbhandle_2 = mysql_connect($hostname_2, $username_2, $password_2) 
							or die("Unable to connect to $hostname_2");
							//echo "Connected to 89<br>";

							//select a database to work with
							$selected_2 = mysql_select_db($aria_db,$dbhandle_2) 
							or die("Could not select db2 database.");	
	
							$my_po_from_ref_2 = "SELECT *
							FROM $aria_db.0_gl_trans
							WHERE tran_date >= '2013-01-01'
							AND  tran_date <= '2017-12-30'
							AND account ='$account'
							and amount!=0
							and type=$type
							and type_no=$type_no
							and amount=$amount

							ORDER BY type_no
							";
							//display_error($my_po_from_ref_2);
							$ms_po_ref_res_2 = mysql_query($my_po_from_ref_2);	
							
							$count = mysql_num_rows($ms_po_ref_res_2);
							//display_error($count);
							if($count==0){
								display_error("Branch: ".$myBranchCode.", "."Type: ".$type.", "."Type #: ".$type_no.", "."Amount: ".$amount);
							}
					
					
					}
	
	
		}
	
	
	

	
	display_notification("Fixing cash deposit gl are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
}

start_form();
start_row();
submit_center('Fix',_("GET TB DISCREP"), true, '', false);
end_table();
end_form();
end_page();
?>