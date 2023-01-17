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
	
page(_($help_context = "Special Discounted Items Inquiry"), false, false, "", $js);

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = begin_month(Today());

start_table();
	start_row();
		// supplier_list_ms_cells('Supplier :', 'supp_id', null, true);
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
// if (!isset($_POST['search']))
	// display_footer_exit();
	
//----------original----------start
// $sql = "SELECT  a.ReceivingID, a. ReceivingNo, a.DateReceived, a.Description as supplier_name, b.Description as item, b.UOM, b.qty
			// FROM ROF a, ROFLine b
			// WHERE a.ReceivingID = b.ReceivingID";
		
// if($_POST['supp_id'] != '') 
	// $sql .= " AND a.VendorCode = ". db_escape($_POST['supp_id']);


// $sql .= " AND a.DateReceived >= '".date2sql($_POST['start_date'])."'
			  // AND a.DateReceived <= '".date2sql($_POST['end_date'])."'";
			  
// $sql .= " ORDER BY DateReceived, a.Description, b.Description";
//----------original----------end

$items = array();

$sql = "SELECT ProductID, Description, SalesOrder FROM Products WHERE SalesOrder > 0";

// if($_POST['supp_id'] != '') 
	// $sql .= "  AND vendorcode = ". db_escape($_POST['supp_id']);

$res = ms_db_query($sql);

while($row = mssql_fetch_array($res)){
	$items[] = $row['ProductID'];
}

// display_error($sql);

$sql2 = "SELECT c.supp_name, item_code, description, SUM(`extended`) as total
				FROM `0_grn_batch` a
				JOIN 0_grn_items b ON a.id = b.grn_batch_id
				JOIN 0_suppliers c ON a.supplier_id = c.supplier_id
				WHERE a.delivery_date BETWEEN '".date2sql($_POST['start_date'])."' AND '".date2sql($_POST['end_date'])."'
				AND b.item_code IN(".implode(',', $items).")
				GROUP BY a.supplier_id, item_code
				ORDER BY c.supp_name";
$res2 = db_query($sql2);

// display_error(db_num_rows($res2)." ".$sql2);



// $th = array ('#', 'Supplier', 'Product Code', 'Description', 'Extended', 'Special CAIP Discount');
$th = array ('#', 'Product Code', 'Description', 'Extended', 'Special CAIP Discount (2.5%)');

// if (mssql_num_rows($res) > 0)

// else 
// {
	// display_heading('No transactions found');
	// display_footer_exit();
// }

$k = 0;
$counter = 1;
$net_of_vat = $caip_discount_amt = $tot_caip_disc_amt = $tot_extended = 0;

$supp_name = '';
while($row2 = db_fetch($res2))
{

	$net_of_vat = ($row2['total']/1.12)+0; //Get net of vat
	$caip_discount_amt = ($net_of_vat*0.025)+0; //CAIP discounted amount
	
	if($row2['supp_name'] != $supp_name AND $supp_name == '')
	{
		//Pangdisplay ng heading na supplier name
		display_heading($row2['supp_name']);
		start_table($table_style2.' width=90%');
		table_header($th);
	}
	
	if($row2['supp_name'] != $supp_name AND $supp_name != '')
	{
		//Total para dun sa di part ng while loop
		alt_table_row_color($k);
		label_cell('     <b>TOTAL</b>', 'colspan = 3 align="right"');
		amount_cell($tot_extended, true);
		amount_cell($tot_caip_disc_amt, true);
		end_row();
		end_table(2);
		
		display_heading($row2['supp_name']);
		start_table($table_style2.' width=90%');
		table_header($th);
		
		$tot_extended = 0;
		$tot_caip_disc_amt = 0;
	}
	
	$supp_name = $row2['supp_name'];
	
	
	alt_table_row_color($k);
	
	label_cell($counter);
	// label_cell($row2['supp_name']);
	label_cell($row2['item_code']);
	label_cell($row2['description']);
	// label_cell($row2['total']);
	amount_cell($row2['total']);
	// label_cell($caip_discount_amt);
	amount_cell($caip_discount_amt);
	$tot_extended += $row2['total'];
	$tot_caip_disc_amt += $caip_discount_amt;
	
	end_row();
	$counter++;
}

//Total para dun sa di part ng while loop
alt_table_row_color($k);
label_cell('     <b>TOTAL</b>', 'colspan = 3 align="right"');
amount_cell($tot_extended, true);
amount_cell($tot_caip_disc_amt, true);
end_row();

end_table();
div_end();
end_form();

end_page();

?>
