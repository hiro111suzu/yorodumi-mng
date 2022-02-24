spiderjobs
spiderでやること一式: プロジェクション、スライス、ヒストグラム作成
マスク、otherのマップデータの分も作成
----------
<?php
//. init
//require_once( "commonlib.php" );
require_once dirname(__FILE__) . '/commonlib.php';

//- できるはずのファイル
define( 'FLIST' , explode( '|', 'hist.png|hist.txt|histlog.png|histlogs.png|hists.png|proj0.jpg|proj0.png|proj2.jpg|proj2.png|proj3.jpg|proj3.png|slc_xa.jpg|slc_xa.png|slc_xb.jpg|slc_xb.png|slc_xc.jpg|slc_xc.png|slc_ya.jpg|slc_ya.png|slc_yb.jpg|slc_yb.png|slc_yc.jpg|slc_yc.png|slc_za.jpg|slc_za.png|slc_zb.jpg|slc_zb.png|slc_zc.jpg|slc_zc.png' ) );


//.. blacklist
$blist = new cls_blist( 'spiderjob' );
/*
$blist_other = new cls_blist([
//	3821, //- 軸情報が読み取れない、おそらくおかしいファイル
]);
*/

//.. define
_envset();

//- gnuplot
define( 'PLOT', <<<EOD
set term png large size 800,400
set output "hist.png"
#set ytics  rotate
set style fill solid 1
plot "hist.txt" using 3:4 notitle with boxes lt rgb "black"
set output "histlog.png"
set logscale y
set format y '%1.0e'
#set ytics  rotate
set style fill solid 1
plot "hist.txt" using 3:4 notitle with boxes lt rgb "black"
EOD
);

//- gnuplot2
define( 'PLOT2', <<<EOD
set parametric
set term png large size 800,400
set output "hist.png"
<add>
set style fill solid 1
plot "hist.txt" using 3:4 title "Density distribution" with boxes lt rgb "#808080"<plot>

set output "histlog.png"
set logscale y
set format y '%1.0e'
set style fill solid 1
plot "hist.txt" using 3:4 title "Density distribution" with boxes lt rgb "#808080"<plot>
EOD
);

//- spider surface作成
define( 'SP_SURF', <<<EOD
pj st
<infile>
<axis>
surf
<size>
<depth>
<ang>
<thr>
0

pj shad
surf
shad
1
30,0.5
0.6

cp to tiff
shad
img.tiff

en
EOD
);
//_spider( SP_SURF );
//die();
/*
{g spider "pj st"


*/

//.. 手動でID指定
$redo_ids = [];
foreach ( (array)$argv as $i ) {
if ( _inlist( $i, 'emdb' ) )
	$redo_ids[] = $i;
}

if ( count( $redo_ids ) > 0 )
	_m( "やりなおすID: " . implode( ', ', $redo_ids ), 'red' );

//. main map loop
_line( 'Main map' );

foreach( _idlist( 'emdb' ) as $id ) {
	//- blacklist
	if ( $blist->inc( $id ) ) continue;
	$fn_map		= _fn( 'map', $id );
	$fn_mapinfo = _fn( 'mapinfo', $id );
	if ( ! file_exists( $fn_mapinfo ) ) continue;

	chdir( _fn( 'emdb_med', $id ) );

	if ( in_array( $id, $redo_ids ) || FLG_REDO )
		exec( "rm -fr mapi" );

	//- 縮小画像がないのでやりなおし
	if ( file_exists( 'mapi/proj0.png') && ! file_exists( 'mapi/proj0.jpg') ) {
		_m( '縮小画像がない、やりなおし' );
		exec( "rm -fr mapi" );
	}

	if ( _newer( "mapi/hist.txt", $fn_map ) ) continue;
	if ( _proc( "spiderjobs-$id" ) ) continue;

	_m( "$id:", 1 );

	//- 8bit map?
	if ( _json_load( _fn( 'mapinfo', $id ) )[ 'MODE' ] == 0 ) {
		_mapconv( $fn_map );
		$fn_map = '../map.mrc';
	}

	exec( "rm -fr mapi" );
	_mkdir( 'mapi' );
	chdir( 'mapi' );
	_spiderjob( $fn_map, $id, 'main' );
	_proc();
} 
//die();

