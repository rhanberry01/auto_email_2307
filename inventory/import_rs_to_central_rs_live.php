<?php
/**
 * Title: Import Per Branch RS to Centralized RS
 * Description: Auto Import [RS] Returned Merchandise (RS Header, Items)
 * 				per branch to Centralized RS Database.
 * Author: AJSP 
 * Date: 08/29/2017 
 */
 
ini_set('memory_limit', '-1');

// MySQL Custom Settings
ini_set('mysql.connect_timeout','0');   
ini_set('max_execution_time', '0'); 

// MSSQL Custom Settings
ini_set('mssql.connect_timeout', 0);
ini_set('mssql.timeout', 0);
ini_set('mssql.textlimit', 2147483647);
ini_set('mssql.textsize', 2147483647);

set_time_limit(0);   
  

$seconds = 15;
if (isset($_GET['sitrololo'])) 
	header('Refresh: '.$seconds);

$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 600);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Import Branch RS to Central RS"), false, false, "", $js);

function ping_conn($host, $port=1433, $timeout=2) {
	$fsock = fsockopen($host, $port, $errno, $errstr, $timeout);
	if (!$fsock) 
		return false;
	else
		return true;
}

function notify($msg, $type=1) {
	if ($type == 2) {
		echo '<div class="msgbox">
				<div class="err_msg">
					' . $msg . '
				</div>
			</div>';
	}
	elseif ($type == 1) {
	 	echo '<div class="msgbox">
				<div class="note_msg">
					' . $msg . '
				</div>
			</div>';
	 }
}

function get_rms_com_ids($conn, $branch) {
	$sql = "SELECT coy_code FROM centralized_returned_merchandise.0_company WHERE branch_id = " .$branch['id']. "";
	$query = mysql_query($sql, $conn);
	$com_id = array();
	while ($com_row = mysql_fetch_assoc($query)) {
		$com_id[] = $com_row['coy_code'];
	}
	return $com_id;
}

function get_rms_user_ids($conn, $branch) {
	$sql = "SELECT id FROM centralized_returned_merchandise.0_users WHERE branch_id = " .$branch['id']. "";
	$query = mysql_query($sql, $conn);
	$users_id = array();
	while ($users_row = mysql_fetch_assoc($query)) {
		$users_id[] = $users_row['id'];
	}
	return $users_id;
}

function get_rms_ids($conn, $branch) {
	$sql = "SELECT rs_id FROM centralized_returned_merchandise.0_rms_header WHERE branch_id = " . $branch['id'] ."";
	$query = mysql_query($sql, $conn);
	$rms_id = array();
	while ($rms_row = mysql_fetch_assoc($query)) {
		$rms_id[] = $rms_row['rs_id'];
	}
	return $rms_id;
}

