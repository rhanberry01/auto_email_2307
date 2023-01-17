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
$path_to_root="../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/checkprint/includes/cv_mailer.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 400);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Deferred Assets Breakdown"), false, false, "", $js);

// //-----------------------------------------------------------------------------------
// // Ajax updates
// //
// if (get_post('Search'))
// {
	// $Ajax->activate('journal_tbl');
// }
// //--------------------------------------------------------------------------------------
// if (!isset($_POST['filterType']))
	// $_POST['filterType'] = -1;

// if (isset($_POST['Search']))
// {
	// global $Ajax;
	
	// $Ajax->activate('tbl_');
// }

//==========FUNCTIONS - start ==========//
function get_total_sales_and_suki($from_date, $to_date){
	$sql = "SELECT SUM(ts_sales) as total_ts_sales, SUM(ts_suki) as total_ts_suki 
				FROM `0_salestotals`
				WHERE ts_date_remit BETWEEN '$from_date' AND '$to_date'";
	$res = db_query($sql,'error.');
	
	return $res;
}
function get_finished_sales_for_formula_1_gross($date1, $date2)
{
	$tax_rate = 12;
	$sql = "SELECT  SUM(Extended) as gross_sales
				FROM [dbo].[FinishedSales] 
				WHERE CAST(LogDate AS DATE) >= '".date2sql($date1)."'
				AND CAST(LogDate AS DATE) <= '".date2sql($date2)."'
				AND Voided = 0";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	// die();
	$gross_sales = $row['gross_sales'];
	
	// return array(round($gross_cost,2), round($gross_sales,2),round($qty,4));
	return round($gross_sales,2);
}
function get_vat_exempt($date1, $date2){
	$sql = "SELECT  SUM(Extended) as gross_sales
				FROM [dbo].[FinishedSales] 
				WHERE CAST(LogDate AS DATE) >= '".date2sql($date1)."'
				AND CAST(LogDate AS DATE) <= '".date2sql($date2)."'
				AND pVatable = 0
				AND Voided = 0";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	$gross_sales = $row['gross_sales'];
	return round($gross_sales,2);
}
function load_other_income($from_date, $to_date){
	global $db_connections;
	
	$query_ = array();
	
	foreach($db_connections as $key=>$db_con){
		if($db_con['ms_host'] == '')
			continue;
		
		/* Retrieve transactions with OR - receipt type only */
		$sql = "(SELECT
						'".date2sql($to_date)."' as nt_date ,bd_payee, bd_memo, bd_receipt_type, SUM(bd_oi) as tot_amount, SUM(bd_vat) as tot_tax
					FROM
						".$db_con['dbname'].".0_other_income_payment_header AS oh
					LEFT JOIN ".$db_con['dbname'].".0_other_income_payment_details AS od
					ON oh.bd_trans_no = od.bd_det_trans_no
					WHERE
						oh.bd_trans_date >= '".date2sql($from_date)."'
					AND oh.bd_trans_date <= '".date2sql($to_date)."'
					AND (oh.bd_vat != 0 OR bd_wt != 0)
					AND oh.bd_cleared = '1'
					AND oh.bd_receipt_type = 'OR'
					GROUP BY bd_payee
					ORDER BY
						oh.bd_trans_date)
					";  /* Original */
					
		$query_[] = $sql;
	}
	
	$sql = "SELECT '".date2sql($to_date)."' as nt_date ,bd_payee, bd_memo, bd_receipt_type, SUM(tot_amount) as tot_amount, SUM(tot_tax) as tot_tax FROM ("; /* Original */
	$sql .= implode ( ' UNION ' , $query_);
	$sql .= ") xxx
					GROUP BY TRIM(bd_payee)
					ORDER BY
						nt_date";
	$res = db_query($sql,'error.');
	// display_error("OTHER INCOME---".$sql);
	return $res;
}
function load_vat_return_values($from_date, $to_date){
	global $db_connections;
	
	$query_ = $fixed_assets_accts = array();
	
	$oth_income_array = $capital_goods_array = array();
	$fixed_assets_accts = array(
										"'1500'",
										"'1510'",
										"'1520'",
										"'1530'",
										"'1540'",
										"'1550'",
										"'1560'",
										"'155010'",
										"'1540010'",
										"'1540020'",
										"'1550020'",
										"'1540030'",
										"'1540011'",
										"'1540012'",
										"'1570'",
										"'1580'",
										"'1590'",
										"'1531'",
										"'155011'",
										"'1540013'"
										);
	foreach($db_connections as $key=>$db_con)
	{
		if($db_con['ms_host'] == '')
			continue;
		
		$sql = "(SELECT 
						'".$db_con['srs_branch']."' as branch,
						SUM(IF(account IN (".implode(',', $fixed_assets_accts).") AND amount > 0, amount, 0)) AS 'property_equipment_amt'
					FROM ".$db_con['dbname'].".`0_gl_trans`
					WHERE tran_date >= '".date2sql($from_date)."'
					AND tran_date <= '".date2sql($to_date)."')";
					
			$query_[] = $sql;
	}
		// $sql = "SELECT supp_name, gst_no, SUM(p_vat) as p_vat, SUM(i_tax) as i_tax, SUM(p_n_vat) as p_n_vat FROM ("; //orig
		$sql = "SELECT branch, SUM(property_equipment_amt) as property_equipment_amt FROM (";
		
		$sql .= implode ( ' UNION ' , $query_);
		
		
		//#group by supp_name and gst_no
		// $sql .= ") xxx
					// GROUP BY supp_name,REPLACE(REPLACE(TRIM(gst_no), '-', ' '), ' ', '')
					// ORDER BY supp_name, gst_no"; 

		$sql .= ") xxx
					GROUP BY branch";
		$res = db_query($sql,'error.');
		display_error($sql);
		return $res;
}
function load_purchases($is_service,$branch, $from_date, $to_date){
	global $db_connections;
	
	$query_ = array();
	
	$input_tax_codes = $services = $others = $others_serv = array();
	
	$input_tax_codes = array('1410','1410010','1410011','1410012','1410013','1410100','1410014','141000409');
	
	$services = array('6040','6060','6100','6130','6170','6180',
				'6200','6220','6240','6250','6260','6290',
				'6290011','6010010','9000');
				
	$others_serv = array('6010','6150','6190','8000');			
	
	$others = array('6110','6160','6280','6300','6112','6010','6150','6190','8000');
	
	$key_ = null;
	foreach($db_connections as $key=>$db_con){
		if($db_con['srs_branch'] != $branch)
			continue;
		$key_ = $key;
		// break;
	}
	
		$sql = "SELECT
			SUM(amount) as tot_amount, '$branch'
			FROM ".$db_connections[$key_]['dbname'].".`0_gl_trans` a
			JOIN ".$db_connections[$key_]['dbname'].".`0_chart_master` b ON a.account = b.account_code
			WHERE a.account IN
			";
			
			if($is_service > 0){
				$sql .= " ('".implode("','", $services)."') ";
			}else{
				$sql .= " ('".implode("','", $others)."') ";
			}
			
		$sql .= "
				AND a.amount > 0
				AND a.tran_date >= '".date2sql($from_date)."'
				AND a.tran_date <= '".date2sql($to_date)."'
				AND CONCAT(type,'~',type_no) 
					IN (SELECT DISTINCT CONCAT(x.type, '~', x.type_no)
							FROM ".$db_connections[$key_]['dbname'].".`0_gl_trans` x
							WHERE x.tran_date >= '".date2sql($from_date)."'
							AND x.tran_date <= '".date2sql($to_date)."'
							AND x.account
							IN ('".implode("','", $input_tax_codes)."') )";

		$res = db_query($sql,'error.');
		// display_error('For Purchases: Services/Other: '.$sql);
		$row = db_fetch($res);
		return $row[0];
	
	/* $sql = "SELECT *
				FROM 0_gl_trans a
				JOIN 0_chart_master b ON a.account = b.account_code
				WHERE a.account IN";
				if($is_service > 0){
					// Purchase: Services
					$sql .= " ('".implode("','", $services)."') ";
				}else{
					// Purchase: Others
					$sql .= " ('".implode("','", $others)."') ";
				}
				
	$sql .= "			
				AND a.amount > 0
				AND a.tran_date >= '2015-12-01'
				AND a.tran_date <= '2015-12-31'
				AND CONCAT(type,'~',type_no) 
					IN (SELECT DISTINCT CONCAT(x.type, '~', x.type_no)
							FROM 0_gl_trans x
							WHERE x.tran_date >= '2015-12-01'
							AND x.tran_date <= '2015-12-31'
							AND x.account
							IN ('".implode("','", $input_tax_codes)."') )"; */
							
}
//==========FUNCTIONS - end ==========//

	start_form();
		
		start_table();
			date_cells('From : ', 'from_date');
			date_cells('To : ', 'to_date');
			submit_cells('generate_btn', 'Generate');
		end_table(1);

		div_start('content');
		
		if(isset($_POST['generate_btn'])){
			
			global $db_connections;
			
			$gross = $suki = $vat_exempt = $gross_vatable = $net_sales = $output_vat_12 = $wholesale_retail = $total_WR_output_vat = 
			$total_business_services = $total_lease_of_real_property = $total_output_vat = 0;
			
			start_table($table_style2);
				$k = 0;
				$vat_purchases = $current_input_vat = $cap_goods_below_1m_vat = $cap_goods_above_1m_vat = $goods_other_than_capital_vat = $services_vat = $others_vat = 0;
					
					/* TEST */
					$res = load_vat_return_values($_POST['from_date'], $_POST['to_date']);
					
					$content = array();
					$labels = array('<b><u>Sales/Receipts</u></b>', //0
											  'Gross Sales', //1
											  'Suki Points', //2
											  '', //3
											  'Net Sales', //4
											  'VAT Exempt', //5
											  'VAT Zero-Rated', //6
											  '', //7
											  'Gross VATable', //8
											  '12% Output VAT', //9
											  '', //10
											  'Wholesale/Retail', //11
											  'Business Services', //12
											  'Lease of Real Property', //13
											  '', //14
											  'Total sales/receipts', //15
											  'VAT exempt', //16
											  '', //17
											  '<b>Total sales/revenues</b>', //18
											  '', //19
											  '<b><u>Output VAT</u></b>', //---label lang to  //20
											  'Wholesale & Retail', //21
											  'Business Services', //22
											  'Lease of real property', //23
											  '', //24
											  '<b>Total Output VAT</b>', //25
											  '', //26
											  '<b><u>Purchase/Disbursements</u></b>', //---label lang to  //27
											  'Capital goods below 1M', //28
											  'Capital goods above 1M', //29
											  'Goods other than capital', //30
											  'Services', //31
											  'Others', //32
											  '', //33
											  '<b>VAT purchases</b>', //34
											  'VAT exempt', //35
											  '', //36
											   '<b>Total Purchases/Disbursements</b>', //37
											  '', //38
											  '<b><u>Input VAT</u></b>', //---label lang to  //39
											  'Beginning: Excess Input VAT',  //40
											  '&nbsp;&nbsp;Deferred Input VAT', //41
											  '', //42
											   '<b>Input VAT from previous period</b>', //43
											  '', //44
											  'Capital goods below 1M', //45
											  'Capital goods above 1M', //46
											  'Goods other than capital', //47
											  'Services', //48
											  'Others', //49
											  '',  //50
											  '<b>Current Input Vat</b>',  //51
											  '<b>Input VAT available for the period</b>', //52
											  '<b>Deferred input VAT - end</b>', //53
											  '<b>Input allocable to exempt SALES</b>', //54
											  '<b>Input VAT allowable for the period</b>', //55
											  '<b><font color="red">VAT PAYABLE/(EXCESS INPUT VAT)</font></b>', //56
											  'Computation for Allocable'); //57
					
					while($row = db_fetch($res)){
						
						$content[$row['branch']][] = ''; //0
						$content[$row['branch']][] = $row['gross_sales_amt']; //1 Gross Sales
						$content[$row['branch']][] = $row['suki_pts_amt']; //2 Suki Points
						$content[$row['branch']][] = ''; //----blank
						$content[$row['branch']][] = $row['gross_sales_amt'] - $row['suki_pts_amt']; // 4 Net Sales
						$content[$row['branch']][] = $row['sales_vat_exempt_amt']; //5 Vat Exempt
						$content[$row['branch']][] = $row['vat_zero_rated_amt']; //6 Vat Zero rated
						$content[$row['branch']][] = ''; //----7 blank
						$content[$row['branch']][] = ($row['gross_sales_amt'] - $row['suki_pts_amt']); //8 Gross Vatable -> (Gross Sales - Suki Pts)
						$content[$row['branch']][] = (($row['gross_sales_amt'] - $row['suki_pts_amt'])/1.12)*0.12; //9 12% Output Vat -> (Gross Vatable/1.12)*0.12 (TEMPORARY since wala pang 23102318 gl code for output tax)
						$content[$row['branch']][] = ''; //----10 blank
						$content[$row['branch']][] = ($row['gross_sales_amt'] - $row['suki_pts_amt']) - $row['output_vat_amt']; //11 Wholesale / Retail -> (Gross Vatable - 12% Output Vat)
						$content[$row['branch']][] = $row['business_services_amt']; //----for business services
						$content[$row['branch']][] = $row['real_property_lease_amt']; //13 lease of real property
						$content[$row['branch']][] = ''; //----14 blank
						$content[$row['branch']][] = ((($row['gross_sales_amt'] - $row['suki_pts_amt']) - $row['output_vat_amt']) + $row['business_services_amt'] + $row['real_property_lease_amt']); //----15 Total sales/receipts NOTE!!! Also include business services
						
						$content[$row['branch']][] = $row['sales_vat_exempt_amt']; //----16 VAT Exempt (same as //5 Vat Exempt)
						$content[$row['branch']][] = ''; //----17 blank
						$content[$row['branch']][] = ((($row['gross_sales_amt'] - $row['suki_pts_amt']) - $row['output_vat_amt']) + $row['business_services_amt'] + $row['real_property_lease_amt']) + $row['sales_vat_exempt_amt']; //----18 total sales/revenues (Total Sales/Receipts + VAT Exempt)
						$content[$row['branch']][] = ''; //----19 blank
						$content[$row['branch']][] = ''; //----20 blank
						$content[$row['branch']][] = ((($row['gross_sales_amt'] - $row['suki_pts_amt']) - $row['output_vat_amt'])*0.12); //----21 Get VAT of wholesale & retail ->old value: $row['wholesale_retail_sales_vat_amt']
						$content[$row['branch']][] = ($row['business_services_amt']*0.12); //----22 (business services * 0.12) // 22 Get VAT of business services
						$content[$row['branch']][] = ($row['real_property_lease_amt']*0.12); //23 Get VAT of real property lease
						$content[$row['branch']][] = ''; //----24 blank
						$content[$row['branch']][] = (((($row['gross_sales_amt'] - $row['suki_pts_amt']) - $row['output_vat_amt'])*0.12) + ($row['business_services_amt']*0.12) + ($row['real_property_lease_amt']*0.12));//25 TOTAL OUTPUT VAT ~~NOTE!!! Also include business services
						$content[$row['branch']][] = ''; //----26 blank
						$content[$row['branch']][] = ''; //----27 blank Purchase/Disbursements
						
						$content[$row['branch']][] = ($row['capital_goods_amt'] < 1000000 ? $row['capital_goods_amt'] : 0); //----28 Capital goods below 1M
						$content[$row['branch']][] = ($row['capital_goods_amt'] > 1000000 ? $row['capital_goods_amt'] : 0); //----29 Capital goods above 1M
						
						$content[$row['branch']][] = $row['purchases_vat_amt']; //----30 Goods other than capital
						$content[$row['branch']][] = load_purchases(1,$row['branch'], $_POST['from_date'], $_POST['to_date']);
						$content[$row['branch']][] = load_purchases(0,$row['branch'], $_POST['from_date'], $_POST['to_date']); //----32 Others
						$content[$row['branch']][] = ''; //----33 blank
						
						/* For VAT Purchases */
						$vat_purchases = ($row['capital_goods_amt'] < 1000000 ? $row['capital_goods_amt'] : 0) + ($row['capital_goods_amt'] > 1000000 ? $row['capital_goods_amt'] : 0) + $row['purchases_vat_amt'] + load_purchases(1,$row['branch'], $_POST['from_date'], $_POST['to_date']) + load_purchases(0,$row['branch'], $_POST['from_date'], $_POST['to_date']);
						
						$content[$row['branch']][] = $vat_purchases; //----34 VAT purchases
						$content[$row['branch']][] = $row['purchases_vat_exempt_amt']; //35 vat exempt of purchases
						$content[$row['branch']][] = ''; //----36 blank
						$content[$row['branch']][] = ($vat_purchases + $row['purchases_vat_exempt_amt']); //----37 Total Purchases/Disbursements
						$content[$row['branch']][] = ''; //----38 blank
						$content[$row['branch']][] = ''; //----39 Input VAT blank
						$content[$row['branch']][] = $row['excess_input_vat_amt']; //40 Beginning: Excess Input VAT
						$content[$row['branch']][] = $row['deferred_input_vat_amt']; //41 Deferred Input VAT
						$content[$row['branch']][] = ''; //----42 blank
						$content[$row['branch']][] = ($row['excess_input_vat_amt']+$row['deferred_input_vat_amt']); //----43 Input VAT from previous period
						$content[$row['branch']][] = ''; //----44 blank
						$content[$row['branch']][] = (($row['capital_goods_amt'] < 1000000 ? $row['capital_goods_amt'] : 0) * 0.12); //----45 Capital goods below 1M * 0.12
						$content[$row['branch']][] = (($row['capital_goods_amt'] > 1000000 ? $row['capital_goods_amt'] : 0) * 0.12); //----46 Capital goods above 1M * 0.12
						$content[$row['branch']][] = $row['purchases_vat_amt'] * 0.12; //----47 Goods other than capital * 0.12
						$content[$row['branch']][] = (load_purchases(1, $row['branch'], $_POST['from_date'], $_POST['to_date']) * 0.12); //----48 Services * 0.12
						$content[$row['branch']][] = (load_purchases(0, $row['branch'], $_POST['from_date'], $_POST['to_date']) * 0.12); //----49 Others * 0.12
						$content[$row['branch']][] = ''; //----50
						
						$cap_goods_below_1m_vat = (($row['capital_goods_amt'] < 1000000 ? $row['capital_goods_amt'] : 0) * 0.12);
						$cap_goods_above_1m_vat_1m_vat = (($row['capital_goods_amt'] > 1000000 ? $row['capital_goods_amt'] : 0) * 0.12);
						$goods_other_than_capital_vat = $row['purchases_vat_amt'] * 0.12;
						$services_vat = (load_purchases(1, $row['branch'], $_POST['from_date'], $_POST['to_date']) * 0.12);
						$others_vat = (load_purchases(0, $row['branch'], $_POST['from_date'], $_POST['to_date']) * 0.12);
						$current_input_vat = ($cap_goods_below_1m_vat + $cap_goods_above_1m_vat_1m_vat + $goods_other_than_capital_vat + $services_vat + $others_vat);
						// echo " computation: ".$current_input_vat." = ".$cap_goods_below_1m_vat." + ".$cap_goods_above_1m_vat." + ".$goods_other_than_capital_vat." + ".$services_vat." + ".$others_vat." </br>";
						
						$content[$row['branch']][] = $current_input_vat; //----51 Current Input Vat
						$content[$row['branch']][] = ($row['excess_input_vat_amt']+$row['deferred_input_vat_amt']) + $current_input_vat; //----52 Input VAT available for the period
						$content[$row['branch']][] = ''; //----53 Deferred input VAT - end
						$content[$row['branch']][] = ''; //----54 Input allocable to exempt SALES
						$content[$row['branch']][] = ''; //----55 Input VAT allowable for the period
						$content[$row['branch']][] = ''; //----56 VAT PAYABLE/(EXCESS INPUT VAT)
						$content[$row['branch']][] = $row['vat_payable_amt']; //----57 VAT PAYABLE/(EXCESS INPUT VAT)
					}
					
					$neg_vals = array(40, 43);
					
					/******************/
					// alt_table_row_color($k);
						// label_cell('SALES/RECEIPTS','class=tableheader');
						// // label_cell(strtoupper($db_connections[$_SESSION['wa_current_user']->company]['srs_branch']),'style="font-weight: bold; text-align: center;"');
						// foreach($db_connections as $key=>$db_con)
						// {	
							// label_cell(strtoupper($db_con['srs_branch']),'style="font-weight: bold; text-align: center;"');
						// }
					// end_row();
					
					// foreach ($labels as $key=>$label)
					// {
						// alt_table_row_color($k);
						
						// label_cell($label);
						// foreach($db_connections as $key2=>$db_con2)
						// {
								// // echo $content[$db_con2['srs_branch']][$key]."~~err<br>"; //Display value
								// if($content[$db_con2['srs_branch']][$key] === ''){
									// label_cell('&nbsp;');
								// }else{
									// amount_cell(!in_array($key,$neg_vals) ?  abs($content[$db_con2['srs_branch']][$key]) : $content[$db_con2['srs_branch']][$key]);
								// }
						// }
						
						// end_row();
					// }
					/******************/
						alt_table_row_color($k);
								label_cell('Branch','class=tableheader');
								for ($i = 1; $i <= 12; $i++) {
									// echo $i;
									// label_cell(strtoupper(date('M', strtotime($i))),'style="font-weight: bold; text-align: center;"');
									label_cell(strtoupper(date("F", mktime(0, 0, 0, $i))),'style="font-weight: bold; text-align: center;"');
								}
						end_row();
						foreach($db_connections as $key=>$db_con)
						{	
							alt_table_row_color($k);
								label_cell(strtoupper($db_con['srs_branch']),'style="font-weight: bold; text-align: center;"');
								

								// foreach($db_connections as $key=>$db_con)
								// {
									// label_cell(strtoupper($db_con['srs_branch']),'style="font-weight: bold; text-align: center;"');
								// }
								
							end_row();
						}
				
			end_table(1);
		}
		
		div_end();
	end_form();

end_page();

?>
