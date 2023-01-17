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
//-----------------------------------------------------------------------------
//
//	Entry/Modify Delivery Note against Sales Order
//
$page_security = 'SA_SALESDELIVERY';
$path_to_root = "..";

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/manufacturing.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

$js = "";

echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />
";

if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}
if ($use_date_picker) {
	$js .= get_js_date_picker();
}
 
$js .= "

$(document).ready(function(){
	$('#limitLink').trigger('click');
})

function callSubmitButton(){
	$('#Commit').trigger('click');
}

//	$(document).ready(function(){
		
		// $('#customer_id').change(function(){
			// post_form();
		// });
		
		//********************Default********************
		var sys_type = 'SA_SALESORDER';
		
		jQuery.prompt.setDefaults({
			show: 'slideDown'
			,top: '20%'
		});
		
		var txt = '<table>'+
					  '<tr>The selected customer credit limit exceeds. Please enter supervisor account for approval.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
		var txt_ = '<table>'+
					  '<tr>Items below minimum inventory level. Enter the supervisor\'s username and password to proceed.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';	
		var txtx = '<table>'+
					  '<tr>The selected customer credit limit exceeds/Items below minimum inventory level. Enter the supervisor\'s username and password to proceed.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
		var txtn = '<table>'+
					  '<tr>Customer details have been changed. Enter the supervisor\'s username and password to proceed.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
					  
		
		var hid_chk_credit = $('input[name=\"hid_chk_credit\"]').val();
		var hid_debtor_no = $('input[name=\"debtor_no11\"]').val();
		var errpro = 'Invalid supervisor user account. Please try again.';
		var sapp = 'Action approved! You can now proceed with the transaction.';
		//alert(hid_debtor_no);
		//***********************************************
		
		function post_form_n(val,ev,f,ty){
		//alert(val+ ' - '+ev+ ' - '+f);
			if(val==true)
				if(f.uname!='' && f.passwd!=''){
				
					//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'ty':ty},
						function(ev){
							// $.prompt(ev);
							if(ev==true){
								// $.prompt(Date());
								$.prompt(sapp,{
								
									buttons: { Ok:true },
									callback: callSubmitButton
								});
								//hid_chk_credit==0;
								$('input[name=\"hid_chk_credit\"]').attr('value',0);
								$('input[name=\"noti_\"]').attr('value',1);
																
								$(\"input[name='credit_']\").val(1);
								$(\"input[name='hid_debtor_no']\").val(hid_debtor_no);
								
								$.post('confirm3.php',{ 'credit_':1, 'hid_debtor_no':hid_debtor_no },
								function(ev){
									//alert(ev);
									//location.reload();
								});				
								
								// $.post('audit.php',{
									// 'type'    : 1,
									// 'trans_no': 1,
									// 'uname'   : f.uname,
									// 'stamp'
								// },
								// function(ev){
								
								// });

							}else{
								/*$.prompt(errpro+'. Reattempt in 3 seconds.');
								window.setTimeout('location.reload()', 3000);*/
								$.prompt(errpro);
							}
						});
				}else
					$.prompt(errpro);
		}
		
		/*if(hid_chk_credit==1)
		$.prompt(txt,{
				buttons: { Ok:true, Cancel:false },
				callback: post_form
			});
			
		var param = $.getUrlVar('ModifyOrderNumber');
		
			$('.editbutton').click(function(ev){
				
				var linee = $(this).attr('name');
				var a = linee.substr(4);
				//alert(a);
			});
			*/
		
		function opemprompt_n(ty, fields, iprice){
			//alert(iprice);

			if((fields == '' || !fields || fields == 0) && (iprice == '' || !iprice || iprice == 0)){
			
			}else{
			
				var txtn = '<table>';
				
				var texts = '';
						  var counter = 0;
						  /*switch(fields){
							case 1: texts += 'Payment Terms';
								break;
							case 2: texts += 'Payment Terms, Price List';
								break;
							case 3: texts += 'Price List';
								break;
							case 4: texts += 'Payment Terms, Price List, Sales Person';
								break;
							case 5: texts += 'Price List, Sales Person';
								break;
							case 6: texts += 'Sales Person';
								break;
							default: texts = fields;
						  }*/
				texts = fields;
				
				if(texts){
					txtn += '<tr><td><b>Fields: </b></td><td>'+texts+'</td></tr>';
				}
				
				if(iprice){
	
						txtn += '<tr><td><b>Line Items: </b></td><td>'+iprice+'</td></tr>'
				
				}
				
				txtn += '<tr>Purchase Order details have been changed. Enter the supervisor\'s username and password to proceed.<br></tr>'+
						  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
						  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>';
						  
					txtn += '</table>';
				$.prompt(txtn,{
						buttons: { Ok:true, Cancel:false },
						callback: post_form_n
					},ty);
			}
		}
//	});
	
	
";

//---------------------------------------Updated Aug 19, 2011------------------
//$js .= "<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>";
//include_once($path_to_root . '/js/jquery-1.3.2.min.js');
// $js .= "<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>";
 // display_error($js);
// die();

//------------------------------------------------------------------------------

if (isset($_GET['ModifyDelivery'])) {
	unset($_SESSION['ddetails']);
	$_SESSION['page_title'] = sprintf(_("Modifying Delivery Receipt # %s."), $_GET['ModifyDelivery']);
	$help_context = "Modifying Delivery Receipt";
	processing_start();
} elseif (isset($_GET['OrderNumber'])) {
	unset($_SESSION['ddetails']);
	//$_SESSION['ddetails']=10;
	$_SESSION['page_title'] = _($help_context = "Deliver Items for a Sales Order");
	processing_start();
}

page($_SESSION['page_title'], false, false, "", $js);

//foreach($_POST as $key=>$val)
//display_error($key.$val);




if (isset($_GET['AddedID'])) {
	$dispatch_no = $_GET['AddedID'];

	display_notification_centered(sprintf(_("Delivery # %s has been entered."),getReferencebyType($dispatch_no, 'DR')));

	display_note(get_customer_trans_view_str(ST_CUSTDELIVERY, $dispatch_no, _("&View This Delivery")), 0, 1);

	display_note(print_document_link($dispatch_no, _("&Print Delivery Receipt"), true, ST_CUSTDELIVERY));
	//display_note(print_document_link($dispatch_no, _("&Email Delivery Receipt"), true, ST_CUSTDELIVERY, false, "printlink", "", 1), 1, 1);
	display_note(print_document_link($dispatch_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 0, 1));
	//display_note(print_document_link($dispatch_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 1, 1), 1);

	display_note(get_gl_view_str(13, $dispatch_no, _("View the GL Journal Entries for this Dispatch")),1);

	hyperlink_params("$path_to_root/sales/customer_invoice.php", _("Invoice This Delivery"), "DeliveryNumber=$dispatch_no");

	hyperlink_params("$path_to_root/sales/inquiry/sales_orders_view.php", _("Select Another Order For Dispatch"), "OutstandingOnly=1");
/*
	echo "
		<p>
		<center>
		<a href='customer_del_so.php?OrderNumber=$dispatch_no&view=0&type=2&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Delivery</a>
		</center>
	";*/
	
	display_footer_exit();

} elseif (isset($_GET['UpdatedID'])) {

	$delivery_no = $_GET['UpdatedID'];

	display_notification_centered(sprintf(_('Delivery Receipt # %s has been updated.'),getReferencebyType($delivery_no, 'DR')));

	display_note(get_trans_view_str(ST_CUSTDELIVERY, $delivery_no, _("View this delivery")), 0, 1);

	display_note(print_document_link($delivery_no, _("&Print Delivery Receipt"), true, ST_CUSTDELIVERY));
	display_note(print_document_link($delivery_no, _("&Email Delivery Receipt"), true, ST_CUSTDELIVERY, false, "printlink", "", 1), 1, 1);
	display_note(print_document_link($delivery_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 0, 1));
	display_note(print_document_link($delivery_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 1, 1), 1);

	hyperlink_params($path_to_root . "/sales/customer_invoice.php", _("Confirm Delivery and Invoice"), "DeliveryNumber=$delivery_no");

	hyperlink_params($path_to_root . "/sales/inquiry/sales_deliveries_view.php", _("Select A Different Delivery"), "OutstandingOnly=1");

/*	echo "
		<p>
		<center>
		<a href='customer_del_so.php?OrderNumber=$dispatch_no&type=2&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Delivery</a>
		</center>
	";
	*/
	display_footer_exit();
}
//-----------------------------------------------------------------------------

if (isset($_GET['OrderNumber']) && $_GET['OrderNumber'] > 0) {
	unset($_SESSION['allownegativecost']);
	$ord = new Cart(ST_SALESORDER, $_GET['OrderNumber'], true);

	/*read in all the selected order into the Items cart  */

	if ($ord->count_items() == 0) {
		hyperlink_params($path_to_root . "/sales/inquiry/sales_orders_view.php",
			_("Select a different sales order to delivery"), "OutstandingOnly=1");
		die ("<br><b>" . _("This order has no items. There is nothing to delivery.") . "</b>");
	}

	$ord->trans_type = ST_CUSTDELIVERY;
	$ord->src_docs = $ord->trans_no;
	$ord->order_no = key($ord->trans_no);
	$ord->trans_no = 0;
	$ord->reference = $Refs->get_next(ST_CUSTDELIVERY);
	$ord->document_date = new_doc_date();
	$_SESSION['Items'] = $ord;
	copy_from_cart();

} elseif (isset($_GET['ModifyDelivery']) && $_GET['ModifyDelivery'] > 0) {

	$_SESSION['Items'] = new Cart(ST_CUSTDELIVERY,$_GET['ModifyDelivery']);
	$_SESSION['Items_old'] = new Cart(ST_CUSTDELIVERY,$_GET['ModifyDelivery']);

	if ($_SESSION['Items']->count_items() == 0) {
		hyperlink_params($path_to_root . "/sales/inquiry/sales_orders_view.php",
			_("Select a different delivery"), "OutstandingOnly=1");
		echo "<br><center><b>" . _("This delivery has all items invoiced. There is nothing to modify.") .
			"</center></b>";
		display_footer_exit();
	}

	copy_from_cart();
	
} elseif ( !processing_active() ) {
	/* This page can only be called with an order number for invoicing*/

	display_error(_("This page can only be opened if an order or delivery receipt has been selected. Please select it first."));

	hyperlink_params("$path_to_root/sales/inquiry/sales_orders_view.php", _("Select a Sales Order to Delivery"), "OutstandingOnly=1");

	end_page();
	exit;

} else {
	check_edit_conflicts();

	if (!check_quantities()) {
		display_error(_("Selected quantity cannot be less than quantity invoiced nor more than quantity	not dispatched on sales order."));

	} elseif(!check_num('ChargeFreightCost', 0)) {
		display_error(_("Freight cost cannot be less than zero"));
		set_focus('ChargeFreightCost');
	}
}

//-----------------------------------------------------------------------------

function check_data()
{
	global $Refs;
	global $SysPrefs;
	global $cfields;
	global $_iprice;
	$stats = 0;
	
	//display_error($_POST['ship_via'].' - '.$_SESSION['Items']->ship_via);
	//display_error($_POST['DispatchDate'].' - '.$_SESSION['Items']->document_date);
	//display_error($_POST['due_date'].' - '.$_SESSION['Items']->due_date);
	//display_error($_POST['salesman'].' - '.$_SESSION['Items']->salesman);
	
	if($_SESSION['Items']->trans_no!=0){
		if($_POST['ship_via'] != $_SESSION['Items']->ship_via && !$_SESSION['ddetails']){
			$stats = 1;
			$cfields .= 'Shipping Company, ';
		}
		if($_POST['DispatchDate'] != $_SESSION['Items']->document_date && !$_SESSION['ddetails']){
			$stats = 1;
			$cfields .= 'Date, ';
		}
		if($_POST['due_date'] != $_SESSION['Items']->due_date && !$_SESSION['ddetails']){
			$stats = 1;
			$cfields .= 'Invoice Deadline, ';
		}
		if($_POST['salesman'] != $_SESSION['Items']->salesman && !$_SESSION['ddetails']){
			$stats = 1;
			$cfields .= 'Salesman, ';
		}
		if($_POST['ChargeFreightCost'] != $_SESSION['Items']->freight_cost && !$_SESSION['ddetails']){
			$stats = 1;
			$cfields .= 'Shipping Fee, ';
		}
		
		$string_content = '';
		
		foreach ($_SESSION['Items']->line_items as $line=>$itm) {
			//display_error($_SESSION['Items']->line_items[$line]->price.' - '.$_SESSION['Items_old']->line_items[$line]->price);
			//display_error($_SESSION['Items']->line_items[$line]->discount_percent.' - '.$_SESSION['Items_old']->line_items[$line]->discount_percent);
			//display_error($_SESSION['Items']->line_items[$line]->discount_percent2.' - '.$_SESSION['Items_old']->line_items[$line]->discount_percent2);
			//display_error($_SESSION['Items']->line_items[$line]->discount_percent3.' - '.$_SESSION['Items_old']->line_items[$line]->discount_percent3);
			
			$string_content = '';
			
			if($_SESSION['Items']->line_items[$line]->price != $_SESSION['Items_old']->line_items[$line]->price){
				$string_content .= 'Price: from '.$_SESSION['Items_old']->line_items[$line]->price.' to '.$_SESSION['Items']->line_items[$line]->price.' | ';
			}
			if($_SESSION['Items']->line_items[$line]->discount_percent != $_SESSION['Items_old']->line_items[$line]->discount_percent){
				$string_content .= 'Discount 1: from '.($_SESSION['Items_old']->line_items[$line]->discount_percent * 100).'% to '.($_SESSION['Items']->line_items[$line]->discount_percent * 100).'% | ';
			}
			if($_SESSION['Items']->line_items[$line]->discount_percent2 != $_SESSION['Items_old']->line_items[$line]->discount_percent2){
				$string_content .= 'Discount 1: from '.($_SESSION['Items_old']->line_items[$line]->discount_percent2 * 100).'% to '.($_SESSION['Items']->line_items[$line]->discount_percent2 * 100).'% | ';
			}
			if($_SESSION['Items']->line_items[$line]->discount_percent3 != $_SESSION['Items_old']->line_items[$line]->discount_percent3){
				$string_content .= 'Discount 1: from '.($_SESSION['Items_old']->line_items[$line]->discount_percent3 * 100).'% to '.($_SESSION['Items']->line_items[$line]->discount_percent3 * 100).'% | ';
			}
			if($_SESSION['Items']->line_items[$line]->discount_percent4 != $_SESSION['Items_old']->line_items[$line]->discount_percent4){
				$string_content .= 'Discount 4: from '.($_SESSION['Items_old']->line_items[$line]->discount_percent4 * 100).'% to '.($_SESSION['Items']->line_items[$line]->discount_percent4 * 100).'% | ';
			}
			if($_SESSION['Items']->line_items[$line]->discount_percent5 != $_SESSION['Items_old']->line_items[$line]->discount_percent5){
				$string_content .= 'Discount 5: from '.($_SESSION['Items_old']->line_items[$line]->discount_percent5 * 100).'% to '.($_SESSION['Items']->line_items[$line]->discount_percent5 * 100).'% | ';
			}
			if($_SESSION['Items']->line_items[$line]->discount_percent6 != $_SESSION['Items_old']->line_items[$line]->discount_percent6){
				$string_content .= 'Discount 6: from '.($_SESSION['Items_old']->line_items[$line]->discount_percent6 * 100).'% to '.($_SESSION['Items']->line_items[$line]->discount_percent6 * 100).'% | ';
			}
			
			if(isset($string_content) && $string_content != ''){
				$string_content = substr($string_content, 0, (strlen($string_content)-2));
			}
			
			if($string_content){
				$_iprice .= '<hr>updated item Item: '.$_SESSION['Items']->line_items[$line]->item_description.' | '.$string_content;
				$stats = 1;
			}
		}
		
		if(isset($cfields) && $cfields != ''){
			$cfields = substr($cfields, 0, (strlen($cfields)-2));
		}	
		
		if($stats){
			display_error("<a id='limitLink' onclick='opemprompt_n(8,\"".$cfields."\", \"".$_iprice."\")' style='cursor:pointer'>Click here to enable customer to transact</a>");	
			return false;
		}
	}
	
	//return false;
	
	if (!isset($_POST['DispatchDate']) || !is_date($_POST['DispatchDate']))	{
		display_error(_("The entered date of delivery is invalid."));
		set_focus('DispatchDate');
		return false;
	}

	if (!is_date_in_fiscalyear($_POST['DispatchDate'])) {
		display_error(_("The entered date of delivery is not in fiscal year."));
		set_focus('DispatchDate');
		return false;
	}

	if (!isset($_POST['due_date']) || !is_date($_POST['due_date']))	{
		display_error(_("The entered dead-line for invoice is invalid."));
		set_focus('due_date');
		return false;
	}

	if ($_SESSION['Items']->trans_no==0) {
		if (!$Refs->is_valid($_POST['ref'])) {
			display_error(_("You must enter a reference."));
			set_focus('ref');
			return false;
		}

		if ($_SESSION['Items']->trans_no==0 && !is_new_reference($_POST['ref'], ST_CUSTDELIVERY)) {
			display_error(_("The entered reference is already in use."));
			set_focus('ref');
			return false;
		}
	}
	if ($_POST['ChargeFreightCost'] == "") {
		$_POST['ChargeFreightCost'] = price_format(0);
	}

	if (!check_num('ChargeFreightCost',0)) {
		display_error(_("The entered shipping value is not numeric."));
		set_focus('ChargeFreightCost');
		return false;
	}

	if ($_SESSION['Items']->has_items_dispatch() == 0 && input_num('ChargeFreightCost') == 0) {
		display_error(_("There are no item quantities on this delivery receipt."));
		return false;
	}

	if (!check_quantities()) {
		return false;
	}
	

	if (!$SysPrefs->allow_negative_stock()&&!isset($_SESSION['allownegativecost'])){
	$aff_items=array();
		foreach ($_SESSION['Items']->line_items as $itm) {

			if ($itm->qty_dispatched && has_stock_holding($itm->mb_flag)) {
				$qoh = get_qoh_on_date($itm->stock_id, $_POST['Location'], $_POST['DispatchDate']);

				if ($itm->qty_dispatched > $qoh) {
					// display_error(_("The delivery cannot be processed because there is an insufficient quantity for item:") .
						// " " . $itm->stock_id . " - " .  $itm->item_description);
						$aff_items[]=$itm->stock_id." - ".$itm->item_description;
						//echo "<script>alert('QWEQWE')</script>";
						// echo "<script>alert('saasdsd');</script>";
						//hidden('not_',$not);
					//	$_POST['noti_'] = 1;
						// display_error('NOTI::'.$_POST['noti_']);
						
				
				}
			}
		}
		$aff_items=array_unique($aff_items);
		if(count($aff_items)>0){
				 display_error(_("The delivery cannot be processed because there is an insufficient quantity for item/s:") .
						 " " . implode(", ",$aff_items));
				display_error('Supervisor\'s approval is needed to enable the customer to transact.<a onclick="openprompt_(2)" class="limitLink" style="cursor:pointer">Click here to enable customer to transact</a>');
				return false;
		}
		
	}
	return true;
}
//------------------------------------------------------------------------------
function copy_to_cart()
{
	$cart = &$_SESSION['Items'];
	$cart->ship_via = $_POST['ship_via'];
	$cart->freight_cost = input_num('ChargeFreightCost');
	$cart->document_date = $_POST['DispatchDate'];
	$cart->due_date =  $_POST['due_date'];
	$cart->Location = $_POST['Location'];
	$cart->Comments = $_POST['Comments'];
	
	$cart->salesman = $_POST['salesman'];
		
	if ($cart->trans_no == 0)
		$cart->reference = $_POST['ref'];

}
//------------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ship_via'] = $cart->ship_via;
	$_POST['ChargeFreightCost'] = price_format($cart->freight_cost);
	$_POST['DispatchDate'] = $cart->document_date;
	$_POST['due_date'] = $cart->due_date;
	$_POST['Location'] = $cart->Location;
	$_POST['Comments'] = $cart->Comments;
	$_POST['cart_id'] = $cart->cart_id;
	$_POST['ref'] = $cart->reference;
	
	$_POST['salesman'] = $cart->salesman;
	
}
//------------------------------------------------------------------------------

