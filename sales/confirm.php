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
	$can_process = 1;
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
	
	// $company = "SELECT * FROM ".TB_PREF."company";
	// echo $company;
	// $company = db_query($company);
	// $a = db_fetch($company);
	
	// $a1 = $a['allow_negative_stock'];
	// $a2 = $a['allow_credit_limit'];
	// $a3 = $a['allow_so_edit'];
	// $a4 = $a['allow_so_approval'];
	// $a5 = $a['allow_voiding'];
	// $a6 = $a['allow_po_edit'];
	// $a7 = $a['allow_po_approval'];
	
	//echo $sql;
	
	$sql = "SELECT * 
			  FROM ".TB_PREF."users 
			  WHERE user_id = BINARY(".db_escape($uname)." )
			  AND password  LIKE ".db_escape(md5($passwd))." 
			  AND is_supervisor = 1";

		if($ty==6){
			$sql .= " AND can_negative_inv = 1";
			$sql .= " AND can_credit_limit = 1";
			$sql .= " AND can_details = 1";
			
			// if($a1 || $can_process!=0)
				// $can_process = $a1;
			// elseif($a2 || $can_process!=0)
				// $can_process = $a2;
			// elseif($a3 || $can_process!=0)
				// $can_process = $a3;
				
		}		
		else if($ty==5){
			$sql .= " AND can_credit_limit = 1";
			$sql .= " AND can_details = 1";
		
			// if($a2 || $can_process!=0)
				// $can_process = $a2;
			// elseif($a3 || $can_process!=0)
				// $can_process = $a3;
		}	
		else if($ty==7){
			$sql .= " AND can_negative_inv = 1";
			$sql .= " AND can_details = 1";
			
			// if($a1 || $can_process!=0)
				// $can_process = $a1;
			// elseif($a3 || $can_process!=0)
				// $can_process = $a3;
		}
		else if($ty==3){
			$sql .= " AND can_negative_inv = 1";
			$sql .= " AND can_credit_limit = 1";
		
			// if($a1 || $can_process!=0)
				// $can_process = $a1;
			// elseif($a2 || $can_process!=0)
				// $can_process = $a2;
		}
		else if($ty==2){
			$sql .= " AND can_negative_inv = 1";
			
			// if($a1 || $can_process!=0)
				// $can_process = $a1;
			
		}else if($ty == 4){
			$sql .= " AND can_details = 1";
		
			// if($a3)
				// $can_process = $a3;
		
		}else if($ty == 8){
			$sql .= " AND can_details = 1";
			
			// if($a3)
				// $can_process = $a3;
			
		}else{
			$sql .= " AND can_credit_limit = 1";
		
			// if($a2)
				// $can_process = $a2;
		
		}
	$sql = db_query($sql);
	$count = db_num_rows($sql);
	// if($can_process!=0)
	echo $count;
	// else
	// echo 0;
	
	if($count!=0)
	{
		
		$_SESSION['logged_uname'] = $uname;
		if($ty==6){
		$_SESSION['allownegativecost']=1;
		$_SESSION['allowcredit']=1;
		$_SESSION['cdetails']=1;
		}		
		else if($ty==5){
	//	$_SESSION['allownegativecost']=1;
		$_SESSION['allowcredit']=1;
		$_SESSION['cdetails']=1;
		}	
		else if($ty==7){
		$_SESSION['allownegativecost']=1;
	//	$_SESSION['allowcredit']=1;
		$_SESSION['cdetails']=1;
		}
		else if($ty==8){
		$_SESSION['ddetails']=1;
		}
		else if($ty==3){
		$_SESSION['allownegativecost']=1;
		$_SESSION['allowcredit']=1;
		}
		else if($ty==2)
		$_SESSION['allownegativecost']=1;
		else if($ty == 4)
		$_SESSION['cdetails']=1;
		else
		$_SESSION['allowcredit']=1;
		// display_error('asd'):
		
		//echo true;
	}
	}
	
	
	// if(isset(noti_))
	// $sql = "UPDATE ".TB_PREF."company
			// SET allow_negative_stock = 1";
	// db_query($sql);
	
?>