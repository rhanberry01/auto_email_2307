<?php

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");

$uname = $_POST['uname'];
$passwd = $_POST['passwd'];
$type = $_POST['type'];

$sql = "SELECT password, is_supervisor
			FROM 0_users
			WHERE user_id LIKE '$uname' AND can_void=1";
// echo('the type = '.$type);

// filter vioding by type

$query = db_query($sql);

if(db_num_rows($query) > 0){
	$row = mysql_fetch_object($query);
	$pass = $row->password;
	$is_sup = $row->is_supervisor;
	
	if(md5($passwd) == $pass){
		if($is_sup == 1){
			echo "1";
		}else{
			echo "3";
		}
	}else{
		echo "2"; //Password mismatch
	}
}else{
	echo "0"; //User does not exist
}

?>