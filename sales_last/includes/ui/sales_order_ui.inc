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
include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/manufacturing.inc");

//--------------------------------------------------------------------------------
function add_to_order(&$order, $new_item, $new_item_qty, $price, $discount, $discount2, $discount3, 
		$discount4, $discount5, $discount6, $comment)
{
	// calculate item price to sum of kit element prices factor for 
	// value distribution over all exploded kit items

	 $std_price = get_kit_price($new_item, $order->customer_currency, 
		$order->sales_type,	$order->price_factor, get_post('OrderDate'), true);

	if ($std_price == 0)
		$price_factor = 0;
	else
		$price_factor = $price/$std_price;

	$kit = get_item_kit($new_item);
	$item_num = db_num_rows($kit);

	while($item = db_fetch($kit)) {
		$std_price = get_kit_price($item['stock_id'], $order->customer_currency, 
			$order->sales_type,	$order->price_factor, get_post('OrderDate'), true);

		// rounding differences are included in last price item in kit
		$item_num--;
		if ($item_num) {
			$price -= $item['quantity']*$std_price*$price_factor;
			$item_price = $std_price*$price_factor;
		} else {
			if ($item['quantity']) 
				$price = $price/$item['quantity'];
			$item_price = $price;
		}
		$item_price = round($item_price, user_price_dec());

	 if (!$item['is_foreign'] && $item['item_code'] != $item['stock_id'])
	 {	// this is sales kit - recurse 
		add_to_order($order, $item['stock_id'], $new_item_qty*$item['quantity'],
			$item_price, $discount, $discount2, $discount3, 
		$discount4, $discount5, $discount6, $std_price);
	 }
	 else
	 {	// stock item record eventually with foreign code

		// check duplicate stock item
		foreach ($order->line_items as $order_item)
		{
			if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0)
			{
				display_warning(_("For Part :").$item['stock_id']. " " 
					. _("This item is already on this document. You have been warned."));
				break;
			}
		}
		$order->add_to_cart (count($order->line_items),	$item['stock_id'], 
			$new_item_qty*$item['quantity'], $item_price, $discount, $discount2, $discount3, 
			$discount4, $discount5, $discount6, $comment);
		
		$order->actions[] = new action_details('added to cart', $item['stock_id'], $new_item_qty*$item['quantity'], 
			$item_price, $discount, $discount2, $discount3, $comment);
	 }
	}
	
}
//---------------------------------------------------------------------------------

function get_customer_details_to_order(&$order, $customer_id, $branch_id)
{
	$ret_error = "";

	$myrow = get_customer_to_order($customer_id);

	$name = $myrow['name'];

	if ($myrow['dissallow_invoices'] == 1)
		$ret_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");

	$deliver = $myrow['address']; // in case no branch address use company address

	$order->set_customer($customer_id, $name, $myrow['curr_code'], 
		$myrow['discount'], $myrow['pymt_discount']);

	// the sales type determines the price list to be used by default
	$order->set_sales_type($myrow['salestype'], $myrow['sales_type'], $myrow['tax_included'],
	    $myrow['factor']);

	if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE)
	{
		$order->dimension_id = $myrow['dimension_id'];
		$order->dimension2_id = $myrow['dimension2_id'];
	}
	$result = get_branch_to_order($customer_id, $branch_id);

	if (db_num_rows($result) == 0)
	{
		return _("The selected customer and branch are not valid, or the customer does not have any branches.");
	}

	$myrow = db_fetch($result);

	$order->set_branch($branch_id, $myrow["tax_group_id"],
	$myrow["tax_group_name"], $myrow["phone"], $myrow["email"]);

	$address = trim($myrow["br_post_address"]) != '' ? $myrow["br_post_address"]
		: (trim($myrow["br_address"]) != '' ? $myrow["br_address"]:$deliver);

	$order->set_delivery($myrow["default_ship_via"], $myrow["br_name"],
		$address);
	if ($order->trans_type == ST_SALESINVOICE) {
		$order->due_date = get_invoice_duedate2($_POST['p_terms'], $order->document_date);
		if ($order->pos != -1)
			$order->cash = date_diff2($order->due_date, Today(), 'd')<2;
	}
	if($order->cash ) {
		if($order->pos != -1) {
		$paym = get_sales_point($order->pos);
		$order->set_location($paym["pos_location"], $paym["location_name"]);
		}
	} else
		$order->set_location($myrow["default_location"], $myrow["location_name"]);
		
	return $ret_error;
}

//---------------------------------------------------------------------------------

