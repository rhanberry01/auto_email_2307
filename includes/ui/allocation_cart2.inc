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
/*
	Class for supplier/customer payment/credit allocations edition
	and related helpers.
*/
//-----------------------------------------------------------------------------------

include_once($path_to_root . "/sales/includes/sales_ui.inc");

class allocation 
{

	var $trans_no; 
	var $type;
	var $person_id = '';
	var $person_name = '';
	var $person_type;
	var $date_;
	var $amount = 0; /*Total amount of the transaction in FX */
	var $amount_remaining = 0; /*Total amount of the transaction in FX */
	var $ewt= 0; /*EWT $amount + $ewt = allocatable*/
	var $discount= 0;
	
	var $allocs; /*array of transactions allocated to */	

	function allocation($type, $trans_no)
	{
		$this->allocs = array();
		
		$this->trans_no = $trans_no;
		$this->type = $type;
		$this->read(); // read payment or credit
	}

	
	function update_ewt_using_item()
	{
		$this->ewt = 0;
		foreach ($this->allocs as $alloc_item)
		{
			// display_error($alloc_item->current_allocated);
			// $this->ewt +=  round2($alloc_item->current_allocated/$alloc_item->amount,2) * round2(($alloc_item->net_amount * $alloc_item->ewt/100),2);
			//$this->ewt +=  round2(($alloc_item->tax_total/$alloc_item->amount)*$alloc_item->current_allocated/1.12,2) * round2($alloc_item->ewt/100,2);

			if($alloc_item->type==ST_SUPPINVOICE)
			$this->ewt +=  round2(($alloc_item->tax_total/$alloc_item->amount)*$alloc_item->current_allocated/1.12,2) * round2($alloc_item->ewt/100,2);
			else
			$this->ewt +=  round2($alloc_item->current_allocated/1.12,2) * round2($alloc_item->ewt/100,2);
		}
		
		return $this->ewt;
	}
	
	
	function add_item($type, $type_no, $date_, $due_date, $amount, $amount_allocated, 
		$current_allocated, $ewt)
	{
		if ($amount > 0)
		{
			$this->allocs[count($this->allocs)] = new allocation_item($type, $type_no, 
				$date_, $due_date, $amount, $amount_allocated, $current_allocated, $ewt);
			return true;
		} 
		else 
		{
			return false;
		}
	}
	
	function update_item($index, $type, $type_no, $date_, $due_date, 
		$amount, $amount_allocated, $current_allocated, $ewt)
	{
		if ($amount > 0)
		{
			$this->allocs[$index] = new allocation_item($type, $type_no, 
				$date_, $due_date, $amount, $amount_allocated, $current_allocated, $ewt);
			return true;
		} 
		else 
		{
			return false;
		}
	}	
	
	function add_or_update_item($type, $type_no, $date_, $due_date, 
		$amount, $amount_allocated, $current_allocated, $ewt)
	{
		for ($i = 0; $i < count($this->allocs); $i++) 
		{
			$item = $this->allocs[$i];
			if (($item->type == $type) && ($item->type_no == $type_no)) 
			{
				return $this->update_item($i, $type, $type_no, $date_, $due_date, 
					$amount, $amount_allocated, $current_allocated, $ewt);
			}  
		}
        return $this->add_item($type, $type_no, $date_, $due_date, 
        	$amount, $amount_allocated, $current_allocated, $ewt);
	}			
	
