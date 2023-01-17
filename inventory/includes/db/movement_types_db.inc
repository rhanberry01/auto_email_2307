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
function add_movement_type($movement_code,$name,$location,$action_type)
{

	$sql = "SELECT * FROM 0_locations where loc_code='$location'";
	$res=db_query($sql);
	$row=db_fetch($res);
	$location_name=$row['location_name'];

	$sql = "INSERT INTO ".TB_PREF."movement_types (movement_code,name,location_code,location,action_type)
		VALUES (".db_escape($movement_code).",".db_escape($name).",".db_escape($location).",".db_escape($location_name).",".db_escape($action_type).")";

	db_query($sql, "could not add item movement type");
}

function update_movement_type($type_id, $movement_code,$name,$location,$action_type)
{
	$sql = "SELECT * FROM 0_locations where loc_code='$location'";
	$res=db_query($sql);
	$row=db_fetch($res);
	$location_name=$row['location_name'];
	
	$sql = "UPDATE ".TB_PREF."movement_types SET movement_code=".db_escape($movement_code).",name=".db_escape($name).",
			location_code=".db_escape($location).",location=".db_escape($location_name).",action_type=".db_escape($action_type)." WHERE id=".db_escape($type_id);

	db_query($sql, "could not update item movement type");
}

function get_all_movement_type($all=false)
{
	$sql = "SELECT * FROM ".TB_PREF."movement_types";
	if (!$all) $sql .= " WHERE !inactive";

	return db_query($sql, "could not get all item movement type");
}

function get_movement_type($type_id)
{
	$sql = "SELECT * FROM ".TB_PREF."movement_types WHERE id=".db_escape($type_id);

	$result = db_query($sql, "could not get item movement type");

	return db_fetch($result);
}

	function get_movement_type_name($movement_code)
{
	$sql = "SELECT * FROM ".TB_PREF."movement_types WHERE movement_code='$movement_code'";
	$result = db_query($sql, "could not get item movement type");
	$row=db_fetch($result);
	return $row['name'];
}
	

function delete_movement_type($type_id)
{
	$sql="DELETE FROM ".TB_PREF."movement_types WHERE id=".db_escape($type_id);

	db_query($sql, "could not delete item movement type");
}

?>