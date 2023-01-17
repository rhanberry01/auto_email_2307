<?php 
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");

if(isset($_POST["xbr_code"]) && isset($_POST['xid'])){
	$sql = "UPDATE ".$_POST['xbr_code'].".0_bank_deposit_cheque_details SET remark = '".$_POST['xremarks']."' WHERE id = ".$_POST['xid']." ";
	$result = db_query($sql);
		if($result){
			echo "Successfully update";
		}else{
			echo 'error';
		}
}