<?php
require_once( "commonlib.php" );

//. scan
define( 'ARC_EMDB', '/home/archive/ftp/pdbj-pre/pub/emdb' );

$json = _json_load2( ARC_EMDB. '/status/latest/emdb_update.json' );
$date = $json->releaseDate;
define( 'DN_MR_DIR', ARC_EMDB. '/structures/EMD-<id>' );
define( 'FN_MR_MAP', ARC_EMDB. '/structures/EMD-<id>/map/emd_<id>.map.gz' );

$data = [];
foreach ( $json->mapReleases->entries as $id ) {
	$id = explode( '-', $id, 2 )[1];
	if ( ! is_dir( strtr( DN_MR_DIR, [ '<id>' => $id ] ) ) )
		$data[ 'no dir' ][] = $id;
	else if ( ! file_exists( strtr( FN_MR_MAP, [ '<id>' => $id ] ) ) ) 
		$data[ 'no map' ][] = $id;
	else
		$data[ 'ok' ][] = $id;
}

//. result
_m( print_r( $data ) );	
define( 'FN_NO_MAP', DN_PREP. '/no_map.txt' );
define( 'FN_NO_DIR', DN_PREP. '/no_dir.txt' );

if ( $data[ 'no map' ] ) 
	file_put_contents( FN_NO_MAP, _out( 'no map' ) );
else
	_del( FN_NO_MAP );

if ( $data[ 'no dir' ] ) 
	file_put_contents( FN_NO_DIR, _out( 'no dir' ) );
else
	_del( FN_NO_DIR );

function _out( $name ) {
	global $data;
	return implode( "\n", $data[ $name ] ). "\n";
}
