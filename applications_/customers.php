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
class customers_app extends application 
{
	function customers_app() 
	{
		global $installed_extensions,$db_connections;
		$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		$this->application("stock", _($this->help_context = "&Items and Inventory"));

		$this->add_module(_("Transactions"));
		
		// $this->application("orders", _($this->help_context = "&Sales"));
	
		// $this->add_module(_("Transactions"));
		//$this->add_lapp_function(0, _("Cash Deposit"),"sales/inquiry/cash_deposit.php", 'SA_SALESTRANSVIEW');		
		//$this->add_lapp_function(0, _("Credit/Debit Card Reconciliation NEW"),"sales/inquiry/acquiring_bank_deductions.php", 'SA_SALESTRANSVIEW');
		// $this->add_lapp_function(0, _("Credit/Debit Card Reconciliation"),"sales/inquiry/acquiring_bank_deductions_bak.php", 'SA_SALESTRANSVIEW');
		// $this->add_rapp_function(0, _("Consignment Sales"),"sales/inquiry/consignment_sales.php", 'SA_SALESPAYMNT');
		// $this->add_rapp_function(0, _("Consignment Sales 2015"),"sales/inquiry/consignment_sales_2015.php", 'SA_SALESPAYMNT');
		// $this->add_rapp_function(0, _("Import CS"),"sales/inquiry/import_consignment_sales.php", 'SA_SALESPAYMNT');
		//$this->add_rapp_function(0, _("Other Income"),"sales/other_income.php?NewMemo=Yes", 'SA_CUSTOMER');
		//$this->add_rapp_function(0, _("Other Income Inquiry/Payment"),"sales/inquiry/other_income_inquiry.php", 'SA_CUSTOMER');
		//$this->add_rapp_function(0, _("Other Income Clearing"),"sales/inquiry/other_income_clearing.php", 'SA_SALESPAYMNT');
		//$this->add_rapp_function(0, _("Depreciation Expense (Fixed Assets)"),"sales/manage/depreciation_expense_fixedassets.php", 'SA_SALESPAYMNT');
		//$this->add_rapp_function(0, _("Fixed Asset Depreciation"),"sales/inquiry/asset_depreciation.php", 'SA_SALESPAYMNT');

		// $this->add_lapp_function(0, _("Initial Cash"),"sales/initial_cash.php", 'SA_SALESORDER');
		// $this->add_lapp_function(0, _("Cashier Remittance"),"sales/cashier_remittance.php?NewOrder=Yes", 'SA_SALESORDER');
		// $this->add_lapp_function(0, _("Cashier Remittance (Same Page Approval)"),"sales/cashier_remittance_fast_approval.php?NewOrder=Yes", 'SA_SALESORDER');
		// $this->add_lapp_function(0, _("Approve Remittance"),"sales/inquiry/approve_remittance.php", 'SA_SALESTRANSVIEW');

		// $this->add_module(_("Inquiries and Reports"));
		// $this->add_lapp_function(1, _("POS Transactions"),"sales/inquiry/pos_noncash_transaction.php", 'SA_DEPOSIT');
		// $this->add_lapp_function(1, _("Cashier Total Shortage"),"sales/inquiry/cashier_total_shortage.php", 'SA_DEPOSIT');
		// $this->add_lapp_function(1, _("Daily Sales"),"sales/inquiry/daily_sales.php", 'SA_SALESTRANSVIEW');
		// $this->add_lapp_function(1, _("Remittance Inquiry"),"sales/inquiry/remittance_inquiry.php", 'SA_SALESTRANSVIEW');
		// $this->add_lapp_function(1, _("Remittance Summary per Day"),"sales/inquiry/remittance_summary_per_day_new_version.php", 'SA_DEPOSIT');
		// $this->add_lapp_function(1, _("Remittance Summary per Day OLD"),"sales/inquiry/remittance_summary_per_day_try2.php", 'SA_DEPOSIT');
		// $this->add_lapp_function(1, _("Prepaid Card Sales"),"sales/inquiry/prepaid_card_sales.php", 'SA_DEPOSIT');
		// $this->add_lapp_function(1, _("Non-Cash Remittance Details"),"sales/inquiry/noncash_transaction.php", 'SA_DEPOSIT');
		// $this->add_lapp_function(1, _("Cashier Remittance Shortage"),"sales/inquiry/cashier_remittance_shortage.php", 'SA_DEPOSIT');
		// $this->add_lapp_function(1, _("Cashier Remittance Over"),"sales/inquiry/cashier_remittance_over.php", 'SA_SALESTRANSVIEW');
		// $this->add_lapp_function(1, _("Counter Summary"),"sales/inquiry/counter_summary.php", 'SA_SALESTRANSVIEW');
		// $this->add_lapp_function(1, _("Sales Total per Entries"),"sales/inquiry/sales_net_per_entries.php", 'SA_SALESTRANSVIEW');

		// $this->add_rapp_function(1, _("Credit/Debit Card Reconciliation Report"),"sales/inquiry/acquiring_bank_deduction_report.php", 'SA_SALESTRANSVIEW');
		// $this->add_rapp_function(1, _("Cash Deposit Summary Per Day"),"sales/inquiry/cash_deposit_report_per_day.php", 'SA_SALESTRANSVIEW');
		// $this->add_rapp_function(1, _("Daily Total Cash Deposit Summary"),"sales/inquiry/daily_cash_deposit_summary.php", 'SA_SALESTRANSVIEW');
		//$this->add_rapp_function(1, _("Fixed Assets & Depreciation Expense Report"),"sales/inquiry/depreciation_expense_report.php", 'SA_SALESTRANSVIEW');
		//$this->add_rapp_function(1, _("Monthly Total Depreciation Expense Report"),"sales/inquiry/monthly_total_depreciation_expense_report.php", 'SA_SALESTRANSVIEW');
		//$this->add_rapp_function(1, _("Other Income Summary"),"sales/inquiry/other_income_summary.php", 'SA_SALESTRANSVIEW');
		// if($_SESSION['wa_current_user']->username != 'audit') {
		// $this->add_module(_("Maintenance"));
		// $this->add_lapp_function(1, _("Denominations"),"sales/manage/denomination.php", 'SA_BANKACCOUNT');
		// $this->add_lapp_function(2, _("Acquiring Bank"),"sales/manage/acquiring_bank.php", 'SA_SALESTRANSVIEW');
		// $this->add_lapp_function(2, _("Tender Types"),"sales/manage/tender_types.php", 'SA_SALESTRANSVIEW');
		// $this->add_lapp_function(2, _("Wholesale Cashiers"),"sales/manage/wholesale_cashiers.php", 'SA_SALESTRANSVIEW');
		// $this->add_rapp_function(2, _("Customers"),"sales/manage/customers.php", 'SA_CUSTOMER');
		// $this->add_rapp_function(2, _("Sales GL Account Maintenance"),"sales/manage/sales_gl_account_setup.php", 'SA_SALESTRANSVIEW');
		// $this->add_rapp_function(2, _("Sales Person"),"sales/manage/sales_people.php", 'SA_CUSTOMER');
		// $this->add_rapp_function(2, _("Sales Area"),"sales/manage/sales_areas.php", 'SA_CUSTOMER');
		// $this->add_rapp_function(2, _("Sales Group"),"sales/manage/sales_groups.php", 'SA_CUSTOMER');
		// $this->add_rapp_function(2, _("Customer Branches"),"sales/manage/customer_branches.php", 'SA_CUSTOMER');
		
		

		// if (count($installed_extensions) > 0)
		// {
		// 	foreach ($installed_extensions as $mod)
		// 	{
		// 		if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "orders")
		// 			$this->add_rapp_function(2, $mod["title"], 
		// 				"modules/".$mod["path"]."/".$mod["filename"]."?",
		// 				isset($mod["access"]) ? $mod["access"] : 'SA_OPEN' );
		// 	}
		// }
	// }
	}
}

?>