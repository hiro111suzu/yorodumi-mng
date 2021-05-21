<?php
//. init
//.. common?
include "omo-common.php";

//. complist
$clist = _data_load( "$omolistdn/complist.data" );

//$filtwid = array( 15 );

//. main
foreach ( $vqnums as $vqn ) foreach ( $filtwid as $wid ) {
	$type = "$vqn-$wid";
	$datafn = "$compdn/$type.data";
	$data = _data_load( $datafn );
	echo $type;

	foreach ( $clist as $mode => $ls ) {
		$fn = "$compdn/$type-$mode.dat";
		if ( $_redo ) _del( $fn );
		if ( file_exists( $fn ) ) continue;


		echo "\n[$fn]\n";
		
		$out = '';
		foreach ( $ls as $i ) {
			$out .= $data[ $i ] . ' ';
//			if ( $vqn = 50 and $mode == 'all' and $data[ $i ] < 0.8 )
//				echo "\nxxx: $i: {$data[$i]}\n";
		}
		file_put_contents( $fn, trim( $out ) );
		echo " - $mode";
//		break;
	}
	echo " - 完了\n";
}

