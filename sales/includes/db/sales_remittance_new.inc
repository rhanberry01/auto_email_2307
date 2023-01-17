<?php
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

function insert_remittance($cashier_id, $treasurer_id,$treasurer_name)
{
	global $path_to_root;
	
	$sql = "INSERT INTO ".CR_DB.TB_PREF."remittance (remittance_date,
					cashier_id,
					cashier_name,
					treasurer_id,
					treasurer_name,
					total_cash,
					total_credit_card,
					total_debit_card,
					total_suki_card,
					total_srs_gc,
					total_others,
					remarks
					)
			VALUES ('".date2sql($_POST['remittance_date'])."',$cashier_id, ".db_escape($_POST['cashier_name']).",  
			$treasurer_id, ".db_escape($treasurer_name).",".input_num('g_total').",
			".input_num('total_credit_card').",
			".input_num('total_debit_card').",
			".input_num('total_suki_card').",
			".input_num('total_srs_gc').",
			".input_num('total_others').",
			".db_escape($_POST['remarks']).")";
	db_query_rs($sql,'failed to insert remittance header');

	$remittance_id = db_insert_id();
	
	$sql = "SELECT * FROM ".CR_DB.TB_PREF."denominations";
	$result = db_query_rs($sql,"could not get denominations");
	
	while ($myrow = db_fetch($result)) 
	{
		$quantity =  input_num('deno'.$myrow["id"]);
		if ($quantity > 0)
			insert_remittance_details($remittance_id, $myrow["denomination"], $quantity);
	}

	return $remittance_id;
}

function get_remittance_header($r_id)
{
	$sql = "SELECT * FROM ".CR_DB.TB_PREF."remittance WHERE remittance_id = $r_id";
	$res = db_query_rs($sql);
	$row = db_fetch($res);
	
	return $row;
}

function insert_remittance_details($remittance_id, $denomination, $quantity)
{
	$sql = "INSERT INTO ".CR_DB.TB_PREF."remittance_details (remittance_id, denomination, quantity)
				VALUES ($remittance_id, $denomination, $quantity)";
	db_query_rs($sql,'failed to insert remittance details');
}

function approve_remittance($r_id)
{

	$sql = "UPDATE ".CR_DB.TB_PREF."remittance SET 
				treasurer_id = ".$_SESSION['wa_current_user']->user.",
				treasurer_name = '".$_SESSION['wa_current_user']->name."'
			WHERE remittance_id = $r_id";
	db_query_rs($sql,'failed to approve remittance');
}


function approve_acquiring($id,$date_paid)
{
	$sqlpay ="UPDATE ".TB_PREF."sales_debit_credit SET processed='1', paid='1', dc_date_paid='$date_paid' WHERE dc_id='".$id."'";
	db_query($sqlpay,'failed to approve.');	
	//display_error($sqlpay);
}


function insert_approve_acquiring($approve_id,$transno,$inv_num,$terminal_id,$transdate,$remitdate,$tendertype,$card_desc,$accountno,$approvalno,$amount,$fee,$mdr,$wt,$cwt,$net,$date_paid)
{

$sql = "INSERT INTO ".TB_PREF."acquiring_deductions
				(dc_id,p_trans_no,p_trans_date,p_invoice_no,p_terminal_id,p_remittance_date,p_tender_type,p_bank_card,p_account_no,p_approval_no,p_amount,
p_mfeepercent,p_mfeeamount,p_wtaxpercent,p_wtaxamount,p_net_total,date_paid) 
			VALUES('".$approve_id."','".$transno."','".$transdate."','".$inv_num."','".$terminal_id."','".$remitdate."','".$tendertype."','".$card_desc."','".$accountno."','".$approvalno."','".$amount."','".$fee."','".$mdr."','".$wt."','".$cwt."','".$net."','".$date_paid."')";
	
	if ($transdate!=0 and $remitdate!=0)
	{
	db_query($sql,"Bank Deduction could not be approved");
	}
	//display_error($sql);
display_notification('Selected has been approved');
}


