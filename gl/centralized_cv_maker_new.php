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
page(_($help_context = "Create CWO"), false, false, "", $js);

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------

function get_pen_dm($supp_id)
{
	// $sql = "SELECT SUM(ov_amount) as t_amount FROM 0_dm_summary_from_2015_to_2017
// WHERE tran_date>='2015-01-01' and tran_date<='2015-12-31'
// and supp_name='$supp_id'
// GROUP BY supp_name";


	// $sql = "SELECT b.*, ROUND(dm_2015+dm_2016+dm_2017,2) as t_dm FROM (SELECT 
	// supp_name,
	// CASE WHEN YEAR(tran_date)='2015' THEN ROUND(SUM(ov_amount),2) ELSE 0 end as 'dm_2015',
	// CASE WHEN YEAR(tran_date)='2016' THEN ROUND(SUM(ov_amount),2) ELSE 0 end as 'dm_2016',
	// CASE WHEN YEAR(tran_date)='2017' THEN ROUND(SUM(ov_amount),2) ELSE 0 end as 'dm_2017'
	// FROM `0_dm_summary_from_2015_to_2017`
	// where supp_name='$supp_id'
	// GROUP BY supp_name, YEAR(tran_date) ) as b";
	
	
	$sql = "SELECT supp_name, sum(dm_2015) as 'dm_2015', sum(dm_2016) as 'dm_2016', sum(dm_2017) as 'dm_2017'
	FROM (
	SELECT 
	supp_name,
	CASE WHEN YEAR(tran_date)='2015' THEN ROUND(SUM(ov_amount),2) ELSE 0 end as 'dm_2015',
	CASE WHEN YEAR(tran_date)='2016' THEN ROUND(SUM(ov_amount),2) ELSE 0 end as 'dm_2016',
	CASE WHEN YEAR(tran_date)='2017' THEN ROUND(SUM(ov_amount),2) ELSE 0 end as 'dm_2017'
	FROM `0_dm_summary_from_2015_to_2017`
	where supp_name='$supp_id'
	GROUP BY supp_name, YEAR(tran_date) ) as b";

	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);

	return $row; 
}

function get_pen_dm_2015($supp_id)
{
	$sql = "SELECT SUM(ov_amount) as t_amount FROM 0_dm_summary_from_2015_to_2017
WHERE tran_date>='2015-01-01' and tran_date<='2015-12-31'
and supp_name='$supp_id'
GROUP BY supp_name";
	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);
	$t_amount=$row['t_amount'];
	return $t_amount; 
}


function get_pen_dm_2016($supp_id)
{
	$sql = "SELECT SUM(ov_amount) as t_amount FROM 0_dm_summary_from_2015_to_2017
WHERE tran_date>='2016-01-01' and tran_date<='2016-12-31'
and supp_name='$supp_id'
GROUP BY supp_name";
	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);
	$t_amount=$row['t_amount'];
	return $t_amount; 
}

function get_pen_dm_2017($supp_id)
{
	$sql = "SELECT SUM(ov_amount) as t_amount FROM 0_dm_summary_from_2015_to_2017
WHERE tran_date>='2017-01-01' and tran_date<='2017-12-31'
and supp_name='$supp_id'
GROUP BY supp_name";
	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);
	$t_amount=$row['t_amount'];
	return $t_amount; 
}

function get_pen_apv($supp_id)
{
	$sql = "SELECT SUM(ov_amount) as t_amount FROM 0_apv_summary_from_2016_to_present
WHERE tran_date>='2016-01-01'
and supp_name='$supp_id'
GROUP BY supp_name";
	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);
	$t_amount=$row['t_amount'];
	return $t_amount; 
}

						
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
	$cvid = $_GET['CV_id'];
	
	$trans_type = ST_SUPPINVOICE;

   	display_notification_centered( _("CWO has been entered"));

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this CWO")));
	br();
	display_note(get_cv_view_str($cvid, _("View CV for this CWO")));
	
   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another CWO"));

	display_footer_exit();
}

if (isset($_POST['_DatePaid_changed'])) {
	$Ajax->activate('_ex_rate');
}


//----------------------------------------------------------------------------------------

function check_valid_entries()
{
	global $Refs;

		if ($_POST['po_num']!='') 
		{
			$num=check_cwo_header($_POST['po_num']);
			if ($num>0) 
			{
			display_error(_("P.O Number: PO".$_POST['po_num']." has a CWO already."));
			set_focus('po_num');
			return false;
			}
		}

	if ($_POST['supp_id']=='') 
	{
		display_error(_("Supplier Cannot be empty."));
		set_focus('supp_id');
		return false;
	}
	
	if ($_POST['po_num']=='') 
	{
		display_error(_("PO Number Cannot be empty."));
		set_focus('po_num');
		return false;
	}
	
	if (!check_num('amount', 0)) 
	{
		display_error(_("The entered amount is invalid or less than zero."));
		set_focus('amount');
		return false;
	}
	
    return true;
}

//----------------------------------------------------------------------------------------
function check_cwo_header($po_num)
{
$sql = "SELECT * FROM ".TB_PREF."cwo_header WHERE c_po_no='$po_num'";
//display_error($sql);	
$res=db_query($sql);
$num=mysql_num_rows($res);
return $num;
}

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

//check_my_suppliers($_POST['supp_id']);
$supp_id=get_supplier_id_by_supp_ref($_POST['supp_id']);

