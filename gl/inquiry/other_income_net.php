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
	
page(_($help_context = "Other Income Total per Entries"), false, false, "", $js);


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

    $rep = new FrontReport(_('Other Income Total per Entries'), "Other Income Total per Entries", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'OTHER INCOME REPORT (Total per Entries)', $format_bold);
	$rep->y ++;
	$rep->sheet->writeString($rep->y, 0, 'Date From: '.sql2date($date_after).' To: '.sql2date($date_before).'', $format_bold);
	$rep->y ++;
	$rep->y ++;
	
	$rep->sheet->setMerge(0,0,0,8);
	$rep->sheet->setMerge(1,0,1,4);
	$rep->sheet->setMerge(2,0,2,4);

	
	$rep->sheet->setColumn(0,0,3); //set column width
	$rep->sheet->setColumn(1,1,40);
	$rep->sheet->setColumn(2,2,20);
	$rep->sheet->setColumn(3,3,20);

//setColumn(from,to,size);
$x=0;

div_start('table_');
start_table($table_style2.' width=90%');
$th = array();

$from_head = array('','Entries', 'Debit','Credit');
$th = array_merge($from_head);
array_push($th);

	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, $header, $format_bold_title);
		$x++;
	}
	$rep->y++;

	$c = $k = 0;
										
//==========AMOUNT
$type=ST_BANKDEPOSIT;
$sql="SELECT gl.account, sum(gl.amount) as t_amount 
FROM ".TB_PREF."gl_trans as gl 
left join ".TB_PREF."other_income_payment_header as oih
on gl.type_no=oih.bd_reference where gl.type='$type'
";

	if (trim($_POST['trans_no']) == '')
	{
	$sql .= " AND gl.tran_date >= '".date2sql($_POST['start_date'])."'
			  AND gl.tran_date <= '".date2sql($_POST['end_date'])."'";
	}
	$sql .= " AND oih.bd_cleared='1' GROUP BY gl.account";
	
//display_error($sql);	
	
$res = db_query($sql);

while($row = db_fetch($res))
{
$c ++;
$x = 0;

$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
$x++;	

if ($row['account']=='1010'){
$type='1010';
$rep->sheet->writeString($rep->y, $x, get_gl_account_name($type), $format_left);
$x++;
$amount1=get_old_credit($_POST['start_date'],$_POST['end_date'],$type);
$rep->sheet->writeNumber($rep->y, $x, abs($amount1), $format_accounting);
$x++;
$amount2=get_new_debit($_POST['start_date'],$_POST['end_date'],$type);
$rep->sheet->writeNumber($rep->y, $x, abs($amount2), $format_accounting);
$x++;
}

if ($row['t_amount']<0 and $row['account']!='1010') {
$rep->sheet->writeString($rep->y, $x, html_entity_decode(get_gl_account_name($row['account'])), $format_left);
$x++;
$rep->sheet->writeString($rep->y, $x, '', $format_left);
$x++;	
$rep->sheet->writeNumber($rep->y, $x, abs($row['t_amount']), $format_accounting);
$x++;
$debit+=$row['t_amount'];
}

if ($row['t_amount']>0 and $row['account']!='1010') {
	
$rep->sheet->writeString($rep->y, $x, html_entity_decode(get_gl_account_name($row['account'])), $format_left);
$x++;
			
		if ($row['account']=='1020011' and $amount1!=0){
			$bank_account_code='1020011';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh1=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d1=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c1=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$rep->sheet->writeNumber($rep->y, $x, $bank1=$t_coh1+abs($coh_d1)-abs($coh_c1), $format_accounting);
			$x++;
		}
		else if ($row['account']=='1020010' and $amount1!=0){
			$bank_account_code='1020010';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh2=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d2=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c2=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$rep->sheet->writeNumber($rep->y, $x, $bank2=$t_coh2+abs($coh_d2)-abs($coh_c2), $format_accounting);
			$x++;
		}
		else if ($row['account']=='1020021' and $amount1!=0){
			$bank_account_code='1020021';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh3=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d3=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c3=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$rep->sheet->writeNumber($rep->y, $x, $bank3=$t_coh3+abs($coh_d3)-abs($coh_c3), $format_accounting);
			$x++;
		}
		else if ($row['account']=='1020030' and $amount1!=0){
			$bank_account_code='1020030';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh4=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d4=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c4=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$rep->sheet->writeNumber($rep->y, $x, $bank4=$t_coh4+abs($coh_d4)-abs($coh_c4), $format_accounting);
			$x++;

		}else if ($row['account']=='10102299' and $amount1!=0){
			$bank_account_code='10102299';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh5=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d5=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c5=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$rep->sheet->writeNumber($rep->y, $x, $bank5=$t_coh5+abs($coh_d5)-abs($coh_c5), $format_accounting);
			$x++;
			//display_error($t_coh5);
			//display_error($coh_d5);
			//display_error($coh_c5);
			//amount_cell($bank5=$t_coh5+abs($coh_d5)-abs($coh_c5),false);
		}
		else{
			$rep->sheet->writeNumber($rep->y, $x, abs($row['t_amount']), $format_accounting);
			$x++;
			$credit+=$row['t_amount'];
		}
		


$rep->sheet->writeString($rep->y, $x, '', $format_left);
$x++;	
// $credit+=$row['t_amount'];
}

$rep->y++;
}

