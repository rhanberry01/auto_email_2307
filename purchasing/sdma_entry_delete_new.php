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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");


$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

 page('Delete Supplier Debit Memo Agreement', false, false,'', $js);

if (isset($_GET['DeletedID'])) 
{
	$trans_no = $_GET['DeletedID'];

   	display_notification_centered( _("Debit Memo Deleted"));

	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View Debit Memo")));

   	hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Debit Memo Inquiry"));

	display_footer_exit();
}

if (isset($_POST['Delete']))
{
	$sql = "SELECT * FROM  ".$_POST['branch'].".".TB_PREF."sdma 
			WHERE id = ".$_POST['sdma_id'];
	if ($_SESSION['wa_current_user']->username != 'admin' AND !$_SESSION['wa_current_user']->can_approve_sdma_1)
	{
		$sql .= " AND prepared_by = ".$_SESSION['wa_current_user']->user;
	}
	$sql .=	" AND approval_2 = 0";
//	display_error($sql);
//	display_error($sql);
	//die;
	$res = db_query($sql);
	
	if (db_num_rows($res)==0)
	{
		display_error("You can not delete this Debit Memo because it may have been posted or its not yours.");
		hyperlink_no_params($path_to_root."/purchasing/inquiry/sdma_inquiry.php", _("Back to Debit Memo Inquiry"));
		display_footer_exit();
	}
	else
	{
		$sql = "UPDATE ".$_POST['branch'].".".TB_PREF."sdma SET is_done = 2, approval_1 = ".$_SESSION['wa_current_user']->user." WHERE id =".$_POST['sdma_id'];
		db_query($sql,'failed to delete DM agreement');
		//display_error($sql);
		//die;
		meta_forward($_SERVER['PHP_SELF'], "DeletedID=".$_POST['sdma_id']);
	}
}

/* if (list_updated('amount_percentage') OR list_updated('just_once') OR isset($_POST['po_no']))
{
	global $Ajax;
	$id = $_GET['reference'];
	$Ajax->activate('main');
}
 */

start_form();
$br_code = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];

if($br_code != 'srsn'){

	display_error('== PLEASE LOGIN TO NOVA ==');

}else{
	div_start('main');

		
	start_table("$table_style2");
	
	label_cells('<b>Reference: </b>',$_GET['reference']);
	
	$csql = "SELECT a.* FROM ".$_GET['branch'].".".TB_PREF."sdma a WHERE reference = '".$_GET['reference']."'";
	$cquery = db_query($csql);			
	$crow=db_fetch($cquery);

	hidden('sdma_id',$crow['id']);
	hidden('branch',$_GET['branch']);

	label_row('<b>PO # (<i>if for 1 PO only</i>): ',$crow['po_no']);

	$ssql = "SELECT supp_name FROM ".$_GET['branch'].".".TB_PREF."suppliers WHERE supplier_id = ".$crow['supplier_id'];
	$squery = db_query($ssql);			
	$srow=db_fetch($squery);
	
	label_cells('<b>Supplier: ',$srow['supp_name']);
	
	$tsql = "SELECT * FROM ".$_GET['branch'].".".TB_PREF."sdma_type WHERE id=".$crow['sdma_type'];
	$tres = db_query($tsql);
	$trow = db_fetch($tres);
	
	label_row('<b>Type: </b>',$trow['type_name']);

	label_row('<b>Debit Memo Effectivity Date: </b>',$crow['dm_date']);
		
	hidden('effective_from','');
	hidden('effective_to','');
	
	if($crow['frequency'] = 1)
		$frequency = 'Weekly';
	else if($crow['frequency'] == 2)
		$frequency = 'Bi-weekly';
	else if($crow['frequency'] == 3)
		$frequency = 'Monthly';
	else if($crow['frequency'] == 4)
		$frequency = 'Quarterly';
	else
		$frequency = '';
	
	label_row('<b>Frequency:</b>',$frequency);
	label_row('<b>Periods (<i>repeat x times</i>): </b>',$crow['period']);

	if ($crow['amount'] != 0)
	{
		label_row('<b>Fixed Amount: </b>',number_format($crow['amount'], 2));		
	}
	else
	{
		label_row('<b>Percentage: </b>',number_format($crow['disc_percent'], 2).'%');	
	} 

		
	label_row('<b>Comment: </b>',$crow['comment']);
	end_table(1);
	div_end();

	echo '<center><font color=red><b>*will delete immediately</b> </font></center>';
	submit_center('Delete', _("<b>Delete Supplier Debit Memo</b>"), true , '', false);
		
	end_form();

}

end_page();
?>
