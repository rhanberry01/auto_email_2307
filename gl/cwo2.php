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
page(_($help_context = "CWO"), false, false, "", $js);

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
						
								function auto_create_cv($invoice_no)
								{
									$trans_no = $invoice_no;
									$type = 20;
									
									//==============GET APV TYPE 20
									$apv_header = get_apv_supp_trans($trans_no);
									$real_cv_trans[] = array(20, $trans_no, $apv_header['TotalAmount']);
									
									$payable_amount = $apv_header['TotalAmount'];
									$total_ewt_ex = 0;
									
									if ($apv_header['ewt'] > 0)
									{
											$total_ewt_ex += $apv_header['ewt'];
									}
									
									$dm_used = 0;
									//========================
									
										// get PO specific DM here
										//------------------------
										$exlude_dm = array();
										// check for percentage DM here
										$current_apv_amount = $apv_header['TotalAmount'];
										$percent_dm_res = get_percent_dm($apv_header['supplier_id'],sql2date($apv_header['del_date']));
										while($percent_dm_row = db_fetch($percent_dm_res))
										{
										$percent_dm_amount = ($current_apv_amount+$apv_header['ewt']) * ($percent_dm_row['disc_percent']/100);
										$p_dm_trans_no = create_dm_from_percentage_sdma($percent_dm_row['id'],$percent_dm_amount,$apv_header['reference']);
										$exclude_dm[] = $p_dm_trans_no;

										$percent_dm_amount = -$percent_dm_amount;
										$real_cv_trans[] = array(53, $p_dm_trans_no, $percent_dm_amount);
										$payable_amount += $percent_dm_amount; //dm amount is negative
										$dm_used ++;

										$current_apv_amount += $percent_dm_amount;
										}
										//-------------------- 

										//==============================GET CREDIT MEMOs
										$p_apv_cm_res = get_pending_apv_and_cm($apv_header['supplier_id'],$trans_no);
										while ($p_apv_cm_row = db_fetch($p_apv_cm_res))
										{
										// get percentage DM here -- use del date from apv
										$real_cv_trans[] = array($p_apv_cm_row['type'], $p_apv_cm_row['trans_no'], $p_apv_cm_row['TotalAmount']);
										$payable_amount += $p_apv_cm_row['TotalAmount'];

										if ($p_apv_cm_row['ewt'] > 0)
										{
											$total_ewt_ex += $p_apv_cm_row['ewt'];
										}

										// check for percentage DM here
										$current_apv_amount = $p_apv_cm_row['TotalAmount'];
										$percent_dm_res = get_percent_dm($apv_header['supplier_id'],sql2date($p_apv_cm_row['del_date']));
										while($percent_dm_row = db_fetch($percent_dm_res))
										{
										$percent_dm_amount = ($current_apv_amount+$p_apv_cm_row['ewt']) * ($percent_dm_row['disc_percent']/100);
										$p_dm_trans_no = create_dm_from_percentage_sdma($percent_dm_row['id'],$percent_dm_amount,$percent_dm_row['reference']);
										$exclude_dm[] = $p_dm_trans_no;

										$percent_dm_amount = -$percent_dm_amount;
										$real_cv_trans[] = array(53, $p_dm_trans_no, $percent_dm_amount);
										$payable_amount += $percent_dm_amount; //dm amount is negative
										$dm_used ++;

										$current_apv_amount += $percent_dm_amount;
										}
										//-------------------- 
										}
										//========================================================
								
										// ================GET DEBIT MEMOs
										$dm_res = get_unused_dm_fixed_price($apv_header['supplier_id'],$exclude_dm);

										//compare total APV to DM 
										$dm_count = db_num_rows($dm_res);

										while($dm_row = db_fetch($dm_res))
										{
										if ($payable_amount > abs($dm_row['TotalAmount']))
										{
										$real_cv_trans[] = array($dm_row['type'], $dm_row['trans_no'], $dm_row['TotalAmount']);
										$payable_amount += $dm_row['TotalAmount']; //dm amount is negative
										$dm_used ++;
										}
										}

									if ($dm_count > 0 AND $dm_used == 0) //there are pending DM but payable amount < any of DM amount/s
										return false;
										// else create a CV. not yet approved this time
										//===============================================
		
									
									//=======AUTO CREATE CV
									$cv_no = get_next_cv_no();
									
									$cv_id = insert_cv($cv_no,Today(),$payable_amount,PT_SUPPLIER,$apv_header['supplier_id'], 
										$real_cv_trans, sql2date($apv_header['due_date']), $total_ewt_ex);
										
									//=======CV approval auto approve
									$sql = "UPDATE ".TB_PREF."cv_header SET approved = 1
											WHERE id = $cv_id";
									db_query($sql,'failed to approve CV');
									
									add_audit_trail(99, $cv_id, Today(), 'CV approved');
									
									return $cv_id;
								
								}


if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_SUPPINVOICE;

   	display_notification_centered( _("CWO has been entered"));

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this CWO")));

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another CWO"));

	display_footer_exit();
}

