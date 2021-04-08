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

foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 1000, 0 ) ) break;
//	$id = _fn2id( $fn );
//	_m( "$id: $fn" );
	$json = _json_load2( $fn );
	$y = substr( $json->database_PDB_rev[0]->date, 0, 4 );
	foreach ( $json->exptl as $j ) {

//		++ $data[ $y ][ $m ];
		++ $data[ $y ][ $metrep[ $j->method ] ?: 'others' ];
	}
//	break;
}
ksort( $data );

$out = [ implode( "\t", [ 'Year', 'X-ray', 'NMR', 'EM', 'others' ] ) ];
foreach ( $data as $y => $d ) {
	$out[] = implode( "\t", [ $y, $d[ 'X-ray' ], $d[ 'NMR' ], $d[ 'EM' ], $d[ 'others' ] ] );
}
$out = implode( "\n", $out );
file_put_contents( 'stat.tsv', $out );
_m( $out );

