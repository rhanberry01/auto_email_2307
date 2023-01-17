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
$page_security = 'SA_DEPOSIT';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Bank Statement Breakdown"), false, false, "", $js);

$cleared_id = find_submit('clear_selected');

function get_branchcode_name($br_code)
{
$sql = "SELECT name from transfers.0_branches where code='".$br_code."' ";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_name=$row['name'];
return $br_name;
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
	WHERE date_deposited='".date2sql($date_deposited)."' AND  credit_amount='$credit_amount' AND balance='$balance'";
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

				insert_selected_to_gl($transno,$bank_gl_code);

				
			update_cash_dep_header($row['cd_id'],$date_paid,$date_cleared,$bank_id,$remarks,$payto,$trans_type);
			
			if ($bank_gl_code=='10102299'){
			$db_='bank_statement_aub_final';
			}
			else if ($bank_gl_code=='1020021'){
			$db_='bank_statement_metro_final';
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

								$sql2="select * from cash_deposit.".TB_PREF."cash_dep_header where cd_trans_type='$trans_type' and cd_id='$cd_id' and cd_cleared='0' and cd_cleared='0' ";
								//display_error($sql);
								$result=db_query($sql2);

								while($row2 = db_fetch($result))
								{
								$id=$row2['cd_id'];
								$transno=$row2['cd_aria_trans_no'];
								$amount=$row2['cd_gross_amount'];
								$br_code=$row2['cd_br_code'];
								}


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

			update_cash_dep_header($row['cd_id'],$date_paid,$date_cleared,$bank_id,$remarks,$payto,$trans_type);
			
			if ($bank_gl_code=='10102299'){
			$db_='bank_statement_aub_final';
			}
			else if ($bank_gl_code=='1020021'){
			$db_='bank_statement_metro_final';
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
				
			update_cash_dep_header($row['cd_id'],$date_paid,$date_cleared,$bank_id,$remarks,$payto,$trans_type);
			
			if ($bank_gl_code=='10102299'){
			$db_='bank_statement_aub_final';
			}
			else if ($bank_gl_code=='1020021'){
			$db_='bank_statement_metro_final';
			}
			else if ($bank_gl_code=='1010040'){
			$db_='bank_statement_bpi';
			}

			update_bank_statement($db_,$bank_statement_id,$row['cd_trans_type'],$row['cd_id']);
		}
	}
	return $count;
}


//==================================================================

