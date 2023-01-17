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
//	Entry/Modify Sales Quotations
//	Entry/Modify Sales Order
//	Entry Direct Delivery
//	Entry Direct Invoice
//

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

// set_page_security( @$_SESSION['Items']->trans_type,
	// array(	ST_SALESORDER=>'SA_SALESORDER',
			// ST_SALESQUOTE => 'SA_SALESQUOTE',
			// ST_CUSTDELIVERY => 'SA_SALESDELIVERY',
			// ST_SALESINVOICE => 'SA_SALESINVOICE'),
	// array(	'NewOrder' => 'SA_SALESORDER',
			// 'ModifySalesOrder' => 'SA_SALESORDER',
			// 'NewQuotation' => 'SA_SALESQUOTE',
			// 'ModifyQuotationNumber' => 'SA_SALESQUOTE',
			// 'NewDelivery' => 'SA_SALESDELIVERY',
			// 'NewInvoice' => 'SA_SALESINVOICE')
// );
add_js_ufile($path_to_root.'/js/thickbox.js');

echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />
";

$js = '';
$fields;

$js .= "

 
	function openThickBox(id){
		url = '../sales/sample.php?OrderNumber=' + id + '&KeepThis=true&TB_iframe=true&height=280&width=300';
		tb_show('Void SO', url);
	}
";


//-----------------------------------------------------------------------------




if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}

$js .= "



$(document).ready(function(){
	$('.limitLink').trigger('click');
})

