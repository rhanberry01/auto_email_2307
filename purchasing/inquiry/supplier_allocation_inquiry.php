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
$page_security = 'SA_SUPPLIERALLOC';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Supplier Allocation Inquiry"), false, false, "", $js);

if (isset($_GET['supplier_id']))
{
	$_POST['supplier_id'] = $_GET['supplier_id'];
}
if (isset($_GET['FromDate']))
{
	$_POST['TransAfterDate'] = $_GET['FromDate'];
}
if (isset($_GET['ToDate']))
{
	$_POST['TransToDate'] = $_GET['ToDate'];
}

//------------------------------------------------------------------------------------------------

start_form();

if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier();

start_table("class='tablestyle_noborder'");
start_row();

supplier_list_cells(_("Select a supplier: "), 'supplier_id', $_POST['supplier_id'], true);

date_cells(_("From:"), 'TransAfterDate', '', null, -30);
date_cells(_("To:"), 'TransToDate', '', null, 1);

supp_allocations_list_cell("filterType", null);

check_cells(_("show settled:"), 'showSettled', null);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');

set_global_supplier($_POST['supplier_id']);

end_row();
end_table();
//------------------------------------------------------------------------------------------------
function check_overdue($row)
{
	return ($row['TotalAmount']>$row['Allocated']) && 
		$row['OverDue'] == 1;
}

function systype_name($trans)
{
	global $systypes_array;
	
	return $systypes_array[$trans["type"]]. trade_non_trade_inv($trans["type"],$trans["trans_no"]);
}

function view_link($trans)
{
	return get_trans_view_str($trans["type"], $trans["trans_no"], $trans["reference"]);
}

function due_date($row)
{
	return (($row["type"] == ST_SUPPINVOICE) || ($row["type"]== ST_SUPPCREDIT))
		? $row["due_date"] : "";
}

function fmt_balance($row)
{
	$value = ($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT)
		? -$row["TotalAmount"] - $row["Allocated"]
		: $row["TotalAmount"] - $row["Allocated"];
	return $value;
}

function alloc_link($row)
{
	$link = 
	pager_link(_("Allocations"),
		"/purchasing/allocations/supplier_allocate.php?trans_no=" .
			$row["trans_no"]. "&trans_type=" . $row["type"], ICON_MONEY );

	return (($row["type"] == ST_BANKPAYMENT || $row["type"] == ST_SUPPCREDIT || $row["type"] == ST_SUPPAYMENT) 
		&& (-$row["TotalAmount"] - $row["Allocated"]) > 0)
		? $link : '';
}

function fmt_debit($row)
{
	$value = -$row["TotalAmount"];
	return $value>=0 ? price_format($value) : '';

}

function fmt_credit($row)
{
	$value = $row["TotalAmount"];
	return $value>0 ? price_format($value) : '';
}
//------------------------------------------------------------------------------------------------

 $date_after = date2sql($_POST['TransAfterDate']);
 $date_to = date2sql($_POST['TransToDate']);

    // Sherifoz 22.06.03 Also get the description
    $sql = "SELECT 
		trans.type, 
		trans.trans_no,
		supplier.supp_name, 
		trans.supp_reference,
    	trans.tran_date, 
		trans.due_date,
		supplier.curr_code, 
    	(trans.ov_amount + trans.ov_gst  + trans.ov_discount + trans.ewt) AS TotalAmount, 
		trans.alloc AS Allocated,
		((trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.") AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue,
		trans.reference
    	FROM "
			.TB_PREF."supp_trans as trans, "
			.TB_PREF."suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id
     	AND trans.tran_date >= '$date_after'
    	AND trans.tran_date <= '$date_to'";

   	if ($_POST['supplier_id'] != ALL_TEXT)
   		$sql .= " AND trans.supplier_id = ".db_escape($_POST['supplier_id']);
   	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT)
   	{
   		if (($_POST['filterType'] == '1') || ($_POST['filterType'] == '2'))
   		{
   			$sql .= " AND trans.type = ".ST_SUPPINVOICE." ";
   		}
   		elseif ($_POST['filterType'] == '3')
   		{
			$sql .= " AND trans.type = ".ST_SUPPAYMENT." ";
   		}
   		elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5'))
   		{
			$sql .= " AND trans.type = ".ST_SUPPCREDIT." ";
   		}

   		if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5'))
   		{
   			$today =  date2sql(Today());
			$sql .= " AND trans.due_date < '$today' ";
   		}
   	}

   	if (!check_value('showSettled'))
   	{
   		$sql .= " AND (round(abs(ov_amount + ov_gst + ov_discount + trans.ewt) - alloc,6) != 0) ";
   	}

$cols = array(
	_("Type") => array('fun'=>'systype_name', 'type'=>'nowrap'),
	_("Reference") => array('fun'=>'view_link', 'ord'=>''),
	_("Supplier") => array('ord'=>''), 
	_("Supp Reference"),
	_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'asc'),
	_("Due Date") => array('fun'=>'due_date'),
	_("Currency") => array('align'=>'center'),
	_("Debit") => array('align'=>'right', 'fun'=>'fmt_debit'), 
	_("Credit") => array('align'=>'right', 'insert'=>true, 'fun'=>'fmt_credit'), 
	_("Allocated") => 'amount', 
	_("Balance") => array('type'=>'amount', 'insert'=>true, 'fun'=>'fmt_balance'),
	array('insert'=>true, 'fun'=>'alloc_link')
	);

if ($_POST['supplier_id'] != ALL_TEXT) {
	$cols[_("Supplier")] = 'skip';
	$cols[_("Currency")] = 'skip';
}
//------------------------------------------------------------------------------------------------

$table =& new_db_pager('doc_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Highlighted items are overdue."));

$table->width = "90%";

display_db_pager($table);

end_form();
end_page();
?>
