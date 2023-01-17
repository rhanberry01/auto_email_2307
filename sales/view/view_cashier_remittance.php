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
//-----------------------------------------------------------------------------
//
//	Entry/Modify Sales Quotations
//	Entry/Modify Sales Order
//	Entry Direct Delivery
//	Entry Direct Invoice
//

$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");
// include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = '';
if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}
// if ($_GET['final'] == 1)
page('Approved Cashier Remittance', true, true, "", $js);

//==================================================================

if (isset($_GET['Remittance_ID']))
{
	if ($_GET['final'] != 1)
	{
		$remittance_id = $_GET['Remittance_ID'];
		$sql = "SELECT * FROM ".CR_DB.TB_PREF."remittance WHERE remittance_id = $remittance_id";
		$res = db_query_rs($sql);
		$row = db_fetch($res);
		start_table($table_style);

		echo "<tr valign=top><td width=25%>";

		div_start('cs_login');
		br();
		display_heading('CASHIER');
		br();
		start_outer_table("$table_style2");
		table_section(1);
			label_row('<b><font color=red>Date :</font></b>','<b><font color=red>'.sql2date($row['remittance_date']).'</font></b>');
			label_row('<b>Name :</b>','<b>'.$row['cashier_name'].'</b>');
		end_outer_table(1);
		div_end();

		echo "</td><td width=25%>";

		div_start('items');
		br();
		display_heading('CASH');
		br();

		start_table("$table_style width=30%");
		$th = array(_("Denomination"), "Pieces");
		$th[] = "Line Total";

		table_header($th);
		$g_total = $k = 0; 

		$result = get_remittance_denominations($remittance_id);
		$d_pcs_t = $d_total = $k = 0; 
		while ($myrow = db_fetch($result)) 
		{
			alt_table_row_color($k);
				
			label_cell('<b>'.number_format2($myrow[0],2).'</b>','align=center');
			label_cell($myrow[1] ? $myrow[1] : '-', 'align=center');
			
			
			if ($myrow[0] * $myrow[1] > 0)
			{
				amount_cell($myrow[0] * $myrow[1]);
				$g_total += ($myrow[0] * $myrow[1]);
			}
			else
				label_cell('-','align=center');
			end_row();
		}
		start_row();
			label_cell('<b>TOTAL CASH :</b>',' colspan=2 class=tableheader');
			amount_cell($g_total,true);
		end_row();

		end_table(1);

		div_end();
		echo "</td><td width=25%>";
			
		div_start('others');
		br();
		display_heading('TOTALS');
		br();

		start_table("$table_style2 width=30%");

		start_row();
			label_cell('<b>TOTAL CREDIT CARD : </b>','nowrap class=tableheader');
			amount_cell($row['total_credit_card'],true);
		end_row();

		start_row();
			label_cell('<b>TOTAL DEBIT CARD : </b>','nowrap class=tableheader');
			amount_cell($row['total_debit_card'],true);
		end_row();

		start_row();
			label_cell('<b>TOTAL SUKI CARD : </b>','nowrap class=tableheader');
			amount_cell($row['total_suki_card'],true);
		end_row();

		start_row();
			label_cell('<b>TOTAL SRS GC: </b>','nowrap class=tableheader');
			amount_cell($row['total_srs_gc'],true);
		end_row();

		start_row();
			label_cell('<b>TOTAL OTHERS: </b>','nowrap class=tableheader');
			amount_cell($row['total_others'],true);
		end_row();

		end_table(1);

		$total_remittance = $g_total+$row['total_credit_card']+$row['total_debit_card']+$row['total_suki_card']+
		$row['total_srs_gc']+$row['total_others'];
		start_table();
		start_row();
		label_cell('<b>TOTAL REMITTANCE: </b>','nowrap class=tableheader');
		amount_cell($total_remittance,true);
		end_row();
		end_table();

		br();	
			
		div_end();
		echo "</td></tr>";

		end_table();
	}
	else if ($_GET['final'] == 1)
	{
		$sql = "SELECT * FROM ".CR_DB.TB_PREF."remittance WHERE remittance_id = ".$_GET['Remittance_ID'];
		$res = db_query_rs($sql);
		$r_row = db_fetch($res);
		
		$remittance_id = $r_row['remittance_id'];
		$cashier_id = $r_row['cashier_id'];
		$remittance_date = sql2date($r_row['remittance_date']);
		
		//====== get initial cash
		// $sql = "SELECT amount FROM ".TB_PREF."initial_cash WHERE cashier_id = $cashier_id AND i_date = '".$r_row['remittance_date']."'";
		// $res = db_query($sql);
		// $row = db_fetch($res);
		$initial_cash = 0;
		//==============
		
		//====== get total remittances and id
		$sql = "SELECT * FROM ".CR_DB.TB_PREF."remittance 
					WHERE cashier_id = $cashier_id
					AND remittance_date = '".date2sql($remittance_date)."'";
		$res = db_query_rs($sql);
		
		$total_cash = $total_credit_card = $total_debit_card = $total_suki_card = $total_srs_gc = $total_others = 0;
		$r_ids = array();
		while($row = db_fetch($res))
		{
			$total_cash += $row['total_cash'];
			$total_credit_card += $row['total_credit_card'];
			$total_debit_card += $row['total_debit_card'];
			$total_suki_card += $row['total_suki_card'];
			$total_srs_gc += $row['total_srs_gc'];
			$total_others += $row['total_others'];
			$r_ids[] = $row['remittance_id'];
		}
		
		
		//====== get cash, credit card, etc.
		$cash_description = 'Cash';
		$total_diffs = $total_others_ = $total_payments = array(); // [payment type] = array(total_cash, count)
		
		$sql = "SELECT * FROM FinishedPayments 
					WHERE LogDate = '".date2sql($remittance_date)."'
					AND UserID = $cashier_id
					AND Voided = 0
					ORDER BY Description";
		// display_error($sql);
		$res = ms_db_query($sql);
		
		// 000	Cash
		// 013	Credit Card
		// 014	Debit Card
		// 004	Customer Card
		// 016	SRSGC
		
		// 001	GC
		// 017	Terms
		// 015	Check
		// 999	OTHERS
		
		$total_payments['000'] = array();
		$total_payments['013'] = array();
		$total_payments['014'] = array();
		$total_payments['004'] = array();
		$total_payments['016'] = array();
		
		while($p_row = mssql_fetch_array($res))
		{
			if ($p_row['TenderCode'] == '000' OR $p_row['TenderCode'] == '013' OR $p_row['TenderCode'] == '014'
				 OR $p_row['TenderCode'] == '004' OR $p_row['TenderCode'] == '016')
			{
				$total_payments[$p_row['TenderCode']][0] += $p_row['Amount'];
				$total_payments[$p_row['TenderCode']][1] ++;
			}
			else
			{
				$total_others_[$p_row['TenderCode']][0] += $p_row['Amount'];
				$total_others_[$p_row['TenderCode']][1] ++;
			}
		}
		
		// $total_payments = uasort($total_payments);
		// $total_others_ = uasort($total_others_);
		
		display_heading('Cashier Name : '.$r_row['cashier_name']);
		display_heading('Date : '.sql2date($r_row['remittance_date']));
		br();
		
		// display_heading("<font color=red><b>* OVER / SHORT is not yet final until transactions are checked</b></font>");
		// hyperlink_params($path_to_root.'/sales/inquiry/sales_breakdown.php', 'Check Transactions Now', 'remittance_id='.$remittance_id);
		// br();
		start_outer_table($table_style2);
		table_section(1);
		
		display_heading('Remittance/s');
		start_table("$table_style");
		
			$th = array("Denomination", "Pieces","Line Total");

			table_header($th);
			
			$result = get_remittance_denominations(implode(',', $r_ids));
			$d_pcs_t = $d_total = $k = 0; 
			while ($myrow = db_fetch($result)) 
			{
				alt_table_row_color($k);
					
				label_cell('<b>'.number_format2($myrow[0],2).'</b>','align=center');
				label_cell($myrow[1] ? $myrow[1] : '-', 'align=center');
				
				
				if ($myrow[0] * $myrow[1] > 0)
				{
					amount_cell($myrow[0] * $myrow[1]);
					$d_total += $myrow[0] * $myrow[1];
					$d_pcs_t += $myrow[1];
				}
				else
					label_cell('-','align=center');
				
				end_row();
			}
			label_cell('<b>TOTAL CASH</b>','align=center');
			label_cell("<b>$d_pcs_t</b>",'align=center');
			amount_cell($d_total,true);
		
		table_section(2);
		// label_row('<b>Initial Cash : '.number_format2($initial_cash,2).'</b>');
		display_heading('TOTALS');

		$total_payments['000'][2] = 'Cash';
		$total_payments['000'][3] = $total_cash;
		
		$total_payments['013'][2] = 'Credit Card';
		$total_payments['013'][3] = $total_credit_card;
		
		$total_payments['014'][2] = 'Debit Card';
		$total_payments['014'][3] = $total_debit_card;
		
		$total_payments['004'][2] = 'SUKI Card';
		$total_payments['004'][3] = $total_suki_card;
		
		$total_payments['016'][2] = 'SRS GC';
		$total_payments['016'][3] = $total_srs_gc;
			
		start_table("$table_style");
		$th = array("Type", "Reading", "Tendered", "Diff.", 'SHORT', 'OVER', "# of trans");
		
		table_header($th);
			
		$k = 0;
		$t_p_totals = array();
		
		foreach($total_payments as $type => $payment)
		{
			$diff = $payment[3] - $payment[0];
			
			$total_diffs[] = $diff;

			$t_p_totals[0] += $payment[0];
			$t_p_totals[1] += $payment[3];
			$t_p_totals[2] += $diff;
			$t_p_totals[5] += $payment[1];

			alt_table_row_color($k);
			label_cell('<b>'.$payment[2].'</b>');
			if ($payment[0] != 0)
				amount_cell($payment[0]);
			else
				label_cell('---','align=center');
			if ($payment[3] != 0)
				amount_cell($payment[3]);
			else
				label_cell('---','align=center');
			
			if ($diff < 0)
			{
				label_cell('<font color=red>('.number_format2(abs($diff),2).')</font>', 'align=right');
				amount_cell(abs($diff));
				label_cell('---','align=center');
				$t_p_totals[3] += abs($diff);
			}
			else if ($diff > 0)
			{
				amount_cell($diff);
				label_cell('---','align=center');
				amount_cell(abs($diff));
				$t_p_totals[4] += abs($diff);
			}
			else
			{
				// amount_cell($diff);
				label_cell('---','align=center');
				label_cell('---','align=center');
				label_cell('---','align=center');
			}
			
			if ($payment[1] != 0)
				label_cell($payment[1], 'align=right');
			else
				label_cell('---','align=center');
			
			end_row();
		}
		
		alt_table_row_color($k);
			label_cell("<b>TOTAL :</b>");
			
			if ($t_p_totals[0] != 0)
				amount_cell($t_p_totals[0],true);
			else
				label_cell('---','align=center');
			if ($t_p_totals[1] != 0)
				amount_cell($t_p_totals[1],true);
			else
				label_cell('---','align=center');
			
			if ($t_p_totals[2] > 0)
				amount_cell($t_p_totals[2],true);
			else
				label_cell('<font color=red><b>('.number_format2(abs($t_p_totals[2]),2).')</b></font>', 'align=right');
			
			if ($t_p_totals[3] != 0)
				amount_cell($t_p_totals[3],true);
			else
				label_cell('---','align=center');
			
			if ($t_p_totals[4] != 0)
				amount_cell($t_p_totals[4],true);
			else
				label_cell('---','align=center');

			label_cell($t_p_totals[5], 'align=right');
		end_row();
		
		end_table(1);
		
		$total_diffs[5] = 0;
		if ($total_others > 0 OR count($total_others_) > 0)
		{
			display_heading('OTHERS');
			start_table("$table_style");
				$th = array("Type", "Reading","Tendered", "Diff.", 'SHORT', 'OVER', "# of trans");
				
				table_header($th);
					
				$t_count  = $t_total = $k = 0; 
				foreach($total_others_ as $type => $payment)
				{
					if ($type == '')
					$t_total += $payment[0];
					$t_count += $payment[1];
					alt_table_row_color($k);
						label_cell($payment[2]);
						amount_cell($payment[0]);
						label_cell($payment[1], 'align=right');
					end_row();
				}
				
				alt_table_row_color($k);
					$others_diff = $total_others-$t_total;
					$total_diffs[5] = $others_diff;
					label_cell("<b>TOTAL :</b>");
					amount_cell($t_total,true);
					amount_cell($total_others,true);
					amount_cell($others_diff,true);
					
					if ($total_others-$t_total < 0) //short
					{
						amount_cell(abs($others_diff),true);
						label_cell('');
					}
					else
					{
						label_cell('');
						amount_cell(abs($others_diff),true);
					}
				label_cell("<b>$t_count</b>", 'align=right');
				end_row();
			
			end_table(1);
		}	
		
		display_heading('TOTAL OVER/SHORT');
		start_table("$table_style");
		$th = array("Cash", "Credit Card", "Debit Card", "SUKI Card", 'SRS GC', 'OTHERS', 'TOTAL');
		
		table_header($th);
			
		alt_table_row_color($k);
		$os_grand_total = 0;
		// var_dump($total_diffs);
		foreach($total_diffs as $tot)
		{
			$os_grand_total += $tot;
			if ($tot >= 0)
				amount_cell(abs($tot),true);
			else
				label_cell('<font color=red><b>('.number_format2(abs($tot),2).')</b></font>', 'align=right');
		}
		if ($os_grand_total >= 0)
			label_cell('<b>'.number_format2(abs($os_grand_total),2).'</b>','align=right');
		else
			label_cell('<font color=red><b>('.number_format2(abs($os_grand_total),2).')</b></font>', 'align=right');
		end_row();
		end_table(1);
		
		echo '<center><font color=red><i>*red means shortage</i></font><center>';
		end_outer_table(1);
		
		// display_note(print_document_link($_GET['AddedRID'], _("&Print This Remittance"), true, 888));
		exit();
	}
	
	// display_footer_exit();
}

end_page(true);

?>