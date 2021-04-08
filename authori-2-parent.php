<?php
require_once( "commonlib.php" );
$data = [];
$data_lev = [];
//. main loop
$ref = _json_load( DN_PREP . '/authori/primary.json.gz' );
foreach ( $ref as $id => $ids ) {
	_count( 100 );
	$todos = $ids;
	$cnt = 1;
	while ( $todos != [] ) {
		$next = [];
		foreach ( $todos as $i1 ) {
			if ( $data[ $i1 ][ $id ] ) continue; //- 多重登録防止
			$data[ $i1 ][ $id ] = true;
			$data_lev[ $i1 ][ $cnt ][ $id ] = true;
//			_m( "$id -> $i1" );
			$next = array_merge( $next, (array)$ref[ $i1 ] );
		}
		$todos = $next;
//		_m(" $id: " . _imp( $next ) );
		++ $cnt;
		if ( $cnt > 5000 ) {
			_m( "$id: 無限ループ? " );
			break;
		}
	}
}

//.. all
$out = [];
$out_rev = [];
foreach ( $data as $id => $ar ) {
	ksort( $ar );
	$out[ $id ][ 'a' ] = count( $ar );
}

//.. parents/children
foreach ( $data_lev as $id => $ar ) {
	$ar = array_keys( $ar[1] );
	sort( $ar );
	$out[ $id ][ 'c' ] = $ar; //- 子供

	foreach ( $ar as $i )
		$out[ $i ][ 'p' ][] = $id;  //- 親
}

_m( count( $out ) );
_json_save( DN_PREP . '/authori/data.json.gz', $out );
