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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Other Income Clearing"), false, false, "", $js);

$cleared_id = find_submit('clear_selected');


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
$sql = "select * from 0_other_income_payment_header";
if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}


if ($_POST['payment_type']!= '')
{
//display_error($_POST['payment_type']);
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

if ($_POST['receipt_type']!= '0')
{
//display_error($_POST['payment_type']);
	$sql .= " AND bd_receipt_type='".$_POST['receipt_type']."'";
}


	$sql .= " AND bd_cleared='0' ORDER BY bd_trans_date, bd_trans_no";

	$res = db_query($sql);
	//display_error($sql);

	
div_start('table_');
start_table($table_style2.' width=92%');
$th = array();
array_push($th, 'Date Paid', 'Trans #','RF/OR/SI #', 'Customer','Type','Amount','Account','Date Deposit','Remarks','');


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
start_form();
	alt_table_row_color($k);
	label_cell(sql2date($row['bd_trans_date']));
	label_cell(get_gl_view_str(ST_BANKDEPOSIT, $row["bd_trans_no"], $row["bd_trans_no"]));
	label_cell($row['bd_or'] ,'nowrap');
	label_cell($row['bd_payee'] ,'nowrap');
	label_cell($row['bd_payment_type']);
	amount_cell($row['bd_amount'],false);
	//label_cell(get_comments_string($type, $row['trans_no']));
	bank_accounts_list_cells('', 'bank_account'.$row['bd_id'], null);
	date_cells('', 'date_paid'.$row['bd_id']);
	text_cells('','remarks'.$row['bd_id'],null);
	$submit='clear_selected'.$row['bd_id'];
	submit_cells($submit, 'Clear', "align=center", true, true,'ok.gif');
	end_row();
end_form();

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
	label_cell('');
	label_cell('');
	label_cell('');
end_row();

end_table();
div_end();
end_form();
end_page();
?>