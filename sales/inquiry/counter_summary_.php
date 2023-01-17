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
// xls=1&filename=CounterSummary.xls&unique=5231644fc0955.xls
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
// include_once($path_to_root . "/reporting/includes/reporting.inc");

if(isset($_POST['dl_excel']))
{
	counter_summary_excel();
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
set_time_limit(0);
page("Counter Summary", false, false, "", $js);

//------------------------------------------------------------------------------------------------


if (!isset($_POST['date_from']))
	$_POST['date_from'] = begin_month(Today());
	
// $_POST['date_from'] = '07/01/2013';
// $_POST['date_to'] = '07/31/2013';

start_form();
start_table();
start_row();
	date_cells('From : ', 'date_from');
	date_cells('To : ', 'date_to');
	label_cell('&nbsp;&nbsp;&nbsp;');
	submit_cells('dl_excel', 'Download Excel File');
end_row();
end_table(1);

function counter_summary_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$from = $_POST['date_from'];
	$to = $_POST['date_to'];
	
	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Counter Summary'), "CounterSummary", "LETTER");
	
    $rep->Font();
	
	$format_bold_title =& $rep->addFormat();
	$format_bold_title->setTextWrap();
	$format_bold_title->setBold();
	$format_bold_title->setAlign('center');
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
	$format_bold_right =& $rep->addFormat();
	$format_bold_right->setBold();
	$format_bold_right->setAlign('right');
	
	$format_accounting =& $rep	->addFormat();
	$format_accounting->setNumFormat('#,##0.00');
	$format_accounting->setAlign('right');
	$format_accounting->setFontFamily('Calibri');
	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'From : '.$from.'   To: '.$to, $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,4);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);
	
	$sql = "SELECT TerminalNo,sum(net_amount)as total_sales FROM (SELECT DISTINCT
	TerminalNo,
	((case when [Return]=0 then SUM(abs(extended)) else 0 end) 
	+ (case when [Return]=1 then -SUM(abs(Extended)) else 0 end)) as net_amount
	FROM FinishedSales 
	WHERE LogDate >= '".date2sql($_POST['date_from'])."'
	AND LogDate <= '".date2sql($_POST['date_to'])."'
	AND Voided='0'
	GROUP BY [Return],TerminalNo) as x 
	GROUP BY TerminalNo";

	$res = ms_db_query($sql);

	$th = array('');
	$th2 = array();

	$r_sql = "SELECT LogDate";
	while($row = mssql_fetch_array($res))
	{
		$th[] = $row['TerminalNo'];
		$th2[] = $row['TerminalNo'];
		$r_sql .= ", SUM(CASE WHEN pVatable = 1 AND TerminalNo = '".$row['TerminalNo']."' THEN net_amount ELSE 0 END)  as VatableSales_".$row['TerminalNo'];
		$r_sql .= ", SUM(CASE WHEN pVatable = 0 AND TerminalNo = '".$row['TerminalNo']."' THEN net_amount ELSE 0 END)  as NonVatableSales_".$row['TerminalNo'];
	}

			$r_sql .= " FROM (SELECT DISTINCT TerminalNo,LogDate,pVatable,
			((case when [Return]=0 then SUM(extended) else 0 end) 
			+ (case when [Return]=1 then -SUM(abs(Extended)) else 0 end)) as net_amount
			FROM FinishedSales 
			WHERE LogDate >='".date2sql($_POST['date_from'])."'
			AND LogDate <='".date2sql($_POST['date_to'])."'
			AND Voided='0'
			group by [Return],TerminalNo,LogDate,pVatable) as x 
			group by Logdate
			order by Logdate";
			
	// display_error($r_sql);die;
	$r_res = ms_db_query($r_sql);
	$th[] = 'Reading';
	$th[] = 'VAT';
	$th[] = 'VAT SALES';
	$th[] = 'NON VAT';
	$th[] = 'Reading';
	$th[] = '';
		
	// table_header($th); //write header
	
	$rep->sheet->setColumn(1,count($th),13);
	
	$x=0;
	$rep->sheet->writeString($rep->y, $x, 'Date', $format_bold);
	$rep->sheet->writeString($rep->y, $x+1, 'Terminal', $format_bold);
	$rep->y++;
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;
	
	while($r_row = mssql_fetch_array($r_res))
	{
		$x=0;
		$day_reading = $day_reading2 = $day_vat = $day_vat_sales = $day_non_vat = 0;
		
		// alt_table_row_color($k);
		$rep->sheet->writeString($rep->y, $x, date('d',strtotime($r_row['LogDate'])), $format_bold_title);
		$x++;
		
		foreach($th2 as $counter)
		{
			$counter_vatable = round($r_row['VatableSales_'.$counter],2);
			$counter_non_vat = round($r_row['NonVatableSales_'.$counter],2);
			$counter_reading = round($counter_vatable + $counter_non_vat,2);
			
			$day_reading += $counter_reading;
			
			
			$counter_vat_sales = round($counter_vatable/1.12,2);
			$counter_vat = round($counter_vatable - $counter_vat_sales,2);
			
			$day_vat_sales += $counter_vat_sales;
			$day_vat += $counter_vat;
			$day_non_vat += $counter_non_vat;
			
			$day_reading2 += $counter_vat_sales + $counter_vat + $counter_non_vat;
			
			if ($counter_reading > 0)
				$rep->sheet->writeNumber($rep->y, $x, $counter_reading, $format_accounting);
			else
				$rep->sheet->writeString($rep->y, $x, ' -  ', $rep->formatRight);
			
			$x++;
		}
		
		$rep->sheet->writeNumber($rep->y, $x, $day_reading, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $day_vat, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $day_vat_sales, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $day_non_vat, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $day_reading2, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, round($day_reading,2) - round($day_reading2,2), $format_accounting);
		$x++;

		$rep->y++;
	}
	
	$rep->y++;
	
	$rep->End();
}

end_form();
end_page();

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>