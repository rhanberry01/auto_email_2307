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
ini_set('memory_limit', '-1');

$page_security = 'SA_GLANALYTIC';
$path_to_root="../..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");



if (isset($_POST['export'])){
			
	    header('Content-type: application/vnd.ms-excel');
		header("Content-Disposition: attachment; filename=SalesGP_".$_POST['date_from']."-".$_POST['date_to'].".xls");
		header("Pragma: no-cache");
		header("Expires: 0");
		$style   = "style='font-family: Arial; font-size:10px'";
		$heading = "style='font-family:helvetica;font-size:10px;'";  
		$th = array('','Alaminos','Antipolo1', 'Antipolo2', 'B Silang','Bagumbong','Cainta', 'Cainta 2', 'Camarin', 'Gagalangin', 'Imus', 'Comembo', 'Malabon','Molino','Montalban','Muzon','Navotas','Novaliches','Pateros','Pedro','Pinas','Punturin','Tondo','Valenzuela');

	//$th = array('','Alaminos','Antipolo1', 'Antipolo2', 'B Silang', 'Cainta', 'Cainta 2', 'Camarin', 'Gagalangin', 'Imus', 'Comembo', 'Malabon','Navotas'
		//,'Novaliches','Pateros','Pedro','Pinas','Punturin','Tondo','Valenzuela');
	echo"<table  width= '90%' align = 'center' border ='1'>
				<tr>
					<td colspan='4'></td>
					<td colspan='4'>Alaminos</td>
					<td colspan='4'>Antipolo1</td>
					<td colspan='4'>Antipolo2</td>
					<td colspan='4'>Bagumbong</td>
					<td colspan='4'>B Silang</td>
					<td colspan='4'>Cainta</td>
					<td colspan='4'>Cainta 2</td>
					<td colspan='4'>Camarin</td>
					<td colspan='4'>Gagalangin</td>
					<td colspan='4'>Imus</td>
					<td colspan='4'>Comembo</td>
					<td colspan='4'>Malabon</td>
					<td colspan='4'>Molino</td>
					<td colspan='4'>Montalban</td>
					<td colspan='4'>Muzon</td>
					<td colspan='4'>Navotas</td>
					<td colspan='4'>Novaliches</td>
					<td colspan='4'>Pateros</td>
					<td colspan='4'>Pedro</td>
					<td colspan='4'>Pinas</td>
					<td colspan='4'>Punturin</td>
					<td colspan='4'>Tondo</td>
					<td colspan='4'>Valenzuela</td>
				</tr>";

		echo"<tr>";
		echo "<td colspan=4>Date</td>";
			$kcount = count($th)-1;
			$count = 0;
			while($kcount > $count){

				$count++;
			echo"<td>Sales</td>
				<td>Cost Of Sales</td>
				<td>GP</td>
				<td>GP Percentage</td>";
		
			}
		echo "</tr>";

	$branch_arr = array('SRSMALAM','SRSMANT1GF', 'SRSMANT2EM','SRSMBAG','SRSMBSL','SRSMCAINTA', 'SRSMCAINTA2', 'SRSMCAMA', 'SRSMGAL', 'SRSMIMU', 'SRSMKUM', 'SRSMMALA','SRSMMOL','SRSMMONTB','SRSMMUZ', 'SRSMNAVO','SRSMNOVA','SRSMPAT','SRSMPEDRO','SRSMPINAS','SRSMPUN','SRSMTON','SRSMVAL');
		$sales_data = getsalesdata($_POST['date_from'],$_POST['date_to']);
		$ldate = date('Y-m-d',strtotime($_POST['date_from']));
		//echo $ldate;
		$count_r = 0;
		$count_d = 0;
		$count_s = 0;
		$branch_counter = 0;

		//echo "<tr>";
			while ($res = mssql_fetch_array($sales_data)){
				if($ldate !=  $res['ldate'])
				{
					echo "</tr>";
					$count_s = $count_r;
				}
				if($count_r == $count_s)
				{
					echo "<tr><td colspan='4'>".$res['ldate']."</td>";
					$branch_counter =0;
					$count_d++;
				}

				if($branch_arr[$branch_counter] != $res['branch']){
					echo "<td></td>";
					echo "<td></td>";
					echo "<td></td>";
					echo "<td></td>";

				/* 	echo "<td>".number_format($res['totalsales'],2)."</td>";
					echo "<td>".number_format($res['totalcsales'],2)."</td>";
					echo "<td>".number_format($res['gp'],2)."</td>";
					echo "<td>".number_format($res['gppercentage'],2)."</td>"; */
			
					$branch_counter++;
					
				}else{

					echo "<td>".number_format($res['totalsales'],2)."</td>";
					echo "<td>".number_format($res['totalcsales'],2)."</td>";
					echo "<td>".number_format($res['gp'],2)."</td>";
					if($res['gppercentage'] < 4){
						echo "<td><b><font color='red'>".number_format($res['gppercentage'],2)."</font></b></td>";
					}else{

						echo "<td>".number_format($res['gppercentage'],2)."</td>";
					}
				}


				$ldate = $res['ldate'];	
				$count_r++;
				$branch_counter++;
					
			}

			echo "<tr>";
					echo "<td colspan='4'>Total</td>";
					$branch_arr = array('SRSMALAM','SRSMANT1GF', 'SRSMANT2EM','SRSMBAG','SRSMBSL','SRSMCAINTA', 'SRSMCAINTA2', 'SRSMCAMA', 'SRSMGAL', 'SRSMIMU', 'SRSMKUM', 'SRSMMALA','SRSMMOL','SRSMMONTB','SRSMMUZ', 'SRSMNAVO','SRSMNOVA','SRSMPAT','SRSMPEDRO','SRSMPINAS','SRSMPUN','SRSMTON','SRSMVAL');
					$tsales_data = gettotalsalesdata($_POST['date_from'],$_POST['date_to']);
					$branch_counter = 0;
					while ($res = mssql_fetch_array($tsales_data)){

						if($branch_arr[$branch_counter] != $res['branch']){
								echo "<td></td>";
								echo "<td></td>";
								echo "<td></td>";
								echo "<td></td>";

								echo "<td>".number_format($res['sales'],2)."</td>";
								echo "<td>".number_format($res['csales'],2)."</td>";
								echo "<td>".number_format($res['gp'],2)."</td>";
								echo "<td>".number_format($res['gpp'],2)."</td>";
						
								$branch_counter++;
								
							}else{

								echo "<td>".number_format($res['sales'],2)."</td>";
								echo "<td>".number_format($res['csales'],2)."</td>";
								echo "<td>".number_format($res['gp'],2)."</td>";
								if($res['gpp'] < 4){
									echo "<td><b><font color='red'>".number_format($res['gpp'],2)."</font></b></td>";
								}else{

									echo "<td>".number_format($res['gpp'],2)."</td>";
								}
							}
							$branch_counter++;
						}
			echo "</tr>";

			echo "<tr>";
					echo "<td colspan='4'></td>";
					$branch_arr = array('SRSMALAM','SRSMANT1GF', 'SRSMANT2EM','SRSMBAG','SRSMBSL','SRSMCAINTA', 'SRSMCAINTA2', 'SRSMCAMA', 'SRSMGAL', 'SRSMIMU', 'SRSMKUM', 'SRSMMALA','SRSMMOL','SRSMMONTB','SRSMMUZ', 'SRSMNAVO','SRSMNOVA','SRSMPAT','SRSMPEDRO','SRSMPINAS','SRSMPUN','SRSMTON','SRSMVAL');
					$tsales_data = gettotalsalesdata($_POST['date_from'],$_POST['date_to']);
					$branch_counter = 0;
					$date_count = $count_d+1;
					
					//$diff = $_POST['date_to'] - $_POST['date_from'];

					
					while ($res = mssql_fetch_array($tsales_data)){

						if($branch_arr[$branch_counter] != $res['branch']){
								echo "<td></td>";
								echo "<td></td>";
								echo "<td></td>";
								echo "<td></td>";

								echo "<td>".number_format($res['sales']/($date_count-1),2)."</td>";
								echo "<td></td>";
								echo "<td></td>";
								echo "<td></td>";
						
								$branch_counter++;
								
							}else{

								echo "<td>".number_format($res['sales']/($date_count-1),2)."</td>";
								echo "<td>".$diff."</td>";
								echo "<td></td>";
								echo "<td></td>";
							}
							$branch_counter++;
						}
			echo "</tr>";




	echo "</table>";

exit();
}


