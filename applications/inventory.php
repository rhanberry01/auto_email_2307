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
class inventory_app extends application 
{
	function inventory_app() 
	{
		global $installed_extensions,$db_connections;
		$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		
		$this->application("stock", _($this->help_context = "&Items and Inventory"));

		$this->add_module(_("Transactions"));
		
		// $this->add_lapp_function(0, _("Inventory Location &Transfers"),
			// "inventory/transfers.php?NewTransfer=1", 'SA_LOCATIONTRANSFER');
		// $this->add_lapp_function(0, _("Inventory &Adjustments"),
			// "inventory/adjustments.php?NewAdjustment=1", 'SA_INVENTORYADJUSTMENT');
			
		//$this->add_lapp_function(0,'');
		
		// $this->add_lapp_function(0, _("Approve Item Adjustment"),
		// "inventory/inquiry/all_adjustment_inquiry2.php?", 'SA_ITEMSTRANSVIEW');

		$this->add_lapp_function(0, _("Approve Item Adjustment (Centralized)"),
		"inventory/inquiry/all_adjustment_inquiry2_live_2.php?", 'SA_ITEMSTRANSVIEW');

		$this->add_lapp_function(0, _("Approve Item Adjustment (MAAM CEZZ)"),
		"inventory/inquiry/all_adjustment_inquiry2.php?", 'SA_ITEMSTRANSVIEW');

		$this->add_lapp_function(0, _("Inventory Gain and Loss Running Total"),
		"inventory/inquiry/inventory_gain_and_loss_running_total.php?", 'SA_ITEMSTRANSVIEW');

		$this->add_lapp_function(0, _("Approve Disposal from B.O"),
		"inventory/inquiry/disposal_approval_2.php?", 'SA_LOCATIONTRANSFER');
		
		$this->add_lapp_function(0, _("Approve Disposal from B.O (Centralized)"),
		"inventory/inquiry/disposal_approval_2.2_live.php?", 'SA_LOCATIONTRANSFER');

		$this->add_lapp_function(0, _("B.O Disposal Running Total"),
		"inventory/inquiry/disposal_running_total_live.php?", 'SA_LOCATIONTRANSFER');

		$this->add_lapp_function(0, _("B.O Aging (Centralized)"),
		"inventory/inquiry/bo_aging_view.php?", 'SA_LOCATIONTRANSFER');
		
		// $this->add_lapp_function(0, _("Approve Transformation"),
		// "inventory/inquiry/transformation_approval.php?", 'SA_LOCATIONTRANSFER');
		
		 if ($myBranchCode=='srsmr'){
		 $this->add_rapp_function(0, _("Movements (Item Adjustment) KUSINA PRICE SURVEY"),
		"inventory/all_item_adjustments_old.php?NewAdjustment=1", 'SA_LOCATIONTRANSFER');
		 }

		$this->add_rapp_function(0, _("Movements (Item Adjustment)"),
		"inventory/all_item_adjustments.php?NewAdjustment=1", 'SA_LOCATIONTRANSFER');

		
		$this->add_rapp_function(0, _("Movements (Stocks Withdrawal)"),
		"inventory/stocks_withdrawal.php?NewTransfer=1", 'SA_LOCATIONTRANSFER');
		
		// if ($myBranchCode=='srsn'){
		// $this->add_rapp_function(0, _("Movements (Item Transformation)"),
		// "inventory/item_transformation_nova.php?NewTransformation=1", 'SA_LOCATIONTRANSFER');
		
		$this->add_rapp_function(0, _("Movements (Item Transformation) w/ Auto approve"),
		"inventory/item_transformation_special.php?NewTransformations=1", 'SA_LOCATIONTRANSFER');
		// }
		// else {
		// $this->add_rapp_function(0, _("Movements (Item Transformation)"),
		// "inventory/item_transformation.php?NewTransformation=1", 'SA_LOCATIONTRANSFER');
		// }
		// $this->add_rapp_function(0, _("Movements (SA to SRS Kusina)"),
		// "inventory/sa_to_kusina.php?NewTransfer=1", 'SA_LOCATIONTRANSFER');


		$this->add_rapp_function(0, _("Movements (Stock Transfer Request)"),
		"inventory/movements_out.php?NewTransfer=1", 'SA_INVENTORYADJUSTMENT');

		$this->add_rapp_function(0, _("Movements (Stock Transfer Request for Caravan)"),
		"inventory/movements_out_for_caravan.php?NewTransfer=1", 'SA_INVENTORYADJUSTMENT');

		$this->add_rapp_function(0, _("Movements (MALABON SA to SRS Kusina)"),
		"inventory/sa_to_kusina2.php?NewTransfer=1", 'SA_LOCATIONTRANSFER');


		$this->add_module(_("Inquiries and Reports"));
		$this->add_lapp_function(1, _("Adjustment Summary"),
		"inventory/inquiry/adjustment_inquiry_per_trans.php?", 'SA_ITEMSTRANSVIEW');
		$this->add_lapp_function(1, _("Auto Adjustment Summary"),
		"inventory/inquiry/auto_adjustment_inquiry.php?", 'SA_ITEMSTRANSVIEW');

		$this->add_lapp_function(1, _("Adjustment Inquiry"),
		"inventory/inquiry/adjustment_inquiry.php", 'SA_ITEMSTRANSVIEW');

		$this->add_lapp_function(1, _("Stocks Withdrawal Inquiry"),
		"inventory/inquiry/stock_withdrawal_inquiry.php?", 'SA_ITEMSTRANSVIEW');
		$this->add_lapp_function(1, _("Disposal From B.O Inquiry"),
		"inventory/inquiry/disposal_inquiry.php?", 'SA_ITEMSTRANSVIEW');
		$this->add_lapp_function(1, _("Return to Supplier Inquiry"),
		"inventory/inquiry/return_to_supplier_inquiry.php?", 'SA_ITEMSTRANSVIEW');
		
		$this->add_lapp_function(1, _("Item Transformation Inquiry"),
		"inventory/inquiry/transformation_inquiry.php?", 'SA_ITEMSTRANSVIEW');
		$this->add_lapp_function(1, _("SA to SRS Kusina Inquiry"),
		"inventory/inquiry/sa_to_resto_inquiry.php?", 'SA_ITEMSTRANSVIEW');
		
		$this->add_lapp_function(1, _("SA to SRS Kusina Summary"),
		"inventory/inquiry/sa_to_resto_summary.php?", 'SA_ITEMSTRANSVIEW');
		
		$this->add_lapp_function(1, _("SA to SRS Kusina Details"),
		"inventory/inquiry/sa_to_kusina_detailed_trans.php?", 'SA_ITEMSTRANSVIEW');
		

		$this->add_lapp_function(1, _("Inventory Item &Movements"),
			"inventory/inquiry/stock_movements.php?", 'SA_ITEMSTRANSVIEW');
		$this->add_lapp_function(1, _("Inventory Item &Status"),
			"inventory/inquiry/stock_status.php?", 'SA_ITEMSSTATVIEW');
			

		$this->add_rapp_function(1, _("Stock Transfer Inquiry"),
		"inventory/inquiry/stock_transfer_inquiry.php?", 'SA_ITEMSTRANSVIEW');

		$this->add_rapp_function(1, _("Stock Transfer Inquiry for Caravan"),
		"inventory/inquiry/stock_transfer_inquiry_for_caravan.php?", 'SA_ITEMSTRANSVIEW');
		
				$this->add_rapp_function(1, _("Stock Transfer Summary (w/ Discrepancies)"),
		"inventory/inquiry/stock_trans_inquiry.php", 'SA_ITEMSTRANSVIEW');
		
				$this->add_rapp_function(1, _("Stock Transfer Summary (All Transactions)"),
		"inventory/inquiry/stock_trans_inquiry2.php", 'SA_ITEMSTRANSVIEW');
		
		$this->add_rapp_function(1,'');
		$this->add_rapp_function(1, _("Inventory &Reports"),
			"reporting/reports_main.php?Class=2", 'SA_ITEMSTRANSVIEW');
			
			
		if($_SESSION['wa_current_user']->username != 'audit') {

		$this->add_module(_("Maintenance"));
		$this->add_lapp_function(2, _("&Items"),
			"inventory/manage/items.php?", 'SA_ITEM');
		$this->add_lapp_function(2, _("&Foreign Item Codes"),
			"inventory/manage/item_codes.php?", 'SA_FORITEMCODE');
		$this->add_lapp_function(2, _("Sales &Kits"),
			"inventory/manage/sales_kits.php?", 'SA_SALESKIT');
		$this->add_lapp_function(2, _("Item &Categories"),
			"inventory/manage/item_categories.php?", 'SA_ITEMCATEGORY');

		$this->add_rapp_function(2, _("Inventory &Movement Types"),
			"inventory/manage/movement_types.php?", 'SA_INVENTORYMOVETYPE');
		$this->add_rapp_function(2, _("&Units of Measure"),
			"inventory/manage/item_units.php?", 'SA_UOM');
		$this->add_rapp_function(2, _("&Reorder Levels"),
			"inventory/reorder_level.php?", 'SA_REORDER');
		$this->add_rapp_function(2, _("Inventory &Locations"),
			"inventory/manage/locations.php?", 'SA_INVENTORYLOCATION');

		$this->add_module(_("Pricing and Costs"));
		$this->add_lapp_function(3, _("Sales &Pricing"),
			"inventory/prices.php?", 'SA_SALESPRICE');
		$this->add_lapp_function(3, _("Sales &Pricing Per Customer"),
			"inventory/prices_per_customer.php?", 'SA_SALESPRICE');

		// $this->add_rapp_function(3, _("Standard &Costs"),
			// "inventory/cost_update.php?", 'SA_STANDARDCOST');
		$this->add_rapp_function(3, _("Purchasing &Pricing"),
			"inventory/purchasing_data.php?", 'SA_PURCHASEPRICING');
		if($_SESSION['wa_current_user']->username == 'admin')
			$this->add_rapp_function(3, _("Recompute and Update Cost of Sales"),"inventory/super_cost_update.php?", 'SA_STANDARDCOST');

		if ($_SESSION['wa_current_user']->user == '1' || $_SESSION['wa_current_user']->user == 'admin') {
			$this->add_module(_("Utilities"));
			$this->add_lapp_function(4, _("Import Branch RM to Centralized RM"),
				"inventory/import_rs_to_central_rs_live.php?", 'SA_ITEMSTRANSVIEW');
			$this->add_lapp_function(4, _("Update Centralized RM to Branch RM"),
				"inventory/update_central_rs_to_branch_rs_live.php?", 'SA_ITEMSTRANSVIEW');
			$this->add_lapp_function(4, _("Auto Compute RM Items Total"),
				"inventory/auto_compute_rs_items_total.php?", 'SA_ITEMSTRANSVIEW');
			$this->add_lapp_function(4, _("Auto Compute RM Items Total (Y.2017)"),
				"inventory/auto_compute_rs_items_total_2017.php?", 'SA_ITEMSTRANSVIEW');
		}
		
		if (count($installed_extensions) > 0)
		{
			foreach ($installed_extensions as $mod)
			{
				if (@$mod['active'] && $mod['type'] == 'plugin' && $mod["tab"] == "stock")
					$this->add_rapp_function(2, $mod["title"], 
						"modules/".$mod["path"]."/".$mod["filename"]."?",
						isset($mod["access"]) ? $mod["access"] : 'SA_OPEN' );
			}
		}
		
	}
	}
}
?>