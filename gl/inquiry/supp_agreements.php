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
$path_to_root="../..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Supplier Agreements"), false, false, "", $js);

//----------------------------------------------------------------------------------------------------

function get_safs($a_type)
{
	$sql = "SELECT a.saf_no, GROUP_CONCAT(CONCAT(a.br_code, '~',a.amount)) as details, supplier_name, date,  type
				FROM srs_aria_z_reporting.`zzz_saf_saf` a
				JOIN `transfers`.`0_branches` b
				ON a.br_code = b.`code`
				WHERE a.saf_no != ''";
				
	if ($a_type)
		$sql .= " AND type = ".db_escape($a_type);
	
	$sql .= " GROUP BY a.saf_no
				ORDER BY supplier_name,type,date";
	// display_error($sql);
	$res = db_query($sql);
	return $res;
}

function get_safs_branches()
{
	$sql = "SELECT DISTINCT b.code, name, aria_db
				FROM srs_aria_z_reporting.`zzz_saf_saf` a
				JOIN `transfers`.`0_branches` b
				ON a.br_code = b.`code`";
	$res = db_query($sql);
	
	return $res;
}

function get_aria_amount_old($aria_db, $saf_no)
{
	$sql = "SELECT amount*(period+1) FROM $aria_db.`0_sdma`
				WHERE reference LIKE ". db_escape($saf_no) ." LIMIT 1";
	$res = db_query($sql);
	$row =db_fetch($res);
	
	return $row[0];
}

function get_aria_amount($aria_db, $saf_no)
{
	$sql = "SELECT id, reference  FROM $aria_db.`0_sdma`
				WHERE reference LIKE ". db_escape($saf_no) ."
				OR reference LIKE ". db_escape($saf_no.'_') ;
	$res = db_query($sql);
	
	if (db_num_rows($res) == 0)
		return 0;
	
	$where = array();
	while($row = db_fetch($res))
		$where [] = "(supp_reference = '".$row['reference']."' AND special_reference = '".$row['id']."')";
	
	
	
	$sql = "SELECT SUM(-ov_amount) FROM  $aria_db.`0_supp_trans`
	WHERE (".implode(' OR ',$where).")
	AND tran_date >= '2015-01-01'
	AND tran_date <= '2016-12-31'";
	$res = db_query($sql);
	$row =db_fetch($res);
	
	return $row[0];
}

function display_details($saf_no, $br_, $dets)
{
	$line_array = array();
	
	foreach($br_ as $br)
		$line_array[$br[0]] = 0;
	
	$det = explode(',',$dets);
	
	foreach($det as $line_detail)
	{
		list($br_code,$amount) = explode('~',$line_detail);
		
		if (!isset($line_array[$br_[$br_code][0]]))
		{	display_error($line_detail);die;}
		
		else
			$line_array[$br_[$br_code][0]] = array($amount,get_aria_amount($br_[$br_code][1],$saf_no));
	}
	
	$return_total = array();
	$c = 0;
	foreach($line_array as $amts)
	{
		$amt = $amts[0];
		$aria_amt = $amts[1];
		$diff = $amt - $aria_amt;
		
		$return_total[] = $diff;
		
		$d_amt = $amt != 0 ? number_format($amt,2) : '';
		$d_aria_amt = $aria_amt != 0 ? number_format($aria_amt,2) : '';
		$d_diff = $diff != 0 ? number_format($diff,2) : '';
			
		if ($diff == 0)
		{
			label_cell($d_amt,' align=right style="background-color:#B8E5F5;"');
			label_cell($d_aria_amt,' align=right style="background-color:#C6F5B8;"');
			label_cell($d_diff,' align=right style="background-color:#F5C8B8;font-weight:bold;"');
		}
		else
		{
			label_cell($d_amt,' align=right class=overduebg');
			label_cell($d_aria_amt,' align=right class=overduebg');
			label_cell($d_diff,' align=right class=overduebg');
		}
		
		$c++;
	}
	
	return $return_total;
}

start_form();
if (!isset($_POST['year']))
	$_POST['year'] = date('Y',strtotime('-1 year'));

echo "<center>";

// text_cells('Year : ', 'year');
// get_branchcode_list_cells('From Location:','from_loc',null,'ALL Branch');
// get_branchcode_list_cells('To Location:','to_loc',null,'ALL Branch');
// yesno_list_cells(' with discrepancy only ? ','discrep_only');
// yesno_list_cells(' with difference in year of Out and In  ? ','diff_date_only');

agreement_inquiry_type_list_cells('Agreement Type : ', 'a_type');
submit_cells('proceed','Search');

// display_error($_POST['a_type']);
if (!($_POST['proceed']))
	display_footer_exit();

br(2);

echo "</center>";

start_table($table_style2.' width=90%');

$k = 0;

$th = array('FILE', 'ARIA', 'Diff' );

start_row();
label_cell('SAF#','class=tableheader');
label_cell('Date','class=tableheader');
label_cell('Supplier','class=tableheader');
label_cell('Type','class=tableheader');
$br_res  = get_safs_branches();

$disp_array = array();
$count = 0;
while($row = db_fetch($br_res))
{
	$count ++;
	$disp_array[$row['code']] = array($count,$row['aria_db']);
	label_cell($row['name'],'class=tableheader colspan=3');
}
end_row();

$br_res  = get_safs_branches();
start_row();
label_cell('','class=tableheader');
label_cell('','class=tableheader');
label_cell('','class=tableheader');
label_cell('','class=tableheader');
while($row = db_fetch($br_res))
{
	foreach($th as $head)
		label_cell($head,'class=tableheader');
}
end_row();


// table_header($th);

$res = get_safs($_POST['a_type']);

display_notification('Count : '. db_num_rows($res));

$count = $k = 0;

$g_total = array();
while($row = db_fetch($res))
{
	alt_table_row_color($k);
	label_cell($row['saf_no']);
	label_cell($row['date'] != '0000-00-00' ? sql2date($row['date']) : '');
	label_cell('<b>'. $row['supplier_name']. '<b>',' nowrap=true');
	label_cell($row['type'],' nowrap=true');
	$g_total[] = display_details($row['saf_no'], $disp_array, $row['details']);
	// var_dump($g_total);die;
	end_row();
}


end_table('');

end_form();

end_page();

?>