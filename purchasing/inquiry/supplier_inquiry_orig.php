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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager2.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
$js .= "
	function openThickBox(id, typex,txt){
		// if(type == 25){
		// 	ttype = 5;
		// }else if(type == 20){
		// 	ttype = 6;
		// }
		url = '../../sales/customer_del_so.php?OrderNumber=' + id + '&view=1&type=' + typex + '&KeepThis=true&TB_iframe=true&height=280&width=300';
		tb_show('Void '+txt, url);
	}
";	
	
page(_($help_context = "Supplier Transaction Inquiry"), false, false, "", $js);

if (isset($_GET['supplier_id'])){
	$_POST['supplier_id'] = $_GET['supplier_id'];
}
if (isset($_GET['FromDate'])){
	$_POST['TransAfterDate'] = $_GET['FromDate'];
}
if (isset($_GET['ToDate'])){
	$_POST['TransToDate'] = $_GET['ToDate'];
}

//------------------------------------------------------------------------------------------------

start_form();

/*if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier();*/

start_table("class='tablestyle_noborder'");
start_row();

ref_cells('Reference #', 'ref');
supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true);

date_cells(_("From:"), 'TransAfterDate', '', null, -30);
date_cells(_("To:"), 'TransToDate');

supp_allocations_list_cell("filterType", null);
	
submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');

end_row();
end_table();
set_global_supplier($_POST['supplier_id']);

//------------------------------------------------------------------------------------------------

function display_supplier_summary($supplier_record)
{
	global $table_style;

	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	$nowdue = "1-" . $past1 . " " . _('Days');
	$pastdue1 = $past1 + 1 . "-" . $past2 . " " . _('Days');
	$pastdue2 = _('Over') . " " . $past2 . " " . _('Days');
	

    start_table("width=80% $table_style");
    $th = array(_("Currency"), _("Terms"), _("Current"), $nowdue,
    	$pastdue1, $pastdue2, _("Total Balance"));

	table_header($th);
    start_row();
	label_cell($supplier_record["curr_code"]);
    label_cell($supplier_record["terms"]);
    amount_cell($supplier_record["Balance"] - $supplier_record["Due"]);
    amount_cell($supplier_record["Due"] - $supplier_record["Overdue1"]);
    amount_cell($supplier_record["Overdue1"] - $supplier_record["Overdue2"]);
    amount_cell($supplier_record["Overdue2"]);
    amount_cell($supplier_record["Balance"]);
    end_row();
    end_table(1);
}
//------------------------------------------------------------------------------------------------

/*div_start('totals_tbl');
if (($_POST['supplier_id'] != "") && ($_POST['supplier_id'] != ALL_TEXT))
{
	$supplier_record = get_supplier_details($_POST['supplier_id']);
    display_supplier_summary($supplier_record);
}
div_end();
*/
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

function trans_view($trans)
{
	if($trans["type"] == ST_SUPPDEBITMEMO || $trans["type"] == ST_SUPPCREDITMEMO)
		return get_gl_view_str($trans["type"], $trans["trans_no"], $trans["reference"]);
	else
		return get_trans_view_str($trans["type"], $trans["trans_no"], $trans["reference"]);
}

function comment_string($row)
{
	return get_comments_string($row["type"], $row["trans_no"]);
}

function due_date($row)
{
	return ($row["type"]== ST_SUPPINVOICE) || ($row["type"]== ST_SUPPCREDIT) ? $row["due_date"] : '';
}

function gl_view($row)
{
	return get_gl_view_str($row["type"], $row["trans_no"]);
}

function credit_link($row)
{
	// return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		// pager_link(_("Purchase Return"),
			// "/purchasing/supplier_credit.php?New=1&invoice_no=".
			// $row['trans_no'], ICON_CREDIT)
			// : '';
	return '';
}

function cv_no_link($row)
{
	// return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		// pager_link(_("Purchase Return"),
			// "/purchasing/supplier_credit.php?New=1&invoice_no=".
			// $row['trans_no'], ICON_CREDIT)
			// : '';
	global $path_to_root;
	return "<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?
				cv_id=".($row['cv_id'])."'onclick=\"javascript:openWindow(this.href,this.target); return false;\"><b>" .
				$row['cv_no'] . "&nbsp;</b></a> ";
}

