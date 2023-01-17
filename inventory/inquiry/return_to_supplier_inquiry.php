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
set_time_limit(0);

$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/db/all_item_adjustments_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Return to Supplier Inquiry"), false, false, "", $js);

$approve_add = find_submit('selected_id',false);

function get_rs_slip_details_view_str($rs_id, $label="", $force=false, $class='', $id='',$icon=false)
{
	return viewer_link($label,"purchasing/view/view_rs_slip.php?rs_id=$rs_id", $class, $id, $icon);
}

function getFDFBCounter()
{
	return getCounter('FDFB');
}

function get_rs_cv_no($movement_no)
{
	$sql = "SELECT st.cv_id as cv_id, cv.amount  as cv_amount FROM ".TB_PREF."supp_trans as st
			LEFT JOIN 0_cv_header as cv
			on st.cv_id=cv.id
			WHERE supp_reference='RS#$movement_no'
			AND type='53'
			";
			//display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return array($row['cv_id'], $row['cv_amount']);
}

function get_rs_items_total($rs_id)
{
	$sql = "SELECT * FROM ".TB_PREF."rms_items
			WHERE rs_id=$rs_id";
	$res = db_query_rs($sql);

	while($row = db_fetch($res)){
			$total+=$row['qty']*$row['price'];
		
	}
	return $total;
}

function get_username_by_id_($id)
{
	$sql = "SELECT real_name FROM returned_merchandise.".TB_PREF."users WHERE id = $id";
	$res = db_query_rs($sql);
	$row = db_fetch($res);
	return $row[0];
}


function get_pos_product_row($barcode)
{
	$sql = "SELECT * FROM POS_Products WHERE Barcode = '".$barcode."'";
	$res = ms_db_query($sql);
	$prod =  mssql_fetch_array($res);
	
	return $prod;
}

function get_product_row($prod_id,$column='')
{
	$sql = "SELECT ".($column == '' ? '*' : $column)." FROM Products WHERE ProductID = $prod_id";
	$res = ms_db_query($sql);
	$prod =  mssql_fetch_array($res);
	return $prod;
}

function get_ms_supp_name($supp_code)
{
	$sql = "SELECT description FROM vendor
			WHERE vendorcode = '$supp_code'";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}


start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
ref_cells('RS#: ','rs_id', null, null, null, true);
supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
date_cells('Date From: ', 'rs_date_from');
date_cells('Date To: ', 'rs_date_to');
// yesno_list_cells(_("Status Type:"), 'status_type', '',_("For Approval"), _("Approved"));
submit_cells('search', 'Search', "", false, false);
end_table(2);
div_end();
div_end();

div_start('dm_list');

	$sql = "SELECT rms.* FROM ".TB_PREF."rms_header as rms";

	if ($_POST['rs_id'] == '')
	{
	$sql .= " WHERE rms.rs_date >= '".date2sql($_POST['rs_date_from'])."'
	  AND rms.rs_date <= '".date2sql($_POST['rs_date_to'])."'";
	  
	if ($_POST['supplier_code'] != ''){
	$sql .= " AND rms.supplier_code = ".db_escape($_POST['supplier_code']);
	}
		
	$sql .= " AND rms.trans_type='53' AND rms.movement_type='R2SSA'";
	}
	else		  
	{
	$sql .= " WHERE (rms.movement_no=".$_POST['rs_id'].")";
	}
	$sql .= " ORDER BY rs_id";
	//display_error($sql);
	$res = db_query_rs($sql);


start_table($table_style2.' width=90%');
$th = array();
		 array_push($th,'#', 'SA to BO Date', 'Supplier','Extended','Created by','Processed by','Remarks', 'Status', 'RS Slip');
		
//array_push($th, 'Date Created', 'TransNo','MovementID','From Location','To Location', 'Created By', 'Date Posted', 'Posted By', 'Status','','','');

if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$k = 0;

$u = get_user($_SESSION["wa_current_user"]->user);
$approver= $u['user_id'];

$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
//display_error($approver);
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(get_rs_view_str($row['rs_id'],'# '.$row['movement_no']));
	label_cell(sql2date($row['rs_date']));
	label_cell(get_ms_supp_name($row['supplier_code']));
	$total=get_rs_items_total($row['rs_id']);
	label_cell(number_format2($total,3),'align=right');
	label_cell(get_username_by_id_($row['created_by']));
	label_cell(get_username_by_id_($row['processed_by']));
	label_cell($row['comment']);
	
	$cv=get_rs_cv_no($row['movement_no']);
	$cv_id=$cv[0];
	$cv_amount=$cv[1];
	
	if ($cv_id!=0 and $cv_amount!=0){
	label_cell("<font color='blue'><b>DEDUCTED</b></font>");
	}
	else if ($cv_id!=0  and $cv_amount==0){
	label_cell("<font color='red'><b>CV VOIDED</b></font>");
	}
	else{
	label_cell("<font color='red'><b>NOT DEDUCTED</b></font>");
	}
	label_cell(get_rs_slip_details_view_str($row['rs_id'],'View RS Slip'));
	end_row();
}
end_table();
br();
br();
div_end();

end_form();
end_page();
?>