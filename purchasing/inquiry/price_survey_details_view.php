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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Price Survey Details"), true);

start_form();
div_start('header');

$type = ST_ITEM_TRANSFORMATION;

function get_user_($id)
{
	$sql = "SELECT * FROM MarkUsers WHERE userid = '".$id."'";
	$res = ms_db_query($sql);
	$prod =  mssql_fetch_array($res);
	
	return $prod;
}


$trans_no=$_GET['trans_no'];

$sql = "select * from Movements where MovementID='$trans_no'";

//display_error($sql);
$res = ms_db_query($sql);
$row = mssql_fetch_array($res);

	$com = get_company_prefs();
	
	display_heading($com['coy_name']);
	display_heading2($com['postal_address']);
	$user=get_user_($row['CreatedBy']);
	br(2);


	start_outer_table("width=95% $table_style2"); // outer table
	table_section(1);
	label_row("Transaction ID: ","<b>$trans_no</b>");
	label_row("Movement Type: ",'Price Survey');
    label_row('Date Created: ',date('Y-m-d', strtotime($row['DateCreated'])));
	label_row('Created By: ',$user['name']);
	//$loc=get_location_name($row["a_from_location"]);
	//label_row('From Location: ',$loc);
	//$loc2=get_location_name($row["a_to_location"]);
	//label_row('To Location: ',$loc2);
	//table_section(2, "33%");
	
	//$adj_type=get_movement_type_name($row['m_code_out']);
	

	table_section(3, "33%");
	$user=get_user_($row['PostedBy']);
	$stats='Posted';
	label_row("Movement Status: ",$stats);
	label_row('Date Posted: ',date('Y-m-d', strtotime($row['PostedDate'])));
	$user=get_user_($row['PostedBy']);
	label_row('Posted By: ',$user['name']);
	end_outer_table(1); // outer table

/* $sql = "SELECT memo_ FROM ".TB_PREF."comments where id='$trans_no' and type='$type'";
//display_error($sql);
$res = db_query($sql);
$row = db_fetch($res);
if ($row['memo_']!='') {
start_table();
label_row("<b>Remarks:</b> ".$row['memo_']);
end_table();
}
br(2); */
display_heading('Price SurveDetails');
br();

$sql="SELECT * FROM MovementLine  where MovementID='$trans_no'";
$res = ms_db_query($sql);
//display_error($sql);


start_table($table_style2.' width=95%');
$th = array();
	
array_push($th, 'ProductID','Barcode','Description', 'Unit Cost','UOM','QTY', 'Extended');


if (mssql_num_rows($res)>0)
	table_header($th);
else
{
	display_heading('No transactions found');
}


$k = 0;
while($row = mssql_fetch_array($res))
{
	
	alt_table_row_color($k);
	start_row("class='inquirybg'");
	label_cell($row['ProductID']);
	label_cell($row['barcode']);
	//$prod_desc=get_product_row($row['stock_id']);
	label_cell("<font size='1'>".$row['Description']."</font>");
	//label_cell($row['price']);
	//label_cell(number_format2(abs($row['price_pcs']),2));
	label_cell(number_format2(abs($row['unitcost']),4));
	label_cell($row['UOM']);
	qty_cell($row['qty'],false,4);
	label_cell(number_format2($x=abs($row['extended']),2));

	//$user=get_user($row['person_id']);
	//label_cell($user['real_name']);
	//label_cell(get_comments_string($type, $row['trans_no']));
	end_row();
	
	$t_total2+=$x;
}

start_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_total2),2)."<b></font>",'align=right');
end_row();

end_table();
br();
br();
div_end();
end_form();
end_page(true);
?>