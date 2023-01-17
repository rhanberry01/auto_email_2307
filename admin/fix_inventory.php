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
$page_security = 'SA_GLSETUP';

$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Get Beginning and Ending Inventory', false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
//-----------------------------------------------------------------------------------
function get_month_beg($date_)
{
	$sql = "SELECT SUM(amount) FROM 0_gl_trans
				WHERE `account` = '1200'
				AND tran_date < '".date2sql($date_)."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

function get_month_end($date_)
{	
	$sql = "SELECT SUM(
						CASE
						WHEN pVatable = 1 THEN
							(((sellingarea + stockroom + Damaged)* costofsales)/ 1.12)
						ELSE
							((sellingarea + stockroom + Damaged)* costofsales)
						END)AS net_of_vat
				FROM	ProductsBackUp
				WHERE	CAST(BackUpDate AS DATE)= '".date2sql($date_)."'";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row['net_of_vat'];
}
function get_month_end_cainta($date_)
{	
	// $sql = "SELECT SUM(
						// CASE
						// WHEN pVatable = 1 THEN
							// (((sellingarea + stockroom + Damaged)* costofsales)/ 1.12)
						// ELSE
							// ((sellingarea + stockroom + Damaged)* costofsales)
						// END)AS net_of_vat
				// FROM	ProductsBackUp
				// WHERE	CAST(BackUpDate AS DATE)= '".date2sql($date_)."'";
	$sql = "SELECT SUM(
										CASE
										WHEN a.pVatable = 1 THEN
											(((b.sellingarea + b.stockroom + b.Damaged)* a.costofsales)/ 1.12)
										ELSE
											((b.sellingarea + b.stockroom + b.Damaged)* a.costofsales)
										END)AS net_of_vat
								FROM Products a , ProductsBackUp b
								WHERE	CAST(BackUpDate AS DATE)=  '".date2sql($date_)."'
				AND a.ProductID = b.ProductID";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row['net_of_vat'];
}

function void_all_succeeding($from_date)
{
	$sql = "SELECT DISTINCT type, type_no FROM 0_gl_trans
				WHERE `account` = '1200'
				AND tran_date >= '".date2sql($from_date)."'";
	$res = db_query($sql);
	
	
	while($row = db_fetch($res))
	{
		$type = $row['type'];
		$type_no = $row['type_no'];
		
		void_gl_trans($type, $type_no, true);
		add_audit_trail($type, $type_no, Today(), _("Voided.")."\n".'wrong_amount');
		add_voided_entry($type, $type_no, Today(), 'wrong_amount');
	}

	
}

function write_inventory_jv($end_date, $beg_amount, $end_amount)
{
	global $Refs;
	$trans_type = '0';
	$date_ = $end_date;
	$ref   = $reference = get_next_reference($trans_type);
	$memo_ = 'To take up inventory for '.date('F d, Y', strtotime($date_)).' & to reverse beginning inventory.';
	
	$trans_no = get_next_trans_no($trans_type);

    $trans_id = $trans_no;
	

	add_gl_trans($trans_type, $trans_id, $date_, '1200',0, 0, $memo_, -$beg_amount);
	add_gl_trans($trans_type, $trans_id, $date_, '1200',0, 0, $memo_, $end_amount);
	add_gl_trans($trans_type, $trans_id, $date_, '500000',0, 0, $memo_, $beg_amount-$end_amount);

	add_comments($trans_type, $trans_id, $date_, $memo_);
	$Refs->save($trans_type, $trans_id, $ref);

	add_audit_trail($trans_type, $trans_id, $date_);
}

if (isset($_POST['fix_now']))
{
	$from_date = $_POST['from'];
	$to_date = $_POST['to'];
	
	begin_transaction();
	
	void_all_succeeding($from_date);
	$times = date_diff2(begin_month($to_date), begin_month($from_date), 'm');
	
	// display_error($times);

	for($i=0;$i<=$times;$i++)
	{
		$current_month_beg = begin_month(add_months($from_date,$i));
		$current_month_end = end_month(add_months($from_date,$i));
		$next_month = begin_month(add_months($from_date,$i+1));
		// display_error($next_month);
		if ($_SESSION['wa_current_user']->company != 12)
		{
			// if ($next_month != '06/01/2015')
				$month_ending = get_month_end($next_month);
			// else
				// $month_ending = 65778842.6428;
			
			if ($month_ending == 0)
			{	
				display_error('possibly no data for '. $next_month);
				display_footer_exit();
			}
			write_inventory_jv($current_month_end, get_month_beg($current_month_beg), $month_ending);
		}
		else
		{
			if (get_month_end_cainta($next_month) == 0)
			{	
				display_error('possibly no data');
				display_footer_exit();
			}
			write_inventory_jv($current_month_end, get_month_beg($current_month_beg), get_month_end_cainta($next_month));
		}
	}
	
	commit_transaction();
	

	display_notification('DONE');
}

if(!isset($_POST['from']))
{
	$_POST['from'] = '01/01/2016';
	$_POST['to'] = '01/01/2016';
}

start_form();
start_table($table_style2);
date_row('DATE from:', 'from');
date_row('DATE to:', 'to');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
