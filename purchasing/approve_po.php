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
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager.inc");

//page(_($help_context = "Item Location Transfer Approval"));

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/manufacturing.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/purchasing/includes/ui/po_ui.inc");

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
					  '<tr>Supervisor Approval is required to approve selected purchase order.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';		
		var txt2 = '<table>'+
					  '<tr>Supervisor Approval is required to reject selected purchase order.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
		var errpro = 'Invalid supervisor user account. Please try again.';
var sapp = 'Purchase Order has been approved.';
var sapp2 = 'Purchase Order has been rejected.';

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
page(_($help_context = "Purchase Order Approval"), false, false, "", $js);

//----------------------------------------------------------------------------------------
//simple_page_mode(true);

if (isset($_GET['order_number']))
{
	$_POST['order_number'] = $_GET['order_number'];
}

//--------------------------------------------------------------------------------------------------
function can_process($trans_no,$action){
	$can_approve = "SELECT allow_po_approval as allow FROM ".TB_PREF."company";
	// display_error($can_approve);
	$can_approve = db_query($can_approve);
	$is = db_fetch($can_approve);
	
		if($is['allow'])
			return $is['allow'];

	if(!$_SESSION['allowapprovePO'.$trans_no]){
		display_error("<a class='approve' onclick='openprompt(".$trans_no.",".$action.")' style='cursor:pointer'>Supervisor Approval is required to continue.</a>");
	return false;	
	}
	return true;

}
function changeOrder($trans_no,$action){
	if(can_process($trans_no,$action)){
	global $Ajax;
	global $Refs;
	
	$date_today = date("Y-m-d");
	
	$poref = getPORef($trans_no);
	
	$sql = "UPDATE ".TB_PREF."purch_orders
			SET is_approve = ".db_escape($action).",
				date_approve = ".db_escape($date_today).",
				approving_officer = ".db_escape($_SESSION["wa_current_user"]->username)."
			WHERE order_no = ".db_escape($trans_no);
		
	db_query($sql, "could not update purch order");
	

/*
	if($action == 1){
		display_notification_centered(_("Purchase Order #$poref has been approved!"));
	}elseif($action == 2){
		//display_notification_centered(_("Processed"));
		display_error(_("Purchase Order #$poref has been rejected!"));
	}
*/

	if($action==2){
		$ordernum=$trans_no;
		$date_ = date('m/d/Y');
			//$number = getPORef($ordernum);
			$today = date('l jS \of F Y h:i:s A');
				$comments = 'VOID '.$today;
			
				$sql = "UPDATE 0_purch_orders
							SET comments = '$comments', vat=0
							WHERE order_no = $ordernum";
				db_query($sql) or die('could not void purchase order');
				
				$sql = "UPDATE 0_purch_order_details
							SET qty_invoiced = 0, unit_price=0, act_price=0, std_cost_unit=0, quantity_ordered=0, quantity_received=0
							WHERE order_no = $ordernum";
				db_query($sql) or die('could not void purchase order details');
				
				add_audit_trail(18, $ordernum, $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry(18, $ordernum, $date_, $vmemo);
				

	}


	display_notification_centered("Purchase Order has been ".(($action==1)?"approved. <a href=po_receive_items.php?PONumber=$trans_no>Receive Items</a> from this order.":"rejected"));

	
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
	meta_forward($path_to_root.'/purchasing/po_entry_items.php','ModifyOrderNumber='.$checkaction);
}
else{
$checkaction=find_submit('print');
if($checkaction!=-1){
	meta_forward($path_to_root . "/reporting/rep212.php",'order_no='.$checkaction);
}}}
}

//$path_to_root . "/reporting/rep212.php?order_no=".$myrow["order_no"]
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

ref_cells(_("PO #:"), 'order_number', '',null, '', true);
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

//figure out the sql required from the inputs available
$sql = "SELECT 
	porder.order_no, 
	porder.reference,
	supplier.supp_name, 
	location.location_name,
	porder.requisition_no, 
	porder.ord_date,
	supplier.curr_code,
	( Sum(line.unit_price*line.quantity_ordered)) AS OrderValue,
	Sum(line.delivery_date < '". date2sql(Today()) ."'
	AND (line.quantity_ordered > line.quantity_received)) As OverDue
	FROM "
		.TB_PREF."purch_orders as porder, "
		.TB_PREF."purch_order_details as line, "
		.TB_PREF."suppliers as supplier, "
		.TB_PREF."locations as location
	WHERE porder.order_no = line.order_no
	AND porder.is_approve = 0
	AND porder.supplier_id = supplier.supplier_id
	AND location.loc_code = porder.into_stock_location
	AND (line.quantity_ordered > line.quantity_received) ";

if (isset($order_number) && $order_number != "")
{
	$sql .= "AND porder.reference LIKE ".db_escape('%'. $order_number . '%');
}
else if($date_after!=""||$date_before!="")
{
	if($date_after!="")
	$sql .= "  AND porder.ord_date >= '$date_after'";
	if($date_before!="")
	$sql .= "  AND porder.ord_date <= '$date_before'";
} //end not order number selected

$sql .= " GROUP BY porder.order_no";

$result = db_query($sql, "could not query stock moves");

//--------------------------------------------------------------------------------------------------

start_form();

div_start('orders_tbl');
start_table($table_style." width=60%");
$th = array(_("PO #"), _("Supplier"), _("Order Date"), _("Amount"), "", "", "", "");

table_header($th);

$j = 1;
$k = 0; //row colour counter

while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	
	$trandate = sql2date($myrow["ord_date"]);
	
	label_cell(get_trans_view_str(ST_PURCHORDER, $myrow["order_no"], $myrow["reference"]));
	label_cell($myrow["supp_name"]);
	label_cell($trandate);
	amount_cell($myrow["OrderValue"]);
	submit_cells("edit".$myrow["order_no"],_(""), " width=12",_('Edit Purchase Order'),false,ICON_EDIT,true);
	submit_cells("approve".$myrow["order_no"],_(""), " width=12",_('Approve Purchase Order'),false,ICON_APPROVE,true);
	submit_cells("disapprove".$myrow["order_no"], _("")," width=12",_('Reject Purchase Order'),false,ICON_DELETE,true);
	 // submit_cells("print".$myrow["order_no"], _(""),"width=12",_('Print Purchase Order'),false,ICON_PRINT,true);
	label_cell(viewer_link("Print", "reporting/rep212.php?order_no=".$myrow["order_no"],'','',ICON_PRINT),"valign=top width=15");
	// echo "<td><a href=". $path_to_root . "/reporting/rep212.php?order_no=".$myrow["order_no"].">Print</a></td>";
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

echo "<center><a href='" . $path_to_root . "/purchasing/approve_po.php'>"._("Back")."</a></centere>\n";

end_page(false, false, true);

?>
