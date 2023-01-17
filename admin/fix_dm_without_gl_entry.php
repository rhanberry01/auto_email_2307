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

	
page('Fix DM without GL Entry', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

if (isset($_POST['fix_now']))
{
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$memo_='au';
	
	
	//FIX RS
	$sql = "SELECT * FROM 0_supp_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."'
	AND ov_amount!=0
	AND trans_no 
	NOT IN (SELECT DISTINCT type_no FROM 0_gl_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."')
	AND (supp_reference LIKE '%RS#%' OR supp_reference  LIKE '%BO%')";
	//display_error($sql);
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
			//2000	Accounts Payable
			//5500	Purchase Returns and Allowances
			add_gl_trans($row['type'], $row['trans_no'], sql2date($row['tran_date']), '2000', 0, 0, $memo_, abs($row['ov_amount']),'',3,$row['supplier_id']);
			add_gl_trans($row['type'], $row['trans_no'], sql2date($row['tran_date']), '5500', 0, 0, $memo_, -abs($row['ov_amount']),'',3,$row['supplier_id']);
	}
	
	
	//FIX SAF NO: BUNDLING
	$sql2 = "SELECT * FROM 0_supp_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."'
	AND ov_amount!=0
	AND trans_no 
	NOT IN (SELECT DISTINCT type_no FROM 0_gl_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."')
	AND supp_reference LIKE '%SAF NO:%'";
	//display_error($sql2);
	$res2 = db_query($sql2);
	
	while($row2 = db_fetch($res2))
	{
			//2000	Accounts Payable
			//2478	Promo Fund Liabilities - Trade Promo
			add_gl_trans($row2['type'], $row2['trans_no'], sql2date($row2['tran_date']), '2000', 0, 0, $memo_, abs($row2['ov_amount']),'',3,$row2['supplier_id']);
			add_gl_trans($row2['type'], $row2['trans_no'], sql2date($row2['tran_date']), '2478', 0, 0, $memo_, -abs($row2['ov_amount']),'',3,$row2['supplier_id']);
	}
	
	
	//FIX SAF DISPLAY ALLOWANCE
	$sql3 = "SELECT * FROM 0_supp_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."'
	AND ov_amount!=0
	AND trans_no 
	NOT IN (SELECT DISTINCT type_no FROM 0_gl_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."')
	AND supp_reference LIKE '%SAF%'";
	//display_error($sql3);
	$res3 = db_query($sql3);
	
	while($row3 = db_fetch($res3))
	{
			//2000	Accounts Payable
			//2471	Promo Fund Liabilities - Display Allowance
			add_gl_trans($row3['type'], $row3['trans_no'], sql2date($row3['tran_date']), '2000', 0, 0, $memo_, abs($row3['ov_amount']),'',3,$row3['supplier_id']);
			add_gl_trans($row3['type'], $row3['trans_no'], sql2date($row3['tran_date']), '2471', 0, 0, $memo_, -abs($row3['ov_amount']),'',3,$row3['supplier_id']);
	}
	
	
	//FIX CHECK FEE
	$sql4 = "SELECT * FROM 0_supp_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."'
	AND ov_amount!=0
	AND trans_no 
	NOT IN (SELECT DISTINCT type_no FROM 0_gl_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."')
	AND supp_reference LIKE '%Check Fee%'";
	//display_error($sql4);
	$res4 = db_query($sql4);
	
	while($row4 = db_fetch($res4))
	{
			//2000	Accounts Payable
			//6160	Office Supplies
			add_gl_trans($row4['type'], $row4['trans_no'], sql2date($row4['tran_date']), '2000', 0, 0, $memo_, abs($row4['ov_amount']),'',3,$row4['supplier_id']);
			add_gl_trans($row4['type'], $row4['trans_no'], sql2date($row4['tran_date']), '6160', 0, 0, $memo_, -abs($row4['ov_amount']),'',3,$row4['supplier_id']);
	}
	
	
	//FIX PO/ADVANCES TO SUPPLIER
	$sql5 = "SELECT * FROM 0_supp_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."'
	AND ov_amount!=0
	AND trans_no 
	NOT IN (SELECT DISTINCT type_no FROM 0_gl_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."')
	AND supp_reference LIKE '%PO%'";
	//display_error($sql5);
	$res5 = db_query($sql5);
	
	while($row5 = db_fetch($res5))
	{
			//2000	Accounts Payable
			//1440	Advances to Supplier
			add_gl_trans($row5['type'], $row5['trans_no'], sql2date($row5['tran_date']), '2000', 0, 0, $memo_, abs($row5['ov_amount']),'',3,$row5['supplier_id']);
			add_gl_trans($row5['type'], $row5['trans_no'], sql2date($row5['tran_date']), '1440', 0, 0, $memo_, -abs($row5['ov_amount']),'',3,$row5['supplier_id']);
	}
	

	$sql6 = "SELECT * FROM 0_supp_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."'
	AND ov_amount!=0
	AND trans_no 
	NOT IN (SELECT DISTINCT type_no FROM 0_gl_trans 
	WHERE type=53 AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."')
	AND supp_reference NOT LIKE '%RS#%'
	AND supp_reference NOT LIKE '%Check Fee%'
	AND supp_reference NOT LIKE '%SAF%'
	AND supp_reference NOT LIKE '%BO%'
	AND supp_reference NOT LIKE '%PO%'";
	//display_error($sql6);
	$res6 = db_query($sql6);
	
	while($row6 = db_fetch($res6))
	{
			//2000	Accounts Payable
			//2470	Promo Fund Liabilities
			add_gl_trans($row6['type'], $row6['trans_no'], sql2date($row6['tran_date']), '2000', 0, 0, $memo_, abs($row6['ov_amount']),'',3,$row6['supplier_id']);
			add_gl_trans($row6['type'], $row6['trans_no'], sql2date($row6['tran_date']), '2470', 0, 0, $memo_, -abs($row6['ov_amount']),'',3,$row6['supplier_id']);
	}
	
	
	// $sql7 = "SELECT agl.type,agl.type_no FROM 0_supp_trans ast
	// LEFT JOIN 0_gl_trans as agl
	// ON ast.trans_no=agl.type_no
	// WHERE 
	// ast.type=53 
	// AND ast.tran_date>='2018-01-01' and ast.tran_date<='2018-12-31'
	// AND ast.ov_amount!=0
	// AND ast.supp_reference NOT LIKE '%RS#%'
	// AND ast.supp_reference NOT LIKE '%Check Fee%'
	// AND ast.supp_reference NOT LIKE '%SAF%'
	// AND ast.supp_reference NOT LIKE '%BO%'
	// AND ast.supp_reference NOT LIKE '%PO%'
	// AND agl.type=53
	// AND agl.amount<0
	// AND agl.memo_='au'
	// AND agl.account='1440'";
	// $res7 = db_query($sql7);
	
	// while($row7 = db_fetch($res7))
	// {
		// $type_nof=$row7['type_no'];
		
		// $sqlf = "UPDATE 0_gl_trans
				// SET account = '2470'
				// WHERE type=53
				// AND amount<0
				// AND account='1440'
				// AND type_no='$type_nof'";
				// //display_error($sqlf);
				// db_query($sqlf);
	// }
	
	display_notification('SUCCESS!!');
}

start_form();

start_table();

if (!isset($_POST['from']))
	$_POST['from'] = '01/01/'.date('Y');

date_row('Date From:', 'from');
date_row('Date To:', 'to');
end_table(1);

submit_center('fix_now', 'Create GL entry');
end_form();

end_page();
?>
