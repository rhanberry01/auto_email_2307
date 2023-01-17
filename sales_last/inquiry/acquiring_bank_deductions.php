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
if (isset($_GET['xls']) AND isset($_GET['filename']) AND isset($_GET['unique']))
{
	$path_to_root = "../..";
	include_once($path_to_root . "/includes/session.inc");
	
	header('Content-Disposition: attachment; filename='.$_GET['filename']);

	$target = $path_to_root.'/company/'.$_SESSION['wa_current_user']->company.'/pdf_files/'.$_GET['unique'];
	readfile($target);
	
	exit;
}

$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");


	//start of excel report
if(isset($_POST['dl_excel']))
{
	cashier_summary_per_day_excel();
	exit;
}
// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $start = $time;

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Credit Card/Debit Card Reconciliation", false, false, "", $js);
//------------------------------------------------------------------------------------------------
$approve_id = find_submit('approve_acquiring');
// $approve_id = find_submit('approve_acquiring');

start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	date_cells(_("Date:"), 'date_', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);

$date_from =date2sql($_POST['date_']);
$date_to = date2sql($_POST['TransToDate']);
display_heading("Summary from ".$_POST['date_']."  to  ".$_POST['TransToDate']);
br();
br();

if ($approve_id != -1)
{
$sql="
select ot.dc_remittance_date as SUMM_DATE,ot.dc_transaction_date as TRANS_DATE,
ot.dc_id as ID,ot.dc_tender_type as TENDER,ot.dc_trans_no as TRANSNO, ot.dc_card_desc as CARD_DESC, ot.dc_account_no as ACCOUNTNO,ot.dc_approval_no as APPROVALNO,
case when ot.dc_tender_type='013' and  (ot.dc_card_desc like 'BPI%' or ot.dc_card_desc='BPI' or ot.dc_card_desc='bpi') then ot.dc_trans_amount else 0 end as BPI_CREDIT,
case when ot.dc_tender_type='014' and  (ot.dc_card_desc like 'BPI%' or ot.dc_card_desc='BPI' or ot.dc_card_desc='bpi') then ot.dc_trans_amount else 0 end as BPI_DEBIT,
case when ot.dc_tender_type='013' and  (ot.dc_card_desc like 'PNB%' or ot.dc_card_desc='PNB' or ot.dc_card_desc='pnb') then ot.dc_trans_amount else 0 end as PNB_CREDIT,
case when ot.dc_tender_type='014' and  (ot.dc_card_desc like 'PNB%' or ot.dc_card_desc='PNB' or ot.dc_card_desc='pnb') then ot.dc_trans_amount else 0 end as PNB_DEBIT,
case when ot.dc_tender_type='013' and  (ot.dc_card_desc like 'METROBANK%' or ot.dc_card_desc='METROBANK' or ot.dc_card_desc='metrobank') then ot.dc_trans_amount else 0 end as METROBANK_CREDIT,
case when ot.dc_tender_type='014' and  (ot.dc_card_desc like 'METROBANK%' or ot.dc_card_desc='METROBANK' or ot.dc_card_desc='metrobank') then ot.dc_trans_amount else 0 end as METROBANK_DEBIT
from ".TB_PREF."sales_debit_credit as ot 
WHERE (ot.dc_transaction_date>='$date_from' and ot.dc_transaction_date<='$date_to')and (ot.dc_tender_type='013' or ot.dc_tender_type='014')  and ot.paid='0' and ot.dc_id='$approve_id' order by ot.dc_transaction_date,ot.dc_id asc";
$result1=db_query($sql);
//display_error($sql);
}

