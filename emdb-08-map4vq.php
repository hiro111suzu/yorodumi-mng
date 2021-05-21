make map for vq
-----
<?php

//. init
require_once( "commonlib.php" );
_mkdir( DN_PREP. '/map4vq' );
_envset( 'eman' );
$blist = new cls_blist( 'map4vq' );

$nonqubic = [];

//. start main loop
foreach( _idlist( 'emdb' ) as $id ) {
	if ( _idfilter( $id ) ) continue;
	if ( $blist->inc( $id, ) ) continue;

	$fn_inmap	= _fn( 'map'	, $id );
	$fn_outmap	= _fn( 'map4vq' , $id );
	$fn_mapinfo	= _fn( 'mapinfo', $id );
	$fn_movinfo	= _fn( 'movinfo', $id );

	if ( ! file_exists( $fn_inmap ) ) //- mapがない
		continue;

	if ( ! file_exists( $fn_mapinfo ) ) {
		_m( "$id: $fn_mapinfo - ファイルがない" );
		continue; //- mapinfoなし
	}
	if ( ! file_exists( $fn_movinfo ) ) {
		_m( "$id: $fn_movinfo - ファイルがない" );
		continue; //- movieinfoなし
	}

	if ( FLG_REDO )
		_del( $fn_outmap );

	if ( _newer( $fn_outmap, $fn_inmap ) ) continue;

	_m( "$id - 処理開始", 1 );

//.. 情報あつめ
	$mapinfo = _json_load( $fn_mapinfo );
//	$is_byte = ( $mapinfo[ 'MODE' ] == 0 ); //- バイトデータ？
//	$imfn = 'tempmap.mrc'; //- 変換の中間マップ

//.. 情報
	//- サイズ
	$nmax = max( [ $mapinfo[ 'NC' ], $mapinfo[ 'NR' ], $mapinfo[ 'NS' ] ] );

	$movinfo = _json_load( $fn_movinfo );
	
	//- 生トモグラム（ソリッド表示）ならやらない
	if ( $movinfo[1][ 'mode' ] == 'solid' ) {
		_m( "$id: 生トモグラム" );
		_del( $fn_outmap );
		continue;
	}

	//- セッションファイルから
	$x = round( $movinfo[1][ 'apix x' ], 3 );
	$y = round( $movinfo[1][ 'apix y' ], 3 );
	$z = round( $movinfo[1][ 'apix z' ], 3 );
	_m( "movinfo: $x, $y, $z" );

	if ( $x * $y * $z == 0 ) {
		$x = $mapinfo[ 'APIX X' ];
		$y = $mapinfo[ 'APIX Y' ];
		$z = $mapinfo[ 'APIX Z' ];
	}

	_m( "use: $x, $y, $z" );

	if ( $x * $y * $z == 0 ) {
		_m( "$id: apix値が異常", -1 );
		if ( $mapinfo[ 'APIX X' ] == 'ERROR' )
			_del( $fn_mapinfo );
		continue;
	}

	$xy = $x / $y;
	$yz = $y / $z;
	_m( "x/y = $xy" );
	_m( "y/z = $yz" );

	//- 立法格子じゃない？
	if (   (integer)$mapinfo[ 'Alpha' ] != 90 
		|| (integer)$mapinfo[ 'Beta' ]  != 90
		|| (integer)$mapinfo[ 'Gamma' ] != 90
		|| $xy < 0.99 || 1.01 < $xy
		|| $yz < 0.99 || 1.01 < $yz
	) {
		$nonqubic[] = $id;
		_m( "$id: 立方格子じゃない" );
		_del( $fn_outmap );
		continue;
	}

	if ( _proc( "make-situs-map-$id" ) ) continue;

	//- apix
	$apix = $movinfo[1][ 'apix x' ] ?: $mapinfo[ 'APIX X' ];
	if ( $apix == '' )
		$apix = _json_load2([ 'emdb', $id ])->map->pixelSpacing->pixelX;

//.. 中間ファイル変換
	_m(  "\n$id: vq用マップ作成開始 - A/pix: $apix A" );

	//- 縮める？
	$sh = 1;
	if ( $nmax > 300 ) $sh = 2;
	if ( $nmax > 500 ) $sh = 3;
	if ( $nmax > 700 ) $sh = 4;
	if ( $nmax > 900 ) $sh = 5;
	if ( $nmax > 1100 ) $sh = 6;
	if ( $nmax > 1300 ) $sh = 7;
	if ( $nmax > 1500 ) $sh = 8;
	if ( $sh > 1 ) {
		_m( "マップ縮小 1/$sh" );
		_proc3d( "shrink=$sh" );
	}

	//- フォーマット問題：integerのマップとか
	if ( $mapinfo[ 'MODE' ] != 0 && $mapinfo[ 'MODE' ] != 2 ) {
		_m( "ファイルモード変換: " . $mapinfo[ 'MODE' ] );
		_proc3d();
	}
		
	//- とにかくコンバートしておくデータ
	if ( _mng_conf( 'situsmap',  $id. '_conv' ) ) {
		_m( "とにかくproc3dコンバート指定", 1 );
		_proc3d();
	}

	//- 密度が逆？
	if ( _mng_conf( 'situsmap', $id. '_invert' ) ) {
		_m( "密度を逆に" );
		_proc3d( 'mult=-1' );
	}
	touch( $fn_outmap, filemtime( $fn_inmap ) );

//.. end of loop
	_proc();
}
_m( "立法格子じゃないデータ\n" . implode( ', ', $nonqubic ) );

//. 亡くなったエントリ対応
_delobs_emdb( 'map4vq' );

_end();

//. func proc3d
function _proc3d( $opt = '' ) {
	global $fn_outmap, $fn_inmap;
	_exec( PROC3D . " $fn_inmap $fn_outmap $opt" );
	$fn_inmap = $fn_outmap;
}
