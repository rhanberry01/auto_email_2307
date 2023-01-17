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

if (isset($_GET['xls']) AND isset($_GET['filename']) AND isset($_GET['unique']))
{
	$path_to_root = "../..";
	include_once($path_to_root . "/includes/session.inc");
	
	header('Content-Disposition: attachment; filename='.$_GET['filename']);

	$target = $path_to_root.'/company/'.$_SESSION['wa_current_user']->company.'/pdf_files/'.$_GET['unique'];
	readfile($target);
	
	exit;
}

$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/modules/checkprint/includes/consignment_mailer.inc");

	//start of excel report
if(isset($_POST['dl_excel']))
{
	cashier_summary_per_day_excel();
	exit;
}

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $start = $time;

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Import Consignment Sales", false, false, "", $js);

//---------------SUBMIT---------------------

if (isset ($_POST['generate'])) {
	//mssql_query("BEGIN TRANSACTION");
	begin_transaction();

	$d_month=$_POST['d_month'];
	$d_year=$_POST['d_year'];

	//display_error($d_year);
	$start_date=__date($_POST['d_year'],$_POST['d_month'],1);
	$end_date=end_month($start_date);

	$myDB=$db_connections[$_SESSION["wa_current_user"]->company]["db_133"];
	get_ms_cons_header($start_date,$end_date,$myDB);
	
	commit_transaction();
	//mssql_query("COMMIT TRANSACTION");
	
	//meta_forward($_SERVER['PHP_SELF']);
	display_notification("Auto import of MS to MYSQl is successful");
}
//---------------END OF SUBMIT-----------------

function month_select_box( $field_name = 'month',$selected) {
$month_options = '';

//display_error($selected);
	for( $i = 1; $i <= 12; $i++ ) {
	$month_num = str_pad( $i, 2, 0, STR_PAD_LEFT );
	$month_name = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0, 0 ) );

	if($month_num==$selected) {
	$x='selected';
	}
	else {
	$x='';
	}

	//display_error($month_num);

	$month_options .= '<option value="' .  $month_num  . '" '.$x.'>' . $month_name . '</option>';
	}
return '<select name="' . $field_name . '">' . $month_options . '</select>';
}


function month_list_cells($label, $name, $selected_id, $all_option=false)
{
	if ($label != null)
		echo "<td>$label</td><td>\n";
		echo month_select_box($name, $selected_id, $all_option);
		echo "</td>\n";
}

function yearDropdownMenu($name, $start_year, $end_year = null, $id='year_select', $selected=null) {
 
        // curret year as end year
		$end_year = is_null($end_year) ? date('Y') : $end_year;
		
		// the current year
        $selected = is_null($selected) ? date('Y') : $selected;
 
        // range of years 
        $r = range($start_year, $end_year);
 
        //create the HTML select
        $select = '<select name="'.$name.'" id="'.$id.'">';
        foreach( $r as $year )
        {
            $select .= "<option value=\"$year\"";
            $select .= ($year==$selected) ? ' selected="selected"' : '';
            $select .= ">$year</option>\n";
        }
        $select .= '</select>';
        return $select;
}

	
function year_list_cells($label,$name, $start_year, $end_year, $id='year_select', $selected)
{
		if ($label != null)
		echo "<td>$label</td><td>\n";
		echo yearDropdownMenu($name,$start_year, $end_year = null, $id='year_select', $selected=null);
		echo "</td>\n";
}