//. mask loop
_line( 'Mask' );

foreach ( _idlist( 'emdb' ) as $id ) {
//	if ( $id > 1001 ) break;

	$mdn = _fn( 'emdb_med', $id ) . '/masks';
	if ( ! is_dir( $mdn ) ) continue;
	foreach ( _json_load( "$mdn/list.json" ) as $num => $fn ) {
		$d = "$mdn/$num";
		if ( is_dir( $d ) ) {
			if ( 10 < count( glob( "$d/*.jpg" ) ) )
				continue;
		}
//		_die(  glob( "$d/*.jpg" )  );
		if ( _proc( "spiderjobs-mask-$id-$num" ) ) continue;

		_m( "$id: マスクデータ", 1 );
		_mkdir( $d );
		chdir( $d );
		$fn_map = DN_EMDB_MR . "/structures/EMD-$id/masks/$fn";

		//- 8bit map?
		if ( _is8bit( $fn_map ) ) {
			_mapconv( $fn_map );
			$fn_map = '../map.mrc';
		}

		//- チェック
		if ( ! file_exists( $fn_map ) ) {
			_problem( "$id: マスクファイルがない: $fn_map" );
			continue;
		}

		_spiderjob( $fn_map, $id, 'mask' ) ;

		_proc();
	}
} 

//. other loop
_line( 'Other' );

foreach( _idlist( 'emdb' ) as $id ) {
//	if ( $blist_other->inc( $id ) ) continue;
	$odn = _fn( 'emdb_med', $id ) . '/other';
	if ( ! is_dir( $odn ) ) continue;
	foreach ( scandir( $odn ) as $fn ) {
		if ( is_dir(" $odn/$fn" ) ) continue;
		$e = substr( _ext( $fn ), 0, 3 );
//		_m( "$id:$e" );
		if ( $e != 'map' and $e != 'mrc' ) continue;

		$d = "$odn/$fn.d";
		if ( is_dir( $d ) ) {
			if ( 10 < count( glob( "$d/*.jpg" ) ) )
				continue;
		}
		if ( _proc( "spiderjobs-other-$id-$num" ) ) continue;

		_m( "$id: otherデータ", 1 );
		_mkdir( $d );
		chdir( $d );
		$fn_map = "$odn/$fn";

		//- 8bit map?
		if ( _is8bit( $fn_map ) ) {
			_mapconv( $fn_map );
			$fn_map = '../map.mrc';
		}
//		die( $fn_map );

		//- チェック
		if ( ! file_exists( $fn_map ) ) {
			_problem( "$id: other マップファイルがない: $fn_map" );
			continue;
		}

		_spiderjob( $fn_map, $id, 'other' ) ;

		_proc();
	}
} 

//. ヒストグラム作り直しループ
//- movinfoの更新に対応

_line( 'ヒストグラム作り直し' );
foreach ( _idlist( 'emdb' ) as $id ) {
	if ( $blist->inc( $id ) ) continue;

	$dn = _fn( 'mapi', $id );
	if ( ! is_dir( $dn ) ) continue;
	chdir( $dn );
	
	if ( file_exists( 'hists.png' ) && _newer( 'hists.png', _fn( 'movinfo', $id ) ) )
		continue;
		
	_m( "$id: movieinfoが更新されたので、ヒストグラム再作成" );
	if ( _plothist( $id ) ) {
		_imgres( 'hist.png'   , 'hists.png' );
		_imgres( 'histlog.png', 'histlogs.png' );
	}
//	_pause();
}

_end();

//. func
//.. _spiderjob:
//- プロジェクション、セクション、ヒストグラム
//- in: 入力マップファイル名（フルパス）
//- カレントディレクトリに吐き出し

