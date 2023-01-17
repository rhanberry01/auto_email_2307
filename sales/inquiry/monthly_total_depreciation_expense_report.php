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
	
page("Monthly Total Depreciation Expense Summary", false, false, "", $js);

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

    $rep = new FrontReport(_('Monthly Total Depreciation Expense Report'), "monthly_total_depreciation_expense_report", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'MONTHLY TOTAL DEPRECIATION EXPENSE', $format_bold);
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
	
$x=1;
$th = array(_('Fixed Asset Type'),_('January'),_('February'), _('March'),_('April'),'May','June','July', _('August'),_('September'),_('October'),'November',_('December'));
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;
	$c = $k = 0;

$mysql_q="select de_asset_gl_type, sum(Jan) as january, sum(Feb) as february,sum(Mar) as march,  
sum(Apr) as april, sum(May) as may, sum(Jun) as june, sum(Jul) as july, sum(Aug) as august,
sum(Sep) as september, sum(Octo) as october, sum(Nov) as november, sum(Dece) as december
from(
select de.asset_gl_type as de_asset_gl_type,
case when month(gl.tran_date)=1 then sum(abs(amount)) else 0 end as Jan,
case when month(gl.tran_date)=2 then sum(abs(amount)) else 0 end as Feb,
case when month(gl.tran_date)=3 then sum(abs(amount)) else 0 end as Mar,
case when month(gl.tran_date)=4 then sum(abs(amount)) else 0 end as Apr,
case when month(gl.tran_date)=5 then sum(abs(amount)) else 0 end as May,
case when month(gl.tran_date)=6 then sum(abs(amount)) else 0 end as Jun,
case when month(gl.tran_date)=7 then sum(abs(amount)) else 0 end as Jul,
case when month(gl.tran_date)=8 then sum(abs(amount)) else 0 end as Aug,
case when month(gl.tran_date)=9 then sum(abs(amount)) else 0 end as Sep,
case when month(gl.tran_date)=10 then sum(abs(amount)) else 0 end as Octo,
case when month(gl.tran_date)=11 then sum(abs(amount)) else 0 end as Nov,
case when month(gl.tran_date)=12 then sum(abs(amount)) else 0 end as Dece
from ".TB_PREF."dep_exp_fixed_assets as de
left join ".TB_PREF."dep_exp_depreciation_details as ded
on de.dep_id = ded.d_dep_id
left join ".TB_PREF."gl_trans as gl
on ded.d_id=gl.type_no
where gl.type='64' and gl.amount<0
and (gl.tran_date>='$datefrom' and gl.tran_date<='$dateto')
group by month(gl.tran_date), de.asset_gl_type) as x group by de_asset_gl_type";

$res2=db_query($mysql_q);

