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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Audit Trail
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);


//----------------------------------------------------------------------------------------------------

print_report();
//----------------------------------------------------------------------------------------------------

function print_report()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $t_nt = $_POST['PARAM_2']; //trade 1  nontrade 0 all 2
	$rba = $_POST['PARAM_3']; // 1-retail 0-belen 2-all
	
	include_once($path_to_root . "/reporting/includes/excel_report.inc");

    $dec = user_price_dec();

	//==================================================== header
		$ap_account = get_company_pref('creditors_act'); //2000
		$h = 'For AP Trade/Non-Trade';

    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Cash Receipts Book'), "CashReceiptsBook", "LETTER");

    $rep->Font();
	
	$rep->sheet->setColumn(0,0,15);
	$rep->sheet->setColumn(0,2,25);
	$rep->sheet->setColumn(0,2,25);
	$rep->sheet->setColumn(3,3,10);
	$rep->sheet->setColumn(4,4,13);
	$rep->sheet->setColumn(5,6,10);
	$rep->sheet->setColumn(7,7,12);
	$rep->sheet->setColumn(8,9,13);
	$rep->sheet->setColumn(9,9,13);
	$rep->sheet->setColumn(10,count($c_header),18);
	
	$com = get_company_prefs();
	
	$format_bold_title =& $rep->addFormat();
	$format_bold_title->setTextWrap();
	$format_bold_title->setBold();
	$format_bold_title->setAlign('center');
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
	$format_bold_right =& $rep->addFormat();
	$format_bold_right->setBold();
	$format_bold_right->setAlign('right');

	$format_bold_center =& $rep->addFormat();
	$format_bold_center->setBold();
	$format_bold_center->setAlign('center');
	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'CASH RECEIPTS BOOK', $format_bold);
	$rep->y ++;

	$rep->sheet->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet->setMerge(0,0,0,3);
	// $rep->sheet->setMerge(1,0,1,3);
	// $rep->sheet->setMerge(2,0,2,3);
	// $rep->sheet->setMerge(4,0,4,3);
	// $rep->sheet->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('DATE','PAYEE');

	array_push($c_header, 'PARTICULARS', 'OR #');
	//array_push($c_header, 'PARTICULARS', 'OR #', 'CASH IN BANK', 'ACCOUNTS PAYABLE');
	// array_push($c_header, 'CREDITABLE WITHHOLDING TAX','OUTPUT TAX');
	$c_header_last_index = count($c_header)-1;

	array_push($c_header,'10102299','1020021','2470','1400','2310', '4020','4020010','4020020','4020025','4020030','4020050','4020051','4021','4030');

	$in_header=array('10102299','1020021','2470','1400','2310','4020','4020010','4020020','4020025','4020030','4020050','4020051','4021','4030');
	
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	// $sql = "select 'malabon' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
// SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
// od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
// oh.bd_official_receipt
// from srs_aria_malabon.0_other_income_payment_header as oh 
// left join srs_aria_malabon.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
// where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
// and od.bd_det_date>='".date2sql($from)."' and od.bd_det_date<='".date2sql($to)."'
// and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
// GROUP BY oh.bd_official_receipt
// ORDER BY bd_official_receipt

// ";

//and od.bd_det_date>='".date2sql($from)."' and od.bd_det_date<='".date2sql($to)."'

	$sql = "
select 'sri' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_imus.0_other_income_payment_header as oh 
left join srs_aria_imus.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsn' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_nova.0_other_income_payment_header as oh 
left join srs_aria_nova.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsnav' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_navotas.0_other_income_payment_header as oh 
left join srs_aria_navotas.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srst' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_tondo.0_other_income_payment_header as oh 
left join srs_aria_tondo.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsc' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_camarin.0_other_income_payment_header as oh 
left join srs_aria_camarin.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsant1' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_antipolo_quezon.0_other_income_payment_header as oh 
left join srs_aria_antipolo_quezon.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsant2' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_antipolo_manalo.0_other_income_payment_header as oh 
left join srs_aria_antipolo_manalo.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsm' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_malabon.0_other_income_payment_header as oh 
left join srs_aria_malabon.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsg' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_gala.0_other_income_payment_header as oh 
left join srs_aria_gala.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srscain' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_cainta.0_other_income_payment_header as oh 
left join srs_aria_cainta.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsmr' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_malabon_rest.0_other_income_payment_header as oh 
left join srs_aria_malabon_rest.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL


select 'srsval' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_valenzuela.0_other_income_payment_header as oh 
left join srs_aria_valenzuela.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsbsl' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_b_silang.0_other_income_payment_header as oh 
left join srs_aria_b_silang.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL


