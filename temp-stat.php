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
	if ( _count( 1000, 0 ) ) break;
//	$id = _fn2id( $fn );
//	_m( "$id: $fn" );
	$json = _json_load2( $fn );
	$y = substr( $json->rdate, 0, 4 );
	foreach ( $json->method as $j ) {
		++ $data[ $y ][ $metrep[ $j ] ?: 'others' ];
	}
	++ $data[ $y ][ 'total' ];	
//	break;
}
ksort( $data );

//. 総数
$out = [ implode( "\t", [ 'Year', 'X-ray', 'NMR', 'EM', 'others' ] ) ];
foreach ( $data as $y => $d ) {
	$out[] = implode( "\t", [ $y, $d[ 'X-ray' ], $d[ 'NMR' ], $d[ 'EM' ], $d[ 'others' ], $d[ 'total' ] ] );
}
$out = implode( "\n", $out );
file_put_contents( 'stat.tsv', $out );
_m( $out );

//. 割合
/*
$out = [ implode( "\t", [ 'Year', 'X-ray', 'NMR', 'EM', 'others' ] ) ];
foreach ( $data as $y => $d ) {
	
	$out[] = implode( "\t", [ $y, $d[ 'X-ray' ], $d[ 'NMR' ], $d[ 'EM' ], $d[ 'others' ], $d[ 'total' ] ] );
}
$out = implode( "\n", $out );
file_put_contents( 'stat.tsv', $out );
*/


