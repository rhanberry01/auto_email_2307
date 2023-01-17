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
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");


$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();


//--------------------------------------------------------------------------------------------------
function get_user_id_by_username_and_branch($user_name, $branch_name) {
	$sql = "SELECT id FROM $branch_name.".TB_PREF."users WHERE user_id = '".$user_name."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_sdma_by_branch($id, $branch_name)
{
	$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function supplier_list_ms_cells_by_branch($label, $name, $selected_id=null, $all_option=false, $submit_on_change=false, $branch_name)
{
	if ($label != null)
		echo "<td>$label</td><td>\n";
		echo supplier_list_ms($name, $selected_id, $all_option, $submit_on_change, $branch_name);
		echo "</td>\n";
}

function supplier_list_ms_by_branch($name, $selected_id=null, $spec_option=false, $submit_on_change=false, $branch_name)
{
	global $all_items;

	$sql = "SELECT vendorcode,description FROM $branch_name.vendor ";

	$mode = get_company_pref('no_supplier_list');
		
	return combo_input_ms($name, $selected_id, $sql, 'vendorcode', 'description',
	array(
	    'order' => array('description'),
		'search_box' => $mode!=0,
		'type' => 1,
		'spec_option' => $spec_option === true ? _("All Suppliers") : $spec_option,
		'spec_id' => '',
		'select_submit'=> $submit_on_change,
		'async' => false,
		'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') :
		_('Select supplier')
		));
}

function supplier_list_row_by_branch($label, $name, $selected_id=null, $all_option = false, 
	$submit_on_change=false, $all=false, $editkey = false, $branch_name)
{
	echo "<tr><td class='label'>$label</td><td>";
	echo supplier_list_by_branch($name, $selected_id, $all_option, $submit_on_change,
		$all, $editkey, $branch_name);
	echo "</td></tr>\n";
}

function supplier_list_by_branch($name, $selected_id=null, $spec_option=false, $submit_on_change=false,
	$all=false, $editkey = false, $branch_name)
{
	global $all_items;

	$sql = "SELECT supplier_id, supp_name, curr_code, inactive FROM $branch_name.".TB_PREF."suppliers ";

	$mode = get_company_pref('no_supplier_list');

	if ($editkey)
		set_editor('supplier', $name, $editkey);
		
	return combo_input($name, $selected_id, $sql, 'supplier_id', 'supp_name',
	array(
	    'order' => array('supp_name'),
		'search_box' => $mode!=0,
		'type' => 1,
		'spec_option' => $spec_option === true ? _("All Suppliers") : $spec_option,
		'spec_id' => $all_items,
		'select_submit'=> $submit_on_change,
		'async' => false,
		'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') :
		_('Select supplier'),
		'show_inactive'=>$all
		));
}

function get_sdma_type_list_row_by_branch($label, $name, $selected_id=null, $all_option = true, $submit_on_change=false, $branch_name)
{
	echo "<tr><td class='label'>$label</td><td>";
	echo get_sdma_type_list_by_branch($name, $selected_id, $all_option, $submit_on_change, $branch_name);
	echo "</td></tr>\n";
}

function get_sdma_type_list_by_branch($name, $selected_id=null, $spec_option=true, $submit_on_change=false, $branch_name)
{
	$sql = "SELECT DISTINCT id, type_name 
			FROM $branch_name.".TB_PREF."sdma_type
			WHERE inactive = 0";

	return combo_input($name, $selected_id, $sql, 'id', 'type_name',
	array(
	    'order' => array('type_name'),
		'search_box' => 0,
		'type' => 1,
		'spec_option' => $spec_option === true ? '' : $spec_option,
		'spec_id' => '',
		'select_submit'=> $submit_on_change,
		'async' => true
		));
}

