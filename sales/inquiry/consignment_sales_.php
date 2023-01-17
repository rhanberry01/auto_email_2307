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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

if (isset($_GET['xls']) AND isset($_GET['filename']) AND isset($_GET['unique']))
{
	$path_to_root = "../..";
	include_once($path_to_root . "/includes/session.inc");
	
	header('Content-Disposition: attachment; filename='.$_GET['filename']);

	$target = $path_to_root.'/company/'.$_SESSION['wa_current_user']->company.'/pdf_files/'.$_GET['unique'];
	readfile($target);
	
	exit;
}

$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/db/sales_remittance.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/modules/checkprint/includes/consignment_mailer.inc");



	//start of excel report
if(isset($_POST['dl_excel']))
{
	cashier_summary_per_day_excel();
	exit;
}

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $start = $time;

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page("Consignment Sales", false, false, "", $js);

//------------------------------------------------------------------------------------------------

function cashier_summary_per_day_excel()
{
	global $path_to_root;
	
	$com = get_company_prefs();
	
	$date_f= $_POST['date_'];
	$date_t= $_POST['TransToDate'];
	
	$date_from = date2sql($_POST['date_']);
	$date_to = date2sql($_POST['TransToDate']);
	$acquiring_bank_id = $_POST['acquiring_bank_id'];

	include_once($path_to_root . "/reporting/includes/excel_report.inc");
	
	$params =   array( 	0 => '',
    				    1 => array('text' => _('Date :'), 'from' => $date_f,'to' => ''),
                    	2 => array('text' => _('Type'), 'from' => $h, 'to' => '')
						);

    $rep = new FrontReport(_('Consignment Sales'), "Consignment Sales", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'CREDIT/ DEBIT CARD RECONCILIATION REPORT', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date From: '.$date_f.' To: '.$date_t.'', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	
	$rep->sheet->setColumn(1,9,10); //set column width
	
	//from 10 to 16
	$rep->sheet->setColumn(10,16,11); //set column width

	
	//setColumn(from,to,size);
	

	

$x=0;
	

$th = array(' ','BankCard', 'Type', _("Batch#"), _("Trans#"), _("TransDate"),'DateRemit','Invoice#', 'Merchant ID', 'Card#', 'Amount', 'MerchantFee','WTax','Debit Net','Credit Net','Date Paid','Remarks');
	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;

$sql="SELECT * FROM ".TB_PREF."acquiring_deductions WHERE (date_paid>='$date_from' AND date_paid<='$date_to')";

if($_POST['acquiring_bank_id']!='')
{
$sqlacq="SELECT * FROM ".TB_PREF."acquiring_banks WHERE id='$acquiring_bank_id'";
$result=db_query($sqlacq);
//display_error($sqlacq);
while($rowacq = db_fetch($result))
{
$acq_bank_name=$rowacq['acquiring_bank'];
}

$sql.=" AND (p_bank_card='$acq_bank_name' OR p_bank_card LIKE '%$acq_bank_name%')";
}

$sql.=" ORDER BY p_ref_id, p_terminal_id, p_approval_no asc";

$res=db_query($sql);
//display_error($sql);
	while($row = db_fetch($res))
	{
		$c ++;
		$x = 0;
		// alt_table_row_color($k);
		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['p_bank_card'],$format_left);
		$x++;
		
	if ($row['p_tender_type']=='13')
	{
		$rep->sheet->writeString($rep->y, $x, 'Credit', $format_left);
		$x++;
	}
	
	if ($row['p_tender_type']=='14')
	{
	$rep->sheet->writeString($rep->y, $x, 'Debit', $format_left);
	$x++;
	} 
		
		$rep->sheet->writeNumber($rep->y, $x, $row['p_ref_id'], $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['p_trans_no'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['p_trans_date'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['p_remittance_date'], $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['p_approval_no'], $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['p_terminal_id'], $format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $row['p_account_no'], $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['p_deposited_amount'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['p_mfeeamount'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['p_wtaxamount'], $format_accounting);

	$x++;
		
	if ($row['p_tender_type']=='13')
	{
		$rep->sheet->writeString($rep->y, $x, '', $format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $row['p_net_total'], $format_accounting);
		$x++;
		$credit_net_total+=$row['p_net_total'];
	}
	
	if ($row['p_tender_type']=='14')
	{
	$rep->sheet->writeNumber($rep->y, $x, $row['p_net_total'], $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_left);
	$x++;
	$debit_net_total+=$row['p_net_total'];
	} 
	
	$rep->sheet->writeString($rep->y, $x, $row['date_paid'], $format_left);
	$x++;
	$rep->sheet->writeString($rep->y, $x, $row['p_remarks'], $format_accounting);
	$rep->y++;
		
	$t_deposited_amount+=$row['p_deposited_amount'];
	$t_mfeeamount+=$row['p_mfeeamount'];
	$t_wtaxamount+=$row['p_wtaxamount'];
	}
	
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_deposited_amount, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_mfeeamount, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $t_wtaxamount, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $debit_net_total, $format_accounting);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, $credit_net_total, $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);
	$x++;
	$rep->sheet->writeString($rep->y, $x, '', $format_accounting);

	
	$rep->End();
	
}
//end of excel report

function get_vendor_details($vendorcode)
{
	$sql = "SELECT * FROM vendor
			WHERE vendorcode = '$vendorcode'";
	$res =  ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}

function get_vendor_commission($vendorcode)
{
	$sql = "SELECT reordermultiplier FROM vendor
			WHERE vendorcode = '$vendorcode'";
	$res =  ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row;
}


								function auto_create_cv($invoice_no)
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
									$cv_no = get_next_cv_no();
									
									$cv_id = insert_cv($cv_no,Today(),$payable_amount,PT_SUPPLIER,$apv_header['supplier_id'], 
										$real_cv_trans, sql2date($apv_header['due_date']), $total_ewt_ex);
										
									//=======CV approval auto approve
									
									$sql = "UPDATE ".TB_PREF."cv_header SET approved = 1 WHERE id = $cv_id";
									db_query($sql,'failed to approve CV');
									
									//add_audit_trail(99, $cv_id, Today(), 'CV approved');
									
									return $cv_id;
								}
								

function new_consignment_cv($create_cv_id,$invoice_num)
{
//$create_cv_id is supplier code.
global $Refs;	
begin_transaction();

$accounts_payable = 2000010; //accounts_payable
//$account=4000030;

$refref = $Refs->get_next(ST_SUPPINVOICE);
$refref = str_replace('NTR','',$refref);
$refref = str_replace('NT','',$refref);
$refref = str_replace('R','',$refref);
//$refref = 'NT'.$refref;


$res=get_vendor_cons_header($create_cv_id);
while($row = db_fetch($res))
{
$amount=$row['t_sales'];
$supp_code=$row['supp_code'];
$start_date=$row['start_date'];
$end_date=$row['end_date'];
}

$ms_row=get_vendor_commission($supp_code);

$amount=$amount-(($ms_row['reordermultiplier']/100) * $amount);


check_my_suppliers($supp_code);
$sup_id=get_supplier_id_by_supp_ref($supp_code);


	$sql = "SELECT tax_group_id FROM ".TB_PREF."suppliers WHERE supplier_id=".db_escape($sup_id);
	$result = db_query($sql, "could not get supplier");
	$row = db_fetch($result);
	$vat_stat=$row['tax_group_id'];
	
	// display_error($sql);
	// display_error($vat_stat);
	// display_error($sup_id);

	
if ($vat_stat==1) {
	$input_tax=1410010; //input tax goods for resale
	$purchases = 5450; // purchases vat
	$amount2=$amount/1.12;
	$tax_amount=$amount2 * 0.12;
	$invoice_no = add_supp_trans(ST_SUPPINVOICE, $sup_id,Today(),Today(),$refref,$supp_ref='INVOICE#: '.$invoice_num,$amount, 0, 0,"",0,0,Today(),1,0,0);
	
	add_supp_invoice_item(ST_SUPPINVOICE, $invoice_no, $stock_id+0, $description='',$purchases, $amount-$tax_amount, $unit_tax+0, $quantity+0, $grn_item_id+0, $po_detail_item_id+0, $memo_="Sales from ".sql2date($start_date)." to ".sql2date($end_date)."", $err_msg="", $i_uom='',  $multiplier=1);
	add_supp_invoice_item(ST_SUPPINVOICE, $invoice_no, $stock_id+0, $description='',$input_tax, $tax_amount, $unit_tax+0, $quantity+0, $grn_item_id+0, $po_detail_item_id+0, $memo_='', $err_msg="", $i_uom='',  $multiplier=1);
	add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $purchases, 0, 0,$amount-$tax_amount, $sup_id, "", $rate);
	add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $input_tax, 0, 0,$tax_amount, $sup_id, "", $rate);
	add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $accounts_payable, 0, 0,-$amount, $sup_id, "", $rate);
	}

