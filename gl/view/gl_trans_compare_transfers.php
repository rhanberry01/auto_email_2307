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
$page_security = 'SA_GLTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "General Ledger Transaction Details"), true,false, $js);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/purchasing/includes/db/sdma_db.inc");

if (!isset($_GET['branch_out']) || !isset($_GET['branch_in']) || !isset($_GET['date_from']) || !isset($_GET['date_to']) ) 
{ /*Script was not passed the correct parameters */

	echo "<p>" . _("The script must be called with a valid transaction type and transaction number to review the general ledger postings for.") . "</p>";
	exit;
}

function get_branch_details($branch_code)
{
	$sql = "SELECT code, name,aria_db, gl_stock_to, gl_stock_from, config_db_key
				FROM transfers.0_branches
				WHERE code = ". db_escape($branch_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function get_transfer_account_details($b_db, $account, $date_from, $date_to, $only_type='', $exclude_type='')
{
	$sql = "SELECT *
				FROM $b_db.0_gl_trans 
				WHERE account = '$account'
				AND tran_date >= '".date2sql($date_from)."'
				AND tran_date <= '".date2sql($date_to)."'
				AND amount != 0 ";
	if ($only_type != '')
	{
		$sql .= " AND type = $only_type";
		$sql .= " ORDER BY type_no";
	}
	if ($exclude_type != '')
	{
		$sql .= " AND type != $exclude_type";
		$sql .= " ORDER BY tran_date, type_no";
	}
		
	$res = db_query($sql);
	return $res;
}

//==========================================================================================
global $systypes_array;
$br_out = get_branch_details($_GET['branch_out']);
$br_in = get_branch_details($_GET['branch_in']);
$date_from = $_GET['date_from'];
$date_to = $_GET['date_to'];

start_table();
start_row();
//========================================= OUT ========================================
echo '<td valign=top>';
	start_table($table_style2);
	table_section_title($br_out['name'],4);
	$res = get_transfer_account_details($br_out['aria_db'], $br_in['gl_stock_to'], $date_from, $date_to, '',70);

	$th = array('Date', 'Type', '#', 'Amount');
	table_header($th);
	$out_total = $k=0;
	while($row = db_fetch($res))
	{
		// $prev_str =  "<a target='_blank' href='$path_to_root/gl/view/gl_trans_view.php?type_id=".$row['type']."&
				// trans_no=".$row['type_no']."&branch=".$br_out['config_db_key']."'
				// onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['type_no'] ."</a>";

		$prev_str =  "<a target='_blank' href='$path_to_root/gl/view/gl_trans_view.php?type_id=".$row['type']."&
					trans_no=".$row['type_no']."&
					branch=".$br_out['config_db_key']."'
					onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['type_no'] ."</a>";
		alt_table_row_color($k);
		label_cell(sql2date($row['tran_date']));
		label_cell($systypes_array[$row['type']]);
		label_cell($prev_str);
		amount_cell($row['amount']);
		
		$out_total += $row['amount'];
		end_row();
	}
	end_table();
echo '</td>';

//========================================= IN ========================================
echo '<td valign=top>';
	start_table($table_style2);
	table_section_title($br_in['name'],4);
	$th = array('Date', 'Type', '#', 'Amount');
	table_header($th);
	$res = get_transfer_account_details($br_in['aria_db'], $br_out['gl_stock_from'], $date_from, $date_to, '',	71);
	$in_total = $k=0;
	while($row = db_fetch($res))
	{
		// $prev_str =  "<a target='_blank' href='$path_to_root/gl/view/gl_trans_view.php?type_id=".$row['type']."&
				// trans_no=".$row['type_no']."&branch=".$br_in['config_db_key']."'
				// onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['type_no'] ."</a>";
		$prev_str = $row['type_no'];
		
		alt_table_row_color($k);
		label_cell(sql2date($row['tran_date']));
		label_cell($systypes_array[$row['type']]);
		label_cell($prev_str);
		amount_cell(-$row['amount']);
		
		$in_total += -$row['amount'];
		
		end_row();
	}
	end_table();
echo '</td>';
end_row();
start_row();
label_cell('<hr>', 'colspan=2');
end_row();
start_row();
label_cell("<b>TOTAL OUT : ".number_format2($out_total,2)."</b>",'align=center');
label_cell("<b>TOTAL IN : ".number_format2($in_total,2)."</b>",'align=center');
end_row();

//===================================

end_table(2);

start_table();
start_row();
//========================================= OUT ========================================
echo '<td valign=top>';
	start_table($table_style2);
	table_section_title($br_out['name'],4);
	$res = get_transfer_account_details($br_out['aria_db'], $br_in['gl_stock_to'], $date_from, $date_to, 70);

	$th = array('Date', 'Type', '#', 'Amount');
	table_header($th);
	$out_total = $k=0;
	while($row = db_fetch($res))
	{
		// $prev_str =  "<a target='_blank' href='$path_to_root/gl/view/gl_trans_view.php?type_id=".$row['type']."&
				// trans_no=".$row['type_no']."&branch=".$br_out['config_db_key']."'
				// onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['type_no'] ."</a>";

		$prev_str =  "<a target='_blank' href='$path_to_root/gl/view/gl_trans_view.php?type_id=".$row['type']."&
					trans_no=".$row['type_no']."&
					branch=".$br_out['config_db_key']."'
					onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['type_no'] ."</a>";
		alt_table_row_color($k);
		label_cell(sql2date($row['tran_date']));
		label_cell($systypes_array[$row['type']]);
		label_cell($prev_str);
		amount_cell($row['amount']);
		
		$out_total += $row['amount'];
		end_row();
	}
	end_table();
echo '</td>';

//========================================= IN ========================================
echo '<td valign=top>';
	start_table($table_style2);
	table_section_title($br_in['name'],4);
	$th = array('Date', 'Type', '#', 'Amount');
	table_header($th);
	$res = get_transfer_account_details($br_in['aria_db'], $br_out['gl_stock_from'], $date_from, $date_to, 71);
	$in_total = $k=0;
	while($row = db_fetch($res))
	{
		$prev_str =  "<a target='_blank' href='$path_to_root/gl/view/gl_trans_view.php?type_id=".$row['type']."&
				trans_no=".$row['type_no']."&branch=".$br_in['config_db_key']."'
				onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['type_no'] ."</a>";
				
		alt_table_row_color($k);
		label_cell(sql2date($row['tran_date']));
		label_cell($systypes_array[$row['type']]);
		label_cell($prev_str);
		amount_cell(-$row['amount']);
		
		$in_total += -$row['amount'];
		
		end_row();
	}
	end_table();
echo '</td>';
end_row();
start_row();
label_cell('<hr>', 'colspan=2');
end_row();
start_row();
label_cell("<b>TOTAL OUT : ".number_format2($out_total,2)."</b>",'align=center');
label_cell("<b>TOTAL IN : ".number_format2($in_total,2)."</b>",'align=center');
end_row();

//===================================

end_table(2);

end_page(true);

?>
