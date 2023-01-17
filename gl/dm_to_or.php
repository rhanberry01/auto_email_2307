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

include($path_to_root . "/includes/db_pager2.inc");
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
page(_($help_context = "Debit Memo to OR"), false, false, "", $js);

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
//$selected_id = find_submit('selected_id');

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_SUPPINVOICE;

   	display_notification_centered( _("CWO has been entered"));

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this CWO")));

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another CWO"));

	display_footer_exit();
}

//----------------------------------------------------------------------------------------

function gl_payment_controls()
{
global $table_style2, $Refs;
	
	$home_currency = get_company_currency();
	br();
	start_outer_table($table_style2, 5);
	table_section(1);
	$refref = $Refs->get_next(ST_SUPPINVOICE);
	$refref = str_replace('NT','',$refref);
	//ref_row(_("APV No.:"), 'reference', '', $refref);
	hidden('reference',$refref);
    //date_row(_("Date:"), 'DatePaid', '', null, 0, 0, 0, null, true);
	supplier_list_row(_("Supplier:"), 'supp_id', null, false, true, false, true);
	//text_row(_("Receiving ID:"), 'rec_id', null);
	//text_row(_("CV ID:"), 'cv_id', null);
	//amount_row(_("Amount:"), 'amount', null, null, $from_currency);
	end_outer_table(1); // outer table
}



function check_voided($trans_no)
{
	$sql = "SELECT memo_
				FROM 0_voided
				WHERE type = 53
				AND id = ".$trans_no;
				//echo $sql.'<p>';
	$query = db_query($sql);
	$count = db_num_rows($query);
	//echo $count.'<p>';
	return $count;
}

//----------------------------------------------------------------------------------------
function cv_no_link($cv_id,$cv_no)
{
	// return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		// pager_link(_("Purchase Return"),
			// "/purchasing/supplier_credit.php?New=1&invoice_no=".
			// $row['trans_no'], ICON_CREDIT)
			// : '';
	global $path_to_root;
	return "<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?
				cv_id=".$cv_id."'onclick=\"javascript:openWindow(this.href,this.target); return false;\"><b>" .
				$cv_no. "&nbsp;</b></a> ";
}
//----------------------------------------------------------------------------------------

function add_or_header($or_trans_no,$or_type,$or_supplier,$or_reference,$or_supp_reference,$or_tran_date,$or_date_processed,$or_amount,$or_processed_by) {
$sql = "INSERT INTO ".TB_PREF."or_header(or_trans_no,or_type,or_supplier,or_reference,or_supp_reference,or_tran_date,or_date_processed,or_amount,or_processed_by)				
VALUES ('$or_trans_no','$or_type','$or_supplier','$or_reference','$or_supp_reference','$or_tran_date','$or_date_processed','$or_amount','$or_processed_by')";		
//db_query($sql);
//display_error($sql);
}


start_form();
if (isset($_POST['Add']))
{
	global $Ajax;
	$prefix = 'selected_id';
	$dm_ids = array();
	foreach($_POST as $postkey=>$postval)
    {
		if (strpos($postkey, $prefix) === 0)
		{
			$id = substr($postkey, strlen($prefix));
			$dm_ids[] = $id;
			print_r($dm_ids);
		}
	}
	if (count($dm_ids) > 0) {
		$rs_id_str = implode(',',$dm_ids);
		//display_error($rs_id_str);
		foreach ($dm_ids as $approve_id)
		{
			$supplier_id=$_POST['supplier_id'.$approve_id];
			$ov_amount=abs($_POST['ov_amount'.$approve_id]);
			$gl_account=$_POST['account'.$approve_id];
			$debit_account=$_POST['debit_account'.$approve_id];
			$credit_account=$_POST['credit_account'.$approve_id];
			$oi_credit_account=$_POST['oi_credit_account'.$approve_id];
			$output_vat_account=$_POST['output_vat_account'.$approve_id];
			$output_vat_percent=$_POST['output_vat_percent'.$approve_id];
			$tran_date1=sql2date($_POST['tran_date1'.$approve_id]);
			$counter=$_POST['counter'.$approve_id];
			$reference=$_POST['reference'.$approve_id];
			$supp_reference=$_POST['supp_reference'.$approve_id];
			$tran_date=$_POST['tran_date'.$approve_id];
			
			$oi_amount=$ov_amount/1.12;
			$tax_amount=$oi_amount * ($output_vat_percent/100);
			
			add_gl_trans_supplier(53, $approve_id, $tran_date1, $output_vat_account, 0, 0,-$tax_amount, $supplier_id, "", $rate);
			
			$sql1 = "UPDATE ".TB_PREF."gl_trans SET 
					amount = '-$oi_amount',
					account='".$oi_credit_account."'
					WHERE type = '53'
					AND account='".$gl_account."'
					AND counter='".$counter."'
					AND type_no = '".$approve_id."'";
			//display_error($sql);
			db_query($sql1, 'failed to update gl_trans');
			
			$sql2 = "SELECT amount,counter FROM  ".TB_PREF."gl_trans
					WHERE type = '53'
					AND account='2000'
					AND type_no = '".$approve_id."'";
			$res=db_query($sql2, 'failed to update gl_trans');
			$row=db_fetch($res);
			$counter=$row['counter'];
			$c_amount=$row['amount'];
			
			$sql3 = "UPDATE ".TB_PREF."gl_trans SET 
					amount = ".abs($c_amount).",
					account='".$credit_account."'
					WHERE type = '53'
					AND account='".$debit_account."'
					AND counter='".$counter."'
					AND type_no = '".$approve_id."'";
			//display_error($sql);
			db_query($sql3, 'failed to update gl_trans');
					
			$approver_id=$_SESSION["wa_current_user"]->user;
			
			add_or_header($approve_id,53,$supplier_id,$reference,$supp_reference,$tran_date,date2sql(Today()),$ov_amount,$approver_id);
		}
	$_POST['supp_id']=$supplier_id;
	$Ajax->activate('table_'); 
	$Ajax->activate('supp_id'); 
	display_notification('Selected Debit Memo succesfully processed.');
	}
	else{
		display_error('Nothing to process!');
	}
}

