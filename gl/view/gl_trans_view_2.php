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
$page_security = 'SA_GLTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");

page(_($help_context = "General Ledger Transaction Details"), true);

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/purchasing/includes/db/sdma_db.inc");

if($_GET['branch']!=''){
$connect_to = $_GET['branch'];
set_global_connection_branch($connect_to);
}


if (!isset($_GET['type_id']) || !isset($_GET['trans_no'])) 
{ /*Script was not passed the correct parameters */

	echo "<p>" . _("The script must be called with a valid transaction type and transaction number to review the general ledger postings for.") . "</p>";
	exit;
}

if ($_GET['branch']!=''){
set_global_connection_branch($_GET['branch']);
}

function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM transfers.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

function trade_non_trade_inv_by_branch($type, $trans_no, $branch)
{
	if ($type != 20)
		return '';
	
	$sql = "SELECT reference FROM ".$branch['aria_db'].".".TB_PREF."supp_trans WHERE type = $type AND trans_no = $trans_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if (strpos($row[0],'NT') === false)
		return ' (Trade)'; //trade;
		
	return ' (Non-Trade)'; //non-trade
	
}

function payment_person_name_by_branch($type, $person_id, $branch, $full=false) {
	global $payment_person_types;

	switch ($type)
	{
		case PT_MISC :
			return $person_id;
		case PT_QUICKENTRY :
			$qe = get_quick_entry_by_branch($person_id, $branch);
			return ($full ? $payment_person_types[$type] . ": ":"") . $qe["description"];
		case PT_WORKORDER :
			global $wo_cost_types;
			return $wo_cost_types[$person_id];
		case PT_CUSTOMER :
			return ($full ?$payment_person_types[$type] . ": ":"") . get_customer_name_by_branch($person_id, $branch);
		case PT_SUPPLIER :
			return ($full ? $payment_person_types[$type] . ": ":"") . get_supplier_name_by_branch($person_id, $branch);
		default :
			return '';
	}
}

function get_quick_entry_by_branch($selected_id, $branch) {
	$sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."quick_entries WHERE id=".db_escape($selected_id);

	$result = db_query($sql, "could not retrieve quick entry $selected_id");

	return db_fetch($result);
}

function get_customer_name_by_branch($customer_id, $branch) {
	$sql = "SELECT name FROM ".$branch['aria_db'].".".TB_PREF."debtors_master WHERE debtor_no=".db_escape($customer_id);

	$result = db_query($sql, "could not get customer");

	$row = db_fetch_row($result);

	return $row[0];
}	

function get_supplier_name_by_branch($supplier_id, $branch)
{
	$sql = "SELECT supp_name AS name FROM ".$branch['aria_db'].".".TB_PREF."suppliers WHERE supplier_id=".db_escape($supplier_id);

	$result = db_query($sql, "could not get supplier");

	$row = db_fetch_row($result);

	return html_entity_decode($row[0]);
}

function comments_display_row_by_branch($type, $id, $branch)
{
	$comments = get_comments_by_branch($type, $id, $branch);
	if ($comments and db_num_rows($comments))
	{
		echo "<tr><td colspan=15>";
    	while ($comment = db_fetch($comments))
    	{
    		echo $comment["memo_"] . "<br>";
    	}
		echo "</td></tr>";
	}
}

function get_comments_by_branch($type, $type_no, $branch)
{
	$sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."comments WHERE type="
		.db_escape($type)." AND id=".db_escape($type_no);

	return db_query($sql, "could not query comments transaction table");
}

function get_dimension_by_branch($id, $allow_null=false, $branch)
{
    $sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."dimensions	WHERE id=".db_escape($id);

	$result = db_query($sql, "The dimension could not be retrieved");

	if (!$allow_null && db_num_rows($result) == 0)
		display_db_error("Could not find dimension $id", $sql);

	return db_fetch($result);
}

//--------------------------------------------------------------------------------------

function get_dimension_string_by_branch($id, $html=false, $branch, $space=' ')
{
	if ($id <= 0)
	{
		if ($html)
			$dim = "&nbsp;";
		else
			$dim = "";
	}
	else
	{
		$row = get_dimension_by_branch($id, true, $branch);
		$dim = $row['reference'] . $space . $row['name'];
	}

	return $dim;
}

