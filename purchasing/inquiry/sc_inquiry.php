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

define('K_PATH_FONTS', "../../reporting/fonts/");
include($path_to_root . "/reporting/includes/pdf_report.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_cv.inc");
require_once($path_to_root . '/modules/PHPMailer/class.phpmailer.php');


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Supplier Clearance Inquiry"), false, false, "", $js);

function get_display_username_by_id_and_branch_($id)
{
	$sql2 = "SELECT real_name FROM srs_aria_nova.".TB_PREF."users WHERE id = $id";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	return $row2[0];
}

$id = find_submit('Delete');
if ($id != -1)
{
	//global $Ajax;
	//display_error($id);
//	begin_transaction();
	handle_delete_item($id);
	meta_forward($path_to_root.'/purchasing/inquiry/sp_inquiry.php');
	//$Ajax->activate();
}	

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('Reference :', 'dm_po_ref');
		date_cells('Date Created From :', 'start_date');
		date_cells(' To :', 'end_date');
		if($_SESSION['wa_current_user']->user == 1 OR $_SESSION['wa_current_user']->user == 888 OR $_SESSION['wa_current_user']->user == 633  OR $_SESSION['wa_current_user']->user == 730 OR $_SESSION['wa_current_user']->user == 642 OR $_SESSION['wa_current_user']->user == 651 OR $_SESSION['wa_current_user']->user == 886){
			supplier_list_ms_cells('Database Supplier:', 'supp_id', null, 'Supplier Name');
		//supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		}else{
			purchaser_supplier_list_ms_cells('Database Supplier:', 'supp_id', null, 'Supplier Name');
		//purchaser_supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		
		}
		purchaser_list_cells('Purchaser :', 'purchaser', null, 'Please Select');
		allyesno_list_cells('Reason for Clearance:','reason', null, 'ALL', 'Change Of Supplier (Traanfer of Account)', 'Supplier Clearance (Pull Out)');
		//yesno_list_cells('Reason for Clearance:','reason', null, 'Change Of Supplier (Traanfer of Account)', 'Supplier Clearance (Pull Out)');
		submit_cells('search', 'Search');
		//submit_cells('export','Export','','');	
	end_row();
end_table(2);
div_end();

div_start('dm_list');
// if (!isset($_POST['search']))
	// display_footer_exit();

	
	$sql = "SELECT * FROM 0_suppliers_clearance_new as a  WHERE  DATE(dateAdded) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(dateAdded) <= '".date2sql($_POST['end_date'])."'";
	 if ($_POST['purchaser'])
	{
		$sql .= " AND a.addedBy = '".$_POST['purchaser']."'";
	}
	
	if ($_POST['reason'] == 0 OR $_POST['reason'] == 1)
	{
		$sql .= " AND a.type = '".$_POST['reason']."'";
	}
	
	if (trim($_POST['dm_po_ref']) == '')
	{
		$sql .= " AND DATE(dateAdded) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(dateAdded) <= '".date2sql($_POST['end_date'])."'";
				  
		if ($_POST['supp_id'])
		{
			$sql .= " AND a.supplierCode = '".$_POST['supp_id']."'";
		}	
	}else{
		$sql .= " AND a.transNo = '".$_POST['dm_po_ref']."'";
		
	}
	
	 
	$sql .= " ORDER BY dateAdded";
	$res = db_query($sql);
//display_error($sql);

start_table($table_style2.' width=90%');
$th = array('#', 'Reference', 'Date Created', 'Reason' ,'Purchaser', 'Supplier', 'Comment');


if (!empty($res))
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$type = 53;
$k = 0;
$amount=0;
while ($row = db_fetch_assoc($res)) 
{
	alt_table_row_color($k);
	label_cell($row['id']);
	//label_cell($row['transNo']);
	label_cell(
				"<a target=blank href='$path_to_root/reporting/supplier_clearance.php?
					id=".$row['id']."$branch'onclick=\"javascript:openWindow(this.href,this.target); return false;\">" .
					$row['transNo'] . "&nbsp;</a> "
				
				);
	label_cell(sql2date($row['dateAdded']));
	if($row['type'] == 0){		
		label_cell('Supplier Clearance (Pull Out)');
	}else{
		label_cell('Change Of Supplier (Traanfer of Account)');
	}
	label_cell(strtoupper(get_display_username_by_id_and_branch_($row['addedBy'])));
	$sql1 = "SELECT description FROM vendor where vendorcode = '".$row['supplierCode']."'";
	$res1 =  ms_db_query($sql1);
	$row1 = mssql_fetch_row($res1);
	label_cell($row1[0]);
	
	
	// label_cell(yes_no($row['once_only']),'align = center');
	
	label_cell($row['remarks']);
	
	// echo button('c_delete', 'Cancel', 'Cancel delete', false);
	// echo button("Delete".$row['id'], 'Delete', 'Delete this item', ICON_DELETE);
	/* if($row["status"] == 0){
		label_cell(pager_link(_("Edit"),
				"/purchasing/supplier_payment_sign.php?id=" . $row["id"], ICON_EDIT));
	echo "<td align=center>";
		submit('Delete'.$row['id'], "", true, 'Delete this item', true, ICON_DELETE);
		echo "</td>";
	//	label_cell(pager_link(_("Delete"),
	//				"/purchasing/supplier_payment_delete.php?id=" . $row["id"], ICON_DELETE));
					
		end_row();
	}else{
		if($row["status"] == 2){
			label_cell("<b>DELETED</b>","colspan=2 align='center'");
		}else{
			label_cell("<b>USED</b>","colspan=2 align='center'");
			
		}
	} */
//	$amount = $amount + $row['amount'];
}
//alt_table_row_color($k);
//label_cell("<b>Total: </b>","colspan=6 align='right'");
//label_cell("<b> ".number_format($amount,2)."</b>","colspan=6 align='left'");
//end_row();

end_table();
div_end();

echo '<center style="margin: 10px 0;">
		<button class="inputsubmit" type="submit" id="approve_all_checked" name="approve_all_checked" style="display: none;">
			<span>Approve All Checked</span>
		</button>
	</center>';

end_form();



end_page();

?>
