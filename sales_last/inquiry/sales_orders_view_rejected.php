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
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/sales/includes/sales_ui.inc");
include($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
//include($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$page_security = 'SA_SALESTRANSVIEW';

set_page_security( @$_POST['order_view_mode'],
	array(	'OutstandingOnly' => 'SA_SALESDELIVERY',
			'InvoiceTemplates' => 'SA_SALESINVOICE'),
	array(	'OutstandingOnly' => 'SA_SALESDELIVERY',
			'InvoiceTemplates' => 'SA_SALESINVOICE')
);

add_js_ufile($path_to_root.'/js/thickbox.js');
echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />
";

//add_js_ufile($path_to_root.'/js/thickbox.js');

if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

$js .= "
	function openThickBox(id){
		url = '../customer_del_so.php?OrderNumber=' + id + '&view=3&type=1&KeepThis=true&TB_iframe=true&height=280&width=300';
		tb_show('Void SO', url);
	}
	
	var message =  '<table>'+
					  '<tr>Editing Sales Order requires supervisor approval. Please enter supervisor account for approval.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
	
	jQuery.prompt.setDefaults({
			show: 'slideDown'
			,top: 350
		});
	
	function post_form(val,ev,f,id){
		$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'noti_':'1'},
			function(ev){
				// alert(ev);
					// $.prompt('Action approved. You can now proceed..',
				if(ev==true){
					$.prompt('Action approved. You can now proceed..',{
						buttons:[
							{title: 'Ok',value:true}
							], 
						submit: function(v){ 
							if(v==true){
								url = '../sales_order_entry.php?ModifyOrderNumber='+id;
								$(location).attr('href',url);
							}
						} 
					});
					
					
				}else{
					$.prompt('Invalid supervisor account.');
				}	
			}
		);
	}
	
	function approval(id){
		$.prompt(message,{
					buttons: { Ok:true, 
								   Cancel:false },
					callback: post_form
				},id);
	}
";


// $js .= "
	// $(document).ready(function(){

		// $('#edit').click(function(ev){
			// alert('qweqwe');
		// });
	// });
// ";
	
if (get_post('type'))
	$trans_type = $_POST['type'];
elseif (isset($_GET['type']) && $_GET['type'] == ST_SALESQUOTE)
	$trans_type = ST_SALESQUOTE;
else
	$trans_type = ST_SALESORDER;

if ($trans_type == ST_SALESORDER)
{
	if (isset($_GET['OutstandingOnly']) && ($_GET['OutstandingOnly'] == true))
	{
		$_POST['order_view_mode'] = 'OutstandingOnly';
		$_SESSION['page_title'] = _($help_context = "Search Outstanding Sales Orders");
	}
	elseif (isset($_GET['InvoiceTemplates']) && ($_GET['InvoiceTemplates'] == true))
	{
		$_POST['order_view_mode'] = 'InvoiceTemplates';
		$_SESSION['page_title'] = _($help_context = "Search Template for Invoicing");
	}
	elseif (isset($_GET['DeliveryTemplates']) && ($_GET['DeliveryTemplates'] == true))
	{
		$_POST['order_view_mode'] = 'DeliveryTemplates';
		$_SESSION['page_title'] = _($help_context = "Select Template for Delivery");
	}
	elseif (!isset($_POST['order_view_mode']))
	{
		$_POST['order_view_mode'] = false;
		$_SESSION['page_title'] = _($help_context = "Search All Rejected Sales Orders");
	}
}
else
{
	$_POST['order_view_mode'] = "Quotations";
	$_SESSION['page_title'] = _($help_context = "Search All Sales Quotations");
}
page($_SESSION['page_title'], false, false, "", $js);

if (isset($_GET['selected_customer']))
{
	$selected_customer = $_GET['selected_customer'];
}
elseif (isset($_POST['selected_customer']))
{
	$selected_customer = $_POST['selected_customer'];
}
else
	$selected_customer = -1;
	
if (isset($_GET['customer_id']))
{
	$_POST['customer_id'] = $_GET['customer_id'];
}

//---------------------------------------------------------------------------------------------

if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") &&
	($_POST['SelectStockFromList'] != ALL_TEXT))
{
 	$selected_stock_item = $_POST['SelectStockFromList'];
}
else
{
	unset($selected_stock_item);
}

