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

	
page('Import Sales to ARIA', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function get_nv_sales($date_)
{
	
	$sql = "SELECT SUM(Extended) FROM [dbo].[FinishedSales] 
			WHERE LogDate = '$date_'
			AND Voided = 0  -- AND Extended !< 0
			AND ProductID IN (SELECT ProductID FROM [dbo].[Products] WHERE pVatable = 0)
			and pVatable != 2 ";	

	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	
	return $row[0];
	
}

function get_zr_sales($date_)
{
	$sql = "SELECT SUM(Extended) FROM [dbo].[FinishedSales] 
				WHERE LogDate = '$date_'
				AND Voided = 0 -- AND Extended !< 0
				AND pVatable = 2";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
	
}

if (isset($_POST['fix_now']))
{
	
	global $Refs;
	
	$sql = "SELECT cast (LogDate as Date) as LogDate ,(SUM(ft.GrandTotal)-SUM(ft.ReturnSubtotal)) as total
	FROM FinishedTransaction as ft
	WHERE LogDate >= '2018-05-10' and LogDate<= '2018-05-10'
	AND Voided='0'	
	group by LogDate
	order by LogDate";
	display_error($sql);
	
	$res = ms_db_query($sql);
	         
	while($row = mssql_fetch_array($res))
	{
		$logdate = $row[0];
		$amount = $row[1];
		
		// $net_of_vat_total = get_net_of_vat_total($m_id);
		$gross = $amount;
		$date_ = $logdate;

 				
		## DEBIT ##
				$sql_collection_d = "SELECT SUM(ABS(amount))+0 FROM  0_gl_trans where tran_date = '".$logdate."' and type ='100' and account =1060000 ";
 				$collection_rows_d = db_fetch(db_query($sql_collection_d));
		## DEBIT ##

		## CREDIT ##
 				$sql_collection_c = "SELECT SUM(ABS(amount))+0 FROM  0_gl_trans where tran_date = '".$logdate."' and type ='100' and account IN(4000040,4000020,4000050,4000,2310) ";
 				$collection_rows_c = db_fetch(db_query($sql_collection_c));
 				
		## CREDIT ##

 		## NON VAT ##
				$sql_collection_nvT = "SELECT SUM(ABS(amount))+0 FROM  0_gl_trans where tran_date = '".$logdate."' and type ='100' and account =4000020 ";
 				$collection_rows_nvat = db_fetch(db_query($sql_collection_nvT));
 				$nv_sales = round(get_nv_sales($date_),2);
		## NON VAT ##

 		## ZERO VAT ##
				$sql_collection_zvT = "SELECT SUM(ABS(amount))+0 FROM  0_gl_trans where tran_date = '".$logdate."' and type ='100' and account =4000050 ";
 				$collection_rows_zvat = db_fetch(db_query($sql_collection_zvT));
 				$zv_sales = round(get_zr_sales($date_),2);
		## ZERO VAT ##


		if(!(round($amount,2) == round($collection_rows_d[0],2) && round($amount,2) == round($collection_rows_c[0],2) && round($collection_rows_nvat[0],2) == round($nv_sales,2) && round($collection_rows_zvat[0],2) == round($zv_sales,2) )){

			$sql_check_sales_ = "SELECT type_no FROM  0_gl_trans where tran_date = '".$logdate."' and type ='100'";
			$ress_ = db_query($sql_check_sales_);
			$rows_ = db_fetch($ress_);

			$delete_sales = " DELETE FROM 0_gl_trans where tran_date = '".$logdate."' and type ='100' and type_no ='".$rows_[0]."' "; 
			db_query($delete_sales);

			$sql_suki = "select sum(amount) as sukipoints  from FinishedPayments where tendercode ='004'and voided=0 and LogDate='$date_'";
					display_error($sql_suki);
					$res2 = ms_db_query($sql_suki);
					$row2 = mssql_fetch_array($res2);
					
					$suki =$row2['sukipoints'];
					
					$net_sales = $gross - $suki;
					display_error("GROSS: ".$gross);
					display_error("SUKI: ".$suki);
					display_error("NET SALES: ".$net_sales);
					
					$nv_sales = round(get_nv_sales($date_),2);
					$zr_sales = round(get_zr_sales($date_),2);
					display_error("NV SALES: ".$nv_sales);
					display_error("ZR SALES: ".$zr_sales);
					$gross_vatable = $net_sales - $nv_sales-$zr_sales;
					display_error("GROSS VATABLE: ".$gross_vatable);
					$sales_vat = round($gross_vatable/1.12,2);
					$output_vat = $gross_vatable - $sales_vat;
					display_error("SALES VAT: ".$sales_vat);
					display_error("OUTPUT VAT: ".$output_vat);
					display_error("ZERO VAT = ".round($collection_rows_zvat[0],2).'=='.round($zv_sales,2).'=>'.round($collection_rows_zvat[0],2)-round($zv_sales,2));
					
					
					$ref   = $Refs->get_next(ST_SALES);
					$memo_ = "Sales";
					
					$trans_type = ST_SALES;

					$trans_id = get_next_trans_no($trans_type);
					

					add_gl_trans(ST_SALES, $trans_id, sql2date($date_), 1060000, 0, 0, $memo_= '', $gross);

					if ($suki != 0){
						add_gl_trans(ST_SALES, $trans_id, sql2date( $date_), 4000040, 0, 0, $memo_= '',-$suki);
					}

					if ($nv_sales != 0){
						add_gl_trans(ST_SALES, $trans_id, sql2date($date_), 4000020, 0, 0, $memo_= '',-$nv_sales);
					}
					
					if ($zr_sales != 0){
						add_gl_trans(ST_SALES, $trans_id, sql2date($date_), 4000050, 0, 0, $memo_= '',-$zr_sales);
					}
					
					add_gl_trans(ST_SALES, $trans_id, sql2date($date_), 4000, 0, 0, $memo_= '',-$sales_vat);
					add_gl_trans(ST_SALES, $trans_id, sql2date($date_), 2310, 0, 0, $memo_= '',-$output_vat);
					
					
					if($memo_ != '')
					{
						add_comments($trans_type, $trans_id, sql2date($date_), $memo_);
					}
					
					$Refs->save($trans_type, $trans_id, $ref);
					
					add_audit_trail($trans_type, $trans_id, sql2date($date_));

					display_error("NOT EQUAL-".$date_."FIXED");	
					display_error(round($amount,2)."=".round($collection_rows_d[0],2)."=".round($collection_rows_c[0],2));
					display_error(round($nv_sales,0)."=".round($collection_rows_nvat[0],2));	
			
		}else{
			display_error("EQUAL-".$date_);	
			display_error(round($amount,2)."=".round($collection_rows_d[0],2)."=".round($collection_rows_c[0],2));	
			display_error(round($nv_sales,2)."=".round($collection_rows_nvat[0],2));
			display_error("ZERO VAT".round($collection_rows_zvat[0],2).'=='.round($zv_sales,2).'=>'.round($collection_rows_zvat[0],2)-round($zv_sales,2));	
		}

		
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'Import');
end_form();

end_page();
?>