else {
$purchases=5400;
$invoice_no = add_supp_trans(ST_SUPPINVOICE, $sup_id,Today(),Today(),$refref,$supp_ref='INVOICE#: '.$invoice_num,$amount, 0, 0,"",0,0,Today(),1,0,0);
add_supp_invoice_item(ST_SUPPINVOICE, $invoice_no, $stock_id+0, $description='',$purchases, $amount, $unit_tax+0, $quantity+0, $grn_item_id+0, $po_detail_item_id+0, $memo_="Sales from ".sql2date($start_date)." to ".sql2date($end_date)."", $err_msg="", $i_uom='',  $multiplier=1);
add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $purchases, 0, 0,$amount, $sup_id, "", $rate);
add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, Today(), $accounts_payable, 0, 0,-$amount, $sup_id, "", $rate);
}


$cv_id=auto_create_cv($invoice_no);

add_comments(ST_SUPPINVOICE, $invoice_no, Today(), $memo_="Sales from ".sql2date($start_date)." to ".sql2date($end_date).", Invoice#: ".$invoice_num);
$Refs->save(ST_SUPPINVOICE, $invoice_no, $refref);
//meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");

	$sql = "UPDATE ".TB_PREF."cons_sales_header SET 
				cv_id = '$cv_id',
				invoice_num='$invoice_num'
			WHERE cons_sales_id=$create_cv_id";
	db_query($sql,'failed to update consignment header');


