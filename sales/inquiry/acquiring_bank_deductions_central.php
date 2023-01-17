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
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/acquiring_bank_deduction_db_central.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/admin/db/voiding_db.inc");
// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $start = $time;
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Credit Card/Debit Card Reconciliation", false, false, "", $js);
//------------------------------------------------------------------------------------------------

$approve_id = find_submit('approve_acquiring');
$selected_id = find_submit('selected_id');
$selected_id2 = find_submit('selected_id2');
$selected_id3 = find_submit('selected_id3');
$for_op_id = find_submit('_for_op');
$check_all_op_id = find_submit('_check_all_op');

// if ($_POST['br_code']!=''){
// $connect_to=$_POST['br_code'];
// //display_error($connect_to);
// set_global_connection_branch_mysql($connect_to);
// }


if (isset($_POST['RefreshInquiry']) OR $for_op_id != -1 OR $check_all_op_id != -1){
				
				global $Ajax,$db_connections;
				$prefixx = 'selected_id3';
				$selected_branches = array();
				
				//display_error($prefixx);

				foreach($_POST as $postkey=>$postval)
				{
					//display_error(strpos($postkey, $prefixx));
					
					if (strpos($postkey, $prefixx) === 0)
					{
						$id = substr($postkey, strlen($prefixx));
						$selected_branches[] = $id;
						
					}
				}
				
				//display_error($selected_branches);
}


start_form();

start_table();
	$sql = "SELECT * from transfers.0_branches order by name";
	//display_error($sql);
	$result=db_query($sql);
	$branch_count = 0;
	while($row = db_fetch($result))
	{
     	check_row("<b>".$row['name']."</b>",'selected_id3'.$row['id'],null,false, '', "align='center'");
		$branch_count ++;
	}
end_table();
	
	br();
	
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	
	//get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
	date_cells(_("Date:"), 'date_', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null);
	acquiring_bank_list_cells(' Acquiring Bank:', 'acquiring_bank_id',null);
    yesno_list_cells(_("Card Type:"), 'card_tender_type', '',_("Credit"), _("Debit"));
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);

$date_from =date2sql($_POST['date_']);
$date_to = date2sql($_POST['TransToDate']);
$acquiring_bank_id=$_POST['acquiring_bank_id'];
$selected_bank=get_selected_acquiring_banks($acquiring_bank_id);

if ($_POST['card_tender_type']=='0') {
$card_tender_type='014';
$bank_header=$selected_bank." Debit";
}
else {
$card_tender_type='013';
$bank_header=$selected_bank." Credit";
}

if (isset($_POST['activate_insertion'])) {
header("Location: ../inquiry/insert_double_payment.php");
}

function for_o_update($id,$tag,$branch_db)
{
	$sql = "UPDATE $branch_db.".TB_PREF."sales_debit_credit SET checked = $tag
				WHERE dc_id = $id";
			//display_error($sql);
	db_query($sql,'failed to update CV for online payment');
}


if ($selected_id != -1) {
global $Ajax;
	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'selected_id') === 0)
		{
		$id = substr($postkey, strlen('selected_id'));
		$id_ = explode(',', $id);
		//display_error("t_net_amount==>".$_POST['t_net_amount'.$id_[0]]);
		$_POST['sub_amount']+=$_POST['t_net_amount'.$id_[0]];
		//display_error($_POST['sub_amount']);
		}
	}
$Ajax->activate('sub_amount'); 
}

$for_op_id = find_submit('_for_op');
if ($for_op_id != -1)
{
	global $Ajax;
	set_time_limit(0);

	$prefix = 'for_op';
	$c_idx= array();
	
	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'for_op') === 0)
		{
		$id = substr($postkey, strlen('for_op'));
		$id_ = explode(',', $id);
	//	display_error("t_net_amount==>".$_POST['t_net_amount'.$id_[0]]);
		$_POST['sub_amount']+=$_POST['t_net_amount'.$id_[0]];

		$c_idx[] = $id;
		
		}
	}
	
	// foreach ($c_idx as $approve_idx){
		// display_error($approve_idx);
		// $br_dbx=$_POST['br_db'.$approve_idx];
	// }

	for_o_update($for_op_id,check_value('for_op'.$for_op_id),$_POST['br_db'.$for_op_id]);
			
	//display_error($_POST['br_db'.$for_op_id]);
	
	//tagging 
	
	set_focus('for_op'.$for_op_id);
	
	$Ajax->activate('sub_amount'); 
}



