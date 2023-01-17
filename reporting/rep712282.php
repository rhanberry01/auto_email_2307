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
$page_security = 'SA_BANKREP';
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
include_once($path_to_root . "/includes/ui/ui_lists.inc");

//----------------------------------------------------------------------------------------------------

print_report();
//----------------------------------------------------------------------------------------------------
function get_particulars($cv_id)
{
	$sql = "SELECT * FROM ".TB_PREF."cv_details
			WHERE cv_id = '$cv_id'
			AND trans_type = '20'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return html_entity_decode(get_comments_string($row['trans_type'], $row['trans_no']));
}

function get_cv_gl_details($cv_id)
{
	$sql = "SELECT trans_type, trans_no
			FROM ".TB_PREF."cv_details
			WHERE cv_id = '$cv_id' ";
	$res = db_query($sql,'fail 1');
	
	$invoices = $where_ = array();
	
	if(db_num_rows($res) == 0)
		return false;
	
	while($row = db_fetch($res))
	{
		$where_[] = "(type = " . $row['trans_type'] ." AND type_no = " . $row['trans_no'] . ")";
		
		// get invoices
		if ($row['trans_type'] == 20)
		{
			$tran_det = get_tran_details($row['trans_type'], $row['trans_no']);
			if ($tran_det['supp_reference'] != ''){
				$apv[] = $tran_det['reference'];
				$invoices[] = $tran_det['supp_reference'];
				
			}
				
		}
	}
			// AND account NOT IN (".get_company_pref('creditors_act').",".get_company_pref('creditors_act_nt').",".
				// get_company_pref('purchase_vat').",".get_company_pref('purchase_non_vat').") 
	$where = implode(' OR ',$where_);
	$sql = "SELECT account , SUM(amount) FROM `0_gl_trans`
			WHERE amount != 0
			AND ($where)
			GROUP BY account
			HAVING SUM(amount) != 0";
	// display_error($sql);die;
	$res = db_query($sql,'fail 2');
	
	
	$ret = array();
	while ($row = db_fetch($res))
	{
		if (!is_bank_account($row[0]) OR (is_bank_account($row[0]) AND $row[1] > 0))
		$ret[$row[0]] = round2($row[1],2);
	}
	
	return array($ret,$invoices,$apv);
}


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

      $rep = new FrontReport(_('UNRECORDED'), "BPI_BankRecon_CR_DR_CARD", "LETTER");

    $rep->Font();
	
	$rep->sheet->setColumn(0,0,15);
	$rep->sheet->setColumn(0,1,12);
	$rep->sheet->setColumn(0,2,12);
	$rep->sheet->setColumn(3,3,10);
	$rep->sheet->setColumn(4,4,50);
	$rep->sheet->setColumn(5,6,10);
	$rep->sheet->setColumn(7,7,12);
	$rep->sheet->setColumn(8,9,13);
	$rep->sheet->setColumn(9,9,13);
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
	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'BPI Bank Recon (Credit/Debit Card)', $format_bold);
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
	$c_header = array('BRANCH','REFRENCE', 'DATE','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	
	// array_push($c_header,
	
// '10102299'
	// );
		

// '2000011',
// '2000012',
// '2000099',
// '2000100',
// '2000101',
// '2000102',
// '2000103',
// '2000104',
// '2000105'

	
	//print_r($c_header);
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	$sql="
	SELECT * FROM cash_deposit.0_bank_statement_bpi
	where date_deposited>='".date2sql($from)."' and date_deposited<='".date2sql($to)."'
	and credit_amount!=0
	and cleared=0
	";
		
	//display_error($sql); die;
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	// if (db_num_rows($res) == 0)
		// return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = $row['branch_code'];
		$c_details[$count][1] = $row['deposit_type'];
		$c_details[$count][2] = sql2date($row['date_deposited']);
		$c_details[$count][3] = $row['credit_amount'];
		$c_details[$count][4] = 'NOT IN ARIA';
		
		$total1+=$row['credit_amount'];			
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
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

	}

	$rep->y ++;
	$rep->sheet->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet->writeNumber ($rep->y, 3, $total1, $format_bold_right);
		
	//}


	$rep->y++;
	
			$rep->sheet3 = $rep->addWorksheet('NOT IN BPI BANK STATEMENT');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet3->setColumn(0,0,15);
	$rep->sheet3->setColumn(0,1,12);
	$rep->sheet3->setColumn(0,2,12);
	$rep->sheet3->setColumn(3,3,10);
	$rep->sheet3->setColumn(4,4,50);
	$rep->sheet3->setColumn(5,6,10);
	$rep->sheet3->setColumn(7,7,12);
	$rep->sheet3->setColumn(8,9,13);
	$rep->sheet3->setColumn(9,9,13);
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
	$rep->sheet3->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet3->writeString($rep->y, 0, 'BPI Bank Recon (Credit/Debit Card)', $format_bold);
	$rep->y ++;

	$rep->sheet3->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet3->setMerge(0,0,0,3);
	// $rep->sheet3->setMerge(1,0,1,3);
	// $rep->sheet3->setMerge(2,0,2,3);
	// $rep->sheet3->setMerge(4,0,4,3);
	// $rep->sheet3->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','MEMO','TRANS DATE','AMOUNT','STATUS','REMITTANCE DATE','AMOUNT');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	
	// array_push($c_header,
	
