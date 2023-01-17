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
include_once($path_to_root . "/admin/db/company_db.inc");

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
   
  if($_from[1] != $_to[1] || $_from[2] != $_to[2])
		die;
		
   $bir = '1601E';
    $rep = new FrontReport(_('1601 E'), $bir, 'legal',9,'P');

	// $rep->addJpegFromFile($path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg',0,1000);
	$filename = $path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg';
		$rep->AddImage($filename, 0, 0, $rep->pageWidth, $rep->pageHeight);
		
	//$rep->TextWrap(50, 950, 100, _($telno), "left");
		
	$rep->fontSize += 4;
		
	$rep->TextWrap(120, 890, 100, spacing($_from[1]), "left");
	$rep->TextWrap(150, 890, 100, spacing($_from[2]), "left");

	$rep->fontSize -= 3;
	
	$telno = TRIM($coy['coy_no']);
	$rep->TextWrap(476, 835, 100, spacing($telno, '  '), "left");
	
	$rep->fontSize += 2;
	
	$first_tax = substr($coy['gst_no'], 0, 3);
	$second_tax = substr($coy['gst_no'], 3, 3);
	$third_tax = substr($coy['gst_no'], 6, 3);
	$fourth_tax = substr($coy['gst_no'], 9, 3);
	
	$rep->TextWrap(57, 858, 100, spacing($first_tax), "left");
	$rep->TextWrap(107, 858, 100, spacing($second_tax), "left");
	$rep->TextWrap(159, 858, 100, spacing($third_tax), "left");
	$rep->TextWrap(208, 858, 100, spacing($fourth_tax), "left");
	
	$rep->TextWrap(322, 858, 100, spacing($coy['rdo_code']), "left");
	
	$rep->fontSize -= 2;
	$rep->TextWrap(436, 858, 100, $coy['line_of_business'], "left");
	
	$rep->TextWrap(57, 835, 300, $coy['coy_name'], "left");
	$rep->TextWrap(57, 814, 300, $coy['postal_address'], "left");
	
	$rep->TextWrap(505, 813, 300, spacing($coy['zip_code'], '  '), "left");
		
    $rep->End();
}

?>