function get_supp_trans_2_by_branch($trans_no, $trans_type=-1, $branch)
{
	$sql = "SELECT ".$branch['aria_db'].".".TB_PREF."supp_trans.*, ".$branch['aria_db'].".".TB_PREF."supp_trans.ov_amount+".$branch['aria_db'].".".TB_PREF."supp_trans.ov_gst+".$branch['aria_db'].".".TB_PREF."supp_trans.ov_discount+".$branch['aria_db'].".".TB_PREF."supp_trans.ewt AS Total,
		".$branch['aria_db'].".".TB_PREF."suppliers.supp_name AS supplier_name, 
		".$branch['aria_db'].".".TB_PREF."suppliers.gst_no, ".$branch['aria_db'].".".TB_PREF."suppliers.curr_code AS SupplierCurrCode, ".$branch['aria_db'].".".TB_PREF."supp_trans.ewt ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".$branch['aria_db'].".".TB_PREF."bank_accounts.bank_name, ".$branch['aria_db'].".".TB_PREF."bank_accounts.bank_account_name, ".$branch['aria_db'].".".TB_PREF."bank_accounts.bank_curr_code,
			".$branch['aria_db'].".".TB_PREF."bank_accounts.account_type AS BankTransType, SUM(".$branch['aria_db'].".".TB_PREF."bank_trans.amount) AS BankAmount,
			".$branch['aria_db'].".".TB_PREF."bank_trans.ref ";
	}

	$sql .= " FROM ".$branch['aria_db'].".".TB_PREF."supp_trans, ".$branch['aria_db'].".".TB_PREF."suppliers ";

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= ", ".$branch['aria_db'].".".TB_PREF."bank_trans, ".$branch['aria_db'].".".TB_PREF."bank_accounts";
	}

	$sql .= " WHERE ".$branch['aria_db'].".".TB_PREF."supp_trans.trans_no=".db_escape($trans_no)."
		AND ".$branch['aria_db'].".".TB_PREF."supp_trans.supplier_id=".$branch['aria_db'].".".TB_PREF."suppliers.supplier_id";

	if ($trans_type > 0)
		$sql .= " AND ".$branch['aria_db'].".".TB_PREF."supp_trans.type=".db_escape($trans_type);

	if ($trans_type == ST_SUPPAYMENT)
	{
		// it's a payment so also get the bank account
		$sql .= " AND ".$branch['aria_db'].".".TB_PREF."bank_trans.trans_no =".db_escape($trans_no)."
			AND ".$branch['aria_db'].".".TB_PREF."bank_trans.type=".db_escape($trans_type)."
			AND ".$branch['aria_db'].".".TB_PREF."bank_accounts.id=".$branch['aria_db'].".".TB_PREF."bank_trans.bank_act ";
	}

	$sql .= " GROUP BY trans_no,type";
	$result = db_query($sql, "Cannot retrieve a supplier transaction");

    if (db_num_rows($result) == 0)
    {
       // can't return nothing
       display_db_error("no supplier trans found for given params d", $sql, true);
       exit;
    }

    if (db_num_rows($result) > 1)
    {
       // can't return multiple
       display_db_error("duplicate supplier transactions found for given params", $sql, true);
       exit;
    }

    return db_fetch($result);
}

