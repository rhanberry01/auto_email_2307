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

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
ini_set('memory_limit', '-1');
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "RF to OR"), false, false, "", $js);

$cleared_id = find_submit('clear_selected');

function for_o_update($id,$tag)
{
	$sql = "UPDATE ".TB_PREF."other_income_payment_header SET bd_or_checked = $tag
				WHERE bd_id = $id";
			//display_error($sql);
	db_query($sql,'failed to update status');
}

$for_op_id = find_submit('_for_op');
if ($for_op_id != -1)
{
	global $Ajax;
	set_time_limit(0);

	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'for_op') === 0)
		{
		$id = substr($postkey, strlen('for_op'));
		$id_ = explode(',', $id);
		//display_error("t_net_amount==>".$_POST['t_net_amount'.$id_[0]]);
		$_POST['sub_amount']+=$_POST['t_net_amount'.$id_[0]];
		}
	}
	
	//tagging 
	for_o_update($for_op_id,check_value('for_op'.$for_op_id));
	set_focus('for_op'.$for_op_id);
	
	$Ajax->activate('sub_amount'); 
}

$check_all_op_id = find_submit('_check_all_op');
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
			
			for_o_update($id,check_value('check_all_op100'));
		}
	}
	$Ajax->activate('table_');
	$Ajax->activate('sub_amount'); 
}



if(isset($_POST['approve_selected']))
{
		$remarks=$_POST['remarks'];
		$or_num=$_POST['or_num'];
		$date_paid=$_POST['date_paid'];
	
		global $Ajax;
		
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
		
		$c_id_str = implode(',',$c_ids);

			foreach ($c_ids as $approve_id)
			{
				//display_error($gl_type);

				$date_cleared=Today();
				begin_transaction();

				$sql="select * from ".TB_PREF."other_income_payment_header where bd_id='$approve_id' order by bd_id asc";
				//display_error($sql);
				$result=db_query($sql);

				while($row = db_fetch($result))
				{
					$id=$row['bd_id'];
					$transno=$row['bd_trans_no'];
					$payment_type=$row['bd_payment_type'];
					$debtor_no=$row['bd_payee_id'];
					$bd_amount=$row['bd_amount'];
	
				}
				
				$total_amount+=$bd_amount;
				
				
				$sql = "UPDATE ".TB_PREF."other_income_payment_header SET 
				bd_official_receipt='$or_num',
				bd_or_checked ='2' WHERE bd_id = ". $approve_id;
				//display_error($sql);
				db_query($sql);
				
				commit_transaction();
				$Ajax->activate('table_');
				display_notification(_("The Transaction has been Cleared."));

			}
			
			// $payment_type_id='1';
			// $payment_type="cash";
			// $payment_from='Miscellaneous';
			// $payee_id=0;
			// $payment_to_bank=0;
			// $payee_branch_id=0;
			// $payment_from_id=0;
			// $payee_branch='';
			// $gross_amount=0;
			// $total_wt=0;
			// $total_oi=0;


			// // $total_amount=0;
			// $total_vat=round($total_amount/1.12*.12,2);
			
	// $trans_no = get_next_trans_no(3);
	// // do the source account postings
	// //-------------
	// $ref=$trans_no;
	// display_error($ref);


			// $sql = "INSERT INTO ".TB_PREF."other_income_payment_header(bd_payment_type_id,bd_payment_type,bd_trans_date,bd_trans_type,bd_trans_no,
			// bd_reference,bd_payment_from_id,bd_payment_from,bd_payment_to_bank,bd_payee_id,bd_payee,bd_cust_branch_id,bd_cust_branch,bd_gross_amount,bd_amount,bd_vat,bd_wt,bd_oi,bd_or,bd_memo,bd_date_deposited,bd_date_cleared,bd_cleared,bd_receipt_type)				
			// VALUES ('$payment_type_id','$payment_type','".date2sql($date_paid)."','3','0','0','$payment_from_id','$payment_from','$payment_to_bank','$payee_id',".db_escape($payee).",'$payee_branch_id','$payee_branch','$total_amount','$total_amount','$total_vat','$total_wt','$total_oi','$or_num',".db_escape($remarks).",'0000-00-00','0000-00-00','0','OR')";		
			// //db_query($sql,'unable to add bank deposit header');
			
			
			// $sql = "INSERT INTO ".TB_PREF."other_income_payment_details(bd_det_date,bd_det_type,bd_det_trans_no,bd_det_ref,bd_det_gl_code,bd_det_amount,bd_det_wt,bd_det_ov,bd_det_memo)				
			// VALUES ('".date2sql($date_paid)."','3','0','0','4021','$total_amount','0','$total_vat',".db_escape($remarks).")";		
			// //db_query($sql,'unable to add other income payment details');
			// display_error($sql);
}






