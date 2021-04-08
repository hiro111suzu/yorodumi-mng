<?php
//. init
require_once( "commonlib.php" );
$logdn = SCRIPTDIR . '/temp-log';

$maxid = _nn( $argv[1], 10000 );

foreach ( $emdbidlist as $id ) {
	if ( _proc( "png2jpeg-$id" ) ) continue;
	chdir( "$_jdata/$id" );

	$flg = 0;
	for ( $i = 1; $i < 20; ++ $i ) {
		$tarfn = "img$i.tar";
		if ( ! file_exists( $tarfn ) ) continue;
		$logfn = "$logdn/$id-$i";
		if ( file_exists( $logfn ) ) continue;
		_m( "$id-$i" );

		//- untar
		$dn = "img$i";
		exec( "rm -rf temptar" );
		mkdir( 'temptar' );
		exec( "rm -rf $dn" );
		mkdir( $dn );

		exec( "tar xf $tarfn -C temptar" );

		exec( "mv temptar/*/* $dn/" );
		exec( "rm -rf temptar" );

		//- すでにjpeg?
		if ( file_exists( "img$i/img00300.jpeg" ) ) {
			_m( "すでにjpeg形式になっている" );
			exec( "rm -rf img$i" );
			touch( $logfn );
			continue;
		}

		//- 変換
		exec( "mogrify -format jpeg img$i/*.png" );
		exec( "rm img$i/*.png" );

		//- 失敗？
		if ( ! file_exists( "img$i/img00300.jpeg" ) ) {
			exec( "rm -rf img$i" );
			_m( "失敗", -1 );
			continue;
		}
		_m( "変換完了" );
		_del( $tarfn );
//		exec( "rm movie$i.flv" );
//		exec( "rm movie$i.asf" );
//		exec( "rm movie$i.mov" );
//		exec( "rm movies$i.flv" );
		touch( $logfn );
		$flg = 1;
	}
	if ( $id >= $maxid ) break;
	if ( $flg )
		_php( 'movie-4-encode', 'nosnap' );
	_proc();
//	if ( $id > 1500 ) break;
}
