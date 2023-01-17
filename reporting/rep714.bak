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
	$destination = $_POST['PARAM_4'];

	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$rep = new FrontReport(_('GL Account Transactions'), "GLAccountTransactions", user_pagesize(),9 ,'P');
	$dec = user_price_dec();


	$cols = array(0, 100, 230, 360, 440, 555);

	$aligns = array('left', 'left', 'center', 'center',	'right');

	$headers = array('TIN', 'Supplier Name', 'Delivery Date', 'Invoice', 'Sum of AMOUNT');


	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => 'Invoice Type','from' => ($trade ? 'TRADE':'NON-TRADE')),
    				    3 => array('text' => 'Supplier Type','from' => ($vat ? 'VAT':'NON-VAT')));
						
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	
	$tax_group_id = 1;
	
	if (!$vat)
		$tax_group_id = 2;
		
	$t_nt_sql = " AND b.reference NOT LIKE 'NT%' ";
	
	if (!$trade)
		$t_nt_sql = " AND b.reference LIKE 'NT%' ";
	
	$sql = "(SELECT b.supplier_id, a.supp_name, a.gst_no, b.del_date, CONCAT('APV# ',reference) as reference_, 
				supp_reference, (ov_amount+ov_gst) as total
			FROM 0_suppliers a, 0_supp_trans b
			WHERE a.supplier_id = b.supplier_id
			AND type = 20
			AND del_date >= '".date2sql($from)."'
			AND del_date <= '".date2sql($to)."'
			AND tax_group_id = $tax_group_id
			AND ov_amount > 0
			$t_nt_sql)
			UNION
			(SELECT 
			c.supplier_id, c.supp_name, c.gst_no, delivery_date as del_date,
			'in discrepancy report' as reference_, source_invoice_no as supp_reference,
			actual_invoice_total as total
			FROM 0_grn_batch a, 0_discrepancy_header b, 0_suppliers c
			WHERE a.delivery_date >= '2013-05-01' AND a.delivery_date <= '2013-05-31' 
			AND a.locked = 1 AND a.id = b.grn_batch_id AND resolved_by = 0
			AND c.supplier_id = a.supplier_id
			AND c.tax_group_id = 1)
			
			ORDER BY supp_name, reference_, del_date";
	$res = db_query($sql,'asdasd');
	
	$last_supp_id = $supp_name = '';
	$ov_total = 0;
	while ($row = db_fetch($res))
	{
		// $row['supp_name'] = preg_replace("/\([^)]+\)/","",$row['supp_name']);
		if ($supp_name != $row['supp_name'])
		{
			// if ($last_supp_id != '') // begins on 2nd supplier
			// {
				// $discrepancy_res = get_supp_discrepancy($last_supp_id, $from, $to);
				// while($d_row = db_fetch($discrepancy_res))
				// {
					// $rep->TextCol(1, 2,'in discrepancy report');
					// $rep->Font('bold');
					// $rep->TextCol(2, 3,	sql2date($d_row['delivery_date']));
					// $rep->Font('');
					// $rep->TextCol(3, 4, $d_row['source_invoice_no']);
					// $rep->TextCol(4, 5, number_format2($d_row['actual_invoice_total'],2));
					// $rep->NewLine(2, 1);
				// }
			// }
			
			$supp_inv_totals = get_supp_inv_total($row['supplier_id'], $from, $to);
			$ov_total += $supp_inv_totals;
			$rep->NewLine();
			$rep->TextCol(0, 1,	$row['gst_no']);
			$rep->Font('bold');
			$rep->TextCol(1, 4,$row['supp_name']);
			$rep->TextCol(4, 5, number_format2($supp_inv_totals,2));
			$rep->Font('');
			$supp_name = $row['supp_name'];
			$rep->Line($rep->row - 2);
			$rep->NewLine(2,1);
		}
		
		$rep->TextCol(1, 2, $row['reference_']);
		$rep->Font('bold');
		$rep->TextCol(2, 3,	sql2date($row['del_date']));
		$rep->Font('');
		$rep->TextCol(3, 4, $row['supp_reference']);
		$rep->TextCol(4, 5, number_format2($row['total'],2));
		$rep->NewLine(2, 1);
		
		$last_supp_id = $row['supplier_id'];
	}
	
	// $discrepancy_res = get_supp_discrepancy($last_supp_id, $from, $to);
	// while($d_row = db_fetch($discrepancy_res))
	// {
		// $rep->TextCol(1, 2,'in discrepancy report');
		// $rep->Font('bold');
		// $rep->TextCol(2, 3,	sql2date($d_row['delivery_date']));
		// $rep->Font('');
		// $rep->TextCol(3, 4, $d_row['source_invoice_no']);
		// $rep->TextCol(4, 5, number_format2($d_row['actual_invoice_total'],2));
		// $rep->NewLine(2, 1);
	// }
	$rep->Line($rep->row + 14,2);
	$rep->Font('bold');
	$rep->TextCol(1, 2, 'Grand Total:');
	$rep->TextCol(4, 5, number_format2($ov_total,2));
	$rep->Font('');
	$rep->NewLine();
	
	
	$rep->End();
}

?>