function display_order_summary($title, &$order, $editable_items=false)
{
	global $table_style, $path_to_root, $SysPrefs;

	display_heading($title);

    div_start('items_table');

	if ($order->get_total_lines() > $order->max_invoice_lines)
	{
		display_error('This transaction will exceed '.$order->max_invoice_lines.' lines for the invoice printout. Please create another transaction for other items. Line count:'.$order->get_total_lines());
	}
	
	start_table("$table_style colspan=7 width=90%");
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"),
		_("Delivered"),
		_("Unit"), _("Price"), _("Discount %"), /*_("Discount2 %"), _("Discount3 %"), _("Discount4 %"), _("Discount5 %"), _("Discount6 %"),*/ _("Notes"), _("Total"), "");

	if ($order->trans_no == 0) {
	unset( $th[3] );
	}

	if (count($order->line_items))
	     $th[]= '';

	table_header($th);

	$total = 0;
	$k = 0;  //row colour counter

	$id = find_submit('Edit');
	$has_marked = false;
	
	$vatable = $nonvat = $zerorated = 0;
	foreach ($order->line_items as $line_no=>$stock_item)
	{

		$line_total = round($stock_item->qty_dispatched * $stock_item->price * (1 - $stock_item->discount_percent) * (1 - $stock_item->discount_percent2) * (1 - $stock_item->discount_percent3)
				* (1 - $stock_item->discount_percent4) * (1 - $stock_item->discount_percent5) * (1 - $stock_item->discount_percent6),
		   user_price_dec());

		$qoh_msg = '';
		if (!$editable_items || $id != $line_no)
		{
			if (!$SysPrefs->allow_negative_stock() && is_inventory_item($stock_item->stock_id)) {
				$qoh = get_qoh_on_date($stock_item->stock_id, 
					$_POST['Location'], $_POST['OrderDate']);
				if ($stock_item->qty_dispatched > $qoh) 
				{
					// oops, we don't have enough of one of the component items
					start_row("class='stockmankobg'");
					$qoh_msg .= $stock_item->stock_id . " - " . $stock_item->item_description . ": " .
					_("Quantity On Hand") . " = " 
					. number_format2($qoh, get_qty_dec($stock_item->stock_id)) . '<br>';
					$has_marked = true;
				 } else 
					alt_table_row_color($k);
			} else {
				alt_table_row_color($k);
			}
			
			
			/////////////////////////////////////////////////////////////////
			
			$sql = "SELECT tax_type_id FROM 0_stock_master WHERE stock_id = ".db_escape($stock_item->stock_id);
			$result = db_query($sql,"could not retrieve tax_type_id");
			$row = db_fetch_row($result);		
			
			if($row[0] == 1)
				$vatable += $line_total;
			else if($row[0] == 2)
				$nonvat += $line_total;
			else
				$zerorated += $line_total;
			
			/////////////////////////////////////////////////////////////////
			

			view_stock_status_cell($stock_item->stock_id);

			//label_cell($stock_item->item_description, "nowrap" );
			label_cell($stock_item->item_description );
			$dec = get_qty_dec($stock_item->stock_id);
			qty_cell($stock_item->qty_dispatched, false, $dec);

			//display_error($stock_item->origprice);
			
			if ($order->trans_no!=0)
				qty_cell($stock_item->qty_done, false, $dec);

			label_cell($stock_item->units);
			amount_cell($stock_item->price);

			percent_cell($stock_item->discount_percent * 100);
			// percent_cell($stock_item->discount_percent2 * 100);
			// percent_cell($stock_item->discount_percent3 * 100);
			// percent_cell($stock_item->discount_percent4 * 100);
			// percent_cell($stock_item->discount_percent5 * 100);
			// percent_cell($stock_item->discount_percent6 * 100);
			
			label_cell($stock_item->comment);
			
			amount_cell($line_total);

			if ($editable_items)
			{
				edit_button_cell("Edit$line_no", _("Edit"),
				_('Edit document line'));
				echo '<td><button type="submit" class="editbutton_rev" name="Delete'.$line_no.'" value="1" title="Remove line from document"'.
						"onclick='return confirm(".'"Are you sure you want to delete this line?"'.");'"
						.'>'.set_icon(ICON_DELETE).'</button></td>';
			}
			end_row();
		}
		else
		{
			sales_order_item_controls($order, $k,  $line_no);
		}

		$total += $line_total;
	}
	
	// foreach ($order->actions as $line_no=>$action_det)
	// {
		// start_row();
		// label_cell($action_det->act.' Item:'.$action_det->item_description .' | Quantity:'.$action_det->quantity 
			// .' | Price:'.$action_det->price
			// .' | Discount 1:'.$action_det->discount_percent
			// .' | Discount 2:'.$action_det->discount_percent2
			// .' | Discount 3:'.$action_det->discount_percent3
			// .' | Note:'.$action_det->comment
			// , 'colspan=11');
		// end_row();
	// }

	if ($id==-1 && $editable_items)
		sales_order_item_controls($order, $k);

	$colspan = 7;
	if ($order->trans_no!=0)
		++$colspan;
	label_cell('', 'colspan=2');
	end_row();
	
	$taxes = $order->get_taxes(input_num('freight_cost'));
	$_tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2, 0);
	
	$display_sub_total = price_format($total + input_num('freight_cost'));
	label_row(_("Total Sales"), price_format($total), "colspan=$colspan align=right","align=right", 2);
	
	//////////////////////////////////////////////////////////////////////////////
	$sql1 = "SELECT tax_group_id FROM 0_cust_branch WHERE branch_code = ".db_escape($_POST['branch_id']);
	$result1 = db_query($sql1,"could not retrieve tax_type_id");
	$row1 = db_fetch_row($result1);

	if($row1[0] == 1)
	{
		label_row(_("VATABLE Sales"), price_format($vatable/1.12), "colspan=$colspan align=right","align=right", 2);
		label_row(_("NON-VATABLE Sales"), price_format($nonvat), "colspan=$colspan align=right","align=right", 2);
		label_row(_("ZERO RATED Sales"), price_format($zerorated), "colspan=$colspan align=right","align=right", 2);
	}
	else if($row1[0] == 2)
	{
		label_row(_("VATABLE Sales"), price_format(0), "colspan=$colspan align=right","align=right", 2);
		label_row(_("NON-VATABLE Sales"), price_format($vatable+$nonvat+$zerorated), "colspan=$colspan align=right","align=right", 2);
		label_row(_("ZERO RATED Sales"), price_format(0), "colspan=$colspan align=right","align=right", 2);
	}
	else 
	{
		label_row(_("VATABLE Sales"), price_format(0), "colspan=$colspan align=right","align=right", 2);
		label_row(_("NON-VATABLE Sales"), price_format($nonvat), "colspan=$colspan align=right","align=right", 2);
		label_row(_("ZERO RATED Sales"), price_format($vatable+$zerorated), "colspan=$colspan align=right","align=right", 2);
	}
	//////////////////////////////////////////////////////////////////////////////
	
	//display_error($tax_total);
	
	$tax_total = display_edit_tax_items($taxes, $colspan, $order->tax_included, 2);
	//$subtotal = $total + input_num('freight_cost') + $tax_total;
	$subtotal = $total;
											
	$display_total = price_format(
			
			input_num('freight_cost') + 
			( $subtotal 
			)
				
			);
	
	//display_error("$subtotal * (1 - $d1 / 100) 
			  // * (1 - $d2 / 100)  * (1 - $d3 / 100)  * (1 - $d4 / 100) 
			  // * (1 - $d5 / 100)");*/
	
