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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	List of Journal Entries
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");

function get_supplier_apv_transactions($is_order_by_apv_no,$apv_no,$po_no,$receiving_no,$invoice_no,$supplier,$from_date, $to_date, $trans_no=0,
	$account=null, $dimension=0, $dimension2=0, $filter_type=null,
	$amount_min=null, $amount_max=null)
{
	$from = date2sql($from_date);
	$to = date2sql($to_date);

	$sql = "SELECT ".TB_PREF."gl_trans.*, "
		.TB_PREF."chart_master.account_name ,
		".TB_PREF."supp_trans.reference,".TB_PREF."supp_trans.supp_reference,".TB_PREF."supp_trans.special_reference
		FROM ".TB_PREF."gl_trans, "
		.TB_PREF."chart_master,".TB_PREF."supp_trans
		WHERE 
		".TB_PREF."chart_master.account_code=".TB_PREF."gl_trans.account
		AND ".TB_PREF."gl_trans.type_no=".TB_PREF."supp_trans.trans_no
		AND ".TB_PREF."gl_trans.type=".TB_PREF."supp_trans.type
		AND ".TB_PREF."gl_trans.tran_date >= '$from'
		AND ".TB_PREF."gl_trans.tran_date <= '$to'
		AND ".TB_PREF."supp_trans.special_reference!=''
		
		";
	if ($trans_no > 0)
		$sql .= " AND ".TB_PREF."gl_trans.type_no LIKE ".db_escape('%'.$trans_no);

	if ($supplier > 0)
		$sql .= " AND ".TB_PREF."gl_trans.person_id = ".db_escape($supplier);
	
	if ($account != null)
		$sql .= " AND ".TB_PREF."gl_trans.account = ".db_escape($account);

	if ($dimension != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension_id = ".($dimension<0?0:db_escape($dimension));

	if ($dimension2 != 0)
  		$sql .= " AND ".TB_PREF."gl_trans.dimension2_id = ".($dimension2<0?0:db_escape($dimension2));

	if ($filter_type != null AND is_numeric($filter_type))
		$sql .= " AND ".TB_PREF."gl_trans.type= ".db_escape($filter_type);
	
	if ($apv_no != null)
		$sql .= " AND ".TB_PREF."gl_trans.type_no = ".db_escape($apv_no);
	
	if ($po_no != null)
		$sql .= " AND ".TB_PREF."supp_trans.special_reference = ".db_escape($po_no);
	
	if ($receiving_no != null)
		$sql .= " AND ".TB_PREF."supp_trans.reference  = ".db_escape($receiving_no);
	
	if ($invoice_no != null)
		$sql .= " AND ".TB_PREF."supp_trans.supp_reference LIKE ".db_escape('%'.$invoice_no);
		
	if ($amount_min != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) >= ABS(".db_escape($amount_min).")";
	
	if ($amount_max != null)
		$sql .= " AND ABS(".TB_PREF."gl_trans.amount) <= ABS(".db_escape($amount_max).")";


	if ($is_order_by_apv_no==1){
		$sql .= " AND ".TB_PREF."gl_trans.amount!='0' ORDER BY ".TB_PREF."gl_trans.type_no";
	}
	else{
		$sql .= " AND ".TB_PREF."gl_trans.amount!='0' ORDER BY ".TB_PREF."gl_trans.person_id, ".TB_PREF."gl_trans.tran_date, ".TB_PREF."gl_trans.counter";
	}

	
	//display_error($sql);
	return db_query($sql, "The transactions for could not be retrieved");
}

//----------------------------------------------------------------------------------------------------

print_list_of_journal_entries();

//----------------------------------------------------------------------------------------------------

function print_list_of_journal_entries()
{
    global $path_to_root, $systypes_array;
    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $supplier = $_POST['PARAM_2'];
	$is_order_by_apv_no = $_POST['PARAM_3'];
	$apv_no = $_POST['PARAM_4'];
	$po_no = $_POST['PARAM_5'];
	$receiving_no = $_POST['PARAM_6'];
	$invoice_no = $_POST['PARAM_7'];
	$destination = $_POST['PARAM_8'];
	
	$systype=ST_SUPPINVOICE;
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

    $cols = array(0, 100, 240, 300, 400, 460, 520, 580);

    $headers = array(_('Type/Account'), _('PO#').' / '._('RR#').' / '._('INV#'), _('Date'),
    	_('Supplier'), _('Debit'), _('Credit'));

    $aligns = array('left', 'left', 'left', 'left', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => 
						$systype == -1 ? _('All') : $systypes_array[$systype],
                            'to' => ''));

    $rep = new FrontReport(_('Supplier APV List'), "JournalEntries", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

    if ($systype == -1)
        $systype = null;

    $trans = get_supplier_apv_transactions($is_order_by_apv_no,$apv_no,$po_no,$receiving_no,$invoice_no,$supplier,$from, $to, -1, null, 0, 0, $systype);
	$count = db_num_rows($trans);
	
	if($count==0)
	{
		display_error("No reports can be generated with the given parameters.");
		die();
	}
    $d_total = $c_total  = $td_total = $tc_total = $typeno = $type = 0;
    while ($myrow=db_fetch($trans))
    {
        if ($type != $myrow['type'] || $typeno != $myrow['type_no'])
        {
            if ($typeno != 0)
            {
                $rep->Line($rep->row  + 4);
            }
			
			if ($td_total != 0)
			{
				$rep->NewLine();
				$rep->font('b');
				$rep->AmountCol(4, 5, abs($td_total), $dec);
				$rep->AmountCol(5, 6, abs($tc_total), $dec);
				$rep->font('');
				$rep->NewLine();
				$td_total = $tc_total = 0;
				$rep->NewLine(2);
			}
			
            $typeno = $myrow['type_no'];
            $type = $myrow['type'];
            $TransName = $systypes_array[$myrow['type']];
            $rep->TextCol(0, 1, $TransName . " # " . $myrow['type_no']);
			$rep->TextCol(1, 2, $myrow['special_reference']." / ".$myrow['reference']." / ".$myrow['supp_reference']);
            $rep->DateCol(2, 3, $myrow['tran_date'], true);
            $coms =  payment_person_name($myrow["person_type_id"],$myrow["person_id"]);
            $memo = get_comments_string($myrow['type'], $myrow['type_no']);
            if ($memo != '')
            {
            	if ($coms == "")
            		$coms = $memo;
            	else
            		$coms .= " / ".$memo;
            }		
            $rep->TextCol(3, 6, $coms);
            $rep->NewLine(2);
        }
        $rep->TextCol(0, 1, $myrow['account']);
        $rep->TextCol(1, 2, $myrow['account_name']);
        $dim_str = get_dimension_string($myrow['dimension_id']);
        $dim_str2 = get_dimension_string($myrow['dimension2_id']);
        if ($dim_str2 != "")
        	$dim_str .= "/".$dim_str2;
        $rep->TextCol(2, 3, $dim_str);
        $rep->TextCol(3, 4, $myrow['memo_']);
        if ($myrow['amount'] > 0.0)
		{
			$d_total += abs($myrow['amount']);
			$td_total += abs($myrow['amount']);
            $rep->AmountCol(4, 5, abs($myrow['amount']), $dec);
		}
        else
		{
			$c_total += abs($myrow['amount']);
			$tc_total += abs($myrow['amount']);
            $rep->AmountCol(5, 6, abs($myrow['amount']), $dec);
		}
        $rep->NewLine(1, 2);
    }
	
    $rep->Line($rep->row  + 4);
	if ($td_total != 0)
	{
		$rep->NewLine();
		$rep->font('b');
		$rep->AmountCol(4, 5, abs($td_total), $dec);
		$rep->AmountCol(5, 6, abs($tc_total), $dec);
		$rep->font('');
		$rep->NewLine();
		$td_total = $tc_total = 0;
	}
	
	$rep->NewLine();
	$rep->font('b');
	$rep->AmountCol(4, 5, abs($d_total), $dec);
	$rep->AmountCol(5, 6, abs($c_total), $dec);
	$rep->font('');
	$rep->NewLine();
	
    $rep->End();
}

?>