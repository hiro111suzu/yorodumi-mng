<?php
require_once( 'vapros-common.php' );

//. unp2reactome
$data = [];
foreach ( _idloop( 'unp_json' ) as $fn ) {
	_count( 1000 );
	$unp_id = _fn2id( $fn ) ;
	foreach ( (array)_json_load2( $fn )->dbref->Reactome as $c )
		$data[ $unp_id ][] = $c[0];
}
_json_save( FN_UNP2REACTOME, $data );
