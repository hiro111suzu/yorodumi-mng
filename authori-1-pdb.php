<?php
require_once( "commonlib.php" );
$data = [];

$_repids = _json_load( DN_DATA . '/pdb/ids_replaced.json.gz' );
//_die( $rep[ '116l' ] );

//. main loop
//.. PDB
foreach ( _idloop( 'pdb_json' ) as $fn ) {
	if ( _count( 5000, 0 ) ) break;
	$id = _fn2id( $fn );

	$j = _json_load2( $fn );
	$ids = [];
	foreach ( (array)$j->refine as $c) {
		$ids = array_merge( $ids, _str2ids( $c->pdbx_starting_model ) );
	}
	foreach ( (array)$j->em_3d_fitting_list as $c ) {
		$ids = array_merge( $ids, _str2ids( $c->pdb_entry_id ) );
	}
	if ( $ids == [] ) continue;
	$data[ $id ] = _clean_ids( $ids, $id );
}

//.. EMDB
_line( 'EMDB' );
_count();
foreach ( _idloop( 'emdb_old_json' ) as $fn ) {
	if ( _count( 1000, 0 ) ) break;
	$id = 'e' . _fn2id( $fn );
	$j = _json_load2( $fn );

	$ids = [];
	foreach ( (array)$j->experiment->fitting as $c ) {
		$ids = array_merge( $ids, _str2ids( $c->pdbEntryId ) );
	}
	if ( $ids == [] ) continue;
	$data[ $id ] = _clean_ids( $ids );
}

_json_save( DN_PREP . '/authori/primary.json.gz', $data );


//. function _str2ids
function _str2ids( $str ) {
	$ids = [];
	if ( is_array( $str ) )
		$str = implode( ' ', $str );
	preg_match_all( '/\b[1-9][0-9a-zA-Z]{3}\b/', $str, $m );
	foreach ( (array)$m[0] as $i ) {
		$ids[] = strtolower( $i );
	}
	return $ids;
}

//. function _clean_ids
//- ???g?͏B??A?????ւ??G???g???Ή?
function _clean_ids( $ids, $id = '---' ){
	global $_repids;
	$ret = [];
	foreach ( _uniqfilt( $ids ) as $i ) {
		if ( $i == $id ) continue;
		if ( $_repids[ $i ] == '' ) {
			$ret[] = $i;
		} else {
			foreach ( $_repids[ $i ] as $j ) {
				if ( $j == $id ) continue;
				$ret[] = $j;
			}
		}
	}
	return $ret;
}
