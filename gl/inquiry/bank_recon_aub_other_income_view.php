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
echo "<script type='text/javascript'>
   document.getElementById('header').style.visibility='hidden';
</script>";
function get_branch_($code,$date, $amount, $ref_no)
{
	global $db_connections;

		$sql = "SELECT aria_db from transfers.0_branches where code='".$code."'";
		$res = db_query($sql);
		$row = db_fetch_row($res);
		
		$sql="SELECT * from ".$row[0].".".TB_PREF."other_income_payment_header
		WHERE bd_trans_no IN(".$ref_no.")";
		$res = db_query($sql);
		//	display_error('Not Reconciled. Try this...');
		if (db_num_rows($res) > 0){
			return $res;
		 }else{
			 display_heading('Not Reconciled. Try this...');
			$sql="SELECT * from ".$row[0].".".TB_PREF."other_income_payment_header
			WHERE bd_amount = ".$amount." and bd_date_deposited = '".$date."'";
			$res = db_query($sql);
			display_error($sql);
			return $res;
		} 
			
		//return $row[0]; 
	

}
$id = find_submit('suggest');
/* if ($id != -1){
	global $db_connections;
	$sql = "select * from cash_deposit.".TB_PREF."bank_statement_aub where id =".$id;
	$res = db_query($sql);
	$row = db_fetch($res);
	
	
	$sql = "SELECT aria_db from transfers.0_branches where code='".$row['branch_code']."'";
	$res = db_query($sql);
	$row1 = db_fetch_row($res);
	
	$sql="SELECT * from ".$row1[0].".".TB_PREF."other_income_payment_header_ado
		  WHERE bd_amount = ".$row['credit_amount']." and bd_date_deposited = '".$row['date_deposited']."'";
	$res = db_query($sql);
	
} */

start_form(true);
div_start('header');

//display_error($db);
$res = get_branch_($_GET['branch_code'],$_GET['date'],$_GET['amount'],$_GET['ref_no']);
$code = $_GET['branch_code'];

start_table($table_style2.' width=95%');
$th = array();
	
array_push($th,'Trans #','Payment Type', 'Trans Date','Trans Type','Payee','Gross Amount','OR','Official Receipt','Reference', 'Date Deposited','Cleared');


if (db_num_rows($res) > 0)
	table_header($th);
/*else
{
	 if($_GET['bank'] == 1)
	display_heading('Not Reconciled. Please Click this '.button_cell("suggest".$_GET['id'], _("Suggest"), false, ICON_VIEW),'align=center'); 
	
}*/


$k = 0;
$nos = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell($row['bd_trans_no']);
//	label_cell($row['bd_id']);
	label_cell($row['bd_payment_type']);
	label_cell(sql2date($row['bd_trans_date']));
	label_cell($row['bd_trans_type']);
	label_cell($row['bd_payee']);
	label_cell(number_format2($row['bd_gross_amount'],2));
	///label_cell(number_format2($row['bd_amount'],2));
	//label_cell(sql2date($row['bd_date_deposited']));
	label_cell($row['bd_or']);
	label_cell($row['bd_official_receipt']);
	label_cell($row['bd_recon_ref']);
	label_cell(sql2date($row['bd_recon_date']));
	label_cell($row['bd_reconciled'] == 0 ? "NO":"YES");
	end_row();
	$nos = $nos+$row['bd_gross_amount'];
}

	alt_table_row_color($k);
	label_cell('');
//	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<b>Total</b>');
	label_cell(number_format2($nos,2));
	//label_cell('');
	//label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	end_row();

end_table();

br();
br();
div_end();
end_form();
end_page(true);
?>