while($myrow = mysql_fetch_array($res2))
{
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, get_gl_account_name($myrow["de_asset_gl_type"]),$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['january'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['february'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['march'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['april'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['may'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['june'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['july'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['august'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['september'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['october'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['november'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrow['december'], $format_accounting);
		$rep->y++;
		
	$t_january+=$myrow['january'];
	$t_february+=$myrow['february'];
	$t_march+=$myrow['march'];
	$t_april+=$myrow['april'];
	$t_may+=$myrow['may'];
	$t_june+=$myrow['june'];
	$t_july+=$myrow['july'];
	$t_august+=$myrow['august'];
	$t_september+=$myrow['september'];
	$t_october+=$myrow['october'];
	$t_november+=$myrow['november'];
	$t_december+=$myrow['december'];
	}
	
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, 'GRAND TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_january, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_february, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_march, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_april, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_may, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_june, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_july, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_august, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_september, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_october, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_november, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_december, $format_accounting);
	$x++;
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
	date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);

$year_=explode_date_to_dmy($_POST['date_']);
div_start('totals');		
display_heading("Summary of Monthly Total Depreciation Expense for the year ".$year_[2]."."."");
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();

$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);

//start of table display
start_table($table_style2);
$th = array(_('Fixed Asset Type'),_('January'),_('February'), _('March'),_('April'),'May','June','July', _('August'),_('September'),_('October'),'November',_('December'));
//,_('Times Depreciated')
table_header($th);

$mysql_q="select de_asset_gl_type, sum(Jan) as january, sum(Feb) as february,sum(Mar) as march,  
sum(Apr) as april, sum(May) as may, sum(Jun) as june, sum(Jul) as july, sum(Aug) as august,
sum(Sep) as september, sum(Octo) as october, sum(Nov) as november, sum(Dece) as december
from(
select de.asset_gl_type as de_asset_gl_type,
case when month(gl.tran_date)=1 then sum(abs(amount)) else 0 end as Jan,
case when month(gl.tran_date)=2 then sum(abs(amount)) else 0 end as Feb,
case when month(gl.tran_date)=3 then sum(abs(amount)) else 0 end as Mar,
case when month(gl.tran_date)=4 then sum(abs(amount)) else 0 end as Apr,
case when month(gl.tran_date)=5 then sum(abs(amount)) else 0 end as May,
case when month(gl.tran_date)=6 then sum(abs(amount)) else 0 end as Jun,
case when month(gl.tran_date)=7 then sum(abs(amount)) else 0 end as Jul,
case when month(gl.tran_date)=8 then sum(abs(amount)) else 0 end as Aug,
case when month(gl.tran_date)=9 then sum(abs(amount)) else 0 end as Sep,
case when month(gl.tran_date)=10 then sum(abs(amount)) else 0 end as Octo,
case when month(gl.tran_date)=11 then sum(abs(amount)) else 0 end as Nov,
case when month(gl.tran_date)=12 then sum(abs(amount)) else 0 end as Dece
from ".TB_PREF."dep_exp_fixed_assets as de
left join ".TB_PREF."dep_exp_depreciation_details as ded
on de.dep_id = ded.d_dep_id
left join ".TB_PREF."gl_trans as gl
on ded.d_id=gl.type_no
where gl.type='64' and gl.amount<0
and (gl.tran_date>='$datefrom' and gl.tran_date<='$dateto')
group by month(gl.tran_date), de.asset_gl_type) as x group by de_asset_gl_type";

// if ($_POST['account']!='')
// {
 // $acc=$_POST['account'];
 // $mysql_q.=" and de.asset_gl_type='".$acc."'";
 // }
 
// $mysql_q.= "group by gl.type_no";

//display_error($mysql_q);
$res2=db_query($mysql_q);

while($myrow = mysql_fetch_array($res2))
{
$c ++;
alt_table_row_color($k);

	label_cell(get_gl_account_name($myrow["de_asset_gl_type"]));
	amount_cell($myrow["january"]);	
	amount_cell($myrow["february"]);
	amount_cell($myrow["march"]);
	amount_cell($myrow["april"]);	
	amount_cell($myrow["may"]);	
	amount_cell($myrow["june"]);
	amount_cell($myrow["july"]);
	amount_cell($myrow["august"]);
	amount_cell($myrow["september"]);	
	amount_cell($myrow["october"]);
	amount_cell($myrow["november"]);
	amount_cell($myrow["december"]);

	$t_january+=$myrow['january'];
	$t_february+=$myrow['february'];
	$t_march+=$myrow['march'];
	$t_april+=$myrow['april'];
	$t_may+=$myrow['may'];
	$t_june+=$myrow['june'];
	$t_july+=$myrow['july'];
	$t_august+=$myrow['august'];
	$t_september+=$myrow['september'];
	$t_october+=$myrow['october'];
	$t_november+=$myrow['november'];
	$t_december+=$myrow['december'];
}

	end_row();
	label_cell("<font color=#880000><b>"."GRAND TOTAL:"."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_january),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_february),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_march),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_april),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_may),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_june),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_july),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_august),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_september),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_october),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_november),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_december),2)."<b></font>",'align=right');
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