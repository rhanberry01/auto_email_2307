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

include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View AP Voucher"), true, false, "", $js);

if (isset($_GET["trans_no"]))
{
	$trans_no = $_GET["trans_no"];
} 
elseif (isset($_POST["trans_no"]))
{
	$trans_no = $_POST["trans_no"];
}

$supp_trans = new supp_trans();
$supp_trans->is_invoice = true;

read_supp_invoice($trans_no, ST_CWODELIVERY, $supp_trans);

$supplier_curr_code = get_supplier_currency($supp_trans->supplier_id);

//display_heading(_("AP Voucher") . " # " . getInvRef($trans_no));
display_heading(_("CWO DELIVERY"));
echo "<br>";

start_table("$table_style width=95%");   
start_row();
label_cells(_("Supplier"), $supp_trans->supplier_name, "class='tableheader2'");
label_cells(_("APV No."), $supp_trans->reference, "class='tableheader2'");
label_cells(_("Supplier's Reference"), $supp_trans->supp_reference, "class='tableheader2'");
end_row();
start_row();
label_cells(_("APV Date"), $supp_trans->tran_date, "class='tableheader2'");
label_cells(_("Due Date"), $supp_trans->due_date, "class='tableheader2'");
label_cells(_("Delivery Date"), $supp_trans->del_date, "class='tableheader2'");
if (!is_company_currency($supplier_curr_code))
	label_cells(_("Currency"), $supplier_curr_code, "class='tableheader2'");
end_row();
comments_display_row(ST_CWODELIVERY, $trans_no);

end_table(1);

$total_gl = display_gl_items($supp_trans, 2);
$total_grn = display_grn_items($supp_trans, 2);

$display_sub_tot = number_format2($total_gl+$total_grn,user_price_dec());
$supp_trans->ov_nv = $supp_trans->get_non_vat_item_total();

start_table("width=95% $table_style");

if (!$supp_trans->nt)
{
// label_row(_("Sub Total"), $display_sub_tot, "align=right", "nowrap align=right width=15%");

$res = get_gl_trans(ST_CWODELIVERY, $trans_no, true);

$tots = array();
while ($rrr = db_fetch($res))
{
	$tots[$rrr['account']] = abs($rrr['amount']);
}

if($tots[get_company_pref('purchase_non_vat')] > 0)
	label_row(_("Purchase NON-VAT:"), price_format($tots[get_company_pref('purchase_non_vat')]), "align=right", "nowrap align=right width=15%");
if($tots[get_company_pref('purchase_vat')] > 0)
	label_row(_("Purchase VAT:"), price_format($tots[get_company_pref('purchase_vat')]), "align=right", "nowrap align=right width=15%");

$tax_items = get_trans_tax_details(ST_CWODELIVERY, $trans_no);
display_supp_trans_tax_details($tax_items, 1);

$total_grn = $supp_trans->ov_amount + $supp_trans->ov_gst;
$display_total = number_format2($total_grn ,user_price_dec());


label_row(_("TOTAL INVOICE"), price_format($supp_trans->ov_amount + $supp_trans->ov_gst), "colspan=1 align=right", "nowrap align=right");

	if ($supp_trans->ov_discount > 0)
	{
		$company_pref = get_company_prefs();
		
		$sql = "SELECT * FROM ".TB_PREF."gl_trans
				WHERE account = ".$company_pref["pyt_discount_act"]."
				AND type = ".ST_CWODELIVERY."
				AND type_no = $trans_no";
		$res = db_query($sql);
		
		if (db_num_rows($res) > 0)
			label_row(_("Purchase Discount:"), price_format($supp_trans->ov_discount), "align=right", "nowrap align=right width=15%");
		else
			label_row(_("Purchase Returns:"), price_format($supp_trans->ov_discount), "align=right", "nowrap align=right width=15%");
	}
	if ($supp_trans->ewt > 0)
	{
		$display_total = number_format2($total_grn - round2($supp_trans->ewt,2) ,user_price_dec());
		label_row(_("less ".$supp_trans->ewt_percent."% EWT :"), '('.number_format2($supp_trans->ewt,2).')', "align=right", "nowrap align=right width=15%");
		
		if ($total_gl != 0)
		{
			label_row(_("Items Net :"), price_format($total_grn-round2($supp_trans->ewt,2)), "align=right", "nowrap align=right width=15%");
			label_row(_("GL Total :"), price_format($total_gl), "align=right", "nowrap align=right width=15%");
		}
				
		label_row(_("NET :"), price_format($total_grn - round2($supp_trans->ewt,2) + $total_gl), "align=right", "nowrap align=right width=15%");
	}
}
else
{	
	label_row(_("Invoice Total:"), price_format($total_gl), "align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
	
	if($supp_trans->ewt_percent > 0)
	{
		label_row(_("less ".$supp_trans->ewt_percent."% EWT:"), '('.price_format($supp_trans->ewt).')', "align=right", "align=right");
		label_row(_("NET:"), price_format($total_gl-$supp_trans->ewt), "align=right style='font-weight:bold;'", "align=right style='font-weight:bold;'");
	}
}

end_table(1);

is_voided_display(ST_CWODELIVERY, $trans_no, _("This invoice has been voided."));

end_page(true);

?>