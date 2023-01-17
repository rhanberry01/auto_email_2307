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

	$order_no = $_GET['order_no'];
	$dec = user_price_dec();

	$cols = array(5, 80, 170, 380);

	// $headers in doctext.inc
	$aligns = array('left',	'right', 'right');

	$params = array('comments' => "");

	$cur = get_company_Pref('curr_default');
	
	$rep = new FrontReport(_('PURCHASE ORDER'), "PurchaseOrderBulk", user_pagesize());
	$rep->currency = $cur;
	$rep->Font();
	$rep->Info($params, $cols, null, $aligns);
	
	$myrow = get_po($order_no);
	$baccount = get_default_bank_account($myrow['curr_code']);
	$params['bankaccount'] = $baccount['id'];
			
	//$rep->AddImage($path_to_root . "/printouts/requisition.jpg", 0, 288, 576, 504);
	
	$rep->fontSize = 14;
	$rep->TextWrap(510, 725, 300, $myrow['order_no']);
	$rep->fontSize = 9;
	$rep->TextWrap(15, 702, 300, $myrow['supp_name']);
	$rep->TextWrap(415, 684, 80, sql2date($myrow['ord_date']));
	$rep->TextWrap(15, 684, 300, $myrow['address']);
	$rep->row = 623;
	
	$result = get_po_details($order_no);
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
		$Net = round2(($myrow2["unit_price"] * $myrow2["quantity_ordered"]), user_price_dec());
		$SubTotal += $Net;
		$dec2 = 0;
		$DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
		$DisplayQty = number_format2($myrow2["quantity_ordered"],get_qty_dec($myrow2['item_code']));
		$DisplayNet = number_format2($Net,$dec);
		$rep->TextCol(0, 1,	$myrow2['item_code']);
		$rep->TextCol(1, 2,	$myrow2['description'], -2);
		$rep->TextCol(2, 3,	$DisplayQty, -2);
		$rep->NewLine(1);
		
	}
		
	$rep->End();
}
?>