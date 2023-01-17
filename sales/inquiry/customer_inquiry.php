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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

add_js_ufile($path_to_root.'/js/thickbox.js');
echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />
";

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
// $js .= "
	// function openThickBox(id, type){
		// if(type == 13){
			// ttype = 2;
		// }else if(type == 10){
			// ttype = 3;
		// }
		// url = '../customer_del_so.php?OrderNumber=' + id + '&view=2&type=' + ttype + '&KeepThis=true&TB_iframe=true&height=280&width=300';
		// tb_show('Void Transaction', url);
	// }
// ";

$js .= "
	function openThickBox(id,typex,txt){
		//alert(typex);
		url = '../customer_del_so.php?OrderNumber=' + id + '&view=2&type='+typex+'&KeepThis=true&TB_iframe=true&height=280&width=300';
		tb_show('Void '+txt, url);
	}
	
	var message =  '<table>'+
					  '<tr>Editing transaction requires supervisor approval. Please enter supervisor account for approval.<br></tr>'+
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
								url = id;
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
		url = id;
		$(location).attr('href',url);
		/* $.prompt(message,{
					buttons: { Ok:true, 
								   Cancel:false },
					callback: post_form
				},id); */
	}
";
	
page(_($help_context = "Customer Transaction Inquiry"), false, false, "", $js);


if (isset($_GET['customer_id']))
{
	$_POST['customer_id'] = $_GET['customer_id'];
}

//------------------------------------------------------------------------------------------------

start_form();
/*
if (!isset($_POST['customer_id']))
	$_POST['customer_id'] = get_global_customer();*/

start_table("class='tablestyle_noborder'");
start_row();

customer_list_cells(_("Select a customer: "), 'customer_id', null, true);

date_cells(_("From:"), 'TransAfterDate', '', null, -30);
date_cells(_("To:"), 'TransToDate', '', null, 1);

if (!isset($_POST['filterType']))
	$_POST['filterType'] = 0;

cust_allocations_list_cells(null, 'filterType', $_POST['filterType'], true);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();

set_global_customer($_POST['customer_id']);

//------------------------------------------------------------------------------------------------

function display_customer_summary($customer_record)
{
	global $table_style;

	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
    if ($customer_record["dissallow_invoices"] != 0)
    {
    	echo "<center><font color=red size=4><b>" . _("CUSTOMER ACCOUNT IS ON HOLD") . "</font></b></center>";
    }

	$nowdue = "1-" . $past1 . " " . _('Days');
	$pastdue1 = $past1 + 1 . "-" . $past2 . " " . _('Days');
	$pastdue2 = _('Over') . " " . $past2 . " " . _('Days');

    start_table("width=80% $table_style");
    $th = array(_("Currency"), _("Terms"), _("Current"), $nowdue,
    	$pastdue1, $pastdue2, _("Total Balance"));
    table_header($th);

	start_row();
    label_cell($customer_record["curr_code"]);
    label_cell($customer_record["terms"]);
	amount_cell($customer_record["Balance"] - $customer_record["Due"]);
	amount_cell($customer_record["Due"] - $customer_record["Overdue1"]);
	amount_cell($customer_record["Overdue1"] - $customer_record["Overdue2"]);
	amount_cell($customer_record["Overdue2"]);
	amount_cell($customer_record["Balance"]);
	end_row();

	end_table();
}
//------------------------------------------------------------------------------------------------

/*div_start('totals_tbl');
if ($_POST['customer_id'] != "" && $_POST['customer_id'] != ALL_TEXT)
{
	$customer_record = get_customer_details($_POST['customer_id'], $_POST['TransToDate']);
    display_customer_summary($customer_record);
    echo "<br>";
}
div_end();*/

if(get_post('RefreshInquiry'))
{
	$Ajax->activate('totals_tbl');
}
//------------------------------------------------------------------------------------------------

function systype_name($trans)
{
	global $systypes_array;
	
	return $systypes_array[$trans["type"]]. trade_non_trade_inv($trans["type"],$trans["trans_no"]);
}

function order_view($row)
{
	// return $row['order_'] != 'auto' ? get_customer_trans_view_str(ST_SALESORDER, $row['order_'], getSORef($row['order_']))	: "";
	if ($row['order_'] != 0)
	{
		$so_ref = getSORef($row['order_']);
		
		if ($so_ref != 'auto')
		return get_customer_trans_view_str(ST_SALESORDER, $row['order_'], getSORef($row['order_']));
	}
	
	return '';
}

function trans_view($trans)
{
	return get_trans_view_str($trans["type"], $trans["trans_no"], $trans['reference']);
}

function due_date($row)
{
	return	$row["type"] == ST_SALESINVOICE	? $row["due_date"] : '';
}

function gl_view($row)
{
	return get_gl_view_str($row["type"], $row["trans_no"]);
}

function fmt_debit($row)
{
	$value =
	    $row['type']==ST_CUSTCREDIT || $row['type']==ST_CUSTCREDITMEMO || $row['type']==ST_CUSTPAYMENT || $row['type']==ST_BANKDEPOSIT ?
		-$row["TotalAmount"] : $row["TotalAmount"];
	return $value>=0 ? price_format($value) : '';

}

