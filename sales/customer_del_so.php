<?php

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	<link href='$path_to_root/js/jquery-ui.css'></script>
";

$preloader_gif = $path_to_root.'/themes/modern/images/ajax-loader.gif';


function deleteHiddenDeliveries($tno){
	$sql = "SELECT trans_no
				FROM 0_debtor_trans
				WHERE type = 13
				AND reference = (SELECT reference
											  FROM 0_debtor_trans
											  WHERE trans_no = $tno
											  AND type = 10)
				AND skip_dr = 1";
	$query = db_query($sql) or die(mysql_error());
	return $query;
}

function checkVoided($trans, $type){
	$sql = "SELECT date_, memo_
				FROM 0_voided
				WHERE type = $type
				AND id = $trans";
				//echo $sql;
	$query = db_query($sql);
	return $query;
}

function checkTransType($trans){
	switch($trans){
		case 1: return 30; break;
		case 2: return 13; break;
		case 3: return 10; break;
		case 4: return 18; break;
		case 5: return 25; break;
		case 6: return 20; break;
	}
}

function updateAlloc($from, $type, $amt){
	$sql = "UPDATE 0_supp_trans
				SET alloc = alloc - $amt
				WHERE trans_no = $from
				AND type = $type";
	$query = db_query($sql);
}

