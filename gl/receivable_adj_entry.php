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
include_once($path_to_root . "/includes/ui/check_cart_2.inc");
include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
$page_security = isset($_GET['NewPayment']) || @($_SESSION['pay_items']->trans_type==ST_SALESTOTAL)? 'SA_PAYMENT' : 'SA_DEPOSIT';

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/ui/gl_bank_ui.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");

include_once($path_to_root . "/modules/checkprint/includes/check_accounts_db.inc");
add_access_extensions();

if (isset($_GET['NewPayment']))
{
	$_SESSION['checks'] = new check_cart(ST_SALESTOTAL,0);
} else if(isset($_GET['NewPayment'])) {
	$_SESSION['checks'] = new check_cart(ST_SALESTOTAL,0);
}

$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();


if(isset($_GET['NewPayment'])) {
	$_SESSION['page_title'] = _($help_context = "Receivable Adjustment Entry");
	handle_new_order(ST_SALESTOTAL);
}
page($_SESSION['page_title'], false, false, '', $js);

$selected_id = find_submit('selected_id');
//-----------------------------------------------------------------------------------------------
check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
if (list_updated('PersonDetailID')) {
	$br = get_branch(get_post('PersonDetailID'));
	$_POST['person_id'] = $br['debtor_no'];
	$Ajax->activate('person_id');
}

//--------------------------------------------------------------------------------------------------
function line_start_focus() {
  global 	$Ajax;

  $Ajax->activate('items_table');
  set_focus('_code_id_edit');
}

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedDep']))
{
	$trans_no = $_GET['AddedDep'];
	$trans_type = ST_SALESTOTAL;

   	display_notification_centered(_("Receivable Adjustment to Transaction $trans_no has been applied."));

	display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL Postings for this Adjustment")));

	hyperlink_params($_SERVER['PHP_SELF'], _("Enter Another Payment"), "NewPayment=yes");

	display_footer_exit();
}
if (isset($_POST['_date__changed'])) {
	$Ajax->activate('_ex_rate');
}
//--------------------------------------------------------------------------------------------------
function handle_new_order($type)
{
	if (isset($_SESSION['pay_items']))
	{
		unset ($_SESSION['pay_items']);
	}

	//session_register("pay_items");

	$_SESSION['pay_items'] = new items_cart($type);
			
	$_POST['date_'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['date_']))
	$_POST['date_'] = end_fiscalyear();
	$_SESSION['pay_items']->tran_date = $_POST['date_'];
}
// $_SESSION['checks'] = new check_cart($_SESSION['pay_items']->trans_type,0);
//-----------------------------------------------------------------------------------------------

$for_op_id = find_submit('_for_op');

if ($for_op_id != -1)
{
	global $Ajax;
	set_time_limit(0);
	
		
	foreach($_POST as $postkey=>$postval )
    {	
			if (strpos($postkey, 'for_op') === 0)
			{
				
				$id = substr($postkey, strlen('for_op'));
				$c_ids[] = $id;
				
				if (count($c_ids) == 1) {
				$id_ = explode(',', $id);
				display_error("trans_id==>".$_POST['id'.$id_[0]]);
				print_r($id_);
				}
				else{
				display_error('You can update 1 Receivable at a time.');
				}

			}
	}
	//tagging 
	set_focus('for_op'.$for_op_id);
	
	//$Ajax->activate('check_count2'); 
	
}

if (isset($_POST['Process']))
{
	$input_error = 0;

	if ($input_error == 1)
		unset($_POST['Process']);
}														

														if (isset($_POST['Process']))
														{
																global $Ajax;
																		
																$prefix = 'for_op';
																$c_ids = array();
																
																foreach($_POST as $postkey=>$postval)
																{
																if (strpos($postkey, $prefix) === 0) {
																$id = substr($postkey, strlen($prefix));
																$c_ids[] = $id;
																}
																}
																
																$c_id_str = implode(',',$c_ids);
																//display_error($c_id_str);
																
																//check if INVOICE# is emtpy.
																foreach ($c_ids as $approve_id)
																{
																$trans_id=$_POST['id'.$approve_id];
																$date_remit=$_POST['date_remit'.$approve_id];
																}
																
																//display_error($trans_id);
																//display_error($date_remit);
																
																foreach ($_SESSION['pay_items']->gl_items as $gl_item)
																{
																//display_error($gl_item->code_id);
																$sql = "INSERT INTO ".TB_PREF."gl_trans(type, type_no, tran_date,account,memo_,amount)
																VALUES(60,$c_id_str, '$date_remit', ".$gl_item->code_id.",'".$gl_item->reference."',".$gl_item->amount.")";
																//display_error($sql);
																db_query($sql, 'failed to post gl');
																
																}
																
																$sql = "DELETE FROM 0_gl_trans
																WHERE type =60 AND type_no = $c_id_str
																AND account ='1430017'";
																db_query($sql,'failed to delete gl');

															  $trans_type = $trans[0];
															  $trans_no = $trans[1];
															  new_doc_date($_POST['date_']);
											
															$_SESSION['pay_items']->clear_items();
															meta_forward($_SERVER['PHP_SELF'], "AddedDep=$trans_id");
														} /*end of process credit note */

