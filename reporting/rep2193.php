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
$page_security = 'SA_SUPPLIERANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Outstanding GRNs Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_pdcc_for_payments();

function print_pdcc_for_payments()
{
    global $path_to_root;
	
	$start_date = $_POST['PARAM_0'];
	$end_date = $_POST['PARAM_1'];
    $supp_id = $_POST['PARAM_2'];
   // $status = $_POST['PARAM_3'];
	$destination = $_POST['PARAM_3'];
	$comments = '';
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($supp_id == ALL_NUMERIC)
		$supp = _('All');
	else
		$supp = get_supplier_name($supp_id);
	
	$stat_str = 'All DM';
	if ($status == 0)
		$stat_str == 'no CV yet';
	else if ($status == 1)
		$stat_str = 'with CV';
    
	$dec = user_price_dec();

	// $cols = array(0,40, 75, 130, 350, 450, 550);
	// $cols = array(0,220,260,305,350,450,550);
	$cols = array(0,50,100,130,200,270,330,400);

	$headers = array('APV REF', 'Date','DM#', 'CV#','Invoice Ref','Debit Memo','Date Deducted','Remarks'); //'Supplier',

	$aligns = array('left','left',	'left',	'left', 'left', 'left','left');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $start_date, 'to' => $end_date),
    				    2 => array('text' => _('Supplier'), 'from' => $supp, 'to' => ''),
    				    3 => array('text' => _('Status'), 'from' => $stat_str, 'to' => ''));

    $rep = new FrontReport(_('Supplier Debit Memos'), "SuppDM", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();
	
	function get_si_ref($t_no)
{
	$sql = "SELECT tran_date,supp_reference, ov_amount FROM ".TB_PREF."supp_trans
			WHERE type=20 and cv_id=$t_no";
	$res = db_query($sql);

	$row = db_fetch($res);

	
	
	return $row;
}

	$sql = "
	
SELECT st.*,s.supp_name,cv.cv_no,cv.bank_trans_id, bt.trans_date
FROM 0_supp_trans as st
LEFT JOIN 0_suppliers as s
ON s.supplier_id = st.supplier_id 
LEFT JOIN 0_cv_header as cv
on st.cv_id=cv.id
LEFT JOIN 0_bank_trans as bt
ON cv.bank_trans_id=bt.id
WHERE st.type='53'
AND st.ov_amount < 0 ";

	// if($status == 0) // no CV
		// $sql .= " AND cv_id = 0";
	// else if($status == 1) // with CV
			// $sql .= " AND st.cv_id != 0";
			
	$sql .= "AND (ISNULL(bank_trans_id) or bank_trans_id=0 or bt.trans_date>'".date2sql($end_date)."') AND st.tran_date >= '".date2sql($start_date)."'
			  AND st.tran_date <= '".date2sql($end_date)."'";
			  
	if ($supp_id != -1)
	{
		$sql .= " AND st.supplier_id = ".$supp_id;
	}

	





	// SELECT a.*, b.supp_name FROM ".TB_PREF."supp_trans a, ".TB_PREF."suppliers b
		// WHERE type = 53
		// AND a.supplier_id = b.supplier_id
		// AND ov_amount < 0";
			
	// if($status == 0) // no CV
		// $sql .= " AND cv_id = 0";
	// else if($status == 1) // with CV
			// $sql .= " AND cv_id != 0";
			
	// $sql .= " AND tran_date >= '".date2sql($start_date)."'
			  // AND tran_date <= '".date2sql($end_date)."'";
			  
	// if ($supp_id != -1)
	// {
		// $sql .= " AND a.supplier_id = ".$supp_id;
	// }

	$sql .= " ORDER BY s.supp_name, st.tran_date";
	
	//echo $sql;
	$res = db_query($sql);
	
	if(db_num_rows($res) <= 0)
	{
		// $rep->TextCol(0,6, "No report found.");
		echo 'No report found';die();
	}
	else
	{
		$temp_supp_name = '';
		$supp_total = $total = 0;

		$supp_rows = array();
		while($myrow = db_fetch($res))
		{
			// $myrow['supp_name']
			
			if ($temp_supp_name != $myrow['supp_name'] AND $temp_supp_name != '')
			{
				$rep->font('B');
				$rep->TextCol(0, 4, $temp_supp_name,-10);
				$rep->TextCol(4, 5, number_format2($supp_total,2),-10);
				$rep->Line($rep->row-2);
				$rep->NewLine();
				$rep->font('');
				
				foreach($supp_rows as $supp_row)
				{
					$rep->font('B');
			if($supp_row[8] <=date2sql($end_date)){
				
				$rep->TextCol(0, 1, $supp_row[0],-5);
			}
			else{
				$rep->TextCol(0, 1, '',-5);
			}
					$rep->font('');
					$rep->TextCol(1, 2, $supp_row[1],-5);
					$rep->TextCol(2, 3, $supp_row[2],-10);
					$rep->TextCol(3, 4, $supp_row[3],-10);
					$rep->TextCol(4, 5, $supp_row[4],-10);
					$rep->TextCol(5, 6, $supp_row[5],-10);
					$rep->TextCol(6, 7, $supp_row[6],-10);
					
					
					if ($destination)
						$rep->TextCol(7, 8, $supp_row[7]);
					else
						$rep->TextColLines(7, 8, $supp_row[7]);
					$rep->NewLine();
					$supp_total = 0;
					$supp_rows = array();
				}
				$rep->NewLine();
			}
				//($myrow['cv_id'] != 0? 'CV# '.get_cv_no($myrow['cv_id']):''),
				$supp_trans_row=get_si_ref($myrow['cv_id']);
			$supp_rows[] = array(

				($myrow['cv_id'] != 0? 'SI# '.$supp_trans_row['supp_reference']:''),
				sql2date($myrow['tran_date']),
				$myrow['cv_no'],
				$myrow['reference'],
				$myrow['supp_reference'],
				number_format2(-$myrow['ov_amount'],2),
				$myrow['trans_date'],
				html_entity_decode(html_entity_decode(get_comments_string(53, $myrow['trans_no']))),
				$supp_trans_row['tran_date']
			);
			
			// $rep->NewLine();
			$temp_supp_name = $myrow['supp_name'];
			$supp_total += -$myrow['ov_amount'];
			
			$total += -$myrow['ov_amount'];		
		}
		
		$rep->font('B');
		$rep->TextCol(0, 4, $temp_supp_name,-10);
		$rep->TextCol(4, 6, number_format2($supp_total,2),-10);
		$rep->Line($rep->row-2);
		$rep->NewLine();
		$rep->font('');
		
		foreach($supp_rows as $supp_row)
		{
			$rep->font('B');
			
			// echo $supp_row[8] ;
			// echo date2sql($end_date);
			// die;
			if($supp_row[8] <= date2sql($end_date)){
				$rep->TextCol(0, 1, $supp_row[0],-5);
			}
			else{
				
				
				$rep->TextCol(0, 1, '',-5);
			}
		
			$rep->font('');
					$rep->TextCol(1, 2, $supp_row[1],-5);
					$rep->TextCol(2, 3, $supp_row[2],-10);
					$rep->TextCol(3, 4, $supp_row[3],-10);
					$rep->TextCol(4, 5, $supp_row[4],-10);
					$rep->TextCol(5, 6, $supp_row[5],-10);
					$rep->TextCol(6, 7, $supp_row[6],-10);
			
			if ($destination)
				$rep->TextCol(7, 8, $supp_row[5]);
			else
				$rep->TextColLines(7, 8, $supp_row[5]);
			$rep->NewLine();
		}
		
		$rep->Line($rep->row + 6);
		$rep->NewLine();
		$rep->font('B');
		$rep->TextCol(0, 1, 'Total');
		$rep->TextCol(4, 5,	number_format2(abs($total),2),-10);
		$rep->font('');
		if ($destination)
			$rep->NewLine();
	}

    $rep->End();
}

?>