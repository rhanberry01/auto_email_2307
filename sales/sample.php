<?php

$path_to_root = "..";
$page_security = 'SA_SALESORDER';

include_once($path_to_root . "/sales/includes/cart_class.inc");
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/sales/includes/sales_ui.inc");
include_once($path_to_root . "/sales/includes/ui/sales_order_ui.inc");
include_once($path_to_root . "/sales/includes/sales_db.inc");
include_once($path_to_root . "/sales/includes/db/sales_types_db.inc");
include_once($path_to_root . "/reporting/includes/reporting.inc");
include_once($path_to_root . "/purchasing/includes/purchasing_ui.inc");

echo "
	<script src='$path_to_root/js/jquery-1.3.2.min.js'></script>
	<script src='$path_to_root/js/jquery-ui.min.js'></script>
	<link href='$path_to_root/js/jquery-ui.css'></script>
";

$preloader_gif = $path_to_root.'/themes/modern/images/ajax-loader.gif';

function deleteHiddenDeliveries($tno){
	$sql = "SELECT trans_no
				FROM 0_debtor_trans
				WHERE type = 13
				AND reference = (SELECT reference
											  FROM 0_debtor_trans
											  WHERE trans_no = $tno
											  AND type = 10)
				AND skip_dr = 1";
	$query = db_query($sql) or die(mysql_error());
	return $query;
}

function checkVoided($trans, $type){
	$sql = "SELECT date_, memo_
				FROM 0_voided
				WHERE type = $type
				AND id = $trans";
				//echo $sql;
	$query = db_query($sql);
	return $query;
}

function checkTransType($trans){
	switch($trans){
		case 1: return 30; break;
		case 2: return 13; break;
		case 3: return 10; break;
		case 4: return 18; break;
		case 5: return 25; break;
		case 6: return 20; break;
	}
}

$ordernum = $_POST['OrderNumber'];
	
	echo "<style>
			.ftitle{
				font-family:arial,helvetica;
				font-size:12px;
				font-weight:bold;
			}
			.ftitle2{
				font-family:arial,helvetica;
				font-size:12px;
			}
			legend{
				color:#045c97;
			}
			#warn{
				color:#ff0000;
			}
		</style>
		<script>
			$(document).ready(function(){
				$('#vbtn').click(function(ev){
					$('#button').slideUp('fast');
					$('#ploader').slideDown('fast');
					check(ev);
					ev.preventDefault();
				});
				
				$('#uname').click(function(ev){
					if(ev.which == 13){
						check(ev);
					}
					ev.preventDefault();
				});
				
				$('#passwd').click(function(ev){
					if(ev.which == 13){
						check(ev);
					}
					ev.preventDefault();
				});
				
				function resetBtns(){
					$('#ploader').slideUp('fast');
					$('#button').slideDown('fast');
				}
				
				function check(event){
					var uname = $('#uname').attr('value');
					var passwd = $('#passwd').attr('value');
					var url = 'checkAccount2.php';
					
					if(!uname || !passwd){
						alert('Please complete the form!');
						$('#ploader').slideUp('fast');
						$('#button').slideDown('fast');
					}else{
						$.post(url, {'uname' : uname, 'passwd' : passwd}, function(res){
							if(res == 0){
								$('#warn').html('User does not exist!');
								resetBtns();
							}else if(res == 1){
								$('#warn').html('');
								/*resetBtns();*/
								/*$('form').submit();*/
								/*tb_remove();*/
								self.parent.tb_remove();
								/*postMe(onum, otype, vmemo);*/
							}else if(res == 2){
								$('#warn').html('Password mismatch!');
								resetBtns();
							}else if(res == 3){
								$('#warn').html('User has no supervisor privileges!');
								resetBtns();
							}
						});
						
					}
				}
				
				$('#redirect_lnk').click(function(ev){
					url = $(this).attr('href');
					window.parent.location.href = '".$path_to_root."' + url;
				});
			});
		</script>";
	
	
			echo "
				<form method=post action=#>
					<div id='thebox'>
						<fieldset>
							<legend class='ftitle'>Supervisor's Login</legend>
							<div id='warn' class='ftitle'></div>
							<table border='0'>
								<tr>
									<td class='ftitle'>Username</td>
									<td class='ftitle'> : </td>
									<td><input type='text' name='uname' id='uname' size='25'></td>
								</tr>
								<tr>
									<td class='ftitle'>Password</td>
									<td class='ftitle'> : </td>
									<td><input type='password' name='passwd' id='passwd' size='25'></td>
								</tr>
							</table>
						</fieldset>
						<center>
							<div id='button'>
								<input type='submit' name='subbtn' value='Void' id='vbtn'>
							</div>
							<div id='ploader' style='display:none'>
								<img src='$preloader_gif'>
							</div>
						</center>
					</div>
				</form>
			";


?>