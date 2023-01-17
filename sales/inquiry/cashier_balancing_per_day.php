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
include_once($path_to_root . "/sales/includes/db/cashier_balancing_per_day_db.inc");
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
	
page("Cashier Summary per Day", false, false, "", $js);
//------------------------------------------------------------------------------------------------

function cashier_summary_per_day_excel()
{
	global $path_to_root;
	$com = get_company_prefs();
	$date_= $_POST['date_'];
	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);
    $rep = new FrontReport(_('Remittance Summary per Day'), "Remittance_Summary_per_Day", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'REMITTANCE SUMMARY PER DAY', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date : '.$date_, $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);
	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,22); //set column width
	$rep->sheet->setColumn(2,3,12); //set column width
	$rep->sheet->setColumn(4,9,9); //set column width
	$rep->sheet->setColumn(10,11,12); //set column width
	$rep->sheet->setColumn(11,12,8); //set column width
	$rep->sheet->setColumn(12,13,11); //set column width
	$rep->sheet->setColumn(15,18,11); //set column width
	//setColumn(cellnum,cellsize/width,colnum);

$x=0;
	
$th = array(' ', _("Cashier Name"), _("Reading"), 'Cash', 'SRSGC', 'GC', 'Terms', 'EVoucher', 'Rice Promo','DD-Kita','Suki Card', 'Check', 'BPI Debit','BPI Credit','Metro Debit','Metro Credit','ATD','ST','Rec.','Rec. from Cus','Rec. from Sup','CWTax','Others','Diff','Short','Over','');
//---------------------------------------------------------------------------------

//START OF INCLUDE  WHOLESALE MS
$sqlwholesale3="select * from ".TB_PREF."wholesale_counter";
$wholesaleresult3=db_query($sqlwholesale3);
$ws_cashier3 = array();
while($ws_row3 = db_fetch($wholesaleresult3))
{
	$ws_cashier3[]=$ws_row3['counter'];
}
$wholesale_sql3_wholesale = '';

if (count($ws_cashier3) > 0)
	$wholesale_sql3_wholesale= "  AND ft.TerminalNo IN(".implode(',',$ws_cashier3).")";

//display_error($wholesale_sql3_wholesale);
//END OF INCLUDE  WHOLESALE MS


//-----//START OF INCLUDE  SPC CTR MS
$sqlwholesale5="select * from ".TB_PREF."special_counter";
$wholesaleresult5=db_query($sqlwholesale5);
$ws_cashier5 = array();
while($ws_row5 = db_fetch($wholesaleresult5))
{
	$ws_cashier5[]=$ws_row5['counter'];
}
$wholesale_sql_spc_ctr = '';
if (count($ws_cashier5) > 0)
	$wholesale_sql_spc_ctr= " AND  ft.TerminalNo IN(".implode(',',$ws_cashier5).")";
//END OF INCLUDE  SPC CTR MS

$wholesale_sql_retail='';
if (count($ws_cashier3) > 0 and count($ws_cashier5) > 0 ){
	$wholesale_sql_retail = " AND  ft.TerminalNo NOT IN(".implode(',',$ws_cashier3).") AND  ft.TerminalNo NOT IN(".implode(',',$ws_cashier5).")";
	$wholesale_sql = " AND  terminal_nos NOT IN(".implode(',',$ws_cashier3).") AND  terminal_nos NOT IN(".implode(',',$ws_cashier5).")";
	
}
	
//END OF INCLUDE  WHOLESALE MS


foreach($th as $header)
{
$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
$x++;
}
$rep->y++;
$c = $k = 0;

//------------------------------------------------------------------------------------------------------------------


$sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
FROM MarkUsers as u
LEFT JOIN FinishedTransaction as ft
ON u.userid = ft.UserID
WHERE LogDate = '".date2sql($_POST['date_'])."'
AND Voided='0'
$wholesale_sql_retail
GROUP BY u.userid,u.name
ORDER BY u.name ASC";
//display_error($sql."11111111111111111");
$res=ms_db_query($sql);
$num_of_cashier = mssql_num_rows($res);
//$myname=mysql_cashier_name($_POST['date_'],$wholesale_sql2);
$myremitotal_reading=mysql_total_reading($_POST['date_'],$wholesale_sql2);
$myremitcash=mysql_cash($_POST['date_'],$wholesale_sql2);
$myremitbpidebit=mysql_bpi_debit($_POST['date_'],$wholesale_sql2);
$myremitbpicredit=mysql_bpi_credit($_POST['date_'],$wholesale_sql2);
$myremitmetrodebit=mysql_metro_debit($_POST['date_'],$wholesale_sql2);
$myremitmetrocredit=mysql_metro_credit($_POST['date_'],$wholesale_sql2);
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
$myremitrec_c=mysql_receivable_c($_POST['date_'],$wholesale_sql2_c);
$myremitrec_s=mysql_receivable_s($_POST['date_'],$wholesale_sql2_s);
$myremitcwtax=mysql_cw_tax($_POST['date_'],$wholesale_sql2);
$myremitothers=mysql_others($_POST['date_'],$wholesale_sql2);
$myremitid=mysql_r_id($_POST['date_'],$wholesale_sql2);

$c=0;
while($row = mssql_fetch_array($res))
{
	$x=0;
$c ++;
alt_table_row_color($k);
// $reading = get_cashier_reading($row['cashier_id'],$_POST['date_']);
//$credit=$credit_read[$row['cashier_id']];
//$debit=$debit_read[$row['cashier_id']];
//$my_name=$myname[$row['uid']];
$mytotal_reading=$myremitotal_reading[$row['uid']];
$mycash=$myremitcash[$row['uid']];
$mybpidebit=$myremitbpidebit[$row['uid']];
$mybpicredit=$myremitbpicredit[$row['uid']];
$mymetrodebit=$myremitmetrodebit[$row['uid']];
$mymetrocredit=$myremitmetrocredit[$row['uid']];
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
$myrec_c=$myremitrec_c[$row['uid']];
$myrec_s=$myremitrec_s[$row['uid']];
$mycwtax=$myremitcwtax[$row['uid']];
$myothers=$myremitothers[$row['uid']];
$myid=$myremitid[$row['uid']];

$diff1 = $row['total']- $mycash - $mysrsgc -$mygc - $myterms - $myevoucher-$myricepromo- $myddkita- $mysuki - $mycheck - $mybpidebit- $mybpicredit - $mymetrodebit- $mymetrocredit - $myatd - $myst - $myrec - $myrec_c - $myrec_s - $mycwtax -$myothers;
		
		$diff1 = $diff1 * -1;
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['c_name'],$format_bold);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['total'], $format_accounting);
		$x++;
		//$rep->sheet->writeNumber($rep->y, $x, $row['total_reading'], $format_accounting);
		//$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mycash, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mysrsgc, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mygc, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myterms, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myevoucher, $format_accounting);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x, $myricepromo, $format_accounting);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x, $myddkita, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mysuki, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mycheck, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mybpidebit, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mybpicredit, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mymetrodebit, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mymetrocredit, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myatd, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myst, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrec, $format_accounting);
		$x++;		
		$rep->sheet->writeNumber($rep->y, $x, $myrec_c, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myrec_s, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mycwtax, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $myothers, $format_accounting);
		$x++;
		
if($diff1 >= 0)
{
$rep->sheet->writeNumber($rep->y, $x, $diff1, $format_over_short);
$x++;
$rep->sheet->writeString($rep->y, $x, '', $format_left);
$x++;
$rep->sheet->writeNumber($rep->y, $x, $diff1, $format_over_short);
$x++;
$over1+=$diff1;
}
else  //short
{
$rep->sheet->writeNumber($rep->y, $x,$diff1, $format_over_short);
$x++;
$rep->sheet->writeNumber($rep->y, $x, $diff1, $format_over_short);
$x++;
$rep->sheet->writeString($rep->y, $x, '', $format_left);
$x++;
$short1+=($diff1);
}
$rep->y++;	

