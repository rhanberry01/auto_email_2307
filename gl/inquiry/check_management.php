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

$page_security = 'SA_GLANALYTIC';

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Check Management"), false, false, "", $js);

//----------------------------------------------------------------------------------------

simple_page_mode(true);

//----------------------------------------------------------------------------------------
//	Orders inquiry table
//

function get_pdc()
{
	$date_after = date2sql($_POST['TransAfterDate']);
	$date_before = date2sql($_POST['TransToDate']);

	// $sql = "SELECT c.debtor_no, 
				// a.chk_number,
				// a.chk_date,
				// a.bank,
				// a.chk_amount,
				// b.bank_act,
				// a.id,
				// c.trans_no, c.type, a.status, a.deposit_date, a.branch, c.branch_code
			// FROM ".TB_PREF."cheque_details a, ".TB_PREF."bank_trans b, ".TB_PREF."debtor_trans c
			// WHERE a.chk_date >= '$date_after'
			// AND a.chk_date <= '$date_before'
			// AND a.bank_trans_id = b.trans_no
			// AND b.trans_no = c.trans_no
			// AND a.type = b.type ";
		
	// if(isset($_POST['status']) && $_POST['status'] != ""){
		// $sql .=  " AND a.status = ".db_escape($_POST['status'])." ";
	// }
	
	// if(isset($_POST['customer_id']) && $_POST['customer_id'] != ""){
		// $sql .=  " AND c.debtor_no = ".db_escape($_POST['customer_id'])." ";
	// }
	
	// if($_POST['status'] == "On-Hand")
		// $sql .= " AND a.type = 12 AND c.type=12 ";
	// else
		// $sql .= " AND a.type = 2 AND c.type=2 ";
	$sql = "SELECT 
				DISTINCT a.chk_number,
				c.debtor_no, 
				a.chk_date,
				a.bank,
				a.chk_amount,
				b.bank_act,
				a.id,
				c.trans_no, c.type, a.status, a.deposit_date, a.branch, c.branch_code 
			  FROM ".TB_PREF."cheque_details a
			  JOIN ".TB_PREF."bank_trans b ON a.bank_trans_id = b.trans_no AND a.`type` = b.`type` 
			  AND a.bank_id = b.id
			  LEFT JOIN ".TB_PREF."debtor_trans c ON b.trans_no = c.trans_no AND b.`type` = c.`type` 
	";
	$sql.= " WHERE a.chk_date BETWEEN ".db_escape($date_after)." AND ".db_escape($date_before);
	if(isset($_POST['status']) && $_POST['status'] != ""){
		$sql.= " AND a.status = ".db_escape($_POST['status'])." ";
	}
	if(isset($_POST['customer_id']) && $_POST['customer_id'] != ""){
		$sql.= " AND c.debtor_no = ".db_escape($_POST['customer_id'])." ";
	}
	if($_POST['status'] == "On-Hand")
		$sql .= " AND a.type = 12 AND c.type=12 ";
	else
		$sql .= " AND a.type = 2 AND c.type=2 ";
	$sql.= " AND status LIKE(".db_escape($_POST['status']).")";
	// switch($_POST['status']){
		// case 'On-Hand' : 
								// // $sql.= " AND a.type = 12 AND c.type = 12 ";
								// break;
		// case 'Uncleared / Bounced' : 
								// // $sql.= " AND a.type = 2 AND c.type = 2";
								// break;
		// case 'Deposited' : break;
		// case 'Cleared' : break;
	// }
	
	// display_error($sql);
	$result = db_query($sql, "could not query pdc");
	return $result;
}

//----------------------------------------------------------------------------------------

