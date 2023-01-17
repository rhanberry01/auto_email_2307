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
	
page("Consignment Summary", false, false, "", $js);

//------------------------------------------------------------------------------------------------
function count_imported_items($cons_id) {
	$sql="SELECT id FROM 0_cons_sales_details where cons_det_id='".$cons_id."'";
	$res=db_query($sql);
	$count=db_num_rows($res);
	return $count;
}

function month_select_box( $field_name = 'month',$selected) {
$month_options = '';

//display_error($selected);
	for( $i = 1; $i <= 12; $i++ ) {
	$month_num = str_pad( $i, 2, 0, STR_PAD_LEFT );
	$month_name = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0, 0 ) );

	if($month_num==$selected) {
	$x='selected';
	}
	else {
	$x='';
	}

	//display_error($month_num);

	$month_options .= '<option value="' .  $month_num  . '" '.$x.'>' . $month_name . '</option>';
	}
return '<select name="' . $field_name . '">' . $month_options . '</select>';
}


function month_list_cells($label, $name, $selected_id, $all_option=false)
{
	if ($label != null)
		echo "<td>$label</td><td>\n";
		echo month_select_box($name, $selected_id, $all_option);
		echo "</td>\n";
}

function yearDropdownMenu($name, $start_year, $end_year = null, $id='year_select', $selected=null) {
 
        // curret year as end year
		$end_year = is_null($end_year) ? date('Y') : $end_year;
		
		// the current year
		//uncomment to reset date
      //  $selected = is_null($selected) ? date('Y') : $selected;
 
        // range of years 
        $r = range($start_year, $end_year);
 
        //create the HTML select
        $select = '<select name="'.$name.'" id="'.$id.'">';
        foreach( $r as $year )
        {
            $select .= "<option value=\"$year\"";
            $select .= ($year==$selected) ? ' selected="selected"' : '';
            $select .= ">$year</option>\n";
        }
        $select .= '</select>';
        return $select;
    }

	
// function year_list_cells($label,$name, $start_year, $end_year, $id='year_select', $selected)
// {
		// if ($label != null)
		// echo "<td>$label</td><td>\n";
		// echo yearDropdownMenu($name,$start_year, $end_year = null, $id='year_select', $selected=null);
		// echo "</td>\n";
// }

function year_list_cells($label,$name, $start_year, $end_year, $id='year_select', $selected)
{
		if ($label != null)
		echo "<td>$label</td><td>\n";
		echo yearDropdownMenu($name,$start_year, $end_year = null, $id='year_select', $selected=null);
		echo "</td>\n";
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


function get_vendor_details($vendorcode)
{
	$sql = "SELECT * FROM vendor
			WHERE vendorcode = '$vendorcode'";
	$res =  ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}

function get_vendor_commission($vendorcode)
{
	$sql = "SELECT reordermultiplier FROM vendor
			WHERE vendorcode = '$vendorcode'";
	$res =  ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}

function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
$d_month=$_POST['d_month'];
$d_year=$_POST['d_year'];
$start_date=__date($_POST['d_year'],$_POST['d_month'],1);
$end_date=end_month($start_date);
	

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Consignment Summary'), "consignment_summary", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'CONSIGNMENT SUMMARY', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date : from '.$start_date.' to '.$end_date, $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,4);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,14); //set column width
	$rep->sheet->setColumn(2,13,14); //set column width

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;
	
	$th = array(' ','Ref#','VendorCode', 'Description','Com.(%)', _("Quantity"), 'CostOfSales',_("Sales"),'E-mail Address','Purchaser','Invoice#','CV#','CV Date');
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

		$sql="select cs.*,cv.cv_date from ".TB_PREF."cons_sales_header as cs 
		LEFT JOIN ".TB_PREF."cv_header as cv
		on cs.cv_id=cv.id where start_date='".date2sql($start_date)."' and end_date='".date2sql($end_date)."'";

		if ($_POST['supp_id']!='') {
		$sql.=" and supp_code='".$_POST['supp_id']."'";
		}

			if ($_POST['type']==1) { 
			$sql.=" and cv_id!='0'";
			}
			else	if ($_POST['type']==2){
			$sql.=" and cv_id='0'";
			}
			else{
			$sql.=" order by supp_name";
			}

		$res = db_query($sql);	
		
		
	while($row = db_fetch($res))
	{ 

		$subt_commision=$row['t_sales']*($row['t_commission']/100);
		$cv_num=get_cv_no($row['cv_id']);
					
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['cons_sales_id'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['supp_code'],$format_left);
		$x++;		
		$rep->sheet->writeString($rep->y, $x, $row['supp_name'],$format_left);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x, $row['t_commission'],$format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_qty'], $format_accounting);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x,$row['t_sales']-$subt_commision, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['t_sales'], $format_accounting);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['supp_email'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['purchaser_name'], $format_left);
		$x++;		
		$rep->sheet->writeString($rep->y, $x, $row['invoice_num'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $cv_num, $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x,  sql2date($row['cv_date']), $format_left);
		$rep->y++;

		$t_qty+=$row['t_qty'];
		$t_sales+=$row['t_sales'];
		$t_cos+=$row['t_sales']-$subt_commision;
	}
	
	
	
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;
	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_qty, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_cos, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_sales, $format_accounting);
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
	$rep->End();
}
//end of excel report------------------------------------------------------------------------------------




