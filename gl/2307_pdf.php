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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");

require_once($path_to_root . '/includes/pdf/fpdf/fpdf.php');
require_once($path_to_root . '/includes/pdf/fpdfi/fpdi.php');


if($_GET['supplier_id']!=''){
		$supplier_id = $_GET['supplier_id'];
}
	
if($_GET['from']!=''){
		$from = $_GET['from'];
		$fromx= $_GET['from'];
}

if($_GET['to']!=''){
		$to = $_GET['to'];
		$tox = $_GET['to'];
}


function get_atc_list($from_date, $to_date, $supplier_id)
{
		// $from = date2sql($from_date);
		// $to = date2sql($to_date);
	
		$sql= "SELECT account_code from 0_atc_codes";
		$res = db_query($sql);
		$atc_list = array();

		while ($row = db_fetch($res)) {
			array_push($atc_list,"'".$row[0]."'");
		}
		
		$atc_code_list = implode(",",$atc_list);
		//display_error($sqlx);
		
		$sqlx = "SELECT gl.*, sup.supp_name, sup.gst_no, atc.atc, atc.ewt_rate FROM 0_gl_trans as gl 
		LEFT JOIN 0_suppliers as sup
		ON gl.person_id=sup.supplier_id
		LEFT JOIN 0_atc_codes as atc
		ON gl.account=atc.account_code
		WHERE gl.amount!=0 AND gl.amount<0 
		AND gl.tran_date >= '$from_date'
		AND gl.tran_date <= '$to_date'
		AND gl.person_id='$supplier_id'
		AND gl.account IN ($atc_code_list) ";

		//echo $sqlx;
		return db_query($sqlx, "Failed to get ATC Codes.");
}


//from date
$from=date_create($from);
$from=date_format($from,"j-m-y");
$from_date = explode('-', $from);
$fday = $from_date[0];
$fmonth   = $from_date[1];
$fyear  = $from_date[2];

//to date
$to=date_create($to);
$to=date_format($to,"j-m-y");
$to_date = explode('-', $to);
$tday = $to_date[0];
$tmonth   = $to_date[1];
$tyear  = $to_date[2];






//echo $supplier_id;
$supplier_row = get_supplier($supplier_id);



$gst_no=$supplier_row['gst_no'];
$tin_no = explode('-', $gst_no);
$tno1 = $tin_no[0];
$tno2   = $tin_no[1];
$tno3  = $tin_no[2];
$tno4  = $tin_no[3];

if($tno4==""){
	$tno4='000';
}

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
$pdf->Write(0, $fmonth);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(48, 35);
$pdf->Write(0, $fday);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(57, 35);
$pdf->Write(0, $fyear);


//DATE 2
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(111, 35);
$pdf->Write(0, $tmonth);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(120, 35);
$pdf->Write(0, $tday);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(129, 35);
$pdf->Write(0, $tyear);




//for SUPPLIER TIN NO
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);

// $pdf->SetXY(50, 45);
//$pdf->Write(0, '004-526-681-000');

$pdf->SetXY(46, 45);
$pdf->Write(0, $tno1);

$pdf->SetXY(60, 45);
$pdf->Write(0, $tno2);

$pdf->SetXY(76, 45);
$pdf->Write(0, $tno3);

$pdf->SetXY(93, 45);
$pdf->Write(0, $tno4);

//for SUPP NAME
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(45, 52);
$pdf->Write(0, html_entity_decode($supplier_row['supp_name']));

//for SUPP ADDRESS
$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(45, 60);
$pdf->Write(0, $supplier_row['address']);



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




$atc_res=get_atc_list($fromx, $tox, $supplier_id);


// //PART 2
// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(59, 111);
// $pdf->Write(0, 'WC 160');

// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(77, 111);
// $pdf->Write(0, '28,571.42');

$x_code=59;
$y_code=111;

$x_amount=77;
$y_amount=111;

$x_tamount=162;
$y_tamount=111;

while ($cons_row = db_fetch($atc_res))
{
		
	$pdf->SetFont('Helvetica','',7);
	$pdf->SetTextColor(255, 0, 0);
	$pdf->SetXY($x_code, $y_code);
	$pdf->Write(0, 'WC 160');
	
	$amount1=abs($cons_row['amount']);
	$amount2=abs($cons_row['amount']);

	$pdf->SetFont('Helvetica','',7);
	$pdf->SetTextColor(255, 0, 0);
	$pdf->SetXY($x_amount, $y_amount);
	$pdf->Write(0, $amount1);

	$pdf->SetFont('Helvetica','',7);
	$pdf->SetTextColor(255, 0, 0);
	$pdf->SetXY($x_tamount, $y_tamount);
	$pdf->Write(0, $amount2);

	$y_amount+=4;
	$y_code+=4;
	$y_tamount+=4;
	
	
	$tx_amount1+=$amount1;
	$tx_amount2+=$amount2;
}


// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(98, 111);
// $pdf->Write(0, '28,571.42');

// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(120, 111);
// $pdf->Write(0, '28,571.42');

// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(141, 111);
// $pdf->Write(0, '28,571.42');




//===========================TOTAL
$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(77, 242);
$pdf->Write(0, $tx_amount1);

// $pdf->SetFont('Helvetica','B',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(98, 242);
// $pdf->Write(0, '28,571.42');

// $pdf->SetFont('Helvetica','B',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(120, 242);
// $pdf->Write(0, '28,571.42');

// $pdf->SetFont('Helvetica','B',7);
// $pdf->SetTextColor(255, 0, 0);
// $pdf->SetXY(141, 242);
// $pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(255, 0, 0);
$pdf->SetXY(162, 242);
$pdf->Write(0, $tx_amount2);

$pdf->Output();

?>