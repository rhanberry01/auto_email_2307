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


$page_security = 'SA_GLANALYTIC';
$path_to_root="../..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");




include_once($path_to_root . "/reporting/includes/reporting.inc");



	//start of excel report
if(isset($_POST['dl_excel']))
{
	Yearly_Other_Income_Report();
	exit;
}






$js = "";
if ($use_date_picker)
	$js = get_js_date_picker();

page(_($help_context = "Sales Comparison"), false, false, "", $js);




function Yearly_Other_Income_Report()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$agency=$_POST['agency'];
	$date_= $_POST['date_'];
	$date_t= $_POST['TransToDate'];
	
$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
	
	//display_error($agency);

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Agency :'), 'from' => $agency_name, 'to' => '')
						);

    $rep = new FrontReport(_('Yearly Other Income Report'), "Yearly_Other_Income_Report", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'Yearly Other Income Report', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,30);
	$rep->sheet->setColumn(2,20,10);

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
$th = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;

$as_title = array('SALES VAT', 'SALES NON VAT', 'ZERO RATED', 'SUKI POINTS', 'OUTPUT VAT');
$es_title = array('SALES VAT', 'EXEMPT', 'ZERO RATED','');
$as = $es = array();

// GET FROM ARIA

$col_total = array();
$sql = "SELECT CASE account
						WHEN 4000 THEN '0'
						WHEN 4000020 THEN '1'
						WHEN 4000050 THEN '2'
						WHEN 4000040 THEN '3'
						WHEN 4020 THEN '5'
						END AS num,account,";
for($i=1; $i<=12; $i++)
{
	$start_date = date2sql(begin_month(__date($_POST['year'], $i, 1)));
	$end_date = date2sql(end_month(__date($_POST['year'], $i, 1)));
	$sql .= " abs(SUM(if(tran_date >= '$start_date' AND tran_date <= '$end_date',amount,0))) as `". date('n',strtotime($start_date)) ."`";
	if ($i != 12)
		$sql .= ",";
}
$sql .= " FROM 0_gl_trans
			WHERE account IN (4000040,
			4000020,
			4000050,
			4020,
			4000)
			AND tran_date >= '".$_POST['year']."-01-01'
			AND tran_date <= '".$_POST['year']."-12-31'
			GROUP BY account";
			
//---------------------------------------- for output vat of sales 			
$sql2 = "SELECT '4' as num,account,";
for($i=1; $i<=12; $i++)
{
	$start_date = date2sql(begin_month(__date($_POST['year'], $i, 1)));
	$end_date = date2sql(end_month(__date($_POST['year'], $i, 1)));
	$sql2 .= " abs(SUM(if(tran_date >= '$start_date' AND tran_date <= '$end_date',amount,0))) as `". date('n',strtotime($start_date)) ."`";
	if ($i != 12)
		$sql2 .= ",";
}
$sql2 .= " FROM 0_gl_trans
			WHERE account = 2310 
			AND tran_date >= '".$_POST['year']."-01-01'
			AND tran_date <= '".$_POST['year']."-12-31'
			AND (type = 60 OR memo_ LIKE 'Sales using Finished Sales Table for %')
			GROUP BY account ";
// display_error($sql.' UNION '. $sql2 . ' ORDER BY num');


$res = db_query($sql.' UNION '. $sql2 . ' ORDER BY num');

$xx = 0;
$yy = '';

$other_income = array();
while($row = db_fetch($res))
{
	
	if ($row['num'] != 5)
	{
		$rep->sheet->writeString($rep->y, $x, get_gl_account_name($as_title[$row['num']]),$format_left);
		$x++;
	}
	
	for($i=1; $i<=12; $i++)
	{
		if ($row['num'] != 5)
		{
		$rep->sheet->writeNumber($rep->y, $x, $row[$i], $format_center);
		$x++;
			$col_total[$i] += $row[$i];
		}
		else
			$other_income[] = $row[$i];
	}
	
	if ($row['num'] != 5)
	$rep->y++;
}
	$x=0;
	$rep->y++;

	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;

foreach($col_total as $c_tot)
{
	$rep->sheet->writeNumber($rep->y, $x, $c_tot, $format_right);
	$x++;
}
	$rep->y++;

//==========================================================
// E SALES
	$rep->sheet->writeString($rep->y, $x, 'E-SALES', $format_bold);
	$x++;
// label_cell('','colspan=12');
	$rep->y++;

$sql = "SELECT * FROM x_fsales_monthly";
$res = db_query($sql);

