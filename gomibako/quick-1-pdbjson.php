title
descriptor
method
resolution

assembly

related

ribosome?

num chains
num atoms

<?php
include "commonlib.php";
define( 'DN_OUT', DN_DATA . '/pdb/quick' );
_mkdir( DN_OUT ); 

$ids = [];
foreach ( glob( _fn( 'pdb_json', '*' ) ) as $pn ) {
//	if ( _count( 1000, 10000 ) ) break;
	$json = _json_load2( $pn );

	//- ribosome?
	$polycnt = 0;
	foreach ( $json->entity as $e ) {
		if ( $e->type != 'polymer' ) continue;
		++ $polycnt;
	}
	if ( $polycnt < 20 ) continue;
	$ids[ $json->_id ] = $polycnt;
}
arsort( $ids );
foreach ( $ids as $id => $num ) {
	_m( "$id:$num" );
}
