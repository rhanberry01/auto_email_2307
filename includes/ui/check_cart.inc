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

class check_cart
{

	var $trans_no; 
	var $type;
	var $person_id = '';
	var $person_name = '';
	var $person_type;
	var $date_;
	var $amount = 0; /*Total amount of the transaction in FX */
	
	var $checks; /*array of transactions allocated to */	

	function check($type, $trans_no)
	{
		$this->allocs = array();
		
		$this->trans_no = $trans_no;
		$this->type = $type;
		$this->read(); // read payment or credit
	}

	function add_item($bank, $branch, $check_number, $check_date, $check_amount)
	{
		if ($check_amount > 0)
		{
			$this->checks[count($this->checks)] = new check_item($bank, $branch, $check_number, $check_date, $check_amount);
			$this->amount += $check_amount;
			return true;
		} 
		else 
			return false;
	}
	
	function delete_check_item($id)
	{
		$this->amount -= $this->checks[$id]->check_amount;
		// $this->checks[$id]->deleted = true;
		array_splice($this->checks, $id, 1);
	}
	
	function edit_item($id, $bank, $branch, $check_number, $check_date, $check_amount)
	{
		$this->amount -= $this->checks[$id]->check_amount;
		$this->checks[$id]->check_bank = trim($bank);
		$this->checks[$id]->check_branch = trim($branch);
		$this->checks[$id]->check_number = trim($check_number);
		$this->checks[$id]->check_date = trim($check_date);
		$this->checks[$id]->check_amount = trim($check_amount);
		$this->amount += $check_amount;
	}
	
	function check_check($bank, $branch, $check_number, $line_no = -1)
	{
		foreach ($this->checks as $id=>$check_item)
		{
			if ($id == $line_no)
				continue;
			
			if ($this->checks[$id]->check_bank == trim($bank) AND  $this->checks[$id]->check_branch== trim($branch)
					AND  $this->checks[$id]->check_number == trim($check_number))
				return $id;
		}
		
		return null;
	}

}

//-----------------------------------------------------------------------------------

class check_item 
{
	var $check_bank;
	var $check_branch;
	
	var $check_number;
	var $check_date;
	var $check_amount;
	
	var $deleted;
	
	function check_item($check_bank, $branch, $check_number, $check_date, $check_amount)
	{
		$this->check_bank = trim($check_bank);
		$this->check_branch = trim($branch);
		$this->check_number = trim($check_number);
		$this->check_date = trim($check_date);
		$this->check_amount = trim($check_amount);
		$this->deleted = false;
	}
}

//-----------------------------------------------------------------------------------
function show_check_cart($session)
{
	global $table_style, $systypes_array,$Ajax;
	
    $k = $counter = $total_allocated = 0;

	start_table("$table_style width=65%");
		
	$th = array('Bank', 'Branch', 'Check #', 'Check Date', 'Amount','','');
	table_header($th);

	display_heading('Check Details');
	
	$id = find_submit('Edit');
	
	if ($id != -1)
		$Ajax->activate('show_check_cart');

	foreach ($session->checks as $line_no=>$check_item)
	{
		
		if ($check_item->deleted == true)
			continue;
			
		start_row();
		
		if ($line_no != $id)
		{
			label_cell($check_item->check_bank);
			label_cell($check_item->check_branch);
			label_cell($check_item->check_number);
			label_cell($check_item->check_date);
			amount_cell($check_item->check_amount);
			
			edit_button_cell("Edit$line_no", _("Edit"),
					_('Edit document line'));
			delete_button_cell("Delete$line_no", _("Delete"),
					_('Edit document line'));		
		}
		else
		{
			$_POST['c_bank'] = $check_item->check_bank;
			$_POST['c_branch'] = $check_item->check_branch;
			$_POST['c_no'] = $check_item->check_number;
			$_POST['c_date'] = $check_item->check_date;
			$_POST['c_amt'] = $check_item->check_amount;
			
			text_cells('','c_bank', null, 20, 40);
			text_cells('', 'c_branch', null, 20, 40);
			text_cells('', 'c_no', null, 20, 40);
			date_cells('', 'c_date',null, null, 0, 0, 1001);
			amount_cells('', 'c_amt', null, null, null, null, 'amount2');
			button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
			button_cell('CancelItemChanges', _("Cancel"),
					_('Cancel changes'), ICON_CANCEL);
			hidden('LineNo', $line_no);
			set_focus('qty');
		}
		end_row();
	}
	
	if ($id == -1)
	{
		text_cells('','c_bank', null, 20, 40);
		text_cells('', 'c_branch', null, 20, 40);
		text_cells('', 'c_no', null, 20, 40);
		date_cells('', 'c_date',null, null, 0, 0, 1001);
		amount_cells('', 'c_amt', null, null, null, null, 'amount2');
		submit_cells('add_c', _("Add Check"), "colspan=2", _('Add new check to OR'), true);
	}
	end_table(2);
}

?>
