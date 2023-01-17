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
	'SA_SALESTRANSVIEW' : 'SA_SALESBULKREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Janusz Dobrwolski
// date_:	2008-01-14
// Title:	Print Delivery Notes
// draft version!
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$packing_slip = 0;
//----------------------------------------------------------------------------------------------------

print_deliveries();

//----------------------------------------------------------------------------------------------------

function print_deliveries()
{
	global $path_to_root, $packing_slip;

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$email = $_POST['PARAM_2'];
	$packing_slip = $_POST['PARAM_3'];
	$comments = $_POST['PARAM_4'];

	if ($from == null)
		$from = 0;
	if ($to == null)
		$to = 0;
	$dec = user_price_dec();

	$fno = explode("-", $from);
	$tno = explode("-", $to);

	// $cols = array(4, 60, 225, 300, 325, 385, 450, 515);

	// // $headers in doctext.inc
	// $aligns = array('left',	'left',	'right', 'left', 'right', 'right', 'right');
	
	$cols = array(-17, 25, 70, 425, 475);	

	// $headers in doctext.inc
	$aligns = array('left',	'left',	'left', 'left', 'left');

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		if ($packing_slip == 0)
			$rep = new FrontReport(_('DELIVERY'), "DeliveryNoteBulk", user_pagesize());
		else
			$rep = new FrontReport(_('PACKING SLIP'), "PackingSlipBulk", user_pagesize());
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
	}

	$first = true;
	for ($i = $fno[0]; $i <= $tno[0]; $i++)
	{
		if(!$first)
		{
			$rep->NewPage();
			$first = false;
		}
				
			if (!exists_customer_trans(ST_CUSTDELIVERY, $i))
				continue;
			$myrow = get_customer_trans($i, ST_CUSTDELIVERY);
			
			if ($myrow['reference'] == 'auto')
				continue;
			
			$branch = get_branch($myrow["branch_code"]);
			$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER); // ?
			
			//$rep->AddImage($path_to_root . "/printouts/dr_form.jpg", 0, 288, 576, 504);
			$rep->fontSize = 7;
			$rep->TextWrap(5, 783, 300, 'Print Date : '.date('m-d-Y h:i a'));
			
			$rep->fontSize = 14;
			$rep->TextWrap(508, 747, 300, $myrow['reference']);
			$rep->fontSize = 9;
			$rep->TextWrap(52, 717, 300, $myrow['DebtorName']);
			$rep->TextWrap(25, 696, 300, $myrow['br_address']);
			$rep->TextWrap(442, 696, 80, sql2date($myrow['tran_date']));
			$rep->row = 645;
			
			$result = get_customer_trans_details(ST_CUSTDELIVERY, $i);
			$SubTotal = 0;
			while ($myrow2=db_fetch($result))
			{
				if ($myrow2["quantity"] == 0)
					continue;
					
				$Net = round2(((1 - $myrow2["discount_percent"]) * (1 - $myrow2["discount_percent2"]) * (1 - $myrow2["discount_percent3"]) * (1 - $myrow2["discount_percent4"]) * (1 - $myrow2["discount_percent5"]) * (1 - $myrow2["discount_percent6"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		$DisplayQty = number_format2($myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
	    		
				$display_discount = 0;
				
				if ($myrow2["discount_percent"]==0){
					$DisplayDiscount =" 0";
				}else{
					$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "";
					$display_discount++;
				}
					
				if ($myrow2["discount_percent2"]==0){
					$DisplayDiscount .= " / 0";
				}else{
					$DisplayDiscount .= " / ".number_format2($myrow2["discount_percent2"]*100,user_percent_dec()) . "";
					$display_discount++;
				}
				
				if ($myrow2["discount_percent3"]==0){
					$DisplayDiscount .= " / 0";
				}else{
					$DisplayDiscount .= " / ".number_format2($myrow2["discount_percent3"]*100,user_percent_dec()) . "";
					$display_discount++;
				}
				
				$rep->TextCol(0, 1,	$DisplayQty, -2);
				$rep->TextCol(1, 2,	$myrow2['units'], -2);
				$oldrow = $rep->row;
				$rep->TextColLines(2, 3, $myrow2['stock_id']." - ".$myrow2['StockDescription'], -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				$rep->TextCol(3, 4,	$DisplayPrice, -2);
				$rep->TextCol(4, 5,	$DisplayNet, -2);
				$rep->row = $newrow - 2;	//17;
				$rep->Font();
			}
			
			$DisplaySubTot = number_format2($SubTotal,$dec);
   			$DisplayFreight = number_format2($myrow["ov_freight"],$dec);
			$DisplayTotal = number_format2($myrow["ov_freight"] +$myrow["ov_freight_tax"] + $myrow["ov_gst"] + $myrow["ov_amount"],$dec);
			
			$rep->row = 505;
			$rep->TextCol(4, 5,	$DisplayTotal, -2);
			
			$__so = getSODetails($myrow['order_']);
			$ao = get_user_by_login($__so['approving_officer']);
			$rep->TextWrap(27, 360, 300, $ao['real_name']);
			//$rep->TextWrap(430, 380, 80, sql2date($myrow['tran_date']));
		
		if($i < $tno[0])
			$rep->NewPage();
	}
	if ($email == 0)
		$rep->End();
}

?>