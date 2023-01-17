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
		global $installed_extensions;
		// $this->application("orders", _($this->help_context = "&Sales"));
	
		// $this->add_module(_("Transactions"));

		// // $this->add_lapp_function(0, _("Initial Cash"),"sales/initial_cash.php", 'SA_SALESORDER');
		// $this->add_lapp_function(0, _("Cashier Remittance"),"sales/cashier_remittance.php?NewOrder=Yes", 'SA_SALESORDER');
		// $this->add_lapp_function(0, _("Cashier Remittance (Same Page Approval)"),"sales/cashier_remittance_fast_approval.php?NewOrder=Yes", 'SA_SALESORDER');
		// $this->add_lapp_function(0, _("Approve Remittance"),"sales/inquiry/approve_remittance.php", 'SA_SALESTRANSVIEW');

		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(0, _("Daily Sales"),"sales/inquiry/daily_sales.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(0, _("Remittance Inquiry"),"sales/inquiry/remittance_inquiry.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(0, _("Remittance Summary per Day"),"sales/inquiry/remittance_summary_per_day.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(0, _("Non-Cash Remittance Details"),"sales/inquiry/noncash_transaction.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(0, _("Cashier Remittance Shortage"),"sales/inquiry/cashier_remittance_shortage.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(0, _("Cashier Remittance Over"),"sales/inquiry/cashier_remittance_over.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(0, _("Counter Summary"),"sales/inquiry/counter_summary.php", 'SA_SALESTRANSVIEW');

		
		$this->add_rapp_function(0, _("Cash Deposit"),"sales/inquiry/cash_deposit.php", 'SA_SALESTRANSVIEW');		
		$this->add_rapp_function(0, _("Credit/Debit Card Reconciliation"),"sales/inquiry/acquiring_bank_deductions.php", 'SA_SALESTRANSVIEW');
		$this->add_rapp_function(0, _("Credit/Debit Card Reconciliation Report"),"sales/inquiry/acquiring_bank_deduction_report.php", 'SA_SALESTRANSVIEW');
		$this->add_rapp_function(0, _("Other Income"),"sales/other_income.php?NewMemo=Yes", 'SA_CUSTOMER');
		$this->add_rapp_function(0, _("Other Income Inquiry"),"sales/inquiry/other_income_inquiry.php", 'SA_CUSTOMER');
		$this->add_rapp_function(0, _("Other Income Clearing"),"sales/inquiry/other_income_clearing.php", 'SA_SALESPAYMNT');
		
		$this->add_module(_("Maintenance"));
		// $this->add_lapp_function(1, _("Denominations"),"sales/manage/denomination.php", 'SA_BANKACCOUNT');
		$this->add_lapp_function(1, _("Acquiring Banks"),"sales/manage/acquiring_bank.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(1, _("Tender Types"),"sales/manage/tender_types.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(1, _("Wholesale Cashiers"),"sales/manage/wholesale_cashiers.php", 'SA_SALESTRANSVIEW');
		$this->add_lapp_function(1, _("Customers"),"sales/manage/customers.php", 'SA_CUSTOMER');
		$this->add_rapp_function(1, _("Sales GL Account Maintenance"),"sales/manage/sales_gl_account_setup.php", 'SA_SALESTRANSVIEW');
		if (count($installed_extensions) > 0)
		{
			foreach ($installed_extensions as $mod)
			{
				if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "orders")
					$this->add_rapp_function(2, $mod["title"], 
						"modules/".$mod["path"]."/".$mod["filename"]."?",
						isset($mod["access"]) ? $mod["access"] : 'SA_OPEN' );
			}
		}
	}
}

?>