function get_username_by_id_by_branch($id, $branch) {
	$sql = "SELECT real_name FROM ".$branch['aria_db'].".".TB_PREF."users WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function get_sdma_by_ref_by_branch($ref, $branch) {
	$sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."sdma WHERE reference = ".db_escape($ref);
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function is_voided_display_by_branch($type, $id, $label, $branch) {
	global $table_style;
	$void_entry = get_voided_entry_by_branch($type, $id, $branch);

	if ($void_entry == null)
		return false;

	start_table("width=50% $table_style");
	echo "<tr><td align=center><font color=red>$label</font><br>";
	echo "<font color=red>" . _("Date Voided:") . " " . sql2date($void_entry["date_"]) . "</font><br>";
	if (strlen($void_entry["memo_"]) > 0)
		echo "<center><font color=red>" . _("Memo:") . " " . $void_entry["memo_"] . "</font></center><br>";
	echo "</td></tr>";
	end_table(1);

	return true;
}

function get_voided_entry_by_branch($type, $type_no, $branch) {
	$sql = "SELECT * FROM ".$branch['aria_db'].".".TB_PREF."voided WHERE type=".db_escape($type)
		." AND id=".db_escape($type_no);
	//display_error($sql);
	$result = db_query($sql, "could not query voided transaction table");

	return db_fetch($result);
	
}

function display_gl_heading($myrow)
{

	global $table_style, $systypes_array;

	switch ($_GET['type_id']) {
		case ST_SUPPINVOICE:
			$col1="Supplier";
			break;
		case ST_SALESINVOICE:
			$col1="Customer";
			break;
			
		case 52:
		case 53:
			$col1="Supplier";
			break;
		default:
			$col1="Person/Item";
			break;
	}

	$com = get_company_prefs();
	
	$b = get_branch_by_id($_GET['bid']);

	display_heading("SAN ROQUE SUPERMARKET RETAIL SYSTEMS INC. - " . $b['name']);
	display_heading2($b['address']);
	br(2);
	
	$supp_ref = '';
	$trans_name = $systypes_array[$_GET['type_id']]. trade_non_trade_inv_by_branch($_GET['type_id'], $_GET['trans_no'], $b); 

    start_table("$table_style width=95%");
    $th = array($col1,_("General Ledger Transaction Details"), _("Reference"),
    	_("Date"));
		
	if ($_GET['type_id'] == 52 OR $_GET['type_id'] == 53)
	{
		$th[] = 'Supplier Reference';
		$sql = "SELECT supp_reference, tran_date FROM ".$b['aria_db'].".".TB_PREF."supp_trans 
					WHERE type = ".$_GET['type_id']."
					AND trans_no = ".$_GET['trans_no'];
		$res = db_query($sql);
		$row = db_fetch($res);
		
		$supp_ref = $row[0];
		$tran_date = $row[1];
    }
	
	table_header($th);	
    start_row();	
    //label_cell("$trans_name #" . $_GET['trans_no']);
	if ($_GET['type_id'] != 0)
		label_cell(payment_person_name_by_branch($myrow["person_type_id"], $myrow["person_id"], $b));
	else
		label_cell('');
		
    label_cell("$trans_name");
    label_cell($myrow["reference"]);
	if ($_GET['type_id'] == 53)
		label_cell(date('F d Y', strtotime((isset($tran_date) ? sql2date($tran_date) : sql2date($myrow["tran_date"])))));
	else
		label_cell((isset($tran_date) ? sql2date($tran_date) : sql2date($myrow["tran_date"])));
	
	if ($_GET['type_id'] == 52 OR $_GET['type_id'] == 53)
		label_cell($supp_ref);
	
	end_row();

	if ($_GET['type_id'] != 52 AND $_GET['type_id'] != 53)
		comments_display_row_by_branch($_GET['type_id'], $_GET['trans_no'], $b);

    end_table(1);
}
$b = get_branch_by_id($_GET['bid']);

$sql = "SELECT gl.*, cm.account_name, IF(ISNULL(refs.reference), '', refs.reference) AS reference FROM ".$b['aria_db']."."
	.TB_PREF."gl_trans as gl
	LEFT JOIN ".$b['aria_db'].".".TB_PREF."chart_master as cm ON gl.account = cm.account_code
	LEFT JOIN ".$b['aria_db'].".".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)"
	." WHERE gl.type= ".db_escape($_GET['type_id']) 
	." AND gl.type_no = ".db_escape($_GET['trans_no'])
	." ORDER BY amount DESC, counter ASC";

$result = db_query($sql,"could not get transactions");
//alert("sql = ".$sql);
// display_error($sql);


if (db_num_rows($result) == 0)
{
	$sql = "SELECT gl.*, cm.account_name, IF(ISNULL(refs.reference), '', refs.reference) AS reference FROM ".$b['aria_db']."."
		.TB_PREF."gl_trans_temp as gl
		LEFT JOIN ".$b['aria_db'].".".TB_PREF."chart_master as cm ON gl.account = cm.account_code
		LEFT JOIN ".$b['aria_db'].".".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)"
		." WHERE gl.type= ".db_escape($_GET['type_id']) 
		." AND gl.type_no = ".db_escape($_GET['trans_no'])
		." ORDER BY amount DESC, counter ASC";
	$result = db_query($sql,"could not get transactions");
	//display_error($sql);
}
if (db_num_rows($result) == 0)
{
    echo "<p><center>" . _("No general ledger transactions have been created for") . " " .$systypes_array[$_GET['type_id']]." " . _("number") . " " . $_GET['trans_no'] . "</center></p><br><br>";
	end_page(true);
	exit;
}

/*show a table of the transactions returned by the sql */
$dim = get_company_pref('use_dimension');

if ($dim == 2)
	$th = array(_("Account Code"), _("Account Name"), _("Dimension")." 1", _("Dimension")." 2",
		_("Debit"), _("Credit"), _("Memo"));
else if ($dim == 1)
	$th = array(_("Account Code"), _("Account Name"), _("Dimension"),
		_("Debit"), _("Credit"), _("Memo"));
else		
	$th = array(_("Account Code"), _("Account Name"),
		_("Debit"), _("Credit"), _("Memo"));
$k = 0; //row colour counter
$heading_shown = false;

$iGL=array();

if($_GET['type_id']==ST_CUSTDELIVERY){
while ($myrow = db_fetch($result)) 
{

	$iGL[$myrow['account']]['amount']+=$myrow['amount'];
	$iGL[$myrow['account']]['account_name']=$myrow['account_name'];
	$iGL[$myrow['account']]['dimension_id']=$myrow['dimension_id'];
	$iGL[$myrow['account']]['dimension2_id']=$myrow['dimension2_id'];
	$iGL[$myrow['account']]['memo_']=$myrow['memo_'];
	$iGL[$myrow['account']]['myrow']=$myrow;
	

}

foreach($iGL as $key=>$value){
	if ($value['amount'] == 0) continue;	
	if (!$heading_shown)
	{
		display_gl_heading($value['myrow']);
		start_table("$table_style width=95%");
		table_header($th);
		$heading_shown = true;
	}	

	alt_table_row_color($k);
	
    label_cell($key);
	label_cell($value['account_name']);
	if ($dim >= 1)
		label_cell(get_dimension_string_by_branch($value['dimension_id'], true, $b));
	if ($dim > 1)
		label_cell(get_dimension_string_by_branch($value['dimension2_id'], true, $b));

	display_debit_or_credit_cells($value['amount']);
	label_cell($value['memo_']);
	end_row();
	

}}
else{
	while ($myrow = db_fetch($result)) 
	{
	if ($myrow['amount'] == 0) continue;
	if (!$heading_shown)
	{
		display_gl_heading($myrow);
		start_table("$table_style width=95%");
		table_header($th);
		$heading_shown = true;
	}	

	alt_table_row_color($k);
	
    label_cell($myrow['account']);
	label_cell($myrow['account_name']);
	if ($dim >= 1)
		label_cell(get_dimension_string_by_branch($myrow['dimension_id'], true, $b));
	if ($dim > 1)
		label_cell(get_dimension_string_by_branch($myrow['dimension2_id'], true, $b));

	display_debit_or_credit_cells($myrow['amount']);
	label_cell($myrow['memo_']);
	end_row();
	
	$value = round2($myrow['amount'] , user_price_dec());
	if ($value >= 0)
	{
	$t_amount1+=$value;
	}
	elseif ($value< 0)
	{
	$t_amount2+=$value;
	}
	}
	
	start_row();
	    label_cell('TOTAL:',"colspan=2 align=right");
		
		if ($t_amount1!=abs($t_amount2)){
		label_cell("<font color=red>".number_format2(abs($t_amount1),2)."</font>",'align=right');
	    label_cell("<font color=red>".number_format2(abs($t_amount2),2)."</font>",'align=right');
		}
		else {
		amount_cell($t_amount1);
	    amount_cell(abs($t_amount2));
		}

		label_cell('');
	end_row();

}
end_table(1);
//end of while loop

if ($heading_shown)
{
	if ($_GET['type_id'] == 52 OR $_GET['type_id'] == 53)
	{
		$prepared_by_sql = "SELECT user, date(stamp) FROM ".$branch['aria_db'].".".TB_PREF."audit_trail 
										WHERE type = ".$_GET['type_id']." 
										AND trans_no = ".$_GET['trans_no']."
										AND description = ''";
		// display_error($prepared_by_sql);
		
		$prepared_by_res = db_query($prepared_by_sql);
		if (db_num_rows($prepared_by_res) > 0)
		{
			$prepared_by_row = db_fetch($prepared_by_res);
			$date__ = explode_date_to_dmy(sql2date($prepared_by_row[1]));
			// $cheque->TextWrap(58, 71-11-12-27, 111, get_username_by_id($prepared_by_row[0]), 'left');
			// $cheque->TextWrap(58, 71-11-12-27+13, 100, $date__[1].'/'.$date__[0], 'right');
		}
		
		start_table($table_style. 'width=95%');
		
		if ($_GET['type_id'] == 53)
		{
			$supp_trans_row = get_supp_trans_2_by_branch($_GET['trans_no'], $_GET['type_id'], $b);
			
			if ($supp_trans_row['special_reference'] == '') // OLD DM
			{
				start_row();
					labelheader_cell('Prepared by:');
					label_cell(get_username_by_id_by_branch($prepared_by_row[0], $b));
				end_row();
			}
			else //NEW DM
			{
				$sdma_row = get_sdma_by_ref_by_branch($supp_trans_row['supp_reference'], $b);
				start_row();
					labelheader_cell('Prepared by:');
					label_cell(get_username_by_id_by_branch($sdma_row['prepared_by'], $b));
					labelheader_cell('Approved by:');
					label_cell(get_username_by_id_by_branch($sdma_row['approval_1'], $b));
					labelheader_cell('Approved by:');
					label_cell(get_username_by_id_by_branch($sdma_row['approval_2'], $b));
				end_row();
			}
			
		}
		else
		{
			start_row();
				labelheader_cell('Prepared by:');
				label_cell(get_username_by_id_by_branch($prepared_by_row[0], $b));
			end_row();
		}
		end_table(1);
		
		
	}
	
}


is_voided_display_by_branch($_GET['type_id'], $_GET['trans_no'], _("This transaction has been voided."), $b);

end_page(true);

?>
