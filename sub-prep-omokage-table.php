プロットファイル作成
omokageマップ用
in:  disttable<mode>.csv
out: data/legacy/omokage-plot<mode>.json

<?php

//. init
require_once( "commonlib.php" );

//- mode
$mode = _omomode();

//- 入力ファイル
$distfn = DN_PREP . "/disttable$mode.csv";
if ( ! file_exists( $distfn ) )
	die( "ファイルがない: $distfn" );

//- 中間ファイル（Rの出力）
$plotfn = DN_PREP . "/plot_Rout$mode.csv";
_del( $plotfn );

//- 出力ファイル
$outfile = DN_DATA . "/legacy/omokage-plot$mode.json";

//- isoMDS
$iso = <<<EOD
library(MASS)
table<-read.csv("<infn>")
iso<-isoMDS(table,cmdscale(table))
write.csv(iso,"<outfn>")
EOD;

//- isoMDSがうごかないのでcmdscaleで
$cmd = <<<EOD
cs<-cmdscale(table<-read.csv("<infn>"))
write.csv(cs,"<outfn>")
EOD;

if ( $argv[1] == 'iso' ) {
	_m( "by isoMDS", 1 );
	$rcmd = $iso;
} else {
	_m( "by cmdscale", 1 );
	$rcmd = $cmd;
}

//.. プロットデータ作成
//- R-cmdscale実行
_Rrun( strtr( $rcmd, array( '<infn>' => $distfn, '<outfn>' => $plotfn ) ) );

if ( file_exists( $plotfn ) ) {
	_m( "mode = $mode: プロットファイル作成 成功" );
} else {
	_m( "mode = $mode: プロットファイル作成失敗", -1 );
	die();
}

//.. JSONファイル作成

_m( "$plotfn => " );

$xmax = 0;
$ymax = 0;
$xmin = 0;
$ymin = 0;

foreach ( _file( $plotfn ) as $line ) {
	$s = explode( ',', $line );
	$n = trim( $s[ 0 ], '"' );
	if ( $n == '' ) continue;
	$data[ $n ][ 'x' ] = $s[ 1 ];
	$data[ $n ][ 'y' ] = $s[ 2 ];
	if ( $xmax < $s[ 1 ] ) $xmax = $s[ 1 ];
	if ( $ymax < $s[ 2 ] ) $ymax = $s[ 2 ];
	if ( $xmin > $s[ 1 ] ) $xmin = $s[ 1 ];
	if ( $ymin > $s[ 2 ] ) $ymin = $s[ 2 ];
}

$xlen = $xmax - $xmin;
$ylen = $ymax - $ymin;

_m( "x: $xlen / y: $ylen = " . ( $xlen / $ylen ) );


$xflip = ( $data[ 'pdb-1lu3' ][ 'x' ] < 0 ); //- 一番小さい構造、左にいくように
$yflip = ( $data[ 'emdb-1599' ][ 'y' ] < 0 ); //- 丸いのは上にいくように

foreach ( $data as $id => $val ) {
	$data[ $id ][ 'x' ] = $xflip
		? ( $val[ 'x' ] - $xmin ) / $xlen
		: 1 - ( $val[ 'x' ] - $xmin ) / $xlen
	;
	$data[ $id ][ 'y' ] = $yflip
		? ( $val[ 'y' ] - $ymin ) / $ylen
		: 1 - ( $val[ 'y' ] - $ymin ) / $ylen
	;
}

_json_save( $outfile, $data );
_m( "$outfile: 保存" );
