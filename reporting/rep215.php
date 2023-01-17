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
$page_security = 'SA_SALESANALYTIC';
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
    $customer = $_POST['PARAM_2'];
    $or_num = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($customer == ALL_NUMERIC)
		$cust = _('All');
	else
		$cust = get_customer_name($customer);
    $dec = user_price_dec();

	$cols = array(0, 200, 280, 340, 400, 460, 530);

	$headers = array(_('Customer'), _('Bank'), _('OR #'), _('Check #'), _('Check Date'), _('Amount'));

	$aligns = array('left',	'left',	'left',	'left', 'left', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''));

    $rep = new FrontReport(_('PDC Report for Collection'), "PDCCollections", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$sql = "SELECT distinct chk_date
			FROM ".TB_PREF."cheque_details, ".TB_PREF."debtor_trans, ".TB_PREF."bank_trans
			WHERE ".TB_PREF."cheque_details.chk_date >= '".date2sql($from)."'
			AND ".TB_PREF."cheque_details.chk_date <= '".date2sql($to)."'
			AND ".TB_PREF."cheque_details.type = 12 
			AND ".TB_PREF."debtor_trans.type = 12 
			AND ".TB_PREF."debtor_trans.trans_no =  ".TB_PREF."bank_trans.trans_no
			AND ".TB_PREF."cheque_details.bank_trans_id =  ".TB_PREF."bank_trans.trans_no ";
	if ($customer != ALL_NUMERIC)
		$sql .= " AND ".TB_PREF."debtor_trans.debtor_no=".db_escape($customer);
	if ($or_num != "")
		$sql .= " AND ".TB_PREF."debtor_trans.reference = ".db_escape($or_num);
	
	$sql .= " ORDER BY ".TB_PREF."cheque_details.chk_date";
	
	$res = db_query($sql, "could not retrieve date");
	
	if(db_num_rows($res) <= 0)
		$rep->TextCol(0,6, "No report found.");
	else
	{
		$grandTotal_amount = 0;
		while($row = db_fetch($res))
		{
			$total = 0;
			
			//$result = getTransactions2($row['chk_date']);
			
			$sql = "SELECT
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
				".TB_PREF."debtor_trans.reference,
				(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
					(".TB_PREF."debtor_trans.ov_amount  * (1 - discount1) * (1 - discount2) * (1 - discount3)
							* (1 - discount4) * (1 - discount5) 
					)
				) as TotalAmount
				FROM
				".TB_PREF."cheque_details ,
				".TB_PREF."debtor_trans ,
				".TB_PREF."debtors_master ,
				".TB_PREF."bank_trans
				WHERE ".TB_PREF."bank_trans.type = 12 
				AND ".TB_PREF."debtor_trans.type = 12 
				AND ".TB_PREF."cheque_details.bank_trans_id =  ".TB_PREF."bank_trans.trans_no 
				AND ".TB_PREF."cheque_details.type =  ".TB_PREF."bank_trans.type 
				AND ".TB_PREF."debtor_trans.reference =  ".TB_PREF."bank_trans.ref 
				AND ".TB_PREF."debtor_trans.debtor_no =  ".TB_PREF."debtors_master.debtor_no
				AND ".TB_PREF."debtor_trans.trans_no =  ".TB_PREF."bank_trans.trans_no
				AND ".TB_PREF."cheque_details.chk_date >= '".$row['chk_date']."'
				AND ".TB_PREF."cheque_details.chk_date <= '".$row['chk_date']."' ";
			if ($customer != ALL_NUMERIC)
				$sql .= " AND ".TB_PREF."debtor_trans.debtor_no=".db_escape($customer);
			if ($or_num != "")
				$sql .= " AND ".TB_PREF."debtor_trans.reference = ".db_escape($or_num);
		
			$sql .= " ORDER BY ".TB_PREF."debtors_master.name, ".TB_PREF."cheque_details.chk_date";
				
			$result =  db_query($sql, "The data could not be retrieved");
			
			$voided = 0;
			
			//$result = getTransactions($from, $to);
			while ($myrow = db_fetch($result))
			{
			
				// voided not included
				$cza = "SELECT id
						FROM ".TB_PREF."voided
						WHERE ".TB_PREF."voided.type = 12
						AND ".TB_PREF."voided.id = ".$myrow['trans_no'];
				$czarina = db_query($cza,"could not retrieve");
				$voided = db_num_rows($czarina);

				if($voided == 0)
				{
					$receipt = get_customer_trans($myrow['trans_no'], ST_CUSTPAYMENT);
				
					$rep->TextCol(0, 1, $receipt['DebtorName']);
					$rep->TextCol(1, 2, $myrow['bank']);
					$rep->TextCol(2, 3, $myrow['reference']);
					$rep->TextCol(3, 4, $myrow['chk_number']);
					$rep->TextCol(4, 5, $myrow['chk_date']);
					$rep->TextCol(5, 6, number_format2(abs($myrow['TotalAmount']),2));
					
					$rep->NewLine();
					
					$total += $myrow['TotalAmount'];		
				}
				
			}
			
			if($voided == 0)
			{
				$rep->Font('bold');
				$rep->TextCol(4, 5,	_('Sub Total'));
				$rep->Font('');
				$rep->TextCol(5, 6, number_format2(abs($total),2));
				$rep->NewLine(2);
			}
			
			$grandTotal_amount += $total;
			
		}
		
		$rep->fontSize += 1;
		$rep->Line($rep->row + 6);
		$rep->row -= 6;
		$rep->Font('bold');
		$rep->TextCol(4, 5,	_('Grand Total '));
		$rep->Font('');
		$rep->TextCol(5, 6, number_format2(abs($grandTotal_amount),2));
	}
	
    $rep->End();
}

?>