$over1=abs($over1);


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
$total_t_bpi_dc+=$mybpidebit;
$total_t_bpi_cc+=$mybpicredit;
$total_t_metro_dc+=$mymetrodebit;
$total_t_metro_cc+=$mymetrocredit;
$total_atd+=$myatd;
$total_st+=$myst;
$total_rec+=$myrec;
$total_rec_c+=$myrec_c;
$total_rec_s+=$myrec_s;
$total_cw_tax+=$mycwtax;
$total_o+=$myothers;
$total_discount+=$sc_disc[$row['cashier_id']];
$total_diff1=$simpos_sales+abs($over1)-abs($short1);
if ($total_diff1>$simpos_sales){
$final_diff1=$total_diff1-$simpos_sales;
}
else {
$final_diff1=$simpos_sales-$total_diff1;
}
}
	
	$x=1;
	$rep->sheet->writeString($rep->y, $x, 'RETAIL TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $simpos_sales, $format_accounting);
	$x++;
	//$rep->sheet->writeNumber($rep->y, $x, $total_remittance, $format_accounting);
	//$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_cash, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_srsgc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_gc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_terms, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_evoucher, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_ricepromo, $format_accounting);
	$x++;	
	$rep->sheet->writeNumber($rep->y, $x, $total_ddkita, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_sc, $format_accounting);
	$x++;	
	$rep->sheet->writeNumber($rep->y, $x, $total_t_check, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_bpi_dc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_bpi_cc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_metro_dc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_metro_cc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_atd, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_st, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_rec, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_rec_c, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_rec_s, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_cw_tax, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_o, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $final_diff1, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, -$short1, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($over1), $format_accounting);
	$rep->y++;
	
	
$user=array();
$sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
FROM MarkUsers as u
LEFT JOIN FinishedTransaction as ft
ON u.userid = ft.UserID
WHERE LogDate = '".date2sql($_POST['date_'])."'
AND Voided='0'
$wholesale_sql3_wholesale
GROUP BY u.userid,u.name
ORDER BY u.name ASC";
$res2=ms_db_query($sql);
//display_error($sql);
													
while($row2 = mssql_fetch_array($res2))
{
$user[]=$row2['uid'];
}					

//print_r($user);

$remit_user=array();

$sql = "SELECT DISTINCT cashier_id
FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date = '".date2sql($_POST['date_'])."'
and cashier_type=2
AND is_disapproved = 0
AND treasurer_id != 0
";
//display_error($sql);
$res=db_query_rs($sql);
$myst = array();
while($row = mysql_fetch_array($res))
{
$remit_user[]=$row['cashier_id'];
}
//print_r($remit_user);

//print_r($remit_user);

$user_in_ws=array_merge($user,$remit_user);	
//display_error($user_in_ws);
$user_sql_ws1 =" WHERE  u.userid IN(".implode(',',$user_in_ws).")";
$user_sql_ws2 =" AND  u.userid IN(".implode(',',$user_in_ws).")";
//display_error($user_sql_ws1);
//display_error($user_sql_ws2);



// $sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
// FROM MarkUsers as u
// LEFT JOIN FinishedTransaction as ft
// ON u.userid = ft.UserID
// WHERE LogDate = '".date2sql($_POST['date_'])."'
// AND Voided='0'
// $user_sql_ws
// GROUP BY u.userid,u.name
// ORDER BY u.name ASC";


$sql="select uid,c_name,total from
(SELECT u.userid as uid, u.name as c_name  FROM MarkUsers as u
$user_sql_ws1) as a
left join
(SELECT u.userid as uid2, u.name as c_name2, 
(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total 
FROM MarkUsers as u LEFT JOIN 
FinishedTransaction as ft ON u.userid = ft.UserID 
$wholesale_sql3_wholesale
WHERE LogDate = '".date2sql($_POST['date_'])."' AND Voided='0' $user_sql_ws2
GROUP BY u.userid,u.name) as b
on a.uid=b.uid2";
//display_error($sql."2222222222222222");
$res2=ms_db_query($sql);

				

															//display_error($sql);
															//$res2=ms_db_query($sql);
															$num_of_cashier = mssql_num_rows($res2);
																
																//$myname2=mysql_cashier_name2($_POST['date_'],$wholesale_sql2);
																$myremitotal_reading2=mysql_total_reading2($_POST['date_'],$wholesale_sql2);
																$myremitcash2=mysql_cash2($_POST['date_'],$wholesale_sql2);
															
																$myremitbpidebit2=mysql_bpi_debit2($_POST['date_'],$wholesale_sql2);
																$myremitbpicredit2=mysql_bpi_credit2($_POST['date_'],$wholesale_sql2);
																$myremitmetrodebit2=mysql_metro_debit2($_POST['date_'],$wholesale_sql2);
																$myremitmetrocredit2=mysql_metro_credit2($_POST['date_'],$wholesale_sql2);

																// $myremitcredit2=mysql_credit2($_POST['date_'],$wholesale_sql2);
																// $myremitdebit2=mysql_debit2($_POST['date_'],$wholesale_sql2);
																
																$myremitsuki2=mysql_suki2($_POST['date_'],$wholesale_sql2);
																$myremitcheck2=mysql_check2($_POST['date_'],$wholesale_sql2);
																$myremitsrsgc2=mysql_srsgc2($_POST['date_'],$wholesale_sql2);
																$myremitgc2=mysql_gc2($_POST['date_'],$wholesale_sql2);
																$myremitterms2=mysql_terms2($_POST['date_'],$wholesale_sql2);
																$myremitevoucher2=mysql_evoucher2($_POST['date_'],$wholesale_sql2);
																$myremitricepromo2=mysql_rice_promo2($_POST['date_'],$wholesale_sql2);
																$myremitddkita2=mysql_ddkita2($_POST['date_'],$wholesale_sql2);
																$myremitatd2=mysql_atd2($_POST['date_'],$wholesale_sql2);
																$myremitst2=mysql_st2($_POST['date_'],$wholesale_sql2);
																$myremitrec2=mysql_receivable2($_POST['date_'],$wholesale_sql2);
																$myremitrec2_c=mysql_receivable2_c($_POST['date_'],$wholesale_sql2_c);
																$myremitrec2_s=mysql_receivable2_s($_POST['date_'],$wholesale_sql2_s);
																$myremitcwtax2=mysql_cw_tax2($_POST['date_'],$wholesale_sql2);
																$myremitothers2=mysql_others2($_POST['date_'],$wholesale_sql2);				
																$myremitid2=mysql_r_id2($_POST['date_'],$wholesale_sql2);
																
																$c=0;
																while($row2 = mssql_fetch_array($res2))
																{
																$c ++;
																alt_table_row_color($k);
																//$my_name2=$myname2[$row2['uid']];
																$mytotal_reading2=$myremitotal_reading2[$row2['uid']];
																$mycash2=$myremitcash2[$row2['uid']];
																
																$mybpidebit2=$myremitbpidebit2[$row2['uid']];
																$mybpicredit2=$myremitbpicredit2[$row2['uid']];																
																$mymetrodebit2=$myremitmetrodebit2[$row2['uid']];
																$mymetrocredit2=$myremitmetrocredit2[$row2['uid']];
																
																$mysuki2=$myremitsuki2[$row2['uid']];
																$mycheck2=$myremitcheck2[$row2['uid']];
																$mysrsgc2=$myremitsrsgc2[$row2['uid']];
																$mygc2=$myremitgc2[$row2['uid']];
																$myterms2=$myremitterms2[$row2['uid']];
																$myevoucher2=$myremitevoucher2[$row2['uid']];
																
																$myricepromo2=$myremitricepromo2[$row2['uid']];
																
																$myddkita2=$myremitddkita2[$row2['uid']];
																$myatd2=$myremitatd2[$row2['uid']];
																$myst2=$myremitst2[$row2['uid']];
																$myrec2=$myremitrec2[$row2['uid']];
																$myrec2_c=$myremitrec2_c[$row2['uid']];
																$myrec2_s=$myremitrec2_s[$row2['uid']];
																$mycwtax2=$myremitcwtax2[$row2['uid']];
																$myothers2=$myremitothers2[$row2['uid']];
																$myid2=$myremitid2[$row2['uid']];
																
																// list($mycash2, $mysrsgc2, $mygc2, $myterms2, $myevoucher2, 
																// $mysuki2, $mycheck2, $mydebit2, $mycredit2, $myatd2, $myst2, $myrec2,$myothers2) 
																// = wh_total_remittance($_POST['date_'],$wholesale_sql2);
																					
																$diff2 = $row2['total']- $mycash2 - $mysrsgc2 -$mygc2 - $myterms2 - $myevoucher2-$myricepromo2- $myddkita2- $mysuki2 - $mycheck2 - $mybpidebit2 - $mybpicredit2 - $mymetrodebit2- $mymetrocredit2 -$myatd2 - $myst2 - $myrec2 - $myrec2_c - $myrec2_s - $mycwtax2 -$myothers2;
																	//-$myothers
																	$x=0;
																	$rep->y++;
																	$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
																	$x++;
																	$rep->sheet->writeString($rep->y, $x, $row2['c_name'],$format_bold);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $row2['total'], $format_accounting);
																	$x++;
																	//$rep->sheet->writeNumber($rep->y, $x, $mytotal_reading, $format_accounting);
																	//$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mycash2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mysrsgc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mygc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myterms2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myevoucher2, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $myricepromo2, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $myddkita2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mysuki2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mycheck2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mybpidebit2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mybpicredit2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mymetrodebit2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mymetrocredit2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myatd2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myst2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myrec2, $format_accounting);
																	$x++;		
																	$rep->sheet->writeNumber($rep->y, $x, $myrec2_c, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myrec2_s, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $mycwtax2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myothers2, $format_accounting);
																	$x++;

																	 if ($mytotal_reading<=0 or $mytotal_reading=='')
																	{
																	$over2=0;
																	$diff2=0;
																	}
																	 
																if($diff2 <= 0)
																	 {
																	$rep->sheet->writeNumber($rep->y, $x, abs($diff2), $format_over_short);
																	$x++;
																	$rep->sheet->writeString($rep->y, $x, '', $format_left);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, abs($diff2), $format_over_short);
																	$x++;
																	// $over2+=abs($diff2);		
																	 }
																	 else
																	{
																	$rep->sheet->writeNumber($rep->y, $x,abs($diff2), $format_over_short);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, -$diff2, $format_over_short);
																	$x++;
																	$rep->sheet->writeString($rep->y, $x, '', $format_left);
																	$x++;
																	//$short2+=abs($diff2);
																}

																	$simpos_sales2+=$row2['total'];
																	$total_remittance2+=$mytotal_reading2;
																	$total_cash2+=$mycash2;
																	$total_srsgc2+=$mysrsgc2;
																	$total_gc2+=$mygc2;
																	$total_terms2+=$myterms2;
																	$total_evoucher2+=$myevoucher2;
																	$total_ricepromo2+=$myricepromo2;
																	$total_ddkita2+=$myddkita2;
																	$total_t_sc2+=$mysuki2;
																	$total_t_check2+=$mycheck2;
																	$total_t_bpi_dc2+=$mybpidebit2;
																	$total_t_bpi_cc2+=$mybpicredit2;																
																	$total_t_metro_dc2+=$mymetrodebit2;
																	$total_t_metro_cc2+=$mymetrocredit2;
																	$total_atd2+=$myatd2;
																	$total_st2+=$myst2;
																	$total_rec2+=$myrec2;
																	$total_rec2_c+=$myrec2_c;
																	$total_rec2_s+=$myrec2_s;
																	$total_cw_tax2+=$mycwtax2;
																	$total_o2+=$myothers2;
																	$total_discount2+=$sc_disc2[$row2['cashier_id']];
																	}
																	
																	$x=1;
																	$rep->y++;
																	$rep->sheet->writeString($rep->y, $x, 'WHOLESALE TOTAL:', $format_bold);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $simpos_sales2, $format_accounting);
																	$x++;
																	//$rep->sheet->writeNumber($rep->y, $x, $total_remittance2, $format_accounting);
																	//$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_cash2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_srsgc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_gc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_terms2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_evoucher2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_ricepromo2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_ddkita2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_sc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_check2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_bpi_dc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_bpi_cc2, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_metro_dc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_metro_cc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_atd2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_st2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_rec2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_rec2_c, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_rec2_s, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_cw_tax2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_o2, $format_accounting);
																	$x++;
			
																	$total_diff2=$simpos_sales2-($total_cash2+$total_srsgc2+$total_gc2+$total_terms2+$total_evoucher2+$total_ricepromo2+$total_ddkita2+$total_t_sc2+$total_t_check2+$total_t_bpi_dc2+$total_t_bpi_cc2+$total_t_metro_dc2+$total_t_metro_cc2+$total_atd2+$total_st2+$total_rec2+$total_rec2_c+$total_rec2_s+$total_cw_tax2+$total_o2);

															if($total_diff2 <= 0)
															{
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff2), $format_accounting);
															$x++;
															$rep->sheet->writeString($rep->y, $x, '', $format_left);
															$x++;
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff2), $format_accounting);
															$x++;
															$t_over2=abs($over2)+abs($total_diff2);
															$t_short2=abs($short2);
															}
															else
															{
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff2), $format_accounting);
															$x++;
															$rep->sheet->writeNumber($rep->y, $x, $total_diff2, $format_accounting);
															$x++;
															$rep->sheet->writeString($rep->y, $x, '', $format_left);
															$x++;
															$t_short2=abs($short2)+abs($total_diff2);
															$t_over2=abs($over2);
															}	
																$rep->y++;
															
																	
																	
