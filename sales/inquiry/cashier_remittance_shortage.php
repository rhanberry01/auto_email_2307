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
	
page("Cashier Shortage Summary", false, false, "", $js);

//------------------------------------------------------------------------------------------------

function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$date_= $_POST['date_'];
	$date_2=$_POST['TransToDate'];
	

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Cashier Remittance Shortage'), "Cashier_Remittance_Shortage", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'Date : from '.$date_.' to '.$date_2, $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'SUMMARY OF CASHIER REMITTANCE SHORTAGE', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,20); //set column width
	$rep->sheet->setColumn(2,17,9); //set column width

	
	//setColumn(cellnum,cellsize/width,colnum);
	
$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
$agency_id=$_POST['agency_name'];
$cashier_id=$_POST['cashier_id'];

$x=0;
	//start of table display
start_table($table_style2);
$datefrom1=$_POST['date_'];
$dateto1=$_POST['TransToDate'];
$d1=explode("/",$datefrom1);
$day1 = $d1[1];
$month1 =$d1[0];
$year1 = $d1[2]; 

$d2=explode("/",$dateto1);
$day2 = $d2[1];
$month2 =$d2[0];
$year2 = $d2[2]; 


//TABLE HEADER
$rep->sheet->writeString($rep->y, $x, '', $format_bold_title);
$x++;
$rep->sheet->writeString($rep->y, $x, 'Cashier Name', $format_bold_title);
$x++;
//labelheader_cell('Agency');
for ($i = $day1; $i <= $day2; $i++)
{
$date_head=$i;

$rep->sheet->writeString($rep->y, $x, "Day".$i, $format_bold_title);
$x++;
}
$rep->sheet->writeString($rep->y, $x, 'TOTAL', $format_bold_title);
$x++;
//
$rep->y++;



$c = $k = 0;


$sql="select";
for ($i = $day1; $i <= $day2; $i++)
{ 
$sql.=" a".$i."";
$sql.=",";
}

$sql.=" t_short_over,
case when ISNULL(hrj.agency_id) then 0 else hrj.agency_id end as agency, 
a.cashier_id as cashier_id, 
a.emp_id as emp_id, a.cashier_name as cashier_name
from (SELECT 
rs.cashier_id as cashier_id, hl.hr_emp_id as emp_id, rs.cashier_name as 
cashier_name,";
for ($i = $day1; $i <= $day2; $i++)
{
$sql.=" sum(case when rs.r_summary_date='$year1-$month1-".$i."' and rs.over_short < 0 then over_short else 0 end) as a".$i."";
$sql.=",";
}
$sql.=" sum(rs.over_short) as t_short_over
FROM ".CR_DB.TB_PREF."remittance_summary as rs
left join ".CR_DB.TB_PREF."hris_link as hl on 
rs.cashier_id=hl.hr_cashier_id 
where rs.r_summary_date>='".$datefrom."' and rs.r_summary_date<='".$dateto."'
and rs.over_short <0";


$sql.=" GROUP BY rs.cashier_id,hl.hr_emp_id) as a left join 
".CR_DB.TB_PREF."hr_employee as hr on hr.employee_id=a.emp_id 
LEFT JOIN ".CR_DB.TB_PREF."hr_emp_job_info as hrj
ON hr.emp_number=hrj.emp_id ";
//display_error($sql);

if ($agency_id!='')
{
$sql.=" and hl.agency_id='".$agency_id."'";
}

// if ($cashier_id!='')
// {
// $sql.=" and rs.cashier_id='".$cashier_id."'";
// }


$sql.="GROUP BY cashier_id,emp_id ORDER BY agency,cashier_name";

$res=db_query_rs($sql);

	while($row = db_fetch($res))
	{
	$t_shortover=$row['t_short_over'];
	
		$c ++;
		$x = 0;
		// alt_table_row_color($k);

		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x,$row['cashier_name'],$format_bold);
		$x++;
		
		
	for ($a = $day1; $a <= $day2; $a++)
	{
	$row_a="a".$a."";
	
	if($row[$row_a] <0)
	{
	$rep->sheet->writeNumber($rep->y, $x, abs($row[$row_a]), $format_accounting);
	$x++;
	}
	else
	{
	$rep->sheet->writeNumber($rep->y, $x, abs($row[$row_a]), $format_accounting);
	$x++;
	
	}
	}
	
	$rep->sheet->writeNumber($rep->y, $x, abs($t_shortover), $format_bold_right);
	$x++;
	$rep->y++;
  
}													
	
//GRAND TOTAL

$sql="select";
for ($i = $day1; $i <= $day2; $i++)
{ 
$sql.=" sum(b".$i.") as b".$i."";
if($i<$day2)
{
$sql.=",";
}
}

