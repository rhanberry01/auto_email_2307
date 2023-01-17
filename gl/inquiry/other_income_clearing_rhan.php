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
	
page(_($help_context = "Other Income Clearing"), false, false, "", $js);

$cleared_id = find_submit('clear_selected');
$approve_delete = find_submit('selected_del');

if ($approve_delete != -1) {
global $Ajax;
delete_line($approve_delete);
$Ajax->activate('table_');
}

function delete_line($del_id)
{
	
	begin_transaction();

$sql="select * from ".TB_PREF."other_income_payment_header where bd_id='$del_id' order by bd_id asc";
//display_error($sql);
$result=db_query($sql);

while($row = db_fetch($result))
{
$id=$row['bd_id'];
$transno=$row['bd_trans_no'];
}

if($transno!=''){
$cash_dep_sql = "SELECT cd_id FROM cash_deposit.".TB_PREF."cash_dep_header
WHERE cd_trans_type = '2'
AND cd_trans_no = '$transno'";
$res = db_query($cash_dep_sql);
$row=db_fetch($res);
$cash_dep_id=$row['cd_id'];

$sqlupdate_status="UPDATE ".TB_PREF."bank_statement_bpi
SET type='0', reference='0', cleared='0' where 
reference='$cash_dep_id' and type='2'";
//display_error($sqlupdate_status);
db_query($sqlupdate_status);

$sqlupdate_status="UPDATE ".TB_PREF."bank_statement_metro
SET type='0', reference='0', cleared='0' where 
reference='$cash_dep_id' and type='2'";
//display_error($sqlupdate_status);
db_query($sqlupdate_status);

$sqldeldep_header = "Delete FROM cash_deposit.".TB_PREF."cash_dep_header
WHERE cd_trans_type = '2'
AND cd_trans_no ='$transno'";
db_query($sqldeldep_header);
	
	
$sql = "DELETE FROM ".TB_PREF."bank_trans WHERE type='2' AND trans_no='$transno'";
db_query($sql, "Could not delete bank trans");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."other_income_payment_header WHERE bd_trans_type='2' AND bd_trans_no='$transno'";
db_query($sql, "Could not delete Other income payment header.");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."other_income_payment_details WHERE bd_det_type='2' AND bd_det_trans_no='$transno'";
db_query($sql, "Could not delete Other income payment details.");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."bank_deposit_cheque_details WHERE type='2' AND bank_trans_id='$transno'";
db_query($sql, "Could not delete bank deposit cheque details.");
//display_error($sql);

$sql = "DELETE FROM ".TB_PREF."gl_trans WHERE type='2' AND type_no='$transno'";
db_query($sql, "Could not delete gl transaction.");
}
// $type_no=$_POST['trans_no'];
// $type=2;
// void_gl_trans($type, $type_no, true);	
	
		commit_transaction();
	
display_notification("Deleting Other Income Transaction is Successful!");

}

