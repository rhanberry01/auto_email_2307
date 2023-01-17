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
include_once($path_to_root . "/gl/includes/db/rs_db.inc");

page('Create DM for ALL pending RETURNS', false, false,'', '');
set_time_limit(0);
//-----------------------------------------------------------------------------------
if (isset($_POST['fix_now']))
{
	if ($_POST['del_rs'] != '') // delete RS
	{
		begin_transaction();
		
		$sql = "SELECT rs_id FROM ".TB_PREF."rms_header 
				WHERE movement_type = 'R2SSA' 
				AND processed = 1
				AND trans_no = 0
				AND movement_no IN (".$_POST['del_rs'].")";
		// display_error($sql);
		$res = db_query_rs($sql);
		
		$rs_ids = array();
		while($row = db_fetch($res))
			$rs_ids[] = $row[0];
		
		$sql = "DELETE FROM ".TB_PREF."rms_items 
				WHERE rs_id IN (".implode(',',$rs_ids).")";
		db_query_rs($sql);
		
		$sql = "DELETE FROM ".TB_PREF."rms_header 
				WHERE rs_id IN (".implode(',',$rs_ids).")";
		db_query_rs($sql);
		
		commit_transaction();
	}
	
	$sql= "SELECT a.*, SUM(b.qty*b.price) as Total 
			FROM 0_rms_header a, 0_rms_items b 
			WHERE a.bo_processed_date >= '2013-01-01' 
			AND a.processed = 1 AND a.movement_type = 'R2SSA' 
			AND a.trans_no = 0 
			AND a.rs_id = b.rs_id 
			GROUP BY b.rs_id 
			ORDER BY movement_type,movement_no, rs_id";
	$res = db_query_rs($sql);
	$rs_id_a = array();
	while($row = db_fetch($res))
	{
		$rs_id_a[0] = $row['rs_id'];
		begin_transaction();
			create_debit_memo_for_rs($rs_id_a);
		commit_transaction();
	}
	display_notification('Done!');
}

start_form();
start_table();
textarea_row(_("DELETE RS: <i>(ex. 1,2,3,4,5)</i>"), 'del_rs', null, 30, 4);
end_table();
submit_center('fix_now', 'DELETE then Confirm ALL remaining RS to DM');
end_form();

end_page();
?>