$js = "";
if ($use_date_picker)
	$js = get_js_date_picker();

page(_($help_context = "GP FORMULA 1"), false, false, "", $js);

global $db_connections, $ms_db,$br_con,$ms_nova;

$ms_nova = mssql_connect('192.168.0.133','markuser','tseug'); 
			   mssql_select_db('SRSMNOVA',$ms_nova);

$ms_db = mssql_connect('192.168.0.133','markuser','tseug'); 
			   mssql_select_db('SRSMNOVA',$ms_db);

//----------------------------------------------------------------------------------------------------
start_form();

function insert_into_fsales($branch,$fsales){
	$ms_db = mssql_connect('192.168.0.133','markuser','tseug'); 
			   mssql_select_db('SRSMNOVA',$ms_db);

	$date =  $fsales['date'];
	$tsales =  $fsales['tsales'];
	$tcsales =  $fsales['tcsales'];
	$sukipoints =  $fsales['sukipoints'];
	$gp =  $fsales['gp'];
	$gppercentage =  $fsales['gppercentage'];
	$IsNet =  1;
	$sktag =  1;

	$sql = "select id,ldate,branch from FSales where ldate ='".$date."' and branch='".$branch."'";
	$resc = mssql_query($sql,$ms_db);
	$resultc = mssql_fetch_row($resc);
	//display_notification($sql);

	if($resultc){
		$sql="UPDATE FSales
			  SET totalsales=".$tsales.",totalcsales=".$tcsales.",gp=".$gp.",gppercentage=".$gppercentage.",IsNet =".$IsNet.",sukipoints=".$sukipoints.",sktag=".$sktag."
			  WHERE ldate = '".$resultc[1]."' and branch='".$resultc[2]."' ";
		$resc = mssql_query($sql,$ms_db);
	//display_notification($sql);
	//die();

	}else{

			$sql = " INSERT INTO FSales(branch,ldate,totalsales,totalcsales,gp,gppercentage,IsNet,sukipoints,sktag) 
			VALUES('".$branch."','".$date."','".$tsales."','".$tcsales."','".$gp."','".$gppercentage."','".$IsNet."','".$sukipoints."','".$sktag."')"; 
			 $result = mssql_query($sql,$ms_db);
	//display_notification($sql);
	//die();

		
	}
	
}

