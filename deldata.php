<?php
//. misc init
require_once( "commonlib.php" );
define( 'MSG', <<<EOD
pre: Preファイル削除
ori: 方位情報削除
mov: ムービー削除
jmfit: Jmol fit ムービー 不使用化(eID-pID)
EOD
);

if ( count( $argv ) == 1 )
	die( MSG );
//	die( 'This is script to delete pdb data' );

define( 'MODE', $argv[1] );

//. mode
foreach ( $argv as $n => $id ) {
	if ( $n == 0 or $n == 1 ) continue;
	if ( ! _inlist( substr( $id, -4 ), 'epdb' ) and MODE != 'jmfit' ) {
		echo "ID error: $id";
		continue;
	}
	$dn = _fn( 'pdb_med', $id );
	if ( is_dir ( $dn ) ) {
		$dirs = scandir( $dn );
		echo "## $id: $dn\n";
	}
	//- pre
	if ( MODE == 'pre' ) foreach ( $dirs as $f ) {
		if ( substr( $f, 0, 4 ) != 'pre_' ) continue;
		$fn = "$dn/$f/5.jpg";
		_del( $fn );
		echo( "deleted: $fn\n" );
	} else if ( MODE == 'ori' ) {
	//- ori
		exec( "rm $dn/snap*.jpg" );
		foreach ( $dirs as $f ) {
			if ( substr( $f, 0, 4 ) != 'pre_' ) continue;
			$fn = "$dn/$f/ori.txt";
			_del( $fn );
			echo( "deleted: $fn\n" );
		}
	} else if ( MODE == 'mov' ) {
	//- mov
		exec( "rm $dn/movie*" );
		exec( "rm -rf $dn/mov_*" );
		exec( "rm $dn/snap*.jpg" );
		foreach ( $dirs as $f ) {
			if ( substr( $f, 0, 6 ) != 'movies' ) continue;
			$fn = "$dn/$f";
			_del( $fn );
			exec( "deleted: $fn\n" );
		}
	} else if ( MODE == 'jmfit' ) {
	//- jmfit
		list( $id, $eid ) = explode( '-', $id );
		$sn = "$dn/snapssjm$eid.jpg";
		if ( file_exists( $sn ) ) {
			exec( "rm $dn/snap*jm$eid.jpg" );
			exec( "rm $dn/movi*jm$eid.*" );
			exec( "rm -f $dn/pre_jm$eid/ori.txt" );
			touch( "$dn/pre_jm$eid/ng" );
			echo "削除完了\n";
		} else {
			echo "ファイルがない: $sn\n";
		}
	} else
		die( "モード名のエラー: ". MODE );
}