function insert_cash_deposit($r_date,$cash_remit,$cash_deposit,$date_deposit,$approved)
{
$date_paid = date2sql($_POST['date_paid']);

$sql = "INSERT INTO ".CR_DB.TB_PREF."cash_deposit
				(s_date,cash_remit,cash_deposit,date_deposit,approved) 
			VALUES('".$r_date."','".$cash_remit."','".$cash_deposit."','".$date_deposit."','".$approved."')";
	db_query($sql,"Bank Deduction could not be approved");
//display_notification('Selected has been approved');
//display_error($sql);
}

function disapprove_remittance($r_id,$reason)
{
	$sql = "UPDATE ".CR_DB.TB_PREF."remittance SET 
				treasurer_id = ".$_SESSION['wa_current_user']->user.",
				treasurer_name = '".$_SESSION['wa_current_user']->name."',
				is_disapproved = 1,
				reason = ". db_escape($reason) ."
			WHERE remittance_id = $r_id";
	db_query_rs($sql,'failed to approve remittance');
}


function approve_depreciation($id)
{
	$sqldep ="UPDATE ".TB_PREF."dep_exp_fixed_assets SET depreciated='1' WHERE dep_id='".$id."'";
	db_query($sqldep,'failed to depreciate.');	
	//display_error($sqldep);
}


function insert_approve_depreciation($approve_id,$db_acquisition_cost,$db_life,$db_monthly_depreciation)
{
$sql = "INSERT INTO ".TB_PREF."dep_exp_depreciation_details
				(d_dep_id,d_monthly_depreciation) 
			VALUES('".$approve_id."','".$db_monthly_depreciation."')";
	db_query($sql,"Depreciation could not be approved");
	//display_error($sql);
//display_notification('Selected has been Depereciated');
}


//START OF REMITTANCE SUMMARY PER DAY
	//start of table display