// display_error(input_num('discount1')/100);
// display_error(1-input_num('discount1')) ;
	/**Added 5-level discount**/
	
	// if (isset($_GET['NewInvoice']) && is_numeric($_GET['NewInvoice'])) {
		
	start_row();
	label_cell(_("Shipping Charge"), "colspan=$colspan align=right");
	small_amount_cells(null, 'freight_cost', price_format(get_post('freight_cost',0)));
	
	start_row();
	label_cells(_("Amount Total"), $display_total, "colspan=$colspan align=right","align=right");
	hidden('tamt', $display_total);
	submit_cells('update', _("Update"), "colspan=2", _("Refresh"), true);
	end_row();

	end_table();
	if ($has_marked) {
		display_note(_("Highlighted items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='stockmankofg'");
//		if ($order->trans_type!=30 && !$SysPrefs->allow_negative_stock())
//			display_error(_("The delivery cannot be processed because there is an insufficient quantity for item:")
//				. '<br>'. $qoh_msg. "<a onclick='openprompt_()' style='cursor:pointer'>Click here to enable customer to transact</a>");
	}
    div_end();
}

// ------------------------------------------------------------------------------

function get_transactions($debtorno, $date)
{
	$date = date2sql($date);

    $sql = "SELECT ".TB_PREF."debtor_trans.*,
		(".TB_PREF."debtor_trans.ov_amount + ".TB_PREF."debtor_trans.ov_gst + ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_discount)
		AS TotalAmount, ".TB_PREF."debtor_trans.alloc AS Allocated,
		((".TB_PREF."debtor_trans.type = 10)
		AND ".TB_PREF."debtor_trans.due_date < '$date') AS OverDue
    	FROM ".TB_PREF."debtor_trans, ".TB_PREF."sys_types
    	WHERE ".TB_PREF."debtor_trans.tran_date <= '$date'
	AND ".TB_PREF."debtor_trans.debtor_no = '$debtorno'
	AND ".TB_PREF."debtor_trans.type != 13
    	AND ".TB_PREF."debtor_trans.type = ".TB_PREF."sys_types.type_id
    	ORDER BY ".TB_PREF."debtor_trans.tran_date";

    return db_query($sql,"No transactions were returned");
}

function check_credit($debtor_no)
{
	$sql = "SELECT allow_credit FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($debtor_no);

	$result = db_query($sql, "could not get allow_credit");

	$row = db_fetch_row($result);
	return $row[0];
}

