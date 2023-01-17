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
$page_security = 'SA_INVENTORYADJUSTMENT';
$path_to_root = "..";
include_once($path_to_root . "/includes/ui/items_cart.inc");

include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/inventory/includes/all_item_adjustments_ui.inc");
include_once($path_to_root . "/inventory/includes/db/all_item_adjustments_db.inc");
$js = "";
$js .= "



$(document).ready(function(){
	$('.limitLink').trigger('click');
})

function callSubmitButton(){
	$('#Process').trigger('click');
}

		jQuery.prompt.setDefaults({
			show: 'slideDown'
			,top: '20%'
		});
		
		var msg = '<table>'+
					  '<tr>The transaction contains item(s) that exceeds to inventory balance.Please enter supervisor\'s account for approval.<br></tr>'+
					  '<tr><td>Username: </td><td><input type=\"text\" id=\"uname\" name=\"uname\"></td></tr>'+
					  '<tr><td>Password: </td><td><input type=\"password\" id=\"passwd\" name=\"passwd\"></td></tr>'+
					  '</table>';
					  
		var hid_chk_credit = $('input[name=\"hid_chk_credit\"]').val();
		var hid_debtor_no = $('input[name=\"debtor_no11\"]').val();
		var errpro = 'Invalid supervisor user account. Please try again.';
		var sapp = 'Action approved! You can now proceed with the transaction.';
		//alert(hid_debtor_no);
		
		function post_form(val,ev,f,ty){
		//alert(val+ ' - '+ev+ ' - '+f+' - '+ty);
	//	alert(ty);
			if(val==true)
				if(f.uname!='' && f.passwd!=''){
					$.post('confirm.php',{ 'uname':f.uname,'passwd':f.passwd,'noti_':1,'ty':ty },
						function(ev){
							if(ev==true){
								$.prompt(sapp,{
								
									buttons: { Ok:true },
									callback: callSubmitButton
								});
								$('input[name=\"hid_chk_credit\"]').attr('value',0);
																
								$(\"input[name='credit_']\").val(1);
								$(\"input[name='hid_debtor_no']\").val(hid_debtor_no);
								
								$.post('confirm.php',{ 'credit_':1, 'hid_debtor_no':hid_debtor_no },
								function(ev){
									//alert(ev);
								});				
							}else{
								$.prompt(errpro);
							}
						});
				}else
					$.prompt(errpro);
		}
		
		if(hid_chk_credit==1)
		$.prompt(msg,{
				buttons: { Ok:true, Cancel:false },
				callback: post_form
			});

		function openprompt(ty){
			$.prompt(msg,{
					buttons: { Ok:true, Cancel:false },
					callback: post_form
				},ty);
		}	
	
//	});
	
	
";


if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Item Adjustment"), false, false, "", $js);

//-----------------------------------------------------------------------------------------------

check_db_has_costable_items(_("There are no inventory items defined in the system which can be adjusted (Purchased or Manufactured)."));

check_db_has_movement_types(_("There are no inventory movement types defined in the system. Please define at least one inventory adjustment type."));

//-----------------------------------------------------------------------------------------------

if (isset($_GET['AddedID'])) 
{
	$trans_no = $_GET['AddedID'];
	$trans_type = ST_INVADJUST;

	display_notification_centered(_("Item adjustments has been processed"));
	
	//display_note(get_trans_view_str($trans_type, $trans_no, _("&View this adjustment")));

	display_note(get_gl_view_str($trans_type, $trans_no, _("View the GL &Postings for this Adjustment")), 1, 0);

	hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another Adjustment"));

	display_footer_exit();
}
//--------------------------------------------------------------------------------------------------

function line_start_focus() {
  global $Ajax;

  $Ajax->activate('items_table');
      	
		$_POST['qty'] = '';
		$_POST['std_cost']='';
		$_POST['stock_id']='';
		$_POST['item_description']='';
		$_POST['b_code']='';
		$_POST['barcode']='';
		$_POST['units']='';
  
  set_focus('_barcode_edit');
}
//-----------------------------------------------------------------------------------------------

function handle_new_order()
{
	if (isset($_SESSION['adj_items']))
	{
		$_SESSION['adj_items']->clear_items();
		unset ($_SESSION['adj_items']);
	}

    //session_register("adj_items");

    $_SESSION['adj_items'] = new items_cart(ST_INVADJUST);
	$_POST['AdjDate'] = new_doc_date();
	if (!is_date_in_fiscalyear($_POST['AdjDate']))
		$_POST['AdjDate'] = end_fiscalyear();
	$_SESSION['adj_items']->tran_date = $_POST['AdjDate'];	
}

//-----------------------------------------------------------------------------------------------

function can_process()
{
	global $Refs;

	$adj = &$_SESSION['adj_items'];

	if (count($adj->line_items) == 0)	{
		display_error(_("You must enter at least one non empty item line."));
		set_focus('stock_id');
		return false;
	}
	if (!$Refs->is_valid($_POST['ref'])) 
	{
		display_error( _("You must enter a reference."));
		set_focus('ref');
		return false;
	}

	// if ((!is_new_reference($_POST['ref'], ST_INVADJUST)) AND ($_POST['get_trans_no']!=''))
	// {
		// display_error( _("The entered reference is already in use."));
		// set_focus('ref');
		// return false;
	// }
	
		if ($_POST['type']=='') 
	{
		display_error(_("You must select Movement type."));
		set_focus('type');
		return false;
	} 

	if (!is_date($_POST['AdjDate'])) 
	{
		display_error(_("The entered date for the adjustment is invalid."));
		set_focus('AdjDate');
		return false;
	} 
	elseif (!is_date_in_fiscalyear($_POST['AdjDate'])) 
	{
		display_error(_("The entered date is not in fiscal year."));
		set_focus('AdjDate');
		return false;
	} else {
		
		// display_error($_SESSION['allownegativecost']);
		if($_SESSION['allownegativecost']==1)
			return true;
	}
	return true;
}

//-------------------------------------------------------------------------------

if (isset($_POST['Process']) && can_process()){

	$trans_no = add_adjustment_details($_SESSION['adj_items']->line_items, $_POST['AdjDate'],$_POST['type'],$_POST['status_type'],
		$_POST['ref'], $_POST['memo_'],$_POST['get_trans_no']);
	new_doc_date($_POST['AdjDate']);
	$_SESSION['adj_items']->clear_items();
	
	unset($_SESSION['allownegativecost']);
	unset($_SESSION['adj_items']);

  meta_forward($_SERVER['PHP_SELF'], "AddedID=$trans_no");
} /*end of process credit note */

//-----------------------------------------------------------------------------------------------

function check_item_data()
{
	if ($_POST['qty']==0)
	{
		display_error(_("The quantity entered is negative or invalid."));
		set_focus('qty');
		return false;
	}
	
	if (!check_value('units') or $_POST['units']=='')
	{
		display_error(_("The units must be entered."));
		set_focus('units');
		return false;
	}
	
	
	
	if (!check_num('std_cost', 0))
	{
		display_error(_("The entered standard cost is negative or invalid."));
		set_focus('std_cost');
		return false;
	}

   	return true;
}

//-----------------------------------------------------------------------------------------------

function handle_update_item()
{
    if($_POST['UpdateItem'] != "" && check_item_data())
    {
		$id = $_POST['LineNo'];
    	$_SESSION['adj_items']->update_cart_item($id, input_num('qty'),$_POST['units'], input_num('std_cost'),$_POST['b_code']);
    }
	line_start_focus();
}

//-----------------------------------------------------------------------------------------------

function handle_delete_item($id,$get_trans_no)
{
//display_error($get_trans_no);

$type=ST_INVADJUST;	
	$sql = "DELETE FROM ".TB_PREF."stock_moves WHERE type='$type' AND trans_no='$get_trans_no' and stock_id='".$_SESSION['adj_items']->line_items[$id]->stock_id."'";
	//display_error($sql);
	db_query($sql);
	
$_SESSION['adj_items']->remove_from_cart($id);
line_start_focus();
}
//-----------------------------------------------------------------------------------------------

function handle_new_item()
{
	if (!check_item_data())
		return;

	add_to_order($_SESSION['adj_items'], $_POST['stock_id'], input_num('qty'), $_POST['units'], input_num('std_cost'), $_POST['item_description'],$_POST['b_code']);
	line_start_focus();
}


function handle_new_open_item($get_trans_no)
{
$gl_type=ST_INVADJUST;
$sql = "SELECT * from ".TB_PREF."stock_moves WHERE type='$gl_type' AND trans_no = '".$get_trans_no."'";
$res = db_query($sql);
//display_error($sql);

while($row=mysql_fetch_array($res)) {
	add_to_order($_SESSION['adj_items'], $row['stock_id'], $row['qty'], $row['i_uom'],  $row['standard_cost'],$description='',$row['barcode']);
	}
	line_start_focus();
}

//----------------------------------------------------------------------------------------------
$id = find_submit('Delete');
if ($id != -1)
	handle_delete_item($id,$_POST['ref']);

if (isset($_POST['AddItem']))
	handle_new_item();

if (isset($_POST['UpdateItem']))
	handle_update_item();

if (isset($_POST['CancelItemChanges'])) {
	line_start_focus();
}
if (isset($_GET['trans_no'])) 
{
handle_new_order();
handle_new_open_item($_GET['trans_no']);
}

//-----------------------------------------------------------------------------------------------

if ((isset($_GET['NewAdjustment']) &&  !isset($_GET['trans_no'])) || !isset($_SESSION['adj_items']))
{
	handle_new_order();
	unset($_SESSION['allownegativecost']);
}

//-----------------------------------------------------------------------------------------------
start_form();

display_order_header($_SESSION['adj_items'],$_GET['trans_no']);

start_outer_table("$table_style width=75%", 10);
display_adjustment_items(_("Items to Adjust"), $_SESSION['adj_items']);
adjustment_options_controls();
end_outer_table(1, false);

//submit_center_first('Update', _("Update"), '', null);
//submit_center_first('Process', _("Process"), '', false);
submit_center('Process', 'Process', "align=center", true, true,'ok.gif');
end_form();
end_page();

?>