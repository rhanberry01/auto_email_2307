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
$page_security = 'SA_ITEMCATEGORY';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

page(_($help_context = "Item Categories"));

include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/inventory/includes/inventory_db.inc");

simple_page_mode(true);
//----------------------------------------------------------------------------------
function check_discs(){
		
		global $_POST;

		for($i=1;$i<=6;$i++)
		{
			$discs[$i]=input_num('disc'.$i);
			$_POST['disc'.$i]=0;
		}

		$discs=array_filter($discs);
		$countx=count($discs);
	
		$x=1;
		foreach($discs as $d){
			$_POST['disc'.$x]=$d;
			$x++;
		}
}

function discount_string($myrow){
		
	for($c=1;$c<=6;$c++){
		if($myrow['disc'.$c]!=0)
		$discx[]=$myrow['disc'.$c];
	}
	
	if(count($discx)==0)
	return "-";
	else return implode("/",$discx)."%";
		


}

if ($Mode=='ADD_ITEM' || $Mode=='UPDATE_ITEM') 
{

	//initialise no input errors assumed initially before we test
	$input_error = 0;

	if (strlen($_POST['description']) == 0) 
	{
		$input_error = 1;
		display_error(_("The item category description cannot be empty."));
		set_focus('description');
	}

	if ($input_error !=1)
	{
		check_discs();
    	if ($selected_id != -1) 
    	{
		    update_item_category($selected_id, $_POST['description'],
				$_POST['tax_type_id'],	$_POST['sales_account'], 
				$_POST['cogs_account'], $_POST['inventory_account'], 
				$_POST['adjustment_account'], $_POST['assembly_account'],
				$_POST['units'], $_POST['mb_flag'],	$_POST['dim1'],	$_POST['dim2'],
				check_value('no_sale'),input_num('disc1'),input_num('disc2'),input_num('disc3'),input_num('disc4'),input_num('disc5'),input_num('disc6'),check_value('restricted'));
			display_notification(_('Selected item category has been updated'));
    	} 
    	else 
    	{
		    add_item_category($_POST['description'],
				$_POST['tax_type_id'],	$_POST['sales_account'], 
				$_POST['cogs_account'], $_POST['inventory_account'], 
				$_POST['adjustment_account'], $_POST['assembly_account'], 
				$_POST['units'], $_POST['mb_flag'],	$_POST['dim1'],	
				$_POST['dim2'],	check_value('no_sale'),input_num('disc1'),input_num('disc2'),input_num('disc3'),input_num('disc4'),input_num('disc5'),input_num('disc6'),check_value('restricted'));
			display_notification(_('New item category has been added'));
    	}
		$Mode = 'RESET';
	}
}

//---------------------------------------------------------------------------------- 

if ($Mode == 'Delete')
{

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."stock_master WHERE category_id=".db_escape($selected_id);
	$result = db_query($sql, "could not query stock master");
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		display_error(_("Cannot delete this item category because items have been created using this item category."));
	} 
	else 
	{
		delete_item_category($selected_id);
		display_notification(_('Selected item category has been deleted'));
	}
	$Mode = 'RESET';
}

if ($Mode == 'RESET')
{
	$selected_id = -1;
	$sav = get_post('show_inactive');
	unset($_POST);
	$_POST['show_inactive'] = $sav;
}
if (list_updated('mb_flag')) {
	$Ajax->activate('details');
}
//----------------------------------------------------------------------------------

$sql = "SELECT c.*, t.name as tax_name FROM ".TB_PREF."stock_category c, "
	.TB_PREF."item_tax_types t WHERE c.dflt_tax_type=t.id";
if (!check_value('show_inactive')) $sql .= " AND !c.inactive";

$result = db_query($sql, "could not get stock categories");

start_form();
start_table("$table_style width=80%");
$th = array(_("Name"), _("Tax type"), _("Units"), _("Type"), _("Sales Act"),
_("Inventory Account"), _("COGS Account"), _("Adjustment Account"),
_("Assembly Account"),/*"Discount",*/ "", "");
inactive_control_column($th);

table_header($th);
$k = 0; //row colour counter

while ($myrow = db_fetch($result)) 
{
	
	alt_table_row_color($k);

	label_cell($myrow["description"]);
	label_cell($myrow["tax_name"]);
	label_cell($myrow["dflt_units"], "align=center");
	label_cell($stock_types[$myrow["dflt_mb_flag"]]);
	label_cell($myrow["dflt_sales_act"], "align=center");
	label_cell($myrow["dflt_inventory_act"], "align=center");
	label_cell($myrow["dflt_cogs_act"], "align=center");
	label_cell($myrow["dflt_adjustment_act"], "align=center");
	label_cell($myrow["dflt_assembly_act"], "align=center");
	//label_cell(discount_string($myrow),"align=right");
	inactive_control_cell($myrow["category_id"], $myrow["inactive"], 'stock_category', 'category_id');
 	edit_button_cell("Edit".$myrow["category_id"], _("Edit"));
 	delete_button_cell("Delete".$myrow["category_id"], _("Delete"));
	end_row();
}

