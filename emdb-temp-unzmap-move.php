<?php
//. init
require_once( "commonlib.php" );

define( 'DN_UNZIP_MAP', '/data/unzipped_maps' );

foreach( _idlist( 'emdb' ) as $id ) {
//	if ( substr( $id, -3 ) != '005' ) continue;
	$fn_uz = DN_UNZIP_MAP. '/'. ( strlen( $id ) < 5 ? 0 : substr( $id, 0, 1 ) )
		. "/emd_$id.map" ;
	$fn_slink = _fn( 'map', $id );
	if ( ! file_exists( $fn_uz ) ) {
		_m( "$id: nomap" );
		continue;
	}
//	_del( $fn_slink );
//	_m( "$id: $fn_uz => $fn_slink" );
	exec( "rm $fn_slink; ln -s $fn_uz $fn_slink" );

//	rename( $fn_old, $fn_new );
}

