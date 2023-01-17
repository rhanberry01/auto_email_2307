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

if($_GET['nt']!=''){
		$nt = $_GET['nt'];
}
if($_GET['supplier_id']!=''){
		$supplier_id = $_GET['supplier_id'];
}
	
if($_GET['type']!=''){
		$type = $_GET['type'];
}

if($_GET['trans_no']!=''){
		$trans_no = $_GET['trans_no'];
}
	
if($_GET['from']!=''){
		$from = $_GET['from'];
		$fromx= $_GET['from'];
}

if($_GET['to']!=''){
		$to = $_GET['to'];
		$tox = $_GET['to'];
}

function get_total_amount($type, $trans_no, $supplier_id) 
{
		$sql= "SELECT account_code from 0_atc_codes";
		$res = db_query($sql);
		$atc_list = array();

		while ($row = db_fetch($res)) {
			array_push($atc_list,"'".$row[0]."'");
		}
		
		$atc_code_list = implode(",",$atc_list);
	
	$sql="SELECT ABS(SUM(gl.amount)) as amount
	FROM 0_gl_trans as gl 
	WHERE gl.amount!=0 AND gl.amount>0 
	AND gl.type = '$type' 
	AND gl.type_no = '$trans_no' 
	AND gl.person_id='$supplier_id'
	AND gl.account NOT IN ('1400',
'1410',
'1410010',
'1410011',
'1410012',
'1410013'
)
	";
	$res=db_query($sql);
	$row=db_fetch($res);
	return $row['amount'];
}

function get_atc_date($type, $trans_no, $supplier_id)
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
		
		$sqlx = "SELECT gl.tran_date FROM 0_gl_trans as gl 
		LEFT JOIN 0_suppliers as sup
		ON gl.person_id=sup.supplier_id
		LEFT JOIN 0_atc_codes as atc
		ON gl.account=atc.account_code
		WHERE gl.amount!=0 AND gl.amount<0 
		AND gl.type = '$type'
		AND gl.type_no = '$trans_no'
		AND gl.person_id='$supplier_id'
		AND gl.account IN ($atc_code_list) ";

		//echo $sqlx;
		return db_query($sqlx, "Failed to get ATC Codes.");
}

function get_atc_list($type, $trans_no, $supplier_id)
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
		
		$sqlx = "SELECT gl.*, sup.supp_name,sup.pay_to, sup.gst_no, atc.atc, atc.ewt_rate FROM 0_gl_trans as gl 
		LEFT JOIN 0_suppliers as sup
		ON gl.person_id=sup.supplier_id
		LEFT JOIN 0_atc_codes as atc
		ON gl.account=atc.account_code
		WHERE gl.amount!=0 AND gl.amount<0 
		AND gl.type = '$type'
		AND gl.type_no = '$trans_no'
		AND gl.person_id='$supplier_id'
		AND gl.account IN ($atc_code_list) ";

		//echo $sqlx;
		return db_query($sqlx, "Failed to get ATC Codes.");
}

$atc_res=get_atc_date($type, $trans_no, $supplier_id);
$date_row= db_fetch($atc_res);
$from1=sql2date($date_row['tran_date']);


$from1=begin_month($from1);
$to1=end_month($from1);



$from1=date_create($from1);
$from1=date_format($from1,"j-m-y");
$from_date1 = explode('-', $from1);
$fday1 = $from_date1[0];
$fmonth1  = $from_date1[1];
$fyear1  = $from_date1[2];

//to date period
$to1=date_create($to1);
$to1=date_format($to1,"j-m-y");
$to_date1 = explode('-', $to1);
$tday1 = $to_date1[0];
$tmonth1   = $to_date1[1];
$tyear1  = $to_date1[2];




// //from date
// $from=date_create($from);
// $from=date_format($from,"j-m-y");
// $from_date = explode('-', $from);
// $fday = $from_date[0];
// $fmonth   = $from_date[1];
// $fyear  = $from_date[2];

// //to date
// $to=date_create($to);
// $to=date_format($to,"j-m-y");
// $to_date = explode('-', $to);
// $tday = $to_date[0];
// $tmonth   = $to_date[1];
// $tyear  = $to_date[2];


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


$source_file = $path_to_root . '/includes/pdf/2307.pdf';
$signature_source_file=$path_to_root . '/includes/pdf/bir_signature2.png';

// initiate FPDI
$pdf = new Fpdi();
// add a page
$pdf->AddPage();
// set the source file
// $pdf->setSourceFile(StreamReader::createByFile($source_file));
$pdf->setSourceFile($source_file);
// import page 1
$tplIdx = $pdf->importPage(1);
// use the imported page and place it at position 10,10 with a width of 100 mm
$pdf->useTemplate($tplIdx, 1, -10, 210);

// now write some text above the imported page

$pdf->Image($signature_source_file,36,240,25);

//for NAME of signatory
$pdf->SetFont('Helvetica','',8);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(37, 250);
$pdf->Write(0, 'Shena O. Matira');

//for TIN
$pdf->SetFont('Helvetica','',8);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(110, 250);
$pdf->Write(0, '199-683-987');


//for Position
$pdf->SetFont('Helvetica','',8);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(165, 250);
$pdf->Write(0, 'Accounting Head');




//DATE 1
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(33, 27);
$pdf->Write(0, $fmonth1);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(43, 27);
$pdf->Write(0, $fday1);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(53, 27);
$pdf->Write(0, $fyear1);

//TO
//DATE 2
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(110, 27);
$pdf->Write(0, $tmonth1);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(120, 27);
$pdf->Write(0, $tday1);

