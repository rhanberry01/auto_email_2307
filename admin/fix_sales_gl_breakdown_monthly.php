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
$page_security = 'SA_GLSETUP';

$path_to_root = "..";
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/admin/db/voiding_db.inc");


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Fix Sales GL Breakdown Monthly Using Finished Sales', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function month_has_adjustment($tran_date)
{
	$sql = "SELECT * FROM 0_gl_trans WHERE memo_ LIKE 'Sales using Finished Sales Table for%'  
				AND tran_date = '".date2sql($tran_date)."'";
	db_query($sql);
	$res = db_query($sql);
	
	return db_num_rows($res) > 0;
}

function void_month_adj($tran_date)
{
	// void month first =====================
	$sql = "SELECT DISTINCT type_no 
	FROM 0_gl_trans
	WHERE memo_ LIKE 'Sales using Finished Sales Table for%'
	AND tran_date = '".date2sql($tran_date)."'
	AND type = 0
	AND amount != 0
	GROUP BY type, type_no";
	// display_error($sql);
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{	
		$x = void_transaction('0', $row['type_no'], $tran_date, 'has computation error');
		// display_error($x . $row['type_no']);
	}
	//===================================
}
function write_monthly_sales_jv($tran_date, $values)
{
	global $Refs;
	$trans_type = 0;
	
	$memo_ = "Sales using Finished Sales Table for ". date('F Y', strtotime($tran_date));
	
	void_month_adj($tran_date);
	
    $trans_id = get_next_trans_no($trans_type);
	$reference = get_next_reference($trans_type);
	// // display_error($trans_id);
	// // ====================
	// // CREATE NEW JOURNAL ENTRY
	
		if (round($values['sales_vat']-$values['fsales_vat'],2) != 0)
		add_gl_trans($trans_type, $trans_id, $tran_date, 4000, 0, 0, $memo_, round($values['sales_vat']-$values['fsales_vat'],2));
		// add_gl_trans($trans_type, $trans_id, $tran_date, 4000, 0, 0, $memo_, -round($values['fsales_vat'],2));
		
		if (round($values['nv_sales']-$values['fsales_nv'],2) != 0)
		add_gl_trans($trans_type, $trans_id, $tran_date, 4000020, 0, 0, $memo_, round($values['nv_sales']-$values['fsales_nv'],2));
		// add_gl_trans($trans_type, $trans_id, $tran_date, 4000020, 0, 0, $memo_, -round($values['fsales_nv'],2));
		
		if (round($values['zr_sales']-$values['fsales_zero_rated'],2) != 0)
		add_gl_trans($trans_type, $trans_id, $tran_date, 4000050, 0, 0, $memo_, round($values['zr_sales']-$values['fsales_zero_rated'],2));
		// if ($values['fsales_zero_rated'] != 0)
			// add_gl_trans($trans_type, $trans_id, $tran_date, 4000050, 0, 0, $memo_, -round($values['fsales_zero_rated'],2));
		
		if (round($values['output_vat']-$values['foutput_vat'],2) != 0)
		add_gl_trans($trans_type, $trans_id, $tran_date, 2310, 0, 0, $memo_, round($values['output_vat']-$values['foutput_vat'],2));
		// add_gl_trans($trans_type, $trans_id, $tran_date, 2310, 0, 0, $memo_, -round($values['foutput_vat'],2));
		
		if (round($values['other_income']-$values['fother_income'],2) != 0)
		add_gl_trans($trans_type, $trans_id, $tran_date, 4020, 0, 0, $memo_, round($values['other_income']-$values['fother_income'],2));
		// add_gl_trans($trans_type, $trans_id, $tran_date, 4020, 0, 0, $memo_, -round($values['fother_income'],2));
		
		if (round($values['suki']-$values['suki2'],2) != 0)
		add_gl_trans($trans_type, $trans_id, $tran_date, 4000040, 0, 0, $memo_, round($values['suki']-$values['suki2'],2));
		// add_gl_trans($trans_type, $trans_id, $tran_date, 4000040, 0, 0, $memo_, -round($values['fother_income'],2));
	//====================
	$Refs->save($trans_type, $trans_id, $reference);
	add_audit_trail($trans_type, $trans_id, Today());

	return $trans_id;
}


