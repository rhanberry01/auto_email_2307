<?php
$page_security = 'SA_CHECKPRINT';
// ----------------------------------------------------------------
// $ Revision:	1.0 $
// Creator:	Tu Nguyen
// date_:	2008-08-04
// Title:	Print CPA Cheques (Canadian Pre-printed Standard)
// ----------------------------------------------------------------

$path_to_root="../..";

include($path_to_root . "/includes/session.inc");

// header('Content-Type: text/csv; charset=utf-8');

$csv_id = $id = $_GET['id'];

$sql = "SELECT * FROM ".TB_PREF."csv
		WHERE id = $id";
$res = db_query($sql);
$row = db_fetch($res);

global $db_connections;
// display_error($db_connections[0][1]);
$filename = strtoupper($db_connections[$_SESSION['wa_current_user']->company]['srs_branch']).'_BPS_batch_'.$id.'.csv';
$target = $path_to_root.'/csv/'.$row['csv_file'];
$real_file_name = $row['csv_file'];

if (file_exists($target))
	unlink($target);

if (!file_exists($target))
{
	$co = get_company_prefs();

	$prefix = 'for_op';

	$count = 0;
	$csv_details = array();

	$billing_institution_code = '999999';
	$subscriber_name = '';
	$subscriber_number = '';


	$list = array();
	$total=0;
	
	$sql = "SELECT * FROM ".TB_PREF."csv_details
			WHERE csv_id = $id";
	// display_error($sql);
	$res = db_query($sql);
	$count = 0;
	while($row = db_fetch($res))
	{
		$cv_header = get_cv_header($row['cv_id']);
		// $to = payment_person_name($cv_header['person_type'],$cv_header['person_id'], false);
		$to = html_entity_decode(get_supplier_pay_to($cv_header['person_id']));
		$supplier_row = get_supplier($cv_header['person_id']);
		
		$cv_header['amount'] = round($cv_header['amount'],2);
		
		$subscriber_name = $to;
		$subscriber_number = $supplier_row['supp_ref'];
		
$account_number=229100002636;
// $account_number=sprintf($account_number);


		$list[] = array('H', $account_number,Today(),$supplier_row['billing_institution_code'], $to, //preg_replace("/\([^)]+\)/","",$to),
						number_format($cv_header['amount'],2,'.',''), $subscriber_name, $subscriber_number, 
						'CV'.$cv_header['cv_no'],'','','','','','','','N','','','',$csv_id);
		$total += $cv_header['amount'];
		$count ++;
	}
	$list[] = array('S',$count,number_format($total,2,'.',''),$csv_id,'','','','','','','','','','','','','','','','','');

	$fp = fopen($path_to_root.'/csv/'.$real_file_name, 'w');

	foreach ($list as $fields) {
		fputcsv($fp, $fields);
	}

	fclose($fp);
}

header('Content-Disposition: attachment; filename='.$filename);
readfile($target);

if (file_exists($target))
	unlink($target);
?>