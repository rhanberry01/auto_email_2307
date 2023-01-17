<?php
	$path_to_root = "..";
include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/includes/manufacturing.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/taxes/tax_calc.inc");

// include_once($path_to_root . "/includes/types.inc");
	
	if(isset($_POST['credit_']) && isset($_POST['hid_debtor_no'])){
		$credit_  = $_POST['credit_'];
		$hid_debtor_no  = $_POST['hid_debtor_no'];
		
		if($credit_)
		$sql = "UPDATE ".TB_PREF."debtors_master 
			 SET allow_credit = ".$credit_."
			 WHERE debtor_no = ".$hid_debtor_no;
		echo $sql;
		db_query($sql);
	}
	
?>