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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Sales Per Day Approval", false, false, "", $js);
start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	date_cells(_("Date:"), 'date_', '', null, -1);
	// date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);
div_start('totals');	
br();	
display_heading("Sales Summary for ".$_POST['date_']);
br(2);
// echo "<center>Download as excel file</center>";
//start of table display
//---------------------------------------------------------------------------------
function get_ms_suki($date)
{
	$sql = "SELECT distinct SUM(Amount) as suki_amount
	FROM [FinishedPayments]
	where LogDate='$date'
	 and TenderCode='004'
	 and voided=0
	 
	 ";

	$res = ms_db_query($sql,'error.');


	while ($row = mssql_fetch_array($res))
	{
		$suki=$row['suki_amount'];
	}
	
	return $suki;
		
}




//START OF DISINCLUDE  WHOLESALE
$sqlwholesale="select * from ".TB_PREF."wholesale_cashiers";
$wholesaleresult=db_query($sqlwholesale);
$ws_cashier = array();
while($ws_row = db_fetch($wholesaleresult))
{
	$ws_cashier[]=$ws_row['cashier_id'];
}
$wholesale_sql = '';

if (count($ws_cashier) > 0)
	$wholesale_sql = " AND cashier_id NOT IN(".implode(',',$ws_cashier).")";
//END OF DISINCLUDE WHOLESALE

//START OF DISINCLUDE  WHOLESALE
$sqlwholesale2="select * from ".TB_PREF."wholesale_cashiers";
$wholesaleresult2=db_query($sqlwholesale2);
$ws_cashier2 = array();
while($ws_row2 = db_fetch($wholesaleresult2))
{
	$ws_cashier2[]=$ws_row2['cashier_id'];
}
$wholesale_sql2 = '';

if (count($ws_cashier2) > 0)
	$wholesale_sql2 = " AND cashier_id IN(".implode(',',$ws_cashier2).")";
//END OF DISINCLUDE WHOLESALE

//START OF INCLUDE  WHOLESALE MS
$sqlwholesale3="select * from ".TB_PREF."wholesale_cashiers";
$wholesaleresult3=db_query($sqlwholesale3);
$ws_cashier3 = array();
while($ws_row3 = db_fetch($wholesaleresult3))
{
	$ws_cashier3[]=$ws_row3['cashier_id'];
}
$wholesale_sql3 = '';

if (count($ws_cashier3) > 0)
	$wholesale_sql3 = " UserID IN(".implode(',',$ws_cashier3).")";
//END OF INCLUDE  WHOLESALE MS

//-----//START OF INCLUDE  WHOLESALE MS
$sqlwholesale4="select * from ".TB_PREF."wholesale_cashiers";
$wholesaleresult4=db_query($sqlwholesale4);
$ws_cashier4 = array();
while($ws_row4 = db_fetch($wholesaleresult4))
{
	$ws_cashier4[]=$ws_row4['cashier_id'];
}
$wholesale_sql4 = '';
if (count($ws_cashier4) > 0)
	$wholesale_sql4 = " AND ft.UserID NOT IN(".implode(',',$ws_cashier4).")";
//END OF INCLUDE  WHOLESALE MS
//------------------------------------------------------------------------------------------------------------------
$c = $k = 0;

$sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
FROM MarkUsers as u
LEFT JOIN FinishedTransaction as ft
ON u.userid = ft.UserID
WHERE LogDate = '".date2sql($_POST['date_'])."'
AND Voided='0'
$wholesale_sql4
GROUP BY u.userid,u.name
ORDER BY u.name ASC";


