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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");

$js = "";
if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}
if ($use_date_picker) {
	$js .= get_js_date_picker();
}

// add_js_file('allocate.js');

page(_($help_context = "Fixed Asset Disposal"), false, false, "", $js);

//----------------------------------------------------------------------------------------------

check_db_has_customers(_("There are no customers defined in the system."));
check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
if (isset($_GET['AddedID'])) {
	$payment_no = $_GET['AddedID'];

	display_notification_centered(_("The selected fixed asset has been successfully disposed."));

	//submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no."-".ST_CUSTPAYMENT, 'prtopt');
	
	hyperlink_no_params($path_to_root ."/sales/inquiry/asset_depreciation.php", _("Process Another &Fixed Asset Depreciation/Disposal"));
	
	display_footer_exit();
}

//----------------------------------------------------------------------------------------------

if (isset($_POST['dispose_asset'])) {

$inserttype=ST_DEPRECIATIONEXPENSE;

	$i_dep_id = $_POST['dep_id'];	
	$i_disposal_date = $_POST['disposal_date'];
	$i_memo_ = $_POST['memo_'];
	$i_date_acquired = $_POST['date_acquired'];
	$i_asset_name = $_POST['asset_name'];
	$i_life = $_POST['life'];
	$i_apv_num = $_POST['apv_num'];
	$i_supplier = $_POST['supplier'];
	$i_asset_gl_type = $_POST['asset_gl_type'];
	$i_acquisition_cost = $_POST['acquisition_cost'];	
	$i_dep_expense_gl_type = $_POST['dep_expense_gl_type'];
	$i_accum_dep_gl_type = $_POST['accum_dep_gl_type'];
	$type=$_POST['asset_gl_type'];
	$det_id = $_POST['det_id'];
	
//display_error($i_dep_id);
$d_acq=sql2date($i_date_acquired);
$d_dis=$i_disposal_date;
$num_mos=$i_life*12;
$mos=date_diff2($d_dis,$d_acq,"m");
//display_error($num_mos);
$db_monthly_depreciation2=$i_acquisition_cost/$num_mos;
$db_monthly_depreciation=round($db_monthly_depreciation2,2);
//display_error($db_monthly_depreciation);

if ($db_monthly_depreciation!='')
{
begin_transaction();
$disposal_date=date2sql($i_disposal_date);

$t_expense=get_unused_life_exp_amount($inserttype, $det_id, $disposal_date)+1;
$it_expense=round($t_expense,2);
//display_error($t_expense);

if ($it_expense<='1' or $it_expense=='') {
$it_loss='0';
}
else {
$it_loss=$it_expense;
}

dispose_fixed_asset($inserttype, $det_id, $disposal_date, $it_loss, $i_memo_);

if ($it_expense!='0' or $it_expense!='') {
add_gl_trans(ST_DEPRECIATIONEXPENSE, $det_id, $i_disposal_date, $i_dep_expense_gl_type, 0, 0, $i_memo_, $it_expense, null, 0);
add_gl_trans(ST_DEPRECIATIONEXPENSE, $det_id, $i_disposal_date, $i_accum_dep_gl_type, 0, 0, $i_memo_, -$it_expense, null, 0);
}
void_unused_life($inserttype, $det_id, $disposal_date);
commit_transaction();

}
meta_forward($_SERVER['PHP_SELF'], "AddedID=$det_id");
}
	
	
//----------------------------------------------------------------------------------------------
global $Ajax;
//START OF FORM
start_form(); 
if (isset($_GET['asset_id'])!='')
{
$asset_id=$_GET['asset_id'];
$date_transact=$_GET['tran_date'];
$tran_date=Today();
$sql ="select defa.* , dedd.d_id as det_id from ".TB_PREF."dep_exp_fixed_assets as defa
left join ".TB_PREF."dep_exp_depreciation_details as dedd
on defa.dep_id=dedd.d_dep_id
where dep_id='$asset_id'";

$res = db_query($sql);
//display_error($sql);
while($row = db_fetch($res))
{
$date_acquired=$row['date_acquired'];
$asset_name=$row['asset_name'];
$life=$row['life'];
$asset_num=$row['asset_num'];
$serial_num=$row['serial_num'];
$apv_num=$row['apv_num'];
$supplier=$row['supplier'];
$type=$row['asset_gl_type'];
$acquisition_cost=$row['acquisition_cost'];
$dep_id=$row['dep_id'];
$dep_expense_gl_type=$row['dep_expense_gl_type'];
$accum_dep_gl_type=$row['accum_dep_gl_type'];
$det_id=$row['det_id'];
}
}
	start_outer_table("$table_style2 width=80%", 5);
	table_section(1);
	label_row(_("Date Acquired:"), sql2date($date_acquired));
	label_row(_("APV Number:"), $apv_num);
	label_row(_("Supplier:"), $supplier);
	
		table_section(2);
		label_row(_("Asset Name:"), $asset_name);
		label_row(_("Acquisition Cost:"), number_format2($acquisition_cost,2));
		label_row(_("Life:"), $life." yr(s)");
		table_section(3);
		label_row(_("Asset Type:"), get_gl_account_name($type),'nowrap');
		label_row("Asset Number: ", $asset_num);
		label_row("Serial Number: ", $serial_num);
		end_outer_table(1);
		br();
		div_start('show_cash_payment');
		display_heading('<u>Fixed Asset Disposal Details</u>');
		br();
		div_end();
		br();
		br();
		start_table("$table_style width=50%");
		
		date_cells(_("Disposal Date:"), 'disposal_date');
		label_row(_("Depreciation #:"), $det_id);
		text_row(_("Memo:"), 'memo_');
		end_table(1);
		br();
		hidden('dep_id', $dep_id);
		hidden('date_acquired', $date_acquired);
		hidden('asset_name', $asset_name);
		hidden('life', $life);
		hidden('asset_num', $asset_num);
		hidden('apv_num', $apv_num);
		hidden('supplier', $supplier);
		hidden('asset_gl_type', $type);
		hidden('dep_expense_gl_type', $dep_expense_gl_type);
		hidden('accum_dep_gl_type', $accum_dep_gl_type);
		hidden('acquisition_cost', $acquisition_cost);
		hidden('det_id', $det_id);
		submit_center('dispose_asset', _("Dispose Asset"), true, '', 'default');
	br();
end_form();
//END OF FORM
end_page();
?>