while($row = db_fetch($result1))
{
//BPI----
if ( (($row['CARD_DESC']=='BPI') or ($row['CARD_DESC']=='bpi')) and $row['TENDER'] =='013' )
{
$acquiring_bank=('BPI') or ('bpi');
$amount=$row['BPI_CREDIT'];
}
if ( (($row['CARD_DESC']=='BPI') or ($row['CARD_DESC']=='bpi')) and $row['TENDER'] =='014' )
{
$acquiring_bank=('BPI') or ('bpi');
$amount=$row['BPI_DEBIT'];
}
//----

//PNB----
if ( (($row['CARD_DESC']=='PNB') or ($row['CARD_DESC']=='pnb')) and $row['TENDER'] =='013' )
{
$acquiring_bank=('PNB') or ('pnb');
$amount=$row['PNB_CREDIT'];
}
if ( (($row['CARD_DESC']=='PNB') or ($row['CARD_DESC']=='pnb')) and $row['TENDER'] =='014' )
{
$acquiring_bank=('PNB') or ('pnb');
$amount=$row['PNB_DEBIT'];
}
//-----

//METROBANK----
if ( (($row['CARD_DESC']=='METROBANK') or ($row['CARD_DESC']=='metrobank')) and $row['TENDER'] =='013' )
{
$acquiring_bank=('METROBANK') or ('metrobank');
$amount=$row['METROBANK_CREDIT'];
}
if ( (($row['CARD_DESC']=='METROBANK') or ($row['CARD_DESC']=='metrobank')) and $row['TENDER'] =='014' )
{
$acquiring_bank=('METROBANK') or ('metrobank');
$amount=$row['METROBANK_DEBIT'];
}
//-----

$tendertype= $row['TENDER'];

//MYSQL QUERY AND DATA
	$mysql = "select * from  ".TB_PREF."acquiring_banks where acquiring_bank='$acquiring_bank'";
	$res1 = db_query($mysql);
	while($myrow = db_fetch($res1))
	{
	$gl_bank_account=$myrow['gl_bank_account'];
	$gl_bank_account=$myrow['gl_bank_debit_account'];
	$gl_mfee_account=$myrow['gl_mfee_account'];
	$gl_wtax_account=$myrow['gl_wtax_account'];
	$cc_fee=$myrow['cc_merchant_fee_percent'];
	$dc_fee=$myrow['dc_merchant_fee_percent'];
	$cc_wt=$myrow['cc_withholding_tax_percent'];
	$dc_fee=$myrow['dc_merchant_fee_percent'];
	$a_b=$myrow['acquiring_bank'];
	//$iv=$myrow['input_vat'];
	//$ov_fee=$myrow['output_vat'];
	$a_b=$acquiring_bank;
	}
	
	//to set withholding tax and merchant fee
	if ($tendertype=='013'){
	$fee=$cc_fee; 
	$wt=$cc_wt;
	}
	
	if ($tendertype=='014') {  
	$fee=$dc_fee;
	$wt=0;
	} 
	//

	if ($acquiring_bank=='' or $acquiring_bank!=$a_b){
	$fee=0; 
	}
	
	$c ++;
	alt_table_row_color($k);
	
	$id=$row['ID'];
	$transdate=$row['TRANS_DATE'];
	$remitdate=$row['SUMM_DATE'];
	$transno=$row['TRANSNO'];
	$card_desc=$row['CARD_DESC'];
	$accountno=$row['ACCOUNTNO'];
	$approvalno=$row['APPROVALNO'];
	$mdr=round(number_format($amount * $fee / 100,2),2);
	$cwt=round(number_format($amount * $wt / 100,2),2);
	$net=$amount-($cwt+$mdr);
	$submit='approve_acquiring'.$row['ID'];
	$total_bpi_credit+=$row['BPI_CREDIT'];
	$total_bpi_debit+=$row['BPI_DEBIT'];
	$total_pnb_credit+=$row['PNB_CREDIT'];
	$total_pnb_debit+=$row['PNB_DEBIT'];
	$total_metrobank_credit+=$row['METROBANK_CREDIT'];
	$total_metrobank_debit+=$row['METROBANK_DEBIT'];
	$total_merchant_fee+=$mdr;
	$total_wt+=$cwt;
	$net_amount+=$net;
	$total_diff=$simpos_sales+abs($over)-abs($short);
	}

	
	 if ($approve_id != -1)
{
	global $Ajax;
	$date_paid=date2sql($_POST['date_paid'.$approve_id]);
	$inv_num=$_POST['inv_num'.$approve_id];
	$terminal_id=$_POST['terminal_id'.$approve_id];
	approve_acquiring($approve_id,$date_paid);
	insert_approve_acquiring($approve_id,$transno,$inv_num,$terminal_id,$transdate,$remitdate,$tendertype,$card_desc,
	$accountno,$approvalno,$amount,$fee,$mdr,$wt,$cwt,$net,$date_paid);
	
		$sqlid_details="select p_id from ".TB_PREF."acquiring_deductions order by p_id asc";
		$result_id_details=db_query($sqlid_details);
		
		while ($cash_id_det_row = db_fetch($result_id_details))
		{
		// $id_count=db_num_rows($sqlid_details);
		// if ($id_count<=1)
		// {
		$p_id=$cash_id_det_row['p_id'];
		// }
		// else {
		// $c_id=++$cash_id_det_row['ct_id'];
		// }
		}
		// $sqlcd_account="select * from ".TB_PREF."sales_gl_accounts";
		// $result_cd_account=db_query($sqlcd_account);
		// while ($cdaccountrow = db_fetch($result_cd_account))
		// {
		// $debit_account=$cdaccountrow["debit_account"];
		// $credit_account=$cdaccountrow["credit_account"];
		// }

		 $sqldc="select * from ".TB_PREF."sales_debit_credit where dc_id='$approve_id'";
		 $result_dc=db_query($sqldc);
		 while ($dc_row = db_fetch($result_dc))
		 {
		 $dctender=$dc_row["dc_tender_type"];
		 $dcamount=$dc_row["dc_trans_amount"];
		 }

		 if($dctender=='013') {
		 $account=$credit_account;
		 }
		 if($dctender=='014') {
		 $account=$debit_account;
		 }
		
		$sqldc="select * from ".TB_PREF."acquiring_deductions where dc_id='$approve_id'";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc))
		{
		$p_amount=$dc_row["p_amount"];
		$p_bank_card=$dc_row["p_bank_card"];
		$p_mfeeamount=$dc_row["p_mfeeamount"];
		$p_wtaxamount=$dc_row["p_wtaxamount"];
		$p_net_total=$dc_row["p_net_total"];
		$date_paid=$dc_row["date_paid"];
		}

