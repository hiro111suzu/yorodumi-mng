<?php
require_once( "commonlib.php" );

/*
//. main loop
foreach ( glob( _fn( 'pdb_json', '*' ) ) as $fn ) {
	if ( _count( 1000, 0 ) ) break;
	$id = substr( basename( $fn ), 0, 4 );
	$json = _json_load2( $fn );
	if ( ! $json->exptl ) _m( "$id: no method" );

}
*/

foreach ( glob( _fn( 'qinfo', '*' )) as $fn ){
	if ( _count( 1000, 0 ) ) break;
	$id = substr( basename( $fn ), 0, 4 );
	$json = _json_load2( $fn );	
	if ( count( $json->method ) == 0 )
		_m( "$id: no method" );
}
