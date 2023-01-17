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
$page_security = 'SA_GLSETUP';

$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Fix Purchase GL (VAT / NON-VAT)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

start_form();

function update_item_tax($stock_id, $tax)
{
	$sql = "SELECT pVatable FROM Products WHERE ProductID = $stock_id";
	$res = ms_db_query($sql);
	$ms_row = mssql_fetch_array($res);
	$ms_tax = $ms_row[0];
	
	if ($ms_tax == $tax)
		return true;
	
	$sql = "UPDATE 0_stock_master SET tax_type_id=$ms_tax WHERE stock_id= " . db_escape($stock_id);
	db_query($sql,'failed to update item tax');
}

function fix_nv_apv($type, $trans_no)
{
	$sql = "SELECT amount, tran_date
				FROM `0_gl_trans` a, 0_chart_master b
				WHERE a.account = b.account_code
				AND type = $type
				AND type_no = $trans_no
				AND account = '2000'";
	$res = db_query($sql);
	
	$row =db_fetch($res);
	$amount = $row[0];
	$tran_date = $row[1];
	
	global $Refs;
	$trans_type = '0';
	$ref   = $reference = get_next_reference($trans_type);
	$memo_ = "To correct vat and non vat purchase of transaction (tagged VAT but NON-VAT) TYPE: $type TRANS_NO: $trans_no";
	
	$trans_id = get_next_trans_no($trans_type);
	
	// reverse credit of APV
	$sql = "SELECT * FROM 0_gl_trans WHERE type = $type AND type_no = $trans_no AND amount > 0";
	$res = db_query($sql);
	while($row = db_fetch($res))
	{
		add_gl_trans($trans_type, $trans_id, sql2date($row['tran_date']), $row['account'], 0, 0, $memo_, -$row['amount']);
	}
	// debit side
	add_gl_trans($trans_type, $trans_id, sql2date($tran_date), '5400', 0, 0, $memo_, abs($amount));

	add_comments($trans_type, $trans_id, Today(), $memo_);
	$Refs->save($trans_type, $trans_id, $ref);

	add_audit_trail($trans_type, $trans_id, Today());	
}

function fix_vat_apv($type, $type_no, $ov_amount, $ov_gst)
{
	$sql = "SELECT amount, tran_date FROM 0_gl_trans WHERE type = $type AND type_no = $type_no AND account = '2000'";
	$res = db_query($sql);
	$row = db_fetch($res);
	$ap = round(abs($row[0]),2);
	$tran_date = sql2date($row[1]);
	
	if (round(($ov_amount + $ov_gst), 2) != $ap)
		return false;
	
	$sql = "SELECT * FROM 0_supp_invoice_items WHERE supp_trans_type = 20 AND supp_trans_no = $type_no";
	$res = db_query($sql);
	
	$new_nv_total = $new_pvat_total = $new_vat_total = 0;
	while($row = db_fetch($res))
	{
		if (!item_is_vatable($row['stock_id']))
			$new_nv_total += ($row['unit_price'] + $row['unit_tax']) * $row['quantity'];
		else
		{
			$gross = ($row['unit_price'] + $row['unit_tax']) * $row['quantity'];
			$pvat = round(($gross/1.12),2);
			$new_pvat_total += $pvat;
			$new_vat_total += $gross - $pvat;
		}
	}

	$sql = "SELECT * FROM 0_gl_trans WHERE type = $type AND type_no = $type_no";
	$res = db_query($sql);
	
	$pvat = $vat = $nv = 0;
	while($row = db_fetch($res))
	{
		if ($row['account'] == 2000)
			continue;
		if ($row['account'] == 5400) // nv
		{
			$nv = $row['amount'];
			continue;
		}
		if ($row['account'] == 5450) // pvat
		{
			$pvat = $row['amount'];
			continue;
		}
		if ($row['account'] == 1410010) // vat
		{
			$vat = $row['amount'];
			continue;
		}
		
	}
	
	if (round($nv,2) == round($new_nv_total,2))
		return false;
	// repopulate GL
	// 5400	purch non vat
	// 5450 	purch vat
	// 1410010	vat
	
	if (round($new_nv_total+$new_pvat_total+$new_vat_total ,2) != round($ap,2))
		return false;
	
	global $Refs;
	$trans_type = '0';
	$ref   = $reference = get_next_reference($trans_type);
	$memo_ = "To correct vat and non vat purchase of transaction  (tagged NON-VAT but VAT)TYPE: $type TRANS_NO: $type_no";
	
	$trans_id = get_next_trans_no($trans_type);
	
	// reverse credit of APV
	$sql = "SELECT * FROM 0_gl_trans WHERE type = $type AND type_no = $type_no AND amount > 0";
	$res = db_query($sql);
	while($row = db_fetch($res))
	{
		add_gl_trans($trans_type, $trans_id, sql2date($row['tran_date']), $row['account'], 0, 0, $memo_, -$row['amount']);
	}
	// debit side
	add_gl_trans($trans_type, $trans_id, $tran_date, '5400', 0, 0, $memo_, abs($new_nv_total));
	add_gl_trans($trans_type, $trans_id, $tran_date, '5450', 0, 0, $memo_, abs($new_pvat_total));
	add_gl_trans($trans_type, $trans_id, $tran_date, '1410010', 0, 0, $memo_, abs($new_vat_total));

	add_comments($trans_type, $trans_id, Today(), $memo_);
	$Refs->save($trans_type, $trans_id, $ref);

	add_audit_trail($trans_type, $trans_id, Today());	
}

