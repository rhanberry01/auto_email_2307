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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");


$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();


//--------------------------------------------------------------------------------------------------
function get_branchcode_($br_id)
{
$sql = "SELECT code from transfers.0_branches_other_income where id='".$br_id."'";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_code=$row['code'];
return $br_code;
}

if (isset($_GET['sdma_id']))
{
	page('Edit Supplier Debit Memo Agreement', false, false,'', $js);
	$sdma_id = $_POST['sdma_id'] = $_GET['sdma_id'];
	

		$sql = "SELECT * FROM ".TB_PREF."sdma 
			WHERE id = $sdma_id";
		
		if (($_SESSION['wa_current_user']->username != 'admin' AND $_SESSION['wa_current_user']->username != 'beth' ) 
			AND !$_SESSION['wa_current_user']->can_approve_sdma_1)
		{
			$sql .= " AND prepared_by = ".$_SESSION['wa_current_user']->user;
		}
		$sql .=	" AND approval_2 = 0";
		// display_error($sql);
		$res = db_query($sql);
		
		if (db_num_rows($res)==0)
		{
			display_error("You can not edit this Debit Memo because it may have been posted or it is not yours.");
			hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
			display_footer_exit();
		}
	
	$row = get_sdma($_POST['sdma_id']);
	
	$_POST['reference'] = $row['reference'];
	$_POST['po_no'] = $row['po_no'];
	$_POST['supplier_id_aria'] = $row['supplier_id'];
	$_POST['sdma_type'] = $row['sdma_type'];
	$_POST['dm_date'] = sql2date($row['dm_date']);
	
	$_POST['amount'] = $row['amount'];
	$_POST['freq'] = $row['frequency'];
	$_POST['receivable'] = $row['receivable'];
	$_POST['periods'] = ($row['period'] > 0 ? $row['period']+1 : 0);
	
	$_POST['disc_percent'] = $row['disc_percent'];
	$_POST['effective_from'] = sql2date($row['effective_from']);
	$_POST['effective_to'] = sql2date($row['effective_to']);
	
	$_POST['comment'] = $row['comment'];

}
else
	page('Create Supplier Debit Memo Agreement', false, false,'', $js);

function check_sdma_reference($reference)
{
	$sql = "SELECT * FROM ".TB_PREF."sdma WHERE reference = ".db_escape($reference);
	$res = db_query($sql);
	
	return (db_num_rows($res) == 0);
}

