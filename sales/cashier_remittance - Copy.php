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

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = '';
if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}

page('Cashier Remittance', false, false, "", $js);

function insert_remittance($cashier_id, $treasurer_id)
{
	global $path_to_root;
	// check if cashier has a final remittance already
	$sql = "SELECT * FROM ".TB_PREF."remittance
			WHERE cashier_id = $cashier_id
			AND remittance_date = '".date2sql(Today())."'
			AND final_remittance = 1";
	$res = db_query($sql);
	
	if (db_num_rows($res) > 0)
	{
		display_error("Final remittance for this cashier has already been entered");
		hyperlink_no_params($path_to_root . "/sales/cashier_remittance.php", _("Enter Another Remittance"));
	
		display_footer_exit();
		return false;
	}
	// ===================================
	
	$sql = "INSERT INTO ".TB_PREF."remittance (remittance_date, cashier_id, treasurer_id, total_amount, final_remittance)
				VALUES ('".date2sql(Today())."',$cashier_id, $treasurer_id,".input_num('g_total').",".($_POST['remittance_type']+0).")";
	db_query($sql,'failed to insert remittance header');

	$sql = "SELECT * FROM ".TB_PREF."denominations";
	$result = db_query($sql,"could not get denominations");

	$remittance_id = db_insert_id();
	
	while ($myrow = db_fetch($result)) 
	{
		$quantity =  input_num('deno'.$myrow["id"]);
		if ($quantity > 0)
			insert_remittance_details($remittance_id, $myrow["denomination"], $quantity);
	}

	return array($remittance_id,$_POST['remittance_type']+0);
}

function insert_remittance_details($remittance_id, $denomination, $quantity)
{
	$sql = "INSERT INTO ".TB_PREF."remittance_details (remittance_id, denomination, quantity)
				VALUES ($remittance_id, $denomination, $quantity)";
	db_query($sql,'failed to insert remittance details');
}

function insert_finished_payments($remittance_id, $row)
{
	// display_error($row['DateTime']);
	
	// 2012-09-26 17:01:58
	// display_error(sql2date($row['DateTime']));
	$sql = "INSERT INTO ".TB_PREF."finishedpayments (
					`remittance_id`, `TransactionNo`, `LineID`, `TenderCode`, `Description`, `Amount`, 
					`Cash`, `Change`, `ChargeToAccount`, `Validate`, `AccountNo`, 
					`ApprovalNo`, `Remarks`, `Shift`, `UserID`, `TerminalNo`, `BranchCode`, 
					`DateTime`, `Voided`, `Layaway`, `LayawayNumber`, `Deposit`)
				VALUES(
					$remittance_id, ".$row['TransactionNo'].", ".$row['LineID'].", ".$row['TenderCode'].", ".db_escape($row['Description']).", ".$row['Amount'].",
					".db_escape($row['Cash']+0) .", ".db_escape($row['Change']+0).", ".db_escape($row['ChargeToAccount']+0).", ".db_escape($row['Validate']+0).", 
					".db_escape($row['AccountNo']).", ".db_escape($row['ApprovalNo']).", ".db_escape($row['Remarks']).", ".db_escape($row['Shift']+0).", 
					".db_escape($row['UserID']).", ".db_escape($row['TerminalNo']).", ".db_escape($row['BranchCode']).", ".
					db_escape(date('Y-m-d H:i:s', strtotime($row['DateTime']))).", 
					".db_escape($row['Voided']+0).", ".db_escape($row['Layaway']+0).", ".db_escape($row['LayawayNumber']+0).", ".db_escape($row['Deposit']+0).")";
	db_query($sql,'failed to insert finished payment');
}
//==================================================================
function ado_num_fields( $rs )
    {
        return $rs->Fields->Count;
    }
	