function insert_data($branch,$from,$to){

	$ms_db = mssql_connect('192.168.0.133','markuser','tseug'); 
			mssql_select_db($branch,$ms_db);


			/*$sql = "select 
					cast(logdate as date) as logdate,
					SUM(round(extended * finishedsales.multiplier,4)) as tsales,
					SUM(averageunitcost * case when [return]=1 then convert(money,0 - totalqty) else totalqty end) as tcsales,
					SUM(round(extended * finishedsales.multiplier,4)) - SUM(averageunitcost * case when [return]=1 then convert(money,0 - totalqty) else totalqty end) as gp,
					(SUM(round(extended * finishedsales.multiplier,4)) - SUM(averageunitcost * case when [return]=1 then convert(money,0 - totalqty) else totalqty end))/ SUM(Extended)*100 as gppercentage
					from FinishedSales  where  
					CAST(LogDate as date) between '".$from."' and '".$to."' and voided = 0 and [Return] = 0
					group by cast(logdate as date)";*/


			/*$sql = "select 
					ldate as logdate,
					sum(totalcost) as tcsales,
					sum(extended) as tsales, 
					case when sum(totalcost) = 0 then 0 else (sum(extended) - (sum(totalcost)))/sum(extended)*100 end as gppercentage , 
					sum(extended) - (sum(totalcost)) as gp
					From (
					select
					FinishedSales.LogDate as ldate , 
					vendor_products.averagenetcost, 
					finishedsales.QtyReturned,
					finishedsales.productid,
					averageunitcost * case when [return]=1 then convert(money,0-totalqty) else totalqty end as TotalCost,
					(chargeallowance * finishedsales.qty) + (chargeamountdiscounted * finishedsales.qty) as charges, 
					(allowance * finishedsales.qty) + (amountdiscounted * finishedsales.qty) + (extended-(extended * finishedsales.multiplier)) as discount,
					0 as rettotalqty,
					0 as retextended,
					totalqty,
					round(extended * finishedsales.multiplier,4) as extended 
					From 
					finishedsales 
					left join products on products.productid=finishedsales.productid
					left join vendor_products on finishedsales.productid=vendor_products.productid 
					where logdate >= '".$from."' and  logdate < = '".$to."'   and voided = 0
					and vendor_products.defa=1
					Union All select 
					FinishedSales.LogDate as ldate ,vendor_products.averagenetcost, finishedsales.QtyReturned,
					finishedsales.productid,0 as TotalCost,0 as charges,0 as discount,totalqty as rettotalqty,
					abs(round(extended,4)) as retextended,0 as totalqty, 0 as extended
					From finishedsales left join products on products.productid=finishedsales.productid 
					left join vendor_products on finishedsales.productid=vendor_products.productid 
					where logdate > = '".$from."' and  logdate < = '".$to."'  AND VOIDED = 0 
					AND [RETURN] = 1) as finishedsales left join products 
					on products.productid=finishedsales.productid
					group by ldate  order by ldate";*/

				$sql = "	select
							cast(finishedsales.ldate as date) as logdate,
							sum(totalcost) AS tcsales,
							(((sum(vat) - ISNULL((select sum(amount) as sukipoints  from FinishedPayments where tendercode ='004'and voided=0 and cast(LogDate as date) = cast(finishedsales.ldate as date)),0) ) / 1.12) + sum(nvat)) as tsales,
							case when sum(totalcost) = 0 then 0 else ((((sum(vat) - ISNULL((select sum(amount) as sukipoints  from FinishedPayments where tendercode ='004'and voided=0 and cast(LogDate as date) = cast(finishedsales.ldate as date)),0)) / 1.12) + sum(nvat)) - (sum(totalcost)))/NULLIF((((sum(vat) - ISNULL((select sum(amount) as sukipoints  from FinishedPayments where tendercode ='004'and voided=0 and cast(LogDate as date) = cast(finishedsales.ldate as date)),0) ) / 1.12) + sum(nvat)),0)*100 end  as gppercentage ,
							(((sum(vat) - ISNULL((select sum(amount) as sukipoints  from FinishedPayments where tendercode ='004'and voided=0 and cast(LogDate as date) = cast(finishedsales.ldate as date)),0)) / 1.12) + sum(nvat)) - (sum(totalcost)) as gp,
							ISNULL((select sum(amount) as sukipoints  from FinishedPayments where tendercode ='004'and voided=0 and cast(LogDate as date) = cast(finishedsales.ldate as date)),0) as sukipoints								
							From 
							(select 
							FinishedSales.LogDate as ldate ,
							vendor.description as description1,
							vendor_products.averagenetcost as av, 
							finishedsales.QtyReturned,
							finishedsales.productid,
							(averageunitcost /
							case when finishedsales.Pvatable =1 OR finishedsales.Pvatable = 2 then 1.12  
											else 1 end 
							)* case when [return]=1 then convert(money,0-totalqty) 
							else totalqty end as TotalCost,
							(chargeallowance * finishedsales.qty) + (chargeamountdiscounted * finishedsales.qty) as charges,
							amountdiscounted  as discount,
							0 as rettotalqty,
							0 as retextended,
							totalqty,
							CASE WHEN  finishedsales.Pvatable = 1  THEN
							round((extended * finishedsales.multiplier),4) 
							ELSE 0  END as vat, 
							CASE WHEN  finishedsales.Pvatable <> 1  THEN
							round((extended * finishedsales.multiplier),4) 
							ELSE 0 END as nvat, 
							finishedSales.PriceOverride as PriceOverride,finishedSales.UOM,finishedSales.Packing
							From finishedsales left join products on products.productid=finishedsales.productid
							INNER JOIN vendor on vendor.vendorcode = Products.vendorcode
							left join vendor_products on finishedsales.productid=vendor_products.productid
								where cast(LogDate as date) >= '".$from."' and cast(LogDate as date) <= '".$to."'  and voided = 0 
							and vendor_products.defa=1 
							Union All 
							select 
							'' as ldate ,
							vendor.description,
							vendor_products.averagenetcost,
							finishedsales.QtyReturned, finishedsales.productid,
							0 as TotalCost,0 as charges,0 as discount,totalqty as rettotalqty ,abs(round(extended,4)) as retextended,0 as totalqty, 0 as vat, 0 as nvat 
							, 0 as PriceOverride,finishedSales.uom,finishedSales.Packing
							From finishedsales 
							join products on products.productid=finishedsales.productid 
							INNER JOIN vendor on vendor.vendorcode = Products.vendorcode
							left join vendor_products on finishedsales.productid=vendor_products.productid
							WHERE cast(LogDate as date) >=  '".$from."' and cast(LogDate as date) <= '".$to."' AND VOIDED = 0   AND [RETURN] = 1 ) 
							as finishedsales 
							left join products on products.productid=finishedsales.productid 
							group by
							finishedsales.ldate
							HAVING sum(vat+nvat) <> 0
							order by  ldate
						";


					
			$result = mssql_query($sql,$ms_db);

			$fsales = array();
			while($res = mssql_fetch_array($result))
			{

				 $arrayName = array('date' => $res['logdate'], 'tsales' => $res['tsales'],'tcsales' => $res['tcsales'],'gp' => $res['gp'],'gppercentage' => $res['gppercentage'],'sukipoints' => $res['sukipoints']); 
				 array_push($fsales,$arrayName);

			}

				//display_notification(count($fsales));
				$count = count($fsales);
				$counter = 0;

				while($count > $counter){
					insert_into_fsales($branch,$fsales[$counter]);
					$counter++;
				}




}


