<?php
include "commonlib.php";
_m( 'js complie', 1 );

define( 'FN_DATA'		, DN_PREP. "/js.json.gz" );
define( 'DATA_CURRENT'	, _json_load( FN_DATA ) );
define( 'DATA_TIME'		, filemtime( FN_DATA ) );
define( 'CMD_COMPILE'	, "java -jar compiler-latest/compiler.jar --compilation_level WHITESPACE_ONLY --js <in> --js_output_file <out>" );

//. タイムスタンプチェック
$flg_update = false;
$flist = [];
foreach ( glob( DN_EMNAVI. '/jsprec/*.js' ) as $fn ) {
	$flist[] = $fn;
	if ( DATA_TIME < filemtime( $fn ) || ! DATA_CURRENT[ basename( $fn, '.js' ) ] )
		$flg_update = true;
}
if ( ! $flg_update )
	die( "No new js file\n" );

//. コンパイル実行
$out = [];
foreach ( $flist as $fn ) {
	$fn_out = _tempfn( 'js' );
	exec( strtr( CMD_COMPILE, [ '<in>' => $fn, '<out>' => $fn_out ] ) );
	$name = basename( $fn, '.js' );
	$out[ $name ] = file_get_contents( $fn_out );
	_m( $name );
	_del( $fn_out );
}
_json_save( FN_DATA, $out );
_m( 'finished', 1 );