$sql.=" 
from (SELECT 
rs.cashier_id as cashier_id, hl.hr_emp_id as emp_id, rs.cashier_name as 
cashier_name,";
for ($i = $day1; $i <= $day2; $i++)
{
$sql.=" sum(case when rs.r_summary_date='$year1-$month1-".$i."' and rs.over_short < 0 then over_short else 0 end) as b".$i."";
$sql.=",";
}
$sql.=" sum(rs.over_short) as t_short_over
FROM ".CR_DB.TB_PREF."remittance_summary as rs
left join ".CR_DB.TB_PREF."hris_link as hl on 
rs.cashier_id=hl.hr_cashier_id 
 left join 
".CR_DB.TB_PREF."hr_employee as hr on hr.emp_number=hl.hr_emp_id 
 LEFT JOIN ".CR_DB.TB_PREF."hr_emp_job_info as hrj
ON hr.emp_number=hrj.emp_id
where rs.r_summary_date>='".$datefrom."' and rs.r_summary_date<='".$dateto."'
and rs.over_short <0";

if ($agency_id!='')
{
$sql.=" and hrj.agency_id='".$agency_id."'";
}

$sql.=" GROUP BY rs.cashier_id,hl.hr_emp_id) as  a";



// if ($cashier_id!='')
// {
// $sql.=" and rs.cashier_id='".$cashier_id."'";
// }


//display_error($sql);

$res=db_query_rs($sql);
$x = 0;
$rep->sheet->writeString($rep->y, $x, '', $format_center);
$x++;
$rep->sheet->writeString($rep->y, $x,'GRAND TOTAL:',$format_bold);
$x++;

while($row = db_fetch($res))
{

	for ($a = $day1; $a <= $day2; $a++)
	{
	$row_b="b".$a."";
	if($row[$row_b] <0)
	{
	$rep->sheet->writeNumber($rep->y, $x, abs($row[$row_b]), $format_bold);
	$x++;
	}
	else
	{
	$rep->sheet->writeNumber($rep->y, $x, abs($row[$row_b]), $format_bold);
	$x++;
	}
	$t_amount+=$row[$row_b];
	}

}
	$rep->sheet->writeNumber($rep->y, $x, abs($t_amount), $format_bold_right);
	$x++;
	$rep->y++;

	$rep->End();
	
}

//end of excel report------------------------------------------------------------------------------------


function get_employee_agency_name($agency_id)
{
	$sql="select agency_name from orange.hs_hr_agency where id='$agency_id'";
	$result = db_query($sql);
	$row=db_fetch($result);
	$name=$row['agency_name'];
	return $name; 
}

start_form();

start_table();
start_row();
	hr_agency_list_cells('AGENCY:','agency_name');
	//cashier_agency_list_cells(_('Agency :'), 'agency_name', null,true); 
	//get_cashier_list_cells('Cashier:', 'cashier_id');
	date_cells(_("Date:"), 'date_', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);

div_start('totals');		
display_heading("Cashier Shortage Remittance From ".$_POST['date_']." To ".$_POST['TransToDate']."");
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();


$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
$agency_id=$_POST['agency_name'];
$cashier_id=$_POST['cashier_id'];
//start of table display
start_table($table_style2);

$datefrom1=$_POST['date_'];
$dateto1=$_POST['TransToDate'];
$d1=explode("/",$datefrom1);
$day1 = $d1[1];
$month1 =$d1[0];
$year1 = $d1[2]; 

$d2=explode("/",$dateto1);
$day2 = $d2[1];
$month2 =$d2[0];
$year2 = $d2[2]; 


//TABLE HEADER
labelheader_cell('');
labelheader_cell('Cashier Name');
//labelheader_cell('Agency');
for ($i = $day1; $i <= $day2; $i++)
{
$date_head=$i;
labelheader_cell("Day".$i);
}
labelheader_cell('Total');
//


$sql="select";
for ($i = $day1; $i <= $day2; $i++)
{ 
$sql.=" a".$i."";
$sql.=",";
}


