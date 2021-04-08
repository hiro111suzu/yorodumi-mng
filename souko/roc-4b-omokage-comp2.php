<?php
include "commonlib.php";
define( 'WDN', DN_ROOT . '/roc_work' );
define( 'SCDN', WDN . '/scores' );

_mkdir( $d = WDN . '/prof' );
define( 'PROFDN', $d );

//. 

foreach ( _json_load( WDN . '/allids.json' ) as $id ) {
	foreach ( [ 30, 50 ] as $num ) {
		$atom = _getcrd( _fn( "vq$num", $id ) );
		//- 全組み合わせ距離
		$prof = [];
		for ( $a1 = 0; $a1 < $num; $a1 ++ ) {
			for ( $a2 = $a1 + 1; $a2 < $num; $a2 ++ ) {
				$prof[] = _dist( $atom[ $a1 ], $atom[ $a2 ] );
			}
		}
		sort( $prof );
		file_put_contents( __fn( $num, $id ), implode( "\n", $prof ) );
	}
}

//die();
$pairs = _json_load( WDN . '/pairs.json' );
foreach ( [ 30, 50 ] as $num ) {
	_line( $num );
	$sc = [];
	foreach ( $pairs as $ids => $a ) {
		$i = explode( ',', $ids );
		$sc[ $ids ] = _score( $i[0], $i[1], $num );
	}
	_comp_save( SCDN . "/omo_{$num}nd.json", $sc );
}


//. func
function __fn( $num, $id ) {
	return PROFDN . "/$id-prof-$num-nd.txt";
}

function _score( $id1, $id2, $num ) {
	$prof1 = _file( __fn( $num, $id1 ) );
	$prof2 = _file( __fn( $num, $id2 ) );

	//- each profile
//	$sc = [];
	$sum = 0;
	$wsum = 0;
	$cnt = count( $prof1 );
	for ( $i = round( $cnt * 0.02 ); $i < $cnt; ++ $i ) {
		$sum  += pow( $prof1[ $i ] - $prof2[ $i ] , 2 );
		$wsum += pow( $prof1[ $i ] + $prof2[ $i ] , 2 );
	}
	$sc = 1 - sqrt( $sum / $wsum );
	return $sc;
}

//.. _getcrd
function _getcrd( $fn ) {
	$ret = [];
	foreach ( _file( $fn )  as $n => $l ) {
		$atom[ $n ][ 'x' ] = substr( $l, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $l, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $l, 46, 8 );
	}
	return $atom;
}

//.. dist
function _dist( $a, $b ) {
	return sqrt(
		pow( $a[ 'x' ] - $b[ 'x' ], 2 ) +
		pow( $a[ 'y' ] - $b[ 'y' ], 2 ) +
		pow( $a[ 'z' ] - $b[ 'z' ], 2 )
	);
}
