<?php
/*
mng / 計算サーバー / cgi(omoview) 3箇所共通スクリプト
_tempfn関数は、サーバーによって違う？
実体は omokage ディレクトリ
*/

//. init

//- R pcaコマンド
define( 'R_PCA_CMD', <<<EOD
pc<-princomp(read.csv("<fn_in>"),cor=FALSE)
sink("<fn_out>")
pc
sink()
EOD
);

//- フィルタ大きさ
define( 'FLTWD', 0.3 );

//- プロファイル先頭の無視する長さ
define( 'IGNLEN', 0.02 );

//- スコアノーマライズ値
define( 'SCNORM', 2.17938653227261 );

//- omopreのスコアリミット
define( 'OMOPRE_SCORE_LIMIT', [
	 0 => 0.5 ,
	 1 => 0.6 ,
	 2 => 0.65 ,
	 3 => 0.65 ,
	 4 => 0.7 ,
	 5 => 0.75 ,
	 6 => 0.8  ,
	 7 => 0.8  ,
	 8 => 0.85 ,
	 9 => 0.85 ,
	10 => 0.9 ,
	11 => 0.9 ,
	12 => 0.9 ,
	13 => 0.9 ,
	14 => 0.9 ,
	15 => 0.9 ,
	16 => 0.9 ,
	17 => 0.9 ,
	18 => 0.9 ,
	19 => 0.9 ,
]);

//. function
//.. _getcrd
//- pdb形式データから原子座標をget
function _getcrd( $fn ) {
	$ret = [];
	foreach ( _file( $fn )  as $n => $l ) {
		$atom[ $n ][ 'x' ] = substr( $l, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $l, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $l, 46, 8 );
	}
	return $atom;
}

//.. _get4profs
//- VQから4種類のプロファイルを作成し、返す
function _get4profs( $atom, $atom30, $flg_array = false ) {
	$getprof = $flg_array ? '_getprof2' : '_getprof' ;

	//... vq30, 50
	//- vq30
	$data[] = $getprof( $atom30 );

	//- vq50
	$data[] = $getprof( $atom );

	//... outer
	//- 重心
	$cg = [];
	foreach ( $atom as $a ) {
		$cg[ 'x' ] += $a[ 'x' ];
		$cg[ 'y' ] += $a[ 'y' ];
		$cg[ 'z' ] += $a[ 'z' ];
	}
	$cg[ 'x' ] /= 50;
	$cg[ 'y' ] /= 50;
	$cg[ 'z' ] /= 50;

	//- 重心からの距離
	$dist = [];
	foreach ( $atom as $num => $a )
		$dist[ $num ] = _dist( $cg, $a );

	//- 遠い順
	arsort( $dist ); //- 「値」を基準に降順ソート
	$atom2 = [];
	foreach ( $dist as $num => $v )
		$atom2[] = $atom[ $num ];

	$data[] = $getprof( $atom2, 25 );

	//... pca
	$fn1 = _tempfn( 'csv' );
	$fn2 = _tempfn( 'txt' );

	//- 座標csv作成
	$s = "x,y,z\n";
	foreach ( $atom as $a )
		$s .= $a[ 'x' ] .','. $a[ 'y' ] .','. $a[ 'z' ] . "\n" ;
	file_put_contents( $fn1, $s );

	//- pca計算
	_Rrun( strtr( R_PCA_CMD, [ '<fn_in>' => $fn1, '<fn_out>' => $fn2 ] ) );

//	_pause( _file( $fn2 ) );
//	$out = [];
//	foreach ( _file( $fn2 ) as $line ) {
//		if ( is_numeric( substr( $line, 0, 1 ) ) ) break;
//	}
//

	foreach ( _file( $fn2 ) as $line ) {
		$out = preg_split( '/ +/', trim( $line ) );
		if (
			preg_match( '/^[0-9\.]+$/', $out[0] ) && 0 < $out[0] &&
			preg_match( '/^[0-9\.]+$/', $out[1] ) && 0 < $out[1] &&
			preg_match( '/^[0-9\.]+$/', $out[2] ) && 0 < $out[2]
		) {
			break;
		} else {
			$out = [0,0,0];
		}
	}

	_del( $fn1, $fn2 );
//	_pause( $out );

	//- data
	$data[] = $out[0];
	$data[] = $out[1];
	$data[] = $out[2];

	//... retrun
	return $data;
}

