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
$page_security = 'SA_GLANALYTIC';
$path_to_root="../..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($use_date_picker)
	$js = get_js_date_picker();

page(_($help_context = "Sales Comparison"), false, false, "", $js);

//----------------------------------------------------------------------------------------------------
start_form();
if (!isset($_POST['year']))
	//$_POST['year'] = date('Y',strtotime('-1 year'));
$_POST['year'] = date('Y');

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
//WHEN 4000040 THEN '3' 
$col_total = array();
$sql = "SELECT CASE account
						WHEN 4000 THEN '0'
						WHEN 4000020 THEN '1'
						WHEN 4000050 THEN '2'
						WHEN 4020 THEN '5'
						WHEN 4000040 THEN '3' 
						END AS num,account,";
for($i=1; $i<=12; $i++)
{
	$start_date = date2sql(begin_month(__date($_POST['year'], $i, 1)));
	$end_date = date2sql(end_month(__date($_POST['year'], $i, 1)));
	$sql .= " abs(SUM(if(tran_date >= '$start_date' AND tran_date <= '$end_date',amount,0))) as `". date('n',strtotime($start_date)) ."`";
	if ($i != 12)
		$sql .= ",";
}
//4000040,
$sql .= " FROM 0_gl_trans
			WHERE account IN (
			4000020,
			4000050,
			4020,
			4000)
			AND tran_date >= '".$_POST['year']."-01-01'
			AND tran_date <= '".$_POST['year']."-12-31'
			AND type = 100
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
			AND (type = 100 OR memo_ LIKE 'Sales using Finished Sales Table for %')
			GROUP BY account ";
 // display_error($sql.' UNION '. $sql2 . ' ORDER BY num');

//die();
$res = db_query($sql.' UNION '. $sql2 . ' ORDER BY num');

$xx = 0;
$yy = '';

$other_income = array();
while($row = db_fetch($res))
{
	
	if ($row['num'] != 5)
	{
		alt_table_row_color($k);
		label_cell('<b>'.$as_title[$row['num']].'**</b>','nowrap');
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
// NON VAT DIFF
alt_table_row_color($k);
label_cell('&nbsp;','colspan=13');
end_row();
alt_table_row_color($k);
label_cell('<b>NON VAT DIFFERENCE</b>');
foreach($col_total as $key => $c_tot)
{
	amount_cell('', 'true');
}
end_row();

// DIFFERENCE
alt_table_row_color($k);
label_cell('&nbsp;','colspan=13');
end_row();
alt_table_row_color($k);
label_cell('<b>TOTAL DIFFERENCE</b>');
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

