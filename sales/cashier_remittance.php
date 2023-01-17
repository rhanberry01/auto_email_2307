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
//-----------------------------------------------------------------------------
$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");
// include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = '';
if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}

if ($_SESSION['wa_current_user']->access == 15)
page('Cashier Remittance', true, true, "", $js);

else
page('Cashier Remittance', false, false, "", $js);
// echo 'E0159 . E1573';
// $_POST['cashier_username'] = 'E0159';


//==================================================================

if (isset($_GET['AddedRID']))
{
	print_remittance($_GET['AddedRID']);
	display_notification_centered(_("<font size=6>Remittance #<b> ".$_GET['AddedRID']."</b> has been successfully entered.</font>"));
	
	hyperlink_no_params($path_to_root . "/sales/cashier_remittance.php", _("Enter Another Remittance"));
	end_page(true);
	exit;
}

start_form();
if ($_SESSION['wa_current_user']->access == 15)
	br(8);
// if (isset($_POST['approve']))
if (isset($_POST['confirm_cashier']))
{
	// $user_id = $_POST['treasurer_username'];
	// $password = $_POST['treasurer_pass'];
	
	// $sql = "SELECT * FROM ".TB_PREF."users WHERE user_id = ".db_escape($user_id)." 
			// AND password=".db_escape(md5($password))."
			// AND can_approve_sales_remittance = 1";
	
	// // display_error($sql);
	// $res = db_query($sql, "could not get validate user login for $user_id");
	
	// if (db_num_rows($res) == 0)
	// {
		// display_error('Invalid user OR Wrong Password for treasurer');
		// set_focus($_POST['treasurer_username']);
		// return false;
	// }
	
	// $row = db_fetch($res);
	// $treasurer_id = $row['id'];
	// $treasurer_name = $row['real_name'];
	
	$treasurer_id = 0;
	$treasurer_name = '';
	
	// display_error('remittance_date : '.$_POST['remittance_date']);
	// display_error('cashier_id : '.$_POST['cashier_id']);
	// display_error('cashier : '.$_POST['cashier_name']);
	// display_error('treasurer_id : '.$treasurer_id);
	// display_error('treasurer: '.$treasurer_name);
	// display_error('total: '.input_num('g_total'));
	// display_error('remarks: '.$_POST['remarks']);
	
	begin_transaction();
	$remittance = insert_remittance($_POST['cashier_id'], $treasurer_id,$treasurer_name);
	
	if (!$remittance)
		return false;
	
	commit_transaction();
	meta_forward($_SERVER['PHP_SELF'], "AddedRID=".$remittance."&final=0");
}

	
if (isset($_POST['compute']))
{
	global $Ajax;
	
	if ($_POST['remittance_type'] == -1)
	{
		display_error('Please Choose a Remittance Type.');
		set_focus($_POST['remittance_type']);
		unset($_POST['compute']);
	}
	
	$user_id = $_POST['cashier_username'];
	$password = $_POST['cashier_pass'];
	
	// CHECK USER FROM MARKUSERS TABLE IN MSSQL
	
	$sql = "SELECT * FROM MarkUsers 
				WHERE loginid = '".ms_escape_string($user_id)."'
				AND password = '".ms_escape_string($password)."'";
	$res = ms_db_query($sql, "could not get login id: $user_id and password: $password");
	
	if (mssql_num_rows($res) == 0)
	{
		display_error('Invalid ID OR Password');
		set_focus($_POST['cashier_username']);
		unset($_POST['compute']);
	}
	
	$row = mssql_fetch_array($res);
	
	$_POST['cashier_id'] = $row['userid'];
	$_POST['cashier_name'] = $row['name'];
	

	$Ajax->activate('cs_login');
	$Ajax->activate('items');
	$Ajax->activate('others');
	$Ajax->activate('tr_login');
}

if (isset($_POST['reset']))
{
	global $Ajax;
	
	unset($_POST['compute']);
	
	$Ajax->activate('cs_login');
	$Ajax->activate('items');
	$Ajax->activate('others');
	$Ajax->activate('tr_login');
}

$canceller = false;
start_table($table_style);

echo "<tr valign=top><td width=25%>";

div_start('cs_login');
br();
display_heading('CASHIER');
br();
start_outer_table("$table_style2");
table_section(1);

// $items = array();
// $items['-1'] = 'Choose Remittance Type';
// $items['0'] = 'Partial Remittance';
// $items['1'] = 'Final Remittance';

// label_row('<b>Date :</b>','<b>'.Today().'</b>');
if (!isset($_POST['compute']))
{
	// label_row('<b>Remittance Type: </b>', array_selector('remittance_type', null, $items, array('select_submit'=> true,'async' => true)));
	// date_row('Date:','remittance_date');
	hidden('remittance_date', Today());
	label_row('<b>Date</b>','<b>'.Today().'</b>');
	text_row('<b>Login ID :</b>','cashier_username');
	password_row('<b>Password :</b>', 'cashier_pass');
}
else
{
	hidden('remittance_type', $_POST['remittance_type']);
	
	hidden('cashier_id', $_POST['cashier_id']);
	hidden('cashier_username', $_POST['cashier_username']);
	hidden('cashier_name', $_POST['cashier_name']);
	hidden('remittance_date', $_POST['remittance_date']);
	
	// label_row('<b>Remittance Type: </b>',$items[$_POST['remittance_type']]);
	label_row('<b><font color=red>Date :</font></b>','<b><font color=red>'.$_POST['remittance_date'].'</font></b>');
	label_row('<b>Name :</b>','<b>'.$_POST['cashier_name'].'</b>');
}