function check_quantities()
{
	$ok =1;
	// Update cart delivery quantities/descriptions
	
	$_SESSION['Items']->actions = array();
	
	$others = '';
	
	$others .= ($_SESSION['Items_old']->freight_cost != input_num('ChargeFreightCost') ?
					' | Shipping Cost from '.$_SESSION['Items_old']->freight_cost.' to '.input_num('ChargeFreightCost')
					: '');
	/*if ($others != '')
		$_SESSION['Items']->actions[] = new action_details('updated an item', $_SESSION['Items']->line_items[$line]->stock_id,'','','','','','','',$others);
	*/
	foreach ($_SESSION['Items']->line_items as $line=>$itm) {
		if (isset($_POST['Line'.$line])) {
		if($_SESSION['Items']->trans_no) {
			$min = $itm->qty_done;
			$max = $itm->quantity;
		} else {
			$min = 0;
			$max = $itm->quantity - $itm->qty_done;
		}
		
			if (check_num('Line'.$line, $min, $max)) {
				$_SESSION['Items']->line_items[$line]->qty_dispatched = input_num('Line'.$line);
			} else {
				set_focus('Line'.$line);
				$ok = 0;
			}

		}
		
		$line_desc = '';
		$line_price = $discount_percent = $discount_percent2 = $discount_percent3 = $discount_percent4 = $discount_percent5 = $discount_percent6 = 0;
		
		if (isset($_POST['Line'.$line.'Desc'])) {
			$line_desc = $_POST['Line'.$line.'Desc'];
			if (strlen($line_desc) > 0) {
				$_SESSION['Items']->line_items[$line]->item_description = $line_desc;
			}
		}
		
		//edit price
		if (isset($_POST['Line'.$line.'Price'])) {
			$line_price = input_num('Line'.$line.'Price');
			if (strlen($line_price) > 0) {
				$_SESSION['Items']->line_items[$line]->price = $line_price;
			}
		}
		
		//edit discount_percent
		if (isset($_POST['Line'.$line.'discount_percent'])) {
			$discount_percent = round2(str_replace('%', '', $_POST['Line'.$line.'discount_percent']),1);
			if (strlen($discount_percent) > 0) {
				$_SESSION['Items']->line_items[$line]->discount_percent = $discount_percent/100;
			}
		}
		
		//edit discount_percent2
		if (isset($_POST['Line'.$line.'discount_percent2'])) {
			$discount_percent2 = round2(str_replace('%', '', $_POST['Line'.$line.'discount_percent2']),1);
			if (strlen($discount_percent2) > 0) {
				$_SESSION['Items']->line_items[$line]->discount_percent2 = $discount_percent2/100;
			}
		}
		
		//edit discount_percent3
		if (isset($_POST['Line'.$line.'discount_percent3'])) {
			$discount_percent3 = round2(str_replace('%', '', $_POST['Line'.$line.'discount_percent3']),1);
			if (strlen($discount_percent3) > 0) {
				$_SESSION['Items']->line_items[$line]->discount_percent3 = $discount_percent3/100;
			}
		}
		
		//edit discount_percent4
		if (isset($_POST['Line'.$line.'discount_percent4'])) {
			$discount_percent4 = round2(str_replace('%', '', $_POST['Line'.$line.'discount_percent4']),1);
			if (strlen($discount_percent4) > 0) {
				$_SESSION['Items']->line_items[$line]->discount_percent4 = $discount_percent4/100;
			}
		}
		
		//edit discount_percent5
		if (isset($_POST['Line'.$line.'discount_percent5'])) {
			$discount_percent5 = round2(str_replace('%', '', $_POST['Line'.$line.'discount_percent5']),1);
			if (strlen($discount_percent5) > 0) {
				$_SESSION['Items']->line_items[$line]->discount_percent5 = $discount_percent5/100;
			}
		}
		
		//edit discount_percent6
		if (isset($_POST['Line'.$line.'discount_percent6'])) {
			$discount_percent6 = round2(str_replace('%', '', $_POST['Line'.$line.'discount_percent6']),1);
			if (strlen($discount_percent6) > 0) {
				$_SESSION['Items']->line_items[$line]->discount_percent6 = $discount_percent6/100;
			}
		}

		$_SESSION['Items']->actions[] = new action_details('updated an item', $_SESSION['Items']->line_items[$line]->stock_id,
			
			($_SESSION['Items_old']->line_items[$line]->qty_dispatched - $line_price != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->qty_dispatched.' to '.$_SESSION['Items']->line_items[$line]->qty_dispatched : ''),
			
			($_SESSION['Items_old']->line_items[$line]->price - $line_price != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->price.' to '.$line_price : ''),
			
			($_SESSION['Items_old']->line_items[$line]->discount_percent - $discount_percent != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->discount_percent.'% to '.$discount_percent.'%'
					: ''),
			
			($_SESSION['Items_old']->line_items[$line]->discount_percent2 - $discount_percent != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->discount_percent2.' to '.$discount_percent2.'%'
					: ''),
			
			($_SESSION['Items_old']->line_items[$line]->discount_percent3 - $discount_percent3 != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->discount_percent3.' to '.$discount_percent3.'%'
					: ''),
					
			($_SESSION['Items_old']->line_items[$line]->discount_percent4 - $discount_percent4 != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->discount_percent4.'% to '.$discount_percent4.'%'
					: ''),
			
			($_SESSION['Items_old']->line_items[$line]->discount_percent5 - $discount_percent5 != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->discount_percent5.' to '.$discount_percent5.'%'
					: ''),
			
			($_SESSION['Items_old']->line_items[$line]->discount_percent6 - $discount_percent6 != 0 ?
					'from '.$_SESSION['Items_old']->line_items[$line]->discount_percent6.' to '.$discount_percent6.'%'
					: ''),
					
			'', 
			
			($_SESSION['Items_old']->line_items[$line]->item_description != $line_desc ?
					'from '.$_SESSION['Items_old']->line_items[$line]->item_description.' to '.$line_desc
					: '')
			
			);
	}
//	else
//	  $_SESSION['Items']->freight_cost = input_num('ChargeFreightCost');
	return $ok;
}
//------------------------------------------------------------------------------

