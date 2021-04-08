<?php 
require_once dirname(__FILE__) . '/commonlib.php';

//$metname
//'X-RAY DIFFRACTION'


$data = [];
foreach ( _idloop( 'pdb_add' ) as $fn ) {
	_count( 100 );
	$json = _json_load2( $fn );
	$y = substr( $json->rdate, 0, 4 );

/*
	$id = _fn2id( $fn );
	$y2 = substr(
		_json_load2( _fn( 'pdb_json', $id ) )->database_PDB_rev[0]->date,
		0, 4 
	);
	if ( $y != $y2 ) {
		_m( "$id: $y != $y2" );
	}
*/
	++ $data[ $y ][ 'all' ];
	foreach ( [ 4, 5, 6, 7 ] as $lim ) {
		if ( $lim <= $json->reso ) continue;
		++ $data[ $y ][ $lim ];
	}
}
//die();
ksort( $data );

$out = [ implode( "\t", [ 'year', 'all', 4, 5, 6, 7 ] ) ];
foreach ( $data as $y => $d ) {
	$out[] = implode( "\t", [ $y, $d[ 'all' ], $d[4], $d[5], $d[6], $d[7] ] );
}
$out = implode( "\n", $out );
file_put_contents( 'stat3.tsv', $out );
_m( $out );