//.. _getprof
//- VQ座標からプロファイルを作成、コンマ区切りで返す
function _getprof( $atom, $cnt = '' ) {
	$n = $cnt == '' ? count( $atom ) : $cnt ;

	//- 全組み合わせ距離
	$prof = [];
	for ( $a1 = 0; $a1 < $n; $a1 ++ ) {
		for ( $a2 = $a1 + 1; $a2 < $n; $a2 ++ ) {
			$prof[] = _dist( $atom[ $a1 ], $atom[ $a2 ] );
		}
	}
	sort( $prof );
//	_error( $prof );
	
	//- 微分もどき
	$out = [];
	$fv = floor( count( $prof ) * FLTWD );
	foreach ( $prof as $i => $v ) {
		for ( $j = 1; $j <= $fv; ++ $j ) {
			$d = $prof[ $i + $j ];
			if ( $d == 0 ) break;
			$out[ $i ] += $d - $v;
		}
	}

//	$out2 = [];
//	foreach ( $out as $n )
//		$out2[] = _keta( $n );
	
	return implode( ',', $out );
}

//.. _getprof2
//- VQ座標からプロファイルを作成、arrayで返す
function _getprof2( $atom, $cnt = '' ) {
	$n = $cnt == '' ? count( $atom ) : $cnt ;

	//- 全組み合わせ距離
	$prof = [];
	for ( $a1 = 0; $a1 < $n; $a1 ++ ) {
		for ( $a2 = $a1 + 1; $a2 < $n; $a2 ++ ) {
			$prof[] = _dist( $atom[ $a1 ], $atom[ $a2 ] );
		}
	}
	sort( $prof );
	
	//- 微分もどき
	$out = [];
	$fv = floor( count( $prof ) * FLTWD );
	foreach ( $prof as $i => $v ) {
		for ( $j = 1; $j <= $fv; ++ $j ) {
			$d = $prof[ $i + $j ];
			if ( $d == 0 ) break;
			$out[ $i ] += $d - $v;
		}
	}
	return $out;
}

//.. _dist: 距離
function _dist( $a, $b ) {
	return sqrt(
		pow( $a[ 'x' ] - $b[ 'x' ], 2 ) +
		pow( $a[ 'y' ] - $b[ 'y' ], 2 ) +
		pow( $a[ 'z' ] - $b[ 'z' ], 2 )
	);
}

//.. _keta: 有効桁数を揃える
function _keta( $in, $yk = 6 ) {
	$keta = 1;
	foreach ( [ 1, 10, 100, 1000, 10000, 100000, 1000000 ] as $i ) {
		if ( $in < $i ) break;
		$keta = $i;
	}
	return ( round( $in / $keta, $yk ) ) * $keta;
}

//.. _saveprofs
//- 改行区切りで保存
//- プロファイル読み込みは _file( $fn )でOK
//- 使ってない
function _saveprofs( $fn, $data ) {
	file_put_contents( $fn, implode( "\n", $data ) );
}

//.. _getscore スコア
/*
	$par:
		- _count_ign() で自動的にプロファイルから読み込みできる
		- 高速化のため指定できるようになっている
*/
function _getscore( $prof1, $prof2, $par = [] ) {
	//- パラメータ取得
	if ( count( $par ) == 0 )
		$par = _count_ign( $prof1 );
	extract( $par ); //- $count, $ign, $pnum


	//- 個別スコア
	foreach ( $prof1 as $n => $p ) {
		$sum = 0;
		$wsum = 0;
		for ( $i = $ign[ $n ]; $i < $count[ $n ]; ++ $i ) {
			$sum  += pow( $p[ $i ] - $prof2[ $n ][ $i ] , 2 );
			$wsum += pow( $p[ $i ] + $prof2[ $n ][ $i ] , 2 );
		}
		if ( $wsum != 0 )
			$sc[ $n ] = 1 - sqrt( $sum / $wsum );
		else
			$sc[ $n ] = 0;
	}


	//- merge
	$sum = 0;
	foreach ( $sc as $s )
		$sum += pow( $s, 2 );
	return round( pow( sqrt( $sum / $pnum ), SCNORM ) * 2 - 1, 4 ) ;
}