if ($supp_id!='') {
$current_id=add_cwo_header(Today(),$supp_id, $_POST['po_num'],$_POST['rec_id']+0, $trans_no+0, input_num('amount'));
$invoice_no = add_supp_trans(ST_SUPPINVOICE, $supp_id, Today(),add_days(Today(), 1),$_POST['reference'],$supp_ref='SI '.$_POST['si_num'].' -- P.O# '.$_POST['po_num'].' CWO',input_num('amount'), 0, 0,"",0,0,Today(),1,0,$_POST['po_num']);

$cv_id=auto_create_cv($invoice_no);
update_cwo_header($cv_id,$current_id,$invoice_no);


add_supp_invoice_item(ST_SUPPINVOICE, $invoice_no, $stock_id+0, $description='',$advances_to_supplier, input_num('amount'), $unit_tax+0, $quantity+0, $grn_item_id+0, $po_detail_item_id+0, $memo_='S.I# '.$_POST['si_num'].' P.O# '.$_POST['po_num'].' CWO',$err_msg="", $i_uom='',  $multiplier=1);

add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $accounts_payable, 0, 0,-input_num('amount'), $supp_id, "", $rate);
add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $advances_to_supplier, 0, 0,input_num('amount'), $supp_id, "", $rate);
//add_comments(ST_SUPPINVOICE, $invoice_no, Today(), $memo_='P.O # '.$_POST['po_num'].' CWO');
$Refs->save(ST_SUPPINVOICE, $invoice_no, $_POST['reference']);
commit_transaction();
meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no&CV_id=$cv_id");
}
else{
display_error('INVALID SUPPLIER.');
}
}
//----------------------------------------------------------------------------------------

if (isset($_POST['Add']))
{
	if (check_valid_entries() == true) 
	{
		handle_add_deposit();
	}
}

//----------------------------------------------------------------------------------------

function gl_payment_controls()
{
	global $table_style2, $Refs;
	
	$home_currency = get_company_currency();

	start_outer_table($table_style2, 5);

	table_section(1);
	$refref = $Refs->get_next(ST_SUPPINVOICE);
	$refref = str_replace('NT','',$refref);
	hidden('reference',$refref);
	text_cells_ex(_("PO #:"), 'po_number', null,'','','','','',true);
	text_row(_("SI #:"), 'si_num', null);
	end_outer_table(1); // outer table

}


start_form();
div_start('details');
gl_payment_controls();

// if ($_POST['po_number']!=null)
// {
//isset($_POST['po_number'])
//$_POST['po_num']!=null
br(1);
global $Ajax;


					
				$sqlx="
				
				SELECT DISTINCT supp_name FROM 0_pending_dms WHERE status=1 and is_ok=0

				";
				$resx = db_query($sqlx);
				$counter=db_num_rows($resx);
				//display_error($counter);

					
					
					// if ($counter>0) {
						div_start('table_');
						display_heading("P.O DETAILS (From New P.O Program)");
						br();
						start_table($table_style2);
						$th = array();
						$from_head = array('<input type="checkbox" id="select_all">',"","Supplier","Total APV (Present)","2015 DM","2016 DM","2017 DM","STATUS");
						table_header($from_head);
						$k = 0;
						//display_error($rowx['supp_name']);
						
						while($rowy = db_fetch($resx))
						{
			
							//display_error($rowy['supp_name']);
							$c ++;
							alt_table_row_color($k);
								echo '<td align="center">
									<input type="checkbox" class="select_one" name="petty_cash[]" value="'.$details['trans_no'].'">
								</td>';
							label_cell($c,'align=right');
							label_cell($rowy['supp_name']);
							label_cell(number_format2($t_apv=get_pen_apv($rowy['supp_name'])));
							
							
							$dm_trans_row=get_pen_dm($rowy['supp_name']);
							label_cell(number_format2($t_dm_2015=abs($dm_trans_row['dm_2015'])));
							label_cell(number_format2($t_dm_2016=abs($dm_trans_row['dm_2016'])));
							label_cell(number_format2($t_dm_2017=abs($dm_trans_row['dm_2017'])));

							
							if($t_apv>($t_dm_2015+$t_dm_2016+$t_dm_2017)){
								label_cell('READY TO DEDUCT');
							}
							else{
								label_cell('');
							}
							
						end_row();
						$ex_apv+=$t_apv;
						$ex_dm_2015+=$t_dm_2015;
						$ex_dm_2016+=$t_dm_2016;
						$ex_dm_2017+=$t_dm_2017;
						}

						start_row();

						label_cell(''); // for checkbox 
						label_cell(''); // for checkbox 
						label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
						label_cell("<font color=#880000><b>".number_format2(abs($ex_apv),2)."<b></font>",'align=right');
						label_cell("<font color=#880000><b>".number_format2(abs($ex_dm_2015),2)."<b></font>",'align=right');
						label_cell("<font color=#880000><b>".number_format2(abs($ex_dm_2016),2)."<b></font>",'align=right');
						label_cell("<font color=#880000><b>".number_format2(abs($ex_dm_2017),2)."<b></font>",'align=right');
						label_cell('');
						//label_cell("<font color=#880000><b>".number_format2(abs($tax_total),2)."<b></font>",'align=right');
						//print_r($sub_t[$gl['gl_used']]);
						//label_cell("<font color=#880000><b>".number_format2(abs($t_total),2)."<b></font>",'align=right');
						end_row();	
						end_table();
						start_table();
						submit_center('Add',_("Create CWO"), true, '', 'default');
						end_table();

					// }
					// else {
					// display_error('Invalid PO Number, No Result Found.');
					// }
				

$Ajax->activate('details');	

br(2);
//}

// else {
// echo "<center><font color='blue'> Please Insert a valid P.O Number.</font></center>";
// br();
//}
div_end();
start_table();
submit_center('Add',_("Create CWO"), true, '', 'default');
end_table();
end_form();
end_page();
?>