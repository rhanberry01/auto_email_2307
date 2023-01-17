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

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

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
include_once($path_to_root . "/inventory/includes/stock_transfer2.inc");

//LAST REVISION: 7/09/2018 by bolalin
//REVISIONS: changed _qty_out to actual_qty_out

//jade added 2/13/2018 ajax request 
// include_once($path_to_root . "/inventory/includes/stock_trans_inquiry_details_ui.php");

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
	
page("Stock Transfer Summary", false, false, "", $js);

//------------------------------------------------------------------------------------------------ 

function cashier_summary_per_day_excel()
{
	ini_set('mssql.connect_timeout',0);
	ini_set('mssql.timeout',0);
	set_time_limit(0);
	
	global $path_to_root, $db_connections;
	
	$com = get_company_prefs();
	
	$date_= $_POST['start_date'];
	$date_t= $_POST['end_date'];
	
$datefrom=date2sql($_POST['date_']);
$dateto=date2sql($_POST['TransToDate']);
	$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Stock Transfer Summary'), "stock_transfer_summary", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'STOCK TRANSFER SUMMARY', $format_bold);
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
	$rep->sheet->setColumn(1,20,20); //set column width
	// $rep->sheet->setColumn(2,3,13); //set column width
	// $rep->sheet->setColumn(4,4,38); //set column width
	// $rep->sheet->setColumn(5,5,13); 
	// $rep->sheet->setColumn(6,6,38); 
	// $rep->sheet->setColumn(7,7,6); 
	// $rep->sheet->setColumn(8,10,12); 
	// $rep->sheet->setColumn(11,12,20); 
	

	
	//setColumn(cellnum,cellsize/width,colnum);
	
//------------------------------------------------------------------------------------------
	
	$x=0;

	$th = array();

array_push($th, '','Date Created', 'Transfer #','MovementOut #','From Branch','Date Dispatch','Qty out','MovementIn #', 'To Branch', 'Date In', 'Qty In');

	foreach($th as $header)
	{
	$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
	$x++;
	}
	$rep->y++;
	$c = $k = 0;
	
	$mtype=get_selected_movement_type($_POST['movement_type']);
			//modify jade 2/13/2018 delete cost in sum query add qty_in and qty_out select
		$sql = "SELECT th.*, sum(actual_qty_out) as cost_out,sum(qty_in) as cost_in,  sum(qty_out) as _qty_out FROM transfers.0_transfer_header as th
	LEFT JOIN transfers.0_transfer_details as td
	on th.id=td.transfer_id
	where th.date_created>='".date2sql($_POST['start_date'])."'  and th.date_created<='".date2sql($_POST['end_date'])."'
	and th.br_code_out='$myBranchCode'
	and th.m_code_out='STO'
	and th.m_id_out!=0
	and th.m_id_in!=''
	GROUP BY th.id
	order by th.id
	";
		$res = db_query($sql);

while($row = db_fetch($res))
{
		//jade added
		$total_in =  $row['cost_in'];
		$total_out = $row['cost_out'];
		$total_diff= $total_in - $total_out;
		
		$c ++;
		$x = 0;
		
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['date_created']),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['id'], $format_left);
		$x++;
		
		$rep->sheet->writeString($rep->y, $x, $row['m_no_out'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, get_transfer_branch_name($row['br_code_out']),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['transfer_out_date']),$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['cost_out'], $format_accounting);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['m_no_in'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x,get_transfer_branch_name($row['br_code_in']),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($row['transfer_in_date']),$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['cost_in'], $format_accounting);
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
				$rep->sheet->writeString($rep->y, $x, '', $format_bold);

		// $rep->sheet->writeString($rep->y, $x, 'NET TOTAL:', $format_bold_right);
		// $x++;
		// $rep->sheet->writeNumber($rep->y, $x, $net_total, $format_accounting);
		$rep->End();
}
//end of excel report------------------------------------------------------------------------------------
		function get_selected_movement_type($type)
		{
			$sql_select_all_input="select movement_code from ".TB_PREF."movement_types where id='$type'";
			//display_error($sql_select_all_input);
			$res=db_query($sql_select_all_input);
			$row = db_fetch($res);
			$movement_code=$row['movement_code'];
			return $movement_code;
		}

