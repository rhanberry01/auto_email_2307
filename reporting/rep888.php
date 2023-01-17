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
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_remittance();

function print_remittance()
{
    global $path_to_root;
	
	$remittance_id = $_POST['PARAM_0'];
	
	// if ($destination)
		// include_once($path_to_root . "/reporting/includes/excel_report.inc");
	// else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();


    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $cust, 'to' => ''));

    $rep = new FrontReport(_('Remittance'), "Remittance", 'SRS_CV');

    // $rep->Info($params, $cols, $headers, $aligns);
    // $rep->Header();
	
	$rep->row = $rep->pageHeight - $rep->topMargin;
	$rep->row -= 31;//$rep->lineHeight*2;
	
	$sql = "SELECT * FROM ".TB_PREF."remittance WHERE remittance_id = ".$remittance_id;
		$res = db_query($sql);
		$r_row = db_fetch($res);
		
		$cashier_id = $r_row['cashier_id'];
		$remittance_date = sql2date($r_row['remittance_date']);
		
		//show SHORT / OVER in here.
		// use terminal-ODBC table to loop on all the terminals
		
		//====== get initial cash
		// $sql = "SELECT amount FROM ".TB_PREF."initial_cash WHERE cashier_id = $cashier_id AND i_date = '".$r_row['remittance_date']."'";
		// $res = db_query($sql);
		// $row = db_fetch($res);
		
		$initial_cash = 0;
		//==============
		
		//====== get total remittances and id
		$sql = "SELECT total_cash,remittance_id FROM ".TB_PREF."remittance 
					WHERE cashier_id = $cashier_id
					AND remittance_date = '".date2sql($remittance_date)."'";
		$res = db_query($sql);
		
		$total_remittances = 0;
		$r_ids = array();
		while($row = db_fetch($res))
		{
			$total_remittances += $row['total_amount'];
			$r_ids[] = $row['remittance_id'];
		}
		
		
		//====== get cash, credit card, etc.
		$cash_description = 'Cash';
		$total_payments = array(); // [payment type] = array(total_amount, count)
		
		$sql = "SELECT * FROM FinishedPayments 
					WHERE LogDate = '".date2sql($remittance_date)."'
					AND UserID = $cashier_id
					AND Voided = 0
					ORDER BY Description";
		$res = ms_db_query($sql);
		
		while($p_row = mssql_fetch_array($res))
		{
			$total_payments[$p_row['Description']][0] += $p_row['Amount'];
			$total_payments[$p_row['Description']][1] ++;
		}
		//================================
		$over_short = $total_remittances - $initial_cash -  $total_payments[$cash_description][0];
		//================================
	
	$full_width = ($rep->pageWidth - $rep->leftMargin- $rep->rightMargin - ($rep->leftMargin));
	
	$rep->fontSize += 2;
	$rep->font('B');
	$rep->TextWrap($rep->leftMargin, $rep->row+10, $rep->pageWidth, get_company_pref('coy_name'), 'center');
	$rep->font('');
	
	$rep->row -= 15;			
	$rep->TextWrap($rep->leftMargin, $rep->row, ($rep->pageWidth - $rep->leftMargin- $rep->rightMargin), 
				'Cashier Name : '.$r_row['cashier_name']);
	$rep->TextWrap($rep->leftMargin, $rep->row-12, ($rep->pageWidth - $rep->leftMargin- $rep->rightMargin), 
				'Remittance # : '.implode(',',$r_ids));
				
	$rep->TextWrap($rep->leftMargin+(($full_width/2) + $rep->leftMargin), $rep->row, $h_width, 
				'Date : '.$remittance_date , 'left');

	$rep->fontSize -= 1;
	
	$rep->row -= $rep->lineHeight*2; 
	
	
	$rep->NewLine();
	$reprow = $rep->row;
	$rep->font('b');
	$rep->TextWrap($rep->leftMargin, $rep->row, $full_width/2, 'Total Remittances', 'center');
	$rep->font('');
	
	//===== remittance header
	
	$col_width = (($full_width/2)/3);
	$rep->NewLine(2);
	$rep->TextWrap($rep->leftMargin, $rep->row, $col_width, 'Denomination', 'center',true);
	$rep->TextWrap($rep->leftMargin+$col_width, $rep->row, $col_width, 'Pieces', 'center',true);
	$rep->TextWrap($rep->leftMargin+$col_width*2, $rep->row, $col_width, 'Total', 'center',true);
	
	
	
	$sql = "SELECT a.denomination, SUM(b.quantity)
				FROM 0_remittance_details b
				RIGHT OUTER JOIN 0_denominations a ON a.denomination =  b.denomination
				AND remittance_id IN (".implode(',', $r_ids).")
				GROUP BY a.denomination
				ORDER BY a.denomination DESC";
	$result = db_query($sql,"could not get denomination count");
	$d_pcs_t = $d_total = $k = 0; 
	while ($myrow = db_fetch($result)) 
	{
		$rep->NewLine(1);	
		$rep->font('b');
		$rep->TextWrap($rep->leftMargin, $rep->row, $col_width, number_format2($myrow[0],2), 'center',true);
		$rep->font('');
		$rep->TextWrap($rep->leftMargin+$col_width, $rep->row, $col_width, $myrow[1] ? $myrow[1] : '-', 'center',true);
		
		if ($myrow[0] * $myrow[1] > 0)
		{
			$rep->TextWrap($rep->leftMargin+$col_width*2, $rep->row, $col_width, number_format2($myrow[0] * $myrow[1],2) .'  ', 'right',true);
			// amount_cell($myrow[0] * $myrow[1]);
			$d_total += $myrow[0] * $myrow[1];
			$d_pcs_t += $myrow[1];
		}
		else
			$rep->TextWrap($rep->leftMargin+$col_width*2, $rep->row, $col_width, '-', 'center',true);
	}
	$rep->NewLine(1);	
	$rep->font('b');
	$rep->TextWrap($rep->leftMargin, $rep->row, $col_width, 'TOTAL CASH', 'center',true);
	$rep->TextWrap($rep->leftMargin+$col_width, $rep->row, $col_width, $d_pcs_t, 'center',true);
	$rep->TextWrap($rep->leftMargin+$col_width*2, $rep->row, $col_width, number_format2($d_total,2).'  ', 'right',true);
	$rep->font('');
	
	$h_width = ($full_width/2) + $rep->leftMargin;
	$rep->row = $reprow;
	$rep->font('b');
	// $rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $h_width, 'Initial Cash : '. number_format2($initial_cash,2), 'left');
	
	// $rep->NewLine(2);
	
	$rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $h_width, 'Tenders', 'center');
	$rep->font('');
	
	$rep->NewLine(2);
	$rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $col_width, 'Type', 'center',true);
	$rep->TextWrap($rep->leftMargin+$h_width+$col_width, $rep->row, $col_width, 'Amount', 'center',true);
	$rep->TextWrap($rep->leftMargin+$h_width+$col_width*2, $rep->row, $col_width, '# of trans', 'center',true);
	
	$t_count  = $t_total = $k = 0; 
	foreach($total_payments as $type => $payment)
	{
		$t_total += $payment[0];
		$t_count += $payment[1];
		$rep->NewLine();
			$rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $col_width, $type, 'center',true);
			$rep->TextWrap($rep->leftMargin+$h_width+$col_width, $rep->row, $col_width, number_format2($payment[0],2).'  ', 'right',true);
			$rep->TextWrap($rep->leftMargin+$h_width+$col_width*2, $rep->row, $col_width, $payment[1].'  ', 'right',true);
	}
	$rep->NewLine();
	$rep->font('b');
	$rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $col_width, 'TOTAL', 'center',true);
	$rep->TextWrap($rep->leftMargin+$h_width+$col_width, $rep->row, $col_width, number_format2($t_total,2).'  ', 'right',true);
	$rep->TextWrap($rep->leftMargin+$h_width+$col_width*2, $rep->row, $col_width, $t_count .'  ', 'right',true);
	$rep->font('');
	$rep->NewLine(2);

	$rep->font('b');
	$rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $col_width*3, '   OVER (CASH) : ', 'left',true);
	$rep->TextWrap($rep->leftMargin+$h_width+$col_width, $rep->row, $col_width*2, ($over_short > 0 ? number_format2($over_short,2) : '---'), 'center',false);
	$rep->NewLine();
	$rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $col_width*3, '   SHORT (CASH)	 : ', 'left',true);
	$rep->TextWrap($rep->leftMargin+$h_width+$col_width, $rep->row, $col_width*2, ($over_short < 0 ? '--- '.number_format2(-$over_short,2). ' ---' : '---'), 'center',false);
	$rep->font('');
	$rep->NewLine(2);
	
	if ($r_row['remarks'] != '')
	{
		$sobra = $rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $col_width*3, 
			'Remarks: '.$r_row['remarks'],'left', 0, 0, NULL, 0, true);
		
		while ($sobra != '')
		{
			$rep->NewLine();
			$sobra = $rep->TextWrap($rep->leftMargin+$h_width, $rep->row, $col_width*3, $sobra,'left', 0, 0, NULL, 0, true);
		}
		
		$rep->NewLine(3);
	}
	
	$rep->LineTo($rep->leftMargin+$h_width, $rep->row, $rep->leftMargin+$h_width+$col_width*3, $rep->row);
				
    $rep->End();
}

?>