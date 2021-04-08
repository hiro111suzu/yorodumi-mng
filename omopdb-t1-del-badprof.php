omo-pdb-3: プロファイル作成

<?php
//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

$_filenames += [
	'sas_vq30'	=> DN_DATA . '/sas/vq/<name>-vq30.pdb' ,
	'sas_vq50'	=> DN_DATA . '/sas/vq/<name>-vq50.pdb' ,
];

//. PDB
foreach ( _idloop( 'prof_pdb' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) die();
//	$name = _fn2id( $fn );  //- 100d-1-50.pdb
	_check_json( 'pdb', $fn, _fn2id( $fn ) );
}

//. emdb
_count();
foreach ( _idloop( 'prof_emdb' ) as $fn ) {
	_count( 'emdb', 0 );
	_check_json( 'emdb', $fn, _fn2id( $fn ) );
}

//. sas
_count();
foreach ( _idloop( 'prof_sas' ) as $vqfn ) {
	_count( 'sasbdb', 0 );
	_check_json( 'sas', $fn, _fn2id( $fn ) );
}

//. function _check_json
function _check_json( $type, $fn, $name ) {
	$json = _json_load( $fn );
	if (
		0 < $json[3] && preg_match( '/^[0-9\.]+$/', $json[3] ) &&
		0 < $json[4] && preg_match( '/^[0-9\.]+$/', $json[4] ) &&
		0 < $json[5] && preg_match( '/^[0-9\.]+$/', $json[5] )
	) return;
	_del( $fn );
	_del( _fn( $type. '_vq30', $name ) );
	_del( _fn( $type. '_vq50', $name ) );
	_m( "$name: bad profile: ". _imp([ $json[3], $json[4], $json[5] ]) );
}
