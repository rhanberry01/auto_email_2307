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
	
$th = array(' ', _("Cashier Name"), _("Reading"), 'Cash', 'SRSGC', 'GC', 'Terms', 'EVoucher', 'Suki Card', 'Check', 'Debit','Credit','ATD','ST','Rec.','Others',
	'Diff','Short','Over','');
	
	
//---------------------------------------------------------------------------------
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
	$wholesale_sql4 = " AND fp.UserID NOT IN(".implode(',',$ws_cashier4).")";
//END OF INCLUDE  WHOLESALE MS
//------------------------------------------------------------------------------------------------------------------
	
	
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;
	$c = $k = 0;

$sql = "SELECT DISTINCT
u.name as c_name,
fp.UserID as uid,
SUM(fp.Extended) as total
FROM FinishedSales as  fp
inner join MarkUsers as u
on fp.UserID=u.UserID
where fp.LogDate='".date2sql($_POST['date_'])."'
and fp.Voided='0'
$wholesale_sql4
group by fp.UserID,u.name
ORDER BY u.name ASC
";
//display_error($sql);
$res=ms_db_query($sql);
$num_of_cashier = mssql_num_rows($res);


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
$myremitatd=mysql_atd($_POST['date_'],$wholesale_sql2);
$myremitst=mysql_st($_POST['date_'],$wholesale_sql2);
$myremitrec=mysql_receivable($_POST['date_'],$wholesale_sql2);
$myremitothers=mysql_others($_POST['date_'],$wholesale_sql2);
$myremitid=mysql_r_id($_POST['date_'],$wholesale_sql2);




	
while($row = mssql_fetch_array($res))
{
		$c ++;
		$x = 0;
		// alt_table_row_color($k);

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
$myatd=$myremitatd[$row['uid']];
$myst=$myremitst[$row['uid']];
$myrec=$myremitrec[$row['uid']];
$myothers=$myremitothers[$row['uid']];
$myid=$myremitid[$row['uid']];

$diff = $row['total']- $mycash - $mysrsgc -$mygc - $myterms - $myevoucher- $mysuki - $mycheck - $mydebit - $mycredit - $myatd - $myst - $myrec -$myothers;
		
		$diff = $diff * -1;
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
		$rep->sheet->writeNumber($rep->y, $x, $mysuki, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mycheck, $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $mydebit, $format_accounting);
		$x++;
		
		$rep->sheet->writeNumber($rep->y, $x, $mycredit, $format_accounting);
		$x++;
		
		$rep->sheet->writeNumber($rep->y, $x, $myatd, $format_accounting);
		$x++;
		
		$rep->sheet->writeNumber($rep->y, $x, $myst, $format_accounting);
		$x++;
		
		$rep->sheet->writeNumber($rep->y, $x, $myrec, $format_accounting);
		$x++;
		
		$rep->sheet->writeNumber($rep->y, $x, $myothers, $format_accounting);
		$x++;
		
	if($diff >= 0)
	{
			$rep->sheet->writeNumber($rep->y, $x, $diff, $format_over_short);
			$x++;
			$rep->sheet->writeString($rep->y, $x, '', $format_left);
			$x++;
			$rep->sheet->writeNumber($rep->y, $x, $diff, $format_over_short);
			$x++;
			$over+=$diff;
		}
		else  //short
		{
			$rep->sheet->writeNumber($rep->y, $x,$diff, $format_over_short);
			$x++;
			$rep->sheet->writeNumber($rep->y, $x, $diff, $format_over_short);
			$x++;
			$rep->sheet->writeString($rep->y, $x, '', $format_left);
			$x++;
			$short+=($diff);
		}
	
		$rep->y++;	

$simpos_sales+=$row['total'];
$total_remittance+=$mytotal_reading;
$total_cash+=$mycash;
$total_srsgc+=$mysrsgc;
$total_gc+=$mygc;
$total_terms+=$myterms;
$total_evoucher+=$myevoucher;
$total_t_sc+=$mysuki;
$total_t_check+=$mycheck;
$total_t_dc+=$mydebit;
$total_t_cc+=$mycredit;
$total_atd+=$myatd;
$total_st+=$myst;
$total_rec+=$myrec;
$total_o+=$myothers;
$total_discount+=$sc_disc[$row['cashier_id']];

	
	
	$total_diff=$simpos_sales+abs($over)-abs($short);
	
	if ($total_diff>$simpos_sales)
	{
	$final_diff=$total_diff-$simpos_sales;
	}
	else {
	$final_diff=$simpos_sales-$total_diff;
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
	$rep->sheet->writeNumber($rep->y, $x, $total_t_sc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_check, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_dc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_t_cc, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_atd, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_st, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_rec, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $total_o, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $final_diff, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, -$short, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($over), $format_accounting);
	$rep->y++;
															
															// $sql = "SELECT DISTINCT
															// u.name as c_name,
															// fp.UserID as uid,
															// SUM(fp.Amount) as total
															// FROM FinishedPayments as  fp
															// left join MarkUsers as u
															// on fp.UserID=u.UserID
															// where fp.LogDate='".date2sql($_POST['date_'])."'
															// and fp.Voided='0'
															// $wholesale_sql3
															// group by fp.UserID,u.name";
															// //display_error($sql);

																	$sql = "SELECT u.userid as uid, u.name as c_name, fp.tots as total
																	FROM MarkUsers u
																	LEFT  JOIN 
																	( SELECT SUM(Extended) as tots, UserID from FinishedSales fp
																		WHERE LogDate='".date2sql($_POST['date_'])."'
																		AND fp.$wholesale_sql3
																		GROUP BY fp.userid
																	) as fp

																	ON u.userid = fp.UserID
																	WHERE u.$wholesale_sql3
																	ORDER BY u.name ASC
																	;";
															
															$res2=ms_db_query($sql);
															$num_of_cashier = mssql_num_rows($res2);
																
														
																$myremitotal_reading2=mysql_total_reading2($_POST['date_'],$wholesale_sql2);
																$myremitcash2=mysql_cash2($_POST['date_'],$wholesale_sql2);
																$myremitcredit2=mysql_credit2($_POST['date_'],$wholesale_sql2);
																$myremitdebit2=mysql_debit2($_POST['date_'],$wholesale_sql2);
																$myremitsuki2=mysql_suki2($_POST['date_'],$wholesale_sql2);
																$myremitcheck2=mysql_check2($_POST['date_'],$wholesale_sql2);
																$myremitsrsgc2=mysql_srsgc2($_POST['date_'],$wholesale_sql2);
																$myremitgc2=mysql_gc2($_POST['date_'],$wholesale_sql2);
																$myremitterms2=mysql_terms2($_POST['date_'],$wholesale_sql2);
																$myremitevoucher2=mysql_evoucher2($_POST['date_'],$wholesale_sql2);
																$myremitatd2=mysql_atd2($_POST['date_'],$wholesale_sql2);
																$myremitst2=mysql_st2($_POST['date_'],$wholesale_sql2);
																$myremitrec2=mysql_receivable2($_POST['date_'],$wholesale_sql2);
																$myremitothers2=mysql_others2($_POST['date_'],$wholesale_sql2);
																$myremitid2=mysql_r_id2($_POST['date_'],$wholesale_sql2);
																
																$c=0;
																while($row2 = mssql_fetch_array($res2))
																{
																	$c ++;
																	alt_table_row_color($k);

																	// $reading = get_cashier_reading($row['cashier_id'],$_POST['date_']);
																
																	//$credit=$credit_read[$row['cashier_id']];
																	//$debit=$debit_read[$row['cashier_id']];
																		
																$mytotal_reading2=$myremitotal_reading2[$row2['uid']];
																$mycash2=$myremitcash2[$row2['uid']];
																$mycredit2=$myremitcredit2[$row2['uid']];
																$mydebit2=$myremitdebit2[$row2['uid']];
																$mysuki2=$myremitsuki2[$row2['uid']];
																$mycheck2=$myremitcheck2[$row2['uid']];
																$mysrsgc2=$myremitsrsgc2[$row2['uid']];
																$mygc2=$myremitgc2[$row2['uid']];
																$myterms2=$myremitterms2[$row2['uid']];
																$myevoucher2=$myremitevoucher2[$row2['uid']];
																$myatd2=$myremitatd2[$row2['uid']];
																$myst2=$myremitst2[$row2['uid']];
																$myrec2=$myremitrec2[$row2['uid']];
																$myothers2=$myremitothers2[$row2['uid']];
																$myid2=$myremitid2[$row2['uid']];
																					
																	$diff2 = $row2['total']- $mycash2 - $mysrsgc2 -$mygc2 - $myterms2 - $myevoucher2- $mysuki2 - $mycheck2 - $mydebit2 - $mycredit2 - $myatd2 - $myst2 - $myrec2 -$myothers2;
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
																	$rep->sheet->writeNumber($rep->y, $x, $mysuki2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mycheck2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mydebit2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $mycredit2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myatd2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myst2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $myrec2, $format_accounting);
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
																	$total_t_sc2+=$mysuki2;
																	$total_t_check2+=$mycheck2;
																	$total_t_dc2+=$mydebit2;
																	$total_t_cc2+=$mycredit2;
																	$total_atd2+=$myatd2;
																	$total_st2+=$myst2;
																	$total_rec2+=$myrec2;
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
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_sc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_check2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_dc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_t_cc2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_atd2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_st2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_rec2, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $total_o2, $format_accounting);
																	$x++;
			
															$total_diff2=$simpos_sales2-($total_cash2+$total_srsgc2+$total_gc2+$total_terms2+$total_evoucher2+$total_t_sc2+$total_t_check2+$total_t_dc2+$total_t_cc2+$total_atd2+$total_st2+$total_rec2+$total_o2);

															if($total_diff2 <= 0)
															{
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff2), $format_accounting);
															$x++;
															$rep->sheet->writeString($rep->y, $x, '', $format_left);
															$x++;
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff2), $format_accounting);
															$x++;
															$t_over=abs($over)+abs($total_diff2);
															$t_short=abs($short);
															}

															else
															{
															$rep->sheet->writeNumber($rep->y, $x, abs($total_diff2), $format_accounting);
															$x++;
															$rep->sheet->writeNumber($rep->y, $x, $total_diff2, $format_accounting);
															$x++;
															$rep->sheet->writeString($rep->y, $x, '', $format_left);
															$x++;
															$t_short=abs($short)+abs($total_diff2);
															$t_over=abs($over);
															}	
																$rep->y++;
																
	
																	$t_simpos=$simpos_sales+$simpos_sales2;
																	$t_remittance=$total_remittance+$total_remittance2;
																	$t_cash=$total_cash+$total_cash2;
																	$t_srsgc=$total_srsgc+$total_srsgc2;
																	$t_gc=$total_gc+$total_gc2;
																	$t_terms=$total_terms+$total_terms2;
																	$t_evoucher=$total_evoucher+$total_evoucher2;
																	$t_t_sc=$total_t_sc+$total_t_sc2;
																	$t_t_check=$total_t_check+$total_t_check2;
																	$t_t_dc=$total_t_dc+$total_t_dc2;
																	$t_t_cc=$total_t_cc+$total_t_cc2;
																	$t_atd=$total_atd+$total_atd2;
																	$t_st=$total_st+$total_st2;
																	$t_rec=$total_rec+$total_rec2;
																	$t_o=$total_o+$total_o2;
																	
																	// $t_final_diff=$final_diff+abs($total_diff2);
																	$t_final_diff=abs($t_over)-abs($t_short);
																	
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
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_sc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_check, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_dc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_t_cc, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_atd, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_st, $format_accounting);
																	$x++;
																	$rep->sheet->writeNumber($rep->y, $x, $t_rec, $format_accounting);
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
$th = array(' ', _("Cashier Name"), _("Reading"), 'Cash', 'SRSGC', 'GC', 'Terms', 'EVoucher', 'Suki Card', 'Check', 'Debit','Credit','ATD','ST','Rec.','Others',
	'Diff','Short','Over','');
