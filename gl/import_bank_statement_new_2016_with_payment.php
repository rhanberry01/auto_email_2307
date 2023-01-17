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
function get_online_payments($date_deposited, $debit_amount)
{
	$from = add_days(sql2date($date_deposited), -7);
	$to = $date_deposited;
	
	$sql = "SELECT c.tran_date, b.*  
			FROM 0_cv_header a, 0_bank_trans b, 0_supp_trans c
			WHERE a.online_payment=2 
			AND a.bank_trans_id = b.id
			AND a.amount!=0
			AND c.type='22'
			AND c.ov_amount!=0
			AND c.tran_date >= '2016-01-01'
			AND c.tran_date <= '".$date_deposited."'
			AND b.type = c.type
			AND b.trans_no = c.trans_no
			AND b.amount='-$debit_amount'
			AND (ISNULL(b.reconciled) OR b.reconciled='' OR  b.reconciled='0000-00-00')
			ORDER BY trans_date";
			//display_error($sql);
			//AND b.trans_date >= '".date2sql($from)."'
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
	
}

function get_bt_cheque_details($chk_number,$debit_amount,$date_deposited)
{
$sql = "SELECT a.tran_date, a.supplier_id, a.cv_id, c.bank_trans_id,c.bank_id, c.chk_number,
c.chk_date, c.chk_amount, c.remark ,b.reconciled
FROM 0_supp_trans a, 0_bank_trans b, 
0_cheque_details c 
WHERE a.tran_date>='2016-01-01' AND a.tran_date<='".$date_deposited."'
AND a.type = '22'
AND a.type = b.type 
AND a.ov_amount!=0
AND a.trans_no = b.trans_no 
AND b.id = c.bank_trans_id 
AND c.chk_number='$chk_number'
AND c.chk_amount='$debit_amount'
AND (ISNULL(b.reconciled) OR b.reconciled='' OR  b.reconciled='0000-00-00')
AND c.deposit_date='0000-00-00'
ORDER BY a.tran_date";
	
$res = db_query($sql);
$row = db_fetch($res);
	
	return $row;
}


function get_all_bpi_sales_credit_debit_trans($sales_dr_cr_date) {
$sql="
SELECT sdc.*,ab.*
FROM ".TB_PREF."sales_debit_credit as sdc 
LEFT JOIN ".TB_PREF."acquiring_banks as ab on sdc.dc_card_desc=ab.acquiring_bank 
WHERE sdc.dc_remittance_date='$sales_dr_cr_date' 
AND (sdc.dc_card_desc='BPI' OR sdc.dc_card_desc='bpi')
AND sdc.processed='0' AND sdc.paid='0' 
";
//display_error($sql);
return $sql;
}


function get_all_metro_sales_credit_debit_trans($sales_dr_cr_date) {
$sql="
SELECT sdc.*,ab.*
FROM ".TB_PREF."sales_debit_credit as sdc 
LEFT JOIN ".TB_PREF."acquiring_banks as ab on sdc.dc_card_desc=ab.acquiring_bank 
WHERE sdc.dc_remittance_date='$sales_dr_cr_date' 
AND (sdc.dc_card_desc='METROBANK' OR sdc.dc_card_desc='metrobank')
AND sdc.processed='0' AND sdc.paid='0' 
";
//display_error($sql);
return $sql;
}

