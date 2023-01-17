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
$page_security = 'SA_GLSETUP';

$path_to_root = "..";
include($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

include($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('VOID CV, APV, CWO if amount == 0', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function delete_grns($type, $type_no)
{
	$sql = "DELETE FROM 0_grn_items
	WHERE grn_batch_id IN (SELECT id  FROM 0_grn_batch
	WHERE purch_order_no IN (SELECT order_no FROM 0_purch_orders
	WHERE reference IN (SELECT special_reference FROM `0_supp_trans`
	WHERE type = $type
	AND trans_no = $type_no
	)))";
	// echo ('<br>'.$sql.'<br>');
	db_query($sql, 'failed to delete grn_items for void');
	
	$sql = "DELETE FROM 0_grn_batch
	WHERE purch_order_no IN (SELECT order_no FROM 0_purch_orders
	WHERE reference IN (SELECT special_reference FROM `0_supp_trans`
	WHERE type = $type
	AND trans_no  = $type_no
	))";
	// echo ('<br>'.$sql.'<br>');
	db_query($sql, 'failed to delete grn_batch for void');


}
if (isset($_POST['fix_now']))
{
	begin_transaction();
	$sql = "SELECT b.cv_id,a.type, a.type_no, a.tran_date,a.dimension_id, SUM(round(amount,2)), 
					b.supp_reference,
					CONCAT('''', b.supp_reference, ''','),
					 b.ov_amount, CONCAT('''',b.special_reference, ''',')
					FROM 0_gl_trans a
					JOIN 0_supp_trans b ON (a.type = b.type AND a.type_no = b.trans_no)
					WHERE a.account IN (SELECT account_code FROM 0_chart_master)
					AND a.tran_date >= '".date2sql($_POST['from'])."'
					AND a.tran_date <= '".date2sql($_POST['to'])."'
					AND a.type IN (20,24)
					GROUP BY a.type, a.type_no
					HAVING SUM(round(amount,2)) != 0";
	// display_error($sql);die;
	$res_X = db_query($sql);
	
	$vmemo = "wrong GL amount";
	while($row = db_fetch($res_X))
	{
		 delete_grns($row['type'], $row['type_no']);
		 // die;
		if ($row['type'] == 20)
		{
			// void CV
			if ($row['cv_id'] != 0 AND !cv_has_payment($row['cv_id']))
			{
				$ordernum = $row['cv_id'];
				$date_ = Today();
				
				
				void_cv($ordernum);
				add_audit_trail(99, $ordernum, $date_, _("Voided.")."\n".$vmemo);
				add_voided_entry(99, $ordernum, $date_, $vmemo);
				
				display_notification('voided CV ID : '.$ordernum);
			}
			else if (cv_has_payment($row['cv_id']))
				continue;
			
			// void APV
			
			$date_ = date('m/d/Y');
			$ordernum = $row['type_no'];
			
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
			if(mysql_num_rows($query) > 0){
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
			
			display_notification('voided APV ID : '.$ordernum);
		}
			
		else if ($row['type'] == 24)
		{
			$date_ = date('m/d/Y');
			$ordernum = $row['type_no'];
			
			updateInvoice($ordernum,24);
		
			$sql = "SELECT ov_amount
						FROM 0_supp_trans
						WHERE trans_no = $ordernum
						AND type = 24";
			$query = db_query($sql);
			$res = mysql_fetch_object($query);
			$invtotal = $res->ov_amount;
		
			$sql = "UPDATE 0_supp_trans
						SET ov_amount = 0, ov_discount = 0, ov_gst = 0, alloc = 0
						WHERE trans_no = $ordernum
						AND type = 24";
			$query = db_query($sql);
			
			$sql = "UPDATE 0_supp_invoice_items
						SET quantity = 0, unit_price = 0, unit_tax = 0, memo_ = '$vmemo'
						WHERE supp_trans_no = $ordernum";
			$query = db_query($sql);
			
			$sql = "UPDATE 0_gl_trans
						SET amount = '0.00'
						WHERE type_no = $ordernum
						AND type = 24";
			$query = db_query($sql);
			
			$sql = "SELECT amt, trans_no_from, trans_type_from
						FROM 0_supp_allocations
						WHERE trans_no_to = $ordernum
						AND trans_type_to = 24";
			$query = db_query($sql);
			if(mysql_num_rows($query) > 0){
				while($res = mysql_fetch_object($query)){
					$amt = $res->amt;
					$from_id = $res->trans_no_from;
					$from_type = $res->trans_type_from;
					updateAlloc($from_id, $from_type, $amt);
				}
			}
			
			$sql = "DELETE FROM 0_supp_allocations
						WHERE trans_no_to = $ordernum
						AND trans_type_to = 24";
			$query = db_query($sql);
			
			add_audit_trail(24, $ordernum, $date_, _("Voided.")."\n".$vmemo);
			add_voided_entry(24, $ordernum, $date_, $vmemo);
			
			display_notification('voided CWO DEL ID : '.$ordernum);
		}
		
			display_notification('-----------------');
	}
	
	commit_transaction();
}

start_form();

if (!isset($_POST['from']))
	$_POST['from'] = '06/13/2016';
if (!isset($_POST['to']))
	$_POST['to'] = '06/23/2016';

start_table($table_style2);
date_row('DATE from:', 'from');
date_row('DATE to:', 'to');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>