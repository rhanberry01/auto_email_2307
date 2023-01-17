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
class general_ledger_app extends application 
{
	function general_ledger_app() 
	{
		global $installed_extensions,$db_connections;
		$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		
		// $this->application("GL", _($this->help_context = "&Banking and General Ledger"));

		$this->add_module(_("Transactions"));
		
		
		$this->add_lapp_function(0, _("&New Cash Deposit"),
		"gl/centralized_cash_deposit.php?NewPayment=Yes", 'SA_DEPOSIT');	
		$this->add_lapp_function(0, _("&New Cash Deposit Clearing"),
		"gl/inquiry/cash_deposit_clearing.php", 'SA_DEPOSIT');
		$this->add_lapp_function(0, _("&New Cash Deposit Breakdown"),
		"gl/inquiry/cash_deposit_breakdown.php", 'SA_DEPOSIT');
		$this->add_lapp_function(0, _("&New Cash Deposit Checking"),
		"gl/inquiry/cash_deposit_book.php", 'SA_DEPOSIT');
		$this->add_lapp_function(0, _("&New Cash Deposit Checking NEW"),
		"gl/inquiry/cash_deposit_book_bak.php", 'SA_DEPOSIT');
		$this->add_lapp_function(0, _("&Bank Statement Summary"),
		"gl/inquiry/cash_deposit_bank_statement.php", 'SA_DEPOSIT');
		$this->add_lapp_function(0, _("&CWO"),
		"gl/cwo.php", 'SA_DEPOSIT');	
		
		
		if($_SESSION['wa_current_user']->username != 'sheayne') {
		// $this->add_lapp_function(0, _("&CWO (Temporary-DIVISORIA)"),
		// "gl/cwo2.php", 'SA_DEPOSIT');	
		$this->add_lapp_function(0, _("&Debit Memo to OR"),
		"gl/dm_to_or.php", 'SA_DEPOSIT');	
		$this->add_lapp_function(0, _("&Debit Memo to AR"),
		"gl/dm_to_ar.php?NewPayment=Yes", 'SA_DEPOSIT');	
		
		if($_SESSION['wa_current_user']->access!=14) {
		$this->add_lapp_function(0, _("&Disbursements"),
			"gl/gl_bank.php?NewPayment=Yes", 'SA_PAYMENT');
		$this->add_lapp_function(0, _("&Bank Deposits"),
			"gl/gl_bank.php?NewDeposit=Yes", 'SA_DEPOSIT');	
		}			
		
		$this->add_lapp_function(0, _("Bank &Transfer"),
			"gl/bank_transfer.php?", 'SA_BANKTRANSFER');
		
		$this->add_lapp_function(0, _("Create DM / Journal from Returns and BO"),
			"gl/process_returns_bo.php?", 'SA_BANKTRANSFER');
			
		// $this->add_lapp_function(0, _("Check Management"),
			// "gl/inquiry/check_management.php?", 'SA_GLANALYTIC');
		$this->add_rapp_function(0, _("&Journal Voucher 2017 ONLY"),
			"gl/gl_journal_2017.php?NewJournal=Yes", 'SA_JOURNALENTRY');
		$this->add_rapp_function(0, _("&Journal Voucher 2018"),
			"gl/gl_journal.php?NewJournal=Yes", 'SA_JOURNALENTRY');
		$this->add_rapp_function(0, _("&Budget Entry"),
			"gl/gl_budget.php?", 'SA_BUDGETENTRY');
		$this->add_lapp_function(0, _("Bank &Reconciliation"),
			"gl/bank_account_reconcile_new.php?", 'SA_RECONCILE');
			
		}
			
		$this->add_rapp_function(0, _("&Petty Cash Entry"),
		"gl/add_petty_cash.php?NewPettyCash=Yes", 'SA_DEPOSIT');	
		$this->add_rapp_function(0, _("&Journal Entry Non-Trade"),
		"gl/journal_non_trade.php?NewJournal=Yes", 'SA_DEPOSIT');	
		$this->add_rapp_function(0, _("&Petty Cash Clearing"),
		"gl/petty_cash_clearing.php", 'SA_DEPOSIT');	
		$this->add_rapp_function(0, _("&Petty Cash Breakdown"),
		"gl/inquiry/petty_cash_summary_3.php", 'SA_DEPOSIT');
		
	
		// $this->add_rapp_function(0, _("&Petty Cash Entry"),
		// "gl/add_petty_cash.php?NewPettyCash=Yes", 'SA_DEPOSIT');	
		// $this->add_rapp_function(0, _("&Petty Cash Clearing"),
		// "gl/petty_cash_clearing.php", 'SA_DEPOSIT');	
		// $this->add_rapp_function(0, _("&Petty Cash Breakdown"),
		// "gl/inquiry/petty_cash_summary.php", 'SA_DEPOSIT');
		
		
		
		$this->add_rapp_function(0, _("&Other Income Receivable"),
		"gl/other_income_rec.php?NewIncome=Yes", 'SA_DEPOSIT');
		$this->add_lapp_function(0, _("Bank Statement Reconciliation"),
				"gl/inquiry/bank_recon.php?", 'SA_DEPOSIT');
		

		if ($myBranchCode=='srsn'){		
				$this->add_rapp_function(0, _("&Other Income Payment"),
				"gl/other_income_pay_nova_upd.php?NewPayment=Yes", 'SA_DEPOSIT');
		}
		else{
					$this->add_rapp_function(0, _("&Other Income Payment"),
				"gl/other_income_pay.php?NewPayment=Yes", 'SA_DEPOSIT');
				}	
		
		if($_SESSION['wa_current_user']->access!=14) {
		$this->add_rapp_function(0, _("&Other Income Clearing"),
		"gl/inquiry/other_income_clearing.php", 'SA_DEPOSIT');	
		}
		
		$this->add_rapp_function(0, _("&Other Income Breakdown"),
		"gl/inquiry/other_income_breakdown.php", 'SA_DEPOSIT');

		$this->add_rapp_function(0, _("&Other Income Net Total"),
		"gl/inquiry/other_income_net.php", 'SA_DEPOSIT');
			
		//display_error($myBranchCode);
		
		if ($myBranchCode=='srsn'){
			
		$this->add_rapp_function(0, _("&Other Income Summary HO"),
		"gl/inquiry/other_income_summary_nova.php", 'SA_DEPOSIT');

		$this->add_rapp_function(0, _("&Other Income Summary All Branch"),
		"gl/inquiry/other_income_summary_all-branch.php", 'SA_DEPOSIT');
			
		$this->add_rapp_function(0, _("&Other Income Deposit HO"),
		"gl/inquiry/other_income_deposit_nova.php", 'SA_DEPOSIT');
		}
		else{
		$this->add_rapp_function(0, _("&Other Income Summary"),
		"gl/inquiry/other_income_summary.php", 'SA_DEPOSIT');
			
		$this->add_rapp_function(0, _("&Other Income Deposit"),
		"gl/inquiry/other_income_deposit.php", 'SA_DEPOSIT');
		}
		
		// $this->add_rapp_function(0, _("&Import Bank Statement"),
		// "gl/import_bank_statement_new.php?NewJournal=Yes", 'SA_JOURNALENTRY');
		if($_SESSION['wa_current_user']->username != 'sheayne') {
		$this->add_rapp_function(0, _("&Import Bank Statement"),
		"gl/import_bank_statement_new_2016_with_payment_new.php?NewJournal=Yes", 'SA_JOURNALENTRY');
		
		
		$this->add_rapp_function(0, _("&Import Bank Statement 2017"),
		"gl/import_bank_statement_new_2017_with_payment_new.php?NewJournal=Yes", 'SA_JOURNALENTRY');
		
		$this->add_rapp_function(0, _("&Fix Bank Accounts"),
		"gl/fix_bank_accounts.php", 'SA_JOURNALENTRY');
		
		$this->add_rapp_function(0, "","");
		$this->add_rapp_function(0, _("&Replenished Petty Cash"),
		"gl/inquiry/replenished_petty_cash.php", 'SA_DEPOSIT');
		// $this->add_rapp_function(0, _("&Petty Cash w/ VAT"),
		// "gl/inquiry/petty_cash_with_vat_summary.php", 'SA_DEPOSIT');
		$this->add_rapp_function(0, _("&Other Income w/ VAT"),
		"gl/inquiry/other_income_with_vat_summary.php", 'SA_DEPOSIT');		
		
		$this->add_rapp_function(0, _("&Payment Reconciliation Summary"),
		"gl/payment_trans_summary2.php", 'SA_DEPOSIT');
		
		$this->add_rapp_function(0, _("&Payment Summary"),
		"gl/inquiry/payment_summary.php", 'SA_DEPOSIT');
		
				$this->add_rapp_function(0, _("&CWO Summary"),
		"gl/inquiry/cwo_inquiry.php", 'SA_DEPOSIT');
				if($_SESSION['wa_current_user']->username != 'audit') {
		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("&Journal Inquiry"),
			"gl/inquiry/journal_inquiry.php?", 'SA_GLANALYTIC');
		$this->add_lapp_function(1, _("GL &Inquiry"),
			"gl/inquiry/gl_account_inquiry.php?", 'SA_GLTRANSVIEW');
		$this->add_lapp_function(1, _("Bank Account &Inquiry"),
			"gl/inquiry/bank_inquiry.php?", 'SA_BANKTRANSVIEW');
		$this->add_lapp_function(1, _("Payment Inquiry"),
			"gl/inquiry/csv_inquiry2.php?", 'SA_BANKTRANSVIEW');
		$this->add_lapp_function(1, _("Ta&x Inquiry"),
			"gl/inquiry/tax_inquiry.php?", 'SA_TAXREP');
			
		$this->add_lapp_function(1, _("Bank Total &Deposit"),
			"gl/inquiry/bank_totals_deposit.php?", 'SA_BANKTRANSVIEW');
		$this->add_lapp_function(1, _("Bank Total &Disbursement"),
			"gl/inquiry/bank_totals_disbursement.php?", 'SA_BANKTRANSVIEW');
		
		$this->add_lapp_function(1, _("Yearly Sales Comparison of ARIA and E-Sales"),
			"gl/inquiry/yearly_sales_comp.php?", 'SA_TAXREP');	

		$this->add_lapp_function(1, _("GP SALES CHECKING (FORMULA 1) "),
			"gl/inquiry/gp_sales_formula1.php?", 'SA_TAXREP');		
			
		$this->add_lapp_function(1, _("Yearly Other Income"),
			"gl/inquiry/yearly_other_income.php?", 'SA_TAXREP');
		
		$this->add_lapp_function(1, "<b>Stock Transfer Due To/From (Per Transfer)</b>",
			"gl/inquiry/transfers_comp.php?", 'SA_TAXREP');
		$this->add_lapp_function(1, "<b>Stock Transfer Due To/From (SUMMARY)</b>",
			"gl/inquiry/transfers_comp_totals.php?", 'SA_TAXREP');
		$this->add_lapp_function(1, "<b>Agreement Inquiry</b>",
			"gl/inquiry/supp_agreements.php?", 'SA_ITEMSTRANSVIEW');
		
		$this->add_rapp_function(1, _("For &VAT Relief (Purchases)"),
			"gl/inquiry/generate_dat_file.php?", 'SA_GLANALYTIC');
		$this->add_rapp_function(1, _("For &VAT Relief (Sales)"),
			"gl/inquiry/generate_sales_dat_file.php?", 'SA_GLANALYTIC');
		$this->add_rapp_function(1, _("Trial &Balance"),
			"gl/inquiry/gl_trial_balance.php?", 'SA_GLANALYTIC');
		$this->add_rapp_function(1, _("Balance &Sheet Drilldown"),
			"gl/inquiry/balance_sheet.php?", 'SA_GLANALYTIC');
		$this->add_rapp_function(1, _("&Profit and Loss Drilldown"),
			"gl/inquiry/profit_loss.php?", 'SA_GLANALYTIC');		
		$this->add_rapp_function(1, _("Banking &Reports"),
			"reporting/reports_main.php?Class=5", 'SA_BANKREP');
		$this->add_rapp_function(1, _("General Ledger &Reports"),
			"reporting/reports_main.php?Class=6", 'SA_GLREP');

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("Bank &Accounts"),
			"gl/manage/bank_accounts.php?", 'SA_BANKACCOUNT');
			
		$this->add_lapp_function(2, _("Supplier Debit Memo Agreement Types"),
			"gl/manage/sdma_type.php?", 'SA_GLACCOUNT');
		
		$this->add_lapp_function(2, "","");
		$this->add_lapp_function(2, _("&Quick Entries"),
			"gl/manage/gl_quick_entries.php?", 'SA_QUICKENTRY');
		$this->add_lapp_function(2, _("Account &Tags"),
			"admin/tags.php?type=account", 'SA_GLACCOUNTTAGS');
		$this->add_lapp_function(2, _("&Currencies"),
			"gl/manage/currencies.php?", 'SA_CURRENCY');
		$this->add_lapp_function(2, _("&Exchange Rates"),
			"gl/manage/exchange_rates.php?", 'SA_EXCHANGERATE');
			
		
		if ($_SESSION['wa_current_user']->company == 24)
		{
			$this->add_rapp_function(2, _("<b><i><u>Import GL Entries for Consolidation (JV ending balance per year)</u></i></b>"),
				"gl/consolidate_gl.php?", 'SA_GLACCOUNT');
			$this->add_rapp_function(2, "","");
		}
			
		$this->add_rapp_function(2, _("&Other Income Types"),
		"gl/manage/other_income_types.php?", 'SA_DEPOSIT');
		$this->add_rapp_function(2, _("&Expenditure Types"),
		"gl/manage/expenditure_types.php?", 'SA_GLACCOUNT');
		$this->add_rapp_function(2, _("&GL Accounts"),
			"gl/manage/gl_accounts.php?", 'SA_GLACCOUNT');
		$this->add_rapp_function(2, _("GL Account &Groups"),
			"gl/manage/gl_account_types.php?", 'SA_GLACCOUNTGROUP');
		$this->add_rapp_function(2, _("GL Account &Classes"),
			"gl/manage/gl_account_classes.php?", 'SA_GLACCOUNTCLASS');
			
		}
			
		$this->add_lapp_function(0,_("&Check Voucher (TEST for Ma'am Jen)"), 'modules/checkprint/check_list_201_test.php',	 'SA_CHECKPRINT');	
		if (count($installed_extensions) > 0)
		{
			foreach ($installed_extensions as $mod)
			{
				if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "GL"){
					$c=($mod['title']=="Check Voucher")?0:2;
					$this->add_lapp_function($c, $mod["title"], 
						"modules/".$mod["path"]."/".$mod["filename"]."?",
						isset($mod["access"]) ? $mod["access"] : 'SA_OPEN' );
					}

			}
			
				
				$this->add_lapp_function(0, "","");	
				$this->add_lapp_function(0, _("Clear &On-hold CV"),
				"gl/cv_on_hold_clear.php?", 'SA_RECONCILE');
		}
	}
	
	}
}
?>