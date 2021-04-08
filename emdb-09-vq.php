EMDB VQ作成

<?php

//. init
require_once( "commonlib.php" );
require_once( 'lib-omo.php' );
_envset( 'situs' );
$blist = new cls_blist( 'emdb_vq' );

//- vq1とvq2、片方のみ作成指定
$vq1 = ! $args[ 'vq2' ];
$vq2 = ! $args[ 'vq1' ];

//- vqの数
//$vqnum = $argar[ 'vq' ] ?: 20 ;

//. start main loop
foreach( _idlist( 'emdb' ) as $id ) {
	if ( _idfilter( $id ) ) continue;
	$did = "emdb-$id";

	$fn_map = _fn( 'map4vq', $id );
	if ( ! file_exists( $fn_map ) ) continue;
	if ( filesize( $fn_map ) == 0 )
		$fn_map	= _fn( 'map', $id );

	foreach ( $vqnums as $vqnum ) {
//		_m( "vqの数: $vqnum", 1 );
		$fn_movinfo = _fn( 'movinfo'       , $id );
		$fn_vqpre	= _fn( "pre_vq$vqnum"  , $id );
		$fn_vq		= _fn( "emdb_vq$vqnum" , $id );

	//.. やるかやらないか
		if ( $blist->inc( $id ) ) {
			_del( $fn_vqpre, $fn_vq );
			continue;
		}

		if ( ! file_exists( $fn_movinfo )	) {
			_m( "$id: movinfoがない", 'red' );
			continue;
		}

		if ( _proc( "make-{$vqnum}-vq-emdb-$id" ) ) continue;

		if ( FLG_REDO )
			_del( $fn_vqpre, $fn_vq );

		//- vqがあっても古かったら消す
		if ( file_exists( $fn_vqpre ) ) {
			if ( filemtime( $fn_map ) > filemtime( $fn_vqpre ) )
				_del( $fn_vqpre, $fn_vq );
		}

		//- 表面レベル
		$thr = trim( _mng_conf( 'situsmap', $id. '_thr' ) )
			?: _json_load( $fn_movinfo )[1][ 'threshold' ]
		;
		if ( _mng_conf( 'situsmap', $id. '_invert' ) )
			$thr = $thr * -1;
		if ( $thr == '' )
			$thr = '0';

	//.. VQ pre
//		if ( $id == '6298' ) _m( "6298-$vqnum", 1 );

		if ( ! file_exists( $fn_vqpre ) ) {
			_line( 'VQ1 開始', "$id-$vqnum: thr: $thr (" . date( 'm/d H:i:s' ) . ')' );
			//- fn_map, fn_vq, fn_vqpre: ファイル名
			//- vqnum, surf
			_qvol([
				'fn_map' => $fn_map ,
				'fn_vq'  => $fn_vqpre ,
				'vqnum'  => $vqnum ,
				'surf'   => $thr
			]);
			_checkvqfile( $fn_vqpre, "$did: vq-pre $vqnum" );
		}

	//.. VQ fine
		if ( $vq2 && file_exists( $fn_vqpre ) && ! file_exists( $fn_vq ) ) {
			_line( 'VQ2 開始', "$id-$vqnum: thr: $thr (" . date( 'm/d H:i:s' ) . ')' );
			_qvol([
				'fn_map'	=> $fn_map ,
				'fn_vqpre'	=> $fn_vqpre ,
				'fn_vq'		=> $fn_vq ,
				'surf'		=> $thr
			]);
			_checkvqfile( $fn_vq, "$did: vq-fine $vqnum" );
		}
		_proc();

	}
}

//. なくなったエントリ対応

_delobs_emdb( 'emdb_vq30' );
_delobs_emdb( 'emdb_vq50' );
_delobs_emdb( 'pre_vq30' );
_delobs_emdb( 'pre_vq50' );

_end();

//. function
//.. _qvol
//- $fn_map, $fn_vq, $fn_vqpre: ファイル名
//- $vqnum, $surf
//- 定数 QVOL QVOL実行ディレクトリパス
function _qvol( $o ) {
	extract( $o );
	if ( ! defined( 'QVOL' ) ) {
		_m( 'qvol実行ファイルの位置が定義されていない' );
		$qvol = 'qvol';
	} else {
		$qvol = QVOL;
	}
	
	_run( "$qvol $fn_map $fn_vqpre $fn_vq", 
		$fn_vqpre != ''		//- input vectorあり?
		? [
			1, 				//- continue
			$surf ?: '1'  , //- 表面レベル
			1 ,				//- LBGする
			1 ,				//- 束縛しない
			1				//- コネクティビティ計算しない
		]
		:[
			1, 				//- continue
			$surf ?: '1' ,	//- 表面レベル
			$vqnum ?: '50' ,//- コードベクターの数
			1				//- コネクティビティ計算しない
		]
	);
}
