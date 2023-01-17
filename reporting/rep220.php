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

print_detailed_rr_report();

function print_detailed_rr_report()
{
    global $path_to_root;
	
	$start_date = $_POST['PARAM_0'];
	$end_date = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	$comments = '';
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");
	
    
	$dec = user_price_dec();

	// $cols = array(0,40, 75, 130, 350, 450, 550);
	// $cols = array(0,220,260,305,350,450,550);
	$cols = array(0, 80, 200, 245, 270, 340, 410, 480, 550);

	$headers_x = array('Product Code', 'Description', 'UOM', 'Qty.', 'Price', 'VAT', 'EWT', 'EXTENDED');

	$aligns = array('left',	'left',	'left', 'right', 'right','right','right', 'right');

    $params =   array( 	0 => $comments,
						1 => array('text' => _('Period'), 'from' => $start_date, 'to' => $end_date));

    $rep = new FrontReport(_('CUSTOM REPORT1'), "CustomReport1", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

	$sql = "SELECT  ReceivingNo, Description, PurchaseOrderNo, ReferenceNo, StatusDescription, PostedDate 
			FROM Receiving 
			WHERE DateReceived >= '".date2sql($start_date)."'
			AND DateReceived < '".date2sql(add_days($end_date,1))."'";
	$res = ms_db_query($sql);
	
	while($row = mssql_fetch_array($res))
	{
		// TextCol($c, $n, $txt, $corr=0, $r=0, $border=0, $fill=0, $link=NULL, $stretch=1, $add_x=0, $x_align='');
		$rep->TextCol(0, 1, 'Receiving No.');
		$rep->TextCol(1, 3, 'Vendor');
	}

    $rep->End();
}

?>