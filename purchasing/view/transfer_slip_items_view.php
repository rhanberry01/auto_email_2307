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
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/includes/date_functions.inc");

//page(_($help_context = "Transfer Slip"), true);

function connect_to_91()
{
$username = "root";
$password = "srsnova";
$hostname = "192.168.0.91"; 
// $myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
//connection to the database
$dbhandle = mysql_connect($hostname, $username, $password) 
or die("Unable to connect to $hostname");
//echo "Connected to 89<br>";

//select a database to work with
$selected = mysql_select_db("transfers",$dbhandle) 
or die("Could not select srs database.");	
}

connect_to_91();

function get_branchcode_name($br_code)
{
$sql = "SELECT name from transfers.0_branches where code='".$br_code."' ";
//display_error($sql);
$result=mysql_query($sql);
$row=db_fetch($result);
$br_name=$row['name'];
return $br_name;
}

function get_branchcode_tin($br_code)
{

$sql = "SELECT tin from transfers.0_branches where code='".$br_code."' ";
//display_error($sql);
$result=mysql_query($sql);
$row=db_fetch($result);
$tin=$row['tin'];
return $tin;
}


function transfer_slip_format($copy){
	
$trans_no=$_GET['trans_no'];
$sql = "select * from transfers.".TB_PREF."transfer_header where id='$trans_no'";
//display_error($sql);
$res = mysql_query($sql);
$row2 = db_fetch($res);

	
//start_table("style=font-size:12px align='left' width='280'");
// start_row('align=center');
// label_cell($db_connections[$_SESSION["wa_current_user"]->company]["name"],'colspan=4');
// end_row();

// start_row();
// label_cell("&nbsp;",'colspan=4');
// end_row();

//$tin=get_branchcode_tin($row2['br_code_out']);

start_row('align=center');
label_cell("<font size='2' face='arial'><b>San Roque Supermarket Retail Systems Inc.</b></font>",'colspan=3');
end_row();

start_row('align=center');
label_cell("<font size='1'>REG.TIN# 007-492-840-000</font>",'colspan=3');
end_row();


start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row('align=center');
label_cell("-$copy-",'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row('align=center');
label_cell("Transfer Slip",'colspan=3');
end_row();

start_row('align=center');
label_cell("Transfer # :$trans_no",'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row('align=left');
label_cell("Date Created : ".sql2date($row2['date_created']),'colspan=3');
end_row();

start_row('align=left','colspan=3');
label_cell("Transfer Out Date : ".sql2date($row2['transfer_out_date']),'colspan=3');
end_row();

start_row();
label_cell("--------------------------------------------------",'colspan=3 align=center');
end_row();

$from=get_branchcode_name($row2['br_code_out']);
start_row('align=left');
label_cell("From: ".$from,'colspan=3');
end_row();
$to=get_branchcode_name($row2['br_code_in']);
start_row('align=left');
label_cell("To: ".$to,'colspan=4');
end_row();

//end_table();

$sql="SELECT * FROM transfers.".TB_PREF."transfer_header as oph 
LEFT JOIN transfers.".TB_PREF."transfer_details as opd
on oph.id=opd.transfer_id
WHERE oph.id='$trans_no'";
$res = mysql_query($sql);
//display_error($sql);


//start_table("style=font-size:12px align='left' width='280'");
start_row('align=left');
label_cell("&nbsp;");
end_row();

start_row('align=left');
label_cell('PRODUCT');
label_cell('UOM',"align=right");
label_cell('QTY',"align=right");
//label_cell('U.COST',"align=right");
end_row();

$k = 0;
while($row = mysql_fetch_array($res))
{
	label_cell("<font size='1'>".$row['description']."</font>");
	label_cell($row['uom'],"align=right");
	label_cell($row['actual_qty_out'],"align=right");
	//label_cell(number_format2(abs($row['cost']),3),"align=right");
	end_row();
	$t_qty+=$row['actual_qty_out'];
	$t_cost+=$row['cost'];
	
}
start_row();
label_cell("--------------------------------------------------",'colspan=3 align=center');
end_row();

start_row();
	label_cell('TOTAL QTY: ',"align=right colspan=2");
	label_cell(number_format2(abs($t_qty)),"align=right");
	//label_cell(number_format2(abs($t_cost),3),"align=right");
end_row();

start_row();
label_cell("--------------------------------------------------",'colspan=3 align=center');
end_row();


start_row();
label_cell("&nbsp;",'colspan=3');
end_row();


start_row('align=left');
label_cell("Prepared by: ".$row2['requested_by'],'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row('align=left');
label_cell("Witnessed by:   "."______________________",'colspan=3');
end_row();

start_row('align=center');
label_cell($row2['checked_by'],'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row('align=left');
label_cell("Delivered by:   "."_______________________",'colspan=3');
end_row();

start_row('align=center');
label_cell($row2['delivered_by'],'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row('align=left');
label_cell("Received by:   "."_______________________",'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();


start_row();
label_cell("--------------------------------------------------",'colspan=3 align=center');
end_row();

}


echo "<font face=verdana>";

start_table("style=font-size:12px align='left' width='280'");

start_row();
transfer_slip_format($copy='ORIGINAL COPY');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row();
label_cell("-  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -  -",'colspan=3 align=center');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row();
label_cell("&nbsp;",'colspan=3');
end_row();

start_row();
transfer_slip_format($copy='DUPLICATE COPY');
end_row();
end_table();

//end_page(true);
?>