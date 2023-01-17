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
	// Title:	List of Journal Entries
	// ----------------------------------------------------------------
	$path_to_root="..";
	
	include_once($path_to_root . "/includes/session.inc");
	include_once($path_to_root . "/includes/date_functions.inc");
	include_once($path_to_root . "/includes/data_checks.inc");
	include_once($path_to_root . "/gl/includes/gl_db.inc");
	include_once($path_to_root . "/includes/ui/ui_view.inc");
	
	
	
	function get_supplier_grn_transactions($is_group,$from_date, $to_date, $where)
	{
		$from = date2sql($from_date);
		$to = date2sql($to_date);
		
		$sql = "SELECT gb.*,sum(gi.extended)  as t_extended, s.supp_name, s.gst_no
		FROM 0_grn_batch gb, 0_grn_items gi, 0_suppliers s
		WHERE gb.id = gi.grn_batch_id
		AND gb.supplier_id = s.supplier_id
		AND gb.delivery_date >= '$from' AND gb.delivery_date <= '$to'";
		// if ($trans_no > 0)
		// $sql .= " AND ".TB_PREF."gl_trans.type_no LIKE ".db_escape('%'.$trans_no);
		if ($is_group==1)
		{
			
			$sql .= " $where GROUP BY gi.grn_batch_id ORDER BY s.supp_name,gb.supplier_id,gb.delivery_date,gb.id";
		}
		else{
			$sql .= " GROUP BY gi.grn_batch_id ORDER BY gi.grn_batch_id";
		}
		
		
		// echo ($sql);die;
		return db_query($sql, "The transactions for could not be retrieved");
	}
	
	//----------------------------------------------------------------------------------------------------
	
	print_list_of_journal_entries();
	
	//----------------------------------------------------------------------------------------------------
	
	function supplier_is_vatable($supplier_id)
	{
		$sql = "SELECT tax_group_id FROM 0_suppliers WHERE supplier_id = $supplier_id";
		$res = db_query($sql);
		$row = db_fetch($res);
		return $row[0] == 1;
	}
	function print_list_of_journal_entries()
	{
		global $path_to_root, $systypes_array;
		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$is_group = $_POST['PARAM_2'];
		//$supplier = $_POST['PARAM_2'];
		// $receiving_no = $_POST['PARAM_3'];
		// $invoice_no = $_POST['PARAM_4'];
		$destination = $_POST['PARAM_3'];
		
		//$systype=ST_SUPPINVOICE;
		
		if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
		else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
		
		$dec = user_price_dec();
		
		$cols = array(0,  215, 275, 325, 370, 460, 540, 620, 700);
		
		$headers = array(_('Supplier'), _('Date'),_('RR#'),_('Invoice#'),  'Amount', 'Purch. NON-VAT', 'Purch. VAT', '12% VAT');
		
		$aligns = array('left', 'left', 'left', 'left', 'right','right','right','right');
		
		$params =   array( 	0 => $comments,
		1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
		2 => array('text' => _('Type'), 'from' => 
		$systype == -1 ? _('All') : $systypes_array[$systype],
		'to' => ''));
		
		$rep = new FrontReport(_('Receiving Summary'), "Receiving Summary", 'Letter', 9, 'L');
		
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();
		
		if ($systype == -1)
        $systype = null;
		
		$p_nv_total = $p_v_total = $v_total = $others_total = 0;
		$for_vat_total = $for_nv_total = $for_dr_total = $supp_total = array(0,0,0,0);
		
		for($xyz=0;$xyz<=2;$xyz++)
		{
			$supplier_id = 0;
			$x=0;
			$prev_coms = '';
			$pnv_gt = $pv_gt = $v_gt = 0;
			
			if($xyz == 0) // VAT
			{
				$rep->Font('bi');
				$rep->fontSize += 3;
				$rep->TextCol(0, 5, '     VATABLE Suppliers');
				$rep->Line($rep->row -5);
				$rep->NewLine(2);
				$rep->fontSize -= 3;
				$rep->Font('');
				
				$and = " AND tax_group_id = 1 AND TRIM(gst_no) != ''";
			}
			else if($xyz == 1) // NON VAT
			{
				
				// show VAT total
				$rep->Font('bold');
				$rep->Line($rep->row + 10,1);
				$rep->NewLine(.5);
				$rep->TextCol(0, 1,	'');
				$rep->TextCol(1, 2, '');
				$rep->TextCol(2, 3, 'VAT TOTAL : ');
				$rep->AmountCol(4, 5, $for_vat_total[0],2);
				$rep->AmountCol(5, 6, $for_vat_total[1],2);
				$rep->AmountCol(6, 7, $for_vat_total[2],2);
				$rep->AmountCol(7, 8, $for_vat_total[3],2);
				$rep->Font('');
				$rep->NewLine();
				
				
				// $rep->NewPage();
				$rep->Header();
				$rep->Font('bi');
				$rep->fontSize += 3;
				$rep->TextCol(0, 5, '     NON-VATABLE Suppliers');
				$rep->Line($rep->row -5);
				$rep->NewLine(2);
				$rep->fontSize -= 3;
				$rep->Font('');
				
				$and = " AND tax_group_id != 1 AND TRIM(gst_no) != ''";
			}
			else if($xyz == 2) // DR
			{
				
				$rep->Font('bold');
				$rep->Line($rep->row + 10,1);
				$rep->NewLine(.5);
				$rep->TextCol(0, 1,	'');
				$rep->TextCol(1, 2, '');
				$rep->TextCol(2, 3, 'NON VAT TOTAL : ');
				$rep->AmountCol(4, 5, $for_nv_total[0],2);
				$rep->AmountCol(5, 6, $for_nv_total[1],2);
				$rep->AmountCol(6, 7, $for_nv_total[2],2);
				$rep->AmountCol(7, 8, $for_nv_total[3],2);
				$rep->Font('');
				$rep->NewLine();
				
				// $rep->NewPage();
				$rep->Header();
				$rep->Font('bi');
				$rep->fontSize += 3;
				$rep->TextCol(0, 5, '     DR Suppliers');
				$rep->Line($rep->row -5);
				$rep->NewLine(2);
				$rep->fontSize -= 3;
				$rep->Font('');
				
				$and = " AND TRIM(gst_no) = ''";
			}
			
			$trans = get_supplier_grn_transactions($is_group,$from, $to,$and);
			$count = db_num_rows($trans);
			$t_extended=0;
			$purch_non_vat_total=0;
			$purch_vat_total=0;
			$vat_total=0;
					
			while ($myrow=db_fetch($trans))
			{
				if ($myrow['supplier_id']!= $supplier_id and $supplier_id != 0 and $is_group==1)
				{
					$rep->Line($rep->row  + 6);
					$rep->NewLine(.5);
					$rep->font('b');
					$rep->AmountCol(4, 5, abs($t_extended), $dec);
					$rep->AmountCol(5, 6, abs($purch_non_vat_total), $dec);
					$rep->AmountCol(6, 7, abs($purch_vat_total), $dec);
					$rep->AmountCol(7, 8, abs($vat_total), $dec);
					$rep->font('');
					$rep->NewLine();
					$rep->NewLine(2);
					$t_extended=0;
					$purch_non_vat_total=0;
					$purch_vat_total=0;
					$vat_total=0;
				}
				
				$receiving_no=$myrow['reference']+0;
				$coms =  $myrow["supp_name"] . ($xyz != 2? (' ['. $myrow["gst_no"].']') : '');
				
				if ($prev_coms != $coms AND  $is_group==1)
				{
					$rep->font('b');
					$rep->TextCol(0, 5, $coms);
					$rep->NewLine();
					$rep->font('');
				}
				else if ($is_group!=1)
				{
					$rep->font('b');
					$rep->TextCol(0, 1, $coms);
					$rep->font('');
				}
				$prev_coms = $coms;
				
				$rep->DateCol(1, 2,  ' ' . $myrow['delivery_date'], true);
				$rep->TextCol(2, 3,' ' . $receiving_no);
				$rep->TextCol(3, 4, ' ' . $myrow['source_invoice_no']);
				$rep->AmountCol(4, 5, $myrow['t_extended'], 2);
				
				$p_nv = $p_vat = $vat = 0;
				if (!supplier_is_vatable($myrow['supplier_id']))
				{
					$p_nv = $myrow['t_extended'];
					$rep->AmountCol(5, 6, $p_nv, 2);
					$purch_non_vat_total += $p_nv;
				}
				else
				{
					$p_vat = round($myrow['t_extended']/1.12 ,2);
					$vat = round($myrow['t_extended'] - $p_vat , 2);
					$rep->AmountCol(6, 7, $p_vat, 2);
					$rep->AmountCol(7, 8, $vat, 2);
					
					$purch_vat_total += $p_vat;
					$vat_total += $vat;
				}
				
				if ($xyz == 0) //VAT
				{
					$for_vat_total[0] += $myrow['t_extended'];
					$for_vat_total[1] += $p_nv;
					$for_vat_total[2] += $p_vat;
					$for_vat_total[3] += $vat;
				}
				else if ($xyz == 1) //VAT
				{
					$for_nv_total[0] += $myrow['t_extended'];
					$for_nv_total[1] += $p_nv;
					$for_nv_total[2] += $p_vat;
					$for_nv_total[3] += $vat;
				}
				else if ($xyz == 2) //DR
				{
					$for_dr_total[0] += $myrow['t_extended'];
					$for_dr_total[1] += $p_nv;
					$for_dr_total[2] += $p_vat;
					$for_dr_total[3] += $vat;
				}
				
				$rep->NewLine(1, 2);
				
				$supplier_id=$myrow["supplier_id"];
				
				$t_extended+=$myrow['t_extended'];
				$t_extended2+=$myrow['t_extended'];
				
				$x++;
			}
			
			if ($is_group==1)
			{
				$rep->Line($rep->row  + 6);
				$rep->NewLine(.5);
				$rep->font('b');
				$rep->AmountCol(4, 5, abs($t_extended), $dec);
				$rep->AmountCol(5, 6, abs($purch_non_vat_total), $dec);
				$rep->AmountCol(6, 7, abs($purch_vat_total), $dec);
				$rep->AmountCol(7, 8, abs($vat_total), $dec);
				$rep->font('');
				$rep->NewLine(2);
				$t_extended=0;
				$purch_non_vat_total=0;
				$purch_vat_total=0;
				$vat_total=0;
			}
		}
		
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 3, 'DR TOTAL : ');
		$rep->AmountCol(4, 5, $for_dr_total[0],2);
		$rep->AmountCol(5, 6, $for_dr_total[1],2);
		$rep->AmountCol(6, 7, $for_dr_total[2],2);
		$rep->AmountCol(7, 8, $for_dr_total[3],2);
		$rep->Font('');
		$rep->NewLine();
		
		
		if ($xyz == 0) //VAT
		{
			$for_vat_total[0] += $myrow['t_extended'];
			$for_vat_total[1] += $p_nv;
			$for_vat_total[2] += $p_vat;
			$for_vat_total[3] += $vat;
		}
		else if ($xyz == 1) //VAT
		{
			$for_nv_total[0] += $myrow['t_extended'];
			$for_nv_total[1] += $p_nv;
			$for_nv_total[2] += $p_vat;
			$for_nv_total[3] += $vat;
		}
		else if ($xyz == 2) //DR
		{
			$for_dr_total[0] += $myrow['t_extended'];
			$for_dr_total[1] += $p_nv;
			$for_dr_total[2] += $p_vat;
			$for_dr_total[3] += $vat;
		}
		// ALL RR  TOTAL
		$rep->FontSize +=2;
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 4, 'ALL RR TOTAL : ');
		$rep->AmountCol(4, 5, $for_vat_total[0] + $for_nv_total[0] + $for_dr_total[0],2);
		$rep->AmountCol(5, 6, $for_vat_total[1] + $for_nv_total[1] + $for_dr_total[1],2);
		$rep->AmountCol(6, 7, $for_vat_total[2] + $for_nv_total[2] + $for_dr_total[2],2);
		$rep->AmountCol(7, 8, $for_vat_total[3] + $for_nv_total[3] + $for_dr_total[3],2);
		$rep->Font('');
		$rep->NewLine();
		$rep->FontSize -= 2;
		
		
		// CONSIGNMENT
		// $rep->NewPage();
		$rep->Header();
		$rep->Font('bi');
		$rep->fontSize += 3;
		$rep->TextCol(0, 5, '     CONSIGNMENT');
		$rep->Line($rep->row -5);
		$rep->NewLine(2);
		$rep->fontSize -= 3;
		$rep->Font('');
		
		$sql = "SELECT b.supplier_id, b.gst_no, a.supp_name, t_cos as total, a.end_date 
						FROM 0_cons_sales_header a, 0_suppliers b
						WHERE a.supp_code = b.supp_ref
						AND start_date >= '".date2sql($from)."'
						AND end_date <= '".date2sql($to)."'
						ORDER BY supp_name";
		// echo $sql;die;
		$res = db_query($sql);
		
		$supp_name = $prev_coms = $coms = '';
		
		$cons_total = array(0,0,0,0);
		$cons_total_per_supp = array(0,0,0,0);
		while ($myrow = db_fetch($res))
		{
			$coms =  $myrow["supp_name"] . ' ['. $myrow["gst_no"].']';
			if ($myrow['supp_name']!= $supp_name and $supp_name != '' and $is_group==1)
			{
				$rep->Line($rep->row  + 6);
				$rep->NewLine(.5);
				$rep->font('b');
				$rep->AmountCol(4, 5, $cons_total_per_supp[0], $dec);
				$rep->AmountCol(5, 6, $cons_total_per_supp[1], $dec);
				$rep->AmountCol(6, 7, $cons_total_per_supp[2], $dec);
				$rep->AmountCol(7, 8, $cons_total_per_supp[3], $dec);
				$rep->font('');
				$rep->NewLine(2);
				$cons_total_per_supp = array(0,0,0,0);
			}
			$supp_name = $myrow['supp_name'];
			
				
				if ($prev_coms != $coms AND  $is_group==1)
				{
					$rep->font('b');
					$rep->TextCol(0, 5, $coms);
					$rep->NewLine();
					$rep->font('');
				}
				else if ($is_group!=1)
				{
					$rep->font('b');
					$rep->TextCol(0, 1, $coms);
					$rep->font('');
				}
				$prev_coms = $coms;
				
				$rep->DateCol(1, 2,  ' ' . $myrow['end_date'], true);
				$rep->TextCol(2, 3,' ' );
				$rep->TextCol(3, 4, ' ' . $myrow['invoice_num']);
				$rep->AmountCol(4, 5, $myrow['total'], 2);
				
				
				$p_nv = $p_vat = $vat = 0;
				
				if (!supplier_is_vatable($myrow['supplier_id']))
				{
					$p_nv = $myrow['total'];
					$rep->AmountCol(5, 6, $p_nv, 2);
					$purch_non_vat_total += $p_nv;
				}
				else
				{
					$p_vat = round($myrow['total']/1.12 ,2);
					$vat = round($myrow['total'] - $p_vat , 2);
					$rep->AmountCol(6, 7, $p_vat, 2);
					$rep->AmountCol(7, 8, $vat, 2);
					
					$purch_vat_total += $p_vat;
					$vat_total += $vat;
				}
				
				$cons_total[0] += $myrow['total'];
				$cons_total[1] += $p_nv;
				$cons_total[2] += $p_vat;
				$cons_total[3] += $vat;
				
				$cons_total_per_supp[0] += $myrow['total'];
				$cons_total_per_supp[1] += $p_nv;
				$cons_total_per_supp[2] += $p_vat;
				$cons_total_per_supp[3] += $vat;
			
				$rep->NewLine();
		}
		
		if ($is_group==1)
			{
				$rep->Line($rep->row  + 6);
				$rep->NewLine(.5);
				$rep->font('b');
				$rep->AmountCol(4, 5, $cons_total_per_supp[0], $dec);
				$rep->AmountCol(5, 6, $cons_total_per_supp[1], $dec);
				$rep->AmountCol(6, 7, $cons_total_per_supp[2], $dec);
				$rep->AmountCol(7, 8, $cons_total_per_supp[3], $dec);
				$rep->font('');
				$rep->NewLine(2);
				$cons_total_per_supp = array(0,0,0,0);
			}
			
		// CONSIGNMENT TOTAL
		$rep->FontSize +=2;
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 4, 'CONSIGNMENT TOTAL : ');
		$rep->AmountCol(4, 5, $cons_total[0],2);
		$rep->AmountCol(5, 6, $cons_total[1],2);
		$rep->AmountCol(6, 7, $cons_total[2],2);
		$rep->AmountCol(7, 8, $cons_total[3],2);
		$rep->Font('');
		$rep->NewLine();
		$rep->FontSize -= 2;
		
		// GRAND  TOTAL
		$rep->FontSize +=2;
		$rep->Font('bold');
		$rep->Line($rep->row + 10,1);
		$rep->TextCol(0, 1,	'');
		$rep->TextCol(1, 2, '');
		$rep->TextCol(2, 4, 'GRAND TOTAL : ');
		$rep->AmountCol(4, 5, $for_vat_total[0] + $for_nv_total[0] + $for_dr_total[0] + $cons_total[0],2);
		$rep->AmountCol(5, 6, $for_vat_total[1] + $for_nv_total[1] + $for_dr_total[1] + $cons_total[1],2);
		$rep->AmountCol(6, 7, $for_vat_total[2] + $for_nv_total[2] + $for_dr_total[2] + $cons_total[2],2);
		$rep->AmountCol(7, 8, $for_vat_total[3] + $for_nv_total[3] + $for_dr_total[3] + $cons_total[3],2);
		$rep->Font('');
		$rep->NewLine();
		$rep->FontSize -= 2;
		
		$rep->End();
	}
	
?>