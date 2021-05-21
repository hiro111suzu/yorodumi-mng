<?php
require_once( "commonlib.php" );
_mkdir( $dn = DN_DATA . '/pdb/qinfo' );

$info = [];
$fn_info = DN_PREP . '/oddpdb.txt';
$ids = [];
$typmet = [
	'ELECTRON CRYSTALLOGRAPHY',
	'ELECTRON MICROSCOPY',
	'EPR',
	'FIBER DIFFRACTION',
	'FLUORESCENCE TRANSFER',
	'INFRARED SPECTROSCOPY',
	'NEUTRON DIFFRACTION',
	'POWDER DIFFRACTION',
	'SOLID-STATE NMR',
	'SOLUTION NMR',
	'SOLUTION SCATTERING',
	'THEORETICAL MODEL',
	'X-RAY DIFFRACTION'
];


//. main loop
foreach ( glob( _fn( 'pdb_json', '*' ) ) as $fn ) {
	_count( 1000 ); 
	$id = substr( basename( $fn ), 0, 4 );
	$fn_out = "$dn/$id.json";
	if ( _newer( $fn_out, $fn ) ) continue;

	$json = _json_load2( $fn );
	$out = [];

	_basicinfo();
//	_json_save( $fn_out, $out );
}

//. basciinfo
function _basicinfo() {
	global $id, $json, $out, $ids, $typmet;
	$met = [];

	foreach ( $json->exptl as $j ) {
		$m = $j->method;
		if ( ! in_array( $m, $typmet ) )
			_info( "$id: odd method: $m" );
		$met[] = $m;
	}
	if ( count( $met ) > 1 )
		_info( "$id: multi exptl: " . _imp( $met ) );
}

//. 
function _info( $s ) {
	global $info; 
	_m( $s );
	$info[] = $s;
	
}

file_put_contents( $fn_info, implode( "\n", $info ) );
