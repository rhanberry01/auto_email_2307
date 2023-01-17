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

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

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
if ($_GET['final'] == 1)
page('Cashier Remittance', true, true, "", $js);

else
page('Cashier Remittance', false, false, "", $js);
// echo 'E0159 . E1573';
// $_POST['cashier_username'] = 'E0159';


	
//==================================================================

if (isset($_GET['AddedRID']))
{
	
	if (!isset($_GET['final']) OR $_GET['final'] == false)
		display_notification_centered(_("Remittance # ".$_GET['AddedRID']." has been successfully entered."));
	
	else if ($_GET['final'] == 1)
	{
		$sql = "SELECT * FROM ".TB_PREF."remittance WHERE remittance_id = ".$_GET['AddedRID'];
		$res = db_query($sql);
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
		$sql = "SELECT * FROM ".TB_PREF."remittance 
					WHERE cashier_id = $cashier_id
					AND remittance_date = '".date2sql($remittance_date)."'";
		$res = db_query($sql);
		
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
			
			$sql = "SELECT a.denomination, SUM(b.quantity)
						FROM 0_remittance_details b
						RIGHT OUTER JOIN 0_denominations a ON a.denomination =  b.denomination
						AND remittance_id IN (".implode(',', $r_ids).")
						GROUP BY a.denomination
						ORDER BY a.denomination DESC";
			$result = db_query($sql,"could not get denomination count");
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
				amount_cell(number_format2(abs($tot),2),true);
			else
				label_cell('<font color=red><b>('.number_format2(abs($tot),2).')</b></font>', 'align=right');
		}
		if ($os_grand_total >= 0)
			label_cell('<b>'.number_format2(abs($os_grand_total),2).'</b>','align=right');
		else
			label_cell('<font color=red><b>('.number_format2(abs($os_grand_total),2).')</b></font>', 'align=right');
		end_row();
		end_table(1);
		
		// start_table("$table_style");
			// start_row();
				// label_cell('<b>OVER (CASH):<b>', 'align=left');
				// label_cell($over_short > 0 ? "<b>".number_format2($over_short,2) ."</b>" : '-', 'align=center');
			// end_row();
			// start_row();
				// label_cell('<b>SHORT (CASH):<b>', 'align=left');
				// label_cell($over_short < 0 ? "<font color=red><b>".number_format2(-$over_short,2)."</b></font>": '-', 'align=center ');
			// end_row();
			
		end_outer_table(1);
		
		display_note(print_document_link($_GET['AddedRID'], _("&Print This Remittance"), true, 888));
		exit();
	}
	
	hyperlink_no_params($path_to_root . "/sales/cashier_remittance.php", _("Enter Another Remittance"));
	display_footer_exit();
}

start_form();

if (isset($_POST['approve']))
{
	$user_id = $_POST['treasurer_username'];
	$password = $_POST['treasurer_pass'];
	
	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id = ".db_escape($user_id)." 
			AND password=".db_escape(md5($password))."
			AND can_approve_sales_remittance = 1";
	
	// display_error($sql);
	$res = db_query($sql, "could not get validate user login for $user_id");
	
	if (db_num_rows($res) == 0)
	{
		display_error('Invalid user OR Wrong Password for treasurer');
		set_focus($_POST['treasurer_username']);
		return false;
	}
	
	$row = db_fetch($res);
	$treasurer_id = $row['id'];
	$treasurer_name = $row['real_name'];
	
	// display_error('remittance_date : '.$_POST['remittance_date']);
	// display_error('cashier_id : '.$_POST['cashier_id']);
	// display_error('cashier : '.$_POST['cashier_name']);
	// display_error('treasurer_id : '.$treasurer_id);
	// display_error('treasurer: '.$treasurer_name);
	// display_error('total: '.input_num('g_total'));
	// display_error('remarks: '.$_POST['remarks']);
	
	begin_transaction();
	$remittance = insert_remittance($_POST['cashier_id'], $treasurer_id,$treasurer_name);
	
	if (!$remittance)
		return false;
	
	commit_transaction();
	meta_forward($_SERVER['PHP_SELF'], "AddedRID=".$remittance."&final=0");
}

if (isset($_POST['compute']))
{
	global $Ajax;
	
	if ($_POST['remittance_type'] == -1)
	{
		display_error('Please Choose a Remittance Type.');
		set_focus($_POST['remittance_type']);
		unset($_POST['compute']);
	}
	
	$user_id = $_POST['cashier_username'];
	$password = $_POST['cashier_pass'];
	
	// CHECK USER FROM MARKUSERS TABLE IN MSSQL
	
	$sql = "SELECT * FROM MarkUsers 
				WHERE loginid = '".ms_escape_string($user_id)."'
				AND password = '".ms_escape_string($password)."'";
	$res = ms_db_query($sql, "could not get login id: $user_id and password: $password");
	
	if (mssql_num_rows($res) == 0)
	{
		display_error('Invalid ID OR Password');
		set_focus($_POST['cashier_username']);
		unset($_POST['compute']);
	}
	
	$row = mssql_fetch_array($res);
	
	$_POST['cashier_id'] = $row['userid'];
	$_POST['cashier_name'] = $row['name'];
	

	$Ajax->activate('cs_login');
	$Ajax->activate('items');
	$Ajax->activate('others');
	$Ajax->activate('tr_login');
}