while($row = db_fetch($res))
{
	// 0 = sales vat
	// 1 = sales non vat
	// 2 = sales zero rated
	$es[$row['month']][0] = $row['sales_vat'];
	$es[$row['month']][1] = $row['sales_nv'];
	$es[$row['month']][2] = $row['sales_zero_vat'];
	$es[$row['month']][3] = $row['sales_vat'] + $row['sales_nv'] + $row['sales_zero_vat'];
}

for($x=0; $x<=3; $x++)
{
	
	$rep->sheet->writeString($rep->y, $x, $es_title[$x], $format_bold);
	$x++;
	
	for($i=1; $i<=12; $i++)
	{
	$rep->sheet->writeNumber($rep->y, $x, $es[$i][$x], $format_right);
	$x++;
	}
	
	$rep->y++;
}

//==========================================================
// DIFFERENCE

	$rep->sheet->writeString($rep->y, $x, '&nbsp;', $format_bold);
	$x++;
	
$rep->y++;

	$rep->sheet->writeString($rep->y, $x, 'DIFFERENCE', $format_bold);
	$x++;

foreach($col_total as $key => $c_tot)
{
	$rep->sheet->writeNumber($rep->y, $x, $c_tot - $es[$key][3], $format_right);
	$x++;
}
$rep->y++;


// for other income

	$rep->sheet->writeString($rep->y, $x, 'OTHER INCOME', $format_bold);
	$x++;


foreach($other_income as $oi)
{
	amount_cell($oi);
	$rep->sheet->writeNumber($rep->y, $x, $oi, $format_right);
	$x++;
}
$rep->y++;


	$rep->End();

}



























	
$x=0;

$th = array('','', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');


	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;
	
	
	$as_title = array('4020',
			'4020010',
			'4020020',
			'4020050',
			'4020030',
			'4020051',
			'4020025',
			'4020060',
			'4020052',
			'402005',
			'4020061',
			'4021',
			'4020062',
			'4020063',
			'4020070',
			'4030'
			);


	$c = $k = 0;

// GET FROM ARIA
$col_total = array();
$sql = "SELECT CASE account
WHEN 4020 THEN '0'
WHEN 4020010 THEN '1'
WHEN 4020020 THEN '2'
WHEN 4020050 THEN '3'
WHEN 4020030 THEN '4'
WHEN 4020051 THEN '5'
WHEN 4020025 THEN '6'
WHEN 4020060 THEN '7'
WHEN 4020052 THEN '8'
WHEN 402005 THEN '9'
WHEN 4020061 THEN '10'
WHEN 4021 THEN '11'
WHEN 4020062 THEN '12'
WHEN 4020063 THEN '13'
WHEN 4020070 THEN '14'
WHEN 4030 THEN '15'
END AS num,account,";
for($i=1; $i<=12; $i++)
{
	$start_date = date2sql(begin_month(__date($_POST['year'], $i, 1)));
	$end_date = date2sql(end_month(__date($_POST['year'], $i, 1)));
	$sql .= " abs(SUM(if(tran_date >= '$start_date' AND tran_date <= '$end_date',amount,0))) as `". date('n',strtotime($start_date)) ."`";
	if ($i != 12)
		$sql .= ",";
}
$sql .= " FROM 0_gl_trans
			WHERE account IN (4020,
			4020010,
			4020020,
			4020050,
			4020030,
			4020051,
			4020025,
			4020060,
			4020052,
			402005,
			4020061,
			4021,
			4020062,
			4020063,
			4020070,
			4030
			)
			AND tran_date >= '".$_POST['year']."-01-01'
			AND tran_date <= '".$_POST['year']."-12-31'
			AND amount<0
			GROUP BY account ORDER BY account";
$res = db_query($sql);

//display_error($sql);


while($row = db_fetch($res))
{
		$c ++;
		$x = 0;
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
	
	if ($row['num'] != 14)
	{
		$rep->sheet->writeString($rep->y, $x, get_gl_account_name($as_title[$row['num']]),$format_left);
		$x++;
	}
	
	for($i=1; $i<=12; $i++)
	{
		if ($row['num'] != 14)
		{
		$rep->sheet->writeNumber($rep->y, $x, $row[$i], $format_center);
		$x++;
		$col_total[$i] += $row[$i];
		}

	}
	
	if ($row['num'] != 14)
	$rep->y++;
}

	$x=1;
	$rep->y++;

	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold);
	$x++;
	

foreach($col_total as $c_tot)
{
		$rep->sheet->writeNumber($rep->y, $x, $c_tot, $format_right);
		$x++;
}

	
	$rep->End();

}















//----------------------------------------------------------------------------------------------------
start_form();
if (!isset($_POST['year']))
	$_POST['year'] = date('Y',strtotime('-1 year'));

echo "<center>";

text_cells('Year : ', 'year');
submit_cells('proceed','Proceed');

br(2);
echo "</center>";