display_notification("CV Created");
commit_transaction();
}

//---------------SUBMIT---------------------
$create_cv_id = find_submit('create_cv',false);
if ($create_cv_id != '')
{
		$invoice_num=$_POST['invoice_num'.$create_cv_id];
		
		if($invoice_num!='') {
		new_consignment_cv($create_cv_id,$invoice_num);
		}
		else {
		display_error('Invoice Number is empty.');
		return false;
		}
		//$Ajax->activate('table_'); 
}

$approve_add = find_submit('print_sales',false);
if ($approve_add!='') {
//global $Ajax;
//display_error($approve_add);
		echo "<script type='text/javascript'>
				window.open('".$path_to_root . "/modules/checkprint/consignment_print.php?",'cons_id='.$approve_add."',
				'_blank','width=1000px,height=700px,scrollbars=0,resizable=no')
				</script>";
				
//$Ajax->activate('table_'); 			
}

$send_new = find_submit('send_email',false);
if ($send_new != '')
{
global $Ajax;
//checking emails
	$res=get_vendor_cons_header($send_new);
	$row=db_fetch($res);
	$supp_code=$row['supp_code'];
	check_my_suppliers_email($supp_code);
		//send_that_sales($send_new,0);
$Ajax->activate('table_'); 
}

$send_new2 = find_submit('send_email2',false);
if ($send_new2 != '')
{
global $Ajax;
		//send_that_sales($send_new2,0);
$Ajax->activate('table_'); 
}


