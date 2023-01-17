<?php
// Test CVS

$page_security = 'SA_JOURNALENTRY';
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
// require_once $path_to_root . '/phpexcelreader/Excel/reader.php';
require_once $path_to_root . '/gl/includes/excel_reader2.php';


$js = '';

if ($use_popup_windows) {
	$js .= get_js_open_window(900, 500);
}

if ($use_date_picker) {
	$js .= get_js_date_picker();
}

page('Import Journal', false, false, "", $js);

//====== check if user can access this page
// if (get_user_column('gl_entries') == 0)
// {
	// display_error('You are not allowed to access this page');
	// display_footer_exit();
// }
//==============================

//=======================================
// function check_jo_number($jo_num){
	// $sql=db_query("SELECT * FROM ".TB_PREF."sales_orders WHERE concat(jo_type,'-',reference)=".db_escape($jo_num));

	// return db_num_rows($sql);
// }

function check_person($type='', $person_id='')
{
	if ($type == '' AND $person_id == '')
		return true;
	switch ($type)
	{
		case PT_MISC :
			return true;
		case PT_CUSTOMER :
			return get_customer_name($person_id) != '';
		case PT_SUPPLIER :
			return  get_supplier_name($person_id) != '';	
		case PT_EMPLOYEE :
			return get_employee_name($person_id) != '';
		default :
			return false;
	}
}

function convert_account_code($fa_account)
{
	$sql = "SELECT aria_account FROM zz_fa_aria WHERE fa_account = ". db_escape($fa_account);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}
//=======================================

if (isset($_POST['upload']))
{
	$mali = false;
	
	if (!is_date($_POST['tran_date']))
	{
		$mali = true;
		display_error('Invalid Date');
		set_focus('tran_date');
	}
	
	if ($mali)
		unset($_POST['upload']);
}