function get_cashiers_total_remittance_per_day($date_,$wholesale_sql)
{
$sql = "SELECT remittance_id,remittance_date,cashier_id, cashier_name,
SUM(total_cash) as t_cash, SUM(total_credit_card) as t_cc, SUM(total_debit_card) as t_dc,
SUM(total_suki_card) as t_sc, SUM(total_check) as t_check, SUM(total_srs_gc) as t_srsgc, SUM(total_gc) as t_gc, SUM(total_terms) as t_terms, SUM(total_e_voucher) as t_evoucher, SUM(total_atd) as t_atd,
SUM(total_stock_transfer) as t_st,  SUM(total_receivable) as t_rec,SUM(total_others) as t_o,
(SUM(total_cash)+SUM(total_credit_card)+SUM(total_debit_card)+SUM(total_suki_card)+SUM(total_srs_gc)+SUM(total_gc)+SUM(total_terms)+SUM(total_e_voucher)+SUM(total_atd)+SUM(total_stock_transfer)+SUM(total_receivable)+SUM(total_others)) as total_reading
FROM ".CR_DB.TB_PREF."remittance 
WHERE remittance_date ='".date2sql($date_)."'
$wholesale_sql
AND is_disapproved = 0
AND treasurer_id != 0
GROUP BY remittance_date,cashier_id,cashier_name
ORDER BY cashier_name
";
return db_query_rs($sql);
}



																	//------------------------RETAIL----------------------------------------
																		
																		function mysql_cashier_name($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		cashier_name
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);
																		//display_error($sql);

																			$myname= array();
																			while($row = mysql_fetch_array($res))
																				$myname[$row['cashier_id']] = $row[1];
																				return $myname;
																		}
																		
																		
																		
																		function mysql_cash($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_cash) as t_cash
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);
																		//display_error($sql);

																			$mycash = array();
																			while($row = mysql_fetch_array($res))
																				$mycash[$row['cashier_id']] = $row[1];
																				return $mycash;
																		}


																		function mysql_credit($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_credit_card) as t_cc
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$mycredit = array();
																			while($row = mysql_fetch_array($res))
																				$mycredit[$row['cashier_id']] = $row[1];
																				return $mycredit;
																		}

																		function mysql_debit($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_debit_card) as t_dc
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$mydebit = array();
																			while($row = mysql_fetch_array($res))
																				$mydebit[$row['cashier_id']] = $row[1];
																				return $mydebit;
																		}


																		function mysql_suki($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_suki_card) as t_sc
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$mysuki = array();
																			while($row = mysql_fetch_array($res))
																				$mysuki[$row['cashier_id']] = $row[1];
																				return $mysuki;
																		}

																		
																		
																		function mysql_check($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_check) as t_check
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$mycheck = array();
																			while($row = mysql_fetch_array($res))
																				$mycheck[$row['cashier_id']] = $row[1];
																				return $mycheck;
																		}
								

																		function mysql_srsgc($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		 SUM(total_srs_gc) as t_srsgc
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$mysrsgc = array();
																			while($row = mysql_fetch_array($res))
																				$mysrsgc[$row['cashier_id']] = $row[1];
																				return $mysrsgc;
																		}
																		
																		
																		function mysql_gc($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		 SUM(total_gc) as t_gc
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$mygc = array();
																			while($row = mysql_fetch_array($res))
																				$mygc[$row['cashier_id']] = $row[1];
																			return $mygc;
																		}
																		
																		
																		function mysql_terms($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		 SUM(total_terms) as t_terms
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myterms = array();
																			while($row = mysql_fetch_array($res))
																				$myterms[$row['cashier_id']] = $row[1];
																			return $myterms;
																		}
																		
																		
																		function mysql_evoucher($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		 SUM(total_e_voucher) as t_evoucher
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myevoucher = array();
																			while($row = mysql_fetch_array($res))
																				$myevoucher[$row['cashier_id']] = $row[1];
																			return $myevoucher;
																		}
																		
																		function mysql_rice_promo($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		 SUM(total_rice_promo) as t_rice_promo
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myricepromo = array();
																			while($row = mysql_fetch_array($res))
																				$myricepromo[$row['cashier_id']] = $row[1];
																			return $myricepromo;
																		}
																		
																		function mysql_ddkita($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_ddkita) as t_ddkita
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myddkita = array();
																			while($row = mysql_fetch_array($res))
																				$myddkita[$row['cashier_id']] = $row[1];
																			return $myddkita;
																		}
																		

																		function mysql_atd($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_atd) as t_atd
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myatd = array();
																			while($row = mysql_fetch_array($res))
																				$myatd[$row['cashier_id']] = $row[1];
																				return $myatd;
																		}


																		function mysql_st($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_stock_transfer) as t_st
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myst = array();
																			while($row = mysql_fetch_array($res))
																				$myst[$row['cashier_id']] = $row[1];
																				return $myst;
																		}
																		
																		function mysql_others($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_others) as t_o
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myothers = array();
																			while($row = mysql_fetch_array($res))
																				$myothers[$row['cashier_id']] = $row[1];
																				return $myothers;
																		}
																		
																		
																		function mysql_receivable($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_receivable) as t_o
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);
																		//display_error($sql);

																			$myreceivable = array();
																			while($row = mysql_fetch_array($res))
																				$myreceivable[$row['cashier_id']] = $row[1];
																				return $myreceivable;
																		}
																		
																		function mysql_cw_tax($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		SUM(total_cw_tax) as t_cw_tax
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);
																		//display_error($sql);

																			$mycwtax = array();
																			while($row = mysql_fetch_array($res))
																				$mycwtax[$row['cashier_id']] = $row[1];
																				return $mycwtax;
																		}
																		
																		
																		
																		function mysql_total_reading($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		(SUM(total_cash)+SUM(total_credit_card)+SUM(total_debit_card)+SUM(total_suki_card)+SUM(total_srs_gc)+SUM(total_atd)+SUM(total_stock_transfer)+SUM(total_receivable)+SUM(total_others)) as total_reading
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$mytotal_reading = array();
																			while($row = mysql_fetch_array($res))
																				$mytotal_reading[$row['cashier_id']] = $row[1];
																				return $mytotal_reading;
																		}
																		
																		
																		
																		function mysql_r_id($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		remittance_id as r_id
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myothers = array();
																			while($row = mysql_fetch_array($res))
																				$myothers[$row['cashier_id']] = $row[1];
																				return $myothers;
																		}

																		//------------------------WHOLESALE----------------------------------------
																	
																		function mysql_cashier_name2($date_,$wholesale_sql2)
																		{
																		$sql = "SELECT cashier_id,
																		cashier_name
																		FROM ".CR_DB.TB_PREF."remittance 
																		WHERE remittance_date ='".date2sql($date_)."'
																		$wholesale_sql2
																		AND is_disapproved = 0
																		AND treasurer_id != 0
																		GROUP BY remittance_date,cashier_id,cashier_name
																		ORDER BY cashier_name
																		";
																		$res=db_query_rs($sql);

																			$myname2 = array();
																			while($row = mysql_fetch_array($res))
																				$myname2[$row['cashier_id']] = $row[1];
																				return $myname2;
																		}
																		


																		//--------------------------------------------------------------------------


