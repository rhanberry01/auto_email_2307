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
class suppliers_app extends application 
{
	function suppliers_app() 
	{
		global $installed_extensions;
		
				if($_SESSION['wa_current_user']->username != 'sheayne') {
		
		$this->application("AP", _($this->help_context = "&Purchases"));

		$this->add_module(_("Transactions"));
		// $this->add_lapp_function(0, _("Create Purchase &Order"),
			// "purchasing/po_entry_items.php?NewOrder=Yes", 'SA_PURCHASEORDER');
		$this->add_lapp_function(0, _("Import PO and Receiving (<b><i>if date is before January 12, 2014</i></b>)"),
			"purchasing/import_po_rr_test.php", 'SA_PURCHASEORDER');
		
		// $this->add_lapp_function(0, _("Import Purchase Order and Receives to APV"),
			// "purchasing/import_po_rr_to_apv.php", 'SA_PURCHASEORDER');
		
		if($_SESSION['wa_current_user']->username == 'admin')		
			$this->add_lapp_function(0, _("Receiving to CV"),"purchasing/import_po_rr_to_cv.php", 'SA_PURCHASEORDER');
		
		$this->add_lapp_function(0, "","");
		
		// $this->add_lapp_function(0, _("Create / View Monthly Rebates"),
			// "purchasing/generate_rebate.php", 'SA_PURCHASEORDER');
		
		
		// $this->add_lapp_function(0, _("&Receive Purchase Order"),
			// "purchasing/inquiry/po_search.php?", 'SA_SUPPTRANSVIEW');
		$this->add_lapp_function(0, /*_("Supplier &Invoices")*/_("Create Account Payable Voucher"),
			"purchasing/supplier_invoice.php?New=1", 'SA_SUPPLIERINVOICE');
		
		$this->add_lapp_function(0, /*_("Supplier &Invoices")*/_("Create Account Payable Voucher (Non-Trade)"),
			"purchasing/supplier_invoice.php?New=1&NT=yes", 'SA_SUPPLIERINVOICE');

		$this->add_lapp_function(0, /*_("Supplier &Invoices")*/_("Create Account Payable Voucher (Addback)"),
			"purchasing/supplier_invoice.php?New=1&NT=no", 'SA_SUPPLIERINVOICE');

			
		$this->add_lapp_function(0, _("Prepare Check Voucher"),
			"purchasing/inquiry/prepare_cv.php?", 'SA_SUPPLIERINVOICE');
		$this->add_lapp_function(0, _("Create Check Payment"),
			"purchasing/supplier_payment_sign.php", 'SA_PURCHASEORDER');
			
	//	$this->add_rapp_function(0, "","");
		// $this->add_rapp_function(0, /*_("Supplier &Invoices")*/_("Create Account Payable Voucher"),
		// 	"purchasing/supplier_invoice.php?New=1", 'SA_SUPPLIERINVOICE');
		// $this->add_rapp_function(0, _("Purchase Returns"),
			// "purchasing/supplier_credit.php?New=1", 'SA_SUPPLIERCREDIT');
		$this->add_rapp_function(0, _("Supplier &Debit/Credit Memo"),
			"purchasing/supp_credit_debit_memo_entry.php?NewMemo=Yes", 'SA_SUPPLIERCREDIT');
		
		$this->add_rapp_function(0, "","");
	//	$this->add_rapp_function(0, _("Supplier Debit Memo Agreement"),
	//		"purchasing/sdma_entry.php?NewMemo=Yes", 'SA_PURCHASEORDER');
			
		$this->add_rapp_function(0, "","");
		$this->add_rapp_function(0, _("Supplier Debit Memo Agreement (ALL BRANCH)"),
			"purchasing/sdma_entry_new.php?NewMemo=Yes", 'SA_PURCHASEORDER');
		
		// $this->add_rapp_function(0, _("Manage Rebates"),
			// "purchasing/manage/supplier_rebates.php?NewMemo=Yes", 'SA_SUPPLIERCREDIT');
		
		$this->add_rapp_function(0, "","");
		$this->add_rapp_function(0, _("Change Reference # of Transactions"),
			"purchasing/change_supp_ref.php", 'SA_SUPPLIERINVOICE');
			
		$this->add_rapp_function(0, "","");
		$this->add_rapp_function(0, _("Supplier Clearance"),
			"purchasing/suppliers_clearance.php", 'SA_PURCHASEORDER');
		
		if($_SESSION["wa_current_user"]->company == 4)
		{
			$this->add_rapp_function(0, "","");
			$this->add_rapp_function(0, _("Mark Receiving for Belen Tan"),
				"purchasing/mark_rr.php", 'SA_SUPPLIERINVOICE');
		}
		
		// $this->add_rapp_function(0, _("Supplier Cash Payments"),
			// "purchasing/supplier_payment.php?", 'SA_SUPPLIERPAYMNT');
		// $this->add_rapp_function(0, _("Supplier Check Payments"),
			// "purchasing/supplier_payment_check.php?", 'SA_SUPPLIERPAYMNT');
		// $this->add_rapp_function(0, _("Supplier &Allocations"),
			// "purchasing/allocations/supplier_allocation_main.php?", 'SA_SUPPLIERALLOC');

		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("View Purchase &Orders"),
			"purchasing/srs_approve_po.php", 'SA_ITEMSTRANSVIEW');
			
		$this->add_lapp_function(1, _("View Imported Receiving"),
			"purchasing/srs_receiving.php", 'SA_ITEMSTRANSVIEW');
		$this->add_lapp_function(1, _("Supplier Transaction Inquiry"),
			"purchasing/inquiry/supplier_inquiry.php?", 'SA_SUPPTRANSVIEW');
		// $this->add_lapp_function(1, _("Approved Purchase Orders &Inquiry"),
			// "purchasing/inquiry/po_search_completed.php?", 'SA_SUPPTRANSVIEW');
		$this->add_lapp_function(1, _("Discrepancy Report"),
			"purchasing/inquiry/discrepancy_report.php?", 'SA_SUPPTRANSVIEW');
			
		$this->add_lapp_function(1, _("Receiving Discrepancy"),
		"purchasing/inquiry/receiving_discrepancy_report.php?", 'SA_SUPPTRANSVIEW');
		
		$this->add_lapp_function(1, _("Products For Bundling Inquiry"),
			"purchasing/inquiry/products_per_bundling_inquiry.php?", 'SA_SUPPTRANSVIEW');
		
		$this->add_lapp_function(1, _("Check Payment Inquiry"),
			"purchasing/inquiry/sp_inquiry.php?", 'SA_SUPPTRANSVIEW');		
		// $this->add_lapp_function(1, _("Rejected Purchase Orders &Inquiry"),
			// "purchasing/inquiry/po_search_completed_rejected.php?", 'SA_SUPPTRANSVIEW');
		// $this->add_lapp_function(1, "","");
		// $this->add_lapp_function(1, _("Supplier Allocation &Inquiry"),
			// "purchasing/inquiry/supplier_allocation_inquiry.php?", 'SA_SUPPLIERALLOC');
		// $this->add_lapp_function(1, _("Payment &Inquiry"),
			// "purchasing/inquiry/payment_inquiry.php?", 'SA_SUPPTRANSVIEW');

		// $this->add_rapp_function(1, _("Supplier and Purchasing &Reports"),
			// "reporting/reports_main.php?Class=1", 'SA_SUPPTRANSVIEW');
		
		// $this->add_rapp_function(1, _("Receiving to APV to CV Inquiry"),
			// "purchasing/inquiry/rr_apv_cv.php?", 'SA_SUPPTRANSVIEW');
		
		// $this->add_rapp_function(1, _("Debit Memo Inquiry"),
			// "purchasing/inquiry/dm_inquiry.php?", 'SA_SUPPTRANSVIEW');
		$this->add_rapp_function(1, _("Suppliers Clearance Inquiry"),
			"purchasing/inquiry/sc_inquiry.php?", 'SA_SUPPTRANSVIEW');
		$this->add_rapp_function(1, _("Debit Memo (Agreement Only) Inquiry Centralized"),
			"purchasing/inquiry/sdma_inquiry.php?", 'SA_SUPPTRANSVIEW');
		$this->add_rapp_function(1, _("Approved Promo Fund Inquiry"),
			"purchasing/inquiry/supp_trans_inquiry.php?", 'SA_SUPPTRANSVIEW');
			
		$this->add_rapp_function(1, _("Debit Memo (Agreement Only) Inquiry Per Branch"),
			"purchasing/inquiry/sdma_inquiry_orig.php?", 'SA_SUPPTRANSVIEW');

		$this->add_rapp_function(1, _("Deducted/Undeducted Debit Memo (Agreement Only) Inquiry"),
			"purchasing/inquiry/sdma_dm_inquiry.php?", 'SA_SUPPTRANSVIEW');

		$this->add_rapp_function(1, _("Free Items Received Inquiry"),
			"purchasing/inquiry/rof_inquiry.php?", 'SA_SUPPTRANSVIEW');
		
		$this->add_rapp_function(1, _("Special Discounted Items (2.5%)"),
			"purchasing/inquiry/special_discounted_items_inquiry.php?", 'SA_SUPPTRANSVIEW');
		$this->add_rapp_function(1, _("View Price Survey"),
			"purchasing/price_survey.php", 'SA_ITEMSTRANSVIEW');	
	if($_SESSION['wa_current_user']->username != 'audit') {
		
		$this->add_module(_("Maintenance"));
		
			if($_SESSION['wa_current_user']->username == 'admin' OR $_SESSION['wa_current_user']->username == 'jenT' OR $_SESSION['wa_current_user']->username == 'beth') {
		$this->add_lapp_function(2, _("&Suppliers (Centralized Update Suppliers To all Branches)"),"purchasing/manage/suppliers.php?", 'SA_SUPPLIER');
		
			}
		$this->add_lapp_function(2, _("&Suppliers with Biller Code List"),"purchasing/manage/suppliers_w_bc.php?", 'SA_SUPPLIER');
		
		// $this->add_lapp_function(2, _("Supplier Off Invoice Discount"),"purchasing/manage/supplier_off_invoice_discount.php?", 'SA_SUPPLIER');
		$this->add_rapp_function(2, _("Void Multiple Debit Memo"),"purchasing/manage/void_debit_memos.php?", 'SA_SUPPLIERCREDIT');

		if (count($installed_extensions) > 0)
		{
			foreach ($installed_extensions as $mod)
			{
				if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "AP")
					$this->add_rapp_function(2, $mod["title"], 
						"modules/".$mod["path"]."/".$mod["filename"]."?",
						isset($mod["access"]) ? $mod["access"] : 'SA_OPEN' );
			}
		}
	}
	}
	
	}
}


?>