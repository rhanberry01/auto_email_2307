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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Fix Sales GL Breakdown', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function get_nv_sales($date_)
{
	$sql = "SELECT SUM(Extended) FROM [dbo].[FinishedSales] 
				WHERE LogDate = '$date_'
				AND Voided = 0
				AND pVatable = 0";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
	
}

function get_zr_sales($date_)
{
	$sql = "SELECT SUM(Extended) FROM [dbo].[FinishedSales] 
				WHERE LogDate = '$date_'
				AND Voided = 0
				AND pVatable = 2";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
	
}

function delete_wrong_sales_gl($type_no)
{
	$sql = "DELETE FROM 0_gl_trans
				WHERE type = 60
				AND type_no = $type_no
				AND account='4000'
				AND amount != 0";
	db_query($sql, 'failed to delete');
	
}

function check_sales($type, $type_no)
{
	$sql = "SELECT * FROM 0_gl_trans
				WHERE type = $type
				AND type_no = $type_no
				AND account = '2310'";
	$res = db_query($sql);
	return db_num_rows($res) == 0;
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
	
	
	$sql = "SELECT a.type,a.type_no,a.tran_date,a.account,a.amount, (SELECT x.amount FROM 0_gl_trans x 
						WHERE x.type = 60
						AND a.type_no = x.type_no
						AND account = '4900') as suki 
				FROM `0_gl_trans` a
				WHERE type = 60
				AND account='4000'
				AND tran_date >= '".date2sql($_POST['from'])."'
				AND tran_date <= '".date2sql($_POST['to'])."'
				AND amount != 0
				ORDER BY tran_date
				;";
	// display_error($sql);
	$res = db_query($sql);
	
	$count = 0;
	while($row = db_fetch($res))
	{
		if (!check_sales($row['type'], $row['type_no']))
			continue;
		// display_error('asdad');
		$count ++;
		$gross = -$row['amount'];
		$date_ = $row['tran_date'];
		$suki = $row['suki'];
		
		$net_sales = $gross - $suki;
		$nv_sales = round(get_nv_sales($date_),2);
		$zr_sales = round(get_zr_sales($date_),2);
		
		$gross_vatable = $net_sales - $nv_sales- $zr_sales;
		
		$sales_vat = round($gross_vatable/1.12,2);
		$output_vat = $gross_vatable - $sales_vat;
		
		delete_wrong_sales_gl($row['type_no']);
		add_gl_trans($row['type'], $row['type_no'], sql2date($date_), 4000040, 0, 0, $memo_= '',-$suki);
		add_gl_trans($row['type'], $row['type_no'], sql2date($date_), 4000020, 0, 0, $memo_= '',-$nv_sales);
		
		if ($zr_sales != 0)
			add_gl_trans($row['type'], $row['type_no'], sql2date($date_), 4000050, 0, 0, $memo_= '',-$zr_sales);
		
		add_gl_trans($row['type'], $row['type_no'], sql2date($date_), 4000, 0, 0, $memo_= '',-$sales_vat);
		add_gl_trans($row['type'], $row['type_no'], sql2date($date_), 2310, 0, 0, $memo_= '',-$output_vat);
		
	}
	
	// $sql = "DELETE FROM 0_gl_trans
				// WHERE type = 60
				// AND account='4000'
				// AND tran_date >= '".date2sql($_POST['from'])."'
				// AND tran_date <= '".date2sql($_POST['to'])."'
				// AND amount != 0";
	// db_query($sql, 'failed to delete');
	
	display_notification('ok - '.$count);
}

start_form();
$_POST['from'] = '01/01/2016';
$_POST['to'] = '01/01/2016';

start_table($table_style2);
date_row('DATE from:', 'from');
date_row('DATE to:', 'to');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