//AMOUNT============
	$x=1;
	$rep->y++;
	$rep->sheet->writeString($rep->y, $x, 'TOTAL:', $format_bold_right);
	$x++;
	$rep->sheet->writeNumber($rep->y, $x, abs($credit)+$amount1+$bank1+$bank2+$bank3+$bank4+$bank5, $format_accounting);
	$x++;	
	$rep->sheet->writeNumber($rep->y, $x, abs($debit)+$amount2, $format_accounting);
	$x++;	
					
	$rep->End();
}

$delete_id = find_submit('delete_selected');

//====================================start heading=========================================
start_form();
//if (!isset($_POST['start_date']))
	//$_POST['start_date'] = '01/01/'.date('Y');

global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKDEPOSIT;

	div_start('pmt_header');

	start_outer_table("width=85% $table_style2"); // outer table

	table_section(1);
	
	if (!isset($_POST['PayType']))
	{
		if (isset($_GET['PayType']))
			$_POST['PayType'] = $_GET['PayType'];
		else
			$_POST['PayType'] = "";
	}
	if (!isset($_POST['person_id']))
	{
		if (isset($_GET['PayPerson']))
			$_POST['person_id'] = $_GET['PayPerson'];
		else
			$_POST['person_id'] = "";
	}
	if (isset($_POST['_PayType_update'])) {
		$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
	}

	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
		br();
    	//	text_row_ex($payment ?_("To the Order of:"):_("Name:"),'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, false, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, false, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, false, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;
    }
		table_section(2);

		payment_type_list_cell('Payment Type:','payment_type');
		ref_cells('Transaction #:', 'trans_no');
		date_cells('From :', 'start_date');
		date_cells('To :', 'end_date');
		br();
		submit_cells('search', 'Search');
	end_row();
	
end_outer_table(1); // outer table
div_end();
//====================================end of heading=========================================
br();



function get_bank_id($bank_account_code){
$sql="SELECT id FROM `0_bank_accounts` where account_code='$bank_account_code'";
$res = db_query($sql);
$row=db_fetch($res);
return $row['id'];
}


function get_all_cash_on_hand($start_date,$end_date,$type,$bank_account_code){

$bank_id=is_bank_account($bank_account_code);

$sql="SELECT gl.account, sum(gl.amount) as t_amount
FROM ".TB_PREF."gl_trans as gl left join ".TB_PREF."other_income_payment_header as oih
on gl.type_no=oih.bd_reference where gl.type='2' 
and gl.account='1010'
AND gl.tran_date >= '".date2sql($start_date)."' 
AND gl.tran_date <= '".date2sql($end_date)."' 
AND oih.bd_date_deposited>='".date2sql($start_date)."' 
AND oih.bd_cleared='1'
AND gl.amount>0
AND oih.bd_payment_to_bank='$bank_id'
GROUP BY gl.account";

//AND oih.bd_date_deposited<='".date2sql($end_date)."' 

display_error($sql);
$res = db_query($sql);
$row=db_fetch($res);
return $row['t_amount']+0;
}


