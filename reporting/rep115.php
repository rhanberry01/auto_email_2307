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
$page_security = 'SA_CUSTPAYMREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Aged Customer Balances
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_aged_customer_analysis();

function get_invoices($customer_id, $to)
{
	$todate = date2sql($to);
	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;
	$PastDueDays3 = 3 * $PastDueDays1;
	$PastDueDays4 = 4 * $PastDueDays1;
	$PastDueDays5 = 5 * $PastDueDays1;

	// Revomed allocated from sql
    $value = "(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
				(".TB_PREF."debtor_trans.ov_amount  * (1 - discount1) * (1 - discount2) * (1 - discount3)
						* (1 - discount4) * (1 - discount5) 
				)
			) - ".TB_PREF."debtor_trans.alloc";
	$value2 = "(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
				(".TB_PREF."debtor_trans.ov_amount  * (1 - discount1) * (1 - discount2) * (1 - discount3)
						* (1 - discount4) * (1 - discount5) 
				)
			)";
	$due = TB_PREF."debtor_trans.due_date+1";
	$sql = "SELECT ".TB_PREF."debtor_trans.type, ".TB_PREF."debtor_trans.reference, TO_DAYS('$todate') - TO_DAYS($due)+1 as overdue,
		".TB_PREF."debtor_trans.due_date,
		".TB_PREF."debtor_trans.tran_date, (TO_DAYS('$todate') - TO_DAYS($due))+1 as overdue,
		$value as Balance,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays1,$value,0) AS Overdue1,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays2,$value,0) AS Overdue2,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays3,$value,0) AS Overdue3,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays4,$value,0) AS Overdue4,
		IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $PastDueDays5,$value,0) AS Overdue5

		FROM ".TB_PREF."debtors_master,
			".TB_PREF."payment_terms,
			".TB_PREF."debtor_trans

		WHERE ".TB_PREF."debtor_trans.type <> ".ST_CUSTDELIVERY."
			AND ".TB_PREF."debtors_master.payment_terms = ".TB_PREF."payment_terms.terms_indicator
			AND ".TB_PREF."debtors_master.debtor_no = ".TB_PREF."debtor_trans.debtor_no
			AND ".TB_PREF."debtor_trans.debtor_no = $customer_id AND alloc<$value2
			AND ".TB_PREF."debtor_trans.due_date <= '$todate' AND alloc<$value2 AND ".TB_PREF."debtor_trans.type NOT IN(".ST_CUSTPAYMENT.",".ST_CUSTDEBITMEMO.",".ST_CUSTCREDITMEMO.",".ST_CUSTCREDIT.")
			AND ABS(
					(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
						(".TB_PREF."debtor_trans.ov_amount  * (1 - discount1) * (1 - discount2) * (1 - discount3)
								* (1 - discount4) * (1 - discount5) 
						)
					)
				) > 0.004
			ORDER BY ".TB_PREF."debtor_trans.tran_date,".TB_PREF."debtor_trans.id";

	return db_query($sql, "The customer details could not be retrieved");
}

