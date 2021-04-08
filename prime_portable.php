<?php
require_once( "commonlib.php" );

//. init
define( 'DN_STRUCT', '../prime_portable/portable_data' );
define( 'DN_INFO'  , '../prime_portable/prime2data' );

//. main
foreach ( glob( DN_INFO. '/*.txt' ) as $fn ) {
	$id = basename( $fn, '.txt' );

	//.. chem
	if ( strlen( $id ) < 4 ) {
		$url = DN_DATA. "/chem/cif/". strtoupper( $id ). ".cif.gz";
		$fn = "$id.cif.gz";
	} else if ( _numonly( $id ) == $id ) {
		$url = DN_DATA. "/emdb/media/$id/ym/1.obj";
		$fn = "$id.obj";
	} else {
		$url = "ftp://ftp.pdbj.org/mine2/data/mmjson/all/$id.json.gz";
		$fn = "$id.json.gz";
	}

	_m( $id );
//	_m( $url. '=>'. DN_STRUCT. "/$fn" );
	copy( $url, DN_STRUCT. "/$fn" );
}

