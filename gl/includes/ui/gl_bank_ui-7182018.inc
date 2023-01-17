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
function display_dm_payment_header(&$order)
{
	global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	div_start('pmt_header');

	start_outer_table("width=80% $table_style2"); // outer table

	table_section(1,"30%");
	
	if ($_POST['payment_type']==1 or $_POST['payment_type']==2)
	{
    bank_accounts_list_row( $payment ? _("From:") : _("To:"), 'bank_account', null, true);
	}
	
	cash_check_list_row('Type:', 'payment_type', '', '', '',true,'');
   
   date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	
	table_section(2, "40%");

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
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('payable_list');
	}

	supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);

	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	table_section(3, "30%");
	text_row(_("AR #:"), 'or_no');
	text_row(_("Memo:"), 'memo_');
	end_outer_table(1); // outer table
	div_end();

	div_start('table_');

	if (list_updated('person_id') or isset($_POST['PayType'])) {
	$sql="SELECT st.*,gl.* FROM ".TB_PREF."supp_trans as st 
	LEFT JOIN 0_gl_trans as gl on st.trans_no=gl.type_no 
	where gl.type='53' and st.type='53' and gl.account='1440'
	and st.supplier_id='".$_POST['person_id']."'
	and st.cv_id='0' and st.ov_amount!=0
	";
	//display_error($sql);
	
	$dm_res=db_query($sql);
	
	$dm_count = db_num_rows($dm_res);
	display_error($dm_count);

		if ($dm_count>0) {
			display_error($dm_count);
			display_heading("Debit Memo (Advances to Supplier) List");
			display_error('OK');
			br();
			start_table($table_style2 .'width=75%');
			$th = array('','Trans#','Description','Reference','Supplier Ref','Other ref','Trans Date','Amount','');
			table_header($th);
			while($dm_row = db_fetch($dm_res))
			{
			$c ++;
			start_row();
			alt_table_row_color($k);
			label_cell($c,'align=right');
			label_cell(get_gl_view_str(53,$dm_row['trans_no'],$dm_row['trans_no']));
			label_cell($dm_row['memo_'],'nowrap');
			label_cell($dm_row['reference'],'nowrap');
			label_cell($dm_row['supp_reference'],'nowrap');
			label_cell($dm_row['special_reference'],'nowrap');
			label_cell(sql2date($dm_row['tran_date']),'nowrap');
			amount_cell(abs($dm_row['amount']));
			// $cv_num=get_cv_no($dm_row['cv_id']);
			// label_cell(cv_no_link($dm_row['cv_id'],$cv_num));
			
			$count=check_voided($dm_row['trans_no']);
			if ($count==0) {
			check_cells('',"selected_id".$dm_row['trans_no']);
			}
			else {
			label_cell("</font color='red'>Voided</font>");
			}
			hidden('supplier_id'.$dm_row['trans_no'],$dm_row['supplier_id']);
			hidden('ov_amount'.$dm_row['trans_no'],$dm_row['amount']);
			hidden('credit_account'.$dm_row['trans_no'],$dm_row['credit_account']);
			hidden('oi_credit_account'.$dm_row['trans_no'],$dm_row['oi_credit_account']);
			hidden('output_vat_account'.$dm_row['trans_no'],$dm_row['output_vat_account']);
			hidden('output_vat_percent'.$dm_row['trans_no'],$dm_row['output_vat_percent']);
			hidden('tran_date1'.$dm_row['trans_no'],$dm_row['tran_date']);
			hidden('account'.$dm_row['trans_no'],$dm_row['account']);
			hidden('counter'.$dm_row['trans_no'],$dm_row['counter']);		
			end_row();
			}
			end_table();
		}
		else {
			display_error('Selected Supplier has no Debit Memo.');
		}
}
	div_end();
	br(2);
}

function check_voided($trans_no)
{
	$sql = "SELECT memo_
				FROM 0_voided
				WHERE type = 53
				AND id = ".$trans_no;
				//echo $sql.'<p>';
	$query = db_query($sql);
	$count = db_num_rows($query);
	//echo $count.'<p>';
	return $count;
}

