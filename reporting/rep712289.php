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
	$bank_account_code = $_POST['PARAM_4']; // bank_account_code

	if($bank_account_code =='10102299'){
		$bank_table = '0_bank_statement_aub';
		$memo_ = '%Adjustment for AUB,%';
		$bank_name = 'AUB';
	}else if ($bank_account_code =='1020021') {
		$bank_table = '0_bank_statement_metro';
		$memo_ = '%Adjustment for METRO,%';
		$bank_name = 'METROBANK';
	}
	else if ($bank_account_code =='1010040') {
		$bank_table = '0_bank_statement_bpi';
		$memo_ = '%Adjustment for BPI,%';
		$bank_name = 'BPI';
	}
	
	include_once($path_to_root . "/reporting/includes/excel_report.inc");

    $dec = user_price_dec();

	//==================================================== header
		$ap_account = get_company_pref('creditors_act'); //2000
		$h = 'For AP Trade/Non-Trade';

    $params =   array( 	0 => '',
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);
    
    $rep = new FrontReport(_('CASH DEPOSIT RECONCILED'), $bank_name." BANK RECON DEPOSIT DETAILS ", "LETTER");
    
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
	$rep->sheet->writeString($rep->y, 0, $bank_name.' DETAILS', $format_bold);
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
	$c_header = array('BRANCH','AMOUNT','DATE RECONCILED','STATUS');
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
	
