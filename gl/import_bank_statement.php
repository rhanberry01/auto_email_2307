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
$page_security = 'SA_JOURNALENTRY';
error_reporting(E_ALL ^ E_NOTICE);
$path_to_root = "..";

include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/ui/gl_journal_ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/gl/includes/excel_reader2.php");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

$_SESSION['page_title'] = _($help_context = "Import Bank Statement");

page($_SESSION['page_title'], false, false,'', $js);
//--------------------------------------------------------------------------------------------------
global $db_connections;

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_JOURNAL;

   	display_notification_centered( _("Journal entry has been entered") . " #$trans_no");

    display_note(get_gl_view_str($trans_type, $trans_no, _("&View this Journal Entry")));

	reset_focus();
	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &New Journal Entry"), "NewJournal=Yes");

	display_footer_exit();
} elseif (isset($_GET['UpdatedID'])) 
{
	$trans_no = $_GET['UpdatedID'];
	$trans_type = ST_JOURNAL;

   	display_notification_centered( _("Journal entry has been updated") . " #$trans_no");

    display_note(get_gl_view_str($trans_type, $trans_no, _("&View this Journal Entry")));

   	hyperlink_no_params($path_to_root."/gl/inquiry/journal_inquiry.php", _("Return to Journal &Inquiry"));

	display_footer_exit();
}

//--------------------------------------------------------------------------------------------------

if (isset($_POST['Process']))
{
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
}

//-----------------------------------------------------------------------------------------------

function insert_selected_to_gl($p_ref_id,$bank_gl_code) {
	
		$sqldc="select * from ".TB_PREF."acquiring_deductions where p_ref_id ='$p_ref_id'";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc)){
		$card_tender_type=$dc_row['p_tender_type'];
		$p_charge_back=$dc_row["p_charge_back"];
		$date_paid=$dc_row["date_paid"];
		$p_bank_card=$dc_row["p_bank_card"];
		
		if ($dc_row["p_charge_back"]!=1) {
		$p_receivable_amount+=$dc_row["p_receivable_amount"];
		$p_mfeeamount+=$dc_row["p_mfeeamount"];
		}
		$p_wtaxamount+=$dc_row["p_wtaxamount"];
		$p_net_total+=$dc_row["p_net_total"];
		$p_deposited_amount+=$dc_row["p_deposited_amount"];
	
		
		if ($dc_row["p_charge_back"]==1) {
		$a_net_total+=abs($dc_row["p_net_total"]);
		}
		
		}
		
		if ($p_receivable_amount < $p_deposited_amount) {
		$p_over_amount=$p_deposited_amount-$p_receivable_amount;
		}
		
		$mysql = "select * from  ".TB_PREF."acquiring_banks where gl_bank_account='$bank_gl_code'";
		$res1 = db_query($mysql);
		//display_error($mysql);
		while($myrow = db_fetch($res1))
		{
		$gl_bank_account=$myrow['gl_bank_account'];
		$gl_bank_debit_account=$myrow['gl_bank_debit_account'];
		$gl_mfee_account=$myrow['gl_mfee_account'];
		$gl_wtax_account=$myrow['gl_wtax_account'];
		$cc_fee=$myrow['cc_merchant_fee_percent'];
		$dc_fee=$myrow['dc_merchant_fee_percent'];
		$cc_wt=$myrow['cc_withholding_tax_percent'];
		//$dc_wt=$myrow['dc_withholding_tax_percent'];
		$a_b=$myrow['acquiring_bank'];
		//$iv=$myrow['input_vat'];
		//$ov_fee=$myrow['output_vat'];
		$a_b=$selected_bank;
		}
		
		
		
if (($p_bank_card=='METROBANK') or ($p_bank_card=='metrobank')) {
		$gl_over_deposit_acount='2000101';
		$sqldc="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'METROBANK%' or acquiring_bank='METROBANK' or acquiring_bank='metrobank')";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc))
		{
		$gl_bank_account=$dc_row["gl_bank_account"];
		$gl_bank_debit_account=$dc_row["gl_bank_debit_account"];
		}
}

if (($p_bank_card=='BPI') or ($p_bank_card=='bpi')) {
		$gl_over_deposit_acount='2000100';
		$sqldc="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'BPI%' or acquiring_bank='BPI' or acquiring_bank='bpi')";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc))
		{
		$gl_bank_account=$dc_row["gl_bank_account"];
		$gl_bank_debit_account=$dc_row["gl_bank_debit_account"];
		}
}		