function get_customer_details_to_order_so(&$order, $customer_id, $branch_id)
{
	$ret_error = "";
	// $chk_credit = 0;

	$myrow = get_customer_to_order($customer_id);

	// $name = $myrow['name'];
	
	// $date = Today();
	// $res = get_transactions($customer_id,$date );
	
	// $total_inv = 0;
	// $total_rec = 0;
	// $total_record = 0;
	// while ($trans = db_fetch($res))
	// {
		// if ($trans['type'] == 10)
			// $total_inv += $trans["TotalAmount"] - $trans["Allocated"];
		// else
			// $total_rec += $trans["TotalAmount"] - $trans["Allocated"];
			
		// $total_record = $total_inv - $total_rec;
	// }
	
	// if ($total_record > $myrow['credit_limit']  && check_credit($customer_id) == 0)
	// {
		// $chk_credit = 1;
	// }
	// hidden('hid_chk_credit', $chk_credit);
	// hidden('hid_debtor_no', $customer_id);
	
	// if($chk_credit == 1 && check_credit($customer_id) == 0)
	// {
		// $ret_error = _("The selected customer credit limit exceeds. Please contact the credit control personnel to discuss. <br><a onclick='openprompt()' style='cursor:pointer'>Click here to enable customer to transact</a>");
		
	// }
	
	if ($myrow['dissallow_invoices'] == 1)
		$ret_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");

	$deliver = $myrow['address']; // in case no delivery address.

	$order->set_customer($customer_id, $name, $myrow['curr_code'], $myrow['discount'], 0, $myrow['payment_terms']);

	// the sales type determines the price list to be used by default
	$order->set_sales_type($myrow['salestype'], $myrow['sales_type'], $myrow['tax_included'],
	    $myrow['factor']);

//	if ($order->Branch != 0)
	{
		$result = get_branch_to_order($customer_id, $branch_id);

		if (db_num_rows($result) == 0)
		{
		return _("The selected customer and branch are not valid, or the customer does not have any branches.");
		}

		$myrow = db_fetch($result);

		$order->set_branch($branch_id, $myrow["tax_group_id"],
		$myrow["tax_group_name"], $myrow["phone"], $myrow["email"]);

		$address = $myrow["br_post_address"];

		if (strlen($address) <= 1)	// if branch has no address
			$address = $deliver;	// set sales order address

		$order->set_location($myrow["default_location"], $myrow["location_name"]);
		$order->set_delivery($myrow["default_ship_via"], $myrow["br_name"],
		$address);
		if ($order->trans_type == 10)
			$order->due_date = get_invoice_duedate($customer_id, $order->document_date);
	}

	return $ret_error;
}

