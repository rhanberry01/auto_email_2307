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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Transfer DM to other Supplier"), false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);


function check_my_suppliers_name($supp_name)
{
	$sql = "SELECT * FROM ".TB_PREF."suppliers WHERE supp_name =".db_escape($supp_name);
	//display_error($sql);
	$res = db_query($sql);
	
	
	$v_sql = "SELECT vendorcode,description,address,city,zipcode,country,fax,email,phone,contactperson,termid,daystodeliver,tradediscount,cashdiscount,
			terms,IncludeLineDiscounts,discountcode1,discountcode2,discountcode3,discount1,discount2,discount3,daystosum,reordermultiplier,remarks,
			SHAREWITHBRANCH,Consignor,LASTDATEMODIFIED,TIN FROM vendor 
			WHERE description = '".ms_escape_string($supp_name)."'";
	$v_res = ms_db_query($v_sql);
		//display_error($v_sql);
	$v_row = mssql_fetch_array($v_res);
	
	if (trim($v_row['vendorcode']) == '')
	{
		display_error("VENDOR CODE NOT IN MSSQL : $supp_name");
		return false;
	}
		
		
	if (db_num_rows($res) > 0)
	{
		$row = db_fetch($res);
		
		$update_sql = "UPDATE ".TB_PREF."suppliers SET
				supp_name = ".db_escape($v_row['description']).", 
				supp_ref = ".db_escape($v_row['vendorcode']).", 
				address = ".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')) .", 
				supp_address = ".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')).", 
				phone = ".db_escape($v_row['phone']).", 
				fax = ".db_escape($v_row['fax']).", 
				contact = ".db_escape($v_row['contactperson'])."
			WHERE supp_name =".db_escape($supp_name);
		db_query($update_sql, 'failed to update supplier');
		return $row['supplier_id'];
	}
	else
	{
		$ins_sql = "INSERT INTO ".TB_PREF."suppliers (supp_name ,supp_ref ,address ,supp_address ,phone ,fax ,gst_no ,contact ,email ,payment_terms ,notes)
			VALUES(".db_escape($v_row['description']).", 
				".db_escape($v_row['vendorcode']).", 
				".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')) .", 
				".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')).", 
				".db_escape($v_row['phone']).", 
				".db_escape($v_row['fax']).", 
				".db_escape($v_row['TIN']).",  
				".db_escape($v_row['contactperson']).",  
				".db_escape($v_row['email']).",  1, 
				".db_escape($v_row['remarks']).")";
		db_query($ins_sql, 'failed to insert supplier');
		
		return db_insert_id();
	}
}

