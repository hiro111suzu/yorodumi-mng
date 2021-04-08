セッションをコピーする

<?php
//. init
require_once( "commonlib.php" );
$post_cmd = '';
define( 'DN_CUR', basename( INIT_DIR ) );

//. パラメータ解釈
if ( INIT_DIR == __DIR__ ) { //- mngディレクトリ内、入力IDも指定
	$id_list = array_slice( $argv, 2 );
	list( $id_in, $num ) = explode( '-', $argv[1] );
	$num = $num ?: 2 ;

} else { //- mngディレクトリ外
	if ( $argv[1] == 'from' || $argv[1] == 'form' ) { //- from
		$id_list = [ DN_CUR ];
		list( $id_in, $num ) = explode( '-', $argv[2] );
		$num = $num ?: 2;
	} else if ( strlen( $argv[1] ) == 1 ) { //- 番号のみ入力
		$id_list = array_slice( $argv, 2 );
		$id_in = DN_CUR;
		$num = $argv[1];
	} else { //- 入力自動決定
		$id_list = array_slice( $argv, 1 );
		$id_in = DN_CUR;
		$num = 2;
	}
}

if ( ! $id_list ) {
	die( "使い方: 入力ID-セッション番号 出力ID 出力ID ..." );
}

_kvtable([
	'ID input'   => $id_in . ' ' ,
	'mov num'	 => $num ,
	'IDs output' => _imp( $id_list ) . ' ',
], 'パラメータ' );

define( 'FORCE', in_array( 'f', $id_list ) );
$fn_in = _fn( 'session', $id_in, $num );
$id_0 = $id_list[0];
$fn_out0 = _fn( 'session', $id_0, $num );
if ( !file_exists( $fn_in ) ) {
	if ( count( $id_list ) == 1 && file_exists( $fn_out0 ) ) {
		$fn_in = $fn_out0;
		$id_in = $id_0;
		$id_list = [ $id_in ];
	} else {
		die( "$id_in-$num: 入力ファイルがない" );
	}
}

//. コピー実行ループ
$cont = file_get_contents( $fn_in );
foreach ( $id_list as $id_out ) {
	if ( $id_out == 'f' ) continue;
	$dn = _fn( 'emdb_med', $id_out );

	//.. スタートファイルが有るかチェック
	if ( ! file_exists( "$dn/start.py" ) ) {
		_m( "$id_out: スタートファイルがない", -1 );
		continue;
	}

	//.. セッションファイルが無いかチェック
	$sfn = _fn( 'session', $id_out, $num );
	if ( file_exists( $sfn ) && ! FORCE ) {
		_m( "$id_out-$num: すでにセッションファイルがある", -1 );
		continue;
	}

	//.. 表面レベル
	$in = $cont;
	$json = _json_load2( _fn( 'emdb_old_json', $id_out ) )->map;
	if ( $json  ) {
		$t = $json->contourLevel ?: $json->contourLevel->_v ;
		if ( $t && ! is_array( $t ) ) {
			_m( "Surface level for $id_out: $t" );
			$in = preg_replace(
				'/surface_levels\': \[ [0-9eE\-\.]+/' ,
				 "surface_levels': [ $t" ,
				 $cont , -1, $rep
			);
			if ( $rep == 0 )
				_m( '置換失敗', -1 );
		} else {
			print_r( _json_load2( _fn( 'emdb_old_json', $id_out ) )->map );
			_pause( "$id_out: 表面レベルなし、実行するならEnterキー" );
		}
	} else {
		_m( "$id_out: main-jsonがない", -1 );
		continue;
	}

	//.. 書き込み
	file_put_contents( $sfn, strtr( $in, [
		"$id_in.map" => "$id_out.map",
		"\\$id_in\\" => "\\$id_out\\" ,
		"/$id_in/"   => "/$id_out/"
	] ) );
	_m( "$id_out-$num: セッションファイルコピー" );
}

//. 終了
if ( INIT_DIR == __DIR__ )
	chdir( INIT_DIR );
if ( $post_cmd )
	exec( $post_cmd );