// echo 'E1573';
end_outer_table(1);
div_end();

echo "</td><td width=25%>";

div_start('items');
br();
display_heading('CASH');
br();
// if ($canceller)
	// display_footer_exit();
start_table("$table_style width=30%");
$th = array(_("Denomination"), "Pieces");

if (isset($_POST['compute']))
	$th[] = "Line Total";

table_header($th);
$g_total = $k = 0; 

$sql = "SELECT * FROM ".TB_PREF."denominations WHERE denomination >= 20";
// if (!check_value('show_inactive')) $sql .= " WHERE !inactive";
$result = db_query($sql,"could not get denominations");

while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);
		
	label_cell('<b>'.number_format2($myrow["denomination"],2).'</b>','align=center');
	
	if (!isset($_POST['compute']))
		small_qty_cells('','deno'.$myrow["id"], null, null, null, 0);
	else
	{
		hidden('deno'.$myrow["id"],$_POST['deno'.$myrow["id"]]);
		qty_cell($_POST['deno'.$myrow["id"]], false, 0);
		$line_total = input_num('deno'.$myrow["id"]) *$myrow["denomination"] ;
		$g_total += $line_total;
		amount_cell($line_total);
	}
	end_row();
}

if (isset($_POST['compute']))
{
	start_row();
		label_cell('<b>TOTAL CASH :</b>',' colspan=2 class=tableheader');
		hidden('g_total',$g_total);
		amount_cell($g_total,true);
	end_row();
}

end_table(1);

	
// if (!isset($_POST['compute']))
	// submit_center('compute', 'Ask Approval', true, false, true);
// else
	// submit_center('reset', 'Edit Again', true, false, true);
	
div_end();
echo "</td><td width=25%>";
	
div_start('others');
br();
display_heading('TOTALS');
br();

start_table("$table_style2 width=30%");

start_row();
	label_cell('<b>TOTAL CREDIT CARD : </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_credit_card');
	else
	{
		amount_cell(input_num('total_credit_card'),true);
		hidden('total_credit_card', $_POST['total_credit_card']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL DEBIT CARD : </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_debit_card');
	else
	{
		amount_cell(input_num('total_debit_card'),true);
		hidden('total_debit_card', $_POST['total_debit_card']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL SUKI CARD : </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_suki_card');
	else
	{
		amount_cell(input_num('total_suki_card'),true);
		hidden('total_suki_card', $_POST['total_suki_card']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL SRS GC: </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_srs_gc');
	else
	{
		amount_cell(input_num('total_srs_gc'),true);
		hidden('total_srs_gc', $_POST['total_srs_gc']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL OTHERS: </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_others');
	else
	{
		amount_cell(input_num('total_others'),true);
		hidden('total_others', $_POST['total_others']);
	}
end_row();

end_table(1);

if (isset($_POST['compute']))
{
	
	$total_remittance = $g_total+input_num('total_credit_card')+input_num('total_debit_card')+input_num('total_suki_card')+
	input_num('total_srs_gc')+input_num('total_others');
	start_table();
	start_row();
	label_cell('<b>TOTAL REMITTANCE: </b>','nowrap class=tableheader');
	amount_cell($total_remittance,true);
	end_row();
	end_table();
}

br();	
	
if (!isset($_POST['compute']))
	submit_center('compute', '<b>CHECK Amount</b>', true, false, false, 'ok.gif');
else
{
	br();	
	submit_center('reset', 'Edit Again', true, false, true);
	br(2);	
	submit_center('confirm_cashier', '<b>CONFIRM</b>', true, false, false, 'ok.gif');
}
br(2);	
hyperlink_no_params($_SERVER['PHP_SELF']."?NewOrder=Yes",'RESET Page');
div_end();
echo "</td></tr>";
/* echo "<td width=25%>";

// div_start('tr_login');
// br();
// display_heading('TREASURER');
// br();
// if (isset($_POST['compute']))
// {
	// set_focus('treasurer_username');
	// start_outer_table("$table_style2");
	// table_section(1);
	// text_row('<b>Login ID :</b>','treasurer_username');
	// password_row('<b>Password :</b>', 'treasurer_pass');
	// textarea_row('Remarks:', 'remarks', '', 20, 4);
	// end_outer_table(1);
	// submit_center('approve', 'Approve', true, false, false);
// }
// else
// {
	// echo '';
// }
// div_end();
echo "</td></tr>";
*/
end_table();
end_form();
end_page(true);

?>