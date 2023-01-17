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
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

//----------------------------------------------------------------------------------------------------
set_time_limit(0);
print_unpaid_apv();

//----------------------------------------------------------------------------------------------------
function get_rr_total_using_po_price($grn_id)
{
	$sql = "SELECT SUM(a.qty_recd *(SELECT extended/quantity_ordered 
									FROM 0_purch_order_details 
									WHERE po_detail_item = a.po_detail_item)) 
			FROM `0_grn_items` a 
			WHERE `grn_batch_id` = $grn_id ";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function print_unpaid_apv()
{
	global $path_to_root;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
	$rep = new FrontReport(_('Unpaid Sales Invoice (up to given date)'), "Unpaid Sales Invoice (up to given date)", user_pagesize(),9 ,'P');
	$cols = array(0, 55, 115, 175, 350, 420, 500, 550);

	$aligns = array('left', 'left', 'left', 'center', 'left', 'right','center');

	$headers = array('APV #', 'Del. Date', 'APV Date' ,'Supplier', 'Invoice #', 'Amount ','CV #');


	$params =   array( 	0 => '',
    				    1 => array('text' => _('APV Date'), 'from' => $from, 'to' => $to));
						
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	
	// if(a.cv_id != 0,(
				// SELECT x.tran_date FROM 0_supp_trans x WHERE x.type=22
				// AND x.ov_amount < 0 AND x.cv_id = a.cv_id),'0000-00-00') as payment_date

	$sql = "SELECT a.supplier_id, a.reference as apv_no, a.del_date as delivery_date, a.tran_date as apv_date, 
			b.supp_name, a.supp_reference as invoice_no, 
			round(a.ov_amount+a.ov_gst,2) as invoice_amount, c.cv_no
			FROM (0_supp_trans a, 0_suppliers b) 
			LEFT JOIN 0_cv_header c ON a.cv_id = c.id 
			WHERE a.type = '20' 
			AND a.ov_amount > '0' 
			AND a.tran_date >= '".date2sql($from)."'
			AND a.tran_date <= '".date2sql($to)."'
			AND a.supplier_id = b.supplier_id 
			AND	(a.cv_id = 0 	OR 
					((SELECT x.tran_date FROM 0_supp_trans x WHERE x.type=22
						AND x.ov_amount < 0 AND x.cv_id = a.cv_id) > '".date2sql($to)."')
					OR
					((SELECT x.tran_date FROM 0_supp_trans x WHERE x.type=22
						AND x.ov_amount < 0 AND x.cv_id = a.cv_id) IS NULL)
				)
			ORDER BY supp_name, a.tran_date";
	// echo $sql;die;
	$res = db_query($sql);
	
	$rr_total = $supp_inv_total = $inv_total = 0;
	$last_supp_id = '';
	while($row = db_fetch($res))
	{
	
		if ($last_supp_id != '' AND $last_supp_id != $row['supplier_id'])
		{
			// show total per supplier
			if ($rr_total > 1)
			{
				$rep->Line($rep->row+9);		
				$rep->font('b');
				$rep->TextCol(0, 1,	'');
				$rep->TextCol(1, 2,	'');
				$rep->TextCol(2, 3,	'');
				$rep->TextCol(3, 4,	'');
				$rep->TextCol(4, 5,	'');
				$rep->TextCol(5, 6,	number_format2($supp_inv_total,2));
				$rep->TextCol(6, 7,	'');
				$rep->font('');
				$rep->NewLine(1.5);
			}
			$rr_total = $supp_inv_total = 0;
		}
		
		$last_supp_id = $row['supplier_id'];
		$rr_total ++;
		
		$rep->TextCol(0, 1,	$row['apv_no']);
		$rep->TextCol(1, 2,	sql2date($row['delivery_date']));
		$rep->TextCol(2, 3,	sql2date($row['apv_date']));
		$rep->TextCol(3, 4,	$row['supp_name'],0,0,0,0,null,1,0,'left');
		$rep->TextCol(4, 5,	'  '.$row['invoice_no']);
		$rep->AmountCol(5, 6, $row['invoice_amount'],2);
		$rep->TextCol(6, 7,	'  '.$row['cv_no'],0,0,0,0,null,1,0,'left');
		$rep->NewLine();
		
		$inv_total += round($row['invoice_amount'],2);
		$supp_inv_total += round($row['invoice_amount'],2);
	}
	$rep->Line($rep->row+9);		
	$rep->font('b');
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 2,	'');
	$rep->TextCol(2, 3,	'');
	$rep->TextCol(3, 4,	'');
	$rep->TextCol(4, 5,	'');
	$rep->AmountCol(5, 6, $supp_inv_total,2);
	$rep->TextCol(6, 7,	'');
	$rep->font('');
	$rep->NewLine(1.5);
	
	$rep->Line($rep->row+9);		
	$rep->font('b');
	$rep->TextCol(3, 5,	'Total Unpaid APV:  ',0,0,0,0,null,1,0,'right');
	$rep->AmountCol(5, 6, $inv_total,2);
	$rep->NewLine();
	
	//==================================================================================================
	$cols = array(0, 55, 115, 175, 350, 420, 500, 550);

	$aligns = array('left', 'left', 'left', 'center', 'left', 'right','center');

	$headers = array('Receiving #', 'PO #', 'Del. Date' ,'Supplier', 'Invoice #', 'Amount ','');


	$params =   array( 	0 => 'with Discrepancy',
    				    1 => array('text' => _('Delivery Date'), 'from' => $from, 'to' => $to));
						
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	
	$sql = "SELECT a.*,c.supp_name, d.reference as po_no
			FROM 0_grn_batch a, 0_discrepancy_header b, 0_suppliers c, 0_purch_orders d
			WHERE a.delivery_date >= '".date2sql($from)."' AND a.delivery_date <= '".date2sql($to)."'
			AND a.locked = 1 AND a.id = b.grn_batch_id AND resolved_by = 0
			AND d.order_no = a.purch_order_no
			AND c.supplier_id = a.supplier_id
			ORDER BY c.supp_name";
	// display_error($sql);die;
	$res = db_query($sql);
	
	$rr_total = 0;
	while($row = db_fetch($res))
	{
		$rr_amount = get_rr_total_using_po_price($row['id']);
		$rep->TextCol(0, 1,	ltrim($row['reference'],'0'));
		$rep->TextCol(1, 2,	ltrim($row['po_no'],'0'));
		$rep->TextCol(2, 3,	sql2date($row['delivery_date']));
		$rep->TextCol(3, 4,	$row['supp_name'],0,0,0,0,null,1,0,'left');
		$rep->TextCol(4, 5,	'  '.$row['source_invoice_no']);
		$rep->AmountCol(5, 6, $rr_amount,2);
		$rep->NewLine();
		
		$rr_total += round($rr_amount,2);
	}
	$rep->Line($rep->row+9);		
	$rep->font('b');
	$rep->TextCol(3, 5,	'Total of with Discrepancy:  ',0,0,0,0,null,1,0,'right');
	$rep->AmountCol(5, 6,	$rr_total,2);
	$rep->NewLine();
	$rep->End();
}

?>