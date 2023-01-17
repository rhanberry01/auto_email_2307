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

page(_($help_context = "Suppliers with Biller Code"));
include($path_to_root . "/includes/ui.inc");

$sql = "SELECT * FROM ".TB_PREF."suppliers 
	WHERE `billing_institution_code` != ''
	ORDER BY supp_name";
$res = db_query($sql);

display_heading(db_num_rows($res).' Suppliers');


start_table($table_style2.'');
$th = array('', 'Supplier', 'Biller Code');
table_header($th);	
$k = 0;
$c = 0;
while($row = db_fetch($res))
{
	$c ++;
	alt_table_row_color($k);
	label_cell($c.'. &nbsp;&nbsp;');
	label_cell('<b>'.$row['supp_name'].'</b>');
	label_cell('<b>'.$row['billing_institution_code'].'</b>');
	end_row();
}
end_table();
end_page();

?>