function display_cash_deposit_header(&$order)
{
	global $table_style2, $Ajax, $Refs,$db_connections;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	div_start('pmt_header');

	start_outer_table("width=80% $table_style2"); // outer table

	table_section(1,"30%");
	
	if ($_POST['payment_type']==1 or $_POST['payment_type']==2)
	{
    bank_accounts_list_row( $payment ? _("From:") : _("To:"), 'bank_account', null, true);
	}
	
	cash_check_list_row2('Type:', 'payment_type', '', '', '',true,'');
  
	table_section(2, "40%");

	if (!isset($_POST['PayType']))
	{
		if (isset($_GET['PayType']))
			$_POST['PayType'] = $_GET['PayType'];
		else
			$_POST['PayType'] = "";
	}

	if (isset($_POST['_PayType_update'])) {
		//$_POST['person_id'] = '';
		$Ajax->activate('pmt_header');
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('payable_list');
	}

	date_row(_("Sales Date:"), 'date_', '', true, 0, 0, 0, null, true);
	date_row(_("Date Deposit:"), 'date_deposit', '', true, 0, 0, 0, null, true);
	//text_row(_("Description:"), 'desc','',50);
	get_all_deposit_type_list_row('Deposit Type:','desc','',true);
	//supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);

	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	table_section(3, "30%");
	//text_row(_("Memo:"), 'memo_');
	textarea_row(_("Memo:"), 'memo_', null, 40,4);
	
				$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
						switch($myBranchCode){
							//1020011 for AUB
							//1020021 for METROBANk
				
						case 'srsn':
									$bank_account='1020011';
									break;
						case 'sri':
									$bank_account='1020021';
									break;
						case 'srsnav':
									$bank_account='1020021';
									break;
						case 'srst':
									$bank_account='1020011';
									break;
						case 'srsc':
									$bank_account='1020021';
									break;
						case 'srsant1':
									$bank_account='1020021';
									break;
						case 'srsant2':
									$bank_account='1020021';
									break;
						case 'srsm':
									$bank_account='1020021';
									break;
						case 'srsmr':
									$bank_account='1020021';
									break;
						case 'srsg':
									$bank_account='1020011';
									break;
						case 'srscain':
									$bank_account='1020011';
									break;
						case 'srsval':
									$bank_account='1020021';
									break;			
						case 'srspun':
									$bank_account='1020011';
									break;		
						case 'srspat':
									$bank_account='1020011';
									break;	
						case 'srsbsl':
									$bank_account='1020021';
									break;		
						case 'srscom':
									$bank_account='1020011';
									break;						
						case 'srsbgb':
									$bank_account='1020011';
									break;		
						case 'srsgv':
									$bank_account='1020011';
									break;											
					}
		hidden('bank_gl_account',$bank_account);
	
	end_outer_table(1); // outer table
	div_end();
}


function get_other_income_type_name($type_id)
{
	$sql = "SELECT oi_type_name FROM ".TB_PREF."other_income_type WHERE oi_id=".db_escape($type_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return $row[0];
}


function display_payment_header(&$order)
{
	global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	div_start('pmt_header');

	start_outer_table("width=80% $table_style2"); // outer table

	table_section(1,"30%");
	
	if ($_POST['payment_type']==1 or $_POST['payment_type']==2)
	{
    bank_accounts_list_row( $payment ? _("From:") : _("To:"), 'bank_account', null, true);
	}
	
	cash_check_list_row('Payment Type:', 'payment_type', '', '', '',true,'');
   
   date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	
	table_section(2, "40%");

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
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('payable_list');
	}

	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Payee:"),
				 'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, true, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;
    }

	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	table_section(3, "30%");
	if ($_POST['payment_type']!=2) {
	get_other_income_type_list_row('Type:','rec_type','',true);
	amount_row('Gross Amount:','gross_amount');
}	
	receipt_list_row('Receipt Released:', 'receipt_type', '', '', '',false,'');
	text_row(_("Receipt#:"), 'or_no');
	text_row(_("Memo:"), 'memo_');
	end_outer_table(1); // outer table
	div_end();
}
######3333
function display_payment_header_nova_upd(&$order)
{
	global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKPAYMENT;
	div_start('head');

	div_start('pmt_header');

	start_outer_table("width=80% $table_style2"); // outer table

	table_section(1,"30%");
	
	if ($_POST['payment_type']==1 or $_POST['payment_type']==2)
	{
    bank_accounts_list_row( $payment ? _("From:") : _("To:"), 'bank_account', null, true);
	}
	
	cash_check_list_row('Payment Type:', 'payment_type', '', '', '',true,'');
   
   date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	
	table_section(2, "40%");

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
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('payable_list');
	}
	
	
	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Payee:"),
				 'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, true, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;
    }

	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	table_section(3, "30%");
	if ($_POST['payment_type']!=2) {
	$res = get_other_income_type_list_row('Type:','rec_type','',true,true);

	if ($_POST['rec_type']!='') {
		global $Ajax;
		$sql = "SELECT oi_gl_debit  FROM 0_other_income_type where oi_id = ".$_POST['rec_type']."
				and oi_gl_debit IN( 4020010,
									4020020,
									4020025,
									2478,
									2473,
									2472,
									2471,
									4020051,
									2477,
									4020050,
									4020030,
									4020051,
									4020010,
									4020020,
									4020050,
									4020030,
									2483,
									2470)";
		$query = db_query($sql);
		//display_error($sql);
		$res = db_fetch_row($query);
		if($res){
		text_row(_("SAF#:"), 'saf_no');
		}else{
		}
		$Ajax->activate('head');  
	}

	
	amount_row('Gross Amount:','gross_amount');
}	
	receipt_list_row('Receipt Released:', 'receipt_type', '', '', '',false,'');
	text_row(_("Receipt#:"), 'or_no');
	text_row(_("Memo:"), 'memo_');
	yesno_list_row(_("Is Paid Online?:"), 'paid_online');
	end_outer_table(1); // outer table
	div_end();
	div_end();
}


