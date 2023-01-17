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
$page_security = 'SA_ITEMSTRANSVIEW';
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/inventory/includes/db/all_item_adjustments_db.inc");

ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);

$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
	
page(_($help_context = "Approve Disposal"), false, false, "", $js);

if ($_SESSION['wa_current_user']->user == "admin") {
	$u = get_id_by_user_id($_SESSION['wa_current_user']->username);
	$_SESSION['wa_current_user']->user = $u['id'];
}


// echo "<pre>";
// print_r($_SESSION['wa_current_user']);

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

function get_id_by_user_id($user_id) {
	$sql = "SELECT id FROM 0_users WHERE user_id = '".$user_id."'";
	$query = db_query($sql);
	return db_fetch($query);
}

function update_posted_rms_header($rs_id, $branch_id)
{
	$sql = "UPDATE centralized_returned_merchandise.".TB_PREF."rms_header SET 
			date_temp_posted = '".date2sql(Today())."',
			temp_posted = '1',
			temp_posted_by_aria_user = '".$_SESSION['wa_current_user']->user."',
			temp_post_comment = ''
			WHERE rs_id = '$rs_id' 
			AND branch_id = '".$branch_id."'";
	$query = db_query($sql);
	if (mysql_affected_rows() > 0)
		return true;
}

function check_damaged($rs_id, $branch){
	$b = $branch;

	$count = 0 ;
	$count_ = 0 ;
	$barcode = array();
	
	$sql = "SELECT prod_id,barcode,item_name,uom,SUM(qty) as qty,orig_uom,orig_multiplier,
					custom_multiplier,price,supplier_code
			FROM centralized_returned_merchandise.".TB_PREF."rms_items WHERE rs_id IN ($rs_id)
			AND branch_id = '".$b['id']."'
			GROUP BY prod_id,barcode,item_name,uom,orig_uom,orig_multiplier,
					custom_multiplier,price,supplier_code";
	$res = db_query($sql);
	while($row = mysql_fetch_array($res))
	{
		$msconn = mssql_connect($b['ms_mov_host'], $b['ms_mov_user'], $b['ms_mov_pass']);
		if (!$msconn) {
			notify('Could not connect to '. $b['ms_mov_host'], 2);
			exit;
		}
		mssql_select_db($b['ms_mov_db'], $msconn);

		$count++;
		
		$pack = $row['custom_multiplier'] == 0 ? $row['orig_multiplier'] : $row['custom_multiplier'];
		$pcs_qty = ($pack * $row['qty']);
		
		$damaged_check = "SELECT Damaged FROM Products WHERE ProductID = ".$row['prod_id'];
		$damaged_ = mssql_query($damaged_check, $msconn);
		$damaged_row = mssql_fetch_array($damaged_);
		
		$qty_left = $damaged_row['Damaged'] - $pcs_qty;
		
		if($qty_left >= 0)
			$count_++;
		else
			$barcode[] = $row['barcode'];
		
		mssql_close($msconn);
	}
	if($count_ != $count)
		return $barcode;
	else
		return 0;
	
}

function get_rs_items_total($rs_id, $branch_id)
{	
	$sql = "SELECT * FROM centralized_returned_merchandise.".TB_PREF."rms_items
			WHERE rs_id=$rs_id 
			AND branch_id='".$branch_id."'";
	$res = db_query($sql);
	
	$total = 0;
	
	while($row = mysql_fetch_array($res)){
		$total += round2($row['qty']*$row['price'],3);
	}
	
	return $total;
}

function get_username_by_id_($id, $branch_id)
{	
	$sql = "SELECT real_name FROM centralized_returned_merchandise.".TB_PREF."users 
			WHERE id = $id
			AND branch_id='".$branch_id."'";
	$query = db_query($sql);
	$row = mysql_fetch_array($query);
	return $row[0];
}

function update_approved_rms_header($rs_id, $approver_comments, $branch_id) {
	$sql = "UPDATE centralized_returned_merchandise.".TB_PREF."rms_header SET 
			date_approved = '".date2sql(Today())."',
			approved = '1',
			approved_by_aria_user = '".$_SESSION['wa_current_user']->user."',
			approver_comment = '".db_escape($approver_comments)."'
			WHERE rs_id = '$rs_id'
			AND branch_id = '".$branch_id."'";
	$objquery = db_query($sql);
	if (!$objquery)
		notify('failed to update movement (FDFB)', 2);
	else 
		return 1;
}

