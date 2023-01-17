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
$path_to_root = "..";

include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$page_security = 'SA_SALESORDER';

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
 
add_js_ufile($path_to_root.'/js/thickbox.js');
echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />
";

$js .= "
$(document).ready(function(){
		$('.approve').trigger('click');
})

function callSubmitButton(val,ev,f,ty){
	
	$('#approve'+ty).trigger('click');
}

function callSubmitButton2(val,ev,f,ty){
	$('#disapprove'+ty).trigger('click');
}

	jQuery.prompt.setDefaults({
			show: 'slideDown'
			,top: 350
		});
		
		var txt = '<table>'+
					  '<tr>Supervisor Approval is required to approve selected order.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';		
		var txt2 = '<table>'+
					  '<tr>Supervisor Approval is required to reject selected order.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
		var errpro = 'Invalid supervisor user account. Please try again.';
var sapp = 'Sales Order has been approved.';
var sapp2 = 'Sales Order has been rejected.';

	function post_form(val,ev,f,ty){
	
			if(val==true)
				if(f.uname!='' && f.passwd!=''){
					$.post('confirmApp.php',{ 'uname':f.uname,'passwd':f.passwd,'ty':ty },
						function(ev){
							
							if(ev==true){
								
								$.prompt(sapp,{
								
									buttons: { Ok:true },
									callback: callSubmitButton
								},ty);
							
							}else{						
								$.prompt(errpro);
							}
						});
				}else
					$.prompt(errpro);
		}				  

	function post_form2(val,ev,f,ty){
	
			if(val==true)
				if(f.uname!='' && f.passwd!=''){
					$.post('confirmApp.php',{ 'uname':f.uname,'passwd':f.passwd,'ty':ty },
						function(ev){
							
							if(ev==true){
								
								$.prompt(sapp2,{
								
									buttons: { Ok:true },
									callback: callSubmitButton2
								},ty);
							
							}else{						
								$.prompt(errpro);
							}
						});
				}else
					$.prompt(errpro);
		}				  

	function openprompt(ty,act){
		
		if(act==1){
			$.prompt(txt,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form
				},ty);
			}
		else {
			$.prompt(txt2,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form2
				},ty);
			}
		}	


";


page(_($help_context = "Sales Order Approval"), false, false, "", $js);
//----------------------------------------------------------------------------------------
//unset($_SESSION['allowapprove']);
//simple_page_mode(true);

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}

