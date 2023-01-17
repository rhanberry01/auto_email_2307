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

	
page('Get Product/s with QTY difference', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function get_inventory_and_cost_backup($prod_id, $date_)
{	
	$sql = "SELECT SellingArea ,Damaged, CostOfSales
				FROM	ProductsBackUp
				WHERE CAST(BackUpDate AS DATE)= '".date2sql($date_)."'
				AND ProductID = $prod_id";
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row;
}

function get_prod_history_total($prod_id, $date_1, $date_2)
{
	$sql = "SELECT SUM (
								CASE
									WHEN SellingAreaIn IS NULL THEN
										0
									ELSE
										SellingAreaIn 
								END  - 
								CASE
									WHEN SellingAreaOut IS NULL THEN
										0
									ELSE
										SellingAreaOut 
								END) as sa,
					SUM (
								CASE
									WHEN DamagedIn IS NULL THEN
										0
									ELSE
										DamagedIn 
								END  - 
								CASE
									WHEN DamagedOut IS NULL THEN
										0
									ELSE
										DamagedOut  
								END) as bo
			FROM ProductHistory WHERE ProductID = $prod_id
			AND CAST(DatePosted AS DATE) >= '".date2sql($date_1)."'
			AND CAST(DatePosted AS DATE) <= '".date2sql($date_2)."'"; 
	// display_error($sql);
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row;
}

if(!isset($_POST['from']))
{
	$_POST['from'] = '01/01/2016';
	$_POST['to'] = '01/01/2016';
}

start_form();
start_table($table_style2);
date_row('DATE:', 'from');
date_row('DATE to:', 'to');
end_table(2);
submit_center('gogogo', 'GO');
br(2);
end_form();

if (isset($_POST['gogogo']))
{
	$from_date = $_POST['from'];
	$to_date = $_POST['to'];
	
	$sql = "SELECT DISTINCT b.ProductID, b.Description FROM ProductHistory a, Products b
				WHERE CAST(DatePosted  AS DATE) >=  '".$_POST['from']."'
				AND CAST(DatePosted  AS DATE) <=  '".$_POST['to']."'
				AND a.ProductID = b.ProductID
				ORDER BY b.Description";
	// display_error($sql);
	$res = ms_db_query($sql);
	
	start_table($table_style2. ' width=90%');
	
	start_row();
		labelheader_cell('&nbsp;');
		labelheader_cell('&nbsp;');
		labelheader_cell('Selling Area','colspan=4');
		labelheader_cell('BO Room','colspan=4');
		labelheader_cell('&nbsp;');
		labelheader_cell('&nbsp;');
	end_row();
	
	$th = array('Product ID', 'Description', 'Beg QTY', 'Trans QTY','End QTY', 'QTY Difference', 
						'Beg QTY', 'Trans QTY','End QTY', 'QTY Difference',
						'Beg Avg Cost', 'End Avg Cost');
	table_header($th);
	
	while($row = mssql_fetch_array($res))
	{
		
		list($sa_beg, $bo_beg, $cost_beg) = get_inventory_and_cost_backup($row['ProductID'], $_POST['from']);
		list($sa_tran,$bo_tran) = get_prod_history_total($row['ProductID'], $_POST['from'],$_POST['to']);
		list($sa_end, $bo_end, $cost_end) = get_inventory_and_cost_backup($row['ProductID'], add_days($_POST['to'], 1));
		
		
		
		if ((($sa_beg + $sa_tran - $sa_end) != 0) OR (($bo_beg + $bo_tran - $bo_end) != 0)
				OR ($cost_beg != $cost_end))
			start_row("class='overduebg'");
		else
		{
			continue;
			// alt_table_row_color($th);
		}
		
		label_cell($row['ProductID']);
		label_cell($row['Description']);
		label_cell($sa_beg);
		label_cell($sa_tran);
		label_cell($sa_end);
		label_cell($sa_beg + $sa_tran - $sa_end);
		label_cell($bo_beg);
		label_cell($bo_tran);
		label_cell($bo_end);
		label_cell($bo_beg + $bo_tran - $bo_end);
		label_cell($cost_beg);
		label_cell($cost_end);

		end_row();
	}
	
	end_table();
}

end_page();
?>