//display_error($sql);
$res=ms_db_query($sql);
$num_of_cashier = mssql_num_rows($res);
//$myname=mysql_cashier_name($_POST['date_'],$wholesale_sql2);
$myremitotal_reading=mysql_total_reading($_POST['date_'],$wholesale_sql2);
$myremitcash=mysql_cash($_POST['date_'],$wholesale_sql2);
$myremitcredit=mysql_credit($_POST['date_'],$wholesale_sql2);
$myremitdebit=mysql_debit($_POST['date_'],$wholesale_sql2);
$myremitsuki=mysql_suki($_POST['date_'],$wholesale_sql2);
$myremitcheck=mysql_check($_POST['date_'],$wholesale_sql2);
$myremitsrsgc=mysql_srsgc($_POST['date_'],$wholesale_sql2);
$myremitgc=mysql_gc($_POST['date_'],$wholesale_sql2);
$myremitterms=mysql_terms($_POST['date_'],$wholesale_sql2);
$myremitevoucher=mysql_evoucher($_POST['date_'],$wholesale_sql2);
$myremitricepromo=mysql_rice_promo($_POST['date_'],$wholesale_sql2);
$myremitddkita=mysql_ddkita($_POST['date_'],$wholesale_sql2);
$myremitatd=mysql_atd($_POST['date_'],$wholesale_sql2);
$myremitst=mysql_st($_POST['date_'],$wholesale_sql2);
$myremitrec=mysql_receivable($_POST['date_'],$wholesale_sql2);
$myremitcwtax=mysql_cw_tax($_POST['date_'],$wholesale_sql2);
$myremitothers=mysql_others($_POST['date_'],$wholesale_sql2);
$myremitid=mysql_r_id($_POST['date_'],$wholesale_sql2);

