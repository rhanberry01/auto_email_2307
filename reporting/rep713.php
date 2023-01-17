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
$page_security = 'SA_OPEN';
// ----------------------------------------------------------------
// $ Revision:	2.0 $
// Creator:	Joe Hunt
// date_:	2005-05-19
// Title:	Audit Trail
// ----------------------------------------------------------------
$path_to_root="..";

include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/data_checks.inc");
include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/ui/ui_view.inc");

//----------------------------------------------------------------------------------------------------

print_audit_trail();


function get_rr_total($from, $to)
{
	// $fromdate = date2sql($from) . " 00:00:00";
	// $todate = date2sql($to). " 23:59:59";
	// $sql = "SELECT COUNT(*)
			// FROM 0_audit_trail a
			// WHERE a.type = 25 AND description = 'import RR'
			// AND a.stamp >= '$fromdate'
			// AND a.stamp <= '$todate'";
			
	$sql = "SELECT COUNT(*) FROM 0_grn_batch
			WHERE delivery_date >= '".date2sql($from)."'
			AND delivery_date <= '".date2sql($to)."'";
    $res = db_query($sql,"No transactions were returned");
	$row = db_fetch($res);
	return $row[0];
}

function get_apv_total($from, $to, $user)
{
	$fromdate = date2sql($from) . " 00:00:00";
	$todate = date2sql($to). " 23:59:59";
	
	$sql = "SELECT COUNT(*)
			FROM 0_audit_trail a
			WHERE a.type = 20 AND description != 'Voided'
			AND a.stamp >= '$fromdate'
			AND a.stamp <= '$todate'";
	if ($user != -1)
		$sql .= " AND a.user = $user";
		
    $res = db_query($sql,"No transactions were returned");
	$row = db_fetch($res);
	return $row[0];
}

function get_cv_total($from, $to, $user)
{
	$fromdate = date2sql($from) . " 00:00:00";
	$todate = date2sql($to). " 23:59:59";
	
	$sql = "SELECT COUNT(*)
			FROM 0_audit_trail a
			WHERE a.type = 99 AND description = 'CV created'
			AND a.stamp >= '$fromdate'
			AND a.stamp <= '$todate'";
	if ($user != -1)
		$sql .= " AND a.user = $user";
		
    $res = db_query($sql,"No transactions were returned");
	$row = db_fetch($res);
	return $row[0];
}

function get_apv_in_cv_total($from, $to, $user)
{
	$fromdate = date2sql($from) . " 00:00:00";
	$todate = date2sql($to). " 23:59:59";
	
	$sql = "SELECT DISTINCT trans_no
			FROM 0_audit_trail a
			WHERE a.type = 99 AND description = 'CV created'
			AND a.stamp >= '$fromdate'
			AND a.stamp <= '$todate'";
	if ($user != -1)
		$sql .= " AND a.user = $user";
	// echo $sql;die;
		
    $res = db_query($sql,"No transactions were returned");
	$cv_ids = array();
	while ($row = db_fetch($res))
		$cv_ids[] = $row[0];

	if (count($cv_ids) == 0)
		return 0;
	
	$sql = "SELECT COUNT(*)
			FROM ".TB_PREF."cv_details
			WHERE cv_id IN (".implode(',',$cv_ids).")
			AND trans_type = 20";
	$res = db_query($sql);
	$row = db_fetch($res);
	
	return $row[0];
}

function get_total_discrepancy($from, $to, $user)
{
	$fromdate = date2sql($from) . " 00:00:00";
	$todate = date2sql($to). " 23:59:59";
	
	$sql = "SELECT COUNT(*)
			FROM ".TB_PREF."discrepancy_header
			WHERE date_submitted >= '$fromdate'
			AND date_submitted <= '$todate'";
	if ($user != -1)
		$sql .= " AND submitted_by = $user";
	
	$res = db_query($sql,"No transactions were returned");
	$row = db_fetch($res);
	return $row[0];
}

