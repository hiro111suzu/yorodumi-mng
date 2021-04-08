make situs map
-----
<?php
//$densrev = array( 1235, 1151, 1152, 1049, 1118, 1155, 1354, 1580, 1596 );

//. init
require_once( "commonlib.php" );
_initlog( "emdb-6: situs map" );

_envset( 'map2map' );

//- マップ問題データ
$ini = $dataini[ 'situsmap' ];
define( BLIST, 'blacklist_make_situsmap' );
$nonqubic = [];

//. start main loop
foreach( $emdbidlist as $id ) {
	if ( _idfilter( $id ) ) continue;

	$mapfn		= _fn( 'map' );
	$situsfn	= _fn( 'situs' );
	$mapinfofn	= _fn( 'mapinfo' );
	$movinfofn	= _fn( 'movinfo' );

	if ( _blist2( $id ) ) {
		_del( $situsfn );
		continue;
	}

	if ( ! file_exists( $mapfn ) ) //- mapがない
		continue;

	if ( ! file_exists( $mapinfofn ) ) {
		_m( "$id: $mapinfofn - ファイルがない" );
		continue; //- mapinfoなし
	}
	if ( ! file_exists( $movinfofn ) ) {
		_m( "$id: $movinfofn - ファイルがない" );
		continue; //- movieinfoなし
	}

	if ( $_redo )
		_del( $situsfn );

	if ( _newer( $situsfn, $mapfn ) ) continue;

	_m( "$id - 処理開始", 1 );

//.. 情報あつめ
	$json = _json_load( $mapinfofn );
	$byte = ( $json[ 'MODE' ] == 0 ); //- バイトデータ？
	$imfn = 'tempmap.mrc'; //- 変換の中間マップ

//.. 情報
	//- サイズ
	$nmax = max( [ $json[ 'NC' ], $json[ 'NR' ], $json[ 'NS' ] ] );

	$j = _json_load( $movinfofn );
	
	//- 生トモグラム（ソリッド表示）ならやらない
	if ( $j[1][ 'mode' ] == 'solid' ) {
		_m( "$id: 生トモグラム" );
		_del( $situsfn );
		continue;
	}

	//- セッションファイルから
	$x = round( $j[1][ 'apix x' ], 3 );
	$y = round( $j[1][ 'apix y' ], 3 );
	$z = round( $j[1][ 'apix z' ], 3 );
	_m( "movinfo: $x, $y, $z" );

	if ( $x * $y * $z == 0 ) {
		$x = $json[ 'APIX X' ];
		$y = $json[ 'APIX Y' ];
		$z = $json[ 'APIX Z' ];
	}

	_m( "use: $x, $y, $z" );

	if ( $x * $y * $z == 0 ) {
		_m( "$id: apix値が異常", -1 );
		if ( $json[ 'APIX X' ] == 'ERROR' )
			_del( $mapinfofn );
		continue;
	}

	$xy = $x / $y;
	$yz = $y / $z;
	_m( "x/y = $xy" );
	_m( "y/z = $yz" );

	//- 立法格子じゃない？
	if (   (integer)$json[ 'Alpha' ] != 90 
		or (integer)$json[ 'Beta' ]  != 90
		or (integer)$json[ 'Gamma' ] != 90
		or $xy < 0.99 or 1.01 < $xy
		or $yz < 0.99 or 1.01 < $yz
	) {
		$nonqubic[] = $id;
		_m( "$id: 立法格子じゃない" );
		_del( $situsfn );
		continue;
	}

	if ( _proc( "make-situs-map-$id" ) ) continue;

	//- apix
	$apix = _nn( $j[1][ 'apix x' ], $json[ 'APIX X' ] );
	if ( $apix == '' )
		$apix = _json( $id )->map->pixelSpacing->pixelX;

//.. 中間ファイル変換

	_m(  "\n$id: Situsマップ作成開始 - A/pix: $apix A" );

	//- 縮める？
	$sh = 1;
	if ( $nmax > 300 ) $sh = 2;
	if ( $nmax > 500 ) $sh = 3;
	if ( $nmax > 700 ) $sh = 4;
	if ( $sh > 1 ) {
		_m( "マップ縮小 1/$sh" );
		_proc3d( "shrink=$sh" );
	}

	//- フォーマット問題：integerのマップとか
	if ( $json[ 'MODE' ] != 0 and $json[ 'MODE' ] != 2 ) {
		_m( "ファイルモード変換: " . $json[ 'MODE' ] );
		_proc3d();
	}
		
	//- とにかくコンバートしておくデータ
	if ( $ini[ $id . '_conv' ] ) {
		_m( "とにかくproc3dコンバート指定", 1 );
		_proc3d();
	}

	//- 密度が逆？
	if ( $ini[ $id . '_invert' ] ) {
		_m( "密度を逆に" );
		_proc3d( 'mult=-1' );
	}

	//.. 本番変換
	$fn = _tempfn();
	file_put_contents( $fn, ''
/*
		. "3\n"
		. ( $apix * $sh ) . "\n"	//- voxel spacing
		. floor( $json[ 'XORIGIN' ] / $sh ) . "\n"	//- X-origin
		. floor( $json[ 'YORIGIN' ] / $sh ) . "\n"	//- Y-origin
		. floor( $json[ 'ZORIGIN' ] / $sh ) . "\n"	//- Z-origin
		. floor( $json[ 'NC' ] / $sh ) . "\n"	//- NX
		. floor( $json[ 'NR' ] / $sh ) . "\n"	//- NY
		. floor( $json[ 'NS' ] / $sh ) . "\n"	//- NZ
*/
		. "2\n"			//- manual
		. ( $apix * $sh ) . "\n"	//- voxel spacing
		. floor( $json[ 'XORIGIN' ] / $sh ) . "\n"	//- X-origin
		. floor( $json[ 'YORIGIN' ] / $sh ) . "\n"	//- Y-origin
		. floor( $json[ 'ZORIGIN' ] / $sh ) . "\n"	//- Z-origin
		. floor( $json[ 'NC' ] / $sh ) . "\n"	//- NX
		. floor( $json[ 'NR' ] / $sh ) . "\n"	//- NY
		. floor( $json[ 'NS' ] / $sh ) . "\n"	//- NZ
	);
	
	_exec( MAP2MAP . " $mapfn $situsfn < $fn" );
	unlink( $fn );

	//- check
	_log( file_exists( $situsfn )
		? "$id: 作成成功"
		: "$id: 作成失敗！！！！！"
	);
	_m( ' ' );

//.. end of loop
//	die();
	_del( $imfn );

	_proc();
}
_writelog();

_m( "立法格子じゃないデータ\n" . implode( ', ', $nonqubic ) );

//. proc3d
function _proc3d( $opt = '' ) {
	global $mapfn, $imfn;
	_exec( "proc3d $mapfn $imfn $opt" );
	$mapfn = $imfn;
}
