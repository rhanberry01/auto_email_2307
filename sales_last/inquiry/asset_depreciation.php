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
if (isset($_GET['xls']) AND isset($_GET['filename']) AND isset($_GET['unique']))
{
	$path_to_root = "../..";
	include_once($path_to_root . "/includes/session.inc");
	header('Content-Disposition: attachment; filename='.$_GET['filename']);
	$target = $path_to_root.'/company/'.$_SESSION['wa_current_user']->company.'/pdf_files/'.$_GET['unique'];
	readfile($target);
	exit;
}

$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

	//start of excel report
if(isset($_POST['dl_excel']))
{
	cashier_summary_per_day_excel();
	exit;
}

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $start = $time;

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Fixed Asset Depreciation", false, false, "", $js);

//------------------------------------------------------------------------------------------------
$approve_id = find_submit('approve_depreciation');

start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	//gl_all_accounts_list_cells(_("Fixed Asset Account:"), 'account', null, false, false, "All Accounts");
	yesno_list_cells('Depreciated:', 'depreciated_status');
	fixed_assets_list_cells(_('Fixed Asset Account:'), 'account', null,true); 
	date_cells(_("Date Acquired:"), 'date_', '', null,'','',-5);
	date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);


$date_from =date2sql($_POST['date_']);
$date_to = date2sql($_POST['TransToDate']);

display_heading("Depreciation Expense Summary from ".$_POST['date_']."  to  ".$_POST['TransToDate']);

br();
br();


if ($approve_id != -1)
{
$sql = "SELECT * FROM ".TB_PREF."dep_exp_fixed_assets where inactive='0' and dep_id='$approve_id' and depreciated='0'";
$result1=db_query($sql);
//display_error($sql);
}

while($row = db_fetch($result1))
{
$db_date_acquired=$row["date_acquired"];
$db_apv_num=$row["apv_num"];
$db_asset_name=$row["asset_name"];
$db_supplier=$row["supplier"];
$db_invoice_num=$row["invoice_num"];
$db_acquisition_cost=$row["acquisition_cost"];
$db_life=$row["life"];
$db_expecte_life_date=$row["expected_life_date"];
$db_asset_num=$row["asset_num"];
$db_serial_num=$row["serial_num"];
$db_asset_gl_type=$row["asset_gl_type"];
$db_dep_expense_gl_type=$row["dep_expense_gl_type"];
$db_accum_dep_gl_type=$row["accum_dep_gl_type"];
$mos=$db_life*12;
$db_monthly_depreciation2=$db_acquisition_cost/$mos;
$db_monthly_depreciation=round($db_monthly_depreciation2,2);
}

	 if ($approve_id != -1)
{
	global $Ajax;
	//$date_paid=date2sql($_POST['date_paid'.$approve_id]);
	
	if ($db_monthly_depreciation!='')
	{
	approve_depreciation($approve_id);
	insert_approve_depreciation($approve_id,$db_acquisition_cost,$db_life,$db_monthly_depreciation);
	
		
		 $sqlid_details="select d_id from ".TB_PREF."dep_exp_depreciation_details order by d_id asc";
		 $result_id_details=db_query($sqlid_details);
	
		while ($det_row = db_fetch($result_id_details))
		{
		$d_id=$det_row['d_id'];
		}

	$d_a=sql2date($db_date_acquired);
	$_day=explode_date_to_dmy($d_a);
	

	//HALF OF MONTH DEPRECIATION
	if ($_day[0]>=16) {
	for ($x=1; $x<=$mos ; $x++)
	{
	
	if ($x==$mos){
	$db_monthly_depreciation-=1;
	}
	
	$add_m=add_months($d_a,+$x);
	$date_paid=half_month($add_m);

	add_gl_trans(ST_DEPRECIATIONEXPENSE, $d_id, $date_paid, $db_dep_expense_gl_type, 0, 0, $memo, $db_monthly_depreciation, null, 0);
	add_gl_trans(ST_DEPRECIATIONEXPENSE, $d_id, $date_paid, $db_accum_dep_gl_type, 0, 0, $memo, -$db_monthly_depreciation, null, 0);
	}
	}
	
	//END OF MONTH DEPRECIATION
	if ($_day[0]<=15){
	$date_paid=end_month($d_a);
	for ($x=1; $x<=$mos ; $x++)
	{
	if ($x==$mos){
	$db_monthly_depreciation-=1;
	}
	add_gl_trans(ST_DEPRECIATIONEXPENSE, $d_id, $date_paid, $db_dep_expense_gl_type, 0, 0, $memo, $db_monthly_depreciation, null, 0);
	add_gl_trans(ST_DEPRECIATIONEXPENSE, $d_id, $date_paid, $db_accum_dep_gl_type, 0, 0, $memo, -$db_monthly_depreciation, null, 0);

	$add_m=add_months($d_a,+$x);
	$date_paid=end_month($add_m);
	}
	}
	display_notification("Selected asset has been depreciated.");
	}
	$Ajax->activate('table_');
}
	
	
div_start('table_');
//start of table display
start_table($table_style2);
$th = array(_('Date Acquired'),_('APV #'),_('Asset Description'), _('Supplier'),_('Invoice #'),_('Acquisition Cost'), _('Life'),_('Monthly Depreciation'),'Asset #',' Serial #','Fixed Asset Type',"");
table_header($th);
//,_('Expected Life End Date')

