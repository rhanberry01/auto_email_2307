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
	
page(_($help_context = "Cash Deposit Clearing"), false, false, "", $js);

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

function get_dep_type_name($dep_id)
{
$sql = "SELECT dep_type_name from cash_deposit.0_deposit_type where dep_id='".$dep_id."' ";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_name=$row['dep_type_name'];
return $br_name;
}

function get_gl_bank_name($account_code)
{
	$sql = "SELECT bank_account_name from cash_deposit.0_all_bank_accounts where account_code='".$account_code."' ";
	//display_error($sql);
	$result=db_query($sql);
	$row=db_fetch($result);
	$bank_name=$row['bank_account_name'];
	return $bank_name;
}

function update_cash_dep_header($cleared_id,$date_paid,$date_cleared,$bank_account_code,$remarks,$payto,$bank_id)	
{		
	 $sql = "UPDATE cash_deposit.".TB_PREF."cash_dep_header 
	 SET cd_cleared = '1',
	 cd_bank_account_code='$bank_account_code',
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
	global $db_connections;
	//display_error($br_code);
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
				// case 'srspun':
							// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
							// break;								
				// case 'srsbsl':
							// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
							// break;			
				// case 'srspat':
							// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
							// break;		
				// case 'srscom':
							// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["comembo_connection"];
							// break;		
		// }

// //display_error($connect_to);
// //set_global_connection_branch($connect_to);

$connect_to=get_connection_to_branch($br_code);
	
	if (!$force && !user_show_gl_info())
		return "";

	$icon = false;
	if ($label == "")
	{
		$label = _("GL");
		$icon = ICON_GL;
	}	
		//	set_global_connection_branch();
	
	return viewer_link($label, 
		"gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no&branch=$connect_to", 
		$class, $id, $icon);
		
		set_global_connection_branch();
		
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
	
	if (!isset($_POST['PayType']))
	{
		if (isset($_GET['PayType']))
			$_POST['PayType'] = $_GET['PayType'];
		else
			$_POST['PayType'] = "";
	}
	if (!isset($_POST['person_id']))
	{
		if (isset($_GET['PayPerson']))
			$_POST['person_id'] = $_GET['PayPerson'];
		else
			$_POST['person_id'] = "";
	}
	if (isset($_POST['_PayType_update'])) {
		$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
	}
	
		get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
		yesno_list_cells('Deposited :', 'yes_no', '', 'Yes', 'No');
		payment_type_list_cell('Payment Type:','payment_type');
		//ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
end_table(); // outer table

br(2);

start_table();
		date_row('Date Deposit:', 'date_paid');
		bank_accounts_list_row2('Bank Account:', 'bank_account', null,'',true);
end_table();
div_end();

br(2);

//start_form();
if (isset($_POST['Add']))
{
	global $Ajax;

	$bank_account_code=$_POST['bank_account'];
	//display_error($bank_account_code);
	$date_paid=$_POST['date_paid'];

	
	$prefix = 'selected_id';
	$dm_ids = array();
	foreach($_POST as $postkey=>$postval)
    {
		if (strpos($postkey, $prefix) === 0)
		{
			$id = substr($postkey, strlen($prefix));
			$dm_ids[] = $id;
			//print_r($dm_ids);
		}
	}
	if (count($dm_ids) > 0) {
		$rs_id_str = implode(',',$dm_ids);
		//display_error($rs_id_str);
		foreach ($dm_ids as $cleared_id)
		{
			
			$remarks=$_POST['remarks'.$cleared_id];
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

			// //display_error($transno);
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
					// case 'srspun':
								// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
								// break;	
					// case 'srspat':
								// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
								// break;
					// case 'srsbsl':
								// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
								// break;	
					// case 'srscom':
								// $connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["comembo_connection"];
								// break;		
					// }

			// //display_error($connect_to);
			// set_global_connection_branch($connect_to);
			
			switch_connection_to_branch_mysql($br_code);


			add_gl_trans(ST_CASHDEPOSIT, $transno, $date_paid, 272727, 0, 0, $remarks,-$amount, null, $person_type_id, $person_id);


			$sql_cib="select id,account_code from ".TB_PREF."bank_accounts where account_code='$bank_account_code'";
			//display_error($sql_cib);
			$result_cib=db_query($sql_cib);

			while ($accountrow = db_fetch($result_cib))
			{
			$bank_id=$accountrow['id'];
			$cash_in_bank=$accountrow['account_code'];
			}
			
			$cash_in_bank=$bank_account_code;
						
			add_gl_trans(ST_CASHDEPOSIT, $transno, $date_paid, $cash_in_bank, 0, 0, $remarks,$amount, null, $person_type_id, $person_id);

			$desc='Cleared';
			add_audit_trail(ST_CASHDEPOSIT, $transno, $date_paid,$desc);

			update_cash_dep_header($cleared_id,date2sql($date_paid),date2sql($date_cleared),$bank_account_code,$remarks,$payto,$bank_id);

			commit_transaction();

			set_global_connection_branch();

			$Ajax->activate('table_');	
		}
	$Ajax->activate('table_'); 
	display_notification(_("The Transaction has been Cleared."));
	}
	else{
		display_error('Nothing to process!');
	}
}

$sql = "select * from cash_deposit.".TB_PREF."cash_dep_header";

$sql .= " WHERE cd_sales_date >= '".date2sql($_POST['start_date'])."'
					AND cd_sales_date <= '".date2sql($_POST['end_date'])."'";

if ($_POST['yes_no']==0) {
$sql.="  AND cd_cleared='0'";
}
else {
$sql.=" AND cd_cleared='1'";
}

if ($_POST['payment_type']!= '')
{
//display_error($_POST['payment_type']);
	$sql .= " AND cd_payment_type_id='".$_POST['payment_type']."'";
}

if ($_POST['from_loc']!= '')
{
//display_error($_POST['payment_type']);
	$sql .= " AND cd_br_code='".$_POST['from_loc']."'";
}

	$sql .= "  order by cd_gross_amount asc";

	$res = db_query($sql);
	//display_error($sql);
	
div_start('table_');

start_table($table_style2.' width=99%');
$th = array();

if ($_POST['yes_no']==0) {
	array_push($th,'','Sales Date', 'Date Created','Aria Trans #', 'Branch','Description', 'Type','Memo','Amount','Remarks','');
}
else{
	array_push($th,'','Sales Date', 'Date Created','Aria Trans #', 'Branch','Description', 'Type','Memo','Amount','Account','Date Deposit','Remarks','');
}

if (db_num_rows($res) > 0){
	display_heading("Sales Book Breakdown");	
	br();
	table_header($th);
}
else
{
	display_heading('No result found');
	display_footer_exit();
}

$c=0;
$k = 0;
while($row = db_fetch($res))
{
	$c++;
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell(sql2date($row['cd_sales_date']));
	label_cell(sql2date($row['cd_trans_date']));

//label_cell($row["cd_id"]);
	label_cell(get_gl_view_str_per_branch($row['cd_br_code'],ST_CASHDEPOSIT, $row["cd_aria_trans_no"], $row["cd_aria_trans_no"]));
//	label_cell(get_gl_view_str(ST_CASHDEPOSIT, $row["cd_aria_trans_no"], $row["cd_aria_trans_no"]));
	
	$branch_name=get_branchcode_name($row['cd_br_code']);
	label_cell($branch_name,'nowrap');
	
	$cd_description=get_dep_type_name($row['cd_description']);
	label_cell($cd_description,'nowrap');
	//label_cell($row['cd_description'] ,'nowrap');
	label_cell($row['cd_payment_type']);
	label_cell($row['cd_memo']);
	amount_cell($row['cd_gross_amount'],false);
	//label_cell(get_comments_string($type, $row['trans_no']));
	if ($row['cd_cleared']==0) {
	text_cells('','remarks'.$row['cd_id'],null);
//	$submit='clear_selected'.$row['cd_id'];
//	submit_cells($submit, 'Clear', "align=center", true, true,'ok.gif');
	check_cells('',"selected_id".$row['cd_id']);
	}
	else {
//	label_cell('Deposited');
		label_cell(get_gl_bank_name($row['cd_bank_account_code']));
		label_cell(sql2date($row['cd_date_deposited']));
		label_cell($row['cd_memo']);
		label_cell('Deposited');
	}
	end_row();
end_form();

$t_amount+=$row['cd_gross_amount'];
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
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	if ($_POST['yes_no']==1) {
	label_cell('');
	label_cell('');
	}
	label_cell('');
	label_cell('');
end_row();
end_table();

br(2);

if ($_POST['yes_no']==0) {
submit_center('Add',_("Clear Selected Items"), true, '', true, ICON_ADD);
}

div_end();
end_form();
end_page();
?>