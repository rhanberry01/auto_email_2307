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
// include_once($path_to_root . "/gl/includes/db/rs_db.inc");

page('Create DM for ALL pending RETURNS', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------
function insert_unused($supp_code,$tran_date,$supp_ref, $ov_amount, $blum_id)
{
	global $Refs;
	$supplier_id = get_supplier_id_by_supp_ref($supp_code);
	
	if ($supplier_id == '')
		return false;
	
	$ref = $Refs->get_next(53);
	$trans_no = add_supp_trans(53, $supplier_id, $tran_date, '',$ref, $supp_ref, $ov_amount,  0, 0, "", 0, 0);

	add_gl_trans_supplier_temp(53, $trans_no, $tran_date, '2000', 0, 0, abs($ov_amount), $supplier_id,
				"The general ledger transaction for the control total could not be added",0, 'From Blumentritt RS');

	add_gl_trans_supplier_temp(53, $trans_no, $tran_date, '2350012', 0, 0, -abs($ov_amount), $supplier_id,
				"The general ledger transaction for the control total could not be added",0, 'From Blumentritt RS');


	add_comments(53, $trans_no, $tran_date,'Due to Blum');
	$Refs->save(53, $trans_no, $ref);
	
	$sql = "UPDATE blum_to_gg_aria_unsused_rs SET transferred=1 WHERE id=$blum_id";
	db_query($sql,'failed to update transferred');
	
	return true;
}

function create_debit_memo_for_blum_rs($rs_row)
{
	global $Refs;
		
	$supp_code = $rs_row[0];
	$supp_id = get_supplier_id_by_supp_ref($supp_code);
	
	if ($supp_id == '')
	{
		// display_error('Supplier does not exist for RS# '. $rs_id_str);
		return false;
	}

	$total = abs($rs_row['Total']);
	
	$tran_date = sql2date($rs_row['bo_processed_date']);
	// insert supp trans for debit memo 
	$reference = $Refs->get_next(ST_SUPPDEBITMEMO);
	$trans_no = add_supp_trans(ST_SUPPDEBITMEMO, $supp_id, $tran_date, '',
			$reference, 'RS#'.$rs_row['movement_no'].'-(from Blumentritt)', -$total,  0, 0, "", 0, 0);
	
	// GL entries
	$debit_account = '2000'; //accounts_payable
	$credit_account = '2350012'; // purchase returns and allowances
	
	add_gl_trans_supplier_temp(ST_SUPPDEBITMEMO, $trans_no, $tran_date, $debit_account, 0, 0, $total, $supp_id,
		"The general ledger transaction for the control total could not be added",0, 'From Blumentritt RS');
	add_gl_trans_supplier_temp(ST_SUPPDEBITMEMO, $trans_no, $tran_date, $credit_account, 0, 0, -$total, $supp_id,
		"The general ledger transaction for the control total could not be added",0, 'From Blumentritt RS');

	$Refs->save(ST_SUPPDEBITMEMO, $trans_no, $reference);
	//===========================================================================
				
	$sql = "UPDATE blum_returns.".TB_PREF."rms_header SET 
				acct_processed_date = '".date2sql(Today())."',
				acct_processed_by = ".$_SESSION['wa_current_user']->user.",
				trans_type = ".ST_SUPPDEBITMEMO.",
				trans_no = $trans_no,
				branch_transferred = 'gagalangin'
			WHERE movement_no = ".$rs_row['movement_no']."
			AND movement_type = 'R2SSA'";
	// display_error($sql);
	db_query($sql,'failed to assign trans_no to RS');
	return $trans_no;
}

if (isset($_POST['unused_aria']))
{

	begin_transaction();
	
	// import all unused but in ARIA
	$sql = "SELECT * FROM blum_to_gg_aria_unsused_rs WHERE transferred=0";
	$res = db_query($sql);
	
	$count = 0;
	while($row = db_fetch($res))
	{
		if (insert_unused($row['supplier_code'],sql2date($row['tran_date']),$row['supp_reference_formatted'], $row['ov_amount'], $row['id']))
			$count ++;
	}
	//===================================================
	commit_transaction();

	display_notification("Blum unused ($count) - Done!");
}

if (isset($_POST['get_rs'])) // for RS in branch
{
	begin_transaction();
	
	$sql= "SELECT a.supplier_code, movement_no , a.bo_processed_date,
				(SELECT SUM(qty*price) FROM blum_returns.0_rms_items x WHERE x.rs_id=a.rs_id) as Total
			FROM blum_returns.`0_rms_header` a, blum_returns.vendor b
			WHERE `trans_no` = '0' 
			AND a.supplier_code = b.vendorcode
			AND `bo_processed_date` >= '2014-01-01' 
			AND `movement_type` = 'R2SSA'
			AND (SELECT SUM(qty*price) FROM blum_returns.0_rms_items x WHERE x.rs_id=a.rs_id) > 0
			ORDER BY supplier_code, bo_processed_date";
	// display_error($sql);
	$res = db_query($sql);
	
	$count = 0;
	while($rs_row = db_fetch($res))
	{
		if (create_debit_memo_for_blum_rs($rs_row))
			$count++;
	}
	
	commit_transaction();
	display_notification("Blum RS ($count) - Done!");
}

start_form();
submit_center('unused_aria', 'Transfer BLUM unused RS (ARIA)');
submit_center('get_rs', 'Transfer BLUM RS');
end_form();

end_page();
?>
