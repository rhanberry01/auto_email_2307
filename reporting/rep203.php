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
$page_security = 'SA_SUPPPAYMREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Payment Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_payment_report();

function getTransactions($supplier, $date)
{
	$date = date2sql($date);
	$dec = user_price_dec();

	$sql = "SELECT ".TB_PREF."supp_trans.supp_reference,
			".TB_PREF."supp_trans.tran_date,
			".TB_PREF."supp_trans.reference,
			".TB_PREF."supp_trans.due_date,
			".TB_PREF."supp_trans.trans_no,
			".TB_PREF."supp_trans.type,
			".TB_PREF."supp_trans.rate,
			(ABS(".TB_PREF."supp_trans.ov_amount) + ABS(".TB_PREF."supp_trans.ov_gst)+ ABS(".TB_PREF."supp_trans.ewt) - ".TB_PREF."supp_trans.alloc) AS Balance,
			(ABS(".TB_PREF."supp_trans.ov_amount) + ABS(".TB_PREF."supp_trans.ov_gst)+ ABS(".TB_PREF."supp_trans.ewt)  ) AS TranTotal
		FROM ".TB_PREF."supp_trans
		WHERE ".TB_PREF."supp_trans.supplier_id = '" . $supplier . "'
		AND type != 20
		AND ".TB_PREF."supp_trans.tran_date <='" . $date . "'
		ORDER BY ".TB_PREF."supp_trans.type,
			".TB_PREF."supp_trans.trans_no";

    return db_query($sql, "No transactions were returned");
}

//----------------------------------------------------------------------------------------------------

