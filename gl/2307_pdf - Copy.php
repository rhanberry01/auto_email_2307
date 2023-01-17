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

$path_to_root = "..";

require_once($path_to_root . '/includes/pdf/fpdf/fpdf.php');
require_once($path_to_root . '/includes/pdf/fpdfi/fpdi.php');

$source_file=$path_to_root . '/includes/pdf/2307.pdf';

// initiate FPDI
$pdf = new Fpdi();
// add a page
$pdf->AddPage();
// set the source file
$pdf->setSourceFile($source_file);
// import page 1
$tplIdx = $pdf->importPage(1);
// use the imported page and place it at position 10,10 with a width of 100 mm
$pdf->useTemplate($tplIdx, 8, -20, 210);

// now write some text above the imported page


//DATE 1
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(40, 35);
$pdf->Write(0, '01');

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(48, 35);
$pdf->Write(0, '01');

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(57, 35);
$pdf->Write(0, '18');


//DATE 2
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(111, 35);
$pdf->Write(0, '03');

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(120, 35);
$pdf->Write(0, '31');

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(129, 35);
$pdf->Write(0, '18');




//for SUPPLIER TIN NO
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);

// $pdf->SetXY(50, 45);
//$pdf->Write(0, '004-526-681-000');

$pdf->SetXY(46, 45);
$pdf->Write(0, '004');

$pdf->SetXY(60, 45);
$pdf->Write(0, '526');

$pdf->SetXY(76, 45);
$pdf->Write(0, '681');

$pdf->SetXY(93, 45);
$pdf->Write(0, '000');

//for SUPP NAME
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(45, 52);
$pdf->Write(0, 'GARDENIA BAKERIES INC.');

//for SUPP ADDRESS
$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(45, 60);
$pdf->Write(0, '68 12TH ST NEW MANILA, QUEZON CITY');



//=================PAYOR

//for PAYOR TIN NO
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);

// $pdf->SetXY(50, 45);
//$pdf->Write(0, '004-526-681-000');

$pdf->SetXY(46, 75);
$pdf->Write(0, '004');

$pdf->SetXY(60, 75);
$pdf->Write(0, '526');

$pdf->SetXY(76, 75);
$pdf->Write(0, '681');

$pdf->SetXY(93, 75);
$pdf->Write(0, '000');


//for PAYOR NAME
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(45, 82);
$pdf->Write(0, 'SAN ROQUE SUPERMARKET RETAIL SYSTEMS INC.');

//for PAYOR ADDRESS
$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(45, 90);
$pdf->Write(0, 'Dumalay St. cor., Quirino Highway Brgy. Sta Monica Novaliches Quezon City');

//PART 2
$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(59, 111);
$pdf->Write(0, 'WC 160');

$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(77, 111);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(98, 111);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(120, 111);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(141, 111);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(162, 111);
$pdf->Write(0, '28,571.42');


//===========================TOTAL
$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(77, 242);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(98, 242);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(120, 242);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(141, 242);
$pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(162, 242);
$pdf->Write(0, '28,571.42');

$pdf->Output();

?>