######3333
function display_payment_header_nova(&$order)
{
	global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKPAYMENT;
	div_start('head');

	div_start('pmt_header');

	start_outer_table("width=80% $table_style2"); // outer table

	table_section(1,"30%");
	
	if ($_POST['payment_type']==1 or $_POST['payment_type']==2)
	{
    bank_accounts_list_row( $payment ? _("From:") : _("To:"), 'bank_account', null, true);
	}
	
	cash_check_list_row('Payment Type:', 'payment_type', '', '', '',true,'');
   
   date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	
	table_section(2, "40%");

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
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
		$Ajax->activate('payable_list');
	}
	
	
	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Payee:"),
				 'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, true, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;
    }

	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	table_section(3, "30%");
	if ($_POST['payment_type']!=2) {
	$res = get_other_income_type_list_row('Type:','rec_type','',true,true);
	
	amount_row('Gross Amount:','gross_amount');
}	
	receipt_list_row('Receipt Released:', 'receipt_type', '', '', '',false,'');
	text_row(_("Receipt#:"), 'or_no');
	text_row(_("Memo:"), 'memo_');
	end_outer_table(1); // outer table
	div_end();
	div_end();
}


function display_bank_header(&$order)
{
	global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	div_start('pmt_header');

	start_outer_table("width=90% $table_style2"); // outer table

	table_section(1);
	
	if ($_POST['payment_type']==1)
	{
    bank_accounts_list_row( $payment ? _("From:") : _("To:"), 'bank_account', null, true);
	}
	
	cash_check_list_cells('Payment Type:', 'payment_type', '', '', '',true,'');
   
   date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	
	table_section(2, "33%");

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
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
	}
    // payment_person_types_list_row( $payment ? _("Pay To:"):_("From:"),
		 // 'PayType', $_POST['PayType'], true);
	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),
		 'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Name:"),
				 'person_id', 40, 50);
    		break;
		//case PT_WORKORDER :
    	//	workorders_list_row(_("Work Order:"), 'person_id', null);
    	//	break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, true, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;

		//case PT_QUICKENTRY :
			//quick_entries_list_row(_("Type").":", 'person_id', null, ($payment ? QE_PAYMENT : QE_DEPOSIT), true);
			//$qid = get_quick_entry(get_post('person_id'));
			//if (list_updated('person_id')) {
			//	unset($_POST['totamount']); // enable default
				//$Ajax->activate('totamount');
		//	}
			//amount_row($qid['base_desc'].":", 'totamount', price_format($qid['base_amount']),
			//	 null, "&nbsp;&nbsp;".submit('go', _("Go"), false, false, true));
			//break;	
		//case payment_person_types::Project() :
    	//	dimensions_list_row(_("Dimension:"), 'person_id', $_POST['person_id'], false, null, true);
    	//	break;
    }

	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	//exchange_rate_display($bank_currency, $person_currency, $_POST['date_']);

	table_section(3, "33%");

	if (isset($_GET['NewPayment']))
	
		$sqlid_details="select max(ot_id)+1 as ot_id from ".TB_PREF."other_income_header";
		
		$result_id_details=db_query($sqlid_details);
		
		while ($ot_id_row = db_fetch($result_id_details))
		{
		$trans_no=$ot_id_row['ot_id'];
		}	
		
	if (($trans_no=='') or ($trans_no=='0')) {
	$trans_no=$trans_no+1;
	}
    	ref_row(_("Reference:"), 'ref', '', $trans_no);
	end_outer_table(1); // outer table

	div_end();
}
//----------------------------------------------------------------------------


