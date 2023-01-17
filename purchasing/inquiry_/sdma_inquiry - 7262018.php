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
$page_security = 'SA_SUPPTRANSVIEW';
$path_to_root = "../..";


include($path_to_root . "/includes/db_pager2.inc");
include($path_to_root . "/includes/session.inc");

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");
include($path_to_root . "/reporting/includes/reporting.inc");

define('K_PATH_FONTS', "../../reporting/fonts/");
include($path_to_root . "/reporting/includes/pdf_report.inc");
include_once($path_to_root . "/gl/includes/db/gl_db_cv.inc");
require_once($path_to_root . '/modules/PHPMailer/class.phpmailer.php');


$js = "";
if ($use_popup_windows)
	$js .= get_js_open_window(900, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();

page(_($help_context = "Debit Memo Agreement Inquiry"), false, false, "", $js);

function yes_no($x,$yes='YES',$no='NO')
{
	if ($x)
		return $yes;
	return $no;
}
function get_real_name($id) {
	$sql = "SELECT real_name  FROM srs_aria_nova.".TB_PREF."users WHERE id = '".$id."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}
function get_po_user_id($id) {
	$sql = "SELECT po_user_id  FROM srs_aria_nova.".TB_PREF."users WHERE id = '".$id."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}
function sent_email($branch, $id){
	//**********************************************************************
	// email magic starts here
	$sql = "SELECT a.*, b.supp_name, b.contact, b.email, c.type_name FROM ".$branch.".".TB_PREF."sdma a, ".$branch.".".TB_PREF."suppliers b, ".$branch.".".TB_PREF."sdma_type c WHERE a.supplier_id = b.supplier_id  AND a.sdma_type = c.id AND a.id =".$id;
	
	$res = db_query($sql);		
	$row = db_fetch($res);	
	$contact = $row['contact'];
	$email = $row['email'];
	$prepared_by = $row['prepared_by'];

	$po_id = get_po_user_id($row['prepared_by']);
	$saf = explode('SRSSAF ',$row['reference']);
	
	//$sql = "INSERT INTO srs_aria_nova.0_c_payment_saf(reference,purchaserID) VALUES('".$row['reference']."','".$row['prepared_by']."')";
	//$query = db_query($sql);
	
	$sql1 = "SELECT * FROM srs_aria_nova.0_sdma_email_sent WHERE reference = 'SRSSAF ".$saf[1]."'";
	$res1 = db_query($sql1);
	if (db_num_rows($res1)==0)
	{
		$sql = "INSERT INTO srs_aria_nova.0_c_payment_saf(reference,purchaserID) VALUES('".$row['reference']."','".$row['prepared_by']."')";
		$query = db_query($sql);
	//	display_error($saf[1]);
	//	die;
		$params =   array( 	0 => $comments,
							1 => array('text' => _('Supplier'), 'from' => $row['supp_name'] , 'to' =>''),
							2 => array('text' => _('Type'), 'from' => $row['type_name']  , 'to' =>''),
							3 => array('text' => _('Created By'), 'from' => strtoupper(get_real_name($row['prepared_by'],$_GET['branch'])) , 'to' =>''),
							4 => array('text' => _('Date Created'), 'from' => sql2date($row['date_created']) , 'to' =>''),
							5 => array('text' => _('Effectivity'), 'from' => $row['frequency'] == 0 ? 'for 1 CV dated '.sql2date($row['dm_date']) : 'for 1 CV '. $frequency[$row['frequency']] .' starting '. sql2date($row['dm_date']).' (for '. ($row['period']+1) .' deductions)','to' =>''),
							6 => array('text' => _('Comments'), 'from' => $row['comment'] , 'to' =>'')
							);

		$path_to_root1 = "..";
		$rep = new FrontReport($row['reference'], "", user_pagesize());

		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header_srs();

		$rep->headerFunc = 'Header_srs';
		$a = 28;
		$total = 0;
		$branches_sql = "SELECT * from transfers.0_branches_other_income where inactive = 0 order by name";
		$branches_query = db_query($branches_sql);

		while ($row=db_fetch($branches_query))
		{
			$sql = "SELECT a.* FROM ".$row['aria_db'].".".TB_PREF."sdma a WHERE reference = 'SRSSAF ".$saf[1]."' AND is_done != 2";
			$query = db_query($sql);	
			$row1=db_fetch($query);
			//	display_error($sql);
			if(!$row1){
			//	$rep->Text($rep->leftMargin,'BRANCH: '.$row['name'], $companyCol,'center');
			}else{
				$a--;
				$companyCol = $rep->endLine - 150;
				$rep->Text($rep->leftMargin,'BRANCH: '.$row['name'], $companyCol,'center');
				
				if ($row1['amount'] <= 0){
					$rep->Text($companyCol,'PERCENTAGE:   '.$row1['disc_percent'].'%');
				}else{
					$rep->Text($companyCol,'AMOUNT:   P '.number_format($row1['amount'],2));
					$total= $total + $row1['amount'];
				}

				$rep->NewLine();
			}
		} 
		//die;
				
		$rep->NewLine();
		$rep->Line($rep->row - 4,2);
		$rep->fontSize += 1;
		
		if($total != 0){
			$rep->Text($rep->leftMargin,'TOTAL AMOUNT:', $companyCol,'center');
			$rep->Font('bold');
			$rep->Text($companyCol,'P '.number_format($total,2));
		}
		//$rep->NewLine();
		//	$rep->AddImage(path_to_root1);
		//$rep->TextCol(3, 4, $total_qty);
		
	//	display_error("Sup(SAF".$saf[1].").png");
	//	die;
		if(file_exists($path_to_root1 . "/doc_signs/Sup(SAF".$saf[1].").png")){
			$rep->NewLine($a);
			$rep->AddImage($path_to_root1 . "/doc_signs/Sup(SAF".$saf[1].").png", 380,180,150, 50);
			$rep->AddImage($path_to_root1 . "/doc_signs/signatures/".$po_id.".png", 70,180,150, 50);
			$rep->Text(100,'Purchaser Signature', $companyCol,'center');
			$rep->Text(400,'Supplier Signature',200,'center');
			$rep->NewLine(6);
			$rep->Text(100,strtoupper(get_real_name($prepared_by)), 500,'center');
		}else{
			$rep->NewLine($a);
			//display_error('asdassds');
		   // $rep->AddImage($path_to_root . "/purchasing/doc_signs/Sup(SAF".$_GET['id'].").png", 330,170,250, 100);
			$rep->AddImage($path_to_root1 . "/doc_signs/signatures/".$po_id.".png", 70,180,150, 50);
			$rep->Text(100,'Purchaser Signature', $companyCol,'center');
			$rep->Text(400,'Supplier Signature',200,'center');
			$sql = "SELECT supplierName FROM srs_aria_nova.".TB_PREF."sdma_supplier WHERE reference = 'SRSSAF".$saf[1]."'";
		
			$res = db_query($sql);
			$row = db_fetch($res);
			
			$rep->NewLine(3);
			$rep->Text(390,strtoupper($row[0]),200,'left');
			$rep->NewLine(3);
			$rep->Text(100,strtoupper(get_real_name($prepared_by)), 500,'center');
		}
		$rep->NewLine();
		//if ($cv_header['person_type'] != PT_SUPPLIER)
		//	return false;
		
	//	$supp_row = get_supplier($cv_header['person_id']);
		$fileatt = $rep->Output('Agreement.pdf','S');
		
		$mail                = new PHPMailer();
		
		// $body                = 'This message is intended for '.$supp_row['pay_to'].'. Payment has been made for transactions within the attached CV';
		
		
			$msg = "Please see attached below.";
		
				
		$body = "<hr><center><b>********** THIS IS A SYSTEM GENERATED E-MAIL. **********</b></center></br></br>
				<p>If you have a query regarding this agreement please call 935-9461 loc. 101 or 355-3960 loc. 101 </p><br>
				".$msg."
				";
				// San Roque Supermarket.
		

		$mail->IsSMTP(); // telling the class to use SMTP
		// $mail->SMTPAuth   = true;
		// $mail->SMTPSecure = "tls";  
		$mail->Host       = "192.168.0.213";
		$mail->Port       = 25;
		// $mail->Username   = "e-santiago@srssulit.com";
		// $mail->Password   = "Sulit6651";
		
		if ($_SESSION['wa_current_user']->loginname == 'admin')
			$mail->SMTPDebug  = 2;
			
		$mail->SetFrom('no-reply@srssulit.com', 'SRS Supplier Debit Memo Agreement Notification');
		
		// $mail->IsSMTP(); // telling the class to use SMTP
		// $mail->SMTPAuth   = true;                  // enable SMTP authentication
		// $mail->SMTPSecure = "ssl";                 // sets the prefix to the server
		// $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
		// $mail->Port       = 465;
		// $mail->Username   = "automailer.srs@gmail.com";  // GMAIL username
		// $mail->Password   = "srsmailer"; 
		// $mail->SMTPDebug  = 2;
		// $mail->SetFrom('automailer.srs@gmail.com', 'SRS Payment Notification');

		$mail->Subject = "SRS Supplier Debit Memo Agreement Notification SRSSAF ".$saf[1];

		$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

		$mail->MsgHTML($body);
		
		
		//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\ REMOVE ON LIVE  //\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
		// $supp_row['email'] = 'enjo.santiago@yahoo.com';
		// $supp_row['email'] = 'e-santiago@srssulit.com';
		// $supp_row['email'] = 'j-tabuyan@srssulit.com';
		//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
		// $cc  = 'e-santiago@srssulit.com';
		// $cc_name = 'Enjo Santiago';
	//	$cc  = 'j-tabuyan@srssulit.com';
	//	$cc_name = 'Jen Tabuyan';
		$sql = "SELECT * FROM srs_aria_nova.".TB_PREF."purchaser_email WHERE userId = ".$prepared_by;
		$res = db_query($sql);
		$row = db_fetch($res);
	//	display_error($row['purName'].'---'.$row['email']);
	//	display_error($contact.'---'.$email);
	//	die;
		$cc  = $row['email'];
		$cc_name = strtoupper($row['purName']);
		
		// $cc  = 'srsccpayment@gmail.com';
		// $cc_name = 'SRS';
		
		//password:Srs01212009
		//$mail->AddAddress(trim($supp_row['email']), $supp_row['pay_to']);
		//$mail->AddAddress('eyem_yu@yahoo.com', strtoupper($contact));
		$mail->AddAddress(trim($email), strtoupper($contact));
		$mail->AddReplyTo($cc, $cc_name);
		$mail->AddCC($cc, $cc_name);
		//display_error('asdas');
		//die;
		// $mail->AddAddress('enjo.santiago@yahoo.com', 'tada');

		 $mail->AddStringAttachment($fileatt, "Agreement.pdf");
		
		global $db_connections;
			
			if(!$mail->Send()) 
			{
				$mail->ClearAddresses();
				$mail->ClearAttachments();
				return ("Failed to send email.");
			}
			else 
			{
				
				$sql = "INSERT INTO srs_aria_nova.0_sdma_email_sent(reference) VALUES('SRSSAF ".$saf[1]."')";
				//display_error($sql);
				db_query($sql);
				$mail->ClearAddresses();
				$mail->ClearAttachments();
				display_notification("Message sent.");
				return ("Message sent.");
					
			}
	
		 // Clear all addresses and attachments for next loop
		  return "Message sent.";
	}
}
function resend_email($branch, $id){
	//**********************************************************************
	// email magic starts here
	$sql = "SELECT a.*, b.supp_name, b.contact, b.email, c.type_name FROM ".$branch.".".TB_PREF."sdma a, ".$branch.".".TB_PREF."suppliers b, ".$branch.".".TB_PREF."sdma_type c WHERE a.supplier_id = b.supplier_id  AND a.sdma_type = c.id AND a.id =".$id;
	
	$res = db_query($sql);		
	$row = db_fetch($res);	
	$contact = $row['contact'];
	$email = $row['email'];
	$prepared_by = $row['prepared_by'];

	$po_id = get_po_user_id($row['prepared_by']);
	$saf = explode('SRSSAF ',$row['reference']);
	
	//	display_error($saf[1]);
	//	die;
		$params =   array( 	0 => $comments,
							1 => array('text' => _('Supplier'), 'from' => $row['supp_name'] , 'to' =>''),
							2 => array('text' => _('Type'), 'from' => $row['type_name']  , 'to' =>''),
							3 => array('text' => _('Created By'), 'from' => strtoupper(get_real_name($row['prepared_by'],$_GET['branch'])) , 'to' =>''),
							4 => array('text' => _('Date Created'), 'from' => sql2date($row['date_created']) , 'to' =>''),
							5 => array('text' => _('Effectivity'), 'from' => $row['frequency'] == 0 ? 'for 1 CV dated '.sql2date($row['dm_date']) : 'for 1 CV '. $frequency[$row['frequency']] .' starting '. sql2date($row['dm_date']).' (for '. ($row['period']+1) .' deductions)','to' =>''),
							6 => array('text' => _('Comments'), 'from' => $row['comment'] , 'to' =>'')
							);

		$path_to_root1 = "..";
		$rep = new FrontReport($row['reference'], "", user_pagesize());

		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header_srs();

		$rep->headerFunc = 'Header_srs';
		$a = 28;
		$total = 0;
		$branches_sql = "SELECT * from transfers.0_branches_other_income where inactive = 0 order by name";
		$branches_query = db_query($branches_sql);

		while ($row=db_fetch($branches_query))
		{
			$sql = "SELECT a.* FROM ".$row['aria_db'].".".TB_PREF."sdma a WHERE reference = 'SRSSAF ".$saf[1]."' AND is_done != 2";
			$query = db_query($sql);	
			$row1=db_fetch($query);
			//	display_error($sql);
			if(!$row1){
			//	$rep->Text($rep->leftMargin,'BRANCH: '.$row['name'], $companyCol,'center');
			}else{
				$a--;
				$companyCol = $rep->endLine - 150;
				$rep->Text($rep->leftMargin,'BRANCH: '.$row['name'], $companyCol,'center');
				
				if ($row1['amount'] <= 0){
					$rep->Text($companyCol,'PERCENTAGE:   '.$row1['disc_percent'].'%');
				}else{
					$rep->Text($companyCol,'AMOUNT:   P '.number_format($row1['amount'],2));
					$total= $total + $row1['amount'];
				}

				$rep->NewLine();
			}
		} 
		//die;
				
		$rep->NewLine();
		$rep->Line($rep->row - 4,2);
		$rep->fontSize += 1;
		
		if($total != 0){
			$rep->Text($rep->leftMargin,'TOTAL AMOUNT:', $companyCol,'center');
			$rep->Font('bold');
			$rep->Text($companyCol,'P '.number_format($total,2));
		}
		//$rep->NewLine();
		//	$rep->AddImage(path_to_root1);
		//$rep->TextCol(3, 4, $total_qty);
		
	//	display_error("Sup(SAF".$saf[1].").png");
	//	die;
		if(file_exists($path_to_root1 . "/doc_signs/Sup(SAF".$saf[1].").png")){
			$rep->NewLine($a);
			$rep->AddImage($path_to_root1 . "/doc_signs/Sup(SAF".$saf[1].").png", 380,180,150, 50);
			$rep->AddImage($path_to_root1 . "/doc_signs/signatures/".$po_id.".png", 70,180,150, 50);
			$rep->Text(100,'Purchaser Signature', $companyCol,'center');
			$rep->Text(400,'Supplier Signature',200,'center');
			$rep->NewLine(6);
			$rep->Text(100,strtoupper(get_real_name($prepared_by)), 500,'center');
		}else{
			$rep->NewLine($a);
			//display_error('asdassds');
		   // $rep->AddImage($path_to_root . "/purchasing/doc_signs/Sup(SAF".$_GET['id'].").png", 330,170,250, 100);
			$rep->AddImage($path_to_root1 . "/doc_signs/signatures/".$po_id.".png", 70,180,150, 50);
			$rep->Text(100,'Purchaser Signature', $companyCol,'center');
			$rep->Text(400,'Supplier Signature',200,'center');
			$sql = "SELECT supplierName FROM srs_aria_nova.".TB_PREF."sdma_supplier WHERE reference = 'SRSSAF".$saf[1]."'";
		
			$res = db_query($sql);
			$row = db_fetch($res);
			
			$rep->NewLine(3);
			$rep->Text(390,strtoupper($row[0]),200,'left');
			$rep->NewLine(3);
			$rep->Text(100,strtoupper(get_real_name($prepared_by)), 500,'center');
		}
		$rep->NewLine();
		//if ($cv_header['person_type'] != PT_SUPPLIER)
		//	return false;
		
	//	$supp_row = get_supplier($cv_header['person_id']);
		$fileatt = $rep->Output('Agreement.pdf','S');
		
		$mail                = new PHPMailer();
		
		// $body                = 'This message is intended for '.$supp_row['pay_to'].'. Payment has been made for transactions within the attached CV';
		
		
			$msg = "Please see attached below.";
		
				
		$body = "<hr><center><b>********** THIS IS A SYSTEM GENERATED E-MAIL. **********</b></center></br></br>
				<p>If you have a query regarding this agreement please call 935-9461 loc. 101 or 355-3960 loc. 101 </p><br>
				".$msg."
				";
				// San Roque Supermarket.
		

		$mail->IsSMTP(); // telling the class to use SMTP
		// $mail->SMTPAuth   = true;
		// $mail->SMTPSecure = "tls";  
		$mail->Host       = "192.168.0.213";
		$mail->Port       = 25;
		// $mail->Username   = "e-santiago@srssulit.com";
		// $mail->Password   = "Sulit6651";
		
		if ($_SESSION['wa_current_user']->loginname == 'admin')
			$mail->SMTPDebug  = 2;
			
		$mail->SetFrom('no-reply@srssulit.com', 'SRS Supplier Debit Memo Agreement Notification');
		
		// $mail->IsSMTP(); // telling the class to use SMTP
		// $mail->SMTPAuth   = true;                  // enable SMTP authentication
		// $mail->SMTPSecure = "ssl";                 // sets the prefix to the server
		// $mail->Host       = "smtp.gmail.com";      // sets GMAIL as the SMTP server
		// $mail->Port       = 465;
		// $mail->Username   = "automailer.srs@gmail.com";  // GMAIL username
		// $mail->Password   = "srsmailer"; 
		// $mail->SMTPDebug  = 2;
		// $mail->SetFrom('automailer.srs@gmail.com', 'SRS Payment Notification');

		$mail->Subject = "SRS Supplier Debit Memo Agreement Notification SRSSAF ".$saf[1];

		$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

		$mail->MsgHTML($body);
		
		
		//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\ REMOVE ON LIVE  //\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
		// $supp_row['email'] = 'enjo.santiago@yahoo.com';
		// $supp_row['email'] = 'e-santiago@srssulit.com';
		// $supp_row['email'] = 'j-tabuyan@srssulit.com';
		//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\//\\
		// $cc  = 'e-santiago@srssulit.com';
		// $cc_name = 'Enjo Santiago';
	//	$cc  = 'j-tabuyan@srssulit.com';
	//	$cc_name = 'Jen Tabuyan';
		$sql = "SELECT * FROM srs_aria_nova.".TB_PREF."purchaser_email WHERE userId = ".$prepared_by;
		$res = db_query($sql);
		$row = db_fetch($res);
		//display_error($row['purName'].'---'.$row['email']);
		//die;
		$cc  = $row['email'];
		$cc_name = strtoupper($row['purName']);
		
		// $cc  = 'srsccpayment@gmail.com';
		// $cc_name = 'SRS';
		
		//password:Srs01212009
		//$mail->AddAddress(trim($supp_row['email']), $supp_row['pay_to']);
		//$mail->AddAddress('eyem_yu@yahoo.com', strtoupper($contact));
		$mail->AddAddress(trim($email), strtoupper($contact));
		$mail->AddReplyTo($cc, $cc_name);
		$mail->AddCC($cc, $cc_name);
		//display_error('asdas');
		//die;
		// $mail->AddAddress('enjo.santiago@yahoo.com', 'tada');

		 $mail->AddStringAttachment($fileatt, "Agreement.pdf");
		
		global $db_connections;
			
			if(!$mail->Send()) 
			{
				$mail->ClearAddresses();
				$mail->ClearAttachments();
				return ("Failed to send email.");
			}
			else 
			{
				$mail->ClearAddresses();
				$mail->ClearAttachments();
				display_notification("Message sent.");
				return ("Message sent.");
					
			}
	
		 // Clear all addresses and attachments for next loop
		  return "Message sent.";
}	
//======================================================================================
$resend_id = find_submit('resend_email');
if ($resend_id != -1)
{
 //display_error($resend_id.'--'. $_POST['branch_name1'.$resend_id]);
 //die;
	resend_email($_POST['branch_name1'.$resend_id], $resend_id);
	$Ajax->activate('dm_list');
}

if (isset($_POST['approve_all_checked'])) {

	if (isset($_POST['approve_sdma1']) && !empty($_POST['approve_sdma1'])) {

		foreach ($_POST['approve_sdma1'] as $key => $approve1) {

			if ($approve1 != -1)
			{
				if (isset($_POST)) {
					$branch_name = $_POST['branch_name1'.$approve1];
				}
				global $Ajax;
			 	$sql2 = "SELECT id FROM $branch_name.".TB_PREF."users WHERE user_id = '". $_SESSION['wa_current_user']->username ."'";
				$res2 = db_query($sql2);
				$row2 = db_fetch($res2);
				$user_id = $row2[0];

				$sql = "UPDATE $branch_name.".TB_PREF."sdma SET approval_1 = $user_id
						WHERE id = $approve1";
				db_query($sql);
				$_POST['search'] = 1; 
				
				sent_email($branch_name, $approve1);
				$Ajax->activate('dm_list');
				
				// create_dm_from_sdma($approve1);
			}
		}
	}

	if (isset($_POST['approve_sdma2']) && !empty($_POST['approve_sdma2'])) {

		foreach ($_POST['approve_sdma2'] as $key => $approve2) {

			if ($approve2 != -1)
			{
				if (isset($_POST)) {
					$branch_name = $_POST['branch_name2'.$approve2];
				}
				global $Ajax;
				$sql2 = "SELECT id FROM $branch_name.".TB_PREF."users WHERE user_id = '". $_SESSION['wa_current_user']->username ."'";
				$res2 = db_query($sql2);
				$row2 = db_fetch($res2);
				$user_id = $row2[0];

				$sql = "UPDATE $branch_name.".TB_PREF."sdma SET approval_2 = $user_id
						WHERE id = $approve2";
				db_query($sql);
				$_POST['search'] = 1;
				$Ajax->activate('dm_list');
				
				create_dm_from_sdma_by_branch($approve2, $branch_name);
			}
		}
	}
}


function get_username_by_id_and_branch($id, $branch_name)
{
	$sql = "SELECT user_id FROM ".TB_PREF."users WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	$user_id = $row[0];

	$sql2 = "SELECT real_name FROM $branch_name.".TB_PREF."users WHERE user_id = '".$user_id."'";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	return $row2[0];
}

function get_display_username_by_id_and_branch($id, $branch_name)
{
	$sql2 = "SELECT real_name FROM $branch_name.".TB_PREF."users WHERE id = $id";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	return $row2[0];
}
function get_display_username_by_id_and_branch_($id)
{
	$sql2 = "SELECT real_name FROM srs_aria_nova.".TB_PREF."users WHERE id = $id";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	return $row2[0];
}


function get_user_id_by_username_and_branch($user_name, $branch_name) {
	$sql = "SELECT id FROM $branch_name.".TB_PREF."users WHERE user_id = '".$user_name."'";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row[0];
}

function create_dm_from_sdma_by_branch($id, $branch_name)
{
	$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma WHERE id=$id AND approval_1 != 0 AND approval_2 != 0 AND is_done = 0";
	// display_error($sql);
	$res = db_query($sql);
	$row = db_fetch($res);
	
	if (db_num_rows($res) == 1 AND $row['amount'] != 0)
		create_dm_from_fixed_amount_sdma_by_branch($id, $branch_name);
}

function create_dm_from_fixed_amount_sdma_by_branch($id, $branch_name)
{
	global $Refs;
	
	$sdma = get_sdma_by_branch($id, $branch_name);
	
	begin_transaction();
	
	$dm_date = sql2date($sdma['dm_date']);
	$freq = $sdma['frequency'];
	$repeat = $sdma['period']+1;
	$spec_ref = $sdma['id'];
	
	if ($sdma['po_no'] != '')
		$spec_ref .= '~PO'.$sdma['po_no'];

	$sdma_type = get_sdma_type_by_branch($sdma['sdma_type'], $branch_name);
	
	if ($repeat == 0)
		$repeat = 1;
	
	$count = 0;
	while($repeat != 0)
	{
		$count ++;
		
		$supp_trans_reference = get_next_by_branch(53, $branch_name);
		
		if ($freq > 0 AND $count > 1)
		{
			switch($freq)
			{
				case 1:
					$dm_date = add_days($dm_date, 7);
					break;
				case 2:	
					$dm_date = add_days($dm_date, 14);
					break;
				case 3:	
					$dm_date= add_months($dm_date, 1);
					break;
				case 4:	
					$dm_date = add_months($dm_date, 3);
					break;
			}
		}
		
		$company_record = get_company_prefs();
		
		$dc_amount = $sdma['amount']; //positive for debit , negative for credit. reverse in supp trans
		$tax_amount = 0;
		
		if ($sdma_type['credit_tax_account'] != '')
		{
			$dc_amount = round($dc_amount/1.12,2);
			$tax_amount  = $sdma['amount']-$dc_amount;
		}
		
		$trans_no = add_supp_trans_by_branch(53, $sdma['supplier_id'], $dm_date, '',
			$supp_trans_reference, $sdma['reference'], -$dc_amount,  -$tax_amount, 0, "", 0, 0,'', false, 0, $spec_ref, $branch_name);
		
		$debit_amt = $dc_amount;
		$credit_amt = $debit_amt;
		$output_vat_amt = 0;
		
		// if ($sdma_type['output_vat_account'] != '' AND $sdma_type['output_vat_percent'] > 0)
		// {
			// $credit_amt = round2($credit_amt * (1+($sdma_type['output_vat_percent']/100)));
			// $output_vat_amt = $debit_amt - $credit_amt;
		// }
		
		add_gl_trans_supplier_temp_by_branch(53, $trans_no, $dm_date, $sdma_type['debit_account'], 0, 0,$debit_amt, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment'], $branch_name);
		
		add_gl_trans_supplier_temp_by_branch(53, $trans_no, $dm_date, $sdma_type['credit_account'], 0, 0,-$credit_amt, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment'], $branch_name);
		
		if ($tax_amount != 0)
		add_gl_trans_supplier_temp_by_branch(53, $trans_no, $dm_date, $sdma_type['credit_tax_account'], 0, 0,-$tax_amount, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment'], $branch_name);
				
		if ($output_vat_amt != 0 AND $sdma_type['output_vat_account'] != '')
			add_gl_trans_supplier_temp_by_branch(53, $trans_no, $dm_date, $sdma_type['output_vat_account'], 0, 0,-$output_vat_amt, 
				$sdma['supplier_id'],"The general ledger transaction for the agreement could not be added",0,$sdma['comment'], $branch_name);
		
		add_comments_by_branch(53, $trans_no, $dm_date, $sdma['comment'], $branch_name);

		save_by_branch(53, $trans_no, $supp_trans_reference, $branch_name);
		
		$repeat	--;
	}
	
	set_sdma_to_done_by_branch($id, $branch_name);
	commit_transaction();
}

function get_sdma_by_branch($id, $branch_name)
{
	$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma WHERE id = $id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function get_sdma_type_by_branch($id, $branch_name)
{
	$sql = "SELECT * FROM $branch_name.".TB_PREF."sdma_type WHERE id=$id";
	$res = db_query($sql);
	$row = db_fetch($res);
	return $row;
}

function get_next_by_branch($type, $branch_name) 
{
	return get_next_reference_by_branch($type, $branch_name);
}

function get_next_reference_by_branch($type, $branch_name)
{
    $sql = "SELECT next_reference FROM $branch_name.".TB_PREF."sys_types WHERE type_id = ".db_escape($type);
    $result = db_query($sql,"The last transaction ref for $type could not be retrieved");

    $row = db_fetch_row($result);
    return $row[0];
}

function add_supp_trans_by_branch($type, $supplier_id, $date_, $due_date, $reference, $supp_reference,
	$amount, $amount_tax, $discount, $err_msg="", $rate=0, $ewt=0, $del_date='', $non_trade=false, $ewt_percent=0,
	$special_reference='', $branch_name)
{
	$date = date2sql($date_);
	if ($due_date == "")
		$due_date = "0000-00-00";
	else
		$due_date = date2sql($due_date);

	//display_error('before'.$del_date);
		
	if($del_date == "")
		$del_date = "0000-00-00";
	else
		$del_date = date2sql($del_date);
	
	
	//display_error('after'.$del_date);
	$trans_no = get_next_trans_no_by_branch($type, $branch_name);

	$curr = get_supplier_currency_by_branch($supplier_id, $branch_name);
	
	if ($rate == 0)
		$rate = get_exchange_rate_from_home_currency_by_branch($curr, $date_, $branch_name);


	$sql = "INSERT INTO $branch_name.".TB_PREF."supp_trans (trans_no, type, supplier_id, tran_date, due_date,
		reference, supp_reference, ov_amount, ov_gst, rate, ov_discount, ewt, del_date, non_trade, ewt_percent,
		special_reference) ";
		
	$sql .= "VALUES (".db_escape($trans_no).", ".db_escape($type)
	.", ".db_escape($supplier_id).", '$date', '$due_date',
		".db_escape($reference).", ".db_escape($supp_reference).", ".db_escape($amount)
		.", ".db_escape($amount_tax).", ".db_escape($rate).", ".db_escape($discount).", ".db_escape($ewt).", '$del_date',
		".($non_trade+0).",$ewt_percent+0,".db_escape($special_reference).")";

	if ($err_msg == "")
		$err_msg = "Cannot insert a supplier transaction record";

	db_query($sql, $err_msg);
	add_audit_trail_by_branch($type, $trans_no, $date_, '', '', $branch_name);

	return $trans_no;
}

function get_next_trans_no_by_branch($trans_type, $branch_name){

	$st = get_systype_db_info_by_branch($trans_type, $branch_name);

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

function get_systype_db_info_by_branch($type, $branch_name)
{
	switch ($type)
	{
        case     ST_JOURNAL      : return array("$branch_name.".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
        case     ST_BANKPAYMENT  : return array("$branch_name.".TB_PREF."bank_trans", "type", "trans_no", "ref", "trans_date");
		case     ST_BANKDEPOSIT  : return array("$branch_name.".TB_PREF."bank_trans", "type", "trans_no", "ref", "trans_date");
        case     ST_OR  	: return array("".TB_PREF."other_income_payment_header", "bd_trans_type", "bd_trans_no", "bd_reference", "bd_trans_date");
        case     3               : return null;
        case     ST_BANKTRANSFER : return array("$branch_name.".TB_PREF."bank_trans", "type", "trans_no", "ref", "trans_date");
        case     ST_SALESINVOICE : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CUSTCREDIT   : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CUSTPAYMENT  : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CUSTDELIVERY : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_LOCTRANSFER  : return array("$branch_name.".TB_PREF."stock_moves", "type", "trans_no", "reference", "tran_date");
		case     ST_ITEM_TRANSFORMATION  : return array("$branch_name.".TB_PREF."transformation_header", "a_type", "a_id", "a_id", "a_date_created");
		case     ST_SAKUSINAOUT  : return array("$branch_name.".TB_PREF."stock_moves", "type", "trans_no", "reference", "tran_date");
		case     ST_SAKUSINAIN  : return array("$branch_name.".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
       // case     ST_INVADJUST    : return array("".TB_PREF."stock_moves", "type", "trans_no", "reference", "tran_date");
        case     ST_INVADJUST    : return array("$branch_name.".TB_PREF."adjustment_header", "a_type", "a_id", "a_id", "a_date_created");
        case     ST_PURCHORDER   : return array("$branch_name.".TB_PREF."purch_orders", null, "order_no", "reference", "tran_date");
        case     ST_SUPPINVOICE  : return array("$branch_name.".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_SUPPCREDIT   : return array("$branch_name.".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_SUPPAYMENT   : return array("$branch_name.".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
        case     ST_CWODELIVERY  : return array("$branch_name.".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
        case     ST_SUPPRECEIVE  : return array("$branch_name.".TB_PREF."grn_batch", null, "id", "reference", "delivery_date");
        case     ST_WORKORDER    : return array("$branch_name.".TB_PREF."workorders", null, "id", "wo_ref", "released_date");
        case     ST_MANUISSUE    : return array("$branch_name.".TB_PREF."wo_issues", null, "issue_no", "reference", "issue_date");
        case     ST_MANURECEIVE  : return array("$branch_name.".TB_PREF."wo_manufacture", null, "id", "reference", "date_");
        case     ST_SALESORDER   : return array("$branch_name.".TB_PREF."sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case     31              : return array("$branch_name.".TB_PREF."service_orders", null, "order_no", "cust_ref", "date");
        case     ST_SALESQUOTE   : return array("$branch_name.".TB_PREF."sales_orders", "trans_type", "order_no", "reference", "ord_date");
        case	 ST_DIMENSION    : return array("$branch_name.".TB_PREF."dimensions", null, "id", "reference", "date_");
        case     ST_COSTUPDATE   : return array("$branch_name.".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
		
		case     ST_CUSTDEBITMEMO  : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_CUSTCREDITMEMO  : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_SUPPDEBITMEMO : return array("$branch_name.".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_SUPPCREDITMEMO  : return array("$branch_name.".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_SUPPDMAR  : return array("$branch_name.".TB_PREF."supp_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_ALLOCATION  : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case     ST_OTHERINCOME  : return array("$branch_name.".TB_PREF."debtor_trans", "type", "trans_no", "reference", "tran_date");
		case   	ST_CASHDEPOSIT : return array("$branch_name.".TB_PREF."gl_trans", "type", "type_no", null, "tran_date");
	}

	display_db_error("invalid type ($type) sent to get_systype_db_info", "", true);
}

function get_supplier_currency_by_branch($supplier_id, $branch_name)
{
    $sql = "SELECT curr_code FROM $branch_name.".TB_PREF."suppliers WHERE supplier_id = '$supplier_id'";

	$result = db_query($sql, "Retreive currency of supplier $supplier_id");

	$myrow=db_fetch_row($result);
	return $myrow[0];
}

function get_exchange_rate_from_home_currency_by_branch($currency_code, $date_, $branch_name)
{
	if ($currency_code == get_company_currency() || $currency_code == null)
		return 1.0000;

	$date = date2sql($date_);

	$sql = "SELECT rate_buy, max(date_) as date_ FROM $branch_name.".TB_PREF."exchange_rates WHERE curr_code = '$currency_code'
				AND date_ <= '$date' GROUP BY rate_buy ORDER BY date_ Desc LIMIT 1";

	$result = db_query($sql, "could not query exchange rates");

	if (db_num_rows($result) == 0)
	{
		// no stored exchange rate, just return 1
		display_error(
			sprintf(_("Cannot retrieve exchange rate for currency %s as of %s. Please add exchange rate manually on Exchange Rates page."),
				 $currency_code, $date_));
		return 1.000;
	}

	$myrow = db_fetch_row($result);
	return $myrow[0];
}

function add_audit_trail_by_branch($trans_type, $trans_no, $trans_date, $descr='',$br_code='', $branch_name)
{
	if ($br_code!=''){
		switch_connection_to_branch($br_code);
	}
	
	$ip = '';
	if (isset($_SERVER['HTTP_CLIENT_IP'])) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}
	elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED'];
	}
	elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_FORWARDED_FOR'];
	}
	elseif (isset($_SERVER['HTTP_FORWARDED'])) {
		$ip = $_SERVER['HTTP_FORWARDED'];
	}
	else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	$sql2 = "SELECT id FROM $branch_name.".TB_PREF."users WHERE user_id = '".$_SESSION["wa_current_user"]->username."'";
	$res2 = db_query($sql2);
	$row2 = db_fetch($res2);
	$user_id = $row2[0];
		
	$sql = "INSERT INTO $branch_name.".TB_PREF."audit_trail"
		. " (type, trans_no, user, fiscal_year, gl_date, description, gl_seq, remote_address)
			VALUES(".db_escape($trans_type).", ".db_escape($trans_no).","
			. $user_id. ","
			. get_company_pref('f_year') .","
			. "'". date2sql(Today()) ."',"
			. db_escape($descr). ", 0,"
			. db_escape($ip). ")";

	db_query($sql, "Cannot add audit info");
	
	// all audit records beside latest one should have gl_seq set to NULL
	// to avoid need for subqueries (not existing in MySQL 3) all over the code
	$sql = "UPDATE $branch_name.".TB_PREF."audit_trail SET gl_seq = NULL"
		. " WHERE type=".db_escape($trans_type)." AND trans_no="
		.db_escape($trans_no)." AND id!=".db_insert_id();

	db_query($sql, "Cannot update audit gl_seq");
}

function add_gl_trans_supplier_temp_by_branch($type, $type_no, $date_, $account, $dimension, $dimension2,  
	$amount, $supplier_id, $err_msg="", $rate=0, $memo="", $branch_name)
{
	if ($err_msg == "")
		$err_msg = "The supplier GL transaction could not be inserted";	
		
	return add_gl_trans_temp_by_branch($type, $type_no, $date_, $account, $dimension, $dimension2, $memo, 
		$amount, get_supplier_currency_by_branch($supplier_id, $branch_name), 
		PT_SUPPLIER, $supplier_id, $err_msg, $rate, $branch_name);
}

function add_gl_trans_temp_by_branch($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_,
	$amount, $currency=null, $person_type_id=null, $person_id=null,	$err_msg="", $rate=0, $branch_name)
{
	global $use_audit_trail;

	$date = date2sql($date_);
	if ($currency != null)
	{
		if ($rate == 0)
			$amount_in_home_currency = to_home_currency($amount, $currency, $date_);
		else
			$amount_in_home_currency = round2($amount * $rate,  user_price_dec());
	}		
	else
		$amount_in_home_currency = round2($amount, user_price_dec());
	if ($dimension == null || $dimension < 0)
		$dimension = 0;
	if ($dimension2 == null || $dimension2 < 0)
		$dimension2 = 0;
	if (isset($use_audit_trail) && $use_audit_trail)
	{
		if ($memo_ == "" || $memo_ == null)
			$memo_ = $_SESSION["wa_current_user"]->username;
		else
			$memo_ = $_SESSION["wa_current_user"]->username . " - " . $memo_;
	}
	$sql = "INSERT INTO $branch_name.".TB_PREF."gl_trans_temp ( type, type_no, tran_date,
		account, dimension_id, dimension2_id, memo_, amount";

	if ($person_type_id != null)
		$sql .= ", person_type_id, person_id";

	$sql .= ") ";

	$sql .= "VALUES (".db_escape($type).", ".db_escape($trans_id).", '$date',
		".db_escape($account).", ".db_escape($dimension).", "
		.db_escape($dimension2).", ".db_escape($memo_).", "
		.db_escape($amount_in_home_currency);

	if ($person_type_id != null)
		$sql .= ", ".db_escape($person_type_id).", ". db_escape($person_id);

	$sql .= ") ";

	if ($err_msg == "")
		$err_msg = "The GL transaction could not be inserted";

	db_query($sql, $err_msg);
	return $amount_in_home_currency;
}

function add_comments_by_branch($type, $type_no, $date_, $memo_, $branch_name)
{
	if ($memo_ != null && $memo_ != "")
	{
    	$date = date2sql($date_);
    	$sql = "INSERT INTO $branch_name.".TB_PREF."comments (type, id, date_, memo_)
    		VALUES (".db_escape($type).", ".db_escape($type_no)
			.", '$date', ".db_escape($memo_).")";

    	db_query($sql, "could not add comments transaction entry");
	}
}

function save_by_branch($type, $id, $reference, $branch_name) 
{
	add_reference_by_branch($type, $id, $reference, $branch_name);
	if ($reference != 'auto')
		save_last_by_branch($reference, $type, $branch_name);
}

function add_reference_by_branch($type, $id, $reference, $branch_name)
{
	$sql = "INSERT INTO $branch_name.".TB_PREF."refs (type, id, reference)
		VALUES (".db_escape($type).", ".db_escape($id).", "
			. db_escape(trim($reference)) . ")";

	db_query($sql, "could not add reference entry");
}

function save_last_by_branch($reference, $type, $branch_name) 
{
	$next = increment_by_branch($reference);
	save_next_reference_by_branch($type, $next, $branch_name);
}

function increment_by_branch($reference) 
{
	
    if (preg_match('/^(\D*?)(\d+)(.*)/', $reference, $result) == 1) 
    {
		list($all, $prefix, $number, $postfix) = $result;
		$dig_count = strlen($number); // How many digits? eg. 0003 = 4
		$fmt = '%0' . $dig_count . 'd'; // Make a format string - leading zeroes
		$nextval =  sprintf($fmt, intval($number + 1)); // Add one on, and put prefix back on

		return $prefix.$nextval.$postfix;
    }
    else 
        return $reference;
}

function save_next_reference_by_branch($type, $reference, $branch_name)
{
    $sql = "UPDATE $branch_name.".TB_PREF."sys_types SET next_reference=" . db_escape(trim($reference)) 
		. " WHERE type_id = ".db_escape($type);

	db_query($sql, "The next transaction ref for $type could not be updated");
}

function set_sdma_to_done_by_branch($id, $branch_name)
{
	$sql = "UPDATE $branch_name.".TB_PREF."sdma SET is_done=1 WHERE id = $id";
	db_query($sql,'failed to set sdma to is_done');
}

// Custom JS here
echo '<script type="text/javascript">';
	echo '$(document).ready(function() {
			$("#approve_all_1").change(function() {
				$(".approve_1").attr("checked", this.checked);
			});

			$("#approve_all_2").change(function() {
				$(".approve_2").attr("checked", this.checked);
			});

			$(".approve_1").change(function() {
				if ($(".approve_1").length == $(".approve_1:checked").length) {
					$("#approve_all_1").attr("checked", "checked");
				}
				else {
					$("#approve_all_1").removeAttr("checked");
				}
			});

			$(".approve_2").change(function() {
				if ($(".approve_2").length == $(".approve_2:checked").length) {
					$("#approve_all_2").attr("checked", "checked");
				}
				else {
					$("#approve_all_2").removeAttr("checked");
				}
			});

			$("input:checkbox").change(function() {
				if ($("input:checkbox:checked").length > 0) {
					$("#approve_all_checked").show();
				}
				else {
					$("#approve_all_checked").hide();
				}	
			});
			
		});';
echo '</script>';	

start_form();
div_start('header');

if (!isset($_POST['start_date']))
	$_POST['start_date'] = '01/01/'.date('Y');

start_table();
	start_row();
		ref_cells('PO #/Reference :', 'dm_po_ref');
		date_cells('Date Created From :', 'start_date');
		date_cells(' To :', 'end_date');
		if($_SESSION['wa_current_user']->user == 888 OR $_SESSION['wa_current_user']->user == 633  OR $_SESSION['wa_current_user']->user == 730 OR $_SESSION['wa_current_user']->user == 642 OR $_SESSION['wa_current_user']->user == 651 OR $_SESSION['wa_current_user']->user == 886){
			supplier_list_ms_cells('Database Supplier:', 'supp_id', null, 'Supplier Name');
		//supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		}else{
			purchaser_supplier_list_ms_cells('Database Supplier:', 'supp_id', null, 'Supplier Name');
		//purchaser_supplier_list_row('ARIA Supplier:', 'supplier_id_aria', null, 'Use Supplier above');
		
		}
		purchaser_list_cells('Purchaser :', 'purchaser', null, 'Please Select');
		allyesno_list_cells('Approval Status:','status', null, 'ALL', 'approved', 'not yet approved');
		submit_cells('search', 'Search');
	end_row();
end_table(2);
div_end();

div_start('dm_list');
// if (!isset($_POST['search']))
	// display_footer_exit();
$branches_sql = "SELECT * FROM transfers.0_branches";
$branches_query = db_query($branches_sql);

$all_branch = array();
while ($branches_res = db_fetch($branches_query)) {
	
	$sql = "SELECT a.*, b.supp_name, c.type_name FROM ".$branches_res['aria_db'].".".TB_PREF."sdma a, ".$branches_res['aria_db'].".".TB_PREF."suppliers b,
				".$branches_res['aria_db'].".".TB_PREF."sdma_type c
			WHERE a.supplier_id = b.supplier_id 
			AND a.sdma_type = c.id";
	if ($_POST['purchaser'])
	{
		$sql .= " AND a.prepared_by = '".$_POST['purchaser']."'";
	}
		
		
	if (trim($_POST['dm_po_ref']) == '')
	{
		$sql .= " AND DATE(date_created) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(date_created) <= '".date2sql($_POST['end_date'])."'";
				  
		if ($_POST['supp_id'])
		{
			$sql .= " AND b.supp_ref = '".$_POST['supp_id']."'";
		}
		
		if($_POST['status'] == 0) // for approval
			$sql .= " AND (approval_1 = 0 OR approval_2 = 0)";
		else if($_POST['status'] == 1) // with CV
				$sql .= " AND (approval_1 != 0 AND approval_2 != 0)";	
	}
	else
	{

		$sql .= " AND DATE(date_created) >= '".date2sql($_POST['start_date'])."'
				  AND DATE(date_created) <= '".date2sql($_POST['end_date'])."'"; // added due to accunting request module trade payable ** rhan 11/10/17

		$sql .= " AND (reference LIKE ".db_escape('%'.$_POST['dm_po_ref'].'%')." 
				  OR po_no LIKE ".db_escape('%'.$_POST['dm_po_ref'].'%')." )";
	}
	
	$sql .= " ORDER BY date_created";
	$res = db_query($sql);
	//display_error($sql);

	while ($row = db_fetch_assoc($res)) {
		$_row = $row;
		$_row['branch_name'] = $branches_res['name'];
		$_row['branch_db'] = $branches_res['aria_db'];
		array_push($all_branch, $_row);
	}
}

start_table($table_style2.' width=90%');
$th = array('#', 'Reference','PO #', 'Date Created', 'Created By', 'Supplier', 'Branch', 'Type', 'Amount', 'Effectivity','Comment',
		'Approval 1'.'<input type="checkbox" id="approve_all_1">','Approval 2'.'<input type="checkbox" id="approve_all_2">', '','','');//,'Inactive');


if (!empty($all_branch))
	table_header($th);
else
{
	display_heading('No transactions found');
	display_footer_exit();
}

$type = 53;
$k = 0;
$amount=0;
foreach ($all_branch as $key => $row) 
{
	alt_table_row_color($k);
	$branch = '&branch='.$row['branch_db'];
	// label_cell(get_supplier_trans_view_str($type, $row['trans_no'], $row['reference']));
	 $ref = explode("SRSSAF ",$row['reference']);
	 
	label_cell($row['id']);
	//label_cell($row['reference']);
	if ($row['approval_1'] != 0 OR $_SESSION['wa_current_user']->user == 1){
		label_cell(
				"<a target=blank href='$path_to_root/reporting/pur_sup_sign.php?
					id=".$ref[1]."$branch'onclick=\"javascript:openWindow(this.href,this.target); return false;\">" .
					$row['reference'] . "&nbsp;</a> "
				
				);
	}else{
		label_cell($row['reference']);
	}
	label_cell($row['po_no']);
	label_cell(sql2date($row['date_created']));
	label_cell(strtoupper(get_display_username_by_id_and_branch_($row['prepared_by'])));
	label_cell($row['supp_name'] ,'nowrap');
	label_cell($row['branch_name']);
	label_cell($row['type_name']);
	
	if ($row['amount'] > 0)
		amount_cell($row['amount']);
	
	else
		label_cell($row['disc_percent'].'%','align=right');
	
	if ($row['po_no'] != '')
	{
		$effectivity = 'for PO # '.$row['po_no'].' Only';
	}
	else if ($row['po_no'] == '' AND $row['once_only'] == 1)
	{
		global $frequency;
		if ($row['frequency'] == 0)
			$effectivity = 'for 1 CV dated '. sql2date($row['dm_date']);
		else
			$effectivity = 'for 1 CV '. $frequency[$row['frequency']] .' starting '. sql2date($row['dm_date']).
				' <br>(<i>for '. ($row['period']+1) .' deductions</i>)';
	}
	else
		$effectivity = sql2date($row['effective_from']) .' to '. sql2date($row['effective_to']);
	
	label_cell($effectivity);
	
	// label_cell(yes_no($row['once_only']),'align = center');
	
	label_cell($row['comment']);
	if($row['is_done'] == 2){
		label_cell("<b>CANCELED</b>","colspan=5 align='center'");
		
	}else{
		// display_error($_SESSION['wa_current_user']->can_approve_sdma_1);
		if ($row['approval_1'] == 0 AND !$_SESSION['wa_current_user']->can_approve_sdma_1) {
			label_cell('<i>pending</i>','align=center');
		}
		else if ($row['approval_1'] == 0 AND $_SESSION['wa_current_user']->can_approve_sdma_1) {
	//	if ($row['approval_1'] == 0) {
			echo '<td align="center">
					<input type="hidden" name="branch_name1'.$row['id'].'" value="'.$row['branch_db'].'" />
					<input type="checkbox" class="approve_1" name="approve_sdma1[]" value="'.$row['id'].'" />
				</td>';
		}
		else {
		
			label_cell('by: <i><b>'.get_display_username_by_id_and_branch($row['approval_1'], $row['branch_db']).'</b></i>');
		}
			
		if ($row['approval_2'] == 0 AND !$_SESSION['wa_current_user']->can_approve_sdma_2) {
			label_cell('<i>pending</i>','align=center');
		}
		else if ($row['approval_2'] == 0 AND $_SESSION['wa_current_user']->can_approve_sdma_2)
		{
			if ($row['approval_1'] == 0) {
				label_cell('<i>waiting for 1st approval</i>','align=center');
			}
			else {
				echo '<td align="center">
					<input type="hidden" name="branch_name2'.$row['id'].'" value="'.$row['branch_db'].'" />
					<input type="checkbox" class="approve_2" name="approve_sdma2[]" value="'.$row['id'].'" />
				</td>';
			}
		}
		else {
			label_cell('by: <i><b>'.get_display_username_by_id_and_branch($row['approval_2'], $row['branch_db']).'</b></i>');
		}

		// label_cell('<b>'.yes_no($row['inactive'],'INACTIVE','ACTIVE').'</b>','align = center');	
		
	/* 	if (($_SESSION['wa_current_user']->username == 'cecil' OR $_SESSION['wa_current_user']->can_approve_sdma_1) OR 
			($row['approval_2'] == 0 AND $row['prepared_by'] == get_user_id_by_username_and_branch($_SESSION['wa_current_user']->username, $row['branch_db']))){ */
		if ($_SESSION['wa_current_user']->user == 633  OR $_SESSION['wa_current_user']->user == 730 OR  $_SESSION['wa_current_user']->user == 1 AND $row['approval_1'] != 0){
			echo '<input type="hidden" name="branch_name1'.$row['id'].'" value="'.$row['branch_db'].'" />';
			label_cell(submit('resend_email'.$row['id'], 'Resend e-mail', false, false, false,ICON_EMAIL),'align=center');	
		}else{
			label_cell('');	
		}
		if ($row['approval_1'] == 0){
			label_cell(pager_link(_("Edit"),
				"/purchasing/sdma_entry_edit_new.php?reference=" . $row["reference"] . "&branch=" . $row['branch_db'] . "&name=" . $row['branch_name'], ICON_EDIT));
				
			label_cell(pager_link(_("Delete"),
				"/purchasing/sdma_entry_delete_new.php?reference=" . $row["reference"] . "&branch=" . $row['branch_db'] . "&name=" . $row['branch_name'], ICON_DELETE));
				
		}else{
			if ($_SESSION['wa_current_user']->user == 633  OR $_SESSION['wa_current_user']->user == 730 OR $_SESSION['wa_current_user']->user == 1){
				label_cell(pager_link(_("Edit"),
				"/purchasing/sdma_entry_edit_new.php?reference=" . $row["reference"] . "&branch=" . $row['branch_db'] . "&name=" . $row['branch_name'], ICON_EDIT));
				
				label_cell(pager_link(_("Delete"),
				"/purchasing/sdma_entry_delete_new.php?reference=" . $row["reference"] . "&branch=" . $row['branch_db'] . "&name=" . $row['branch_name'], ICON_DELETE));
				
			}
			
		}
			
	}
	
	end_row();
$amount = $amount + $row['amount'];
}
alt_table_row_color($k);
label_cell("<b>Total: </b>","colspan=8 align='right'");
label_cell("<b> ".number_format($amount,2)."</b>","colspan=8 align='left'");
end_row();

end_table();
div_end();

echo '<center style="margin: 10px 0;">
		<button class="inputsubmit" type="submit" id="approve_all_checked" name="approve_all_checked" style="display: none;">
			<span>Approve All Checked</span>
		</button>
	</center>';

end_form();



end_page();

?>