if (($p_bank_card=='PNB') or ($p_bank_card=='pnb')) {
		$gl_over_deposit_acount='2000102';
		$sqldc="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'PNB%' or acquiring_bank='PNB' or acquiring_bank='pnb')";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc))
		{
		$gl_bank_account=$dc_row["gl_bank_account"];
		$gl_bank_debit_account=$dc_row["gl_bank_debit_account"];
		}
}		


	//payment
	add_gl_trans(ST_CREDITDEBITDEPOSIT, $p_ref_id, sql2date($date_paid), $gl_bank_account, 0, 0, $memo, $p_net_total, null, 0);
	
	// //merchant fee
	// add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_mfee_account, 0, 0, $memo, $p_mfeeamount, null, 0);
	
	// //withholding tax
	// if($card_tender_type=='013')
	// {
	// add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_wtax_account, 0, 0, $memo, $p_wtaxamount, null, 0);
	// }

	// //over payment
	// if ($p_over_amount>0) {
	// add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_over_deposit_acount, 0, 0, $memo, -$p_over_amount, null, 0);
	// }
	
	// //receivable
	// add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_bank_debit_account, 0, 0, $memo, -$p_receivable_amount, null, 0);

	// //chargeback
	// if ($a_net_total!='') {
	// add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_over_deposit_acount, 0, 0, $memo, $a_net_total, null, 0);
	// }
}

function update_bank_deposit_cheque_details($cleared_id,$date_paid,$remarks)	
{		
	 $sql = "UPDATE ".TB_PREF."bank_deposit_cheque_details 
	 SET deposited='1',
	 deposit_date='$date_paid',
	 remark='$remarks'
	 WHERE bank_trans_id = '$cleared_id'";

db_query($sql);
//display_error($sql);
}

function update_bank_trans($bank_account, $date_paid, $cleared_id)
{
$type=ST_BANKDEPOSIT;
	$sql = "UPDATE ".TB_PREF."bank_trans SET bank_act = '$bank_account', trans_date='$date_paid'
				WHERE trans_no = '$cleared_id' AND type='$type'";
	db_query($sql);
}


function update_cash_dep_header($cleared_id,$date_paid,$date_cleared,$bank_id,$remarks,$payto,$trans_type)	
{		
	 $sql = "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
	 SET cd_cleared = '1',
	 cd_date_deposited='$date_paid',
	 cd_date_cleared='".date2sql(Today())."'
	WHERE cd_id = '$cleared_id'
	and cd_trans_type='$trans_type'
	";
db_query($sql);
//display_error($sql);
}

function update_bank_statement($db_,$bank_statement_id,$trans_type,$dep_id)	
{		
	 $sql = "UPDATE cash_deposit.".TB_PREF."$db_ 
	 SET type = '$trans_type',
	 reference='$dep_id',
	 cleared='1'
	WHERE id = '$bank_statement_id'";
db_query($sql);
//display_error($sql);
}

function update_other_income_payment_header($cleared_id,$date_paid,$date_cleared,$bank_id,$remarks,$payto)	
{			//display_error($connect_to);
	 $sql = "UPDATE ".TB_PREF."other_income_payment_header 
	 SET bd_cleared = '1',
	 bd_payment_to_bank='$bank_id',
	 bd_date_deposited='$date_paid',
	 bd_date_cleared='".date2sql(Today())."'
	WHERE bd_trans_no = '$cleared_id'";
db_query($sql);
//display_error($sql);
}

function check_exist_statement($db_,$date_deposited,$credit_amount,$balance)
{
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."$db_ 
	WHERE date_deposited='".date2sql($date_deposited)."' AND  amount_deposited='$credit_amount' AND balance='$balance'";
	//display_error($sql);
	$res = db_query($sql);
	$count_row=db_num_rows($res);
	return $count_row;
}


