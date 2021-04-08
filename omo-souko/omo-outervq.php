<?php
//. init
//.. common?
include "omo-common.php";

//. 
$ids = _file( "$omodn/idlist/idls.data" );
echo count( $ids ) . " entries\n";

//. main
//- idごとのループ
foreach ( $ids as $did ) {
	$vqfn = _ofn( 'vq50' );
	$ovqfn = _ofn( "ovq" );

	if ( $_redo ) _del( $ovqfn );
	if ( _newer( $ovqfn, $vqfn ) ) {
		echo '.';
		continue;
	}

	echo "$did: ";
	//- 疑似原子の読み込み
	$sum = []; //- 重心計算用
	$str = []; //- PDB文字列
	foreach ( _file( $vqfn ) as $n => $line ) {
		$str[ $n ] = $line;
		$atom[ $n ][ 'x' ] = substr( $line, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $line, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $line, 46, 8 );
		$sum[ 'x' ] += $atom[ $n ][ 'x' ];
		$sum[ 'y' ] += $atom[ $n ][ 'y' ];
		$sum[ 'z' ] += $atom[ $n ][ 'z' ];
	}
	//- 重心
	$cg[ 'x' ] = $sum[ 'x' ] / 50;
	$cg[ 'y' ] = $sum[ 'y' ] / 50;
	$cg[ 'z' ] = $sum[ 'z' ] / 50;

	//- 重心からの距離
	$dist = [];
	foreach ( $atom as $num => $a ) {
		$dist[ $num ] =sqrt( 
			pow( $cg[ 'x' ] - $a[ 'x' ], 2 ) +
			pow( $cg[ 'y' ] - $a[ 'y' ], 2 ) +
			pow( $cg[ 'z' ] - $a[ 'z' ], 2 )
		);
	}
	arsort( $dist ); //- 「値」を基準に降順ソート

	//- 作成
	$out = [];
	foreach ( $dist as $num => $v )
		$out[] = $str[ $num ];

	echo _save( $ovqfn, $out )
		? count( $out ) . "原子 - 完了\n"
		: "失敗！！！！！\n"
	;

}