function check_data()
{
	if (trim($_POST['reference']) == '')
	{
		display_error('Reference is required.');
		return false;
	}
	
	if (!check_sdma_reference($_POST['reference']) AND $_POST['sdma_id'] == '')
	{
		display_error('Reference is already entered.');
		return false;
	}
	
	if ($_POST['supplier_id'] == '' AND  $_POST['supplier_id_aria'] == '') {
		display_error(_("You must choose a supplier. "));
		set_focus('supplier_id_aria');
		$input_error = 1;
	}
	
	if ($_POST['supplier_id'] != '' AND  $_POST['supplier_id_aria'] != '') {
		display_error(_("You must choose only 1 supplier. "));
		set_focus('supplier_id_aria');
		$input_error = 1;
	}

	if ($_POST['sdma_type'] == '')
	{
		display_error('Choose a Debit Memo Type.');
		return false;
	}
	if (trim($_POST['comment']) == '')
	{
		display_error('Comment is required.');
		return false;
	}
	
	
/*	if ($_POST['amount_percentage'] == 0 AND input_num('amount') <= 0)
	{
		display_error('Amount should be greater than 0');
		return false;
	}
	
	if ($_POST['amount_percentage'] == 1 AND input_num('disc_percent') <= 0)
	{
		display_error('Percentage Discount should be greater than 0');
		return false;
	}*/
	
	if ($_POST['dm_date'] == '')
	{
		display_error('DM Date should not be empty');
		return false;
	}
	
	/*if (!is_date($_POST['dm_date']) AND $_POST['po_no'] == '')
	{
		display_error('DM Date should be a valid date');
		return false;
	}*/
	/*
	if ($_POST['just_once'] == 0 AND $_POST['po_no'] == '')
	{
		if ($_POST['effective_from'] == '')
		{
			display_error('Effective from should not be empty');
			return false;
		}
		
		if ($_POST['effective_to'] == '')
		{
			display_error('Effective to should not be empty');
			return false;
		}
		
		if (!is_date($_POST['effective_to']))
		{
			display_error('Effective to should be a date');
			return false;
		}
		
		if (!is_date($_POST['effective_from']))
		{
			display_error('Effective from should be a date');
			return false;
		}
		
		if (!is_date($_POST['effective_to']))
		{
			display_error('Effective to should be a date');
			return false;
		}

		if (date_diff2($_POST['effective_to'],$_POST['effective_from'],'d') < 0)
		{
			display_error('Effective from should be less than Effective to');
			return false;
		}
	}*/
	
	return true;
}

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];

   	display_notification_centered( _("Debit Memo submitted for approval"));

	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View Debit Memo")));

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter Debit Memo"));

	display_footer_exit();
}
if (isset($_GET['UpdatedID'])) 
{
	$trans_no = $_GET['UpdatedID'];

   	display_notification_centered( _("Debit Memo Updated"));

	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View Debit Memo")));

   	hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Debit Memo Inquiry"));

	display_footer_exit();
}
if (isset($_GET['DeletedID'])) 
{
	$trans_no = $_GET['DeletedID'];

   	display_notification_centered( _("Debit Memo Deleted"));

	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View Debit Memo")));

   	hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Debit Memo Inquiry"));

	display_footer_exit();
}

