<?php
// Test CVS

$page_security = 'SA_JOURNALENTRY';
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
// require_once $path_to_root . '/phpexcelreader/Excel/reader.php';
require_once $path_to_root . '/gl/includes/excel_reader2.php';
require_once $path_to_root . '/dimensions/includes/dimensions_db.inc';


$js = '';

if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}

page('Import Journal', false, false, "", $js);


function check_exist_statement($br_code,$bank_trans_id,$chk_number)
{
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."centralized_payment_aub_final
	WHERE br_code='$br_code' AND  bank_trans_id='$bank_trans_id' AND chk_number='$chk_number'";
	
	//display_error($sql);
	$res = db_query($sql);
	$count_row=db_num_rows($res);
	return $count_row;
}



if (isset($_POST['upload'])) 
{
	// ============================= set GL to ZERO ==================================
	
	begin_transaction();
	
	$count = 0;
	
	foreach($db_connections as $key=>$db_con)
	{
		// if($key != 22) // for checking a branch
				// continue;
		if (!isset($db_con['dimension_ref']) OR $db_con['dimension_ref'] === '')
			continue;
			
		$count ++;
		$sql = "SELECT '".$db_con['br_code']."' as br_code,bt.id as bank_trans_id,bt.type,bt.trans_no,
		chk_date, pay_to, chk_number, chk_amount,person_id
		FROM ".$db_con['dbname'].".0_cheque_details as cd
		LEFT JOIN ".$db_con['dbname'].".0_bank_trans as bt
		on cd.bank_trans_id=bt.id
		WHERE cd.chk_date >= '2016-01-01'
		AND cd.chk_date <= '2016-09-31'
		AND bt.type=22
		AND bt.amount!=0
		AND bt.bank_act=20
		ORDER BY cd.chk_date";
		
		//display_error ($sql);
		// continue;
		$res = db_query($sql);
		
		$dimension_id = get_dimension_id($db_con['dimension_ref']);
		
		display_notification($db_con['srs_branch'] .'----------------------------------------------------------------------');
		if($dimension_id==21){
			while($row = db_fetch($res))
			{
							$check_count=check_exist_statement($row['br_code'],$row['bank_trans_id'],$row['chk_number']);

							if ($check_count<=0){
							$sql = "INSERT INTO cash_deposit.".TB_PREF."centralized_payment_aub_final(br_code,bank_trans_id,type,trans_no,chk_date,pay_to,chk_number,chk_amount,supp_id,deposit_date,reconciled)				
							VALUES (".db_escape($row['br_code']).",".$row['bank_trans_id'].",".$row['type'].",".$row['trans_no'].",'".$row['chk_date']."',".db_escape($row['pay_to']).",".db_escape($row['chk_number']).",".$row['chk_amount'].",".$row['person_id'].",'0000-00-00',0)";		
							//display_error($sql);
							db_query($sql,'unable to import bank deposit statement');
							}
			}
		}
		
	}
	commit_transaction();
}

start_form();
submit_center('upload','Consolidate all checks');
end_form();

end_page();

?>