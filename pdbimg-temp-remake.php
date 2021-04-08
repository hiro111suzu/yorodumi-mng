<?php
require_once( "commonlib.php" );
require_once( "pdbimg-common.php" );
$time_start = time();
$num = trim( $argv[1] ) ?: 30000;
_m( "$num 個の画像に対して実行" );

//. main
$data = [];
$dn = DN_DATA . '/pdb/img_asb/';

foreach ( glob( "$dn/*.jpg" ) as $pn ) {
	$id = basename( $pn, '.jpg' );
	$time = filemtime( $pn );
	$data[ $id ] = $time;
}

arsort( $data );
$v = array_values( $data );
$k = array_keys( $data );
_pause( '一番古い画像: ' . $k[0] . ' @ ' . date( 'c', $v[0] ) );
//_die();

foreach ( array_slice( $data, 0, $num ) as $id => $time ) {
	_m( "$id:"  . date( 'c', $time ) );
	rename( "$dn/$id.jpg", DN_REMAKE . "/$id.jpg" );
	$id4 = substr( $id, 0, 4 );
	_del( DN_DATA . "/pdb/img/$id4.jpg" );
}

_php( 'mng.php pdbimg' );

_m( round( ( time() - $time_start ) / 60 ) . " 分 / $num 画" );
