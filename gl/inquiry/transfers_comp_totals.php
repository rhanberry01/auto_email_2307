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
	
page(_($help_context = "Transfers TOTAL Comparison"), false, false, "", $js);

//----------------------------------------------------------------------------------------------------

function get_transfer_branches($branch)
{
	$sql = "SELECT code, name,aria_db, gl_stock_to, gl_stock_from 
				FROM transfers.0_branches";
	
	if ($branch != '')
		$sql .= ' WHERE code = '. db_escape($branch);
	$sql .= " ORDER BY name";
	$res = db_query($sql);
	$branches = array();
	
	while($row = db_fetch($res))
	{
		$branches[$row['code']] = array(
											'name' => $row['name'],
											'aria_db' => $row['aria_db'],
											'gl_stock_to' => $row['gl_stock_to'],
											'gl_stock_from' => $row['gl_stock_from']
											);
	}
	
	return $branches;
}

function get_transfers($from_date, $to_date, $from_loc, $to_loc)
{
	$sql = "SELECT
					GROUP_CONCAT(a.id) as t_ids,
					a.br_code_out,
					c.`name` AS branch_out,
					c.aria_db AS out_db,
					a.br_code_in,
					d.`name` AS branch_in,
					d.aria_db AS in_db
				FROM
					transfers.0_transfer_header a
				JOIN transfers.0_branches c ON a.br_code_out = c.`code`
				JOIN transfers.0_branches d ON a.br_code_in = d.`code`
				WHERE a.transfer_out_date >= '".date2sql($from_date)."'
				AND a.transfer_out_date <= '".date2sql($to_date)."'";
	if ($from_loc != '')
		$sql .= " AND a.br_code_out = ". db_escape($from_loc);
	if ($to_loc != '')
		$sql .= " AND a.br_code_in = ". db_escape($to_loc);
	
	$sql .= " GROUP BY c.`name`, d.`name`
				ORDER BY c.`name`, d.`name`";
	// display_error($sql);
	$res = db_query($sql);
	
	return $res;
}

function get_branch_other_branch_in($aria_db, $account, $date_from, $date_to)
{
	$sql = "SELECT SUM(amount) FROM $aria_db.0_gl_trans
				WHERE account = '$account' 
				AND tran_date >= '".date2sql($date_from)."'
				AND tran_date <= '".date2sql($date_to)."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return round(-$row[0],2);
}

function get_branch_out_with_in($branch, $date_from, $date_to)
{
	// get transfer out first
	$sql = "SELECT SUM(a.amount),b.name,a.account,b.aria_db, b.code
				FROM
					".$branch['aria_db'].".0_gl_trans a
				JOIN transfers.0_branches b ON a.account = b.gl_stock_to
				WHERE amount > 0
				AND tran_date >= '".date2sql($date_from)."'
				AND tran_date <= '".date2sql($date_to)."'
				GROUP BY account
				ORDER BY b.name
				";
	// display_error($sql);die;
	$res = db_query($sql);
	
	$data = array();
	while($row = db_fetch($res))
	{
		$data[] = array( round($row[0],2), $row[1], $row[2],
					get_branch_other_branch_in($row['aria_db'], $branch['gl_stock_from'], $date_from, $date_to),
					$row['code']
					);
	}
	return $data;
}


start_form();
if (!isset($_POST['year']))
	$_POST['year'] = date('Y',strtotime('-1 year'));

echo "<center>";

// text_cells('Year : ', 'year');
if (!isset($_POST['from_date']))
{
	$_POST['from_date'] = '01/01/2016';
	$_POST['to_date'] = '12/31/2016';
}
date_cells('From :','from_date');
date_cells('To :','to_date');
get_branchcode_list_cells('From Location:','from_loc',null,'ALL Branch');
// get_branchcode_list_cells('To Location:','to_loc',null,'ALL Branch');
yesno_list_cells(' with discrepancy only ? ','discrep_only');
submit_cells('proceed','Search');

br(2);
echo "</center>";

if (!isset($_POST['proceed']))
	display_footer_exit();

display_heading('Date From : '.$_POST['from_date'] .' to ' . $_POST['to_date']);
start_table($table_style2.' width=90%');

$k = 0;
$th = array('Branch OUT', 'TOTAL Amount OUT','===>', 'Branch IN', 'TOTAL Amount IN');

table_header($th);

// $res = get_transfers($_POST['from_date'],$_POST['to_date'],$_POST['from_loc'], $_POST['to_loc']);
$t_branches = get_transfer_branches($_POST['from_loc']);
// var_dump($t_branches);
$count = $out_total = $in_total = $k = 0;
foreach($t_branches as $br_code=>$branch)
{
	$data = get_branch_out_with_in($branch, $_POST['from_date'], $_POST['to_date'],$t_branches);
	
	foreach($data as $details)
	{
		$out_amount = $details[0];
		$in_amount = $details[3];
		
		if ($_POST['discrep_only'] AND $out_amount == $in_amount)
			continue;
			
		if ($out_amount == $in_amount)
			alt_table_row_color($k);
		else
			echo '<tr class=overduebg>';
		
		//branch out, branch in, date from, date to
		$prev_str =  "<a target='_blank' href='$path_to_root/gl/view/gl_trans_compare_transfers.php?branch_out=". $br_code ."&
					branch_in=". $details[4] ."&
					date_from=". $_POST['from_date'] ."&
					date_to=". $_POST['to_date'] ."' 
				onclick=\"javascript:openWindow(this.href,this.target); return false;\"> View GL </a>";
				
		$count ++;
		label_cell($branch['name']);
		amount_cell($out_amount);
		label_cell($prev_str,'align=center');
	
		label_cell($details[1]);
		amount_cell($in_amount);
		
		$out_total += $out_amount;
		$in_total += $in_amount;
		end_row();
	}
}
alt_table_row_color($k);
// label_cell("<b>COUNT : $count</b>",'align=center');
// label_cell('');
label_cell('<b>TOTAL OUT: </b>','align=right');
amount_cell($out_total);
label_cell('');
label_cell('<b>TOTAL IN: </b>','align=right');
amount_cell($in_total);
end_row();

end_table('');

end_form();

end_page();

?>