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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/purchasing/includes/po_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/includes/db/audit_trail_db.inc");

$js = '';

#####################
add_js_ufile($path_to_root.'/js/thickbox.js');
echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />
";
#####################

if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

####################
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
					$.post('confirm2.php',{ 'uname':f.uname,'passwd':f.passwd,'ty':ty},
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
####################
	
if (isset($_GET['ModifyOrderNumber'])) 
{
	page(_($help_context = "Modify Purchase Order"), false, false, "", $js);
} 
else 
{
	page(_($help_context = "Purchase Order Entry"), false, false, "", $js);
}

//---------------------------------------------------------------------------------------------------

check_db_has_suppliers(_("There are no suppliers defined in the system."));

check_db_has_purchasable_items(_("There are no purchasable inventory items defined in the system."));

//---------------------------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$order_no = $_GET['AddedID'];
	$trans_type = ST_PURCHORDER;	

	if (!isset($_GET['Updated']))
		display_notification_centered(_("Purchase Order has been entered"));
	else
		display_notification_centered(_("Purchase Order has been updated"));

	hyperlink_params($_SERVER['PHP_SELF'], _("Edit this Purchase Order"), "ModifyOrderNumber=".$order_no);
	br();
	display_note(get_trans_view_str($trans_type, $order_no, _("&View this order")), 0, 1);

	display_note(print_document_link($order_no, _("&Print This Order"), true, $trans_type), 0, 1);

	//display_note(print_document_link($order_no, _("&Email This Order"), true, $trans_type, false, "printlink", "", 1));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter &Another Purchase Order"), "NewOrder=yes");
	
	// hyperlink_no_params($path_to_root."/purchasing/inquiry/po_search.php", _("Select An &Outstanding Purchase Order"));
	
	/*echo "
		<p>
		<center>
		<a href='../sales/customer_del_so.php?OrderNumber=$order_no&type=4&view=0&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Purchase Order</a>
		</center>
	";*/
	
	display_footer_exit();	
}
//--------------------------------------------------------------------------------------------------

function copy_from_cart()
{
	//display_error('copy from cart '.$_POST['StkLocation'].' - '.$_SESSION['PO']->Location);
	$_POST['supplier_id'] = $_SESSION['PO']->supplier_id;
	$_POST['OrderDate'] = $_SESSION['PO']->orig_order_date;
    $_POST['Requisition'] = $_SESSION['PO']->requisition_no;
    $_POST['ref'] = $_SESSION['PO']->reference;
	$_POST['Comments'] = $_SESSION['PO']->Comments;
    $_POST['StkLocation'] = $_SESSION['PO']->Location;
    $_POST['delivery_address'] = $_SESSION['PO']->delivery_address;
}

function copy_to_cart()
{
	//display_error('copy to cart '.$_POST['StkLocation'].' - '.$_SESSION['PO']->Location);
	$_SESSION['PO']->supplier_id = $_POST['supplier_id'];
	$_SESSION['PO']->orig_order_date = $_POST['OrderDate'];
	$_SESSION['PO']->reference = $_POST['ref'];
	$_SESSION['PO']->requisition_no = $_POST['Requisition'];
	$_SESSION['PO']->Comments = $_POST['Comments'];
	$_SESSION['PO']->Location = $_POST['StkLocation'];
	$_SESSION['PO']->delivery_address = $_POST['delivery_address'];
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_stock_id_edit');
}
//--------------------------------------------------------------------------------------------------

function unset_form_variables() {
	unset($_POST['stock_id']);
    unset($_POST['qty']);
    unset($_POST['price']);
    unset($_POST['req_del_date']);
}

//---------------------------------------------------------------------------------------------------