function for_o_update($id,$tag)
{
	$sql = "UPDATE ".TB_PREF."other_income_payment_header SET checked = $tag
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
$bank_account=$_POST['bank_account'];
$date_paid=$_POST['date_paid'];


// display_error($remarks);
// display_error($bank_account);
// display_error($date_paid);

	
	
	// $remarks=$_POST['remarks'];
	// if ($remarks == ''){
		// display_error(_("Remarks cannot be empty."));
		// set_focus('remarks');
	// }
	
	
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
		//display_error($c_id_str);
		
				// $gl_type=0;
				// $bd_vat=0;
				// $bd_wt=0;
				// $bd_amount=0;
		
			//check if INVOICE# is emtpy.
			foreach ($c_ids as $approve_id)
			{
				
				$bd_vat=0;
				$bd_wt=0;
				$bd_amount=0;
				
				
				$gl_type=$_POST['gl_type'.$approve_id];
				$bd_vat=$_POST['bd_vat'.$approve_id];
				$bd_wt=$_POST['bd_wt'.$approve_id];
				$bd_amount=$_POST['bd_amount'.$approve_id];
			
				//display_error($gl_type);

				$date_cleared=Today();
				begin_transaction();

				$sql="select * from ".TB_PREF."other_income_payment_header where bd_id='$approve_id' order by bd_id asc";
				display_error($sql);
				$result=db_query($sql);

				while($row = db_fetch($result))
				{
					$id=$row['bd_id'];
					$transno=$row['bd_trans_no'];
					$payment_type=$row['bd_payment_type'];
					$debtor_no=$row['bd_payee_id'];
				}
				
				
				$bd_gross_amount=$bd_amount+$bd_wt;
				$bd_oi=$bd_gross_amount-$bd_vat;
				
				
				if($transno!=''){
					
					$sql = "UPDATE ".TB_PREF."other_income_payment_header SET 
					bd_gross_amount='$bd_gross_amount',
					bd_amount='$bd_amount',
					bd_vat='$bd_vat',
					bd_wt='$bd_wt',
					bd_oi='$bd_oi'
					WHERE bd_trans_no = ". $transno;
					db_query($sql);
					
					$sql = "UPDATE ".TB_PREF."other_income_payment_details SET 
					bd_det_gl_code ='$gl_type',
					bd_det_amount='$bd_amount',
					bd_det_ov='$bd_vat',
					bd_det_wt='$bd_wt'
					WHERE bd_det_trans_no = ". $transno;
					db_query($sql);
					
					//update oi account
					$sql = "UPDATE ".TB_PREF."gl_trans SET 
					account ='$gl_type', 
					amount='-$bd_oi'
					WHERE type=2 and account not in ('1010','2310','1400') and amount<0 and type_no = ". $transno;
					db_query($sql);
					
					
					//update coh account
					$sql="SELECT amount FROM 0_gl_trans
					where type='2'
					and account='1010'
					and type_no= ". $transno;
					$result= db_query($sql, "failed to get id.");
					$row = db_fetch($result);
					$gl_bd_amount1=abs($row['amount']);
					
					if (($gl_bd_amount1!=0 or $gl_bd_amount1!='') and $bd_amount!=0){
					$sql = "UPDATE ".TB_PREF."gl_trans SET 
					amount='$bd_amount'
					WHERE type=2 and account in ('1010') and amount>0 and type_no = ". $transno;
					db_query($sql);
					}
					else if (($gl_bd_amount1==0 or $gl_bd_amount1=='') and $bd_vat!=0){
					add_gl_trans(ST_BANKDEPOSIT, $transno, $date_paid, 1010, 0, 0, $remarks,$bd_amount, null, $person_type_id, $person_id);
					}
					
					
					//update cwtax account
					$sql="SELECT amount FROM 0_gl_trans
					where type='2'
					and account='1400'
					and type_no= ". $transno;
					$result= db_query($sql, "failed to get id.");
					$row = db_fetch($result);
					$gl_bd_amount2=abs($row['amount']);
					
					if (($gl_bd_amount2!=0 or $gl_bd_amount2!='') and $bd_wt!=0){
					$sql = "UPDATE ".TB_PREF."gl_trans SET 
					amount='$bd_wt'
					WHERE type=2 and account in ('1400') and amount>0 and type_no = ". $transno;
					db_query($sql);
					}
					else if (($gl_bd_amount2==0 or $gl_bd_amount2=='') and $bd_wt!=0){
					add_gl_trans(ST_BANKDEPOSIT, $transno, $date_paid, 1400, 0, 0, $remarks,$bd_wt, null, $person_type_id, $person_id);
					}

					//update output-vat account
					$sql="SELECT amount FROM 0_gl_trans
					where type='2'
					and account='2310'
					and type_no= ". $transno;
					$result= db_query($sql, "failed to get id.");
					$row = db_fetch($result);
					$gl_bd_amount3=abs($row['amount']);
					
					if (($gl_bd_amount3!=0 or $gl_bd_amount3!='') and $bd_vat!=0){
					$sql = "UPDATE ".TB_PREF."gl_trans SET 
					amount='-$bd_vat'
					WHERE type=2 and account in ('2310') and amount<0 and type_no = ". $transno;
					db_query($sql);
					}
					else if (($gl_bd_amount3==0 or $gl_bd_amount3=='') and $bd_vat!=0){
					add_gl_trans(ST_BANKDEPOSIT, $transno, $date_paid, 2310, 0, 0, $remarks,-$bd_vat, null, $person_type_id, $person_id);
					}

				}

				update_bank_deposit_cheque_details($transno,date2sql($date_paid),$remarks);	
				update_bank_trans2($bank_account, date2sql($date_paid), $transno,$bd_amount);
				add_gl_trans(ST_BANKDEPOSIT, $transno, $date_paid, 1010, 0, 0, $remarks,-$bd_amount, null, $person_type_id, $person_id);

				$sql_cib="select account_code from ".TB_PREF."bank_accounts where id='$bank_account'";
				//display_error($sql_cib);
				$result_cib=db_query($sql_cib);

				while ($accountrow = db_fetch($result_cib))
				{
				$cash_in_bank=$accountrow['account_code'];
				}
							
				add_gl_trans(ST_BANKDEPOSIT, $transno, $date_paid, $cash_in_bank, 0, 0, $remarks,$bd_amount, null, $person_type_id, $person_id);
				$desc='Cleared';
				add_audit_trail(ST_BANKDEPOSIT, $transno, $date_paid,$desc);

				update_other_income_payment_header($approve_id,date2sql($date_paid),date2sql($date_cleared),$bank_account,$remarks,$payto);
				
				
				$sql = "UPDATE ".TB_PREF."other_income_payment_header SET 
				checked ='2' WHERE bd_id = ". $approve_id;
				db_query($sql);
				
				commit_transaction();
				$Ajax->activate('table_');
				display_notification(_("The Transaction has been Cleared."));

			}
}