function insert_sdma_by_branch($reference,$supplier_id,$sdma_type,$dm_date,$frequency,$period,$amount,$disc_percent,
		$effective_from,$effective_to,$once_only,$po_no,$comment, $receivable, $branch_name)
{
	$disc_vat_only = 0; // change later
	$frequency += 0;
	
	if (is_date($effective_from))
		$effective_from = "'".date2sql($effective_from)."'";
	else
		$effective_from = 'NULL';
	
	if (is_date($effective_to))
		$effective_to = "'".date2sql($effective_to)."'";
	else
		$effective_to = 'NULL';
	
	if (is_date($dm_date))
		$dm_date = "'".date2sql($dm_date)."'";
	else
		$dm_date = 'NULL';
	
		
	$sql = "INSERT INTO $branch_name.".TB_PREF."sdma(reference,supplier_id,sdma_type,dm_date,frequency,period,amount,disc_percent,
					disc_vat_only,effective_from,effective_to,once_only,po_no,comment, receivable, prepared_by)
			VALUES(".db_escape($reference).",".$supplier_id.",".$sdma_type.",$dm_date,$frequency,$period,
				".$amount.",".$disc_percent.",$disc_vat_only, $effective_from, $effective_to,$once_only,
				".db_escape($po_no).",".db_escape($comment).",$receivable,".$_SESSION["wa_current_user"]->user.")";
	// display_error($sql);
	db_query($sql,'failed to add DM Agreement');
	return db_insert_id();
}

function delete_sdma_by_branch($sdma_id, $branch_name)
{
	$sql = "DELETE FROM $branch_name.".TB_PREF."sdma WHERE id = $sdma_id";
	db_query($sql,'failed to delete DM agreement');
}

if (isset($_POST['branch'])) {
	$branch_name = $_POST['branch'];
}

if (isset($_GET['sdma_id']) && isset($_GET['branch']))
{
	page('Edit Supplier Debit Memo Agreement', false, false,'', $js);
	$sdma_id = $_POST['sdma_id'] = $_GET['sdma_id'];
	$branch_name = $_POST['branch'] = $_GET['branch'];
	

		$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma 
			WHERE id = $sdma_id";
		
		if (($_SESSION['wa_current_user']->username != 'admin' AND $_SESSION['wa_current_user']->username != 'beth' ) 
			AND !$_SESSION['wa_current_user']->can_approve_sdma_1)
		{
			$sql .= " AND prepared_by = ".get_user_id_by_username_and_branch($_SESSION['wa_current_user']->username, $branch_name);
		}
		$sql .=	" AND approval_2 = 0";

		// display_error($sql);
		$res = db_query($sql);
		
		if (db_num_rows($res)==0)
		{
			display_error("You can not edit this Debit Memo because it may have been posted or it is not yours.");
			hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
			display_footer_exit();
		}
	
	$row = get_sdma_by_branch($_POST['sdma_id'], $branch_name);

	$_POST['reference'] = $row['reference'];
	$_POST['po_no'] = $row['po_no'];
	$_POST['supplier_id_aria'] = $row['supplier_id'];
	$_POST['sdma_type'] = $row['sdma_type'];
	$_POST['dm_date'] = sql2date($row['dm_date']);
	
	$_POST['amount'] = $row['amount'];
	$_POST['freq'] = $row['frequency'];
	$_POST['receivable'] = $row['receivable'];
	$_POST['periods'] = ($row['period'] > 0 ? $row['period']+1 : 0);
	
	$_POST['disc_percent'] = $row['disc_percent'];
	$_POST['effective_from'] = sql2date($row['effective_from']);
	$_POST['effective_to'] = sql2date($row['effective_to']);
	
	$_POST['comment'] = $row['comment'];

}
else
	page('Create Supplier Debit Memo Agreement', false, false,'', $js);

function check_sdma_reference_by_branch($reference, $branch_name)
{
	$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma WHERE reference = ".db_escape($reference);
	$res = db_query($sql);
	
	return (db_num_rows($res) == 0);
}