//.. _count_ign
//- プロファイルの要素数、無視する長さ、プロファイルの個数
function _count_ign( $sprof ) {
	$ign = [];
	$count = [];
	foreach ( $sprof as $n => $v ) {
		$count[ $n ] = count( $v );
		$ign[ $n ] = round( $count[ $n ] * IGNLEN );
	}
	return [ 'ign' => $ign, 'count' => $count, 'pnum' => count( $sprof ) ];
}

//.. _bin2prof
//- バイナリデータからプロファイルを復元
function _bin2prof( $bin ) {
	$a = unpack( 'f*', $bin );
//	_m( count( $a ) );
//	die();
	return count( $a ) == 159
		? [
			array_slice( $a, 0	, 55 ) ,
			array_slice( $a, 55	, 51 ) ,
			array_slice( $a, 106, 50 ) ,
			array_slice( $a, 156, 3 )
		] :[
			array_slice( $a, 0	, 109 ) ,
			array_slice( $a, 109, 102 ) ,
			array_slice( $a, 211, 100) ,
			array_slice( $a, 311, 3 )
		]
	;
}

//.. _prof2bin: (shrinkしてあるプロファイルのみ)
function _prof2bin( $json ) {
	$bin = '';
	foreach ( $json as $prof ) {
		foreach ( $prof as $val ) {
			$bin .= pack( 'f', $val );
		}
	}
	return $bin;
}

//.. _bin2compos
function _bin2compos( $bin ) {
	return $bin == '' ? [] : unpack( 'S*', $bin );
}

//.. _prefilter_sql
function _prefilter_sql( $pca, $level ) {
	$m1 = $pca[0] * $level;
	$m2 = $pca[1] * $level;
	$m3 = $pca[2] * $level;
	$l1 = $pca[0] / $level;
	$l2 = $pca[1] / $level;
	$l3 = $pca[2] / $level;
	return ''
		. " AND $m1 < pca1 AND pca1 < $l1"
		. " AND $m2 < pca2 AND pca2 < $l2"
		. " AND $m3 < pca3 AND pca3 < $l3"
	;
}

//.. cls_omo_small_search 小規模検索
class cls_omo_small_search {
	protected $obj_db = [];
	const PREFILT_LEV = [ 0.85, 0.7, 0.5 ];
	const SCORE_LIMIT = 0.7;
	
	//... construct
	function __construct( $db_type = '' ) {
		$this->obj_db = $db_type == 'pre'
			? [
				's'  => new cls_sqlite( '/yorodumi/sqlite/omokage_pre/profdb_s.sqlite' ) ,
				'ss' => new cls_sqlite( '/yorodumi/sqlite/omokage_pre/profdb_ss.sqlite' ) ,
			] : [
				's'  => new cls_sqlite( 'profdb_s' ) ,
				'ss' => new cls_sqlite( 'profdb_ss' ) ,
			]
		;
	}

	
	//... do
	function do( $id_query ) {
		//- stage 1
		$query_prof = $this->getprof( $id_query, 'ss' );
		foreach ( self::PREFILT_LEV as $level ) {
			$result = $this->obj_db['ss']->qar([
				'select' => [ 'id', 'data' ] ,
				'where' => "id <> '$id_query'". _prefilter_sql( $query_prof[3], $level )
			]);
			if ( $result ) break;
		}

		//- stage 2
		$count_ign = _count_ign( $query_prof );
		$list = [];
		foreach ( $result as $c ) {
			$id = $data = null;
			extract( $c );
			$s = _getscore( $query_prof, _bin2prof( $data ), $count_ign );
			if ( $s < self::SCORE_LIMIT ) continue;
			$list[ $id ] = $s;
		}
		arsort( $list );

		//- stage 3
		$query_prof = $this->getprof( $id_query, 's' );
		$count_ign = _count_ign( $query_prof );
		$list2 = [];
		foreach ( array_keys( array_slice( $list, 0, 20 ) ) as $id ) {
			$list2[ $id ] = _getscore( $query_prof, $this->getprof( $id, 's' ), $count_ign );
		}
		arsort( $list2 );
		return $list2;
	}

	//... getprof
	function getprof( $id, $type ) {
		return _bin2prof( $this->obj_db[ $type ]->qcol([
			'select' => 'data' ,
			'where' => "id='$id'" ,
		])[0] );
	}
}

