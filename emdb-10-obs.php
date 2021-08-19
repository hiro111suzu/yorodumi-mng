<?php
require( "commonlib.php" );
$data = [];
define( 'FN_OUT', DN_DATA . '/emdb/emdb-obs.json.gz' );

$_filenames += [
	//- emdb
	'emdb_obs'	=> DN_EMDB_MR . '/obsoleted/EMD-<id>/header/emd-<id>.xml' ,
];

//. main
foreach ( _idloop( 'emdb_obs' ) as $fn ) {
	$bn = basename( $fn );
	if ( _instr( '-v', $bn ) ) continue;
	$id = _numonly( $bn );
	_m( basename( $fn ). ' -> '. $id );
	$xml = simplexml_load_file( $fn );

	//- author
	$x = $xml->deposition->authors;
	if ( 1 < count( (array)$x ) ) {
		$a = [];
		foreach ( $x as $c ) {
			$a[] = _x( $c );
		}
		$auth = _imp( $a );
	} else {
		$auth = _x( $x );
	}
		
	//- repid
	$repids = [];
	foreach ( (array)$xml->deposition->supersededByList as $k => $v ) {
		if ( $k != 'entry' ) continue;
		$repids[] = _numonly( $v );
	}

	$data[ $id ] = [
		'title'		=> _x( $xml->deposition->title ) ,
		'authors'	=> $auth ,
		'repids'	=> $repids ,
		'det'		=> _x( $xml->deposition->details ),
		'map'		=> _x( $xml->map->annotationDetails ),
		'sample'	=> _x( $xml->sample->name ),
		'date_dep' 	=> _x( $xml->deposition->depositionDate ),
		'date_obs' 	=> _x( $xml->deposition->obsoletedDate ),
	];
}

_comp_save( FN_OUT, $data );
_end();
