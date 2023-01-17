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

	
page('Transfer GL Date of DM/CM/Purchase Discount to Payment Date', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function change_gl_date($type,$trans_no, $date_)
{
	$sql = "UPDATE 0_gl_trans SET tran_date = '".$date_."' 
			WHERE type = $type AND type_no = $trans_no
			AND account != '5450'
			AND account != '5400'
			AND account != '1410010'
			AND account != '2000'
			AND account != '2000010'
			";
	db_query($sql);
}

function change_gl_date_nt($type,$trans_no, $date_)
{
	$sql = "UPDATE 0_gl_trans SET tran_date = '".$date_."' 
			WHERE type = $type AND type_no = $trans_no";
	db_query($sql);
}

if (isset($_POST['fix_now']))
{
	$sql = "SELECT a.tran_date,b.* FROM `0_supp_trans` a, 0_cv_details b
			WHERE type=22
			AND tran_date >= '".date2sql($_POST['from'])."'
			AND tran_date <= '".date2sql($_POST['to'])."'
			AND a.cv_id != 0
			AND a.cv_id = b.cv_id
			AND b.voided = 0
			AND b.trans_type != 22
			AND b.trans_type != 20";
	$res = db_query($sql);
	
	$cc = 0;
	while($row = db_fetch($res))
	{
		$cc ++;
		change_gl_date($row['trans_type'],$row['trans_no'], $row['tran_date']);
	}
	
	display_notification("Fixed $cc CVs");
	
	// FOR NT APV fix
	$sql = "SELECT DISTINCT a.reference, a.trans_no, a.tran_date, a.del_date, b.tran_date as gl_date
		FROM 0_supp_trans a , 0_gl_trans b
		WHERE a.type = 20 
		AND b.type = 20
		AND b.tran_date >= '".date2sql($_POST['from'])."'
		AND b.tran_date <= '".date2sql($_POST['to'])."'
		AND ov_amount > 0
		AND cv_id != 0
		AND a.trans_no = b.type_no
		AND a.reference LIKE 'NT%'
		AND del_date != b.tran_date";
	$res = db_query($sql);
	
	$cc = 0;
	while($row = db_fetch($res))
	{
		$cc ++;
		change_gl_date_nt(20,$row['trans_no'], $row['del_date']);
	}
	
	display_notification("Fixed $cc APVs");
}

start_form();
$_POST['from'] = '01/01/2016';

start_table($table_style2);
date_row('Payment DATE from:', 'from');
date_row('Payment DATE to:', 'to');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>