if (($p_bank_card=='METROBANK') or ($p_bank_card=='metrobank'))
{
		$sqldc="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'METROBANK%' or acquiring_bank='METROBANK' or acquiring_bank='metrobank')";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc))
		{
		$gl_bank_account=$dc_row["gl_bank_account"];
		$gl_bank_debit_account=$dc_row["gl_bank_debit_account"];
		}
}

if (($p_bank_card=='BPI') or ($p_bank_card=='bpi'))
{
		$sqldc="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'BPI%' or acquiring_bank='BPI' or acquiring_bank='bpi')";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc))
		{
		$gl_bank_account=$dc_row["gl_bank_account"];
		$gl_bank_debit_account=$dc_row["gl_bank_debit_account"];
		}
}		

if (($p_bank_card=='PNB') or ($p_bank_card=='pnb'))
{
		$sqldc="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'PNB%' or acquiring_bank='PNB' or acquiring_bank='pnb')";
		$result_dc=db_query($sqldc);
		while ($dc_row = db_fetch($result_dc))
		{
		$gl_bank_account=$dc_row["gl_bank_account"];
		$gl_bank_debit_account=$dc_row["gl_bank_debit_account"];
		}
}		
	//payment
	 add_gl_trans(ST_CREDITDEBITDEPOSIT, $p_id, sql2date($date_paid), $gl_bank_account, 0, 0, $memo, $p_net_total, null, 0);
	//merchant fee
	add_gl_trans(ST_CREDITDEBITDEPOSIT, $p_id, sql2date($date_paid), $gl_mfee_account, 0, 0, $memo, $p_mfeeamount, null, 0);
	if($dctender=='013')
	{
	//withholding tax
	add_gl_trans(ST_CREDITDEBITDEPOSIT, $p_id, sql2date($date_paid), $gl_wtax_account, 0, 0, $memo, $p_wtaxamount, null, 0);
	}
	//receivable
	add_gl_trans(ST_CREDITDEBITDEPOSIT, $p_id, sql2date($date_paid), $gl_bank_debit_account, 0, 0, $memo, -$p_amount, null, 0);
	$Ajax->activate('table_');
}
	
div_start('table_');
//start of table display
start_table($table_style2);
$th = array(' ',_("TransDate"), 'Invoice#', 'T-ID',  'Approval#', 'BPI Credit', 'BPI Debit', 'PNB Credit','PNB Debit','MB Credit',
'MB Debit','MFee (%)','MFee Amt.','CWT (%)','CWT Tax', 'Net Amt.','Date Paid',"");
table_header($th);

