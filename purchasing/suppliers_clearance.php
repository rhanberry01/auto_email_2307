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


//--------------------------------------------------------------------------------------------------
function get_branchcode_($br_id)
{
$sql = "SELECT code from transfers.0_branches_other_income where id='".$br_id."'";
//display_error($sql);
$result=db_query($sql);
$row=db_fetch($result);
$br_code=$row['code'];
return $br_code;
}

if (isset($_POST['Process']))
{

	if($_POST['supplier_id'] == ''){
		display_error('Please enter a Supplier.');
		exit;
	}
	if($_POST['remarks'] == ''){
		display_error('Please enter a Remarks.'); 
		exit;
	}
	
	$date = date('Y-m-d');
	$no = 0;
	$sql = "Update ".TB_PREF."counter set counter=counter+1 WHERE code = 'SC'";
	db_query($sql);
	
	if($_POST['reason'] == 1){
		if($_POST['to_supplier_id'] == ''){
			display_error('Please enter a Transfer to Supplier.');
			exit;
		}
		
		$sql_dm = "SELECT
SUM(a.amount) as dm
FROM
(
select st.ov_amount as amount,s.supp_ref from srs_aria_alaminos.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 
 
UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_manalo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_quezon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_b_silang.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_bagumbong.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_blum.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta_san_juan.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_camarin.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_comembo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_gala.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_graceville.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_imus.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon_rest.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_molino.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_navotas.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_nova.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_pateros.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_punturin_val.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_retail.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_san_pedro.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_talon_uno.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_tondo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_valenzuela.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 
) as a";
	$result_dm=db_query($sql_dm);
	$row_dm=db_fetch($result_dm);
	
	$sql_apv = "SELECT
SUM(a.amount) as apv
FROM
(
select st.ov_amount as amount,s.supp_ref from srs_aria_alaminos.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 
 
UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_manalo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_quezon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_b_silang.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_bagumbong.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_blum.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta_san_juan.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_camarin.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_comembo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_gala.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_graceville.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_imus.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon_rest.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_molino.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_navotas.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_nova.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_pateros.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_punturin_val.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_retail.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_san_pedro.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_talon_uno.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_tondo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_valenzuela.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 
) as a";
	$result_apv=db_query($sql_apv);
	$row_apv=db_fetch($result_apv);
	
	$csql = "SELECT Count(*) as nos from transfers.0_branches_other_income  order by name";
	$cresult=db_query($csql);
	$crow=db_fetch($cresult);
	
	$bsql = "SELECT * from transfers.0_branches_other_income order by name";
	$bresult=db_query($bsql);
	//display_error($row_apv['apv'].'>'.-$row_dm['dm']);
	
	$sql_ = "INSERT INTO ".TB_PREF."suppliers_clearance_new (transNo, supplierCode, type, toSupplierCode, effectivityDate, remarks, addedBy, dateAdded) VALUES (".db_escape($_POST['reference']).",".db_escape($_POST['supplier_id']).",".$_POST['reason'].",".db_escape($_POST['to_supplier_id']).", '".date2sql($_POST['date_effective'])."',".db_escape($_POST['remarks']).",".db_escape($_SESSION['wa_current_user']->user).", '".$date."')";
	//display_error($sql_);
		db_query($sql_);
		$insert_id = db_insert_id();
	
	 if($row_apv['apv'] > -$row_dm['dm']){
		while($row = db_fetch($bresult))
		{
			$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."suppliers SET inactive= 1 WHERE supp_ref ='".$_POST['supplier_id']."'";
			//display_error($sql);
			db_query($sql);
		}
		$sql = "UPDATE ".TB_PREF."suppliers_clearance_new SET status= 1 WHERE id =".$insert_id;
		db_query($sql);
	}else{
		
		while($row = db_fetch($bresult))
		{
			if(!ping($row['rs_host'])){
				display_error('no connection'.$row['name']);
			}else{
				$conn = mysql_connect($row['rs_host'], $row['rs_user'], $row['rs_pass']);
				mysql_select_db($row['rs_db'], $conn);
				$sql = "INSERT INTO ".TB_PREF."suppliers (TransNo, type, supplierCode, toSupplierCode, effectivityDate, dateAdded)
				VALUES ('".$_POST['reference']."',".$_POST['reason'].",".db_escape($_POST['supplier_id']).",".db_escape($_POST['to_supplier_id']).", '".date2sql($_POST['date_effective'])."','".$date."')";
				
				$query = mysql_query($sql, $conn);
		
				$sql_1 = "INSERT INTO ".TB_PREF."suppliers_clearance_new_branch (headerID, branch)
				VALUES (".$insert_id.",".db_escape($row['code']).")";
				db_query($sql_1);
				$no++;
			}
		}
		if($no == $crow['nos']){
			$sql = "UPDATE ".TB_PREF."suppliers_clearance_new SET status= 1 WHERE id =".$insert_id;
			db_query($sql);
		}
		$bsql = "SELECT * from transfers.0_branches_other_income where id IN(1,2,4) order by name";
		$bresult=db_query($bsql);
		
		while($row = db_fetch($bresult)){
				$cv_id = array();
				
				$s_sql = "SELECT  DISTINCT st.cv_id FROM ".$row['aria_db'].".".TB_PREF."supp_trans as st inner join ".$row['aria_db'].".".TB_PREF."suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."' where type IN(20,53)";
				$s_result = db_query($s_sql);
				$s_row_ = db_fetch($s_result);
				//display_error($s_sql);
				
				if($s_row_){
					 while($s_row = db_fetch($s_result))
					{
						array_push($cv_id, $s_row['cv_id']);
					}
						$cv_id = implode(',',$cv_id);
						
						$sql = "DELETE gl from ".$row['aria_db'].".".TB_PREF."gl_trans as gl INNER JOIN ".$row['aria_db'].".".TB_PREF."cv_details as cd ON gl.type = cd.trans_type AND gl.type_no = cd.trans_no WHERE cv_id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."gl_trans_temp as glt INNER JOIN ".$row['aria_db'].".".TB_PREF."cv_details as cd ON glt.type = cd.trans_type AND glt.type_no = cd.trans_no SET glt.posted = 0 WHERE cv_id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."cv_details SET voided = 1 WHERE cv_id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."cv_header SET amount=0 WHERE id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."supp_trans as st inner join ".$row['aria_db'].".".TB_PREF."suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."' SET st.cv_id = 0 where type IN(20,53)";
						db_query($sql);
						//display_error($sql);
				}
			}
		} 
	
	}else{
	$sql_dm = "SELECT
SUM(a.amount) as dm
FROM
(
select st.ov_amount as amount,s.supp_ref from srs_aria_alaminos.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 
 
UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_manalo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_quezon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_b_silang.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_bagumbong.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_blum.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta_san_juan.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_camarin.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_comembo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_gala.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_graceville.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_imus.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon_rest.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_molino.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_navotas.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_nova.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_pateros.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_punturin_val.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_retail.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_san_pedro.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_talon_uno.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_tondo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_valenzuela.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='53' 
) as a";
	$result_dm=db_query($sql_dm);
	$row_dm=db_fetch($result_dm);
	
	$sql_apv = "SELECT
SUM(a.amount) as apv
FROM
(
select st.ov_amount as amount,s.supp_ref from srs_aria_alaminos.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 
 
UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_manalo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_antipolo_quezon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_b_silang.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_bagumbong.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_blum.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_cainta_san_juan.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_camarin.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_comembo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_gala.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_graceville.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_imus.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_malabon_rest.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_molino.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_navotas.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_nova.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_pateros.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_punturin_val.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_retail.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_san_pedro.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_talon_uno.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_tondo.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 

UNION ALL
select st.ov_amount as amount,s.supp_ref from srs_aria_valenzuela.0_supp_trans as st inner join 
0_suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."'
 where  type ='20' 
) as a";
	$result_apv=db_query($sql_apv);
	$row_apv=db_fetch($result_apv);
	
	$csql = "SELECT Count(*) as nos from transfers.0_branches_other_income order by name";
	$cresult=db_query($csql);
	$crow=db_fetch($cresult);
	
	$bsql = "SELECT * from transfers.0_branches_other_income order by name";
	$bresult=db_query($bsql);
	//display_error($row_apv['apv'].'>'.-$row_dm['dm']);
	
	$sql_ = "INSERT INTO ".TB_PREF."suppliers_clearance_new (transNo, supplierCode, type,  remarks, addedBy, dateAdded) VALUES (".db_escape($_POST['reference']).",".db_escape($_POST['supplier_id']).",".$_POST['reason'].",".db_escape($_POST['remarks']).",".db_escape($_SESSION['wa_current_user']->user).", '".$date."')";
		db_query($sql_);
		$insert_id = db_insert_id();
	
	 if($row_apv['apv'] > -$row_dm['dm']){
		while($row = db_fetch($bresult))
		{
			$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."suppliers SET inactive= 1 WHERE supp_ref ='".$_POST['supplier_id']."'";
			//display_error($sql);
			db_query($sql);
		}
		$sql = "UPDATE ".TB_PREF."suppliers_clearance_new SET status= 1 WHERE id =".$insert_id;
		db_query($sql);
	}else{
		
		while($row = db_fetch($bresult))
		{
			if(!ping($row['rs_host'])){
				display_error('no connection'.$row['name']);
			}else{
				$conn = mysql_connect($row['rs_host'], $row['rs_user'], $row['rs_pass']);
				mysql_select_db($row['rs_db'], $conn);
				$sql = "INSERT INTO ".TB_PREF."suppliers (TransNo, type, supplierCode, dateAdded)
				VALUES ('".$_POST['reference']."',".$_POST['reason'].",".db_escape($_POST['supplier_id']).",'".$date."')";
				
				$query = mysql_query($sql, $conn);
		
				$sql_1 = "INSERT INTO ".TB_PREF."suppliers_clearance_new_branch (headerID, branch)
				VALUES (".$insert_id.",".db_escape($row['code']).")";
				db_query($sql_1);
				$no++;
			}
		}
		if($no == $crow['nos']){
			$sql = "UPDATE ".TB_PREF."suppliers_clearance_new SET status= 1 WHERE id =".$insert_id;
			db_query($sql);
		}
		$bsql = "SELECT * from transfers.0_branches_other_income where id IN(1,2,4) order by name";
		$bresult=db_query($bsql);
		
		while($row = db_fetch($bresult)){
				$cv_id = array();
				
				$s_sql = "SELECT  DISTINCT st.cv_id FROM ".$row['aria_db'].".".TB_PREF."supp_trans as st inner join ".$row['aria_db'].".".TB_PREF."suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."' where type IN(20,53)";
				$s_result = db_query($s_sql);
				$s_row_ = db_fetch($s_result);
				//display_error($s_sql);
				
				if($s_row_){
					 while($s_row = db_fetch($s_result))
					{
						array_push($cv_id, $s_row['cv_id']);
					}
						$cv_id = implode(',',$cv_id);
						
						$sql = "DELETE gl from ".$row['aria_db'].".".TB_PREF."gl_trans as gl INNER JOIN ".$row['aria_db'].".".TB_PREF."cv_details as cd ON gl.type = cd.trans_type AND gl.type_no = cd.trans_no WHERE cv_id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."gl_trans_temp as glt INNER JOIN ".$row['aria_db'].".".TB_PREF."cv_details as cd ON glt.type = cd.trans_type AND glt.type_no = cd.trans_no SET glt.posted = 0 WHERE cv_id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."cv_details SET voided = 1 WHERE cv_id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."cv_header SET amount=0 WHERE id IN(".$cv_id.")";
						db_query($sql);
						//display_error($sql);
						$sql = "UPDATE ".$row['aria_db'].".".TB_PREF."supp_trans as st inner join ".$row['aria_db'].".".TB_PREF."suppliers as s on s.supplier_id = st.supplier_id and s.supp_ref = '".$_POST['supplier_id']."' SET st.cv_id = 0 where type IN(20,53)";
						db_query($sql);
						//display_error($sql);
				}
			}
		} 
		
	}
	
	//display_error($_POST['reference'].'---'.$_POST['supplier_id'].'---'.$_POST['reason'].'---'.$_POST['remarks']);
	//exit;
}
page('Supplier Clearance', false, false,'', $js);

