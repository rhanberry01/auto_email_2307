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
	
page(_($help_context = "DM to DEDUCT"), false, false, "", $js);

// ===== Custom JS ===== //
echo '
<script type="text/javascript">
	$(document).ready(function() {
		$("#select_all").change(function() {
			$(".select_one").attr("checked", this.checked);
		});

		$(".select_one").change(function() {
			if ($(".select_one").length == $(".select_one:checked").length) {
				$("#select_all").attr("checked", "checked");
			}
			else {
				$("#select_all").removeAttr("checked");
			}
		});
	});
</script>
';
// ===== End - Custom ===== //

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

// start_table();
// get_petty_cash_user_list_cells('Created By:','created_by');
// yesno_list_cells('Replenished w/ CV :', 'yes_no', '', 'Yes', 'No');
// ref_cells('Sequence #:', 'trans_no');
// date_cells('From :', 'start_date');
// date_cells('To :', 'end_date');
// submit_cells('search', 'Search');
// end_table();
br();
display_heading("DM and APV Summary");	
br();	
//====================================end of heading=========================================

//====================================display table=========================================
//submit_center('dl_excel','Download as excel file');
br();

function get_pen_dm_2016($supp_id)
{
	$sql = "SELECT supplier_id, sum(abs(round(ov_amount,2))) as t_amount FROM `0_supp_trans`
	where type=53
	and cv_id=0
	and ov_amount!=0
	and tran_date>='2016-01-01' and tran_date<='2016-12-31'
	and supplier_id='$supp_id'
	GROUP BY supplier_id";
	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);
	$t_amount=$row['t_amount'];
	return $t_amount; 
}

function get_pen_apv($supp_id)
{
	$sql = "SELECT supplier_id, sum(abs(round(ov_amount,2))) as t_amount FROM `0_supp_trans`
	where type=20
	and cv_id=0
	and ov_amount!=0
	and tran_date>='2016-01-01'
	and supplier_id='$supp_id'
	GROUP BY supplier_id";
	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);
	$t_amount=$row['t_amount'];
	return $t_amount; 
}


