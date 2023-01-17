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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Deposit Other Income"), false, false, "", $js);

$cleared_id = find_submit('clear_selected');

function get_gl_view_str_per_branch($br_code,$type, $trans_no, $label="", $force=false, $class='', $id='',$icon=true)
{
	global $db_connections;
	//display_error($br_code);
	// switch($br_code){
						// case 'srsn':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
									// break;
						// case 'sri':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
									// break;
						// case 'srsnav':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
									// break;
						// case 'srst':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
									// break;
						// case 'srsc':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
									// break;
						// case 'srsant1':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
									// break;
						// case 'srsant2':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
									// break;
						// case 'srsm':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
									// break;
						// case 'srsmr':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
									// break;
						// case 'srsg':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
									// break;
						// case 'srscain':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
									// break;
						// case 'srsval':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
									// break;			
						// case 'srspun':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
									// break;								
						// case 'srsbsl':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
									// break;			
						// case 'srspat':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
									// break;	
						// case 'srscom':
									// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["comembo_connection"];
									// break;											
		// }

		// //display_error($connect_to);
// //set_global_connection_branch($connect_to);

$connect_to=get_connection_to_branch($br_code);
	
	if (!$force && !user_show_gl_info())
		return "";

	$icon = false;
	if ($label == "")
	{
		$label = _("GL");
		$icon = ICON_GL;
	}	
		//	set_global_connection_branch();
	
	return viewer_link($label, 
		"gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no&branch=$connect_to", 
		$class, $id, $icon);
		
		set_global_connection_branch();
		
}


function for_o_update($id,$tag)
{
	$sql = "UPDATE ".TB_PREF."other_income_payment_header SET checked = '$tag'
				WHERE bd_trans_no = '$id'";
				//display_error($sql);
	db_query($sql,'failed to update checking.');
}

function update_other_income_payment_header($cleared_id,$date_paid,$date_cleared,$bank_id,$remarks,$payto)	
{		
	 $sql = "UPDATE ".TB_PREF."other_income_payment_header 
	 SET bd_cleared = '0',
	 bd_payment_to_bank='$bank_id',
	 bd_date_deposited='0000-00-00',
	 bd_date_cleared='0000-00-00',
	 checked='2'
	WHERE bd_trans_no = '$cleared_id'";

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

function update_cash_dep_header($cleared_id,$date_paid,$date_cleared,$bank_account_code,$remarks,$payto,$bank_id)	
{		
	 $sql = "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
	 SET cd_cleared = '0',
	 cd_bank_account_code='$bank_account_code',
	 cd_bank_deposited='$bank_id',
	 cd_date_deposited='0000-00-00',
	 cd_date_cleared='0000-00-00'
	WHERE cd_id = '$cleared_id'";
db_query($sql);
//display_error($sql);
}

start_form();
//====================================if cleared_id======================================
$for_op_id = find_submit('_for_op');
if ($for_op_id != -1)
{
	$br_code2=$_POST['from_loc2'];

	$x=switch_connection_to_branch_mysql($br_code2);
	// display_error($x);
	// display_error($br_code2);
	
	global $Ajax;
	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'for_op') === 0)
		{
		$id = substr($postkey, strlen('for_op'));
		$id_ = explode(',', $id);
	//	display_error("bd_amount==>".$_POST['bd_amount'.$id_[0]]);
		$_POST['sub_amount']+=$_POST['bd_amount'.$id_[0]];
		}
	}
	
	//tagging 
	for_o_update($for_op_id,check_value('for_op'.$for_op_id));
	set_focus('for_op'.$for_op_id);
	
	$Ajax->activate('sub_amount'); 
	$Ajax->activate('t_amount'); 
	//$Ajax->activate('table_'); 
}