function create_other_income_header(&$order)
{
	global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	div_start('pmt_header');

	start_outer_table("width=90% $table_style2"); // outer table

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
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
	}

	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),
		 'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Name:"),
				 'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, true, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;

    }
	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	table_section(2, "33%");
	date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	
	table_section(3, "33%");

		text_row(_("Memo:"), 'memo_');
	end_outer_table(1); // outer table
	div_end();
}


function display_gl_items($title, &$order)
{
	global $table_style, $path_to_root;

	$dim = get_company_pref('use_dimension');
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	display_heading($title);

    div_start('items_table');
	start_table("$table_style colspan=7 width=95%");

	if ($dim == 2)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1",
			_("Dimension")." 2", _("Amount"), _("Memo"), "");
	else if ($dim == 1)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension"),
			_("Amount"), _("Memo"), "");
	else
		$th = array(_("Account Code"), _("Account Description"),
			_("Amount"), _("Memo"), "");

	if (count($order->gl_items)) $th[] = '';

	table_header($th);
	$k = 0;  //row colour counter

	$id = find_submit('Edit');
	foreach ($order->gl_items as $line => $item)
	{
		if ($id != $line)
		{
    		alt_table_row_color($k);

			label_cell($item->code_id);
			label_cell($item->description);
    		if ($dim >= 1)
				label_cell(get_dimension_string($item->dimension_id, true));
    		if ($dim > 1)
				label_cell(get_dimension_string($item->dimension2_id, true));
			//amount_cell(abs($item->amount));
			if ($order->trans_type == ST_BANKDEPOSIT)
				amount_cell(-$item->amount);
			else		
				amount_cell($item->amount);
			label_cell($item->reference);

			edit_button_cell("Edit$line", _("Edit"),
				_('Edit document line'));
			delete_button_cell("Delete$line", _("Delete"),
				_('Remove line from document'));
    		end_row();
		}
		else
		{
			gl_edit_item_controls($order, $dim, $line);
		}
	}

	if ($id == -1)
		gl_edit_item_controls($order, $dim);

	if ($order->count_gl_items())
		label_row(_("Total"), number_format2(abs($order->gl_items_total()), user_price_dec()),"colspan=" . $colspan . " align=right", "align=right",3);
    end_table();
	div_end();
}

//---------------------------------------------------------------------------------

function display_other_income_items($title, &$order)
{
	global $table_style, $path_to_root;

	$dim = get_company_pref('use_dimension');
	$colspan = ($dim == 2 ? 4 : ($dim == 1 ? 3 : 2));
	display_heading($title);

    div_start('items_table');
	start_table("$table_style colspan=7 width=95%");

	if ($dim == 2)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension")." 1",
			_("Dimension")." 2", _("Amount"), _("Memo"), "");
	else if ($dim == 1)
		$th = array(_("Account Code"), _("Account Description"), _("Dimension"),
			_("Amount"), _("Memo"), "");
	else
		$th = array(_("Account Code"), _("Account Description"),
			_("Amount"), _("Memo"), "");

	if (count($order->gl_items)) $th[] = '';

	table_header($th);
	$k = 0;  //row colour counter

	$id = find_submit('Edit');
	foreach ($order->gl_items as $line => $item)
	{
		if ($id != $line)
		{
    		alt_table_row_color($k);

			label_cell($item->code_id);
			label_cell($item->description);
    		if ($dim >= 1)
				label_cell(get_dimension_string($item->dimension_id, true));
    		if ($dim > 1)
				label_cell(get_dimension_string($item->dimension2_id, true));
			//amount_cell(abs($item->amount));
			if ($order->trans_type == ST_BANKDEPOSIT)
				amount_cell(-$item->amount);
			else		
				amount_cell($item->amount);
			label_cell($item->reference);

			edit_button_cell("Edit$line", _("Edit"),
				_('Edit document line'));
			delete_button_cell("Delete$line", _("Delete"),
				_('Remove line from document'));
    		end_row();
		}
		else
		{
			gl_edit_item_controls($order, $dim, $line);
		}
	}

	if ($id == -1)
		gl_edit_item_controls($order, $dim);

	if ($order->count_gl_items())
	{
	// if($item->code_id='1400')
	// {
	// $wt=$item->amount;
	// }
	
	// if($item->code_id='2310')
	// {
	// $ot=$item->amount;
	// }
		label_row(_("Total"), number_format2(abs($order->gl_items_total()-$ot-$wt), user_price_dec()),"colspan=" . $colspan . " align=right", "align=right",3);
}
    end_table();
	div_end();
}

//---------------------------------------------------------------------------------