$user1=array();
			
$sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
FROM MarkUsers as u
LEFT JOIN FinishedTransaction as ft
ON u.userid = ft.UserID
WHERE LogDate = '".date2sql($_POST['date_'])."'
AND Voided='0'
$wholesale_sql_spc_ctr
GROUP BY u.userid,u.name
ORDER BY u.name ASC";
//display_error($sql);
$res2=ms_db_query($sql);
													
while($row2 = mssql_fetch_array($res2))
{
$user1[]=$row2['uid'];
}					



$remit_user1=array();
$sql = "SELECT DISTINCT cashier_id
FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date = '".date2sql($_POST['date_'])."'
and cashier_type=3
AND is_disapproved = 0
AND treasurer_id != 0
";
//display_error($sql);
$res=db_query_rs($sql);
$myst = array();
while($row = mysql_fetch_array($res))
{
$remit_user1[]=$row['cashier_id'];
}


//print_r($user1);

if($remit_user1!=0){
$user_in_spc_ctr=array_merge($user1,$remit_user1);	
}
else{
	$user_in_spc_ctr=$user1;	
}


$user_in_spc_ctr1 =" WHERE  u.userid IN(".implode(',',$user_in_spc_ctr).")";
$user_in_spc_ctr2 =" AND  u.userid IN(".implode(',',$user_in_spc_ctr).")";
//display_error($user_in_spc_ctr);



// $sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
// FROM MarkUsers as u
// LEFT JOIN FinishedTransaction as ft
// ON u.userid = ft.UserID
// WHERE LogDate = '".date2sql($_POST['date_'])."'
// AND Voided='0'
// $user_sql_ws
// GROUP BY u.userid,u.name
// ORDER BY u.name ASC";


$sql="select uid,c_name,total from
(SELECT u.userid as uid, u.name as c_name  FROM MarkUsers as u
$user_in_spc_ctr1) as a
left join
(SELECT u.userid as uid2, u.name as c_name2, 
(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total 
FROM MarkUsers as u LEFT JOIN 
FinishedTransaction as ft ON u.userid = ft.UserID 
$wholesale_sql_spc_ctr
WHERE LogDate = '".date2sql($_POST['date_'])."' AND Voided='0' $user_in_spc_ctr2
GROUP BY u.userid,u.name) as b
on a.uid=b.uid2";
$res3=ms_db_query($sql);
															
//display_error($sql."333333333333333");
															//$res3=ms_db_query($sql);
															
															$num_of_cashier = mssql_num_rows($res3);
																
																//$myname3=mysql_cashier_name3($_POST['date_'],$wholesale_sql3);
																$myremitotal_reading3=mysql_total_reading3($_POST['date_'],$wholesale_sql3);
																$myremitcash3=mysql_cash3($_POST['date_'],$wholesale_sql3);
															
																$myremitbpidebit3=mysql_bpi_debit3($_POST['date_'],$wholesale_sql3);
																$myremitbpicredit3=mysql_bpi_credit3($_POST['date_'],$wholesale_sql3);
																$myremitmetrodebit3=mysql_metro_debit3($_POST['date_'],$wholesale_sql3);
																$myremitmetrocredit3=mysql_metro_credit3($_POST['date_'],$wholesale_sql3);

																// $myremitcredit3=mysql_credit3($_POST['date_'],$wholesale_sql3);
																// $myremitdebit3=mysql_debit3($_POST['date_'],$wholesale_sql3);
																
																$myremitsuki3=mysql_suki3($_POST['date_'],$wholesale_sql3);
																$myremitcheck3=mysql_check3($_POST['date_'],$wholesale_sql3);
																$myremitsrsgc3=mysql_srsgc3($_POST['date_'],$wholesale_sql3);
																$myremitgc3=mysql_gc($_POST['date_'],$wholesale_sql3);
																$myremitterms3=mysql_terms3($_POST['date_'],$wholesale_sql3);
																$myremitevoucher3=mysql_evoucher3($_POST['date_'],$wholesale_sql3);
																$myremitricepromo3=mysql_rice_promo3($_POST['date_'],$wholesale_sql3);
																$myremitddkita3=mysql_ddkita3($_POST['date_'],$wholesale_sql3);
																$myremitatd3=mysql_atd3($_POST['date_'],$wholesale_sql3);
																$myremitst3=mysql_st3($_POST['date_'],$wholesale_sql3);
																$myremitrec3=mysql_receivable3($_POST['date_'],$wholesale_sql3);
																$myremitrec3_c=mysql_receivable3_c($_POST['date_'],$wholesale_sql3_c);
																$myremitrec3_s=mysql_receivable3_s($_POST['date_'],$wholesale_sql3_s);
																$myremitcwtax3=mysql_cw_tax3($_POST['date_'],$wholesale_sql3);
																$myremitothers3=mysql_others3($_POST['date_'],$wholesale_sql3);				
																$myremitid3=mysql_r_id3($_POST['date_'],$wholesale_sql3);
																
																$c=0;
																while($row3 = mssql_fetch_array($res3))
																{
																$c ++;
																alt_table_row_color($k);
																//$my_name3=$myname3[$row3['uid']];
																$mytotal_reading3=$myremitotal_reading3[$row3['uid']];
																$mycash3=$myremitcash3[$row3['uid']];
																
																$mybpidebit3=$myremitbpidebit3[$row3['uid']];
																$mybpicredit3=$myremitbpicredit3[$row3['uid']];																
																$mymetrodebit3=$myremitmetrodebit3[$row3['uid']];
																$mymetrocredit3=$myremitmetrocredit3[$row3['uid']];
																
																$mysuki3=$myremitsuki3[$row3['uid']];
																$mycheck3=$myremitcheck3[$row3['uid']];
																$mysrsgc3=$myremitsrsgc3[$row3['uid']];
																$mygc3=$myremitgc3[$row3['uid']];
																$myterms3=$myremitterms3[$row3['uid']];
																$myevoucher3=$myremitevoucher3[$row3['uid']];
																
																$myricepromo3=$myremitricepromo3[$row3['uid']];
																
																$myddkita3=$myremitddkita3[$row3['uid']];
																$myatd3=$myremitatd3[$row3['uid']];
																$myst3=$myremitst3[$row3['uid']];
																$myrec3=$myremitrec3[$row3['uid']];
																$myrec3_c=$myremitrec3_c[$row3['uid']];
																$myrec3_c=$myremitrec3_s[$row3['uid']];
																$mycwtax3=$myremitcwtax3[$row3['uid']];
																$myothers3=$myremitothers3[$row3['uid']];
																$myid3=$myremitid3[$row3['uid']];
																
																// list($mycash3, $mysrsgc3, $mygc3, $myterms3, $myevoucher3, 
																// $mysuki3, $mycheck3, $mydebit3, $mycredit3, $myatd3, $myst3, $myrec3,$myothers3) 
																// = wh_total_remittance($_POST['date_'],$wholesale_sql3);
																					
																$diff3 = $row3['total']- $mycash3 - $mysrsgc3 -$mygc3 - $myterms3 - $myevoucher3-$myricepromo3- $myddkita3- $mysuki3 - $mycheck3 - $mybpidebit3 - $mybpicredit3 - $mymetrodebit3- $mymetrocredit3 -$myatd3 - $myst3 - $myrec3 - $myrec3_c - $myrec3_s  - $mycwtax3 -$myothers3;
																	//-$myothers
																	$x=0;
																	$rep->y++;
																	$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
																	$x++;
																	$rep->sheet->writeString($rep->y, $x, $row3['c_name'],$format_bold);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $row3['total'], $format_accounting);
																	$x++;
																	//$rep->sheet->writeNumber($rep->y, $x, $mytotal_reading, $format_accounting);
																	//$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mycash3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mysrsgc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mygc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myterms3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myevoucher3, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $myricepromo3, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $myddkita3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mysuki3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mycheck3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mybpidebit3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mybpicredit3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mymetrodebit3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mymetrocredit3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myatd3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myst3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myrec3, $format_accounting);
																	$x++;			
																	$rep->sheet->writeNumber($rep->y, $x, $myrec3_c, $format_accounting);
																	$x++;	
																	$rep->sheet->writeNumber($rep->y, $x, $myrec3_s, $format_accounting);
																	$x++;																		
																	$rep->sheet->writeNumber($rep->y, $x, $mycwtax3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myothers3, $format_accounting);
																	$x++;

																	  if ($mytotal_reading<=0 or $mytotal_reading=='')
																	{
																	$over3=0;
																	$diff3=0;
																	}
																	 
																if($diff3 <= 0)
																	 {
																	$rep->sheet->writeNumber($rep->y, $x, abs($diff3), $format_over_short);
																	$x++;
																	$rep->sheet->writeString($rep->y, $x, '', $format_left);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, abs($diff3), $format_over_short);
																	$x++;
																	// $over3+=abs($diff3);		
																	 }
																	 else
																	{
																	$rep->sheet->writeNumber($rep->y, $x,abs($diff3), $format_over_short);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, -$diff3, $format_over_short);
																	$x++;
																	$rep->sheet->writeString($rep->y, $x, '', $format_left);
																	$x++;
																	//$short3+=abs($diff3);
																}

																	$simpos_sales3+=$row3['total'];
																	$total_remittance3+=$mytotal_reading3;
																	$total_cash3+=$mycash3;
																	$total_srsgc3+=$mysrsgc3;
																	$total_gc3+=$mygc3;
																	$total_terms3+=$myterms3;
																	$total_evoucher3+=$myevoucher3;
																	$total_ricepromo3+=$myricepromo3;
																	$total_ddkita3+=$myddkita3;
																	$total_t_sc3+=$mysuki3;
																	$total_t_check3+=$mycheck3;
																	$total_t_bpi_dc3+=$mybpidebit3;
																	$total_t_bpi_cc3+=$mybpicredit3;																
																	$total_t_metro_dc3+=$mymetrodebit3;
																	$total_t_metro_cc3+=$mymetrocredit3;
																	$total_atd3+=$myatd3;
																	$total_st3+=$myst3;
																	$total_rec3+=$myrec3;
																	$total_rec3_c+=$myrec3_c;
																	$total_rec3_s+=$myrec3_s;
																	$total_cw_tax3+=$mycwtax3;
																	$total_o3+=$myothers3;
																	$total_discount3+=$sc_disc3[$row3['cashier_id']];
																	}
																	
																	$x=1;
																	$rep->y++;
																	$rep->sheet->writeString($rep->y, $x, 'SPC CTR. TOTAL:', $format_bold);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $simpos_sales3, $format_accounting);
																	$x++;
																	//$rep->sheet->writeNumber($rep->y, $x, $total_remittance3, $format_accounting);
																	//$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_cash3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_srsgc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_gc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_terms3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_evoucher3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_ricepromo3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_ddkita3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_sc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_check3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_bpi_dc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_bpi_cc3, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_metro_dc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_metro_cc3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_atd3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_st3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_rec3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_rec3_c, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_rec3_s, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_cw_tax3, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_o3, $format_accounting);
																	$x++;
			
																	$total_diff3=$simpos_sales3-($total_cash3+$total_srsgc3+$total_gc3+$total_terms3+$total_evoucher3+$total_ricepromo3+$total_ddkita3+$total_t_sc3+$total_t_check3+$total_t_bpi_dc3+$total_t_bpi_cc3+$total_t_metro_dc3+$total_t_metro_cc3+$total_atd3+$total_st3+$total_rec3+$total_rec3_c+$total_rec3_s+$total_cw_tax3+$total_o3);

															if($total_diff3 <= 0)
															{
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff3), $format_accounting);
															$x++;
															$rep->sheet->writeString($rep->y, $x, '', $format_left);
															$x++;
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff3), $format_accounting);
															$x++;
															$t_over3=abs($over3)+abs($total_diff3);
															$t_short3=abs($short3);
															}
															else
															{
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff3), $format_accounting);
															$x++;
															$rep->sheet->writeNumber($rep->y, $x, $total_diff3, $format_accounting);
															$x++;
															$rep->sheet->writeString($rep->y, $x, '', $format_left);
															$x++;
															$t_short3=abs($short3)+abs($total_diff3);
															$t_over3=abs($over3);
															}	
																$rep->y++;
																
																	$t_simpos=$simpos_sales+$simpos_sales2+$simpos_sales3;
																	$t_remittance=$total_remittance+$total_remittance2+$total_remittance3;
																	$t_cash=$total_cash+$total_cash2+$total_cash3;
																	$t_srsgc=$total_srsgc+$total_srsgc2+$total_srsgc3;
																	$t_gc=$total_gc+$total_gc2+$total_gc3;
																	$t_terms=$total_terms+$total_terms2+$total_terms3;
																	$t_evoucher=$total_evoucher+$total_evoucher2+$total_evoucher3;
																	$t_ricepromo=$total_ricepromo+$total_ricepromo2+$total_ricepromo3;
																	$t_ddkita=$total_ddkita+$total_ddkita2+$total_ddkita3;
																	$t_t_sc=$total_t_sc+$total_t_sc2+$total_t_sc3;
																	$t_t_check=$total_t_check+$total_t_check2+$total_t_check3;
																	$t_t_bpi_dc=$total_t_bpi_dc+$total_t_bpi_dc2+$total_t_bpi_dc3;
																	$t_t_bpi_cc=$total_t_bpi_cc+$total_t_bpi_cc2+$total_t_bpi_cc3;																	
																	$t_t_metro_dc=$total_t_metro_dc+$total_t_metro_dc2+$total_t_metro_dc3;
																	$t_t_metro_cc=$total_t_metro_cc+$total_t_metro_cc2+$total_t_metro_cc3;
																	$t_atd=$total_atd+$total_atd2+$total_atd3;
																	$t_st=$total_st+$total_st2+$total_st3;
																	$t_rec=$total_rec+$total_rec2+$total_rec3;
																	$t_rec_c=$total_rec_c+$total_rec2_c+$total_rec3_c;
																	$t_rec_s=$total_rec_s+$total_rec2_s+$total_rec3_s;
																	$t_cwtax=$total_cw_tax+$total_cw_tax2+$total_cw_tax3;
																	$t_o=$total_o+$total_o2+$total_o3;
																	// $t_final_diff=$final_diff+abs($total_diff3);
																	$t_short=abs($short1)+abs($t_short2)+abs($t_short3);
																	$t_over=abs($over1)+abs($t_over2)+abs($t_over3);
																	$t_final_diff=$t_short-$t_over;
																	
																	$x=1;
																	$rep->y++;
																	$rep->sheet->writeString($rep->y, $x, 'GRAND TOTAL:', $format_bold);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_simpos, $format_accounting);
																	$x++;
																	//$rep->sheet->writeNumber($rep->y, $x, $t_remittance, $format_accounting);
																	//$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_cash, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_srsgc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_gc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_terms, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_evoucher, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_ricepromo, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_ddkita, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_sc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_check, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_bpi_dc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_bpi_cc, $format_accounting);
																	$x++;																	
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_metro_dc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_metro_cc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_atd, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_st, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_rec, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_rec_c, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_rec_s, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_cwtax, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_o, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, abs($t_final_diff), $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, -$t_short, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_over, $format_accounting);
																	$x++;
																	
																											
																	
	$rep->End();
}
//end of excel report------------------------------------------------------------------------------------
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
display_heading("Summary for ".$_POST['date_']);
br();
// echo "<center>Download as excel file</center>";
submit_center('dl_excel','Download as excel file');
br();
//start of table display
start_table($table_style2);
$th = array(' ', _("Cashier Name"), _("Reading"), 'Cash', 'SRSGC', 'GC', 'Terms', 'EVoucher', 'Rice Promo','DD-Kita','Suki Card', 'Check', 'BPI Debit','BPI Credit','Metro Debit','Metro Credit','ATD','ST','Rec.','Rec. from Cus','Rec. from Sup','CWTax','Others','Diff','Short','Over','');
table_header($th);
//---------------------------------------------------------------------------------