function get_ms_supp_name($supp_code)
{
	$sql = "SELECT description FROM vendor
			WHERE vendorcode = '$supp_code'";
	$res = ms_db_query($sql);
	$row = mssql_fetch_array($res);
	return $row[0];
}

function get_ms_supp_name_by_branch($supp_code, $branch_id) {
	$b = get_branch_by_id($branch_id);
	$conn = mssql_connect($b['ms_mov_host'], $b['ms_mov_user'], $b['ms_mov_pass']);
	mssql_select_db($b['ms_mov_db'], $conn);
	$sql = "SELECT description FROM vendor
			WHERE vendorcode = '$supp_code'";
	$res = mssql_query($sql, $conn);
	$row = mssql_fetch_array($res);
	return $row[0];
}

function is_purchaser($user_id) {
	$po_host = '192.168.0.56'; // change to .89 if state == development
	$po_user = 'root';
	$po_pass = ''; // srsnova
	$po_db = 'srs';

	$conn = mysql_connect($po_host, $po_user, $po_pass);
	mysql_select_db($po_db, $conn);

	$sql = "SELECT * FROM users WHERE aria_user_id = '".$user_id."'";
	$query = mysql_query($sql, $conn);
	if (mysql_num_rows($query) > 0)
		return true;
}

function get_purchaser_vendors($user_id) {
	$po_host = '192.168.0.56'; // change to .89 if state == development
	$po_user = 'root';
	$po_pass = ''; // srsnova
	$po_db = 'srs';

	$vArr = array();

	$conn = mysql_connect($po_host, $po_user, $po_pass);
	mysql_select_db($po_db, $conn);

	$sql = "SELECT id FROM users WHERE aria_user_id = '".$user_id."'";
	$query = mysql_query($sql, $conn);
	if (mysql_num_rows($query) == 1) {
		$res = mysql_fetch_assoc($query);
		$id = $res['id'];

		$vSql = "SELECT vendor FROM user_vendor WHERE user_id = '".$id."'";
		$vQuery = mysql_query($vSql, $conn);
		while ($vRow = mysql_fetch_assoc($vQuery)) {
			array_push($vArr, $vRow['vendor']);
		}
	}
	return $vArr;
}

function is_operations($user_id) {
	$sql = "SELECT role_id FROM ".TB_PREF."users WHERE id = '".$user_id."'";
	$query = db_query($sql);
	$res = db_fetch($query);
	$role_id = $res['role_id'];
	$s_sql = "SELECT role FROM ".TB_PREF."security_roles WHERE id = '".$role_id."'";
	$s_query = db_query($s_sql);
	if (db_num_rows($s_query) == 1) {
		$result = db_fetch_assoc($s_query);
		$role = $result['role'];
		if ($role == 'Operations Supervisor' || $role_id = '17') {
			return true;
		}
	}
}

function get_branch_by_id($branch_id) {
	$sql = "SELECT * FROM centralized_returned_merchandise.0_branches WHERE id = $branch_id";
	$query = db_query($sql);
	return db_fetch($query);
}

function get_rs_view_str_per_branch($trans_no, $label, $branch_id) {
	global $path_to_root;

	$viewer = "purchasing/view/view_rs_live_2.php?rs_id=$trans_no&bid=$branch_id";

	return "<a target='_blank' href='$path_to_root/$viewer' onclick=\"javascript:openWindow(this.href,this.target); return false;\">$label</a>";
}

