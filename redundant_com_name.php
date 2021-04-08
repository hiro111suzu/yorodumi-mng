<?php
include "commonlib.php";

$only_ids = explode( ',', $argv[1] );
$out = [];

foreach ( _idloop( 'pdb_json' ) as $fn ) {
	$id = _fn2id( $fn );
	_count( 1000 );
//	if ( $only_ids != [] )
//		if ( !in_array( $id, $only_ids ) ) continue;
	foreach ( (array)_json_load2( $fn )->entity_name_com as $c ) {
		$nm = $c->name;
		if ( $nm == '' ) continue;

		$red = 0;
		$len = strlen( $nm );
		foreach ( range( 50, 2 ) as $i ) {
			$p = substr( $nm, 0, floor( $len / $i ) );
			if ( strlen( $p ) < 3 ) continue;
			if ( substr_count( $nm, $p ) != $i ) continue;
			$red = $i;
			break;
		}
		if ( $red == 0 ) continue;
		$eid = $c->entity_id;
		$o = "$id: entity-$eid: $red x \"$p\"";
		_m( $o );
		$out[] = $o;
	}
}
file_put_contents( 'redundant_com_name.txt', implode( "\n", $out ) );

