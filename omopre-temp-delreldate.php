<?php
//. init
require_once( "omopre-common.php" );
$count = 0;
define( 'REL_DATE', _json_load2( DN_DATA. '/binfo.json' )->rel_date );
define( 'FLG_DO', $argv[1] == 'do' );

//. emdb
_line( 'EMDB' );
foreach ( ( new cls_sqlite( 'main' ) )->qar([
	'select' => [ 'id', 'release' ] ,
	'where' => 'database="EMDB"',
]) as $ar ) {
	$database = $id = $release = null;
	extract( $ar );
	_set( "e$id", $release );
}

//. PDB
_line( 'PDB' );
foreach ( ( new cls_sqlite( 'pdb' ) )->qar([
	'select' => [ 'id', 'rdate', 'json' ]
]) as $ar ) {
//	if ( _count( 'pdb', 0 ) ) break;
	$id = $rdate = $json= null;
	extract( $ar );

	$json = json_decode( $json );
	$omo_id = "$id-". (
		( in_array( $json->asb[0], (array)$json->identasb ) ? 'd' : $json->asb[0] )
		?: 'd'
	);
	_set( $omo_id, $rdate );
}

//. output
_m( REL_DATE. "公開予定データ: $count 個" );
_m( FLG_DO ? '削除完了' : 'doオプションで削除' );

//. func
//.. _set
function _set( $id, $date ) {
	global $count;
	if ( $date != REL_DATE ) return;
	$fn = _fn( 'omolist', $id );
	if ( ! file_exists( $fn ) ) return;
	++ $count;
	if ( FLG_DO ) {
		_m( "$id: データ削除" );
		_del( $fn );
	} else {
		_m( "$id: データ削除すべき" );
	}
}
