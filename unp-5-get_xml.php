<?php
//. init
require_once( "unp-common.php" );

define( 'MAXNUM', 1000 );
define( 'MAXNUM_OLD', $argv[1] ?: 100 );
_m( '古いファイルを再ダウンロードする数: '. MAXNUM_OLD );
//define( 'MAXNUM', 5 );
$allids = gzfile( FN_ALL_UNPIDS );
$ids_not_found = _tsv_load( FN_IDS_NOT_FOUND );
define( 'INIT_NOT_FOUND', $ids_not_found );
define( 'FN_TEMP_DL', DN_TEMP. '/unp_dl.xml' );
define( 'OLDER_THAN_30_DAYS', time() - 30 * 24 * 60 * 60  );
//define( 'OLDER_THAN_30_DAYS', time() - 3000 * 24 * 60 * 60  );

//. main loop
$cnt = 0;
$cnt_old = 0;
$rest_old = 0;
foreach ( gzfile( FN_ALL_UNPIDS ) as $id ) {

	$id = trim( $id );
	if ( $id == '' ) continue;
	if ( strlen( $id ) < 5 ) continue;

	_count( 1000 );
	
	//.. 以前見つからなかった？
	if ( $ids_not_found[ $id ] )  {
		if ( $ids_not_found[ $id ] < OLDER_THAN_30_DAYS ) {
			_m( $id . ': 以前見つからなかったファイルの再試行', 'blue' );
		} else {
			//	_m( "$id: 見つからないID " . date( 'Y-m-d', $ids_not_found[$id] ) );
			continue;
		}
	}

	//.. ダウンロード必要か？
	$fn_out = _fn( 'unp_xml', $id );
	if ( file_exists( $fn_out ) ) {
//		_m( "exists: " );

		unset( $ids_not_found[ $id ] );

		//- 0バイトフィル
/*
		if ( filesize( $fn_out ) < 100 ) {
			if ( $cnt_old < MAXNUM_OLD ) {
				_m( "$id: " . date( 'Y-m-d', $time_out ). " zero byte file" );
				++ $cnt_old;
			}
		}
*/
		//- 古いファイル対応
		$time_out = filemtime( $fn_out ); 
		if ( $time_out < OLDER_THAN_30_DAYS ) {
			if ( $cnt_old < MAXNUM_OLD ) {
				_m( "$id: ". date( 'Y-m-d', $time_out ). " old file ($cnt_old)" );
				++ $cnt_old;
			} else {
				++ $rest_old;
				continue;
			}
		} else {
			continue;
		}
	} else {
		_m( "new file: -------------------------------------" );
	}

	//.. ダウンロード実行
	if (
		copy( strtr( URL_UNIPROT_XML, [ '<id>' => $id ] ), FN_TEMP_DL ) 
		&& 100 < filesize( FN_TEMP_DL )
		&& simplexml_load_file( FN_TEMP_DL )->entry
	) {
		copy( FN_TEMP_DL, $fn_out );
		_m( "$id: downloaded" );
//		_m( date( 'Y-m-d', filemtime( $fn_out ) ) );
	} else {
		_m( "$id: XML file not found", 'red' );
		$ids_not_found[ $id ] = time();
	}
	
	//.. 続けるか？
	++ $cnt;
	if ( MAXNUM < $cnt ) {
		_m( 'Downloaded limit num files - '. MAXNUM. ', break loop.' );
		break;
	}

	//- stop?
	if ( file_exists( $fn = DN_PROC . '/stop' ) ) {
		_m( 'stopファイルがあるので中止'. $fn, 'green' );
		break;
	}
	sleep( 2 );
}

if ( $ids_not_found != INIT_NOT_FOUND ) {
	_tsv_save( FN_IDS_NOT_FOUND, $ids_not_found );
}

//. end
_m( '残りの古いファイルの個数: '. $rest_old );
_del( FN_TEMP_DL );
