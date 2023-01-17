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
	}elseif ($bank_account_code =='1020021') {
		$bank_table = '0_bank_statement_metro';
		$memo_ = '%Adjustment for METRO,%';
		$bank_name = 'METROBANK';
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

      $rep = new FrontReport(_('SUMMARY'), $bank_name." Disbursement Reconciliation Summary", "LETTER");

    $rep->Font();
	
	$rep->sheet->setColumn(0,0,50);
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
	$rep->sheet->writeString($rep->y, 0, $bank_name.' SUMMARY', $format_bold);
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
	$c_header = array('DESCRIPTION','BOOK','BANK','UNDER AND OVER DEPOSIT');
	$c_header_last_index = count($c_header)-1;
//	print_r($c_header_last_index = count($c_header)-1);
	
	// array_push($c_header,
	
// '".$bank_account_code."'
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
	SELECT * FROM cash_deposit.$bank_table
	where date_deposited>='".date2sql($from)."' and date_deposited<='".date2sql($to)."'
	and credit_amount!=0
	and cleared=1
	and type=101
	";*/


	$sql ="";

	if($bank_account_code =='10102299'){ //aub
		$sql .="		SELECT 'BOOK' as trans_types,'SUPPLIERS PAY RECONCILED' as description, 
					 SUM(g_amount) as amount, 0 as deposit_over
					FROM
					(select   'srs_aria_alaminos' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_alaminos.0_bank_trans  as bts
					INNER JOIN  srs_aria_alaminos.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_alaminos.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00') 
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL
					
					select   'srs_aria_graceville' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_graceville.0_bank_trans  as bts
					INNER JOIN  srs_aria_graceville.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_graceville.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00') 
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select      'srs_aria_antipolo_manalo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_antipolo_manalo.0_bank_trans  as bts
					INNER JOIN  srs_aria_antipolo_manalo.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_antipolo_manalo.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select     'srs_aria_antipolo_quezon' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_antipolo_quezon.0_bank_trans  as bts
					INNER JOIN  srs_aria_antipolo_quezon.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_antipolo_quezon.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select      'srs_aria_b_silang' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_b_silang.0_bank_trans  as bts
					INNER JOIN  srs_aria_b_silang.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_b_silang.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select     'srs_aria_bagumbong' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_bagumbong.0_bank_trans  as bts
					INNER JOIN  srs_aria_bagumbong.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_bagumbong.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select     'srs_aria_blum' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_blum.0_bank_trans  as bts
					INNER JOIN  srs_aria_blum.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_blum.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select     'srs_aria_cainta' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_cainta.0_bank_trans  as bts
					INNER JOIN  srs_aria_cainta.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_cainta.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select     'srs_aria_cainta_san_juan' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_cainta_san_juan.0_bank_trans  as bts
					INNER JOIN  srs_aria_cainta_san_juan.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_cainta_san_juan.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select     'srs_aria_camarin' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_camarin.0_bank_trans  as bts
					INNER JOIN  srs_aria_camarin.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_camarin.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select      'srs_aria_comembo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_comembo.0_bank_trans  as bts
					INNER JOIN  srs_aria_comembo.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_comembo.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select      'srs_aria_gala' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_gala.0_bank_trans  as bts
					INNER JOIN  srs_aria_gala.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_gala.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select     'srs_aria_hero' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_hero.0_bank_trans  as bts
					INNER JOIN  srs_aria_hero.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_hero.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_imus' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_imus.0_bank_trans  as bts
					INNER JOIN  srs_aria_imus.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_imus.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_malabon' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_malabon.0_bank_trans  as bts
					INNER JOIN  srs_aria_malabon.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_malabon.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_malabon_rest' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_malabon_rest.0_bank_trans  as bts
					INNER JOIN  srs_aria_malabon_rest.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_malabon_rest.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select      'srs_aria_molino' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_molino.0_bank_trans  as bts
					INNER JOIN  srs_aria_molino.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_molino.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



					UNION ALL

					select      'srs_aria_navotas' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_navotas.0_bank_trans  as bts
					INNER JOIN  srs_aria_navotas.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_navotas.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select      'srs_aria_nova' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_nova.0_bank_trans  as bts
					INNER JOIN  srs_aria_nova.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_nova.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select    'srs_aria_pateros' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_pateros.0_bank_trans  as bts
					INNER JOIN  srs_aria_pateros.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_pateros.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



					UNION ALL

					select 'srs_aria_punturin_val' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_punturin_val.0_bank_trans  as bts
					INNER JOIN  srs_aria_punturin_val.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_punturin_val.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select  'srs_aria_retail' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_retail.0_bank_trans  as bts
					INNER JOIN  srs_aria_retail.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_retail.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


					UNION ALL

					select  'srs_aria_san_pedro' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_san_pedro.0_bank_trans  as bts
					INNER JOIN  srs_aria_san_pedro.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_san_pedro.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



					UNION ALL

					select   'srs_aria_talon_uno' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_talon_uno.0_bank_trans  as bts
					INNER JOIN  srs_aria_talon_uno.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_talon_uno.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



					UNION ALL

					select 'srs_aria_tondo' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_tondo.0_bank_trans  as bts
					INNER JOIN  srs_aria_tondo.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_tondo.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



					UNION ALL

					select 'srs_aria_valenzuela' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_valenzuela.0_bank_trans  as bts
					INNER JOIN  srs_aria_valenzuela.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_valenzuela.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select 'srs_aria_montalban' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_montalban.0_bank_trans  as bts
					INNER JOIN  srs_aria_montalban.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_montalban.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					UNION ALL

					select 'srs_aria_manggahan' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_manggahan.0_bank_trans  as bts
					INNER JOIN  srs_aria_manggahan.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_manggahan.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
					and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

					)  as m

					UNION ALL

					SELECT 'BANK' as trans_types,'SUPPLIERS PAY RECONCILED' as description,-sum(debit_amount) as amount,sum(under_over_deposit) as deposit_over FROM cash_deposit.$bank_table
					where debit_amount > 0
					and cleared=1
					and type=22
					and date_deposited>='".date2sql($from)."'
					and date_deposited<='".date2sql($to)."'

					UNION ALL


					SELECT
					'BOOK' as trans_types,'SUPPLIERS PAY UNRECONCILED' as description, 
					-SUM(chk_amount) as amount, 0 as deposit_over
					FROM
					(select   'srs_aria_alaminos' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_alaminos.0_bank_trans  as bts
					INNER JOIN  srs_aria_alaminos.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_alaminos.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled = '0000-00-00' ) 
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'

					UNION ALL
					
					select      'srs_aria_graceville' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_graceville.0_bank_trans  as bts
					INNER JOIN  srs_aria_graceville.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_graceville.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select      'srs_aria_antipolo_manalo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_antipolo_manalo.0_bank_trans  as bts
					INNER JOIN  srs_aria_antipolo_manalo.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_antipolo_manalo.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_antipolo_quezon' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_antipolo_quezon.0_bank_trans  as bts
					INNER JOIN  srs_aria_antipolo_quezon.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_antipolo_quezon.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select      'srs_aria_b_silang' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_b_silang.0_bank_trans  as bts
					INNER JOIN  srs_aria_b_silang.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_b_silang.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_bagumbong' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_bagumbong.0_bank_trans  as bts
					INNER JOIN  srs_aria_bagumbong.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_bagumbong.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_blum' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_blum.0_bank_trans  as bts
					INNER JOIN  srs_aria_blum.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_blum.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_cainta' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_cainta.0_bank_trans  as bts
					INNER JOIN  srs_aria_cainta.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_cainta.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_cainta_san_juan' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_cainta_san_juan.0_bank_trans  as bts
					INNER JOIN  srs_aria_cainta_san_juan.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_cainta_san_juan.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_camarin' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_camarin.0_bank_trans  as bts
					INNER JOIN  srs_aria_camarin.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_camarin.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select      'srs_aria_comembo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_comembo.0_bank_trans  as bts
					INNER JOIN  srs_aria_comembo.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_comembo.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select      'srs_aria_gala' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_gala.0_bank_trans  as bts
					INNER JOIN  srs_aria_gala.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_gala.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


					UNION ALL

					select     'srs_aria_hero' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_hero.0_bank_trans  as bts
					INNER JOIN  srs_aria_hero.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_hero.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select     'srs_aria_imus' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_imus.0_bank_trans  as bts
					INNER JOIN  srs_aria_imus.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_imus.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00')
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select     'srs_aria_malabon' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_malabon.0_bank_trans  as bts
					INNER JOIN  srs_aria_malabon.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_malabon.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select     'srs_aria_malabon_rest' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_malabon_rest.0_bank_trans  as bts
					INNER JOIN  srs_aria_malabon_rest.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_malabon_rest.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select      'srs_aria_molino' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_molino.0_bank_trans  as bts
					INNER JOIN  srs_aria_molino.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_molino.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




					UNION ALL

					select      'srs_aria_navotas' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_navotas.0_bank_trans  as bts
					INNER JOIN  srs_aria_navotas.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_navotas.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select      'srs_aria_nova' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_nova.0_bank_trans  as bts
					INNER JOIN  srs_aria_nova.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_nova.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select    'srs_aria_pateros' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_pateros.0_bank_trans  as bts
					INNER JOIN  srs_aria_pateros.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_pateros.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00'  )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




					UNION ALL

					select 'srs_aria_punturin_val' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_punturin_val.0_bank_trans  as bts
					INNER JOIN  srs_aria_punturin_val.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_punturin_val.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select  'srs_aria_retail' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_retail.0_bank_trans  as bts
					INNER JOIN  srs_aria_retail.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_retail.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



					UNION ALL

					select  'srs_aria_san_pedro' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_san_pedro.0_bank_trans  as bts
					INNER JOIN  srs_aria_san_pedro.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_san_pedro.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




					UNION ALL

					select   'srs_aria_talon_uno' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_talon_uno.0_bank_trans  as bts
					INNER JOIN  srs_aria_talon_uno.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_talon_uno.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




					UNION ALL

					select 'srs_aria_tondo' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_tondo.0_bank_trans  as bts
					INNER JOIN  srs_aria_tondo.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_tondo.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




					UNION ALL

					select 'srs_aria_valenzuela' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_valenzuela.0_bank_trans  as bts
					INNER JOIN  srs_aria_valenzuela.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_valenzuela.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'

					UNION ALL

					select 'srs_aria_montalban' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_montalban.0_bank_trans  as bts
					INNER JOIN  srs_aria_montalban.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_montalban.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'

					UNION ALL

					select 'srs_aria_manggahan' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
					bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_manggahan.0_bank_trans  as bts
					INNER JOIN  srs_aria_manggahan.0_cheque_details as cds
					on bts.type = cds.type and  bts.id = cds.bank_trans_id
					INNER JOIN srs_aria_manggahan.0_gl_trans as gls ON
					gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
					and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
					and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




					)  as n";

	}elseif ($bank_account_code =='1020021') { //metro

		$sql .="SELECT 'BOOK' as trans_types,'SUPPLIERS PAY RECONCILED' as description, 
				 SUM(g_amount) as amount, 0 as deposit_over
				FROM
				(select   'srs_aria_alaminos' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_alaminos.0_bank_trans  as bts
				INNER JOIN srs_aria_alaminos.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00') 
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select   'srs_aria_graceville' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_graceville.0_bank_trans  as bts
				INNER JOIN srs_aria_graceville.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00') 
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select      'srs_aria_antipolo_manalo' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_antipolo_manalo.0_bank_trans  as bts
				INNER JOIN srs_aria_antipolo_manalo.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select     'srs_aria_antipolo_quezon' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_antipolo_quezon.0_bank_trans  as bts
				INNER JOIN srs_aria_antipolo_quezon.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select      'srs_aria_b_silang' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_b_silang.0_bank_trans  as bts
				INNER JOIN srs_aria_b_silang.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select     'srs_aria_bagumbong' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_bagumbong.0_bank_trans  as bts
				INNER JOIN srs_aria_bagumbong.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select     'srs_aria_blum' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_blum.0_bank_trans  as bts
				INNER JOIN srs_aria_blum.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select     'srs_aria_cainta' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_cainta.0_bank_trans  as bts
				INNER JOIN srs_aria_cainta.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select     'srs_aria_cainta_san_juan' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_cainta_san_juan.0_bank_trans  as bts
				INNER JOIN srs_aria_cainta_san_juan.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select     'srs_aria_camarin' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_camarin.0_bank_trans  as bts
				INNER JOIN srs_aria_camarin.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select      'srs_aria_comembo' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_comembo.0_bank_trans  as bts
				INNER JOIN srs_aria_comembo.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select      'srs_aria_gala' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_gala.0_bank_trans  as bts
				INNER JOIN srs_aria_gala.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select     'srs_aria_hero' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_hero.0_bank_trans  as bts
				INNER JOIN srs_aria_hero.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_imus' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_imus.0_bank_trans  as bts
				INNER JOIN srs_aria_imus.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_malabon' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_malabon.0_bank_trans  as bts
				INNER JOIN srs_aria_malabon.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_malabon_rest' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_malabon_rest.0_bank_trans  as bts
				INNER JOIN srs_aria_malabon_rest.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select      'srs_aria_molino' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_molino.0_bank_trans  as bts
				INNER JOIN srs_aria_molino.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



				UNION ALL

				select      'srs_aria_navotas' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_navotas.0_bank_trans  as bts
				INNER JOIN srs_aria_navotas.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select      'srs_aria_nova' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_nova.0_bank_trans  as bts
				INNER JOIN srs_aria_nova.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select    'srs_aria_pateros' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_pateros.0_bank_trans  as bts
				INNER JOIN srs_aria_pateros.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



				UNION ALL

				select 'srs_aria_punturin_val' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_punturin_val.0_bank_trans  as bts
				INNER JOIN srs_aria_punturin_val.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select  'srs_aria_retail' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_retail.0_bank_trans  as bts
				INNER JOIN srs_aria_retail.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


				UNION ALL

				select  'srs_aria_san_pedro' as branch_,  bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_san_pedro.0_bank_trans  as bts
				INNER JOIN srs_aria_san_pedro.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



				UNION ALL

				select   'srs_aria_talon_uno' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_talon_uno.0_bank_trans  as bts
				INNER JOIN srs_aria_talon_uno.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



				UNION ALL

				select 'srs_aria_tondo' as branch_,  bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_tondo.0_bank_trans  as bts
				INNER JOIN srs_aria_tondo.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



				UNION ALL

				select 'srs_aria_valenzuela' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_valenzuela.0_bank_trans  as bts
				INNER JOIN srs_aria_valenzuela.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select 'srs_aria_montalban' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_montalban.0_bank_trans  as bts
				INNER JOIN srs_aria_montalban.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				UNION ALL

				select 'srs_aria_manggahan' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, gls.amount as g_amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_manggahan.0_bank_trans  as bts
				INNER JOIN srs_aria_manggahan.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no WHERE gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
				and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

				)  as m

				UNION ALL

				SELECT 'BANK' as trans_types,'SUPPLIERS PAY RECONCILED' as description,-sum(debit_amount) as amount,sum(under_over_deposit) as deposit_over FROM cash_deposit.$bank_table
				where debit_amount > 0
				and cleared=1
				and type=22
				and date_deposited>='".date2sql($from)."'
				and date_deposited<='".date2sql($to)."'

				UNION ALL


				SELECT
				'BOOK' as trans_types,'SUPPLIERS PAY UNRECONCILED' as description, 
				-SUM(amount) as amount, 0 as deposit_over
				FROM
				(select   'srs_aria_alaminos' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_alaminos.0_bank_trans  as bts
				INNER JOIN srs_aria_alaminos.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled = '0000-00-00' ) 
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'

				UNION ALL

				select      'srs_aria_graceville' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_graceville.0_bank_trans  as bts
				INNER JOIN srs_aria_graceville.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select      'srs_aria_antipolo_manalo' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_antipolo_manalo.0_bank_trans  as bts
				INNER JOIN srs_aria_antipolo_manalo.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_antipolo_quezon' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_antipolo_quezon.0_bank_trans  as bts
				INNER JOIN srs_aria_antipolo_quezon.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select      'srs_aria_b_silang' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_b_silang.0_bank_trans  as bts
				INNER JOIN srs_aria_b_silang.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_bagumbong' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_bagumbong.0_bank_trans  as bts
				INNER JOIN srs_aria_bagumbong.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_blum' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_blum.0_bank_trans  as bts
				INNER JOIN srs_aria_blum.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_cainta' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_cainta.0_bank_trans  as bts
				INNER JOIN srs_aria_cainta.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_cainta_san_juan' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_cainta_san_juan.0_bank_trans  as bts
				INNER JOIN srs_aria_cainta_san_juan.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_camarin' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_camarin.0_bank_trans  as bts
				INNER JOIN srs_aria_camarin.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select      'srs_aria_comembo' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_comembo.0_bank_trans  as bts
				INNER JOIN srs_aria_comembo.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select      'srs_aria_gala' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_gala.0_bank_trans  as bts
				INNER JOIN srs_aria_gala.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select     'srs_aria_hero' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_hero.0_bank_trans  as bts
				INNER JOIN srs_aria_hero.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select     'srs_aria_imus' as branch_ ,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_imus.0_bank_trans  as bts
				INNER JOIN srs_aria_imus.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00')
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select     'srs_aria_malabon' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_malabon.0_bank_trans  as bts
				INNER JOIN srs_aria_malabon.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select     'srs_aria_malabon_rest' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_malabon_rest.0_bank_trans  as bts
				INNER JOIN srs_aria_malabon_rest.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select      'srs_aria_molino' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_molino.0_bank_trans  as bts
				INNER JOIN srs_aria_molino.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




				UNION ALL

				select      'srs_aria_navotas' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_navotas.0_bank_trans  as bts
				INNER JOIN srs_aria_navotas.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select      'srs_aria_nova' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_nova.0_bank_trans  as bts
				INNER JOIN srs_aria_nova.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select    'srs_aria_pateros' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_pateros.0_bank_trans  as bts
				INNER JOIN srs_aria_pateros.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00'  )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




				UNION ALL

				select 'srs_aria_punturin_val' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_punturin_val.0_bank_trans  as bts
				INNER JOIN srs_aria_punturin_val.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select  'srs_aria_retail' as branch_,bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_retail.0_bank_trans  as bts
				INNER JOIN srs_aria_retail.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'



				UNION ALL

				select  'srs_aria_san_pedro' as branch_,  bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_san_pedro.0_bank_trans  as bts
				INNER JOIN srs_aria_san_pedro.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




				UNION ALL

				select   'srs_aria_talon_uno' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_talon_uno.0_bank_trans  as bts
				INNER JOIN srs_aria_talon_uno.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




				UNION ALL

				select 'srs_aria_tondo' as branch_,  bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_tondo.0_bank_trans  as bts
				INNER JOIN srs_aria_tondo.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




				UNION ALL

				select 'srs_aria_valenzuela' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_valenzuela.0_bank_trans  as bts
				INNER JOIN srs_aria_valenzuela.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select 'srs_aria_montalban' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_montalban.0_bank_trans  as bts
				INNER JOIN srs_aria_montalban.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'


				UNION ALL

				select 'srs_aria_manggahan' as branch_, bts.bank_act,bts.trans_date, bts.id as b_id, 
				bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_manggahan.0_bank_trans  as bts
				INNER JOIN srs_aria_manggahan.0_gl_trans as gls ON
				gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='".$bank_account_code."'
				and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled  = '0000-00-00' )
				and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'




				)  as n ";
	}



	$sql .="


		UNION ALL


		SELECT 'BOOK' as trans_types,'JV RECONCILED' as description,sum(amount) as amount, 0 as deposit_over FROM
		(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
		FROM srs_aria_retail.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and  dimension_id  ='99'
		UNION ALL

		SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
		FROM srs_aria_antipolo_quezon.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0   and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
		FROM srs_aria_antipolo_manalo.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
		FROM srs_aria_nova.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
		FROM srs_aria_cainta.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
		FROM srs_aria_camarin.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
		FROM srs_aria_blum.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
		FROM srs_aria_malabon.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
		FROM srs_aria_navotas.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
		FROM srs_aria_imus.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
		FROM srs_aria_gala.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_graceville' as br_code,'srs_aria_graceville' as branch, 0_gl_trans.*
		FROM srs_aria_graceville.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
		FROM srs_aria_tondo.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'
		UNION ALL

		SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
		FROM srs_aria_valenzuela.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
		FROM srs_aria_malabon_rest.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
		FROM srs_aria_b_silang.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
		FROM srs_aria_punturin_val.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
		FROM srs_aria_pateros.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
		FROM srs_aria_comembo.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
		FROM srs_aria_cainta_san_juan.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
		FROM srs_aria_san_pedro.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
		FROM srs_aria_alaminos.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
		FROM srs_aria_talon_uno.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  ='99'

		UNION ALL

		SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
		FROM srs_aria_bagumbong.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  ='99'


		UNION ALL

		SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
		FROM srs_aria_manggahan.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  ='99'


		UNION ALL

		SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
		FROM srs_aria_montalban.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  ='99'


		) as o

		UNION ALL

		SELECT 'BANK' as trans_types,'JV RECONCILED' as description,sum(debit_amount) as amount,sum(under_over_deposit) as deposit_over FROM cash_deposit.$bank_table
		where debit_amount > 0
		and cleared=1
		and type=0
		and date_deposited>='2016-01-01'
		and date_deposited<='2016-12-31'

		UNION ALL 


		SELECT 'BOOK' as trans_types,'JV UNRECONCILED' as description,sum(amount) as amount, 0 as deposit_over FROM
		(SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
		FROM srs_aria_retail.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and  dimension_id  !='99'
		UNION ALL

		SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
		FROM srs_aria_antipolo_quezon.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0   and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
		FROM srs_aria_antipolo_manalo.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
		FROM srs_aria_nova.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
		FROM srs_aria_cainta.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
		FROM srs_aria_camarin.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
		FROM srs_aria_blum.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
		FROM srs_aria_malabon.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id !='99'

		UNION ALL

		SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
		FROM srs_aria_navotas.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
		FROM srs_aria_imus.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
		FROM srs_aria_gala.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_graceville' as br_code,'srs_aria_graceville' as branch, 0_gl_trans.*
		FROM srs_aria_graceville.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
		FROM srs_aria_tondo.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'
		UNION ALL

		SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
		FROM srs_aria_valenzuela.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id !='99'

		UNION ALL

		SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
		FROM srs_aria_malabon_rest.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
		FROM srs_aria_b_silang.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
		FROM srs_aria_punturin_val.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
		FROM srs_aria_pateros.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
		FROM srs_aria_comembo.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
		FROM srs_aria_cainta_san_juan.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id !='99'

		UNION ALL

		SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
		FROM srs_aria_san_pedro.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
		FROM srs_aria_alaminos.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id !='99'

		UNION ALL

		SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
		FROM srs_aria_talon_uno.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0  and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
		FROM srs_aria_bagumbong.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  !='99'

		UNION ALL

		SELECT 'srs_aria_manggahan' as br_code,'srs_aria_manggahan' as branch, 0_gl_trans.*
		FROM srs_aria_manggahan.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  !='99'


		UNION ALL

		SELECT 'srs_aria_montalban' as br_code,'srs_aria_montalban' as branch, 0_gl_trans.*
		FROM srs_aria_montalban.0_gl_trans
		WHERE tran_date >= '".date2sql($from)."'
		AND  tran_date <= '".date2sql($to)."'
		AND account ='".$bank_account_code."' AND amount<0 AND type=0 and dimension_id  !='99') as p

		UNION ALL


		SELECT 'BANK' as trans_types,'BANK DEBIT UNRECONCILED' as description, -sum(debit_amount) as amount,sum(under_over_deposit) as deposit_over FROM cash_deposit.$bank_table
		where debit_amount!=0
		and cleared=0
		and date_deposited>='".date2sql($from)."'
		and date_deposited<='".date2sql($to)."'

		UNION ALL

		SELECT 'BANK' as trans_types,'REVERSAL RECONCILED' as description,-sum(credit_amount) as amount,sum(under_over_deposit) as deposit_over FROM cash_deposit.$bank_table
		where credit_amount!=0
		and cleared=1
		and type=99
		and date_deposited>='".date2sql($from)."'
		and date_deposited<='".date2sql($to)."'

 ";
		
	// display_error($sql); die;
		
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
	$old_description = '';
	while($row = db_fetch($res))
	{
		$count ++;

		$date_to_ = explode_date_to_dmy($to);

		$deposit = '';
		if ($row['deposit_over'] == 0) {

			$deposit = '0';

		}else{

			$deposit = $row['deposit_over'];
		}

		if($old_description !=  $row['description']){
			$c_details[$count][0] = $row['description'];
			$c_details[$count][3] = $deposit;

		}
		$new_description = $row['description'];
		
		if($row['trans_types']=='BOOK'){
			$c_details[$count][1] = $row['amount'];
			$c_details[$count][2] = '';
			$c_details[$count][3] = $deposit;

			
			$total5+=$row['amount'];
		}
		else{
			if($old_description == $new_description){
				$c_details[$count-1][2] = $row['amount'];
				$c_details[$count-1][3] = $deposit;


			}else{
				$c_details[$count][1] = '';
				$c_details[$count][2] = $row['amount'];	
				$c_details[$count][3] = $deposit;

			}
			
			
			$total6+=$row['amount'];
		}
		$old_description =  $row['description'];
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
	$rep->sheet->writeString($rep->y, 0, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet->writeNumber ($rep->y, 1, $total5, $format_bold_right);
		$rep->sheet->writeNumber ($rep->y, 2, $total6, $format_bold_right);
		
	//}

	$rep->y++;
	
    $rep->End();
}

?>