function print_payment_report()
{
    global $path_to_root, $systypes_array;

    $to = $_POST['PARAM_0'];
    $fromsupp = $_POST['PARAM_1'];
    $currency = $_POST['PARAM_2'];
	$zero = $_POST['PARAM_3'];
    $comments = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($fromsupp == ALL_NUMERIC)
		$from = _('All');
	else
		$from = get_supplier_name($fromsupp);

    $dec = user_price_dec();

	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	$cols = array(0, 100, 130, 190,	250, 320, 385, 450,	515);

	// $headers = array(_('Trans Type'), _('#'), _('Due Date'), '', '',
		// '', _('Total'), _('Balance'));

	$aligns = array('left',	'left',	'left',	'left',	'right', 'right', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('End Date'), 'from' => $to, 'to' => ''),
    				    2 => array('text' => _('Supplier'), 'from' => $from, 'to' => ''),
    				    3 => array(  'text' => _('Currency'),'from' => $currency, 'to' => ''));

    $rep = new FrontReport(_('Payment Report'), "PaymentReport", user_pagesize());

    $rep->Font();
    // $rep->Info($params, $cols, $headers, $aligns);
    // $rep->Header();
	
	if ($destination)	//excel
	{
		$headers = array(_('Trans Type'), _('#'), _('Date'), '', '', 'INV #', _('Amount'), _(''));
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header__();
	}
	else
	{
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header_();
	}

	$total = array();
	$grandtotal = array(0,0);

	$sql = "SELECT supplier_id, supp_name AS name, curr_code, ".TB_PREF."payment_terms.terms FROM ".TB_PREF."suppliers, ".TB_PREF."payment_terms
		WHERE ";
	if ($fromsupp != ALL_NUMERIC)
		$sql .= "supplier_id=".db_escape($fromsupp)." AND ";
	$sql .= "".TB_PREF."suppliers.payment_terms = ".TB_PREF."payment_terms.terms_indicator
		ORDER BY supp_name";
	$result = db_query($sql, "The customers could not be retrieved");

	$rep->row += 20;
	
	while ($myrow=db_fetch($result))
	{
		$res = getTransactions($myrow['supplier_id'], $to);
		if(!$zero && !db_num_rows($res))
			continue;
		if (!$convert && $currency != $myrow['curr_code'])
			continue;
		$rep->fontSize += 2;
		$rep->TextCol(0, 4, strtoupper($myrow['name'] . " - " . $myrow['terms']));
		if ($convert)
			$rep->TextCol(5, 7,	$myrow['curr_code']);
		$rep->fontSize -= 2;
		$rep->NewLine(1, 2);
		
		if (db_num_rows($res)==0)
			continue;
		$total[0] = $total[1] = 0.0;
		
		
		// header	
		$rep->Font('bold');	
		$rep->TextCol(0, 1,	"Trans Type");
		$rep->TextCol(1, 2,	"#");
		$rep->TextCol(2, 3,	"Date");
		$rep->TextCol(3, 4, "");
		$rep->TextCol(4, 5,	"");
		$rep->TextCol(5, 6,	"INV #");	
		$rep->TextCol(6, 7, "Amount");	
		$rep->TextCol(7, 8,	"");	
		$rep->NewLine(1, 2);
		$rep->Font('');
		// header	
		
		$rep->Line($rep->row + 4);
		
		while ($trans=db_fetch($res))
		{
			if ($convert)
				$rate = $trans['rate'];
			else
				$rate = 1.0;
			$rep->NewLine(1, 2);
			$rep->TextCol(0, 1, $systypes_array[$trans['type']]);
			$rep->TextCol(1, 2,	$trans['reference']);
			$rep->DateCol(2, 3,	$trans['tran_date'], true);
			
			/////////////////////////////////////////////////////////////////////
			
			$sql001 = "SELECT * FROM ".TB_PREF."supp_allocations
				WHERE (trans_type_from=".$trans['type']." AND trans_no_from=".$trans['trans_no'].")";
			$result001 = db_query($sql001, "sql001");

			$inv_no = "";
			$x=0;
			while ($row001 = db_fetch($result001))
			{	
				$x++;
				
				if($x == 1)
					$inv_no .= get_reference($row001['trans_type_to'], $row001['trans_no_to']);
				else
					$inv_no .= ",".get_reference($row001['trans_type_to'], $row001['trans_no_to']);
			}
			
			/////////////////////////////////////////////////////////////////////			
			
			$rep->TextCol(5, 6,	$inv_no);	
			
			if ($trans['type'] != ST_SUPPINVOICE&&$trans['type'] != ST_SUPPDEBITMEMO)
			{
				$trans['TranTotal'] = -$trans['TranTotal'];
				$trans['Balance'] = -$trans['Balance'];
			}
			$item[0] = $trans['TranTotal'] * $rate;
			//$rep->AmountCol(6, 7, $item[0], $dec);
			$item[1] = $trans['Balance'] * $rate;
			$rep->AmountCol(6, 7, abs($item[0]), $dec);
			
			for ($i = 0; $i < 1; $i++)
			{
				$total[$i] += $item[$i];
				$grandtotal[$i] += $item[$i];
			}
		}
		$rep->Line($rep->row - 8);
		$rep->NewLine(2);
		$rep->TextCol(0, 3,	_('Total'));
		for ($i = 0; $i < 1; $i++)
		{
			$rep->AmountCol($i + 6, $i + 7, abs($total[$i]), $dec);
			$total[$i] = 0.0;
		}
    	//$rep->Line($rep->row  - 4);
    	$rep->NewLine(2);
	}
	$rep->NewLine();
	$rep->fontSize += 2;
	$rep->TextCol(0, 3,	_('Grand Total'));
	$rep->fontSize -= 2;
	for ($i = 0; $i < 1; $i++){
		$rep->AmountCol($i + 6, $i + 7, abs($grandtotal[$i]), $dec);
	
		//$rows = db_num_rows($res);
	
		// die($count);
		if($grandtotal[$i]==0)
		{
			display_error("No reports can be generated with the given parameters.");
			die();
		}
	}
	$rep->Line($rep->row  - 4);
	$rep->NewLine();
    $rep->End();
}

?>