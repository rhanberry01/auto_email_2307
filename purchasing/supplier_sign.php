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
$page_security = 'SA_PURCHASEORDER';
$path_to_root = "..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

echo "
	<script src='$path_to_root/js/jquery.min.js'></script>
	<script src='$path_to_root/js/numeric-1.2.6.min.js'></script> 
	<script src='$path_to_root/js/bezier.js'></script>
	<script src='$path_to_root/js/jquery.signaturepad.js'></script> 
	
	<script src='$path_to_root/js/html2canvas.js'></script>
	<script src='$path_to_root/js/json2.min.js'></script>
	<link  rel='stylesheet' href='$path_to_root/js/jquery.signaturepad.css'>
	
	<style type='text/css'>
			body{
				font-family:monospace;
				text-align:center;
			}
			#btnSaveSign {
				color: #fff;
				background: #f99a0b;
				padding: 5px;
				border: none;
				border-radius: 5px;
				font-size: 20px;
				margin-top: 10px;
			}
			#btnClearSign {
				color: #fff;
				background: #f99a0b;
				padding: 5px;
				border: none;
				border-radius: 5px;
				font-size: 20px;
				margin-top: 10px;
			}
			#signArea{
				width:660px;
				margin: 0px auto;
			}
			#signArea1{
				width:660px;
				margin: 60px auto;
			}
			.sign-container {
				width: 60%;
				margin: auto;
			}
			.sign-preview {
				width: 150px;
				height: 50px;
				border: solid 1px #CFCFCF;
				margin: 10px 5px;
			}
			.tag-ingo {
				font-family: cursive;
				font-size: 12px;
				text-align: left;
				font-style: oblique;
			}
		</style>
";

$js = '';
if ($use_popup_windows)
	$js .= get_js_open_window(800, 500);
if ($use_date_picker)
	$js .= get_js_date_picker();



start_form();
	$supp_id = $_GET['AddedID'];
	//echo $_GET['AddedID'];
	echo '<div id="signArea" >
			<h2 class="tag-ingo">Supplier`s Signature</h2>
			<div class="sig sigWrapper" style="height:auto;">
				<input type="hidden" id="supplierid" value='.$supp_id.'>
				<div class="typed"></div>
				<canvas class="sign-pad" id="ssign-pad" width="650" height="320"></canvas>
			</div>
			<br><br>
		</div>';
	 echo '<td><b>Supplier`s Name:</td> 
			<input type="text" id="sname" style="width: 340px; height:30px; text-transform:uppercase"><br>';
		
end_form();
	echo '<button id="btnSaveSign">Comfirm</button>
			<button id="btnClearSign">Clear</button>';
	


//------------------------------------------------------------------------------------------------

end_page();
?>
<script src='./js/jquery.signaturepad.js'></script> 

<script>
	$(document).ready(function() {
		$('#signArea').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:290});
	//	$('#signArea1').signaturePad({drawOnly:true, drawBezierCurves:true, lineTop:290});
	});
	
	$("#btnSaveSign").click(function(e){
		//alert($('#signArea').signaturePad().validateForm());
		 if($('#signArea').signaturePad().validateForm()){
			html2canvas([document.getElementById('ssign-pad')], {
				onrendered: function (canvas) {
					var canvas_img_data = canvas.toDataURL('image/png');
					var simg_data = canvas_img_data.replace(/^data:image\/(png|jpg);base64,/, "");
					//ajax call to save image inside folder
					//alert($('#sname').val());
					$.ajax({
						url: 'save_sign.php',
						data: { simg_data:simg_data, supp_id:$('#supplierid').val(), sname:$('#sname').val()},
						type: 'post',
						dataType: 'json',
						success: function (response) {
							alert("Successfully.  SRSSAF "+$('#supplierid').val());
							window.location = 'inquiry/sdma_inquiry.php';
						}
					});
				}
			});
		}else{
			if($('#sname').val() != ''){
				$.ajax({
						url: 'save_sign.php',
						data: { simg_data:'', supp_id:$('#supplierid').val(), sname:$('#sname').val()},
						type: 'post',
						dataType: 'json',
						success: function (response) {
							alert("Successfully.  SRSSAF "+$('#supplierid').val());
							window.location = 'inquiry/sdma_inquiry.php';
						}
					});
			}
		}
	});
	$("#btnClearSign").click(function(e){
		$('#signArea').signaturePad().clearCanvas();
		//$('#signArea1').signaturePad().clearCanvas();
	});
	$(document).ready(function() {
        window.history.pushState(null, "", window.location.href);        
        window.onpopstate = function() {
            window.history.pushState(null, "", window.location.href);
        };
    });

  </script> 