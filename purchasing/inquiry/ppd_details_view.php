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
	
page(_($help_context = "Item Transformation Details"), true);

start_form();
div_start('header');

$type = ST_ITEM_TRANSFORMATION;

function get_description($transformation_no, $sku_no)
{
	$br_con = mysql_connect('192.168.0.43', 'root', 'srsnova');
	mysql_select_db("transfers", $br_con);

	$sql = "SELECT * FROM ".TB_PREF."prod_for_bundling_sku WHERE transformation_no=".$transformation_no." AND sku_no = ".$sku_no.""; 
	$res = mysql_query($sql, $br_con);
	$prod =  mysql_fetch_array($res);
	return $prod['remarks'];
}
function get_branch_name_($branch_code)
{
	$br_con = mysql_connect('192.168.0.43', 'root', 'srsnova');
	mysql_select_db("transfers", $br_con);

	$sql = "SELECT * FROM branches WHERE code='".$branch_code."'"; 
	$res = mysql_query($sql, $br_con);
	$branch =  mysql_fetch_array($res);
	return $branch['name'];
}

$ref_no=$_GET['ref_no'];
//display_error($ref_no);
$t_total1 = 0;

$br_con = mysql_connect('192.168.0.43', 'root', 'srsnova');
mysql_select_db("transfers", $br_con);

$sql = "select * from ".TB_PREF."prod_for_bundling_header  where a_saf_no='$ref_no'";

//display_error($sql);
$res = mysql_query($sql, $br_con);

while($row = mysql_fetch_array($res)){
	//display_error($row['a_trans_no']);
	 $sql_ = "select * from ".TB_PREF."prod_for_bundling_branches_refs  where transformation_no=".$row['a_trans_no'].""; 
	//display_error($sql);
	$res_ = mysql_query($sql_, $br_con);

	while($row_ = mysql_fetch_array($res_)){
		start_outer_table("width=100% $table_style2"); // outer table
		table_section(1);
		label_row('Branch:',get_branch_name_($row_['branch_code']));
		
		table_section(2, "45%");
		label_row('Description:',"<font size='1'>".get_description($row_['transformation_no'], $row_['sku_no'])."</font>");
		table_section(3);
		qty_cell($row_['allocation'],false,4);
		table_section(4);
		label_cell(number_format2(abs($row_['cost']),2));
		
		table_section(5, "20%");
		label_row('Allocation Amount: ', number_format2(abs($row_['allocation_amount']),2));
		end_outer_table(1); // outer table 
		
		br();
		start_table($table_style2.' width=95%');
		$th = array();
			
		array_push($th, 'ProductID','Barcode','Description', 'Cost','UOM', 'QTY');


		if (db_num_rows($res) > 0)
			table_header($th);
		else
		{
			display_heading('No transactions found');
		}
		
		$sql_1 = "select * from ".TB_PREF."prod_for_bundling_details  where transformation_no=".$row_['transformation_no']." AND sku_no = ".$row_['sku_no'].""; 
	//display_error($sql);
		$res_1 = mysql_query($sql_1, $br_con);

		while($row_1 = mysql_fetch_array($res_1)){
			alt_table_row_color($k);
			label_cell($row_1['stock_id']);
			label_cell($row_1['barcode']);
			label_cell("<font size='1'>".$row_1['description']."</font>");
			label_cell(number_format2(abs($row_1['cost']),2));
			label_cell($row_1['uom']);
			qty_cell($row_1['qty'],false,4);
			end_row();
			
		}
		end_table();
		br();
		$t_total1 = $t_total1 + $row_['allocation_amount'];
	} 	
start_table($table_style1.' width=95%; align=right');
start_row();
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>', 'align=center');
	label_cell("<font color=#880000><b>".number_format2(abs($t_total1),2)."<b></font>",'align=center');
end_row();
end_table();
br();
br();
 
}
end_form();
end_page(true);
?>