if (isset($_POST['_DatePaid_changed'])) {
	$Ajax->activate('_ex_rate');
}

//----------------------------------------------------------------------------------------

function gl_payment_controls()
{
global $table_style2, $Refs;
	
	$home_currency = get_company_currency();

	start_form();

	start_outer_table($table_style2, 5);

	table_section(1);
	$refref = $Refs->get_next(ST_SUPPINVOICE);
	$refref = str_replace('NT','',$refref);
	//ref_row(_("APV No.:"), 'reference', '', $refref);
	hidden('reference',$refref);
    //date_row(_("Date:"), 'DatePaid', '', null, 0, 0, 0, null, true);
	supplier_list_row(_("Supplier:"), 'supp_id', null, false, false, false, true);
	text_row(_("PO #:"), 'po_num', null);
	text_row(_("SI #:"), 'si_num', null);
	//text_row(_("Receiving ID:"), 'rec_id', null);
	//text_row(_("CV ID:"), 'cv_id', null);
	amount_row(_("Amount:"), 'amount', null, null, $from_currency);

	end_outer_table(1); // outer table

    submit_center('Add',_("Enter"), true, '', 'default');

	end_form();
}

//----------------------------------------------------------------------------------------

function check_valid_entries()
{
	global $Refs;
	
	// if (!is_date($_POST['DatePaid'])) 
	// {
		// display_error(_("The entered date is invalid."));
		// set_focus('DatePaid');
		// return false;
	// }

	if ($_POST['supp_id']=='') 
	{
		display_error(_("Supplier Canot be empty."));
		set_focus('supp_id');
		return false;
	}
	
	if ($_POST['po_num']=='') 
	{
		display_error(_("PO Number Canot be empty."));
		set_focus('po_num');
		return false;
	}
	
	// if ($_POST['rec_id']=='') 
	// {
		// display_error(_("Receiving ID Canot be empty."));
		// set_focus('rec_id');
		// return false;
	// }
		
	// if ($_POST['cv_id']=='') 
	// {
		// display_error(_("CV ID Canot be empty."));
		// set_focus('cv_id');
		// return false;
	// }
	
	if (!check_num('amount', 0)) 
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}

    return true;
}

//----------------------------------------------------------------------------------------
function add_cwo_header($date, $supp_id, $po_num, $rec_id, $cv_id, $amount)
{
$sql = "INSERT INTO ".TB_PREF."cwo_header (c_date,c_sup_id,c_po_no,c_rec_id,c_cv_id,c_supp_trans_no,amount,voided)
VALUES ('".date2sql($date)."','$supp_id','$po_num','$rec_id','$cv_id','','$amount','0')";
//display_error($sql);	
db_query($sql,'failed to insert cwo header');
$id = db_insert_id();
return $id;
}

function update_cwo_header($cv_id,$c_id,$invoice_no)
{
	$sql = "UPDATE ".TB_PREF."cwo_header SET c_cv_id='$cv_id', c_supp_trans_no='$invoice_no' WHERE c_id=$c_id";
	db_query($sql,'failed to update cwo header');
	//display_error($sql);
}

function handle_add_deposit()
{
global $Refs;	
begin_transaction();
$accounts_payable = 2000; //accounts_payable
$advances_to_supplier = 1440; // advances_to_supplier

$current_id=add_cwo_header(Today(),$_POST['supp_id'], $_POST['po_num'],$_POST['rec_id']+0, $trans_no+0, input_num('amount'));
$invoice_no = add_supp_trans(ST_SUPPINVOICE, $_POST['supp_id'], Today(),add_days(Today(), 1),$_POST['reference'],$supp_ref='SI '.$_POST['si_num'].' -- P.O# '.$_POST['po_num'].' CWO',input_num('amount'), 0, 0,"",0,0,Today(),1,0,$_POST['po_num']);

$cv_id=auto_create_cv($invoice_no);
update_cwo_header($cv_id,$current_id,$invoice_no);


add_supp_invoice_item(ST_SUPPINVOICE, $invoice_no, $stock_id+0, $description='',$advances_to_supplier, input_num('amount'), $unit_tax+0, $quantity+0, $grn_item_id+0, $po_detail_item_id+0, $memo_='S.I# '.$_POST['si_num'].' P.O# '.$_POST['po_num'].' CWO',$err_msg="", $i_uom='',  $multiplier=1);

add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $accounts_payable, 0, 0,-input_num('amount'), $_POST['supp_id'], "", $rate);
add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $advances_to_supplier, 0, 0,input_num('amount'), $_POST['supp_id'], "", $rate);
//add_comments(ST_SUPPINVOICE, $invoice_no, Today(), $memo_='P.O # '.$_POST['po_num'].' CWO');
$Refs->save(ST_SUPPINVOICE, $invoice_no, $_POST['reference']);
commit_transaction();
meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
}
//----------------------------------------------------------------------------------------

if (isset($_POST['Add']))
{
	if (check_valid_entries() == true) 
	{
		handle_add_deposit();
	}
}

gl_payment_controls();
end_page();
?>