function getsalesdata($from,$to){
	$ms_db = mssql_connect('192.168.0.133','markuser','tseug'); 
			mssql_select_db('SRSMNOVA',$ms_db);
	$sql = "select * from FSales where ldate between '".$from."' and '".$to."' order by ldate,branch";
	$result = mssql_query($sql,$ms_db);
	return $result;

}


function gettotalsalesdata($from,$to){
	$ms_db = mssql_connect('192.168.0.133','markuser','tseug'); 
			mssql_select_db('SRSMNOVA',$ms_db);
	$sql = "select 
			branch,
			sum(totalsales) as sales,
			sum(totalcsales) as csales,
			sum(totalsales)-sum(totalcsales) as gp,
			(sum(totalsales)-sum(totalcsales))/sum(totalsales)*100 as gpp,
			sum(sukipoints) as tskpoints
			from FSales where ldate BETWEEN '".$from."' and '".$to."'
			GROUP BY branch  ORDER BY branch";
	$result = mssql_query($sql,$ms_db);
	return $result;

}


function get_allbranch(){
	$ms_db = mssql_connect('192.168.0.133','markuser','tseug'); 
			mssql_select_db('SRSMNOVA',$ms_db);
	$sql = "select ms_db from R_branch_133";
	$result = mssql_query($sql,$ms_db);
	return $result;
}