table_header($th);

//---------------------------------------------------------------------------------
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
	$wholesale_sql4 = " AND fp.UserID NOT IN(".implode(',',$ws_cashier4).")";
//END OF INCLUDE  WHOLESALE MS
//------------------------------------------------------------------------------------------------------------------


$c = $k = 0;

$sql = "SELECT DISTINCT
u.name as c_name,
fp.UserID as uid,
SUM(fp.Extended) as total
FROM FinishedSales as  fp
inner join MarkUsers as u
on fp.UserID=u.UserID
where fp.LogDate='".date2sql($_POST['date_'])."'
and fp.Voided='0'
$wholesale_sql4
group by fp.UserID,u.name
ORDER BY u.name ASC
";
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
$myremitatd=mysql_atd($_POST['date_'],$wholesale_sql2);
$myremitst=mysql_st($_POST['date_'],$wholesale_sql2);
$myremitrec=mysql_receivable($_POST['date_'],$wholesale_sql2);
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
$mycredit=$myremitcredit[$row['uid']];
$mydebit=$myremitdebit[$row['uid']];
$mysuki=$myremitsuki[$row['uid']];
$mycheck=$myremitcheck[$row['uid']];
$mysrsgc=$myremitsrsgc[$row['uid']];
$mygc=$myremitgc[$row['uid']];
$myterms=$myremitterms[$row['uid']];
$myevoucher=$myremitevoucher[$row['uid']];
$myatd=$myremitatd[$row['uid']];
$myst=$myremitst[$row['uid']];
$myrec=$myremitrec[$row['uid']];
$myothers=$myremitothers[$row['uid']];
$myid=$myremitid[$row['uid']];



