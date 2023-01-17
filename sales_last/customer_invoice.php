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
//---------------------------------------------------------------------------
//
//	Entry/Modify Sales Invoice against single delivery
//	Entry/Modify Batch Sales Invoice against batch of deliveries
//
$page_security = 'SA_SALESINVOICE';
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

add_js_ufile($path_to_root.'/js/thickbox.js');
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

//	$(document).ready(function(){
		
		// $('#customer_id').change(function(){
			// post_form();
		// });
		
		//********************Default********************
		var sys_type = 'SA_SALESORDER';
		
		jQuery.prompt.setDefaults({
			show: 'slideDown'
			,top: 350
		});
		
		var txt = '<table>'+
					  '<tr>The selected customer credit limit exceeds. Please enter supervisor user account for approval.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
		var txt_ = '<table>'+
					  '<tr>Items below minimum inventory level. Enter the supervisor\'s username and password to proceed.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
					  
		
		var hid_chk_credit = $('input[name=\"hid_chk_credit\"]').val();
		var hid_debtor_no = $('input[name=\"debtor_no11\"]').val();
		var errpro = 'Invalid supervisor user account. Please try again.';
		var sapp = 'Action approved! You can now proceed with the transaction.';
		//alert(hid_debtor_no);
		//***********************************************
		
		function post_form(val,ev,f){
		//alert(val+ ' - '+ev+ ' - '+f);
			if(val==true)
				if(f.uname!='' && f.passwd!=''){
					//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'noti_':1 },
						function(ev){
							// $.prompt(ev);
							if(ev==true){
								// $.prompt(Date());
								$.prompt(sapp);
								//hid_chk_credit==0;
								$('input[name=\"hid_chk_credit\"]').attr('value',0);
																
								$(\"input[name='credit_']\").val(1);
								$(\"input[name='hid_debtor_no']\").val(hid_debtor_no);
								
								$.post('confirm2.php',{ 'credit_':1, 'hid_debtor_no':hid_debtor_no },
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
								//$.prompt(errpro+'. Reattempt in 3 seconds.');
								//window.setTimeout('location.reload()', 3000);
								$.prompt(errpro);
							}
						});
				}else
					$.prompt(errpro);
		}
		
		function post_form_(val,ev,f){
		//alert(val+ ' - '+ev+ ' - '+f);
			if(val==true)
				if(f.uname!='' && f.passwd!=''){
					//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd},
						function(ev){
							// $.prompt(ev);
							if(ev==true){
								// $.prompt(Date());
								$.prompt(sapp);
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
								//$.prompt(errpro+'. Reattempt in 3 seconds.');
								//window.setTimeout('location.reload()', 3000);
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
			
		function openprompt(){
			$.prompt(txt,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form
				});
		}
		function openprompt_(){
			$.prompt(txt_,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form_
				});
		}
//	});
	
	
";

if (isset($_GET['ModifyInvoice'])) {
	$_SESSION['page_title'] = sprintf(_("Modifying Sales Invoice # %s.") ,$_GET['ModifyInvoice']);
	$help_context = "Modifying Sales Invoice";
} elseif (isset($_GET['DeliveryNumber'])) {
	//$_SESSION['page_title'] = _($help_context = "Issue an Invoice for Delivery Note");
	$_SESSION['page_title'] = _($help_context = "Issue an Invoice for Delivery Receipts");
} elseif (isset($_GET['BatchInvoice'])) {
	//$_SESSION['page_title'] = _($help_context = "Issue Batch Invoice for Delivery Notes");
	$_SESSION['page_title'] = _($help_context = "Issue Batch Invoice for Delivery Receipts");
}

page($_SESSION['page_title'], false, false, "", $js);

//-----------------------------------------------------------------------------
check_edit_conflicts();

$remove = find_submit('RemoveDN');

if (isset($_GET['AddedID'])) {

	$invoice_no = $_GET['AddedID'];
	$trans_type = ST_SALESINVOICE;

	display_notification(_("Selected deliveries has been processed"), true);

	display_note(get_customer_trans_view_str($trans_type, $invoice_no, _("&View This Invoice")), 0, 1);

	display_note(print_document_link($invoice_no, _("&Print This Invoice"), true, ST_SALESINVOICE));
	//display_note(print_document_link($invoice_no, _("&Email This Invoice"), true, ST_SALESINVOICE, false, "printlink", "", 1),1);

	display_note(get_gl_view_str($trans_type, $invoice_no, _("View the GL &Journal Entries for this Invoice")),1);

	hyperlink_params("$path_to_root/sales/inquiry/sales_deliveries_view.php", _("Select Another &Delivery For Invoicing"), "OutstandingOnly=1");

/*	echo "
		<p>
		<center>
		<a href='customer_del_so.php?OrderNumber=$invoice_no&view=0&type=3&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Invoice</a>
		</center>
	";
	*/
	display_footer_exit();

} elseif (isset($_GET['UpdatedID']))  {

	$invoice_no = $_GET['UpdatedID'];

	display_notification_centered(sprintf(_('Sales Invoice # %s has been updated.'),$invoice_no));

	display_note(get_trans_view_str(ST_SALESINVOICE, $invoice_no, _("&View This Invoice")));
	echo '<br>';
	display_note(print_document_link($invoice_no, _("&Print This Invoice"), true, ST_SALESINVOICE));

	hyperlink_no_params($path_to_root . "/sales/inquiry/customer_inquiry.php", _("Select A Different &Invoice to Modify"));
/*
	echo "
		<p>
		<center>
		<a href='customer_del_so.php?OrderNumber=$invoice_no&view=0&type=3&KeepThis=true&TB_iframe=true&height=280&width=300' class='thickbox'>Void This Invoice</a>
		</center>
	";*/
	
	display_footer_exit();

} 
// elseif (isset($_GET['RemoveDN'])) {

	// for($line_no = 0; $line_no < count($_SESSION['Items']->line_items); $line_no++) {
		// $line = &$_SESSION['Items']->line_items[$line_no];
		// if ($line->src_no == $_GET['RemoveDN']) {
			// $line->quantity = $line->qty_done;
			// $line->qty_dispatched=0;
		// }
	// }
	// unset($line);

    // // Remove also src_doc delivery note
    // $sources = &$_SESSION['Items']->src_docs;
    // unset($sources[$_GET['RemoveDN']]);
// }
elseif ($remove != -1) {
	// display_error($remove);
	
	$_SESSION['Items']->remove_from_cart($remove);
	
	$Ajax->activate('_page_body');
}

//-----------------------------------------------------------------------------

if ( (isset($_GET['DeliveryNumber']) && ($_GET['DeliveryNumber'] > 0) )
|| isset($_GET['BatchInvoice'])) {

	processing_start();

	if (isset($_GET['BatchInvoice'])) {
		$src = $_SESSION['DeliveryBatch'];
		unset($_SESSION['DeliveryBatch']);
	} else {
		$src = array($_GET['DeliveryNumber']);
	}
	/*read in all the selected deliveries into the Items cart  */
	$dn = new Cart(ST_CUSTDELIVERY, $src, true);

	if ($dn->count_items() == 0) {
		hyperlink_params($path_to_root . "/sales/inquiry/sales_deliveries_view.php",
			_("Select a different delivery to invoice"), "OutstandingOnly=1");
		die ("<br><b>" . _("There are no delivered items with a quantity left to invoice. There is nothing left to invoice.") . "</b>");
	}

	$dn->trans_type = ST_SALESINVOICE;
	$dn->src_docs = $dn->trans_no;
	$dn->trans_no = 0;
	$dn->reference = $Refs->get_next(ST_SALESINVOICE);
	$dn->due_date = get_invoice_duedate2($dn->p_terms, $dn->document_date);

	$_SESSION['Items'] = $dn;
	$_SESSION['logged_uname'] = '';
	copy_from_cart();

} elseif (isset($_GET['ModifyInvoice']) && $_GET['ModifyInvoice'] > 0) {

	if ( get_parent_trans(ST_SALESINVOICE, $_GET['ModifyInvoice']) == 0) { // 1.xx compatibility hack
		echo"<center><br><b>" . _("There are no delivery receipts for this invoice.<br>
		Most likely this invoice was created in <!--//Front Accounting//-->ARIA version prior to 2.0
		and therefore can not be modified.") . "</b></center>";
		display_footer_exit();
	}
	processing_start();
	$_SESSION['Items'] = new Cart(ST_SALESINVOICE, $_GET['ModifyInvoice']);
	$_SESSION['Items_old'] = new Cart(ST_SALESINVOICE,$_GET['ModifyInvoice']);
	$_SESSION['logged_uname'] == '';

	if ($_SESSION['Items']->count_items() == 0) {
		echo"<center><br><b>" . _("All quantities on this invoice has been credited. There is nothing to modify on this invoice") . "</b></center>";
		display_footer_exit();
	}
	copy_from_cart();
} elseif (!processing_active()) {
	/* This page can only be called with a delivery for invoicing or invoice no for edit */
	display_error(_("This page can only be opened after delivery selection. Please select delivery to invoicing first."));

	hyperlink_no_params("$path_to_root/sales/inquiry/sales_deliveries_view.php", _("Select Delivery to Invoice"));

	end_page();
	exit;
} elseif (!check_quantities()) {
	display_error(_("Selected quantity cannot be less than quantity credited nor more than quantity not invoiced yet."));
}
if (isset($_POST['Update'])) {
	copy_to_cart();
	$Ajax->activate('Items');
}
if (isset($_POST['_InvoiceDate_changed'])) {
	$_POST['due_date'] = get_invoice_duedate($_SESSION['Items']->customer_id, 
		$_POST['InvoiceDate']);
	$Ajax->activate('due_date');
}

//-----------------------------------------------------------------------------
function check_quantities()
{
	$ok =1;
	
	$_SESSION['Items']->actions = array();
	
	$others = '';
	
	$others .= ($_SESSION['Items_old']->freight_cost != input_num('ChargeFreightCost') ?
					' | Shipping Cost from '.$_SESSION['Items_old']->freight_cost.' to '.input_num('ChargeFreightCost')
					: '');
	$others .= ($_SESSION['Items_old']->discount1 != input_num('discount1') ?
					' | Discount 1 from '.$_SESSION['Items_old']->discount1.' to '.input_num('discount1')
					: '');
	$others .= ($_SESSION['Items_old']->discount2 != input_num('discount2') ?
					' | Discount 2 from '.$_SESSION['Items_old']->discount2.' to '.input_num('discount2')
					: '');
	$others .= ($_SESSION['Items_old']->discount3 != input_num('discount3') ?
					' | Discount 3 from '.$_SESSION['Items_old']->discount3.' to '.input_num('discount3')
					: '');
	$others .= ($_SESSION['Items_old']->discount4 != input_num('discount4') ?
					' | Discount 4 from '.$_SESSION['Items_old']->discount4.' to '.input_num('discount4')
					: '');
	$others .= ($_SESSION['Items_old']->discount5 != input_num('discount5') ?
					' | Discount 5 from '.$_SESSION['Items_old']->discount5.' to '.input_num('discount5')
					: '');
	
	/*if ($others != '')
		$_SESSION['Items']->actions[] = new action_details('', $_SESSION['Items']->line_items[$line]->stock_id,'','','','','','','',$others);*/
	
	foreach ($_SESSION['Items']->line_items as $line_no=>$itm) {
	
		if (isset($_POST['Line'.$line_no])) {
			if($_SESSION['Items']->trans_no) {
				$min = $itm->qty_done;
				$max = $itm->quantity;
			} else {
				$min = 0;
				$max = $itm->quantity - $itm->qty_done;
			}
			if (check_num('Line'.$line_no, $min, $max)) {
				$_SESSION['Items']->line_items[$line_no]->qty_dispatched =
				    input_num('Line'.$line_no);
			}
			else {
				$ok = 0;
			}
				
		}

		if (isset($_POST['Line'.$line_no.'Desc'])) {
			$line_desc = $_POST['Line'.$line_no.'Desc'];
			if (strlen($line_desc) > 0) {
				$_SESSION['Items']->line_items[$line_no]->item_description = $line_desc;
			}
		}
		
		$_SESSION['Items']->actions[] = new action_details('updated an item', $_SESSION['Items']->line_items[$line_no]->stock_id,
		
		($_SESSION['Items_old']->line_items[$line_no]->qty_dispatched - $_SESSION['Items']->line_items[$line_no]->qty_dispatched!= 0 ?
				'from '.$_SESSION['Items_old']->line_items[$line_no]->qty_dispatched.' to '.$_SESSION['Items']->line_items[$line_no]->qty_dispatched : ''),
		'','','','','', 
			
			($_SESSION['Items_old']->line_items[$line_no]->item_description != $_SESSION['Items']->line_items[$line_no]->item_description ?
				'from '.$_SESSION['Items_old']->line_items[$line_no]->item_description.' to '.$_SESSION['Items']->line_items[$line_no]->item_description
				: '')
		
		);
	}
	
 return $ok;
}

function set_delivery_shipping_sum($delivery_notes) 
{
    
    $shipping = 0;
    
    foreach($delivery_notes as $delivery_num) 
    {
        $myrow = get_customer_trans($delivery_num, 13);
        //$branch = get_branch($myrow["branch_code"]);
        //$sales_order = get_sales_order_header($myrow["order_"]);
        
        //$shipping += $sales_order['freight_cost'];
        $shipping += $myrow['ov_freight'];
    }
    $_POST['ChargeFreightCost'] = price_format($shipping);
}


function copy_to_cart()
{
	$cart = &$_SESSION['Items'];
	$cart->ship_via = $_POST['ship_via'];
	$cart->freight_cost = input_num('ChargeFreightCost');
	$cart->document_date =  $_POST['InvoiceDate'];
	$cart->due_date =  $_POST['due_date'];
	$cart->Comments = $_POST['Comments'];
	
	
	if ($_SESSION['Items']->trans_no == 0)
		$cart->reference = $_POST['ref'];
}
//-----------------------------------------------------------------------------

function copy_from_cart()
{
	$cart = &$_SESSION['Items'];
	$_POST['ship_via'] = $cart->ship_via;
	$_POST['ChargeFreightCost'] = price_format($cart->freight_cost);

	$_POST['InvoiceDate']= $cart->document_date;
	$_POST['due_date'] = $cart->due_date;
	$_POST['Comments']= $cart->Comments;
	$_POST['cart_id'] = $cart->cart_id;
	$_POST['ref'] = $cart->reference;
}

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
//display_error($sql);
	$result = db_query($sql, "could not get allow_credit");

	$row = db_fetch_row($result);
	return $row[0];
}

function check_data()
{
	global $Refs;
	
	copy_to_cart();
	if ($_SESSION['Items']->get_total_lines() > $_SESSION['Items']->max_invoice_lines)
	{
		display_error('This transaction will exceed '.$_SESSION['Items']->max_invoice_lines.' lines for the invoice printout. 
		Please create another transaction for the other items. Line count:'.$_SESSION['Items']->get_total_lines());
   		return false;
	}

	if (!isset($_POST['InvoiceDate']) || !is_date($_POST['InvoiceDate'])) {
		display_error(_("The entered invoice date is invalid."));
		set_focus('InvoiceDate');
		return false;
	}

	if (!is_date_in_fiscalyear($_POST['InvoiceDate'])) {
		display_error(_("The entered invoice date is not in fiscal year."));
		set_focus('InvoiceDate');
		return false;
	}

	if (!isset($_POST['due_date']) || !is_date($_POST['due_date']))	{
		display_error(_("The entered invoice due date is invalid."));
		set_focus('due_date');
		return false;
	}

	if ($_SESSION['Items']->trans_no == 0) {
		if (!$Refs->is_valid($_POST['ref'])) {
			display_error(_("You must enter a reference."));
			set_focus('ref');
			return false;
		}

		if (!is_new_reference($_POST['ref'], 10)) {
			display_error(_("The entered reference is already in use."));
			set_focus('ref');
			return false;
		}
	}

	if ($_POST['ChargeFreightCost'] == "") {
		$_POST['ChargeFreightCost'] = price_format(0);
	}

	if (!check_num('ChargeFreightCost', 0)) {
		display_error(_("The entered shipping value is not numeric."));
		set_focus('ChargeFreightCost');
		return false;
	}

	if ($_SESSION['Items']->has_items_dispatch() == 0 && input_num('ChargeFreightCost') == 0) {
		display_error(_("There are no item quantities on this invoice."));
		return false;
	}

	if (!check_quantities()) {
		display_error(_("Selected quantity cannot be less than quantity credited nor more than quantity not invoiced yet."));
		return false;
	}

	$chk_credit = 0;
	
	$myrow = get_customer_to_order($_SESSION['Items']->customer_id);
	$cart_total=$_SESSION['Items']->get_items_total();

	//display_error($cart_total);
	$date = Today();
	
	$res = get_transactions11($_SESSION['Items']->customer_id,$date );
	
	$total_inv = 0;
	$total_rec = 0;
	$total_record = 0;

	while ($trans = db_fetch($res))
	{
		if ($trans['type'] == 10)
			$total_inv += $trans["TotalAmount"] - $trans["Allocated"];
		else
			$total_rec += $trans["TotalAmount"] - $trans["Allocated"];
	}
	
	
		$total_record = $total_inv - $total_rec + $cart_total;
	//display_error('total record '.$total_record.' -- '.$chk_credit."--".$myrow['credit_limit']);
	if ($total_record > $myrow['credit_limit']  && check_credit11($_SESSION['Items']->customer_id) == 0 && $total_record>0)

	{
		$chk_credit = 1;
		//display_error("The selected customer credit limit exceeds. Please contact the credit control personnel to discuss. <br><a onclick='openprompt()' style='cursor:pointer'>Click here to enable customer to transact</a>");	
		//return false;
	}//display_error($chk_credit);
	hidden('hid_chk_credit', $chk_credit);
	hidden('hid_debtor_no', $_SESSION['Items']->customer_id);
	
	// if($chk_credit == 1 && check_credit11($_SESSION['Items']->customer_id) == 0)
	// {
		// display_error("The selected customer credit limit exceeds. Please contact the credit control personnel to discuss. <br><a onclick='openprompt()' style='cursor:pointer'>Click here to enable customer to transact</a>");	
		// return false;
	// }
	
	return true;
}

hidden('debtor_no11', $_SESSION['Items']->customer_id);

//-----------------------------------------------------------------------------
if (isset($_POST['process_invoice']) && check_data()) {

	$newinvoice=  $_SESSION['Items']->trans_no == 0;
	copy_to_cart();
	if ($newinvoice) new_doc_date($_SESSION['Items']->document_date);
	$invoice_no = $_SESSION['Items']->write();

	$sql = "UPDATE ".TB_PREF."debtors_master 
			SET allow_credit = 0
			WHERE debtor_no = ".$_SESSION['Items']->customer_id;
	// echo $sql;
	// display_error($sql);
	db_query($sql);
	
	processing_end();
	if ($newinvoice) {
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
	} else {
		meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$invoice_no");
	}
}

// find delivery spans for batch invoice display
$dspans = array();
$lastdn = ''; $spanlen=1;

for ($line_no = 0; $line_no < count($_SESSION['Items']->line_items); $line_no++) {
	$line = $_SESSION['Items']->line_items[$line_no];
	if ($line->quantity == $line->qty_done) {
		continue;
	}
	if ($line->src_no == $lastdn) {
		$spanlen++;
	} else {
		if ($lastdn != '') {
			$dspans[] = $spanlen;
			$spanlen = 1;
		}
	}
	$lastdn = $line->src_no;
}
$dspans[] = $spanlen;

//-----------------------------------------------------------------------------

$is_batch_invoice = count($_SESSION['Items']->src_docs) > 1;

$is_edition = $_SESSION['Items']->trans_type == ST_SALESINVOICE && $_SESSION['Items']->trans_no != 0;
start_form();
hidden('cart_id');

start_table("$table_style2 width=80%", 5);

start_row();
label_cells(_("Customer"), $_SESSION['Items']->customer_name, "class='tableheader2'");
label_cells(_("Branch"), get_branch_name($_SESSION['Items']->Branch), "class='tableheader2'");
label_cells(_("Currency"), $_SESSION['Items']->customer_currency, "class='tableheader2'");
end_row();
start_row();

if ($_SESSION['Items']->trans_no == 0) {
	ref_cells(_("INV No."), 'ref', '', null, "class='tableheader2'");
} else {
	label_cells(_("INV No."), $_SESSION['Items']->reference, "class='tableheader2'");
}

//label_cells(_("Delivery Notes:"),
label_cells(_("Delivery Receipts:"),

get_customer_trans_view_str(ST_CUSTDELIVERY, array_keys($_SESSION['Items']->src_docs)), "class='tableheader2'");

label_cells(_("Sales Type"), $_SESSION['Items']->sales_type_name, "class='tableheader2'");

end_row();
start_row();

if (!isset($_POST['ship_via'])) {
	$_POST['ship_via'] = $_SESSION['Items']->ship_via;
}
label_cell(_("Shipping Company"), "class='tableheader2'");
shippers_list_cells(null, 'ship_via', $_POST['ship_via']);

if (!isset($_POST['InvoiceDate']) || !is_date($_POST['InvoiceDate'])) {
	$_POST['InvoiceDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['InvoiceDate'])) {
		$_POST['InvoiceDate'] = end_fiscalyear();
	}
}

date_cells(_("Date"), 'InvoiceDate', '', $_SESSION['Items']->trans_no == 0, 
	0, 0, 0, "class='tableheader2'", true);

if (!isset($_POST['due_date']) || !is_date($_POST['due_date'])) {
	$_POST['due_date'] = get_invoice_duedate($_SESSION['Items']->customer_id, $_POST['InvoiceDate']);
}

date_cells(_("Due Date"), 'due_date', '', null, 0, 0, 0, "class='tableheader2'");


end_row();

//-------------------------------------------------------------------------------------------------
start_row();
$sql = "SELECT salesman 
		FROM ".TB_PREF."debtor_trans 
		WHERE type = 13 
		AND trans_no = ".db_escape($_GET['DeliveryNumber']);
$result = db_query($sql,"could not retrieve salesman");
$myrow = db_fetch_row($result);	
$salesman = $myrow[0];

if ($is_batch_invoice) 
	sales_persons_list_cells(_("Sales Person:"), 'salesman', $salesman, false, "class='tableheader2'");
else
{
	label_cell(_("Sales Person:"), "class='tableheader2'");
	label_cell(get_salesman_name($myrow[0]));

	hidden('salesman', $myrow[0]);
}
//-------------------------------------------------------------------------------------------------

$a = get_customer_details($_SESSION['Items']->customer_id);
$d = db_fetch($a);
label_cells(_('Payment Terms:'), get_term_name($_SESSION['Items']->p_terms), "class='tableheader2'");

end_row();
end_table();

$row = get_customer_to_order($_SESSION['Items']->customer_id);
if ($row['dissallow_invoices'] == 1)
{
	display_error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
	end_form();
	end_page();
	exit();
}	

display_heading(_("Invoice Items"));

div_start('Items');

if ($_SESSION['Items']->get_total_lines() > $_SESSION['Items']->max_invoice_lines AND !isset($_POST['process_invoice']))
{
	display_error('This transaction will exceed '.$_SESSION['Items']->max_invoice_lines.' lines for the invoice printout. 
	Please create another transaction for the other items. Line count:'.$_SESSION['Items']->get_total_lines());
}
	
start_table("$table_style width=80%");
$th = array(_("Item Code"), _("Item Description"), _("Delivered"), _("Units"), _("Invoiced"),
	_("This Invoice"), _("Price"), _("Tax Type"), _("Discount"), /*_("Discount2"), _("Discount3"), _("Discount4"), _("Discount5"), _("Discount6"),*/ _("Notes"), _("Total"),_(''));

if ($is_batch_invoice) {
    $th[] = _("DN");
    $th[] = "";
}

if ($is_edition) {
    $th[4] = _("Credited");
}

table_header($th);
$k = 0;
$has_marked = false;
$show_qoh = true;

$dn_line_cnt = 0;

$vatable = $nonvat = $zerorated = 0;
foreach ($_SESSION['Items']->line_items as $line=>$ln_itm) {
// display_error(var_dump($ln_itm));
	if ($ln_itm->quantity == $ln_itm->qty_done) {
		continue; // this line was fully invoiced
	}
	alt_table_row_color($k);
	view_stock_status_cell($ln_itm->stock_id);

	hidden('Line'.$line.'Desc', $ln_itm->item_description);
	// text_cell(null, 'Line'.$line.'Desc', $ln_itm->item_description, 30, 50);
	label_cell($ln_itm->item_description);
	$dec = get_qty_dec($ln_itm->stock_id);
	qty_cell($ln_itm->quantity, false, $dec);
	label_cell($ln_itm->units);
	qty_cell($ln_itm->qty_done, false, $dec);

	if ($is_batch_invoice) {
		// for batch invoices we can only remove whole deliveries
		// echo '<td nowrap align=right>';
		// hidden('Line' . $line, $ln_itm->qty_dispatched );
		// echo number_format2($ln_itm->qty_dispatched, $dec).'</td>';
		small_qty_cells(null,'Line' . $line, $ln_itm->qty_dispatched);
	} else {
		small_qty_cells(null, 'Line'.$line, qty_format($ln_itm->qty_dispatched, $ln_itm->stock_id, $dec), null, null, $dec);
	}
	$display_discount_percent = percent_format($ln_itm->discount_percent*100) . " %";
	// $display_discount_percent2 = percent_format($ln_itm->discount_percent2*100) . " %";
	// $display_discount_percent3 = percent_format($ln_itm->discount_percent3*100) . " %";
	// $display_discount_percent4 = percent_format($ln_itm->discount_percent4*100) . " %";
	// $display_discount_percent5 = percent_format($ln_itm->discount_percent5*100) . " %";
	// $display_discount_percent6 = percent_format($ln_itm->discount_percent6*100) . " %";

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
	
	amount_cell($ln_itm->price);
	label_cell($ln_itm->tax_type_name);
	label_cell($display_discount_percent, "nowrap align=right");
	// label_cell($display_discount_percent2, "nowrap align=right");
	// label_cell($display_discount_percent3, "nowrap align=right");
	// label_cell($display_discount_percent4, "nowrap align=right");
	// label_cell($display_discount_percent5, "nowrap align=right");
	// label_cell($display_discount_percent6, "nowrap align=right");
	
	// text_cells(null, 'Line'.$line.'discount_percent', $display_discount_percent);
	// text_cells(null, 'Line'.$line.'discount_percent2', $display_discount_percent2);
	// text_cells(null, 'Line'.$line.'discount_percent3', $display_discount_percent3);
	
	label_cell($ln_itm->comment, "nowrap align=right");
	amount_cell($line_total);
	
	//if ($editable_items)
			{
				// edit_button_cell("Edit$line_no", _("Edit"),
				// _('Edit document line'));
				// delete_button_cell("Delete$line_no&itm".$ln_itm->stock_id, _("Delete"),
				// _('Remove line from document'));
				// delete_button_cell("itm".$ln_itm->stock_id, _("Delete"),
				// _('Remove line from document'));
				
				 delete_button_cell("RemoveDN".$line, _('Delete'),	_('Remove line from document'));
				
				// echo '<td><a href="task.php?option=delete" onclick="return (confirm('do you really want to delete the stuff?'));>Delete</a></td>';
				// echo '<td><button type="submit" class="editbutton_rev" name="Delete'.$line_no.'" value="1" title="Remove line from document"'.
						// "onclick='return confirm(".'"Are you sure you want to delete this line?"'.");'"
						// .'>'.set_icon(ICON_DELETE).'</button></td>';
			}

	if ($is_batch_invoice) {
		if ($dn_line_cnt == 0) {
			$dn_line_cnt = $dspans[0];
			$dspans = array_slice($dspans, 1);
			label_cell(get_reference(13,$ln_itm->src_no), "rowspan=$dn_line_cnt class=oddrow nowrap");
			// label_cell("<a href='" . $_SERVER['PHP_SELF'] . "?RemoveDN=".
				// $ln_itm->src_no."'>" . _("Remove") . "</a>", "rowspan=$dn_line_cnt class=oddrow");
		}
		$dn_line_cnt--;
	}
	end_row();
	$counter++;
}

hidden('item_count',$counter);

div_start('err_msg_div');
if ($_SESSION['logged_uname'] == '')
{
	echo "<div class='err_msg'>";
	echo 'Supervisor\'s approval is needed to enable deleting of items. <br>
			If you haven\'t entered the supervisor\'s account <a class="app_">Click here</a> ';
	echo "</div>";
}
div_end();

// display_error('Supervisor\'s approval is needed to enable modifying of items. <a class="app_">Click here</a> ');
// echo "</div>";
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
	
/*Don't re-calculate freight if some of the order has already been delivered -
depending on the business logic required this condition may not be required.
It seems unfair to charge the customer twice for freight if the order
was not fully delivered the first time ?? */

if (!isset($_POST['ChargeFreightCost']) || $_POST['ChargeFreightCost'] == "") {
	if ($_SESSION['Items']->any_already_delivered() == 1) {
		$_POST['ChargeFreightCost'] = price_format(0);
	} else {
		$_POST['ChargeFreightCost'] = price_format($_SESSION['Items']->freight_cost);
	}

	if (!check_num('ChargeFreightCost')) {
		$_POST['ChargeFreightCost'] = price_format(0);
	}
}


$accumulate_shipping = get_company_pref('accumulate_shipping');
if ($is_batch_invoice && $accumulate_shipping)
	set_delivery_shipping_sum(array_keys($_SESSION['Items']->src_docs));

$colspan = 10;

if ($is_batch_invoice) {
label_cell('', 'colspan=2');
}

end_row();
$inv_items_total = $_SESSION['Items']->get_items_total_dispatch();
$display_sub_total = price_format($inv_items_total + input_num('ChargeFreightCost'));

$display_total = price_format(
			
			input_num('ChargeFreightCost') + 
			( $inv_items_total
			)
				
			);

$taxes = $_SESSION['Items']->get_taxes(input_num('ChargeFreightCost'));
$_tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['Items']->tax_included, $is_batch_invoice ? 2:0, 0);

label_row(_("Total Sales"), price_format($inv_items_total), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);

// label_row(_("VATABLE Sales"), price_format($inv_items_total - $_tax_total), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
// label_row(_("NON-VATABLE Sales"), price_format(0), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
// label_row(_("ZERO RATED Sales"), price_format(0), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);

//////////////////////////////////////////////////////////////////////////////
$sql1 = "SELECT tax_group_id FROM 0_cust_branch WHERE branch_code = ".db_escape($_SESSION['Items']->Branch);
$result1 = db_query($sql1,"could not retrieve tax_type_id");
$row1 = db_fetch_row($result1);

if($row1[0] == 1)
{
	label_row(_("VATABLE Sales"), price_format($vatable/1.12), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
	label_row(_("NON-VATABLE Sales"), price_format($nonvat), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
	label_row(_("ZERO RATED Sales"), price_format($zerorated), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
}
else if($row1[0] == 2)
{
	label_row(_("VATABLE Sales"), price_format(0), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
	label_row(_("NON-VATABLE Sales"), price_format($vatable+$nonvat+$zerorated), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
	label_row(_("ZERO RATED Sales"), price_format(0), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
}
else 
{
	label_row(_("VATABLE Sales"), price_format(0), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
	label_row(_("NON-VATABLE Sales"), price_format($nonvat), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
	label_row(_("ZERO RATED Sales"), price_format($vatable+$zerorated), "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);
}
//////////////////////////////////////////////////////////////////////////////

$tax_total = display_edit_tax_items($taxes, $colspan, $_SESSION['Items']->tax_included, $is_batch_invoice ? 2:0);


start_row();
label_cell(_("Shipping Cost"), "colspan=$colspan align=right");
small_amount_cells(null, 'ChargeFreightCost', null);

// $display_total = price_format(($inv_items_total + input_num('ChargeFreightCost') + $tax_total));


label_row(_("Amount Total"), $display_total, "colspan=$colspan align=right","align=right", $is_batch_invoice ? 2 : 0);

end_table(1);
div_end();

start_table($table_style2);
textarea_row(_("Memo"), 'Comments', null, 50, 4);

end_table(1);

submit_center_first('Update', _("Update"),
  _('Refresh document page'), false);
submit_center_last('process_invoice', _("Create Invoice"),
  _('Check entered data and save document'), 'default');

end_form();

echo "<center><br><a href=javascript:goBack()>Cancel Invoice</a><p></center>";

end_page();

?>

<script>
	$(document).ready(function(){
		
		c = $('input[name="item_count"]').val();
		for(a=0;a<c;a++){
			// alert(a);
			$(':button[name="RemoveDN'+a+'"]').attr('disabled',true);
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
				  
		function pro_(a,txt){
			if(a===null)
			$.prompt(txt,{
				buttons: { Ok:true, Cancel:false },
				callback: post_form,//,
			});
		}
		//Prompt when page is loaded...

		a = $('input[name="ref"]').val();
		
		$('.app_').css('cursor','pointer');
		$('.app_').click(function(){
			write(pro_(null,txt));
		});
		
		function post_form(val,ev,f){
			if(val==true){
				if(f.uname!='' && f.passwd!=''){
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd },
						function(ev){
							 // $.prompt(ev);
							if(ev==true){
								$.prompt('Action approved. You can now proceed..');
								c = $('input[name="item_count"]').val();
								for(a=0;a<c;a++){
									$(':button[name="RemoveDN'+a+'"]').attr('disabled',false);
									
									$('.err_msg').hide();
								}
							}else{
								txt = '<table>'+
										  '<tr>Please enter supervisor user account for approval.<br></tr>'+
										  '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
										  '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
										  '</table>'+mes;
								
								$('input[name="logged_uname"]').attr('value',$);
								write(pro_(null,txt));
							}
						});
				}
			}else{
				// history.back();
			}
		}			
		event.preventDefault();
		
	});
</script>

<!--
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
		var inv_no = $.getUrlVar('ModifyInvoice');
		//Prompt when page is loaded...
		// if(inv_no)
		// write(pro_(null,txt));
		
		$('.editbutton').click(function(ev){
			write(pro_(null,txt));
		});
		
		function post_form(val,ev,f){
			if(val==true){
				if(f.uname!='' && f.passwd!=''){
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd },
						function(ev){
							$.prompt(ev);
							if(ev==true){
								$.prompt('Action approved. You can now proceed..');
							}else{
								// txt = '<table>'+
										  // '<tr>Please enter account for approval.<br></tr>'+
										  // '<tr><td>Username: </td><td><input type="text" id="uname" name="uname"></td></tr>'+
										  // '<tr><td>Password: </td><td><input type="password" id="passwd" name="passwd"></td></tr>'+
										  // '</table>'+
										  // '<br><font color=red>Invalid supervisor user account.</font>';
								
								// $('input[name="logged_uname"]').attr('value',$);
								// write(pro_(null,txt));
								
								$.prompt('asdasd');
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
-->


<script>
	$(document).ready(function(){
		
		
		jQuery.prompt.setDefaults({
			//prefix: 'myPrompt',
			show: 'slideDown'
			,top: '40%'
		});
		
		// $('#Update').hide();
		
		c = $('input[name="item_count"]').val();
		for(a=0;a<c;a++){
			//alert(a);
			$(':button[name="Edit'+a+'"]').attr('disabled',false);
			$('input[name="Line'+a+'Price"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent2"]').attr('disabled',true);
			$('input[name="Line'+a+'discount_percent3"]').attr('disabled',true);
		}
		
		// var num = linee.substr(4);
		
		// jQuery.prompt.$('.jqiclose').click(function(ev){
			// alert('asdsd');
		// });
		var inv_no = $.getUrlVar('ModifyInvoice');
		var errpro = 'Invalid supervisor user account. Please try again.';
		var sapp = 'Action approved! You can now proceed with the transaction.';
		
		if(inv_no)
		$('.editbutton').click(function(ev){
		
			//var linee = $(this+'input[name="linee"]').val();
			var linee = $(this).attr('name');
			//var errpro = 'Invalid supervisor user account. Please try again.';
		
			// alert( linee.split('=',2));
			var inv_no = $.getUrlVar('ModifyInvoice');
			// var vars     = linee.split('&');
			// var char_no = linee.search();
			var item       = linee.substr(3);

			function post_form(val,ev,f){
			//alert(val+ ' - '+ev+ ' - '+f);
				if(val==true)
					if(f.uname!='' && f.passwd!=''){
						//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
						$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd },
							function(ev){
								// $.prompt(ev);
								if(ev==true){
								
									$.post('deleteItmInvoice.php',{'item':item,'inv_no':inv_no,'user':f.uname},
										function(ev){
											$.prompt(ev);
										});
									// $.prompt('Item Deleted.');
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
					  '<tr>Supervisor\'s approval is needed to delete a line item.<br></tr>'+
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
		
		$('#process_delivery').click(function(){		
		//var errpro = 'Invalid supervisor user account. Please try again.';
		
			if($('input[name="noti_"]').val()=='0'){
			function post_form(val,ev,f){
				if(f.uname!='' && f.passwd!=''){
					// $.prompt(f.noti_);
					//$.prompt('Username: ' + f.uname + '<br>Password: '+f.passwd);
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'noti_':'1'},
						function(ev){
							// $.prompt(ev);
							if(ev==true){
								$.prompt(sapp);
								// alert('asdasd');
								$('input[name="noti_"]').attr("value",1);
								location.reload();
								//return true;
								//alert('sadasd');
								// $("input[name='noti_']").val(1);
								//var noti_ = 1;
								
								$.post('confirm.php',{ 'noti_':1 },
								function(ev){
									//alert(ev);
									location.reload();
								});
								// location.reload();
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
					  
			
			var or_no = $.getUrlVar('OrderNumber');
			// alert(or_no);
			if(or_no)
			$.prompt(txt,{
				// opacity: 0.8,
				buttons: { Ok:true, Cancel:false },
				callback: post_form,//,
				//, prefix:'jqismooth'
				//,top: 300
			});
			}
				//$.prompt(txt);
			// $.prompt(txt,{
				// // opacity: 0.8,
				// callback: post_form//,
				// //, prefix:'jqismooth'
				// //,top: 300
			// });
		});
		
		// $('#Update').click(function(ev){
			// alert('asdasd');
			// event.preventDefault();
		// });
	});
</script>