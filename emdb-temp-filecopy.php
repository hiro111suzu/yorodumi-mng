<?php
//. init
require_once( "commonlib.php" );
$_filenames += [
	'filelist'	=> DN_EMDB_MED . '/<id>/filelist.json' ,
	'map_souko' => '/mardisk4/unzipped_maps/emd_<id>.map' ,
	'fsc_check' => DN_PREP . '/fsc_imgs/<id>.png' ,
	'dir_info'  => DN_PREP . '/emdb_dirinfo/<id>-<s1>.json' ,
];

//. start main loop
$size = 0;
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 500 );
	$did = "emdb-$id";
	$dn_src  = DN_EMDB_MR . "/structures/EMD-$id/other";
	$dn_dest = DN_EMDB_MED . "/$id/other";
	foreach ( array_merge(
		glob( "$dn_src/*.map" ),
		glob( "$dn_src/*.mrc" )
	) as $pn ) {
		_m( $pn );
		_del( _fn( 'dir_info', $id, 'other' ) );
		$bn = basename( $pn );
		if ( file_exists( "$dn_dest/$bn" ) ) {
			unlink( "$dn_dest/$bn" );
			_m( "$bn deleted" );
		} else {
			_m( "$bn: nofile", 'red' );
		}
		_del(  "$dn_dest/$bn"  );
//		if ( file_exists( "$dn_dest/$bn" ) ) {
//			_m( 'ファイル削除'
//		}
//		_m( $pn . ' : ' . filesize( $pn ) );
//		$size += filesize( $pn );
	}
}
//$size = number_format( $size );
//_m( "sum: $size byte" );
