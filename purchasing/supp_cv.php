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
$page_security = 'SA_SUPPLIERPAYMNT';
$path_to_root = "..";

include_once($path_to_root . "/includes/ui/items_cart.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_cv.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/modules/checkprint/includes/check_accounts_db.inc");
add_access_extensions();

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Check Voucher Entry"), false, false, "", $js);

//----------------------------------------------------------------------------------------

check_db_has_suppliers(_("There are no suppliers defined in the system."));

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

//----------------------------------------------------------------------------------------
if (isset($_GET['AddedID'])) 
{
	$cvid = $_GET['cvid'];

   	display_notification_centered( _("Check Voucher has been sucessfully entered"));

	// submenu_print(_("&Print This CV"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, 'prtopt');
	//submenu_print(_("&Email This CV"), ST_SUPPAYMENT, $payment_id."-".ST_SUPPAYMENT, null, 1);

    display_note(get_cv_view_str($cvid, _("View this CV")));

//    hyperlink_params($path_to_root . "/purchasing/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");

	hyperlink_no_params($path_to_root.'/purchasing/inquiry/prepare_cv.php', _("Create another CV"));

	display_footer_exit();
}

//----------------------------------------------------------------------------------------

if (isset($_POST['process']))
{
	 if (!is_date($_POST['cv_date']))
   	{
		display_error(_("The entered date is invalid."));
		set_focus('cv_date');
		unset($_POST['process']);
	} 
	// elseif (!is_date_in_fiscalyear($_POST['cv_date'])) 
	// {
		// display_error(_("The entered date is not in fiscal year."));
		// set_focus('cv_date');
		// unset($_POST['process']);
	// }
    if (!$Refs->is_valid($_POST['cv_no'])) 
    {
		display_error(_("You must enter a reference."));
		set_focus('cv_no');
		unset($_POST['process']);
	}

	if (!is_new_reference($_POST['cv_no'], ST_SUPPAYMENT) OR !is_new_cv_no($_POST['cv_no'])) 
	{
		display_error(_("The entered reference is already in use."));
		set_focus('cv_no');
		unset($_POST['process']);
	}
}

if (isset($_POST['process']))
{
	$cv_id = insert_cv($_POST['cv_no'],$_POST['cv_date'],input_num('amount')-input_num('ewt_amt'),PT_SUPPLIER,$_POST['supplier_id'], 
		$_SESSION['real_cv_trans'], $_POST['cv_due_date'], input_num('ewt_amt'));
	unset($_SESSION['real_cv_trans']);
	unset($_SESSION['cv_trans']);
	meta_forward($_SERVER['PHP_SELF'], "cvid=$cv_id&AddedID=".$_POST['cv_no']."&supplier_id=".$_POST['supplier_id']);
}
//----------------------------------------------------------------------------------------
if (!is_array($_SESSION['cv_transactions']))
{
	display_error('There are no transactions chosen for this CV.');
	hyperlink_no_params($path_to_root.'/purchasing/inquiry/prepare_cv.php', _("Choose transactions first"));
	exit();
}	
else
{
	$cv_trans = $_SESSION['cv_transactions'];
	// unset($_SESSION['cv_transactions']);
}
//----------------------------------------------------------------------------------------
$where_sql = "";
foreach($cv_trans as $trans_)
{
	$tr = explode('~',$trans_);
	if ($where_sql != '')
		$where_sql .= "OR (type=$tr[0] AND trans_no=$tr[1])";
	else
		$where_sql .= "WHERE (type=$tr[0] AND trans_no=$tr[1])";
}
//-----------------------------------------------------------------------------------------
global $systypes_array;
//+ewt
$sql = "SELECT *,round(ov_amount + ov_gst  - ov_discount - ewt,2) AS TotalAmount FROM ".TB_PREF."supp_trans $where_sql 
			ORDER BY del_date";
$res = db_query($sql);
// display_error($sql);

$k = $total = $total_d = $total_c = $total_ewt = 0;
start_form();
$th = array('Type', 'Trxn #', 'Supplier Invoice', 'Delivery Date', 'Due Date','Debit', 'Credit', 'GL', 
'EWT (included)','EWT (upon payment)');
start_table($table_style);
table_header($th);
$_SESSION['real_cv_trans'] = array();
$supplier = '';
$ddate = '';