/*	$sql="
	SELECT * FROM cash_deposit.`0_bank_statement_aub`
	where date_deposited>='".date2sql($from)."' and date_deposited<='".date2sql($to)."'
	and credit_amount!=0
	and cleared=1
	and type=101
	";*/

	$sql="
		SELECT * FROM (
select 'alaminos' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_alaminos.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'manalo' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_antipolo_manalo.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'quezon' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_antipolo_quezon.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'bagumbong' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_bagumbong.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'b silang' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_b_silang.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'cainta' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_cainta.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'san juan cainta' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_cainta_san_juan.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'camarin' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_camarin.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'comembo' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_comembo.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'gagalangin' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_gala.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'graceville' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_graceville.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'hero' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_hero.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'imus' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_imus.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'malabon' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_malabon.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'resto' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_malabon_rest.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'molino' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_molino.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'navotas' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_navotas.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'nova' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_nova.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'pateros' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_pateros.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'punturin' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_punturin_val.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'retail' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_retail.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'san pedro' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_san_pedro.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0
UNION ALL
select 'talon uno' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_talon_uno.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0
UNION ALL
select 'tondo' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_tondo.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0
UNION ALL
select 'valenzuela' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_valenzuela.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'mangahan' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_manggahan.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0
UNION ALL
select 'montalban' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_montalban.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 1
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0

) as a
	";
		
	//display_error($sql); die;
		
	   $res = db_query($sql);
	// $res2 = db_query($sql);
	
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
		/*$c_details[$count][0] = $row['branch_code'];
		$c_details[$count][1] = $row['bank_ref_num'];
		$c_details[$count][2] = sql2date($row['date_deposited']);
		$c_details[$count][3] = $row['credit_amount'];
		$c_details[$count][4] = 'RECONCILED';*/

		$c_details[$count][0] = $row['branch'];
		$c_details[$count][1] = $row['amount'];
		$c_details[$count][2] = sql2date($row['tran_date']);
		$c_details[$count][3] = 'RECONCILED';
		
		$total1+=$row['amount'];			
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
	$rep->sheet->writeString($rep->y, 1, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet->writeNumber ($rep->y, 2, $total1, $format_bold_right);
		
	//}
	
	
		$rep->y++;
	
			$rep->sheet2 = $rep->addWorksheet('CASH DEPOSIT UNRECONCILED');
	
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
	$rep->sheet2->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet2->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet2->setMerge(0,0,0,3);
	// $rep->sheet2->setMerge(1,0,1,3);
	// $rep->sheet2->setMerge(2,0,2,3);
	// $rep->sheet2->setMerge(4,0,4,3);
	// $rep->sheet2->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','AMOUNT','DATE RECONCILED','STATUS');
	//$c_header = array('BRANCH','MEMO','TRANS DATE','AMOUNT','STATUS');
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
	
/*$sql="
SELECT * FROM cash_deposit.0_cash_dep_header_new
where cd_cleared=0
and cd_bank_account_code='10102299'";*/


$sql ="
	SELECT * FROM (
select 'alaminos' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_alaminos.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'manalo' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_antipolo_manalo.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'quezon' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_antipolo_quezon.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'bagumbong' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_bagumbong.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'b silang' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_b_silang.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'cainta' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_cainta.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'san juan cainta' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_cainta_san_juan.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'camarin' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_camarin.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'comembo' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_comembo.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'gagalangin' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_gala.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'graceville' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_graceville.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'hero' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_hero.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'imus' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_imus.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'malabon' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_malabon.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'resto' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_malabon_rest.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'molino' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_molino.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'navotas' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_navotas.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'nova' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_nova.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'pateros' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_pateros.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'punturin' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_punturin_val.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'retail' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_retail.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'san pedro' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_san_pedro.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0
UNION ALL
select 'talon uno' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_talon_uno.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0
UNION ALL
select 'tondo' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_tondo.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."' 
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0
UNION ALL
select 'valenzuela' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_valenzuela.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'mangahan' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_manggahan.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
UNION ALL
select 'montalban' as branch,gl_aria.amount,gl_aria.tran_date,'RECONCILED'  from srs_aria_montalban.0_gl_trans as gl_aria
LEFT JOIN cash_deposit.0_cash_dep_header_new as chn
on gl_aria.type_no = chn.cd_id and gl_aria.type = chn.cd_trans_type
where  gl_aria.tran_date >='".date2sql($from)."' and gl_aria.tran_date <='".date2sql($to)."'  
and gl_aria.account ='".$bank_account_code."' and chn.cd_bank_account_code ='".$bank_account_code."' and gl_aria.type = '101' and chn.cd_cleared = 0
and  gl_aria.amount > 0 and chn.cd_gross_amount > 0 
) AS b";	
	
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
		/*$c_details[$count][0] = $row['cd_br_code'];
		$c_details[$count][1] = $row['cd_memo'];
		$c_details[$count][2] = sql2date($row['cd_date_deposit']);
		$c_details[$count][3] = $row['cd_gross_amount'];*/
		$c_details[$count][0] = $row['branch'];
		$c_details[$count][1] = $row['amount'];
		$c_details[$count][2] = sql2date($row['tran_date']);
		$c_details[$count][4] = 'NOT RECONCILED';
		//$total3+=$row['cd_gross_amount'];		
		$total3+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet2->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet2->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
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
	$rep->sheet2->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet2->writeNumber ($rep->y, 3, $total3, $format_bold_right);
		
	//}

	
	

	$rep->y++;
	
		$rep->sheet3 = $rep->addWorksheet('BANK DEPOSIT RECONCILED');
	
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
	$rep->sheet3->writeString($rep->y, 0, $bank_name.'(Details)', $format_bold);
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
	$c_header = array('TRANS#','BRANCH','MEMO','TRANS DATE','AMOUNT','STATUS');
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
SELECT * from
(
SELECT 'srsal' as br_code, 'alaminos' as branch,oi.*,gl.amount as amount 
FROM srs_aria_alaminos.0_gl_trans as gl 
LEFT JOIN srs_aria_alaminos.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsant2' as br_code,'manalo' as branch,oi.*,gl.amount as amount 
FROM srs_aria_antipolo_manalo.0_gl_trans as gl 
LEFT JOIN srs_aria_antipolo_manalo.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsant1' as br_code,'quezon' as branch,oi.*,gl.amount as amount 
FROM srs_aria_antipolo_quezon.0_gl_trans as gl 
LEFT JOIN srs_aria_antipolo_quezon.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsbsl' as br_code,'bsilang' as branch,oi.*,gl.amount as amount 
FROM srs_aria_b_silang.0_gl_trans as gl 
LEFT JOIN srs_aria_b_silang.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsbgb' as br_code,'bagumbong' as branch,oi.*,gl.amount as amount 
FROM srs_aria_bagumbong.0_gl_trans as gl 
LEFT JOIN srs_aria_bagumbong.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srscain' as br_code,'cainta' as branch,oi.*,gl.amount as amount 
FROM srs_aria_cainta.0_gl_trans as gl 
LEFT JOIN srs_aria_cainta.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srscain2' as br_code,'cainta2' as branch,oi.*,gl.amount as amount 
FROM srs_aria_cainta_san_juan.0_gl_trans as gl 
LEFT JOIN srs_aria_cainta_san_juan.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsc' as br_code,'camarin' as branch,oi.*,gl.amount as amount 
FROM srs_aria_camarin.0_gl_trans as gl 
LEFT JOIN srs_aria_camarin.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srscom' as br_code,'comembo' as branch,oi.*,gl.amount as amount 
FROM srs_aria_comembo.0_gl_trans as gl 
LEFT JOIN srs_aria_comembo.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsg' as br_code,'gagalangin' as branch,oi.*,gl.amount as amount 
FROM srs_aria_gala.0_gl_trans as gl 
LEFT JOIN srs_aria_gala.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 
UNION ALL

SELECT 'srsgrace' as br_code,'graceville' as branch,oi.*,gl.amount as amount 
FROM srs_aria_graceville.0_gl_trans as gl 
LEFT JOIN srs_aria_graceville.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'sri' as br_code,'imus' as branch,oi.*,gl.amount as amount 
FROM srs_aria_imus.0_gl_trans as gl 
LEFT JOIN srs_aria_imus.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsm' as br_code,'malabon' as branch,oi.*,gl.amount as amount 
FROM srs_aria_malabon.0_gl_trans as gl 
LEFT JOIN srs_aria_malabon.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsmr' as br_code,'resto malabon' as branch,oi.*,gl.amount as amount 
FROM srs_aria_malabon_rest.0_gl_trans as gl 
LEFT JOIN srs_aria_malabon_rest.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsnav' as br_code,'navotas' as branch,oi.*,gl.amount as amount 
FROM srs_aria_navotas.0_gl_trans as gl 
LEFT JOIN srs_aria_navotas.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsn' as br_code,'nova' as branch,oi.*,gl.amount as amount 
FROM srs_aria_nova.0_gl_trans as gl 
LEFT JOIN srs_aria_nova.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srspat' as br_code,'pateros' as branch,oi.*,gl.amount as amount 
FROM srs_aria_pateros.0_gl_trans as gl 
LEFT JOIN srs_aria_pateros.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srspun' as br_code,'punturin' as branch,oi.*,gl.amount as amount 
FROM srs_aria_punturin_val.0_gl_trans as gl 
LEFT JOIN srs_aria_punturin_val.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsret' as br_code,'retail' as branch,oi.*,gl.amount as amount 
FROM srs_aria_retail.0_gl_trans as gl 
LEFT JOIN srs_aria_retail.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srssanp' as br_code,'san pedro' as branch,oi.*,gl.amount as amount 
FROM srs_aria_san_pedro.0_gl_trans as gl 
LEFT JOIN srs_aria_san_pedro.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 
UNION ALL

SELECT 'srstu' as br_code, 'talon uno' as branch,oi.*,gl.amount as amount 
FROM srs_aria_talon_uno.0_gl_trans as gl 
LEFT JOIN srs_aria_talon_uno.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srst' as br_code,'tondo' as branch,oi.*,gl.amount as amount 
FROM srs_aria_tondo.0_gl_trans as gl 
LEFT JOIN srs_aria_tondo.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsval' as br_code,'valenzuela' as branch,oi.*,gl.amount as amount 
FROM srs_aria_valenzuela.0_gl_trans as gl 
LEFT JOIN srs_aria_valenzuela.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1  

UNION ALL

SELECT 'srsman' as br_code,'mangahan' as branch,oi.*,gl.amount as amount 
FROM srs_aria_manggahan.0_gl_trans as gl 
LEFT JOIN srs_aria_manggahan.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1 

UNION ALL

SELECT 'srsmon' as br_code,'montalban' as branch,oi.*,gl.amount as amount 
FROM srs_aria_montalban.0_gl_trans as gl 
LEFT JOIN srs_aria_montalban.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 1  




) as c";
	
	
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
		$c_details[$count][0] = $row['bd_trans_no'];
		$c_details[$count][1] = $row['branch'];
		$c_details[$count][2] = $row['bd_memo'];
		$c_details[$count][3] = sql2date($row['bd_trans_date']);
		$c_details[$count][4] = $row['amount'];
		$c_details[$count][5] = 'RECONCILED';
		
		$total2+=$row['amount'];	
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
		$rep->sheet3->writeNumber ($rep->y, 3, $total2, $format_bold_right);
		
	//}


	
		$rep->y++;
	
			$rep->sheet4 = $rep->addWorksheet('BANK DEPOSIT  UNRECONCILED');
	
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
	$rep->sheet4->writeString($rep->y, 0, 'AUB Bank Recon (Details)', $format_bold);
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
	$c_header = array('TRANS#','BRANCH','MEMO','TRANS DATE','AMOUNT','STATUS');
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
SELECT *  from
(
SELECT 'srsal' as br_code, 'alaminos' as branch,oi.*,gl.amount as amount 
FROM srs_aria_alaminos.0_gl_trans as gl 
LEFT JOIN srs_aria_alaminos.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsant2' as br_code,'manalo' as branch,oi.*,gl.amount as amount 
FROM srs_aria_antipolo_manalo.0_gl_trans as gl 
LEFT JOIN srs_aria_antipolo_manalo.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsant1' as br_code,'quezon' as branch,oi.*,gl.amount as amount 
FROM srs_aria_antipolo_quezon.0_gl_trans as gl 
LEFT JOIN srs_aria_antipolo_quezon.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsbsl' as br_code,'bsilang' as branch,oi.*,gl.amount as amount 
FROM srs_aria_b_silang.0_gl_trans as gl 
LEFT JOIN srs_aria_b_silang.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsbgb' as br_code,'bagumbong' as branch,oi.*,gl.amount as amount 
FROM srs_aria_bagumbong.0_gl_trans as gl 
LEFT JOIN srs_aria_bagumbong.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srscain' as br_code,'cainta' as branch,oi.*,gl.amount as amount 
FROM srs_aria_cainta.0_gl_trans as gl 
LEFT JOIN srs_aria_cainta.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srscain2' as br_code,'cainta2' as branch,oi.*,gl.amount as amount 
FROM srs_aria_cainta_san_juan.0_gl_trans as gl 
LEFT JOIN srs_aria_cainta_san_juan.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsc' as br_code,'camarin' as branch,oi.*,gl.amount as amount 
FROM srs_aria_camarin.0_gl_trans as gl 
LEFT JOIN srs_aria_camarin.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srscom' as br_code,'comembo' as branch,oi.*,gl.amount as amount 
FROM srs_aria_comembo.0_gl_trans as gl 
LEFT JOIN srs_aria_comembo.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsg' as br_code,'gagalangin' as branch,oi.*,gl.amount as amount 
FROM srs_aria_gala.0_gl_trans as gl 
LEFT JOIN srs_aria_gala.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 
UNION ALL

SELECT 'srsgrace' as br_code,'graceville' as branch,oi.*,gl.amount as amount 
FROM srs_aria_graceville.0_gl_trans as gl 
LEFT JOIN srs_aria_graceville.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'sri' as br_code,'imus' as branch,oi.*,gl.amount as amount 
FROM srs_aria_imus.0_gl_trans as gl 
LEFT JOIN srs_aria_imus.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsm' as br_code,'malabon' as branch,oi.*,gl.amount as amount 
FROM srs_aria_malabon.0_gl_trans as gl 
LEFT JOIN srs_aria_malabon.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsmr' as br_code,'resto malabon' as branch,oi.*,gl.amount as amount 
FROM srs_aria_malabon_rest.0_gl_trans as gl 
LEFT JOIN srs_aria_malabon_rest.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsnav' as br_code,'navotas' as branch,oi.*,gl.amount as amount 
FROM srs_aria_navotas.0_gl_trans as gl 
LEFT JOIN srs_aria_navotas.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsn' as br_code,'nova' as branch,oi.*,gl.amount as amount 
FROM srs_aria_nova.0_gl_trans as gl 
LEFT JOIN srs_aria_nova.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srspat' as br_code,'pateros' as branch,oi.*,gl.amount as amount 
FROM srs_aria_pateros.0_gl_trans as gl 
LEFT JOIN srs_aria_pateros.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srspun' as br_code,'punturin' as branch,oi.*,gl.amount as amount 
FROM srs_aria_punturin_val.0_gl_trans as gl 
LEFT JOIN srs_aria_punturin_val.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsret' as br_code,'retail' as branch,oi.*,gl.amount as amount 
FROM srs_aria_retail.0_gl_trans as gl 
LEFT JOIN srs_aria_retail.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srssanp' as br_code,'san pedro' as branch,oi.*,gl.amount as amount 
FROM srs_aria_san_pedro.0_gl_trans as gl 
LEFT JOIN srs_aria_san_pedro.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 
UNION ALL

SELECT 'srstu' as br_code, 'talon uno' as branch,oi.*,gl.amount as amount 
FROM srs_aria_talon_uno.0_gl_trans as gl 
LEFT JOIN srs_aria_talon_uno.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srst' as br_code,'tondo' as branch,oi.*,gl.amount as amount 
FROM srs_aria_tondo.0_gl_trans as gl 
LEFT JOIN srs_aria_tondo.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0 

UNION ALL

SELECT 'srsval' as br_code,'valenzuela' as branch,oi.*,gl.amount as amount 
FROM srs_aria_valenzuela.0_gl_trans as gl 
LEFT JOIN srs_aria_valenzuela.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0  

UNION ALL

SELECT 'srsman' as br_code,'mangahan' as branch,oi.*,gl.amount as amount 
FROM srs_aria_manggahan.0_gl_trans as gl 
LEFT JOIN srs_aria_manggahan.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0  

UNION ALL

SELECT 'srsmon' as br_code,'montalban' as branch,oi.*,gl.amount as amount 
FROM srs_aria_montalban.0_gl_trans as gl 
LEFT JOIN srs_aria_montalban.0_other_income_payment_header as oi
on gl.type_no=oi.bd_trans_no
where gl.type=2
and gl.tran_date>='".date2sql($from)."' and gl.tran_date<='".date2sql($to)."'
and gl.account='".$bank_account_code."'
and gl.amount>0 and bd_trans_type = 2  AND bd_reconciled = 0  


) as d


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
		$c_details[$count][0] = $row['bd_trans_no'];
		$c_details[$count][1] = $row['branch'];
		$c_details[$count][2] = $row['bd_memo'];
		$c_details[$count][3] = sql2date($row['bd_trans_date']);
		$c_details[$count][4] = $row['amount'];
		$c_details[$count][5] = 'NOT IN BANK STATEMENT';
		
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
		// $rep->sheet4->writeString($rep->y, $ind, $title , $format_bold_title);
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
	$rep->sheet4->writeString($rep->y, 3, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet4->writeNumber ($rep->y, 4, $total4, $format_bold_right);
		
	//}
	
		$rep->y++;

		$rep->sheet5 = $rep->addWorksheet('SALES TOTAL RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet5->setColumn(0,0,15);
	$rep->sheet5->setColumn(0,1,12);
	$rep->sheet5->setColumn(0,2,12);
	$rep->sheet5->setColumn(3,3,10);
	$rep->sheet5->setColumn(4,4,50);
	$rep->sheet5->setColumn(5,6,10);
	$rep->sheet5->setColumn(7,7,12);
	$rep->sheet5->setColumn(8,9,13);
	$rep->sheet5->setColumn(9,9,13);
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
	$rep->sheet5->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet5->writeString($rep->y, 0, $bank_name.'(Details)', $format_bold);
	$rep->y ++;

	$rep->sheet5->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	
	//print_r($c_header);
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	

	$sql="SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60  and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id ='99'
) as e";
	
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total5+=$row['amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet5->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet5->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet5->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet5->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet5->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet5->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet5->writeNumber ($rep->y, 3, $total5, $format_bold_right);
		
	//}

	
	$rep->y++;
	
				$rep->sheet6 = $rep->addWorksheet('SALES TOTAL UNRECONCILED' );
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet6->setColumn(0,0,15);
	$rep->sheet6->setColumn(0,1,12);
	$rep->sheet6->setColumn(0,2,12);
	$rep->sheet6->setColumn(3,3,10);
	$rep->sheet6->setColumn(4,4,50);
	$rep->sheet6->setColumn(5,6,10);
	$rep->sheet6->setColumn(7,7,12);
	$rep->sheet6->setColumn(8,9,13);
	$rep->sheet6->setColumn(9,9,13);
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
	$rep->sheet6->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet6->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet6->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet6->setMerge(0,0,0,3);
	// $rep->sheet6->setMerge(1,0,1,3);
	// $rep->sheet6->setMerge(2,0,2,3);
	// $rep->sheet6->setMerge(4,0,4,3);
	// $rep->sheet6->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
	
$sql="SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60  and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=60 and dimension_id !='99'



) as f

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
		
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		
		$total6+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet6->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet6->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet6->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet6->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet6->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet6->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet6->writeNumber ($rep->y, 7, $total6, $format_bold_right);
		
	//}

	$rep->y++;

				$rep->sheet7 = $rep->addWorksheet('JOURNAL RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet7->setColumn(0,0,15);
	$rep->sheet7->setColumn(0,1,12);
	$rep->sheet7->setColumn(0,2,12);
	$rep->sheet7->setColumn(3,3,10);
	$rep->sheet7->setColumn(4,4,50);
	$rep->sheet7->setColumn(5,6,10);
	$rep->sheet7->setColumn(7,7,12);
	$rep->sheet7->setColumn(8,9,13);
	$rep->sheet7->setColumn(9,9,13);
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
	$rep->sheet7->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet7->writeString($rep->y, 0, $bank_name.'(Details)', $format_bold);
	$rep->y ++;

	$rep->sheet7->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet7->setMerge(0,0,0,3);
	// $rep->sheet7->setMerge(1,0,1,3);
	// $rep->sheet7->setMerge(2,0,2,3);
	// $rep->sheet7->setMerge(4,0,4,3);
	// $rep->sheet7->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id ='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'


UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'

UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id='99'


) as g";
	
	
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total7+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet7->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet7->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet7->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet7->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet7->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet7->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet7->writeNumber ($rep->y, 7, $total7, $format_bold_right);
		
	//}
	
	
	
	$rep->y++;

	$rep->sheet8 = $rep->addWorksheet('JOURNAL UNRECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet8->setColumn(0,0,15);
	$rep->sheet8->setColumn(0,1,12);
	$rep->sheet8->setColumn(0,2,12);
	$rep->sheet8->setColumn(3,3,10);
	$rep->sheet8->setColumn(4,4,50);
	$rep->sheet8->setColumn(5,6,10);
	$rep->sheet8->setColumn(7,7,12);
	$rep->sheet8->setColumn(8,9,13);
	$rep->sheet8->setColumn(9,9,13);
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
	$rep->sheet8->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet8->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet8->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet8->setMerge(0,0,0,3);
	// $rep->sheet8->setMerge(1,0,1,3);
	// $rep->sheet8->setMerge(2,0,2,3);
	// $rep->sheet8->setMerge(4,0,4,3);
	// $rep->sheet8->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'


UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'

UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=0 and dimension_id !='99'




) as h


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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		$total8+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet8->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet8->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet8->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet8->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet8->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet8->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet8->writeNumber ($rep->y, 7, $total8, $format_bold_right);
		
	//}

	
	$rep->y++;


	$rep->sheet9 = $rep->addWorksheet('OUTSTANDING CHECKS RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet9->setColumn(0,0,15);
	$rep->sheet9->setColumn(0,1,12);
	$rep->sheet9->setColumn(0,2,12);
	$rep->sheet9->setColumn(3,3,10);
	$rep->sheet9->setColumn(4,4,50);
	$rep->sheet9->setColumn(5,6,10);
	$rep->sheet9->setColumn(7,7,12);
	$rep->sheet9->setColumn(8,9,13);
	$rep->sheet9->setColumn(9,9,13);
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
	$rep->sheet9->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet9->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet9->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet9->setMerge(0,0,0,3);
	// $rep->sheet9->setMerge(1,0,1,3);
	// $rep->sheet9->setMerge(2,0,2,3);
	// $rep->sheet9->setMerge(4,0,4,3);
	// $rep->sheet9->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7  AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7  AND dimension_id ='99' 


UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7  AND dimension_id ='99' ) as i
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total9+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet9->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet9->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet9->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet9->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet9->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet9->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet9->writeNumber ($rep->y, 7, $total9, $format_bold_right);
		
	//}
	
	
		$rep->y++;

	$rep->sheet10 = $rep->addWorksheet('OUTSTANDING CHECKS UNRECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet10->setColumn(0,0,15);
	$rep->sheet10->setColumn(0,1,12);
	$rep->sheet10->setColumn(0,2,12);
	$rep->sheet10->setColumn(3,3,10);
	$rep->sheet10->setColumn(4,4,50);
	$rep->sheet10->setColumn(5,6,10);
	$rep->sheet10->setColumn(7,7,12);
	$rep->sheet10->setColumn(8,9,13);
	$rep->sheet10->setColumn(9,9,13);
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
	$rep->sheet10->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet10->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet10->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet10->setMerge(0,0,0,3);
	// $rep->sheet10->setMerge(1,0,1,3);
	// $rep->sheet10->setMerge(2,0,2,3);
	// $rep->sheet10->setMerge(4,0,4,3);
	// $rep->sheet10->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	
	//print_r($c_header);
	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
$sql="SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7  AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7  AND dimension_id  !='99' 


UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=7  AND dimension_id  !='99' 



) as j
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
		
		
		
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		
		$total10+=$row['amount'];		

	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet10->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet10->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet10->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet10->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet10->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet10->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet10->writeNumber ($rep->y, 7, $total10, $format_bold_right);
		
	//}
	
	$rep->y++;

	$rep->sheet11 = $rep->addWorksheet('STALED CHECKS RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet11->setColumn(0,0,15);
	$rep->sheet11->setColumn(0,1,12);
	$rep->sheet11->setColumn(0,2,12);
	$rep->sheet11->setColumn(3,3,10);
	$rep->sheet11->setColumn(4,4,50);
	$rep->sheet11->setColumn(5,6,10);
	$rep->sheet11->setColumn(7,7,12);
	$rep->sheet11->setColumn(8,9,13);
	$rep->sheet11->setColumn(9,9,13);
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
	$rep->sheet11->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet11->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet11->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet11->setMerge(0,0,0,3);
	// $rep->sheet11->setMerge(1,0,1,3);
	// $rep->sheet11->setMerge(2,0,2,3);
	// $rep->sheet11->setMerge(4,0,4,3);
	// $rep->sheet11->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6  AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6  AND dimension_id ='99' 

UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id ='99'

UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."' 
AND account ='".$bank_account_code."' AND amount>0 AND type=6  AND dimension_id ='99' 


) as k
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total11+=$row['amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet11->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet11->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet11->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet11->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet11->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet11->writeNumber ($rep->y, 3, $total11, $format_bold_right);
		
	//}
	
		$rep->y++;

			$rep->sheet12 = $rep->addWorksheet('STALED CHECKS UNRECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet12->setColumn(0,0,15);
	$rep->sheet12->setColumn(0,1,12);
	$rep->sheet12->setColumn(0,2,12);
	$rep->sheet12->setColumn(3,3,10);
	$rep->sheet12->setColumn(4,4,50);
	$rep->sheet12->setColumn(5,6,10);
	$rep->sheet12->setColumn(7,7,12);
	$rep->sheet12->setColumn(8,9,13);
	$rep->sheet12->setColumn(9,9,13);
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
	$rep->sheet12->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet12->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet12->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet11->setMerge(0,0,0,3);
	// $rep->sheet11->setMerge(1,0,1,3);
	// $rep->sheet11->setMerge(2,0,2,3);
	// $rep->sheet11->setMerge(4,0,4,3);
	// $rep->sheet11->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
SELECT * FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6  AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6  AND dimension_id  !='99' 

UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_montalban	' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban	.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=6  AND dimension_id  !='99' 

) as l
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		
		$total12+=$row['amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet12->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet12->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet12->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet12->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet12->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet12->writeNumber ($rep->y, 3, $total12, $format_bold_right);
		
	//}
	
	
	
		$rep->y++;


		$rep->sheet13 = $rep->addWorksheet('APV RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet13->setColumn(0,0,15);
	$rep->sheet13->setColumn(0,1,12);
	$rep->sheet13->setColumn(0,2,12);
	$rep->sheet13->setColumn(3,3,10);
	$rep->sheet13->setColumn(4,4,50);
	$rep->sheet13->setColumn(5,6,10);
	$rep->sheet13->setColumn(7,7,12);
	$rep->sheet13->setColumn(8,9,13);
	$rep->sheet13->setColumn(9,9,13);
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
	$rep->sheet13->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet13->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet13->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet11->setMerge(0,0,0,3);
	// $rep->sheet11->setMerge(1,0,1,3);
	// $rep->sheet11->setMerge(2,0,2,3);
	// $rep->sheet11->setMerge(4,0,4,3);
	// $rep->sheet11->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
$sql="
SELECT *
FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0  AND type=20  AND dimension_id  ='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  ='99'


) as q
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total13+=$row['amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet13->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet13->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet13->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet13->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet13->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet13->writeNumber ($rep->y, 3, $total13, $format_bold_right);
		
	//}
	
	
	
		$rep->y++;


	$rep->sheet14 = $rep->addWorksheet('APV UNRECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet14->setColumn(0,0,15);
	$rep->sheet14->setColumn(0,1,12);
	$rep->sheet14->setColumn(0,2,12);
	$rep->sheet14->setColumn(3,3,10);
	$rep->sheet14->setColumn(4,4,50);
	$rep->sheet14->setColumn(5,6,10);
	$rep->sheet14->setColumn(7,7,12);
	$rep->sheet14->setColumn(8,9,13);
	$rep->sheet14->setColumn(9,9,13);
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
	$rep->sheet14->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet14->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet14->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet11->setMerge(0,0,0,3);
	// $rep->sheet11->setMerge(1,0,1,3);
	// $rep->sheet11->setMerge(2,0,2,3);
	// $rep->sheet11->setMerge(4,0,4,3);
	// $rep->sheet11->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
SELECT *
FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0  AND type=20  AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=20 AND dimension_id  !='99'




) as x
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		
		$total14+=$row['amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet14->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet14->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet14->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet14->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet14->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet14->writeNumber ($rep->y, 3, $total14, $format_bold_right);
		
	//}
	

		$rep->y++;

			$rep->sheet17 = $rep->addWorksheet('BAYAD CENTER RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet17->setColumn(0,0,15);
	$rep->sheet17->setColumn(0,1,12);
	$rep->sheet17->setColumn(0,2,12);
	$rep->sheet17->setColumn(3,3,10);
	$rep->sheet17->setColumn(4,4,50);
	$rep->sheet17->setColumn(5,6,10);
	$rep->sheet17->setColumn(7,7,12);
	$rep->sheet17->setColumn(8,9,13);
	$rep->sheet17->setColumn(9,9,13);
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
	$rep->sheet17->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet17->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet17->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet11->setMerge(0,0,0,3);
	// $rep->sheet11->setMerge(1,0,1,3);
	// $rep->sheet11->setMerge(2,0,2,3);
	// $rep->sheet11->setMerge(4,0,4,3);
	// $rep->sheet11->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
$sql="
SELECT *
FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0  AND type=74  AND dimension_id  ='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'



UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99'


UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  ='99') as q
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total17+=$row['amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet17->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet17->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet17->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet17->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet17->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet17->writeNumber ($rep->y, 3, $total17, $format_bold_right);
		
	//}
	
	
	
		$rep->y++;


	$rep->sheet18 = $rep->addWorksheet('BAYAD CENTER UNRECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet18->setColumn(0,0,15);
	$rep->sheet18->setColumn(0,1,12);
	$rep->sheet18->setColumn(0,2,12);
	$rep->sheet18->setColumn(3,3,10);
	$rep->sheet18->setColumn(4,4,50);
	$rep->sheet18->setColumn(5,6,10);
	$rep->sheet18->setColumn(7,7,12);
	$rep->sheet18->setColumn(8,9,13);
	$rep->sheet18->setColumn(9,9,13);
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
	$rep->sheet18->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet18->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet18->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet11->setMerge(0,0,0,3);
	// $rep->sheet11->setMerge(1,0,1,3);
	// $rep->sheet11->setMerge(2,0,2,3);
	// $rep->sheet11->setMerge(4,0,4,3);
	// $rep->sheet11->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE', 'MEMO','DEBIT AMOUNT','STATUS');
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
SELECT *
FROM
(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0  AND type=74  AND dimension_id  !='99'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'



UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
FROM srs_aria_manggahan.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'


UNION ALL

SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
FROM srs_aria_montalban.0_gl_trans
WHERE tran_date >= '".date2sql($from)."'
AND  tran_date <= '".date2sql($to)."'
AND account ='".$bank_account_code."' AND amount>0 AND type=74 AND dimension_id  !='99'
) as x
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
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		
		$total18+=$row['amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet18->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet18->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet18->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet18->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet18->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet18->writeNumber ($rep->y, 3, $total18, $format_bold_right);
		
	//}
	

		$rep->y++;	



		$rep->sheet15 = $rep->addWorksheet('BANK CREDIT UNRECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet15->setColumn(0,0,15);
	$rep->sheet15->setColumn(0,1,12);
	$rep->sheet15->setColumn(0,2,12);
	$rep->sheet15->setColumn(3,3,10);
	$rep->sheet15->setColumn(4,4,50);
	$rep->sheet15->setColumn(5,6,10);
	$rep->sheet15->setColumn(7,7,12);
	$rep->sheet15->setColumn(8,9,13);
	$rep->sheet15->setColumn(9,9,13);
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
	$rep->sheet15->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet15->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet15->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;


//===================================================================================

	// get the header
	$c_header = array('BRANCH','REFRENCE', 'DATE RECONCILED','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
	$sql="SELECT * FROM cash_deposit.$bank_table
	where credit_amount!=0
	and cleared=0
	and date_deposited>='".date2sql($from)."'
	and date_deposited<='".date2sql($to)."'";
	
	
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
		$c_details[$count][1] = $row['bank_ref_num'];
		$c_details[$count][2] = sql2date($row['date_deposited']);
		$c_details[$count][3] = $row['credit_amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		
		$total15+=$row['credit_amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		//var_dump($c_header);
		// var_dump($ind);
		// var_dump($c_header_last_index);

		$rep->sheet15->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet15->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet15->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet15->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet15->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet15->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet15->writeNumber ($rep->y, 3, $total15, $format_bold_right);
		
	//}
	
	
	
		$rep->y++;


		$rep->sheet16 = $rep->addWorksheet('REVERSAL RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet16->setColumn(0,0,15);
	$rep->sheet16->setColumn(0,1,12);
	$rep->sheet16->setColumn(0,2,12);
	$rep->sheet16->setColumn(3,3,10);
	$rep->sheet16->setColumn(4,4,50);
	$rep->sheet16->setColumn(5,6,10);
	$rep->sheet16->setColumn(7,7,12);
	$rep->sheet16->setColumn(8,9,13);
	$rep->sheet16->setColumn(9,9,13);
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
	$rep->sheet16->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet16->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet16->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;


//===================================================================================

	// get the header
	$c_header = array('BRANCH','REFRENCE', 'DATE RECONCILED','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	

	$c_details = array();
	$c_totals = array();
	$gl_details = array();
	
$sql="SELECT * FROM cash_deposit.$bank_table
where credit_amount!=0
and cleared=1
and type=99
and date_deposited>='".date2sql($from)."'
and date_deposited<='".date2sql($to)."'
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
		$c_details[$count][1] = $row['bank_ref_num'];
		$c_details[$count][2] = sql2date($row['date_deposited']);
		$c_details[$count][3] = $row['credit_amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total16+=$row['credit_amount'];	
	}
		//var_dump($c_totals1);die;

	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		$rep->sheet16->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	
	// array_push($c_header, 'SUNDRIES', 'CR', 'DR');
	
	// foreach ($c_header as $ind => $title){
		// $rep->sheet16->writeString($rep->y, $ind, $title , $format_bold_title);
	// }
	
	// var_dump($c_details);die;
	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet16->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet16->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet16->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet16->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet16->writeNumber ($rep->y, 3, $total16, $format_bold_right);
		
	//}
	
	
	
		$rep->y++;
	

	#########################
	$rep->sheet19 = $rep->addWorksheet('CR_DR CARD RECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet19->setColumn(0,0,15);
	$rep->sheet19->setColumn(0,1,12);
	$rep->sheet19->setColumn(0,2,12);
	$rep->sheet19->setColumn(3,3,10);
	$rep->sheet19->setColumn(4,4,50);
	$rep->sheet19->setColumn(5,6,10);
	$rep->sheet19->setColumn(7,7,12);
	$rep->sheet19->setColumn(8,9,13);
	$rep->sheet19->setColumn(9,9,13);
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
	$rep->sheet19->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet19->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet19->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;


//===================================================================================

	// get the header
	$c_header = array('BRANCH','REFRENCE', 'DATE RECONCILED','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	

	$c_details = array();
	$c_totals = array();
	$gl_details = array();

	
	$sql="SELECT * FROM cash_deposit.$bank_table
		where credit_amount > 0
		and cleared=1
		and type=62
		and date_deposited>='".date2sql($from)."'
		and date_deposited<='".date2sql($to)."'";
	
	
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;
	
	$header_total_count=count($c_header2)-1;
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = $row['branch_code'];
		$c_details[$count][1] = $row['bank_ref_num'];
		$c_details[$count][2] = sql2date($row['date_deposited']);
		$c_details[$count][3] = $row['credit_amount'];
		$c_details[$count][4] = 'RECONCILED';
		
		$total19+=$row['credit_amount'];	
	}


	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		$rep->sheet19->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet19->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet19->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet19->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet19->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet19->writeNumber ($rep->y, 3, $total19, $format_bold_right);
		
	//}
	
	
	
		$rep->y++;
	#######################

		$rep->sheet20 = $rep->addWorksheet('CR_DR CARD UNRECONCILED');
	
	    $dec = user_price_dec();

	//==================================================== header
	$rep->sheet20->setColumn(0,0,15);
	$rep->sheet20->setColumn(0,1,12);
	$rep->sheet20->setColumn(0,2,12);
	$rep->sheet20->setColumn(3,3,10);
	$rep->sheet20->setColumn(4,4,50);
	$rep->sheet20->setColumn(5,6,10);
	$rep->sheet20->setColumn(7,7,12);
	$rep->sheet20->setColumn(8,9,13);
	$rep->sheet20->setColumn(9,9,13);
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
	$rep->sheet20->writeString($rep->y, 0, $com['coy_name'], $format_bold);
	$rep->y ++;
	$rep->sheet20->writeString($rep->y, 0, $bank_name.' (Details)', $format_bold);
	$rep->y ++;

	$rep->sheet20->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;


//===================================================================================

	// get the header
	$c_header = array('BRANCH','REFRENCE', 'DATE RECONCILED','AMOUNT','STATUS');
	$c_header_last_index = count($c_header)-1;

	

	$c_details = array();
	$c_totals = array();
	$gl_details = array();

	
	$sql="SELECT * FROM
(
SELECT 'srsal' as br_code, 'alaminos' as branch, ad.*,gl.amount as amount FROM srs_aria_alaminos.`0_gl_trans` as gl 
LEFT JOIN srs_aria_alaminos.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id



UNION ALL

SELECT 'srsant2' as br_code,'manalo' as branch,ad.*,gl.amount as amount FROM srs_aria_antipolo_manalo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_antipolo_manalo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL


SELECT 'srsant1' as br_code,'quezon' as branch,ad.*,gl.amount as amount FROM srs_aria_antipolo_quezon.`0_gl_trans` as gl 
LEFT JOIN srs_aria_antipolo_quezon.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsbsl' as br_code,'bsilang' as branch,ad.*,gl.amount as amount FROM srs_aria_b_silang.`0_gl_trans` as gl 
LEFT JOIN srs_aria_b_silang.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsbgb' as br_code,'bagumbong' as branch,ad.*,gl.amount as amount FROM srs_aria_bagumbong.`0_gl_trans` as gl 
LEFT JOIN srs_aria_bagumbong.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srscain' as br_code,'cainta' as branch,ad.*,gl.amount as amount FROM srs_aria_cainta.`0_gl_trans` as gl 
LEFT JOIN srs_aria_cainta.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL


SELECT 'srscain2' as br_code,'cainta2' as branch,ad.*,gl.amount as amount FROM srs_aria_cainta_san_juan.`0_gl_trans` as gl 
LEFT JOIN srs_aria_cainta_san_juan.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsc' as br_code,'camarin' as branch,ad.*,gl.amount as amount FROM srs_aria_camarin.`0_gl_trans` as gl 
LEFT JOIN srs_aria_camarin.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srscom' as br_code,'comembo' as branch,ad.*,gl.amount as amount FROM srs_aria_comembo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_comembo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsg' as br_code,'gagalangin' as branch,ad.*,gl.amount as amount FROM srs_aria_gala.`0_gl_trans` as gl 
LEFT JOIN srs_aria_gala.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'sri' as br_code,'imus' as branch,ad.*,gl.amount as amount FROM srs_aria_imus.`0_gl_trans` as gl 
LEFT JOIN srs_aria_imus.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsm' as br_code,'malabon' as branch,ad.*,gl.amount as amount FROM srs_aria_malabon.`0_gl_trans` as gl 
LEFT JOIN srs_aria_malabon.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsmr' as br_code,'resto malabon' as branch,ad.*,gl.amount as amount FROM srs_aria_malabon_rest.`0_gl_trans` as gl 
LEFT JOIN srs_aria_malabon_rest.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsnav' as br_code,'navotas' as branch,ad.*,gl.amount as amount FROM srs_aria_navotas.`0_gl_trans` as gl 
LEFT JOIN srs_aria_navotas.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsn' as br_code,'nova' as branch,ad.*,gl.amount as amount FROM srs_aria_nova.`0_gl_trans` as gl 
LEFT JOIN srs_aria_nova.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srspat' as br_code,'pateros' as branch,ad.*,gl.amount as amount FROM srs_aria_pateros.`0_gl_trans` as gl 
LEFT JOIN srs_aria_pateros.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srspun' as br_code,'punturin' as branch,ad.*,gl.amount as amount FROM srs_aria_punturin_val.`0_gl_trans` as gl 
LEFT JOIN srs_aria_punturin_val.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srssanp' as br_code,'san pedro' as branch,ad.*,gl.amount as amount FROM srs_aria_san_pedro.`0_gl_trans` as gl 
LEFT JOIN srs_aria_san_pedro.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srstu' as br_code, 'talon uno' as branch,ad.*,gl.amount as amount FROM srs_aria_talon_uno.`0_gl_trans` as gl 
LEFT JOIN srs_aria_talon_uno.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srst' as br_code,'tondo' as branch,ad.*,gl.amount as amount FROM srs_aria_tondo.`0_gl_trans` as gl 
LEFT JOIN srs_aria_tondo.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsval' as br_code,'valenzuela' as branch,ad.*,gl.amount as amount FROM srs_aria_valenzuela.`0_gl_trans` as gl 
LEFT JOIN srs_aria_valenzuela.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsmon' as br_code,'montalban' as branch,ad.*,gl.amount as amount FROM srs_aria_montalban.`0_gl_trans` as gl 
LEFT JOIN srs_aria_montalban.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

UNION ALL

SELECT 'srsman' as br_code,'mangahan' as branch,ad.*,gl.amount as amount FROM srs_aria_manggahan.`0_gl_trans` as gl 
LEFT JOIN srs_aria_manggahan.0_acquiring_deductions as ad
on gl.type_no=ad.p_ref_id
where gl.type=62 AND ad.reconciled = 0
and gl.account='".$bank_account_code."'
and gl.tran_date>='".date2sql($from)."'  and gl.tran_date<='".date2sql($to)."'
and gl.amount>0
GROUP BY ad.p_ref_id

) as ty";
	
	
		
	$res = db_query($sql);
	$res2 = db_query($sql);
	

	$checker = $count = 0;
	$last_bank_id = $last_check = 0;
	set_time_limit(0);
	
	$c_header2=$c_header;
	
	$header_total_count=count($c_header2)-1;
	
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);
		
		//$c_details[$count][0] = $del_dates;
		$c_details[$count][0] = $row['br_code'];
		$c_details[$count][1] = $row['tran_date'];
		$c_details[$count][2] = $row['memo_'];
		$c_details[$count][3] = $row['amount'];
		$c_details[$count][4] = 'UNRECONCILED';
		
		$total20+=$row['amount'];	
	}


	//====================================================HEADER-============================
	foreach ($c_header as $ind => $title){
		
		$rep->sheet20->writeString($rep->y, $ind, ($ind <= $c_header_last_index ? $title : html_entity_decode(get_gl_account_name($title))), $format_bold_title);
	}
	

	foreach($c_details as $i => $details)
	{
		$rep->y ++;
		foreach($details as $index => $det)
		{		
				if(is_numeric($det)){
					if($det==0){
						$rep->sheet20->writeString($rep->y, $index, '', $rep->formatLeft);
					}
					else{
						$rep->sheet20->writeNumber($rep->y, $index, $det, $rep->formatLeft);
					}
				
					
				}
				else{
					$rep->sheet20->writeString($rep->y, $index, $det, $rep->formatLeft);
				}
				
		}	

	}

	$rep->y ++;
	$rep->sheet20->writeString($rep->y, 2, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet20->writeNumber ($rep->y, 3, $total20, $format_bold_right);
		
	//}
	
	
	
		$rep->y++;
	#######################


    $rep->End();
}

?>


