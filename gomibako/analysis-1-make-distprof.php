<?php
/*
距離プロファイルを作成
*/

//. init

require_once( "commonlib.php" );
_initlog( "an-1: make distprof" );

_add_fn([
	'vq_pdb_30'	=> DN_DATA . '/vq/<id>-d-30.pdb' ,
	'vq_pdb_50'	=> DN_DATA . '/vq/<id>-d-50.pdb' ,
]);

//- vqの数
//$vqnum = _nn( $argar[ 'vq' ], 50  );

//- フィルタのサイズ
$fltwd = 0.3;

//- R pcaコマンド
$rcmd = <<<EOF
pc<-princomp(read.csv("<infn>"),cor=FALSE)
sink("<outfn>")
pc
sink()
EOF;
/*
$rcmd = <<<EOF
library(Rcmdr)
t<-read.csv("<infn>")
pc<-princomp(t,cor=FALSE)
sink("<outfn>")
pc
sink()
EOF;
*/

//. main
_count();

foreach ( $vqnums as $vqnum ) {
	_m(  "VQ: $vqnum 個", 1 );

	foreach ( _joblist( 'did' ) as $did ) {
		_count(100);

		//.. 準備

		$vqfn = substr( $did, 0, 1 ) == 'p'
			? _fn( "vq_pdb_$vqnum", substr( $did, -4 ) ) //- PDB
			: _fn( "vq$vqnum" ) //- EMDB
		;

		if ( ! file_exists( $vqfn ) )
			$vqfn = _fn( "prevq$vqnum" );
		$proffn = _fn( "prof$vqnum" );

		//.. やるか？
		if ( $_redo )
			_del( $proffn );
		if ( _newer( $proffn, $vqfn ) ) continue;

		//.. 座標をゲット
		$atom = [];
		foreach ( file( $vqfn ) as $n => $line ) {
			$atom[ $n ][ 'x' ] = substr( $line, 30, 8 );
			$atom[ $n ][ 'y' ] = substr( $line, 38, 8 );
			$atom[ $n ][ 'z' ] = substr( $line, 46, 8 );
		}

		//.. 全組み合わせの距離を計算 prof
		$n = count( $atom );
		$prof = [];
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
		if ( $prof[ 50 ] == 0 ) {
			_del( $vqfn );
			_log( "$did: エラー 大きさがゼロの構造" );
			continue;
		}

		//.. 微分もどき
		$out = [];
		$fv = floor( count( $prof ) * $fltwd );
		foreach ( $prof as $i => $v ) {
			for ( $j = 1; $j <= $fv; ++ $j ) {
				$d = $prof[ $i + $j ];
				if ( $d == 0 ) break;
				$out[ $i ] += $d - $v;
			}
		}

	/*
	{# 20*19/2
	{# 190*0.3
	57
	*/

		//.. 保存
		file_put_contents( $proffn, implode( "\n", $out ) );
		if ( file_exists( $proffn ) )
			_log( "$did:データ保存成功 : $n原子" );
		else
			_log( "$did:データ保存失敗", -1 );
	}
}

//. outer

