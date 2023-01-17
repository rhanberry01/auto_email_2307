<?php
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
ini_set('memory_limit', '-1');
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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Fix Journal Ref', false, false, "", $js);
set_time_limit(0);

function get_lastRef_used($get_2018){

	 if($get_2018){
	 	$get_ref = "select DISTINCT ";
	 	$date_to = '2018-01-01';
	 	$date_from = '2018-12-31';
	 	$LIMIT = 0;
	 	$order = 'ASC';
		//$query = "LIKE '%-2018%' AND "; 
		$query = " AND "; 
	}else{
		$get_ref = "select ";
		$date_to = '2017-01-01';
	 	$date_from = '2017-12-31';
		$query = " AND "; 
		$LIMIT = 1;
		$order = 'ASC';

	}		

	

	 $get_ref .=	"type_no,reference
					from 0_gl_trans as gl
					INNER JOIN 0_refs as r on gl.type = r.type and gl.type_no = r.id
					 ";

	 $get_ref .= $query;
	
	 $get_ref .=" gl.tran_date >= '".$date_to."' and  gl.tran_date <= '".$date_from."' and gl.type = 0
				  and gl.type = 0 
				  ORDER BY type_no ";

     $get_ref .= $order;


	 if($LIMIT == 1){

	 $get_ref .= " LIMIT 1";

	 }		  
	 
		$res_type = db_query($get_ref);
		$res_ = db_fetch($res_type);

	if($get_2018){
		
	   	return array($get_ref,$res_type);

	}else{
		if(strpos($res_['reference'], '-2017')){
			$n_ref = str_replace('-2017','', $res_['reference']);
			$old_type_no = preg_replace('/[^0-9]/ ','',$n_ref);
		return array($old_type_no,$get_ref);
		}else{
		$old_type_no = preg_replace('/[^0-9]/ ','',$res_['reference']);
		return array($old_type_no,$get_ref);
		}

	}
		

}


function get_wrong_ref_used(){

	 $get_ref = "select DISTINCT gl.type_no,gl.tran_date,r.reference
				from 0_gl_trans as gl
				INNER JOIN 0_refs as r on gl.type = r.type and gl.type_no = r.id and  
				gl.tran_date >= '2017-01-01' and  gl.tran_date <= '2017-12-31' and gl.type = 0
				ORDER BY type_no ASC";

		$res_type = db_query($get_ref);			
		return $res_type ;
}


function get_j_branch_code($br_code)
{

	$sql = "SELECT journal_code FROM transfers.0_branches_other_income WHERE code =".db_escape($br_code)."";
	$result = db_query($sql, "could not get  $br_code");
	//return $sql; 
	$res = db_fetch($result);
	return $res['journal_code'];
}


function update_ref($trans_no,$new_ref)
{
	$sql = "UPDATE 0_refs SET reference ='".$new_ref."' WHERE id =".$trans_no." AND type = 0 ";
	db_query($sql, "could not get  $br_code");
	return $sql;
}


function update_last_ref_2018($new_ref)
{
	$sql = "UPDATE 0_sys_types SET next_reference ='".$new_ref."' WHERE type_id = 0 ";
	db_query($sql, "could not get  $br_code");
	return $sql;
}



if (isset($_POST['fix_now']))
{
		
		//FIX 2017
		display_error('FIX 2017');
		$last_ref_ = get_lastRef_used();

		$last_ref = $last_ref_[0]-1;
		display_error('SQL : '.$last_ref_[1]);
		display_error('LAST OLD 2017 REF: '.$last_ref);

		$br_code = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		$branch_code = get_j_branch_code($br_code);


		$res_type = get_wrong_ref_used();

		while($row = db_fetch($res_type)){
			$last_ref = $last_ref+1;
			$last_ref = $last_ref.'-'.'2017';
			$upd = update_ref($row['type_no'],$last_ref);
			display_error('TRANS NO '.$row['type_no'].' OLD REF : '.$row['reference'].'-----'.'NEW REF :'.$last_ref);
			display_error('UPD : '.$upd);

		}

		//FIX 2018
		display_error('');
		display_error('');
		display_error('');
		display_error('');
		display_error('');
		display_error('');
		display_error('FIX 2018');
		 

		 $last_ref_2018 = 0;
		 $get_2018 = get_lastRef_used(1);
		 $res_2018 = db_query($get_2018[0]);


		 $br_code = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
		 $branch_code = get_j_branch_code($br_code);
		 $year = '2018';
		 display_error($get_2018[0]);
		 $last_2018_ref ='';
		while($rows = db_fetch($res_2018)){
			display_error($rows['type_no']);
			$last_ref_2018 = $last_ref_2018+1;
			$new_ref = $last_ref_2018.'-'.$branch_code.'-'.$year;
			$upd_2018 = update_ref($rows['type_no'],$new_ref);
			display_error('TRANS NO '.$rows['type_no'].' OLD REF : '.$rows['reference'].'-----'.'NEW REF : '.$new_ref);
			display_error('UPD : '.$upd_2018);

			$last_2018_ref = ($last_ref_2018+1).'-'.$branch_code.'-'.$year;;
		}

		display_error('');
		display_error('');

		display_error('LAST REFERENCE FOR 2018: '.$last_2018_ref);
		//update sys_types
		$upd_sys_types = update_last_ref_2018($last_2018_ref);
		display_error('SQL : '.$upd_sys_types);




}


start_form();
start_table($table_style2);
submit_center('fix_now', 'fix');
end_form();

end_page();
?>
