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
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/db/inventory_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_order_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_credit_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_invoice_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_delivery_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_points_db.inc");
include_once($path_to_root . "/sales/includes/db/custalloc_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_db.inc");
include_once($path_to_root . "/sales/includes/db/cust_trans_details_db.inc");
include_once($path_to_root . "/sales/includes/db/payment_db.inc");
include_once($path_to_root . "/sales/includes/db/branches_db.inc");
include_once($path_to_root . "/sales/includes/db/customers_db.inc");

//----------------------------------------------------------------------------------------
// $price in customer's currency
// $quantity is used as is (if it's neg it's neg, if it's pos it's pos)
// $std_cost is in home currency
// $show_or_hide 1 show this item in invoice/credit views, 0 to hide it (used for write-off items)
// $type is 10 (invoice) or 11 (credit)


function add_stock_move_customer($type, $stock_id, $trans_id, $location, $date_, $reference,
	$quantity, $std_cost, $show_or_hide=1, $price=0, $discount_percent=0, $discount_percent2=0, 
	$discount_percent3=0, $discount_percent4=0, $discount_percent5=0, $discount_percent6=0)
{
	return add_stock_move($type, $stock_id, $trans_id, $location, $date_, $reference,
		$quantity, $std_cost, 0, $show_or_hide, $price, $discount_percent,
		"The customer stock movement record cannot be inserted", $discount_percent2, 
		$discount_percent3, $discount_percent4, $discount_percent5, $discount_percent6);
}

//----------------------------------------------------------------------------------------
// add a debtor-related gl transaction
// $date_ is display date (non-sql)
// $amount is in CUSTOMER'S currency

function add_gl_trans_customer($type, $type_no, $date_, $account, $dimension, $dimension2,
	$amount, $customer_id, $err_msg="", $rate=0, $memo='')
{
	if ($err_msg == "")
		$err_msg = "The customer GL transaction could not be inserted";

	return add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, $memo, $amount,
		get_customer_currency($customer_id),
		PT_CUSTOMER, $customer_id, $err_msg, $rate);
}

//----------------------------------------------------------------------------------------

function get_calculated_price($stock_id, $add_pct)
{
	$avg = get_standard_cost($stock_id);
	if ($avg == 0)
		return 0;
	return round2($avg * (1 + $add_pct / 100), user_price_dec());
}

function round_to_nearest($price, $round_to)
{
	if ($price == 0)
		return 0;
	$pow = pow(10, user_price_dec());
	if ($pow >= $round_to)
		$mod = ($pow % $round_to);
	else
		$mod = ($round_to % $pow);
	if ($mod != 0)
		$price = ceil($price) - ($pow - $round_to) / $pow;
	else	
    	$price = ceil($price * ($pow / $round_to)) / ($pow / $round_to);
    return $price;

}

