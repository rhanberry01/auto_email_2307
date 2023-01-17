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
	
page("Cashier Total Shortage", false, false, "", $js);

//------------------------------------------------------------------------------------------------

function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$agency=$_POST['agency'];
	$date_= $_POST['date_'];
	$date_t= $_POST['TransToDate'];
	
$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
	
	//display_error($agency);
	$agency_name=get_employee_agency_name($agency);

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Agency :'), 'from' => $agency_name, 'to' => '')
						);

    $rep = new FrontReport(_('Cashier Total Shortage'), "cashier_total_shortage", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'CASHIER TOTAL SHORTAGE', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Agency: '.$agency_name, $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date From: '.sql2date($date_).' To: '.sql2date($date_t).'', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,8);
	$rep->sheet->setMerge(2,0,2,8);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,20); //set column width
	$rep->sheet->setColumn(2,2,35); //set column width
	$rep->sheet->setColumn(3,3,20); //set column width

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;
	
$th = array(' ', "Employee ID", "Cashier Name", "Total Short");
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

$sql="Select 
case when  ISNULL(hrj.agency_id) then 0 else hrj.agency_id end as agency, 
a.cashier_id as cashier_id, a.emp_id as emp_id, a.cashier_name as cashier_name,a.t_short 
from (SELECT rs.cashier_id as cashier_id, hl.hr_emp_id as emp_id, 
rs.cashier_name as cashier_name,
sum(rs.over_short) as t_short 
FROM ".CR_DB.TB_PREF."remittance_summary as rs 
left join ".CR_DB.TB_PREF."hris_link as hl 
on rs.cashier_id=hl.hr_cashier_id
where rs.r_summary_date>='".$datefrom."'
and rs.r_summary_date<='".$dateto."' and rs.over_short < 0 
GROUP BY rs.cashier_id,hl.hr_emp_id) as a
left join ".CR_DB.TB_PREF."hr_employee as hr on hr.employee_id=a.emp_id
LEFT JOIN ".CR_DB.TB_PREF."hr_emp_job_info as hrj ON hr.emp_number=hrj.emp_id";

if ($_POST['agency']!='') {
	$sql.=" WHERE hrj.agency_id='".$agency."'";
}

if ($_POST['agency']!=0) {
	$sql.=" and a.emp_id!=0";
}


$sql.=" GROUP BY cashier_id,emp_id ORDER BY cashier_name";

$res=db_query_rs($sql);
//display_error($sql);


	while($row = db_fetch($res))
	{ 
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['emp_id'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['cashier_name'],$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, abs($row['t_short']), $format_right);
		$x++;
		$rep->y++;
		
	$t_short+=$row['t_short'];
	}
	
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, 'GRAND TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_short), $format_accounting);
	
	
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
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	hr_agency_list_cells('AGENCY:','agency');
	date_cells(_("Date:"), 'date_', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);





div_start('totals');		
display_heading("Total Cashier Shortage From ".$_POST['date_']." To ".$_POST['TransToDate']."");
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();



$agency=$_POST['agency'];
$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);

//start of table display
start_table($table_style2);


$th = array(' ', "Employee ID", "Cashier Name", "Total Short");
table_header($th);


$sql="Select 
case when  ISNULL(hrj.agency_id) then 0 else hrj.agency_id end as agency, 
a.cashier_id as cashier_id, a.emp_id as emp_id, a.cashier_name as cashier_name,a.t_short 
from (SELECT rs.cashier_id as cashier_id, hl.hr_emp_id as emp_id, 
rs.cashier_name as cashier_name,
sum(rs.over_short) as t_short 
FROM ".CR_DB.TB_PREF."remittance_summary as rs 
left join ".CR_DB.TB_PREF."hris_link as hl 
on rs.cashier_id=hl.hr_cashier_id
where rs.r_summary_date>='".$datefrom."'
and rs.r_summary_date<='".$dateto."' and rs.over_short < 0 
GROUP BY rs.cashier_id,hl.hr_emp_id) as a
left join ".CR_DB.TB_PREF."hr_employee as hr on hr.employee_id=a.emp_id
LEFT JOIN ".CR_DB.TB_PREF."hr_emp_job_info as hrj ON hr.emp_number=hrj.emp_id";

if ($_POST['agency']!='') {
	
	if ($_POST['agency']==0){
		$sql.=" WHERE (hrj.agency_id='".$agency."' or hrj.agency_id='' or hrj.agency_id=NULL)  ";
	}
	else{
		$sql.=" WHERE hrj.agency_id='".$agency."'";
	}
	
}

if ($_POST['agency']!=0) {
	$sql.=" and a.emp_id!=0";
}


$sql.=" GROUP BY cashier_id,emp_id ORDER BY cashier_name";

$res=db_query_rs($sql);
//display_error($sql);

while($row = mysql_fetch_array($res))
{

$c ++;
alt_table_row_color($k);
label_cell($c,'align=right');
label_cell($row['emp_id']);
label_cell($row['cashier_name']);
amount_cell(abs($row['t_short']));
$t_short+=$row['t_short'];
}
	end_row();
	label_cell('');
	label_cell('');
	label_cell("<font color=#880000><b>"."TOTAL:"."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_short),2)."<b></font>",'align=right');
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