	//
	//	Read payment or credit current/available allocations to cart.
	//
	function read($type = null, $trans_no = 0) 
	{
		if ($type == null) {	// re-read
			$type = $this->type;
			$trans_no = $this->trans_no;
		}
		if ($type == ST_BANKPAYMENT || $type == ST_BANKDEPOSIT) {
			$bank_trans = db_fetch(get_bank_trans($type, $trans_no));
			$this->person_type = $bank_trans['person_type_id'] == PT_SUPPLIER;
		} else
			$this->person_type = $type == ST_SUPPCREDIT || $type == ST_SUPPAYMENT || $type == ST_SUPPCREDITMEMO;
		$this->allocs = array();

		if ($trans_no) {
			$trans = $this->person_type ? get_supp_trans($trans_no, $type) 
				: get_customer_trans($trans_no, $type);

			$this->person_id = $trans[$this->person_type ? 'supplier_id':'debtor_no'];
			$this->person_name = $trans[$this->person_type ? "supplier_name":"DebtorName"];
			$this->amount = $trans["Total"];
			$this->date_ = sql2date($trans["tran_date"]);
		} 
		else {
			$this->person_id = get_post($this->person_type ? 'supplier_id':'customer_id');
			$this->date_ = get_post($this->person_type ? 'DatePaid':'DateBanked', Today());
		}

	/* Now populate the array of possible (and previous actual) allocations 
		for this customer/supplier. First get the transactions that have 
		outstanding balances ie Total-alloc >0 */

		if ($this->person_type)
			$trans_items = get_allocatable_to_supp_transactions($this->person_id);
		else
		 	$trans_items = get_allocatable_to_cust_transactions($this->person_id);

		while ($myrow = db_fetch($trans_items))
		{
			$this->add_item($myrow["type"], $myrow["trans_no"],
				sql2date($myrow["tran_date"]),
				sql2date($myrow["due_date"]),
				$myrow["Total"], // trans total
				$myrow["alloc"], // trans total allocated
				0, 1); // this allocation
		}

		if ($trans_no == 0) return; // this is new payment

	/* Now get trans that might have previously been allocated to by this trans
	NB existing entries where still some of the trans outstanding entered from
	above logic will be overwritten with the prev alloc detail below */

		if ($this->person_type)
			$trans_items = get_allocatable_to_supp_transactions($this->person_id, 
				$trans_no, $type);
		else
		 	$trans_items = get_allocatable_to_cust_transactions($this->person_id, 
				$trans_no, $type);
	
		while ($myrow = db_fetch($trans_items))
		{
			$this->add_or_update_item ($myrow["type"], $myrow["trans_no"],
				sql2date($myrow["tran_date"]),
				sql2date($myrow["due_date"]),
				$myrow["Total"],
				$myrow["alloc"] - $myrow["amt"], $myrow["amt"], 1);
		}
	}
	//
	//	Update allocations in database.
	//
	function write()
	{
		begin_transaction();

		if ($this->person_type)
			clear_supp_alloctions($this->type, $this->trans_no, $this->date_);
		else
		 	clear_cust_alloctions($this->type, $this->trans_no, $this->date_);

		// now add the new allocations
		$total_allocated = 0;
		foreach ($this->allocs as $alloc_item)
		{
			if ($alloc_item->current_allocated > 0)
			{
				if ($this->person_type) {
					add_supp_allocation($alloc_item->current_allocated,
						$this->type, $this->trans_no,
			    	 	$alloc_item->type, $alloc_item->type_no, $this->date_);

					update_supp_trans_allocation($alloc_item->type, 
						$alloc_item->type_no, $alloc_item->current_allocated);
					
					$other = $alloc_item->amount_allocated;
					
					// ------------------------------  UPDATING OF PURCHASE BOOKS
					$sql1 = "SELECT ov_amount, ov_gst , ov_discount FROM `0_supp_trans` WHERE type = ".ST_SUPPINVOICE." AND trans_no = ".$alloc_item->type_no;
					$res1 = db_query($sql1);
					$row1 = db_fetch($res1);
					
					$tax_percent = ($row1['ov_gst'] / $row1['ov_amount']); // in decimal format
					
					$sql__ = "SELECT * FROM ".TB_PREF."books_purchase WHERE trans_type = ".ST_SUPPINVOICE." AND trans_no = ".$alloc_item->type_no;
					$res__ = db_query($sql__);
					$row__ = db_fetch($res__);
					
					$sql2 = "SELECT  SUM(amt) FROM ".TB_PREF."supp_allocations 
									WHERE trans_no_to = ".$row__['trans_no']."
									AND trans_type_to = 20
									AND trans_type_from = 21";
					$res2 = db_query($sql2);
					$row2 = db_fetch($res2);
					
					$current_p_amt = ($row__['purchase_amount']) - ($alloc_item->amount_allocated - $row2[0]  / (1 + $tax_percent)) ;
					$current_input_tax = $row__['input_tax'] - (($alloc_item->amount_allocated - $row2[0]) - (($alloc_item->amount_allocated  - $row2[0]) / (1 + $tax_percent)));
				
					
					$current_p_amt = $row__['purchase_amount'] + ($alloc_item->current_allocated  / (1 + $tax_percent));
					$new_input_tax = $row__['input_tax'] + ($alloc_item->current_allocated - ($alloc_item->current_allocated  / (1 + $tax_percent)));
					
					
					update_purchase_book(sql2date($row__['date']), $row__['supplier_id'], ST_SUPPINVOICE, $row__['trans_no'], $row__['reference'], 
						$current_p_amt, $row__['ewt'], $new_input_tax,
						$row__['discount'],$row__['returns']+$trans['ov_amount']);
					//------------------------------------------------------------------------
					
				} else {
	 				add_cust_allocation($alloc_item->current_allocated,
						$this->type, $this->trans_no,
 			     		$alloc_item->type, $alloc_item->type_no, $this->date_);
			
	 				update_debtor_trans_allocation($alloc_item->type, 
						$alloc_item->type_no, $alloc_item->current_allocated);
				}
				// Exchange Variations Joe Hunt 2008-09-20 ////////////////////

				exchange_variation($this->type, $this->trans_no,
					$alloc_item->type, $alloc_item->type_no, $this->date_,
					$alloc_item->current_allocated,
					$this->person_type ? PT_SUPPLIER : PT_CUSTOMER);
				

				//////////////////////////////////////////////////////////////
				$total_allocated += $alloc_item->current_allocated;
			}

		}  /*end of the loop through the array of allocations made */
		if ($this->person_type)
			update_supp_trans_allocation($this->type, $this->trans_no, 
				$total_allocated);
		else
		 	update_debtor_trans_allocation($this->type,	$this->trans_no, 
				$total_allocated);
	
		commit_transaction();

	}

