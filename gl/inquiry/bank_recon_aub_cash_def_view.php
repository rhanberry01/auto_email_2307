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
function get_branch_($code, $ref_no)
{
	global $db_connections;
		$sql="SELECT * from cash_deposit.".TB_PREF."cash_dep_header_new
		WHERE cd_id IN(".$ref_no.")";
		display_error($sql);
		$res = db_query($sql);
		return $res;
		
	/* 	$sql = "SELECT aria_db from transfers.0_branches where code='".$code."'";
		$res = db_query($sql);
		$row = db_fetch_row($res);
		
		$sql="SELECT * from ".$row[0].".".TB_PREF."other_income_payment_header
		WHERE  = ".$ref_no;
		display_error($sql);
		$res = db_query($sql);
		return $res;
		//return $row[0]; */
	

}

start_form();
div_start('header');

//display_error($db);
$res = get_branch_($_GET['branch_code'],$_GET['ref_no']);

start_table($table_style2.' width=95%');
$th = array();
	
array_push($th,'Trans #','Payment Type', 'Sales Date','Trans Date','Trans Type','Gross Amount', 'Date Deposited', 'Date Cleared','Cleared','Bank Account Code');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
}


$k = 0;
$nos = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell($row['cd_id']);
	label_cell($row['cd_payment_type']);
	label_cell(sql2date($row['cd_sales_date']));
	label_cell(sql2date($row['cd_trans_date']));
	label_cell($row['cd_trans_type']);
	label_cell(number_format2($row['cd_gross_amount'],2));
	label_cell(sql2date($row['cd_date_deposited']));
	label_cell(sql2date($row['cd_date_cleared']));
	label_cell($row['cd_cleared'] == 0 ? "NO":"YES");
	label_cell($row['cd_bank_account_code']);
	//label_cell(sql2date($row['cd_date_deposit']));
	end_row();
	$nos = $nos+$row['cd_gross_amount'];
}
	alt_table_row_color($k);
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<b>Total</b>');
	label_cell(number_format2($nos,2));
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');


end_table();

br();
br();
div_end();
end_form();
end_page(true);
?>