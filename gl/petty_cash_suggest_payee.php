<?php
//Get our database abstraction file
$page_security = 'SA_DEPOSIT';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");

if (isset($_GET['search_payee']) && $_GET['search_payee'] != '') {
	//Add slashes to any quotes to avoid SQL problems.
	$search_payee = $_GET['search_payee'];
	$suggest_query = db_query("SELECT DISTINCT pcd_payee as suggest FROM 0_petty_cash_details WHERE pcd_payee like('" .$search_payee . "%') ORDER BY pcd_payee");
	while($suggest = db_fetch($suggest_query)) {
		echo $suggest['suggest'] . "\n";
	}
}
?>