function display_order_header(&$order, $editable, $date_text, $display_tax_group=false)
{
	global $table_style2, $Ajax, $SysPrefs;

	start_outer_table("width=80% $table_style2");

	table_section(1);
	
	$customer_error = "";
	$change_prices = 0;

	if (isset($order) && !$editable)
	{
		// can't change the customer/branch if items already received on this order
		//echo $order->customer_name . " - " . $order->deliver_to;
		label_row(null, $order->customer_name . " - " . $order->deliver_to);
		hidden('customer_id', $order->customer_id);
		hidden('branch_id', $order->Branch);
		hidden('sales_type', $order->sales_type);
		if ($order->trans_type != ST_SALESORDER  && $order->trans_type != ST_SALESQUOTE) {
			hidden('dimension_id', $order->dimension_id); // 2008-11-12 Joe Hunt
			hidden('dimension2_id', $order->dimension2_id);
		}	
	}
	else
	{
		
		if($_SESSION['Items']->trans_no == 0){
			customer_list_row2(_("Customer:"), 'customer_id', ((list_updated('customer_id')||(!isset($_GET['NewOrder'])&&!isset($_GET['NewInvoice'])&&!isset($_GET['NewDelivery'])))?'':' '), ' ' , true, false, true);
			if ($order->customer_id != get_post('customer_id', -1))
			{
				// customer has changed
				$Ajax->activate('branch_id');
		
			}
			customer_branches_list_row(_("Branch:"),
			  $_POST['customer_id'], 'branch_id', null, false, true, true, true);
		}else{
			label_row('Customer', $order->customer_name);
			label_row('Branch', $order->deliver_to);
			hidden('customer_id', $order->customer_id);
			hidden('branch_id', $order->Branch);
		}

		

		if( ($order->customer_id != get_post('customer_id', -1)) ||
			($order->Branch != get_post('branch_id', -1)) ||
			list_updated('customer_id')) 
		{

			if (!isset($_POST['branch_id']) || $_POST['branch_id'] == "")
			{
				// ignore errors on customer search box call
				if ($_POST['customer_id'] == '')
					$customer_error = _("No customer found for entered text.");
				else
					$customer_error = _("The selected customer does not have any branches. Please create at least one branch.");
				unset($_POST['branch_id']);
				$order->Branch = 0;
			} 
			else
			{

				$old_order = (PHP_VERSION<5) ? $order : clone( $order );

				//$customer_error = get_customer_details_to_order($order, $_POST['customer_id'], $_POST['branch_id']);
				
				$customer_error = get_customer_details_to_order_so($order, $_POST['customer_id'], $_POST['branch_id']);	
				
				$_POST['Location'] = $order->Location;
				$_POST['deliver_to'] = $order->deliver_to;
				$_POST['delivery_address'] = $order->delivery_address;
				$_POST['phone'] = $order->phone;
				$_POST['p_terms'] = $order->p_terms;
				if (get_post('cash') !== $order->cash) {
					$_POST['cash'] = $order->cash;
					$Ajax->activate('delivery');
					$Ajax->activate('cash');
				} else {
					if ($order->trans_type == ST_SALESINVOICE)
					{
						$_POST['delivery_date'] = $order->due_date;
						$Ajax->activate('delivery_date');
					}
					$Ajax->activate('Location');
					$Ajax->activate('deliver_to');
					$Ajax->activate('phone');
					$Ajax->activate('delivery_address');
				}
				// change prices if necessary
				// what about discount in template case?
				if ($old_order->customer_currency != $order->customer_currency) {
					$change_prices = 1;
				}
				if ($old_order->sales_type != $order->sales_type) {
				//  || $old_order->default_discount!=$order->default_discount
					$_POST['sales_type'] = $order->sales_type;
					$Ajax->activate('sales_type');
					$change_prices = 1;
				}
				if ($old_order->dimension_id != $order->dimension_id) {
					$_POST['dimension_id'] = $order->dimension_id;
				    $Ajax->activate('dimension_id');
				}
				if ($old_order->dimension2_id != $order->dimension2_id) {
					$_POST['dimension2_id'] = $order->dimension2_id;
				    $Ajax->activate('dimension2_id');
				}
				unset($old_order);
			}
			set_global_customer($_POST['customer_id']);
		} // changed branch
		else
		{
			$row = get_customer_to_order($_POST['customer_id']);
			if ($row['dissallow_invoices'] == 1)
				$customer_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
		
		}
	}


	//ref_row(_("SO No.").':', 'ref', _('Reference number unique for this document type'), null, '');

	if ($_SESSION['Items']->trans_type == ST_SALESORDER){
		if($_SESSION['Items']->trans_no == 0){
			ref_row2(_("SO No.").':', 'ref', _('Reference number unique for this document type'), null, '');
		}else{
			label_row(_("SO No.").':', $_SESSION['Items']->reference);
			hidden('ref', $_SESSION['Items']->reference);
		}
	}else if ($_SESSION['Items']->trans_type == ST_CUSTDELIVERY){
		if($_SESSION['Items']->trans_no == 0){
			ref_row2(_("DR No.").':', 'ref', _('Reference number unique for this document type'), null, '');
		}else{
			label_row(_("DR No.").':', $_SESSION['Items']->reference);
			hidden('ref', $_SESSION['Items']->reference);
		}
	}else if ($_SESSION['Items']->trans_type == ST_SALESINVOICE){
		if($_SESSION['Items']->trans_no == 0){
			ref_row2(_("INV No.").':', 'ref', _('Reference number unique for this document type'), null, '');
		}else{
			label_row(_("INV No.").':', $_SESSION['Items']->reference);
			hidden('ref', $_SESSION['Items']->reference);
		}
	}

	if (!is_company_currency($order->customer_currency))
	{
	    table_section(2);

		label_row(_("Customer Currency:"), $order->customer_currency);
		exchange_rate_display($order->customer_currency, get_company_currency(),
			($editable ? $_POST['OrderDate'] : $order->document_date));
	}
	table_section(3);

		hidden('cash', 0);
	if ($order->trans_type == ST_SALESINVOICE && $order->pos != -1) {
		//sale_payment_list_cells(_('Payment:'), 'cash', null, true);
		// payment_terms_list_cells('Payment Terms: ','pterms')
		$cash_payment = get_post('cash', 0);
		// current user can issue both credit and cash invoices
		if ($order->cash != $cash_payment) {
			$order->cash = $cash_payment;
			if ($cash_payment) {
				$paym = get_sales_point(user_pos());
				$order->cash_account = $paym['pos_account'];
				$order->account_name = $paym['bank_account_name'];
				$_POST['Location'] = $order->Location = $paym['pos_location'];
				$order->location_name = $paym['location_name'];
			}
			$Ajax->activate('items_table');
			$Ajax->activate('delivery');
			set_focus($order->pos == -1 ? 'delivery_date' : 'account');
		}
	} else
		hidden('cash', $order->cash);

	if($editable) {
		$str = sales_types_list_row2(_("Price List"), 'sales_type', null, true,'',true);
	} else {
		label_row(_("Price List:"), $order->sales_type_name);
	}
	if ($order->sales_type != $_POST['sales_type']) {
		$myrow = get_sales_type($_POST['sales_type']);
		$order->set_sales_type($myrow['id'], $myrow['sales_type'],
			$myrow['tax_included'], $myrow['factor']);
		$Ajax->activate('sales_type');
		$change_prices = 1;
	}
	$a = get_customer_details($order->customer_id);
	$d = db_fetch($a);
	// label_row(_('Payment Terms:'),$a['terms']);
	//display_error($order->cash);
	if (!$order->cash)
		payment_terms_list_row('Payment Terms: ', 'p_terms', null, '');
	// label_row(_("Customer Discount:"), ($order->default_discount * 100) . "%");
	//display_error('salesman' . $_POST['salesman']);
	if($_POST['salesman'] == '-1'){
		$sql = "SELECT salesman 
				FROM ".TB_PREF."cust_branch 
				WHERE branch_code = ".db_escape($order->Branch);
		$result = db_query($sql,"could not retrieve salesman");
		$myrow = db_fetch_row($result);	
		$salesman = $myrow[0];
	}else{
		$salesman = $_POST['salesman'];
	}
	sales_persons_list_row2( _("Sales Person:"), 'salesman', $salesman, '');		
	
	table_section(4);

	if ($editable)
	{
		if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "")
			$_POST['OrderDate'] = $order->document_date;

		date_row($date_text, 'OrderDate', _('Date of order receive'),
			$order->trans_no==0, 0, 0, 0, null, true);
		if (isset($_POST['_OrderDate_changed'])) {
			if (!is_company_currency($order->customer_currency) 
				&& (get_base_sales_type()>0)) {
					$change_prices = 1;
			}
			$Ajax->activate('_ex_rate');
			if ($order->trans_type == ST_SALESINVOICE) {
				$_POST['delivery_date'] = get_invoice_duedate(get_post('customer_id'), get_post('OrderDate'));
			} else 
				$_POST['delivery_date'] = add_days(get_post('OrderDate'), $SysPrefs->default_delivery_required_by());
			$Ajax->activate('items_table');
			$Ajax->activate('delivery_date');
		}
		if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE)
		{	// 2008-11-12 Joe Hunt added dimensions
			$dim = get_company_pref('use_dimension');
			if ($dim > 0)
				dimensions_list_row(_("Dimension").":", 'dimension_id', 
					null, true, ' ', false, 1, false);
			else
				hidden('dimension_id', 0);
			if ($dim > 1)
				dimensions_list_row(_("Dimension")." 2:", 'dimension2_id', 
					null, true, ' ', false, 2, false);
			else
				hidden('dimension2_id', 0);
		}	
	}
	else
	{
		label_row($date_text, $order->document_date);
		hidden('OrderDate', $order->document_date);
	}

	if ($display_tax_group)
	{
	    label_row(_("Tax Group:"), $order->tax_group_name);
	    hidden('tax_group_id', $order->tax_group_id);
	}
	
	label_row(_("Customer Discount:"), ($order->default_discount * 100) . "%");
	end_outer_table(1); // outer table

	if ($change_prices != 0) {
		foreach ($order->line_items as $line_no=>$item) {
			$line = &$order->line_items[$line_no];
			$line->price = get_kit_price($line->stock_id, $order->customer_currency,
				$order->sales_type, $order->price_factor, get_post('OrderDate'));
		//		$line->discount_percent = $order->default_discount;
		}
	    $Ajax->activate('items_table');
	}

	return $customer_error;
}