if (isset($_POST['generate'])){


		if($_POST['branch'] == -1){
			$all_branch = get_allbranch();
			
			
			while($res = mssql_fetch_array($all_branch))
			{
				insert_data($res['ms_db'],$_POST['date_from'],$_POST['date_to']);
			}
			display_notification('Sales Has Been Updated');
		}else{
				insert_data($_POST['branch'],$_POST['date_from'],$_POST['date_to']);
				display_notification('Sales Has Been Updated '.$_POST['branch']);
		}

		$ms_db = mssql_connect('192.168.0.133','markuser','tseug'); 
		mssql_select_db('SRSMNOVA',$ms_db);


}else if(isset($_POST['view'])){

echo "<div style='overflow:auto; height:400px;'>";
		start_table($table_style2.'style=overflow-x:scroll; width=50%');

			$th = array('','Alaminos','Antipolo1', 'Antipolo2','Bagumbong','B Silang', 'Cainta', 'Cainta 2', 'Camarin', 'Gagalangin', 'Imus', 'Comembo', 'Malabon','Molino','Montalban','Muzon','Navotas','Novaliches','Pateros','Pedro','Pinas','Punturin','Tondo','Valenzuela');
			table_header($th,'colspan=7');
			
			alt_table_row_color($k);
			label_cell('Date','colspan=7');

			$kcount = count($th)-1;
			$count = 0;
			while($kcount > $count){

				$count++;

				label_cell('Sales','');
				label_cell('Cost Of Sales','');
				label_cell('Profit','');
				label_cell('GP','');
				label_cell('Net of Vat','');
				label_cell('Less SK Points','');
				label_cell('SK Points','');
			
			}
			end_row();

			$branch_arr = array('SRSMALAM','SRSMANT1GF', 'SRSMANT2EM','SRSMBAG', 'SRSMBSL', 'SRSMCAINTA', 'SRSMCAINTA2', 'SRSMCAMA', 'SRSMGAL', 'SRSMIMU', 'SRSMKUM', 'SRSMMALA','SRSMMOL','SRSMMONTB','SRSMMUZ', 'SRSMNAVO','SRSMNOVA','SRSMPAT','SRSMPEDRO','SRSMPINAS','SRSMPUN','SRSMTON','SRSMVAL');
			$sales_data = getsalesdata($_POST['date_from'],$_POST['date_to']);
			$ldate = date('Y-m-d',strtotime($_POST['date_from']));
			//echo $ldate;
			$count_d = 0;
			$count_r = 0;
			$count_s = 0;
			$branch_counter = 0;
			alt_table_row_color($k);

	

			//display_notification();

			while ($res = mssql_fetch_array($sales_data)){

				if($ldate !=  $res['ldate'])
				{
					end_row();
					$count_s = $count_r;
					$count_d++;
				}	
//	echo $count_r.'----------'.$count_s;
				if($count_r == $count_s)
				{
					label_cell($res['ldate'],'colspan=7');
					$branch_counter = 0;
				}
				//echo $branch_arr[$branch_counter].'-------'.$res['branch'].'</br>';

				if($branch_arr[$branch_counter] != $res['branch']){
					label_cell('','');
					label_cell('','');
					label_cell('','');
					label_cell('','');
					label_cell('','');
					label_cell('','');
					label_cell('','');
				//echo '------->>>>>>>>>>>>></br>';


					label_cell(number_format($res['totalsales'],2),'');
					label_cell(number_format($res['totalcsales'],2),'');
					label_cell(number_format($res['gp'],4),'');
					label_cell(number_format($res['gppercentage'],2),'');
					label_cell($res['IsNet'],'');
					if($res['sktag']){
						$sk = 'Yes';
					}else{
						$sk = 'No';
					}
					label_cell($sk,'');
					if($res['sukipoints']){
						$Sukipoints = $res['sukipoints'];
					}else{
						$Sukipoints = 0;
					}
					label_cell(number_format($Sukipoints,2),'');
					$branch_counter++;
					
				}else{

					label_cell(number_format($res['totalsales'],2),'');
					label_cell(number_format($res['totalcsales'],2),'');
					label_cell(number_format($res['gp'],4),'');
					if($res['gppercentage'] < 5){
						label_cell("<font color='red'>".number_format($res['gppercentage'],2).'</font>','');
					}else{

						label_cell(number_format($res['gppercentage'],2),'');
					}
					label_cell($res['IsNet'],'');
					if($res['sktag']){
						$sk = 'Yes';
					}else{
						$sk = 'No';
					}
					label_cell($sk,'');
					if($res['sukipoints']){
						$Sukipoints = $res['sukipoints'];
					}else{
						$Sukipoints = 0;
					}
					label_cell(number_format($Sukipoints,2),'');
				}

				
				$ldate = $res['ldate'];	
				$count_r++;
				$branch_counter++;
				
			}
			
				alt_table_row_color($k);
					label_cell('Total','colspan=7');
					$branch_arr = array('SRSMALAM','SRSMANT1GF', 'SRSMANT2EM', 'SRSMBAG', 'SRSMBSL','SRSMCAINTA', 'SRSMCAINTA2', 'SRSMCAMA', 'SRSMGAL', 'SRSMIMU', 'SRSMKUM', 'SRSMMALA','SRSMMOL','SRSMMONTB','SRSMMUZ', 'SRSMNAVO','SRSMNOVA','SRSMPAT','SRSMPEDRO','SRSMPINAS','SRSMPUN','SRSMTON','SRSMVAL');
					$tsales_data = gettotalsalesdata($_POST['date_from'],$_POST['date_to']);
					$branch_counter = 0;
					$date_count = $count_d+1;
					while ($res = mssql_fetch_array($tsales_data)){

						if($branch_arr[$branch_counter] != $res['branch']){
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');


							label_cell(number_format($res['sales'],2),'');
							label_cell(number_format($res['csales'],2),'');
							label_cell(number_format($res['gp'],4),'');
							label_cell(number_format($res['gpp'],2),'');
							label_cell($res['IsNet'],'');
							if($res['sktag']){
								$sk = 'Yes';
							}else{
								$sk = 'No';
							}
							label_cell($sk,'');
							if($res['sukipoints']){
								$Sukipoints = $res['sukipoints'];
							}else{
								$Sukipoints = 0;
							}
							label_cell(number_format($Sukipoints,2),'');
							$branch_counter++;
							
						}else{

							label_cell(number_format($res['sales'],2),'');
							label_cell(number_format($res['csales'],2),'');
							label_cell(number_format($res['gp'],4),'');
							if($res['gpp'] < 5){
								label_cell("<font color='red'>".number_format($res['gpp'],2).'</font>','');
							}else{

								label_cell(number_format($res['gpp'],2),'');
							}
							label_cell($res['IsNet'],'');
							if($res['sktag']){
								$sk = 'Yes';
							}else{
								$sk = 'No';
							}
							label_cell($sukipoints,'');
							if($res['sukipoints']){
								$Sukipoints = $res['sukipoints'];
							}else{
								$Sukipoints = 0;
							}
							label_cell(number_format($Sukipoints,2),'');

						}
						$branch_counter++;
					}
				end_row();



				alt_table_row_color($k);
					label_cell('DAS','colspan=7');
					$branch_arr = array('SRSMALAM','SRSMANT1GF', 'SRSMANT2EM', 'SRSMBAG', 'SRSMBSL','SRSMCAINTA', 'SRSMCAINTA2', 'SRSMCAMA', 'SRSMGAL', 'SRSMIMU', 'SRSMKUM', 'SRSMMALA','SRSMMOL','SRSMMONTB','SRSMMUZ', 'SRSMNAVO','SRSMNOVA','SRSMPAT','SRSMPEDRO','SRSMPINAS','SRSMPUN','SRSMTON','SRSMVAL');
					$tsales_data = gettotalsalesdata($_POST['date_from'],$_POST['date_to']);
					$branch_counter = 0;
					$date_count = $count_d+1;
					while ($res = mssql_fetch_array($tsales_data)){

						if($branch_arr[$branch_counter] != $res['branch']){
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							




							label_cell(number_format($res['sales']/$date_count,2),'');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							


							$branch_counter++;
							
						}else{

						    label_cell(number_format($res['sales']/$date_count,2),'');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							label_cell('','');
							


						}
						$branch_counter++;
					}
				end_row();


		end_table('');

		echo '</div>';
}



start_table($table_style2);	
	start_row();
		branch_rebates_list_row(_("Branch :"), 'branch', null, false,false);
		date_row(_("Date: From "), 'date_from');
		date_row(_("Date To: "), 'date_to');
	table_section(2);
	echo '</br>';
	table_section(2);
		submit_cells('generate','Generate','','');	
		submit_cells('view','View','','');	
		submit_cells('export','Export','','');	
	end_row();
end_table();

end_form();

end_page();

?>