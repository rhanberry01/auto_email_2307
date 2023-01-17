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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/db/item_transformation_db.inc");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Bank Statement"), false, false, "", $js);

function get_branch_($code)
{
	global $db_connections;
	$sql = "SELECT name from transfers.0_branches where code='".$code."'";
	$res = db_query($sql);
	$row = db_fetch_row($res);
	return $row[0];
}

$id = find_submit('submit');
//if(isset($_POST) && sizeof($_POST) > 0) {
//	echo "<pre>";
//	print_r($_POST);
//	echo "</pre>";
//}

	
if($id != -1){
	
	global $Ajax;

	if($_POST['clear'.$id] == null)
		$isclear = 0;
	else
		$isclear = 1;
	if($_POST['filterType'] == 1){
		$sql = "Update cash_deposit.".TB_PREF."bank_statement_aub SET type = ".$_POST['type'.$id].", branch_code = '".$_POST['branch'.$id]."',  reference = '".$_POST['reference'.$id]."', cleared = ".$isclear." where id=".$id;
		db_query($sql);
	}else{
		$sql = "Update cash_deposit.".TB_PREF."bank_statement_metro SET type = ".$_POST['type'.$id].", branch_code = '".$_POST['branch'.$id]."',  reference = '".$_POST['reference'.$id]."', cleared = ".$isclear." where id=".$id;
		db_query($sql);
	}
		
	//display_error($sql);
	
	$sql = "SELECT aria_db from transfers.0_branches where code='".$_POST['branch'.$id]."'";
	$res = db_query($sql);
	$row1 = db_fetch_row($res);
	
	//display_error($row1[0]);
	
	$nos = explode(',',$_POST['reference'.$id]);
	foreach($nos as $i =>$ref) {
		$i>0;
		if($_POST['type'.$id] == 2){
			$sql1 = "Update ".$row1[0].".".TB_PREF."other_income_payment_header SET bd_reconciled = ".$isclear.", bd_recon_date = '".$_POST['date_deposit'.$id]."', bd_recon_ref = '".$id."'  where bd_trans_no='".$ref."'";
		}else{
			$sql1 = "Update cash_deposit.".TB_PREF."cash_dep_header_new SET cd_cleared = ".$isclear.",  cd_date_deposited='".$_POST['date_deposit'.$id]."', cd_date_cleared='".date2sql(Today())."'  where cd_id=".$ref;
		}
		db_query($sql1);
		
		$description = 'Trans:'.$ref;
		$sql = "INSERT INTO  cash_deposit.".TB_PREF."audit_trail (branch, type, description, user) VALUES (".db_escape($row1[0]).",'APPROVING','".$description ."', '".$_SESSION['wa_current_user']->user."')"; 
		db_query($sql);
	//	display_error($sql1);
	}	

	$Ajax->activate('list');

	
}

start_form();
//div_start('assa');
//	echo '34534434';
//div_end();
div_start('header');

$type = ST_ITEM_TRANSFORMATION;

// // if (!isset($_POST['start_date']))
	// // $_POST['start_date'] = '01/01/'.date('Y');
start_table();
	start_row();
		ref_cells(_("Bank Statement #:"), 'id', null,null, null, true);
		bank_list_cells('Bank', 'filterType', $_POST['filterType'], true);
		//yesno_list_cells(_("Status Type:"), 'movement_type', '',_("Deliver to Branch"), _("Received from Branch"));
		//adjustment_types_list_row(_("Movement Type:"), 'movement_type', $row['id']);
		status_type_list_cells('Status', 'status_type', null, true);
		//yesno_list_cells(_("Status Type:"), 'status_type', '',_("Cleared"), _("Not Cleared"));
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();


//if (!isset($_POST['search']))
	//display_footer_exit();
	
//$sql = "SELECT a.* from ".TB_PREF."transformation_header a WHERE";
if($_POST['filterType'] == 1)
	$sql = "select * from cash_deposit.".TB_PREF."bank_statement_aub where";
else if($_POST['filterType'] == 2)
	$sql = "select * from cash_deposit.".TB_PREF."bank_statement_metro where";

 if ($_POST['start_date'])
{
	$sql .= "  date_deposited >= '".date2sql($_POST['start_date'])."'
			  AND date_deposited<= '".date2sql($_POST['end_date'])."'";	
}
if($_POST['id'] != null)		
	$sql .= " AND id = ".$_POST['id']."";