function is_divi($supplier_code) {
	$divi_suppliers = array('DIVMUGM001', 'ANDING001', 'BLHAGM001', 'CAMILA002', 'CAICHJ001', 'GOODRU001', 'COTRCO002', 'EVAMAR001', 'DIVADS010', 'JINMOR001', 'JOCGEM001', 'JULUST001', 'CVMAGM001', 'GIGIME001', 'NEESMA001', 'RULESI003', 'SHULIU001', 'SAGENME001', 'DIVLBM001', 'YAYYIN001', 'ZHJIZO002', 'ZHYUDA001', 'CAMILA001', 'CAMILA005', 'EDGTRO001', 'EDGTRO003', 'HOTIBA002', 'DIVJUC001', 'CHDIGM001', 'JUSPAU001', 'WANWEN001', 'WANWEN003', 'LOMEGM001', 'WENHON002', 'WEAENT001', 'WEENCO002', 'GATTRA001', 'GATTRA004', 'BAGEME001', 'ZHJIZO001', 'ZHJIZO007', 'MAJECO002', 'FEABDG001', 'MALUAS004', 'LACTRA002', 'LACTRA001', 'MCPAPR002', 'ANIHON001', 'BAGEME001', 'HONHUA001', 'COASIN001', 'NIMFCO001', 'MUPTII001', 'STCEHO001', 'STCEHO002', 'DCONMA001', 'ALLOGM001', '881163012', 'AMJMAR001', 'AMLMER001', 'ALGMDS001', 'KEMFCO001', 'BVGEMD001', 'BUTEMI001', 'RTABGEN002', 'CHTRCO001', '770174412', 'LUZSTT001', 'PBGMSE001', 'DEBAGM001', 'JASOTY001', 'ELVIMA001', 'ETLETR001', 'EDGIFH001', 'DISHMS001', 'ESPFOO001', 'FMGEME001', 'FAFAWE', '44092512', 'FMSFGM001', 'FULENT012', 'GATTRA003', 'HBCARF001', 'HOCOXI001', 'DIVIHTB001', 'MILFLO001', 'HUCHEN001', 'HUMAPA001', 'HVATRA001', 'ITMMER001', 'JEHETR001', 'RPOCOS002', 'DIVFHB021', 'GAAGEMD001', 'LUCIWC001', 'DIBETR012', 'JJLBRE001', 'JOCGEM002', 'JOROGA001', 'KEGEME001', 'KEKIWE001', 'LEBENT002', 'ADLAME001', 'CVMAGM002', 'LOGEME001', 'LTEPHI002', 'MAGUMA001', 'FEABDG002', 'MAJECO001', 'MARGMD001', 'POPAMP001', 'MARTST001', 'MITOGM001', 'NACALE001', 'NEMAME001', 'DIVINCC001', 'NM2MER001', 'NORTRA001', 'LALYST001', 'PANMER001', 'POPFGM001', 'POWPLY001', 'PUPLGM001', 'RABACO', 'RESRTS001', 'ANGCOL001', 'SKNACO003', 'SARENT001', 'CCMEWS001', '78811112', 'SHYOXI001', 'DIVRGM001', 'SIAIRM001', 'SOGBAN001', 'SMCPINC001', 'SOEDEN001', 'SPLRTW001', 'STAPCO001', 'TRETGM', 'IPIMAR001', 'DIVCHMP011', 'JEFMAR001', 'SHTRGM001', 'FIGEME001', 'FGILGM001', 'ZHJIZO009', 'ZHJIZO003', 'JIMTAN001');
	if (in_array($supplier_code, $divi_suppliers))
		return true;
}

if ($db_connections[$_SESSION["wa_current_user"]->company]["name"] != 'San Roque Supermarket - NOVA') {
	display_error('<h2>'."YOU CAN ONLY ACCESS THIS PAGE WHEN YOU'RE LOGGED IN FROM NOVALICHES BRANCH. ".'</br>'."Please logout and login to novaliches branch in order to proceed. Thank you!".'</h2>');
	exit;
}

if (isset($_POST['approve_all_selected'])) {
	if (isset($_POST['post_id']) && !empty($_POST['post_id'])) {
		foreach ($_POST['post_id'] as $key => $posted_approve_id) {
			$branch_id = $_POST['bid_'.$posted_approve_id];
			$update_status = update_posted_rms_header($posted_approve_id, $branch_id);
			if ($update_status) 
				notify("Disposal #".$posted_approve_id." is successfully posted but it is still pending for approval.");
			else 
				notify("Disposal #".$posted_approve_id." is not posted! Please try again.", 2);
		}
	}


	if (isset($_POST['approve_id']) && !empty($_POST['approve_id'])) {

		foreach ($_POST['approve_id'] as $key => $approve_id) {
			
			$b = get_branch_by_id($_POST['bid_'.$approve_id]);
			if (ping_conn($b['ms_mov_host'])) {
				$check = check_damaged($approve_id, $b);
				
				if ($check == 0) {
					// $approver_comments = $_POST['approver_comments'.$approve_id];
					$approver_comments = '';
					$a = update_approved_rms_header($approve_id, $approver_comments, $b['id']);
					if ($a) 
						notify("Disposal #".$approve_id." is successfully approved but it is still temporary.");
					else 
						notify("Disposal #".$approve_id." is not approved! Please try again.", 2);
				}
				else {
						notify("Cannot processed barcode(s) ".implode(',',$check)." in Trans. # ".$approve_id.".", 2);		
				}
			}
			else {
				notify($b['ms_mov_host'] . " is not reacheable. Skipping Trans. # ".$approve_id.".", 2);
			}

				
		}
	}
}


