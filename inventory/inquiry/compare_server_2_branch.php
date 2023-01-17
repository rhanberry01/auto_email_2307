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
ini_set('memory_limit', '-1');
set_time_limit(0);

$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Compare Items From Server to Branch"), false, false, "", $js);

start_form();
div_start('header');

$type = ST_SAKUSINAOUT;

global $db_connections;

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');
br();

function check_double_branch_barcode($barcode){
$sql="SELECT Barcode,count(Barcode) as duplicate
FROM [POS_Products]
WHERE Barcode='$barcode'
GROUP BY Barcode
HAVING count(Barcode) > 1";
$res1 = ms_db_query($sql) or die(mssql_get_last_message());

while($row = mssql_fetch_array($res1))
{
	$count=$row['duplicate'];
}
return $count;
}

function check_double_server_barcode($barcode,$myDB){
$sql="SELECT Barcode,count(Barcode) as duplicate
FROM [192.168.0.133].$myDB.[dbo].[POS_Products]
WHERE Barcode='$barcode'
GROUP BY Barcode
HAVING count(Barcode) > 1";

$res1 = ms_db_query($sql) or die(mssql_get_last_message());
while($row = mssql_fetch_array($res1))
{
	$count=$row['duplicate'];
}
return $count;
}

start_table();
	start_row();
		submit_cells('search', 'DISPLAY ALL ITEMS WITH ISSUE','','',false,ICON_ADD);
		// submit_cells($selected, _("Add"), "colspan=2",_('Add Petty Cash'), true, ICON_ADD);		
	end_row();
end_table(2);
div_end();

div_start('dm_list');
if (!isset($_POST['search']))
	display_footer_exit();

display_notification("Please inform Purchasing department to fix this Issue or ISD department for inquiries/support.");

$myDataCenter = "192.168.0.133";
$myDB=$db_connections[$_SESSION["wa_current_user"]->company]["db_133"];


				// $myServer = "192.168.0.133";
				// $myUser = "markuser";
				// $myPass = "tseug";
				// //$myDB = "SRSMNOVA"; 
				// $myDB=$db_connections[$_SESSION["wa_current_user"]->company]["db_133"];
				// //display_error($myDB);

				// $dbhandle = mssql_connect($myServer, $myUser, $myPass)
				// or die("Couldn't connect to SQL Server on $myServer"); 
				
				// $selected = mssql_select_db($myDB, $dbhandle)
				// or die("Couldn't open database $myDB"); 

				
// $master = mssql_connect("192.168.0.133","markuser","tseug")
// or die("Couldn't connect to SQL Server on master"); 
// $slave = mssql_connect("192.168.0.179","markuser","tseug")
// or die("Couldn't connect to SQL Server on slave"); 

// if(mssql_select_db("SRSMNOVA",$master) && mssql_select_db("srspos",$slave))
// {
    // //Both Selected
    // $sql = mssql_query("SELECT * FROM [POS_Products]",$master);

    // while($row = mssql_fetch_array($sql))
    // {
         // //Query the other $slave DB here!
         // $slave_sql = mssql_query("SELECT * FROM [POS_Products] WHERE Barcode ='".$row['Barcode']."'",$slave);
         // while($sub_row = mssql_fetch_array($slave_sql))
         // {
             // if ($row['srp']!=$sub_row['srp']){
				 // label_cell($row['Barcode']);
			 // }
         // }
	// end_row();
    // }
	
// }		
				
			
// SELECT *
// INTO #myTempTable
// FROM OPENQUERY([192.168.0.133], 'SELECT * FROM [POS_Products]')

// SELECT *
// FROM [POS_Products] as ps1
// left JOIN #myTempTable as ps2
// ON ps1.Barcode=ps2.Barcode
// WHERE  (ps1.uom!=ps2.uom or ps1.srp!=ps2.srp)
// order by ps1.Barcode
			
// SELECT *
// FROM [192.168.0.133].SRSMNOVA.[dbo].[POS_Products] as ps1
// left JOIN [POS_Products] as ps2
// ON ps1.Barcode=ps2.Barcode
// WHERE  (ps1.uom!=ps2.uom or ps1.srp!=ps2.srp)
// order by ps1.Barcode

ms_db_query("SET ANSI_NULLS ON") or die(mssql_get_last_message());
ms_db_query("SET ANSI_WARNINGS ON") or die(mssql_get_last_message());


$sql_check_server_exist="Select 1 Where Exists (Select [server_id] From sys.servers Where [name]='192.168.0.133') ";
$result= ms_db_query($sql_check_server_exist) or die(mssql_get_last_message());
	
	
$check_server_count=mssql_num_rows($result);
//display_error($check_server_count);

if ($check_server_count==0){
	$sql_add_link_server="EXEC sp_addlinkedserver   
    @server='192.168.0.133', 
    @srvproduct='',
    @provider='SQLNCLI', 
    @datasrc='192.168.0.133'
	";
	ms_db_query($sql_add_link_server) or die(mssql_get_last_message());
 }

 	$sql_login="
	EXEC sp_addlinkedsrvlogin
    @useself='FALSE',
    @rmtsrvname='192.168.0.133',
    @rmtuser='markuser',
    @rmtpassword='tseug'";
	ms_db_query($sql_login) or die(mssql_get_last_message());

