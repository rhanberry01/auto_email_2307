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
$page_security = 'SA_JOURNALENTRY';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/db/rs_db.inc");

$js = "";
#####################

if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

####################

page(_($help_context = "View Returns and Disposals"), false, false, "", $js);
global $db_connections, $table_style2, $Ajax;

set_time_limit(0);
function get_result()
{
	$sql = "SELECT a.*, SUM(b.qty*b.price) as Total
			FROM ".TB_PREF."rms_header a, ".TB_PREF."rms_items b";

	if ($_POST['rs_id'] == '')
	{
		$sql .= " WHERE a.rs_date >= '".date2sql($_POST['rs_date_from'])."'
				  AND a.rs_date <= '".date2sql($_POST['rs_date_to'])."'";
				  
		if ($_POST['supplier_code'] != '')
			$sql .= " AND a.supplier_code = ".db_escape($_POST['supplier_code']);
		
		$sql .= " AND a.processed = 1";
	}
	else		  
		$sql .= " WHERE (a.rs_id = ".$_POST['rs_id']." 
				  OR a.movement_no = ".$_POST['rs_id'].")";
		
	if ($_POST['rs_type'] == 0) // returns
		$sql .= " AND a.movement_type = 'R2SSA'";
	else if ($_POST['rs_type'] == 1) // disposals
		$sql .= " AND a.movement_type = 'FDFB'";
		
	if ($_POST['rs_status'] == 0) //pending
		$sql .= " AND a.trans_no = 0";
	else if ($_POST['rs_status'] == 1) //processed
		$sql .= " AND a.trans_no != 0";

	$sql .= " AND a.rs_id = b.rs_id";
	$sql .= " GROUP BY b.rs_id
			ORDER BY movement_type,movement_no, rs_id";

	$res = db_query_rs($sql);
	// display_error($sql);
	return $res;
}

// REPRINT ------------------------------------------------------
// $print_supp_id = find_submit('print_supp');
// if($print_supp_id != -1)
	// print_rs($print_supp_id,0);
// $print_ware_id = find_submit('print_ware');
// if($print_ware_id != -1)
	// print_rs($print_ware_id,1);
// $print_acct_id = find_submit('print_acct');
// if($print_acct_id != -1)
	// print_rs($print_acct_id,2);

// RS PROCESED ---------------------------------------------------
if (isset($_GET['processed_returns']) AND $_GET['processed_returns'] != '')
{
	$type = 53;
	$trans_no = $_GET['processed_returns'];
	
	display_notification("Returns Processed!");
	
	display_note(get_gl_view_str($type, $trans_no, _("&View the GL Entries of Debit Memo")));

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Back to Returns and Disposals"));
		
	display_footer_exit();
}
if (isset($_GET['processed_disposals']) AND $_GET['processed_disposals'] != '')
{
	$type = 0;
	$trans_no = $_GET['processed_disposals'];
	
	display_notification("Disposals Processed!");

	display_note(get_gl_view_str($type, $trans_no, _("&View the GL Journal Entries")));

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Back to Returns and Disposals"));
		
	display_footer_exit();
}
// ===============================================================

// PROCESS --------------------------------------------------------
if (isset($_POST['process_checked_as_dm']))
{
	$prefix = 'dm_me';
	$rs_ids = array();
	foreach($_POST as $postkey=>$postval)
    {
		if (strpos($postkey, $prefix) === 0)
		{
			$id = substr($postkey, strlen($prefix));
			$rs_ids[] = $id;
		}
	}
	if (count($rs_ids) > 0)
	{
		$dm_trans_no = create_debit_memo_for_rs($rs_ids);
		meta_forward($_SERVER['PHP_SELF'], "processed_returns=$dm_trans_no");
	}
	else
		display_error('Nothing to process!');
}

if (isset($_POST['process_disposals']))
{
	$res = get_result();
	$rs_ids = array();
	$last_type = '';
	$last_no = '';
	
	while($row = db_fetch($res))
	{
		if ($last_type == $row['movement_type'] AND $last_no == $row['movement_no'])
			continue;
		$last_type = $row['movement_type'];
		$last_no = $row['movement_no'];
		$rs_ids[] = $row['rs_id'];
	}
	// display_error(count($rs_ids));
	if (count($rs_ids) > 0)
	{
		$journal_trans_no = create_journal_for_rs($rs_ids,$_POST['j_remarks']);
		meta_forward($_SERVER['PHP_SELF'], "processed_disposals=$journal_trans_no");
	}
	else
		display_error('Nothing to process!');
}
//==============================================================================================

if (isset($_POST['search']) OR isset($_POST['rs_id']))
{
	global $Ajax;
	$Ajax->activate('_body');
}

start_form();
div_start('header');

