<?php
require_once( "commonlib.php" );
$ids = $cnt = [];

foreach ( _idloop( 'chem_json' ) as $fn ) {
	_count( 5000 );
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );
	foreach ( $json->chem_comp as $k => $v ) {
		if ( $v == '' ) continue;
		$ids[ $k ][] = $id;
		++ $cnt[ $k ];
	}
}
arsort( $cnt );
//kvtable( $cnt );

$data = [];
foreach ( $cnt as $k => $num ) {
	if ( 5000 < $num )
		$data[ $k ] = $num;
	else
		$data[ $k ] = $num. ' - '. _imp( array_slice( $ids[ $k ], 0, 10 ) );
}
_kvtable( $data );