$diff = $row['total']- $mycash - $mysrsgc -$mygc - $myterms - $myevoucher- $mysuki - $mycheck - $mydebit - $mycredit - $myatd - $myst - $myrec -$myothers;


label_cell($c,'align=right');
label_cell('<b>'.$row['c_name'].'</b>');
amount_cell($row['total']);
//amount_cell($mytotal_reading);
amount_cell($mycash);
amount_cell($mysrsgc);
amount_cell($mygc);
amount_cell($myterms);
amount_cell($myevoucher);
amount_cell($mysuki);
amount_cell($mycheck);
amount_cell($mydebit);
amount_cell($mycredit);
amount_cell($myatd);
amount_cell($myst);
amount_cell($myrec);	
amount_cell($myothers);	



	if($diff <= 0)
	{
		amount_cell(abs($diff),true);
		label_cell('');
		amount_cell(abs($diff),true);
		$over+=$diff;
	}
	else
	{
		label_cell("<font color=red><b>(".number_format2(abs($diff),2).")<b></font>",'align=right');
		amount_cell(abs($diff),true);
		label_cell('');
		$short+=($diff);
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
$total_t_sc+=$mysuki;
$total_t_check+=$mycheck;
$total_t_dc+=$mydebit;
$total_t_cc+=$mycredit;
$total_atd+=$myatd;
$total_st+=$myst;
$total_rec+=$myrec;
$total_o+=$myothers;
$total_discount+=$sc_disc[$row['cashier_id']];

	
	$total_diff=$simpos_sales+abs($over)-abs($short);
	
	if ($total_diff>$simpos_sales)
	{
	$final_diff=$total_diff-$simpos_sales;
	}
	else {
	$final_diff=$simpos_sales-$total_diff;
	}
}



