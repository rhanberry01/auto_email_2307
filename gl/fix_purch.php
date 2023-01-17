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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Fix Other Income Date Deposit"), false, false, "", $js);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	//$cvid = $_GET['CV_id'];
	//$trans_type = ST_CREDITDEBITDEPOSIT;
   	display_notification_centered( _("Other Income has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	display_footer_exit();
}
// $and1 = " AND tax_group_id = 1 AND TRIM(gst_no) != ''"; //vat
// $and2 = " AND tax_group_id != 1 AND TRIM(gst_no) != ''"; //nv
// $and3 = " AND TRIM(gst_no) = ''"; //dr

$and1 = " AND  account IN ('1410','1410010','1410011','1410012','1410013') "; //vat
$and2 = " AND  account NOT IN ('1410','1410010','1410011','1410012','1410013') "; //nv
$and3 = " AND TRIM(gst_no) = ''"; //dr

//----------------------------------------------------------------------------------------

function compare_from_tb_to_pr($type, $type_no, $account)
	{
		//type = $type
		$sql = "SELECT DISTINCT c.type, c.trans_no, b.gst_no, b.supp_name, b.supplier_id,
						a.tran_date, c.del_date, c.supp_reference, c.ov_amount + c.ov_gst as total, b.tax_group_id
						FROM 0_gl_trans a, 0_suppliers b, 0_supp_trans c
						WHERE c.trans_no='$type_no'
						AND c.type='$type'
						AND c.tran_date >= '2016-01-01'
						AND c.tran_date <= '2016-12-31'
						AND (account = '5400')
						AND a.amount != 0
						AND ((a.type = 20 AND c.type = 20) OR (a.type = 24 AND c.type = 24) )
						AND a.person_id = b.supplier_id
						AND a.type_no = c.trans_no
						AND a.type = c.type
						ORDER BY supp_name,gst_no, c.del_date";
						
						//display_error($sql);
							
		$res = db_query($sql,'error.');

		$row = db_fetch($res);
		
		if ($row['trans_no']==''){
			display_error($sql);
			return array($type, $type_no);
			}
		
	//	return $row['type_no'];
	}


function compare_from_pr_to_tb($type, $type_no, $account)
	{
		//type = $type
		$sql = "SELECT * FROM `0_gl_trans`
					WHERE account = ". db_escape($account)."
					 AND type_no='$type_no'
					 AND type='$type'
					AND tran_date >= '2016-01-01'
					AND tran_date <= '2016-12-31'";
		//display_error($sql);
		
		$res = db_query($sql);
		
		$row = db_fetch($res);
		
		if ($row['type_no']==''){
			//display_error($sql);
			return array($type, $type_no);
			}
		
	//	return $row['type_no'];
	}


function get_gl_trans_t($type, $type_no, $account)
	{
		
		//type = $type
		$sql = "SELECT sum(amount) FROM `0_gl_trans`
					WHERE 
					 account = ". db_escape($account)."
					AND tran_date >= '2016-01-01'
					AND tran_date <= '2016-12-31'";
		display_error($sql);
		$res = db_query($sql);
		$row = db_fetch($res);
		
		return round($row[0],2);
	}
	
	function get_gl_trans_t20($type, $type_no, $account)
	{
		
		//type = $type
		$sql = "SELECT sum(amount) FROM `0_gl_trans`
					WHERE 	type = 20
					 AND account = ". db_escape($account)."
					AND tran_date >= '2016-01-01'
					AND tran_date <= '2016-12-31'
					
					";
		display_error($sql);
		$res = db_query($sql);
		$row = db_fetch($res);
		
		return round($row[0],2);
	}
	
	function get_gl_trans_t24($type, $type_no, $account)
	{
		
		//type = $type
		$sql = "SELECT sum(amount) FROM `0_gl_trans`
					WHERE 	type = 24
					 AND account = ". db_escape($account)."
					AND tran_date >= '2016-01-01'
					AND tran_date <= '2016-12-31'
					
					";
		display_error($sql);
		$res = db_query($sql);
		$row = db_fetch($res);
		
		return round($row[0],2);
	}
	
		function get_gl_trans_t0($type, $type_no, $account)
	{
		
		//type = $type
		$sql = "SELECT sum(amount) FROM `0_gl_trans`
					WHERE 	type = 0
					 AND account = ". db_escape($account)."
					AND tran_date >= '2016-01-01'
					AND tran_date <= '2016-12-31'
					
					";
		display_error($sql);
		$res = db_query($sql);
		$row = db_fetch($res);
		
		return round($row[0],2);
	}
	

	
	
	//AND (account = '5400' OR account = '5450' OR account = '1410010')

//AND (account = '5400' OR account = '5450')
start_form();
		$sql = "SELECT DISTINCT c.type, c.trans_no, b.gst_no, b.supp_name, b.supplier_id,
						a.tran_date, c.del_date, c.supp_reference, c.ov_amount + c.ov_gst as total, b.tax_group_id
						FROM 0_gl_trans a, 0_suppliers b, 0_supp_trans c
						WHERE c.tran_date >= '2016-01-01'
						AND c.tran_date <= '2016-12-31'
						AND (account = '5400')
						AND a.amount != 0
						AND c.ov_amount != 0
						AND ((a.type = 20 AND c.type = 20) OR (a.type = 24 AND c.type = 24) )
						AND a.person_id = b.supplier_id
						AND a.type_no = c.trans_no
						AND a.type = c.type
						".$and."
						ORDER BY supp_name,gst_no, c.del_date";
							
		$res = db_query($sql,'error.');
		display_error($sql);
		
			$company_pref = get_company_prefs();
		
		while ($row = db_fetch($res))
		{
			
				// $vat1 += get_gl_trans_amount($row['type'], $row['trans_no'], '1410');
				// $vat2 += get_gl_trans_amount($row['type'], $row['trans_no'], '1410010');
				// $vat3 += get_gl_trans_amount($row['type'], $row['trans_no'], '1410011');
				// $vat4 += get_gl_trans_amount($row['type'], $row['trans_no'], '1410012');
				// $vat5 += get_gl_trans_amount($row['type'], $row['trans_no'], '1410013');
				
				$vat6 += get_gl_trans_amount($row['type'], $row['trans_no'], '5400');
				$vat7 += get_gl_trans_amount($row['type'], $row['trans_no'], '5450');
				
			$p_nv += get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_non_vat"]);
			$p_v += get_gl_trans_amount($row['type'], $row['trans_no'], $company_pref["purchase_vat"]);
			
			$trans_no=compare_from_pr_to_tb($row['type'], $row['trans_no'], 5400);
			
			
			if ($trans_no!=''){
			display_error("Transaction: ".$trans_no[1]." Type: ".$trans_no[0]." is not included in trial balance or probably not in 0_gl_trans.");	
			}

		}
		
		
		
		$sqlo = "SELECT * FROM `0_gl_trans`
		WHERE account = '5400'
		AND tran_date >= '2016-01-01'
		AND tran_date <= '2016-12-31'
		AND amount!=0;
		";
		//display_error($sql);
		$reso = db_query($sqlo);

		while ($row = db_fetch($reso))
		{
			$trans_no=compare_from_tb_to_pr($row['type'], $row['type_no'], 5400);
			if ($trans_no!=''){
			display_error("Transaction: ".$trans_no[1]." Type: ".$trans_no[0]." is not included in purchase report or probably not in 0_supp_trans.");	
			}

		}
		
		

		
		$p = get_gl_trans_t(0, 0, 5400);
		
		$p0 = get_gl_trans_t0(0, 0, 5400);
		$p20 = get_gl_trans_t20(20, 0, 5400);
		$p24 = get_gl_trans_t24(24, 0, 5400);
		
			 display_error("GL TOTAL: ".$p);
			 display_error("GL TOTAL 0: ".$p0);
			 display_error("GL TOTAL 20: ".$p20);
			 display_error("GL TOTAL 24: ".$p24);
			// display_error($vat2);
			// display_error($vat3);
			// display_error($vat4);
			// display_error($vat5);
			display_error("PURCH NV: ".$vat6);
		//	display_error("PURCH V: ".$vat7);			
			
			display_error("PURCH NV2: ".$p_nv);
			//display_error("PURCH V2: ".$p_v);
		
		

end_form();
end_page();
?>