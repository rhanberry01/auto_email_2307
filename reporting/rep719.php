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
function get_amounts($from_date, $to_date, $type,$trans_no,$acct)
{
	$res = acct_trans($from_date, $to_date, $trans_no, null, 0,0, $type,null, null,
		" ORDER BY amount DESC");
	
	// 6280		Store Supplies-expense
	// 1410011		Input Tax Goods Not for Resale
	// 2000010		Accounts Payable - Non Trade
	// 23303158		1% EWT payable - local supplier of goods - corporate

	$arr = array();// 0->inv amount(6280+1410011) 1->VAT(1410011) 2->taxable(6280) 3->ewt(23303158/other acct)
	while($row = db_fetch($res))
	{
			if ($row['account'] == $acct)
			{
				$arr['ewt'] = abs(round($row['amount'],2));
				continue;
			}
			
			if ($row['amount'] > 0 AND ($row['account'] == '1410011' OR $row['account'] == '1410012'))
			{
				$arr['vat'] = abs(round($row['amount'],2)); 
				continue;
			}
			
			if ($row['amount'] > 0 AND stripos($row['account_name'], ' NV') === false)
			{
				$arr['taxable'] += abs(round($row['amount'],2));
				continue;
			}
			
			if ($row['amount'] > 0)
				$arr['others'] += abs(round($row['amount'],2));
	}
		$arr['inv_amount'] = $arr['vat'] + $arr['taxable'] + $arr['others'];
		
	return $arr;
}