// '10102299'
	// );
		

// '2000011',
// '2000012',
// '2000099',
// '2000100',
// '2000101',
// '2000102',
// '2000103',
// '2000104',
// '2000105'

	
	//print_r($c_header);
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	

$sql="
SELECT 'srsal' as br_code, 'alaminos' as branch, ad.*,gl.amount as amount FROM srs_aria_alaminos.`0_gl_trans` as gl 
LEFT JOIN srs_aria_alaminos.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id



UNION ALL

SELECT 'srsant2' as br_code,'manalo' as branch,ad.*,gl.amount as amount FROM srs_aria_antipolo_manalo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_antipolo_manalo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL


SELECT 'srsant1' as br_code,'quezon' as branch,ad.*,gl.amount as amount FROM srs_aria_antipolo_quezon.`0_gl_trans` as gl 
LEFT JOIN srs_aria_antipolo_quezon.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsbsl' as br_code,'bsilang' as branch,ad.*,gl.amount as amount FROM srs_aria_b_silang.`0_gl_trans` as gl 
LEFT JOIN srs_aria_b_silang.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsbgb' as br_code,'bagumbong' as branch,ad.*,gl.amount as amount FROM srs_aria_bagumbong.`0_gl_trans` as gl 
LEFT JOIN srs_aria_bagumbong.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srscain' as br_code,'cainta' as branch,ad.*,gl.amount as amount FROM srs_aria_cainta.`0_gl_trans` as gl 
LEFT JOIN srs_aria_cainta.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL


SELECT 'srscain2' as br_code,'cainta2' as branch,ad.*,gl.amount as amount FROM srs_aria_cainta_san_juan.`0_gl_trans` as gl 
LEFT JOIN srs_aria_cainta_san_juan.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsc' as br_code,'camarin' as branch,ad.*,gl.amount as amount FROM srs_aria_camarin.`0_gl_trans` as gl 
LEFT JOIN srs_aria_camarin.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srscom' as br_code,'comembo' as branch,ad.*,gl.amount as amount FROM srs_aria_comembo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_comembo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsg' as br_code,'gagalangin' as branch,ad.*,gl.amount as amount FROM srs_aria_gala.`0_gl_trans` as gl 
LEFT JOIN srs_aria_gala.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'sri' as br_code,'imus' as branch,ad.*,gl.amount as amount FROM srs_aria_imus.`0_gl_trans` as gl 
LEFT JOIN srs_aria_imus.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsm' as br_code,'malabon' as branch,ad.*,gl.amount as amount FROM srs_aria_malabon.`0_gl_trans` as gl 
LEFT JOIN srs_aria_malabon.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsmr' as br_code,'resto malabon' as branch,ad.*,gl.amount as amount FROM srs_aria_malabon_rest.`0_gl_trans` as gl 
LEFT JOIN srs_aria_malabon_rest.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsnav' as br_code,'navotas' as branch,ad.*,gl.amount as amount FROM srs_aria_navotas.`0_gl_trans` as gl 
LEFT JOIN srs_aria_navotas.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsn' as br_code,'nova' as branch,ad.*,gl.amount as amount FROM srs_aria_nova.`0_gl_trans` as gl 
LEFT JOIN srs_aria_nova.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srspat' as br_code,'pateros' as branch,ad.*,gl.amount as amount FROM srs_aria_pateros.`0_gl_trans` as gl 
LEFT JOIN srs_aria_pateros.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srspun' as br_code,'punturin' as branch,ad.*,gl.amount as amount FROM srs_aria_punturin_val.`0_gl_trans` as gl 
LEFT JOIN srs_aria_punturin_val.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srssanp' as br_code,'san pedro' as branch,ad.*,gl.amount as amount FROM srs_aria_san_pedro.`0_gl_trans` as gl 
LEFT JOIN srs_aria_san_pedro.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srstu' as br_code, 'talon uno' as branch,ad.*,gl.amount as amount FROM srs_aria_talon_uno.`0_gl_trans` as gl 
LEFT JOIN srs_aria_talon_uno.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srst' as br_code,'tondo' as branch,ad.*,gl.amount as amount FROM srs_aria_tondo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_tondo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsval' as br_code,'valenzuela' as branch,ad.*,gl.amount as amount FROM srs_aria_valenzuela.`0_gl_trans` as gl 
LEFT JOIN srs_aria_valenzuela.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
and reconciled=0
GROUP BY ad.p_ref_id

