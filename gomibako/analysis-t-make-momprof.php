<?
//- 距離プロファイルを作成
ini_set( "memory_limit", "512M" );

//. init
require_once( "commonlib.php" );
_initlog( "make distprof" );

//. main

foreach ( _joblist() as $job ) {
	echo ".";
	$db = $job[ 'db' ];
	$id = $job[ 'id' ];
	$s = "$_omokagedn/$db-$id";
	$vqfn 		= ( $db == 'emdb' ) ? "$s-vq2.pdb" : "$s-vq.pdb";
//	echo "[$vqfn]";
	$proffn		= "$s-prof07.txt";
//	$proffn2	= "$s-prof02.txt";
//	$proffn3	= "$s-prof03.txt";
//	$proffn4	= "$s-prof04.txt";
//	$proffn5	= "$s-prof05.txt";

	//- やるか？
	if ( ! file_exists( $vqfn ) ) continue;
	if ( $_redo )
		@unlink( $proffn );
	
	if ( file_exists( $proffn ) ) {
		if ( filemtime( $proffn ) > filemtime( $vqfn ) )
			continue;
	}

	//- やる
	$file = file( $vqfn );

	//- 座標をゲット
	$atom = array();
	foreach ( $file as $n => $line ) {
//		$r = preg_split( '/ +/', $line );
		$atom[ $n ][ 'x' ] = substr( $line, 30, 8 );
		$atom[ $n ][ 'y' ] = substr( $line, 38, 8 );
		$atom[ $n ][ 'z' ] = substr( $line, 46, 8 );
	}
	//- 全組み合わせの距離を計算
/*	$prof = array();
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
		unlink( $vqfn );
		unlink( "$jdata/$id/emd_$id.situs" );
		_log( "$id: エラー 大きさがゼロの構造" );
		continue;
	}
*/
	$n = count( $atom );
	$prof = array();
	foreach ( $atom as $n1 => $a1 ) {
		foreach ( $atom as $n2 => $a2 ) {
			if ( $n1 == $n2 ) continue;
			$prof[ $n1 ] += sqrt( 
				pow( $a1[ 'x' ] - $a2[ 'x' ], 2 ) +
				pow( $a1[ 'y' ] - $a2[ 'y' ], 2 ) +
				pow( $a1[ 'z' ] - $a2[ 'z' ], 2 )
			) ;
		}
	}
	sort( $prof );
	echo count( $prof );
/*
	//- 変化量
	$fv = 100; //- フィルタ
	$cnt = count( $prof );
	$prof2 = array();
	for ( $i = $fv; $i < $cnt; ++ $i ) {
		$prof2[] = $prof[ $i ] - $prof[ $i - $fv ];
	}

	//- 変化量のこり
	$prof3 = $prof2;
	for ( $i = $cnt - $fv; $i < $cnt; ++$i ) {
		$prof3[] = $prof[ $cnt - 1 ] - $prof[ $i ];
	}	
	
	//- モード4 変化量 その２
	$fv = 50; //- フィルタ
	$prof4 = array();
	for ( $i = $fv; $i < $cnt; ++ $i ) {
		$prof4[] = $prof[ $i ] - $prof[ $i - $fv ];
	}
	for ( $i = $cnt - $fv; $i < $cnt; ++$i ) {
		$prof4[] = $prof[ $cnt - 1 ] - $prof[ $i ];
	}	

	//- モード5 変化量 その3
	$fv = 150; //- フィルタ
	$prof5 = array();
	for ( $i = $fv; $i < $cnt; ++ $i ) {
		$prof5[] = $prof[ $i ] - $prof[ $i - $fv ];
	}
	for ( $i = $cnt - $fv; $i < $cnt; ++$i ) {
		$prof5[] = $prof[ $cnt - 1 ] - $prof[ $i ];
	}	
*/
	//- 保存
	file_put_contents( $proffn, implode( "\n", $prof ) );
//	file_put_contents( $proffn2, implode( "\n", $prof2 ) );
//	file_put_contents( $proffn3, implode( "\n", $prof3 ) );
//	file_put_contents( $proffn4, implode( "\n", $prof4 ) );
//	file_put_contents( $proffn5, implode( "\n", $prof5 ) );
	if ( file_exists( $proffn ) 
//		and file_exists( $proffn2 ) 
//		and file_exists( $proffn3 ) 
	)
		_log( "$id:データ保存しました : $n atoms / $cnt pairs" );
	else
		_log( "$id:！！！エラー！！！ データ保存失敗！！！" );
}


?>