function get_all_cashier_total_disc_per_day($date_)
{
	$sql = "SELECT UserID, SUM(Qty*AmountDiscounted)
			FROM FinishedSales
			WHERE LogDate = '".date2sql($date_)."'
			AND AmountDiscounted > 0
			GROUP BY UserID";
	$res = ms_db_query($sql);
	
	$sc_ = array();
	while($row = mssql_fetch_array($res))
		$sc_[$row['UserID']] = $row[1];
	
	return $sc_;
}


//function get_all_cashier_reading($date_)
//{
//	$sql = "SELECT UserID, SUM(Amount) FROM FinishedPayments 
//			WHERE LogDate ='".date2sql($date_)."'
//			AND Voided = 0
//			GROUP BY UserID";
	// display_error($sql);
//	$res = ms_db_query($sql);
	
//	$c_reading = array();
//	while($row = mssql_fetch_array($res))
//		$c_reading[$row['UserID']] = $row[1];
	
//	return $c_reading;
//}


function get_all_cashier_reading($date_)
{
	$sql = "SELECT 
UserID,sum(Extended)as f_extended
FROM FinishedSales as fs
WHERE LogDate ='".date2sql($date_)."'
AND Voided='0'
GROUP BY UserID";
				
	// display_error($sql);
	$res = ms_db_query($sql);
	
	$c_reading = array();
	while($row = mssql_fetch_array($res))
		$c_reading[$row['UserID']] = $row[1];
	
	return $c_reading;
}



function get_all_sales_reading($datefrom,$dateto)
{
	$sql="SELECT cast(LogDate as date) as logdate,(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total FROM FinishedTransaction as ft
	WHERE LogDate >='".$datefrom."' AND LogDate <='".$dateto."'  AND Voided='0' GROUP BY LogDate";
	
	
	// $sql = "SELECT cast(LogDate as date) as logdate, SUM(Amount) as total FROM FinishedPayments 
			// WHERE LogDate >='".$datefrom."' AND LogDate <='".$dateto."'
			// AND Voided = 0
			// GROUP BY LogDate";
	// display_error($sql);
	$res = ms_db_query($sql);
	
	$x_reading = array();
	while($row = mssql_fetch_array($res))
		$x_reading[$row['logdate']] = $row[1];
	
	return $x_reading;
}

		//end of table display
//END OF REMITTANCE SUMMARY PER DAY


function get_total_cashier_reading($date_)
{
$sql = "select sum(total) as gtotal from (SELECT DISTINCT
u.name,
fp.UserID,
SUM(fp.Amount) as total
FROM [FinishedPayments] as  fp
left join MarkUsers as u
on fp.UserID=u.UserID
where fp.LogDate='".date2sql($date_)."'
and fp.Voided='0' group by fp.UserID,u.name) as x";
$res = ms_db_query($sql);
	while($row = mssql_fetch_array($res))
	$total_read=$row['gtotal'];
	return $total_read;
}



