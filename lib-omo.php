<?php
/*
呼び主
emdb-8
idealmodel-1
*/

//. function
//.. _checkvqfile
function _checkvqfile( $fn, $str ) {
	if ( ! file_exists( $fn ) ) {
		_problem( "$str: 作成失敗" );

	} else if ( filesize( $fn ) < 100 ) {
		unlink( $fn );
		_problem( "$str: 作成失敗、異常なファイル" );
	} else {
		_log( "$str: 作成完了" );
	}
}

//.. _getcrd
//- PDB座標を取得
function _getcrd( $fn ) {
	$ret = [];
	foreach ( _file( $fn )  as $n => $l ) {
		$atom[ $n ][ 'x' ] = substr( $l, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $l, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $l, 46, 8 );
	}
	return $atom;
}

//.. _getprof
function _getprof( $atom, $cnt = '' ) {
	$n = $cnt ?: count( $atom );

	//- 全組み合わせ距離
	$prof = [];
	for ( $a1 = 0; $a1 < $n; $a1 ++ ) {
		for ( $a2 = $a1 + 1; $a2 < $n; $a2 ++ ) {
			$prof[] = _dist( $atom[ $a1 ], $atom[ $a2 ] );
		}
	}
	sort( $prof );

	//- 微分もどき
	$out = [];
	$fv = floor( count( $prof ) * FLTWD );
	foreach ( $prof as $i => $v ) {
		for ( $j = 1; $j <= $fv; ++ $j ) {
			$d = $prof[ $i + $j ];
			if ( $d == 0 ) break;
			$out[ $i ] += $d - $v;
		}
	}
	return implode( "\n", $out );
}

//.. dist
function _dist( $a, $b ) {
	return sqrt(
		pow( $a[ 'x' ] - $b[ 'x' ], 2 ) +
		pow( $a[ 'y' ] - $b[ 'y' ], 2 ) +
		pow( $a[ 'z' ] - $b[ 'z' ], 2 )
	);
}
