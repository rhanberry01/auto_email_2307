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
$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Other Income Clearing"), false, false, "", $js);

$approve_id = find_submit('update_cust_payment_details');

start_form();
div_start('header');

$type = ST_CUSTPAYMENT;

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('Reference #:', 'dm_no');
		customer_list_cells('Customer :', 'supp_id', null, true);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();



if ($approve_id != -1)
{
global $Ajax;

$remarks=$_POST['remarks'.$approve_id];
$bank_account=$_POST['bank_account'.$approve_id];
$date_paid=$_POST['date_paid'.$approve_id];


$type = ST_CUSTPAYMENT;
$sqlapprove="SELECT a.*, b.debtor_ref FROM ".TB_PREF."cust_payment_details a, ".TB_PREF."debtors_master b 
WHERE type = '$type'
AND a.debtor_no = b.debtor_no 
AND amount > 0 
AND chk_date >= '".date2sql($_POST['start_date'])."' 
AND chk_date <= '".date2sql($_POST['end_date'])."'
AND id='$approve_id'
ORDER BY chk_date, id asc";
db_query($sqlapprove);
//display_error($sqlapprove);

$deposit_date=Today();
	
begin_transaction();

update_cust_payment_details($approve_id,date2sql($date_paid),$bank_account,$remarks,$payto);

//update_cust_payment_details($approve_id,date2sql($deposit_date),$bank_id,$remarks,$payto);


$sql="select * from 0_cust_payment_details where id='$approve_id' order by id asc";
$result=db_query($sql);

while($row = db_fetch($result))
{
$id=$row['id'];
$transno=$row['trans_no'];
$amount=$row['amount'];
$payment_type=$row['payment_type'];
$debtor_no=$row['debtor_no'];
$deposit_date=sql2date($row['deposit_date']);
$bank_id=$row['bank_id'];
}

$sqlcheck_in_transit="select cash_account, check_in_transit from ".TB_PREF."sales_gl_accounts";
$result_check_in_transit=db_query($sqlcheck_in_transit);
//display_error($sqlcheck_in_transit);
while ($accountrow = db_fetch($result_check_in_transit))
{
$check_in_transit=$accountrow["check_in_transit"];
$cash_in_transit=$accountrow["cash_account"];
}

	
if ($payment_type=='Cash')
{
//cash	
add_gl_trans_customer(ST_CUSTPAYMENT, $transno,$date_paid,$cash_in_transit, 0, 0, -$amount, $debtor_no,
"Cannot insert a GL transaction for the debtors account credit", $rate);

		$sql_cib="select account_code from ".TB_PREF."bank_accounts where id='$bank_id'";
		$result_cib=db_query($sql_cib);

		while ($accountrow = db_fetch($result_cib))
		{
		$cash_in_bank=$accountrow['account_code'];
		}
		
//cash			
add_gl_trans_customer(ST_CUSTPAYMENT, $transno,$date_paid,$cash_in_bank, 0, 0, $amount, $debtor_no,
"Cannot insert a GL transaction for the debtors account credit", $rate);
}

if ($payment_type=='Check')
{
//check
add_gl_trans_customer(ST_CUSTPAYMENT, $transno,$date_paid,$check_in_transit, 0, 0, -$amount, $debtor_no,
"Cannot insert a GL transaction for the debtors account credit", $rate);

		$sql_cib="select account_code from ".TB_PREF."bank_accounts where id='$bank_id'";
		$result_cib=db_query($sql_cib);

		while ($accountrow = db_fetch($result_cib))
		{
		$cash_in_bank=$accountrow['account_code'];
		}
		
//check
add_gl_trans_customer(ST_CUSTPAYMENT, $transno,$date_paid,$cash_in_bank, 0, 0, $amount, $debtor_no,
"Cannot insert a GL transaction for the debtors account credit", $rate);
}
commit_transaction();

$Ajax->activate('table_');
display_notification(_("The Transaction has been Cleared."));
}

	
/* original query
$sql = "SELECT a.*, b.debtor_ref FROM 0_cust_payment_details a, 0_debtors_master b 
		WHERE type = '$type'
		AND a.debtor_no = b.debtor_no 
		AND amount > 0
		AND deposited='0'
		";
		

if (trim($_POST['dm_no']) == '')
{
	$sql .= " AND chk_date >= '".date2sql($_POST['start_date'])."'
			  AND chk_date <= '".date2sql($_POST['end_date'])."'";
			  
	if ($_POST['supp_id'])
	{
		$sql .= " AND a.debtor_no = ".$_POST['supp_id'];
	}
}
else
{
	$sql .= " AND (ref_no LIKE ".db_escape('%'.$_POST['dm_no'].'%')." )";
}
$sql .= " ORDER BY chk_date";
*/
	
	
	
$sql = "select a.*, b.debtor_ref, c.type as c_type,
c.type_no as c_type_no,
c.tran_date as c_tran_date,
c.account as c_account,
c.memo_ as c_memo_,
c.amount as c_amount from 0_cust_payment_details as a
left join 0_debtors_master b 
on a.debtor_no = b.debtor_no
left join 0_gl_trans c
on a.trans_no=c.type_no
WHERE c.type ='$type' AND a.amount > 0  AND c.amount > 0 
AND a.deposited='0'";
		

if (trim($_POST['dm_no']) == '')
{
	$sql .= " AND chk_date >= '".date2sql($_POST['start_date'])."'
			  AND chk_date <= '".date2sql($_POST['end_date'])."'";
			  
	if ($_POST['supp_id'])
	{
		$sql .= " AND a.debtor_no = ".$_POST['supp_id'];
	}
}
else
{
	$sql .= " AND (ref_no LIKE ".db_escape('%'.$_POST['dm_no'].'%')." )";
}
$sql .= " group by id ORDER BY chk_date";



$res = db_query($sql);
//display_error($sql);


div_start('table_');
start_table($table_style2.' width=85%');
$th = array();
array_push($th, 'Date Paid', 'Trans #', 'Customer', 'Reference #','Type','Amount','Account','Date Deposit','Remarks','');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}


$k = 0;
while($row = db_fetch($res))
{
start_form();
	alt_table_row_color($k);
	label_cell(sql2date($row['chk_date']));
	label_cell(get_gl_view_str(ST_CUSTPAYMENT, $row["trans_no"], $row["trans_no"]));
	//label_cell($row['trans_no']);
	//label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['debtor_ref'] ,'nowrap');
	label_cell($row['ref_no']);
	label_cell($row['payment_type']);
	amount_cell($row['amount'],true);
	//label_cell(get_comments_string($type, $row['trans_no']));
	bank_accounts_list_cells('', bank_account.$row['id'], null);
	date_cells('', date_paid.$row['id']);
	text_cells('',remarks.$row['id'],null);
	$submit='update_cust_payment_details'.$row['id'];
	submit_cells($submit, 'Clear', "align=center", true, true,'ok.gif');
	end_row();
end_form();
}


end_table();
div_end();
end_form();



end_page();

?>