start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	supplier_list_ms_cells('Consignor: ', 'supp_id', null, true);
	
		$items2 = array();
		$items2['0'] = 'All';
		$items2['1'] = 'With CV';
		$items2['2'] = 'Without CV';

		label_cells('Status:',array_selector('type', null, $items2, array() ));
		
	//date_cells(_("Date:"), 'date_', '', null, -1);
	//date_cells(_(" To:"), 'TransToDate', '', null);
	month_list_cells('Month:','d_month',$_POST['d_month']);
	//year_list_cells('Year:','d_year',1990); //uncomment to reset year selection
	year_list_cells('Year:','d_year',2018);
		
	date_cells('CV Date from :', 'cv_date_from');
	date_cells('to :', 'cv_date_to');
	
	
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'),false,ICON_VIEW);
	//submit_cells('generate', _("Generate"),'',_('Generate Data'),false,ICON_DOWN);
end_row();
end_table(1);


$d_month=$_POST['d_month'];
$d_year=$_POST['d_year'];
$start_date=__date($_POST['d_year'],$_POST['d_month'],1);
$end_date=end_month($start_date);

//display_error($start_date);
//display_error($end_date);

$sql="select cs.*,cv.cv_date from ".TB_PREF."cons_sales_header as cs 
LEFT JOIN ".TB_PREF."cv_header as cv
on cs.cv_id=cv.id where start_date='".date2sql($start_date)."' and end_date='".date2sql($end_date)."'";

if ($_POST['supp_id']!='') {
$sql.=" and supp_code='".$_POST['supp_id']."'";
}

if ($_POST['cv_date_from']!='' and ($_POST['type']==1) ) {
$sql.=" and cv_date>='".date2sql($_POST['cv_date_from'])."' ";
}

if ($_POST['cv_date_to']!='' and ($_POST['type']==1) ) {
$sql.=" and cv_date<='".date2sql($_POST['cv_date_to'])."' ";
}




	if ($_POST['type']==1) { 
	$sql.=" and cv_id!='0'";
	}
	else	if ($_POST['type']==2){
	$sql.=" and cv_id='0'";
	}
	else{
	$sql.=" order by supp_name";
	}

$res = db_query($sql);	
//display_error($sql);

if (isset($_POST['RefreshInquiry'])){
	
display_heading("Consignment Summary from ".$start_date." to ".$end_date."");
br();
//echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();

div_start('table_');
//start of table display
start_table($table_style2 .'width=100%');
$th = array(' ','Ref#','VendorCode', 'Description','Com.(%)', _("Quantity"), 'CostOfSales',_("Sales"),'E-mail Address','Purchaser','Invoice#','CV#','CV Date');
table_header($th);

while($row = db_fetch($res))
{
	
	$subt_commision=$row['t_sales']*($row['t_commission']/100);
	$c ++;
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell('<b>'."CS".$row['cons_sales_id'].'</b>');
	label_cell($row['supp_code']);
	label_cell($row['supp_name']);
	label_cell($row['t_commission']);
	qty_cell($row['t_qty']);
	amount_cell($row['t_sales']-$subt_commision);
	amount_cell($row['t_sales']);
	
	//$sup_id=get_supplier_id_by_supp_ref($row['supp_code']);
	//$supp_row = get_supplier($sup_id);
	label_cell('<font color=red>'.$row['supp_email'].'</font>',"align=center");
	
	label_cell($row['purchaser_name']);
		
	// $count=count_imported_items($row['cons_sales_id']);
	// label_cell($count);
	
	if($row['invoice_num']=='') {
	label_cell('');
	}
	else {
	label_cell('<font color=red>'.$row['invoice_num'].'</font>',"align=center");
	}
	
	if($row['invoice_num']=='') {
	label_cell('');
	}
	else {
	$cv_num=get_cv_no($row['cv_id']);
	label_cell(cv_no_link($row['cv_id'],$cv_num));
	
	//label_cell('<font color=red>'.$cv_num.'</font>',"align=center");
	}
	label_cell(sql2date($row['cv_date']));
	end_row();
	
	$t_qty+=$row['t_qty'];
	$t_sales+=$row['t_sales'];
	$t_cos+=$row['t_sales']-$subt_commision;
}

mssql_close($dbhandle);
	//hidden('s_month',$_POST['d_month']);
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2($t_qty,2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2($t_cos,2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2($t_sales,2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	end_row();
	
div_end();
end_table(1);
}

end_form();
end_page();


// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>