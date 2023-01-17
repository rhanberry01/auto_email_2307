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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";


include($path_to_root . "/includes/db_pager2.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/reporting/includes/reporting.inc");

define('K_PATH_FONTS', "../../reporting/fonts/");
include($path_to_root . "/reporting/includes/pdf_report.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_cv.inc");
require_once($path_to_root . '/modules/PHPMailer/class.phpmailer.php');


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Supplier Transaction Inquiry"), false, false, "", $js);

function get_real_name($id) {
	$sql = "SELECT real_name  FROM srs_aria_nova.".TB_PREF."users WHERE id = '".$id."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}
function get_po_user_id($id) {
	$sql = "SELECT po_user_id  FROM srs_aria_nova.".TB_PREF."users WHERE id = '".$id."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_username_by_id_and_branch($id, $branch_name)
{
	$sql = "SELECT user_id FROM ".TB_PREF."users WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	$user_id = $row[0];

	$sql2 = "SELECT real_name FROM $branch_name.".TB_PREF."users WHERE user_id = '".$user_id."'";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	return $row2[0];
}

function get_display_username_by_id_and_branch($id)
{
	$sql2 = "SELECT real_name FROM srs_aria_nova.".TB_PREF."users WHERE id = $id";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	return $row2[0];
}

function get_user_id_by_username_and_branch($user_name, $branch_name) {
	$sql = "SELECT id FROM $branch_name.".TB_PREF."users WHERE user_id = '".$user_name."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}
function get_supplier_id($supp, $branch_name) {
	$sql = "SELECT supplier_id FROM srs_aria_nova.".TB_PREF."suppliers WHERE supp_ref = '".$supp."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_supplier_name_vendorcode($supp) {
	$sql = "SELECT description FROM vendor WHERE vendorcode = '".$supp."'";
	//return $sql;
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}
function get_supplier_name_($supp_id, $branch_name) {
	$sql = "SELECT supp_name FROM $branch_name.".TB_PREF."suppliers WHERE supplier_id = '".$supp_id."'";
//	return $sql;
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
} 
start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = date('m/d/Y');
//display_error($_POST['start_date']);
//die;
start_table();
	start_row();
		ref_cells('SRSSAF # :', 'dm_po_ref');
		date_cells('Date Created From :', 'start_date');
		date_cells(' To :', 'end_date');
		 if($_SESSION['wa_current_user']->user == 888 OR $_SESSION['wa_current_user']->user == 633 OR $_SESSION['wa_current_user']->user == 1 OR $_SESSION['wa_current_user']->user == 730 OR $_SESSION['wa_current_user']->user == 642 OR $_SESSION['wa_current_user']->user == 651 OR $_SESSION['wa_current_user']->user == 886){
			supplier_list_ms_cells('Database Supplier:', 'supp_id', null, 'Supplier Name');
		//supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		}else{
			purchaser_supplier_list_ms_cells('Database Supplier:', 'supp_id', null, 'Supplier Name');
		//purchaser_supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		
		} 
		purchaser_list_cells('Purchaser :', 'purchaser', null, 'Please Select');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
// if (!isset($_POST['search']))
	// display_footer_exit();
$branches_sql = "SELECT * FROM transfers.0_branches";
$branches_query = db_query($branches_sql);

$all_branch = array();
while ($branches_res = db_fetch($branches_query)) {
	$sql = "SELECT * from (SELECT b.type_no, replace(a.supp_reference,'SAF NO:','') as supp_trans, a.supplier_id as supplier, b.tran_date,(SELECT account_name from srs_aria_nova.0_chart_master where account_code = b.account) as accountName, b.amount, b.memo_,(SELECT purchaserID from srs_aria_nova.0_c_payment_saf where reference = replace(a.supp_reference,'SAF NO:','') LIMIT 1) as purchaserID	FROM ".$branches_res['aria_db'].".".TB_PREF."supp_trans as a, ".$branches_res['aria_db'].".".TB_PREF."gl_trans as b WHERE a.trans_no = b.type_no and  a.type = b.type AND a.type = 53 and b.account NOT IN(23001606,
2470,
2471,
2472,
2473,
2474,
2475,
2476,
2477,
2478,
2479,
2480,
2481,
2482,
2483,
4020020,
4020025,
4020030,
402005,
4020050,
4020051,
4020052,
4020060,
4020061)";
	
	
	if ($_POST['purchaser'])
	{
		$sql .= " AND replace(a.supp_reference,'SAF NO:','') IN (SELECT reference from srs_aria_nova.0_c_payment_saf where purchaserID = ".$_POST['purchaser'].")";
	}
	
	if (trim($_POST['dm_po_ref']) == '')
	{
		$sql .=" AND replace(a.supp_reference,'SAF NO:','') LIKE '%SRSSAF%'";
		
		$sql .= " AND DATE(a.tran_date) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(a.tran_date) <= '".date2sql($_POST['end_date'])."'";
				  
		if ($_POST['supp_id'])
		{
				$sql .= " AND a.supplier_id = '".get_supplier_id($_POST['supp_id'],$branches_res['aria_db'])."'";
		}
		
	}else{
		$sql .=" AND replace(a.supp_reference,'SAF NO:','') = '".$_POST['dm_po_ref']."'";
		$sql .= " AND DATE(a.tran_date) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(a.tran_date) <= '".date2sql($_POST['end_date'])."'";
	}
	
	$sql .="UNION ALL SELECT b.type_no,a.bd_saf,(SELECT supplierCode from srs_aria_nova.0_c_payment_saf_supp where reference = a.bd_saf LIMIT 1) as supplier, b.tran_date, (SELECT account_name from srs_aria_nova.0_chart_master where account_code = b.account) as accountName, b.amount, a.bd_memo as mermo_, (SELECT purchaserID from srs_aria_nova.0_c_payment_saf where reference = a.bd_saf LIMIT 1) as purchaserID FROM ".$branches_res['aria_db'].".".TB_PREF."other_income_payment_header as a, ".$branches_res['aria_db'].".".TB_PREF."gl_trans b where a.bd_trans_no = b.type_no and b.account NOT IN(23001606,
2470,
2471,
2472,
2473,
2474,
2475,
2476,
2477,
2478,
2479,
2480,
2481,
2482,
2483,
4020020,
4020025,
4020030,
402005,
4020050,
4020051,
4020052,
4020060,
4020061)";
	
	if ($_POST['purchaser'])
	{
		$sql .= " AND a.bd_saf IN (SELECT reference from srs_aria_nova.0_c_payment_saf where purchaserID = ".$_POST['purchaser'].")";
	}
	
	if (trim($_POST['dm_po_ref']) == '')
	{
		$sql .=" AND a.bd_saf LIKE '%SRSSAFCF%'";
		
		$sql .= " AND DATE(b.tran_date) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(b.tran_date) <= '".date2sql($_POST['end_date'])."'";
				  
		if ($_POST['supp_id'])
		{
				$sql .= " AND a.bd_saf IN (SELECT reference from srs_aria_nova.0_c_payment_saf_supp where supplierCode = '".$_POST['purchaser']."')";
		}
		
	}else{
		$sql .=" AND a.bd_saf LIKE '%".$_POST['dm_po_ref']."%'";
		$sql .= " AND DATE(b.tran_date) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(b.tran_date) <= '".date2sql($_POST['end_date'])."'";
	}
	
	$sql .= ") as asd";
	
	//display_error($sql);
	//display_error($sql);
	//die;
	$res = db_query($sql);

	while ($row = db_fetch_assoc($res)) {
		$_row = $row;
		$_row['branch_name'] = $branches_res['name'];
		$_row['branch_db'] = $branches_res['aria_db'];
		array_push($all_branch, $_row);
	}
	//display_error(var_dump($all_branch));
}

start_table($table_style2.' width=90%');
$th = array('Branch', 'SRSSAF #','Trans Date', 'Purchaser', 'Supplier',  'Account Name', 'Amount', 'Comment');//,'Inactive');


if (!empty($all_branch))
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$type = 53;
$k = 0;
$amount=0;
foreach ($all_branch as $key => $row) 
{
	alt_table_row_color($k);
	label_cell($row['branch_name']);
//	label_cell($row['trans_no']);
	label_cell($row['supp_trans']);
	label_cell(sql2date($row['tran_date']));
	label_cell(strtoupper(get_display_username_by_id_and_branch($row['purchaserID'])));
	
	label_cell(get_supplier_name_($row['supplier'], $row['branch_db']) == null ? get_supplier_name_vendorcode($row['supplier'], $row['branch_db']):get_supplier_name_($row['supplier'], $row['branch_db']));
//	label_cell(get_supplier_name_vendorcode($row['supplier']));
//	label_cell($row['supplier']);
	label_cell($row['accountName']);
	label_cell(number_format($row['amount'],2));
	label_cell($row['memo_']);
				
	end_row();
	$amount = $amount + $row['amount'];
}
alt_table_row_color($k);
label_cell("<b>Total: </b>","colspan=6 align='right'");
label_cell("<b> ".number_format($amount,2)."</b>","colspan=8 align='left'");
end_row();
end_table();
div_end();

end_form();



end_page();

?>