start_form();
div_start('header');

$type = ST_INVADJUST;

// if (!isset($_POST['start_date']))
	// $_POST['start_date'] = '01/01/'.date('Y');
// start_table();
// start_row();
// adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
// end_row();
// end_table();

br();

start_table();
	start_row();
		//ref_cells('Transaction#:', 'trans_no');
				//text_cells('Barcode:','barcode','',9);
			//	yesno_list_cells(_("Status Type:"), 'status_type', '',_("Open"), _("Posted"));
			//	get_ms_products_category_list_cells('','category',null,true);
	//	supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
		//yesno_list_cells(_("Status Type:"), 'movement_type', '',_("Deliver to Branch"), _("Received from Branch"));
		//adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
				// echo "<td>"._("To Location:")."</td><td><select name='from_loc'>\n";
		// echo "<option value='' selected:>Select Branch</option>";
		// for ($i = 0; $i < count($db_connections); $i++)
			// echo "<option value=".$db_connections[$i]["br_code2"].">" . $db_connections[$i]["name"] . "</option>";
		// echo "</select>\n";
		
		// echo "<td>"._("To Location:")."</td><td><select name='to_loc'>\n";
		// echo "<option value='' selected:>Select Branch</option>";
		// for ($i = 0; $i < count($db_connections); $i++)
			// echo "<option value=".$db_connections[$i]["br_code2"].">" . $db_connections[$i]["name"] . "</option>";
		// echo "</select>\n";
		
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();

$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
$mtype=get_selected_movement_type($_POST['movement_type']);
		
		//modify jade 2/13/2018 delete cost in sum query add qty_in and qty_out select
	$sql = "SELECT th.*, sum(actual_qty_out) as cost_out,sum(qty_in) as cost_in,  sum(qty_out) as _qty_out FROM transfers.0_transfer_header as th
	LEFT JOIN transfers.0_transfer_details as td
	on th.id=td.transfer_id
	where th.date_created >= '".date2sql($_POST['start_date'])."'  and th.date_created <='".date2sql($_POST['end_date'])."'
	and th.br_code_out='$myBranchCode'
	and th.m_code_out='STO'
	and th.m_id_out!=0
	and th.m_id_in!=''
	GROUP BY th.id
	order by th.id
	";
		$res = db_query($sql);
				
	
	// if ($_POST['from_loc'] != ''){
		// $sql .= ' AND th.br_code_out = ' .db_escape($_POST['from_loc']);
	// }
		
	
	// if ($_POST['to_loc'] != ''){
				// $sql .= ' AND th.br_code_in = ' .db_escape($_POST['to_loc']);
	// }


	// $sql .="	GROUP BY th.id
	// order by th.br_code_out,th.id
	// ";
// display_error($sql);
start_table($table_style2.' width=90%');
$th = array();
	
array_push($th, 'Date Created', 'Transfer #','MovementOut #','From Branch','Date Dispatch','Qty out','MovementIn #', 'To Branch', 'Date In', 'Qty In', 'Discrepancy','Remarks','Attactment file','Checked_by','','');
// $count=mssql_num_rows($res) ;
 // display_error($count);

if (mysql_num_rows($res) > 0){
	submit_center('dl_excel','Download as excel file');
	br();
	table_header($th);
	display_heading("Stock Transfer Summary From ".$_POST['start_date']." To ".$_POST['end_date']);
	br();
}