	function get_total_allocations()
	{
		$total = 0;
		foreach($this->allocs as $alloc_item)
		{
			$total += $alloc_item->current_allocated;
		}
		return $total;
	}
	
	function get_total_ewt()
	{
		$total = 0;
		foreach($this->allocs as $alloc_item)
		{
			//$total += ($alloc_item->current_allocated * ($alloc_item->ewt / 100));
			$total += (($alloc_item->current_allocated / 1.12) * ($alloc_item->ewt / 100));
		}
		return $total;
	}
	
	function clear_allocations()
	{
		foreach($this->allocs as $alloc_item)
		{
			$alloc_item->current_allocated = 0;
			$alloc_item->ewt = 0;
		}
	}

} 

//-----------------------------------------------------------------------------------

class allocation_item 
{

	var $type;
	var $type_no;
	
	var $date_;
	var $due_date;
	
	var $amount_allocated;
	var $amount;
	
	var $net_amount;
	var $tax_total;
	var $current_allocated;
	
	var $ewt;
	
	function allocation_item ($type, $type_no, $date_, $due_date, $amount, 
		$amount_allocated, $current_allocated, $ewt=0)
	{

		$this->type = $type;
		$this->type_no = $type_no;
		
		$this->date_ = $date_;
		$this->due_date = $due_date;
		
		$this->amount = $amount;
		$this->amount_allocated = $amount_allocated;
		$this->current_allocated = $current_allocated;
		$this->tax_total=$this->get_trans_ewt2($type,$type_no);
		$this->ewt = $ewt;
		$this->net_amount = $this->amount - $this->get_trans_ewt($type, $type_no);
	}
	
	function get_trans_ewt($type, $type_no)
	{
		$tax_tot = 0;
		$res = get_trans_tax_details($type, $type_no);
		while($row = db_fetch($res))
		{
			$tax_tot += $row['amount'];
		}
	
		return round($tax_tot,2);
	}

	function get_trans_ewt2($type, $type_no)
	{
		$tax_tot = 0;
		$res = get_trans_tax_details($type, $type_no);
		while($row = db_fetch($res))
		{
			$tax_tot += $row['net_amount']+$row['net_amount']*($row['rate']/100);
		}
	
		return round($tax_tot,2);
	}
}

//--------------------------------------------------------------------------------

