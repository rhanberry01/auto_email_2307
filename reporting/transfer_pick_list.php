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
$page_security = 'SA_ITEMSTRANSVIEW';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Stock Check Sheet
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/stock_transfer2.inc");
//----------------------------------------------------------------------------------------------------

print_packing_list();

function print_packing_list()
{
    global $comp_path, $path_to_root, $pic_height;

    $trans_id = $_GET['transfer_id'];

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

	$cols = array(0, 100, 300, 380, 460, 540);
	$headers = array(_('Barcode'), _('Description'),'Units', 'QTY Requested', 'QTY to Dispatch');
	$aligns = array('left', 'left', 'center', 'center', 'right');


	$tr_header = get_transfer_header($trans_id);
	
    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Location'), 'from' => get_transfer_branch_name($tr_header['br_code_out']), 
									'to' => '> '.get_transfer_branch_name($tr_header['br_code_in'])),
						2 => array('text' => _('Date Created'), 'from' => sql2date($tr_header['date_created']) , 'to' =>''),
						3 => array('text' => _('Delivery Date'), 'from' => sql2date($tr_header['delivery_date']) , 'to' =>''),
						4 => array('text' => _('Requested By: '), 'from' => strtoupper($tr_header['requested_by']) , 'to' =>'')
						);


    $rep = new FrontReport("Transfer # $trans_id (Packing List)", "Packing_List_Transfer_$trans_id", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header_srs();

	$rep->headerFunc = 'Header_srs';
	$res = get_for_transfer_items($trans_id);
	
	$rows = db_num_rows($res);
	
	// die($count);
	if($rows==0)
	{
		display_error("No reports can be generated with the given parameters.");
		die();
	}
	
	$catt = '';
	$total_qty = 0;
	while ($row=db_fetch($res))
	{
		// for($i=0;$i<=89;$i++)
		// {
		$rep->TextCol(0, 1, $row['barcode']);
		$rep->TextCol(1, 2, $row['description']);
		$rep->TextCol(2, 3, $row['uom']);
		$rep->TextCol(3, 4, $row['qty_out']);
		$rep->TextCol(4, 5, '');
		$total_qty += $row['qty_out'];
		$rep->Line($rep->row - 1);
		$rep->NewLine();
		// }
	}
	$rep->NewLine();
				
	$rep->fontSize += 1;
	$rep->Font('bold');
	$rep->TextCol(0, 3,	_('Total QTY:'));
	$rep->TextCol(3, 4, $total_qty);
	$rep->Line($rep->row - 4,2);
	$rep->NewLine();
    $rep->End();
}

?>