function check_my_suppliers_by_branch($supp_ref, $branch_name)
{
	$sql = "SELECT * FROM $branch_name.".TB_PREF."suppliers WHERE supp_ref =".db_escape($supp_ref);
	$res = db_query($sql);
	
	
	$v_sql = "SELECT 	vendorcode,description,address,city,zipcode,country,fax,email,phone,contactperson,termid,daystodeliver,tradediscount,cashdiscount,
			terms,IncludeLineDiscounts,discountcode1,discountcode2,discountcode3,discount1,discount2,discount3,daystosum,reordermultiplier,remarks,
			SHAREWITHBRANCH,Consignor,LASTDATEMODIFIED,TIN FROM vendor 
			WHERE vendorcode = '".ms_escape_string($supp_ref)."'";
	$v_res = ms_db_query($v_sql);
	$v_row = mssql_fetch_array($v_res);
	
	if ($v_row['vendorcode'] == '')
	{
		return false;
	}
		
		
	if (db_num_rows($res) > 0)
	{
		$row = db_fetch($res);
		
		$update_sql = "UPDATE $branch_name.".TB_PREF."suppliers SET
				supp_name = ".db_escape($v_row['description']).", 
				supp_ref = ".db_escape($v_row['vendorcode']).", 
				address = ".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')) .", 
				supp_address = ".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')).", 
				phone = ".db_escape($v_row['phone']).", 
				fax = ".db_escape($v_row['fax']).", 
				contact = ".db_escape($v_row['contactperson'])."
			WHERE supp_ref =".db_escape($supp_ref);
		db_query($update_sql, 'failed to update supplier');
		return $row['supplier_id'];
	}
	else
	{
		$ins_sql = "INSERT INTO $branch_name.".TB_PREF."suppliers (supp_name ,supp_ref ,address ,supp_address ,phone ,fax ,gst_no ,contact ,email ,payment_terms ,notes)
			VALUES(".db_escape($v_row['description']).", 
				".db_escape($v_row['vendorcode']).", 
				".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')) .", 
				".db_escape($v_row['address'] . (trim($v_row['city']) != '' ? ', '.$v_row['city']:'')).", 
				".db_escape($v_row['phone']).", 
				".db_escape($v_row['fax']).", 
				".db_escape($v_row['TIN']).",  
				".db_escape($v_row['contactperson']).",  
				".db_escape($v_row['email']).",  1, 
				".db_escape($v_row['remarks']).")";
		db_query($ins_sql, 'failed to insert supplier');
		
		return db_insert_id();
	}
}

function update_sdma_by_branch($sdma_id,$reference,$supplier_id,$sdma_type,$dm_date,$frequency,$period,$amount,$disc_percent,
		$effective_from,$effective_to,$once_only,$po_no,$comment, $receivable, $branch_name)
{
	$disc_vat_only = 0; // change later
	$frequency += 0;
	
	if (is_date($effective_from))
		$effective_from = "'".date2sql($effective_from)."'";
	else
		$effective_from = 'NULL';
	
	if (is_date($effective_to))
		$effective_to = "'".date2sql($effective_to)."'";
	else
		$effective_to = 'NULL';
	
	if (is_date($dm_date))
		$dm_date = "'".date2sql($dm_date)."'";
	else
		$dm_date = 'NULL';
				
	$sql = "UPDATE $branch_name.".TB_PREF."sdma SET
				supplier_id = $supplier_id,
				sdma_type = $sdma_type,
				dm_date = $dm_date,
				frequency = $frequency,
				period = $period,
				amount = $amount,
				disc_percent = $disc_percent,
				disc_vat_only = $disc_vat_only,
				effective_from = $effective_from,
				effective_to = $effective_to,
				once_only = $once_only,
				po_no = ".db_escape($po_no).",
				comment = ".db_escape($comment).",
				receivable = $receivable,
				prepared_by = ".$_SESSION["wa_current_user"]->user."
			WHERE id = $sdma_id";
	// display_error($sql);
	db_query($sql,'failed to add DM Agreement');
}

