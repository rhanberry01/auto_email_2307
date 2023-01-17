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
//include_once($path_to_root . "/gl/includes/excel_reader2.php");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Payment Summary"), false, false, "", $js);

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
//====================================start heading=========================================

function cv_no_link($cv_id,$cv_no,$connect_to)
{
	//set_global_connection_branch($connect_to);
	
	// return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		// pager_link(_("Purchase Return"),
			// "/purchasing/supplier_credit.php?New=1&invoice_no=".
			// $row['trans_no'], ICON_CREDIT)
			// : '';
	global $path_to_root;
	return "<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?branch=".$connect_to."&cv_id=".$cv_id."'onclick=\"javascript:openWindow(this.href,this.target);  return false;\"><b>" .
				$cv_no. "&nbsp;</b></a>  ";
}

function get_gl_view_str_per_branch($br_code,$type, $trans_no, $label="", $force=false, $class='', $id='',$icon=true)
{
	global $db_connections;
	//display_error($br_code);
	$connect_to=switch_connection_to_branch($br_code);
	
	if (!$force && !user_show_gl_info())
		return "";

	$icon = false;
	if ($label == "")
	{
		$label = _("GL");
		$icon = ICON_GL;
	}	
	
	return viewer_link($label, 
		"gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no&branch=$connect_to", 
		$class, $id, $icon);
		
		set_global_connection_branch();
		
}


start_form();



if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_JOURNAL;

   	display_notification_centered( _("Journal entry has been entered") . " #$trans_no");

    display_note(get_gl_view_str($trans_type, $trans_no, _("&View this Journal Entry")));

	reset_focus();
	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &New Journal Entry"), "NewJournal=Yes");

	display_footer_exit();
} elseif (isset($_GET['UpdatedID'])) 
{
	$trans_no = $_GET['UpdatedID'];
	$trans_type = ST_JOURNAL;

   	display_notification_centered( _("Journal entry has been updated") . " #$trans_no");

    display_note(get_gl_view_str($trans_type, $trans_no, _("&View this Journal Entry")));

   	hyperlink_no_params($path_to_root."/gl/inquiry/journal_inquiry.php", _("Return to Journal &Inquiry"));

	display_footer_exit();
}

