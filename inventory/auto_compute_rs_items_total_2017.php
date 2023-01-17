<?php
/**
 * Title: Import Per Branch RS to Centralized RS
 * Description: Auto Import [RS] Returned Merchandise (RS Header, Items)
 * 				per branch to Centralized RS Database.
 * Author: AJSP 
 * Date: 08/29/2017 
 */
 
ini_set('memory_limit', '-1');

// MySQL Custom Settings
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 

// MSSQL Custom Settings
ini_set('mssql.connect_timeout', 0);
ini_set('mssql.timeout', 0);
ini_set('mssql.textlimit', 2147483647);
ini_set('mssql.textsize', 2147483647);

set_time_limit(0);   
  

$seconds = 15;
if (isset($_GET['sitrololo'])) 
	header('Refresh: '.$seconds);

$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Auto Compute RM Items Total (Y.2017)"), false, false, "", $js);

function ping_conn($host, $port=1433, $timeout=2) {
	$fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
	if (!$fsock) 
		return false;
	else
		return true;
}

function notify($msg, $type=1) {
	if ($type == 2) {
		echo '<div class="msgbox">
				<div class="err_msg">
					' . $msg . '
				</div>
			</div>';
	}
	elseif ($type == 1) {
	 	echo '<div class="msgbox">
				<div class="note_msg">
					' . $msg . '
				</div>
			</div>';
	 }
}

function get_rs_items_total($rs_id, $branch_id) {	
	$sql = "SELECT REPLACE(FORMAT(SUM(FORMAT(price*qty, 3)),3), ',', '') AS items_total FROM centralized_returned_merchandise.".TB_PREF."rms_items
			WHERE rs_id=$rs_id 
			AND branch_id='".$branch_id."'";
	$query = db_query($sql);
	$result = db_fetch($query);
	
	return $result['items_total'];
}


if (isset($_POST['ImportRS']) OR isset($_GET['sitrololo'])) {
	
	$preloader_gif = $path_to_root.'/themes/modern/images/ajax-loader.gif';

	echo "<div id='ploader' style='display:none'>
			<img src='$preloader_gif'>
		</div>";

	display_notification("Auto Compute Started");

	if (isset($_POST['ImportRS'])) {
		meta_forward($_SERVER['PHP_SELF'], "sitrololo=ayasawanitralala");
	}

	$sql = "SELECT rs_id, branch_id 
			FROM centralized_returned_merchandise.0_rms_header
			WHERE items_total IS NULL
			AND rs_date >= '2017-01-01'
			LIMIT 1000";
	$query = db_query($sql);

	$items_total = 0;
	while ($row = mysql_fetch_array($query)) {
		$items_total = get_rs_items_total($row['rs_id'], $row['branch_id']);
		if (is_null($items_total))
			$items_total = 0;
		db_query("BEGIN", "could not start a transaction");
		$rms_sql = "UPDATE centralized_returned_merchandise.0_rms_header
					SET items_total = $items_total
					WHERE rs_id = '".$row['rs_id']."'
					AND branch_id = '".$row['branch_id']."'";
		$rms_query = db_query($rms_sql);
		// notify(mysql_error($rms_query), 2);
		if (mysql_affected_rows() > 0) {
			notify($row['rs_id'] . " is updated.");
			db_query("COMMIT");
		}
		else {
			// notify(mysql_error($rms_query), 2);
			notify($row['rs_id'] . " cannot be updated.", 2);
			db_query("ROLLBACK", "transaction has been canceled");
		}
	}

}


// Start Form
start_form();

if (!isset($_GET['sitrololo'])) 
	submit_center('ImportRS', '<b>Start Auto Compute and Insert RM Items Total</b>');

end_form();
end_page();