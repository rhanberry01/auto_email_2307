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
	
page("Other Income Summary", false, false, "", $js);

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

    $rep = new FrontReport(_('Other Income Summary'), "Other_Income_Summary", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'OTHER INCOME SUMMARY', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date : from '.$date_.' to '.$date_t, $format_bold);
	$rep->y ++;
	$rep->y ++;
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,13,13); //set column width

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;
	
$th = array(' ', _("TranDate"), _("Customer"),'GL Account', 'Description', 'Amount', 'Cash', 'Cash Date Deposited', 'Check','CWTax','Bank','Branch','Check#','Check Date Deposited');
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

$mysql_q="select distinct gl.type as gl_type,gl.type_no as gl_type_no,gl.tran_date as gl_tran_date,gl.account as gl_account,gl.memo_ as gl_memo,
gl.amount as gl_amount,dt.ov_gst as dt_tax,cpd.amount as cpd_amount,cpd.payment_type as cpd_payment_type,cpd.bank as cpd_bank,cpd.branch as cpd_branch,
cpd.chk_number as cpd_chk_number,cpd.deposit_date as cpd_deposit_date, cpd.debtor_no as cpd_debtor_no, dm.debtor_ref as debtor_name
from ".TB_PREF."gl_trans as gl
left join ".TB_PREF."debtor_trans as dt
on gl.type_no=dt.trans_no
left join ".TB_PREF."cust_payment_details as cpd
on gl.type_no=cpd.trans_no
left join 0_debtors_master as dm 
on cpd.debtor_no=dm.debtor_no
where (gl.tran_date>='".$datefrom."' and gl.tran_date<='".$dateto."') and (dt.tran_date>='".$datefrom."' and dt.tran_date<='".$dateto."') and gl.amount<0 and gl.type='63'
and dt.type='12' and cpd.deposited='1'";

if ($_POST['account']!='')
{
$acc=$_POST['account'];
$mysql_q.=" and gl.account='".$acc."'";
}

$mysql_q.=" order by gl.tran_date,gl.type_no, cpd_payment_type asc";
$res2=db_query($mysql_q);

while($row = db_fetch($res2))
{ 
if ($row['cpd_payment_type']=='Cash')
{
$cash=$row['cpd_amount'];
$cash_date_deposited=$row['cpd_deposit_date'];
}

if ($row['cpd_payment_type']=='Check')
{
$check=$row['cpd_amount'];
$check_date_deposited=$row['cpd_deposit_date'];
}
if ($row['cpd_chk_number']>0)
{
		$c ++;
		$x = 0; 
		
		
		// if($previous_gl!=$row['gl_account'])
		// {
			// $x = 0; 

		// ////$y_amount+=$row['gl_amount'];
		// $rep->sheet->writeString($rep->y, $x, '',$format_left);
		// $x++;
		// $rep->sheet->writeString($rep->y, $x, $row['gl_tran_date']." : ".get_gl_account_name($row["gl_account"]),$format_left);
		// $x++;
		// end_row();
		// $previous_gl =$row['gl_account'];
		// $previous_date=$row['gl_tran_date'];
		// // //$y_amount=0;
		// } 
		
		// $rep->y++;
		$x = 0; 
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['gl_tran_date'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['debtor_name'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, get_gl_account_name($row["gl_account"]),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['gl_memo'],$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, abs($row['gl_amount']), $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $cash, $format_accounting);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $cash_date_deposited, $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $check, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['dt_tax'], $format_accounting);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['cpd_bank'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['cpd_branch'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['cpd_chk_number'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $check_date_deposited,$format_left);

	$rep->y++;
	$t_amount+=$row['gl_amount'];
	$t_cash+=$cash;
	$t_check+=$check;
	$t_tax+=$row['dt_tax'];
	}
	}
	
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, 'GRAND TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, $row['gl_tran_date'],$format_left);
	$x++;
	$rep->sheet->writeString($rep->y, $x, $row['gl_tran_date'],$format_left);
	$x++;
	$rep->sheet->writeString($rep->y, $x, $row['gl_tran_date'],$format_left);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_amount), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_cash, $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '',$format_left);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_check, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_tax, $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold);
	$x++;
	
	$rep->End();
}
//end of excel report------------------------------------------------------------------------------------