//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	//if (!check_num('amount', 0))
	//{
	//	display_error( _("The amount entered is not a valid number or is less than zero."));
	//	set_focus('amount');
	//	return false;
	//}

	if ($_POST['code_id'] == $_POST['bank_account'])
	{
		display_error( _("The source and destination accounts cannot be the same."));
		set_focus('code_id');
		return false;
	}

   	return true;
}
//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
	$amount = ($_SESSION['pay_items']->trans_type==ST_SALESTOTAL ? 1:-1) * input_num('amount');
    if($_POST['UpdateItem'] != "" && check_item_data())
    {
    	$_SESSION['pay_items']->update_gl_item($_POST['Index'], $_POST['code_id'], 
    	    $_POST['dimension_id'], $_POST['dimension2_id'], $amount , $_POST['LineMemo']);
    }
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id)
{
	$_SESSION['pay_items']->remove_gl_item($id);
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_new_item()
{
	if (!check_item_data())
		return;
	$amount = ($_SESSION['pay_items']->trans_type==ST_SALESTOTAL ? 1:-1) * input_num('amount');

	$_SESSION['pay_items']->add_gl_item($_POST['code_id'], $_POST['dimension_id'],
		$_POST['dimension2_id'], $amount, $_POST['LineMemo']);
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id);

if (isset($_POST['AddItem']))
	handle_new_item();

if (isset($_POST['UpdateItem']))
	handle_update_item();

if (isset($_POST['CancelItemChanges']))
	line_start_focus();

if (isset($_POST['go']))
{
	display_quick_entries($_SESSION['pay_items'], $_POST['person_id'], input_num('totamount'), 
		$_SESSION['pay_items']->trans_type==ST_SALESTOTAL ? QE_PAYMENT : QE_DEPOSIT);
	$_POST['totamount'] = price_format(0); $Ajax->activate('totamount');
	line_start_focus();
}
//-----------------------------------------------------------------------------------------------

$del_id = find_submit('Delete_2_');
if ($del_id!=-1)
	handle_delete_item_2($del_id);

if (isset($_POST['CancelItemChanges_2_'])) {
	check_line_start_focus_2();
}

if (isset($_POST['UpdateItem_2_']))
	handle_update_item_2();


//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\

if ($selected_id != -1) {
global $Ajax;
	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'selected_id') === 0)
		{
		$id = substr($postkey, strlen('selected_id'));
		$id_ = explode(',', $id);
		//display_error("t_net_amount==>".$_POST['t_net_amount'.$id_[0]]);
		$_POST['sub_amount']+=$_POST['t_total_receivable'.$id_[0]];
		//display_error($_POST['sub_amount']);
		}
	}
$Ajax->activate('sub_amount'); 
}

start_form();
if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('Reference #:', 'dm_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);

//===================================RECEIVABLE LIST=======================================
	$sql = "SELECT * FROM 0_salestotals as st
	LEFT JOIN 0_salestotals_details as std
	ON st.ts_id=std.ts_id
	LEFT JOIN 0_gl_trans as gl
	ON std.tsd_id=gl.type_no
	where st.ts_receivable!=0
	AND gl.type='60'
	AND gl.account='1430017'";

if (trim($_POST['dm_no']) == '')
{
	$sql .= " AND ts_date_remit >= '".date2sql($_POST['start_date'])."'
				 AND ts_date_remit <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " AND (tsd_id LIKE ".db_escape('%'.$_POST['dm_no'].'%')." )";
}

$sql .= " ORDER BY ts_date_remit";
$res = db_query($sql);
//display_error($sql);

div_start('table_');
display_heading("Receivable Summary from ".$_POST['start_date']."  to  ".$_POST['end_date']);
br();
start_table($table_style2.' width=40%');
$th = array();
array_push($th, 'Date', 'Trans #','Current GL Entry','Amount','');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell(sql2date($row['ts_date_remit']),"align='center'");
	label_cell(get_gl_view_str(60, $row["tsd_id"], $row["tsd_id"]),"align='center'");
	label_cell(get_gl_account_name($row['account']),"align='center'");
	//gl_all_accounts_list_cells('', 'gl_type'.$row['tsd_id'], $row['account'], false, false, "All Accounts");
	amount_cell($row['ts_receivable'],true);
	//text_cells(null, 'remarks'.$row['tsd_id'],$row['remarks']);

	hidden('date_remit'.$row['tsd_id'],$row['ts_date_remit']);
	hidden('id'.$row['tsd_id'],$row['tsd_id']);

	check_cells('','for_op'.$row['tsd_id'],null,true, '', "align='center'");
	//submit_cells($submit, 'Apply changes', "align=center", true, true,'ok.gif');
	end_row();
}
end_table();
div_end();
//===================================END OF RECEIVABLE LIST=======================================

br();

start_table("$table_style2 width=85%", 10);
echo "<td>";
div_start('show_check_cart');
display_gl_items($_SESSION['pay_items']->trans_type==ST_SALESTOTAL ?_("GL Items"):_("GL Items"), $_SESSION['pay_items']);
div_end();
echo "</td>";
end_row();
end_table(1);
end_table(1);
//submit_center_first('Update', _("Update"), '', null);
//submit_center_last('Process',_("Process Payment"), '', 'default');
submit_center('Process', 'Process Payment', "align=center", true, true,'ok.gif');
br(3);

end_form();
end_table(1);
end_page();
?>