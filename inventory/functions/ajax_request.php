<?php
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");


if(isset($_POST['xaction'])){
	switch ($_POST['xaction']){
		case 'update_checked':
			$xid=$_POST['xid'];
			$xuser=$_POST['xuser'];
			$check="SELECT tagging_checked_by,id FROM transfers.0_transfer_header WHERE id = '{$xid}' AND tagging_checked_by != '' ";
			$checkrows=db_query($check,'error');
			if(mysql_num_rows($checkrows)){
				$sql="UPDATE transfers.0_transfer_header SET tagging_checked_by = '' WHERE id = '{$xid}' ";
				$res=db_query($sql,"error");
				if($res){
					echo 'Successfully updated';
				}else{
					echo db_query($sql,"error");
				}
			}else{
				$sql="UPDATE transfers.0_transfer_header SET tagging_checked_by = '{$xuser}' WHERE id = '{$xid}' ";
				$res=db_query($sql,"error");
				if($res){
					echo 'Successfully updated';
				}else{
					echo db_query($sql,"error");
				}
			}
		break;

		
		
		default:
		break;
	}
}