else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$k = 0;
$total_out = 0;
while($row = db_fetch($res))
{
	echo '<pre>';
	// print_r($row);
	echo '</pre>';
	// jade added 02/13/2018 diff 
	$total_in =  $row['cost_in'];
	$total_out = $row['cost_out'];
	$total_diff = $total_in - $total_out;
	$descrip = number_format2(abs($total_diff),2);
	if($total_diff != 0.00 || $total_diff != "0.00"){
			// echo '<pre>';
			// print_r($row);
			alt_table_row_color($k);
			label_cell(sql2date($row['date_created']));
			label_cell($row['id']);
			label_cell($row['m_no_out']);
			//label_cell($row['br_code_out']);
			label_cell(get_transfer_branch_name($row['br_code_out']));

			label_cell(sql2date($row['transfer_out_date']));
		//	label_cell($row['cost']);
			label_cell(number_format2(abs($row['cost_out']),2));
			label_cell($row['m_no_in']);
			label_cell(get_transfer_branch_name($row['br_code_in']));
			//label_cell($row['br_code_in']);
			label_cell(sql2date($row['transfer_in_date']));
			label_cell(number_format2(abs($row['cost_in']),2));
			label_cell(number_format2(abs($total_diff),2));
			// label_cell('<a href="stock_trans_inquiry_details.php?transfer_id='.$row['id'].'">View</a>'); 
			// jade added 2/13/2018
			label_cell($row['remarsk_descrip']);
			if($row['remarsk_descrip'] != ""){
				$file_scanned = '<a href="../../invoice_attachment/'.$row['id'].'-'.$row['br_code_out'].'.pdf" target="_blank">Scanned File</a>';
			}else{
				$file_scanned = '';
			}
			label_cell($file_scanned);
			if($row['tagging_checked_by'] != ""){
				$checked = "checked";
			}else{
				$checked = "";
			}
			label_cell(''.$row['tagging_checked_by'].'','class="xchecked_by"');
			label_cell('<a href="#" onclick="show_details('.$row['id'].');">View</a>');
			label_cell('<input type="checkbox" name="txtchecked" onclick="Checked_by(this.value)" '.$checked.' value="'.$row['id'].'"> Checked');
			//end jade added
			// echo '<a href="#" id="myBtn'.$row['id'].'" hidden onclick="viewModal('.$row['id'].');">View</a>';
			// label_cell($row['UOM']);
			// label_cell($row['qty']);
			// label_cell(number_format2(abs($row['unitcost']),3));
			// label_cell(number_format2(abs($row['extended']),2));
			//label_cell($row['Remarks']);
			end_row();
			$net_total+=$row['extended'];
	}
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
label_cell('');
label_cell('');
label_cell('');
label_cell('');
//label_cell('<font color=#880000><b>'.'NET AMOUNT:'.'</b></font>');
label_cell('');
//label_cell("<font color=#880000><b>".number_format2(abs($net_total),2)."<b></font>",'align=left');
//label_cell('');
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
//tagging_checked_by
?>
<form method="post">
	<input type="hidden" name="xby_checked" id="xby_checked" >
	<button type="button" name="btn_checked" id="btn_checked" onclick="Checked_by()"></button>
</form>

<script>
function show_details(value){
	window.open("../includes/stock_trans_inquiry_details_ui.php?transfer_id="+value, "_blank", "toolbar=yes,scrollbars=yes,resizable=yes,top=220,left=200,width=1000,height=400");
}
function Checked_by(value=""){
	if(value!=""){
		$("#xby_checked").val(value);
		$("#btn_checked").trigger("click");
	}else{
		var xuser = '<?=$_SESSION["wa_current_user"]->username?>';
		var xid = $("#xby_checked").val();
		var xaction = 'update_checked';
		$.ajax({
			url: '../functions/ajax_request.php',
			type: 'post',
			data: {
				xuser:xuser,
				xid:xid,
				xaction:xaction,
			},
			success: function(data){
				alert(data);
				location.reload();
			}
		});
	}
}
</script>
