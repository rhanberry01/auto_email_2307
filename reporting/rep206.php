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
			order_no,
			".TB_PREF."grn_batch.supplier_id,
			".TB_PREF."suppliers.supp_name,
			".TB_PREF."grn_items.item_code,
			".TB_PREF."grn_items.description,
			qty_recd,
			quantity_inv,
			std_cost_unit,
			act_price,
			unit_price
		FROM ".TB_PREF."grn_items,
			".TB_PREF."grn_batch,
			".TB_PREF."purch_order_details,
			".TB_PREF."suppliers
		WHERE ".TB_PREF."grn_batch.supplier_id=".TB_PREF."suppliers.supplier_id
		AND ".TB_PREF."grn_batch.id = ".TB_PREF."grn_items.grn_batch_id
		AND ".TB_PREF."grn_items.po_detail_item = ".TB_PREF."purch_order_details.po_detail_item
		AND qty_recd-quantity_inv <>0 ";

	if ($fromsupp != ALL_NUMERIC)
		$sql .= "AND ".TB_PREF."grn_batch.supplier_id =".db_escape($fromsupp)." ";
	$sql .= "ORDER BY ".TB_PREF."grn_batch.supplier_id,
			".TB_PREF."grn_batch.id";

    return db_query($sql, "No transactions were returned");
}
function getTransactions2($fromsupp,$item,$enddate){
	$sql = "SELECT *
			FROM ".TB_PREF."purch_orders a, ".TB_PREF."purch_order_details b
			WHERE a.order_no=b.order_no";
			//	AND quantity_ordered>quantity_received";
		if ($fromsupp != ALL_NUMERIC)
		$sql .= " AND a.supplier_id =".db_escape($fromsupp)." ";
		if($enddate!=""&&$enddate!=null)
		$sql .= " AND b.delivery_date<=".db_escape(date2sql($enddate))." ";
		if($item!=""&&$item!=null)
		$sql .= " AND b.item_code=".db_escape($item);
	//	display_error($sql);
		return db_query($sql, "No transactions were returned");
		
}
//----------------------------------------------------------------------------------------------------

function print_outstanding_GRN()
{
    global $path_to_root;
	$enddate = $_POST['PARAM_0'];
	$item = $_POST['PARAM_1'];
    $fromsupp = $_POST['PARAM_2'];
    $comments = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_4'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($fromsupp == ALL_NUMERIC)
		$from = _('All');
	else
		$from = get_supplier_name($fromsupp);
    $dec = user_price_dec();

	$cols = array(0, 30, 170, 230,290, 350, 410, 480, 550);

	// $headers = array(_('Order #'), _('Item') . '/' . _('Description'), _('Qty Ord'), _('Qty Rcvd'), "Qty Inv", _('Outstanding'),
		// _('Unit Cost'), _('Value'));

	$aligns = array('left',	'center',	'right',	'right', 'right', 'right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Supplier'), 'from' => $from, 'to' => ''));

    $rep = new FrontReport(_('PO Summary Report'), "POSummary", user_pagesize());

    $rep->Font();
    // $rep->Info($params, $cols, $headers, $aligns);
    // $rep->Header();
	
	if ($destination)	//excel
	{
		$headers = array(_('Order #'), _('Item') . '/' . _('Description'), _('Qty Ord'), _('Qty Rcvd'), "Qty Inv", _('Outstanding'),
		_('Unit Cost'), _('Value'));
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
	$res = getTransactions2($fromsupp,$item,$enddate);

	$rep->row += 20;
	
	While ($GRNs = db_fetch($res))
	{
	//	$grn_item=db_fetch(db_query("SELECT sum(quantity_inv) as quantity"))
		$supp_name=get_supplier_name($GRNs['supplier_id']);
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
			$rep->TextCol(0, 6, strtoupper($supp_name_);
			$rep->fontSize -= 2;
			$Supplier = $GRNs['supplier_id'];
		}
		
		$rep->NewLine();
		
		// header	
		$rep->Font('bold');	
		$rep->TextCol(0, 1,	"Order #");
		$rep->TextCol(1, 2,	"Item / Description");
		$rep->TextCol(2, 3,	"Qty Ord");
		$rep->TextCol(3, 4, "Qty Rcvd");
		$rep->TextCol(4, 5,	"Qty Inv");
		$rep->TextCol(5, 6,	"Outstanding");	
		$rep->TextCol(6, 7, "Unit Cost");	
		$rep->TextCol(7, 8,	"Value");	
		$rep->NewLine(1, 2);
		$rep->Font('');
		
		$rep->Line($rep->row + 4);
		$rep->NewLine();
		// header	
		
		$rep->TextCol(0, 1, $GRNs['reference']);
		//$rep->TextCol(1, 2, $GRNs['order_no']);
		$rep->TextCol(1, 2, $GRNs['item_code'] . '-' . $GRNs['description']);
		$rep->AmountCol(2, 3, $GRNs['quantity_ordered'], $dec2);
		$rep->AmountCol(3, 4, $GRNs['quantity_received'], $dec2);
		$rep->AmountCol(4, 5, $GRNs['qty_invoiced'], $dec2);
		
		$QtyOstg = $GRNs['quantity_ordered'] - $GRNs['quantity_received'];
		$Value = ($GRNs['quantity_ordered'] - $GRNs['quantity_received']) * $GRNs['unit_price'];
		$rep->AmountCol(5, 6, $QtyOstg, $dec2);
		$rep->AmountCol(6, 7, $GRNs['unit_price'], $dec);
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
	$rep->NewLine(2);
	$rep->TextCol(0, 7, _('Grand Total'));
	$rep->AmountCol(7, 8, $Tot_Val, $dec);
	$rep->Line($rep->row - 2);
	$rep->NewLine();
	$rep->fontSize -= 2;
    $rep->End();
}

?>