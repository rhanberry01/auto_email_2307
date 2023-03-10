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
/* Definition of the Supplier Transactions class to hold all the information for an accounts payable invoice or credit note
*/

include_once($path_to_root . "/taxes/tax_calc.inc");

class supp_trans 
{

	var $grn_items; /*array of objects of class GRNDetails using the GRN No as the pointer */
	var $gl_codes; /*array of objects of class gl_codes using a counter as the pointer */
	var $supplier_id;
	var $supplier_name;
	var $terms_description;
	var $terms;
	
	var $tax_description;
	var $tax_group_id;
	
	var $is_invoice;

	var $Comments;
	var $tran_date;
	var $due_date;
	var $del_date;
	
	var $supp_reference;
	var $reference;
	var $special_reference;
	var $ov_amount;
	var $gl_amount;
	var $ov_discount;
	var $ov_gst;
	var $ov_nv; //non vat
	var $gl_codes_counter=0;

	var $nt;
	var $ewt_percent;
	
	var $discrepancy_id=0;
	var $choose_inv=0;
	
	var $expected_total;
	
	var $item_count=0;
	var $note;
	var $qty_total;
	var $off_inv_disc;
	
	var $purch_non_vat;
	var $purch_vat;
	var $vat;
	var $purch_discount;
	var $ewt;
	var $acounts_payable;
	var $ret_disc;
	var $add_to_ov_amount;
	
	var $purch_ret;
	var $disp_allow;
	var $trade_promo;
	var $rebate;

	function supp_trans()
	{
		/*Constructor function initialises a new Supplier Transaction object */
		$this->grn_items = array();
		$this->gl_codes = array();
	}

	function add_grn_to_trans($grn_item_id, $po_detail_item, $item_code, $item_description, 
		$qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, 
		$Complete, $std_cost_unit, $gl_code, $r_uom='', $multiplier=1,$row='')
	{
		$this->grn_items[$grn_item_id] = new grn_item($grn_item_id, $po_detail_item, 
			$item_code, $item_description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, 
			$order_price, $chg_price, $Complete, $std_cost_unit, $gl_code,$r_uom,$multiplier,$row);
		return 1;
	}

	function add_gl_codes_to_trans($gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_)
	{
		$this->gl_codes[$this->gl_codes_counter] = new gl_codes($this->gl_codes_counter, 
			$gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_);
		$this->gl_codes_counter++;
		return 1;
	}

	function remove_grn_from_trans(&$grn_item_id)
	{
	    unset($this->grn_items[$grn_item_id]);
	}
	function remove_gl_codes_from_trans(&$gl_code_counter)
	{
	     unset($this->gl_codes[$gl_code_counter]);
	}
	
	function is_valid_trans_to_post()
	{
		return (count($this->grn_items) > 0 || count($this->gl_codes) > 0 || 
			($this->ov_amount != 0) || ($this->ov_discount > 0));
	}
	
	function clear_items()
	{
		unset($this->grn_items);
		unset($this->gl_codes);
		$this->item_count = $this->ov_amount = $this->gl_amount = $this->ov_nv = $this->ov_discount = $this->supplier_id = 0;
		
		$this->grn_items = array();
		$this->gl_codes = array();		
	}
	
    function get_taxes($tax_group_id=null, $shipping_cost=0, $gl_codes=true)
    {
		$vat_inc = true;
    	$items = array();
    	$prices = array();
    	
        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;
        	
		// preload the taxgroup !
		$tax_group = get_tax_group_items_as_array($tax_group_id);    	
        
        foreach ($this->grn_items as $ln_itm) 
        {
        	$items[] = $ln_itm->item_code;
        	$prices[] =round( ($ln_itm->this_quantity_inv * $ln_itm->taxfree_charge_price($tax_group_id, $tax_group)),
			 user_price_dec());
        }

        if ($tax_group_id == null)
        	$tax_group_id = $this->tax_group_id;        	
        
        $taxes = get_tax_for_items($items, $prices, $shipping_cost, $tax_group_id, $vat_inc);

///////////////// Joe Hunt 2009.08.18

		if ($gl_codes)
		{
			foreach ($this->gl_codes as $gl_code)
			{
				$index = is_tax_account($gl_code->gl_code);
				if ($index !== false)
				{
					$taxes[$index]['Value'] += $gl_code->amount;
				}	
			}
		}	
////////////////		
        return $taxes;
    }

