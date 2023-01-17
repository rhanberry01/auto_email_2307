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
	
page(_($help_context = "URC Summary"), false, false, "", $js);



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

    $rep = new FrontReport(_('URC Summary'), "urc_summary", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'URC Summary', $format_bold);
	$rep->y ++;

	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,13); //set column width
	$rep->sheet->setColumn(1,1,13); //set column width
	$rep->sheet->setColumn(2,13,13); //set column width

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;
	
$from_head = array('Branch','Date', 'Supplier', 'Trans#','Receiving#','REF','CV#', 'Delivery Date','Delivery Batch#','PO REF#','PO','CWO DELIVERY', 'DM', 'CV Amount');
$th = array_merge($from_head);
array_push($th);
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

	
	$sql="SELECT *, round(ov_amount + ov_gst  - ov_discount - ewt,2) AS TotalAmount  FROM srs_aria_nova.0_urc_summary where tran_date>='2015-01-01'
and tran_date<='2017-11-31'";

	$res = db_query($sql);
	//display_error($sql);
	

	while($row = db_fetch($res))
	{ 

		//$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeString($rep->y, $x, $row['branch_name'], $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['tran_date']), $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['supp_name'], $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['trans_no'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['reference'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['supp_reference'], $format_left);
		$x++;				
		
			if($row['type']==24){
				
					//get cwo delivery cv
					$sql_cv="SELECT cv_no FROM srs_aria_nova.0_urc_summary where tran_date>='2015-01-01'
					and tran_date<='2017-11-31' and special_reference='".$row['special_reference']."' and type=20";
					$res_cv = db_query($sql_cv);
					$row_cv = db_fetch($res_cv);
			
					$rep->sheet->writeString($rep->y, $x, $row_cv['cv_no'], $format_accounting);
					$x++;
			}
			else{
									
					$rep->sheet->writeString($rep->y, $x, $row['cv_no'], $format_left);
					$x++;	
			}


		
		$rep->sheet->writeString($rep->y, $x, sql2date($row['del_date']), $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['id'], $format_left);
		$x++;			
		$rep->sheet->writeString($rep->y, $x, $row['special_reference'], $format_left);
		$x++;			
		
		
		if($row['type']==20){
		$rep->sheet->writeNumber($rep->y, $x, $row['TotalAmount'], $format_accounting);
		$x++;
		$apv+=$row['TotalAmount'];
		}
		else{
		$rep->sheet->writeNumber($rep->y, $x, 0, $format_accounting);
		$x++;
		}
		
		
		if($row['type']==24){
		$rep->sheet->writeNumber($rep->y, $x, $row['TotalAmount'], $format_accounting);
		$x++;
		$del+=$row['TotalAmount'];
		}
		else{
		$rep->sheet->writeNumber($rep->y, $x, 0, $format_accounting);
		$x++;
		}
		
		if($row['type']==53){
		$rep->sheet->writeNumber($rep->y, $x, $row['TotalAmount'], $format_accounting);
		$x++;
		$dm+=$row['TotalAmount'];
		}
		else{
		$rep->sheet->writeNumber($rep->y, $x, 0, $format_accounting);
		$x++;
		}
		
		$rep->sheet->writeNumber($rep->y, $x, $row['cv_amount'], $format_accounting);
		$x++;
		$cv+=$row['cv_amount'];
		
		
		$rep->y++;
	
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
	$rep->sheet->writeNumber($rep->y, $x,abs($apv), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x,abs($del), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x,abs($dm), $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x,abs($cv), $format_accounting);
	$x++;
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
// start_table();
		// supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true);
		// date_cells('From :', 'start_date');
		// date_cells('To :', 'end_date');
		// submit_cells('search', 'Search');
// end_table();
// //====================================end of heading=========================================
// br();

//====================================display table=========================================

//submit_center('dl_excel','Do not Click Me');
submit_center('dl_excel','Download as excel file');
br();

div_start('table_');

div_end();
end_form();
end_page();
?>