//--------------------------------------------------------------------------------------------------
function can_process($trans_no,$action){
	$can_credit = "SELECT allow_so_approval as allow FROM ".TB_PREF."company";
	$can_credit = db_query($can_credit);
	$is = db_fetch($can_credit);
	// display_error($is['allow']);
	
		if($is['allow'])
			return $is['allow'];


	if(!$_SESSION['allowapprove'.$trans_no]){
		display_error("<a class='approve' onclick='openprompt(".$trans_no.",".$action.")' style='cursor:pointer'>Supervisor Approval is required to continue.</a>");
	return false;	
	}
	return true;

}
function changeOrder($trans_no,$action){
	if(can_process($trans_no,$action)){
	
//	display_error('text');
	global $Ajax;
	global $Refs;

	
	$date_today = date("Y-m-d");
	
	$soref = getSORef($trans_no);
	
	$sql = "UPDATE ".TB_PREF."sales_orders
			SET is_approve = ".db_escape($action).",
				date_approve = ".db_escape($date_today).",
				approving_officer = ".db_escape($_SESSION["wa_current_user"]->username)."
			WHERE order_no = ".db_escape($trans_no);
	db_query($sql, "could not update sales order");
	
	if($action==2){
		$date_ = date('m/d/Y');
			
			$ordernum=$trans_no;
				$number = getSONum($ordernum);
				$today = date('l jS \of F Y h:i:s A');
				$comments = 'VOID '.$today;
				//delete_sales_order($ordernum, 30);
				$sql = "UPDATE 0_sales_orders
							SET comments = '$comments',freight_cost=0
							WHERE order_no = $ordernum";
				db_query($sql) or die('could not void sales order');
				
				$sql = "UPDATE 0_sales_order_details
							SET qty_sent = 0, unit_price = 0, quantity = 0, discount_percent = 0, discount_percent2 = 0, discount_percent3 = 0, comment = '$comments'
							WHERE order_no = $ordernum";
				db_query($sql) or die('could not void sales order details');
				
				add_audit_trail(30, $ordernum, $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry(30, $ordernum, $date_, $vmemo);

	}
		
/*
	if($action == 1){
		display_notification_centered(_("Sales Order #$soref has been approved!"));
	}elseif($action == 2){
		//display_notification_centered(_("Processed"));
		display_error(_("Sales Order #$soref has been rejected!"));
	}
*/
	display_notification_centered("Sales Order has been ".(($action==1)?"approved. <a href=customer_delivery.php?OrderNumber=$trans_no>Create Delivery Receipt</a> for this order.":"rejected"));

	


	$Ajax->activate('orders_tbl');
}
}

$checkaction=find_submit('approve');
if($checkaction!=-1)
changeOrder($checkaction,1);
else{
$checkaction=find_submit('disapprove');
if($checkaction!=-1)
changeOrder($checkaction,2);	
else{
$checkaction=find_submit('edit');
if($checkaction!=-1){
	meta_forward($path_to_root.'/sales/sales_order_entry.php','ModifyOrderNumber='.$checkaction);
}
else{
$checkaction=find_submit('print');
if($checkaction!=-1){
	meta_forward($path_to_root . "/reporting/rep109.php",'order_no='.$checkaction);
}}}
}

//--------------------------------------------------------------------------------------------------


if (get_post('SearchOrders')) 
{
	$Ajax->activate('orders_tbl');
} elseif (get_post('_order_number_changed')) 
{
	$disable = get_post('order_number') !== '';

	$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
	$Ajax->addDisable(true, 'OrdersToDate', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'order_number');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}

start_form();

start_table("class='tablestyle_noborder'");
start_row();

ref_cells(_("SO #:"), 'order_number', '',null, '', true);
date_cells(_("from:"), 'OrdersAfterDate', '', null, -30,0,1001);
date_cells(_("to:"), 'OrdersToDate', '', null, 1,0,1001);

submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

end_row();

end_table(1);

end_form();

//--------------------------------------------------------------------------------------------------
//	Orders inquiry table
//
if (isset($_POST['order_number']) && ($_POST['order_number'] != ""))
{
	$order_number = $_POST['order_number'];
}

$date_after = date2sql($_POST['OrdersAfterDate']);
$date_before = date2sql($_POST['OrdersToDate']);
// display_error($date_after." - ".$date_before);
//figure out the sql required from the inputs available
// $line_total = round2($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent) * (1 - $stock_item->discount_percent2) * (1 - $stock_item->discount_percent3),
	   // user_price_dec());

/*	   
$sql = "SELECT sorder.*, debtor.name, 
			Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue
	FROM ".TB_PREF."sales_orders as sorder, "
		.TB_PREF."debtors_master as debtor, "
		.TB_PREF."sales_order_details as line
	WHERE sorder.order_no = line.order_no
	AND sorder.trans_type = line.trans_type
	AND sorder.debtor_no = debtor.debtor_no
	AND sorder.reference != 'auto'
	AND sorder.is_approve = 0 ";
*/

// $line_total = round2($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent) * (1 - $stock_item->discount_percent2) * (1 - $stock_item->discount_percent3),
	   // user_price_dec());
$sql = "SELECT sorder.*, debtor.name, 
			SUM(line.unit_price*line.quantity*(1-line.discount_percent)*(1-line.discount_percent2)*(1-line.discount_percent3)*(1-line.discount_percent4)*(1-line.discount_percent5)*(1-line.discount_percent6)) as OrderValue, 
			( SUM(line.unit_price*line.quantity*(1-line.discount_percent)*(1-line.discount_percent2)*(1-line.discount_percent3)*(1-line.discount_percent4)*(1-line.discount_percent5)*(1-line.discount_percent6)) + freight_cost) as TotalValue,
			
			( 
				(
					SUM(line.unit_price*line.quantity*(1-line.discount_percent)*(1-line.discount_percent2)*(1-line.discount_percent3)*(1-line.discount_percent4)*(1-line.discount_percent5)*(1-line.discount_percent6)) 
					
				)
				+ freight_cost
			)  as TotalOrderValue
			
	FROM ".TB_PREF."sales_orders as sorder, "
		.TB_PREF."debtors_master as debtor, "
		.TB_PREF."sales_order_details as line
	WHERE sorder.order_no = line.order_no
	AND sorder.trans_type = line.trans_type
	AND sorder.debtor_no = debtor.debtor_no
	AND sorder.reference != 'auto'
	AND sorder.is_approve = 0 ";
	
if (isset($order_number) && $order_number != "")
{
	$sql .= " AND sorder.reference LIKE ".db_escape('%'. $order_number . '%');
}
else if($date_after!=""||$date_before!="")
{
	if($date_after!="")
	$sql .= "  AND sorder.ord_date >= '$date_after'";
	if($date_before!="")
	$sql .= "  AND sorder.ord_date <= '$date_before'";
} //end not order number selected

$sql .= " GROUP BY sorder.order_no
		ORDER BY ord_date";
$result = db_query($sql, "could not query sales order");

//--------------------------------------------------------------------------------------------------

start_form();

div_start('orders_tbl');
start_table($table_style." width=60%");
$th = array(_("SO #"), _("Customer"), _("Order Date"), _("Amount"), "", "", "", "");

table_header($th);

$j = 1;
$k = 0; //row colour counter

while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	
	$trandate = sql2date($myrow["ord_date"]);
	
	label_cell(get_trans_view_str(ST_SALESORDER, $myrow["order_no"], getSORef($myrow["order_no"])));
	label_cell($myrow["name"]);
	label_cell($trandate);
	amount_cell($myrow["TotalOrderValue"]/*$myrow["TotalValue"]*//*$myrow["OrderValue"]*/);
	submit_cells("edit".$myrow["order_no"],_(""), " width=12",_('Edit Sales Order'),false,ICON_EDIT,true);
	submit_cells("approve".$myrow["order_no"],_(""), " width=12",_('Approve Sales Order'),false,ICON_APPROVE,true);
	submit_cells("disapprove".$myrow["order_no"], _("")," width=12",_('Reject Sales Order'),false,ICON_DELETE,true);
	label_cell(viewer_link("Print", "reporting/rep109.php?order_no=".$myrow["order_no"],'','',ICON_PRINT),"valign=top width=15");
	end_row();
	
	$j++;
	if ($j == 11)
	{
		$j = 1;
		table_header($th);
	}
}

end_table(1);
div_end();

end_form();

echo "<center><a href='" . $path_to_root . "/sales/approve_so.php'>"._("Back")."</a></centere>\n";

end_page(false, false, true);

?>