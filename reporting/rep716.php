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

print_cv_details();

//----------------------------------------------------------------------------------------------------
function get_inv_amount_ewt($type,$trans_no)
{
	$sql = "SELECT ewt FROM ".TB_PREF."supp_trans 
			WHERE type=$type
			AND trans_no = $trans_no";
	// display_error($sql);die;
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

function get_transactions($date_after, $date_to, $supp_id)
{
    $date_after = date2sql($date_after);
    $date_to = date2sql($date_to);
	
	$sql = "SELECT * FROM ".TB_PREF."cv_header 
	WHERE cv_date >= '$date_after'
		AND cv_date <= '$date_to'";

	$sql .= " AND bank_trans_id != 0 AND approved = 1 AND online_payment = 2";
	$sql .= ' AND person_id IN ('.implode(',',$supp_id).')';
	
	return db_query($sql,"No supplier transactions were returned");
}

function print_cv_details()
{
	global $path_to_root, $systypes_array, $systypes_array_short;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$supp_id = $_POST['PARAM_2'];

	$destination = $_POST['PARAM_3'];
		
		//$systype=ST_SUPPINVOICE;
		
		if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
		else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		

	$rep = new FrontReport(_('Online CV Details'), "Online CV Details", user_pagesize(),9 ,'P');
	$dec = user_price_dec();


	$cols = array(0, 70, 140, 380, 455, 550);

	$aligns = array('center', 'left', 'left', 'center', 'right');

	$headers = array('CV #', 'CV Date', 'Transaction' ,'Trans Amount', 'CV Amount ');
	
	$params = array();
	$params[] = $comments;
	
	foreach($supp_id as $supp)
		$params[] = array('text' => _('Supplier'), 'from' => get_supplier_name($supp), 'to' => '');
	
    $params[] = array('text' => _('CV Date'), 'from' => $from, 'to' => $to);
						
	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	
	$res = get_transactions($from, $to, $supp_id);
	
	$cv_grand_total = 0;
	while($cv_header = db_fetch($res))
	{
		$cv_grand_total += $cv_header['amount'];
		$rep->font('b');
		$rep->TextCol(0, 1,	$cv_header['cv_no']);
		$rep->TextCol(1, 2,	sql2date($cv_header['cv_date']));
		$rep->TextCol(2, 3,	'Online Payment Date : '.sql2date(get_cv_payment_date($cv_header['id'])));
		$rep->TextCol(4, 5,	number_format2($cv_header['amount'],2));
		$rep->font('');
		$rep->NewLine();

		$cv_details = get_cv_details($cv_header['id'], 'AND trans_type !=22 ORDER BY amount DESC');
		while ($cv_d_row = db_fetch($cv_details))
		{
			$inv_ewt = 0;

			for ($ii = 0; $ii <= 0 ; $ii ++)
			{

				$tran_det = get_tran_details($cv_d_row['trans_type'], $cv_d_row['trans_no']);
				$cv_d_row['amount'] = round2($cv_d_row['amount'], user_price_dec());
				
				$tran_date_ = sql2date(($cv_d_row['trans_type'] != 20 ? $tran_det['tran_date'] : $tran_det['del_date']));
				
				if (strpos($tran_det['reference'],'NT') !== false AND $cv_d_row['trans_type'] == 20) // NON TRADE
				{
					$add_text = '';
					$comment = get_comments_string($cv_d_row['trans_type'], $cv_d_row['trans_no']);
					if (trim($comment) != '')
						$add_text .= "  -- ".$comment;
				
					$final_text = $systypes_array_short[$cv_d_row['trans_type']]. ' # '.$tran_det['reference'] . $add_text;
					
					// $sobra = $cheque->TextWrap(110, $cheque->row, 250, $final_text , 'left',
						// 0, 0, NULL, 0, true);
						
					
					$nt_res = get_gl_trans($cv_d_row['trans_type'], $cv_d_row['trans_no'],true);
					
					while($nt_row = db_fetch($nt_res))
					{
						if(get_company_pref('creditors_act') == $nt_row['account'] 
							OR get_company_pref('creditors_act_nt') == $nt_row['account'])
							continue;
						$cheque->Newline();
						$final_text = get_gl_account_name($nt_row['account']);
						$amount = number_format2($nt_row['amount'],2);
					}
				}
				else
				{
					$add_text='';
					if ($tran_det['supp_reference'] != '')
						$add_text = ($cv_d_row['trans_type'] == 20 ? "  --  SI # " : ' -- ') . $tran_det['supp_reference'];
					
					$comment = get_comments_string($cv_d_row['trans_type'], $cv_d_row['trans_no']);
					if (trim($comment) != '')
						$add_text .= "  -- ".$comment;
					
					$final_text = $systypes_array[$cv_d_row['trans_type']]. ' # '.$tran_det['reference'] . $add_text;
					
					if ($cv_d_row['trans_type'] != 20)
						$amount = number_format2($cv_d_row['amount'],2);
					else
					{
						$inv_ewt = get_inv_amount_ewt($cv_d_row['trans_type'],$cv_d_row['trans_no']);
						if (date_diff2(sql2date($cv_header['cv_date']),'08/01/2013','d') < 0)
						{
							$inv_ewt_tot += $inv_ewt;
						}
						
						$amount = number_format2($cv_d_row['amount'] + $inv_ewt,2);
						
					}
						
				}
				
				$rep->TextCol(2, 3,	$final_text);
				// $rep->TextCol(3, 4,	$tran_date_);
				$rep->TextCol(3, 4,	$amount);
				$rep->NewLine();
			
			}
		}
		
		if ($cv_header['ewt'] != 0)
		{
			$ewt = number_format2(-($cv_header['ewt']),2);
			
			$rep->TextCol(2, 3,	'EWT');
			$rep->TextCol(3, 4,	$ewt);
			$rep->NewLine();
		}
		else if($inv_ewt_tot != 0)
		{
			$ewt = number_format2(-($inv_ewt_tot),2);
			
			$rep->TextCol(3, 4,	$ewt);
			$rep->NewLine();
		}
		
		$rep->Line($rep->row+9);
			
	}

	$rep->Line($rep->row+9,2);
	$rep->row --;
	$rep->font('b');
	$rep->TextCol(3, 4, 'Grand Total : ');
	$rep->TextCol(4, 5,	number_format2($cv_grand_total,2));
	$rep->font('');
	$rep->End();
}

?>