//START OF INCLUDE  WHOLESALE MS
$sqlwholesale3="select * from ".TB_PREF."wholesale_counter";
$wholesaleresult3=db_query($sqlwholesale3);
$ws_cashier3 = array();
while($ws_row3 = db_fetch($wholesaleresult3))
{
	$ws_cashier3[]=$ws_row3['counter'];
}
$wholesale_sql3_wholesale = '';

if (count($ws_cashier3) > 0)
	$wholesale_sql3_wholesale= "  AND ft.TerminalNo IN(".implode(',',$ws_cashier3).")";

//display_error($wholesale_sql3_wholesale);
//END OF INCLUDE  WHOLESALE MS


//-----//START OF INCLUDE  SPC CTR MS
$sqlwholesale5="select * from ".TB_PREF."special_counter";
$wholesaleresult5=db_query($sqlwholesale5);
$ws_cashier5 = array();
while($ws_row5 = db_fetch($wholesaleresult5))
{
	$ws_cashier5[]=$ws_row5['counter'];
}
$wholesale_sql_spc_ctr = '';
if (count($ws_cashier5) > 0)
	$wholesale_sql_spc_ctr= " AND  ft.TerminalNo IN(".implode(',',$ws_cashier5).")";
//END OF INCLUDE  SPC CTR MS

$wholesale_sql_retail='';
if (count($ws_cashier3) > 0 and count($ws_cashier5) > 0 ){
	$wholesale_sql_retail = " AND  ft.TerminalNo NOT IN(".implode(',',$ws_cashier3).") AND  ft.TerminalNo NOT IN(".implode(',',$ws_cashier5).")";
	$wholesale_sql = " AND  terminal_nos NOT IN(".implode(',',$ws_cashier3).") AND  terminal_nos NOT IN(".implode(',',$ws_cashier5).")";
	
}
	
//END OF INCLUDE  WHOLESALE MS

//------------------------------------------------------------------------------------------------------------------
$c = $k = 0;


$sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
FROM MarkUsers as u
LEFT JOIN FinishedTransaction as ft
ON u.userid = ft.UserID
WHERE LogDate = '".date2sql($_POST['date_'])."'
AND Voided='0'
$wholesale_sql_retail
GROUP BY u.userid,u.name
ORDER BY u.name ASC";
//display_error($sql."11111111111111111");
$res=ms_db_query($sql);
$num_of_cashier = mssql_num_rows($res);
//$myname=mysql_cashier_name($_POST['date_'],$wholesale_sql2);
$myremitotal_reading=mysql_total_reading($_POST['date_'],$wholesale_sql2);
$myremitcash=mysql_cash($_POST['date_'],$wholesale_sql2);
$myremitbpidebit=mysql_bpi_debit($_POST['date_'],$wholesale_sql2);
$myremitbpicredit=mysql_bpi_credit($_POST['date_'],$wholesale_sql2);
$myremitmetrodebit=mysql_metro_debit($_POST['date_'],$wholesale_sql2);
$myremitmetrocredit=mysql_metro_credit($_POST['date_'],$wholesale_sql2);
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
$myremitrec_c=mysql_receivable_c($_POST['date_'],$wholesale_sql2);
$myremitrec_s=mysql_receivable_s($_POST['date_'],$wholesale_sql2);
$myremitcwtax=mysql_cw_tax($_POST['date_'],$wholesale_sql2);
$myremitothers=mysql_others($_POST['date_'],$wholesale_sql2);
$myremitid=mysql_r_id($_POST['date_'],$wholesale_sql2);

