<?php 
include "commonlib.php";
$str = $argv[1] ?: 'str' ;

$ids = _idlist( 'emdb' );
$cnt = 0;
foreach ( array_rand( $ids, 10000 ) as $key ) {
	preg_match_all(
		'/^.*'. $str. '.*$/im' ,
		file_get_contents( _fn( 'emdb_xml', $ids[ $key ] ) ) ,
		$match,
		PREG_PATTERN_ORDER
	);
	if ( count( $match[0] ) < 1 ) continue;
	_m( '### '. $ids[ $key ], 1 );
	_m( implode( "\n", $match[0] ?: [ '--- none ---' ] ) );
	++ $cnt;
	if ( 20 < $cnt ) break;
}