function get_ms_cons_header($start_date,$end_date,$myDB) {
	ini_set('mssql.connect_timeout',0);
	ini_set('mssql.timeout',0);
	set_time_limit(0);

	$myServer = "192.168.0.133";
	$myUser = "sa";
	$myPass = "tseug";
	//$myDB = "SRSMNOVA"; 
	display_error($myDB."asasasasa");


	$dbhandle = mssql_connect($myServer, $myUser, $myPass)
	or die("Couldn't connect to SQL Server on $myServer". mssql_get_last_message()); 

	$selected = mssql_select_db($myDB, $dbhandle)
	or die("Couldn't open database $myDB". mssql_get_last_message()); 

	//display_error($myDB);
	
	//SET DEADLOCK_PRIORITY HIGH
	$sql="

	SET LOCK_TIMEOUT 0
	SET QUERY_GOVERNOR_COST_LIMIT 99999999999999
	
	USE $myDB
	
	
	select v_vendorcode as Vendor, v_description as Description, sum(f_qty) as 
	QTY,sum(f_extended) Sales,sum(f_auc) as CostofSales,
	v_commission as Commission, v_email as Email
	from 
	(SELECT
	fs.ProductID as f_productid,
	v.vendorcode as v_vendorcode,
	v.reordermultiplier as v_commission,
	v.[description] as v_description,
	v.email as v_email,
	vp.VendorProductCode as vp_product_code,
	vp.uom as vp_uom,
	case when [return]=1 then -fs.TotalQty else fs.TotalQty end as f_qty,
	fs.extended as f_extended,
	fs.productid,fs.AverageUnitCost * case when ([return]=1)
	then convert(money,0-fs.totalqty) else fs.totalqty end as f_auc
	FROM [FinishedSales] as fs
	left join Products as p
	on fs.ProductID = p.ProductID
	left join VENDOR_Products as vp
	on fs.ProductID =vp.ProductID 
	left join [vendor] as v
	on vp.VendorCode=v.vendorcode
	where fs.LogDate >= '".date2sql($start_date)." 00:00:00' and fs.LogDate <= '".date2sql($end_date)." 00:00:00' and fs.Voided=0 and vp.defa=1 and v.Consignor=1
	";
		//'MANUNI001',
	$sql.=") as a group by v_vendorcode,v_description,v_commission,v_email";
	//and v.vendorcode='ZEGEME001'
	//and v.vendorcode IN ('SONSUP001','TLPAPC001','TGAENT001')
	display_error($sql);
	$res=mssql_query($sql)
	or die("Failed to query header.". mssql_get_last_message()); 

	while($row = mssql_fetch_array($res))
	{
	ini_set('mssql.connect_timeout',0);
	ini_set('mssql.timeout',0);
	set_time_limit(0);
	//display_error('a');
	$sql = "Select * from ".TB_PREF."cons_sales_header where supp_code=".db_escape($row['Vendor'])." and start_date='".date2sql($start_date)."' and end_date='".date2sql($end_date)."'";
	$result = db_query($sql);
	display_error($sql);
	$cons_head_row=db_fetch($result);
	$cons_id=$cons_head_row['cons_sales_id'];
	$p_name=$cons_head_row['purchaser_name'];
	
	if(mysql_num_rows($result)) {
	$sql = "UPDATE ".TB_PREF."cons_sales_header SET
	t_commission=".$row['Commission'].", supp_email=".db_escape($row['Email'])."
	where supp_code=".db_escape($row['Vendor'])." and start_date='".date2sql($start_date)."' and end_date='".date2sql($end_date)."'";
	$result = db_query($sql);
	
		//if there is a header but details is empty, it will try to insert again.
		$count=count_imported_items($cons_id);
		display_error($count);
		if ($count<=0 or $count==null) {
				display_error($row['Vendor']);
				get_ms_cons_details($cons_id,$row['Vendor'],$start_date,$end_date,$myDB);
				display_error('good0.');
		}
	
	//return false;
	//display_error('Sales from Supplier ('.$row['Description'].') Already Exist.');
				if ($p_name=='') 
				{
				$myServer = "192.168.0.133";
				$myUser = "sa";
				$myPass = "tseug";
				//$myDB = "SRSMNOVA"; 
				$myDB2="NEWDATACENTER";
				//display_error($myDB2);

				$dbhandle = mssql_connect($myServer, $myUser, $myPass)
				or die("Couldn't connect to SQL Server on $myServer". mssql_get_last_message()); 
				
				$selected = mssql_select_db($myDB2, $dbhandle)
				or die("Couldn't open database $myDB". mssql_get_last_message()); 
				
	
				//display_error('b');
				$ms_vendor= "SELECT TOP 1 [PURID] FROM vendor WHERE vendorcode = '".$row['Vendor']."'";
				//display_error($ms_po);
				$ms_vendor_res = mssql_query($ms_vendor);			  
				//$count = mssql_num_rows($ms_vendor_res);
				$ms_vendor_row= mssql_fetch_array($ms_vendor_res);
				//display_error($dm_count);
				$owner=$ms_vendor_row['PURID'];
				
				
				//display_error('b');
				$ms_vendor2= "SELECT TOP 1 [name],[userid] FROM MarkUsers WHERE userid = '".$owner."'";
				//display_error($ms_vendor2);
				$ms_vendor_res2= mssql_query($ms_vendor2);			  
				//$count = mssql_num_rows($ms_vendor_res2);
				$ms_vendor_row2 = mssql_fetch_array($ms_vendor_res2);
				//display_error($count);
				
				$user_id=$ms_vendor_row2['userid'];
				$purchaser_name=$ms_vendor_row2['name'];
				
				//display_error($user_id);
				//display_error($purchaser_name);
				//mssql_close($dbhandle);
		
				$sql="UPDATE ".TB_PREF."cons_sales_header SET
				purchaser_id='".$user_id."', purchaser_name=".db_escape($purchaser_name)."
				where supp_code=".db_escape($row['Vendor'])." and start_date='".date2sql($start_date)."' and end_date='".date2sql($end_date)."'";
				display_error($sql);
				$result = db_query($sql);
				//mssql_close($dbhandle);
				}
	
	//display_error('good1.');
	}
	else if ($row['Sales']<=0) {
		//display_error('good2.');
	//	return false;
	display_error('Sales from Supplier'.$row['Description'].' is zero (0).');
	}
	else{
		display_error('good3.');
	$sql="INSERT INTO ".TB_PREF."cons_sales_header (supp_code, start_date, end_date, supp_name, t_qty,t_sales,t_cos,t_commission,supp_email)
	VALUES (".db_escape($row['Vendor']).",'".date2sql($start_date)."','".date2sql($end_date)."',".db_escape($row['Description']).", ".$row['QTY'].",".$row['Sales'].",".$row['CostofSales'].",".$row['Commission'].",".db_escape($row['Email']).")";
	display_error($sql.";");
	db_query($sql, "Failed to generate data.");
	
	$id=db_insert_id();
	get_ms_cons_details($id,$row['Vendor'],$start_date,$end_date,$myDB);
	//send_that_sales($id,0);
	
	}
	}
	mssql_close($dbhandle);
}

function get_ms_cons_details($id,$supp_id,$start_date,$end_date,$myDB)
{

		$myServer = "192.168.0.133";
		$myUser = "sa";
		$myPass = "tseug";
		//$myDB = "SRSMNOVA"; 
		display_error($myDB);

		$dbhandle = mssql_connect($myServer, $myUser, $myPass)
		or die("Couldn't connect to SQL Server on $myServer". mssql_get_last_message()); 

		$selected = mssql_select_db($myDB, $dbhandle)
		or die("Couldn't open database $myDB". mssql_get_last_message()); 

		//SET DEADLOCK_PRIORITY HIGH
	$sql1="
	

	SET LOCK_TIMEOUT 0
	SET QUERY_GOVERNOR_COST_LIMIT 99999999999999
	
	USE $myDB


	select v_vendorcode as Vendor,f_productid as ProductID,vp_product_code as VendorProductCode,v_description as VenDescription,vp_description as ProdDescription,
	vp_uom as UOM,sum(f_qty) as QTY,sum(f_extended) Sales,sum(f_auc) as CostofSales from 
	(SELECT
	fs.ProductID as f_productid,
	v.vendorcode as v_vendorcode,
	vp.[description] as vp_description,
	v.Description as v_description,
	vp.VendorProductCode as vp_product_code,
	vp.uom as vp_uom,
	case when [return]=1 then -fs.TotalQty else fs.TotalQty end as f_qty,
	fs.extended as f_extended,
	fs.productid,fs.AverageUnitCost * case when ([return]=1)
	then convert(money,0-fs.totalqty) else fs.totalqty end as f_auc
	FROM [FinishedSales] as fs
	left join Products as p
	on fs.ProductID = p.ProductID
	left join VENDOR_Products as vp
	on fs.ProductID =vp.ProductID 
	left join [vendor] as v
	on vp.VendorCode=v.vendorcode
	where fs.LogDate >= '".date2sql($start_date)." 00:00:00' and fs.LogDate <= '".date2sql($end_date)." 00:00:00' and fs.Voided=0 and vp.defa=1 and v.vendorcode='$supp_id') as a
	group by f_productid,v_vendorcode,v_description,vp_description,vp_product_code,vp_uom";
	display_error($sql1);
	$res1=mssql_query($sql1)
	or die("Failed to query details.". mssql_get_last_message()); 
	//display_error('fgfgfg');

	
	
	display_error($res1);
	while($row1 = mssql_fetch_array($res1))
	{
	// if ($row['Sales']<0) {
	// return false;
	// //display_error('Sales from Product ('.$row['ProdDescription'].') is zero (0).');
	// }
	// else {
		
	//this is last update====erase the condition if error occurs.
	//display_error('fgfgrrrrrrrrrrrrrrrrrrrrrrrrrrfg');
	//display_error('very good.');
	// display_error($row1['Sales']);
		 if ($row1['Sales']>0 or $row1['Sales']!=null or $row1['Sales']!='') {
			// //display_error('very good1.');
		$sql2 = "INSERT INTO ".TB_PREF."cons_sales_details (cons_det_id, prod_id, prod_code, description, uom, qty,sales,cos)
		VALUES (".$id.",'".$row1['ProductID']."','".$row1['VendorProductCode']."',".db_escape($row1['ProdDescription']).", '".$row1['UOM']."', ".$row1['QTY'].",".$row1['Sales'].",".$row1['CostofSales'].")";
		db_query($sql2, "Failed to insert data.");
		display_error($sql2.";");
		}
	
	}
	// }

	//mssql_close($dbhandle);
}

start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	//date_cells(_("Date:"), 'date_', '', null, -1);
	//date_cells(_(" To:"), 'TransToDate', '', null);
	month_list_cells('Month:','d_month',$_POST['d_month']);
	year_list_cells('Year:','d_year',1990);
	submit_cells('generate', _("Process"),'',_('Generate Data'),false,ICON_DOWN);
end_row();
end_table(1);

end_form();
end_page();

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>