<?php
//. init
//.. common?
include "omo-common.php";
$ini =  $dataini[ 'situsmap' ];

//define( 'VQ1', in_array( 'vq1', $argv ) );

//. 
$vqdn = "$omodn/vq";
$ids = array();

foreach ( _data_load( "$omolistdn/idlist.data" ) as $cat => $ls ) {
	foreach ( array_values( $ls ) as $i ) {
		$a = explode( '-' , $i );
		$ids[ $a[0] ][] = $a[1];
	}
}

$emjobs = array();
foreach ( $ids[ 'emdb' ] as $id ) {
	//- mapfn
	$f = DN_EMDB_MED . "/$id/emd_{$id}_r.situs"; //- vq専用のマップ
 	$f = file_exists( $f ) ? $f : DN_EMDB_MED . "/$id/emd_$id.situs";

	//- threshold
	$json = _json_load( _fn( 'movinfo' ) );
	$th = $json[1][ 'threshold' ] ;

	$emjobs[ $id ] = array( 'mapfn' => $f, 'thr' => $th );
}

//. emdb-vq1
echo "\n########## EMDB - vq1 ##########\n";

foreach ( $vqnums as $vqnum ) foreach( $emjobs as $id => $j ) {
	if ( $vqnum == 50 ) continue;

	$outfn = "$vqdn/emdb-$id-$vqnum-pre.pdb";
	if ( file_exists( $outfn ) continue;
	if ( _proc( "$id-$vqnum-vqpre" ) ) continue;

	$th = $j[ 'thr' ];
	echo "\n$id-$vqnum-pre: 作成開始 (thr: $th) (" . date( 'm/d H:i:s' ) . ") " ;
	_run( "qvol {$j['mapfn']} $outfn", ''
		. "1\n"
		. "$th\n"	//- 表面レベル
		. "$vqnum\n"	//- コードベクターの数
		. "1\n"		//- コネクティビティ計算するか？
	);
	_chkvq( $outfn, $vqnum );
	_proc();
}

//. emdb-vq2
echo "\n########## EMDB - vq2 ##########\n";
foreach ( $vqnums as $vqnum ) foreach( $emjobs as $id => $j ) {
	$prefn = "$vqdn/emdb-$id-$vqnum-pre.pdb";
	$outfn = "$vqdn/emdb-$id-$vqnum.pdb";

	if ( file_exists( $outfn ) ) continue;
	if ( $vqnum == 50 ) {
		copy ( DN_DATA . "/omokage-data/emdb-$id-vq2.pdb", $outfn );
		continue;
	}
	if ( ! file_exists( $prefn ) ) continue;
	if ( _proc( "$id-$vqnum-vq2" ) ) continue;

	echo "\n$id-$vqnum: 作成開始 (thr: $th) (" . date( 'm/d H:i:s' ) . ") " ;
	_run( "qvol {$j['mapfn']} $prefn $outfn", ''
		. "1\n"
		. $j[ 'thr' ] . "\n"	//- 表面レベル
		. "1\n"		//- LBGする
		. "1\n"		//- 束縛しない
		. "1\n"		//- コネクティビティ計算するか？
	);
	_chkvq( $outfn, $vqnum, 'VQ2' );
	_proc();
}

//. pdb
echo "\n########## PDB ##########\n";
foreach ( $vqnums as $vqnum ) foreach( $ids[ 'pdb' ] as $id ) {
	$outfn = "$vqdn/pdb-$id-$vqnum.pdb";
	if ( file_exists( $outfn ) ) continue;

	if ( $vqnum == 50 ) {
		$did = "pdb-$id";
		copy ( _fn( 'vq50' ), $outfn );
		continue;
	}

	if ( _proc( "$id-$vqnum-vq" ) ) continue;
	echo "\nPDB-$id-$vqnum: 作成開始 (" . date( 'm/d H:i:s' ) . ") " ;
	
	$srcfn = _fn( 'pdb', $id );
	_run( "qpdb $srcfn $outfn", ''
		. "1\n"	//- Do you want to mass-weight the atoms?(水とか) No
		. "1\n"	//- B-factor threshold? No
		. "$vqnum\n" //- cvの数
		. "1\n"		//- コネクティビティ計算するか？
		. "1\n"	//- No
	);
	_chkvq( $outfn, $vqnum );
	_proc();
}
/*
//. pdb-split
echo "\n########## PDB-split ##########\n";
foreach ( $vqnums as $vqnum ) foreach( $ids[ 'pdbsplit' ] as $id ) {

	$outfn = "$vqdn/pdbsplit-$id-$vqnum.pdb";
	if ( file_exists( $outfn ) ) continue;
	if ( _proc( "split-$id-$vqnum-vq" ) ) continue;
	echo "\nPDB-$id-$vqnum: 作成開始 (" . date( 'm/d H:i:s' ) . ") " ;
	
	$srcfn = "$omodn/pdbsplit/$id.pdb";
	_run( "qpdb $srcfn $outfn", ''
		. "1\n"	//- Do you want to mass-weight the atoms?(水とか) No
		. "1\n"	//- B-factor threshold? No
		. "$vqnum\n" //- cvの数
		. "1\n"		//- コネクティビティ計算するか？
		. "1\n"	//- No
	);
	_chkvq( $outfn, $vqnum );
	_proc();
}
*/


//. func

//.. _chkvq: ちゃんとしたvqファイルができたかチェック
function _chkvq( $fn, $vqn, $str = 'VQ' ) {
	$s = "$str 作成完了";
	if ( ! file_exists( $fn ) )
		$s = "エラー！！！ $str 作成失敗";
	else if (  count( file( $fn ) ) != $vqn ) {
		unlink( $fn );
		$s = "エラー：変なファイルができた！！！！！";
	}
	_log( $s );
}

