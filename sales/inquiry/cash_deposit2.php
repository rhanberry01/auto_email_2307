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
	
page("Deposit Remittance", false, false, "", $js);

//------------------------------------------------------------------------------------------------
$approve_id = find_submit('insert_cash_deposit');
// $approve_id = find_submit('approve_acquiring');


start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	date_cells(_("Date:"), 'date_', '', null, -1);
	//date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);


$date_from =date2sql($_POST['date_']);
//$date_to = date2sql($_POST['TransToDate']);
display_heading("Deposit Remittance: ".$_POST['date_']);
br();
br();


 if ($approve_id != -1)
{
$x=implode("-",$approve_id);
$year = $x[0]; 
$month =$x[1];
$day = $x[2];
$transaction_date=$year.$month.$day;

$sqlselect="SELECT SUM(r.total_cash) as t_cash,
SUM(r.total_srs_gc) as t_srsgc,
SUM(r.total_suki_card) as t_sc,
SUM(r.total_debit_card) as t_dc,
SUM(r.total_credit_card) as t_cc,
SUM(r.total_atd) as t_atd,
SUM(r.total_stock_transfer) as t_st,
(SUM(r.total_cash)+SUM(r.total_credit_card)+SUM(r.total_debit_card)+
SUM(r.total_suki_card)+SUM(r.total_srs_gc)+SUM(r.total_atd)+
SUM(r.total_stock_transfer)) 
as total_remittance,
r.remittance_date as r_date,
rd.date_remit as date_remit,
rd.cash_deposit as cash_deposit,
rd.srsgc_deposit as srsgc_deposit,
rd.suki_deposit as suki_deposit,
rd.debit_deposit as debit_deposit,
rd.credit_deposit as credit_deposit,
rd.atd_deposit as atd_deposit,
rd.st_deposit as st_deposit,
rd.total_deposit as total_deposit,
rd.date_deposit as d_deposit,
rd.approved as c_approved
FROM ".CR_DB.TB_PREF."remittance as r
left join ".CR_DB.TB_PREF."remittance_deposit as rd
on r.remittance_date = rd.date_remit
WHERE r.remittance_date ='".$date_from."'
AND r.is_disapproved = 0
AND r.treasurer_id != 0
AND r.remittance_date ='$approve_id'
group by r.remittance_date order by r.remittance_date asc";
$res=db_query_rs($sqlselect);
//display_error($sqlselect);

while($row = db_fetch($res))
{
$c ++;
$r_date=$row['r_date'];
$cash_remit=$row['t_cash'];
$t_srsgc_remit=$row['t_srsgc'];
$t_sc_remit=$row['t_sc'];
$t_dc_remit=$row['t_dc'];
$t_cc_remit=$row['t_cc'];
$t_atd_remit=$row['t_atd'];
$t_st_remit=$row['t_st'];
$total_remit=$row['total_remittance'];

//$total_sales=$cash_remit+$t_srsgc_remit+$t_sc_remit+$t_dc_remit+$t_cc_remit+$t_atd_remit+$t_st_remit;									

$cash_deposit=$row['cash_deposit'];
$srsgc_deposit=$row['srsgc_deposit'];
$suki_deposit=$row['suki_deposit'];
$debit_deposit=$row['debit_deposit'];
$credit_deposit=$row['credit_deposit'];
$atd_deposit=$row['atd_deposit'];
$st_deposit=$row['st_deposit'];
$date_deposit=$row['d_deposit'];
$approved=1;
}
}


