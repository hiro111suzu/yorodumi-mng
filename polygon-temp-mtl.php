Jmol ポリゴンデータ縮小
使っていない
<?php
//. init
require_once( "commonlib.php" );
$mlx = __DIR__ . '/filt.mlx';

//. start main loop
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 100 );
	$dn = DN_EMDB_MED . "/$id/ym";
	if ( ! is_dir( $dn ) ) continue;
	_m( $id );
//	$fn = "$dn/1.mtl";
//	file_put_contents( $fn,
//		preg_replace( '/^# .+\n/', '', file_get_contents( $fn ) )
//	);
	$fn = "$dn/1.obj";
	if ( !file_exists( $fn ) ) continue;
	file_put_contents( $fn,
		preg_replace( '/^# Created by .+\n/', '', 
			file_get_contents( $fn )
		)
	);
//	_pause();
}
