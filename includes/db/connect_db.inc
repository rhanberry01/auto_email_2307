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
function get_connection_to_branch($br_code)
{
	//display_error($br_code);
	global $db_connections;
	switch($br_code) {
		
						case 'sri':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
									break;
						case 'srsn':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
									break;
						case 'srsnav':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
									break;
						case 'srst':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
									break;
						case 'srsc':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
									break;
						case 'srsant1':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
									break;
						case 'srsant2':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
									break;
						case 'srsm':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
									break;
						case 'srsmr':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
									break;
						case 'srsg':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
									break;
						case 'srscain':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
									break;
						case 'srscain2':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta2_connection"];
									break;
						case 'srsval':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
									break;			
						case 'srspun':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
									break;								
						case 'srsbsl':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
									break;	
						case 'srscom':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["comembo_connection"];
									break;											
						case 'srspat':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
									break;	
						case 'srssanp':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["sanpedro_connection"];
									break;
						case 'srstu':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["talonuno_connection"];
									break;
						case 'srsal':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["alaminos_connection"];
									break;
						case 'srsret':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["retail_connection"];
									break;
						case 'srsb':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["blum_connection"];
									break;
						case 'srsbgb':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bagumbong_connection"];
									break;
						case 'srsgv':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["graceville_connection"];
									break;
						case 'srsmol':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["molino_connection"];
									break;
						case 'srsman':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manggahan_connection"];
									break;
						case 'srsmon':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["montalban_connection"];
									break;

	}
	
	//display_error($connect_to);
	return $connect_to;
}


function switch_connection_to_branch($br_code)
{
	//display_error($br_code);
	global $db_connections;
	switch($br_code) {
						case 'sri':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
									break;
						case 'srsn':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
									break;
						case 'srsnav':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
									break;
						case 'srst':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
									break;
						case 'srsc':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
									break;
						case 'srsant1':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
									break;
						case 'srsant2':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
									break;
						case 'srsm':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
									break;
						case 'srsmr':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
									break;
						case 'srsg':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
									break;
						case 'srscain':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
									break;
						case 'srscain2':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta2_connection"];
									break;
						case 'srsval':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
									break;			
						case 'srspun':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
									break;								
						case 'srsbsl':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
									break;	
						case 'srscom':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["comembo_connection"];
									break;											
						case 'srspat':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
									break;	
						case 'srssanp':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["sanpedro_connection"];
									break;
						case 'srstu':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["talonuno_connection"];
									break;
						case 'srsal':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["alaminos_connection"];
									break;
						case 'srsret':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["retail_connection"];
									break;
						case 'srsb':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["blum_connection"];
									break;
						case 'srsbgb':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bagumbong_connection"];
									break;
									
						case 'srsgv':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["graceville_connection"];
									break;
									
						case 'srsmol':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["molino_connection"];
									break;
															case 'srsman':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manggahan_connection"];
									break;
						case 'srsmon':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["montalban_connection"];
									break;
	}
	
	//display_error($connect_to);
	set_global_connection_branch($connect_to);
	
	return $connect_to;
}

function switch_connection_to_branch_mysql($br_code)
{
	//display_error($br_code);
	global $db_connections;
	switch($br_code) {
						case 'sri':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["imus_connection"];
									break;
						case 'srsn':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["nova_connection"];
									break;
						case 'srsnav':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["navotas_connection"];
									break;
						case 'srst':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["tondo_connection"];
									break;
						case 'srsc':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["camarin_connection"];
									break;
						case 'srsant1':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["quezon_connection"];
									break;
						case 'srsant2':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manalo_connection"];
									break;
						case 'srsm':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["malabon_connection"];
									break;
						case 'srsmr':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["resto_connection"];
									break;
						case 'srsg':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["gagalangin_connection"];
									break;
						case 'srscain':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta_connection"];
									break;
						case 'srscain2':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["cainta2_connection"];
									break;
						case 'srsval':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["valenzuela_connection"];
									break;			
						case 'srspun':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["punturin_connection"];
									break;								
						case 'srsbsl':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bsilang_connection"];
									break;	
						case 'srscom':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["comembo_connection"];
									break;											
						case 'srspat':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["pateros_connection"];
									break;	
						case 'srssanp':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["sanpedro_connection"];
									break;
						case 'srstu':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["talonuno_connection"];
									break;
						case 'srsal':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["alaminos_connection"];
									break;
						case 'srsret':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["retail_connection"];
									break;
						case 'srsb':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["blum_connection"];
									break;
						case 'srsbgb':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["bagumbong_connection"];
									break;
						case 'srsgv':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["graceville_connection"];
									break;
									
						case 'srsmol':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["molino_connection"];
									break;
						case 'srsman':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["manggahan_connection"];
									break;
						case 'srsmon':
									$connect_to=$db_connections[$_SESSION["wa_current_user"]->company]["montalban_connection"];
									break;
	}
	
	//display_error($connect_to);
	set_global_connection_branch_mysql($connect_to);
	
	return $connect_to;
}

