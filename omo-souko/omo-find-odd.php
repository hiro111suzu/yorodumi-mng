<?php
//. init
include "omo-common.php";
$compdn = "$omodn/comp";

$comp = _data_load( "$omodn/comp/50-20.data" );
echo count( $comp ) . " data \n";

$complist = _data_load( "$omodn/idlist/complist.data" );

$ar = array(
	'70s-70s' ,
	'80s-80s' ,
	'c1-c1' ,
	'c2-c2' ,
	'30s-30s' ,
	'50s-50s'
);

//. main

foreach ( $ar as $mode ) {
	echo "##### $mode:\n";
	$data = array();
	$cnt = array();
	foreach ( $complist[ $mode ] as $ids ) {
//		echo "$ids\n";
		$i = explode( '|', $ids );
		$d = $comp[ $ids ];
		$data[ $i[0] ] += $d;
		$data[ $i[1] ] += $d;
		++ $cnt[ $i[0] ];
		++ $cnt[ $i[1] ];
	}
	asort( $data );
	$out = '';
	foreach ( $data as $id => $val ) {
		$val = $val / ( count( $data ) - 1 );
		echo "$id\t$val\t{$cnt[$id]}\n";
		$out .= "$id\t$val\n" ;
	}
	file_put_contents( "$omodn/odd-$mode.tsv", $out );
}