function handle_delete_item($line_no)
{
	//global $_iprice;
	if($_SESSION['PO']->some_already_received($line_no) == 0)
	{
		$_SESSION['PO']->actions[] = new action_details('deleted an item', $_SESSION['PO']->line_items[$line_no]->stock_id, 
				$_SESSION['PO']->line_items[$line_no]->quantity, $_SESSION['PO']->line_items[$line_no]->price, 
				0,0,0, $_SESSION['PO']->line_items[$line_no]->req_del_date,
				$_SESSION['PO']->line_items[$line_no]->item_description);
				
				//display_error(print_r($_SESSION['PO']->actions));
				//checkPOLineItems($_iprice);
				
		$_SESSION['PO']->remove_from_order($line_no);
		unset_form_variables();
	} 
	else 
	{
		display_error(_("This item cannot be deleted because some of it has already been received."));
	}	
    line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function handle_cancel_po()
{
	global $path_to_root;
	
	//need to check that not already dispatched or invoiced by the supplier
	if(($_SESSION['PO']->order_no != 0) && 
		$_SESSION['PO']->any_already_received() == 1)
	{
		display_error(_("This order cannot be cancelled because some of it has already been received.") 
			. "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
		return;
	}
	
	if($_SESSION['PO']->order_no != 0)
	{
		delete_po($_SESSION['PO']->order_no);
	} else {
		unset($_SESSION['PO']);
		meta_forward($path_to_root.'/index.php','application=AP');
	}

	$_SESSION['PO']->clear_items();
	$_SESSION['PO'] = new purch_order;

	display_notification(_("This purchase order has been cancelled."));

	hyperlink_params($path_to_root . "/purchasing/po_entry_items.php", _("Enter a new purchase order"), "NewOrder=Yes");
	echo "<br>";

	end_page();
	exit;
}

//---------------------------------------------------------------------------------------------------

function check_data()
{
	$dec = get_qty_dec($_POST['stock_id']);
	$min = 1 / pow(10, $dec);
    if (!check_num('qty',$min))
    {
    	$min = number_format2($min, $dec);
	   	display_error(_("The quantity of the order item must be numeric and not less than ").$min);
		set_focus('qty');
	   	return false;
    }

    if (!check_num('price', 0))
    {
	   	display_error(_("The price entered must be numeric and not less than zero."));
		set_focus('price');
	   	return false;	   
    }
    if (!is_date($_POST['req_del_date'])){
    		display_error(_("The date entered is in an invalid format."));
		set_focus('req_del_date');
   		return false;    	 
    }
     
    return true;	
}

//---------------------------------------------------------------------------------------------------

function handle_update_item()
{
		//global $_iprice;
	$allow_update = check_data(); 

	if ($allow_update)
	{
		if ($_SESSION['PO']->line_items[$_POST['line_no']]->qty_inv > input_num('qty') ||
			$_SESSION['PO']->line_items[$_POST['line_no']]->qty_received > input_num('qty'))
		{
			display_error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received.  This is prohibited.") .
				"<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
			set_focus('qty');
			return;
		}
	
		$_SESSION['PO']->actions[] = new action_details('updated an item', $_SESSION['PO']->line_items[$_POST['line_no']]->stock_id, 
			
			($_SESSION['PO']->line_items[$_POST['line_no']]->quantity - input_num('qty') != 0 ?
					'from '.$_SESSION['PO']->line_items[$_POST['line_no']]->quantity.' to '.input_num('qty')
					: ''),
			
			($_SESSION['PO']->line_items[$_POST['line_no']]->price - input_num('price') != 0 ?
					'from '.$_SESSION['PO']->line_items[$_POST['line_no']]->price.' to '.input_num('price')
					: ''),
			
			0,0,0,
			
			($_SESSION['PO']->line_items[$_POST['line_no']]->req_del_date != $_POST['req_del_date'] ?
					'from '.$_SESSION['PO']->line_items[$_POST['line_no']]->req_del_date.' to '.$_POST['req_del_date']
					: ''),
				
			($_SESSION['PO']->line_items[$_POST['line_no']]->req_del_date != $_POST['req_del_date'] ?
					'from '.$_SESSION['PO']->line_items[$_POST['line_no']]->req_del_date.' to '.$_POST['req_del_date']
					: ''));
			
			 // $_POST['item_description']);
		
		//display_error(print_r($_SESSION['PO']->action));
		//checkPOLineItems($_iprice);
		
		$_SESSION['PO']->update_order_item($_POST['line_no'], input_num('qty'), input_num('price'),$_POST['req_del_date']);
		unset_form_variables();
	}	
    line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function handle_add_new_item()
{
		global $Ajax;
	$allow_update = check_data();
	$dup=false;
	if ($allow_update == true)
	{ 
		if (count($_SESSION['PO']->line_items) > 0)
		{
		    foreach ($_SESSION['PO']->line_items as $order_item) 
		    {

    			/* do a loop round the items on the order to see that the item
    			is not already on this order */
   			    if (($order_item->stock_id == $_POST['stock_id']) && 
   			    	($order_item->Deleted == false)) 
   			    {
				  	$dup = true;
			    }
		    }  /*end of the foreach loop to look for pre-existing items of the same code */
		}

		if ($allow_update == true)
		{
		   	$sql = "SELECT description, units, mb_flag
				FROM ".TB_PREF."stock_master WHERE stock_id = ".db_escape($_POST['stock_id']);

		    $result = db_query($sql,"The stock details for " . $_POST['stock_id'] . " could not be retrieved");

		    if (db_num_rows($result) == 0)
		    {
				$allow_update = false;
		    }		    

			if ($allow_update)
		   	{
		   			if(!list_updated('stock_id')&&input_num('price')==0&&!$dup){
		   				$Ajax->activate('price');
		   				$_POST['price'] = get_purchase_price($_POST['supplier_id'], $_POST['stock_id']);
		   			}
				$myrow = db_fetch($result);
				$_SESSION['PO']->add_to_order ($_POST['line_no'], $_POST['stock_id'], input_num('qty'), 
					$myrow["description"], input_num('price'), $myrow["units"],
					$_POST['req_del_date'], 0, 0);
					
				$_SESSION['PO']->actions[] = new action_details('added to cart', $_POST['stock_id'], input_num('qty'), 
					input_num('price'), 0, 0, 0, $_POST['req_del_date']);

					//display_error(print_r($_SESSION['PO']->actions));
					
				unset_form_variables();
				$_POST['stock_id']	= "";
	   		} 
	   		else 
	   		{
			     display_error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
		   	}

		} /* end of if not already on the order and allow input was true*/
    }
    // display_error($_SESSION['PO']->get_items_total());
	line_start_focus();
}

//---------------------------------------------------------------------------------------------------

function checkPurchaseOrderDetails(&$stats, &$fields, &$_iprice, &$podetails){
	if($_POST['OrderDate'] != $_SESSION['PO']->orig_order_date && (!$_SESSION['pdetails'] || $_SESSION['pdetails']==0)){
		$fields .= "Order Date, ";
		$stats = false;
	}
	//display_error($_POST['Requisition'].' - '.$_SESSION['PO']->requisition_no);
	if($_POST['Requisition'] != $_SESSION['PO']->requisition_no && (!$_SESSION['pdetails'] || $_SESSION['pdetails']==0)){
		$fields .= "Suppliers Reference, ";
		$stats = false;
	}
	//display_error($podetails['into_stock_location'].' - '.$_SESSION['PO']->Location);
	if($_POST['StkLocation'] != $podetails['into_stock_location'] && (!$_SESSION['pdetails'] || $_SESSION['pdetails']==0)){
		$fields .= "Receive Into, ";
		$stats = false;
	}
	if($_POST['delivery_address'] != $_SESSION['PO']->delivery_address && (!$_SESSION['pdetails'] || $_SESSION['pdetails']==0)){
		$fields .= "Delivery to, ";
		$stats = false;
	}
	
	if(isset($fields) && $fields != ''){
		$fields = substr($fields, 0, (strlen($fields)-2));
	}	
	
	//display_error(print_r($_SESSION['PO']->actions));
	
	$action_details = '';
	if ($_SESSION['PO']->actions != '' && (!$_SESSION['pdetails'] || $_SESSION['pdetails']==0))
	{
		foreach ($_SESSION['PO']->actions as $line_no=>$action_det)
		{
			
			$action_details .= '  <hr> - ';				
				
			$action_details .= $action_det->act.' Item:'.$action_det->item_description;
			
			if($action_det->act){
				$stats = false;
			}
			
			if ($action_det->quantity != ''){
				$action_details .= ' | Quantity:'.$action_det->quantity;
				
			}
			if ($action_det->price != ''){
				$action_details .= ' | Price:'.$action_det->price;
				
			}
			if ($action_det->discount_percent > 0){
				$action_details .=' | Req. Del. Date:'.$action_det->comment;
				
			}
		}
	}
	
	$_iprice = $action_details;
}

function can_commit()
{
	global $Refs;
	global $fields;
	global $_iprice;
	
	$_SESSION['fields_change']=0;
	
	$stats = true;
	
	$podetails = getPurchDetail($_SESSION['PO']->order_no);
	//display_error($_SESSION['PO']->orig_order_date.' - '.$_POST['OrderDate']);
	//display_error('You are about to save this transaction.');
	
	//display_error(count($_SESSION['PO']->line_items));
	
	
	//return false;
	
	if($podetails['is_approve']){
		checkPurchaseOrderDetails($stats, $fields, $_iprice, $podetails);
	
	$can_po_edit = "SELECT allow_po_editing as allow FROM ".TB_PREF."company";
	$can_po_edit = db_query($can_po_edit);
	$is = db_fetch($can_po_edit);
	
		if($is['allow'])
			return $is['allow'];
	
		if($stats == false || $is['allow']){
			$_SESSION['fields_change']=1;
				display_error("<a id='limitLink' onclick='opemprompt_n(8,\"".$fields."\", \"".$_iprice."\")' style='cursor:pointer'>Click here to enable customer to transact</a>");	
			
			return false;
		}else{
			$fields = 0;
		}
	}	
	//return false;
	################################################3
	
	if (!get_post('supplier_id')) 
	{
		display_error(_("There is no supplier selected."));
		set_focus('supplier_id');
		return false;
	} 
	
	if (!is_date($_POST['OrderDate'])) 
	{
		display_error(_("The entered order date is invalid."));
		set_focus('OrderDate');
		return false;
	} 
	
	if (!$_SESSION['PO']->order_no) 
	{
    	if (!$Refs->is_valid(get_post('ref'))) 
    	{
    		display_error(_("There is no reference entered for this purchase order."));
			set_focus('ref');
    		return false;
    	} 
    	
    	if (!is_new_reference(get_post('ref'), ST_PURCHORDER)) 
    	{
    		display_error(_("The entered reference is already in use."));
			set_focus('ref');
    		return false;
    	}
	}
	
	if (get_post('delivery_address') == '')
	{
		display_error(_("There is no delivery address specified."));
		set_focus('delivery_address');
		return false;
	} 
	
	if (get_post('StkLocation') == '')
	{
		display_error(_("There is no location specified to move any items into."));
		set_focus('StkLocation');
		return false;
	} 
	
	if ($_SESSION['PO']->order_has_items() == false)
	{
     	display_error (_("The order cannot be placed because there are no lines entered on this order."));
     	return false;
	}
	if($_SESSION['PO']->get_items_total()<=0){
		display_error("The order total must be greater than 0.");
		return false;
	}
		
	return true;
}

//---------------------------------------------------------------------------------------------------

function handle_commit_order()
{

	if (can_commit())
	{
		copy_to_cart();

		if ($_SESSION['PO']->order_no == 0)
		{ 
			
			/*its a new order to be inserted */
			$order_no = add_po($_SESSION['PO']);
			new_doc_date($_SESSION['PO']->orig_order_date); 
			unset($_SESSION['PO']);
			 
        	meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no");	

		} 
		else 
		{ 

			/*its an existing order need to update the old order info */
			$order_no = update_po($_SESSION['PO']);
			
			unset($_SESSION['PO']);
			
        	meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no&Updated=1");	
		}
	}	
}
//---------------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['Commit']))
{
	//$_SESSION['pdetails'] = 0;
	handle_commit_order();
}
if (isset($_POST['UpdateLine']))
	handle_update_item();

if (isset($_POST['EnterLine']))
	handle_add_new_item();

if (isset($_POST['CancelOrder'])){ 
	unset($_SESSION['pdetails']);
	handle_cancel_po();
}
if (isset($_POST['CancelUpdate'])){
	unset($_SESSION['pdetails']);
	unset_form_variables();
}
if (isset($_GET['ModifyOrderNumber']) && $_GET['ModifyOrderNumber'] != "")
{
	//unset($_SESSION['pdetails']);
	create_new_po();
	
	$_SESSION['PO']->order_no = $_GET['ModifyOrderNumber'];	

	/*read in all the selected order into the Items cart  */
	read_po($_SESSION['PO']->order_no, $_SESSION['PO']);
	
	copy_from_cart();
	unset($_SESSION['pdetails']);
}

if (isset($_POST['CancelUpdate']) || isset($_POST['UpdateLine'])) {
	line_start_focus();
}

if (isset($_GET['NewOrder'])){
	unset($_SESSION['pdetails']);
	//global $string = '';
	create_new_po();
}
//---------------------------------------------------------------------------------------------------

start_form();

//display_error($_SESSION['PO']->order_no);

display_po_header($_SESSION['PO']);
echo "<br>";

display_po_items($_SESSION['PO']);

start_table($table_style2);
textarea_row(_("Memo:"), 'Comments', null, 70, 4);

end_table(1);

div_start('controls', 'items_table');
if ($_SESSION['PO']->order_has_items()) 
{
	if ($_SESSION['PO']->order_no)
		submit_center_first('Commit', _("Update PO"), '', false);
	else
		submit_center_first('Commit', _("Create PO"), '', false);
	submit_center_last('CancelOrder', _("Cancel PO")); 	
}
else
	submit_center('CancelOrder', _("Cancel PO"), true, false, 'cancel');
div_end();
//---------------------------------------------------------------------------------------------------

end_form();
end_page();
?>



<script>
	$(document).ready(function(){
		jQuery.prompt.setDefaults({
			show: 'slideDown'
			,top: 300
		});
		$(this).keydown(function(e) {
			// ESCAPE key pressed
			if (e.keyCode == 27) {
			   history.back();
			}
		});
		// $('div[class="jqicontainer"]').find('uname').focus();
		var mes = '<br><font color=red>Invalid supervisor user account.</font>';
		var txt = '<table>'+
				  '<tr>Please enter account for approval.<br></tr>'+
				  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
				  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
				  '</table>';
				  
		function pro_(a,txt){
			if(a===null)
			$.prompt(txt,{
				buttons: { Ok:true, Cancel:false },
				callback: post_form,//,
			});
		}
		//Prompt when page is loaded...
		var or_no = $.getUrlVar('ModifyOrderNumber');
			
		// if(or_no)
		// write(pro_(null,txt));
		
		function post_form(val,ev,f){
			if(val==true){
				if(f.uname!='' && f.passwd!=''){
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd },
						function(ev){
							 // $.prompt(ev);
							if(ev==true){
								$.prompt('Action approved. You can now proceed..');
							}else{
								txt = '<table>'+
										  '<tr>Please enter account for approval.<br></tr>'+
										  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
										  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
										  '</table>'+
										  '<br><font color=red>Invalid supervisor user account.</font>';
								
								$('input[name="logged_uname"]').attr('value',$);
								write(pro_(null,txt));
							}
						});
				}
			}else{
				history.back();
			}
		}			
		event.preventDefault();
	});
</script>