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

$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_SUPPTRANSVIEW' : 'SA_SUPPBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Purchase Orders
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

//----------------------------------------------------------------------------------------------------

print_po();

//----------------------------------------------------------------------------------------------------
function get_terms($id)
{
	$sql = "SELECT terms FROM ".TB_PREF."payment_terms WHERE terms_indicator=".db_escape($id);
	$result = db_query($sql,"could not get paymentterms");
	$row = db_fetch($result);
	return $row["terms"];
}

function get_po($order_no)
{
   	$sql = "SELECT ".TB_PREF."purch_orders.*, ".TB_PREF."suppliers.supp_name,  ".TB_PREF."suppliers.supp_account_no,
   		".TB_PREF."suppliers.curr_code, ".TB_PREF."suppliers.payment_terms, ".TB_PREF."locations.location_name,
   		".TB_PREF."suppliers.email, ".TB_PREF."suppliers.address, ".TB_PREF."suppliers.contact
		FROM ".TB_PREF."purch_orders, ".TB_PREF."suppliers, ".TB_PREF."locations
		WHERE ".TB_PREF."purch_orders.supplier_id = ".TB_PREF."suppliers.supplier_id
		AND ".TB_PREF."locations.loc_code = into_stock_location
		AND ".TB_PREF."purch_orders.order_no = ".db_escape($order_no);
   	$result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
}

function get_po_details($order_no)
{
	$sql = "SELECT ".TB_PREF."purch_order_details.*, units
		FROM ".TB_PREF."purch_order_details
		LEFT JOIN ".TB_PREF."stock_master
		ON ".TB_PREF."purch_order_details.item_code=".TB_PREF."stock_master.stock_id
		WHERE order_no =".db_escape($order_no)." ";
	$sql .= " ORDER BY po_detail_item";
	return db_query($sql, "Retreive order Line Items");
}

function print_po()
{
	global $path_to_root;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];
	$no_price = $_POST['PARAM_5'];

	if ($from == null)
		$from = 0;
	if ($to == null)
		$to = 0;
	$dec = user_price_dec();
	
	$cols = array(-5, 30, 70, 395, 450,510);

	// $headers in doctext.inc
	$aligns = array('left', 'left', 'left', 'right', 'right', 'right');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		$rep = new FrontReport(_('PURCHASE ORDER'), "PurchaseOrderBulk", user_pagesize());
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
	}

	for ($i = $from; $i <= $to; $i++)
	{		
		$myrow = get_po($i);
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];
				
		//$rep->AddImage($path_to_root . "/printouts/po_form.jpg", 0, 288, 576, 504);
		
		$rep->fontSize = 7;
		$rep->TextWrap(5, 783, 300, 'Print Date : '.date('m-d-Y h:i a'));
		
		$rep->fontSize = 14;
		$rep->TextWrap(495, 720, 300, $myrow['reference']);
		$rep->fontSize = 9;
		$rep->TextWrap(425, 694, 80, sql2date($myrow['ord_date']));
		$rep->TextWrap(45, 694, 300, $myrow['supp_name']);
		$rep->TextWrap(45, 674, 300, $myrow['address']);
		$rep->row = 615;
		
		$result = get_po_details($i);
		$SubTotal = 0;
		while ($myrow2=db_fetch($result))
		{
			$data = get_purchase_data($myrow['supplier_id'], $myrow2['item_code']);
			if ($data !== false)
			{
				if ($data['supplier_description'] != "")
					$myrow2['description'] = $data['supplier_description'];
				if ($data['suppliers_uom'] != "")
					$myrow2['units'] = $data['suppliers_uom'];
				if ($data['conversion_factor'] != 1)
				{
					$myrow2['unit_price'] = round2($myrow2['unit_price'] * $data['conversion_factor'], user_price_dec());
					$myrow2['quantity_ordered'] = round2($myrow2['quantity_ordered'] / $data['conversion_factor'], user_qty_dec());
				}
			}	
			$Net = round2(($myrow2["extended"]), user_price_dec());
			$SubTotal += $Net;
			$dec2 = 0;
			$DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
			$DisplayQty = number_format2($myrow2["quantity_ordered"],get_qty_dec($myrow2['item_code']));
			$DisplayNet = number_format2($Net,$dec);
			$rep->TextCol(0, 1,	$DisplayQty, -2);
			$rep->TextCol(1, 2, $myrow2['units'], -2);
			$rep->TextCol(2, 3, $myrow2['description'], -2);
			
			$discounts = array();
	
			if ($myrow2['disc_percent1'] != 0)
				$discounts[] = $myrow2['disc_percent1'].'%';
			if ($myrow2['disc_percent2'] != 0)
				$discounts[] = $myrow2['disc_percent2'].'%';
			if ($myrow2['disc_percent3'] != 0)
				$discounts[] = $myrow2['disc_percent3'].'%';
			if ($myrow2['disc_amount1'] != 0)
				$discounts[] = $myrow2['disc_amount1'];
			if ($myrow2['disc_amount2'] != 0)
				$discounts[] = $myrow2['disc_amount2'];
			if ($myrow2['disc_amount3'] != 0)
				$discounts[] = $myrow2['disc_amount3'];
			
			$discounts = implode(' ,', $discounts);
			
			if (!$no_price)
			{
				$rep->TextCol(3, 4, $DisplayPrice, -2);
				$rep->TextCol(4, 5, $discounts, -2);
				$rep->TextCol(5, 6, $DisplayNet, -2);
			}
			$rep->NewLine(1);
		}
		
		$s = get_supplier($myrow['supplier_id']);
		if (!$no_price)
		$rep->TextWrap(70, 400, 300, get_terms($s['payment_terms']));
		//$rep->TextWrap(70, 380, 300, $myrow['address']);
		
		$ao = get_user_by_login($myrow['approving_officer']);
		$rep->TextWrap(390, 370, 300, $ao['real_name']);
		
		if($i < $to)
			$rep->NewPage();
		
	}
	if ($email == 0)
		$rep->End();
}

?>