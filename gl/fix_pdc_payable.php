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
	
$sql="SELECT '$myBranchCode' as br_code, c.type, c.trans_no,  c.supplier_id, 
c.tran_date, b.trans_date, cd.chk_date, c.cv_id,c.non_trade,
a.cv_date,a.cv_no,b.id as b_id, cd.chk_number, ROUND(cd.chk_amount,2) as  chk_amount,
cd.deposit_date,'0' as reconciled
FROM 0_supp_trans as c
LEFT JOIN 0_cv_header as a 
ON c.cv_id=a.id 
LEFT JOIN 0_bank_trans as b 
ON a.bank_trans_id=b.id 
LEFT JOIN 0_cheque_details as cd 
ON b.id=cd.bank_trans_id
WHERE a.amount!=0 
AND c.ov_amount!=0 
AND c.type='22' 
AND b.type='22'
AND b.amount!=0 
AND b.bank_act=20
AND !ISNULL(cd.chk_number) 
AND cd.chk_number IN (
5465230,
5465231,
5465229,
5465226,
5465224,
5465225,
5465228,
5465227,
5462222,
5295837,
5464700,
5465321,
5462223,
5465207,
5212137,
5564089,
5569017,
5568328,
5567485,
5566064,
5462226,
5294267,
5572645,
5462227,
5571013,
5727068,
5728131,
5462228,
5727842,
5462229,
5726688,
5892488,
5462230,
5729369,
5731948,
5892619,
5896550,
5896551,
5462231,
5895188,
5462232,
5900529,
5462233,
5899996,
5895986,
5900363,
5900496,
5900528
)
order by b.trans_date
";
		
$result= db_query($sql, "failed to get bank_accounts id.");
	
	//2305-pdc payable
	//2501601-pdc payable-aub
	while($row = db_fetch($result))
	{
		
		// $sql1 = "UPDATE ".TB_PREF."gl_trans SET account='10102299'
		// WHERE type_no='".$row['trans_no']."' and type='22' and account='2501601'";
		// db_query($sql1,"Failed to update gl_trans.");
		
		//$date_ = sql2date($row['chk_date']);
		$date_='01/31/2016';
		$ref   = $Refs->get_next(0);
		$memo_ = "Adjustment for Payment, Outstanding Checks Beginning, Transaction#: ".$row['trans_no'];
		
		$trans_type = ST_JOURNAL;

		$trans_id = get_next_trans_no($trans_type);
		
			
			add_gl_trans($trans_type, $trans_id, $date_, 2000, 0, 0, $memo_, $row['chk_amount'],null,3,$row['supplier_id']);
			add_gl_trans($trans_type, $trans_id, $date_, 10102299, 0, 0, $memo_, -$row['chk_amount'],null,3,$row['supplier_id']);
			

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $date_, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $date_);
	}

	
	display_notification("Fixing Bank Accounts are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
}

start_form();
start_row();
submit_center('Fix',_("Fix PDC Payable"), true, '', false);
end_table();
end_form();
end_page();
?>