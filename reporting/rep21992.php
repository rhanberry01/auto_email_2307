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
	$destination = $_POST['PARAM_3'];
	$is_group = $_POST['PARAM_4'];
	$status = $_POST['PARAM_5'];
	//$paid = $_POST['PARAM_6'];
	   
	$comments = '';
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	if ($supp_id == ALL_NUMERIC)
		$supp = _('All');
	else
		$supp = get_supplier_name($supp_id);
	
	$stat_str = 'All APV';
	if ($status == 0)
		$stat_str == 'no CV yet';
	else if ($status == 1)
		$stat_str = 'with CV';
    
	$dec = user_price_dec();

	// $cols = array(0,40, 75, 130, 350, 450, 550);
	// $cols = array(0,220,260,305,350,450,550);
	$cols = array(0, 50, 75, 130, 230, 330, 450, 500, 550);

	$headers = array('Branch', '#', 'Date', 'Reference', 'Amount   ', 'Reference', 'CV#', 'CV Status'); //'Supplier',

	$aligns = array('left','left',	'left',	'left', 'right', 'left', 'right', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $start_date, 'to' => $end_date),
    				    2 => array('text' => _('Supplier'), 'from' => $supp, 'to' => ''),
						3 => array('text' => _('Status'), 'from' => $stat_str, 'to' => ''));

    $rep = new FrontReport(_('Supplier APV from 2015 to Present'), "SuppAPV", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	// $sql = "SELECT a.*, b.supp_name FROM ".TB_PREF."supp_trans a, ".TB_PREF."suppliers b
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

	// $sql .= " ORDER BY supp_name, tran_date";
	
	
	// echo $sql;
	if ($supp_id != -1)
	{
		$sql_get_new_vendor= "SELECT supp_ref, supp_name FROM srs_aria_nova.0_suppliers WHERE supplier_id='$supp_id'";
		//display_error($sql_get_old_bank_id);
		$result1 = db_query($sql_get_new_vendor, "failed to get bank_accounts id.");
		$row1=db_fetch($result1);
		$vendor_codes=$row1['supp_ref'];
		$supp_names=$row1['supp_name'];
		
	}
	
	$sql = "SELECT *, round(ov_amount + ov_gst  - ov_discount - ewt,2) AS TotalAmount  FROM srs_aria_nova.0_apv_summary_from_2015_to_present5";

	$sql .= " WHERE tran_date >= '".date2sql($start_date)."'
				AND tran_date <= '".date2sql($end_date)."'";
				
	if ($supp_id != -1)
	{
		$sql .= " AND supp_name=".db_escape($supp_names)."";
	}
	
	
	if($status == 0) // no CV
		$sql .= " AND cv_id = 0";
	else if($status == 1) // with CV
			$sql .= " AND cv_id != 0";
			
	// if($paid == 0) // no CV
		$sql .= " AND bank_trans_id = 0";
	// else if($paid == 1) // with CV
			// $sql .= " AND bank_trans_id != 0";
	
	if ($is_group==1)
	{
		$sql .= " ORDER BY supp_name, branch_code, tran_date";
	}


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
					$rep->TextCol(0, 1, $supp_row[0],-5);
					$rep->font('');
					$rep->TextCol(1, 2, $supp_row[1],-5);
					$rep->TextCol(2, 3, $supp_row[2],-10);
					$rep->TextCol(3, 4, $supp_row[3],-10);
					$rep->TextCol(4, 5, $supp_row[4],-10);
					if ($destination)
						$rep->TextCol(5, 6, $supp_row[5]);
					else
						$rep->TextColLines(5, 6, $supp_row[5]);
					
					$rep->TextCol(6, 7, $supp_row[6],-5);
					$rep->TextCol(7, 8, $supp_row[7],-5);
					$rep->NewLine();
					$supp_total = 0;
					$supp_rows = array();
				}
				$rep->NewLine();
			}
			
			$supp_rows[] = array(
				$myrow['branch_name'],
				$myrow['reference'],
				sql2date($myrow['tran_date']),
				$myrow['supp_reference'],
				number_format2($myrow['TotalAmount'],2),
				html_entity_decode(html_entity_decode($myrow['special_reference'])),
				html_entity_decode(html_entity_decode($myrow['cv_no'])),
				$myrow['bank_trans_id']
			);
			
			// $rep->NewLine();
			$temp_supp_name = $myrow['supp_name'];
			$supp_total += $myrow['TotalAmount'];
			
			$total += $myrow['TotalAmount'];		
		}
		
		$rep->font('B');
		$rep->TextCol(0, 4, $temp_supp_name,-10);
		$rep->TextCol(4, 5, number_format2($supp_total,2),-10);
		$rep->Line($rep->row-2);
		$rep->NewLine();
		$rep->font('');
		
		foreach($supp_rows as $supp_row)
		{
			$rep->font('B');
			$rep->TextCol(0, 1, $supp_row[0],-5);
			$rep->font('');
			$rep->TextCol(1, 2, $supp_row[1],-5);
			$rep->TextCol(2, 3, $supp_row[2],-10);
			$rep->TextCol(3, 4, $supp_row[3],-10);
			$rep->TextCol(4, 5, $supp_row[4],-10);
			if ($destination)
				$rep->TextCol(5, 6, $supp_row[5]);
			else
				$rep->TextColLines(5, 6, $supp_row[5]);
			
			$rep->TextCol(6, 7, $supp_row[6],-5);
			$rep->TextCol(7, 8, $supp_row[7],-5);
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