function get_price ($stock_id, $currency, $sales_type_id, $factor=null, $date=null)
{
	if ($date == null)
	    $date = new_doc_date();

	if ($factor === null) 
	{
		$myrow = get_sales_type($sales_type_id);
		$factor = $myrow['factor'];
	}

	$add_pct = get_company_pref('add_pct');
	$base_id = get_base_sales_type();
    $home_curr = get_company_currency();
	//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
	$sql = "SELECT price, curr_abrev, sales_type_id
		FROM ".TB_PREF."prices
		WHERE stock_id = ".db_escape($stock_id)."
			AND (curr_abrev = ".db_escape($currency)." OR curr_abrev = ".db_escape($home_curr).")";

	$result = db_query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
	$num_rows = db_num_rows($result);
	$rate = round2(get_exchange_rate_from_home_currency($currency, $date),
	    user_exrate_dec());
	$round_to = get_company_pref('round_to');
	$prices = array();
	while($myrow = db_fetch($result)) 
	{
	    $prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
	}
	$price = false;
	if (isset($prices[$sales_type_id][$currency])) 
	{
	    $price = $prices[$sales_type_id][$currency];
	}
	elseif (isset($prices[$base_id][$currency])) 
	{
	    $price = $prices[$base_id][$currency] * $factor;
	}
	elseif (isset($prices[$sales_type_id][$home_curr])) 
	{
	    $price = $prices[$sales_type_id][$home_curr] / $rate;
	}
	elseif (isset($prices[$base_id][$home_curr])) 
	{
	    $price = $prices[$base_id][$home_curr] * $factor / $rate;
	}
/*
	if (isset($prices[$sales_type_id][$home_curr])) 
	{
	    $price = $prices[$sales_type_id][$home_curr] / $rate;
	}
	elseif (isset($prices[$base_id][$currency])) 
	{
	    $price = $prices[$base_id][$currency] * $factor;
	}
	elseif (isset($prices[$base_id][$home_curr])) 
	{
	    $price = $prices[$base_id][$home_curr] * $factor / $rate;
	}
*/	
	elseif ($num_rows == 0 && $add_pct != -1)
	{
		$price = get_calculated_price($stock_id, $add_pct);
		if ($currency != $home_curr)
			$price /= $rate;
		if ($factor != 0)
			$price *= $factor;
	}	
	if ($price === false)
		return 0;
	elseif ($round_to != 1)	
		return round_to_nearest($price, $round_to);
	else
		return round2($price, user_price_dec());
}
//----------------------------------------------------------------------------------------
//
//	Get price for given item or kit.
//  When $std==true price is calculated as a sum of all included stock items,
//	otherwise all prices set for kits and items are accepted.
//
function get_kit_price($item_code, $currency, $sales_type_id, $factor=null, 
	$date=null, $std = false)
{
	$kit_price = 0.00;
	if (!$std) {
		$kit_price = get_price( $item_code, $currency, $sales_type_id, 
			$factor, $date);

		if ($kit_price !== false) {
			return $kit_price;
		}
	}	
	// no price for kit found, get total value of all items
	$kit = get_item_kit($item_code);
	
	while($item = db_fetch($kit)) {
		if ($item['item_code'] != $item['stock_id']) {
			// foreign/kit code
			$kit_price += $item['quantity'] * get_kit_price( $item['stock_id'], 
				$currency, $sales_type_id, $factor, $date, $std);

		} else {
			// stock item
			$kit_price += $item['quantity'] * get_price( $item['stock_id'], 
				$currency, $sales_type_id, $factor, $date);
		}
	}
	return $kit_price;
}

//-----------------------------------------------------------------------------

function set_document_parent($cart)
{
	$inv_no = key($cart->trans_no);

	if (count($cart->src_docs) == 1) {

		// if this child document has only one parent - update child link
		$src = array_keys($cart->src_docs);
		$del_no = reset($src);

		$sql = 'UPDATE '.TB_PREF.'debtor_trans SET trans_link = ' . $del_no .
			' WHERE type='.db_escape($cart->trans_type).' AND trans_no='. $inv_no ;
		db_query($sql, 'Child document link cannot be updated');

	}
	if ($cart->trans_type != ST_SALESINVOICE)
		return 0;

	// the rest is batch invoice specific

	foreach ($cart->line_items as $line) {
		if ($line->quantity != $line->qty_dispatched) {
			return 1;	// this is partial invoice
		}
	}

	$sql = 'UPDATE '.TB_PREF.'debtor_trans SET trans_link = ' . $inv_no .
	' WHERE type='.get_parent_type($cart->trans_type).' AND (';

	$deliveries = array_keys($cart->src_docs);

	foreach ($deliveries as $key=>$del)
		$deliveries[$key] = 'trans_no='.$del;

	$sql .= implode(' OR ', $deliveries) . ')';
	db_query($sql, 'Delivery links cannot be updated');

	return 0; // batch or complete invoice
}

//--------------------------------------------------------------------------------------------------
function get_parent_type($type)
{
	$parent_types = array( ST_CUSTCREDIT => ST_SALESINVOICE, ST_SALESINVOICE => ST_CUSTDELIVERY, ST_CUSTDELIVERY => ST_SALESORDER );
	return isset($parent_types[$type]) ?  $parent_types[$type] : 0;
}

//--------------------------------------------------------------------------------------------------
function update_parent_line($doc_type, $line_id, $qty_dispatched)
{
	$doc_type = get_parent_type($doc_type);

//	echo "update line: $line_id, $doc_type, $qty_dispatched";
	if ($doc_type==0)
		return false;
	else {
		if ($doc_type==ST_SALESORDER)
			$sql = "UPDATE ".TB_PREF."sales_order_details
				SET qty_sent = qty_sent + $qty_dispatched
				WHERE id=".db_escape($line_id);
		else
			$sql = "UPDATE ".TB_PREF."debtor_trans_details
				SET qty_done = qty_done + $qty_dispatched
				WHERE id=".db_escape($line_id);
	}
	db_query($sql, "The parent document detail record could not be updated");
	return true;
}