function get_old_credit($start_date,$end_date,$type,$bank_account_code,$bank_id){
$sql="SELECT sum(bd_amount) as t_amount FROM `0_other_income_payment_header`
where bd_trans_date<= '".date2sql($end_date)."' 
and bd_date_deposited> '".date2sql($end_date)."'
AND bd_cleared='1'";
display_error($sql);
$res = db_query($sql);
$row=db_fetch($res);
return $row['t_amount']+0;
}


function get_new_debit($start_date,$end_date,$type,$bank_account_code,$bank_id){
	
$end_date2=add_months(begin_month($end_date),-1);
	
$sql="SELECT sum(bd_amount) as t_amount FROM `0_other_income_payment_header`
where bd_trans_date<= '".date2sql(end_month($end_date2))."'
and bd_date_deposited> '".date2sql(end_month($end_date2))."'

AND bd_cleared='1'";
display_error($sql);
$res = db_query($sql);
$row=db_fetch($res);
return $row['t_amount']+0;
}


function get_coh_credit($start_date,$end_date,$type,$bank_account_code,$bank_id){
$sql="SELECT sum(bd_amount) as t_amount FROM `0_other_income_payment_header`
where bd_trans_date>= '".date2sql($start_date)."' 
and bd_trans_date<= '".date2sql($end_date)."' 
and bd_date_deposited> '".date2sql($end_date)."'
AND bd_payment_to_bank='$bank_id' 
AND bd_cleared='1'";
//display_error($sql);
$res = db_query($sql);
$row=db_fetch($res);
return $row['t_amount']+0;
}

function get_coh_debit($start_date,$end_date,$type,$bank_account_code,$bank_id){
$end_date2=add_months(begin_month($end_date),-1);
$sql="SELECT sum(bd_amount) as t_amount FROM `0_other_income_payment_header`
where bd_trans_date<= '".date2sql(end_month($end_date2))."'
and bd_date_deposited> '".date2sql(end_month($end_date2))."'
and bd_date_deposited<= '".date2sql($end_date)."'
AND bd_payment_to_bank='$bank_id'
AND bd_cleared='1'";
//display_error($sql);
$res = db_query($sql);
$row=db_fetch($res);
return $row['t_amount']+0;
}
// function get_old_credit($start_date,$end_date,$type){
// $sql="SELECT gl.account, sum(gl.amount) as t_amount 
// FROM ".TB_PREF."gl_trans as gl 
// left join ".TB_PREF."other_income_payment_header as oih
// on gl.type_no=oih.bd_reference where gl.type='2' and gl.account='$type'";

// $sql .= " AND gl.tran_date >= '".date2sql($start_date)."' AND gl.tran_date <= '".date2sql($end_date)."'

// AND oih.bd_date_deposited>='".date2sql($end_date)."'
// ";
// $sql .= " AND oih.bd_cleared='1' GROUP BY gl.account";
// //display_error($sql);
// $res = db_query($sql);
// $row=db_fetch($res);
// return $row['t_amount'];
// }

// function get_new_debit($start_date,$end_date,$type){
// $sql="SELECT gl.account, sum(gl.amount) as t_amount 
// FROM ".TB_PREF."gl_trans as gl 
// left join ".TB_PREF."other_income_payment_header as oih
// on gl.type_no=oih.bd_reference where gl.type='2' and gl.account='$type'";

// //display_error($end_date);

// $end_date2=add_months(begin_month($end_date),-1);

// //display_error($end_date2);

// $sql .= " AND gl.tran_date >= '".date2sql(add_months($start_date,-1))."' AND gl.tran_date <= '".date2sql(end_month($end_date2))."'