//====================================start heading=========================================
start_form();
if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');


global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

div_start('pmt_header');

	start_table("$table_style2"); // outer table
		ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
end_table(); // outer table

div_end();

br();

$sql = "
SELECT oh.*, od.bd_det_gl_code FROM 0_other_income_payment_header as oh
LEFT JOIN 0_other_income_payment_details as od
on oh.bd_reference=od.bd_det_ref
where oh.bd_receipt_type='RF'
and od.bd_det_gl_code='4021'
and oh.bd_cleared=1
and bd_or_checked!=2
";
	$sql .= "AND oh.bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND oh.bd_trans_date <= '".date2sql($_POST['end_date'])."'
			  AND od.bd_det_date >= '".date2sql($_POST['start_date'])."'
			  AND od.bd_det_date <= '".date2sql($_POST['end_date'])."'
			  ";
	$res = db_query($sql);
	//display_error($sql);
br();

start_form();
start_table();
	//bank_accounts_list_row('Account', 'bank_account', null);
	date_row('OR Date:', 'date_paid');
	text_row('OR Number:','or_num',null);
	text_row('Memo:','remarks',null);
		//gl_all_accounts_list_cells('', 'exp_type'.$row['bd_id'], '', false, false, "All Accounts");
end_table();	
br();
div_start('table_');
start_table($table_style2.' width=75%');
$th = array();
//array_push($th, 'Date Paid', 'Trans #','RF/OR/SI #', 'Customer','Type','Amount','Account','Date Deposit','Remarks');
array_push($th, 'Date Paid', 'Trans #','RF #', 'Payee','Account','Amount');
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
	label_cell(get_gl_view_str(ST_BANKDEPOSIT, $row["bd_trans_no"], $row["bd_trans_no"]));
	label_cell($row['bd_or'] ,'nowrap');
	label_cell($row['bd_payee'] ,'nowrap');
	//label_cell($row['bd_payment_type']);
	//get_other_income_type_list_cells('','rec_type'.$row["bd_id"],8,true);
	label_cell(html_entity_decode(get_gl_account_name($row["bd_det_gl_code"])));
	//gl_all_accounts_list_cells('', 'gl_type'.$row["bd_id"],$row["bd_det_gl_code"]);
	text_cells(null, 'bd_amount'.$row['bd_id'],$row['bd_amount'], 11, 20);
	
	
		if ($row['bd_or_checked'] == 1)
		{
		$_POST['for_op'.$row['bd_id']] = 1;
		}
		else
		{
		unset($_POST['for_op'.$row['bd_id']]);
		}

		check_cells('','for_op'.$row['bd_id'],null,true, '', "align='center'");
		hidden('for_check_all_op'.$row['bd_id'],$row['bd_id']);
	
	end_row();

$t_amount+=$row['bd_amount'];
}

start_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell('');
end_row();

end_table();
div_end();
br();
start_table();
submit_cells('approve_selected', 'Tag RF to OR', "align=center", true, true,'ok.gif');

end_table();
div_end();
end_form();
end_page();
?>