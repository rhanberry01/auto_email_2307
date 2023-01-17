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
$page_security = 'SA_GLANALYTIC';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	List of Journal Entries
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");

//----------------------------------------------------------------------------------------------------

print_list_of_journal_entries();

//----------------------------------------------------------------------------------------------------

function print_list_of_journal_entries()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $supplier_id = $_POST['PARAM_2'];
	$destination = $_POST['PARAM_3'];
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

    $cols = array(0, 100, 240, 300, 400, 460, 520, 580);

    $headers = array(_('Type/Account'), _('Reference'), _('Date/Dim.'),
    	_('Memo'), _('amount'));

    $aligns = array('left', 'left', 'left', 'left', 'right', 'right');

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('Type'), 'from' => 
						$systype == -1 ? _('All') : $systypes_array[$systype],
                            'to' => ''));
    $com = get_company_prefs();	
    $rep = new FrontReport(_('Advances To Suppliers'), "Advances To Suppliers", user_pagesize());
    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

    if ($systype == -1)
        $systype = null;

    $from = date2sql($from);
	$to = date2sql($to);

    $trans ="SELECT 
(CASE 
 WHEN (a.type = '53' or  a.type = '20') && (ISNULL(special_reference) || special_reference = '0' || special_reference = '') THEN supp_reference
 WHEN (ISNULL(supp_reference)) THEN '0'
	ELSE special_reference
	END
) as special_references,
a.*, c.supp_name,c.*,st.*,a.tran_date as date  
FROM 0_gl_trans a JOIN 0_chart_master b ON a.account = b.account_code 
LEFT OUTER JOIN 0_suppliers c
ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
LEFT JOIN 0_supp_trans as st on st.type = a.type and st.trans_no = a.type_no
WHERE a.tran_date >= '".$from."' AND a.tran_date <= '".$to."' AND a.account = '1440' AND (CASE 
 WHEN (a.type = '53' or  a.type = '20') && (ISNULL(special_reference) || special_reference = '0' || special_reference = '') THEN supp_reference
 WHEN (ISNULL(supp_reference)) THEN '0'
	ELSE special_reference
	END) NOT IN(SELECT
(CASE 
 WHEN (a.type = '53' or  a.type = '20') && (ISNULL(special_reference) || special_reference = '0' || special_reference = '') THEN supp_reference
  WHEN (ISNULL(supp_reference)) THEN '0'
	ELSE special_reference
	END
) as special_references
FROM 0_gl_trans a JOIN 0_chart_master b ON a.account = b.account_code 
LEFT OUTER JOIN 0_suppliers c
ON (a.person_id = c.supplier_id AND a.person_type_id = 3)
LEFT JOIN 0_supp_trans as st on st.type = a.type and st.trans_no = a.type_no 
WHERE a.tran_date >= '".$from."' AND a.tran_date <= '".$to."' AND a.account = '1440' GROUP BY special_references having sum(a.amount) = 0)";
	
	if( $supplier_id != -1){
  		 $trans .=" and c.supplier_id = '".$supplier_id."'";
	}
   $trans .="ORDER BY special_references,supp_name";
		
    	$res = db_query($trans);

    $d_total = $c_total = $typeno = $type = 0;
   while ($myrow=db_fetch($res))
   {
    	
		
		if ($myrow['amount']){
			if ($type != $myrow['type'] || $typeno != $myrow['type_no'])
			{
				if ($typeno != 0)
				{
					$rep->Line($rep->row  + 4);
					$rep->NewLine();
				}
				$typeno = $myrow['type_no'];
				$type = $myrow['type'];
				$TransName = $systypes_array[$myrow['type']];
				$rep->TextCol(0, 1, $TransName . " # " . $myrow['type_no']);
				$rep->TextCol(1,1, get_reference($myrow['type'], $myrow['type_no']));
				$rep->DateCol(2, 2, $myrow['date'], true);
				$coms =  payment_person_name($myrow["person_type_id"],$myrow["person_id"]);
				$memo = get_comments_string($myrow['type'], $myrow['type_no']);
				if ($memo != '')
				{
					if ($coms == "")
						$coms = $memo;
					else
						$coms .= " / ".$memo;
				}		
				$rep->TextCol(3,3, $coms);
				$rep->NewLine(2);
			}
			//$rep->TextCol(0, 1, $myrow['account']);
			//$rep->TextCol(1, 2, $myrow['account_name']);
			//$dim_str = get_dimension_string($myrow['dimension_id']);
			//$dim_str2 = get_dimension_string($myrow['dimension2_id']);
			//if ($dim_str2 != "")
			//	$dim_str .= "/".$dim_str2;c
			//$rep->TextCol(2, 3, $dim_str);
			$rep->TextCol(3, 5, $myrow['memo_']);
			
			$d_total  += $myrow['amount'];
			$rep->AmountCol(5, 6, $myrow['amount'], $dec);
			
			$rep->NewLine(1, 2);
		}
    }
	
    $rep->Line($rep->row  + 4);
	
	$rep->NewLine();
	$rep->font('b');
	$rep->AmountCol(5, 6, abs($d_total), $dec);
	$rep->font('');
	$rep->NewLine();
	
    $rep->End();
}

?>