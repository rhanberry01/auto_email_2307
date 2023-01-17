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

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('DELETE history without receiving/movement header. CREATE history for receiving line AND correct date of adjustment and history'  , false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
//-----------------------------------------------------------------------------------

function insert_producthistory($prod_id, $barcode, $last_inserted_recID,$receivingcounter, $date_, $qty, $price)
{
	$date_ = "'". date2sql($date_)."'";
	
	$producthistory = "INSERT INTO [ProductHistory]([ProductID],[Barcode],[TransactionID],[TransactionNo],[DatePosted]
			  ,[TransactionDate],[Description],[BeginningSellingArea],[BeginningStockRoom],[FlowStockRoom],[FlowSellingArea]
			  ,[SellingAreaIn],[SellingAreaOut],[StockRoomIn],[StockRoomOut],[UnitCost],[DamagedIn],[DamagedOut],[LayawayIn]
			  ,[LayawayOut],[OnRequestIn],[OnRequestOut],[PostedBy],[DateDeleted],[DeletedBy],[MovementCode],[TerminalNo]
			  ,[LotNo],[ExpirationDate],[SHAREWITHBRANCH],[CANCELLED],[CANCELLEDBY],[BeginningDamaged],[FlowDamaged])
			  VALUES(".$prod_id.",'".$barcode."',$last_inserted_recID,'$receivingcounter',$date_, 
			  $date_, 'Received', 0, NULL, 2, 2,".($qty).", NULL, NULL, 
			  NULL, ".$price.", NULL,
			  NULL, NULL, NULL, NULL, NULL, '1', NULL, NULL, '_DR', NULL, 0, NULL, 0, 0, '', NULL, NULL)
			  ";
	// echo $producthistory;die;		  
	ms_db_query($producthistory,'failed to insert Product History');
}

function subtract_from_history($date_posted, $prod_id, $diff)
{
	// get 1 history line with qty >= diff
	$sql = "SELECT TOP 1 LineID
					FROM ProductHistory
					WHERE MovementCode = 'AS'
					AND CAST(DatePosted as DATE) = '".date2sql($date_posted)."'
					AND ProductID = $prod_id
					AND SellingAreaOut >= $diff
					ORDER BY SellingAreaOut DESC";
	$res = ms_db_query($sql,'no line id');
	$row = mssql_fetch_array($res);
	$line_id = $row[0];
	
	// subtract difference to that history line 
	$sql = "UPDATE ProductHistory SET SellingAreaOut= SellingAreaOut-$diff WHERE LineID = $line_id";
	ms_db_query($sql,'failed to subtract difference');
	// display_notification($sql);
}

function delete_history_plus_movement($line_id,$tran_id,$tran_no,$movementcode)
{
	$sql = "DELETE FROM MovementLine WHERE MovementID = $tran_id";
	ms_db_query($sql,'failed to DELETE FROM Movements');
	$sql = "DELETE FROM Movements WHERE MovementID = $tran_id AND MovementNo = '$tran_no' AND MovementCode = '$movementcode'";
	ms_db_query($sql,'failed to DELETE FROM MovementLine');
	$sql = "DELETE FROM ProductHistory WHERE LineID=$line_id";
	ms_db_query($sql,'failed to DELETE FROM ProductHistory');
}

function copy_history_date_to_movement($tran_id, $movementcode, $date_)
{
	 $date_ = "'".date('Y-m-d',strtotime($date_))."'";
	
	$sql=  "UPDATE Movements SET 
						DateCreated = $date_ , PostedDate = $date_ , TransactionDate = $date_ 
					WHERE MovementCode = '$movementcode' 
					AND MovementID = $tran_id";
	// echo $sql . '<br>';
	ms_db_query($sql,'failed to subtract difference');
}

