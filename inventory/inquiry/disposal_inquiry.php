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

$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");


function get_bo_date($m_no)
{
	$m_no=$m_no+0;
	
	$sql = "SELECT bo_processed_date FROM returned_merchandise.".TB_PREF."rms_header WHERE movement_no='$m_no' and movement_type='FDFB'";
	
	$res = db_query_rs($sql);
	$row = db_fetch($res);
	return $row[0];
}

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
	
page("Disposal Inquiry", false, false, "", $js);

//------------------------------------------------------------------------------------------------

function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$date_= $_POST['start_date'];
	$date_t= $_POST['end_date'];
	
$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
	

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Disposal Summary'), "disposal_summary", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'DISPOSAL FROM BO SUMMARY', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date : '.$date_ ." To: ".$date_t, $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Status : POSTED ', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,10); //set column width
	$rep->sheet->setColumn(2,3,13); //set column width
	$rep->sheet->setColumn(4,4,38); //set column width
	$rep->sheet->setColumn(5,5,13); 
	$rep->sheet->setColumn(6,6,38); 
	$rep->sheet->setColumn(7,7,6); 
	$rep->sheet->setColumn(8,10,12); 
	

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;

	$th = array();

	array_push($th, '','BO Date','Date Posted', 'Transaction#','Category','Vendor','Product Code','Description','UOM', 'QTY', 'Price', 'Extended');

	foreach($th as $header)
	{
	$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
	$x++;
	}
	$rep->y++;
	$c = $k = 0;

	$sql = "SELECT m.[MovementID]
	,m.[MovementNo]
	,m.[MovementCode]
	,m.[DateCreated]
	,m.[Status]
	,m.[PostedBy]
	,CAST(m.[PostedDate] AS DATE ) as PostedDate
	,m.[TransactionDate]
	,m.[NetTotal]
	,m.[TotalQty]
	,ml.[barcode]
	,ml.[ProductID]
	,ml.[ProductCode]
	,ml.[Description] as Description
	,ml.[UOM]
	,ml.[unitcost]
	,ml.[qty]
	,ml.[extended]
	,p.[vendorcode]
	,p.[FieldACode]
	,p.[FieldBCode]
	,p.[FieldCCode]
	,c.[Description] as category
	,v.[description] as vendor
	FROM [Movements] as m
	LEFT JOIN [MovementLine] as ml
	on m.MovementID=ml.MovementID
	LEFT JOIN [Products] as p
	on ml.ProductID=p.ProductID
	LEFT JOIN [FieldA] as c
	on p.FieldACode=c.FieldACode
	LEFT JOIN [vendor] as v
	on p.vendorcode=v.vendorcode
	where m.MovementCode='FDFB'";
		
	if ($_POST['start_date'])
	{
		$sql .= " and (CAST(m.PostedDate AS DATE )>='".date2sql($_POST['start_date'])."' 
		and CAST(m.PostedDate AS DATE )<='".date2sql($_POST['end_date'])."')";	
	}

	if ($_POST['trans_no'])
	{
	$sql .= " AND m.MovementNo='".$_POST['trans_no']."'";	
	}


	if ($_POST['status_type']==1)
	{
	//Open
	$stats='1' ;
	}
	else {
	//Posted
	$stats='2';
	}


	if ($_POST['supplier_code'] != ''){
	$sql .= " AND v.vendorcode=".db_escape($_POST['supplier_code'])."";
	}


	if ($_POST['status_type']!='')
	{
	$sql .= " AND Status= '$stats'";	
	}

	if ($_POST['category']!='')
	{
	$sql .= " AND p.FieldACode=".db_escape($_POST['category'])."";	
	}

	$sql .= " ORDER BY MovementNo,Description";	

	//display_error($sql);

	$res = ms_db_query($sql);

	while($row = mssql_fetch_array($res))
	{ 
		$c ++;
		$x = 0;
		
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date(get_bo_date($row['MovementNo'])),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['PostedDate']),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['MovementNo'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['category'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['vendor'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['barcode'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['Description'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['UOM'],$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['qty'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['unitcost'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['extended'], $format_accounting);
		$x++;
		$net_total+=$row['extended'];
		$rep->y++;
	}
	
		$x=0;
		//$rep->y++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, '', $format_bold);
		$x++;
		$rep->sheet->writeString($rep->y, $x, 'NET TOTAL:', $format_bold_right);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $net_total, $format_accounting);
		$rep->End();
}
//end of excel report------------------------------------------------------------------------------------

start_form();
div_start('header');

$type = ST_INVADJUST;

// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('Transaction#:', 'trans_no');
				text_cells('Barcode:','barcode','',9);
				yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
				get_ms_products_category_list_cells('','category',null,true);
		supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
		//yesno_list_cells(_("Status Type:"), 'movement_type', '',_("Deliver to Branch"), _("Received from Branch"));
		//adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();
	
$sql = "SELECT m.[MovementID]
,m.[MovementNo]
,m.[MovementCode]
,m.[DateCreated]
,m.[Status]
,m.[PostedBy]
,CAST(m.[PostedDate] AS DATE ) as PostedDate
,m.[TransactionDate]
,m.[NetTotal]
,m.[TotalQty]
,ml.[barcode]
,ml.[ProductID]
,ml.[ProductCode]
,ml.[Description] as Description
,ml.[UOM]
,ml.[unitcost]
,ml.[qty]
,ml.[extended]
,p.[vendorcode]
,p.[FieldACode]
,p.[FieldBCode]
,p.[FieldCCode]
,c.[Description] as category
,v.[description] as vendor
FROM [Movements] as m
LEFT JOIN [MovementLine] as ml
on m.MovementID=ml.MovementID
LEFT JOIN [Products] as p
on ml.ProductID=p.ProductID
LEFT JOIN [FieldA] as c
on p.FieldACode=c.FieldACode
LEFT JOIN [vendor] as v
on p.vendorcode=v.vendorcode
where m.MovementCode='FDFB'";
	
if ($_POST['start_date'])
{
	$sql .= " and (CAST(m.PostedDate AS DATE )>='".date2sql($_POST['start_date'])."' 
	and CAST(m.PostedDate AS DATE )<='".date2sql($_POST['end_date'])."')";	
}

if ($_POST['trans_no'])
{
$sql .= " AND m.MovementNo='".$_POST['trans_no']."'";	
}


if ($_POST['status_type']==1)
{
//Open
$stats='1' ;
}
else {
//Posted
$stats='2';
}


if ($_POST['supplier_code'] != ''){
$sql .= " AND v.vendorcode=".db_escape($_POST['supplier_code'])."";
}


if ($_POST['status_type']!='')
{
$sql .= " AND Status= '$stats'";	
}

if ($_POST['category']!='')
{
$sql .= " AND p.FieldACode=".db_escape($_POST['category'])."";	
}

if ($_POST['barcode']!='')
{
$sql .= " AND ml.barcode=".db_escape($_POST['barcode'])."";	
}

$sql .= " ORDER BY MovementNo,Description";	

//display_error($sql);

$res = ms_db_query($sql);
start_table($table_style2.' width=90%');
$th = array();
	
array_push($th, 'BO Date', 'Date Posted', 'Transaction#','Category','Vendor','Product Code','Description','UOM', 'QTY', 'Price', 'Extended');
// $count=mssql_num_rows($res) ;
 // display_error($count);

if (mssql_num_rows($res) > 0){
	submit_center('dl_excel','Download as excel file');
	br();
	table_header($th);
	display_heading("Disposal Summary From ".$_POST['start_date']." To ".$_POST['end_date']);
	br();
}

else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$k = 0;
while($row = mssql_fetch_array($res))
{
	alt_table_row_color($k);
	label_cell(sql2date(get_bo_date($row['MovementNo'])));
	label_cell(sql2date($row['PostedDate']));
	label_cell($row['MovementNo']);
	label_cell($row['category']);
	label_cell($row['vendor']);
	label_cell($row['barcode']);
	label_cell($row['Description']);
	label_cell($row['UOM']);
	label_cell($row['qty']);
	label_cell(number_format2(abs($row['unitcost']),3));
	label_cell(number_format2(abs($row['extended']),2));
	end_row();
	
	$net_total+=$row['extended'];
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
label_cell('<font color=#880000><b>'.'NET AMOUNT:'.'</b></font>');
label_cell("<font color=#880000><b>".number_format2(abs($net_total),2)."<b></font>",'align=left');
end_row();

end_table();
br();
br();
div_end();
end_form();
end_page();

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>