select 'srspun' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_punturin_val.0_other_income_payment_header as oh 
left join srs_aria_punturin_val.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srspat' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_pateros.0_other_income_payment_header as oh 
left join srs_aria_pateros.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srscom' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_comembo.0_other_income_payment_header as oh 
left join srs_aria_comembo.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srscain2' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_cainta_san_juan.0_other_income_payment_header as oh 
left join srs_aria_cainta_san_juan.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srssanp' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_san_pedro.0_other_income_payment_header as oh 
left join srs_aria_san_pedro.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srstu' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_talon_uno.0_other_income_payment_header as oh 
left join srs_aria_talon_uno.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsal' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo, oh.bd_trans_type, oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_alaminos.0_other_income_payment_header as oh 
left join srs_aria_alaminos.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsret' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo, oh.bd_trans_type, oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_retail.0_other_income_payment_header as oh 
left join srs_aria_retail.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsbgb' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo, oh.bd_trans_type, oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_bagumbong.0_other_income_payment_header as oh 
left join srs_aria_bagumbong.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt
	
ORDER BY bd_official_receipt

	
	";


	$res = db_query($sql);
//display_error($sql); die;
	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		//display_error($row['branch']);
		//switch_connection_to_branch_mysql($row['branch']);			
		
		
		$sql_ = "SELECT aria_db from transfers.0_branches where code='".$row['branch']."'";
		//display_error($sql);
		$result_=db_query($sql_);
		$row_ = db_fetch($result_);
		$aria_db=$row_['aria_db'];
		
		if($row['branch']=='srsret'){
			$aria_db='srs_aria_retail';
		}
		
		
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		$gl_[$row['bd_det_gl_code']] = $row['bd_oi'];
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = sql2date($row['bd_or_date']);
		$c_details[$count][1] = $row['bd_payee'];
		$last_index = 1;

		$c_details[$count][$last_index+1] = $row['bd_memo'];
		$c_details[$count][$last_index+2] = $row['bd_official_receipt'];
		// if($row['bd_payment_type_id']!='3'){
			// $c_details[$count][$last_index+3] = $row['bd_amount'];
		// }
		
		
		$c_details[$count][$last_index+4] = 0; //dm metrobank
		// $c_details[$count][$last_index+5] = $row['bd_wt'];
		// $c_details[$count][$last_index+6] = $row['bd_vat'];
		
		
		$trans_no='';
		$sqlx= "SELECT * FROM $aria_db.0_other_income_payment_header where bd_official_receipt='".$row['bd_official_receipt']."'";
		$resx=db_query($sqlx);
		display_error($sqlx);

		while($rowx = db_fetch($resx))
		{
		$trans_no[]=$rowx['bd_trans_no'];
		}

		
									$sql2 = "SELECT 
									counter,
									type,
									type_no,
									tran_date,
									account,
									memo_,
									sum(amount) as amount,
									person_id
									FROM $aria_db.0_gl_trans WHERe type = 2
									AND type_no IN(".implode(',',$trans_no).")
									AND amount!=0
									AND account!='1010'
									GROUP BY account
									";						
									
									// $sql2 = "SELECT *
									// FROM 0_gl_trans WHERE type = 20
									// AND type_no IN (21987)
									// ";
									display_error($sql2);
									$res2 = db_query($sql2);
									while($row2 = db_fetch($res2))
									{
										$gl_[$row2['account']]= array("account"=>$row2['account'],"amount"=>$row2['amount']);
									}
											foreach($gl_ as $ind => $amt)
											{		
														$indx = array_search($ind, $c_header);
														if ($indx == false)
														{
															
															$indw = array_search($ind, $in_header);
															if ($indw == true)
															{
																//$c_header[] = $ind;
																end($c_header);         // move the internal pointer to the end of the array
																$indx = key($c_header); // fetches the key of the element pointed to by the internal pointer
															}
															else{
																$c_details1[$count][$ind]=$amt['amount'];
																$c_totals1[$indw]+=$amt['amount'];
															}				
														}						
														$c_details[$count][$indx]+=$amt['amount'];
														$c_totals[$indx]+=$amt['amount'];	
									
											}

										$gl_='';
										$last_check = $row['type_no'];
										
				$c_details[$count][0] = sql2date($row['bd_or_date']);
		

	}
	
	set_global_connection_branch();
		//var_dump($c_totals1);die;
			//var_dump($gl_);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		$rep->sheet->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
		$c_header_last_index = count($c_header)-1;
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	$x = $ind++;
	$rep->sheet->writeString($rep->y, $ind, 'SUNDRIES', $format_bold_title);
	$ind++;
	$rep->sheet->writeString($rep->y, $ind, 'DR', $format_bold_title);
	$ind++;
	$rep->sheet->writeString($rep->y, $ind, 'CR', $format_bold_title);
	//var_dump($c_details); die;
	//var_dump($c_details1); die;
	
	foreach($c_details as $i => $details)
	{	
		$rep->y ++;
		foreach($details as $index => $det)
		{		
	
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet->writeString($rep->y, $index, $det, $rep->formatLeft);
				}

		}	
		
		foreach($c_details1 as $i1 => $details1)
		{	
			if($i1 == $i)
			{		
				foreach($details1 as $index1 => $det1)
				{
					$a = $x;
					$a++;
					$rep->sheet->writeString($rep->y, $a, html_entity_decode(get_gl_account_name($index1)), $rep->formatLeft);
					$a++;
					if($det1 >= 0){
						$rep->sheet->writeNumber($rep->y, $a, $det1, $rep->formatLeft);
					}else{
						$a++;
						$rep->sheet->writeNumber($rep->y, $a, abs($det1), $rep->formatLeft);
					}
					$rep->y++;
				
				}
			}
		//	echo $c_header[$index].'<br>';
		}	
	}
	
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'TOTAL', $rep->formatLeft);
	$qwert = $rep->y;
	foreach ($c_totals as $ind => $total)
	{
		$rep->sheet->writeNumber ($rep->y, $ind, $total, $format_bold_right);
		
	}

	$rep->y++;
	
		$rep->sheet2 = $rep->addWorksheet('Summary');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet2->setColumn(0,0,15);
	$rep->sheet2->setColumn(0,1,12);
	$rep->sheet2->setColumn(0,2,12);
	$rep->sheet2->setColumn(3,3,10);
	$rep->sheet2->setColumn(4,4,50);
	$rep->sheet2->setColumn(5,6,10);
	$rep->sheet2->setColumn(7,7,12);
	$rep->sheet2->setColumn(8,9,13);
	$rep->sheet2->setColumn(9,9,13);
	//$rep->sheet->setColumn(10,count($c_header),18);
	
	$com = get_company_prefs();
	
	$format_bold_title =& $rep->addFormat();
	$format_bold_title->setTextWrap();
	$format_bold_title->setBold();
	$format_bold_title->setAlign('center');
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
	$format_bold_right =& $rep->addFormat();
	$format_bold_right->setBold();
	$format_bold_right->setAlign('right');

	$format_bold_center =& $rep->addFormat();
	$format_bold_center->setBold();
	$format_bold_center->setAlign('center');
	
	
	$rep->y = 0;
	$rep->sheet2->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet2->writeString($rep->y, 0, 'CASH DISBURSEMENT BOOK', $format_bold);
	$rep->y ++;

	$rep->sheet2->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet2->setMerge(0,0,0,3);
	// $rep->sheet2->setMerge(1,0,1,3);
	// $rep->sheet2->setMerge(2,0,2,3);
	// $rep->sheet2->setMerge(4,0,4,3);
	// $rep->sheet2->setMerge(7,0,7,3);
