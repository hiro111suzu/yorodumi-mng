<?php
include "commonlib.php";

$only_ids = explode( ',', $argv[1] );
$out = [];

foreach ( glob( _fn( 'pdb_json', '*' ) ) as $fn ) {
	$id = basename( $fn, '.json.gz' );
//	if ( $only_ids != [] )
//		if ( !in_array( $id, $only_ids ) ) continue;

	_count( 1000 );
	$o = [];
	foreach ( (array)_json_load2( $fn )->pdbx_entry_details as $c ) {
//		_m( $id );
		foreach( $c as $k => $v ) {
			$o[] = $k;
		}
	}
	if ( $o != [] ) {
		$o = "$id: " . implode( ', ', $o );
		_m( $o );
		$out[] = $o;
	}
}
file_put_contents( 'ids_pdbx_entry_details.txt', implode( "\n", $out ) );
