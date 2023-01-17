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
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");

include($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

	
page('Journal Stock Transfer IN - MS Movement to ARIA (Net of VAT per Item)', false, false, "", $js);

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

//-----------------------------------------------------------------------------------
//FIX ONLY THE NET OF VAT IN ARIA

function adjust_gl_entry($m_id, $m_no, $nettotal,$branch,$tran_date)
{
	global $Refs;
	begin_transaction();
	
		$sql = "SELECT id,br_code_out FROM transfers.0_transfer_header WHERE m_id_in = '$m_id' AND br_code_in = '$branch' AND transfer_in_date>='2017-01-01'";
	//display_error($sql); die();
	$res = db_query($sql);
	$row = db_fetch($res);
	
	$trans_no = $row['id'];
	$br_code_out = $row['br_code_out'];
	
		$tran_date = sql2date('2017-12-31');
		//display_error($tran_date); die;
		
		$sql1 = "SELECT b.gl_stock_from
					FROM transfers.0_branches b
					WHERE b.code= '$br_code_out'
					";
					//display_error($sql1); die;
		$res1= db_query($sql1);
		$row1 = db_fetch($res1);
		
		$gl_from = $row1['gl_stock_from'];

		
		$ref   = $Refs->get_next(0);
		$memo_ = "Stock Transfer IN Transit, MovementID#: ".$m_id;
		
		$trans_type = ST_JOURNAL;
		$trans_id = get_next_trans_no($trans_type);

			add_gl_trans($trans_type, $trans_id, $tran_date, '570003', 0, 0, $memo_, $nettotal);
			add_gl_trans($trans_type, $trans_id, $tran_date, $gl_from, 0, 0, $memo_, -$nettotal);
			

		if($memo_ != '')
		{
			add_comments($trans_type, $trans_id, $tran_date, $memo_);
		}
		
		$Refs->save($trans_type, $trans_id, $ref);
		
		add_audit_trail($trans_type, $trans_id, $tran_date);
		
			
		display_notification('Sucessful Journal for transfer in movement id # '. $m_id .' movement # '. $m_no);
		//return true;
	
	commit_transaction();
}

//=========================================================

function get_net_of_vat_total($m_id, $branch)
{
	$sql = "SELECT 
	MovementLine.MovementID,
	ROUND(SUM(CASE WHEN Products.pVatable = 1
	THEN ROUND((ROUND((extended/1.12),2)),2)
	ELSE ROUND((ROUND((extended),2)),2) END),2) AS net_of_vat
	from MovementLine inner join Movements
	on MovementLine.MovementID = Movements.MovementID inner join
	Products on Products.ProductID = MovementLine.ProductID
	inner join MovementTypes on Movements.MovementCode = MovementTypes.MovementCode
	where (CAST (Movements.TransactionDate  as DATE)) >=  '2017-01-01' 
	and  Movements.status = 2
	and Movements.MovementCode='STI' 
	and Movements.MovementID ='$m_id'
	group by  MovementLine.MovementID";
	$res = ms_db_query($sql);
		
	 //display_error($sql);
	$total = 0;
	while($row = mssql_fetch_array($res))
	{
		$per_item_total = $row['net_of_vat'];
		//display_error($per_item_total);
		$total += $per_item_total;
	}
	
	return $total;
}

if (isset($_POST['fix_now']))
{
	set_time_limit(0);
	
	$sql = "
	
	SELECT DISTINCT MovementID,MovementNo,CAST (PostedDate as Date) as date from (
	SELECT m.MovementID,m.MovementNo,m.PostedDate
	FROM [Movements] as m
	LEFT JOIN [Movementline] as ml
	ON m.MovementID=ml.MovementID
	where m.MovementCode='STI' 
	and CAST (m.PostedDate as Date)>='2017-01-01'
	and m.MovementID IN (
	892640,
901143,
895236,
25160,
901879,
14765,
901915,
904075,
903059,
904065,
901283,
901310,
901312,
895077,
901926,
901927,
901929,
901949,
892647,
903196,
903840,
8304,
892654,
902416,
27804,
27805,
736196,
901355,
903056,
44838,
901588,
892651,
139555,
34286,
737116,
86476,
34287,
34288,
34290,
34294,
766880,
74906,
74902,
45358,
246987,
246988,
246767,
30161,
125798,
25637,
737484,
246990,
12723,
12725,
25939,
753363,
753364,
753497,
737041,
753547,
737042,
25725,
75079,
12250,
12252,
34540,
31758,
737443,
25562,
737071,
30203,
12254,
25566,
737448,
34512,
45201,
27492,
75083,
132549,
137545,
25691,
767131,
30192,
27499,
27496,
246844,
246847,
753442,
732773,
12721,
139756,
767151,
767152,
34545,
25770,
75106,
20736,
204873,
204875,
204874,
12256,
119509,
33548,
23591,
737457,
33430,
737458,
735194,
22895,
734885,
20720,
20711,
20735,
20717,
20713,
66760,
767149,
20726,
25088,
22897,
31763,
133421,
20737,
20738,
20715,
133235,
20709,
20816,
205925,
20723,
133238,
20728,
20739,
119090,
131720,
20733,
20730,
20740,
22894,
22896,
903203,
903200,
903198,
243264,
904072,
894024,
901129,
765434,
901268,
33583
	
	)
	) x
	

	";
	// $sql .= " AND MovementNo = '0000000146'";
	//$sql .= " AND MovementID = 34";
	$res = ms_db_query($sql);
	
	//display_error($sql);
	
	global $db_connections;
	$this_branch = $db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
	
	$nettotal = $net_of_vat_total = 0;
	while($row = mssql_fetch_array($res))
	{
		$m_id = $row[0];
		$m_no = $row[1];
		$tran_date = $row[2];
		
		if ($m_id == 0)
			continue;
		
		$net_of_vat_total = get_net_of_vat_total($m_id,$this_branch);
		adjust_gl_entry($m_id,$m_no, round($net_of_vat_total,2),$this_branch,$tran_date);
	}
	
	display_notification('SUCCESS!!');
}

start_form();
start_table($table_style2);
submit_center('fix_now', 'Process');
end_form();

end_page();
?>
