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
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

//----------------------------------------------------------------------------------------------------

print_cash_disbursement_book();

function print_cash_disbursement_book()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
    $comments = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    
	$dec = user_price_dec();

	$headers = array('Date',
								'Supplier',
								'INV #',
								'Amount',
								'Input Tax',
								'EWT',
								'Disc',
								'Ret',
								'Net Amount');
	
	$aligns = array('left', 'left', 'left', 'right', 'right', 'right', 'right', 'right', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Customer'), 'from' => $supp, 'to' => ''));

    $rep = new FrontReport(_('Cash Disbursement Book'), "CashDisbursementBook", user_pagesize(),9,'L');

	$lastx = $rep->pageWidth - 36;
	$minus = 70;
	$cols = array(0, 70, $lastx - ($minus*8), $lastx - ($minus*7), $lastx - ($minus*6), $lastx - ($minus*5), $lastx - ($minus*4), $lastx - ($minus*3), $lastx - ($minus*2), $lastx - $minus);
	
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$sql = "SELECT * FROM ".TB_PREF."books_disbursements 
				WHERE date BETWEEN '".date2sql($from)."'
				AND '".date2sql($to)."'";
	
	$res = db_query($sql, "could not retrieve data");
	
	if(db_num_rows($res) <= 0){
		$rep->TextCol(0,6, "No report found.");
	}else{
		while ($myrow = db_fetch($res))
		{
			if($myrow['purchase_amount'] != 0){
				$rep->TextCol(0, 1, sql2date($myrow['date']));
				if(is_numeric($myrow['supplier_id']))
					$rep->TextCol(1, 2, htmlspecialchars_decode(get_supplier_name($myrow['supplier_id'])));
				else
					$rep->TextCol(1, 2, htmlspecialchars_decode($myrow['supplier_id']));
				$rep->TextCol(2, 3, $myrow['reference']);
				$rep->TextCol(3, 4, number_format2($myrow['purchase_amount'],2));
				$rep->TextCol(4, 5, number_format2($myrow['input_tax'],2));
				$rep->TextCol(5, 6, number_format2($myrow['ewt'],2));
				$rep->TextCol(6, 7, number_format2($myrow['discount'],2));
				$rep->TextCol(7, 8, number_format2($myrow['returns'],2));
				$rep->TextCol(8, 9, number_format2($myrow['purchase_amount'] + $myrow['ewt'] - $myrow['discount'],2));

					$totals[1]+=$myrow['purchase_amount'];
			$totals[2]+=$myrow['input_tax'];
			$totals[3]+=$myrow['ewt'];
			$totals[4]+=$myrow['discount'];
			$totals[5]+=$myrow['returns'];
			$totals[6]+=$myrow['purchase_amount'] + $myrow['ewt'] - $myrow['discount'];
			
				$rep->NewLine();
				
				
				if ($rep->row < $rep->bottomMargin + $rep->lineHeight)
				{
					$rep->Line($rep->row - 2);
					$rep->Header();
				}
			}
		}
		$s=3;
		$rep->Line($rep->row  - 3);
		$rep->NewLine();
		$rep->TextCol(0,3,"Total");
		foreach ($totals as $key => $value) {
			$rep->TextCol($s,$s+1,number_format2($value,2));
			$s++;
		}
			$rep->Line($rep->row  - 4);
	}
	

    $rep->End();
}

?>