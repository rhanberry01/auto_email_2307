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
$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/ui.inc");
simple_page_mode(false);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Cash Deposit"), false, false, "", $js);


//----------------------------------------------------------------------------------
function write_cash_deposit($selected,$date_remit,$cash_remit,$cash_deposit,$date_deposit)
{
		$sql = "INSERT INTO ".TB_PREF."cash_deposit
				(date_remit, cash_remit, cash_deposit, date_deposit) 
			VALUES('".$date_remit."', '".$cash_remit."', '".$cash_deposit."','".$date_deposit."')";

		db_query($sql,"Cash Deposit could not be saved.");
		//display_error($sql);
}

if (isset($_POST['save'])) 
{
$remit_date=$_POST['date_remit'];
global $Ajax;
	//initialise no input errors assumed initially before we test
	$input_error = 0;
	

	if (strlen($_POST['cash_deposit']) == 0)
	{
		$input_error = 1;
		display_error(_("The Amount to Deposit cannot be empty."));
		set_focus('cash_deposit');
	}
	
	if (($_POST['cash_deposit']) == 0)
	{
		$input_error = 1;
		display_error(_("The Amount to Deposit cannot be zero."));
		set_focus('cash_deposit');
	}
	


	if ($input_error !=1) {

	
		$sqlgl="select * from ".TB_PREF."cash_deposit_details where date_remit='$remit_date'";
		$resultgl=db_query($sqlgl);
		$exist=db_num_rows($resultgl);
	 
	 if (($_POST['cash_deposit']!='0') and ($exist<=0)){
		write_cash_deposit($selected_id,$_POST['date_remit'],$_POST['cash_remit'],$_POST['cash_deposit'],date2sql($_POST['date_deposit']));
		display_notification(_('New Cash Deposit has been added.'));
		
		$sqlgl="select date_remit,cash_remit, sum(cash_deposit) as cash_deposit from ".TB_PREF."cash_deposit where date_remit='$remit_date'";
		$resultgl=db_query($sqlgl);
		//display_error($sqlgl);
		while ($cashglrow = db_fetch($resultgl))
		{
		$gldate_remit=$cashglrow["date_remit"];
		$glcash_remit=$cashglrow["cash_remit"];
		$glcash_deposit=$cashglrow["cash_deposit"];
		}	

		$sqlcash_account="select cash_account, cash_in_bank from ".TB_PREF."sales_gl_accounts";
		$result_cash_account=db_query($sqlcash_account);
		while ($accountrow = db_fetch($result_cash_account))
		{
		$cash_in_transit=$accountrow["cash_account"];
		$cash_in_bank=$accountrow["cash_in_bank"];	
		}

		if ($glcash_deposit>=$glcash_remit)
		{

		$processed=1;
		$sql = "INSERT INTO ".TB_PREF."cash_deposit_details
				(date_remit,total_cash, processed) 
			VALUES('".$gldate_remit."', '".$glcash_remit."','".$processed."')";

		db_query($sql,"Cash Deposit could not be saved.");
		//display_error($sql);
		
		
		$sqlid_details="select ct_id from ".TB_PREF."cash_deposit_details order by ct_id asc";
		$result_id_details=db_query($sqlid_details);
		
		while ($cash_id_det_row = db_fetch($result_id_details))
		{
		// $id_count=db_num_rows($sqlid_details);
		// if ($id_count<=1)
		// {
		$c_id=$cash_id_det_row['ct_id'];
		// }
		// else {
		// $c_id=++$cash_id_det_row['ct_id'];
		// }
		}
		
$date_from =date2sql($_POST['date_']);
$sqldc = "select * from ".TB_PREF."cash_deposit where date_remit='$date_from' order by d_id asc";
$resultdc=db_query($sqldc);
//display_error($sqldc);
while($rowdc = db_fetch($resultdc))
{
$d_id[]=$rowdc['d_id'];
$cash_deposit[]=$rowdc['cash_deposit'];
$date_deposit[]=$rowdc['date_deposit'];
}

for($i = 0; $i<count($d_id); $i++) //Getting total # var submitted and running loop 
{ 
$r_cash_deposit =  $cash_deposit[$i]; 
$r_date_deposit =  $date_deposit[$i]; 
$r_d_id =  $d_id[$i]; 

if ($r_d_id != "0") {

$date=sql2date($r_date_deposit);
		if (($glcash_deposit>$glcash_remit) and ($i+1==count($d_id)))
		{
		$t_over_deposit=$glcash_deposit-$glcash_remit;
		$r_cash_deposit=$r_cash_deposit-$t_over_deposit;
		add_gl_trans(ST_CASHDEPOSIT, $c_id, $date, $cash_in_bank, 0, 0, $memo, $r_cash_deposit, null, 0);
		add_gl_trans(ST_CASHDEPOSIT, $c_id, $date, $cash_in_transit, 0, 0, $memo, -$r_cash_deposit, null, 0);
		}
		
		else {
		add_gl_trans(ST_CASHDEPOSIT, $c_id, $date, $cash_in_bank, 0, 0, $memo, $r_cash_deposit, null, 0);
		add_gl_trans(ST_CASHDEPOSIT, $c_id, $date, $cash_in_transit, 0, 0, $memo, -$r_cash_deposit, null, 0);
		}
}		
}
		if ($glcash_deposit>$glcash_remit)
		{
		$t_over_deposit=$glcash_deposit-$glcash_remit;
		add_gl_trans(ST_CASHDEPOSIT, $c_id, $date, $cash_in_bank, 0, 0, $memo, $t_over_deposit, null, 0);
		add_gl_trans(ST_CASHDEPOSIT, $c_id, $date, 4020, 0, 0, $memo, -$t_over_deposit, null, 0);
		}
	
				
}
		// $trans_id = get_next_trans_no(ST_COSTUPDATE);	
		// add_gl_trans(ST_CASHDEPOSIT, $c_id, Today(), $cash_account, 0, 0, $memo, $glcash_remit, null, 0);
		// add_gl_trans(ST_CASHDEPOSIT, $c_id, Today(), $cash_account, 0, 0, $memo, -$glcash_remit, null, 0);
		$Mode = 'RESET';
		
	}
	}
	$Ajax->activate('table_');	
}

