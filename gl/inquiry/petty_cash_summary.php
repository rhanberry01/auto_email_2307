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
if (isset($_GET['xls']) AND isset($_GET['filename']) AND isset($_GET['unique']))
{
	$path_to_root = "../..";
	include_once($path_to_root . "/includes/session.inc");
	header('Content-Disposition: attachment; filename='.$_GET['filename']);
	$target = $path_to_root.'/company/'.$_SESSION['wa_current_user']->company.'/pdf_files/'.$_GET['unique'];
	readfile($target);
	exit;
}


$page_security = 'SA_DEPOSIT';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");

//start of excel report
if(isset($_POST['dl_excel']))
{
	cashier_summary_per_day_excel();
	exit;
}

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Petty Cash Breakdown"), false, false, "", $js);



function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$date_f= $_POST['start_date'];
	$date_t= $_POST['end_date'];
	
$date_after = date2sql($_POST['start_date']);
$date_before = date2sql($_POST['end_date']);

	
	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_f,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Petty Cash Breakdown'), "Petty Cash Breakdown", "LETTER");
	
    $rep->Font();
	
	$format_header =& $rep->addFormat();
	$format_header->setBold();
	$format_header->setAlign('center');
	$format_header->setFontFamily('Calibri');
	$format_header->setSize(16);
	
	$format_bold_title =& $rep->addFormat();
	$format_bold_title->setTextWrap();
	$format_bold_title->setBold();
	$format_bold_title->setAlign('center');
	$format_bold_title->setFontFamily('Calibri');
	
	$format_left =& $rep->addFormat();
	$format_left->setTextWrap();
	$format_left->setAlign('left');
	$format_left->setFontFamily('Calibri');
	
	$format_center =& $rep->addFormat();
	$format_center->setTextWrap();
	$format_center->setAlign('center');
	$format_center->setFontFamily('Calibri');
	
	$format_right =& $rep->addFormat();
	$format_right->setTextWrap();
	$format_right->setAlign('right');
	$format_right->setFontFamily('Calibri');
	
	$format_bold =& $rep->addFormat();
	$format_bold->setBold();
	$format_bold->setAlign('left');
	$format_bold->setFontFamily('Calibri');
	
	$format_bold_right =& $rep->addFormat();
	$format_bold_right->setBold();
	$format_bold_right->setAlign('right');
	$format_bold_right->setFontFamily('Calibri');
	
	$format_accounting =& $rep	->addFormat();
	$format_accounting->setNumFormat('_(* #,##0.00_);_(* (#,##0.00);_(* "-"??_);_(@_)');
	$format_accounting->setAlign('right');
	$format_accounting->setFontFamily('Calibri');
	
	$format_over_short =& $rep	->addFormat();
	$format_over_short->setNumFormat('#,##0.00_);[Red](#,##0.00);_(* "-"_);');
	$format_over_short->setAlign('right');
	$format_over_short->setFontFamily('Calibri');
	
	$rep->sheet->writeString($rep->y, 0, $com['coy_name'], $format_header);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'PETTY CASH (Breakdown)', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date From: '.sql2date($date_after).' To: '.sql2date($date_before).'', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,14);
	$rep->sheet->setColumn(2,2,10);
	$rep->sheet->setColumn(3,3,14);
	$rep->sheet->setColumn(4,4,40);
	$rep->sheet->setColumn(5,5,45);
	$rep->sheet->setColumn(6,6,12);
	$rep->sheet->setColumn(7,7,14);
	$rep->sheet->setColumn(9,11,14);
	$rep->sheet->setColumn(10,50,16);

	//setColumn(from,to,size);
	
$x=1;

div_start('table_');
start_table($table_style2.' width=90%');
$th = array();

//gl header
$sql="select pcd_gl_type from ".TB_PREF."petty_cash_details as pcd
left join ".TB_PREF."petty_cash_header as pch
on pcd.pc_id=pch.pc_id";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE pcd_date >= '".date2sql($_POST['start_date'])."'
			  AND pcd_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (pcd.pc_id LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['created_by']!='') {
$sql.=" and pcd.pcd_prepared_by_id='".$_POST['created_by']."' ";
}

