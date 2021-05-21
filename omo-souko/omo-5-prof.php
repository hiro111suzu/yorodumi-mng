<?php
//. init
//.. common?
include "omo-common.php";


//. 
$ids = _file( "$omodn/idlist/idls.data" );
echo count( $ids ) . " entries\n";

//. main
foreach ( $ids as $did ) foreach ( $vqnums as $vqn ) {
	//- vqごと
	$vqfn = "$vqdn/$did-$vqn.pdb";
	$proffn = "$profdn/$did-$vqn-0.txt";

	if ( ! file_exists( $vqfn ) ) {
		echo "no vq file: $vqfn\n";
		continue;
	}
//	if ( count( file( $vqfn ) ) != $vqn ) continue;

	if ( $_redo )
		_del( $proffn );
	if ( _newer( $proffn, $vqfn ) ) continue;

	echo "$did($vqn): ";
	//- 疑似原子の読み込み
	$atom = array();
	foreach ( file( $vqfn ) as $n => $line ) {
		$atom[ $n ][ 'x' ] = substr( $line, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $line, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $line, 46, 8 );
	}

	//- フィルタ無し計算 mode:0
	$n = count( $atom );
	echo "$n atoms | 0-"; 
	$prof = array();
	for ( $a1 = 0; $a1 < $n; $a1 ++ ) {
		for ( $a2 = $a1 + 1; $a2 < $n; $a2 ++ ) {
			$prof[] = sqrt(
				pow( $atom[ $a1 ][ 'x' ] - $atom[ $a2 ][ 'x' ], 2 ) +
				pow( $atom[ $a1 ][ 'y' ] - $atom[ $a2 ][ 'y' ], 2 ) +
				pow( $atom[ $a1 ][ 'z' ] - $atom[ $a2 ][ 'z' ], 2 )
			);
		}
	}
	sort( $prof );
	_savedata( $proffn, $prof );

	//- フィルタありデータ
	foreach ( $filtwid as $wid ) {
		if ( $wid == 0 ) continue;
		echo "  $wid-";

		//- フィルタ長さ決め - %なので、100で割る
		$fnum = round( count( $prof ) * $wid / 100 );

		$data = array();
		foreach ( $prof as $i => $v ) {
			for ( $j = 1; $j <= $fnum; ++ $j ) {
				$d = $prof[ $i + $j ];
				if ( $d == 0 ) break;
				$data[ $i ] += $d - $v;
			}
		}
		_savedata( "$profdn/$did-$vqn-$wid.txt", $data );
	}
	echo "\n";
}

//- savedata
function _savedata( $fn, $data ) {
	if ( count( $data ) < 10 ) {
		echo ( "異常データ " ); 
		return;
	}
	_del( $fn );
	file_put_contents( $fn, implode( "\n", $data ) );
	echo ( file_exists( $fn ) ? "OK" : "ファイル作成失敗" ); 
}
