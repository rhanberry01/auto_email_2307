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


// ===== Custom JS ===== //
echo '
<script type="text/javascript">
	$(document).ready(function() {
		$("#select_all").change(function() {
			$(".select_one").attr("checked", this.checked);
		});

		$(".select_one").change(function() {
			if ($(".select_one").length == $(".select_one:checked").length) {
				$("#select_all").attr("checked", "checked");
			}
			else {
				$("#select_all").removeAttr("checked");
			}
		});
	});
</script>
';
// ===== End - Custom ===== //



// Custom JS here
echo '<script type="text/javascript">';
	echo '$(document).ready(function() {

			$("input:checkbox").change(function() {
				if ($("input:checkbox:checked").length > 0) {
					$("#approve_all_checked").show();
				}
				else {
					$("#approve_all_checked").hide();
				}	
			});
			
		});';
echo '</script>';	


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


function create_dm_in($pcd_ids) {
	
		global $db_connections, $Refs;
		
		$myBranchCodeNova='srsn';
		
		$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		
		set_time_limit(0);
		
	//display_error($pcd_ids);
	//	begin_transaction();
			
		$tran_date='2018-01-01';
		$date_x=sql2date($tran_date);
		
		$transfer_dms_sql= "SELECT DISTINCT supp_name FROM transfers.0_transferred_transactions where br_code!='$myBranchCodeNova' and br_in_trans_no=0 and type=53 and br_code='$myBranchCode'";
		$transfer_dms_sql.= " AND trans_no IN (".$pcd_ids.")";
		$transfer_dms_result=db_query($transfer_dms_sql);
		
		//display_error($transfer_dms_sql);
		
		while($transfer_dms_result_row=db_fetch($transfer_dms_result)) 
		{		
			$supp_name_x=$transfer_dms_result_row['supp_name'];
			
			//display_error($supp_name_x);
			
			//$x=switch_connection_to_branch_mysql($myBranchCodeNova);	
			set_global_connection_branch_mysql(4);
			
			//display_error($x);
			
			//===========SUPP NAME
			$sql_get_new_vendor= "SELECT supplier_id, supp_ref, supp_name FROM 0_suppliers WHERE supp_name=".db_escape($supp_name_x)."";
			$result1 = db_query($sql_get_new_vendor, "failed to get bank_accounts id.");
			$row1=db_fetch($result1);
			$vendor_codes=$row1['supp_ref'];
			$supp_names=$row1['supp_name'];
			$supp_id=$row1['supplier_id'];
			
			//display_error($sql_get_new_vendor);
			
			//display_error($vendor_codes);
			//display_error($supp_names);
			//display_error($supp_id);
		
			if ($supp_names == '')
			{
				//check_my_suppliers_name($supp_name_x);
				//display_error('GO1');
				display_error("$supp_name_x Supplier does not exist in NOVA.");
				die();
			}
			else{
				//display_error('GO2');
						//$sql_ = "SELECT * from transfers.0_branches where code='srsn'";
						$sql_ = "SELECT code, name from transfers.0_branches_other_income where code='$myBranchCode'";
						//display_error($sql_);
						$result_=db_query($sql_);
						while($row_ = db_fetch($result_))
						{
							$myBranchCodex=$row_['code'];
							$myBranchNamex=$row_['name'];
							//display_error($myBranchCodex);
								
								$sql_y = "SELECT br_code, supp_name, ABS(ROUND(SUM(amount),2)) as amt
								FROM transfers.0_transferred_transactions
								WHERE supp_name=".db_escape($supp_name_x)."
								and br_code!='srsn'
								and br_code='$myBranchCodex'
								and br_in_trans_no=0
								and type=53";
								
								$sql_y.= " AND trans_no IN (".$pcd_ids.") GROUP BY br_code, supp_name";
								//display_error($sql_y);
								$result_y=db_query($sql_y);
								while($row_y = db_fetch($result_y))
								{
										//$br_code=$row_y['br_code'];
										$amt=$row_y['amt'];
										//$supp_name=$row_y['supp_name'];
										// insert supp trans for debit memo 
										$reference = $Refs->get_next(ST_SUPPDEBITMEMO);
										$trans_no = add_supp_trans(ST_SUPPDEBITMEMO, $supp_id, $date_x, '', $reference, "$myBranchNamex Debit Memo IN", -$amt,  0, 0, "", 0, 0);
										
										// GL entries
										$debit_account = '2000'; //accounts_payable
										$credit_account = '5700033'; // 5700033	Debit Memo In
										
										add_gl_trans(ST_SUPPDEBITMEMO, $trans_no, $date_x, $debit_account, 0, 0, "$myBranchNamex Debit Memo IN", $amt,null,3,$supp_id);
										add_gl_trans(ST_SUPPDEBITMEMO, $trans_no, $date_x, $credit_account, 0, 0, "$myBranchNamex Debit Memo IN", -$amt, null,3,$supp_id);
						

										//=============TO GET MEMO							
										$sql_get_dm = "
										
										SELECT * FROM transfers.0_transferred_transactions
										where br_code!='srsn' 
										and supp_name=".db_escape($supp_name_x)."
										and br_code='$myBranchCodex'
										and br_in_trans_no=0
										and type=53
										
										";
										$sql_get_dm.= " AND trans_no IN (".$pcd_ids.")";
										//display_error($sql_get_dm);
										$result_x=db_query($sql_get_dm);
										while($row_x = db_fetch($result_x))
										{
											$branch_supp_ref[]=$row_x['supp_ref'];
											$dm_x = implode(', ',$branch_supp_ref);
										}
										//print_r($dm_x);
										$memo_="Transferred Debit Memo: ".$myBranchNamex." Ref#: (".$dm_x.")";
										//display_error($memo_);
										if($memo_ != '')
										{
											add_comments(ST_SUPPDEBITMEMO, $trans_no, $date_x, $memo_);
										}
										//============END TO GET MEMO
										
										
										$Refs->save(ST_SUPPDEBITMEMO, $trans_no, $reference);
										
										$sql = "UPDATE transfers.0_transferred_transactions SET br_in_trans_no='$trans_no', br_code_in='$myBranchCodeNova'
										where br_code!='srsn' 
										and supp_name=".db_escape($supp_name_x)."
										and br_code='$myBranchCodex'
										and br_in_trans_no=0
										and type=53
										";
										$sql.= " AND trans_no IN (".$pcd_ids.")";
										
										db_query($sql,'failed to update 0_transferred_transactions.');
										//display_error($sql);
										
										$branch_supp_ref="";
										$dm_x="";
								}
						}
				}
				
		}


		//display_notification("Create DM Successful!");
	
		//commit_transaction();
		
		set_global_connection_branch();//go back to default connection.

}

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
if (isset($_POST['RefreshInquiry'])) {
	
	$Ajax->activate('table_');
} 

