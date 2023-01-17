<?php
// Test CVS

$page_security = 'SA_JOURNALENTRY';
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
// require_once $path_to_root . '/phpexcelreader/Excel/reader.php';
require_once $path_to_root . '/gl/includes/excel_reader2.php';
require_once $path_to_root . '/dimensions/includes/dimensions_db.inc';


$js = '';

if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}

page('Import Journal', false, false, "", $js);

// function void_all_on_consolidated()
// {
	// $sql = "SELECT DISTINCT type_no 
			// FROM srs_aria_z_reporting.0_gl_trans
			// WHERE memo_ LIKE 'import branch ending balance - %'
			// AND amount != 0
			// AND type = 0";
	// $res = db_query($sql);
	
	// while($row = db_fetch($res))
	// {	
		// $x = void_transaction('0', $row['type_no'], Today(), 'will generate a new one');
		// // display_error($x . $row['type_no']);
	// }
// }

function clear_all()
{
	$sql = "TRUNCATE srs_aria_z_reporting.0_acquiring_deductions"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_adjustment_header"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_areas"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_attachments"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_audit_trail"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_bank_trans"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_books_disbursements"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_books_purchase"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_books_receipts"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_books_sales"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_budget_trans"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cash_deposit"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cash_deposit_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_check_account"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_check_issue_batch"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_check_issue_batch_cv"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_check_trans"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cheque_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_comments"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cons_sales_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cons_sales_header"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_csv"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_csv_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cust_allocations"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cv_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cv_header"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cwo_header"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_debtor_trans"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_debtor_trans_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_dep_exp_depreciation_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_dep_exp_fixed_assets"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_discrepancy_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_discrepancy_header"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_disposed_fixed_asset"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_finishedpayments"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_finishedsales"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_finishedtransaction"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_gl_trans"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_gl_trans_temp"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_grn_batch"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_grn_items"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_initial_cash"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_item_codes"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_item_units"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_loc_stock"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_other_income_payment_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_other_income_payment_header"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_other_income_receivable"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_prices"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_prices_per_customer"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_print_profiles"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_printers"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_purch_data"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_purch_order_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_purch_orders"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_quick_entries"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_quick_entry_lines"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_rebates"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_refs"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_remittance"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_remittance_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_sales_debit_credit"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_sales_gl_accounts"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_sales_order_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_sales_orders"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_salesman"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_salestotals"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_salestotals_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_sdma"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_stock_master"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_stock_moves"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_supp_allocations"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_supp_invoice_items"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_supp_trans"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_trans_tax_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_transformation_details"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_transformation_header"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_voided"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_cust_branch"; 
	db_query($sql,'failed on truncate');
	$sql = "TRUNCATE srs_aria_z_reporting.0_debtors_master"; 
	db_query($sql,'failed on truncate');
	$sql = "UPDATE srs_aria_z_reporting.0_sys_types SET type_no = 0, next_reference = 1"; 
	db_query($sql,'failed on truncate');
}

function create_month_ending($year, $branch_db_, $dimension_id, $branch_name)
{
	global $Refs;
	
	// $date_ = "01/01/$year";
	$trans_type = ST_JOURNAL;
	
	$trans_ids = array();
	for($i=1;$i<=12;$i++)
	{
		$date_start = str_pad($i, 2, '0', STR_PAD_LEFT) ."/01/$year";
		$date_end = end_month($date_start);
		
		// display_notification($date_start . ' -> '. $date_end);die;
		$memo_ = 'import branch ending balance - ' . $date_end . " ($branch_name)";
		
		$sql = "SELECT account, SUM(round(amount,2)) as amt
					FROM $branch_db_.0_gl_trans
					WHERE tran_date >= '".date2sql($date_start)."'
					AND  tran_date <= '".date2sql($date_end)."'
					GROUP BY account
					HAVING SUM(amount) != 0";
		// display_error($sql);
		// return false;
		$res = db_query($sql);
		
		// display_notification($sql . '; ~~~ '.db_num_rows($res));
		if (db_num_rows($res) == 0)
			continue;
		
		$trans_id = get_next_trans_no($trans_type);
		$ref   = $Refs->get_next(0);
		// display_notification($trans_id .' <<<<<<<<<<<<<<<<<<');

		while($row = db_fetch($res))
			add_gl_trans($trans_type, $trans_id, $date_end, $row['account'], $dimension_id, 0, $memo_, $row['amt']); 

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $date_end, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $date_end);
		
		$trans_ids[] = $trans_id;
	}
	
	// display_notification('eto count : '. count($trans_ids));
	return implode(' , ', $trans_ids);
	// =============================================================================
}

if (isset($_POST['upload'])) 
{
	include_once($path_to_root . "/admin/db/voiding_db.inc");

	$debit = $credit = 0;
	
	// ============================= set GL to ZERO ==================================
	
	begin_transaction();
	clear_all();
	
	$count = 0;
	
	foreach($db_connections as $key=>$db_con)
	{
		// if($key != 22) // for checking a branch
				// continue;
		if (!isset($db_con['dimension_ref']) OR $db_con['dimension_ref'] === '')
			continue;
			
		$count ++;
		$sql = "SELECT DISTINCT YEAR(tran_date) 
					FROM ".$db_con['dbname'].".0_gl_trans 
					WHERE tran_date  >= '2016-01-01'
					ORDER BY 1
					";
		// display_error ($sql);
		// continue;
		$res = db_query($sql);
		
		$dimension_id = get_dimension_id($db_con['dimension_ref']);
		
		display_notification($db_con['srs_branch'] .'----------------------------------------------------------------------');
		while($row = db_fetch($res))
		{
			
			$jv_id = create_month_ending($row[0],$db_con['dbname'],$dimension_id, $db_con['srs_branch']);
			
			if ($jv_id)
				display_notification($db_con['srs_branch'] .' ~ '. $row[0] . ' ~ # '. $jv_id);
		}
		
	}
	commit_transaction();
}

start_form();
submit_center('upload','JV MONTHLY Ending Balance to consolidated branch');
end_form();

end_page();

?>