function set_global_connection()
{
	global $db, $ms_db, $rs_db;

   	$db = $_SESSION["wa_current_user"]->get_db_connection();
   	// $ms_db = $_SESSION["wa_current_user"]->ms_get_db_connection();
   	// $rs_db = $_SESSION["wa_current_user"]->get_rs_db_connection();
}

function set_global_connection_branch($branch=-1)
{
	global $db, $ms_db, $rs_db;

   	$db = $_SESSION["wa_current_user"]->get_db_connection($branch);
   	$ms_db = $_SESSION["wa_current_user"]->ms_get_db_connection($branch);
   	$rs_db = $_SESSION["wa_current_user"]->get_rs_db_connection($branch);
}


function set_global_connection_branch_mysql($branch=-1)
{
	global $db, $ms_db, $rs_db;

   	$db = $_SESSION["wa_current_user"]->get_db_connection($branch);
   	// $ms_db = $_SESSION["wa_current_user"]->ms_get_db_connection($branch);
   // $rs_db = $_SESSION["wa_current_user"]->get_rs_db_connection($branch);
}


$db_duplicate_error_code = 1062;

//DB wrapper functions to change only once for whole application

function db_query($sql, $err_msg=null)
{
	global $db, $show_sql, $sql_trail, $select_trail, $go_debug;
	
	if ($show_sql)
	{
		echo "<font face=arial size=2 color=000099><b>SQL..</b></font>";
		echo "<pre>";
		echo $sql;
		echo "</pre>\n";
	}
	
	set_time_limit(0);
	$result = mysql_query($sql, $db);
	if($sql_trail) {
		if ($select_trail || (strstr($sql, 'SELECT') === false)) {
			mysql_query(
			"INSERT INTO ".TB_PREF."sql_trail
				(`sql`, `result`, `msg`)
				VALUES(".db_escape($sql).",".($result ? 1 : 0).",
				".db_escape($err_msg).")", $db);
		}
	}

	if ($err_msg != null || $go_debug) {
		$exit = $err_msg != null;
		if (function_exists('xdebug_call_file'))
			check_db_error('<br>At file '.xdebug_call_file().':'.xdebug_call_line().':<br>'.$err_msg, $sql, $exit);
		else
			check_db_error($err_msg, $sql, $exit);
	}
	return $result;
}

function db_query_rs($sql, $err_msg=null)
{
	global $rs_db, $show_sql, $sql_trail, $select_trail, $go_debug;
	
	if (!isset($rs_db))
		$rs_db = $_SESSION["wa_current_user"]->get_rs_db_connection();
	
	if ($show_sql)
	{
		echo "<font face=arial size=2 color=000099><b>SQL..</b></font>";
		echo "<pre>";
		echo $sql;
		echo "</pre>\n";
	}
	

	$result = mysql_query($sql, $rs_db);
	if($sql_trail) {
		if ($select_trail || (strstr($sql, 'SELECT') === false)) {
			mysql_query(
			"INSERT INTO ".TB_PREF."sql_trail
				(`sql`, `result`, `msg`)
				VALUES(".db_escape($sql).",".($result ? 1 : 0).",
				".db_escape($err_msg).")", $rs_db);
		}
	}

	if ($err_msg != null || $go_debug) {
		$exit = $err_msg != null;
		if (function_exists('xdebug_call_file'))
			check_db_error('<br>At file '.xdebug_call_file().':'.xdebug_call_line().':<br>'.$err_msg, $sql, $exit);
		else
			check_db_error($err_msg, $sql, $exit);
	}
	return $result;
}

function ms_db_query($sql, $err_msg=null)
{
	global $show_sql, $sql_trail, $select_trail, $go_debug, $ms_db;
	
	if (!$ms_db)
		$ms_db = $_SESSION["wa_current_user"]->ms_get_db_connection();
	
	if ($show_sql)
	{
		echo "<font face=arial size=2 color=000099><b>SQL..</b></font>";
		echo "<pre>";
		echo $sql;
		echo "</pre>\n";
	}
	
	$result = mssql_query($sql, $ms_db);
	
	if (!$result AND $err_msg!='')
	{
		$str = "<b>" . _("DATABASE ERROR :") . "</b> $err_msg<br>";
		$str .= "<b>sql that failed was : </b>" . $sql . "<br>";
		display_error($str);
	}

	if ($err_msg != null || $go_debug) {
		$exit = $err_msg != null;
		if (function_exists('xdebug_call_file'))
			ms_check_db_error('<br>At file '.xdebug_call_file().':'.xdebug_call_line().':<br>'.$err_msg, $sql, $exit);
		else
			ms_check_db_error($err_msg, $sql, $exit);
	}
	return $result;
}

function db_fetch_row ($result)
{

	return mysql_fetch_row($result);
}

function db_fetch_assoc ($result)
{

	return mysql_fetch_assoc($result);
}

function db_fetch ($result)
{

	return mysql_fetch_array($result);
}

function db_seek (&$result,$record)
{
	return mysql_data_seek($result, $record);
}

function db_free_result ($result)
{
	if ($result)
		mysql_free_result($result);
}

function db_num_rows ($result)
{
	return mysql_num_rows($result);
}

function db_num_fields ($result)
{
	return mysql_num_fields($result);
}

function db_escape($value = "", $nullify = false)
{
	$value = @html_entity_decode($value, ENT_QUOTES, $_SESSION['language']->encoding);
	$value = @htmlspecialchars($value, ENT_QUOTES, $_SESSION['language']->encoding);

  	//reset default if second parameter is skipped
	$nullify = ($nullify === null) ? (false) : ($nullify);

  	//check for null/unset/empty strings
	if ((!isset($value)) || (is_null($value)) || ($value === "") || $value == 'NULL') {
		$value = ($nullify) ? ("NULL") : ("''");
	} else {
		if (is_string($value)) {
      		//value is a string and should be quoted; determine best method based on available extensions
			if (function_exists('mysql_real_escape_string')) {
		  		$value = "'" . mysql_real_escape_string($value) . "'";
			} else {
			  $value = "'" . mysql_escape_string($value) . "'";
			}
		} else if (!is_numeric($value)) {
			//value is not a string nor numeric
			display_error("ERROR: incorrect data type send to sql query");
			echo '<br><br>';
			exit();
		}
	}
	return $value;
}

function ms_escape_string($data) 
{
	if ( !isset($data) or empty($data) ) return '';
	if ( is_numeric($data) ) return $data;

	$non_displayables = array(
		'/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15
		'/%1[0-9a-f]/',             // url encoded 16-31
		'/[\x00-\x08]/',            // 00-08
		'/\x0b/',                   // 11
		'/\x0c/',                   // 12
		'/[\x0e-\x1f]/'             // 14-31
	);
	foreach ( $non_displayables as $regex )
		$data = preg_replace( $regex, '', $data );
	$data = str_replace("'", "''", $data );
	return $data;
}


function db_error_no()
{
	global $db;
	return mysql_errno($db);
}

function db_error_msg($conn)
{
	return mysql_error($conn);
}

function db_insert_id()
{
	global $db;
	return mysql_insert_id($db);
}

function db_num_affected_rows()
{
	global $db;
	return mysql_affected_rows($db);
}

?>
