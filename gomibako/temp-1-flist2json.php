<?php
require_once( "commonlib.php" );

$_filelistfn = "_filelist.xml";
$xml = _openxml( $_filelistfn );
$data = array();
$count = array();
foreach ( $xml as $id => $v1 ) {
	foreach ( $v1 as $n2=>$v2 ) {
		$count[ $n2 ] += 1; 
		echo "$n2: $v2\n";
	}
}

foreach ( $count as $n => $v ) {
	echo "$n : $v \n";
}



?>