if ($_POST['status_type']==1)
{
//Not Cleared
$sql .= "  AND cleared='1'";
}else if($_POST['status_type']==2){
//Cleared
$sql .= "  AND cleared='0'";
} else{
	$sql .= "";
}


$sql .= " AND debit_amount=0 order by date_deposited";
//display_error($sql);
$res = db_query($sql);
//display_error($sql);

div_start('list');
start_table($table_style2.' width=95%');
$th = array();
	
array_push($th, 'Bank Statement #', 'Date Deposited', 'Bank Ref #','Deposit Type','Debit Amount','Credit Amount','Balance','Type','Reference','Cleared', 'Branch', '', '','');


if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

	//display_error($approver);

while($row = db_fetch($res))
{
	alt_table_row_color($k);
	hidden('date_deposit'.$row['id'], $row['date_deposited']);
	label_cell($row['id']);
	label_cell(sql2date($row['date_deposited']));
	label_cell($row['bank_ref_num']);
	label_cell($row['deposit_type']);
	label_cell(number_format2($row['debit_amount'],2));
	label_cell(number_format2($row['credit_amount'],2));
	label_cell(number_format2($row['balance'],2));
	type_list_cells(null, 'type'.$row['id'],$row['type'] == 0 ? '0':$row['type'], true);
	//label_cell($row['type']);
	textarea_cells(null, 'reference'.$row['id'],  $row['reference'], 20, 2);
	check_cells(null,"clear".$row['id'],$row['cleared']);
//	label_cell($row['cleared'] == 0 ? "NO":"YES");
	get_branchcode_list_cells(null,'branch'.$row['id'],$row['branch_code']);
	//if($_POST['filterType'] == 1)
		
			label_cell(get_bank_suggestion_str($row['date_deposited'],$_POST['filterType'], $row['credit_amount'],  'View Suggestion'));
		//else
		//	label_cell();
			 
		if($row['type'] == '101'){
			label_cell(get_bank_recon_101_view_str($row['branch_code'],$row['reference'],  'View'));
			//submit_cells("submit".$row['id'], _("Approve"), "colspan=1",_('Approve'), true, '');
			echo "<td colspan='1'><button type='submit' name='submit".$row['id']."' id='submit".$row['id']."' value='Approve' title='Approve'><span>Approve</span></button>
			</td>";
			//button_cell("submit".$row['id'], _("Cleared"), false, ICON_UPDATE);
		}else if($row['type'] == '2'){
			label_cell(get_bank_recon_2_view_str($row['branch_code'],$_POST['filterType'],$row['id'],$row['date_deposited'],$row['credit_amount'], $row['reference'], 'View'));
			//button_cell("submit".$row['id'], _("Cleared"), false, ICON_UPDATE);
			echo "<td colspan='1'><button type='submit' name='submit".$row['id']."' id='submit".$row['id']."' value='Approve' title='Approve'><span>Approve</span></button>
			</td>";
		}else if($row['type'] == '22' or $row['type'] == '62'){
			label_cell();
			label_cell();
			//echo "<td colspan='1'><button type='submit' name='submit".$row['id']."' id='submit".$row['id']."' //value='Approve' title='Approve'><span>Approve</span></button>
			//</td>";
		}else{
			label_cell();
			echo "<td colspan='1'><button type='submit' name='submit".$row['id']."' id='submit".$row['id']."' value='Approve' title='Approve'><span>Approve</span></button>
			</td>";
		}
		
	//else if($_POST['filterType'] == 2)
	//	label_cell($row['reference']);
	//label_cell($user_post['real_name']);
	//label_cell(get_comments_string($type, $row['trans_no']));
	
	/*if ($_POST['status_type']==1){
		$uncleared='untag'.$row["id"];
		submit_cells($untag, _("Uncleared"), "colspan=2",_('Uncleared'), true,ICON_DELETE);	
	}else{
		label_cell(get_bank_tagging_str($_POST['filterType'],$row['id'], 'Cleared'));
	} */
	
	end_row();
}
end_table();
br();
br();
div_end();

end_form();
end_page();
?>