//--------------------------------------------------------------------------------------------------
// find inventory location for given transaction
//
function get_location(&$cart)
{
	$sql = "SELECT ".TB_PREF."locations.* FROM ".TB_PREF."stock_moves,"
		.TB_PREF."locations".
		" WHERE type=".db_escape($cart->trans_type).
		" AND trans_no=".key($cart->trans_no).
		" AND qty!=0 ".
		" AND ".TB_PREF."locations.loc_code=".TB_PREF."stock_moves.loc_code";
	$result = db_query($sql, 'Retreiving inventory location');


	if (db_num_rows($result)) {
		return db_fetch($result);
	}
	return null;
}
//--------------------------------------------------------------------------------------------------
// Generic read debtor transaction into cart
//
//	$trans_no - array of trans nums; special case trans_no==0 - new doc
//
function read_sales_trans($doc_type, $trans_no, &$cart)
{
	if (!is_array($trans_no) && $trans_no)
			$trans_no = array($trans_no);

	$cart->trans_type = $doc_type;
	if (!$trans_no) { // new document
		$cart->trans_no = $trans_no;
	} else {
		// read header data from first document
		$myrow = get_customer_trans($trans_no[0],$doc_type);
		if (count($trans_no)>1)
			$cart->trans_no = get_customer_trans_version($doc_type, $trans_no);
		else
			$cart->trans_no = array($trans_no[0]=>$myrow["version"]);

		$cart->set_sales_type($myrow["tpe"], $myrow["sales_type"], $myrow["tax_included"],0);

		$cart->set_customer($myrow["debtor_no"], $myrow["DebtorName"],
			$myrow["curr_code"], $myrow["discount"], 0, get_order_payment_terms($myrow['order_'],false));

		$cart->set_branch($myrow["branch_code"], $myrow["tax_group_id"],
			$myrow["tax_group_name"],	$myrow["phone"], $myrow["email"]);

		$cart->reference = $myrow["reference"];
		$cart->order_no = $myrow["order_"];
		$cart->trans_link = $myrow["trans_link"];
		$cart->due_date = sql2date($myrow["due_date"]);
		$cart->document_date = sql2date($myrow["tran_date"]);
		$cart->dimension_id = $myrow['dimension_id']; // added 2.1 Joe Hunt 2008-11-12
		$cart->dimension2_id = $myrow['dimension2_id'];
		$cart->salesman = $myrow['salesman'];
				
		$cart->Comments = '';
		foreach ( $trans_no as $trans ) {
			$cart->Comments .= get_comments_string($doc_type,$trans);
		}

		// FIX this should be calculated sum() for multiply parents

		$cart->set_delivery($myrow["ship_via"], $myrow["br_name"],
		$myrow["br_address"], $myrow["ov_freight"]);

		$location = 0;
		$myrow = get_location($cart); // find location from movement

		if($myrow!=null) {
			$cart->set_location($myrow['loc_code'], $myrow['location_name']);
		}

		$result = get_customer_trans_details($doc_type,$trans_no);
		if (db_num_rows($result) > 0) {
			for($line_no=0; $myrow = db_fetch($result); $line_no++)	{
				$cart->line_items[$line_no] = new line_details(
					$myrow["stock_id"],$myrow["quantity"],
					$myrow["unit_price"], $myrow["discount_percent"], 
					$myrow["discount_percent2"], $myrow["discount_percent3"], 
					$myrow["discount_percent4"], $myrow["discount_percent5"], 
					$myrow["discount_percent6"], $myrow["comment"],
					$myrow["qty_done"], $myrow["standard_cost"],
					$myrow["StockDescription"],$myrow["id"], $myrow["debtor_trans_no"]);
			}
		}
	} // !newdoc

	return true;
}
//----------------------------------------------------------------------------------------

function get_me($id, &$comments, &$counter){
	$sql = "SELECT comments	
				FROM 0_sales_orders
				WHERE order_no = $id";
	$query = db_query($sql);
	$row = mysql_fetch_object($query);
	$comments .= $row->comments;
	
	if($counter > 0){
		$counter--;
		$comments .= ' ';
	}
}

function get_Comment($query, &$comments){
	
	$counter = (db_num_rows($query) - 1);
	while($res = mysql_fetch_object($query)){
		get_me($res->order_, $comments, $counter);
	}

	/*$sql = "SELECT comments
				FROM 0_sales_orders
				WHERE order_no = $id";
				//die($sql);
	$query = db_query($sql);
	if(db_num_rows($query) > 0){
		$res = mysql_fetch_object($query);
		return $res->comments;
	}else{
		return '';
	}
	return 'Hello!';*/
}

