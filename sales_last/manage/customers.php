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
$page_security = 'SA_CUSTOMER';
$path_to_root = "../..";

include_once($path_to_root . "/includes/session.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

// page(_($help_context = "Customers"), @$_REQUEST['popup']); 
page(_($help_context = "Customers"), false, false, "", $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/includes/banking.inc");
include_once($path_to_root . "/includes/ui.inc");

include($path_to_root . "/includes/db_pager.inc");

if (isset($_GET['debtor_no'])) 
{
	$_POST['customer_id'] = $_GET['debtor_no'];
}
$new_customer = (!isset($_POST['customer_id']) || $_POST['customer_id'] == ""); 
//--------------------------------------------------------------------------------------------

function can_process()
{
	if (strlen($_POST['CustName']) == 0) 
	{
		display_error(_("The customer name cannot be empty."));
		set_focus('CustName');
		return false;
	} 

	if (strlen($_POST['cust_ref']) == 0) 
	{
		display_error(_("The customer short name cannot be empty."));
		set_focus('cust_ref');
		return false;
	} 
	
	if (!check_num('credit_limit', 0))
	{
		display_error(_("The credit limit must be numeric and not less than zero."));
		set_focus('credit_limit');
		return false;		
	} 
	
	if (!check_num('pymt_discount', 0, 100)) 
	{
		display_error(_("The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0."));
		set_focus('pymt_discount');
		return false;		
	} 
	
	if (!check_num('discount', 0, 100)) 
	{
		display_error(_("The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0."));
		set_focus('discount');
		return false;		
	} 
	
	global $new_customer;
	
	if (strlen($_POST['br_name']) == 0 AND $new_customer)
	{
		display_error(_("The Branch name cannot be empty."));
		set_focus('br_name');
		return false;		
	}

	if (strlen($_POST['br_ref']) == 0  AND $new_customer)
	{
		display_error(_("The Branch short name cannot be empty."));
		set_focus('br_ref');
		return false;		
	}

	return true;
}

//--------------------------------------------------------------------------------------------

function handle_submit()
{
	global $path_to_root, $new_customer, $Ajax;

	if (!can_process())
		return;
		
	if ($new_customer == false) 
	{

		$sql = "UPDATE ".TB_PREF."debtors_master SET name=" . db_escape($_POST['CustName']) . ", 
			debtor_ref=" . db_escape($_POST['cust_ref']) . ",
			address=".db_escape($_POST['address']) . ", 
			tax_id=".db_escape($_POST['tax_id']) . ", 
			curr_code=".db_escape($_POST['curr_code']) . ", 
			email=".db_escape($_POST['email']) . ", 
			dimension_id=".db_escape($_POST['dimension_id']) . ", 
			dimension2_id=".db_escape($_POST['dimension2_id']) . ", 
            credit_status=".db_escape($_POST['credit_status']) . ", 
            payment_terms=".db_escape($_POST['payment_terms']) . ", 
            discount=" . input_num('discount') / 100 . ", 
            pymt_discount=" . input_num('pymt_discount') / 100 . ", 
            credit_limit=" . input_num('credit_limit') . ", 
            sales_type = ".db_escape($_POST['sales_type']) . ", 
            notes=".db_escape($_POST['notes']) . "
            WHERE debtor_no = ".db_escape($_POST['customer_id']);

		db_query($sql,"The customer could not be updated");

		update_record_status($_POST['customer_id'], $_POST['inactive'],
			'debtors_master', 'debtor_no');

		$Ajax->activate('customer_id'); // in case of status change
		display_notification(_("Customer has been updated."));
	} 
	else 
	{ 	//it is a new customer

		begin_transaction();

		$sql = "INSERT INTO ".TB_PREF."debtors_master (name, debtor_ref, address, tax_id, email, dimension_id, dimension2_id,  
			curr_code, credit_status, payment_terms, discount, pymt_discount,credit_limit,  
			sales_type, notes) VALUES (".db_escape($_POST['CustName']) .", " .db_escape($_POST['cust_ref']) .", "
			.db_escape($_POST['address']) . ", " . db_escape($_POST['tax_id']) . ","
			.db_escape($_POST['email']) . ", ".db_escape($_POST['dimension_id']) . ", " 
			.db_escape($_POST['dimension2_id']) . ", ".db_escape($_POST['curr_code']) . ", 
			" . db_escape($_POST['credit_status']) . ", ".db_escape($_POST['payment_terms']) . ", " . input_num('discount')/100 . ", 
			" . input_num('pymt_discount')/100 . ", " . input_num('credit_limit') 
			 .", ".db_escape($_POST['sales_type']).", ".db_escape($_POST['notes']) . ")";

		db_query($sql,"The customer could not be added");

		$_POST['customer_id'] = db_insert_id();
		$new_customer = false;
		
		//============================================================ BRANCH
		$sql = "INSERT INTO ".TB_PREF."cust_branch (debtor_no, br_name, branch_ref, br_address,
				salesman, phone, phone2, fax,
				contact_name, area, email, tax_group_id, sales_account, receivables_account, payment_discount_account, sales_discount_account, default_location,
				br_post_address, disable_trans, group_no, default_ship_via, notes)
				VALUES (".db_escape($_POST['customer_id']). ",".db_escape($_POST['br_name']) . ", "
					.db_escape($_POST['br_ref']) . ", "
					.db_escape($_POST['br_address']) . ", ".db_escape($_POST['salesman']) . ", "
					.db_escape($_POST['phone']) . ", ".db_escape($_POST['phone2']) . ", "
					.db_escape($_POST['fax']) . ","
					.db_escape($_POST['contact_name']) . ", ".db_escape($_POST['area']) . ","
					.db_escape($_POST['email']) . ", ".db_escape($_POST['tax_group_id']) . ", "
					.db_escape($_POST['sales_account']) . ", "
					.db_escape($_POST['receivables_account']) . ", "
					.db_escape($_POST['payment_discount_account']) . ", "
					.db_escape($_POST['sales_discount_account']) . ", "
					.db_escape($_POST['default_location']) . ", "
					.db_escape($_POST['br_post_address']) . ","
					.db_escape($_POST['disable_trans']) . ", "
					.db_escape($_POST['group_no']) . ", "
					.db_escape($_POST['default_ship_via']). ", "
					.db_escape($_POST['notes']) . ")";
		db_query($sql,"The branch record could not be inserted");
		//============================================================
		
		commit_transaction();			

		display_notification(_("A new customer has been added."));

		$Ajax->activate('_page_body');
	}
}
//--------------------------------------------------------------------------------------------

if (isset($_POST['submit'])) 
{
	handle_submit();
}
//-------------------------------------------------------------------------------------------- 

if (isset($_POST['delete'])) 
{

	//the link to delete a selected record was clicked instead of the submit button

	$cancel_delete = 0;

	// PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'
	$sel_id = db_escape($_POST['customer_id']);
	$sql= "SELECT COUNT(*) FROM ".TB_PREF."debtor_trans WHERE debtor_no=$sel_id";
	$result = db_query($sql,"check failed");
	$myrow = db_fetch_row($result);
	if ($myrow[0] > 0) 
	{
		$cancel_delete = 1;
		display_error(_("This customer cannot be deleted because there are transactions that refer to it."));
	} 
	else 
	{
		$sql= "SELECT COUNT(*) FROM ".TB_PREF."sales_orders WHERE debtor_no=$sel_id";
		$result = db_query($sql,"check failed");
		$myrow = db_fetch_row($result);
		if ($myrow[0] > 0) 
		{
			$cancel_delete = 1;
			display_error(_("Cannot delete the customer record because orders have been created against it."));
		} 
		else 
		{
			$sql = "SELECT COUNT(*) FROM ".TB_PREF."cust_branch WHERE debtor_no=$sel_id";
			$result = db_query($sql,"check failed");
			$myrow = db_fetch_row($result);
			if ($myrow[0] > 0) 
			{
				$cancel_delete = 1;
				display_error(_("Cannot delete this customer because there are branch records set up against it."));
				//echo "<br> There are " . $myrow[0] . " branch records relating to this customer";
			}
		}
	}
	
	if ($cancel_delete == 0) 
	{ 	//ie not cancelled the delete as a result of above tests
		$sql = "DELETE FROM ".TB_PREF."debtors_master WHERE debtor_no=$sel_id";
		db_query($sql,"cannot delete customer");

		display_notification(_("Selected customer has been deleted."));
		unset($_POST['customer_id']);
		$new_customer = true;
		$Ajax->activate('_page_body');
	} //end if Delete Customer
}

check_db_has_sales_types(_("There are no sales types defined. Please define at least one sales type before adding a customer."));
 
start_form();

if (db_has_customers()) 
{
	start_table("class = 'tablestyle_noborder'");
	start_row();
	customer_list_cells(_("Select a customer: "), 'customer_id', null,
		_('New customer'), true, check_value('show_inactive'));
	check_cells(_("Show inactive:"), 'show_inactive', null, true);
	end_row();
	end_table();
	if (get_post('_show_inactive_update')) {
		$Ajax->activate('customer_id');
		set_focus('customer_id');
	}
} 
else 
{
	hidden('customer_id');
}

if ($new_customer) 
{	
	$_POST['CustName'] = $_POST['cust_ref'] = $_POST['address'] = $_POST['tax_id']  = '';
	$_POST['dimension_id'] = 0;
	$_POST['dimension2_id'] = 0;
	$_POST['sales_type'] = -1;
	$_POST['email'] = '';
	$_POST['curr_code']  = get_company_currency();
	$_POST['credit_status']  = -1;
	$_POST['payment_terms']  = $_POST['notes']  = '';

	$_POST['discount']  = $_POST['pymt_discount'] = percent_format(0);
	$_POST['credit_limit']	= price_format($SysPrefs->default_credit_limit());
	$_POST['inactive'] = 0;
} 
else 
{
	
	$sql = "SELECT * FROM ".TB_PREF."debtors_master WHERE debtor_no = ".db_escape($_POST['customer_id']);
	$result = db_query($sql,"check failed");

	$myrow = db_fetch($result);

	$_POST['CustName'] = $myrow["name"];
	$_POST['cust_ref'] = $myrow["debtor_ref"];
	$_POST['address']  = $myrow["address"];
	$_POST['tax_id']  = $myrow["tax_id"];
	$_POST['email']  = $myrow["email"];
	$_POST['dimension_id']  = $myrow["dimension_id"];
	$_POST['dimension2_id']  = $myrow["dimension2_id"];
	$_POST['sales_type'] = $myrow["sales_type"];
	$_POST['curr_code']  = $myrow["curr_code"];
	$_POST['credit_status']  = $myrow["credit_status"];
	$_POST['payment_terms']  = $myrow["payment_terms"];
	$_POST['discount']  = percent_format($myrow["discount"] * 100);
	$_POST['pymt_discount']  = percent_format($myrow["pymt_discount"] * 100);
	$_POST['credit_limit']	= price_format($myrow["credit_limit"]);
	$_POST['notes']  = $myrow["notes"];
	$_POST['inactive'] = $myrow["inactive"];
}

start_outer_table($table_style2, 5);
table_section(1);
table_section_title(_("Name and Address"));

text_row(_("Customer Name:"), 'CustName', $_POST['CustName'], 40, 80);
text_row(_("Customer Short Name:"), 'cust_ref', null, 30, 30);
textarea_row(_("Address:"), 'address', $_POST['address'], 35, 5);

email_row(_("E-mail:"), 'email', null, 40, 40);
text_row(_("Tax Identification No.:"), 'tax_id', null, 40, 40);


if ($new_customer) 
{
	currencies_list_row(_("Customer's Currency:"), 'curr_code', $_POST['curr_code']);
} 
else 
{
	label_row(_("Customer's Currency:"), $_POST['curr_code']);
	hidden('curr_code', $_POST['curr_code']);				
}	
sales_types_list_row(_("Sales Type/Price List:"), 'sales_type', $_POST['sales_type']);

table_section(2);

table_section_title(_("Sales"));

percent_row(_("Discount Percent:"), 'discount', $_POST['discount']);
percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $_POST['pymt_discount']);
amount_row(_("Credit Limit:"), 'credit_limit', $_POST['credit_limit']);

payment_terms_list_row(_("Payment Terms:"), 'payment_terms', $_POST['payment_terms']);
credit_status_list_row(_("Credit Status:"), 'credit_status', $_POST['credit_status']); 
$dim = get_company_pref('use_dimension');
if ($dim >= 1)
	dimensions_list_row(_("Dimension")." 1:", 'dimension_id', $_POST['dimension_id'], true, " ", false, 1);
if ($dim > 1)
	dimensions_list_row(_("Dimension")." 2:", 'dimension2_id', $_POST['dimension2_id'], true, " ", false, 2);
if ($dim < 1)
	hidden('dimension_id', 0);
if ($dim < 2)
	hidden('dimension2_id', 0);

if (!$new_customer)  {
	start_row();
	echo '<td>'._('Customer branches').':</td>';
  	hyperlink_params_td($path_to_root . "/sales/manage/customer_branches.php",
		'<b>'. (@$_REQUEST['popup'] ?  _("Select or &Add") : _("&Add or Edit ")).'</b>', 
		"debtor_no=".$_POST['customer_id'].(@$_REQUEST['popup'] ? '&popup=1':''));
	end_row();

}

textarea_row(_("General Notes:"), 'notes', null, 35, 5);
record_status_list_row(_("Customer status:"), 'inactive');
end_outer_table(1);

//=============================================================================
if ($new_customer)
{
	
	echo '<br>';
	display_heading('Branch');
	echo '<br>';
	start_outer_table($table_style2, 5);
	table_section(1);

	table_section_title(_("Name and Contact"));

	text_row(_("Branch Name:"), 'br_name', null, 35, 40);
	text_row(_("Branch Short Name:"), 'br_ref', null, 30, 30);
	text_row(_("Contact Person:"), 'contact_name', null, 35, 40);

	text_row(_("Phone Number:"), 'phone', null, 32, 30);
	text_row(_("Secondary Phone Number:"), 'phone2', null, 32, 30);
	text_row(_("Fax Number:"), 'fax', null, 32, 30);

	email_row(_("E-mail:"), 'email', null, 35, 55);

	table_section_title(_("Sales"));

	sales_persons_list_row( _("Sales Person:"), 'salesman', null);

	sales_areas_list_row( _("Sales Area:"), 'area', null);

	sales_groups_list_row(_("Sales Group:"), 'group_no', null, true);

	locations_list_row(_("Default Inventory Location:"), 'default_location', null);

	shippers_list_row(_("Default Shipping Company:"), 'default_ship_via', null);

	tax_groups_list_row(_("Tax Group:"), 'tax_group_id', null);

	yesno_list_row(_("Disable this Branch:"), 'disable_trans', null);

	table_section(2);

	table_section_title(_("GL Accounts"));

	// 2006-06-14. Changed gl_al_accounts_list to have an optional all_option 'Use Item Sales Accounts'
	gl_all_accounts_list_row(_("Sales Account:"), 'sales_account', null, false, false, true);

	gl_all_accounts_list_row(_("Sales Discount Account:"), 'sales_discount_account');

	gl_all_accounts_list_row(_("Accounts Receivable Account:"), 'receivables_account');

	gl_all_accounts_list_row(_("Prompt Payment Discount Account:"), 'payment_discount_account');

	table_section_title(_("Addresses"));

	textarea_row(_("Mailing Address:"), 'br_post_address', null, 35, 4);

	textarea_row(_("Billing Address:"), 'br_address', null, 35, 4);

	textarea_row(_("General Notes:"), 'notes', null, 35, 4);

	end_outer_table(1);
}
//=============================================================================

div_start('controls');
if ($new_customer)
{
	submit_center('submit', _("Add New Customer"), true, '', 'default');
} 
else 
{
	submit_center_first('submit', _("Update Customer"), 
	  _('Update customer data'), @$_REQUEST['popup'] ? true : 'default');
	submit_return('select', get_post('customer_id'), _("Select this customer and return to document entry."));
	submit_center_last('delete', _("Delete Customer"), 
	  _('Delete customer data if have been never used'), true);
}
div_end();



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

//------------------------------------------------------------------------------------------------

if (!$new_customer) 
{
	start_table("class='tablestyle_noborder'");
	start_row();

	date_cells(_("From:"), 'TransAfterDate', '', null, -30);
	date_cells(_("To:"), 'TransToDate', '', null, 1);
	
	if (!isset($_POST['filterType']))
		$_POST['filterType'] = 0;

	cust_allocations_list_cells(null, 'filterType', $_POST['filterType'], true);

	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
	end_row();
	end_table();

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
		(trans.ov_amount + trans.ov_gst + trans.ov_freight 
			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount, "; 
   	if ($_POST['filterType'] != ALL_TEXT)
		$sql .= "@bal := @bal+(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), ";

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
			AND trans.branch_code = branch.branch_code 
			AND trans.debtor_no = ".db_escape($_POST['customer_id']);

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
   	
	//------------------------------------------------------------------------------------------------

	db_query("set @bal:=0");

	$cols = array(
		_("Type") => array('fun'=>'systype_name', 'ord'=>'', 'type'=>'nowrap'),
		_("#") => array('fun'=>'trans_view', 'ord'=>''),
		_("Order") => array('fun'=>'order_view'), 
		_("Date") => array('name'=>'tran_date', 'type'=>'date', 'ord'=>'desc'),
		_("Due Date") => array('type'=>'date', 'fun'=>'due_date'),
		_("Customer") => array('ord'=>''), 
		_("Branch") => array('ord'=>''), 
		_("Currency") => array('align'=>'center'),
		_("Debit") => array('align'=>'right', 'fun'=>'fmt_debit'), 
		_("Credit") => array('align'=>'right','insert'=>true, 'fun'=>'fmt_credit'), 
			array('insert'=>true, 'fun'=>'gl_view')
		);


	$table =& new_db_pager('trans_tbl', $sql, $cols);

	$table->width = "85%";

	display_db_pager($table);

}	// if


hidden('popup', @$_REQUEST['popup']);

end_form();
end_page();

?>