";

	
	
//display_error($sql); die;
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	// if (db_num_rows($res) == 0)
		// return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = $row['branch'];
		$c_details[$count][1] = $row['bd_memo'];
		$c_details[$count][2] = sql2date($row['date_paid']);
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'NOT IN BANK STATEMENT';
		$c_details[$count][5] = sql2date($row['p_remittance_date']);
		
		$total3+=$row['amount'];		
		
		
		switch_connection_to_branch_mysql($row['br_code']);
		
		$sqlx = "SELECT amount
		FROM 0_gl_trans WHERE type = 60
		and tran_date='".$row['p_remittance_date']."'
		AND account = '1451002'
		AND amount!=0";
		display_error($sqlx);

		$resx = db_query($sqlx);
		$rowx = db_fetch($resx);
		$c_details[$count][6] = $rowx['amount'];
		set_global_connection_branch();
		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet3->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet3->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet3->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet3->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet3->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet3->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet3->writeNumber ($rep->y, 3, $total3, $format_bold_right);
		
	//}

	$rep->y++;
	
			$rep->sheet4 = $rep->addWorksheet('IN BPI BANK STATEMENT');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet4->setColumn(0,0,15);
	$rep->sheet4->setColumn(0,1,12);
	$rep->sheet4->setColumn(0,2,12);
	$rep->sheet4->setColumn(3,3,10);
	$rep->sheet4->setColumn(4,4,50);
	$rep->sheet4->setColumn(5,6,10);
	$rep->sheet4->setColumn(7,7,12);
	$rep->sheet4->setColumn(8,9,13);
	$rep->sheet4->setColumn(9,9,13);
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
	$rep->sheet4->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet4->writeString($rep->y, 0, 'BPI Bank Recon (Credit/Debit Card)', $format_bold);
	$rep->y ++;

	$rep->sheet4->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet4->setMerge(0,0,0,3);
	// $rep->sheet4->setMerge(1,0,1,3);
	// $rep->sheet4->setMerge(2,0,2,3);
	// $rep->sheet4->setMerge(4,0,4,3);
	// $rep->sheet4->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','MEMO','TRANS DATE','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	
	// array_push($c_header,
	
// '10102299'
	// );
		

// '2000011',
// '2000012',
// '2000099',
// '2000100',
// '2000101',
// '2000102',
// '2000103',
// '2000104',
// '2000105'

	
	//print_r($c_header);
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	
// SELECT 'srsal' as br_code, 'alaminos' as branch, ad.*,gl.amount as amount FROM srs_aria_alaminos.`0_gl_trans` as gl 
// LEFT JOIN srs_aria_alaminos.0_acquiring_deductions as ad
// on gl.type_no=ad.p_ref_id
// where gl.type=62
// and gl.account='1010040'
// and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
// and gl.amount>0
// and reconciled=1
// GROUP BY ad.p_ref_id
	
$sql="

SELECT 'srsal' as br_code, 'alaminos' as branch, ad.*,gl.amount as amount FROM srs_aria_alaminos.`0_gl_trans` as gl 
LEFT JOIN srs_aria_alaminos.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id


UNION ALL

SELECT 'srsant2' as br_code,'manalo' as branch,ad.*,gl.amount as amount FROM srs_aria_antipolo_manalo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_antipolo_manalo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL


