<?php
//. misc init
require_once( "commonlib.php" );
require_once( 'taxo-common.php' );
define( 'TSV_REP', _tsv_load2( FN_TSV_REP ) );
$o_sql = new cls_sqlite( 'taxoid' );

//. emdb

$data = [];
_count();
foreach ( _idloop( 'emdb_old_json' ) as $fn ) {
	$id = _fn2id( $fn );
	_count( 1000 );
	$json = _json_load2( $fn );
	foreach ( (array)$json->sample->sampleComponent as $v1 ) {
		if ( ! is_object( $v1 ) ) continue;
		if ( ! is_object( $v1->virus ) ) continue;
		$virus = (string)$v1->virus->sciSpeciesName;
		$host = (string)$v1->virus->natSource->hostSpecies
			?: (string)$v1->virus->natSource->hostCategory;
		$host = TSV_REP['emdb_host_rep'][ strtolower($host) ] ?: $host;
		if ( $host == $virus ) continue;
		if ( ! $virus || ! $host ) continue;

		$virus = $o_sql->qcol([
			'select' => 'id',
			'where' => 'name='. _quote( $virus )
		])[0] ?: $virus;
		if ( TSV_REP['emdb_host_wrong_pair'][ strtolower($virus) ] == $host )
			continue;
		
		$data[ $virus ][ $host ] = 1;
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