$purchaser_vendors = '';
if (is_purchaser($_SESSION['wa_current_user']->user)) {
	$purchaser_vendors = get_purchaser_vendors($_SESSION['wa_current_user']->user);
}

// print_r($purchaser_vendors);

// Custom JS here
echo '<script type="text/javascript">';
	echo '$(document).ready(function() {

			$("#post_all").change(function() {
				$(".post_one").attr("checked", this.checked);
			});

			$("#approve_all").change(function() {
				$(".approve_one").attr("checked", this.checked);
			});

			$(".post_one").change(function() {
				if ($(".post_one").length == $(".post_one:checked").length) {
					$("#post_all").attr("checked", "checked");
				}
				else {
					$("#post_all").removeAttr("checked");
				}
			});

			$(".approve_one").change(function() {
				if ($(".approve_one").length == $(".approve_one:checked").length) {
					$("#approve_all").attr("checked", "checked");
				}
				else {
					$("#approve_all").removeAttr("checked");
				}
			});

			$("input:checkbox").change(function() {
				if ($("input:checkbox:checked").length > 0) {
					$("#approve_all_selected").show();
				}
				else {
					$("#approve_all_selected").hide();
				}	
			});
			
		});';
echo '</script>';	

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
ref_cells('#: ','rs_id', null, null, null, true);
supplier_list_ms_cells('Supplier: ', 'supplier_code', null, true);
date_cells('Date From: ', 'rs_date_from');
date_cells('Date To: ', 'rs_date_to');
yesno_list_cells(_("Status Type:"), 'status_type', '',_("For Approval"), _("Approved"));
submit_cells('search', 'Search', "", false, false);
end_table(2);
div_end();
div_end();

div_start('dm_list');



$all_branch = array();
$running_total = array();

	$sql = "SELECT * FROM centralized_returned_merchandise.".TB_PREF."rms_header";

	if ($_POST['rs_id'] == '')
	{
		$sql .= " WHERE rs_date >= '".date2sql($_POST['rs_date_from'])."'
				  AND rs_date <= '".date2sql($_POST['rs_date_to'])."'";
				  
		if ($_POST['supplier_code'] != '')
			$sql .= " AND supplier_code = ".db_escape($_POST['supplier_code']);
			
		if ($_POST['status_type']==1)
		{
		//Open
		$stats='0' ;
		$sql .= "  AND movement_no = 0";	
		}
		else {
		//Posted
		$stats='2';
		$sql .= "  AND movement_no!= ''";	
		}

		if ($_POST['status_type']!='')
		{	
			if ($stats == 0) {
				$sql .= "  AND approved = '$stats' OR approved = 1";	
			}
			else {
				$sql .= "  AND approved = '$stats'";
			}
		}
					
		$sql .= " AND movement_type='FDFB' AND processed = '1'";		
			
	}
	else		  
	{
		$sql .= " WHERE (rs_id = ".$_POST['rs_id']." 
				  OR movement_no = ".$_POST['rs_id'].")";
		
		if($prevent_duplicate) // pending only
		$sql .= " AND processed = 0";		
		$_POST['status'] == 2;
	}
	$sql .= " ORDER BY rs_id";
	
	$res = db_query($sql);


// display_error($sql);
// exit;


start_table($table_style2.' width=90%');
$th =array('#', 'Branch', 'SA to BO Date', 'Supplier', 'Extended', 'Created by', 'Processed by','Remarks', 'Status', 'Operations '.'<input type="checkbox" id="post_all">', 'GM '.'<input type="checkbox" id="approve_all">');
		
//array_push($th, 'Date Created', 'TransNo','MovementID','From Location','To Location', 'Created By', 'Date Posted', 'Posted By', 'Status','','','');

if (db_num_rows($res) > 0)
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$k = 0;

