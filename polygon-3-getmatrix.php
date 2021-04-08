Jmol用 移動マトリックスを作成
- chimeraを実行し、
- marixをoutput
- json形式へ

<?php
require_once( "commonlib.php" );

define( 'CMD_TEMPLATE', <<<EOD
cd <dn>
open <fn_session>; wait
matrixget <fn_matrix>
stop noask
EOD
);

define( 'FITDB', _json_load( DN_PREP. '/emn/fitdb.json.gz' ) );

//. matrix.txt
foreach ( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	if ( ! FITDB[ "emdb-$id" ] ) continue;
	$dn = _fn( 'emdb_med', $id );

	//- セッションファイル
	$fn_session = _fn( 'session', $id, 1 );
	if ( ! file_exists( $fn_session ) )
		$fn_session = _fn( 'session-old', $id, 1 );
	if ( ! file_exists( $fn_session ) )
		continue;

	//- matrix file
	_mkdir( "$dn/ym" );
	$fn_matrix     = "$dn/ym/matrix.txt";
	$fn_matrix_pre = "$dn/matrix.txt";

	if ( file_exists( $fn_matrix_pre ) && (
		! file_exists( $fn_matrix ) ||
		filemtime( $fn_matrix ) < filemtime( $fn_matrix_pre )
	)) {
		_m( "$id: マトリックスディレクトリへ移動" );
//		_pause( );
		rename( $fn_matrix_pre, $fn_matrix );
	}

//	if ( FLG_REDO )
//		_del( $fn_matrix );
//	if ( _newer( $fn_matrix, $fn_session ) ) continue;
	if ( file_exists( $fn_matrix ) ) continue;
//	_pause( "$id: no matrix file " );
/*
	//- chimera
	$fn_cmd = _tempfn( 'cmd' );
	file_put_contents( $fn_cmd, strtr( CMD_TEMPLATE, [
		'<dn>'         => $dn,
		'<fn_session>' => $fn_session,
		'<fn_matrix>'  => $fn_matrix 
	]) );
	_m( "$id - $fn_matrix 移動マトリックス テキスト形式" );
	_del( $fn_session . 'c' ); //- セッションコンパイルファイルを削除
	_exec( DISPLAY . "chimera $fn_cmd" );
	_del( $fn_cmd );
*/
//	break;
}
//. matrix json

foreach ( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	$dn = _fn( 'emdb_med', $id );

	//- matrix file
	$fn_matrix 	= "$dn/ym/matrix.txt";
	$fn_json = "$dn/ym/matrix.json";
	if ( ! file_exists( $fn_matrix ) ) continue;
	if ( FLG_REDO )
		_del( $fn_json );
	if ( _newer( $fn_json, $fn_matrix ) ) continue;

	//- json
	$f = _file( $fn_matrix );
	$a = explode( ' ', trim( $f[1] ) );
	$b = explode( ' ', trim( $f[2] ) );
	$c = explode( ' ', trim( $f[3] ) );
	_json_save( $fn_json, [
		'1' => "{$a[0]},{$a[1]},{$a[2]}" ,
		'2' => "{$b[0]},{$b[1]},{$b[2]}" ,
		'3' => "{$c[0]},{$c[1]},{$c[2]}" ,
		'4' => "{$a[3]} {$b[3]} {$c[3]}"
	] );

	_m( "$id - $fn_json 移動マトリックス json型式" );	
}
