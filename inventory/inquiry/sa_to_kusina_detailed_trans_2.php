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
	
page(_($help_context = "SRS MALABON SA to SRS KUSINA"), false, false, "", $js);


$type = ST_SAKUSINAOUT;


function get_product_row($prod_id)
{
	$sql = "SELECT * FROM Products WHERE ProductID = $prod_id";
	$res = ms_db_query($sql);
	$prod =  mssql_fetch_array($res);
	
	return $prod;
}

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

    $rep = new FrontReport(_('SRS MALABON SA to SRS KUSINA'), "malabon_sa_to_kusina", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'SRS MALABON SA to SRS KUSINA', $format_bold);
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
	
	$type = ST_SAKUSINAOUT;
$th = array('','Posted By','Trans#','Trans Date','ProductID','Barcode','Description', 'Cost','UOM', 'QTY', 'Extended');
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;

	$c = $k = 0;

$sql.=" SELECT  th.name_out as name_out,th.date_created as date_created,th.id as id, th.aria_trans_no_out as trans_out,
(td.cost*td.qty_out) as ext,td.* FROM transfers.".TB_PREF."transfer_details as td left join 
transfers.".TB_PREF."transfer_header as th on th.id=td.transfer_id where th.aria_type_out='$type' and td.barcode='7132' ";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " AND (th.date_created>='".date2sql($_POST['start_date'])."' and th.date_created<='".date2sql($_POST['end_date'])."')";
}
//display_error($sql);
$res = db_query($sql);
//display_error($sql);

$c=1;
	while($row = db_fetch($res))
	{ 
		$prod_desc=get_product_row($row['stock_id']);
		$x = 0;
		$rep->sheet->writeNumber($rep->y, $x, $c++, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['name_out'], $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['trans_out'], $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['date_created']), $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['stock_id'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['barcode'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x,$prod_desc['Description'], $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x,$row['cost'], $format_accounting);
		$x++;
		$rep->sheet->writeString($rep->y, $x,$row['uom'], $format_center);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x,$row['qty_out'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x,$row['ext'], $format_accounting);
		$rep->y++;
		
		$t_total+=$row['ext'];
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
	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x,abs($t_total), $format_accounting);
	$rep->End();
}
//end of excel report------------------------------------------------------------------------------------

//====================================start heading=========================================
start_form();
if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

if (!isset($_POST['end_date']))
	$_POST['end_date'] = '01/01/'.date('Y');


global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_table("width=30% $table_style2"); // outer table
		//tax_gl_list_cells('', 'tax_type', null, true, false, "All Accounts");
		//receipt_list_cells('Receipt Released:', 'receipt_type', '', '', '',false,'');
		//get_other_income_type_list_cells('Type:','rec_type','',true);
		//ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
	
end_table();
div_end();

br();


//====================================display table=========================================
br(2);
display_heading('SRS MALABON SA to SRS Kusina Transfer Details');
br();


$sql.=" SELECT th.name_out as name_out,th.date_created as date_created,th.id as id, th.aria_trans_no_out as trans_out,
(td.cost*td.qty_out) as ext,td.* FROM transfers.".TB_PREF."transfer_details as td left join 
transfers.".TB_PREF."transfer_header as th on th.id=td.transfer_id where th.aria_type_out='$type' and td.barcode='7132' ";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " AND (th.date_created>='".date2sql($_POST['start_date'])."' and th.date_created<='".date2sql($_POST['end_date'])."')";
}
//display_error($sql);
$res = db_query($sql);

start_table($table_style2.' width=80%');
$th = array();
	
array_push($th,'', 'Posted By','Trans#','Trans Date','ProductID','Barcode','Description', 'Cost','UOM', 'QTY', 'Extended');


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
$c=1;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell($c++);
	label_cell($row['name_out']);
	label_cell($row['trans_out']);
	label_cell($row['date_created']);
	//label_cell(get_gl_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['stock_id']);
	label_cell($row['barcode']);
	$prod_desc=get_product_row($row['stock_id']);
	label_cell("<font size='1'>".$prod_desc['Description']."</font>");
	//label_cell($row['price']);
	//label_cell(number_format2(abs($row['price_pcs']),2));
	label_cell($row['cost']);
	label_cell($row['uom']);
	qty_cell($row['qty_out'],false,4);
	label_cell(number_format2($row['ext'],2));
	//$user=get_user($row['person_id']);
	//label_cell($user['real_name']);
	//label_cell(get_comments_string($type, $row['trans_no']));
	end_row();
	
	$t_total+=$row['ext'];
}
start_row();
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2(abs($t_total),2)."<b></font>",'align=right');
end_row();
end_table();
div_end();
end_form();
end_page();
?>