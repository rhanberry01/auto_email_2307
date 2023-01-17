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
 
 page('Edit Supplier Debit Memo Agreement', false, false,'', $js);

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
	
	/* if (!check_sdma_reference($_POST['reference']) AND $_POST['sdma_id'] == '')
	{
		display_error('Reference is already entered.');
		return false;
	} */
	
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
	
	/*if ($_POST['dm_date'] == '' AND $_POST['po_no'] == '')
	{
		display_error('DM Date should not be empty');
		return false;
	}
	
	if (!is_date($_POST['dm_date']) AND $_POST['po_no'] == '')
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

if (isset($_POST['Update'])){
	$saf = explode('SRSSAF ',$_POST['reference']);
	meta_forward($path_to_root.'/purchasing/supplier_sign.php', "AddedID=".$saf[1]."");
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
	
				if (check_data() == true)
				{
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
							//display_error($data_amount);
							$data_amount_all_branch ='amount_all_branch';
							$data_dm_date ='dm_date';
							$data_receivable=0;
							$amount = input_num($data_amount);
							
							if($amount != 0 || input_num($disc_percent) != 0){
 							//	display_error('ss');
							//	begin_transaction();
								if($branch_ids != 23 AND $branch_ids != 11){
							//	display_error($amount.'--'.input_num($disc_percent));
								$br_code = get_branchcode_($branch_ids);
							//	display_error($br_code.'--'.$_POST['supplier_id']);
								switch_connection_to_branch_mysql($br_code);
								//display_error($_POST[$reference].'</br>'.$_POST[$data_amount].'</br>'.$_POST[$data_dm_date].'</br>'.$_POST[$data_receivable].'</br>');


									if ($_POST['po_no'] != '')
									{
										$_POST['effective_from'] = 'NULL';
										$_POST['effective_to'] = 'NULL';
										$_POST['just_once'] = '0';
									}

									$periods = input_num($periods);
							
									if ($amount != 0 || input_num($disc_percent) !=0)
										$_POST['just_once'] = '1';
									
									if ($_POST['freq'] == 0)
										$periods = 0;
									else
										$periods -= 1;
										
									//		display_error($_POST['freq'].'----'.$periods);
									if ($periods == 0)
										$_POST['freq'] == 0;

									if ($_POST['supplier_id'] != '')
									{
										$supp_ref = $_POST['supplier_id'];
										$_POST['supplier_id_aria'] = check_my_suppliers($supp_ref);
									}
											 $sql = "SELECT * FROM ".TB_PREF."sdma 
											WHERE reference = '".$_POST['reference']."'";
											 $res = db_query($sql);
											 if (db_num_rows($res)==0)
											{

												$id = insert_sdma($_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST[$data_dm_date],$_POST['freq'],$periods,$amount,input_num($disc_percent),$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'], $data_receivable);
											}else{ 
										
												 $sql = "SELECT * FROM ".TB_PREF."sdma 
												WHERE reference = '".$_POST['reference']."'
												AND approval_2 = 0";
												 $res = db_query($sql);
												 if (db_num_rows($res)==0)
												{

													display_error("You can not edit this Debit Memo because it may have been posted.");
													hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
													display_footer_exit();
												} 
											
												$reference = $_POST['reference'];
												update_sdma_per_reference($_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST['dm_date'],
													$_POST['freq'],$periods,$amount,input_num($disc_percent),
													$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'],0);
											}
												
										
								}
							}
							$branch_ids++;
						}
					//die;
						meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$reference");
					//meta_forward($path_to_root.'/purchasing/supplier_sign.php', "AddedID=".$_POST['reference']."");

					

		}
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
	 display_error($sql);
	 die;
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

/* if (list_updated('amount_percentage') OR list_updated('just_once') OR isset($_POST['po_no']))
{
	global $Ajax;
	$id = $_GET['reference'];
	$Ajax->activate('main');
}
 */

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
	
	hidden('reference',$_GET['reference']);
	label_cells('<font color=red>*Reference: </font>',$_GET['reference']);
	
	$csql = "SELECT a.* FROM ".$_GET['branch'].".".TB_PREF."sdma a WHERE reference = '".$_GET['reference']."'";
	$cquery = db_query($csql);			
	$crow=db_fetch($cquery);

	ref_row('PO # (<i>if for 1 PO only</i>): ', 'po_no', null, $crow['po_no'], true);
	// addded by mhae
	$ssql = "SELECT supp_ref FROM ".$_GET['branch'].".".TB_PREF."suppliers WHERE supplier_id = ".$crow['supplier_id'];
	$squery = db_query($ssql);			
	$srow=db_fetch($squery);
	
	if($_SESSION['wa_current_user']->user == 1  OR $_SESSION['wa_current_user']->user == 633  OR $_SESSION['wa_current_user']->user == 730 OR $_SESSION['wa_current_user']->user == 642 OR $_SESSION['wa_current_user']->user == 651 OR $_SESSION['wa_current_user']->user == 886 OR $_SESSION['wa_current_user']->user == 654 OR $_SESSION['wa_current_user']->user == 905){
		supplier_list_ms_cells('Database Supplier (<i>if not in ARIA</i>):', 'supplier_id', $srow['supp_ref']);
		//supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
	}else{
		purchaser_supplier_list_ms_cells('Database Supplier (<i>if not in ARIA</i>):', 'supplier_id',$srow['supp_ref']);
		//purchaser_supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		
	}
	// supplier_list_row('<font color=red>*Supplier: </font>', 'supplier_id_aria', null, '');
	get_sdma_type_list_row_('<font color=red>*Type: </font>', 'sdma_type', $crow['sdma_type']);
	$_POST['dm_date'] = date('m/d/Y', strtotime($crow['dm_date']));
	//yesno_list_row('<font color=red>*Amount/Percentage: </font>', 'amount_percentage', null, "Percentage", "Fixed Amount",true,true);
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
			frequency_list_row("Frequency", 'freq', $crow['frequency']);
			qty_cells('Periods (<i>repeat x times</i>):', 'periods', ($crow['period']+1), null, null, 0);
			//amount_row('<font>*Fixed Amount (Apply to all branch): </font>','amount_all_branch');
		}
	}


		$sql = "SELECT * from transfers.0_branches_other_income  order by name";
		//display_error($sql);
		$result=db_query($sql);
		$branch_count = 0;

		while($row = db_fetch($result))
		{
			
			if($row['id'] != 23 AND $row['id'] != 11){
				$sql1 = "SELECT a.* FROM ".$row['aria_db'].".".TB_PREF."sdma a WHERE reference = '".$_GET['reference']."'";
				$query1 = db_query($sql1);			
				$row1=db_fetch($query1);
				
					if($row1){
						label_row('<b>'.$row['name'].'</b>');
						hidden('selected_id'.$row['id']);
						if ($row1['amount'] != 0)
						{
							if (!isset($_POST['dm_date']) OR $_POST['dm_date'] == '')
								$inc_years = 1001;
								
							amount_row('<font color=red>*Fixed Amount: </font>','amount'.$row['id'], number_format($row1['amount'], 2));
							
						}
						else
						{
							percent_row('<font color=red>*Percentage: </font>' ,'disc_percent'.$row['id'], number_format($row1['disc_percent'],2));
			
						} 
					}else{
						label_row('<b>'.$row['name'].'</b>');
						hidden('selected_id'.$row['id']);
						if(!$crow['amount'] == 0){
							amount_row('<font color=red>*Fixed Amount: </font>','amount'.$row['id']);
							hidden('disc_percent'.$row['id'],0);
						}else{
							percent_row('<font color=red>*Percentage: </font>' ,'disc_percent'.$row['id']);
							hidden('amount'.$row['id'],0);
						}
					}
			}
			$branch_count ++;
		}

		
		hidden('branch_count',$branch_count);
		

	textarea_row('Comment: ', 'comment', $crow['comment'], 35, 3);
	end_table(1);
	submit_center('Process', _("<b>Update Supplier Debit Memo</b>"), true , '', 'default');
	div_end();
	$saf = explode('SRSSAF ',$_GET['reference']);
	//echo $saf[1];

	$sql = "SELECT supplierName FROM srs_aria_nova.".TB_PREF."sdma_supplier WHERE reference = 'SRSSAF".$saf[1]."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if(file_exists($path_to_root . "/purchasing/doc_signs/Sup(SAF".$saf[1].").png")){
		$purchaserSign  = $path_to_root . "/purchasing/doc_signs/Sup(SAF".$saf[1].").png";
		echo '<br><center><td><b>Supplier`s Sign:</b></td><br>
		<td><img src="'.$purchaserSign.'" style="width:450px;height:150px;"></td>';
		if (db_num_rows($res1)==0){
			echo '<br><center><td><b>Supplier`s Name:</b> '.$row['supplierName'].'</td>';
		}
	
	}else{
		echo '<br><center><td><b>Supplier`s Name: </b>'.$row['supplierName'].'</td>';
	}
		br(2);
		//echo '<center><font color=red><b>*will delete immediately</b> </font></center>';
		//submit_center('Delete', _("<b>Delete Supplier Debit Memo</b>"), true , '', false);
		submit_center('Update', _("<b>Update Supplier Signature</b>"), true , '', 'default');

	end_form();

}



//------------------------------------------------------------------------------------------------

end_page();
?>
