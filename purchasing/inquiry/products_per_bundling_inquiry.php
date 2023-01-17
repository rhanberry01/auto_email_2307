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
	
page(_($help_context = "Products Per Bundling Inquiry"), false, false, "", $js);

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
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		get_branchcode_list_cells('Branch:','from_loc',null,'Select Branch');
		ref_cells('Reference :', 'dm_po_ref');
		supplier_list_cells('Supplier :', 'supp_id', null, true);
		date_cells('Date Created From :', 'start_date');
		date_cells(' To :', 'end_date');
		//allyesno_list_cells('Approval Status:','status', null, 'ALL', 'approved', 'not yet approved');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
// if (!isset($_POST['search']))
	// display_footer_exit();

####rhan 82218 #####

$br_code=$_POST['from_loc'];


#######rhan
if(!$_POST['from_loc']){
	$branches_sql = "SELECT * FROM transfers.0_branches_other_income";
}else{
	$branches_sql = "SELECT * FROM transfers.0_branches_other_income WHERE code ='".$_POST['from_loc']."' ";
}
$branches_query = db_query($branches_sql);

$all_branch = array();

while ($branches_res = db_fetch($branches_query)) {


	$sql = "select glt.type_no,s.supp_name,glt.memo_,SUM(glt.amount) as amount,st.supp_reference,ch.cv_no
	from ".$branches_res['aria_db'].".".TB_PREF."gl_trans as glt LEFT JOIN ".$branches_res['aria_db'].".".TB_PREF."supp_trans as st ON glt.type = st.type  and glt.type_no = st.trans_no
	LEFT JOIN ".$branches_res['aria_db'].".".TB_PREF."suppliers as s on s.supplier_id =  st.supplier_id LEFT JOIN ".$branches_res['aria_db'].".".TB_PREF."cv_header as ch on ch.id = st.cv_id 
	WHERE glt.memo_ like '%products for bundling, SAF NO%' and glt.type = '53' and account ='2478'";
			
	if (trim($_POST['dm_po_ref']) == '')
	{
		$sql .= " AND DATE(glt.tran_date) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(glt.tran_date) <= '".date2sql($_POST['end_date'])."'";
				  
		if ($_POST['supp_id'])
		{
			$sql .= " AND st.supplier_id = ".$_POST['supp_id'];
		}
	}
	else
	{
		$sql .= " AND st.supp_reference LIKE ".db_escape('SAF NO:'.$_POST['dm_po_ref'].'%')."";
	}
	$sql .= " GROUP BY glt.memo_,st.supp_reference,ch.cv_no ORDER BY supp_name";
	$res = db_query($sql);


	while ($row = db_fetch_assoc($res)) {
		$_row = $row;
		$_row['branch_name'] = $branches_res['name'];
		$_row['branch_db'] = $branches_res['aria_db'];
		array_push($all_branch, $_row);
	}

}




start_table($table_style2.' width=90%');
$th = array('Branch','#','Supplier Name', 'Memo', 'Amount', 'Reference','CV #','');//,'Inactive');


if (!empty($all_branch))
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}


$type = 53;
$k = 0;
$count  = 0;
foreach ($all_branch as $key => $row) 
{

	alt_table_row_color($k);

	// label_cell(get_supplier_trans_view_str($type, $row['trans_no'], $row['reference']));
	label_cell($row['branch_name']);
	label_cell($row['type_no']);
	label_cell($row['supp_name'] ,'nowrap');
	label_cell($row['memo_'] ,'nowrap');
	amount_cell($row['amount']);
	label_cell($row['supp_reference'] ,'nowrap');
	label_cell($row['cv_no'] ,'nowrap');
	$supp_reff = explode(":",$row["supp_reference"]);
	label_cell(get_ppd_details_view_str($row['branch_db'],$supp_reff[1],'View'));

}


end_row();
end_table();
div_end();
end_form();



end_page();

?>