$sql="select ot.dc_remittance_date as SUMM_DATE,ot.dc_transaction_date as TRANS_DATE,
ot.dc_id as ID,ot.dc_tender_type as TENDER,ot.dc_trans_no as TRANSNO, ot.dc_card_desc as CARD_DESC, ot.dc_account_no as ACCOUNTNO,ot.dc_approval_no as APPROVALNO,
case when ot.dc_tender_type='013' and  (ot.dc_card_desc like 'BPI%' or ot.dc_card_desc='BPI' or ot.dc_card_desc='bpi') then ot.dc_trans_amount else 0 end as BPI_CREDIT,
case when ot.dc_tender_type='014' and  (ot.dc_card_desc like 'BPI%' or ot.dc_card_desc='BPI' or ot.dc_card_desc='bpi') then ot.dc_trans_amount else 0 end as BPI_DEBIT,
case when ot.dc_tender_type='013' and  (ot.dc_card_desc like 'PNB%' or ot.dc_card_desc='PNB' or ot.dc_card_desc='pnb') then ot.dc_trans_amount else 0 end as PNB_CREDIT,
case when ot.dc_tender_type='014' and  (ot.dc_card_desc like 'PNB%' or ot.dc_card_desc='PNB' or ot.dc_card_desc='pnb') then ot.dc_trans_amount else 0 end as PNB_DEBIT,
case when ot.dc_tender_type='013' and  (ot.dc_card_desc like 'METROBANK%' or ot.dc_card_desc='METROBANK' or ot.dc_card_desc='metrobank') then ot.dc_trans_amount else 0 end as METROBANK_CREDIT,
case when ot.dc_tender_type='014' and  (ot.dc_card_desc like 'METROBANK%' or ot.dc_card_desc='METROBANK' or ot.dc_card_desc='metrobank') then ot.dc_trans_amount else 0 end as METROBANK_DEBIT
from ".TB_PREF."sales_debit_credit as ot 
WHERE (ot.dc_transaction_date>='$date_from' and ot.dc_transaction_date<='$date_to')and (ot.dc_tender_type='013' or ot.dc_tender_type='014')  and ot.paid='0' order by ot.dc_transaction_date,ot.dc_card_desc,ot.dc_approval_no  asc";

$result=db_query($sql);

