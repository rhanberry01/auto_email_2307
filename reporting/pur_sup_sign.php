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
function get_po_user_id($id) {
	$sql = "SELECT po_user_id  FROM srs_aria_nova.".TB_PREF."users WHERE id = '".$id."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
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
	
	$sql = "SELECT a.*, b.supp_name, c.type_name FROM ".$_GET['branch'].".".TB_PREF."sdma a, ".$_GET['branch'].".".TB_PREF."suppliers b,
				".$_GET['branch'].".".TB_PREF."sdma_type c WHERE a.supplier_id = b.supplier_id  AND a.sdma_type = c.id AND reference = 'SRSSAF ".$_GET['id']."'";
	$res = db_query($sql);		
	$row = db_fetch($res);	
	//die();
	$po_id = get_po_user_id($row['prepared_by']);
	$prepared_by = $row['prepared_by'];
	
    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Supplier'), 'from' => $row['supp_name'] , 'to' =>''),
    				    2 => array('text' => _('Type'), 'from' => $row['type_name']  , 'to' =>''),
						3 => array('text' => _('Created By'), 'from' => strtoupper(get_real_name($row['prepared_by'],$_GET['branch'])) , 'to' =>''),
						4 => array('text' => _('Date Created'), 'from' => sql2date($row['date_created']) , 'to' =>''),
						5 => array('text' => _('Effectivity'), 'from' => $row['frequency'] == 0 ? 'for 1 CV dated '.sql2date($row['dm_date']) : 'for 1 CV '. $frequency[$row['frequency']] .' starting '. sql2date($row['dm_date']).' (for '. ($row['period']+1) .' deductions)','to' =>''),
						6 => array('text' => _('Comments'), 'from' => $row['comment'] , 'to' =>'')
						);


   $rep = new FrontReport("SRSSAF # $id", "", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header_srs();

	$rep->headerFunc = 'Header_srs';
	$branches_sql = "SELECT * from transfers.0_branches_other_income where inactive = 0 order by name";
	$branches_query = db_query($branches_sql);
	
	$a = 28;
	$total = 0;
	while ($row=db_fetch($branches_query))
	{
		$sql = "SELECT a.* FROM ".$row['aria_db'].".".TB_PREF."sdma a WHERE reference = 'SRSSAF ".$_GET['id']."' AND is_done != 2";
		$query = db_query($sql);	
		$row1=db_fetch($query);
		if(!$row1){
		//	$rep->Text($rep->leftMargin,'BRANCH: '.$row['name'], $companyCol,'center');
		}else{
			$a--;
			$companyCol = $rep->endLine - 150;
			$rep->Text($rep->leftMargin,'BRANCH: '.$row['name'], $companyCol,'center');
			
			if ($row1['amount'] <= 0){
				$rep->Text($companyCol,'PERCENTAGE:   '.$row1['disc_percent'].'%');
			}else{
				$rep->Text($companyCol,'AMOUNT:   P '.number_format($row1['amount'],2));
				$total= $total + $row1['amount'];
			}
/* 			$rep->TextCol(0, 1,$row['name']);
			if($row1['amount'] <= 0){
				$rep->TextCol(4, 10, '');
				$rep->TextCol(4, 5, $row1['disc_percent'].'%');
			}else{
				$rep->TextCol(4, 5, $row1['amount']);
				$rep->TextCol(4, 5, '');
			}
			$rep->Line($rep->row - 1);
			$rep->NewLine(); */
			$rep->NewLine();
		}
		
	
	//	$total_qty += $row['qty_out'];
	}
			
	$rep->NewLine();
	$rep->Line($rep->row - 4,2);
	$rep->fontSize += 1;
	
	if($total != 0){
		$rep->Text($rep->leftMargin,'TOTAL AMOUNT:', $companyCol,'center');
		$rep->Font('bold');
		$rep->Text($companyCol,'P '.number_format($total,2));
	}
	//$rep->NewLine();
	//	$rep->AddImage(path_to_root1);
	//$rep->TextCol(3, 4, $total_qty);
	
	$rep->NewLine();
	
	if(file_exists($path_to_root . "/purchasing/doc_signs/Sup(SAF".$_GET['id'].").png")){
		$rep->NewLine($a);
	    $rep->AddImage($path_to_root . "/purchasing/doc_signs/Sup(SAF".$_GET['id'].").png", 380,180,150, 50);
		if(file_exists($path_to_root . "/purchasing/doc_signs/signatures/".$po_id.".png")){
			$rep->AddImage($path_to_root . "/purchasing/doc_signs/signatures/".$po_id.".png", 70,180,150, 50);
		}
			$rep->Text(100,'Purchaser Signature', $companyCol,'center');
			$rep->Text(400,'Supplier Signature',200,'center');
			$rep->NewLine(6);
			$rep->Text(100,strtoupper(get_real_name($prepared_by)), 500,'center');
	}else{
		$rep->NewLine($a);
	   // $rep->AddImage($path_to_root . "/purchasing/doc_signs/Sup(SAF".$_GET['id'].").png", 330,170,250, 100);
		if(file_exists($path_to_root . "/purchasing/doc_signs/signatures/".$po_id.".png")){
			$rep->AddImage($path_to_root . "/purchasing/doc_signs/signatures/".$po_id.".png", 70,180,150, 50);
		}
		$rep->Text(100,'Purchaser Signature', $companyCol,'center');
		$rep->Text(400,'Supplier Signature',200,'center');
		
		$sql = "SELECT supplierName FROM srs_aria_nova.".TB_PREF."sdma_supplier WHERE reference = 'SRSSAF".$id."'";
	
		$res = db_query($sql);
		$row = db_fetch($res);
		
		$rep->NewLine(3);
		$rep->Text(390,strtoupper($row[0]),200,'left');
		$rep->NewLine(3);
		$rep->Text(100,strtoupper(get_real_name($prepared_by)), 500,'center');
	}
	$rep->NewLine();
    $rep->End();
}

?>