function check_qoh()
{
	global $SysPrefs;

	if (!$SysPrefs->allow_negative_stock())	{
		foreach ($_SESSION['Items']->line_items as $itm) {

			if ($itm->qty_dispatched && has_stock_holding($itm->mb_flag)) {
				$qoh = get_qoh_on_date($itm->stock_id, $_POST['Location'], $_POST['DispatchDate']);

				if ($itm->qty_dispatched > $qoh) {
					// display_error(_("The delivery cannot be processed because there is an insufficient quantity for item:") .
						// " " . $itm->stock_id . " - " .  $itm->item_description);
						
						//echo "<script>alert('QWEQWE')</script>";
						// echo "<script>alert('saasdsd');</script>";
						//hidden('not_',$not);
						$_POST['noti_'] = 1;
						// display_error('NOTI::'.$_POST['noti_']);
						
					return false;
				}
			}
		}
	}
	$_POST['noti_'] = 1;
	return true;
}
//------------------------------------------------------------------------------

if (isset($_POST['process_delivery']) && check_data()) {
// if (isset($_POST['process_delivery']) && check_data() && isset($_POST['noti_'])) {
	// if($_POST['noti_']==0){
	// $sql = "UPDATE ".TB_PREF."company
			// SET allow_negative_stock = 1";
			//display_error($sql);
	// db_query($sql);

	// $_POST['noti_'] = 'asdasd';
	// die();
	// display_error($_POST['noti_']);
	// display_error('qwew2q321321');
	// display_error('asdasd');
	$dn = &$_SESSION['Items'];
	
	if ($_POST['bo_policy']) {
		$bo_policy = 0;
	} else {
		$bo_policy = 1;
	}
	$newdelivery = ($dn->trans_no == 0);

	copy_to_cart();
	if ($newdelivery) new_doc_date($dn->document_date);
	$delivery_no = $dn->write($bo_policy);

	// $sql = "UPDATE ".TB_PREF."company
			// SET allow_negative_stock = 0";
			// display_error($sql);
	// db_query($sql);
	
	processing_end();
	if ($newdelivery) {
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$delivery_no");
	} else {
		meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$delivery_no");
	}
	
}elseif(!check_qoh()){
	$_POST['noti_'] = '1';
}