function show_allocatable($show_totals) {
	global $table_style, $systypes_array;
	
    $k = $counter = $total_allocated = 0;

	div_start('alloc_tbl');
	
	if (count($_SESSION['s_alloc']->allocs)) 
	{			
		
		start_table("$table_style width=60%");
   		$th = array(_("Transaction Type"), _("#"), _("Date"), _("Due Date"), _("Amount"),
   			_("Other Allocations"), _("This Allocation"), _("EWT %"), _("Balance"),'','');
	   	table_header($th);
		
	//	display_heading('Payables');
		
		foreach ($_SESSION['s_alloc']->allocs as $alloc_item)
	    {
		
			if (list_updated('bank_account') OR $_POST['_limiter_changed'])
			{
				$_POST["amount" . $counter] = '';
			}
		
			alt_table_row_color($k);
    		label_cell($systypes_array[$alloc_item->type]);
			if($alloc_item->type == ST_SUPPDEBITMEMO || $alloc_item->type == ST_SUPPCREDITMEMO)
				label_cell(get_gl_view_str($alloc_item->type, $alloc_item->type_no, $alloc_item->type_no), 'nowrap');
	   		else
				label_cell(get_trans_view_str($alloc_item->type, $alloc_item->type_no, getPayRef($alloc_item->type_no, $alloc_item->type)), 'nowrap');
    		label_cell($alloc_item->date_, "align=right");
    		label_cell($alloc_item->due_date, "align=right");
	    	amount_cell($alloc_item->amount);
			hidden('tot_amount'.$counter, $alloc_item->amount);
			amount_cell($alloc_item->amount_allocated);
			
			// if (isset($_POST['_ewt_percent_changed']))
				// display_error($_POST['_ewt_percent_changed']);

    		amount_cells(null, "amount" . $counter, (input_num("amount" . $counter) != 0 ? $_POST["amount" . $counter] : ''),
				null, null, null, 'amount_submit');

			amount_cells(null, 'ewt_percent'.$counter, (input_num("ewt_percent" . $counter) != 0 ? price_format("ewt_percent" . $counter) : ''),
				null, null, null, 'amount_submit');
				
			$alloc_item->ewt = input_num("ewt_percent" . $counter);

	    	// $un_allocated = round($alloc_item->amount - $alloc_item->amount_allocated, 6);
	    	$un_allocated = round($alloc_item->amount - $alloc_item->current_allocated - $alloc_item->amount_allocated, 2);
    		amount_cell($un_allocated, false,'', 'maxval'.$counter);
			
			// label_cell("<a href='#' name=Alloc$counter onclick='allocate_all(this.name.substr(5));return true;'>"
				 // . _("All") . "</a>");
				 
			// label_cell("<a href='#' name=DeAll$counter onclick='allocate_none(this.name.substr(5));return true;'>"
				 // . _("None") . "</a>".hidden("un_allocated" . $counter, 
				 // price_format($un_allocated), false));
			edit_button_cell("All".$counter, _("All"), false,true);
			edit_button_cell("None".$counter, _("None"), false,true);
				 
			end_row();

   	    	$total_allocated += $alloc_item->current_allocated;
	   	    $counter++;
	   	}
		
		if ($show_totals) 
		{
    	   	label_row(_("Total Allocated"), price_format($total_allocated),
	    		"colspan=6 align=right", "align=right id='total_allocated'", 3);
			$amount = $_SESSION['s_alloc']->amount;
			$ewt = $_SESSION['s_alloc']->ewt;
			$discount = $_SESSION['s_alloc']->discount;

			if ($_SESSION['s_alloc']->type == ST_SUPPCREDIT
				|| $_SESSION['s_alloc']->type == ST_SUPPAYMENT
				||  $_SESSION['s_alloc']->type == ST_BANKPAYMENT
				||  $_SESSION['s_alloc']->type == ST_SUPPCREDITMEMO)
				$amount = -$amount;
			
			if (round2($amount + $ewt + $discount - $total_allocated,2) < 0)
	        {
        		$font1 = "<font color=red>";
        		$font2 = "</font>";
    	    }
	        else
        		$font1 = $font2 = "";
			$left_to_allocate = price_format(round2($amount + $ewt + $discount - $total_allocated,2));
	        label_row(_("Left to Allocate"), $font1 . $left_to_allocate . $font2, 
				"colspan=6 align=right", "nowrap align=right id='left_to_allocate'",
				 3);
		}
		end_table(1);
	}
	hidden('TotalNumberOfAllocs', $counter);
	
	div_end();
}
//--------------------------------------------------------------------------------

function check_allocations()
{
	global $SysPrefs;

	$total_allocated = 0;

	for ($counter = 0; $counter < $_POST["TotalNumberOfAllocs"]; $counter++)
	{
		if (!check_num('amount' . $counter, 0))
		{
			display_error(_("The entry for one or more amounts is invalid or negative."));
			set_focus('amount'.$counter);
			return false;
		 }

		  /*Now check to see that the AllocAmt is no greater than the
		 amount left to be allocated against the transaction under review */
		 //display_error(input_num('un_allocated' . $counter));
		 if (input_num('amount' . $counter) > input_num('un_allocated' . $counter))
		 {
			display_error(_("At least one transaction is overallocated."));
			set_focus('amount'.$counter);
			return false;
		 }

		 $_SESSION['s_alloc']->allocs[$counter]->current_allocated = input_num('amount' . $counter);

		 $total_allocated += input_num('amount' . $counter);
	}

	$amount = $_SESSION['s_alloc']->amount;
	

	if (in_array($_SESSION['s_alloc']->type, array(ST_BANKPAYMENT, ST_SUPPCREDIT, ST_SUPPAYMENT, ST_SUPPCREDITMEMO)))
		$amount = -$amount;

	if ($total_allocated - ($amount + input_num('discount') + input_num('ewt'))  > $SysPrefs->allocation_settled_allowance())
	{
		//display_error(_("These allocations cannot be processed because the amount allocated is more than the total amount left to allocate."));
		display_error(_("These allocations cannot be processed because 'This Allocation Column' is more than the 'Left to Allocate Column'."));
		return false;
	}

	return true;
}

?>