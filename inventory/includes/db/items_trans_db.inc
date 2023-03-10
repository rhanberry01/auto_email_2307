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
//-------------------------------------------------------------------------------------------------------------

function stock_cost_update($stock_id, $material_cost, $labour_cost, $overhead_cost,
	$last_cost)
{
	$mb_flag = get_mb_flag($stock_id);
    
    if (is_service($mb_flag))
    {
    	display_db_error("Cannot do cost update for Service item : $stock_id", "");	
    }		
	
	$update_no = 0;
	
	begin_transaction();
	
	$sql = "UPDATE ".TB_PREF."stock_master SET material_cost=".db_escape($material_cost).", 
		labour_cost=".db_escape($labour_cost).", 
		overhead_cost=".db_escape($overhead_cost).", 
		last_cost=".db_escape($last_cost)." 
		WHERE stock_id=".db_escape($stock_id);
	db_query($sql,"The cost details for the inventory item could not be updated");

	$qoh = get_qoh_on_date($_POST['stock_id']);
	
   	$date_ = Today();

	$new_cost = $material_cost + $labour_cost + $overhead_cost;
	if ($qoh > 0)
	{
    
	   	$update_no = get_next_trans_no(ST_COSTUPDATE);
		if (!is_date_in_fiscalyear($date_))
			$date_ = end_fiscalyear();
	   
		$stock_gl_code = get_stock_gl_code($stock_id);


		$value_of_change = $qoh * ($new_cost - $last_cost);

		$memo_ = "Cost was " . $last_cost . " changed to " . $new_cost . " x quantity on hand of $qoh";
		add_gl_trans_std_cost(ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["adjustment_account"], 
			$stock_gl_code["dimension_id"], $stock_gl_code["dimension2_id"], $memo_, (-$value_of_change));	   

		add_gl_trans_std_cost(ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["inventory_account"], 0, 0, $memo_, 
			$value_of_change);
   	}

	$item_row = get_item($stock_id);
	add_audit_trail(ST_COSTUPDATE, $update_no, $date_,"Cost was " . $last_cost . " changed to " . $new_cost ." For Item: ".$item_row['description']);
	commit_transaction();

	return $update_no;
}

//-------------------------------------------------------------------------------------------------------------

?>