$check_all_op_id = find_submit('_check_all_op');
if ($check_all_op_id != -1)
{
	$br_code2=$_POST['from_loc2'];

	$x=switch_connection_to_branch_mysql($br_code2);
	// display_error($x);
	// display_error($br_code2);

	
	global $Ajax;
	//tagging 
	
	$prefix = 'for_check_all_op';
	foreach($_POST as $postkey=>$postval)
    {
		if (strpos($postkey, $prefix) === 0)
		{
			$id = substr($postkey, strlen($prefix));
			
				$id_ = explode(',', $id);
				//display_error("bd_amount==>".$_POST['bd_amount'.$id_[0]]);
				$_POST['sub_amount']+=$_POST['bd_amount'.$id_[0]];
			
			for_o_update($id,check_value('check_all_op100'));
		}
	}

	$Ajax->activate('sub_amount'); 
	$Ajax->activate('t_amount'); 
	$Ajax->activate('table_'); 
}

										//ACTIONS UPON APPROVAL.
										if (isset($_POST['approve_selected']))
										{
											$bank_account=$_POST['bank_account'];
											$tender_type=$_POST['tender_type'];
											
											$br_code2=$_POST['from_loc2'];
											
											$x=switch_connection_to_branch_mysql($br_code2);
											// display_error($x);
											// display_error($br_code2);
											
											if ($bank_account == '' or $bank_account == '0')
											{
											display_error(_("Please Select Bank Account."));
											set_focus('bank_account');
											return false;
											}
											
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
												$c_id_str = implode(',',$c_ids);
												$trans_nos=$c_id_str;
												//display_error($c_id_str);

												$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
												$trans_type=ST_BANKDEPOSIT;
												$bank_account_code=$_POST['bank_account'];
												$memo_=$_POST['memo_'];
												
												$sql_cib="select id,account_code from ".TB_PREF."bank_accounts where account_code='$bank_account_code'";
												//display_error($sql_cib);
												$result_cib=db_query($sql_cib);

												while ($accountrow = db_fetch($result_cib))
												{
													$bank_id=$accountrow['id'];
													$cash_in_bank=$accountrow['account_code'];
												}

												$cash_in_bank=$bank_account_code;		
												
												
												
												if($_POST['deposit_type']==0){
														foreach ($c_ids as $approve_id)
														{
														for_o_update($approve_id,2);
														update_other_income_payment_header($approve_id,date2sql($_POST['date_deposit']),date2sql(Today()),$bank_id,$remarks,$payto);

														$bd_trans_date=$_POST['bd_trans_date'.$approve_id];
														}

														$id=add_cash_dep_header($myBranchCode,$tender_type,sql2date($bd_trans_date),$trans_type,$trans_nos,$_POST['t_amount'],$desc=1,$memo_,$payee_name);
														update_cash_dep_header($id,date2sql($_POST['date_deposit']),date2sql($_POST['date_deposit']),$bank_account_code,$memo_,$payto,$bank_id);
												}
												else{
																				
														foreach ($c_ids as $approve_id)
														{
														for_o_update($approve_id,2);
														update_other_income_payment_header($approve_id,date2sql($_POST['date_deposit']),date2sql(Today()),$bank_id,$remarks,$payto);

														$bd_trans_date=$_POST['bd_trans_date'.$approve_id];
														$bd_amount=$_POST['bd_amount'.$approve_id];
														
														$id=add_cash_dep_header($myBranchCode,$tender_type,sql2date($bd_trans_date),$trans_type,$approve_id,$bd_amount,$desc=1,$memo_,$payee_name);
														update_cash_dep_header($id,date2sql($_POST['date_deposit']),date2sql($_POST['date_deposit']),$bank_account_code,$memo_,$payto,$bank_id);
														}
												}
												

												display_notification(_('Selected transaction has been succesfuly deposited.'));
											}
											
											else {
												display_error('No Item Selected, Please select atleast one (1) to process approval.');
											}
											
																					
											$_POST['bank_account']='';
											$_POST['t_amount']='';
											$_POST['memo_']='';
										
											$Ajax->activate('bank_account');
											$Ajax->activate('t_amount');
											$Ajax->activate('memo_');
											$Ajax->activate('table_');
										}

//=======================================================================
div_start('table_');

// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');

global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_outer_table("width=90% $table_style2"); // outer table

	table_section(1);
	
	if (!isset($_POST['PayType']))
	{
		if (isset($_GET['PayType']))
			$_POST['PayType'] = $_GET['PayType'];
		else
			$_POST['PayType'] = "";
	}
	if (!isset($_POST['person_id']))
	{
		if (isset($_GET['PayPerson']))
			$_POST['person_id'] = $_GET['PayPerson'];
		else
			$_POST['person_id'] = "";
	}
	if (isset($_POST['_PayType_update'])) {
		$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
	}

	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
		br();
    	//	text_row_ex($payment ?_("To the Order of:"):_("Name:"),'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, false, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, false, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, false, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;
    }
	table_section(2);
			get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
		payment_type_list_cell('Payment Type:','payment_type');
		receipt_list_cells('Receipt Released:', 'receipt_type', '', '', '',false,'');
		ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
	
end_outer_table(1); // outer table
div_end();

div_start('table_');

br();