if (isset($_POST['Update']) || isset($_POST['_Location_update'])) {
// display_error('TESTING LNG PO!!!');
	$Ajax->activate('Items');
}
//------------------------------------------------------------------------------

$id=find_submit('Edit');
//$open=2;
if($id!=-1){
	GLOBAL $Ajax;
	$Ajax->activate('body_header');
}

// if(isset($_POST['uname']) || $_POST['passwd']){
	// $sql = "SELECT * 
			// FROM ".TB_PREF."users 
			// WHERE user_id = ".db_escape($_POST['uname'])." AND "."password = ".db_escape(md5($_POST['passwd']));
	// $sql = db_query($sql);
	// if(db_num_rows($sql)!=0){
		// $open = 1;
		// //$id=1;
		// //display_error($_POST['uname']. ' = '.$_POST['passwd']);
	// }else{
		// // $id=-1;
		// display_error('Invalid user account.');
		// $open = 2;
	// }
	
// }

// if($_POST['enter_acc']){
// foreach($_POST['enter_acc'] AS $key=>$value)
		// //display_error($key.' = '.$value);
// }
//------------------------------------------------------------------------------
start_form();
hidden('cart_id');
hidden('check_data',check_qoh());

div_start('body_header');
// if($id!=-1 || $open==2)
// {
// display_error('OPEN1 : '.$open);
// start_form(false,false,$_SERVER['PHP_SELF']);
	// div_start('accnt');
		// start_table();
			// // echo "<tr>";
				// // label_cells("Please enter your account for verification: ");
			// // echo "</tr>";
				// //display_notification(sprintf(_('Please enter your account for verification: ')));
			// start_row();
				// echo "<td>Username: </td><td><input type='text' name='uname' class='uname'></td>";
				// echo "<td>Password: </td><td><input type='password' name='passwd' class='passwd'></td>";
				// // ref_cells(_("Username: "), 'uname', '',null, '', true);
				// // ref_cells(_("Password: "), 'passwd', '',null, '', true);
				
				// submit_cells('enter_acc', _("Submit"),'',_('Select documents'), 'default');
			// end_row();
		// end_table();
	// div_end();
