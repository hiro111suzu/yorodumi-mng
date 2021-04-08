term のリストアップ、多すぎる奴と、全然無い奴を除く
<?php

//. init
require_once( "commonlib.php" );
die(); //- いろいろ古くなっている


//- blacklist （未使用）
$s = <<<EOD
lead
file
water
position
author
fitting
per
home
value
back
author
形容的な働きをする言葉
EOD;

$blacklist = explode( "\n", $s );


$terms = _data_load( DN_DATA . '/term/e2j.data' );
$db = new PDO( 'sqlite:' .FN_DBMAIN, '', '' );
$limit = floor( ( count( _idlist( 'epdb' ) ) + count( _idlist( 'emdb' ) ) ) * 0.3 );

$data[] = array();
_count100( $terms );
foreach ( $terms as $term => $ar ) {
//	if ( _count100() == 1000 ) break;
	_count100();
	//- 3文字なら、後方一にもスペース入れる（単語として使っていないと認めない）
	//- （2文字は消してあるので無い）
	$sterm = strtolower( $term );
	if ( strlen( $term ) < 4 )
		$sterm .= ' ';
	$res =  $db->query( "SELECT db_id FROM main WHERE search_words LIKE \"% $sterm%\"" )
			->fetchAll( PDO::FETCH_ASSOC );
	$count = count( $res );
	if ( $count == 0 ) continue;
	if ( $count > $limit ) continue;
	$s = $e2j[ $term ] = implode( '|', $ar );

	_m( "$term: [$count] $s" );
	foreach ( $res as $d ) {
		$data[ $d[ 'db_id' ] ][ $term ] = $count;
	}
}

foreach ( $data as $did => $ar ) {
	asort( $ar );
	echo ( print_r( $ar ) );
	$out = array();
	foreach ( $ar as $term => $cnt )
		$out[ $term ] =  $e2j[ $term ];

	$id = substr( $did, -4 );
	$fn = ( substr( $did, 0, 1 ) == 'e' )
		? "$_emdbdir/$id-jterm.data"
		: "$_pdbdir/$id-jterm.data"
	;
	_comp_save( $fn, $out );
}

//.. _count100: 100毎にカウント
//- $num 全体数 / ゼロなら、カウント実行、そうでなければ初期化
function _count100( $num = 0 ) {
	global $cnt_now, $cnt_total;
	if ( is_array( $num ) )
		$num = count( $num );

	if ( $num != 0 ) {
		$cnt_total = $num;
		$cnt_now = 0;
		_m( "0 / $cnt_total" );
	} else {
		++ $cnt_now;
		$bar = '';
		if ( $cnt_total != 0 ) {
			$p = floor( $cnt_now / $cnt_total * 50 );
			$bar = '[' . str_repeat( '#', $p ) . str_repeat( '_', 50 - $p ) . ']';
		}
		if ( substr( $cnt_now, -2 ) == '00' )
			_m( "$bar $cnt_now / $cnt_total" );
	}
	return $cnt_now;
}
