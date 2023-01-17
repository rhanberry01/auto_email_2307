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

	
page('Stock Transfer IN - MS Movement to ARIA (Net of VAT per Item)', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function adjust_gl_entry_double()
{
	$sql = "SELECT type, type_no, COUNT(*) as count
	FROM 0_gl_trans
	where type=71
	and account='570001'
	and tran_date>='2017-01-01'
	GROUP BY type_no
	HAVING COUNT(*) > 1";
		
	$result1 = db_query($sql);
	
	while($row1 = db_fetch($result1))
	{
			$type = $row1[0];
			$trans_no = $row1[1];
			
			$sqlx = "SELECT account,tran_date FROM 0_gl_trans WHERE type = 71 AND type_no = $trans_no AND account!='570001'";
			$resx= db_query($sqlx);
			$rowx= db_fetch($resx);
			
			$gl_from = $rowx['account'];
			$tran_date = sql2date($rowx['tran_date']);
			
			
			$sql = "DELETE FROM 0_gl_trans WHERE type = 71 AND type_no = $trans_no and tran_date>='2017-01-01'";
			db_query($sql,'failed to delete wrong transfer in GL');
			
			add_gl_trans(71, $trans_no, $tran_date, $gl_from, 0, 0, $memo_, 0);
			add_gl_trans(71, $trans_no, $tran_date, '570001', 0, 0, $memo_,  0);
			
			display_notification('Sucessful deleting double transaction # '. $trans_no);
	}
}

function adjust_gl_entry_not_in_transfer_header($branch)
{
	$sql = "SELECT type, type_no FROM `0_gl_trans`
	where type=71
	and tran_date>='2017-01-01'
	and tran_date<='2017-12-31'
	and account='570001'
	and amount!=0
	";
		
	$result1 = db_query($sql);
	
	while($row1 = db_fetch($result1))
	{
			$type = $row1[0];
			$trans_no = $row1[1];
			
			$sqlx = "SELECT aria_trans_no_in FROM transfers.0_transfer_header 
			WHERE aria_trans_no_in='$trans_no' AND br_code_in = '$branch' AND transfer_in_date>='2017-01-01' and transfer_in_date<='2017-12-31'";
			$resx= db_query($sqlx);
			$rowx= db_fetch($resx);
			//display_error($sqlx);
			
			
			$aria_trans_no_in = $rowx['aria_trans_no_in'];
			
			if($aria_trans_no_in==''){
				// display_error($trans_no);
				// display_error($aria_trans_no_in);
				
				$sql = "UPDATE 0_gl_trans SET amount = 0 WHERE type = 71 AND type_no = $trans_no";
				db_query($sql,'failed to update transfer in GL');
				display_notification('Sucessful updating not exist in header # '. $trans_no);
			}
			
			// $sql = "DELETE FROM 0_gl_trans WHERE type = 71 AND type_no = $trans_no";
			// db_query($sql,'failed to delete wrong transfer in GL');
			
			// add_gl_trans(71, $trans_no, $tran_date, $gl_from, 0, 0, $memo_, 0);
			// add_gl_trans(71, $trans_no, $tran_date, '570001', 0, 0, $memo_,  0);

	}
}


function adjust_gl_entry_not_in_ms($branch)
{
	$sql = "SELECT type, type_no FROM `0_gl_trans`
	where type=71
	and tran_date>='2017-01-01'
	and tran_date<='2017-12-31'
	and account='570001'
	and amount!=0
	";
	$result1 = db_query($sql);
	
	while($row1 = db_fetch($result1))
	{
			$type = $row1[0];
			$trans_no = $row1[1];
			
			$sqlx = "SELECT m_id_in FROM transfers.0_transfer_header 
			WHERE aria_trans_no_in='$trans_no' AND br_code_in = '$branch' AND transfer_in_date>='2017-01-01' and transfer_in_date<='2017-12-31'";
			$resx= db_query($sqlx);
			$rowx= db_fetch($resx);
			$m_id_in = $rowx['m_id_in'];
			//display_error($sqlx);
			
			$sqlr = "
			SELECT 
			MovementLine.MovementID as MovementID
			from MovementLine inner join Movements
			on MovementLine.MovementID = Movements.MovementID inner join
			Products on Products.ProductID = MovementLine.ProductID
			inner join MovementTypes on Movements.MovementCode = MovementTypes.MovementCode
			where (CAST (Movements.TransactionDate  as DATE) >=  '2017-01-01' 
			and  CAST (Movements.TransactionDate  as DATE)<='2017-12-31') 
			and  Movements.status = 2
			and Movements.MovementCode='STI' 
			and Movements.MovementID IN ($m_id_in)
			group by MovementLine.MovementID
			";
			$resr = ms_db_query($sqlr);
			$num=mssql_num_rows($resr);
			
			//display_error($num);

				if($num==0 or is_null($num)){
					// display_error($trans_no);
					// display_error($m_id_in);
					
					$sql = "UPDATE 0_gl_trans SET amount = 0 WHERE type = 71 AND type_no = $trans_no";
					db_query($sql,'failed to update transfer in GL');
					
					display_notification('Sucessful zero not exist in trans_no # '. $trans_no);
				}
			
			// $sql = "DELETE FROM 0_gl_trans WHERE type = 71 AND type_no = $trans_no";
			// db_query($sql,'failed to delete wrong transfer in GL');
			
			// add_gl_trans(71, $trans_no, $tran_date, $gl_from, 0, 0, $memo_, 0);
			// add_gl_trans(71, $trans_no, $tran_date, '570001', 0, 0, $memo_,  0);

	}
	
}

function adjust_gl_entry($m_id, $m_no, $nettotal,$branch)
{
	
	$sql = "SELECT id FROM transfers.0_transfer_header WHERE m_id_in = $m_id AND br_code_in = '$branch' 
	AND transfer_in_date>='2017-01-01' and m_id_in NOT LIKE '%,%'";
	
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	
	if ($trans_no == '')
	{
		$status=0;
		
		return $status;
	}
	else {
		$status=1;
		
		begin_transaction();
		$sql = "UPDATE 0_gl_trans SET amount = $nettotal WHERE type = 71 AND type_no = $trans_no AND account = '570001'";
		db_query($sql, 'failed to update gl_trans (positive)'. 'for m_id : '.$m_id );
		$sql = "UPDATE 0_gl_trans SET amount = -$nettotal WHERE type = 71 AND type_no = $trans_no AND account != '570001'";
		db_query($sql, 'failed to update gl_trans (negative)'. 'for m_id : '.$m_id );
		commit_transaction();
		
		display_notification('Sucessful 1st Stage for transfer in movement id # '. $m_id .' movement # '. $m_no);
		return $status;
	}

}

function adjust_gl_entry_partial($m_id, $m_no, $nettotal,$branch)
{
	$sql = "SELECT id,br_code_out,DATE(transfer_in_date) as transfer_in_date,m_id_in,m_no_in FROM transfers.0_transfer_header 
	WHERE m_id_in LIKE '%$m_id%' AND br_code_in = '$branch' 
	AND (transfer_in_date>='2017-01-01' and transfer_in_date<='2017-12-31')
	";
	//display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row[0];
	$branch_out = $row[1];
	$tran_date = sql2date($row[2]);
	$m_id = $row[3];
	$m_no = $row[4];
	
	if ($trans_no == '')
	{
		$status=0;
		
		return $status;
	}
	else{
		$status=1;
		
		
		begin_transaction();
		$nettotal = get_net_of_vat_total($m_id,$branch);
		
		$sql1 = "SELECT b.gl_stock_from FROM transfers.0_branches b
					WHERE b.code= '$branch_out'";

		$res1= db_query($sql1);
		$row1 = db_fetch($res1);
		
		$gl_from = $row1['gl_stock_from'];
		$memo_ = "Transfer,  MovementID#: ".$m_id;
		
		$sql = "DELETE FROM 0_gl_trans WHERE type = 71 AND type_no = $trans_no and tran_date>='2017-01-01' and tran_date<='2017-12-31'";
		db_query($sql,'failed to delete wrong transfer in GL');
		
		add_gl_trans(71, $trans_no, $tran_date, $gl_from, 0, 0, $memo_, -$nettotal);
		add_gl_trans(71, $trans_no, $tran_date, '570001', 0, 0, $memo_, $nettotal);
		commit_transaction();
		
		display_notification('Sucessful Partial for transfer in movement id # '. $m_id .' movement # '. $m_no);
		
		return $status;
		
	}
}

function adjust_gl_entry_journal($m_id, $m_no, $nettotal,$branch,$tran_date)
{
	global $Refs;
	$sql = "SELECT id,spc_transfer_in_date FROM transfers.0_transfer_header WHERE m_id_in IN ($m_id) 
	AND br_code_in = '$branch' AND transfer_in_date>='2017-01-01' and transfer_in_date<='2017-12-31'";
	//display_error($sql); die();
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row['id'];
	$spc_transfer_in_date = $row['spc_transfer_in_date'];
	
	$memo_ = "Stock Transfer, MovementID#: ".$m_id;
	
	$sqlx = "UPDATE 0_gl_trans SET amount='0' WHERE type = 0 AND memo_ = '$memo_' AND amount!=0 and tran_date>='2017-01-01' and tran_date<='2017-12-31'";
	$resx= db_query($sqlx);
	$rowx= db_fetch($resx);

	if ($trans_no == '')
	{
		
		$status=1;
		
		begin_transaction();
		$tran_date = sql2date($tran_date);
		
		$sql1 = "SELECT b.gl_stock_from
					FROM transfers.0_branches b
					WHERE b.code= '$branch'
					";
					//display_error($sql1); die;
		$res1= db_query($sql1);
		$row1 = db_fetch($res1);
		
		$gl_from = $row1['gl_stock_from'];
		
		$ref   = $Refs->get_next(0);
		
		$trans_type = ST_JOURNAL;

		$trans_id = get_next_trans_no($trans_type);

			
			add_gl_trans($trans_type, $trans_id, $tran_date, '570001', 0, 0, $memo_, $nettotal);
			add_gl_trans($trans_type, $trans_id, $tran_date, $gl_from, 0, 0, $memo_, -$nettotal);
			

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $tran_date, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $tran_date);
		
		commit_transaction();
		
		display_notification('Sucessful Journal for transfer in movement id # '. $m_id .' movement # '. $m_no);
		return $status;
	}
	
}