$u = get_user($_SESSION["wa_current_user"]->user);
$approver= $u['user_id'];

$myBranchCode=$db_connections[$_SESSION["wa_current_user"]->company]["br_code"];
//display_error($approver);

// echo "<pre>"; print_r($all_branch); echo "</pre>";

while ($row = mysql_fetch_assoc($res)) {
	alt_table_row_color($k);
	label_cell(get_rs_view_str_per_branch($row['rs_id'],'# '.$row['rs_id'], $row['branch_id']));
	label_cell($row['branch_name']);
	label_cell(sql2date($row['rs_date']));
	label_cell(get_ms_supp_name($row['supplier_code']));
	// $total = get_rs_items_total($row['rs_id'], $row['branch_id']);
	$total = $row['items_total'];
	label_cell(number_format2($total,3),'align=right');
	label_cell(get_username_by_id_($row['created_by'], $row['branch_id']));
	label_cell(get_username_by_id_($row['processed_by'], $row['branch_id']));
	label_cell($row['comment']);
	
	if ($row['approved'] == 0) {
		label_cell('For Approval');
	}
	else {
		label_cell("Approved by ".get_username_by_id($row['approved_by_aria_user']));
	}


	if ($row['temp_posted'] == 0) {
		if (is_divi($row['supplier_code'])) {
			if (!empty($purchaser_vendors)) {
				if (in_array($row['supplier_code'], $purchaser_vendors)) {
					echo '<td align="center">
						<input type="hidden" name="bid_'.$row['rs_id'].'" value="'.$row['branch_id'].'" />
						<input type="checkbox" class="post_one" name="post_id[]" value="'.$row['rs_id'].'" />
					</td>';			
				}
				else {
					label_cell('<i>Not your Supplier. For approval of another Purchaser</i>','align=center');
				}
			}
			else {
				label_cell('<i>For Approval of Purchaser</i>','align=center');
			}
		}
		elseif (is_operations($_SESSION['wa_current_user']->user) || $_SESSION["wa_current_user"]->user="admin") {
			echo '<td align="center">
				<input type="hidden" name="bid_'.$row['rs_id'].'" value="'.$row['branch_id'].'" />
				<input type="checkbox" class="post_one" name="post_id[]" value="'.$row['rs_id'].'" />
			</td>';
		}
		else {
			label_cell('<i>Pending</i>','align=center');
		}
	}
	elseif ($row['temp_posted'] == 1) {
		label_cell('<i>Approved (Temporarily)</i>','align=center');
	}
	else {
		label_cell('<i>Approved</i>','align=center');
	}



	if ($row['temp_posted'] == 0) {
		if (is_divi($row['supplier_code'])) {
			label_cell('<i>For Approval of Puchaser</i>','align=center');
		}
		else {
			label_cell('<i>For Approval of Operations</i>','align=center');
		}
	}
	elseif ($row['temp_posted'] == 2 && $row['approved'] == 0) {
		if ($_SESSION["wa_current_user"]->user == 'admin' || $approver == '6666' || $_SESSION['wa_current_user'] == '606' || $_SESSION['wa_current_user']->username == 'jenc') {
			echo '<td align="center">
				<input type="hidden" name="bid_'.$row['rs_id'].'" value="'.$row['branch_id'].'" />
				<input type="checkbox" class="approve_one" name="approve_id[]" value="'.$row['rs_id'].'" />
			</td>';	
		}
		else {
			label_cell('<i>Pending</i>','align=center');
		}
	}
	elseif ($row['temp_posted'] == 1 && $row['approved'] == 0) {
		if (is_divi($row['supplier_code'])) {
			label_cell('<i>For Approval of Puchaser</i>','align=center');
		}
		else {
			label_cell('<i>For Approval of Operations</i>','align=center');
		}

	}
	elseif ($row['approved'] == 1) {
		label_cell('<i>Approved (Temporarily)</i>', 'align=center');
	}
	else {
		label_cell('<i>Approved</i>', 'align="center"');
	}
	

	end_row();
}
end_table();
br();
br();
div_end();

echo '<center style="margin: 10px 0;">
		<button class="inputsubmit" type="submit" id="approve_all_selected" name="approve_all_selected" style="display: none;">
			<span>Approve Selected</span>
		</button>
	</center>';

end_form();
end_page();
?>