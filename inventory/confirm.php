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

	$uname  = $_POST['uname'];
	$passwd = $_POST['passwd'];
	//$noti_  = $_POST['noti_'];
	$ty = $_POST['ty'];
	if(!isset($passwd)){
		echo "
			<script>
				alert(' --- ' + ".$passwd." + ' --- ');
			</script>
		";
	}
	
	// $sql = "UPDATE ".TB_PREF."company
			// SET allow_negative_stock = ".$noti_;
	// db_query($sql);
	
	// if(isset($_POST['credit_']) && isset($_POST['hid_debtor_no'])){
		// $credit_  = $_POST['credit_'];
		// $hid_debtor_no  = $_POST['hid_debtor_no'];
		
		// if($credit_)
		// $sql = "UPDATE ".TB_PREF."debtors_master 
			// // SET allow_credit = ".$credit_."
			// // WHERE debtor_no = ".$hid_debtor_no;
		// // echo $sql;
		// // db_query($sql);
	// }
	
	if(isset($uname) && isset($passwd)){
	
	//add_audit_trail(SA_SALESINVOICE,$invoice_no,Today(),'Supervisor Approval');
	

	
	//echo $sql;
	
	$sql = "SELECT * 
			  FROM ".TB_PREF."users 
			  WHERE user_id = BINARY(".db_escape($uname)." )
			  AND password  LIKE ".db_escape(md5($passwd))." 
			  AND is_supervisor = 1";
	//echo $sql;

		if($ty==6){
			$sql .= " AND can_negative_inv = 1";
			$sql .= " AND can_credit_limit = 1";
			$sql .= " AND can_details = 1";

		}		
		else if($ty==5){
			$sql .= " AND can_credit_limit = 1";
			$sql .= " AND can_details = 1";
		}	
		else if($ty==7){
			$sql .= " AND can_negative_inv = 1";
			$sql .= " AND can_details = 1";
		}
		else if($ty==3){
			$sql .= " AND can_negative_inv = 1";
			$sql .= " AND can_credit_limit = 1";
		}
		else if($ty==2){
			$sql .= " AND can_negative_inv = 1";
		}else if($ty == 4){
			$sql .= " AND can_details = 1";
		}else if($ty == 8){
			$sql .= " AND can_details = 1";
		}else{
			$sql .= " AND can_credit_limit = 1";
		}
	// echo $sql;
	//$query = 'asdasd';
	$sql = db_query($sql);
	$count = db_num_rows($sql);
	echo $count;
	
	if($count!=0)
	$_SESSION['allownegativecost']=1;
		// if($count!=0)
		// {
			
			// $_SESSION['logged_uname'] = $uname;
			// if($ty==6){
			// $_SESSION['allownegativecost']=1;
			// $_SESSION['allowcredit']=1;
			// $_SESSION['cdetails']=1;
			// }		
			// else if($ty==5){
		// //	$_SESSION['allownegativecost']=1;
			// $_SESSION['allowcredit']=1;
			// $_SESSION['cdetails']=1;
			// }	
			// else if($ty==7){
			// $_SESSION['allownegativecost']=1;
		// //	$_SESSION['allowcredit']=1;
			// $_SESSION['cdetails']=1;
			// }
			// else if($ty==8){
			// $_SESSION['ddetails']=1;
			// }
			// else if($ty==3){
			// $_SESSION['allownegativecost']=1;
			// $_SESSION['allowcredit']=1;
			// }
			// else if($ty==2)
			// $_SESSION['allownegativecost']=1;
			// else if($ty == 4)
			// $_SESSION['cdetails']=1;
			// else
			// $_SESSION['allowcredit']=1;
			// // display_error('asd'):
			
			// //echo true;
		// }
	}
	
	
	// if(isset(noti_))
	// $sql = "UPDATE ".TB_PREF."company
			// SET allow_negative_stock = 1";
	// db_query($sql);
	
?>