function item_is_vatable($stock_id)
{
	$sql  = "SELECT tax_type_id FROM 0_stock_master WHERE stock_id = $stock_id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return ($row['tax_type_id'] == 1);
}

function check_supp_invoice_item($type, $trans_no)
{
	$sql = "SELECT SUM((unit_price+unit_tax) * quantity) FROM 0_supp_invoice_items
				WHERE supp_trans_no = $trans_no
				AND supp_trans_type = $type
				AND stock_id != ''";
	// display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

if (isset($_POST['fix_now']))
{
	// copy all item's VAT/NON-VAt tagging
	$sql = "SELECT stock_id, tax_type_id FROM 0_stock_master";
	$res = db_query($sql);
	
	while($row = db_query($res))
	{
		update_item_tax($row['stock_id'], $row['tax_type_id']);
	}
	
	// fix non vat first
	$sql = "SELECT DISTINCT a.type, a.trans_no, a.supplier_id, a.ov_amount, a.ov_gst
				FROM 0_supp_trans a, 0_gl_trans b
				WHERE a.type = 20
				AND a.type = b.type
				AND a.trans_no = b.type_no
				AND account = 5450
				AND b.amount != 0
				AND a.tran_date >= '2016-01-01'
				AND supplier_id IN (SELECT supplier_id FROM 0_suppliers WHERE tax_group_id != 1)";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		$supp_inv_item_total = check_supp_invoice_item($row['type'], $row['trans_no']);
		
		if (abs(($row['ov_amount'] + $row['ov_gst']) -  $supp_inv_item_total) > 5)
		{
			// display_error('pass');
			continue;
		}
		fix_nv_apv($row['type'], $row['trans_no']);
	}
	
	//fix vat
	$sql = "SELECT DISTINCT a.type, a.trans_no, a.supplier_id, ov_amount, ov_gst
				FROM 0_supp_trans a, 0_gl_trans b
				WHERE a.type = 20
				AND a.type = b.type
				AND a.trans_no = b.type_no
				AND account = 5400
				AND b.amount != 0
				AND a.tran_date >= '2016-01-01'
				AND supplier_id IN (SELECT supplier_id FROM 0_suppliers WHERE tax_group_id = 1)";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		$supp_inv_item_total = check_supp_invoice_item($row['type'], $row['trans_no']);
		
		// display_error(($row['ov_amount'] + $row['ov_gst']));
		// display_error($supp_inv_item_total);
		// display_error('_________________');
		if (abs(($row['ov_amount'] + $row['ov_gst']) -  $supp_inv_item_total) > 5)
		{
			// display_error('pass');
			continue;
		}
		fix_vat_apv($row['type'], $row['trans_no'], $row['ov_amount'], $row['ov_gst']);
	}
	
	//fix returns
	display_notification('SUCCESS');
}

submit_center('fix_now', 'GO');
end_form();

end_page();
?>
