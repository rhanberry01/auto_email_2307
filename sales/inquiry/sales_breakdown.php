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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Sales Breakdown"), false, false, "", $js);

//------------------------------------------------------------------------------------------------
global $Ajax;

if (isset($_GET['remittance_id']))
	$_POST['remittance_id'] = $_GET['remittance_id'];
	
$remittance_id = $_POST['remittance_id'];

$sql = "SELECT * FROM ".TB_PREF."remittance WHERE remittance_id = $remittance_id";
$res = db_query($sql);
$r_row = db_fetch($res);

$ov_sh_ = get_remittance_short_over_per_type($remittance_id);

start_table("$table_style2");
label_row("Date :", sql2date($r_row['remittance_date']), "class='tableheader2'");
label_row("Cashier Name:", get_ms_username_by_id($r_row['cashier_id']), "class='tableheader2'");

foreach($ov_sh_ as $desc => $amount_count)
{
	label_row($amount_count[0] > 0 ? 'Over  ' . "($desc) :" : 'Short  ' . "($desc) :", number_format2(abs($amount_count[0]),2), "class='tableheader2'");
}
// display_heading("Date : ".sql2date($r_row['remittance_date']));
// display_heading("Cashier Name : ".get_username_by_id($r_row['cashier_id']));
// display_heading($ov_sh > 0 ? 'Over : ' : 'Short : ' . number_format2(abs($ov_sh),2));
end_table(1);

if ($r_row['trans_checked'])
{
	display_heading('All Credit Card and Debit Card transactions has been checked');
}
br();
start_form();
echo '<center>'.yesno_list('filter', null, "Show Only Debit and Credit Card", "Show All", true).'</center>';
hidden('remittance_id', $_POST['remittance_id']);
br();

if (list_updated('filter'))
{
	$Ajax->activate('payments');
}

// ==================================================================================

$ptype_id = find_submit('payment_type');

if ($ptype_id != -1)
{
	$sql = "UPDATE ".TB_PREF."finishedpayments SET 
				Description = '".$_POST['payment_type'.$ptype_id]."'
				WHERE id = $ptype_id";
	db_query($sql,'failed to update payment type');
	
	$Ajax->activate('payments');
}
// ==================================================================================
$correct_id = find_submit('correct');
if ($correct_id != -1) // transaction was correct!
{
	$acquiring_bank_id = $_POST['acquiring_bank_id'.$correct_id];
	
	if ($acquiring_bank_id == 0)
	{
		display_error('Acquiring Bank must not be empty!');
		$correct_id = -1;
		return false;
	}
	
	$sql = "UPDATE ".TB_PREF."finishedpayments SET 
					actual_amount = Amount,
					acquiring_bank_id = ".($acquiring_bank_id).",
					checked_by = ".$_SESSION["wa_current_user"]->user."
				WHERE id = $correct_id";
	db_query($sql,'failed to update finished payments checked');
	
	confirm_trans_remittance($_POST['remittance_id']);
	
	$Ajax->activate('payments');
}

// ==================================================================================
$correction_id = find_submit('apply_correction');
if ($correction_id != -1)
{
	$act_amount = input_num('act_amount'.$correction_id);
	$remarks = $_POST['remark'.$correction_id];
	$acquiring_bank_id = $_POST['acquiring_bank_id'.$correction_id];
	
	if ($acquiring_bank_id == 0)
	{
		display_error('Acquiring Bank must not be empty!');
		$correction_id = -1;
		return false;
	}
	if ($remarks == '')
	{
		display_error('Remarks field should not be empty');
		$correction_id = -1;
		return false;
	}
	$sql = "UPDATE ".TB_PREF."finishedpayments SET 
					actual_amount = ".($act_amount+0).",
					acquiring_bank_id = ".($acquiring_bank_id).",
					aria_remarks = ".db_escape($remarks).",
					checked_by = ".$_SESSION["wa_current_user"]->user;
	
	$sql .= " WHERE id = $correction_id";
	db_query($sql,'failed to update finished payments checked');
	
	confirm_trans_remittance($_POST['remittance_id']);
	
	$Ajax->activate('payments');
}
// ==================================================================================

