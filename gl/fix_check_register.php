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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
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
page(_($help_context = "COMPARE OLD & NEW CHECK REGISTER"), false, false, "", $js);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	//$cvid = $_GET['CV_id'];
	//$trans_type = ST_CREDITDEBITDEPOSIT;
   	display_notification_centered( _("Other Income has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	display_footer_exit();
}
// $and1 = " AND tax_group_id = 1 AND TRIM(gst_no) != ''"; //vat
// $and2 = " AND tax_group_id != 1 AND TRIM(gst_no) != ''"; //nv
// $and3 = " AND TRIM(gst_no) = ''"; //dr


			$sql = "SELECT * FROM `check_old_register`";
			//display_error($sql);
			$res = db_query($sql,'error.');
								
			while($row = db_fetch($res))
			{
				$old_check[]="'".$row['old_check']."'";
			}
			$old_check_nos=implode(",",$old_check);
			
			
			
			$sql = "SELECT * FROM `check_new_register` where new_check not in ($old_check_nos)";
			$res = db_query($sql,'error.');
			//display_error($sql);
								
				while($row = db_fetch($res))
				{		
					$m=date("F", strtotime($row['new_date']));
					if($row['new_type']==0){
						$type='TRADE';
					}
					else
					{
						$type='NON-TRADE';
					}
					
					display_error("This check number is not in old check register: ".$row['new_check']." $type, month of $m");
		
				}
				

			$sql = "SELECT * FROM `check_new_register`";
			//display_error($sql);
			$res = db_query($sql,'error.');
								
			while($row = db_fetch($res))
			{
				$new_check[]="'".$row['new_check']."'";
				
						$sql1 = "SELECT * FROM 0_bank_trans as bt
						LEFT JOIN 0_check_trans as ct
						on bt.id=ct.bank_trans_id
						WHERE check_ref='".$row['new_check']."'";

						$res1 = db_query($sql1,'error.');

						while($row1 = db_fetch($res1))
						{
						if ($row1['amount']==0){
						$m=date("F", strtotime($row['new_date']));
							if($row['new_type']==0){
								$type='TRADE';
							}
							else
							{
								$type='NON-TRADE';
							}

						display_error("This Check number is already voided in new check register : ".$row['new_check']." $type, month of $m");
						}
						}
				
			}
			
			$new_check_nos=implode(",",$new_check);
			
			$sql = "SELECT * FROM `check_old_register` where old_check not in ($new_check_nos)";
			$res = db_query($sql,'error.');
			//display_error($sql);
								
				while($row = db_fetch($res))
				{		
					$m=date("F", strtotime($row['old_date']));
							if($row['old_type']==0){
								$type='TRADE';
							}
							else
							{
								$type='NON-TRADE';
							}
					
					display_error("This check number is not in new check register: ".$row['old_check']." $type,  month of $m");
				}

	// display_error('=======================OLD CHECK REGISTER===========================');
	// $sql = "SELECT * FROM `check_old_register`";
	// $res = db_query($sql,'error.');
								
			// while($row = db_fetch($res))
			// {
		
				// $sql1 = "SELECT * FROM 0_bank_trans as bt
				// LEFT JOIN 0_check_trans as ct
				// on bt.id=ct.bank_trans_id
				// WHERE check_ref='".$row['old_check']."'";
				
						// $res1 = db_query($sql1,'error.');
								
						// while($row1 = db_fetch($res1))
						// {
							// if ($row1['amount']==0){

							// display_error("This Check number is already voided in old check register : ".$row['old_check']);
							// }
						// }
			// }
			

	// display_error('=======================OLD CHECK REGISTER===========================');
			
			// $sql = "SELECT * FROM `check_old_register` as co
			// LEFT JOIN check_new_register as cn
			// ON co.old_check=cn.new_check";
			// //display_error($sql);
			// $res = db_query($sql,'error.');
								
			// while($row = db_fetch($res))
			// {
				// if ($row['new_check']==''){
					
					// display_error("This Check number is not in new check register: ".$row['old_check']);
				// }
			// }
			
			
			// display_error('======================NEW CHECK REGISTER============================');
			
		// $sql = "SELECT * FROM `check_new_register`";
		// $res = db_query($sql,'error.');
									
				// while($row = db_fetch($res))
				// {
			
					// $sql1 = "SELECT * FROM 0_bank_trans as bt
					// LEFT JOIN 0_check_trans as ct
					// on bt.id=ct.bank_trans_id
					// WHERE check_ref='".$row['new_check']."'";
					
							// $res1 = db_query($sql1,'error.');
									
							// while($row1 = db_fetch($res1))
							// {
								// if ($row1['amount']==0){

								// display_error("This Check number is already voided in old check register : ".$row['new_check']);
								// }
							// }
				// }
				
				
				
			// display_error('======================NEW CHECK REGISTER============================');


			// $sql = "SELECT * FROM `check_new_register` as cn
			// LEFT JOIN check_old_register as co 
			// ON cn.new_check=co.old_check";
			// //display_error($sql);
			// $res = db_query($sql,'error.');
								
			// while($row = db_fetch($res))
			// {
				// if ($row['old_check']==''){
					
					// display_error("This Check number is not in old check register: ".$row['new_check']);
				// }
			// }

end_form();
end_page();
?>