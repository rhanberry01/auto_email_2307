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
$path_to_root = "../..";

include($path_to_root . "/includes/db_pager.inc");
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_banking.inc");

$page_security = 'SA_SALESTRANSVIEW';

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "PDC Monitoring"), false, false, "", $js);

//----------------------------------------------------------------------------------------

simple_page_mode(true);

//----------------------------------------------------------------------------------------
//	Orders inquiry table
//

function get_pdc()
{
	$date_after = date2sql($_POST['TransAfterDate']);
	$date_before = date2sql($_POST['TransToDate']);

	$sql = "SELECT c.debtor_no, 
				a.chk_number,
				a.chk_date,
				a.bank,
				a.chk_amount,
				b.bank_act,
				a.id,
				c.trans_no, c.type
			FROM ".TB_PREF."cheque_details a, ".TB_PREF."bank_trans b, ".TB_PREF."debtor_trans c
			WHERE a.type = 12
			AND a.deposited = 0
			AND a.chk_date >= '$date_after'
			AND a.chk_date <= '$date_before'
			AND a.bank_trans_id = b.trans_no
			AND b.trans_no = c.trans_no
			AND a.type = b.type";	
			
	if (isset($_POST['chk_no']) && $_POST['chk_no'] != "")
	{
		$chk_no = "%".$_POST['chk_no']."%";
		$sql .= " AND a.chk_number LIKE ".db_escape($chk_no);
	}

	$result = db_query($sql, "could not query pdc");
	return $result;
}

//----------------------------------------------------------------------------------------

if (isset($_POST['btn_deposit'])) 
{	
	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'deposit') === 0)
		{
			$id = substr($postkey, strlen('deposit'));
			
			$id_ = explode(',', $id);
			
			// display_error($id_[0]);
									
			$sql = "UPDATE ".TB_PREF."cheque_details 
					  SET deposited = 1 
					  WHERE id = ".db_escape($id_[0]);
			// display_error($sql);
			db_query($sql);
			$insert = 1;					
		}
	}
	
	if($insert == 1)
	{
		display_notification('Deposited.');
		$Ajax->activate('_page_body');	
		// $Ajax->activate('orders_tbl');
	}

}

if(isset($_GET['new']))
	$Ajax->activate('_page_body');

//----------------------------------------------------------------------------------------

start_form();

start_table("class='tablestyle_noborder'");
start_row();

ref_cells(_("Check #:"), 'chk_no', '',null, '', true);

date_cells(_("From:"), 'TransAfterDate', '', null, 1);
date_cells(_("To:"), 'TransToDate', '', null, 30);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();

end_form();

if(get_post('RefreshInquiry'))
{
	$Ajax->activate('orders_tbl');
}

//----------------------------------------------------------------------------------------

start_form();

div_start('orders_tbl');
start_table($table_style);
$th = array(_("OR #"), _("Customer"), _("Check #"), _("Check Date"), _("Bank"), _("Amount"), _("Deposit To"), "");

table_header($th);

$j = 1;
$k = 0; //row colour counter
$result = get_pdc();
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	
	$act = get_bank_account($myrow["bank_act"]);
	
	label_cell(get_customer_trans_view_str(ST_CUSTPAYMENT, $myrow["trans_no"], $myrow["reference"]));
	label_cell(get_customer_name($myrow["debtor_no"]));
	label_cell($myrow["chk_number"]);
	label_cell(sql2date($myrow["chk_date"]));
	label_cell($myrow["bank"]);
	amount_cell($myrow["chk_amount"]);
	label_cell($act['bank_account_name']);
	check_cells("", "deposit".$myrow["id"]);
	
	// hidden("from_bank_acct".$myrow["id"], $myrow["bank_act"]);
	// hidden("check_date".$myrow["id"], $myrow["chk_date"]);
	// hidden("check_amount".$myrow["id"], $myrow["amount"]);
	// hidden("check_no".$myrow["id"], $myrow["chk_number"]);
	// hidden("or_no".$myrow["id"], $ref_no["location"]."-".$ref_no["form_type_no"]."-".get_so_form_cat_name($ref_no["form_type"]));
	
	end_row();
	
	$j++;
	if ($j == 11)
	{
		$j = 1;
		table_header($th);
	}
}

end_table(1);

div_end();

submit_center('btn_deposit', _("Deposit"), true, '', 'default');

end_form();

end_page();

?>