div_start('payments');

$sql = "SELECT * FROM ".TB_PREF."remittance WHERE remittance_id = ".$remittance_id;
$res = db_query($sql);
$r_row = db_fetch($res);

$cashier_id = $r_row['cashier_id'];
$remittance_date = sql2date($r_row['remittance_date']);
		
$sql = "SELECT * FROM FinishedPayments 
					WHERE LogDate = '".date2sql($remittance_date)."'
					AND UserID = $cashier_id
					AND Voided = 0";
if ($_POST['filter'] == 1)
	$sql .= " AND Description IN ('Debit Card','Credit Card')";

$sql .= " ORDER BY TerminalNo, TransactionNo";

$res = ms_db_query($sql);


start_table("$table_style2");

$th = array('Terminal No.', ' Transaction No.', 'Payment Type',  'Account No', 'Card Used', 'Approval No', 'POS Amount');

if ($_POST['filter'] == 1)
{
	$th[] = 'Acquiring Bank';
	$th[] = 'Correct';
	$th[] = 'Swiper Amount';
	$th[] = 'Remarks';
	$th[] = '';
}
else
	$th[] = 'Swiper Amount';


$k = 0;
$terminalno='';
while($row = mssql_fetch_array($res))
{
	if ($terminalno != $row['TerminalNo'])
	{
		table_header($th);
		$terminalno = $row['TerminalNo'];
	}
	alt_table_row_color($k);
	label_cell($row['TerminalNo']);
	label_cell($row['TransactionNo']);
	
	
	if ($_POST['filter'] == 1)
	{
		if (($row['Description'] == 'Credit Card' OR $row['Description'] == 'Debit Card'))
		{
		
			if ($row['checked_by'] == 0)
			{
				payment_types_ms_cells('', 'payment_type'.$row['id'], $row['Description'], false, true);
				label_cell($row['AccountNo']);
				label_cell($row['Remarks']);
				label_cell($row['ApprovalNo']);
				amount_cell($row['Amount'],true);
	
				acquiring_bank_list_cells('', 'acquiring_bank_id'.$row['id']);
				label_cell(submit('xcorrect'.$row['id'], 'Correct', false, false, 'default'));
				amount_cells('','act_amount'.$row['id'],null,'nowrap');
				text_cells('', 'remark'.$row['id']);
				label_cell(submit('xapply_correction'.$row['id'], 'Update', false, false, 'default'));
			}
			else
			{
				label_cell($row['Description']);
				label_cell($row['AccountNo']);
				label_cell($row['Remarks']);
				label_cell($row['ApprovalNo']);
				amount_cell($row['Amount'],true);
				
				label_cell(get_acquiring_bank_col($row['acquiring_bank_id'], 'acquiring_bank'));
				if ($row['actual_amount'] == $row['Amount'])
					label_cell('<img src="'.$path_to_root.'/themes/modern/images/ok.gif" height="12">','align=center');
				else
				{
					label_cell('<img src="'.$path_to_root.'/themes/modern/images/delete.gif" height="12">','align=center');
					amount_cell($row['actual_amount'],true);
					label_cell($row['aria_remarks'], 'colspan=2');

				}
					
				
			}
		}
		
	}
	else if ($_POST['filter'] != 1 AND $row['checked_by'] != 0)
	{
		label_cell($row['Description']);
		label_cell($row['AccountNo']);
		label_cell($row['Remarks']);
		label_cell($row['ApprovalNo']);
		amount_cell($row['Amount'],true);
		
		amount_cell($row['actual_amount'],true);
	}
	else
	{
		label_cell($row['Description']);
		label_cell($row['AccountNo']);
		label_cell($row['Remarks']);
		label_cell($row['ApprovalNo']);
		amount_cell($row['Amount'],true);
	}
	end_row();
}


end_table();
div_end();

end_form();
end_page();

?>