//---------------------------------------------------------------------------------------------
//	Query format functions
//
function check_overdue($row)
{
	global $trans_type;
	if ($trans_type == ST_SALESQUOTE)
		return (date1_greater_date2(Today(), sql2date($row['delivery_date'])));
	else
		return ($row['type'] == 0
			&& date1_greater_date2(Today(), sql2date($row['ord_date']))
			&& ($row['TotDelivered'] < $row['TotQuantity']));
}

function check_voided(&$row)
{
	$sql = "SELECT memo_
				FROM 0_voided
				WHERE type = ".$row['trans_type']."
				AND id = ".$row['order_no'];
				//echo $sql.'<p>';
	$query = db_query($sql);
	$count = db_num_rows($query);
	//echo $count.'<p>';
	return $count;
}

function view_link($row)
{
	global $trans_type;
	return  get_customer_trans_view_str($trans_type, $row['order_no'], $row['reference']); //getSORef($order_no));
}

function prt_link($row)
{
	global $trans_type;
	return print_document_link($row['order_no'], _("Print"), true, $trans_type, ICON_PRINT);
}

function edit_link($row) 
{
	$count = check_voided($row);
	$delivered = sales_order_has_deliveries($row['order_no']);
	
	if($count > 0){
		return '';
	}else if($delivered > 0){
		return '';
	}else{
		global $trans_type;
		$modify = ($trans_type == ST_SALESORDER ? "ModifyOrderNumber" : "ModifyQuotationNumber");
	  // return pager_link( _("Edit"),
		// "/sales/sales_order_entry.php?$modify=" . $row['order_no'], ICON_EDIT,'edit');
		
		
		$idd = $modify."=".$row['order_no'];
		return "<img style='cursor:pointer' title='Edit' src='../../themes/modern/images/edit.gif' height=12 width=12 onclick='approval(".$idd.")'>"; 
	}
}

function dispatch_link($row)
{
	$count = check_voided($row);

	global $trans_type;
	if ($trans_type == ST_SALESORDER)
	{
  		if($count > 0){
			return '';
		}else{
			return pager_link( _("Dispatch"), "/sales/customer_delivery.php?OrderNumber=" .$row['order_no'], ICON_DOC);
		}
	}
	else
	{
		if($count > 0){
			return '';
		}else{
			return pager_link( _("Sales Order"), "/sales/sales_order_entry.php?OrderNumber=" .$row['order_no'], ICON_DOC);
		}
	}
}

function so_void_link($row)
{
	$delivered = sales_order_has_deliveries($row['order_no']);
	$void = check_voided($row);
	
	if($void == 0){
		if($delivered == 0){
			return "<img style='cursor:pointer' title='Void SO' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['order_no'].")'>"; 
		}
	}
}

function invoice_link($row)
{
	global $trans_type;
	$count = check_voided($row);
	
	if ($trans_type == ST_SALESORDER){
  		if($count == 0){
			return pager_link( _("Invoice"),
				"/sales/sales_order_entry.php?NewInvoice=" .$row["order_no"], ICON_DOC);
		}
	}else{
		return '';
	}
}

function delivery_link($row)
{
	$count = check_voided($row);
	//echo $count.'<p>';
	if($count > 0){
		return '';
	}else{
		return pager_link( _("Delivery"), "/sales/sales_order_entry.php?NewDelivery=" .$row['order_no'], ICON_DOC);
	}
}

function order_link($row)
{
  return pager_link( _("Sales Order"),
	"/sales/sales_order_entry.php?NewQuoteToSalesOrder=" .$row['order_no'], ICON_DOC);
}

function tmpl_checkbox($row)
{
	global $trans_type;
	if ($trans_type == ST_SALESQUOTE)
		return '';
	$name = "chgtpl" .$row['order_no'];
	$value = $row['type'] ? 1:0;

// save also in hidden field for testing during 'Update'

 return checkbox(null, $name, $value, true,
 	_('Set this order as a template for direct deliveries/invoices'))
	. hidden('last['.$row['order_no'].']', $value, false);
}
//---------------------------------------------------------------------------------------------
// Update db record if respective checkbox value has changed.
//
function change_tpl_flag($id)
{
	global	$Ajax;
	
  	$sql = "UPDATE ".TB_PREF."sales_orders SET type = !type WHERE order_no=$id";

  	db_query($sql, "Can't change sales order type");
	$Ajax->activate('orders_tbl');
}