if ($_POST['yes_no']==0) {
$sql.="  AND pcd.pcd_w_cv='0'";
}
else {
$sql.=" AND pcd.pcd_w_cv='1'";
}

$sql .= " AND pcd.pcd_replenished='1' AND pcd_wid_breakdown='0' GROUP BY pcd_gl_type";
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);
$gls=array();
while($row = db_fetch($res))
{
$gls[]=get_gl_account_name($row['pcd_gl_type']);
$gl_types[$row['pcd_gl_type']] ['gl_used']=$row['pcd_gl_type'];
}


//tax header
$sql="select pcd_tax_type from ".TB_PREF."petty_cash_details as pcd
left join ".TB_PREF."petty_cash_header as pch
on pcd.pc_id=pch.pc_id";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE pcd_date >= '".date2sql($_POST['start_date'])."'
			  AND pcd_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (pcd.pc_id LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['created_by']!='') {
$sql.=" and pcd.pcd_prepared_by_id='".$_POST['created_by']."' ";
}


if ($_POST['yes_no']==0) {
$sql.="  AND pcd.pcd_w_cv='0'";
}
else {
$sql.=" AND pcd.pcd_w_cv='1'";
}


$sql .= " AND pcd.pcd_replenished='1' AND pcd_wid_breakdown='0' GROUP BY pcd_tax_type";
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);

$tax=array();
while($row = db_fetch($res))
{
if ($row['pcd_tax_type']!=''){
$tax[]=get_gl_account_name($row['pcd_tax_type']);
$tax_type[$row['pcd_tax_type']] ['tax_used']=$row['pcd_tax_type'];
}
}

$from_head = array("SEQ#","Date","Employee Name",'REF#','Payee','Purpose of Expense','TIN#');

$th =array_merge($from_head,$tax);
$th2=array_merge($th,$gls);
//print_r($th2);
array_push($th2,'Amount');

	foreach($th2 as $header)
	{
		$rep->sheet->writeString($rep->y, $x, html_entity_decode($header), $format_bold_title);
		$x++;
	}
	$rep->y++;