function ado_fetch_array($rs,  $row_number = -1 )
{
	// global $ADO_NUM, $ADO_ASSOC, $ADO_BOTH;
	if( $row_number > -1 ) // Defined in adoint.h - adBookmarkFirst    = 1
		$rs->Move( $row_number, 1 );
	
	if( $rs->EOF )
		return false;
	
	$ToReturn = array();
	for( $x = 0; $x < ado_num_fields($rs); $x++ )
	{
		// if( $result_type == $ADO_NUM || $result_type == $ADO_BOTH )
			$ToReturn[ $x ] = $rs->Fields[$x]->Value;
		// if( $result_type == $ADO_ASSOC || $result_type == $ADO_BOTH )
			$ToReturn[ $rs->Fields[$x]->Name ] = $rs->Fields[$x]->Value;
	}
	$rs->MoveNext();
	return $ToReturn;
}


	
//==================================================================

if (isset($_GET['AddedRID']))
{
	
	display_notification_centered(_("Remittance # ".$_GET['AddedRID']." has been successfully entered."));
	
	if ($_GET['final'] == 1)
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
		$sql = "SELECT total_amount,remittance_id FROM ".TB_PREF."remittance 
					WHERE cashier_id = $cashier_id
					AND remittance_date = '".date2sql($remittance_date)."'";
		$res = db_query($sql);
		
		$total_remittances = 0;
		$r_ids = array();
		while($row = db_fetch($res))
		{
			$total_remittances += $row['total_amount'];
			$r_ids[] = $row['remittance_id'];
		}
		
		
		//====== get cash, credit card, etc.
		$cash_description = 'Cash';
		$total_payments = array(); // [payment type] = array(total_amount, count)
		
		$sql = "SELECT * FROM FinishedPayments 
					WHERE LogDate = '".date2sql(Today())."'
					AND UserID = $cashier_id
					AND Voided = 0
					ORDER BY Description";

		$res = ms_db_query($sql);
		
		while($p_row = mssql_fetch_array($res))
		{
			$total_payments[$p_row['Description']][0] += $p_row['Amount'];
			$total_payments[$p_row['Description']][1] ++;
		}
		//================================
		$over_short = $total_remittances - $initial_cash - $total_payments[$cash_description][0];
		//================================
		
		display_heading('Cashier Name : '.get_username_by_id($cashier_id));
		br();
		display_heading("<font color=red><b>* OVER / SHORT is not yet final until transactions are checked</b></font>");
		hyperlink_params($path_to_root.'/sales/inquiry/sales_breakdown.php', 'Check Transactions Now', 'remittance_id='.$remittance_id);
		br();
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
			label_cell("<b>$d_pcs_t</b>",'align=right');
			amount_cell($d_total,true);
		
		table_section(2);
		// label_row('<b>Initial Cash : '.number_format2($initial_cash,2).'</b>');
		label_row('<b>Tenders<b>', '','align=left');
		
		start_table("$table_style");
			$th = array("Type", "Amount","# of trans");
			
			table_header($th);
				
			$t_count  = $t_total = $k = 0; 
			foreach($total_payments as $type => $payment)
			{
				$t_total += $payment[0];
				$t_count += $payment[1];
				alt_table_row_color($k);
					label_cell("$type");
					amount_cell($payment[0]);
					label_cell($payment[1], 'align=right');
				end_row();
			}
			alt_table_row_color($k);
				label_cell("<b>TOTAL :</b>");
				amount_cell($t_total,true);
				label_cell('<b>'.$t_count.'</b>','align=right');
			end_row();
		
		end_table(2);
		
		
		start_table("$table_style");
			start_row();
				label_cell('<b>CASH OVER :<b>', 'align=center');
				label_cell($over_short > 0 ? "<b>".number_format2($over_short,2) ."</b>" : '-', 'align=center');
			end_row();
			start_row();
				label_cell('<b>CASH SHORT :<b>', 'align=center');
				label_cell($over_short < 0 ? "<font color=red><b>".number_format2(-$over_short,2)."</b></font>": '-', 'align=center ');
			end_row();
			
		end_outer_table(1);
		
		display_note(print_document_link($_GET['AddedRID'], _("&Print This Remittance"), true, 888));
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
	
	begin_transaction();
	$remittance = insert_remittance($_POST['cashier_id'], $treasurer_id);
	
	if (!$remittance)
		return false;
	
	// if ($remittance[1]) // final remittance // getting data per TERMINAL. (MDB file)
	// {
		// $remittance_id = $remittance[0];
		// $sql = "SELECT * FROM ".TB_PREF."remittance WHERE remittance_id = $remittance_id";
		// $res = db_query($sql);
		// $row = db_fetch($res);
		
		// $cashier_id = $row['cashier_id'];
		// $remittance_date = sql2date($row['remittance_date']);
		
		// $a_datetime = explode_date_to_dmy($remittance_date);
		
		// $sql = "SELECT SUM(total_amount) FROM ".TB_PREF."remittance WHERE cashier_id = $cashier_id AND remittance_date = '".date2sql($remittance_date)."'";
		// $res = db_query($sql);
		// $row = db_fetch($res);
		
		// $total_cash_remittance = $row[0];
		
		// $d_sql = "DELETE FROM ".TB_PREF."finishedpayments WHERE remittance_id = $remittance_id";
		// db_query($d_sql,'failed to delete finishedpayments of the remittance');
		
		// $sql = "SELECT * FROM ".TB_PREF."terminal_odbc WHERE inactive = 0";
		// $res = db_query($sql);
		
		// $errs = array();
		// while($row = db_fetch($res))
		// {
			// //============= ADO 
			// $pos_db = str_replace("/","\\", $row['file_location']);
			// // $pos_db = '\\\\kevs\\Files\\ipostemp.mdb';
			
			// try 
			// { 
				// $pos_conn = new COM('ADODB.Connection') or die("Cannot start ADO");
				// $pos_conn->Open("DRIVER={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$pos_db") ;
			// }
			// catch (exception $e) 
			// { 
				// $errs[] = 'failed to connect to Terminal # '. $row['terminal_no'] . "  - Location: ". str_replace("/","\\", $row['file_location']);
				// continue;
			// }
			
			
			// // AND DateValue(DateTime) = '".$a_datetime[2].'/'.$a_datetime[1].'/'.$a_datetime[0]."'
			// $sql = "SELECT * FROM FinishedPayments 
						// WHERE UserID = '$cashier_id'
						// AND Voided = 0";
			// $result = $pos_conn->Execute($sql);
			
			// if ($result)
			// {
				// while($pos_row = ado_fetch_array($result))
				// {
					// insert_finished_payments($remittance_id,$pos_row);
				// }
			// }
			
			// $result->Close(); 
			// $pos_conn->Close(); 
			// $result = null; 
			// $pos_conn = null;
			// //================================================
			
			// //============= ODBC
			// // $pos_db = '\\\\angelo\\SRS\\ipostemp.mdb';
			// // $pos_db = 'C:\\Documents and Settings\\Administrator\\Desktop\\srs_pos_db\\IPOSTEMP.MDB';
			// // $pos_conn = new COM('ADODB.Connection') or die("Cannot start ADO");
			// // $pos_conn->Open("DRIVER={Microsoft Access Driver (*.mdb, *.accdb)}; DBQ=$pos_db") ;
			
			// // $pos_conn = odbc_connect($row['odbc'], '', '') or die(odbc_errormsg());
			// // // $pos_conn =odbc_connect("DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$pos_db", "", "", "SQL_CUR_USE_ODBC");
			
			// // // AND Voided = 0"
			// // $date__ = explode_date_to_dmy(Today());
			// // $sql = "SELECT * FROM FinishedPayments 
						// // WHERE UserID = '$cashier_id'
						// // AND DateValue(DateTime) = #".$date__[1]."/".$date__[0]."/".$date__[2]."#";
			// // // $result = $pos_conn->Execute($sql);
			// // $result = odbc_exec($pos_conn, $sql);
		
			// // while($pos_row = odbc_fetch_array($result))
			// // {
				// // insert_finished_payments($remittance_id,$pos_row);
			// // }
			
			// // odbc_free_result($result);
			// // odbc_close($pos_conn);
			// //================================================
			
			// //====================================
		// }
		
		// confirm_trans_remittance($remittance_id);
	// }
	// if (count($errs) == 0)
	// {
		commit_transaction();
		meta_forward($_SERVER['PHP_SELF'], "AddedRID=".$remittance[0]."&final=".$remittance[1]);
	// }
	// else{
		// cancel_transaction();
		// foreach($errs as $err)
		// {
			// display_error($err);
		// }
		
		// display_error('press F5 to retry submission');
		// display_footer_exit();
	// }
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
	// $sql = "SELECT * FROM ".TB_PREF."users WHERE user_id = ".db_escape($user_id)." AND"
		// ." password=".db_escape(md5($password));
	// $res = db_query($sql, "could not get validate user login for $user_id");
	
	// if (db_num_rows($res) == 0)
	// {
		// display_error('Invalid user OR Wrong Password');
		// set_focus($_POST['cashier_username']);
		// unset($_POST['compute']);
	// }
	
	// $row = db_fetch($res);
	
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
	$Ajax->activate('tr_login');
}

