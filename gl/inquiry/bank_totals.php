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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root="../..";

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Supplier Agreements"), false, false, "", $js);

//----------------------------------------------------------------------------------------------------
function get_monthly_bank_total($aria_db, $account, $year)
{
					// SUM(amount)
	$sql = "SELECT
					SUM(if(tran_date >= '$year-01-01' AND tran_date < '$year-02-01',amount,0)) as jan,
					SUM(if(tran_date >= '$year-02-01' AND tran_date < '$year-03-01',amount,0)) as feb,
					SUM(if(tran_date >= '$year-03-01' AND tran_date < '$year-04-01',amount,0)) as mar,
					SUM(if(tran_date >= '$year-04-01' AND tran_date < '$year-05-01',amount,0)) as apr,
					SUM(if(tran_date >= '$year-05-01' AND tran_date < '$year-06-01',amount,0)) as may,
					SUM(if(tran_date >= '$year-06-01' AND tran_date < '$year-07-01',amount,0)) as jun,
					SUM(if(tran_date >= '$year-07-01' AND tran_date < '$year-08-01',amount,0)) as jul,
					SUM(if(tran_date >= '$year-08-01' AND tran_date < '$year-09-01',amount,0)) as aug,
					SUM(if(tran_date >= '$year-09-01' AND tran_date < '$year-10-01',amount,0)) as sep,
					SUM(if(tran_date >= '$year-10-01' AND tran_date < '$year-11-01',amount,0)) as oct,
					SUM(if(tran_date >= '$year-11-01' AND tran_date < '$year-12-01',amount,0)) as nov,
					SUM(if(tran_date >= '$year-12-01' AND tran_date <= '$year-12-31',amount,0)) as 'dec'
				FROM
					$aria_db.0_gl_trans
				WHERE
					account = '$account'
				AND tran_date >= '$year-01-01'
				AND tran_date <= '$year-12-31'";
				// display_error($sql);die;
	$res = db_query($sql);
	$row = db_fetch($res);
	
	for ($i=0; $i<=11; $i++)
		amount_cell(round($row[$i],2));
}

start_form();
if (!isset($_POST['year']))
	$_POST['year'] = date('Y',strtotime('-1 year'));

echo "<center>";

text_cells('Year : ', 'year');

$bank_array = array(
'1020021' => 'Cash in bank MB (CA) 255-7-255-52145-0',
'10102299' => 'Cash in Bank AUB 001-01-009229-9',
'1010040'	=> 'Cash in Bank - BPI  RSI Nova 4540-0055-89');

echo "<td>Bank :</td>\n";
echo "<td>";
echo array_selector('bank_acc', null, $bank_array, 
		array()); 
echo "</td>\n";

submit_cells('proceed','Search');


// if (!isset($_POST['proceed']))
	// display_footer_exit();

br(2);

echo "</center>";

start_table($table_style2.' width=90%');

$k = 0;

$th = array('Branch','January','February','March','April','May','June','July','August','September','October','November','December');
table_header($th);

foreach($db_connections as $key=>$db_con)
{
	// if($key != 5) // for checking a branch
	if (!isset($db_con['dimension_ref']) OR $db_con['dimension_ref'] === '')
		continue;
	
	alt_table_row_color($k);
	label_cell(strtoupper($db_con['srs_branch']), ' class=tableheader');
	get_monthly_bank_total($db_con['dbname'], $_POST['bank_acc'], $_POST['year']);
	end_row();
}


end_table('');

end_form();

end_page();

?>