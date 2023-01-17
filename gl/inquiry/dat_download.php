<?php

$page_security = 'SA_CHECKPRINT';
// ----------------------------------------------------------------
// $ Revision:	1.0 $
// Creator:	Tu Nguyen
// date_:	2008-08-04
// Title:	Print CPA Cheques (Canadian Pre-printed Standard)
// ----------------------------------------------------------------

$path_to_root="../..";

include($path_to_root . "/includes/session.inc");

// header('Content-Type: text/csv; charset=utf-8');

$filename = $_GET['filename'];

$target = $path_to_root."/dat/".$filename;

header('Content-Disposition: attachment; filename='.$filename);
readfile($target);

?>