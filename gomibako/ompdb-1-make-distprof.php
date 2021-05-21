<?php
//- 距離プロファイルを作成
ini_set( "memory_limit", "512M" );

//. init
require_once( "commonlib.php" );

//- コマンドファイル
$s = "1\n"	//- Do you want to mass-weight the atoms?(水とか) No
	. "1\n"	//- B-factor threshold? No
	. "50\n" //- cvの数
	. "1\n"		//- コネクティビティ計算するか？
	. "1\n"	//- No
;
file_put_contents( "cmd2.txt", $s );

//. main
$dn = 'ompdb-data';
foreach ( scandir( $dn ) as $fn ) {
	if ( is_dir( "ompdb/$fn" ) ) continue;
	if ( substr( $fn, -3 ) != 'ent' ) continue;

	//.. 準備
	echo ".";
	$id = preg_replace( '/\..+$/', '', $fn );
	$infile	= "$dn/$fn";
	$vqfn 	= "$dn/$id-vq.pdb";
	$proffn = "$dn/$id-prof.txt";

	//.. vq
	if ( ! file_exists( $vqfn ) ) {
		echo "$id: VQ作成開始";
		exec( "qpdb $infile $vqfn <cmd2.txt" );
		if ( file_exists( $vqfn ) )
			if ( filesize( $vqfn ) < 100 ) {
				unlink( $vqfn );
				_log( "失敗 !!!!!" );
			} else {
				_log( "VQ作成成功" );
			}
		else
			_log( "失敗 !!!!!" );
	}

	//.. vq
	if ( ! file_exists( $proffn ) and file_exists( $vqfn ) ) {
		echo "$id: プロファイル作成開始 ... ";
		exec( "qpdb $pdbfn $vqfn <cmd2.txt" );
		$atom = array();
		foreach ( file( $vqfn ) as $n => $line ) {
			$atom[ $n ][ 'x' ] = substr( $line, 30, 8 );
			$atom[ $n ][ 'y' ] = substr( $line, 38, 8 );
			$atom[ $n ][ 'z' ] = substr( $line, 46, 8 );
		}

		//... 全組み合わせの距離を計算 prof
		$n = count( $atom );
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
		if ( $prof[ 10 ] == 0 ) {
			_del( $vqfn );
			_log( "$id: エラー 大きさがゼロの構造" );
			continue;
		}
		file_put_contents( $proffn, implode( "\n", _diff( 150 ) ) );
		echo file_exists( $proffn ) ?  "成功\n" : "失敗\n";
	}
}


//. 微分もどき
function _diff( $fv, $flg = 0 ) {
	global $prof;
	$ret = array();
	foreach ( $prof as $i => $v ) {
		for ( $j = 1; $j <= $fv; ++ $j ) {
			$d = $prof[ $i + $j ];
			if ( $d == 0 ) break;
			$ret[ $i ] += $d - $v;
		}
		if ( $flg > 0 )
			$ret[ $i ] = sqrt( $ret[ $i ] );
	}
	return $ret;
}