if (isset($_POST['ImportRS']) OR isset($_GET['sitrololo'])) {
	
	$preloader_gif = $path_to_root.'/themes/modern/images/ajax-loader.gif';

	echo "<div id='ploader' style='display:none'>
			<img src='$preloader_gif'>
		</div>";

	display_notification("Import Branch RS to Central RS Database Started");

	if (isset($_POST['ImportRS'])) {
		meta_forward($_SERVER['PHP_SELF'], "sitrololo=ayasawanitralala");
	}

	$host = "192.168.0.91";
	$user = "root";
	$pass = "srsnova";
	$db = "centralized_returned_merchandise"; // centralized_returned_merchandise
	$b_conn = mysql_connect($host, $user, $pass);

	if (!ping_conn($host, 3306)) { // 3306 - mysql but not working :/
		notify("ERR: Cannot Reach to Centralized RS Database - " . $host . ". Please Try Again Later.", 2);
		return false;
	}
	else {
		if (!$b_conn) {
			notify("ERR: Connection to " . $host . " failed! Please try again!", 2);
		}
		else {
			notify("Connected to Centralized RS Database!");
			mysql_select_db($db,  $b_conn);

			$b_sql = "SELECT * FROM 0_branches";
			$b_query = mysql_query($b_sql, $b_conn);

			while ($b_row = mysql_fetch_assoc($b_query)) {
				// Get RM Header ID's if exist
				$com_id = get_rms_com_ids($b_conn, $b_row);
				$user_id = get_rms_user_ids($b_conn, $b_row);
				$rms_id = get_rms_ids($b_conn, $b_row);

				notify("Trying to reach " . $b_row['name'] . " ...");
				if (!ping_conn($b_row['rs_host'], 80)) {
					notify("ERR: " . $b_row['name'] . " - " . $b_row['rs_host'] . " cannot be reached! Trying to connect. Please wait...</br>Skipping " . $b_row['name'] . " ... ", 2);
					continue;
				}
				else {
					$rms_conn = mysql_connect($b_row['rs_host'], $b_row['rs_user'], $b_row['rs_pass']);

					if (!$rms_conn) {
						notify("ERR: Cannot connect to " . $b_row['rs_host'] . ".</br>Skipping " . $b_row['name'] . " ... ", 2);
						continue;
					}
					else {
						notify("Connected to " . $b_row['rs_host']);
						mysql_select_db($b_row['rs_db'], $rms_conn);

						$all_rms_sql = array();

						if (!empty($com_id))
							$com_sql = "SELECT * FROM 0_company WHERE coy_code NOT IN (".implode(',', $com_id).")";	
						else
							$com_sql = "SELECT * FROM 0_company";

						$com_query = mysql_query($com_sql, $rms_conn);
						$com_num_rows = mysql_num_rows($com_query);
						if ($com_num_rows == 0) {
							$all_rms_sql['com_count'] = 0;
							$all_rms_sql['com'] = '';
						}
						else {

							$temp_com_arr = array();
							while ($com_row = mysql_fetch_assoc($com_query)) {
								$c_sql = "INSERT INTO centralized_returned_merchandise.0_company (coy_code, coy_name, gst_no, coy_no, tax_prd, tax_last, postal_address, phone, fax, email, coy_logo, domicile, curr_default, debtors_act, pyt_discount_act, creditors_act, creditors_act_nt, bank_charge_act, exchange_diff_act, profit_loss_year_act, retained_earnings_act, freight_act, default_sales_act, default_sales_discount_act, default_sales_ewt_act, default_sales_tracking_charges_act, default_prompt_payment_act, default_inventory_act, default_cogs_act, default_adj_act, default_inv_sales_act, default_assembly_act, payroll_act, allow_negative_stock, allow_credit_limit, allow_so_edit, allow_so_approval, allow_voiding, allow_po_editing, allow_po_approval, po_over_receive, po_over_charge, default_credit_limit, default_workorder_required, default_dim_required, past_due_days, use_dimension, f_year, no_item_list, no_customer_list, no_supplier_list, base_sales, foreign_codes, accumulate_shipping, legal_text, default_delivery_required, version_id, time_zone, add_pct, round_to, login_tout, sales_other_charges_act_positive, sales_other_charges_act_negative, pyt_other_charges_act_positive, pyt_other_charges_act_negative, default_purchase_ewt_act, cash_bank_account, check_bank_account, rdo_code, line_of_business, zip_code, atc, perc_tax, ewt_percent, purchase_vat, purchase_non_vat, rebate_act, online_payment_bank_id, branch_id, branch_name) VALUES ('".$com_row['coy_code']."', '".$com_row['coy_name']."', '".$com_row['gst_no']."', '".$com_row['coy_no']."', '".$com_row['tax_prd']."', '".$com_row['tax_last']."', '".$com_row['postal_address']."', '".$com_row['phone']."', '".$com_row['fax']."', '".$com_row['email']."', '".$com_row['coy_logo']."', '".$com_row['domicile']."', '".$com_row['curr_default']."', '".$com_row['debtors_act']."', '".$com_row['pyt_discount_act']."', '".$com_row['creditors_act']."', '".$com_row['creditors_act_nt']."', '".$com_row['bank_charge_act']."', '".$com_row['exchange_diff_act']."', '".$com_row['profit_loss_year_act']."', '".$com_row['retained_earnings_act']."', '".$com_row['freight_act']."', '".$com_row['default_sales_act']."', '".$com_row['default_sales_discount_act']."', '".$com_row['default_sales_ewt_act']."', '".$com_row['default_sales_tracking_charges_act']."', '".$com_row['default_prompt_payment_act']."', '".$com_row['default_inventory_act']."', '".$com_row['default_cogs_act']."', '".$com_row['default_adj_act']."', '".$com_row['default_inv_sales_act']."', '".$com_row['default_assembly_act']."', '".$com_row['payroll_act']."', '".$com_row['allow_negative_stock']."', '".$com_row['allow_credit_limit']."', '".$com_row['allow_so_edit']."', '".$com_row['allow_so_approval']."', '".$com_row['allow_voiding']."', '".$com_row['allow_po_editing']."', '".$com_row['allow_po_approval']."', '".$com_row['po_over_receive']."', '".$com_row['po_over_charge']."', '".$com_row['default_credit_limit']."', '".$com_row['default_workorder_required']."', '".$com_row['default_dim_required']."', '".$com_row['past_due_days']."', '".$com_row['use_dimension']."', '".$com_row['f_year']."', '".$com_row['no_item_list']."', '".$com_row['no_customer_list']."', '".$com_row['no_supplier_list']."', '".$com_row['base_sales']."', '".$com_row['foreign_codes']."', '".$com_row['accumulate_shipping']."', '".$com_row['legal_text']."', '".$com_row['default_delivery_required']."', '".$com_row['version_id']."', '".$com_row['time_zone']."', '".$com_row['add_pct']."', '".$com_row['round_to']."', '".$com_row['login_tout']."', '".$com_row['sales_other_charges_act_positive']."', '".$com_row['sales_other_charges_act_negative']."', '".$com_row['pyt_other_charges_act_positive']."', '".$com_row['pyt_other_charges_act_negative']."', '".$com_row['default_purchase_ewt_act']."', '".$com_row['cash_bank_account']."', '".$com_row['check_bank_account']."', '".$com_row['rdo_code']."', '".$com_row['line_of_business']."', '".$com_row['zip_code']."', '".$com_row['atc']."', '".$com_row['perc_tax']."', '".$com_row['ewt_percent']."', '".$com_row['purchase_vat']."', '".$com_row['purchase_non_vat']."', '".$com_row['rebate_act']."', '".$com_row['online_payment_bank_id']."', '".$b_row['id']."', '".$b_row['name']."')"; 
								$temp_com_arr[] = array(
									'com_id' => $com_row['coy_code'],
									'com_sql' => $c_sql
								);
							}
							$all_rms_sql['com_count'] = $com_num_rows; 
							$all_rms_sql['com'] = $temp_com_arr; 
						}


						if (!empty($user_id))
							$user_sql = "SELECT * FROM 0_users WHERE id NOT IN (".implode(',', $user_id).")";	
						else
							$user_sql = "SELECT * FROM 0_users";

						// notify($user_sql, 2);

						$user_query = mysql_query($user_sql, $rms_conn);
						$u_num_rows = mysql_num_rows($user_query);
						if ($u_num_rows == 0) {
							$all_rms_sql['user_count'] = 0;
							$all_rms_sql['users'] = '';
						}
						else {
							$temp_u_arr = array();
							while ($user_row = mysql_fetch_assoc($user_query)) {
								if ($user_row['last_visit_date'] == '')
									$user_row['last_visit_date'] = 'NULL';
								else
									$user_row['last_visit_date'] = "'".$user_row['last_visit_date']."'";

								if ($user_row['can_edit'] == '')
									$user_row['can_edit'] = 'NULL';

								if ($user_row['can_approve_so'] == '')
									$user_row['can_approve_so'] = 'NULL';

								if ($user_row['can_approve_po'] == '')
									$user_row['can_approve_po'] = 'NULL';

								if ($user_row['can_approve_cv'] == '')
									$user_row['can_approve_cv'] = 'NULL'; 

								if ($user_row['can_approve_sales_remittance'] == '')
									$user_row['can_approve_sales_remittance'] = 'NULL'; 

								$u_sql = "INSERT INTO centralized_returned_merchandise.0_users (id, user_id, password, real_name, role_id, page_size, prices_dec, qty_dec, rates_dec, percent_dec, show_gl, show_codes, show_hints, last_visit_date, startup_tab, inactive, is_supervisor, allow_user, can_credit_limit, can_negative_inv, can_details, can_void, can_edit, can_approve_so, can_approve_po, can_approve_cv, can_approve_sales_remittance, branch_id, branch_name) VALUES ('".$user_row['id']."', '".$user_row['user_id']."', '".$user_row['password']."', '".$user_row['real_name']."', '".$user_row['role_id']."', '".$user_row['page_size']."', '".$user_row['prices_dec']."', '".$user_row['qty_dec']."', '".$user_row['rates_dec']."', '".$user_row['percent_dec']."', '".$user_row['show_gl']."', '".$user_row['show_codes']."', '".$user_row['show_hints']."', ".$user_row['last_visit_date'].", '".$user_row['startup_tab']."', '".$user_row['inactive']."', '".$user_row['is_supervisor']."', '".$user_row['allow_user']."', '".$user_row['can_credit_limit']."', '".$user_row['can_negative_inv']."', '".$user_row['can_details']."', '".$user_row['can_void']."', ".$user_row['can_edit'].", ".$user_row['can_approve_so'].", ".$user_row['can_approve_po'].", '".$user_row['can_approve_cv']."', '".$user_row['can_approve_sales_remittance']."', '".$b_row['id']."', '".$b_row['name']."')";
								$temp_u_arr[] = array(
										'user_id' => $user_row['id'],
										'user_sql' => $u_sql
									);
							}
							$all_rms_sql['user_count'] = $u_num_rows;
							$all_rms_sql['users'] = $temp_u_arr;
						}

						if (!empty($rms_id)) 
							$rms_sql = "SELECT * FROM 0_rms_header WHERE rs_id NOT IN (".implode(',', $rms_id).") LIMIT 1000";
						else 
							$rms_sql = "SELECT * FROM 0_rms_header LIMIT 1000";

						// notify($rms_sql);
						
						$rms_query = mysql_query($rms_sql, $rms_conn);

						$h_num_rows = mysql_num_rows($rms_query);
						if ($h_num_rows == 0) {
							// continue;
							$all_rms_sql['header_count'] = 0;
							$all_rms_sql['rms'] = '';
						}
						else {
							// notify("Processing " . $h_num_rows . " Returned Merchandise Records");
							$all_rms_sql['header_count'] = mysql_num_rows($rms_query); 
							
							$temp_rms_arr = array();
							while ($rms_row = mysql_fetch_assoc($rms_query)) {

								$temp_arr = array();

								$temp_posted = 0;
								$approved = 0;
								if ($rms_row['temp_posted'] == 1)
									$temp_posted = 2;

								if ($rms_row['approved'] == 1)
									$approved = 2;

								if ($rms_row['acct_processed_date'] == '')
									$rms_row['acct_processed_date'] = 'NULL';
								else
									$rms_row['acct_processed_date'] = "'".$rms_row['acct_processed_date']."'";

								if ($rms_row['date_temp_posted'] == '')
									$rms_row['date_temp_posted'] = 'NULL';
								else
									$rms_row['date_temp_posted'] = "'".$rms_row['date_temp_posted']."'";

								if ($rms_row['purch_approved_by'] == '')
									$rms_row['purch_approved_by'] = 'NULL'; 

								if ($rms_row['purch_approved_date'] == '')
									$rms_row['purch_approved_date'] = 'NULL';
								else
									$rms_row['purch_approved_date'] = "'".$rms_row['purch_approved_date']."'";

								if ($rms_row['date_approved'] == '')
									$rms_row['date_approved'] = 'NULL';
								else
									$rms_row['date_approved'] = "'".$rms_row['date_approved']."'";

								if ($rms_row['bo_processed_date'] == '')
									$rms_row['bo_processed_date'] = 'NULL';
								else
									$rms_row['bo_processed_date'] = "'".$rms_row['bo_processed_date']."'";

								if ($rms_row['temp_posted_by_aria_user'] == '')
									$rms_row['temp_posted_by_aria_user'] = 'NULL';

								if ($rms_row['movement_no'] == '')
									$rms_row['movement_no'] = 'NULL';

								$h_sql = "INSERT INTO centralized_returned_merchandise.0_rms_header (rs_id, rs_date, supplier_code, rs_action, comment, movement_type, movement_no, processed, bo_processed_date, acct_processed_date, created_by, processed_by, acct_processed_by, trans_type, trans_no, approved, temp_posted, temp_posted_by_aria_user, date_temp_posted, purch_approved, purch_approved_by, purch_approved_date, purch_approved_comment, temp_post_comment, approved_by_aria_user, date_approved, approver_comment, branch_id, branch_name) VALUES ('".$rms_row['rs_id']."', '".$rms_row['rs_date']."', '".$rms_row['supplier_code']."', '".$rms_row['rs_action']."', '".mysql_real_escape_string($rms_row['comment'])."', '".$rms_row['movement_type']."', ".$rms_row['movement_no'].", '".$rms_row['processed']."', ".$rms_row['bo_processed_date'].", ".$rms_row['acct_processed_date'].", '".$rms_row['created_by']."', '".$rms_row['processed_by']."', '".$rms_row['acct_processed_by']."', '".$rms_row['trans_type']."', '".$rms_row['trans_no']."', '".$approved."', '".$temp_posted."', ".$rms_row['temp_posted_by_aria_user'].", ".$rms_row['date_temp_posted'].", '".$rms_row['purch_approved']."', ".$rms_row['purch_approved_by'].", ".$rms_row['purch_approved_date'].", '".$rms_row['purch_approved_comment']."', '".$rms_row['temp_post_comment']."', '".$rms_row['approved_by_aria_user']."', ".$rms_row['date_approved'].", '".$rms_row['approver_comment']."', '".$b_row['id']."', '".$b_row['name']."')";
								
								$temp_arr['header'] = $h_sql;
								$temp_arr['header_id'] = $rms_row['rs_id'];

								$rms_i_sql = "SELECT * FROM 0_rms_items WHERE rs_id = '".$rms_row['rs_id']."'";
								$rms_i_query = mysql_query($rms_i_sql, $rms_conn);

								$i_num = mysql_num_rows($rms_i_query);
								
								if ($i_num == 0) {
									$temp_arr['items'] = '';
									$temp_arr['items_count'] = 0;
									$temp_arr['items_id'] = '';
									$temp_rms_arr[] = $temp_arr;
									// continue;
								}
								else {
									// notify("Processing " . $i_num . " Returned Merchandise Items.");
									
									$i_sql = "INSERT INTO centralized_returned_merchandise.0_rms_items (id, rs_id, prod_id, barcode, item_name, uom, qty, orig_uom, orig_multiplier, custom_multiplier, price, supplier_code, branch_id, branch_name) VALUES ";
									
									$items_id = array();
									$i = 1;
									while ($rms_i_row = mysql_fetch_assoc($rms_i_query)) {
										if ($i < $i_num) 
											$i_sql .= "('".$rms_i_row['id']."', '".$rms_i_row['rs_id']."', '".$rms_i_row['prod_id']."', '".$rms_i_row['barcode']."', '".$rms_i_row['item_name']."', '".$rms_i_row['uom']."', '".$rms_i_row['qty']."', '".$rms_i_row['orig_uom']."', '".$rms_i_row['orig_multiplier']."', '".$rms_i_row['custom_multiplier']."', '".$rms_i_row['price']."', '".$rms_i_row['supplier_code']."', '".$b_row['id']."', '".$b_row['name']."'), ";
										else
											$i_sql .= "('".$rms_i_row['id']."', '".$rms_i_row['rs_id']."', '".$rms_i_row['prod_id']."', '".$rms_i_row['barcode']."', '".$rms_i_row['item_name']."', '".$rms_i_row['uom']."', '".$rms_i_row['qty']."', '".$rms_i_row['orig_uom']."', '".$rms_i_row['orig_multiplier']."', '".$rms_i_row['custom_multiplier']."', '".$rms_i_row['price']."', '".$rms_i_row['supplier_code']."', '".$b_row['id']."', '".$b_row['name']."') ";

										$items_id[] = $rms_i_row['id'];
										$i++;
									}

									$temp_arr['items'] = $i_sql;
									$temp_arr['items_count'] = $i_num;
									$temp_arr['items_id'] = implode(', ', $items_id);
									$temp_rms_arr[] = $temp_arr;
								}

							}
							$all_rms_sql['rms'] = $temp_rms_arr;
							
						}
						
						if ($b_row['id'] != 1)
							mysql_close($rms_conn);	

						// echo "<pre>";
						// print_r($all_rms_sql);
						// echo "</pre>";
						// exit;

						if (!empty($all_rms_sql)) {
							if ($all_rms_sql['com_count'] != 0 && !empty($all_rms_sql['com'])) {
								notify("Processing " . $all_rms_sql['com_count'] . " Company");
								foreach ($all_rms_sql['com'] as $key => $value) {
									mysql_query("BEGIN", $b_conn);
									$run_com = mysql_query($value['com_sql'], $b_conn);
									$c_num = mysql_affected_rows($b_conn);
									notify("ERR: " . mysql_error($b_conn), 2);
									notify("Inserting coy_code " . $value['com_id']);
									if ($run_com) {
										notify($c_num . " company is added.");
										mysql_query("COMMIT", $b_conn);
									}
									else {
										notify("ERR: Company cannot be inserted", 2);
										mysql_query("ROLLBACK", $b_conn);
										notify("ERR: Transaction has been cancelled for coy_code " . $value['com_id'] , 2);
									}
								}
							}
							else {
								notify("Nothing is processed for 0_company table.");
							}

							if ($all_rms_sql['user_count'] != 0 && !empty($all_rms_sql['users'])) {
								notify("Processing " . $all_rms_sql['user_count'] . " Users");
								foreach ($all_rms_sql['users'] as $key => $value) {
									mysql_query("BEGIN", $b_conn);
									$run_user = mysql_query($value['user_sql'], $b_conn);
									$user_num = mysql_affected_rows($b_conn);
									notify("ERR: " . mysql_error($b_conn), 2);
									notify("Inserting user_id " . $value['user_id']);
									if ($run_user) {
										notify($user_num . " user is added.");
										mysql_query("COMMIT", $b_conn);
									}
									else {
										notify("ERR: User cannot be inserted", 2);
										mysql_query("ROLLBACK", $b_conn);
										notify("ERR: Transaction has been cancelled for user_id " . $value['user_id'] , 2);
									}
								}
							}
							else {
								notify("Nothing is processed for 0_users table.");
							}

							if ($all_rms_sql['header_count'] != 0 && !empty($all_rms_sql['rms'])) {
								notify("Processing " . $all_rms_sql['header_count'] . " Returned Merchandise Records");
								foreach ($all_rms_sql['rms'] as $key => $value) {
									notify("Inserting " . $value['header_id']);
									mysql_query("BEGIN", $b_conn);
									// notify($value['header'], 2);
									$run_header = mysql_query($value['header'], $b_conn);
									$h_num = mysql_affected_rows($b_conn);
									if ($run_header) {
										if (!empty($value['items'])) {
											notify("Processing " . $value['items_count'] . " Returned Merchandise Items.");
											notify("Inserting " . $value['items_id']);
											$run_items = mysql_query($value['items'], $b_conn);
											if ($run_items) {
												notify($h_num . " RM Header Added.");
												notify(mysql_affected_rows($b_conn) . " RM Items Added.");
												mysql_query("COMMIT", $b_conn);
											}
											else {
												notify("ERR: " . mysql_error($b_conn), 2);
												mysql_query("ROLLBACK", $b_conn);
												notify("ERR: Transaction has been cancelled. Header ID " . $value['header_id'] . " along with " . $value['items_id'] . " items", 2);
											}
										}
										else {
											notify($h_num . " RM Header Added.");
											mysql_query("COMMIT", $b_conn);
										}
									}
									else {
										notify("ERR: " . mysql_error($b_conn), 2);
										mysql_query("ROLLBACK", $b_conn);
										notify("ERR: Transaction has been cancelled. Header ID " . $value['header_id'], 2);
									}
								}
							}
							else {
								notify("Nothing is processed for Return Merchandise.");
							}
						}
						notify("Done Processing " . $b_row['name']); 
					}
				}
			}
		}
	}

	mysql_close($b_conn);

}


// Start Form
start_form();

if (!isset($_GET['sitrololo'])) 
	submit_center('ImportRS', '<b>Start Import</b>');

end_form();
end_page();