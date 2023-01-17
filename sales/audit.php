<?php

	$type = $_POST['type'];
	$trans_no = $_POST['trans_no'];
	$user = $_POST['user'];
	$date  = $_POST['stamp'];
	$desc = $_POST['description'];
	$fiscal = $_POST['fiscal'];
	$gl_date = $_POST['gl_date'];
	$seq = $_POST['seq'];
	
	//opt 1 = credit limit
	$opt = $_POST['opt'];
	
	switch($opt){
		case 1 : $sql = "INSERT INTO ".TB_PREF."audit_trail(user,stamp,description) VALUES 
						  (".db_escape($user).",
						   ".db_escape($stamp).",
						   ".db_escape($desc)."
						  )";
						  echo $sql;
	}
	// db_query($sql);

?>