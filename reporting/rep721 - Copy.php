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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt, Chaitanya for the recursive version 2009-02-05.
// date_:	2005-05-19
// Title:	Profit and Loss Statement
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_profit_and_loss_statement();
function print_profit_and_loss_statement()
{
	global $comp_path, $path_to_root, $db_connections;

	$dim = get_company_pref('use_dimension');
	$dimension = $dimension2 = 0;

	$to = $from = $_POST['PARAM_0'];
	$gross =  !$_POST['PARAM_1'];
	// $compare = $_POST['PARAM_2'];
	// if ($dim == 2)
	// {
		// $dimension = $_POST['PARAM_3'];
		// $dimension2 = $_POST['PARAM_4'];
		// $decimals = $_POST['PARAM_5'];
		// $graphics = $_POST['PARAM_6'];
		// $comments = $_POST['PARAM_7'];
		// $destination = $_POST['PARAM_8'];
	// }
	// else if ($dim == 1)
	// {
		// $dimension = $_POST['PARAM_3'];
		// $decimals = $_POST['PARAM_4'];
		// $graphics = $_POST['PARAM_5'];
		// $comments = $_POST['PARAM_6'];
		// $destination = $_POST['PARAM_7'];
	// }
	// else
	// {
		// $display_zero = $_POST['PARAM_2'];
		// $graphics = $_POST['PARAM_4'];
		// $comments = $_POST['PARAM_3'];
		$destination = $_POST['PARAM_2'];
		
		$decimals = true;
	// }
	
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	if ($graphics)
	{
		include_once($path_to_root . "/reporting/includes/class.graphic.inc");
		$pg = new graph();
	}
	
	$dec = 4;
	
	$pdec = 4;

	$cols = array(0, 80, 290, 370, 540);
	//------------0--1---2----3----4----5--

	$headers = array(_('Account'), _('Account Name'), _('Period'), 'Percentage', '');

	$aligns = array('left',	'left',	'right', 'right', 'right');

    if ($dim == 2)
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
                    	2 => array('text' => _('Dimension')." 1",
                            'from' => get_dimension_string($dimension), 'to' => ''),
                    	3 => array('text' => _('Dimension')." 2",
                            'from' => get_dimension_string($dimension2), 'to' => ''));
    }
    else if ($dim == 1)
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => $to),
                    	2 => array('text' => _('Dimension'),
                            'from' => get_dimension_string($dimension), 'to' => ''));
    }
    else
    {
    	$params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'),'from' => $from, 'to' => ''),
    				    2 => array('text' => 'Computation','from' => ($gross ? 'Gross' : 'Net of VAT'), 'to' => ''));
    }


	if ($compare == 0 || $compare == 2)
	{
		$end = $to;
		if ($compare == 2)
		{
			$begin = $from;
			$headers[3] = _('Budget');
		}
		else
			$begin = begin_fiscalyear();
	}
	elseif ($compare == 1)
	{
		$begin = add_months($from, -12);
		$end = add_months($to, -12);
		$headers[3] = _('Period Y-1');
	}

	$branch_name = strtoupper($db_connections[$_SESSION["wa_current_user"]->company]["srs_branch"]);
	
	$rep = new FrontReport("Daily GP - $branch_name", "Daily_GP - $branch_name", user_pagesize());

	$rep->Font();
	$rep->Info($params, $cols, $headers, $aligns);
	$rep->Header();
	
	$sql = "TRUNCATE gp_sorter_copy";
	db_query($sql);
	
	// $gross = true;//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
	
	$stock_ids = array(
		2004807194
		// 2004804111
		
		
		// 2004802125
		// 2004855842,
		// 2004853758,
		// 2004856099,
		// 2004861181
		// 146853
		);
	
	// // prod history
	$sql = "SELECT DISTINCT  ProductID FROM ProductHistory 
				WHERE CAST(DatePosted AS DATE) >= '".date2sql($from)."'
				AND CAST(DatePosted AS DATE) <= '".date2sql($to)."'";
	$sql .= " AND MovementCode = '_DR'";
	// echo $sql; die;
	// // receiving
	// $sql = "SELECT DISTINCT b.ProductID FROM Receiving a, ReceivingLine b
				// WHERE CAST(DateReceived AS DATE) >= '".date2sql($from)."'
				// AND CAST(DateReceived AS DATE) <= '".date2sql($to)."'
				// AND a.ReceivingID = b.ReceivingID";
	
	if (count($stock_ids) == 0)
	{
		$res = ms_db_query($sql);
		while($row = mssql_fetch_array($res))
			$stock_ids[] = $row[0];
	}
	
	$counter = 0;
	
	$with_discrepancy = $for_sort = array();
	foreach($stock_ids as $stock_id)
	{	//<<<<<<<<<<<<<<<<<<<<< --------------- start loop here
		if (!$gross)
			$formula1 = get_finished_sales_for_formula_1($from, $to, $stock_id);
		else
			$formula1 = get_finished_sales_for_formula_1_gross($from, $to, $stock_id);
		
		 $form_1 = $formula1[1] - $formula1[0];
		 
		 // formula 2 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		 $sales = $formula1[1];
		
		if (!$gross)
		{
			$beginning = get_inventory_backup_amount($from,$stock_id);
			$purchases = get_ms_purchases($from, $to,$stock_id);
			$ending = get_inventory_backup_amount(add_days($to,1),$stock_id);
		}
		else
		{
			$beginning = get_inventory_backup_amount_gross($from,$stock_id);
			$purchases = get_ms_purchases_gross($from, $to,$stock_id);
			$ending = get_inventory_backup_amount_gross(add_days($to,1),$stock_id);
		}
		
		
		$movements_array = array();
		//============ Display Movements from MSSQL
			
		if(!$gross)
		{
			$sql = "SELECT movements.movementcode, MovementTypes.Description,  
						ROUND(SUM(CASE
								WHEN Products.pVatable = 1 
									THEN ROUND((ROUND((extended/1.12),4)),4)
								ELSE
									ROUND((ROUND((extended),4)),4)
								END),4) AS total
					 from MovementLine inner join Movements 
					on MovementLine.MovementID = Movements.MovementID inner join Products on Products.ProductID = MovementLine.ProductID  
					inner join MovementTypes on Movements.MovementCode = MovementTypes.MovementCode  
					 where CAST (Movements.PostedDate  as DATE) between  '".date2sql($from)."' and '".date2sql($to)."' and  Movements .status = 2";
			if ($stock_id != '')
				$sql .= " AND MovementLine.ProductID = $stock_id";
			// if (count($stock_id) != 0)
				// $sql .= " AND MovementLine.ProductID IN (".$stock_id.")";
				
			$sql .= " group by  movements.movementcode, MovementTypes.Description
					 order by  MovementTypes.Description  ";
		}
		else
		{
			$sql = "SELECT movements.movementcode, MovementTypes.Description,  
						ROUND(SUM(ROUND((extended),4)),4) AS total
					 from MovementLine inner join Movements 
					on MovementLine.MovementID = Movements.MovementID inner join Products on Products.ProductID = MovementLine.ProductID  
					inner join MovementTypes on Movements.MovementCode = MovementTypes.MovementCode  
					 where CAST (Movements.PostedDate  as DATE) between  '".date2sql($from)."' and '".date2sql($to)."' and  Movements .status = 2";
			if ($stock_id != '')
				$sql .= " AND MovementLine.ProductID = $stock_id";
			// if (count($stock_id) != 0)
				// $sql .= " AND MovementLine.ProductID IN (".$stock_id.")";
			$sql .= " group by  movements.movementcode, MovementTypes.Description
					 order by  MovementTypes.Description  ";
		}
		
		
		// echo $sql; die;
		$ms_res = ms_db_query($sql);
		
		$exclude = array('R2CSA','RSAFC','D2BSR','SA2BO');
		$positive = array('PS','STI','IGBO','IGSA','ITI','PASA',);
		$negative = array('FDFB','NASA','WH2BU','R2SSA','STO','D2BSR','IGNBO','IGNSA','ITO',);
		
		$rep->Font('i');
		$movements_total = 0;
		while($ms_row = mssql_fetch_array($ms_res))
		{
			if (in_array($ms_row['movementcode'],$exclude))
				continue;	
			
			if (in_array($ms_row['movementcode'],$negative))
					$ms_row['total'] = -$ms_row['total'];
			
			$movements_total += $ms_row['total'];
			$movements_array[] = array($ms_row['Description'], round($ms_row['total'],$dec));
		}
		$gp = $sales - $purchases - ($beginning+$movements_total-$ending);
		 
		 
		 if((round(abs($form_1 - $gp),2) == 0) AND (count($stock_ids) > 1))
			 continue;
		
		insert_gp_sorter_copy($stock_id, round(abs($form_1 - $gp),2), $formula1[1], $formula1[0], $beginning, $purchases, $ending, $gp);
		// $for_sort[] = array($stock_id, round(abs($form_1 - $gp),2));
		// $with_discrepancy[$stock_id]['stock_id'] = $stock_id;
		// $with_discrepancy[$stock_id]['sales'] = $formula1[1];
		// $with_discrepancy[$stock_id]['cos'] = $formula1[0];
		 // // formula 2 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		// $with_discrepancy[$stock_id]['beginning'] = $beginning;
		// $with_discrepancy[$stock_id]['purchases'] = $purchases;
		// $with_discrepancy[$stock_id]['ending'] = $ending;
		$with_discrepancy[$stock_id]['movements'] = $movements_array;
	}	//<<<<<<<<<<<----------------------------- end loop here
	
	
	// usort($for_sort, function($a, $b) {
			 // if ($a[1] == $b[1]) 
			 // {
				// return 0;
			// }
			// return ($a[1] > $b[1]) ? -1 : 1;
		// });
		
	 //============================================================================
	 //============================================================================
	 //============================DISPLAY IF NOT EQUAL=============================
	 //============================================================================
	 //============================================================================
		
	 // foreach($for_sort as $sorting)
	 
	 $sql = "SELECT * FROM gp_sorter_copy ORDER BY diff DESC";
	 $res_x = db_query($sql);
	 while($row_x = db_fetch($res_x))
	 {
		$stock_id = $row_x['stock_id'];

		$formula1[1] = $sales = $row_x['sales'];
		$formula1[0] = $row_x['cos'];
		// echo $formula1[1].'<br>';
		 // formula 2 ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		$beginning = $row_x['beginning'];
		$purchases = $row_x['purchases'];
		$ending = $row_x['ending'];
		$movements_array = $with_discrepancy[$stock_id]['movements'];
		$gp = $row_x['gp'];
		
		$counter ++;
		
		$sql = "SELECT Description FROM Products WHERE ProductID IN (".$stock_id.")";
		$res = ms_db_query($sql);
		$row = mssql_fetch_array($res);
		
		$rep->Font('bold');
		$rep->Font('biu');
		$rep->TextCol(0, 2, $counter .'.   '.$row[0] . " ($stock_id)");
		$rep->TextCol(2, 3, 'difference of '.$row_x['diff']);
		$rep->Font('');
		$rep->NewLine();

		$rep->Font('bold');
		$rep->TextCol(0, 5, '     Formula # 1 ');
		$rep->Font('');
		$rep->NewLine();
		
		$rep->Font('bold');
		$rep->TextCol(1, 2, 'Sales : ');
		$rep->Font('');
		$rep->AmountCol(2, 3, $formula1[1], $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		$rep->NewLine();	
		$rep->Font('');
		
		$rep->Font('bold');
		$rep->TextCol(1, 2, 'Cost of Sales : ');
		$rep->Font('');
		$rep->AmountCol(2, 3, $formula1[0], $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		// $rep->NewLine();	
		$rep->Font('');
			
		$rep->Font('bold');
		$rep->TextCol(2, 3, '_____________', $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		$rep->NewLine();	
		$rep->Font('');
			
		$rep->Font('bi');
		$rep->TextCol(1, 2, 'Gross Profit : ');
		// $rep->Font('');
		$rep->AmountCol(2, 3, $formula1[1] - $formula1[0], $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		// $rep->NewLine();	
		$rep->Font('');
		
		
		//===================FORMULA 2==============================================	
			
		$rep->Font('bold');
		$rep->NewLine(2);	
		$rep->TextCol(0, 5, '     Formula # 2');
		$rep->Font('');
		$rep->NewLine();
		
		$rep->Font('bold');
		$rep->TextCol(1, 2, 'Sales : ');
		$rep->Font('');
		$rep->AmountCol(2, 3, $sales, $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		$rep->NewLine();	
		$rep->Font('');
		
		$rep->Font('bold');
		$rep->TextCol(1, 2, 'Purchases : ');
		$rep->Font('');
		$rep->AmountCol(2, 3, $purchases, $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		$rep->NewLine();	
		$rep->Font('');
		
		$rep->Font('bold');
		$rep->TextCol(1, 2, 'Beginning  : ');
		$rep->Font('');
		$rep->AmountCol(2, 3, $beginning, $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		$rep->NewLine();	
		$rep->Font('');
		

		//============ Display Movements from MSSQL
			
		$rep->Font('bold');
		$rep->TextCol(1, 2, 'Inventory Movements');
		$rep->Font();
		$rep->NewLine();
		
		$rep->Font('i');
		// $movements_total = 0;
		
		foreach($movements_array as $m_details)
		{
			$rep->TextCol(1, 2, "   ".$m_details[0]);
			$rep->AmountCol(2, 3, $m_details[1], $dec);
			// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
			$rep->NewLine();	
		}
		
		$rep->Font('');
		
		//=====================================
		
		$rep->Font('bold');
		$rep->TextCol(1, 2, 'Ending  : ');
		$rep->Font('');
		$rep->AmountCol(2, 3, $ending, $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		$rep->NewLine();	
		$rep->Font('');
			
		$rep->Font('bold');
		$rep->TextCol(2, 3, '_____________', $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		$rep->NewLine();	
		$rep->Font('');
		
		$rep->Font('bi');
		$rep->TextCol(1, 2, 'Gross Profit : ');
		// $rep->Font('');
		$rep->AmountCol(2, 3, $gp, $dec);
		// $rep->TextCol(3, 4, compute_percent($beg_inv,$a));
		// $rep->NewLine();	
		$rep->Font('');
		$rep->Line($rep->row - 6);
		$rep->NewLine(2);
		// =====================================	
	 }
	$rep->End();
}

function get_finished_sales_for_formula_1_gross($date1, $date2, $stock_id)
{
	$tax_rate = 12;
	$sql = "SELECT ROUND(SUM(
					(CASE WHEN [Return] = 0
					THEN
						AverageUnitCost
					ELSE
						-AverageUnitCost
					END)
					*TotalQty),4) as gross_cost, SUM(Extended) as gross_sales
				FROM [dbo].[FinishedSales] a
				WHERE CAST(LogDate AS DATE) >= '".date2sql($date1)."'
				AND CAST(LogDate AS DATE) <= '".date2sql($date2)."'
				AND Voided = 0";
	
	if ($stock_id != '')
		$sql .= " AND a.ProductID = $stock_id";
	// echo $sql;die;
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	$gross_cost = $row['gross_cost'];
	$gross_sales = $row['gross_sales'];
	
	return array($gross_cost, $gross_sales);
}

function get_finished_sales_for_formula_1($date1, $date2, $stock_id)
{
	$tax_rate = 12;
	$sql = "SELECT SUM(
					(CASE WHEN [Return] = 0
					THEN
						AverageUnitCost
					ELSE
						-AverageUnitCost
					END)
					*TotalQty) as non_vat_cost, SUM(Extended) as non_vat_sales
				FROM [dbo].[FinishedSales] 
				WHERE CAST(LogDate AS DATE) >= '".date2sql($date1)."'
				AND CAST(LogDate AS DATE) <= '".date2sql($date2)."'
				AND Voided = 0  AND pVatable = 0";

	if ($stock_id != '')
		$sql .= " AND ProductID = $stock_id";
	// echo $sql;die;
	
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	$non_vat_cost = $row['non_vat_cost'];
	$non_vat_sales = $row['non_vat_sales'];
	
	$sql = "SELECT SUM(
					(CASE WHEN [Return] = 0
					THEN
						AverageUnitCost
					ELSE
						-AverageUnitCost
					END)
					*TotalQty) as vat_cost, SUM(Extended) as vat_sales
				FROM [dbo].[FinishedSales] 
				WHERE CAST(LogDate AS DATE) >= '".date2sql($date1)."'
				AND CAST(LogDate AS DATE) <= '".date2sql($date2)."'
				AND Voided = 0  AND pVatable = 1";
				
	if ($stock_id != '')
		$sql .= " AND ProductID = $stock_id";
	
	// echo $sql;
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	$vat_cost = $row['vat_cost']/(1+($tax_rate/100));
	$vat_sales = $row['vat_sales']/(1+($tax_rate/100));

	$sql = "SELECT SUM(
					(CASE WHEN [Return] = 0
					THEN
						AverageUnitCost
					ELSE
						-AverageUnitCost
					END)
					*TotalQty) as vat_cost, SUM(Extended) as vat_sales
				FROM [dbo].[FinishedSales] 
				WHERE CAST(LogDate AS DATE) >= '".date2sql($date1)."'
				AND CAST(LogDate AS DATE) <= '".date2sql($date2)."'
				AND Voided = 0  AND pVatable = 2";

	if ($stock_id != '')
		$sql .= " AND ProductID = $stock_id";
	
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	$special_vat_cost = $row['vat_cost']/(1+($tax_rate/100));
	$special_vat_sales = $row['vat_sales'];
	
	return array(round($non_vat_cost+$vat_cost+$special_vat_cost,2), round($non_vat_sales+$vat_sales+$special_vat_sales,2));
}

function get_inventory_backup_amount($date_,$stock_id)
{	
	$sql = "SELECT ROUND(SUM(
						CASE
						WHEN pVatable = 1 THEN
							ROUND((((sellingarea + stockroom + Damaged)* ROUND(costofsales,4))/ 1.12),4)
						ELSE
							ROUND(((sellingarea + stockroom + Damaged)* ROUND(costofsales,4)),4)
						END),4) AS net_of_vat
				FROM	[dbo].[ProductsBackUp] a
				WHERE	CAST(BackUpDate AS DATE) = '".date2sql($date_)."'";
	
	if ($stock_id != '')
		$sql .= " AND a.ProductID = $stock_id";
	// echo $sql; die;
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row['net_of_vat'];
}

function get_inventory_backup_amount_gross($date_, $stock_id)
{	
	$sql = "SELECT ROUND(SUM((sellingarea + stockroom + Damaged)* ROUND(costofsales,4)),4) AS gross
				FROM	[dbo].[ProductsBackUp] a
				WHERE	CAST(BackUpDate AS DATE) = '".date2sql($date_)."'";
	if ($stock_id != '')
		$sql .= " AND a.ProductID = $stock_id";
	
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row['gross'];
}

function get_ms_purchases($date1, $date2, $stock_id)
{
	
	return get_ms_purchases_net_of_vat($date1,$date2,$stock_id) + get_ms_purchases_non_vat($date1,$date2,$stock_id);
}

function get_ms_purchases_gross($date1, $date2, $stock_id)
{
	$sql = "select ROUND(SUM(extended),4) as total from ReceivingLine a
				inner join Receiving on a.ReceivingID = Receiving.ReceivingID  
				inner join products on a.productid = products.productid 
				where CAST (receiving.PostedDate as DATE) between  '".date2sql($date1)."' and '".date2sql($date2)."'
				and Receiving.Status = 2";
	
	if ($stock_id != '')
		$sql .= " AND a.ProductID = $stock_id";

	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}

function get_ms_purchases_net_of_vat($date1,$date2,$stock_id)
{
	/*Receiving Net of Vat  DELIVERIES*/
	$sql = "select ROUND(SUM(extended/1.12),4) as total from ReceivingLine a
			inner join Receiving on a.ReceivingID = Receiving.ReceivingID  
			inner join products on a.productid = products.productid 
			where CAST (receiving.PostedDate as DATE) between  '".date2sql($date1)."' and '".date2sql($date2)."'
			and Receiving.Status = 2 and Products.pVatable = 1";
	if ($stock_id != '')
		$sql .= " AND a.ProductID = $stock_id";
		
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}

function get_ms_purchases_non_vat($date1,$date2,$stock_id)
{
	/*receiving Non Vat */
	$sql = " select ROUND(SUM(extended),4) as total from ReceivingLine a
				inner join Receiving on a.ReceivingID = Receiving.ReceivingID  
				inner join products on a.productid = products.productid 
				where CAST (Receiving.PostedDate as DATE) between '".date2sql($date1)."' and '".date2sql($date2)."'
				and Receiving.Status = 2 and Products.pVatable = 0";
	if ($stock_id != '')
		$sql .= " AND a.ProductID = $stock_id";

	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}

function insert_gp_sorter_copy($stock_id,$diff,$sales,$cos,$beginning,$purchases,$ending,$gp)
{
	$sql = "INSERT IGNORE INTO gp_sorter_copy (stock_id,diff,sales,cos,beginning,purchases,ending,gp)
				VALUES ($stock_id,$diff, ".($sales+0).",".($cos+0).",".($beginning+0).",".($purchases+0).",".($ending+0).",".($gp+0).")";
	db_query($sql,'failed to insert to gp sorter');
}
?>