if (isset($_POST['process'])) 
{
	global $db_connections;
	
	set_time_limit(0);
	
	if ($_POST['bank_account2']=='10102299'){
		
		//handle_new_aub_excel_item();
		
		$sql="select * from cash_deposit.".TB_PREF."bank_statement_aub_final where cleared='0' and date_deposited>='2015-09-02'  order by date_deposited";
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
		
	}
	
	else if ($_POST['bank_account2']=='1020021'){
		
		//handle_new_metro_excel_item();
		
		$sql="select * from cash_deposit.".TB_PREF."bank_statement_metro_final where cleared='0' and date_deposited>='2015-09-02'  order by date_deposited";
		$res = db_query($sql);

		while($row = db_fetch($res))
		{
				$date_deposited=$row['date_deposited'];
				$credit_amount=$row['credit_amount'];
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
	
	else if ($_POST['bank_account2']=='1010040'){
		//display_error('asasa');
		
		//handle_new_bpi_excel_item();
		
		$sql="select * from cash_deposit.".TB_PREF."bank_statement_bpi where cleared='0' and date_deposited>='2015-09-02'  order by date_deposited";
		$res = db_query($sql);

		while($row = db_fetch($res))
		{
				$date_deposited=$row['date_deposited'];
				$credit_amount=$row['credit_amount'];
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
	
	else if ($_POST['bank_account2']=='1030040'){
		display_error(_("Selected bank is not available."));
	}	else if ($_POST['bank_account2']=='1030042'){
		display_error(_("Selected bank is not available"));
	}
	
	else {
		display_error(_("Please Select Bank."));
		set_focus('bank_type');
	}
	
	set_global_connection_branch();
}


//====================================start heading=========================================

start_form();
// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');

global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_table("width=90% $table_style2"); // outer table

	table_section();
		//get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
		cash_dep_trans_type_list_cells('Transaction Type:', 'trans_type', '', '', '',false,'');
		bank_accounts_list_cells2('Bank Account:', 'bank_account', null,'',true);
		yesno_list_cells('Deposited :', 'yes_no', '', 'Yes', 'No');
		payment_type_list_cell('Payment Type:','payment_type');
		//ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
end_table(); // outer table

br(2);
div_end();

	if ($_POST['bank_account']=='10102299'){
	$db_='bank_statement_aub_final';
	}
	else if ($_POST['bank_account']=='1020021'){
	$db_='bank_statement_metro_final';
	}
	else if ($_POST['bank_account']=='1010040'){
	$db_='bank_statement_bpi';
	}

	// display_error($_POST['bank_account']);
	// display_error($db_);

	$sql = "select * from cash_deposit.".TB_PREF."$db_";
	
	if ($_POST['yes_no']==1) {
	$sql.="  as b
	left JOIN cash_deposit.".TB_PREF."cash_dep_header as h
	on b.reference=h.cd_id";
	}

	$sql .= " WHERE credit_amount!=0 and date_deposited >= '".date2sql($_POST['start_date'])."'
	AND date_deposited <= '".date2sql($_POST['end_date'])."'";
	
	if ($_POST['trans_type']!=''){
	$sql.= " AND type='".$_POST['trans_type']."'";
	}

	if ($_POST['yes_no']==0) {
	$sql.="  AND cleared='0'";
	}
	else {
	$sql.=" AND cleared='1'";
	}

	$sql .= "  order by date_deposited,credit_amount asc";

	$res = db_query($sql);
	//display_error($sql);
	
div_start('table_');

if ($_POST['yes_no']==0) {
start_table();
//submit_cells('process','Process Uncleared','','',false,ICON_ADD);
end_table();
}


br();

start_table($table_style2.' width=70%');
$th = array();


array_push($th,'','Date Deposit', 'Description','Amount','Type','Reference','Branch','Trans#','');

if (db_num_rows($res) > 0){
	display_heading("$bank_header Summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);
	br();
	table_header($th);
}
else
{
	display_heading('No result found');
	display_footer_exit();
}

$c=0;
$k = 0;
while($row = db_fetch($res))
{
	$c++;
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell(sql2date($row['date_deposited']));
	label_cell($row['deposit_type']);
	amount_cell($row['credit_amount'],false);

			if ($row['type']=='61'){
			label_cell('Sales');
			}
			else if ($row['type']=='62'){
			label_cell('CR/DR Card');
			}
			else if ($row['type']=='2'){
			label_cell('Other Income');
			}
			else{
			label_cell('');	
			}

	label_cell($row['reference']);
	label_cell(get_branchcode_name($row['cd_br_code']));
	//label_cell($row['cd_br_code']);
	label_cell($row['cd_aria_trans_no']);
	if ($row['cleared']==0) {
	label_cell('Uncleared');
	}
	else {
	label_cell('Cleared');
	}
	end_row();
end_form();

$t_amount+=$row['credit_amount'];
//$t_balance+=$row['balance'];
}

start_row();
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>','align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	//label_cell("<font color=#880000><b>".number_format2(abs($t_balance),2)."<b></font>",'align=right');
	label_cell('');
		label_cell('');
	if ($_POST['yes_no']==1) {
label_cell('');
label_cell('');
}
else{
	label_cell('');
	label_cell('');
}
	label_cell('');



end_row();
end_table();
hidden('bank_account2',$_POST['bank_account']);

div_end();
end_form();
end_page();
?>