$c=0;
while($row = mssql_fetch_array($res))
{
// $reading = get_cashier_reading($row['cashier_id'],$_POST['date_']);
//$credit=$credit_read[$row['cashier_id']];
//$debit=$debit_read[$row['cashier_id']];
//$my_name=$myname[$row['uid']];
$mytotal_reading=$myremitotal_reading[$row['uid']];
$mycash=$myremitcash[$row['uid']];
$mycredit=$myremitcredit[$row['uid']];
$mydebit=$myremitdebit[$row['uid']];
$mysuki=$myremitsuki[$row['uid']];
$mycheck=$myremitcheck[$row['uid']];
$mysrsgc=$myremitsrsgc[$row['uid']];
$mygc=$myremitgc[$row['uid']];
$myterms=$myremitterms[$row['uid']];
$myevoucher=$myremitevoucher[$row['uid']];
$myricepromo=$myremitricepromo[$row['uid']];
$myddkita=$myremitddkita[$row['uid']];
$myatd=$myremitatd[$row['uid']];
$myst=$myremitst[$row['uid']];
$myrec=$myremitrec[$row['uid']];
$mycwtax=$myremitcwtax[$row['uid']];
$myothers=$myremitothers[$row['uid']];
$myid=$myremitid[$row['uid']];

$diff = $row['total']- $mycash - $mysrsgc -$mygc - $myterms - $myevoucher-$myricepromo- $myddkita- $mysuki - $mycheck - $mydebit - $mycredit - $myatd - $myst - $myrec -$mycwtax -$myothers;

if($diff <= 0){
$over+=$diff;
}
else
{

$short+=($diff);
}


$simpos_sales+=$row['total'];
$total_remittance+=$mytotal_reading;
$total_cash+=$mycash;
$total_srsgc+=$mysrsgc;
$total_gc+=$mygc;
$total_terms+=$myterms;
$total_evoucher+=$myevoucher;
$total_ricepromo+=$myricepromo;
$total_ddkita+=$myddkita;
$total_t_sc+=$mysuki;
$total_t_check+=$mycheck;
$total_t_dc+=$mydebit;
$total_t_cc+=$mycredit;
$total_atd+=$myatd;
$total_st+=$myst;
$total_rec+=$myrec;
$total_cw_tax+=$mycwtax;
$total_o+=$myothers;
$total_discount+=$sc_disc[$row['cashier_id']];
$total_diff=$simpos_sales+abs($over)-abs($short);
if ($total_diff>$simpos_sales){
$final_diff=$total_diff-$simpos_sales;
}
else {
$final_diff=$simpos_sales-$total_diff;
}
}

															// $sql="SELECT uid, c_name,total FROM (SELECT u.userid as uid, u.name as c_name
															// FROM MarkUsers as u
															// where $wholesale_sql3)as a
															// left join 
															// (SELECT userid as uid2,(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total FROM FinishedTransaction as ft
															// WHERE LogDate = '".date2sql($_POST['date_'])."'  AND Voided='0' AND  $wholesale_sql3
															// group by userid) as b
															// on a.uid=b.uid2
															// ORDER BY c_name ASC";

															// $res2=ms_db_query($sql);
															// $num_of_cashier = mssql_num_rows($res2);
																
																// //$myname2=mysql_cashier_name2($_POST['date_'],$wholesale_sql2);
																// $myremitotal_reading2=mysql_total_reading2($_POST['date_'],$wholesale_sql2);
																// $myremitcash2=mysql_cash2($_POST['date_'],$wholesale_sql2);
																// $myremitcredit2=mysql_credit2($_POST['date_'],$wholesale_sql2);
																// $myremitdebit2=mysql_debit2($_POST['date_'],$wholesale_sql2);
																// $myremitsuki2=mysql_suki2($_POST['date_'],$wholesale_sql2);
																// $myremitcheck2=mysql_check2($_POST['date_'],$wholesale_sql2);
																// $myremitsrsgc2=mysql_srsgc2($_POST['date_'],$wholesale_sql2);
																// $myremitgc2=mysql_gc2($_POST['date_'],$wholesale_sql2);
																// $myremitterms2=mysql_terms2($_POST['date_'],$wholesale_sql2);
																// $myremitevoucher2=mysql_evoucher2($_POST['date_'],$wholesale_sql2);
																// $myremitricepromo2=mysql_rice_promo2($_POST['date_'],$wholesale_sql2);
																// $myremitddkita2=mysql_ddkita2($_POST['date_'],$wholesale_sql2);
																// $myremitatd2=mysql_atd2($_POST['date_'],$wholesale_sql2);
																// $myremitst2=mysql_st2($_POST['date_'],$wholesale_sql2);
																// $myremitrec2=mysql_receivable2($_POST['date_'],$wholesale_sql2);
																// $myremitcwtax2=mysql_cw_tax2($_POST['date_'],$wholesale_sql2);
																// $myremitothers2=mysql_others2($_POST['date_'],$wholesale_sql2);				
																// $myremitid2=mysql_r_id2($_POST['date_'],$wholesale_sql2);
																
																// $c=0;
																// while($row2 = mssql_fetch_array($res2))
																// {
																// //$my_name2=$myname2[$row2['uid']];
																// $mytotal_reading2=$myremitotal_reading2[$row2['uid']];
																// $mycash2=$myremitcash2[$row2['uid']];
																// $mycredit2=$myremitcredit2[$row2['uid']];
																// $mydebit2=$myremitdebit2[$row2['uid']];
																// $mysuki2=$myremitsuki2[$row2['uid']];
																// $mycheck2=$myremitcheck2[$row2['uid']];
																// $mysrsgc2=$myremitsrsgc2[$row2['uid']];
																// $mygc2=$myremitgc2[$row2['uid']];
																// $myterms2=$myremitterms2[$row2['uid']];
																// $myevoucher2=$myremitevoucher2[$row2['uid']];
																
																// $myricepromo2=$myremitricepromo2[$row2['uid']];
																
																// $myddkita2=$myremitddkita2[$row2['uid']];
																// $myatd2=$myremitatd2[$row2['uid']];
																// $myst2=$myremitst2[$row2['uid']];
																// $myrec2=$myremitrec2[$row2['uid']];
																// $mycwtax2=$myremitcwtax2[$row2['uid']];
																// $myothers2=$myremitothers2[$row2['uid']];
																// $myid2=$myremitid2[$row2['uid']];
																
																// // list($mycash2, $mysrsgc2, $mygc2, $myterms2, $myevoucher2, 
																// // $mysuki2, $mycheck2, $mydebit2, $mycredit2, $myatd2, $myst2, $myrec2,$myothers2) 
																// // = wh_total_remittance($_POST['date_'],$wholesale_sql2);
																					
																// $diff2 = $row2['total']- $mycash2 - $mysrsgc2 -$mygc2 - $myterms2 - $myevoucher2-$myricepromo2- $myddkita2- $mysuki2 - $mycheck2 - $mydebit2 - $mycredit2 - $myatd2 - $myst2 - $myrec2 - $mycwtax2 -$myothers2;
																
																	  // if ($mytotal_reading<=0 or $mytotal_reading=='')
																	// {
																	// $over2=0;
																	// $diff2=0;
																	 // }
																	
																	// $simpos_sales2+=$row2['total'];
																	// $total_remittance2+=$mytotal_reading2;
																	// $total_cash2+=$mycash2;
																	// $total_srsgc2+=$mysrsgc2;
																	// $total_gc2+=$mygc2;
																	// $total_terms2+=$myterms2;
																	// $total_evoucher2+=$myevoucher2;
																	// $total_ricepromo2+=$myricepromo2;
																	// $total_ddkita2+=$myddkita2;
																	// $total_t_sc2+=$mysuki2;
																	// $total_t_check2+=$mycheck2;
																	// $total_t_dc2+=$mydebit2;
																	// $total_t_cc2+=$mycredit2;
																	// $total_atd2+=$myatd2;
																	// $total_st2+=$myst2;
																	// $total_rec2+=$myrec2;
																	// $total_cw_tax2+=$mycwtax2;
																	// $total_o2+=$myothers2;
																	// $total_discount2+=$sc_disc2[$row2['cashier_id']];
															// }	
															
															// $total_diff2=$simpos_sales2-($total_cash2+$total_srsgc2+$total_gc2+$total_terms2+$total_evoucher2+$total_ricepromo2+$total_ddkita2+$total_t_sc2+$total_t_check2+$total_t_dc2+$total_t_cc2+$total_atd2+$total_st2+$total_rec2+$total_cw_tax2+$total_o2);

															// if($total_diff2 <= 0){
															// $t_over=abs($over)+abs($total_diff2);
															// $t_short=abs($short);
															// }
															// else
															// {
															// $t_short=abs($short)+abs($total_diff2);
															// $t_over=abs($over);	
															// }
																	// $t_simpos=$simpos_sales+$simpos_sales2;
																	// $t_remittance=$total_remittance+$total_remittance2;
																	// $t_cash=$total_cash+$total_cash2;
																	// $t_srsgc=$total_srsgc+$total_srsgc2;
																	// $t_gc=$total_gc+$total_gc2;
																	// $t_terms=$total_terms+$total_terms2;
																	// $t_evoucher=$total_evoucher+$total_evoucher2;
																	// $t_ricepromo=$total_ricepromo+$total_ricepromo2;
																	// $t_ddkita=$total_ddkita+$total_ddkita2;
																	// $t_t_sc=$total_t_sc+$total_t_sc2;
																	// $t_t_check=$total_t_check+$total_t_check2;
																	// $t_t_dc=$total_t_dc+$total_t_dc2;
																	// $t_t_cc=$total_t_cc+$total_t_cc2;
																	// $t_atd=$total_atd+$total_atd2;
																	// $t_st=$total_st+$total_st2;
																	// $t_rec=$total_rec+$total_rec2;
																	// $t_cwtax=$total_cw_tax+$total_cw_tax2;
																	// $t_o=$total_o+$total_o2;
																	// // $t_final_diff=$final_diff+abs($total_diff2);
																	// $t_final_diff=abs($t_over)-abs($t_short);