$sql.=" t_short_over,
case when ISNULL(hrj.agency_id) then 0 else hrj.agency_id end as agency, 
a.cashier_id as cashier_id, 
a.emp_id as emp_id, a.cashier_name as cashier_name
from (SELECT 
rs.cashier_id as cashier_id, hl.hr_emp_id as emp_id, rs.cashier_name as 
cashier_name,";
for ($i = $day1; $i <= $day2; $i++)
{
$sql.=" sum(case when rs.r_summary_date='$year1-$month1-".$i."' and rs.over_short < 0 then over_short else 0 end) as a".$i."";
$sql.=",";
}
$sql.=" sum(rs.over_short) as t_short_over
FROM ".CR_DB.TB_PREF."remittance_summary as rs
left join ".CR_DB.TB_PREF."hris_link as hl on 
rs.cashier_id=hl.hr_cashier_id 
where rs.r_summary_date>='".$datefrom."' and rs.r_summary_date<='".$dateto."'
and rs.over_short <0";

$sql.=" GROUP BY rs.cashier_id,hl.hr_emp_id) as a left join 
".CR_DB.TB_PREF."hr_employee as hr on hr.employee_id=a.emp_id 
LEFT JOIN ".CR_DB.TB_PREF."hr_emp_job_info as hrj
ON hr.emp_number=hrj.emp_id";

if ($agency_id!='')
{
$sql.=" WHERE hrj.agency_id='".$agency_id."'";
}

// if ($cashier_id!='')
// {
// $sql.=" and rs.cashier_id='".$cashier_id."'";
// }


$sql.=" GROUP BY cashier_id,emp_id ORDER BY agency,cashier_name";
//display_error($sql);

$res=db_query_rs($sql);


$previous_agency="";
while($row = db_fetch($res))
{
	$c ++;
	alt_table_row_color($k);
	
	if($previous_agency!=$row['agency'])
{
alt_table_row_color($k);
$colspan=$day2+3;
$agency_name=get_employee_agency_name($row['agency']);

if ($row['agency']==0){
	$agency_name='No Emloyee ID & Agency';
}

label_cell($agency_name.":",'colspan="'.$colspan.'" class=tableheader2');
end_row();
alt_table_row_color($k);
$previous_agency=$row['agency'];
} 
	
	
	label_cell($c,'align=right');
	label_cell('<b>'.$row['cashier_name'].'</b>');
	//label_cell('<b>'.$row['a_name'].'</b>');
	
	for ($a = $day1; $a <= $day2; $a++)
	{
	$row_a="a".$a."";
	if($row[$row_a] <0)
	{
	label_cell("<font color=red>(".number_format2(abs($row[$row_a]),2).")</font>",'align=right');
	}
	else
	{
	amount_cell(abs($row[$row_a]));
	}
	}
	//$over+=$row[$row_a];
	label_cell("<b>".number_format2(abs($row['t_short_over']),2)."</b>",'align=right');
	end_row();
	$t_total+=$row['t_short_over'];
}


//GRAND TOTAL



$sql="select";
for ($i = $day1; $i <= $day2; $i++)
{ 
$sql.=" sum(b".$i.")as b".$i."";
if($i<$day2)
{
$sql.=",";
}
}



$sql.=" from (select DISTINCT ";
for ($i = $day1; $i <= $day2; $i++)
{ 
$sql.=" sum(b".$i.") as b".$i."";
if($i<$day2)
{
$sql.=",";
}
}



$sql.=" from (SELECT 
rs.cashier_id as cashier_id, hl.hr_emp_id as emp_id, rs.cashier_name as 
cashier_name,";
for ($i = $day1; $i <= $day2; $i++)
{
$sql.=" sum(case when rs.r_summary_date='$year1-$month1-".$i."' and rs.over_short < 0 then over_short else 0 end) as b".$i."";
$sql.=",";
}
$sql.=" sum(rs.over_short) as t_short_over
FROM ".CR_DB.TB_PREF."remittance_summary as rs
left join ".CR_DB.TB_PREF."hris_link as hl on 
rs.cashier_id=hl.hr_cashier_id 
where rs.r_summary_date>='".$datefrom."' and rs.r_summary_date<='".$dateto."'
and rs.over_short <0  GROUP BY rs.cashier_id,hl.hr_emp_id) as a";



if ($agency_id!='')
{
$sql.=" left join 
".CR_DB.TB_PREF."hr_employee as hr on hr.employee_id=a.emp_id
LEFT JOIN ".CR_DB.TB_PREF."hr_emp_job_info as hrj 
ON hr.emp_number=hrj.emp_id";

$sql.=" WHERE hrj.agency_id='".$agency_id."'";
}

$sql.=" GROUP BY cashier_id) as b";


// if ($agency_id!='')
// {
// $sql.=" WHERE hrj.agency_id='".$agency_id."'";
// }

// if ($cashier_id!='')
// {
// $sql.=" and rs.cashier_id='".$cashier_id."'";
// }

//display_error($sql);

$res=db_query_rs($sql);

label_cell('');
label_cell('<font color=#880000><b>'.'GRAND TOTAL:'.'</b></font>');

while($row = db_fetch($res))
{

	for ($a = $day1; $a <= $day2; $a++)
	{
	$row_b="b".$a."";
	if($row[$row_b] <0)
	{
	label_cell("<font color=red>(".number_format2(abs($row[$row_b]),2).")</font>",'align=right');
	}
	else
	{
	label_cell("<font color=#880000><b>".number_format2(abs($row[$row_b]),2)."<b></font>",'align=right');
	}
	$t_amount+=$row[$row_b];
	}

}

label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');

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