omo-pdb-3: プロファイル作成

<?php
//. init
require_once( "commonlib.php" );
require_once( "omo-common.php" );

$_filenames += [
	'sas_vq30'	=> DN_DATA. '/sas/vq/<name>-vq30.pdb' ,
	'sas_vq50'	=> DN_DATA. '/sas/vq/<name>-vq50.pdb' ,
];

$only = $argv[1];

$skip_pdb = false;
$skip_em  = false;
$skip_sas = false;

if ( $only == 'e' ) {
	$skip_pdb = true;
	$skip_sas = true;
	_m( "em だけ" );
}
if ( $only == 's' ) {
	$skip_pdb = true;
	$skip_em = true;
	_m( "sas だけ" );
}
$onlyid = '';
if ( is_numeric( $only ) ) {
	$skip_em = true;
	$skip_sas = true;
	$onlyid = $only;
	_m( "PDB-{$onlyid}xxx だけ" );
}

//. プロファイル作成
//.. PDB
_mkdir( DN_OMODATA. '/prof' );
if ( ! $skip_pdb ) foreach ( _idloop( 'pdb_vq50' ) as $fn_vq50 ) {
	if ( _count( 'pdb', 0 ) ) die();
	$name = _fn2id( $fn_vq50 );  //- 100d-1-50.pdb

	if ( $onlyid != '' )
		if ( substr( $name, 0, 4 ) != $onlyid ) continue;

	if ( _save_profdata(
		$fn_vq50 , //- vq50
		_fn( 'prof_pdb', $name )  //- outfn
	) )
		_log( "$name: プロファイル保存" );
}

//.. emdb
_mkdir( DN_OMODATA. '/prof-emdb' );
_count();
if ( ! $skip_em ) foreach ( _idloop( 'emdb_vq50' ) as $fn_vq50 ) {
	_count( 'emdb', 0 );
	$name = _fn2id( $fn_vq50 );
	if ( _save_profdata(
		$fn_vq50 , //- vqname
		_fn( 'prof_emdb', $name )  //- outfn
	) )
		_log( "$name: プロファイル保存" );
}

//.. sas
_mkdir( DN_OMODATA. '/prof-sas' );
_count();
if ( ! $skip_sas ) foreach ( _idloop( 'sas_vq50' ) as $fn_vq50 ) {
	_count( 'sasbdb', 0 );
	$name = _fn2id( $fn_vq50 );
	if ( _save_profdata(
		$fn_vq50 , //- vqname
		_fn( 'prof_sas', $name )  //- outfn
	) )
		_log( "$name: プロファイル保存" );
}

//. 取り消しデータ消去
//.. pdb
define( 'DEL_FLUG', true );
_line( '古いプロファイル削除' );
$cnt = 0;
if ( ! $skip_pdb ) foreach ( _idloop( 'prof_pdb' ) as $pn ) {
	_count( 'pdb' );
	$id = _fn2id( $pn );
	if ( file_exists( _fn( 'pdb_vq50', $id ) )
		&& file_exists( _fn( 'pdb_vq30', $id ) ) ) continue;
	__del( $pn );
 	_log( "$id: プロファイル削除" );
 	++ $cnt;
}

//.. emdb
if ( ! $skip_em ) foreach ( _idloop( 'prof_emdb' ) as $pn ) {
	$id = _fn2id( $pn );
	$f30 = _fn( 'emdb_vq30', $id );
	$f50 = _fn( 'emdb_vq50', $id );
	if ( file_exists( $f30 ) && file_exists( $f50 ) ) continue;
	__del( $pn );
	__del( $f30 );
	__del( $f50 );
	_log( "emdb-$id: プロファイル削除" );
}

//.. sasbdb
if ( ! $skip_sas ) foreach ( _idloop( 'prof_sas' ) as $pn ) {
	$id = _fn2id( $pn );
	$f30 = _fn( 'sas_vq30', $id );
	$f50 = _fn( 'sas_vq50', $id );
	
	if ( file_exists( $f30 ) && file_exists( $f50 ) ) continue;
	__del( $pn );
	__del( $f30 );
	__del( $f50 );
	_log( "SAS-model-$id: プロファイル削除" );
}
_end();

//. function __del
function __del( $fn ) {
	if ( DEL_FLUG )
		_del( $fn );
	else
		_m( '削除禁止モードで動作中', 'red' );
}

//. function _save_profdata
function _save_profdata( $fn_vq50, $fn_prof ) {
	//- profが新しければやらない
	$fn_vq30 = strtr( $fn_vq50, [ '50.pdb' => '30.pdb' ] );
	if ( ! file_exists( $fn_vq50 ) ) return;
	if ( ! file_exists( $fn_vq30 ) ) return;
	if ( _newer( $fn_prof, $fn_vq30 ) && _newer( $fn_prof, $fn_vq50 ) ) return;

	//.. プロファイル作成
	$atom30 = _getcrd( $fn_vq30 );
	$atom   = _getcrd( $fn_vq50 );
	if ( count( $atom30 ) != 30 || count( $atom ) != 50 ) {
		_m( "$id: bad data", -1 );
		_del( $fn_vq50 );
		_del( $fn_vq30 );
		return;
	}
	_json_save( $fn_prof, _get4profs( $atom, $atom30, true ) );

	//.. save
	return true;
}
