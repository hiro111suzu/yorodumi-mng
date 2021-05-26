<?php
//. init
require_once( "omopre-common.php" );
$list = [];

//. emdb
_line( 'EMDB' );
foreach ( ( new cls_sqlite( 'main' ) )->qar([
	'select' => [ 'id', 'release' ] ,
	'where' => 'database="EMDB"',
]) as $ar ) {
	$database = $id = $release = null;
	extract( $ar );
	_set( "e$id", $release, 'emdb' );
}

//. PDB
_line( 'PDB' );
foreach ( ( new cls_sqlite( 'pdb' ) )->qar([
	'select' => [ 'id', 'rdate', 'json' ]
]) as $ar ) {
	if ( _count( 'pdb', 0 ) ) break;
	$id = $rdate = $json= null;
	extract( $ar );

	$json = json_decode( $json );
	$omo_id = "$id-". (
		( in_array( $json->asb[0], (array)$json->identasb ) ? 'd' : $json->asb[0] )
		?: 'd'
	);
	_set( $omo_id, $rdate, 'pdb' );
}

//. sasbdb
_line( 'SASBDB' );
foreach ( _json_load( DN_PREP. '/sas/sas_mid.json' )['id2mid'] as $mid_set ) {
	foreach ( $mid_set as $mid )
	_set( "s$mid", '-', 'sasbdb' );
}
_cnt();

//. output
arsort( $list );
_save( FN_OMOPRE_IDLIST, array_keys( $list ) );

//. func
//.. _set
function _set( $id, $date, $db ) {
	global $list;

	//- すでにある
	$fn = _fn( 'omolist', $id );
	if ( file_exists( $fn ) ) {
		if ( filesize( $fn ) < 10 ) {
			_cnt( "$db - zero" );
			_del( $fn );
		} else {
			_cnt( "$db - exists" );
			return;
		}
	}

	//- DBにあるか
	if ( ! _ezsqlite([
		'dbname' => FN_DB_SS ,
		'select' => 'id' ,
		'where'  => [ 'id', $id ]
	]) ) {
//		_pause( $id );
		_cnt( "$db - no profile" );
		return;
	}

	//- 書き込み
	$list[ $id ] = $date;
	_cnt( "$db - todo" );
}
