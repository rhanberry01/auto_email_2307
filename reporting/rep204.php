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
$page_security = 'SA_SUPPLIERANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Outstanding GRNs Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_outstanding_GRN();

function getTransactions($fromsupp)
{
	$sql = "SELECT ".TB_PREF."grn_batch.id,
			".TB_PREF."purch_order_details.order_no, ".TB_PREF."purch_orders.reference,
			".TB_PREF."grn_batch.supplier_id,
			".TB_PREF."suppliers.supp_name,
			".TB_PREF."grn_items.item_code,
			".TB_PREF."grn_items.description,
			qty_recd,
			quantity_inv,
			std_cost_unit,
			act_price,
			unit_price,
			rcomments,
			rnotes
		FROM ".TB_PREF."grn_items,
			".TB_PREF."grn_batch,
			".TB_PREF."purch_order_details,
			".TB_PREF."purch_orders,
			".TB_PREF."suppliers
		WHERE ".TB_PREF."grn_batch.supplier_id=".TB_PREF."suppliers.supplier_id
		AND ".TB_PREF."grn_batch.id = ".TB_PREF."grn_items.grn_batch_id
		AND ".TB_PREF."grn_items.po_detail_item = ".TB_PREF."purch_order_details.po_detail_item
		AND qty_recd-quantity_inv <>0 
		AND ".TB_PREF."purch_orders.order_no = ".TB_PREF."purch_order_details.order_no ";

	if ($fromsupp != ALL_NUMERIC)
		$sql .= "AND ".TB_PREF."grn_batch.supplier_id =".db_escape($fromsupp)." ";
	$sql .= "ORDER BY ".TB_PREF."grn_batch.supplier_id,
			".TB_PREF."grn_batch.id";

    return db_query($sql, "No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_outstanding_GRN()
{
    global $path_to_root;

    $fromsupp = $_POST['PARAM_0'];
    $comments = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($fromsupp == ALL_NUMERIC)
		$from = _('All');
	else
		$from = get_supplier_name($fromsupp);
    $dec = user_price_dec();

	$cols = array(0, 40, 80, 190,	250, 320, 385, 450,	515);

	// $headers = array(_('GRN'), _('Order'), _('Item') . '/' . _('Description'), _('Qty Recd'), _('qty Inv'), _('Balance'),
		// _('Std Cost'), _('Value'));

	$aligns = array('left',	'left',	'left',	'right', 'right', 'right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Supplier'), 'from' => $from, 'to' => ''));

    $rep = new FrontReport(_('Items Received Report'), "OutstandingGRN", user_pagesize());

    $rep->Font();
    // $rep->Info($params, $cols, $headers, $aligns);
    // $rep->Header();
	
	if ($destination)	//excel
	{
		$headers = array(_('GRN'), _('Order'), _('Item') . '/' . _('Description'), _('Qty Recd'), _('Qty Inv'), _('Balance'),
		_('Std Cost'), _('Value'));
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header__();
	}
	else
	{
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header_();
	}

	$Tot_Val=0;
	$Supplier = '';
	$SuppTot_Val=0;
	$res = getTransactions($fromsupp);
	
	$rep->row += 20;

	While ($GRNs = db_fetch($res))
	{
		$dec2 = get_qty_dec($GRNs['item_code']);
		if ($Supplier != $GRNs['supplier_id'])
		{
			if ($Supplier != '')
			{
				$rep->NewLine(2);
				$rep->TextCol(0, 7, _('Total'));
				$rep->AmountCol(7, 8, $SuppTot_Val, $dec);
				$rep->Line($rep->row - 2);
				$rep->NewLine(3);
				$SuppTot_Val = 0;
			}
			$rep->NewLine();
			$rep->fontSize += 2;
			$rep->TextCol(0, 6, strtoupper($GRNs['supp_name']));
			$rep->fontSize -= 2;
			$Supplier = $GRNs['supplier_id'];
		}

		$rep->NewLine();
		
		// header	
		$rep->Font('bold');	
		$rep->TextCol(0, 1,	"GRN");
		$rep->TextCol(1, 2,	"Order");
		$rep->TextCol(2, 3,	"Item / Description");
		$rep->TextCol(3, 4, "Qty Recd");
		$rep->TextCol(4, 5,	"Qty Inv");
		$rep->TextCol(5, 6,	"Balance");	
		$rep->TextCol(6, 7, "Std Cost");	
		$rep->TextCol(7, 8,	"Value");	
		$rep->NewLine(1, 2);
		$rep->Font('');
		
		$rep->Line($rep->row + 4);
		$rep->NewLine();
		// header	
		
		$rep->TextCol(0, 1, $GRNs['id']);
		$rep->TextCol(1, 2, $GRNs['reference']);
		//$rep->TextCol(2, 3, $GRNs['item_code'] . '-' . $GRNs['description'] . '-' . $GRNs['rnotes']);
		$rep->TextCol(2, 3, $GRNs['item_code'] . '-' . $GRNs['description']);
		$rep->AmountCol(3, 4, $GRNs['qty_recd'], $dec2);
		$rep->AmountCol(4, 5, $GRNs['quantity_inv'], $dec2);
		$QtyOstg = $GRNs['qty_recd'] - $GRNs['quantity_inv'];
		$Value = ($GRNs['qty_recd'] - $GRNs['quantity_inv']) * $GRNs['std_cost_unit'];
		$rep->AmountCol(5, 6, $QtyOstg, $dec2);
		$rep->AmountCol(6, 7, $GRNs['std_cost_unit'], $dec);
		$rep->AmountCol(7, 8, $Value, $dec);
		$Tot_Val += $Value;
		$SuppTot_Val += $Value;

		$rep->NewLine(0, 1);
	}
	if ($Supplier != '')
	{
		$rep->NewLine(2);
		$rep->TextCol(0, 7, _('Total'));
		$rep->AmountCol(7, 8, $SuppTot_Val, $dec);
		$rep->Line($rep->row - 2);
		$rep->NewLine(3);
		$SuppTot_Val = 0;
	}
	$rep->fontSize += 2;
	$rep->NewLine();
	$rep->TextCol(0, 7, _('Grand Total'));
	$rep->AmountCol(7, 8, $Tot_Val, $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->fontSize -= 2;
    $rep->End();
}

?>