if (isset($_FILES['xls_file']) AND $_FILES['xls_file']['name'] != '' AND isset($_POST['upload'])) 
{
	
	$sql_date = date2sql($_POST['tran_date']);
	
	$date__ = explode_date_to_dmy($_POST['tran_date']);
	
	$result = $_FILES['xls_file']['error'];
	
 	$upload_file = 'Yes'; //Assume all is well to start off with
	$filename = $path_to_root . "/uploaded_journals";
	if (!file_exists($filename))
	{
		mkdir($filename);
	}	
	$filename_ = $_FILES['xls_file']['name'];
	$filename .= "/" . $filename_;
	
	 //But check for the worst 
	if (strtoupper(substr(trim($_FILES['xls_file']['name']), strlen($_FILES['xls_file']['name']) - 3)) != 'XLS')
	{
		display_error(_('Only xls_file files are supported - a file extension of .xls file is expected'));
		$upload_file ='No';
	} 
 
	if (file_exists($filename))
	{
		$result = unlink($filename);
		if (!$result) 
		{
			display_error(_('The existing file could not be removed'));
			display_error('Duplicate File');
			$upload_file ='No';
		}
	}
	
	if ($upload_file == 'Yes')
	{
		$result  =  move_uploaded_file($_FILES['xls_file']['tmp_name'], $filename);
	}
	
		
	if ($upload_file == 'No')
		display_footer_exit();

	// ExcelFile($filename, $encoding);
	$data = new Spreadsheet_Excel_Reader();


	// Set output Encoding.
	$data->setOutputEncoding('CP1251');


	$data->read($filename);

	error_reporting(E_ALL ^ E_NOTICE);

	begin_transaction();

	$debit = $credit = 0;
	
	//============================ add journal entry
	global $Refs;

	$date_ = $_POST['tran_date'];
	$ref   = $_POST['ref'];
	// $memo_ = $_POST['memo'];
	$memo_ = $_POST['memo_'];
	$trans_type = ST_JOURNAL;

    // $trans_id = get_next_trans_no($trans_type);
		
	start_table($table_style2);
	
	for ($i = 1; $i <= $data->sheets[0]['numRows']; $i++) 
	{
		if ($i == 1)
		{
			$th = array(	_('Account Code'), _('Account name'), /*'DR/CR',*/ 'Debit', 'Credit');
			table_header($th);
			continue;
			
		}
		
		if ($data->sheets[0]['cells'][$i][1] == '')
			continue;
			
		start_row();
		
		$account_code = $data->sheets[0]['cells'][$i][1];
		
		$account_code = convert_account_code($account_code);
	
		if (!$account_code)
		{
			if (user_numeric($data->sheets[0]['cells'][$i][3]) + user_numeric($data->sheets[0]['cells'][$i][4]) != 0)
			{
					echo $data->sheets[0]['cells'][$i][1];
			}
			continue;
		}
	
		$gl_name = get_gl_account_name($account_code, true);
		
		if (!$gl_name)
		{
			// display_error("GL Code is not yet added to the database. (". $data->sheets[0]['cells'][$i][1] . ")");
			// display_error("File upload failed. Try Again after adding the GL code,");
			// chmod($filename, 0755);
			// $result__ = unlink($filename);
			// if (!$result__) 
				// display_error(_('The existing file could not be removed'));
			// display_footer_exit();
			
			continue;
		}
		
		$debit_amt = user_numeric($data->sheets[0]['cells'][$i][3]);
		$credit_amt = user_numeric($data->sheets[0]['cells'][$i][4]);
		
		if ($debit_amt == 0 AND $credit_amt ==0)
			continue;
		
		label_cell($account_code);
		label_cell($gl_name);
		label_cell(number_format2($debit_amt,2) > 0 ? number_format2($debit_amt,2) : '');
		label_cell(number_format2($credit_amt,2) > 0 ? number_format2($credit_amt,2) : '');
		
		// $dim = $data->sheets[0]['cells'][$i][4];
		// $line_memo = $data->sheets[0]['cells'][$i][2];
		$line_memo = '';
	
		if ($debit_amt > 0) //debit
		{
			$amt = $debit_amt;
			$debit += $amt;
		}
		
		if ($credit_amt > 0) //credit
		{
			$amt = -$credit_amt;
			$credit += -$amt;
		}
		
		$amt = user_numeric($amt);

		// $is_bank_to = is_bank_account($account_code);

		// add_gl_trans($trans_type, $trans_id, $date_, $account_code, 0, 0, $memo_, $amt); //-----Updated variable name for memo, added underscore
		
    	// if ($is_bank_to)
    	// {
			// add_bank_trans($trans_type, $trans_id, $is_bank_to, $ref, $date_, $amt, 0, '');
    	// } 

		end_row();
			
	}
	
	start_row();
	label_cell('<b>TOTALS :<b>', 'align=right colspan=2');
	amount_cell($debit,true);
	amount_cell($credit,true);	
	end_row();
	end_table();
	
	// if($memo_ != ''){
		// add_comments($trans_type, $trans_id, $date_, $memo_);
	// }
	
	// $Refs->save($trans_type, $trans_id, $ref);
	
	// add_audit_trail($trans_type, $trans_id, $date_);

	// if (round($debit,2) == round($credit,2))
	// {	
		// commit_transaction();
		// display_notification('upload successful');
	// }
	// else
	// {
		// cancel_transaction();
		// display_error('upload failed');
	// }
		
}
else if (isset($_POST['upload']) AND $_FILES['xls_file']['name'] == '')
{
	display_error('No file selected');
}

if (!isset($upload_file) OR $upload_file != 'Yes')
{
	global $Refs;
	
	if (!isset($_POST['tran_date']))
		$_POST['tran_date'] = '01/01/2015';
	
	$_POST['ref'] = $Refs->get_next(0);
	start_form(true);
	start_table();
	start_row();
	ref_row(_("Reference:"), 'ref', '');
	date_cells('Journal Date :', 'tran_date');
	end_row();
	start_row();
	label_cell( "<b>Excel File (.xls) only	</b>:");
	label_cell("<input type='file' id='xls_file' name='xls_file'>");
	end_row();
	// payment_person_types_list_cells('payment type','ptype');
	end_table();
	
	echo "<br><table align='center'>";
	textarea_row(_("Memo"), 'memo_', null, 50, 3);
	echo "</table><br>";
	
	// $tth = array('A<br>Account Code', 'B<br>Account Description', 'C<br>JO #', 'D<br>Cost Center ID', 'E<br>Cost Center',	
						// 'F<br>Dr/Cr',	'G<br>DR Amount', 'H<br>CR Amount', 'I<br>&nbsp;','J<br>Person Type ID', 'K<br>Person ID');
	
	$tth = array('A<br>Account Code', 'B<br>Account Name', 'C<br>DR Amount', 'D<br>CR Amount');
	
	start_table('border=1');
		display_heading('Template');
		table_header($tth);
	end_table(2);
	
	submit_center('upload','Upload File');
	end_form();
}
end_page();

?>
