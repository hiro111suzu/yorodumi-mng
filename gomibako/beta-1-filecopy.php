<?php
//. init
/*
betaマップデータをコピー

*/

require_once( "commonlib.php" );
$srcbase = "$_rootdn/emdb-beta/structures";
$destbase = "$_rootdn/beta-test";

$idlist = array();
foreach ( scandir( $srcbase ) as $dn ) {
	if ( substr( $dn, 0, 3 ) != 'EMD' ) continue;
	$idlist[] = substr( $dn, -4 );
}

//. start main loop
foreach( $idlist as $id ) {
	print ".";
	$did = "emdb-$id";

	$srcdn = "$srcbase/EMD-$id";
	$destdn = "$destbase/$id";

	//.. makedir
	if ( ! is_dir( $destdn ) ) {
		if ( mkdir( $destdn ) )
			_print( "$id: made directory" );
		else
			_print( "$id: error !!! cound not make directory !!!!!" );
	}

	//.. copy map
	$src  = "$srcdn/map/emd_{$id}.map.gz";
	$dest = "$destdn/emd_{$id}.map";

	$flag = 0;
	if ( file_exists( $src ) ) {
		$srctime = filemtime( $src );
		if ( ! file_exists( $dest ) ) {
			$flag = 1;
			$msg = "$id: new map";
		} else if ( $srctime != filemtime( $dest ) ) {
			$flag = 1;
			$msg = "$id: changed map";
		}
	}
	
	if ( $flag ) {
		_print( $msg );
		_del( 'temp.map', 'temp.map.gz', $dest ); //- 一応消しておく
		copy( $src, 'temp.map.gz' );
		exec( "gunzip temp.map.gz" );
		rename( 'temp.map', $dest );
		_del( 'temp.map', 'temp.map.gz', "$destdn/proj3.jpg", "$destdn/emd_$id.situs" );
		touch( $dest, $srctime );
	}
}