$k = 0;
$type=ST_PETTYCASH;
$sql="select pcd.*,pch.pc_employee_name,gl.account, gl.amount from ".TB_PREF."petty_cash_details as pcd
left join ".TB_PREF."petty_cash_header as pch
on pcd.pc_id=pch.pc_id
left join ".TB_PREF."gl_trans_temp as gl on pcd.pcd_id=gl.type_no
";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE pcd_date >= '".date2sql($_POST['start_date'])."'
			  AND pcd_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (pcd.pc_id LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

$sql .= " AND gl.type='$type' AND gl.account NOT IN (1410,1410010,1410011,1410012,1410013) AND gl.amount>0  AND pcd.pcd_replenished='1' AND pcd_wid_breakdown='0'";

if ($_POST['yes_no']==0) {
$sql.="  AND pcd.pcd_w_cv='0' AND gl.posted='0' ";
}
else {
$sql.=" AND pcd.pcd_w_cv='1' AND gl.posted='1' ";
}

if ($_POST['created_by']!='') {
$sql.=" and pcd.pcd_prepared_by_id='".$_POST['created_by']."' ";
}


$sql.=" ORDER BY pc_id";
//display_error($sql);
$res = db_query($sql);

$data=array();
while($row = db_fetch($res))
{
$data[$row['pcd_id']] ['seq']=$row['pc_id'];
$data[$row['pcd_id']] ['date']=$row['pcd_date'];
$data[$row['pcd_id']] ['employee_name']=$row['pc_employee_name'];
$data[$row['pcd_id']] ['ref']=$row['pcd_ref'];
$data[$row['pcd_id']] ['payee']=$row['pcd_payee'];
$data[$row['pcd_id']] ['purpose']=$row['pcd_purpose'];
$data[$row['pcd_id']] ['tin']=$row['pcd_tin'];
//$data[$row['pcd_id']] [$row['pcd_gl_type']]=$row['pcd_amount']-$row['pcd_tax'];
$data[$row['pcd_id']] [$row['pcd_gl_type']]=$row['amount'];
$data[$row['pcd_id']] [$row['pcd_tax_type']]=$row['pcd_tax'];
$data[$row['pcd_id']] ['sub_t_amount']+=$row['amount']+$row['pcd_tax'];
//$data[$row['pcd_id']] ['sub_t_tax']+=$row['pcd_tax'];
$data[$row['pcd_id']] ['amount']=$row['pcd_amount'];
//display_error($data);
}

foreach($data as $emp_id=>$details) {
		$c ++;
		$x = 0;
		
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;	
		$rep->sheet->writeString($rep->y, $x, $details['seq'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($details['date']),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $details['employee_name'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $details['ref'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $details['payee'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, html_entity_decode($details['purpose']),$format_left);
		$x++;

		$rep->sheet->writeString($rep->y, $x, $details['tin'],$format_left);
		$x++;
		
		foreach($tax_type as $tax) 
		{
		$rep->sheet->writeNumber($rep->y, $x, $details[$tax['tax_used']], $format_accounting);
		$x++;
		$tax_total+=$details[$tax['tax_used']];
		$tax_line_total[$tax['tax_used']]+=$details[$tax['tax_used']];
		}

		foreach($gl_types as $gl) 
		{
		$rep->sheet->writeNumber($rep->y, $x, $details[$gl['gl_used']], $format_accounting);
		$x++;
		$t_total+=$details[$gl['gl_used']];
		$per_line_total[$gl['gl_used']]+=$details[$gl['gl_used']];
		}

		$rep->sheet->writeNumber($rep->y, $x, $details['sub_t_amount'], $format_accounting);
		$x++;
		
//print_r($per_line_total);
$rep->y++;
}

	$x=1;
	
	$rep->sheet->writeString($rep->y, $x, '', $format_bold_right);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold_right);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold_right);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold_right);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold_right);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_bold_right);
	$x++;
	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold_right);
	$x++;

foreach($tax_line_total as $sub_tax) {
	$rep->sheet->writeNumber($rep->y, $x, abs($sub_tax), $format_accounting);
	$x++;
}

	
foreach($per_line_total as $sub_t) 
{
	$rep->sheet->writeNumber($rep->y, $x, abs($sub_t), $format_accounting);
	$x++;	

}		
	$rep->sheet->writeNumber($rep->y, $x, abs($t=$t_total+$tax_total), $format_accounting);
	$x++;						
	$rep->End();
}


$delete_id = find_submit('delete_selected');

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_SUPPINVOICE;

   	display_notification_centered( _("CV has been created"));

	display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this CV")));

   	hyperlink_no_params($_SERVER['PHP_SELF'], _("Create &Another CV"));

	display_footer_exit();
}

//====================================start heading=========================================
start_form();
//if (!isset($_POST['start_date']))
//$_POST['start_date'] = '01/01/'.date('Y');

global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

start_table();
get_petty_cash_user_list_cells('Created By:','created_by');
yesno_list_cells('Replenished w/ CV :', 'yes_no', '', 'Yes', 'No');
ref_cells('Sequence #:', 'trans_no');
date_cells('From :', 'start_date');
date_cells('To :', 'end_date');
submit_cells('search', 'Search');
end_table();
br();
display_heading("Petty Cash Summary from ".$_POST['start_date']." to ".$_POST['end_date']."");	
br();	
br();
//====================================end of heading=========================================

//====================================display table=========================================
submit_center('dl_excel','Download as excel file');
br();

