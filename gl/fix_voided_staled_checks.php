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
	
	global $db_connections,$Refs;
	
	set_time_limit(0);
	
	begin_transaction();	
	
		$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		
		
		
$sql="SELECT trans_no,chk_date, pay_to, chk_number, chk_amount, 'srs' as branch
FROM 0_cheque_details as cd
LEFT JOIN 0_bank_trans as bt
on cd.bank_trans_id=bt.id
WHERE cd.chk_number IN (
// 5219493,
// 5288405,
// 5294114,
// 5294153,
// 5463645,
// 5463646,
// 5463733,
// 5463734,
// 5224237,
// 5289962,
// 5293130,
// 5463232,
// 5463257,
// 5463303,
// 5463317,
// 5463318,
// 5139139,
// 5220045,
// 5220068,
// 5290358,
// 5293418,
// 5293422,
// 5462655,
// 5462656,
// 5216005,
// 5218843,
// 5221388,
// 5293998,
// 5461936,
// 5462095,
// 5462096,
// 5286970,
// 5295230,
// 5295473,
// 5463432,
// 5463433,
// 5463488,
// 5463489,
// 5291693,
// 5463172,
// 5215785,
// 5223175,
// 5223472,
// 5289561,
// 5294502,
// 5294558,
// 5460722,
// 5462463,
// 5462464,
// 5464440,
// 5465218,
// 5460905,
// 5461079,
// 5461080,
// 5464141,
// 5464142,
// 5214885,
// 5220729,
// 5222841,
// 5287892,
// 5288835,
// 5289207,
// 5292294,
// 5292346,
// 5292500,
// 5292766,
// 5293655,
// 5293731,
// 5293745,
// 5293753,
// 5294903,
// 5294949,
// 5294950,
// 5295011,
// 5295025,
// 5295027,
// 5295030,
// 5459920,
// 5459935,
// 5460084,
// 5460089,
// 5460090,
// 5461464,
// 5462887,
// 5462949,
// 5462950,
// 5464035,
// 5464985,
// 5217705,
// 5462195,
// 5462196,
// 5462243,
// 5462244,
// 5212009
)
AND bt.type=22
AND bt.bank_act=20
ORDER BY chk_number
";
		
		
		
		
		
		
		
	
		// $sql="SELECT * FROM cash_deposit.0_centralized_payment_aub
		// where reconciled='0000-00-00'
		// and br_code='$myBranchCode'
		// and chk_date>='2015-01-01' 
		// and chk_date<='2015-11-31'
		// and chk_number IN (
// 5465880,
// 5468471,
// 5567554,
// 5568436,
// 5563570,
// 5567304,
// 5566907,
// 5566920,
// 5463426,
// 5565283,
// 5565295,
// 5565303,
// 5569102,
// 5572444,
// 5565832,
// 5212106,
// 5212111,
// 5212115,
// 5465167,
// 5566659,
// 5566560,
// 5571741,
// 5566449,
// 5564024,
// 5466614,
// 5469178,
// 5469245,
// 5566245,
// 5566281,
// 5569397

		
		// )
		// ";
		
		//display_error($sql);
		
	$result= db_query($sql, "failed to get bank_accounts id.");
	
	//2305-pdc payable
	//2501601-pdc payable-aub
	while($row = db_fetch($result))
	{
		$date_ = sql2date($row['chk_date']);
		
		//$staled_check_month=add_months($date_,+6);
		//$staled_check_month_date=end_month($staled_check_month);
		$staled_check_month_date='12/31/2015';
		
		//display_error($staled_check_month_date);
		
		$ref   = $Refs->get_next(0);
		$memo_ = "Adjustment for Payment, Staled Check, Transaction#: ".$row['trans_no'];
		
		$trans_type = ST_JOURNAL;

		$trans_id = get_next_trans_no($trans_type);
		
			add_gl_trans($trans_type, $trans_id, $staled_check_month_date, 10102299, 0, 0, $memo_, $row['chk_amount'],null,3,$row['supplier_id']);
			add_gl_trans($trans_type, $trans_id, $staled_check_month_date, 2000014, 0, 0, $memo_, -$row['chk_amount'],null,3,$row['supplier_id']);

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $staled_check_month_date, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $staled_check_month_date);
		
		//display_error($trans_id);
	}

	
	display_notification("Fixing Staled Checks are Successful!");
	
	commit_transaction();
	//}
	// else {
	// display_notification("Selected Account Code is included in Bank Accounts");
	// }
	
	
}

start_form();
start_row();
submit_center('Fix',_("Fix Staled Checks"), true, '', false);
end_table();
end_form();
end_page();
?>