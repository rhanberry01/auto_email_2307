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
$page_security = 'SA_BANKACCOUNT';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");

page('CHANGE BANK GL ACCOUNT CODE', false, false,'', '');
//-----------------------------------------------------------------------------------

global $bank_account_types;

function update_bank_gl_and_transactions($bank_id, $old_code, $new_code)
{
	begin_transaction();
	//===== update 0_bank_accounts
	$sql1 = "UPDATE ".TB_PREF."bank_accounts SET
				account_code = ".db_escape($new_code)."
			WHERE id = $bank_id";
	db_query($sql1,'failed to update account code of bank');
	
	//===== update gl_trans
	$sql = "SELECT DISTINCT type
			FROM ".TB_PREF."bank_trans
			WHERE bank_act = $bank_id";
	$res_ = db_query($sql);
	
	while($row_ = db_fetch($res_))
	{
		// get all trans_no of bank per type
		$sql = "SELECT DISTINCT trans_no
			FROM ".TB_PREF."bank_trans
			WHERE bank_act = $bank_id
			AND type = ".$row_[0];
		$res = db_query($sql);
		
		$trans_nos = array();
		while($row = db_fetch($res))
			$trans_nos[] = $row[0];
			
		// update gl_trans using trans_nos and old_code
		$sql = "UPDATE ".TB_PREF."gl_trans SET 
					account = ".db_escape($new_code)."
				WHERE type = ".$row_[0]."
				AND account = ".db_escape($old_code)."
				AND type_no IN (".implode(',',$trans_nos).")";
		db_query($sql,'failed to update gl_trans');
	}
	
	$sql = "UPDATE ".TB_PREF."check_account SET	
				bank_ref = ".db_escape($new_code)."
			WHERE account_id = $bank_id";
	db_query($sql,'failed to update check account of bank');
			
	commit_transaction();
}

if (isset($_POST['update_banks']))
{
	$sql = "SELECT * FROM ".TB_PREF."bank_accounts";
	$res = db_query($sql);

	$flag = false;
	while ($row = db_fetch($res))
	{
		if ($row['account_code'] != $_POST['new_account_code'.$row['id']]) // GL account code changed
		{
			// display_error($row['account_code'] .' -> '. $_POST['new_account_code'.$row['id']]);
			update_bank_gl_and_transactions($row['id'], $row['account_code'],$_POST['new_account_code'.$row['id']]);
			$flag = true;
		}
	}
	
	if ($flag)
		display_notification('Banks and transactions updated');
}

start_form();

start_table($table_style2);

$sql = "SELECT * FROM ".TB_PREF."bank_accounts";
$res = db_query($sql);

$th = array('Bank Name','Bank Type','GL Account Code','New Account Code');
table_header($th);
$k = 0;
while ($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell($row['bank_name']);
	label_cell($bank_account_types[$row['account_type']]);
	label_cell($row['account_code'].' - '.get_gl_account_name($row['account_code']));
	// hidden('old_account_code'.$row['id'],$row['account_code']);
	gl_all_accounts_list_cells('', 'new_account_code'.$row['id'], $row['account_code']);
	end_row();
}

end_table(1);

submit_center('update_banks', 'UPDATE BANKS');
end_form();

end_page();
?>
