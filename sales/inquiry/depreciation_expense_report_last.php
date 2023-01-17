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
	
page("Depreciation Expense Summary", false, false, "", $js);

//------------------------------------------------------------------------------------------------

function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$date_= $_POST['date_'];
	$date_t= $_POST['TransToDate'];
	
$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
	

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Depreciation Expense Report'), "depreciation_expense_report", "LETTER");
	
    $rep->Font();
	
	$format_header =& $rep->addFormat();
	$format_header->setBold();
	$format_header->setAlign('center');
	$format_header->setFontFamily('Calibri');
	$format_header->setSize(16);
	
	$format_bold_title =& $rep->addFormat();
	$format_bold_title->setTextWrap();
	$format_bold_title->setBold();
	$format_bold_title->setAlign('center');
	$format_bold_title->setFontFamily('Calibri');
	
	$format_left =& $rep->addFormat();
	$format_left->setTextWrap();
	$format_left->setAlign('left');
	$format_left->setFontFamily('Calibri');
	
	$format_center =& $rep->addFormat();
	$format_center->setTextWrap();
	$format_center->setAlign('center');
	$format_center->setFontFamily('Calibri');
	
	$format_right =& $rep->addFormat();
	$format_right->setTextWrap();
	$format_right->setAlign('right');
	$format_right->setFontFamily('Calibri');
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	$format_bold->setFontFamily('Calibri');
	
	$format_bold_right =& $rep->addFormat();
	$format_bold_right->setBold();
	$format_bold_right->setAlign('right');
	$format_bold_right->setFontFamily('Calibri');
	
	$format_accounting =& $rep	->addFormat();
	$format_accounting->setNumFormat('_(* #,##0.00_);_(* (#,##0.00);_(* "-"??_);_(@_)');
	$format_accounting->setAlign('right');
	$format_accounting->setFontFamily('Calibri');
	
	$format_over_short =& $rep	->addFormat();
	$format_over_short->setNumFormat('#,##0.00_);[Red](#,##0.00);_(* "-"_);');
	$format_over_short->setAlign('right');
	$format_over_short->setFontFamily('Calibri');
	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_header);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date : '.$date_, $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'ASSET AND DEPRECIATION EXPENSE SUMMARY', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,20); //set column width
	$rep->sheet->setColumn(2,13,13); //set column width

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;
	
$th = array("",_('Date Acquired'),_('APV#'),_('Asset Name'), _('Supplier'),_('Inv.#'),'Asset#',' Serial#','Asset Type', _('Life'),_('Months Left'),_('Acquisition Cost'),'Monthly Depreciation',_('Total Depreciation'),_('Net Book Value'));
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

$mysql_q="select de.dep_id as de_id, de.date_acquired as de_date_acquired, de.apv_num as de_apv_num, de.invoice_num as de_invoice_num, de.asset_name as de_asset_name,
 de.supplier as de_supplier, de.life as de_life,de.expected_life_date as de_expected_life_date, de.asset_num as de_asset_num,de.serial_num as de_serial_num,
 de.asset_gl_type as de_asset_gl_type, ded.d_id as ded_d_id, de.acquisition_cost as de_acquisition_cost,ded.d_monthly_depreciation as ded_d_monthly_depreciation,
abs(sum(gl.amount)) as accum_depreciation,
round(de.acquisition_cost-sum(abs(gl.amount))) as net_book_value
from ".TB_PREF."dep_exp_fixed_assets as de
left join ".TB_PREF."dep_exp_depreciation_details as ded
on de.dep_id = ded.d_dep_id
left join ".TB_PREF."gl_trans as gl
on ded.d_id=gl.type_no
where gl.type='64' and gl.amount<0
and gl.tran_date<='$datefrom'";

if ($_POST['account']!='')
{
 $acc=$_POST['account'];
 $mysql_q.=" and de.asset_gl_type='".$acc."'";
 }
 
$mysql_q.= "group by gl.type_no";

//display_error($mysql_q);
$res2=db_query($mysql_q);
while($myrow = mysql_fetch_array($res2))
{
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
			//$months_life=date_diff2(sql2date($myrow["expected_life_date"]),sql2date($myrow["date_acquired"]),"m");
	$months_life=$myrow["de_life"]*12;
	$months_depreciated_times=date_diff2(sql2date($datefrom),sql2date($myrow["de_date_acquired"]),"m");
	$months_left=$months_life-$months_depreciated_times;
	if ($months_left<0)
	{
	$months_left=0;
	}
	$monthly_depreciation=$myrow["de_acquisition_cost"]/$months_life;
		
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $myrow["de_date_acquired"],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $myrow["de_apv_num"], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $myrow["de_asset_name"], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $myrow["de_supplier"], $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow["de_invoice_num"], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow["de_asset_num"], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow["de_serial_num"], $format_accounting);
		$x++;
		$rep->sheet->writeString($rep->y, $x, get_gl_account_name($myrow["de_asset_gl_type"]), $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $myrow["de_life"]." (Yrs.)", $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $months_left. " (Mos.)", $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow["de_acquisition_cost"], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x,$myrow["ded_d_monthly_depreciation"], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow["accum_depreciation"], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x,$myrow["net_book_value"], $format_accounting);
		$rep->y++;
		
	$t_acquisition_cost+=$myrow['de_acquisition_cost'];
	$t_monthly_depreciation+=$myrow['ded_d_monthly_depreciation'];
	$t_accum_depreciation_expense+=$myrow['accum_depreciation'];
	$t_book_value+=$myrow["net_book_value"];
	
	}
	
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, 'GRAND TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_acquisition_cost), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_monthly_depreciation), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_accum_depreciation_expense), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_book_value), $format_accounting);
	
	$rep->End();
}
//end of excel report------------------------------------------------------------------------------------


