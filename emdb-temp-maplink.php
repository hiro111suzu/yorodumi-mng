<?php
//. init
require_once( "commonlib.php" );
define( 'DN_UNZIP_MAP', '../../../../../../unzipped_maps' );

$_filenames += [
	'map_souko' => DN_UNZIP_MAP. '/emd_<id>.map' ,
	'fsc_check' => DN_PREP. '/fsc_imgs/<id>.png' ,
];

//. start main loop
$_dn_ext_ids = [];
$_dn_ext_cnt = [];
foreach( _idlist( 'emdb' ) as $id ) {
	_count( 'emdb' );
	$did = "emdb-$id";

	//.. copy map
	$fn_slink = _fn( 'map', $id );
	$fn_map_souko = _fn( 'map_souko', $id ); //- 実体
	if ( !file_exists( $fn_map_souko ) ) continue;
	if ( !file_exists( $fn_slink ) ) {
//		_pause( "$id - nomap" );
//		exec( "ln -s $fn_map_souko $fn_slink" ); //- シンボリックリンク
		exec( "rm $fn_slink; ln -s $fn_map_souko $fn_slink" ); //- シンボリックリンク
	}
	
//	if ( filesize( $fn_slink ) < 10 )
//	_m( "$id: " . filesize( $fn_slink ) );
//	exec( "rm $fn_slink; ln -s $fn_map_souko $fn_slink" ); //- シンボリックリンク

}