if (isset($_POST['approve_cash']))
{
 global $Ajax;
 begin_transaction();
 //INSERT TO SALES TOTALS
 
 
 
 if($_POST['t_date']!=''){
	 

	
	
	$sqlbutton = "select * from ".TB_PREF."gl_trans where account='4900' and tran_date='".date2sql($_POST['t_date'])."' and type=60 and amount!=0";
	//display_error($sqlbutton);
	$resbut=db_query($sqlbutton);
	$row = db_fetch($resbut);	
	$current_sales_discount=abs($row['amount']);
	
	$diff=$current_sales_discount-$_POST['t_t_sc'];
	// display_error($current_sales_discount);
	// display_error($diff);
	
	
	$sqlbutton1 = "select * from ".TB_PREF."gl_trans where account='4020' and tran_date='".date2sql($_POST['t_date'])."' and type=60 and amount!=0";
	$resbut1=db_query($sqlbutton1);
	$row1 = db_fetch($resbut1);	
	$current_sales_discount1=abs($row1['amount']);
	
	
	
	if ($_POST['t_t_sc']>$current_sales_discount){
		$new_oi=$current_sales_discount1+abs($diff);
	}
	else{
		$new_oi=$current_sales_discount1-abs($diff);
	}
	
	
	
	
	
	

	
	 $sql2 = "UPDATE ".TB_PREF."gl_trans SET amount=-$new_oi where account='4020' and tran_date='".date2sql($_POST['t_date'])."' and type=60 and amount!=0"; 
	//display_error($sql2);
	db_query($sql2);
	

	 $sql1 = "UPDATE ".TB_PREF."gl_trans SET amount=".$_POST['t_t_sc']." where account='4900' and tran_date='".date2sql($_POST['t_date'])."' and type=60 and amount!=0"; 
	//display_error($sql1);
	db_query($sql1);
	
	// $sql3 = "UPDATE ".TB_PREF."gl_trans SET amount=-".$_POST['t_t_sc']." where account='4000040' and tran_date='".date2sql($_POST['t_date'])."' and type=60 and amount!=0"; 
	// //display_error($sql1);
	// db_query($sql3);
	
	display_notification(_("The Suki Points has been updated."));
 }
 


commit_transaction();
$Ajax->activate('table_');	
}		
	
