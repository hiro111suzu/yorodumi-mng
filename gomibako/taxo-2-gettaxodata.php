taxoデータをDDBJからダウンロード

==========

<?php
require_once( "commonlib.php" );
$tdn = "$_ddn/taxo";
$tdata = _tsv_load( "$tdn/taxorank.tsv" );
$emdata = _tsv_load( "$tdn/em_spec.tsv" );
$jdn = "$_ddn/json";

$limit = 20; //- リミットエントリ数

//. listup
$names = array();

foreach ( $emdata as $name => $num )
	$names[] = $name;

foreach ( $tdata as $name => $num ) {
	if ( $num < $limit ) break;
	$names[] = $name;
}

$names = array_unique( array_filter( $names ) );

_m( "取得するデータ数: " . count( $names ) );


//. get text data

$count = 0;
foreach ( $names as $name ) {
	$tn = _taxoname( $name );

	$tfn = "$tdn/txt/{$tn['_']}.txt";
	if ( file_exists( $tfn ) ) continue;
	
	_m( "$name: 取得開始 ... ", 2 );

	$f = file_get_contents(
		'http://xml.nig.ac.jp/rest/Invoke?service=TxSearch&method=searchSimple&tx_Name='
		. $tn[ '+' ]
	);

	if ( strpos( $f, '<html' ) !== false ) {
		_m( 'サービスが止まっているらしい', -1 );
		break;
	}

	if ( strlen( $f ) > 2 ) {
		file_put_contents( $tfn, $f );
		_m( "完了: " . strlen( $f ) . "文字" );
	} else {
		_m( "データが無い" );
	}
	sleep( 10 );

	++ $count;
}

_m( "count: $count" );