$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(130, 27);
$pdf->Write(0, $tyear1);


//for SUPPLIER TIN NO
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);

// $pdf->SetXY(50, 45);
//$pdf->Write(0, '004-526-681-000');

$pdf->SetXY(39, 38);
$pdf->Write(0, $tno1);

$pdf->SetXY(54, 38);
$pdf->Write(0, $tno2);

$pdf->SetXY(73, 38);
$pdf->Write(0, $tno3);

$pdf->SetXY(91, 38);
$pdf->Write(0, $tno4);

//for SUPP NAME
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(39, 45);

if($supplier_row['pay_to']!=""){
	if($nt == 1)
		$pdf->Write(0, html_entity_decode($supplier_row['supp_name']));
	else
		$pdf->Write(0, html_entity_decode($supplier_row['pay_to']));
}
else {
	
	$supp_name=$supplier_row['supp_name'];

	$start_from_name= strpos($supp_name,"(");
	$final_supp_name= substr_replace($supp_name,"",$start_from_name);

	$pdf->Write(0, html_entity_decode($final_supp_name));
}


//for SUPP ADDRESS
$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(39, 54);
$pdf->Write(0, $supplier_row['address']);


//=================PAYOR

//for PAYOR TIN NO
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);

// $pdf->SetXY(50, 45);
//$pdf->Write(0, '004-526-681-000');

$pdf->SetXY(39, 71);
$pdf->Write(0, '007');

$pdf->SetXY(55, 71);
$pdf->Write(0, '492');

$pdf->SetXY(73, 71);
$pdf->Write(0, '840');

$pdf->SetXY(91, 71);
$pdf->Write(0, '000');


//for PAYOR NAME
$pdf->SetFont('Helvetica','',10);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(39, 77);
$pdf->Write(0, 'SAN ROQUE SUPERMARKET RETAIL SYSTEMS INC.');

//for PAYOR ADDRESS
$pdf->SetFont('Helvetica','',7);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(39, 87);
$pdf->Write(0, 'Dumalay St. cor., Quirino Highway Brgy. Sta Monica Novaliches Quezon City');


$atc_res=get_atc_list($type, $trans_no, $supplier_id);


// //PART 2
// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(0, 50, 119);
// $pdf->SetXY(59, 111);
// $pdf->Write(0, 'WC 160');

// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(0, 50, 119);
// $pdf->SetXY(77, 111);
// $pdf->Write(0, '28,571.42');

$x_code=59;
$y_code=111;


//========================================================quarter positioning
//$fmonth=3;
if ($fmonth1=='2' or $fmonth1=='5' or $fmonth1=='8' or $fmonth1=='11') {
	$xq_amount=98;
}
else if($fmonth1=='3' or $fmonth1=='6' or $fmonth1=='9' or $fmonth1=='12'){
	$xq_amount=122;
}
else{
	$xq_amount=87;
}



$yq_amount=111;


$x_amount=141;
$y_amount=111;

$x_tamount=163;
$y_tamount=111;

while ($cons_row = db_fetch($atc_res))
{
		
	$pdf->SetFont('Helvetica','',7);
	$pdf->SetTextColor(0, 50, 119);
	$pdf->SetXY(56, 109);
	$pdf->Write(0, $cons_row['atc']);
	
	//$amount1=abs($cons_row['amount']);
	$amount2=abs($cons_row['amount']);
	
	$amount1=get_total_amount($type, $trans_no, $supplier_id);
	
	$pdf->SetFont('Helvetica','',7);
	$pdf->SetTextColor(20, 50, 119);
	$pdf->SetXY($xq_amount, 109);
	$pdf->Write(0, number_format($amount1,2));

	$pdf->SetFont('Helvetica','',7);
	$pdf->SetTextColor(0, 50, 119);
	$pdf->SetXY(145, 109);
	$pdf->Write(0, number_format($amount1,2));

	$pdf->SetFont('Helvetica','',7);
	$pdf->SetTextColor(0, 50, 119);
	$pdf->SetXY(169, 109);
	$pdf->Write(0, number_format($amount2,2));

	$y_amount+=4;
	$y_code+=4;
	$y_tamount+=4;
	
	
	$tx_amount1+=$amount1;
	$tx_amount2+=$amount2;
}


// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(0, 50, 119);
// $pdf->SetXY(98, 111);
// $pdf->Write(0, '28,571.42');

// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(0, 50, 119);
// $pdf->SetXY(120, 111);
// $pdf->Write(0, '28,571.42');

// $pdf->SetFont('Helvetica','',7);
// $pdf->SetTextColor(0, 50, 119);
// $pdf->SetXY(141, 111);
// $pdf->Write(0, '28,571.42');


//===========================TOTAL
$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY($xq_amount, 238);
$pdf->Write(0, number_format($tx_amount1,2));

// $pdf->SetFont('Helvetica','B',7);
// $pdf->SetTextColor(0, 50, 119);
// $pdf->SetXY(98, 242);
// $pdf->Write(0, '28,571.42');

// $pdf->SetFont('Helvetica','B',7);
// $pdf->SetTextColor(0, 50, 119);
// $pdf->SetXY(120, 242);
// $pdf->Write(0, '28,571.42');

$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(145, 238);
$pdf->Write(0, number_format($tx_amount1,2));

$pdf->SetFont('Helvetica','B',7);
$pdf->SetTextColor(0, 50, 119);
$pdf->SetXY(170, 238);
$pdf->Write(0, number_format($tx_amount2,2));

$pdf->Output();

?>