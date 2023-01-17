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
//-----------------------------------------------------------------------------------------
//	Returns next transaction number.
//	Used only for transactions stored in tables without autoincremented key.
//
function get_next_trans_no ($trans_type){

	$st = get_systype_db_info($trans_type);

	if (!($st && $st[0] && $st[2])) {
		// this is in fact internal error condition.
		display_error('Internal error: invalid type passed to get_next_trans_no()');
		return 0;
	}
	$sql = "SELECT MAX(`$st[2]`) FROM $st[0]";

	if ($st[1] != null)
		 $sql .= " WHERE `$st[1]`=$trans_type";

    $result = db_query($sql,"The next transaction number for $trans_type could not be retrieved");
    $myrow = db_fetch_row($result);

    return $myrow[0] + 1;
}

function trade_non_trade_inv($type, $trans_no)
{
	if ($type != 20)
		return '';
		
	// $sql = "SELECT * FROM ".TB_PREF."supp_invoice_items WHERE supp_trans_no = $trans_no AND supp_trans_type = 20 AND gl_code = 0"; // get 
	// $res = db_query($sql);
	// if (db_num_rows($res) > 0)
		// return ' (Trade)'; //trade;
	// return ' (Non-Trade)'; //non-trade
	
	$sql = "SELECT reference FROM ".TB_PREF."supp_trans WHERE type = $type AND trans_no = $trans_no";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if (strpos($row[0],'NT') === false)
		return ' (Trade)'; //trade;
		
	return ' (Non-Trade)'; //non-trade
	
}
//-----------------------------------------------------------------------------
function get_systype_db_info($type)
{
	switch ($type)
	{
        case     ST_JOURNAL      : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
        case     ST_SALES      : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
        case     ST_STALEDCHECKS      : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
        case     ST_OUTSTANDINGCHECKS      : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
        case     ST_BANKPAYMENT  : return array("".TB_PREF."bank_trans", "type", "trans_no", "ref", "trans_date");
		case     ST_BANKDEPOSIT  : return array("".TB_PREF."bank_trans", "type", "trans_no", "ref", "trans_date");
        case     ST_OR  	: return array("".TB_PREF."other_income_payment_header", "bd_trans_type", "bd_trans_no", "bd_reference", "bd_trans_date");
        case     3               : return null;
        case     ST_BANKTRANSFER : return array("".TB_PREF."bank_trans", "type", "trans_no", "ref", "trans_date");
        case     ST_SALESINVOICE : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CUSTCREDIT   : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CUSTPAYMENT  : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CUSTDELIVERY : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_LOCTRANSFER  : return array("".TB_PREF."stock_moves", "type", "trans_no", "reference", "tran_date");
		case     ST_ITEM_TRANSFORMATION  : return array("".TB_PREF."transformation_header", "a_type", "a_id", "a_id", "a_date_created");
		case     ST_SAKUSINAOUT  : return array("".TB_PREF."stock_moves", "type", "trans_no", "reference", "tran_date");
		case     ST_SAKUSINAIN   : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
       // case     ST_INVADJUST    : return array("".TB_PREF."stock_moves", "type", "trans_no", "reference", "tran_date");
        case     ST_INVADJUST    : return array("".TB_PREF."adjustment_header", "a_type", "a_id", "a_id", "a_date_created");
        case     ST_PURCHORDER   : return array("".TB_PREF."purch_orders", null, "order_no", "reference", "tran_date");
        case     ST_SUPPINVOICE  : return array("".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_SUPPCREDIT   : return array("".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_SUPPAYMENT   : return array("".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CWODELIVERY  : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
        case     ST_SUPPRECEIVE  : return array("".TB_PREF."grn_batch", null, "id", "reference", "delivery_date");
        case     ST_WORKORDER    : return array("".TB_PREF."workorders", null, "id", "wo_ref", "released_date");
        case     ST_MANUISSUE    : return array("".TB_PREF."wo_issues", null, "issue_no", "reference", "issue_date");
        case     ST_MANURECEIVE  : return array("".TB_PREF."wo_manufacture", null, "id", "reference", "date_");
        case     ST_SALESORDER   : return array("".TB_PREF."sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case     31              : return array("".TB_PREF."service_orders", null, "order_no", "cust_ref", "date");
        case     ST_SALESQUOTE   : return array("".TB_PREF."sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case	 ST_DIMENSION    : return array("".TB_PREF."dimensions", null, "id", "reference", "date_");
        case     ST_COSTUPDATE   : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		
		case     ST_CUSTDEBITMEMO   : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_CUSTCREDITMEMO  : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_SUPPDEBITMEMO   : return array("".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_SUPPCREDITMEMO  : return array("".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_SUPPDMAR        : return array("".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_ALLOCATION      : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_OTHERINCOME     : return array("".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case   	 ST_CASHDEPOSIT     : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		###rhan###
		case     ST_INV_GAIN_SA        : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case     ST_INV_GAIN_BO        : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case     ST_INV_LOSS_SA        : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case     ST_INV_LOSS_BO        : return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_DIS_BO 			: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_POS_ADJ_SA 		: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_POS_ADJ_BO 		: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_NEG_ADJ_SA 		: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_NEG_ADJ_BO 		: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_PRO_BUNDLING 	: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_KUS_IN 			: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_KUS_OUT 		: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_STR_NEG_ADJ 	: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_STR_POS_ADJ 	: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_SW_POS_ADJ 		: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		case 	 ST_SW_NEG_ADJ 		: return array("".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		###rhan###

	}

	display_db_error("invalid type ($type) sent to get_systype_db_info", "", true);
}

function get_systypes()
{
	$sql = "SELECT * FROM ".TB_PREF."sys_types";
	$result = db_query($sql, "could not query systypes table");
	return $result;
}
?>