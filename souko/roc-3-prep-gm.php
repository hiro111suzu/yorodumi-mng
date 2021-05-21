<?php
include "commonlib.php";
define( 'WDN', DN_ROOT . '/roc_work' );
_mkdir( WDN . '/scores' );

//. 


$pairs = _json_load( WDN . '/pairs.json' );
_m( 'ペアの数:' . count( $pairs ) );

foreach (  [ 10, 20, 40 ] as $num ) {
	_line( $num );
	$sc = [];
	foreach ( _file( WDN . "/ng$num.txt" ) as $line ) {
		$line = trim( $line );
		if ( substr( $line, 0, 1 ) == '#' ) continue;
		$a = explode( ' ', $line );
		$id1 = _id2did( substr( $a[ 0 ], -4 ) );
		$id2 = _id2did( substr( $a[ 1 ], -4 ) );
		if ( $id1 == $id2 ) continue;
		$id12 = "$id1,$id2";
		if ( $pairs[ $id12 ] == '' ) {
			$id12 = "$id2,$id1";
			if ( $pairs[ $id12 ] == '' ) {
//				_m( "$id12: そんなペアはリストにない" );
				continue;
			}
		}
		$sc[ $id12 ] = $a[2];
	}
	foreach ( $pairs as $i => $a ) {
		if ( $sc[ $i ] == '' )
			_m( "$num - $i: データが無い", -1 );
	}
	_m( "データの数:" . count( $sc ) );
	_comp_save( WDN . "/scores/gmfit$num.json", $sc );
}



/*
foreach ( $pairs as $ids => $ar ) {
	$a = explode( ',', $ids );
	$id1 = 
}
_m( "エラーの数: $errcnt" );


_comp_save( WDN . '/id2cate.json', $id2cate );
_comp_save( WDN . '/cate2id.json', $cate2id );
_comp_save( WDN . '/pairs.json', $pairs );
_comp_save( WDN . '/allids.json', $allids );
*/
//print_r( $pairs );

