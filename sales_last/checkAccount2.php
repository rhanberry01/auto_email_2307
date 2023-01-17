<?php

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");

echo "<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />";

$uname = $_POST['uname'];
$passwd = $_POST['passwd'];

$sql = "SELECT password, is_supervisor
			FROM 0_users
			WHERE user_id LIKE '$uname'";
$query = db_query($sql);

if(db_num_rows($query) > 0){
	$row = mysql_fetch_object($query);
	$pass = $row->password;
	$is_sup = $row->is_supervisor;
	
	if(md5($passwd) == $pass){
		if($is_sup == 1){
			echo "1";
			$sql = "UPDATE ".TB_PREF."company
					SET allow_negative_stock = 1";
					//display_error($sql);
			db_query($sql);
			// echo "
				// <script>
					// tb_remove();
				// </script>
			// ";
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