label_cell();
label_cell('<font color=#880000><b>'.'RETAIL TOTAL:'.'</b></font>');
label_cell("<font color=#880000><b>".number_format2(abs($simpos_sales),2)."<b></font>",'align=right');
//label_cell("<font color=#880000><b>".number_format2(abs($total_remittance2),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_cash),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_srsgc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_gc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_terms),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_evoucher),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_sc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_check),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_dc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_t_cc),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_atd),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_st),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_rec),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($total_o),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2($final_diff,2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(-$short,2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($over),2)."<b></font>",'align=right');
label_cell('');
end_row();


															
															// $sql = "SELECT DISTINCT
															// u.name as c_name,
															// fp.UserID as uid,
															// SUM(fp.Extended) as total
															// FROM FinishedSales as  fp
															// inner join MarkUsers as u
															// on fp.UserID=u.UserID
															// where fp.LogDate='".date2sql($_POST['date_'])."'
															// and fp.Voided='0'
															// $wholesale_sql3
															// group by fp.UserID,u.name";
															
															$sql = "SELECT u.userid as uid, u.name as c_name, fp.tots as total
																	FROM MarkUsers u
																	LEFT  JOIN 
																	( SELECT SUM(Extended) as tots, UserID from FinishedSales fp
																		WHERE LogDate='".date2sql($_POST['date_'])."'
																		AND fp.$wholesale_sql3
																		GROUP BY fp.userid
																	) as fp

																	ON u.userid = fp.UserID
																	WHERE u.$wholesale_sql3
																	ORDER BY u.name ASC
																	;";
															// display_error($sql);
															$res2=ms_db_query($sql);
															$num_of_cashier = mssql_num_rows($res2);
																
														
														
																//$myname2=mysql_cashier_name2($_POST['date_'],$wholesale_sql2);
																$myremitotal_reading2=mysql_total_reading2($_POST['date_'],$wholesale_sql2);
																
																$myremitcash2=mysql_cash2($_POST['date_'],$wholesale_sql2);
																$myremitcredit2=mysql_credit2($_POST['date_'],$wholesale_sql2);
																$myremitdebit2=mysql_debit2($_POST['date_'],$wholesale_sql2);
																$myremitsuki2=mysql_suki2($_POST['date_'],$wholesale_sql2);
																$myremitcheck2=mysql_check2($_POST['date_'],$wholesale_sql2);
																$myremitsrsgc2=mysql_srsgc2($_POST['date_'],$wholesale_sql2);
																$myremitgc2=mysql_gc2($_POST['date_'],$wholesale_sql2);
																$myremitterms2=mysql_terms2($_POST['date_'],$wholesale_sql2);
																$myremitevoucher2=mysql_evoucher2($_POST['date_'],$wholesale_sql2);
																$myremitatd2=mysql_atd2($_POST['date_'],$wholesale_sql2);
																$myremitst2=mysql_st2($_POST['date_'],$wholesale_sql2);
																$myremitrec2=mysql_receivable2($_POST['date_'],$wholesale_sql2);
																$myremitothers2=mysql_others2($_POST['date_'],$wholesale_sql2);
																
																$myremitid2=mysql_r_id2($_POST['date_'],$wholesale_sql2);
																
																
																$c=0;
																while($row2 = mssql_fetch_array($res2))
																{
																	$c ++;
																	alt_table_row_color($k);

																	// $reading = get_cashier_reading($row['cashier_id'],$_POST['date_']);
																	//$credit=$credit_read[$row['cashier_id']];
																	//$debit=$debit_read[$row['cashier_id']];
																	
																//$my_name2=$myname2[$row2['uid']];
																$mytotal_reading2=$myremitotal_reading2[$row2['uid']];
																$mycash2=$myremitcash2[$row2['uid']];
																$mycredit2=$myremitcredit2[$row2['uid']];
																$mydebit2=$myremitdebit2[$row2['uid']];
																$mysuki2=$myremitsuki2[$row2['uid']];
																$mycheck2=$myremitcheck2[$row2['uid']];
																$mysrsgc2=$myremitsrsgc2[$row2['uid']];
																$mygc2=$myremitgc2[$row2['uid']];
																$myterms2=$myremitterms2[$row2['uid']];
																$myevoucher2=$myremitevoucher2[$row2['uid']];
																$myatd2=$myremitatd2[$row2['uid']];
																$myst2=$myremitst2[$row2['uid']];
																$myrec2=$myremitrec2[$row2['uid']];
																$myothers2=$myremitothers2[$row2['uid']];
																$myid2=$myremitid2[$row2['uid']];
																
																// list($mycash2, $mysrsgc2, $mygc2, $myterms2, $myevoucher2, 
																// $mysuki2, $mycheck2, $mydebit2, $mycredit2, $myatd2, $myst2, $myrec2,$myothers2) 
																// = wh_total_remittance($_POST['date_'],$wholesale_sql2);
																					
																$diff2 = $row2['total']- $mycash2 - $mysrsgc2 -$mygc2 - $myterms2 - $myevoucher2- $mysuki2 - $mycheck2 - $mydebit2 - $mycredit2 - $myatd2 - $myst2 - $myrec2 -$myothers2;
																	
																	label_cell($c,'align=right');
																	label_cell('<b>'.$row2['c_name'].'</b>');
																	amount_cell($row2['total']);
																	//amount_cell($mytotal_reading);
																	amount_cell($mycash2);
																	amount_cell($mysrsgc2);
																	amount_cell($mygc2);
																	amount_cell($myterms2);
																	amount_cell($myevoucher2);
																	amount_cell($mysuki2);
																	amount_cell($mycheck2);
																	amount_cell($mydebit2);
																	amount_cell($mycredit2);
																	amount_cell($myatd2);
																	amount_cell($myst2);
																	amount_cell($myrec2);	
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
																	$total_t_sc2+=$mysuki2;
																	$total_t_check2+=$mycheck2;
																	$total_t_dc2+=$mydebit2;
																	$total_t_cc2+=$mycredit2;
																	$total_atd2+=$myatd2;
																	$total_st2+=$myst2;
																	$total_rec2+=$myrec2;
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
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_sc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_check2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_dc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_t_cc2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_atd2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_st2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_rec2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_o2),2)."<b></font>",'align=right');
															
															$total_diff2=$simpos_sales2-($total_cash2+$total_srsgc2+$total_gc2+$total_terms2+$total_evoucher2+$total_t_sc2+$total_t_check2+$total_t_dc2+$total_t_cc2+$total_atd2+$total_st2+$total_rec2+$total_o2);

															if($total_diff2 <= 0)
															{
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff2),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff2),2)."<b></font>",'align=right');
															$t_over=abs($over)+abs($total_diff2);
															$t_short=abs($short);
															}
															
															else
															{
															label_cell("<font color=#880000><b>".number_format2(abs($total_diff2),2)."<b></font>",'align=right'); 
															label_cell("<font color=#880000><b>".number_format2(-$total_diff2,2)."<b></font>",'align=right'); 
															label_cell("<font color=#880000><b>".number_format2(abs(),2)."<b></font>",'align=right');
															$t_short=abs($short)+abs($total_diff2);
															$t_over=abs($over);	
															}
															
															label_cell('');
															end_row();
															
	
																	$t_simpos=$simpos_sales+$simpos_sales2;
																	$t_remittance=$total_remittance+$total_remittance2;
																	$t_cash=$total_cash+$total_cash2;
																	$t_srsgc=$total_srsgc+$total_srsgc2;
																	$t_gc=$total_gc+$total_gc2;
																	$t_terms=$total_terms+$total_terms2;
																	$t_evoucher=$total_evoucher+$total_evoucher2;
																	$t_t_sc=$total_t_sc+$total_t_sc2;
																	$t_t_check=$total_t_check+$total_t_check2;
																	$t_t_dc=$total_t_dc+$total_t_dc2;
																	$t_t_cc=$total_t_cc+$total_t_cc2;
																	$t_atd=$total_atd+$total_atd2;
																	$t_st=$total_st+$total_st2;
																	$t_rec=$total_rec+$total_rec2;
																	$t_o=$total_o+$total_o2;
																	
																	// $t_final_diff=$final_diff+abs($total_diff2);
																	$t_final_diff=abs($t_over)-abs($t_short);
																	
	
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
															//label_cell('');
															//label_cell('');
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
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_sc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_check),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_dc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_t_cc),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_atd),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_st),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_rec),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_o),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_final_diff),2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(-$t_short,2)."<b></font>",'align=right');
															label_cell("<font color=#880000><b>".number_format2(abs($t_over),2)."<b></font>",'align=right');
															label_cell('');
															end_row();
																end_table(1);										
															//$t_simpos

															br();
															start_form();
															start_table();
															start_row();
															div_end();

		