function next_type_no()
{
	$gl_type=ST_CREDITDEBITDEPOSIT;
	$sql = "select max(type_no)+1 from 0_gl_trans where type='$gl_type'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function approve_acquiring($id,$date_paid)
{
	$sqlpay ="UPDATE ".TB_PREF."sales_debit_credit SET processed='1', paid='1', dc_date_paid='$date_paid',checked='2' WHERE dc_id='".$id."'";
	db_query($sqlpay,'failed to approve.');	
	//display_error($sqlpay);
}


function insert_approve_acquiring($over_payment,$charge_back,$approve_id,$p_ref_id,$transno,$remarks,$terminal_id,$transdate,$remitdate,$tendertype,$card_desc,$accountno,$approvalno,$receivable_amount,$deposited_amount,$fee,$mdr,$wt,$cwt,$net,$date_paid)
{
if($charge_back==1) {
$deposited_amount=-$deposited_amount;
$receivable_amount=-$receivable_amount;
$mdr=-$mdr;
$net=-$net;
}

$sql = "INSERT INTO ".TB_PREF."acquiring_deductions
				(dc_id,p_ref_id,p_trans_no,p_trans_date,p_remarks,p_terminal_id,p_remittance_date,p_tender_type,p_bank_card,
				p_account_no,p_approval_no,p_receivable_amount,p_deposited_amount,p_mfeepercent,p_mfeeamount,p_wtaxpercent,p_wtaxamount,p_net_total,p_over_payment,p_charge_back,date_paid) 
			VALUES('".$approve_id."','".$p_ref_id."','".$transno."','".$transdate."','".$remarks."',
			'".$terminal_id."','".$remitdate."','".$tendertype."','".$card_desc."','".$accountno."',
			'".$approvalno."','".$receivable_amount."','".$deposited_amount."','".$fee."','".$mdr."','".$wt."','".$cwt."','".$net."','".$over_payment."','".$charge_back."','".$date_paid."')";
	
	if ($transdate!=0 or $transdate!='') {
	db_query($sql,"Bank Deduction could not be approved");
	}
	//display_error($sql);
//display_notification('Selected has been approved');
}

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
	
	//merchant fee
	add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_mfee_account, 0, 0, $memo, $p_mfeeamount, null, 0);
	
	//withholding tax
	if($card_tender_type=='013')
	{
	add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_wtax_account, 0, 0, $memo, $p_wtaxamount, null, 0);
	}

	//over payment
	if ($p_over_amount>0) {
	add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_over_deposit_acount, 0, 0, $memo, -$p_over_amount, null, 0);
	}
	
	//receivable
	add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_bank_debit_account, 0, 0, $memo, -$p_receivable_amount, null, 0);

	//chargeback
	if ($a_net_total!='') {
	add_gl_trans(ST_CREDITDEBITDEPOSIT,  $p_ref_id, sql2date($date_paid), $gl_over_deposit_acount, 0, 0, $memo, $a_net_total, null, 0);
	}
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

function update_bank_statement($db_,$bank_statement_id,$trans_type,$dep_id,$myBranchCode)	
{		
	 $sql = "UPDATE cash_deposit.".TB_PREF."$db_ 
	 SET type = '$trans_type',
	 reference='$dep_id',
	 cleared='1',
	 branch_code='$myBranchCode'
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

function check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance)
{
	if ($credit_amount>0 AND $credit_amount!=0){
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."$db_ 
	WHERE date_deposited='".date2sql($date_deposited)."' AND  credit_amount='$credit_amount' AND balance='$balance'";
	}
	else{
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."$db_ 
	WHERE date_deposited='".date2sql($date_deposited)."' AND  debit_amount='$debit_amount' AND balance='$balance'";
	}
	
	//display_error($sql);
	$res = db_query($sql);
	$count_row=db_num_rows($res);
	return $count_row;
}


function clear_aria_cr_dr_trans($bank_statement_id,$date_paid,$credit_amount,$bank_gl_code)
{
	global $db_connections;
	$trans_type=ST_CREDITDEBITDEPOSIT;
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."cash_dep_header WHERE cd_trans_type='$trans_type' and cd_gross_amount='$credit_amount'  and cd_bank_account_code='$bank_gl_code' and cd_cleared='0' and cd_sales_date>='2016-01-01' order by cd_trans_date limit 1";
	$res = db_query($sql);
	
	$count=db_num_rows($res);
	
	if ($count>0){
		while($row = db_fetch($res))
		{
			switch_connection_to_branch($row['cd_br_code']);
			
			$myBranchCode=$row['cd_br_code'];
				
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
			
			if ($bank_gl_code=='10102299'){
			$db_='bank_statement_aub';
			}
			else if ($bank_gl_code=='1020021'){
			$db_='bank_statement_metro';
			}
			else if ($bank_gl_code=='1010040'){
			$db_='bank_statement_bpi';
			}

			update_bank_statement($db_,$bank_statement_id,$row['cd_trans_type'],$row['cd_id'],$myBranchCode);
		}
	}

	return $count;
		//display_error($sql);
}


