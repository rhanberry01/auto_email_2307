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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager2.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/reporting/includes/reporting.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Deducted/Undeducted Debit Memo (excluding RS) Inquiry"), false, false, "", $js);

function yes_no($x,$yes='YES',$no='NO')
{
	if ($x)
		return $yes;
	return $no;
}

//======================================================================================

$approve1 = find_submit('approve_sdma1');
if ($approve1 != -1)
{
	global $Ajax;
	$sql = "UPDATE ".TB_PREF."sdma SET approval_1 = ". $_SESSION['wa_current_user']->user ."
			WHERE id = $approve1";
	db_query($sql);
	$_POST['search'] = 1;
	$Ajax->activate('dm_list');
	
	// create_dm_from_sdma($approve1);
}

$approve2 = find_submit('approve_sdma2');
if ($approve2 != -1)
{
	global $Ajax;
	$sql = "UPDATE ".TB_PREF."sdma SET approval_2 = ". $_SESSION['wa_current_user']->user ."
			WHERE id = $approve2";
	db_query($sql);
	$_POST['search'] = 1;
	$Ajax->activate('dm_list');
	
	create_dm_from_sdma($approve2);
}

start_form();
div_start('header_');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

echo '<center>';
ref_cells('DM/Agreement Reference #:', 'dm_po_ref');
br();echo '<b>OR</b>';
br();


		supplier_list_cells('Supplier :', 'supp_id', null, true);
		date_cells('DM Date From :', 'start_date');
		date_cells(' To :', 'end_date');
		get_sdma_type_list_cells('Agreement Type : ','sdma_type');
br(2);
		text_cells('Comment contains (for no agreement only) : ', 'comment_like');
		allyesno_list_cells('Status:','status', null, 'Undeducted', 'Deducted', 'ALL');
		submit_cells('search', 'Search');
echo '</center>';

div_end();

div_start('dm_list');
// if (!isset($_POST['search']))
	// display_footer_exit();
	
$sql = "SELECT a.trans_no, a.reference as dm_ref, b.reference as agreement_ref, a.tran_date, a.supp_reference,
					d.supp_name,
					e.type_name,
					a.ov_amount, b.po_no, b.frequency, 
					b.effective_from, b.effective_to, b.once_only, b.dm_date, b.period, 
					b.comment, b.date_created, c.cv_no,a.cv_id 
			FROM 0_supp_trans a
			JOIN 0_suppliers d ON a.supplier_id = d.supplier_id 
			LEFT OUTER JOIN 0_cv_header c ON (a.cv_id = c.id)
			LEFT OUTER JOIN 0_sdma b ON (a.special_reference = b.id AND a.supp_reference = b.reference)
			LEFT OUTER JOIN 0_sdma_type e ON b.sdma_type = e.id 
			WHERE ov_amount != 0 
			AND a.type = 53 
			AND (a.supp_reference NOT LIKE 'RS%#%')
			";
		
if (trim($_POST['dm_po_ref']) == '')
{
	$sql .= " AND tran_date >= '".date2sql($_POST['start_date'])."'
			  AND tran_date <= '".date2sql($_POST['end_date'])."'";
			  
	if ($_POST['supp_id'])
	{
		$sql .= " AND a.supplier_id = ".$_POST['supp_id'];
	}
	
	if($_POST['status'] == 1) // deducted
		$sql .= " AND cv_id != 0";
	else if($_POST['status'] == 2) // undeducted
		$sql .= " AND cv_id = 0";
		
	if ($_POST['sdma_type'] != '')
		$sql .= " AND b.sdma_type = ".$_POST['sdma_type'];
	
	if ($_POST['comment_like'] != '')
		$sql .= " AND a.supp_reference LIKE '%".$_POST['comment_like']."%'";
}
else
{
	$sql .= " AND (a.reference LIKE ".db_escape('%'.$_POST['dm_po_ref'].'%')." 
			  OR b.reference LIKE ".db_escape('%'.$_POST['dm_po_ref'].'%')." )";
}

//echo $sql;
function cv_no_link($cv_id, $cv_no)
{
	global $path_to_root;
	if($cv_id != 0)
		return "<a target=blank href='$path_to_root/modules/checkprint/cv_print.php?
				cv_id=".($cv_id)."'onclick=\"javascript:openWindow(this.href,this.target); return false;\"><b>" .
				$cv_no . "&nbsp;</b></a> ";
	return '';
}

$sql .= " ORDER BY date_created, a.reference";
$res = db_query($sql);
display_error($sql);

start_table($table_style2.' width=90%');
$th = array('DM #', 'Agreement #', 'Agreement Date', 'DM Date', 'Supplier', 'Type', 'Amount', 'Effectivity', 'Comment', 'CV #');//,'Inactive');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$type = 53;
$k = 0;
while($row = db_fetch($res))
{
	alt_table_row_color($k);

	// label_cell(get_supplier_trans_view_str($type, $row['trans_no'], $row['reference']));
	
	label_cell($row['dm_ref']);
	label_cell($row['agreement_ref'],'align=center');
	label_cell(sql2date($row['date_created']));
	label_cell(sql2date($row['tran_date']));
	label_cell($row['supp_name'] ,'nowrap');
	label_cell($row['type_name']);
	
	// if ($row['ov_amount'] > 0)
	amount_cell(abs($row['ov_amount']));

	
	if ($row['agreement_ref'])
	{	
		if ($row['po_no'] != '')
		{
			$effectivity = 'for PO # '.$row['po_no'].' Only';
		}
		else if ($row['po_no'] == '' AND $row['once_only'] == 1)
		{
			global $frequency;
			if ($row['frequency'] == 0)
				$effectivity = 'for 1 CV dated '. sql2date($row['dm_date']);
			else
				$effectivity = 'for 1 CV '. $frequency[$row['frequency']] .' starting '. sql2date($row['dm_date']).
					' <br>(<i>for '. ($row['period']+1) .' deductions</i>)';
		}
		else
			$effectivity = sql2date($row['effective_from']) .' to '. sql2date($row['effective_to']);
	}
	
	label_cell($effectivity);
	
	if (trim($row['comment']) != '')
		label_cell($row['comment']);
	else
		label_cell($row['supp_reference']);
		
	
	label_cell(cv_no_link($row['cv_id'], $row['cv_no']));
	
	
	end_row();
}

end_table();
div_end();
end_form();



end_page();

?>
