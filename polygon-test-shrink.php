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

	$infn = "$dn/1.obj";
	$infn2 = "$dn/1.mtl";
//	if ( ! file_exists( $infn ) ) 
//		_pause( "nofile: $infn" );
	$outfn = "$dn/s1.ply";
	$outfn2 = "$dn/s1.mtl";
	if ( _newer( $outfn, $infn ) ) continue;

	_exec( DISPLAY . "meshlabserver -i $infn $nfn2 -o $outfn -s $mlx" );
	_pause( $id );

}
