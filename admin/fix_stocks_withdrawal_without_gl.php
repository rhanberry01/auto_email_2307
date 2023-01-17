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

	
page('Fix Stock transfer without GL ENTRIES', false, false, "", $js);
set_time_limit(0);
//-----------------------------------------------------------------------------------

if (isset($_POST['fix_now']))
{
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	$m_type=ST_STOCKS_WITHDRAWAL;
	
	$sql = "SELECT 
	[MovementID]
	,[MovementNo]
	,[MovementCode]
	,CAST ([TransactionDate] as Date) as TransactionDate
	,[NetTotal]
	,[Remarks]
	FROM [Movements]
	where MovementCode='SW'
	and cast(PostedDate as Date)>='2017-01-01'
	and cast(PostedDate as Date)<='2017-12-31'";
	$res = db_query($sql);
	
	while($row = mssql_fetch_array($res))
	{
			$tax_rate = 12;
		
			$MovementID=$row['MovementID'];
			$MovementNo=$row['MovementNo'];
			$MovementCode=$row['MovementCode'];
			$TransactionDate=$row['TransactionDate'];
			$NetTotal=$row['NetTotal'];
			$Remarks=$row['Remarks'];

			$sqlx = "SELECT 
			sth.aria_trans_no_out,
			sth.nature_of_req,
			sth.br_code_out,
			sth.br_code_in,
			sth.m_id_out,
			rn.gl_debit
			FROM transfers.0_stocks_withdrawal_header as sth
			LEFT JOIN transfers.0_request_nature as rn
			ON sth.nature_of_req=rn.id
			WHERE date_posted>='2017-01-01'
			and date_posted<='2017-12-31'
			AND m_id_out = '$MovementID'";
			$resx = db_query($sqlx);
			
			while($rowx = db_fetch($resx))
			{
				$nature_of_req=$rowx['nature_of_req'];
				$br_code_out=$rowx['br_code_out'];
				$br_code_in=$rowx['br_code_in'];
				$m_id_out=$rowx['m_id_out'];
				$gl_debit=$rowx['gl_debit'];
	
						$NetofVat=$NetTotal / (1+($tax_rate/100));
						add_gl_trans(ST_STOCKS_WITHDRAWAL, $rowx['aria_trans_no_out'], sql2date($row['TransactionDate']), $row['gl_debit'], 0, 0, ".db_escape($Remarks).", abs($NetofVat));
						add_gl_trans(ST_STOCKS_WITHDRAWAL, $rowx['aria_trans_no_out'], sql2date($row['TransactionDate']), '570004', 0, 0, ".db_escape($Remarks).", -abs($NetofVat));
			}
	}
	
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'GO');
end_form();

end_page();
?>