$c=0;
while($row = mssql_fetch_array($res))
{
$c ++;
alt_table_row_color($k);
// $reading = get_cashier_reading($row['cashier_id'],$_POST['date_']);
//$credit=$credit_read[$row['cashier_id']];
//$debit=$debit_read[$row['cashier_id']];
//$my_name=$myname[$row['uid']];
$mytotal_reading=$myremitotal_reading[$row['uid']];
$mycash=$myremitcash[$row['uid']];
$mybpidebit=$myremitbpidebit[$row['uid']];
$mybpicredit=$myremitbpicredit[$row['uid']];
$mymetrodebit=$myremitmetrodebit[$row['uid']];
$mymetrocredit=$myremitmetrocredit[$row['uid']];
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
$myrec_c=$myremitrec_c[$row['uid']];
$myrec_s=$myremitrec_s[$row['uid']];
$mycwtax=$myremitcwtax[$row['uid']];
$myothers=$myremitothers[$row['uid']];
$myid=$myremitid[$row['uid']];

$diff1 = $row['total']- $mycash - $mysrsgc -$mygc - $myterms - $myevoucher-$myricepromo- $myddkita- $mysuki - $mycheck - $mybpidebit- $mybpicredit - $mymetrodebit- $mymetrocredit - $myatd - $myst - $myrec - $myrec_c - $myrec_s - $mycwtax -$myothers;

label_cell($c,'align=right');
label_cell('<b>'.$row['c_name'].'</b>');
amount_cell($row['total']);
//amount_cell($mytotal_reading);
amount_cell($mycash);
amount_cell($mysrsgc);
amount_cell($mygc);
amount_cell($myterms);
amount_cell($myevoucher);
amount_cell($myricepromo);
amount_cell($myddkita);
amount_cell($mysuki);
amount_cell($mycheck);
amount_cell($mybpidebit);
amount_cell($mybpicredit);
amount_cell($mymetrodebit);
amount_cell($mymetrocredit);
amount_cell($myatd);
amount_cell($myst);
amount_cell($myrec);
amount_cell($myrec_c);
amount_cell($myrec_s);
amount_cell($mycwtax);		
amount_cell($myothers);	

if($diff1 <= 0){
amount_cell(abs($diff1),true);
label_cell('');
amount_cell(abs($diff1),true);
$over1+=$diff1;
}
else
{
label_cell("<font color=red><b>(".number_format2(abs($diff1),2).")<b></font>",'align=right');
amount_cell(abs($diff1),true);
label_cell('');
$short1+=($diff1);
}

label_cell(viewer_link('View', "sales/view/view_cashier_remittance.php?
Remittance_ID=".$myid."&final=1", '', '', ''),'align=center');
// label_cell(print_document_link($row['remittance_id'], _("Print"), true, 888));
end_row();

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
$total_t_bpi_dc+=$mybpidebit;
$total_t_bpi_cc+=$mybpicredit;
$total_t_metro_dc+=$mymetrodebit;
$total_t_metro_cc+=$mymetrocredit;
$total_atd+=$myatd;
$total_st+=$myst;
$total_rec+=$myrec;
$total_rec_c+=$myrec_c;
$total_rec_s+=$myrec_s;
$total_cw_tax+=$mycwtax;
$total_o+=$myothers;
$total_discount+=$sc_disc[$row['cashier_id']];
$total_diff1=$simpos_sales+abs($over1)-abs($short1);
if ($total_diff1>$simpos_sales){
$total_diff1=$total_diff1-$simpos_sales;
}
else {
$total_diff1=$simpos_sales-$total_diff1;
}
}
$over1=abs($over1);


label_cell();
label_cell('<font color=#880000><b>'.'RETAIL TOTAL:'.'</b></font>');
label_cell("<font color=#880000><b>".number_format2(abs($simpos_sales),2)."<b></font>",'align=right');
//label_cell("<font color=#880000><b>".number_format2(abs($total_remittance2),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_cash),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_srsgc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_gc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_terms),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_evoucher),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_ricepromo),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_ddkita),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_sc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_check),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_bpi_dc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_bpi_cc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_metro_dc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_metro_cc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_atd),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_st),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_rec),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_rec_c),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_rec_s),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_cw_tax),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_o),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2($total_diff1,2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(-$short1,2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($over1),2)."<b></font>",'align=right');
label_cell('');
end_row();	

									
$user=array();
$sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
FROM MarkUsers as u
LEFT JOIN FinishedTransaction as ft
ON u.userid = ft.UserID
WHERE LogDate = '".date2sql($_POST['date_'])."'
AND Voided='0'
$wholesale_sql3_wholesale
GROUP BY u.userid,u.name
ORDER BY u.name ASC";
$res2=ms_db_query($sql);
//display_error($sql);
													
while($row2 = mssql_fetch_array($res2))
{
$user[]=$row2['uid'];
}					

//print_r($user);

$remit_user=array();

$sql = "SELECT DISTINCT cashier_id
FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date = '".date2sql($_POST['date_'])."'
and cashier_type=2
AND is_disapproved = 0
AND treasurer_id != 0
";
//display_error($sql);
$res=db_query_rs($sql);
$myst = array();
while($row = mysql_fetch_array($res))
{
$remit_user[]=$row['cashier_id'];
}
//print_r($remit_user);

//print_r($remit_user);

$user_in_ws=array_merge($user,$remit_user);	
//display_error($user_in_ws);
$user_sql_ws1 =" WHERE  u.userid IN(".implode(',',$user_in_ws).")";
$user_sql_ws2 =" AND  u.userid IN(".implode(',',$user_in_ws).")";
//display_error($user_sql_ws1);
//display_error($user_sql_ws2);



// $sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
// FROM MarkUsers as u
// LEFT JOIN FinishedTransaction as ft
// ON u.userid = ft.UserID
// WHERE LogDate = '".date2sql($_POST['date_'])."'
// AND Voided='0'
// $user_sql_ws
// GROUP BY u.userid,u.name
// ORDER BY u.name ASC";


$sql="select uid,c_name,total from
(SELECT u.userid as uid, u.name as c_name  FROM MarkUsers as u
$user_sql_ws1) as a
left join
(SELECT u.userid as uid2, u.name as c_name2, 
(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total 
FROM MarkUsers as u LEFT JOIN 
FinishedTransaction as ft ON u.userid = ft.UserID 
$wholesale_sql3_wholesale
WHERE LogDate = '".date2sql($_POST['date_'])."' AND Voided='0' $user_sql_ws2
GROUP BY u.userid,u.name) as b
on a.uid=b.uid2";
//display_error($sql."2222222222222222");
$res2=ms_db_query($sql);

				

															//display_error($sql);
															//$res2=ms_db_query($sql);
															$num_of_cashier = mssql_num_rows($res2);
																
																//$myname2=mysql_cashier_name2($_POST['date_'],$wholesale_sql2);
																$myremitotal_reading2=mysql_total_reading2($_POST['date_'],$wholesale_sql2);
																$myremitcash2=mysql_cash2($_POST['date_'],$wholesale_sql2);
															
																$myremitbpidebit2=mysql_bpi_debit2($_POST['date_'],$wholesale_sql2);
																$myremitbpicredit2=mysql_bpi_credit2($_POST['date_'],$wholesale_sql2);
																$myremitmetrodebit2=mysql_metro_debit2($_POST['date_'],$wholesale_sql2);
																$myremitmetrocredit2=mysql_metro_credit2($_POST['date_'],$wholesale_sql2);

																// $myremitcredit2=mysql_credit2($_POST['date_'],$wholesale_sql2);
																// $myremitdebit2=mysql_debit2($_POST['date_'],$wholesale_sql2);
																
																$myremitsuki2=mysql_suki2($_POST['date_'],$wholesale_sql2);
																$myremitcheck2=mysql_check2($_POST['date_'],$wholesale_sql2);
																$myremitsrsgc2=mysql_srsgc2($_POST['date_'],$wholesale_sql2);
																$myremitgc2=mysql_gc2($_POST['date_'],$wholesale_sql2);
																$myremitterms2=mysql_terms2($_POST['date_'],$wholesale_sql2);
																$myremitevoucher2=mysql_evoucher2($_POST['date_'],$wholesale_sql2);
																$myremitricepromo2=mysql_rice_promo2($_POST['date_'],$wholesale_sql2);
																$myremitddkita2=mysql_ddkita2($_POST['date_'],$wholesale_sql2);
																$myremitatd2=mysql_atd2($_POST['date_'],$wholesale_sql2);
																$myremitst2=mysql_st2($_POST['date_'],$wholesale_sql2);
																$myremitrec2=mysql_receivable2($_POST['date_'],$wholesale_sql2);
																$myremitrec2_c=mysql_receivable2_c($_POST['date_'],$wholesale_sql2);
																$myremitrec2_s=mysql_receivable2_s($_POST['date_'],$wholesale_sql2);
																$myremitcwtax2=mysql_cw_tax2($_POST['date_'],$wholesale_sql2);
																$myremitothers2=mysql_others2($_POST['date_'],$wholesale_sql2);				
																$myremitid2=mysql_r_id2($_POST['date_'],$wholesale_sql2);
																
																$c=0;
																while($row2 = mssql_fetch_array($res2))
																{
																$c ++;
																alt_table_row_color($k);
																//$my_name2=$myname2[$row2['uid']];
																$mytotal_reading2=$myremitotal_reading2[$row2['uid']];
																$mycash2=$myremitcash2[$row2['uid']];
																
																$mybpidebit2=$myremitbpidebit2[$row2['uid']];
																$mybpicredit2=$myremitbpicredit2[$row2['uid']];																
																$mymetrodebit2=$myremitmetrodebit2[$row2['uid']];
																$mymetrocredit2=$myremitmetrocredit2[$row2['uid']];
																
																$mysuki2=$myremitsuki2[$row2['uid']];
																$mycheck2=$myremitcheck2[$row2['uid']];
																$mysrsgc2=$myremitsrsgc2[$row2['uid']];
																$mygc2=$myremitgc2[$row2['uid']];
																$myterms2=$myremitterms2[$row2['uid']];
																$myevoucher2=$myremitevoucher2[$row2['uid']];
																
																$myricepromo2=$myremitricepromo2[$row2['uid']];
																
																$myddkita2=$myremitddkita2[$row2['uid']];
																$myatd2=$myremitatd2[$row2['uid']];
																$myst2=$myremitst2[$row2['uid']];
																$myrec2=$myremitrec2[$row2['uid']];
																$myrec2_c=$myremitrec2_c[$row2['uid']];
																$myrec2_s=$myremitrec2_s[$row2['uid']];
																$mycwtax2=$myremitcwtax2[$row2['uid']];
																$myothers2=$myremitothers2[$row2['uid']];
																$myid2=$myremitid2[$row2['uid']];
																
																// list($mycash2, $mysrsgc2, $mygc2, $myterms2, $myevoucher2, 
																// $mysuki2, $mycheck2, $mydebit2, $mycredit2, $myatd2, $myst2, $myrec2,$myothers2) 
																// = wh_total_remittance($_POST['date_'],$wholesale_sql2);
																					
																$diff2 = $row2['total']- $mycash2 - $mysrsgc2 -$mygc2 - $myterms2 - $myevoucher2-$myricepromo2- $myddkita2- $mysuki2 - $mycheck2 - $mybpidebit2 - $mybpicredit2 - $mymetrodebit2- $mymetrocredit2 -$myatd2 - $myst2 - $myrec2 - $myrec2_c - $myrec2_s - $mycwtax2 -$myothers2;
																	label_cell($c,'align=right');
																	label_cell('<b>'.$row2['c_name'].'</b>');
																	amount_cell($row2['total']);
																	//amount_cell($mytotal_reading);
																	amount_cell($mycash2);
																	amount_cell($mysrsgc2);
																	amount_cell($mygc2);
																	amount_cell($myterms2);
																	amount_cell($myevoucher2);
																	amount_cell($myricepromo2);
																	amount_cell($myddkita2);
																	amount_cell($mysuki2);
																	amount_cell($mycheck2);
																	amount_cell($mybpidebit2);
																	amount_cell($mybpicredit2);																	
																	amount_cell($mymetrodebit2);
																	amount_cell($mymetrocredit2);
																	amount_cell($myatd2);
																	amount_cell($myst2);
																	amount_cell($myrec2);	
																	amount_cell($myrec2_c);	
																	amount_cell($myrec2_s);	
																	amount_cell($mycwtax2);	
																	amount_cell($myothers2);	
																
																	if ($mytotal_reading<=0 or $mytotal_reading=='')
																	{
																	$over2=0;
																	$diff2=0;
																	}
																	
																	 if($diff2 <= 0)
																	{
																	 label_cell("<b>".number_format2(abs($diff2),2)."<b>",'align=right',true);
																	 label_cell('');
																	 label_cell("<b>".number_format2(abs($diff2),2)."<b>",'align=right',true);
																	 //$over2+=$diff2;
																	}
																	 else
																	{
																	 label_cell("<font color=red><b>(".number_format2(abs($diff2),2).")<b></font>",'align=right',true);
																	 label_cell("<font color=red><b>(".number_format2(abs($diff2),2).")<b></font>",'align=right',true);
																	 label_cell('');
																	// $short2+=$diff2;
																	}

																	label_cell(viewer_link('View', "sales/view/view_cashier_remittance.php?
																		Remittance_ID=".$myid."&final=1", '', '', ''),'align=center');
																	// label_cell(print_document_link($row['remittance_id'], _("Print"), true, 888));
																	end_row();
																	
																	$simpos_sales2+=$row2['total'];
																	$total_remittance2+=$mytotal_reading2;
																	$total_cash2+=$mycash2;
																	$total_srsgc2+=$mysrsgc2;
																	$total_gc2+=$mygc2;
																	$total_terms2+=$myterms2;
																	$total_evoucher2+=$myevoucher2;
																	$total_ricepromo2+=$myricepromo2;
																	$total_ddkita2+=$myddkita2;
																	$total_t_sc2+=$mysuki2;
																	$total_t_check2+=$mycheck2;
																	$total_t_bpi_dc2+=$mybpidebit2;
																	$total_t_bpi_cc2+=$mybpicredit2;
																	$total_t_metro_dc2+=$mymetrodebit2;
																	$total_t_metro_cc2+=$mymetrocredit2;
																	$total_atd2+=$myatd2;
																	$total_st2+=$myst2;
																	$total_rec2+=$myrec2;
																	$total_rec2_c+=$myrec2_c;
																	$total_rec2_s+=$myrec2_s;
																	$total_cw_tax2+=$mycwtax2;
																	$total_o2+=$myothers2;
																	$total_discount2+=$sc_disc2[$row2['cashier_id']];
															}
																		
															label_cell();
															label_cell('<font color=#880000><b>'.'WHOLESALE TOTAL:'.'</b></font>');
															label_cell("<font color=#880000><b>".number_format2(abs($simpos_sales2),2)."<b></font>",'align=right');
															//label_cell("<font color=#880000><b>".number_format2(abs($total_remittance2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_cash2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_srsgc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_gc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_terms2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_evoucher2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_ricepromo2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_ddkita2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_sc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_check2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_bpi_dc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_bpi_cc2),2)."<b></font>",'align=right');															
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_metro_dc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_metro_cc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_atd2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_st2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_rec2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_rec2_c),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_rec2_s),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_cw_tax2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_o2),2)."<b></font>",'align=right');
															
															$total_diff2=$simpos_sales2-($total_cash2+$total_srsgc2+$total_gc2+$total_terms2+$total_evoucher2+$total_ricepromo2+$total_ddkita2+$total_t_sc2+$total_t_check2+$total_t_bpi_dc2+$total_t_bpi_cc2+$total_t_metro_dc2+$total_t_metro_cc2+$total_atd2+$total_st2+$total_rec2+$total_rec2_c+$total_rec2_s+$total_cw_tax2+$total_o2);

															if($total_diff2 <= 0){
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff2),2)."<b></font>",'align=right');
															$t_over2=abs($over2)+abs($total_diff2);
															$t_short2=abs($short2);
															}
															else
															{
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff2),2)."<b></font>",'align=right'); 
															label_cell("<font color=#880000><b>".number_format2(-$total_diff2,2)."<b></font>",'align=right'); 
															label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
															$t_short2=abs($short2)+abs($total_diff2);
															$t_over2=abs($over2);	
															}
															label_cell('');
															end_row();

													
													
													