if (isset($_POST['ProcessOrder'])) 
{	
	foreach($_POST as $postkey=>$postval )
    {	
		if (strpos($postkey, 'deposit') === 0)
		{
			$id = substr($postkey, strlen('deposit'));
			
			$id_ = explode(',', $id);
			
			// display_error($id_[0]);
			// display_error("bank_account==>".$_POST['bank_account']);
			// display_error("chk_amt==>".$_POST['chk_amt'.$id_[0]]);
			// display_error("debtor_no==>".$_POST['debtor_no'.$id_[0]]);
			// display_error("bank_act==>".$_POST['bank_act'.$id_[0]]);			
			// display_error("chk_number==>".$_POST['chk_number'.$id_[0]]);			
			// display_error("branch==>".$_POST['branch'.$id_[0]]);			
			// display_error("bank==>".$_POST['bank'.$id_[0]]);			
			
			global $Refs;
			
			begin_transaction();
			
			//$trans_no = get_next_trans_no(ST_BANKDEPOSIT);
			$ref = $Refs->get_next(ST_BANKDEPOSIT);
			$currency = get_bank_account_currency($_POST['bank_account']);
			$to_bank_gl_account = get_bank_gl_account($_POST['bank_account']);
			$from_bank_gl_account = get_bank_gl_account($_POST['bank_act'.$id_[0]]);
			$total_amount = $_POST['chk_amt'.$id_[0]];
			$debtor_no = $_POST['debtor_no'.$id_[0]];
			$chk_number = $_POST['chk_number'.$id_[0]];
			$branch = $_POST['branch'.$id_[0]];
			$bank = $_POST['bank'.$id_[0]];
			$branch_code = $_POST['branch_code'.$id_[0]];
						
			// convert to customer currency
			$cust_amount = exchange_from_to($total_amount, $currency, get_customer_currency($debtor_no), Today());
			// we need to negate it too
			$cust_amount = -$cust_amount;

			$trans_no = write_customer_trans(ST_BANKDEPOSIT, 0, $debtor_no, $branch_code, Today(),
				$ref, $cust_amount);
			
			$id = add_bank_trans_2(ST_BANKDEPOSIT, $trans_no, $_POST['bank_account'], $ref,
				Today(), $total_amount,
				PT_CUSTOMER, $debtor_no,
				$currency);
			
			// to bank account
			add_gl_trans(ST_BANKDEPOSIT, $trans_no, Today(), $to_bank_gl_account, 0, 0, null,
				$total_amount, null, PT_CUSTOMER, $debtor_no);
				
			// to bank account
			add_gl_trans(ST_BANKDEPOSIT, $trans_no, Today(), $from_bank_gl_account, 0, 0, null,
				-$total_amount, null, PT_CUSTOMER, $debtor_no);
				
			add_check_deposit($trans_no,ST_BANKDEPOSIT,$bank,$branch,$chk_number,
					Today(),$total_amount, "Deposited", Today(), $id);
			
			$sql = "UPDATE ".TB_PREF."cheque_details 
					SET deposit_date = ".db_escape(date2sql(Today())).",
						status = 'Deposited'	
					WHERE id = ".db_escape($id_[0]);			
			db_query($sql);			
								
			$Refs->save(ST_BANKDEPOSIT, $trans_no, $ref);
			add_audit_trail(ST_BANKDEPOSIT, $trans_no, Today());
			
			commit_transaction();
						
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

date_cells(_("From:"), 'TransAfterDate', '', null);
date_cells(_("To:"), 'TransToDate', '', null, 30);

customer_list_cells(_("Customer:"), 'customer_id',null, true);

check_type_list_cells(_("Status:"), 'status', null, true);

submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'), 'default');
end_row();
end_table();

end_form();

br();

if(get_post('RefreshInquiry'))
{
	$Ajax->activate('orders_tbl');
}

//----------------------------------------------------------------------------------------

start_form();

div_start('orders_tbl');
start_table($table_style);
$th = array(_("Check #"), _("Check Date"), _("Customer"), _("Amount"), _("Status"), _("Deposit Date"), "");

table_header($th);

$j = 1;
$k = 0; //row colour counter
$result = get_pdc();
while ($myrow = db_fetch($result))
{
	alt_table_row_color($k);
	
	$act = get_bank_account($myrow["bank_act"]);
	
	label_cell($myrow["chk_number"]);
	label_cell(sql2date($myrow["chk_date"]));
	label_cell(get_customer_name($myrow["debtor_no"]));
	amount_cell($myrow["chk_amount"]);
	label_cell($myrow["status"]);
	if($myrow["deposit_date"] == "0000-00-00")
		label_cell("");	
	else
		label_cell($myrow["deposit_date"]);	
		
	if($myrow["status"] == "On-Hand")
		check_cells("", "deposit".$myrow["id"]);
	else
		label_cell("");
		
	hidden("chk_amt".$myrow["id"], $myrow["chk_amount"]);
	hidden("debtor_no".$myrow["id"], $myrow["debtor_no"]);
	hidden("bank_act".$myrow["id"], $myrow["bank_act"]);
	hidden("chk_number".$myrow["id"], $myrow["chk_number"]);
	hidden("branch".$myrow["id"], $myrow["branch"]);
	hidden("bank".$myrow["id"], $myrow["bank"]);
	hidden("branch_code".$myrow["id"], $myrow["branch_code"]);
	
	end_row();
	
	$j++;
	if ($j == 11)
	{
		$j = 1;
		table_header($th);
	}
}

end_table(1);

br();
echo "<center>";
check_accounts_list_row_3(_("Deposit To:"), 'bank_account');
echo "</center>";
br();

div_end();

if(isset($_POST['status']) && $_POST['status'] == "On-Hand")
{
	submit_center_first('ProcessOrder', _("Process"), false, false);
	submit_center_last('CancelOrder', _("Cancel"), false);
	br();
}

end_form();

end_page();

?>