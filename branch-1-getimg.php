<?php
require_once( "commonlib.php" );

define( 'DN_FROM',	'/home/archive/ftp/carbohydrates/images' );
define( 'DN_TO',	DN_PREP. '/chem/polysac_img/' );

define( 'DN_UNZIP'	, DN_TEMP. '/img_unzip' );
_mkdir( DN_UNZIP );
define( 'FN_TIME_DATA', DN_PREP. '/chem/brimg_time.json.gz' );
$time = _json_load( FN_TIME_DATA );

//. main
$c = [];
foreach ( glob( DN_FROM. '/*' ) as $dn ) {
	_count( 1000 );
//	if ( _count( 10, 100 ) ) break;
	if ( ! is_dir( $dn ) ) {
		_m( 'ディレクトリじゃない - '. $dn ); 
		continue;
	}
	$id = basename( $dn );
	$t = filemtime( $dn );
	if ( $time[ $id ] == $t ) {
		_cnt( '変更なし' );
		continue;
	}
	_m( $id );
	$time[ $id ] = $t;
	foreach ( glob( "$dn/*.tar.gz" ) as $fn ) {
		exec( "tar -xzvf $fn -C ". DN_UNZIP );
		foreach ( glob( DN_UNZIP. '/*.svg' ) as $fn ) {
			_cnt(
				rename( $fn, DN_TO. '/'. basename( $fn ) )
				? '解凍 成功' : '解凍 失敗'
			);
		}
		foreach ( glob( DN_UNZIP. '/*' ) as $fn )
			_del( $fn );
	}
}
_cnt();
_json_save( FN_TIME_DATA, $time );
