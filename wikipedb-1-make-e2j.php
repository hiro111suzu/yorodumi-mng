<?php
require_once( "wikipe-common.php" );

//. download

if (
	! _cp( URL_LANGLINKS, FN_LANGLINKS ) ||
	! _cp( URL_EN_STUB, FN_EN_STUB_XML ) ||
	! _cp( URL_JA_STUB, FN_JA_STUB_XML ) 
) 
	die( 'ファイルダウンロード失敗' );

function _cp( $from, $to ) {
	_line( 'Downloading', "$from\n=> $to" );
	return copy( $from, $to );
}

//. id => en 
_line( 'wikipe ja(ID) => en' );
$sqlite = new cls_sqlw([
	'fn' => FN_DB_ID2EN, 
	'cols' => [
		'id UNIQUE' ,
		'en'
	],
	'indexcols' => [ 'id' ],
	'new' => true
]);

//.. main
$flg_not_started = true;
$fh_sql = gzopen( FN_LANGLINKS, 'r' );
$rest = '';
while ( true ) {
	$line = $line = fgets( $fh_sql, 50000 );
	if ( $line === false ) {
		gzclose( $fh_sql );
		break;
	}
	_count( 1000 );
	//- 頭を読み飛ばす
	if ( $flg_not_started ) {
		if ( ! _instr( 'INSERT INTO `langlinks` VALUES (', $line ) ) continue;
		$line = strtr( $line, [ 'INSERT INTO `langlinks` VALUES (' => '' ] );
		$flg_not_started = false;
	}

	//- 分割
	$array = explode( "'),(", strtr( $rest. $line, [ '\\\'' => QUOTE_MARK, '\\' => '' ] ) );
	$rest = array_pop( $array );
	foreach ( $array as $data ) {
		_cnt( 'total' );
		list( $id, $lang, $word ) = explode( ',', $data, 3 );
		if ( $lang != "'en'" ) continue;
		$sqlite->set([ $id, strtr( trim( $word, "'" ), [ QUOTE_MARK => "'" ] ) ]);
		_cnt( 'en' );
	}
}
_cnt();
$sqlite->end();

//. ja => en
_line( 'wikipe ja(ID) => en' );
_count();
$fh_xml = gzopen( FN_JA_STUB_XML, 'r' );
$sqlite_id2en = new cls_sqlite( FN_DB_ID2EN );

$sqlite = new cls_sqlw([
	'fn' => FN_DB_E2J, 
	'cols' => [
		'en' ,
		'ja'
	],
	'indexcols' => [ 'en' ],
	'new' => true
]);

//.. main
$flg_not_started = true;
$xml_ar = [];
while ( true ) {
	$line = $line = fgets( $fh_xml, 50000 );
	if ( $line === false ) {
		gzclose( $fh_xml );
		break;
	}
	$line = trim( $line );
	//- 開始
	if ( $line == '<page>' ) {
		$xml_ar = [];
		$flg_ns0 = false;
	}
	//- ns0?
	if ( $line == '<ns>0</ns>' )
		$flg_ns0 = true;
	$xml_ar[] = $line;

	if ( $line != '</page>' ) continue;
	if ( ! $flg_ns0 ) continue;
	
	//- 書き込み
	if ( _count( 100000, 0 ) ) break;
	$xml = simplexml_load_string( implode( '', $xml_ar ) );
	_cnt( 'total' );
	$en = $sqlite_id2en->qcol(['select' => 'en', 'where' => 'id="' . (string)$xml->id .'"' ])[0];
	if ( !$en ) continue;
	$sqlite->set([ $en, strtr( (string)$xml->title, [ 'Wikipedia:' => '' ] ) ]);
	_cnt( 'en->ja' );
	$xml_ar = [];
	$flg_ns0 = false;
}

_cnt();
$sqlite->end();


