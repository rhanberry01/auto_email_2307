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
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Print Invoices
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

//----------------------------------------------------------------------------------------------------

print_invoices();

//----------------------------------------------------------------------------------------------------

function get_terms($id)
{
	$sql = "SELECT terms FROM ".TB_PREF."payment_terms WHERE terms_indicator=".db_escape($id);
	$result = db_query($sql,"could not get paymentterms");
	$row = db_fetch($result);
	return $row["terms"];
}

function y__($y)
{	
	return 792 - $y;
}
	
function print_invoices()
{
	global $path_to_root;
	
	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$currency = $_POST['PARAM_2'];
	$email = $_POST['PARAM_3'];
	$paylink = $_POST['PARAM_4'];
	$comments = $_POST['PARAM_5'];

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
	
	$cols = array(-20, 18, 55, 360, 425, 530);	

	// $headers in doctext.inc
	$aligns = array('right',  'center',	'left', 'right', 'right', 'left');	

	$params = array('comments' => $comments);

	$cur = get_company_Pref('curr_default');

	if ($email == 0)
	{
		$rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize());
		$rep->IncludeJS("print();");
		$rep->currency = $cur;
		$rep->Font();
		$rep->Info($params, $cols, null, $aligns);
		
		
	$rep->SetFont('ltype','',10);
	}

	$x_ = 0;
	$y_ = 0;
	
	for ($i = $fno[0]; $i <= $tno[0]; $i++)
	{
		if($rep->pageNumber > 1)
			$rep->newPage();
			
		for ($j = ST_SALESINVOICE; $j <= ST_CUSTCREDIT; $j++)
		{
			if (isset($_POST['PARAM_6']) && $_POST['PARAM_6'] != $j)
				continue;
			if (!exists_customer_trans($j, $i))
				continue;
			$sign = $j==ST_SALESINVOICE ? 1 : -1;
			$myrow = get_customer_trans($i, $j);
			$baccount = get_default_bank_account($myrow['curr_code']);
			$params['bankaccount'] = $baccount['id'];

			$branch = get_branch($myrow["branch_code"]);
			$branch['disable_branch'] = $paylink; // helper
			if ($j == ST_SALESINVOICE)
				$sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
			else
				$sales_order = null;
				
			// $rep->AddImage($path_to_root . "/printouts/inv_form.png", 0, $rep->pageHeight);
			
			$rep->fontSize = 7;
			$rep->TextWrap(5, 783, 300, 'Print Date : '.date('m-d-Y h:i a'));
			
			$rep->fontSize = 9;
			$rep->TextWrap(113+$x_, y__(160)+$y_, 409-113, $myrow['DebtorName']);
			$rep->TextWrap(477+$x_, y__(160)+$y_, 607-477, sql2date($myrow['tran_date']));
			
			$rep->TextWrap(113+$x_, y__(160)-15+$y_, 409-113, $myrow['address']);
			$rep->TextWrap(477+$x_, y__(160)-15+$y_, 607-477, get_salesman_name($myrow['tran_salesman']));
			
			$rep->TextWrap(294+$x_, y__(160)-15-15+$y_, 409-294, $myrow['tax_id']);
			$rep->TextWrap(477+$x_, y__(160)-15-15+$y_, 607-477, get_terms($myrow['payment_terms']));
			// $rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
			$rep->TextWrap(113+$x_, y__(160)-15-15-15+$y_, 409-113, $myrow['br_address']);
			$rep->row = 550;
			$y = y__(238)-12;
			
			// $rep->TextWrap(8, 280, 300, 'The amount is inclusive of 12% VAT, unless otherwise indicated.');
			
			$shipping = $myrow['ov_freight'];
			
			$result = get_customer_trans_details($j, $i);
			$SubTotal = 0;
			
			$_subtotal = 0;
			
			$line_counter = 0;
			
			$vatable_f = $nonvat_f = $zerorated_f = 0;
			while ($myrow2=db_fetch($result))
			{
				for($ty=0;$ty<=0;$ty++)
				{
				
					if ($myrow2["quantity"] == 0)
						continue;
						
					if ($y <= y__(488 + 12))
					{
						$rep->NewPage();
						$rep->fontSize = 9;
						$rep->TextWrap(113+$x_, y__(160)+$y_, 409-113, $myrow['DebtorName']);
						$rep->TextWrap(477+$x_, y__(160)+$y_, 607-477, sql2date($myrow['tran_date']));
						
						$rep->TextWrap(113+$x_, y__(160)-15+$y_, 409-113, $myrow['address']);
						$rep->TextWrap(477+$x_, y__(160)-15+$y_, 607-477, get_salesman_name($myrow['salesman']));
						
						$rep->TextWrap(294+$x_, y__(160)-15-15+$y_, 409-294, $myrow['tax_id']);
						$rep->TextWrap(477+$x_, y__(160)-15-15+$y_, 607-477, get_terms($myrow['payment_terms']));
						// $rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
						$rep->TextWrap(113+$x_, y__(160)-15-15-15+$y_, 409-113, $myrow['br_address']);
						$rep->row = 550;
						$y = y__(238)-12+$y_;
					}
						
					$Net = round2($sign * ( $myrow2["unit_price"] * $myrow2["quantity"]
							 // * (1 - $myrow2['discount_percent']) 
							 // * (1 - $myrow2['discount_percent2']) 
							 // * (1 - $myrow2['discount_percent3'])
							 )
							 , user_price_dec());
					
					$Net_with_dc = round2($sign * ( $myrow2["unit_price"] * $myrow2["quantity"]
							 * (1 - $myrow2['discount_percent']) 
							 * (1 - $myrow2['discount_percent2']) 
							 * (1 - $myrow2['discount_percent3'])
							 * (1 - $myrow2['discount_percent4'])
							 * (1 - $myrow2['discount_percent5'])
							 * (1 - $myrow2['discount_percent6'])
							 )
							 , user_price_dec());
					
					$SubTotal += $Net_with_dc;
					
					/////////////////////////////////////////////////////////////////
			
					$sql2 = "SELECT tax_type_id FROM 0_stock_master WHERE stock_id IN ( ".db_escape($myrow2["stock_id"])." )";
					$result2 = db_query($sql2,"could not retrieve tax_type_id");
					$row2 = db_fetch_row($result2);		

					if($row2[0] == 1)
						$vatable_f += $Net_with_dc;
					else if($row2[0] == 2)
						$nonvat_f += $Net_with_dc;
					else
						$zerorated_f += $Net_with_dc;

					/////////////////////////////////////////////////////////////////
					
					$dc_total = ($myrow2["unit_price"] * $myrow2["quantity"]) - $Net_with_dc;
					
					$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
					$DisplayPrice2 = number_format2($myrow2["unit_price"],$dec);
					
					$DisplayQty = number_format2($sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
					$DisplayNet = number_format2($Net,$dec);
					
					
					// $line_counter++;
					$rep->TextWrap(18+$x_, $y+$y_, 95-18, $DisplayQty,'center');
					$rep->TextWrap(97+$x_, $y+$y_, 130-97, $myrow2['units'],'center');
					
					$orig_y1 = $y;
					
					$over = $rep->TextWrap(132+$x_, $y+$y_, 411-132, $myrow2['stock_id']." - ".$myrow2['StockDescription'],'left',0,0,null,0);
				
					if ($over == '')
						$last_y1 = $y;
						
					while($over != '')
					{
						$y -= 12;
						$over = $rep->TextWrap(132+$x_, $y+$y_, 411-132, $over,'left',0,0,null,0);
						$last_y1 = $y;
					}
					
					
					$y = $orig_y1;
					
					$rep->TextWrap(413+$x_, $y+$y_, 502-413, $DisplayPrice,'right');
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, $DisplayNet ,'right');
					
					$disc_str = '';
					
					if ($myrow2['discount_percent'] != 0)
					{
						if ($disc_str == '')
							$disc_str .= "Discount : "; //. ($myrow2['discount_percent']*100) . "%";
						else
							$disc_str .= " | ". ($myrow2['discount_percent']*100) . "%";	
					}
					
					// if ($myrow2['discount_percent2'] != 0)
					// {
						// if ($disc_str == '')
							// $disc_str .= "Discount : ". ($myrow2['discount_percent2']*100) . "%";
						// else
							// $disc_str .= " | ". ($myrow2['discount_percent2']*100) . "%";	
					// }
					
					// if ($myrow2['discount_percent3'] != 0)
					// {
						// if ($disc_str == '')
							// $disc_str .= "Discount : ". ($myrow2['discount_percent3']*100) . "%";
						// else
							// $disc_str .= " | ". ($myrow2['discount_percent3']*100) . "%";	
					// }
					
					// if ($myrow2['discount_percent4'] != 0)
					// {
						// if ($disc_str == '')
							// $disc_str .= "Discount : ". ($myrow2['discount_percent4']*100) . "%";
						// else
							// $disc_str .= " | ". ($myrow2['discount_percent4']*100) . "%";	
					// }
					
					// if ($myrow2['discount_percent5'] != 0)
					// {
						// if ($disc_str == '')
							// $disc_str .= "Discount : ". ($myrow2['discount_percent5']*100) . "%";
						// else
							// $disc_str .= " | ". ($myrow2['discount_percent5']*100) . "%";	
					// }
					
					// if ($myrow2['discount_percent6'] != 0)
					// {
						// if ($disc_str == '')
							// $disc_str .= "Discount : ". ($myrow2['discount_percent6']*100) . "%";
						// else
							// $disc_str .= " | ". ($myrow2['discount_percent6']*100) . "%";	
					// }
		
					
					$y = $last_y1;
					if (trim($myrow2['comment']) != '')
					{
						$myrow2['comment'] = trim($myrow2['comment']);
						$y -= 12;
						$over = $rep->TextWrap(132+$x_, $y+$y_, 411-132, $myrow2['comment'],'left',0,0,null,0);
						
						while($over != '')
						{
							$y -= 12;
							$over = $rep->TextWrap(132+$x_, $y+$y_, 411-132, $over,'left',0,0,null,0);
						}
						// $y = $last_y1-12;
					}
					
					if (trim($disc_str) != '')		// DISCOUNT HERE
					{
						$myrow2['comment'] = trim($disc_str);
						$y -= 12;
						$over = $rep->TextWrap(132+$x_, $y+$y_, 411-132-10, $myrow2['comment'],'right',0,0,null,0);
						$rep->TextWrap(504+$x_, $y+$y_, 600-504, '-'.number_format2($dc_total,2),'right');
					}	
					
					$y -= 12;
				}
			}
			
			if ($y <= y__(488 + 12))
			{
				$rep->NewPage();
				$rep->fontSize = 9;
				$rep->TextWrap(113+$x_, y__(160)+$y_, 409-113, $myrow['DebtorName']);
				$rep->TextWrap(477+$x_, y__(160)+$y_, 607-477, sql2date($myrow['tran_date']));
				
				$rep->TextWrap(113+$x_, y__(160)-15+$y_, 409-113, $myrow['address']);
				$rep->TextWrap(477+$x_, y__(160)-15+$y_, 607-477, get_salesman_name($myrow['salesman']));
				
				$rep->TextWrap(294+$x_, y__(160)-15-15+$y_, 409-294, $myrow['tax_id']);
				$rep->TextWrap(477+$x_, y__(160)-15-15+$y_, 607-477, get_terms($myrow['payment_terms']));
				// $rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
				$rep->TextWrap(113+$x_, y__(160)-15-15-15+$y_, 409-113, $myrow['br_address']);
				$rep->row = 550;
				$y = y__(238)-12+$y_;
			}
			else
				$y += 12;
			
			$print_total = false;
			if($myrow["discount1"] != 0)
			{
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Subtotal', 'right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($SubTotal,2) ,'right');
				
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Less '.$myrow["discount1"]*100 .'% Discount','right');
				$rep->TextWrap(413+$x_, $y+$y_, 502-413, $myrow["discount1"]*100 .'%','right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, '- '.number_format2($SubTotal*$myrow["discount1"],2) ,'right');
				$SubTotal -= $SubTotal*$myrow["discount1"];
				$print_total = true;
			}

			if($myrow["discount2"] != 0)
			{
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Subtotal', 'right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($SubTotal,2) ,'right');
				
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Less '.$myrow["discount2"]*100 .'% Discount','right');
				$rep->TextWrap(413+$x_, $y+$y_, 502-413, $myrow["discount2"]*100 .'%','right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, '- '.number_format2($SubTotal*$myrow["discount2"],2) ,'right');
				$SubTotal -= $SubTotal*$myrow["discount2"];
				$print_total = true;
			}

			if($myrow["discount3"] != 0)
			{
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Subtotal', 'right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($SubTotal,2) ,'right');
				
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Less '.$myrow["discount3"]*100 .'% Discount','right');
				$rep->TextWrap(413+$x_, $y+$y_, 502-413, $myrow["discount3"]*100 .'%','right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, '- '.number_format2($SubTotal*$myrow["discount3"],2) ,'right');
				$SubTotal -= $SubTotal*$myrow["discount3"];
				$print_total = true;
			}

			if($myrow["discount4"] != 0)
			{
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Subtotal', 'right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($SubTotal,2) ,'right');
				
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Less '.$myrow["discount4"]*100 .'% Discount','right');
				$rep->TextWrap(413+$x_, $y+$y_, 502-413, $myrow["discount4"]*100 .'%','right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, '- '.number_format2($SubTotal*$myrow["discount4"],2) ,'right');
				$SubTotal -= $SubTotal*$myrow["discount4"];
				$print_total = true;
			}

			if($myrow["discount5"] != 0)
			{
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Subtotal', 'right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($SubTotal,2) ,'right');
				
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132-10, 'Less '.$myrow["discount5"]*100 .'% Discount','right');
				$rep->TextWrap(413+$x_, $y+$y_, 502-413, $myrow["discount5"]*100 .'%','right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, '- '.number_format2($SubTotal*$myrow["discount5"],2) ,'right');
				$SubTotal -= $SubTotal*$myrow["discount5"];
				$print_total = true;
			}
		
			if ($shipping > 0)
			{
				$y -= 12;
				$rep->TextWrap(132+$x_, $y+$y_, 411-132, 'Plus Shipping','left');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($shipping,2) ,'right');
				$SubTotal += $shipping;
				$print_total = true;
			}
			
			//=========== totals	
			$y = y__(488+2);
			
			if ($print_total)
			{
				$rep->TextWrap(132+$x_, $y+$y_, 411-132, 'Total', 'right');
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($SubTotal,2) ,'right');
				
				$rep->SetLineWidth(2);
				$rep->lineTo(504-20+$x_, $y+$y_+9, 600, $y+$y_+9);
				$rep->lineTo(504-20+$x_, $y+$y_-2, 600, $y+$y_-2);
				$rep->SetLineWidth(1);
		
			}
			
			$tax_items = get_trans_tax_details(ST_SALESINVOICE, $i);
			
			$vatable = $vat_exempt = $zero_rated = $total_sales = $vat = $grand_total = 0;
			
			while ($tax_item = db_fetch($tax_items))
			{
				$vat += $tax_item['amount'];
				$vatable += $tax_item['net_amount'];
			}
			
			$vatable = round2($vatable	* (1 - $myrow["discount1"]) * (1 - $myrow["discount2"]) * (1 - $myrow["discount3"])
					* (1 - $myrow["discount4"]) * (1 - $myrow["discount5"]),2);
			
			$vat = round2($vat	* (1 - $myrow["discount1"]) * (1 - $myrow["discount2"]) * (1 - $myrow["discount3"])
					* (1 - $myrow["discount4"]) * (1 - $myrow["discount5"]),2);
			
			$vat_exempt = $SubTotal - $shipping - $vatable - $vat;
			
			$total_sales = $vatable + $vat_exempt + $shipping + $vat;
			
			// $y -= 14;
			// if (round2($vatable,2) > 0)
				// $rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($vatable,2) ,'right');
			// $y -= 14;
			// if (round2($vat_exempt,2) > 0)
				// $rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($vat_exempt,2) ,'right');
			// $y -= 14;
			// if (round2($zero_rated,2) > 0)
				// $rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($zero_rated,2) ,'right');
			// $y -= 14;
			
			
			$y -= 14;
			if (round2($total_sales,2) > 0)
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($total_sales,2) ,'right');
			
			//////////////////////////////////////////////////////////////////////////////
			$sql1 = "SELECT tax_group_id FROM 0_cust_branch WHERE branch_code = ".db_escape($myrow["branch_code"]);
			$result1 = db_query($sql1,"could not retrieve tax_type_id");
			$row1 = db_fetch_row($result1);

			if($row1[0] == 1)
			{
				// $y -= 14;
				// if (round2($vatable_f,2) > 0)
					// $rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($vatable_f/1.12,2) ,'right');
				$y -= 14;
				if (round2($vatable_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($vatable_f/1.12,2) ,'right');
				$y -= 14;
				if (round2($nonvat_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($nonvat_f,2) ,'right');
				$y -= 14;
				
				if (round2($zerorated_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($zerorated_f,2) ,'right');
			}
			else if($row1[0] == 2)
			{
				// $y -= 14;
				// if (round2($vatable_f,2) > 0)
					// $rep->TextWrap(504+$x_, $y+$y_, 600-504, '' ,'right');
				$y -= 14;
				if (round2($vatable_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, '' ,'right');
				$y -= 14;
				if (round2($nonvat_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($vatable_f+$nonvat_f+$zerorated_f,2) ,'right');
				$y -= 14;
				
				if (round2($zerorated_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, '' ,'right');
			}
			else
			{
				// $y -= 14;
				// if (round2($vatable_f,2) > 0)
					// $rep->TextWrap(504+$x_, $y+$y_, 600-504, '' ,'right');
				$y -= 14;
				if (round2($vatable_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, '' ,'right');
				$y -= 14;
				if (round2($nonvat_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($nonvat_f,2) ,'right');
				$y -= 14;
				
				if (round2($zerorated_f,2) > 0)
					$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($vatable_f+$zerorated_f,2) ,'right');
			}
			//////////////////////////////////////////////////////////////////////////////
			
			// if (round2($total_sales,2) > 0)
				// $rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($total_sales,2) ,'right');
			$y -= 14;
			if (round2($vat,2) > 0)
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($vat,2) ,'right');
			$y -= 14;
			if (round2($SubTotal,2) > 0)
				$rep->TextWrap(504+$x_, $y+$y_, 600-504, number_format2($SubTotal,2) ,'right');
			
			// approving officer
			$y = y__(680);
			//$rep->TextWrap(432+$x_, $y+$y_, 607-412, get_approving_officer($myrow['order_']) ,'left');
			$rep->TextWrap(432+$x_, $y+$y_, 607, get_approving_officer($myrow['order_']) ,'left');
			
			$y -= 14*2;
			$rep->TextWrap(432+$x_, $y+$y_, 607-412, 'Prepared By:' ,'left');
			$y -= 14;
			$last_audit = get_audit_trail_last(ST_SALESINVOICE, $i);
			//$rep->TextWrap(432+$x_, $y+$y_, 607-412, get_username_by_id($last_audit['user']) ,'left');
			$rep->TextWrap(432+$x_, $y+$y_, 607-412, get_username_by_id($last_audit['user']) ,'left');
			//$rep->TextWrap(432+$x_, $y+$y_, 607-412, get_approving_officer($myrow['order_']) ,'left');
			
			$rep->TextWrap(10, 280, 300, 'The amount is inclusive of 12% VAT, unless otherwise indicated.');
			
			$coms = '';
			$comments = get_comments(ST_SALESINVOICE, $i);
			if ($comments and db_num_rows($comments))
			{
				while ($comment = db_fetch($comments))
				{
					$coms .=  $comment["memo_"] . ". ";
				}
			}
			$rep->row = 280-14;
			$rep->TextWrapLines(30, 300, $coms);
			
		}
		
		if($i < $tno[0])
			$rep->NewPage();
		
	}
	
	if ($email == 0)
		$rep->End();
}

?>