<?php
require_once( "sem-common.php" );

define( 'DN_SDATA', DN_SEMINAR. '/sdata' );
_mkdir( DN_SDATA );
$_filenames += [
	'sdata' => DN_SDATA. '/<id>.json' 
];

_m( _date_dif( '2020-03-01', '2020-02-01' ) );
//. main loop
/*
foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) _break();
	$id = _fn2id( $fn );
	$fn_out = _fn( 'sdata', $id );
	if ( FLG_REDO )
		_del( $fn_out );
	if ( _newer( $fn_out, $fn ) ) continue;

	$json = _json_load2( $fn );
	$date_dif = $json->diffrn_detector[0]->pdbx_collection_date;
	$date_em  = $json->em_imaging[0]->date;
	if ( $date_dif ) {
		$out = [
			'type' => 'diffrn' ,
			'date' => $date_dif ,
		];
	} else if ( $date_em ){
		$out = [
			'type' => 'em' ,
			'date' => $date_em ,
		];
	} else {
		$out = [];
	}
	if ( $out )
		_json_save( $fn_out, $out );
}
*/
//. get days
$met = [];
$data = [];
foreach ( _idloop('qinfo') as $fn ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$id = _fn2id( $fn );
	$sinfo = _json_load2( _fn( 'sdata', $id ) );
	if ( ! $sinfo->date ) continue;
	_cnt( 'both' );
	_cnt( $sinfo->type );
	$ddate = _json_load2( $fn )->ddate;
	$y = substr( $ddate, 0, 3 ). '0';
	$dif = _date_dif( $ddate, $sinfo->date );
	if ( $dif < 0 || 3650 < $dif ) continue;
	if ( $dif < 5 )	
		_m( "$id: $dif" );
	$data[ $y ][] = $dif;
}
_cnt();
$lines = "year\tavg. days\t+/-\n";

//. histogram
_line( 'histogram' );
$histo = [];
foreach ( $data as $c ) foreach ( $c as $d ) {
	$d = floor( $d / 50 ) * 50;
	++ $histo[ $d ];
}
ksort( $histo );
$lines = '';
foreach ( $histo as $d => $num ) {
	$lines .= "$d\t$num\n";
}
_m( $lines );
file_put_contents( DN_SEMINAR. '/histo_datacol_dep.tsv', $lines );

//. decadely
_line( 'decadely' );
$lines = "decade\tdays\t+/-\n";
foreach ( $data as $year => $days_list ) {
	$cnt = count( $days_list );
	$avg = array_sum( $days_list ) / $cnt;
	$sum = 0;
	foreach ( $days_list as $d ) {
		$sum += pow( $avg - $d, 2 );
	}
	$sigma = sqrt( $sum / $cnt );
	$lines .= implode( "\t", [
		$year,
		round( $avg, 1 ),
		round( $sigma, 1 )
	]). "\n";
}
_m( $lines );
file_put_contents( DN_SEMINAR. '/days_col2dep.tsv', $lines );