function get_pen_dm_2017($supp_id)
{
	$sql = "SELECT supplier_id, sum(abs(round(ov_amount,2))) as t_amount FROM `0_supp_trans`
	where type=53
	and cv_id=0
	and ov_amount!=0
	and tran_date>='2017-01-01' and tran_date<='2017-12-31'
	and supplier_id='$supp_id'
	GROUP BY supplier_id";
	//display_error($sql);
	$result = db_query($sql);
	$row=db_fetch($result);
	$t_amount=$row['t_amount'];
	return $t_amount; 
}

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
	
	$cv_id = insert_cv($cv_no,$apv_cv_date,$payable_amount,PT_SUPPLIER,$apv_header['supplier_id'], $real_cv_trans, sql2date($apv_header['due_date']), $total_ewt_ex);
		
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
		case "srsgv":
	       $supplier_id='110';
	        break;
				case "srsmol":
       $supplier_id='110';
        break;
	    default:
	        echo "No supplier_id.";
	}

	$accounts_payable = 2000010; //accounts_payable
	$advances_to_supplier = 1440; // advances_to_supplier

	// ===== Get Total Amount of selected pcd_id ===== //
	$sql = "SELECT sum(gl.amount) as amount 
			FROM ".TB_PREF."gl_trans_temp as gl
			left join ".TB_PREF."petty_cash_details as pcd
			on pcd.pcd_id=gl.type_no
			where gl.type='59' 
			and gl.amount>0 and gl.tran_date>='$date_from' and gl.tran_date<='$date_to'
			and gl.posted='0' and pcd.pcd_w_cv='0'";

	if ($created_by!='') 
	{
		$sql .= " and pcd.pcd_prepared_by_id='".$created_by."'";
	}

	if (!empty($_POST['petty_cash'])) {
		$pcd_ids = implode(',', $_POST['petty_cash']);
		$sql .= " AND pcd.pcd_id IN (".$pcd_ids.")";
	}

	// $sql .= " group by account";

	$res = db_query($sql);
	$row = db_fetch_row($res);
	$total_amount = $row[0];
	// echo $total_amount; exit;

	// ===== End - Get Total Amount of selected pcd_id ===== //

	$invoice_no = add_supp_trans(ST_SUPPINVOICE, $supplier_id, $apv_cv_date, $apv_cv_date, $_POST['reference'], '', $total_amount, 0, 0, "", 0, 0, $apv_cv_date, 1, 0, 0);

	$cv_id = auto_create_cv($invoice_no, $apv_cv_date);

	$sql = "SELECT gl.tran_date as tran_date, gl.type as type, 
			gl.type_no as type_no, gl.account as account, sum(gl.amount) as amount 
			FROM ".TB_PREF."gl_trans_temp as gl
			left join ".TB_PREF."petty_cash_details as pcd
			on pcd.pcd_id=gl.type_no
			where gl.type='59' 
			and gl.amount>0 and gl.tran_date>='$date_from' and gl.tran_date<='$date_to'
			and gl.posted='0' and pcd.pcd_w_cv='0'";

	if ($created_by!='') 
	{
		$sql .= " and pcd.pcd_prepared_by_id='".$created_by."'";
	}

	if (!empty($_POST['petty_cash'])) {
		$pcd_ids = implode(',', $_POST['petty_cash']);
		$sql .= " AND pcd.pcd_id IN (".$pcd_ids.")";
	}

	$sql .= " group by account";

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

		add_supp_invoice_item(ST_SUPPINVOICE, $invoice_no, $stock_id+0, $description='', $account, $amount, $unit_tax+0, $quantity+0, $grn_item_id+0, $po_detail_item_id+0, $memo_="Petty Cash ".$d1." - ".$d2,$err_msg="", $i_uom='',  $multiplier=1);
		add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, $apv_cv_date, $account, 0, 0,$amount, $supplier_id, "", $rate);
	}

	add_gl_trans_supplier(ST_SUPPINVOICE, $invoice_no, $apv_cv_date, $accounts_payable, 0, 0,-$total_amount, $supplier_id, "", $rate);


	//update cv status from gl_trans_temp and petty_cash_details.
	$sql="SELECT * FROM 0_petty_cash_details where (pcd_date>='$date_from'  and pcd_date<='$date_to') and pcd_w_cv='0' and pcd_replenished='1' ";

	if ($created_by!='') 
	{
		$sql.=" and pcd_prepared_by_id='".$created_by."'";
	}

	if (!empty($_POST['petty_cash'])) {
		$pcd_ids = implode(',', $_POST['petty_cash']);
		$sql .= " AND pcd_id IN (".$pcd_ids.")";
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
	
if(isset($_POST['create_apv']))
{
	/*// Testing POST data
		echo "<pre>";
		print_r($_POST);
		echo "</pre>";

		echo $pcd_ids = implode(',', $_POST['petty_cash']);
		exit;
	// End Test*/
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
start_table($table_style2.' width=60%');
$th = array();

$from_head = array('<input type="checkbox" id="select_all">',"","Supplier","Total APV (Present)","2016 DM","2017 DM","STATUS");


 table_header($from_head);

$k = 0;

$sql="SELECT DISTINCT s.supplier_id as supp_id,supp_name FROM `0_supp_trans` as s
LEFT JOIN 0_suppliers as ss
ON s.supplier_id=ss.supplier_id
where type=53
and cv_id=0
and ov_amount!=0
and tran_date>='2016-01-01' and tran_date<='2017-12-31'
ORDER BY supp_name";
// display_error($sql);
$res = db_query($sql);

while($row = db_fetch($res))
{
	$c ++;
	alt_table_row_color($k);
		echo '<td align="center">
			<input type="checkbox" class="select_one" name="petty_cash[]" value="'.$details['trans_no'].'">
		</td>';
	label_cell($c,'align=right');
	label_cell($row['supp_name']);
	label_cell(number_format2($t_apv=get_pen_apv($row['supp_id'])));
	label_cell(number_format2($t_dm_2016=get_pen_dm_2016($row['supp_id'])));
	label_cell(number_format2($t_dm_2017=get_pen_dm_2017($row['supp_id'])));
	
	if($t_apv>$t_dm_2016+$t_dm_2017){
		label_cell('READY TO DEDUCT');
	}
	else{
		label_cell('');
	}
	
	end_row();
$ex_apv+=$t_apv;
$ex_dm_2016+=$t_dm_2016;
$ex_dm_2017+=$t_dm_2017;
}

start_row();

label_cell(''); // for checkbox 
label_cell(''); // for checkbox 
label_cell('<font color=#880000><b>'.'TOTAL:'.'</b></font>');
label_cell("<font color=#880000><b>".number_format2(abs($ex_apv),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($ex_dm_2016),2)."<b></font>",'align=right');
label_cell("<font color=#880000><b>".number_format2(abs($ex_dm_2017),2)."<b></font>",'align=right');
label_cell('');
//label_cell("<font color=#880000><b>".number_format2(abs($tax_total),2)."<b></font>",'align=right');
//print_r($sub_t[$gl['gl_used']]);
//label_cell("<font color=#880000><b>".number_format2(abs($t_total),2)."<b></font>",'align=right');
end_row();	
end_table();

div_end();
end_form();
end_page();
?>