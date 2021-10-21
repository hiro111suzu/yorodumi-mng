<?php
//. init
require_once( 'commonlib.php' );
require_once( 'omo-common.php' );

define( 'COMPOS_NORM', 65535 * 5 );

$_filenames += [
	'omofh_data'  => DN_PREP. '/omofh/data_<id>.json.gz' ,
	'omofh_doing' => DN_PREP. '/omofh/doing/data_<id>.json.gz' ,
	'omofh_done'  => DN_PREP. '/omofh/done/data_<id>.json.gz' ,
//	'omofh_doing' => DN_PREP. '/omofh/doing/<id>' ,
	
];

//. prep
$profdb_k = new cls_sqlite( 'profdb_k' );

//. main
foreach ( _idloop( 'omofh_data' ) as $fn_in ) {
	if ( ! file_exists( $fn_in ) ) continue;
	$num = _numonly( basename( $fn_in, '.json.gz' ) );
	_line( "dataset #$num" );
	$fn_doing = _fn( 'omofh_doing', $num );
	$fn_out   = _fn( 'omofh_done' , $num );
	rename( $fn_in, $fn_doing );
	$data = _json_load( $fn_doing );
	if ( ! $data ) {
		unlink( $fn_doing );
		continue;
	}
	foreach ( array_keys( $data ) as $ids ) {
		$id = explode( ':', $ids );
		$compos0 = _get_compos_data( $id[0] ); 
		$compos1 = _get_compos_data( $id[1] ); 
		if ( $compos0 && $compos1 ) {
			$sum = 0;
			foreach ( $compos0 as $num => $v ) {
				$sum += abs( $v - $compos1[ $num ] );
			}
			$sum = 1 - $sum / COMPOS_NORM;
		} else {
			$sum = 10;
		}
		$data[ $ids ]['compos'] = $sum;
//		_m( "$ids: $sum" );
		echo '-';
	}
	_json_save( $fn_out, $data );
	_del( $fn_doing );
//	break;
}

function _get_compos_data( $id ) {
	return _bin2compos( _ezsqlite([
		'dbname' => 'profdb_k' ,
		'select' => 'compos' ,
		'where' => [ 'id', $id ],
	]));
}


