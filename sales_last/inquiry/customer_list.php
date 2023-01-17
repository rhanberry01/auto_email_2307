<?php

$page_security = 'SA_SALESTRANSVIEW';
$path_to_root = "../..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
$js = "";
echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	
	<link href='$path_to_root/js/jquery-ui.css'>
	<link rel='stylesheet' href='$path_to_root/js/thickbox.css' type='text/css' media='screen' />
";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Customer List with Balances"), false, false, "", $js);


function item_class($row){
	if(is_manufactured($row['mb_flag'])) return "Manufactured";
	else if(is_service($row['mb_flag'])) return "Service";
	else if(is_purchased($row['mb_flag'])) return "Purchased";
}

function is_chem($row){
	if($row['is_chemical']) return 'Yes';
	else return 'No';
}
function viewr($row){
	return viewer_link($row['stock_id'],"inventory/inquiry/item_details.php?stock_id=".$row['stock_id']);
}

function chex($row){

	return "<input type=checkbox name=chex[$row[debtor_no]] value=1 class=chcktbl>";
	//return viewer_link($row['stock_id'],"inventory/inquiry/item_details.php?stock_id=".$row['stock_id']);
}

if(isset($_POST['process'])){
	//display_error('test');
	$debtor_array=array();
	foreach($_POST['chex'] as $c=>$x){
		$debtor_array[]=$c;	
	}
	//$debtor_array=implode(",",$debtor_array);
	if(count($debtor_array)==0)
	display_error("You must select at least one customer.");
	else
	echo "<a target='_blank' id='hiddenLink' style='display:none' href='".$path_to_root."/reporting/prn_redirect.php?PARAM_0=".begin_fiscalyear()."&PARAM_1=".end_fiscalyear()."&PARAM_2=".rawurlencode(serialize($debtor_array))."&PARAM_3=PHP&PARAM_4=0&PARAM_5=&PARAM_6=0&REP_ID=101'>You can't see me.</a>";

}
start_form();
submit_center('process',"Display: Statements of Account",true,false,false,false,false);

br();
/*
start_table("cellspacing=10 width=75%");
start_row();

text_cells_ex("Item Code: ",'stock_id',25);

echo "<td align=right>";
submit_center_first('Search', 'Search', false, true);
end_table(2);*/


$sql = "SELECT 
	d.debtor_ref, 
	d.name,
	d.address,
	terms.terms,
	d.credit_limit,
	d.debtor_no
	FROM "
		.TB_PREF."debtors_master as d, "
		.TB_PREF."payment_terms as terms
	WHERE d.payment_terms = terms.terms_indicator
	AND d.debtor_no IN(SELECT DISTINCT debtor_no FROM ".TB_PREF."debtor_trans WHERE type!=".ST_CUSTDELIVERY.")";
	
$sql .= " ORDER BY name";

$result = db_query($sql,"No orders were returned");

/*show a table of the orders returned by the sql */
$cols = array(
		_("Customer Ref") => array('ord'=>''),
		_("Customer Name"),
		_("Address"),
		_("Payment Terms"), 
		_("Credit Limit") => array('ord'=>'','type'=>'amount'), 
		// "<a onclick=javascript:checkAll(true) style='cursor:pointer;'>[]</a><a onclick=javascript:checkAll(false) style='cursor:pointer;'>[ ]</a>" => array('fun'=>'chex')
		checkbox('', "checkall' id='checkall", null, false, false) => array('fun'=>'chex', 'align'=>center)
);
//display_error($sql);
$table =& new_db_pager('orders_tbl', $sql, $cols,null,null,999);
//$table->set_marker('check_overdue', _("Marked orders have overdue items."));

$table->width = "90%";

display_db_pager($table);
br();
//submit_center('process',"Display: Statements of Account");

end_form();

end_page();
?>

<style type="text/css">
select{width:15em;}
</style>

<script>
$(document).ready(function(){
	
  if ($('#hiddenLink').attr('href')) {
               window.open($('#hiddenLink').attr('href'),"_blank");
  	
  }


	// function checkAll(allx){
	
		
			// var docx=document.forms[0];
			// //alert(docx);
			// for (var i = docx.length - 1; i >= 0; i--) {
				// var lelz=docx.elements[i].name;
				// var lelz2=lelz.substring(0,4);
			
				// if(lelz2=='chex') eval("document.forms[0].elements["+i+"].checked="+allx);
			// };
		

	// }
	
	$('#checkall').click(function () {
		if (this.checked == false) {
			$('.chcktbl:checked').attr('checked', false);
		}
		else {
			$('.chcktbl:not(:checked)').attr('checked', true);
}
	});
	

});
</script>