$id = find_submit('_chgtpl');
if ($id != -1)
	change_tpl_flag($id);

if (isset($_POST['Update']) && isset($_POST['last'])) {
	foreach($_POST['last'] as $id => $value)
		if ($value != check_value('chgtpl'.$id))
			change_tpl_flag($id);
}

function getTrxnStatus($row){
	
	/*$sql = "SELECT DISTINCT trans_no, type
				FROM 0_debtor_trans
				WHERE order_ = ".$row['order_no']."";*/
	
	$count = check_voided($row);
	
	if($count > 0){
		return 'Voided';
	}else{
		$deliveries = sales_order_has_deliveries($row['order_no']);
		$dr_no = getDRNofromSO($row['order_no']);
		
		if(isset($dr_no) && $dr_no != 0){
			$voided = checkIfVoided($dr_no, 13);
			
			if($voided > 0){
				return 'Pending';
			}else{
				$invoice = getSINofromDR($dr_no);
				
				if(isset($invoice) && $invoice != 0){
					//return 'Invoiced';
					if(get_voided_entry(10, $invoice) === false){
						return 'Invoiced';
					}else{
						return 'Delivered';
					}
				}else{
					return 'Delivered';
				}
			}
		}else{
			return 'Pending';
		}
		
	}
}

//---------------------------------------------------------------------------------------------
//	Order range form
//
if (get_post('_OrderNumber_changed')) // enable/disable selection controls
{
	$disable = get_post('OrderNumber') !== '';

  	if ($_POST['order_view_mode']!='DeliveryTemplates' 
		&& $_POST['order_view_mode']!='InvoiceTemplates') {
			$Ajax->addDisable(true, 'OrdersAfterDate', $disable);
			$Ajax->addDisable(true, 'OrdersToDate', $disable);
	}
	$Ajax->addDisable(true, 'customer_id', $disable);
	$Ajax->addDisable(true, 'StockLocation', $disable);
	$Ajax->addDisable(true, '_SelectStockFromList_edit', $disable);
	$Ajax->addDisable(true, 'SelectStockFromList', $disable);

	if ($disable) {
		$Ajax->addFocus(true, 'OrderNumber');
	} else
		$Ajax->addFocus(true, 'OrdersAfterDate');

	$Ajax->activate('orders_tbl');
}

start_form();

if (!isset($_POST['customer_id']))
	$_POST['customer_id'] = get_global_customer();

start_table("width='80%' class='tablestyle_noborder'");
start_row();
//ref_cells(_("#:"), 'OrderNumber', '',null, '', true);
if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates')
{
	ref_cells(_("SO #"), 'OrderReference', '',null, '', true);
  	date_cells(_("from:"), 'OrdersAfterDate', '', null, -30);
  	date_cells(_("to:"), 'OrdersToDate', '', null, 1);
}

customer_list_cells(_("Select a customer: "), 'customer_id', null, true);

locations_list_cells(_("Location:"), 'StockLocation', null, true);



if ($trans_type == ST_SALESQUOTE)
	check_cells(_("Show All:"), 'show_all');
	
if ($trans_type == ST_SALESORDER && $_GET['OutstandingOnly'] != true){
	//statusCombo($_POST);
	$array=array(0=>'All', 1=>'Pending', 2=>'Delivered', 3=>'Invoiced', 4=>'Voided');
	label_cell("Status:");
	label_cell(array_selector('stats',$_POST['stats'],$array));
}
	
submit_cells('SearchOrders', _("Search"),'',_('Select documents'), 'default');

echo "</tr><tr><td colspan='13' align='left'><table border='0'><tr>";

stock_items_list_cells(_("Item:"), 'SelectStockFromList', null, true, false, false, false, 'colspan=12');
 
 echo "</table></td>";

hidden('order_view_mode', $_POST['order_view_mode']);
hidden('type', $trans_type);

end_row();

end_table(1);

