<?php
$page_security = 2;
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Karen
// date_:	2005-05-19
// Title:	Receive
// ----------------------------------------------------------------

$page_security = $_POST['PARAM_0'] == $_POST['PARAM_1'] ?
	'SA_MANUFTRANSVIEW' : 'SA_MANUFBULKREP';
	
$path_to_root="../";

include_once($path_to_root . "includes/session.inc");
include_once($path_to_root . "includes/date_functions.inc");
include_once($path_to_root . "includes/data_checks.inc");
//include_once($path_to_root . "sales/includes/sales_db.inc");

$receive_id = $_GET['id'];

//----------------------------------------------------------------------------------------------------
function get_po($order_no)
{
   	$sql = "SELECT ".TB_PREF."purch_orders.*, ".TB_PREF."suppliers.supp_name,
   		".TB_PREF."suppliers.curr_code, ".TB_PREF."suppliers.payment_terms, ".TB_PREF."locations.location_name,
   		".TB_PREF."suppliers.email, ".TB_PREF."suppliers.address
		FROM ".TB_PREF."purch_orders, ".TB_PREF."suppliers, ".TB_PREF."locations
		WHERE ".TB_PREF."purch_orders.supplier_id = ".TB_PREF."suppliers.supplier_id
		AND ".TB_PREF."locations.loc_code = into_stock_location
		AND ".TB_PREF."purch_orders.order_no = " . $order_no;
   	$result = db_query($sql, "The order cannot be retrieved");
    return db_fetch($result);
	//$rep->TextCol(4, 5,	$sql, -2);
}

function get_po_details($order_no)
{
	$sql = "SELECT ".TB_PREF."purch_order_details.*, units
		FROM ".TB_PREF."purch_order_details
		LEFT JOIN ".TB_PREF."stock_master
		ON ".TB_PREF."purch_order_details.item_code=".TB_PREF."stock_master.stock_id
		WHERE order_no =$order_no ";
	$sql .= " ORDER BY po_detail_item";
	return db_query($sql, "Retreive order Line Items");
}
function get_series($po_no){
	$series=db_fetch(db_query("SELECT series_no FROM 0_purch_orders WHERE order_no=".$po_no));
	return $series[0];
}
/*************************************/
	global $path_to_root;
	include_once($path_to_root . "reporting/includes/pdf_report.inc");
	$rep = new FrontReport(_('Receive Form'), "ReceiveForm.pdf", 'A4',9,'L');

	$sql = "SELECT * FROM ".TB_PREF."grn_batch WHERE id=".$receive_id;
	$get_grn = db_query($sql);
	while($row = db_fetch($get_grn)){
	$supp=get_supplier($row['supplier_id']);
	$address=get_supp_location($row['loc_code']);
		
		
	
	//==================================================================================================//
	$rep->Line($rep->bottomMargin);
	$rep->Line($rep->pageHeight - $rep->topMargin);
	$rep->LineTo($rep->leftMargin, $rep->bottomMargin, $rep->leftMargin, $rep->pageHeight - $rep->topMargin);
	$rep->LineTo($rep->pageWidth - $rep->rightMargin, $rep->bottomMargin, $rep->pageWidth - $rep->rightMargin, $rep->pageHeight - $rep->topMargin);
	
	$rep->Font('bold');
	$rep->fontSize = 18;
	
	
	$rep->TextWrap(0,545,$rep->pageWidth,'Airport House of Wines.','center');
	
	$rep->Font('bold');
	$rep->fontSize = 11;	
	$rep->TextWrap(0,530,$rep->pageWidth, 'RECEIVING FORM','center');
	
	
	
	$rep->Font('');
	$rep->fontSize = 10;
	
	$rep->TextWrap($rep->leftMargin+5,510,592,'Supplier : '.$supp['supp_name'],'left');
	//$rep->TextWrap($rep->leftMargin+5,510-10,592,'Address : '.$supp['address'],'left');
	$rep->TextWrap($rep->leftMargin+5,510-45,692,'Deliver to (Warehouse) : '.$address['location_name'],'left'); 
	$rep->TextWrap($rep->leftMargin+25,510-55,692, $address['delivery_address'],'left');

	$rep->Font('bold');
	$rep->TextWrap(580,510,178,'R.R No.: '.$head['rr_no'],'left');
	$rep->TextWrap(580,510-10,178,'R.R Date : '.$head['rr_date'],'left');
	$rep->TextWrap(580+5,510-20,692,'P.O No. : '.$row['purch_order_no'],'left');

	
	
	
	
	$rep->Font('');
	
	$rep->Line(440);
	$rep->Line(440-12);
		$rep->fontSize=9;

	$rep->TextWrap(40,430,110,'Item Code','center'); 
	$rep->LineTo(145,440,145,75);
	
	$rep->TextWrap(150,430,300,'Item Description','center'); 

	$rep->LineTo(515,440,515,75);
	
		$rep->TextWrap(512,430,40,'R.R Qty','center'); 
	$rep->LineTo(550,440,550,75);
	
		$rep->TextWrap(550,430,25,'Unit','center'); 
	$rep->LineTo(575,440,575,75);
	
		$rep->TextWrap(600,430,50,'Unit Price','center'); 
	$rep->LineTo(666,440,666,75);
	
		$rep->TextWrap(690,430,50,'Amount','center'); 
			
		$sql_details = "SELECT * FROM ".TB_PREF."grn_items WHERE grn_batch_id=".$row['id'];
		$go_details = db_query($sql_details);
			$yy=400;

		while($field = db_fetch($go_details))
		{
		$desc=db_fetch(db_query("SELECT * from 0_stock_master where stock_id='$field[item_code]'"));
		$po_details = db_fetch(db_query("SELECT * FROM ".TB_PREF."purch_order_details WHERE po_detail_item=".$field['po_detail_item']));

		
		$rep->TextWrap(40,$yy+15,110,$field['item_code'],'left'); 
		$rep->TextWrap(150,$yy+15,300,$desc['description'],'left'); 
		//$rep->TextWrap(455,$yy+15,40,$field['se_qty'],'right');
		$rep->TextWrap(495,$yy+15,40,$field['qty_recd'],'right');
		$rep->TextWrap(554,$yy+15,25,$desc['units'],'left');
		$rep->TextWrap(585,$yy+15,75,number_format2($po_details["unit_price"],2),'right');
		$line_total = $po_details["unit_price"] * $field['qty_recd'];
		$rep->TextWrap(655,$yy+15,100,number_format2(($line_total),2),'right');
		$yy-=25;	
		//$total+=$a["unit_price"]*$a["quantity_ordered"];
		
		}
	$totalAmount = db_fetch(db_query("SELECT SUM(quantity_received*unit_price)  FROM ".TB_PREF."purch_order_details WHERE order_no=".$row['purch_order_no']));}
	

	$rep->Line(75);
	$rep->Font('bold');
	$rep->fontSize=10;
	$rep->TextWrap(655,60,100,"Total: ".number_format2($totalAmount[0],2),'right');
	$rep->Font('');

	$rep->TextWrap(160,40,63,'Prepared by','center');
	$rep->TextWrap(400,40,63,'Approved by','center');
	//$sql='SELECT DISTINCT(supplier_id) FROM `0_receive_details` WHERE rr_no= 1';
	//$suppliers=db_query($sql);
		
