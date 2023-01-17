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
function get_bt_cheque_details_aub($myBranchCode,$chk_number,$debit_amount,$date_deposited)
{
$sql = "SELECT * FROM cash_deposit.".TB_PREF."centralized_payment_aub_final WHERE chk_number='$chk_number' AND chk_amount='$debit_amount' AND reconciled='0' ORDER BY chk_date";
//display_error($sql);
$res = db_query($sql,'failed to select bank statement.');
$row = db_fetch($res);
	
return $row;
}

function get_bt_cheque_details_metro($myBranchCode,$chk_number,$debit_amount,$date_deposited)
{
$sql = "SELECT * FROM cash_deposit.".TB_PREF."centralized_payment_metro WHERE br_code='$myBranchCode' AND chk_amount='$debit_amount' AND reconciled='0' ORDER BY bank_trans_date";
//display_error($sql);
$res = db_query($sql);
$row = db_fetch($res);
	
return $row;
}

function update_aub_bank_statement($db_,$bank_statement_id,$trans_type,$dep_id,$myBranchCode,$trans_no)	
{		
	 $sql = "UPDATE cash_deposit.".TB_PREF."$db_ 
	 SET type = '$trans_type',
	 type_no='$trans_no',
	 reference='$dep_id',
	 cleared='1',
	 branch_code='$myBranchCode'
	WHERE id = '$bank_statement_id'";
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

function check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance,$bank_ref_num)
{
	if ($credit_amount>0 AND $credit_amount!=0){
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."$db_ 
	WHERE date_deposited='".date2sql($date_deposited)."' AND  credit_amount='$credit_amount' AND balance='$balance' AND bank_ref_num='$bank_ref_num'";
	}
	else{
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."$db_ 
	WHERE date_deposited='".date2sql($date_deposited)."' AND  debit_amount='$debit_amount' AND balance='$balance' AND bank_ref_num='$bank_ref_num'";
	}
	
	//display_error($sql);
	$res = db_query($sql);
	$count_row=db_num_rows($res);
	return $count_row;
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
	
	$db_='bank_statement_metro_final';
	
	if ($bank_format_marker=='Branch')
	{
		for ($i = 5; $i <= $excel_row; $i++) 
		{
			$date_deposited=$data->val($i,1);
			$bank_ref_num=$data->val($i,2);
			$deposit_type=$data->val($i,3);
			$date_deposited= date('m/d/Y', strtotime($date_deposited));

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

			
						if ($debit_amount!=0 AND $debit_amount>0){
						
							$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance,$bank_ref_num);

							if ($check_count<=0 AND $debit_amount!=0){
							$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_metro_final(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
							VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",'$debit_amount',0,'$balance')";		
							//display_error($sql);
							db_query($sql,'unable to import bank deposit statement');
							}
						}
						else{
							
							$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance,$bank_ref_num);

							if ($check_count<=0 AND $credit_amount!=0){
							$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_metro_final(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
							VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",0,$credit_amount,'$balance')";		
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
//==========================================================================================

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
	
	$db_='bank_statement_aub_final';
	//display_error($bank_format_marker);
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
				

			if ($debit_amount!=0 AND $debit_amount>0){
				
					$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance);

					if ($check_count<=0 AND $debit_amount!=0){
					$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_aub_final(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
					VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",'$debit_amount',0,'$balance')";		
					//display_error($sql);
					db_query($sql,'unable to import bank deposit statement');
					}
			}
	
			else{
						$check_count=check_exist_statement($db_,$date_deposited,$debit_amount,$credit_amount,$balance);

						if ($check_count<=0 AND $credit_amount!=0){
						$sql = "INSERT INTO cash_deposit.".TB_PREF."bank_statement_aub_final(date_deposited,bank_ref_num,deposit_type,debit_amount,credit_amount,balance)				
						VALUES ('".date2sql($date_deposited)."',".db_escape($bank_ref_num).",".db_escape($deposit_type).",0,$credit_amount,'$balance')";		
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

//=====================================================================

if (isset($_POST['upload'])) 
{
	global $db_connections;
	
	set_time_limit(0);
	
	if ($_POST['bank_account']=='10102299'){
		
		//handle_new_aub_excel_item();
		
		//===========PAYMENT RECON=============
		$db_='bank_statement_aub_final';
		$type=22;
		
		// $sql_ = "SELECT * from transfers.0_branches";
		// //display_error($sql);
		// $result_=db_query($sql_);
		// while($row_ = db_fetch($result_))
		// {
				
				//$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
				//display_error($myBranchCode);
				//$myBranchCode='srst';
				//$myBranchCode=$row_['code'];
				//switch_connection_to_branch($row_['code']);			
				
				$sql_payment="select * from cash_deposit.".TB_PREF."bank_statement_aub_final where bank_ref_num!='' AND cleared='0' AND debit_amount!=0 order by date_deposited";
				$res_payment = db_query($sql_payment,'failed to select bank statement.');
				//display_error($sql_payment);

				while($row_debit = db_fetch($res_payment))
				{
					$date_deposited=$row_debit['date_deposited'];
					$debit_amount=$row_debit['debit_amount'];
					$bank_ref_num=$row_debit['bank_ref_num'];
					$bank_statement_id=$row_debit['id'];
					$bank_gl_code='10102299';
					
					
					$row_bank_trans=get_bt_cheque_details_aub($myBranchCode,$bank_ref_num,$debit_amount,$date_deposited);
					$bank_trans_id=$row_bank_trans['id'];
					$trans_no=$row_bank_trans['trans_no'];
					$myBranchCode=$row_bank_trans['br_code'];
					
					//display_error($bank_trans_id);
					
						if ($bank_trans_id!=0 AND $bank_trans_id!='')
						{
							$sql_update_bank_trans = "UPDATE cash_deposit.".TB_PREF."centralized_payment_aub_final 
							SET reconciled='1',
							deposit_date='$date_deposited',
							ref='$bank_statement_id'
							WHERE id ='$bank_trans_id' AND type='$type'";
							db_query($sql_update_bank_trans,'failed to select aub payments.');
							
							update_aub_bank_statement($db_,$bank_statement_id,$type,$bank_trans_id,$myBranchCode,$trans_no);
						}	
				}
	//	}
	//===================================
	}
	
	else if ($_POST['bank_account']=='1020021'){
		global $db_connections;
		
			handle_new_metro_excel_item();
			
			
			// //===========PAYMENT RECON=============
		// $db_='bank_statement_metro_final';
		// $type=22;
		
		
		// // $sql_ = "SELECT * from transfers.0_branches";
		// // $result_=db_query($sql_);

		// // while($row_ = db_fetch($result_))
		// // {
			
			// $myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
			// //$myBranchCode=$row_['code'];
			// //switch_connection_to_branch($row_['code']);			
			
			// $sql_payment="select * from cash_deposit.".TB_PREF."bank_statement_metro_final where cleared='0' AND debit_amount!=0 order by date_deposited";
			// $res_payment = db_query($sql_payment);

			// while($row_debit = db_fetch($res_payment))
			// {
			// $date_deposited=$row_debit['date_deposited'];
			// $debit_amount=$row_debit['debit_amount'];
			// $bank_ref_num=$row_debit['bank_ref_num'];
			// $bank_statement_id=$row_debit['id'];
			// $bank_gl_code='1020021';
				
				// $row_bank_trans=get_bt_cheque_details_metro($myBranchCode,$bank_ref_num,$debit_amount,$date_deposited);
				// $bank_trans_id=$row_bank_trans['id'];
				
				// //display_error($bank_trans_id);
			
					// if ($bank_trans_id!=0 AND $bank_trans_id!='')
					// {
						// $sql_update_bank_trans = "UPDATE cash_deposit.".TB_PREF."centralized_payment_metro
						// SET reconciled='1',
						// deposit_date='$date_deposited'
						// WHERE id ='$bank_trans_id' AND type='$type'";
						// db_query($sql_update_bank_trans);
						
						// update_bank_statement($db_,$bank_statement_id,$type,$bank_trans_id,$myBranchCode);
					// }
			
			// }
		// //}
		// //==============================================
			
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
	
	display_notification_centered("successful.");
	
	//set_global_connection_branch();
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