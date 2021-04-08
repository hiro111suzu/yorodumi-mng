<?php
require_once( "commonlib.php" );
$info = [];
define( 'DN_OUT', DN_PREP. '/pdb_taglist' );
_mkdir( DN_OUT );
_mkdir( DN_OUT. '/json' );
$_filenames += [
	'taginfo' => DN_OUT. '/json/<id>.json.gz'
];

//. make tagname json
$flg_recount = false;
foreach ( _idloop( 'pdb_json_pre' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$id = _fn2id( $fn );

	$fn_out = _fn( 'taginfo', $id );
	if ( FLG_REDO )
		_del( $fn_out );
	if ( _newer( $fn_out, $fn ) ) continue;

	$flg_recount = true;
	$json = _json_load2( $fn );
	$out = [];

	foreach ( $json as $categ => $c1 ) {
		if ( !is_array( $c1 ) ){
			continue;
		}
		foreach ( $c1 as $c2 ) {
			foreach ( $c2 as $item => $val ) {
				if ( $val == '' ) continue;
				$out[ $categ ][ $item ] = 1;
			}
		}
	}
	_json_save( $fn_out, $out );
}
_delobs_pdb( 'taginfo' );

//. 集計
if ( ! $flg_recount ) {
	_end();
	_die( '再集計の必要なし' );
}

$stat =[];
$ids = [];
_count();
foreach ( _idloop( 'taginfo' ) as $fn ) {
	if ( _count( 'pdb', 0 ) ) _pause();
	$id = _fn2id( $fn );
	$json = _json_load( $fn );
	foreach ( _json_load( $fn ) as $categ => $c ) {
		_count_tag( $categ, $id );
		foreach ( array_keys( $c ) as $item ) {
			_count_tag( "$categ.$item", $id );
		}
	}
}
_comp_save( DN_DATA . '/pdb/dicitem_count.json.gz', $stat );
_comp_save( DN_DATA . '/pdb/dicitem_ids.json.gz', $ids );
_end();

function _count_tag( $tag, $id ) {
	global $ids, $stat;
	++ $stat[ $tag ];
	if ( $stat[ $tag ] < 100 ) {
		$ids[ $tag ][] = $id;
	}
}