start_table();
		hidden('tender_type',0);
		//	cash_check_list_row2('Type:', 'tender_type', '', '', '',false,'');
		date_row('Date Deposit:', 'date_deposit');
		
		$items2 = array();
		$items2['0'] = 'Group Transactions';
		$items2['1'] = 'Single Transaction';
		//$items2['2'] = 'Without CV';

		label_cells('Deposit as :',array_selector('deposit_type', null, $items2, array() ));
		
		// $myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
						// switch($myBranchCode){
							// //1020011 for AUB
							// //1020021 for METROBANk
				
						// case 'srsn':
									// $bank_account='1020011';
									// break;
						// case 'sri':
									// $bank_account='1020021';
									// break;
						// case 'srsnav':
									// $bank_account='1020021';
									// break;
						// case 'srst':
									// $bank_account='1020011';
									// break;
						// case 'srsc':
									// $bank_account='1020021';
									// break;
						// case 'srsant1':
									// $bank_account='1020021';
									// break;
						// case 'srsant2':
									// $bank_account='1020021';
									// break;
						// case 'srsm':
									// $bank_account='1020021';
									// break;
						// case 'srsmr':
									// $bank_account='1020021';
									// break;
						// case 'srsg':
									// $bank_account='1020011';
									// break;
						// case 'srscain':
									// $bank_account='1020011';
									// break;
						// case 'srsval':
									// $bank_account='1020021';
									// break;			
						// case 'srspun':
									// $bank_account='1020011';
									// break;		
						// case 'srspat':
									// $bank_account='1020011';
									// break;		
					// }
		//hidden('bank_account',$bank_account);
		bank_accounts_list_row2('Bank Account:', 'bank_account', null,'',true);
		text_row(_("Memo:"), 'memo_','',36);
end_table();
div_end();
br(2);

display_heading("Other Income Summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);
br();

//====================================display table=========================================
$br_code=$_POST['from_loc'];


switch_connection_to_branch_mysql($br_code);

$sql = "select * from 0_other_income_payment_header";
if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}


if ($_POST['payment_type']!= '')
{
//display_error($_POST['payment_type']);
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

if ($_POST['receipt_type']!= '0')
{
//display_error($_POST['payment_type']);
	$sql .= " AND bd_receipt_type='".$_POST['receipt_type']."'";
}


	$sql .= " AND bd_cleared='0' and checked!='2' ORDER BY bd_trans_date, bd_trans_no";

	$res = db_query($sql);
	//display_error($sql);
	//display_error($_POST['from_loc']);

start_table($table_style2.' width=70%');

$th=array('Date Paid', 'Trans #','RF/OR/SI #', 'Customer','Type','Amount');
array_push($th, 'Check all<br>'.checkbox('', 'check_all_op100', null, true, false));

if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No result found');
	display_footer_exit();
}


$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['bd_trans_date']));
	//label_cell(get_gl_view_str(ST_BANKDEPOSIT, $row["bd_trans_no"], $row["bd_trans_no"]));
	label_cell(get_gl_view_str_per_branch($_POST['from_loc'],ST_BANKDEPOSIT, $row["bd_trans_no"], $row["bd_trans_no"]));
	label_cell($row['bd_or'] ,'nowrap');
	label_cell($row['bd_payee'] ,'nowrap');
	label_cell($row['bd_payment_type']);
	amount_cell($row['bd_amount'],false);
	hidden('bd_amount'.$row['bd_trans_no'],$row['bd_amount']);
	hidden('from_loc2',$_POST['from_loc']);
	//label_cell(get_comments_string($type, $row['trans_no']));

	
		if ($row['checked'] == 1)
		{
			$_POST['for_op'.$row['bd_trans_no']] = 1;
			$checked_amount+=$row['bd_amount'];
		}
		else
		{
			unset($_POST['for_op'.$row['bd_trans_no']]);
		}
	
	check_cells('','for_op'.$row['bd_trans_no'],null,true, '', "align='center'");
	hidden('for_check_all_op'.$row['bd_trans_no'],$row['bd_trans_no']);
	hidden('bd_trans_date'.$row['bd_trans_no'],$row['bd_trans_date']);
	
	end_row();
$t_amount+=$row['bd_amount'];
}

start_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
end_row();
end_table();
br(2);

start_table();
label_cell('<b>Total Amount Selected: </b>');

//label_cell("<font color=#880000><b>".number_format2(abs($_POST['sub_amount']),2)."<b></font>",'align=right','sub_amount');
label_cell("<font color=#880000><b>".number_format2(abs($checked_amount),2)."<b></font>",'align=right','sub_amount');
	//hidden('t_amount',$_POST['sub_amount']);
hidden('t_amount',$checked_amount);
end_table();
br();
br();
start_table();
submit_cells('approve_selected', 'Deposit Selected Amount', "align=center", true, true,'ok.gif');
end_table();

div_end();
end_form();
end_page();
?>