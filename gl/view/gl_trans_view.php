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
function get_user_c($id,$trans_no){


		$sql = "
			select 
			CASE
				WHEN real_name = '' OR real_name = null 
				THEN user_id
				ELSE real_name
			END as user
			from 0_audit_trail as autl 
			INNER JOIN 0_users as s
			on s.id = autl.`user`
			where type =".$id." and trans_no = ".$trans_no."
		";
		$res = db_query($sql);
		$row = db_fetch($res);
		return $row[0];
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
	
	display_heading($com['coy_name']);
	display_heading2($com['postal_address']);
	br(2);
	
	$supp_ref = '';
	$trans_name = $systypes_array[$_GET['type_id']]. trade_non_trade_inv($_GET['type_id'],$_GET['trans_no']); //$systypes_array[$_GET['type_id']];
    start_table("$table_style width=95%");
    $th = array($col1,_("General Ledger Transaction Details"), _("Reference"),
    	_("Date"),_("Created by"));
		
	if ($_GET['type_id'] == 52 OR $_GET['type_id'] == 53)
	{
		$th[] = 'Supplier Reference';
		$sql = "SELECT supp_reference, tran_date FROM ".TB_PREF."supp_trans 
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
		label_cell(payment_person_name($myrow["person_type_id"],$myrow["person_id"]));
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
	
	label_cell(get_user_c($_GET['type_id'],$_GET['trans_no']));
	end_row();

	if ($_GET['type_id'] != 52 AND $_GET['type_id'] != 53)
		comments_display_row($_GET['type_id'], $_GET['trans_no']);


    end_table(1);
}
$sql = "SELECT gl.*, cm.account_name, IF(ISNULL(refs.reference), '', refs.reference) AS reference FROM "
	.TB_PREF."gl_trans as gl
	LEFT JOIN ".TB_PREF."chart_master as cm ON gl.account = cm.account_code
	LEFT JOIN ".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)"
	." WHERE gl.type= ".db_escape($_GET['type_id']) 
	." AND gl.type_no = ".db_escape($_GET['trans_no'])
	." ORDER BY amount DESC, counter ASC";
$result = db_query($sql,"could not get transactions");
//alert("sql = ".$sql);
//display_error($sql);

if (db_num_rows($result) == 0)
{
	$sql = "SELECT gl.*, cm.account_name, IF(ISNULL(refs.reference), '', refs.reference) AS reference FROM "
		.TB_PREF."gl_trans_temp as gl
		LEFT JOIN ".TB_PREF."chart_master as cm ON gl.account = cm.account_code
		LEFT JOIN ".TB_PREF."refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)"
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
		label_cell(get_dimension_string($value['dimension_id'], true));
	if ($dim > 1)
		label_cell(get_dimension_string($value['dimension2_id'], true));

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
		label_cell(get_dimension_string($myrow['dimension_id'], true));
	if ($dim > 1)
		label_cell(get_dimension_string($myrow['dimension2_id'], true));

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
		$prepared_by_sql = "SELECT user, date(stamp) FROM ".TB_PREF."audit_trail 
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
			$supp_trans_row = get_supp_trans_2($_GET['trans_no'],$_GET['type_id']);
			
			if ($supp_trans_row['special_reference'] == '') // OLD DM
			{
				start_row();
					labelheader_cell('Prepared by:');
					label_cell(get_username_by_id($prepared_by_row[0]));
				end_row();
			}
			else //NEW DM
			{
				$sdma_row = get_sdma_by_ref($supp_trans_row['supp_reference']);
				start_row();
					labelheader_cell('Date:');
					label_cell(sql2date($sdma_row['date_created']));
					labelheader_cell('Date:');
					label_cell(sql2date($sdma_row['date_created']));
					labelheader_cell('Date:');
					label_cell(sql2date($sdma_row['date_created']));
				end_row();


				start_row();
					labelheader_cell('Prepared by:');
					label_cell(get_username_by_id($sdma_row['prepared_by']));
					labelheader_cell('Approved by:');
					label_cell(get_username_by_id($sdma_row['approval_1']));
					labelheader_cell('Approved by:');
					label_cell(get_username_by_id($sdma_row['approval_2']));
				end_row();
			}
			
		}
		else
		{
			start_row();
				labelheader_cell('Prepared by:');
				label_cell(get_username_by_id($prepared_by_row[0]));
			end_row();
		}
		end_table(1);
		
		
	}
	
}


is_voided_display($_GET['type_id'], $_GET['trans_no'], _("This transaction has been voided."));

end_page(true);

?>