$sqlbutton = "select * from ".TB_PREF."salestotals where ts_date_remit='".date2sql($_POST['date_'])."' and approved=1";
$resbut=db_query($sqlbutton);
$approved_date = mysql_num_rows($resbut);	

$date=date2sql($_POST['date_']);

$t_t_sc=get_ms_suki($date);
		
 // if ($approved_date<1)
 // {
// div_start('table_');
hidden('t_date',$_POST['date_']);
// hidden('t_simpos',$t_simpos);
// hidden('t_srsgc',$t_srsgc);
// hidden('t_gc',$t_gc);
// hidden('t_terms',$t_terms);
// hidden('t_evoucher',$t_evoucher);
// hidden('t_ricepromo',$t_ricepromo);
// hidden('t_ddkita',$t_ddkita);
hidden('t_t_sc',$t_t_sc);
// hidden('t_t_check',$t_t_check);
// hidden('t_t_dc',$t_t_dc);
// hidden('t_t_cc',$t_t_cc);
// hidden('t_atd',$t_atd);
// hidden('t_st',$t_st);
// hidden('t_rec',$t_rec);
// hidden('t_cwtax',$t_cwtax);
// hidden('t_short',$t_short);
// hidden('t_over',$t_over);
// hidden('t_cash',$t_cash);
 start_table();
// start_row();
// label_cells('TOTAL READING:',$t_simpos);
// end_row();

// start_row();
// label_cells('TOTAL CASH:',$t_cash);
// end_row();

// start_row();
// label_cells('TOTAL SRSGC:',$t_srsgc);
// end_row();

// start_row();
// label_cells('TOTAL GC:',$t_gc);
// end_row();

start_row();
label_cells('TOTAL SUKICARD:',$t_t_sc);
end_row();

// start_row();
// label_cells('TOTAL CHECK:',$t_t_check);
// end_row();

// start_row();
// label_cells('TOTAL DEBIT:',$t_t_dc);
// end_row();

// start_row();
// label_cells('TOTAL CREDIT:',$t_t_cc);
// end_row();

// start_row();
// label_cells('TOTAL OVER:',$t_over);
// end_row();

// start_row();
// label_cells('TOTAL SHORT:',$t_short);
// end_row();
//text_cells('','t_cash',$t_cash,'12');
end_table();
br(2);
start_table();
start_row();
submit_cells('approve_cash', 'Approve', "align=center", true, true,'ok.gif');
end_row();
end_table();
// }
// else{
	// start_table();
	// start_row();
	// label_cells("<b><font color='red'>ALREADY APPROVED...<b></font>");
	// end_row();
	// end_table();
// }
end_form();
end_row();
end_table(2);
div_end();
end_form();
end_page();
?>