function fmt_credit($row)
{
	$value =
	    !($row['type']==ST_CUSTCREDIT || $row['type']==ST_CUSTCREDITMEMO || $row['type']==ST_CUSTPAYMENT || $row['type']==ST_BANKDEPOSIT) ?
		-$row["TotalAmount"] : $row["TotalAmount"];
	return $value>0 ? price_format($value) : '';
}

function credit_link($row)
{
	return $row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		pager_link(_("Sales Return"),
			"/sales/customer_credit_invoice.php?InvoiceNumber=".
			$row['trans_no'], ICON_CREDIT)
			: '';
}

function edit_link($row)
{
	$str = '';
	$path_to_root = "..";
	
	switch($row['type']) {
	case ST_SALESINVOICE:
		if (get_voided_entry(ST_SALESINVOICE, $row["trans_no"]) === false && $row['Allocated'] == 0)
			$str = "/customer_invoice.php?ModifyInvoice=".$row['trans_no'];
		break;
	case ST_CUSTCREDIT:
  		if (get_voided_entry(ST_CUSTCREDIT, $row["trans_no"]) === false && $row['Allocated'] == 0) // 2008-11-19 Joe Hunt
		{	 
			if ($row['order_']==0) // free-hand credit note
			    $str = "/credit_note_entry.php?ModifyCredit=".$row['trans_no'];
			else	// credit invoice
			    $str = "/customer_credit_invoice.php?ModifyCredit=".$row['trans_no'];
		}	    
		break;
	 case ST_CUSTDELIVERY:
  		if (get_voided_entry(ST_CUSTDELIVERY, $row["trans_no"]) === false)
   			$str = "/customer_delivery.php?ModifyDelivery=".$row['trans_no'];
		break;
	}
	
	if ($str != "" && !is_closed_trans($row['type'], $row["trans_no"])){
		if($row['type'] == ST_CUSTDELIVERY){
			$delivery = get_customer_trans($row['trans_no'], 13);
			if (get_voided_entry(ST_CUSTDELIVERY, $delivery['trans_link']) === false){
				// return pager_link(_('Edit'), $str, ICON_EDIT);
				$str = $path_to_root.$str;
				return "<img style='cursor:pointer' title='Edit' src='../../themes/modern/images/edit.gif' height=12 width=12 onclick='approval(\"$str\")'>"; 
			}else{
				
			}
		}elseif($row['type'] == ST_SALESINVOICE){
		
		}else{
			$str = $path_to_root.$str;
			return "<img style='cursor:pointer' title='Edit' src='../../themes/modern/images/edit.gif' height=12 width=12 onclick='approval(\"$str\")'>"; 
			// return pager_link(_('Edit'), $str, ICON_EDIT);
		}
	}
	return '';	
}

function prt_link($row)
{
  	if ($row['type'] != ST_CUSTPAYMENT && $row['type'] != ST_BANKDEPOSIT && $row['type'] != ST_CUSTCREDIT &&$row['type'] != ST_CUSTDEBITMEMO && $row['type'] != ST_CUSTCREDITMEMO){ // customer payment or bank deposit printout not defined yet.
 	if (get_voided_entry($row['type'], $row['trans_no']) === false)
 		return print_document_link($row['trans_no']."-".$row['type'], _("Print"), true, $row['type'], ICON_PRINT);
 	}else if($row['type'] != ST_CUSTDEBITMEMO && $row['type'] != ST_CUSTCREDITMEMO){
if (get_voided_entry($row['type'], $row['trans_no']) === false)
		return print_document_link($row['trans_no']."-".$row['type'], _("Print Receipt"), true, ST_CUSTPAYMENT, ICON_PRINT);
	}
}

function trans_void_link($row)
{
	global $systypes_array;
		
	$ex=array(ST_CUSTDEBITMEMO,ST_CUSTCREDITMEMO,ST_SUPPDEBITMEMO,ST_SUPPCREDITMEMO);
	if($row['type'] == ST_CUSTDELIVERY){
		$delivery = get_customer_trans($row['trans_no'], 13);
		if (get_voided_entry(ST_CUSTDELIVERY, $row['trans_no']) === false){
			return "<img style='cursor:pointer' title='Void' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['trans_no'].", ".$row['type'].",\"".$systypes_array[$row['type']]."\")'>"; 
		}
	}elseif($row['type'] == ST_SALESINVOICE){
		if (get_voided_entry(ST_SALESINVOICE, $row["trans_no"]) === false && $row['Allocated'] == 0){
			return "<img style='cursor:pointer' title='Void' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['trans_no'].", ".$row['type'].",\"".$systypes_array[$row['type']]."\")'>"; 
		}
	}elseif($row['type'] == ST_CUSTCREDIT){
	
	}elseif($row['type'] == ST_CUSTPAYMENT){
		if (get_voided_entry(ST_CUSTPAYMENT, $row['trans_no']) === false){
			return "<img style='cursor:pointer' title='Void' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['trans_no'].", ".$row['type'].",\"".$systypes_array[$row['type']]."\")'>"; 
		}
	}else{
		if(!in_array($row['type'],$ex))
		return "<img style='cursor:pointer' title='Void' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['trans_no'].", ".$row['type'].",\"".$systypes_array[$row['type']]."\")'>"; 
	}
}

