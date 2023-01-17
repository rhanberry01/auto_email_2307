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
$page_security = 'SA_SUPPLIERCREDIT';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager2.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/reporting/includes/reporting.inc");

$js = "
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
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
	
page(_($help_context = "Void Debit Memo"), false, false, "", $js);

$sql = "SELECT can_void FROM 0_users WHERE id=".$_SESSION['wa_current_user']->user;
$res = db_query($sql);
$row = db_fetch($res);

if ($row[0] != 1)
{
	display_error('You are not allowed to void Debit Memo');
	display_footer_exit();
}

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

//------------------------------------------------------------------------------------------------

if (isset($_GET['Voided']))
{
	display_notification('Debit Memo/s successfully voided.');
	hyperlink_params($_SERVER['PHP_SELF'], _("Void other Debit Memo"), "New=Yes");
	display_footer_exit();
}

if (isset($_POST['CancelVoiding']))
{
	meta_forward($_SERVER['PHP_SELF'], "New=yes");
}

if (isset($_POST['ConfirmVoiding']))
{
	$ids = explode(',',$_POST['id_string']);
	
	foreach($ids as $id)
		void_dm($id, $_POST['memo_']);
		
	meta_forward($_SERVER['PHP_SELF'], "Voided=yes");
}

if (isset($_POST['void_checked']))
{
	$id = array();
	
    foreach($_POST as $postkey=>$postval )
    {
		if (strpos($postkey, 'void_me') === 0)
		{
			$id[] = substr($postkey, strlen('void_me'));
		}
    }
   
	if (count($id)>0)
	{
		$id_string = implode(',',$id);
		start_table($table_style2);
		
		hidden('id_string',$id_string);
		textarea_row(_("Memo:"), 'memo_', null, 30, 4);
		end_table(1);
		submit_center_first('ConfirmVoiding', _("Proceed"), '', false);
		submit_center_last('CancelVoiding', _("Cancel"), '', 'cancel');
		
		br();
		
		$th = array('Trxn #', 'Supplier', "Supplier's Reference", 'Comment', 'Date', 'Debit');
		
		start_table($table_style2);
		table_header($th);
		
		$sql = "SELECT trans.*, supp_name,
					(trans.ov_amount + trans.ov_gst  - trans.ov_discount - trans.ewt ) AS TotalAmount
				FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as supplier
				WHERE supplier.supplier_id = trans.supplier_id	
				AND type = ".ST_SUPPDEBITMEMO."
				AND trans_no IN(".$id_string.")";
		$res = db_query($sql);
		
		
		$tot = $k = 0;
		while($row = db_fetch($res))
		{
			$value = -$row["TotalAmount"];
			$tot += $value;
			alt_table_row_color($k);
			label_cell(get_gl_view_str($row["type"], $row["trans_no"], $row["reference"]));
			label_cell($row['supp_name']);
			label_cell($row['supp_reference']);
			label_cell(get_comments_string($row["type"], $row["trans_no"]));
			label_cell(sql2date($row['tran_date']));
			label_cell($value >=0 ? price_format($value) : '','align=right');
			end_row();
		}
		alt_table_row_color($k);
		label_cell('<b>TOTAL:<b>','align=right colspan=5');
		amount_cell($tot,true);
		end_row();
		
		end_table();
		display_footer_exit();
	}
}

/*if (!isset($_POST['supplier_id']))
	$_POST['supplier_id'] = get_global_supplier();*/

start_table("class='tablestyle_noborder'");
start_row();

supplier_list_cells(_("Select a supplier:"), 'supplier_id', null, true);

date_cells(_("From:"), 'TransAfterDate', '', null, -30);
date_cells(_("To:"), 'TransToDate');

// supp_allocations_list_cell("filterType", null);
$_POST['filterType'] = 6;
hidden('filterType',$_POST['filterType']);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');

end_row();
end_table();
set_global_supplier($_POST['supplier_id']);

//------------------------------------------------------------------------------------------------

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
	return $row['type'] == ST_SUPPINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
		pager_link(_("Purchase Return"),
			"/purchasing/supplier_credit.php?New=1&invoice_no=".
			$row['trans_no'], ICON_CREDIT)
			: '';
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
	if (get_voided_entry($row['type'], $row['trans_no']) === false) //&&!in_array($row['type'],$ex)
	return "<img style='cursor:pointer' title='Void' src='../../themes/modern/images/remove.png' onclick='openThickBox(".$row['trans_no'].", ".$row['type'].",\"".$systypes_array[$row['type']]."\")'>"; 
}