//--------------------------------------------------------------------------------

function getAllItems(){
	$stock_ids = '';
	$sql = "SELECT stock_id FROM `0_stock_master`";
	$query = db_query($sql, "could not fetch item codes");
	
	if(db_num_rows($query) > 0){
		$counter = 1;
		$total = db_num_rows($query) - 1;
		while($res = mysql_fetch_object($query)){
			$stock_ids .= "'$res->stock_id'";
			
			if($total != 0){
				$stock_ids .= ", ";
				$total--;
			}
		}
	}else{
		$stock_ids = "'No Items Found'";
	}
	
	return $stock_ids;
}

function getAllItemDesc(){
	$item_desc = '';
	$sql = "SELECT description FROM `0_stock_master`";
	$query = db_query($sql, "could not fetch item codes");
	
	if(db_num_rows($query) > 0){
		$counter = 1;
		$total = db_num_rows($query) - 1;
		while($res = mysql_fetch_object($query)){
			$item_desc .= "'$res->description'";
			
			if($total != 0){
				$item_desc .= ", ";
				$total--;
			}
		}
	}else{
		$item_desc = "'No Items Found'";
	}
	
	return $item_desc;
}

function get_default_disc($stock_id)
{
	//$disc_col = "disc".$disc;
	
	$sql = "SELECT disc1, disc2, disc3, disc4, disc5, disc6
			FROM ".TB_PREF."stock_category 
			JOIN ".TB_PREF."stock_master ON ".TB_PREF."stock_category.category_id = ".TB_PREF."stock_master.category_id
			WHERE stock_id=".db_escape($stock_id);

	$result = db_query($sql, "could not get customer");

	return db_fetch_row($result);
}