if (isset ($_POST['generate'])) {
	//mssql_query("BEGIN TRANSACTION");
	begin_transaction();

	$d_month=$_POST['d_month'];
	$d_year=$_POST['d_year'];

	//display_error($d_year);
	$start_date=__date($_POST['d_year'],$_POST['d_month'],1);
	$end_date=end_month($start_date);

	$myDB=$db_connections[$_SESSION["wa_current_user"]->company]["db_133"];
	get_ms_cons_header($start_date,$end_date,$myDB);
	
	commit_transaction();
	//mssql_query("COMMIT TRANSACTION");

	display_notification("Auto import of MS to MYSQl is successful");
}
//---------------END OF SUBMIT-----------------

function month_select_box( $field_name = 'month',$selected) {
$month_options = '';

//display_error($selected);
	for( $i = 1; $i <= 12; $i++ ) {
	$month_num = str_pad( $i, 2, 0, STR_PAD_LEFT );
	$month_name = date( 'F', mktime( 0, 0, 0, $i + 1, 0, 0, 0 ) );

	if($month_num==$selected) {
	$x='selected';
	}
	else {
	$x='';
	}

	//display_error($month_num);

	$month_options .= '<option value="' .  $month_num  . '" '.$x.'>' . $month_name . '</option>';
	}
return '<select name="' . $field_name . '">' . $month_options . '</select>';
}


function month_list_cells($label, $name, $selected_id, $all_option=false)
{
	if ($label != null)
		echo "<td>$label</td><td>\n";
		echo month_select_box($name, $selected_id, $all_option);
		echo "</td>\n";
}


function yearDropdownMenu($name, $start_year, $end_year = null, $id='year_select', $selected=null) {
 
        // curret year as end year
		$end_year = is_null($end_year) ? date('Y') : $end_year;
		
		// the current year
        $selected = is_null($selected) ? date('Y') : $selected;
 
        // range of years 
        $r = range($start_year, $end_year);
 
        //create the HTML select
        $select = '<select name="'.$name.'" id="'.$id.'">';
        foreach( $r as $year )
        {
            $select .= "<option value=\"$year\"";
            $select .= ($year==$selected) ? ' selected="selected"' : '';
            $select .= ">$year</option>\n";
        }
        $select .= '</select>';
        return $select;
    }

	
function year_list_cells($label,$name, $start_year, $end_year, $id='year_select', $selected)
{
		if ($label != null)
		echo "<td>$label</td><td>\n";
		echo yearDropdownMenu($name,$start_year, $end_year = null, $id='year_select', $selected=null);
		echo "</td>\n";
}



