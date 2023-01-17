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
page(_($help_context = "Fix Bank Accounts"), false, false, "", $js);

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
	
	// $sql="SELECT tran_date,
	// type,type_no,memo_,amount,account,
	// TRIM(LEADING 'Adjustment for Payment, Transaction#: ' FROM memo_) as trans_num 
	// FROM 0_gl_trans
	// where  
	// memo_ like '%Adjustment for Payment, Transaction#: %'
	// AND type ='0'
	// AND tran_date>='2015-01-01' and tran_date<='2015-12-31'
	// AND account='2501601'
	// ORDER BY trans_num
	// ";
	//display_error($sql);

	$result= db_query($sql, "failed to get bank_accounts id.");
	
	//2305-pdc payable
	//2501601-pdc payable-aub
	while($row = db_fetch($result))
	{
		$trans_num=$row['trans_num'];
		
		$sql2="SELECT * FROM 0_supp_trans AS st
		LEFT JOIN 0_bank_trans AS bt
		ON st.trans_no=bt.trans_no
		LEFT JOIN 0_cheque_details as cd
		ON cd.bank_trans_id=bt.id
		WHERE bt.trans_date>='2015-01-01' AND bt.trans_date<='2015-12-31'
		AND st.type='22'
		AND bt.type='22'
		AND MONTH(st.tran_date)!=MONTH(cd.chk_date)
		AND bt.amount!=0
		AND bt.trans_no='$trans_num'
		ORDER BY bt.trans_no";
		//display_error($sql2);

			$result2= db_query($sql2, "failed to get pdc payable chk_date.");
			//2305-pdc payable
			//2501601-pdc payable-aub
			while($row1 = db_fetch($result2))
			{
				$sql3 = "UPDATE ".TB_PREF."gl_trans SET tran_date='".$row1['chk_date']."'
				WHERE type_no='".$row['type_no']."' and type='0' and memo_ like '%Adjustment for Payment, Transaction#: %'";
				db_query($sql3,"Failed to update gl_trans.");
				//display_error($sql3);
				
			}

	}

	
	display_notification("Fixing PDC Payable are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
	
	
}

start_form();
start_row();
submit_center('Fix',_("Fix PDC Payable Dates"), true, '', false);
end_table();
end_form();
end_page();
?>