if (isset($_POST['Process']))
{
				global $Ajax,$db_connections;
				$prefix = 'selected_id';
				$dm_ids = array();


				/*	foreach($_POST as $postkey=>$postval)
				{
					//display_error(strpos($postkey, $prefix));
					
					if (strpos($postkey, $prefix) === 0)
					{
						$id = substr($postkey, strlen($prefix));
						$dm_ids[] = $id;
						//print_r($dm_ids);
					}
				}
				*/

				$tran_date=$_POST['dm_date'];
				if (is_date_in_event_locker($tran_date)==1)
				{
					display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
					exit();
				}


				if (check_data() == true)
				{
					$sql = "SELECT counter FROM ".TB_PREF."counter
						WHERE code = 'SDMA'";
					$res = db_query($sql);
					$row = db_fetch($res);
					$sql = "Update ".TB_PREF."counter set counter=counter+1 WHERE code = 'SDMA'";
					db_query($sql);
						
					$ref = str_pad($row['counter'], 5, '000000', STR_PAD_LEFT);
						
					$_POST['reference'] = $ref;
					
						$branch_ids = 1;
						$branch_count = $_POST['branch_count'];

					//	display_error($branch_count);

						while($branch_count  >= $branch_ids){

							$reference ='reference';
							$po_no ='po_no';
							$supplier_id ='supplier_id';
							$supplier_id_aria ='supplier_id_aria';
							$sdma_type ='sdma_type';
							$amount_percentage ='amount_percentage';
							$freq ='freq';
							$periods ='periods';

							$disc_percent ='disc_percent'.$branch_ids;
							$data_amount ='amount'.$branch_ids;
							$data_amount_all_branch ='amount_all_branch';
							$data_dm_date ='dm_date';
							$data_receivable=0;
							$amount = input_num($data_amount);

							if(input_num('amount_all_branch') != 0 ){
								$amount = input_num('amount_all_branch');
							}
							
							if($amount != 0 || input_num($disc_percent) !=0){
 							//	display_error('ss');
							if($branch_ids != 23 AND $branch_ids != 11){
							//	begin_transaction();
								$br_code = get_branchcode_($branch_ids);
								switch_connection_to_branch_mysql($br_code);
								//display_error($_POST[$reference].'</br>'.$_POST[$data_amount].'</br>'.$_POST[$data_dm_date].'</br>'.$_POST[$data_receivable].'</br>');


									if ($_POST['po_no'] != '')
									{
										$_POST['effective_from'] = 'NULL';
										$_POST['effective_to'] = 'NULL';
										$_POST['just_once'] = '0';
									}

									$periods = input_num('periods');
							
									if ($amount != 0 || input_num($disc_percent) !=0)
										$_POST['just_once'] = '1';
									
									if ($_POST['freq'] == 0)
										$periods = 0;
									else
										$periods -= 1;
										
									if ($periods == 0)
										$_POST['freq'] == 0;

									if ($_POST['supplier_id'] != '')
									{
										$supp_ref = $_POST['supplier_id'];
										$_POST['supplier_id_aria'] = check_my_suppliers($supp_ref);
									}

										if ($_POST['sdma_id'] == '')
										{
									//	display_error($br_code);									
										//display_error('code'.$br_code.$_POST['supplier_id'].'</br>'.'Supplier'.$_POST['supplier_id_aria'].'</br>'.$_POST[$reference].'</br>'.$_POST[$data_amount].'</br>'.$_POST[$data_dm_date].'</br>'.$_POST[$data_receivable].'</br>');
											$id = insert_sdma('SRSSAF '.$_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST[$data_dm_date],
												$_POST['freq'],$periods,$amount,input_num($disc_percent),
											$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'], $data_receivable);
										}
										
									/*	else if (!$_POST['sdma_id'] == '')
										{
											$sql = "SELECT * FROM ".TB_PREF."sdma 
											WHERE id = ".$_POST['sdma_id']."
											AND approval_2 = 0";
											$res = db_query($sql);
											
											if (db_num_rows($res)==0)
											{
												display_error("You can not edit this Debit Memo because it may have been posted.");
												hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
												display_footer_exit();
											}
											
											$id = $_POST['sdma_id'];
											update_sdma($_POST['sdma_id'],$_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST['dm_date'],
												$_POST['freq'],$periods,input_num('amount'),input_num('disc_percent'),
												$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'], $_POST['receivable']);
												
											meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$id");
										}*/

										}
							
							}
							$branch_ids++;

						}
						///die;
						// added by mhae
						meta_forward($path_to_root.'/purchasing/supplier_sign.php', "AddedID=".$_POST['reference']."");
					//meta_forward($_SERVER['PHP_SELF'], "AddedID=$id");
					

		}	

		set_global_connection_branch();

			
			/*if (count($dm_ids) > 0) {
					$rs_id_str = implode(',',$dm_ids);
					//display_error($rs_id_str);


				if (check_data() == true)
				{
				
					if ($_POST['supplier_id_aria']!=''){
							$aria_supp_ref=get_supplier_ref($_POST['supplier_id_aria']);
							//display_error($aria_supp_ref);
						}
						
					foreach ($dm_ids as $cleared_id)
					{
						
							//display_error($cleared_id);
							 
							$br_code=get_branchcode_($cleared_id);
							 
							begin_transaction();
							//display_error($br_code);
						
							switch_connection_to_branch_mysql($br_code);
							
							if ($_POST['po_no'] != '')
							{
								$_POST['effective_from'] = 'NULL';
								$_POST['effective_to'] = 'NULL';
								$_POST['just_once'] = '0';
							}
							
							$periods = input_num('periods');
							
							if (input_num('amount') != 0)
								$_POST['just_once'] = '1';
							
							if ($_POST['freq'] == 0)
								$periods = 0;
							else
								$periods -= 1;
								
							if ($periods == 0)
								$_POST['freq'] == 0;
							
							
							if ($_POST['supplier_id'] != '')
							{
								$supp_ref = $_POST['supplier_id'];
								$_POST['supplier_id_aria'] = check_my_suppliers($supp_ref);
							}
							//---------------------------------------------------------
							
							// else 
								// $_POST['supplier_id'] = $_POST['supplier_id_aria'];
							
							
							if ($_POST['sdma_id'] == '')
							{
								$id = insert_sdma($_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST['dm_date'],
									$_POST['freq'],$periods,input_num('amount'),input_num('disc_percent'),
									$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'], $_POST['receivable']);
									
								meta_forward($_SERVER['PHP_SELF'], "AddedID=$id");
							}
							else if (!$_POST['sdma_id'] == '')
							{
								$sql = "SELECT * FROM ".TB_PREF."sdma 
								WHERE id = ".$_POST['sdma_id']."
								AND approval_2 = 0";
								$res = db_query($sql);
								
								if (db_num_rows($res)==0)
								{
									display_error("You can not edit this Debit Memo because it may have been posted.");
									hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
									display_footer_exit();
								}
								
								$id = $_POST['sdma_id'];
								update_sdma($_POST['sdma_id'],$_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST['dm_date'],
									$_POST['freq'],$periods,input_num('amount'),input_num('disc_percent'),
									$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'], $_POST['receivable']);
									
								meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$id");
							}
					}
				}
			}
			
			set_global_connection_branch();*/
}

if (isset($_POST['Delete']))
{
	$sql = "SELECT * FROM ".TB_PREF."sdma 
			WHERE id = ".$_POST['sdma_id'];
	if ($_SESSION['wa_current_user']->username != 'admin' AND !$_SESSION['wa_current_user']->can_approve_sdma_1)
	{
		$sql .= " AND prepared_by = ".$_SESSSION['wa_current_user']->user;
	}
	$sql .=	" AND approval_2 = 0";
	// display_error($sql);
	$res = db_query($sql);
	
	if (db_num_rows($res)==0)
	{
		display_error("You can not delete this Debit Memo because it may have been posted or its not yours.");
		hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
		display_footer_exit();
	}
	else
	{
		delete_sdma($_POST['sdma_id']);
		meta_forward($_SERVER['PHP_SELF'], "DeletedID=".$_POST['sdma_id']);
	}
}

if (list_updated('amount_percentage') OR list_updated('just_once') OR isset($_POST['po_no']))
{
	global $Ajax;
	$Ajax->activate('main');
}


start_form();
$br_code = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];