// $check_all_op_id = find_submit('_check_all_op');
if ($check_all_op_id != -1)
{
	global $Ajax;
	set_time_limit(0);
	//tagging 
	
	$prefix = 'for_check_all_op';
	foreach($_POST as $postkey=>$postval)
    {
		if (strpos($postkey, $prefix) === 0)
		{
			$id = substr($postkey, strlen($prefix));
			
				$id_ = explode(',', $id);
				//display_error("t_net_amount==>".$_POST['t_net_amount'.$id_[0]]);
				$_POST['sub_amount']+=$_POST['t_net_amount'.$id_[0]];
			
			for_o_update($id,check_value('check_all_op100'),$_POST['br_db'.$id_[0]]);
		}
	}
	$Ajax->activate('table_');
	$Ajax->activate('sub_amount'); 
}


										//ACTIONS UPON APPROVAL.
										if (isset($_POST['approve_selected']))
										{	
											global $Ajax,$db_connections;									
											set_time_limit(0);
											
											//check if TID is empty
											$terminal_id=$_POST['terminal_id'];
											if ($terminal_id == '')
											{
												display_error(_("Merchant ID cannot be empty."));
												set_focus('terminal_id');
												return false;
											}
											
											//$prefix = 'selected_id';
											$prefix = 'for_op';
											$c_ids = array();
											
											foreach($_POST as $postkey=>$postval)
											{
												if (strpos($postkey, $prefix) === 0) {
												$id = substr($postkey, strlen($prefix));
												$c_ids[] = $id;
												}
											}

											if (count($c_ids) > 0) {
												
											begin_transaction();
											
											//$c_id_str = implode(',',$c_ids);
											
											
											//=======start of checking before continue===============================
											$br_codes=array();
											//check if INVOICE# is emtpy.
											foreach ($c_ids as $approve_id)
											{	
												$remarks=$_POST['remarks'.$approve_id];
												$approval_num=$_POST['approval_num'.$approve_id];
												$receivable_amount=$_POST['receivable_amount'.$approve_id];
												$deposited_amount=$_POST['deposited_amount'.$approve_id];
												$br_db=$_POST['br_db'.$approve_id];
												$br_code=$_POST['br_code'.$approve_id];
												$br_name=$_POST['br_name'.$approve_id];
												
												
												// display_error($approve_id);
												// display_error($approval_num);
												// display_error($receivable_amount);
												// display_error($deposited_amount);
												// display_error($br_db);
												// display_error($br_code);
												// display_error($br_name);
												
												
												if (($approval_num== '') or ($deposited_amount=='')) {
												display_error(_("Invoice# cannot be empty."));
												set_focus('approval_num'.$approve_id);
												return false;
												}
												
												
												
												if (!in_array($br_code, $br_codes))
												{
												$br_codes[]=$br_code;
												}
											}
											//=======end of checking before continue===========================

										
				
											//========================to record transaction per branch
											foreach ($br_codes as $b_ids)
											{
												$br_trans_no=array();
												

												//display_error($b_ids);
												
															switch($b_ids){
													
															case 'srsn':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
																		break;
															case 'sri':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
																		break;
															case 'srsnav':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
																		break;
															case 'srst':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
																		break;
															case 'srsc':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
																		break;
															case 'srsant1':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
																		break;
															case 'srsant2':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
																		break;
															case 'srsm':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
																		break;
															case 'srsmr':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
																		break;
															case 'srsg':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
																		break;
															case 'srscain':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
																		break;
															case 'srsval':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
																		break;			
															case 'srspun':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
																		break;								
															case 'srsbsl':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
																		break;			
															case 'srspat':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
																		break;								
															case 'srscom':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["comembo_connection"];
																		break;
															case 'srstu':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["talonuno_connection"];
																		break;
															case 'srscain2':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta2_connection"];
																		break;
															case 'srssanp':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["sanpedro_connection"];
																		break;		
															case 'srsal':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["alaminos_connection"];
																		break;		
															case 'srsbgb':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bagumbong_connection"];
																		break;
															case 'srsgv':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["graceville_connection"];
																		break;																			
															case 'srsmol':
																		$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["molino_connection"];
																		break;	
																								case 'srsman':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manggahan_connection"];
									break;
						case 'srsmon':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["montalban_connection"];
									break;
														}
												
												
												set_global_connection_branch_mysql($connect_to);
												
												$p_ref_id=next_type_no();
												if ($p_ref_id==null) {
												$p_ref_id=1;
												}
												
												//die();
												
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

													
													foreach ($c_ids as $approve_id)
													{
															$branch_aria_db=$_POST['br_db'.$approve_id];
															$br_c=$_POST['br_code'.$approve_id];
															$branch_name=$_POST['br_name'.$approve_id];
															
														if($b_ids==$br_c){
															
															$br_trans_no[]=$approve_id;
															
															//DB SELECTION OF CARD TRANSACTION
															$sql=get_all_credit_debit_trans($date_from,$date_to,$br_c,$branch_name,$branch_aria_db);
															$sql.=" and ot.dc_id='$approve_id'";
															if ($card_tender_type!='') {
															$sql.=" and (ot.dc_tender_type='$card_tender_type')";
															}
															if($selected_bank!='') {
															$sql.=" and ot.dc_card_desc like '$selected_bank%'";
															}
															$sql.=" order by ot.dc_transaction_date,ot.dc_id asc";
															$result1=db_query($sql);
															//display_error($sql);
															
															while($row = db_fetch($result1))
															{
																$remarks=$_POST['remarks'.$approve_id];
																$approval_num=$_POST['approval_num'.$approve_id];
																$receivable_amount=$_POST['receivable_amount'.$approve_id];
																$deposited_amount=$_POST['deposited_amount'.$approve_id];
															
															
															//MYSQL QUERY AND DATA
																$mysql = "select * from  ".TB_PREF."acquiring_banks where acquiring_bank='$selected_bank'";
																$res1 = db_query($mysql);
																//display_error($mysql);
																while($myrow = db_fetch($res1))
																{
																	$gl_bank_account=$myrow['gl_bank_account'];
																	$gl_bank_account=$myrow['gl_bank_debit_account'];
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
																
																$tendertype= $row['TENDER'];
																$charge_back=$row['CHARGE_BACK'];
																

																if ($tendertype=='013') {
																$fee=$cc_fee; 
																$wt=$cc_wt;

																if ($charge_back=='1') {
																$wt=$cc_wt;
																}
																}
																
																if ($tendertype=='014') {
																$fee=$dc_fee; 
																$wt=0;

																if ($charge_back=='1') {
																$wt=0;
																}
																}
															
														
																if ($selected_bank=='' or $selected_bank!=$a_b) {
																$fee=0; 
																}
															
																$id=$row['ID'];
																$transdate=$row['TRANS_DATE'];
																$remitdate=$row['SUMM_DATE'];
																$transno=$row['TRANSNO'];
																$card_desc=$row['CARD_DESC'];
																$accountno=$row['ACCOUNTNO'];
																//$approvalno=$row['APPROVALNO'];
																$over_payment=$row['OVER_PAYMENT'];
															
																$mdr=round(($deposited_amount * $fee) / 100,2);
																$cwt=round(($deposited_amount * $wt) / 100,2);
																$net=$deposited_amount-($cwt+$mdr);
																}

															$date_paid=date2sql($_POST['date_paid']);
															//display_error($date_paid);
															$terminal_id=$_POST['terminal_id'];
															//display_error($terminal_id);
															approve_acquiring($approve_id,$date_paid);
															
															insert_approve_acquiring($over_payment,$charge_back,$approve_id,$p_ref_id,$transno,$remarks,$terminal_id,$transdate,$remitdate,$tendertype,$card_desc,
															$accountno,$approval_num,$receivable_amount,$deposited_amount,$fee,$mdr,$wt,$cwt,$net,$date_paid);
															
															$sql = "UPDATE ".TB_PREF."sales_debit_credit SET 
															checked ='2' WHERE dc_id = ". $approve_id;
															db_query($sql);
														}	
	
													}

													$c_id_str = implode(',',$br_trans_no);													
													
													insert_selected_to_gl($approve_id,$c_id_str,$p_ref_id,$gl_wtax_account,$gl_mfee_account,$card_tender_type);
													
													commit_transaction();

													display_notification(_('Selected transaction has been succesfuly approved.'));
													
													$_POST['sub_amount']='';
													$_POST['terminal_id']='';
													
													$Ajax->activate('table_');
													$Ajax->activate('terminal_id');
													// return true;

											}
										
												
										set_global_connection_branch();//go back to default connection.
									}
										else {
										display_error('No Item Selected, Please select atleast one (1) to process approval.');
										}
									}

																	
										
if ((($selected_bank!='') and ($card_tender_type!='')) or ($_GET['tran_date']!='')) 
{

	
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
//display_error($_POST['from_loc']);

							
//display_error($selected_bank);
//display_error($card_tender_type);
display_heading("$bank_header Summary from ".$_POST['date_']."  to  ".$_POST['TransToDate']);
br();
br();
start_table();
date_row('Date Approved:','date_paid');
//text_row('Merchant ID:','terminal_id',$_POST['terminal_id'],16);
get_all_merchant_list_cells(' Merchant ID:', 'terminal_id',null);
//$_POST['charge_back']
end_table();
br();
div_start('table_');
//start of table display
start_table($table_style2);
$th = array(' ','Branch',_("Transaction Date"), 'Invoice#', $bank_header,'Merchant Fee (%)','Merchant Fee Amt.','CWTax (%)','CWTax Amount', 'Net Amount','Remarks');
array_push($th, 'Check all<br>'.checkbox('', 'check_all_op100', null, true, false));
table_header($th);

//DB SELECTION OF CARD TRANSACTION
if ($_GET['tran_date']!='') {
$date_from=$_GET['tran_date'];
$date_to=$_GET['tran_date'];
$selected_bank=$_GET['selected_bank'];
}



	$branches_id=implode($selected_branches,",");

	
	$sqlx = "SELECT * from transfers.0_branches where id IN ($branches_id)";
	//display_error($sqlx);
	$result=db_query($sqlx);
	$count=mysql_num_rows($result);
	$data=0;
	//display_error($count);
	
	$branch_count = 0;
	while($rowe = db_fetch($result))
	{
		$br_c=$rowe['code'];
		$branch_name=$rowe['name'];
		$branch_aria_db=$rowe['aria_db'];
		
		$sqlxx.=get_all_credit_debit_trans($date_from,$date_to,$br_c,$branch_name,$branch_aria_db);
	
		if ($card_tender_type!='') {
		$sqlxx.=" and (ot.dc_tender_type='$card_tender_type')";
		}
		if($selected_bank!='') {
		$sqlxx.=" and ot.dc_card_desc like '$selected_bank%'";
		}
		
		$data++;
		
		if ($data<$count){
			$sqlxx.=" UNION ALL ";
		}
		
		//display_error($sqlxx);
	}
	
		//$sqlxx.=" order by ot.dc_transaction_date,ot.dc_card_desc,ot.dc_approval_no asc";

	//display_error($sqlxx);

				


$result=db_query($sqlxx);

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
	//$dc_wt=$myrow['dc_withholding_tax_percent'];
	
	$a_b=$myrow['acquiring_bank'];
	//$iv=$myrow['input_vat'];
	//$ov_fee=$myrow['output_vat'];
	$a_b=$acquiring_bank;
	}
	
	// //to set withholding tax and merchant fee
	// if ($tendertype=='013') {
	// $fee=$cc_fee; 
	// $wt=$cc_wt;
	// }
	
	// if ($tendertype=='014') {  
	// $fee=$dc_fee;
	// $wt=0;
	// } 
	// //

	// if ($acquiring_bank=='' or $acquiring_bank!=$a_b) {
	// $fee=0; 
	// }
	
											$charge_back=$row['CHARGE_BACK'];
											
											//to set withholding tax and merchant fee
											if ($tendertype=='013') {
											$fee=$cc_fee; 
											$wt=$cc_wt;

											if ($charge_back=='1') {
											$wt=$cc_wt;
											}
											}
											
											if ($tendertype=='014') {
											$fee=$dc_fee; 
											$wt=0;

											if ($charge_back=='1') {
											$wt=0;
											}
											}

											//
											if ($selected_bank=='' or $selected_bank!=$a_b) {
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

	label_cell($c,'align=right');
	
	label_cell($row['br_name']);
	
	if ($charge_back==1) {
	label_cell(sql2date($row['TRANS_DATE'])." <font color=red> CB</font>");
	}
	else {
	label_cell(sql2date($row['TRANS_DATE']));
	}
	//label_cell($row['APPROVALNO']);
	text_cells('',approval_num.$row['ID'],$row['APPROVALNO'],11);
	hidden('tender_type'.$row['ID'],$tendertype);

	if (($selected_bank=='BPI' or $selected_bank=='bpi') and ($card_tender_type=='013')) {
	//amount_cell($row['BPI_CREDIT']);
	text_cells(null, 'deposited_amount'.$row['ID'],$row['BPI_CREDIT'], 11, 20);
	hidden('receivable_amount'.$row['ID'],$row['BPI_CREDIT']);
	
	$mdr=round(($row['BPI_CREDIT'] * $fee) / 100,2);
	$cwt=round(($row['BPI_CREDIT'] * $wt) / 100,2);
	$net=$row['BPI_CREDIT']-($cwt+$mdr);
	if ($charge_back!=1) {
	$total_bpi_credit+=$row['BPI_CREDIT'];
	}
	}
	if (($selected_bank=='BPI' or $selected_bank=='bpi') and ($card_tender_type=='014')) {
	//amount_cell($row['BPI_DEBIT']);
	text_cells(null, 'deposited_amount'.$row['ID'],$row['BPI_DEBIT'], 11, 20);
	hidden('receivable_amount'.$row['ID'],$row['BPI_DEBIT'], 11, 20);
	
	$mdr=round(($row['BPI_DEBIT'] * $fee) / 100,2);
	$cwt=round(($row['BPI_DEBIT'] * $wt) / 100,2);
	$net=$row['BPI_DEBIT']-($cwt+$mdr);
	if ($charge_back!=1) {
	$total_bpi_debit+=$row['BPI_DEBIT'];
	}
	}
		
	if (($selected_bank=='PNB' or $selected_bank=='pnb') and ($card_tender_type=='013')) {
	//amount_cell($row['PNB_CREDIT']);
	text_cells(null, 'deposited_amount'.$row['ID'],$row['PNB_CREDIT'], 11, 20);
	hidden('receivable_amount'.$row['ID'],$row['PNB_CREDIT']);
	
	$mdr=round(($row['PNB_CREDIT'] * $fee) / 100,2);
	$cwt=round(($row['PNB_CREDIT'] * $wt) / 100,2);
	$net=$row['PNB_CREDIT']-($cwt+$mdr);
	if ($charge_back!=1) {
	$total_pnb_credit+=$row['PNB_CREDIT'];
	}
	}
	if (($selected_bank=='PNB' or $selected_bank=='pnb') and ($card_tender_type=='014')) {
	//amount_cell($row['PNB_DEBIT']);
	text_cells(null, 'deposited_amount'.$row['ID'],$row['PNB_DEBIT'], 11, 20);
	hidden('receivable_amount'.$row['ID'],$row['PNB_DEBIT']);
	
	$mdr=round(($row['PNB_DEBIT'] * $fee) / 100,2);
	$cwt=round(($row['PNB_DEBIT'] * $wt) / 100,2);
	$net=$row['PNB_DEBIT']-($cwt+$mdr);
	if ($charge_back!=1) {
	$total_pnb_debit+=$row['PNB_DEBIT'];
	}
	}
	if (($selected_bank=='METROBANK' or $selected_bank=='metrobank') and ($card_tender_type=='013')) {
	//amount_cell($row['METROBANK_CREDIT']);
	text_cells(null, 'deposited_amount'.$row['ID'],$row['METROBANK_CREDIT'], 11, 20);
	hidden('receivable_amount'.$row['ID'],$row['METROBANK_CREDIT']);
	$mdr=round(($row['METROBANK_CREDIT'] * $fee) / 100,2);
	$cwt=round(($row['METROBANK_CREDIT'] * $wt) / 100,2);
	$net=$row['METROBANK_CREDIT']-($cwt+$mdr);
	if ($charge_back!=1) {
	$total_metrobank_credit+=$row['METROBANK_CREDIT'];
	}
	}
	if (($selected_bank=='METROBANK' or $selected_bank=='metrobank') and ($card_tender_type=='014')) {
	text_cells(null, 'deposited_amount'.$row['ID'],$row['METROBANK_DEBIT'], 11, 20);
	hidden('receivable_amount'.$row['ID'],$row['METROBANK_DEBIT']);
	$mdr=round(($row['METROBANK_DEBIT'] * $fee) / 100,2);
	$cwt=round(($row['METROBANK_DEBIT'] * $wt) / 100,2);
	$net=$row['METROBANK_DEBIT']-($cwt+$mdr);
	if ($charge_back!=1) {
	$total_metrobank_debit+=$row['METROBANK_DEBIT'];
	}
	}
	
	label_cell($fee);
	//text_cells('',$mdr=round(number_format($amount * $fee / 100,2),2));
	text_cells(null, 'mdr'.$row['ID'],round($mdr,2), 10, 20);
	label_cell($wt);
	text_cells(null, 'cwt'.$row['ID'], round($cwt,2), 10, 20);
	//amount_cell($net=$amount-($cwt+$mdr));
	hidden('t_charge_back'.$row['ID'],$row['CHARGE_BACK']);
	text_cells(null, 't_net_amount'.$row['ID'],$net, 11, 20);
	//hidden('t_net_amount'.$row['ID'],$net);
	text_cells('',remarks.$row['ID'],'',11);
	//$submit='approve_acquiring'.$row['ID'];
	//submit_cells($submit, 'Approve', "align=center", true, true,'ok.gif');
	
	//$selected='selected_id'.$row['ID'];
	//check_cells('',$selected,'',true);
	
		if ($row['checked'] == 1)
		{
			$_POST['for_op'.$row['ID']] = 1;
		}
		else
		{
			unset($_POST['for_op'.$row['ID']]);
		}
	
	check_cells('','for_op'.$row['ID'],null,true, '', "align='center'");
	hidden('for_check_all_op'.$row['ID'],$row['ID']);
	
	hidden('br_db'.$row['ID'],$row['br_db']);
	hidden('br_code'.$row['ID'],$row['br_code']);
	hidden('br_name'.$row['ID'],$row['br_name']);
	
	end_row();

	if ($charge_back!=1) {
	$total_merchant_fee+=$mdr;
	$total_wt+=$cwt;
	$net_amount+=$net;
	}
}
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell('');
	if (($selected_bank=='BPI' or $selected_bank=='bpi') and ($card_tender_type=='013')){
	label_cell("<font color=#880000><b>".number_format2(abs($total_bpi_credit),2)."<b></font>",'align=right');
	}
	if (($selected_bank=='BPI' or $selected_bank=='bpi') and ($card_tender_type=='014')){
	label_cell("<font color=#880000><b>".number_format2(abs($total_bpi_debit),2)."<b></font>",'align=right');
	}
	if (($selected_bank=='PNB' or $selected_bank=='pnb') and ($card_tender_type=='013')){
	label_cell("<font color=#880000><b>".number_format2(abs($total_pnb_credit),2)."<b></font>",'align=right');
	}
	if (($selected_bank=='PNB' or $selected_bank=='pnb') and ($card_tender_type=='014')){
	label_cell("<font color=#880000><b>".number_format2(abs($total_pnb_debit),2)."<b></font>",'align=right');
	}
	if (($selected_bank=='METROBANK' or $selected_bank=='metrobank') and ($card_tender_type=='013')){
	label_cell("<font color=#880000><b>".number_format2(abs($total_metrobank_credit),2)."<b></font>",'align=right');
	}
	if (($selected_bank=='METROBANK' or $selected_bank=='metrobank') and ($card_tender_type=='014')){
	label_cell("<font color=#880000><b>".number_format2(abs($total_metrobank_debit),2)."<b></font>",'align=right');
	}
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($total_merchant_fee),2)."<b></font>",'align=right');
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($total_wt),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($net_amount),2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	//label_cell("<font color=#880000><b>".number_format2(abs($_POST['sub_amount']),2)."<b></font>",'align=right','sub_amount');
	//label_cell($_POST['sub_amount'],'','sub_amount');
	end_row();	
end_table(1);
br();
start_table();
label_cell('<b>Total Net Amount Selected: </b>');
label_cell("<font color=#880000><b>".number_format2(abs($_POST['sub_amount']),2)."<b></font>",'align=right','sub_amount');
end_table();
br();
br();
start_table();
submit_cells('approve_selected', 'Approve Selected', "align=center", true, true,'ok.gif');
submit_cells('activate_insertion', 'Add Over Payment', "align=center", true, false,'ok.gif');
end_table();
div_end();
}

end_form();
//NOTE: merhant id = terminal id, approval no = invoice no
end_page();
?>