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
	
page(_($help_context = "Adjustment Details"), true);

start_form();
div_start('header');

$type = ST_INVADJUST;

function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM transfers.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

function get_product_row_by_branch($prod_id, $branch) {
	$conn = mssql_connect($branch['ms_mov_host'], $branch['ms_mov_user'], $branch['ms_mov_pass']);
	mssql_select_db($branch['ms_mov_db'], $conn);
	$sql = "SELECT * FROM Products WHERE ProductID = $prod_id";
	$res = mssql_query($sql, $conn);
	$prod =  mssql_fetch_array($res);
	
	return $prod;
}

function get_movement_type_name_by_branch($movement_code, $branch) {
	$sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."movement_types WHERE movement_code='$movement_code'";
	$result = db_query($sql, "could not get item movement type");
	$row=db_fetch($result);
	return $row['name'];
}

function get_user_by_branch($id,$branch){

	$sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."users WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get user $id");

	return db_fetch($result);
}

$trans_no=$_GET['trans_no'];

$b = get_branch_by_id($_GET['bid']);

$sql = "select * from ".$b['aria_db'].".".TB_PREF."adjustment_header where a_trans_no='$trans_no' and a_type='$type'";

//display_error($sql);
$res = db_query($sql);
$row = db_fetch($res);

	$com = get_company_prefs();
	
	display_heading("SAN ROQUE SUPERMARKET RETAIL SYSTEMS INC. - ".$b['name']);
	display_heading2($b['address']);
	br(2);


	start_outer_table("width=95% $table_style2"); // outer table
	table_section(1);
	label_row("Transaction No: ","<b>$trans_no</b>");
	label_row('From Location: ',$row['a_from_location']);
	label_row('To Location: ',$row['a_to_location']);
	table_section(2, "33%");
	
	$adj_type=get_movement_type_name_by_branch($row['a_movement_code'], $b);
	
	label_row("Adjustment Type: ",$adj_type);
    label_row('Date Created: ',sql2date($row['a_date_created']));
	$user=get_user_by_branch($row['a_created_by'], $b);
	label_row('Created By: ',$user['real_name']);
	
	table_section(3, "33%");
	if ($row['a_status']=='1') {
	$stats='Open';
	}
	else {
	$stats='Posted';
	}
	label_row("Movement Status: ",$stats);
	label_row('Date Posted: ',sql2date($row['a_date_posted']));
	$user=get_user_by_branch($row['a_posted_by'], $b);
	label_row('Posted By: ',$user['real_name']);
	end_outer_table(1); // outer table

$sql = "SELECT memo_ FROM ".$b['aria_db'].".".TB_PREF."comments where id='$trans_no' and type='$type'";
//display_error($sql);
$res = db_query($sql);
$row = db_fetch($res);
if ($row['memo_']!='') {
start_table();
label_row("<b>Remarks:</b> ".$row['memo_']);
end_table();
}
br(2);
display_heading('Adjustment Details');
br();


$sql = "SELECT sm.*,toh.a_trans_no FROM ".$b['aria_db'].".".TB_PREF."adjustment_header  as toh";



$sql.=" left join ".$b['aria_db'].".0_stock_moves as sm
on toh.a_trans_no=trans_no
where toh.a_trans_no='$trans_no' and sm.type='$type'";
$res = db_query($sql);
// display_error($sql);


start_table($table_style2.' width=95%');
$th = array();
	
array_push($th,'', 'Trans Date','ProductID','Barcode','Description', 'Cost','UOM', 'QTY', 'Extended');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
}

$c=0;
$k = 0;
while($row = db_fetch($res))
{
	$c ++;
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell($row['tran_date']);
	//label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['stock_id']);
	label_cell($row['barcode']);
	$prod_desc=get_product_row_by_branch($row['stock_id'], $b);
	label_cell("<font size='1'>".$prod_desc['Description']."</font>");
	//label_cell($row['price']);
	//label_cell(number_format2(abs($row['price_pcs']),2));
	label_cell($row['standard_cost']);
	label_cell($row['i_uom']);
	qty_cell($row['qty'],false,4);
	label_cell(number_format2($x=$row['standard_cost'] * $row['qty'] * $row['multiplier'],2));
	//$user=get_user($row['person_id']);
	//label_cell($user['real_name']);
	//label_cell(get_comments_string($type, $row['trans_no']));
	end_row();
	$t_total+=$x;
}

start_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_total),2)."<b></font>",'align=right');
end_row();

end_table();
br();
br();
div_end();
end_form();
end_page(true);
?>