if (isset($_POST['approve_cash']))
{
 global $Ajax;
 
 //INSERT TO SALES TOTALS
if ($_POST['t_simpos']>0)
{
save_sales_totals(date2sql($_POST['t_date']),$_POST['t_simpos'],$_POST['t_cash'],$_POST['t_srsgc'],
$_POST['t_gc'],$_POST['t_terms'],$_POST['t_evoucher'],$_POST['t_t_sc'], $_POST['t_t_check'],$_POST['t_t_dc'],$_POST['t_t_cc'],$_POST['t_atd'],$_POST['t_st'],
$_POST['t_rec'],$_POST['t_short'],$_POST['t_over'],$approved=1,$processed=0);
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
	dc_trans_no,dc_account_no,dc_tender_type,dc_approval_no,dc_trans_amount,dc_card_desc,dc_date_paid,processed,paid)
	VALUES ('".$rowdc['ot_remitid']."', '".$rowdc['r_dateremit']."', '".$rowdc['ot_transdate']."', '".$rowdc['ot_transno']."','".$rowdc['ot_accountno']."','".$rowdc['ot_tender']."','".$rowdc['ot_approvalno']."','".$rowdc['ot_transamount']."','".$rowdc['ot_carddesc']."','".$dc_date_paid."','0','0')";
	//display_error($sql);		
	db_query($sql,'failed to insert other remittance');
	}
}
}
else {
display_error(_("The Total Sales Cannot be Empty."));
}

