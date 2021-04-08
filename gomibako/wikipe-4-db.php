<?php
/*
key	taxo(sci_name) chem(chemid) other(title)

date
en [ key, abst ] ,
ja [ key, abst ]
*/

require_once( "wikipe-common.php" );
define ( 'FN_OUT', DN_DATA . '/wikipe.json.gz' );

$sqlite = new cls_sqlw([
	'fn' => 'wikipe' , 
	'cols' => [
		'key UNIQUE COLLATE NOCASE' ,
		'cat' ,
		'en_title COLLATE NOCASE' ,
		'en_abst COLLATE NOCASE' ,
		'ja_title COLLATE NOCASE' ,
		'ja_abst COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'key', 'cat' ] ,
]);

$sqlite_large = new cls_sqlw([
	'fn' => DN_PREP . '/wikipe/large.sqlite' , 
	'cols' => [
		'key UNIQUE COLLATE NOCASE' ,
		'cat' ,
		'en_title COLLATE NOCASE' ,
		'en_abst COLLATE NOCASE' ,
		'ja_title COLLATE NOCASE' ,
		'ja_abst COLLATE NOCASE' ,
	],
	'new' => true ,
	'indexcols' => [ 'key', 'cat' ] ,
]);

//. main
foreach ([
	'c' => 'wikipe_chem' ,
	't' => 'wikipe_taxo' ,
	'm' => 'wikipe_misc' ,
] as $cat => $type ) foreach ( (array)_idloop( $type ) as $fn ) {
	_count( 1000 );
	$json = _json_load2( $fn );
	if ( ! $json->et && ! $json->jt ) {
//		_del( $fn );
		_m( '中身なし: '. basename( $fn, '.json.gz' ) );
		continue;
	}
//	$key = $cat == 'm' ? strtolower( $json->key ) : $json->key;
	$sqlite_large->set([
		$json->key,
		$cat,
		$json->et ,
		$json->ea ,
		$json->jt ,
		$json->ja ,
	]);
	$sqlite->set([
		$json->key,
		$cat,
		$json->et ,
		_abst_rep( $json->ea ) ,
		$json->jt ,
		_abst_rep( $json->ja, true ) ,
	]);
	_cnt( 'total' );
	_cnt( $cat );
}
$sqlite->end();
$sqlite_large->end();
_cnt();

//. func _abst_rep
function _abst_rep( $in, $lng_ja = false ) {
	$del = $lng_ja ? '。' : '. ';
	$lim = 
	$ret = _reg_rep( $in, [
		'/<dl>.+?<\/dl>/' => '',
		'/ style=".+">/' => '>' ,
		'/ id=".+">/'	 => '' ,
		'/<\/?span.*?>/' => '' ,
		'/>[ \n\r\t]+</' => '> <' ,
		'/<p><\/p>/'	 => ' ' ,
		'/<br>/'		 => ' ',
		'/^<p>/'		 => '',
//		'/<\/p>.*$/'	 => '',
		'/<ul>.+?<\/ul>/' => ' ... ',
		'/\(<.+?>\)/'	 => '',
		'/\(\)/'		 => ' ' ,
		'/ +/'			 => ' ' ,
	]);
	if ( strlen( $ret ) > 300 ) {
		$a = explode( $del, $ret, 4 );
		$ret =implode( $del, array_slice( $a, 0, 2 ) ) . '...';
//		if ( strlen( $ret ) > 300 )
//			$ret = $a[0] . '...';
	}
	return $ret;
}
