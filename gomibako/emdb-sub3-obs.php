<?php
require( "commonlib.php" );
$data = [];
$ofn = new cls_file( DN_DATA . '/emdb-obs.json.gz' );

_add_fn([
	//- emdb
	'emdb_obs'	=> DN_EMDB_MR . '/obsoleted/EMD-<id>/header/emd-<id>.xml' 
]);

//. main
foreach ( _idloop( 'emdb_obs' ) as $fn ) {
	$id = _numonly( basename( $fn ) );
	$xml = simplexml_load_file( $fn );
	
	//- author
	$x = $xml->deposition->authors;
	if ( 1 < count( $x ) ) {
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
//	_pause( $id . print_r( $data, true ) );
}

$ofn->compsave( $data, true );
