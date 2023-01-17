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
	
page(_($help_context = "Other Income Breakdown"), false, false, "", $js);



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

    $rep = new FrontReport(_('Other Income Breakdown'), "Other Income Breakdown", "LETTER");
	
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
	$rep->sheet->writeString($rep->y, 0, 'OTHER INCOME REPORT (Breakdown)', $format_bold);
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
	
	
$x=0;
div_start('table_');
start_table($table_style2.' width=90%');
$th = array();

$sql="SELECT bd_det_gl_code  FROM ".TB_PREF."other_income_payment_header as oih
LEFT JOIN ".TB_PREF."other_income_payment_details as oid
ON oih.bd_trans_no=oid.bd_det_trans_no
LEFT JOIN ".TB_PREF."bank_accounts as ba
ON  oih.bd_payment_to_bank=ba.id";


if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}

if ($_POST['payment_type']!= '')
{
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

$sql .= " AND bd_cleared='1' GROUP BY bd_det_gl_code ORDER BY  bd_trans_date,bd_payment_type,bd_payee, bd_det_gl_code";
	
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);
while($row = db_fetch($res))
{
$gls[]=get_gl_account_name($row['bd_det_gl_code']);
$gl_types[$row['bd_det_gl_code']] ['gl_used']=$row['bd_det_gl_code'];
}

$sql="SELECT bank_name FROM ".TB_PREF."other_income_payment_header as oih
LEFT JOIN ".TB_PREF."other_income_payment_details as oid
ON oih.bd_trans_no=oid.bd_det_trans_no
LEFT JOIN ".TB_PREF."bank_accounts as ba
ON  oih.bd_payment_to_bank=ba.id";


if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}

if ($_POST['payment_type']!= '')
{
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

$sql .= " AND bd_cleared='1' GROUP BY bank_name ORDER BY  bd_trans_date,bd_payment_type,bd_payee, bd_det_gl_code";
	
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);
while($row = db_fetch($res))
{
$bnk[]=$row['bank_name'];
$bank_types[$row['bank_name']] ['bank_used']=$row['bank_name'];
}

$from_head = array('','Date Paid', 'Trans #','RF/OR/SI #', 'Payee','Description','Type','Date Deposited','VAT','Wtax');
$th = array_merge($from_head,$bnk,$gls);

array_push($th);


	foreach($th as $header)
	{
		$rep->sheet->writeString($rep->y, $x, html_entity_decode($header), $format_bold_title);
		$x++;
	}
	$rep->y++;


	$c = $k = 0;
										

$sql="SELECT distinct oih.*, oid.*, ba.bank_name  FROM ".TB_PREF."other_income_payment_header as oih
LEFT JOIN ".TB_PREF."other_income_payment_details as oid
ON oih.bd_trans_no=oid.bd_det_trans_no
LEFT JOIN ".TB_PREF."bank_accounts as ba
ON  oih.bd_payment_to_bank=ba.id";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}