function getAllComments($trans){
	$sql = "SELECT reference
				FROM 0_debtor_trans
				WHERE trans_no = $trans
				AND type = 10";
	$query = db_query($sql);
	$res = mysql_fetch_object($query);
	
	$ref = $res->reference;
	
	$sql2 = "SELECT order_
				FROM 0_debtor_trans
				WHERE reference = $ref
				AND type = 13";
	$query2 = db_query($sql2);
	
	$comments = '';
	
	$comments .= get_Comment($query2, $comments);
	
	return $comments;
}

function getSODetails($sorder){
	$sql = "SELECT * FROM ".TB_PREF."sales_orders
				WHERE order_no = $sorder
				AND trans_type = 30";
	$result = db_query($sql);
	return db_fetch($result);
}


//=============================================== special functions
function confirm_trans_remittance($remittance_id)
{
	$sql = "SELECT * FROM `0_finishedpayments`
				WHERE remittance_id = 1
				AND Description IN ('Debit Card','Credit Card')
				AND checked_by = 0; ";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
	{
		$sql = "UPDATE ".TB_PREF."remittance SET trans_checked = 1
					WHERE remittance_id = $remittance_id";
		db_query($sql,'failed to update remittance trans checker');
	}
}



//------------ACQUIRING BANK QUERIES
function get_all_acquiring_banks($all)
{
    $sql = "SELECT * FROM ".TB_PREF."acquiring_banks";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY acquiring_bank";
    return  db_query($sql, "could not get acquiring banks");
}

function get_selected_acquiring_banks($id)
{
    $sql = "SELECT * FROM ".TB_PREF."acquiring_banks";
	$sql .= " WHERE id='$id'";
	$sql .= " AND inactive='0' ORDER BY acquiring_bank";
	//display_error($sql);
	$res=db_query($sql, "could not get acquiring banks");
	$row=db_fetch($res);
	return $row['acquiring_bank'];
}


function edit_display($selected_id)
{
    $sql = "SELECT * FROM ".TB_PREF."acquiring_banks WHERE id=".$selected_id."";
    return  db_query($sql, "could not display bank info");
}


function get_acquiring_bank_col($id, $col='')
{
	$sql = "SELECT * FROM ".TB_PREF."acquiring_banks WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if ($col == '')
		return $row;
	else
		return $row[$col];
}



//------------NON CASH QUERIES
function get_all_noncash($all,$date_from,$trans_type,$cashier_id2)
{
 $sql = "select ot.id, ot.remittance_id, ot.trans_no, ot.account_no,
ot.tender_type,ot.approval_no,ot.trans_amount,ot.card_desc, ot.verified ,r.remittance_date,r.cashier_id, r.cashier_name, tt.Description
from ".CR_DB.TB_PREF."other_trans as ot
left join ".CR_DB.TB_PREF."remittance as r
on ot.remittance_id=r.remittance_id
left join ".CR_DB.TB_PREF."tendertypes as tt
on ot.tender_type=tt.tendercode
where (remittance_date='$date_from')";
	if (!$all) $sql .= " AND !verified"; //change to not verified to hide.
	if ($trans_type!='') $sql .= " AND ot.tender_type='$trans_type'";
	if ($cashier_id2!='') $sql .= " AND r.cashier_id='$cashier_id2'";
	$sql .= " ORDER BY tender_type desc";
	//display_error($sql);
return  db_query_rs($sql, "could not get non-cash transaction");
}


function edit_noncash($selected_id)
{
    $sql = "SELECT * FROM ".CR_DB.TB_PREF."other_trans as ot
	left join ".CR_DB.TB_PREF."remittance as r
	on ot.remittance_id=r.remittance_id
	WHERE ot.id=".$selected_id."";
	//display_error($sql);
    return  db_query_rs($sql);
	
}



