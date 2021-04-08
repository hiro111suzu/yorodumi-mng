<?php
require_once( "sem-common.php" );

$data = [];
//. main loop
foreach ( _idloop( 'qinfo' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) break;
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );
	$met = 'other';
	$m = $json->method;
	if ( $m == [ 'ELECTRON MICROSCOPY' ] ||
		$m == [ 'ELECTRON CRYSTALLOGRAPHY' ]
	) {
		$met = 'em';
	} else if (
		$m == [ 'X-RAY DIFFRACTION' ]
	){ 
		$met = 'x-ray';
	}
	++ $data[ explode( '-', $json->rdate )[0] ][ $met ];
}
ksort( $data );
$data2 = [];
foreach ( $data as $y => $v ) {
	$data2[ $y ] = [
		$v['em'] ?: '0' ,
		$v['x-ray'] ?: '0' ,
		$v['other'] ?: '0' ,
	];
}
_save_as_tsv( DN_SEMINAR. '/y-met-stat.tsv', [ 'year', 'EM', 'X-ray', 'other' ], $data2 );
