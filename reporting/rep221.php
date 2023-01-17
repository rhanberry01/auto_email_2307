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
$page_security = 'SA_SUPPLIERANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Outstanding GRNs Report
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");

//----------------------------------------------------------------------------------------------------

print_supplier_list();

function print_supplier_list()
{
    global $path_to_root;
	
	$vat_nv = $_POST['PARAM_0'];
	$destination = $_POST['PARAM_1'];
	
	$comments = '';
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
    
	$dec = user_price_dec();

	// $cols = array(0,40, 75, 130, 350, 450, 550);
	// $cols = array(0,220,260,305,350,450,550);
	$cols = array(0, 150, 200, 550);

	$headers = array('Code', ' ', 'Supplier Name');

	$aligns = array('left', 'left', 'left');

    $params =   array( 	0 => ($vat_nv ? 'VAT Suppliers' : 'NON-VAT Suppliers'));

    $rep = new FrontReport(_('Supplier_List'), "SupplierList", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$v_nv = "tax_group_id = 1";
	
	if (!$vat_nv)
		$v_nv = "tax_group_id != 1";
	
	$sql = "SELECT supp_ref as vendor_code, supp_name as supplier_name 
			FROM `0_suppliers` WHERE $v_nv ORDER BY supp_name";
	$res = db_query($sql);
	while($row = db_fetch($res))
	{
		$rep->TextCol(0, 1, $row[0]);
		$rep->TextCol(1, 2, '-');
		$rep->TextCol(2, 3, $row[1]);
		$rep->NewLine();
	}
	$rep->Line($rep->row + 10,2);
	$rep->NewLine();
    $rep->End();
}

?>