<?php
//. misc init
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );
define( 'TSV_REP', _tsv_load2( FN_TSV_REP ) );
$o_sql = new cls_sqlite( 'taxoid' );

//. emdb

$data = [];
_count();
foreach ( _idloop( 'emdb_json' ) as $fn ) {
	$id = _fn2id( $fn );
	_count( 1000 );
	foreach ( (array)_emdb_json3_rep( _json_load2( $fn ) )->sample->supramolecule as $c ) {
		if ( ! $c->sci_species_name ) continue;
		$virus = $c->sci_species_name;
		$virus = $o_sql->qcol([
			'select' => 'id',
			'where' => 'name='. _quote( $virus )
		])[0] ?: $virus;
		foreach ( (array)$c->natural_host as $h ) {
			$host = $h->organism ?: $h->synonym_organism;
			if ( $host == $virus ) continue;
			$host = TSV_REP['emdb_host_rep'][ strtolower($host) ] ?: $host;
			if ( ! $virus || ! $host ) continue;
			if ( TSV_REP['emdb_host_wrong_pair'][ strtolower($virus) ] == $host )
				continue;
			$data[ $virus ][ $host ] = 1;
//			_pause( "$id: $virus -> $host" );
		}
//		if ( $virus == 110829 )
//			_m( "$id: $host" );
//		_m( "$virus -> $host" );
	}
}

foreach ( TSV_REP['virus2host'] as $virus => $host ) {
	$virus = $o_sql->qcol([
		'select' => 'id',
		'where' => 'name='. _quote( $virus )
	])[0] ?: $virus ;
	$data[ $virus ][ $host ] = 1 ;
	_m( "$virus -> $host" );
}

//_pause( $data[110829] );
foreach ( $data as $v => $h ) {
	$data[ $v ] = array_keys( $h );
}


//_pause( $data[110829] );
_json_save( FN_VIRUS2HOSTS, $data );