//===================================================================================
	$rep->y ++;
	$rep->y ++;
	// get the header
	$c_header = array('ACCOUNTS','DEBIT','CREDIT');
	//$c_header = array('BRANCH','MEMO','TRANS DATE','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	
	$sql = "
select 'sri' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_imus.0_other_income_payment_header as oh 
left join srs_aria_imus.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsn' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_nova.0_other_income_payment_header as oh 
left join srs_aria_nova.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsnav' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_navotas.0_other_income_payment_header as oh 
left join srs_aria_navotas.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srst' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_tondo.0_other_income_payment_header as oh 
left join srs_aria_tondo.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsc' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_camarin.0_other_income_payment_header as oh 
left join srs_aria_camarin.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsant1' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_antipolo_quezon.0_other_income_payment_header as oh 
left join srs_aria_antipolo_quezon.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsant2' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_antipolo_manalo.0_other_income_payment_header as oh 
left join srs_aria_antipolo_manalo.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsm' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_malabon.0_other_income_payment_header as oh 
left join srs_aria_malabon.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsg' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_gala.0_other_income_payment_header as oh 
left join srs_aria_gala.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srscain' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_cainta.0_other_income_payment_header as oh 
left join srs_aria_cainta.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsmr' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_malabon_rest.0_other_income_payment_header as oh 
left join srs_aria_malabon_rest.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL


select 'srsval' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_valenzuela.0_other_income_payment_header as oh 
left join srs_aria_valenzuela.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsbsl' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_b_silang.0_other_income_payment_header as oh 
left join srs_aria_b_silang.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL


