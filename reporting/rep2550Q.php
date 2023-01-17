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

function print_($_x,$_y,&$rep, $str,$inc=14,$double=array(),$d=0)
{
	$x -= $inc;
	$counter = 0;
	
	if ($d == 0)
		$d = $inc;
	
	for($i=0;$i<strlen($str);$i++)
	{
		$x += $inc;
		
		if (in_array($i, $double))
			$x += $d;
			// $rep->TextWrap($_x+$x,$_y,100,$str{$i});
		
		$rep->TextWrap($_x+$x,$_y,100,$str{$i});
	}
}

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
   
      
   $_from = explode_date_to_dmy($from);
   $_to = explode_date_to_dmy($to);
   
   $bir = '2550Q';
    $rep = new FrontReport(_('2550 Q'), $bir, 'legal',9,'P');

	// $rep->addJpegFromFile($path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg',0,1000);
	$filename = $path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg';
		$rep->AddImage($filename, 0, 0, $rep->pageWidth, $rep->pageHeight);

		$myrow = get_company_prefs();
				
	$rep->fontSize += 5;
	$rep->TextWrap(98, 895, 100, spacing($_from[1],"  "), "left");
	$rep->TextWrap(135, 895, 100, spacing($_from[2],"  "), "left");
	
	//$rep->TextWrap(248, 895, 100, spacing($_from[1]), "left");
	//$rep->TextWrap(275,895, 100,spacing($_from[2]), "left");
		
		$tin = $myrow['gst_no'];
		print_(68,875,$rep,$tin,11,array(3,6,9),5);
		
		$rdo = $myrow['rdo_code'];
		print_(270,875,$rep,$rdo,14);
		$rep->fontSize -= 2;
		$rep->TextWrap(455,877,145,$myrow['line_of_business'],'left');
$rep->fontSize += 2;
	//	$rep->TextWrap(480,850,92,$myrow['coy_no'],'left');
		$rep->fontSize += 2;
		$rep->TextWrap(473, 850, 100, spacing($myrow['coy_no'], '  '), "left");
		$rep->fontSize -= 2;

		$rep->TextWrap(55,850,380,$myrow['coy_name'],'left');

		$rep->TextWrap(55,826,380,$myrow['postal_address'],'left');
		print_(532,825,$rep,$myrow['zip_code'],13);
		// $rep->TextWrap(472,746,50,$myrow['atc'],'left');
		$rep->fontSize -= 4;	
    $rep->End();
}

?>