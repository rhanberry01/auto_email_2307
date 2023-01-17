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
$page_security = 'SA_GLREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	GL Accounts Transactions
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_GL_transactions();
																	// function mysql_cash2($date_,$cashier_id)
																		// {
																			
																			// $sql = "SELECT * FROM cashier_remittance.0_remittance_summary
																			// where terminal_nos like '%33%'
																			// and r_summary_date='$date_'";
																			
																		function mysql_cash2($date_,$cashier_id,$counter)
																		{
																				
																			// $sql_ = "SELECT counter FROM cashier_remittance.0_wholesale_counter";															
																			// $res_ = db_query($sql_);
																			// $row = db_fetch($res_);
																			// $counter = $row['counter'];
																			
																			$sqlwholesale3="select counter from  cashier_remittance.".TB_PREF."wholesale_counter";
																			$wholesaleresult3=db_query_rs($sqlwholesale3);
																			$ws_cashier3 = array();
																			while($ws_row3 = db_fetch($wholesaleresult3))
																			{
																			$ws_cashier3[]=$ws_row3['counter'];
																			}
																			$wholesale_sql3_wholesale = '';

																			if (count($ws_cashier3) > 0)
																			$wholesale_sql3_wholesale= "  where terminal_nos IN(".implode(',',$ws_cashier3).")";
																			
		
																			$sql = "	SELECT  cashier_name,
																			SUM(total_cash)
																			+SUM(total_credit_card)
																			+SUM(total_debit_card)
																			+SUM(total_suki_card)
																			+SUM(total_check)
																			+SUM(total_srs_gc)
																			+SUM(total_gc)
																			+SUM(total_terms)
																			+SUM(total_e_voucher)
																			+SUM(total_rice_promo)
																			+SUM(total_ddkita)
																			+SUM(total_atd)
																			+SUM(total_stock_transfer)
																			+SUM(total_others)
																			+SUM(total_receivable)
																			+SUM(total_cw_tax)
																			as total_remit
																			FROM cashier_remittance.0_remittance_summary
																			$wholesale_sql3_wholesale
																			and r_summary_date='$date_'
																			GROUP BY r_summary_date
																			";															
																				
																			//die($sql);	
																			// $sql = "SELECT cashier_id,
																			// SUM(total_cash) as t_cash
																			// FROM ".CR_DB.TB_PREF."remittance 
																			// WHERE remittance_date ='$date_'
																			// AND terminal_nos='033'
																			// AND is_disapproved = 0
																			// AND treasurer_id != 0
																			// GROUP BY remittance_date,cashier_id,cashier_name
																			// ORDER BY cashier_name
																			
																			//display_error($sql);
																			$res=db_query_rs($sql);
																			$row2=db_fetch($res);
																			
																			return $row2;

																			//$mycash = array();
																			// while($row = mysql_fetch_array($res))
																			// {
																				// $mycash[$row['cashier_id']] = $row[1];
																			// }
																				//return $mycash;
																		}


//----------------------------------------------------------------------------------------------------

