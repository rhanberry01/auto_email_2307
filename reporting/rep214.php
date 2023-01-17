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

print_pdcc_for_payments();

function print_pdcc_for_payments()
{
    global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    $supplier = $_POST['PARAM_2'];
    $or_num = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($supplier == ALL_NUMERIC)
		$supp = _('All');
	else
		$supp = get_supplier_name($supplier);
    $dec = user_price_dec();

	$cols = array(0, 200, 280, 340, 400, 460, 530);

	$headers = array(_('Payee'), _('Bank'), _('OR #'), _('Check #'), _('Check Date'), _('Amount'));

	$aligns = array('left',	'left',	'left',	'left', 'left', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => $supp, 'to' => ''));

    $rep = new FrontReport(_('PDC Report for Payments'), "PDCPayments", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$sql = "SELECT
				".TB_PREF."cheque_details.id,
				".TB_PREF."cheque_details.bank_trans_id,
				".TB_PREF."cheque_details.bank,
				".TB_PREF."cheque_details.branch,
				".TB_PREF."cheque_details.chk_number,
				".TB_PREF."cheque_details.chk_date,
				".TB_PREF."bank_trans.amount,
				".TB_PREF."bank_trans.*
			FROM ".TB_PREF."cheque_details, ".TB_PREF."bank_trans
			WHERE ".TB_PREF."cheque_details.chk_date >= '".date2sql($from)."'
			AND ".TB_PREF."cheque_details.chk_date <= '".date2sql($to)."'
			AND ".TB_PREF."cheque_details.type IN (".ST_SUPPAYMENT.", ".ST_BANKPAYMENT.")
			AND ".TB_PREF."cheque_details.bank_trans_id =  ".TB_PREF."bank_trans.trans_no 
			AND ".TB_PREF."cheque_details.bank_id =  ".TB_PREF."bank_trans.id ";
	if ($supplier != ALL_NUMERIC)
		$sql .= " AND ".TB_PREF."bank_trans.person_id=".db_escape($supplier);
	if ($or_num != "")
		$sql .= " AND ".TB_PREF."bank_trans.reference = ".db_escape($or_num);
		
	$sql .= " ORDER BY ".TB_PREF."cheque_details.chk_date";

	$res = db_query($sql, "could not retrieve date");
	
	if(db_num_rows($res) <= 0)
		$rep->TextCol(0,6, "No report found.");
	else
	{
		$total = 0;
		while($myrow = db_fetch($res))
		{
			$rep->TextCol(0, 1, payment_person_name($myrow["person_type_id"],$myrow["person_id"], false));	//$receipt['supplier_name']);
			$rep->TextCol(1, 2, $myrow['bank']);
			$rep->TextCol(2, 3, $myrow['ref']);
			$rep->TextCol(3, 4, $myrow['chk_number']);
			$rep->TextCol(4, 5, $myrow['chk_date']);
			$rep->TextCol(5, 6, number_format2(abs($myrow['amount']),2));
			
			$rep->NewLine();
			
			$total += $myrow['amount'];		
						
		}
		
		$rep->fontSize += 1;
		$rep->Line($rep->row + 6);
		$rep->row -= 6;
		$rep->Font('bold');
		$rep->TextCol(4, 5,	_('Total '));
		$rep->Font('');
		$rep->TextCol(5, 6, number_format2(abs($total),2));
	}
	
    $rep->End();
}

?>