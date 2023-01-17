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
	}


	for ($i = $fno[0]; $i <= $tno[0]; $i++)
	{
		if($rep->pageNumber > 1)
			$rep->newPage();
			
		for ($j = ST_SALESINVOICE; $j <= ST_CUSTCREDIT; $j++)
		{
			// if (isset($_POST['PARAM_6']) && $_POST['PARAM_6'] != $j)
				// continue;
			// if (!exists_customer_trans($j, $i))
				// continue;
			// $sign = $j==ST_SALESINVOICE ? 1 : -1;
			// $myrow = get_customer_trans($i, $j);
			// $baccount = get_default_bank_account($myrow['curr_code']);
			// $params['bankaccount'] = $baccount['id'];

			// $branch = get_branch($myrow["branch_code"]);
			// $branch['disable_branch'] = $paylink; // helper
			// if ($j == ST_SALESINVOICE)
				// $sales_order = get_sales_order_header($myrow["order_"], ST_SALESORDER);
			// else
				// $sales_order = null;
			// if ($email == 1)
			// {
				// $rep = new FrontReport("", "", user_pagesize());
				// $rep->currency = $cur;
				// $rep->Font();
				// if ($j == ST_SALESINVOICE)
				// {
					// $rep->title = _('INVOICE');
					// $rep->filename = "Invoice" . $myrow['reference'] . ".pdf";
				// }
				// else
				// {
					// $rep->title = _('CREDIT NOTE');
					// $rep->filename = "CreditNote" . $myrow['reference'] . ".pdf";
				// }
				// $rep->Info($params, $cols, null, $aligns);
			// }
			// else
				// $rep->title = ($j == ST_SALESINVOICE) ? _('INVOICE') : _('CREDIT NOTE');
			// $rep->Header2($myrow, $branch, $sales_order, $baccount, $j);

   			// $result = get_customer_trans_details($j, $i);
			// $SubTotal = 0;
			// while ($myrow2=db_fetch($result))
			// {
				// if ($myrow2["quantity"] == 0)
					// continue;
					
				// $Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   // user_price_dec());
				// $SubTotal += $Net;
	    		// $DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		// $DisplayQty = number_format2($sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		// $DisplayNet = number_format2($Net,$dec);
	    		
				// $display_discount = 0;
			
				// if ($myrow2["discount_percent"]==0){
					// $DisplayDiscount =" 0";
				// }else{
					// $DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "";
					// $display_discount++;
				// }
					
				// if ($myrow2["discount_percent2"]==0){
					// $DisplayDiscount .= " / 0";
				// }else{
					// $DisplayDiscount .= " / ".number_format2($myrow2["discount_percent2"]*100,user_percent_dec()) . "";
					// $display_discount++;
				// }
				
				// if ($myrow2["discount_percent3"]==0){
					// $DisplayDiscount .= " / 0";
				// }else{
					// $DisplayDiscount .= " / ".number_format2($myrow2["discount_percent3"]*100,user_percent_dec()) . "";
					// $display_discount++;
				// }
				
				// $rep->TextCol(0, 1,	$myrow2['stock_id'], -2);
				// $oldrow = $rep->row;
				// $rep->TextColLines(1, 2, $myrow2['StockDescription'], -2);
				// $newrow = $rep->row;
				// $rep->row = $oldrow;
				// $rep->TextCol(2, 3,	$DisplayQty, -2);
				// $rep->TextCol(3, 4,	$myrow2['units'], -2);
				// $rep->TextCol(4, 5,	$DisplayPrice, -2);
				
				// if($display_discount == 0){
			
				// }else{
					// $rep->fontSize -= 2;
					// $rep->TextCol(5, 6,	$DisplayDiscount, -2);
					// $rep->fontSize += 2;
				// }
				
				// //$rep->TextCol(5, 6,	$myrow2["discount_percent"], -2);
				
				// $rep->TextCol(6, 7,	$DisplayNet, -2);
				// $rep->row = $newrow;
				// //$rep->NewLine(1);
				
				// $rep->TextColLines(1, 2, $myrow2['comment'], -2);
				
				// if ($rep->row < $rep->bottomMargin + (15 * $rep->lineHeight))
					// $rep->Header2($myrow, $branch, $sales_order, $baccount,$j);
			// }

			// $comments = get_comments($j, $i);
			// if ($comments && db_num_rows($comments))
			// {
				// $rep->NewLine();
    			// while ($comment=db_fetch($comments))
    				// $rep->TextColLines(0, 6, $comment['memo_'], -2);
			// }

   			// $DisplaySubTot = number_format2($SubTotal,$dec);
   			// $DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);

    		// $rep->row = $rep->bottomMargin + (15 * $rep->lineHeight);
			// $linetype = true;
			// $doctype = $j;
			// if ($rep->currency != $myrow['curr_code'])
			// {
				// include($path_to_root . "/reporting/includes/doctext2.inc");
			// }
			// else
			// {
				// include($path_to_root . "/reporting/includes/doctext.inc");
			// }

			// $rep->TextCol(3, 6, $doc_Sub_total, -2);
			// $rep->TextCol(6, 7,	$DisplaySubTot, -2);
			// $rep->NewLine();
			// $rep->TextCol(3, 6, $doc_Shipping, -2);
			// $rep->TextCol(6, 7,	$DisplayFreight, -2);
			// $rep->NewLine();
			// $tax_items = get_trans_tax_details($j, $i);
    		// while ($tax_item = db_fetch($tax_items))
    		// {
    			// $DisplayTax = number_format2($sign*$tax_item['amount'], $dec);

    			// if ($tax_item['included_in_price'])
    			// {
					// $rep->TextCol(3, 7, $doc_Included . " " . $tax_item['tax_type_name'] .
						// " (" . $tax_item['rate'] . "%) " . $doc_Amount . ": " . $DisplayTax, -2);
				// }
    			// else
    			// {
					// $rep->TextCol(3, 6, $tax_item['tax_type_name'] . " (" .
						// $tax_item['rate'] . "%)", -2);
					// $rep->TextCol(6, 7,	$DisplayTax, -2);
				// }
				// $rep->NewLine();
    		// }
    		// $rep->NewLine();
			// $DisplayTotal = number_format2($sign*($myrow["ov_freight"] + $myrow["ov_gst"] +
				// $myrow["ov_amount"]+$myrow["ov_freight_tax"]),$dec);
			// $rep->Font('bold');
			// $rep->TextCol(3, 6, $doc_TOTAL_INVOICE, - 2);
			// $rep->TextCol(4, 7, $DisplayTotal, -2);
			// $words = price_in_words($myrow['Total'], $j);
			// if ($words != "")
			// {
				// $rep->NewLine(1);
				// $rep->TextCol(1, 7, $myrow['curr_code'] . ": " . $words, - 2);
			// }	
			// $rep->Font();
			// if ($email == 1)
			// {
				// $myrow['dimension_id'] = $paylink; // helper for pmt link
				// if ($myrow['email'] == '')
				// {
					// $myrow['email'] = $branch['email'];
					// $myrow['DebtorName'] = $branch['br_name'];
				// }
				// $rep->End($email, $doc_Invoice_no . " " . $myrow['reference'], $myrow, $j);
			// }
			
			
			
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
				
			//$rep->AddImage($path_to_root . "/printouts/inv_form.jpg", 0, 58, 574, 735);
			
			$rep->fontSize = 9;
			$rep->TextWrap(75, 646, 300, $myrow['DebtorName']);
			$rep->TextWrap(430, 646, 80, sql2date($myrow['tran_date']));
			$rep->TextWrap(75, 630, 300, $myrow['address']);
			$rep->TextWrap(430, 630, 300, get_salesman_name($myrow['salesman']));
			$rep->TextWrap(275, 616, 300, $myrow['tax_id']);
			$rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
			$rep->TextWrap(75, 602, 300, $myrow['br_address']);
			$rep->row = 550;
			
			$rep->TextWrap(8, 280, 300, 'The amount is inclusive of 12% VAT, unless otherwise indicated.');
			
			$shipping = $myrow['ov_freight'];
			
			$result = get_customer_trans_details($j, $i);
			$SubTotal = 0;
			
			$_subtotal = 0;
			
			$line_counter = 0;
			
			while ($myrow2=db_fetch($result))
			{

				if ($myrow2["quantity"] == 0)
					continue;
					
				if($line_counter == 18){
					$rep->NewPage();
					$cols = array(-20, 20, 55, 360, 445);	

					// $headers in doctext.inc
					$aligns = array('left',  'left',	'left', 'right', 'right');	

					//$params = array('comments' => $comments);

					//$cur = get_company_Pref('curr_default');
				
					/* $rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize());
					$rep->currency = $cur;
					$rep->Font();
					$rep->Info($params, $cols, null, $aligns);
					$rep->fontSize = 9; */
					$rep->TextWrap(75, 646, 300, $myrow['DebtorName']);
					$rep->TextWrap(430, 646, 80, sql2date($myrow['tran_date']));
					$rep->TextWrap(75, 630, 300, $myrow['address']);
					$rep->TextWrap(430, 630, 300, get_salesman_name($myrow['salesman']));
					$rep->TextWrap(275, 616, 300, $myrow['tax_id']);
					$rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
					$rep->TextWrap(75, 602, 300, $myrow['br_address']);
					$rep->row = 550;
					
					$rep->TextWrap(0, 280, 300, 'The amount is inclusive of 12% VAT, unless otherwise indicated.');
					$line_counter = 0;
				}
					
				$Net = round2($sign * ((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]),
				   user_price_dec());
				$SubTotal += $Net;
	    		$DisplayPrice = number_format2($myrow2["unit_price"],$dec);
	    		$DisplayQty = number_format2($sign*$myrow2["quantity"],get_qty_dec($myrow2['stock_id']));
	    		$DisplayNet = number_format2($Net,$dec);
	    		
				$display_discount = 0;
				$DisplayDiscount = '';
			
				if ($myrow2["discount_percent"]==0){
					if($myrow2["discount_percent2"] == 0 || $myrow2["discount_percent3"] == 0){
					}else{
						$DisplayDiscount =" 0%";
					}
				}else{
					$DisplayDiscount = number_format2($myrow2["discount_percent"]*100,user_percent_dec()) . "%";
					$display_discount++;
				}
					
				if ($myrow2["discount_percent2"]==0){
					if($myrow2["discount_percent3"]!=0 || $myrow2["discount_percent"]!=0){
						$DisplayDiscount .= "  /  0%";
					}
				}else{
					$DisplayDiscount .= "  /  ".number_format2($myrow2["discount_percent2"]*100,user_percent_dec()) . "%";
					$display_discount++;
				}
				
				if ($myrow2["discount_percent3"]==0){
					if($myrow2["discount_percent2"]!=0 || $myrow2["discount_percent"]!=0){
						$DisplayDiscount .= "  /  0%";
					}
				}else{
					$DisplayDiscount .= "  /  ".number_format2($myrow2["discount_percent3"]*100,user_percent_dec()) . "%";
					$display_discount++;
				}
				
				$line_counter++;
				$rep->TextCol(0, 1,	$DisplayQty, -2);
				$rep->TextCol(1, 2,	$myrow2['units'], -2);
				$oldrow = $rep->row;
				$rep->TextColLinesWrap(2, 3, $myrow2['stock_id']." - ".$myrow2['StockDescription'].' - '.$line_counter, -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				$rep->TextCol(3, 4,	$DisplayPrice, -2);
				$rep->TextCol(4, 5,	$DisplayNet, -2);
				$rep->row = $newrow;
				//$rep->TextColLines(2, 3, $myrow2['comment'], -2);
				$rep->Font();
				
				$displayme = '';
				
				if($myrow2['comment'] != ''){
					$displayme = $myrow2['comment'];
					if($DisplayDiscount != ''){
						$displayme .= '   Discount: '.$DisplayDiscount;
					}
				}else{
					if($DisplayDiscount != ''){
						$displayme = 'Discount: '.$DisplayDiscount;
					}else{
						$displayme = '';
					}
				}				
				
				if($displayme != ''){
					$line_counter++;
					$rep->TextColLines(2, 3, $displayme);
					$newrow = $rep->row;
					$rep->row = $oldrow;
					$rep->row = $newrow;
					$rep->Font();
				}
				
				$_subtotal += $Net;
			}
			
			if($shipping > 0){
				if($line_counter == 18){
					$rep->NewPage();
					$cols = array(-20, 20, 55, 360, 445);	

					// $headers in doctext.inc
					$aligns = array('left',  'left',	'left', 'right', 'right');	

					//$params = array('comments' => $comments);

					//$cur = get_company_Pref('curr_default');
				
					/* $rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize());
					$rep->currency = $cur;
					$rep->Font();
					$rep->Info($params, $cols, null, $aligns);
					$rep->fontSize = 9; */
					$rep->TextWrap(75, 646, 300, $myrow['DebtorName']);
					$rep->TextWrap(430, 646, 80, sql2date($myrow['tran_date']));
					$rep->TextWrap(75, 630, 300, $myrow['address']);
					$rep->TextWrap(430, 630, 300, get_salesman_name($myrow['salesman']));
					$rep->TextWrap(275, 616, 300, $myrow['tax_id']);
					$rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
					$rep->TextWrap(75, 602, 300, $myrow['br_address']);
					$rep->row = 550;
					
					$rep->TextWrap(0, 280, 300, 'The amount is inclusive of 12% VAT, unless otherwise indicated.');
					$line_counter = 0;
				}
			
				$line_counter++;
				$shipfee = number_format2($shipping,$dec);
				$oldrow = $rep->row;
				$rep->TextColLinesWrap(2, 3, 'Shipping Fee', -2);
				$newrow = $rep->row;
				$rep->row = $oldrow;
				$rep->TextCol(4, 5, $shipfee, -2);
				$rep->row = $newrow;
				$rep->Font();
				
				$_subtotal += $shipping;
			}
			
			
			
			$line_counter++;
			$subt = number_format2($_subtotal,$dec);
			$oldrow = $rep->row;
			$rep->TextColLinesWrap(2, 3, 'Subtotal', -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			$rep->TextCol(4, 5, $subt, -2);
			$rep->row = $newrow;
			$rep->Font();
			
			$_subt = $_subtotal;
			
			$counter = 0;
			for($y=1 ; $y<=5 ; $y++){
				if($myrow['discount'.$y] != 0){
					$counter++;
				}
			}
			
			$counter--;
			
			if($line_counter == 18){
				$rep->NewPage();
				$cols = array(-20, 20, 55, 360, 445);	

				// $headers in doctext.inc
				$aligns = array('left',  'left',	'left', 'right', 'right');	

				//$params = array('comments' => $comments);

				//$cur = get_company_Pref('curr_default');
			
				/* $rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize());
				$rep->currency = $cur;
				$rep->Font();
				$rep->Info($params, $cols, null, $aligns);
				$rep->fontSize = 9; */
				$rep->TextWrap(75, 646, 300, $myrow['DebtorName']);
				$rep->TextWrap(430, 646, 80, sql2date($myrow['tran_date']));
				$rep->TextWrap(75, 630, 300, $myrow['address']);
				$rep->TextWrap(430, 630, 300, get_salesman_name($myrow['salesman']));
				$rep->TextWrap(275, 616, 300, $myrow['tax_id']);
				$rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
				$rep->TextWrap(75, 602, 300, $myrow['br_address']);
				$rep->row = 550;
				
				$rep->TextWrap(0, 280, 300, 'The amount is inclusive of 12% VAT, unless otherwise indicated.');
				$line_counter = 0;
			}
			
			for($x=1 ; $x<=5 ; $x++){
				if($myrow['discount'.$x] != 0){					
					$line_counter++;
					
					if($line_counter == 18){
						$rep->NewPage();
						$cols = array(-20, 20, 55, 360, 445);	

						// $headers in doctext.inc
						$aligns = array('left',  'left',	'left', 'right', 'right');	

						//$params = array('comments' => $comments);

						//$cur = get_company_Pref('curr_default');
					
						/* $rep = new FrontReport(_('INVOICE'), "InvoiceBulk", user_pagesize());
						$rep->currency = $cur;
						$rep->Font();
						$rep->Info($params, $cols, null, $aligns);
						$rep->fontSize = 9; */
						$rep->TextWrap(75, 646, 300, $myrow['DebtorName']);
						$rep->TextWrap(430, 646, 80, sql2date($myrow['tran_date']));
						$rep->TextWrap(75, 630, 300, $myrow['address']);
						$rep->TextWrap(430, 630, 300, get_salesman_name($myrow['salesman']));
						$rep->TextWrap(275, 616, 300, $myrow['tax_id']);
						$rep->TextWrap(430, 616, 300, get_terms($myrow['payment_terms']));
						$rep->TextWrap(75, 602, 300, $myrow['br_address']);
						$rep->row = 550;
						
						$rep->TextWrap(0, 280, 300, 'The amount is inclusive of 12% VAT, unless otherwise indicated.');
						$line_counter = 0;
					}
					
					$disp_discount = $myrow['discount'.$x] * 100;
					$discCode = 'Disc'.$disp_discount;
					$discCode .= ' - Less '.$disp_discount.'% Discount';
					
					$subtract_this = $_subt * $myrow['discount'.$x];
					$_subt = $_subt - $subtract_this;
					$disp_subthis = number_format2($subtract_this,$dec);
					
					
					$oldrow = $rep->row;
					$rep->TextColLinesWrap(2, 3, $discCode, -2);
					$newrow = $rep->row;
					$rep->row = $oldrow;
					$rep->TextCol(3, 4, '-'.$disp_discount.'%', -2);
					$rep->TextCol(4, 5, '-'.$disp_subthis, -2);
					$rep->row = $newrow;
					//$rep->TextColLines(2, 3, $myrow2['comment'], -2);
					$rep->Font();
					
					if($counter > 0){
						$line_counter++;
						$__subt = number_format2($_subt,$dec);
						$oldrow = $rep->row;
						$rep->TextColLinesWrap(2, 3, 'Subtotal', -2);
						$newrow = $rep->row;
						$rep->row = $oldrow;
						$rep->TextCol(4, 5, $__subt, -2);
						$rep->row = $newrow;
						$rep->Font();
						$counter--;
					}
				}
			}
			
			$line_counter++;
			$oldrow = $rep->row;
			$rep->TextColLinesWrap(2, 3, '', -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			$rep->row = $newrow;
			
			$memo = getAllComments($i);
			
			$line_counter++;
			$oldrow = $rep->row;
			$rep->TextColLinesWrap(2, 3, $memo, -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			$rep->row = $newrow;
			$rep->Font();
			
			$line_counter++;
			$oldrow = $rep->row;
			$rep->TextColLinesWrap(2, 3, '', -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			$rep->row = $newrow;
			
			$line_counter++;
			$oldrow = $rep->row;
			$rep->TextColLinesWrap(2, 3, '', -2);
			$newrow = $rep->row;
			$rep->row = $oldrow;
			$rep->row = $newrow;
			
			$line_counter++;
			$__subt = number_format2($_subt,$dec);
			$oldrow = $rep->row;
			$newrow = $rep->row;
			$rep->row = $oldrow;
			$rep->TextCol(3, 4, 'Total', -2);
			$rep->TextCol(4, 5, $__subt, -2, 0, 'TB');
			$rep->row = $newrow;
			$rep->Font();
			
			$DisplaySubTot = number_format2($SubTotal,$dec);
   			$DisplayFreight = number_format2($sign*$myrow["ov_freight"],$dec);
			
			if ($myrow['discount1']==0){
				$DisplayDiscount2 =" ";
			}else{
				$DisplayDiscount2 = $myrow['discount1']*100 ."%";
			}
				
			if ($myrow['discount2']==0){
				$DisplayDiscount2 .=" ";
			}else{
				$DisplayDiscount2 .= " / ". $myrow['discount2']*100 ."%";
			}
			
			if ($myrow['discount3']==0){
				$DisplayDiscount2 .=" ";
			}else{
				$DisplayDiscount2 .= " / ". $myrow['discount3']*100 ."%";
			}
			
			if ($myrow['discount4']==0){
				$DisplayDiscount2 .=" ";
			}else{
				$DisplayDiscount2 .= " / ". $myrow['discount4']*100 ."%";
			}
			
			if ($myrow['discount5']==0){
				$DisplayDiscount2 .=" ";
			}else{
				$DisplayDiscount2 .= " / ". $myrow['discount5']*100 ."%";
			}
			
			
			
			$rep->row = 290;
			//$rep->TextColLines(4, 5, $DisplayDiscount2, -2);
			// $rep->TextColLines(4, 5, $myrow['discount1'] * 100 ."%"." / ".$myrow['discount2'] * 100 ."%"." / ".$myrow['discount3'] * 100 ."%"." / ".$myrow['discount4'] * 100 ."%"." / ".$myrow['discount5'] * 100 ."%", -2);
			// $rep->row = 290-10;
			// $rep->TextColLines(4, 5, number_format2($SubTotal - ($SubTotal * $myrow['discount1']), $dec), -2);
						
			$tax_items = get_trans_tax_details($j, $i);
    		while ($tax_item = db_fetch($tax_items))
    		{
    			$DisplayTax = number_format2($sign*$tax_item['amount'], $dec);

    			if ($tax_item['included_in_price'])
    			{
					// $rep->TextCol(3, 7, $doc_Included . " " . $tax_item['tax_type_name'] .
						// " (" . $tax_item['rate'] . "%) " . $doc_Amount . ": " . $DisplayTax, -2);
					$rep->row = 273;
					//$rep->TextCol(4, 6,	$DisplayTax, -2);
					//$rep->TextCol(4, 6,	$DisplayTax);
				}
    			else
    			{
					// $rep->TextCol(3, 6, $tax_item['tax_type_name'] . " (" .
						// $tax_item['rate'] . "%)", -2);
					// $rep->TextCol(6, 7,	$DisplayTax, -2);
					$rep->row = 273;
					//$rep->TextCol(4, 5,	$DisplayTax, -2);
				}
				$rep->NewLine();
    		}
			
			$DisplayTotal = number_format2($sign*($myrow["ov_freight"] + $myrow["ov_gst"]+$myrow["ov_freight_tax"] + 
					($myrow["ov_amount"]
						* (1 - $myrow["discount1"]) * (1 - $myrow["discount2"]) * (1 - $myrow["discount3"])
						* (1 - $myrow["discount4"]) * (1 - $myrow["discount5"]))
					),$dec);
		
			$rep->row = 263;
			//$rep->TextCol(4, 5,	$DisplayTotal, -2);
			
			$rep->fontSize = 12;
			$rep->TextWrap(510, 70, 300, $myrow['reference']);
		}
		
	}
	
	if ($email == 0)
		$rep->End();
}

?>