	function get_non_vat_item_total()
	{
		$tot = 0;
		foreach ($this->grn_items as $ln_itm) 
        {
        	$row = get_item_tax_type_for_item($ln_itm->item_code);
			
			if ($row['id'] == 2)
			{
				$tot += $ln_itm->chg_price * $ln_itm->this_quantity_inv;
			}	
		
        }
		return $tot;
	}
    
    function get_total_charged($tax_group_id=null)
    {
    	$total = 0;
    	
		// preload the taxgroup !
		if ($tax_group_id != null)
			$tax_group = get_tax_group_items_as_array($tax_group_id);
		else 		
			$tax_group = null;    	
    	
		foreach ($this->grn_items as $ln_itm)
    		$total += round(($ln_itm->this_quantity_inv * $ln_itm->taxfree_charge_price($tax_group_id, $tax_group)),
			 user_price_dec());

   		foreach ($this->gl_codes as $gl_line)
   		{	//////// 2009-08-18 Joe Hunt
   			// if (!is_tax_account($gl_line->gl_code))
   			$total += $gl_line->amount;
   		}	
   		return $total;
    }
	
    function get_total_charged_exact($tax_group_id=null)
    {
    	$total = 0;
    	
		// preload the taxgroup !
		if ($tax_group_id != null)
			$tax_group = get_tax_group_items_as_array($tax_group_id);
		else 		
			$tax_group = null;    	
    	
		foreach ($this->grn_items as $ln_itm)
    		$total += ($ln_itm->this_quantity_inv * $ln_itm->taxfree_charge_price($tax_group_id, $tax_group));

   		foreach ($this->gl_codes as $gl_line)
   		{	//////// 2009-08-18 Joe Hunt
   			// if (!is_tax_account($gl_line->gl_code))
   			$total += $gl_line->amount;
   		}	
   		return $total;
    }

} /* end of class defintion */

class grn_item 
{

/* Contains relavent information from the purch_order_details as well to provide in cached form,
all the info to do the necessary entries without looking up ie additional queries of the database again */

	var $id;
	var $po_detail_item;
	var $item_code;
	var $item_description;
	var $qty_recd;
	var $prev_quantity_inv;
	var $this_quantity_inv;
	var $order_price;
	var $chg_price;
	var $Complete;
	var $std_cost_unit;
	var $gl_code;
	var $r_uom;
	var $multiplier;
	var $row;

	function grn_item ($id, $po_detail_item, $item_code, $item_description, $qty_recd, 
		$prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, $Complete, 
		$std_cost_unit, $gl_code,$r_uom='',$multiplier=1,$row='')
	{

		$this->id = $id;
		$this->po_detail_item = $po_detail_item;
		$this->item_code = $item_code;
		$this->item_description = $item_description;
		$this->qty_recd = $qty_recd;
		$this->prev_quantity_inv = $prev_quantity_inv;
		$this->this_quantity_inv = $this_quantity_inv;
		$this->order_price =$order_price;
		$this->chg_price = $chg_price;
		$this->Complete = $Complete;
		$this->std_cost_unit = $std_cost_unit;
		$this->gl_code = $gl_code;
		
		$this->multiplier = $multiplier;
		if ($r_uom != '')
			$this->r_uom=$r_uom;
			
		if (is_array($row) AND count($row)>0)
				$this->row = $row;
	}
	
	function full_charge_price($tax_group_id, $tax_group=null)
	{
		return get_full_price_for_item($this->item_code, 
		  $this->chg_price, $tax_group_id, 0, $tax_group);
	}
	
	function taxfree_charge_price($tax_group_id, $tax_group=null)
	{
//		if ($tax_group_id==null)
//			return $this->chg_price;
		return get_tax_free_price_for_item($this->item_code, $this->chg_price, 
		  $tax_group_id, 0, $tax_group);
	}
	
	function taxfree_charge_price_vat_inc($tax_group_id, $tax_group=null)
	{
//		if ($tax_group_id==null)
//			return $this->chg_price;
		return get_tax_free_price_for_item($this->item_code, $this->chg_price, 
		  $tax_group_id, 1, $tax_group);
	}
}


class gl_codes 
{

	var $Counter;
	var $gl_code;
	var $gl_act_name;
	var $gl_dim;
	var $gl_dim2;
	var $amount;
	var $memo_;

	function gl_codes ($Counter, $gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_)
	{

	/* Constructor function to add a new gl_codes object with passed params */
		$this->Counter = $Counter;
		$this->gl_code = $gl_code;
		$this->gl_act_name = $gl_act_name;
		$this->gl_dim = $gl_dim;
		$this->gl_dim2 = $gl_dim2;
		$this->amount = $amount;
		$this->memo_= $memo_;
	}
}

?>