if($br_code != 'srsn'){

	display_error('== PLEASE LOGIN TO NOVA ==');

}else{
	//display_error($_SESSION['wa_current_user']->user);

	div_start('main');
	// display_error($_POST['sdma_id']);
	if (isset($_POST['sdma_id']))
		hidden('sdma_id',$_POST['sdma_id']);


	if (!isset($_POST['just_once']))
		$_POST['just_once'] = 1;
		
	start_table("$table_style2");

	if ($_POST['sdma_id'] == ''){
		
		// added by mhae
		$sql = "SELECT counter FROM ".TB_PREF."counter
		WHERE code = 'SDMA'";
		$res = db_query($sql);
		$row = db_fetch($res);
		
	//	$sql = "Update ".TB_PREF."counter set counter=counter+1 WHERE code = 'SDMA'";
	//	db_query($sql);
		
		$ref = str_pad($row['counter'], 5, '000000', STR_PAD_LEFT);
		hidden('reference',$ref);
		label_cells('<font color=red>*Temporary Reference: </font>','SRSSAF '.$ref);
		
		//text_row('<font color=red>*Reference: </font>', 'reference', null, 20, 255);
	}else
	{
		hidden('reference',$_POST['reference']);
		label_cells('<font color=red>*Temporary Reference: </font>',$_POST['reference']);
	}

	ref_row('PO # (<i>if for 1 PO only</i>): ', 'po_no', null, null, true);
	// addded by mhae
	if($_SESSION['wa_current_user']->user == 888  OR $_SESSION['wa_current_user']->user == 633  OR $_SESSION['wa_current_user']->user == 730 OR $_SESSION['wa_current_user']->user == 642 OR $_SESSION['wa_current_user']->user == 651 OR $_SESSION['wa_current_user']->user == 886 OR $_SESSION['wa_current_user']->user == 654 OR $_SESSION['wa_current_user']->user == 905){
		supplier_list_ms_cells('Database Supplier (<i>if not in ARIA</i>):', 'supplier_id', null, 'Use Supplier below');
		//supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
	}else{
		purchaser_supplier_list_ms_cells('Database Supplier (<i>if not in ARIA</i>):', 'supplier_id', null, 'Use Supplier below');
		//purchaser_supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		
	}
	// supplier_list_row('<font color=red>*Supplier: </font>', 'supplier_id_aria', null, '');
	get_sdma_type_list_row_('<font color=red>*Type: </font>', 'sdma_type');

	yesno_list_row('<font color=red>*Amount/Percentage: </font>', 'amount_percentage', null, "Percentage", "Fixed Amount",true,true);
	date_row('<font color=red>*Debit Memo Effectivity Date: </font>','dm_date',null, null, 0, 0, $inc_years);;
	//hidden('dm_date',Today());



	if ($_POST['po_no'] == '')
	{
		if (!isset($_POST['dm_date']))
			$_POST['dm_date'] = begin_month(Today());

		// yesno_list_row('for 1 CV only: ', 'just_once', null, "YES", "NO",true,true);


		if ($_POST['just_once'] == 0)
		{
			date_row('<font color=red>*Effective from: </font>','effective_from');
			date_row('<font color=red>*Effective to: </font>','effective_to');
			hidden('periods',0);
			hidden('freq',0);
		}
		else
		{
			hidden('effective_from','');
			hidden('effective_to','');
			frequency_list_row("Frequency", 'freq', null);
			qty_cells('Periods (<i>repeat x times</i>):', 'periods', null, null, null, 0);
			amount_row('<font>*Fixed Amount (Apply to all branch): </font>','amount_all_branch');
		}
	}


		$sql = "SELECT * from transfers.0_branches_other_income order by name";
		//display_error($sql);
		$result=db_query($sql);
		$branch_count = 0;

		while($row = db_fetch($result))
		{
			if($row['id'] != 23 AND $row['id'] != 11){
				label_cells('<b>'.$row['name'].'</b>');
				hidden('selected_id'.$row['id']);
				//check_row("<b>".$row['name']."</b>",'selected_id'.$row['id'],null,false, '', "align='center'");
			
				if ($_POST['amount_percentage'] == 0)
				{
					if (!isset($_POST['dm_date']) OR $_POST['dm_date'] == '')
						$inc_years = 1001;
						
					amount_row('<font color=red>*Fixed Amount: </font>','amount'.$row['id']);
					hidden('disc_percent'.$row['id'],0);
					$_POST['just_once'] = 1;
					hidden('just_once'.$row['id'],$_POST['just_once']);
				//	yesno_list_cells('Receivable From Supplier ?','receivable'.$row['id']);
					
				}
				else
				{
					percent_row('<font color=red>*Percentage: </font>' ,'disc_percent'.$row['id']);
					hidden('amount'.$row['id'],0);
					$_POST['receivable'] = $_POST['just_once'] = 0;
					hidden('just_once'.$row['id'],$_POST['just_once']);
					hidden('receivable'.$row['id'],$_POST['just_once']);
				}
			}
			$branch_count ++;
		}

		
		hidden('branch_count',$branch_count);
		

	textarea_row('Comment: ', 'comment', '', 35, 3);
	end_table(1);
	div_end();


	if (isset($_POST['sdma_id']) AND $_POST['sdma_id'] != '')
	{
		submit_center('Process', _("<b>Update Supplier Debit Memo</b>"), true , '', 'default');
		br(5);
		echo '<center><font color=red><b>*will delete immediately</b> </font></center>';
		submit_center('Delete', _("<b>Delete Supplier Debit Memo</b>"), true , '', false);
	}
	else
		submit_center('Process', _("<b>Submit Supplier Debit Memo for Approval</b>"), true , '', 'default');
	end_form();

}



//------------------------------------------------------------------------------------------------

end_page();
?>