$user1=array();
			
$sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
FROM MarkUsers as u
LEFT JOIN FinishedTransaction as ft
ON u.userid = ft.UserID
WHERE LogDate = '".date2sql($_POST['date_'])."'
AND Voided='0'
$wholesale_sql_spc_ctr
GROUP BY u.userid,u.name
ORDER BY u.name ASC";
//display_error($sql);
$res2=ms_db_query($sql);
													
while($row2 = mssql_fetch_array($res2))
{
$user1[]=$row2['uid'];
}					



$remit_user1=array();
$sql = "SELECT DISTINCT cashier_id
FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date = '".date2sql($_POST['date_'])."'
and cashier_type=3
AND is_disapproved = 0
AND treasurer_id != 0
";
//display_error($sql);
$res=db_query_rs($sql);
$myst = array();
while($row = mysql_fetch_array($res))
{
$remit_user1[]=$row['cashier_id'];
}


//print_r($user1);

if($remit_user1!=0){
$user_in_spc_ctr=array_merge($user1,$remit_user1);	
}
else{
	$user_in_spc_ctr=$user1;	
}


$user_in_spc_ctr1 =" WHERE  u.userid IN(".implode(',',$user_in_spc_ctr).")";
$user_in_spc_ctr2 =" AND  u.userid IN(".implode(',',$user_in_spc_ctr).")";
//display_error($user_in_spc_ctr);



// $sql="SELECT u.userid as uid, u.name as c_name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
// FROM MarkUsers as u
// LEFT JOIN FinishedTransaction as ft
// ON u.userid = ft.UserID
// WHERE LogDate = '".date2sql($_POST['date_'])."'
// AND Voided='0'
// $user_sql_ws
// GROUP BY u.userid,u.name
// ORDER BY u.name ASC";


