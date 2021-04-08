<?php
//. misc. init
require_once( "commonlib.php" );

//. main

foreach ( _idloop( 'pdb_json' ) as $jsonfn ) {
	//- 準備
	if ( _count( 1000, 0 ) ) break;
	$id = _fn2id( $jsonfn );

	$json = _json_load2( $jsonfn );
	foreach ( (array)$json->struct_conf as $c ) {
//		_m( $c->id );
		_cnt( $c->conf_type_id );
	}
}

_cnt();