$sql = "SELECT * FROM ".TB_PREF."dep_exp_fixed_assets where inactive='0'";

if ($_POST['account']!='')
{
$acc=$_POST['account'];
$sql.=" and asset_gl_type='".$acc."'";
}

if ($_POST['depreciated_status']!='1')
{
$depreciated_status=$_POST['depreciated_status'];
$sql.="  and depreciated='".$depreciated_status."'";
}

if ($_POST['depreciated_status']!='0')
{
$depreciated_status=$_POST['depreciated_status'];
$sql.="  and depreciated='".$depreciated_status."'";
}


if ($_POST['date_']!='' and $_POST['TransToDate']!='')
{
$sql.=" and (date_acquired>='$date_from' and date_acquired<='$date_to')";
}
//display_error($sql);
$result=db_query($sql);

while($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	$months_life=$myrow["life"]*12;
	$monthly_depreciation=$myrow["acquisition_cost"]/$months_life;
	label_cell($myrow["date_acquired"],'nowrap');
	label_cell($myrow["apv_num"],'nowrap');
	label_cell($myrow["asset_name"],'nowrap');
	label_cell($myrow["supplier"],'nowrap');
	label_cell($myrow["invoice_num"],'nowrap');
	amount_cell($myrow["acquisition_cost"],'nowrap');
	//label_cell($myrow["branch"],'nowrap');
	label_cell($myrow["life"]." (Yrs.)",'nowrap');
	//label_cell($myrow["expected_life_date"],'nowrap');
	amount_cell($monthly_depreciation,'nowrap');
	//label_cell($myrow["expected_life_date"]." (".$months_life." yrs.)",'nowrap');
	label_cell($myrow["asset_num"],'nowrap');
	//label_cell(date_diff2(sql2date($myrow["expected_life_date"]),sql2date($myrow["date_acquired"]), "m"));
	label_cell($myrow["serial_num"],'nowrap');
	label_cell(get_gl_account_name($myrow["asset_gl_type"]),'nowrap');
	//date_cells('',date_paid.$myrow['dep_id']);
	 // $_day=explode_date_to_dmy(sql2date($myrow["date_acquired"]));
	//label_cell($date_paid,'nowrap');
	if ($myrow['depreciated']=='0'){
	$submit='approve_depreciation'.$myrow['dep_id'];
	submit_cells($submit, 'Depreciate', "align=center", true, true,'ok.gif');
	}
	else {
		label_cell("Depreciated",'nowrap');
	}
	
	end_row();	
}

// if ($approve_id != -1)
// {
// }
// else {
	// label_cell('');
	// label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	// label_cell('');
	// label_cell('');
	// label_cell('');
	// label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
	// label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
	// label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
	// label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
	// label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
	// label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
	// label_cell('');
	// label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
	// label_cell('');
	// end_row();
	// }

end_table(1);
div_end();
end_form();
end_page();

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>