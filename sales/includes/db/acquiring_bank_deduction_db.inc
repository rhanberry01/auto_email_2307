<?php
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");


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
	$sqlpay ="UPDATE ".TB_PREF."sales_debit_credit SET processed='1', paid='1', dc_date_paid='$date_paid' WHERE dc_id='".$id."'";
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

function get_all_credit_debit_trans($date_from,$date_to) {
$sql="
select ot.checked as checked, ot.dc_remittance_date as SUMM_DATE,ot.dc_transaction_date as TRANS_DATE,
ot.dc_id as ID,ot.dc_tender_type as TENDER,ot.dc_trans_no as TRANSNO, ot.dc_card_desc as CARD_DESC, ot.dc_account_no as ACCOUNTNO,ot.dc_approval_no as APPROVALNO,
ot.dc_over_payment as OVER_PAYMENT, ot.dc_charge_back as CHARGE_BACK,
case when ot.dc_tender_type='013' and (ot.dc_card_desc like 'BPI%' or ot.dc_card_desc='BPI' or ot.dc_card_desc='bpi') then ot.dc_trans_amount else 0 end as BPI_CREDIT,
case when ot.dc_tender_type='014' and (ot.dc_card_desc like 'BPI%' or ot.dc_card_desc='BPI' or ot.dc_card_desc='bpi') then ot.dc_trans_amount else 0 end as BPI_DEBIT,
case when ot.dc_tender_type='013' and (ot.dc_card_desc like 'PNB%' or ot.dc_card_desc='PNB' or ot.dc_card_desc='pnb') then ot.dc_trans_amount else 0 end as PNB_CREDIT,
case when ot.dc_tender_type='014' and (ot.dc_card_desc like 'PNB%' or ot.dc_card_desc='PNB' or ot.dc_card_desc='pnb') then ot.dc_trans_amount else 0 end as PNB_DEBIT,
case when ot.dc_tender_type='013' and (ot.dc_card_desc like 'METROBANK%' or ot.dc_card_desc='METROBANK' or ot.dc_card_desc='metrobank') then ot.dc_trans_amount else 0 end as METROBANK_CREDIT,
case when ot.dc_tender_type='014' and (ot.dc_card_desc like 'METROBANK%' or ot.dc_card_desc='METROBANK' or ot.dc_card_desc='metrobank') then ot.dc_trans_amount else 0 end as METROBANK_DEBIT
from ".TB_PREF."sales_debit_credit as ot 
WHERE (ot.dc_transaction_date>='$date_from' and ot.dc_transaction_date<='$date_to') and ot.paid='0'";
//display_error($sql);
return $sql;
}

function insert_selected_to_gl($approve_id,$c_id_str,$p_ref_id,$gl_wtax_account,$gl_mfee_account,$card_tender_type) {
		$sqldc="select * from ".TB_PREF."acquiring_deductions where dc_id IN ($c_id_str)";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc)){
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
	 //add_gl_trans(ST_CREDITDEBITDEPOSIT, $p_ref_id, sql2date($date_paid), $gl_bank_account, 0, 0, $memo, $p_net_total, null, 0);
	

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
?>