function get_user_in_audit_trail($from, $to, $user)
{
	$fromdate = date2sql($from) . " 00:00:00";
	$todate = date2sql($to). " 23:59:59";

	$sql = "SELECT DISTINCT `user`, real_name
			FROM ".TB_PREF."audit_trail a, ".TB_PREF."users b
			WHERE ((a.type = 20 AND description != 'Voided')
			OR (a.type = 99 AND description = 'CV created'))
			AND a.stamp >= '$fromdate'
			AND a.stamp <= '$todate'
			AND a.`user` = b.id";
	if ($user != -1)
		$sql .= " AND a.user = $user";
	$sql .= ' ORDER BY real_name';
	
	$res = db_query($sql,"No transactions were returned");
	
	$users = array();
	while($row = db_fetch($res))
	{
		$users[$row[0]] = $row[1];
	}
	
	$sql = "SELECT submitted_by, real_name
			FROM ".TB_PREF."discrepancy_header a, ".TB_PREF."users b
			WHERE a.`submitted_by` = b.id
			AND a.date_submitted >= '$fromdate'
			AND a.date_submitted <= '$todate'";
	if ($user != -1)
		$sql .= " AND a.submitted_by = $user";
	$sql .= ' ORDER BY real_name';
	$res = db_query($sql,"No transactions were returned");
	
	while($row = db_fetch($res))
	{
		$users[$row[0]] = $row[1];
	}
	
	return $users;
}
//----------------------------------------------------------------------------------------------------

function print_audit_trail()
{
    global $path_to_root, $systypes_array;

    $from = $_POST['PARAM_0'];
    $to = $_POST['PARAM_1'];
    $systype = 99; // $_POST['PARAM_2'];
    $user = $_POST['PARAM_2'];
    $comments = '';
	$destination = false;
	if ($destination)
		include_once($path_to_root . "/reporting/includes/excel_report.inc");
	else
		include_once($path_to_root . "/reporting/includes/pdf_report.inc");

    $dec = user_price_dec();

    $cols = array(0, 70, 170, 210, 420, 520, 520);

    // $headers = array(_('User'),_('Type'), _('Date'), _('Time'),  _('Trans Date'), _('#'));

    $aligns = array('left', 'left', 'right', 'left', 'left', 'left');

	$usr = get_user($user);
	$user_id = $usr['user_id'];
    $params =   array( 	0 => $comments,
    				    1 => array('text' => _('Period'), 'from' => $from,'to' => $to),
                    	2 => array('text' => _('User'), 'from' => ($user != -1 ? $user_id : _('All')), 'to' => ''),
						3 => array('text' => _('TOTAL Receiving'), 'from' => get_rr_total($from, $to, $user), 'to' => ''),
						4 => array('text' => _('TOTAL APV'), 'from' => get_apv_total($from, $to, $user), 'to' => ''),
						5 => array('text' => _('TOTAL APV w/ CV'), 'from' => get_apv_in_cv_total($from, $to, $user), 'to' => ''),
						6 => array('text' => _('TOTAL CV'), 'from' => get_cv_total($from, $to, $user), 'to' => ''),
						7 => array('text' => _('TOTAL Discrepancy'), 'from' => get_total_discrepancy($from, $to, $user), 'to' => ''),
						);

    $rep = new FrontReport(_('RR, APV and CV Monitoring'), "Monitoring", user_pagesize(),10);

    $rep->Font();
    $rep->Info($params, $cols, $headers, $aligns);
    $rep->Header();

    $trans = get_user_in_audit_trail($from, $to, $user);

	$count = count($trans);
	// die($count);
	if($count==0)
	{
		display_error("No reports can be generated with the given parameters.");
		die();
	}
	
	$rep->NewLine(-1, 2);
	
	foreach($trans as $id => $real_name)
	// while ($myrow=db_fetch($trans))
    {
		$rep->fontSize += 2;
		$rep->font('b');
		$rep->TextCol(0, 5, $real_name);
		$rep->font('');
		
		$rep->NewLine(1.2, 2);
		$rep->TextCol(1, 2, 'Total APV :');
		$rep->TextCol(2, 3, get_apv_total($from, $to, $id));
		$rep->NewLine(1.2, 2);
		$rep->TextCol(1, 2, 'Total APV w/ CV:');
		$rep->TextCol(2, 3, get_apv_in_cv_total($from, $to, $id));
		$rep->NewLine(1.2, 2);
		$rep->TextCol(1, 2, 'Total CV :');
		$rep->TextCol(2, 3, get_cv_total($from, $to, $id));
		$rep->NewLine(1.2, 2);
		$rep->TextCol(1, 2, 'Total Discrepancy :');
		$rep->TextCol(2, 3, get_total_discrepancy($from, $to, $id));
		
		$rep->fontSize -= 2;
		
		$rep->Line($rep->row-5);
		$rep->NewLine(1, 2);

		
        $rep->NewLine(1, 2);
    }
    $rep->End();
}

?>