set_global_customer($_POST['customer_id']);
//---------------------------------------------------------------------------------------------
//	Orders inquiry table
//
$sql = "SELECT 
		sorder.order_no,
		
		debtor.name,
		branch.br_name,"
		.($_POST['order_view_mode']=='InvoiceTemplates' 
		   	|| $_POST['order_view_mode']=='DeliveryTemplates' ?
		 "sorder.comments, " : "sorder.customer_ref, ")
		."sorder.ord_date,
		sorder.delivery_date,
		sorder.deliver_to,
		/*Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue,*/
		
		( 
			(
				SUM(line.unit_price*line.quantity*(1-line.discount_percent)*(1-line.discount_percent2)*(1-line.discount_percent3)*(1-line.discount_percent4)*(1-line.discount_percent5)*(1-line.discount_percent6)) 
				
			)
			+ freight_cost
		)  as TotalOrderValue, 
		
		SUM(line.unit_price*line.quantity*(1-line.discount_percent)*(1-line.discount_percent2)*(1-line.discount_percent3)*(1-line.discount_percent4)*(1-line.discount_percent5)*(1-line.discount_percent6))+freight_cost as OrderValue,
		debtor.curr_code,
		sorder.type,
		Sum(line.qty_sent) AS TotDelivered,
		Sum(line.quantity) AS TotQuantity, 
		reference, 
		sorder.trans_type
	FROM ".TB_PREF."sales_orders as sorder, "
		.TB_PREF."sales_order_details as line, "
		.TB_PREF."debtors_master as debtor, "
		.TB_PREF."cust_branch as branch
		WHERE sorder.order_no = line.order_no
		AND sorder.trans_type = line.trans_type
		AND sorder.trans_type = $trans_type
		AND sorder.debtor_no = debtor.debtor_no
		AND sorder.branch_code = branch.branch_code
		AND sorder.is_approve = 2
		AND sorder.reference != 'auto'
		AND debtor.debtor_no = branch.debtor_no";
		
if ($_POST['customer_id'] != ALL_TEXT)
   		$sql .= " AND sorder.debtor_no = ".db_escape($_POST['customer_id']);

if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "")
{
	// search orders with number like 
	$number_like = "%".$_POST['OrderNumber'];
	$sql .= " AND sorder.order_no LIKE ".db_escape($number_like)
 			." GROUP BY sorder.order_no";
}
elseif (isset($_POST['OrderReference']) && $_POST['OrderReference'] != "")
{
	// search orders with reference like 
	$number_like = "%".$_POST['OrderReference']."%";
	$sql .= " AND sorder.reference LIKE ".db_escape($number_like)
 			." GROUP BY sorder.order_no";
}
else	// ... or select inquiry constraints
{
  	if ($_POST['order_view_mode']!='DeliveryTemplates' && $_POST['order_view_mode']!='InvoiceTemplates')
  	{
		$date_after = date2sql($_POST['OrdersAfterDate']);
		$date_before = date2sql($_POST['OrdersToDate']);

		$sql .=  " AND sorder.ord_date >= '$date_after'"
				." AND sorder.ord_date <= '$date_before'";
  	}
  	if ($trans_type == 32 && !check_value('show_all'))
  		$sql .= " AND sorder.delivery_date >= '".date2sql(Today())."'";
	if ($selected_customer != -1)
		$sql .= " AND sorder.debtor_no=".db_escape($selected_customer);

	if (isset($selected_stock_item))
		$sql .= " AND line.stk_code=".db_escape($selected_stock_item);

	if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT)
		$sql .= " AND sorder.from_stk_loc = ".db_escape($_POST['StockLocation']);

	if ($_POST['order_view_mode']=='OutstandingOnly')
		$sql .= " AND line.qty_sent < line.quantity";
	elseif ($_POST['order_view_mode']=='InvoiceTemplates' || $_POST['order_view_mode']=='DeliveryTemplates')
		$sql .= " AND sorder.type=1";

	if($_POST['stats'] == 1){
		//status = pending
		$sql .= " AND sorder.order_no NOT IN (SELECT DISTINCT order_ FROM 0_debtor_trans dtrans
																		WHERE dtrans.type IN (13, 10, 12, 11))
					AND sorder.order_no NOT IN (SELECT id FROM 0_voided WHERE type = sorder.trans_type)";
	}
	
	if($_POST['stats'] == 2){
		//status = delivered
		$sql .= " AND sorder.order_no IN (SELECT DISTINCT order_ FROM 0_debtor_trans dtrans
															 WHERE dtrans.type = 13 AND order_ = sorder.order_no
                                                             AND trans_link = 0
															 AND trans_no NOT IN (SELECT id FROM 0_voided WHERE type=13))";
	}
	
	if($_POST['stats'] == 3){
		//status = invoiced
		$sql .= " AND sorder.order_no IN (SELECT DISTINCT order_ FROM 0_debtor_trans dtrans
															 WHERE dtrans.type = 13 AND order_ = sorder.order_no
                                                             AND trans_link != 0
															 AND trans_no NOT IN (SELECT id FROM 0_voided WHERE type=13))";
	}
		
	if($_POST['stats'] == 4){
		//status = voided
		$sql .= " AND sorder.order_no IN (SELECT id FROM 0_voided WHERE type = sorder.trans_type)";
	}
		
	$sql .= " GROUP BY sorder.order_no,
				sorder.debtor_no,
				sorder.branch_code,
				sorder.customer_ref,
				sorder.ord_date,
				sorder.deliver_to";
}