gl_payment_controls();
br(2);

div_start('table_');
if (list_updated('supp_id') or isset($_POST['Add'])) {
	$sql="SELECT st.*,gl.*,sd.* FROM ".TB_PREF."supp_trans as st
	LEFT JOIN ".TB_PREF."gl_trans as gl
	on st.trans_no=gl.type_no 
	LEFT JOIN ".TB_PREF."sdma_type as sd
	on gl.account=sd.credit_account
	where gl.type='53' and sd.oi_credit_account!=''
	and st.type='53'
	and !st.ov_amount>=0 
	and st.supplier_id='".$_POST['supp_id']."'
	GROUP BY st.trans_no";
	//display_error($sql);
	$dm_res=db_query($sql);
	
	$dm_count = db_num_rows($dm_res);
	//display_error($dm_count);

		if ($dm_count>0) {

			display_heading("Debit Memo (Promo Fund Liabilities) List");
			br();
			start_table($table_style2 .'width=75%');
			$th = array('','Trans#','Description','Reference','Supplier Ref','Other ref','Trans Date','Amount','CV#','');
			table_header($th);
					
			while($dm_row = db_fetch($dm_res))
			{
			$c ++;
			start_row();
			alt_table_row_color($k);
			label_cell($c,'align=right');
			label_cell(get_gl_view_str(53,$dm_row['trans_no'],$dm_row['trans_no']));
			label_cell($dm_row['memo_'],'nowrap');
			label_cell($dm_row['reference'],'nowrap');
			label_cell($dm_row['supp_reference'],'nowrap');
			label_cell($dm_row['special_reference'],'nowrap');
			label_cell(sql2date($dm_row['tran_date']),'nowrap');
			amount_cell(abs($dm_row['amount']));
			$cv_num=get_cv_no($dm_row['cv_id']);
			label_cell(cv_no_link($dm_row['cv_id'],$cv_num));
			
			$count=check_voided($dm_row['trans_no']);
			if ($count==0) {
			check_cells('',"selected_id".$dm_row['trans_no']);
			}
			else {
			label_cell("</font color='red'>Voided</font>");
			}
			//check_cells('',"selected_id".$dm_row['trans_no']);
			hidden('supplier_id'.$dm_row['trans_no'],$dm_row['supplier_id']);
			hidden('ov_amount'.$dm_row['trans_no'],$dm_row['amount']);
			hidden('debit_account'.$dm_row['trans_no'],$dm_row['debit_account']);
			hidden('credit_account'.$dm_row['trans_no'],$dm_row['credit_account']);
			hidden('oi_credit_account'.$dm_row['trans_no'],$dm_row['oi_credit_account']);
			hidden('output_vat_account'.$dm_row['trans_no'],$dm_row['output_vat_account']);
			hidden('output_vat_percent'.$dm_row['trans_no'],$dm_row['output_vat_percent']);
			hidden('tran_date1'.$dm_row['trans_no'],$dm_row['tran_date']);
			hidden('account'.$dm_row['trans_no'],$dm_row['account']);
			hidden('counter'.$dm_row['trans_no'],$dm_row['counter']);
			hidden('reference'.$dm_row['trans_no'],$dm_row['reference']);
			hidden('supp_reference'.$dm_row['trans_no'],$dm_row['supp_reference']);
			hidden('tran_date'.$dm_row['trans_no'],$dm_row['tran_date']);
			
			end_row();
			}
			end_table();
			start_table();
			br(2);
			submit_center('Add',_("Process"), true, '', true, ICON_ADD);
			end_table();
		}
		else {
			display_error('Selected Supplier has no Debit Memo.');
		}

}
			div_end();
end_form();
end_page();
?>