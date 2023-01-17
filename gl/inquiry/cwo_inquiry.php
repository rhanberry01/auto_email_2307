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
	
page(_($help_context = "CWO Inquiry"), false, false, "", $js);



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

    $rep = new FrontReport(_('CWO Summary'), "cwo_summary", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'CWO Summary', $format_bold);
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
	
$from_head = array('','Date', 'Supplier', 'Trans#','PO#','PR#','CV#', 'Delivery Date','Delivery Batch#','Receiving#','Amount Paid','Amount Delivered');
$th = array_merge($from_head);
array_push($th);
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

	$sql="SELECT cwo.*, po.*, grn.*, cv.amount as cv_amount, sum(gi.extended) as t_extended FROM ".TB_PREF."cwo_header as cwo
	LEFT JOIN ".TB_PREF."purch_orders as po
	on cwo.c_po_no=po.reference
	LEFT JOIN ".TB_PREF."grn_batch as grn
	on grn.purch_order_no=po.order_no
	LEFT JOIN 0_grn_items as gi
	ON grn.id=gi.grn_batch_id
	LEFT JOIN 0_cv_header as cv 
	ON cwo.c_cv_id=cv.id
	";

	$sql.=" WHERE cwo.c_date>='".date2sql($_POST['start_date'])."' AND cwo.c_date<='".date2sql($_POST['end_date'])."'";

	if ($_POST['supplier_id']!=''){
	$sql.=" AND  cwo.c_sup_id= '".$_POST['supplier_id']."'";
	}

	$sql.=" GROUP BY grn.purch_order_no ORDER BY cwo.c_date,cwo.c_po_no";

	$res = db_query($sql);
	//display_error($sql);
	

	while($row = db_fetch($res))
	{ 




		$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['c_date']), $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, get_supplier_name($row['c_sup_id']), $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['c_supp_trans_no'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['c_po_no'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['c_pr_no'], $format_left);
		$x++;				
		
		if (!IS_NULL($row['c_cv_id'])){
		$cv_num=get_cv_no($row['c_cv_id']);
		}
		
		if ($row['cv_amount']==0 or $row['cv_amount']=='' or IS_NULL($row['cv_amount'])){
		$rep->sheet->writeString($rep->y, $x, $cv_num." VOIDED", $format_left);
		$x++;	
		}
		else{
		$rep->sheet->writeString($rep->y, $x, $cv_num, $format_left);
		$x++;	
		}
		
		$rep->sheet->writeString($rep->y, $x, sql2date($row['delivery_date']), $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['id'], $format_left);
		$x++;			
		$rep->sheet->writeString($rep->y, $x, $row['reference'], $format_left);
		$x++;			
		$rep->sheet->writeNumber($rep->y, $x, $row['amount'], $format_accounting);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x, $row['t_extended'], $format_accounting);
		$rep->y++;
		

	
		$t_amount_paid+=$row['amount'];
		$t_amount_delivered+=$row['t_extended'];
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
	$rep->sheet->writeNumber($rep->y, $x,abs($t_amount_paid), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($t_amount_delivered), $format_accounting);
	$rep->End();
}


function cv_no_link($cv_id,$cv_no)
{
	// return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		// pager_link(_("Purchase Return"),
			// "/purchasing/supplier_credit.php?New=1&invoice_no=".
			// $row['trans_no'], ICON_CREDIT)
			// : '';
	global $path_to_root;
	return "<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?
				cv_id=".$cv_id."'onclick=\"javascript:openWindow(this.href,this.target); return false;\"><b>" .
				$cv_no. "&nbsp;</b></a> ";
}

$delete_id = find_submit('delete_selected');

//====================================start heading=========================================
start_form();
//if (!isset($_POST['start_date']))
	//$_POST['start_date'] = '01/01/'.date('Y');
start_table();
		supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
end_table();
//====================================end of heading=========================================
br();

//====================================display table=========================================
if (isset($_POST['search'])) {

//submit_center('dl_excel','Do not Click Me');
submit_center('dl_excel','Download as excel file');
br();

div_start('table_');
display_heading("CWO summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);	
br();
start_table($table_style2.' width=85%');
$th = array();

$from_head = array('','Date', 'Supplier', 'Trans#','PO#','PR#','CV#', 'Delivery Date','Delivery Batch#','Receiving#','Amount Paid','Amount Delivered','GL');
$th = array_merge($from_head);
array_push($th);
	 table_header($th);

$k = 0;

//==========AMOUNT

$sql="SELECT cwo.*, po.*, grn.*, cv.amount as cv_amount, sum(gi.extended) as t_extended FROM ".TB_PREF."cwo_header as cwo
LEFT JOIN ".TB_PREF."purch_orders as po
on cwo.c_po_no=po.reference
LEFT JOIN ".TB_PREF."grn_batch as grn
on grn.purch_order_no=po.order_no
LEFT JOIN 0_grn_items as gi
ON grn.id=gi.grn_batch_id
LEFT JOIN 0_cv_header as cv 
ON cwo.c_cv_id=cv.id
";

$sql.=" WHERE cwo.c_date>='".date2sql($_POST['start_date'])."' AND cwo.c_date<='".date2sql($_POST['end_date'])."'";

if ($_POST['supplier_id']!=''){
	$sql.=" AND  cwo.c_sup_id= '".$_POST['supplier_id']."'";
}

$sql.=" GROUP BY grn.purch_order_no ORDER BY cwo.c_date,cwo.c_po_no";
	
$res = db_query($sql);

while($row = db_fetch($res))
{
$c ++;
start_row();
alt_table_row_color($k);
label_cell($c,'align=right');
label_cell(sql2date($row['c_date']),'nowrap');
label_cell(get_supplier_name($row['c_sup_id']) ,'nowrap');
label_cell(get_trans_view_str(20,$row['c_supp_trans_no'],$row['c_supp_trans_no']));
label_cell(($row['c_po_no']),'nowrap');
label_cell(($row['c_pr_no']),'nowrap');

if (!IS_NULL($row['c_cv_id'])){
	$cv_num=get_cv_no($row['c_cv_id']);
}

if ($row['cv_amount']==0 or $row['cv_amount']=='' or IS_NULL($row['cv_amount'])){
label_cell(cv_no_link($row['c_cv_id'],$cv_num)." VOIDED");
}
else{
label_cell(cv_no_link($row['c_cv_id'],$cv_num));
}
label_cell(sql2date($row['delivery_date']),'nowrap');
label_cell(($row['id']),'nowrap');
label_cell(($row['reference']),'nowrap');
amount_cell($row['amount'],false);
amount_cell($row['t_extended'],false);
label_cell(get_gl_view_str(20,$row['c_supp_trans_no']));
end_row();
$t_amount_paid+=$row['amount'];
$t_amount_delivered+=$row['t_extended'];
}

//AMOUNT============

start_row();
label_cell();
label_cell();
label_cell();
label_cell();
label_cell();
label_cell();
label_cell();
label_cell();
label_cell();
label_cell('<b><font color=#880000>TOTAL:</font></b>','align=right');
label_cell("<font color=#880000><b>".number_format2(abs($t_amount_paid),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($t_amount_delivered),2)."<b></font>",'align=right');
label_cell();
end_row();

// else {
// display_error('NO RESULT FOUND.');
// }

end_table();
}
div_end();
end_form();
end_page();
?>