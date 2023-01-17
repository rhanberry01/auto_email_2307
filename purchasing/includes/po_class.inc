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
/* Definition of the purch_order class to hold all the information for a purchase order and delivery
*/

include_once($path_to_root . "/taxes/tax_calc.inc");

class purch_order 
{

	var $supplier_id;
	
	var $line_items; /*array of objects of class line_details using the product id as the pointer */
	var $curr_code;
	var $requisition_no;
	var $delivery_address;
	var $Comments;
	var $Location;
	var $supplier_name;
	var $orig_order_date;
	var $order_no; /*Only used for modification of existing orders otherwise only established when order committed */
	var $lines_on_order;
	var $rcomments;
	
	var $reference;
	
	var $actions;
	
	function purch_order()
	{
		/*Constructor function initialises a new purchase order object */
		$this->line_items = array();
		$this->lines_on_order = $this->order_no = $this->supplier_id = 0;
	}

	function add_to_order($line_no, $stock_id, $qty, $item_descr, $price, $uom, $req_del_date, $qty_inv, $qty_recd,$or_no=0,$rnotes='',
		$disc_percent1,$disc_percent2,$disc_percent3,$disc_amount1,$disc_amount2, $disc_amount3,$extended,$item_row='')
	{
		if ($qty != 0 && isset($qty))
		{
			$this->line_items[$line_no] = new line_details($line_no, $stock_id, $item_descr, $qty, $price, $uom, $req_del_date, $qty_inv, $qty_recd,$or_no,$rnotes,
				$disc_percent1,$disc_percent2,$disc_percent3,$disc_amount1,$disc_amount2, $disc_amount3,$extended,$item_row);
			$this->lines_on_order++;
			Return 1;
		}
		Return 0;
	}

	function update_order_item($line_no, $qty, $price, $req_del_date)
	{
		$this->line_items[$line_no]->quantity = $qty;
		$this->line_items[$line_no]->price = $price;
		$this->line_items[$line_no]->req_del_date = $req_del_date;
		$this->line_items[$line_no]->price = $price;
	}

	function remove_from_order($line_no)
	{
		 $this->line_items[$line_no]->Deleted = true;
	}
	
	function order_has_items() 
	{
		if (count($this->line_items) > 0)
		{
		   	foreach ($this->line_items as $ordered_items) 
		   	{
				if ($ordered_items->Deleted == false)
				{
					return true;
				}
		   	}
		}
		return false;		
	}
	
	function clear_items() 
	{
    	unset($this->line_items);
		$this->line_items = array();
		
		$this->lines_on_order = 0;  
		$this->order_no = 0;
	}

	function get_items_total()
	{
		$total = 0;

		foreach ($this->line_items as $ln_itm) {
			$price = $ln_itm->price;
			if($ln_itm->Deleted==false){
			$total += round($ln_itm->quantity * $price, 
			   user_price_dec());
			}
		}
		return $total;
	}

	function any_already_received()
	{
		/* Checks if there have been deliveries or invoiced entered against any of the line items */
		if (count($this->line_items) > 0)
		{
		   	foreach ($this->line_items as $ordered_items) 
		   	{
				if ($ordered_items->qty_received != 0 || $ordered_items->qty_inv != 0)
				{
					return 1;
				}
		   	}
		}
		return 0;
	}

	function some_already_received($line_no)
	{
		/* Checks if there have been deliveries or amounts invoiced against a specific line item */
		if (count($this->line_items) > 0)
		{
		   	if ($this->line_items[$line_no]->qty_received != 0 || 
		   		$this->line_items[$line_no]->qty_inv != 0)
		 	{
				return 1;
		   	}
		}
		return 0;
	}
	
	
	function get_taxes($tax_group_id=null, $shipping_cost=0, $gl_codes=true)
    {
		$vat_inc = 1;
    	$items = array();
    	$prices = array();
    	
        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;
        	
		// preload the taxgroup !
		$tax_group = get_tax_group_items_as_array($tax_group_id);    	
		
		//foreach ($this->grn_items as $ln_itm) 
        foreach ($this->line_items as $ordered_items) 
        {
			if ($ordered_items->Deleted == false)
			{
				$items[] = $ordered_items->stock_id;
				$prices[] =round( ($ordered_items->quantity * $ordered_items->taxfree_charge_price($tax_group_id, $tax_group)),
				 user_price_dec());
			}
        }

        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;        	
        
        $taxes = get_tax_for_items($items, $prices, $shipping_cost, $tax_group_id, $vat_inc);

        return $taxes;
    }	
	
