<?php 

	mysql_connect("127.0.0.1", "root", "srsnova");
	mysql_select_db("srs_aria_nova");
	if($_POST['sname']){
		$sql_ = "SELECT * FROM srs_aria_nova.0_sdma_supplier WHERE reference='SRSSAF".$_POST['supp_id']."'";
		$result_ = mysql_query($sql_);
		$row_ = mysql_fetch_array($result_);
		if ($row_ == 0){
			$sql = "INSERT INTO srs_aria_nova.0_sdma_supplier(reference,supplierName) VALUES('SRSSAF".$_POST['supp_id']."','".$_POST['sname']."')";
			//return $sql; 
			 mysql_query($sql);
		// display_error($sql);
		//db_query($sql,'failed to add DM Agreement');
		}else{
			$sql = "UPDATE srs_aria_nova.0_sdma_supplier SET supplierName = '".$_POST['sname']."' WHERE reference='SRSSAF".$_POST['supp_id']."'";
			mysql_query($sql);
		}
	}
	$result = array();
	if($_POST['simg_data']){
		$imagedata = base64_decode($_POST['simg_data']);
		$supp_id = $_POST['supp_id'];
		$filename = 'Sup(SAF'.$supp_id.')';
		$file_name = './doc_signs/'.$filename.'.png';
		file_put_contents($file_name,$imagedata);
		$result['status'] = 1;
		$result['file_name'] = $file_name;
		echo json_encode($result);
	}/* else{
		$imagedata = base64_decode($_POST['pimg_data']);
		$supp_id = $_POST['supp_id'];
		$filename = 'Pur(SAF'.$supp_id.')';
	} */
	//Location to where you want to created sign image
	
?>