// end_form();
// }
start_table("$table_style2 width=80%", 5);
echo "<tr><td>"; // outer table

start_table("$table_style width=100%");
start_row();
label_cells(_("Customer"), $_SESSION['Items']->customer_name, "class='tableheader2'");
label_cells(_("Branch"), get_branch_name($_SESSION['Items']->Branch), "class='tableheader2'");
label_cells(_("Currency"), $_SESSION['Items']->customer_currency, "class='tableheader2'");
end_row();
start_row();

//if (!isset($_POST['ref']))
//	$_POST['ref'] = $Refs->get_next(ST_CUSTDELIVERY);

if ($_SESSION['Items']->trans_no==0) {
	ref_cells(_("DR No."), 'ref', '', null, "class='tableheader2'");
} else {
	label_cells(_("DR No."), $_SESSION['Items']->reference, "class='tableheader2'");
}

$so_ref = getSORef($_SESSION['Items']->order_no);
$view_so_ref = '';
if ($so_ref != 'auto')
	$view_so_ref = get_customer_trans_view_str(ST_SALESORDER,$_SESSION['Items']->order_no, $so_ref);

label_cells(_("For Sales Order"),	$view_so_ref, "class='tableheader2'");
// label_cells(_("For Sales Order"), get_customer_trans_view_str(ST_SALESORDER, $_SESSION['Items']->order_no), "class='tableheader2'");