function check_overdue($row)
{
	return $row['OverDue'] == 1
		&& (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
}
//------------------------------------------------------------------------------------------------
    $date_after = date2sql($_POST['TransAfterDate']);
    $date_to = date2sql($_POST['TransToDate']);

  $sql = "SELECT 
  		trans.type, 
		trans.trans_no, 
		trans.order_, 
		
		trans.tran_date, 
		trans.due_date, 
		debtor.name, 
		branch.br_name,
		debtor.curr_code,
		(trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount + trans.ewt + trans.tracking + 
			(trans.ov_amount  ) )	AS TotalAmount, "; 
   	if ($_POST['filterType'] != ALL_TEXT)
		$sql .= "@bal := @bal+(
					trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount +
					(trans.ov_amount)
					), ";

//	else
//		$sql .= "IF(trans.type=".ST_CUSTDELIVERY.",'', IF(trans.type=".ST_SALESINVOICE." OR trans.type=".ST_BANKPAYMENT.",@bal := @bal+
//			(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), @bal := @bal-
//			(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount))) , ";
		$sql .= "trans.alloc AS Allocated, trans.reference, 
		((trans.type = ".ST_SALESINVOICE.")
			AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue
		FROM "
			.TB_PREF."debtor_trans as trans, "
			.TB_PREF."debtors_master as debtor, "
			.TB_PREF."cust_branch as branch
		WHERE debtor.debtor_no = trans.debtor_no
			AND trans.tran_date >= '$date_after'
			AND trans.tran_date <= '$date_to'
			AND trans.skip_dr = 0
			AND trans.reference != 'auto'
			AND trans.branch_code = branch.branch_code";

   	if ($_POST['customer_id'] != ALL_TEXT)
   		$sql .= " AND trans.debtor_no = ".db_escape($_POST['customer_id']);

   	if ($_POST['filterType'] != ALL_TEXT)
   	{
   		if ($_POST['filterType'] == '1')
   		{
   			$sql .= " AND (trans.type = ".ST_SALESINVOICE." OR trans.type = ".ST_BANKPAYMENT.") ";
   		}
   		elseif ($_POST['filterType'] == '2')
   		{
   			$sql .= " AND (trans.type = ".ST_SALESINVOICE.") ";
   		}
   		elseif ($_POST['filterType'] == '3')
   		{
			$sql .= " AND (trans.type = " . ST_CUSTPAYMENT 
					." OR trans.type = ".ST_BANKDEPOSIT.") ";
   		}
   		elseif ($_POST['filterType'] == '4')
   		{
			$sql .= " AND trans.type = ".ST_CUSTCREDIT." ";
   		}
   		elseif ($_POST['filterType'] == '5')
   		{
			$sql .= " AND trans.type = ".ST_CUSTDELIVERY." ";
   		}

    	if ($_POST['filterType'] == '2')
    	{
    		$today =  date2sql(Today());
    		$sql .= " AND trans.due_date < '$today'
				AND (trans.ov_amount + trans.ov_gst + trans.ov_freight_tax + 
				trans.ov_freight + trans.ov_discount - trans.alloc > 0) ";
    	}
   	}
	$sql .= " ORDER BY reference";

//------------------------------------------------------------------------------------------------
db_query("set @bal:=0");

$cols = array(
	_("Type") => array('fun'=>'systype_name', 'ord'=>'', 'type'=>'nowrap'),
	_("Trxn #") => array('fun'=>'trans_view', 'ord'=>'', 'type'=>'nowrap'),
	_("Order") => array('fun'=>'order_view'), 

	_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'),
	_("Due Date") => array('type'=>'date', 'fun'=>'due_date'),
	_("Customer") => array('ord'=>''), 
	_("Branch") => array('ord'=>''), 
	_("Currency") => array('align'=>'center'),
	_("Debit") => array('align'=>'right', 'fun'=>'fmt_debit'), 
	_("Credit") => array('align'=>'right','insert'=>true, 'fun'=>'fmt_credit'), 
	_("RB") => array('align'=>'right', 'type'=>'amount'),
		array('insert'=>true, 'fun'=>'gl_view'),
		array('insert'=>true, 'fun'=>'credit_link'),
		array('insert'=>true, 'fun'=>'edit_link'),
		array('insert'=>true, 'fun'=>'prt_link'),
		array('insert'=>true, 'fun'=>'trans_void_link')
	);


/*if ($_POST['customer_id'] != ALL_TEXT) {
	$cols[_("Customer")] = 'skip';
	$cols[_("Currency")] = 'skip';
}*/
if ($_POST['filterType'] == ALL_TEXT)
	$cols[_("RB")] = 'skip';

$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Highlighted items are overdue."));

$table->width = "85%";

display_db_pager($table);

end_form();
end_page();

?>