function callSubmitButton(){
	$('#ProcessOrder').trigger('click');
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
		
		function post_form(val,ev,f,ty){
		//alert(val+ ' - '+ev+ ' - '+f+' - '+ty);
	//	alert(ty);
			if(val==true)
				if(f.uname!='' && f.passwd!=''){
					//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'noti_':1,'ty':ty },
						function(ev){
							// $.prompt(ev);
							if(ev==true){
								// $.prompt(Date());
								// $.prompt(sapp);
								$.prompt(sapp,{
								
									buttons: { Ok:true },
									callback: callSubmitButton
								});
								//hid_chk_credit==0;
								$('input[name=\"hid_chk_credit\"]').attr('value',0);
																
								$(\"input[name='credit_']\").val(1);
								$(\"input[name='hid_debtor_no']\").val(hid_debtor_no);
								
								$.post('confirm2.php',{ 'credit_':1, 'hid_debtor_no':hid_debtor_no },
								function(ev){
									//alert(ev);
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
		
		function post_form_(val,ev,f,ty){
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
		
		if(hid_chk_credit==1)
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
			
		function openprompt(ty){
		//	alert(ty);
			$.prompt(txt,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form
				},ty);
		}	
		function openpromptx(ty){
		//	alert(ty);
			$.prompt(txtx,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form
				},ty);
		}
		function openprompt_(ty){
			$.prompt(txt_,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form_
				},ty);
		}
		function opemprompt_n(ty, fields,extra, iprice, mode){
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
					if(mode == 0){
						txtn += '<tr><td><b>Price Change for Items: </b></td><td>'+iprice+'</td></tr>'
					}else{
						txtn += '<tr><td><b>Line Items: </b></td><td>'+iprice+'</td></tr>'
					}
				}
				
				switch(extra){
					case 1: txtn+='<tr>The selected customer credit limit exceeds/Items below minimum inventory level. <br>';
					ty=5;
						break;
					case 2: txtn+='<tr>The selected customer credit limit exceeds. <br>';
					ty=6;
						break;
					case 3: txtn+='<tr>Items below minimum inventory level. <br>';
					ty=7;
						break;
					
				}
				txtn += '<tr>Customer details have been changed. Enter the supervisor\'s username and password to proceed.<br></tr>'+
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

//-----------------------------------------------------------------------------

if (isset($_GET['NewDelivery']) && is_numeric($_GET['NewDelivery'])) {
     unset($_SESSION['allownegativecost']);
     unset($_SESSION['allowcredit']);
	 unset($_SESSION['negative_inv']);
	 unset($_SESSION['cdetails']);
	 unset($_SESSION['Items']);
	$_SESSION['page_title'] = _($help_context = "Direct Sales Delivery");
	create_cart(ST_CUSTDELIVERY, $_GET['NewDelivery']);
	//display_error();
} elseif (isset($_GET['NewInvoice']) && is_numeric($_GET['NewInvoice'])) {
	 unset($_SESSION['allownegativecost']);
     unset($_SESSION['allowcredit']);
	 unset($_SESSION['negative_inv']);
	 unset($_SESSION['cdetails']);
	 unset($_SESSION['Items']);
	$_SESSION['page_title'] = _($help_context = "Direct Sales Invoice");
	create_cart(ST_SALESINVOICE, $_GET['NewInvoice']);

} elseif (isset($_GET['ModifyOrderNumber']) && is_numeric($_GET['ModifyOrderNumber'])) {
 unset($_SESSION['allowcredit']);
 unset($_SESSION['negative_inv']);
     unset($_SESSION['allownegativecost']);
 unset($_SESSION['cdetails']);
	$help_context = 'Modifying Sales Order';
	$_SESSION['page_title'] = sprintf( _("Modifying Sales Order"));
	create_cart(ST_SALESORDER, $_GET['ModifyOrderNumber']);

} elseif (isset($_GET['ModifyQuotationNumber']) && is_numeric($_GET['ModifyQuotationNumber'])) {

	$help_context = 'Modifying Sales Quotation';
	$_SESSION['page_title'] = sprintf( _("Modifying Sales Quotation # %s"), $_GET['ModifyQuotationNumber']);
	create_cart(ST_SALESQUOTE, $_GET['ModifyQuotationNumber']);

} elseif (isset($_GET['NewOrder'])) {
 unset($_SESSION['allowcredit']);
	unset($_POST['customer_id']);
	unset($_SESSION['negative_inv']);
	 unset($_SESSION['allownegativecost']);
	unset($_SESSION['Items']);
	unset($_SESSION['cdetails']);
	$_SESSION['page_title'] = _($help_context = "New Sales Order Entry");
	create_cart(ST_SALESORDER, 0);
} elseif (isset($_GET['NewQuotation'])) {

	$_SESSION['page_title'] = _($help_context = "New Sales Quotation Entry");
	create_cart(ST_SALESQUOTE, 0);
} elseif (isset($_GET['NewQuoteToSalesOrder'])) {
	$_SESSION['page_title'] = _($help_context = "Sales Order Entry");
	create_cart(ST_SALESQUOTE, $_GET['NewQuoteToSalesOrder']);
}

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------
if (list_updated('branch_id')) {
	// when branch is selected via external editor also customer can change
	$br = get_branch(get_post('branch_id'));
	$_POST['customer_id'] = $br['debtor_no'];
	$Ajax->activate('customer_id');
}

if (isset($_GET['AddedID'])) {
	$order_no = $_GET['AddedID'];
	display_notification_centered(sprintf( _("Order # %s has been entered."),getSONum($order_no)));
	hyperlink_params($_SERVER['PHP_SELF'], _("Edit this Sales Order"), "ModifyOrderNumber=".$order_no);
	br();
	submenu_view(_("&View This Order"), ST_SALESORDER, $order_no);

	submenu_print(_("&Print This Order"), ST_SALESORDER, $order_no, 'prtopt');
	//submenu_print(_("&Email This Order"), ST_SALESORDER, $order_no, null, 1);
	set_focus('prtopt');
	
	/*submenu_option(_("Make &Delivery Against This Order"),
		"/sales/customer_delivery.php?OrderNumber=$order_no");
		
	submenu_option(_("Make &Invoice Against This Delivery"),
		"/sales/customer_so_inv.php?OrderNumber=$order_no");*/

	submenu_option(_("Enter a &New Order"),	"/sales/sales_order_entry.php?NewOrder=0");
	
	/*echo "
		<p>
		<center>
		<a href='customer_del_so.php?OrderNumber=$order_no&view=0&type=1&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Sales Order</a>
		</center>
	";*/
	
	display_footer_exit();

} elseif (isset($_GET['UpdatedID'])) {
	$order_no = $_GET['UpdatedID'];

	display_notification_centered(sprintf( _("Order # %s has been updated."),getSONum($order_no)));
hyperlink_params($_SERVER['PHP_SELF'], _("Edit this Sales Order"), "ModifyOrderNumber=".$order_no);
	br();
	submenu_view(_("&View This Order"), ST_SALESORDER, $order_no);

	submenu_print(_("&Print This Order"), ST_SALESORDER, $order_no, 'prtopt');
	//submenu_print(_("&Email This Order"), ST_SALESORDER, $order_no, null, 1);
	set_focus('prtopt');

	/*submenu_option(_("Confirm Order Quantities and Make &Delivery"),
		"/sales/customer_delivery.php?OrderNumber=$order_no");*/

	submenu_option(_("Select A Different &Order"),
		"/sales/inquiry/sales_orders_view.php?OutstandingOnly=1");
/*
	echo "
		<p>
		<center>
		<a href='customer_del_so.php?OrderNumber=$order_no&type=1&view=0&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Sales Order</a>
		</center>
	";
		*/
	display_footer_exit();

} elseif (isset($_GET['AddedQU'])) {
	$order_no = $_GET['AddedQU'];
	display_notification_centered(sprintf( _("Quotation # %s has been entered."),$order_no));

	submenu_view(_("&View This Quotation"), ST_SALESQUOTE, $order_no);

	submenu_print(_("&Print This Quotation"), ST_SALESQUOTE, $order_no, 'prtopt');
	//submenu_print(_("&Email This Quotation"), ST_SALESQUOTE, $order_no, null, 1);
	set_focus('prtopt');
	
	submenu_option(_("Make &Sales Order Against This Quotation"),
		"/sales/sales_order_entry.php?NewQuoteToSalesOrder=$order_no");

	submenu_option(_("Enter a New &Quotation"),	"/sales/sales_order_entry.php?NewQuotation=0");

	display_footer_exit();

} elseif (isset($_GET['UpdatedQU'])) {
	$order_no = $_GET['UpdatedQU'];

	display_notification_centered(sprintf( _("Quotation # %s has been updated."),$order_no));

	submenu_view(_("&View This Quotation"), ST_SALESQUOTE, $order_no);

	submenu_print(_("&Print This Quotation"), ST_SALESQUOTE, $order_no, 'prtopt');
	//submenu_print(_("&Email This Quotation"), ST_SALESQUOTE, $order_no, null, 1);
	set_focus('prtopt');

	submenu_option(_("Make &Sales Order Against This Quotation"),
		"/sales/sales_order_entry.php?NewQuoteToSalesOrder=$order_no");

	submenu_option(_("Select A Different &Quotation"),
		"/sales/inquiry/sales_orders_view.php?type=".ST_SALESQUOTE);

	display_footer_exit();
} elseif (isset($_GET['AddedDN'])) {
	$delivery = $_GET['AddedDN'];

	//display_notification_centered(sprintf(_("Delivery # %d has been entered."),$delivery));
	display_notification_centered(sprintf(_("Delivery # %s has been entered."),getReferencebyType($delivery, 'DR')));

	submenu_view(_("&View This Delivery"), ST_CUSTDELIVERY, $delivery);

	submenu_print(_("&Print Delivery Receipt"), ST_CUSTDELIVERY, $delivery, 'prtopt');
	//submenu_print(_("&Email Delivery Receipt"), ST_CUSTDELIVERY, $delivery, null, 1);
	submenu_print(_("P&rint as Packing Slip"), ST_CUSTDELIVERY, $delivery, 'prtopt', null, 1);
	//submenu_print(_("E&mail as Packing Slip"), ST_CUSTDELIVERY, $delivery, null, 1, 1);
	set_focus('prtopt');

	display_note(get_gl_view_str(ST_CUSTDELIVERY, $delivery, _("View the GL Journal Entries for this Dispatch")),0, 1);

	submenu_option(_("Make &Invoice Against This Delivery"),
		"/sales/customer_invoice.php?DeliveryNumber=$delivery");

	if ((isset($_GET['Type']) && $_GET['Type'] == 1))
		submenu_option(_("Enter a New Template &Delivery"),
			"/sales/inquiry/sales_orders_view.php?DeliveryTemplates=Yes");
	else
		submenu_option(_("Enter a &New Delivery"), 
			"/sales/sales_order_entry.php?NewDelivery=0");

	display_footer_exit();

} elseif (isset($_GET['AddedDI'])) {
	$invoice = $_GET['AddedDI'];

	//display_notification_centered(sprintf(_("Invoice # %d has been entered."), $invoice));
	display_notification_centered(sprintf(_("Invoice # %s has been entered."), getReferencebyType($invoice, 'INV')));

	submenu_view(_("&View This Invoice"), ST_SALESINVOICE, $invoice);

	submenu_print(_("&Print Sales Invoice"), ST_SALESINVOICE, $invoice."-".ST_SALESINVOICE, 'prtopt');
	//submenu_print(_("&Email Sales Invoice"), ST_SALESINVOICE, $invoice."-".ST_SALESINVOICE, null, 1);
	set_focus('prtopt');
	
	$sql = "SELECT trans_type_from, trans_no_from FROM ".TB_PREF."cust_allocations
			WHERE trans_type_to=".ST_SALESINVOICE." AND trans_no_to=".db_escape($invoice);
	$result = db_query($sql, "could not retrieve customer allocation");
	$row = db_fetch($result);
	if ($row !== false)
		submenu_print(_("Print &Receipt"), $row['trans_type_from'], $row['trans_no_from']."-".$row['trans_type_from'], 'prtopt');

	display_note(get_gl_view_str(ST_SALESINVOICE, $invoice, _("View the GL &Journal Entries for this Invoice")),0, 1);

	if ((isset($_GET['Type']) && $_GET['Type'] == 1))
		submenu_option(_("Enter a &New Template Invoice"), 
			"/sales/inquiry/sales_orders_view.php?InvoiceTemplates=Yes");
	else
		submenu_option(_("Enter a &New Direct Invoice"),
			"/sales/sales_order_entry.php?NewInvoice=0");

	display_footer_exit();
} else
	check_edit_conflicts();
//-----------------------------------------------------------------------------

function copy_to_cart()
{
	$cart = &$_SESSION['Items'];

	$cart->reference = $_POST['ref'];
//display_error('->'.$_POST['ref']);
	$cart->Comments =  $_POST['Comments'];

	$cart->salesman = $_POST['salesman'];
	
	$cart->p_terms = $_POST['p_terms'];
	
	//display_error();
	
	$cart->document_date = $_POST['OrderDate'];
	if ($cart->trans_type == ST_SALESINVOICE)
		$cart->cash = $_POST['cash']; 
	if ($cart->cash) {
		$cart->due_date = $cart->document_date;
		$cart->phone = $cart->cust_ref = $cart->delivery_address = '';
		$cart->freight_cost = input_num('freight_cost');
		$cart->ship_via = 1;
		$cart->deliver_to = '';//$_POST['deliver_to'];
	} else {
		$cart->due_date = $_POST['delivery_date'];
		$cart->cust_ref = $_POST['cust_ref'];
		$cart->freight_cost = input_num('freight_cost');
		$cart->deliver_to = $_POST['deliver_to'];
		$cart->delivery_address = $_POST['delivery_address'];
		$cart->phone = $_POST['phone'];
		$cart->Location = $_POST['Location'];
		$cart->ship_via = $_POST['ship_via'];

		$cart->p_terms = $_POST['p_terms'];
	}
	
	}
	if (isset($_POST['email']))
		$cart->email =$_POST['email'];
	else
		$cart->email = '';
	$cart->customer_id	= $_POST['customer_id'];
	$cart->Branch = $_POST['branch_id'];
	$cart->sales_type = $_POST['sales_type'];
	// POS
	if ($cart->trans_type!=ST_SALESORDER && $cart->trans_type!=ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
		$cart->dimension_id = $_POST['dimension_id'];
		$cart->dimension2_id = $_POST['dimension2_id'];
	}	

//-----------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ref'] = $cart->reference;
	$_POST['Comments'] = $cart->Comments;
	//display_error('~>'.$_POST['ref']);
	$_POST['salesman'] = $cart->salesman;
	$_POST['OrderDate'] = $cart->document_date;
	$_POST['delivery_date'] = $cart->due_date;
	$_POST['cust_ref'] = $cart->cust_ref;
	$_POST['freight_cost'] = price_format($cart->freight_cost);
	$_POST['p_terms'] = $cart->p_terms;
		
	$_POST['deliver_to'] = $cart->deliver_to;
	$_POST['delivery_address'] = $cart->delivery_address;
	$_POST['phone'] = $cart->phone;
	$_POST['Location'] = $cart->Location;
	$_POST['ship_via'] = $cart->ship_via;

	$_POST['customer_id'] = $cart->customer_id;

	$_POST['branch_id'] = $cart->Branch;
	$_POST['sales_type'] = $cart->sales_type;
	// POS 
	if ($cart->trans_type == ST_SALESINVOICE)
		$_POST['cash'] = $cart->cash;
	if ($cart->trans_type!=ST_SALESORDER && $cart->trans_type!=ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
		$_POST['dimension_id'] = $cart->dimension_id;
		$_POST['dimension2_id'] = $cart->dimension2_id;
	}	
	$_POST['cart_id'] = $cart->cart_id;
		
}
//--------------------------------------------------------------------------------
function get_so_status($order_no){
		if(!is_numeric($order_no))
		return true;
		$stat=db_fetch(db_query("SELECT is_approve FROM ".TB_PREF."sales_orders WHERE order_no=".$order_no));
		return $stat[0];

}
function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_stock_id_edit');
}

function checkSalesOrderDetails(&$stats, &$fields, &$_iprice, &$_mode){	
	if($_SESSION['Items']->trans_type==ST_SALESORDER){
		$so_details = getSODetails(key($_SESSION['Items']->trans_no));
		
		if($_SESSION['Items']->trans_no == 0){
			//new order
			$_mode = 0;
			$cust  = get_customer($_POST['customer_id']);
			$branch = get_branch($_POST['branch_id']);
			
			if($_POST['p_terms'] != $cust['payment_terms'] && !$_SESSION['cdetails']){
				$fields .= 'Payment Terms, ';
				$stats = false;
			}
			if($_POST['sales_type'] != $cust['sales_type'] && !$_SESSION['cdetails']){
				$fields .= 'Price List, ';
				$stats = false;
			}
			if($_POST['salesman'] != $branch['salesman'] && !$_SESSION['cdetails']){
				$fields .= 'Sales Person, ';
				$stats = false;
			}
			$date = date('m/d/Y');
			if($_POST['OrderDate'] != $date && !$_SESSION['cdetails']){
				$fields .= 'Order Date, ';
				$stats = false;
			}
			if($_POST['Location'] != $branch['default_location'] && !$_SESSION['cdetails']){
				$fields .= 'Deliver from Location, ';
				$stats = false;
			}
			if($_POST['delivery_date'] != $date && !$_SESSION['cdetails']){
				$fields .= 'Required Delivery Date, ';
				$stats = false;
			}
			/*if($_POST['deliver_to'] != $branch['br_name'] && !$_SESSION['cdetails']){
				$fields .= 'Deliver To, ';
				$stats = false;
			}*/
			if($_POST['delivery_address'] != $cust['address'] && !$_SESSION['cdetails']){
				$fields .= 'Delivery Address, ';
				$stats = false;
			}
				if($_POST['phone'] != $branch['phone'] && !$_SESSION['cdetails']){
				$fields .= 'Contact Phone Number, ';
				$stats = false;
			}
			/*if($_POST['Comments'] != '' && !$_SESSION['cdetails']){
				$fields .= 'Comments, ';
				$stats = false;
			}*/
			if($_POST['ship_via'] != $branch['default_ship_via'] && !$_SESSION['cdetails']){
				$fields .= 'Shipping Company, ';
				$stats = false;
			}		
			
			$pchange = $_SESSION['Items']->get_changed_price();
			//display_error(count($pchange));
			if(count($pchange) > 0){
				foreach($pchange as $p){
					$_iprice .= $_SESSION['Items']->line_items[$p]->item_description.'(price), ';
					//display_error($_SESSION['Items']->line_items[$p]->item_description);
					//display_error($p);
				}
				$stats = false;
			}
			
			if(isset($_iprice) && $_iprice != ''){
				$_iprice = substr($_iprice, 0, (strlen($_iprice)-2));
			}
			
		}else{
			//update order
			$so = get_all_sales_order_details(key($_SESSION['Items']->trans_no));
			//display_error($_POST['p_terms'].' - '.$so->payment_terms);
			if($_POST['p_terms'] != $so->payment_terms && !$_SESSION['cdetails']){
				$fields .= 'Payment Terms, ';
				$stats = false;
			}
			if($_POST['sales_type'] != $so->order_type && !$_SESSION['cdetails']){
				$fields .= 'Price List, ';
				$stats = false;
			}
			if($_POST['salesman'] != $so->salesman && !$_SESSION['cdetails']){
				$fields .= 'Sales Person, ';
				$stats = false;
			}
			if($_POST['OrderDate'] != sql2date($so->ord_date) && !$_SESSION['cdetails']){
				$fields .= 'Order Date, ';
				$stats = false;
			}
			if($_POST['Location'] != $so->from_stk_loc && !$_SESSION['cdetails']){
				$fields .= 'Deliver from Location, ';
				$stats = false;
			}
			if($_POST['delivery_date'] != sql2date($so->delivery_date) && !$_SESSION['cdetails']){
				$fields .= 'Required Delivery Date, ';
				$stats = false;
			}
			//display_error($_POST['deliver_to'].' - '.$so->deliver_to);
			//$stats = false;
			/*if($_POST['deliver_to'] != $so->deliver_to && !$_SESSION['cdetails']){
				$fields .= 'Deliver To, ';
				$stats = false;
			}*/
			if($_POST['delivery_address'] != $so->delivery_address && !$_SESSION['cdetails']){
				$fields .= 'Delivery Address, ';
				$stats = false;
			}
			if($_POST['phone'] != $so->contact_phone && !$_SESSION['cdetails']){
				$fields .= 'Contact Phone Number, ';
				$stats = false;
			}
			/*if($_POST['Comments'] != '' && !$_SESSION['cdetails']){
				$fields .= 'Comments, ';
				$stats = false;
			}*/
			if($_POST['ship_via'] != $so->ship_via && !$_SESSION['cdetails']){
				$fields .= 'Shipping Company, ';
				$stats = false;
			}	
			
			if($so_details['is_approve'] == 0){
				$_mode = 0;
				$pchange = $_SESSION['Items']->get_changed_price();
				//display_error(count($pchange));
				if(count($pchange) > 0){
					foreach($pchange as $p){
						$_iprice .= $_SESSION['Items']->line_items[$p]->item_description.', ';
						//display_error($_SESSION['Items']->line_items[$p]->item_description);
						//display_error($p);
					}
					$stats = false;
				}
				
				if(isset($_iprice) && $_iprice != ''){
					$_iprice = substr($_iprice, 0, (strlen($_iprice)-2));
				}
			}else{
				$_mode = 1;
				
				if($_POST['freight_cost'] != $so->freight_cost && !$_SESSION['cdetails']){
					$fields .= 'Shipping Fee, ';
					$stats = false;
				}
				if($_POST['discount1'] != $so->discount1 && !$_SESSION['cdetails']){
					$fields .= 'Discount 1, ';
					$stats = false;
				}
				if($_POST['discount2'] != $so->discount2 && !$_SESSION['cdetails']){
					$fields .= 'Discount 2, ';
					$stats = false;
				}
				if($_POST['discount3'] != $so->discount3 && !$_SESSION['cdetails']){
					$fields .= 'Discount 3, ';
					$stats = false;
				}
				if($_POST['discount4'] != $so->discount4 && !$_SESSION['cdetails']){
					$fields .= 'Discount 4, ';
					$stats = false;
				}
				if($_POST['discount5'] != $so->discount5 && !$_SESSION['cdetails']){
					$fields .= 'Discount 5, ';
					$stats = false;
				}
				
				$action_details = '';
				if ($_SESSION['Items']->actions != '')
				{
					foreach ($_SESSION['Items']->actions as $line_no=>$action_det)
					{
						
						$action_details .= '  <hr> - ';
						
						$action_details .= $action_det->act.' Item:'.$action_det->item_description;
						
						if($action_det->act){
							$stats = false;
						}
						
						if ($action_det->quantity != '')	
							$action_details .= ' | Quantity:'.$action_det->quantity;

						if ($action_det->price != '')	
							$action_details .= ' | Price:'.$action_det->price;
							
						if ($action_det->discount_percent > 0)	
							$action_details .=' | Discount 1:'.$action_det->discount_percent;
							
						if ($action_det->discount_percent > 0)	
							$action_details .=' | Discount 2:'.$action_det->discount_percent2;
					
						if ($action_det->discount_percent > 0)	
							$action_details .=' | Discount 3:'.$action_det->discount_percent3;
						
						if ($action_det->discount_percent > 0)	
							$action_details .=' | Note:'.$action_det->comment;
							
					}
				}
				$_iprice = $action_details;
			}
			
		}
		
		if(isset($fields) && $fields != ''){
			$fields = substr($fields, 0, (strlen($fields)-2));
		}
		
		//$fields .= $action_details;
	}else if($_SESSION['Items']->trans_type==ST_CUSTDELIVERY){
		$_mode = 1;
		if($_SESSION['Items']->trans_no == 0){
			//new order
			$cust  = get_customer($_POST['customer_id']);
			$branch = get_branch($_POST['branch_id']);
			
			if($_POST['p_terms'] != $cust['payment_terms'] && !$_SESSION['cdetails']){
				$fields .= 'Payment Terms, ';
				$stats = false;
			}
			if($_POST['sales_type'] != $cust['sales_type'] && !$_SESSION['cdetails']){
				$fields .= 'Price List, ';
				$stats = false;
			}
			if($_POST['salesman'] != $branch['salesman'] && !$_SESSION['cdetails']){
				$fields .= 'Sales Person, ';
				$stats = false;
			}
			$date = date('m/d/Y');
			if($_POST['OrderDate'] != $date && !$_SESSION['cdetails']){
				$fields .= 'Order Date, ';
				$stats = false;
			}
			if($_POST['Location'] != $branch['default_location'] && !$_SESSION['cdetails']){
				$fields .= 'Deliver from Location, ';
				$stats = false;
			}
			if($_POST['delivery_date'] != $date && !$_SESSION['cdetails']){
				$fields .= 'Required Delivery Date, ';
				$stats = false;
			}
			/*if($_POST['deliver_to'] != $branch['br_name'] && !$_SESSION['cdetails']){
				$fields .= 'Deliver To, ';
				$stats = false;
			}*/
			if($_POST['delivery_address'] != $cust['address'] && !$_SESSION['cdetails']){
				$fields .= 'Delivery Address, ';
				$stats = false;
			}
				if($_POST['phone'] != $branch['phone'] && !$_SESSION['cdetails']){
				$fields .= 'Contact Phone Number, ';
				$stats = false;
			}
			/*if($_POST['Comments'] != '' && !$_SESSION['cdetails']){
				$fields .= 'Comments, ';
				$stats = false;
			}*/
			if($_POST['ship_via'] != $branch['default_ship_via'] && !$_SESSION['cdetails']){
				$fields .= 'Shipping Company, ';
				$stats = false;
			}
			
			if(isset($fields) && $fields != ''){
				$fields = substr($fields, 0, (strlen($fields)-2));
			}	
			
			$pchange = $_SESSION['Items']->get_changed_price();
			//display_error(count($pchange));
			if(count($pchange) > 0){
				foreach($pchange as $p){
					$_iprice .= $_SESSION['Items']->line_items[$p]->item_description.'(price), ';
					//display_error($_SESSION['Items']->line_items[$p]->item_description);
					//display_error($p);
				}
				$stats = false;
			}
			
			if(isset($_iprice) && $_iprice != ''){
				$_iprice = substr($_iprice, 0, (strlen($_iprice)-2));
			}
		}
	}elseif($_SESSION['Items']->trans_type==ST_SALESINVOICE){
		$_mode = 1;
		if($_SESSION['Items']->trans_no == 0){
			//new order
			$cust  = get_customer($_POST['customer_id']);
			$branch = get_branch($_POST['branch_id']);
			
			
			if($_POST['sales_type'] != $cust['sales_type'] && !$_SESSION['cdetails']){
				$fields .= 'Price List, ';
				$stats = false;
			}
			if($_POST['salesman'] != $branch['salesman'] && !$_SESSION['cdetails']){
				$fields .= 'Sales Person, ';
				$stats = false;
			}
			$date = date('m/d/Y');
			if($_POST['OrderDate'] != $date && !$_SESSION['cdetails']){
				$fields .= 'Order Date, ';
				$stats = false;
			}
			
			if($_POST['cash'] == 0){
				//delayed
				if($_POST['p_terms'] != $cust['payment_terms'] && !$_SESSION['cdetails']){
					$fields .= 'Payment Terms, ';
					$stats = false;
				}
				if($_POST['Location'] != $branch['default_location'] && !$_SESSION['cdetails']){
					$fields .= 'Deliver from Location, ';
					$stats = false;
				}
				if($_POST['delivery_date'] != $date && !$_SESSION['cdetails']){
					$fields .= 'Required Delivery Date, ';
					$stats = false;
				}
				/*if($_POST['deliver_to'] != $branch['br_name'] && !$_SESSION['cdetails']){
					$fields .= 'Deliver To, ';
					$stats = false;
				}*/
				if($_POST['delivery_address'] != $cust['address'] && !$_SESSION['cdetails']){
					$fields .= 'Delivery Address, ';
					$stats = false;
				}
					if($_POST['phone'] != $branch['phone'] && !$_SESSION['cdetails']){
					$fields .= 'Contact Phone Number, ';
					$stats = false;
				}
				/*if($_POST['Comments'] != '' && !$_SESSION['cdetails']){
					$fields .= 'Comments, ';
					$stats = false;
				}*/
				if($_POST['ship_via'] != $branch['default_ship_via'] && !$_SESSION['cdetails']){
					$fields .= 'Shipping Company, ';
					$stats = false;
				}
			}else if($_POST['cash'] == 1){
				//cash
				//no checking required
			}
			
			if(isset($fields) && $fields != ''){
				$fields = substr($fields, 0, (strlen($fields)-2));
			}	
			
			$pchange = $_SESSION['Items']->get_changed_price();
			//display_error(count($pchange));
			if(count($pchange) > 0){
				foreach($pchange as $p){
					$_iprice .= $_SESSION['Items']->line_items[$p]->item_description.'(price), ';
					//display_error($_SESSION['Items']->line_items[$p]->item_description);
					//display_error($p);
				}
				$stats = false;
			}
			
			if(isset($_iprice) && $_iprice != ''){
				$_iprice = substr($_iprice, 0, (strlen($_iprice)-2));
			}
		}
	}
}

//--------------------------------------------------------------------------------
function can_process() {
	global $fields;
	global $_iprice;
	global $_mode;
	global $Refs;
	$_SESSION['fields_change']=0;
	
	$fields = '';
	$_iprice = '';
	$_mode = 0;
	$stats = true;
	$countme = 0;
	$comma = 0;
	//display_error($_POST['p_terms'] . ' - ' . $cust['payment_terms'] . ' - ' . $_SESSION['Items']->p_terms);
	$ses = $_SESSION['Items'];
	copy_to_cart();
	
	if ($_SESSION['Items']->get_total_lines() > $_SESSION['Items']->max_invoice_lines)
	{
		display_error('This transaction will exceed '.$_SESSION['Items']->max_invoice_lines.' lines for the invoice printout. 
		Please create another transaction for the other items. Line count:'.$_SESSION['Items']->get_total_lines());
   		return false;
	}

	$_SESSION['Items'] = $ses;
	
	checkSalesOrderDetails($stats, $fields, $_iprice, $_mode);
	
	//return false;
	
	if($stats == false){
		$_SESSION['fields_change']=1;
//		return false;
	}else{
		$fields = 0;
	}	
	//return false;
	//display_error($_SESSION['Items']->p_terms);
	//display_error($cust['payment_terms']);
	
	//display_error($_SESSION['Items']->sales_type);
	//display_error($cust['sales_type']);
	
	//$_SESSION['Items']->salesman = 1;
	
	//display_error(print_r($_SESSION['Items']));
	//display_error($branch['salesman']);
 
	
	//display_error($_SESSION['Items']->trans_type);
	
	// if($_SESSION['Items']->trans_type !== ST_SALESORDER){
		// foreach($_SESSION['Items']->line_items as $line=>$ln_itm)
		// {
			// $qoh = get_qoh_on_date($ln_itm->stock_id, $_POST['Location'], $_POST['OrderDate']);
			
			// if($ln_itm->quantity > $qoh)
			// {				
				// if(!isset($_SESSION['allownegativecost']) || $_SESSION['allownegativecost'] == 0){
					// display_error('Supervisor\'s approval is needed to enable the customer to transact.
									// <a onclick="openprompt_(2)" style="cursor:pointer">Click here to enable customer to transact</a>'
									// );
					// return false;
				// }
			// }
		// }
	// }

	if($_POST['p_terms'] == 0 && $_POST['cash'] == 0){
		display_error(_("You must select a payment term."));
		set_focus('p_terms');
		return false;
	}
	
	if($_POST['sales_type'] == 0){
		display_error(_("You must select a price list."));
		set_focus('sales_type');
		return false;
	}
	
	if($_POST['salesman'] == 0){
		display_error(_("You must select a sales person."));
		set_focus('salesman');
		return false;
	}
	
	$total_items = count($_SESSION['Items']->line_items);
	//display_error(' total count ' . $total_item);
	if (!isset($total_items) || $total_items == 0 || strlen($total_items) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('AddItem');
		return false;
	}
	
	// if($_POST['noti_']==1)
	// return false;

	// //display_error($_POST['noti_']);
	
	// if($_POST['noti_']==0 && $order->trans_type == ST_SALESINVOICE){
		// return false;
	// }else{
		// return true;
	// }
	
	if (!get_post('customer_id')) 
	{
		display_error(_("There is no customer selected."));
		set_focus('customer_id');
		return false;
	} 
	
	if (!get_post('branch_id')) 
	{
		display_error(_("This customer has no branch defined."));
		set_focus('branch_id');
		return false;
	} 
	
	##########################
	if($_POST['freight_cost']){
		///check the shipping cost and the 5 level discount.
	}
	##########################
	
	if (!isset($_POST['OrderDate']) || !is_date($_POST['OrderDate'])) {
		display_error(_("The entered date is invalid."));
		set_focus('OrderDate');
		return false;
	}
	if ($_SESSION['Items']->trans_type!=ST_SALESORDER && $_SESSION['Items']->trans_type!=ST_SALESQUOTE && !is_date_in_fiscalyear($_POST['OrderDate'])) {
		display_error(_("The entered date is not in fiscal year"));
		set_focus('OrderDate');
		return false;
	}
	// if (count($_SESSION['Items']->line_items) == 0)	{
		// display_error(_("You must enter at least one non empty item line."));
		// set_focus('AddItem');
		// return false;
	// }
	$cart_total=$_SESSION['Items']->get_items_total();
	if($cart_total<=0){
			
			display_error("Order Total must be greater than zero.");
			set_focus('price');
			return false;

	}
	if ($_SESSION['Items']->cash == 0) {
	if (strlen($_POST['deliver_to']) <= 1) {
		display_error(_("You must enter the person or company to whom delivery should be made to."));
		set_focus('deliver_to');
		return false;
	}


		if (strlen($_POST['delivery_address']) <= 1) {
			display_error( _("You should enter the street address in the box provided. Orders cannot be accepted without a valid street address."));
			set_focus('delivery_address');
			return false;
		}

		if ($_POST['freight_cost'] == "")
			$_POST['freight_cost'] = price_format(0);

		if (!check_num('freight_cost',0)) {
			display_error(_("The shipping cost entered is expected to be numeric."));
			set_focus('freight_cost');
			return false;
		}
		if (!is_date($_POST['delivery_date'])) {
			if ($_SESSION['Items']->trans_type==ST_SALESQUOTE)
				display_error(_("The Valid date is invalid."));
			else	
				display_error(_("The delivery date is invalid."));
			set_focus('delivery_date');
			return false;
		}
		//if (date1_greater_date2($_SESSION['Items']->document_date, $_POST['delivery_date'])) {
		if (date1_greater_date2($_POST['OrderDate'], $_POST['delivery_date'])) {
			if ($_SESSION['Items']->trans_type==ST_SALESQUOTE)
				display_error(_("The requested valid date is before the date of the quotation."));
			else	
				display_error(_("The requested delivery date is before the date of the order."));
			set_focus('delivery_date');
			return false;
		}
	}
	else
	{
		if (!db_has_cash_accounts())
		{
			display_error(_("You need to define a cash account for your Sales Point."));
			return false;
		}	
	}	
	if (!$Refs->is_valid($_POST['ref'])) {
		display_error(_("You must enter a reference. -- ".$_POST['ref']));
		set_focus('ref');
		return false;
	}
   	if ($_SESSION['Items']->trans_no==0 && !is_new_reference($_POST['ref'], 
   		$_SESSION['Items']->trans_type)) {
   		display_error(_("The entered reference is already in use."));
		set_focus('ref');
   		return false;
   	}
	
	
	
	
	return true;
}
//-----------------------------------------------------------------------------
// $sql = "SELECT allow_negative_stock 
		  // FROM ".TB_PREF."company";
// $sql = db_query($sql);
// $data = db_fetch($sql);
// // hidden('noti_',$data[0]);
// if(isset($data[0])){
// display_error($data[0]);
	// $_SESSION['allownegativecost'] = $data[0];
	// hidden('noti_',$_SESSION['allownegativecost']);
// }
// display_error($_SESSION['allownegativecost']);
// $_SESSION['noti_'] = $data[0];
// display_error($_SESSION['noti_']);

//-----------------------------------------------------------------------------

function get_transactions11($debtorno, $date)
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

function check_credit11($debtor_no)
{
	$sql = "SELECT allow_credit FROM ".TB_PREF."debtors_master WHERE debtor_no=".db_escape($debtor_no);

	$result = db_query($sql, "could not get allow_credit");

	$row = db_fetch_row($result);
	return $row[0];
}

function can_credit() {
$_SESSION['negative_inv']=0;
global $fields;
global $_iprice;
global $_mode;

	$can_credit = "SELECT allow_credit_limit as allow FROM ".TB_PREF."company";
	$can_credit = db_query($can_credit);
	$is = db_fetch($can_credit);
	// display_error($is['allow']);
	
		if($is['allow'])
			return $is['allow'];

		if($_SESSION['Items']->trans_type != ST_SALESORDER){
		//	display_error($_SESSION['Items']->trans_type.ST_SALESORDER);
		foreach($_SESSION['Items']->line_items as $line=>$ln_itm)
		{
			$qoh = get_qoh_on_date($ln_itm->stock_id, $_POST['Location'], $_POST['OrderDate']);
			
			if($ln_itm->quantity > $qoh)
			{				
				if(!isset($_SESSION['allownegativecost']) || $_SESSION['allownegativecost'] == 0){
					$_SESSION['negative_inv']=1;
					}
				}
			}
			
			
		}
	$chk_credit = 0;
	
	$myrow = get_customer_to_order($_POST['customer_id']);
	$cart_total=$_SESSION['Items']->get_items_total();
	$name = $myrow['name'];
	
	$date = Today();
	$res = get_transactions11($_POST['customer_id'],$date );
	
	$total_inv = 0;
	$total_rec = 0;
	$total_record = 0;
	$total_amt = str_replace(',', '', $_POST['tamt']) -0;
	//display_error($total_amt);
	while ($trans = db_fetch($res))
	{
		if ($trans['type'] == 10)
			$total_inv += $trans["TotalAmount"] - $trans["Allocated"];
		else
			$total_rec += $trans["TotalAmount"] - $trans["Allocated"];
			
	}

		$total_record = $total_inv - $total_rec + $cart_total;
	//display_error('total record '.$total_record.' -- '.$chk_credit."--".$myrow['credit_limit']);
	if(get_so_status(key($_SESSION['Items']->trans_no))){
	if ($total_record > $myrow['credit_limit']  && !$_SESSION['allowcredit'] && $total_record>0 && $_SESSION['negative_inv'])

	{
	if($_SESSION['fields_change'])
	display_error("<a class='limitLink' onclick='opemprompt_n(4,\"".$fields."\",1, \"".$_iprice."\")' style='cursor:pointer'>Click here to enable customer to transact</a>");	
	else
	display_error("<a class='limitLink' onclick='openpromptx(3)' style='cursor:pointer'>Click here to enable customer to transact</a>");	
		return false;
		//$chk_credit = 1;

	}
	else if ($total_record > $myrow['credit_limit']  && !$_SESSION['allowcredit'] && $total_record>0)

	{
	if($_SESSION['fields_change'])
	display_error("<a class='limitLink' onclick='opemprompt_n(4,\"".$fields."\",2, \"".$_iprice."\", ".$_mode.")' style='cursor:pointer'>Click here to enable customer to transact</a>");	
	else
	display_error("<a class='limitLink' onclick='openprompt(1)' style='cursor:pointer'>Click here to enable customer to transact</a>");	
		return false;
		//$chk_credit = 1;

	}	
	else if ($_SESSION['negative_inv']&&!$_SESSION['allownegativecost'])

	{
	if($_SESSION['fields_change'])
	display_error("<a class='limitLink' onclick='opemprompt_n(4,\"".$fields."\",3, \"".$_iprice."\", ".$_mode.")' style='cursor:pointer'>Click here to enable customer to transact</a>");	
	else
	display_error("<a class='limitLink' onclick='openprompt_(2)' style='cursor:pointer'>Click here to enable customer to transact</a>");	
		return false;
		//$chk_credit = 1;

	}
	else if($_SESSION['fields_change']&& !$_SESSION['cdetails']){
			display_error("<a class='limitLink' onclick='opemprompt_n(4, \"".$fields."\", 0, \"".$_iprice."\", ".$_mode.")' style='cursor:pointer'>Click here to enable customer to transact</a>");	
			return false;
	}
	}
	// hidden('hid_chk_credit', $chk_credit);
	// hidden('hid_debtor_no', $_POST['customer_id']);
	
	// if($chk_credit == 1 && check_credit11($_POST['customer_id']) == 0)
	// {
		
	// }
	
	return true;
	
}

//hidden('debtor_no11', $_POST['customer_id']);
//-----------------------------------------------------------------------------

if (isset($_POST['ProcessOrder']) && can_process() && can_credit()) {
// if (isset($_POST['ProcessOrder']) && $_POST['noti_']==1) {

	copy_to_cart();

	// if($_POST['noti_']==0){
		// $sql = "UPDATE ".TB_PREF."company
				// SET allow_negative_stock = 1";
				// // display_error($sql);
		// db_query($sql);
	// }
	
	##############
	$_SESSION['Items']->due_date = get_invoice_duedate2($_POST['p_terms'], $_SESSION['Items']->document_date);
	
	$modified = ($_SESSION['Items']->trans_no != 0);
	$so_type = $_SESSION['Items']->so_type;

	$_SESSION['Items']->write(1);
	if (count($messages)) { // abort on failure or error messages are lost
		$Ajax->activate('_page_body');
		display_footer_exit();
	}
	$trans_no = key($_SESSION['Items']->trans_no);
	$trans_type = $_SESSION['Items']->trans_type;
	new_doc_date($_SESSION['Items']->document_date);
	##############3
	
	
	// $sql = "UPDATE ".TB_PREF."debtors_master 
			// SET allow_credit = 0
			// WHERE debtor_no = ".$_POST['customer_id'];
	// // echo $sql;
	// // display_error($sql);
	// db_query($sql);
	
		// $sql = "UPDATE ".TB_PREF."company
				// SET allow_negative_stock = 0";
				// // display_error($sql);
		// db_query($sql);
	
	
	unset($_SESSION['allownegativecost']);
	unset($_SESSION['allowcredit']);
	
	processing_end();
	if ($modified) {
		if ($trans_type == ST_SALESQUOTE)
			meta_forward($_SERVER['PHP_SELF'], "UpdatedQU=$trans_no");
		else	
			meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$trans_no");
	} elseif ($trans_type == ST_SALESORDER) {
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
	} elseif ($trans_type == ST_SALESQUOTE) {
		meta_forward($_SERVER['PHP_SELF'], "AddedQU=$trans_no");
	} elseif ($trans_type == ST_SALESINVOICE) {
		meta_forward($_SERVER['PHP_SELF'], "AddedDI=$trans_no&Type=$so_type");
	} else {
		meta_forward($_SERVER['PHP_SELF'], "AddedDN=$trans_no&Type=$so_type");
	}
}

if (isset($_POST['update'])) {
	copy_to_cart();
	$Ajax->activate('items_table');
}

//--------------------------------------------------------------------------------

if(list_updated('cash')){
	$sql = "SELECT payment_terms
				FROM 0_debtors_master
				WHERE debtor_no = ".$_POST['customer_id'];
	$query = db_query($sql);
	if(db_num_rows($query) > 0){
		$row = mysql_fetch_object($query);
		$_POST['p_terms'] = $row->payment_terms;
	}
	$Ajax->activate('p_term');
}

function check_item_data()
{
	$_SESSION['negative_inv']=0;
	//display_error($_SESSION['Items']->trans_type." ".ST_SALESORDER);
	//display_error($_POST['sales_type']);
	//display_error(input_num('Disc'));
	global $SysPrefs;
	if($_POST['stock_id']==''||!isset($_POST['stock_id'])){
		display_error( _("Please enter the item code"));
		set_focus('stock_id');
		return false;
	}else if($_POST['sales_type']==0||$_POST['sales_type']==null){
		display_error("Please choose a price list first.");
		set_focus('sales_type');
		return false;
	}else if(!check_num('qty', 0)) {
		display_error( _("The item could not be updated because you are attempting to set the quantity ordered to less than 0"));
		set_focus('qty');
		return false;
	} else if(!check_num('price', 0)) {
		display_error( _("Price for item must be entered and can not be less than 0"));
		set_focus('price');
		return false;
	} else if(!check_num('Disc', 0, 100) || !check_num('Disc2', 0, 100) || !check_num('Disc3', 0, 100) || !check_num('Disc4', 0, 100) || !check_num('Disc5', 0, 100) || !check_num('Disc6', 0, 100)) {
		display_error( _("Discount for item can not be less than 0 or more than 100"));
		set_focus('Disc');
		return false;
	} else if(isset($_POST['LineNo']) && isset($_SESSION['Items']->line_items[$_POST['LineNo']])
	    && !check_num('qty', $_SESSION['Items']->line_items[$_POST['LineNo']]->qty_done)) {

		set_focus('qty');
		display_error(_("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively."));
		return false;
	} // Joe Hunt added 2008-09-22 -------------------------
	else if($_SESSION['Items']->trans_type!=ST_SALESORDER && $_SESSION['Items']->trans_type!=ST_SALESQUOTE && !$SysPrefs->allow_negative_stock() &&
		is_inventory_item($_POST['stock_id']))
	{
		//display_error(ST_SALESORDER-$_SESSION['Items']->trans_type);
		// $sql = "SELECT allow_negative_stock 
		  // FROM ".TB_PREF."company";
		// $sql = db_query($sql);
		// $data = db_fetch($sql);

		//display_error($_SESSION['allownegativecost']);
		$qoh = get_qoh_on_date($_POST['stock_id'], $_POST['Location'], $_POST['OrderDate']);
		if (input_num('qty') > $qoh && $_SESSION['Items']->trans_type != ST_SALESORDER)
		{
			if(!isset($_SESSION['allownegativecost']) || $_SESSION['allownegativecost'] == 0){
				$_SESSION['negative_inv']=1;
				// $stock = get_item($_POST['stock_id']);
				// display_error(_("The delivery cannot be processed because there is an insufficient quantity for item:") .
					// " " . $stock['stock_id'] . " - " . $stock['description'] . " - " .
					// _("Quantity On Hand") . " = " . number_format2($qoh, get_qty_dec($_POST['stock_id'])));
					
				// display_error('Supervisor\'s approval is needed to enable the customer to transact.
						// <a onclick="openprompt_(2)" style="cursor:pointer">Click here to enable customer to transact</a>'
						// );
						// // $sql = "UPDATE ".TB_PREF."company
								// // SET allow_negative_stock = 1";
								// // // display_error($sql);
						// // db_query($sql);
						
			// //			$_SESSION['allownegativecost'] =1;
				
				// return false;
			}
		}
		return true;
	}

	return true;
	
}

//--------------------------------------------------------------------------------

function handle_update_item()
{
	unset($_SESSION['allowcredit']);
	unset($_SESSION['allownegativecost']);
	if ($_POST['UpdateItem'] != '' && check_item_data()) {
	check_discs();
		$_SESSION['Items']->actions[] = new action_details('updated an item', $_SESSION['Items']->line_items[$_POST['LineNo']]->stock_id, 
		
			($_SESSION['Items']->line_items[$_POST['LineNo']]->quantity - input_num('qty') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->quantity.' to '.input_num('qty')
					: ''),
			
			($_SESSION['Items']->line_items[$_POST['LineNo']]->price - input_num('price') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->price.' to '.input_num('price')
					: ''),
			
			($_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent - input_num('Disc') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent.'% to '.input_num('Disc').'%'
					: ''),
			
			($_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent2 - input_num('Disc2') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent2.' to '.input_num('Disc2').'%'
					: ''),
			
			($_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent3 - input_num('Disc3') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent3.' to '.input_num('Disc3').'%'
					: ''),
					
			($_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent4 - input_num('Disc4') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent3.' to '.input_num('Disc4').'%'
					: ''),
					
			($_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent5 - input_num('Disc5') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent5.' to '.input_num('Disc5').'%'
					: ''),
					
			($_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent6 - input_num('Disc6') != 0 ?
					'from '.$_SESSION['Items']->line_items[$_POST['LineNo']]->discount_percent6.' to '.input_num('Disc6').'%'
					: ''),
			
		 $_POST['comment'], 
		 $_POST['item_description']);
		
		$_SESSION['Items']->update_cart_item($_POST['LineNo'],
		 input_num('qty'), input_num('price'),
		 input_num('Disc') / 100, input_num('Disc2') / 100, 
		 input_num('Disc3') / 100, input_num('Disc4') / 100, 
		 input_num('Disc5') / 100, input_num('Disc6') / 100, 
		 $_POST['item_description'], $_POST['comment'] );
	}
  line_start_focus();
}

//--------------------------------------------------------------------------------

function handle_delete_item($line_no)
{
    if ($_SESSION['Items']->some_already_delivered($line_no) == 0) {
	
		$_SESSION['Items']->actions[] = new action_details('deleted an item', $_SESSION['Items']->line_items[$line_no]->stock_id, 
				$_SESSION['Items']->line_items[$line_no]->quantity, $_SESSION['Items']->line_items[$line_no]->price, 
				$_SESSION['Items']->line_items[$line_no]->discount_percent, 
				$_SESSION['Items']->line_items[$line_no]->discount_percent2, 
				$_SESSION['Items']->line_items[$line_no]->discount_percent3, $_SESSION['Items']->line_items[$line_no]->comment,
				$_SESSION['Items']->line_items[$line_no]->item_description);
				
	    $_SESSION['Items']->remove_from_cart($line_no);
    } else {
	display_error(_("This item cannot be deleted because some of it has already been delivered."));
    }
    line_start_focus();
}

//--------------------------------------------------------------------------------

function handle_new_item()
{
	global $Ajax;
	$dup=false;
	if (!check_item_data()) {
			return;
	}
	check_discs();
	foreach ($_SESSION['Items']->line_items as $order_item)
		{
			if (strcasecmp($order_item->stock_id, $_POST['stock_id']) == 0)
				{
					$dup=true;
				}
		}

	if(!list_updated('stock_id')&&input_num('price')==0&&!$dup){

		$sql = "SELECT price 
				FROM ".TB_PREF."prices_per_customer 
				WHERE debtor_no = ".db_escape($_SESSION['Items']->customer_id)."
				AND stock_id = ".db_escape($_POST['stock_id'])."
				AND sales_type_id = ".db_escape($_SESSION['Items']->sales_type)."
				AND curr_abrev = ".db_escape($_SESSION['Items']->customer_currency);
		//		display_error($sql);
		$result = db_query($sql,"could not retrieve price for ".get_customer_name($_SESSION['Items']->customer_id));
		$myrow = db_fetch_row($result);	
		$price = $myrow[0];
				if(db_num_rows($result) <= 0)
		{
			$price = get_kit_price($_POST['stock_id'],
					$_SESSION['Items']->customer_currency, 	$_SESSION['Items']->sales_type,
					$_SESSION['Items']->price_factor, get_post('OrderDate'));
		}
			
		   				$Ajax->activate('price');
		   				$_POST['price'] = $price;
		   			}


	add_to_order($_SESSION['Items'], $_POST['stock_id'], input_num('qty'),
		input_num('price'), input_num('Disc') / 100, input_num('Disc2') / 100, 
		input_num('Disc3') / 100, input_num('Disc4') / 100, input_num('Disc5') / 100, 
		input_num('Disc6') / 100, $_POST['comment']);
	$_POST['_stock_id_edit'] = $_POST['stock_id']	= "";
	     unset($_SESSION['allownegativecost']);
     unset($_SESSION['allowcredit']);
	line_start_focus();
}

//--------------------------------------------------------------------------------
function check_discs(){
		
		global $_POST;

		for($i=1;$i<=6;$i++)
		{
		
			$discs[$i]=input_num('Disc'.(($i==1)?'':$i));
			$_POST['Disc'.(($i==1)?'':$i)]=0;
		}

		$discs=array_filter($discs);
		$countx=count($discs);
	
		$x=1;
		foreach($discs as $d){

			$_POST['Disc'.(($x==1)?'':$x)]=$d;
			$x++;
		}
}
function  handle_cancel_order()
{
	global $path_to_root, $Ajax;


	if ($_SESSION['Items']->trans_type == ST_CUSTDELIVERY) {
		display_notification(_("Direct delivery entry has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Delivery"),	"/sales/sales_order_entry.php?NewDelivery=0");

	} elseif ($_SESSION['Items']->trans_type == ST_SALESINVOICE) {
		display_notification(_("Direct invoice entry has been cancelled as requested."), 1);
		submenu_option(_("Enter a New Sales Invoice"),	"/sales/sales_order_entry.php?NewInvoice=0");
	} else {
		if ($_SESSION['Items']->trans_no != 0) {
			if ($_SESSION['Items']->trans_type == ST_SALESORDER && 
				sales_order_has_deliveries(key($_SESSION['Items']->trans_no)))
				display_error(_("This order cannot be cancelled because some of it has already been invoiced or dispatched. However, the line item quantities may be modified."));
			else {
				delete_sales_order(key($_SESSION['Items']->trans_no), $_SESSION['Items']->trans_type);
				if ($_SESSION['Items']->trans_type == ST_SALESQUOTE)
				{
					display_notification(_("This sales quotation has been cancelled as requested."), 1);
					submenu_option(_("Enter a New Sales Quotation"), "/sales/sales_order_entry.php?NewQuotation=Yes");
				}
				else
				{
					display_notification(_("This sales order has been cancelled as requested."), 1);
					submenu_option(_("Enter a New Sales Order"), "/sales/sales_order_entry.php?NewOrder=Yes");
				}
			}	
		} else {
			processing_end();
			meta_forward($path_to_root.'/index.php','application=orders');
		}
	}
	$Ajax->activate('_page_body');
	processing_end();
	display_footer_exit();
}

//--------------------------------------------------------------------------------

function create_cart($type, $trans_no)
{ 
	global $Refs;

	processing_start();
	$doc_type = $type;

	if (isset($_GET['NewQuoteToSalesOrder']))
	{
		$trans_no = $_GET['NewQuoteToSalesOrder'];
		$doc = new Cart(ST_SALESQUOTE, $trans_no);
		$doc->trans_no = 0;
		$doc->trans_type = ST_SALESORDER;
		$doc->reference = $Refs->get_next($doc->trans_type);
		$doc->document_date = $doc->due_date = new_doc_date();
		$doc->Comments = _("Sales Quotation") . " # " . $trans_no;
		$_SESSION['Items'] = $doc;
	}	
	elseif($type != ST_SALESORDER && $type != ST_SALESQUOTE && $trans_no != 0) { // this is template
		$doc_type = ST_SALESORDER;

		$doc = new Cart(ST_SALESORDER, array($trans_no));
		$doc->trans_type = $type;
		$doc->trans_no = 0;
		$doc->document_date = new_doc_date();
		if ($type == ST_SALESINVOICE) {
			$doc->due_date = get_invoice_duedate($doc->customer_id, $doc->document_date);
			$doc->pos = user_pos();
			$pos = get_sales_point($doc->pos);
			$doc->cash = $pos['cash_sale'];
			if (!$pos['cash_sale'] || !$pos['credit_sale']) 
				$doc->pos = -1; // mark not editable payment type
			else
				$doc->cash = date_diff2($doc->due_date, Today(), 'd')<2;
		} else
			$doc->due_date = $doc->document_date;
		$doc->reference = $Refs->get_next($doc->trans_type);
		//$doc->Comments='';
		foreach($doc->line_items as $line_no => $line) {
			$doc->line_items[$line_no]->qty_done = 0;
		}
		$_SESSION['Items'] = $doc;
	} else
		$_SESSION['Items'] = new Cart($type,array($trans_no));
	
$_SESSION['Items']->cash = 0;	
	copy_from_cart();
	//display_error($_SESSION['Items']->trans_no[]);
}

//--------------------------------------------------------------------------------

if (isset($_POST['CancelOrder'])){
	handle_cancel_order();
	unset($_SESSION['cdetails']);
}

$id = find_submit('Delete');
if ($id!=-1)
	handle_delete_item($id);

if (isset($_POST['UpdateItem'])){
	handle_update_item();
}

if (isset($_POST['AddItem'])){
	handle_new_item();
}

if (isset($_POST['CancelItemChanges'])) {
	line_start_focus();
}

//--------------------------------------------------------------------------------
check_db_has_stock_items(_("There are no inventory items defined in the system."));

check_db_has_customer_branches(_("There are no customers, or there are no customers with branches. Please define customers and customer branches."));

if ($_SESSION['Items']->trans_type == ST_SALESINVOICE) {
	$idate = _("Invoice Date:");
	$orderitems = _("Sales Invoice Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Invoice");
	$cancelorder = _("Cancel Invoice");
	//$porder = _("Place Invoice");
	$porder = _("Create Invoice");
	unset($_SESSION['cdetails']);
} elseif ($_SESSION['Items']->trans_type == ST_CUSTDELIVERY) {
	$idate = _("Delivery Date:");
	$orderitems = _("Delivery Receipt Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Dispatch");
	$cancelorder = _("Cancel DR");
	//$porder = _("Place Delivery");
	$porder = _("Make DR");
	unset($_SESSION['cdetails']);
} elseif ($_SESSION['Items']->trans_type == ST_SALESQUOTE) {
	$idate = _("Quotation Date:");
	$orderitems = _("Sales Quotation Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Quotation");
	$cancelorder = _("Cancel Quotation");
	$porder = _("Place Quotation");
	$corder = _("Commit Quotations Changes");
	unset($_SESSION['cdetails']);
} else {
	$idate = _("Order Date:");
	$orderitems = _("Sales Order Items");
	$deliverydetails = _("Enter Delivery Details and Confirm Order");
	//$cancelorder = _("Cancel Order");
	$cancelorder = _("Cancel SO");
	//$porder = _("Place Order");
	$porder = _("Create SO");
	$corder = _("Commit Order Changes");
	unset($_SESSION['cdetails']);
}
start_form(false,false,'','form');

//display_error(var_dump($_SESSION['Items']));
//display_error(key($_SESSION['Items']->trans_no));

hidden('cart_id');
$customer_error = display_order_header($_SESSION['Items'],
	($_SESSION['Items']->any_already_delivered() == 0), $idate);
//display_error($_SESSION['Items']->trans_type);
if ($customer_error == "") {
	start_table("$table_style width=80%", 10);
	echo "<tr><td>";
	display_order_summary($orderitems, $_SESSION['Items'], true);
	echo "</td></tr>";
	echo "<tr><td>";
	display_delivery_details($_SESSION['Items']);
	echo "</td></tr>";
	end_table(1);

	if ($_SESSION['Items']->trans_no == 0) {

		submit_center_first('ProcessOrder', $porder,
		    _('Check entered data and save document'), false);
		submit_js_confirm('CancelOrder', _('You are about to void this Document.\nDo you want to continue?'));
	} else {
		submit_center_first('ProcessOrder', $corder,
		    _('Validate changes and update document'), false);
	}

	submit_center_last('CancelOrder', $cancelorder,
	   _('Cancels document entry or removes sales order when editing an old document'));
} else {
	display_error($customer_error);
}
end_form();
end_page();

?>
<!--
<script>
	$(document).ready(function(){
		
		jQuery.prompt.setDefaults({
			//prefix: 'myPrompt',
			show: 'slideDown'
			,top: '40%'
		});
		
		$(this).keydown(function(e) {
			// ESCAPE key pressed
			if (e.keyCode == 27) {
			   history.back();
			}
		});
		
		$('#ProcessOrder').click(function(){		
			var errpro = 'Invalid supervisor user account. Please try again.';
			
			var nd = $.getUrlVar('NewDelivery');
			var ni = $.getUrlVar('NewInvoice');
			
			
			if($('input[name="noti_"]').val()==0 || nd!='' || ni!=''){
			
				function post_form(val,ev,f){
					if(f.uname!='' && f.passwd!=''){
						// $.prompt(f.noti_);
						//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
						$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'noti_':'1'},
							function(ev){
								// $.prompt(ev);
								if(ev==true){
									$.prompt('Action approved. You can now proceed..');
									// alert('asdasd');
									$('input[name="noti_"]').attr("value",1);
									// location.reload();
									//return true;
									//alert('sadasd');
									$("input[name='noti_']").val(1);
									//var noti_ = 1;
								}
								// }else
									// $.prompt(errpro);
									//ev.preventDefault();
							});
						//$.prompt('Action approved. You can now proceed..');
					}else
						$.prompt(errpro,{
						
						});
				}
				var txt = '<table>'+
					  '<tr>Items below minimum inventory level. Enter the supervisor\'s username and password to proceed.<br></tr>'+
					  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
					  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
					  '</table>';
					  
				$.prompt(txt);
				$.prompt(txt,{
					// opacity: 0.8,
					buttons: { Ok:true, Cancel:false },
					callback: post_form,//,
					//, prefix:'jqismooth'
					//,top: 300
				});
			}
			
			event.preventDefault();
		});
	});
</script>-->