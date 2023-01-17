<?php


$page_security = 'SA_GLANALYTIC';
$path_to_root="../..";

include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/modules/checkprint/includes/cv_mailer.inc");

$sql = "UPDATE 0_email_verified SET verified = 1 WHERE cv_no = ".db_escape($_GET['cv_no'])." ";
echo $sql;
$res = db_query($sql);
display_error('successfully verify');