if ($_POST['payment_type']!= '')
{
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

	$sql .= " AND bd_cleared='1' ORDER BY bd_trans_date,bd_payment_type,bd_payee, bd_det_gl_code";
	
$res = db_query($sql);

$data=array();
while($row = db_fetch($res))
{
$data[$row['bd_trans_no']] ['or_num']=$row['bd_or'];
$data[$row['bd_trans_no']] ['payee']=$row['bd_payee'];
$data[$row['bd_trans_no']] ['date']=$row['bd_trans_date'];
$data[$row['bd_trans_no']] ['desc']=$row['bd_memo'];
$data[$row['bd_trans_no']] ['payment_type']=$row['bd_payment_type'];
$data[$row['bd_trans_no']] ['date_deposited']=$row['bd_date_deposited'];
$data[$row['bd_trans_no']] ['amount']=$row['bd_amount'];
$data[$row['bd_trans_no']] ['vat']=$row['bd_vat'];
$data[$row['bd_trans_no']] ['tax']=$row['bd_wt'];
$data[$row['bd_trans_no']][$row['bank_name']]=$row['bd_amount'];
$data[$row['bd_trans_no']][$row['bd_det_gl_code']]=$row['bd_amount'];
//display_error($row['bd_trans_no']);
//display_error($data);
}

foreach($data as $emp_id=>$details) {
		$c ++;
		$x = 0;


		$rep->sheet->writeNumber($rep->y, $x, $c, $format_center);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($details['date']),$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $emp_id,$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $details['or_num'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $details['payee'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $details['desc'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, $details['payment_type'],$format_left);
		$x++;
		$rep->sheet->writeString($rep->y, $x, sql2date($details['date_deposited']),$format_left);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $details['vat'], $format_accounting);
		$x++;
		$rep->sheet->writeNumber($rep->y, $x, $details['tax'], $format_accounting);
		$x++;	
		
foreach($bank_types as $bk) 
{
$rep->sheet->writeNumber($rep->y, $x, $details[$bk['bank_used']], $format_accounting);
$x++;
$per_line_total2[$bk['bank_used']]+=$details[$bk['bank_used']];
}	

foreach($gl_types as $gl) 
{
$rep->sheet->writeNumber($rep->y, $x, $details[$gl['gl_used']], $format_accounting);
$x++;
$per_line_total[$gl['gl_used']]+=$details[$gl['gl_used']];
}

$t_vat+=$details['vat'];
$t_wt+=$details['tax'];

$rep->y++;
}

	$x=1;
	$rep->y++;
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
	$rep->sheet->writeNumber($rep->y, $x, abs($t_vat), $format_accounting);
	$x++;	
	$rep->sheet->writeNumber($rep->y, $x, abs($t_wt), $format_accounting);
	$x++;	

	
	foreach($per_line_total2 as $sub_t) 
{
	$rep->sheet->writeNumber($rep->y, $x, abs($sub_t), $format_accounting);
	$x++;	

}	
	
foreach($per_line_total as $sub_t) 
{
	$rep->sheet->writeNumber($rep->y, $x, abs($sub_t), $format_accounting);
	$x++;	

}							
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

//====================================display table=========================================
if (isset($_POST['search'])) {

submit_center('dl_excel','Download as excel file');
br();

div_start('table_');
start_table($table_style2.' width=90%');
$th = array();

$sql="SELECT bd_det_gl_code  FROM ".TB_PREF."other_income_payment_header as oih
LEFT JOIN ".TB_PREF."other_income_payment_details as oid
ON oih.bd_trans_no=oid.bd_det_trans_no
LEFT JOIN ".TB_PREF."bank_accounts as ba
ON  oih.bd_payment_to_bank=ba.id";


if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}

if ($_POST['payment_type']!= '')
{
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

$sql .= " AND bd_cleared='1' GROUP BY bd_det_gl_code ORDER BY  bd_trans_date,bd_payment_type,bd_payee, bd_det_gl_code";
	
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);
while($row = db_fetch($res))
{
$gls[]=get_gl_account_name($row['bd_det_gl_code']);
$gl_types[$row['bd_det_gl_code']] ['gl_used']=$row['bd_det_gl_code'];
}

$sql="SELECT bank_name FROM ".TB_PREF."other_income_payment_header as oih
LEFT JOIN ".TB_PREF."other_income_payment_details as oid
ON oih.bd_trans_no=oid.bd_det_trans_no
LEFT JOIN ".TB_PREF."bank_accounts as ba
ON  oih.bd_payment_to_bank=ba.id";


if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}

if ($_POST['payment_type']!= '')
{
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

$sql .= " AND bd_cleared='1' GROUP BY bank_name ORDER BY  bd_trans_date,bd_payment_type,bd_payee, bd_det_gl_code";
	
//display_error($sql);
$res = db_query($sql);
$no_of_gl=db_num_rows($res);
while($row = db_fetch($res))
{
$bnk[]=$row['bank_name'];
$bank_types[$row['bank_name']] ['bank_used']=$row['bank_name'];
}


$from_head = array('Date Paid', 'Trans #','RF/OR/SI #', 'Payee','Description','Type','Date Deposited','VAT','Wtax');
$th = array_merge($from_head,$bnk,$gls);
array_push($th);

// if (db_num_rows($res) > 0)
	 table_header($th);
// else
// {
	// display_heading('No result found');
	// display_footer_exit();
// }

$k = 0;

$sql="SELECT distinct oih.*, oid.*, ba.bank_name  FROM ".TB_PREF."other_income_payment_header as oih
LEFT JOIN ".TB_PREF."other_income_payment_details as oid
ON oih.bd_trans_no=oid.bd_det_trans_no
LEFT JOIN ".TB_PREF."bank_accounts as ba
ON  oih.bd_payment_to_bank=ba.id";

if (trim($_POST['trans_no']) == '')
{
	$sql .= " WHERE bd_trans_date >= '".date2sql($_POST['start_date'])."'
			  AND bd_trans_date <= '".date2sql($_POST['end_date'])."'";
}
else
{
	$sql .= " WHERE (bd_trans_no LIKE ".db_escape('%'.$_POST['trans_no'].'%')." )";
}

if ($_POST['person_id']!= '' and $_POST['search'])
{
	$sql .= " AND bd_payee_id='".$_POST['person_id']."'";
}

if ($_POST['payment_type']!= '')
{
	$sql .= " AND bd_payment_type_id='".$_POST['payment_type']."'";
}

	$sql .= " AND bd_cleared='1' ORDER BY bd_trans_date,bd_payment_type,bd_payee, bd_det_gl_code";
	
//display_error($sql);	
	
$res = db_query($sql);

$data=array();
while($row = db_fetch($res))
{
$data[$row['bd_trans_no']] ['or_num']=$row['bd_or'];
$data[$row['bd_trans_no']] ['payee']=$row['bd_payee'];
$data[$row['bd_trans_no']] ['date']=$row['bd_trans_date'];
$data[$row['bd_trans_no']] ['desc']=$row['bd_memo'];
$data[$row['bd_trans_no']] ['payment_type']=$row['bd_payment_type'];
$data[$row['bd_trans_no']] ['date_deposited']=$row['bd_date_deposited'];
$data[$row['bd_trans_no']] ['vat']=$row['bd_vat'];
$data[$row['bd_trans_no']] ['tax']=$row['bd_wt'];
$data[$row['bd_trans_no']][$row['bank_name']]=$row['bd_amount'];
$data[$row['bd_trans_no']][$row['bd_det_gl_code']]=$row['bd_oi'];

//display_error($row['bd_trans_no']);
//display_error($data);
}

//print_r($gl_types);
//br();


foreach($data as $emp_id=>$details) {
//print_r($details);
//br();
alt_table_row_color($k);
label_cell(sql2date($details['date']));
label_cell(get_gl_view_str(ST_BANKDEPOSIT, $emp_id, $emp_id));
label_cell($details['or_num'] ,'nowrap');
label_cell($details['payee'] ,'nowrap');
label_cell($details['desc'] ,'nowrap');
label_cell($details['payment_type']);
label_cell(sql2date($details['date_deposited']));

$act = get_bank_account($details['bank']);
amount_cell($details['vat'],false);
amount_cell($details['tax'],false);

foreach($bank_types as $bk) 
{
amount_cell($details[$bk['bank_used']],false);
$per_line_total2[$bk['bank_used']]+=$details[$bk['bank_used']];
}

foreach($gl_types as $gl) 
{
amount_cell($details[$gl['gl_used']],false);
$per_line_total[$gl['gl_used']]+=$details[$gl['gl_used']];
}

$t_vat+=$details['vat'];
$t_wt+=$details['tax'];
//print_r($per_line_total);
//br();
//amount_cell($details['amount'],false);
}


if ($gl_types!='' or $bank_types!='') {
		start_row();
		label_cell('');
		label_cell('');
		label_cell('');
		label_cell('');
		label_cell('');
		label_cell('');
		label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
		label_cell("<font color=#880000><b>".number_format2(abs($t_vat),2)."<b></font>",'align=right');
		label_cell("<font color=#880000><b>".number_format2(abs($t_wt),2)."<b></font>",'align=right');

		foreach($per_line_total2 as $sub_t) 
		{
		label_cell("<font color=#880000><b>".number_format2(abs($sub_t),2)."<b></font>",'align=right');
		}
		foreach($per_line_total as $sub_t) 
		{
		label_cell("<font color=#880000><b>".number_format2(abs($sub_t),2)."<b></font>",'align=right');
		}
		//print_r($sub_t[$gl['gl_used']]);
		//label_cell("<font color=#880000><b>".number_format2(abs($t_total),2)."<b></font>",'align=right');
}
else {
display_error('NO RESULT FOUND.');
}
end_row();	
end_table();
}
div_end();
end_form();
end_page();
?>