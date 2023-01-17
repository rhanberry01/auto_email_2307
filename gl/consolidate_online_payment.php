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


function check_exist_statement($br_code,$bank_trans_id,$amount)
{
	$sql = "SELECT * FROM cash_deposit.".TB_PREF."centralized_payment_metro_final
	WHERE br_code='$br_code' AND  bank_trans_id='$bank_trans_id' AND chk_amount='$amount'";
	
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
		
		$sql = "
		SELECT '".$db_con['br_code']."' as br_code,c.type, c.trans_no,c.supplier_id, c.tran_date, b.trans_date, a.id,
		a.cv_no, b.id as bank_trans_id, abs(b.amount) as amount
		FROM ".$db_con['dbname'].".0_cv_header a, ".$db_con['dbname'].".0_bank_trans b, ".$db_con['dbname'].".0_supp_trans c
		WHERE online_payment='2' 
		AND a.bank_trans_id = b.id
		AND b.trans_date >= '2016-01-01'
		AND b.trans_date <= '2016-09-31'
		AND b.type = c.type
		AND b.trans_no = c.trans_no
		ORDER BY trans_date
		";
		
		//display_error ($sql);
		// continue;
		$res = db_query($sql);
		
		$dimension_id = get_dimension_id($db_con['dimension_ref']);
		
		display_notification($db_con['srs_branch'] .'----------------------------------------------------------------------');
		// if($dimension_id==21){
			while($row = db_fetch($res))
			{
							$check_count=check_exist_statement($row['br_code'],$row['bank_trans_id'],$row['amount']);

							if ($check_count<=0){
							$sql = "INSERT INTO cash_deposit.".TB_PREF."centralized_payment_metro_final(
							br_code,type,trans_no,supplier_id,supp_trans_date,bank_trans_date,chk_date,cv_id,cv_no,bank_trans_id,chk_number,chk_amount,deposit_date,reconciled,ref)				
							VALUES (".db_escape($row['br_code']).",".$row['type'].",".$row['trans_no'].",".$row['supplier_id'].",'".$row['tran_date']."','".$row['trans_date']."','0000-00-00','".$row['id']."','".$row['cv_no']."','".$row['bank_trans_id']."',0,".$row['amount'].",'0000-00-00',0,0)";		
							//display_error($sql);
							db_query($sql,'unable to import bank deposit statement');
							}
			}
		//}
		
	}
	commit_transaction();
}

start_form();
submit_center('upload','Consolidate all online payment');
end_form();

end_page();

?>