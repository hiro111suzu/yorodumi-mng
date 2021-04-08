<?php
require_once( "commonlib.php" );
define( 'DN_PRE', '/home/archive/ftp/pdbj-pre' );

// /home/archive/ftp/pdbj-pre/prd/PRD_000001.cif
// /home/archive/ftp/pdbj-pre/prdcc/PRDCC_000001.cif
$_filenames += [
	//- in
	'prd_cif'	=> DN_PRE. '/prd/PRD_<id>.cif' ,
	'prd_cifcc'	=> DN_PRE. '/prdcc/PRDCC_<id>.cif' ,
	'fam_cif'	=> DN_PRE. '/family/FAM_<id>.cif' ,

	//- out
	'fam_json'	=> DN_PREP. '/prd/fam_json/<id>.json.gz' ,
	'prd_json'	=> DN_DATA. '/prd/<id>.json.gz' ,

	//- img
	'bird_img'	=> DN_DATA. '/bird/img/<id>.jpg' ,
	'bird_img2'	=> DN_DATA. '/bird/img/<id>.svg' ,

];

/*
foreach ( _idloop( 'prd_cifcc' ) as $fn_cif ) {
	$id = _fn2id( $fn_cif );
	if ( !file_exists( _fn( 'prd_cif', $id ) ) )
		_m( "only cifcc: $id" );
}
*/