$sql = "SELECT  
ps1.ProductID as ProductID1
,ps1.Barcode as Barcode1
,ps1.PriceModeCode as PriceModeCode1
,ps1.Description as Description1
,ps1.uom as uom1
,ps1.qty as qty1
,ps1.srp as srp1
,ps1.LastDateModified as LastDateModified1
,ps2.ProductID as ProductID2
,ps2.Barcode as Barcode2
,ps2.PriceModeCode as PriceModeCode2
,ps2.Description as Description2
,ps2.uom as uom2
,ps2.qty as qty2
,ps2.srp as srp2
,ps2.LastDateModified as LastDateModified2
FROM [192.168.0.133].$myDB.[dbo].[POS_Products] as ps1
left JOIN [POS_Products] as ps2
ON ps1.Barcode=ps2.Barcode
WHERE  (ps1.uom!=ps2.uom or ps1.srp!=ps2.srp)
order by ps1.Barcode,ps1.Description";
//$res1 = ms_db_query($sql);
$res1 = ms_db_query($sql) or die(mssql_get_last_message());

$sql = "SELECT  
ps1.ProductID as ProductID1
,ps1.Barcode as Barcode1
,ps1.PriceModeCode as PriceModeCode1
,ps1.Description as Description1
,ps1.uom as uom1
,ps1.qty as qty1
,ps1.srp as srp1
,ps1.LastDateModified as LastDateModified1
,ps2.ProductID as ProductID2
,ps2.Barcode as Barcode2
,ps2.PriceModeCode as PriceModeCode2
,ps2.Description as Description2
,ps2.uom as uom2
,ps2.qty as qty2
,ps2.srp as srp2
,ps2.LastDateModified as LastDateModified2
FROM [192.168.0.133].$myDB.[dbo].[POS_Products] as ps1
left JOIN [POS_Products] as ps2
ON ps1.Barcode=ps2.Barcode
WHERE  (ps1.uom!=ps2.uom or ps1.srp!=ps2.srp)
order by ps2.Barcode,ps2.Description ";

// $res2 = ms_db_query($sql);
$res2 = ms_db_query($sql) or die(mssql_get_last_message());
// $res = mssql_query($sql);
//display_error($sql);

 if (!$res1) {
    // The query has failed, print a nice error message
    // using mssql_get_last_message()
    die('MSSQL error: ' . mssql_get_last_message());
}
 
// $x=mssql_num_rows($res1);
//display_error($x);

// start_table($table_style2.' width=95%');
// $th = array();
	
// array_push($th, 'BARCODE1','BARCODE2');


// if (mssql_num_rows($res) > 0)
	 // table_header($th);
// else
// {
	// display_heading('No transactions found');
	// display_footer_exit();
// }

// end_table();

	start_outer_table("width=95% $table_style2");
	table_section(1, "50%");
	table_section_title('DATACENTER', 8);
	$th = array();
	array_push($th, 'Description','Barcode','Code','UOM','QTY','SRP','LastModified','IssueFound');
	table_header($th,"width=95%");
	while($row = mssql_fetch_array($res1))
	{
	start_row();
	alt_table_row_color($k);
	label_cell($row['Description1']);
	label_cell($row['Barcode1']);
	label_cell($row['PriceModeCode1']);
	label_cell($row['uom1']);
	label_cell($row['qty1']);
	label_cell($row['srp1']);
	label_cell($row['LastDateModified1']);
	$duplicate=check_double_server_barcode($row['Barcode2'],$myDB);
	if ($duplicate>0){
	label_cell("<font color='red'>Barcode has Duplicate.</font>");
	}
	else if ($row['uom1'] !=$row['uom2']){
	label_cell("<font color='green'>UOM from DataCenter is not the same with to branch.</font>");
	}
	else if  ($row['srp1'] !=$row['srp2']){
	label_cell("<font color='orange'>The SRP is not Updated to branch.</font>");
	}
	end_row();
	}


	table_section(2, "50%");
	table_section_title('BRANCH', 8);
	$th = array();
	array_push($th, 'Description','Barcode','Code','UOM','QTY','SRP','LastModified','IssueFound');
	table_header($th,"width=95%");
	while($row2 = mssql_fetch_array($res2))
	{
	start_row();
	alt_table_row_color($k);
	label_cell($row2['Description2']);
	label_cell($row2['Barcode2']);
	label_cell($row2['PriceModeCode2']);
	label_cell($row2['uom2']);
	label_cell($row2['qty2']);
	label_cell($row2['srp2']);
	label_cell($row2['LastDateModified2']);
	$duplicate=check_double_branch_barcode($row2['Barcode2']);
	if ($duplicate>0){
	label_cell("<font color='red'>Barcode has Duplicate.</font>");
	}
	else if ($row2['uom1'] !=$row2['uom2']){
	label_cell("<font color='green'>UOM from DataCenter is not the same with to branch.</font>");
	}
	else if  ($row2['srp1'] !=$row2['srp2']){
	label_cell("<font color='orange'>The SRP is not Updated to branch.</font>");
	}
	else {
	label_cell('Unkown Issue.');
	}
	end_row();
	}
	end_outer_table(1); // outer table

br();
br();
div_end();
end_form();
end_page();
?>