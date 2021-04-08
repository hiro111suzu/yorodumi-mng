自動補完用キーワード
データ取りまとめ

<?php
require_once( "commonlib.php" );
/*
$kw ,
$kw_em ,
$kw_hot ,
$kw_em_hot ,
$an ,
$an_em
*/
extract( _json_load( DN_PREP . '/keyword/kwinfo.json.gz' ) );

//. keyword
_line( 'keyword' );
//.. DB準備
$sqlite = new cls_sqlw([
	'fn'		=> 'autocomp_kw' ,
	'cols'		=> [ 'w PRIMARY KEY', 'a INTEGER', 'e INTEGER' ] ,
	'indexcols' => [ 'w', 'a', 'e' ] ,
	'new'		=> true
]);

//.. 書き込み
$k = [];
foreach ( $kw as $w => $num )
	$k[ $w ][ 'all' ] = $num;
foreach ( $kw_em as $w => $num )
	$k[ $w ][ 'em' ] = $num;

ksort( $k );
_m( 'データ数: ' . count( $k ) );
foreach ( $k as $w => $a ) {
	$sqlite->set( [ $w, $a['all'], (integer)$a['em'] ] );
}
$sqlite->end();

//. author name
_line( 'author name' );

//.. DB準備
$sqlite = new cls_sqlw([
	'fn'		=> 'autocomp_an' ,
	'cols'		=> [ 'w PRIMARY KEY', 'a INTEGER', 'e INTEGER' ] ,
	'indexcols' => [ 'w', 'a', 'e' ] ,
	'new'		=> true
]);

//.. 書き込み
$k = [];
foreach ( $an as $w => $num )
	$k[ $w ][ 'all' ] = $num;
foreach ( $an_em as $w => $num )
	$k[ $w ][ 'em' ] = $num;

_m( 'データ数: ' . count( $k ) );
ksort( $k );
foreach ( $k as $w => $a ) {
	$sqlite->set( [ $w, (integer)$a['all'], (integer)$a['em'] ] );
}
$sqlite->end();
$k = [];
//. hot
_line( 'for init' );
_json_save( DN_DATA . '/autocomp.json.gz', [
	'kw'	=> _datalist( $kw_hot ) ,
	'kw_em'	=> _datalist( $kw_em_hot ) ,
	'an'	=> _datalist( $an ) ,
	'an_em' => _datalist( $an_em ),
]);

function _datalist( $ar ) {
	$ret = '';
	arsort( $ar );
	foreach ( array_slice( $ar, 0, 60 ) as $w => $num ) {
		$ret .= '<option value="' . htmlspecialchars( $w ) . '">';
//		$ret .= htmlspecialchars( $w ) .'|';
	}
	return $ret;
}