//---------------------START OF DELETE--------------------------------
if ($Mode == 'Delete')
{
	$gl_type_del=ST_CASHDEPOSIT;
	$sql_del="delete from ".TB_PREF."cash_deposit where d_id=".db_escape($selected_id)."";
	db_query($sql_del);

	$date_from =date2sql($_POST['date_']);
	$sqlsel="select * from ".TB_PREF."cash_deposit_details where date_remit='$date_from'";
	$result=db_query($sqlsel);
	//display_error($sqlsel);
	$num=db_num_rows($result);
	
	while ($myrow = db_fetch($result))
	{
	$type_del=$myrow['ct_id'];
	}
	
	if($type_del>=1)
	{
	$sql_delgl="delete from ".TB_PREF."gl_trans where type='$gl_type_del' and type_no='$type_del'";
	db_query($sql_delgl);
	//display_error($sql_delgl);
	}
	
	if ($num>=1)
	{
	$sql_del="delete from ".TB_PREF."cash_deposit_details where date_remit='$date_from'";
	db_query($sql_del);
	}
	display_notification(_('Selected Cash Deposit has been Deleted..'));
	$Mode = 'RESET';
	//display_error($sql_del);
}
//------------------------END OF DELETE-------------------------------------------


//------------------------START DISPLAYING DATA TO TABLE-------------------------------------------
start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	date_cells(_("Date:"), 'date_', '', null, 0);
	// date_cells(_("To:"), 'TransToDate', '', null);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'));
end_row();
end_table(1);
br();

$date_from =date2sql($_POST['date_']);
$sqltcash="select * from ".TB_PREF."salestotals where ts_date_remit='$date_from'";
//display_error($sqltcash);
$cashresult=db_query($sqltcash);
while ($cashrow = db_fetch($cashresult))
{
$total_cash_remit=$cashrow["ts_cash"];
start_table();
start_row();
label_cell('<b>'.'Total Deposit in Transit: '.'</b>','nowrap');
label_cell("<font color=#880000><b>".number_format2(abs($total_cash_remit),2)."<b></font>",'align=right');
end_row();
end_table(1);
}

start_table("$table_style width=35%");
$th = array('Date Deposit','Amount Deposit',"");
inactive_control_column($th);

table_header($th);
$k = 0; //row colour counter
$balance=$total_cash_remit-$t_deposit;
$sql="select * from ".TB_PREF."cash_deposit where date_remit='$date_from' order by date_deposit desc";

$result=db_query($sql);
while ($myrow = db_fetch($result))
{

	alt_table_row_color($k);
	//label_cell($myrow["date_remit"],'nowrap');
	label_cell($myrow["date_deposit"],'nowrap');
	amount_cell($myrow["cash_deposit"]);
 	//edit_button_cell("Edit".$myrow["d_id"], _("Edit"));
	$t_deposit+=$myrow["cash_deposit"];


delete_button_cell("Delete".$myrow["d_id"], _("Delete"));
}

$balance=$total_cash_remit-$t_deposit;
end_row();

if($balance<=0)
{
$balance=0;
}
//label_cell('');
label_cell('<b>'.'TOTAL DEPOSIT:'.'</b>');
label_cell("<font color=#880000><b>".number_format2(abs($t_deposit),2)."<b></font>",'align=right');
label_cell('');

start_row();
//label_cell('');
label_cell('<b>'.'UNDEPOSIT BALANCE: '.'</b>','nowrap');
label_cell("<font color=#880000><b>".number_format2(abs($balance),2)."<b></font>",'align=right');
label_cell('');
end_table(1);

//------------------------END OF DISPLAYING DATA TO TABLE-------------------------------------------
br();
//FORM

if($balance>0)
{
start_outer_table("$table_style2 width=35%");
br();
start_table();
date_row('Date Deposit:', date_deposit);
text_row(_("Amount to Deposit:"), 'cash_deposit',$balance);
hidden('cash_remit',$total_cash_remit);
hidden('date_remit',$date_from);
hidden('hid_balance',$balance);
end_table(1);
end_outer_table(1);
//submit_add_or_update_center($selected_id == '', '', 'both',false);
submit_center('save', 'Process', "align=center", true, false,'ok.gif');
}
div_end();
end_form();
end_page();
?>