if (isset($_POST['reset']))
{
	global $Ajax;
	
	unset($_POST['compute']);
	
	$Ajax->activate('cs_login');
	$Ajax->activate('items');
	$Ajax->activate('others');
	$Ajax->activate('tr_login');
}

$canceller = false;
start_table($table_style);

echo "<tr valign=top><td width=25%>";

div_start('cs_login');
br();
display_heading('CASHIER');
br();
start_outer_table("$table_style2");
table_section(1);

// $items = array();
// $items['-1'] = 'Choose Remittance Type';
// $items['0'] = 'Partial Remittance';
// $items['1'] = 'Final Remittance';

// label_row('<b>Date :</b>','<b>'.Today().'</b>');
if (!isset($_POST['compute']))
{
	// label_row('<b>Remittance Type: </b>', array_selector('remittance_type', null, $items, array('select_submit'=> true,'async' => true)));
	date_row('Date:','remittance_date');
	text_row('<b>Login ID :</b>','cashier_username');
	password_row('<b>Password :</b>', 'cashier_pass');
}
else
{
	hidden('remittance_type', $_POST['remittance_type']);
	
	hidden('cashier_id', $_POST['cashier_id']);
	hidden('cashier_username', $_POST['cashier_username']);
	hidden('cashier_name', $_POST['cashier_name']);
	hidden('remittance_date', $_POST['remittance_date']);
	
	// label_row('<b>Remittance Type: </b>',$items[$_POST['remittance_type']]);
	label_row('<b><font color=red>Date :</font></b>','<b><font color=red>'.$_POST['remittance_date'].'</font></b>');
	label_row('<b>Name :</b>','<b>'.$_POST['cashier_name'].'</b>');
}

// echo 'E1573';
end_outer_table(1);
div_end();

echo "</td><td width=25%>";

div_start('items');
br();
display_heading('CASH');
br();
// if ($canceller)
	// display_footer_exit();
start_table("$table_style width=30%");
$th = array(_("Denomination"), "Pieces");

if (isset($_POST['compute']))
	$th[] = "Line Total";

table_header($th);
$g_total = $k = 0; 

$sql = "SELECT * FROM ".TB_PREF."denominations";
// if (!check_value('show_inactive')) $sql .= " WHERE !inactive";
$result = db_query($sql,"could not get denominations");

while ($myrow = db_fetch($result)) 
{
	alt_table_row_color($k);
		
	label_cell('<b>'.number_format2($myrow["denomination"],2).'</b>','align=center');
	
	if (!isset($_POST['compute']))
		small_qty_cells('','deno'.$myrow["id"], null, null, null, 0);
	else
	{
		hidden('deno'.$myrow["id"],$_POST['deno'.$myrow["id"]]);
		qty_cell($_POST['deno'.$myrow["id"]], false, 0);
		$line_total = input_num('deno'.$myrow["id"]) *$myrow["denomination"] ;
		$g_total += $line_total;
		amount_cell($line_total);
	}
	end_row();
}

if (isset($_POST['compute']))
{
	start_row();
		label_cell('<b>TOTAL AMOUNT :</b>',' colspan=2 class=tableheader');
		hidden('g_total',$g_total);
		amount_cell($g_total,true);
	end_row();
}

end_table(1);

	
// if (!isset($_POST['compute']))
	// submit_center('compute', 'Ask Approval', true, false, true);
// else
	// submit_center('reset', 'Edit Again', true, false, true);
div_end();
echo "</td><td width=25%>";
	
div_start('others');
br();
display_heading('OTHERS');
br();
	
start_table("$table_style2 width=30%");

start_row();
	label_cell('<b>TOTAL CREDIT CARD : </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_credit_card');
	else
	{
		amount_cell(input_num('total_credit_card'));
		hidden('total_credit_card', $_POST['total_credit_card']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL DEBIT CARD : </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_debit_card');
	else
	{
		amount_cell(input_num('total_debit_card'));
		hidden('total_debit_card', $_POST['total_debit_card']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL SUKI CARD : </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_suki_card');
	else
	{
		amount_cell(input_num('total_suki_card'));
		hidden('total_suki_card', $_POST['total_suki_card']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL SRS GC: </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_srs_gc');
	else
	{
		amount_cell(input_num('total_srs_gc'));
		hidden('total_srs_gc', $_POST['total_srs_gc']);
	}
end_row();

start_row();
	label_cell('<b>TOTAL OTHERS: </b>','nowrap class=tableheader');
	if (!isset($_POST['compute']))
		amount_cells('','total_others');
	else
	{
		amount_cell(input_num('total_others'));
		hidden('total_others', $_POST['total_others']);
	}
end_row();

end_table(1);

	
if (!isset($_POST['compute']))
	submit_center('compute', 'Ask Approval', true, false, true);
else
	submit_center('reset', 'Edit Again', true, false, true);
div_end();
echo "</td><td width=25%>";

div_start('tr_login');
br();
display_heading('TREASURER');
br();
if (isset($_POST['compute']))
{
	set_focus('treasurer_username');
	start_outer_table("$table_style2");
	table_section(1);
	text_row('<b>Login ID :</b>','treasurer_username');
	password_row('<b>Password :</b>', 'treasurer_pass');
	textarea_row('Remarks:', 'remarks', '', 20, 4);
	end_outer_table(1);
	submit_center('approve', 'Approve', true, false, false);
}
else
{
	echo '';
}
div_end();
echo "</td></tr>";
end_table();
end_form();
end_page();

?>