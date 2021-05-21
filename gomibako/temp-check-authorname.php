<?php
include "commonlib.php";
$as = [];
foreach ( $emdbidlist as $id ) {
	$j = _json_load2( $n = _fn( 'emdb_json', $id ) );
	$a = $j->deposition->primaryReference->journalArticle->authors
		. ', '
		. $j->deposition->authors
	;
	if ( $a != '' ) {

		foreach ( explode( ',', $a ) as $p ) {
			$as[ trim( $p ) ] = 1;
		}
	} else {
//		print_r( $j->deposition );
		_m( "$id: noname" );
	}
}

$as = array_keys( $as );
sort( $as );
foreach ( $as as $p )
	if ( ! _instr( ' ', $p ) )
		_m( $p );
//_m( implode( "\n", $as ) );

