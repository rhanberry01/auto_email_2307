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

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Transfers Comparison"), false, false, "", $js);

//----------------------------------------------------------------------------------------------------

function get_transfers($year, $from_loc, $to_loc)
{
	$sql = "SELECT a.id, c.`name` as branch_out, c.aria_db as out_db,  a.m_no_out as movement_out_no, DATE(transfer_out_date) as out_date,
					d.`name` as branch_in,d.aria_db as in_db, a.m_no_in as movement_in_no, DATE(transfer_in_date) as in_date
				FROM transfers.0_transfer_header a
				JOIN transfers.0_branches c ON a.br_code_out = c.`code`
				JOIN transfers.0_branches d ON a.br_code_in = d.`code`
				WHERE a.transfer_out_date >= '$year-01-01'
				AND a.transfer_out_date <' " . ($year+1) ."-01-01'";
	if ($from_loc != '')
		$sql .= " AND a.br_code_out = ". db_escape($from_loc);
	if ($to_loc != '')
		$sql .= " AND a.br_code_in = ". db_escape($to_loc);
	
	$sql .= " ORDER BY c.`name`, a.id";
	// display_error($sql);
	$res = db_query($sql);
	
	return $res;
}

function get_transfer_out_amount($t_id, $b_db)
{
	$sql = "SELECT SUM(amount) 
				FROM $b_db.0_gl_trans 
				WHERE type = 70
				AND type_no = $t_id
				AND amount > 0";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_transfer_in_amount($t_id, $b_db)
{
	$sql = "SELECT SUM(amount) 
				FROM $b_db.0_gl_trans 
				WHERE type = 71
				AND type_no = $t_id
				AND amount < 0";
	$res = db_query($sql);
	$row = db_fetch($res);
	return -$row[0];
}

start_form();
if (!isset($_POST['year']))
	$_POST['year'] = date('Y',strtotime('-1 year'));

echo "<center>";

text_cells('Year : ', 'year');
get_branchcode_list_cells('From Location:','from_loc',null,'ALL Branch');
get_branchcode_list_cells('To Location:','to_loc',null,'ALL Branch');
yesno_list_cells(' with discrepancy only ? ','discrep_only');
yesno_list_cells(' with difference in year of Out and In  ? ','diff_date_only');
submit_cells('proceed','Search');

br(2);
echo "</center>";

start_table($table_style2.' width=90%');

$k = 0;
$th = array('Transfer ID', 'Branch OUT','Date OUT', 'Amount OUT', 'Branch IN', 'Date IN', 'Amount IN','Difference');

table_header($th);

$res = get_transfers($_POST['year'],$_POST['from_loc'], $_POST['to_loc']);

$count = $out_total = $in_total = $k = 0;
while($row = db_fetch($res))
{
	$actual = '';
	
	if ($row['out_date'])
		$actual = '&actual=1';
	
	$prev_str =  "<a target='_blank' href='$path_to_root/inventory/view/view_transfer_2.php?transfer_id=". $row['id'] ."$actual' 
				onclick=\"javascript:openWindow(this.href,this.target); return false;\">".$row['id'] ."</a>";
				
	$out_amount = round(get_transfer_out_amount($row['id'], $row['out_db']),2);
	$in_amount = round(get_transfer_in_amount($row['id'], $row['in_db']),2);
	
	if ($_POST['discrep_only'] AND $out_amount == $in_amount)
		continue;
	

	
	if ($_POST['diff_date_only'])
	{
		if (is_date(sql2date($row['out_date'])))
			$out_date_ = explode_date_to_dmy(sql2date($row['out_date']));
		else
			continue;
	
		if (is_date(sql2date($row['in_date'])))
			$in_date_ = explode_date_to_dmy(sql2date($row['in_date']));
		else
			continue;
		
		if ($out_date_[2] == $in_date_[2])
			continue;
	}
		
	if ($out_amount == $in_amount)
		alt_table_row_color($k);
	else
		echo '<tr class=overduebg>';
	$count ++;
	label_cell($prev_str);
	label_cell($row['branch_out']);
	label_cell(sql2date($row['out_date']));
	amount_cell($out_amount);
	label_cell($row['branch_in']);
	label_cell(sql2date($row['in_date']));
	amount_cell($in_amount);
	amount_cell($out_amount-$in_amount);
	
	$out_total += $out_amount;
	$in_total += $in_amount;
	end_row();
}
alt_table_row_color($k);
label_cell("<b>COUNT : $count</b>",'align=center');
label_cell('');
label_cell('<b>TOTAL : </b>','align=right');
amount_cell($out_total);
label_cell('');
label_cell('');
amount_cell($in_total);
label_cell('');

end_row();

end_table('');

end_form();

end_page();

?>