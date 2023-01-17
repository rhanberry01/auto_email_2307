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

include($path_to_root . "/includes/ui.inc");

page('Fix Nova Actual Count', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------
function update_if_vatable($barcode)
{
	$sql = "SELECT b.pVatable FROM nova_pos_products a, nova_products b
			WHERE a.Barcode LIKE '%$barcode%'
			AND b.ProductID = a.ProductID
			AND b.pVatable = 1";
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return false;
	
	$sql = "UPDATE nova_actual_count SET IS_VATABLE = 1 WHERE UPC = '$barcode'";
	db_query($sql);
}


if (isset($_POST['fix_now']))
{
	$sql = "SELECT UPC FROM nova_actual_count";
	$res = db_query($sql);
	
	while($row = db_fetch($res))
	{
		update_if_vatable($row['UPC']);
	}
	
	display_notification('SUCCESS');
}

start_form();
submit_center('fix_now', 'Fix Nova Actual Count');
end_form();

end_page();
?>