$previous_card_desc = "";
$previous_date="";
while($row = db_fetch($result))
{
//BPI----
if ( (($row['CARD_DESC']=='BPI') or ($row['CARD_DESC']=='bpi')) and $row['TENDER'] =='013' ){
$acquiring_bank=('BPI') or ('bpi');
$amount=$row['BPI_CREDIT'];
}
if ( (($row['CARD_DESC']=='BPI') or ($row['CARD_DESC']=='bpi')) and $row['TENDER'] =='014' ){
$acquiring_bank=('BPI') or ('bpi');
$amount=$row['BPI_DEBIT'];
}
//----


//PNB----
if ( (($row['CARD_DESC']=='PNB') or ($row['CARD_DESC']=='pnb')) and $row['TENDER'] =='013' ){
$acquiring_bank=('PNB') or ('pnb');
$amount=$row['PNB_CREDIT'];
}
if ( (($row['CARD_DESC']=='PNB') or ($row['CARD_DESC']=='pnb')) and $row['TENDER'] =='014' ){
$acquiring_bank=('PNB') or ('pnb');
$amount=$row['PNB_DEBIT'];
}
//-----


//METROBANK----
if ( (($row['CARD_DESC']=='METROBANK') or ($row['CARD_DESC']=='metrobank')) and $row['TENDER'] =='013' ){
$acquiring_bank=('METROBANK') or ('metrobank');
$amount=$row['METROBANK_CREDIT'];
}
if ( (($row['CARD_DESC']=='METROBANK') or ($row['CARD_DESC']=='metrobank')) and $row['TENDER'] =='014' ){
$acquiring_bank=('METROBANK') or ('metrobank');
$amount=$row['METROBANK_DEBIT'];
}
//-----

$tendertype= $row['TENDER'];
//MYSQL QUERY AND DATA
	$mysql = "select * from  ".TB_PREF."acquiring_banks where acquiring_bank='$acquiring_bank'";
	$res2 = db_query($mysql);
	while($myrow = db_fetch($res2))
	{
	$cc_fee=$myrow['cc_merchant_fee_percent'];
	$dc_fee=$myrow['dc_merchant_fee_percent'];
	$cc_wt=$myrow['cc_withholding_tax_percent'];
	$dc_fee=$myrow['dc_merchant_fee_percent'];
	$a_b=$myrow['acquiring_bank'];
	//$iv=$myrow['input_vat'];
	//$ov_fee=$myrow['output_vat'];
	$a_b=$acquiring_bank;
	}
	//to set withholding tax and merchant fee
	if ($tendertype=='013') {
	$fee=$cc_fee; 
	$wt=$cc_wt;
	}
	
	if ($tendertype=='014') {  
	$fee=$dc_fee;
	$wt=0;
	} 
	//

	if ($acquiring_bank=='' or $acquiring_bank!=$a_b){
	$fee=0; 
	}
		
	$c ++;
	alt_table_row_color($k);
	$id=$row['ID'];
	$transdate=$row['TRANS_DATE'];
	$remitdate=$row['SUMM_DATE'];
	$transno=$row['TRANSNO'];
	$card_desc=$row['CARD_DESC'];
	$accountno=$row['ACCOUNTNO'];
	$approvalno=$row['APPROVALNO'];
	
	//CARD_DESC TITLE
	if($previous_card_desc!=$row['CARD_DESC']){
	alt_table_row_color($k);
	label_cell('');
	label_cell($row['TRANS_DATE']." : ".$row['CARD_DESC'],'colspan=17 class=tableheader2');
	alt_table_row_color($k);
	$previous_card_desc=$row['CARD_DESC'];
	$previous_date=$row['TRANS_DATE'];
	} //

	label_cell($c,'align=right');
	label_cell(sql2date($row['TRANS_DATE']));
	hidden($row['SUMM_DATE']);
	//label_cell('<b>'.$row['TRANSNO'].'</b>');
	hidden($row['ACCOUNTNO']);
	text_cells('',inv_num.$row['ID'],'',5);
	text_cells('',terminal_id.$row['ID'],'',5);
	label_cell($row['APPROVALNO']);
	hidden($row['CARD_DESC']);
	amount_cell($row['BPI_CREDIT']);
	amount_cell($row['BPI_DEBIT']);
	amount_cell($row['PNB_CREDIT']);
	amount_cell($row['PNB_DEBIT']);
	amount_cell($row['METROBANK_CREDIT']);
	amount_cell($row['METROBANK_DEBIT']);
	percent_cell($fee);
	amount_cell($mdr=round(number_format($amount * $fee / 100,2),2));
	amount_cell($wt);
	amount_cell($cwt=round(number_format($amount * $wt / 100,2),2));
	amount_cell($net=$amount-($cwt+$mdr));
	date_cells('',date_paid.$row['ID']);

	$submit='approve_acquiring'.$row['ID'];
	submit_cells($submit, 'Approve', "align=center", true, true,'ok.gif');
	end_row();
	
	$total_bpi_credit+=$row['BPI_CREDIT'];
	$total_bpi_debit+=$row['BPI_DEBIT'];
	$total_pnb_credit+=$row['PNB_CREDIT'];
	$total_pnb_debit+=$row['PNB_DEBIT'];
	$total_metrobank_credit+=$row['METROBANK_CREDIT'];
	$total_metrobank_debit+=$row['METROBANK_DEBIT'];
	$total_merchant_fee+=$mdr;
	$total_wt+=$cwt;
	$net_amount+=$net;
	$total_diff=$simpos_sales+abs($over)-abs($short);
}

	 if ($approve_id != -1) {
		
	}

	else {
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($total_bpi_credit),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($total_bpi_debit),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($total_pnb_credit),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($total_pnb_debit),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($total_metrobank_credit),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($total_metrobank_debit),2)."<b></font>",'align=right');
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($total_merchant_fee),2)."<b></font>",'align=right');
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($total_wt),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($net_amount),2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	end_row();
	}

end_table(1);
div_end();
end_form();
end_page();
// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>