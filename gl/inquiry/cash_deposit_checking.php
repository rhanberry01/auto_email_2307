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


function get_branchcode_name($br_code)
{
$sql = "SELECT name from transfers.0_branches where code='".$br_code."' ";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_name=$row['name'];
return $br_name;
}

function update_cash_dep_header($cleared_id,$date_paid,$date_cleared,$bank_id,$remarks,$payto)	
{		
	 $sql = "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
	 SET cd_cleared = '1',
	 cd_bank_deposited='$bank_id',
	 cd_date_deposited='$date_paid',
	 cd_date_cleared='$date_cleared'
	WHERE cd_id = '$cleared_id'";
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



function get_gl_view_str_per_branch($br_code,$type, $trans_no, $label="", $force=false, $class='', $id='',$icon=true)
{
	
	switch($br_code){
						case 'srsn':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
									break;
						case 'sri':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
									break;
						case 'srsnav':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
									break;
						case 'srst':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
									break;
						case 'srsc':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
									break;
						case 'srsant1':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
									break;
						case 'srsant2':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
									break;
						case 'srsm':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
									break;
						case 'srsmr':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
									break;
						case 'srsg':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
									break;
						case 'srscain':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
									break;
						case 'srsval':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
									break;			
						case 'srspun':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
									break;								
						case 'srsbsl':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
									break;			
						case 'srspat':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
									break;		
		}

set_global_connection_branch($connect_to);
	
	if (!$force && !user_show_gl_info())
		return "";

	$icon = false;
	if ($label == "")
	{
		$label = _("GL");
		$icon = ICON_GL;
	}	
			set_global_connection_branch();
	
	return viewer_link($label, 
		"gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no", 
		$class, $id, $icon);
		
}


//====================================start heading=========================================
start_form();
// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');


global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_table("width=85% $table_style2"); // outer table

	table_section();
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
	
end_table(); // outer table
div_end();

br(2);

//====================================if cleared_id=========================================
if ($cleared_id != -1)
{
global $Ajax;

$remarks=$_POST['remarks'.$cleared_id];
$bank_account=$_POST['bank_account'.$cleared_id];
$date_paid=$_POST['date_paid'.$cleared_id];

$date_cleared=Today();
begin_transaction();

$sql="select * from cash_deposit.".TB_PREF."cash_dep_header where cd_id='$cleared_id'";
//display_error($sql);
$result=db_query($sql);

while($row = db_fetch($result))
{
$id=$row['cd_id'];
$transno=$row['cd_aria_trans_no'];
$amount=$row['cd_gross_amount'];
$br_code=$row['cd_br_code'];
}

//display_error($transno);


// switch($br_code){
			// case 'srsn':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
						// break;
			// case 'sri':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
						// break;
			// case 'srsnav':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
						// break;
			// case 'srst':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
						// break;
			// case 'srsc':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
						// break;
			// case 'srsant1':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
						// break;
			// case 'srsant2':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
						// break;
			// case 'srsm':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
						// break;
			// case 'srsmr':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
						// break;
			// case 'srsg':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
						// break;
			// case 'srscain':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
						// break;
			// case 'srsval':
						// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
						// break;			
		// }



// //display_error($connect_to);
// set_global_connection_branch($connect_to);


switch_connection_to_branch_mysql($br_code);


add_gl_trans(ST_CASHDEPOSIT, $transno, $date_paid, 1010, 0, 0, $remarks,-$amount, null, $person_type_id, $person_id);


$sql_cib="select account_code from ".TB_PREF."bank_accounts where id='$bank_account'";
//display_error($sql_cib);
$result_cib=db_query($sql_cib);

while ($accountrow = db_fetch($result_cib))
{
$cash_in_bank=$accountrow['account_code'];
}
			
add_gl_trans(ST_CASHDEPOSIT, $transno, $date_paid, $cash_in_bank, 0, 0, $remarks,$amount, null, $person_type_id, $person_id);

$desc='Cleared';
add_audit_trail(ST_CASHDEPOSIT, $transno, $date_paid,$desc);

update_cash_dep_header($cleared_id,date2sql($date_paid),date2sql($date_cleared),$bank_account,$remarks,$payto);

commit_transaction();

set_global_connection_branch();

$Ajax->activate('table_');
display_notification(_("The Transaction has been Cleared."));
}

//====================================display table=========================================
//$sql = "select * from cash_deposit.".TB_PREF."cash_dep_header";


$sql = "select cd_sales_date,cd_payment_type_id,cd_payment_type,sum(on_hand) as on_hand, sum(in_bank) as in_bank,  sum(on_hand)+sum(in_bank) as gross from
(SELECT cd_sales_date,
cd_payment_type_id,
cd_payment_type,
case WHEN cd_cleared='0' then sum(cd_gross_amount) else 0 end as on_hand,
case WHEN cd_cleared='1' then sum(cd_gross_amount) else 0 end as in_bank 
FROM cash_deposit.".TB_PREF."cash_dep_header
WHERE cd_sales_date>='".date2sql($_POST['start_date'])."' AND  cd_sales_date<='".date2sql($_POST['end_date'])."'
GROUP BY cd_payment_type_id,cd_cleared,cd_sales_date) as x
GROUP BY cd_payment_type_id,cd_sales_date ORDER BY cd_sales_date ";


// if (trim($_POST['trans_no']) == '')
// {
	// $sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  // AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
// }
// else
// {
	// $sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
// }

// if ($_POST['person_id']!= '' and $_POST['search'])
// {
	// $sql .= " AND bd_payee_id='".$_POST['person_id']."'";
// }


/*
if ($_POST['payment_type']!= '')
{
//display_error($_POST['payment_type']);
	$sql .= " WHERE cd_payment_type_id='".$_POST['payment_type']."'";
}

	$sql .= "  order by cd_gross_amount asc";
*/
	$res = db_query($sql);
	display_error($sql);
	


display_heading("Sales Book Summary of ".$_POST['start_date']."");	
br();

div_start('table_');
start_table($table_style2.' width=45%');
$th = array();
array_push($th,'Sales Date','Type','Amount Deposited', 'Cash in Bank', 'Uncleared');


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
	label_cell($row['cd_payment_type']);
	amount_cell($gross=$row['gross'],false);
	amount_cell($row['in_bank'],false);
	amount_cell($row['on_hand'],false);
	end_row();
end_form();

$t_gross+=$row['gross'];
$t_in_bank+=$row['in_bank'];
$t_on_hand+=$row['on_hand'];
}

start_row();
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_gross),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_in_bank),2)."<b></font>",'align=right');
	label_cell("<font color=red><b>".number_format2(abs($t_on_hand),2)."<b></font>",'align=right');
end_row();

end_table();
div_end();
end_form();
end_page();
?>