//=========================================================

function get_net_of_vat_total($m_id, $branch)
{
	$sql = "SELECT 
	MovementLine.MovementID,
	ROUND(SUM(CASE WHEN Products.pVatable = 1
	THEN ROUND((ROUND((extended/1.12),2)),2)
	ELSE ROUND((ROUND((extended),2)),2) END),2) AS net_of_vat
	from MovementLine inner join Movements
	on MovementLine.MovementID = Movements.MovementID inner join
	Products on Products.ProductID = MovementLine.ProductID
	inner join MovementTypes on Movements.MovementCode = MovementTypes.MovementCode
	where (CAST (Movements.TransactionDate  as DATE) >=  '2017-01-01' 
	and  CAST (Movements.TransactionDate  as DATE)<='2017-12-31') 
	and  Movements.status = 2
	and Movements.MovementCode='STI' 
	and Movements.MovementID IN ($m_id)
	group by  MovementLine.MovementID";
	$res = ms_db_query($sql);
		
	 //display_error($sql);
	$total = 0;
	while($row = mssql_fetch_array($res))
	{
		$per_item_total = $row['net_of_vat'];
		//display_error($per_item_total);
		$total += $per_item_total;
	}
	
	return $total;
}

if (isset($_POST['fix_now']))
{
	
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	
	//TO DELETE FIRST DOUBLE TRANSACTIONS
	//adjust_gl_entry_double();
	// adjust_gl_entry_not_in_transfer_header($this_branch);
	// adjust_gl_entry_not_in_ms($this_branch);
	
	$sql = "
	
	SELECT DISTINCT MovementID,MovementNo,CAST (PostedDate as Date) as date from (
	SELECT m.MovementID,m.MovementNo,m.PostedDate
	FROM [Movements] as m
	LEFT JOIN [Movementline] as ml
	ON m.MovementID=ml.MovementID
	where m.MovementCode='STI'
	and CAST (m.PostedDate as Date)>='2017-01-01'
	and CAST (m.PostedDate as Date)<='2017-12-31'
	
	
	) x
	
	";
	
	// and m.MovementID in (

	// )
	$res = ms_db_query($sql);
	
	// display_error($sql);
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mssql_fetch_array($res))
	{
		
		$m_id = $row[0];
		$m_no = $row[1];
		$tran_date = $row[2];
		
		$net_of_vat_total = get_net_of_vat_total($m_id,$this_branch);

		//1. CHECK SA ARIA IF MERON THEN UPDATE YUNG TAMANG NET OF VAT.
		$stats1=adjust_gl_entry($m_id,$m_no, round($net_of_vat_total,2),$this_branch);
		
		//display_error($stats1);
		//2. IF WALA SA ARIA, CHECK IF NASA PARTIAL SIYA THEN UPDATE YUNG TAMANG NET OF VAT.
		if ($stats1==0) {
			$stats2=adjust_gl_entry_partial($m_id,$m_no, round($net_of_vat_total,2),$this_branch);
		}
		
		//3. IF WALA SA WALA SA ARIA AT PARTIAL, GAWA NG JOURNAL.
		if ($stats2==0) {
			$stats3=adjust_gl_entry_journal($m_id,$m_no, round($net_of_vat_total,2),$this_branch,$tran_date);
		}
		
		
	}
	
	 display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'Process');
end_form();

end_page();
?>