//... _spiderjob ちゃんと全部できるまでやる ラッパー関数
function _spiderjob( $fn_map, $id, $type ) {
	$maxrep = 3;
	foreach ( range( 1, $maxrep ) as $cnt ) {
		exec( 'rm -f *' );
		_spiderjob_main( $fn_map, $id, $type );

		$flg_ok = true;
		$fn_ng = [];
		$g = glob( '*' );
		foreach ( FLIST as $f ) {
			if ( in_array( $f, $g ) ) continue;
			$fn_ng[] = $f;
			$flg_ok = false;
		}
		if ( $flg_ok ) {
			_log( "$id: spider各種ファイル作成成功" );
			return;
		}
		_del( 'map.mrc' );
		_m( "$id - $fn_map: 作成失敗したファイル\n"
			. implode( "\n  ", $fn_ng )
			. "\nやりなおし $cnt 回目"
			, -1 
		);
	}
	_proc();
	_problem( "$id: spider-jobs $maxrep 回連続失敗" );

	return ;
}

//.. _spiderjob_main 本体関数
function _spiderjob_main( $fn_map, $id, $type ) {
	_m( "マップ名: $fn_map" );
	$id_type = "$id: ($type)";

	//- マップのサイズ取得
	//- x_orig,y_orig,z_orig もとのxyz
	//- x,y,z 付け足したあとのxyz

	$thr = shell_exec( IMINFO . " $fn_map | grep [0-9]*x[0-9]*x[0-9]*" );
	preg_match( '/([0-9]+)x([0-9]+)x([0-9]+)/', $thr, $d );
	list( $dummy, $x, $y , $z ) = $d;

	//... 軸の長さチェック
	if ( $x == '' || $y == '' || $z == '' ) {
		//- 7845と3821はマップから読み取れない
		$j = null;
		if ( $type == 'main' )
			$j = _json_load2(['emdb_new_json', $id])->map->spacing;
		if ( _instr( 'half_map', $fn_map ) )
			$j = _json_load2(['emdb_new_json', $id])->interpretation->half_map_list->spacing;
		if ( $j ) {
			list( $x, $y, $z ) = [ $j->x , $j->y, $j->z ];
		} else {
			//- メインマップ以外はなさげ
			$a = [];
			if ( $x == '' ) $a[] = 'x';
			if ( $y == '' ) $a[] = 'y';
			if ( $z == '' ) $a[] = 'z';
			_problem( "$id_type 軸の長さ情報が無い: " . _imp( $a ) );
			return;
		}

	}

//	if ( $id == 7485 ) {
//		list( $x, $y , $z ) = [ 64, 64, 64 ];
//	}
/*
	$j = _json_load2( _fn( 'mapinfo', $id ) );
	list( $dummy, $x, $y , $z ) = [
		0 => '' ,
		$j->MAPC => $j->NC ,
		$j->MAPR => $j->NR ,
		$j->MAPS => $j->NS ,
	];
	list( $dummy, $x, $y , $z ) = $d;
*/
	list( $x_orig, $y_orig, $z_orig ) = [ $x, $y, $z ];
	_m( "Map size: $x x $y x $z" );


	//... 一番長い軸から、大きさ決定

	$a = [ $x, $y, $z ];
	rsort( $a );
	list( $len1, $len2, $len3 ) = $a;

	//- 長い構造？
	$flg_long = $len2 > $len1 && $len3 > $len1;

	//- 平べったいマップだったら、長いことにしない？
	if ( $len3 * 10 < $len1 && $len2 * 10 < $len1 )
		$flg_long = false;

	//- 巨大マップだったら、長いことにしない？
	if ( $len1 > 1000 )
		$flg_long = false;

	$pmap = 'map'; //- 入力ファイル名
	$smap = 'map';
	if ( $flg_long ) {
		_m( "長い構造!!" );
		$pmap = 'map2';
		$smap = 'map2s';
		$x = $y = $z = $len1;
	}


	//... セクションの位置
	$x1 = round( $x / 2 - $x_orig / 6 );
	$x2 = round( $x / 2 );
	$x3 = round( $x / 2 + $x_orig / 6 );

	$y1 = round( $y / 2 - $y_orig / 6 );
	$y2 = round( $y / 2 );
	$y3 = round( $y / 2 + $y_orig / 6 );

	$z1 = round( $z / 2 - $z_orig / 6 );
	$z2 = round( $z / 2 );
	$z3 = round( $z / 2 + $z_orig / 6 );

	//... convert to spi
	if ( ! file_exists( $fn_map ) ) {
		_problem( "$id: 入力マップファイルがない: $fn_map" );
		_m( "cwd: " . getcwd() ); 
		return;
	}

	_spider( "cp from ccp4 \n $fn_map \n map \n n \n en \n",
		"$id-$type spider形式へコンバート" );
	if ( ! file_exists( "map.spi" ) )
		_problem( "$id_type spiderファイル作成失敗: map.spi" );
	
	//... 長い構造なら、継ぎ足し
	if ( $flg_long ) {
		_m( "長い構造用の処理", 1 );
		//- 表面用 ノーマライズなし
		passthru( PROC3D . " $fn_map map2s.spi spidersingle clip=$len1,$len1,$len1" );

		//- その他用 ノーマライズあり
		passthru( PROC3D . " $fn_map temp.mrc norm" );
		passthru( PROC3D . " temp.mrc map2.spi spidersingle clip=$len1,$len1,$len1" );
		_del( "temp.mrc" );
	}

	//... spider
	_spider( ''
		//- projections
		. " pj 3 \n $pmap \n $x,$y \n pj0 \n 0,0,0 \n"
		. " pj 3 \n $pmap \n $x,$z \n pj2 \n 270,90,90 \n"
		. " pj 3 \n $pmap \n $y,$z \n pj3 \n 0,90,90 \n"
		
		//- slices
		. " ps x \n $pmap \n slc_xa \n $x1 \n"
		. " ps x \n $pmap \n slc_xb \n $x2 \n"
		. " ps x \n $pmap \n slc_xc \n $x3 \n"
		. " ps   \n $pmap \n slc_ya \n $y1 \n"
		. " ps   \n $pmap \n slc_yb \n $y2 \n"
		. " ps   \n $pmap \n slc_yc \n $y3 \n"
		. " ps z \n $pmap \n slc_za \n $z1 \n"
		. " ps z \n $pmap \n slc_zb \n $z2 \n"
		. " ps z \n $pmap \n slc_zc \n $z3 \n"
		
		//- histogram
		. " hi d \n  map \n hist \n"

		//- end
		. " en \n"
	,
		"$id-$type: プロジェクション画像作成"
	);

	//... surfaces
	if ( $type == 'main' ) {
		$surfval = 
			_json_load([ 'movinfo', $id ])[1][ 'threshold' ]
			?: _json_load2([ 'emdb_new_json', $id ])->map->contour[0]->level
		;
		if ( $surfval != '' ) {
			$xd = round( $x / 2 );
			$yd = round( $y / 2 );
			$zd = round( $z / 2 );
			
			$p = [ //- 0:axis, 1:ang, 2:size, 3:depth, 4: filename
				'x' => [ 'z', '90' , $y, "-$xd, $xd", 'surf_x' ] ,
				'y' => [ 'z', '180', $x, "-$yd, $yd", 'surf_y' ] ,
				'z' => [ 'y', '0'  , $x, "-$zd, $zd", 'surf_z' ]
			];

			foreach ( [ 'x', 'y', 'z' ] as $ang ) {
				_spider( strtr( SP_SURF, [
					'<infile>'	=> $smap ,
					'<axis>'	=> $p[ $ang ][0] ,
					'<size>'	=> $p[ $ang ][2] ,
					'<depth>'	=> $p[ $ang ][3] ,
					'<ang>'		=> $p[ $ang ][1] ,
					'<thr>'		=> $surfval
				]) ,
					"$id-$ang: 表面図作成"
				);
				_imgconv( 'img.tiff', $p[ $ang ][4] . '.png' );
				_imgres(  'img.tiff', $p[ $ang ][4] . '.jpg', '100x100' );
			}
		}
	}

	//... copy img
	//- proj
	foreach ( [ 0, 2, 3 ] as $i ) {
		exec( PROC2D . " pj$i.spi proj$i.png png" );
		_imgres( "proj$i.png",  "proj$i.jpg", '100x100' );
	}
	_m( 'copied projection imgs' );

	//- slices
	foreach ( [ 'x', 'y', 'z' ] as $s1 ) foreach ( [ 'a', 'b', 'c' ] as $s2 ) {
		$f = "slc_$s1$s2";
		if ( ! file_exists( "$f.spi" ) ) continue;
		exec( PROC2D . " $f.spi $f.png png" );
		_imgres( "$f.png",  "$f.jpg", '100x100' );
	}
	_m( 'copied slice imgs' );

	//... histogram
	copy( 'hist.spi', 'hist.txt' );
	if ( _plothist( $id ) ) {
		_imgres( 'hist.png'   , 'hists.png' );
		_imgres( 'histlog.png', 'histlogs.png' );
	}


	//... clean
	exec( 'rm *.spi *.spi.* *.tiff' );
//	_log( "made projections" );
	
}

