全PDBデータ取得

<?php
//. init
require_once( "commonlib.php" );

_initlog( 'omopdb-1: 全PDBデータ取得' );
$depdn = DN_FDATA . '/pdb/dep/';
$asbdn = DN_FDATA . '/pdb/asb/';

//. main
_rsync( array(
	'from'	=> _kf1dir( '/pdbj-pre/pdb/' ) ,
	'to'	=> $depdn ,
	'opt'	=> '-L' //- シンボリックリンクの実体
));

_rsync( array(
	'from'	=> _kf1dir( '/rcsb-pre/pub/pdb/data/biounit/coordinates/all/' ) ,
	'to'	=> $asbdn ,
	'opt'	=> '-L' //- シンボリックリンクの実体
));
