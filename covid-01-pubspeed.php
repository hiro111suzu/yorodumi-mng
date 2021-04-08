<?php
//. init
require_once( "commonlib.php" );

//. 

$data = [];
foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) break;
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );
	$date_exp = $json->diffrn_detector[0]->pdbx_collection_date;
	if ( ! $date_exp || ! _instr( '-', $date_exp ) ) continue;
//	$date_rel = $json->pdbx_audit_revision_history[0]->revision_date;
	$date_dep = $json->pdbx_database_status[0]->recvd_initial_deposition_date;
	$days = ( strtotime( $date_dep ) - strtotime( $date_exp ) ) / 3600 / 24;
	if ( $days < 0 ) continue;
//		_pause( "$id: $days ($date_exp -> $date_dep)" );
//	if ( 7000 < $days )
//		_pause( "$id: $days ($date_exp -> $date_dep)" );
	if ( ! is_int( $days ) ) continue;
//		_pause( "$id: $days ($date_exp -> $date_dep)" );
//
//	_pause( "$date_exp -> $date_dep => $days" );
	$data[ substr( $date_dep, 0, 4 ) ][] = $days;
}
ksort( $data );

$tsv = '';
foreach ( $data as $year => $days ) {
	$cnt = count( $days );
	$avg = array_sum( $days ) / $cnt;
	$sum = 0;
	foreach ( $days as $val ) {
		$sum += pow( $avg - $val, 2 );
	}
	$stdev = sqrt( $sum / $cnt );
	$min = min( $days );
	$max = max( $days );
	$tsv .= implode( "\t", [ $year, $avg, $stdev, $min, $max, $cnt ] ). "\n";
}
_m( $tsv );
file_put_contents( DN_PREP. '/covid/dep_speed.tsv', $tsv );

