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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

if (isset($_GET['xls']) AND isset($_GET['filename']) AND isset($_GET['unique']))
{
	$path_to_root = "../..";
	include_once($path_to_root . "/includes/session.inc");
	
	header('Content-Disposition: attachment; filename='.$_GET['filename']);

	$target = $path_to_root.'/company/'.$_SESSION['wa_current_user']->company.'/pdf_files/'.$_GET['unique'];
	readfile($target);
	
	exit;
}

$page_security = 'SA_DEPOSIT';
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
	
page("Prepaid Card Sales", false, false, "", $js);
//-----------------------------------------------------------------------------------------------
function cashier_summary_per_day_excel()
{
	ini_set('mssql.connect_timeout',0);
	ini_set('mssql.timeout',0);
	set_time_limit(0);
	
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

    $rep = new FrontReport(_('Prepaid Card Sales'), "prepaid_card_sales", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'PREPAID CARD SALES', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date From: '.$date_.' To: '.$date_t.'', $format_bold);
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
	
	$th = array(' ', _("Date"), _("Cashier Name"),'Trans#', 'Counter#','Barcode', 'Description', 'UOM', 'Price', 'QTY', 'Extended');
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	
	$rep->y++;

	$c = $k = 0;

	$sql="SELECT 
	mu.name
	,fs.[TransactionNo]
	,fs.[ProductID]
	,fs.[ProductCode]
	,fs.[Barcode]
	,fs.[Description]
	,fs.[UOM]
	,fs.[Qty]
	,fs.[Packing]
	,fs.[TotalQty]
	,fs.[AverageUnitCost]
	,fs.[Price]
	,fs.[Extended] ,[Return]
	,fs.[UserID]
	,fs.[TerminalNo]
	,CAST(fs.[LogDate] as DATE) as LogDate
	,fs.[DateTime]
	,fs.[Voided]
	FROM [FinishedSales] as fs
	left join [MarkUsers] as mu
	on fs.UserID=mu.userid
	where Barcode IN (
	'999000701256',
	'4806522100015',
	'999000701225',
	'999000701461',
	'4806522100107',
	'999000701096',
	'999000701218',
	'999000701300',
	'999000701294',
	'999000701287',
	'999000701348',
	'4806522100046',
	'999000701089',
	'4806522100268',
	'999000701317',
	'999000701324',
	'999000701331',
	'999000701041',
	'4800607200812',
	'999000701119',
	'4800607100839',
	'999000715079'
	
	)
	and LogDate>='".$datefrom."' and LogDate<='".$dateto."'";
	//display_error($sql);
	$res=ms_db_query($sql);

while($row = mssql_fetch_array($res))
{
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
	
		$t_extended+=$row['Extended'];
		
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['LogDate'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['name'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['TransactionNo'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['TerminalNo'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['Barcode'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['Description'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['UOM'],$format_left);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x, $row['Price'], $format_accounting);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['Qty'],$format_left);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x, $row['Extended'], $format_accounting);
		$rep->y++;
		
		$t_extended+=$row['Extended'];
	}
	
	$x=1;
	$rep->y++;
	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;	
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;
	$rep->sheet->writeString($rep->y, $x, 'GRAND TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_extended, $format_accounting);
	
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

if ($_POST['RefreshInquiry']){

div_start('totals');		
display_heading("Prepaid Card Sales From ".$_POST['date_']." To ".$_POST['TransToDate']."");
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();

$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);

//start of table display
start_table($table_style2);

$th = array(' ', _("Date"), _("Cashier Name"),'Trans#', 'Counter#','Barcode', 'Description', 'UOM', 'Price', 'QTY', 'Extended');

table_header($th);


$sql="SELECT 
mu.name
,fs.[TransactionNo]
,fs.[ProductID]
,fs.[ProductCode]
,fs.[Barcode]
,fs.[Description]
,fs.[UOM]
,fs.[Qty]
,fs.[Packing]
,fs.[TotalQty]
,fs.[AverageUnitCost]
,fs.[Price]
,fs.[Extended] ,[Return]
,fs.[UserID]
,fs.[TerminalNo]
,CAST(fs.[LogDate] as DATE) as LogDate
,fs.[DateTime]
,fs.[Voided]
FROM [FinishedSales] as fs
left join [MarkUsers] as mu
on fs.UserID=mu.userid
where Barcode IN (
'999000701256',
'4806522100015',
'999000701225',
'999000701461',
'4806522100107',
'999000701096',
'999000701218',
'999000701300',
'999000701294',
'999000701287',
'999000701348',
'4806522100046',
'999000701089',
'4806522100268',
'999000701317',
'999000701324',
'999000701331',
'999000701041',
'4800607200812',
'999000701119',
'4800607100839')
and LogDate>='".$datefrom."' and LogDate<='".$dateto."'";
//display_error($sql);
$res=ms_db_query($sql);


while($row = mssql_fetch_array($res))
{
	$c ++;
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell(sql2date($row['LogDate']));
	label_cell($row['name']);
	label_cell($row['TransactionNo']);
	label_cell($row['TerminalNo']);
	label_cell($row['Barcode']);
	label_cell($row['Description']);
	label_cell($row['UOM']);
	amount_cell($row['Price']);
	label_cell($row['Qty']);
	amount_cell($row['Extended']);
	$t_extended+=$row['Extended'];
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
	label_cell('');
	label_cell("<font color=#880000><b>"."GRAND TOTAL:"."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_extended),2)."<b></font>",'align=right');
div_end();
}
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