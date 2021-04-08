<?php
include "commonlib.php";
include "sas-common.php";
//define( 'GETLIST', true );

//. IDリスト取得
_line( 'IDリスト取得' );

/*
if ( GETLIST == true ) {
	$csv = '';
	$listpage = "http://www.sasbdb.org/browse/?page=";
	$page = 1;
	$ids = [];
	while (true) {
		$txt = file_get_contents( $listpage . $page );
		preg_match_all( '/\/data\/(SAS.+?)\//', $txt, $match, PREG_PATTERN_ORDER );
		if ( count( $match[1] ) == 0 ) break;
		foreach ( $match[1] as $i )
			$ids[ $i ] = 1;
		_m( "get list page #$page" );

		//- ids in page
		$csv = "$page," . implode( ',', $match[1] ) . "\n";
		++ $page;
		sleep(1);
	}
	
	_m( '# 404エラーはページ確認動作のため #' . "\n" );
	$ids = array_keys( $ids );
	if ( count( $ids ) > 100 )
		_save( FN_SASIDS, $ids );
}
*/
foreach ( json_decode( 
	file_get_contents( "https://www.sasbdb.org/rest-api/entry/codes/all/?format=json" )
) as $a ) {
	if ( ! $a->code ) continue;
	$ids[] = $a->code;
}
//_die( _imp( $ids ) );
_save( FN_SASIDS, $ids );
_m( 'Got ID list, '. count( $ids ). ' entries' );

//. sascifファイル取得
$u = "http://www.sasbdb.org/media/sascif/sascif_files/<i>.sascif";

//$ids = _file( FN_SASIDS );
_line( '個別データ取得 - エントリ数: ' . count( $ids ) );
foreach ( $ids as $id ) {
	sleep( 1 );
	$cont = file_get_contents( strtr( $u, [ '<i>' => $id ] ) );
	if ( ! _instr( 'data_SAS', $cont ) ) {
		_problem( "$id: CIFファイル取得失敗" );
		continue;
	}
	_comp_save(
		_fn( 'sas_cif', $id ) ,
		$cont
	);
}

//. なくなったデータ対応

_delobs_misc( 'sas_cif', 'sasbdb' );
_end();
