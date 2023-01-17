<?php
/**********************************************************************
    Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL, 
	as published by the Free Software Foundation, either version 3 
	of the License, or (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  
    See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
***********************************************************************/

// function add_user($user_id, $real_name, $password, $phone, $email, $role_id, 
	// $language, $profile, $rep_popup, $pos, $is_supervisor,$supervisor_type,$can_credit,$can_negative_inv,$can_details,$can_void,$can_edit,$can_approve_so,$can_approve_po)
	
function add_user($user_id, $real_name, $password, $phone, $email, $role_id, 
	$language, $profile, $rep_popup, $pos, $is_supervisor,$items,$can_approve_cv,$can_approve_sales_remittance,
	$can_approve_sdma_1,$can_approve_sdma_2)
{

	global $db_connections;
	
	foreach($db_connections as $key=>$db_con)
	{
			// display_error($items);
		$sql = "INSERT INTO ".$db_con['dbname'].'.'.$db_con['tbpref']."users (user_id, real_name, password"
			.", phone, email, role_id, language, pos, print_profile, rep_popup, can_approve_cv,can_approve_sales_remittance,
				can_approve_sdma_1,can_approve_sdma_2, 
				is_supervisor, theme, page_size, startup_tab,can_credit_limit,
				can_negative_inv,can_details,can_void,can_edit,can_approve_so,can_approve_po)
			VALUES (".db_escape($user_id).", 
			".db_escape($real_name).", ".db_escape($password) .",".db_escape($phone)
			.",".db_escape($email).", ".db_escape($role_id).", ".db_escape($language)
			.", ".db_escape($pos).",".db_escape($profile).",".db_escape($rep_popup)
			.",".db_escape($can_approve_cv).",".db_escape($can_approve_sales_remittance)
			.",".db_escape($can_approve_sdma_1).",".db_escape($can_approve_sdma_2)
			.",".db_escape($is_supervisor).",".db_escape('modern').",".db_escape('Letter').",".db_escape('AP');

		// $sql.= ",";
		$count = count($items);

		$type = array(1=>'Credit Limit',
							   2=>'Negative Inventory',
							   3=>'Details',
							   4=>'Voiding',
							   5=>'Editing',
							   6=>'Sales Order Approval',
							   7=>'Purchase Order Approval'
			);
			
		foreach($type as $key=>$val){
			if(!in_array($key,$items))
				$sql .= ',0';
			else
				$sql .= ',1';
		}
		
		$sql .= " )";
		// display_error($sql);
		db_query($sql, "could not add user for $user_id");
	}

	return true;
}
//-----------------------------------------------------------------------------------------------

function update_user_password($id, $user_id, $password)
{
	$sql = "UPDATE ".TB_PREF."users SET password=".db_escape($password) . ",
		user_id = ".db_escape($user_id). " WHERE id=".db_escape($id);

	db_query($sql, "could not update user password for $user_id");
}

//-----------------------------------------------------------------------------------------------

function update_user($id, $user_id, $real_name, $phone, $email, $role_id, 
	$language, $profile, $rep_popup, $pos, $is_supervisor,$items,$can_approve_cv,$can_approve_sales_remittance,
	$can_approve_sdma_1,$can_approve_sdma_2)
{
	// $sql = "UPDATE ".TB_PREF."users SET real_name=".db_escape($real_name).
	// ", phone=".db_escape($phone).",
		// email=".db_escape($email).",
		// role_id=".db_escape($role_id).",
		// language=".db_escape($language).",
		// print_profile=".db_escape($profile).",
		// rep_popup=".db_escape($rep_popup).",
		// pos=".db_escape($pos).",
		// user_id = " . db_escape($user_id).",
		// is_supervisor = " . db_escape($is_supervisor).",
		// supervisor_type = " .db_escape($supervisor_type)
		// . " WHERE id=" . db_escape($id);
	$sql = "UPDATE ".TB_PREF."users SET real_name=".db_escape($real_name).
	", phone=".db_escape($phone).",
		email=".db_escape($email).",
		role_id=".db_escape($role_id).",
		language=".db_escape($language).",
		print_profile=".db_escape($profile).",
		rep_popup=".db_escape($rep_popup).",
		pos=".db_escape($pos).",
		user_id = " . db_escape($user_id).",
		can_approve_cv = " . db_escape($can_approve_cv).",
		can_approve_sales_remittance = " . db_escape($can_approve_sales_remittance).",
		can_approve_sdma_1 = " . db_escape($can_approve_sdma_1).",
		can_approve_sdma_2 = " . db_escape($can_approve_sdma_2).",
		is_supervisor = " . db_escape($is_supervisor);
		
	// if($is_supervisor)
	
	$cols = array(
	1=>'can_credit_limit',
	2=>'can_negative_inv',
	3=>'can_details',
	4=>'can_void',
	5=>'can_edit',
	6=>'can_approve_so',
	7=>'can_approve_po'
	);
	// $db_ins = array_merge($items,$cols);
	
	// foreach($db_ins as $x)
	// display_error('xxxxxxx ==> '.$x);
	// $sql.= ",";\
		foreach($cols as $key=>$val){
		if(!in_array($key,$_POST['limit']))
			$sql .= ','.$val.' = 0';
		else
			$sql .= ','.$val.' = 1';
		}
		$sql .= " WHERE user_id=".db_escape($_POST['user_id']);
	db_query($sql, "could not update user for $user_id");
}

//-----------------------------------------------------------------------------------------------

