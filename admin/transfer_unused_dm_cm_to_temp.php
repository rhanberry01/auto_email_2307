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

page('Fix GL Date of DM/CM/EWT', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------

function update_dm_cm_ewt_gl_trans($type, $trans_no, $tran_date, $account='')
{
	$sql = "UPDATE ".TB_PREF."gl_trans
			SET tran_date = '$tran_date'
			WHERE type = $type
			AND type_no = $trans_no";
	if ($account != '')
		$sql .= " AND account = '$account'";
			
	db_query($sql,'failed to update tran_date of DM / CM / EWT');
}

function add_ewt_to_ap_gl_then_delete_ewt($type, $trans_no, $amount, $counter, $account=2000)
{
	$sql = "UPDATE ".TB_PREF."gl_trans
			SET amount = amount + $amount
			WHERE type = $type
			AND type_no = $trans_no";
	if ($account != '')
		$sql .= " AND account = '$account'";
	db_query($sql,'failed to update amount of EWT');
	
	$sql = "DELETE FROM ".TB_PREF."gl_trans
			WHERE counter = $counter";
	db_query($sql,'failed to delete ewt');
}


function update_cv_ewt($cv_id, $amt)
{
	$sql = "UPDATE ".TB_PREF."cv_header
			SET ewt = ewt+$amt
			WHERE id = $cv_id";
	db_query('failed to update ewt of CV');
}

//-----------------------------------------------------------------------------------
$company_pref = get_company_prefs();

if (isset($_POST['move_it']))
{
	$sql = "INSERT INTO ".TB_PREF."gl_trans_temp(type, type_no, tran_date, account, memo_, amount, person_type_id, person_id)
			SELECT b.type, b.type_no, b.tran_date, b.account, b.memo_, b.amount, b.person_type_id, b.person_id
			FROM 0_supp_trans a, 0_gl_trans b
			WHERE a.type IN (52,53)
			AND ov_amount != 0 
			AND cv_id = 0
			AND a.type = b.type
			AND a.trans_no = b.type_no";
	db_query($sql, 'failed to insert gl_trans to temp_gl_trans');

	$sql = "DELETE FROM 0_gl_trans
			WHERE type = 53 
			AND type_no IN(SELECT trans_no FROM 0_supp_trans 
			WHERE type = 53
			AND ov_amount != 0 
			AND cv_id = 0)";
	db_query($sql, 'failed to delete gl_trans 53');

	$sql = "DELETE FROM 0_gl_trans
			WHERE type = 52 
			AND type_no IN(SELECT trans_no FROM 0_supp_trans 
			WHERE type = 52
			AND ov_amount != 0 
			AND cv_id = 0)";
	db_query($sql, 'failed to delete gl_trans 52');
	
	display_notification('TEMP DM/CM moved.');
}


if (isset($_POST['edit_tran_date']))
{
	$sql = "SELECT a.type, a.trans_no, b.cv_date
			FROM 0_supp_trans a, 0_cv_header b
			WHERE a.type IN (52,53)
			AND a.cv_id  = b.id";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		update_dm_cm_ewt_gl_trans($row[0], $row[1], $row[2]);
	}
	
	display_notification('fixed DM/CM tran_dates.');
}

if (isset($_POST['fix_ewt_paid']))
{
	// $sql = "SELECT a.type, a.trans_no,  c.trans_date
			// FROM 0_supp_trans a, 0_cv_header b, 0_bank_trans c
			// WHERE a.type = 20
			// AND a.cv_id  = b.id
			// AND b.bank_trans_id = c.id
			// ";
	$sql = "SELECT a.type, a.trans_no, d.tran_date
			FROM 0_supp_trans a, 0_cv_header b, 0_bank_trans c, 0_supp_trans d
			WHERE a.type = 20
			AND a.cv_id  = b.id
			AND b.bank_trans_id = c.id
			AND c.type = d.type
			AND c.trans_no = d.trans_no
			ORDER BY d.tran_date DESC";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		update_dm_cm_ewt_gl_trans($row[0], $row[1], $row[2],$company_pref["default_purchase_ewt_act"]); // ewt account
	}
	
	display_notification('fixed EWT GL date (paid).');
}

if (isset($_POST['fix_ewt_apv_no_cv']))
{
	// GL entry of AP will be new AP = AP + EWT
	
	$sql = "SELECT b.counter, b.type, b.type_no, account, amount 
			FROM 0_supp_trans a, 0_gl_trans b
			WHERE a.cv_id = 0
			AND a.type = 20
			AND a.ov_amount != 0
			AND a.ewt != 0
			AND a.type = b.type
			AND a.trans_no = b.type_no
			AND b.amount < 0
			AND (b.account = '2330'
			OR b.account = '23303158')";
	$res = db_query($sql);
	while($row = db_fetch($res))
	{
		add_ewt_to_ap_gl_then_delete_ewt($row[1], $row[2], $row[4], $row[0]);
	}
	display_notification('fixed EWT GL date (no CV).');
}

if (isset($_POST['fix_ewt_unpaid']))
{
	// GL entry of AP will be new AP = AP + EWT
	// then copy TOTAL ewt of ALL APV to ewt of CV
	
	$sql = "SELECT b.type, b.type_no, b.amount, b.counter, c.cv_no, c.id
			FROM 0_supp_trans a, 0_gl_trans b, 0_cv_header c
			WHERE a.cv_id != 0
			AND a.type = 20
			AND a.ov_amount != 0
			AND a.ewt != 0
			AND a.type = b.type
			AND a.trans_no = b.type_no
			AND b.amount < 0
			AND (b.account = '2330'
			OR b.account = '23303158')
			AND a.cv_id = c.id
			AND c.bank_trans_id = 0
			ORDER BY c.cv_no";
	$res = db_query($sql);
	while($row = db_fetch($res))
	{
		add_ewt_to_ap_gl_then_delete_ewt($row[0], $row[1], $row[2], $row[3]);
		update_cv_ewt($row['id'], $row['amount']);
	}
	display_notification('fixed EWT GL date (with cv - unpaid).');
}

start_form();
submit_center('move_it', 'Move Unused DM/CM');
br(2);
submit_center('edit_tran_date', 'Copy CV Date to DM/CM');
br();
echo '<hr>';
br();
submit_center('fix_ewt_paid', 'Fix EWT Date (APV with PAID CV)');
br(2);
submit_center('fix_ewt_unpaid', 'Fix EWT Date (APV with UNPAID CV)');
br(2);
submit_center('fix_ewt_apv_no_cv', 'Fix EWT Date (APV without CV)');
end_form();

end_page();
?>
