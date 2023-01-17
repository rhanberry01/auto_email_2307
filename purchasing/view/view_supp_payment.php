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

include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Payment to Supplier"), true, false, "", $js);

function get_chk_details($trans_no)
{
	$sql = "SELECT b.* FROM 0_bank_trans a, 0_cheque_details b
	where trans_no = $trans_no AND a.type = 22
	AND a.id = b.bank_trans_id";
	
	// display_error($sql);
	$result = db_query($sql,"Failed to retrieve cheque details -> view_receipt");
	// $row = db_fetch($result);
	// return $row;
	return $result;
}


if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
}

$receipt = get_supp_trans_2($trans_no, ST_SUPPAYMENT);

$company_currency = get_company_currency();

$show_currencies = false;
$show_both_amounts = false;

if (($receipt['bank_curr_code'] != $company_currency) || ($receipt['SupplierCurrCode'] != $company_currency))
	$show_currencies = true;

if ($receipt['bank_curr_code'] != $receipt['SupplierCurrCode']) 
{
	$show_currencies = true;
	$show_both_amounts = true;
}

echo "<center>";

//display_heading(_("Payment to Supplier") . " #". $receipt['ref']);
display_heading(_("Payment to Supplier") );

echo "<br>";
start_table("$table_style2 width=80%");

start_row();
label_cells(_("To Supplier"), $receipt['supplier_name'], "class='tableheader2'");
label_cells(_("From Bank Account"), $receipt['bank_account_name'], "class='tableheader2'");
label_cells(_("Date Paid"), sql2date($receipt['tran_date']), "class='tableheader2'");
end_row();
start_row();
if ($show_currencies)
	label_cells(_("Payment Currency"), $receipt['bank_curr_code'], "class='tableheader2'");
label_cells(_("Amount"), number_format2(-$receipt['BankAmount'], user_price_dec()), "class='tableheader2'");
label_cells(_("EWT"), number_format2(-$receipt['ewt'], user_price_dec()), "class='tableheader2'");
label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
end_row();
start_row();
if ($show_currencies) 
{
	label_cells(_("Supplier's Currency"), $receipt['SupplierCurrCode'], "class='tableheader2'");
}
if ($show_both_amounts)
	label_cells(_("Amount"), number_format2(-$receipt['Total'], user_price_dec()), "class='tableheader2'");
label_cells(_("Reference"), $receipt['ref'], "class='tableheader2'");
end_row();
comments_display_row(ST_SUPPAYMENT, $trans_no);

end_table(1);

if($receipt['BankTransType'] == 1)
{
	div_start('chk_details');
	display_heading2(_("Cheque Details"));
	start_table("$table_style width='80%'");
	
	$j = 1;
	$k = 0; //row colour counter
	$over_due = false;
	
	$th = array(_("Supplier"), _("Bank"), _("Cheque Number"), 
			_("Cheque Date"),
		_("Amount"));
	table_header($th);
    $k = $total_allocated = 0;
	
	//$myrow = get_chk_details($trans_no);
	$result = get_chk_details($trans_no);
	while ($myrow = db_fetch($result))
	{
					
		label_cell($myrow['pay_to']);
		label_cell($myrow['bank']);
		label_cell($myrow['chk_number']);
		label_cell(sql2date($myrow['chk_date']));
		label_cell(number_format2(abs($myrow['chk_amount']),user_price_dec()));
		
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

$voided = is_voided_display(ST_SUPPAYMENT, $trans_no, _("This payment has been voided."));

// now display the allocations for this payment
if (!$voided) 
{
	display_allocations_from(PT_SUPPLIER, $receipt['supplier_id'], ST_SUPPAYMENT, $trans_no, -$receipt['Total']);
}

end_page(true);
?>