function sales_order_item_controls(&$order, &$rowcounter, $line_no=-1)
{

    global $Ajax;

	alt_table_row_color($rowcounter);

	$item_codes = getAllItems();
	$item_desc = getAllItemDesc();
	
	echo "
		<script>
			$(document).ready(function(){
				
			});
		</script>
	";
	
	$id = find_submit('Edit');
	if ($line_no!=-1 && $line_no == $id) // edit old line
	{//echo "fdgasdagfasd";    
		$_POST['stock_id'] = $order->line_items[$id]->stock_id;
		$dec = get_qty_dec($_POST['stock_id']);
		$_POST['qty'] = number_format2($order->line_items[$id]->qty_dispatched, $dec);
		$_POST['price'] = price_format($order->line_items[$id]->price);
		$_POST['Disc'] = percent_format($order->line_items[$id]->discount_percent*100);
		// $_POST['Disc2'] = percent_format($order->line_items[$id]->discount_percent2*100);
		// $_POST['Disc3'] = percent_format($order->line_items[$id]->discount_percent3*100);
		// $_POST['Disc4'] = percent_format($order->line_items[$id]->discount_percent4*100);
		// $_POST['Disc5'] = percent_format($order->line_items[$id]->discount_percent5*100);
		// $_POST['Disc6'] = percent_format($order->line_items[$id]->discount_percent6*100);
		$_POST['item_description'] = $order->line_items[$id]->item_description;
		$_POST['comment'] = $order->line_items[$id]->comment;
		$units = $order->line_items[$id]->units;
		hidden('stock_id', $_POST['stock_id']);
		label_cell($_POST['stock_id']);
		text_cells(null,'item_description', null, 45, 150);
		//label_cell($order->line_items[$line_no]->item_description, "nowrap");
		
	    $Ajax->activate('items_table');
	}
	else	// prepare new line
	{
		//echo "<input type=";
		sales_items_list_cells(null,'stock_id', null, '', true);
		if (list_updated('stock_id')) {
			    $Ajax->activate('price');
			    $Ajax->activate('units');
			    //$Ajax->activate('qty');
			    $Ajax->activate('line_total');
			    $Ajax->activate('Disc');
			    // $Ajax->activate('Disc2');
			    // $Ajax->activate('Disc3');
			    // $Ajax->activate('Disc4');
			    // $Ajax->activate('Disc5');
			    // $Ajax->activate('Disc6');
		}

		$item_info = get_item_edit_info($_POST['stock_id']);
		$units = $item_info["units"];
		$dec = $item_info['decimals'];
		$_POST['qty'] = number_format2(1, $dec);
		
		$sql = "SELECT price 
				FROM ".TB_PREF."prices_per_customer 
				WHERE debtor_no = ".db_escape($order->customer_id)."
				AND stock_id = ".db_escape($_POST['stock_id'])."
				AND sales_type_id = ".db_escape($order->sales_type)."
				AND curr_abrev = ".db_escape($order->customer_currency);
		$result = db_query($sql,"could not retrieve price for ".get_customer_name($order->customer_id));
		$myrow = db_fetch_row($result);	
		$price = $myrow[0];
		
		
		if(db_num_rows($result) <= 0)
		{
			$price = get_kit_price($_POST['stock_id'],
				$order->customer_currency, $order->sales_type,
				$order->price_factor, get_post('OrderDate'));
		}
			
		$_POST['price'] = price_format($price);
		// default to the customer's discount %
		$_POST['Disc'] = percent_format($order->default_discount * 100);
		
		$row = get_default_disc($_POST['stock_id']);
		// $_POST['Disc'] = $row[0];
		// $_POST['Disc2'] = $row[1];
		// $_POST['Disc3'] = $row[2];
		// $_POST['Disc4'] = $row[3];
		// $_POST['Disc5'] = $row[4];
		// $_POST['Disc6'] = $row[5];
		
		$_POST['comment'] = '';
	}
$keyups="onkeyup=\"if(event.keyCode==13) {try{this.value=get_amount(this.name);document.getElementById('AddItem').click();}catch(h){document.forms[0].UpdateItem.click();}}\"";

	qty_cells(null, 'qty', $_POST['qty'], null, null, $dec,$keyups);

	if ($order->trans_no!=0) {
		qty_cell($line_no==-1 ? 0 :$order->line_items[$line_no]->qty_done, false, $dec);
	}

	label_cell($units, '', 'units');
	$str = amount_cells(null, 'price',null,null,null,null,'amount',$keyups);

	small_amount_cells(null, 'Disc', percent_format($_POST['Disc']), null, null, user_percent_dec(),'amount',$keyups);
	// small_amount_cells(null, 'Disc2', percent_format($_POST['Disc2']), null, null, user_percent_dec(),'amount',$keyups);
	// small_amount_cells(null, 'Disc3', percent_format($_POST['Disc3']), null, null, user_percent_dec(),'amount',$keyups);
	// small_amount_cells(null, 'Disc4', percent_format($_POST['Disc4']), null, null, user_percent_dec(),'amount',$keyups);
	// small_amount_cells(null, 'Disc5', percent_format($_POST['Disc5']), null, null, user_percent_dec(),'amount',$keyups);
	// small_amount_cells(null, 'Disc6', percent_format($_POST['Disc6']), null, null, user_percent_dec(),'amount',$keyups);
	
	hidden('Disc2', 0);
	hidden('Disc3', 0);
	hidden('Disc4', 0);
	hidden('Disc5', 0);
	hidden('Disc6', 0);

	text_cells(null, 'comment', $_POST['comment'], 20, 150,false,'','',$keyups);

	$line_total = input_num('qty') * input_num('price') * (1 - input_num('Disc') / 100) * (1 - input_num('Disc2') / 100) * (1 - input_num('Disc3') / 100)
			 * (1 - input_num('Disc4') / 100) * (1 - input_num('Disc5') / 100) * (1 - input_num('Disc6') / 100);

	amount_cell($line_total, false, '','line_total');

	if ($id!=-1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
		hidden('LineNo', $line_no);
		set_focus('qty');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new item to document'), true);
	}

	end_row();
}

