<?php 
require_once dirname(__FILE__) . '/commonlib.php';

//$metname
//'X-RAY DIFFRACTION'

$metrep = [
    'X-RAY DIFFRACTION' => 'X-ray' ,
    'SOLUTION NMR' => 'NMR', 
    'SOLID-STATE NMR' => 'NMR' ,
    'ELECTRON CRYSTALLOGRAPHY' => 'EM', 
    'ELECTRON MICROSCOPY' => 'EM', 
];

$data = [];

foreach ( _idloop( 'qinfo' ) as $fn ) {
	_count( 1000 );
//	$id = _fn2id( $fn );
//	_m( "$id: $fn" );
	$json = _json_load2( $fn );
	$y = substr( $json->ddate, 0, 4 );
	foreach ( $json->method as $m ) {
		++ $data[ $y ][ $metrep[ $m ] ?: 'others' ];
	}
//	break;
}
ksort( $data );

$out = [ implode( "\t", [ 'Year', 'X-ray', 'NMR', 'EM', 'others' ] ) ];
foreach ( $data as $y => $d ) {
	$out[] = implode( "\t", [ $y, $d[ 'X-ray' ], $d[ 'NMR' ], $d[ 'EM' ], $d[ 'others' ] ] );
}
$out = implode( "\n", $out );
file_put_contents( 'stat2.tsv', $out );
_m( $out );

