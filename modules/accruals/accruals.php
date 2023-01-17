<?php
/**********************************************
Author: Joe Hunt
Name: Revenue / Cost Accruals v2.2
Free software under GNU GPL
***********************************************/
$page_security = 'SA_ACCRUALS';
$path_to_root="../..";

include_once($path_to_root . "/includes/session.inc");
add_access_extensions();
include_once($path_to_root . "/gl/includes/db/gl_db_trans.inc");

$js = get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

// Begin the UI
include_once($path_to_root . "/includes/ui.inc");

page("Revenue / Cost Accruals", false, false,'', $js);
// Turn these next two lines on for debugging
//error_reporting(E_ALL);
//ini_set("display_errors", "on");

//--------------------------------------------------------------------------------------------------
function is_date_in_fiscalyears($date)
{
	$date = date2sql($date);
	$sql = "SELECT * FROM ".TB_PREF."fiscal_year WHERE '$date' >= begin AND '$date' <= end";

	$result = db_query($sql, "could not get all fiscal years");
	return (db_num_rows($result) > 0);
}

if (!isset($_POST['freq']))
	$_POST['freq'] = 3;
// If the import button was selected, we'll process the form here.  (If not, skip to actual content below.)
if (isset($_POST['go']) || isset($_POST['show']))
{
	$input_error = 0;
	if (!is_date($_POST['date_'])) 
	{
		display_error("The entered date is invalid.");
		set_focus('date_');
		$input_error = 1;
	} 
	elseif (!is_date_in_fiscalyear($_POST['date_'])) 
	{
		display_error("The entered date is not in fiscal year.");
		set_focus('date_');
		$input_error = 1;
	} 
	elseif (input_num('amount', 0) == 0.0)	
	{
		display_error("The amount can not be 0.");
		set_focus('periods');
		$input_error = 1;
	} 
	elseif (input_num('periods', 0) < 1)	
	{
		display_error("The periods must be greater than 0.");
		set_focus('periods');
		$input_error = 1;
	} 
	if ($input_error == 0)
	{
		$periods = input_num('periods');
		$date_ = get_post('date_');
		$freq = get_post('freq');
		$lastdate = ($freq== 1?add_days($date_,7*$periods):($freq==2?add_days($date_,14*$periods):
			($freq==3?add_months($date_,$periods):add_months($date_,3*$periods))));
		if (!is_date_in_fiscalyears($lastdate)) 
		{
			display_error("Some of the period dates are outside the fiscal year. Create a new fiscal year first!");
			set_focus('date_');
			$input_error = 1;
		} 
		if ($input_error == 0)
		{
			$amount = input_num('amount');
			$am = round2($amount / $periods, user_price_dec());
			if ($am * $periods != $amount)
				$am0 = $am + $amount - $am * $periods;
			else
				$am0 = $am;
			if (get_post('memo_') != "")
				$memo = $_POST['memo_'];
			else	
				$memo = "Accruals for $amount";
			if (isset($_POST['go']))
				begin_transaction();
			else
			{
				start_table($table_style);
				$dim = get_company_pref('use_dimension');

				$first_cols = array("Date", "Account");
				if ($dim == 2)
					$dim_cols = array("Dimension 1", "Dimension 2");
				elseif ($dim == 1)
					$dim_cols = array("Dimension");
				else
					$dim_cols = array();

				$remaining_cols = array("Debit", "Credit", "Memo");

				$th = array_merge($first_cols, $dim_cols, $remaining_cols);
				table_header($th);
				$k = 0;
			}
			for ($i = 0; $i < $periods; $i++)
			{
				if ($i > 0)
				{
					switch($freq)
					{
						case 1:
							$date_ = add_days($date_, 7);
							break;
						case 2:	
							$date_ = add_days($date_, 14);
							break;
						case 3:	
							$date_ = add_months($date_, 1);
							break;
						case 4:	
							$date_ = add_months($date_, 3);
							break;
					}
					$am0 = $am;
				}
				if (isset($_POST['go']))
				{
					$id = get_next_trans_no(ST_JOURNAL);
					$ref = $Refs->get_next(ST_JOURNAL);
					add_gl_trans(ST_JOURNAL, $id, $date_, get_post('acc_act'), 0,
						0, $ref, $am0 * -1);
					add_gl_trans(ST_JOURNAL, $id, $date_, get_post('res_act'), get_post('dimension_id'),
						get_post('dimension2_id'), $ref, $am0);
					add_comments(ST_JOURNAL, $id, $date_, $memo);
					$Refs->save(ST_JOURNAL, $id, $ref);
				}
				else
				{
					alt_table_row_color($k);
					label_cell($date_);
					label_cell($_POST['acc_act'] . " " . get_gl_account_name($_POST['acc_act']));
					if ($dim > 0)
						label_cell("");
					if ($dim > 1)	
						label_cell("");
					display_debit_or_credit_cells($am0 * -1);	
					label_cell($memo);
					alt_table_row_color($k);
					label_cell($date_);
					label_cell($_POST['res_act'] . " " . get_gl_account_name($_POST['res_act']));
					if ($dim > 0)
						label_cell(get_dimension_string($_POST['dimension_id'], true));
					if ($dim > 1)	
						label_cell(get_dimension_string($_POST['dimension2_id'], true));
					display_debit_or_credit_cells($am0);	
					label_cell($memo);
				}	
			}
			if (isset($_POST['go']))
			{
				commit_transaction();
				display_notification_centered("Revenue / Cost Accruals have been processed.");
				$_POST['date_'] = $_POST['amount'] = $_POST['periods'] = "";
			}
			else
			{
				end_table(1);
				display_notification_centered("Showing GL Transactions.");
			}
		}	
	}
}

function frequency_list_row($label, $name, $selected=null)
{
	echo "<tr>\n";
	label_cell($label);
	echo "<td>\n";
	$freq = array( 
		'1'=> "Weekly",
		'2'=> "Bi-weekly",
		'3' => "Monthly",
		'4' => "Quarterly",
	);
	echo array_selector($name, $selected, $freq);
	echo "</td>\n";
	echo "</tr\n";
}

$dim = get_company_pref('use_dimension');

start_form(false, false, "", "accrual");
start_table("$table_style2");

date_row("Date", 'date_', 'First date of Accruals', true, 0, 0, 0, null, true);
start_row();
gl_all_accounts_list_cells("Accrued Balance Account", 'acc_act', null, true, false, false, true);
end_row();
gl_all_accounts_list_row("Revenue / Cost Account", 'res_act', null, true);

if ($dim >= 1)
	dimensions_list_row("Dimension", 'dimension_id', null, true, " ", false, 1);
if ($dim > 1)
	dimensions_list_row("Dimension 2", 'dimension2_id', null, true, " ", false, 2);

$url = "modules/accruals/trans.php?act=".get_post('acc_act')."&date=".get_post('date_');
amount_row("Amount", 'amount', null, null, viewer_link("Search Amount", $url, "", "", ICON_VIEW));  

frequency_list_row("Frequency", 'freq', null);

text_row("Periods", 'periods', null, 3, 3);
textarea_row(_("Memo"), 'memo_', null, 35, 3);

end_table(1);
submit_center_first('show', "Show GL Rows");//,true,false,'process',ICON_SUBMIT);
submit_center_last('go', "Process Accruals");//,true,false,'process',ICON_SUBMIT);
submit_js_confirm('go', "Are you sure you want to post accruals?");

end_form();

end_page();

?>