// //TO INSERT ALL OTHER TRANS TO sales_debit_credit
// for($i = 0; $i<count($_POST['ot_remitid']); $i++) //Getting total # var submitted and running loop 
// { 
// $r_dateremit =  $_POST['r_dateremit'][$i]; 
// $ot_remitid =  $_POST['ot_remitid'][$i]; 
// $ot_transdate =  $_POST['ot_transdate'][$i]; 
// $ot_transno =  $_POST['ot_transno'][$i]; 
// $ot_accountno =  $_POST['ot_accountno'][$i];
// $ot_tender =  $_POST['ot_tender'][$i];
// $ot_approvalno =  $_POST['ot_approvalno'][$i];
// $ot_transamount =  $_POST['ot_transamount'][$i];
// $ot_carddesc = $_POST['ot_carddesc'][$i];
// $dc_date_paid='0000-00-00';
// if ($ot_remitid != "0") {
// $sql = "INSERT INTO ".TB_PREF."sales_debit_credit (dc_remittance_id,dc_remittance_date,dc_transaction_date,
// dc_trans_no,dc_account_no,dc_tender_type,dc_approval_no,dc_trans_amount,dc_card_desc,dc_date_paid,processed,paid)
// VALUES ('$ot_remitid', '$r_dateremit', '$ot_transdate', '$ot_transno','$ot_accountno','$ot_tender','$ot_approvalno','$ot_transamount','$ot_carddesc','$dc_date_paid','0','0')";
// display_error($sql);		
// db_query($sql,'failed to insert other remittance');
// }
// }

