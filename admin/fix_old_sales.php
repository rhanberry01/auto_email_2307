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

function adjust_gl_entry($m_id, $m_no, $nettotal)
{
	begin_transaction();
	$sql = "SELECT id FROM transfers.0_transfer_header WHERE m_id_out = $m_id";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	
	if ($trans_no == '')
	{
		display_notification('HEADER NOT UPDATED for transfer out movement id # '. $m_id .' movement # '. $m_no);
		return true;
	}
	
	$sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 100 AND type_no = $trans_no AND account = '570002'";

	db_query($sql, 'failed to update gl_trans (positive)'. 'for m_id : '.$m_id );
	
	$sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 100 AND type_no = $trans_no AND account != '570002'";

	db_query($sql, 'failed to update gl_trans (negative)'. 'for m_id : '.$m_id );
	
	commit_transaction();
}

function get_nv_sales($date_)
{
	$sql = "SELECT SUM(Extended) FROM [dbo].[FinishedSales] 
			WHERE LogDate = '$date_'
			AND Voided = 0 -- AND Extended !< 0
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
	
	$res = ms_db_query($sql);

	
	while($row = mssql_fetch_array($res))
	{
	
		$logdate = $row[0];
		$amount = $row[1];
		

		$gross = $amount;
		$date_ = $logdate;
		
/*		$sql_collection_d = "SELECT SUM(ABS(amount))+0 FROM  0_gl_trans where tran_date = '".$logdate."' and type ='60' and account =1060000";
 	    $collection_rows_d = db_fetch(db_query($sql_collection_d));*/

		$sql_sales = "DELETE FROM 0_gl_trans where type = '60' and tran_date between '".$logdate."' and '".$logdate."'
					  and account in('4000040','4000020','4000','2310','4000050')";	
		$res_sales = db_query($sql_sales);

		$get_type = "Select type_no from 0_gl_trans where tran_date between '".$logdate."' and '".$logdate."' and type ='60' and amount != 0 limit 1  ";
		$res_type = db_query($get_type);
		$res_ = db_fetch($res_type);
		$old_type_no = $res_[0];

		$salest_type = "Select account from 0_gl_trans where tran_date between '".$logdate."' and '".$logdate."' and type ='60' and account ='1060000' and amount != 0 limit 1  ";
		$salest_res_type = db_query($salest_type);
		$salest_res_ = db_fetch($salest_res_type);
		$salest_old = $salest_res_[0];

		if(!$salest_old){

			$sql_insert = "INSERT INTO 0_gl_trans (type, type_no, tran_date,account,amount,memo_) VALUES ('60', ".$old_type_no.", '".$logdate."','1060000',".-$amount.",'')";
			//display_error($sql_insert);
			db_query($sql_insert);

		}else{
			## IF EXISTED
			$sql_sales = "DELETE FROM 0_gl_trans where type = '60' and tran_date between '".$logdate."' and '".$logdate."'
					  and account in('1060000') and type_no =".$old_type_no."";	
			$res_sales = db_query($sql_sales);
			//display_error($sql_sales);

			$sql_insert = "INSERT INTO 0_gl_trans (type, type_no, tran_date,account,amount,memo_) VALUES ('60', ".$old_type_no.", '".$logdate."','1060000',".-$amount.",'')";
			db_query($sql_insert);
			//display_error($sql_insert);

		}

		

		display_error("DATE: ".$logdate);
		display_error("AMOUNT: ".-$amount);

		
	}
	
	display_notification('NO CONNECTION');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'Fix old sales');
end_form();

end_page();
?>