function get_last_cv_date()
{
	$sql = "SELECT max(tran_date) as tran_date FROM ".TB_PREF."gl_trans_temp where type='59' and posted='1'";
	$result = db_query($sql);
	$row=db_fetch($result);
	$last_cv_date=$row['tran_date'];
	return $last_cv_date; 
}

								function auto_create_cv($invoice_no,$apv_cv_date)
								{
									$trans_no = $invoice_no;
									$type = 20;
									
									//==============GET APV TYPE 20
									$apv_header = get_apv_supp_trans($trans_no);
									$real_cv_trans[] = array(20, $trans_no, $apv_header['TotalAmount']);
									
									$payable_amount = $apv_header['TotalAmount'];
									$total_ewt_ex = 0;
									
									if ($apv_header['ewt'] > 0)
									{
											$total_ewt_ex += $apv_header['ewt'];
									}
									
									$dm_used = 0;
									//========================
		
									//=======AUTO CREATE CV
									$cv_no = get_next_cv_no(false);
									
									$cv_id = insert_cv($cv_no,$apv_cv_date,$payable_amount,PT_SUPPLIER,$apv_header['supplier_id'], 
										$real_cv_trans, sql2date($apv_header['due_date']), $total_ewt_ex);
										
									//=======CV approval auto approve
									
									$sql = "UPDATE ".TB_PREF."cv_header SET approved = 1
											WHERE id = $cv_id";
									db_query($sql,'failed to approve CV');
								
									//add_audit_trail(99, $cv_id, Today(), 'CV approved');
									
									// $sql = "INSERT INTO ".TB_PREF."audit_trail"
									// . " (type, trans_no, user, fiscal_year, gl_date, description, gl_seq)
									// VALUES('99',".db_escape($cv_id).",'598',
									// ".get_company_pref('f_year').",".db_escape(date2sql(Today())).",".db_escape('CV approved').","."0)";
									// db_query($sql,"Failed to add audit trail.");
									return $cv_id;
								}
					
function new_petty_cash_cv($myBranchCode)
{
global $Refs;	
begin_transaction();
$d1=$_POST['date1'];
$d2=$_POST['date2'];
$date_from=date2sql($_POST['date1']);
$date_to=date2sql($_POST['date2']);
$created_by=$_POST['created_by2'];

$apv_cv_date=$_POST['apv_cv_date'];

//$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
//display_error($myBranchCode);
switch ($myBranchCode) {
    case "srsn":
		$supplier_id='110';
        break;
    case "srst":
        $supplier_id='88';
        break;
    case "srsg":
        $supplier_id='150';
        break; 
	case "srsnav":
        $supplier_id='181';
        break;
	case "sri":
        $supplier_id='411';
        break;
	case "srsm":
       $supplier_id='554';
        break;
	case "srsmr":
       $supplier_id='554';
        break;
	case "srsc":
        $supplier_id='591';
        break;
	case "srsant2":
        $supplier_id='110';
        break;
	case "srsant1":
        $supplier_id='110';
        break;
	case "srscain":
        $supplier_id='110';
        break;
	case "srscain2":
        $supplier_id='110';
        break;
	case "srsval":
        $supplier_id='591';
        break;
	case "srspat":
        $supplier_id='591';
        break;
	case "srsret":
        $supplier_id='13';
        break;
	case "srspun":
        $supplier_id='591';
        break;
	case "srscom":
        $supplier_id='591';
        break;
	case "srsbsl":
       $supplier_id='591';
       break;	
	case "srsh":
       $supplier_id='10';
       break;	
	case "srssanp":
       $supplier_id='110';
       break;	
	 case "srstu":
       $supplier_id='591';
       break;
	 case "srsal":
       $supplier_id='110';
       break;
	 case "srstu":
       $supplier_id='591';
        break;
	case "srsbgb":
       $supplier_id='110';
        break;
	case "srsmol":
       $supplier_id='110';
        break;
		
				case "srsman":
		   $supplier_id='110';
			break;
		case "srsmon":
		   $supplier_id='110';
			break;
		
		
    default:
        echo "No supplier_id.";
}

$accounts_payable = 2000010; //accounts_payable
$advances_to_supplier = 1440; // advances_to_supplier

$invoice_no = add_supp_trans(ST_SUPPINVOICE, $supplier_id,$apv_cv_date,$apv_cv_date,$_POST['reference'],'',input_num('t_amount'), 0, 0,"",0,0,$apv_cv_date,1,0,0);

$cv_id=auto_create_cv($invoice_no,$apv_cv_date);

$sql="SELECT gl.tran_date as tran_date, gl.type as type, 
gl.type_no as type_no, gl.account as account, sum(gl.amount) as amount 
FROM ".TB_PREF."gl_trans_temp as gl
left join ".TB_PREF."petty_cash_details as pcd
on pcd.pcd_id=gl.type_no
where gl.type='59' 
and gl.amount>0 and gl.tran_date>='$date_from' and gl.tran_date<='$date_to'
and gl.posted='0' and pcd.pcd_w_cv='0'";

if ($created_by!='') {
$sql.=" and pcd.pcd_prepared_by_id='".$created_by."'";
}

$sql.=" group by account";

//display_error($sql);
$res = db_query($sql);
while($row = db_fetch($res))
{
$type=$row['type'];
$type_no=$row['type_no'];
$account=$row['account'];
$amount=$row['amount'];
$t_amount+=$row['amount'];
//display_error($row['amount']);

add_supp_invoice_item(ST_SUPPINVOICE, $invoice_no, $stock_id+0, $description='',$account, $amount, $unit_tax+0, $quantity+0, $grn_item_id+0, $po_detail_item_id+0, $memo_="Petty Cash ".$d1." - ".$d2,$err_msg="", $i_uom='',  $multiplier=1);
add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, $apv_cv_date, $account, 0, 0,$amount, $supplier_id, "", $rate);
}