function check_data()
{
	if (trim($_POST['reference']) == '')
	{
		display_error('Reference is required.');
		return false;
	}
	
	if (!check_sdma_reference_by_branch($_POST['reference'], $branch_name) AND $_POST['sdma_id'] == '')
	{
		display_error('Reference is already entered.');
		return false;
	}
	
	if ($_POST['supplier_id'] == '' AND  $_POST['supplier_id_aria'] == '') {
		display_error(_("You must choose a supplier. "));
		set_focus('supplier_id_aria');
		$input_error = 1;
	}
	
	if ($_POST['supplier_id'] != '' AND  $_POST['supplier_id_aria'] != '') {
		display_error(_("You must choose only 1 supplier. "));
		set_focus('supplier_id_aria');
		$input_error = 1;
	}

	if ($_POST['sdma_type'] == '')
	{
		display_error('Choose a Debit Memo Type.');
		return false;
	}
	
	if ($_POST['amount_percentage'] == 0 AND input_num('amount') <= 0)
	{
		display_error('Amount should be greater than 0');
		return false;
	}
	
	if ($_POST['amount_percentage'] == 1 AND input_num('disc_percent') <= 0)
	{
		display_error('Percentage Discount should be greater than 0');
		return false;
	}
	
	if ($_POST['dm_date'] == '' AND $_POST['po_no'] == '')
	{
		display_error('DM Date should not be empty');
		return false;
	}
	
	if (!is_date($_POST['dm_date']) AND $_POST['po_no'] == '')
	{
		display_error('DM Date should be a valid date');
		return false;
	}
	
	if ($_POST['just_once'] == 0 AND $_POST['po_no'] == '')
	{
		if ($_POST['effective_from'] == '')
		{
			display_error('Effective from should not be empty');
			return false;
		}
		
		if ($_POST['effective_to'] == '')
		{
			display_error('Effective to should not be empty');
			return false;
		}
		
		if (!is_date($_POST['effective_to']))
		{
			display_error('Effective to should be a date');
			return false;
		}
		
		if (!is_date($_POST['effective_from']))
		{
			display_error('Effective from should be a date');
			return false;
		}
		
		if (!is_date($_POST['effective_to']))
		{
			display_error('Effective to should be a date');
			return false;
		}

		if (date_diff2($_POST['effective_to'],$_POST['effective_from'],'d') < 0)
		{
			display_error('Effective from should be less than Effective to');
			return false;
		}
	}
	
	return true;
}

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];

   	display_notification_centered( _("Debit Memo submitted for approval"));

	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View Debit Memo")));

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter Debit Memo"));

	display_footer_exit();
}
if (isset($_GET['UpdatedID'])) 
{
	$trans_no = $_GET['UpdatedID'];

   	display_notification_centered( _("Debit Memo Updated"));

	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View Debit Memo")));

   	hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Debit Memo Inquiry"));

	display_footer_exit();
}
if (isset($_GET['DeletedID'])) 
{
	$trans_no = $_GET['DeletedID'];

   	display_notification_centered( _("Debit Memo Deleted"));

	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View Debit Memo")));

   	hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Debit Memo Inquiry"));

	display_footer_exit();
}