function update_other_income_payment_header($cleared_id,$date_paid,$date_cleared,$bank_id,$remarks,$payto)	
{		
	 $sql = "UPDATE ".TB_PREF."other_income_payment_header 
	 SET bd_cleared = '1',
	 bd_payment_to_bank='$bank_id',
	 bd_date_deposited='$date_paid',
	 bd_date_cleared='$date_cleared'
	WHERE bd_id = '$cleared_id'";

db_query($sql);
//display_error($sql);
}

function update_bank_deposit_cheque_details($cleared_id,$date_paid,$remarks)	
{		
	 $sql = "UPDATE ".TB_PREF."bank_deposit_cheque_details 
	 SET deposited='1',
	 deposit_date='$date_paid',
	 remark='$remarks'
	 WHERE bank_trans_id = '$cleared_id'";

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

function update_bank_trans2($bank_account, $date_paid, $cleared_id,$bd_amount)
{
$type=ST_BANKDEPOSIT;
	$sql = "UPDATE ".TB_PREF."bank_trans SET bank_act = '$bank_account', trans_date='$date_paid',amount='-$bd_amount'
				WHERE trans_no = '$cleared_id' AND type='$type'";
	db_query($sql);
}


//====================================start heading=========================================
start_form();
if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');


global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_outer_table("width=85% $table_style2"); // outer table

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

br();

//====================================if cleared_id=========================================
if ($cleared_id != -1)
{
global $Ajax;

$remarks=$_POST['remarks'.$cleared_id];
$bank_account=$_POST['bank_account'.$cleared_id];
$date_paid=$_POST['date_paid'.$cleared_id];

$date_cleared=Today();
begin_transaction();

$sql="select * from ".TB_PREF."other_income_payment_header where bd_id='$cleared_id' order by bd_id asc";
//display_error($sql);
$result=db_query($sql);

while($row = db_fetch($result))
{
$id=$row['bd_id'];
$transno=$row['bd_trans_no'];
$amount=$row['bd_amount'];
$payment_type=$row['bd_payment_type'];
$debtor_no=$row['bd_payee_id'];
}

update_bank_deposit_cheque_details($transno,date2sql($date_paid),$remarks);	
update_bank_trans($bank_account, date2sql($date_paid), $transno);
add_gl_trans(ST_BANKDEPOSIT, $transno, $date_paid, 1010, 0, 0, $remarks,-$amount, null, $person_type_id, $person_id);

$sql_cib="select account_code from ".TB_PREF."bank_accounts where id='$bank_account'";
//display_error($sql_cib);
$result_cib=db_query($sql_cib);

while ($accountrow = db_fetch($result_cib))
{
$cash_in_bank=$accountrow['account_code'];
}
			
add_gl_trans(ST_BANKDEPOSIT, $transno, $date_paid, $cash_in_bank, 0, 0, $remarks,$amount, null, $person_type_id, $person_id);
$desc='Cleared';
add_audit_trail(ST_BANKDEPOSIT, $transno, $date_paid,$desc);

update_other_income_payment_header($cleared_id,date2sql($date_paid),date2sql($date_cleared),$bank_account,$remarks,$payto);

commit_transaction();
$Ajax->activate('table_');
display_notification(_("The Transaction has been Cleared."));
}

//====================================display table=========================================
$sql = "select oh.*, od.bd_det_gl_code, at.user from 0_other_income_payment_header as oh
left join 0_other_income_payment_details as od
on oh.bd_trans_no=od.bd_det_trans_no
left join 0_audit_trail as at
on oh.bd_trans_no=at.trans_no

";
if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE oh.bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND oh.bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (oh.bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND oh.bd_payee_id='".$_POST['person_id']."'";
}


if ($_POST['payment_type']!= '')
{
//display_error($_POST['payment_type']);
	$sql .= " AND oh.bd_payment_type_id='".$_POST['payment_type']."'";
}

if ($_POST['receipt_type']!= '0')
{
//display_error($_POST['payment_type']);
	$sql .= " AND oh.bd_receipt_type='".$_POST['receipt_type']."'";
}


	$sql .= " AND bd_payment_type_id!=3 AND at.type=2 AND at.description='Paid' AND oh.bd_cleared='0' and user =830 ORDER BY oh.bd_trans_date, oh.bd_trans_no";

	$res = db_query($sql);
	//display_error($sql);

br();



start_form();
start_table();
	bank_accounts_list_row('Account', 'bank_account', null);
	date_row('Date Deposit', 'date_paid');
	text_row('Remarks','remarks',null);
		//gl_all_accounts_list_cells('', 'exp_type'.$row['bd_id'], '', false, false, "All Accounts");
end_table();	
br();
div_start('table_');
start_table($table_style2.' width=92%');
$th = array();
//array_push($th, 'Date Paid', 'Trans #','RF/OR/SI #', 'Customer','Type','Amount','Account','Date Deposit','Remarks');
array_push($th, 'Created by','Date Paid', 'Trans #','RF/OR/SI #', 'Payee','Payment Type','Account','Output Vat','Wtax','Amount','Delete');
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
	
	$u = get_user($row['user']);
	$approver_real_name = $u['real_name'];

	label_cell($approver_real_name ,'nowrap');
	label_cell(sql2date($row['bd_trans_date']));
	label_cell(get_gl_view_str(ST_BANKDEPOSIT, $row["bd_trans_no"], $row["bd_trans_no"]));
	label_cell($row['bd_or'].''.$row['bd_official_receipt'] ,'nowrap');
	label_cell($row['bd_payee'] ,'nowrap');
	label_cell($row['bd_payment_type']);
	//get_other_income_type_list_cells('','rec_type'.$row["bd_id"],8,true);
	
	gl_all_accounts_list_cells('', 'gl_type'.$row["bd_id"],$row["bd_det_gl_code"]);

	text_cells(null, 'bd_vat'.$row['bd_id'],$row['bd_vat'], 11, 20);
	text_cells(null, 'bd_wt'.$row['bd_id'],$row['bd_wt'], 11, 20);
	text_cells(null, 'bd_amount'.$row['bd_id'],$row['bd_amount'], 11, 20);
	
	
	// amount_cell($row['bd_vat'],false);
	// amount_cell($row['bd_wt'],false);
	// amount_cell($row['bd_amount'],false);
	
	
	//label_cell(get_comments_string($type, $row['trans_no']));
	
	//	date_cells('', 'date_paid'.$row['bd_id']);
	//	text_cells('','remarks'.$row['bd_id'],null);
	// $submit='clear_selected'.$row['bd_id'];
	// submit_cells($submit, 'Clear', "align=center", true, true,'ok.gif');
	
	$selected_del='selected_del'.$row["bd_id"];
	submit_cells($selected_del, _(""), "align=center",_('Delete'), true,ICON_DELETE);
	
	
		if ($row['checked'] == 1)
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
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	//label_cell('');
	//label_cell('');
	//label_cell('');
end_row();

end_table();
div_end();
br();
start_table();
submit_cells('approve_selected', 'Approve Selected', "align=center", true, true,'ok.gif');

end_table();
div_end();
end_form();
end_page();
?>