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
/* Author Rob Mallon */
$page_security = 'SA_RECONCILE';
$path_to_root = "..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/banking.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

add_js_file('reconcile.js');

page(_($help_context = "Payment Summary"), false, false, "", $js);

function get_branchcode_name($br_code)
{
global $br_code;

$sql = "SELECT name from transfers.0_branches where code='".$br_code."' ";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_name=$row['name'];
return $br_name;
}


function bs_date($row)
{
	$value = sql2date($row['date_deposited']);
	return $value;
}

function bs_debit_amount($row)
{
	$value = $row["debit_amount"];
	return $value>=0 ? price_format($value) : '';
}

function bs_desc($row)
{
	$value = $row["deposit_type"];
	return $value;
}

function bs_deptype($row)
{
		if (($row['bank_ref_num']==0 OR $row['bank_ref_num']=='') AND $row["deposit_type"]!='DM34 - Debit Memo-Managers Check'){
			$value = 'Online Payment';
		}
		else{
			$value = 'Check Payment';
		}
	return $value;
}

function bs_ref($row)
{
	$value = $row["bank_ref_num"];
	return $value;
}

function bs_branch($row)
{
	$value = get_branchcode_name($row['branch_code']);
	return $value;
}

function bs_status($row)
{
	if ($row['cleared']==0) {
		$value = 'Uncleared';
	}
	else {
		$value = 'Cleared';
	}
	
	return $value;
}

//=================================================================
function cv_no_link($cv_id,$cv_no,$connect_to)
{
//global $myBranchCode;
global $connect_to;
//display_error($connect_to);

	
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
	global $br_code;
	
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


function as_date($row)
{
	$value = sql2date($row['tran_date']);
	return $value;
}

function as_recon_date($row)
{
	if ($row['reconciled']){
	$value=sql2date($row['reconciled']);
	}
	else{
	$value='-N/A-';
	}
	return $value;
}

function as_cv_no($row)
{
global $connect_to;
//display_error($connect_to);

	$value=cv_no_link($row['a_id'],$row["cv_no"],$connect_to);
	return $value;
}

function as_check_no($row)
{
	if ($row['chk_number']){
	$value=$row['chk_number'];
	}
	else{
	$value='-N/A-';
	}
	
	return $value;
}

function as_ref($row)
{
	$value=$row['b_id'];
	return $value;
}

function as_supp_name($row)
{
	$value=get_supplier_name($row['supplier_id']);
	return $value;
}

function as_amount($row)
{
	$value=abs($row['amount']);
	return $value>=0 ? price_format($value) : '';
}

function as_gl_trans_view($row)
{
	global $br_code;
	
	$value=get_gl_view_str_per_branch($br_code,22, $row["trans_no"]);
	return $value;
}


function as_status($row)
{
	if ($row['reconciled']!='' AND  $row['reconciled']!='0000-00-00') {
		$value = 'Cleared';
	}
	else {
		$value = 'Uncleared';
	}
	
	return $value;
}

//	This function can be used directly in table pager 
//	if we would like to change page layout.
//
//---------------------------------------------------------------------------------------
// Update db record if respective checkbox value has changed.

start_form();


if (isset($_POST['Reconcile'])) {
	set_focus('bank_date');
	foreach($_POST['last'] as $id => $value)
		if ($value != check_value('rec_'.$id))
			if(!change_tpl_flag($id)) break;
    $Ajax->activate('_page_body');
}

//------------------------------------------------------------------------------------------------

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
		$items['1'] = 'Cleared';
		$items['2'] = 'Uncleared';
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

global $myBranchCode;
global $connect_to;

echo "<hr>";
//------------------------------------------------------------------------------------------------

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

	display_error($sql);
	$cols =
	array(
		_("Date") =>array('fun'=>'bs_date', 'ord'=>''),
		_("Description")=>array('fun'=>'bs_desc', 'ord'=>''),
		_("Type")=>array('fun'=>'bs_deptype', 'ord'=>''),
		_("Reference")=>array('fun'=>'bs_ref', 'ord'=>''),
		_("Branch") =>array('fun'=>'bs_branch', 'ord'=>''),
		_("Amount") =>array('fun'=>'bs_debit_amount', 'ord'=>''),
		_("Status") =>array('fun'=>'bs_status', 'ord'=>''),

	   );
	$table1 =& new_db_pager('trans_tbl1', $sql, $cols);
	
	br();
	display_heading("Bank Statement Summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);
	br();

	$table1->width = "80%";
	display_db_pager($table1);
	
//=============================================================================================
	br(2);
	
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
	
	//display_error($sql);

	$cols =
	array(
		_("Trans Date") =>array('fun'=>'as_date', 'ord'=>''),
		_("Date Reconciled")=>array('fun'=>'as_recon_date', 'ord'=>''),
		_("CV#")=>array('fun'=>'as_cv_no', 'ord'=>''),
		_("Check#")=>array('fun'=>'as_check_no', 'ord'=>''),
		_("Reference") =>array('fun'=>'as_ref', 'ord'=>''),
		_("Payee") =>array('fun'=>'as_supp_name', 'ord'=>''),
		_("Amount") =>array('fun'=>'as_amount', 'ord'=>''),
		_("Status") =>array('fun'=>'as_status', 'ord'=>''),
		_(" ") =>array('fun'=>'as_gl_trans_view', 'ord'=>''),
	   );
	$table2 =& new_db_pager('trans_tbl2', $sql, $cols);
	
	
	echo "<hr>";
	display_heading("ARIA Payment Summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);
	br();

	$table2->width = "80%";
	display_db_pager($table2);
	
	
end_form();

//------------------------------------------------------------------------------------------------
end_page();
?>