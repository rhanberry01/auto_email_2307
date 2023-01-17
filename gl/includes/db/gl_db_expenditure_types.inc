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

function get_exp_types($all=false, $balance=-1)
{
	$sql = "SELECT * FROM ".TB_PREF."expenditure_type";
	if (!$all) 
		$sql .= " WHERE !inactive";
		$sql .= " ORDER BY exp_type_name";
	return db_query($sql, "could not get expenditure types");
}


function get_selected_exp_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."expenditure_type WHERE exp_id = ".db_escape($id);

	$result = db_query($sql, "could not get expenditure type");

	return db_fetch($result);
}


function add_exp_type($name, $gl_debit_account,$gl_vat_account,$vat_percent)
{
	$sql = "INSERT INTO ".TB_PREF."expenditure_type (exp_type_name,exp_gl_debit,exp_ov_gl,exp_ov)
			VALUES (".db_escape($name).",'$gl_debit_account','$gl_vat_account','$vat_percent')";
	db_query($sql);
}


function update_exp_type($id, $name, $gl_debit_account,$gl_vat_account,$vat_percent)
{
	$sql = "UPDATE ".TB_PREF."expenditure_type SET exp_type_name=".db_escape($name).",exp_gl_debit='$gl_debit_account',
	exp_ov_gl='$gl_vat_account', exp_ov='$vat_percent' WHERE exp_id=".db_escape($id)." ";
	db_query($sql);
}

function delete_exp_type($id)
{
		$sql = "DELETE FROM ".TB_PREF."expenditure_type WHERE exp_id = ".db_escape($id);
		db_query($sql, "could not delete expenditure type");
}

?>