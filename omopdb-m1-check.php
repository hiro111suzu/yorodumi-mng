vq30 と vq50 の位置がおかしいデータを探す

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

//. メイン
$count = 0;
foreach ( _idloop( 'pdb_vq50' ) as $fn_vq50 ) {
	if ( _count( 10000, 0 ) ) break;
//	if ( _count( 100, 100 ) ) break;

	$id = _fn2id( $fn_vq50 );

	//- 座標
	$vq50 = _getcrd( $fn_vq50 );
	$fn_vq30 = strtr( $fn_vq50, [ '-50.pdb' => '-30.pdb' ] );
	if ( ! file_exists( $fn_vq30 ) )
		continue;
	$vq30 = _getcrd( $fn_vq30 );

	//- 重心
	$cent50 = _center( $vq50 );
	$cent30 = _center( $vq30 );

	//- 平均距離
	$avdis = _avdis( $vq50, $cent50 );
	$avdis30 = _avdis( $vq30, $cent30 );
	$sizerat = $avdis30 < $avdis ? $avdis / $avdis30 : $avdis30 / $avdis;
//	_m( $sizerat );

	if ( $sizerat > 1.2 )
		_m( "$id: 大きさが結構違うみたい $sizerat" );

	//- 重心感距離
	$dist = _udist( $cent50, $cent30 );
	$rat = $dist / $avdis;

//	if ( $rat < 0.2 or 0.2 < $rat) continue;
	if ( $rat < 0.14 ) continue;

	//- 出力
	_m(  "$id: $rat" );
	++ $count;

	if ( in_array( 'del', $argv ) ) {
		_del( $fn_vq50 );
		_del( $fn_vq30 );
		_del( _fn( 'altmodel', $id ) );
	} else {
//		_m( file_exists( _fn( 'altmodel', $id ) )
//			? "altodelある"
//			: "altmodelない"
//		);
	}
}
_line( "ダメデータの数: " . $count );

//. 関数
function _center( $crd ) {
	$cnt = count( $crd );
//	_die( array_column( $crd, 'x' ) );
	return [
		'x' => array_sum( array_column( $crd, 'x' ) ) / $cnt,
		'y' => array_sum( array_column( $crd, 'y' ) ) / $cnt,
		'z' => array_sum( array_column( $crd, 'z' ) ) / $cnt
	];
}

function _udist( $c1, $c2 ) {
	return sqrt(
		pow( $c1[ 'x' ] - $c2[ 'x' ], 2 ) +
		pow( $c1[ 'y' ] - $c2[ 'y' ], 2 ) +
		pow( $c1[ 'z' ] - $c2[ 'z' ], 2 )
	);
}

function _avdis( $crd, $center ) {
	$dist = 0;
	foreach ( $crd as $atom )
		$dist += _udist( $atom, $center );
	return $dist / count( $crd );
}