start_form();

start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	gl_all_accounts_list_cells(_("Account:"), 'account', null, false, false, "All Accounts");
	date_cells(_("Date:"), 'date_', '', null, -1);
	date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);

div_start('totals');		
display_heading("Other Income Summary From ".$_POST['date_']." To ".$_POST['TransToDate']."");
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();


$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
$acc=$_POST['account'];
//start of table display
start_table($table_style2);

$th = array(' ', _("TranDate"), _("Customer"), 'GL Account', 'Description', 'Amount', 'Cash', 'Cash Date Deposited', 'Check', 'CWTax', 'Bank', 'Branch', 'Check#', 'Check Date Deposited');
table_header($th);

$mysql_q="select distinct gl.type as gl_type,gl.type_no as gl_type_no,gl.tran_date as gl_tran_date,gl.account as gl_account,gl.memo_ as gl_memo,
gl.amount as gl_amount,dt.ov_gst as dt_tax,cpd.amount as cpd_amount,cpd.payment_type as cpd_payment_type,cpd.bank as cpd_bank,cpd.branch as cpd_branch,
cpd.chk_number as cpd_chk_number,cpd.deposit_date as cpd_deposit_date, cpd.debtor_no as cpd_debtor_no, dm.debtor_ref as debtor_name
from ".TB_PREF."gl_trans as gl
left join ".TB_PREF."debtor_trans as dt
on gl.type_no=dt.trans_no
left join ".TB_PREF."cust_payment_details as cpd
on gl.type_no=cpd.trans_no
left join 0_debtors_master as dm 
on cpd.debtor_no=dm.debtor_no
where (gl.tran_date>='".$datefrom."' and gl.tran_date<='".$dateto."') and (dt.tran_date>='".$datefrom."' and dt.tran_date<='".$dateto."') and gl.amount<0 and gl.type='63'
and dt.type='12' and cpd.deposited='1'";

if ($_POST['account']!='')
{
$acc=$_POST['account'];
$mysql_q.=" and gl.account='".$acc."'";
}

$mysql_q.=" order by gl.tran_date,gl.type_no, cpd_payment_type asc";


//display_error($mysql_q);
$res2=db_query($mysql_q);
$previous_gl = "";
$previous_date = "";
while($row = mysql_fetch_array($res2))
{
if ($row['cpd_payment_type']=='Cash')
{
$cash=$row['cpd_amount'];
$cash_date_deposited=$row['cpd_deposit_date'];
}

if ($row['cpd_payment_type']=='Check')
{
$check=$row['cpd_amount'];
$check_date_deposited=$row['cpd_deposit_date'];
}

if ($row['cpd_chk_number']>0)
{
$c ++;

if($previous_gl!=$row['gl_account'])
{
alt_table_row_color($k);
////$y_amount+=$row['gl_amount'];
label_cell('');
label_cell($row['gl_tran_date']." : ".get_gl_account_name($row["gl_account"]),'colspan=14 class=tableheader2');
end_row();
alt_table_row_color($k);
 $previous_gl =$row['gl_account'];
 $previous_date=$row['gl_tran_date'];
// //$y_amount=0;
 } 

alt_table_row_color($k);
label_cell($c,'align=right');
label_cell($row['gl_tran_date']);
label_cell($row['debtor_name']);
label_cell(get_gl_account_name($row["gl_account"]),'nowrap');
label_cell($row['gl_memo']);
amount_cell(abs($row['gl_amount']));
amount_cell($cash);
label_cell($cash_date_deposited);
amount_cell($check);
amount_cell($row['dt_tax']);
label_cell($row['cpd_bank']);
label_cell($row['cpd_branch']);
label_cell($row['cpd_chk_number']);
label_cell($check_date_deposited);

	$t_amount+=$row['gl_amount'];
	$t_cash+=$cash;
	$t_check+=$check;
	$t_tax+=$row['dt_tax'];
}
}

	end_row();
	label_cell('');
	label_cell("<font color=#880000><b>"."GRAND TOTAL:"."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_cash),2)."<b></font>",'align=right');
	label_cell('');
	label_cell("<font color=#880000><b>".number_format2(abs($t_check),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_tax),2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
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