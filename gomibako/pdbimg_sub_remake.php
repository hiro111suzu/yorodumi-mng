<?php
include "commonlib.php";

$infodn = DN_PREP . '/info_asb';
$imgdn  = DN_DATA . '/pdb/img_dep';

//. IDリスト作り直し
if ( $argv[1] == 'all' ) {
	_mkdir( DN_PREP . '/ids_remake/' );
	foreach ( _file( DN_DATA . '/allpdbids.txt' ) as $id ) {
		$n = _idfn( $id );
		if ( file_exists( $n ) ) continue;
		touch( $n );
	}
	die( '全IDリスト更新完了' );
}

//. 古いやつから
$stopfn = DN_PROC . '/stop_reimg';
if ( $argv[1] == 'stop' ) {
	touch( $stopfn );
	die( '画像作成ルーチン停止フラグ' );
}


if ( $argv[1] == 'old' ) {
	while ( ture ) {
		if ( file_exists( $stopfn ) ) {
			_line( 'ストップフラグにより停止' );
			_del( $stopfn );
			break;
		}
		$idfiles = glob( _idfn( '*' ) );
		if ( count( $idfiles ) == 0 ) {
			_line( '全て処理完了' );
			break;
		}
		$ar = [];
		foreach ( $idfiles as $fn ) {
			$id = basename( $fn, '.txt' );
			$ar[ $id ] = filemtime( $fn );
		}
		asort( $ar );
		$ar = array_slice( array_keys( $ar), 0, 100 );
		_m( '更新するID: ' . _imp( $ar ) );

		//- 画像を消して実行
		foreach ( $ar as $id )
			_delimgs( $id );
		_makeimage();
	}
	die( '完了' );
}

//. ID指定
if ( count( $argv ) > 1 ) {
	foreach ( $argv as $id ) {
		if ( strlen( $id ) > 5 ) continue;
		_delimgs( strtolower( $id ) );
	}
	_makeimage();
}

//. function
function _idfn( $id ) {
	return DN_PREP . "/ids_remake/$id.txt";
}

function _delimgs( $id ) {
	_del( DN_PREP . "/info_asb/$id.json" );
	_del( DN_DATA . "/pdb/img_dep/$id.jpg" );
	_del( DN_DATA . "/pdb/img/$id.jpg" );
	_del( _idfn( $id ) );
}

function _makeimage() {
	_php( 'allpdb-5' );
	_php( 'allpdb-6', 'no_check' );
	_php( 'allpdb-7' );
}