//----------------------------------------------------------------------------------------
if (isset($_POST['approve_all_checked'])) {
	
	
	if (!empty($_POST['petty_cash'])) {
		$pcd_ids = implode(',', $_POST['petty_cash']);
		//display_error($pcd_ids);
	}

	global $db_connections, $Refs;
	$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	set_time_limit(0);
	
	begin_transaction();

					$transfer_dms_sql_gl="SELECT SUM(abs(round(ov_amount + ov_gst  - ov_discount - ewt,2))) AS TotalAmount
					FROM 0_supp_trans as sth
					WHERE type = 53
					AND cv_id=0
					AND ov_amount!=0";
					$transfer_dms_sql_gl .= " AND trans_no IN (".$pcd_ids.")";
					$transfer_dms_result_gl=db_query($transfer_dms_sql_gl);
					//display_error($transfer_dms_sql_gl);
		
		
					while($transfer_dms_result_row_gl=mysql_fetch_array($transfer_dms_result_gl)) 
					{
							$date_ = Today();
							$ref   = $Refs->get_next(0);

							$trans_type = ST_JOURNAL;

							$trans_id = get_next_trans_no($trans_type);

							$transfer_dms_sql="SELECT sth.*, sup.supp_name, SUM(abs(round(ov_amount + ov_gst  - ov_discount - ewt,2))) AS TotalAmount
							FROM 0_supp_trans as sth
							LEFT JOIN 0_suppliers as sup
							ON sth.supplier_id=sup.supplier_id 
							WHERE type = 53
							AND cv_id=0
							AND ov_amount!=0";
							$transfer_dms_sql .= " AND trans_no IN (".$pcd_ids.")";
							
							$transfer_dms_sql .= " GROUP BY trans_no";
							
							$transfer_dms_result=db_query($transfer_dms_sql);
							
							//display_error($transfer_dms_sql);
							
							while($transfer_dms_result_row=mysql_fetch_array($transfer_dms_result)) 
							{
								

									$supp_name=$transfer_dms_result_row['supp_name'];
									
									//===========SUPP NAME
									$sql_get_new_vendor= "SELECT supplier_id, supp_ref, supp_name FROM srs_aria_nova.0_suppliers WHERE supp_name=".db_escape($supp_name)."";
									$result1 = db_query($sql_get_new_vendor, "failed to get bank_accounts id.");
									$row1=db_fetch($result1);
									$supp_names=$row1['supp_name'];
									//display_error($supp_names);
								
									if ($supp_names == '')
									{
										//check_my_suppliers_name($supp_name_x);
										//display_error('GO1');
										display_error("$supp_name Supplier does not exist in NOVA.");
										die();
									}
										
											
											$branch_supp_ref[]=$transfer_dms_result_row['supp_reference'];
											$dm_x = implode(', ',$branch_supp_ref);
											
											
											$type=$transfer_dms_result_row['type'];
											$supp_id=$transfer_dms_result_row['supplier_id'];
											$supp_ref=$transfer_dms_result_row['supp_reference'];
										
											//$br_code=$transfer_dms_result_row['branch_code'];
											$trans_no=$transfer_dms_result_row['trans_no'];
											$refx=$transfer_dms_result_row['reference'];
											$date_x=$transfer_dms_result_row['tran_date'];
											$amount=$transfer_dms_result_row['TotalAmount'];
											

											$sql = "INSERT INTO transfers.0_transferred_transactions (supp_id, supp_ref, supp_name, br_code, type, trans_no, ref, date_, amount, journal_out_trans_no) 
											VALUES ('$supp_id', ".db_escape($supp_ref).",".db_escape($supp_name).", '$myBranchCode', '$type', '$trans_no', ".db_escape($refx).", '$date_x', '$amount', '$trans_id')";
											db_query($sql, "dm could not be inserted");	
											//display_error($sql);
									

											$sql = "UPDATE ".TB_PREF."supp_trans SET cv_id='-4' 
											WHERE type='$type'
											AND trans_no='$trans_no'
											AND cv_id=0 ";
											db_query($sql,'failed to update supp_trans.');
											
											//display_error($sql);
											
							}
							

							
								$memo_="Journal, To transfer pending DM to NOVA Branch. Ref#: (".$dm_x.")";

								add_gl_trans($trans_type, $trans_id, $date_, 5700034, 0, 0, "$myBranchNamex Debit Memo Out", $transfer_dms_result_row_gl['TotalAmount'],null);
								add_gl_trans($trans_type, $trans_id, $date_, 2000, 0, 0, "$myBranchNamex Debit Memo Out", -$transfer_dms_result_row_gl['TotalAmount'],null);
						
						
								if($memo_ != '')
								{
									add_comments($trans_type, $trans_id, $date_, $memo_);
								}

								$Refs->save($trans_type, $trans_id, $ref);

								add_audit_trail($trans_type, $trans_id, $date_);
					
					}
					
	commit_transaction();
	
	create_dm_in($pcd_ids);

	display_notification("Transfer DM Successful!");


}
	