function get_all_bank_name($date_)
{
$sql ="select 
fp_userid,
fp_remarks
from(SELECT DISTINCT
fp.LogDate as fp_logdate,
u.name as u_name,
fp.UserID as fp_userid,
case when fp.TenderCode='013' then fp.Amount end as credit,
case when fp.TenderCode='014' then fp.Amount end as debit,
fp.Remarks as fp_remarks
FROM [FinishedPayments] as  fp
left join MarkUsers as u
on fp.UserID=u.UserID
where fp.LogDate='".date2sql($date_)."' 
and (fp.TenderCode='013' or fp.TenderCode='014')
group by fp.UserID,u.name,fp.TenderCode, fp.Amount, fp.Remarks, fp.LogDate) as x
group by u_name, fp_userid, fp_remarks,fp_logdate  order by fp_userid";
$res = ms_db_query($sql);
	
	$bankcard = array();
	while($row = mssql_fetch_array($res))
		$bankcard[$row['fp_userid']] = $row[1];
		return $bankcard;
		
}




function get_all_credit_reading($date_)
{
$sql = "SELECT UserID, SUM(Amount) FROM FinishedPayments
where LogDate='".date2sql($date_)."' 
and TenderCode='013' and Voided='0'
GROUP BY UserID";
	// display_error($sql);
	$res = ms_db_query($sql);
	
	
	$credit_read = array();
	while($row = mssql_fetch_array($res))
		$credit_read[$row['UserID']] = $row[1];
		
		return $credit_read;
		
}
		//end of table display
//END OF CREDIT SUMMARY PER DAY


function get_total_credit_reading($date_)
{
$sql = "select sum(total) as gtotal from (SELECT DISTINCT
u.name,
fp.UserID,
SUM(fp.Amount) as total
FROM [FinishedPayments] as  fp
left join MarkUsers as u
on fp.UserID=u.UserID
where fp.LogDate='".date2sql($date_)."'
and fp.Voided='0' and fp.TenderCode='013' group by fp.UserID,u.name) as x";
$res = ms_db_query($sql);
	while($row = mssql_fetch_array($res))
	$total_read=$row['gtotal'];
	return $total_read;
}





function get_all_cash_reading($date_)
{
$sql = "SELECT cast(LogDate as date) as s_date, SUM(Amount) FROM FinishedPayments
WHERE LogDate>='".date2sql($date_)."' and LogDate<='".date2sql($date_)."' 
and TenderCode='000' and Voided='0'
GROUP BY LogDate";
	// display_error($sql);
	$res = ms_db_query($sql);

	$cash_read = array();
	while($row = mssql_fetch_array($res))
		$cash_read[$row['UserID']] = $row[1];
	return $cash_read;
	
}
		//end of table display
//END OF CREDIT SUMMARY PER DAY



function get_all_debit_reading($date_)
{
$sql = "SELECT UserID, SUM(Amount) FROM FinishedPayments
where LogDate='".date2sql($date_)."' 
and TenderCode='014' and Voided='0'
GROUP BY UserID";
	// display_error($sql);
	$res = ms_db_query($sql);
	
	
	$debit_read = array();
	while($row = mssql_fetch_array($res))
		$debit_read[$row['UserID']] = $row[1];
		
		return $debit_read;
		
}
		//end of table display
//END OF CREDIT SUMMARY PER DAY


function get_total_debit_reading($date_)
{
$sql = "select sum(total) as gtotal from (SELECT DISTINCT
u.name,
fp.UserID,
SUM(fp.Amount) as total
FROM [FinishedPayments] as  fp
left join MarkUsers as u
on fp.UserID=u.UserID
where fp.LogDate='".date2sql($date_)."'
and fp.Voided='0' and fp.TenderCode='014' group by fp.UserID,u.name) as x";
$res = ms_db_query($sql);
	while($row = mssql_fetch_array($res))
	$total_read=$row['gtotal'];
	return $total_read;
}




function get_remittance_denominations($r_ids)
{
	// $r_ids = array();
	$sql = "SELECT a.denomination, SUM(b.quantity) as quantity
				FROM ".CR_DB."0_remittance_details b
				RIGHT OUTER JOIN ".CR_DB."0_denominations a ON a.denomination =  b.denomination
				AND remittance_id IN ($r_ids)
				GROUP BY a.denomination
				ORDER BY a.denomination DESC";
	$result = db_query_rs($sql,"could not get denomination count");
	// display_error(($sql));
	return $result;
}