start_table($table_style2.' width=90%');

$k = 0;
$th = array('', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

table_header($th);

$as_title = array('SALES VAT', 'SALES NON VAT', 'ZERO RATED', 'SUKI POINTS', 'OUTPUT VAT');
$es_title = array('SALES VAT', 'EXEMPT', 'ZERO RATED','');
$as = $es = array();

// GET FROM ARIA

$col_total = array();
$sql = "SELECT CASE account
						WHEN 4000 THEN '0'
						WHEN 4000020 THEN '1'
						WHEN 4000050 THEN '2'
						WHEN 4000040 THEN '3'
						WHEN 4020 THEN '5'
						END AS num,account,";
for($i=1; $i<=12; $i++)
{
	$start_date = date2sql(begin_month(__date($_POST['year'], $i, 1)));
	$end_date = date2sql(end_month(__date($_POST['year'], $i, 1)));
	$sql .= " abs(SUM(if(tran_date >= '$start_date' AND tran_date <= '$end_date',amount,0))) as `". date('n',strtotime($start_date)) ."`";
	if ($i != 12)
		$sql .= ",";
}
$sql .= " FROM 0_gl_trans
			WHERE account IN (4000040,
			4000020,
			4000050,
			4020,
			4000)
			AND tran_date >= '".$_POST['year']."-01-01'
			AND tran_date <= '".$_POST['year']."-12-31'
			GROUP BY account";
			
//---------------------------------------- for output vat of sales 			
$sql2 = "SELECT '4' as num,account,";
for($i=1; $i<=12; $i++)
{
	$start_date = date2sql(begin_month(__date($_POST['year'], $i, 1)));
	$end_date = date2sql(end_month(__date($_POST['year'], $i, 1)));
	$sql2 .= " abs(SUM(if(tran_date >= '$start_date' AND tran_date <= '$end_date',amount,0))) as `". date('n',strtotime($start_date)) ."`";
	if ($i != 12)
		$sql2 .= ",";
}
$sql2 .= " FROM 0_gl_trans
			WHERE account = 2310 
			AND tran_date >= '".$_POST['year']."-01-01'
			AND tran_date <= '".$_POST['year']."-12-31'
			AND (type = 60 OR memo_ LIKE 'Sales using Finished Sales Table for %')
			GROUP BY account ";
// display_error($sql.' UNION '. $sql2 . ' ORDER BY num');


$res = db_query($sql.' UNION '. $sql2 . ' ORDER BY num');

$xx = 0;
$yy = '';

$other_income = array();
while($row = db_fetch($res))
{
	
	if ($row['num'] != 5)
	{
		alt_table_row_color($k);
		label_cell('<b>'.$as_title[$row['num']].'</b>','nowrap');
	}
	
	for($i=1; $i<=12; $i++)
	{
		if ($row['num'] != 5)
		{
			amount_cell($row[$i]);
			$col_total[$i] += $row[$i];
		}
		else
			$other_income[] = $row[$i];
	}
	
	if ($row['num'] != 5)
	end_row();
}

alt_table_row_color($k);
label_cell('');
foreach($col_total as $c_tot)
{
	amount_cell($c_tot, 'true');
}
end_row();

//==========================================================
// E SALES
alt_table_row_color($k);
label_cell('<b>E-SALES</b>','colspan=13');
// label_cell('','colspan=12');
end_row();

$sql = "SELECT * FROM x_fsales_monthly";
$res = db_query($sql);

while($row = db_fetch($res))
{
	// 0 = sales vat
	// 1 = sales non vat
	// 2 = sales zero rated
	$es[$row['month']][0] = $row['sales_vat'];
	$es[$row['month']][1] = $row['sales_nv'];
	$es[$row['month']][2] = $row['sales_zero_vat'];
	$es[$row['month']][3] = $row['sales_vat'] + $row['sales_nv'] + $row['sales_zero_vat'];
}

for($x=0; $x<=3; $x++)
{
	alt_table_row_color($k);
	label_cell($es_title[$x],'nowrap');
	
	for($i=1; $i<=12; $i++)
	{
		amount_cell($es[$i][$x], $x==3);
	}
	
	end_row();
}

//==========================================================
// DIFFERENCE
alt_table_row_color($k);
label_cell('&nbsp;','colspan=13');
end_row();
alt_table_row_color($k);
label_cell('<b>DIFFERENCE</b>');
foreach($col_total as $key => $c_tot)
{
	amount_cell($c_tot - $es[$key][3], 'true');
}
end_row();


// for other income
alt_table_row_color($k);
label_cell('<b>OTHER INCOME</b>');
foreach($other_income as $oi)
{
	amount_cell($oi);
}
end_row();


end_table('');

end_form();

end_page();

?>