start_form();


start_table("class='tablestyle_noborder'");
start_row();

supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true);

if (!isset($_POST['TransAfterDate']))
{
	$ddd = explode_date_to_dmy(Today());
	$_POST['TransAfterDate'] = __date(2018,1,1);
}
date_cells(_("From:"), 'TransAfterDate', '', null, -30);
date_cells(_("To:"), 'TransToDate');

//supp_allocations_list_cell("filterType", null);


submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), false);

end_row();
end_table();


if ($_POST['supplier_id']!=''){
	
	//display_error($_POST['supplier_id']);
	
// start_table();
// start_row();
// // supplier_list_cells(_("Transfer DM From Supplier:"), 'account_from', null, true);
// // supplier_list_cells(_("to Supplier:"), 'account_to', null, true);
// end_row();
// end_table();
// br();
// br();
// start_row();
// echo '<center style="margin: 10px 0;">
		// <button class="inputsubmit" type="submit" id="approve_all_checked" name="approve_all_checked" style="display: none;">
			// <span>Approve All Checked</span>
		// </button>
	// </center>';
// end_table();

br();

div_start('table_');

echo '<center style="margin: 10px 0;">
		<button class="inputsubmit" type="submit" id="approve_all_checked" name="approve_all_checked" style="display: none;">
			<span>Approve All Checked</span>
		</button>
	</center>';
	
start_table($table_style2.' width=75%');
$th = array();

$from_head = array('<input type="checkbox" id="select_all">',"","Supplier","Supp Ref.","Amount","Description");


 table_header($from_head);

$k = 0;

    $date_after = date2sql($_POST['TransAfterDate']);
    $date_to = date2sql($_POST['TransToDate']);

	$sql="SELECT a.*,SUM(abs(round(ov_amount + ov_gst  - ov_discount - ewt,2))) AS TotalAmount FROM (SELECT 'srsal' as branch_code, 'ALAMINOS' as branch, sup.supplier_id as supp_id, sup.supp_ref, sup.supp_name, spt.*, c.memo_
	FROM 0_supp_trans as spt
	LEFT JOIN 0_suppliers as sup
	ON spt.supplier_id=sup.supplier_id LEFT JOIN 0_comments as c ON (spt.trans_no=c.id and spt.type=c.type)
	where spt.type=53
	and cv_id=0
	and ov_amount!=0
	and tran_date>='$date_after' and tran_date<='$date_to' ";
	// display_error($sql);

	
	if ($_POST['supplier_id'] != ALL_TEXT)
			$sql .= " AND sup.supplier_id = ".db_escape($_POST['supplier_id']);
	
	
	$sql.=" ) as a GROUP BY trans_no";
	
	//display_error($sql);
	
$res = db_query($sql); 

while($row = db_fetch($res))
{
	$c ++;
	alt_table_row_color($k);
		echo '<td align="center">
			<input type="checkbox" class="select_one" name="petty_cash[]" value="'.$row['trans_no'].'">
		</td>';
	label_cell($c,'align=right');
	label_cell($row['supp_name']);
	label_cell($row['supp_reference']);
	label_cell(number_format2($amount=$row['TotalAmount']));
	//label_cell(number_format2($t_dm_2016=abs(get_pen_dm_2016($row['supp_name']))));
	//label_cell(number_format2($t_dm_2017=abs(get_pen_dm_2017($row['supp_name']))));
	

		label_cell($row['memo_']);

	
	end_row();
$ex_apv+=$t_apv;
$ex_dm_2015+=$amount;
}

start_row();

label_cell(''); // for checkbox 
label_cell(''); // for checkbox 
label_cell(''); // for checkbox 
label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
label_cell("<font color=#880000><b>".number_format2(abs($ex_dm_2015),2)."<b></font>",'align=right');

label_cell('');
//label_cell("<font color=#880000><b>".number_format2(abs($tax_total),2)."<b></font>",'align=right');
//print_r($sub_t[$gl['gl_used']]);
//label_cell("<font color=#880000><b>".number_format2(abs($t_total),2)."<b></font>",'align=right');
end_row();	
end_table();

}

div_end();

end_form();
end_page();
?>