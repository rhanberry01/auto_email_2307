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

page(_($help_context = "Payments Inquiry"), false, false, "", $js);

//-----------------------------------------------------------------------------------
// Ajax updates
//
if (get_post('Search'))
{
	$Ajax->activate('journal_tbl');
}
//--------------------------------------------------------------------------------------
if (!isset($_POST['filterType']))
	$_POST['filterType'] = -1;

if (isset($_POST['Search']))
{
	global $Ajax;
	
	$Ajax->activate('tbl_');
}

start_form();

start_table("class='tablestyle_noborder'");
start_row();

$items = array();
$items['0'] = 'ALL';
$items['1'] = 'Online Payment';
$items['4'] = 'AUB & Metrobank Check';
$items['2'] = 'AUB Check';
$items['3'] = 'Metrobank Check';

label_cells('Payment Type:',array_selector('p_type', null, $items, array() ));

if (!isset($_POST['FromDate']))
	$_POST['FromDate'] = begin_month(Today());

date_cells(_("From:"), 'FromDate', '', null, 0, 0, 0);
date_cells(_("To:"), 'ToDate');
submit_cells('Search', _("Search"), '', '', 'default');
end_table();

function view_link($id) 
{
	return get_csv_view_str($id);
}

function view_check_batch_link($id)
{
	return get_check_batch_view_str($id);
}

function csv_dl_link($r_id) 
{
	global $path_to_root;
	$id = default_focus();
	// $target = $path_to_root.'/csv/'.$row['csv_file'];

	$target = $path_to_root.'/modules/checkprint/csv_download.php?id='.$r_id;
	return "<a href='$target' id='$id' onclick=\"javascript:openWindow(this.href,this.target); 
		return false;\"><b>Download CSV File</b></a>";
}

function check_batch_dl_link($row) 
{
	global $path_to_root;
	$id = default_focus();
	// $target = $path_to_root.'/csv/'.$row['csv_file'];

	if ($row['type'] == 2)
	{
		$export_page = "aub_check_export.php?batch_id=".$row['real_id'];
		$label = 'Excel';
	}
	else if ($row['type'] == 3)
	{
		$export_page = "mb_check_export.php?batch_id=".$row['real_id'];
		$label = 'Text';
	}
	
	$target = $path_to_root.'/modules/checkprint/'.$export_page;
	return "<a href='$target' id='$id' onclick=\"javascript:openWindow(this.href,this.target); 
		return false;\"><b>Download $label File</b></a>";
}

function csv_amount($id)
{
	return get_csv_amount($id);
}

function check_batch_email($batch_id)
{
	$sql = "SELECT b.email_sent FROM 0_csv_details a, 0_cv_header b
				WHERE csv_id=$batch_id
				AND a.cv_id = b.id
				AND email_sent = 0";
	// display_error($sql);
	$res = db_query($sql);
	return db_num_rows($res) > 0;
}

//-------------------------------------------------------------------------------------
$csv_batch_id = find_submit('email');
if ($csv_batch_id != -1)
{

	$res = get_csv_details($csv_batch_id);
	
	while($row = db_fetch($res))
	{
		if ($row['email_sent'] == 0)
		send_that_cv($row['id'],$row['bank_trans_id']);
	}
	// $r_ = get_cv_header($email_id);
	// if (!$r_['email_sent'])
		// send_that_cv($email_id,$r_['bank_trans_id']);
	
	unset($_POST['email'.$csv_batch_id]);
}

$csv_batch_resend_id = find_submit('resend_email');
if ($csv_batch_resend_id != -1)
{

	$res = get_csv_details($csv_batch_resend_id);
	
	while($row = db_fetch($res))
	{
		// if ($row['email_sent'] == 0)
		send_that_cv($row['id'],$row['bank_trans_id']);
	}
	// $r_ = get_cv_header($email_id);
	// if (!$r_['email_sent'])
		// send_that_cv($email_id,$r_['bank_trans_id']);
	
	unset($_POST['resend_email'.$csv_batch_resend_id]);
}

//-------------------------------------------------------------------------------------

