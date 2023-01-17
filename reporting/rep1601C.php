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

function print_($_x,$_y,&$rep, $str,$inc=14,$double=array())
{
	$x -= $inc;
	$counter = 0;
	
	for($i=0;$i<strlen($str);$i++)
	{
		$x += $inc;
		
		if (in_array($i, $double))
			$x += $inc;
			// $rep->TextWrap($_x+$x,$_y,100,$str{$i});
		
		$rep->TextWrap($_x+$x,$_y,100,$str{$i});
	}
}

function print_cash_disbursement_book()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
   
   
   $bir = '1601C';
    $rep = new FrontReport(_('1601 C'), $bir, 'legal',9,'P');

	// $rep->addJpegFromFile($path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg',0,1000);
	$filename = $path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg';
		$rep->AddImage($filename, 0, 0, $rep->pageWidth, $rep->pageHeight);
		$myrow = get_company_prefs();
		
		$from_ = explode_date_to_dmy($from);
		$to_ = explode_date_to_dmy($to);
		
		if ($from_[1] != $to_[1] OR $from_[2] != $to_[2])
			die;
		
		$rep->fontSize += 4;
		$mmyyyy = $from_[1].$from_[2];
		print_(122,870,$rep,$mmyyyy);
		
		$tin = $myrow['gst_no'];
		print_(56,839,$rep,$tin,13,array(3,6,9));
		
		$rdo = $myrow['rdo_code'];
		print_(310,839,$rep,$rdo,10);
		
		$rep->TextWrap(420,839-2,104,$myrow['line_of_business'],'left');
		print_(450,808,$rep,$myrow['coy_no'],11);
		$rep->TextWrap(58,808,380,$myrow['coy_name'],'left');
		$rep->TextWrap(58,777,380,$myrow['postal_address'],'left');
		print_(475,777,$rep,$myrow['zip_code'],13);
		$rep->TextWrap(472,746,50,$myrow['atc'],'left');
		$rep->fontSize -= 4;
    $rep->End();
}

?>