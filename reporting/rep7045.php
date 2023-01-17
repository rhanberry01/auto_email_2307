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
	//AND a.type=53
function get_gl_transactions_704($from_date, $to_date, $account, $order_by_person=false)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT a.*, s.cv_id,c.supp_name,s.supp_reference FROM 0_gl_trans a
	JOIN 0_chart_master b ON a.account = b.account_code
	LEFT OUTER JOIN  0_suppliers c ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
	LEFT JOIN 0_supp_trans s
	ON (a.type=s.type and a.type_no=s.trans_no) 
	WHERE a.tran_date >= '$from'
	AND a.tran_date <= '$to'
	AND a.account = '$account'";
	if ($order_by_person == false)
		$sql .= " ORDER BY a.tran_date, a.counter";
	else
		$sql .= " ORDER BY c.supp_name";
	//echo $sql;die;
	return db_query($sql, "The transactions for could not be retrieved");
}

function print_GL_transactions()
{
	global $path_to_root, $systypes_array, $systypes_array_short;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$fromacc = $_POST['PARAM_2'];
	$t_nt = $_POST['PARAM_3'];
	$group_by_person = $_POST['PARAM_4'];
	$destination = $_POST['PARAM_5'];
	
	$sql = "SELECT account_name FROM ".TB_PREF."chart_master
			WHERE account_code IN (".implode(',',$fromacc).")";
			
			
	// var_dump($fromacc);die;
	// if ($dim == 2)
	// {
		// $dimension = $_POST['PARAM_3'];
		// $dimension2 = $_POST['PARAM_4'];
		// $comments = $_POST['PARAM_5'];
		// $destination = $_POST['PARAM_6'];
	// }
	// else if ($dim == 1)
	// {
		// $dimension = $_POST['PARAM_3'];
		// $comments = $_POST['PARAM_4'];
		// $destination = $_POST['PARAM_5'];
	// }
	// else
	// {
		// $comments = $_POST['PARAM_3'];
		// $destination = $_POST['PARAM_4'];
	// }
	
	$acct_name = '';
	foreach($fromacc as $acct_code)
		$acct_name .= get_gl_account_name($acct_code).' / ';
	
	$acct_name = substr($acct_name,0,-2);

	$comments = '';
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$rep = new FrontReport(_('GL Account Transactions'), "GLAccountTransactions", user_pagesize(),9 ,'L');
	$dec = user_price_dec();

  //$cols = array(0, 80, 100, 150, 210, 280, 340, 400, 450, 510, 570);
	$cols = array(0, 35, 45, 100, 175, 230, 360, 440, 525, 595, 665, 735);
	//------------0--1---2---3----4----5----6----7----8----9----10-------
	//-----------------------dim1-dim2-----------------------------------
	//-----------------------dim1----------------------------------------
	//-------------------------------------------------------------------
	$aligns = array('left', 'left', 'left',	'left',	'left', 'left',	'left',	'left',	'right', 'right', 'right');

	// if ($dim == 2)
		// $headers = array(_('Type'),	'', _('#'),	_('Date'), _('Dimension')." 1", _('Dimension')." 2", 'TIN',
			// _('Person/Item'), _('Debit'),	_('Credit'), _('Balance'));
	// elseif ($dim == 1)
		// $headers = array(_('Type'),	'', _('#'),	_('Date'), _('Dimension'), "", 'TIN', _('Person/Item'),
			// _('Debit'),	_('Credit'), _('Balance'));
	// else
		$headers = array(_('Type'),	'', _('#'),	_('Date'), "Particulars", "Reference", 'TIN', _('Person/Item'),
			_('Debit'),	_('Credit'), _('Balance'));

	// if ($dim == 2)
	// {
    	// $params =   array( 	0 => $comments,
    				    // 1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    // 2 => array('text' => _('Accounts'),'from' => 'accounts'),
                    	// 3 => array('text' => _('Dimension')." 1", 'from' => get_dimension_string($dimension),
                            // 'to' => ''),
                    	// 4 => array('text' => _('Dimension')." 2", 'from' => get_dimension_string($dimension2),
                            // 'to' => ''));
    // }
    // else if ($dim == 1)
    // {
    	// $params =   array( 	0 => $comments,
    				    // 1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    // 2 => array('text' => _('Accounts'),'from' => $fromacc),
                    	// 3 => array('text' => _('Dimension'), 'from' => get_dimension_string($dimension),
                            // 'to' => ''));
    // }
    // else
    // {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Accounts'),'from' => $acct_name));
    // }

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();

	$accounts = get_gl_accounts_array($fromacc);
	$count = db_num_rows($accounts);
	// die($count);
	if($count==0)
	{
		display_error("No reports can be generated with the given parameters.");
		die();
	}


	
	
	$grand_total = array();
	while ($account=db_fetch($accounts))
	{

		
		$total_debit = $total_credit = $prev_balance = 0;
		if (is_account_balancesheet($account["account_code"]))
			$begin = "";
		else
		{
			$begin = begin_fiscalyear();
			if (date1_greater_date2($begin, $from))
				$begin = $from;
			$begin = add_days($begin, -1);
		}
		
		// $prev_balance = get_gl_balance_from_to($begin, $from, $account["account_code"], $dimension, $dimension2);

		$trans = get_gl_transactions_704($from, $to,  $account['account_code'], $group_by_person);
		$rows = db_num_rows($trans);
		
		if($rows==0){
			// display_error("No reports can be generated with the given parameters.");
			// die();
			continue;
		}
		
		if ($prev_balance == 0.0 && $rows == 0)
			continue;
		$rep->Font('bold');
		$rep->TextCol(0, 6,	$account['account_code'] . " " . $account['account_name'], -2);
		
		// $rep->TextCol(6, 8, _('Opening Balance'));
		// if ($prev_balance > 0.0)
			// $rep->AmountCol(8, 9, abs($prev_balance), $dec);
		// else
			// $rep->AmountCol(9, 10, abs($prev_balance), $dec);
			
		$total_debit = $total_credit = $prev_balance = 0;
		if (is_account_balancesheet($account["account_code"]))
			$begin = "";
		else
		{
			$begin = begin_fiscalyear();
			if (date1_greater_date2($begin, $from))
				$begin = $from;
			$begin = add_days($begin, -1);
		}
			
		$rep->Font();
		$total = $prev_balance;
		
		
		$rep->NewLine(2);
		if ($rows > 0)
		{
					$bfw = get_gl_balance_from_to($begin, $from ,$account['account_code'] , $_POST['Dimension'], $_POST['Dimension2']);
					$rep->TextCol(0, 2,'Beginning',-2);
					$rep->TextCol(3, 3, $from);
					$rep->TextCol(4, 4, '');
					$rep->TextCol(5, 5,'');
					$rep->TextCol(6, 6, '');
					$rep->TextCol(7, 7, '');

				if ($bfw> 0.0)
				{
					$rep->AmountCol(8, 9, abs($bfw), $dec);
					$total_debit += abs($bfw);
				}
				else
				{
					$rep->AmountCol(9, 10, abs($bfw), $dec);
					$total_credit += abs($bfw);
				}
					
					$total=$bfw;
					
					// $rep->AmountCol(8, 9, $bfw);
					// $rep->AmountCol(9, 10, 0);
					$rep->TextCol(10, 11, '');

					$rep->Font('');
					$rep->NewLine(2);
					
			while ($myrow=db_fetch($trans))
			{
				$reference = get_reference($myrow["type"], $myrow["type_no"]);
				
				if ($t_nt == 1 AND(strpos($reference,'NT') !== false))
					continue;
				else if ($t_nt == 0 AND(strpos($reference,'NT') === false))
					continue;
				
				$total += $myrow['amount'];
				
				$voided_str = '';
				
				if ($myrow['amount'] == 0)
				{	
					$voided_str = 'VOIDED - ';
					
					continue; // remove to show voided
				}
				
				
					
				$rep->TextCol(0, 2, $systypes_array_short[$myrow["type"]], -2);
				$rep->TextCol(2, 3, $reference != '' ? $reference : $myrow['type_no']);
				// $rep->TextCol(2, 3,	$myrow['type_no'], -2);
				$rep->DateCol(3, 4,	$myrow["tran_date"], true);
				// if ($dim >= 1)
					// $rep->TextCol(4, 5,	get_dimension_string($myrow['dimension_id']));
				// if ($dim > 1)
				$cv_num=get_cv_no($myrow['cv_id']);
				$cv_	=get_cv_header($myrow['cv_id']);
			
			if($cv_num=='' OR $cv_['cv_date']>'2016-12-31'){
			$rep->TextCol(4, 5,'');
			}
			else{
			$rep->TextCol(4, 5,	'CV#:'.$cv_num);
			}
				
				$rep->TextCol(5, 6,	$myrow['supp_reference']);
				$rep->TextCol(6, 7,	get_supplier_tin($myrow["person_id"]));
				
				$ppp = $myrow['supp_name'];
				if ($ppp == '')
					$ppp = payment_person_name($myrow["person_type_id"],$myrow["person_id"], false);
				$rep->TextCol(7, 8,	$ppp);
				if ($myrow['amount'] > 0.0)
				{
					$rep->AmountCol(8, 9, abs($myrow['amount']), $dec);
					$total_debit += abs($myrow['amount']);
				}
				else
				{
					$rep->AmountCol(9, 10, abs($myrow['amount']), $dec);
					$total_credit += abs($myrow['amount']);
				}
				$rep->TextCol(10, 11, number_format2($total, $dec));
				
				$rep->NewLine();
				if ($rep->row < $rep->bottomMargin + $rep->lineHeight)
				{
					$rep->Line($rep->row - 2);
					$rep->Header();
				}
			}
			$rep->NewLine();
		}
		$rep->Font('bold');
		// $rep->TextCol(6, 8,	_("Ending Balance"));
		// if ($total > 0.0)
			// $rep->AmountCol(8, 9, abs($total), $dec);
		// else
			// $rep->AmountCol(9, 10, abs($total), $dec);
		
		if (abs($total_debit) != 0)
			$rep->AmountCol(8, 9, abs($total_debit), $dec);
		if (abs($total_credit) != 0)
			$rep->AmountCol(9, 10, abs($total_credit), $dec);
		if (abs($total) != 0)
			$rep->AmountCol(10, 11, ($total), $dec);
		
		$rep->Font();
		$rep->Line($rep->row - $rep->lineHeight + 4);
		$rep->NewLine(2, 1);
		
		$grand_total[0] += abs($total_debit);
		$grand_total[1] += abs($total_credit);
		$grand_total[2] += ($total);
	}
	
	$rep->Font('bold');
	$rep->TextCol(6, 7,	'TOTAL : ');
	
	if ($grand_total[0] != 0)
		$rep->AmountCol(8, 9, $grand_total[0], $dec);
	if ($grand_total[1] != 0)
		$rep->AmountCol(9, 10, $grand_total[1], $dec);
	if ($grand_total[2] != 0)
		$rep->AmountCol(10, 11, $grand_total[2], $dec);
	$rep->Font('');
	
	if ($destination)
		$rep->NewLine();
	
	$rep->End();
}

?>