add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, $apv_cv_date, $accounts_payable, 0, 0,-input_num('t_amount'), $supplier_id, "", $rate);


//update cv status from gl_trans_temp and petty_cash_details.
$sql="SELECT * FROM 0_petty_cash_details where (pcd_date>='$date_from'  and pcd_date<='$date_to') and pcd_w_cv='0' and pcd_replenished='1' ";

if ($created_by!='') {
$sql.=" and pcd_prepared_by_id='".$created_by."'";
}
$res = db_query($sql);
while($row = db_fetch($res))
{
		$pcd_id=$row['pcd_id'];
		$sql_update_pcd = "UPDATE ".TB_PREF."petty_cash_details SET 
				pcd_w_cv = 1, pcd_cv_id = '".$cv_id."'
				WHERE pcd_id='".$pcd_id."'";
		//display_error($sql_update_pcd);
		db_query($sql_update_pcd,'failed to update petty_cash_details.');
		
		$sql_update_gl_temp = "UPDATE ".TB_PREF."gl_trans_temp SET 
				posted = 1, memo_='".$cv_id."'
				WHERE type_no='".$pcd_id."'";
		//display_error($sql_update_gl_temp);
		db_query($sql_update_gl_temp,'failed to update temp gl trans.');
}

add_comments(ST_SUPPINVOICE, $invoice_no, $apv_cv_date, $memo_="Petty Cash ".$d1." - ".$d2);
$Refs->save(ST_SUPPINVOICE, $invoice_no, $_POST['reference']);

meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
commit_transaction();
}
	
if(isset($_POST['create_apv'])){
$d1=$_POST['date1'];
$d2=$_POST['date2'];
$date_from=date2sql($_POST['date1']);
$date_to=date2sql($_POST['date2']);
$cv_end_date=$_POST['cv_end_date'];

// if ($date_to<=$cv_end_date){
// display_error("Last Petty Cash CV Date is ".sql2date($cv_end_date).", You cannot create CV if end date of the current transaction is less than or equal to last CV date.");
// return false;
// }
// else if ($date_from<=$cv_end_date){
// display_error("Last Petty Cash CV Date is ".sql2date($cv_end_date).", You cannot create CV if start date of the current transaction is less than or equal to last CV date.");
// return false;
// }
// else {
$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];	
new_petty_cash_cv($myBranchCode);
//}
}
	
div_start('table_');
start_table($table_style2.' width=90%');
$th = array();

