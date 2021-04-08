<?php 
include "commonlib.php";
$query = 'secondary_citation';

foreach ( _idloop( 'emdb_new_json' ) as $fn ) {
	_cnt('total');
	$id = _fn2id( $fn );
	$json = _json_load2( $fn );
	foreach ( $json->deposition->primaryReference->journalArticle as $k => $v ) {
//		if ( ! _instr( 'ref_', $k ) ) continue;
		_cnt( $k );
	}
/*
	if ( _instr( $query, $txt ) ) {
		_m( "found: $id" );
		_cnt('hit');
	}
*/
}

_cnt();