function clear_aria_cash_dep_trans($bank_statement_id,$date_paid,$credit_amount,$bank_gl_code)
{
	global $db_connections;
	$trans_type=ST_CASHDEPOSIT;
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."cash_dep_header WHERE cd_trans_type='$trans_type' and cd_gross_amount='$credit_amount'  and cd_bank_account_code='$bank_gl_code' and cd_cleared='0' and cd_sales_date>='2016-01-01' order by cd_trans_date limit 1";
	$res = db_query($sql);
	
	$count=db_num_rows($res);
	
		//display_error($sql);
	if ($count>0){
		while($row = db_fetch($res))
		{
			if ($date_paid>$row['cd_date_deposit']){
			
				switch_connection_to_branch($row['cd_br_code']);
				
					$myBranchCode=$row['cd_br_code'];
					
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
				
				if ($bank_gl_code=='10102299'){
				$db_='bank_statement_aub';
				}
				else if ($bank_gl_code=='1020021'){
				$db_='bank_statement_metro';
				}
				else if ($bank_gl_code=='1010040'){
				$db_='bank_statement_bpi';
				}

				update_bank_statement($db_,$bank_statement_id,$row['cd_trans_type'],$row['cd_id'],$myBranchCode);
				
			}	
		}
	}
	return $count;
}


