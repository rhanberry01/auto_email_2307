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

function print_cash_disbursement_book()
{
	global $path_to_root;
	
	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
   
   
   $bir = '2307';
    $rep = new FrontReport(_('2307'), $bir, 'legal',9,'P');

	// $rep->addJpegFromFile($path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg',0,1000);
	$filename = $path_to_root.'/reporting/BIR/includes/'.$bir.'.jpg';
		$rep->AddImage($filename, 0, 0, $rep->pageWidth, $rep->pageHeight);
    $rep->End();
}

?>