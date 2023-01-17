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

print_rr();

//----------------------------------------------------------------------------------------------------
function get_rr($order_no)
{
   	$sql = "SELECT ".TB_PREF."grn_batch.*, ".TB_PREF."suppliers.supp_name,  ".TB_PREF."suppliers.supp_account_no,
   		".TB_PREF."suppliers.curr_code, ".TB_PREF."suppliers.payment_terms, ".TB_PREF."locations.location_name,
   		".TB_PREF."suppliers.email, ".TB_PREF."suppliers.address, ".TB_PREF."suppliers.contact
		FROM ".TB_PREF."grn_batch, ".TB_PREF."suppliers, ".TB_PREF."locations
		WHERE ".TB_PREF."grn_batch.supplier_id = ".TB_PREF."suppliers.supplier_id
		AND ".TB_PREF."locations.loc_code = ".TB_PREF."grn_batch.loc_code
		AND ".TB_PREF."grn_batch.id = ".db_escape($order_no);
   	$result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
}

function get_rr_details($order_no)
{
	$sql = "SELECT ".TB_PREF."grn_items.*, units, unit_price
		FROM ".TB_PREF."grn_items
		LEFT JOIN ".TB_PREF."stock_master
		ON ".TB_PREF."grn_items.item_code=".TB_PREF."stock_master.stock_id
		LEFT JOIN ".TB_PREF."purch_order_details
		ON ".TB_PREF."purch_order_details.po_detail_item=".TB_PREF."grn_items.po_detail_item
		WHERE ".TB_PREF."grn_items.grn_batch_id =".db_escape($order_no)." ";
	$sql .= " ORDER BY ".TB_PREF."grn_items.po_detail_item";
	return db_query($sql, "Retreive order Line Items");
}

function print_rr()
{
	global $path_to_root;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$email = $_POST['PARAM_2'];
	$comments = $_POST['PARAM_3'];

	if ($from == null)
		$from = 0;
	if ($to == null)
		$to = 0;
	$dec = user_price_dec();

	$cols = array(-15, 50, 385, 470);

	// $headers in doctext.inc
	$aligns = array('left', 'left', 'left', 'left', 'left');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		$rep = new FrontReport(_('Receiving Report'), "ReceivingReportBulk", user_pagesize());
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
	}

	for ($i = $from; $i <= $to; $i++)
	{		
		$myrow = get_rr($i);
		$baccount = get_default_bank_account($myrow['curr_code']);
		$params['bankaccount'] = $baccount['id'];
				
		//$rep->AddImage($path_to_root . "/printouts/rr_form.jpg", 0, 288, 576, 504);
		
		$rep->fontSize = 7;
		$rep->TextWrap(5, 783, 300, 'Print Date : '.date('m-d-Y h:i a'));
		
		$rep->fontSize = 14;
		$rep->TextWrap(492, 646, 300, $myrow['reference']);
		$rep->fontSize = 9;
		$rep->TextWrap(463, 698, 80, sql2date($myrow['delivery_date']));
		$rep->TextWrap(100, 646, 300, $myrow['supp_name']);
		$rep->TextWrap(78, 627, 300, $myrow['address']);
		$rep->row = 575;
		
		$result = get_rr_details($i);
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
					$myrow2['qty_recd'] = round2($myrow2['qty_recd'] / $data['conversion_factor'], user_qty_dec());
				}
			}	
			$Net = round2(($myrow2["unit_price"] * $myrow2["qty_recd"]), user_price_dec());
			$SubTotal += $Net;
			$dec2 = 0;
			$DisplayPrice = price_decimal_format($myrow2["unit_price"],$dec2);
			$DisplayQty = number_format2($myrow2["qty_recd"],get_qty_dec($myrow2['item_code']));
			$DisplayNet = number_format2($Net,$dec);
			$rep->TextCol(0, 1,	$DisplayQty." ".$myrow2['units'], -2);
			$rep->TextCol(1, 2, $myrow2['item_code']." - ".$myrow2['description'], -2);
			$rep->TextCol(2, 3, $DisplayPrice, -2);
			$rep->TextCol(3, 4, $DisplayNet, -2);
			$rep->NewLine(1);
			
			// if($i < $to)
			// {
				// $rep->NewPage();
				// $rep->AddImage($path_to_root . "/printouts/rr_form.jpg", 0, 288, 576, 504);
				// $rep->TextWrap(480, 625, 300, $myrow['id']);
				// $rep->TextWrap(138, 627, 300, $myrow['supp_name']);
				// $rep->TextWrap(115, 607, 300, $myrow['address']);
				// $rep->TextWrap(463, 674, 80, sql2date($myrow['delivery_date']));
				// $rep->row = 555;
			// }
		}
		
		//assign names
		$u = get_user($_SESSION["wa_current_user"]->user);
		$user_real_name = $u['real_name'];
		$rep->TextWrap(40, 350, 300, $user_real_name);
		//Approving officer should be link on the parent transaction.
		$__po = getPurchDetail($myrow['purch_order_no']);
		$ao = get_user_by_login($__po['approving_officer']);
		$app_user_real_name = $ao['real_name'];
		$rep->TextWrap(420, 350, 300, $app_user_real_name);
		//$rep->TextWrap(420, 350, 300, $myrow['purch_order_no']);
		
		if($i < $to)
			$rep->NewPage();
		
		
	}
	if ($email == 0)	
		$rep->End();
}

?>