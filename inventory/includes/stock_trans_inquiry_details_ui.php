<?php
$page_security = 'SA_ITEMSTRANSVIEW';	
$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include($path_to_root . "/includes/db_pager2.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/inventory/includes/stock_transfer2.inc");
include_once($path_to_root . "/includes/tcpdf/tcpdf.php");

//LAST REVISION: 7/09/2018 by bolalin
//REVISIONS: changed qty_out to actual_qty_out


		$sql_1="SELECT br_code_in,br_code_out FROM transfers.0_transfer_header WHERE id = '".$_GET['transfer_id']."' ";
		$result_1=db_query($sql_1,"error");
		while ($row = db_fetch($result_1)) {
			$br_code_in = $row['br_code_in'];
			$br_code_out = $row['br_code_out'];
		}

		$pdf = new TCPDF("P", PDF_UNIT, 'Folio', false, 'UTF-8', false);
		$filename = "SRS Stock Transfer Inquiry.pdf";
		$title = "SRS Stock Transfer Inquiry";
		
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_BOTTOM);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(true);
		
		$pdf->AddPage('P', 'A4');
		
		$content = "<table width=\"100%\" border=\"0px\">
						<tr>
						 <th style=\"text-align:center; font-size:16px\"><b>Branch: ". get_transfer_branch_name($br_code_out)."</b></th>
					    </tr>
						<tr>
						 <th style=\"text-align:center; font-size:16px\"><b>Stock Transfer Discrepancy Report</b></th>
					    </tr> 
					 </table><br><br>";

		$content .= "<table width=\"100%\" border=\"0px\">
						<tr>
							<th width=\"7%\"><b>From:</b></th>
							<th style=\"text-align:left; font-size:13px;width:33%\">". get_transfer_branch_name($br_code_out)."</th>

							<th width=\"20%\"><b>Transfer Slip No:</b></th>
							<th style=\"text-align:left; font-size:13px;width:23%\">".$_GET['transfer_id']."</th>

							<th width=\"4%\"><b>To:</b></th>
							<th style=\"text-align:left; font-size:13px;width:30%\">". get_transfer_branch_name($br_code_in)."</th>
						</tr>
					</table>";

		$content .= "
						<br>
						<center>
							<table width=\"100%\" border=\"1px\">
							 <thead>
								 <tr>
									<th style=\"text-align:center; font-size:11px; width:55;\"><b>Transfer #</b></th>
									<th style=\"text-align:center; font-size:11px; width:160;\"><b>Description</b></th>
									<th style=\"text-align:center; font-size:11px; width:78;\"><b>Barcode</b></th>
									<th style=\"text-align:center; font-size:11px; width:30;\"><b>OUM</b></th>
									<th style=\"text-align:center; font-size:11px; width:45;\"><b>Cost</b></th>
									<th style=\"text-align:center; font-size:11px; width:50;\"><b>Qty out</b></th>
									<th style=\"text-align:center; font-size:11px; width:50;\"><b>Qty In</b></th>
									<th style=\"text-align:center; font-size:11px; width:70;\"><b>Discrepancy</b></th>
								 </tr>
							 </thead>
							 <tbody>";

							$sql="SELECT td.*,th.remarsk_descrip,th.id FROM transfers.0_transfer_details as td
							LEFT JOIN transfers.0_transfer_header as th 
							on th.id= td.transfer_id 
							WHERE th.id = '".$_GET['transfer_id']."'  ";
							$result=db_query($sql,"error");
							if(db_num_rows($result) != ""){
							$count=1;
							$total_in = 0;
							$total_out = 0;
							while ($row = db_fetch($result)) {
							$remarks = $row['remarsk_descrip'];
							$total_in = $total_in + $row['qty_in'];
							$total_out = $total_out + $row['actual_qty_out'];
							$diff = $row['actual_qty_out'] - $row['qty_in'];
							$total_diff= $total_in - $total_out;

		$content .= "
						<tr class='evenrow'>
							<td style=\"text-align:center; font-size:10px; width:55;\">".$row['transfer_id']."</td>
							<td style=\"text-align:center; font-size:10px; width:160;\">".$row['description']."</td>
							<td style=\"text-align:center; font-size:10px; width:78;\">".$row['barcode']."</td>
							<td style=\"text-align:center; font-size:10px; width:30;\">".$row['uom']."</td>
							<td style=\"text-align:center; font-size:10px; width:45;\">".(number_format2(abs($row['cost']),2))."</td>
							<td style=\"text-align:center; font-size:10px; width:50;\">".(number_format2(abs($row['actual_qty_out']),2))."</td>
							<td style=\"text-align:center; font-size:10px; width:50;\">".(number_format2(abs($row['qty_in']),2))."</td>
							<td style=\"text-align:center; font-size:10px; width:70;\">".(number_format2(abs($diff),2))."</td>
						</tr>
					";					
				}

		$content .= "
						<tr>
							<td colspan=\"6\"></td>
							<td style=\"text-align:center; font-size:11px; width:50;margin-right:20px;\" ><b>Total</b></td>
							<td style=\"text-align:right; font-size:11px; width:70;text-align:center;\">".number_format2(abs($total_diff),2)."</td>
						</tr>
					";
		}

		$content .= "
						</tbody> 
						</table>
						</center><br><br>
							<table width=\"30%\" border=\"0px\">
								<tr>
									<td style=\"text-align:left; font-size:12px; width:75;\">Remarks</td>
								</tr>
								<tr>
									<td style=\"text-align:left; font-size:10px; width:75;\">".$remarks."</td>
								</tr>
							</table><br><br><br>

							<table width=\"100%\" border=\"0px\">
								<tr>
									<td style=\"text-align:left; font-size:8px; width:75;\">Prepared by: </td>
									<td style=\"text-align:left; font-size:8px; width:75;\">Checked by: </td>
									<td style=\"text-align:left; font-size:8px; width:75;\">Security-OIC O/G: </td>
									<td style=\"text-align:left; font-size:8px; width:75;\">Security-OIC O/G: </td>
									<td style=\"text-align:left; font-size:8px; width:75;\">Delivered by: </td>
									<td style=\"text-align:left; font-size:8px; width:75;\">Receive by: </td>
									<td style=\"text-align:left; font-size:8px; width:75;\">Security-OIC I/c: </td>
								</tr>
								<tr>
									<td align='center'>__________</td>
									<td align='center'>__________</td>
									<td align='center'>__________</td>
									<td align='center'>__________</td>
									<td align='center'>__________</td>
									<td align='center'>__________</td>
									<td align='center'>__________</td>
								</tr>
							</table>
					";

		$pdf->writeHTML($content,true,false,false,false,'');

		$pdf->Output($filename, 'I');
	
?>
	