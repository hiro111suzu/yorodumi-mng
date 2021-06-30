<?php
require_once( "commonlib.php" );
define( 'FN_ID_TABLE', DN_PREP. '/empiar_id_table.json.gz' );
define( 'FN_OUT', DN_DATA. '/empiar_info.json' );

//. 
$output = _json_load( FN_ID_TABLE );

foreach ( _json_load( FN_ID_TABLE ) as $emp_id => $data ) {
	_count( 100 );
	$add = [];
	foreach ( $data['emdb'] as $emdb_id ) {
		foreach ( _ezsqlite([
			'dbname' => 'main' ,
			'select' => [ 'microscope', 'acc_vol', 'detector', 'resolution' ] ,
			'where'  => [ 'db_id', "emdb-$emdb_id" ] ,
		]) as $k => $v ) {
			$add[ $k ][] = $v;
		}
	}
	foreach ( $add as $k => $v ) {
		$v = array_values( array_unique( (array)$v ) );
		$output[ $emp_id ][ $k ] = $v ?: [];
		if ( $k != 'resolution' )
			_cnt2( _imp( $v ), $k );
	}
}
_cnt2();
//ksort( $data );
//_comp_save( DN_DATA . '/emdb/empiar.json.gz', $data );

_json_save( FN_OUT, $output );