$sql="select uid,c_name,total from
(SELECT u.userid as uid, u.name as c_name  FROM MarkUsers as u
$user_in_spc_ctr1) as a
left join
(SELECT u.userid as uid2, u.name as c_name2, 
(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total 
FROM MarkUsers as u LEFT JOIN 
FinishedTransaction as ft ON u.userid = ft.UserID 
$wholesale_sql_spc_ctr
WHERE LogDate = '".date2sql($_POST['date_'])."' AND Voided='0' $user_in_spc_ctr2
GROUP BY u.userid,u.name) as b
on a.uid=b.uid2";
$res3=ms_db_query($sql);
															
//display_error($sql."333333333333333");
															//$res3=ms_db_query($sql);
															
															$num_of_cashier = mssql_num_rows($res3);
																
																//$myname3=mysql_cashier_name3($_POST['date_'],$wholesale_sql3);
																$myremitotal_reading3=mysql_total_reading3($_POST['date_'],$wholesale_sql3);
																$myremitcash3=mysql_cash3($_POST['date_'],$wholesale_sql3);
															
																$myremitbpidebit3=mysql_bpi_debit3($_POST['date_'],$wholesale_sql3);
																$myremitbpicredit3=mysql_bpi_credit3($_POST['date_'],$wholesale_sql3);
																$myremitmetrodebit3=mysql_metro_debit3($_POST['date_'],$wholesale_sql3);
																$myremitmetrocredit3=mysql_metro_credit3($_POST['date_'],$wholesale_sql3);

																// $myremitcredit3=mysql_credit3($_POST['date_'],$wholesale_sql3);
																// $myremitdebit3=mysql_debit3($_POST['date_'],$wholesale_sql3);
																
																$myremitsuki3=mysql_suki3($_POST['date_'],$wholesale_sql3);
																$myremitcheck3=mysql_check3($_POST['date_'],$wholesale_sql3);
																$myremitsrsgc3=mysql_srsgc3($_POST['date_'],$wholesale_sql3);
																$myremitgc3=mysql_gc($_POST['date_'],$wholesale_sql3);
																$myremitterms3=mysql_terms3($_POST['date_'],$wholesale_sql3);
																$myremitevoucher3=mysql_evoucher3($_POST['date_'],$wholesale_sql3);
																$myremitricepromo3=mysql_rice_promo3($_POST['date_'],$wholesale_sql3);
																$myremitddkita3=mysql_ddkita3($_POST['date_'],$wholesale_sql3);
																$myremitatd3=mysql_atd3($_POST['date_'],$wholesale_sql3);
																$myremitst3=mysql_st3($_POST['date_'],$wholesale_sql3);
																$myremitrec3=mysql_receivable3($_POST['date_'],$wholesale_sql3);
																$myremitrec3_c=mysql_receivable3_c($_POST['date_'],$wholesale_sql3);
																$myremitrec3_s=mysql_receivable3_s($_POST['date_'],$wholesale_sql3);
																$myremitcwtax3=mysql_cw_tax3($_POST['date_'],$wholesale_sql3);
																$myremitothers3=mysql_others3($_POST['date_'],$wholesale_sql3);				
																$myremitid3=mysql_r_id3($_POST['date_'],$wholesale_sql3);
																
																$c=0;
																while($row3 = mssql_fetch_array($res3))
																{
																$c ++;
																alt_table_row_color($k);
																//$my_name3=$myname3[$row3['uid']];
																$mytotal_reading3=$myremitotal_reading3[$row3['uid']];
																$mycash3=$myremitcash3[$row3['uid']];
																
																$mybpidebit3=$myremitbpidebit3[$row3['uid']];
																$mybpicredit3=$myremitbpicredit3[$row3['uid']];																
																$mymetrodebit3=$myremitmetrodebit3[$row3['uid']];
																$mymetrocredit3=$myremitmetrocredit3[$row3['uid']];
																
																$mysuki3=$myremitsuki3[$row3['uid']];
																$mycheck3=$myremitcheck3[$row3['uid']];
																$mysrsgc3=$myremitsrsgc3[$row3['uid']];
																$mygc3=$myremitgc3[$row3['uid']];
																$myterms3=$myremitterms3[$row3['uid']];
																$myevoucher3=$myremitevoucher3[$row3['uid']];
																
																$myricepromo3=$myremitricepromo3[$row3['uid']];
																
																$myddkita3=$myremitddkita3[$row3['uid']];
																$myatd3=$myremitatd3[$row3['uid']];
																$myst3=$myremitst3[$row3['uid']];
																$myrec3=$myremitrec3[$row3['uid']];
																$myrec3_c=$myremitrec3_c[$row3['uid']];
																$myrec3_s=$myremitrec3_s[$row3['uid']];
																$mycwtax3=$myremitcwtax3[$row3['uid']];
																$myothers3=$myremitothers3[$row3['uid']];
																$myid3=$myremitid3[$row3['uid']];
																
																// list($mycash3, $mysrsgc3, $mygc3, $myterms3, $myevoucher3, 
																// $mysuki3, $mycheck3, $mydebit3, $mycredit3, $myatd3, $myst3, $myrec3,$myothers3) 
																// = wh_total_remittance($_POST['date_'],$wholesale_sql3);
																					
																$diff3 = $row3['total']- $mycash3 - $mysrsgc3 -$mygc3 - $myterms3 - $myevoucher3-$myricepromo3- $myddkita3- $mysuki3 - $mycheck3 - $mybpidebit3 - $mybpicredit3 - $mymetrodebit3- $mymetrocredit3 -$myatd3 - $myst3 - $myrec3 - $myrec3_c - $myrec3_s - $mycwtax3 -$myothers3;
																	label_cell($c,'align=right');
																	label_cell('<b>'.$row3['c_name'].'</b>');
																	amount_cell($row3['total']);
																	//amount_cell($mytotal_reading);
																	amount_cell($mycash3);
																	amount_cell($mysrsgc3);
																	amount_cell($mygc3);
																	amount_cell($myterms3);
																	amount_cell($myevoucher3);
																	amount_cell($myricepromo3);
																	amount_cell($myddkita3);
																	amount_cell($mysuki3);
																	amount_cell($mycheck3);
																	amount_cell($mybpidebit3);
																	amount_cell($mybpicredit3);																	
																	amount_cell($mymetrodebit3);
																	amount_cell($mymetrocredit3);
																	amount_cell($myatd3);
																	amount_cell($myst3);
																	amount_cell($myrec3);	
																	amount_cell($myrec3_c);	
																	amount_cell($myrec3_s);	
																	amount_cell($mycwtax3);	
																	amount_cell($myothers3);	
																
																	if ($mytotal_reading<=0 or $mytotal_reading=='')
																	{
																	$over3=0;
																	$diff3=0;
																	}
																	
																	 if($diff3 <= 0)
																	{
																	 label_cell("<b>".number_format2(abs($diff3),2)."<b>",'align=right',true);
																	 label_cell('');
																	 label_cell("<b>".number_format2(abs($diff3),2)."<b>",'align=right',true);
																	 //$over3+=$diff3;
																	}
																	 else
																	{
																	 label_cell("<font color=red><b>(".number_format2(abs($diff3),2).")<b></font>",'align=right',true);
																	 label_cell("<font color=red><b>(".number_format2(abs($diff3),2).")<b></font>",'align=right',true);
																	 label_cell('');
																	// $short3+=$diff3;
																	}

																	label_cell(viewer_link('View', "sales/view/view_cashier_remittance.php?
																		Remittance_ID=".$myid."&final=1", '', '', ''),'align=center');
																	// label_cell(print_document_link($row['remittance_id'], _("Print"), true, 888));
																	end_row();
																	
																	$simpos_sales3+=$row3['total'];
																	$total_remittance3+=$mytotal_reading3;
																	$total_cash3+=$mycash3;
																	$total_srsgc3+=$mysrsgc3;
																	$total_gc3+=$mygc3;
																	$total_terms3+=$myterms3;
																	$total_evoucher3+=$myevoucher3;
																	$total_ricepromo3+=$myricepromo3;
																	$total_ddkita3+=$myddkita3;
																	$total_t_sc3+=$mysuki3;
																	$total_t_check3+=$mycheck3;
																	$total_t_bpi_dc3+=$mybpidebit3;
																	$total_t_bpi_cc3+=$mybpicredit3;
																	$total_t_metro_dc3+=$mymetrodebit3;
																	$total_t_metro_cc3+=$mymetrocredit3;
																	$total_atd3+=$myatd3;
																	$total_st3+=$myst3;
																	$total_rec3+=$myrec3;
																	$total_rec3_c+=$myrec3_c;
																	$total_rec3_s+=$myrec3_s;
																	$total_cw_tax3+=$mycwtax3;
																	$total_o3+=$myothers3;
																	$total_discount3+=$sc_disc3[$row3['cashier_id']];
															}
																		
															label_cell();
															label_cell('<font color=#880000><b>'.'SPC CTR. TOTAL:'.'</b></font>');
															label_cell("<font color=#880000><b>".number_format2(abs($simpos_sales3),2)."<b></font>",'align=right');
															//label_cell("<font color=#880000><b>".number_format2(abs($total_remittance3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_cash3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_srsgc3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_gc3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_terms3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_evoucher3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_ricepromo3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_ddkita3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_sc3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_check3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_bpi_dc3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_bpi_cc3),2)."<b></font>",'align=right');															
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_metro_dc3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_metro_cc3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_atd3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_st3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_rec3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_rec3_c),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_rec3_s),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_cw_tax3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_o3),2)."<b></font>",'align=right');
															
															$total_diff3=$simpos_sales3-($total_cash3+$total_srsgc3+$total_gc3+$total_terms3+$total_evoucher3+$total_ricepromo3+$total_ddkita3+$total_t_sc3+$total_t_check3+$total_t_bpi_dc3+$total_t_bpi_cc3+$total_t_metro_dc3+$total_t_metro_cc3+$total_atd3+$total_st3+$total_rec3+$total_rec3_c+$total_rec3_s+$total_cw_tax3+$total_o3);

															if($total_diff3 <= 0){
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff3),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff3),2)."<b></font>",'align=right');
															$t_over3=abs($over3)+abs($total_diff3);
															$t_short3=abs($short3);
															}
															else
															{
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff3),2)."<b></font>",'align=right'); 
															label_cell("<font color=#880000><b>".number_format2(-$total_diff3,2)."<b></font>",'align=right'); 
															label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
															$t_short3=abs($short3)+abs($total_diff3);
															$t_over3=abs($over3);	
															}
															label_cell('');
															end_row();
																	$t_simpos=$simpos_sales+$simpos_sales2+$simpos_sales3;
																	$t_remittance=$total_remittance+$total_remittance2+$total_remittance3;
																	$t_cash=$total_cash+$total_cash2+$total_cash3;
																	$t_srsgc=$total_srsgc+$total_srsgc2+$total_srsgc3;
																	$t_gc=$total_gc+$total_gc2+$total_gc3;
																	$t_terms=$total_terms+$total_terms2+$total_terms3;
																	$t_evoucher=$total_evoucher+$total_evoucher2+$total_evoucher3;
																	$t_ricepromo=$total_ricepromo+$total_ricepromo2+$total_ricepromo3;
																	$t_ddkita=$total_ddkita+$total_ddkita2+$total_ddkita3;
																	$t_t_sc=$total_t_sc+$total_t_sc2+$total_t_sc3;
																	$t_t_check=$total_t_check+$total_t_check2+$total_t_check3;
																	$t_t_bpi_dc=$total_t_bpi_dc+$total_t_bpi_dc2+$total_t_bpi_dc3;
																	$t_t_bpi_cc=$total_t_bpi_cc+$total_t_bpi_cc2+$total_t_bpi_cc3;																	
																	$t_t_metro_dc=$total_t_metro_dc+$total_t_metro_dc2+$total_t_metro_dc3;
																	$t_t_metro_cc=$total_t_metro_cc+$total_t_metro_cc2+$total_t_metro_cc3;
																	$t_atd=$total_atd+$total_atd2+$total_atd3;
																	$t_st=$total_st+$total_st2+$total_st3;
																	$t_rec=$total_rec+$total_rec2+$total_rec3;
																	$t_rec_c=$total_rec_c+$total_rec2_c+$total_rec3_c;
																	$t_rec_s=$total_rec_s+$total_rec2_s+$total_rec3_s;
																	$t_cwtax=$total_cw_tax+$total_cw_tax2+$total_cw_tax3;
																	$t_o=$total_o+$total_o2+$total_o3;
																	// $t_final_diff=$final_diff+abs($total_diff3);
																																		
																	$t_short=abs($short1)+abs($t_short2)+abs($t_short3);
																	$t_over=abs($over1)+abs($t_over2)+abs($t_over3);
																	$t_final_diff=$t_short-$t_over;
																	// display_error($over1);
																	// display_error($t_over2);
																	// display_error($t_over3);															
																	
																	
																	
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
															label_cell('');
															label_cell('');
															end_row();
															label_cell('');
															label_cell('<font color=#880000><b>'.'GRAND TOTAL:'.'</b></font>');
															label_cell("<font color=#880000><b>".number_format2(abs($t_simpos),2)."<b></font>",'align=right');
															//label_cell("<font color=#880000><b>".number_format2(abs($t_remittance),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_cash),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_srsgc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_gc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_terms),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_evoucher),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_ricepromo),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_ddkita),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_sc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_check),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_bpi_dc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_bpi_cc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_metro_dc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_metro_cc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_atd),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_st),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_rec),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_rec_c),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_rec_s),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_cwtax),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_o),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_final_diff),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(-$t_short,2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_over),2)."<b></font>",'align=right');
															label_cell('');
															end_row();
															label_cell('');
															end_row();
																end_table(1);										
															br();
															start_form();
															start_table();
															start_row();
															div_end();
															
															
function delete_wrong_sales_gl($type_no)
{
	$sql = "DELETE FROM 0_gl_trans
				WHERE type = 60
				AND type_no = $type_no
				AND account='4000'
				AND amount != 0";
	db_query($sql, 'failed to delete');
	
}																							
															
function get_nv_sales($date_)
{
	$sql = "SELECT SUM(Extended) FROM [dbo].[FinishedSales] 
				WHERE LogDate = '$date_'
				AND Voided = 0
				AND pVatable = 0";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
	
}

function get_zr_sales($date_)
{
	$sql = "SELECT SUM(Extended) FROM [dbo].[FinishedSales] 
				WHERE LogDate = '$date_'
				AND Voided = 0
				AND pVatable = 2";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
	
}

function check_sales($type, $type_no)
{
	$sql = "SELECT * FROM 0_gl_trans
				WHERE type = $type
				AND type_no = $type_no
				AND account = '2310'";
	$res = db_query($sql);
	return db_num_rows($res) == 0;
}		
													
