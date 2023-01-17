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
ini_set('mssql.connect_timeout',0);
ini_set('mssql.timeout',0);
set_time_limit(0);
$page_security = 'SA_BANKTRANSFER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/gl/includes/gl_ui.inc");
include_once($path_to_root . "/includes/references.inc");
$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();
page(_($help_context = "Fix Consignment APV Dates"), false, false, "", $js);

//check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
//----------------------------------------------------------------------------------------	
				
if (isset($_GET['AddedID'])) {
	$trans_no = $_GET['AddedID'];
	//$cvid = $_GET['CV_id'];
	//$trans_type = ST_CREDITDEBITDEPOSIT;
   	display_notification_centered( _("Consignment has been fixed"));
	// display_note(get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries")));
	// br();
	// display_note(get_cv_view_str($cvid, _("View Transaction")));
   	// hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter &Another"));
	display_footer_exit();
}


//----------------------------------------------------------------------------------------
if (isset($_POST['Add'])){
	
	set_time_limit(0);
	
	$sql= "SELECT gl.type_no,gl.tran_date,gl.amount,st.reference,st.supp_reference,
	s.supp_name,cv.cv_no, cv.bank_trans_id,st.supplier_id,c.memo_ 
	FROM `0_gl_trans` as gl
	LEFT JOIN 0_supp_trans as st
	ON gl.type=st.type and gl.type_no=st.trans_no
	LEFT JOIN 0_cv_header as cv
	ON st.cv_id=cv.id
	LEFT JOIN 0_suppliers as s
	ON st.supplier_id=s.supplier_id
	LEFT JOIN 0_comments as c
	ON gl.type_no=c.id
	where gl.type=20
	and gl.account=2300
	and gl.tran_date>='2017-01-01'
	and c.memo_ like '%Sales from%'";
	$res=db_query($sql);
	display_error($sql.'****');

	while($row = db_fetch($res))
	{
		
		$memo_=substr($row['memo_'],0,36);
			
		$sql2= "SELECT gl.type_no,gl.tran_date,gl.amount,st.reference,st.supp_reference,
		s.supp_name,cv.cv_no, cv.bank_trans_id,st.supplier_id,c.memo_ 
		FROM `0_gl_trans` as gl
		LEFT JOIN 0_supp_trans as st
		ON gl.type=st.type and gl.type_no=st.trans_no
		LEFT JOIN 0_cv_header as cv
		ON st.cv_id=cv.id
		LEFT JOIN 0_suppliers as s
		ON st.supplier_id=s.supplier_id
		LEFT JOIN 0_comments as c
		ON gl.type_no=c.id
		where gl.type=20
		and gl.account=2000
		and gl.tran_date>='2016-01-01'
		and c.memo_ like '%".$memo_."%'
		and st.supplier_id='".$row['supplier_id']."'
		and gl.amount='".-$row['amount']."'
		";
		$res2=db_query($sql2);
		//display_error($sql2); 
			
			if ($row['type_no']!=''){
				
					

					while($row2 = db_fetch($res2))
					{
						$no[]=$row2['type_no'];
					}

					$no1=implode($no,",");

					$sql2_1 = "SELECT * FROM `0_supp_trans`
					where type=20
					and trans_no IN (
					$no1
					) ORDER BY cv_id desc";
					
					$res2_1=db_query($sql2_1);
					display_error($sql2_1);
					
					$count=mysql_num_rows ($res2_1);
					
					display_error($count);
					if($count==2){
						while($row2_1 = db_fetch($res2_1))
						{
							display_error('-START-');
							
							if(($row2_1['cv_id']!='' and $row2_1['cv_id']!=0) and $row2_1['tran_date'] >= '2017-01-01' ){
								$supp_reference=$row2_1['supp_reference'];
								$cv_id=$row2_1['cv_id'];
								display_error('supplier_id - '.$row2_1['supplier_id']);
								display_error('tran_date - '.$row2_1['tran_date']);
								display_error('ov_amount - '.$row2_1['ov_amount']);
								display_error('cv_id - '.$row2_1['cv_id']);
								
								//remove payment
									$sql_supp1 = "UPDATE ".TB_PREF."supp_trans 
									SET supp_reference='',
									cv_id=0,alloc=0,ov_amount=0
									WHERE type='20' and trans_no='".$row2_1['trans_no']."'";
									
									db_query($sql_supp1);
									display_error($sql_supp1);

								//void gl_trans	
									$sql_gl = "UPDATE ".TB_PREF."gl_trans 
									SET amount=0
									WHERE type='20' and type_no='".$row2_1['trans_no']."'";
									
									db_query($sql_gl);
									display_error($sql_gl);

								//insert_void
									$sql_void = "INSERT INTO ".TB_PREF."voided (type,id,date_,memo_)
											     VALUES (20,
											   		    ".$row2_1['trans_no'].",
											   		    '".date('Y-m-d')."',
											   		    'accrued voided amount:".$row2_1['ov_amount']."'
											   		   )";
									db_query($sql_void);
									display_error($sql_void);


								display_error('FIXED*');
							}
							
														
							if($row2_1['cv_id']=='' or $row2_1['cv_id']==0  and $row2_1['tran_date'] <='2017-01-01'){
								display_error('supplier_id - '.$row2_1['supplier_id']);
								display_error('tran_date - '.$row2_1['tran_date']);
								display_error('ov_amount - '.$row2_1['ov_amount']);
								display_error('cv_id - '.$row2_1['cv_id']);
								display_error('FIXED*');
								
									$sql_supp2 = "UPDATE ".TB_PREF."supp_trans 
									SET supp_reference='$supp_reference',cv_id='$cv_id',
									alloc=".$row2_1['ov_amount']."
									WHERE type='20' and trans_no='".$row2_1['trans_no']."'";
									db_query($sql_supp2);
									display_error($sql_supp2);

								display_error('FIXED*');

							}
							display_error('-END-');

					
						}
						//die();
						
					}
					//display_error($sql2_1); die;
					
					$no1='';
					$no='';
					
					//die();
			}
	}
	
	display_notification("Fixing Accrued Consignment date is Successful!");
}
start_form();
start_table();
start_row();
//ref_cells('Transaction #:', 'trans_no');
//date_cells('Change date deposited to :', 'date');
end_row();
end_table();
br();
start_row();
submit_center('Add',_("Fix Accrued Consignment"), true, '', 'default');
end_table();
end_form();
end_page();
?>