function get_customer_details_16($customer_id, $to=null)
{

	if ($to == null)
		$todate = date("Y-m-d");
	else
		$todate = date2sql($to);
	$past1 = get_company_pref('past_due_days');
	$past2 = 2 * $past1;
	$past3 = 3 * $past1;
	$past4 = 4 * $past1;
	$past5 = 5 * $past1;
	// removed - debtor_trans.alloc from all summations

    $value = "IF(".TB_PREF."debtor_trans.type=11 OR ".TB_PREF."debtor_trans.type=12 OR ".TB_PREF."debtor_trans.type=2,
	-1, 1) *".
      "(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
				(".TB_PREF."debtor_trans.ov_amount 
				)
			) - ".TB_PREF."debtor_trans.alloc";
	$value2 = "IF(".TB_PREF."debtor_trans.type=11 OR ".TB_PREF."debtor_trans.type=12 OR ".TB_PREF."debtor_trans.type=2,
	-1, 1) *".
      "(".TB_PREF."debtor_trans.ov_gst +  ".TB_PREF."debtor_trans.ov_freight + ".TB_PREF."debtor_trans.ov_freight_tax + ".TB_PREF."debtor_trans.ov_discount + ".TB_PREF."debtor_trans.ewt + ".TB_PREF."debtor_trans.tracking +
				(".TB_PREF."debtor_trans.ov_amount 
				)
			)";
	$due = TB_PREF."debtor_trans.due_date+1";
    $sql = "SELECT ".TB_PREF."debtors_master.name, ".TB_PREF."debtors_master.curr_code, ".TB_PREF."payment_terms.terms,
		".TB_PREF."debtors_master.credit_limit, ".TB_PREF."credit_status.dissallow_invoices, ".TB_PREF."credit_status.reason_description,

		Sum(".$value.") AS Balance,

		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past2,$value,0)) AS Overdue2,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past3,$value,0)) AS Overdue3,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past4,$value,0)) AS Overdue4,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past5,$value,0)) AS Overdue5

		FROM ".TB_PREF."debtors_master,
			 ".TB_PREF."payment_terms,
			 ".TB_PREF."credit_status,
			 ".TB_PREF."debtor_trans

		WHERE
			 ".TB_PREF."debtors_master.payment_terms = ".TB_PREF."payment_terms.terms_indicator
			 AND ".TB_PREF."debtors_master.credit_status = ".TB_PREF."credit_status.id
			 AND ".TB_PREF."debtors_master.debtor_no = ".db_escape($customer_id)."
			 AND ".TB_PREF."debtor_trans.due_date <= '$todate' AND alloc<$value2 AND ".TB_PREF."debtor_trans.type NOT IN(".ST_CUSTPAYMENT.",".ST_CUSTDEBITMEMO.",".ST_CUSTCREDITMEMO.",".ST_CUSTCREDIT.")
			 AND ".TB_PREF."debtor_trans.type <> 13
			 AND ".TB_PREF."debtors_master.debtor_no = ".TB_PREF."debtor_trans.debtor_no

		GROUP BY
			  ".TB_PREF."debtors_master.name,
			  ".TB_PREF."payment_terms.terms,
			  ".TB_PREF."payment_terms.days_before_due,
			  ".TB_PREF."payment_terms.day_in_following_month,
			  ".TB_PREF."debtors_master.credit_limit,
			  ".TB_PREF."credit_status.dissallow_invoices,
			  ".TB_PREF."credit_status.reason_description";
    $result = db_query($sql,"The customer details could not be retrieved");

    if (db_num_rows($result) == 0)
    {

    	/*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */

    	$nil_balance = true;

    	$sql = "SELECT ".TB_PREF."debtors_master.name, ".TB_PREF."debtors_master.curr_code, ".TB_PREF."debtors_master.debtor_no,  ".TB_PREF."payment_terms.terms,
    		".TB_PREF."debtors_master.credit_limit, ".TB_PREF."credit_status.dissallow_invoices, ".TB_PREF."credit_status.reason_description
    		FROM ".TB_PREF."debtors_master,
    		     ".TB_PREF."payment_terms,
    		     ".TB_PREF."credit_status

    		WHERE
    		     ".TB_PREF."debtors_master.payment_terms = ".TB_PREF."payment_terms.terms_indicator
    		     AND ".TB_PREF."debtors_master.credit_status = ".TB_PREF."credit_status.id
    		     AND ".TB_PREF."debtors_master.debtor_no = ".db_escape($customer_id);

    	$result = db_query($sql,"The customer details could not be retrieved");

    }
    else
    {
    	$nil_balance = false;
    }

    $customer_record = db_fetch($result);

    if ($nil_balance == true)
    {
    	$customer_record["Balance"] = 0;
    	$customer_record["Due"] = 0;
    	$customer_record["Overdue1"] = 0;
    	$customer_record["Overdue2"] = 0;
    }

    return $customer_record;

}