start_form();
$br_code = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];

if($br_code != 'srsn'){

	display_error('== PLEASE LOGIN TO NOVA ==');

}else{
	//display_error($_SESSION['wa_current_user']->user);
	global $Ajax;
	$Ajax->activate('main');
	div_start('main');
	start_table("$table_style2");

	$sql = "SELECT counter FROM ".TB_PREF."counter
	WHERE code = 'SC'";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	
	$ref = str_pad($row['counter'], 5, '000000', STR_PAD_LEFT);
	hidden('reference',$ref);
	label_cells('<font color=red>*Trans #: </font>',$ref);
	
	//if($_SESSION['wa_current_user']->user == 888  OR $_SESSION['wa_current_user']->user == 633  OR $_SESSION['wa_current_user']->user == 730 OR $_SESSION['wa_current_user']->user == 642 OR $_SESSION['wa_current_user']->user == 651 OR $_SESSION['wa_current_user']->user == 886){
		supplier_list_ms_row('Database Supplier (<i>if not in ARIA</i>):', 'supplier_id', null, 'Use Supplier below');
		//supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
	//}else{
	//	purchaser_supplier_list_ms_row('Database Supplier (<i>if not in ARIA</i>):', 'supplier_id', null, 'Use Supplier below');
		//purchaser_supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above')
	//}
	
	yesno_list_row('<font color=red>*Reason for Clearance: </font>', 'reason', null, "Change Of Supplier (Traanfer of Account)", "Supplier Clearance (Pull Out)",true,true);
	if ($_POST['reason'] == 1) {
		supplier_list_ms_row('To Supplier (<i>if not in ARIA</i>):', 'to_supplier_id', null, 'Use Supplier below');
		date_row(_("Date Effective:"), 'date_effective', '', true, 0, 0, 0, null, true);
	}
	textarea_row('Remarks: ', 'remarks', '', 35, 3);
	end_table(1);
	div_end();
	submit_center('Process', _("<b>Submit</b>"), true , '', 'default');
	end_form();

}



//------------------------------------------------------------------------------------------------

end_page();
?>
