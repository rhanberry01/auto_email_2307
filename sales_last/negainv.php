<?php

$path_to_root = "..";
$page_security = 'SA_SALESORDER';
include_once($path_to_root . "/includes/session.inc");
$value = $_POST['val'];
$_SESSION['allownegativecost'] =$value;
echo $_SESSION['allownegativecost'];

?>