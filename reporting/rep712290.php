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

      $rep = new FrontReport(_('BOOK_RECONCILED'), "AUB_BankRecon_CHECK", "LETTER");

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
	$rep->sheet->writeString($rep->y, 0, 'AUB Bank Recon (Payment)', $format_bold);
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
	$c_header = array('BRANCH','Trans #','GL DATE','CHECK #','AMOUNT','DATE RECONCILED','STATUS');
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
	
	//AND g.tran_date >='".date2sql($from)."' AND g.tran_date <='".date2sql($to)."'
	
$sql="
SELECT
a.chk_amount,
a.chk_number,
a.*
FROM
(select   'srs_aria_alaminos' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_alaminos.0_bank_trans  as bts
INNER JOIN  srs_aria_alaminos.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_alaminos.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00') 
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select      'srs_aria_antipolo_manalo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_antipolo_manalo.0_bank_trans  as bts
INNER JOIN  srs_aria_antipolo_manalo.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_antipolo_manalo.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_antipolo_quezon' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_antipolo_quezon.0_bank_trans  as bts
INNER JOIN  srs_aria_antipolo_quezon.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_antipolo_quezon.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select      'srs_aria_b_silang' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_b_silang.0_bank_trans  as bts
INNER JOIN  srs_aria_b_silang.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_b_silang.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_bagumbong' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_bagumbong.0_bank_trans  as bts
INNER JOIN  srs_aria_bagumbong.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_bagumbong.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_blum' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_blum.0_bank_trans  as bts
INNER JOIN  srs_aria_blum.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_blum.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_cainta' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_cainta.0_bank_trans  as bts
INNER JOIN  srs_aria_cainta.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_cainta.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_cainta_san_juan' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_cainta_san_juan.0_bank_trans  as bts
INNER JOIN  srs_aria_cainta_san_juan.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_cainta_san_juan.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_camarin' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_camarin.0_bank_trans  as bts
INNER JOIN  srs_aria_camarin.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_camarin.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select      'srs_aria_comembo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_comembo.0_bank_trans  as bts
INNER JOIN  srs_aria_comembo.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_comembo.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select      'srs_aria_gala' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_gala.0_bank_trans  as bts
INNER JOIN  srs_aria_gala.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_gala.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_hero' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_hero.0_bank_trans  as bts
INNER JOIN  srs_aria_hero.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_hero.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select     'srs_aria_imus' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_imus.0_bank_trans  as bts
INNER JOIN  srs_aria_imus.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_imus.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select     'srs_aria_malabon' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_malabon.0_bank_trans  as bts
INNER JOIN  srs_aria_malabon.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_malabon.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select     'srs_aria_malabon_rest' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_malabon_rest.0_bank_trans  as bts
INNER JOIN  srs_aria_malabon_rest.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_malabon_rest.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select      'srs_aria_molino' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_molino.0_bank_trans  as bts
INNER JOIN  srs_aria_molino.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_molino.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select      'srs_aria_navotas' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_navotas.0_bank_trans  as bts
INNER JOIN  srs_aria_navotas.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_navotas.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select      'srs_aria_nova' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_nova.0_bank_trans  as bts
INNER JOIN  srs_aria_nova.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_nova.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select    'srs_aria_pateros' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_pateros.0_bank_trans  as bts
INNER JOIN  srs_aria_pateros.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_pateros.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select 'srs_aria_punturin_val' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_punturin_val.0_bank_trans  as bts
INNER JOIN  srs_aria_punturin_val.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_punturin_val.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select  'srs_aria_retail' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_retail.0_bank_trans  as bts
INNER JOIN  srs_aria_retail.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_retail.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select  'srs_aria_san_pedro' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_san_pedro.0_bank_trans  as bts
INNER JOIN  srs_aria_san_pedro.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_san_pedro.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select   'srs_aria_talon_uno' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_talon_uno.0_bank_trans  as bts
INNER JOIN  srs_aria_talon_uno.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_talon_uno.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select 'srs_aria_tondo' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_tondo.0_bank_trans  as bts
INNER JOIN  srs_aria_tondo.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_tondo.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select 'srs_aria_valenzuela' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_valenzuela.0_bank_trans  as bts
INNER JOIN  srs_aria_valenzuela.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_valenzuela.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and !(ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled='0000-00-00')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."') 
as a  

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
		$c_details[$count][0] = $row['branch_'];
		$c_details[$count][1] = $row['type_no'];
		$c_details[$count][2] = sql2date($row['tdate']);

		if(is_null($row['chk_number'])){
			$c_details[$count][3] = 'ONLINE';
		}else{
			$c_details[$count][3] = $row['chk_number'];
		}
		
		$c_details[$count][4] = $row['chk_amount'];
		$c_details[$count][5] = sql2date($row['reconciled']);
		$c_details[$count][6] = 'RECONCILED';
		
		$total1+=$row['chk_amount'];			
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
	$rep->sheet->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet->writeNumber ($rep->y, 7, $total1, $format_bold_right);
		
	//}

	$rep->y++;
	
		$rep->sheet2 = $rep->addWorksheet('BOOK_OUTSTANDING_&_STALED');
	
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
	$rep->sheet2->writeString($rep->y, 0, 'AUB Bank Recon (Payment)', $format_bold);
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
	$c_header = array('BRANCH','TRANS #','DATE','CHECK #','AMOUNT', 'RECONCILED','DATE RECONCILED','STATUS');
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

SELECT
a.chk_amount,
a.chk_number,
a.*
FROM
(select   'srs_aria_alaminos' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_alaminos.0_bank_trans  as bts
INNER JOIN  srs_aria_alaminos.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_alaminos.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."') 
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select      'srs_aria_antipolo_manalo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_antipolo_manalo.0_bank_trans  as bts
INNER JOIN  srs_aria_antipolo_manalo.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_antipolo_manalo.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_antipolo_quezon' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_antipolo_quezon.0_bank_trans  as bts
INNER JOIN  srs_aria_antipolo_quezon.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_antipolo_quezon.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select      'srs_aria_b_silang' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_b_silang.0_bank_trans  as bts
INNER JOIN  srs_aria_b_silang.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_b_silang.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_bagumbong' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_bagumbong.0_bank_trans  as bts
INNER JOIN  srs_aria_bagumbong.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_bagumbong.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_blum' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_blum.0_bank_trans  as bts
INNER JOIN  srs_aria_blum.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_blum.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_cainta' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_cainta.0_bank_trans  as bts
INNER JOIN  srs_aria_cainta.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_cainta.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_cainta_san_juan' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_cainta_san_juan.0_bank_trans  as bts
INNER JOIN  srs_aria_cainta_san_juan.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_cainta_san_juan.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_camarin' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_camarin.0_bank_trans  as bts
INNER JOIN  srs_aria_camarin.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_camarin.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select      'srs_aria_comembo' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_comembo.0_bank_trans  as bts
INNER JOIN  srs_aria_comembo.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_comembo.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select      'srs_aria_gala' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_gala.0_bank_trans  as bts
INNER JOIN  srs_aria_gala.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_gala.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'

UNION ALL

select     'srs_aria_hero' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_hero.0_bank_trans  as bts
INNER JOIN  srs_aria_hero.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_hero.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select     'srs_aria_imus' as branch_ ,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_imus.0_bank_trans  as bts
INNER JOIN  srs_aria_imus.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_imus.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select     'srs_aria_malabon' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_malabon.0_bank_trans  as bts
INNER JOIN  srs_aria_malabon.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_malabon.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select     'srs_aria_malabon_rest' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_malabon_rest.0_bank_trans  as bts
INNER JOIN  srs_aria_malabon_rest.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_malabon_rest.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select      'srs_aria_molino' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_molino.0_bank_trans  as bts
INNER JOIN  srs_aria_molino.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_molino.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select      'srs_aria_navotas' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_navotas.0_bank_trans  as bts
INNER JOIN  srs_aria_navotas.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_navotas.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select      'srs_aria_nova' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_nova.0_bank_trans  as bts
INNER JOIN  srs_aria_nova.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_nova.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select    'srs_aria_pateros' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_pateros.0_bank_trans  as bts
INNER JOIN  srs_aria_pateros.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_pateros.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select 'srs_aria_punturin_val' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no  from srs_aria_punturin_val.0_bank_trans  as bts
INNER JOIN  srs_aria_punturin_val.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_punturin_val.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select  'srs_aria_retail' as branch_,cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_retail.0_bank_trans  as bts
INNER JOIN  srs_aria_retail.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_retail.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'


UNION ALL

select  'srs_aria_san_pedro' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_san_pedro.0_bank_trans  as bts
INNER JOIN  srs_aria_san_pedro.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_san_pedro.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select   'srs_aria_talon_uno' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_talon_uno.0_bank_trans  as bts
INNER JOIN  srs_aria_talon_uno.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_talon_uno.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select 'srs_aria_tondo' as branch_,  cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_tondo.0_bank_trans  as bts
INNER JOIN  srs_aria_tondo.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_tondo.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled<='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."'



UNION ALL

select 'srs_aria_valenzuela' as branch_, cds.*,bts.bank_act,bts.trans_date, bts.id as b_id, 
bts.amount, bts.reconciled,gls.tran_date as tdate,gls.type as typew, gls.type_no   from srs_aria_valenzuela.0_bank_trans  as bts
INNER JOIN  srs_aria_valenzuela.0_cheque_details as cds
on bts.type = cds.type and  bts.id = cds.bank_trans_id
INNER JOIN srs_aria_valenzuela.0_gl_trans as gls ON
gls.type = bts.type  and gls.type_no =  bts.trans_no and gls.account ='10102299'
and gls.type =22 and bts.type = 22 and gls.amount < 0 and (ISNULL(bts.reconciled) OR bts.reconciled='' OR  bts.reconciled <='".date2sql($to)."')
and gls.tran_date >='".date2sql($from)."' and gls.tran_date <='".date2sql($to)."'
and reconciled <> '' and bts.reconciled >='".date2sql($from)."' and bts.reconciled <='".date2sql($to)."') 
as a  


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
		$c_details[$count][0] = $row['branch_'];
		$c_details[$count][1] = sql2date($row['type_no']);
		$c_details[$count][2] = sql2date($row['tdate']);


		if(is_null($row['chk_number'])){
			$c_details[$count][3] = 'ONLINE';
		}else{
			$c_details[$count][3] = $row['chk_number'];
		}
		
		$c_details[$count][4] = sql2date($row['chk_amount']);
		$c_details[$count][5] = sql2date($row['reconciled']);		
		
		$mos = date_diff2($to, end_month(sql2date($row['tdate'])), 'm');
			
		if($mos>=6){
			$c_details[$count][6] = 'STALED CHECKS';
		}
		else{
			$c_details[$count][6] = 'OUTSTANDING CHECKS';
		}
		
		
		
		$total2+=$row['chk_amount'];	
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
	$rep->sheet2->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet2->writeNumber ($rep->y, 7, $total2, $format_bold_right);
		
	//}

	$rep->y++;
	
			$rep->sheet3 = $rep->addWorksheet('BANK_OTHERS_&_UNRECORDED');
	
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
	$rep->sheet3->writeString($rep->y, 0, 'AUB Bank Recon (Payment)', $format_bold);
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
	$c_header = array('BRANCH','GL DATE','CV DATE','CV NO.', 'TXN code','Ref Num', 'CHECK DATE','Debit AMOUNT','DATE RECONCILED','STATUS');
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


SELECT * FROM cash_deposit.0_bank_statement_aub
where date_deposited>='".date2sql($from)."'
and date_deposited<='".date2sql($to)."'
and debit_amount!=0
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
		$c_details[$count][1] = sql2date($row['date_deposited']);
		$c_details[$count][2] = sql2date($row['cv_date']);
		$c_details[$count][3] = $row['cv_no'];
		$c_details[$count][4] =$row['deposit_type'];
		$c_details[$count][5] = $row['bank_ref_num'];
		$c_details[$count][6] = sql2date($row['g_tran_date']);
		$c_details[$count][7] = $row['debit_amount'];
		$c_details[$count][8] = sql2date($row['reconciled']);
		$c_details[$count][9] = 'UNRECORDED';
		
		$total3+=$row['debit_amount'];		

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
	$rep->sheet3->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet3->writeNumber ($rep->y, 7, $total3, $format_bold_right);
		
	//}
	
			$rep->y++;
	
			$rep->sheet4 = $rep->addWorksheet('BOOK_JOURNAL BY IT');
	
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
	$rep->sheet4->writeString($rep->y, 0, 'AUB Bank Recon (Payment)', $format_bold);
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
	$c_header = array('BRANCH','GL DATE','CV DATE','CV NO.', 'Memo','Ref Num', 'CHECK DATE','Debit AMOUNT','DATE RECONCILED','STATUS');
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

SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ like '%Adjustment for AUB,%'

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
		$c_details[$count][1] = sql2date($row['tran_date']);
		$c_details[$count][2] = sql2date($row['cv_date']);
		$c_details[$count][3] = $row['cv_no'];
		$c_details[$count][4] =$row['memo_'];
		$c_details[$count][5] = $row['bank_ref_num'];
		$c_details[$count][6] = sql2date($row['g_tran_date']);
		$c_details[$count][7] = $row['amount'];
		$c_details[$count][8] = sql2date($row['reconciled']);
		$c_details[$count][9] = 'JOURNAL';
		
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
	$rep->sheet4->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet4->writeNumber ($rep->y, 7, $total4, $format_bold_right);
		
	//}

	$rep->y++;

	$rep->sheet5 = $rep->addWorksheet('BOOK_JOURNAL');
	
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
	$rep->sheet5->writeString($rep->y, 0, 'AUB Bank Recon (Payment)', $format_bold);
	$rep->y ++;

	$rep->sheet5->writeString($rep->y, 0, $to, $format_bold);
	$rep->y ++;

	// $rep->sheet4->setMerge(0,0,0,3);
	// $rep->sheet4->setMerge(1,0,1,3);
	// $rep->sheet4->setMerge(2,0,2,3);
	// $rep->sheet4->setMerge(4,0,4,3);
	// $rep->sheet4->setMerge(7,0,7,3);
//===================================================================================

	// get the header
	$c_header = array('BRANCH','GL DATE','CV DATE','CV NO.', 'Memo','Ref Num', 'CHECK DATE','Debit AMOUNT','DATE RECONCILED','STATUS');
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

SELECT 'srs_aria_retail' as br_code,'srs_aria_retail' as branch, 0_gl_trans.*
FROM srs_aria_retail.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_antipolo_quezon' as br_code,'srs_aria_antipolo_quezon' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_quezon.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_antipolo_manalo' as br_code,'srs_aria_antipolo_manalo' as branch, 0_gl_trans.*
FROM srs_aria_antipolo_manalo.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_nova' as br_code,'srs_aria_nova' as branch, 0_gl_trans.*
FROM srs_aria_nova.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_cainta' as br_code,'srs_aria_cainta' as branch, 0_gl_trans.*
FROM srs_aria_cainta.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_camarin' as br_code,'srs_aria_camarin' as branch, 0_gl_trans.*
FROM srs_aria_camarin.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_blum' as br_code,'srs_aria_blum' as branch, 0_gl_trans.*
FROM srs_aria_blum.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_malabon' as br_code,'srs_aria_malabon' as branch, 0_gl_trans.*
FROM srs_aria_malabon.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_navotas' as br_code,'srs_aria_navotas' as branch, 0_gl_trans.*
FROM srs_aria_navotas.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_imus' as br_code,'srs_aria_imus' as branch, 0_gl_trans.*
FROM srs_aria_imus.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_gala' as br_code,'srs_aria_gala' as branch, 0_gl_trans.*
FROM srs_aria_gala.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_tondo' as br_code,'srs_aria_tondo' as branch, 0_gl_trans.*
FROM srs_aria_tondo.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_valenzuela' as br_code,'srs_aria_valenzuela' as branch, 0_gl_trans.*
FROM srs_aria_valenzuela.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_malabon_rest' as br_code,'srs_aria_malabon_rest' as branch, 0_gl_trans.*
FROM srs_aria_malabon_rest.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_b_silang' as br_code,'srs_aria_b_silang' as branch, 0_gl_trans.*
FROM srs_aria_b_silang.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_punturin_val' as br_code,'srs_aria_punturin_val' as branch, 0_gl_trans.*
FROM srs_aria_punturin_val.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_pateros' as br_code,'srs_aria_pateros' as branch, 0_gl_trans.*
FROM srs_aria_pateros.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_comembo' as br_code,'srs_aria_comembo' as branch, 0_gl_trans.*
FROM srs_aria_comembo.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_cainta_san_juan' as br_code,'srs_aria_cainta_san_juan' as branch, 0_gl_trans.*
FROM srs_aria_cainta_san_juan.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_san_pedro' as br_code,'srs_aria_san_pedro' as branch, 0_gl_trans.*
FROM srs_aria_san_pedro.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_alaminos' as br_code,'srs_aria_alaminos' as branch, 0_gl_trans.*
FROM srs_aria_alaminos.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_talon_uno' as br_code,'srs_aria_talon_uno' as branch, 0_gl_trans.*
FROM srs_aria_talon_uno.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

UNION ALL

SELECT 'srs_aria_bagumbong' as br_code,'srs_aria_bagumbong' as branch, 0_gl_trans.*
FROM srs_aria_bagumbong.0_gl_trans
WHERE tran_date >= '2016-01-01'
AND  tran_date <= '2016-12-31'
AND account ='10102299' AND amount<0 AND type=0 and memo_ not like '%Adjustment for AUB,%'

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
		$c_details[$count][1] = sql2date($row['tran_date']);
		$c_details[$count][2] = sql2date($row['cv_date']);
		$c_details[$count][3] = $row['cv_no'];
		$c_details[$count][4] =$row['memo_'];
		$c_details[$count][5] = $row['bank_ref_num'];
		$c_details[$count][6] = sql2date($row['g_tran_date']);
		$c_details[$count][7] = $row['amount'];
		$c_details[$count][8] = sql2date($row['reconciled']);
		$c_details[$count][9] = 'JOURNAL';
		
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
	$rep->sheet5->writeString($rep->y, 6, 'TOTAL', $format_bold_right);
	$qwert = $rep->y;
	// foreach ($c_totals as $ind => $total)
	// {
		$rep->sheet5->writeNumber ($rep->y, 7, $total5, $format_bold_right);
		
	//}

	$rep->y++;
	
	
    $rep->End();
}

?>