_m( 'Outer profile', 1 );
_count();
foreach ( _joblist( 'did' ) as $did ) {

	//.. 準備
	_count( 100 );

	$vqfn = _fn( "vq50" );
	if ( ! file_exists( $vqfn ) )
		$vqfn = _fn( "prevq50" );
	$proffn = _fn( "profout" );

	//.. やるか？
	if ( $_redo )
		_del( $proffn );
	if ( _newer( $proffn, $vqfn ) ) continue;

	//.. 座標をゲット（重心から遠い順）
	$atom = [];
	$sum = [];
	foreach ( file( $vqfn ) as $n => $line ) {
		$str[ $n ] = $line;
		$atom[ $n ][ 'x' ] = substr( $line, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $line, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $line, 46, 8 );
		$sum[ 'x' ] += $atom[ $n ][ 'x' ];
		$sum[ 'y' ] += $atom[ $n ][ 'y' ];
		$sum[ 'z' ] += $atom[ $n ][ 'z' ];
	}
	//- 重心
	$cg[ 'x' ] = $sum[ 'x' ] / 50;
	$cg[ 'y' ] = $sum[ 'y' ] / 50;
	$cg[ 'z' ] = $sum[ 'z' ] / 50;

	//- 重心からの距離
	$dist = [];
	foreach ( $atom as $num => $a ) {
		$dist[ $num ] =sqrt( 
			pow( $cg[ 'x' ] - $a[ 'x' ], 2 ) +
			pow( $cg[ 'y' ] - $a[ 'y' ], 2 ) +
			pow( $cg[ 'z' ] - $a[ 'z' ], 2 )
		);
	}
	arsort( $dist ); //- 「値」を基準に降順ソート

	//- 作成
	$atom2 = [];
	foreach ( $dist as $num => $v )
		$atom2[] = $atom[ $num ];
	
	//- 
		//.. 全組み合わせの距離を計算 prof
	$n = 25; //count( $atom );
	$prof = [];
	for ( $a1 = 0; $a1 < $n; $a1 ++ ) {
		for ( $a2 = $a1 + 1; $a2 < $n; $a2 ++ ) {
			$prof[] = sqrt(
				pow( $atom2[ $a1 ][ 'x' ] - $atom2[ $a2 ][ 'x' ], 2 ) +
				pow( $atom2[ $a1 ][ 'y' ] - $atom2[ $a2 ][ 'y' ], 2 ) +
				pow( $atom2[ $a1 ][ 'z' ] - $atom2[ $a2 ][ 'z' ], 2 )
			);
		}
	}
	sort( $prof );
	if ( $prof[ 50 ] == 0 ) {
		_del( $vqfn );
		_log( "$did: エラー 大きさがゼロの構造" );
		continue;
	}

		//.. 微分もどき
	$out = [];
	$fv = floor( count( $prof ) * $fltwd );
	foreach ( $prof as $i => $v ) {
		for ( $j = 1; $j <= $fv; ++ $j ) {
			$d = $prof[ $i + $j ];
			if ( $d == 0 ) break;
			$out[ $i ] += $d - $v;
		}
	}

	/*
	{# 20*19/2
	{# 190*0.3
	57
	*/

		//.. 保存
	_log( _save( $proffn, $out )
		? "$did:データ保存しました : $n atoms"
		: "$did:！！！エラー！！！ データ保存失敗！！！"
	);

}

//. pca
_m( 'PCA', 1 );
_count();
foreach ( _joblist( 'did' ) as $did ) {

	//.. 準備
	_count( 100 );
	$vqfn = _fn( "vq50" );
	if ( ! file_exists( $vqfn ) )
		$vqfn = _fn( "prevq50" );
	$proffn = _fn( "profpca" );

	//.. やるか？
	if ( $_redo )
		_del( $proffn );
	if ( _newer( $proffn, $vqfn ) ) continue;

	//.. 座標ファイル作成
	$fn1 = _tempfn( 'csv' );
	$fn2 = _tempfn( 'txt' );

	$s = "x,y,z\n";
	foreach ( file( $vqfn ) as $l )
		$s .= _num( $l, 30 ) .','. _num( $l, 38 ) .','. _num( $l, 46 ) . "\n" ;
	file_put_contents( $fn1, $s );
	
	//.. PCA計算
	_Rrun( strtr( $rcmd, [ '<infn>' => $fn1, '<outfn>' => $fn2 ] ) );
	$a = _file( $fn2 );
	$out = preg_replace( '/ +/', "\n", trim( $a[4] ) );
	_log( file_put_contents( $proffn, $out )
		? "$did-PCA: データ保存PCA"
		: "$did-PCA:！！！エラー！！！ データ保存失敗！！！"
	);
	_del( $fn1, $fn2 );
}

function _num( $str, $num ) {
	return trim( substr( $str, $num, 8 ) );
}

_php( 'analysis-2-make-score-table' );