label_cells(_("Sales Type"), $_SESSION['Items']->sales_type_name, "class='tableheader2'");
end_row();
start_row();

if (!isset($_POST['Location'])) {
	$_POST['Location'] = $_SESSION['Items']->Location;
}
label_cell(_("Delivery From"), "class='tableheader2'");
label_cell(get_location_name($_POST['Location']));
hidden('Location',$_POST['Location']);
//locations_list_cells(null, 'Location', null, false, true);

if (!isset($_POST['ship_via'])) {
	$_POST['ship_via'] = $_SESSION['Items']->ship_via;
}
label_cell(_("Shipping Company"), "class='tableheader2'");
shippers_list_cells(null, 'ship_via', $_POST['ship_via']);

// set this up here cuz it's used to calc qoh
if (!isset($_POST['DispatchDate']) || !is_date($_POST['DispatchDate'])) {
	$_POST['DispatchDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['DispatchDate'])) {
		$_POST['DispatchDate'] = end_fiscalyear();
	}
}
date_cells(_("Date"), 'DispatchDate', '', $_SESSION['Items']->trans_no==0, 0, 0, 0, "class='tableheader2'");
end_row();

end_table();

echo "</td><td>";// outer table

start_table("$table_style width=99%");

if (!isset($_POST['due_date']) || !is_date($_POST['due_date'])) {
	$_POST['due_date'] = get_invoice_duedate($_SESSION['Items']->customer_id, $_POST['DispatchDate']);
}
start_row();
label_cells(_("Payment Terms:"), get_term_name($_SESSION['Items']->p_terms),"class='tableheader2'");
end_row();
start_row();
date_cells(_("Invoice Dead-line"), 'due_date', '', null, 0, 0, 0, "class='tableheader2'");
end_row();

//-------------------------------------------------------------------------------------------------
$sql = "SELECT salesman FROM ".TB_PREF."sales_orders WHERE order_no=".db_escape($_SESSION['Items']->order_no);
$result = db_query($sql, "could not get salesman");
$row = db_fetch_row($result);

start_row();
// label_cell(_("Sales Person:"),"class='tableheader2'");
// sales_persons_list_cells('', 'salesman', $_SESSION['Items']->salesman);		
label_cell(_("Sales Person:"), "class='tableheader2'");
label_cell(get_salesman_name($row[0]));

hidden('salesman', $row[0]);
end_row();
//-------------------------------------------------------------------------------------------------

end_table();

echo "</td></tr>";
end_table(1); // outer table

$row = get_customer_to_order($_SESSION['Items']->customer_id);
if ($row['dissallow_invoices'] == 1)
{
	display_error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
	end_form();
	end_page();
	exit();
}	
div_end();

display_heading(_("Delivery Items"));

div_start('Items');
start_table("$table_style width=80%");

$new = $_SESSION['Items']->trans_no==0;
$th = array(_("Item Code"), _("Item Description"), 
	$new ? _("Ordered") : _("Max. delivery"), _("Units"), $new ? _("Delivered") : _("Invoiced"),
	_("This Delivery"), _("Price"), _("Tax Type"), _("Discount"), /*_("Discount2"), _("Discount3"), 
	_("Discount4 %"), _("Discount5 %"), _("Discount6 %"),*/ _("Notes"), _("Total"),'');

table_header($th);
$k = 0;
$has_marked = false;

