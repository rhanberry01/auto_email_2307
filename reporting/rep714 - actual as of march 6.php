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
$page_security = 'SA_GLREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	GL Accounts Transactions
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_GL_transactions();

//----------------------------------------------------------------------------------------------------
function get_supp_inv_total($supp_id, $from, $to)
{
	$sql = "SELECT SUM(round(ov_amount+ov_gst,2))
			FROM 0_supp_trans b 
			WHERE supplier_id = $supp_id
			AND type = 20 
			AND del_date >= '".date2sql($from)."'
			AND del_date <= '".date2sql($to)."'";
	$res = db_query($sql);
	$row = db_fetch($res);
		
	return ($row[0] + get_supp_discrepancy_total($supp_id, $from, $to));
}

function get_supp_discrepancy_total($supp_id, $from, $to)
{
	$sql = "SELECT SUM(actual_invoice_total) 
		FROM 0_grn_batch a, 0_discrepancy_header b
		WHERE a.delivery_date >= '".date2sql($from)."'
		AND a.delivery_date <= '".date2sql($to)."'
		AND a.supplier_id = $supp_id
		AND a.locked = 1
		AND a.id = b.grn_batch_id
		AND resolved_by = 0";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function print_GL_transactions()
{
	global $path_to_root, $systypes_array, $systypes_array_short;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$trade = $_POST['PARAM_2'];
	$vat = $_POST['PARAM_3'];
	$rba = $_POST['PARAM_4'];// 1 0 2
	$destination = $_POST['PARAM_5'];
	
	$rba_str = 'ALL';
	
	if ($rba == 1)
		$rba_str = 'RETAIL ONLY';
	else if ($rba == 0)
		$rba_str = 'BELEN TAN ONLY';
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$rep = new FrontReport(_('Purchase Report'), "PurchaseReport", user_pagesize(),9 ,'L');
	$dec = user_price_dec();


	// $cols = array(0, 100, 230, 360, 440, 555);
	// $aligns = array('left', 'left', 'center', 'center',	'right');
	// $headers = array('TIN', 'Supplier Name', 'Delivery Date', 'Invoice', 'Sum of AMOUNT');

	$cols = array(0, 75, 275, 350, 425, 500, 575, 650, 725, 800);
	$aligns = array('left', 'left', 'center', 'left', 'right', 'right', 'right', 'right');
	$headers = array('TIN', 'Supplier Name', 'Delivery Date', 'Inv. #', 'Amount','Purch. NON-VAT', 'Purch. VAT', '12% VAT');
	//'Discount', 


	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => 'Invoice Type','from' => ($trade ? 'TRADE':'NON-TRADE')),
    				    3 => array('text' => 'Supplier Type','from' => ($vat ? 'VAT':'NON-VAT')),
    				    4 => array('text' => 'Retaill / Belen Tan','from' => $rba_str));
						
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	
	$tax_group_id = 1;
	
	if (!$vat)
		$tax_group_id = 2;
		
	if ($trade)
		$t_nt_sql = " AND b.reference NOT LIKE 'NT%' ";
	
	if (!$trade)
		$t_nt_sql = " AND b.reference LIKE 'NT%' ";
		
	$rba_apv_sql = $rba_rr_sql = '';
	if ($rba == 1) //retail
	{
		$rba_apv_sql = " AND ewt = 0";
		
		$sql = "SELECT a.id  FROM 0_grn_batch a, 0_purch_orders b
				WHERE a.purch_order_no = b.order_no
				AND b.reference IN (59418,59659,60421,59840,59585,60283,
					59878,60098,59782,60470,60302,60179,
					59973,59765,59565,60490,60349,59497,
					59599,59674)";
		// display_error($sql);die;
		$res = db_query($sql);
		
		$rr_belen = array();
		
		while($rr_b_row = db_fetch($res))
		{
			$rr_belen[] = $rr_b_row[0];
		}
		
		$rba_rr_sql = " AND a.id NOT IN (".implode(',',$rr_belen).")";
	}
	else if ($rba == 0) // belen tan
	{
		$rba_apv_sql = " AND ewt > 0";
		
		$sql = "SELECT a.id  FROM 0_grn_batch a, 0_purch_orders b
				WHERE a.purch_order_no = b.order_no
				AND b.reference IN (59418,59659,60421,59840,59585,60283,
					59878,60098,59782,60470,60302,60179,
					59973,59765,59565,60490,60349,59497,
					59599,59674)";
		$res = db_query($sql);
		
		$rr_belen = array();
		
		while($rr_b_row = db_fetch($res))
		{
			$rr_belen[] = $rr_b_row[0];
		}
		$rba_rr_sql = " AND a.id IN (".implode(',',$rr_belen).")";
	}
	
	$sql = "(SELECT b.supplier_id, a.supp_name, a.gst_no, b.del_date, CONCAT('APV# ',reference) as reference_, 
				supp_reference, (ov_amount+ov_gst) as total, type, trans_no
			FROM 0_suppliers a, 0_supp_trans b
			WHERE a.supplier_id = b.supplier_id
			AND type = 20
			AND del_date >= '".date2sql($from)."'
			AND del_date <= '".date2sql($to)."'
			AND tax_group_id = $tax_group_id
			AND ov_amount > 0
			$t_nt_sql
			$rba_apv_sql)";
			// display_error($sql);die;
	$sql .= " UNION
			(SELECT 
			c.supplier_id, c.supp_name, c.gst_no, delivery_date as del_date,
			'in discrepancy report' as reference_, source_invoice_no as supp_reference,
			actual_invoice_total as total, 25 as type, a.id as trans_no
			FROM 0_grn_batch a, 0_discrepancy_header b, 0_suppliers c
			WHERE a.delivery_date >= '".date2sql($from)."' AND a.delivery_date <= '".date2sql($to)."'
			AND a.locked = 1 AND a.id = b.grn_batch_id AND resolved_by = 0
			AND c.supplier_id = a.supplier_id
			AND c.tax_group_id = $tax_group_id
			$rba_rr_sql)
			
			ORDER BY supp_name, reference_, del_date";

	$res = db_query($sql,'error.');
	// echo $sql;die;
	$last_supp_id = $supp_name = $last_gst = '';
	$counter = $supp_total_ = $ov_total = 0;
	
	$p_nv_total = $p_v_total = $v_total = 0;
	
	$details = array();
	while ($row = db_fetch($res))
	{
		$counter = 1;
		// $row['supp_name'] = preg_replace("/\([^)]+\)/","",$row['supp_name']);
		// if ($supp_name != $row['supp_name'] AND $supp_name != '')
		// {	
			// $rep->NewLine();
			// $rep->Font('bold');
			// $rep->TextCol(0, 1,	$last_gst);
			// $rep->TextCol(1, 4, $supp_name);
			// $rep->TextCol(4, 6, '');
			// $rep->TextCol(6, 7, number_format2($supp_total_,2));
			// $rep->Font('');
			
			// $rep->Line($rep->row - 2);
			// $rep->NewLine(1.5,1);
			
			// foreach($details as $detail)
			// {
				// if ($detail[0] == 'in discrepancy report')
					// $rep->Font('italic');
				
				// $rep->TextCol(0, 1, $detail[0], -5);
				// // $rep->Font('bold');
				// $rep->TextCol(1, 2, $detail[1]);
				// // $rep->Font('');
				// $rep->TextCol(2, 3, $detail[2]);
				// $rep->TextCol(3, 4, $detail[3]); // p nv
				// $rep->TextCol(4, 5, $detail[4]); // p v
				// $rep->TextCol(5, 6, $detail[5]); // vat
				// // $rep->TextCol(6, 7, number_format2($row['ov_discount'],2)); // disc
				// $rep->TextCol(6, 7, $detail[6]); // sum
				// $rep->NewLine(1.5, 1);
				
				// if ($detail[0] == 'in discrepancy report')
					// $rep->Font('');
			// }
			
			// $counter = 0;
			// $supp_total_ = 0;
			// $details = array();
		// }
		
		$last_supp_id = $row['supplier_id'];
		$supp_name = $row['supp_name'];
		$last_gst = $row['gst_no'];
		
		// totals -----------------------------------------------------
		$vat = $p_nv = $p_v = 0;
		
		if ($row['type'] == 20)
		{
			$company_pref = get_company_prefs();
			$p_nv = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_non_vat"]);
			$p_nv += get_gl_trans_amount($row['type'], $row['trans_no'], '1440');
			$p_v = get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_vat"]);
			$p_v += get_gl_trans_amount($row['type'], $row['trans_no'], '4000030');
			
			// if ($vat)
			// {
				$vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410');
				$vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410010');
				$vat += get_gl_trans_amount($row['type'], $row['trans_no'], '1410011');
			// }
			
			if ($vat == 0)
			{
				$p_nv += $p_v;
				$p_v = 0;
			}
			
			if ($p_v == 0 AND $p_nv == 0 AND $vat != 0)
				$p_v = round2($row['total']-$vat,2);
			
			if ($p_nv+$p_v+$vat != 0)
				$row['total'] = round2($p_nv+$p_v+$vat,2);
			else
				$p_nv = $row['total'];
		}
		else
		{
			$p_v = round($row['total']/(1.12),2);
			$vat = round($row['total'] - $p_v,2);
		}
		
		$details[$counter][0] = $row['reference_'];
		$details[$counter][1] = sql2date($row['del_date']);
		$details[$counter][2] = $row['supp_reference'];
		$details[$counter][3] = round($p_nv,2);
		$details[$counter][4] = round($p_v,2);
		$details[$counter][5] = round($vat,2);
		$details[$counter][6] = round($row['total'],2);
		
		$p_nv_total += round($p_nv,2);
		$p_v_total += round($p_v,2); 
		$v_total += round($vat,2); 
		$ov_total += round($row['total'],2);
		
		$rep->TextCol(0, 1,	trim($last_gst));
		$rep->TextCol(1, 2, $supp_name);
		$rep->TextCol(2, 3, $details[$counter][1]);
		$rep->TextCol(3, 4, $details[$counter][2]);
		$rep->AmountCol(4, 5, $details[$counter][6],2);
		$rep->AmountCol(5, 6, $details[$counter][3],2);
		$rep->AmountCol(6, 7, $details[$counter][4],2);
		$rep->AmountCol(7, 8, $details[$counter][5],2);
		
		// $rep->TextCol(6, 7, number_format2($supp_total_,2));
		// $rep->TextCol(0, 1, $detail[0], -5);
		// // $rep->Font('bold');
		// $rep->TextCol(1, 2, $detail[1]);
		// // $rep->Font('');
		// $rep->TextCol(2, 3, $detail[2]);
		// $rep->TextCol(3, 4, $detail[3]); // p nv
		// $rep->TextCol(4, 5, $detail[4]); // p v
		// $rep->TextCol(5, 6, $detail[5]); // vat
		// // $rep->TextCol(6, 7, number_format2($row['ov_discount'],2)); // disc
		// $rep->TextCol(6, 7, $detail[6]); // sum
		
		
		// $supp_total_ += $row['total'];
		// $ov_total += $row['total'];
		$rep->NewLine();
	}
	
	$rep->Font('bold');
	$rep->Line($rep->row + 10);
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 2, '');
	$rep->TextCol(2, 3, '');
	$rep->TextCol(3, 4, 'TOTAL : ');
	$rep->AmountCol(4, 5, $ov_total,2);
	$rep->AmountCol(5, 6, $p_nv_total,2);
	$rep->AmountCol(6, 7, $p_v_total,2);
	$rep->AmountCol(7, 8, $v_total,2);
	$rep->Font('');

	// $rep->NewLine();
	// $rep->Font('bold');
	// $rep->TextCol(0, 1,	$last_gst);
	// $rep->TextCol(1, 4, '  '.$supp_name);
	// $rep->TextCol(4, 6, '  ');
	// $rep->TextCol(6, 7, number_format2($supp_total_,2));
	// $rep->Font('');
	
	// $rep->Line($rep->row - 2);
	// $rep->NewLine(1.5,1);
	
	// foreach($details as $detail)
	// {
		// $rep->TextCol(0, 1, $detail[0], -5);
		// $rep->Font('bold');
		// $rep->TextCol(1, 2, $detail[1]);
		// $rep->Font('');
		// $rep->TextCol(2, 3, $detail[2]);
		// $rep->TextCol(3, 4, $detail[3]); // p nv
		// $rep->TextCol(4, 5, $detail[4]); // p v
		// $rep->TextCol(5, 6, $detail[5]); // vat
		// // $rep->TextCol(6, 7, number_format2($row['ov_discount'],2)); // disc
		// $rep->TextCol(6, 7, $detail[6]); // sum
		// $rep->NewLine(1.5, 1);
	// }
			
	// $rep->Line($rep->row + 14,2);
	// $rep->Font('bold');
	// $rep->TextCol(1, 2, 'Grand Total:');
	// $rep->TextCol(6, 7, number_format2($ov_total,2));
	// $rep->Font('');
	$rep->NewLine();
	
	
	$rep->End();
}

?>