function tender_list($name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{
	global $all_items;

	$sql = "SELECT * FROM ".TB_PREF."tendertypes";

return combo_input($name, $selected_id, $sql, 'TenderCode', 'Description',
	array(
		'spec_option'=>$new_item ? _("All Tender") : false,
		'spec_id' => '',
		'select_submit'=> $submit_on_change,
		'show_inactive' => $show_inactive
	) );
}


function tender_list_cells($label, $name, $selected_id=null, $new_item=true, $submit_on_change=false,
	$show_inactive = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo tender_list($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
	echo "</td>\n";
}


function tender_list_row($label, $name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{
	echo "<tr><td class='label'>$label</td>";
	tender_list_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
	echo "</tr>\n";
}



//REMITTANCE GL Class

function sales_gl_class_name_list($name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{
	global $all_items;

	$sql = "SELECT * FROM ".TB_PREF."sales_gl_class";

return combo_input($name, $selected_id, $sql, 'id', 'sales_gl_type_name',
	array(
		'spec_option'=>$new_item ? _("New role") : false,
		'spec_id' => '',
		'select_submit'=> $submit_on_change,
		'show_inactive' => $show_inactive
	) );
}


function sales_gl_class_cells($label, $name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{
	if ($label != null)
		echo "<td>$label</td>\n";
	echo "<td>";
	echo sales_gl_class_name_list($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
	echo "</td>\n";
}

function sales_gl_class_row($label, $name, $selected_id=null, $new_item=false, $submit_on_change=false,
	$show_inactive = false)
{
	echo "<tr><td class='label'>$label</td>";
	sales_gl_class_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
	echo "</tr>\n";
}


function get_all_sales_gl_class($all)
{
    $sql = "SELECT * FROM ".TB_PREF."sales_gl_class";
	if (!$all) $sql .= " WHERE !inactive";
	$sql .= " ORDER BY id";
    return  db_query($sql, "could not get GL Class");
}

function remittance_gl_class_edit_display($selected_id)
{
    $sql = "SELECT * FROM ".TB_PREF."sales_gl_class WHERE id=".$selected_id."";
    return  db_query($sql, "could not remittance type info");
}


function get_remittance_glclass_col($id, $col='')
{
	$sql = "SELECT * FROM ".TB_PREF."sales_gl_class WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if ($col == '')
		return $row;
	else
		return $row[$col];
}
//


//SALES GL ACCOUNTS
function get_sales_gl_account_prefs($tbpref = TB_PREF)
{
	$sql = "SELECT * FROM ".$tbpref."sales_gl_accounts WHERE s_gl_id=1";
	
	$result = db_query($sql, "The sales_gl_accounts preferences could not be retrieved");

	if (db_num_rows($result) == 0)
		display_db_error("FATAL : Could not find sales_gl_accounts prefs", $sql);

	return db_fetch($result);
}


function get_depreciation_expenses($all,$account)
{
    $sql = "SELECT * FROM ".TB_PREF."dep_exp_fixed_assets";
	if (!$all) $sql.= " WHERE !inactive";
	if ($all) $sql.= " WHERE inactive='1' or inactive='0'";
	
	if ($account!='')
	{
	$acc=$account;
	$sql.=" and asset_gl_type='".$acc."'";
	}
	$sql.= " ORDER BY asset_name";
	
 //display_error($sql);
 return  db_query($sql, "could not get table dep_exp_fixed_assets");
}


function update_sales_gl_account_setup(
$sales_account,
$cash_account,
$cash_tender,
$gc_account,
$gc_tender,
$suki_account,
$suki_tender,
$debit_account,
$debit_tender,
$credit_account,
$credit_tender,
$check_account,
$check_tender,
$terms_account,
$terms_tender,
$evoucher_account,
$evoucher_tender,
$receivable_account,
$atd,
$stock_transfer,
$shortage,
$overage,
$cash_in_bank,
$check_in_transit
)
{
$sql = "UPDATE ".TB_PREF."sales_gl_accounts SET	
sales_account=".db_escape($sales_account).",
cash_account=".db_escape($cash_account).",
cash_tender=".db_escape($cash_tender).",
gc_account=".db_escape($gc_account).",
gc_tender=".db_escape($gc_tender).",
suki_account=".db_escape($suki_account).",
suki_tender=".db_escape($suki_tender).",
debit_account=".db_escape($debit_account).",
debit_tender=".db_escape($debit_tender).",
credit_account=".db_escape($credit_account).",
credit_tender=".db_escape($credit_tender).",
check_account=".db_escape($check_account).",
check_tender=".db_escape($check_tender).",
terms_account=".db_escape($terms_account).",
terms_tender=".db_escape($terms_tender).",
evoucher_account=".db_escape($evoucher_account).",
evoucher_tender=".db_escape($evoucher_tender).",
receivable_account=".db_escape($receivable_account).",
atd=".db_escape($atd).",
stock_transfer=".db_escape($stock_transfer).",
shortage=".db_escape($shortage).",
overage=".db_escape($overage).",
cash_in_bank=".db_escape($cash_in_bank).",
check_in_transit=".db_escape($check_in_transit)."
		WHERE s_gl_id=1";

	db_query($sql, "The Sales GL Account Setup could not be updated ");
}

?>