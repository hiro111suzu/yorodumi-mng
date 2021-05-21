<?php
//. ini
include "omo-common.php";

//- read data
$data = array();
$num = 30;
$ar = array(
	"10-$num", 
	"20-$num", 
	"30-$num", 
	"40-$num", 
	"50-$num", 
	"outer-40",
	"outer-35",
	"outer-30",
	"outer-25",
);

foreach ( $ar as $n )
	$data[ $n ] = _data_load( "$compdn/$n.data" );

echo "data読み込み完了\n";

$clist = _data_load( "$omolistdn/complist.data" );
echo "clist読み込み完了\n";

//- keys
$comps = array_keys( $data[ "10-$num" ] );

//- merge
foreach ( $mrglist as $str ) {
	$out = array();
	$modes = explode( '+', $str );


	foreach ( $comps as $comp ) {
		$sum = 0;
//		$sum = 1;
		foreach ( $modes as $mode ) {
			$s = ( substr( $mode, 0, 1 ) == 'o' )
				? 'outer-' . substr( $mode, -2 )
				: "$mode-$num"
			;
			$sum += pow( $data[ $s ][ $comp ], 2 ) ;//- rms
//			$sum = $sum * $data[ $mode ][ $comp ]; //- かけ算
//			$sum += $data[ $mode ][ $comp ]; //- 平均
		}
		$out[ $comp ] = sqrt( $sum / count( $modes ) ); //- rms
//		$out[ $comp ] = $sum;  //- かけ算
//		$out[ $comp ] = $sum / count( $modes ); //- 平均
		

		

//		echo count( $modes );
	}
	_data_save( "$compdn/mrg$str.data", $out );
	echo "$str: 書き込み完了\n";
	
	foreach ( $clist as $mode => $ls ) {
		$fn = "$compdn/mrg{$str}-$mode.dat";
//		if ( $_redo ) _del( $fn );
//		if ( file_exists( $fn ) ) continue;

		$out2 = '';
		echo "[$str] - mode: ";
		foreach ( $ls as $i ) {
			$out2 .= $out[ $i ] . ' ';
			echo $out[ $i ] . ' ';
//			if ( $vqn = 50 and $mode == 'all' and $data[ $i ] < 0.8 )
//				echo "\nxxx: $i: {$data[$i]}\n";
		}
		file_put_contents( $fn, trim( $out2 ) );
		echo "\n";
	
	}
}