function acct_trans($from_date, $to_date, $trans_no=0,
	$account=null, $dimension=0, $dimension2=0, $filter_type=null,
	$amount_min=null, $amount_max=null, $additional='')
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT ".TB_PREF."gl_trans.*, "
		.TB_PREF."chart_master.account_name FROM ".TB_PREF."gl_trans, "
		.TB_PREF."chart_master
		WHERE ".TB_PREF."chart_master.account_code=".TB_PREF."gl_trans.account
		AND tran_date >= '$from'
		AND tran_date <= '$to'
		AND round(amount,2) != 0";
	if ($trans_no > 0)
		$sql .= " AND ".TB_PREF."gl_trans.type_no = $trans_no";

	if ($account != null)
		$sql .= " AND ".TB_PREF."gl_trans.account = ".db_escape($account);

	if ($dimension != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension_id = ".($dimension<0?0:db_escape($dimension));

	if ($dimension2 != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension2_id = ".($dimension2<0?0:db_escape($dimension2));

	if ($filter_type != null AND is_numeric($filter_type))
		$sql .= " AND ".TB_PREF."gl_trans.type= ".db_escape($filter_type);
		
	if ($amount_min != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) >= ABS(".db_escape($amount_min).")";
	
	if ($amount_max != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) <= ABS(".db_escape($amount_max).")";

	if ($additional == '')
		$sql .= " ORDER BY account, tran_date, counter";
	else
		$sql .= " $additional";
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
	$destination = $_POST['PARAM_4'];
	
	$sql = "SELECT account_name FROM ".TB_PREF."chart_master
			WHERE account_code IN (".implode(',',$fromacc).")";
			
	
	$acct_name = '';
	foreach($fromacc as $acct_code)
		$acct_name .= get_gl_account_name($acct_code).' / ';
	
	$acct_name = substr($acct_name,0,-2);

	$comments = '';
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$rep = new FrontReport(_('EWT Payable Expenses'), "EWTPayableExpenses", user_pagesize(),9 ,'L');
	$dec = user_price_dec();

  //$cols = array(0, 80, 100, 150, 210, 280, 340, 400, 450, 510, 570);
	$cols = array(0,70,220,300,375,465,555,645,735);
	//------------0--1---2---3----4----5----6----7----8----9----10-------
	//-----------------------dim1-dim2-----------------------------------
	//-----------------------dim1----------------------------------------
	//-------------------------------------------------------------------
	$aligns = array('left','left','left','left','right','right','right','right');

	$headers = array(_('Date'),	'Payee', _('Ref #'), 'TIN', 'Inv. Amount', 'VAT','Taxable', 'EWT Payable');


	$params =   array( 	0 => $comments,
					1 => array('text' => _('Period'), 'from' => $from, 'to' => $to));


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
		$rep->Font('bold');
		$rep->TextCol(0, 8,	$account['account_code'] . " " . $account['account_name'], -2);
		$rep->NewLine(2);
		$rep->Font();
		
		$trans = acct_trans($from, $to, -1, $account['account_code'], $dimension, $dimension2);
		$rows = db_num_rows($trans);
		
		$per_acct_total = array();
		if ($rows > 0)
		{
			while ($myrow=db_fetch($trans))
			{
				$reference = get_reference($myrow["type"], $myrow["type_no"]);
				
				if ($t_nt == 1 AND(strpos($reference,'NT') !== false))
					continue;
				else if ($t_nt == 0 AND(strpos($reference,'NT') === false))
					continue;
				
				$tran_amts = get_amounts($from, $to, $myrow["type"],$myrow["type_no"],$account['account_code']);
				
				$per_acct_total['inv_amount'] += $tran_amts['taxable'];
				$per_acct_total['vat'] += $tran_amts['ewt'];
				$per_acct_total['taxable'] += $tran_amts['taxable'];
				$per_acct_total['ewt'] += $tran_amts['ewt'];
				
				$grand_total['inv_amount'] += $tran_amts['taxable'];
				$grand_total['vat'] += $tran_amts['ewt'];
				$grand_total['taxable'] += $tran_amts['taxable'];
				$grand_total['ewt'] += $tran_amts['ewt'];
				
				
				$rep->DateCol(0, 1,	$myrow["tran_date"], true);
				$rep->TextCol(1, 2, payment_person_name($myrow["person_type_id"],$myrow["person_id"], false));
				// $rep->TextCol(2, 3, '  ' . $myrow["type_no"]);
				$rep->TextCol(2, 3, '  ' . $reference);
				$rep->TextCol(3, 4,	'  ' . get_supplier_tin($myrow["person_id"]));
				$rep->TextCol(4, 5,	'  ' . number_format2($tran_amts['inv_amount'],2));
				$rep->TextCol(5, 6,	'  ' . number_format2($tran_amts['vat'],2));
				$rep->TextCol(6, 7,	'  ' . number_format2($tran_amts['taxable'],2));
				$rep->TextCol(7, 8,	'  ' . number_format2($tran_amts['ewt'],2));
				
				$rep->NewLine();
				if ($rep->row < $rep->bottomMargin + $rep->lineHeight)
				{
					$rep->Line($rep->row - 2);
					$rep->Header();
				}
			}
			$rep->Line($rep->row + 10);
			$rep->TextCol(0, 1,	'  ');
			$rep->TextCol(1, 2,	'  ');
			$rep->TextCol(2, 3,	'  ');
			$rep->TextCol(3, 4,	'  ');
			$rep->TextCol(4, 5,	'  ' . number_format2($per_acct_total['inv_amount'],2));
			$rep->TextCol(5, 6,	'  ' . number_format2($per_acct_total['vat'],2));
			$rep->TextCol(6, 7,	'  ' . number_format2($per_acct_total['taxable'],2));
			$rep->TextCol(7, 8,	'  ' . number_format2($per_acct_total['ewt'],2));
		}
	
		$rep->Line($rep->row - $rep->lineHeight + 4,1.5);
		$rep->NewLine(2,1);
	}
	
	$rep->Font('bold');
	$rep->TextCol(0, 1,	'  ');
	$rep->TextCol(1, 2,	'  ');
	$rep->TextCol(2, 3,	'  ');
	$rep->TextCol(3, 4,	'  ');
	$rep->TextCol(4, 5,	'  ' . number_format2($grand_total['inv_amount'],2));
	$rep->TextCol(5, 6,	'  ' . number_format2($grand_total['vat'],2));
	$rep->TextCol(6, 7,	'  ' . number_format2($grand_total['taxable'],2));
	$rep->TextCol(7, 8,	'  ' . number_format2($grand_total['ewt'],2));
	
	if ($destination)
		$rep->NewLine();
	
	$rep->End();
}


// $sql = "SELECT 0_gl_trans.*, 
		// 0_chart_master.account_name FROM ".TB_PREF."gl_trans, 
		// 0_chart_master
		// WHERE 0_chart_master.account_code=".TB_PREF."gl_trans.account
		// AND 0_gl_trans.type_no = 9341 AND 0_gl_trans.type= 20 ORDER BY account, tran_date, counter";
?>