function clear_aria_cr_dr_trans($bank_statement_id,$date_paid,$credit_amount,$bank_gl_code)
{
	global $db_connections;
	$trans_type=ST_CREDITDEBITDEPOSIT;
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."cash_dep_header WHERE cd_trans_type='$trans_type' and cd_gross_amount='$credit_amount'  and cd_bank_account_code='$bank_gl_code' and cd_cleared='0' and cd_sales_date>='2015-09-02' order by cd_trans_date limit 1";
	$res = db_query($sql);
	
	$count=db_num_rows($res);
	
	if ($count>0){
		while($row = db_fetch($res))
		{
			switch_connection_to_branch($row['cd_br_code']);
				
				$cd_id=$row['cd_id'];
			
				$bank_account=$row['cd_bank_deposited'];
			
								$date_cleared=Today();

								$sql2="select * from cash_deposit.".TB_PREF."cash_dep_header where cd_trans_type='$trans_type' and cd_id='$cd_id' and cd_cleared='0'";
								//display_error($sql);
								$result=db_query($sql2);

								while($row2 = db_fetch($result))
								{
								$id=$row2['cd_id'];
								$transno=$row2['cd_aria_trans_no'];
								$amount=$row2['cd_gross_amount'];
								$br_code=$row2['cd_br_code'];
								}
								
								if ($transno!='') {
									insert_selected_to_gl($transno,$bank_gl_code);
								}
								else{
								display_error($row['cd_br_code'].$cd_id."failed to clear.");
								}

				
			update_cash_dep_header($row['cd_id'],$date_paid,$date_cleared,$bank_id,$remarks,$payto,$trans_type);
			
			if ($bank_gl_code=='1020011'){
			$db_='bank_statement_aub';
			}
			else if ($bank_gl_code=='1020021'){
			$db_='bank_statement_metro';
			}
			else if ($bank_gl_code=='1010040'){
			$db_='bank_statement_bpi';
			}

			update_bank_statement($db_,$bank_statement_id,$row['cd_trans_type'],$row['cd_id']);
		}
	}

	return $count;
		//display_error($sql);
}


function clear_aria_cash_dep_trans($bank_statement_id,$date_paid,$credit_amount,$bank_gl_code)
{
	global $db_connections;
	$trans_type=ST_CASHDEPOSIT;
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."cash_dep_header WHERE cd_trans_type='$trans_type' and cd_gross_amount='$credit_amount'  and cd_bank_account_code='$bank_gl_code' and cd_cleared='0' and cd_sales_date>='2015-09-02' order by cd_trans_date limit 1";
	$res = db_query($sql);
	
	$count=db_num_rows($res);
	
		//display_error($sql);
	if ($count>0){
		while($row = db_fetch($res))
		{
			switch_connection_to_branch($row['cd_br_code']);
				
				$cd_id=$row['cd_id'];
			
				$bank_account=$row['cd_bank_deposited'];
			
								$date_cleared=Today();

								$sql2="select * from cash_deposit.".TB_PREF."cash_dep_header where cd_trans_type='$trans_type' and cd_id='$cd_id' and cd_cleared='0'";
								//display_error($sql);
								$result=db_query($sql2);

								while($row2 = db_fetch($result))
								{
								$id=$row2['cd_id'];
								$transno=$row2['cd_aria_trans_no'];
								$amount=$row2['cd_gross_amount'];
								$br_code=$row2['cd_br_code'];
								}

								if ($transno!='') {
								add_gl_trans(ST_CASHDEPOSIT, $transno, sql2date($date_paid), 272727, 0, 0, $remarks,-$amount, null, $person_type_id, $person_id);


								$sql_cib="select id,account_code from ".TB_PREF."bank_accounts where account_code='$bank_gl_code'";
								//display_error($sql_cib);
								$result_cib=db_query($sql_cib);

								while ($accountrow = db_fetch($result_cib))
								{
								$cash_in_bank=$accountrow['account_code'];
								}
											
								//display_error($cash_in_bank);			
								add_gl_trans(ST_CASHDEPOSIT, $transno, sql2date($date_paid), $cash_in_bank, 0, 0, $remarks,$amount, null, $person_type_id, $person_id);

								$desc='Cleared';
								add_audit_trail(ST_CASHDEPOSIT, $transno, $date_paid,$desc);
								}
								
								else{
								display_error($row['cd_br_code'].$cd_id."failed to clear.");
								}
								
								

			update_cash_dep_header($row['cd_id'],$date_paid,$date_cleared,$bank_id,$remarks,$payto,$trans_type);
			
			if ($bank_gl_code=='1020011'){
			$db_='bank_statement_aub';
			}
			else if ($bank_gl_code=='1020021'){
			$db_='bank_statement_metro';
			}
			else if ($bank_gl_code=='1010040'){
			$db_='bank_statement_bpi';
			}

			update_bank_statement($db_,$bank_statement_id,$row['cd_trans_type'],$row['cd_id']);
		}
	}
	return $count;
}


