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
function compute_percent($amount,$a)
{
	$m = 1;
	
	if ($amount < 0)
		$m = -1;
	$amount = abs($amount);
	$prc = round(($amount/$a) * 100,2);
	
	return  $prc > 0 ? $prc*$m ." %" : '';
}

//and  (memo_ not like '%to record CAJE%' and memo_ not like '%to record PAJE%' and memo_ not like '%to record closing entries%')
function get_gl_trans_from_to_array_r($from,$to,$accounts)
{
	$from = date2sql($from);
	$to = date2sql($to);

    $sql = "SELECT sum(amount)
			FROM 0_gl_trans a
				JOIN 0_chart_master b ON a.account = b.account_code
				LEFT OUTER JOIN  0_suppliers c ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
				WHERE a.tran_date >='".$from."'
				AND a.tran_date <= '".$to."'
				AND a.account IN(".$accounts.") and  (memo_ not like '%to record closing entries%')
			";

	 //echo $sql; die;;
	$result = db_query($sql, "Transactions for account $account could not be calculated");

	$row = db_fetch_row($result);
	return $row[0];
}



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
		$per_balance = get_gl_trans_from_to_opex($from, $to, $account["account_code"], $dimension, $dimension2);

		if ($compare == 2)
			$acc_balance = get_budget_trans_from_to($begin, $end, $account["account_code"], $dimension, $dimension2);
		else
			$acc_balance = get_gl_trans_from_to_opex($begin, $end, $account["account_code"], $dimension, $dimension2);
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
		$rep->TextCol(2, 3,	$account['account_name']);

		$rep->AmountCol(3, 4, $per_balance * $convert, $dec);
		
		if ($a != 0) // display % to revenue
		{
			$rep->TextCol(3, 4, compute_percent($per_balance * $convert,$a));	
		}


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
	

		$totals_arr = display_type($a,$accounttype["id"], $accounttype["name"], $from, $to, $begin, $end, $compare, $convert, $dec, 
			$pdec, $rep, $dimension, $dimension2, $pg, $graphics,$display_zero);
		$per_balance_total += $totals_arr[0];
		$acc_balance_total += $totals_arr[1];
	}

	
	$totals_arr[0] = $code_per_balance + $per_balance_total;
	$totals_arr[1] = $code_acc_balance + $acc_balance_total;
	return $totals_arr;
}