if ($trans_type == ST_SALESORDER)
	$cols = array(
		_("SO #") => array('fun'=>'view_link', 'type'=>'nowrap'),
		_("Customer"),
		_("Branch"), 
		_("Cust Order Ref"),
		_("Order Date") => 'date',
		_("Required By") =>array('type'=>'date', 'ord'=>''),
		_("Delivery To"), 
		_("Order Total") => array('type'=>'amount', 'ord'=>''),
		'Type' => 'skip',
		_("Currency") => array('align'=>'center')
	);
else
	$cols = array(
		_("Quote #") => array('fun'=>'view_link', 'type'=>'nowrap'),
		_("Customer"),
		_("Branch"), 
		_("Cust Order Ref"),
		_("Quote Date") => 'date',
		_("Valid until") =>array('type'=>'date', 'ord'=>''),
		_("Delivery To"), 
		_("Quote Total") => array('type'=>'amount', 'ord'=>''),
		'Type' => 'skip',
		_("Currency") => array('align'=>'center')
	);
// if ($_POST['order_view_mode'] == 'OutstandingOnly') {
	// //array_substitute($cols, 3, 1, _("Cust Order Ref"));
	// array_append($cols, array(array('insert'=>true, 'fun'=>'dispatch_link'), array('insert'=>true, 'fun'=>'so_void_link')));

// } elseif ($_POST['order_view_mode'] == 'InvoiceTemplates') {
	// array_substitute($cols, 3, 1, _("Description"));
	// array_append($cols, array( array('insert'=>true, 'fun'=>'invoice_link')));

// } else if ($_POST['order_view_mode'] == 'DeliveryTemplates') {
	// array_substitute($cols, 3, 1, _("Description"));
	// array_append($cols, array(
			// array('insert'=>true, 'fun'=>'delivery_link'))
	// );

// } elseif ($trans_type == ST_SALESQUOTE) {
	 // array_append($cols,array(
					// array('insert'=>true, 'fun'=>'edit_link'),
					// array('insert'=>true, 'fun'=>'order_link'),
					// array('insert'=>true, 'fun'=>'prt_link')));
// } elseif ($trans_type == ST_SALESORDER) {
	 // array_append($cols,array(
			// _("Tmpl") => array('insert'=>true, 'fun'=>'tmpl_checkbox'),
			// _("Status") => array('insert'=>true, 'fun'=>'getTrxnStatus'),
					// array('insert'=>true, 'fun'=>'edit_link'),
					// array('insert'=>true, 'fun'=>'prt_link'),
					// array('insert'=>true, 'fun'=>'so_void_link'),
					// array('insert'=>true, 'fun'=>'status_link')));
// };


$table =& new_db_pager('orders_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Highlighted items are overdue."));
//$table->set_marker('check_voided', _("Marked items are voided."), 'voidedbg', 'voidedfg');

$table->width = "80%";

display_db_pager($table);
submit_center('Update', _("Update"), true, '', null);

end_form();
end_page();
?>