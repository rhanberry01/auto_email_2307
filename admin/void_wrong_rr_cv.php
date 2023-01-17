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
$page_security = 'SA_SETUPCOMPANY';
$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

page('void wrong RR CV', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------
function get_cv_of_rr($grn_batch_id)
{
	$sql= "SELECT b.id FROM 0_grn_batch a, 0_grn_items b
			WHERE a.id = $grn_batch_id
			AND a.id = b.grn_batch_id
			LIMIT 1";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$grn_item_id = $row[0];
	
	
	$sql = "SELECT e.id,e.cv_no FROM 0_supp_invoice_items c , 0_supp_trans d, 0_cv_header e
			WHERE  c.grn_item_id = $grn_item_id
			AND c.supp_trans_no = d.trans_no
			AND c.supp_trans_type = 20
			AND d.type = 20
			AND d.cv_id = e.id
			LIMIT 1";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

if (isset($_POST['voidmaster']))
{
	$sql = "SELECT a.*
			FROM 0_grn_batch a
			WHERE a.reference like '%_____84___' 
			ORDER BY delivery_date DESC";
	// display_error($sql);die;
	$res1 = db_query($sql);
	$date_ = Today();
	$vmemo = ' wrong branch for PO RR APV CV';
	while($row = db_fetch($res1))
	{
		begin_transaction();
		$cv_row = get_cv_of_rr($row['id']);
		
		// delete PO
			delete_po($row['purch_order_no']);
		
		//void CV
			if ($cv_row['id'] != '')
			{	
				void_cv($cv_row['id']);
				add_audit_trail(ST_CV, $cv_row['id'], $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry(ST_CV, $cv_row['id'], $date_, $vmemo);
			}
		//-----------------------------------------------------------------------------------------
		
		//void APV
			$number = get_reference(ST_SUPPINVOICE, $ordernum);
			
			$sql = "SELECT * FROM `0_cv_details` WHERE `cv_id` = ".$cv_row['id']." AND `trans_type` = '20' ";
			$res2= db_query($sql);
			while($cv_d_row = db_fetch($res2))
			{
				$ordernum = $cv_d_row['trans_no'];
				updateInvoice($ordernum);
			
				$sql = "SELECT ov_amount
							FROM 0_supp_trans
							WHERE trans_no = $ordernum
							AND type = 20";
				$query = db_query($sql);
				$res = mysql_fetch_object($query);
				$invtotal = $res->ov_amount;
			
				$sql = "UPDATE 0_supp_trans
							SET ov_amount = 0, ov_discount = 0, ov_gst = 0, alloc = 0
							WHERE trans_no = $ordernum
							AND type = 20";
				$query = db_query($sql);
				
				$sql = "UPDATE 0_supp_invoice_items
							SET quantity = 0, unit_price = 0, unit_tax = 0, memo_ = '$vmemo'
							WHERE supp_trans_no = $ordernum";
				$query = db_query($sql);
				
				$sql = "UPDATE 0_gl_trans
							SET amount = '0.00'
							WHERE type_no = $ordernum
							AND type = 20";
				$query = db_query($sql);
				
				$sql = "SELECT amt, trans_no_from, trans_type_from
							FROM 0_supp_allocations
							WHERE trans_no_to = $ordernum
							AND trans_type_to = 20";
				$query = db_query($sql);
				if(mysql_num_rows($query) > 0)
				{
					while($res = mysql_fetch_object($query)){
						$amt = $res->amt;
						$from_id = $res->trans_no_from;
						$from_type = $res->trans_type_from;
						updateAlloc($from_id, $from_type, $amt);
					}
				}
				
				$sql = "DELETE FROM 0_supp_allocations
							WHERE trans_no_to = $ordernum
							AND trans_type_to = 20";
				$query = db_query($sql);
				
				add_audit_trail(20, $ordernum, $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry(20, $ordernum, $date_, $vmemo);
			}
		//-----------------------------------------------------------------------------------------
		
		//delete RR
			$sql = "DELETE FROM 0_grn_items WHERE grn_batch_id = ". $row['id'];
			db_query($sql,'failed to delete grn items');
			$sql = "DELETE FROM 0_grn_batch WHERE id = ". $row['id'];
			db_query($sql,'failed to delete grn header');
		//-----------------------------------------------------------------------------------------	
		commit_transaction();
	}
}

start_form();
submit_center('voidmaster', 'VOID all wrong RR and CV');
end_form();

end_page();
?>