if (isset($_POST['fix_now']))
{
	$sql = "SELECT * FROM 0_chart_master WHERE account_code = '4000040'";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0) // insert account first
	{
		$sql = "INSERT INTO `0_chart_master` (`account_code`, `account_name`, `account_type`) VALUES ('4000040', 'Sales - Suki Points', '6')";
		db_query($sql,'failed to insert sales - suki points');
		
		$sql = "INSERT INTO `0_chart_types` (`id`, `name`, `class_id`, `parent`) VALUES ('61', 'Sales Discount', '1', '0')";
		db_query($sql,'failed to insert sales discount type');
	}	
	$sql = "UPDATE 0_chart_master SET account_type = 61 WHERE account_code = '4900'";
	db_query($sql,'failed to update sales discount account type');
	
	$from_date = $_POST['from'];
	$to_date = $_POST['to'];
	$times = date_diff2(begin_month($to_date), begin_month($from_date), 'm');
	
	for($i=0;$i<=$times;$i++)
	{
		
		$current_month_beg = begin_month(add_months($from_date,$i));
		$current_month_end = end_month(add_months($from_date,$i));
		
		// // check for journal this month
		// if (month_has_adjustment($current_month_end))
				// continue;
		
		// // display_error($current_month_beg .' - '. $current_month_end);
		
		$sql = "SELECT SUM(IF(account='4900',amount,0)) as suki,
			SUM(IF((account='4000040'),amount,0)) as suki2,
			SUM(IF((account='4000020'),amount,0)) as nv_sales,
			SUM(IF((account='4000050'),amount,0)) as zr_sales,
			SUM(IF((account='4000'),amount,0)) as sales_vat,
			SUM(IF((account='2310'),amount,0)) as output_vat,
			SUM(IF((account='4020'),amount,0)) as other_income
			FROM 0_gl_trans 
			WHERE type = 60 
			AND tran_date >= '".date2sql($current_month_beg)."' 
			AND tran_date <= '".date2sql($current_month_end)."'";
		// display_error($sql);
		$res = db_query($sql);
		$row = db_fetch($res);
		
		// $total_debit = $row[0];
		$suki = $row['suki'];
		$suki2 = abs($row['suki2']);
		$nv_sales = abs($row['nv_sales']);
		$zr_sales = abs($row['zr_sales']);
		$sales_vat = abs($row['sales_vat']);
		$output_vat = abs($row['output_vat']);
		$other_income = abs($row['other_income']);
		
		$total_debit = $old_amt = ($zr_sales + $nv_sales + $sales_vat + $output_vat + $other_income + $suki);
		
		$fsales_vat = $fsales_nv =  $fsales_zero_rated = 0;
		
		list($d,$m,$y) = explode_date_to_dmy($current_month_beg);
		$sql = "SELECT month, sales_vat, sales_nv, sales_zero_vat 
					FROM `x_fsales_monthly`
					WHERE `month` = ". ($m+0)."
					AND year_ = $y";
		// display_error($sql);
		$res = db_query($sql);
		$row = db_fetch($res);
		
		if ($row[1] == '' AND $row[2] == '')
			continue;
		
		$fsales_vat = $row[1];
		$fsales_nv = $row[2];
		
		if (isset($row[3]))
			$fsales_zero_rated = $row[3];
		
		$f_gross = $fsales_vat + $fsales_nv +  $fsales_zero_rated;

		$fsales_nv = round($fsales_nv,2);
		
		$gross_vatable = $fsales_vat - $suki;
		
		$fsales_vat = round($gross_vatable/1.12,2);
		$foutput_vat = round($gross_vatable - $fsales_vat ,2);
		
		$fother_income = $total_debit - ($fsales_nv+$fsales_zero_rated+$fsales_vat+$foutput_vat + $suki2);

		
		$values = array();
		$values['zr_sales'] = $zr_sales;
		$values['fsales_zero_rated'] = $fsales_zero_rated;
		$values['suki'] = $suki;
		$values['suki2'] = $suki2;
		
		$values['nv_sales'] = $nv_sales;
		$values['fsales_nv'] = $fsales_nv;
		$values['sales_vat'] = $sales_vat;
		$values['fsales_vat'] = $fsales_vat;
		$values['output_vat'] = $output_vat;
		$values['foutput_vat'] = $foutput_vat;
		$values['other_income'] = $other_income;
		$values['fother_income'] = $fother_income;
		
		
		
		$new_amt = ($fsales_zero_rated + $fsales_nv + $fsales_vat + $foutput_vat + $fother_income + $suki2);
		// // for checking -------------------------------------------------------------------
			// display_notification(' --------------------------------------------------------------------------------- ');
			// display_notification(date('F Y', strtotime($current_month_end)));
			
			// display_notification('total_debit  >> '.  $total_debit);
			// display_notification('suki  >> '.  $suki .' ~ '. $suki2 . ' = '. ($suki-$suki2));
			// display_notification('sales zero rated >> '.  $zr_sales .' ~ '. $fsales_zero_rated . ' = '. ($zr_sales-$fsales_zero_rated));
			// display_notification('sales non vat >> '.  $nv_sales .' ~ '. $fsales_nv . ' = '. ($nv_sales-$fsales_nv));
			// display_notification('sales vat >> '.  $sales_vat .' ~ '. $fsales_vat . ' = '. ($sales_vat-$fsales_vat));
			// display_notification('output vat >> '.  $output_vat .' ~ '. $foutput_vat . ' = '. ($output_vat-$foutput_vat));
			// display_notification('other_income >> '.  $other_income .' ~ '. $fother_income . ' = '. ($other_income-$fother_income));
			// display_notification('TOTAL  >> '.  $old_amt .' ~ '. $new_amt . ' = '. ($old_amt-$new_amt));
			// display_notification('E SALES GROSS >> '.$f_gross);
		// // end of for checking -------------------------------------------------------------------
		// display_error($current_month_end .' <<<');
		
		// void_month_adj($current_month_end);
		
		
		
		if (round($old_amt - $new_amt,2) != 0)
		{
			display_notification($current_month_end . ' DEBIT is not equal to CREDIT : '. round($old_amt - $new_amt,2));
		}
		else if (round(abs($suki-$suki2) + abs($zr_sales-$fsales_zero_rated) + abs($nv_sales-$fsales_nv) + abs($sales_vat-$fsales_vat) 
			+ abs($output_vat-$foutput_vat) + abs($other_income-$fother_income),2) != 0)
		{	
			$trans_id = write_monthly_sales_jv($current_month_end, $values);
			display_notification('OK >>>  '.$current_month_end . ' trans_id : '. $trans_id);
		}
		else
			display_notification('OK >>>  '.$current_month_end . ' NO CORRECTION.');
			
		// die;
	}
}

start_form();

if (!isset($_POST['from']))
	$_POST['from'] = '01/01/2016';
if (!isset($_POST['to']))
	$_POST['to'] = '01/01/2016';

start_table($table_style2);
date_row('DATE from:', 'from');
date_row('DATE to:', 'to');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
