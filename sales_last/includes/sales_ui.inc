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
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/banking.inc");
// include_once($path_to_root . "/sales/includes/ui/invoice_ui.inc");
//include_once($path_to_root . "/sales/includes/ui/po_ui.inc");
//include_once($path_to_root . "/sales/includes/ui/grn_ui.inc");
//----------------------------------------------------------------------------
// helper functions for script execution control
//

function processing_start()
{
    processing_end();
    $_SESSION['Processing'] = $_SERVER['PHP_SELF'];
}

function processing_end()
{
    unset($_SESSION['Processing']);
    if ( isset($_SESSION['Items']) ) {
		unset($_SESSION['Items']->line_items);
		unset($_SESSION['Items']);
    }
}

function processing_active()
{
    return (isset($_SESSION['Processing']) && $_SESSION['Processing']==$_SERVER['PHP_SELF']);
}
/*
	Check if the cart was not destroyed during opening the edition page in
	another browser tab.
*/
function check_edit_conflicts($cartname='Items')
{
	global $Ajax;
	
	if (isset($_POST['cart_id']) && $_POST['cart_id'] != $_SESSION[$cartname]->cart_id) {
		display_error(_('This edit session has been abandoned by opening sales document in another browser tab. You cannot edit more than one sales document at once.'));
		$Ajax->activate('_page_body');
		display_footer_exit();
	}
}

function getSONum($transNo){
	$sql = "SELECT reference FROM 0_sales_orders
				WHERE order_no = $transNo";
	$query = db_query($sql);
	
	if(mysql_num_rows($query) > 0){
		$row = mysql_fetch_object($query);
		return $row->reference;
	}else{
		return 0;
	}
}

function getReferencebyType($transNo, $type){
	switch($type){
		case 'DR': $tid = 13;
			break;
		case 'INV': $tid = 10;
			break;
		case 'CN': $tid = 11;
			break;
		case 'PMT': $tid = 12;
			break;
	}

	$sql = "SELECT reference FROM 0_debtor_trans
				WHERE trans_no = $transNo AND type = $tid";
	$query = db_query($sql);
	
	if(mysql_num_rows($query) > 0){
		$row = mysql_fetch_object($query);
		return $row->reference;
	}else{
		return 0;
	}
}

function getDRINV($so, $type){
	switch($type){
		case 'DR': $tid = 13;
			break;
		case 'INV': $tid = 10;
			break;
		case 'CN': $tid = 11;
			break;
		case 'PMT': $tid = 12;
			break;
	}
	
	$sql = "SELECT reference FROM 0_debtor_trans
				WHERE order_ = $so AND type = $tid";
	$query = db_query($sql);
	
	if(mysql_num_rows($query) > 0){
		$row = mysql_fetch_object($query);
		return $row->reference;
	}else{
		return 0;
	}
}

function getSORef($order){
	$sql = "SELECT reference
				FROM 0_sales_orders
				WHERE order_no = $order
				AND trans_type = 30";
	$query = db_query($sql);
	if(db_num_rows($query) > 0){
		$res = mysql_fetch_object($query);
		return $res->reference;
	}else{
		return 0;
	}
}

function getPayRef($trans, $type){
	$sql = "SELECT reference
				FROM 0_debtor_trans
				WHERE trans_no = $trans
				AND type = $type";
	$query = db_query($sql);
	if(db_num_rows($query) > 0){
		$res = mysql_fetch_object($query);
		return $res->reference;
	}else{
		return 0;
	}
}

function getSObyDtrans($trans, $type){
	$sql = "SELECT order_
				FROM 0_debtor_trans
				WHERE trans_no = $trans
				AND type = $type";
	$query = db_query($sql);
	if(db_num_rows($query) > 0){
		$res = mysql_fetch_object($query);
		return $res->order_;
	}else{
		return 0;
	}
}

function getBAccountType($bank){
	$sql = "SELECT account_type
				FROM 0_bank_accounts
				WHERE id = $bank";
	$query = db_query($sql);
	if(db_num_rows($query) > 0){
		$res = mysql_fetch_object($query);
		return $res->account_type;
	}else{
		return 0;
	}
}

?>