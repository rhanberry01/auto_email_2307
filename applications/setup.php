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
class setup_app extends application
{
	function setup_app()
	{
		if($_SESSION['wa_current_user']->username != 'audit') {
		
		global $installed_extensions;
		$this->application("system", _($this->help_context = "S&etup"));

		$this->add_module(_("Company Setup"));
		$this->add_lapp_function(0, _("&Company Setup"),
			"admin/company_preferences.php?", 'SA_SETUPCOMPANY');
		$this->add_lapp_function(0, _("&User Accounts Setup"),
			"admin/users.php?", 'SA_USERS');
		$this->add_lapp_function(0, _("&Access Setup"),
			"admin/security_roles.php?", 'SA_SECROLES');
		$this->add_lapp_function(0, _("&Display Setup"),
			"admin/display_prefs.php?", 'SA_SETUPDISPLAY');
		$this->add_lapp_function(0, _("&Forms Setup"),
			"admin/forms_setup.php?", 'SA_FORMSETUP');
		$this->add_rapp_function(0, _("&Taxes"),
			"taxes/tax_types.php?", 'SA_TAXRATES');
		$this->add_rapp_function(0, _("Tax &Groups"),
			"taxes/tax_groups.php?", 'SA_TAXGROUPS');
		$this->add_rapp_function(0, _("Item Ta&x Types"),
			"taxes/item_tax_types.php?", 'SA_ITEMTAXTYPE');
		$this->add_rapp_function(0, _("System and &General GL Setup"),
			"admin/gl_setup.php?", 'SA_GLSETUP');
		$this->add_rapp_function(0, _("&Fiscal Years"),
			"admin/fiscalyears.php?", 'SA_FISCALYEARS');
			
		$this->add_rapp_function(0, _("&Event Locker"),
			"admin/event_locker.php?", 'SA_FISCALYEARS');
			
		$this->add_rapp_function(0, _("&Print Profiles"),
			"admin/print_profiles.php?", 'SA_PRINTPROFILE');

		$this->add_module(_("Miscellaneous"));
		$this->add_lapp_function(1, _("Pa&yment Terms"),
			"admin/payment_terms.php?", 'SA_PAYTERMS');
		$this->add_lapp_function(1, _("Shi&pping Company"),
			"admin/shipping_companies.php?", 'SA_SHIPPING');
		$this->add_rapp_function(1, _("&Points of Sale"),
			"sales/manage/sales_points.php?", 'SA_POSSETUP');
		$this->add_rapp_function(1, _("&Printers"),
			"admin/printers.php?", 'SA_PRINTERS');

		$this->add_module(_("Maintenance"));
		
		$this->add_lapp_function(2, _("&Void a Transaction"),
			"admin/void_transaction.php?", 'SA_VOIDTRANSACTION');
		$this->add_lapp_function(2, _("&Discrep AP Voucher"),
			"admin/void_transaction_dis.php?", 'SA_SUPPTRANSVIEW');
			
		// $this->add_lapp_function(2, _("POS - ODBC"),
			// "admin/pos_odbc.php?", 'SA_CREATECOMPANY');

		$this->add_lapp_function(2, _("POS DB Location"),
			"admin/pos_db_path.php?", 'SA_SETUPCOMPANY');
			
		$this->add_lapp_function(2, _("View or &Print Transactions"),
			"admin/view_print_transaction.php?", 'SA_VIEWPRINTTRANSACTION');
		$this->add_lapp_function(2, _("&Attach Documents"),
			"admin/attachments.php?filterType=20", 'SA_ATTACHDOCUMENT');
		$this->add_lapp_function(2, _("System &Diagnostics"),
			"admin/system_diagnostics.php?", 'SA_OPEN');

		$this->add_lapp_function(2, _("<b>Fix GL Discrep</b>"),"admin/fix_gl_discrep_tb.php?", 'SA_OPEN');
		// $this->add_lapp_function(2, "<b>Fix Sales GL Breakdown *auto create sales suki points</b>",
			// "admin/fix_sales_gl_breakdown.php?", 'SA_GLSETUP');
		$this->add_lapp_function(2, "<b>Fix APV Purchase VAT and VAT</b>",
			"admin/fix_purchase_and_pvat.php?", 'SA_GLSETUP');
		$this->add_lapp_function(2, "<b>---> Fix Debit Memo (POST all temp + FIX Supp payment (delete Other Expense account))</b>",
			"admin/fix_debit_memo_post_temp.php?", 'SA_GLSETUP');
		$this->add_lapp_function(2, "<b>---> Fix Debit Memo (POST all temp + FIX Supp payment (delete Other Income account))</b>",
			"admin/fix_debit_memo_post_temp_oi.php?", 'SA_GLSETUP');
			
		$this->add_lapp_function(2, "<b>Stock Transfer Out -- MS Movement to ARIA (Net of VAT per Item) (login to out)</b>",
			"admin/ms_transfer_to_aria_out.php?", 'SA_GLSETUP');
			
		$this->add_lapp_function(2, "<b>Stock Transfer IN -- MS  Movement to ARIA (Net of VAT per Item) (login to in)</b>",
			"admin/ms_transfer_to_aria_in.php?", 'SA_GLSETUP');
			
		$this->add_lapp_function(2, "<b>Stock Transfer IN -- Journal MS  Movement which not in ARIA (for transfer in w/out gl entries) (login to in)</b>",
			"admin/journal_ms_transfer_not_in_aria.php?", 'SA_GLSETUP');
			
		$this->add_lapp_function(2, "<b>Kusina Transfer Out -- MS Movement to ARIA (login to malabon)</b>",
			"admin/ms_kusina_transfer_to_aria_out.php?", 'SA_GLSETUP');
		
		$this->add_lapp_function(2, "<b>Kusina Transfer IN -- MS  Movement to ARIA (login to resto)</b>",
			"admin/ms_kusina_transfer_to_aria_in.php?", 'SA_GLSETUP');
			
		$this->add_lapp_function(2, "<b>Kusina Transfer IN-- Journal MS  KUSINA IN which not in ARIA by using OUT (for transfer in not in ms kusina and w/out gl entries) (login to resto)</b>",
			"admin/journal_ms_kusina_transfer_not_in_aria.php?",  'SA_GLSETUP');
		
		
		
		
		
		
		$this->add_rapp_function(2, _("<b>---> Fix Transfer Out using Transfer In</b>"),"admin/fix_transfer_out_using_in.php", 'SA_OPEN');
		$this->add_rapp_function(2, "<b>---> Fix DM entries to Promo Fund *Set all DM types first</b>",
			"admin/fix_dm_promo_fund.php?", 'SA_GLSETUP');
			

		
		$this->add_rapp_function(2, "<b>Fix APV GL to Delivery Date</b>",
			"admin/fix_apv_gl_to_del_date.php?", 'SA_GLSETUP');
			

		
		// // $this->add_rapp_function(2, "<b>Delete JV of CWO then Reimport</b>",
			// // "admin/fix_delete_wrong_cwo_jv.php?", 'SA_GLSETUP');
		
		// $this->add_rapp_function(2, "<b>Fix (DR) Transfers IN</b>",
			// "admin/fix_dr_in.php?", 'SA_GLSETUP');
		
		// $this->add_rapp_function(2, "<b>Fix (DR) Transfers OUT</b>",
			// "admin/fix_dr_out.php?", 'SA_GLSETUP');
		
		$this->add_rapp_function(2, "<b>Adjust Beginning and Ending Inventory (Journal Entry)</b>",
			"admin/fix_inventory.php?", 'SA_GLSETUP');
		
		$this->add_rapp_function(2, "<b>Fix Stock Transfers IN (new transfers)</b>",
			"admin/fix_transfer_in.php?", 'SA_GLSETUP');
		
		$this->add_rapp_function(2, "<b>Fix Stock Transfers OUT (new transfers)</b>",
			"admin/fix_transfer_out.php?", 'SA_GLSETUP');
		
		// $this->add_rapp_function(2, "<b>Fix Debit Memo GL</b>",
			// "admin/fix_debit_memo_gl.php?", 'SA_GLSETUP');
		
		$this->add_rapp_function(2, '');
		
		
		// $this->add_rapp_function(2, '');
		
		$this->add_rapp_function(2, _("Fix Bank and GL Entry of Online Payments (assign bank of online payment on System and General GL Setup"),
			"admin/fix_online_payments.php?", 'SA_GLSETUP');
		// $this->add_rapp_function(2, _("VOID wrong RR and CV"),
			// "admin/void_wrong_rr_cv.php?", 'SA_GLSETUP');
		

		// // $this->add_rapp_function(2, _("DELETE then Confirm ALL remaining RS to DM"),
			// // "admin/fix_dm_to_rs.php?", 'SA_GLSETUP');
			
		// // $this->add_rapp_function(2, _("Fix EWT GL Entry of Online Payments"),
			// // "admin/fix_ewt_of_online_payments.php?", 'SA_GLSETUP');
		// // $this->add_rapp_function(2, _("Fix GL Date of DM/CM/EWT"),
			// // "admin/transfer_unused_dm_cm_to_temp.php?", 'SA_GLSETUP');
		// // $this->add_rapp_function(2, _("Delete Double Item - Receiving"),
			// // "admin/delete_double_rr_items.php?", 'SA_SUPPLIERINVOICE');
		// // $this->add_rapp_function(2, _("Update Bank GL Account"),
			// // "admin/update_bank_gl.php?", 'SA_BANKACCOUNT');
			
		// $this->add_rapp_function(2, _("Update Movement Line of Returns/Disposal"),
			// "admin/update_rs_movementline.php?", 'SA_BANKACCOUNT');
		// $this->add_rapp_function(2, _("Approve ALL CV"),
			// "admin/approve_all_cv.php?", 'SA_SETUPCOMPANY');
			
		$this->add_rapp_function(2, "<b>Transfer GL Date of DM/CM/Purchase Discount to Payment Date</b>",
			"admin/fix_dm_gl_date_to_payment_date.php?", 'SA_GLSETUP');
			
		$this->add_rapp_function(2, '');
		$this->add_rapp_function(2, "<b>Fix Sales GL Breakdown PER MONTH USING FINISHED SALES<b>",
			"admin/fix_sales_gl_breakdown_monthly.php?", 'SA_GLSETUP');
		$this->add_rapp_function(2, '');
		
		// $this->add_rapp_function(2, "<b>Fix Purchase GL (VAT / NON-VAT)</b>",
			// "admin/fix_purchase_gl.php?", 'SA_GLSETUP');



		$this->add_rapp_function(2, "<b>Fix Stock Transfers IN (Due To)</b>",
			"admin/fix_transfer_in_due_to.php?", 'SA_GLSETUP');

		$this->add_rapp_function(2, '');
		$this->add_rapp_function(2, "<b>Fix OLD TRANSFER OUT (will void then copy amount from OLD IN) </b>",
			"admin/fix_old_transfer_out_using_in.php?", 'SA_GLSETUP');

		$this->add_rapp_function(2, '');
		$this->add_rapp_function(2, _("&Backup and Restore"),
			"admin/backups.php?", 'SA_BACKUP');
		// $this->add_rapp_function(2, _("Create/Update &Companies"),
			// "admin/create_coy.php?", 'SA_SETUPCOMPANY');
		// $this->add_rapp_function(2, _("Install/Update &Languages"),
			// "admin/inst_lang.php?", 'SA_CREATELANGUAGE');
		// $this->add_rapp_function(2, _("Install/Activate &Extensions"),
			// "admin/inst_module.php?", 'SA_CREATEMODULES');
		// $this->add_rapp_function(2, _("Software &Upgrade"),
			// "admin/inst_upgrade.php?", 'SA_SOFTWAREUPGRADE');
		if (count($installed_extensions) > 0)
		{
			foreach ($installed_extensions as $mod)
			{
				if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "system")
					$this->add_rapp_function(2, $mod["title"], 
						"modules/".$mod["path"]."/".$mod["filename"]."?",
						isset($mod["access"]) ? $mod["access"] : 'SA_OPEN' );
			}
		}
	}
	
			$this->add_module(_("Data Fix Tool"));
			$this->add_lapp_function(3, "<b>TB/GL Fix - (Fix DM with no GL entries)</b>","admin/fix_dm_without_gl_entry.php?", 'SA_GLSETUP');
			$this->add_lapp_function(3, "<b>TB/GL Fix - (Fix DM Vat Discrep)</b>","admin/fix_dm_vat_discrep.php?", 'SA_GLSETUP');
			$this->add_lapp_function(3, "<b>TB/GL Fix - (Fix Purchase Vat Discrep)</b>","admin/fix_purchases_vat_discrep.php?", 'SA_GLSETUP');
			$this->add_lapp_function(3, "<b>TB/GL Fix - (Remove Centavo Difference)</b>","admin/fix_cents_discrep.php?", 'SA_GLSETUP');


}
}


?>