$is_trade = false;
$company_pref = get_company_prefs();
while($row = db_fetch($res))
{
	if ($supplier == 0)
	{
		$supplier = $row['supplier_id'];
		hidden('supplier_id', $supplier);
	}
	$_SESSION['real_cv_trans'][] = array($row["type"], $row["trans_no"],$row['TotalAmount']);
	alt_table_row_color($k);
	
	
	if (trade_non_trade_inv($row["type"], $row["trans_no"]) == ' (Trade)' AND $is_trade == false)
		$is_trade = true;
		
	label_cell($systypes_array[$row['type']]);
	
	if($row["type"] == ST_SUPPDEBITMEMO || $row["type"] == ST_SUPPCREDITMEMO)
		label_cell(get_gl_view_str($row["type"], $row["trans_no"], $row["reference"]));
	else
		label_cell(get_trans_view_str($row["type"], $row["trans_no"], $row["reference"]));
	label_cell($row['supp_reference']);
	label_cell(sql2date(($row['type'] == 20 ? $row['del_date'] : $row['tran_date'])));
	
	if ($row['type'] != ST_SUPPDEBITMEMO)
	{
		label_cell(sql2date($row['due_date']));
		
		if (date1_greater_date2 (sql2date($row['due_date']), $ddate) OR $ddate == '')
			$ddate = sql2date($row['due_date']);
	}
	else
		label_cell();
		
	if ($row['ewt'] > 0)
	{
		$ewt_inc_amount = get_ewt_inc($row["type"], $row["trans_no"], $company_pref["default_purchase_ewt_act"]);
		
		if ($ewt_inc_amount > 0)
		{
			$total_ewt_inc += $row['ewt'];
		}
		else
		{
			$row['TotalAmount'] += $row['ewt'];
			$total_ewt_ex += $row['ewt'];
		}
	}
	

	$total += $row['TotalAmount'];
	if ($row['TotalAmount'] < 0)
	{
		amount_cell(abs($row['TotalAmount']));
		$total_c += $row['TotalAmount'];
		label_cell('');
	}
	else
	{
		label_cell('');
		amount_cell($row['TotalAmount']);
		$total_d += $row['TotalAmount'];
	}

	label_cell(get_gl_view_str($row["type"], $row["trans_no"]));
	
	if ($row['ewt'] > 0)
	{
		if ($ewt_inc_amount > 0)
		{
			// label_cell($ewt_inc_amount,'align=right');
			label_cell($row['ewt'],'align=right');
			label_cell('');
		}
		else
		{
			label_cell('');
			label_cell($row['ewt'],'align=right');
		}
	}
	else
	{
		label_cell('');
		label_cell('');
	}
	
	
	
	end_row();
}

if (isset($_POST['add_ewt']) OR list_updated('ewt_amt'))
{
	global $Ajax;
	$Ajax->activate('r1');
}



$_POST['cv_due_date'] = $ddate;

$cols = 4 + 1;
alt_table_row_color($k);
label_cell('<b>Totals:</b>', "colspan=$cols");
amount_cell(abs($total_c),true);
amount_cell($total_d,true);
hidden('amount', $total);
label_cell('');
amount_cell($total_ewt_inc,true);
amount_cell($total_ewt_ex,true);
end_row();
end_table(2);

// start_table();
// yesno_list_row("Manually input EWT?", 'compute_ewt' , null, "", "", true); 
$_POST['compute_ewt'] = 0;
hidden('compute_ewt',$_POST['compute_ewt']);
// end_table(1);


div_start('r1');
start_table("$table_style2");
label_row('Supplier :', '<b>'.get_supplier_name($supplier).'</b>',"class='tableheader2'");
start_row();
label_cell('CV No.',"class='tableheader2'");
ref_cells('','cv_no','',get_next_cv_no($is_trade));
end_row();
start_row();
label_cell('CV Date:',"class='tableheader2'");
date_cells('','cv_date');
end_row();
start_row();
label_cell('CV Due Date:',"class='tableheader2'");
date_cells('','cv_due_date');
end_row();

$new = '';

if ($total_ewt_ex > 0)
{
	$total = $total_d + $total_c;
	$new = 'net ';
	start_row();
	label_cell('CV Amount :', "class='tableheader2'");
	amount_cell($total,true);
	
	if (!isset($_POST['ewt_amt']))
		$_POST['ewt_amt'] = $total_ewt_ex;
	$total -= input_num('ewt_amt');
	
	
	if ($_POST['compute_ewt'] == 0)
	{
		start_row();
		labelheader_cell2('less EWT (upon payment): ');
		amount_cell($_POST['ewt_amt']);
		hidden('ewt_amt', $_POST['ewt_amt']);
	}
	else
	{

		start_row();
		labelheader_cell2('less EWT (upon payment): ');
		amount_cells('','ewt_amt');
		submit_cells('add_ewt','Apply EWT','',false,true);
	}
	// amount_cells('','ewt_amt');
	// submit_cells('add_ewt','Apply EWT','',false,true);
	end_row();
}
else if ($_POST['compute_ewt'] == 1)
{
	// $total -= input_num('ewt_amt');
	start_row();
	labelheader_cell2('less EWT (upon payment): ');
	amount_cells('','ewt_amt');	
	submit_cells('add_ewt','Apply EWT','',false,true);
}

start_row();
label_cell($new.'CV Amount :', "class='tableheader2'");
amount_cell($total,true);
end_row();
end_table();
div_end();
echo "<br><table align='center'>";

textarea_row(_("Particulars"), 'memo_', null, 50, 3);

echo "</table>";
submit_center('process','Create CV');
br();

end_form();
end_page();
?>