function clear_aria_other_income_trans($bank_statement_id,$date_paid,$credit_amount,$bank_gl_code)
{
	global $db_connections;
	$trans_type=ST_BANKDEPOSIT;
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."cash_dep_header WHERE cd_trans_type='$trans_type' and cd_gross_amount='$credit_amount'  and cd_bank_account_code='$bank_gl_code' and cd_cleared='0' and cd_sales_date>='2016-01-01' order by cd_trans_date limit 1";
	$res = db_query($sql);
	
	$count=db_num_rows($res);
	//display_error($sql);
	if ($count>0){
		while($row = db_fetch($res))
		{
			if ($date_paid>$row['cd_sales_date']){
				
					switch_connection_to_branch($row['cd_br_code']);
					
						$myBranchCode=$row['cd_br_code'];
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
					
					if ($bank_gl_code=='10102299'){
					$db_='bank_statement_aub';
					}
					else if ($bank_gl_code=='1020021'){
					$db_='bank_statement_metro';
					}
					else if ($bank_gl_code=='1010040'){
					$db_='bank_statement_bpi';
					}

					update_bank_statement($db_,$bank_statement_id,$row['cd_trans_type'],$row['cd_id'],$myBranchCode);
			}
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
			
				$bank_ref_num=$data->val($i,2);
				$bank_ref_num = preg_replace('/\s+/'," ",$bank_ref_num);
				$bank_ref_num=trim($bank_ref_num);

				$deposit_type=$data->val($i,3);
				$deposit_type = preg_replace('/\s+/'," ",$deposit_type);
				$deposit_type=trim($deposit_type);
			
				//$debit_amount=$data->val($i,4);
			
				// if (!is_numeric($debit_amount)){
					// $debit_amount=number_format($data->val($i,4), 2);
					// //$balance=$data->val($i,6);
					// $debit_amount = str_replace(',','',$debit_amount);
					// $debit_amount = (double)$debit_amount;
				// }

			$balance=number_format($data->raw($i, 6), 2);
				//$balance=$data->val($i,6);
			$balance = str_replace(',','',$balance);
			$balance = (double)$balance;
			
			$debit_amount=number_format($data->raw($i, 4), 2);
			$debit_amount = str_replace(',','',$debit_amount);
			$debit_amount = (double)$debit_amount;
			
			$credit_amount=number_format($data->raw($i, 5), 2);
			$credit_amount = str_replace(',','',$credit_amount);
			$credit_amount = (double)$credit_amount;
				
			if ($credit_amount!=0 AND $credit_amount>0){
				
				$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance);

				if ($check_count<=0 AND $credit_amount!=0){
					$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_aub(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
					VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",0,'$credit_amount','$balance')";		
					//display_error($sql);
					db_query($sql,'unable to import bank deposit statement');
				}
			}
			else{
				
					$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance);

					if ($check_count<=0 AND $debit_amount!=0){
					$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_aub(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
					VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",'$debit_amount',0,'$balance')";		
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
			$bank_ref_num=$data->val($i,2);
			$deposit_type=$data->val($i,3);
			$date_deposited= date('m/d/Y', strtotime($date_deposited));
			// $credit_amount=$data->val($i,5);
			// $debit_amount=$data->val($i,4);
			// $balance=$data->val($i,6);


			$balance=number_format($data->raw($i, 6), 2);
				//$balance=$data->val($i,6);
			$balance = str_replace(',','',$balance);
			$balance = (double)$balance;
			
			$debit_amount=number_format($data->raw($i, 4), 2);
			$debit_amount = str_replace(',','',$debit_amount);
			$debit_amount = (double)$debit_amount;
			
			$credit_amount=number_format($data->raw($i, 5), 2);
			$credit_amount = str_replace(',','',$credit_amount);
			$credit_amount = (double)$credit_amount;

			
						if ($credit_amount!=0 AND $credit_amount>0){
						
							$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance);
							
							if ($check_count<=0 AND $credit_amount!=0){
							$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_metro(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
							VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",0,'$credit_amount','$balance')";		
							//display_error($sql);
							db_query($sql,'unable to import bank deposit statement');
						}
						}
						else{
					
							$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance);

							if ($check_count<=0 AND $debit_amount!=0){
							$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_metro(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
							VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",'$debit_amount',0,'$balance')";		
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
				$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_bpi(date_deposited,deposit_type,credit_amount,balance)				
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
	
	if ($_POST['bank_account']=='10102299'){
		
		handle_new_aub_excel_item();
		//===========SALES,OI=========
		$sql="select * from cash_deposit.".TB_PREF."bank_statement_aub where credit_amount!=0 and cleared='0' and date_deposited>='2016-01-01'  order by date_deposited";
		$res = db_query($sql);

		while($row = db_fetch($res))
		{
				$date_deposited=$row['date_deposited'];
				$credit_amount=$row['credit_amount'];
				$bank_statement_id=$row['id'];
				$bank_gl_code='10102299';
				
			
				$count1=clear_aria_cash_dep_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				if ($count1<=0){
					$count2=clear_aria_other_income_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				}
				
				// if ($count2<=0){
				// $count3=clear_aria_cr_dr_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				// }
		}
		//==================================
		
		//===========PAYMENT RECON=============
		$db_='bank_statement_aub';
		$type=22;
		
		$sql_ = "SELECT * from transfers.0_branches where code!='srscain2'";
		//display_error($sql);
		$result_=db_query($sql_);
		while($row_ = db_fetch($result_))
		{
				$myBranchCode=$row_['code'];
				switch_connection_to_branch($row_['code']);			
				
				$sql_payment="select * from cash_deposit.".TB_PREF."bank_statement_aub where cleared='0' AND debit_amount!=0 order by date_deposited";
				$res_payment = db_query($sql_payment);

				while($row_debit = db_fetch($res_payment))
				{
				$date_deposited=$row_debit['date_deposited'];
				$debit_amount=$row_debit['debit_amount'];
				$bank_ref_num=$row_debit['bank_ref_num'];
				$bank_statement_id=$row_debit['id'];
				$bank_gl_code='10102299';
				
				$row_bank_trans=get_bt_cheque_details($bank_ref_num,$debit_amount,$date_deposited);
				$bank_trans_id=$row_bank_trans['bank_trans_id'];
				
					if ($bank_trans_id!=0 AND $bank_trans_id!='')
					{
						$sql_update_bank_trans = "UPDATE ".TB_PREF."bank_trans SET reconciled='$date_deposited'
						WHERE id ='$bank_trans_id' AND type='$type'";
						db_query($sql_update_bank_trans);
						
						update_bank_statement($db_,$bank_statement_id,$type,$bank_trans_id,$myBranchCode);
						
						$sql = "UPDATE ".TB_PREF."cheque_details 
						SET deposited = 1,
						deposit_date='$date_deposited'
						WHERE bank_trans_id= ".db_escape($bank_trans_id)."";
						// display_error($sql);
						db_query($sql);
					}	
				}
		}
	//===================================
	}
	
	else if ($_POST['bank_account']=='1020021'){
		
		handle_new_metro_excel_item();
		
		//===========SALES,OI,CR-DR=========
		$sql1="select * from cash_deposit.".TB_PREF."bank_statement_metro where cleared='0' and date_deposited>='2016-01-01' order by date_deposited";
		$res1 = db_query($sql1);

		while($row1 = db_fetch($res1))
		{
				$date_deposited=$row1['date_deposited'];
				$credit_amount=$row1['credit_amount'];
				$bank_statement_id=$row1['id'];
				$bank_gl_code='1020021';

				$count1=clear_aria_cash_dep_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				
				if ($count1<=0){
					$count2=clear_aria_other_income_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				}	
				// if ($count2<=0){
				// $count3=clear_aria_cr_dr_trans($bank_statement_id,$date_deposited,$credit_amount,$bank_gl_code);
				// }
		}
		
		//$start_date_paid = add_days(Today(), -1);
			
		$sql = "SELECT * from transfers.0_branches where code!='srscain2'";
		//display_error($sql);
		$result=db_query($sql);
		while($row = db_fetch($result))
		{
				$myBranchCode=$row['code'];
				switch_connection_to_branch($row['code']);
		
				$sql_sales_dr_cr = "SELECT date,tender_type,gross,mdr,cwt, (gross-(cwt+mdr)) as net FROM (SELECT 
				sdc.dc_remittance_date as date,sdc.dc_tender_type as tender_type, 
				round(sum(sdc.dc_trans_amount),2) as gross,
				round(sum((sdc.dc_trans_amount * 
				case when dc_tender_type='013' then cc_merchant_fee_percent else dc_merchant_fee_percent end)/100),2) as mdr,
				round(sum((sdc.dc_trans_amount * 
				case when dc_tender_type='013' then cc_withholding_tax_percent else 0 end)/100),2) as cwt
				FROM 
				".TB_PREF."sales_debit_credit as sdc
				LEFT JOIN ".TB_PREF."acquiring_banks as ab 
				on sdc.dc_card_desc=ab.acquiring_bank
				WHERE sdc.dc_remittance_date>='2016-01-01' AND sdc.dc_remittance_date<='2016-02-31'
				AND (sdc.dc_card_desc='METROBANK' OR sdc.dc_card_desc='metrobank')
				AND sdc.processed='0' AND sdc.paid='0'
				GROUP BY sdc.dc_remittance_date,sdc.dc_tender_type
				) as a";
				
				$res = db_query($sql_sales_dr_cr);
				$count1=db_num_rows($res);
				//display_error($count1);
				
					while($row_sales_dr_cr = db_fetch($res))
					{
						//display_error($row_sales_dr_cr['net']);
						
							$tender_type=$row_sales_dr_cr['tender_type'];
							if($tender_type=='013'){
								$deposit_type='BATCH CREDITING'; //this is not official
								
							}
							else{
								$deposit_type='CASH/CHECK DEPOSIT'; //this is not official
 						}
						
						$minimum_amount=$row_sales_dr_cr['net']-5;
						$maximum_amount=$row_sales_dr_cr['net']+5;	
						$sales_dr_cr_date=$row_sales_dr_cr['date'];
						
						
						//$date_from_clear=add_days(sql2date($row_sales_dr_cr['date']), -5);
						$date_to_clear=add_days(sql2date($row_sales_dr_cr['date']), 7);
						
						$sql2="select * from cash_deposit.".TB_PREF."bank_statement_metro where  date_deposited>'".$sales_dr_cr_date."' and date_deposited<='".date2sql($date_to_clear)."'
						AND (credit_amount>='".$minimum_amount."' AND credit_amount<='".$maximum_amount."') AND deposit_type='$deposit_type' AND cleared='0' ORDER BY date_deposited LIMIT 1";
						$res2 = db_query($sql2);
						//display_error($sql2);
						//'".$row_sales_dr_cr['date']."'  				
						
							while($statement_row = db_fetch($res2))
							{
								
								//display_error('asas');
								
								$date_paid=$statement_row['date_deposited'];
								$bank_statement_id=$statement_row['id'];
								
											$p_ref_id=next_type_no();
											if ($p_ref_id==null) {
											$p_ref_id=1;
											}
								
											$inserttype=ST_CREDITDEBITDEPOSIT;
											//TO CHECK IF TRANSNO IS VOIDED ALREADY
											$void_entry = get_voided_entry($inserttype, $p_ref_id);
											db_fetch_row($void_entry);
											
											if ($void_entry>0) {
											$sqldelrefs = "Delete FROM ".TB_PREF."voided
											WHERE type = '$inserttype' and id='$p_ref_id'";
											db_query($sqldelrefs);
											//display_error($sqldelrefs);	
											if (!$sqldelrefs)
											{
											display_error(_("Failed to delete voided gl trans_no."));
											return false;
											}
											}
											
												//DB SELECTION OF CARD TRANSACTION
												$sql=get_all_metro_sales_credit_debit_trans($sales_dr_cr_date);
												if ($tender_type!='') {
												$sql.=" AND sdc.dc_tender_type='$tender_type'";
												}
												$result1=db_query($sql);
												//display_error($sql);
												
												while($row = db_fetch($result1))
												{
													$remarks='';
													$approval_num=$row['dc_approval_no'];
													$receivable_amount=$row['dc_trans_amount'];
													$deposited_amount=$row['dc_trans_amount'];
													$gl_bank_account=$row['gl_bank_account'];
													$gl_bank_debit_account=$row['gl_bank_debit_account'];
													$gl_mfee_account=$row['gl_mfee_account'];
													$gl_wtax_account=$row['gl_wtax_account'];
													$cc_fee=$row['cc_merchant_fee_percent'];
													$dc_fee=$row['dc_merchant_fee_percent'];
													$cc_wt=$row['cc_withholding_tax_percent'];
													$a_b=$row['acquiring_bank'];
													$a_b='BPI';
													$charge_back=$row['dc_charge_back'];
														
														if ($tender_type=='013') {
														$fee=$cc_fee; 
														$wt=$cc_wt;

														if ($charge_back=='1') {
														$wt=$cc_wt;
														}
														}
														
														if ($tender_type=='014') {
														$fee=$dc_fee; 
														$wt=0;

														if ($charge_back=='1') {
														$wt=0;
														}
														}
													
														$id=$row['dc_id'];
														$transdate=$row['dc_transaction_date'];
														$remitdate=$row['dc_remittance_date'];
														$transno=$row['dc_trans_no'];
														$card_desc=$row['dc_card_desc'];
														$accountno=$row['dc_account_no'];
														//$approvalno=$row['APPROVALNO'];
														$over_payment=$row['dc_over_payment'];
													
														$mdr=round(($deposited_amount * $fee) / 100,2);
														$cwt=round(($deposited_amount * $wt) / 100,2);
														$net=$deposited_amount-($cwt+$mdr);
													
														$net_total+=$net;
														
														$terminal_id='';

														approve_acquiring($id,$date_paid);
														insert_approve_acquiring($over_payment,$charge_back,$id,$p_ref_id,$transno,$remarks,$terminal_id,$transdate,$remitdate,$tender_type,$card_desc,
														$accountno,$approval_num,$receivable_amount,$deposited_amount,$fee,$mdr,$wt,$cwt,$net,$date_paid);		
												}

											insert_selected_to_gl($p_ref_id,$gl_bank_account);
											
											//$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
											$cd_id=add_cash_dep_header($myBranchCode,$payment_type=0,sql2date($transdate),$inserttype,$p_ref_id,$net_total,$desc=1,$memo_,$payee_name);
											
											$date_cleared=Today();
											$db_='bank_statement_metro';
											
											update_cash_dep_header($cd_id,$date_paid,$date_cleared,$bank_id,$remarks,$payto,$inserttype);
											
											update_bank_statement($db_,$bank_statement_id,$inserttype,$cd_id,$myBranchCode);
											
											$sql_cib="select id,account_code from ".TB_PREF."bank_accounts where account_code='$gl_bank_account'";
											//display_error($sql_cib);
											$result_cib=db_query($sql_cib);

											while ($accountrow = db_fetch($result_cib))
											{
											$bank_id=$accountrow['id'];
											$cash_in_bank=$accountrow['account_code'];
											}

											$cash_in_bank=$gl_bank_account;
																			
											$sql = "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
											SET cd_bank_account_code='$gl_bank_account',
											cd_bank_deposited='$bank_id'
											WHERE cd_id = '$cd_id'";
											//display_error($gl_bank_account);
											db_query($sql);
											
											display_notification(_('Selected transaction has been succesfuly approved.'));
								}
					}
		}
		//==============================================
		
		set_global_connection_branch();//go back to default connection.
		
		//===========PAYMENT RECON=============
		$db_='bank_statement_metro';
		$type=22;
		
		
		$sql_ = "SELECT * from transfers.0_branches where code!='srscain2'";
		$result_=db_query($sql_);

		while($row_ = db_fetch($result_))
		{
			$myBranchCode=$row_['code'];
			switch_connection_to_branch($row_['code']);			
			
			$sql_payment="select * from cash_deposit.".TB_PREF."bank_statement_metro where cleared='0' AND debit_amount!=0 order by date_deposited";
			$res_payment = db_query($sql_payment);

			while($row_debit = db_fetch($res_payment))
			{
			$date_deposited=$row_debit['date_deposited'];
			$debit_amount=$row_debit['debit_amount'];
			$bank_ref_num=$row_debit['bank_ref_num'];
			$bank_statement_id=$row_debit['id'];
			$bank_gl_code='1020021';
			
			$row_bank_trans=get_online_payments($date_deposited,$debit_amount);
			
			$bank_trans_id=$row_bank_trans['id'];
			
			//display_error($bank_trans_id);
			
					if ($bank_trans_id!=0 AND $bank_trans_id!='')
					{
						$sql_update_bank_trans = "UPDATE ".TB_PREF."bank_trans SET reconciled='$date_deposited'
						WHERE id ='$bank_trans_id' AND type='$type'";
						db_query($sql_update_bank_trans);
						
						update_bank_statement($db_,$bank_statement_id,$type,$bank_trans_id,$myBranchCode);
					}
			
			}
		}
		//==============================================
		
	}
	
	else if ($_POST['bank_account']=='1010040'){
		//display_error('asasa');
		set_time_limit(0);
			
			handle_new_bpi_excel_item();
			
		$start_date_paid = add_days(Today(), -1);
			
		$sql = "SELECT * from transfers.0_branches where code!='srscain2'";
		//display_error($sql);
		$result=db_query($sql);
		while($row = db_fetch($result))
		{
				$myBranchCode=$row['code'];
				switch_connection_to_branch($row['code']);
		
				$sql_sales_dr_cr="SELECT date,tender_type,gross,mdr,cwt, (gross-(cwt+mdr)) as net FROM (SELECT 
				sdc.dc_remittance_date as date,sdc.dc_tender_type as tender_type, 
				round(sum(sdc.dc_trans_amount),2) as gross,
				round(sum((sdc.dc_trans_amount * 
				case when dc_tender_type='013' then cc_merchant_fee_percent else dc_merchant_fee_percent end)/100),2) as mdr,
				round(sum((sdc.dc_trans_amount * 
				case when dc_tender_type='013' then cc_withholding_tax_percent else 0 end)/100),2) as cwt
				FROM 
				".TB_PREF."sales_debit_credit as sdc
				LEFT JOIN ".TB_PREF."acquiring_banks as ab 
				on sdc.dc_card_desc=ab.acquiring_bank
				WHERE sdc.dc_remittance_date>='2016-01-01' AND sdc.dc_remittance_date<='2016-02-31'
				AND (sdc.dc_card_desc='BPI' OR sdc.dc_card_desc='bpi')
				AND sdc.processed='0' AND sdc.paid='0'
				GROUP BY sdc.dc_remittance_date,sdc.dc_tender_type
				) as a";
		
				$res = db_query($sql_sales_dr_cr);
				$count1=db_num_rows($res);
				//display_error($count1);
				
					while($row_sales_dr_cr = db_fetch($res))
					{
						//display_error($row_sales_dr_cr['net']);
						$minimum_amount=$row_sales_dr_cr['net']-5;
						$maximum_amount=$row_sales_dr_cr['net']+5;	
						$sales_dr_cr_date=$row_sales_dr_cr['date'];
						$tender_type=$row_sales_dr_cr['tender_type'];
						
						if($tender_type=='013'){
							$deposit_type='4390 CR MEMO';
							
							$date_to_clear=add_days(sql2date($row_sales_dr_cr['date']), 3);
							$sql2="select * from cash_deposit.".TB_PREF."bank_statement_bpi where date_deposited > '".$row_sales_dr_cr['date']."' and date_deposited<='".date2sql($date_to_clear)."'
							AND (credit_amount>='".$minimum_amount."' AND credit_amount<='".$maximum_amount."') AND deposit_type='$deposit_type' AND cleared='0' ORDER BY date_deposited LIMIT 1";
							$res2 = db_query($sql2);
						}
						else{
							$deposit_type='1349 EPS CREDT';
									
							$date_to_clear=add_days(sql2date($row_sales_dr_cr['date']), 3);
							$sql2="select * from cash_deposit.".TB_PREF."bank_statement_bpi where date_deposited>='".$row_sales_dr_cr['date']."' and date_deposited<='".date2sql($date_to_clear)."'
							AND (credit_amount>='".$minimum_amount."' AND credit_amount<='".$maximum_amount."') AND deposit_type='$deposit_type' AND cleared='0' ORDER BY date_deposited LIMIT 1";
							$res2 = db_query($sql2);
						}

						//display_error($sql2);
						// date_deposited>='".$row_sales_dr_cr['date']."' 
						
							while($statement_row = db_fetch($res2))
							{
								//display_error('asas');		
								$date_paid=$statement_row['date_deposited'];
								$bank_statement_id=$statement_row['id'];
								
											$p_ref_id=next_type_no();
											if ($p_ref_id==null) {
											$p_ref_id=1;
											}
								
											$inserttype=ST_CREDITDEBITDEPOSIT;
											//TO CHECK IF TRANSNO IS VOIDED ALREADY
											$void_entry = get_voided_entry($inserttype, $p_ref_id);
											db_fetch_row($void_entry);
											
											if ($void_entry>0) {
											$sqldelrefs = "Delete FROM ".TB_PREF."voided
											WHERE type = '$inserttype' and id='$p_ref_id'";
											db_query($sqldelrefs);
											//display_error($sqldelrefs);	
											if (!$sqldelrefs)
											{
											display_error(_("Failed to delete voided gl trans_no."));
											return false;
											}
											}
											
												//DB SELECTION OF CARD TRANSACTION
												$sql=get_all_bpi_sales_credit_debit_trans($sales_dr_cr_date);
												if ($tender_type!='') {
												$sql.=" AND sdc.dc_tender_type='$tender_type'";
												}
												$result1=db_query($sql);
												//display_error($sql);
												
												while($row = db_fetch($result1))
												{
													$remarks='';
													$approval_num=$row['dc_approval_no'];
													$receivable_amount=$row['dc_trans_amount'];
													$deposited_amount=$row['dc_trans_amount'];
													$gl_bank_account=$row['gl_bank_account'];
													$gl_bank_debit_account=$row['gl_bank_debit_account'];
													$gl_mfee_account=$row['gl_mfee_account'];
													$gl_wtax_account=$row['gl_wtax_account'];
													$cc_fee=$row['cc_merchant_fee_percent'];
													$dc_fee=$row['dc_merchant_fee_percent'];
													$cc_wt=$row['cc_withholding_tax_percent'];
													$a_b=$row['acquiring_bank'];
													$a_b='BPI';
													$charge_back=$row['dc_charge_back'];
														
														if ($tender_type=='013') {
														$fee=$cc_fee; 
														$wt=$cc_wt;

														if ($charge_back=='1') {
														$wt=$cc_wt;
														}
														}
														
														if ($tender_type=='014') {
														$fee=$dc_fee; 
														$wt=0;

														if ($charge_back=='1') {
														$wt=0;
														}
														}
													
														$id=$row['dc_id'];
														$transdate=$row['dc_transaction_date'];
														$remitdate=$row['dc_remittance_date'];
														$transno=$row['dc_trans_no'];
														$card_desc=$row['dc_card_desc'];
														$accountno=$row['dc_account_no'];
														//$approvalno=$row['APPROVALNO'];
														$over_payment=$row['dc_over_payment'];
													
														$mdr=round(($deposited_amount * $fee) / 100,2);
														$cwt=round(($deposited_amount * $wt) / 100,2);
														$net=$deposited_amount-($cwt+$mdr);
													
														$net_total+=$net;
														
														$terminal_id='';

														approve_acquiring($id,$date_paid);
														insert_approve_acquiring($over_payment,$charge_back,$id,$p_ref_id,$transno,$remarks,$terminal_id,$transdate,$remitdate,$tender_type,$card_desc,
														$accountno,$approval_num,$receivable_amount,$deposited_amount,$fee,$mdr,$wt,$cwt,$net,$date_paid);		
												}

											insert_selected_to_gl($p_ref_id,$gl_bank_account);
											
											//$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
											$cd_id=add_cash_dep_header($myBranchCode,$payment_type=0,sql2date($transdate),$inserttype,$p_ref_id,$net_total,$desc=1,$memo_,$payee_name);
											
											$date_cleared=Today();
											$db_='bank_statement_bpi';
											
											update_cash_dep_header($cd_id,$date_paid,$date_cleared,$bank_id,$remarks,$payto,$inserttype);
											
											update_bank_statement($db_,$bank_statement_id,$inserttype,$cd_id,$myBranchCode);
											
											$sql_cib="select id,account_code from ".TB_PREF."bank_accounts where account_code='$gl_bank_account'";
											//display_error($sql_cib);
											$result_cib=db_query($sql_cib);

											while ($accountrow = db_fetch($result_cib))
											{
												$bank_id=$accountrow['id'];
												$cash_in_bank=$accountrow['account_code'];
											}

											$cash_in_bank=$gl_bank_account;
																			
											$sql="UPDATE cash_deposit.".TB_PREF."cash_dep_header 
											SET cd_bank_account_code='$gl_bank_account',
											cd_bank_deposited='$bank_id'
											WHERE cd_id = '$cd_id'";
											//display_error($gl_bank_account);
											db_query($sql);
											
											display_notification(_('Selected transaction has been succesfuly approved.'));
								}
					}
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