function get_ms_cons_header($start_date,$end_date,$myDB) {

	$myServer = "192.168.0.133";
	$myUser = "markuser";
	$myPass = "tseug";
	//$myDB = "SRSMNOVA"; 
	//display_error($myDB);

	$dbhandle = mssql_connect($myServer, $myUser, $myPass)
	or die("Couldn't connect to SQL Server on $myServer"); 

	$selected = mssql_select_db($myDB, $dbhandle)
	or die("Couldn't open database $myDB"); 

	$sql="select v_vendorcode as Vendor, v_description as Description, sum(f_qty) as 
	QTY,sum(f_extended) Sales,sum(f_auc) as CostofSales from 
	(SELECT
	fs.ProductID as f_productid,
	v.vendorcode as v_vendorcode,
	v.[description] as v_description,
	vp.VendorProductCode as vp_product_code,
	vp.uom as vp_uom,
	fs.TotalQty as f_qty,
	fs.extended as f_extended,
	fs.productid,fs.AverageUnitCost * case when ([return]=1)
	then convert(money,0-fs.totalqty) else fs.totalqty end as f_auc
	FROM [FinishedSales] as fs
	left join Products as p
	on fs.ProductID = p.ProductID
	left join VENDOR_Products as vp
	on fs.ProductID =vp.ProductID 
	left join [vendor] as v
	on vp.VendorCode=v.vendorcode
	where fs.LogDate >= '".date2sql($start_date)." 00:00:00' and fs.LogDate <= '".date2sql($end_date)." 00:00:00' and fs.Voided=0 and vp.defa=1 and v.Consignor=1";
	$sql.=") as a group by v_vendorcode,v_description";
	//and v.vendorcode='IMEXCO001'
	//display_error($sql);
	$res=mssql_query($sql);


	while($row = mssql_fetch_array($res))
	{
	//display_error('a');
	$sql = "Select * from ".TB_PREF."cons_sales_header where supp_code=".db_escape($row['Vendor'])." and start_date='".date2sql($start_date)."' and end_date='".date2sql($end_date)."'";
	$result = db_query($sql);
	
	if(mysql_num_rows($result)) {
	display_error('Sales from Supplier ('.$row['Description'].') Already Exist.');
	}
	else if ($row['Sales']==0) {
	display_error('Sales from Supplier'.$row['Description'].' is zero (0).');
	}
	else{
	$sql = "INSERT INTO ".TB_PREF."cons_sales_header (supp_code, start_date, end_date, supp_name, t_qty,t_sales,t_cos)
	VALUES (".db_escape($row['Vendor']).",'".date2sql($start_date)."','".date2sql($end_date)."',".db_escape($row['Description']).", ".$row['QTY'].",".$row['Sales'].",".$row['CostofSales'].")";
	//display_error($sql);
	db_query($sql, "Failed to generate data.");
	
	$id=db_insert_id();
	get_ms_cons_details($id,$row['Vendor'],$start_date,$end_date,$myDB);
	}
	}
	
	mssql_close($dbhandle);

}

function get_ms_cons_details($id,$supp_id,$start_date,$end_date,$myDB)
{

	$myServer = "192.168.0.133";
	$myUser = "markuser";
	$myPass = "tseug";
	//$myDB = "SRSMNOVA"; 
	//display_error($myDB);

	$dbhandle = mssql_connect($myServer, $myUser, $myPass)
	or die("Couldn't connect to SQL Server on $myServer"); 

	$selected = mssql_select_db($myDB, $dbhandle)
	or die("Couldn't open database $myDB"); 


	$sql="select v_vendorcode as Vendor,f_productid as ProductID,vp_product_code as VendorProductCode,v_description as VenDescription,vp_description as ProdDescription,
	vp_uom as UOM,sum(f_qty) as QTY,sum(f_extended) Sales,sum(f_auc) as CostofSales from 
	(SELECT
	fs.ProductID as f_productid,
	v.vendorcode as v_vendorcode,
	vp.[description] as vp_description,
	v.Description as v_description,
	vp.VendorProductCode as vp_product_code,
	vp.uom as vp_uom,
	fs.TotalQty as f_qty,
	fs.extended as f_extended,
	fs.productid,fs.AverageUnitCost * case when ([return]=1)
	then convert(money,0-fs.totalqty) else fs.totalqty end as f_auc
	FROM [FinishedSales] as fs
	left join Products as p
	on fs.ProductID = p.ProductID
	left join VENDOR_Products as vp
	on fs.ProductID =vp.ProductID 
	left join [vendor] as v
	on vp.VendorCode=v.vendorcode
	where fs.LogDate >= '".date2sql($start_date)." 00:00:00' and fs.LogDate <= '".date2sql($end_date)." 00:00:00' and fs.Voided=0 and vp.defa=1 and v.vendorcode='$supp_id') as a
	group by f_productid,v_vendorcode,v_description,vp_description,vp_product_code,vp_uom";
	$res=mssql_query($sql);


	while($row = mssql_fetch_array($res))
	{
	if ($row['Sales']==0) {
	display_error('Sales from Product ('.$row['VenDescription'].') is zero (0).');
	}
	else {
	$sql = "INSERT INTO ".TB_PREF."cons_sales_details (cons_det_id, prod_id, prod_code, description, uom, qty,sales,cos)
	VALUES (".$id.",'".$row['ProductID']."','".$row['VendorProductCode']."',".db_escape($row['ProdDescription']).", '".$row['UOM']."', ".$row['QTY'].",".$row['Sales'].",".$row['CostofSales'].")";
	//display_error($sql);
	db_query($sql, "Failed to generate data.");
	}
	}

	mssql_close($dbhandle);

}

start_form();
start_table();
start_row();
	// get_cashier_list_cells('Cashier:', 'cashier_id');
	supplier_list_ms_cells('Consignor: ', 'supp_id', null, true);
	//date_cells(_("Date:"), 'date_', '', null, -1);
	//date_cells(_(" To:"), 'TransToDate', '', null);
	month_list_cells('Month:','d_month',$_POST['d_month']);
	year_list_cells('Year:','d_year',1990);
	submit_cells('RefreshInquiry', _("Search"),'',_('Refresh Inquiry'),false,ICON_VIEW);
	//submit_cells('generate', _("Generate"),'',_('Generate Data'),false,ICON_DOWN);
end_row();
end_table(1);


$d_month=$_POST['d_month'];
$d_year=$_POST['d_year'];
$start_date=__date($_POST['d_year'],$_POST['d_month'],1);
$end_date=end_month($start_date);

//display_error($start_date);
//display_error($end_date);

$sql="select * from ".TB_PREF."cons_sales_header where start_date='".date2sql($start_date)."' and end_date='".date2sql($end_date)."'";

if ($_POST['supp_id']!='') {
$sql.=" and supp_code='".$_POST['supp_id']."'";
}

$sql.=" order by supp_name";

$res = db_query($sql);	
//display_error($sql);

//if (isset($_POST['RefreshInquiry'])){
	
display_heading("Product Sales Report from ".$start_date." to ".$end_date."");
br();
// echo "<center>Download as excel file</center>";
//submit_center('dl_excel','Download as excel file');
br();

div_start('table_');
//start of table display
start_table($table_style2 .'width=95%');
$th = array(' ','VendorCode', 'Description', _("Quantity"), 'CostOfSales',_("Sales"),'','','E-mail Address','Invoice#','CV');
table_header($th);


while($row = db_fetch($res))
{
	$c ++;
	alt_table_row_color($k);
	label_cell($c,'align=right');
	label_cell($row['supp_code']);
	label_cell($row['supp_name']);
	qty_cell($row['t_qty']);
	amount_cell($row['t_cos']);
	amount_cell($row['t_sales']);
	submit_cells('print_sales'.$row['cons_sales_id'], 'View Sales', "align=center", 'View Sales',false,ICON_PDF);
	
	if($row['email_sent']!=1) {
	submit_cells('send_email'.$row['cons_sales_id'], 'Send e-Mail', "align=center", false, true,ICON_EMAIL);
	}
	else {
	submit_cells('send_email2'.$row['cons_sales_id'], 'Resend', "align=center", false, true,ICON_DOC);
	//label_cell('<font color=red>Email Sent</font>',"align=center");
	}
	
	$sup_id=get_supplier_id_by_supp_ref($row['supp_code']);
	$supp_row = get_supplier($sup_id);
	label_cell('<font color=red>'.$supp_row['consignment_email'].'</font>',"align=center");
	
	if($row['invoice_num']=='') {
	text_cells('','invoice_num'.$row['cons_sales_id'],'',11);
	}
	else {
	label_cell('<font color=red>'.$row['invoice_num'].'</font>',"align=center");
	}
	
	if($row['invoice_num']=='') {
	submit_cells('create_cv'.$row['cons_sales_id'], 'Create CV', "align=center", false, false,ICON_MONEY);
	}
	else {
	$cv_num=get_cv_no($row['cv_id']);
	label_cell('<font color=red>'.$cv_num.'</font>',"align=center");
	}
	end_row();
	
	$t_qty+=$row['t_qty'];
	$t_sales+=$row['t_sales'];
	$t_cos+=$row['t_cos'];
}

mssql_close($dbhandle);
	//hidden('s_month',$_POST['d_month']);
	label_cell('');
	label_cell('');
	label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
	label_cell("<font color=#880000><b>".number_format2($t_qty,2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2($t_cos,2)."<b></font>",'align=right');
	label_cell("<font color=#880000><b>".number_format2($t_sales,2)."<b></font>",'align=right');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	label_cell('');
	end_row();
	
div_end();
end_table(1);

end_form();
end_page();

// $time = microtime();
// $time = explode(' ', $time);
// $time = $time[1] + $time[0];
// $finish = $time;
// $total_time = round(($finish - $start), 4);
// echo 'Page generated in '.$total_time.' seconds.';
?>