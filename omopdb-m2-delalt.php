<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

_add_fn([
	'vq_error'	=> DN_OMODATA . '/error/<name>.txt'  //- <s> = id-type
]);

$count = 0;
foreach ( glob( _fn( 'altmodel', '*' ) ) as $altfn ) {
//	if ( _count( 100, 100 ) ) break;
	$id = basename( $altfn, '.pdb.gz' );
	if ( ! file_exists( _fn( 'vq_error', $id ) ) ) continue;
	if ( $size = filesize( $altfn ) == 0 ) continue;
	_m( "$id: " . filesize( $altfn ) );
	_del( $altfn );
	++ $count;
//	count( _file( $altfn ) )

//	if ( file_exists( _fn( 'pdb_vq50', $id ) ) ) continue;

//	_m( "no vq50 file for $id" );
//	_del( $altfn );
//	++ $count;
}
_m( "あやしいaltmodel: $count" );