// AND oih.bd_date_deposited>='".date2sql(end_month($end_date2))."'
// ";
// $sql .= " AND oih.bd_cleared='1' GROUP BY gl.account";
// //display_error($sql);
// $res = db_query($sql);
// $row=db_fetch($res);
// return $row['t_amount'];
// }
//====================================display table=========================================
if (isset($_POST['search'])) {
submit_center('dl_excel','Download as excel file');
br();

div_start('table_');
start_table($table_style2.' width=50%');
$th = array();

$from_head = array('Entries', 'Debit','Credit');
$th = array_merge($from_head);
array_push($th);
	 table_header($th);

$k = 0;

//==========AMOUNT===========================
$type=ST_BANKDEPOSIT;

$sql="SELECT gl.account, sum(gl.amount) as t_amount 
FROM ".TB_PREF."gl_trans as gl 
left join ".TB_PREF."other_income_payment_header as oih
on gl.type_no=oih.bd_reference where gl.type='$type'
";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " AND gl.tran_date >= '".date2sql($_POST['start_date'])."'
	AND gl.tran_date <= '".date2sql($_POST['end_date'])."'";
}
	$sql .= " AND oih.bd_cleared='1' GROUP BY gl.account";

display_error($sql);	

$res = db_query($sql);

while($row = db_fetch($res)){
start_row();
alt_table_row_color($k);

if ($row['account']=='1010'){
$type='1010';
label_cell(get_gl_account_name($type) ,'nowrap');

$amount1=get_old_credit($_POST['start_date'],$_POST['end_date'],$type);
amount_cell(abs($amount1),false);

$amount2=get_new_debit($_POST['start_date'],$_POST['end_date'],$type);
amount_cell(abs($amount2),false);
//display_error($amount1);
}

if ($row['t_amount']<0 and $row['account']!='1010') {
label_cell(get_gl_account_name($row['account']) ,'nowrap');
label_cell('' ,'nowrap');
amount_cell(abs($row['t_amount']),false);
$debit+=$row['t_amount'];
}

if ($row['t_amount']>0 and $row['account']!='1010') {
label_cell(get_gl_account_name($row['account']) ,'nowrap');
		if ($row['account']=='1020011'){
			$bank_account_code='1020011';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh1=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d1=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c1=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			// display_error($t_coh1);
			// display_error($coh_d1);
			// display_error($coh_c1);
			amount_cell($bank1=$t_coh1+abs($coh_d1)-abs($coh_c1),false);
		}
		else if ($row['account']=='1020010'){
			$bank_account_code='1020010';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh2=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d2=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c2=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			// display_error($t_coh2);
			// display_error($coh_d2);
			// display_error($coh_c2);
			amount_cell($bank2=$t_coh2+abs($coh_d2)-abs($coh_c2),false);
		}
		else if ($row['account']=='1020021'){
			$bank_account_code='1020021';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh3=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d3=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c3=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			// display_error($t_coh3);
			// display_error($coh_d3);
			// display_error($coh_c3);
			amount_cell($bank3=$t_coh3+abs($coh_d3)-abs($coh_c3),false);
		}
		else if ($row['account']=='1020030'){
			$bank_account_code='1020030';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh4=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d4=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c4=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			// display_error($t_coh4);
			// display_error($coh_d4);
			// display_error($coh_c4);
			amount_cell($bank4=$t_coh4+abs($coh_d4)-abs($coh_c4),false);
		}
		else if ($row['account']=='10102299'){
			$bank_account_code='10102299';
			$bank_id=get_bank_id($bank_account_code);
			$t_coh5=get_all_cash_on_hand($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_d5=get_coh_debit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			$coh_c5=get_coh_credit($_POST['start_date'],$_POST['end_date'],$type,$bank_account_code,$bank_id);
			//display_error($t_coh5);
			//display_error($coh_d5);
			//display_error($coh_c5);
			amount_cell($bank5=$t_coh5+abs($coh_d5)-abs($coh_c5),false);
		}
		
		else{
			amount_cell(abs($row['t_amount']),false);
			$credit+=$row['t_amount'];
		}
		
label_cell('' ,'nowrap');
}
end_row();
}
//AMOUNT====================================
start_row();
label_cell('<b><font color=#880000>TOTAL:</font></b>','align=right');
label_cell("<font color=#880000><b>".number_format2(abs($credit)+$amount1+$bank1+$bank2+$bank3+$bank4+$bank5,2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($debit)+$amount2,2)."<b></font>",'align=right');
end_row();
end_table();
}
div_end();
end_form();
end_page();
?>