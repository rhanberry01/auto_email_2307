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

$path_to_root1="../..";


include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/stock_transfer2.inc");
//----------------------------------------------------------------------------------------------------

print_packing_list();
function get_real_name($id) {
	$sql = "SELECT real_name  FROM srs_aria_nova.".TB_PREF."users WHERE id = '".$id."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}
function get_display_username_by_id_and_branch_($id)
{
	$sql2 = "SELECT real_name FROM srs_aria_nova.".TB_PREF."users WHERE id = $id";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	return $row2[0];
}
function print_packing_list()
{
    global $comp_path, $path_to_root, $pic_height, $frequency;;

    $id = $_GET['id'];

	include_once($path_to_root . "/reporting/includes/pdf_report.inc");

//	$cols = array(0, 200, 300, 380, 460);
//	$headers = array(_('Branch'), _('Amount/Percentage'));
//	$aligns = array('left', 'left');


	//$tr_header = get_transfer_header($id);
	
	$sql = "SELECT * FROM 0_suppliers_clearance_new as a  WHERE id =".$id;
	$res = db_query($sql);		
	$row = db_fetch($res);	
	//die();
	$purchaser = get_display_username_by_id_and_branch_($row['addedBy']);
  /*   $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Supplier'), 'from' => $row['supp_name'] , 'to' =>''),
    				    2 => array('text' => _('Type'), 'from' => $row['type_name']  , 'to' =>''),
						3 => array('text' => _('Created By'), 'from' => strtoupper(get_real_name($row['prepared_by'],$_GET['branch'])) , 'to' =>''),
						4 => array('text' => _('Date Created'), 'from' => sql2date($row['date_created']) , 'to' =>''),
						5 => array('text' => _('Effectivity'), 'from' => $row['frequency'] == 0 ? 'for 1 CV dated '.sql2date($row['dm_date']) : 'for 1 CV '. $frequency[$row['frequency']] .' starting '. sql2date($row['dm_date']).' (for '. ($row['period']+1) .' deductions)','to' =>''),
						6 => array('text' => _('Comments'), 'from' => $row['comment'] , 'to' =>'')
						);
 */

   $rep = new FrontReport("SRSSAF # $id", "", user_pagesize());

    $rep->Font();
  //  $rep->Info($params, $cols, $headers, $aligns);
   // $rep->Header_srs();
	//$rep->headerFunc = 'Header_srs';
	$rep->fontSize += 4;
	$rep->Font('bold');
	$rep->Text(140, 'SAN ROQUE SUPERMARKET RETAIL SYSTEMS INC.', $companyCol,'center');
	$rep->NewLine(1.5);
	$rep->fontSize -= 2;
	$rep->Text(180, "SUPPLIER'S CLEARANCE AND ROUTING SLIP", $companyCol,'center');
	$rep->NewLine(1.5);
	$rep->fontSize -= 2;
	$rep->Text(480, "Control #:  ".$row['transNo'], $companyCol,'center');
	
	$rep->NewLine(2);
	$rep->fontSize += 1;
	
	$sql1 = "SELECT description FROM vendor where vendorcode = '".$row['tosupplierCode']."'";
	$res1 =  ms_db_query($sql1);
	$row1 = mssql_fetch_row($res1);
	
	$rep->Text($rep->leftMargin, "SUPPLIER Name:  ".$row1[0], $companyCol,'center');
	$rep->Text($rep->endLine - 280, "Prepared By:  ".$purchaser, $companyCol,'center');
	$rep->Text($rep->endLine - 100, "Date:  ".sql2date($row['dateAdded']), $companyCol,'center');
	
	$rep->NewLine(2);
	$rep->fontSize += 1;
	
	if($row['type'] == 0){		
		$type = 'Supplier Clearance (Pull Out)';
	}else{
		$type =  'Change Of Supplier (Traanfer of Account)';
	}
	$rep->Text($rep->leftMargin, "I. Reason For Clearance", $companyCol,'center');
	$rep->Font();
	$rep->Text($rep->leftMargin+150, $type, $companyCol,'center');
	
	$rep->NewLine(1.5);

	$rep->Text($rep->leftMargin+30, "[ ] Change Company Name", $companyCol,'center');
	$rep->Text($rep->leftMargin+300, "[ ] Supplier for Clearance", $companyCol,'center');
	$rep->NewLine();
	$rep->Text($rep->leftMargin+30, "[ ] Change Distributor", $companyCol,'center');
	$rep->Text($rep->leftMargin+300, "[ ] Product for Phase Out / Depletion", $companyCol,'center');
	$rep->NewLine();
	$rep->Text($rep->leftMargin+30, "[ ] Change of Supplier", $companyCol,'center');
	$rep->Text($rep->leftMargin+300, "[ ] Products for Clearance", $companyCol,'center');
	$rep->NewLine();
	$rep->Text($rep->leftMargin+40, " (Transfer of Account)", $companyCol,'center');
	$rep->Text($rep->leftMargin+300, "[ ] Expired (nearly expired products)", $companyCol,'center');
	
	$rep->NewLine(2);
	$rep->Font('bold');
	//$rep->fontSize += 1;
	$rep->Text($rep->leftMargin, "II. Purchaser's Remarks / Instruction", $companyCol,'center');
	$rep->NewLine(1.5);
	$rep->Font();
	if($row['type'] == 0){
		$rep->Text($rep->leftMargin+40, $row['remarks'], $companyCol,'center');
		$rep->NewLine(1.5);
		$rep->Text($rep->leftMargin+40, '___________________________________________________________________________', $companyCol,'center');
	}else{
		$sql2 = "SELECT description FROM vendor where vendorcode = '".$row['supplierCode']."'";
		$res2 =  ms_db_query($sql2);
		$row2 = mssql_fetch_row($res2);
		$rep->Text($rep->leftMargin+40, $row['remarks'], $companyCol,'center');
		$rep->NewLine(1.5);
		$rep->Text($rep->leftMargin+40, 'Transfer to '.$row2[0], $companyCol,'center');
	}
	
	$rep->NewLine(1.5);
	$rep->Text($rep->leftMargin+40, '___________________________________________________________________________', $companyCol,'center');
	$rep->NewLine(1.5);
	$rep->Text($rep->leftMargin+40, '___________________________________________________________________________', $companyCol,'center');
	
	$rep->NewLine(2);
	$rep->fontSize -= 2;
	$rep->Font('bold');
	$rep->Text($rep->leftMargin, 'Prepared By', $companyCol,'center');
	$rep->Text($rep->leftMargin+250, 'Noted By', $companyCol,'center');
	$rep->Text($rep->leftMargin+450, 'Approved By', $companyCol,'center');
	$rep->NewLine(2);
	$rep->Font();
	$rep->Text($rep->leftMargin,$purchaser, $companyCol,'center');
	$rep->Text($rep->leftMargin+250, '_______________________', $companyCol,'center');
	$rep->Text($rep->leftMargin+450, '_______________________', $companyCol,'center');
	$rep->NewLine();
	$rep->Font('bold');
	$rep->Text($rep->leftMargin,'Purchaser', $companyCol,'center');
	$rep->Text($rep->leftMargin+250, 'Purchasing Manager', $companyCol,'center');
	$rep->Text($rep->leftMargin+450, 'Sr. Purchasing Manager', $companyCol,'center');
	$rep->NewLine(2);
	$rep->Font('bold');
	$rep->fontSize += 2;
	$rep->Text($rep->leftMargin, "III. Route To", $companyCol,'center');
	$rep->NewLine(2);
	$rep->Text($rep->leftMargin, "Department / Name of Person", $companyCol,'center');
	$rep->Text($rep->leftMargin+180, "Date Received", $companyCol,'center');
	$rep->Text($rep->leftMargin+320, "Remarks", $companyCol,'center');
	$rep->Text($rep->leftMargin+460, "Signature", $companyCol,'center');
	$rep->NewLine(4);
	$rep->Text($rep->leftMargin, "Accounting", $companyCol,'center');
	$rep->Text($rep->leftMargin+160, "__________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+290, "______________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+450, "_______________________", $companyCol,'center');
	$rep->NewLine(3);
	$rep->Text($rep->leftMargin, "Treasury", $companyCol,'center');
	$rep->Text($rep->leftMargin+160, "__________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+290, "______________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+450, "_______________________", $companyCol,'center');
	$rep->NewLine(3);
	$rep->Text($rep->leftMargin, "Operations", $companyCol,'center');
	$rep->Text($rep->leftMargin+160, "__________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+290, "______________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+450, "_______________________", $companyCol,'center');
	$rep->NewLine(3);
	$rep->Text($rep->leftMargin, "Information System", $companyCol,'center');
	$rep->Text($rep->leftMargin+160, "__________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+290, "______________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+450, "_______________________", $companyCol,'center');
	$rep->NewLine(3);
	$rep->Text($rep->leftMargin, "Purchasing", $companyCol,'center');
	$rep->Text($rep->leftMargin+160, "__________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+290, "______________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+450, "_______________________", $companyCol,'center');
	
	$rep->NewLine(4);
	$rep->Font('bold');
	$rep->Text($rep->leftMargin, "IV. Accounting: ", $companyCol,'center');
	$rep->Font();
	$rep->Text($rep->leftMargin+80, " Date Finished Computing the Final Pay ________________________________________", $companyCol,'center');
	$rep->NewLine(2);
	$rep->Font('bold');
	$rep->Text($rep->leftMargin, "V. Treasury: ", $companyCol,'center');
	$rep->Font();
	$rep->Text($rep->leftMargin+70, " Date Voucher and Check Prepared _____________________________________________", $companyCol,'center');
	$rep->NewLine(2);
	$rep->Font('bold');
	$rep->Text($rep->leftMargin, "VI. Supplier Representative ", $companyCol,'center');
	$rep->NewLine(2);
	$rep->Font();
	$rep->Text($rep->leftMargin+40, "Receive By: ____________________________", $companyCol,'center');
	$rep->Text($rep->leftMargin+300, "Date Received: _______________________", $companyCol,'center');
	$rep->NewLine();
	$rep->Text($rep->leftMargin+110, "(Signature over Printed Name)", $companyCol,'center');
	
    $rep->End();
}

?>