SELECT 'srsant1' as br_code,'quezon' as branch,ad.*,gl.amount as amount FROM srs_aria_antipolo_quezon.`0_gl_trans` as gl 
LEFT JOIN srs_aria_antipolo_quezon.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsbsl' as br_code,'bsilang' as branch,ad.*,gl.amount as amount FROM srs_aria_b_silang.`0_gl_trans` as gl 
LEFT JOIN srs_aria_b_silang.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsbgb' as br_code,'bagumbong' as branch,ad.*,gl.amount as amount FROM srs_aria_bagumbong.`0_gl_trans` as gl 
LEFT JOIN srs_aria_bagumbong.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srscain' as br_code,'cainta' as branch,ad.*,gl.amount as amount FROM srs_aria_cainta.`0_gl_trans` as gl 
LEFT JOIN srs_aria_cainta.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL


SELECT 'srscain2' as br_code,'cainta2' as branch,ad.*,gl.amount as amount FROM srs_aria_cainta_san_juan.`0_gl_trans` as gl 
LEFT JOIN srs_aria_cainta_san_juan.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsc' as br_code,'camarin' as branch,ad.*,gl.amount as amount FROM srs_aria_camarin.`0_gl_trans` as gl 
LEFT JOIN srs_aria_camarin.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srscom' as br_code,'comembo' as branch,ad.*,gl.amount as amount FROM srs_aria_comembo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_comembo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsg' as br_code,'gagalangin' as branch,ad.*,gl.amount as amount FROM srs_aria_gala.`0_gl_trans` as gl 
LEFT JOIN srs_aria_gala.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'sri' as br_code,'imus' as branch,ad.*,gl.amount as amount FROM srs_aria_imus.`0_gl_trans` as gl 
LEFT JOIN srs_aria_imus.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsm' as br_code,'malabon' as branch,ad.*,gl.amount as amount FROM srs_aria_malabon.`0_gl_trans` as gl 
LEFT JOIN srs_aria_malabon.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsmr' as br_code,'resto malabon' as branch,ad.*,gl.amount as amount FROM srs_aria_malabon_rest.`0_gl_trans` as gl 
LEFT JOIN srs_aria_malabon_rest.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsnav' as br_code,'navotas' as branch,ad.*,gl.amount as amount FROM srs_aria_navotas.`0_gl_trans` as gl 
LEFT JOIN srs_aria_navotas.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsn' as br_code,'nova' as branch,ad.*,gl.amount as amount FROM srs_aria_nova.`0_gl_trans` as gl 
LEFT JOIN srs_aria_nova.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srspat' as br_code,'pateros' as branch,ad.*,gl.amount as amount FROM srs_aria_pateros.`0_gl_trans` as gl 
LEFT JOIN srs_aria_pateros.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srspun' as br_code,'punturin' as branch,ad.*,gl.amount as amount FROM srs_aria_punturin_val.`0_gl_trans` as gl 
LEFT JOIN srs_aria_punturin_val.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srssanp' as br_code,'san pedro' as branch,ad.*,gl.amount as amount FROM srs_aria_san_pedro.`0_gl_trans` as gl 
LEFT JOIN srs_aria_san_pedro.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srstu' as br_code, 'talon uno' as branch,ad.*,gl.amount as amount FROM srs_aria_talon_uno.`0_gl_trans` as gl 
LEFT JOIN srs_aria_talon_uno.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srst' as br_code,'tondo' as branch,ad.*,gl.amount as amount FROM srs_aria_tondo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_tondo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsval' as br_code,'valenzuela' as branch,ad.*,gl.amount as amount FROM srs_aria_valenzuela.`0_gl_trans` as gl 
LEFT JOIN srs_aria_valenzuela.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62
and gl.account='1010040'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

";
	
	
//display_error($sql); die;
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	
	// if (db_num_rows($res) == 0)
		// return false;

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;

	$header_total_count=count($c_header2)-1;
	//print_r(count($c_header)-1);
	//==================End of counting header
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = $row['branch'];
		$c_details[$count][1] = $row['bd_memo'];
		$c_details[$count][2] = sql2date($row['date_paid']);
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'IN BANK STATEMENT';
		
		$total4+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet4->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet3->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet4->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet4->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet4->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet4->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet4->writeNumber ($rep->y, 3, $total4, $format_bold_right);
		
	//}

	$rep->y++;
	
    $rep->End();
}

?>