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

function get_oi_types($all=false, $balance=-1)
{
	$sql = "SELECT * FROM ".TB_PREF."other_income_type";
	if (!$all) 
		$sql .= " WHERE !inactive";
		$sql .= " ORDER BY oi_type_name";
	return db_query($sql, "could not get other income types");
}


function get_selected_oi_type($id)
{
	$sql = "SELECT * FROM ".TB_PREF."other_income_type WHERE oi_id = ".db_escape($id);

	$result = db_query($sql, "could not get other income type");

	return db_fetch($result);
}


function add_oi_type($name, $gl_debit_account,$gl_vat_account,$gl_wtax_account,$vat_percent,$withholding_tax_percent)
{
	$sql = "INSERT INTO ".TB_PREF."other_income_type (oi_type_name,oi_gl_debit,oi_ov_gl,oi_wt_gl,oi_ov,oi_wt)
			VALUES (".db_escape($name).",'$gl_debit_account','$gl_vat_account','$gl_wtax_account','$vat_percent','$withholding_tax_percent')";
	db_query($sql);
}


function update_oi_type($id, $name, $gl_debit_account,$gl_vat_account,$gl_wtax_account,$vat_percent,$withholding_tax_percent)
{
	$sql = "UPDATE ".TB_PREF."other_income_type SET oi_type_name=".db_escape($name).",oi_gl_debit='$gl_debit_account',
	oi_ov_gl='$gl_vat_account',oi_wt_gl='$gl_wtax_account', oi_ov='$vat_percent', oi_wt='$withholding_tax_percent' WHERE oi_id=".db_escape($id)." ";
	db_query($sql);
}

function delete_oi_type($id)
{
		$sql = "DELETE FROM ".TB_PREF."other_income_type WHERE oi_id = ".db_escape($id);
		db_query($sql, "could not delete other income type");
}

?>