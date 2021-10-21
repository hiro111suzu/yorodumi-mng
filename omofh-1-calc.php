<?php
//. init
require_once( 'commonlib.php' );
require_once( 'omo-common.php' );

define( 'FUNC_ITEMS', [ 'ec', 'go', 'rt' ] );
define( 'COMPOS_NORM', 65535 * 5 );

define( 'SIZE_LIM', $argv[1] ?: 0 );
$dn = DN_PREP. '/omofh';
_mkdir( "$dn/". SIZE_LIM );
$_filenames += [
	'omofh_data'  => "$dn/". SIZE_LIM. "/data_<id>.json.gz" ,
	'omofh_doing' => "$dn/doing/". SIZE_LIM. "-<id>" ,
];

//. prep
$profdb = new cls_sqlite( 'profdb_ss' );
_set_ids_all();

define( 'COUNT_IGN', _count_ign( _get_prof( $ids_all[0] ) ) );
define( 'SAS_MODEL_ID', _json_load( DN_DATA. '/sas/subdata.json.gz' )[ 'mid' ] );

//. main

while ( true ) {
	if ( file_exists( DN_PREP. '/omofh/stop' ) ) {
		_m( 'stopファイル検出', 1 );
		break;
	}
	//.. 新しいデータファイル名
	$num = 1;
	while ( true) {
		$fn_data  = _fn( 'omofh_data', $num );
		$fn_doing = _fn( 'omofh_doing', $num );
		if (
			! file_exists( $fn_data ) &&
			! file_exists( $fn_doing )
		) break;
		++ $num;
	}
	touch( $fn_doing );
	_line( "ラウンド #$num" );

	//.. 過去データ
	$prev_data = [];
	foreach ( _idloop( 'omofh_data' ) as $pn ) {
		if ( $pn == $fn_data ) continue;
		$new = _json_load( $pn );
		if ( ! $new ) {
			unlink( $pn );
		} else {
			$prev_data = array_merge( $prev_data,  );
		}
	}
	_m( count( $prev_data ). ' 個の過去データ読み込み' );

	//.. main loop start
	$count = 0;
	$data = [];
	while ( true ) {
		//.. ID決定
		$omo_id = [
			'0' => array_shift( $ids_all ) ,
			'1' => array_shift( $ids_all ) ,
		];
		if ( count( $ids_all ) == 0 ) {
			_m( 'ID枯渇', -1 );
			_set_ids_all();
			break;
		}
		sort( $omo_id );
		if ( $prev_data[ implode( ':', $omo_id ) ] ) continue;
		$str_id0 = _omoid2strid( $omo_id[0] );
		$str_id1 = _omoid2strid( $omo_id[1] );
		
		//.. omokage
		$shape_score = _getscore(
			_get_prof( $omo_id[0] ) ,
			_get_prof( $omo_id[1] ) ,
			COUNT_IGN
		);
		if ( $shape_score < 0.7 ) continue;
		
		//.. fh, compos
		$func = $hom = 0;
		extract( _fh_score( $str_id0, $str_id1 ) );
		if ( ! $func && ! $hom ) continue;
		$compos = _compos_score( $omo_id[0], $omo_id[1] );

		//.. result
		$key = $omo_id[0]. ':'. $omo_id[1];
		_m( implode( "\t", [
			"#$count",
			$key,
			$shape_score,
			round( $func, 5 ),
			round( $hom, 5 ) ,
			$compos
		]) );
		$data[ $key ] = [
			'shape' => $shape_score ,
			'func' => $func ,
			'hom' => $hom ,
			'compos' => $compos
		];
		++ $count;
		if ( 999 < $count ) break;
	}
	_json_save( $fn_data, $data );
	_del( $fn_doing );
}

//_m( json_encode( _fh_score( $id1, $id2 ), JSON_PRETTY_PRINT ) );

//. func
//.. _set_ids_all
function _set_ids_all() {
	_m( 'IDリスト作成' );
	global $ids_all, $profdb;
	$ids_all = $profdb->qcol( SIZE_LIM ? [
		'select' => 'id' ,
		'where' => ''
			. 'pca1 > '. SIZE_LIM. ' OR '
			. 'pca2 > '. SIZE_LIM. ' OR '
			. 'pca3 > '. SIZE_LIM
		,
	]: [
		'select' => 'id'
	]);
	shuffle( $ids_all );
}

//.. _fh_score
function _fh_score( $id1, $id2 ) {
	list( $items1, $score1_f, $score1_h ) = _get_items( $id1 );
	list( $items2, $score2_f, $score2_h ) = _get_items( $id2 );
	if ( ! $items1 || ! $items2 ) return false;

	$sum = [];
	foreach ( $items1 as $item ) {
		if ( ! in_array( $item, $items2 ) ) continue;
		$sum[
			in_array( explode( ':', $item, 2 )[0], FUNC_ITEMS ) ? 'f' : 'h'
		] += _ezsqlite([
			'dbname' => 'dbid2strids' ,
			'select' => 'score' ,
			'where'  => [ 'dbid', $item ]
		]);
	}
	$sc_f = $score1_f + $score2_f;
	$sc_h =  $score1_h + $score2_h;
	return [
		'func' => $sc_f ? $sum['f'] / $sc_f * 2 : 0, 
		'hom'  => $sc_h ? $sum['h'] / $sc_h * 2 : 0,
	];
}

//.. _get_items
function _get_items( $str_id ) {
	list( $items, $score_f, $score_c ) = array_values( _ezsqlite([
		'dbname' => 'strid2dbids' ,
		'select' => [ 'dbids', 'score_f', 'score_h + score_c' ] ,
		'where'  => [ 'strid', $str_id ] ,
	]));
	return [ explode( '|', $items ), $score_f, $score_c ];
}

//.. _get_prof
function _get_prof( $id ) {
	global $profdb;
	return _bin2prof( $profdb->qcol([
		'select' => 'data' ,
		'where' => "id='$id'" ,
	])[0] );
}

//.. _omoid2strid
function _omoid2strid( $id ) {
	$idp = explode( '-', $id, 2 );
	//- pdb
	if ( $idp[1] )
		return $idp[0];

	//- saamodel
	if ( substr( $id, 0, 1 ) == 's' )
		return SAS_MODEL_ID[ _numonly( $id ) ];

	//- emdb
	return $id;
}

//.. _compos_
function _compos_score( $id1, $id2 ) {
	$compos = [];
	foreach ( [ $id1, $id2 ] as $num => $id ) {
		$compos[ $num ] = _bin2compos( _ezsqlite([
			'dbname' => 'profdb_k' ,
			'select' => 'compos' ,
			'where' => [ 'id', $id ],
		]));
//		print_r([ $id => $compos[ $num ]] );
	}
//	_pause([ $id1 => $compos[0], $id2 => $compos[1] ]);
	if ( $compos[0] && $compos[1] ) {
		$sum = 0;
		foreach ( $compos[0] as $num => $v ) {
			$sum += abs( $v - $compos[1][ $num ] );
		}
		$sum = 1 - $sum / COMPOS_NORM;
	} else {
		$sum = 10;
	}
	return $sum;
}