if (isset($_POST['Process']))
{
	if (check_data() == true)
	{
		if ($_POST['po_no'] != '')
		{
			$_POST['effective_from'] = 'NULL';
			$_POST['effective_to'] = 'NULL';
			$_POST['just_once'] = '0';
		}
		
		$periods = input_num('periods');
		
		if (input_num('amount') != 0)
			$_POST['just_once'] = '1';
		
		if ($_POST['freq'] == 0)
			$periods = 0;
		else
			$periods -= 1;
			
		if ($periods == 0)
			$_POST['freq'] == 0;
		
		
		if ($_POST['supplier_id'] != '')
		{
			$supp_ref = $_POST['supplier_id'];
			$_POST['supplier_id_aria'] = check_my_suppliers_by_branch($supp_ref, $branch_name);
		}
		//---------------------------------------------------------
		
		// else 
			// $_POST['supplier_id'] = $_POST['supplier_id_aria'];
		
		
		if ($_POST['sdma_id'] == '')
		{
			$id = insert_sdma_by_branch($_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST['dm_date'],
				$_POST['freq'],$periods,input_num('amount'),input_num('disc_percent'),
				$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'], $_POST['receivable'], $branch_name);
				
			meta_forward($_SERVER['PHP_SELF'], "AddedID=$id");
		}
		else if (!$_POST['sdma_id'] == '')
		{
			$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma 
			WHERE id = ".$_POST['sdma_id']."
			AND approval_2 = 0";
			$res = db_query($sql);
			
			if (db_num_rows($res)==0)
			{
				display_error("You can not edit this Debit Memo because it may have been posted.");
				hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
				display_footer_exit();
			}
			
			$id = $_POST['sdma_id'];
			update_sdma_by_branch($_POST['sdma_id'],$_POST['reference'],$_POST['supplier_id_aria'],$_POST['sdma_type'],$_POST['dm_date'],
				$_POST['freq'],$periods,input_num('amount'),input_num('disc_percent'),
				$_POST['effective_from'],$_POST['effective_to'],$_POST['just_once'],$_POST['po_no'],$_POST['comment'], $_POST['receivable'], $branch_name);
				
			meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$id");
		}
	}
}

if (isset($_POST['Delete']))
{
	$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma 
			WHERE id = ".$_POST['sdma_id'];
	if ($_SESSION['wa_current_user']->username != 'admin' AND !$_SESSION['wa_current_user']->can_approve_sdma_1)
	{
		$sql .= " AND prepared_by = ".get_user_id_by_username_and_branch($_SESSSION['wa_current_user']->username, $branch_name);
	}
	$sql .=	" AND approval_2 = 0";
	// display_error($sql);
	$res = db_query($sql);
	
	if (db_num_rows($res)==0)
	{
		display_error("You can not delete this Debit Memo because it may have been posted or its not yours.");
		hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
		display_footer_exit();
	}
	else
	{
		delete_sdma_by_branch($_POST['sdma_id'], $branch_name);
		meta_forward($_SERVER['PHP_SELF'], "DeletedID=".$_POST['sdma_id']);
	}
}

if (list_updated('amount_percentage') OR list_updated('just_once') OR isset($_POST['po_no']))
{
	global $Ajax;
	$Ajax->activate('main');
}


start_form();

div_start('main');
// display_error($_POST['sdma_id']);
if (isset($_POST['sdma_id']))
	hidden('sdma_id',$_POST['sdma_id']);

if (isset($_POST['branch'])) 
	hidden('branch', $_POST['branch']);

if (!isset($_POST['just_once']))
	$_POST['just_once'] = 1;
	
start_table("$table_style2");

if (isset($_GET['name'])) {
	echo '<tr>
			<td colspan="2"><center><b>'.$_GET['name'].'</b></center></td>
		</tr>';
}

if ($_POST['sdma_id'] == '')
	text_row('<font color=red>*Reference: </font>', 'reference', null, 20, 255);
else
{
	hidden('reference',$_POST['reference']);
	label_cells('<font color=red>*Reference: </font>',$_POST['reference']);
}

ref_row('PO # (<i>if for 1 PO only</i>): ', 'po_no', null, null, true);
supplier_list_ms_cells_by_branch('Database Supplier (<i>if not in ARIA</i>):', 'supplier_id', null, 'Use Supplier below', '', $branch_name);
supplier_list_row_by_branch('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above', '', '', '', $branch_name);
// supplier_list_row('<font color=red>*Supplier: </font>', 'supplier_id_aria', null, '');
get_sdma_type_list_row_by_branch('<font color=red>*Type: </font>', 'sdma_type', '', '', '', $branch_name);

yesno_list_row('<font color=red>*Amount/Percentage: </font>', 'amount_percentage', null, "Percentage", "Fixed Amount",true,true);

if ($_POST['amount_percentage'] == 0)
{
	if (!isset($_POST['dm_date']) OR $_POST['dm_date'] == '')
		$inc_years = 1001;
		
	amount_row('<font color=red>*Fixed Amount: </font>','amount');
	hidden('disc_percent',0);
	date_row('<font color=red>*Debit Memo Effectivity Date: </font>','dm_date',
		null, null, 0, 0, $inc_years);
	$_POST['just_once'] = 1;
	hidden('just_once',$_POST['just_once']);
	yesno_list_cells('<b>Receivable From Supplier ?</b>','receivable');
	
}
else
{
	percent_row('<font color=red>*Percentage: </font>' ,'disc_percent');
	hidden('dm_date',Today());
	hidden('amount',0);
	$_POST['receivable'] = $_POST['just_once'] = 0;
	hidden('just_once',$_POST['just_once']);
	hidden('receivable',$_POST['just_once']);
}

if ($_POST['po_no'] == '')
{
	if (!isset($_POST['dm_date']))
		$_POST['dm_date'] = begin_month(Today());

	// yesno_list_row('for 1 CV only: ', 'just_once', null, "YES", "NO",true,true);


	if ($_POST['just_once'] == 0)
	{
		date_row('<font color=red>*Effective from: </font>','effective_from');
		date_row('<font color=red>*Effective to: </font>','effective_to');
		hidden('periods',0);
		hidden('freq',0);
	}
	else
	{
		hidden('effective_from','');
		hidden('effective_to','');
		frequency_list_row("Frequency", 'freq', null);
		qty_cells('Periods (<i>repeat x times</i>):', 'periods', null, null, null, 0);
	}
}

textarea_row('Comment: ', 'comment', '', 35, 3);
end_table(1);
div_end();


if (isset($_POST['sdma_id']) AND $_POST['sdma_id'] != '' && isset($_POST['branch']))
{
	submit_center('Process', _("<b>Update Supplier Debit Memo</b>"), true , '', 'default');
	br(5);
	echo '<center><font color=red><b>*will delete immediately</b> </font></center>';
	submit_center('Delete', _("<b>Delete Supplier Debit Memo</b>"), true , '', false);
}
else
	submit_center('Process', _("<b>Submit Supplier Debit Memo for Approval</b>"), true , '', 'default');
end_form();
//------------------------------------------------------------------------------------------------

end_page();
?>