if (isset($_POST['fix_now']))
{
	$from_date = $_POST['from'];
	$to_date = $_POST['to'];
	
	// begin_transaction();
	
	// Update NULL Movement Codes (PASA)
	$sql = "UPDATE ProductHistory SET
						MovementCode = 'PASA',
						Description = 'Positive Adjustment(SA)'
					WHERE MovementCode IS NULL
					AND Description = 'PASA'";
	ms_db_query($sql);
	
	// DELETE HISTORY AND MOVEMENT OF ITEM W/ HISTORY AND MOVEMENT HEADER BUT NOT IN DETAILS
	// caused by unfinished processing of RS2SSA
	$sql = "SELECT x.LineID, x.TransactionID,x.TransactionNo, x.MovementCode FROM ProductHistory x
					WHERE CAST(DatePosted as DATE) >= '".date2sql($_POST['from'])."'
					AND  CAST(DatePosted as DATE) <= '".date2sql($_POST['to'])."'
					AND MovementCode IN ('R2SSA','SW','IGSA')
					AND (SELECT COUNT(b.LineID)
								FROM Movements a , MovementLine b
								WHERE a.MovementID = b.MovementID 
								AND a.MovementID = x.TransactionID
								AND b.ProductID = x.ProductID
								AND a.MovementCode = x.MovementCode) = 0";
	$res = ms_db_query($sql);
	while ($row = mssql_fetch_array($res))
		delete_history_plus_movement($row['LineID'],$row['TransactionID'],$row['TransactionNo'],$row['MovementCode']);
	
	
	
	// DELETE HISTORY WITHOUT RECEIVING HEADER
	$sql = "DELETE FROM ProductHistory x
					WHERE CAST(DatePosted as DATE) >= '".date2sql($_POST['from'])."'
					AND CAST(DatePosted as DATE) <= '".date2sql($_POST['to'])."'
					AND MovementCode = '_DR'
					AND Description != 'Received Free'
					AND (SELECT COUNT(b.LineID)
								FROM Movements a , MovementLine b
								WHERE a.MovementID = b.MovementID) = 0";
	$res = ms_db_query($sql);
	
	// CREATE HISTORY FOR RECEIVING WITHOUT IT
	$sql = "SELECT b.ProductID,b.Barcode,b.ReceivingID,a.ReceivingNo, CAST(a.PostedDate as  DATE) as date_ ,  b.totalqtypurchased, round(extended/pack,4) as price
					FROM Receiving a , ReceivingLine b
					WHERE a.ReceivingID = b.ReceivingID
					AND a.PostedDate >= '".date2sql($_POST['from'])."'
					AND a.PostedDate <= '".date2sql($_POST['to'])."'
					AND (SELECT COUNT(x.LineID) FROM ProductHistory x 
								WHERE x.movementcode = '_DR' AND x.TransactionID = b.ReceivingID AND x.ProductID = b.ProductID) = 0";
	// echo $sql;die;
	$res = ms_db_query($sql);
	
	while ($row = mssql_fetch_array($res))
	{
			insert_producthistory($row['ProductID'], $row['Barcode'], $row['ReceivingID'],$row['ReceivingNo'], sql2date($row['date_']), 
				$row['totalqtypurchased']+0, $row['price']+0);
	}
	
	
	// SUBTRACT VOIDED SALES QTY  FROM HISTORY
	$sql = "SELECT b.DatePosted, b.ProductID, a.qty as fs_qty, b.sa_out, b.sa_out - a.qty as diff
					FROM (SELECT LogDate, ProductID, SUM((CASE WHEN [Return] = 0 THEN TotalQty ELSE -TotalQty END)) as qty 
					FROM FinishedSales WHERE Voided = 0 
					AND CAST(LogDate as DATE) >= '".date2sql($_POST['from'])."'
					AND CAST(LogDate as DATE) <= '".date2sql($_POST['to'])."'
					GROUP BY LogDate, ProductID)a, 
					(SELECT DatePosted, ProductID, SUM( ISNULL(SellingAreaOut,0) - ISNULL(SellingAreaIn,0)) as sa_out FROM ProductHistory
					WHERE MovementCode IN ('AS','CS')
					AND CAST(DatePosted as DATE) >= '".date2sql($_POST['from'])."'
					AND CAST(DatePosted as DATE) <= '".date2sql($_POST['to'])."'
					GROUP BY DatePosted,ProductID) b
					WHERE CAST(a.LogDate as DATE) = CAST(b.DatePosted as DATE)
					AND a.ProductID = b.ProductID
					AND a.qty != b.sa_out
					ORDER BY b.DatePosted";
	// echo $sql;die;
	$res = ms_db_query($sql);
	
	while ($row = mssql_fetch_array($res))
	{
			subtract_from_history(mssql2date($row['DatePosted']), $row['ProductID'], $row['diff']);
	}
	
	
	// AUTO CORRECT - Movement and history
	$sql = "SELECT a.TransactionDate as m_date, b.TransactionDate as h_date, a.MovementID,b.MovementCode 
				FROM Movements a , ProductHistory b
				WHERE a.MovementID = b.TransactionID
				AND a.MovementCode = b.MovementCode
				AND a.TransactionDate != b.TransactionDate
				AND a.TransactionDate >= '".date2sql($_POST['from'])."'
				AND a.TransactionDate <= '".date2sql($_POST['to'])."'";
	$res = ms_db_query($sql);
	// echo $sql;
	while ($row = mssql_fetch_array($res))
	{
			copy_history_date_to_movement($row['MovementID'], $row['MovementCode'],$row['h_date']);
	}

	display_notification('DONE');
}

if(!isset($_POST['from']))
	$_POST['from'] = '01/01/2016';

if(!isset($_POST['to']))
	$_POST['to'] = '01/01/2016';

start_form();
start_table($table_style2);
date_row('DATE from:', 'from');
date_row('DATE to:', 'to');
end_table(2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