function get_cashier_reading($cashier_id,$date_)
{
	$sql = "SELECT SUM(Amount) FROM FinishedPayments 
			WHERE LogDate = '".date2sql($date_)."'
			AND UserID = $cashier_id
			AND Voided = 0";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}


function get_cashier_total_disc_per_day($cashier_id,$date_)
{
	$sql = "SELECT SUM(Qty*AmountDiscounted)
			FROM FinishedSales
			WHERE LogDate = '".date2sql($date_)."'
			AND UserID = $cashier_id
			AND AmountDiscounted > 0";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
}


function non_item_line($txt,$align='c')
{
	$max_len = strlen($txt);
	// $max_char = 32; //font 10
	$max_char = 33; //font 9
	$text = '';
	
	$pad = STR_PAD_BOTH;
	
	if ($align =='l') //left
		$pad = STR_PAD_RIGHT;
	if ($align =='r') //right
		$pad = STR_PAD_LEFT;
	
	for($x=1; $x<=ceil($max_len/$max_char); $x++)
	{
		$text .= str_pad(substr($txt,(($x-1)*$max_char),$max_char), $max_char, " ", $pad). PHP_EOL;
	}
	return $text;
}

function item_line($row)
{	
	if($row['quantity'] == 0)
		return '';
	
	// $max_char = 38; //font 9
	$max_denomination = 8; //font 9
	$max_pieces = 12; //font 9
	$max_total_amount = 13; //font 9

	
	//ex.   1000           100      100,000.00 
	
	$text = '';
	$line_total = $row['quantity']*$row['denomination'];

	if ($row['denomination'] >= 1)
		$text .= str_pad($row['denomination'] ,$max_denomination , " ", STR_PAD_LEFT);
	else
	{
		$text .= str_pad(number_format2($row['denomination'],2) ,$max_denomination+3 , " ", STR_PAD_LEFT);
		$max_pieces -= 3;
	}
	$text .= str_pad($row['quantity'], $max_pieces, " ", STR_PAD_LEFT);
	$text .= str_pad(number_format2($line_total,2), $max_total_amount, " ", STR_PAD_LEFT);

	return $text . PHP_EOL;
}


function print_remittance($id)
{
	//font size 9 
	global $db_connections;
	//----------------------- priiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiiinnnnnnnnnnnnnttttttttttttttt
	$fp = fopen('C:/cashier_remittance.txt','w');
	
	$r_header = get_remittance_header($id);

	fwrite($fp, PHP_EOL . non_item_line($db_connections[$_SESSION["wa_current_user"]->company]["name"]));
	fwrite($fp, PHP_EOL . non_item_line('Cashier Remittance # '.$id));
	
	$print_time = Now();
	
	$remittance_date = sql2date($r_header['remittance_date']);
	
	$remittance_date_time = $remittance_date .' '. Now();
	
	if ($remittance_date != Today()) // reprint
		$remittance_date_time = $remittance_date;
		
	fwrite($fp, PHP_EOL . non_item_line('Date : '.strtoupper($remittance_date_time),'l'));
	
	if ($remittance_date != Today()) // reprint
		fwrite($fp, non_item_line('Print Date : '.strtoupper(Today().' '.Now()),'l'));
	
	fwrite($fp, non_item_line('Cashier : '.($r_header['cashier_name']),'l'));

	$res = get_remittance_denominations($id);
	
	$total_cash = 0;
	
	if (db_num_rows($res) > 0)
		fwrite($fp, PHP_EOL . non_item_line('Denomination    Pieces     Total '));
	
	while($row = db_fetch($res))
	{
		fwrite($fp, item_line($row));
		$total_cash += ($row['quantity']*$row['denomination']);
	}
	
	$total_remittance = $total_cash;
	
	fwrite($fp, non_item_line('---------------------------------'));

	fwrite($fp, non_item_line(' TOTAL CASH        : '. 
			str_pad(number_format2($total_cash,2) ,	12, " ", STR_PAD_LEFT)));
	fwrite($fp, non_item_line('---------------------------------'));
	
	if ($r_header['total_credit_card'] > 0)
	{
		fwrite($fp, non_item_line(' TOTAL CREDIT CARD : '. 
			str_pad(number_format2($r_header['total_credit_card'],2) ,	12, " ", STR_PAD_LEFT)));
		$total_remittance += $r_header['total_credit_card'];
	}
	
	if ($r_header['total_debit_card'] > 0)
	{	
		fwrite($fp, non_item_line(' TOTAL DEBIT CARD  : '. 
			str_pad(number_format2($r_header['total_debit_card'],2) ,	12, " ", STR_PAD_LEFT)));
			$total_remittance += $r_header['total_debit_card'];
	}
	
	if ($r_header['total_suki_card'] > 0)
	{	
		fwrite($fp, non_item_line(' TOTAL SUKI CARD   : '. 
			str_pad(number_format2($r_header['total_suki_card'],2) ,	12, " ", STR_PAD_LEFT)));
		$total_remittance += $r_header['total_suki_card'];
	}
		
	if ($r_header['total_srs_gc'] > 0)
	{	
		fwrite($fp, non_item_line(' TOTAL SRS GC      : '. 
			str_pad(number_format2($r_header['total_srs_gc'],2) ,	12, " ", STR_PAD_LEFT)));
		$total_remittance += $r_header['total_srs_gc'];
	}
	
	if ($r_header['total_others'] > 0)
	{
		fwrite($fp, non_item_line(' TOTAL OTHERS      : '. 
			str_pad(number_format2($r_header['total_others'],2) ,	12, " ", STR_PAD_LEFT)));
		$total_remittance += $r_header['total_others'];
	}
		
	if ($total_remittance != $total_cash)
	{
		fwrite($fp, non_item_line('---------------------------------'));
		fwrite($fp, non_item_line(' TOTAL REMITTANCE  : '. 
			str_pad(number_format2($total_remittance,2) ,	12, " ", STR_PAD_LEFT)));
	}
	fwrite($fp, PHP_EOL .PHP_EOL .non_item_line('_________________________________'));
	fwrite($fp, non_item_line('Prepared By'));
	
	fclose($fp);
	
	$bat_file = 'C:/cashier_remittance.bat';
	$fp = fopen($bat_file,'w');
	fwrite($fp, 'NOTEPAD /P C:/cashier_remittance.txt');
	fclose($fp);

	// exec($bat_file);
	
	// times printed
	$sql = "UPDATE ".TB_PREF."remittance SET times_printed = times_printed+1 WHERE remittance_id=$id";
	db_query($sql);
}

function save_sales_totals(
$ts_date_remit,
$ts_sales,
$ts_cash,
$ts_srsgc,
$ts_gc,
$ts_terms,
$ts_evoucher,
$ts_ricepromo,
$ts_ddkita,
$ts_suki,
$ts_check,
$ts_debit,
$ts_credit,
$ts_atd,
$ts_stock_trasnfer,
$ts_receivable,
$ts_cwtax,
$ts_short,
$ts_over,
$approved,
$processed)
{
$sql = "INSERT INTO ".TB_PREF."salestotals(ts_date_remit,
ts_sales,ts_cash,ts_srsgc,ts_gc,ts_terms,ts_e_voucher,ts_ricepromo,
ts_ddkita,ts_suki,ts_check,ts_debit,ts_credit,ts_atd,ts_stock_transfer,
ts_receivable,ts_cwtax,ts_short,ts_over,approved,processed) 
VALUES ('".$ts_date_remit."', '".$ts_sales."',  ".$ts_cash.", '".$ts_srsgc."', '".$ts_gc."','".$ts_terms."','".$ts_evoucher."','".$ts_ricepromo."','".$ts_ddkita."','".$ts_suki."', '".$ts_check."', '".$ts_debit."',
'".$ts_credit."', '".$ts_atd."', '".$ts_stock_trasnfer."', '".$ts_receivable."','".$ts_cwtax."','".$ts_short."', '".$ts_over."', 
'".$approved."', '".$processed."')";
//display_error($sql);
db_query($sql, "The Sales Totals could not be saved ");
}
?>