function gl_edit_item_controls(&$order, $dim, $Index=null)
{
	global $Ajax;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	start_row();
	$id = find_submit('Edit');
	if ($Index != -1 && $Index == $id)
	{
		$item = $order->gl_items[$Index];
		$_POST['code_id'] = $item->code_id;
		$_POST['dimension_id'] = $item->dimension_id;
		$_POST['dimension2_id'] = $item->dimension2_id;
		$_POST['amount'] = price_format(abs($item->amount));
		$_POST['description'] = $item->description;
		$_POST['LineMemo'] = $item->reference;

		hidden('Index', $id);
		echo gl_all_accounts_list('code_id', null, true, true);
		if ($dim >= 1)
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1)
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	    $Ajax->activate('items_table');
	}
	else
	{
		$_POST['amount'] = price_format(0);
		$_POST['dimension_id'] = 0;
		$_POST['dimension2_id'] = 0;
		$_POST['LineMemo'] = "";
		if(isset($_POST['_code_id_update'])) {
			    $Ajax->activate('code_id');
		}

		// if ($_POST['PayType'] == PT_CUSTOMER)
		// {
			// $acc = get_branch_accounts($_POST['PersonDetailID']);
			// $_POST['code_id'] = $acc['receivables_account'];
		// }
		elseif ($_POST['PayType'] == PT_SUPPLIER)
		{
			$acc = get_supplier_accounts($_POST['person_id']);
			$_POST['code_id'] = $acc['payable_account'];
		}
		//elseif ($_POST['PayType'] == PT_WORKORDER)
		//	$_POST['code_id'] = get_company_pref('default_assembly_act');
		else {
			$_POST['code_id'] =
				get_company_pref($payment ? 'default_cogs_act':'default_inv_sales_act');
		}
		echo gl_all_accounts_list('code_id', null, true, true);
		if ($dim >= 1)
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
		if ($dim > 1)
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
	}
	if ($dim < 1)
		hidden('dimension_id', 0);
	if ($dim < 2)
		hidden('dimension2_id', 0);
$keyups="onkeyup=\"if(event.keyCode==13) {try{this.value=get_amount(this.name);document.getElementById('AddItem').click();}catch(h){document.forms[0].UpdateItem.click();}}\"";

	amount_cells(null, 'amount',null,null,null,null,'amount',$keyups);
	text_cells(null, 'LineMemo', $_POST['LineMemo'], 35, 255,false,'','',$keyups);

	if ($id != -1)
	{
		button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
		button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
 		set_focus('amount');
	}
	else
	{
		submit_cells('AddItem', _("Add Item"), "colspan=2",
		    _('Add new item to document'), true);
	}

	end_row();
}
//---------------------------------------------------------------------------------
function gl_options_controls()
{
	echo "<br><table align='center'>";

	textarea_row(_("Memo"), 'memo_', null, 50, 3);

  	echo "</table>";
}

//---------------------------------------------------------------------------------
function other_income_rec_header(&$order)
{
	global $table_style2, $Ajax, $Refs;
	$payment = $order->trans_type == ST_BANKPAYMENT;

	div_start('pmt_header');

	start_outer_table("width=80% $table_style2"); // outer table

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
		$Ajax->activate('code_id');
		$Ajax->activate('pagehelp');
		$Ajax->activate('editors');
	}

	payment_person_types_list_row_( $payment ? _("Pay To:"):_("From:"),
		 'PayType', $_POST['PayType'], true);
    switch ($_POST['PayType'])
    {
		case PT_MISC :
    		text_row_ex($payment ?_("To the Order of:"):_("Payee:"),
				 'person_id', 40, 50);
    		break;
		case PT_SUPPLIER :
    		supplier_list_row(_("Supplier:"), 'person_id', null, false, true, false, true);
    		break;
		case PT_CUSTOMER :
    		 customer_list_row(_("Customer:"), 'person_id', null, false, true, false, true);

        	 if (db_customer_has_branches($_POST['person_id']))
        	 {
        		customer_branches_list_row(_("Branch:"), $_POST['person_id'], 
				 'PersonDetailID', null, false, true, true, true);
        	 }
        	 else
        	{
				$_POST['PersonDetailID'] = ANY_NUMERIC;
        	 hidden('PersonDetailID');
        	}
    		 break;

    }
	$person_currency = payment_person_currency($_POST['PayType'], $_POST['person_id']);
	$bank_currency = get_bank_account_currency($_POST['bank_account']);

	table_section(2, "33%");
	date_row(_("Date:"), 'date_', '', true, 0, 0, 0, null, true);
	get_other_income_type_list_row('Type:','rec_type','',true);
	amount_row('Gross Amount:','amount');	
	table_section(3, "33%");
	textarea_row(_("Memo:"), 'memo_', null, 28, 4);
	end_outer_table(1); // outer table
	div_end();
}
?>