//gl header
$sql="select pcd_gl_type from ".TB_PREF."petty_cash_details as pcd
left join ".TB_PREF."petty_cash_header as pch
on pcd.pc_id=pch.pc_id";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE pcd_date >= '".date2sql($_POST['start_date'])."'
			  AND pcd_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (pcd.pc_id LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['created_by']!='') {
$sql.=" and pcd.pcd_prepared_by_id='".$_POST['created_by']."' ";
}

if ($_POST['yes_no']==0) {
$sql.="  AND pcd.pcd_w_cv='0'";
}
else {
$sql.=" AND pcd.pcd_w_cv='1'";
}

$sql .= " AND pcd.pcd_replenished='1' AND pcd_wid_breakdown='0' GROUP BY pcd_gl_type";
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);
$gls=array();
while($row = db_fetch($res))
{
$gls[]=get_gl_account_name($row['pcd_gl_type']);
$gl_types[$row['pcd_gl_type']] ['gl_used']=$row['pcd_gl_type'];
}


//tax header
$sql="select pcd_tax_type from ".TB_PREF."petty_cash_details as pcd
left join ".TB_PREF."petty_cash_header as pch
on pcd.pc_id=pch.pc_id";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE pcd_date >= '".date2sql($_POST['start_date'])."'
			  AND pcd_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (pcd.pc_id LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['created_by']!='') {
$sql.=" and pcd.pcd_prepared_by_id='".$_POST['created_by']."' ";
}


if ($_POST['yes_no']==0) {
$sql.="  AND pcd.pcd_w_cv='0'";
}
else {
$sql.=" AND pcd.pcd_w_cv='1'";
}


$sql .= " AND pcd.pcd_replenished='1' AND pcd_wid_breakdown='0' GROUP BY pcd_tax_type";
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);

$tax=array();
while($row = db_fetch($res))
{
if ($row['pcd_tax_type']!=''){
$tax[]=get_gl_account_name($row['pcd_tax_type']);
$tax_type[$row['pcd_tax_type']] ['tax_used']=$row['pcd_tax_type'];
}
}

$from_head = array("SEQ#","Trans#","Date","Employee Name",'REF#','Payee','Purpose of Expense','TIN#');

$th =array_merge($from_head,$tax);
$th2=array_merge($th,$gls);
//print_r($th2);
array_push($th2,'Amount');
//print_r($th);

// if (db_num_rows($res) > 0)
	 table_header($th2);
// else
// {
	// display_heading('No result found');
	// display_footer_exit();
// }