//INSERT AND SELECT ACCOUNT_ID


		$sqlsalesid="select * from ".TB_PREF."salestotals order by ts_id asc";
		$result_sales_id=db_query($sqlsalesid);
		while ($idrow = db_fetch($result_sales_id))
		{
		$salestotals_id=$idrow["ts_id"];
		}

		$sql = "INSERT INTO ".TB_PREF."salestotals_details (ts_id) 
			VALUES('".$salestotals_id."')";

		db_query($sql,"Cash Deposit could not be saved.");
		
		$sqlid_details="select tsd_id from ".TB_PREF."salestotals_details order by tsd_id asc";
		$result_id_details=db_query($sqlid_details);
		
		while ($cash_id_det_row = db_fetch($result_id_details))
		{
		// $id_count=db_num_rows($sqlid_details);
		// if ($id_count<=1)
		// {
		$tsd_id=$cash_id_det_row['tsd_id'];
		// }
		// else {
		// $c_id=++$cash_id_det_row['ct_id'];
		// }
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
		$receivable_account=$accountrow["receivable_account"];
		$atd=$accountrow["atd"];
		$stock_transfer=$accountrow["stock_transfer"];
		$shortage=$accountrow["shortage"];
		$overage=$accountrow["overage"];
		$cash_in_bank=$accountrow["cash_in_bank"];
		}
	
		
if (($_POST['t_t_dc']>0) or ($_POST['t_t_cc']>0))
{

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
		
		if (($gl_bank_card_desc=='BPI') or ($gl_bank_card_desc=='bpi'))
		{
		$bpiamount=$gl_bank_total_amount;
		}
		
		if (($gl_bank_card_desc=='PNB') or ($gl_bank_card_desc=='pnb'))
		{
		$pnbamount=$gl_bank_total_amount;
		}
		
		if (($gl_bank_card_desc=='METROBANK') or ($gl_bank_card_desc=='metrobank'))
		{
		$mbamount=$gl_bank_total_amount;
		}
		
		}

		// display_error($bpiamount);
		// display_error($pnbamount);
		// display_error($mbamount);
		
