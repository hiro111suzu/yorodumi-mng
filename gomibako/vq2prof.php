vq2prof
<?php

//. init
require_once( "commonlib.php" );

$outdn  = '/novdisk2/db/emnavi/omocache';

define( 'FLTWD', 0.3 );

//- R pcaコマンド
define( 'R_CMD', <<<EOF
pc<-princomp(read.csv("<infn>"),cor=FALSE)
sink("<outfn>")
pc
sink()
EOF
);

//. input file
$fn = $argv[1];
if ( ! file_exists( $fn ) )
	die( "no file: $fn" );

$c = _getcrd( $fn );
$cc = count( $c );
if ( $cc == 30 ) {
	$atom30 = $c;
	$fn = strtr( $fn, [ '-30.' => '-50.' ] );
	$atom = _getcrd( $fn );
} else if ( $cc == 50 ) {
	$atom = $c;
	$fn = strtr( $fn, [ '-50.' => '-30.' ] );
	$atom30 = _getcrd( $fn );
} else {
	die( "wrong file: $fn" );
}

if ( count( $atom30 ) + count( $atom ) != 80 )
	die( "wrong data: $fn" );

$proffn = '_' . md5( serialize( [ $atom30, $atom ] ) ) . '.txt';

//. 実行
$data = [];

//... vq30, 50
//- vq30
$data[] = _getprof( $atom30 );

//- vq50
$data[] = _getprof( $atom );
	
//... outer
//- 重心
$cg = [];
foreach ( $atom as $a ) {
	$cg[ 'x' ] += $a[ 'x' ];
	$cg[ 'y' ] += $a[ 'y' ];
	$cg[ 'z' ] += $a[ 'z' ];
}
$cg[ 'x' ] /= 50;
$cg[ 'y' ] /= 50;
$cg[ 'z' ] /= 50;

//- 重心からの距離
$dist = [];
foreach ( $atom as $num => $a )
	$dist[ $num ] = _dist( $cg, $a );

//- 遠い順
arsort( $dist ); //- 「値」を基準に降順ソート
$atom2 = [];
foreach ( $dist as $num => $v )
	$atom2[] = $atom[ $num ];

$data[] = _getprof( $atom2, 25 );

//... pca
$fn1 = _tempfn( 'csv' );
$fn2 = _tempfn( 'txt' );

//- 座標csv作成
$s = "x,y,z\n";
foreach ( $atom as $a )
	$s .= $a[ 'x' ] .','. $a[ 'y' ] .','. $a[ 'z' ] . "\n" ;
file_put_contents( $fn1, $s );

//- pca計算
_Rrun( strtr( R_CMD, [ '<infn>' => $fn1, '<outfn>' => $fn2 ] ) );
$a = _file( $fn2 );
$out = preg_split( '/ +/', trim( $a[4] ) );

_del( $fn1, $fn2 );

//- data
$data[] = $out[0];
$data[] = $out[1];
$data[] = $out[2];

//.. save
print_r( $data );

file_put_contents( "$outdn/$proffn", implode( "\n", $data ) );
_m( "$proffn: プロファイル保存" );

//. func
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

//.. _getprof
function _getprof( $atom, $cnt = '' ) {
	$n = _nn( $cnt, count( $atom ) );

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
	return implode( ',', $out );
}

//.. dist
function _dist( $a, $b ) {
	return sqrt(
		pow( $a[ 'x' ] - $b[ 'x' ], 2 ) +
		pow( $a[ 'y' ] - $b[ 'y' ], 2 ) +
		pow( $a[ 'z' ] - $b[ 'z' ], 2 )
	);
}