if (!isset($_POST['rs_date_from']))
	$_POST['rs_date_from'] = begin_month(Today());
if (!isset($_POST['status']))
	$_POST['status'] = 1;
	
start_table();
start_row();
ref_cells('Slip #: ','rs_id',null,null,null,false);
supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
date_cells('Date From: ', 'rs_date_from');
date_cells('Date To: ', 'rs_date_to');
end_row();
end_table();

start_table();
start_row();
yesno_list_cells('Type:', 'rs_type', null, 'Disposals', 'Returns');
yesno_list_cells('Status:', 'rs_status', null, 'Processed', 'Pending');
submit_cells('search', 'Search', "", false, false);
end_table(2);

div_end();

div_start('rs_list');

if (!isset($_POST['search']) AND $_POST['rs_id'] == '')
	display_footer_exit();

$actions = array(0 => 'Pending',
					  1 => 'Return',
					  2 => 'Dispose'
					);
$actions_pending = array(0 => 'Pending',
					  1 => 'to be Returned',
					  2 => 'For Disposal'
					);
$actions_processed = array(0 => '',
					  1 => 'Returned',
					  2 => 'Disposed'
					);

$res = get_result();

display_heading('Returns and Disposals Slips');
br();
submit_center('export','Export to Excel	');
br();
start_table($table_style2);
$th = array('#', 'Date', 'Supplier', 'Disposed/Returned by', 'Processed by', 'Remarks','Transaction','Total Amount');

table_header($th);
$k = 0;
$count = db_num_rows($res);
$last_type = '';
$last_no = '';
while($row = db_fetch($res))
{
	if ($last_type == $row['movement_type'] AND $last_no == $row['movement_no'])
		continue;
		
	$last_type = $row['movement_type'];
	$last_no = $row['movement_no'];
	
	$text = '';
	
	alt_table_row_color($k);
	// hyperlink_params_td($path_to_root . "/purchasing/view/view_rs", 'RMS # '.$row['rs_id'], 'rs_id='.$row['rs_id']);
	// label_cell(viewer_link('RMS # '.$row['rs_id'], "purchasing/view/view_rs.php?rs_id=".$row['rs_id']));
	// label_cell(get_rs_view_str($row['rs_id'],$row['movement_type'].' # '.$row['movement_no']));
	label_cell(get_movement_view_str($row['rs_id'],$row['movement_type'].' # '.$row['movement_no']));
	
	label_cell(sql2date($row['bo_processed_date']));
	label_cell(get_ms_supp_name($row['supplier_code']));
	
	
	if ($row['processed_by'] > 0)
		label_cell(get_username_by_id_rs($row['processed_by']),'align=center');
	else
		label_cell('');
		
		
	if ($row['trans_no'] == 0) // to be processed by accounting
	{
		// if ($row['rs_action'] == 1)
		if ($row['movement_type'] == 'R2SSA')
		{
			$text = 'Create Debit Memo';
			
			label_cell('');
			label_cell($row['comment']);
			
			if ($_POST['supplier_code'] == '' AND $count != 1)
				// submit_cells('make_dm_journal'.$row['rs_id'], $text, 'align=center', $text, true);
				label_cell('');
			else
			{
				check_cells('', 'dm_me'.$row['rs_id'],null,false,false,'align=center');
			}
			
		}
		// else if ($row['rs_action'] == 2)
		else if ($row['movement_type'] == 'FDFB')
		{
			// $text = 'Create Journal Entry';
			// submit_cells('make_dm_journal'.$row['rs_id'], $text, 'align=center'.
				// ($_POST['supplier_code'] == '' ? ' colspan=2':''), $text, true);
			label_cell('');
			label_cell($row['comment']);
			label_cell('');
		}
	}
	
	$total += $row['Total'];
	label_cell(number_format2($row['Total'],3),'align=right');
	end_row();
}

alt_table_row_color($k);
label_cell('<b>TOTAL: </b>','colspan=7 align=right');
label_cell('<b>'.number_format2($total,3).'</b>','align=right');
end_row();

end_table(2);

if ($_POST['rs_type'] == 0 AND $_POST['rs_status'] == 0) 
{
	if ($_POST['supplier_code'] != '' OR $count == 1)
		submit_center('process_checked_as_dm', '<b>Process checked Returns as 1 Debit Memo</b>');
	else
		echo '<center><font color=red>*choose a supplier to process Returns</font></center>';
}
else if ($_POST['rs_status'] == 0 AND $count > 0)
{
	start_table();
	textarea_row('Remarks : ', 'j_remarks', '', 40, 4);
	end_table(2);
	submit_center('process_disposals', '<b>Create Journal Entry for all these Disposals</b>');
}
div_end();

end_form();
end_page();
?>