select 'srspun' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_punturin_val.0_other_income_payment_header as oh 
left join srs_aria_punturin_val.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srspat' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_pateros.0_other_income_payment_header as oh 
left join srs_aria_pateros.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srscom' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_comembo.0_other_income_payment_header as oh 
left join srs_aria_comembo.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srscain2' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_cainta_san_juan.0_other_income_payment_header as oh 
left join srs_aria_cainta_san_juan.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srssanp' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_san_pedro.0_other_income_payment_header as oh 
left join srs_aria_san_pedro.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srstu' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo,oh.bd_trans_type,oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_talon_uno.0_other_income_payment_header as oh 
left join srs_aria_talon_uno.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt


UNION ALL

select 'srsal' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo, oh.bd_trans_type, oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_alaminos.0_other_income_payment_header as oh 
left join srs_aria_alaminos.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt

UNION ALL

select 'srsret' as branch, SUM(oh.bd_amount) as bd_amount, SUM(oh.bd_oi) as bd_oi, oh.bd_trans_no,
SUM(oh.bd_wt) as bd_wt,SUM(oh.bd_vat) as bd_vat,
od.bd_det_gl_code, oh.bd_or_date, oh.bd_payee, oh.bd_memo, oh.bd_trans_type, oh.bd_payment_type_id,
oh.bd_official_receipt
from srs_aria_retail.0_other_income_payment_header as oh 
left join srs_aria_retail.0_other_income_payment_details as od on oh.bd_trans_no = od.bd_det_trans_no 
where oh.bd_or_date>='".date2sql($from)."' and oh.bd_or_date<='".date2sql($to)."'
and oh.bd_official_receipt!='' and oh.bd_official_receipt!=0
GROUP BY oh.bd_official_receipt
	
ORDER BY bd_official_receipt

	
	";
//display_error($sql); die;
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;
	//================To count total header, then add the sundries part
	// while($row2 = db_fetch($res2))
	// {
		// $gl_2[$row2['bd_det_gl_code']] = $row2['bd_oi'];
		// if (in_array($row2['bd_det_gl_code'], $c_header2)){
		
						// foreach($gl_2 as $ind2 => $amt2)
						// {
								// $indx2 = array_search($ind2, $c_header2);
								// if ($indx2 == false)
								// {
									// $c_header2[] = $ind2;
								// }
								
						// }
		// }
	// }
	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$sql_ = "SELECT aria_db from transfers.0_branches where code='".$row['branch']."'";
		//display_error($sql);
		$result_=db_query($sql_);
		$row_ = db_fetch($result_);
		$aria_db=$row_['aria_db'];
		
		if($row['branch']=='srsret'){
			$aria_db='srs_aria_retail';
		}
		
		$trans_no='';
		$sqlx= "SELECT * FROM $aria_db.0_other_income_payment_header where bd_official_receipt='".$row['bd_official_receipt']."'";
		$resx=db_query($sqlx);
		display_error($sqlx);

		while($rowx = db_fetch($resx))
		{
		$trans_no[]=$rowx['bd_trans_no'];
		}
		
									$sql2 = "SELECT account,SUM(amount) as amount FROM (SELECT 
									counter,
									type,
									type_no,
									tran_date,
									account,
									memo_,
									sum(amount) as amount,
									person_id
									FROM $aria_db.0_gl_trans WHERe type = 2
									AND type_no IN(".implode(',',$trans_no).")
									AND amount!=0
									AND account!='1010'
									GROUP BY account) as a GROUP BY account
									";						
									
									display_error($sql2);
									$res2 = db_query($sql2);
									// while($row2 = db_fetch($res2))
									// {
										// $gl_[$row2['account']]= array("account"=>$row2['account'],"amount"=>$row2['amount']);
									// }
	
	
				while($row2 = db_fetch($res2))
				{
					$count ++;

					$date_to_ = explode_date_to_dmy($to);

					$c_details[$count][0] = get_gl_account_name($row2['account']);
					
					if($row2['amount']>0){
						$c_details[$count][1] = ROUND($row2['amount'],2);
						$c_details[$count][2] = 0;
						$total_debit+=ROUND($row2['amount'],2);
					}
					
					if($row2['amount']<0){
						$c_details[$count][1] = 0;
						$c_details[$count][2] = ROUND(abs($row2['amount']),2);
						$total_credit+=ROUND(abs($row2['amount']),2);
					}
			
				}

	}
		//print_r($type_no); die;
		
	//	$trans_number = implode(',',$type_no);
		
		//display_error($trans_number); die;
	
	



	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){

		$rep->sheet2->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	//print_r(c_details);die;
	
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet2->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet2->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				}
				else{
					$rep->sheet2->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
		}	
	}

	$rep->y ++;
	$rep->sheet2->writeString($rep->y, 0, 'GRAND TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet2->writeNumber ($rep->y, 1, $total_debit, $format_bold_right);
		$rep->sheet2->writeNumber ($rep->y, 2, abs($total_credit), $format_bold_right);
		
	//}

	$rep->y++;
	
	
    $rep->End();
}

?>