function check_overdue($row)
{
	return $row['OverDue'] == 1
		&& (abs($row["TotalAmount"]) - $row["Allocated"] != 0);
}

function void_checked_button($row)
{
	return checkbox(null, 'void_me'.$row['trans_no'], null, false, false);
}



//------------------------------------------------------------------------------------------------
function void_dm($trans_no, $vmemo)
{
	$ordernum = $trans_no;
	$type = ST_SUPPDEBITMEMO;
	
	
	$res2=get_gl_trans($type, $ordernum);
	$row2=db_fetch($res2);
	$tran_date=sql2date($row2['tran_date']);
	
	
	if (is_date_in_event_locker($tran_date)==1)
	{
		display_error(_("Transaction is INVALID, Date of transaction is included to CLOSED ACCOUNTING BOOKS. To continue, Please ask permission from Accounting Head to UNLOCK the books."));
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
}
//------------------------------------------------------------------------------------------------

    $date_after = date2sql($_POST['TransAfterDate']);
    $date_to = date2sql($_POST['TransToDate']);

    // Sherifoz 22.06.03 Also get the description
		// supplier.curr_code, 
    $sql = "SELECT 1,
		trans.type, 
		trans.trans_no,
		supplier.supp_name, 
		trans.supp_reference,
		1,
    	trans.tran_date, 
		trans.due_date,
    	(trans.ov_amount + trans.ov_gst  - trans.ov_discount - trans.ewt ) AS TotalAmount, 
		trans.alloc AS Allocated,
		((trans.type = ".ST_SUPPINVOICE." OR trans.type = ".ST_SUPPCREDIT.") AND trans.due_date < '" . date2sql(Today()) . "') AS OverDue,
    	(ABS(trans.ov_amount + trans.ov_gst  - trans.ov_discount - trans.ewt - trans.alloc) <= 0.005) AS Settled,
		trans.reference
		FROM ".TB_PREF."supp_trans as trans, ".TB_PREF."suppliers as supplier
    	WHERE supplier.supplier_id = trans.supplier_id
     	AND trans.tran_date >= '$date_after'
    	AND trans.tran_date <= '$date_to'
		AND trans.ov_amount != 0
		AND trans.type = ".ST_SUPPDEBITMEMO."
		AND cv_id = 0";	// exclude voided transactions
		
		// + trans.ewt
   	if ($_POST['supplier_id'] != ALL_TEXT)
   		$sql .= " AND trans.supplier_id = ".db_escape($_POST['supplier_id']);
	

$cols = array(
			submit('void_checked', 'Void Checked', false, 'Void Checked Debit Memo') => 
				array('fun'=>'void_checked_button','align'=>'center'),
			_("Type") => array('fun'=>'systype_name','type'=>'nowrap'), 
			_("Trxn #") => array('fun'=>'trans_view', 'ord'=>'desc'), 
			_("Supplier") => array('ord'=>''),
			_("Supplier's Reference"), 
			_("Comment") => array('fun'=>'comment_string'),
			_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'), 
			// _("Due Date") => array('type'=>'date', 'fun'=>'due_date'), 
			// _("Currency") => array('align'=>'center'),
			_("Debit") => array('align'=>'right', 'fun'=>'fmt_debit2'), 
			// _("Credit") => array('align'=>'right', 'insert'=>true,'fun'=>'fmt_credit2'), 
			'View GL' => array('insert'=>true, 'fun'=>'gl_view'),
			// array('insert'=>true, 'fun'=>'void_link')
			);

/*if ($_POST['supplier_id'] != ALL_TEXT)
{
	$cols[_("Supplier")] = 'skip';
	$cols[_("Currency")] = 'skip';
}*/
//------------------------------------------------------------------------------------------------


/*show a table of the transactions returned by the sql */
$table =& new_db_pager('trans_tbl', $sql, $cols, null, null, 1000);
$table->set_marker('check_overdue', _("Highlighted items are overdue."));

$table->width = "85%";

display_db_pager($table);

end_form();
end_page();

?>
