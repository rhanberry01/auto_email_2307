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

//----------------------------------------------------------------------------------------------------

print_GL_transactions();

//----------------------------------------------------------------------------------------------------
function get_non_apv_purchase($type, $type_no)
{
	$sql = "SELECT tran_date,
					SUM(if(account='5400',amount,0)) as p_nv,
					SUM(if(account='5450',amount,0)) as p_v,
					SUM(if((account='2310' OR account='1410' OR account='1410010' OR account='1410011') ,amount,0)) as vat,
					memo_
				FROM 0_gl_trans
				WHERE type = $type AND type_no = $type_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return array($row['memo_'],$row['p_nv'],$row['p_v'],$row['vat']);
}
function load_trade_purchases($from_date, $to_date){
	global $db_connections;
	
	$query_ = array();
	
	foreach($db_connections as $key=>$db_con)
	{
		if($db_con['ms_host'] == '')
			continue;
		
		$sql = "(SELECT b.supp_name, b.contact, b.gst_no, b.address,
						SUM(IF(account='5450',amount,0)) as 'p_vat',	 
						SUM(IF(account='1410010',amount,0)) as 'i_tax',
						SUM(IF(account='5400',amount,0)) as 'p_n_vat'
					FROM ".$db_con['dbname'].".`0_gl_trans` a, ".$db_con['dbname'].".`0_suppliers` b
					WHERE person_type_id = 3
					AND tran_date >= '".date2sql($from_date)."'
					AND tran_date <= '".date2sql($to_date)."'
					AND amount > 0
					AND a.person_id = b.supplier_id
					AND b.gst_no != ''
					GROUP BY person_id
					HAVING SUM(IF(account='5450',amount,0)) > 0
					ORDER BY b.supp_name, b.gst_no)";
					
			$query_[] = $sql;
	}
		$sql = "SELECT supp_name, gst_no, address, SUM(p_vat) as p_vat, SUM(i_tax) as i_tax, SUM(p_n_vat) as p_n_vat FROM (";
		
		$sql .= implode ( ' UNION ' , $query_);
		/*
		//group by supp_name and gst_no
		$sql .= ") xxx
					GROUP BY supp_name,REPLACE(REPLACE(TRIM(gst_no), '-', ' '), ' ', '')
					ORDER BY supp_name, gst_no"; 
		*/
		$sql .= ") xxx
					GROUP BY REPLACE(REPLACE(TRIM(gst_no), '-', ' '), ' ', '')
					ORDER BY supp_name, gst_no
					";
					// LIMIT 5";
		$res = db_query($sql,'error.');
		// display_error("TRADE---".$sql);
		return $res;
}
function load_non_trade_purchases($from_date, $to_date){
	global $db_connections;
	
	$query_ = array();
	
	foreach($db_connections as $key=>$db_con)
	{
		if($db_con['ms_host'] == '')
			continue;
	
		$sql = "
					(SELECT
						'".date2sql($to_date)."' as nt_date ,pcd_payee, pcd_tin, SUM(pcd_amount) as tot_amount, SUM(pcd_tax) as tot_tax
					FROM
						".$db_con['dbname'].".0_petty_cash_details
					WHERE
						pcd_date >= '".date2sql($from_date)."'
					AND pcd_date <= '".date2sql($to_date)."'
					AND pcd_ref != ''
					AND pcd_ref != '0'
					AND pcd_w_cv = '1'
					GROUP BY pcd_tin, pcd_payee
					ORDER BY
						pcd_date)
					";
					
		$query_[] = $sql;
	}		
	
	$sql = "SELECT '".date2sql($to_date)."' as nt_date ,pcd_payee, pcd_tin, SUM(tot_amount) as tot_amount, SUM(tot_tax) as tot_tax FROM (";
	
	$sql .= implode ( ' UNION ' , $query_);
	
	/*
	//group by with tin and payee
	$sql .= ") xxx
					GROUP BY TRIM(pcd_tin), TRIM(pcd_payee)
					ORDER BY
						nt_date";
	*/
	
	$sql .= ") xxx
					GROUP BY TRIM(pcd_tin)
					ORDER BY nt_date
					";
					// LIMIT 5";
	
	$res = db_query($sql,'error.');
	// display_error("NONTRADE---".$sql);
	// echo "NONTRADE---".$sql."--<br>";die();
	return $res;
}
function print_GL_transactions()
{
	global $path_to_root, $systypes_array, $systypes_array_short;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$from = $_POST['PARAM_0'];
	$to = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$rep = new FrontReport(_('VAT Relief (Purchases)'), "VATReliefPurchasesReport", 'LONG',8 ,'L');
	// $dec = user_price_dec();


	// // $cols = array(0, 20, 90, 145, 205, 275, 345, 415, 485, 555);
	// $cols = array(0, 80, 180, 380, 420, 520, 600, 415, 485, 710);
	// // $aligns = array('left', 'left', 'left', 'left', 'right', 'right', 'right', 'right', 'right');
	// $aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'left', 'left');
	// // $headers = array('','Branch','Delivery Date', 'Inv. #', 'Amount','Purch. NON-VAT', 'Purch. VAT', '12% VAT', ' Others');
	// $headers = array('Taxable Month','Taxpayer Identification','Registered Name', 'Name of Supplier', 'Supplier\'s Address','Amount of Gross Purchase', 'Amount of Exempt Purchase', 'Amount of Zero-rated Purchase', 'Amount of Taxable Purchase', 'Amount of Purchase of Services', 'Amount of Purchase of Capital Goods', 'Amount of Purchase of Goods other than Capital Goods', 'Amount of Input Tax', 'Amount of Gross Taxable Purchase');

	// $params =   array( 	0 => $comments,
    				    // 1 => array('text' => _('Period'), 'from' => $from, 'to' => $to));
						
	// $rep->Font();
	// $rep->Info($params, $cols, $headers, $aligns);
	// $rep->HeaderVAT();
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	
	$format_str_right =& $rep->addFormat();
	$format_str_right->setAlign('right');
	
	$format_str_left =& $rep->addFormat();
	$format_str_left->setAlign('left');
	
	$format_str_right_bold =& $rep->addFormat();
	$format_str_right_bold->setBold();
	$format_str_right_bold->setAlign('right');

	$rep->sheet->writeString($rep->y, 0, 'PURCHASE TRANSACTION', $format_bold);
	$rep->NewLine();
	$rep->sheet->writeString($rep->y, 0, 'RECONCILIATION OF LISTING FOR ENFORCEMENT', $format_bold);
	$rep->NewLine();
	$rep->NewLine();
	$rep->NewLine();
	$rep->NewLine();
	$rep->sheet->writeString($rep->y, 0, 'TIN: '.get_company_pref('gst_no'), $format_bold);
	$rep->NewLine();
	$rep->sheet->writeString($rep->y, 0, 'OWNER\'S NAME: '.strtoupper(get_company_pref('coy_name')), $format_bold);
	$rep->NewLine();
	$rep->sheet->writeString($rep->y, 0, 'OWNER\'S TRADE NAME: '.strtoupper(get_company_pref('coy_name')), $format_bold);
	$rep->NewLine();
	$rep->sheet->writeString($rep->y, 0, 'OWNER\'S ADDRESS: '.strtoupper(get_company_pref('postal_address')), $format_bold);
	$rep->NewLine();
	$rep->NewLine();
	
	$header = $content = $total = array();
	$header = array('TAXABLE MONTH', 'TAXPAYER IDENTIFICATION NUMBER', 'REGISTERED NAME',
	'NAME OF SUPPLIER', 'SUPPLIER\'S ADDRESS','AMOUNT OF GROSS PURCHASE', 'AMOUNT OF EXEMPT PURCHASE',
	'AMOUNT OF ZERO-RATED PURCHASE', 'AMOUNT OF TAXABLE PURCHASE', 'AMOUNT OF PURCHASE OF SERVICES',
	'AMOUNT OF PURCHASE OF CAPITAL GOODS', 'AMOUNT OF PURCHASE OTHER THAN CAPITAL GOODS', 'AMOUNT OF INPUT TAX',
	'AMOUNT OF GROSS TAXABLE PURCHASE'
	);
	$x = 0;

	$gross_purchase_amt = $exempt_purchase_amt = $zero_rated_amt = 
	$taxable_purchase_amt = $purch_services_amt = $purch_capital_goods_amt = 
	$purch_goods_other_than_capital_goods = $input_tax_amt = $gross_taxable_purch_amt = 0;
	
	$tot_gross_purchase = $tot_input_tax_amt = $tot_purchase_non_vat_amt = 
	$tot_zero_rated_amt = $tot_taxable_purchase_amt = $tot_purch_services_amt = 
	$tot_purch_capital_goods_amt = $tot_purch_goods_other_than_capital_goods = 
	$tot_gross_taxable_purch_amt = 0;
	
	$gross_purchase_amt2 = $exempt_purchase_amt2 = $zero_rated_amt2 = 
	$taxable_purchase_amt2 = $purch_services_amt2 = $purch_capital_goods_amt2 = 
	$purch_goods_other_than_capital_goods2 = $input_tax_amt2 = $gross_taxable_purch_amt2 = 0;
	
	$tot_gross_purchase2 = $tot_input_tax_amt2 = $tot_purchase_non_vat_amt2 = 
	$tot_zero_rated_amt2 = $tot_taxable_purchase_amt2 = $tot_purch_services_amt2 = 
	$tot_purch_capital_goods_amt2 = $tot_purch_goods_other_than_capital_goods2 = 
	$tot_gross_taxable_purch_amt2 = 0;
	
	foreach($header as $dis_header){
		$rep->sheet->writeString($rep->y, $x, $dis_header, $format_bold);
		$x++;
	}
	
	$res = load_trade_purchases($from, $to);
	while($row = db_fetch($res)){
		$rep->NewLine();
		
		$supp_name = $row['supp_name'];
		$contact = $row['contact'];
		$address = $row['address'];
		$tin = str_replace("-", "", trim($row['gst_no']));
		// $tin = substr($tin, 0, -4);
		$tin = substr($tin, 0, 9);
		$tin = addslashes($tin);
		
		$gross_purchase_amt = (round($row['p_vat'],2) + round($row['p_n_vat'],2));
		$taxable_purchase_amt = round($row['p_vat'],2);
		$purchase_non_vat_amt = round($row['p_n_vat'],2);
		$purch_goods_other_than_capital_goods = round($row['p_vat'],2);
		$input_tax_amt = round($row['i_tax'],2);
		$gross_taxable_purch_amt = ($taxable_purchase_amt + $input_tax_amt);
		
		$contents = array(array($to, $format_str_left), 
		array(implode("-", str_split($tin, 3)), $format_str_left), 
		array(strtoupper($supp_name), $format_str_left),
		array(strtoupper($supp_name), $format_str_left), 
		array($address, $format_str_left), 
		array(number_format($gross_purchase_amt, 2), $format_str_right), 
		array(number_format($purchase_non_vat_amt, 2), $format_str_right),
		array(number_format($zero_rated_amt, 2), $format_str_right), 
		array(number_format($taxable_purchase_amt, 2), $format_str_right), 
		array(number_format($purch_services_amt, 2), $format_str_right),
		array(number_format($purch_capital_goods_amt, 2), $format_str_right), 
		array(number_format($purch_goods_other_than_capital_goods, 2), $format_str_right), 
		array(number_format($input_tax_amt, 2), $format_str_right),
		array(number_format($gross_taxable_purch_amt, 2), $format_str_right)
		);
		
		foreach($contents as $keyx => $content){
			$rep->sheet->writeString($rep->y, $keyx, $content[0], $content[1]);
		}
		
		$tot_gross_purchase += $gross_purchase_amt;
		$tot_purchase_non_vat_amt += $purchase_non_vat_amt;
		$tot_zero_rated_amt += $zero_rated_amt;
		$tot_taxable_purchase_amt += $taxable_purchase_amt;
		$tot_purch_services_amt += $purch_services_amt;
		$tot_purch_capital_goods_amt += $purch_capital_goods_amt;
		$tot_purch_goods_other_than_capital_goods += $purch_goods_other_than_capital_goods;
		$tot_input_tax_amt += $input_tax_amt;
		$tot_gross_taxable_purch_amt += $gross_taxable_purch_amt;
		
	}
	
	$rep->NewLine();
	
	/* foreach($header as $dis_header){
		$rep->sheet->writeString($rep->y, $x, $dis_header, $format_bold);
		$x++;
	} */
	
	$res2 = load_non_trade_purchases($from, $to);
	while($row2 = db_fetch($res2)){
		$rep->NewLine();
		
		$supp_name2 = $row2['pcd_payee'];
		// $contact = $row['contact'];
		$address2 = '';
		$tin2 = str_replace("-", "", trim($row['pcd_tin']));
		// $tin = substr($tin, 0, -4);
		$tin2 = substr($tin2, 0, 9);
		$tin2 = addslashes($tin2);
		
		$purchase_non_vat_amt2 = 0;
			if($row2['tot_tax'] == 0){
				$purchase_non_vat_amt2 = round($row2['tot_amount'],2);
				$taxable_purchase_amt2 = 0;
				$purch_goods_other_than_capital_goods2 = 0;
				$input_tax_amt2 = 0;
				$gross_taxable_purch_amt2 = 0;
			}else{
				$taxable_purchase_amt2 = round($row2['tot_amount'],2);
				$purch_goods_other_than_capital_goods2 = round($row2['tot_amount'],2);
				$input_tax_amt2 = round($row2['tot_tax'],2);
				$gross_taxable_purch_amt2 = ($taxable_purchase_amt2 + $input_tax_amt2);
			}
		$gross_purchase_amt2 = ($taxable_purchase_amt2 + $purchase_non_vat_amt2);
			
		$contents = array(array($to, $format_str_left), 
		array(implode("-", str_split($tin2, 3)), $format_str_left), 
		array(strtoupper($supp_name2), $format_str_left),
		array(strtoupper($supp_name2), $format_str_left), 
		array($address2, $format_str_left), 
		array(number_format($gross_purchase_amt2, 2), $format_str_right), 
		array(number_format($purchase_non_vat_amt2, 2), $format_str_right),
		array(number_format($zero_rated_amt2, 2), $format_str_right), 
		array(number_format($taxable_purchase_amt2, 2), $format_str_right), 
		array(number_format($purch_services_amt2, 2), $format_str_right),
		array(number_format($purch_capital_goods_amt2, 2), $format_str_right), 
		array(number_format($purch_goods_other_than_capital_goods2, 2), $format_str_right), 
		array(number_format($input_tax_amt2, 2), $format_str_right),
		array(number_format($gross_taxable_purch_amt2, 2), $format_str_right)
		);
		
		foreach($contents as $keyx => $content){
			$rep->sheet->writeString($rep->y, $keyx, $content[0], $content[1]);
		}
		
		$tot_gross_purchase2 += $gross_purchase_amt2;
		$tot_purchase_non_vat_amt2 += $purchase_non_vat_amt2;
		$tot_zero_rated_amt2 += $zero_rated_amt2;
		$tot_taxable_purchase_amt2 += $taxable_purchase_amt2;
		$tot_purch_services_amt2 += $purch_services_amt2;
		$tot_purch_capital_goods_amt2 += $purch_capital_goods_amt2;
		$tot_purch_goods_other_than_capital_goods2 += $purch_goods_other_than_capital_goods2;
		$tot_input_tax_amt2 += $input_tax_amt2;
		$tot_gross_taxable_purch_amt2 += $gross_taxable_purch_amt2;
		
	}
	
	$rep->NewLine();
	$rep->NewLine();
	
	$z = 0;
	
	$total = array('GRAND TOTAL',
	' ', 
	' ',
	' ', 
	' ', 
	number_format(($tot_gross_purchase + $tot_gross_purchase2),2), 
	number_format(($tot_purchase_non_vat_amt + $tot_purchase_non_vat_amt2),2),
	number_format(($tot_zero_rated_amt + $tot_zero_rated_amt2),2),
	number_format(($tot_taxable_purchase_amt + $tot_taxable_purchase_amt2),2),
	number_format(($tot_purch_services_amt + $tot_purch_services_amt2),2),
	number_format(($tot_purch_capital_goods_amt + $tot_purch_capital_goods_amt2),2),
	number_format(($tot_purch_goods_other_than_capital_goods + $tot_purch_goods_other_than_capital_goods2),2),
	number_format(($tot_input_tax_amt + $tot_input_tax_amt2),2),
	number_format(($tot_gross_taxable_purch_amt + $tot_gross_taxable_purch_amt2),2)
	);
	
	foreach($total as $dis_total){
		$rep->sheet->writeString($rep->y, $z, $dis_total, $format_str_right_bold);
		$z++;
	}
	
	/* Set width of all columns */
	$rep->sheet->setColumn(0,1,25);
	$rep->sheet->setColumn(2,2,50.14);
	$rep->sheet->setColumn(3,4,50);
	$rep->sheet->setColumn(5,13,25);
	
	$rep->NewLine();
	$rep->NewLine();
	
	$rep->sheet->writeString($rep->y, 0, 'END OF REPORT', $format_str_left);
	
	$rep->NewLine();
	
	$rep->End();
}

?>