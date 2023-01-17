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
$page_security = 'SA_SUPPLIER';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Supplier Off Invoice Discount"), false, false, "", $js);

include_once($path_to_root . "/includes/ui.inc");

//================================================================

if (isset($_POST['add_disc']))
{
	if (input_num('discount') > 100 OR input_num('discount') <= 0)
	{
		display_error('Invalid Discount');
		unset($_POST['add_disc']);
	}
}

if (isset($_POST['add_disc']))
{
	global $Ajax;
	$sql = "INSERT INTO ".TB_PREF."supplier_off_invoice (supplier_id, discount)
			VALUES(".$_POST['supp_id'].",".input_num('discount').")";
	db_query($sql,'failed to insert off invoice discount');
	
	$_POST['discount'] = '';
	$Ajax->activate('details');
}

$del_id = find_submit('Delete');
if ($del_id != -1)
{
	global $Ajax;
	$sql = "DELETE FROM ".TB_PREF."supplier_off_invoice WHERE id =$del_id";
	db_query($sql,'failed to delete discount');
	$_POST['discount'] = '';
	$Ajax->activate('details');
}

start_form();
div_start('header');
	echo "<center>Supplier : ";
	echo supplier_list('supp_id', null, "All Suppliers", true);
	echo "</center><br><br>";
div_end();

div_start('details');
if ($_POST['supp_id'] == '') //view all
{
	start_table($table_style2);
	$th = array('Supplier','Discount/s');
	table_header($th);
	
	$sql = "SELECT a.*, b.supp_name FROM ".TB_PREF."supplier_off_invoice a, ".TB_PREF."suppliers b
			WHERE a.supplier_id = b.supplier_id
			ORDER BY supp_name,id";
	$res = db_query($sql);
	
	$k = 0;
	$supp_name = '';
	$disc = array();
	while($row = db_fetch($res))
	{
		if ($supp_name != $row['supp_name'] AND $supp_name != '')
		{
			alt_table_row_color($k);
			label_cell($supp_name);
			label_cell(implode(', ',$disc), 'align=right');
			end_row();
			
			$supp_name = $row['supp_name'];
			$disc = array();
		}
		// else
		// {
			$supp_name = $row['supp_name'];
			$disc[] = $row['discount'].'%';
		//}
	}
	alt_table_row_color($k);
	label_cell($supp_name);
	label_cell(implode(', ',$disc), 'align=right');
	end_row();
	end_table();
}
else
{
	start_table($table_style2);
	$th = array('Discounts','');
	table_header($th);
	
	$sql = "SELECT * FROM ".TB_PREF."supplier_off_invoice 
			WHERE supplier_id = ".$_POST['supp_id']." 
			ORDER BY id";
	$res = db_query($sql);
	
	$k = 0;
	while($row = db_fetch($res))
	{
		alt_table_row_color($k);
		label_cell($row['discount'].'%', 'align=right');
		delete_button_cell("Delete".$row['id'], _("Delete"));
		end_row();
	}
	
	end_table(2);
	
	start_table();
		start_row();
		percent_cells('Discount : ', 'discount');
		submit_cells('add_disc', 'Add', '', false, true);
		end_row();
	end_table();
	
}
div_end();
end_form();

end_page();

?>