function clear_aria_other_income_trans($bank_statement_id,$date_paid,$credit_amount,$bank_gl_code)
{
	global $db_connections;
	$trans_type=ST_BANKDEPOSIT;
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."cash_dep_header WHERE cd_trans_type='$trans_type' and cd_gross_amount='$credit_amount'  and cd_bank_account_code='$bank_gl_code' and cd_cleared='0' and cd_sales_date>='2015-09-02' order by cd_trans_date limit 1";
	$res = db_query($sql);
	
	$count=db_num_rows($res);
	//display_error($sql);
	if ($count>0){
		while($row = db_fetch($res))
		{
			switch_connection_to_branch($row['cd_br_code']);
			
				$trans_nos = explode(",", $row['cd_aria_trans_no']);
			
				$bank_account=$row['cd_bank_deposited'];
			
				foreach ($trans_nos as $trans_num)
				{
					$sql2="select * from ".TB_PREF."other_income_payment_header where bd_trans_no='$trans_num' and bd_cleared='0' order by bd_trans_date asc";
					//display_error($sql);
					$result=db_query($sql2);

					while($row2 = db_fetch($result))
					{
					$id=$row2['bd_id'];
					$transno=$row2['bd_trans_no'];
					$amount=$row2['bd_amount'];
					$payment_type=$row2['bd_payment_type'];
					$debtor_no=$row2['bd_payee_id'];
					}
					
					if ($transno!='') {
					update_bank_deposit_cheque_details($transno,$date_paid,$remarks);	
					update_bank_trans($bank_account, $date_paid, $transno);
					add_gl_trans(ST_BANKDEPOSIT, $transno, sql2date($date_paid), 1010, 0, 0, $remarks,-$amount, null, $person_type_id, $person_id);

					$sql_cib="select account_code from ".TB_PREF."bank_accounts where id='$bank_account'";
					//display_error($sql_cib);
					$result_cib=db_query($sql_cib);

					while ($accountrow = db_fetch($result_cib))
					{
					$cash_in_bank=$accountrow['account_code'];
					}
								
					add_gl_trans(ST_BANKDEPOSIT, $transno, sql2date($date_paid), $cash_in_bank, 0, 0, $remarks,$amount, null, $person_type_id, $person_id);
					$desc='Cleared';
					add_audit_trail(ST_BANKDEPOSIT, $transno, $date_paid,$desc);

					update_other_income_payment_header($transno,$date_paid,date2sql($date_cleared),$bank_account,$remarks,$payto);
					}
					
					else{
					display_error($row['cd_br_code'].$trans_num."failed to clear.");
					}
									
				}
				
			update_cash_dep_header($row['cd_id'],$date_paid,$date_cleared,$bank_id,$remarks,$payto,$trans_type);
			
			if ($bank_gl_code=='1020011'){
			$db_='bank_statement_aub';
			}
			else if ($bank_gl_code=='1020021'){
			$db_='bank_statement_metro';
			}
			else if ($bank_gl_code=='1010040'){
			$db_='bank_statement_bpi';
			}

			update_bank_statement($db_,$bank_statement_id,$row['cd_trans_type'],$row['cd_id']);
		}
	}
	return $count;
}

//===========AUB BANK STATEMENT==============
function handle_new_aub_excel_item()
{
	$excel_file_name=$_FILES["excel_file"]["name"];
	$excel_file_type=$_FILES["excel_file"]["type"];
	$excel_file_size=$_FILES["excel_file"]["size"];
	$excel_file_tmp=$_FILES["excel_file"]["tmp_name"];
	$data = new Spreadsheet_Excel_Reader($excel_file_tmp);
	$data->dump(false,false);

	$excel_col= $data->colcount();
	$excel_row= $data->rowcount();
	
	$bank_format_marker=$data->val(1,1);
	
	$db_='bank_statement_aub';
	
	if ($bank_format_marker=='Date')
	{
		for ($i = 2; $i <= $excel_row; $i++) 
		{
			$date_deposited=$data->val($i,1);
			$deposit_type=$data->val($i,3);
			$credit_amount=$data->val($i,5);

			$balance=number_format($data->raw($i, 6), 2);
				//$balance=$data->val($i,6);
				
			$balance = str_replace(',','',$balance);
			$balance = (double)$balance;
				
			if ($credit_amount>0 AND ($deposit_type==' CK3 - Check Deposit - Local' OR $deposit_type==' CD - Cash Deposit')){
				
				$check_count=check_exist_statement($db_,$date_deposited,$credit_amount,$balance);

				if ($check_count<=0){
					$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_aub(date_deposited,deposit_type,amount_deposited,balance)				
					VALUES ('".date2sql($date_deposited)."',".db_escape($deposit_type).",'$credit_amount','$balance')";		
					//display_error($sql);
					db_query($sql,'unable to import bank deposit statement');
				}
			}
		}
	}
	else{
			display_error("Failed To Import File, Uploaded Excel File is not an AUB Bank Statement.");
	}
	
	if ($excel_file_tmp!='') {
	display_notification_centered("Excel data has been successfully uploaded.");
		 if ($check_count>0){
		display_notification_centered("Some data or maybe all data already exist.");
		}
	}
	else {
	display_error("No Excel file has been uploaded.");
	}
}

