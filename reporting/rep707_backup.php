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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt, Chaitanya for the recursive version 2009-02-05.
// date_:	2005-05-19
// Title:	Profit and Loss Statement
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

function display_type($a, $type, $typename, $from, $to, $begin, $end, $compare, $convert, &$dec, 
&$pdec, &$rep, $dimension, $dimension2, &$pg, $graphics,$display_zero)
{
	$code_per_balance = 0;
	$code_acc_balance = 0;
	$per_balance_total = 0;
	$acc_balance_total = 0;
	unset($totals_arr);
	$totals_arr = array();

	$printtitle = 0; //Flag for printing type name	
	
	//Get Accounts directly under this group/type
	$result = get_gl_accounts(null, null, $type);	
	while ($account=db_fetch($result))
	{
		$per_balance = get_gl_trans_from_to($from, $to, $account["account_code"], $dimension, $dimension2);

		if ($compare == 2)
			$acc_balance = get_budget_trans_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
		else
			$acc_balance = get_gl_trans_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
		if (!$per_balance AND !$display_zero)
			continue;
		
		//Print Type Title if it has atleast one non-zero account	
		if (!$printtitle)
		{
			$printtitle = 1;
			$rep->row -= 4;
			$rep->TextCol(0, 5, '   '.$typename);
			$rep->row -= 4;
			$rep->Line($rep->row);
			$rep->NewLine();		
		}			

		$rep->TextCol(0, 1,	'   '.$account['account_code']);
		$rep->TextCol(1, 2,	$account['account_name']);

		$rep->AmountCol(2, 3, $per_balance * $convert, $dec);
		
		if ($a != 0) // display % to revenue
		{
			$rep->TextCol(3, 4, compute_percent($per_balance * $convert,$a));	
		}
		// $rep->AmountCol(3, 4, $acc_balance * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($per_balance, $acc_balance), $pdec);

		$rep->NewLine();

		if ($rep->row < $rep->bottomMargin + 3 * $rep->lineHeight)
		{
			$rep->Line($rep->row - 2);
			$rep->Header();
		}

		$code_per_balance += $per_balance;
		$code_acc_balance += $acc_balance;
	}
		
	//Get Account groups/types under this group/type
	$result = get_account_types(false, false, $type);
	while ($accounttype=db_fetch($result))
	{
		//Print Type Title if has sub types and not previously printed
		// if (!$printtitle)
		// {
			// $printtitle = 1;
			// $rep->row -= 4;
			// $rep->TextCol(0, 5, $typename);
			// $rep->row -= 4;
			// $rep->Line($rep->row);
			// $rep->NewLine();		
		// }

		$totals_arr = display_type($a,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
			$pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
		$per_balance_total += $totals_arr[0];
		$acc_balance_total += $totals_arr[1];
	}

	//Display Type Summary if total is != 0 OR head is printed (Needed in case of unused hierarchical COA) 
	// if (($code_per_balance + $per_balance_total + $code_acc_balance + $acc_balance_total) != 0 || $printtitle)
	// {
		// $rep->row += 6;
		// $rep->Line($rep->row);
		// $rep->NewLine();
		// $rep->TextCol(0, 2,	_('Total') . " " . $typename);
		// $rep->AmountCol(2, 3, ($code_per_balance + $per_balance_total) * $convert, $dec);
		// $rep->AmountCol(3, 4, ($code_acc_balance + $acc_balance_total) * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve(($code_per_balance + $per_balance_total), ($code_acc_balance + $acc_balance_total)), $pdec);		
		// if ($graphics)
		// {
			// $pg->x[] = $typename;
			// $pg->y[] = abs($code_per_balance + $per_balance_total);
			// $pg->z[] = abs($code_acc_balance + $acc_balance_total);
		// }
		// $rep->NewLine();
	// }
	
	$totals_arr[0] = $code_per_balance + $per_balance_total;
	$totals_arr[1] = $code_acc_balance + $acc_balance_total;
	return $totals_arr;
}

function compute_percent($amount,$a)
{
	$m = 1;
	
	if ($amount < 0)
		$m = -1;
	$amount = abs($amount);
	$prc = round(($amount/$a) * 100,2);
	
	return  $prc > 0 ? $prc*$m ." %" : '';
}

print_profit_and_loss_statement();

//----------------------------------------------------------------------------------------------------

function Achieve($d1, $d2)
{
	if ($d1 == 0 && $d2 == 0)
		return 0;
	elseif ($d2 == 0)
		return 999;
	$ret = ($d1 / $d2 * 100.0);
	if ($ret > 999)
		$ret = 999;
	return $ret;
}

//----------------------------------------------------------------------------------------------------

function print_profit_and_loss_statement()
{
	global $comp_path, $path_to_root, $db_connections;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	// $compare = $_POST['PARAM_2'];
	// if ($dim == 2)
	// {
		// $dimension = $_POST['PARAM_3'];
		// $dimension2 = $_POST['PARAM_4'];
		// $decimals = $_POST['PARAM_5'];
		// $graphics = $_POST['PARAM_6'];
		// $comments = $_POST['PARAM_7'];
		// $destination = $_POST['PARAM_8'];
	// }
	// else if ($dim == 1)
	// {
		// $dimension = $_POST['PARAM_3'];
		// $decimals = $_POST['PARAM_4'];
		// $graphics = $_POST['PARAM_5'];
		// $comments = $_POST['PARAM_6'];
		// $destination = $_POST['PARAM_7'];
	// }
	// else
	// {
		$display_zero = $_POST['PARAM_2'];
		// $graphics = $_POST['PARAM_4'];
		$comments = $_POST['PARAM_3'];
		$destination = $_POST['PARAM_4'];
		
		$decimals = true;
	// }
	
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	if ($graphics)
	{
		include_once($path_to_root . "/reporting/includes/class.graphic.inc");
		$pg = new graph();
	}
	if (!$decimals)
		$dec = 0;
	else
		$dec = user_price_dec();
	$pdec = user_percent_dec();

	$cols = array(0, 80, 390, 470, 540);
	//------------0--1---2----3----4----5--

	$headers = array(_('Account'), _('Account Name'), _('Period'), 'Percentage', '');

	$aligns = array('left',	'left',	'right', 'right', 'right');

    if ($dim == 2)
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
                    	2 => array('text' => _('Dimension')." 1",
                            'from' => get_dimension_string($dimension), 'to' => ''),
                    	3 => array('text' => _('Dimension')." 2",
                            'from' => get_dimension_string($dimension2), 'to' => ''));
    }
    else if ($dim == 1)
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
                    	2 => array('text' => _('Dimension'),
                            'from' => get_dimension_string($dimension), 'to' => ''));
    }
    else
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to));
    }


	if ($compare == 0 || $compare == 2)
	{
		$end = $to;
		if ($compare == 2)
		{
			$begin = $from;
			$headers[3] = _('Budget');
		}
		else
			$begin = begin_fiscalyear();
	}
	elseif ($compare == 1)
	{
		$begin = add_months($from, -12);
		$end = add_months($to, -12);
		$headers[3] = _('Period Y-1');
	}

	
	$branch_name = strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"]);
	
	$rep = new FrontReport("Profit and Loss Statement - $branch_name $from - $to", "Profit_and_Loss - $branch_name $from - $to", user_pagesize());

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();

	$classper = 0.0;
	$classacc = 0.0;
	$salesper = 0.0;
	$salesacc = 0.0;	

	//================ get REVENUES total
	$r_tots = 0;
	$result = get_gl_accounts(null, null, 6);	
	while ($account=db_fetch($result))
	{
		$per_balance = get_gl_trans_from_to($from, $to, $account["account_code"], $dimension, $dimension2);

		if ($compare == 2)
			$acc_balance = get_budget_trans_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
		else
			$acc_balance = get_gl_trans_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
		
		if (!$per_balance && !$acc_balance)
			continue;

		$r_tots += $per_balance;
		// $code_acc_balance += $acc_balance;
	}
	$result = get_gl_accounts(null, null, 61);	
	while ($account=db_fetch($result))
	{
		$per_balance = get_gl_trans_from_to($from, $to, $account["account_code"], $dimension, $dimension2);

		if ($compare == 2)
			$acc_balance = get_budget_trans_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
		else
			$acc_balance = get_gl_trans_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
		
		if (!$per_balance && !$acc_balance)
			continue;

		$r_tots += $per_balance;
		// $code_acc_balance += $acc_balance;
	}
	
	$r_tots = -$r_tots;
	
	
	//================
	
	//================ REVENUES
	$class = get_account_class(4);
	// while ($class = db_fetch($classresult))
	// {
		$class_per_total = 0;
		$class_acc_total = 0;
		$convert = get_class_type_convert($class["ctype"]); 		
		
		//Print Class Name	
		$rep->Font('bold');
		$rep->TextCol(0, 5, 'A.   '.$class["class_name"]);
		$rep->Font();
		$rep->NewLine();
		
		//Get Account groups/types under this group/type with no parents
		$accounttype = get_account_type(6);
		// while ($accounttype=db_fetch($typeresult))
		// {
			$classtotal = display_type($r_tots,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
				$pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
			
			
			$class_per_total += $classtotal[0];
			$class_acc_total += $classtotal[1];			
		// }
		
		//Print Gross Sales
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2,	'   Total Gross Sales');
		$rep->AmountCol(2, 3, $class_per_total * $convert, $dec);
		$rep->TextCol(3, 4, compute_percent($class_per_total * $convert,$r_tots));
		$rep->NewLine(2);
		$rep->Font('');
		//--------------------------------------------------------
		
		// Suki Points
		if ($accounttype = get_account_type(61))
		{
			$classtotal = display_type($r_tots,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
				$pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
			
			
			$class_per_total += $classtotal[0];
			$class_acc_total += $classtotal[1];
		}
		//---------------------------------------------------
		
		//Print Class Summary	
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2,	_('Total') . " " . $class["class_name"]);
		$rep->AmountCol(2, 3, $class_per_total * $convert, $dec);
			$a = $class_per_total * $convert;
		$rep->TextCol(3, 4, compute_percent($a,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine(2);	

		$salesper += $class_per_total;
		$salesacc += $class_acc_total;
	// }
	
	//============ COST OF GOODS SOLD
	$class = get_account_class(5);
	
	$class_per_total = 0;
	$class_acc_total = 0;
		
	$convert = get_class_type_convert($class["ctype"]); 		
	
	$accounttype = get_account_type(7);	
	//Print Class Name	
	$rep->Font('bold');
	$rep->TextCol(0, 5, 'B.   '.$accounttype["name"]);
	$rep->Font();
	$rep->NewLine();

	// // while ($accounttype=db_fetch($typeresult))
		// // {
			// $rep->Font('bold');
			// $rep->TextCol(0, 5, '   Purchases');
			// $rep->Font();
			// $rep->NewLine();
	
			// $classtotal = display_type($a,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
				// $pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
			
			// $class_per_total += $classtotal[0];
			// $class_acc_total += $classtotal[1];			
		// // }
		
		$typeresult = get_account_types(false, 5, 7);
		while ($accounttype=db_fetch($typeresult))
		{
			// $rep->Font('bold');
			// $rep->TextCol(0, 5, '   Purchases');
			// $rep->Font();
			// $rep->NewLine();
			$classtotal = display_type($a,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
				$pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
			$class_per_total += $classtotal[0];
			$class_acc_total += $classtotal[1];			
		}
		
		//Print Class Summary	
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2, "   Total Purchases and Transfers");
		
		$b = $class_per_total * $convert;
		$rep->AmountCol(2, 3, $b, $dec);
		$rep->TextCol(3, 4, compute_percent($b,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine();	
		
	//============ Display Movements from MSSQL
		
	$rep->NewLine();
	$rep->Font('bold');
	$rep->TextCol(0, 5, 'Inventory Movements');
	$rep->Font();
	$rep->NewLine();
	
		$sql = "SELECT movements.movementcode, MovementTypes.Description,  
					SUM(CASE
							WHEN Products.pVatable = 1 
								THEN (extended/1.12)
							ELSE
								(extended)
							END) AS total
				 from MovementLine inner join Movements 
				on MovementLine.MovementID = Movements.MovementID inner join Products on Products.ProductID = MovementLine.ProductID  
				inner join MovementTypes on Movements.MovementCode = MovementTypes.MovementCode  
				 where CAST (Movements.PostedDate  as DATE) between  '".date2sql($from)."' and '".date2sql($to)."' and  Movements .status = 2
				 group by  movements.movementcode, MovementTypes.Description
				 order by  MovementTypes.Description  ";
		$ms_res = ms_db_query($sql);
		
		$exclude = array('D2BSR','SA2BO');
		$rep->Font('i');
		while($ms_row = mssql_fetch_array($ms_res))
		{
			if (in_array($ms_row['movementcode'],$exclude))
				continue;	
			$rep->TextCol(0, 2, "   ".$ms_row['Description']);
			$rep->AmountCol(2, 3, $ms_row['total'], $dec);
			// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
			$rep->NewLine();	
		}
		$rep->Font('');
		
		//=====================================
		

		//============ Add: Beginning Inventory
		$beg_inv = get_gl_balance_from_to('', $from, '1200');
		
		
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine(1.5);
		$rep->TextCol(0, 2, "   Add:  Beginning Inventory");
		// $rep->TextCol(0, 2, "   Add:  Beginning Inventory");
		$rep->AmountCol(2, 3, $beg_inv, $dec);
		$rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		// $rep->TextCol(2, 3, $beg_inv);
			$b += $beg_inv;
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->NewLine();	
		
		$salesper += $class_per_total;
		$salesacc += $class_acc_total;
	
		//============ Less: Ending Inventory
		$end_inv = get_gl_balance_from_to('', add_days($to,1), '1200');
		
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine(1.5);
		$rep->TextCol(0, 2, "   Less:  Ending Inventory");
		// $rep->TextCol(0, 2, "   Add:  Beginning Inventory");
		$rep->AmountCol(2, 3, $end_inv, $dec);
		$rep->TextCol(3, 4, compute_percent($end_inv,$a));
		// $rep->TextCol(2, 3, $end_inv);
			$b -= $end_inv;
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->NewLine();	
		
		$salesper += $class_per_total;
		$salesacc += $class_acc_total;
	
	//============ Cost of Goods Sold
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2, "Cost of Goods Sold");
		$rep->AmountCol(2, 3, $b, $dec);
		$rep->TextCol(3, 4, compute_percent($b,$a));
		$rep->NewLine(2);
	
	
	//============= GROSS PROFIT
	
	//Print Class Summary	
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2,	'Gross Profit');
		$gp = $a-$b;
		$rep->AmountCol(2, 3, $gp, $dec);
		$rep->TextCol(3, 4, compute_percent($gp,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Line($rep->row-6);
		$rep->Font();
		$rep->NewLine(2);	
	
	
	//============ Operating Expense
	$class = get_account_class(5);
	
	$class_per_total = 0;
	$class_acc_total = 0;
		
	$convert = get_class_type_convert($class["ctype"]); 		
	
	$accounttype = get_account_type(8);	
	//Print Class Name	
	$rep->Font('bold');
	$rep->TextCol(0, 5, 'C.   '.$accounttype["name"]);
	$rep->Font();
	$rep->NewLine();
	
	// while ($accounttype=db_fetch($typeresult))
		// {
			$classtotal = display_type($a,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
				$pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
			
			$class_per_total += $classtotal[0];
			$class_acc_total += $classtotal[1];			
		// }
		
		//Print Class Summary	
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2,	_('Total') . " " . $accounttype["name"]);
		$rep->AmountCol(2, 3, $class_per_total * $convert, $dec);
			$c = $class_per_total * $convert;
			$rep->TextCol(3, 4, compute_percent($c,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine(2);	

		$salesper += $class_per_total;
		$salesacc += $class_acc_total;
	
	
	//============= Net Income from Operations
	
	//Print Class Summary	
		$rep->row += 6;
		// $rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2,	'Net Income from Operations');
		$nifo = $gp-$c;
		$rep->AmountCol(2, 3, $nifo, $dec);
		$rep->TextCol(3, 4, compute_percent($nifo,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine(2);	
	
	
	//============ Other Income - Non Operating Expense
	$class = get_account_class(4);
	
	$class_per_total = 0;
	$class_acc_total = 0;
		
	$convert = get_class_type_convert($class["ctype"]); 		
	
	$accounttype = get_account_type(4020);	
	//Print Class Name	
	$rep->Font('bold');
	$rep->TextCol(0, 5, 'D.   Other Income (Expense)');
	$rep->Font();
	$rep->NewLine();
	
	// while ($accounttype=db_fetch($typeresult))
		// {
			$classtotal = display_type($a,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
				$pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
			
			$class_per_total += $classtotal[0];
			$class_acc_total += $classtotal[1];			
		// }
		
		//Print Class Summary	
		$rep->row += 6;
		$rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2,	_('Total') . " Other Income (Expense)");
		$rep->AmountCol(2, 3, $class_per_total * $convert, $dec);
			$d = $class_per_total * $convert;
		$rep->TextCol(3, 4, compute_percent($d,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine(2);	

		$salesper += $class_per_total;
		$salesacc += $class_acc_total;
	
	
	//============= Net Income Before Tax
	
	//Print Class Summary	
		$rep->row += 6;
		// $rep->Line($rep->row);
		$rep->NewLine();
		$rep->Font('bold');
		$rep->TextCol(0, 2,	'Net Income Before Tax');
		$nibt = $nifo+$d;
		$rep->AmountCol(2, 3, $nibt, $dec);
		$rep->TextCol(3, 4, compute_percent($nibt,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine();	
	
	//----------------------------------------------------------------------------------
	
	
	//DISPLAY PROMO FUND ---------------- 
	// if($_SESSION['wa_current_user']->username == 'admin')
	if (true)
	{
		$accounts = array(2470,2471,2472,2473,2474,2475,2476,2477,2478,2479,2480,2481,2482,2483);
		$rep->row += 6;
		// $rep->Line($rep->row);
		$rep->NewLine(2);
		$rep->Font('bold');
		$rep->TextCol(0, 2,	'Promo Fund Balance');
		
		$pf_balance = abs(get_gl_trans_from_to_array($from, $to, $accounts));
		$rep->AmountCol(2, 3, $pf_balance, $dec);
		$rep->TextCol(3, 4, compute_percent($pf_balance,$a));
		// $rep->AmountCol(3, 4, $class_acc_total * $convert, $dec);
		// $rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine();	
	}
	//----------------------------------------------------------------------------------
		// if(
		// $salesper == 0 && 
		// $salesacc == 0
		// ){
			// display_error("No reports can be generated with the given parameters.");
			// die();
		// }
	
	// $rep->Font('bold');	
	// $rep->TextCol(0, 2,	_('Net Income'));
	// $rep->AmountCol(2, 3, $salesper *-1, $dec); // always convert
	// $rep->AmountCol(3, 4, $salesacc * -1, $dec);
	// $rep->AmountCol(4, 5, Achieve($salesper, $salesacc), $pdec);
	// if ($graphics)
	// {
		// $pg->x[] = _('Net Income');
		// $pg->y[] = abs($salesper);
		// $pg->z[] = abs($salesacc);
	// }
	// $rep->Font();
	// $rep->NewLine();
	// $rep->Line($rep->row);
	// if ($graphics)
	// {
		// global $decseps, $graph_skin;
		// $pg->title     = $rep->title;
		// $pg->axis_x    = _("Group");
		// $pg->axis_y    = _("Amount");
		// $pg->graphic_1 = $headers[2];
		// $pg->graphic_2 = $headers[3];
		// $pg->type      = $graphics;
		// $pg->skin      = $graph_skin;
		// $pg->built_in  = false;
		// $pg->fontfile  = $path_to_root . "/reporting/fonts/Vera.ttf";
		// $pg->latin_notation = ($decseps[$_SESSION["wa_current_user"]->prefs->dec_sep()] != ".");
		// $filename = $comp_path.'/'.user_company(). "/pdf_files/test.png";
		// $pg->display($filename, true);
		// $w = $pg->width / 1.5;
		// $h = $pg->height / 1.5;
		// $x = ($rep->pageWidth - $w) / 2;
		// $rep->NewLine(2);
		// if ($rep->row - $h < $rep->bottomMargin)
			// $rep->Header();
		// $rep->AddImage($filename, $x, $rep->row - $h, $w, $h);
	// }
		
	$rep->End();
}

function get_gl_trans_from_to_array($from_date, $to_date, $account, $dimension=0, $dimension2=0)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

    $sql = "SELECT SUM(amount) FROM ".TB_PREF."gl_trans
		WHERE account IN ('".implode("','",$account)."')";
	if ($from_date != "")
		$sql .= " AND tran_date >= '$from'";
	if ($to_date != "")
		$sql .= " AND tran_date <= '$to'";
	if ($dimension != 0)
  		$sql .= " AND dimension_id = ".($dimension<0?0:db_escape($dimension));
	if ($dimension2 != 0)
  		$sql .= " AND dimension2_id = ".($dimension2<0?0:db_escape($dimension2));

	// echo $sql; die;;
	$result = db_query($sql, "Transactions for account $account could not be calculated");

	$row = db_fetch_row($result);
	return $row[0];
}

?>