switch($_POST['p_type'])
{
	case 1: // csv only
		$sql = "SELECT 'Online Payment', date(date), id, '1' as type, id as real_id, bank_id
			FROM ".TB_PREF."csv
			WHERE date(date) >= '" . date2sql($_POST['FromDate']) . "'
			AND date(date) <= '" . date2sql($_POST['ToDate']) . "'
			ORDER BY id";
		break;
	
	case 2: // AUB
		$sql = "SELECT IF(check_writer = 1, 'AUB Check Writer', 'Metrobank Check Writer'), date(stamp), batch_no, '2' as type, id as real_id
					, bank_id
			FROM ".TB_PREF."check_issue_batch
			WHERE date(stamp) >= '" . date2sql($_POST['FromDate']) . "'
			AND date(stamp) <= '" . date2sql($_POST['ToDate']) . "'
			AND check_writer = 1
			ORDER BY id";
		break;
		
	case 3: // Metrobank
		$sql = "SELECT IF(check_writer = 1, 'AUB Check Writer', 'Metrobank Check Writer'), date(stamp), batch_no, '3' as type, id as real_id
					, bank_id
			FROM ".TB_PREF."check_issue_batch
			WHERE date(stamp) >= '" . date2sql($_POST['FromDate']) . "'
			AND date(stamp) <= '" . date2sql($_POST['ToDate']) . "'
			AND check_writer = 2
			ORDER BY id";
		break;
		
	case 4: // AUB & Metrobank
		$sql = "SELECT IF(check_writer = 1, 'AUB Check Writer', 'Metrobank Check Writer'), date(stamp), batch_no, (check_writer+1) as type, id as real_id
					, bank_id
			FROM ".TB_PREF."check_issue_batch
			WHERE date(stamp) >= '" . date2sql($_POST['FromDate']) . "'
			AND date(stamp) <= '" . date2sql($_POST['ToDate']) . "'
			ORDER BY id";
		break;
		
	case 0:
		$sql = "(SELECT 'Online Payment', date(date), id, '1' as type, id as real_id, bank_id
			FROM ".TB_PREF."csv
			WHERE date(date) >= '" . date2sql($_POST['FromDate']) . "'
			AND date(date) <= '" . date2sql($_POST['ToDate']) . "')
			
			UNION
			
			(SELECT IF(check_writer = 1, 'AUB Check Writer', 'Metrobank Check Writer'), date(stamp), batch_no, (check_writer+1) as type, 
				id as real_id, bank_id
			FROM ".TB_PREF."check_issue_batch
			WHERE date(stamp) >= '" . date2sql($_POST['FromDate']) . "'
			AND date(stamp) <= '" . date2sql($_POST['ToDate']) . "')
			
			ORDER BY 2,3, type;
			";
		break;
}

$th = array('Bank' ,'Batch No.', 'Date Created', 'Amount', 'Details', 'Payment Type','Download', 'Email');

echo '<br>';
div_start('tbl_');
start_table("$table_style2 width=80%");
table_header($th);

$res = db_query($sql);

$k = 0;
while ($row = db_fetch($res))
{
	alt_table_row_color($k);
	
	
	if ($row['type'] == 1 ) //csv
	{
		$id = $row['real_id'];
		label_cell(get_bank_name($row['bank_id']));
		label_cell($row[2],'align=center');
		label_cell(sql2date($row[1]),'align=center');
		amount_cell(csv_amount($id));
		label_cell(view_link($id),'align=center');
		label_cell($row[0]);
		label_cell('&nbsp;&nbsp;&nbsp;&nbsp;'.csv_dl_link($id),'align=left');
		
		if (check_batch_email($id))
			label_cell(submit('email'.$id, 'Send e-mail', false, false, false,ICON_EMAIL), 'align=center');
		else
			label_cell('E-mail sent  ' . submit('resend_email'.$id, 'Resend e-mail', false, false, false,ICON_EMAIL),'align=center');
	}
	else
	{
		$id = $row['real_id'];
		if (get_check_batch_amount($id) == 0)
			continue;
		label_cell(get_bank_name($row['bank_id']));
		label_cell($row[2],'align=center');
		label_cell(sql2date($row[1]),'align=center');
		amount_cell(get_check_batch_amount($id));
		label_cell(view_check_batch_link($id),'align=center');
		label_cell($row[0]);
		label_cell('&nbsp;&nbsp;&nbsp;&nbsp;'.check_batch_dl_link($row),'align=left');
		label_cell('');
	}
	end_row();
}
end_table();
div_end();

end_form();
end_page();

?>
