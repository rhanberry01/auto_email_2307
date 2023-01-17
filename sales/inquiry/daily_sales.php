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
	
page("Daily Sales", false, false, "", $js);

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

    $rep = new FrontReport(_('Daily Sales'), "daily_sales", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'DAILY TOTAL SALES', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date : '.$date_, $format_bold);
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
	
$th = array(' ', _("Date"), _("Sales"),'Cash', 'SRS GC', 'GC', 'Terms', 'EVoucher', 'Suki Card', 'Debit Card','Credit Card','ATD','StockTrans');
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

$mysql_q="SELECT 
remittance_date as r_date,
SUM(total_cash) as t_cash,
SUM(total_srs_gc) as t_srsgc,
SUM(total_gc) as t_gc,
SUM(total_terms) as t_terms,
SUM(total_e_voucher) as t_evoucher,
SUM(total_suki_card) as t_sc,
SUM(total_debit_card) as t_dc,
SUM(total_credit_card) as t_cc,
SUM(total_atd) as t_atd,
SUM(total_stock_transfer) as t_st
FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date >='".$datefrom."' and remittance_date <='".$dateto."'
AND is_disapproved = 0
AND treasurer_id != 0
group by remittance_date";
$res2=db_query_rs($mysql_q);

$x_reading = get_all_sales_reading($datefrom,$dateto);
	while($row = db_fetch($res2))
	{ 
	
	$sales =$x_reading[$row['r_date']];
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['r_date'],$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $sales, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_cash'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_srsgc'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_gc'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_terms'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_evoucher'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_sc'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_dc'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_cc'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_atd'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_st'], $format_accounting);

		$rep->y++;
	$t_sales+=$sales;
	$t_cash+=$row['t_cash'];
	$t_srsgc+=$row['t_srsgc'];
	$t_gc+=$row['t_gc'];
	$t_terms+=$row['t_terms'];
	$t_evoucher+=$row['t_evoucher'];
	$t_sc+=$row['t_sc'];
	$t_dc+=$row['t_dc'];
	$t_cc+=$row['t_cc'];
	$t_atd+=$row['t_atd'];
	$t_st+=$row['t_st'];
	
	}
	
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, 'GRAND TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_sales, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_cash, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_srsgc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_gc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_terms, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_evoucher, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_sc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_dc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_cc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_atd, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_st, $format_accounting);
	
	
	$rep->End();
}
//end of excel report------------------------------------------------------------------------------------




start_form();

start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	date_cells(_("Date:"), 'date_', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);





div_start('totals');		
display_heading("Daily Total Sales From ".$_POST['date_']." To ".$_POST['TransToDate']."");
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();




$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);



//start of table display
start_table($table_style2);


$th = array(' ', _("Date"), _("Sales"),'Cash', 'SRS GC', 'GC', 'Terms', 'EVoucher', 'Suki Card', 'Debit Card','Credit Card','ATD');

table_header($th);

$mysql_q="SELECT 
remittance_date as r_date,
SUM(total_cash) as t_cash,
SUM(total_srs_gc) as t_srsgc,
SUM(total_gc) as t_gc,
SUM(total_terms) as t_terms,
SUM(total_e_voucher) as t_evoucher,
SUM(total_suki_card) as t_sc,
SUM(total_debit_card) as t_dc,
SUM(total_credit_card) as t_cc,
SUM(total_atd) as t_atd,
SUM(total_stock_transfer) as t_st
FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date >='".$datefrom."' and remittance_date <='".$dateto."'
AND is_disapproved = 0
AND treasurer_id != 0
group by remittance_date";

$res2=db_query_rs($mysql_q);


$x_reading = get_all_sales_reading($datefrom,$dateto);
while($row = mysql_fetch_array($res2))
{

$sales =$x_reading[$row['r_date']];

$c ++;
alt_table_row_color($k);
label_cell($c,'align=right');
label_cell($row['r_date']);
amount_cell(abs($sales));
	amount_cell($row['t_cash']);
	amount_cell($row['t_srsgc']);
	amount_cell($row['t_gc']);
	amount_cell($row['t_terms']);
	amount_cell($row['t_evoucher']);
	amount_cell($row['t_sc']);
	amount_cell($row['t_dc']);
	amount_cell($row['t_cc']);
	amount_cell($row['t_atd']);


	$t_sales+=$sales;
	$t_cash+=$row['t_cash'];
	$t_srsgc+=$row['t_srsgc'];
	$t_gc+=$row['t_gc'];
	$t_terms+=$row['t_terms'];
	$t_evoucher+=$row['t_evoucher'];
	$t_sc+=$row['t_sc'];
	$t_dc+=$row['t_dc'];
	$t_cc+=$row['t_cc'];
	$t_atd+=$row['t_atd'];

	
}



	end_row();
	label_cell('');
	label_cell("<font color=#880000><b>"."GRAND TOTAL:"."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_sales),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_cash),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_srsgc),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_gc),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_terms),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_evoucher),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_sc),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_dc),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_cc),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_atd),2)."<b></font>",'align=right');

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