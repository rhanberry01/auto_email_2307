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
	
page(_($help_context = "Other Income Summary"), false, false, "", $js);

$delete_id = find_submit('delete_selected');


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


//====================================start heading=========================================
start_form();
//if (!isset($_POST['start_date']))
	//$_POST['start_date'] = '01/01/'.date('Y');

global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_outer_table("width=98% $table_style2"); // outer table

	table_section(1);
	
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

	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
		br();
    	//	text_row_ex($payment ?_("To the Order of:"):_("Name:"),'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, false, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, false, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, false, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;
    }
	table_section(2);

		get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
		get_other_income_user_list_cells('Created By:','created_by');
		payment_type_list_cell('Payment Type:','payment_type');
		receipt_list_cells('Receipt Released:', 'receipt_type', '', '', '',false,'');
		ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
	
end_outer_table(1); // outer table
div_end();
//====================================end of heading=========================================
br();


//====================================if delete_id=========================================
if ($delete_id != -1)
{
global $Ajax,$db_connections;
$date_deleted=Today();

$br_code=$_POST['from_loc'];
$br_code=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];	

switch_connection_to_branch($br_code);
//display_error($br_code);

begin_transaction();
$sql="select * from ".TB_PREF."other_income_payment_header where bd_id='$delete_id' order by bd_id asc";
$result=db_query($sql);

while($row = db_fetch($result))
{
$id=$row['bd_id'];
$trans_no=$row['bd_trans_no'];
$amount=$row['bd_amount'];
$payment_type=$row['bd_payment_type'];
$debtor_no=$row['bd_payee_id'];
$date_cleared=sql2date($row['date_cleared']);
$bank_id=$row['bd_payment_to_bank'];
}

$type=ST_BANKDEPOSIT;
$desc='Deleted';
add_audit_trail($type,$trans_no,$date_deleted,$desc);

$sql = "DELETE FROM ".TB_PREF."bank_trans WHERE type='$type' AND trans_no='$trans_no'";
db_query($sql, "Could not delete bank trans");

$sql = "DELETE FROM ".TB_PREF."other_income_payment_header WHERE bd_trans_type='$type' AND bd_trans_no='$trans_no'";
db_query($sql, "Could not delete Other income payment header.");

$sql = "DELETE FROM ".TB_PREF."other_income_payment_details WHERE bd_det_type='$type' AND bd_det_trans_no='$trans_no'";
db_query($sql, "Could not delete Other income payment details.");

$sql = "DELETE FROM ".TB_PREF."bank_deposit_cheque_details WHERE type='$type' AND bank_trans_id='$trans_no'";
db_query($sql, "Could not delete bank deposit cheque details.");

$sql = "DELETE FROM ".TB_PREF."gl_trans WHERE type='$type' AND type_no='$trans_no'";
db_query($sql, "Could not delete gl.");

commit_transaction();
$Ajax->activate('table_');
display_notification(_("The Transaction has been Deleted."));
}



//====================================display table=========================================

$br_code=$_POST['from_loc'];

switch_connection_to_branch($br_code);


$sql = "select oip.*, au.user from ".TB_PREF."other_income_payment_header as oip
LEFT JOIN ".TB_PREF."audit_trail as au
on oip.bd_trans_no=au.trans_no";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}

if ($_POST['payment_type']!= '')
{
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

if ($_POST['receipt_type']!= '0')
{
//display_error($_POST['payment_type']);
	$sql .= " AND bd_receipt_type='".$_POST['receipt_type']."'";
}

if ($_POST['created_by']!='') {
$sql.=" AND user='".$_POST['created_by']."' ";
}

	$sql .= " AND bd_cleared='0' AND type='2' AND description='Paid' ORDER BY bd_trans_date, bd_trans_no";

	//display_error($sql);
	$res = db_query($sql);


div_start('table_');
start_table($table_style2.' width=85%');
$th = array();
array_push($th, 'Date Paid', 'Trans #','Created By','RF/OR/SI #', 'Payee','Type','Amount','VAT','Wtax','');

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
	label_cell(sql2date($row['bd_trans_date']));
	//label_cell(get_gl_view_str(ST_BANKDEPOSIT, $row["bd_trans_no"], $row["bd_trans_no"]));
	label_cell(get_gl_view_str_per_branch($_POST['from_loc'],ST_BANKDEPOSIT, $row["bd_trans_no"], $row["bd_trans_no"]));

	$user=get_user($row['user'],$_POST['from_loc']);
	label_cell($user['real_name']);
	
	label_cell($row['bd_or'] ,'nowrap');
	label_cell($row['bd_payee'] ,'nowrap');
	label_cell($row['bd_payment_type']);
	amount_cell($row['bd_amount'],false);
	amount_cell($row['bd_vat'],false);
	amount_cell($row['bd_wt'],false);
	$submit='delete_selected'.$row['bd_id'];
	// if (($row['bd_trans_date']==Today()) OR ($_SESSION['wa_current_user']->username == 'admin')) {
	submit_cells($submit, 'Delete', "align=center", true, true,'delete.gif');
	// }
	 // else {
	 // label_cell('');
	 // }
	end_row();
end_form();

$t_amount+=$row['bd_amount'];
$t_vat+=$row['bd_vat'];
$total_wt+=$row['bd_wt'];
}
start_row();

	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_vat),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($total_wt),2)."<b></font>",'align=right');
	label_cell('');
	end_row();	

end_table();
div_end();
end_form();
end_page();
?>