function print_GL_transactions()
{
	set_time_limit(0);
	global $path_to_root, $systypes_array, $systypes_array_short,$db_connections;;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	$display_zero = 1;
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	//$rep = new FrontReport(_('Purchase Report'), "PurchaseReport", 'LONG',8 ,'P');

	$branch_name = strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"]);
	echo $branch_name.'-----';
	$rep = new FrontReport(_('Operating Expense'), $branch_name." OPEX ".$from.'-'.$to, user_pagesize(),9 ,'P');
	$dec = user_price_dec();


	$cols = array(0, 20, 100, 420);
	$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left');
	$headers = array('','Account Code', 'Account Description', 'Amount');
	//'Discount', 


	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from, 'to' => $to));
						
	if ($destination)
	{
		global $db_connections;
		$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"];
		$params[] = array('text' => _('Branch'), 'from' => $this_branch, 'to' => '');
	}

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	

	$last_supp_id = $supp_name = $last_gst = '';
	$counter = $supp_total_ = $ov_total = 0;
	
	

		if($xyz == 0) // VAT
		{
			$rep->Font('bi');
			$rep->fontSize += 3;
			//$rep->TextCol(0, 5, '     VATABLE Suppliers');
			$rep->Line($rep->row -5);
			$rep->NewLine(2);
			$rep->fontSize -= 3;
		}
	
			
		$sql = "SELECT x.*, y.*, z.account_name  FROM (SELECT a.expense_id, a.acc_code, b.expense_desc FROM 0_chart_expense_group as a
		LEFT JOIN 0_chart_expense_category as b
		on a.expense_id=b.id) as x
		LEFT JOIN 
		(SELECT account, sum(amount) as amount FROM `0_gl_trans`
		where  tran_date>='".date2sql($from)."'
		and tran_date<= '".date2sql($to)."'  and  (memo_ not like '%to record closing entries%')
		GROUP BY account) as y
		ON x.acc_code=y.account
		LEFT JOIN 0_chart_master as z
		ON x.acc_code=z.account_code
		ORDER BY expense_id,acc_code";
		
		//die($sql );

		
		$res = db_query($sql,'error.');
		
		$company_pref = get_company_prefs();
		
		while ($row = db_fetch($res))
		{
				$counter ++;
			
				$supp_name = $row['expense_desc'];
				
			
			
			if ($last_supp_id != $row['expense_id'] )
			{
				if ($last_supp_id != '')
				{
					// total per supplier
					$rep->Line($rep->row+10);
					
					$rep->Font('bold');
					$rep->Line($rep->row + 10);
					
					if ($destination)
					{
						//$rep->sheet->writeString($rep->y, 2, '===> TOTAL:', $rep->formatTitle);
						$rep->sheet->writeString($rep->y, 2, '===> TOTAL:');
					}
					else
					{
						$rep->TextCol(0, 1,	'');
						$rep->TextCol(1, 2, '');
						$rep->TextCol(2, 3, '');
					}
					
			
					$rep->AmountCol(3, 4, $supp_total[1],2);

					$rep->Font('');
					$rep->NewLine(2);
					
					$supp_total = array(0,0,0,0,0);

				}
				
				$rep->font('bold');
				$rep->TextCol(0, 5,	$supp_name . '   -   '.$t);
				$rep->Line($rep->row -2);
				$rep->NewLine();
				$rep->font('');
				
				
			
			}
				
			
			//==================================
				$last_supp_id = $row['expense_id'];
			
			
				$details[$counter][0] = '';
				$details[$counter][1] = $row['acc_code'];
				$details[$counter][2] = $row['account_name'];
				if($row['amount']==''){
					$details[$counter][3] = round(0,2);
				}
				else{
					$details[$counter][3] = round($row['amount'],2);
				}
				
					

				$supp_total[1] += $details[$counter][3];
				
			
			$rep->TextCol(0, 1, $systypes_array_short[$row["type"]], -2);
			$rep->TextCol(1, 2, $details[$counter][1]);
			$rep->TextCol(2, 3, $details[$counter][2] , -5);		
			$rep->AmountCol(3, 4, $details[$counter][3],2);
			$rep->NewLine();	
		
			$t_reading+=$details[$counter][3];
			
		
		}
		

		
		if ($last_supp_id != '')
		{
			// total per supplier
			$rep->Line($rep->row+10);
			
			$rep->Font('bold');
			$rep->Line($rep->row + 10);
			$rep->TextCol(0, 1,	'');
			$rep->TextCol(1, 2, '');
			$rep->TextCol(2, 3, '');
			$rep->AmountCol(3, 4, $supp_total[1],2);

		}
			
	
	// die;
	
	
	$rep->NewLine();

	// ============================== APV TOTAL
	$rep->Font('bold');
	$rep->Line($rep->row + 10,1);
	$rep->TextCol(0, 1,	'');
	$rep->TextCol(1, 2, '');
	$rep->TextCol(2, 3, 'TOTAL : ');
	$rep->AmountCol(3, 4, $t_reading,2);

	$rep->Font('');
	$rep->NewLine();

	//============ Other Income - Non Operating Expense
	$class = get_account_class(4);
	
	$class_per_total = 0;
	$class_acc_total = 0;
		
	$convert = get_class_type_convert($class["ctype"]); 		
	
	$accounttype = get_account_type(4020);	
	//Print Class Name	
	$rep->Font('bold');
	$rep->TextCol(0, 5, 'D.   Other Income');
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
		$rep->TextCol(0, 2,	_('Total') . " Other Income ");
		$rep->AmountCol(3, 4, $class_per_total * $convert, $dec);
			$d = $class_per_total * $convert;
		$rep->TextCol(4, 5, compute_percent($d,$a));
		$rep->AmountCol(4, 5, $class_acc_total * $convert, $dec);
		//$rep->AmountCol(4, 5, Achieve($class_per_total, $class_acc_total), $pdec);
		$rep->Font();
		$rep->NewLine(2);	

		$salesper += $class_per_total;
		$salesacc += $class_acc_total;

		$accounts = '23001606,
							2470,
							2471,
							2472,
							2473,
							2474,
							2475,
							2476,
							2477,
							2478,
							2479,
							2480,
							2481,
							2482,
							2483,
							4020020,
							4020025,
							4020030,
							402005,
							4020050,
							4020051,
							4020052,
							4020060,
							4020061';

		$rep->row += 6;
		$rep->NewLine(2);
		$rep->Font('bold');
		$rep->TextCol(0, 2,	'Promo Fund ');

		
		$pf_balance = abs(get_gl_trans_from_to_array_r($from,$to,$accounts));
		$rep->AmountCol(3, 4, $pf_balance, $dec);
		$rep->TextCol(4, 5, compute_percent($pf_balance,$a));
		$rep->Font();
		$rep->NewLine();	

	
	$rep->End();
}

?>