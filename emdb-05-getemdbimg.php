<?php
//. init
require_once( "commonlib.php" );

define( 'DN_IMG'  , DN_DATA . '/emdb/emdb-fig' );
define( 'URL_IMG' , 'http://emsearch.rutgers.edu/gifs/400_<id>.gif' );

$_filenames += [
	'img_gif' => DN_IMG. "/<id>.gif",
	'img_jpg' => DN_IMG. "/<id>.jpg"
];

//. idlist
//- マップはあるのに画像がないIDの抽出
$idlist =[];
$_data_cnt[ 'to be downloaded' ] = 0;
foreach ( _idlist( 'emdb' ) as $id ) {
	$j = _json_load([ 'filelist', $id ]);
	_cnt( 'total' );
	if ( ! $j[ 'map' ] ) {
		_cnt( 'no map' );
		continue;
	}
	if ( $j[ 'images' ] ) {
		_cnt( 'in EMDB archive' );
		continue;
	}
	if ( file_exists( _fn( 'img_gif', $id ) ) ) {
		_cnt( 'downloaded' ); 
		continue;
	}
	_cnt( 'to be downloaded' );
	$idlist[] = $id;
}
_cnt();

//. redo?
if ( FLG_REDO )
	exec( "rm -rf " . DN_IMG );
_mkdir( DN_IMG );

//. main loop
foreach( $idlist as $id ) {
	_count( 'emdb' );
	$fn_gif = _fn( 'img_gif', $id );
	$fn_jpg = _fn( 'img_jpg', $id );
	if ( file_exists( $fn_gif ) ) {
		continue;
	}

	//- 画像ダウンロード
	if ( @copy( strtr( URL_IMG, [ '<id>' => $id ] ), $fn_gif ) ) {
		_imgres( $fn_gif, $fn_jpg );
		_log( "$id: 画像を取得" );
	} else {
		_m( "$imgurl$id.gif : 画像がない" );
	}
}
_end();
