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
page(_($help_context = "Transfer DM to other Supplier"), false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	

function update_sup_id_from_supp_trans($supp_reference,$trans_no){
	$sql = "UPDATE ".TB_PREF."supp_trans SET cv_id='-2' 
	WHERE type='53'
	AND trans_no='$trans_no'
	AND cv_id=0
	AND supp_reference='$supp_reference'";
	db_query($sql,'failed to update supp_trans.');
	display_error($sql);
}

//----------------------------------------------------------------------------------------
if (isset($_POST['Fix'])) {
	
	global $db_connections;

	set_time_limit(0);
	
	$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	
	begin_transaction();
		
	$sql_transfer = "SELECT * FROM transfers.`0_pending_dms`
	where `status`=3
	and is_ok=0
	and br_code='$myBranchCode'";
	
	display_error($sql_transfer);
	$result_transfer=db_query($sql_transfer);

	while($transfer_row=mysql_fetch_array($result_transfer)) 
	{
		$pending_dm_id=$transfer_row['id'];
		$from_supp_name=$transfer_row['supp_name'];
		$to_supp_code=$transfer_row['to_supp_code'];
		$ref=$transfer_row['ref'];
		$supp_ref=$transfer_row['supp_ref'];
		

				$row1=db_fetch($result1);
				$supp_id_to=$row1['supplier_id'];
				$supp_code_to=$row1['supp_ref'];
				$supp_name_to=$row1['supp_name'];
				//display_error($vendor_account_to);

				$sql = "SELECT 'srs' as branch_code, sup.supp_name, spt.*, s.supp_ref
				FROM 0_supp_trans as spt
				LEFT JOIN 0_suppliers as sup
				ON spt.supplier_id=sup.supplier_id
				LEFT JOIN 0_suppliers as s
				ON spt.supplier_id=s.supplier_id
				where type=53
				and cv_id=0
				and ov_amount!=0
				and tran_date>='2015-01-01' and tran_date<='2017-12-31'
				and supp_reference='$supp_ref'
				";
				display_error($sql);
				$result=db_query($sql);

				while($row=mysql_fetch_array($result)) 
				{
						update_sup_id_from_supp_trans($supp_ref,$row['trans_no']);

				}

				$sql_is_ok="UPDATE transfers.`0_pending_dms` SET is_ok=1
				WHERE id='$pending_dm_id'";
				display_error($sql_is_ok);
				db_query($sql_is_ok,'failed to update gl_trans temp.');

	}
	
	commit_transaction();
	
	display_notification('SUCCESS!!');
}


start_form();
start_table();
start_row();
// supplier_list_cells(_("Transfer DM From Supplier:"), 'account_from', null, true);
// supplier_list_cells(_("to Supplier:"), 'account_to', null, true);
end_row();
end_table();
br();
br();
start_row();
submit_center('Fix', 'Transfer');
end_table();
end_form();
end_page();
?>