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

function get_consignment_transactions($year,$month)
{
	$start_date=__date($year,$month,1);
	$end_date=end_month($start_date);
	
	$sql = "select chs.cons_sales_id as cons_sales_id,
	chs.supp_name as s_name,chs.t_commission as t_commission,
	chs.t_cos as t_cos, chs.t_sales as t_sales,s.* from ".TB_PREF."cons_sales_header  as chs
	left join ".TB_PREF."suppliers as s
	on chs.supp_code=s.supp_ref
	where chs.start_date='".date2sql($start_date)."'
	and chs.end_date='".date2sql($end_date)."'
	order by s.tax_group_id,chs.supp_name";
	//echo ($sql);die;
	
	return db_query($sql);
}

//----------------------------------------------------------------------------------------------------

print_list_of_journal_entries();

//----------------------------------------------------------------------------------------------------

function print_list_of_journal_entries()
{
    global $path_to_root, $systypes_array;
    $year = $_POST['PARAM_0'];
	$month = $_POST['PARAM_1'];
	$destination = $_POST['PARAM_2'];
	
	//$systype=ST_SUPPINVOICE;
	
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

    $cols = array(0, 55,290,350,430,500);

    $headers = array(_('Reference#'),_('Supplier'), _('Comm.(%)'),_('Cost of Sales'), _('Sales'), _('Commission'));

    $aligns = array('left', 'left','left','left','left');
	
	
	$start_date=__date($_POST['PARAM_0'],$_POST['PARAM_1'],1);
	$end_date=end_month($start_date);

    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $start_date,'to' => $end_date),
                    	2 => array('text' => _('Type'), 'from' => 
						$systype == -1 ? _('All') : $systypes_array[$systype],
                            'to' => ''));

    $rep = new FrontReport(_('Consignment Sales Summary'), "Consignment", user_pagesize());

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

    if ($systype == -1)
        $systype = null;

    $trans = get_consignment_transactions($year,$month);
	$count = db_num_rows($trans);
	
	if($count==0)
	{
		display_error("No reports can be generated with the given parameters.");
		die();
	}
  
  $x=0;
    while ($myrow=db_fetch($trans))
    {
         $rep->TextCol(0, 1, "CS".$myrow['cons_sales_id']);
		 
		if ($myrow['tax_group_id']==1){
		$rep->TextCol(1, 2, $myrow['s_name']." (NV)");
		}
		else if ($myrow['tax_group_id']==2){
		$rep->TextCol(1, 2, $myrow['s_name']." (V)");
		}
		else{
		$rep->TextCol(1, 2, $myrow['s_name']);
		}
            $rep->TextCol(2, 3, $myrow['t_commission'].'%');			
            $rep->AmountCol(3, 4, $myrow['t_cos'],2);
			$rep->AmountCol(4, 5, $myrow['t_sales'], 2);
			$rep->AmountCol(5, 6, $subt_commision=$myrow['t_sales']*($myrow['t_commission']/100),2);
	
			$rep->NewLine(1, 2);
			
			$t_cos+=$myrow['t_cos'];
			$t_extended+=$myrow['t_sales'];
			$t_commision+=$subt_commision;
			
			$x++;
    }
	
    $rep->Line($rep->row  + 4);
	$rep->NewLine();
	$rep->font('b');
	$rep->AmountCol(3, 4, abs($t_cos), $dec);
	$rep->AmountCol(4, 5, abs($t_extended), $dec);
	$rep->AmountCol(5, 6, abs($t_commision), $dec);
	$rep->font('');
	$rep->NewLine();
	
    $rep->End();
}
?>