if($_POST){
	$ordernum = $_POST['OrderNumber'];
	$type = $_POST['type'];
	$vmemo = $_POST['vmemo'];
	
	$view = $_POST['view'];
	
	$today = date('l jS \of F Y h:i:s A');
	$comments = 'VOID '.$today;
	
	// the first will be the last, and the last will be the firstss
	
	switch($type){
		case ST_SALESORDER:
			//Sales Order			
			$date_ = date('m/d/Y');
			
			if(sales_order_has_deliveries($ordernum)){
				display_error(_("This order cannot be cancelled because some of it has already been invoiced or dispatched. However, the line item quantities may be modified."));
			}else{
				$number = getSONum($ordernum);
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
				
				if($view == 0){
					echo "
						<script>
							alert('Sales Order #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/sales/sales_order_entry.php?NewOrder=Yes';
						</script>
					";
				}elseif($view == 1){
					echo "
						<script>
							alert('Sales Order #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/sales/inquiry/sales_orders_view.php?OutstandingOnly=1';
						</script>
					";
				}elseif($view == 2){
					echo "
						<script>
							alert('Sales Order #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/sales/inquiry/sales_orders_view_inv.php?OutstandingOnly=1';
						</script>
					";
				}elseif($view == 3){
					echo "
						<script>
							alert('Sales Order #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/sales/inquiry/sales_orders_view.php?type=30';
						</script>
					";
				}
			}
			break;
		case ST_CUSTDELIVERY:
			//Delivery Receipt
			$date_ = date('m/d/Y');
			
			$delivery = get_customer_trans($ordernum, 13);
			if ($delivery['trans_link'] != 0)
			{
				if (get_voided_entry(10, $delivery['trans_link']) === false){
					display_error(_("The system cannot void this delivery for the invoice for this transaction has not been voided."));
					return false;
				}
			}
			
			void_sales_delivery(13, $ordernum);
			add_audit_trail(13, $ordernum, $date_, _("Voided.")."\n".$vmemo);
			add_voided_entry(13, $ordernum, $date_, $vmemo);
			
			$number = getReferencebyType($ordernum, 'DR');
			
			//window.parent.location.href = '".$path_to_root."/sales/inquiry/sales_orders_view.php?OutstandingOnly=1';
			if($view == 0){
				echo "
					<script>
						alert('Delivery Receipt #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/index.php?application=orders';
					</script>
				";
			}elseif($view == 1){
				echo "
					<script>
						alert('Delivery Receipt #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/sales/inquiry/sales_deliveries_view.php?OutstandingOnly=1';
					</script>
				";
			}elseif($view == 2){
				echo "
					<script>
						alert('Delivery Receipt #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/sales/inquiry/customer_inquiry.php?';
					</script>
				";
			}
			break;
		case ST_SALESINVOICE:
			//Invoice			
			$date_ = date('m/d/Y');
			void_sales_invoice(10, $ordernum);
			add_audit_trail(10, $ordernum, $date_, _("Voided.")."\n".$vmemo);
			add_voided_entry(10, $ordernum, $date_, $vmemo);
			
			$hiddenDR = deleteHiddenDeliveries($ordernum);
			
			if(db_num_rows($hiddenDR) > 0){
				while($res = mysql_fetch_object($hiddenDR)){
					$trans_no = $res->trans_no;
					
					void_sales_delivery(13, $trans_no);
					add_audit_trail(13, $trans_no, $date_, _("Voided.")."\n".$vmemo);
					add_voided_entry(13, $trans_no, $date_, $vmemo);
				}
			}
			
			$number = getReferencebyType($ordernum, 'INV');
			
			if($view == 0){
				echo "
					<script>
						alert('Invoice #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/index.php?application=orders';
					</script>
				";
			}else if($view == 1){
				echo "
					<script>
						alert('Invoice #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/sales/inquiry/sales_orders_view_inv.php?OutstandingOnly=1';
					</script>
				";
			}else if($view == 2){
				echo "
					<script>
						alert('Invoices #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/sales/inquiry/customer_inquiry.php?';
					</script>
				";
			}
			break;
		case ST_PURCHORDER:
			//Purchase Order			
			$date_ = date('m/d/Y');
			$number = getPORef($ordernum);
			
			if(po_received($ordernum)){
				display_error(_("This order cannot be cancelled because some of it has already been invoiced or received. However, the line item quantities may be modified."));
			}else{
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
				
				if($view == 0){
					echo "
						<script>
							alert('Purchase Order #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/purchasing/po_entry_items.php?NewOrder=Yes';
						</script>
					";
				}else if($view == 1){
					echo "
						<script>
							alert('Purchase Order #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/purchasing/inquiry/po_search.php?';
						</script>
					";
				}else if($view == 2){
					echo "
						<script>
							alert('Purchase Order #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/purchasing/inquiry/po_search_completed.php?';
						</script>
					";
				}
			}
			
			break;
		case ST_SUPPRECEIVE:
			//Receiving		
			$date_ = date('m/d/Y');
			$number = getRecRef($ordernum);
			
			if(po_invoiced($ordernum)){
				display_error(_("The system cannot void this received note for the invoice for this transaction has not been voided."));
			}else{
				updatePOEntries($ordernum);
				
				$sql = "UPDATE 0_grn_items
							SET quantity_inv = 0, qty_recd = 0
							WHERE grn_batch_id = $ordernum";
				$query = db_query($sql);
				
				$sql = "UPDATE 0_stock_moves
							SET price = 0, qty = 0, discount_percent = 0, standard_cost = 0
							WHERE trans_no = $ordernum
							AND type = 25";
				$query = db_query($sql);
				
				add_audit_trail(25, $ordernum, $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry(25, $ordernum, $date_, $vmemo);
				
				if($view == 0){
					echo "
						<script>
							alert('Receiving Note #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/purchasing/po_entry_items.php?NewOrder=Yes';
						</script>
					";
				}else if($view == 1){
					echo "
						<script>
							alert('Receiving Note #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/purchasing/inquiry/supplier_inquiry.php?';
						</script>
					";
				}
			}
			
			break;
		
		case ST_CWODELIVERY: //24
		//CWO delivery
		
			$type = ST_CWODELIVERY;
			$res2=get_gl_trans($type, $ordernum);
			$row2=db_fetch($res2);
			$tran_date=sql2date($row2['tran_date']);

			if (is_date_in_event_locker($tran_date)==1)
			{
				echo "
					<script>
						alert('Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books.');
					</script>
				";
				display_error("Not allowed to void.");
				exit();
			}
			
		
			$date_ = date('m/d/Y');
			$number = get_reference(ST_CWODELIVERY, $ordernum);
		
			updateInvoice($ordernum,24);
		
			$sql = "SELECT ov_amount
						FROM 0_supp_trans
						WHERE trans_no = $ordernum
						AND type = 24";
			$query = db_query($sql);
			$res = mysql_fetch_object($query);
			$invtotal = $res->ov_amount;
		
			$sql = "UPDATE 0_supp_trans
						SET ov_amount = 0, ov_discount = 0, ov_gst = 0, alloc = 0
						WHERE trans_no = $ordernum
						AND type = 24";
			$query = db_query($sql);
			
			$sql = "UPDATE 0_supp_invoice_items
						SET quantity = 0, unit_price = 0, unit_tax = 0, memo_ = '$vmemo'
						WHERE supp_trans_no = $ordernum";
			$query = db_query($sql);
			
			$sql = "UPDATE 0_gl_trans
						SET amount = '0.00'
						WHERE type_no = $ordernum
						AND type = 24";
			$query = db_query($sql);
			
			$sql = "SELECT amt, trans_no_from, trans_type_from
						FROM 0_supp_allocations
						WHERE trans_no_to = $ordernum
						AND trans_type_to = 24";
			$query = db_query($sql);
			if(mysql_num_rows($query) > 0){
				while($res = mysql_fetch_object($query)){
					$amt = $res->amt;
					$from_id = $res->trans_no_from;
					$from_type = $res->trans_type_from;
					updateAlloc($from_id, $from_type, $amt);
				}
			}
			
			$sql = "DELETE FROM 0_supp_allocations
						WHERE trans_no_to = $ordernum
						AND trans_type_to = 24";
			$query = db_query($sql);
			
			add_audit_trail(24, $ordernum, $date_, _("Voided.")."\n".$vmemo);
			add_voided_entry(24, $ordernum, $date_, $vmemo);
			
			if($view == 0){
				echo "
					<script>
						alert('CWO Delivery #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/purchasing/po_entry_items.php?NewOrder=Yes';
					</script>
				";
			}else if($view == 1){
				//http://localhost/HChem/purchasing/inquiry/supplier_inquiry.php?
				echo "
					<script>
						alert('CWO Delivery#$number has been voided!');
						window.parent.location.href = '".$path_to_root."/purchasing/inquiry/supplier_inquiry.php?';
					</script>
				";
			}
			
			break;
		case ST_SUPPINVOICE: //20
		
			$type = ST_SUPPINVOICE;
			$res2=get_gl_trans($type, $ordernum);
			$row2=db_fetch($res2);
			$tran_date=sql2date($row2['tran_date']);

			if (is_date_in_event_locker($tran_date)==1)
			{
				echo "
					<script>
						alert('Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books.');
					</script>
				";
				display_error("Not allowed to void.");
				exit();
			}
		
			//PO Invoice
			$date_ = date('m/d/Y');
			$number = get_reference(ST_SUPPINVOICE, $ordernum);
		
			updateInvoice($ordernum);
		
			$sql = "SELECT ov_amount
						FROM 0_supp_trans
						WHERE trans_no = $ordernum
						AND type = 20";
			$query = db_query($sql);
			$res = mysql_fetch_object($query);
			$invtotal = $res->ov_amount;
		
			$sql = "UPDATE 0_supp_trans
						SET ov_amount = 0, ov_discount = 0, ov_gst = 0, alloc = 0
						WHERE trans_no = $ordernum
						AND type = 20";
			$query = db_query($sql);
			
			$sql = "UPDATE 0_supp_invoice_items
						SET quantity = 0, unit_price = 0, unit_tax = 0, memo_ = '$vmemo'
						WHERE supp_trans_no = $ordernum";
			$query = db_query($sql);
			
			$sql = "UPDATE 0_gl_trans
						SET amount = '0.00'
						WHERE type_no = $ordernum
						AND type = 20";
			$query = db_query($sql);
			
			$sql = "SELECT amt, trans_no_from, trans_type_from
						FROM 0_supp_allocations
						WHERE trans_no_to = $ordernum
						AND trans_type_to = 20";
			$query = db_query($sql);
			if(mysql_num_rows($query) > 0){
				while($res = mysql_fetch_object($query)){
					$amt = $res->amt;
					$from_id = $res->trans_no_from;
					$from_type = $res->trans_type_from;
					updateAlloc($from_id, $from_type, $amt);
				}
			}
			
			$sql = "DELETE FROM 0_supp_allocations
						WHERE trans_no_to = $ordernum
						AND trans_type_to = 20";
			$query = db_query($sql);
			
			add_audit_trail(20, $ordernum, $date_, _("Voided.")."\n".$vmemo);
			add_voided_entry(20, $ordernum, $date_, $vmemo);
			
			if($view == 0){
				echo "
					<script>
						alert('APV #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/purchasing/po_entry_items.php?NewOrder=Yes';
					</script>
				";
			}else if($view == 1){
				//http://localhost/HChem/purchasing/inquiry/supplier_inquiry.php?
				echo "
					<script>
						alert('APV #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/purchasing/inquiry/supplier_inquiry.php?';
					</script>
				";
			}
			
			break;
		case ST_CUSTDEBITMEMO:
		case ST_CUSTCREDITMEMO:
		break;		
		case ST_SUPPDEBITMEMO:
		case ST_SUPPCREDITMEMO:
			//Memo
			$type = ST_SUPPDEBITMEMO;
			$res2=get_gl_trans($type, $ordernum);
			$row2=db_fetch($res2);
			$tran_date=sql2date($row2['tran_date']);
			
			if (is_date_in_event_locker($tran_date)==1)
			{
				echo "
					<script>
						alert('Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books.');
					</script>
				";
				display_error("Not allowed to void.");
				exit();
			}
			
			
			$date_ = date('m/d/Y');
			$number = getInvRef($ordernum);
		
			void_bank_trans($type, $ordernum, true);
			void_gl_trans($type, $ordernum, true);
			void_gl_trans_temp($type, $ordernum, true);
			void_supp_allocations($type, $ordernum);
			void_supp_trans($type, $ordernum);
			
			add_audit_trail($type, $ordernum, $date_, _("Voided.")."\n".$vmemo);
			add_voided_entry($type, $ordernum, $date_, $vmemo);
			
			$type_name = $systypes_array[$type];
			
				if($view == 0){
				echo "
					<script>
						alert('$type_name #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/purchasing/po_entry_items.php?NewOrder=Yes';
					</script>
				";
			}else if($view == 1){
				//http://localhost/HChem/purchasing/inquiry/supplier_inquiry.php?
				echo "
					<script>
						alert('$type_name #$number has been voided!');
						window.parent.location.href = '".$path_to_root."/purchasing/inquiry/supplier_inquiry.php?';
					</script>
				";
			}
			
			
			
			
			break;
		case ST_CUSTPAYMENT:
		
			$type = ST_CUSTPAYMENT;
			$res2=get_gl_trans($type, $ordernum);
			$row2=db_fetch($res2);
			$tran_date=sql2date($row2['tran_date']);

			if (is_date_in_event_locker($tran_date)==1)
			{
				echo "
					<script>
						alert('Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books.');
					</script>
				";
				display_error("Not allowed to void.");
				exit();
			}
		
				$date_ = date('m/d/Y');
				$number = getPayRef($ordernum, 12);
				
				$void_entry = get_voided_entry($type, $ordernum);
				post_void_customer_trans($type, $ordernum);
				void_books_receipts($ordernum);
				add_audit_trail(12, $ordernum, $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry(12, $ordernum, $date_, $vmemo);
				
				if($view == 2){
					echo "
						<script>
							alert('Customer Payment #$number has been voided!');
							window.parent.location.href = '".$path_to_root."/sales/inquiry/customer_inquiry.php?';
						</script>
					";
				}
			break;
			
		case ST_SUPPAYMENT:
		
				
				$type = ST_SUPPAYMENT;
				$res2=get_gl_trans($type, $ordernum);
				$row2=db_fetch($res2);
				$tran_date=sql2date($row2['tran_date']);

			if (is_date_in_event_locker($tran_date)==1)
			{
				echo "
					<script>
						alert('Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books.');
					</script>
				";
				
				display_error("Not allowed to void.");
				exit();
			}
			
		
				$date_ = Today();
				if (!exists_supp_trans($type, $ordernum))
					display_error($systypes_array[$type]." # ".get_reference_no($ordernum, $type)." does not exist.");
				
				post_void_supp_trans($type, $ordernum);
				
				add_audit_trail($type, $ordernum, $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry($type, $ordernum, $date_, $vmemo);
				
				if($view == 1){
					echo "
						<script>
							alert('Supplier Payment has been voided!');
							window.parent.location.href = '".$path_to_root."/modules/checkprint/check_list_201.php';
						</script>
					";
				}
				
			break;
		
		case ST_CV:
		
			$type = ST_CV;
			$res2=get_gl_trans($type, $ordernum);
			$row2=db_fetch($res2);
			$tran_date=sql2date($row2['tran_date']);

			if (is_date_in_event_locker($tran_date)==1)
			{
				echo "
					<script>
						alert('Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books.');
					</script>
				";
				display_error("Not allowed to void.");
				exit();
			}
		
			$date_ = Today();
			void_cv($ordernum);
			add_audit_trail($type, $ordernum, $date_, _("Voided.")."\n".$vmemo);
			add_voided_entry($type, $ordernum, $date_, $vmemo);
			
			if($view == 1 AND $_SESSION['wa_current_user']->username != 'admin')
			{
				echo "
					<script>
						alert('Check Voucher has been voided!');
						window.parent.location.href = '".$path_to_root."/modules/checkprint/check_list_201.php?';
					</script>
				";
			}
			else
			{
				echo "
					<script>
						alert('Check Voucher has been voided!');
						window.parent.location.href = '".$path_to_root."/modules/checkprint/check_list_2_fast.php?';
					</script>
				";
			}
				
			break;
		default:
				echo "<label style='font-family:arial; font-size'>To void this transaction, click <a href='../admin/void_transaction.php?'>here</a></label>";
			break;
	}
	
}else{
	$ordernum = $_GET['OrderNumber'];
	$type = $_GET['type'];
	$view = $_GET['view'];
	
	// $ttype = checkTransType($type);
	$debtors = checkVoided($ordernum, $type);
	
	$count = db_num_rows($debtors);
	
	$supp_types = array(ST_SUPPINVOICE,ST_SUPPCREDIT,ST_SUPPCREDITMEMO,ST_SUPPDEBITMEMO); //,ST_SUPPAYMENT
	
	if (in_array($type,$supp_types))
	{
		if ($cv_no = supp_trans_in_cv($type, $ordernum))
		{
			display_error("This transaction is in <b>CV # $cv_no </b>. Void the CV first");
			exit;
		}
	}
	
	if ($type == ST_CV AND cv_has_payment($ordernum))
	{
		display_error("Check has been issued to this Check Voucher. Void the Payment first");
			exit;
	}
	
	echo "<style>
			.ftitle{
				font-family:arial,helvetica;
				font-size:12px;
				font-weight:bold;
			}
			.ftitle2{
				font-family:arial,helvetica;
				font-size:12px;
			}
			legend{
				color:#045c97;
			}
			#warn{
				color:#ff0000;
			}
		</style>
		<script>
			$(document).ready(function(){
				$('#vbtn').click(function(ev){
					$('#button').slideUp('fast');
					$('#ploader').slideDown('fast');
					check(ev);
					ev.preventDefault();
				});
				
				$('#uname').click(function(ev){
					if(ev.which == 13){
						check(ev);
					}
					ev.preventDefault();
				});
				
				$('#passwd').click(function(ev){
					if(ev.which == 13){
						check(ev);
					}
					ev.preventDefault();
				});
				
				function resetBtns(){
					$('#ploader').slideUp('fast');
					$('#button').slideDown('fast');
				}
				
				function check(event){
					var uname = $('#uname').attr('value');
					var passwd = $('#passwd').attr('value');
					var reason = $('#vmemo').attr('value');
					var url = 'checkAccount.php';
					var onum = $('#OrderNumber').attr('value');
					var otype = $('#type').attr('value');
					var vmemo = $('#vmemo').attr('value');
					
					var allow = $('#allowme').attr('value');
					
					if(allow == 1){
						$('#warn').html('');
						resetBtns();
						$('form').submit();
					}else{
						if(!uname || !passwd || !reason){
							alert('Please complete the form!');
							$('#ploader').slideUp('fast');
							$('#button').slideDown('fast');
						}else{
							$.post(url, {'uname' : uname, 'passwd' : passwd, 'type' : otype}, function(res){
								// $('#warn').html(res);
								
								$('#warn').html(res);
									resetBtns();
								
								if(res == 0){
									$('#warn').html('Invalid user account! Either the user does not exist in the database or the user does not have any priviledge to proceed with the operation.');
									resetBtns();
								}else if(res == 1){
									$('#warn').html('');
									resetBtns();
									$('form').submit();
									/*postMe(onum, otype, vmemo);*/
								}else if(res == 2){
									$('#warn').html('Password mismatch!');
									resetBtns();
								}else if(res == 3){
									$('#warn').html('User has no supervisor privileges!');
									resetBtns();
								}
							});
							
						}
					}	
				}
				
				$('#redirect_lnk').click(function(ev){
					url = $(this).attr('href');
					window.parent.location.href = '".$path_to_root."' + url;
				});
			});
		</script>";
	
		$can_credit = "SELECT allow_voiding as allow FROM ".TB_PREF."company";
		
	$can_credit = db_query($can_credit);
	$is = db_fetch($can_credit);
	// display_error($is['allow']);
	
	$allow = $is['allow'];
	
	echo "<input type='hidden' id='allowme' value='$allow'>";
	
	if($count > 0){
		echo "
			<div id='warn' class='ftitle'>This transaction is already voided!</div>
		";
		
		$result = mysql_fetch_object($debtors);
		
		$date = date('F d, Y', strtotime($result->date_));
		$memo = $result->memo_;
		
		echo "
			<center>
			<div class='ftitle2'>
				<p>
				Transaction was voided on: <b>$date</b>
				<br>
				Reason: <b>$memo</b>
			</div>
			</center>
		";
	}else{
		//echo $allow;
		if(true){
			echo "
				<form method=post action=#>
					<input type='hidden' name='OrderNumber' id='OrderNumber' value='$ordernum'>
					<input type='hidden' name='type' id='type' value='$type'>
					<input type='hidden' name='view' id='view' value='$view'>
					<div id='thebox'>
			";
			if($allow == 1){
			
			}else{
				echo "	<fieldset>
							<legend class='ftitle'>Supervisor's Login</legend>
							<div id='warn' class='ftitle'></div>
							<table border='0'>
								<tr>
									<td class='ftitle'>Username</td>
									<td class='ftitle'> : </td>
									<td><input type='text' name='uname' id='uname' size='25'></td>
								</tr>
								<tr>
									<td class='ftitle'>Password</td>
									<td class='ftitle'> : </td>
									<td><input type='password' name='passwd' id='passwd' size='25'></td>
								</tr>
							</table>
						</fieldset>";
			}
					echo "
						<fieldset>
							<table border='0'>
								<legend class='ftitle'>Void Transaction</legend>
								<tr>
									<td class='ftitle'>Reason</td>
									<td class='ftitle'> : </td>
									<td><textarea name='vmemo' id='vmemo' cols='21' rows='5'></textarea></td>
								</tr>
							</table>
						</fieldset>
						<center>
							<div id='button'>
								<input type='submit' name='subbtn' value='Void' id='vbtn'>
							</div>
							<div id='ploader' style='display:none'>
								<img src='$preloader_gif'>
							</div>
						</center>
					</div>
				</form>
			";
		}
	
	}
}

?>