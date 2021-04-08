omokage-map 画像作成

<?php
//. init
require_once( "commonlib.php" );

//. 設定
$isize = 500;	//- 大画像サイズ
$isizef = 20;	//- 枠のサイズ
$isizetotal = $isize + $isizef * 2;

$dsize = 10;	//- ドットサイズ
$psize = 40;	//- 赤丸サイズ

$mode = _omomode();
$pfile = DN_DATA . "/legacy/omokage-plot$mode.json"; //- プロットデータファイル

exec( "rm $_omokagedn/*.jpg" );

//. データ読み込み
$plotdata = _json_load( $pfile );

$rnd = $plotdata;
foreach ( $rnd as $did => $a )
	$rnd[ $did ][ 'emdb' ] = ( substr( $did, 0, 1 ) == 'e' );
shuffle( $rnd );

//. 白い画像作成

$oimg = imagecreatetruecolor( $isizetotal, $isizetotal );
imagefill ( $oimg , 0, 0, imagecolorallocate($oimg, 255, 255, 255) );

//- R,G,B,alpha
//- alpha 
//- 0 から 127 までの値。 0 は完全に不透明な状態。 127 は完全に透明な状態を表します。

$red	= imagecolorallocatealpha($oimg, 255, 0, 0, 30 ); 
$green	= imagecolorallocatealpha($oimg, 0, 120, 0, 95 ); //- 使わない
$blue	= imagecolorallocatealpha($oimg, 0, 0, 150, 95 ); //- 使わない

$green2	= imagecolorallocatealpha($oimg, 0, 120, 0, 124 ); 
$blue2	= imagecolorallocatealpha($oimg, 0, 0, 150, 124 ); 

//$rads = array( 15, 9, 4, 2 )
//$rads = array( 16, 8, 4, 2 );
$rads = array( 18, 13, 8, 6, 3, 1 );
//$rads = array( 18, 10, 6, 3, 2, 1 );

//. 大画像作成ループ
foreach ( $rnd as $v ) {
	foreach ( $rads as $r ) {
		imagefilledellipse( $oimg , 
			round( $v[ 'x' ] * $isize + $isizef ) ,
			round( $v[ 'y' ] * $isize + $isizef ) ,
			$r, $r ,
			( $v[ 'emdb' ] ? $green2 : $blue2 )
		);
	}
}

imagejpeg( $oimg, "$_omokagedn/plot.jpg" )
	? _m( "Omokage map 大きい画像作成成功 " )
	: die( "Omokage map 大きい画像作成失敗!!!!" )
;

//. 個々の画像
$eimg = imagecreatetruecolor( $isizetotal, $isizetotal );
$simg = imagecreatetruecolor( 100, 100 );
foreach ( $plotdata as $did => $v ) {
	_count( 1000 );
	imagecopy( $eimg, $oimg, 0,0,0,0, $isizetotal, $isizetotal );
	imagefilledellipse( $eimg , 
		round( $v[ 'x' ] * $isize + $isizef ) ,
		round( $v[ 'y' ] * $isize + $isizef ) ,
		$psize, $psize ,
		$red
	);
	imagecopyresampled( $simg, $eimg, 0,0,0,0, 100, 100, $isizetotal, $isizetotal );
//	imagegif( $simg, "$_omokagedn/$did.gif" );
	imagejpeg( $simg, "$_omokagedn/$did.jpg" );
//	if ( $did == 'emdb-1020' ) break;
}

//リサイズ
//imagecopyresampled
