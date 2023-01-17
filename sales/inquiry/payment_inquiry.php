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
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Payments Inquiry"), false, false, "", $js);

//------------------------------------------------------------------------------------------------

start_form();

start_table("class='tablestyle_noborder'");
start_row();

ref_cells(_("OR #:"), 'reference', '',null, '', true);

date_cells(_("From:"), 'TransAfterDate', '', null, -30);
date_cells(_("To:"), 'TransToDate', '', null, 1);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();


//------------------------------------------------------------------------------------------------

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
	return $row['order_']>0 ? get_customer_trans_view_str(ST_SALESORDER, $row['order_'], getSORef($row['order_']))	:  "";
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
	    $row['type']==ST_CUSTCREDIT || $row['type']==ST_CUSTPAYMENT || $row['type']==ST_BANKDEPOSIT ?
		-$row["TotalAmount"] : $row["TotalAmount"];
	return $value>=0 ? price_format($value) : '';

}

function fmt_credit($row)
{
	$value =
	    !($row['type']==ST_CUSTCREDIT || $row['type']==ST_CUSTPAYMENT || $row['type']==ST_BANKDEPOSIT) ?
		-$row["TotalAmount"] : $row["TotalAmount"];
	return $value>0 ? price_format($value) : '';
}

function credit_link($row)
{
	return $row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		pager_link(_("Credit This"),
			"/sales/customer_credit_invoice.php?InvoiceNumber=".
			$row['trans_no'], ICON_CREDIT)
			: '';
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
		trans.tran_date, 
		debtor.name, 
		branch.br_name,
		debtor.curr_code,
		(trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount + trans.ewt + trans.tracking + 
			(trans.ov_amount  ) )	AS TotalAmount, "; 
   	if ($_POST['filterType'] != ALL_TEXT)
		$sql .= "@bal := @bal+(
					trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount +
					(trans.ov_amount )
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
			AND trans.type = 12
			AND trans.branch_code = branch.branch_code";

   	if (isset($_POST['reference']) && $_POST['reference'] != "")
	{
		$delivery = "%".$_POST['reference'];
		$sql .= " AND trans.reference LIKE ".db_escape($delivery);
		$sql .= " GROUP BY trans.trans_no";
	}

//------------------------------------------------------------------------------------------------
db_query("set @bal:=0");

$cols = array(
	_("Type") => array('fun'=>'systype_name', 'ord'=>'', 'type'=>'nowrap'),
	_("OR/PR #") => array('fun'=>'trans_view', 'ord'=>''),
	_("OR/PR Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'),
	_("Customer") => array('ord'=>''), 
	_("Branch") => array('ord'=>''), 
	_("Currency") => array('align'=>'center'),
	_("Debit") => array('align'=>'right', 'fun'=>'fmt_debit'), 
	_("Credit") => array('align'=>'right','insert'=>true, 'fun'=>'fmt_credit'), 
	_("RB") => array('align'=>'right', 'type'=>'amount'),
		array('insert'=>true, 'fun'=>'gl_view')
	);


$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Highlighted items are overdue."));

$table->width = "85%";

display_db_pager($table);

end_form();
end_page();

?>