$vatable = $nonvat = $zerorated = 0;
foreach ($_SESSION['Items']->line_items as $line=>$ln_itm) 
{

	if ($ln_itm->quantity==$ln_itm->qty_done) {
		continue; //this line is fully delivered
	}
	// if it's a non-stock item (eg. service) don't show qoh
	$show_qoh = true;
	if ($SysPrefs->allow_negative_stock() || !has_stock_holding($ln_itm->mb_flag) ||
		$ln_itm->qty_dispatched == 0) {
		$show_qoh = false;
	}

	if ($show_qoh) {
		$qoh = get_qoh_on_date($ln_itm->stock_id, $_POST['Location'], $_POST['DispatchDate']);
	}

	if ($show_qoh && ($ln_itm->qty_dispatched > $qoh)) {
		// oops, we don't have enough of one of the component items
		start_row("class='stockmankobg'");
		$has_marked = true;
	} else {
		alt_table_row_color($k);
	}
	view_stock_status_cell($ln_itm->stock_id);

	label_cell($ln_itm->item_description);
	hidden('Line'.$line.'Desc', $ln_itm->item_description);
	//text_cells(null, 'Line'.$line.'Desc', $ln_itm->item_description, 30, 50);
	$dec = get_qty_dec($ln_itm->stock_id);
	qty_cell($ln_itm->quantity, false, $dec);
	label_cell($ln_itm->units);
	qty_cell($ln_itm->qty_done, false, $dec);

	small_qty_cells(null, 'Line'.$line, qty_format($ln_itm->qty_dispatched, $ln_itm->stock_id, $dec), null, null, $dec);

	$display_discount_percent = percent_format($ln_itm->discount_percent*100) . "%";
	// $display_discount_percent2 = percent_format($ln_itm->discount_percent2*100) . "%";
	// $display_discount_percent3 = percent_format($ln_itm->discount_percent3*100) . "%";
	// $display_discount_percent4 = percent_format($ln_itm->discount_percent4*100) . "%";
	// $display_discount_percent5 = percent_format($ln_itm->discount_percent5*100) . "%";
	// $display_discount_percent6 = percent_format($ln_itm->discount_percent6*100) . "%";

	$line_total = ($ln_itm->qty_dispatched * $ln_itm->price * (1 - $ln_itm->discount_percent) * (1 - $ln_itm->discount_percent2) * (1 - $ln_itm->discount_percent3) * (1 - $ln_itm->discount_percent4) * (1 - $ln_itm->discount_percent5) * (1 - $ln_itm->discount_percent6));
	
	/////////////////////////////////////////////////////////////////
			
	$sql = "SELECT tax_type_id FROM 0_stock_master WHERE stock_id = ".db_escape($ln_itm->stock_id);
	$result = db_query($sql,"could not retrieve tax_type_id");
	$row = db_fetch_row($result);		
	
	if($row[0] == 1)
		$vatable += $line_total;
	else if($row[0] == 2)
		$nonvat += $line_total;
	else
		$zerorated += $line_total;
	
	/////////////////////////////////////////////////////////////////

	//if ($id!=-1)
	//{
	//amount_cell($ln_itm->price);
	$_POST['price'.$line] = number_format2($ln_itm->price,2);
	$_POST['display_discount_percent_'.$line] = $display_discount_percent;
	// $_POST['display_discount_percent2_'.$line] = $display_discount_percent2;
	// $_POST['display_discount_percent3_'.$line] = $display_discount_percent3;
	// $_POST['display_discount_percent4_'.$line] = $display_discount_percent4;
	// $_POST['display_discount_percent5_'.$line] = $display_discount_percent5;
	// $_POST['display_discount_percent6_'.$line] = $display_discount_percent6;
	
	//amount_cells(null, 'price'.$line);
	//amount_cell($ln_itm->price);	
	// text_cells(null, 'Line'.$line.'Price', $ln_itm->price, 10, 20);
	
	amount_cells(null, 'Line'.$line.'Price', $ln_itm->price);
		
	label_cell($ln_itm->tax_type_name);
	// label_cell($display_discount_percent, "nowrap align=right");
	// label_cell($display_discount_percent2, "nowrap align=right");
	// label_cell($display_discount_percent3, "nowrap align=right");
	
	//display_error($display_discount_percent);
	//amount_cells(null, 'price'.$line);	
	
	// amount_cells(null, 'display_discount_percent_'.$line,'',"disabled=disabled");	
	// amount_cells(null, 'display_discount_percent2_'.$line);	
	// amount_cells(null, 'display_discount_percent3_'.$line);	
	
	text_cells(null, 'Line'.$line.'discount_percent', $display_discount_percent);
	// text_cells(null, 'Line'.$line.'discount_percent2', $display_discount_percent2);
	// text_cells(null, 'Line'.$line.'discount_percent3', $display_discount_percent3);
	// text_cells(null, 'Line'.$line.'discount_percent4', $display_discount_percent4);
	// text_cells(null, 'Line'.$line.'discount_percent5', $display_discount_percent5);
	// text_cells(null, 'Line'.$line.'discount_percent6', $display_discount_percent6);
	
	
	label_cell($ln_itm->comment);
	amount_cell($line_total);
	
	hidden('linee',$line);
		edit_button_cell("Edit$line", _("Edit"),
		  _('Edit document line'));
	//}

	end_row();
	$counter++;
}

	// used for checking actions=====================================================
	// foreach ($_SESSION['Items']->actions as $line_no=>$action_det)
	// {
		// start_row();
		// label_cell($action_det->act.' Item Code:'.$action_det->stock_id 
			// .' | Description:'.$action_det->item_description 
			// .' | Quantity:'.$action_det->quantity 
			// .' | Price:'.$action_det->price
			// .' | Discount 1:'.$action_det->discount_percent
			// .' | Discount 2:'.$action_det->discount_percent2
			// .' | Discount 3:'.$action_det->discount_percent3
			// .' | Note:'.$action_det->comment
			// .$action_det->others
			// , 'colspan=11');
		// end_row();
	// }

div_start('err_msg_div');
/*echo "<div class='err_msg'>";
echo 'Supervisor\'s approval is needed to enable modifying of items. <a onclick="pro(3)" style="cursor:pointer">Click here</a> ';
// display_error('Supervisor\'s approval is needed to enable modifying of items. <a class="app_">Click here</a> ');
echo "</div>";*/
div_end();

hidden('item_count',$counter);

$sql__ = "SELECT allow_negative_stock 
		FROM ".TB_PREF."company";
$sql__ = db_query($sql__);
$d__   = db_fetch($sql__);


hidden('noti_',$d__[0]);
// if(!isset($_POST['noti_']))
// $_POST['noti_']==0;

$_POST['ChargeFreightCost'] =  get_post('ChargeFreightCost', 
	price_format($_SESSION['Items']->freight_cost));

$colspan = 10;



$inv_items_total = $_SESSION['Items']->get_items_total_dispatch();	

$taxes = $_SESSION['Items']->get_taxes(input_num('ChargeFreightCost'));
$_tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['Items']->tax_included, 0, 0);

$display_sub_total = price_format($inv_items_total );

$display_total = price_format(
					(
						(
							$inv_items_total
						)
						+ input_num('ChargeFreightCost') 
					)
				);

label_row(_("Total Sales"), $display_sub_total, "colspan=$colspan align=right","align=right");

// label_row(_("VATABLE Sales"), price_format($inv_items_total - $_tax_total), "colspan=$colspan align=right","align=right");
// label_row(_("NON-VATABLE Sales"), price_format(0), "colspan=$colspan align=right","align=right");
// label_row(_("ZERO RATED Sales"), price_format(0), "colspan=$colspan align=right","align=right");

//////////////////////////////////////////////////////////////////////////////
$sql1 = "SELECT tax_group_id FROM 0_cust_branch WHERE branch_code = ".db_escape($_SESSION['Items']->Branch);
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

//$display_sub_total = price_format($inv_items_total + input_num('ChargeFreightCost'));

$tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['Items']->tax_included);

				

start_row();
label_cell(_("Shipping Cost"), "colspan=$colspan align=right");
small_amount_cells(null, 'ChargeFreightCost', $_SESSION['Items']->freight_cost);
end_row();

label_row(_("Amount Total"), $display_total, "colspan=$colspan align=right","align=right");

end_table(1);

