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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Sales Total per Entries"), false, false, "", $js);



function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$date_f= $_POST['start_date'];
	$date_t= $_POST['end_date'];
	
$date_after = date2sql($_POST['start_date']);
$date_before = date2sql($_POST['end_date']);

	
	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_f,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Sales Total per Entries'), "Sales Total per Entries", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'SALES REPORT (Total per Entries)', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date From: '.sql2date($date_after).' To: '.sql2date($date_before).'', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,40);
	$rep->sheet->setColumn(2,2,20);
	$rep->sheet->setColumn(3,3,20);

	
	//setColumn(from,to,size);
	
	
$x=0;

div_start('table_');
start_table($table_style2.' width=90%');
$th = array();

$from_head = array('','Entries', 'Debit','Credit');
$th = array_merge($from_head);
array_push($th);

	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;
	
$sales_type=ST_SALESTOTAL;
//==========AMOUNT
$sql="select account, sum(amount) as t_amount from ".TB_PREF."gl_trans";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE type='$sales_type' 
			AND tran_date >= '".date2sql($_POST['start_date'])."'
			AND tran_date <= '".date2sql($_POST['end_date'])."'";
}

$sql .= " AND amount!='0' AND account!='' GROUP BY account ORDER BY amount desc";
	
//display_error($sql);	
	
$res = db_query($sql);

while($row = db_fetch($res))
{
$c ++;
$x = 0;
$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
$x++;	
$rep->sheet->writeString($rep->y, $x, get_gl_account_name($row['account']), $format_left);
$x++;

if($row['t_amount']<0) {
$rep->sheet->writeString($rep->y, $x, '', $format_left);
$x++;	
$rep->sheet->writeNumber($rep->y, $x, $row['t_amount'], $format_accounting);
$x++;
$credit+=$row['t_amount'];
}
else {
$rep->sheet->writeNumber($rep->y, $x, $row['t_amount'], $format_accounting);
$x++;
$rep->sheet->writeString($rep->y, $x, '', $format_left);
$x++;
$debit+=$row['t_amount'];
}
$rep->y++;
}


//AMOUNT============

	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold_right);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($debit), $format_accounting);
	$x++;	
	$rep->sheet->writeNumber($rep->y, $x, abs($credit), $format_accounting);
	$x++;	
					
	$rep->End();
}


$delete_id = find_submit('delete_selected');

//====================================start heading=========================================
start_form();
//if (!isset($_POST['start_date']))
	//$_POST['start_date'] = '01/01/'.date('Y');

start_table();
start_row();
		date_cells('From :', 'start_date','',null,-1);
		date_cells('To :', 'end_date');
	submit_cells('search', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table();


//====================================end of heading=========================================
br();

//====================================display table=========================================
if (isset($_POST['search'])) {

submit_center('dl_excel','Download as excel file');
br();

div_start('table_');
start_table($table_style2.' width=50%');
$th = array();

$from_head = array('Entries', 'Debit','Credit');
$th = array_merge($from_head);
array_push($th);
	 table_header($th);

$k = 0;
$sales_type=ST_SALESTOTAL;
//==========AMOUNT
$sql="select account, sum(amount) as t_amount from ".TB_PREF."gl_trans";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE type='$sales_type' 
			AND tran_date >= '".date2sql($_POST['start_date'])."'
			AND tran_date <= '".date2sql($_POST['end_date'])."'";
}

$sql .= " AND amount!='0' AND account!='' GROUP BY account ORDER BY amount desc";
	
//display_error($sql);	
	
$res = db_query($sql);

while($row = db_fetch($res))
{
start_row();
alt_table_row_color($k);
label_cell(get_gl_account_name($row['account']) ,'nowrap');
if($row['t_amount']<0) {
label_cell('' ,'nowrap');
amount_cell(abs($row['t_amount']),false);
$credit+=$row['t_amount'];
}
else {
amount_cell($row['t_amount'],false);
label_cell('' ,'nowrap');
$debit+=$row['t_amount'];
}
end_row();
}

//AMOUNT============


if ($debit!='' and $credit!='') {
start_row();
label_cell('<b><font color=#880000>TOTAL:</font></b>','align=right');
label_cell("<font color=#880000><b>".number_format2(abs($debit),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($credit),2)."<b></font>",'align=right');
end_row();
}
else {
display_error('NO RESULT FOUND.');
}

end_table();
}
div_end();
end_form();
end_page();
?>