function fmt_debit2($row)
{
	$value = -$row["TotalAmount"];
	return $value>=0 ? price_format($value) : '';

}

function fmt_credit2($row)
{
	$value = $row["TotalAmount"];
	return $value>0 ? price_format($value) : '';
}

function prt_link($row)
{
  	if ($row['type'] == ST_SUPPAYMENT || $row['type'] == ST_BANKPAYMENT || $row['type'] == ST_SUPPCREDIT) 
 		return print_document_link($row['trans_no']."-".$row['type'], _("Print Remittance"), true, ST_SUPPAYMENT, ICON_PRINT);
}

function void_link($row){
	// $ex=array(ST_CUSTDEBITMEMO,ST_CUSTCREDITMEMO,ST_SUPPDEBITMEMO,ST_SUPPCREDITMEMO);
	global $systypes_array;
	if($row['type'] == 20){
		if($row['non_trade'] == 1){
			if (get_voided_entry($row['type'], $row['trans_no']) === false) //&&!in_array($row['type'],$ex)
				return "<img style='cursor:pointer' title='Void' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['trans_no'].", ".$row['type'].",\"".$systypes_array[$row['type']]."\")'>"; 
		}
	}else{
		if (get_voided_entry($row['type'], $row['trans_no']) === false) //&&!in_array($row['type'],$ex)
				return "<img style='cursor:pointer' title='Void' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['trans_no'].", ".$row['type'].",\"".$systypes_array[$row['type']]."\")'>"; 
		
	}
}

function check_overdue($row)
{
	return $row['OverDue'] == 1
		&& (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
}

function special_ref($row)
{
	if ($row['type'] == ST_SUPPINVOICE)
	{
		return 'PO#'.$row['special_reference'];
	}
	else if ($row['type'] == ST_SUPPDEBITMEMO AND $row['special_reference']!= '')
	{
		return get_sdma_ref($row['special_reference']);
	}
	else
		return '';
}
//------------------------------------------------------------------------------------------------

    $date_after = date2sql($_POST['TransAfterDate']);
    $date_to = date2sql($_POST['TransToDate']);

    // Sherifoz 22.06.03 Also get the description
		// supplier.curr_code, 
    $sql = "SELECT trans.type, 
		trans.trans_no,
		supplier.supp_name, 
		trans.supp_reference,
		1,
		trans.special_reference,
    	trans.tran_date, 
		trans.due_date,
    	(trans.ov_amount + trans.ov_gst  - trans.ov_discount - trans.ewt ) AS TotalAmount, 
		trans.alloc AS Allocated,
		((trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.") AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue,
    	(ABS(trans.ov_amount + trans.ov_gst  - trans.ov_discount - trans.ewt - trans.alloc) <= 0.005) AS Settled,
		trans.reference,
		cv_no, trans.cv_id,  trans.non_trade 
		FROM (".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as supplier)
		LEFT OUTER JOIN 0_cv_header as cv_head
		ON trans.cv_id = cv_head.id
    	WHERE supplier.supplier_id = trans.supplier_id
     	";	// exclude voided transactions
	
	if ($_POST['ref'] != '')
		$sql .= " AND (trans.reference LIKE '%".$_POST['ref']."%'
						OR trans.supp_reference LIKE '%".$_POST['ref']."%') and trans_no NOT IN(SELECT trans_no from 0_discrepancy WHERE status = 0)";
	else	
		$sql .= " AND trans.tran_date >= '$date_after'
				AND trans.tran_date <= '$date_to'
				AND trans.ov_amount != 0 and trans_no NOT IN(SELECT trans_no from 0_discrepancy WHERE status = 0) ";
		// + trans.ewt
   	if ($_POST['supplier_id'] != ALL_TEXT)
   		$sql .= " AND trans.supplier_id = ".db_escape($_POST['supplier_id']);
   	if (isset($_POST['filterType']) && $_POST['filterType'] != ALL_TEXT)
   	{
   		if (($_POST['filterType'] == '1')) 
   		{
   			$sql .= " AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_BANKDEPOSIT.")";
   		} 
   		elseif (($_POST['filterType'] == '2')) 
   		{
   			$sql .= " AND trans.type = ".ST_SUPPINVOICE." ";
   		} 
		elseif (($_POST['filterType'] == '24')) 
   		{
   			$sql .= " AND trans.type = ".ST_CWODELIVERY." ";
   		} 
   		elseif ($_POST['filterType'] == '3') 
   		{
			$sql .= " AND (trans.type = ".ST_SUPPAYMENT." OR trans.type = ".ST_BANKPAYMENT.") ";
   		} 
   		elseif (($_POST['filterType'] == '4') || ($_POST['filterType'] == '5')) 
   		{
			$sql .= " AND trans.type = ".ST_SUPPCREDIT."  ";
   		}
		elseif ($_POST['filterType'] == '6') // debit memo
   		{
			$sql .= " AND trans.type = ".ST_SUPPDEBITMEMO."  ";
   		}
		elseif ($_POST['filterType'] == '7') // credit memo
   		{
			$sql .= " AND trans.type = ".ST_SUPPCREDITMEMO."  ";
   		}
		else if (($_POST['filterType'] == '8')) // ap trade
   		{
   			$sql .= " AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_BANKDEPOSIT.")
					  AND reference NOT LIKE ('NT%')
					  AND reference NOT LIKE ('RR%')";
			// $sql .= " AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_BANKDEPOSIT.") 
					// AND	(SELECT COUNT(*) FROM ".TB_PREF."supp_invoice_items 
						// WHERE supp_trans_no = trans.trans_no AND supp_trans_type = trans.type AND gl_code = 0) > 0";
   		} 
		if (($_POST['filterType'] == '9')) // ap non-trade
   		{
			$sql .= " AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_BANKDEPOSIT.")
					  AND (reference LIKE ('NT%')
					  OR reference LIKE ('RR%'))";
   			// $sql .= " AND (trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_BANKDEPOSIT.") 
					// AND	(SELECT COUNT(*) FROM ".TB_PREF."supp_invoice_items 
						// WHERE supp_trans_no = trans.trans_no AND supp_trans_type = trans.type AND gl_code = 0) = 0";
   		} 

   		if (($_POST['filterType'] == '2') || ($_POST['filterType'] == '5')) 
   		{
   			$today =  date2sql(Today());
			$sql .= " AND trans.due_date < '$today' AND (ABS(trans.ov_amount + trans.ov_gst  + trans.ov_discount+trans.ewt)-trans.alloc)!=0";
   		}

   	}
   		//display_error($sql);