if ($has_marked) {
	display_note(_("Highlighted items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='red'");
}
start_table($table_style2);

policy_list_row(_("Action For Balance"), "bo_policy", null);

textarea_row(_("Memo"), 'Comments', null, 50, 4);

end_table(1);
div_end();
submit_center_first('Update', _("Update"),
  _('Refresh document page'), false);
submit_center_last('process_delivery', _("Make DR"),
  _('Check entered data and save document'), false);
  
end_form();

echo "<center><br><a href=javascript:goBack()>Cancel DR</a><p></center>";

end_page();

?>


<script>
	$(document).ready(function(){
	$('.limitLink').trigger('click');
	})

	$('input[name="Line'+a+'Price"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent2"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent3"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent4"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent5"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent6"]').attr('disabled',false);
	
	function callSubmitButton(){
	$('#process_delivery').trigger('click');
	}
		c = $('input[name="item_count"]').val();
		for(a=0;a<c;a++){
			$(':button[name="Edit'+a+'"]').attr('disabled',false);
			$('input[name="Line'+a+'Price"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent2"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent3"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent4"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent5"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent6"]').attr('disabled',true);
		}
		
		jQuery.prompt.setDefaults({
			show: 'slideDown'
			,top: '40%'
		});
		$(this).keydown(function(e) {
			// ESCAPE key pressed
			if (e.keyCode == 27) {
			   history.back();
			}
		});
		// $('div[class="jqicontainer"]').find('uname').focus();
		var mes = '<br><font color=red>Invalid supervisor user account. Please try again.</font>';
		var txt = '<table>'+
				  '<tr>Please enter supervisor user account for approval.<br></tr>'+
				  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
				  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
				  '</table>';
		var txt_ = '<table>'+
					  '<tr>Items below minimum inventory level. Enter the supervisor\'s username and password to proceed.<br></tr>'+
					  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
					  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
					  '</table>';
		
	
		//Prompt when page is loaded...

	
		// if(a==undefined)
		// write(pro_(null,txt));
		
		// $('.errr').css({
			// 'margin': '10px',
			// 'padding': '3px',
			// 'border': '1px solid #cc3300',
			// 'background-color': '#ffcccc',
			// 'color': '#dd2200',
			// 'text-align': 'center',
			// 'width': '100%',
			
		// });
		
	
		function post_form(val,ev,f,ty){
			if(val==true){
				if(f.uname!='' && f.passwd!=''){
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'ty':ty },
						function(ev){
							 // $.prompt(ev);
							 
							if(ev==true){
								$.prompt('Action approved! You can now modify the line items of this transaction.');
								c = $('input[name="item_count"]').val();
								for(a=0;a<c;a++){
									$(':button[name="Edit'+a+'"]').attr('disabled',false);
									$('input[name="Line'+a+'Price"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent2"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent3"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent4"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent5"]').attr('disabled',false);
									$('input[name="Line'+a+'discount_percent6"]').attr('disabled',false);
									
									$('.err_msg').hide();
								}
							}else{
								txt = '<table>'+
										  '<tr>Please enter supervisor user account for approval.<br></tr>'+
										  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
										  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
										  '</table>'+
										  '<br><font color=red>Invalid supervisor user account. Please try again.</font>';
								
								$('input[name="logged_uname"]').attr('value',$);
								pro(3);
							}
						});
				}
			}else{
			
				// history.back();
			}
		}			
	//	event.preventDefault();
		
	
		var errpro = 'Invalid supervisor user account. Please try again.';
			function pro(ty){
			
			$.prompt(txt,{
				buttons: { Ok:true, Cancel:false },
				callback: post_form,//,
			},ty);
		}
			function openprompt_(ty){
			$.prompt(txt_,{
				// opacity: 0.8,
				buttons: { Ok:true, Cancel:false },
				callback: post_form_,//,
				//, prefix:'jqismooth'
				//,top: 300
			},ty);
			}
			
			function post_form_(val,ev,f,ty){
		//	alert(val+ ' - '+ev+ ' - '+f+' - '+ty);
				if(f.uname!='' && f.passwd!=''){
					// $.prompt(f.noti_);
					//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'noti_':'1','ty':ty},
						function(ev){
							// $.prompt(ev);
							if(ev==true){
								$.prompt('Action approved! You can now proceed with the transaction.',{
								
									buttons: { Ok:true },
									callback: callSubmitButton
								});
								// alert('asdasd');
								// $('input[name="noti_"]').attr("value",1);
								// $('input[name="check_data"]').attr("value",0);
								// location.reload();
								//return true;
								//alert('sadasd');
								// $("input[name='noti_']").val(1);
								//var noti_ = 1;
								
								$.post('confirm.php',{ 'noti_':1 },
								function(ev){
									//alert(ev);
									//location.reload();
								});
								// location.reload();
							}
							 else
								$.prompt(errpro);
								//ev.preventDefault();
						});
					//$.prompt('Action approved. You can now proceed..');
				}else
					$.prompt(errpro);
			}
						
			
					  
			
			// var or_no = $.getUrlVar('OrderNumber');
	//		var check = $('input[name="check_data"]').val();
			// alert(check);
	
			

		/*
		$('.editbutton').click(function(ev){
		
			//var linee = $(this+'input[name="linee"]').val();
			var linee = $(this).attr('name');
			var errpro = 'Invalid supervisor user account. Please try again.';
		

			function post_form(val,ev,f){
			//alert(val+ ' - '+ev+ ' - '+f);
				if(val==true)
					if(f.uname!='' && f.passwd!=''){
						//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
						$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd },
							function(ev){
								$.prompt(ev);
								if(ev==true){
									$.prompt('Action approved. You can now proceed..');
									//enable_form(true);
										//var a = $('.editbutton').val();
										//var linee = ($('.editbutton').attr('name')).substr(4);
										// linee = linee.substr(4);
										//alert('QWEQWE:' +linee.substr(4));
										var a = linee.substr(4);
										//alert(a);
										$(':button[name="Edit'+a+'"]').attr('disabled','true').css('cursor:default;');
										//$('[name="Edit'+a+'"]').attr('disabled',false);

										$('input[name="Line'+a+'Price"]').attr('disabled',false);
										$('input[name="Line'+a+'discount_percent"]').attr('disabled',false);
										$('input[name="Line'+a+'discount_percent2"]').attr('disabled',false);
										$('input[name="Line'+a+'discount_percent3"]').attr('disabled',false);
										$('#Update').show();

								}else
									$.prompt(errpro);
							});
					}else
						$.prompt(errpro,{
						
						});
			}
						
			var txt = '<table>'+
					  '<tr>Please enter account for approval.<br></tr>'+
					  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
					  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
					  '</table>';
					  
			
			$.prompt(txt,{
				// opacity: 0.8,
				buttons: { Ok:true, Cancel:false },
				callback: post_form,//,
				//, prefix:'jqismooth'
				//,top: 300
			});
			
			event.preventDefault();
		});
		*/
</script>