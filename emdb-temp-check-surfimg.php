<?php
//. init
require_once dirname(__FILE__) . '/commonlib.php';
$cnt = 0;
foreach ( _idloop( 'emdb_json' ) as $fn ) {
	$id = _fn2id( $fn );
	if ( file_exists( _fn( 'emdb_med', $id ). '/mapi/surf_x.jpg' ) )
		continue;
	if ( _branch(
		_emdb_json3_rep( _json_load2( $fn ) ) ,
		'map->contour[0]->level'
	)[0] == '' )
		continue;
	_m( "$id: 表面レベルがあるのに、表面図がない" );
	++ $cnt;
	exec( "rm -fr ". _fn( 'emdb_med', $id ). "/mapi" );
//	break;
}

_m( "数: ". $cnt );
