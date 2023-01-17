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
include_once($path_to_root . "/reporting/includes/pdf_report.inc");

//----------------------------------------------------------------------------------------------------

print_cash_disbursement_book();

function spacing($value, $spaces=' '){
	$max = strlen($value);
	$next = 0;
	$display = '';
	
	while($next < $max){
		$display .= substr($value,$next,1);
		if($next < $max-1){
			$display .= $spaces;
		}
		$next++;
	}
	return $display;
}

function print_cash_disbursement_book()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
   
   $coy = get_company_prefs();
   
   $_from = explode_date_to_dmy($from);
   $_to = explode_date_to_dmy($to);
   
   $bir = '2551Q';
    $rep = new FrontReport(_('2551 Q'), $bir, 'legal',9,'P');

	// $rep->addJpegFromFile($path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg',0,1000);
	$filename = $path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg';
		$rep->AddImage($filename, 0, 0, $rep->pageWidth, $rep->pageHeight);
    
	$rep->fontSize += 4;
		
	$rep->TextWrap(102, 847, 100, spacing($_from[1]), "left");
	$rep->TextWrap(130, 847, 100, spacing($_from[2]), "left");
	
	$first_tax = substr($coy['gst_no'], 0, 3);
	$second_tax = substr($coy['gst_no'], 3, 3);
	$third_tax = substr($coy['gst_no'], 6, 3);
	$fourth_tax = substr($coy['gst_no'], 9, 3);
	
	$rep->TextWrap(61, 805, 100, spacing($first_tax), "left");
	$rep->TextWrap(100, 805, 100, spacing($second_tax), "left");
	$rep->TextWrap(138, 805, 100, spacing($third_tax), "left");
	$rep->TextWrap(176, 805, 100, spacing($fourth_tax), "left");
	
	$rep->TextWrap(263, 805, 100, spacing($coy['rdo_code'], '  '), "left");
	
	$rep->fontSize -= 2;
	$rep->TextWrap(425, 805, 100, $coy['line_of_business'], "left");
	
	$rep->TextWrap(47, 773, 300, $coy['coy_name'], "left");	
	$rep->TextWrap(47, 743, 300, $coy['postal_address'], "left");
	
	$rep->fontSize += 2;
	$rep->TextWrap(488, 773, 100, spacing($coy['coy_no'], '  '), "left");
	$rep->TextWrap(542, 743, 100, spacing($coy['zip_code']), "left");
	
		
	$sql = "SELECT SUM(ov_amount) as amount 
			FROM ".TB_PREF."debtor_trans
			WHERE type = 12
			AND tran_date BETWEEN ".db_escape(date2sql($from))." AND ".db_escape(date2sql($to));
		/*	AND MONTH(tran_date) = ".db_escape($_from[1])."
			AND YEAR(tran_date) = ".db_escape($_from[2]);*/
	$result = db_query($sql, "could not get amount");
	$row = db_fetch_row($result);
	$amount = $row[0];
	$total_amount = $amount * ($coy['perc_tax'] / 100);
	
	$rep->fontSize -= 2;
	$rep->TextWrap(167, 665, 100, $coy['atc'], "left");
	$rep->fontSize += 2;
	
	$rep->TextWrap(269, 665, 100, number_format2($amount, 2), "right");
	$rep->TextWrap(340, 665, 100, percent_format($coy['perc_tax'])."%", "right");
	$rep->TextWrap(483, 665, 100, number_format2($total_amount, 2), "right");


	$rep->End();
}

?>