//===========METROBANK BANK STATEMENT==============
function handle_new_metro_excel_item()
{
	$excel_file_name=$_FILES["excel_file"]["name"];
	$excel_file_type=$_FILES["excel_file"]["type"];
	$excel_file_size=$_FILES["excel_file"]["size"];
	$excel_file_tmp=$_FILES["excel_file"]["tmp_name"];
	$data = new Spreadsheet_Excel_Reader($excel_file_tmp);
	$data->dump(false,false);

	$excel_col= $data->colcount();
	$excel_row= $data->rowcount();
	
	$bank_format_marker=$data->val(4,7);
	
	$db_='bank_statement_metro';
	
	if ($bank_format_marker=='Branch')
	{
		for ($i = 5; $i <= $excel_row; $i++) 
		{
			$date_deposited=$data->val($i,1);
			$deposit_type=$data->val($i,3);
			$date_deposited= date('m/d/Y', strtotime($date_deposited));
			$credit_amount=$data->val($i,5);
			$balance=$data->val($i,6);

			if ($credit_amount>0 AND $deposit_type=='CASH/CHECK DEPOSIT') {
				
				$check_count=check_exist_statement($db_,$date_deposited,$credit_amount,$balance);
				
				if ($check_count<=0){
				$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_metro(date_deposited,deposit_type,amount_deposited,balance)				
				VALUES ('".date2sql($date_deposited)."',".db_escape($deposit_type).",'$credit_amount','$balance')";		
				//display_error($sql);
				db_query($sql,'unable to import bank deposit statement');
				}
			}
		}
	}
	else{
			display_error("Failed To Import File, Uploaded Excel File is not a Metrobank Bank Statement.");
	}
	
	if ($excel_file_tmp!='') {
	display_notification_centered("Excel data has been successfully uploaded.");
		if ($check_count>0){
		display_notification_centered("Some data or maybe all data already exist.");
		}
	}
	else {
	display_error("No Excel file has been uploaded.");
	}
}

//===========BPI BANK STATEMENT==============
function handle_new_bpi_excel_item()
{
	$excel_file_name=$_FILES["excel_file"]["name"];
	$excel_file_type=$_FILES["excel_file"]["type"];
	$excel_file_size=$_FILES["excel_file"]["size"];
	$excel_file_tmp=$_FILES["excel_file"]["tmp_name"];
	$data = new Spreadsheet_Excel_Reader($excel_file_tmp);
	$data->dump(false,false);

	$excel_col= $data->colcount();
	$excel_row= $data->rowcount();
	
	$bank_format_marker=$data->val(1,2);
	//display_error($bank_format_marker);
	
	$db_='bank_statement_bpi';

	if ($bank_format_marker=='Description')
	{
		for ($i = 2; $i <= $excel_row; $i++) 
		{
			$date_deposited=$data->val($i,1);
			$deposit_type=$data->val($i,2);
			$date_deposited= date('m/d/Y', strtotime($date_deposited));
			$credit_amount=$data->val($i,6);
			$balance=$data->val($i,7);
			
				$balance = str_replace(',','',$balance);
				$balance = (double)$balance;

				$credit_amount = str_replace(',','',$credit_amount);
				$credit_amount = (double)$credit_amount;
				//display_error($credit_amount);
				

				//display_error($deposit_type);

			if (strpos($deposit_type, '4390 CR MEMO') !== false OR strpos($deposit_type, '1349 EPS CREDT') !== false OR strpos($deposit_type, '0431 DR MEMO') !== false OR strpos($deposit_type, '4360 CO.CREDIT') !== false) {
				//display_error('good');

				$check_count=check_exist_statement($db_,$date_deposited,$credit_amount,$balance);
				
				if ($check_count<=0){
				$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_bpi(date_deposited,deposit_type,amount_deposited,balance)				
				VALUES ('".date2sql($date_deposited)."',".db_escape($deposit_type).",'$credit_amount','$balance')";		
				//display_error($sql);
				db_query($sql,'unable to import bank deposit statement');
				}
			}
		}
	}
	else{
			display_error("Failed To Import File, Uploaded Excel File is not a BPI Bank Statement.");
	}
	
	if ($excel_file_tmp!='') {
	display_notification_centered("Excel data has been successfully uploaded.");
		if ($check_count>0){
		display_notification_centered("Some data or maybe all data already exist.");
		}
	}
	else {
	display_error("No Excel file has been uploaded.");
	}
}

