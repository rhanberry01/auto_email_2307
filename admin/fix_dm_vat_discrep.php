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

	
page('Fix DM Vat Discrep', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

if (isset($_POST['fix_now']))
{
	
	
		$sql = "select tran_date,type,type_no,amount from (SELECT tran_date,type,type_no,sum(amount) as amount 
		FROM 0_gl_trans
		where type=53
		AND tran_date >= '".date2sql($_POST['from'])."' AND tran_date <= '".date2sql($_POST['to'])."'
		GROUP BY type,type_no) as a
		where amount<-1";
		$res=db_query($sql,'failed to get gl discrep.');
		
		while($row = db_fetch($res))
		{
			$type=$row['type'];
			$type_no=$row['type_no'];
			
			// reverse credit of APV
			$sql_x = "SELECT ROUND(sum(amount),2) as amount  FROM 0_gl_trans WHERE type = $type AND type_no = $type_no AND amount < 0 and account!='2000'";
			$res_x = db_query($sql_x);
			while($row_x = db_fetch($res_x))
			{
				
				
					$tran_date=sql2date($row_x['tran_date']);
					
					$accounts_payable=ABS($row_x['amount']); //accounts payable
					// $purchase_vat=ROUND($accounts_payable/1.12,2); //purchases
					// $input_tax=ROUND($accounts_payable-$purchase_vat,2); //input tax
					
					// display_error($accounts_payable);
					// display_error($purchase_vat);
					// display_error($input_tax);
					
					

					$sql_purch_1 = "UPDATE 0_gl_trans SET amount= $accounts_payable WHERE type = $type AND type_no = $type_no AND amount > 0 and account='2000'";
					$res_purch=db_query($sql_purch_1);
					//display_error($sql_purch_1);

					// $sql_purch_2 = "UPDATE 0_supp_trans SET ov_amount= $accounts_payable WHERE type = $type AND trans_no = $type_no";
					// db_query($sql_purch_2);
					// //display_error($sql_purch_2);
					

			}
		
		
		}
	

	
	display_notification('SUCCESS!!');
}

start_form();
start_table();

if (!isset($_POST['from']))
	$_POST['from'] = '01/01/'.date('Y');

date_row('Date From:', 'from');
date_row('Date To:', 'to');
end_table(1);
submit_center('fix_now', 'Fix DM VAT Discrep');
end_form();

end_page();
?>