$k = 0;
$type=ST_PETTYCASH;
$sql="select pcd.*,pch.pc_employee_name,gl.account, gl.amount from ".TB_PREF."petty_cash_details as pcd
left join ".TB_PREF."petty_cash_header as pch
on pcd.pc_id=pch.pc_id
left join ".TB_PREF."gl_trans_temp as gl on pcd.pcd_id=gl.type_no
";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE pcd_date >= '".date2sql($_POST['start_date'])."'
			  AND pcd_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (pcd.pc_id LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

$sql .= " AND gl.type='$type' AND gl.account NOT IN (1410,1410010,1410011,1410012,1410013) AND gl.amount>0  AND pcd.pcd_replenished='1' AND pcd_wid_breakdown='0'";

if ($_POST['yes_no']==0) {
$sql.="  AND pcd.pcd_w_cv='0' AND gl.posted='0' ";
}
else {
$sql.=" AND pcd.pcd_w_cv='1' AND gl.posted='1' ";
}

if ($_POST['created_by']!='') {
$sql.=" and pcd.pcd_prepared_by_id='".$_POST['created_by']."'";
}


$sql.=" ORDER BY pc_id";
	
//display_error($sql);
$res = db_query($sql);

$data=array();
while($row = db_fetch($res))
{
$data[$row['pcd_id']] ['seq']=$row['pc_id'];
$data[$row['pcd_id']] ['trans_no']=$row['pcd_id'];
$data[$row['pcd_id']] ['date']=$row['pcd_date'];
$data[$row['pcd_id']] ['employee_name']=$row['pc_employee_name'];
$data[$row['pcd_id']] ['ref']=$row['pcd_ref'];
$data[$row['pcd_id']] ['payee']=$row['pcd_payee'];
$data[$row['pcd_id']] ['purpose']=$row['pcd_purpose'];
$data[$row['pcd_id']] ['tin']=$row['pcd_tin'];
//$data[$row['pcd_id']] [$row['pcd_gl_type']]=$row['pcd_amount']-$row['pcd_tax'];
$data[$row['pcd_id']] [$row['pcd_gl_type']]=$row['amount'];
$data[$row['pcd_id']] [$row['pcd_tax_type']]=$row['pcd_tax'];
$data[$row['pcd_id']] ['sub_t_amount']+=$row['amount']+$row['pcd_tax'];
//$data[$row['pcd_id']] ['sub_t_tax']+=$row['pcd_tax'];
$data[$row['pcd_id']] ['amount']=$row['pcd_amount'];
//display_error($data);
}

//print_r($gl_types);
//br();

foreach($data as $emp_id=>$details) {
//print_r($details);
//br();
alt_table_row_color($k);
label_cell("<b>".$details['seq']."</b>");
label_cell($details['trans_no']);
label_cell(sql2date($details['date']));
label_cell($details['employee_name'] ,'nowrap');
label_cell($details['ref'] ,'nowrap');
label_cell($details['payee'] ,'nowrap');
label_cell($details['purpose'] ,'nowrap');
label_cell($details['tin'] ,'nowrap');

foreach($tax_type as $tax) 
{
amount_cell($details[$tax['tax_used']],false);
$tax_total+=$details[$tax['tax_used']];
$tax_line_total[$tax['tax_used']]+=$details[$tax['tax_used']];
}

foreach($gl_types as $gl) 
{
amount_cell($details[$gl['gl_used']],false);
$t_total+=$details[$gl['gl_used']];
$per_line_total[$gl['gl_used']]+=$details[$gl['gl_used']];
}

amount_cell($details['sub_t_amount'],false);
//amount_cell($details['sub_t_tax'],false);
}

start_row();
label_cell('');
label_cell('');
label_cell('');
label_cell('');
label_cell('');
label_cell('');
label_cell('');
label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');

//print_r($per_line_total);

foreach($tax_line_total as $sub_tax) {
label_cell("<font color=#880000><b>".number_format2(abs($sub_tax),2)."<b></font>",'align=right');
}

foreach($per_line_total as $sub_t) 
{
label_cell("<font color=#880000><b>".number_format2(abs($sub_t),2)."<b></font>",'align=right');
}

label_cell("<font color=#880000><b>".number_format2(abs($t=$t_total+$tax_total),2)."<b></font>",'align=right');
//label_cell("<font color=#880000><b>".number_format2(abs($tax_total),2)."<b></font>",'align=right');
//print_r($sub_t[$gl['gl_used']]);
//label_cell("<font color=#880000><b>".number_format2(abs($t_total),2)."<b></font>",'align=right');
end_row();	
end_table();

br();
if ($_POST['yes_no']==0) {
start_table();
$refref = $Refs->get_next(ST_SUPPINVOICE);
$refref = str_replace('NT','',$refref);
$refref = 'NT'.$refref;
//ref_row(_("APV No.:"), 'reference', '', $refref);
hidden('reference',$refref);
hidden('date1',$_POST['start_date']);
hidden('date2',$_POST['end_date']);
hidden('created_by2',$_POST['created_by']);
hidden('t_amount',$t);
$last_cv_date= get_last_cv_date();
hidden('cv_end_date',$last_cv_date);
date_cells('Date of CV:', 'apv_cv_date');
//start_row();
//label_cell("<b>LAST CV DATE: "."<font color='red'>".sql2date($last_cv_date)."</font></b> ");
//end_row();	
submit_cells('create_apv', 'Create CV', "align=center", true, true,'ok.gif');
end_table();
}
div_end();
end_form();
end_page();
?>