function get_terms($id)
{
	$sql = "SELECT terms FROM ".TB_PREF."payment_terms WHERE terms_indicator=".db_escape($id);
	$result = db_query($sql,"could not get paymentterms");
	$row = db_fetch($result);
	return $row["terms"];
}

//----------------------------------------------------------------------------------------------------

function print_aged_customer_analysis()
{
    global $comp_path, $path_to_root, $systypes_array;

    $to = $_POST['PARAM_0'];
    $fromcust = $_POST['PARAM_1'];
    $currency = $_POST['PARAM_2'];
	$summaryOnly = $_POST['PARAM_3'];
	$zero = $_POST['PARAM_4'];
    $graphics = $_POST['PARAM_5'];
    $comments = $_POST['PARAM_6'];
	$destination = $_POST['PARAM_7'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	if ($graphics)
	{
		include_once($path_to_root . "/reporting/includes/class.graphic.inc");
		$pg = new graph();
	}

	if ($fromcust == ALL_NUMERIC)
		$from = _('All');
	else
		$from = get_customer_name($fromcust);
    $dec = user_price_dec();

	if ($summaryOnly == 1)
		$summary = _('Summary Only');
	else
		$summary = _('Detailed Report');
	if ($currency == ALL_TEXT)
	{
		$convert = true;
		$currency = _('Balances in Home Currency');
	}
	else
		$convert = false;

	$PastDueDays1 = get_company_pref('past_due_days');
	$PastDueDays2 = 2 * $PastDueDays1;
	$PastDueDays3 = 3 * $PastDueDays1;
	$PastDueDays4 = 4 * $PastDueDays1;
	$PastDueDays5 = 5 * $PastDueDays1;
	$nowdue = "1-" . $PastDueDays1 . " " . _('Days');
	$pastdue1 = $PastDueDays1 + 1 . "-" . $PastDueDays2 . " " . _('Days');
	$pastdue2 = $PastDueDays2 + 1 . "-" . $PastDueDays3 . " " . _('Days');
	$pastdue3 = $PastDueDays3 + 1 . "-" . $PastDueDays4 . " " . _('Days');
	$pastdue4 = $PastDueDays4 + 1 . "-" . $PastDueDays5 . " " . _('Days');
	$pastdue5 = _('Over') . " " . $PastDueDays5 . " " . _('Days');

	$cols = array(0, 100, 170, 220,	290, 340, 405, 470,	535,600,665,730);
	// $headers = array(_('Transaction'),	_('#'),	_('Date'),	_('Current'), $nowdue, $pastdue1, $pastdue2, $pastdue3, $pastdue4, $pastdue5,
		// _('Total Balance'));

	$aligns = array('left',	'left',	'right',	'right', 'right', 'right', 'right',	'right',	'right',	'right',	'right');

    $params =   array( 	0 => $comments,
    					1 => array('text' => _('End Date'), 'from' => $to, 'to' => ''),
    				    2 => array('text' => _('Customer'),	'from' => $from, 'to' => ''),
    				    3 => array('text' => _('Currency'), 'from' => $currency, 'to' => ''),
                    	4 => array('text' => _('Type'),		'from' => $summary,'to' => ''));

	// if ($convert)
		// $headers[2] = _('Currency');
    $rep = new FrontReport(_('Overdue Customer Analysis'), "OverdueCustomerAnalysis", user_pagesize(),9,'L');

    $rep->Font();
    
	if ($summaryOnly)
	{	
		$headers = array(_('Transaction'),	_('#'), _('Total Balance'),	_('Date'),	_('Overdue'), $nowdue, $pastdue1, $pastdue2, $pastdue3, $pastdue4, $pastdue5);
			
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();
	}
	else
	{
		if ($destination)	//excel
		{
			$headers = array(_('Transaction'),	_('#'), _('Total Balance'),	_('Date'),	_('Overdue'), $nowdue, $pastdue1, $pastdue2, $pastdue3, $pastdue4, $pastdue5);
			
			$rep->Info($params, $cols, $headers, $aligns);
			$rep->Header__();
		}
		else
		{
			$rep->Info($params, $cols, $headers, $aligns);
			$rep->Header_();
		}
	}

	$total = array(0,0,0,0, 0);

	$sql = "SELECT debtor_no, name, curr_code, payment_terms FROM ".TB_PREF."debtors_master";
	if ($fromcust != ALL_NUMERIC)
		$sql .= " WHERE debtor_no=".db_escape($fromcust);
	$sql .= " ORDER BY name";
	$result = db_query($sql, "The customers could not be retrieved");

	if (!$summaryOnly)
		$rep->row += 20;
	
	$grand_total_val = 0.0;
	$grand_total_val_16 = 0.0;
	while ($myrow=db_fetch($result))
	{
		$res = get_invoices($myrow['debtor_no'], $to);
		$custrec = get_customer_details2($myrow['debtor_no'], $to);
		if(!$zero && !db_num_rows($res))
			continue;
		if (!$convert && $currency != $myrow['curr_code'])
			continue;
		$rep->fontSize += 2;
		$rep->TextCol(0, 4, strtoupper($myrow['name']." - Terms : ".get_terms($myrow['payment_terms'])));
		if ($convert)
		{
			$rate = get_exchange_rate_from_home_currency($myrow['curr_code'], $to);
			//$rep->TextCol(2, 3,	$myrow['curr_code']);
		}
		else
			$rate = 1.0;
		$rep->fontSize -= 2;
	
		foreach ($custrec as $i => $value)
			$custrec[$i] *= $rate;
		// $total[0] += ($custrec["Balance"] - $custrec["Due"]);
		// $total[1] += ($custrec["Due"]-$custrec["Overdue1"]);
		// $total[2] += ($custrec["Overdue1"]-$custrec["Overdue2"]);
		// $total[3] += ($custrec["Overdue2"]-$custrec["Overdue3"]);
		// $total[4] += ($custrec["Overdue3"]-$custrec["Overdue4"]);
		// $total[5] += ($custrec["Overdue4"]-$custrec["Overdue5"]);
		// $total[6] += $custrec["Overdue5"];
		// $total[7] += $custrec["Balance"];
		// $str2 = array($custrec["Balance"] - $custrec["Due"],
			// $custrec["Due"]-$custrec["Overdue1"],
			// $custrec["Overdue1"]-$custrec["Overdue2"],
			// $custrec["Overdue2"]-$custrec["Overdue3"],
			// $custrec["Overdue3"]-$custrec["Overdue4"],
			// $custrec["Overdue4"]-$custrec["Overdue5"],
			// $custrec["Overdue5"],
			// $custrec["Balance"]);
			
		$total_16[0] += ($custrec["Balance"] - $custrec["Due"]) + ($custrec["Due"]-$custrec["Overdue1"]);
		$total_16[1] += ($custrec["Overdue1"]-$custrec["Overdue2"]);
		$total_16[2] += ($custrec["Overdue2"]-$custrec["Overdue3"]);
		$total_16[3] += ($custrec["Overdue3"]-$custrec["Overdue4"]);
		$total_16[4] += ($custrec["Overdue4"]-$custrec["Overdue5"]);
		$total_16[5] += $custrec["Overdue5"];
		//$total_16[6] += $custrec["Balance"];
		$str2_16 = array(($custrec["Balance"] - $custrec["Due"]) + ($custrec["Due"]-$custrec["Overdue1"]),
			$custrec["Overdue1"]-$custrec["Overdue2"],
			$custrec["Overdue2"]-$custrec["Overdue3"],
			$custrec["Overdue3"]-$custrec["Overdue4"],
			$custrec["Overdue4"]-$custrec["Overdue5"],
			$custrec["Overdue5"]);	//,
			//$custrec["Balance"]);
		
		$total_val = 0.0;
		if ($summaryOnly)
		{	
			$total[0] += ($custrec["Balance"] - $custrec["Due"]) + ($custrec["Due"]-$custrec["Overdue1"]);
			$total[1] += ($custrec["Overdue1"]-$custrec["Overdue2"]);
			$total[2] += ($custrec["Overdue2"]-$custrec["Overdue3"]);
			$total[3] += ($custrec["Overdue3"]-$custrec["Overdue4"]);
			$total[4] += ($custrec["Overdue4"]-$custrec["Overdue5"]);
			$total[5] += $custrec["Overdue5"];
			$total_val += $custrec["Balance"];
			//$total[6] += $custrec["Balance"];
			$str2 = array(($custrec["Balance"] - $custrec["Due"]) + ($custrec["Due"]-$custrec["Overdue1"]),
				$custrec["Overdue1"]-$custrec["Overdue2"],
				$custrec["Overdue2"]-$custrec["Overdue3"],
				$custrec["Overdue3"]-$custrec["Overdue4"],
				$custrec["Overdue4"]-$custrec["Overdue5"],
				$custrec["Overdue5"]);	//,
				//$custrec["Balance"]);
		
			$rep->AmountCol(2, 3, $total_val, $dec);	
			for ($i = 0; $i < count($str2); $i++)
				$rep->AmountCol($i + 5, $i + 6, $str2[$i], $dec);
			$rep->NewLine(1);
		}
		
		if (!$summaryOnly)
		{
			// header	
			$rep->Font('bold');			
			$rep->NewLine(1);
			$rep->TextCol(0, 1,	"Transaction");
			$rep->TextCol(1, 2,	"#");
			$rep->TextCol(2, 3, "Total Balance");
			$rep->TextCol(3, 4,	"Due Date");
			$rep->TextCol(4, 5,	"Overdue");
			$rep->TextCol(5, 6,	$nowdue);	
			$rep->TextCol(6, 7, $pastdue1);	
			$rep->TextCol(7, 8,	$pastdue2);	
			$rep->TextCol(8, 9,	$pastdue3);	
			$rep->TextCol(9, 10, $pastdue4);	
			$rep->TextCol(10, 11, $pastdue5);	
			$rep->NewLine(1, 2);
			$rep->Font('');
			// header	
			
    		if (db_num_rows($res)==0)
				continue;
    		$rep->Line($rep->row + 4);
			
			$total_val_16 = 0.0;
			while ($trans=db_fetch($res))
			{
				$rep->NewLine(1, 2);
        		$rep->TextCol(0, 1, $systypes_array[$trans['type']], -2);
				$rep->TextCol(1, 2,	$trans['reference'], -2);
				$rep->AmountCol(2, 3, $trans["Balance"], $dec);
				$rep->DateCol(3, 4, $trans['due_date'], true, -2);
				$rep->TextCol(4, 5,	$trans['overdue'], -2);
				
				if ($trans['type'] == ST_CUSTCREDIT || $trans['type'] == ST_CUSTPAYMENT || $trans['type'] == ST_BANKDEPOSIT)
				{
					$trans['Balance'] *= -1;
					$trans['Due'] *= -1;
					$trans['Overdue1'] *= -1;
					$trans['Overdue2'] *= -1;
					$trans['Overdue3'] *= -1;
					$trans['Overdue4'] *= -1;
					$trans['Overdue5'] *= -1;
				}
				foreach ($trans as $i => $value)
					$trans[$i] *= $rate;
				// $str = array($trans["Balance"] - $trans["Due"],
					// $trans["Due"]-$trans["Overdue1"],
					// $trans["Overdue1"]-$trans["Overdue2"],
					// $trans["Overdue2"]-$trans["Overdue3"],
					// $trans["Overdue3"]-$trans["Overdue4"],
					// $trans["Overdue4"]-$trans["Overdue5"],
					// $trans["Overdue5"],
					// $trans["Balance"]);
					
				$str = array(($trans["Balance"] - $trans["Due"]) + ($trans["Due"]-$trans["Overdue1"]),
					$trans["Overdue1"]-$trans["Overdue2"],
					$trans["Overdue2"]-$trans["Overdue3"],
					$trans["Overdue3"]-$trans["Overdue4"],
					$trans["Overdue4"]-$trans["Overdue5"],
					$trans["Overdue5"]);	//,
					//$trans["Balance"]);
				for ($i = 0; $i < count($str); $i++)
					$rep->AmountCol($i + 5, $i + 6, $str[$i], $dec);
					
				$total_val_16 += $trans["Balance"];
			}
			$rep->Line($rep->row - 8);
			$rep->NewLine(2);
			
			// total
			$rep->TextCol(0, 1, "Total", -2);
			$rep->AmountCol(2, 3, $total_val_16, $dec);
			for ($i = 0; $i < count($str2); $i++)
				$rep->AmountCol($i + 5, $i + 6, $str2[$i], $dec);
				
			$rep->NewLine(3);
			// total
		}
		
		$grand_total_val_16 += $total_val_16;
		$grand_total_val += $total_val;
	}
	
	$rep->NewLine();
	
	if ($summaryOnly)
	{
		$rep->NewLine();
    	$rep->Line($rep->row  + 4);
    	$rep->NewLine();
	}
	
	$rep->fontSize += 2;
	$rep->TextCol(0, 3, _('Grand Total'));
	$rep->fontSize -= 2;
	
	if ($summaryOnly)
	{	
		$rep->AmountCol(2, 3, $grand_total_val, $dec);
		for ($i = 0; $i < count($total); $i++)
		{
			$rep->AmountCol($i + 5, $i + 6, $total[$i], $dec);
			if ($graphics && $i < count($total) - 1)
			{
				$pg->y[$i] = abs($total[$i]);
			}
		}
	}
	else
	{
		$rep->AmountCol(2, 3, $grand_total_val_16, $dec);
		for ($i = 0; $i < count($total_16); $i++)
		{
			$rep->AmountCol($i + 5, $i + 6, $total_16[$i], $dec);
			if ($graphics && $i < count($total_16) - 1)
			{
				$pg->y[$i] = abs($total_16[$i]);
			}
		}
	}
   	$rep->Line($rep->row - 8);
   	if ($graphics)
   	{
   		global $decseps, $graph_skin;
		$pg->x = array(_('Current'), $nowdue, $pastdue1, $pastdue2);
		$pg->title     = $rep->title;
		$pg->axis_x    = _("Days");
		$pg->axis_y    = _("Amount");
		$pg->graphic_1 = $to;
		$pg->type      = $graphics;
		$pg->skin      = $graph_skin;
		$pg->built_in  = false;
		$pg->fontfile  = $path_to_root . "/reporting/fonts/Vera.ttf";
		$pg->latin_notation = ($decseps[$_SESSION["wa_current_user"]->prefs->dec_sep()] != ".");
		$filename = $comp_path .'/'. user_company(). "/images/test.png";
		$pg->display($filename, true);
		$w = $pg->width / 1.5;
		$h = $pg->height / 1.5;
		$x = ($rep->pageWidth - $w) / 2;
		$rep->NewLine(2);
		if ($rep->row - $h < $rep->bottomMargin)
		{
			if ($summaryOnly)
				$rep->Header();
			else
				$rep->Header_();
		}
		$rep->AddImage($filename, $x, $rep->row - $h, $w, $h);
	}
	$rep->NewLine();
    $rep->End();
}

?>