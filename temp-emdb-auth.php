<?php
//. misc. init
require_once( "commonlib.php" );

//. main

$out = [];
foreach ( _idloop( 'emdb_add' ) as $fn_json ) {
	//- 準備
	if ( _count( 'emdb', 0 ) ) break;
	$id = _fn2id( $fn_json );

	$json = _json_load2( $fn_json );
	foreach ( [ 'sauthor', 'author' ] as $tag ) {
		foreach ( (array)$json->$tag as $auth ) {
			if ( ! is_null( $auth ) ) continue;
			_m( "$id-$tag" );
			_problem( "$id: $tag - object tag" );
			break;
		}
	}
}
_end();