function update_user_display_prefs($id, $price_dec, $qty_dec, $exrate_dec, 
	$percent_dec, $showgl, $showcodes, $date_format, $date_sep, $tho_sep, 
	$dec_sep, $theme, $pagesize, $show_hints, $profile, $rep_popup, $query_size, 
	$graphic_links,	$lang, $stickydate, $startup_tab)
{
	$sql = "UPDATE ".TB_PREF."users SET
		prices_dec=".db_escape($price_dec).",
		qty_dec=".db_escape($qty_dec).",
		rates_dec=".db_escape($exrate_dec).",
		percent_dec=".db_escape($percent_dec).",
		show_gl=".db_escape($showgl).",
		show_codes=".db_escape($showcodes).",
		date_format=".db_escape($date_format).",
		date_sep=".db_escape($date_sep).",
		tho_sep=".db_escape($tho_sep).",
		dec_sep=".db_escape($dec_sep).",
		theme=".db_escape($theme).",
		page_size=".db_escape($pagesize).",
		show_hints=".db_escape($show_hints).",
		print_profile=".db_escape($profile).",
		rep_popup=".db_escape($rep_popup).",
		query_size=".db_escape($query_size).",
		graphic_links=".db_escape($graphic_links).",
		language=".db_escape($lang).",
		sticky_doc_date=".db_escape($stickydate).",
		startup_tab=".db_escape($startup_tab)."
		WHERE id = ".db_escape($id);

	db_query($sql, "could not update user display prefs for $id");
}

//-----------------------------------------------------------------------------------------------


function get_users($all=false)
{
	$sql = "SELECT u.*, r.role FROM ".TB_PREF."users u, ".TB_PREF."security_roles r
		WHERE u.role_id=r.id";
	if (!$all) $sql .= " AND !u.inactive";
	
	return db_query($sql, "could not get users");
}
//----------------------------------------------------------------
function get_user_all_branch($id,$br_code)
{

	$sql = "SELECT * FROM ".$br_code.".".TB_PREF."users WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get user $id");

	return db_fetch($result);
}
//-----------------------------------------------------------------------------------------------

function get_user($id,$br_code)
{
	if ($br_code!='') {
	switch_connection_to_branch($br_code);
	}
	
	$sql = "SELECT * FROM ".TB_PREF."users WHERE id=".db_escape($id);

	$result = db_query($sql, "could not get user $id");

	return db_fetch($result);
}

//-----------------------------------------------------------------------------------------------
//	This function is necessary for admin prefs update after upgrade from 2.1
//
function get_user_by_login($user_id)
{
	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id=".db_escape($user_id);

	$result = db_query($sql, "could not get user $user_id");

	return db_fetch($result);
}

//-----------------------------------------------------------------------------------------------

function delete_user($id)
{
	$sql="DELETE FROM ".TB_PREF."users WHERE id=".db_escape($id);

	db_query($sql, "could not delete user $id");
}

//-----------------------------------------------------------------------------------------------

function get_user_for_login($user_id, $password)
{
	set_global_connection();

// do not exclude inactive records or you lost access after source upgrade
// on sites using pre 2.2 database
	$sql = "SELECT * FROM ".TB_PREF."users WHERE user_id = ".db_escape($user_id)." AND"
		." password=".db_escape($password);

	return db_query($sql, "could not get validate user login for $user_id");
}

//-----------------------------------------------------------------------------------------------

function update_user_visitdate($user_id)
{
	$sql = "UPDATE ".TB_PREF."users SET last_visit_date='". date("Y-m-d H:i:s") ."'
		WHERE user_id=".db_escape($user_id);

	db_query($sql, "could not update last visit date for user $user_id");
}

//-----------------------------------------------------------------------------------------------
function check_user_activity($id) 
{
	$sql = "SELECT COUNT(*) FROM ".TB_PREF."audit_trail WHERE audit_trail.user="
		. db_escape($id);
	$result = db_query($sql,"Cant check user activity");
	$ret = db_fetch($result);

	return $ret[0];
}

//-----------------------------------------------------------------------------------------------
function show_users_online()
{
	global $show_users_online;
	
	if (!isset($show_users_online) || $show_users_online == 0 || !defined('TB_PREF') || !isset($_SESSION['get_text']))
		return "";
	$result = db_query("SHOW TABLES LIKE '".TB_PREF."useronline'");	
	if (db_num_rows($result) == 1)
	{
		$timeoutseconds = 120;

		$timestamp=time();
		$timeout=$timestamp-$timeoutseconds;
		/*
		This will find out if user is from behind proxy server. 
		In that case, the script would count them all as 1 user.
		This function tryes to get real IP address.
		*/
		if (isset($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED'];
		}
		elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_FORWARDED_FOR'];
		}
		elseif (isset($_SERVER['HTTP_FORWARDED'])) {
			$ip = $_SERVER['HTTP_FORWARDED'];
		}
		else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// Add user to database
		db_query("INSERT INTO ".TB_PREF."useronline (timestamp, ip, file) VALUES ('". $timestamp ."','". $ip ."','". $_SERVER['PHP_SELF'] ."')");
		//Remove users that were not online within $timeoutseconds.
		db_query("DELETE FROM ".TB_PREF."useronline WHERE timestamp<". $timeout);

		// Select online users
		$result = db_query("SELECT DISTINCT ip FROM ".TB_PREF."useronline");
		$users = db_num_rows($result);
	}
	else
		$users = 1;
	return "$users ".($users == 1 ? _("user online") : _("users online"));

}

function get_username_by_id($id)
{
	$sql = "SELECT real_name FROM ".TB_PREF."users WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_ms_username_by_id($id)
{
	$sql = "SELECT name FROM MarkUsers
				WHERE userid = $id";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}
?>