function print_GL_transactions()
{
	set_time_limit(0);
	global $path_to_root, $systypes_array, $systypes_array_short;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	//$rep = new FrontReport(_('Purchase Report'), "PurchaseReport", 'LONG',8 ,'P');
	$rep = new FrontReport(_('Direct Cashier Remittance Summary'), "ShortReport", user_pagesize(),9 ,'P');
	$dec = user_price_dec();


	$cols = array(0, 20, 150, 200, 280, 360, 410, 460,510);
	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left');
	$headers = array('','Date', 'CTR#', 'Reading','Total Remit', 'Short', 'Over', 'Deduction','Final Remit by');
	//'Discount', 


	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to));
						
	if ($destination)
	{
		global $db_connections;
		$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"];
		$params[] = array('text' => _('Branch'), 'from' => $this_branch, 'to' => '');
	}

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	

	$last_supp_id = $supp_name = $last_gst = '';
	$counter = $supp_total_ = $ov_total = 0;
	
	
	$p_nv_total = $p_v_total = $v_total = $others_total = 0;
	
	$for_vat_total = $for_nv_total = $for_dr_total = $supp_total = array(0,0,0,0,0);
	$details = array();
	
	$consignment_purchases = $srs_as_supp_purchases = $no_purchase = array();
	// $consignment_supp = get_consigment_supplier();

	$consignment_count = $srs_as_supp_count = $np_count = 0;
	

		if($xyz == 0) // VAT
		{
			$rep->Font('bi');
			$rep->fontSize += 3;
			//$rep->TextCol(0, 5, '     VATABLE Suppliers');
			$rep->Line($rep->row -5);
			$rep->NewLine(2);
			$rep->fontSize -= 3;
		}
	
		// $sql_ = "SELECT counter FROM cashier_remittance.0_wholesale_counter";															
		// $res_ = db_query_rs($sql_);
		// $row = db_fetch($res_);	
		// $counter = $row['counter'];
		// $counter1 =str_pad($row['counter'],3,"00", STR_PAD_LEFT);
		// //return var_dump($counter1);
		
		
		$sqlwholesale3="select counter from  cashier_remittance.".TB_PREF."wholesale_counter";
		$wholesaleresult3=db_query_rs($sqlwholesale3);
		$ws_cashier3 = array();
		while($ws_row3 = db_fetch($wholesaleresult3))
		{
		$ws_cashier3[]=$ws_row3['counter'];
		}
		$wholesale_sql3_wholesale = '';

		if (count($ws_cashier3) > 0)
		$wholesale_sql3_wholesale= "  AND ft.TerminalNo IN(".implode(',',$ws_cashier3).")";
		
		
		$sql = "SELECT CAST(ft.LogDate as Date) as LogDate,ft.TerminalNo,ft.userid as uid2,m.name, (SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal))
		as total FROM FinishedTransaction as ft
		left join MarkUsers as m
		on ft.UserID=m.userid
		WHERE LogDate >='".date2sql($from)."' AND LogDate  <= '".date2sql($to)."'
		AND Voided='0'
		$wholesale_sql3_wholesale
		group by ft.userid,m.name,ft.TerminalNo,ft.LogDate
		order by ft.LogDate";
		
		//die($sql );

		
		$res = ms_db_query($sql,'error.');
		
		$company_pref = get_company_prefs();
		
		while ($row = mssql_fetch_array($res))
		{
			$counter ++;
		
			$supp_name = $row['LogDate'];
			$last_gst = $row['gst_no'];
			
			$vat = $p_nv = $p_v = 0;
			
			$p_nv = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_non_vat"]);
			$p_v = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_vat"]);
			// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410');
			$vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410010');
			// $vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410011');

			// header
			
				
			if ($last_supp_id != $row['LogDate'] )
			{
			
				if ($last_supp_id != '')
				{
					$over_short1=0;
					
					// total per supplier
					$rep->Line($rep->row+10);
					
					$rep->Font('bold');
					$rep->Line($rep->row + 10);
					
					if ($destination)
					{
						//$rep->sheet->writeString($rep->y, 2, '===> TOTAL:', $rep->formatTitle);
						$rep->sheet->writeString($rep->y, 2, '===> TOTAL:');
					}
					else
					{
						$rep->TextCol(0, 1,	'');
						$rep->TextCol(1, 2, '');
						$rep->TextCol(2, 3, '');
					}
					
				
					
					$rep->AmountCol(3, 4, $supp_total[1],2);
					$rep->AmountCol(4, 5, $supp_total[2],2);
					
					$t_remit+=$supp_total[2];
					
					$over_short=$supp_total[1]-$supp_total[2];
					
					if ($over_short>0){
						$over_short1=$over_short;
						$t_over_short1+=$over_short1;
						$rep->AmountCol(5, 6, abs($over_short1),2);
					}
					else{
						$rep->AmountCol(5, 6, 0,2);
					}
					
					if ($over_short<0){
						$over_short2=$over_short;
						$t_over_short2+=$over_short2;
						$rep->AmountCol(6, 7, abs($over_short2),2);
					}
					else{
						$rep->AmountCol(6, 7, 0,2);
					}
					
					
					$rep->AmountCol(7 ,8, abs($count_row),2);
					//$rep->AmountCol(8 ,9, abs($count_row),2);
					//$rep->AmountCol(7 ,8, abs($over_short1)/2,2);
					$rep->TextCol(8,9, $remit_by);

					$rep->Font('');
					$rep->NewLine(2);
					
					$supp_total = array(0,0,0,0,0);
					

				
				}
				
				$rep->font('bold');
				$rep->TextCol(0, 5,	$supp_name . '   -   '.$last_gst);
				$rep->Line($rep->row -2);
				$rep->NewLine();
				$rep->font('');
				
				//	$t_deduction+=abs($over_short1)/2;
				
			
			}
				
			
			//==================================
			$last_supp_id = $row['LogDate'];
			
			// totals -----------------------------------------------------
			
			$age_days=date_diff2($to,sql2date($row["del_date"]),"d");
			//$age_days=date_diff2(Today(),sql2date($row["del_date"]),"d");
			//$due_days=date_diff2(Today(),sql2date($row["due_date"]),"d");
			
			
			
				$details[$counter][0] = '';
				$details[$counter][1] = $row['name'];
				$details[$counter][2] = $row['TerminalNo'];
				$details[$counter][3] = round($row['total'],2);
	
				$cash=mysql_cash2($row['LogDate'],$row['uid2'], $row['TerminalNo']+0);
				$details[$counter][4] = round($cash['total_remit'],2);
					
					$remit_by=$cash['cashier_name'];

			$ov_total += round($row['amount'],2);
			
			

			$supp_total[1] += $details[$counter][3];
			$supp_total[2] = $details[$counter][4];

			
			$rep->TextCol(0, 1, $systypes_array_short[$row["type"]], -2);
			$rep->TextCol(1, 2, $details[$counter][1]);
			$rep->TextCol(2, 3, $details[$counter][2] , -5);		
			$rep->AmountCol(3, 4, $details[$counter][3],2);
			$rep->NewLine();	
		
			$t_reading+=$details[$counter][3];
			
		
		}
		

		
		if ($last_supp_id != '')
		{
			// total per supplier
			$over_short1=0;
			
			$rep->Line($rep->row+10);
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10);
			$rep->TextCol(0, 1,	'');
			$rep->TextCol(1, 2, '');
			$rep->TextCol(2, 3, '');
			$rep->AmountCol(3, 4, $supp_total[1],2);
			$rep->AmountCol(4, 5, $supp_total[2],2);
					
					$over_short=$supp_total[1]-$supp_total[2];
					$t_remit+=$supp_total[2];
					
					if ($over_short>0){
						$over_short1=$over_short;
						$t_over_short1+=$over_short1;
						$rep->AmountCol(5, 6, abs($over_short1),2);
					}
					else{
						$rep->AmountCol(5, 6, 0,2);
					}
					
					if ($over_short<0){
						$over_short2=$over_short;
						$t_over_short2+=$over_short2;
						$rep->AmountCol(6, 7, abs($over_short2),2);
					}
					else{
						$rep->AmountCol(6, 7, 0,2);
					}
					
			$rep->AmountCol(7 ,8, abs($over_short1)/2,2);
			$rep->TextCol(8 ,9, $remit_by);

			$rep->Font('');
			$rep->NewLine();
			
			$supp_total = array(0,0,0,0,0);
			//$t_deduction+=abs($over_short1)/2;
		}
			
	
	// die;
	
	
	$rep->NewLine();

	// ============================== APV TOTAL
	$rep->Font('bold');
	$rep->Line($rep->row + 10,1);
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 2, '');
	$rep->TextCol(2, 3, 'TOTAL : ');
	$rep->AmountCol(3, 4, $t_reading,2);
	$rep->AmountCol(4, 5, $t_remit,2);
	$rep->AmountCol(5, 6, abs($t_over_short1),2);
	$rep->AmountCol(6, 7, abs($t_over_short2),2);
	// $rep->TextCol(7, 8, $remit_by);

	$rep->Font('');
	$rep->NewLine();
	
	$rep->End();
}

?>