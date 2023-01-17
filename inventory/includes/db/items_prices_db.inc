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
function add_item_price($stock_id, $sales_type_id, $curr_abrev, $price)
{
	$sql = "INSERT INTO ".TB_PREF."prices (stock_id, sales_type_id, curr_abrev, price) 
		VALUES (".db_escape($stock_id).", ".db_escape($sales_type_id)
		.", ".db_escape($curr_abrev).", ".db_escape($price).")";
	
	db_query($sql,"an item price could not be added");		
}

function update_item_price($price_id, $sales_type_id, $curr_abrev, $price)
{
	$sql = "UPDATE ".TB_PREF."prices SET sales_type_id=".db_escape($sales_type_id).", 
		curr_abrev=".db_escape($curr_abrev).", 
		price=".db_escape($price)." WHERE id=".db_escape($price_id);
	
	db_query($sql,"an item price could not be updated");		
}

function delete_item_price($price_id)
{
	$sql="DELETE FROM ".TB_PREF."prices WHERE id= ".db_escape($price_id);
	db_query($sql,"an item price could not be deleted");			
}

function get_prices($stock_id)
{
	$sql = "SELECT ".TB_PREF."sales_types.sales_type, ".TB_PREF."prices.* 
		FROM ".TB_PREF."prices, ".TB_PREF."sales_types 
		WHERE ".TB_PREF."prices.sales_type_id = ".TB_PREF."sales_types.id 
		AND stock_id=".db_escape($stock_id)
		." ORDER BY curr_abrev, sales_type_id";	
	
	return db_query($sql,"item prices could not be retrieved");
}

function get_stock_price($price_id)
{
	$sql = "SELECT * FROM ".TB_PREF."prices WHERE id=".db_escape($price_id);
	
	$result = db_query($sql,"price could not be retrieved");
	
	return db_fetch($result);
}

function add_item_price_per_customer($stock_id, $sales_type_id, $curr_abrev, $price, $debtor_no)
{
	$sql = "INSERT INTO ".TB_PREF."prices_per_customer (stock_id, sales_type_id, curr_abrev, price, debtor_no) 
		VALUES (".db_escape($stock_id).", ".db_escape($sales_type_id)
		.", ".db_escape($curr_abrev).", ".db_escape($price).", ".db_escape($debtor_no).")";
	
	db_query($sql,"an item price could not be added");		
}

function update_item_price_per_customer($price_id, $sales_type_id, $curr_abrev, $price, $debtor_no)
{
	$sql = "UPDATE ".TB_PREF."prices_per_customer SET sales_type_id=".db_escape($sales_type_id).", 
		curr_abrev=".db_escape($curr_abrev).", 
		price=".db_escape($price).", debtor_no=".db_escape($debtor_no)." 
		WHERE id=".db_escape($price_id);
	
	db_query($sql,"an item price could not be updated");		
}

function delete_item_price_per_customer($price_id)
{
	$sql="DELETE FROM ".TB_PREF."prices_per_customer WHERE id= ".db_escape($price_id);
	db_query($sql,"an item price could not be deleted");			
}

function get_prices_per_customer($stock_id)
{
	$sql = "SELECT ".TB_PREF."sales_types.sales_type, ".TB_PREF."prices_per_customer.* 
		FROM ".TB_PREF."prices_per_customer, ".TB_PREF."sales_types 
		WHERE ".TB_PREF."prices_per_customer.sales_type_id = ".TB_PREF."sales_types.id 
		AND stock_id=".db_escape($stock_id)
		." ORDER BY curr_abrev, sales_type_id";	
	
	return db_query($sql,"item prices could not be retrieved");
}

function get_stock_price_per_customer($price_id)
{
	$sql = "SELECT * FROM ".TB_PREF."prices_per_customer WHERE id=".db_escape($price_id);
	
	$result = db_query($sql,"price could not be retrieved");
	
	return db_fetch($result);
}

?>