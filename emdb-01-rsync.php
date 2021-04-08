公開前のEMDBアーカイブを取得
XMLコピー
IDリスト作成
<?php
require_once( "commonlib.php" );

//. rsync
_rsync([
	'title' => 'EMDBアーカイブ取得' ,
	'from'	=> [ 'pub/emdb/' ] ,
	'to'	=> DN_EMDB_MR
]);

//. copy xml files
define( 'DN_XML_COPY'	, DN_FDATA. '/emdbxml' );
define( 'DN_XMLOLD'		, DN_FDATA. '/emdbxml-old' );

_mkdir( DN_XML_COPY );
_mkdir( DN_XMLOLD );

$_filenames += [
	'xmlcopy' => DN_XML_COPY. '/emd-<id>.xml'
];

$ids = '';
foreach ( _idloop( 'emdb_xml', 'XMLファイルをコピー' ) as $fn_src ) {
	if ( _instr( '-v', $fn_src ) ) continue; //- 別バージョンは無視
	_count( 500 );
	$id = _numonly( basename( $fn_src ) );
	$ids .= "$id\n";

	//- xmlコピー
	$fn_dest = _fn( 'xmlcopy', $id );
	
	if ( _same_time( $fn_src, $fn_dest ) ) {
		continue;
	}

	if ( file_exists( $fn_dest ) ) {
		_m( "$fn_dest => " . _fn_bkup( $fn_dest ) );
		_log( "$id: 変更 XMLファイル 古いXMLファイル保存" );
		rename( $fn_dest, _fn_bkup( $fn_dest ) );
	} else {
		_log( "$id: 新規 XMLファイル" );
	}
//	_m( 'copy pre' );
	_copy( $fn_src, $fn_dest ); //- タイムスタンプもコピー
//	_m( 'copy post' );
}

//- ID list保存
_line( 'IDリスト作成' );
_comp_save( DN_DATA . "/emdbidlist.txt", $ids ); //- 廃止予定
_comp_save( DN_DATA . "/ids/emdb.txt", $ids );

//. end
_delobs_emdb( 'xmlcopy' );
_end();

//. function _fn_bkup
function _fn_bkup( $fn ) {
	$ret = DN_XMLOLD
		. '/'. basename( $fn, '.xml' ). '-'
		. date( "Ymd", filemtime( $fn ) )
		. '.xml'
	;
	while ( file_exists( $ret ) )  {
		$ret = strtr( $ret, [ '.xml' => '-.xml' ] );
	}
	return $ret ;
}
