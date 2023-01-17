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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Details"), true);
function get_other_income($date, $bank, $amount)
{
	$date1 = date('Y-m-d',strtotime(add_days(sql2date($date), -7)));
	$date = date('Y-m-d',strtotime(add_days(sql2date($date), 2)));
		$sql = "SELECT * from (SELECT 'ALAMINOS' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_alaminos.0_gl_trans as gl LEFT JOIN srs_aria_alaminos.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'MANALO' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_antipolo_manalo.0_gl_trans as gl LEFT JOIN srs_aria_antipolo_manalo.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 
UNION ALL

SELECT 'QUEZON' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_antipolo_quezon.0_gl_trans as gl LEFT JOIN srs_aria_antipolo_quezon.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 
UNION ALL

SELECT 'BSILANG' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_b_silang.0_gl_trans as gl LEFT JOIN srs_aria_b_silang.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'BAGUMBONG' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_bagumbong.0_gl_trans as gl LEFT JOIN srs_aria_bagumbong.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'CAINTA' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_cainta.0_gl_trans as gl LEFT JOIN srs_aria_cainta.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'CAINTA2' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_cainta_san_juan.0_gl_trans as gl LEFT JOIN srs_aria_cainta_san_juan.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."'

UNION ALL

SELECT 'CAMARIN' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_camarin.0_gl_trans as gl LEFT JOIN srs_aria_camarin.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'COMEMBO' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_comembo.0_gl_trans as gl LEFT JOIN srs_aria_comembo.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'GAGALANGIN' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_gala.0_gl_trans as gl LEFT JOIN srs_aria_gala.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

 SELECT 'GRACEVILLE' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_graceville.0_gl_trans as gl LEFT JOIN srs_aria_graceville.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'IMUS' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_imus.0_gl_trans as gl LEFT JOIN srs_aria_imus.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL 

SELECT 'MALABON' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_malabon.0_gl_trans as gl LEFT JOIN srs_aria_malabon.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'RESTO' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_malabon_rest.0_gl_trans as gl LEFT JOIN srs_aria_malabon_rest.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'NAVOTAS' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_navotas.0_gl_trans as gl LEFT JOIN srs_aria_navotas.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'NOVA' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_nova.0_gl_trans as gl LEFT JOIN srs_aria_nova.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'PAATEROS' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_pateros.0_gl_trans as gl LEFT JOIN srs_aria_pateros.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'PUNTURIN' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_punturin_val.0_gl_trans as gl LEFT JOIN srs_aria_punturin_val.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'RETAIL' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_retail.0_gl_trans as gl LEFT JOIN srs_aria_retail.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'PEDRO' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_san_pedro.0_gl_trans as gl LEFT JOIN srs_aria_san_pedro.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."'

UNION ALL

SELECT 'TALON UNO' as branch,  oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_talon_uno.0_gl_trans as gl LEFT JOIN srs_aria_talon_uno.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'TONDO' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_tondo.0_gl_trans as gl LEFT JOIN srs_aria_tondo.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank."' 

UNION ALL

SELECT 'VALENZUELA' as branch, oi.bd_trans_no as 'bd_trans_no', oi.bd_amount as 'bd_amount', oi.bd_trans_date as 'bd_trans_date',  oi.bd_reconciled as 'bd_reconciled',  oi.bd_memo as 'bd_memo' FROM srs_aria_valenzuela.0_gl_trans as gl LEFT JOIN srs_aria_valenzuela.0_other_income_payment_header as oi on gl.type_no=oi.bd_trans_no and gl.type = oi.bd_trans_type where oi.bd_trans_date>='".$date1."' and oi.bd_trans_date<='".$date."' and gl.account = '".$bank.
"') as a Order by a.bd_trans_date ASC";
//display_error($sql);	
	$res = db_query($sql);
	return $res;

}
function get_cash_on_deposit($date, $bank, $amount)
{
	$date1 = date('Y-m-d',strtotime(add_days(sql2date($date), -7)));
	$date = date('Y-m-d',strtotime(add_days(sql2date($date), 2)));
		$sql = "SELECT * FROM cash_deposit.0_cash_dep_header_new where cd_bank_account_code='".$bank."' and cd_date_deposited >='".$date1."' and cd_date_deposited <='".$date."' Order By cd_date_deposited asc";
		//and cd_gross_amount = ".$amount.""; 
//display_error($sql);	
	$res = db_query($sql);
	return $res;

}
function get_branch_name_($code)
{
	 	$sql = "SELECT name from transfers.0_branches where code='".$code."'";
		$res = db_query($sql);
		$row = db_fetch_row($res);
		return $row[0];

}
start_form(true);
div_start('header');

//display_error($db);
if($_GET['bank'] == '1')
	$bank = '10102299';
else
	$bank = '1020021';
	
$res = get_other_income($_GET['date'], $bank, $_GET['amount']);
$res1 = get_cash_on_deposit($_GET['date'], $bank, $_GET['amount']);
echo"<table style='width:100%'><tr><td class='menu_group'><b>Other Income</b></td>
				<td class='menu_group'>Cash On Deposit</td></tr><tr><td  width='50%' >";

start_table($table_style2.' width=95%');
$th = array();
	
array_push($th, 'Trans #', 'Branch','Trans Date','Bank','Memo','Amount', 'Is Cleared?');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	//display_footer_exit();
}


	//display_error($approver);

while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell($row['bd_trans_no']);
	label_cell($row['branch']);
	label_cell(sql2date($row['bd_trans_date']));
	label_cell($row['bd_memo']);
	label_cell($bank == '10102299' ? 'AUB':'Metro Bank');
	label_cell(number_format2($row['bd_amount'],2));
	label_cell($row['bd_reconciled'] == 1 ? 'YES':'NO');
	
	end_row();
}
end_table();
echo"</td><td width='50%'>";
start_table($table_style2.' width=95%');
$th = array();
	
array_push($th, 'Trans #', 'Branch','Trans Date','Bank','Payment Type','Amount', 'Is Cleared?');


if (db_num_rows($res1) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	//display_footer_exit();
}


	//display_error($approver);

while($row = db_fetch($res1))
{
	alt_table_row_color($k);
	label_cell($row['cd_id']);
	label_cell( get_branch_name_($row['cd_br_code']));
	label_cell(sql2date($row['cd_date_deposited']));
	label_cell($row['cd_bank_account_code'] == '10102299' ? 'AUB':'Metro Bank');
	label_cell($row['cd_payment_type']);
	label_cell(number_format2($row['cd_gross_amount'],2));
	label_cell($row['cd_cleared'] == 1 ? 'YES':'NO');
	
	end_row();
}
end_table();
echo"</td></tr>";
echo"</table>";

div_end();
br();
br();
br();

end_form();
end_page(true);
?>