//=====================================================================

if (isset($_POST['upload'])) 
{
	global $db_connections;
	
	set_time_limit(0);
	
	if ($_POST['bank_account']=='1020011'){
		
		handle_new_aub_excel_item();
		
		$sql="select * from cash_deposit.".TB_PREF."bank_statement_aub where cleared='0' and date_deposited>='2015-09-02'  order by date_deposited";
		$res = db_query($sql);

		while($row = db_fetch($res))
		{
				$date_deposited=$row['date_deposited'];
				$credit_amount=$row['amount_deposited'];
				$bank_statement_id=$row['id'];
				$bank_gl_code='1020011';
				
			
				$count1=clear_aria_cash_dep_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				if ($count1<=0){
					$count2=clear_aria_other_income_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				}
				
				// if ($count2<=0){
				// $count3=clear_aria_cr_dr_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				// }
		}
		
	}
	
	else if ($_POST['bank_account']=='1020021'){
		
		handle_new_metro_excel_item();
		
		$sql="select * from cash_deposit.".TB_PREF."bank_statement_metro where cleared='0' and date_deposited>='2015-09-02'  order by date_deposited";
		$res = db_query($sql);

		while($row = db_fetch($res))
		{
				$date_deposited=$row['date_deposited'];
				$credit_amount=$row['amount_deposited'];
				$bank_statement_id=$row['id'];
				$bank_gl_code='1020021';

				$count1=clear_aria_cash_dep_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				if ($count1<=0){
					$count2=clear_aria_other_income_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				}
				
				if ($count2<=0){
				$count3=clear_aria_cr_dr_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				}
		}

	}
	
	else if ($_POST['bank_account']=='1010040'){
		//display_error('asasa');
		
		handle_new_bpi_excel_item();
		
		$sql="select * from cash_deposit.".TB_PREF."bank_statement_bpi where cleared='0' and date_deposited>='2015-09-02'  order by date_deposited";
		$res = db_query($sql);

		while($row = db_fetch($res))
		{
			$date_deposited=$row['date_deposited'];
			$credit_amount=$row['amount_deposited'];
			$bank_statement_id=$row['id'];
			$bank_gl_code='1010040';
		
			// $count1=clear_aria_cash_dep_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
			// if ($count1<=0){
				// $count2=clear_aria_other_income_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
			// }
			
			// if ($count2<=0){
			$count3=clear_aria_cr_dr_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
			// }
		}

	}
	
	else if ($_POST['bank_account']=='1030040'){
		display_error(_("Selected bank is not available."));
	}	else if ($_POST['bank_account']=='1030042'){
		display_error(_("Selected bank is not available"));
	}
	
	else {
		display_error(_("Please Select Bank."));
		set_focus('bank_type');
	}
	
	set_global_connection_branch();
}

start_form(true);
start_table("$table_style2 width=90%", 10);
start_row();
echo "<td>";
start_table();
//bank_account_list_cells('Bank:', 'bank_type', '', '', '',false,'');
bank_accounts_list_cells2('Bank Account:', 'bank_account', null,'',true);
file_cells('Excel File:','excel_file', $id="");
submit_cells('upload','Import','','',false);
end_table();
echo "</td>";
end_row();
end_table(1);
end_form();
//------------------------------------------------------------------------------------------------
end_page();
?>