if (isset($_POST['reset']))
{
	global $Ajax;
	
	unset($_POST['compute']);
	
	$Ajax->activate('cs_login');
	$Ajax->activate('items');
	$Ajax->activate('tr_login');
}

$canceller = false;
if (list_updated('remittance_type'))
{
	if ($_POST['remittance_type'] == 1) //  final remittance. check for all open terminals
	{
		
		$sql = "SELECT COUNT(*) FROM OpenTerminals
					WHERE Status != 3
					AND LogDate = '".date2sql(Today())."'";
		$res = ms_db_query($sql);
		display_error($sql);
		$row = mssql_fetch_array($res);
			display_error($row[0]);
		if ($row[0] > 0)
		{
			display_error('Some Terminals are not yet Processed. All Terminals must be processsed before using final remittance');
			$canceller = true;
			$Ajax->activate('items');
		}
	}
}

div_start('cs_login');
start_outer_table("$table_style2");
table_section(1);

$items = array();
$items['-1'] = 'Choose Remittance Type';
$items['0'] = 'Partial Remittance';
$items['1'] = 'Final Remittance';

label_row('<b>Date :</b>','<b>'.Today().'</b>');
if (!isset($_POST['compute']))
{
	label_row('<b>Remittance Type: </b>', array_selector('remittance_type', null, $items, array('select_submit'=> true,'async' => true)));

	text_row('<b>Cashier Login ID :</b>','cashier_username');
	password_row('<b>Cashier Password :</b>', 'cashier_pass');
}
else
{
	hidden('remittance_type', $_POST['remittance_type']);
	
	hidden('cashier_id', $_POST['cashier_id']);
	hidden('cashier_username', $_POST['cashier_username']);
	hidden('cashier_name', $_POST['cashier_name']);
	
	label_row('<b>Remittance Type: </b>',$items[$_POST['remittance_type']]);
	
	label_row('<b>Cashier Name :</b>',$_POST['cashier_name']);
	
}


end_outer_table(1);
div_end();

div_start('items');

if ($canceller)
	display_footer_exit();
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
		qty_cells('','deno'.$myrow["id"], null, null, null, 0);
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
		label_cell('<b>TOTAL AMOUNT :</b>','align=right colspan=2');
		hidden('g_total',$g_total);
		amount_cell($g_total,true);
	end_row();
}

end_table(1);
	
if (!isset($_POST['compute']))
	submit_center('compute', 'Ask Approval', true, false, true);
else
	submit_center('reset', 'Edit Again', true, false, true);
	
	
br();
div_end();

div_start('tr_login');
if (isset($_POST['compute']))
{
	start_outer_table("$table_style2");
	table_section(1);
	text_row('<b>Treasurer Username :</b>','treasurer_username');
	password_row('<b>Treasurer Password :</b>', 'treasurer_pass');
	end_outer_table(1);
	submit_center('approve', 'Approve', true, false, false);
}
div_end();

end_form();
end_page();

?>