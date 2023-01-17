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
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);

page(_($help_context = "View Customer Payment"), true, false, "", $js);

function get_chk_details($trans_no)
{
	$sql = "SELECT DISTINCT
			".TB_PREF."cheque_details.id,
			".TB_PREF."cheque_details.bank_trans_id,
			".TB_PREF."cheque_details.bank,
			".TB_PREF."cheque_details.branch,
			".TB_PREF."cheque_details.chk_number,
			".TB_PREF."cheque_details.chk_date,
			".TB_PREF."bank_trans.amount,
			".TB_PREF."debtors_master.name,
			".TB_PREF."debtor_trans.tran_date,
			".TB_PREF."debtor_trans.trans_no,
			".TB_PREF."cheque_details.chk_amount,
			".TB_PREF."cheque_details.deposited,
			".TB_PREF."cheque_details.status
			FROM
			".TB_PREF."cheque_details ,
			".TB_PREF."debtor_trans ,
			".TB_PREF."debtors_master ,
			".TB_PREF."bank_trans
			WHERE
			".TB_PREF."bank_trans.trans_no = $trans_no AND ".TB_PREF."bank_trans.type = 12 AND ".TB_PREF."debtor_trans.type = 12 AND
			".TB_PREF."cheque_details.bank_trans_id =  ".TB_PREF."bank_trans.trans_no AND
			".TB_PREF."cheque_details.bank_id =  ".TB_PREF."bank_trans.id AND
			".TB_PREF."cheque_details.type =  ".TB_PREF."bank_trans.type AND
			".TB_PREF."debtor_trans.reference =  ".TB_PREF."bank_trans.ref AND
			".TB_PREF."debtor_trans.debtor_no =  ".TB_PREF."debtors_master.debtor_no ";
	
	//display_error($sql);
	$result = db_query($sql,"Failed to retrieve cheque details -> view_receipt");
	// $row = db_fetch($result);
	// return $row;
	return $result;
}

if (isset($_GET["trans_no"]))
{
	$trans_id = $_GET["trans_no"];
}

$receipt = get_customer_trans($trans_id, ST_CUSTPAYMENT);

//display_heading(sprintf(_("Customer Payment #%s"),$receipt['reference']));
display_heading(sprintf(_("Customer Payment"),''));

echo "<br>";
start_table("$table_style width=80%");
start_row();
label_cells(_("From Customer"), $receipt['DebtorName'], "class='tableheader2'");
label_cells(_("Into Bank Account"), $receipt['bank_account_name'], "class='tableheader2'");
label_cells(_("Date of Deposit"), sql2date($receipt['tran_date']), "class='tableheader2'");
end_row();
start_row();
label_cells(_("Payment Currency"), $receipt['curr_code'], "class='tableheader2'");
label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
label_cells(_("OR/PR No."), $receipt['reference'], "class='tableheader2'");
end_row();
start_row();
label_cells(_("Amount"), price_format($receipt['Total'] - $receipt['ov_discount'] - $receipt['ewt']), "class='tableheader2'");
label_cells(_("EWT"), price_format($receipt['ewt']), "class='tableheader2'");
label_cells(_("Discount"), price_format($receipt['ov_discount']), "class='tableheader2'");
end_row();
start_row();
label_cells(_("Other Charges"), price_format($receipt['tracking']), "class='tableheader2'");
end_row();
comments_display_row(ST_CUSTPAYMENT, $trans_id);

end_table(1);

if($receipt['BankTransType'] == 1)
{
	div_start('chk_details');
	display_heading2(_("Cheque Details"));
	start_table("$table_style width='80%'");
	
	$j = 1;
	$k = 0; //row colour counter
	$over_due = false;
	
	$th = array(_("Customer"), _("Bank"), _("Bank Branch"), _("Cheque Number"), 
			_("Cheque Date"), _("Amount"), _("Status"));
	table_header($th);
    $k = $total_allocated = 0;
	
	$result = get_chk_details($trans_id);
	while ($myrow = db_fetch($result))
	{
					
		label_cell($myrow['name']);
		label_cell($myrow['bank']);
		label_cell($myrow['branch']);
		label_cell($myrow['chk_number']);
		label_cell(sql2date($myrow['chk_date']));
		label_cell(number_format2($myrow['chk_amount'],user_price_dec()));
		//label_cell($myrow['deposited'] == 1 ? 'Deposited' : 'Pending');
		label_cell($myrow['status']);
		
		end_row();
	}

	$j++;
	if ($j == 12)
	{
		$j = 1;
		table_header($th);
	} //end of page full new headings if
	end_table(1);
}

$voided = is_voided_display(ST_CUSTPAYMENT, $trans_id, _("This customer payment has been voided."));

if (!$voided)
{
	display_allocations_from(PT_CUSTOMER, $receipt['debtor_no'], ST_CUSTPAYMENT, $trans_id, $receipt['Total']/*+$receipt['ewt']*/+$receipt['tracking']);
}

end_page(true);
?>