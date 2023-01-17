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
$page_security = 'SA_GLREP';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	GL Accounts Transactions
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_db.inc");

//----------------------------------------------------------------------------------------------------

print_paid_unpaid_apv();

//----------------------------------------------------------------------------------------------------
set_time_limit(0);
function print_paid_unpaid_apv()
{
	global $path_to_root;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$t_nt = $_POST['PARAM_2'];
	$w_wo_cv = $_POST['PARAM_3'];
	$only_balances = $_POST['PARAM_4'];
	$supplier_id = $_POST['PARAM_5'];
	$destination = $_POST['PARAM_6'];

	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
	$rep = new FrontReport(_('Supplier Transactions'), "Supplier Transactions", user_pagesize(),9 ,'P');
	
	$cols = array(0, 80, 150, 250, 350, 450, 550);

	$aligns = array('center', 'center', 'center', 'right', 'right','center');

	$headers = array('Trans Type', 'Reference', 'Date' , 'Debit', 'Credit ','CV #');


	if ($w_wo_cv == 0)
		$w_wo_cv_str = 'No CV';
	else if ($w_wo_cv == 1)
		$w_wo_cv_str = 'With CV';
	else if ($w_wo_cv == 0)
		$w_wo_cv_str = 'Without CV';
	
	$t_nt_str = 'All';
	
	if ($t_nt == 0)
		$t_nt_str = 'Non-Trade Only';
	else if ($t_nt == 1)
		$t_nt_str = 'Trade Only';
	
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Dates Included'), 'from' => $from, 'to' => $to),
    				    2 => array('text' => _('Supplier'), 'from' => get_supplier_name($supplier_id), 'to' => ''),
    				    3 => array('text' => 'Trade/Non-Trade', 'from' => $t_nt_str, 'to' => ''),
    				    4 => array('text' => 'With/Without CV', 'from' => $w_wo_cv_str, 'to' => ''));
						
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	
	$sql = "SELECT b.supp_name, a.trans_no, a.type, a.supp_reference,a.tran_date, 
				IF(type = 20,round(ov_amount+ov_gst-ov_discount,2),round(ov_amount+ov_gst-ov_discount+ewt,2)) as amount, a.cv_id 
			FROM 0_supp_trans a, 0_suppliers b
			WHERE tran_date >= '".date2sql($from)."'
			AND tran_date <= '".date2sql($to)."'
			AND a.supplier_id = b.supplier_id
			AND round(ov_amount+ov_gst-ov_discount+ewt,2) != 0";
			
	if ($t_nt == 1) //trade
		$sql .= " AND (a.reference NOT LIKE 'NT%')";
	else if ($t_nt == 0) //non trade
		$sql .= "  AND (a.reference LIKE 'NT%')";
	
	if ($w_wo_cv == 0) //no CV only
		$sql .= " AND a.cv_id = 0";
	else if ($w_wo_cv == 1) //with CV only
		$sql .= " AND a.cv_id != 0";
		
	if ($supplier_id != -1)
		$sql .= " AND a.supplier_id = $supplier_id";
		
	$sql .= " ORDER BY supp_name,cv_id";
	
	// display_error($sql);die;
	$res = db_query($sql);
	
	$supp_name = '';
	$supp_credit = $supp_debit = $total_credit = $total_debit = 0;
	
	global $systypes_array;
	while($row = db_fetch($res))
	{
		if ($supp_name != $row['supp_name'])
		{
			if ($supp_name != '')
			{
				$bal = round(abs($supp_debit),2) - round(abs($supp_credit),2);
				$rep->font('b');
				$rep->Line($rep->row+10);
				$rep->TextCol(3, 4,	number_format2(abs($supp_debit),2),0,0,0,0,null,1,0,'right');
				$rep->TextCol(4, 5,	number_format2(abs($supp_credit),2),0,0,0,0,null,1,0,'right');
				$rep->TextCol(5, 6,	$bal >= 0 ? number_format2($bal,2) : '('.number_format2(abs($bal),2).')' 
					,0,0,0,0,null,1,0,'right');
				$rep->NewLine();
				$rep->NewLine();
				$rep->font('');
			}
			$rep->font('b');
			$rep->TextCol(0, 6,	$row['supp_name'],0,0,0,0,null,1,0,'left');
			$rep->Line($rep->row-2);
			$rep->NewLine();
			$rep->font('');
			$supp_name = $row['supp_name'];
			
			$supp_credit = $supp_debit = 0;
		}
		
		$rep->TextCol(0, 1,	$systypes_array[$row['type']],0,0,0,0,null,1,0,'left');
		$rep->TextCol(1, 2,	'   '.$row['supp_reference'],0,0,0,0,null,1,0,'left');
		$rep->TextCol(2, 3,	sql2date($row['tran_date']),0,0,0,0,null,1,0,'center');
		
		if ($row['amount'] <= 0) // debit //reversed
		{
			$rep->TextCol(3, 4,	number_format2(abs($row['amount']),2),0,0,0,0,null,1,0,'right');
			$supp_debit += $row['amount'];
			$total_debit += $row['amount'];
		}
		else // credit
		{
			$rep->TextCol(4, 5,	number_format2(abs($row['amount']),2),0,0,0,0,null,1,0,'right');
			$supp_credit += abs($row['amount']);
			$total_credit += abs($row['amount']);
		}
		
		$rep->TextCol(5, 6,	get_cv_no($row['cv_id']));
		
		$rep->NewLine();
	}
	
	$bal = round(abs($supp_debit),2) - round(abs($supp_credit),2);
	$rep->font('b');
	$rep->Line($rep->row+10);
	$rep->TextCol(3, 4,	number_format2(abs($supp_debit),2),0,0,0,0,null,1,0,'right');
	$rep->TextCol(4, 5,	number_format2(abs($supp_credit),2),0,0,0,0,null,1,0,'right');
	$rep->TextCol(5, 6,	($bal >= 0 ? number_format2($bal,2) : '('.number_format2(abs($bal),2).')')
		,0,0,0,0,null,1,0,'right');
	$rep->NewLine();
	$rep->NewLine();
	$rep->font('');
	
	$bal = round(abs($total_debit),2) - round(abs($total_credit),2);
	$rep->font('b');
	$rep->Line($rep->row+10,2);
	$rep->TextCol(0, 3,	'GRAND TOTAL: ',0,0,0,0,null,1,0,'right');
	$rep->TextCol(3, 4,	number_format2(abs($total_debit),2),0,0,0,0,null,1,0,'right');
	$rep->TextCol(4, 5,	number_format2(abs($total_credit),2),0,0,0,0,null,1,0,'right');
	$rep->TextCol(5, 6,	$bal >= 0 ? number_format2($bal,2) : '('.number_format2(abs($bal),2).')' 
		,0,0,0,0,null,1,0,'right');
	$rep->font('');
	
	$rep->End();
}

?>