//	$suppCount=0;
	/*while($rowSupp=db_fetch($suppliers)){
		if($suppCount>0)
			$rep->AddPage();
			$suppCount++;

	$sql='SELECT * FROM 0_receive WHERE rr_no = 1';
	$sql2="SELECT * FROM 0_receive_details WHERE supplier_id=$rowSupp[0] AND rr_no = 1"; //die($sql2);
	$sql3=db_query("SELECT DISTINCT po_no FROM 0_receive_details WHERE supplier_id=$rowSupp[0] AND rr_no = 1");
	$esql="SELECT DISTINCT po_no FROM 0_receive_details WHERE supplier_id=$rowSupp[0] AND rr_no = 1";
	//die($sql2);
	$totalAmount=db_fetch(db_query("SELECT sum(rr_amount) FROM 0_receive_details WHERE rr_no= 1"));
	$head=db_query($sql,'failed sql='.$sql);
	$details=db_query($sql2,'failed sql='.$sql2);
	$head=db_fetch($head);
	$details=db_fetch($details);
	
	
	$address=mysql_fetch_array(mysql_query("SELECT * FROM 0_locations WHERE loc_code='$details[deliver_to]'"));


	//==================================================================================================//
	$rep->Line($rep->bottomMargin);
	$rep->Line($rep->pageHeight - $rep->topMargin);
	$rep->LineTo($rep->leftMargin, $rep->bottomMargin, $rep->leftMargin, $rep->pageHeight - $rep->topMargin);
	$rep->LineTo($rep->pageWidth - $rep->rightMargin, $rep->bottomMargin, $rep->pageWidth - $rep->rightMargin, $rep->pageHeight - $rep->topMargin);
	
	$rep->Font('bold');
	$rep->fontSize = 18;
	
	$rep->TextWrap(0,550,$rep->pageWidth,'Airport House of Wines.','center');
	
	$rep->Font('bold');
	$rep->fontSize = 12;	
	$rep->TextWrap(0,530,$rep->pageWidth, 'RECEIVE FORM','center');
	
	$rep->Font('');
	$rep->fontSize = 10;
	
	$rep->TextWrap($rep->leftMargin+5,510,592,'Supplier : '.$supp['supp_name'],'left');
	$rep->TextWrap($rep->leftMargin+5,510-10,592,'Address : '.$supp['address'],'left');
	$rep->TextWrap($rep->leftMargin+5,510-40,692,'Deliver to (Warehouse) : '.$address['location_name'],'left'); 
	$rep->TextWrap($rep->leftMargin+25,510-55,692, $address['delivery_address'],'left');

	$rep->Font('bold');
	$rep->TextWrap(592+5,510,692,'R.R No.: '.$head['rr_no'],'left');
	//.$head['series_no']
	$rep->TextWrap(592+5,510-10,692,'R.R Date : '.$head['rr_date'],'left');
	if(mysql_num_rows($sql3)>1){
		while($a=db_fetch($sql3)){
			$pos.=get_series($a['po_no']).",";
		}
		$rep->TextWrap(592+5,510-20,692,'P.O No. : '.$pos,'left');
	}
	else
	$pow=mysql_fetch_row($sql3);
	$rep->TextWrap(592+5,510-20,692,'P.O No. : '.$pow[0],'left');
	
	$rep->Font('');
if($details['type']=='Foreign'){
	$rep->TextWrap(592+5,510-40,692,'Set Forex : ','left');
	$rep->TextWrap(592+5,510-50,692,'Actual Forex : ','left');
	$rep->TextWrap(592+5,510-60,692,'Cost Multiplier : ','left');
	}
	$rep->Font('');
	
	$rep->Line(440);
	$rep->Line(440-12);
		$rep->fontSize=9;

	$rep->TextWrap(40,430,110,'Item Code','center'); 
	$rep->LineTo(145,440,145,75);
	
	$rep->TextWrap(150,430,300,'Item Description','center'); 
if($details['type']=='Foreign'){		$rep->LineTo(475,440,475,75);

	$rep->TextWrap(475,430,40,'S.E Qty','center'); }
	$rep->LineTo(515,440,515,75);
	
		$rep->TextWrap(512,430,40,'R.R Qty','center'); 
	$rep->LineTo(550,440,550,75);
	
		$rep->TextWrap(550,430,25,'Unit','center'); 
	$rep->LineTo(575,440,575,75);
	
		$rep->TextWrap(600,430,50,'Unit Price','center'); 
	$rep->LineTo(666,440,666,75);
	
		$rep->TextWrap(690,430,50,'Line Total','center'); 
	
	$yy=400;
	$total=0;
	$rep->fontSize=8;
	$sql3=db_query($sql2);
	while($a=db_fetch($sql3))
	{
		$desc=db_fetch(db_query("SELECT main_description,units from 0_stock_master where stock_id='$a[item_code]'"));
		$rep->TextWrap(40,$yy+15,110,$a['item_code'],'left'); 
		$rep->TextWrapLines2(150,$yy+15,300,$desc[0],'left'); 
		$rep->TextWrap(455,$yy+15,40,$a['se_qty'],'right');
		$rep->TextWrap(495,$yy+15,40,$a['rr_qty'],'right');
		$rep->TextWrap(554,$yy+15,25,$desc[1],'left');
		$rep->TextWrap(585,$yy+15,75,number_format2($a["po_unit_price"],2),'right');
		$rep->TextWrap(655,$yy+15,100,number_format2(($a['rr_amount']),2),'right');
		$yy-=25;	
		$total+=$a["unit_price"]*$a["quantity_ordered"];
	}
	
	$rep->Line(75);
	$rep->Font('bold');
	$rep->fontSize=10;
	$rep->TextWrap(655,60,100,"Total: ".number_format2($totalAmount[0],2),'right');
		$rep->Font('');

		$rep->TextWrap(160,40,63,'Prepared by','center');
	$rep->TextWrap(400,40,63,'Approved by','center');
	}*/
	$rep->End();
	
	
	//==================================================================================================//


?>