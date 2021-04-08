距離テーブル(Rに使う用)作成
in : dn_prep/simtable<mode>.json.gz
out: disttable<mode>.csv

<?php

//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );
ini_set( "memory_limit", "8192M" );

$blacklist = _blist( 'blacklist_make_disttable' );

$mode = _omomode();

//- 入力ファイル
_ex_or_die( $infn = DN_PREP . "/simtable$mode.json.gz" );

//- csvファイル
$csvfn = DN_PREP . "/disttable$mode.csv";

//. main loop
_m( "類似度テーブル ($infn) 読み込み" );
$indata  = _json_load( $infn ) ;
_m( "完了" );

//- ID list 作成
$idlist = [];
foreach ( array_keys( $indata ) as $i ) {
	if ( $i == 'pdb-3dwu' ) die( '3dwu' );
	if ( ! in_array( $i, $blacklist ) )
		$idlist[] = $i;
}
_m(  "データ数: " . count( $idlist )
	. ", ブラックリスト: " . count( $blacklist ) );

//- 距離係数
$ce = 2.5;
if ( $mode == 1 ) $ce = 1.5;
if ( $mode == 2 ) $ce = 2.5;
if ( $mode == 3 ) $ce = 2.5;
if ( $mode == 4 ) $ce = 2.5;
if ( $mode == 5 ) $ce = 2.5;
if ( $mode == 7 ) $ce = 2;
if ( $mode == 8 ) $ce = 2.5;

_m( "モード: $mode / 係数 = $ce" );
_count();

//- csv形式に変換
$s = '';
$rscn = 1 / SCNORM;
foreach ( $idlist as $id1 ) {
	$s .= $id1;
	foreach ( $idlist as $id2 ) {
		if ( $id1 == $id2 ) {
			$s .= ",0";
		} else {
			$v = pow( ( $indata[ $id1 ][ $id2 ] + 1 ) / 2 , $rscn );
			$v = pow( 1 - $v, $ce );
//			_m( $v );
			if ( $v == 0 ) {
				_m( "$id1-$id2 - 同一構造" );
				$v = "0.000000001"; //- 0は許されない
			}
			$s .= ",$v";
		}
	}
	$s .= "\n";
	_count( 100 );
}

	//.. 書き込み
file_put_contents( $csvfn, implode( ',', $idlist ) . "\n" . $s );
_log( "$csvfn: 書き込み." );

//. マップにないIDを収集

$ids = [];
foreach ( array_keys( $indata ) as $i )
	$ids[] = substr( $i, -4 );


$exids = [];
foreach ( _joblist() as $a ) {
	if ( ! in_array( $a[ 'id' ], $ids ) )
		$exids[] = $a[ 'id' ];
}

file_put_contents( DN_DATA . '/legacy/igomoids.txt', implode( "\n", $exids ) );

//. 
_php( 'sub-prep-omokage-table' );
_php( 'sub-make-omo-image' );
