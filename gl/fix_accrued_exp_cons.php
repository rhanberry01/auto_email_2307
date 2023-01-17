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
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Fix Consignment APV Dates"), false, false, "", $js);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	//$cvid = $_GET['CV_id'];
	//$trans_type = ST_CREDITDEBITDEPOSIT;
   	display_notification_centered( _("Consignment has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	display_footer_exit();
}


//----------------------------------------------------------------------------------------
if (isset($_POST['Add'])){
set_time_limit(0);
	
	$sql= "select cs.*,cv.cv_date from 0_cons_sales_header as cs 
	LEFT JOIN 0_cv_header as cv
	on cs.cv_id=cv.id where start_date>='2015-01-01'
	and start_date<='2015-12-01'
	and cv_date>='2016-01-01'
	and invoice_num!=''
	";
	$res=db_query($sql);

while($row = db_fetch($res))
{
	$sql2= "SELECT * FROM 0_supp_trans
	where TRIM(LEADING 'INVOICE#: ' FROM supp_reference)='".$row['invoice_num']."'
	and type=20
	and supp_reference like '%INVOICE#: %'";
	if ($row['invoice_num']!=''){
		

		$res2=db_query($sql2);
			while($row2 = db_fetch($res2))
			{
				$sql2_1 = "UPDATE ".TB_PREF."gl_trans 
				SET amount='0'
				WHERE type='".$row2['type']."' and type_no='".$row2['trans_no']."'
				and account!='2000'";
				
				db_query($sql2_1,"Failed to update gl_trans.");
				//display_error($sql2_1);
				add_gl_trans(20, $row2['trans_no'], sql2date($row2['tran_date']), 2300, 0, 0, $memo_, $row2['ov_amount'],null,3,$row2['supplier_id']);
			}
			
	}
	
}
	
display_notification("Fixing Consignment date is Successful!");
}
start_form();
start_table();
start_row();
//ref_cells('Transaction #:', 'trans_no');
//date_cells('Change date deposited to :', 'date');
end_row();
end_table();
br();
start_row();
submit_center('Add',_("Fix Consignment 2015"), true, '', 'default');
end_table();
end_form();
end_page();
?>