if($bpiamount>0)
{
		$sqlbpi="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'BPI%' or acquiring_bank='BPI' or acquiring_bank='bpi')";
		$result_bpi=db_query($sqlbpi);
		while ($bpi_row = db_fetch($result_bpi))
		{
		$gl_bpi_account=$bpi_row["gl_bank_account"];
		$gl_bpi_debit_account=$bpi_row["gl_bank_debit_account"];
		}
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gl_bpi_debit_account, 0, 0, $memo, $bpiamount, null, 0);
}

if($pnbamount>0)
{
		$sqlpnb="select gl_bank_account,gl_bank_debit_account from ".TB_PREF."acquiring_banks where (acquiring_bank like 'PNB%' or acquiring_bank='PNB' or acquiring_bank='pnb')";
		$result_pnb=db_query($sqlpnb);
		while ($pnb_row = db_fetch($result_pnb))
		{
		$gl_pnb_account=$pnb_row["gl_bank_account"];
		$gl_pnb_debit_account=$pnb_row["gl_bank_debit_account"];
		}
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gl_pnb_debit_account, 0, 0, $memo, $pnbamount, null, 0);
}

if($mbamount>0)
{
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

 if ($_POST['t_cash']>0)
 {
 add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $cash_account, 0, 0, $memo, $_POST['t_cash'], null, 0);
 }
 if ($_POST['t_srsgc']>0)
{
 add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gc_account, 0, 0, $memo, $_POST['t_srsgc'], null, 0);
 }
 
if ($_POST['t_gc']>0)
{
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $gc_account, 0, 0, $memo, $_POST['t_gc'], null, 0);
}
 
if ($_POST['t_terms']>0)
{
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $terms_account, 0, 0, $memo, $_POST['t_terms'], null, 0);
}
 
if ($_POST['t_evoucher']>0)
{
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $evoucher_account, 0, 0, $memo, $_POST['t_evoucher'], null, 0);
}
 
 if ($_POST['t_t_sc']>0)
 {
 add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $suki_account, 0, 0, $memo, $_POST['t_t_sc'], null, 0);
 }
 
  if ($_POST['t_t_check']>0)
 {
 add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $check_account, 0, 0, $memo, $_POST['t_t_check'], null, 0);
 }
 
 // if ($_POST['t_t_dc']>0)
 // {
 // add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $debit_account, 0, 0, $memo, $_POST['t_t_dc'], null, 0);
 // }
 // if ($_POST['t_t_cc']>0)
// {
 // add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $credit_account, 0, 0, $memo, $_POST['t_t_cc'], null, 0);
// }

 if ($_POST['t_rec']>0)
{
 add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $receivable_account, 0, 0, $memo, $_POST['t_rec'], null, 0);
}

 if ($_POST['t_short']>0)
 {
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $shortage, 0, 0, $memo, $_POST['t_short'], null, 0);
}
 
 
 if ($_POST['t_atd']>0)
 {
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $atd, 0, 0, $memo, $_POST['t_atd'], null, 0);
}
 
  if ($_POST['t_st']>0)
 {
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $stock_transfer, 0, 0, $memo, $_POST['t_st'], null, 0);
}
 
 
 if ($_POST['t_simpos']>0)
{
add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $sales_account, 0, 0, $memo, -$_POST['t_simpos'], null, 0);
}
 
 if ($_POST['t_over']>0)
 {
 add_gl_trans(ST_SALESTOTAL, $tsd_id, $_POST['t_date'], $overage, 0, 0, $memo, -$_POST['t_over'], null, 0);
}
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
hidden('t_t_sc',$t_t_sc);
hidden('t_t_check',$t_t_check);
hidden('t_t_dc',$t_t_dc);
hidden('t_t_cc',$t_t_cc);
hidden('t_atd',$t_atd);
hidden('t_st',$t_st);
hidden('t_rec',$t_rec);
hidden('t_short',$t_short);
hidden('t_over',$t_over);

submit_cells('approve_cash', 'Approve', "align=center", true, true,'ok.gif');
}

end_form();
end_row();
end_table(2);
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