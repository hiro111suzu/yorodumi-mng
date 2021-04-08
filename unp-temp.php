<?php
require_once( "unp-common.php" );

define( 'MAXNUM', 100 );
//define( 'MAXNUM', 5 );

//. download
$cnt = 0;

foreach ( gzfile( FN_ALL_UNPIDS ) as $id ) {

	$id = trim( $id );
	if ( $id == '' ) continue;
	if ( strlen( $id ) < 5 ) continue;

	_count( 1000 );
	$fn_json = _fn( 'unp_json', $unp_id );
	$fn_xml = _fn( 'unp_xml', $id );
	if ( ! file_exists( $fn_xml) ) {
//		_pause( "$id: xmlなし" );
		continue;
	}
	if (
		file_exists( $fn_json )
		&&
		200 < filesize( $fn_json ) 
	) {
//		_mpause( "$id: json バイト数 = ". filesize( $fn_json ) );
		continue;
	}
	++$cnt ;
	_m( "$id: ($cnt) json バイト数 = ". filesize( $fn_json ) );
	

	_del( $fn_json );
	_del( $fn_xml );

	_m( "$id: bad json - $cnt" );
}


