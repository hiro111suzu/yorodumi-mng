<?php
include "commonlib.php";
define( 'DN_UNZIP_MAP', '/data/unzipped_maps' );

$fn = '/emd_<id>.map';
$_filenames += [
	'unzipped_map'	=> DN_UNZIP_MAP. $fn ,
	'media_map'		=> DN_EMDB_MED. "/<id>$fn" ,
];

//. mainloop
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	$from = _fn( 'unzipped_map', $id );
	$to   = _fn( 'media_map', $id );
	if ( ! file_exists( $from ) ) {
		_m( "$id: no map !" );
		continue;
	}
	exec( "ln -fs $from $to" );
//	_pause( "ln -fs $from $to" );
/*
	if ( ! file_exists( $to ) ) {
		_m( "$id: no media map !" );
		continue;
	}
*/	
	
}
