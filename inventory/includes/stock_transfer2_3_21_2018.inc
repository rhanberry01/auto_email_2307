<?php

function get_transfer_branch_name($br_code)
{
	$sql = "SELECT name FROM transfers.0_branches WHERE code = ". db_escape($br_code);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

function get_transfer_header($transfer_id)
{
	$sql = "SELECT * FROM transfers.0_transfer_header WHERE id=$transfer_id";
	$tr_res = db_query($sql);
	return db_fetch($tr_res);
}


function get_for_transfer_items($transfer_id)
{
	$sql = "SELECT * FROM transfers.0_transfer_details  WHERE transfer_id = $transfer_id";
	// display_error($sql);
	return db_query($sql);
}

function get_stocks_withdrawal_header($transfer_id)
{
	$sql = "SELECT * FROM transfers.0_stocks_withdrawal_header WHERE id=$transfer_id";
	$tr_res = db_query($sql);
	//display_error($sql);
	return db_fetch($tr_res);
}


function get_for_stocks_withdrawal_items($transfer_id)
{
	$sql = "SELECT * FROM transfers.0_stocks_withdrawal_details  WHERE transfer_id = $transfer_id";
	// display_error($sql);
	return db_query($sql);
}


function get_nature_of_req_name($id)
{
	$sql = "SELECT type_name FROM transfers.0_request_nature WHERE id = ".db_escape($id);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_hr_dept_name($id)
{
	$sql = "SELECT name FROM orange.hr_department WHERE department_id = ".db_escape($id);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

?>