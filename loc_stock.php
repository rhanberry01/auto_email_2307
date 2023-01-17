<?php




include("includes/session.inc");
db_query("TRUNCATE 0_loc_stock");
$locs=db_query("SELECT loc_code FROm 0_locations");

$items=db_query("SELECT stock_id FROm 0_stock_master");
while($a=db_fetch($locs)){
	$loc[]=$a[0];
}
while($b=db_fetch($items)){
	$item[]=$b[0];
}
foreach($loc as $l){
	foreach($item as $i)
	db_query("INSERT INTO 0_loc_stock VALUES('$l','$i',0)");
}

header("Location: index.php");
?>