//.. _plothist
//- 表面レベルの縦線入りプロット

function _plothist( $id = '' ) {
	//... 元データが無い
	if ( ! file_exists( 'hist.txt' ) ) {
		_problem( "$id: hist.txtがない" );
		return false;
	}

	//... メインマップ以外では、普通のPlot
	if ( substr( getcwd(), -5 ) != '/mapi' ) {
		_m( "chdir = " . getcwd() );
		_gnuplot( PLOT );
		return true;
	}

	//... 最大値 (縦線の一番上)	
	$v = [];
	foreach ( _file( 'hist.txt' ) as $line ) {
		$a = preg_split( '/ +/', $line, NULL, PREG_SPLIT_NO_EMPTY );
		if ( ! _instr( ':', $a[3] ) )
			$v[] = $a[3];
	}
	$max = max( $v ) * 1.1;
	$add = "set trange[0:$max]\n";

	//... 各レベル

	$contour = _json_load2([ 'emdb_new_json', $id ])->map->contour[0];
	$lev_xml = $contour->level;
	$src_xml = $contour->source;
	$color = strtolower( $src_xml ) == 'author' ? '0000d0' : '00d000';
	$lev_mov = _json_load([ 'movinfo', $id ])[1][ 'threshold' ];

	//... gnuplot スクリプトに埋め込む文字列
	$addp = '';
	if ( $lev_mov != '' ) {
		$add .= "const1 = $lev_mov\n";
		$addp .= ", const1,t title \"Surface, movie #1 ($lev_mov)\" "
			. "lt rgb \"#d00000\" linewidth 3"
		;
	}

	if ( $lev_xml != '' ) {
		$add .= "const2 = $lev_xml\n";
		$addp .= ", const2,t title \"Surface, by $src_xml ($lev_xml)\" "
			. "lt rgb \"#$color\" linewidth 3"
		;
	}

	//... 実行
	_m( "ヒストグラム - $id" );
	_kvtable([
		'level - xml' => $lev_xml ,
		'level - xml source' => $src_xml ,
		'level - mov'  => $lev_mov ,
	]);
	_gnuplot( strtr( PLOT2, [ '<add>' => $add, '<plot>' => $addp ] ) );
	return true;
}

//.. _spider: スパイダーコマンド実行
//- _spider:
function _spider( $cmd, $comment = '' ) {
	if ( ! defined( 'SPIDER' ) )
		_envset( 'spider' );
	
	_line( 'SPIDERスクリプト 開始',  $comment );

	$c = preg_replace( "/[\n\r]+/", "\n", $cmd );
	passthru( "echo \"$c\" | " . SPIDER . " spi" );
	_m( "終了: $comment", 1 );
}

//.. _is8bit: 8bitマップか？
function _is8bit( $fn_map ) {
	foreach ( _map2map( $fn_map ) as $s ) {
		if ( strpos( $out, "MODE =        0" ) === false ) continue;
		return 1;
	}
}

//.. _mapconv: 8bitマップをmrcに変換
function _mapconv( $fn_map ) {
	_exec( 'echo 2 | ' . MAP2MAP . " $fn_map map.situs" );
	_exec( 'echo 7 | ' . MAP2MAP . " map.situs map.mrc" );
	_del( 'map.situs' );
	_m( "$fn: 8bitマップをmrcに変換", 1 );
}