//----------------------------------------------------------------------------------------
if (isset($_POST['Fix'])) {
	
		global $db_connections, $Refs;
		$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		
		set_time_limit(0);
		
		begin_transaction();
			
		$tran_date='2018-01-01';
		$date_x=sql2date($tran_date);

		
		/*

			$transfer_dms_sql= "SELECT *, SUM(abs(round(ov_amount + ov_gst  - ov_discount - ewt,2))) AS TotalAmount FROM `0_apv_summary_from_2015_to_present`
			where supp_name LIKE '%PHIL. LEADING%'
			GROUP BY trans_no";
		$transfer_dms_result=db_query($transfer_dms_sql);
		
		while($pending_dms_result_row=mysql_fetch_array($transfer_dms_result)) 
		{
				$supp_id=$pending_dms_result_row['supplier_id'];
				$supp_ref=$pending_dms_result_row['supp_reference'];
				$supp_name=$pending_dms_result_row['supp_name'];
				$br_code=$pending_dms_result_row['branch_code'];
				$trans_no=$pending_dms_result_row['trans_no'];
				$ref=$pending_dms_result_row['reference'];
				$date_=$pending_dms_result_row['tran_date'];
				$amount=$pending_dms_result_row['TotalAmount'];

				$sql = "INSERT INTO transfers.0_transferred_apvs (supp_id, supp_ref, supp_name, br_code, trans_no, ref, date_, amount) 
				VALUES ('$supp_id', ".db_escape($supp_ref).",".db_escape($supp_name).", '$br_code', '$trans_no', ".db_escape($ref).", '$date_', '$amount')";
				db_query($sql, "dm could not be inserted");	
				//display_error($sql);
		}
		*/
		


		$transfer_dms_sql= "SELECT DISTINCT supp_name FROM transfers.0_transferred_apvs where br_code!='srsn' and nova_trans_no=0 and supp_name like '%MOLINA%' ";
		$transfer_dms_result=db_query($transfer_dms_sql);
		
		while($transfer_dms_result_row=mysql_fetch_array($transfer_dms_result)) 
		{
			$supp_name_x=$transfer_dms_result_row['supp_name'];
			//===========SUPP NAME
			$sql_get_new_vendor= "SELECT supplier_id, supp_ref, supp_name FROM 0_suppliers WHERE supp_name=".db_escape($supp_name_x)."";
			//display_error($sql_get_new_vendor);
			$result1 = db_query($sql_get_new_vendor, "failed to get bank_accounts id.");
			$row1=db_fetch($result1);
			$vendor_codes=$row1['supp_ref'];
			$supp_names=$row1['supp_name'];
			$supp_id=$row1['supplier_id'];
			
			//display_error($supp_names);
		
			if ($supp_names == '')
			{
				check_my_suppliers_name($supp_name_x);
				//display_error('GO1');
				display_error("$supp_name_x Supplier does not exist in NOVA.");
				//return false;
			}
			else{
				//display_error('GO2');
						//$sql_ = "SELECT * from transfers.0_branches where code='srsn'";
						$sql_ = "SELECT code, name from transfers.0_branches_other_income";
						//display_error($sql_);
						$result_=db_query($sql_);
						while($row_ = db_fetch($result_))
						{
							$myBranchCodex=$row_['code'];
							$myBranchNamex=$row_['name'];
							//display_error($myBranchCodex);
								
								$sql_y = "SELECT br_code, YEAR(date_) as yr, supp_name, ABS(ROUND(SUM(amount),2)) as amt
								FROM transfers.0_transferred_apvs
								WHERE supp_name=".db_escape($supp_name_x)."
								and br_code!='srsn'
								and br_code='$myBranchCodex'
								and nova_trans_no=0
								GROUP BY br_code, YEAR(date_), supp_name";
								//display_error($sql_y);
								$result_y=db_query($sql_y);
								while($row_y = db_fetch($result_y))
								{
										$br_code=$row_y['br_code'];
										$yr=$row_y['yr'];
										$amt=$row_y['amt'];
										//$supp_name=$row_y['supp_name'];
										// insert supp trans for debit memo 
										$reference = $Refs->get_next(20);
										$trans_no = add_supp_trans(20, $supp_id, $date_x, '', $reference, "$myBranchNamex Consolidated $yr Pending APV", $amt,  0, 0, "", 0, 0);
										
										// GL entries
										$debit_account = '5700031'; //purchase in
										$credit_account = '2000'; //accounts_payable
										
										add_gl_trans(20, $trans_no, $date_x, $debit_account, 0, 0, "$myBranchNamex Consolidated $yr Pending APV", $amt,null,3,$supp_id);
										add_gl_trans(20, $trans_no, $date_x, $credit_account, 0, 0, "$myBranchNamex Consolidated $yr Pending APV", -$amt, null,3,$supp_id);
						

										//=============TO GET MEMO							
										$sql_get_dm = "SELECT * FROM transfers.0_transferred_apvs
										where br_code!='srsn' 
										and supp_name=".db_escape($supp_name_x)."
										and br_code='$myBranchCodex'
										and YEAR(date_)='$yr'
										and nova_trans_no=0
										";
										//display_error($sql_get_dm);
										$result_x=db_query($sql_get_dm);
										while($row_x = db_fetch($result_x))
										{
											$branch_supp_ref[]=$row_x['supp_ref'];
											$dm_x = implode(', ',$branch_supp_ref);
										}
										//print_r($dm_x);
										$memo_="Transferred APV: ".$myBranchNamex."-".$yr.": (".$dm_x.")";
										//display_error($memo_);
										if($memo_ != '')
										{
											add_comments(20, $trans_no, $date_x, $memo_);
										}
										//============END TO GET MEMO
										
										
										$Refs->save(20, $trans_no, $reference);
										
										$sql = "UPDATE transfers.0_transferred_apvs SET nova_trans_no='$trans_no' 
										where br_code!='srsn' 
										and supp_name=".db_escape($supp_name_x)."
										and br_code='$myBranchCodex'
										and YEAR(date_)='$yr'
										and nova_trans_no=0
										";
										db_query($sql,'failed to update 0_transferred_apvs.');
										//display_error($sql);
										
										$branch_supp_ref="";
										$dm_x="";
								}
						}
				}
		}


		display_notification("Create DM Successful!");
	
		commit_transaction();

}

start_form();
start_table();
start_row();
// supplier_list_cells(_("Transfer DM From Supplier:"), 'account_from', null, true);
// supplier_list_cells(_("to Supplier:"), 'account_to', null, true);
end_row();
end_table();
br();
br();
start_row();
submit_center('Fix', 'TRANSFER PURCHASES TO NOVA');
end_table();
end_form();
end_page();
?>