inactive_control_row($th);
end_table();
echo '<br>';
//----------------------------------------------------------------------------------

div_start('details');
start_table($table_style2);

if ($selected_id != -1) 
{
 	if ($Mode == 'Edit') {
		//editing an existing item category
		$myrow = get_item_category($selected_id);

		$_POST['category_id'] = $myrow["category_id"];
		$_POST['description']  = $myrow["description"];
		$_POST['tax_type_id']  = $myrow["dflt_tax_type"];
		$_POST['sales_account']  = $myrow["dflt_sales_act"];
		$_POST['cogs_account']  = $myrow["dflt_cogs_act"];
		$_POST['inventory_account']  = $myrow["dflt_inventory_act"];
		$_POST['adjustment_account']  = $myrow["dflt_adjustment_act"];
		$_POST['assembly_account']  = $myrow["dflt_assembly_act"];
		$_POST['units']  = $myrow["dflt_units"];
		$_POST['mb_flag']  = $myrow["dflt_mb_flag"];
		$_POST['dim1']  = $myrow["dflt_dim1"];
		$_POST['dim2']  = $myrow["dflt_dim2"];
		$_POST['no_sale']  = $myrow["dflt_no_sale"];
		$_POST['restricted']  = $myrow["restricted"];
		$_POST['disc1'] = number_format($myrow['disc1'],user_percent_dec());
		$_POST['disc2'] = number_format($myrow['disc2'],user_percent_dec());
		$_POST['disc3'] = number_format($myrow['disc3'],user_percent_dec());
		$_POST['disc4'] = number_format($myrow['disc4'],user_percent_dec());
		$_POST['disc5'] = number_format($myrow['disc5'],user_percent_dec());
		$_POST['disc6'] = number_format($myrow['disc6'],user_percent_dec());

	} 
	hidden('selected_id', $selected_id);
	hidden('category_id');
} else if ($Mode != 'CLONE') {
	if (!isset($_POST['long_description']))
		$_POST['long_description'] = '';
	if (!isset($_POST['description']))
		$_POST['description'] = '';
	$_POST['no_sale']  = 0;
	$company_record = get_company_prefs();
		$_POST['restricted']  = 0;

    if (get_post('inventory_account') == "")
    	$_POST['inventory_account'] = $company_record["default_inventory_act"];

    if (get_post('cogs_account') == "")
    	$_POST['cogs_account'] = $company_record["default_cogs_act"];

	if (get_post('sales_account') == "")
		$_POST['sales_account'] = $company_record["default_inv_sales_act"];

	if (get_post('adjustment_account') == "")
		$_POST['adjustment_account'] = $company_record["default_adj_act"];

	if (get_post('assembly_account') == "")
		$_POST['assembly_account'] = $company_record["default_assembly_act"];



}

text_row(_("Category Name:"), 'description', null, 30, 30);  

table_section_title(_("Default values for new items"));

item_tax_types_list_row(_("Item Tax Type:"), 'tax_type_id', null);

stock_item_types_list_row(_("Item Type:"), 'mb_flag', null, true);

stock_units_list_row(_("Units of Measure:"), 'units', null);

check_row(_("Exclude from sales:"), 'no_sale');

/*check_row(_("Item Restricted"), 'restricted');*/

gl_all_accounts_list_row(_("Sales Account:"), 'sales_account', $_POST['sales_account']);

if (is_service($_POST['mb_flag']))
{
	gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
	hidden('inventory_account', $_POST['inventory_account']);
	hidden('adjustment_account', $_POST['adjustment_account']);
}
else
{
	gl_all_accounts_list_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);

	gl_all_accounts_list_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
	gl_all_accounts_list_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
}

if (is_manufactured($_POST['mb_flag']))
	gl_all_accounts_list_row(_("Item Assembly Costs Account:"), 'assembly_account', $_POST['assembly_account']);
else
	hidden('assembly_account', $_POST['assembly_account']);

$dim = get_company_pref('use_dimension');
if ($dim >= 1)
{
	dimensions_list_row(_("Dimension")." 1", 'dim1', null, true, " ", false, 1);
	if ($dim > 1)
		dimensions_list_row(_("Dimension")." 2", 'dim2', null, true, " ", false, 2);
}
if ($dim < 1)
	hidden('dim1', 0);
if ($dim < 2)
	hidden('dim2', 0);

hidden('disc1',0);
hidden('disc2',0);
hidden('disc3',0);
hidden('disc4',0);
hidden('disc5',0);
hidden('disc6',0);
/*percent_row("Default Discount :",'disc1',$_POST['disc1']);
percent_row("Discount 2:",'disc2',$_POST['disc2']);
percent_row("Discount 3:",'disc3',$_POST['disc3']);
percent_row("Discount 4:",'disc4',$_POST['disc4']);
percent_row("Discount 5:",'disc5',$_POST['disc5']);
percent_row("Discount 6:",'disc6',$_POST['disc6']);*/
end_table(1);
div_end();
submit_add_or_update_center($selected_id == -1, '', 'both', true);
end_form();

end_page();

?>