	function get_taxes_for_receive($tax_group_id=null, $shipping_cost=0, $gl_codes=true)
    {
		$vat_inc = 1;
    	$items = array();
    	$prices = array();
    	
        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;
        	
		// preload the taxgroup !
		$tax_group = get_tax_group_items_as_array($tax_group_id);    	
		
		//foreach ($this->grn_items as $ln_itm) 
        foreach ($this->line_items as $ordered_items) 
        {
			if ($ordered_items->Deleted == false)
			{
				$items[] = $ordered_items->stock_id;
				$prices[] =round( ($ordered_items->receive_qty * $ordered_items->taxfree_charge_price($tax_group_id, $tax_group)),
				 user_price_dec());
			}
        }

        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;        	
        
        $taxes = get_tax_for_items($items, $prices, $shipping_cost, $tax_group_id, $vat_inc);

        return $taxes;
    }	
	
	
} /* end of class defintion */

class line_details 
{

	Var $line_no;
	Var $po_detail_rec;
	Var $stock_id;
	Var $item_description;
	Var $quantity;
	Var $price;
	Var $units;
	Var $req_del_date;
	Var $qty_inv;
	Var $or_no;
	Var $qty_received;
	Var $standard_cost;
	Var $receive_qty;
	Var $Deleted;	
	Var $rnotes;
	
	var $disc_percent1;
	var $disc_percent2;
	var $disc_percent3;
	var $disc_amount1;
	var $disc_amount2;
	var $disc_amount3;
	var $extended;
	
	var $item_row = array();
	
	function line_details($line_no, $stock_item, $item_descr, $qty, $prc, $uom, $req_del_date, $qty_inv, $qty_recd,$or_no,$rnotes,
		$disc_percent1,$disc_percent2,$disc_percent3,$disc_amount1,$disc_amount2, $disc_amount3,$extended,$item_row='')
	{

		/* Constructor function to add a new LineDetail object with passed params */
		$this->line_no = $line_no;
		$this->stock_id = $stock_item;
		$this->item_description = $item_descr;
		$this->quantity = $qty;
		$this->req_del_date = $req_del_date;
		$this->price = $prc;
		$this->units = $uom;
		$this->qty_received = $qty_recd;
		$this->qty_inv = $qty_inv;
		$this->receive_qty = 0;	/*initialise these last two only */
		$this->standard_cost =0;
		$this->or_no=$or_no;
		$this->Deleted = false;
		$this->rnotes=$rnotes;
		
		$this->disc_percent1 = $disc_percent1;
		$this->disc_percent2 = $disc_percent2;
		$this->disc_percent3 = $disc_percent3;
		$this->disc_amount1 = $disc_amount1;
		$this->disc_amount2 = $disc_amount2;
		$this->disc_amount3 = $disc_amount3;
		$this->extended = $extended;
		
		if (is_array($item_row))
			$this->item_row = $item_row;
	}
	
	function taxfree_charge_price($tax_group_id, $tax_group=null)
	{
//		if ($tax_group_id==null)
//			return $this->chg_price;
		return get_tax_free_price_for_item($this->stock_id, $this->price, 
		  $tax_group_id, 0, $tax_group);
	}
}

class action_details
{
	var $act;
	var $stock_id;
	var $item_description;
	var $quantity;
	var $price;
	var $discount_percent;
	var $discount_percent2;
	var $discount_percent3;
	var $comment;

	function action_details($act, $stock_id, $qty, $prc, $disc_percent, $disc_percent2, $disc_percent3, $comment, $description='')
	{
	/* Constructor function to add a new LineDetail object with passed params */
		$item_row = get_item($stock_id);
		$this->act = $act;
		if ($item_row == null)
			display_db_error("invalid item added to order : $stock_id", "");

		if ($description == null)
			$this->item_description = $item_row["description"];
		else
			$this->item_description = $description;
		$this->tax_type_name = $item_row["tax_type_name"];

		$this->stock_id = $stock_id;
		$this->quantity = $qty;
		$this->price = $prc;
		$this->discount_percent = $disc_percent;
		$this->discount_percent2 = $disc_percent2;
		$this->discount_percent3 = $disc_percent3;
		$this->comment = $comment;
	}

}

?>