if (isset($_POST['approve_cash']))
{
global $Ajax;
begin_transaction();
 //INSERT TO SALES TOTALS
if ($_POST['t_simpos']>0) {
save_sales_totals(date2sql($_POST['t_date']),$_POST['t_simpos']+0,$_POST['t_cash']+0,$_POST['t_srsgc']+0,
$_POST['t_gc']+0,$_POST['t_terms']+0,$_POST['t_evoucher']+0,$_POST['t_ricepromo']+0,$_POST['t_ddkita']+0,$_POST['t_t_sc']+0, $_POST['t_t_check']+0,$_POST['t_t_dc']+0,$_POST['t_t_cc']+0,$_POST['t_atd']+0,$_POST['t_st']+0,
$_POST['t_rec']+0,$_POST['t_rec_c']+0,$_POST['t_rec_s']+0,$_POST['t_cwtax']+0,$_POST['t_short']+0,$_POST['t_over']+0,$approved=1,$processed=0);
display_notification(_("The Total Sales has been Saved."));

$sqldc = "select r.remittance_date as r_dateremit,ot.id as ot_id, ot.remittance_id as ot_remitid,
ot.transaction_date as ot_transdate,ot.trans_no as ot_transno,ot.account_no as ot_accountno,
ot.tender_type as ot_tender,ot.approval_no as ot_approvalno,ot.trans_amount as ot_transamount,
ot.card_desc as ot_carddesc from ".CR_DB.TB_PREF."other_trans as ot 
left join ".CR_DB.TB_PREF."remittance as r
on r.remittance_id=ot.remittance_id
where  ot.transaction_date='".date2sql($_POST['date_'])."' and (ot.tender_type='013' or ot.tender_type='014')";
$resultdc=db_query_rs($sqldc);
//display_error($sqldc);
while($rowdc = db_fetch($resultdc))
{
$dc_date_paid='0000-00-00';
	if ($rowdc['ot_remitid'] != "0") {
	$sql = "INSERT INTO ".TB_PREF."sales_debit_credit (dc_remittance_id,dc_remittance_date,dc_transaction_date,
	dc_trans_no,dc_account_no,dc_tender_type,dc_approval_no,dc_trans_amount,dc_card_desc,dc_date_paid,dc_over_payment,dc_charge_back,processed,paid)
	VALUES ('".$rowdc['ot_remitid']."', '".$rowdc['r_dateremit']."', '".$rowdc['ot_transdate']."', '".$rowdc['ot_transno']."','".$rowdc['ot_accountno']."','".$rowdc['ot_tender']."','".$rowdc['ot_approvalno']."','".$rowdc['ot_transamount']."','".$rowdc['ot_carddesc']."','".$dc_date_paid."','0','0','0','0')";
	//display_error($sql);		
	db_query($sql,'failed to insert other remittance');
	}
}
}
else {
display_error(_("The Total Sales Cannot be Empty."));
}
$sqlsalesid="select * from ".TB_PREF."salestotals order by ts_id asc";
$result_sales_id=db_query($sqlsalesid);
while ($idrow = db_fetch($result_sales_id))
{
$salestotals_id=$idrow["ts_id"];
}
$sql = "INSERT INTO ".TB_PREF."salestotals_details (ts_id) VALUES('".$salestotals_id."')";
db_query($sql,"Cash Deposit could not be saved.");
$sqlid_details="select tsd_id from ".TB_PREF."salestotals_details order by tsd_id asc";
$result_id_details=db_query($sqlid_details);
while ($cash_id_det_row = db_fetch($result_id_details))
{
$tsd_id=$cash_id_det_row['tsd_id'];
}
//INSERT GL
$sql_account="select * from ".TB_PREF."sales_gl_accounts";
$result_account=db_query($sql_account);
while ($accountrow = db_fetch($result_account))
{
$sales_account=$accountrow["sales_account"];
$cash_account=$accountrow["cash_account"];
$gc_account=$accountrow["gc_account"];
$suki_account=$accountrow["suki_account"];
$debit_account=$accountrow["debit_account"];
$credit_account=$accountrow["credit_account"];
$check_account=$accountrow["check_account"];
$terms_account=$accountrow["terms_account"];
$evoucher_account=$accountrow["evoucher_account"];
$rice_promo_account=$accountrow["ricepromo_account"];
$ddkita_account=$accountrow["ddkita_account"];
$receivable_account=$accountrow["receivable_account"];
$receivable_account_c=$accountrow["receivable_account_cus"];
$receivable_account_s=$accountrow["receivable_account_sup"];
$cwtax_account=$accountrow["cwtax_account"];
$atd=$accountrow["atd"];
$stock_transfer=$accountrow["stock_transfer"];
$shortage=$accountrow["shortage"];
$overage=$accountrow["overage"];
$cash_in_bank=$accountrow["cash_in_bank"];
}
if (($_POST['t_t_dc']>0) or ($_POST['t_t_cc']>0)){
//DEBIT AND CREDIT GL ACCOUNT
$sql_bank_gl="select card_desc as carddesc, sum(trans_amount) as total_amount
from ".CR_DB.TB_PREF."other_trans where (tender_type='013' or tender_type='014')
and transaction_date='".date2sql($_POST['date_'])."'
group by card_desc";
//display_error($sql_bank_gl);
$result_bank_gl=db_query_rs($sql_bank_gl);
while ($bank_gl_accountrow = db_fetch($result_bank_gl))
{
$gl_bank_total_amount=$bank_gl_accountrow["total_amount"];
$gl_bank_card_desc=$bank_gl_accountrow["carddesc"];
if (($gl_bank_card_desc=='BPI') or ($gl_bank_card_desc=='bpi')){
$bpiamount=$gl_bank_total_amount;
}
if (($gl_bank_card_desc=='PNB') or ($gl_bank_card_desc=='pnb')){
$pnbamount=$gl_bank_total_amount;
}
if (($gl_bank_card_desc=='METROBANK') or ($gl_bank_card_desc=='metrobank')){
$mbamount=$gl_bank_total_amount;
}
}
if($bpiamount>0){
$sqlbpi="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'BPI%' or acquiring_bank='BPI' or acquiring_bank='bpi')";
$result_bpi=db_query($sqlbpi);
while ($bpi_row = db_fetch($result_bpi))
{
$gl_bpi_account=$bpi_row["gl_bank_account"];
$gl_bpi_debit_account=$bpi_row["gl_bank_debit_account"];
}
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gl_bpi_debit_account, 0, 0, $memo, $bpiamount, null, 0);
}
if($pnbamount>0){
$sqlpnb="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'PNB%' or acquiring_bank='PNB' or acquiring_bank='pnb')";
$result_pnb=db_query($sqlpnb);
while ($pnb_row = db_fetch($result_pnb))
{
$gl_pnb_account=$pnb_row["gl_bank_account"];
$gl_pnb_debit_account=$pnb_row["gl_bank_debit_account"];
}
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gl_pnb_debit_account, 0, 0, $memo, $pnbamount, null, 0);
}
if($mbamount>0){
$sqlmb="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'METROBANK%' or acquiring_bank='METROBANK' or acquiring_bank='metrobank')";
$result_mb=db_query($sqlmb);
while ($mb_row = db_fetch($result_mb))
{
$gl_mb_account=$mb_row["gl_bank_account"];
$gl_mb_debit_account=$mb_row["gl_bank_debit_account"];
}
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gl_mb_debit_account, 0, 0, $memo, $mbamount, null, 0);
}
}

if ($_POST['t_cash']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $cash_account, 0, 0, $memo, $_POST['t_cash'], null, 0);
}
if ($_POST['t_srsgc']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gc_account, 0, 0, $memo, $_POST['t_srsgc'], null, 0);
}
if ($_POST['t_gc']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gc_account, 0, 0, $memo, $_POST['t_gc'], null, 0);
}
if ($_POST['t_terms']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $terms_account, 0, 0, $memo, $_POST['t_terms'], null, 0);
}
if ($_POST['t_evoucher']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $evoucher_account, 0, 0, $memo, $_POST['t_evoucher'], null, 0);
}
if ($_POST['t_ricepromo']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $rice_promo_account, 0, 0, $memo, $_POST['t_ricepromo'], null, 0);
}
if ($_POST['t_ddkita']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $ddkita_account, 0, 0, $memo, $_POST['t_ddkita'], null, 0);
}
if ($_POST['t_t_sc']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $suki_account, 0, 0, $memo, $_POST['t_t_sc'], null, 0);
}
if ($_POST['t_t_check']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $cash_account, 0, 0, $memo, $_POST['t_t_check'], null, 0);
}
if ($_POST['t_rec']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $receivable_account, 0, 0, $memo, $_POST['t_rec'], null, 0);
}
if ($_POST['t_rec_c']>0){
			$res_c=mysql_receivable_c_total($_POST['date_']);
			while($row_c = db_fetch($res_c)) 
			{
				add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $row_c["gl_account"], 0, 0, $memo, $row_c["amount"], null, 0);
			}
}

if ($_POST['t_rec_s']>0){
			$res_s=mysql_receivable_s_total($_POST['date_']);
			while($row_s = mysql_fetch_array($res_s))
			{
				add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $row_s["gl_account"], 0, 0, $memo, $row_s["amount"], null, 0);
			}
}
if ($_POST['t_cwtax']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $cwtax_account, 0, 0, $memo, $_POST['t_cwtax'], null, 0);
}
if ($_POST['t_short']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $shortage, 0, 0, $memo, $_POST['t_short'], null, 0);
}
if ($_POST['t_atd']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $atd, 0, 0, $memo, $_POST['t_atd'], null, 0);
}
if ($_POST['t_st']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $stock_transfer, 0, 0, $memo, $_POST['t_st'], null, 0);
}

if ($_POST['t_over']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $overage, 0, 0, $memo, -$_POST['t_over'], null, 0);
}

if ($_POST['t_simpos']>0){
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $sales_account, 0, 0, $memo, -$_POST['t_simpos'], null, 0);
}


commit_transaction();
$Ajax->activate('table_');	
}			
$sqlbutton = "select * from ".TB_PREF."salestotals where ts_date_remit='".date2sql($_POST['date_'])."' and approved=1";
$resbut=db_query($sqlbutton);
$approved_date = mysql_num_rows($resbut);	
		
if ($approved_date<1)
{
div_start('table_');
label_cell('TOTAL CASH:','nowrap class=tableheader');
text_cells('','t_cash',$t_cash,'12');
hidden('t_date',$_POST['date_']);
hidden('t_simpos',$t_simpos);
hidden('t_srsgc',$t_srsgc);
hidden('t_gc',$t_gc);
hidden('t_terms',$t_terms);
hidden('t_evoucher',$t_evoucher);
hidden('t_ricepromo',$t_ricepromo);
hidden('t_ddkita',$t_ddkita);
hidden('t_t_sc',$t_t_sc);
hidden('t_t_check',$t_t_check);
//hidden('t_t_dc',$t_t_dc);
hidden('t_t_dc',$t_t_bpi_dc+$t_t_metro_dc);
hidden('t_t_cc',$t_t_bpi_cc+$t_t_metro_cc);
hidden('t_atd',$t_atd);
hidden('t_st',$t_st);
hidden('t_rec',$t_rec);
hidden('t_rec_c',$t_rec_c);
hidden('t_rec_s',$t_rec_s);
hidden('t_cwtax',$t_cwtax);
hidden('t_short',$t_short);
hidden('t_over',$t_over);
submit_cells('approve_cash', 'Close Transaction', "align=center", true, true,'ok.gif');
}
end_form();
end_row();
end_table(2);
div_end();
end_form();
end_page();
?>