if ($approve_id != -1)
{
global $Ajax;
$r_date= $_POST['r_date'];
$cash_todeposit = $_POST['cash_remit'];
$srsgc_todeposit = $_POST['t_srsgc_remit'];
$suki_todeposit = $_POST['t_sc_remit'];
$debit_todeposit = $_POST['t_dc_remit'];
$credit_todeposit = $_POST['t_cc_remit'];
$atd_todeposit = $_POST['t_atd_remit'];
$at_todeposit = $_POST['t_st_remit'];
//$totalremit_todeposit = $_POST['total_remit'];
$date_todeposit = date2sql($_POST['date_deposit']);
$approved=1;


$sql = "INSERT INTO ".CR_DB.TB_PREF."remittance_deposit
(date_remit,cash_deposit,srsgc_deposit,suki_deposit,debit_deposit,credit_deposit,
atd_deposit,st_deposit,total_deposit,date_deposit,approved) 
VALUES('".$r_date."','".$cash_todeposit."','".$srsgc_todeposit."','".$suki_todeposit."','".$debit_todeposit."','".$credit_todeposit."',
'".$atd_todeposit."','".$at_todeposit."','".$totalremit_todeposit."','".$date_todeposit."','".$approved."')";
db_query($sql,"Bank Deduction could not be approved");
//display_notification('Selected has been approved');
//display_error($sql);
$Ajax->activate('table_');
}


																div_start('table_');
																//start of table display
																start_table($table_style2);
																$th = array('',_("Date Remit"),'Cash','SRSGC','Suki Card','Debit','Credit','ATD','ST','Date Deposit',"");
																table_header($th);


																$sql="SELECT SUM(r.total_cash) as t_cash,
																SUM(r.total_srs_gc) as t_srsgc,
																SUM(r.total_suki_card) as t_sc,
																SUM(r.total_debit_card) as t_dc,
																SUM(r.total_credit_card) as t_cc,
																SUM(r.total_atd) as t_atd,
																SUM(r.total_stock_transfer) as t_st,
																(SUM(r.total_cash)+SUM(r.total_credit_card)+SUM(r.total_debit_card)+
																SUM(r.total_suki_card)+SUM(r.total_srs_gc)+SUM(r.total_atd)+
																SUM(r.total_stock_transfer)) 
																as total_remittance,
																r.remittance_date as r_date,
																rd.date_remit as date_remit,
																rd.cash_deposit as cash_deposit,
																rd.srsgc_deposit as srsgc_deposit,
																rd.suki_deposit as suki_deposit,
																rd.debit_deposit as debit_deposit,
																rd.credit_deposit as credit_deposit,
																rd.atd_deposit as atd_deposit,
																rd.st_deposit as st_deposit,
																rd.total_deposit as total_deposit,
																rd.date_deposit as d_deposit,
																rd.approved as c_approved
																FROM ".CR_DB.TB_PREF."remittance as r
																left join ".CR_DB.TB_PREF."remittance_deposit as rd
																on r.remittance_date = rd.date_remit
																WHERE r.remittance_date ='".$date_from."'
																AND r.is_disapproved = 0
																AND r.treasurer_id != 0
																group by r.remittance_date order by r.remittance_date asc";
																$result=db_query_rs($sql);


																while($row = db_fetch($result))
																{
															
																$c ++;

																$r_date=$row['r_date'];
																$cash_remit=$row['t_cash'];
																$t_srsgc_remit=$row['t_srsgc'];
																$t_sc_remit=$row['t_sc'];
																$t_dc_remit=$row['t_dc'];
																$t_cc_remit=$row['t_cc'];
																$t_atd_remit=$row['t_atd'];
																$t_st_remit=$row['t_st'];
															//	$total_remit=$row['total_remittance'];

																//$total_sales=$cash_remit+$t_srsgc_remit+$t_sc_remit+$t_dc_remit+$t_cc_remit+$t_atd_remit+$t_st_remit;									
																
																
																$date_remit=$row['date_remit'];
																$cash_deposit=$row['cash_deposit'];
																$srsgc_deposit=$row['srsgc_deposit'];
																$suki_deposit=$row['suki_deposit'];
																$debit_deposit=$row['debit_deposit'];
																$credit_deposit=$row['credit_deposit'];
																$atd_deposit=$row['atd_deposit'];
																$st_deposit=$row['st_deposit'];
																$date_deposit=$row['d_deposit'];
																$approved=1;

																alt_table_row_color($k);
																label_cell($c,'align=right');
																
																
																if ($row['d_deposit']!='' and $row['c_approved']!='')
																{
																label_cell($date_remit);
																amount_cell($cash_deposit);
																amount_cell($srsgc_deposit);
																amount_cell($suki_deposit);
																amount_cell($debit_deposit);
																amount_cell($credit_deposit);
																amount_cell($atd_deposit);
																amount_cell($st_deposit);
																//amount_cell($total_deposit);
																label_cell($date_deposit);
																label_cell('Approved');
																}

																else {
																text_cells('',r_date,$r_date);
																amount_cells('',cash_remit,$cash_remit);
																amount_cells('',t_srsgc_remit,$t_srsgc_remit);
																amount_cells('',t_sc_remit,$t_sc_remit);
																amount_cells('',t_dc_remit,$t_dc_remit);
																amount_cells('',t_cc_remit,$t_cc_remit);
																amount_cells('',t_atd_remit,$t_atd_remit);
																amount_cells('',t_st_remit,$t_st_remit);
																//amount_cells('',total_remit,$total_remit);
																date_cells('',date_deposit);
	
																$x=explode("-",$r_date);
																$year = $x[0]; 
																$month =$x[1];
																$day = $x[2];
																$transaction_date=$year.$month.$day;

																$submit='insert_cash_deposit'.$transaction_date;
																submit_cells($submit, 'Process', "align=center", true, false,'ok.gif');
																}
																end_row();
																}	

	// label_cell('');
	// label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	// label_cell('');
	// label_cell('');
	// label_cell('');
	// label_cell('');
	// label_cell('');
	end_row();

end_table(1);
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