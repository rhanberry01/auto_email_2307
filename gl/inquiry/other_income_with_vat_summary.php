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
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");


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
	
page(_($help_context = "Other Income with VAT"), false, false, "", $js);




function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$date_= $_POST['date_'];
	$date_t= $_POST['TransToDate'];
	
	$datefrom=$_POST['start_date'];
	$dateto=$_POST['end_date'];
		


	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Other Income with VAT'), "other_icome_with_vat", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'OTHER INCOME with VAT', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date : from '.$datefrom.' to '.$dateto, $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,13); //set column width
	$rep->sheet->setColumn(2,13,13); //set column width

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;
	
$th = array('', 'Date', 'Trans#','RF/OR/SI #', 'Payee','Purpose','Account','Amount','VAT','Wtax','Gross Amount');
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

$sql = "select oh.*,od.bd_det_gl_code from 0_other_income_payment_header as oh
LEFT JOIN 0_other_income_payment_details as od
ON oh.bd_trans_no=od.bd_det_trans_no

";
if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE oh.bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND oh.bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (oh.bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['rec_type']!= '')
{
	
		$sqlrec="select oi_gl_debit from ".TB_PREF."other_income_type where oi_id='".$_POST['rec_type']."' and inactive='0'";
		//display_error($sqlrec);
		$result_=db_query($sqlrec);
		

		$rec_row = db_fetch($result_);
		$ot_gl_debit_account=$rec_row["oi_gl_debit"];

		$sql .= " AND od.bd_det_gl_code='$ot_gl_debit_account'";
}

	$sql .= " AND (oh.bd_vat!=0 OR bd_wt!='0') AND bd_cleared='1' ORDER by oh.bd_trans_date";

	$res = db_query($sql);
//display_error($sql);


	while($row = db_fetch($res))
	{ 
	
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['bd_trans_date']), $format_center);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['bd_trans_no'], $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['bd_or'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['bd_payee'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['bd_memo'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, get_gl_account_name($row['bd_det_gl_code']), $format_left);
		$x++;
		
		// if ($row['pcd_tax_type']!='' AND $row['pcd_tax_type']!=0){
		// $rep->sheet->writeString($rep->y, $x, get_gl_account_name($row['pcd_tax_type']), $format_left);
		// $x++;
		// }
		// else{
		// $rep->sheet->writeString($rep->y, $x, '', $format_accounting);
		// $x++;
		// }
		$rep->sheet->writeNumber($rep->y, $x, $row['bd_amount'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['bd_vat'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['bd_wt'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['bd_gross_amount'], $format_accounting);
		$rep->y++;
		
		$t_amount+=$row['bd_amount'];
		$t_vat+=$row['bd_vat'];
		$t_wt+=$row['bd_wt'];
		$t_extended+=$row['bd_gross_amount'];
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
	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x,abs($t_amount), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x,abs($t_vat), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_wt), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x,abs($t_extended), $format_accounting);
	$rep->End();
}
//end of excel report------------------------------------------------------------------------------------

//====================================start heading=========================================
start_form();
if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');


global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_table("width=75% $table_style2"); // outer table
		//tax_gl_list_cells('', 'tax_type', null, true, false, "All Accounts");
		//receipt_list_cells('Receipt Released:', 'receipt_type', '', '', '',false,'');
		get_other_income_type_list_cells('Type:','rec_type','',true);
		ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
	
end_table();
div_end();

br();


//====================================display table=========================================
$sql = "select oh.*,od.bd_det_gl_code from 0_other_income_payment_header as oh
LEFT JOIN 0_other_income_payment_details as od
ON oh.bd_trans_no=od.bd_det_trans_no

";
if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE oh.bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND oh.bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (oh.bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['rec_type']!= '' and $_POST['search'])
{
	
		$sqlrec="select oi_gl_debit from ".TB_PREF."other_income_type where oi_id='".$_POST['rec_type']."' and inactive='0'";
		//display_error($sqlrec);
		$result_=db_query($sqlrec);
		

		$rec_row = db_fetch($result_);
		$ot_gl_debit_account=$rec_row["oi_gl_debit"];

		$sql .= " AND od.bd_det_gl_code='$ot_gl_debit_account'";
}

	$sql .= " AND (oh.bd_vat!=0 OR bd_wt!='0') AND bd_cleared='1' ORDER by oh.bd_trans_date";

	$res = db_query($sql);
//display_error($sql);

	
div_start('table_');
start_table($table_style2.' width=85%');
$th = array();
array_push($th,'', 'Date', 'Trans#','RF/OR/SI #', 'Payee','Purpose','Account','Amount','VAT','Wtax','Gross Amount');


if (db_num_rows($res) > 0){
	submit_center('dl_excel','Download as excel file');
	br();
	table_header($th);
}

else
{
	br();
	display_heading('No result found');
	display_footer_exit();
}

$k = 0;

while($row = db_fetch($res))
{
$c ++;
start_form();
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell(sql2date($row['bd_trans_date']));
	label_cell(get_gl_view_str(ST_BANKDEPOSIT, $row["bd_trans_no"],$row["bd_trans_no"]));
	//label_cell($row["bd_trans_no"]);
	label_cell($row['bd_or'] ,'nowrap');
	label_cell($row['bd_payee'] ,'nowrap');
	label_cell($row['bd_memo'] ,'nowrap');
	label_cell(get_gl_account_name($row['bd_det_gl_code']));
	// if ($row['pcd_tax_type']!='' AND $row['pcd_tax_type']!=0){
	// label_cell(get_gl_account_name($row['pcd_tax_type']));
	// }
	// else{
	// label_cell();
	// }
	
		amount_cell($row['bd_amount'],false);
		amount_cell($row['bd_vat'],false);
		amount_cell($row['bd_wt'],false);
		amount_cell($row['bd_gross_amount'],false);
	end_row();
end_form();

$t_amount+=$row['bd_amount'];
$t_vat+=$row['bd_vat'];
$t_wt+=$row['bd_wt'];
$t_extended+=$row['bd_gross_amount'];

}

start_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_amount),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_vat),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_wt),2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2(abs($t_extended),2)."<b></font>",'align=right');
end_row();

end_table();
div_end();
end_form();
end_page();
?>