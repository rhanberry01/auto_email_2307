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
page(_($help_context = "Fix Other Income Date Deposit"), false, false, "", $js);

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


//----------------------------------------------------------------------------------------
if (isset($_POST['Add'])){
	
	ini_set('mssql.connect_timeout',0);
	ini_set('mssql.timeout',0);
	set_time_limit(0);
	
		
	$myDB=$db_connections[$_SESSION["wa_current_user"]->company]["db_133"];

	$myServer = "192.168.0.133";
	$myUser = "markuser";
	$myPass = "tseug";
	//$myDB = "SRSMNOVA"; 
	//display_error($myDB."asasasasa");


	$dbhandle = mssql_connect($myServer, $myUser, $myPass)
	or die("Couldn't connect to SQL Server on $myServer"); 

	$selected = mssql_select_db($myDB, $dbhandle)
	or die("Couldn't open database $myDB"); 
	
	$sql= "SELECT * FROM 0_cons_sales_details as csd
	LEFT JOIN 0_cons_sales_header as csh
	on csd.cons_det_id=csh.cons_sales_id
	where csd.cos=0
	and start_date>='2017-03-01'
	and csd.sales!=0
	";

	$res=db_query($sql);


	
while($row = db_fetch($res))
{
		$sql1 = "SELECT top 1 [AverageUnitCost]
		FROM [FinishedSales]
		where LogDate>='".$row['start_date']."' and LogDate<='".$row['end_date']."'
		and ProductID=".$row['prod_id']."";
			$res1=mssql_query($sql1);
			while($row1 = mssql_fetch_array($res1))
			{
				$sql2_1 = "UPDATE ".TB_PREF."cons_sales_details SET cos=".$row1['AverageUnitCost']."
				WHERE prod_id=".$row['prod_id']." and cons_det_id=".$row['cons_det_id']."";
				if($row['prod_id']!=''){					
					db_query($sql2_1,"Failed to update gl_trans.");
				}
				
				$sql3 = "UPDATE ".TB_PREF."cons_sales_header SET
				email_sent=0
				where cons_sales_id=".$row['cons_det_id']."";
				if($row['prod_id']!=''){					
					$result = db_query($sql3);
				}
				
				
				
				display_error($sql3);
				display_error($sql2_1);
			}
}
	
display_notification("Fixing Consignment zero cos is Successful!");
}
start_form();
start_table();
start_row();
//ref_cells('Transaction #:', 'trans_no');
//date_cells('Change date deposited to :', 'date');
end_row();
end_table();
br();
start_row();
submit_center('Add',_("Fix Consignment Zero Cos."), true, '', 'default');
end_table();
end_form();
end_page();
?>