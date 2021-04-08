<?php
require_once( "sem-common.php" );
define( 'SARS2_PROTEINS', [
'P0DTD1' ,
'P0DTC1' ,
'P0DTC2' ,
'P0DTC7' ,
'P0DTC3' ,
'P0DTC9' ,
'P0DTD2' ,
'P0DTC5' ,
'P0DTC4' ,
'P0DTC6' ,
'P0DTD8' ,
'P0DTC8' ,
'P0DTD3' ,
'A0A663DJA2' ,
]);

$data = [];
$sp_x = [];
//. main loop
foreach ( _json_load( FN_IDLIST ) as $id ) {
	$json = _json_load2([ 'qinfo', $id ]);

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
	_m( "$id: $met" );
	foreach ( $json->ref as $c ) {
		if ( $c[0] != 'UNP' ) continue;
		if ( ! in_array( $c[1], SARS2_PROTEINS ) ) continue;
		++ $data[ $c[1] ][ $met ];
		if ( $c[1] == 'P0DTC2' && $met == 'x-ray' )
			$sp_x[] = $id;
	}
}
$data2 = [];
foreach ( $data as $unp => $c ) {
	$data2[ $unp ] = [
		$c[ 'x-ray' ] ?: '0' ,
		$c[ 'em' ] ?: '0' ,
		$c[ 'other' ] ?: '0' ,
	];
}

ksort( $data2 );
_kvtable( $data2 );
_save_as_tsv( DN_SEMINAR. '/unp_met.tsv', [ 'unp', 'x-ray', 'em', 'other' ], $data2 );

file_put_contents( DN_SEMINAR. '/xray_spike.txt', implode( "\n", $sp_x ). "\n" );