//--------------------------------------------------------------------------------

function display_delivery_details(&$order)
{
	global $table_style2, $Ajax;

	div_start('delivery');	

	if (get_post('cash', 0)) {	// Direct payment sale
		$Ajax->activate('items_table');
		display_heading(_('Cash payment'));
		start_table("$table_style2 width=60%");
		label_row(_("Deliver from Location:"), $order->location_name);

		hidden('Location', $order->Location);
		label_row(_("Cash account:"), $order->account_name);
		textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);
		end_table();
	} else {

		if ($order->trans_type==ST_SALESINVOICE)
		{
			$title = _("Delivery Details");
			$delname = _("Due Date").':';
		}
		elseif ($order->trans_type==ST_CUSTDELIVERY)
		{
			$title = _("Invoice Delivery Details");
			$delname = _("Invoice before").':';
		}
		elseif ($order->trans_type==ST_SALESQUOTE)
		{
			$title = _("Quotation Delivery Details");
			$delname = _("Valid until").':';
		}
		else
		{
			$title = _("Order Delivery Details");
			$delname = _("Required Delivery Date").':';
		}
		display_heading($title);
		start_outer_table("$table_style2 width=90%");
		table_section(1);

		locations_list_row(_("Deliver from Location:"), 'Location', null, 
			false, true);
		if (list_updated('Location'))
			$Ajax->activate('items_table');


		date_row($delname, 'delivery_date',
			$order->trans_type==ST_SALESORDER ?  _('Enter requested day of delivery') : $order->trans_type==ST_SALESQUOTE ? _('Enter Valid until Date') : '');
		text_row(_("Deliver To:"), 'deliver_to', $order->deliver_to, 40, 40,
			_('Additional identifier for delivery e.g. name of receiving person'));

		textarea_row(_("Address:"), 'delivery_address', $order->delivery_address, 35, 5,
			_('Delivery address. Default is address of customer branch'));

		table_section(2);

		text_row(_("Contact Phone Number:"), 'phone', $order->phone, 25, 25,
		    _('Phone number of ordering person. Defaults to branch phone number'));
		text_row(_("Customer Reference:"), 'cust_ref', $order->cust_ref, 25, 25,
		  _('Customer reference number for this order (if any)'));
		textarea_row(_("Comments:"), "Comments", $order->Comments, 31, 5);

		shippers_list_row(_("Shipping Company:"), 'ship_via', $order->ship_via);

		end_outer_table(1);
	}
	div_end();
}

function statusCombo($_POST){
	echo "
		<td>Status:</td>
		<td>
			<select name='stats' id='stats'>
				";
					if($_POST['stats'] == 0){
						echo "<option value=0 selected>All</option>";
					}else{
						echo "<option value=0 selected>All</option>";
					}
					
					if($_POST['stats'] == 1){
						echo "<option value=1 selected>Pending</option>";
					}else{
						echo "<option value=1>Pending</option>";
					}
					
					if($_POST['stats'] == 2){
						echo "<option value=2 selected>Delivered</option>";
					}else{
						echo "<option value=2>Delivered</option>";
					}
					
					if($_POST['stats'] == 3){
						echo "<option value=3 selected>Invoiced</option>";
					}else{
						echo "<option value=3>Invoiced</option>";
					}
					
					if($_POST['stats'] == 4){
						echo "<option value=4 selected>Voided</option>";
					}else{
						echo "<option value=4>Voided</option>";
					}
			echo "
			</select>
		</td>
	";
}

function getDRNofromSO($so){
	$sql = "SELECT trans_no
				FROM 0_debtor_trans
				WHERE type=".ST_CUSTDELIVERY."
				AND order_ = $so";
	$query = db_query($sql);
	
	if(db_num_rows($query) > 0){
		$row = mysql_fetch_object($query);
		return $row->trans_no;
	}else{
		return 0;
	}
}

function checkIfVoided($id, $type){
	$sql = "SELECT memo_
				FROM 0_voided
				WHERE type = $type
				AND id = $id";
	$query = db_query($sql);
	return db_num_rows($query);
}

function getSINofromDR($dr){
	$sql = "SELECT trans_link
				FROM 0_debtor_trans
				WHERE type=".ST_CUSTDELIVERY."
				AND trans_no = $dr";
	$query = db_query($sql);
	
	if(db_num_rows($query) > 0){
		$row = mysql_fetch_object($query);
		return $row->trans_link;
	}else{
		return 0;
	}
}

?>