//--------------------------------------------------------------------------------------------------

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_table("width=90% $table_style2"); // outer table

	table_section();
	//get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
	//cash_dep_trans_type_list_cells('Transaction Type:', 'trans_type', '', '', '',false,'');
	//bank_accounts_list_cells2('Bank Account:', 'bank_account', null,'',true);
		//yesno_list_cells('Reconciled :', 'yes_no', '', 'Yes', 'No');
		get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
		$items = array();
		$items['0'] = 'All';
		$items['1'] = 'Reconciled';
		$items['2'] = 'Not Reconciled';
		label_cells('Status:',array_selector('recon_status', null, $items, array() ));
		
		$items2 = array();
		$items2['0'] = 'All';
		$items2['1'] = 'Check Payment (AUB)';
		$items2['2'] = 'Online Payment (MetroBank)';

		label_cells('Payment Type:',array_selector('type', null, $items2, array() ));
		
	//payment_type_list_cell('Payment Type:','payment_type');
		//ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		//label_cell('<b>From: 2016/01/01</b>','align=right');
		date_cells(' To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
end_table(); // outer table

br(2);
div_end();





ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

//==========================================================================================

$br_code=$_POST['from_loc'];
switch_connection_to_branch($br_code);
$connect_to=get_connection_to_branch($br_code);
$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
$myBranchCode=$br_code;



// $sql_t="SELECT sum(abs(b.amount)) AS t_recon FROM 0_supp_trans as c 
// LEFT JOIN 0_cv_header as a ON c.cv_id=a.id LEFT JOIN 0_bank_trans as b ON a.bank_trans_id=b.id
// LEFT JOIN 0_cheque_details as cd ON b.id=cd.bank_trans_id WHERE a.amount!=0 AND c.type='22' 
// AND c.ov_amount!=0 AND c.tran_date >='".date2sql($_POST['start_date'])."' AND  c.tran_date <='".date2sql($_POST['end_date'])."'";
// //display_error($sql_t);
// $res_t = db_query($sql_t);
// $trow = db_fetch($res_t);
// $t_recon=$trow['t_recon'];

// $sql_t="SELECT sum(abs(b.amount)) AS t_aub FROM 0_supp_trans as c 
// LEFT JOIN 0_cv_header as a ON c.cv_id=a.id LEFT JOIN 0_bank_trans as b ON a.bank_trans_id=b.id
// LEFT JOIN 0_cheque_details as cd ON b.id=cd.bank_trans_id WHERE a.amount!=0 AND c.type='22' 
// AND c.ov_amount!=0 AND c.tran_date >='".date2sql($_POST['start_date'])."' AND  c.tran_date <='".date2sql($_POST['end_date'])."'
// AND !ISNULL(cd.chk_number) AND !(ISNULL(b.reconciled) OR b.reconciled='' OR b.reconciled='0000-00-00') ";
// //display_error($sql_t);
// $res_t = db_query($sql_t);
// $trow = db_fetch($res_t);
// $t_aub=$trow['t_aub'];

// $sql_t="SELECT sum(abs(b.amount)) AS t_metro FROM 0_supp_trans as c 
// LEFT JOIN 0_cv_header as a ON c.cv_id=a.id LEFT JOIN 0_bank_trans as b ON a.bank_trans_id=b.id
// LEFT JOIN 0_cheque_details as cd ON b.id=cd.bank_trans_id WHERE a.amount!=0 AND c.type='22' 
// AND c.ov_amount!=0 AND c.tran_date >='".date2sql($_POST['start_date'])."' AND  c.tran_date <='".date2sql($_POST['end_date'])."'
// AND ISNULL(cd.chk_number) AND !(ISNULL(b.reconciled) OR b.reconciled='' OR b.reconciled='0000-00-00') 

// ";
// //display_error($sql_t);
// $res_t = db_query($sql_t);
// $trow = db_fetch($res_t);
// $t_metro=$trow['t_metro'];


$sql_t_="SELECT sum(debit_amount) as outs_bal FROM cash_deposit.0_bank_statement_metro
WHERE bank_ref_num IN (
2550810233,
2550810234,
2550810235,
2550810236,
2550810237)
";
//display_error($sql_t);
$res_t_ = db_query($sql_t_);
$trow_ = db_fetch($res_t_);
$t_metro_=$trow_['outs_bal'];






//echo "<hr>";
start_table("width=45% $table_style2"); // outer table

$th = array(_("Total <br> Payment"),_("Reconciled <br> Checks"),_("Reconciled <br> Online Payment"),_("Reconciled <br> Total"), _("Unreconciled <br> Amount"),_("Outstanding <br> Bal. Metro"));
table_header($th);
start_row();
amount_cell($t_recon);
amount_cell($t_aub);
amount_cell($t_metro);
amount_cell($reconciled=$t_aub+$t_metro);
amount_cell($t_recon-$reconciled);
amount_cell($t_metro_);
end_row();
end_table();
div_end();
br();


echo "<hr>";

// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');

	if ($_POST['type']==1) { //AUB checks
				$db_='bank_statement_aub';
				
				$sql = "select * from cash_deposit.".TB_PREF."$db_";
				$sql .= " WHERE date_deposited >= '".date2sql($_POST['start_date'])."'
				AND date_deposited <= '".date2sql($_POST['end_date'])."'
				AND debit_amount!=0";
				
				if ($_POST['trans_type']!=''){
				$sql.= " AND type='".$_POST['trans_type']."'";
				}

				
				if ($_POST['recon_status']==2) {
				$sql.="  AND cleared='0'";
				}
				else if($_POST['recon_status']==1) {
				$sql.="  AND cleared='1' AND branch_code='$myBranchCode' ";
				}
				// else{
				
					// $sql.=" AND branch_code='$myBranchCode'";
				// }
				
				$sql .= "  order by debit_amount ";

	}
	else if ($_POST['type']==2) { //Metrobank checks
				$db_='bank_statement_metro';
				
				$sql = "select * from cash_deposit.".TB_PREF."$db_";
				$sql .= " WHERE date_deposited >= '".date2sql($_POST['start_date'])."'
				AND date_deposited <= '".date2sql($_POST['end_date'])."'
				AND debit_amount!=0";
				
				if ($_POST['trans_type']!=''){
				$sql.= " AND type='".$_POST['trans_type']."'";
				}

				
				if ($_POST['recon_status']==2) {
				$sql.="  AND cleared='0'";
				}
				else if($_POST['recon_status']==1) {
				$sql.="  AND cleared='1' AND branch_code='$myBranchCode' ";
				}
				// else{
				
					// $sql.=" AND branch_code='$myBranchCode'";
				// }
				
				$sql .= "  order by debit_amount ";
	
	
	}
	else{ //SELECTING ALL USING UNION
				$sql = "SELECT * FROM cash_deposit.0_bank_statement_aub
				WHERE date_deposited >= '".date2sql($_POST['start_date'])."'
				AND date_deposited <= '".date2sql($_POST['end_date'])."' AND debit_amount!=0";
				
				if ($_POST['recon_status']==2) {
				$sql.="  AND cleared='0'";
				}
				else if($_POST['recon_status']==1) {
				$sql.="  AND cleared='1' AND branch_code='$myBranchCode' ";
				}
				
				$sql .= " UNION";
				
				$sql .= " SELECT * FROM cash_deposit.0_bank_statement_metro
				WHERE date_deposited >= '".date2sql($_POST['start_date'])."'
				AND date_deposited <= '".date2sql($_POST['end_date'])."' AND debit_amount!=0";
				
				if ($_POST['recon_status']==2) {
				$sql.="  AND cleared='0'";
				}
				else if($_POST['recon_status']==1) {
				$sql.="  AND cleared='1' AND branch_code='$myBranchCode' ";
				}
	
	}

	$res = db_query($sql);
	//display_error($sql);
	
br();
display_heading("Bank Statement Summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);
	//display_heading("Summary from 2016/01/01  to  ".$_POST['end_date']);

br();
start_table($table_style2.' width=70%');
$th = array();
array_push($th,'','Date Deposit', 'Description','Type','Reference','Branch','Amount','');
	table_header($th);

$c=0;
$k = 0;
while($row = db_fetch($res))
{
	$c++;
	//alt_table_row_color($k);
	start_row("class='overduebg'");
	label_cell($c,'align=right');
	label_cell(sql2date($row['date_deposited']));
	label_cell($row['deposit_type']);
	

		if ($row['bank_ref_num']==0 OR $row['bank_ref_num']==''){
		label_cell('Online Payment');	

		}
		else{
		label_cell('Check Payment');	
		}


	label_cell($row['reference']);
	label_cell(get_branchcode_name($row['branch_code']));
	//label_cell($row['cd_br_code']);
	//label_cell($row['cd_aria_trans_no']);
	amount_cell($row['debit_amount'],false);
	if ($row['cleared']==0) {
	label_cell('Uncleared');
	}
	else {
	label_cell('Cleared');
	}
	end_row();


$t_amount+=$row['debit_amount'];
//$t_balance+=$row['balance'];
}

start_row();
	label_cell('');
	label_cell('');
	label_cell('');
		label_cell('');
	if ($_POST['yes_no']==1) {
//label_cell('');
label_cell('');
}
else{
	//label_cell('');
	label_cell('');
}
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>','align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell('');



end_row();
end_table();
br();

echo "<hr>";


//==========================================================================================

//c=supp_trans
//a=cv_header
//b=bank_trans
//cd=cheque_details

$sql="SELECT c.trans_no,
c.type, 
c.supplier_id,
c.tran_date, c.cv_id,
a.id as a_id,a.cv_no,
b.id as b_id, b.amount, b.reconciled,
cd.chk_number, cd.chk_date,
cd.deposit_date
FROM 0_supp_trans as c
LEFT JOIN 0_cv_header as a
ON c.cv_id=a.id
LEFT JOIN 0_bank_trans as b
ON a.bank_trans_id=b.id
LEFT JOIN 0_cheque_details as cd
ON b.id=cd.bank_trans_id
WHERE a.amount!=0
AND c.type='22'
AND c.ov_amount!=0
AND c.tran_date >='".date2sql($_POST['start_date'])."' AND  c.tran_date <='".date2sql($_POST['end_date'])."'";

	if ($_POST['type']==1) { //AUB checks
	$sql.=" AND !ISNULL(cd.chk_number)";
	}
	else if ($_POST['type']==2) { //Metrobank Online Payment
	$sql.=" AND ISNULL(cd.chk_number)";
	}
	
	if ($_POST['recon_status']==1) {
	$sql.=" AND !(ISNULL(b.reconciled) OR b.reconciled='' OR  b.reconciled='0000-00-00')";
	$sql .= "  order by b.amount desc";
	}
	else if ($_POST['recon_status']==2) {
	$sql.=" AND (ISNULL(b.reconciled) OR b.reconciled='' OR  b.reconciled='0000-00-00')";
	$sql .= "  order by b.amount desc";
	}



	$res = db_query($sql);
	//display_error($sql);
	
div_start('table_');

// if ($_POST['yes_no']==0) {
// start_table();
// submit_cells('process','Process Uncleared','','',false,ICON_ADD);
// end_table();
// }

br();

start_table($table_style2.' width=70%');
$th = array();



// array_push($th,'','Trans Date','Date Reconciled','CV#','Check#','Reference', 'Payee','Amount','');

// if (db_num_rows($res) > 0){
	// display_heading("ARIA Payment Summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);
	// //display_heading("Summary from 2016/01/01  to  ".$_POST['end_date']);
	// br();
	// table_header($th);
// }
// else
// {
	// display_heading('No result found');
	// display_footer_exit();
// }

$c=0;
$k = 0;

	$cols =
	array(
		_("Trans Date") => array('fun'=>'tran_date', 'ord'=>''),
		_("Date Reconciled") => array('fun'=>'reconciled', 'ord'=>''),
		_("CV#") => 'cv_no',
		_("Check#") => array('align'=>'right', 'fun'=>'chk_number'), 
		_("Reference") => array('align'=>'right','insert'=>true, 'fun'=>'b_id'), 
	    _("Payee") => array('fun'=>'supplier_id'), 
	    _("Amount") => array('fun'=>'amount')
	   );
	$table =& new_db_pager('trans_tbl', $sql, $cols);

	$table->width = "80%";
	display_db_pager($table);


// while($row = db_fetch($res))
// {
	// $c++;
	// //alt_table_row_color($k);
		// if ($row['reconciled']){
		// // paid
		// start_row("class='paidbg'");
		// } 
		// else if (!$row['reconciled']){
			// start_row("class='inquirybg'");
		// }
		// else{
		// alt_table_row_color($k);
		// }
	
	
	
	// label_cell($c,'align=right');
	// label_cell(sql2date($row['tran_date']));
	// if ($row['reconciled']){
	// label_cell(sql2date($row['reconciled']));
	// }
	// else{
	// label_cell('-N/A-');
	// }

// //	label_cell($row['cv_no']);
	// label_cell(cv_no_link($row['a_id'],$row["cv_no"],$connect_to));
	// if ($row['chk_number']){
	// label_cell($row['chk_number']);
	// }
	// else{
	// label_cell('-N/A-');
	// }
	
	
	// label_cell($row['b_id']);
	// label_cell(get_supplier_name($row['supplier_id']));
	// amount_cell(abs($row['amount']),false);
	// label_cell(get_gl_view_str_per_branch($br_code,22, $row["trans_no"]));
	// end_row();
// // end_form();

// $t_amount2+=$row['amount'];
// //$t_balance+=$row['balance'];
// }

start_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>','align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount2),2)."<b></font>",'align=right');
	label_cell('');
end_row();
end_table();
hidden('bank_account2',$_POST['bank_account']);

div_end();
end_form();
end_page();
?>