start_form();

start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	//gl_all_accounts_list_cells(_("Fixed Asset Account:"), 'account', null, false, false, "All Accounts");
	fixed_assets_list_cells(_('Fixed Asset Account:'), 'account', null,true); 
	date_cells(_("Date:"), 'date_', '', null);
	//date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);


div_start('totals');		
display_heading("Depreciation Expense Summary as of ".$_POST['date_']."");
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();

$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);

//start of table display
start_table($table_style2);
$th = array("",_('Date Acquired'),_('APV#'),_('Asset Name'), _('Supplier'),_('Inv.#'),'Asset#',' Serial#','Asset Type', _('Life'),_('Months Left'),_('Acquisition Cost'),'Monthly Depreciation',_('Total Depreciation'),_('Net Book Value'));
//,_('Times Depreciated')
table_header($th);

$mysql_q="select de.dep_id as de_id, de.date_acquired as de_date_acquired, de.apv_num as de_apv_num, de.invoice_num as de_invoice_num, de.asset_name as de_asset_name,
 de.supplier as de_supplier, de.life as de_life,de.expected_life_date as de_expected_life_date, de.asset_num as de_asset_num,de.serial_num as de_serial_num,
 de.asset_gl_type as de_asset_gl_type, ded.d_id as ded_d_id, de.acquisition_cost as de_acquisition_cost,ded.d_monthly_depreciation as ded_d_monthly_depreciation,
abs(sum(gl.amount)) as accum_depreciation,
round(de.acquisition_cost-sum(abs(gl.amount))) as net_book_value
from ".TB_PREF."dep_exp_fixed_assets as de
left join ".TB_PREF."dep_exp_depreciation_details as ded
on de.dep_id = ded.d_dep_id
left join ".TB_PREF."gl_trans as gl
on ded.d_id=gl.type_no
where gl.type='64' and gl.amount<0
and gl.tran_date<='$datefrom'";

if ($_POST['account']!='')
{
 $acc=$_POST['account'];
 $mysql_q.=" and de.asset_gl_type='".$acc."'";
 }
 
$mysql_q.= " group by gl.type_no";

//display_error($mysql_q);
$res2=db_query($mysql_q);

while($myrow = mysql_fetch_array($res2))
{
$c ++;
alt_table_row_color($k);

	//$months_life=date_diff2(sql2date($myrow["expected_life_date"]),sql2date($myrow["date_acquired"]),"m");
	$months_life=$myrow["de_life"]*12;
	$months_depreciated_times=date_diff2(sql2date($datefrom),sql2date($myrow["de_date_acquired"]),"m");
	$months_left=$months_life-$months_depreciated_times;
	if ($months_left<0)
	{
	$months_left=0;
	}
	$monthly_depreciation=$myrow["de_acquisition_cost"]/$months_life;
	label_cell($c,'align=right');
	label_cell($myrow["de_date_acquired"],'nowrap');
	label_cell($myrow["de_apv_num"],'nowrap');
	label_cell($myrow["de_asset_name"],'nowrap');
	label_cell($myrow["de_supplier"],'nowrap');
	label_cell($myrow["de_invoice_num"],'nowrap');
	label_cell($myrow["de_asset_num"],'nowrap');
	label_cell($myrow["de_serial_num"],'nowrap');
	label_cell(get_gl_account_name($myrow["de_asset_gl_type"]),'nowrap');
	label_cell($myrow["de_life"]." (Yrs.)",'nowrap');
	label_cell($months_left. " (Mos.)",'nowrap');
	//label_cell($months_depreciated_times. " (Mos.)",'nowrap');
	amount_cell($myrow["de_acquisition_cost"],'nowrap');
	amount_cell($myrow["ded_d_monthly_depreciation"],'nowrap');
	label_cell("<font color=red><b>".number_format2($myrow["accum_depreciation"],2)."<b></font>",'align=right');
	amount_cell($myrow["net_book_value"],'nowrap');	

	$t_acquisition_cost+=$myrow['de_acquisition_cost'];
	$t_monthly_depreciation+=$myrow['ded_d_monthly_depreciation'];
	$t_accum_depreciation_expense+=$myrow['accum_depreciation'];
	$t_book_value+=$myrow["net_book_value"];
}
	end_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell("<font color=#880000><b>"."GRAND TOTAL:"."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($t_acquisition_cost),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_monthly_depreciation),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_accum_depreciation_expense),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_book_value),2)."<b></font>",'align=right');
div_end();
end_table(1);
end_form();
end_page();
// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>