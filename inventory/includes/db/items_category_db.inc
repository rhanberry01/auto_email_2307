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
function add_item_category($description, $tax_type_id, $sales_account, 
	$cogs_account, $inventory_account, $adjustment_account, $assembly_account, 
	$units, $mb_flag, $dim1, $dim2, $no_sale,$disc1=0,$disc2=0,$disc3=0,$disc4=0,$disc5=0,$disc6=0,$restricted=0)
{
	$sql = "INSERT INTO ".TB_PREF."stock_category (description, dflt_tax_type,
			dflt_units, dflt_mb_flag, dflt_sales_act, dflt_cogs_act, 
			dflt_inventory_act, dflt_adjustment_act, dflt_assembly_act, 
			dflt_dim1, dflt_dim2, dflt_no_sale,disc1,disc2,disc3,disc4,disc5,disc6)
		VALUES ("
		.db_escape($description).","
		.db_escape($tax_type_id).","
		.db_escape($units).","
		.db_escape($mb_flag).","
		.db_escape($sales_account).","
		.db_escape($cogs_account).","
		.db_escape($inventory_account).","
		.db_escape($adjustment_account).","
		.db_escape($assembly_account).","
		.db_escape($dim1).","
		.db_escape($dim2).","
		.db_escape($no_sale).","
		.db_escape($disc1).","
		.db_escape($disc2).","
		.db_escape($disc3).","
		.db_escape($disc4).","
		.db_escape($disc5).","
		.db_escape($disc6)
		.")";
		
	db_query($sql,"an item category could not be added");
}

function update_item_category($id, $description, $tax_type_id, 
	$sales_account, $cogs_account, $inventory_account, $adjustment_account, 
	$assembly_account, $units, $mb_flag, $dim1, $dim2, $no_sale,$disc1=0,$disc2=0,$disc3=0,$disc4=0,$disc5=0,$disc6=0)

{
	$sql = "UPDATE ".TB_PREF."stock_category SET "
		."description = ".db_escape($description).","
		."dflt_tax_type = ".db_escape($tax_type_id).","
		."dflt_units = ".db_escape($units).","
		."dflt_mb_flag = ".db_escape($mb_flag).","
		."dflt_sales_act = ".db_escape($sales_account).","
		."dflt_cogs_act = ".db_escape($cogs_account).","
		."dflt_inventory_act = ".db_escape($inventory_account).","
		."dflt_adjustment_act = ".db_escape($adjustment_account).","
		."dflt_assembly_act = ".db_escape($assembly_account).","
		."dflt_dim1 = ".db_escape($dim1).","
		."dflt_dim2 = ".db_escape($dim2).","
		."dflt_no_sale = ".db_escape($no_sale).","
		."disc1 = ".db_escape($disc1).","
		."disc2 = ".db_escape($disc2).","
		."disc3 = ".db_escape($disc3).","
		."disc4 = ".db_escape($disc4).","
		."disc5 = ".db_escape($disc5).","
		."disc6 = ".db_escape($disc6)
        ."WHERE category_id = ".db_escape($id);

	db_query($sql,"an item category could not be updated");
}

function delete_item_category($id)
{
	$sql="DELETE FROM ".TB_PREF."stock_category WHERE category_id=".db_escape($id);

	db_query($sql,"an item category could not be deleted");
}

function get_item_category($id)
{
	$sql="SELECT * FROM ".TB_PREF."stock_category WHERE category_id=".db_escape($id);

	$result = db_query($sql,"an item category could not be retrieved");

	return db_fetch($result);
}

function get_category_name($id)
{
	$sql = "SELECT description FROM ".TB_PREF."stock_category WHERE category_id=".db_escape($id);

	$result = db_query($sql, "could not get sales type");

	$row = db_fetch_row($result);
	return $row[0];
}

?>