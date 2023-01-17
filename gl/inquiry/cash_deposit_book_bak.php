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
	
page(_($help_context = "Sales Book Summary"), false, false, "", $js);

$cleared_id = find_submit('clear_selected');

//====================================start heading=========================================
start_form();
// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');


global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_table("width=85% $table_style2"); // outer table

	table_section();
		get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
		cash_dep_trans_type_list_cells('Transaction Type:', 'trans_type', '', '', '',false,'');
		bank_accounts_list_cells2('Bank Account:', 'bank_account', null,'',true);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
	
end_table(); // outer table
div_end();

br(2);

//====================================display table=========================================
//$sql = "select * from cash_deposit.".TB_PREF."cash_dep_header";

function get_t_sales($start_date,$end_date){
$sql2 = "select * from ".TB_PREF."salestotals";
$sql2 .= " WHERE ts_date_remit >= '".date2sql($start_date)."' AND ts_date_remit <= '".date2sql($end_date)."'";
//display_error($sql2);
$res=db_query($sql2);
$row = db_fetch($res);
// while($row = db_fetch($res))
// {
	$cash=$row['ts_cash'];
	$over=$row['ts_over'];
	$debit=$row['ts_debit'];
	$credit=$row['ts_credit'];
	$check=$row['ts_check'];
	//display_error($cash);
//}
return array($cash, $over, $debit,$credit,$check);
}

$br_code=$_POST['from_loc'];

switch_connection_to_branch($br_code);

$sql = "select cd_sales_date,cd_trans_type,sum(on_hand) as on_hand, 
sum(in_bank) as in_bank, sum(on_hand)+sum(in_bank) as gross 
from (SELECT cd_sales_date, cd_trans_type,
case WHEN cd_cleared='0' then sum(cd_gross_amount) else 0 end as on_hand, 
case WHEN cd_cleared='1' then sum(cd_gross_amount) else 0 end as in_bank 
FROM cash_deposit.0_cash_dep_header 
WHERE cd_sales_date>='".date2sql($_POST['start_date'])."' AND  cd_sales_date<='".date2sql($_POST['end_date'])."'";

if ($_POST['from_loc']!= '')
{
	//display_error($_POST['payment_type']);
	$sql .= " AND cd_br_code='".$_POST['from_loc']."'";
}

if ($_POST['trans_type']!= '')
{
	//display_error($_POST['payment_type']);
	$sql .= " AND cd_trans_type='".$_POST['trans_type']."'";
}

if ($_POST['bank_account']!= '')
{
	//display_error($_POST['payment_type']);
	$sql .= " AND cd_bank_account_code='".$_POST['bank_account']."'";
}

$sql .= " GROUP BY cd_trans_type,cd_cleared,cd_sales_date) as x
GROUP BY cd_trans_type,cd_sales_date ORDER BY cd_sales_date ";
//display_error($sql);

$res = db_query($sql);

display_heading("Sales Book Summary of ".$_POST['start_date']."");	
br();

div_start('table_');
start_table($table_style2.' width=70%');
$th = array();
//'Type','Amount Deposited', 'Cash in Bank', 'Uncleared', 


//$sales=get_t_sales($_POST['start_date'],$_POST['end_date']);
//display_error($sales['0']);

array_push($th,'Sales Date','Trans Type','Amount Deposited', 'Cash in Bank','Uncleared');


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
	label_cell(sql2date($row['cd_sales_date']));
	
		
			if ($row['cd_trans_type']=='61'){
			label_cell('Sales');
			}
			else if ($row['cd_trans_type']=='62'){
			label_cell('CR/DR Card');
			}
			else if ($row['cd_trans_type']=='2'){
			label_cell('Other Income');
			}
	

	
	
	
			// if ($row['cd_payment_type_id']=='1'){
			// amount_cell($sales[0],false);
			// }
			// else {
			// label_cell('');
			// }
	amount_cell($gross=$row['gross'],false);
	amount_cell($row['in_bank'],false);
	amount_cell($row['on_hand'],false);
	
			// if ($row['cd_payment_type_id']=='1'){
			// //amount_cell($sales[0],false);
			// label_cell('');
			// }
			// else {
			// label_cell('');
			// }
	
end_row();
end_form();

$t_gross+=$row['gross'];
$t_in_bank+=$row['in_bank'];
$t_on_hand+=$row['on_hand'];
}

start_row();
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	//label_cell("<font color=#880000><b>".number_format2(abs($sales[0]),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_gross),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_in_bank),2)."<b></font>",'align=right');
	label_cell("<font color=red><b>".number_format2(abs($t_gross-$t_in_bank),2)."<b></font>",'align=right');
	//label_cell("<font color=red><b>".number_format2(abs($sales[0]-$t_in_bank),2)."<b></font>",'align=right');
end_row();
end_table();
div_end();
end_form();
end_page();
?>