$cols = array(
			_("Type") => array('fun'=>'systype_name', 'ord'=>'','type'=>'nowrap'), 
			_("Trxn #") => array('fun'=>'trans_view', 'ord'=>''), 
			_("Supplier"),
			_("Supplier's Reference"),
			_("Other Reference") => array('fun'=>'special_ref'),
			_("Comment") => array('fun'=>'comment_string'),
			_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'), 
			_("Due Date") => array('type'=>'date', 'fun'=>'due_date'), 
			// _("Currency") => array('align'=>'center'),
			_("Debit") => array('align'=>'right', 'fun'=>'fmt_debit2'), 
			_("Credit") => array('align'=>'right', 'insert'=>true,'fun'=>'fmt_credit2'), 
			array('insert'=>true, 'fun'=>'gl_view'),
			"CV #" => array('fun'=>'cv_no_link'),
			array('insert'=>true, 'fun'=>'prt_link'),
			// array('insert'=>true, 'fun'=>'void_link')
			);

			
// $u = get_user($_SESSION["wa_current_user"]->user);
// $approver= $u['user_id'];

	// if ($approver=='admin') {
			array_push($cols, array('insert'=>true, 'fun'=>'void_link'));
	//}

/*if ($_POST['supplier_id'] != ALL_TEXT)
{
	$cols[_("Supplier")] = 'skip';
	$cols[_("Currency")] = 'skip';
}*/
//------------------------------------------------------------------------------------------------


/*show a table of the transactions returned by the sql */
// display_error($sql);
$table =& new_db_pager('trans_tbl', $sql, $cols);
$table->set_marker('check_overdue', _("Highlighted items are overdue."));

$table->width = "85%";

display_db_pager($table);

end_form();
end_page();

?>
