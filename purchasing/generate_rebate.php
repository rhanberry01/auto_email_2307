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

$seconds = 18000; // 5 hours
if (isset($_GET['nmhgsdrdntgdsad']))
	header('Refresh: '.$seconds);

$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/db/audit_trail_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Create or View Monthly Rebates"), false, false, "", $js); 

$import_errors = array();
//===================================================
function get_apv_total_using_del_date($date_, $supplier_id)
{
	$start = begin_month($date_);
	$end = end_month($date_);
	
	$sql = "SELECT SUM(ov_amount+ov_gst) FROM ".TB_PREF."supp_trans 
			WHERE del_date >= '".date2sql($start)."'
			AND del_date <= '".date2sql($end)."'
			AND supplier_id = $supplier_id
			AND type = 20"; // apv only
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

function check_for_rebate_debit_memo($date_, $supplier_id)
{

	$sql = "SELECT * FROM ".TB_PREF."supp_trans 
				WHERE type = ".ST_SUPPDEBITMEMO."
				AND supplier_id = $supplier_id
				AND tran_date = '". date2sql(add_days(Today(), 1))."'
				AND supp_reference LIKE '%~rebate%'";
	$res = db_query($sql);
	return (db_num_rows($res) == 0);

}

function create_rebate_debit_memo($date_, $supplier_id, $percentage)
{
	global $Refs;
	
	if (!check_for_rebate_debit_memo($date_, $supplier_id))
		return false;
	
	$year = date("Y");
	$month = date("n");
	$day = date("j");
	
	$apv_total = get_apv_total_using_del_date($date_, $supplier_id);
	
	if ($apv_total == 0)
		return false;
		
	$debit_memo_amount = $apv_total * ($percentage/100);
	$supp_reference = '~rebate_'.$month.'-'.$year;
	$reference = $Refs->get_next(ST_SUPPDEBITMEMO);
	$tran_date = add_days(Today(), 1);
	$particulars = 'Rebate for the month of '. date('F', strtotime(date2sql(Today()))) ." $year.";
	
	$trans_no = add_supp_trans(ST_SUPPDEBITMEMO, $supplier_id, $tran_date,  '',
			$reference, $supp_reference, -$debit_memo_amount, 0, 0, "", 0, 0);
	
	$co = get_company_prefs();
	
	add_gl_trans_supplier(ST_SUPPDEBITMEMO, $trans_no, $tran_date, $co['creditors_act'], 0, 0, //ACCOUNTS PAYABLE DITO
		$debit_memo_amount, $supplier_id,"The general ledger transaction for the control total could not be added",0, $particulars);
	
	add_gl_trans_supplier(ST_SUPPDEBITMEMO, $trans_no, $tran_date, $co['rebate_act'], 0, 0, //REBATE ACCOUNT
		-$debit_memo_amount, $supplier_id,"The general ledger transaction for the control total could not be added",0, $particulars);
		
	add_comments(ST_SUPPDEBITMEMO, $trans_no, $tran_date, $cart->memo_);

	$Refs->save(ST_SUPPDEBITMEMO, $trans_no, $reference);
}
//===================================================
//XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
//===================================================
start_form();

if (isset($_POST['start_generation']) OR isset($_GET['nmhgsdrdntgdsad']))
{
	$preloader_gif = $path_to_root.'/themes/modern/images/ajax-loader.gif';
	echo "<div id='ploader' style='display:none'>
								<img src='$preloader_gif'>
				</div>";
	
	display_notification("Checking for end of month rebates every $seconds seconds.");
	if (isset($_POST['start_generation']))
		meta_forward($_SERVER['PHP_SELF'], "nmhgsdrdntgdsad=zxcnnpdfsbyu");
	

	// global $Ajax;
	set_time_limit(0);
	
	$date2use = begin_month(Today());
	
	if ((Today() == end_month(Today()))) // if today is the last day of the month
	{
		$sql = "SELECT * FROM ".TB_PREF."rebates
					WHERE start_date <= '".date2sql(Today())."'
					AND end_date >= '".date2sql(Today())."'";
		$res = db_query($sql);
		
		while ($row = db_fetch($res))
		{
			create_rebate_debit_memo(Today(), $row['supplier_id'], $row['percentage']);
		}
		//========================================= END 
		$date2use = add_days(Today(), 1);
	}
		//== display debit memos created
		div_start('items_table');
		
		display_heading('Recently Added Debit Memos');

		start_table("$table_style2 width=80%");
		$th = array("Trxn #", "Supplier", 'Date', 'Amount');
		table_header($th);
		$k = 0;
		
		$sql = "SELECT * FROM ".TB_PREF."supp_trans
					WHERE supp_reference LIKE '%~rebate%'
					AND tran_date = '".date2sql($date2use)."'";
		$res = db_query($sql);			
		
		while ($row = db_fetch($res))
		{
			alt_table_row_color($k);
			label_cell(get_gl_view_str($row["type"], $row["trans_no"], $row['reference']));
			label_cell(get_supplier_name($row['supplier_id']));
			label_cell(sql2date($row['tran_date']));
			amount_cell(abs($row['ov_amount']),true);
			end_row();
		}
		
		end_table();
		div_end();
}

//===================================================

else
{
	start_table("class='tablestyle_noborder'");
	start_row();

	submit_cells('start_generation', _("Start Creating Rebates Every Month"),'',_('Start'));

	end_row();

	end_table(1);
}

end_form();
end_page();

?>
