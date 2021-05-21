<?php
//. init
//.. common?
include "omo-common.php";

$ids = _file( "$omodn/idlist/idls.data" );

//. complist
$c = _data_load( "$omolistdn/complist.data" );
$complist = $c[ 'all' ];
$c = '';

//$filtwid = array( 15 );

//. main
foreach ( $ovqnums as $ovqn ) {
	$type = "outer-$ovqn";
	$data = array();
	$datafn = "$compdn/$type.data";

	if ( $_redo )
		_del( $datafn );
	if ( file_exists( $datafn ) ) continue;
	if ( _proc( "comp-$type" ) ) continue;

	echo "$type - 開始";
	
	//- 全部読み込んでおく
	$vq = array();
	foreach ( $ids as $did ) {
		$fn = _ofn( "oprof$ovqn" );
		if ( ! file_exists( $fn ) )
			die( "ファイルがない: $fn" );
		$vq[ $did ] = _file( $fn );
	}
	echo " - データ読み込み完了: " . count( $vq );

	foreach ( $complist as $line ) {
		$ida = explode( '|', $line );
		$v1 = $vq[ $ida[0] ];
		$v2 = $vq[ $ida[1] ];

//		die( ' #cnt: {$ida[0]}' . count( $v1 ) . ' vs. ' . count( $v2 ) );
//		die( " #0: {$ida[0]} vs. #1 {$ida[1]} " );
		$count = count( $v1 );
		$ign = round( $count * 0.05 );
		$sum = 0;
		$wsum = 0;
		for ( $i = $ign; $i < $count; ++ $i ) {
			$sum += pow( $v1[ $i ] - $v2[ $i ] , 2 );
			$wsum += pow( $v1[ $i ] + $v2[ $i ] , 2 );
		}
		$data[ $line ] = 1 - sqrt( $sum / $wsum );
	}
	echo " - 比較完了\n";
	_data_save( $datafn, $data );
	_proc();
	
}

