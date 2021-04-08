<?php
//. init
require_once( "commonlib.php" );
_mkdir( DN_PREP. '/mom' );
_mkdir( DN_PREP. '/mom/html' );

$_filenames += [
	'mom_html' => DN_PREP. '/mom/html/<id>.html' ,
];

define( 'URL_MOM', 'https://numon.pdbj.org/mom/<id>?l=ja' );
//define( 'URL_MOM_E', 'https://numon.pdbj.org/mom/<id>?l=ja' );
//$max_id = 500;

//. main
$id = 1;
while( true ) {
	++ $id;
	$fn_out = _fn( 'mom_html', $id );
	if ( file_exists( $fn_out ) && ! FLG_REDO ) {
		_m( "$id = file exists" );
		continue;
	}
	$url = strtr( URL_MOM, [ '<id>' => $id ] );
	$html = @file_get_contents( $url );
	$res = $http_response_header[0];
	_m( $res );
	if ( ! _instr( '200', $res ) || _instr( 'ページが見つかりません', $html ) ) {
		_m( "$id: コンテンツなし" );
		break;
/*
	if ( strlen( $html ) < 3000 ) {
		if ( $max_id < $id )
			break;
		else {
			_m( "ファイル取得失敗: $id" );
			_m( "URL: $url" );
			_pause( $http_response_header );
			continue;
		}
*/
	} else {
		_m( "$id: htmlデータ取得" );
		file_put_contents( $fn_out, $html );
	}
//	if ( $html_j )
//		file_put_contents( $fn_out_j, $html_j );
//	_pause( 'output' );

}


